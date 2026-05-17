<?php
// phpcs:disable WordPress.Security.NonceVerification.Missing -- All AJAX handlers
// call self::check_nonce_and_cap() as first statement; it invokes
// check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce') which validates the
// session-bound nonce. Plugin Check cannot trace static method indirection,
// so we document the verification via file-level disable.
/**
 * SEO_AEO_Regenerate_Content — Dedicated flow "Rigenera intera pagina" (v3.41.8).
 *
 * This class is the ONLY legit emitter of `typed_confirm=riscrivi`. The Orchestratore
 * standard preview/execute paths reject content_generator via the v3.41.7 403 guard;
 * this dedicated flow re-authorises the call after a 3-fold safety gate:
 *   (a) typed-confirm input "riscrivi"
 *   (b) checkbox "I accept the content will be replaced"
 *   (c) origin marker request_origin=content_regenerate_dedicated_flow
 *
 * Pre-write: double backup — field-level `_seo_aeo_content_backup` (via
 * aeo_create_field_backup) AND whole-post snapshot via SEO_AEO_Snapshot_Manager
 * with tag 'content_regen_pre_write'. Both restorable.
 *
 * AJAX surface (4 endpoints, all manage_options-gated):
 *   seo_aeo_orchestra_regen_get_page_info  → Step 1 (page metadata)
 *   seo_aeo_orchestra_regen_generate       → Step 2 (preview proposal, 50cr)
 *   seo_aeo_orchestra_regen_apply          → Step 3 (validate + backup + write)
 *   seo_aeo_orchestra_regen_rollback       → Step 4 (restore from backup)
 */

if (!defined('ABSPATH')) {
    exit;
}

class SEO_AEO_Regenerate_Content {

    const NONCE_ACTION = 'seo_aeo_orchestra_regen_nonce';
    const ORIGIN_MARKER = 'content_regenerate_dedicated_flow';
    const SNAPSHOT_TAG = 'content_regen_pre_write';
    const ESTIMATED_CREDITS = 50;

    public static function init() {
        add_action('wp_ajax_seo_aeo_orchestra_regen_get_page_info', array(__CLASS__, 'ajax_get_page_info'));
        add_action('wp_ajax_seo_aeo_orchestra_regen_generate',      array(__CLASS__, 'ajax_generate'));
        add_action('wp_ajax_seo_aeo_orchestra_regen_apply',         array(__CLASS__, 'ajax_apply'));
        add_action('wp_ajax_seo_aeo_orchestra_regen_rollback',      array(__CLASS__, 'ajax_rollback'));
    }

    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient capability.', 'aeo-orchestra'));
        }
        $tpl = SEO_AEO_DIR . 'templates/regenerate-page.php';
        if (!file_exists($tpl)) {
            echo '<div class="wrap"><p>Template non trovato.</p></div>';
            return;
        }
        include $tpl;
    }

    // ────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────

    private static function check_nonce_and_cap() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('code' => 'forbidden_cap'), 403);
        }
        if (!check_ajax_referer(self::NONCE_ACTION, 'nonce', false)) {
            wp_send_json_error(array('code' => 'bad_nonce'), 403);
        }
    }

    private static function compute_stats($content) {
        $stripped = wp_strip_all_tags((string) $content);
        $word_count = $stripped !== '' ? str_word_count($stripped) : 0;
        return array(
            'byte_size'        => strlen((string) $content),
            'word_count'       => $word_count,
            'reading_time_min' => $word_count > 0 ? max(1, (int) round($word_count / 200)) : 0,
        );
    }

    // ────────────────────────────────────────────────────────────────────
    // Step 1 — Page info
    // ────────────────────────────────────────────────────────────────────

    public static function ajax_get_page_info() {
        self::check_nonce_and_cap();
        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if ($post_id <= 0) {
            wp_send_json_error(array('code' => 'bad_post_id'), 400);
        }
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(array('code' => 'post_not_found'), 404);
        }
        $stats = self::compute_stats($post->post_content);
        $has_existing_backup = function_exists('aeo_field_backup_exists')
            && aeo_field_backup_exists($post_id, 'content');
        wp_send_json_success(array(
            'post_id'             => $post_id,
            'title'               => $post->post_title,
            'slug'                => $post->post_name,
            'url'                 => get_permalink($post_id),
            'post_status'         => $post->post_status,
            'modified_gmt'        => $post->post_modified_gmt,
            'current_byte_size'   => $stats['byte_size'],
            'current_word_count'  => $stats['word_count'],
            'reading_time_min'    => $stats['reading_time_min'],
            'has_existing_backup' => $has_existing_backup,
            'tier'                => 'DANGER',
            'estimated_credits'   => self::ESTIMATED_CREDITS,
        ));
    }

    // ────────────────────────────────────────────────────────────────────
    // Step 2 — Generate proposal (call backend /ai/aeo-content)
    // ────────────────────────────────────────────────────────────────────

    public static function ajax_generate() {
        self::check_nonce_and_cap();
        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if ($post_id <= 0) {
            wp_send_json_error(array('code' => 'bad_post_id'), 400);
        }
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(array('code' => 'post_not_found'), 404);
        }

        if (!class_exists('SEO_AEO_API_Client')) {
            wp_send_json_error(array('code' => 'api_client_missing'), 500);
        }
        $api = new SEO_AEO_API_Client();

        $keyword = isset($_POST['keyword']) ? sanitize_text_field(wp_unslash($_POST['keyword'])) : '';
        if ($keyword === '') {
            $words = preg_split('/\s+/u', strtolower($post->post_title));
            $words = array_filter($words, function($w) { return strlen($w) > 3; });
            $keyword = implode(' ', array_slice($words, 0, 3));
        }
        $body_excerpt = substr(wp_strip_all_tags($post->post_content), 0, 3000);

        $resp = $api->api_request('/ai/aeo-content', array(
            'topic'           => $keyword !== '' ? $keyword : $post->post_title,
            'keywords'        => array($keyword),
            'target_engines'  => array('google_ai', 'chatgpt', 'perplexity'),
            'include_schema'  => true,
            'include_faq'     => true,
            'language'        => 'it',
            'tier'            => 'premium',
            'context'         => array(
                'current_title'   => $post->post_title,
                'current_excerpt' => $body_excerpt,
                'request_origin'  => self::ORIGIN_MARKER,
            ),
        ));

        if (isset($resp['error'])) {
            wp_send_json_error(array(
                'code'    => 'backend_error',
                'message' => isset($resp['message']) ? (string) $resp['message'] : 'backend_failed',
            ), 502);
        }

        $proposed_content = isset($resp['content']) ? (string) $resp['content'] : '';
        if ($proposed_content === '') {
            wp_send_json_error(array('code' => 'empty_proposal'), 502);
        }

        $current_stats  = self::compute_stats($post->post_content);
        $proposed_stats = self::compute_stats($proposed_content);

        $byte_diff = $proposed_stats['byte_size'] - $current_stats['byte_size'];
        $byte_pct  = $current_stats['byte_size'] > 0
            ? round($byte_diff * 100.0 / $current_stats['byte_size'], 1)
            : 0;

        wp_send_json_success(array(
            'agent'      => 'content_generator',
            'tier'       => 'DANGER',
            'operation'  => 'replace_full',
            'current'    => array_merge(array('value' => $post->post_content), $current_stats),
            'proposed'   => array_merge(array('value' => $proposed_content), $proposed_stats),
            'delta'      => array(
                'byte_diff' => $byte_diff,
                'byte_pct'  => $byte_pct,
                'word_diff' => $proposed_stats['word_count'] - $current_stats['word_count'],
            ),
            'estimated_credits' => self::ESTIMATED_CREDITS,
            'reversible' => true,
            'backup_locations' => array(
                'field_backup'      => '_seo_aeo_content_backup',
                'snapshot_manager'  => self::SNAPSHOT_TAG,
            ),
        ));
    }

    // ────────────────────────────────────────────────────────────────────
    // Step 3 — Apply (validate triple gate, backup, write)
    // ────────────────────────────────────────────────────────────────────

    public static function ajax_apply() {
        self::check_nonce_and_cap();

        // ── Triple gate ───────────────────────────────────────────
        $typed_confirm = isset($_POST['typed_confirm']) ? sanitize_text_field(wp_unslash($_POST['typed_confirm'])) : '';
        $typed_confirm_norm = strtolower(trim($typed_confirm));
        if ($typed_confirm_norm !== 'riscrivi') {
            wp_send_json_error(array(
                'code'    => 'typed_confirm_mismatch',
                'message' => 'Devi digitare esattamente "riscrivi" per confermare.',
            ), 400);
        }

        $checkbox_accepted = isset($_POST['checkbox_accepted'])
            && in_array(strtolower((string) $_POST['checkbox_accepted']), array('1', 'true', 'yes', 'on'), true);
        if (!$checkbox_accepted) {
            wp_send_json_error(array(
                'code'    => 'checkbox_not_accepted',
                'message' => 'Devi accettare il consenso esplicito alla sostituzione.',
            ), 400);
        }

        $request_origin = isset($_POST['request_origin']) ? sanitize_text_field(wp_unslash($_POST['request_origin'])) : '';
        if ($request_origin !== self::ORIGIN_MARKER) {
            wp_send_json_error(array(
                'code'    => 'wrong_origin',
                'message' => 'Origin marker invalido.',
            ), 403);
        }

        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_kses_post applied on next line ($proposed_content).
        $proposed_content_raw = isset($_POST['proposed_content']) ? wp_unslash((string) $_POST['proposed_content']) : '';
        $proposed_content = wp_kses_post($proposed_content_raw);
        if ($post_id <= 0) {
            wp_send_json_error(array('code' => 'bad_post_id'), 400);
        }
        if ($proposed_content === '') {
            wp_send_json_error(array('code' => 'empty_proposed_content'), 400);
        }
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(array('code' => 'post_not_found'), 404);
        }
        $current_content = (string) $post->post_content;

        // ── Pre-write DOUBLE backup ───────────────────────────────
        $backup_results = array();
        if (function_exists('aeo_create_field_backup')) {
            $bk = aeo_create_field_backup($post_id, 'content', $current_content, 'REGENERATE_CONTENT', 'DANGER');
            $backup_results['field_backup'] = is_wp_error($bk) ? $bk->get_error_message() : 'ok';
        } else {
            $backup_results['field_backup'] = 'helper_missing';
        }
        if (class_exists('SEO_AEO_Snapshot_Manager') && method_exists('SEO_AEO_Snapshot_Manager', 'create_snapshot')) {
            try {
                $snap_id = SEO_AEO_Snapshot_Manager::create_snapshot($post_id, self::SNAPSHOT_TAG);
                $backup_results['snapshot_id'] = $snap_id ? $snap_id : 'no_id_returned';
            } catch (Throwable $e) {
                $backup_results['snapshot_id'] = 'error:' . $e->getMessage();
            }
        } else {
            $backup_results['snapshot_id'] = 'snapshot_manager_missing';
        }

        // Refuse to proceed if BOTH backups failed.
        $field_ok    = $backup_results['field_backup'] === 'ok';
        $snapshot_ok = is_string($backup_results['snapshot_id'])
            && $backup_results['snapshot_id'] !== 'no_id_returned'
            && strpos($backup_results['snapshot_id'], 'error:') !== 0
            && $backup_results['snapshot_id'] !== 'snapshot_manager_missing';
        if (!$field_ok && !$snapshot_ok) {
            wp_send_json_error(array(
                'code'           => 'backup_failed',
                'backup_results' => $backup_results,
                'message'        => 'Backup pre-write fallito su entrambi i meccanismi. Operazione abortita.',
            ), 500);
        }

        // ── Write ─────────────────────────────────────────────────
        $updated = wp_update_post(array(
            'ID'           => $post_id,
            'post_content' => $proposed_content,
        ), true);
        if (is_wp_error($updated)) {
            wp_send_json_error(array(
                'code'    => 'write_failed',
                'message' => $updated->get_error_message(),
                'backup_results' => $backup_results,
            ), 500);
        }

        clean_post_cache($post_id);
        $new_post = get_post($post_id);
        $new_stats = self::compute_stats($new_post->post_content);

        wp_send_json_success(array(
            'success'        => true,
            'post_id'        => $post_id,
            'new_byte_size'  => $new_stats['byte_size'],
            'new_word_count' => $new_stats['word_count'],
            'view_url'       => get_permalink($post_id),
            'backup_results' => $backup_results,
            'message'        => 'Contenuto sostituito. Backup salvati. Puoi ripristinare con il pulsante qui sotto.',
        ));
    }

    // ────────────────────────────────────────────────────────────────────
    // Step 4 — Rollback (restore from field-level backup)
    // ────────────────────────────────────────────────────────────────────

    public static function ajax_rollback() {
        self::check_nonce_and_cap();
        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if ($post_id <= 0) {
            wp_send_json_error(array('code' => 'bad_post_id'), 400);
        }
        if (!function_exists('aeo_restore_field_backup')) {
            wp_send_json_error(array('code' => 'helper_missing'), 500);
        }
        $r = aeo_restore_field_backup($post_id, 'content');
        if (is_wp_error($r)) {
            wp_send_json_error(array(
                'code'    => 'restore_failed',
                'message' => $r->get_error_message(),
            ), 500);
        }
        clean_post_cache($post_id);
        $restored = get_post($post_id);
        $stats = self::compute_stats($restored->post_content);
        wp_send_json_success(array(
            'success'           => true,
            'restored_byte_size'=> $stats['byte_size'],
            'restored_word_count'=>$stats['word_count'],
            'message'           => 'Contenuto precedente ripristinato.',
        ));
    }
}

// 3.41.8 - self-init at load time (registers wp_ajax_* hooks immediately,
// since plugin require_once happens before AJAX router fires).
if (class_exists('SEO_AEO_Regenerate_Content')) {
    SEO_AEO_Regenerate_Content::init();
}

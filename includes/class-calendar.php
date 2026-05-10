<?php
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com
 *
 * Content Calendar (3.33.0)
 *
 * UI calendario mese-view per pianificare articoli AI con auto-publish
 * opzionale. Storage: backend Mongo `calendar_slots` (vedi routes/calendar.py).
 *
 * Cron WP `seo_aeo_orchestra_calendar_tick` (ogni ora):
 *   1. fetch backend /calendar/cron/list-due-actions
 *   2. for each `to_generate`: chiama /ai/complete-article + crea draft +
 *      notify backend (status=generated)
 *   3. for each `to_publish`: wp_update_post(post_status=publish) + notify
 *      backend (status=published)
 *
 * Riusa logic AI (api_request /ai/complete-article) — NON duplicato.
 * Brand voice + language injection già fatto da api_request.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Calendar {

    const CRON_HOOK = 'seo_aeo_orchestra_calendar_tick';

    public function __construct() {
        add_action('init', array($this, 'register_cron'));
        add_action(self::CRON_HOOK, array($this, 'process_due_actions'));
    }

    public function register_cron() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            // First fire 90s after registration to avoid race con autopilot.
            wp_schedule_event(time() + 90, 'hourly', self::CRON_HOOK);
        }
    }

    public static function deactivate_cron() {
        $next = wp_next_scheduled(self::CRON_HOOK);
        if ($next) wp_unschedule_event($next, self::CRON_HOOK);
    }

    /**
     * Cron entry-point. Fetcha azioni dovute dal backend e le esegue.
     */
    public function process_due_actions() {
        try {
            $api = isset($GLOBALS['seo_aeo_api']) ? $GLOBALS['seo_aeo_api'] : null;
            if (!$api) {
                seo_aeo_debug_log('[SEO_AEO Calendar] cron skip: api client missing');
                return;
            }
            $license_key = get_option('seo_aeo_orchestra_license_key', '');
            if (empty($license_key)) return;

            $resp = $api->api_request('/calendar/cron/list-due-actions', array());
            if (!is_array($resp) || empty($resp['success'])) {
                seo_aeo_debug_log('[SEO_AEO Calendar] cron list-due failed: ' . wp_json_encode($resp));
                return;
            }

            // 1. Generate due drafts
            $to_generate = isset($resp['to_generate']) && is_array($resp['to_generate']) ? $resp['to_generate'] : array();
            $max_per_run = 3; // limit calls per cron tick — protezione anti-burst crediti
            $count_gen = 0;
            foreach ($to_generate as $slot) {
                if ($count_gen >= $max_per_run) break;
                if ((string) ($slot['license_key'] ?? '') !== $license_key) continue;
                self::generate_article_for_slot($slot);
                $count_gen++;
            }

            // 2. Publish due drafts
            $to_publish = isset($resp['to_publish']) && is_array($resp['to_publish']) ? $resp['to_publish'] : array();
            foreach ($to_publish as $slot) {
                if ((string) ($slot['license_key'] ?? '') !== $license_key) continue;
                self::publish_slot_post($slot);
            }

            // 3. 3.35.0 — Cleanup orphan preview transient (slot eliminati con preview attiva).
            // Best-effort, non bloccante.
            try { self::cleanup_orphan_previews(); } catch (Throwable $e) {}
        } catch (Throwable $e) {
            seo_aeo_debug_log('[SEO_AEO Calendar] process_due_actions FATAL: ' . $e->getMessage());
        }
    }

    /**
     * Genera draft per uno slot. Notifica backend del risultato.
     * @param array $slot {slot_id, topic, keyword, scheduled_at_utc, brand_voice_override, category_id, auto_publish}
     * @return array {success, post_id|error}
     */
    public static function generate_article_for_slot($slot) {
        $api = isset($GLOBALS['seo_aeo_api']) ? $GLOBALS['seo_aeo_api'] : null;
        if (!$api) return array('success' => false, 'error' => 'API client missing');

        $slot_id = isset($slot['slot_id']) ? (string) $slot['slot_id'] : '';
        if (empty($slot_id)) return array('success' => false, 'error' => 'slot_id mancante');

        $topic = isset($slot['topic']) ? (string) $slot['topic'] : '';
        $kw    = isset($slot['keyword']) ? (string) $slot['keyword'] : $topic;
        $cat   = isset($slot['category_id']) ? (int) $slot['category_id'] : 0;
        if (empty($cat)) {
            $cat = (int) get_option('seo_aeo_orchestra_calendar_default_category', 0);
        }
        $sched_iso = isset($slot['scheduled_at_utc']) ? (string) $slot['scheduled_at_utc'] : '';
        $bv_override = isset($slot['brand_voice_override']) ? (string) $slot['brand_voice_override'] : '';

        // Mark backend "generating" (3.33.1: usa patch_slot_state che fa PATCH corretto;
        // prima si usava api_request POST che ritornava 405 Method Not Allowed e il backend
        // non veniva notificato dello stato 'generating').
        try {
            self::patch_slot_state($slot_id, array('status' => 'generating'));
        } catch (Throwable $e) {
            seo_aeo_debug_log('[SEO_AEO Calendar] mark generating failed: ' . $e->getMessage());
        }
        seo_aeo_debug_log('[SEO_AEO Calendar] generate_article_for_slot ENTRY slot_id=' . $slot_id . ' topic=' . substr($topic, 0, 80));

        // Generate via /ai/complete-article (Brand Voice + lingua auto-injected da api_request).
        // Override: se bv_override valorizzato, lo passiamo esplicito (l'api_request rispetta valori già presenti).
        $payload = array(
            'topic'         => $topic,
            'keyword'       => !empty($kw) ? $kw : $topic,
            'length'        => 'medium',
            'include_image' => true,
            'include_meta'  => true,
        );
        if (!empty($bv_override)) {
            $payload['brand_voice_addition'] = $bv_override;
        }

        $resp = $api->api_request('/ai/complete-article', $payload);
        // 3.33.1: diagnostica esplicita per future debug "Genera ora non funziona"
        if (is_array($resp)) {
            seo_aeo_debug_log('[SEO_AEO Calendar] complete-article response keys: ' . implode(',', array_keys($resp)));
        } else {
            seo_aeo_debug_log('[SEO_AEO Calendar] complete-article response NON-ARRAY: ' . substr((string) $resp, 0, 300));
        }
        if (!is_array($resp) || !empty($resp['error']) || empty($resp['content'])) {
            $err = (is_array($resp) && !empty($resp['message'])) ? $resp['message']
                : (is_array($resp) && !empty($resp['detail']) ? $resp['detail'] : 'Backend error (response vuota o formato non valido)');
            seo_aeo_debug_log('[SEO_AEO Calendar] complete-article FAIL slot=' . $slot_id . ' err=' . substr((string) $err, 0, 300) . ' raw=' . substr(wp_json_encode($resp), 0, 300));
            self::patch_slot_state($slot_id, array('status' => 'error', 'error' => mb_substr($err, 0, 500)));
            return array('success' => false, 'error' => $err);
        }
        seo_aeo_debug_log('[SEO_AEO Calendar] complete-article OK slot=' . $slot_id . ' content_bytes=' . strlen((string) $resp['content']) . ' has_image=' . (!empty($resp['image_base64']) ? '1' : '0'));

        // Schedule post_date = scheduled_at_utc (locale conversion fatto da WP)
        $post_date_gmt = self::iso_to_mysql_utc($sched_iso);
        $post_date     = self::iso_to_mysql_local($sched_iso);

        // Layout config (riusa logica autopilot)
        $layout_post_type = get_option('seo_aeo_orchestra_layout_post_type', 'post');
        $layout_template  = get_option('seo_aeo_orchestra_layout_template', 'default');
        $layout_author    = (int) get_option('seo_aeo_orchestra_layout_author', 0);

        $existing_types = function_exists('get_post_types') ? get_post_types(array('public' => true), 'names') : array('post', 'page');
        if (!in_array($layout_post_type, (array) $existing_types, true)) $layout_post_type = 'post';
        if ($layout_author < 1) $layout_author = (int) get_option('seo_aeo_orchestra_default_author_id', 1);
        if ($layout_author < 1) $layout_author = 1;

        $post_data = array(
            'post_title'    => $topic,
            'post_content'  => $resp['content'],
            'post_status'   => 'draft',
            'post_author'   => $layout_author,
            'post_type'     => $layout_post_type,
            'post_date'     => $post_date,
            'post_date_gmt' => $post_date_gmt,
        );
        if ($cat > 0 && $layout_post_type === 'post') {
            $post_data['post_category'] = array($cat);
        }
        $post_id = wp_insert_post($post_data, true);
        if (!empty($layout_template) && $layout_template !== 'default' && !is_wp_error($post_id)) {
            update_post_meta($post_id, '_wp_page_template', $layout_template);
        }
        if (is_wp_error($post_id) || !$post_id) {
            $err = is_wp_error($post_id) ? $post_id->get_error_message() : 'wp_insert_post failed';
            seo_aeo_debug_log('[SEO_AEO Calendar] wp_insert_post FAIL slot=' . $slot_id . ' err=' . $err);
            self::patch_slot_state($slot_id, array('status' => 'error', 'error' => mb_substr($err, 0, 500)));
            return array('success' => false, 'error' => $err);
        }
        seo_aeo_debug_log('[SEO_AEO Calendar] wp_insert_post OK slot=' . $slot_id . ' post_id=' . $post_id);

        // Markers (3.35.8: helper unificato → 6 meta fields invece di 3)
        SEO_AEO_AI_Helpers::mark_ai_generated($post_id, 'calendar', 0);
        update_post_meta($post_id, '_seo_aeo_calendar_slot_id', $slot_id);

        // Featured image + 3.33.2: inline prepend per visibilita' su tutti i theme (incluso Elementor che non renderizza featured)
        if (!empty($resp['image_base64'])) {
            try {
                $alt_for_image = !empty($kw) ? ($topic . ' — ' . $kw) : $topic;
                SEO_AEO_AI_Helpers::attach_featured_image($post_id, $resp['image_base64'], $topic, $alt_for_image, 'calendar');
                $thumb_id = get_post_thumbnail_id($post_id);
                if ($thumb_id) {
                    $img_url = wp_get_attachment_url($thumb_id);
                    if ($img_url) {
                        $inline_block = SEO_AEO_AI_Helpers::build_inline_image_block($thumb_id, $img_url, $topic);
                        wp_update_post(array('ID' => $post_id, 'post_content' => $inline_block . $resp['content']));
                        seo_aeo_debug_log('[SEO_AEO Calendar] inline image prepended slot=' . $slot_id . ' attach=' . $thumb_id);
                    }
                }
            } catch (Throwable $e) { seo_aeo_debug_log('[SEO_AEO Calendar] attach image: ' . $e->getMessage()); }
        }

        // Meta tags via bridge
        if (!empty($resp['meta']) && is_array($resp['meta']) && class_exists('SEO_AEO_Engine_Bridge')) {
            try {
                SEO_AEO_Engine_Bridge::write_meta($post_id, array(
                    'meta_title'       => isset($resp['meta']['title']) ? $resp['meta']['title'] : '',
                    'meta_description' => isset($resp['meta']['description']) ? $resp['meta']['description'] : '',
                    'focus_keyword'    => $kw,
                ));
            } catch (Throwable $e) { seo_aeo_debug_log('[SEO_AEO Calendar] write_meta: ' . $e->getMessage()); }
        }

        // Notify backend
        self::patch_slot_state($slot_id, array(
            'status'      => 'generated',
            'post_id'     => (int) $post_id,
            'generated_at'=> gmdate('Y-m-d\TH:i:s\Z'),
            'error'       => '',
        ));

        return array('success' => true, 'post_id' => $post_id);
    }

    /**
     * Pubblica il post collegato a uno slot. Skippa con status=skipped se utente
     * ha già toccato/cancellato il post.
     */
    public static function publish_slot_post($slot) {
        $slot_id = isset($slot['slot_id']) ? (string) $slot['slot_id'] : '';
        $post_id = isset($slot['post_id']) ? (int) $slot['post_id'] : 0;
        if (!$slot_id || !$post_id) return array('success' => false, 'error' => 'slot/post mancante');

        $post = get_post($post_id);
        if (!$post) {
            self::patch_slot_state($slot_id, array(
                'status' => 'skipped',
                'error'  => 'Post eliminato dall\'utente prima del publish.',
            ));
            return array('success' => false, 'skipped' => true);
        }
        if ($post->post_status !== 'draft') {
            // Utente ha cambiato stato (publish manuale, trash, pending) → rispetta la sua decisione
            $final_status = $post->post_status === 'publish' ? 'published' : 'skipped';
            self::patch_slot_state($slot_id, array(
                'status' => $final_status,
                'published_at' => gmdate('Y-m-d\TH:i:s\Z'),
                'error'  => $final_status === 'skipped' ? ('Post non più draft (status=' . $post->post_status . ')') : '',
            ));
            return array('success' => true, 'skipped' => $final_status === 'skipped');
        }

        $r = wp_update_post(array('ID' => $post_id, 'post_status' => 'publish'), true);
        if (is_wp_error($r) || !$r) {
            $err = is_wp_error($r) ? $r->get_error_message() : 'wp_update_post failed';
            self::patch_slot_state($slot_id, array('status' => 'error', 'error' => mb_substr($err, 0, 500)));
            return array('success' => false, 'error' => $err);
        }
        self::patch_slot_state($slot_id, array(
            'status' => 'published',
            'published_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'error'  => '',
        ));
        return array('success' => true, 'post_id' => $post_id);
    }

    /**
     * Notifica backend del cambio stato slot. Chiamata blackbox via api_request
     * con endpoint speciale `__patch_slot__` interno → in api_request usiamo POST,
     * ma il backend espone PATCH /calendar/slot/{id}. Per evitare di toccare
     * api_request, abbiamo aggiunto un endpoint POST `/calendar/slot-patch/{slot_id}`
     * lato backend? NO — invece usiamo wp_remote_request con PATCH diretto.
     */
    private static function patch_slot_state($slot_id, $fields) {
        $license_key = get_option('seo_aeo_orchestra_license_key', '');
        $api_url = defined('SEO_AEO_API_URL') ? SEO_AEO_API_URL : 'https://aeo-orchestra.com';
        $url = rtrim($api_url, '/') . '/api/calendar/slot/' . rawurlencode((string) $slot_id);
        $body = is_array($fields) ? $fields : array();
        $body['license_key'] = $license_key;
        $resp = wp_remote_request($url, array(
            'method'  => 'PATCH',
            'timeout' => 20,
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => wp_json_encode($body),
        ));
        if (is_wp_error($resp)) {
            seo_aeo_debug_log('[SEO_AEO Calendar] patch_slot_state error: ' . $resp->get_error_message());
            return false;
        }
        return true;
    }

    /**
     * ISO 8601 UTC string → 'Y-m-d H:i:s' in UTC (per post_date_gmt).
     */
    private static function iso_to_mysql_utc($iso) {
        if (empty($iso)) return current_time('mysql', true);
        try {
            $dt = new DateTime($iso, new DateTimeZone('UTC'));
            return $dt->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            return current_time('mysql', true);
        }
    }

    /**
     * ISO 8601 UTC string → 'Y-m-d H:i:s' in WP timezone (per post_date display).
     */
    private static function iso_to_mysql_local($iso) {
        if (empty($iso)) return current_time('mysql');
        try {
            $dt = new DateTime($iso, new DateTimeZone('UTC'));
            $tz = wp_timezone();
            $dt->setTimezone($tz);
            return $dt->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            return current_time('mysql');
        }
    }

    /**
     * @deprecated 3.35.8 — Logica spostata in SEO_AEO_AI_Helpers::attach_featured_image().
     * Wrapper kept for backward compat se altro codice esterno chiama self::attach_featured_image.
     */
    private static function attach_featured_image($post_id, $base64, $title, $alt_text = '') {
        return SEO_AEO_AI_Helpers::attach_featured_image($post_id, $base64, $title, $alt_text, 'calendar');
    }

    /**
     * Helper: triggerable manuale via AJAX (admin button "Genera ora").
     * Esegue subito generation per uno slot specifico (saltando il check del cron).
     */
    public static function generate_now_by_slot_id($slot_id) {
        seo_aeo_debug_log('[SEO_AEO Calendar] generate_now_by_slot_id ENTRY slot_id=' . $slot_id);
        $api = isset($GLOBALS['seo_aeo_api']) ? $GLOBALS['seo_aeo_api'] : null;
        if (!$api) {
            seo_aeo_debug_log('[SEO_AEO Calendar] generate_now: API client missing');
            return array('success' => false, 'error' => 'API client missing');
        }
        $license_key = get_option('seo_aeo_orchestra_license_key', '');
        if (empty($license_key)) {
            seo_aeo_debug_log('[SEO_AEO Calendar] generate_now: license_key empty');
            return array('success' => false, 'error' => 'License key non configurata');
        }
        $api_url = defined('SEO_AEO_API_URL') ? SEO_AEO_API_URL : 'https://aeo-orchestra.com';
        // Window ampia: -90 giorni per slot vecchi non ancora generati, +365 futuro
        $today = gmdate('Y-m-d', time() - 90 * 86400);
        $year_ahead = gmdate('Y-m-d', time() + 365 * 86400);
        $url = rtrim($api_url, '/') . '/api/calendar/slots?license_key=' . rawurlencode($license_key)
             . '&from=' . $today . '&to=' . $year_ahead;
        $resp = wp_remote_get($url, array('timeout' => 20));
        if (is_wp_error($resp)) {
            seo_aeo_debug_log('[SEO_AEO Calendar] generate_now: list slots wp_error=' . $resp->get_error_message());
            return array('success' => false, 'error' => $resp->get_error_message());
        }
        $body = json_decode(wp_remote_retrieve_body($resp), true);
        if (!is_array($body) || empty($body['success'])) {
            $detail = is_array($body) && !empty($body['detail']) ? $body['detail'] : 'List slots failed';
            seo_aeo_debug_log('[SEO_AEO Calendar] generate_now: list slots fail detail=' . $detail);
            return array('success' => false, 'error' => $detail);
        }
        $slot = null;
        $count = 0;
        foreach ((array) ($body['slots'] ?? array()) as $s) {
            $count++;
            // Match su entrambi 'slot_id' e 'id' per compatibilità futura
            $candidate_id = (string) ($s['slot_id'] ?? $s['id'] ?? '');
            if ($candidate_id === (string) $slot_id) { $slot = $s; break; }
        }
        seo_aeo_debug_log('[SEO_AEO Calendar] generate_now: scanned ' . $count . ' slots, found=' . ($slot ? 'yes' : 'no'));
        if (!$slot) {
            return array('success' => false, 'error' => 'Slot non trovato (id=' . substr($slot_id, 0, 12) . '...). Aggiorna la pagina e riprova.');
        }
        // Bypass se status non planned (es. gia generato): logga ma non ri-genera
        $cur_status = (string) ($slot['status'] ?? 'planned');
        if ($cur_status !== 'planned') {
            seo_aeo_debug_log('[SEO_AEO Calendar] generate_now: slot status=' . $cur_status . ' (skip re-gen, return current state)');
            return array('success' => false, 'error' => 'Lo slot e in stato "' . $cur_status . '", non rigenero. Elimina e ricrea se vuoi rigenerare.');
        }
        return self::generate_article_for_slot($slot);
    }

    public static function publish_now_by_slot_id($slot_id, $post_id) {
        return self::publish_slot_post(array('slot_id' => $slot_id, 'post_id' => (int) $post_id));
    }

    // ============================================================
    // 3.35.0 — Calendar Preview & Refund (preview-first flow)
    // ============================================================

    const PREVIEW_TRANSIENT_PREFIX = 'seo_aeo_calendar_preview_';
    const PREVIEW_TTL_SECONDS = 1800; // 30 min

    /**
     * Genera content+image+meta via backend, MA NON crea post WP.
     * Salva in transient + segna slot status="preview" sul backend.
     *
     * @return array {success, generation_id, preview, refundable_until} | {success:false, error}
     */
    public static function generate_preview_for_slot($slot_id) {
        seo_aeo_debug_log('[SEO_AEO Calendar] generate_preview_for_slot ENTRY slot_id=' . $slot_id);
        $api = isset($GLOBALS['seo_aeo_api']) ? $GLOBALS['seo_aeo_api'] : null;
        if (!$api) return array('success' => false, 'error' => 'API client missing');
        $license_key = get_option('seo_aeo_orchestra_license_key', '');
        if (empty($license_key)) return array('success' => false, 'error' => 'License key non configurata');

        // 1) Idempotenza: se transient esiste e generation_id ancora valido, ritorna quello
        $existing = get_transient(self::PREVIEW_TRANSIENT_PREFIX . $slot_id);
        if (is_array($existing) && !empty($existing['generation_id']) && !empty($existing['preview'])) {
            seo_aeo_debug_log('[SEO_AEO Calendar] preview ALREADY EXISTS slot_id=' . $slot_id . ' gen=' . $existing['generation_id']);
            return array(
                'success'          => true,
                'cached'           => true,
                'generation_id'    => $existing['generation_id'],
                'preview'          => $existing['preview'],
                'refundable_until' => isset($existing['refundable_until']) ? $existing['refundable_until'] : '',
            );
        }

        // 2) Risolvi slot dal backend
        $api_url = defined('SEO_AEO_API_URL') ? SEO_AEO_API_URL : 'https://aeo-orchestra.com';
        $today = gmdate('Y-m-d', time() - 90 * 86400);
        $year_ahead = gmdate('Y-m-d', time() + 365 * 86400);
        $url = rtrim($api_url, '/') . '/api/calendar/slots?license_key=' . rawurlencode($license_key)
             . '&from=' . $today . '&to=' . $year_ahead;
        $resp = wp_remote_get($url, array('timeout' => 20));
        if (is_wp_error($resp)) return array('success' => false, 'error' => $resp->get_error_message());
        $body = json_decode(wp_remote_retrieve_body($resp), true);
        if (!is_array($body) || empty($body['success'])) {
            return array('success' => false, 'error' => 'List slots failed');
        }
        $slot = null;
        foreach ((array) ($body['slots'] ?? array()) as $s) {
            $cid = (string) ($s['slot_id'] ?? $s['id'] ?? '');
            if ($cid === (string) $slot_id) { $slot = $s; break; }
        }
        if (!$slot) return array('success' => false, 'error' => 'Slot non trovato');

        $cur_status = (string) ($slot['status'] ?? 'planned');
        if (!in_array($cur_status, array('planned', 'preview', 'error'), true)) {
            return array('success' => false, 'error' => 'Lo slot e in stato "' . $cur_status . '", non rigenero. Elimina e ricrea per forzare.');
        }

        $topic = (string) ($slot['topic'] ?? '');
        $kw    = (string) ($slot['keyword'] ?? $topic);
        if ($kw === '') $kw = $topic;
        $bv_override = (string) ($slot['brand_voice_override'] ?? '');

        // 3) Mark generating sul backend
        try { self::patch_slot_state($slot_id, array('status' => 'generating')); } catch (Throwable $e) {}

        // 4) Chiama backend generation
        $payload = array(
            'topic'         => $topic,
            'keyword'       => $kw,
            'length'        => 'medium',
            'include_image' => true,
            'include_meta'  => true,
        );
        if (!empty($bv_override)) $payload['brand_voice_addition'] = $bv_override;

        $resp = $api->api_request('/ai/complete-article', $payload);
        if (!is_array($resp) || !empty($resp['error']) || empty($resp['content'])) {
            $err = is_array($resp) && !empty($resp['message']) ? $resp['message']
                : (is_array($resp) && !empty($resp['detail']) ? $resp['detail'] : 'Errore generazione AI');
            self::patch_slot_state($slot_id, array('status' => 'error', 'error' => mb_substr((string) $err, 0, 500)));
            return array('success' => false, 'error' => $err);
        }
        $generation_id    = (string) ($resp['generation_id'] ?? '');
        $refundable_until = (string) ($resp['refundable_until'] ?? '');
        $content          = (string) $resp['content'];
        $image_b64        = isset($resp['image_base64']) ? (string) $resp['image_base64'] : '';
        $meta             = isset($resp['meta']) && is_array($resp['meta']) ? $resp['meta'] : array();

        $word_count = str_word_count(wp_strip_all_tags($content));
        $preview = array(
            'topic'            => $topic,
            'keyword'          => $kw,
            'content_html'     => $content,
            'image_base64'     => $image_b64,
            'meta_title'       => isset($meta['title']) ? (string) $meta['title'] : '',
            'meta_description' => isset($meta['description']) ? (string) $meta['description'] : '',
            'focus_keyword'    => $kw,
            'word_count'       => $word_count,
            'has_image'        => !empty($image_b64),
        );

        $payload_save = array(
            'generation_id'    => $generation_id,
            'refundable_until' => $refundable_until,
            'preview'          => $preview,
            'slot_id'          => $slot_id,
            'generated_at'     => gmdate('c'),
            'topic'            => $topic,
            'keyword'          => $kw,
            'brand_voice'      => $bv_override,
        );
        set_transient(self::PREVIEW_TRANSIENT_PREFIX . $slot_id, $payload_save, self::PREVIEW_TTL_SECONDS);

        // 5) Mark slot status=preview (nuovo stato)
        try {
            self::patch_slot_state($slot_id, array(
                'status'        => 'preview',
                'error'         => '',
            ));
        } catch (Throwable $e) {
            seo_aeo_debug_log('[SEO_AEO Calendar] preview patch state failed: ' . $e->getMessage());
        }

        return array(
            'success'          => true,
            'generation_id'    => $generation_id,
            'refundable_until' => $refundable_until,
            'preview'          => $preview,
        );
    }

    /**
     * Commit della preview: legge transient → wp_insert_post → meta → notifica backend.
     * Idempotente: skip se transient gia consumato (status=generated).
     */
    public static function commit_preview_for_slot($slot_id) {
        $payload = get_transient(self::PREVIEW_TRANSIENT_PREFIX . $slot_id);
        if (!is_array($payload) || empty($payload['preview'])) {
            return array('success' => false, 'error' => 'Anteprima scaduta o non trovata. Genera di nuovo.');
        }
        $preview      = $payload['preview'];
        $topic        = (string) ($payload['topic'] ?? ($preview['topic'] ?? ''));
        $kw           = (string) ($payload['keyword'] ?? ($preview['keyword'] ?? $topic));
        $content      = (string) ($preview['content_html'] ?? '');
        $image_b64    = (string) ($preview['image_base64'] ?? '');
        $meta_title   = (string) ($preview['meta_title'] ?? '');
        $meta_desc    = (string) ($preview['meta_description'] ?? '');
        if ($content === '') return array('success' => false, 'error' => 'Anteprima senza contenuto');

        // Risolvi slot per scheduled_at_utc + category
        $api_url = defined('SEO_AEO_API_URL') ? SEO_AEO_API_URL : 'https://aeo-orchestra.com';
        $license_key = get_option('seo_aeo_orchestra_license_key', '');
        $today = gmdate('Y-m-d', time() - 90 * 86400);
        $year_ahead = gmdate('Y-m-d', time() + 365 * 86400);
        $url = rtrim($api_url, '/') . '/api/calendar/slots?license_key=' . rawurlencode($license_key)
             . '&from=' . $today . '&to=' . $year_ahead;
        $resp = wp_remote_get($url, array('timeout' => 20));
        $sched_iso = '';
        $cat = 0;
        if (!is_wp_error($resp)) {
            $body = json_decode(wp_remote_retrieve_body($resp), true);
            foreach ((array) ($body['slots'] ?? array()) as $s) {
                if ((string) ($s['slot_id'] ?? '') === (string) $slot_id) {
                    $sched_iso = (string) ($s['scheduled_at_utc'] ?? '');
                    $cat = (int) ($s['category_id'] ?? 0);
                    // Idempotency: gia generato → skip wp_insert_post
                    $existing_status = (string) ($s['status'] ?? '');
                    $existing_post_id = (int) ($s['post_id'] ?? 0);
                    if ($existing_status === 'generated' && $existing_post_id > 0 && get_post($existing_post_id)) {
                        delete_transient(self::PREVIEW_TRANSIENT_PREFIX . $slot_id);
                        return array('success' => true, 'post_id' => $existing_post_id, 'idempotent' => true);
                    }
                    break;
                }
            }
        }
        if ($cat === 0) $cat = (int) get_option('seo_aeo_orchestra_calendar_default_category', 0);

        $post_date_gmt = self::iso_to_mysql_utc($sched_iso);
        $post_date     = self::iso_to_mysql_local($sched_iso);

        $layout_post_type = get_option('seo_aeo_orchestra_layout_post_type', 'post');
        $layout_template  = get_option('seo_aeo_orchestra_layout_template', 'default');
        $layout_author    = (int) get_option('seo_aeo_orchestra_layout_author', 0);
        $existing_types = function_exists('get_post_types') ? get_post_types(array('public' => true), 'names') : array('post', 'page');
        if (!in_array($layout_post_type, (array) $existing_types, true)) $layout_post_type = 'post';
        if ($layout_author < 1) $layout_author = (int) get_option('seo_aeo_orchestra_default_author_id', 1);
        if ($layout_author < 1) $layout_author = 1;

        $post_data = array(
            'post_title'    => $topic,
            'post_content'  => $content,
            'post_status'   => 'draft',
            'post_author'   => $layout_author,
            'post_type'     => $layout_post_type,
            'post_date'     => $post_date,
            'post_date_gmt' => $post_date_gmt,
        );
        if ($cat > 0 && $layout_post_type === 'post') $post_data['post_category'] = array($cat);
        $post_id = wp_insert_post($post_data, true);
        if (is_wp_error($post_id) || !$post_id) {
            $err = is_wp_error($post_id) ? $post_id->get_error_message() : 'wp_insert_post failed';
            return array('success' => false, 'error' => $err);
        }
        if (!empty($layout_template) && $layout_template !== 'default') {
            update_post_meta($post_id, '_wp_page_template', $layout_template);
        }
        // 3.35.8: helper unificato → 6 meta fields invece di 3
        SEO_AEO_AI_Helpers::mark_ai_generated($post_id, 'calendar_preview', 0);
        update_post_meta($post_id, '_seo_aeo_calendar_slot_id', $slot_id);
        if (!empty($payload['generation_id'])) {
            update_post_meta($post_id, '_seo_aeo_calendar_generation_id', (string) $payload['generation_id']);
        }

        // Featured image + inline cover
        if (!empty($image_b64)) {
            try {
                $alt_for_image = !empty($kw) ? ($topic . ' — ' . $kw) : $topic;
                SEO_AEO_AI_Helpers::attach_featured_image($post_id, $image_b64, $topic, $alt_for_image, 'calendar_preview');
                $thumb_id = get_post_thumbnail_id($post_id);
                if ($thumb_id) {
                    $img_url = wp_get_attachment_url($thumb_id);
                    if ($img_url) {
                        $inline_block = SEO_AEO_AI_Helpers::build_inline_image_block($thumb_id, $img_url, $topic);
                        wp_update_post(array('ID' => $post_id, 'post_content' => $inline_block . $content));
                    }
                }
            } catch (Throwable $e) {}
        }

        // Meta tags via bridge
        if (class_exists('SEO_AEO_Engine_Bridge') && ($meta_title !== '' || $meta_desc !== '')) {
            try {
                SEO_AEO_Engine_Bridge::write_meta($post_id, array(
                    'meta_title'       => $meta_title,
                    'meta_description' => $meta_desc,
                    'focus_keyword'    => $kw,
                ));
            } catch (Throwable $e) {}
        }

        // Notify backend
        self::patch_slot_state($slot_id, array(
            'status'       => 'generated',
            'post_id'      => (int) $post_id,
            'generated_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'error'        => '',
        ));

        delete_transient(self::PREVIEW_TRANSIENT_PREFIX . $slot_id);
        return array('success' => true, 'post_id' => (int) $post_id);
    }

    /**
     * Carica preview salvata (se esiste). Usato dal bottone "Riprendi anteprima".
     */
    public static function load_preview_for_slot($slot_id) {
        $payload = get_transient(self::PREVIEW_TRANSIENT_PREFIX . $slot_id);
        if (!is_array($payload) || empty($payload['preview'])) {
            return array('success' => false, 'error' => 'Anteprima non disponibile (scaduta o mai generata).');
        }
        return array(
            'success'          => true,
            'generation_id'    => (string) ($payload['generation_id'] ?? ''),
            'refundable_until' => (string) ($payload['refundable_until'] ?? ''),
            'preview'          => $payload['preview'],
        );
    }

    /**
     * Refund: chiama backend /ai/refund-generation. Se OK, scarta transient + reset slot a "planned".
     */
    public static function refund_preview_for_slot($slot_id, $generation_id, $reason = '') {
        $api = isset($GLOBALS['seo_aeo_api']) ? $GLOBALS['seo_aeo_api'] : null;
        if (!$api) return array('success' => false, 'error' => 'API client missing');

        $payload = array(
            'generation_id' => $generation_id,
            'reason'        => mb_substr((string) $reason, 0, 200),
        );
        $resp = $api->api_request('/ai/refund-generation', $payload);
        if (!is_array($resp)) {
            return array('success' => false, 'error' => 'Backend non raggiungibile');
        }
        // 410/409/429 etc. ritornano detail/message. Non success.
        if (empty($resp['success'])) {
            $err = !empty($resp['message']) ? $resp['message']
                : (!empty($resp['detail']) ? $resp['detail']
                : (!empty($resp['error']) ? $resp['error'] : 'Errore rimborso'));
            return array('success' => false, 'error' => $err);
        }

        // Refund OK → scarta transient + reset slot a "planned"
        delete_transient(self::PREVIEW_TRANSIENT_PREFIX . $slot_id);
        try {
            self::patch_slot_state($slot_id, array(
                'status'  => 'planned',
                'post_id' => 0,
                'error'   => '',
            ));
        } catch (Throwable $e) {}

        return array(
            'success'                  => true,
            'refunded'                 => (int) ($resp['refunded'] ?? 0),
            'refunds_today'            => (int) ($resp['refunds_today'] ?? 0),
            'refunds_remaining_today'  => (int) ($resp['refunds_remaining_today'] ?? 0),
        );
    }

    /**
     * Cleanup transient orfani: chiamato da cron. Itera tutti gli slot status=preview
     * del backend e rimuove transient di slot che non esistono piu.
     */
    public static function cleanup_orphan_previews() {
        global $wpdb;
        $like = $wpdb->esc_like('_transient_' . self::PREVIEW_TRANSIENT_PREFIX) . '%';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
            $like
        ));
        if (!is_array($rows)) return 0;
        $api_url = defined('SEO_AEO_API_URL') ? SEO_AEO_API_URL : 'https://aeo-orchestra.com';
        $license_key = get_option('seo_aeo_orchestra_license_key', '');
        if (empty($license_key)) return 0;
        $today = gmdate('Y-m-d', time() - 90 * 86400);
        $year_ahead = gmdate('Y-m-d', time() + 365 * 86400);
        $url = rtrim($api_url, '/') . '/api/calendar/slots?license_key=' . rawurlencode($license_key)
             . '&from=' . $today . '&to=' . $year_ahead;
        $resp = wp_remote_get($url, array('timeout' => 20));
        if (is_wp_error($resp)) return 0;
        $body = json_decode(wp_remote_retrieve_body($resp), true);
        $valid_ids = array();
        foreach ((array) ($body['slots'] ?? array()) as $s) {
            $valid_ids[(string) ($s['slot_id'] ?? '')] = true;
        }
        $removed = 0;
        foreach ($rows as $opt) {
            $slot_id = substr($opt, strlen('_transient_' . self::PREVIEW_TRANSIENT_PREFIX));
            if (!isset($valid_ids[$slot_id])) {
                delete_transient(self::PREVIEW_TRANSIENT_PREFIX . $slot_id);
                $removed++;
            }
        }
        return $removed;
    }
}

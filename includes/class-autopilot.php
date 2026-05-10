<?php
// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com
 *
 * Auto-Pilot Scheduler (3.24.0 — ultimo step roadmap "Soro Killer")
 *
 * Cron WP che, dato un job configurato (nome + set keyword snapshot + frequenza
 * + opzioni publish/draft + length + categoria + autore), pesca la prossima
 * keyword non ancora usata, chiama /ai/complete-article (con Brand Voice auto-
 * applicato dal client API), crea post WordPress (draft default), allega
 * featured image se inclusa, scrive meta tags via SEO_AEO_Engine_Bridge.
 *
 * Tabelle:
 *  - {prefix}seo_aeo_autopilot_jobs
 *  - {prefix}seo_aeo_autopilot_log
 *
 * Solo per piani Premium (la verifica è lato backend complete-article).
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Autopilot {

    const TABLE_JOBS    = 'seo_aeo_autopilot_jobs';
    const TABLE_LOG     = 'seo_aeo_autopilot_log';
    const DB_VERSION    = '1.1';
    const DB_VERSION_OPT = 'seo_aeo_autopilot_db_version';
    const CRON_HOOK     = 'seo_aeo_orchestra_autopilot_run';

    public function __construct() {
        add_action('init', array(__CLASS__, 'maybe_create_tables'));
        add_action('init', array($this, 'register_cron'));
        add_action(self::CRON_HOOK, array($this, 'run_due_jobs'));
    }

    public static function maybe_create_tables() {
        $stored = get_option(self::DB_VERSION_OPT, '');
        if ($stored === self::DB_VERSION) return;

        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();
        $tbl_jobs = $wpdb->prefix . self::TABLE_JOBS;
        $tbl_log  = $wpdb->prefix . self::TABLE_LOG;

        $sql_jobs = "CREATE TABLE $tbl_jobs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(160) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            frequency_days INT UNSIGNED NOT NULL DEFAULT 7,
            post_status VARCHAR(20) NOT NULL DEFAULT 'draft',
            include_image TINYINT(1) NOT NULL DEFAULT 1,
            length VARCHAR(20) NOT NULL DEFAULT 'medium',
            post_category_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            post_author_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            keywords_json LONGTEXT NOT NULL,
            used_keywords_json LONGTEXT NOT NULL,
            layout_override_json LONGTEXT NULL,
            generated_count INT UNSIGNED NOT NULL DEFAULT 0,
            next_run DATETIME NULL,
            last_run DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_status_next (status, next_run)
        ) $charset_collate;";

        $sql_log = "CREATE TABLE $tbl_log (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            job_id BIGINT UNSIGNED NOT NULL,
            keyword VARCHAR(255) NOT NULL,
            post_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL,
            error_message TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_job_id (job_id),
            KEY idx_created_at (created_at)
        ) $charset_collate;";

        dbDelta($sql_jobs);
        dbDelta($sql_log);
        update_option(self::DB_VERSION_OPT, self::DB_VERSION);
    }

    public function register_cron() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 60, 'hourly', self::CRON_HOOK);
        }
    }

    public static function deactivate_cron() {
        $next = wp_next_scheduled(self::CRON_HOOK);
        if ($next) wp_unschedule_event($next, self::CRON_HOOK);
    }

    /**
     * Crea un nuovo job. $data:
     *  name, keywords (array), frequency_days, post_status, include_image,
     *  length, post_category_id, post_author_id
     */
    public static function create_job($data) {
        global $wpdb;
        self::maybe_create_tables();
        $tbl = $wpdb->prefix . self::TABLE_JOBS;
        $now = current_time('mysql', true);

        $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
        if (empty($name)) $name = 'Auto-Pilot job ' . gmdate('Y-m-d');
        $keywords = isset($data['keywords']) && is_array($data['keywords']) ? array_values($data['keywords']) : array();
        if (empty($keywords)) return array('success' => false, 'error' => 'Nessuna keyword nel job. Seleziona almeno 1 keyword da un set.');

        $freq = isset($data['frequency_days']) ? intval($data['frequency_days']) : 7;
        if ($freq < 1) $freq = 1;
        if ($freq > 90) $freq = 90;

        $post_status = isset($data['post_status']) ? sanitize_text_field($data['post_status']) : 'draft';
        if (!in_array($post_status, array('draft', 'publish'), true)) $post_status = 'draft';

        $include_image = !empty($data['include_image']) ? 1 : 0;
        $length = isset($data['length']) ? sanitize_text_field($data['length']) : 'medium';
        if (!in_array($length, array('short', 'medium', 'long'), true)) $length = 'medium';

        $cat = isset($data['post_category_id']) ? intval($data['post_category_id']) : 0;
        $author = isset($data['post_author_id']) ? intval($data['post_author_id']) : get_current_user_id();

        // 3.31.5: per-job layout override (opzionale)
        $layout_override_json = null;
        if (!empty($data['layout_override']) && is_array($data['layout_override'])) {
            $ov = $data['layout_override'];
            $payload = array(
                'enabled'   => !empty($ov['enabled']) ? 1 : 0,
                'post_type' => isset($ov['post_type']) ? sanitize_text_field((string) $ov['post_type']) : '',
                'template'  => isset($ov['template'])  ? sanitize_text_field((string) $ov['template'])  : '',
                'category'  => isset($ov['category'])  ? intval($ov['category']) : 0,
                'author'    => isset($ov['author'])    ? intval($ov['author']) : 0,
            );
            $layout_override_json = wp_json_encode($payload);
        }

        $next_run = gmdate('Y-m-d H:i:s', time() + 120); // first run in 2 minutes (after admin click)

        $insert = $wpdb->insert(
            $tbl,
            array(
                'name'                => $name,
                'status'              => 'active',
                'frequency_days'      => $freq,
                'post_status'         => $post_status,
                'include_image'       => $include_image,
                'length'              => $length,
                'post_category_id'    => $cat,
                'post_author_id'      => $author,
                'keywords_json'       => wp_json_encode($keywords),
                'used_keywords_json'  => '[]',
                'layout_override_json'=> $layout_override_json,
                'generated_count'     => 0,
                'next_run'            => $next_run,
                'last_run'            => null,
                'created_at'          => $now,
                'updated_at'          => $now,
            ),
            array('%s', '%s', '%d', '%s', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
        );
        if ($insert === false) return array('success' => false, 'error' => $wpdb->last_error);
        return array('success' => true, 'id' => (int) $wpdb->insert_id);
    }

    public static function list_jobs() {
        global $wpdb;
        self::maybe_create_tables();
        $tbl = $wpdb->prefix . self::TABLE_JOBS;
        $rows = $wpdb->get_results("SELECT * FROM $tbl ORDER BY status ASC, next_run ASC LIMIT 100", ARRAY_A);
        if (!is_array($rows)) return array();
        foreach ($rows as &$r) {
            $kws = json_decode($r['keywords_json'], true);
            $used = json_decode($r['used_keywords_json'], true);
            $r['keywords_total'] = is_array($kws) ? count($kws) : 0;
            $r['keywords_used']  = is_array($used) ? count($used) : 0;
            $r['keywords_remaining'] = max(0, $r['keywords_total'] - $r['keywords_used']);
        }
        return $rows;
    }

    public static function get_job($id) {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE_JOBS;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $tbl WHERE id = %d", (int) $id), ARRAY_A);
    }

    public static function update_status($id, $status) {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE_JOBS;
        if (!in_array($status, array('active', 'paused'), true)) return array('success' => false);
        $r = $wpdb->update($tbl, array('status' => $status, 'updated_at' => current_time('mysql', true)), array('id' => (int) $id), array('%s', '%s'), array('%d'));
        return array('success' => $r !== false);
    }

    public static function delete_job($id) {
        global $wpdb;
        $tbl_jobs = $wpdb->prefix . self::TABLE_JOBS;
        $tbl_log  = $wpdb->prefix . self::TABLE_LOG;
        $wpdb->delete($tbl_log, array('job_id' => (int) $id), array('%d'));
        $r = $wpdb->delete($tbl_jobs, array('id' => (int) $id), array('%d'));
        return array('success' => $r !== false);
    }

    public static function get_log($job_id = 0, $limit = 30) {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE_LOG;
        $limit = max(1, min(100, (int) $limit));
        if ($job_id > 0) {
            return $wpdb->get_results($wpdb->prepare("SELECT * FROM $tbl WHERE job_id = %d ORDER BY id DESC LIMIT %d", (int) $job_id, $limit), ARRAY_A);
        }
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $tbl ORDER BY id DESC LIMIT %d", $limit), ARRAY_A);
    }

    /**
     * Cron entry-point. Processa tutti i job attivi con next_run <= NOW().
     */
    public function run_due_jobs() {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE_JOBS;
        $now = current_time('mysql', true);
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tbl WHERE status = 'active' AND (next_run IS NULL OR next_run <= %s) LIMIT 5",
            $now
        ), ARRAY_A);
        if (!is_array($rows)) return;
        foreach ($rows as $job) {
            try {
                self::process_one_keyword((int) $job['id']);
            } catch (Throwable $e) {
                orch_debug_log('SEO_AEO Autopilot run_due_jobs error job=' . $job['id'] . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * Process una keyword del job: pick → generate → publish → log → schedule next.
     * Ritorna array con {success, post_id|error}.
     */
    public static function process_one_keyword($job_id) {
        global $wpdb;
        $tbl_jobs = $wpdb->prefix . self::TABLE_JOBS;
        $job = self::get_job($job_id);
        if (!$job) return array('success' => false, 'error' => 'Job non trovato');

        $keywords = json_decode($job['keywords_json'], true);
        $used     = json_decode($job['used_keywords_json'], true);
        if (!is_array($keywords)) $keywords = array();
        if (!is_array($used)) $used = array();

        // Pick next not-yet-used keyword (object {keyword, topic})
        $next = null;
        foreach ($keywords as $kw) {
            $kwText = is_array($kw) ? (isset($kw['keyword']) ? $kw['keyword'] : '') : (string) $kw;
            if (empty($kwText)) continue;
            if (in_array($kwText, $used, true)) continue;
            $next = is_array($kw) ? $kw : array('keyword' => $kwText, 'topic' => $kwText);
            break;
        }
        if (!$next) {
            // Tutte le keyword già usate: pausa il job e logga
            $wpdb->update($tbl_jobs, array(
                'status' => 'paused',
                'next_run' => null,
                'updated_at' => current_time('mysql', true),
            ), array('id' => $job_id), array('%s', '%s', '%s'), array('%d'));
            self::log_event($job_id, '(set esaurito)', 0, 'completed', 'Tutte le keyword del set sono state generate. Job pausato.');
            return array('success' => true, 'completed' => true);
        }

        $kwText = $next['keyword'];
        $topic = !empty($next['topic']) ? $next['topic'] : $kwText;

        // Call backend /ai/complete-article (Brand Voice auto-injected da api_request)
        $api_class = 'SEO_AEO_API_Client';
        if (!class_exists($api_class)) {
            self::log_event($job_id, $kwText, 0, 'error', 'API Client class missing');
            return array('success' => false, 'error' => 'API client missing');
        }
        $api = new $api_class();
        $payload = array(
            'topic'         => $topic,
            'keyword'       => $kwText,
            'length'        => $job['length'],
            'include_image' => (int) $job['include_image'] === 1,
            'include_meta'  => true,
        );
        $resp = $api->api_request('/ai/complete-article', $payload);

        if (!is_array($resp) || !empty($resp['error']) || empty($resp['content'])) {
            $err = (is_array($resp) && !empty($resp['message'])) ? $resp['message'] : (is_array($resp) && !empty($resp['detail']) ? $resp['detail'] : 'Backend error');
            self::log_event($job_id, $kwText, 0, 'error', $err);
            // Schedule retry: prossimo run in 1 ora (non sprechiamo crediti, non blocchiamo il job)
            $wpdb->update($tbl_jobs, array(
                'last_run'   => current_time('mysql', true),
                'next_run'   => gmdate('Y-m-d H:i:s', time() + 3600),
                'updated_at' => current_time('mysql', true),
            ), array('id' => $job_id), array('%s', '%s', '%s'), array('%d'));
            return array('success' => false, 'error' => $err);
        }

        // 3.31.5: Layout config (global default + per-job override). Risolve quale
        // post_type/template/categoria/autore usare per il post creato dall'AI.
        $layout = self::resolve_layout_for_job($job);

        // Create WP post (rispetta layout configurato)
        $post_data = array(
            'post_title'   => $topic,
            'post_content' => $resp['content'],
            'post_status'  => $job['post_status'],
            'post_author'  => (int) $layout['author'] ?: ((int) $job['post_author_id'] ?: get_current_user_id()),
            'post_type'    => $layout['post_type'],
        );
        // Categoria: usa override layout se >0, altrimenti la categoria del job (legacy).
        $cat_to_use = (int) $layout['category'] > 0 ? (int) $layout['category'] : (int) $job['post_category_id'];
        if ($cat_to_use > 0 && $layout['post_type'] === 'post') {
            $post_data['post_category'] = array($cat_to_use);
        }
        $post_id = wp_insert_post($post_data, true);
        // Apply page template (must be done AFTER insert, requires meta)
        if (!is_wp_error($post_id) && $post_id && !empty($layout['template']) && $layout['template'] !== 'default') {
            update_post_meta($post_id, '_wp_page_template', $layout['template']);
        }
        if (is_wp_error($post_id) || !$post_id) {
            $err = is_wp_error($post_id) ? $post_id->get_error_message() : 'wp_insert_post failed';
            self::log_event($job_id, $kwText, 0, 'error', $err);
            return array('success' => false, 'error' => $err);
        }

        // 3.32.0: marker AI-generated per KPI Analytics. Lega il post al job.
        // 3.35.8: helper unificato (logica identica a quella precedente, 6 fields)
        SEO_AEO_AI_Helpers::mark_ai_generated($post_id, 'autopilot', (int) $job_id);

        // Featured image from base64
        if (!empty($resp['image_base64'])) {
            try {
                $alt_for_image = !empty($kwText) ? ($topic . ' — ' . $kwText) : $topic;
                SEO_AEO_AI_Helpers::attach_featured_image($post_id, $resp['image_base64'], $topic, $alt_for_image, 'autopilot');
                // 3.33.2: inline prepend per visibilita' frontend su tutti i theme
                $thumb_id = get_post_thumbnail_id($post_id);
                if ($thumb_id) {
                    $img_url = wp_get_attachment_url($thumb_id);
                    if ($img_url) {
                        $inline_block = SEO_AEO_AI_Helpers::build_inline_image_block($thumb_id, $img_url, $topic);
                        wp_update_post(array('ID' => $post_id, 'post_content' => $inline_block . $resp['content']));
                    }
                }
            } catch (Throwable $e) {
                orch_debug_log('SEO_AEO Autopilot attach image failed: ' . $e->getMessage());
            }
        }

        // Write meta tags via bridge (Yoast/RankMath/AIOSEO/native)
        if (!empty($resp['meta']) && is_array($resp['meta'])) {
            try {
                if (class_exists('SEO_AEO_Engine_Bridge')) {
                    SEO_AEO_Engine_Bridge::write_meta($post_id, array(
                        'meta_title'       => isset($resp['meta']['title']) ? $resp['meta']['title'] : '',
                        'meta_description' => isset($resp['meta']['description']) ? $resp['meta']['description'] : '',
                        'focus_keyword'    => $kwText,
                    ));
                }
            } catch (Throwable $e) {
                orch_debug_log('SEO_AEO Autopilot write_meta failed: ' . $e->getMessage());
            }
        }

        // Update job state
        $used[] = $kwText;
        $next_run = gmdate('Y-m-d H:i:s', time() + ((int) $job['frequency_days']) * 86400);
        $wpdb->update($tbl_jobs, array(
            'used_keywords_json' => wp_json_encode($used),
            'generated_count'    => ((int) $job['generated_count']) + 1,
            'last_run'           => current_time('mysql', true),
            'next_run'           => $next_run,
            'updated_at'         => current_time('mysql', true),
        ), array('id' => $job_id), array('%s', '%d', '%s', '%s', '%s'), array('%d'));

        self::log_event($job_id, $kwText, $post_id, 'success', null);
        return array(
            'success'     => true,
            'post_id'     => $post_id,
            'keyword'     => $kwText,
            'title'       => $topic,
            'edit_url'    => function_exists('get_edit_post_link') ? get_edit_post_link($post_id, '') : '',
            'preview_url' => function_exists('get_preview_post_link') ? get_preview_post_link($post_id) : '',
        );
    }

    /**
     * @deprecated 3.35.8 — Logica spostata in SEO_AEO_AI_Helpers::attach_featured_image().
     * Wrapper kept for backward compat se altro codice esterno chiama self::attach_featured_image.
     */
    private static function attach_featured_image($post_id, $base64, $title, $alt_text = '') {
        return SEO_AEO_AI_Helpers::attach_featured_image($post_id, $base64, $title, $alt_text, 'autopilot');
    }

    private static function log_event($job_id, $keyword, $post_id, $status, $error) {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE_LOG;
        $wpdb->insert($tbl, array(
            'job_id'        => (int) $job_id,
            'keyword'       => sanitize_text_field(mb_substr((string) $keyword, 0, 250)),
            'post_id'       => (int) $post_id,
            'status'        => sanitize_text_field((string) $status),
            'error_message' => $error ? mb_substr((string) $error, 0, 1000) : null,
            'created_at'    => current_time('mysql', true),
        ), array('%d', '%s', '%d', '%s', '%s', '%s'));
    }

    /**
     * Manual trigger per test: forza esecuzione di 1 keyword sul job (anche se next_run futuro).
     */
    public static function run_now($job_id) {
        return self::process_one_keyword((int) $job_id);
    }

    /**
     * 3.31.5: Risolve la config layout effettiva per un job.
     * Order of precedence: per-job override (layout_override_json) > global settings > defaults.
     * Ritorna array {post_type, template, category, author, status}.
     */
    public static function resolve_layout_for_job($job) {
        // Defaults globali (settings)
        $layout = array(
            'post_type' => get_option('seo_aeo_orchestra_layout_post_type', 'post'),
            'template'  => get_option('seo_aeo_orchestra_layout_template', 'default'),
            'category'  => (int) get_option('seo_aeo_orchestra_layout_category', 0),
            'author'    => (int) get_option('seo_aeo_orchestra_layout_author', 0),
            'status'    => get_option('seo_aeo_orchestra_layout_status', 'draft'),
        );
        // Per-job override
        if (is_array($job) && !empty($job['layout_override_json'])) {
            $ov = json_decode($job['layout_override_json'], true);
            if (is_array($ov) && !empty($ov['enabled'])) {
                if (!empty($ov['post_type'])) $layout['post_type'] = (string) $ov['post_type'];
                if (!empty($ov['template']))  $layout['template']  = (string) $ov['template'];
                if (isset($ov['category']))   $layout['category']  = (int) $ov['category'];
                if (isset($ov['author']))     $layout['author']    = (int) $ov['author'];
            }
        }
        // Sanity check post_type
        $existing = get_post_types(array('public' => true), 'names');
        if (!in_array($layout['post_type'], $existing, true)) $layout['post_type'] = 'post';
        if ($layout['author'] < 1) $layout['author'] = (int) get_current_user_id();
        return $layout;
    }
}

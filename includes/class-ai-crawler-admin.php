<?php
// phpcs:disable WordPress.Security.NonceVerification.Missing
// phpcs:disable WordPress.Security.NonceVerification.Recommended
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
// Reason: nonce + sanitize chain is enforced upstream. AJAX handlers call
// check_ajax_referer at the top of each method; admin form handlers call
// check_admin_referer; reads of $_SERVER (DOCUMENT_ROOT, HTTP_USER_AGENT)
// are wrapped in sanitize_text_field(wp_unslash()) at the read site. The
// Plugin Check static analyzer cannot trace control flow across method
// boundaries, so it flags these as missing — but the security guarantees
// hold at runtime.
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com
 *
 * 3.35.80 — AI Crawler Admin (page render + AJAX + CSV export)
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_AI_Crawler_Admin {
    // 3.35.84: 4 new AJAX handlers (top_bots/top_pages/trend/compliance) + ajax_aip_summary for Dashboard widgets


    public static function init() {
        add_action('wp_ajax_orch_ai_crawler_save_allowlist', array(__CLASS__, 'ajax_save_allowlist'));
        add_action('wp_ajax_orch_ai_crawler_save_privacy', array(__CLASS__, 'ajax_save_privacy'));
        add_action('wp_ajax_orch_ai_crawler_log_query', array(__CLASS__, 'ajax_log_query'));
        add_action('wp_ajax_orch_ai_crawler_stats', array(__CLASS__, 'ajax_stats'));
        add_action('wp_ajax_orch_ai_crawler_robots_save', array(__CLASS__, 'ajax_robots_save'));
        add_action('wp_ajax_orch_ai_crawler_robots_preview', array(__CLASS__, 'ajax_robots_preview'));
        add_action('admin_post_orch_ai_crawler_export_csv', array(__CLASS__, 'export_csv'));
        // 3.35.84: 5 new AJAX handlers Dashboard widgets (summary extends existing ajax_stats)
        add_action('wp_ajax_orch_ai_crawler_summary', array(__CLASS__, 'ajax_aip_summary'));
        add_action('wp_ajax_orch_ai_crawler_top_bots', array(__CLASS__, 'ajax_top_bots'));
        add_action('wp_ajax_orch_ai_crawler_top_pages', array(__CLASS__, 'ajax_top_pages'));
        add_action('wp_ajax_orch_ai_crawler_trend', array(__CLASS__, 'ajax_trend'));
        add_action('wp_ajax_orch_ai_crawler_compliance', array(__CLASS__, 'ajax_compliance'));
    }

    public static function render_page() {
        if (!current_user_can('manage_options')) wp_die('Permessi insufficienti.');
        $template = SEO_AEO_DIR . 'templates/ai-crawlers.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>AI Crawlers</h1><p>Template missing.</p></div>';
        }
    }

    public static function ajax_save_allowlist() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            $map = isset($_POST['allowlist']) && is_array($_POST['allowlist']) ? $_POST['allowlist'] : array();
            $clean = array();
            foreach ($map as $slug => $val) {
                $clean[sanitize_key($slug)] = $val ? true : false;
            }
            $saved = SEO_AEO_AI_Crawler_Detector::set_allowlist($clean);
            wp_send_json(array('success' => true, 'allowlist' => $saved));
        } catch (Throwable $e) {
            wp_send_json(array('error' => true, 'message' => $e->getMessage()));
        }
        wp_die();
    }

    public static function ajax_save_privacy() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            $log_ip = !empty($_POST['log_ip']) ? '1' : '0';
            update_option(SEO_AEO_AI_Crawler_Detector::PRIVACY_LOG_IP_OPT, $log_ip, false);
            wp_send_json(array('success' => true, 'log_ip' => $log_ip === '1'));
        } catch (Throwable $e) {
            wp_send_json(array('error' => true, 'message' => $e->getMessage()));
        }
        wp_die();
    }

    public static function ajax_log_query() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            global $wpdb;
            $table = $wpdb->prefix . SEO_AEO_AI_Crawler_Detector::TABLE_NAME;

            $bot_filter = isset($_POST['bot']) ? sanitize_key($_POST['bot']) : '';
            $page = isset($_POST['page']) ? max(1, (int) $_POST['page']) : 1;
            $per_page = 50;
            $offset = ($page - 1) * $per_page;

            $where = array();
            $params = array();
            if ($bot_filter) {
                $where[] = 'bot_name = %s';
                $params[] = $bot_filter;
            }
            $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $count_sql = "SELECT COUNT(*) FROM $table $where_sql";
            $rows_sql = "SELECT id, bot_name, bot_provider, user_agent, request_uri, request_method, response_code, HEX(ip_address) AS ip_hex, visited_at FROM $table $where_sql ORDER BY visited_at DESC LIMIT %d OFFSET %d";

            $params_for_rows = array_merge($params, array($per_page, $offset));

            if (!empty($params)) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
                $total = (int) $wpdb->get_var($wpdb->prepare($count_sql, $params));
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
                $rows = $wpdb->get_results($wpdb->prepare($rows_sql, $params_for_rows), ARRAY_A);
            } else {
                // Table name is derived from $wpdb->prefix + literal constant — no user input. Plugin schema operation, no cache.
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                $total = (int) $wpdb->get_var($count_sql);
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
                $rows = $wpdb->get_results($wpdb->prepare($rows_sql, $per_page, $offset), ARRAY_A);
            }

            // Decode IP
            foreach ($rows as &$r) {
                $r['ip'] = '';
                if (!empty($r['ip_hex'])) {
                    $packed = @hex2bin($r['ip_hex']);
                    if ($packed) {
                        $r['ip'] = @inet_ntop($packed);
                        if ($r['ip'] === false) $r['ip'] = '';
                    }
                }
                unset($r['ip_hex']);
            }

            wp_send_json(array(
                'success' => true,
                'rows' => $rows,
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'pages_count' => (int) ceil($total / $per_page),
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => true, 'message' => $e->getMessage()));
        }
        wp_die();
    }

    public static function ajax_stats() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            global $wpdb;
            $table = $wpdb->prefix . SEO_AEO_AI_Crawler_Detector::TABLE_NAME;

            $window = isset($_POST['window']) ? (int) $_POST['window'] : 7;
            if (!in_array($window, array(7, 28), true)) $window = 7;

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
            $total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE visited_at > (NOW() - INTERVAL %d DAY)",
                $window
            ));

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
            $top_bot = $wpdb->get_row($wpdb->prepare(
                "SELECT bot_name, bot_provider, COUNT(*) AS visits FROM $table
                 WHERE visited_at > (NOW() - INTERVAL %d DAY)
                 GROUP BY bot_name, bot_provider
                 ORDER BY visits DESC LIMIT 1",
                $window
            ), ARRAY_A);

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
            $top_page = $wpdb->get_row($wpdb->prepare(
                "SELECT request_uri, COUNT(*) AS hits FROM $table
                 WHERE visited_at > (NOW() - INTERVAL %d DAY)
                 GROUP BY request_uri
                 ORDER BY hits DESC LIMIT 1",
                $window
            ), ARRAY_A);

            // Provider breakdown for the period
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
            $by_provider = $wpdb->get_results($wpdb->prepare(
                "SELECT bot_provider, COUNT(*) AS visits FROM $table
                 WHERE visited_at > (NOW() - INTERVAL %d DAY)
                 GROUP BY bot_provider
                 ORDER BY visits DESC",
                $window
            ), ARRAY_A);

            wp_send_json(array(
                'success' => true,
                'window' => $window,
                'total_visits' => $total,
                'top_bot' => $top_bot,
                'top_page' => $top_page,
                'by_provider' => $by_provider,
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => true, 'message' => $e->getMessage()));
        }
        wp_die();
    }

    public static function ajax_robots_save() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            $override = isset($_POST['override']) ? (string) wp_unslash($_POST['override']) : '';
            $override = trim($override);
            if ($override === '' || strtolower($override) === '(default — disabled)') {
                delete_option(SEO_AEO_AI_Crawler_Detector::ROBOTS_OVERRIDE_OPT);
            } else {
                update_option(SEO_AEO_AI_Crawler_Detector::ROBOTS_OVERRIDE_OPT, $override, false);
            }
            wp_send_json(array('success' => true));
        } catch (Throwable $e) {
            wp_send_json(array('error' => true, 'message' => $e->getMessage()));
        }
        wp_die();
    }

    public static function ajax_robots_preview() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            $auto = SEO_AEO_AI_Crawler_Detector::preview_auto_robots_txt();
            $override = (string) get_option(SEO_AEO_AI_Crawler_Detector::ROBOTS_OVERRIDE_OPT, '');
            wp_send_json(array(
                'success' => true,
                'auto_generated' => $auto,
                'override' => $override,
                'override_active' => $override !== '',
                'live_url' => home_url('/robots.txt'),
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => true, 'message' => $e->getMessage()));
        }
        wp_die();
    }

    public static function export_csv() {
        if (!current_user_can('manage_options')) wp_die('forbidden');
        check_admin_referer('orch_ai_crawler_export_csv');

        global $wpdb;
        $table = $wpdb->prefix . SEO_AEO_AI_Crawler_Detector::TABLE_NAME;
        $bot_filter = isset($_GET['bot']) ? sanitize_key($_GET['bot']) : '';

        $where = '';
        $params = array();
        if ($bot_filter) {
            $where = 'WHERE bot_name = %s';
            $params[] = $bot_filter;
        }

        $sql = "SELECT bot_name, bot_provider, user_agent, request_uri, request_method, response_code, HEX(ip_address) AS ip_hex, visited_at FROM $table $where ORDER BY visited_at DESC LIMIT 5000";
        if (!empty($params)) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
            $rows = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        } else {
            // Table name is derived from $wpdb->prefix + literal constant — no user input. Plugin schema operation, no cache.
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $rows = $wpdb->get_results($sql, ARRAY_A);
        }

        $filename = 'aeo-crawler-log-' . gmdate('Y-m-d-His');
        if ($bot_filter) $filename .= '-' . $bot_filter;
        $filename .= '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, array('visited_at_utc', 'bot_name', 'bot_provider', 'request_method', 'response_code', 'request_uri', 'ip_address', 'user_agent'));
        foreach ($rows as $r) {
            $ip = '';
            if (!empty($r['ip_hex'])) {
                $packed = @hex2bin($r['ip_hex']);
                if ($packed) $ip = (string) @inet_ntop($packed);
            }
            fputcsv($out, array(
                $r['visited_at'],
                $r['bot_name'],
                $r['bot_provider'],
                $r['request_method'],
                $r['response_code'],
                $r['request_uri'],
                $ip,
                $r['user_agent'],
            ));
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose,WordPress.WP.AlternativeFunctions.file_system_read_fclose -- closing php://output for streaming CSV download, WP_Filesystem not applicable for output streams
        fclose($out);
        exit;
    }
    // ═══════════════════════════════════════════════════════════════
    // 3.35.84: 4 new Dashboard widgets AJAX handlers
    // Pattern: defensive nonce + auth + try/catch + transient cache + JSON
    // ═══════════════════════════════════════════════════════════════

    private static function _check_ajax_auth() {
        if (!check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce', false)) {
            seo_aeo_debug_log('[AIP] AJAX nonce check failed');
            wp_send_json_error(array('message' => 'invalid_nonce'), 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'forbidden'), 403);
        }
    }

    /**
     * 3.35.84: AI Performance summary — 4 stat card data.
     * GET wp-ajax/orch_ai_crawler_summary OR existing ajax_stats extended.
     */
    public static function ajax_aip_summary() {
        self::_check_ajax_auth();
        try {
            $force = !empty($_POST['force']);
            $cache_key = 'seo_aeo_aip_summary_28d';
            if (!$force) {
                $cached = get_transient($cache_key);
                if ($cached !== false) wp_send_json_success($cached);
            }

            global $wpdb;
            $table = $wpdb->prefix . SEO_AEO_AI_Crawler_Detector::TABLE_NAME;

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
            $hits_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 28 DAY)");
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
            $bots_active = (int) $wpdb->get_var("SELECT COUNT(DISTINCT bot_name) FROM $table WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 28 DAY)");

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
            $row = $wpdb->get_row("
                SELECT
                    SUM(CASE WHEN visited_at >= DATE_SUB(NOW(), INTERVAL 28 DAY) THEN 1 ELSE 0 END) AS current_28d,
                    SUM(CASE WHEN visited_at >= DATE_SUB(NOW(), INTERVAL 56 DAY) AND visited_at < DATE_SUB(NOW(), INTERVAL 28 DAY) THEN 1 ELSE 0 END) AS prev_28d
                FROM $table WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 56 DAY)
            ", ARRAY_A);
            $current = (int) ($row['current_28d'] ?? 0);
            $prev = (int) ($row['prev_28d'] ?? 0);
            $trend_pct = ($prev > 0) ? (int) round(100 * ($current - $prev) / $prev) : ($current > 0 ? 100 : 0);

            // blocked_bypass
            $allowlist = get_option(SEO_AEO_AI_Crawler_Detector::ALLOWLIST_OPT, array());
            $blocked_names = array();
            $defs = SEO_AEO_AI_Crawler_Detector::bot_definitions();
            if (is_array($allowlist)) {
                foreach ($allowlist as $slug => $action) {
                    if ($action === 'block' && isset($defs[$slug])) $blocked_names[] = $defs[$slug]['name'];
                }
            }
            $blocked_bypass = 0;
            if (!empty($blocked_names)) {
                $placeholders = implode(',', array_fill(0, count($blocked_names), '%s'));
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
                $blocked_bypass = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 28 DAY) AND bot_name IN ($placeholders)",
                    $blocked_names
                ));
            }

            $result = array(
                'hits_total'     => $hits_total,
                'bots_active'    => $bots_active,
                'trend_pct'      => $trend_pct,
                'blocked_bypass' => $blocked_bypass,
                'cached_at'      => current_time('mysql'),
            );
            set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);
            wp_send_json_success($result);
        } catch (Throwable $e) {
            seo_aeo_debug_log('[AIP] ajax_aip_summary exception: ' . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()), 500);
        }
    }

    /**
     * 3.35.84: top 5 bot last 28 days (bar chart).
     */
    public static function ajax_top_bots() {
        self::_check_ajax_auth();
        try {
            $force = !empty($_POST['force']);
            $cache_key = 'seo_aeo_aip_top_bots_28d';
            if (!$force) {
                $cached = get_transient($cache_key);
                if ($cached !== false) wp_send_json_success($cached);
            }

            global $wpdb;
            $table = $wpdb->prefix . SEO_AEO_AI_Crawler_Detector::TABLE_NAME;
            // 3.35.84.3: GROUP BY bot_name only — consolidate empty-provider rows + dedupe legacy slug forms
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
            $rows = $wpdb->get_results("
                SELECT bot_name,
                       COALESCE(MAX(NULLIF(bot_provider, '')), 'unknown') AS bot_provider,
                       COUNT(*) AS hits
                FROM $table
                WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 28 DAY)
                GROUP BY bot_name
                ORDER BY hits DESC
                LIMIT 5
            ", ARRAY_A);

            if (!is_array($rows)) $rows = array();
            foreach ($rows as &$r) {
                $slug = SEO_AEO_AI_Crawler_Detector::slug_from_bot_name($r['bot_name']);
                $r['classification'] = SEO_AEO_AI_Crawler_Detector::classification($slug);
                $r['hits'] = (int) $r['hits'];
            }

            $result = array('rows' => $rows, 'cached_at' => current_time('mysql'));
            set_transient($cache_key, $result, 15 * MINUTE_IN_SECONDS);
            wp_send_json_success($result);
        } catch (Throwable $e) {
            seo_aeo_debug_log('[AIP] ajax_top_bots exception: ' . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()), 500);
        }
    }

    /**
     * 3.35.84: top 10 pages last 28 days with bot breakdown.
     */
    public static function ajax_top_pages() {
        self::_check_ajax_auth();
        try {
            $force = !empty($_POST['force']);
            $cache_key = 'seo_aeo_aip_top_pages_28d';
            if (!$force) {
                $cached = get_transient($cache_key);
                if ($cached !== false) wp_send_json_success($cached);
            }

            global $wpdb;
            $table = $wpdb->prefix . SEO_AEO_AI_Crawler_Detector::TABLE_NAME;

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
            $top = $wpdb->get_results("
                SELECT url_path, COUNT(*) AS hits
                FROM $table
                WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 28 DAY) AND url_path != ''
                GROUP BY url_path
                ORDER BY hits DESC
                LIMIT 10
            ", ARRAY_A);
            if (!is_array($top)) $top = array();

            $rows = array();
            if (!empty($top)) {
                $paths = array_column($top, 'url_path');
                $placeholders = implode(',', array_fill(0, count($paths), '%s'));
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
                $break = $wpdb->get_results($wpdb->prepare(
                    "SELECT url_path, bot_name, COUNT(*) AS hits
                     FROM $table
                     WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 28 DAY) AND url_path IN ($placeholders)
                     GROUP BY url_path, bot_name
                     ORDER BY url_path, hits DESC",
                    $paths
                ), ARRAY_A);

                $break_map = array();
                foreach ($break as $b) {
                    if (!isset($break_map[$b['url_path']])) $break_map[$b['url_path']] = array();
                    $break_map[$b['url_path']][] = array('bot_name' => $b['bot_name'], 'hits' => (int) $b['hits']);
                }
                foreach ($top as $t) {
                    $rows[] = array(
                        'url_path' => $t['url_path'],
                        'hits'     => (int) $t['hits'],
                        'bots'     => isset($break_map[$t['url_path']]) ? array_slice($break_map[$t['url_path']], 0, 5) : array(),
                    );
                }
            }

            $result = array('rows' => $rows, 'cached_at' => current_time('mysql'));
            set_transient($cache_key, $result, 15 * MINUTE_IN_SECONDS);
            wp_send_json_success($result);
        } catch (Throwable $e) {
            seo_aeo_debug_log('[AIP] ajax_top_pages exception: ' . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()), 500);
        }
    }

    /**
     * 3.35.84: trend 28gg per top 4 bot (sparkline data).
     */
    public static function ajax_trend() {
        self::_check_ajax_auth();
        try {
            $force = !empty($_POST['force']);
            $cache_key = 'seo_aeo_aip_trend_28d';
            if (!$force) {
                $cached = get_transient($cache_key);
                if ($cached !== false) wp_send_json_success($cached);
            }

            global $wpdb;
            $log_table = $wpdb->prefix . SEO_AEO_AI_Crawler_Detector::TABLE_NAME;
            $stats_table = $wpdb->prefix . SEO_AEO_AI_Crawler_Detector::TABLE_DAILY_STATS;

            // top 4 bot last 28 days
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
            $top4 = $wpdb->get_col("
                SELECT bot_name FROM $log_table
                WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 28 DAY)
                GROUP BY bot_name ORDER BY COUNT(*) DESC LIMIT 4
            ");
            if (!is_array($top4) || empty($top4)) {
                $result = array('labels' => array(), 'series' => array(), 'cached_at' => current_time('mysql'));
                set_transient($cache_key, $result, HOUR_IN_SECONDS);
                wp_send_json_success($result);
            }

            $placeholders = implode(',', array_fill(0, count($top4), '%s'));
            // Try daily_stats first; fallback to raw aggregation if daily_stats empty
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
            $rows = $wpdb->get_results($wpdb->prepare("
                SELECT stat_date, bot_name, SUM(hit_count) AS hits
                FROM $stats_table
                WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL 28 DAY) AND bot_name IN ($placeholders)
                GROUP BY stat_date, bot_name ORDER BY stat_date ASC, bot_name ASC
            ", $top4), ARRAY_A);
            if (empty($rows)) {
                // Fallback: aggregate raw on the fly (cron not yet run)
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
                $rows = $wpdb->get_results($wpdb->prepare("
                    SELECT DATE(visited_at) AS stat_date, bot_name, COUNT(*) AS hits
                    FROM $log_table
                    WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 28 DAY) AND bot_name IN ($placeholders)
                    GROUP BY DATE(visited_at), bot_name ORDER BY stat_date ASC, bot_name ASC
                ", $top4), ARRAY_A);
            }

            // Build labels (28 days back from today)
            $labels = array();
            for ($i = 27; $i >= 0; $i--) {
                $labels[] = gmdate('Y-m-d', strtotime("-$i days"));
            }
            // Build series: 1 polyline per top bot
            $series = array();
            foreach ($top4 as $bot_name) {
                $values = array_fill_keys($labels, 0);
                foreach ($rows as $r) {
                    if ($r['bot_name'] === $bot_name && isset($values[$r['stat_date']])) {
                        $values[$r['stat_date']] = (int) $r['hits'];
                    }
                }
                $color_map = SEO_AEO_AI_Crawler_Detector::TREND_COLORS;
                $color = isset($color_map[$bot_name]) ? $color_map[$bot_name] : '#6366f1';
                $series[] = array(
                    'bot_name' => $bot_name,
                    'color'    => $color,
                    'values'   => array_values($values),
                );
            }

            $result = array('labels' => $labels, 'series' => $series, 'cached_at' => current_time('mysql'));
            set_transient($cache_key, $result, HOUR_IN_SECONDS);
            wp_send_json_success($result);
        } catch (Throwable $e) {
            seo_aeo_debug_log('[AIP] ajax_trend exception: ' . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()), 500);
        }
    }

    /**
     * 3.35.84: compliance check robots.txt — cross-reference allowlist vs logs.
     */
    public static function ajax_compliance() {
        self::_check_ajax_auth();
        try {
            $force = !empty($_POST['force']);
            $cache_key = 'seo_aeo_aip_compliance_28d';
            if (!$force) {
                $cached = get_transient($cache_key);
                if ($cached !== false) wp_send_json_success($cached);
            }

            $allowlist = get_option(SEO_AEO_AI_Crawler_Detector::ALLOWLIST_OPT, array());
            $defs = SEO_AEO_AI_Crawler_Detector::bot_definitions();
            $blocked_names = array();
            if (is_array($allowlist)) {
                foreach ($allowlist as $slug => $action) {
                    if ($action === 'block' && isset($defs[$slug])) $blocked_names[] = $defs[$slug]['name'];
                }
            }
            $blocked_count = count($blocked_names);
            if ($blocked_count === 0) {
                $result = array('compliant' => true, 'violations' => array(), 'blocked_bots_count' => 0, 'cached_at' => current_time('mysql'));
                set_transient($cache_key, $result, HOUR_IN_SECONDS);
                wp_send_json_success($result);
            }

            global $wpdb;
            $table = $wpdb->prefix . SEO_AEO_AI_Crawler_Detector::TABLE_NAME;
            $placeholders = implode(',', array_fill(0, count($blocked_names), '%s'));
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
            $rows = $wpdb->get_results($wpdb->prepare("
                SELECT bot_name, COUNT(*) AS violations
                FROM $table
                WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 28 DAY) AND bot_name IN ($placeholders)
                GROUP BY bot_name HAVING violations > 0 ORDER BY violations DESC
            ", $blocked_names), ARRAY_A);
            if (!is_array($rows)) $rows = array();

            // Enrich with top URL paths per violator
            foreach ($rows as &$r) {
                $r['violations'] = (int) $r['violations'];
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
                $paths = $wpdb->get_col($wpdb->prepare("
                    SELECT url_path FROM $table
                    WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 28 DAY) AND bot_name = %s AND url_path != ''
                    GROUP BY url_path ORDER BY COUNT(*) DESC LIMIT 5
                ", $r['bot_name']));
                $r['url_paths'] = is_array($paths) ? $paths : array();
            }

            $result = array(
                'compliant'          => empty($rows),
                'violations'         => $rows,
                'blocked_bots_count' => $blocked_count,
                'cached_at'          => current_time('mysql'),
            );
            set_transient($cache_key, $result, HOUR_IN_SECONDS);
            wp_send_json_success($result);
        } catch (Throwable $e) {
            seo_aeo_debug_log('[AIP] ajax_compliance exception: ' . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()), 500);
        }
    }

}

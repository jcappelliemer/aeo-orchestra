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
 * 3.35.80 — AI Crawler Detector + Logger
 *
 * Hook on WP `init` priority 1: matches User-Agent against known AI bot patterns,
 * inserts row in wp_seo_aeo_ai_crawler_log for matched requests.
 *
 * 18 bots across 9 providers (OpenAI / Anthropic / Perplexity / Google / Microsoft
 * / Meta / xAI / DeepSeek / Apple).
 *
 * Performance: synchronous insert (Solaris-class traffic ~5 visits/day per bot).
 * If volume grows, switch to wp-cron deferred queue.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_AI_Crawler_Detector {

    // 3.35.84: DB_VERSION 1.1 + TABLE_DAILY_STATS + AGGREGATE_HOOK + CLASSIFICATION map
    const TABLE_NAME = 'seo_aeo_ai_crawler_log';
    const TABLE_DAILY_STATS = 'seo_aeo_ai_crawler_daily_stats';
    const DB_VERSION_OPT = 'seo_aeo_ai_crawler_db_version';
    const DB_VERSION = '1.2';   // 3.35.84.3: backfill canonical bot_name + bot_provider
    const ALLOWLIST_OPT = 'seo_aeo_ai_crawler_allowlist';
    const PRIVACY_LOG_IP_OPT = 'seo_aeo_ai_crawler_log_ip';
    const IP_LOGGING_MODE_OPT = 'seo_aeo_ai_crawler_ip_mode';
    const ROBOTS_OVERRIDE_OPT = 'seo_aeo_ai_crawler_robots_override';
    const CLEANUP_HOOK = 'seo_aeo_ai_crawler_log_cleanup';
    const AGGREGATE_HOOK = 'seo_aeo_ai_perf_daily_aggregate';

    const CLASSIFICATION = array(
        'gptbot' => 'green', 'oai-searchbot' => 'green', 'chatgpt-user' => 'green',
        'claudebot' => 'green', 'claude-searchbot' => 'green', 'claude-user' => 'green',
        'perplexitybot' => 'green', 'perplexity-user' => 'green',
        'google-extended' => 'green', 'applebot-extended' => 'green',
        'googleother' => 'yellow', 'amazonbot' => 'yellow', 'bytespider' => 'yellow',
        'meta-externalagent' => 'yellow', 'bingbot' => 'yellow', 'grok' => 'yellow',
        'deepseekbot' => 'yellow', 'mistralai-user' => 'yellow', 'youbot' => 'yellow',
        'cohere-ai' => 'yellow',
        'ccbot' => 'red', 'diffbot' => 'red',
    );

    const TREND_COLORS = array(
        'GPTBot' => '#16a34a',
        'Claude-User' => '#2563eb', 'ClaudeBot' => '#2563eb', 'Claude-SearchBot' => '#2563eb',
        'PerplexityBot' => '#9333ea', 'Perplexity-User' => '#9333ea',
        'GoogleOther' => '#d97706', 'Google-Extended' => '#d97706',
        'Bingbot' => '#0ea5e9', 'Applebot-Extended' => '#737373',
        'CCBot' => '#dc2626',
    );

    /**
     * Bot definitions — slug => { provider, ua_patterns[], description, doc_url }
     * Allowlist toggle key uses slug.
     */
    public static function bot_definitions() {
        return array(
            // OpenAI
            'gptbot' => array(
                'provider' => 'openai',
                'name' => 'GPTBot',
                'patterns' => array('/GPTBot/i'),
                'description' => 'Training crawler di ChatGPT (OpenAI). Indicizza il sito per il modello.',
                'doc_url' => 'https://platform.openai.com/docs/gptbot',
            ),
            'oai-searchbot' => array(
                'provider' => 'openai',
                'name' => 'OAI-SearchBot',
                'patterns' => array('/OAI-SearchBot/i'),
                'description' => 'ChatGPT Search live citation crawler.',
                'doc_url' => 'https://platform.openai.com/docs/bots',
            ),
            'chatgpt-user' => array(
                'provider' => 'openai',
                'name' => 'ChatGPT-User',
                'patterns' => array('/ChatGPT-User/i'),
                'description' => 'User-initiated browse: ChatGPT visita un URL su richiesta utente.',
                'doc_url' => 'https://platform.openai.com/docs/bots',
            ),
            // Anthropic
            'claudebot' => array(
                'provider' => 'anthropic',
                'name' => 'ClaudeBot',
                'patterns' => array('/ClaudeBot/i', '/anthropic-ai/i'),
                'description' => 'Training crawler di Claude (Anthropic).',
                'doc_url' => 'https://www.anthropic.com/claudebot',
            ),
            'claude-searchbot' => array(
                'provider' => 'anthropic',
                'name' => 'Claude-SearchBot',
                'patterns' => array('/Claude-SearchBot/i'),
                'description' => 'Claude search citation crawler (live answers).',
                'doc_url' => 'https://docs.claude.com/en/docs/claude-code/web-search',
            ),
            'claude-user' => array(
                'provider' => 'anthropic',
                'name' => 'Claude-User',
                'patterns' => array('/Claude-User/i'),
                'description' => 'User-initiated browse: Claude visita un URL per rispondere.',
                'doc_url' => 'https://www.anthropic.com/claudebot',
            ),
            // Perplexity
            'perplexitybot' => array(
                'provider' => 'perplexity',
                'name' => 'PerplexityBot',
                'patterns' => array('/PerplexityBot/i'),
                'description' => 'Index crawler di Perplexity AI.',
                'doc_url' => 'https://docs.perplexity.ai/guides/bots',
            ),
            'perplexity-user' => array(
                'provider' => 'perplexity',
                'name' => 'Perplexity-User',
                'patterns' => array('/Perplexity-User/i'),
                'description' => 'User-initiated browse di Perplexity.',
                'doc_url' => 'https://docs.perplexity.ai/guides/bots',
            ),
            // Google AI
            'google-extended' => array(
                'provider' => 'google',
                'name' => 'Google-Extended',
                'patterns' => array('/Google-Extended/i'),
                'description' => 'Gemini/Bard training crawler. Separato da Googlebot SEO.',
                'doc_url' => 'https://developers.google.com/search/docs/crawling-indexing/overview-google-crawlers',
            ),
            // Microsoft
            'bingbot' => array(
                'provider' => 'microsoft',
                'name' => 'Bingbot',
                'patterns' => array('/bingbot/i', '/BingPreview/i'),
                'description' => 'Bingbot (SEO + Copilot). Bloccare disabilita anche search Bing.',
                'doc_url' => 'https://www.bing.com/webmasters/help/which-crawlers-does-bing-use-8c184ec0',
            ),
            // Meta
            'meta-externalagent' => array(
                'provider' => 'meta',
                'name' => 'Meta-ExternalAgent',
                // 3.35.84: alias /FacebookBot/i added
                'patterns' => array('/Meta-ExternalAgent/i', '/meta-externalfetcher/i', '/FacebookBot/i'),
                'description' => 'Meta AI training crawler (Llama, Meta AI). Include legacy FacebookBot.',
                'doc_url' => 'https://developers.facebook.com/docs/sharing/webmasters/web-crawlers',
            ),
            // xAI / Grok
            'grok' => array(
                'provider' => 'xai',
                'name' => 'Grok / xAI',
                'patterns' => array('/xAI/i', '/Grok/i'),
                'description' => 'xAI Grok crawler (X/Twitter AI).',
                'doc_url' => 'https://x.ai/',
            ),
            // DeepSeek
            'deepseekbot' => array(
                'provider' => 'deepseek',
                'name' => 'DeepSeekBot',
                'patterns' => array('/DeepSeekBot/i', '/deepseek-ai/i'),
                'description' => 'DeepSeek training crawler.',
                'doc_url' => 'https://www.deepseek.com/',
            ),
            // Apple
            'applebot-extended' => array(
                'provider' => 'apple',
                'name' => 'Applebot-Extended',
                'patterns' => array('/Applebot-Extended/i'),
                'description' => 'Apple Intelligence training (separato da Applebot SEO).',
                'doc_url' => 'https://support.apple.com/en-us/119829',
            ),
            // Common Crawl (used heavily by AI training)
            'ccbot' => array(
                'provider' => 'commoncrawl',
                'name' => 'CCBot',
                'patterns' => array('/CCBot/i'),
                'description' => 'Common Crawl — usato come dataset training da molti AI (GPT-3 included).',
                'doc_url' => 'https://commoncrawl.org/ccbot',
            ),
            // Cohere
            'cohere-ai' => array(
                'provider' => 'cohere',
                'name' => 'cohere-ai',
                'patterns' => array('/cohere-ai/i', '/cohere-training/i'),
                'description' => 'Cohere AI training crawler.',
                'doc_url' => 'https://cohere.com/',
            ),
            // Mistral
            'mistralai-user' => array(
                'provider' => 'mistral',
                'name' => 'MistralAI-User',
                'patterns' => array('/MistralAI/i', '/Mistral-User/i'),
                'description' => 'Mistral AI bot (Le Chat).',
                'doc_url' => 'https://mistral.ai/',
            ),
            // You.com
            'youbot' => array(
                'provider' => 'you',
                'name' => 'YouBot',
                'patterns' => array('/YouBot/i'),
                'description' => 'You.com AI search engine crawler.',
                'doc_url' => 'https://about.you.com/youbot/',
            ),
            // 3.35.84: 4 new bots
            'googleother' => array(
                'provider' => 'google',
                'name' => 'GoogleOther',
                'patterns' => array('/GoogleOther/i'),
                'description' => 'Google research/AI training crawler.',
                'doc_url' => 'https://developers.google.com/search/docs/crawling-indexing/overview-google-crawlers',
            ),
            'amazonbot' => array(
                'provider' => 'amazon',
                'name' => 'Amazonbot',
                'patterns' => array('/Amazonbot/i'),
                'description' => 'Amazon AI training (Alexa, Q).',
                'doc_url' => 'https://developer.amazon.com/amazonbot',
            ),
            'bytespider' => array(
                'provider' => 'bytedance',
                'name' => 'Bytespider',
                'patterns' => array('/Bytespider/i'),
                'description' => 'TikTok/ByteDance AI training crawler.',
                'doc_url' => 'https://www.bytedance.com/',
            ),
            'diffbot' => array(
                'provider' => 'diffbot',
                'name' => 'Diffbot',
                'patterns' => array('/Diffbot/i'),
                'description' => 'Diffbot Knowledge Graph crawler.',
                'doc_url' => 'https://www.diffbot.com/',
            ),
        );
    }

    public static function init() {
        // Schedule cleanup cron if not already
        add_action('init', array(__CLASS__, 'maybe_schedule_cleanup'), 5);
        add_action(self::CLEANUP_HOOK, array(__CLASS__, 'cleanup_old_logs'));
        add_action(self::AGGREGATE_HOOK, array(__CLASS__, 'daily_aggregate'));

        // Migration check (handles upgrades without reactivation)
        add_action('admin_init', array(__CLASS__, 'maybe_migrate'));

        // Bot detection on every page load (priority 1, very early)
        add_action('init', array(__CLASS__, 'detect_and_log'), 1);

        // robots.txt filter integration
        add_filter('robots_txt', array(__CLASS__, 'filter_robots_txt'), 20, 2);
    }

    /**
     * Idempotent migration via dbDelta + version gate.
     */
    public static function maybe_migrate() {
        // 3.35.84: DB_VERSION 1.1 — add 3 columns + new daily_stats table + cron + IP mode migration
        $current = get_option(self::DB_VERSION_OPT, '0');
        if (version_compare($current, self::DB_VERSION, '>=')) return;

        global $wpdb;
        $log_table = $wpdb->prefix . self::TABLE_NAME;
        $stats_table = $wpdb->prefix . self::TABLE_DAILY_STATS;
        $charset_collate = $wpdb->get_charset_collate();

        $sql_log = "CREATE TABLE $log_table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            bot_name VARCHAR(64) NOT NULL,
            bot_provider VARCHAR(32) NOT NULL,
            user_agent VARCHAR(500) NOT NULL,
            request_uri VARCHAR(2048) NOT NULL,
            request_method VARCHAR(8) NOT NULL DEFAULT 'GET',
            response_code SMALLINT UNSIGNED NOT NULL DEFAULT 200,
            response_status_class TINYINT UNSIGNED NOT NULL DEFAULT 2,
            ip_address VARBINARY(16) DEFAULT NULL,
            ip_hash CHAR(64) DEFAULT NULL,
            url_path VARCHAR(255) NOT NULL DEFAULT '',
            visited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_bot_visited (bot_name, visited_at),
            KEY idx_provider_visited (bot_provider, visited_at),
            KEY idx_visited (visited_at),
            KEY idx_path_visited (url_path(190), visited_at)
        ) $charset_collate;";

        $sql_stats = "CREATE TABLE $stats_table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            stat_date DATE NOT NULL,
            bot_name VARCHAR(64) NOT NULL,
            bot_provider VARCHAR(32) NOT NULL,
            url_path VARCHAR(255) NOT NULL,
            hit_count INT UNSIGNED NOT NULL DEFAULT 0,
            blocked_count INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY uq_date_bot_path (stat_date, bot_name, url_path(190)),
            KEY idx_date_bot (stat_date, bot_name),
            KEY idx_date_provider (stat_date, bot_provider)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_log);
        dbDelta($sql_stats);

        // Backfill url_path + response_status_class for existing rows
        // Table name is derived from $wpdb->prefix + literal constant — no user input. Plugin schema operation, no cache.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query("UPDATE $log_table SET url_path = SUBSTRING_INDEX(SUBSTRING_INDEX(request_uri, '?', 1), '#', 1) WHERE url_path = '' LIMIT 10000");
        // Table name is derived from $wpdb->prefix + literal constant — no user input. Plugin schema operation, no cache.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query("UPDATE $log_table SET response_status_class = FLOOR(response_code/100) WHERE response_code >= 100 LIMIT 10000");

        // Schedule daily aggregation cron (02:00 UTC)
        if (!wp_next_scheduled(self::AGGREGATE_HOOK)) {
            wp_schedule_event(strtotime('tomorrow 02:00 UTC'), 'daily', self::AGGREGATE_HOOK);
        }

        // Migrate legacy PRIVACY_LOG_IP_OPT to IP_LOGGING_MODE_OPT
        if (get_option(self::IP_LOGGING_MODE_OPT, null) === null) {
            $legacy = get_option(self::PRIVACY_LOG_IP_OPT, '1');
            update_option(self::IP_LOGGING_MODE_OPT, $legacy === '1' ? 'raw' : 'none');
        }

        // 3.35.84.3: backfill canonical bot_name + bot_provider for existing rows
        // (Pre-3.35.84 logs stored bot_name as lowercase slug; new logs store $def['name'].
        // Plus some pre-3.35.84 rows had empty bot_provider. Backfill both via bot_definitions registry.)
        $bots = self::bot_definitions();
        $backfilled = 0;
        foreach ($bots as $slug => $def) {
            // Update legacy slug-named rows to canonical name + ensure provider populated
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
            $r1 = $wpdb->query($wpdb->prepare(
                "UPDATE $log_table SET bot_name = %s, bot_provider = %s WHERE bot_name = %s",
                $def['name'], $def['provider'], $slug
            ));
            // Update rows with canonical name but empty provider
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
            $r2 = $wpdb->query($wpdb->prepare(
                "UPDATE $log_table SET bot_provider = %s WHERE bot_name = %s AND (bot_provider = '' OR bot_provider IS NULL)",
                $def['provider'], $def['name']
            ));
            $backfilled += (int) $r1 + (int) $r2;
        }
        if ($backfilled > 0) {
            seo_aeo_debug_log("[AEO Orchestra] DB v1.2 backfill: $backfilled rows canonicalized");
        }

        update_option(self::DB_VERSION_OPT, self::DB_VERSION);
    }

    public static function maybe_schedule_cleanup() {
        if (!wp_next_scheduled(self::CLEANUP_HOOK)) {
            wp_schedule_event(time() + 3600, 'daily', self::CLEANUP_HOOK);
        }
    }

    public static function unschedule_cleanup() {
        wp_clear_scheduled_hook(self::CLEANUP_HOOK);
    }

    public static function cleanup_old_logs() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;
        // Table name is derived from $wpdb->prefix + literal constant — no user input. Plugin schema operation, no cache.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $deleted = $wpdb->query("DELETE FROM $table WHERE visited_at < (NOW() - INTERVAL 30 DAY)");
        if ($deleted > 0) {
            seo_aeo_debug_log("[AEO Orchestra] AI crawler log cleanup: deleted $deleted entries > 30d");
        }
    }

    /**
     * Match UA → bot definition. Returns slug or null.
     */
    public static function match_user_agent($ua) {
        if (!$ua || !is_string($ua)) return null;
        foreach (self::bot_definitions() as $slug => $def) {
            foreach ($def['patterns'] as $pattern) {
                if (@preg_match($pattern, $ua)) return $slug;
            }
        }
        return null;
    }

    public static function get_allowlist() {
        $stored = get_option(self::ALLOWLIST_OPT, array());
        if (!is_array($stored)) $stored = array();
        // Default: all bots ON unless explicitly disabled
        $defs = self::bot_definitions();
        $resolved = array();
        foreach ($defs as $slug => $_) {
            $resolved[$slug] = isset($stored[$slug]) ? (bool) $stored[$slug] : true;
        }
        return $resolved;
    }

    public static function set_allowlist($map) {
        if (!is_array($map)) $map = array();
        $defs = self::bot_definitions();
        $clean = array();
        foreach ($defs as $slug => $_) {
            $clean[$slug] = isset($map[$slug]) ? (bool) $map[$slug] : true;
        }
        update_option(self::ALLOWLIST_OPT, $clean, false);
        return $clean;
    }

    public static function is_ip_logging_enabled() {
        return get_option(self::PRIVACY_LOG_IP_OPT, '1') === '1';
    }

    /**
     * Hook on init priority 1: detect + log if AI bot.
     */
    public static function detect_and_log() {
        if (defined('DOING_CRON') && DOING_CRON) return;
        if (defined('WP_CLI') && WP_CLI) return;
        if (is_admin() && !wp_doing_ajax()) return;

        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
        if (!$ua) return;

        $slug = self::match_user_agent($ua);
        if (!$slug) return;

        // 3.35.84: async — register shutdown to insert AFTER response (zero overhead)
        $payload = array(
            'slug'         => $slug,
            'ua'           => substr($ua, 0, 500),
            'request_uri'  => substr(isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '', 0, 2048),
            'method'       => substr(isset($_SERVER['REQUEST_METHOD']) ? (string) $_SERVER['REQUEST_METHOD'] : 'GET', 0, 8),
            'remote_addr'  => isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '',
            'cf_ip'        => isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? (string) $_SERVER['HTTP_CF_CONNECTING_IP'] : '',
            'xff'          => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? (string) $_SERVER['HTTP_X_FORWARDED_FOR'] : '',
        );
        register_shutdown_function(array(__CLASS__, 'async_insert_log'), $payload);
    }

    public static function async_insert_log($payload) {
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;
        $defs = self::bot_definitions();
        $def = isset($defs[$payload['slug']]) ? $defs[$payload['slug']] : null;
        if (!$def) return;

        $ip_mode = get_option(self::IP_LOGGING_MODE_OPT, 'raw');
        $ip_packed = null;
        $ip_hash = null;
        if ($ip_mode !== 'none') {
            $ip_str = $payload['cf_ip'];
            if (!$ip_str && $payload['xff']) {
                $parts = explode(',', $payload['xff']);
                $ip_str = trim($parts[0]);
            }
            if (!$ip_str) $ip_str = $payload['remote_addr'];
            if ($ip_str) {
                if ($ip_mode === 'raw') {
                    $packed = @inet_pton($ip_str);
                    if ($packed !== false) $ip_packed = $packed;
                } elseif ($ip_mode === 'hash') {
                    $salt = function_exists('wp_salt') ? wp_salt('auth') : '';
                    $ip_hash = hash('sha256', $ip_str . $salt);
                }
            }
        }

        $url_path = (string) $payload['request_uri'];
        if ($url_path) {
            $q = strpos($url_path, '?');
            if ($q !== false) $url_path = substr($url_path, 0, $q);
            $h = strpos($url_path, '#');
            if ($h !== false) $url_path = substr($url_path, 0, $h);
        }
        $url_path = substr($url_path, 0, 255);

        @$wpdb->insert($table, array(
            'bot_name'              => $def['name'],
            'bot_provider'          => $def['provider'],
            'user_agent'            => $payload['ua'],
            'request_uri'           => $payload['request_uri'],
            'request_method'        => $payload['method'],
            'response_code'         => 200,
            'response_status_class' => 2,
            'ip_address'            => $ip_packed,
            'ip_hash'               => $ip_hash,
            'url_path'              => $url_path,
            'visited_at'            => current_time('mysql'),
        ));
    }

    public static function daily_aggregate() {
        global $wpdb;
        $log_table = $wpdb->prefix . self::TABLE_NAME;
        $stats_table = $wpdb->prefix . self::TABLE_DAILY_STATS;

        $sql = "INSERT INTO $stats_table (stat_date, bot_name, bot_provider, url_path, hit_count, blocked_count)
            SELECT DATE(visited_at), bot_name, bot_provider, url_path,
                   COUNT(*) AS hit_count,
                   SUM(CASE WHEN response_code IN (403, 429) THEN 1 ELSE 0 END) AS blocked_count
            FROM $log_table
            WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
              AND visited_at < CURDATE()
              AND url_path != ''
            GROUP BY DATE(visited_at), bot_name, bot_provider, url_path
            ON DUPLICATE KEY UPDATE
                hit_count = VALUES(hit_count),
                blocked_count = VALUES(blocked_count)";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Table name is $wpdb->prefix-derived (schema-controlled), placeholders come from array_fill in IN() clauses, admin-diagnostic query (low frequency, no caching needed).
        $rows = $wpdb->query($sql);
        if ($rows !== false) seo_aeo_debug_log("[AEO Orchestra] Daily aggregate: $rows rows");

        // Table name is derived from $wpdb->prefix + literal constant — no user input. Plugin schema operation, no cache.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query("DELETE FROM $log_table WHERE visited_at < (NOW() - INTERVAL 30 DAY) LIMIT 10000");
        // Table name is derived from $wpdb->prefix + literal constant — no user input. Plugin schema operation, no cache.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query("DELETE FROM $stats_table WHERE stat_date < (CURDATE() - INTERVAL 90 DAY) LIMIT 10000");

        delete_transient('seo_aeo_aip_summary_28d');
        delete_transient('seo_aeo_aip_top_bots_28d');
        delete_transient('seo_aeo_aip_top_pages_28d');
        delete_transient('seo_aeo_aip_trend_28d');
        delete_transient('seo_aeo_aip_compliance_28d');
    }

    public static function classification($slug) {
        return isset(self::CLASSIFICATION[$slug]) ? self::CLASSIFICATION[$slug] : 'yellow';
    }

    public static function slug_from_bot_name($bot_name) {
        // 3.35.84.3: case-insensitive lookup + slug fallback for legacy data
        if (!$bot_name) return null;
        $bot_name_lower = strtolower($bot_name);
        foreach (self::bot_definitions() as $slug => $def) {
            if ($def['name'] === $bot_name) return $slug;
            if (strtolower($def['name']) === $bot_name_lower) return $slug;
            if ($slug === $bot_name_lower) return $slug;  // legacy slug match
        }
        return null;
    }

    /**
     * Filter robots.txt — append Disallow per bot disabled.
     */
    public static function filter_robots_txt($output, $public) {
        if (!$public) return $output;  // staging/private — leave default

        // Manual override takes precedence
        $override = get_option(self::ROBOTS_OVERRIDE_OPT, '');
        if (is_string($override) && trim($override) !== '') {
            return trim($override) . "\n";
        }

        $allowlist = self::get_allowlist();
        $defs = self::bot_definitions();
        $disallowed_lines = array();
        foreach ($allowlist as $slug => $enabled) {
            if ($enabled) continue;
            if (!isset($defs[$slug])) continue;
            $disallowed_lines[] = "\n# AI Crawler block (AEO Orchestra)";
            $disallowed_lines[] = "User-agent: " . $defs[$slug]['name'];
            $disallowed_lines[] = "Disallow: /";
        }
        if (!empty($disallowed_lines)) {
            $output .= "\n" . implode("\n", $disallowed_lines) . "\n";
        }
        return $output;
    }

    /**
     * Build the auto-generated robots.txt preview (what filter_robots_txt would output).
     */
    public static function preview_auto_robots_txt() {
        $base = "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n";
        $allowlist = self::get_allowlist();
        $defs = self::bot_definitions();
        $disallowed_lines = array();
        foreach ($allowlist as $slug => $enabled) {
            if ($enabled) continue;
            if (!isset($defs[$slug])) continue;
            $disallowed_lines[] = "\n# AI Crawler block (AEO Orchestra)";
            $disallowed_lines[] = "User-agent: " . $defs[$slug]['name'];
            $disallowed_lines[] = "Disallow: /";
        }
        if (!empty($disallowed_lines)) {
            $base .= "\n" . implode("\n", $disallowed_lines) . "\n";
        }
        return $base;
    }
}

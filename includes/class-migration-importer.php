<?php
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com
 *
 * 3.35.66 — Migration Importer (H feature)
 *
 * Estende il Migration Wizard con import per-post di meta keys da Yoast / RankMath / AIOSEO.
 * Quando l'utente migra da uno di questi plugin, perde altrimenti 5 anni di metadata.
 *
 * Flow:
 *   1. detect_plugins()  → quali plugin attivi + count post con meta keys popolati
 *   2. get_mapping($pl)  → tabella di mapping yoast_key → orch_key
 *   3. create_backup()   → snapshot dei valori orch_* esistenti per rollback
 *   4. import_batch()    → process N post per request (default 50, evita timeout PHP)
 *   5. rollback($id)     → ripristina valori da backup snapshot
 *
 * Backup format: WP option `_orch_migration_backup_{plugin}_{timestamp}` con array
 *   [ post_id => { orch_key => previous_value }, ... ] + auto-purge dopo 7 giorni.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Migration_Importer {

    const BACKUP_OPTION_PREFIX = '_orch_migration_backup_';
    const BACKUP_TTL_DAYS = 7;
    const MAX_BATCH_SIZE = 100;

    /**
     * Detect Yoast / RankMath / AIOSEO active + count post with populated meta keys.
     */
    public static function detect_plugins() {
        global $wpdb;
        $detected = array();

        // Yoast
        $yoast_active = defined('WPSEO_VERSION') || class_exists('WPSEO_Frontend');
        if ($yoast_active) {
            $count = self::count_posts_with_any_meta(self::yoast_source_keys());
            $detected['yoast'] = array(
                'name'           => 'Yoast SEO',
                'active'         => true,
                'posts_count'    => $count,
                'mappable_keys'  => count(self::get_mapping('yoast')),
                'version'        => defined('WPSEO_VERSION') ? WPSEO_VERSION : 'unknown',
            );
        }

        // RankMath
        $rm_active = class_exists('RankMath') || defined('RANK_MATH_VERSION');
        if ($rm_active) {
            $count = self::count_posts_with_any_meta(self::rankmath_source_keys());
            $detected['rankmath'] = array(
                'name'           => 'RankMath SEO',
                'active'         => true,
                'posts_count'    => $count,
                'mappable_keys'  => count(self::get_mapping('rankmath')),
                'version'        => defined('RANK_MATH_VERSION') ? RANK_MATH_VERSION : 'unknown',
            );
        }

        // AIOSEO
        $aioseo_active = defined('AIOSEO_VERSION') || class_exists('AIOSEO\\Plugin\\AIOSEO');
        if ($aioseo_active) {
            $count = self::count_posts_with_any_meta(self::aioseo_source_keys());
            // AIOSEO 4+ usa custom table wp_aioseo_posts
            $aioseo_table = $wpdb->prefix . 'aioseo_posts';
            $aioseo_table_count = 0;
            // Table name is derived from $wpdb->prefix + literal constant — no user input. Plugin schema operation, no cache.
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
            if ($wpdb->get_var("SHOW TABLES LIKE '$aioseo_table'") === $aioseo_table) {
                // Table name is derived from $wpdb->prefix + literal constant — no user input. Plugin schema operation, no cache.
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
                $aioseo_table_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $aioseo_table WHERE title <> '' OR description <> ''");
                $count = max($count, $aioseo_table_count);
            }
            $detected['aioseo'] = array(
                'name'           => 'All in One SEO',
                'active'         => true,
                'posts_count'    => $count,
                'mappable_keys'  => count(self::get_mapping('aioseo')),
                'version'        => defined('AIOSEO_VERSION') ? AIOSEO_VERSION : 'unknown',
                'has_custom_table' => $aioseo_table_count > 0,
            );
        }

        return $detected;
    }

    /**
     * Returns array of source meta_key => orch meta_key for the given source plugin.
     */
    public static function get_mapping($plugin) {
        switch ($plugin) {
            case 'yoast':
                return array(
                    '_yoast_wpseo_title'                  => '_orch_meta_title',
                    '_yoast_wpseo_metadesc'               => '_orch_meta_desc_override',
                    '_yoast_wpseo_focuskw'                => '_orch_focus_keyword',
                    '_yoast_wpseo_canonical'              => '_orch_canonical_override',
                    '_yoast_wpseo_meta-robots-noindex'    => '_orch_robots_index',  // value translation
                    '_yoast_wpseo_meta-robots-nofollow'   => '_orch_robots_follow', // value translation
                    '_yoast_wpseo_opengraph-title'        => '_orch_og_title',
                    '_yoast_wpseo_opengraph-description'  => '_orch_og_description',
                    '_yoast_wpseo_opengraph-image'        => '_orch_og_image_override',
                    '_yoast_wpseo_twitter-title'          => '_orch_twitter_title',
                    '_yoast_wpseo_twitter-description'    => '_orch_twitter_description',
                );
            case 'rankmath':
                return array(
                    'rank_math_title'              => '_orch_meta_title',
                    'rank_math_description'        => '_orch_meta_desc_override',
                    'rank_math_focus_keyword'      => '_orch_focus_keyword',
                    'rank_math_canonical_url'      => '_orch_canonical_override',
                    'rank_math_robots'             => '_orch_robots_directive',  // serialized → parse
                    'rank_math_facebook_title'     => '_orch_og_title',
                    'rank_math_facebook_description' => '_orch_og_description',
                    'rank_math_facebook_image'     => '_orch_og_image_override',
                    'rank_math_twitter_title'      => '_orch_twitter_title',
                    'rank_math_twitter_description'=> '_orch_twitter_description',
                );
            case 'aioseo':
                return array(
                    '_aioseo_title'        => '_orch_meta_title',
                    '_aioseo_description'  => '_orch_meta_desc_override',
                    '_aioseo_keywords'     => '_orch_focus_keyword',
                );
            default:
                return array();
        }
    }

    private static function yoast_source_keys() { return array_keys(self::get_mapping('yoast')); }
    private static function rankmath_source_keys() { return array_keys(self::get_mapping('rankmath')); }
    private static function aioseo_source_keys() { return array_keys(self::get_mapping('aioseo')); }

    /**
     * Count post.ID con almeno una delle meta keys popolata (non vuota).
     */
    private static function count_posts_with_any_meta($keys) {
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table names from $wpdb->prefix (schema-controlled, MySQL prepared statements do not support identifiers as placeholders); IN() clause placeholders dynamically built via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern).
        global $wpdb;
        if (empty($keys)) return 0;
        $placeholders = implode(',', array_fill(0, count($keys), '%s'));
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
        $sql = $wpdb->prepare(
            "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key IN ($placeholders) AND meta_value <> ''",
            $keys
        );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
        return (int) $wpdb->get_var($sql);
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
    }

    /**
     * Yoast variable replacement (%%title%% → {{title}}, etc.).
     */
    private static function translate_yoast_vars($s) {
        if (!is_string($s) || $s === '') return $s;
        $map = array(
            '%%title%%'        => '{{title}}',
            '%%page%%'         => '{{page}}',
            '%%sep%%'          => '{{sep}}',
            '%%sitename%%'     => '{{site_name}}',
            '%%site_name%%'    => '{{site_name}}',
            '%%sitedesc%%'     => '{{site_description}}',
            '%%category%%'     => '{{category}}',
            '%%tag%%'          => '{{tag}}',
            '%%date%%'         => '{{date}}',
            '%%modified%%'     => '{{modified}}',
            '%%author%%'       => '{{author}}',
            '%%searchphrase%%' => '{{search}}',
            '%%excerpt%%'      => '{{excerpt}}',
            '%%primary_category%%' => '{{category}}',
        );
        return strtr($s, $map);
    }

    /**
     * Translate value when source-specific encoding differs from Orchestra.
     * Returns null if value should be skipped (e.g. empty after translation).
     */
    private static function translate_value($plugin, $source_key, $value) {
        if ($value === '' || $value === null) return null;

        // Yoast robots noindex: 1=noindex, 2=index, 0=default
        if ($plugin === 'yoast' && $source_key === '_yoast_wpseo_meta-robots-noindex') {
            $v = (string) $value;
            if ($v === '1') return '0';   // noindex
            if ($v === '2') return '1';   // index
            return null;                   // 0 = default → skip (no override needed)
        }
        // Yoast nofollow: 1=nofollow → Orchestra _orch_robots_follow=0; 0=follow → skip
        if ($plugin === 'yoast' && $source_key === '_yoast_wpseo_meta-robots-nofollow') {
            $v = (string) $value;
            if ($v === '1') return '0';   // nofollow
            return null;
        }
        // RankMath robots: serialized array like ['index','noindex','nofollow','noarchive']
        if ($plugin === 'rankmath' && $source_key === 'rank_math_robots') {
            $unserialized = @maybe_unserialize($value);
            if (!is_array($unserialized)) return null;
            $directives = array_map('strtolower', $unserialized);
            return implode(',', array_unique($directives));
        }

        // Default: text fields → translate yoast variables (no-op for non-yoast)
        if ($plugin === 'yoast') {
            return self::translate_yoast_vars((string) $value);
        }
        return $value;
    }

    /**
     * STEP 1 — Create backup before import. Snapshots existing _orch_* values for the
     * posts that will be touched, so rollback can restore exact prior state.
     *
     * Returns backup_id (option key suffix).
     */
    public static function create_backup($plugin) {
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table names from $wpdb->prefix (schema-controlled, MySQL prepared statements do not support identifiers as placeholders); IN() clause placeholders dynamically built via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern).
        global $wpdb;
        $mapping = self::get_mapping($plugin);
        if (empty($mapping)) return false;

        $backup_id = (string) time();
        $option_key = self::BACKUP_OPTION_PREFIX . $plugin . '_' . $backup_id;

        // Get post IDs that have any source meta key populated
        $source_keys = array_keys($mapping);
        $placeholders = implode(',', array_fill(0, count($source_keys), '%s'));
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
        $sql = $wpdb->prepare(
            "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ($placeholders) AND meta_value <> ''",
            $source_keys
        );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
        $post_ids = $wpdb->get_col($sql);
        if (empty($post_ids)) return false;

        // Snapshot existing _orch_* values for these posts
        $orch_keys = array_values(array_unique($mapping));
        $snapshot = array();
        foreach ($post_ids as $pid) {
            $pid = (int) $pid;
            $row = array();
            foreach ($orch_keys as $orch_key) {
                $existing = get_post_meta($pid, $orch_key, true);
                $row[$orch_key] = $existing;
            }
            $snapshot[$pid] = $row;
        }

        update_option($option_key, array(
            'plugin'    => $plugin,
            'created_at' => time(),
            'expires_at' => time() + (self::BACKUP_TTL_DAYS * DAY_IN_SECONDS),
            'post_count' => count($snapshot),
            'snapshot'  => $snapshot,
        ), false);

        // Auto-purge old backups
        self::purge_expired_backups();

        return $backup_id;
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
    }

    /**
     * STEP 2 — Import a batch of posts. Returns offset for next batch, or null if done.
     *
     * @param string $plugin       'yoast' | 'rankmath' | 'aioseo'
     * @param int    $offset       starting post offset
     * @param int    $limit        batch size (default 50)
     * @param array  $opts         {
     *   bool override_existing  — if true, overwrite Orchestra meta even when present
     *   array skip_keys         — source keys to skip (user-deselected in UI)
     * }
     * @return array {
     *   processed_in_batch: int,
     *   total_processed: int,
     *   total_to_process: int,
     *   next_offset: int|null,
     *   last_title: string,
     *   keys_written: { orch_key => count, ... }
     * }
     */
    public static function import_batch($plugin, $offset = 0, $limit = 50, $opts = array()) {
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table names from $wpdb->prefix (schema-controlled, MySQL prepared statements do not support identifiers as placeholders); IN() clause placeholders dynamically built via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern).
        global $wpdb;
        $mapping = self::get_mapping($plugin);
        if (empty($mapping)) return array('error' => 'unknown_plugin');

        $limit = max(1, min(self::MAX_BATCH_SIZE, (int) $limit));
        $offset = max(0, (int) $offset);
        $override = !empty($opts['override_existing']);
        $skip_keys = isset($opts['skip_keys']) && is_array($opts['skip_keys']) ? array_flip($opts['skip_keys']) : array();

        // Filter mapping
        $effective_mapping = array();
        foreach ($mapping as $src => $dst) {
            if (!isset($skip_keys[$src])) $effective_mapping[$src] = $dst;
        }

        // Count total posts to process
        $source_keys = array_keys($effective_mapping);
        if (empty($source_keys)) return array('error' => 'no_keys_after_skip');

        $placeholders = implode(',', array_fill(0, count($source_keys), '%s'));
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
        $sql_count = $wpdb->prepare(
            "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key IN ($placeholders) AND meta_value <> ''",
            $source_keys
        );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
        $total = (int) $wpdb->get_var($sql_count);

        // Fetch batch of post IDs ordered by ID for deterministic pagination
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
        $sql_batch = $wpdb->prepare(
            "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ($placeholders) AND meta_value <> '' ORDER BY post_id ASC LIMIT %d OFFSET %d",
            array_merge($source_keys, array($limit, $offset))
        );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
        $post_ids = $wpdb->get_col($sql_batch);

        $processed = 0;
        $keys_written = array();
        $last_title = '';
        $samples = array();

        foreach ($post_ids as $pid) {
            $pid = (int) $pid;
            $post = get_post($pid);
            if (!$post) continue;

            // Skip CPTs that are NOT public (privacy: don't migrate hidden CPTs)
            $pt = $post->post_type;
            $pt_obj = get_post_type_object($pt);
            if ($pt_obj && empty($pt_obj->public)) continue;

            $written_for_post = array();
            foreach ($effective_mapping as $src_key => $dst_key) {
                $src_value = get_post_meta($pid, $src_key, true);
                if ($src_value === '' || $src_value === null) continue;

                $translated = self::translate_value($plugin, $src_key, $src_value);
                if ($translated === null || $translated === '') continue;

                if (!$override) {
                    $existing = get_post_meta($pid, $dst_key, true);
                    if ($existing !== '' && $existing !== null) continue;
                }

                update_post_meta($pid, $dst_key, $translated);
                if (!isset($keys_written[$dst_key])) $keys_written[$dst_key] = 0;
                $keys_written[$dst_key]++;
                $written_for_post[$dst_key] = $translated;
            }

            if (!empty($written_for_post)) $processed++;
            $last_title = (string) $post->post_title;

            // Collect 5 random-ish samples on the FIRST batch only (offset == 0)
            if ($offset === 0 && count($samples) < 5 && !empty($written_for_post)) {
                $samples[] = array(
                    'post_id' => $pid,
                    'title'   => $last_title,
                    'changes' => $written_for_post,
                );
            }
        }

        $next_offset = ($offset + $limit) >= $total ? null : ($offset + $limit);

        return array(
            'processed_in_batch' => $processed,
            'total_processed'    => $offset + count($post_ids),
            'total_to_process'   => $total,
            'next_offset'        => $next_offset,
            'last_title'         => $last_title,
            'keys_written'       => $keys_written,
            'samples'            => $samples,
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
    }

    /**
     * STEP 3 — Rollback: restore _orch_* values from backup snapshot.
     */
    public static function rollback($plugin, $backup_id) {
        $option_key = self::BACKUP_OPTION_PREFIX . $plugin . '_' . $backup_id;
        $backup = get_option($option_key, false);
        if (!is_array($backup) || empty($backup['snapshot'])) {
            return array('error' => 'backup_not_found');
        }

        $restored = 0;
        foreach ($backup['snapshot'] as $pid => $row) {
            if (!is_array($row)) continue;
            foreach ($row as $orch_key => $original_value) {
                if ($original_value === '' || $original_value === null) {
                    delete_post_meta((int) $pid, $orch_key);
                } else {
                    update_post_meta((int) $pid, $orch_key, $original_value);
                }
            }
            $restored++;
        }

        // Mark backup as consumed (don't delete — keep for audit)
        $backup['rolled_back_at'] = time();
        update_option($option_key, $backup, false);

        return array(
            'ok'           => true,
            'posts_restored' => $restored,
        );
    }

    /**
     * List backups (for UI to show "Available rollbacks").
     */
    public static function list_backups() {
        global $wpdb;
        $like = $wpdb->esc_like(self::BACKUP_OPTION_PREFIX) . '%';
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
        $rows = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name DESC LIMIT 50",
                $like
            ),
            'ARRAY_A'
        );
        $backups = array();
        foreach ($rows as $r) {
            $value = @maybe_unserialize($r['option_value']);
            if (!is_array($value)) continue;
            $name = $r['option_name'];
            $suffix = substr($name, strlen(self::BACKUP_OPTION_PREFIX));
            $parts = explode('_', $suffix, 2);
            $plugin = isset($parts[0]) ? $parts[0] : '?';
            $bid    = isset($parts[1]) ? $parts[1] : '?';
            $backups[] = array(
                'option_name'   => $name,
                'plugin'        => $plugin,
                'backup_id'     => $bid,
                'created_at'    => isset($value['created_at']) ? (int) $value['created_at'] : 0,
                'expires_at'    => isset($value['expires_at']) ? (int) $value['expires_at'] : 0,
                'post_count'    => isset($value['post_count']) ? (int) $value['post_count'] : 0,
                'rolled_back_at' => isset($value['rolled_back_at']) ? (int) $value['rolled_back_at'] : 0,
            );
        }
        return $backups;
    }

    /**
     * Auto-purge backups older than BACKUP_TTL_DAYS days.
     */
    public static function purge_expired_backups() {
        $backups = self::list_backups();
        $now = time();
        foreach ($backups as $b) {
            if ($b['expires_at'] && $b['expires_at'] < $now && empty($b['rolled_back_at'])) {
                delete_option($b['option_name']);
            }
        }
    }

    /**
     * WPML/Polylang detection — for warning display.
     */
    public static function detect_multilingual() {
        $plugins = array();
        if (defined('ICL_SITEPRESS_VERSION')) $plugins[] = 'WPML';
        if (defined('POLYLANG_VERSION') || function_exists('pll_languages_list')) $plugins[] = 'Polylang';
        return $plugins;
    }
}

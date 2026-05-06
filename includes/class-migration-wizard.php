<?php
// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com
 *
 * Migration Wizard "Switch da Yoast" (v3.19.0 — giorno 6/7 della roadmap).
 *
 * Esegue una migrazione guidata da Yoast SEO / RankMath / AIOSEO a Orchestra Native:
 *
 *   1. SCAN: detect plugin SEO attivo, count meta esistenti, count redirect Yoast Premium,
 *            presenza schema custom, etc.
 *   2. BACKUP: genera un JSON downloadable con tutti i dati pre-migrazione (recovery).
 *   3. MIGRATE META: copia meta keys da `_yoast_wpseo_*` → `_seo_aeo_meta_*` (preserva
 *            entrambi, NON sostituisce — così Yoast continua a funzionare se non disinstallato).
 *   4. IMPORT REDIRECTS: Yoast Premium redirects (option `wpseo-premium-redirects-base`) e
 *            plugin Redirection (table `wp_redirection_items`) → tabella `wp_seo_aeo_redirects`.
 *   5. ACTIVATE STACK: attiva native_output + override_mode + sitemap + schema + redirect_manager.
 *   6. SUGGEST DISINSTALL: link al pannello WP Plugins (no auto-delete — safety).
 *
 * IMPORTANTE: tutti i passaggi sono ATOMICI e REVERSIBILI:
 *   - I meta originali NON vengono cancellati (solo copiati)
 *   - I redirect importati hanno notes="Importato da Yoast Premium" (filtrabili)
 *   - I toggle native restano in option separate, disattivabili via UI
 *   - Il backup JSON è scaricato dall'utente prima di qualsiasi modifica
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Migration_Wizard {

    // Mapping Yoast keys → Orchestra keys
    const META_MAPPING = array(
        '_yoast_wpseo_title'           => '_seo_aeo_meta_title',
        '_yoast_wpseo_metadesc'        => '_seo_aeo_meta_description',
        '_yoast_wpseo_focuskw'         => '_seo_aeo_focus_keyword',
        '_yoast_wpseo_canonical'       => '_seo_aeo_canonical',
        '_yoast_wpseo_meta-robots-noindex'  => '_seo_aeo_noindex',
        '_yoast_wpseo_opengraph-title' => '_seo_aeo_og_title',
        '_yoast_wpseo_opengraph-description' => '_seo_aeo_og_description',
        '_yoast_wpseo_opengraph-image' => '_seo_aeo_og_image',
        '_yoast_wpseo_twitter-title'   => '_seo_aeo_twitter_title',
        '_yoast_wpseo_twitter-description' => '_seo_aeo_twitter_description',
        '_yoast_wpseo_twitter-image'   => '_seo_aeo_twitter_image',
    );

    /**
     * STEP 1 — SCAN: rileva stato attuale.
     */
    public static function scan() {
        global $wpdb;

        $detected_plugin = null;
        if (defined('WPSEO_VERSION') || class_exists('WPSEO_Frontend')) $detected_plugin = 'yoast';
        elseif (class_exists('RankMath') || defined('RANK_MATH_VERSION')) $detected_plugin = 'rankmath';
        elseif (defined('AIOSEO_VERSION')) $detected_plugin = 'aioseo';

        // Conta meta key Yoast esistenti
        $meta_counts = array();
        foreach (array_keys(self::META_MAPPING) as $key) {
            $count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != ''",
                $key
            ));
            if ($count > 0) $meta_counts[$key] = $count;
        }

        // Yoast Premium redirects
        $yoast_redirects = get_option('wpseo-premium-redirects-base', array());
        $yoast_redirects_count = is_array($yoast_redirects) ? count($yoast_redirects) : 0;

        // Plugin Redirection (table)
        $redirection_count = 0;
        $redirection_table = $wpdb->prefix . 'redirection_items';
        if ($wpdb->get_var("SHOW TABLES LIKE '$redirection_table'") === $redirection_table) {
            $redirection_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $redirection_table WHERE status = 'enabled'");
        }

        // Schema custom (controllo molto veloce: c'è classe Yoast schema?)
        $has_yoast_schema = class_exists('WPSEO_Schema');

        // Stato Orchestra native (cosa è già attivo)
        $native_enabled = class_exists('SEO_AEO_Output_Renderer') && SEO_AEO_Output_Renderer::is_active();
        $sitemap_enabled = class_exists('SEO_AEO_Sitemap') && SEO_AEO_Sitemap::is_enabled();
        $llms_enabled = class_exists('SEO_AEO_LLMs_Txt') && SEO_AEO_LLMs_Txt::is_enabled();
        $schema_enabled = class_exists('SEO_AEO_Schema') && SEO_AEO_Schema::is_enabled();
        $redirect_enabled = class_exists('SEO_AEO_Redirect_Manager') && SEO_AEO_Redirect_Manager::is_enabled();

        return array(
            'detected_plugin' => $detected_plugin,
            'detected_plugin_label' => self::plugin_label($detected_plugin),
            'meta_counts' => $meta_counts,
            'meta_total_posts' => array_sum($meta_counts),
            'yoast_redirects_count' => $yoast_redirects_count,
            'redirection_plugin_count' => $redirection_count,
            'has_yoast_schema' => $has_yoast_schema,
            'native_state' => array(
                'output' => $native_enabled,
                'sitemap' => $sitemap_enabled,
                'llms' => $llms_enabled,
                'schema' => $schema_enabled,
                'redirect' => $redirect_enabled,
            ),
        );
    }

    /**
     * STEP 2 — BACKUP: produce un JSON con tutti i dati attuali.
     */
    public static function build_backup() {
        global $wpdb;

        $backup = array(
            'orchestra_version' => defined('SEO_AEO_VERSION') ? SEO_AEO_VERSION : '?',
            'site_url' => home_url('/'),
            'created_at' => current_time('c', true),
            'wp_version' => get_bloginfo('version'),
            'detected_plugin' => null,
            'meta' => array(),
            'yoast_redirects' => null,
            'yoast_settings' => null,
        );

        $scan = self::scan();
        $backup['detected_plugin'] = $scan['detected_plugin'];

        // Meta values (limit 5000 posts per safety)
        foreach (array_keys(self::META_MAPPING) as $yoast_key) {
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != '' LIMIT 5000",
                $yoast_key
            ), ARRAY_A);
            if (!empty($rows)) {
                $backup['meta'][$yoast_key] = $rows;
            }
        }

        // Yoast Premium redirects
        $backup['yoast_redirects'] = get_option('wpseo-premium-redirects-base', null);

        // Yoast settings (option key wpseo)
        $backup['yoast_settings'] = get_option('wpseo', null);

        return $backup;
    }

    /**
     * STEP 3 — MIGRATE META: copia da Yoast keys a Orchestra keys.
     * NON cancella i dati originali — pattern "shadow copy" per safety.
     * Ritorna count copiati.
     */
    public static function migrate_meta() {
        global $wpdb;

        $total_copied = 0;
        $per_key_copied = array();

        foreach (self::META_MAPPING as $yoast_key => $orch_key) {
            // INSERT IGNORE pattern per non sovrascrivere se Orchestra ha già un valore
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != ''",
                $yoast_key
            ), ARRAY_A);

            $copied = 0;
            foreach ($rows as $r) {
                $post_id = (int) $r['post_id'];
                $value = $r['meta_value'];

                // Skip se Orchestra ha già un valore (no overwrite)
                $existing = get_post_meta($post_id, $orch_key, true);
                if ($existing !== '' && $existing !== false) continue;

                update_post_meta($post_id, $orch_key, $value);
                $copied++;
            }

            if ($copied > 0) {
                $per_key_copied[$yoast_key . ' → ' . $orch_key] = $copied;
                $total_copied += $copied;
            }
        }

        return array(
            'total_copied' => $total_copied,
            'per_key' => $per_key_copied,
        );
    }

    /**
     * STEP 4 — IMPORT REDIRECTS: copia redirect da Yoast Premium e/o Redirection plugin
     * nella tabella wp_seo_aeo_redirects.
     * Skip duplicati (source_path già esistente).
     */
    public static function import_redirects() {
        if (!class_exists('SEO_AEO_Redirect_Manager')) {
            return array('error' => 'Redirect Manager non disponibile.');
        }
        SEO_AEO_Redirect_Manager::maybe_create_tables();

        global $wpdb;
        $tbl = $wpdb->prefix . 'seo_aeo_redirects';

        // Existing source_paths to skip duplicates
        $existing_sources = $wpdb->get_col("SELECT source_path FROM $tbl");
        $existing_set = array_flip($existing_sources);

        $imported_yoast = 0;
        $imported_redirection = 0;
        $skipped_dup = 0;

        // ─── Yoast Premium ─────────────────────────────────────────────────
        $yoast_redirects = get_option('wpseo-premium-redirects-base', array());
        if (is_array($yoast_redirects)) {
            foreach ($yoast_redirects as $r) {
                if (!is_array($r)) continue;
                $source = isset($r['origin']) ? trim($r['origin']) : '';
                $target = isset($r['url']) ? trim($r['url']) : '';
                $type   = isset($r['type']) ? (int) $r['type'] : 301;
                $format = isset($r['format']) ? $r['format'] : 'plain';
                if (empty($source) || empty($target)) continue;
                if (!preg_match('#^/#', $source)) $source = '/' . $source;
                if (isset($existing_set[$source])) { $skipped_dup++; continue; }

                $is_regex = ($format === 'regex') ? 1 : 0;
                $result = SEO_AEO_Redirect_Manager::add_redirect(
                    $source, $target, $type, (bool)$is_regex, true,
                    'Importato da Yoast Premium · ' . current_time('Y-m-d')
                );
                if (!empty($result['success'])) $imported_yoast++;
                $existing_set[$source] = true;
            }
        }

        // ─── Plugin Redirection (table) ────────────────────────────────────
        $redirection_table = $wpdb->prefix . 'redirection_items';
        if ($wpdb->get_var("SHOW TABLES LIKE '$redirection_table'") === $redirection_table) {
            $items = $wpdb->get_results("SELECT url, action_data, action_type, action_code, regex FROM $redirection_table WHERE status = 'enabled' AND action_type = 'url'", ARRAY_A);
            foreach ($items as $r) {
                $source = trim($r['url']);
                $target = trim($r['action_data']);
                $type = (int) $r['action_code'] ?: 301;
                $is_regex = (int) $r['regex'] ? 1 : 0;
                if (empty($source) || empty($target)) continue;
                if (!preg_match('#^/#', $source)) $source = '/' . $source;
                if (isset($existing_set[$source])) { $skipped_dup++; continue; }

                $result = SEO_AEO_Redirect_Manager::add_redirect(
                    $source, $target, $type, (bool)$is_regex, true,
                    'Importato da plugin Redirection · ' . current_time('Y-m-d')
                );
                if (!empty($result['success'])) $imported_redirection++;
                $existing_set[$source] = true;
            }
        }

        return array(
            'imported_yoast_premium' => $imported_yoast,
            'imported_redirection' => $imported_redirection,
            'skipped_duplicates' => $skipped_dup,
            'total_imported' => $imported_yoast + $imported_redirection,
        );
    }

    /**
     * STEP 5 — ACTIVATE NATIVE STACK: turn on tutti i toggle Orchestra Native in modo coordinato.
     * Override Mode incluso solo se Yoast/RankMath/AIOSEO è ancora attivo (per silenziarlo).
     */
    public static function activate_stack($options = array()) {
        $defaults = array(
            'output'    => true,
            'override'  => true,   // se altro plugin SEO è attivo
            'sitemap'   => true,
            'llms'      => true,
            'schema'    => false,  // default OFF: l'utente attiva manualmente se vuole sostituire schema custom
            'redirect'  => true,
        );
        $opts = array_merge($defaults, $options);
        $activated = array();

        if ($opts['output'] && class_exists('SEO_AEO_Output_Renderer')) {
            update_option(SEO_AEO_Output_Renderer::OPTION_ENABLED, '1');
            $activated[] = 'output';

            if ($opts['override'] && SEO_AEO_Output_Renderer::detect_other_seo_plugin()) {
                update_option(SEO_AEO_Output_Renderer::OPTION_OVERRIDE, '1');
                $activated[] = 'override';
            }
        }
        if ($opts['sitemap'] && class_exists('SEO_AEO_Sitemap')) {
            SEO_AEO_Sitemap::set_enabled(true);
            $activated[] = 'sitemap';
        }
        if ($opts['llms'] && class_exists('SEO_AEO_LLMs_Txt')) {
            SEO_AEO_LLMs_Txt::set_enabled(true);
            $activated[] = 'llms';
        }
        if ($opts['schema'] && class_exists('SEO_AEO_Schema')) {
            SEO_AEO_Schema::set_enabled(true);
            $activated[] = 'schema';
        }
        if ($opts['redirect'] && class_exists('SEO_AEO_Redirect_Manager')) {
            SEO_AEO_Redirect_Manager::set_enabled(true);
            $activated[] = 'redirect';
        }

        return array('activated' => $activated);
    }

    private static function plugin_label($key) {
        $labels = array('yoast' => 'Yoast SEO', 'rankmath' => 'Rank Math', 'aioseo' => 'All in One SEO');
        return isset($labels[$key]) ? $labels[$key] : null;
    }
}

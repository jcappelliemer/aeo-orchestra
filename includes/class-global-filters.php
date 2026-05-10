<?php
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com. All rights reserved.
 * Unauthorized copying, redistribution or resale is strictly prohibited.
 */

/**
 * SEO_AEO_Global_Filters — single source of truth for filters that apply
 * across ALL native outputs (head meta, sitemap.xml, llms.txt, schema.org).
 *
 * Introduced in 3.35.49-beta as part of Stage 1.5 architectural refactor.
 *
 * Settings stored in WP option `seo_aeo_global_filters`:
 *   {
 *     post_types:       string[]   // post type slugs to include; [] = all public minus blocked
 *     exclude_patterns: string     // newline-separated, substring match on URL OR slug
 *     respect_noindex:  bool       // honor Yoast/RankMath/AIOSEO/native noindex meta
 *     schema_version:   int        // migration tracking
 *   }
 *
 * Per-output classes (class-llms-txt, class-sitemap, class-output-renderer,
 * class-schema) call ::is_post_globally_excluded($post) for the shared logic
 * and may layer their own override patterns on top.
 *
 * Migration: maybe_migrate() reads the legacy 3.35.47 options
 * (seo_aeo_llms_exclude_patterns + seo_aeo_llms_post_types) once, copies
 * them into the new option, and bumps schema_version. Idempotent.
 * Old keys are LEFT in place for rollback safety.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Global_Filters {

    const OPTION_KEY      = 'seo_aeo_global_filters';
    const SCHEMA_VERSION  = 2;

    // Legacy 3.35.47 option keys — read once during migration, then orphaned.
    const LEGACY_OPT_PATTERNS = 'seo_aeo_llms_exclude_patterns';
    const LEGACY_OPT_PT       = 'seo_aeo_llms_post_types';

    // Per-output settings (3.35.51 — Stage 1.5 Addendum 2)
    const OPTION_SITEMAP_SETTINGS    = 'seo_aeo_sitemap_settings';
    const OPTION_LLMS_FULL_SETTINGS  = 'seo_aeo_llms_full_settings';
    const OPTION_SCHEMA_SETTINGS     = 'seo_aeo_schema_settings';

    // Default post types per output (different intent: Google vs AI)
    const LLMS_FULL_DEFAULT_POST_TYPES = array('post', 'page');

    // Valid schema modes
    const SCHEMA_MODES = array('auto', 'replace', 'augment', 'off');

    // ============================================================
    // Hardcoded blocklists (3.35.47, moved here in 3.35.49)
    // ============================================================

    public static function hardcoded_slug_blocklist() {
        return array(
            'battle-plan',
            'line-up',
            'chiusura-lavori',
            'false-parent',
            'callback',
            'thank-you',
            'thankyou',
            'anagrafica-tributaria',
            'wp-login',
            'wp-admin',
            'cart',
            'my-account',
        );
    }

    public static function hardcoded_slug_prefix_blocklist() {
        return array('false-', 'test-', 'temp-', 'draft-', 'template-');
    }

    public static function hardcoded_post_type_blocklist() {
        return array(
            'elementor_library',
            'e-floating-buttons',
            'elementor_font',
            'elementor_icons',
            'elementor_snippet',
            'attachment',
            'revision',
            'nav_menu_item',
            'custom_css',
            'customize_changeset',
            'oembed_cache',
            'user_request',
            'wp_block',
            'wp_template',
            'wp_template_part',
            'wp_global_styles',
            'wp_navigation',
            'shop_order',
            'shop_order_refund',
            'product_variation',
        );
    }

    public static function hardcoded_url_blocklist() {
        return array(
            'elementor_library=',
            '?elementor=',
            '/elementor/',
            'wc-ajax=',
            '/wp-admin/',
            '/wp-login',
        );
    }

    // ============================================================
    // Settings get/set + migration
    // ============================================================

    public static function defaults() {
        return array(
            // null = user has not actively configured → fallback to all public minus blocked.
            // [] (empty array) = user explicitly chose "include nothing".
            'post_types'       => null,
            'exclude_patterns' => '',
            'respect_noindex'  => true,
            'schema_version'   => self::SCHEMA_VERSION,
        );
    }

    /**
     * Returns merged settings (defaults overlaid with stored). Always-array safe.
     */
    public static function get_settings() {
        $stored = get_option(self::OPTION_KEY, array());
        if (!is_array($stored)) $stored = array();
        return array_merge(self::defaults(), $stored);
    }

    /**
     * Persist a partial update. Validates types + sanitizes. Returns the merged
     * post-save settings array.
     */
    public static function update_settings($payload) {
        if (!is_array($payload)) $payload = array();
        $current = self::get_settings();

        // post_types whitelist — keep only registered public types not in blocklist
        if (array_key_exists('post_types', $payload)) {
            // Literal-empty semantics: user-sent [] persists as [] (= include nothing),
            // distinct from null (= "no choice, fallback to all public").
            $list = is_array($payload['post_types']) ? $payload['post_types'] : array();
            $post_types_user_set_check = true; // marker for grep + future reference
            $valid   = get_post_types(array('public' => true), 'names');
            $blocked = array_flip(self::hardcoded_post_type_blocklist());
            $clean = array();
            foreach ($list as $pt) {
                $pt = is_string($pt) ? sanitize_key($pt) : '';
                if ($pt === '') continue;
                if (!isset($valid[$pt])) continue;
                if (isset($blocked[$pt])) continue;
                $clean[] = $pt;
            }
            $current['post_types'] = array_values(array_unique($clean));
        }

        if (array_key_exists('exclude_patterns', $payload)) {
            $raw = (string) $payload['exclude_patterns'];
            $raw = wp_check_invalid_utf8($raw);
            $raw = wp_strip_all_tags($raw);
            $raw = preg_replace('/\r\n|\r/', "\n", $raw);
            $current['exclude_patterns'] = $raw;
        }

        if (array_key_exists('respect_noindex', $payload)) {
            $current['respect_noindex'] = (bool) $payload['respect_noindex'];
        }

        $current['schema_version'] = self::SCHEMA_VERSION;
        update_option(self::OPTION_KEY, $current);

        // Bust LLMs cache transients so the next /llms.txt reflects the new filters.
        if (class_exists('SEO_AEO_LLMs_Txt') && method_exists('SEO_AEO_LLMs_Txt', 'flush_all_cache')) {
            SEO_AEO_LLMs_Txt::flush_all_cache();
        }

        return $current;
    }

    /**
     * Idempotent migration. Runs at most once: subsequent calls return early
     * because schema_version is already at SCHEMA_VERSION.
     *
     * Safe behavior: leaves legacy options in place so a rollback to 3.35.48
     * still finds its data. They can be cleaned up in a later release.
     */
    public static function maybe_migrate() {
        $stored = get_option(self::OPTION_KEY, null);
        $current_v = is_array($stored) && isset($stored['schema_version']) ? (int) $stored['schema_version'] : 0;
        if ($current_v >= self::SCHEMA_VERSION) {
            return false;
        }

        $new = is_array($stored) ? array_merge(self::defaults(), $stored) : self::defaults();

        // ===== v0 (pre-3.35.49) → first run =====
        // Pull legacy 3.35.47 keys into the global doc if not already there.
        if ($current_v < 1) {
            $legacy_patterns = get_option(self::LEGACY_OPT_PATTERNS, '');
            if (is_string($legacy_patterns) && $legacy_patterns !== '' && empty($new['exclude_patterns'])) {
                $new['exclude_patterns'] = $legacy_patterns;
            }
            $legacy_pt = get_option(self::LEGACY_OPT_PT, null);
            if (is_array($legacy_pt) && !empty($legacy_pt) && $new['post_types'] === null) {
                $clean = array();
                foreach ($legacy_pt as $pt) {
                    if (is_string($pt) && $pt !== '') $clean[] = sanitize_key($pt);
                }
                if (!empty($clean)) {
                    $new['post_types'] = array_values(array_unique($clean));
                }
            }
        }

        // ===== v1 (3.35.49–3.35.50) → v2 (3.35.51) =====
        // Section 1 'Filtri globali' eliminata. Il setting post_types che era
        // globale viene migrato all'opzione Sitemap.xml (default più sensato:
        // sitemap = ALL public, e l'utente che aveva una whitelist globale
        // verosimilmente la voleva valida per Google).
        if ($current_v < 2) {
            $existing_pt = isset($new['post_types']) ? $new['post_types'] : null;
            if (is_array($existing_pt) && !empty($existing_pt)) {
                $current_sitemap = get_option(self::OPTION_SITEMAP_SETTINGS, array());
                if (!is_array($current_sitemap)) $current_sitemap = array();
                // Don't overwrite if user already configured sitemap explicitly
                if (!array_key_exists('post_types', $current_sitemap) || $current_sitemap['post_types'] === null) {
                    $current_sitemap['post_types'] = array_values($existing_pt);
                    update_option(self::OPTION_SITEMAP_SETTINGS, $current_sitemap);
                    seo_aeo_debug_log('[SEO_AEO] v2 migration: copied global.post_types → sitemap.post_types: ' . wp_json_encode($existing_pt));
                }
            }
            // post_types in $new is no longer used as a global selector but kept
            // for rollback safety — generators ignore it from v2 onward.
        }

        $new['schema_version'] = self::SCHEMA_VERSION;
        update_option(self::OPTION_KEY, $new);

        seo_aeo_debug_log('[SEO_AEO] Global filters migrated to schema v' . self::SCHEMA_VERSION);

        return true;
    }

    // ============================================================
    // Per-output post type whitelists (Stage 1.5 Addendum 2)
    // ============================================================

    /**
     * Internal: filter a list to the currently registered public post types,
     * minus the hardcoded blocklist. Used by all per-output getters to ensure
     * stale stored slugs (CPT was deactivated) don't leak through.
     */
    private static function filter_to_valid_pt($list) {
        if (!is_array($list)) return array();
        $public  = get_post_types(array('public' => true), 'names');
        $blocked = array_flip(self::hardcoded_post_type_blocklist());
        $clean = array();
        foreach ($list as $pt) {
            if (!is_string($pt) || $pt === '') continue;
            if (isset($blocked[$pt])) continue;
            if (!isset($public[$pt])) continue;
            $clean[$pt] = true;
        }
        return array_keys($clean);
    }

    private static function default_all_public_pt() {
        $public  = get_post_types(array('public' => true), 'names');
        $blocked = array_flip(self::hardcoded_post_type_blocklist());
        $allowed = array();
        foreach ($public as $pt) {
            if (isset($blocked[$pt])) continue;
            $allowed[] = $pt;
        }
        return $allowed;
    }

    // ---- Sitemap.xml settings ----

    public static function get_sitemap_settings() {
        $stored = get_option(self::OPTION_SITEMAP_SETTINGS, array());
        if (!is_array($stored)) $stored = array();
        // Defaults: post_types null → "all public minus blocked"
        return array_merge(array('post_types' => null), $stored);
    }

    /**
     * Resolved post-type list for sitemap.xml.
     * null user_choice → all public minus blocked (Google needs everything).
     * array (incl. []) → exactly that list.
     */
    public static function get_sitemap_post_types() {
        $s = self::get_sitemap_settings();
        $uc = isset($s['post_types']) ? $s['post_types'] : null;
        if ($uc === null) return self::default_all_public_pt();
        if (is_array($uc)) return self::filter_to_valid_pt($uc);
        return self::default_all_public_pt();
    }

    public static function update_sitemap_settings($payload) {
        if (!is_array($payload)) $payload = array();
        $current = self::get_sitemap_settings();
        if (array_key_exists('post_types', $payload)) {
            $v = $payload['post_types'];
            if ($v === null || is_array($v)) {
                $current['post_types'] = $v;
            }
        }
        if (array_key_exists('takeover_competitors', $payload)) {
            $v = $payload['takeover_competitors'];
            if (is_bool($v) || $v === null) {
                $current['takeover_competitors'] = $v;
            } elseif (is_string($v) || is_int($v)) {
                $current['takeover_competitors'] = !empty($v) && $v !== 'null';
            }
        }
        update_option(self::OPTION_SITEMAP_SETTINGS, $current);
        // Bust sitemap cache
        if (class_exists('SEO_AEO_Sitemap') && method_exists('SEO_AEO_Sitemap', 'flush_all_cache')) {
            SEO_AEO_Sitemap::flush_all_cache();
        }
        return $current;
    }

    // ---- /llms-full.txt settings ----

    public static function get_llms_full_settings() {
        $stored = get_option(self::OPTION_LLMS_FULL_SETTINGS, array());
        if (!is_array($stored)) $stored = array();
        // Defaults: post_types null → curated default ['post','page']
        return array_merge(array('post_types' => null), $stored);
    }

    /**
     * Resolved post-type list for /llms-full.txt.
     * null user_choice → curated default ['post','page'] (AI needs curation).
     * array (incl. []) → exactly that list.
     */
    public static function get_llms_full_post_types() {
        $s = self::get_llms_full_settings();
        $uc = isset($s['post_types']) ? $s['post_types'] : null;
        if ($uc === null) return self::filter_to_valid_pt(self::LLMS_FULL_DEFAULT_POST_TYPES);
        if (is_array($uc)) return self::filter_to_valid_pt($uc);
        return self::filter_to_valid_pt(self::LLMS_FULL_DEFAULT_POST_TYPES);
    }

    public static function update_llms_full_settings($payload) {
        if (!is_array($payload)) $payload = array();
        $current = self::get_llms_full_settings();
        if (array_key_exists('post_types', $payload)) {
            $v = $payload['post_types'];
            if ($v === null || is_array($v)) {
                $current['post_types'] = $v;
            }
        }
        update_option(self::OPTION_LLMS_FULL_SETTINGS, $current);
        // Bust llms cache because the post-type set affects /llms-full.txt
        if (class_exists('SEO_AEO_LLMs_Txt') && method_exists('SEO_AEO_LLMs_Txt', 'flush_all_cache')) {
            SEO_AEO_LLMs_Txt::flush_all_cache();
        }
        return $current;
    }

    /**
     * Detect which other plugins emit a sitemap.xml on this site.
     * Returns array of {plugin, sitemap_url, detection_method}.
     */
    public static function detect_sitemap_providers() {
        $providers = array();

        // Yoast SEO sitemap module
        if (class_exists('WPSEO_Sitemaps') || (defined('WPSEO_VERSION') && function_exists('YoastSEO'))) {
            $providers[] = array(
                'plugin'           => 'Yoast SEO',
                'sitemap_url'      => home_url('/sitemap_index.xml'),
                'detection_method' => 'class_or_function',
            );
        }

        // Rank Math sitemap
        if (class_exists('\\RankMath\\Sitemap\\Sitemap') || defined('RANK_MATH_VERSION')) {
            $providers[] = array(
                'plugin'           => 'Rank Math',
                'sitemap_url'      => home_url('/sitemap_index.xml'),
                'detection_method' => 'constant_or_class',
            );
        }

        // AIOSEO sitemap
        if (defined('AIOSEO_VERSION') || class_exists('AIOSEO\\Plugin\\Common\\Sitemap\\Sitemap')) {
            $providers[] = array(
                'plugin'           => 'All in One SEO',
                'sitemap_url'      => home_url('/sitemap.xml'),
                'detection_method' => 'constant_or_class',
            );
        }

        // SEOPress sitemap
        if (defined('SEOPRESS_VERSION') || function_exists('seopress_xml_sitemap')) {
            $providers[] = array(
                'plugin'           => 'SEOPress',
                'sitemap_url'      => home_url('/sitemaps.xml'),
                'detection_method' => 'constant_or_function',
            );
        }

        // WP core sitemap (always present in WP 5.5+ unless disabled)
        if (function_exists('wp_sitemaps_get_server')) {
            $providers[] = array(
                'plugin'           => 'WordPress Core',
                'sitemap_url'      => home_url('/wp-sitemap.xml'),
                'detection_method' => 'function_exists',
            );
        }

        return $providers;
    }

    /**
     * Recommend whether the sitemap takeover toggle should be ON. If a
     * competitor SEO plugin is detected (other than WP core), recommend ON
     * so AEO Orchestra owns /sitemap.xml unambiguously.
     */
    public static function recommend_sitemap_takeover() {
        $providers = self::detect_sitemap_providers();
        foreach ($providers as $p) {
            if ($p['plugin'] !== 'WordPress Core') return true;
        }
        return false;
    }

    public static function get_sitemap_takeover() {
        $s = self::get_sitemap_settings();
        if (!array_key_exists('takeover_competitors', $s)) return null; // null = auto (use recommendation)
        return (bool) $s['takeover_competitors'];
    }

        // ---- Schema.org settings + detection ----

    public static function get_schema_settings() {
        $stored = get_option(self::OPTION_SCHEMA_SETTINGS, array());
        if (!is_array($stored)) $stored = array();
        return array_merge(array('mode' => 'auto'), $stored);
    }

    public static function update_schema_settings($payload) {
        if (!is_array($payload)) $payload = array();
        $current = self::get_schema_settings();
        if (isset($payload['mode']) && in_array($payload['mode'], self::SCHEMA_MODES, true)) {
            $current['mode'] = $payload['mode'];
        }
        update_option(self::OPTION_SCHEMA_SETTINGS, $current);
        return $current;
    }

    /**
     * Detect which other plugins are emitting JSON-LD on this site.
     * Returns array of {plugin, types_emitted, detection_method}.
     * Best-effort: known mappings of types each plugin typically emits.
     * No runtime page scan — purely based on class/constant detection (instant).
     */
    public static function detect_schema_providers() {
        $providers = array();

        // Yoast SEO (free + premium share schema engine)
        if (class_exists('WPSEO_Schema') || function_exists('YoastSEO') || defined('WPSEO_VERSION')) {
            $providers[] = array(
                'plugin'           => 'Yoast SEO',
                'types_emitted'    => array('Organization', 'WebSite', 'WebPage', 'Article', 'BreadcrumbList'),
                'detection_method' => 'class_or_function',
            );
        }

        // Rank Math
        if (defined('RANK_MATH_VERSION') || class_exists('\\RankMath\\Helper') || class_exists('RankMath')) {
            $providers[] = array(
                'plugin'           => 'Rank Math',
                'types_emitted'    => array('Organization', 'WebSite', 'WebPage', 'Article', 'BreadcrumbList', 'Product', 'Service'),
                'detection_method' => 'constant_or_class',
            );
        }

        // All in One SEO (AIOSEO)
        if (defined('AIOSEO_VERSION') || class_exists('AIOSEO\\Plugin\\Common\\Schema\\Schema') || function_exists('aioseo')) {
            $providers[] = array(
                'plugin'           => 'All in One SEO',
                'types_emitted'    => array('Organization', 'WebSite', 'WebPage', 'Article'),
                'detection_method' => 'constant_or_class',
            );
        }

        // SEOPress
        if (defined('SEOPRESS_VERSION') || function_exists('seopress_get_service')) {
            $providers[] = array(
                'plugin'           => 'SEOPress',
                'types_emitted'    => array('Organization', 'Article', 'BreadcrumbList'),
                'detection_method' => 'constant_or_function',
            );
        }

        // Schema Pro / Schema by Brainstorm Force
        if (defined('WPSP_VERSION') || class_exists('BSF_Schema_Pro_Loader')) {
            $providers[] = array(
                'plugin'           => 'Schema Pro',
                'types_emitted'    => array('Article', 'Product', 'Recipe', 'FAQPage', 'HowTo'),
                'detection_method' => 'constant_or_class',
            );
        }

        // Theme-level schema (heuristic): some themes emit `<script type="application/ld+json">`
        // directly via wp_head. We can't reliably distinguish without a page scan, so just note
        // the possibility when no plugin was detected.
        return $providers;
    }

    /**
     * Recommend a schema mode based on detection:
     *   - No detected provider → 'replace' (Orchestra is sole provider, safe)
     *   - 1+ providers detected → 'augment' (don't duplicate, add only missing types)
     */
    public static function recommend_schema_mode() {
        $providers = self::detect_schema_providers();
        return empty($providers) ? 'replace' : 'augment';
    }

    /**
     * Resolve the effective schema mode: if user chose 'auto', return the
     * detection-based recommendation; otherwise return user choice as-is.
     */
    public static function resolve_schema_mode() {
        $s = self::get_schema_settings();
        $mode = isset($s['mode']) ? $s['mode'] : 'auto';
        if ($mode === 'auto') {
            return self::recommend_schema_mode();
        }
        return $mode;
    }

        // ============================================================
    // Pattern parsing
    // ============================================================

    /**
     * Parse a textarea-style exclude_patterns string into an array of trimmed
     * substrings, skipping blank lines and #-comments.
     */
    public static function parse_patterns($raw) {
        if (!is_string($raw) || $raw === '') return array();
        $lines = preg_split('/\r\n|\r|\n/', $raw);
        $out = array();
        foreach ($lines as $l) {
            $l = trim($l);
            if ($l === '') continue;
            if (substr($l, 0, 1) === '#') continue;
            $out[] = $l;
        }
        return $out;
    }

    // ============================================================
    // Public type detection
    // ============================================================

    /**
     * The list of post type slugs that should be exposed by native outputs.
     * Honors user choice if any; otherwise defaults to "all public minus blocked".
     */
    public static function get_allowed_post_types() {
        $settings = self::get_settings();
        $public   = get_post_types(array('public' => true), 'names');
        $blocked  = array_flip(self::hardcoded_post_type_blocklist());

        $user_choice = isset($settings['post_types']) ? $settings['post_types'] : null;

        // null sentinel → user has not actively configured → fallback to all public minus blocked.
        if ($user_choice === null) {
            $allowed = array();
            foreach ($public as $pt) {
                if (isset($blocked[$pt])) continue;
                $allowed[] = $pt;
            }
            return $allowed;
        }

        // Literal array (including []) → user explicitly chose this list. Honor it verbatim.
        if (is_array($user_choice)) {
            $allowed_map = array();
            foreach ($user_choice as $pt) {
                if (!is_string($pt) || $pt === '') continue;
                if (isset($blocked[$pt])) continue;
                if (!isset($public[$pt])) continue;
                $allowed_map[$pt] = true;
            }
            return array_keys($allowed_map);
        }

        // Defensive fallback — unknown type → treat as null
        $allowed = array();
        foreach ($public as $pt) {
            if (isset($blocked[$pt])) continue;
            $allowed[] = $pt;
        }
        return $allowed;
    }

    /**
     * Detected public post types (filtered by hardcoded blocklist), shaped for
     * the admin UI checkbox grid.
     */
    public static function detected_post_types() {
        $public_pt = get_post_types(array('public' => true), 'objects');
        $blocked   = array_flip(self::hardcoded_post_type_blocklist());
        $detected  = array();
        foreach ($public_pt as $pt) {
            if (isset($blocked[$pt->name])) continue;
            $detected[] = array(
                'slug'  => $pt->name,
                'label' => isset($pt->labels->name) ? $pt->labels->name : $pt->name,
            );
        }
        return $detected;
    }

    // ============================================================
    // Per-post checks
    // ============================================================

    /**
     * Check Yoast / RankMath / AIOSEO / native noindex meta on a post.
     */
    public static function is_noindex_post($post_id) {
        $y = get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true);
        if ($y === '1' || $y === 1 || $y === true) return true;
        $r = get_post_meta($post_id, 'rank_math_robots', true);
        if (is_array($r) && in_array('noindex', $r, true)) return true;
        $a = get_post_meta($post_id, '_aioseo_meta_robots_noindex', true);
        if ($a === '1' || $a === 1 || $a === true) return true;
        $n = get_post_meta($post_id, '_seo_aeo_noindex', true);
        if ($n === '1' || $n === 1 || $n === true) return true;
        return false;
    }

    /**
     * Returns true if the post should be excluded from ALL native outputs based
     * on the GLOBAL settings only. Per-output classes layer their own overrides
     * on top of this base check.
     */
    public static function is_post_globally_excluded($post) {
        if (!is_object($post)) return true;
        if (!isset($post->ID, $post->post_type, $post->post_name)) return true;

        $blocked_pt = array_flip(self::hardcoded_post_type_blocklist());
        if (isset($blocked_pt[$post->post_type])) return true;

        $slug = strtolower((string) $post->post_name);
        foreach (self::hardcoded_slug_blocklist() as $needle) {
            if ($needle !== '' && strpos($slug, $needle) !== false) return true;
        }
        foreach (self::hardcoded_slug_prefix_blocklist() as $prefix) {
            if ($prefix !== '' && strpos($slug, $prefix) === 0) return true;
        }

        $url    = (string) get_permalink($post);
        $url_lc = strtolower($url);
        foreach (self::hardcoded_url_blocklist() as $needle) {
            if ($needle !== '' && strpos($url_lc, strtolower($needle)) !== false) return true;
        }

        $settings = self::get_settings();
        $patterns = self::parse_patterns($settings['exclude_patterns']);
        foreach ($patterns as $pat) {
            $pat_lc = strtolower($pat);
            if ($pat_lc === '') continue;
            if (strpos($slug, $pat_lc) !== false) return true;
            if (strpos($url_lc, $pat_lc) !== false) return true;
        }

        if (!empty($settings['respect_noindex']) && self::is_noindex_post($post->ID)) {
            return true;
        }

        return false;
    }
}

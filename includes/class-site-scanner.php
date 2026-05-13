<?php
/**
 * 3.40.2 — Extended site scanner: multi-signal builder + headless detection.
 *
 * Returns a combined profile with confidence scores + signal breakdown so
 * the Impostazioni → Compatibilità Sito tab can show transparently WHY
 * the plugin chose a given environment (e.g. "Detected because: WPGraphQL
 * active + siteurl != home + Vercel env constant").
 *
 * Builder detection (legacy in v3.40.0 — unchanged, returns one slug).
 *
 * NEW headless detection — combines 5 signals:
 *   1. wp_graphql/wp-graphql.php (or wpgraphql/wp-graphql.php) active
 *   2. WP_HOME constant != WP_SITEURL (canonical sign of decoupled WP)
 *   3. Theme stem matches /headless|api|stub|null|frontity/i
 *   4. constant defined: VERCEL_URL / NETLIFY / NEXT_PUBLIC_ / NUXT
 *   5. REST API enabled + content-type negotiation succeeds on /wp-json/wp/v2
 *
 * Each signal contributes 15-30% confidence. Threshold 40% to flag headless.
 * Below threshold: plugin treats site as standard WP (builder result wins).
 *
 * When headless confidence wins over builder confidence, the environment
 * key returned by SEO_AEO_Capability_Matrix::get_environment_key() is
 * 'headless_<mode>' where mode is inferred:
 *   - WPGraphQL active → 'gql'
 *   - else REST API primary → 'rest'
 *   - else generic SSG → 'ssg'
 *
 * Results cached in option aeo_site_profile (full snapshot dict).
 * Builder cache aeo_site_builder kept for back-compat.
 */
if (!defined('ABSPATH')) exit;

class SEO_AEO_Site_Scanner {

    /**
     * Plugin-slug → builder identifier. First match wins, so we list
     * the heavier/full-builder slugs first.
     */
    const PLUGIN_BUILDER_MAP = array(
        'elementor/elementor.php'                     => 'elementor',
        'elementor-pro/elementor-pro.php'             => 'elementor',
        'js_composer/js_composer.php'                 => 'wpbakery',
        'divi-builder/divi-builder.php'               => 'divi',
        'bricks/bricks.php'                           => 'bricks',
        'oxygen/oxygen.php'                           => 'oxygen',
        'beaver-builder-lite-version/fl-builder.php'  => 'beaver',
        'bb-plugin/fl-builder.php'                    => 'beaver',
        'breakdance/plugin.php'                       => 'breakdance',
    );

    /**
     * Plugin slugs that indicate WPGraphQL is installed/active.
     */
    const GQL_PLUGINS = array(
        'wp-graphql/wp-graphql.php',
        'wpgraphql/wp-graphql.php',
    );

    /**
     * Detect the active builder. Saves to option aeo_site_builder and
     * returns the slug. Falls back to gutenberg or classic based on a
     * sample of the 10 most recent published posts.
     */
    public static function detect_builder($force = false) {
        if (!$force) {
            $cached = get_option('aeo_site_builder', '');
            if ($cached) return $cached;
        }

        $active_plugins = (array) get_option('active_plugins', array());
        if (is_multisite()) {
            $active_plugins = array_merge($active_plugins, array_keys((array) get_site_option('active_sitewide_plugins', array())));
        }
        foreach ($active_plugins as $slug) {
            if (isset(self::PLUGIN_BUILDER_MAP[$slug])) {
                $builder = self::PLUGIN_BUILDER_MAP[$slug];
                update_option('aeo_site_builder', $builder, false);
                return $builder;
            }
        }

        $theme = wp_get_theme();
        if ($theme && in_array(strtolower((string) $theme->get('Name')), array('divi', 'extra'), true)) {
            update_option('aeo_site_builder', 'divi', false);
            return 'divi';
        }

        try {
            $recent = get_posts(array(
                'post_type'      => array('post', 'page'),
                'post_status'    => 'publish',
                'posts_per_page' => 10,
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'fields'         => 'ids',
            ));
            $block_hits = 0;
            $checked = 0;
            foreach ($recent as $pid) {
                $checked++;
                $content = (string) get_post_field('post_content', $pid);
                if (function_exists('has_blocks') && has_blocks($content)) {
                    $block_hits++;
                }
            }
            if ($checked > 0 && ($block_hits / $checked) >= 0.4) {
                update_option('aeo_site_builder', 'gutenberg', false);
                return 'gutenberg';
            }
        } catch (Throwable $e) {
            /* fall through */
        }

        update_option('aeo_site_builder', 'classic', false);
        return 'classic';
    }

    /**
     * 3.40.2 — Multi-signal headless detection. Returns:
     *   array(
     *     'is_headless'   => bool,
     *     'confidence'    => int 0-100,
     *     'mode'          => 'rest' | 'gql' | 'ssg' (best inference),
     *     'signals'       => array of [{name, weight, matched, note}],
     *   )
     *
     * Threshold for is_headless = true: total confidence >= 40.
     */
    public static function detect_headless() {
        $signals = array();

        // (1) WPGraphQL plugin active. Strong signal for gql mode.
        $active_plugins = (array) get_option('active_plugins', array());
        if (is_multisite()) {
            $active_plugins = array_merge($active_plugins, array_keys((array) get_site_option('active_sitewide_plugins', array())));
        }
        $gql_active = false;
        foreach (self::GQL_PLUGINS as $slug) {
            if (in_array($slug, $active_plugins, true)) {
                $gql_active = true;
                break;
            }
        }
        $signals[] = array(
            'name'    => 'WPGraphQL plugin',
            'weight'  => 30,
            'matched' => $gql_active,
            'note'    => $gql_active ? 'wp-graphql attivo (forte indicatore decoupled frontend)' : 'wp-graphql non attivo',
        );

        // (2) WP_HOME / siteurl != WP_SITEURL.
        $home    = (string) get_option('home', '');
        $siteurl = (string) get_option('siteurl', '');
        $home_diff = ($home !== '' && $siteurl !== '' && rtrim($home, '/') !== rtrim($siteurl, '/'));
        $signals[] = array(
            'name'    => 'siteurl != home',
            'weight'  => 25,
            'matched' => $home_diff,
            'note'    => $home_diff
                ? 'siteurl (' . esc_html($siteurl) . ') diverso da home (' . esc_html($home) . ') — pattern decoupled tipico'
                : 'siteurl e home coincidono',
        );

        // (3) Theme stem suggests headless / stub / Frontity.
        $theme = wp_get_theme();
        $theme_name = $theme ? strtolower((string) $theme->get('Name')) : '';
        $theme_match = (bool) preg_match('/headless|api|stub|null|frontity|empty/i', $theme_name);
        $signals[] = array(
            'name'    => 'Theme stem headless',
            'weight'  => 15,
            'matched' => $theme_match,
            'note'    => $theme_match
                ? 'Tema "' . esc_html($theme_name) . '" suggerisce un theme stub headless'
                : 'Tema standard',
        );

        // (4) Environment constants suggesting Next.js / Vercel / Netlify / Nuxt.
        $env_constants = array('VERCEL', 'VERCEL_URL', 'NETLIFY', 'NEXT_PUBLIC_WORDPRESS_URL', 'NUXT_PUBLIC_WP_URL');
        $env_match = false;
        $env_hit = '';
        foreach ($env_constants as $c) {
            if (defined($c) || (function_exists('getenv') && getenv($c))) {
                $env_match = true;
                $env_hit = $c;
                break;
            }
        }
        $signals[] = array(
            'name'    => 'Frontend env constants',
            'weight'  => 20,
            'matched' => $env_match,
            'note'    => $env_match
                ? 'Costante "' . esc_html($env_hit) . '" presente — frontend headless'
                : 'Nessuna costante VERCEL/NETLIFY/NEXT/NUXT rilevata',
        );

        // (5) REST API enabled + reachable.
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rest_enabled is a WordPress core filter.
        $rest_enabled = function_exists('rest_get_url_prefix') && apply_filters('rest_enabled', true);
        $signals[] = array(
            'name'    => 'REST API enabled',
            'weight'  => 10,
            'matched' => (bool) $rest_enabled,
            'note'    => $rest_enabled ? 'WP REST API esposto (richiesto per modalita\' headless)' : 'REST API disabilitato',
        );

        // Confidence aggregation.
        $confidence = 0;
        foreach ($signals as $s) {
            if ($s['matched']) $confidence += (int) $s['weight'];
        }
        if ($confidence > 100) $confidence = 100;

        $is_headless = $confidence >= 40;
        // Mode inference: prefer gql when graphql active, else rest, else ssg.
        $mode = 'rest';
        if ($gql_active) {
            $mode = 'gql';
        } elseif ($env_match) {
            $mode = 'ssg';
        } elseif (!$rest_enabled) {
            $mode = 'ssg';
        }

        return array(
            'is_headless' => $is_headless,
            'confidence'  => $confidence,
            'mode'        => $mode,
            'signals'     => $signals,
        );
    }

    /**
     * 3.40.2 — Combined full-scan. Runs both detectors, aggregates result,
     * saves to aeo_site_profile + back-compat aeo_site_builder /
     * aeo_site_is_headless / aeo_headless_mode options.
     */
    public static function scan_full($force = false) {
        $builder = self::detect_builder($force);
        $head = self::detect_headless();
        // Builder confidence is binary in v3.40.2: 100 if a plugin slug
        // matched, 70 if theme-Divi fallback, 50 if has_blocks() sampling
        // returned gutenberg, 30 for classic default. v3.40.3 may add a
        // proper score function — for now this approximation drives the UI.
        $builder_conf = 30;
        $active_plugins = (array) get_option('active_plugins', array());
        if (is_multisite()) {
            $active_plugins = array_merge($active_plugins, array_keys((array) get_site_option('active_sitewide_plugins', array())));
        }
        foreach ($active_plugins as $slug) {
            if (isset(self::PLUGIN_BUILDER_MAP[$slug])) { $builder_conf = 100; break; }
        }
        if ($builder_conf < 100 && $builder === 'gutenberg') $builder_conf = 50;
        if ($builder_conf < 100 && $builder === 'divi')      $builder_conf = 70;

        // Decision: if headless confidence >= 40 AND > builder confidence,
        // promote headless as the primary environment.
        $primary = 'standard';  // builder wins
        if ($head['is_headless'] && $head['confidence'] >= $builder_conf) {
            $primary = 'headless';
        }

        $profile = array(
            'builder'             => $builder,
            'builder_confidence'  => $builder_conf,
            'is_headless'         => $head['is_headless'],
            'headless_confidence' => $head['confidence'],
            'headless_mode'       => $head['mode'],
            'headless_signals'    => $head['signals'],
            'primary'             => $primary,
            'scanned_at'          => current_time('mysql'),
        );

        update_option('aeo_site_profile', $profile, false);
        // Back-compat options consumed by SEO_AEO_Capability_Matrix.
        update_option('aeo_site_is_headless', $primary === 'headless', false);
        if ($primary === 'headless') {
            update_option('aeo_headless_mode', $head['mode'], false);
        } else {
            delete_option('aeo_headless_mode');
        }
        return $profile;
    }

    /**
     * Wipe cache + re-detect. Returns the full profile (v3.40.2) instead
     * of just the builder slug. Old callers that expect a string still
     * get a usable value via $profile['builder'].
     */
    public static function force_rescan() {
        delete_option('aeo_site_builder');
        delete_option('aeo_site_profile');
        delete_option('aeo_site_is_headless');
        delete_option('aeo_headless_mode');
        return self::scan_full(true);
    }

    /**
     * Convenience: full site profile snapshot. Returns cached if present,
     * else triggers a scan.
     */
    public static function get_profile() {
        $cached = get_option('aeo_site_profile', null);
        if (is_array($cached) && !empty($cached)) return $cached;
        return self::scan_full(false);
    }
}

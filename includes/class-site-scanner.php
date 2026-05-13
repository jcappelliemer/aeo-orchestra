<?php
/**
 * 3.40.0 — Lightweight site scanner.
 *
 * Detects the active page builder by scanning the active plugins list
 * for known builder slugs, then falls back to gutenberg/classic based
 * on whether the most recent published posts use block markup. The
 * result is cached in the option aeo_site_builder.
 *
 * v3.40.0 ships the builder probe only. The full headless detection
 * (multi-signal confidence via RestAPI / Schema / theme / DNS) is
 * deferred to v3.40.2 — for now the user can manually flag the site
 * headless via a settings toggle (option aeo_site_is_headless) +
 * choose the mode (aeo_headless_mode = rest | gql | ssg). That data
 * feeds into SEO_AEO_Capability_Matrix::get_environment_key() with
 * the headless_* prefix.
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
        'breakdance/plugin.php'                       => 'breakdance',  // future
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

        // (1) Active builder plugins.
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

        // (2) Divi via active theme (Divi theme without plugin still uses the builder).
        $theme = wp_get_theme();
        if ($theme && in_array(strtolower((string) $theme->get('Name')), array('divi', 'extra'), true)) {
            update_option('aeo_site_builder', 'divi', false);
            return 'divi';
        }

        // (3) Gutenberg vs Classic — sample recent posts for block markup.
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
            // ignore — fall through to classic.
        }

        // (4) Default.
        update_option('aeo_site_builder', 'classic', false);
        return 'classic';
    }

    /**
     * Wipe cache + re-detect. Returns the new builder.
     */
    public static function force_rescan() {
        delete_option('aeo_site_builder');
        return self::detect_builder(true);
    }

    /**
     * Convenience: full site profile snapshot for the wizard / settings page.
     */
    public static function get_profile() {
        $builder = self::detect_builder();
        $is_headless = (bool) get_option('aeo_site_is_headless', false);
        $headless_mode = get_option('aeo_headless_mode', '');
        return array(
            'builder'        => $builder,
            'is_headless'    => $is_headless,
            'headless_mode'  => $headless_mode,
            'scanned_at'     => current_time('mysql'),
        );
    }
}

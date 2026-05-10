<?php
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com
 *
 * 3.35.70 — Hreflang emission for multilingual sites.
 *
 * Detects WPML / Polylang / TranslatePress and emits <link rel="alternate" hreflang>
 * tags in <head> for the current page + all its translations + x-default.
 *
 * Silent skip if no multilingual plugin detected (Solaris monolingua case).
 *
 * Setting: seo_aeo_hreflang_enabled (default '1' when plugin detected, '0' otherwise).
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Hreflang {

    const OPTION_ENABLED = 'seo_aeo_hreflang_enabled';

    public static function init() {
        add_action('wp_head', array(__CLASS__, 'emit_tags'), 2);
    }

    /**
     * Returns the detected multilingual plugin slug or null.
     * @return string|null 'wpml' | 'polylang' | 'translatepress' | null
     */
    public static function detect_plugin() {
        if (function_exists('icl_get_languages') || defined('ICL_SITEPRESS_VERSION')) return 'wpml';
        if (function_exists('pll_languages_list') || defined('POLYLANG_VERSION')) return 'polylang';
        if (class_exists('TRP_Translate_Press') || defined('TRP_PLUGIN_VERSION')) return 'translatepress';
        return null;
    }

    public static function is_enabled() {
        // Emergency disable kill-switch
        if (defined('AEO_ORCHESTRA_EMERGENCY_DISABLE') && AEO_ORCHESTRA_EMERGENCY_DISABLE) return false;
        // Skip if no plugin detected — silent
        if (self::detect_plugin() === null) return false;
        // Default enabled when plugin detected
        $setting = get_option(self::OPTION_ENABLED, '1');
        return $setting === '1';
    }

    /**
     * Returns translations map for the given post ID:
     *   array of {lang_code, url} where lang_code is the BCP-47 (it-IT, en-US) and url is permalink.
     * Includes the post itself.
     *
     * @return array list of {lang, url, is_default}
     */
    public static function get_translations($post_id) {
        $plugin = self::detect_plugin();
        if (!$plugin) return array();

        switch ($plugin) {
            case 'wpml':
                return self::wpml_translations($post_id);
            case 'polylang':
                return self::polylang_translations($post_id);
            case 'translatepress':
                return self::translatepress_translations($post_id);
        }
        return array();
    }

    /**
     * WPML translations resolver.
     */
    private static function wpml_translations($post_id) {
        if (!function_exists('apply_filters')) return array();
        $post_type = get_post_type($post_id);
        if (!$post_type) return array();

        $element_type = 'post_' . $post_type;
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML upstream hook name, fixed by external plugin
        $details = apply_filters('wpml_post_language_details', null, $post_id);
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML upstream hook name, fixed by external plugin
        $default_lang = apply_filters('wpml_default_language', null);

        $translations = array();
        $langs = function_exists('icl_get_languages') ? icl_get_languages('skip_missing=0') : array();
        if (!is_array($langs)) return array();

        foreach ($langs as $lang_code => $info) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML upstream hook name, fixed by external plugin
            $tx_id = apply_filters('wpml_object_id', $post_id, $post_type, false, $lang_code);
            if (!$tx_id) continue;
            $url = get_permalink($tx_id);
            if (!$url) continue;
            $locale = isset($info['default_locale']) ? $info['default_locale'] : $lang_code;
            $translations[] = array(
                'lang'       => self::normalize_locale($locale, $lang_code),
                'url'        => $url,
                'is_default' => ($lang_code === $default_lang),
                'lang_code'  => $lang_code,
            );
        }
        return $translations;
    }

    /**
     * Polylang translations resolver.
     */
    private static function polylang_translations($post_id) {
        if (!function_exists('pll_languages_list') || !function_exists('pll_get_post')) return array();
        $langs = pll_languages_list(array('hide_empty' => 0));
        $default_lang = function_exists('pll_default_language') ? pll_default_language() : '';
        if (!is_array($langs)) return array();

        $translations = array();
        foreach ($langs as $lang_code) {
            $tx_id = pll_get_post($post_id, $lang_code);
            if (!$tx_id) continue;
            $url = get_permalink($tx_id);
            if (!$url) continue;

            $locale = '';
            if (function_exists('PLL') && method_exists(PLL()->model, 'get_language')) {
                $lo = PLL()->model->get_language($lang_code);
                if ($lo && isset($lo->locale)) $locale = $lo->locale;
            }
            $translations[] = array(
                'lang'       => self::normalize_locale($locale, $lang_code),
                'url'        => $url,
                'is_default' => ($lang_code === $default_lang),
                'lang_code'  => $lang_code,
            );
        }
        return $translations;
    }

    /**
     * TranslatePress translations resolver (basic — emits per-language URLs based on
     * URL prefix scheme, since TRP doesn't have a canonical "post translation map").
     */
    private static function translatepress_translations($post_id) {
        if (!class_exists('TRP_Translate_Press')) return array();
        $trp = TRP_Translate_Press::get_trp_instance();
        if (!$trp || !method_exists($trp, 'get_component')) return array();
        $settings_obj = $trp->get_component('settings');
        if (!$settings_obj || !method_exists($settings_obj, 'get_settings')) return array();
        $settings = $settings_obj->get_settings();
        $publish_languages = isset($settings['publish-languages']) && is_array($settings['publish-languages'])
            ? $settings['publish-languages'] : array();
        $default_lang = isset($settings['default-language']) ? (string) $settings['default-language'] : '';
        if (empty($publish_languages)) return array();

        $self_url = get_permalink($post_id);
        if (!$self_url) return array();

        $translations = array();
        $url_converter = $trp->get_component('url_converter');
        foreach ($publish_languages as $lang_code) {
            if ($url_converter && method_exists($url_converter, 'get_url_for_language')) {
                $url = $url_converter->get_url_for_language($lang_code, $self_url, '');
            } else {
                $url = $self_url; // fallback
            }
            $translations[] = array(
                'lang'       => self::normalize_locale($lang_code, $lang_code),
                'url'        => $url,
                'is_default' => ($lang_code === $default_lang),
                'lang_code'  => $lang_code,
            );
        }
        return $translations;
    }

    /**
     * Normalize WP locale (it_IT) to hreflang format (it-IT) or fallback to lang code.
     */
    private static function normalize_locale($locale, $lang_code) {
        if (!$locale && $lang_code) return $lang_code;
        if (strpos($locale, '_') !== false) {
            return str_replace('_', '-', $locale);
        }
        return $locale ?: $lang_code;
    }

    /**
     * Emit hreflang tags in <head>.
     */
    public static function emit_tags() {
        if (!self::is_enabled()) return;
        if (!is_singular() && !is_home() && !is_front_page()) return;

        $post_id = is_singular() ? get_the_ID() : 0;
        if (!$post_id) {
            // Try home_id for front_page
            $post_id = (int) get_option('page_on_front', 0);
            if (!$post_id) return;
        }

        $translations = self::get_translations($post_id);
        if (empty($translations)) return;

        echo "\n<!-- AEO Orchestra · hreflang -->\n";
        $default_url = '';
        foreach ($translations as $tr) {
            if (empty($tr['lang']) || empty($tr['url'])) continue;
            echo '<link rel="alternate" hreflang="' . esc_attr($tr['lang']) . '" href="' . esc_url($tr['url']) . '" />' . "\n";
            if (!empty($tr['is_default'])) $default_url = $tr['url'];
        }
        if ($default_url) {
            echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($default_url) . '" />' . "\n";
        }
    }

    /**
     * Render preview of hreflang for a given post (used by admin AJAX).
     */
    public static function render_preview($post_id = 0) {
        if (!$post_id) {
            $post_id = (int) get_option('page_on_front', 0);
            if (!$post_id) {
                // Pick first published post
                $posts = get_posts(array('post_type' => array('post', 'page'), 'posts_per_page' => 1));
                if (!empty($posts)) $post_id = $posts[0]->ID;
            }
        }
        if (!$post_id) return array('post_id' => 0, 'tags' => array());
        $translations = self::get_translations($post_id);
        $tags = array();
        $default_url = '';
        foreach ($translations as $tr) {
            if (empty($tr['lang']) || empty($tr['url'])) continue;
            $tags[] = array('hreflang' => $tr['lang'], 'href' => $tr['url'], 'is_default' => !empty($tr['is_default']));
            if (!empty($tr['is_default'])) $default_url = $tr['url'];
        }
        if ($default_url) {
            $tags[] = array('hreflang' => 'x-default', 'href' => $default_url, 'is_default' => true);
        }
        return array(
            'post_id'    => $post_id,
            'post_title' => get_the_title($post_id),
            'plugin'     => self::detect_plugin(),
            'enabled'    => self::is_enabled(),
            'tags'       => $tags,
        );
    }
}

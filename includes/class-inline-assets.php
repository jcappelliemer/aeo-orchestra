<?php
/**
 * 3.35.85.0 — Inline assets registrar.
 *
 * WP.org guideline 4 / Plugin Check requires plugins to use the
 * wp_enqueue_* / wp_add_inline_* APIs rather than emitting raw
 * `<style>` and `<script>` tags from templates. This class provides a
 * single shared style and script handle for the plugin's admin pages.
 * Templates push inline blocks via:
 *
 *   SEO_AEO_Inline_Assets::add_inline_style(<<<CSS ... CSS);
 *   SEO_AEO_Inline_Assets::add_inline_script(<<<JS ... JS);
 *
 * The handles are registered with empty `src` (deps-only) — there is no
 * external file. WordPress emits the accumulated inline content inside
 * the proper enqueue pipeline.
 *
 * Scope: only fires on admin pages whose hook starts with `seo-aeo-` or
 * matches the plugin's parent menu slug. Front-end output is unchanged.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Inline_Assets {

    const STYLE_HANDLE  = 'seo-aeo-inline-css';
    const SCRIPT_HANDLE = 'seo-aeo-inline-js';

    /** @var bool tracks whether we already registered handles in this request */
    private static $registered = false;

    public static function bootstrap() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'maybe_register'), 1);
    }

    public static function maybe_register($hook) {
        // Restrict to plugin admin pages. WP appends parent slug + child slug
        // to $hook for submenus, so a substring match is enough.
        if (strpos((string) $hook, 'seo-aeo') === false) {
            return;
        }
        self::ensure_registered();
    }

    /**
     * Idempotent registration. Public so templates / one-off hooks can call
     * it directly when admin_enqueue_scripts has already fired before they
     * had a chance to push inline content.
     */
    public static function ensure_registered() {
        if (self::$registered) return;
        self::$registered = true;

        $version = defined('SEO_AEO_VERSION') ? SEO_AEO_VERSION : '1.0.0';

        // Empty src (false) registers a deps-only handle that WP can attach
        // inline content to. The handle MUST be enqueued (not just registered)
        // for wp_add_inline_* to actually emit anything.
        wp_register_style(self::STYLE_HANDLE, false, array(), $version);
        wp_enqueue_style(self::STYLE_HANDLE);

        wp_register_script(self::SCRIPT_HANDLE, false, array('jquery'), $version, true);
        wp_enqueue_script(self::SCRIPT_HANDLE);
    }

    public static function add_inline_style($css) {
        if (!is_string($css) || $css === '') return;
        self::ensure_registered();
        wp_add_inline_style(self::STYLE_HANDLE, $css);
    }

    public static function add_inline_script($js) {
        if (!is_string($js) || $js === '') return;
        self::ensure_registered();
        wp_add_inline_script(self::SCRIPT_HANDLE, $js);
    }
}

SEO_AEO_Inline_Assets::bootstrap();

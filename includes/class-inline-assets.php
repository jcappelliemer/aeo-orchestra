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
        // 3.36.8 — fix late-call regression: wp_add_inline_style() only emits
        // CSS if the registered handle has not yet been printed. Admin pages
        // print queued styles in <head> via admin_print_styles, BEFORE the
        // template body runs. So calls from inside a template body buffer
        // (the ob_start/ob_get_clean pattern used by 25+ templates here)
        // were silently dropped. WordPress queued the inline content but
        // never re-printed the already-done handle, collapsing dashboard
        // layouts where flex/grid CSS lived inside a template-body buffer
        // (e.g. the .orch-wiz-hero 5-box header).
        // Detection: if admin_print_styles has already fired, defer the CSS
        // to admin_print_footer_scripts (which also runs for styles, despite
        // the action name) and emit a style tag at footer time. CSS is
        // plugin-authored source, so it is trusted at the framing layer.
        if (did_action('admin_print_styles') > 0) {
            add_action('admin_print_footer_scripts', function () use ($css) {
                $tag_id = 'seo-aeo-inline-late-' . substr(md5($css), 0, 8);
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS is plugin-authored, trusted at framing layer; the only dynamic part is the tag_id (md5-hashed).
                echo "\n<style id=\"" . esc_attr($tag_id) . "\">\n" . $css . "\n</style>\n";
            });
            return;
        }
        wp_add_inline_style(self::STYLE_HANDLE, $css);
    }

    public static function add_inline_script($js) {
        if (!is_string($js) || $js === '') return;
        self::ensure_registered();
        // 3.36.8 — mirror late-call fix for scripts. See add_inline_style()
        // comment above for the reasoning. wp_print_footer_scripts naturally
        // re-runs script printing, but wp_add_inline_script() suffers the
        // same "handle already done" silent drop for scripts in head group.
        // The plugin registers SCRIPT_HANDLE with in_footer=true so this is
        // rare, but defensive parity keeps behaviour symmetric.
        if (did_action('admin_print_scripts') > 0 && did_action('admin_print_footer_scripts') > 0) {
            add_action('admin_footer', function () use ($js) {
                $tag_id = 'seo-aeo-inline-late-' . substr(md5($js), 0, 8);
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JS is plugin-authored, trusted at framing layer; the only dynamic part is the tag_id (md5-hashed).
                echo "\n<script id=\"" . esc_attr($tag_id) . "\">\n" . $js . "\n</script>\n";
            }, 999);
            return;
        }
        wp_add_inline_script(self::SCRIPT_HANDLE, $js);
    }
}

SEO_AEO_Inline_Assets::bootstrap();

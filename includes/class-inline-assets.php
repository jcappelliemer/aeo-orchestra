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
                // 3.37.0 Module 8 — DOMContentLoaded timing fix. The 3.36.8 late-emit
                // path solved the silent-drop CSS bug but introduced a regression:
                // user code using document.addEventListener('DOMContentLoaded', cb)
                // never ran its callback because DOMContentLoaded already fired by
                // footer time. Wrap with a thin IIFE that, when readyState is past
                // 'loading', temporarily intercepts document.addEventListener for
                // the DOMContentLoaded event during user-code execution and fires
                // the registered callback immediately. Other addEventListener calls
                // (click/change/etc.) pass through unchanged. The patch is restored
                // in finally{} so no global side effects survive the wrapper.
                $wrapper  = "(function(){\n";
                $wrapper .= "  if (document.readyState === 'loading') {\n";
                $wrapper .= "    document.addEventListener('DOMContentLoaded', function(){\n";
                $wrapper .= "      " . $js . "\n";
                $wrapper .= "    });\n";
                $wrapper .= "  } else {\n";
                $wrapper .= "    var __seoAeoOrigAdd = document.addEventListener;\n";
                $wrapper .= "    document.addEventListener = function(event, handler, options) {\n";
                $wrapper .= "      if (event === 'DOMContentLoaded' && typeof handler === 'function') {\n";
                $wrapper .= "        try { handler(new Event('DOMContentLoaded')); } catch(e) { if (window.console) console.error('[SeoAeoLateInit]', e); }\n";
                $wrapper .= "        return;\n";
                $wrapper .= "      }\n";
                $wrapper .= "      return __seoAeoOrigAdd.call(document, event, handler, options);\n";
                $wrapper .= "    };\n";
                $wrapper .= "    try {\n";
                $wrapper .= "      " . $js . "\n";
                $wrapper .= "    } finally {\n";
                $wrapper .= "      document.addEventListener = __seoAeoOrigAdd;\n";
                $wrapper .= "    }\n";
                $wrapper .= "  }\n";
                $wrapper .= "})();\n";
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JS is plugin-authored, trusted at framing layer; the only dynamic part is the tag_id (md5-hashed).
                echo "\n<script id=\"" . esc_attr($tag_id) . "\">\n" . $wrapper . "</script>\n";
            }, 999);
            return;
        }
        wp_add_inline_script(self::SCRIPT_HANDLE, $js);
    }
}

SEO_AEO_Inline_Assets::bootstrap();

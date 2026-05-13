<?php
// phpcs:disable WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
/**
 * Plugin Name: AEO Orchestra
 * Plugin URI: https://aeo-orchestra.com
 * Description: Plugin SEO + AEO completo: specialisti AI perfettamente orchestrati per meta tags, content generation, schema, llms.txt, sitemap, redirect manager, brand voice e altro.
 * Version: 3.39.10
 * Requires at least: 5.8
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * Author: Solaris Code SL
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: aeo-orchestra
 * Domain Path: /languages
 *
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
if (!defined('ABSPATH')) exit;

// 3.38.5 — Strict PHP version gate. Bail BEFORE any class loading if PHP < 7.4.
// Renders an admin notice instead of fataling. Plugin appears installed but
// inert, so WP's broken-plugin recovery never triggers the file-removal path.
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>AEO Orchestra:</strong> Richiede PHP 7.4 o superiore. Versione rilevata: ' . esc_html(PHP_VERSION) . '. Contatta il tuo hosting per aggiornare.</p></div>';
    });
    return;
}

define('SEO_AEO_VERSION', '3.39.10');
define('SEO_AEO_AGENTS_COUNT', 13);  // 3.35.84.2: +Verify-Live  // mirrors backend/helpers/config.py AGENTS_COUNT — bump on every new agent
define('SEO_AEO_TOOLS_COUNT', 22);   // 3.35.84.2: +Verify-Live, Profilo Business, AI Performance, AI Crawlers   // mirrors backend/helpers/config.py TOOLS_COUNT — bump on every new tool
define('SEO_AEO_DIR', plugin_dir_path(__FILE__));

/**
 * 3.38.5 — Defensive require. Replaces bare require_once for new (v3.38.0+)
 * files. file_exists() pre-check, try/catch wrap (catches Error on PHP 8+),
 * records failures in option seo_aeo_load_failures so an admin_notices hook
 * can surface a precise diagnostic to the user.
 *
 * Returns true on success, false on any failure. Caller can early-return
 * on false to avoid follow-on errors.
 */
function seo_aeo_safe_require($file, $label = '') {
    if (!file_exists($file) || !is_readable($file)) {
        seo_aeo_record_load_failure($file, $label, 'file_missing_or_unreadable');
        return false;
    }
    try {
        require_once $file;
        return true;
    } catch (Throwable $e) {
        seo_aeo_record_load_failure($file, $label, $e->getMessage());
        return false;
    }
}

function seo_aeo_record_load_failure($file, $label, $reason) {
    $failures = get_option('seo_aeo_load_failures', array());
    if (!is_array($failures)) $failures = array();
    $failures[] = array(
        'file'      => basename($file),
        'path'      => $file,
        'label'     => $label,
        'reason'    => substr((string) $reason, 0, 300),
        'timestamp' => gmdate('c'),
    );
    // Cap to last 20 failures to prevent unbounded growth.
    if (count($failures) > 20) $failures = array_slice($failures, -20);
    update_option('seo_aeo_load_failures', $failures, false);
    if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- diagnostic for load-failure path only, gated on WP_DEBUG.
        @error_log('[SEO_AEO] Load failure: ' . basename($file) . ' — ' . $reason);
    }
}

// Surface recorded load failures as admin notices so the user knows which
// file to recover. Hook on admin_notices priority 1 (early) so it appears
// above other notices.
add_action('admin_notices', function () {
    $failures = get_option('seo_aeo_load_failures', array());
    if (empty($failures) || !is_array($failures)) return;
    // Only show on plugin's own pages OR Plugins screen — otherwise too noisy.
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    $on_plugin_page = $screen && (
        strpos((string) $screen->id, 'seo-aeo') !== false ||
        $screen->id === 'plugins'
    );
    if (!$on_plugin_page) return;
    echo '<div class="notice notice-error is-dismissible"><p><strong>AEO Orchestra — load failures detected:</strong></p><ul style="margin:0 0 8px 18px;list-style:disc;">';
    foreach (array_slice($failures, -5) as $f) {
        echo '<li><code>' . esc_html($f['file']) . '</code>: ' . esc_html($f['reason']) . '</li>';
    }
    echo '</ul><p>Re-install the plugin from a known-good ZIP to recover. Diagnostic: <a href="' . esc_url(admin_url('admin.php?page=seo-aeo-debug-cache')) . '">Cache Debug</a>.</p></div>';
}, 1);

define('SEO_AEO_URL', plugin_dir_url(__FILE__));
define('SEO_AEO_PLUGIN_DIR', SEO_AEO_DIR);
define('SEO_AEO_PLUGIN_URL', SEO_AEO_URL);
if (!defined('SEO_AEO_API_URL')) {
    define('SEO_AEO_API_URL', get_option('seo_aeo_orchestra_api_url', 'https://aeo-orchestra.com'));
}

// Distribution channel: 'direct' (license-server, default) or 'wporg' (no self-updater).
// The WP.org build script rewrites this constant to 'wporg' when packaging.
if (!defined('SEO_AEO_DISTRIBUTION_CHANNEL')) {
    define('SEO_AEO_DISTRIBUTION_CHANNEL', 'wporg');
}

/*
 * 3.35.85.0 (WP.org review compliance): SEO_AEO_IS_FREE constant removed.
 * The plugin codebase is now a single distribution. All features ship in
 * every build; premium operations are gated server-side (serviceware) by
 * the `SEO_AEO_API_URL` backend based on `seo_aeo_orchestra_license_key`.
 * The only remaining channel-aware behavior is the custom updater, which
 * only loads when SEO_AEO_DISTRIBUTION_CHANNEL !== 'wporg' (WP.org rules
 * forbid plugins shipping their own updaters).
 *
 * Coexistence guard (Pro install + WP.org install on the same site):
 * the WP.org channel auto-deactivates if it detects the direct Pro slug.
 * Both builds share option keys (seo_aeo_*) and DB tables, so running
 * both simultaneously emits duplicate <head> output. Only the WP.org
 * channel needs to step aside.
 */
// 3.35.55: Heuristic page roles classification on activation + weekly refresh.
register_activation_hook(__FILE__, function () {
    if (!wp_next_scheduled('seo_aeo_weekly_role_classify')) {
        wp_schedule_event(time() + DAY_IN_SECONDS, 'weekly', 'seo_aeo_weekly_role_classify');
    }
    // Defer the first heuristic classification to a single-fire event so activation stays fast.
    wp_schedule_single_event(time() + 30, 'seo_aeo_first_role_classify');
});

// 3.38.1 Task 4 — Post-activation redirect to Setup Guidato. Industry-standard
// onboarding pattern (Yoast, RankMath, Elementor): on first activation, set a
// short-lived transient scoped to the activating user. An admin_init listener
// then redirects them to Setup Guidato in first-run mode.
register_activation_hook(__FILE__, function () {
    // Don't redirect during multi-plugin bulk activation (WP fires deactivation
    // hooks for each one and we'd compete).
    if (isset($_REQUEST['activate-multi'])) return;
    $uid = get_current_user_id();
    if ($uid <= 0) return;
    // Respect users who previously opted out and users who already completed setup.
    if (get_option('seo_aeo_setup_skipped_by_user', false)) return;
    if (get_option('seo_aeo_setup_completed_once', false)) return;
    set_transient('seo_aeo_just_activated_' . $uid, 1, 60);
});

add_action('admin_init', function () {
    if (defined('DOING_AJAX') && DOING_AJAX) return;
    if (defined('DOING_CRON') && DOING_CRON) return;
    $uid = get_current_user_id();
    if ($uid <= 0) return;
    if (!get_transient('seo_aeo_just_activated_' . $uid)) return;
    // Clear the flag BEFORE redirecting so we never loop.
    delete_transient('seo_aeo_just_activated_' . $uid);
    $url = admin_url('admin.php?page=seo-aeo-setup-guidato&first_run=1');
    wp_safe_redirect($url);
    exit;
}, 1);
register_deactivation_hook(__FILE__, function () {
    $ts = wp_next_scheduled('seo_aeo_weekly_role_classify');
    if ($ts) wp_unschedule_event($ts, 'seo_aeo_weekly_role_classify');
    $ts2 = wp_next_scheduled('seo_aeo_first_role_classify');
    if ($ts2) wp_unschedule_event($ts2, 'seo_aeo_first_role_classify');
});
add_action('seo_aeo_first_role_classify', function () {
    if (class_exists('SEO_AEO_Page_Roles')) {
        SEO_AEO_Page_Roles::heuristic_classify_all();
    }
});

// Self-healing: if the cron isn't scheduled (e.g. plugin upgraded without re-activation),
// schedule it on the first admin_init.
add_action('admin_init', function () {
    if (!wp_next_scheduled('seo_aeo_weekly_role_classify')) {
        wp_schedule_event(time() + DAY_IN_SECONDS, 'weekly', 'seo_aeo_weekly_role_classify');
    }
    // First-run safety: if option isn't populated yet, run heuristic now (sync — small sites only)
    $map_raw = get_option('seo_aeo_page_role_map', '');
    if ((!is_string($map_raw) || $map_raw === '' || $map_raw === '[]' || $map_raw === '{}') && class_exists('SEO_AEO_Page_Roles')) {
        // Defer to a single event so admin page load isn't blocked
        if (!wp_next_scheduled('seo_aeo_first_role_classify')) {
            wp_schedule_single_event(time() + 5, 'seo_aeo_first_role_classify');
        }
    }
});

if (SEO_AEO_DISTRIBUTION_CHANNEL === 'wporg') {
    register_activation_hook(__FILE__, 'seo_aeo_free_block_if_pro_active');
    add_action('admin_init', 'seo_aeo_free_runtime_coexistence_check', 1);
}

if (!function_exists('seo_aeo_free_pro_is_active')) {
    function seo_aeo_free_pro_is_active() {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return is_plugin_active('seo-aeo-orchestra/seo-aeo-orchestra.php');
    }
}

if (!function_exists('seo_aeo_free_block_if_pro_active')) {
    function seo_aeo_free_block_if_pro_active() {
        if (seo_aeo_free_pro_is_active()) {
            wp_die(
                '<h1>AEO Orchestra Pro gia attivo</h1>'
                . '<p>Hai gia installato e attivato la versione <strong>Pro</strong> di AEO Orchestra. La versione gratuita (WordPress.org) non e necessaria.</p>'
                . '<p><a href="' . esc_url(admin_url('plugins.php')) . '">Torna ai Plugin</a></p>',
                'AEO Orchestra Free',
                array('back_link' => true)
            );
        }
    }
}

if (!function_exists('seo_aeo_free_runtime_coexistence_check')) {
    function seo_aeo_free_runtime_coexistence_check() {
        if (!current_user_can('manage_options')) return;
        if (!seo_aeo_free_pro_is_active()) return;
        deactivate_plugins(plugin_basename(__FILE__));
        add_action('admin_notices', 'seo_aeo_free_pro_conflict_notice');
        if (isset($_GET['activate'])) unset($_GET['activate']);
    }
}

if (!function_exists('seo_aeo_free_pro_conflict_notice')) {
    function seo_aeo_free_pro_conflict_notice() {
        echo '<div class="notice notice-warning is-dismissible"><p><strong>AEO Orchestra Free</strong> auto-disattivato: la versione <strong>Pro</strong> e gia installata e attiva. Il Free non e necessario quando Pro e presente.</p></div>';
    }
}

// Include classes (safe load).
// 3.35.85.0: every class loads unconditionally. Premium-only flows fail
// gracefully via backend serviceware errors when the license_key is missing
// or below tier — no client-side gating.
try {
    require_once SEO_AEO_DIR . 'includes/class-api-client.php';
    require_once SEO_AEO_DIR . 'includes/class-ai-helpers.php'; // 3.35.8: shared utilities (attach_featured_image, mark_ai_generated, build_inline_image_block)
    // 3.35.84.4: centralized admin notices + dynamic "what's new" parser (load before any class that registers a notice).
    require_once SEO_AEO_DIR . 'includes/helper-debug-log.php'; // 3.36.0: WP_DEBUG-gated logger
    require_once SEO_AEO_DIR . 'includes/class-admin-notices.php';
    require_once SEO_AEO_DIR . 'includes/class-whats-new.php';
    // 3.35.85.0: shared $_POST/$_GET JSON sanitizer (WP.org review compliance).
    require_once SEO_AEO_DIR . 'includes/class-input-sanitizer.php';
    // 3.35.85.0: inline-assets registrar — templates push <style>/<script>
    // content via SEO_AEO_Inline_Assets::add_inline_{style,script}(), which
    // wraps wp_add_inline_{style,script} on a deps-only registered handle.
    require_once SEO_AEO_DIR . 'includes/class-inline-assets.php';
    require_once SEO_AEO_DIR . 'includes/class-business-profile.php'; // 3.35.83: foundation feature
    seo_aeo_safe_require(SEO_AEO_DIR . 'includes/class-setup-progress.php', 'class-setup-progress'); // 3.38.0
    seo_aeo_safe_require(SEO_AEO_DIR . 'includes/class-setup-widget.php', 'class-setup-widget'); // 3.38.0
    require_once SEO_AEO_DIR . 'includes/class-admin-ui.php';
    require_once SEO_AEO_DIR . 'includes/class-ajax-handlers.php';
    require_once SEO_AEO_DIR . 'includes/class-widget.php';
    require_once SEO_AEO_DIR . 'includes/class-history.php';
    require_once SEO_AEO_DIR . 'includes/class-usage-tracker.php';
    // The custom updater only ships in non-wporg channels; WP.org plugins must
    // use the directory's update mechanism, not their own.
    if (SEO_AEO_DISTRIBUTION_CHANNEL !== 'wporg') {
        require_once SEO_AEO_DIR . 'includes/class-updater.php';
    }
    // 3.35.67: PHP 8 compat polyfills (loaded VERY early so all classes can rely on them)
    if (file_exists(SEO_AEO_DIR . 'includes/compat/php-polyfills.php')) {
        require_once SEO_AEO_DIR . 'includes/compat/php-polyfills.php';
    }
    // 3.35.65: emergency disable + debug snapshot (loaded early to catch fatals)
    require_once SEO_AEO_DIR . 'includes/class-emergency-disable.php';
    require_once SEO_AEO_DIR . 'includes/class-debug-snapshot.php';
    SEO_AEO_Debug_Snapshot::init();
    if (is_admin()) { SEO_AEO_Emergency_Disable::init_admin_notice(); }
    require_once SEO_AEO_DIR . 'includes/class-snapshot-manager.php';
    require_once SEO_AEO_DIR . 'includes/class-seo-engine-bridge.php';
    require_once SEO_AEO_DIR . 'includes/class-output-renderer.php';
    require_once SEO_AEO_DIR . 'includes/class-sitemap.php';
    require_once SEO_AEO_DIR . 'includes/class-global-filters.php';
    require_once SEO_AEO_DIR . 'includes/class-page-roles.php';
    require_once SEO_AEO_DIR . 'includes/class-edit-screen-metabox.php';
    require_once SEO_AEO_DIR . 'includes/class-llms-txt.php';
    require_once SEO_AEO_DIR . 'includes/class-schema.php';
    require_once SEO_AEO_DIR . 'includes/class-redirect-manager.php';
    require_once SEO_AEO_DIR . 'includes/class-hreflang.php';
    SEO_AEO_Hreflang::init();
    // 3.35.78: AI provider router (backend-centralized, no local credentials)
    require_once SEO_AEO_DIR . 'includes/class-ai-provider-router.php';
    // 3.35.77: Verify-live SSE Phase 1
    require_once SEO_AEO_DIR . 'includes/class-verify-live.php';
    SEO_AEO_Verify_Live::init();
    // 3.35.80: AI Crawler Allowlist + Logger
    require_once SEO_AEO_DIR . 'includes/class-ai-crawler-detector.php';
    require_once SEO_AEO_DIR . 'includes/class-ai-crawler-admin.php';
    SEO_AEO_AI_Crawler_Detector::init();
    SEO_AEO_AI_Crawler_Admin::init();
    require_once SEO_AEO_DIR . 'includes/class-migration-wizard.php';
    require_once SEO_AEO_DIR . 'includes/class-migration-importer.php';
    require_once SEO_AEO_DIR . 'includes/class-brand-voice.php';
    require_once SEO_AEO_DIR . 'includes/class-autopilot.php';
    require_once SEO_AEO_DIR . 'includes/class-translator.php';
    require_once SEO_AEO_DIR . 'includes/class-credits-bar.php';
    require_once SEO_AEO_DIR . 'includes/class-calendar.php';
    require_once SEO_AEO_DIR . 'includes/class-image-seo.php';
} catch (Throwable $e) {
    return;
}

// 3.38.5 — If we reached this point without any load failure being recorded
// in this request, clear the historical failures option (self-healing once
// the user re-uploads a complete plugin ZIP). Wrapped in an anonymous closure
// so the local vars never leak to the global scope (Plugin Check compliance).
( function () {
    if ( empty( get_option( 'seo_aeo_load_failures' ) ) ) return;
    $seo_aeo_critical_classes = array(
        'SEO_AEO_API_Client',
        'SEO_AEO_Inline_Assets',
        'SEO_AEO_Setup_Progress',
        'SEO_AEO_Setup_Widget',
    );
    foreach ( $seo_aeo_critical_classes as $seo_aeo_cls ) {
        if ( ! class_exists( $seo_aeo_cls ) ) return;
    }
    delete_option( 'seo_aeo_load_failures' );
} )();

// Initialize components TOP-LEVEL (no plugins_loaded wrap) - ensures classes are loaded before admin-ajax.php
global $seo_aeo_api, $seo_aeo_admin, $seo_aeo_ajax, $seo_aeo_widget, $seo_aeo_history, $seo_aeo_tracker;

try {
    
    if (class_exists('SEO_AEO_API_Client')) {
        $seo_aeo_api = new SEO_AEO_API_Client();
    } else {
        $seo_aeo_api = null;
    }

    $seo_aeo_main = new stdClass();
    $seo_aeo_main->api_client   = $seo_aeo_api;
    $seo_aeo_main->license_key  = get_option('seo_aeo_orchestra_license_key', '');
    $seo_aeo_main->license_type = get_option('seo_aeo_orchestra_license_type', 'starter');

    if (class_exists('SEO_AEO_Orchestra_Admin_UI')) {
        $seo_aeo_admin = new SEO_AEO_Orchestra_Admin_UI($seo_aeo_main);
    } else {
    }
    
    if (class_exists('SEO_AEO_Orchestra_Ajax_Handlers')) {
        $seo_aeo_ajax = new SEO_AEO_Orchestra_Ajax_Handlers($seo_aeo_main);
    } else {
    }
    
    if (class_exists('SEO_AEO_Orchestra_Widget')) {
        $seo_aeo_widget = new SEO_AEO_Orchestra_Widget($seo_aeo_main);
    }
    if (class_exists('SEO_AEO_Orchestra_History')) {
        $seo_aeo_history = new SEO_AEO_Orchestra_History();
    }
    if (class_exists('SEO_AEO_Orchestra_Usage_Tracker')) {
        $seo_aeo_tracker = new SEO_AEO_Orchestra_Usage_Tracker();
    }
    
    if (SEO_AEO_DISTRIBUTION_CHANNEL !== 'wporg') {
        if (class_exists('SEO_AEO_Orchestra_Updater')) {
            $seo_aeo_updater = new SEO_AEO_Orchestra_Updater();
        } else {
        }
    }

    if (class_exists('SEO_AEO_Credits_Bar')) {
        $seo_aeo_credits_bar = new SEO_AEO_Credits_Bar();
    }

    if (class_exists('SEO_AEO_Output_Renderer')) {
        $seo_aeo_output = new SEO_AEO_Output_Renderer();
    }

    if (class_exists('SEO_AEO_Sitemap')) {
        $seo_aeo_sitemap = new SEO_AEO_Sitemap();
    }

    if (class_exists('SEO_AEO_LLMs_Txt')) {
        $seo_aeo_llms = new SEO_AEO_LLMs_Txt();
    }

    if (class_exists('SEO_AEO_Schema')) {
        $seo_aeo_schema = new SEO_AEO_Schema();
    }

    if (class_exists('SEO_AEO_Brand_Voice')) {
        $seo_aeo_brand_voice = new SEO_AEO_Brand_Voice();
    }

    if (class_exists('SEO_AEO_Autopilot')) {
        $seo_aeo_autopilot = new SEO_AEO_Autopilot();
    }

    if (class_exists('SEO_AEO_T')) {
        SEO_AEO_T::init();
    }

    if (class_exists('SEO_AEO_Redirect_Manager')) {
        $seo_aeo_redirect = new SEO_AEO_Redirect_Manager();
    }

    if (class_exists('SEO_AEO_Calendar')) {
        $seo_aeo_calendar = new SEO_AEO_Calendar();
    }

    if (class_exists('SEO_AEO_Image_SEO')) {
        SEO_AEO_Image_SEO::init();
    }

    // Auto-flush rewrite rules dopo plugin update (3.13.3+)
    // Garantisce che endpoint /seo-aeo-sitemap.xml e /llms.txt funzionino
    // anche se l'utente non interagisce con i toggle dopo l'aggiornamento.
    add_action('admin_init', function () {
        $stored = get_option('seo_aeo_orchestra_flush_rewrite_version', '');
        if ($stored !== SEO_AEO_VERSION) {
            if (function_exists('flush_rewrite_rules')) {
                flush_rewrite_rules(false);
                update_option('seo_aeo_orchestra_flush_rewrite_version', SEO_AEO_VERSION);
            }
        }
    }, 99);

} catch (Throwable $e) {
}

/**
 * Activation hook - SILENT. Deferred activation via admin_init.
 */
register_activation_hook(__FILE__, function () {
    update_option('seo_aeo_orchestra_needs_activation', '1');
});

/**
 * Deferred license activation on first admin page load.
 * NON-BLOCKING: reduced timeout, no auto-deactivation on failure.
 */
add_action('admin_init', function () {
    // Only run on plugin pages - avoid HTTP calls on every WP admin page
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- read-only menu routing
    if (!isset($_GET['page']) || strpos($_GET['page'], 'seo-aeo') === false) return;
    if (get_option('seo_aeo_orchestra_needs_activation', '0') !== '1') {
        return;
    }
    delete_option('seo_aeo_orchestra_needs_activation');
    try {
        $key = get_option('seo_aeo_orchestra_license_key', '');
        if (empty($key)) {
            return;
        }
        $api = new SEO_AEO_API_Client();
        $result = $api->activate_license();
        if (!empty($result['success'])) {
            delete_option('seo_aeo_activation_error');
        }
    } catch (Throwable $e) {
    }
});

/**
 * Clean up bad option values left by previous versions.
 */
add_action('admin_init', function () {
    $err = get_option('seo_aeo_activation_error');
    if ($err !== false && !is_string($err)) {
        delete_option('seo_aeo_activation_error');
    }
    $reason = get_option('seo_aeo_deactivation_reason');
    if ($reason !== false && !is_string($reason)) {
        delete_option('seo_aeo_deactivation_reason');
    }
});

/**
 * Deactivation hook - cleanup.
 */
register_deactivation_hook(__FILE__, function () {
    delete_option('seo_aeo_activation_error');
    delete_option('seo_aeo_deactivation_reason');
    delete_option('seo_aeo_orchestra_needs_activation');
});

/**
 * Admin notices - safe string handling.
 */
add_action('admin_notices', function () {
    $err = get_option('seo_aeo_activation_error');
    if ($err && is_string($err)) {
        echo '<div class="notice notice-error"><p><strong>AEO Orchestra:</strong> '
            . esc_html($err) . '</p></div>';
    } elseif ($err) {
        delete_option('seo_aeo_activation_error');
    }
    $reason = get_option('seo_aeo_deactivation_reason');
    if ($reason && is_string($reason)) {
        echo '<div class="notice notice-error"><p><strong>AEO Orchestra:</strong> '
            . esc_html($reason)
            . ' <a href="https://aeo-orchestra.com/dashboard">Vai alla dashboard &rarr;</a></p></div>';
        delete_option('seo_aeo_deactivation_reason');
    } elseif ($reason) {
        delete_option('seo_aeo_deactivation_reason');
    }
});

/**
 * Domain validation on admin_init.
 * Only logs warnings, does NOT auto-deactivate.
 */
add_action('admin_init', function () {
    // Only run on plugin pages
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- read-only menu routing
    if (!isset($_GET['page']) || strpos($_GET['page'], 'seo-aeo') === false) return;
    try {
        global $seo_aeo_api;
        if (!$seo_aeo_api) return;
        $lock = get_option('seo_aeo_domain_lock', array());
        if (!empty($lock) && !$seo_aeo_api->validate_domain()) {
            $original = isset($lock['domain']) ? $lock['domain'] : 'sconosciuto';
            update_option('seo_aeo_activation_error',
                "Attenzione: dominio diverso da quello attivato ({$original}). Vai su aeo-orchestra.com/dashboard per trasferire."
            );
        }
    } catch (Throwable $e) {
    }
});


/**
 * Filter title tag for SEO.
 */
add_filter('pre_get_document_title', function ($title) {
    try {
        $post_id = get_the_ID();
        if ($post_id) {
            $meta_title = get_post_meta($post_id, '_seo_aeo_meta_title', true);
            if ($meta_title) return $meta_title;
        }
    } catch (Throwable $e) {
    }
    return $title;
}, 10);

// Cron snapshot cleanup (ondata 2 — Propose/Review/Apply)
if (class_exists('SEO_AEO_Snapshot_Manager')) {
    register_activation_hook(__FILE__, array('SEO_AEO_Snapshot_Manager', 'register_cron'));
    register_deactivation_hook(__FILE__, array('SEO_AEO_Snapshot_Manager', 'unregister_cron'));
}

// 3.35.61.1: PHP version check with admin_notice
add_action('admin_init', 'seo_aeo_php_version_notice');
function seo_aeo_php_version_notice() {
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-warning"><p><strong>SEO AEO Orchestra:</strong> PHP ' . esc_html(PHP_VERSION) . ' rilevato. Il plugin richiede PHP 7.4+. Contatta il tuo hosting per aggiornare.</p></div>';
        });
    }
}


/* ═════════════════════════════════════════════════════════════════════
 * 3.37.1 — Module 15. Self-cache-invalidation on plugin upgrade.
 *
 * Why: WordPress fires upgrader_process_complete after a successful plugin
 * update, BUT the PHP files newly written to disk are NOT yet visible to
 * the request: PHP OPcache still serves the stale bytecode of the old
 * version until the OPcache TTL expires (default 2s — but premium hosts
 * with revalidate_freq=0 and validate_timestamps=0 may need a full
 * webserver restart). Plus plugin-feature caches (Business Profile,
 * Identity, AI Performance stats) sit in WP transients and may serve
 * pre-update output for hours.
 *
 * What: file-scoped opcache_invalidate() across the plugin tree (NEVER
 * global opcache_reset — that would disrupt unrelated plugins/themes),
 * plus a bulk delete of every transient whose key starts with
 * `_transient_seo_aeo_` (and its companion `_transient_timeout_`),
 * plus delete_site_transient('update_plugins') so the WP update-check
 * banner stops loop-showing "update available" after install.
 *
 * Ships in BOTH paid and wporg builds. (class-updater.php — paid only —
 * still handles its own updater-cache cleanup; the two hooks compose
 * cleanly: this one focuses on bytecode + feature-transients.)
 * ════════════════════════════════════════════════════════════════════ */

function seo_aeo_module15_cache_invalidate($upgrader, $hook_extra) {
    // Only react to plugin updates (not theme / core / translation).
    if (empty($hook_extra['type']) || $hook_extra['type'] !== 'plugin') {
        return;
    }
    if (empty($hook_extra['plugins']) || !is_array($hook_extra['plugins'])) {
        return;
    }
    $this_plugin = plugin_basename(__FILE__);
    if (!in_array($this_plugin, $hook_extra['plugins'], true)) {
        return;
    }

    // (1) OPcache — invalidate each PHP file under this plugin dir.
    $opcache_count = 0;
    if (function_exists('opcache_invalidate') && function_exists('opcache_get_status')) {
        $status = @opcache_get_status(false);
        if (!empty($status['opcache_enabled'])) {
            $plugin_dir = plugin_dir_path(__FILE__);
            try {
                $iter = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($plugin_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                foreach ($iter as $f) {
                    if ($f->isFile() && strtolower($f->getExtension()) === 'php') {
                        if (@opcache_invalidate($f->getPathname(), true)) {
                            $opcache_count++;
                        }
                    }
                }
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG && function_exists('seo_aeo_debug_log')) {
                    seo_aeo_debug_log('Module 15 OPcache invalidate exception: ' . $e->getMessage());
                }
            }
        }
    }

    // (2) Plugin feature transients — enumerate matching keys, then delete via
    //     delete_transient() so both the DB row AND the WP_Object_Cache (in-memory
    //     options group) are invalidated. A raw DELETE FROM {options} would clear
    //     the table but leave a stale cached value visible to the same request.
    $deleted_transients = 0;
    global $wpdb;
    if (isset($wpdb)) {
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $rows = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_seo_aeo_%'"
        );
        // phpcs:enable
        if (is_array($rows)) {
            foreach ($rows as $opt_name) {
                // strip the '_transient_' prefix to get the transient "name"
                if (strpos($opt_name, '_transient_') === 0 && strpos($opt_name, '_transient_timeout_') !== 0) {
                    $tname = substr($opt_name, strlen('_transient_'));
                    if (delete_transient($tname)) {
                        $deleted_transients++;
                    }
                }
            }
        }
        // Also clear the companion _transient_timeout_ rows (delete_transient
        // normally handles these, but be defensive in case any drifted).
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_seo_aeo_%'"
        );
        // phpcs:enable
    }

    // (3) Object cache group flush (Redis/Memcached backends).
    if (function_exists('wp_cache_flush_group')) {
        @wp_cache_flush_group('seo_aeo_orchestra');
    }

    // (4) Force WP update-check refresh — stops the "update available" banner
    //     looping after install on hosts with aggressive object-cache TTLs.
    delete_site_transient('update_plugins');
    if (function_exists('wp_clean_plugins_cache')) {
        wp_clean_plugins_cache(true);
    }

    // (5) Debug log only under WP_DEBUG (no production spam).
    if (defined('WP_DEBUG') && WP_DEBUG && function_exists('seo_aeo_debug_log')) {
        seo_aeo_debug_log(sprintf(
            'Module 15 cache invalidated on upgrade to %s — opcache_files=%d, transients=%d',
            SEO_AEO_VERSION,
            $opcache_count,
            $deleted_transients
        ));
    }
}
add_action('upgrader_process_complete', 'seo_aeo_module15_cache_invalidate', 10, 2);


/* Hidden diagnostic page: WP Admin → URL `?page=seo-aeo-debug-cache`.
 * Not in the menu (parent_slug = null) — only reachable by typing the URL or
 * via the "Cache Debug" link we render at the bottom of Impostazioni.
 * Lets the user inspect cache state + manually re-run Module 15. */

add_action('admin_menu', function () {
    add_submenu_page(
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only menu registration
        null,
        'AEO Orchestra — Cache Debug',
        'Cache Debug',
        'manage_options',
        'seo-aeo-debug-cache',
        'seo_aeo_module15_render_debug_page'
    );
}, 999);

function seo_aeo_module15_render_debug_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Forbidden');
    }

    // Handle force-refresh action.
    $did_refresh = false;
    if (isset($_POST['seo_aeo_action']) && $_POST['seo_aeo_action'] === 'force_refresh') {
        check_admin_referer('seo_aeo_debug_cache');
        seo_aeo_module15_cache_invalidate(null, array(
            'type'    => 'plugin',
            'plugins' => array(plugin_basename(__FILE__)),
        ));
        $did_refresh = true;
    }

    // State inspection.
    $version_const = defined('SEO_AEO_VERSION') ? SEO_AEO_VERSION : '(undefined)';
    $main_file     = __FILE__;
    $main_mtime    = file_exists($main_file) ? gmdate('Y-m-d H:i:s', filemtime($main_file)) . ' UTC' : '(missing)';
    $opcache_status = function_exists('opcache_get_status') ? @opcache_get_status(false) : null;
    $opcache_on    = !empty($opcache_status['opcache_enabled']);
    $opcache_scripts = $opcache_on ? (int) ($opcache_status['opcache_statistics']['num_cached_scripts'] ?? 0) : 0;

    // Count plugin transients currently in DB.
    global $wpdb;
    $transient_count = 0;
    if (isset($wpdb)) {
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $transient_count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_seo_aeo_%'"
        );
        // phpcs:enable
    }
    ?>
    <div class="wrap">
        <h1>🔧 AEO Orchestra — Cache Debug</h1>
        <p style="max-width:760px;color:#555;">
            Pagina diagnostica per investigare lo stato della cache PHP / WordPress quando
            un aggiornamento del plugin non sembra essere applicato. Normalmente non serve —
            il plugin invalida la cache automaticamente al momento dell'update.
        </p>

        <?php if ($did_refresh) : ?>
            <div class="notice notice-success"><p><strong>✓ Cache invalidata.</strong> Ricarica le pagine admin per vedere il risultato.</p></div>
        <?php endif; ?>

        <table class="form-table">
            <tr><th scope="row">Versione (costante)</th><td><code><?php echo esc_html($version_const); ?></code></td></tr>
            <tr><th scope="row">Main file mtime</th><td><code><?php echo esc_html($main_mtime); ?></code></td></tr>
            <tr><th scope="row">OPcache attivo</th><td><?php echo $opcache_on ? '✓ Sì' : '✗ No (o non disponibile)'; ?></td></tr>
            <?php if ($opcache_on) : ?>
                <tr><th scope="row">Script cachati (OPcache)</th><td><?php echo esc_html((string) $opcache_scripts); ?></td></tr>
            <?php endif; ?>
            <tr><th scope="row">Transient plugin in DB</th><td><?php echo esc_html((string) $transient_count); ?></td></tr>
        </table>

        <form method="post">
            <?php wp_nonce_field('seo_aeo_debug_cache'); ?>
            <input type="hidden" name="seo_aeo_action" value="force_refresh" />
            <p>
                <button type="submit" class="button button-primary">🔄 Force Cache Refresh</button>
                <span style="color:#666;margin-left:10px;">
                    Invalida OPcache di tutti i file PHP del plugin + flush transient + ricarica update-check.
                </span>
            </p>
        </form>

        <hr style="margin-top:30px;" />
        <p style="font-size:12px;color:#888;">
            <strong>Quando usarla:</strong> se aggiorni il plugin (manualmente o via WP auto-update)
            e una modifica annunciata in changelog non sembra propagata sul tuo sito —
            tipicamente perché il tuo hosting ha un OPcache aggressivo. Prima di contattare il
            supporto, prova questo pulsante.
        </p>
    </div>
    <?php
}



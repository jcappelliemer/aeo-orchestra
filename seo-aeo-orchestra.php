<?php
// phpcs:disable WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
/**
 * Plugin Name: AEO Orchestra
 * Plugin URI: https://aeo-orchestra.com
 * Description: Plugin SEO + AEO completo: specialisti AI perfettamente orchestrati per meta tags, content generation, schema, llms.txt, sitemap, redirect manager, brand voice e altro.
 * Version: 3.36.7
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

define('SEO_AEO_VERSION', '3.36.7');
define('SEO_AEO_AGENTS_COUNT', 13);  // 3.35.84.2: +Verify-Live  // mirrors backend/helpers/config.py AGENTS_COUNT — bump on every new agent
define('SEO_AEO_TOOLS_COUNT', 22);   // 3.35.84.2: +Verify-Live, Profilo Business, AI Performance, AI Crawlers   // mirrors backend/helpers/config.py TOOLS_COUNT — bump on every new tool
define('SEO_AEO_DIR', plugin_dir_path(__FILE__));
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


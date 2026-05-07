<?php
// phpcs:disable WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
/**
 * Plugin Name: AEO Orchestra
 * Plugin URI: https://aeo-orchestra.com
 * Description: Plugin SEO + AEO completo: specialisti AI perfettamente orchestrati per meta tags, content generation, schema, llms.txt, sitemap, redirect manager, brand voice e altro.
 * Version: 3.35.44
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

define('SEO_AEO_VERSION', '3.35.44');
define('SEO_AEO_AGENTS_COUNT', 12);  // mirrors backend/helpers/config.py AGENTS_COUNT — bump on every new agent
define('SEO_AEO_TOOLS_COUNT', 18);   // mirrors backend/helpers/config.py TOOLS_COUNT — bump on every new tool
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

// SEO_AEO_IS_FREE: true when distributed via WP.org (no AI features, no SaaS backend).
// false (default) for direct distribution from aeo-orchestra.com — full Pro features.
if (!defined('SEO_AEO_IS_FREE')) {
    define('SEO_AEO_IS_FREE', SEO_AEO_DISTRIBUTION_CHANNEL === 'wporg');
}

/*
 * Pro+Free coexistence guard.
 * The WP.org Free build lives in /wp-content/plugins/aeo-orchestra/ while
 * the direct Pro build lives in /wp-content/plugins/seo-aeo-orchestra/.
 * They share option keys (seo_aeo_*) and DB tables, so running both
 * simultaneously emits duplicate <head> output. Only the Free build
 * needs to step aside; if Pro is present, Free auto-deactivates.
 */
if (SEO_AEO_IS_FREE) {
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

// Include classes (safe load)
try {
    if (!SEO_AEO_IS_FREE) { require_once SEO_AEO_DIR . 'includes/class-api-client.php'; }
    if (!SEO_AEO_IS_FREE) { require_once SEO_AEO_DIR . 'includes/class-ai-helpers.php'; } // 3.35.8: shared utilities (attach_featured_image, mark_ai_generated, build_inline_image_block)
    require_once SEO_AEO_DIR . 'includes/class-admin-ui.php';
    require_once SEO_AEO_DIR . 'includes/class-ajax-handlers.php';
    require_once SEO_AEO_DIR . 'includes/class-widget.php';
    require_once SEO_AEO_DIR . 'includes/class-history.php';
    if (!SEO_AEO_IS_FREE) { require_once SEO_AEO_DIR . 'includes/class-usage-tracker.php'; }
    if (SEO_AEO_DISTRIBUTION_CHANNEL !== 'wporg') {
        require_once SEO_AEO_DIR . 'includes/class-updater.php';
    }
    require_once SEO_AEO_DIR . 'includes/class-snapshot-manager.php';
    require_once SEO_AEO_DIR . 'includes/class-seo-engine-bridge.php';
    require_once SEO_AEO_DIR . 'includes/class-output-renderer.php';
    require_once SEO_AEO_DIR . 'includes/class-sitemap.php';
    require_once SEO_AEO_DIR . 'includes/class-llms-txt.php';
    require_once SEO_AEO_DIR . 'includes/class-schema.php';
    require_once SEO_AEO_DIR . 'includes/class-redirect-manager.php';
    require_once SEO_AEO_DIR . 'includes/class-migration-wizard.php';
    if (!SEO_AEO_IS_FREE) { require_once SEO_AEO_DIR . 'includes/class-brand-voice.php'; }
    if (!SEO_AEO_IS_FREE) { require_once SEO_AEO_DIR . 'includes/class-autopilot.php'; }
    require_once SEO_AEO_DIR . 'includes/class-translator.php';
    if (!SEO_AEO_IS_FREE) { require_once SEO_AEO_DIR . 'includes/class-credits-bar.php'; }
    if (!SEO_AEO_IS_FREE) { require_once SEO_AEO_DIR . 'includes/class-calendar.php'; }
    if (!SEO_AEO_IS_FREE) { require_once SEO_AEO_DIR . 'includes/class-image-seo.php'; }
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

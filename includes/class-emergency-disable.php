<?php
/**
 * 3.35.65: Emergency disable mode.
 *
 * Activated by adding `define('AEO_ORCHESTRA_EMERGENCY_DISABLE', true);` in wp-config.php.
 *
 * Effect: plugin loads MA non registra hooks su wp_head, the_content, template_redirect,
 * o frontend output. Solo l'admin del plugin resta accessibile per diagnosi.
 *
 * Less drastic than renaming the plugin folder: lets James diagnose via admin_notices
 * and the debug snapshot REST endpoint without losing access to settings.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Emergency_Disable {

    /**
     * Returns true if emergency disable is active.
     */
    public static function is_active() {
        return defined('AEO_ORCHESTRA_EMERGENCY_DISABLE') && AEO_ORCHESTRA_EMERGENCY_DISABLE === true;
    }

    /**
     * Setup admin_notice when active. Runs only in admin context.
     */
    public static function init_admin_notice() {
        if (!self::is_active()) return;
        add_action('admin_notices', array(__CLASS__, 'render_notice'));
    }

    public static function render_notice() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>AEO Orchestra — Emergency disable attivo.</strong> ';
        echo 'Tutti gli hook frontend (wp_head, output_renderer, schema, sitemap, llms.txt, redirect) sono disabilitati. ';
        echo 'L\'admin del plugin resta accessibile per diagnosi. ';
        echo 'Per riattivare: rimuovi <code>define(\'AEO_ORCHESTRA_EMERGENCY_DISABLE\', true);</code> da <code>wp-config.php</code>.</p>';
        echo '</div>';
    }
}

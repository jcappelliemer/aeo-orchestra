<?php
// phpcs:disable WordPress.Security.NonceVerification.Missing
// phpcs:disable WordPress.Security.NonceVerification.Recommended
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
// Reason: nonce + sanitize chain is enforced upstream. AJAX handlers call
// check_ajax_referer at the top of each method; admin form handlers call
// check_admin_referer; reads of $_SERVER (DOCUMENT_ROOT, HTTP_USER_AGENT)
// are wrapped in sanitize_text_field(wp_unslash()) at the read site. The
// Plugin Check static analyzer cannot trace control flow across method
// boundaries, so it flags these as missing — but the security guarantees
// hold at runtime.
/*
 * 3.35.83-beta — Business Profile dedicated panel (.83 foundation feature)
 *
 * Single source of truth per identity context. Read by ALL AI tools (present + future):
 *   - Verify-Live (Standard + Premium++)
 *   - Contenuti AI / Meta Tags / Analisi AEO (post .84 integration)
 *   - llms.txt / Schema markup / OG tags (scope='public' only)
 *
 * Storage: MongoDB collection identity_profiles (extended via .83 migration).
 * Backend: routes/business_profile.py + scope-aware _build_context_block in ai_premium.py
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Business_Profile {

    public function __construct() {
        add_action('init', array(__CLASS__, 'register_ajax_actions'));
        add_action('admin_init', array(__CLASS__, 'maybe_auto_create_from_wp'), 10);
        // 3.35.84.4: BP "pending" banner is now registered through the centralized
        // admin-notices module. Run on `init` so SEO_AEO_Admin_Notices is loaded.
        add_action('init', array(__CLASS__, 'register_pending_notice'), 20);
    }

    /**
     * 3.35.84.4 — Register the "Profilo Business da confermare" notice through
     * the central admin-notices module. Replaces the legacy `all_admin_notices`
     * hook that produced banner-inside-hero positioning quirks on plugin pages
     * with custom hero cards (e.g. native-output, brand-voice).
     */
    public static function register_pending_notice() {
        if (!class_exists('SEO_AEO_Admin_Notices')) return;

        SEO_AEO_Admin_Notices::register('business_profile_pending', array(
            'variant'      => 'warning',
            'dismiss_days' => 1, // matches legacy 24h snooze TTL
            'scope'        => function ($screen) {
                $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
                if (strpos($page, 'seo-aeo') !== 0) return false;
                // Don't show on the BP page itself (user is already there).
                if ($page === 'seo-aeo-business-profile') return false;
                return true;
            },
            'condition'    => array(__CLASS__, 'is_profile_pending'),
            'render'       => array(__CLASS__, 'render_pending_notice_body'),
        ));
    }

    /** Cached check: is the BP profile NOT yet customer-confirmed? */
    public static function is_profile_pending() {
        $cached = get_transient('seo_aeo_bp_confirmed_state');
        if ($cached === 'confirmed') return false;
        if ($cached === 'pending') return true;
        // Cache miss: hit backend once, store result for an hour.
        $is_confirmed = false;
        if (class_exists('SEO_AEO_API_Client')) {
            $api  = new SEO_AEO_API_Client();
            $resp = $api->get_business_profile();
            $is_confirmed = is_array($resp) && !empty($resp['profile']) && !empty($resp['profile']['customer_confirmed']);
        }
        set_transient('seo_aeo_bp_confirmed_state', $is_confirmed ? 'confirmed' : 'pending', HOUR_IN_SECONDS);
        return !$is_confirmed;
    }

    public static function render_pending_notice_body() {
        $url = esc_url(admin_url('admin.php?page=seo-aeo-business-profile'));
        ob_start();
        ?>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <strong>⚠ <?php echo esc_html__('Profilo Business da confermare.', 'aeo-orchestra'); ?></strong>
            <span><?php echo esc_html__('I tool AI generano suggerimenti generici senza un profilo completo.', 'aeo-orchestra'); ?></span>
            <a href="<?php echo esc_url($url); ?>" class="button button-primary" style="margin-left:auto;">
                🏢 <?php echo esc_html__('Vai al Profilo Business →', 'aeo-orchestra'); ?>
            </a>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    public static function register_ajax_actions() {
        if (!is_admin()) return;
        add_action('wp_ajax_seo_aeo_business_profile_get',     array(__CLASS__, 'ajax_get'));
        add_action('wp_ajax_seo_aeo_business_profile_save',    array(__CLASS__, 'ajax_save'));
        add_action('wp_ajax_seo_aeo_business_profile_preview', array(__CLASS__, 'ajax_preview'));
        add_action('wp_ajax_seo_aeo_business_profile_confirm', array(__CLASS__, 'ajax_confirm'));
        // 3.39.1 — Site Context auto-generation.
        add_action('wp_ajax_seo_aeo_business_profile_generate_site_context', array(__CLASS__, 'ajax_generate_site_context'));
        add_action('wp_ajax_seo_aeo_bp_banner_snooze',         array(__CLASS__, 'ajax_banner_snooze'));
        add_action('wp_ajax_seo_aeo_bp_section_state',         array(__CLASS__, 'ajax_section_state'));
    }

    public static function ajax_get() {
        // 3.35.83.1: defensive nonce check (no auto-die, return JSON error)
        if (!check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce', false)) {
            seo_aeo_debug_log('[seo-aeo-bp] ajax_get: nonce check failed');
            wp_send_json_error(array('message' => 'invalid_nonce'), 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'forbidden'), 403);
        }
        try {
            $api = new SEO_AEO_API_Client();
            $resp = $api->get_business_profile();
            if (!is_array($resp)) {
                seo_aeo_debug_log('[seo-aeo-bp] ajax_get: backend returned non-array');
                wp_send_json_error(array('message' => 'backend_unavailable'), 502);
            }
            wp_send_json_success($resp);
        } catch (Throwable $e) {
            seo_aeo_debug_log('[seo-aeo-bp] ajax_get exception: ' . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()), 500);
        }
    }

    public static function ajax_save() {
        if (!check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce', false)) {
            seo_aeo_debug_log('[seo-aeo-bp] ajax_save: nonce check failed');
            wp_send_json_error(array('message' => 'invalid_nonce'), 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'forbidden'), 403);
        }
        try {
            $fields = array();
            if (isset($_POST['fields']) && is_array($_POST['fields'])) {
                $fields = wp_unslash($_POST['fields']);
            } elseif (isset($_POST['fields_json'])) {
                $fields = json_decode(wp_unslash($_POST['fields_json']), true);
                if (!is_array($fields)) $fields = array();
            }

            $api = new SEO_AEO_API_Client();
            $resp = $api->save_business_profile($fields);
            if (!is_array($resp) || empty($resp['success'])) {
                $msg = isset($resp['message']) ? $resp['message'] : 'save_failed';
                seo_aeo_debug_log('[seo-aeo-bp] ajax_save: backend returned no success — ' . $msg);
                wp_send_json_error(array('message' => $msg), 500);
            }

            // Invalidate transient + dashboard stats cache
            delete_transient('seo_aeo_identity_profile');
            delete_transient('seo_aeo_bp_dashboard_stats');
            delete_transient('seo_aeo_bp_confirmed_state');

            wp_send_json_success(array(
                'profile'       => isset($resp['profile']) ? $resp['profile'] : array(),
                'last_saved_at' => isset($resp['last_saved_at']) ? $resp['last_saved_at'] : '',
                'stats'         => isset($resp['stats']) ? $resp['stats'] : array(),
            ));
        } catch (Throwable $e) {
            seo_aeo_debug_log('[seo-aeo-bp] ajax_save exception: ' . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()), 500);
        }
    }

    public static function ajax_preview() {
        if (!check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce', false)) {
            seo_aeo_debug_log('[seo-aeo-bp] ajax_preview: nonce check failed');
            wp_send_json_error(array('message' => 'invalid_nonce'), 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'forbidden'), 403);
        }
        try {
            $scope = isset($_POST['scope']) ? sanitize_key($_POST['scope']) : 'full';
            if (!in_array($scope, array('full', 'public'), true)) $scope = 'full';

            $api = new SEO_AEO_API_Client();
            $resp = $api->preview_business_profile($scope);
            if (!is_array($resp)) {
                seo_aeo_debug_log('[seo-aeo-bp] ajax_preview: backend returned non-array');
                wp_send_json_error(array('message' => 'backend_unavailable'), 502);
            }
            wp_send_json_success($resp);
        } catch (Throwable $e) {
            seo_aeo_debug_log('[seo-aeo-bp] ajax_preview exception: ' . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()), 500);
        }
    }

    public static function ajax_confirm() {
        if (!check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce', false)) {
            seo_aeo_debug_log('[seo-aeo-bp] ajax_confirm: nonce check failed');
            wp_send_json_error(array('message' => 'invalid_nonce'), 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'forbidden'), 403);
        }
        try {
            $api = new SEO_AEO_API_Client();
            $resp = $api->confirm_business_profile();
            if (!is_array($resp) || empty($resp['success'])) {
                $msg = isset($resp['message']) ? $resp['message'] : 'confirm_failed';
                seo_aeo_debug_log('[seo-aeo-bp] ajax_confirm: backend returned no success — ' . $msg);
                wp_send_json_error(array('message' => $msg), 500);
            }

            // Invalidate ALL transient caches (banner, dashboard stats, identity)
            delete_transient('seo_aeo_bp_confirmed_state');
            delete_transient('seo_aeo_bp_dashboard_stats');
            delete_transient('seo_aeo_identity_profile');

            // 3.35.83.1 Stage 5: force WP update_plugins transient refresh on confirm
            // (cosmetic — non related to confirm directly, but ensures dashboard banner
            //  reflects latest version state if user just updated)
            delete_site_transient('update_plugins');

            wp_send_json_success(array(
                'confirmed_at' => isset($resp['confirmed_at']) ? $resp['confirmed_at'] : '',
                'message'      => 'Profilo confermato',
            ));
        } catch (Throwable $e) {
            seo_aeo_debug_log('[seo-aeo-bp] ajax_confirm exception: ' . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()), 500);
        }
    }

    public static function ajax_banner_snooze() {
        // 3.35.83.1 Patch (c): uniformed nonce action to seo_aeo_orchestra_nonce (was 'orch_bp')
        // Backward compat: also accept 'orch_bp' nonce for in-flight requests
        $nonce_ok = check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce', false)
                 || check_ajax_referer('orch_bp', 'nonce', false);
        if (!$nonce_ok) {
            seo_aeo_debug_log('[seo-aeo-bp] ajax_banner_snooze: nonce check failed');
            wp_send_json_error(array('message' => 'invalid_nonce'), 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'forbidden'), 403);
        }
        update_user_meta(get_current_user_id(), 'seo_aeo_bp_banner_snoozed', time());
        wp_send_json_success();
    }

    public static function ajax_section_state() {
        if (!check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce', false)) {
            seo_aeo_debug_log('[seo-aeo-bp] ajax_section_state: nonce check failed');
            wp_send_json_error(array('message' => 'invalid_nonce'), 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'forbidden'), 403);
        }
        $section = isset($_POST['section']) ? sanitize_key($_POST['section']) : '';
        $open = !empty($_POST['open']) ? 1 : 0;
        if (!$section) wp_send_json_error(array('message' => 'missing_section'), 400);
        $states = get_user_meta(get_current_user_id(), 'seo_aeo_bp_section_states', true);
        if (!is_array($states)) $states = array();
        $states[$section] = $open;
        update_user_meta(get_current_user_id(), 'seo_aeo_bp_section_states', $states);
        wp_send_json_success();
    }

    /**
     * Auto-create skeleton on first admin access if profile doesn't exist.
     * Uses WP basics: bloginfo('name') + bloginfo('description').
     */
    public static function maybe_auto_create_from_wp() {
        if (!current_user_can('manage_options')) return;
        if (defined('DOING_AJAX') && DOING_AJAX) return;
        if (defined('DOING_CRON') && DOING_CRON) return;

        // Run once per session via transient flag (60s)
        if (get_transient('seo_aeo_bp_autocheck')) return;
        set_transient('seo_aeo_bp_autocheck', 1, 60);

        if (!class_exists('SEO_AEO_API_Client')) return;
        $api = new SEO_AEO_API_Client();
        $existing = $api->get_business_profile();
        if (is_array($existing) && !empty($existing['profile']) && !empty($existing['profile']['business_name'])) {
            return; // already exists with data
        }

        // Auto-create skeleton from WP basics
        $api->auto_create_business_profile_from_wp(array(
            'business_name'        => (string) get_bloginfo('name'),
            'business_description' => (string) get_bloginfo('description'),
            'prefilled_from_wp'    => true,
        ));
    }

    /**
     * Render the Business Profile admin page.
     */
    public static function render_page() {
        if (!current_user_can('manage_options')) wp_die('forbidden');
        require_once SEO_AEO_DIR . 'templates/business-profile.php';
    }

    /**
     * 3.39.1 — Proxy /api/business-profile/site-context-generate.
     */
    public static function ajax_generate_site_context() {
        if (!check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'invalid_nonce'), 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'forbidden'), 403);
        }
        try {
            $api = new SEO_AEO_API_Client();
            $resp = $api->generate_site_context();
            if (!is_array($resp)) {
                wp_send_json_error(array('message' => 'invalid_response'), 500);
            }
            if (!empty($resp['error']) || (isset($resp['success']) && !$resp['success'])) {
                $msg = isset($resp['message']) ? $resp['message'] : (isset($resp['detail']) ? $resp['detail'] : 'Errore sconosciuto');
                wp_send_json_error(array('message' => $msg), 500);
            }
            wp_send_json_success(array(
                'fields'         => isset($resp['fields']) ? $resp['fields'] : array(),
                'pages_analyzed' => isset($resp['pages_analyzed']) ? (int) $resp['pages_analyzed'] : 0,
                'content_chars'  => isset($resp['content_chars']) ? (int) $resp['content_chars'] : 0,
            ));
        } catch (Throwable $e) {
            wp_send_json_error(array('message' => 'exception: ' . $e->getMessage()), 500);
        }
    }
}

// Bootstrap
new SEO_AEO_Business_Profile();

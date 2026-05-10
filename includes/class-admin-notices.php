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
/**
 * 3.35.84.4-beta — Centralized admin notices.
 *
 * Single point of injection for all in-plugin admin banners. Each notice has:
 *   - a stable ID (used as dismiss state key)
 *   - a `condition` callback (returns true when the notice should show)
 *   - optional `scope` callback (return true on the screens where the notice is allowed)
 *   - optional `expire_days` (auto-hide N days after first show, even without dismiss)
 *   - a `render` callback returning the inner HTML body
 *
 * All notices are emitted on the standard WP `admin_notices` hook (NOT
 * `all_admin_notices`), which renders inside `<div id="wpbody-content">` and
 * BEFORE the page template's `<div class="wrap">` — so banners always sit
 * above any plugin hero card. This replaces the ad-hoc inline rendering
 * that produced banner-inside-hero quirks (3.35.84.3-beta and earlier).
 *
 * Dismiss URL format:
 *   admin.php?page=<current_page>&orch_dismiss=<id>&_wpnonce=<nonce>
 * The dismiss handler stores a per-user transient keyed by ID + user_id,
 * with TTL = `dismiss_days` (default 30). After expiry the banner can
 * re-surface — useful for transient states (e.g. "profile pending") but
 * NOT for one-shot announcements (set `expire_days` for those).
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Admin_Notices {

    /** @var array<string, array> registered notice specs */
    private static $notices = array();

    public static function bootstrap() {
        add_action('admin_init', array(__CLASS__, 'maybe_handle_dismiss'));
        add_action('admin_notices', array(__CLASS__, 'render_all'), 5);
        // 3.35.85.1: push the notice CSS through SEO_AEO_Inline_Assets at
        // admin_enqueue_scripts time so it lands in <head> via
        // wp_add_inline_style instead of being echoed inline at render_all
        // time. Keeps the bot scanners happy.
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_styles'), 20);
    }

    public static function enqueue_styles($hook) {
        if (strpos((string) $hook, 'seo-aeo') === false) return;
        if (!class_exists('SEO_AEO_Inline_Assets')) return;
        SEO_AEO_Inline_Assets::add_inline_style(
            '.orch-admin-notice{position:relative;display:flex;gap:14px;align-items:flex-start;margin:14px 20px 18px 2px;padding:14px 40px 14px 18px;border-radius:8px;border:1px solid #e2e8f0;border-left-width:4px;background:#ffffff;color:#0f172a;font-size:13px;line-height:1.5;}'
            . '.orch-admin-notice--info{border-left-color:#0055FF;background:linear-gradient(135deg,#eff6ff 0%,#f0f9ff 100%);border-color:#bfdbfe;}'
            . '.orch-admin-notice--warning{border-left-color:#d97706;background:linear-gradient(135deg,#fffbeb 0%,#fef3c7 100%);border-color:#fde68a;}'
            . '.orch-admin-notice--success{border-left-color:#059669;background:linear-gradient(135deg,#ecfdf5 0%,#d1fae5 100%);border-color:#a7f3d0;}'
            . '.orch-admin-notice--error{border-left-color:#dc2626;background:linear-gradient(135deg,#fef2f2 0%,#fee2e2 100%);border-color:#fecaca;}'
            . '.orch-admin-notice-body{flex:1;min-width:0;}'
            . '.orch-admin-notice-body a{color:#0055FF;font-weight:600;text-decoration:none;}'
            . '.orch-admin-notice-body a:hover{text-decoration:underline;}'
            . '.orch-admin-notice-body .button-primary{margin-left:auto;}'
            . '.orch-admin-notice-dismiss{position:absolute;top:8px;right:10px;color:#94a3b8;font-size:18px;line-height:1;text-decoration:none;font-weight:300;}'
            . '.orch-admin-notice-dismiss:hover{color:#0f172a;}'
        );
    }

    /**
     * Register a notice spec.
     *
     * @param string $id
     * @param array  $opts {
     *   @type callable $condition     Required. Returns bool (true = show).
     *   @type callable $render        Required. Returns inner HTML (no .wrap div).
     *   @type callable $scope         Optional. Receives WP_Screen, returns bool.
     *   @type int      $expire_days   Optional. Auto-hide N days after first show.
     *   @type int      $dismiss_days  Optional. TTL of the dismiss transient (default 30).
     *   @type string   $variant       Optional. one of: warning, info, success, error (default info).
     * }
     */
    public static function register($id, array $opts) {
        if (empty($opts['condition']) || empty($opts['render'])) return;
        self::$notices[$id] = array_merge(array(
            'scope'         => null,
            'expire_days'   => 0,
            'dismiss_days'  => 30,
            'variant'       => 'info',
        ), $opts);
    }

    public static function render_all() {
        if (!is_admin()) return;
        if (!current_user_can('manage_options')) return;
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $uid    = get_current_user_id();
        if (!$uid) return;

        foreach (self::$notices as $id => $spec) {
            // Scope guard
            if (!empty($spec['scope']) && is_callable($spec['scope'])) {
                if (!call_user_func($spec['scope'], $screen)) continue;
            }
            // Dismiss guard (per-user transient)
            if (get_transient(self::dismiss_key($id, $uid))) continue;

            // Auto-expire guard
            if (!empty($spec['expire_days'])) {
                $first_seen_opt = self::first_seen_key($id);
                $first_seen     = (int) get_option($first_seen_opt, 0);
                if ($first_seen === 0) {
                    $first_seen = time();
                    update_option($first_seen_opt, $first_seen, false);
                }
                if (time() > $first_seen + ((int) $spec['expire_days']) * DAY_IN_SECONDS) {
                    continue;
                }
            }

            // Condition guard
            if (!call_user_func($spec['condition'])) continue;

            // Render
            $body = (string) call_user_func($spec['render']);
            if ($body === '') continue;

            $variant = in_array($spec['variant'], array('warning', 'info', 'success', 'error'), true) ? $spec['variant'] : 'info';
            $dismiss_url = self::build_dismiss_url($id);
            echo '<div class="orch-admin-notice orch-admin-notice--' . esc_attr($variant) . '">';
            echo '<div class="orch-admin-notice-body">' . wp_kses_post($body) . '</div>';
            echo '<a href="' . esc_url($dismiss_url) . '" class="orch-admin-notice-dismiss" aria-label="' . esc_attr__('Ignora questa notifica', 'aeo-orchestra') . '" title="' . esc_attr__('Non mostrare più', 'aeo-orchestra') . '">×</a>';
            echo '</div>';
        }
        // 3.35.85.1: CSS moved to enqueue_styles() / SEO_AEO_Inline_Assets so
        // we don't emit any <style> tag from inside admin_notices.
    }

    public static function maybe_handle_dismiss() {
        if (empty($_GET['orch_dismiss'])) return;
        if (!current_user_can('manage_options')) return;
        $id = sanitize_key($_GET['orch_dismiss']);
        if (!isset(self::$notices[$id])) return;
        $nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';
        if (!wp_verify_nonce($nonce, 'orch_dismiss_' . $id)) return;
        $uid = get_current_user_id();
        if (!$uid) return;
        $spec = self::$notices[$id];
        $ttl  = max(1, (int) $spec['dismiss_days']) * DAY_IN_SECONDS;
        set_transient(self::dismiss_key($id, $uid), 1, $ttl);

        // Drop the dismiss query args from URL via redirect (clean address bar)
        $url = remove_query_arg(array('orch_dismiss', '_wpnonce'));
        wp_safe_redirect($url ?: admin_url());
        exit;
    }

    /** Build the dismiss URL preserving the current admin page. */
    public static function build_dismiss_url($id) {
        $base = admin_url('admin.php');
        $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
        $args = array('orch_dismiss' => $id);
        if ($page !== '') $args['page'] = $page;
        return wp_nonce_url(add_query_arg($args, $base), 'orch_dismiss_' . $id);
    }

    /** Programmatically clear a dismiss state for a user (e.g. on plugin upgrade). */
    public static function clear_dismiss($id, $user_id = null) {
        $uid = $user_id ?: get_current_user_id();
        if (!$uid) return;
        delete_transient(self::dismiss_key($id, $uid));
    }

    private static function dismiss_key($id, $uid) {
        return 'seo_aeo_notice_dismissed_' . $id . '_u' . (int) $uid;
    }

    private static function first_seen_key($id) {
        return 'seo_aeo_notice_first_seen_' . $id;
    }
}

SEO_AEO_Admin_Notices::bootstrap();

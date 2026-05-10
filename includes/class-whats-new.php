<?php
/**
 * 3.35.84.4-beta — Dynamic "What's new" section.
 *
 * Reads `== Changelog ==` from readme.txt and returns the entry matching
 * SEO_AEO_VERSION (with `-beta` / `-alpha` etc. stripped). The dashboard
 * (templates/wizard-home.php) renders the result; the hardcoded v3.20
 * bullets that previously lived inline are gone.
 *
 * Dismiss state: per-user-per-version transient (30 days). When a new
 * release ships, SEO_AEO_VERSION changes → the transient key changes →
 * the section reappears with the new entry.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Whats_New {

    const TRANSIENT_PREFIX = 'seo_aeo_whatsnew_dismissed_';
    const DISMISS_DAYS     = 30;
    const MAX_BULLETS      = 5;

    public static function bootstrap() {
        add_action('admin_init', array(__CLASS__, 'maybe_handle_dismiss'));
    }

    /**
     * Returns the parsed entry for the current plugin version.
     *
     * @return array|null { 'version' => string, 'bullets' => string[] } or null when no match.
     */
    public static function current_entry() {
        $version = defined('SEO_AEO_VERSION') ? SEO_AEO_VERSION : '';
        if (!$version) return null;
        return self::entry_for_version($version);
    }

    public static function entry_for_version($version) {
        $stripped = self::strip_prerelease_suffix($version);
        $readme   = self::load_readme();
        if ($readme === '') return null;

        // Locate `== Changelog ==`
        $pos = strpos($readme, '== Changelog ==');
        if ($pos === false) return null;
        $changelog = substr($readme, $pos);

        // Try exact match `= <stripped> =` first, then fall back to the version
        // with the `-beta` suffix (in case readme uses the suffixed form).
        $candidates = array($stripped, $version);
        foreach ($candidates as $needle) {
            $entry = self::parse_entry($changelog, $needle);
            if ($entry !== null) return $entry;
        }
        return null;
    }

    /** Has the current user dismissed the section for the current version? */
    public static function is_dismissed_for_current_user() {
        $version = defined('SEO_AEO_VERSION') ? SEO_AEO_VERSION : '';
        $uid     = get_current_user_id();
        if (!$version || !$uid) return false;
        return (bool) get_transient(self::transient_key($uid, $version));
    }

    /** Build a clean dismiss URL preserving the current admin page. */
    public static function dismiss_url() {
        $base = admin_url('admin.php');
        $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : 'seo-aeo-orchestra';
        $args = array('page' => $page, 'orch_whatsnew_dismiss' => '1');
        return wp_nonce_url(add_query_arg($args, $base), 'orch_whatsnew_dismiss');
    }

    public static function maybe_handle_dismiss() {
        if (empty($_GET['orch_whatsnew_dismiss'])) return;
        if (!current_user_can('manage_options')) return;
        $nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';
        if (!wp_verify_nonce($nonce, 'orch_whatsnew_dismiss')) return;
        $uid     = get_current_user_id();
        $version = defined('SEO_AEO_VERSION') ? SEO_AEO_VERSION : '';
        if ($uid && $version) {
            set_transient(self::transient_key($uid, $version), 1, self::DISMISS_DAYS * DAY_IN_SECONDS);
        }
        $url = remove_query_arg(array('orch_whatsnew_dismiss', '_wpnonce'));
        wp_safe_redirect($url ?: admin_url('admin.php?page=seo-aeo-orchestra'));
        exit;
    }

    // ─── internals ──────────────────────────────────────────────────────────

    private static function strip_prerelease_suffix($v) {
        // 3.35.84.3.1-beta → 3.35.84.3.1; 3.35.0-rc1 → 3.35.0
        return preg_replace('/-(beta|alpha|rc\d*|dev).*$/i', '', (string) $v);
    }

    private static function load_readme() {
        $path = defined('SEO_AEO_DIR') ? SEO_AEO_DIR . 'readme.txt' : '';
        if (!$path || !is_readable($path)) return '';
        // Cache parsed file once per request via static.
        static $cached = null;
        if ($cached === null) {
            $cached = (string) @file_get_contents($path);
        }
        return $cached;
    }

    private static function parse_entry($changelog, $version) {
        // Match `= <version> =` at the start of a line.
        $needle = "= " . $version . " =";
        $start  = strpos($changelog, $needle);
        if ($start === false) return null;
        // Scan forward to next `= ` heading or end-of-string.
        $body_start = $start + strlen($needle);
        $next_head  = preg_match('/^=\s+\S/m', substr($changelog, $body_start), $m, PREG_OFFSET_CAPTURE);
        $body       = $next_head ? substr($changelog, $body_start, $m[0][1]) : substr($changelog, $body_start);

        // Collect bullets ("* ..." or "- ...").
        $bullets = array();
        foreach (preg_split('/\r?\n/', $body) as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if ($line[0] !== '*' && $line[0] !== '-') continue;
            $bullets[] = ltrim(substr($line, 1));
            if (count($bullets) >= self::MAX_BULLETS) break;
        }
        if (empty($bullets)) return null;
        return array(
            'version' => $version,
            'bullets' => $bullets,
        );
    }

    private static function transient_key($uid, $version) {
        return self::TRANSIENT_PREFIX . (int) $uid . '_' . md5($version);
    }
}

SEO_AEO_Whats_New::bootstrap();

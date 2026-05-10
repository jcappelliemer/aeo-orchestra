<?php
/**
 * 3.35.65: Debug snapshot — captures plugin-scoped PHP errors for remote diagnosis.
 *
 * Custom set_error_handler() registered at plugin init.
 * Errors are filtered to those originating from /wp-content/plugins/seo-aeo-orchestra/
 * and stored in WP option seo_aeo_error_log (FIFO, max 100 entries).
 *
 * Exposed via REST: GET /wp-json/aeo-orchestra/v1/debug-snapshot
 * Auth: Authorization: Bearer {license_key}
 *
 * Use case: Aruba and other shared hosting suppress display_errors and make
 * /wp-content/debug.log unreadable. This endpoint gives ops a way to read the
 * last 100 plugin errors over HTTP without FTP/cPanel access.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Debug_Snapshot {

    const OPTION_KEY = 'seo_aeo_error_log';
    const MAX_ENTRIES = 100;
    const PLUGIN_PATH_FRAGMENT = 'seo-aeo-orchestra';

    public static function init() {
        // 3.36.1 (WP.org B.4): WP_DEBUG only — diagnostic error trap is admin/dev-side
        // only. In production (WP_DEBUG=false) we never install the handler, so the
        // Plugin Check `set_error_handler` warning never fires for end users.
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        // Register custom error handler — non-destructive, chains to previous
        $prev = set_error_handler(array(__CLASS__, 'handle_error'));
        // Store previous handler so we don't break other plugins' handlers
        $GLOBALS['seo_aeo_prev_error_handler'] = $prev;

        // Register shutdown handler for fatal errors (E_ERROR not catchable by set_error_handler)
        register_shutdown_function(array(__CLASS__, 'handle_shutdown'));

        // REST endpoint
        add_action('rest_api_init', array(__CLASS__, 'register_rest'));
    }

    /**
     * Custom error handler. Captures plugin-scoped errors only.
     * Returns false so PHP default handler (and previous handler if any) still runs.
     */
    public static function handle_error($errno, $errstr, $errfile, $errline) {
        if (strpos((string) $errfile, self::PLUGIN_PATH_FRAGMENT) !== false) {
            self::append_entry(array(
                'level'   => self::level_label($errno),
                'message' => self::truncate((string) $errstr, 500),
                'file'    => self::sanitize_path((string) $errfile),
                'line'    => (int) $errline,
                'time'    => gmdate('c'),
            ));
        }

        // Chain to previous handler if set
        if (!empty($GLOBALS['seo_aeo_prev_error_handler']) && is_callable($GLOBALS['seo_aeo_prev_error_handler'])) {
            return call_user_func($GLOBALS['seo_aeo_prev_error_handler'], $errno, $errstr, $errfile, $errline);
        }

        return false;
    }

    /**
     * Shutdown handler — captures fatal errors that set_error_handler misses.
     */
    public static function handle_shutdown() {
        $err = error_get_last();
        if (!$err) return;
        if (!in_array($err['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR), true)) return;
        if (strpos((string) $err['file'], self::PLUGIN_PATH_FRAGMENT) === false) return;

        self::append_entry(array(
            'level'   => 'FATAL',
            'message' => self::truncate((string) $err['message'], 500),
            'file'    => self::sanitize_path((string) $err['file']),
            'line'    => (int) $err['line'],
            'time'    => gmdate('c'),
        ));
    }

    private static function append_entry($entry) {
        $log = get_option(self::OPTION_KEY, array());
        if (!is_array($log)) $log = array();
        $log[] = $entry;
        if (count($log) > self::MAX_ENTRIES) {
            $log = array_slice($log, -self::MAX_ENTRIES);
        }
        update_option(self::OPTION_KEY, $log, false);
    }

    private static function level_label($errno) {
        $map = array(
            E_ERROR             => 'E_ERROR',
            E_WARNING           => 'E_WARNING',
            E_PARSE             => 'E_PARSE',
            E_NOTICE            => 'E_NOTICE',
            E_CORE_ERROR        => 'E_CORE_ERROR',
            E_CORE_WARNING      => 'E_CORE_WARNING',
            E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
            E_USER_ERROR        => 'E_USER_ERROR',
            E_USER_WARNING      => 'E_USER_WARNING',
            E_USER_NOTICE       => 'E_USER_NOTICE',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED        => 'E_DEPRECATED',
            E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
        );
        return isset($map[$errno]) ? $map[$errno] : 'UNKNOWN_' . (int) $errno;
    }

    private static function sanitize_path($path) {
        $abspath = defined('ABSPATH') ? ABSPATH : '';
        if ($abspath && strpos($path, $abspath) === 0) {
            return substr($path, strlen($abspath));
        }
        return basename($path);
    }

    private static function truncate($s, $len) {
        if (strlen($s) <= $len) return $s;
        return substr($s, 0, $len) . '...[truncated]';
    }

    public static function register_rest() {
        register_rest_route('aeo-orchestra/v1', '/debug-snapshot', array(
            'methods'             => 'GET',
            'callback'            => array(__CLASS__, 'rest_handler'),
            'permission_callback' => array(__CLASS__, 'rest_auth'),
        ));
    }

    public static function rest_auth($request) {
        $auth = $request->get_header('authorization');
        if (!$auth) return new WP_Error('no_auth', 'Missing Authorization header', array('status' => 401));
        if (stripos($auth, 'Bearer ') !== 0) return new WP_Error('bad_auth', 'Expected Bearer token', array('status' => 401));
        $token = trim(substr($auth, 7));

        $stored = '';
        if (function_exists('get_option')) {
            $stored = (string) get_option('seo_aeo_license_key', '');
        }
        if (!$stored || !hash_equals($stored, $token)) {
            return new WP_Error('forbidden', 'Invalid license key', array('status' => 403));
        }
        return true;
    }

    public static function rest_handler($request) {
        $log = get_option(self::OPTION_KEY, array());
        if (!is_array($log)) $log = array();

        return rest_ensure_response(array(
            'ok'              => true,
            'plugin_version'  => defined('SEO_AEO_VERSION') ? SEO_AEO_VERSION : 'unknown',
            'php_version'     => PHP_VERSION,
            'wp_version'      => function_exists('get_bloginfo') ? get_bloginfo('version') : 'unknown',
            'site_url'        => function_exists('home_url') ? home_url() : '',
            'emergency_mode'  => class_exists('SEO_AEO_Emergency_Disable') ? SEO_AEO_Emergency_Disable::is_active() : false,
            'errors'          => $log,
            'errors_count'    => count($log),
            'snapshot_at'     => gmdate('c'),
        ));
    }

    /**
     * Manual flush — for admin "clear log" button.
     */
    public static function clear_log() {
        delete_option(self::OPTION_KEY);
    }
}

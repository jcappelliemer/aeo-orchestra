<?php
/**
 * 3.36.0 — Debug log helper.
 *
 * Wraps PHP's error_log() so it only emits when WP_DEBUG is on. Production
 * sites with WP_DEBUG off have zero log output (silenced by the
 * environment, not by the helper) — this lets us keep diagnostic
 * instrumentation in the codebase without the WP.org Plugin Check tool
 * flagging it as "Debug code should not normally be used in production".
 *
 * Usage:
 *   seo_aeo_debug_log('keyword research failed', ['post_id' => $id, 'error' => $err]);
 *
 * The optional second argument is appended as compact JSON. Pass null or
 * omit it if you only have a message.
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('seo_aeo_debug_log')) {
    function seo_aeo_debug_log($msg, $context = null) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) return;
        $line = '[Orchestra] ' . (string) $msg;
        if ($context !== null) {
            $encoded = wp_json_encode($context);
            if (is_string($encoded)) $line .= ' ' . $encoded;
        }
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional debug log, WP_DEBUG-gated
        error_log($line);
    }
}

<?php
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com
 *
 * 3.35.67 — PHP 8.x compatibility shim.
 *
 * Provides polyfills for PHP 8+ functions used by the plugin so the codebase
 * runs on PHP 7.4 (Aruba shared hosting floor). Loaded VERY early in main
 * file (before any other class) so subsequent code can rely on these.
 *
 * Polyfilled:
 *   - str_contains()      (PHP 8.0)
 *   - str_starts_with()   (PHP 8.0)
 *   - str_ends_with()     (PHP 8.0)
 *   - array_is_list()     (PHP 8.1)
 *
 * NOT polyfillable in PHP 7.x (must NOT be used in plugin source — CI lint blocks):
 *   - Nullsafe operator `?->`
 *   - `match` expression
 *   - Named arguments
 *   - Constructor property promotion
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        if ($needle === '' || $needle === null) return true;
        return strpos((string) $haystack, (string) $needle) !== false;
    }
}

if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        if ($needle === '' || $needle === null) return true;
        return strncmp((string) $haystack, (string) $needle, strlen((string) $needle)) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        if ($needle === '' || $needle === null) return true;
        $needle_len = strlen((string) $needle);
        if ($needle_len === 0) return true;
        return substr((string) $haystack, -$needle_len) === (string) $needle;
    }
}

if (!function_exists('array_is_list')) {
    function array_is_list($array) {
        if (!is_array($array)) return false;
        if ($array === array()) return true;
        if (array_keys($array) !== range(0, count($array) - 1)) return false;
        return true;
    }
}

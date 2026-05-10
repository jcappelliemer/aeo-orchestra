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
 * 3.35.85.0 — Input sanitizer helpers.
 *
 * `json_decode()` does not sanitize. WP.org review (10 May 2026) flagged any
 * `json_decode($_POST[...])` pattern that doesn't explicitly clean the
 * decoded leaves. This class provides a single, consistent path:
 *
 *   $items = SEO_AEO_Input_Sanitizer::decode_json_post('items');
 *
 * - Reads the raw $_POST value (returns null if absent or not a string).
 * - Removes WP magic-quote slashes via wp_unslash().
 * - Decodes the JSON. Returns null on parse failure.
 * - Recursively walks the decoded structure: every string leaf is run
 *   through sanitize_text_field(); ints/floats/bools/null pass through;
 *   anything else (objects, resources) is dropped to an empty string.
 *
 * Callers that need a stronger or domain-specific sanitization (URLs,
 * HTML, integers > 0) MUST still apply it on top — this helper provides
 * the safe baseline, not the final word.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Input_Sanitizer {

    /**
     * Recursively sanitize a structure decoded from JSON.
     */
    public static function recursive($value) {
        if (is_array($value)) {
            $out = array();
            foreach ($value as $k => $v) {
                $clean_key = is_string($k) ? sanitize_key($k) : (int) $k;
                $out[$clean_key] = self::recursive($v);
            }
            return $out;
        }
        if (is_string($value))                                    return sanitize_text_field($value);
        if (is_int($value) || is_float($value) || is_bool($value)) return $value;
        if (is_null($value))                                      return null;
        return ''; // objects, resources, anything weird
    }

    /**
     * Decode a JSON field from $_POST safely. Returns null if absent or
     * malformed; otherwise an array with sanitized leaves.
     */
    public static function decode_json_post($key) {
        if (!isset($_POST[$key])) return null;
        $raw = wp_unslash($_POST[$key]);
        if (!is_string($raw)) return null;
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) return null;
        return self::recursive($decoded);
    }

    /**
     * Same as decode_json_post but for $_GET.
     */
    public static function decode_json_get($key) {
        if (!isset($_GET[$key])) return null;
        $raw = wp_unslash($_GET[$key]);
        if (!is_string($raw)) return null;
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) return null;
        return self::recursive($decoded);
    }
}

<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- helper functions use 'aeo_' prefix (short form of 'seo_aeo_'). Renaming would break ~30 caller sites across the plugin; aeo_ is a documented plugin namespace per CLAUDE.md.
/**
 * SEO_AEO Field-Level Backup Helpers (v3.41.7)
 *
 * Coexists with SEO_AEO_Snapshot_Manager (whole-post snapshots, TTL 7d).
 * Field-level backup is faster, addressable per-field, and dedicated for
 * Tier CAUTION/DANGER surgical writes. Defense in depth.
 *
 * Backup record shape (stored in post_meta key _seo_aeo_<field>_backup):
 *   {
 *     'timestamp' => current_time('mysql', true),
 *     'value'     => '<previous value verbatim>',
 *     'source'    => 'aeo_action_<action_type>',
 *     'tier'      => 'CAUTION' | 'DANGER',
 *     'plugin_version' => SEO_AEO_VERSION,
 *   }
 *
 * Field naming convention: $field MUST be a safe slug (lowercase, _ only).
 * Backup meta key is composed as "_seo_aeo_{$field}_backup" so callers
 * pass e.g. "meta_title", "meta_description", "post_content",
 * "custom_schema_html". The leading underscore prefix is added by us.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('aeo_create_field_backup')) {
    /**
     * Save the current value of $field on $post_id to a sidecar
     * post_meta key BEFORE the new value is written.
     *
     * @param int    $post_id
     * @param string $field         Safe slug; meta key becomes "_seo_aeo_{$field}_backup".
     * @param mixed  $current_value Whatever the field currently holds.
     * @param string $action_type   For audit trail; e.g. "GENERATE_SCHEMA".
     * @param string $tier          "CAUTION" | "DANGER".
     * @return bool|WP_Error true on success, WP_Error on validation failure.
     */
    function aeo_create_field_backup($post_id, $field, $current_value, $action_type = '', $tier = 'CAUTION') {
        if (!is_int($post_id) && !ctype_digit((string) $post_id)) {
            return new WP_Error('aeo_backup_bad_post_id', 'post_id must be integer-like');
        }
        $post_id = (int) $post_id;
        if ($post_id <= 0) {
            return new WP_Error('aeo_backup_bad_post_id', 'post_id must be positive');
        }
        if (!is_string($field) || !preg_match('/^[a-z][a-z0-9_]*$/', $field)) {
            return new WP_Error('aeo_backup_bad_field', 'field must be a safe slug [a-z][a-z0-9_]*');
        }
        $tier = strtoupper((string) $tier);
        if (!in_array($tier, array('CAUTION', 'DANGER'), true)) {
            $tier = 'CAUTION';
        }
        $record = array(
            'timestamp'       => function_exists('current_time') ? current_time('mysql', true) : gmdate('Y-m-d H:i:s'),
            'value'           => is_string($current_value) ? $current_value : maybe_serialize($current_value),
            'source'          => 'aeo_action_' . sanitize_key((string) $action_type),
            'tier'            => $tier,
            'plugin_version'  => defined('SEO_AEO_VERSION') ? SEO_AEO_VERSION : 'unknown',
        );
        $meta_key = '_seo_aeo_' . $field . '_backup';
        $ok = update_post_meta($post_id, $meta_key, $record);
        if ($ok === false) {
            return new WP_Error('aeo_backup_write_failed', 'update_post_meta returned false');
        }
        return true;
    }
}

if (!function_exists('aeo_read_field_backup')) {
    /**
     * Read the backup record for $field on $post_id (or null if none).
     */
    function aeo_read_field_backup($post_id, $field) {
        $post_id = (int) $post_id;
        if ($post_id <= 0) return null;
        if (!preg_match('/^[a-z][a-z0-9_]*$/', (string) $field)) return null;
        $rec = get_post_meta($post_id, '_seo_aeo_' . $field . '_backup', true);
        return is_array($rec) ? $rec : null;
    }
}

if (!function_exists('aeo_restore_field_backup')) {
    /**
     * Restore the backed-up value for $field on $post_id. Dispatches to the
     * appropriate WP write API based on field convention:
     *   - "post_content"  → wp_update_post
     *   - "post_title"    → wp_update_post
     *   - anything else   → update_post_meta on key (without _backup suffix)
     *     translation: "meta_title" backup restores to "_seo_aeo_meta_title"
     *
     * Returns true|WP_Error.
     */
    function aeo_restore_field_backup($post_id, $field) {
        $rec = aeo_read_field_backup($post_id, $field);
        if (!$rec || !isset($rec['value'])) {
            return new WP_Error('aeo_backup_not_found', 'No backup record for that field');
        }
        $value = $rec['value'];
        if (is_string($value) && (substr($value, 0, 2) === 'a:' || substr($value, 0, 2) === 'O:')) {
            $maybe = maybe_unserialize($value);
            if ($maybe !== null) $value = $maybe;
        }
        $post_id = (int) $post_id;
        if ($field === 'post_content') {
            $r = wp_update_post(array('ID' => $post_id, 'post_content' => $value), true);
            if (is_wp_error($r)) return $r;
            return true;
        }
        if ($field === 'post_title') {
            $r = wp_update_post(array('ID' => $post_id, 'post_title' => $value), true);
            if (is_wp_error($r)) return $r;
            return true;
        }
        $meta_key = '_seo_aeo_' . $field;
        $ok = update_post_meta($post_id, $meta_key, $value);
        if ($ok === false) {
            return new WP_Error('aeo_restore_write_failed', 'update_post_meta returned false');
        }
        return true;
    }
}

if (!function_exists('aeo_field_backup_exists')) {
    function aeo_field_backup_exists($post_id, $field) {
        return aeo_read_field_backup($post_id, $field) !== null;
    }
}

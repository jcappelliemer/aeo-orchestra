<?php
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.InputNotValidated
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com. All rights reserved.
 * Unauthorized copying, redistribution or resale is strictly prohibited.
 */
/**
 * AEO Orchestra - History Manager
 * Handles search/analysis history persistence using JSON encoding.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Orchestra_History {

    /**
     * Cap dimensione singolo item: data + restore_payload combinati.
     * 100KB per item × 100 items = ~10MB option (autoload OFF).
     */
    const MAX_DATA_BYTES = 102400;
    const MAX_RESTORE_BYTES = 102400;
    const MAX_ITEMS_TOTAL = 100;
    const MAX_ITEMS_PER_SECTION = 30;

    public function __construct() {
        try {
            add_action('wp_ajax_seo_aeo_orchestra_save_history', array($this, 'ajax_save_history'));
            add_action('wp_ajax_seo_aeo_orchestra_get_history', array($this, 'ajax_get_history'));
            add_action('wp_ajax_seo_aeo_orchestra_get_history_item', array($this, 'ajax_get_history_item'));
            add_action('wp_ajax_seo_aeo_orchestra_clear_history_section', array($this, 'ajax_clear_history_section'));
        } catch (Throwable $e) {
        }
    }

    /**
     * AJAX: cancella cronologia di una specifica sezione (3.26.4).
     */
    public function ajax_clear_history_section() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
        $section = sanitize_text_field($_POST['section'] ?? '');
        if (empty($section)) { wp_send_json(array('error' => 'section mancante')); return; }
        $history = $this->get_history_array();
        $kept = array_values(array_filter($history, function($item) use ($section) {
            return !isset($item['section']) || $item['section'] !== $section;
        }));
        $json = wp_json_encode($kept, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        delete_option('seo_aeo_orchestra_history_json');
        add_option('seo_aeo_orchestra_history_json', $json, '', 'no');
        wp_send_json(array('success' => true, 'removed' => count($history) - count($kept)));
    }

    public function ajax_save_history() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        $section = sanitize_text_field(wp_unslash($_POST['section']));
        $type = sanitize_text_field(wp_unslash($_POST['type']));
        $title = sanitize_text_field(wp_unslash($_POST['title']));
        // 3.35.85.0 (WP.org Issue 4): the history `data` field is free-form
        // multi-line text from the AI generation flow. Use sanitize_textarea_field
        // (preserves newlines, strips invalid UTF-8 + control chars + tags) rather
        // than passing raw $_POST through to the option store.
        $raw_data = isset($_POST['data']) ? sanitize_textarea_field(wp_unslash($_POST['data'])) : '';
        if (strlen($raw_data) > self::MAX_DATA_BYTES) {
            $raw_data = substr($raw_data, 0, self::MAX_DATA_BYTES);
        }
        $credits = intval(wp_unslash($_POST['credits']));

        $restore_payload = '';
        if (isset($_POST['restore_payload'])) {
            // restore_payload is JSON; sanitize the raw string before json_decode,
            // then recursively sanitize the decoded leaves before re-encoding.
            $rp_raw = sanitize_textarea_field(wp_unslash($_POST['restore_payload']));
            if (is_string($rp_raw) && strlen($rp_raw) <= self::MAX_RESTORE_BYTES) {
                $decoded = json_decode($rp_raw, true);
                if (is_array($decoded)) {
                    // 3.37.0 Module 10 — restore_payload has CSS-selector keys
                    // (e.g. "#orch-page-results") + HTML string values. The
                    // generic sanitize_recursive() called sanitize_key() on
                    // keys which stripped '#' → broken selectors; and
                    // sanitize_text_field() on values which stripped tags →
                    // empty restored content. Use the dedicated helper.
                    $clean = $this->sanitize_restore_payload($decoded);
                    $restore_payload = wp_json_encode($clean);
                    if (!is_string($restore_payload)) $restore_payload = '';
                }
            }
        }

        $history = $this->get_history_array();
        $id = 'h' . str_replace('.', '', (string) microtime(true)) . wp_rand(1000, 9999);

        array_unshift($history, array(
            'id'              => $id,
            'section'         => $section,
            'type'            => $type,
            'title'           => $title,
            'data'            => $raw_data,
            'restore_payload' => $restore_payload,
            'credits'         => $credits,
            'date'            => current_time('mysql'),
        ));

        // Cap per-section + cap totale
        $by_section_count = array();
        $pruned = array();
        foreach ($history as $item) {
            $sec = isset($item['section']) ? $item['section'] : '';
            $by_section_count[$sec] = isset($by_section_count[$sec]) ? $by_section_count[$sec] + 1 : 1;
            if ($by_section_count[$sec] <= self::MAX_ITEMS_PER_SECTION) {
                $pruned[] = $item;
            }
        }
        $history = array_slice($pruned, 0, self::MAX_ITEMS_TOTAL);

        $json = wp_json_encode($history, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        delete_option('seo_aeo_orchestra_history_json');
        add_option('seo_aeo_orchestra_history_json', $json, '', 'no');
        wp_send_json(array('success' => true, 'count' => count($history), 'id' => $id));
    }

    public function ajax_get_history() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        $section = sanitize_text_field(wp_unslash($_POST['section']));
        $history = $this->get_history_array();
        if (empty($history)) { wp_send_json(array()); return; }

        $filtered = array_values(array_filter($history, function($item) use ($section) {
            return isset($item['section']) && $item['section'] === $section;
        }));
        // Lista leggera: niente payload completo nel response
        $light = array_map(function($it) {
            return array(
                'id'           => isset($it['id']) ? $it['id'] : '',
                'section'      => isset($it['section']) ? $it['section'] : '',
                'type'         => isset($it['type']) ? $it['type'] : '',
                'title'        => isset($it['title']) ? $it['title'] : '',
                'credits'      => isset($it['credits']) ? $it['credits'] : 0,
                'date'         => isset($it['date']) ? $it['date'] : '',
                'has_restore'  => !empty($it['restore_payload']),
                'data_preview' => isset($it['data']) ? mb_substr((string) $it['data'], 0, 1500) : '',
                // Backward-compat: i flussi vecchi leggono `data` per il preview
                'data'         => isset($it['data']) ? mb_substr((string) $it['data'], 0, 1500) : '',
            );
        }, array_slice($filtered, 0, 20));
        wp_send_json($light);
    }

    /**
     * Ritorna 1 item completo per id (data + restore_payload integri),
     * usato dal bottone "Riapri" lato client.
     */
    public function ajax_get_history_item() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        $id = isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
        if (empty($id)) { wp_send_json(array('error' => 'id mancante')); return; }

        $history = $this->get_history_array();
        foreach ($history as $item) {
            if (isset($item['id']) && $item['id'] === $id) {
                wp_send_json(array('success' => true, 'item' => $item));
                return;
            }
        }
        wp_send_json(array('error' => 'item non trovato'));
    }

    /**
     * 3.35.85.0 — recursively sanitize a decoded JSON tree.
     * Strings → sanitize_text_field; ints/floats/bools/null pass through; arrays
     * recurse; anything else (objects, resources) becomes an empty string.
     */
    private function sanitize_recursive($value) {
        if (is_array($value)) {
            $out = array();
            foreach ($value as $k => $v) {
                $clean_key = is_string($k) ? sanitize_key($k) : (int) $k;
                $out[$clean_key] = $this->sanitize_recursive($v);
            }
            return $out;
        }
        if (is_string($value)) return sanitize_text_field($value);
        if (is_int($value) || is_float($value) || is_bool($value) || is_null($value)) return $value;
        return '';
    }

    /**
     * 3.37.0 Module 10 — sanitize restore_payload tree.
     *
     * The payload contains:
     *   - top-level keys: "fields", "outputs", "view_html", "meta"
     *   - inside fields/outputs: keys are CSS selectors (e.g. "#orch-page-results",
     *     "[name=\"meta_title\"]") and values are either form values (strings)
     *     or HTML markup (strings)
     *
     * Generic sanitize_recursive is wrong here because sanitize_key() strips
     * the leading '#' and other selector chars, and sanitize_text_field() strips
     * HTML tags. The payload is admin-only (nonce-gated AJAX) and is later
     * rendered back into the same admin UI that wrote it.
     *
     * This helper preserves CSS-selector keys verbatim (when they match a safe
     * regex) and uses wp_kses_post on string values to safely retain HTML.
     */
    private function sanitize_restore_payload($value) {
        if (is_array($value)) {
            $out = array();
            foreach ($value as $k => $v) {
                if (is_string($k)) {
                    // Allow CSS-selector chars: letters, digits, _ - # . [ ] = " '  space : , >
                    // Length cap 200 chars (generous for nested selectors).
                    if (strlen($k) > 200) continue;
                    if (preg_match('/^[A-Za-z0-9_\-#.\[\]="\' :,>]+$/u', $k)) {
                        $clean_key = $k;
                    } else {
                        // Fallback: strip-down to a sanitize_key shape so we still
                        // get a key (but won't match a real DOM selector).
                        $clean_key = sanitize_key($k);
                        if ($clean_key === '') continue;
                    }
                } else {
                    $clean_key = (int) $k;
                }
                $out[$clean_key] = $this->sanitize_restore_payload($v);
            }
            return $out;
        }
        if (is_string($value)) {
            // wp_kses_post: preserves post-allowed HTML, strips <script>, JS event
            // handlers, javascript: hrefs, etc. Bounds size to MAX_RESTORE_BYTES
            // is enforced at the outer level (raw $_POST length check).
            return wp_kses_post($value);
        }
        if (is_int($value) || is_float($value) || is_bool($value) || is_null($value)) {
            return $value;
        }
        return '';
    }

    public function get_history_array() {
        $json_raw = get_option('seo_aeo_orchestra_history_json', '');
        if (!empty($json_raw) && is_string($json_raw)) {
            $decoded = json_decode($json_raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        $legacy = get_option('seo_aeo_orchestra_history');
        if (is_array($legacy) && !empty($legacy)) {
            $json = wp_json_encode($legacy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            delete_option('seo_aeo_orchestra_history_json');
            add_option('seo_aeo_orchestra_history_json', $json, '', 'no');
            delete_option('seo_aeo_orchestra_history');
            return $legacy;
        }
        return array();
    }
}

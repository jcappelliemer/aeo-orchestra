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
        $raw_data = isset($_POST['data']) ? wp_unslash($_POST['data']) : '';
        if (strlen($raw_data) > self::MAX_DATA_BYTES) {
            $raw_data = substr($raw_data, 0, self::MAX_DATA_BYTES);
        }
        $credits = intval(wp_unslash($_POST['credits']));

        $restore_payload = '';
        if (isset($_POST['restore_payload'])) {
            $rp = wp_unslash($_POST['restore_payload']);
            if (is_string($rp) && strlen($rp) <= self::MAX_RESTORE_BYTES) {
                $decoded = json_decode($rp, true);
                if (is_array($decoded)) $restore_payload = $rp;
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

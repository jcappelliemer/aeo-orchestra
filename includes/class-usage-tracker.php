<?php
error_reporting(0);
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com. All rights reserved.
 * Unauthorized copying, redistribution or resale is strictly prohibited.
 */
/**
 * AEO Orchestra - Usage Tracker (P1)
 * Tracks feature/credit consumption for auditing.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Orchestra_Usage_Tracker {

    const OPTION_KEY = 'seo_aeo_orchestra_usage_log';
    const MAX_ENTRIES = 500;

    public function __construct() {
        try {
            add_action('wp_ajax_seo_aeo_orchestra_track_usage', array($this, 'ajax_track_usage'));
            add_action('wp_ajax_seo_aeo_orchestra_get_usage', array($this, 'ajax_get_usage'));
            add_action('wp_ajax_seo_aeo_orchestra_get_usage_stats', array($this, 'ajax_get_usage_stats'));
        } catch (Throwable $e) {
            error_log('SEO AEO Usage Tracker __construct error: ' . $e->getMessage());
        }
    }

    public function ajax_track_usage() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        $type = sanitize_text_field($_POST['usage_type']);
        $credits = intval($_POST['credits']);
        $page_title = isset($_POST['page_title']) ? sanitize_text_field($_POST['page_title']) : '';
        $detail = isset($_POST['detail']) ? sanitize_text_field(mb_substr($_POST['detail'], 0, 200)) : '';

        $entry = array(
            'timestamp' => current_time('mysql'),
            'type' => $type,
            'credits' => $credits,
            'page' => $page_title,
            'detail' => $detail,
            'user' => wp_get_current_user()->user_login
        );

        $log = $this->get_log();
        array_unshift($log, $entry);
        $log = array_slice($log, 0, self::MAX_ENTRIES);
        $this->save_log($log);

        wp_send_json(array('success' => true, 'total_entries' => count($log)));
    }

    public function ajax_get_usage() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        $page = isset($_POST['page_num']) ? max(1, intval($_POST['page_num'])) : 1;
        $per_page = 25;
        $filter_type = isset($_POST['filter_type']) ? sanitize_text_field($_POST['filter_type']) : '';

        $log = $this->get_log();
        if ($filter_type) {
            $log = array_values(array_filter($log, function($e) use ($filter_type) {
                return $e['type'] === $filter_type;
            }));
        }

        $total = count($log);
        $offset = ($page - 1) * $per_page;
        $entries = array_slice($log, $offset, $per_page);

        wp_send_json(array(
            'entries' => $entries,
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $per_page)
        ));
    }

    public function ajax_get_usage_stats() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        $log = $this->get_log();

        $total_credits = 0;
        $by_type = array();
        $by_day = array();
        $by_user = array();

        foreach ($log as $entry) {
            $credits = isset($entry['credits']) ? intval($entry['credits']) : 0;
            $total_credits += $credits;

            $type = isset($entry['type']) ? $entry['type'] : 'unknown';
            if (!isset($by_type[$type])) {
                $by_type[$type] = array('count' => 0, 'credits' => 0);
            }
            $by_type[$type]['count']++;
            $by_type[$type]['credits'] += $credits;

            $day = isset($entry['timestamp']) ? substr($entry['timestamp'], 0, 10) : '';
            if ($day) {
                if (!isset($by_day[$day])) {
                    $by_day[$day] = array('count' => 0, 'credits' => 0);
                }
                $by_day[$day]['count']++;
                $by_day[$day]['credits'] += $credits;
            }

            $user = isset($entry['user']) ? $entry['user'] : 'unknown';
            if (!isset($by_user[$user])) {
                $by_user[$user] = array('count' => 0, 'credits' => 0);
            }
            $by_user[$user]['count']++;
            $by_user[$user]['credits'] += $credits;
        }

        // Sort by_day descending
        krsort($by_day);

        wp_send_json(array(
            'total_entries' => count($log),
            'total_credits' => $total_credits,
            'by_type' => $by_type,
            'by_day' => $by_day,
            'by_user' => $by_user
        ));
    }

    private function get_log() {
        $raw = get_option(self::OPTION_KEY, '');
        if (!empty($raw) && is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) return $decoded;
        }
        return array();
    }

    private function save_log($log) {
        $json = wp_json_encode($log, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        delete_option(self::OPTION_KEY);
        add_option(self::OPTION_KEY, $json, '', 'no');
    }
}

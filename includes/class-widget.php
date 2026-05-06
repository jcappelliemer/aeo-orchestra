<?php
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotValidated
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com. All rights reserved.
 * Unauthorized copying, redistribution or resale is strictly prohibited.
 */
/**
 * AEO Orchestra - Score Widget
 * Frontend widget showing SEO + AEO scores on the website.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Orchestra_Widget {

    private $main;

    public function __construct($main = null) {
        try {
            $this->main = $main;
            add_action('init', array($this, 'maybe_enable_widget'));
            add_action('wp_ajax_seo_aeo_orchestra_widget_score', array($this, 'ajax_widget_score'));
            add_action('wp_ajax_nopriv_seo_aeo_orchestra_widget_score', array($this, 'ajax_widget_score'));
        } catch (Throwable $e) {
        }
    }

    public function maybe_enable_widget() {
        $widget_enabled = get_option('seo_aeo_orchestra_widget_enabled', 'no');
        $widget_visibility = get_option('seo_aeo_orchestra_widget_visibility', 'admin_only');
        if ($widget_enabled === 'yes') {
            if ($widget_visibility === 'everyone' || ($widget_visibility === 'admin_only' && current_user_can('manage_options'))) {
                add_action('wp_footer', array($this, 'render_score_widget'));
                add_action('wp_enqueue_scripts', array($this, 'enqueue_widget_scripts'));
            }
        }
    }

    public function enqueue_widget_scripts() {
        try {
            $plugin_dir = defined('SEO_AEO_PLUGIN_DIR') ? SEO_AEO_PLUGIN_DIR : '';
            $plugin_url = defined('SEO_AEO_PLUGIN_URL') ? SEO_AEO_PLUGIN_URL : '';
            $version = defined('SEO_AEO_VERSION') ? SEO_AEO_VERSION : '2.0.0';

            $css_file = $plugin_dir . 'assets/css/widget.css';
            $js_file = $plugin_dir . 'assets/js/widget.js';
            $css_ver = $version . '.' . (file_exists($css_file) ? filemtime($css_file) : '0');
            $js_ver = $version . '.' . (file_exists($js_file) ? filemtime($js_file) : '0');

            wp_enqueue_style('seo-aeo-widget', $plugin_url . 'assets/css/widget.css', array(), $css_ver);
            wp_enqueue_script('seo-aeo-widget', $plugin_url . 'assets/js/widget.js', array('jquery'), $js_ver, true);

            global $post;
            $current_post_id = 0;
            if (is_singular() && $post) {
                $current_post_id = $post->ID;
            } elseif (is_front_page() || is_home()) {
                $current_post_id = intval(get_option('page_on_front'));
            }

            wp_localize_script('seo-aeo-widget', 'seoAeoWidget', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('seo_aeo_widget_nonce'),
                'pageUrl' => is_singular() ? get_permalink() : home_url(add_query_arg(array(), false)),
                'postId' => $current_post_id,
                'showBranding' => $this->should_show_branding(),
                'brandingUrl' => 'https://aeo-orchestra.com',
                'brandingText' => 'Powered by AEO Orchestra'
            ));
        } catch (Throwable $e) {
        }
    }

    private function should_show_branding() {
        $branding = get_option('seo_aeo_orchestra_widget_branding', 'auto');
        if ($branding === 'always') return true;
        if ($branding === 'never') return false;
        $license_type = ($this->main && isset($this->main->license_type)) ? $this->main->license_type : get_option('seo_aeo_orchestra_license_type', 'starter');
        return !in_array($license_type, array('professional', 'team', 'b2b_custom'));
    }

    public function ajax_widget_score() {
        check_ajax_referer('seo_aeo_widget_nonce', 'nonce');
        $url = sanitize_url($_POST['url']);
        $post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;

        if (empty($url) && !$post_id) {
            wp_send_json(array('error' => 'URL mancante'));
            return;
        }

        if (!$post_id && $url) {
            $post_id = url_to_postid($url);
            if (!$post_id) {
                $front = intval(get_option('page_on_front'));
                $home_url = trailingslashit(home_url());
                if ($front && (trailingslashit($url) === $home_url || $url === home_url())) {
                    $post_id = $front;
                }
            }
        }

        if ($post_id > 0) {
            $seo_score = get_post_meta($post_id, '_seo_aeo_seo_score', true);
            $aeo_score = get_post_meta($post_id, '_seo_aeo_aeo_score', true);
            $last_analysis = get_post_meta($post_id, '_seo_aeo_last_analysis', true);

            if ($seo_score !== '' && $seo_score !== false) {
                wp_send_json(array(
                    'seo_score' => intval($seo_score),
                    'aeo_score' => ($aeo_score !== '' && $aeo_score !== false) ? intval($aeo_score) : null,
                    'ai_visibility' => get_post_meta($post_id, '_seo_aeo_ai_visibility', true) ?: null,
                    'citability' => get_post_meta($post_id, '_seo_aeo_citability', true) ? intval(get_post_meta($post_id, '_seo_aeo_citability', true)) : null,
                    'issues_count' => intval(get_post_meta($post_id, '_seo_aeo_issues_count', true)),
                    'aeo_issues_count' => intval(get_post_meta($post_id, '_seo_aeo_aeo_issues_count', true)),
                    'top_suggestion' => get_post_meta($post_id, '_seo_aeo_top_suggestion', true) ?: '',
                    'top_aeo_suggestion' => '',
                    'cached' => false,
                    'source' => 'orchestrator',
                    'last_analysis' => $last_analysis ?: ''
                ));
                return;
            }
        }

        $cache_key = 'seo_aeo_score_' . md5($url . '_' . $post_id);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            $cached['source'] = 'cache';
            wp_send_json($cached);
            return;
        }

        // 3.7.5 — SAFETY: NON chiamare AI live dal widget pubblico (consumo crediti per ogni visitor).
        // Se non ci sono postmeta salvati né transient cache, ritorniamo placeholder vuoto.
        // L'analisi AI deve essere lanciata esplicitamente dall'admin (Orchestratore / pagine specifiche),
        // NON dal traffic pubblico del sito. Vecchio comportamento causava bleeding crediti in staging.
        wp_send_json(array(
            'seo_score' => null,
            'aeo_score' => null,
            'ai_visibility' => null,
            'citability' => null,
            'issues_count' => 0,
            'aeo_issues_count' => 0,
            'top_suggestion' => '',
            'top_aeo_suggestion' => '',
            'cached' => false,
            'source' => 'no_data',
            'message' => 'Nessuna analisi salvata per questa pagina. Lancia analisi dal pannello admin.'
        ));
    }

    public function render_score_widget() {
        $show_branding = $this->should_show_branding();
        ?>
        <div id="seo-aeo-score-widget" class="seo-aeo-widget-container" style="display:none;">
            <button id="seo-aeo-widget-toggle" class="seo-aeo-widget-badge" title="SEO + AEO Score">
                <span class="seo-aeo-widget-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 20V10"></path><path d="M18 20V4"></path><path d="M6 20v-4"></path>
                    </svg>
                </span>
                <span class="seo-aeo-widget-scores">
                    <span id="seo-aeo-badge-seo">--</span>
                    <span class="seo-aeo-widget-sep">|</span>
                    <span id="seo-aeo-badge-aeo">--</span>
                </span>
            </button>

            <div id="seo-aeo-widget-panel" class="seo-aeo-widget-panel" style="display:none;">
                <div class="seo-aeo-widget-header">
                    <h4>Score SEO + AEO</h4>
                    <button id="seo-aeo-widget-close" class="seo-aeo-widget-close">&times;</button>
                </div>
                <div id="seo-aeo-widget-content" class="seo-aeo-widget-body">
                    <div class="seo-aeo-widget-loading">Analisi in corso...</div>
                </div>
                <?php if ($show_branding): ?>
                <div class="seo-aeo-widget-footer">
                    <a href="https://aeo-orchestra.com" target="_blank" rel="noopener">Powered by AEO Orchestra</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

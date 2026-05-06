<?php
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting
// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com. All rights reserved.
 * Unauthorized copying, redistribution or resale is strictly prohibited.
 */
/**
 * AEO Orchestra - AJAX Handlers
 * All WordPress AJAX handlers for SEO/AEO agents, orchestrator, images, and content.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Orchestra_Ajax_Handlers {

    private $main;

    /**
     * Get the API client safely, returning null if not available.
     */
    private function get_api() {
        if (isset($this->main) && is_object($this->main) && isset($this->main->api_client)) {
            return $this->main->api_client;
        }
        return null;
    }

    /**
     * Check API is available, send error JSON + wp_die if not.
     * Returns the API client or null (after sending error response).
     */
    private function require_api() {
        $api = $this->get_api();
        if (!$api) {
            wp_send_json(array('error' => 'Plugin non configurato. Verifica la licenza nelle Impostazioni.'));
            wp_die();
            return null;
        }
        return $api;
    }

    /**
     * Mappa il locale WP a un codice lingua a 2 lettere supportato dal backend AI.
     * Default it. Whitelist: it/en/es/fr/de.
     * 3.31.0: priorità setting utente `seo_aeo_orchestra_language` (UI dedicata),
     * fallback locale WP. Allinea al comportamento di SEO_AEO_T::detect_locale()
     * cosi' lingua interfaccia + lingua AI sono coerenti.
     */
    private function get_ai_language() {
        $supported = array('it', 'en', 'es', 'fr', 'de');
        $opt = get_option('seo_aeo_orchestra_language', '');
        if ($opt && in_array($opt, $supported, true)) return $opt;
        $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
        $code = strtolower(substr((string) $locale, 0, 2));
        return in_array($code, $supported, true) ? $code : 'it';
    }

    /**
     * Safe wrapper for API requests. Catches timeouts and errors.
     * Auto-inietta `language` per endpoint AI (/ai/*) se non gia' presente,
     * cosi' i contenuti generati seguono la lingua dell'admin WP.
     */
    private function safe_api_call($endpoint, $data = array()) {
        try {
            $api = $this->require_api();
            if (!$api) return;
            if (is_string($endpoint) && strpos($endpoint, '/ai/') === 0 && !isset($data['language'])) {
                $data['language'] = $this->get_ai_language();
            }
            $result = $api->api_request($endpoint, $data);
            wp_send_json($result);
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore di comunicazione con il server: ' . $e->getMessage()));
        }
        wp_die();
    }

    public function __construct($main = null) {
        try {
            $this->main = $main;

            // License & Connection
            add_action('wp_ajax_seo_aeo_orchestra_validate_license', array($this, 'ajax_validate_license'));
            add_action('wp_ajax_seo_aeo_orchestra_test_connection', array($this, 'ajax_test_connection'));

            // SEO Agents
            add_action('wp_ajax_seo_aeo_orchestra_analyze', array($this, 'ajax_analyze_seo'));
            add_action('wp_ajax_seo_aeo_orchestra_generate_meta', array($this, 'ajax_generate_meta'));
            add_action('wp_ajax_seo_aeo_orchestra_generate_content', array($this, 'ajax_generate_content'));
            add_action('wp_ajax_seo_aeo_orchestra_local_seo', array($this, 'ajax_local_seo'));

            // AEO Agents
            add_action('wp_ajax_seo_aeo_orchestra_aeo_analyze', array($this, 'ajax_aeo_analyze'));
            add_action('wp_ajax_seo_aeo_orchestra_aeo_content', array($this, 'ajax_aeo_content'));

            // Orchestrator
            add_action('wp_ajax_seo_aeo_orchestra_orchestrate_single', array($this, 'ajax_orchestrate_single'));
            add_action('wp_ajax_seo_aeo_orchestra_execute_action', array($this, 'ajax_execute_action'));

            // Pages, Keywords, Content
            add_action('wp_ajax_seo_aeo_orchestra_get_pages', array($this, 'ajax_get_pages'));
            add_action('wp_ajax_seo_aeo_orchestra_suggest_keywords', array($this, 'ajax_suggest_keywords'));
            add_action('wp_ajax_seo_aeo_orchestra_publish_content', array($this, 'ajax_publish_content'));

            // Image & Media
            add_action('wp_ajax_seo_aeo_orchestra_generate_image', array($this, 'ajax_generate_image'));
            add_action('wp_ajax_seo_aeo_orchestra_upload_media', array($this, 'ajax_upload_media'));

            // Complete Article
            add_action('wp_ajax_seo_aeo_orchestra_complete_article', array($this, 'ajax_complete_article'));
            add_action('wp_ajax_seo_aeo_orchestra_save_post_meta', array($this, 'ajax_save_post_meta'));

            // Credit Balance & First Use
            add_action('wp_ajax_seo_aeo_orchestra_get_credits', array($this, 'ajax_get_credits'));
            add_action('wp_ajax_seo_aeo_orchestra_dismiss_first_use', array($this, 'ajax_dismiss_first_use'));
            add_action('wp_ajax_seo_aeo_orchestra_rerun_history', array($this, 'ajax_rerun_history'));
            // Propose / Review / Apply (v3.0.0)
            add_action('wp_ajax_seo_aeo_orchestra_propose', array($this, 'ajax_propose_action'));
            add_action('wp_ajax_seo_aeo_orchestra_apply_proposal', array($this, 'ajax_apply_proposal'));
            add_action('wp_ajax_seo_aeo_orchestra_discard_proposal', array($this, 'ajax_discard_proposal'));
            add_action('wp_ajax_seo_aeo_orchestra_undo_snapshot', array($this, 'ajax_undo_snapshot'));
            add_action('wp_ajax_seo_aeo_orchestra_list_snapshots', array($this, 'ajax_list_snapshots'));
            add_action('wp_ajax_seo_aeo_orchestra_get_snapshot_details', array($this, 'ajax_get_snapshot_details'));
            add_action('wp_ajax_seo_aeo_orchestra_clear_analyses_history', array($this, 'ajax_clear_analyses_history'));
            add_action('wp_ajax_seo_aeo_orchestra_get_health_score', array($this, 'ajax_get_health_score'));
            add_action('wp_ajax_seo_aeo_orchestra_get_transactions', array($this, 'ajax_get_transactions'));
            add_action('wp_ajax_seo_aeo_orchestra_dismiss_onboarding', array($this, 'ajax_dismiss_onboarding'));
            // Schema validator (v3.8.0)
            add_action('wp_ajax_seo_aeo_orchestra_validate_schema', array($this, 'ajax_validate_schema'));
            // Cannibalization detector (v3.9.0) + AI fix proposal (v3.11.3)
            add_action('wp_ajax_seo_aeo_orchestra_scan_cannibalization', array($this, 'ajax_scan_cannibalization'));
            add_action('wp_ajax_seo_aeo_orchestra_cannibal_fix_proposal', array($this, 'ajax_cannibal_fix_proposal'));
            // Cannibalization apply (3.11.6): applica keyword e/o internal link con snapshot
            add_action('wp_ajax_seo_aeo_orchestra_cannibal_apply', array($this, 'ajax_cannibal_apply'));
            // Native output toggle (3.12.0): abilita/disabilita output SEO nativo (sostituto Yoast)
            add_action('wp_ajax_seo_aeo_orchestra_toggle_native_output', array($this, 'ajax_toggle_native_output'));
            add_action('wp_ajax_seo_aeo_orchestra_native_output_status', array($this, 'ajax_native_output_status'));
            // Native sitemap toggle (3.13.0): abilita/disabilita sitemap.xml nativa
            add_action('wp_ajax_seo_aeo_orchestra_toggle_native_sitemap', array($this, 'ajax_toggle_native_sitemap'));
            add_action('wp_ajax_seo_aeo_orchestra_native_sitemap_status', array($this, 'ajax_native_sitemap_status'));
            // llms.txt toggle (3.13.1): abilita/disabilita llms.txt + llms-full.txt
            add_action('wp_ajax_seo_aeo_orchestra_toggle_native_llms_txt', array($this, 'ajax_toggle_native_llms_txt'));
            add_action('wp_ajax_seo_aeo_orchestra_native_llms_txt_status', array($this, 'ajax_native_llms_txt_status'));
            // Schema.org toggle (3.14.0)
            add_action('wp_ajax_seo_aeo_orchestra_toggle_native_schema', array($this, 'ajax_toggle_native_schema'));
            add_action('wp_ajax_seo_aeo_orchestra_native_schema_status', array($this, 'ajax_native_schema_status'));
            // Redirect Manager (3.15.0): toggle + CRUD redirect + 404 log
            add_action('wp_ajax_seo_aeo_orchestra_toggle_native_redirect', array($this, 'ajax_toggle_native_redirect'));
            add_action('wp_ajax_seo_aeo_orchestra_redirect_status', array($this, 'ajax_redirect_status'));
            add_action('wp_ajax_seo_aeo_orchestra_redirect_list', array($this, 'ajax_redirect_list'));
            add_action('wp_ajax_seo_aeo_orchestra_redirect_add', array($this, 'ajax_redirect_add'));
            add_action('wp_ajax_seo_aeo_orchestra_redirect_update', array($this, 'ajax_redirect_update'));
            add_action('wp_ajax_seo_aeo_orchestra_redirect_delete', array($this, 'ajax_redirect_delete'));
            add_action('wp_ajax_seo_aeo_orchestra_redirect_404_log', array($this, 'ajax_redirect_404_log'));
            add_action('wp_ajax_seo_aeo_orchestra_redirect_suggest_target', array($this, 'ajax_redirect_suggest_target'));
            // Brand Voice (3.21.0)
            add_action('wp_ajax_seo_aeo_orchestra_brand_voice_analyze', array($this, 'ajax_brand_voice_analyze'));
            add_action('wp_ajax_seo_aeo_orchestra_brand_voice_activate', array($this, 'ajax_brand_voice_activate'));
            add_action('wp_ajax_seo_aeo_orchestra_brand_voice_deactivate', array($this, 'ajax_brand_voice_deactivate'));
            add_action('wp_ajax_seo_aeo_orchestra_brand_voice_delete', array($this, 'ajax_brand_voice_delete'));
            add_action('wp_ajax_seo_aeo_orchestra_brand_voice_get', array($this, 'ajax_brand_voice_get'));
            add_action('wp_ajax_seo_aeo_orchestra_brand_voice_update', array($this, 'ajax_brand_voice_update'));
            // Keyword Research Autopilot (3.23.0)
            add_action('wp_ajax_seo_aeo_orchestra_keyword_research', array($this, 'ajax_keyword_research'));
            // Topup Option B (3.30.0-beta) — in-WP modal di ricarica
            add_action('wp_ajax_seo_aeo_orchestra_topup_options', array($this, 'ajax_topup_options'));
            add_action('wp_ajax_seo_aeo_orchestra_topup_checkout', array($this, 'ajax_topup_checkout'));
            // Keyword sets picker (3.23.2): leggi i set salvati in cronologia per riusarli ovunque
            add_action('wp_ajax_seo_aeo_orchestra_keyword_sets_list', array($this, 'ajax_keyword_sets_list'));
            // Preview draft con WP theme (3.26.1)
            add_action('wp_ajax_seo_aeo_orchestra_create_preview_draft', array($this, 'ajax_create_preview_draft'));
            // Save meta tags manuale dalla review grid (3.26.1)
            add_action('wp_ajax_seo_aeo_orchestra_save_meta_manual', array($this, 'ajax_save_meta_manual'));
            // Auto-Pilot Scheduler (3.24.0)
            add_action('wp_ajax_seo_aeo_orchestra_autopilot_create', array($this, 'ajax_autopilot_create'));
            add_action('wp_ajax_seo_aeo_orchestra_autopilot_list',   array($this, 'ajax_autopilot_list'));
            add_action('wp_ajax_seo_aeo_orchestra_autopilot_toggle', array($this, 'ajax_autopilot_toggle'));
            add_action('wp_ajax_seo_aeo_orchestra_autopilot_delete', array($this, 'ajax_autopilot_delete'));
            add_action('wp_ajax_seo_aeo_orchestra_autopilot_run_now', array($this, 'ajax_autopilot_run_now'));
            add_action('wp_ajax_seo_aeo_orchestra_autopilot_log',    array($this, 'ajax_autopilot_log'));
            // Migration Wizard (3.19.0)
            add_action('wp_ajax_seo_aeo_orchestra_migration_scan', array($this, 'ajax_migration_scan'));
            add_action('wp_ajax_seo_aeo_orchestra_migration_backup', array($this, 'ajax_migration_backup'));
            add_action('wp_ajax_seo_aeo_orchestra_migration_migrate_meta', array($this, 'ajax_migration_migrate_meta'));
            add_action('wp_ajax_seo_aeo_orchestra_migration_import_redirects', array($this, 'ajax_migration_import_redirects'));
            add_action('wp_ajax_seo_aeo_orchestra_migration_activate_stack', array($this, 'ajax_migration_activate_stack'));
            // GSC integration (v3.10.0)
            add_action('wp_ajax_seo_aeo_orchestra_gsc_status', array($this, 'ajax_gsc_status'));
            add_action('wp_ajax_seo_aeo_orchestra_gsc_connect_url', array($this, 'ajax_gsc_connect_url'));
            add_action('wp_ajax_seo_aeo_orchestra_gsc_disconnect', array($this, 'ajax_gsc_disconnect'));
            add_action('wp_ajax_seo_aeo_orchestra_gsc_list_sites', array($this, 'ajax_gsc_list_sites'));
            add_action('wp_ajax_seo_aeo_orchestra_gsc_top_pages', array($this, 'ajax_gsc_top_pages'));
            add_action('wp_ajax_seo_aeo_orchestra_gsc_timeline', array($this, 'ajax_gsc_timeline'));
            add_action('wp_ajax_seo_aeo_orchestra_gsc_aeo_impact', array($this, 'ajax_gsc_aeo_impact'));
            // Analytics page (3.32.0): GSC summary + Orchestra KPI differenziatori
            add_action('wp_ajax_seo_aeo_orchestra_analytics_fetch', array($this, 'ajax_analytics_fetch'));
            add_action('wp_ajax_seo_aeo_orchestra_analytics_page_keywords', array($this, 'ajax_analytics_page_keywords'));
            // Bing Webmaster Tools — AI Performance (v3.29.0)
            add_action('wp_ajax_seo_aeo_orchestra_bing_status', array($this, 'ajax_bing_status'));
            add_action('wp_ajax_seo_aeo_orchestra_bing_auth_url', array($this, 'ajax_bing_auth_url'));
            add_action('wp_ajax_seo_aeo_orchestra_bing_disconnect', array($this, 'ajax_bing_disconnect'));
            add_action('wp_ajax_seo_aeo_orchestra_bing_list_sites', array($this, 'ajax_bing_list_sites'));
            add_action('wp_ajax_seo_aeo_orchestra_bing_ai_performance', array($this, 'ajax_bing_ai_performance'));

            // 3.31.5: Layout articoli AI (settings + preview + autopilot review queue)
            add_action('wp_ajax_seo_aeo_orchestra_layout_discover',     array($this, 'ajax_layout_discover'));
            add_action('wp_ajax_seo_aeo_orchestra_layout_preview',      array($this, 'ajax_layout_preview'));
            add_action('wp_ajax_seo_aeo_orchestra_layout_save',         array($this, 'ajax_layout_save'));
            add_action('wp_ajax_seo_aeo_orchestra_autopilot_queue',     array($this, 'ajax_autopilot_queue'));
            add_action('wp_ajax_seo_aeo_orchestra_autopilot_publish',   array($this, 'ajax_autopilot_publish'));
            add_action('wp_ajax_seo_aeo_orchestra_autopilot_discard',   array($this, 'ajax_autopilot_discard'));
            add_action('wp_ajax_seo_aeo_orchestra_autopilot_regenerate',array($this, 'ajax_autopilot_regenerate'));

            // 3.33.0: Content Calendar — pianifica articoli con auto-publish opzionale
            add_action('wp_ajax_seo_aeo_orchestra_calendar_list',         array($this, 'ajax_calendar_list'));
            add_action('wp_ajax_seo_aeo_orchestra_calendar_add',          array($this, 'ajax_calendar_add'));
            add_action('wp_ajax_seo_aeo_orchestra_calendar_update',       array($this, 'ajax_calendar_update'));
            add_action('wp_ajax_seo_aeo_orchestra_calendar_delete',       array($this, 'ajax_calendar_delete'));
            add_action('wp_ajax_seo_aeo_orchestra_calendar_generate_now', array($this, 'ajax_calendar_generate_now'));
            add_action('wp_ajax_seo_aeo_orchestra_calendar_publish_now',  array($this, 'ajax_calendar_publish_now'));
            // 3.35.0 — Calendar Preview & Refund flow
            add_action('wp_ajax_seo_aeo_orchestra_calendar_preview_commit', array($this, 'ajax_calendar_preview_commit'));
            add_action('wp_ajax_seo_aeo_orchestra_calendar_preview_refund', array($this, 'ajax_calendar_preview_refund'));
            add_action('wp_ajax_seo_aeo_orchestra_calendar_preview_load',   array($this, 'ajax_calendar_preview_load'));
            add_action('wp_ajax_seo_aeo_orchestra_calendar_categories',   array($this, 'ajax_calendar_categories'));
            add_action('wp_ajax_seo_aeo_orchestra_calendar_suggest',      array($this, 'ajax_calendar_suggest'));
            add_action('wp_ajax_seo_aeo_orchestra_calendar_bulk_create',  array($this, 'ajax_calendar_bulk_create'));
            add_action('wp_ajax_seo_aeo_orchestra_calendar_preview_url',  array($this, 'ajax_calendar_preview_url'));
            add_action('wp_ajax_seo_aeo_orchestra_calendar_inject_image', array($this, 'ajax_calendar_inject_image'));
            add_action('wp_ajax_seo_aeo_orchestra_calendar_quick_preview', array($this, 'ajax_calendar_quick_preview'));
            add_action('wp_ajax_seo_aeo_orchestra_calendar_bulk_delete', array($this, 'ajax_calendar_bulk_delete'));

            // 3.34.0: Image SEO Manager — audit + bulk fix metadata immagini con AI Vision
            add_action('wp_ajax_seo_aeo_orchestra_image_seo_audit',        array($this, 'ajax_image_seo_audit'));
            add_action('wp_ajax_seo_aeo_orchestra_image_seo_generate_one', array($this, 'ajax_image_seo_generate_one'));
            add_action('wp_ajax_seo_aeo_orchestra_image_seo_save_one',     array($this, 'ajax_image_seo_save_one'));
            add_action('wp_ajax_seo_aeo_orchestra_image_seo_bulk_queue',   array($this, 'ajax_image_seo_bulk_queue'));
            add_action('wp_ajax_seo_aeo_orchestra_image_seo_bulk_status',  array($this, 'ajax_image_seo_bulk_status'));
            add_action('wp_ajax_seo_aeo_orchestra_image_seo_bulk_cancel',  array($this, 'ajax_image_seo_bulk_cancel'));
            add_action('wp_ajax_seo_aeo_orchestra_image_seo_export_csv',   array($this, 'ajax_image_seo_export_csv'));
            // 3.35.2: AI fix one-click + bulk changelog + bulk preview flow
            add_action('wp_ajax_seo_aeo_orchestra_image_seo_ai_fix_one',          array($this, 'ajax_image_seo_ai_fix_one'));
            // 3.35.4 — 2-step AI fix flow (preview + apply) per richiesta cliente: "ai fix mi deve far vedere le modifiche prima"
            add_action('wp_ajax_seo_aeo_orchestra_image_seo_ai_fix_preview',       array($this, 'ajax_image_seo_ai_fix_preview'));
            add_action('wp_ajax_seo_aeo_orchestra_image_seo_ai_fix_apply',         array($this, 'ajax_image_seo_ai_fix_apply'));
            add_action('wp_ajax_seo_aeo_orchestra_image_seo_bulk_changelog',      array($this, 'ajax_image_seo_bulk_changelog'));
            add_action('wp_ajax_seo_aeo_orchestra_image_seo_bulk_preview_get',    array($this, 'ajax_image_seo_bulk_preview_get'));
            add_action('wp_ajax_seo_aeo_orchestra_image_seo_bulk_preview_apply',  array($this, 'ajax_image_seo_bulk_preview_apply'));
            add_action('wp_ajax_seo_aeo_orchestra_image_seo_bulk_preview_clear',  array($this, 'ajax_image_seo_bulk_preview_clear'));
        } catch (Throwable $e) {
        }
    }

    // -- License --
    public function ajax_validate_license() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            $api = $this->require_api();
            if (!$api) return;
            $license_key = isset($_POST['license_key']) ? sanitize_text_field(wp_unslash($_POST['license_key'])) : '';
            if (empty($license_key)) {
                wp_send_json(array('error' => 'License key mancante.'));
                wp_die();
            }
            $result = $api->validate_license($license_key);
            if (!empty($result['valid'])) {
                update_option('seo_aeo_orchestra_license_key', $license_key);
                if (isset($this->main) && is_object($this->main)) {
                    $this->main->license_key = $license_key;
                }
                // 3.7.5 — Persisto domain_lock locale (registra che questo dominio è attivato)
                $current_domain = wp_parse_url(get_site_url(), PHP_URL_HOST);
                if ($current_domain) {
                    $current_domain = preg_replace('/^www\./', '', strtolower($current_domain));
                    update_option('seo_aeo_orchestra_domain_locked', array(
                        'domain' => $current_domain,
                        'activated_at' => current_time('mysql'),
                        'max_sites' => isset($result['max_sites']) ? intval($result['max_sites']) : 1,
                        'active_sites_count' => isset($result['active_sites_count']) ? intval($result['active_sites_count']) : 1,
                        'active_sites' => isset($result['active_sites']) ? (array)$result['active_sites'] : array($current_domain),
                    ));
                }
            }
            wp_send_json($result);
            wp_die();
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore validazione: ' . $e->getMessage()));
            wp_die();
        }
    }

    // -- Test Connection --
    public function ajax_test_connection() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            $api = $this->get_api();
            $api_url = defined('SEO_AEO_API_URL') ? SEO_AEO_API_URL : 'NON_DEFINITA';

            // Step 1: Quick socket check (2s) - catches firewall blocks
            if ($api && !$api->can_reach_host()) {
                $parsed = wp_parse_url($api_url);
                $host = isset($parsed['host']) ? $parsed['host'] : $api_url;
                wp_send_json(array(
                    'connected' => false,
                    'error' => 'Il server non riesce a raggiungere ' . $host . '. Controlla le impostazioni firewall dell\'hosting o contatta il supporto Aruba per abilitare le connessioni HTTP in uscita.',
                    'api_url' => $api_url,
                    'firewall_blocked' => true,
                    'tip' => 'Su hosting condiviso Aruba, le connessioni in uscita verso domini esterni potrebbero essere bloccate. Contatta l\'assistenza Aruba e chiedi di abilitare le connessioni verso ' . $host . ' sulla porta 443 (HTTPS).'
                ));
                wp_die();
                return;
            }

            // Step 2: Actual HTTP check
            $url = $api_url . '/api/health';
            $response = @wp_remote_get($url, array('timeout' => 5, 'sslverify' => false));
            if (is_wp_error($response)) {
                wp_send_json(array(
                    'connected' => false,
                    'error' => $response->get_error_message(),
                    'api_url' => $api_url,
                    'tip' => 'Il server non raggiunge ' . $api_url . '. Cause possibili: firewall hosting, SSL, DNS.'
                ));
            } else {
                $code = wp_remote_retrieve_response_code($response);
                wp_send_json(array(
                    'connected' => ($code >= 200 && $code < 400),
                    'status_code' => $code,
                    'api_url' => $api_url,
                    'body' => json_decode(wp_remote_retrieve_body($response), true)
                ));
            }
            wp_die();
        } catch (Throwable $e) {
            wp_send_json(array('connected' => false, 'error' => $e->getMessage()));
            wp_die();
        }
    }

    // -- SEO Analyze --
    public function ajax_analyze_seo() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        if (!current_user_can('edit_posts')) { wp_send_json(array('error' => 'forbidden')); return; }
        $url = isset($_POST['url']) ? sanitize_url($_POST['url']) : '';
        $keyword = isset($_POST['keyword']) ? sanitize_text_field(wp_unslash($_POST['keyword'])) : '';
        $content = isset($_POST['content']) ? sanitize_textarea_field(wp_unslash($_POST['content'])) : '';
        $this->safe_api_call('/ai/analyze', array('url' => $url, 'keyword' => $keyword, 'content' => $content));
    }

    // -- Meta Tags --
    public function ajax_generate_meta() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) { wp_send_json(array('error' => 'forbidden')); return; }
            $api = $this->require_api();
            if (!$api) return;
            $post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;
            $keyword = isset($_POST['keyword']) ? sanitize_text_field(wp_unslash($_POST['keyword'])) : '';
            $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
            $content = isset($_POST['content']) ? sanitize_textarea_field(wp_unslash($_POST['content'])) : '';
            // 3.26.1: dry_run = solo genera, non salva. Per il flow review-then-apply del bulk.
            $dry_run = !empty($_POST['dry_run']);

            if ($post_id > 0) {
                $post = get_post($post_id);
                if ($post) {
                    $title = $post->post_title;
                    $content = wp_strip_all_tags($post->post_content);
                }
            }

            $response = $api->api_request('/ai/generate-meta', array('title' => $title, 'content' => substr($content, 0, 3000), 'keyword' => $keyword, 'language' => $this->get_ai_language()));

            if (!$dry_run && $post_id > 0 && !isset($response['error'])) {
                if (!empty($response['title'])) update_post_meta($post_id, '_seo_aeo_meta_title', $response['title']);
                if (!empty($response['description'])) update_post_meta($post_id, '_seo_aeo_meta_description', $response['description']);
                if (!empty($response['keywords']) && is_array($response['keywords'])) update_post_meta($post_id, '_seo_aeo_meta_keywords', implode(', ', $response['keywords']));
            }
            wp_send_json($response);
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore generazione meta: ' . $e->getMessage()));
        }
        wp_die();
    }

    /**
     * AJAX: salva manualmente meta tags da review grid (3.26.1).
     */
    public function ajax_save_meta_manual() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) { wp_send_json(array('error' => 'Permessi insufficienti.')); return; }
            $post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;
            if ($post_id <= 0) { wp_send_json(array('error' => 'post_id mancante')); return; }
            $title = isset($_POST['meta_title']) ? sanitize_text_field(wp_unslash($_POST['meta_title'])) : '';
            $desc  = isset($_POST['meta_description']) ? sanitize_text_field(wp_unslash($_POST['meta_description'])) : '';
            $kw    = isset($_POST['meta_keywords']) ? sanitize_text_field(wp_unslash($_POST['meta_keywords'])) : '';

            if (class_exists('SEO_AEO_Engine_Bridge')) {
                SEO_AEO_Engine_Bridge::write_meta($post_id, array(
                    'meta_title'       => $title,
                    'meta_description' => $desc,
                    'focus_keyword'    => $kw,
                ));
            } else {
                if ($title) update_post_meta($post_id, '_seo_aeo_meta_title', $title);
                if ($desc)  update_post_meta($post_id, '_seo_aeo_meta_description', $desc);
                if ($kw)    update_post_meta($post_id, '_seo_aeo_meta_keywords', $kw);
            }
            wp_send_json(array('success' => true, 'post_id' => $post_id));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    // -- Content Generation --
    public function ajax_generate_content() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        if (!current_user_can('edit_posts')) { wp_send_json(array('error' => 'forbidden')); return; }
        $topic = isset($_POST['topic']) ? sanitize_text_field(wp_unslash($_POST['topic'])) : '';
        $keywords = isset($_POST['keywords']) ? array_map('trim', explode(',', sanitize_text_field(wp_unslash($_POST['keywords'])))) : array();
        $length = isset($_POST['length']) ? sanitize_text_field(wp_unslash($_POST['length'])) : 'medium';
        $include_faq = !empty($_POST['include_faq']) && in_array($_POST['include_faq'], array('true', 'yes', '1', 'on'), true);
        $this->safe_api_call('/ai/generate-content', array('topic' => $topic, 'keywords' => array_filter($keywords), 'length' => $length, 'include_faq' => $include_faq));
    }

    // -- Local SEO --
    public function ajax_local_seo() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        if (!current_user_can('edit_posts')) { wp_send_json(array('error' => 'forbidden')); return; }
        $service = isset($_POST['service']) ? sanitize_text_field(wp_unslash($_POST['service'])) : '';
        $city = isset($_POST['city']) ? sanitize_text_field(wp_unslash($_POST['city'])) : '';
        $template = isset($_POST['template']) ? sanitize_text_field(wp_unslash($_POST['template'])) : '';
        $this->safe_api_call('/ai/local-seo', array('service' => $service, 'city' => $city, 'template' => $template ? $template : 'service'));
    }

    // -- AEO Analysis --
    public function ajax_aeo_analyze() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        if (!current_user_can('edit_posts')) { wp_send_json(array('error' => 'forbidden')); return; }
        $url = isset($_POST['url']) ? sanitize_url($_POST['url']) : '';
        $keyword = isset($_POST['keyword']) ? sanitize_text_field(wp_unslash($_POST['keyword'])) : '';
        $content = isset($_POST['content']) ? sanitize_textarea_field(wp_unslash($_POST['content'])) : '';
        $this->safe_api_call('/ai/aeo-analyze', array('url' => $url, 'keyword' => $keyword, 'content' => $content));
    }

    // -- AEO Content --
    public function ajax_aeo_content() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        if (!current_user_can('edit_posts')) { wp_send_json(array('error' => 'forbidden')); return; }
        $topic = isset($_POST['topic']) ? sanitize_text_field(wp_unslash($_POST['topic'])) : '';
        $keywords = isset($_POST['keywords']) ? array_map('trim', explode(',', sanitize_text_field(wp_unslash($_POST['keywords'])))) : array();
        $target_engines = isset($_POST['target_engines']) ? array_map('sanitize_text_field', (array)$_POST['target_engines']) : array('google_ai', 'chatgpt', 'perplexity');
        $include_schema = !empty($_POST['include_schema']) && in_array($_POST['include_schema'], array('true', 'yes', '1', 'on'), true);
        $include_faq = !empty($_POST['include_faq']) && in_array($_POST['include_faq'], array('true', 'yes', '1', 'on'), true);
        $this->safe_api_call('/ai/aeo-content', array(
            'topic' => $topic,
            'keywords' => array_filter($keywords),
            'target_engines' => $target_engines,
            'include_schema' => $include_schema,
            'include_faq' => $include_faq
        ));
    }

    // -- Get site pages --
    public function ajax_get_pages() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        if (!current_user_can('edit_posts')) { wp_send_json(array('error' => 'forbidden')); return; }
        $pages = get_posts(array('numberposts' => -1, 'post_type' => 'page', 'post_status' => 'publish', 'orderby' => 'menu_order', 'order' => 'ASC'));
        $posts = get_posts(array('numberposts' => -1, 'post_type' => 'post', 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC'));
        $front_page_id = intval(get_option('page_on_front'));
        $result = array();

        if ($front_page_id) {
            $home = get_post($front_page_id);
            if ($home) {
                $result[] = array('id' => $home->ID, 'title' => $home->post_title, 'url' => get_permalink($home->ID), 'type' => 'homepage');
            }
        }
        foreach ($pages as $p) {
            if ($p->ID == $front_page_id) continue;
            $result[] = array('id' => $p->ID, 'title' => $p->post_title, 'url' => get_permalink($p->ID), 'type' => 'page');
        }
        foreach ($posts as $p) {
            $result[] = array('id' => $p->ID, 'title' => $p->post_title, 'url' => get_permalink($p->ID), 'type' => 'post');
        }
        wp_send_json($result);
    }

    // -- Suggest keywords --
    public function ajax_suggest_keywords() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        if (!current_user_can('edit_posts')) { wp_send_json(array('error' => 'forbidden')); return; }
        $context = isset($_POST['context']) ? sanitize_text_field(wp_unslash($_POST['context'])) : '';
        $agent_type = isset($_POST['agent_type']) ? sanitize_text_field(wp_unslash($_POST['agent_type'])) : '';
        $this->safe_api_call('/ai/suggest-keywords', array(
            'context' => $context,
            'agent_type' => $agent_type
        ));
    }

    // -- Publish content --
    public function ajax_publish_content() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        if (!current_user_can('publish_posts')) { wp_send_json(array('error' => 'forbidden')); return; }
        $title = sanitize_text_field(wp_unslash($_POST['title']));
        $content = wp_kses_post(wp_unslash($_POST['content']));
        $post_type = sanitize_text_field(wp_unslash($_POST['post_type']));
        $status = sanitize_text_field(wp_unslash($_POST['status']));
        if (!$title || !$content) {
            wp_send_json(array('error' => 'Titolo e contenuto sono obbligatori'));
            return;
        }
        $post_id = wp_insert_post(array(
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => $status ?: 'draft',
            'post_type'    => $post_type ?: 'post',
        ));
        if (is_wp_error($post_id)) {
            wp_send_json(array('error' => $post_id->get_error_message()));
        } else {
            // 3.32.0: marker AI-generated per KPI "AI Articles in TOP 10" su Analytics
            $this->mark_ai_generated($post_id, isset($_POST['source']) ? sanitize_key(wp_unslash($_POST['source'])) : 'publish_content');
            wp_send_json(array('success' => true, 'post_id' => $post_id, 'edit_url' => admin_url('post.php?post=' . $post_id . '&action=edit')));
        }
    }

    /**
     * @deprecated 3.35.8 — Logica spostata in SEO_AEO_AI_Helpers::mark_ai_generated().
     * Wrapper kept per backward compat: il metodo è chiamato come $this->mark_ai_generated()
     * da ajax_publish_content e ajax_autopilot_regenerate.
     */
    private function mark_ai_generated($post_id, $source = 'unknown', $job_id = 0) {
        SEO_AEO_AI_Helpers::mark_ai_generated($post_id, $source, $job_id);
    }

    // -- AI Image Generation --
    public function ajax_generate_image() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) { wp_send_json(array('error' => 'forbidden')); return; }
            $prompt = isset($_POST['prompt']) ? sanitize_text_field(wp_unslash($_POST['prompt'])) : '';
            if (empty($prompt)) {
                wp_send_json(array('error' => 'Inserisci una descrizione per l\'immagine'));
                wp_die();
            }
            $api = $this->require_api();
            if (!$api) return;
            $result = $api->api_request('/ai/generate-image', array(
                'prompt' => $prompt,
                'size' => '1024x1024'
            ));
            if (isset($result['error'])) {
                wp_send_json(array('error' => $result['error']));
                wp_die();
            }
            if (isset($result['image_base64'])) {
                $upload = $this->save_base64_to_media($result['image_base64'], $prompt);
                wp_send_json(array(
                    'success' => true,
                    'image_url' => $upload['url'],
                    'attachment_id' => $upload['id'],
                    'credits_used' => isset($result['credits_used']) ? $result['credits_used'] : 5
                ));
            } else {
                wp_send_json(array('error' => 'Nessuna immagine generata'));
            }
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore generazione immagine: ' . $e->getMessage()));
        }
        wp_die();
    }

    // -- Upload image from Media Library to post --
    public function ajax_upload_media() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        if (!current_user_can('upload_files')) { wp_send_json(array('error' => 'forbidden')); return; }
        $post_id = intval(wp_unslash($_POST['post_id']));
        $attachment_id = intval(wp_unslash($_POST['attachment_id']));
        $position = sanitize_text_field(wp_unslash($_POST['position']));

        if (!$post_id || !$attachment_id) {
            wp_send_json(array('error' => 'Post ID e Attachment ID richiesti'));
            return;
        }

        if ($position === 'featured') {
            set_post_thumbnail($post_id, $attachment_id);
            wp_send_json(array('success' => true, 'message' => 'Immagine in evidenza impostata'));
        } else {
            $image_url = wp_get_attachment_url($attachment_id);
            $image_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
            $img_tag = '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_alt) . '" class="aligncenter size-full" />';
            $post = get_post($post_id);
            $updated_content = $img_tag . "\n\n" . $post->post_content;
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $updated_content
            ));
            wp_send_json(array('success' => true, 'message' => 'Immagine aggiunta al contenuto', 'image_url' => $image_url));
        }
    }

    // -- Save base64 image to WP Media Library --
    private function save_base64_to_media($base64_data, $title = '') {
        $image_data = base64_decode($base64_data);
        $filename = sanitize_file_name('ai-image-' . time() . '.png');
        $upload_dir = wp_upload_dir();
        $filepath = $upload_dir['path'] . '/' . $filename;
        file_put_contents($filepath, $image_data);

        $filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'guid'           => $upload_dir['url'] . '/' . $filename,
            'post_mime_type' => $filetype['type'],
            'post_title'     => $title ? sanitize_text_field(substr($title, 0, 100)) : 'AI Generated Image',
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        $attach_id = wp_insert_attachment($attachment, $filepath);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
        wp_update_attachment_metadata($attach_id, $attach_data);
        update_post_meta($attach_id, '_wp_attachment_image_alt', $title ? sanitize_text_field(substr($title, 0, 100)) : 'AI Generated Image');

        return array(
            'id' => $attach_id,
            'url' => wp_get_attachment_url($attach_id)
        );
    }

    // -- Complete Article --
    public function ajax_complete_article() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) { wp_send_json(array('error' => 'forbidden')); return; }
            $topic = isset($_POST['topic']) ? sanitize_text_field(wp_unslash($_POST['topic'])) : '';
            $keyword = isset($_POST['keyword']) ? sanitize_text_field(wp_unslash($_POST['keyword'])) : '';
            $length = isset($_POST['length']) ? sanitize_text_field(wp_unslash($_POST['length'])) : 'medium';
            if (empty($topic) || empty($keyword)) {
                wp_send_json(array('error' => 'Argomento e keyword sono obbligatori'));
                wp_die();
            }
            $api = $this->require_api();
            if (!$api) return;
            $result = $api->api_request('/ai/complete-article', array(
                'topic' => $topic,
                'keyword' => $keyword,
                'length' => $length ? $length : 'medium',
                'include_image' => true,
                'include_meta' => true,
                'language' => $this->get_ai_language(),
            ));
            if (isset($result['error']) || isset($result['detail'])) {
                wp_send_json(array('error' => isset($result['detail']) ? $result['detail'] : $result['error']));
                wp_die();
            }
            $response = array(
                'content' => isset($result['content']) ? $result['content'] : '',
                'credits_used' => isset($result['credits_used']) ? $result['credits_used'] : 15
            );
            if (!empty($result['image_base64'])) {
                $upload = $this->save_base64_to_media($result['image_base64'], $topic);
                $response['image_url'] = $upload['url'];
                $response['attachment_id'] = $upload['id'];
            }
            if (isset($result['meta'])) {
                $response['meta'] = $result['meta'];
            }
            wp_send_json($response);
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore generazione articolo: ' . $e->getMessage()));
        }
        wp_die();
    }

    // -- Save post meta tags --
    public function ajax_save_post_meta() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        if (!current_user_can('edit_posts')) { wp_send_json(array('error' => 'forbidden')); return; }
        $post_id = intval(wp_unslash($_POST['post_id']));
        if (!$post_id) {
            wp_send_json(array('error' => 'Post ID mancante'));
            return;
        }
        $meta_title = sanitize_text_field(wp_unslash($_POST['meta_title']));
        $meta_desc = sanitize_text_field(wp_unslash($_POST['meta_description']));
        $meta_kw = sanitize_text_field(wp_unslash($_POST['meta_keywords']));
        if ($meta_title) update_post_meta($post_id, '_seo_aeo_meta_title', $meta_title);
        if ($meta_desc) update_post_meta($post_id, '_seo_aeo_meta_description', $meta_desc);
        if ($meta_kw) update_post_meta($post_id, '_seo_aeo_meta_keywords', $meta_kw);
        wp_send_json(array('success' => true));
    }

    // -- Orchestrate single page --
    public function ajax_orchestrate_single() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) { wp_send_json(array('error' => 'forbidden')); return; }
            $api = $this->require_api();
            if (!$api) return;
            $url = isset($_POST['url']) ? sanitize_url($_POST['url']) : '';
            $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
            $post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;

            $keyword = isset($_POST['keyword']) ? sanitize_text_field(wp_unslash($_POST['keyword'])) : '';
            if (empty($keyword) && !empty($title)) {
                $words = array_filter(explode(' ', strtolower($title)), function($w) { return strlen($w) > 3; });
                $keyword = implode(' ', array_slice($words, 0, 3));
            }

            $has_meta_title = $post_id > 0 ? (bool)get_post_meta($post_id, '_seo_aeo_meta_title', true) : false;
            $has_meta_desc = $post_id > 0 ? (bool)get_post_meta($post_id, '_seo_aeo_meta_description', true) : false;

            $seo = $api->api_request('/ai/analyze', array('url' => $url, 'keyword' => $keyword));
            $aeo = $api->api_request('/ai/aeo-analyze', array('url' => $url, 'keyword' => $keyword));

        $seo_score = isset($seo['seo_score']) ? intval($seo['seo_score']) : (isset($seo['score']) ? intval($seo['score']) : null);
        $aeo_score = isset($aeo['aeo_score']) ? intval($aeo['aeo_score']) : null;

        $actions = array();

        if (!$has_meta_title || !$has_meta_desc) {
            $actions[] = array(
                'agent' => 'meta_tags',
                'priority' => 'alta',
                'label' => 'Genera Meta Tags',
                'description' => 'Meta ' . (!$has_meta_title ? 'title' : '') . (!$has_meta_title && !$has_meta_desc ? ' e ' : '') . (!$has_meta_desc ? 'description' : '') . ' mancanti',
                'data' => array('post_id' => $post_id, 'keyword' => $keyword)
            );
        }

        if ($seo_score !== null && $seo_score < 70) {
            $top_issues = isset($seo['issues']) ? array_slice($seo['issues'], 0, 3) : array();
            $actions[] = array(
                'agent' => 'seo_analysis',
                'priority' => $seo_score < 40 ? 'critica' : 'media',
                'label' => 'Migliora SEO (Score: ' . $seo_score . ')',
                'description' => !empty($top_issues) ? (is_string($top_issues[0]) ? $top_issues[0] : '') : 'Score SEO basso',
                'data' => array('url' => $url, 'keyword' => $keyword)
            );
        }

        if ($aeo_score !== null && $aeo_score < 60) {
            $aeo_issues_text = array();
            if (isset($aeo['issues'])) {
                foreach (array_slice($aeo['issues'], 0, 2) as $iss) {
                    $aeo_issues_text[] = is_string($iss) ? $iss : (isset($iss['description']) ? $iss['description'] : '');
                }
            }
            $actions[] = array(
                'agent' => 'aeo_content',
                'priority' => $aeo_score < 30 ? 'alta' : 'media',
                'label' => 'Ottimizza per AI (AEO: ' . $aeo_score . ')',
                'description' => !empty($aeo_issues_text) ? implode('; ', array_filter($aeo_issues_text)) : 'Contenuto non ottimizzato per risposte AI',
                'data' => array('url' => $url, 'keyword' => $keyword, 'post_id' => $post_id)
            );
        }

        if (isset($seo['suggestions']) && count($seo['suggestions']) > 2) {
            $top_suggs = array();
            foreach (array_slice($seo['suggestions'], 0, 2) as $s) {
                $top_suggs[] = is_string($s) ? $s : (isset($s['description']) ? $s['description'] : '');
            }
            $actions[] = array(
                'agent' => 'content_generator',
                'priority' => 'bassa',
                'label' => 'Rigenera contenuto',
                'description' => !empty($top_suggs) ? implode('; ', array_filter($top_suggs)) : 'Il contenuto potrebbe essere migliorato per SEO + AEO',
                'data' => array('topic' => $title, 'keyword' => $keyword)
            );
        }

        if ($post_id > 0) {
            update_post_meta($post_id, '_seo_aeo_seo_score', $seo_score);
            update_post_meta($post_id, '_seo_aeo_aeo_score', $aeo_score);
            update_post_meta($post_id, '_seo_aeo_ai_visibility', isset($aeo['ai_visibility']) ? $aeo['ai_visibility'] : '');
            update_post_meta($post_id, '_seo_aeo_citability', isset($aeo['citability_score']) ? $aeo['citability_score'] : 0);
            update_post_meta($post_id, '_seo_aeo_issues_count', isset($seo['issues']) ? count($seo['issues']) : 0);
            update_post_meta($post_id, '_seo_aeo_aeo_issues_count', isset($aeo['issues']) ? count($aeo['issues']) : 0);
            update_post_meta($post_id, '_seo_aeo_top_suggestion', isset($seo['suggestions'][0]) ? (is_string($seo['suggestions'][0]) ? $seo['suggestions'][0] : '') : '');
            update_post_meta($post_id, '_seo_aeo_last_analysis', current_time('mysql'));
        }

        wp_send_json(array(
            'url' => $url,
            'title' => $title,
            'post_id' => $post_id,
            'seo_score' => $seo_score,
            'aeo_score' => $aeo_score,
            'ai_visibility' => isset($aeo['ai_visibility']) ? $aeo['ai_visibility'] : null,
            'citability' => isset($aeo['citability_score']) ? $aeo['citability_score'] : null,
            'seo_issues' => isset($seo['issues']) ? count($seo['issues']) : 0,
            'aeo_issues' => isset($aeo['issues']) ? count($aeo['issues']) : 0,
            'has_meta' => $has_meta_title && $has_meta_desc,
            'actions' => $actions,
            'seo_detail' => $seo,
            'aeo_detail' => $aeo
        ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore orchestrazione: ' . $e->getMessage()));
        }
        wp_die();
    }

    // -- Execute orchestrator action --
    public function ajax_execute_action() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) { wp_send_json(array('error' => 'forbidden')); return; }
            $api = $this->require_api();
            if (!$api) return;
            $agent = isset($_POST['agent']) ? sanitize_text_field(wp_unslash($_POST['agent'])) : '';
            $data = isset($_POST['action_data']) ? (array)$_POST['action_data'] : array();

            switch ($agent) {
                case 'meta_tags':
                    $post_id = isset($data['post_id']) ? intval($data['post_id']) : 0;
                    $keyword = isset($data['keyword']) ? sanitize_text_field($data['keyword']) : '';
                    $result = $api->api_request('/ai/generate-meta', array(
                        'title' => get_the_title($post_id),
                        'content' => substr(wp_strip_all_tags(get_post_field('post_content', $post_id)), 0, 3000),
                        'keyword' => $keyword,
                        'language' => $this->get_ai_language(),
                    ));
                    if (!isset($result['error']) && $post_id > 0) {
                        if (!empty($result['title'])) update_post_meta($post_id, '_seo_aeo_meta_title', $result['title']);
                        if (!empty($result['description'])) update_post_meta($post_id, '_seo_aeo_meta_description', $result['description']);
                        if (!empty($result['keywords']) && is_array($result['keywords'])) update_post_meta($post_id, '_seo_aeo_meta_keywords', implode(', ', $result['keywords']));
                        $result['saved'] = true;
                    }
                    wp_send_json($result);
                    break;

                case 'aeo_content':
                    $keyword = isset($data['keyword']) ? sanitize_text_field($data['keyword']) : '';
                    $result = $api->api_request('/ai/aeo-content', array(
                        'topic' => $keyword,
                        'keywords' => array($keyword),
                        'target_engines' => array('google_ai', 'chatgpt', 'perplexity'),
                        'include_schema' => true,
                        'include_faq' => true,
                        'language' => $this->get_ai_language(),
                    ));
                    wp_send_json($result);
                    break;

                case 'content_generator':
                    $topic = isset($data['topic']) ? sanitize_text_field($data['topic']) : '';
                    $keyword = isset($data['keyword']) ? sanitize_text_field($data['keyword']) : '';
                    $result = $api->api_request('/ai/generate-content', array(
                        'topic' => $topic,
                        'keywords' => array($keyword),
                        'length' => 'medium',
                        'include_faq' => true,
                        'language' => $this->get_ai_language(),
                    ));
                    wp_send_json($result);
                    break;

                default:
                    wp_send_json(array('error' => 'Agente non riconosciuto: ' . $agent));
            }
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore esecuzione azione: ' . $e->getMessage()));
        }
        wp_die();
    }

    // -- Get Credit Balance from API --
    // ============================================================
    // PROPOSE / REVIEW / APPLY — v3.0.0
    // ============================================================

    public function ajax_propose_action() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) {
                wp_send_json(array('error' => 'Permessi insufficienti.'));
                return;
            }
            $api = $this->require_api();
            if (!$api) return;

            $agent = isset($_POST['agent']) ? sanitize_key(wp_unslash($_POST['agent'])) : '';
            $allowed = array('meta_tags', 'aeo_content', 'content_generator', 'seo_analysis');
            if (!in_array($agent, $allowed, true)) {
                wp_send_json(array('error' => 'Agente non supportato: ' . $agent));
                return;
            }

            $post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;
            $url     = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
            if ($post_id <= 0 || empty($url)) {
                wp_send_json(array('error' => 'Parametri mancanti (post_id, url).'));
                return;
            }

            $post = get_post($post_id);
            if (!$post) {
                wp_send_json(array('error' => 'Post non trovato: ' . $post_id));
                return;
            }

            // BRIDGE: leggo meta dal plugin SEO attivo (Yoast/RankMath/AIOSEO/native)
            $bridge_meta = class_exists('SEO_AEO_SEO_Engine_Bridge')
                ? SEO_AEO_SEO_Engine_Bridge::read_meta($post_id)
                : array('meta_title' => '', 'meta_description' => '', 'meta_keywords' => array());
            $current_state = array(
                'post_content'     => wp_strip_all_tags($post->post_content),
                'post_title'       => $post->post_title,
                'meta_title'       => $bridge_meta['meta_title'],
                'meta_description' => $bridge_meta['meta_description'],
                'meta_keywords'    => $bridge_meta['meta_keywords'],
            );

            $keywords_raw = isset($_POST['keywords']) ? sanitize_text_field(wp_unslash($_POST['keywords'])) : '';
            $keywords = $keywords_raw !== '' ? array_filter(array_map('trim', explode(',', $keywords_raw))) : array();
            $params = array(
                'topic'    => isset($_POST['topic']) ? sanitize_text_field(wp_unslash($_POST['topic'])) : $post->post_title,
                'keywords' => array_values($keywords),
                'length'   => isset($_POST['length']) ? sanitize_key(wp_unslash($_POST['length'])) : 'medium',
            );

            $payload = array(
                'post_id'       => $post_id,
                'url'           => $url,
                'current_state' => $current_state,
                'params'        => $params,
            );
            $response = $api->api_request('/proposals/propose/' . $agent, $payload);

            if (isset($response['error'])) {
                $msg = isset($response['message']) ? $response['message']
                     : (is_string($response['error']) ? $response['error'] : 'Errore sconosciuto');
                wp_send_json(array('error' => $msg));
                return;
            }

            // 3.4.7 — Detection Elementor: pagine Elementor renderizzano da _elementor_data
            // (JSON), non da wp_posts.post_content. Quindi modifiche di aeo_content/
            // content_generator/seo_analysis verranno salvate ma NON visibili sul sito
            // pubblico finché l'utente non aggiorna la pagina con Elementor stesso.
            // I meta_tags via Yoast bridge funzionano normalmente.
            if (is_array($response)) {
                $elementor_mode = get_post_meta($post_id, '_elementor_edit_mode', true);
                $elementor_data = get_post_meta($post_id, '_elementor_data', true);
                $response['is_elementor'] = ($elementor_mode === 'builder' && !empty($elementor_data));
                $response['agent'] = $agent;
            }

            wp_send_json($response);

        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore generazione proposta: ' . $e->getMessage()));
        }
        wp_die();
    }

    public function ajax_apply_proposal() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) {
                wp_send_json(array('error' => 'Permessi insufficienti.'));
                return;
            }
            $api = $this->require_api();
            if (!$api) return;

            $proposal_id = isset($_POST['proposal_id']) ? sanitize_text_field(wp_unslash($_POST['proposal_id'])) : '';
            if (empty($proposal_id) || !preg_match('/^p_[a-z0-9]{6,20}$/', $proposal_id)) {
                wp_send_json(array('error' => 'proposal_id non valido.'));
                return;
            }

            $override = array();
            if (isset($_POST['override_post_content']) && $_POST['override_post_content'] !== '') {
                $override['post_content'] = wp_kses_post(wp_unslash($_POST['override_post_content']));
            }
            if (isset($_POST['override_meta_title']) && $_POST['override_meta_title'] !== '') {
                $override['meta_title'] = sanitize_text_field(wp_unslash($_POST['override_meta_title']));
            }
            if (isset($_POST['override_meta_description']) && $_POST['override_meta_description'] !== '') {
                $override['meta_description'] = sanitize_textarea_field(wp_unslash($_POST['override_meta_description']));
            }
            if (isset($_POST['override_meta_keywords']) && $_POST['override_meta_keywords'] !== '') {
                $kws = array_filter(array_map('trim', explode(',', sanitize_text_field(wp_unslash($_POST['override_meta_keywords'])))));
                if (!empty($kws)) $override['meta_keywords'] = array_values($kws);
            }

            $payload = array();
            if (!empty($override)) $payload['override_state'] = $override;

            $response = $api->api_request('/proposals/apply/' . $proposal_id, $payload);

            if (isset($response['error']) || empty($response['applied'])) {
                $msg = isset($response['message']) ? $response['message']
                     : (isset($response['detail']) ? $response['detail']
                     : (isset($response['error']) ? $response['error'] : 'Apply fallito'));
                wp_send_json(array('error' => $msg));
                return;
            }

            $post_id     = intval($response['post_id']);
            $final_state = isset($response['final_state']) ? $response['final_state'] : array();

            $post = get_post($post_id);
            if (!$post) {
                wp_send_json(array('error' => 'Post non trovato al momento apply: ' . $post_id));
                return;
            }

            // BRIDGE: leggo meta correnti dal plugin SEO attivo per snapshot
            $bridge_prev = class_exists('SEO_AEO_SEO_Engine_Bridge')
                ? SEO_AEO_SEO_Engine_Bridge::read_meta($post_id)
                : array('meta_title' => '', 'meta_description' => '', 'meta_keywords' => array());
            $previous_state = array(
                'post_content'     => $post->post_content,
                'post_title'       => $post->post_title,
                'meta_title'       => $bridge_prev['meta_title'],
                'meta_description' => $bridge_prev['meta_description'],
                'meta_keywords'    => is_array($bridge_prev['meta_keywords'])
                                        ? implode(', ', $bridge_prev['meta_keywords'])
                                        : (string) $bridge_prev['meta_keywords'],
            );

            $applied_state = array();

            if (isset($final_state['post_content']) && $final_state['post_content'] !== null) {
                $applied_state['post_content'] = $final_state['post_content'];
                wp_update_post(array('ID' => $post_id, 'post_content' => $final_state['post_content']));
            }
            if (isset($final_state['post_title']) && $final_state['post_title'] !== null) {
                $applied_state['post_title'] = $final_state['post_title'];
                wp_update_post(array('ID' => $post_id, 'post_title' => $final_state['post_title']));
            }
            // BRIDGE: scrivo sul plugin SEO attivo + Orchestra native (doppia scrittura)
            $meta_to_write = array();
            if (isset($final_state['meta_title']) && $final_state['meta_title'] !== null) {
                $applied_state['meta_title'] = $final_state['meta_title'];
                $meta_to_write['meta_title'] = $final_state['meta_title'];
            }
            if (isset($final_state['meta_description']) && $final_state['meta_description'] !== null) {
                $applied_state['meta_description'] = $final_state['meta_description'];
                $meta_to_write['meta_description'] = $final_state['meta_description'];
            }
            if (isset($final_state['meta_keywords']) && is_array($final_state['meta_keywords'])) {
                $keywords_csv = implode(', ', $final_state['meta_keywords']);
                $applied_state['meta_keywords'] = $keywords_csv;
                $meta_to_write['meta_keywords'] = $final_state['meta_keywords'];
            }
            if (!empty($meta_to_write) && class_exists('SEO_AEO_SEO_Engine_Bridge')) {
                $bridge_result = SEO_AEO_SEO_Engine_Bridge::write_meta($post_id, $meta_to_write);
                $applied_state['_seo_engine'] = $bridge_result['engine_label'];
            } elseif (!empty($meta_to_write)) {
                // Fallback se bridge non caricato
                if (isset($meta_to_write['meta_title'])) {
                    update_post_meta($post_id, '_seo_aeo_meta_title', $meta_to_write['meta_title']);
                }
                if (isset($meta_to_write['meta_description'])) {
                    update_post_meta($post_id, '_seo_aeo_meta_description', $meta_to_write['meta_description']);
                }
                if (isset($meta_to_write['meta_keywords'])) {
                    update_post_meta($post_id, '_seo_aeo_meta_keywords',
                        is_array($meta_to_write['meta_keywords'])
                            ? implode(', ', $meta_to_write['meta_keywords'])
                            : $meta_to_write['meta_keywords']);
                }
            }

            $snapshot_id = null;
            if (class_exists('SEO_AEO_Snapshot_Manager')) {
                $snapshot_mgr = new SEO_AEO_Snapshot_Manager();
                $snapshot_id = $snapshot_mgr->create_snapshot($post_id, $proposal_id,
                                                              $previous_state, $applied_state);
            }

            wp_send_json(array(
                'applied'     => true,
                'proposal_id' => $proposal_id,
                'post_id'     => $post_id,
                'snapshot_id' => $snapshot_id,
                'applied_at'  => isset($response['applied_at']) ? $response['applied_at'] : gmdate('c'),
                'final_state' => $applied_state,
            ));

        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore apply proposta: ' . $e->getMessage()));
        }
        wp_die();
    }

    public function ajax_discard_proposal() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) {
                wp_send_json(array('error' => 'Permessi insufficienti.'));
                return;
            }
            $api = $this->require_api();
            if (!$api) return;

            $proposal_id = isset($_POST['proposal_id']) ? sanitize_text_field(wp_unslash($_POST['proposal_id'])) : '';
            if (empty($proposal_id) || !preg_match('/^p_[a-z0-9]{6,20}$/', $proposal_id)) {
                wp_send_json(array('error' => 'proposal_id non valido.'));
                return;
            }

            $response = $api->api_request('/proposals/discard/' . $proposal_id, array());

            if (isset($response['error']) || empty($response['discarded'])) {
                $msg = isset($response['message']) ? $response['message']
                     : (isset($response['detail']) ? $response['detail']
                     : (isset($response['error']) ? $response['error'] : 'Discard fallito'));
                wp_send_json(array('error' => $msg));
                return;
            }

            wp_send_json($response);

        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore discard proposta: ' . $e->getMessage()));
        }
        wp_die();
    }

    public function ajax_undo_snapshot() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) {
                wp_send_json(array('error' => 'Permessi insufficienti.'));
                return;
            }

            $snapshot_id = isset($_POST['snapshot_id']) ? sanitize_text_field(wp_unslash($_POST['snapshot_id'])) : '';
            if (empty($snapshot_id)) {
                wp_send_json(array('error' => 'snapshot_id mancante.'));
                return;
            }

            if (!class_exists('SEO_AEO_Snapshot_Manager')) {
                wp_send_json(array('error' => 'Snapshot manager non disponibile.'));
                return;
            }

            $snapshot_mgr = new SEO_AEO_Snapshot_Manager();
            $result = $snapshot_mgr->restore_snapshot($snapshot_id);

            if (!$result['success']) {
                wp_send_json(array('error' => $result['message']));
                return;
            }

            wp_send_json(array(
                'restored'    => true,
                'snapshot_id' => $snapshot_id,
                'post_id'     => $result['post_id'],
                'message'     => 'Versione precedente ripristinata correttamente.',
            ));

        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore ripristino snapshot: ' . $e->getMessage()));
        }
        wp_die();
    }

    public function ajax_list_snapshots() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) {
                wp_send_json(array('error' => 'Permessi insufficienti.'));
                return;
            }

            $limit = isset($_POST['limit']) ? intval(wp_unslash($_POST['limit'])) : 20;
            $limit = max(1, min($limit, 100));

            if (!class_exists('SEO_AEO_Snapshot_Manager')) {
                wp_send_json(array('items' => array(), 'count' => 0));
                return;
            }

            $snapshot_mgr = new SEO_AEO_Snapshot_Manager();
            $items = $snapshot_mgr->list_snapshots($limit);

            wp_send_json(array('items' => $items, 'count' => count($items)));

        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore lista snapshot: ' . $e->getMessage()));
        }
        wp_die();
    }

    public function ajax_get_snapshot_details() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) {
                wp_send_json(array('error' => 'Permessi insufficienti.'));
                return;
            }

            $snapshot_id = isset($_POST['snapshot_id']) ? sanitize_text_field(wp_unslash($_POST['snapshot_id'])) : '';
            if (empty($snapshot_id)) {
                wp_send_json(array('error' => 'snapshot_id mancante.'));
                return;
            }

            if (!class_exists('SEO_AEO_Snapshot_Manager')) {
                wp_send_json(array('error' => 'Snapshot manager non disponibile.'));
                return;
            }

            $snapshot_mgr = new SEO_AEO_Snapshot_Manager();
            $snapshot = $snapshot_mgr->get_snapshot($snapshot_id);

            if (!$snapshot || !is_array($snapshot)) {
                wp_send_json(array('error' => 'Snapshot non trovato o scaduto.'));
                return;
            }

            wp_send_json(array(
                'snapshot_id'    => isset($snapshot['snapshot_id']) ? $snapshot['snapshot_id'] : $snapshot_id,
                'proposal_id'    => isset($snapshot['proposal_id']) ? $snapshot['proposal_id'] : '',
                'post_id'        => isset($snapshot['post_id']) ? intval($snapshot['post_id']) : 0,
                'post_title'     => isset($snapshot['post_title']) ? $snapshot['post_title'] : '',
                'applied_at'     => isset($snapshot['applied_at']) ? intval($snapshot['applied_at']) : 0,
                'applied_by'     => isset($snapshot['applied_by']) ? intval($snapshot['applied_by']) : 0,
                'expires_at'     => isset($snapshot['expires_at']) ? intval($snapshot['expires_at']) : 0,
                'previous_state' => isset($snapshot['previous_state']) ? $snapshot['previous_state'] : array(),
                'applied_state'  => isset($snapshot['applied_state']) ? $snapshot['applied_state'] : array(),
            ));

        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore caricamento dettagli: ' . $e->getMessage()));
        }
        wp_die();
    }

    public function ajax_clear_analyses_history() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'Permessi insufficienti.'));
                return;
            }

            // Conta entries prima del delete (per dare feedback all'utente)
            $raw = get_option('seo_aeo_orchestra_history_json', '');
            $count = 0;
            if (!empty($raw)) {
                $arr = json_decode($raw, true);
                if (is_array($arr)) $count = count($arr);
            }

            // Elimina opzione (delete + add per garantire reset anche se autoload misbehaved)
            delete_option('seo_aeo_orchestra_history_json');

            wp_send_json(array(
                'cleared' => $count,
                'success' => true
            ));

        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore svuotamento cronologia: ' . $e->getMessage()));
        }
        wp_die();
    }

    /**
     * 3.6.0 — Live update Health Score senza refresh pagina.
     * Ricalcola dalla cronologia WP option e ritorna JSON con tutti i field hero.
     */
    public function ajax_get_health_score() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) {
                wp_send_json(array('error' => 'Permessi insufficienti.'));
                return;
            }

            $history_raw = get_option('seo_aeo_orchestra_history_json', '');
            $orch_history = array();
            if (!empty($history_raw)) {
                $decoded = json_decode($history_raw, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $h) {
                        if (isset($h['section']) && $h['section'] === 'orchestrator') {
                            $orch_history[] = $h;
                        }
                    }
                }
            }

            // Estrae score da un item (avg di seo + aeo)
            $extract = function($item) {
                if (empty($item['data'])) return null;
                $data = json_decode($item['data'], true);
                if (!is_array($data)) return null;
                $scores = array();
                foreach (array('avg_seo', 'avg_aeo') as $k) {
                    if (isset($data[$k]) && is_numeric($data[$k])) $scores[] = (float) $data[$k];
                }
                if (empty($scores)) return null;
                return (int) round(array_sum($scores) / count($scores));
            };

            $score_current  = !empty($orch_history) ? $extract($orch_history[0]) : null;
            $score_previous = (count($orch_history) > 1) ? $extract($orch_history[1]) : null;
            $score_delta    = ($score_current !== null && $score_previous !== null)
                              ? ($score_current - $score_previous) : null;
            $score_class    = 'neutral';
            if ($score_current !== null) {
                if ($score_current >= 75)      $score_class = 'good';
                elseif ($score_current >= 50)  $score_class = 'mid';
                else                           $score_class = 'bad';
            }

            $last_date = !empty($orch_history) ? $orch_history[0]['date'] : null;
            $days_since = null;
            if ($last_date) {
                $ts = strtotime($last_date);
                if ($ts) $days_since = (int) floor((time() - $ts) / 86400);
            }

            // Sparkline: ultimi 8 score
            $sparkline = array();
            foreach (array_slice($orch_history, 0, 8) as $h) {
                $s = $extract($h);
                if ($s !== null) $sparkline[] = $s;
            }
            $sparkline = array_reverse($sparkline);

            wp_send_json(array(
                'score_current'    => $score_current,
                'score_previous'   => $score_previous,
                'score_delta'      => $score_delta,
                'score_class'      => $score_class,
                'days_since_last'  => $days_since,
                'sparkline_scores' => $sparkline,
                'has_history'      => !empty($orch_history)
            ));

        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore calcolo health score: ' . $e->getMessage()));
        }
        wp_die();
    }

    /**
     * 3.7.0 — Storico transazioni crediti (audit lato cliente).
     * Recupera ultime 50 transazioni dal backend via license_key.
     */
    public function ajax_get_transactions() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) {
                wp_send_json(array('error' => 'Permessi insufficienti.'));
                return;
            }
            $api = $this->require_api();
            if (!$api) return;
            $result = $api->api_request('/plugin/wallet/transactions');
            if (is_array($result) && isset($result['error'])) {
                wp_send_json(array('error' => $result['error']));
                return;
            }
            wp_send_json($result);
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore caricamento storico: ' . $e->getMessage()));
        }
        wp_die();
    }

    /** 3.7.0 — Dismiss onboarding wizard (set option 1) */
    public function ajax_dismiss_onboarding() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) {
                wp_send_json(array('error' => 'Permessi insufficienti.'));
                return;
            }
            update_option('seo_aeo_orchestra_onboarding_dismissed', 1, false);
            wp_send_json(array('success' => true));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
        wp_die();
    }

    public function ajax_get_credits() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) { wp_send_json(array('credits' => '?', 'error' => 'forbidden')); return; }
            $api = $this->require_api();
            if (!$api) return;
            $result = $api->api_request('/plugin/wallet/balance');
            if (is_array($result) && isset($result['error'])) {
                wp_send_json(array('credits' => '?', 'error' => $result['error']));
            } else {
                $plan = isset($result['plan_credits']) ? intval($result['plan_credits']) : 0;
                $topup = isset($result['topup_credits']) ? intval($result['topup_credits']) : 0;
                wp_send_json(array('credits' => $plan + $topup, 'plan_credits' => $plan, 'topup_credits' => $topup));
            }
        } catch (Throwable $e) {
            wp_send_json(array('credits' => '?', 'error' => $e->getMessage()));
        }
        wp_die();
    }

    // -- Dismiss First Use Wizard --
    public function ajax_dismiss_first_use() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        if (!current_user_can('edit_posts')) { wp_send_json(array('error' => 'forbidden')); return; }
        update_option('seo_aeo_orchestra_first_use_done', '1');
        wp_send_json(array('success' => true));
    }

    // -- Re-run a history item --
    public function ajax_rerun_history() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        if (!current_user_can('edit_posts')) { wp_send_json(array('error' => 'forbidden')); return; }
        $type = sanitize_text_field(wp_unslash($_POST['rerun_type']));
        $data_raw = isset($_POST['rerun_data']) ? wp_unslash($_POST['rerun_data']) : '';
        $data = json_decode($data_raw, true);
        if (!is_array($data)) { $data = array(); }

        // Estrai url + keyword con fallback su nomi alternativi (storia legacy)
        $extract_url = function($d) {
            foreach (array('url', 'page_url', 'target_url') as $k) {
                if (!empty($d[$k])) return $d[$k];
            }
            return '';
        };
        $extract_kw = function($d) {
            foreach (array('keyword', 'main_keyword', 'primary_keyword', 'focus_keyword') as $k) {
                if (!empty($d[$k])) return $d[$k];
            }
            return '';
        };

        switch ($type) {
            case 'seo_analysis':
                $url = sanitize_url($extract_url($data));
                $keyword = sanitize_text_field($extract_kw($data));
                if (!$url) { wp_send_json(array('error' => 'URL mancante nei dati storici')); return; }
                wp_send_json(array('redirect' => 'seo-aeo-analyze', 'params' => array('url' => $url, 'keyword' => $keyword)));
                break;
            case 'aeo_analysis':
                $url = sanitize_url($extract_url($data));
                $keyword = sanitize_text_field($extract_kw($data));
                if (!$url) { wp_send_json(array('error' => 'URL mancante nei dati storici')); return; }
                wp_send_json(array('redirect' => 'seo-aeo-aeo-analysis', 'params' => array('url' => $url, 'keyword' => $keyword)));
                break;
            case 'orchestrator':
                wp_send_json(array('redirect' => 'seo-aeo-orchestratore', 'params' => array()));
                break;
            default:
                // Fallback generico: prova a redirect verso la pagina del tipo
                $url = sanitize_url($extract_url($data));
                $keyword = sanitize_text_field($extract_kw($data));
                $redirect_map = array(
                    'meta_tags' => 'seo-aeo-meta-tags',
                    'seo_content' => 'seo-aeo-content',
                    'aeo_content' => 'seo-aeo-aeo-content',
                    'local_seo' => 'seo-aeo-local-seo',
                );
                $redirect = isset($redirect_map[$type]) ? $redirect_map[$type] : 'seo-aeo-orchestratore';
                wp_send_json(array('redirect' => $redirect, 'params' => array('url' => $url, 'keyword' => $keyword)));
        }
    }

    // -- GSC integration (v3.10.0) --
    private function gsc_proxy($endpoint, $data = array(), $require_caps = 'edit_posts') {
        if (!current_user_can($require_caps)) {
            wp_send_json(array('error' => 'Permessi insufficienti.'));
            return;
        }
        $api = $this->require_api();
        if (!$api) return;
        $result = $api->api_request($endpoint, $data);
        wp_send_json($result);
    }

    public function ajax_gsc_status() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            $this->gsc_proxy('/gsc/status');
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    public function ajax_gsc_connect_url() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'Solo amministratori possono connettere GSC.'));
                return;
            }
            $api = $this->require_api();
            if (!$api) return;
            // Return URL = WP admin dashboard plugin (per redirect post-OAuth)
            $return_url = admin_url('admin.php?page=seo-aeo-orchestratore');
            $result = $api->api_request('/gsc/auth-url', array(
                'return_url' => $return_url,
            ));
            wp_send_json($result);
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    public function ajax_gsc_disconnect() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            $this->gsc_proxy('/gsc/disconnect', array(), 'manage_options');
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    public function ajax_gsc_list_sites() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            $this->gsc_proxy('/gsc/list-sites');
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    public function ajax_gsc_top_pages() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            $site_url = isset($_POST['site_url']) ? esc_url_raw(wp_unslash($_POST['site_url'])) : '';
            $days = isset($_POST['days']) ? intval(wp_unslash($_POST['days'])) : 30;
            $row_limit = isset($_POST['row_limit']) ? intval(wp_unslash($_POST['row_limit'])) : 10;
            $order_by = isset($_POST['order_by']) ? sanitize_key(wp_unslash($_POST['order_by'])) : 'impressions';
            if (empty($site_url)) {
                wp_send_json(array('error' => 'site_url mancante.'));
                return;
            }
            $this->gsc_proxy('/gsc/top-pages', array(
                'site_url' => $site_url,
                'days' => $days,
                'row_limit' => $row_limit,
                'order_by' => $order_by,
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * 3.28.2 — Daily timeline (clicks/impressions/ctr/position) per the chart.
     */
    public function ajax_gsc_timeline() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            $site_url = isset($_POST['site_url']) ? esc_url_raw(wp_unslash($_POST['site_url'])) : '';
            $days = isset($_POST['days']) ? intval(wp_unslash($_POST['days'])) : 28;
            if (empty($site_url)) {
                wp_send_json(array('error' => 'site_url mancante.'));
                return;
            }
            $this->gsc_proxy('/gsc/timeline', array(
                'site_url' => $site_url,
                'days' => $days,
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * 3.28.2 — AEO Orchestra impact: post-activation vs pre-activation comparison.
     */
    public function ajax_gsc_aeo_impact() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            $site_url = isset($_POST['site_url']) ? esc_url_raw(wp_unslash($_POST['site_url'])) : '';
            if (empty($site_url)) {
                wp_send_json(array('error' => 'site_url mancante.'));
                return;
            }
            $this->gsc_proxy('/gsc/aeo-impact', array(
                'site_url' => $site_url,
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * 3.32.0 — Analytics page bootstrap. Backend ritorna summary aggregato (clicks,
     * impressions, ctr, position) + topQueries + topPages + timeSeries + i 5 KPI
     * differenziatori Orchestra (ai_articles_in_top10, brand_voice_impact,
     * redirect_rescues, auto_pilot_roi, meta_freshness_score).
     */
    public function ajax_analytics_fetch() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'forbidden'));
                return;
            }
            $period = isset($_POST['period']) ? sanitize_text_field(wp_unslash($_POST['period'])) : 'last28Days';
            $allowed = array('last7Days', 'last28Days', 'last90Days');
            if (!in_array($period, $allowed, true)) $period = 'last28Days';
            $api = $this->require_api();
            if (!$api) return;
            $result = $api->api_request('/gsc/analytics', array(
                'period' => $period,
            ));
            wp_send_json($result);
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * 3.32.0 — Lazy fetch keyword breakdown per una specifica top page.
     * Triggered on row expand nel template analytics.
     */
    public function ajax_analytics_page_keywords() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'forbidden'));
                return;
            }
            $page_url = isset($_POST['page_url']) ? esc_url_raw(wp_unslash($_POST['page_url'])) : '';
            $period = isset($_POST['period']) ? sanitize_text_field(wp_unslash($_POST['period'])) : 'last28Days';
            $allowed = array('last7Days', 'last28Days', 'last90Days');
            if (!in_array($period, $allowed, true)) $period = 'last28Days';
            if (empty($page_url)) {
                wp_send_json(array('error' => 'page_url missing'));
                return;
            }
            $api = $this->require_api();
            if (!$api) return;
            $result = $api->api_request('/gsc/page-keywords', array(
                'page_url' => $page_url,
                'period'   => $period,
            ));
            wp_send_json($result);
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    // -- Bing Webmaster Tools — AI Performance (v3.29.0) --
    /**
     * Generic Bing proxy. Mirror of gsc_proxy(). license_key auto-injected by api_request.
     */
    private function bing_proxy($endpoint, $data = array(), $require_caps = 'edit_posts') {
        if (!current_user_can($require_caps)) {
            wp_send_json(array('error' => 'Permessi insufficienti.'));
            return;
        }
        $api = $this->require_api();
        if (!$api) return;
        $result = $api->api_request($endpoint, $data);
        wp_send_json($result);
    }

    public function ajax_bing_status() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            $this->bing_proxy('/bing/status');
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    public function ajax_bing_auth_url() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'Solo amministratori possono connettere Bing.'));
                return;
            }
            $api = $this->require_api();
            if (!$api) return;
            $return_url = admin_url('admin.php?page=seo-aeo-orchestratore');
            $result = $api->api_request('/bing/auth-url', array(
                'return_url' => $return_url,
            ));
            wp_send_json($result);
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    public function ajax_bing_disconnect() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            $this->bing_proxy('/bing/disconnect', array(), 'manage_options');
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    public function ajax_bing_list_sites() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            $this->bing_proxy('/bing/list-sites');
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    public function ajax_bing_ai_performance() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            $site_url = isset($_POST['site_url']) ? esc_url_raw(wp_unslash($_POST['site_url'])) : '';
            $days = isset($_POST['days']) ? intval(wp_unslash($_POST['days'])) : 28;
            if (empty($site_url)) {
                wp_send_json(array('error' => 'site_url mancante.'));
                return;
            }
            $this->bing_proxy('/bing/ai-performance', array(
                'site_url' => $site_url,
                'days' => $days,
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    // -- Cannibalization detector (v3.9.0) --
    /**
     * Estrae le focus keyword di un singolo post da Yoast / RankMath / AIOSEO / Native.
     * Ritorna array di stringhe (può contenere CSV non espansi: il backend espande).
     */
    private function read_focus_keywords($post_id) {
        $kws = array();

        // Yoast: singola focus keyword
        $yoast = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
        if (!empty($yoast)) $kws[] = (string) $yoast;

        // RankMath: CSV
        $rm = get_post_meta($post_id, 'rank_math_focus_keyword', true);
        if (!empty($rm)) $kws[] = (string) $rm;

        // AIOSEO: keywords JSON [{label,value},...] OR keyphrases JSON
        $aio_raw = get_post_meta($post_id, '_aioseo_keywords', true);
        if (!empty($aio_raw)) {
            $decoded = json_decode($aio_raw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $entry) {
                    if (is_array($entry) && isset($entry['label'])) {
                        $kws[] = (string) $entry['label'];
                    } elseif (is_string($entry)) {
                        $kws[] = $entry;
                    }
                }
            } else {
                $kws[] = (string) $aio_raw;
            }
        }
        // AIOSEO Pro: focus keyphrase
        $aio_focus = get_post_meta($post_id, '_aioseo_keyphrases', true);
        if (!empty($aio_focus)) {
            $decoded = json_decode($aio_focus, true);
            if (is_array($decoded) && isset($decoded['focus']['keyphrase'])) {
                $kws[] = (string) $decoded['focus']['keyphrase'];
            }
        }

        // Native (plugin proprio)
        $native = get_post_meta($post_id, '_seo_aeo_meta_keywords', true);
        if (!empty($native)) $kws[] = (string) $native;

        return $kws;
    }

    /**
     * Detect engine attivo (per labeling response).
     */
    private function detect_active_seo_engine() {
        if (class_exists('WPSEO_Options') || defined('WPSEO_VERSION')) return 'yoast';
        if (class_exists('RankMath') || defined('RANK_MATH_VERSION')) return 'rankmath';
        if (defined('AIOSEO_VERSION') || class_exists('AIOSEO\\Plugin\\Common\\Main')) return 'aioseo';
        return 'native';
    }

    public function ajax_scan_cannibalization() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) {
                wp_send_json(array('error' => 'Permessi insufficienti.'));
                return;
            }

            $post_types = isset($_POST['post_types']) ? (array) $_POST['post_types'] : array('post', 'page');
            $post_types = array_map('sanitize_key', $post_types);

            $engine = $this->detect_active_seo_engine();

            $query = new WP_Query(array(
                'post_type' => $post_types,
                'post_status' => 'publish',
                'posts_per_page' => 1000,
                'no_found_rows' => true,
                'fields' => 'ids',
                'suppress_filters' => true,
            ));

            $pages = array();
            foreach ($query->posts as $pid) {
                $kws = $this->read_focus_keywords($pid);
                $pages[] = array(
                    'post_id' => (int) $pid,
                    'url' => get_permalink($pid),
                    'title' => get_the_title($pid),
                    'keywords' => array_values(array_filter(array_map('strval', $kws), function($s) { return $s !== ''; })),
                    'engine' => $engine,
                );
            }

            $api = $this->require_api();
            if (!$api) return;

            $result = $api->api_request('/seo/scan-cannibalization', array(
                'pages' => $pages,
            ));

            wp_send_json($result);
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    /**
     * AI fix proposal per un singolo gruppo cannibalizzato (v3.11.3).
     * Per ogni post_id ricevuto, fetcha titolo + excerpt + word_count e li manda al backend.
     * Il backend chiama l'AI, sceglie la pagina primaria, suggerisce keyword + internal link.
     * Costa CANNIBAL_FIX_COST crediti wallet (deduzione lato backend).
     */
    public function ajax_cannibal_fix_proposal() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) {
                wp_send_json(array('error' => 'Permessi insufficienti.'));
                return;
            }

            $keyword = isset($_POST['keyword']) ? sanitize_text_field(wp_unslash($_POST['keyword'])) : '';
            $post_ids = isset($_POST['post_ids']) ? (array) $_POST['post_ids'] : array();
            $post_ids = array_filter(array_map('intval', $post_ids));

            if (empty($keyword) || count($post_ids) < 2) {
                wp_send_json(array('error' => 'Servono almeno 2 post_id e una keyword.'));
                return;
            }
            if (count($post_ids) > 10) {
                wp_send_json(array('error' => 'Massimo 10 pagine per gruppo.'));
                return;
            }

            $pages = array();
            foreach ($post_ids as $pid) {
                $post = get_post($pid);
                if (!$post || $post->post_status !== 'publish') continue;

                // Excerpt: prima eventuale post_excerpt, poi primi 500 char di stripped content
                $raw_excerpt = trim((string) $post->post_excerpt);
                if ($raw_excerpt === '') {
                    $stripped = wp_strip_all_tags(strip_shortcodes((string) $post->post_content));
                    $raw_excerpt = trim(preg_replace('/\s+/', ' ', $stripped));
                }
                $excerpt = mb_substr($raw_excerpt, 0, 500);

                // Word count: usa quello memorizzato se WP lo calcola, altrimenti stimato
                $stripped_full = wp_strip_all_tags(strip_shortcodes((string) $post->post_content));
                $word_count = str_word_count(preg_replace('/\s+/', ' ', $stripped_full));

                $current_kws = $this->read_focus_keywords($pid);
                $current_keyword = is_array($current_kws) && !empty($current_kws) ? (string) $current_kws[0] : '';

                $pages[] = array(
                    'post_id' => (int) $pid,
                    'url' => get_permalink($pid),
                    'title' => get_the_title($pid),
                    'excerpt' => $excerpt,
                    'word_count' => (int) $word_count,
                    'published_at' => $post->post_date_gmt ?: $post->post_date,
                    'current_keyword' => $current_keyword,
                );
            }

            if (count($pages) < 2) {
                wp_send_json(array('error' => 'Le pagine selezionate non sono valide o non sono pubblicate.'));
                return;
            }

            $api = $this->require_api();
            if (!$api) return;

            $result = $api->api_request('/seo/cannibalization-fix-proposal', array(
                'keyword' => $keyword,
                'pages' => $pages,
            ));

            wp_send_json($result);
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    /**
     * Applica una decisione cannibalization su UNA pagina (v3.11.6).
     * Richiesta dall'utente dopo aver visto la proposta AI.
     *
     * Input POST:
     *   - post_id (required)
     *   - apply_keyword (bool) — se true: aggiorna focus keyword
     *   - new_keyword (string) — la nuova keyword se apply_keyword=true
     *   - apply_link (bool) — se true: inserisce link interno
     *   - link_anchor (string) — testo del link
     *   - link_target_post_id (int) — post_id della pagina primaria
     *   - link_paragraph_hint (string, opt) — hint dove inserire il link
     *
     * Crea uno snapshot per ognuna delle modifiche (riutilizzando snapshot manager esistente)
     * così l'utente può fare undo dalla cronologia.
     */
    public function ajax_cannibal_apply() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) {
                wp_send_json(array('error' => 'Permessi insufficienti.'));
                return;
            }

            $post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;
            $apply_keyword = !empty($_POST['apply_keyword']);
            $apply_link = !empty($_POST['apply_link']);
            $new_keyword = isset($_POST['new_keyword']) ? sanitize_text_field(wp_unslash($_POST['new_keyword'])) : '';
            $link_anchor = isset($_POST['link_anchor']) ? sanitize_text_field(wp_unslash($_POST['link_anchor'])) : '';
            $link_target_pid = isset($_POST['link_target_post_id']) ? intval(wp_unslash($_POST['link_target_post_id'])) : 0;
            $link_hint = isset($_POST['link_paragraph_hint']) ? sanitize_text_field(wp_unslash($_POST['link_paragraph_hint'])) : '';

            if ($post_id <= 0) {
                wp_send_json(array('error' => 'post_id mancante o non valido.'));
                return;
            }
            if (!current_user_can('edit_post', $post_id)) {
                wp_send_json(array('error' => 'Non puoi modificare questa pagina.'));
                return;
            }
            if (!$apply_keyword && !$apply_link) {
                wp_send_json(array('error' => 'Nessuna azione selezionata.'));
                return;
            }

            $post = get_post($post_id);
            if (!$post || $post->post_status !== 'publish') {
                wp_send_json(array('error' => 'Pagina non trovata o non pubblicata.'));
                return;
            }

            $applied = array();
            $snapshot_ids = array();
            $proposal_id_pseudo = 'p_cannibal' . substr(md5($post_id . microtime(true)), 0, 8);

            // ──────── Apply keyword ────────
            if ($apply_keyword) {
                if (empty($new_keyword)) {
                    wp_send_json(array('error' => 'Nuova keyword mancante.'));
                    return;
                }

                $current_meta = SEO_AEO_Engine_Bridge::read_meta($post_id);
                $previous_keyword = isset($current_meta['meta_keywords']) && is_array($current_meta['meta_keywords']) && !empty($current_meta['meta_keywords'])
                    ? (string) $current_meta['meta_keywords'][0]
                    : '';

                $write_result = SEO_AEO_Engine_Bridge::write_meta($post_id, array(
                    'meta_keywords' => $new_keyword,
                ));
                if (empty($write_result['success'])) {
                    wp_send_json(array('error' => 'Bridge write_meta fallito: ' . ($write_result['message'] ?? 'unknown')));
                    return;
                }

                // Snapshot della modifica keyword
                if (class_exists('SEO_AEO_Snapshot_Manager')) {
                    $snap = new SEO_AEO_Snapshot_Manager();
                    $snap_id = $snap->create_snapshot(
                        $post_id,
                        $proposal_id_pseudo . '_kw',
                        array('meta_keywords' => $previous_keyword),
                        array('meta_keywords' => $new_keyword)
                    );
                    if ($snap_id) $snapshot_ids[] = $snap_id;
                }

                $applied[] = array(
                    'type' => 'keyword',
                    'previous' => $previous_keyword,
                    'new' => $new_keyword,
                );
            }

            // ──────── Apply internal link ────────
            if ($apply_link) {
                if (empty($link_anchor) || $link_target_pid <= 0) {
                    wp_send_json(array('error' => 'Anchor o target_post_id mancante per il link.'));
                    return;
                }
                $target_url = get_permalink($link_target_pid);
                if (!$target_url) {
                    wp_send_json(array('error' => 'URL target non risolvibile (post_id=' . $link_target_pid . ').'));
                    return;
                }

                $previous_content = (string) $post->post_content;

                // Evita duplicati: se nel content c'è già un link verso $target_url, skip
                if (strpos($previous_content, $target_url) !== false) {
                    $applied[] = array(
                        'type' => 'link_skipped',
                        'reason' => 'Esiste già un link verso questa pagina nel contenuto.',
                    );
                } else {
                    $new_content = $this->insert_internal_link($previous_content, $link_anchor, $target_url, $link_hint);

                    if ($new_content === $previous_content) {
                        wp_send_json(array('error' => 'Non è stato trovato un punto adatto per inserire il link. Inseriscilo manualmente.'));
                        return;
                    }

                    $upd = wp_update_post(array(
                        'ID' => $post_id,
                        'post_content' => $new_content,
                    ), true);
                    if (is_wp_error($upd)) {
                        wp_send_json(array('error' => 'wp_update_post fallito: ' . $upd->get_error_message()));
                        return;
                    }

                    if (class_exists('SEO_AEO_Snapshot_Manager')) {
                        $snap = new SEO_AEO_Snapshot_Manager();
                        $snap_id = $snap->create_snapshot(
                            $post_id,
                            $proposal_id_pseudo . '_link',
                            array('post_content' => $previous_content),
                            array('post_content' => $new_content)
                        );
                        if ($snap_id) $snapshot_ids[] = $snap_id;
                    }

                    $applied[] = array(
                        'type' => 'link',
                        'anchor' => $link_anchor,
                        'target_url' => $target_url,
                    );
                }
            }

            wp_send_json(array(
                'success' => true,
                'post_id' => $post_id,
                'applied' => $applied,
                'snapshot_ids' => $snapshot_ids,
                'message' => count($applied) . ' modifica/e applicata/e con snapshot di ripristino.',
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    /**
     * Inserisce un link <a href="$target_url">$anchor</a> in un punto sensato del $content.
     *
     * Strategia (in ordine di priorità):
     *  1. Se $hint contiene parole chiave riconoscibili nel content, trova il <p> che le contiene
     *     e appende " <a>$anchor</a>" prima del </p>.
     *  2. Altrimenti, appende il link in coda al PRIMO paragrafo non vuoto trovato.
     *  3. Se non c'è alcun <p>, restituisce $content invariato (caller gestirà l'errore).
     */
    private function insert_internal_link($content, $anchor, $target_url, $hint = '') {
        if (empty($content) || empty($anchor) || empty($target_url)) return $content;

        $link_html = ' <a href="' . esc_url($target_url) . '">' . esc_html($anchor) . '</a>';

        // Trova tutti i tag <p>...</p>
        if (!preg_match_all('/<p\b[^>]*>(.*?)<\/p>/is', $content, $matches, PREG_OFFSET_CAPTURE)) {
            return $content;
        }

        $target_idx = -1;

        // Strategia 1: cerca il <p> con substring del hint
        if (!empty($hint)) {
            $hint_lc = mb_strtolower($hint);
            $hint_words = array_filter(preg_split('/\s+/', $hint_lc), function($w) { return mb_strlen($w) > 4; });
            foreach ($matches[1] as $i => $m) {
                $p_lc = mb_strtolower(wp_strip_all_tags($m[0]));
                $hits = 0;
                foreach ($hint_words as $w) {
                    if (strpos($p_lc, $w) !== false) $hits++;
                }
                if ($hits >= 2) { $target_idx = $i; break; }  // almeno 2 parole > 4 caratteri matchano
            }
        }

        // Strategia 2: primo paragrafo non vuoto
        if ($target_idx === -1) {
            foreach ($matches[1] as $i => $m) {
                if (mb_strlen(trim(wp_strip_all_tags($m[0]))) > 30) {
                    $target_idx = $i;
                    break;
                }
            }
        }

        if ($target_idx === -1) return $content;

        $full_match = $matches[0][$target_idx][0];
        $offset = $matches[0][$target_idx][1];
        $new_p = preg_replace('/<\/p>$/i', $link_html . '</p>', $full_match);

        return substr($content, 0, $offset) . $new_p . substr($content, $offset + strlen($full_match));
    }

    // -- Schema validator (v3.8.0) --
    public function ajax_validate_schema() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) {
                wp_send_json(array('error' => 'Permessi insufficienti.'));
                return;
            }

            $post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;
            if (!$post_id) {
                wp_send_json(array('error' => 'post_id mancante.'));
                return;
            }

            $post = get_post($post_id);
            if (!$post || $post->post_status === 'trash') {
                wp_send_json(array('error' => 'Post non trovato.'));
                return;
            }

            $url = get_permalink($post_id);
            if (!$url) {
                wp_send_json(array('error' => 'URL non disponibile per questo post.'));
                return;
            }

            // 3.8.1 — cache-buster opzionale per re-validate dopo apply (forza WP/page-cache plugin a rigenerare)
            $nocache = !empty($_POST['nocache']);
            $fetch_url = $url;
            if ($nocache) {
                $sep = (strpos($url, '?') === false) ? '?' : '&';
                $fetch_url = $url . $sep . '_orch_nc=' . time();
            }

            // Fetch rendered HTML server-side. Yoast/RankMath/AIOSEO inject JSON-LD
            // at render time, so we need the full output, not just post_content.
            $resp = wp_remote_get($fetch_url, array(
                'timeout' => 12,
                'sslverify' => false,
                'redirection' => 3,
                'headers' => array('User-Agent' => 'SEO-AEO-Orchestra/' . SEO_AEO_VERSION),
            ));

            if (is_wp_error($resp)) {
                wp_send_json(array('error' => 'Impossibile fetchare la pagina: ' . $resp->get_error_message()));
                return;
            }

            $code = wp_remote_retrieve_response_code($resp);
            if ($code >= 400) {
                wp_send_json(array('error' => 'La pagina ha risposto con HTTP ' . $code));
                return;
            }

            $html = wp_remote_retrieve_body($resp);
            if (empty($html)) {
                wp_send_json(array('error' => 'HTML vuoto dalla pagina.'));
                return;
            }

            $api = $this->require_api();
            if (!$api) return;

            $result = $api->api_request('/seo/validate-schema', array(
                'post_id' => $post_id,
                'url' => $url,
                'html' => $html,
            ));

            wp_send_json($result);
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    /**
     * Native Output (3.12.0) — toggle ON/OFF dell'emissione di <head> SEO native.
     * Solo admin. Se rilevato altro plugin SEO (Yoast/RankMath/AIOSEO) il toggle viene
     * comunque salvato ma il rendering resta inattivo finché l'altro plugin è attivo.
     */
    public function ajax_toggle_native_output() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'Solo amministratori.'));
                return;
            }

            // Due toggle separati: enable (base) e override (avanzato)
            $field = isset($_POST['field']) ? sanitize_key(wp_unslash($_POST['field'])) : 'enable';
            $value = !empty($_POST['enable']) ? '1' : '0';

            if ($field === 'override') {
                update_option(SEO_AEO_Output_Renderer::OPTION_OVERRIDE, $value);
            } else {
                update_option(SEO_AEO_Output_Renderer::OPTION_ENABLED, $value);
            }

            wp_send_json(array(
                'success' => true,
                'enabled_setting' => get_option(SEO_AEO_Output_Renderer::OPTION_ENABLED, '0') === '1',
                'override_setting' => SEO_AEO_Output_Renderer::is_override_mode(),
                'is_active' => SEO_AEO_Output_Renderer::is_active(),
                'other_seo_plugin' => SEO_AEO_Output_Renderer::detect_other_seo_plugin(),
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    /**
     * Native Sitemap (3.13.0): toggle ON/OFF della sitemap.xml nativa.
     */
    public function ajax_toggle_native_sitemap() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'Solo amministratori.'));
                return;
            }
            $enable = !empty($_POST['enable']);
            SEO_AEO_Sitemap::set_enabled($enable);
            wp_send_json(array(
                'success' => true,
                'enabled' => SEO_AEO_Sitemap::is_enabled(),
                'sitemap_url' => home_url('/seo-aeo-sitemap.xml'),
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    /**
     * llms.txt (3.13.1): toggle ON/OFF
     */
    public function ajax_toggle_native_llms_txt() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'Solo amministratori.'));
                return;
            }
            $enable = !empty($_POST['enable']);
            SEO_AEO_LLMs_Txt::set_enabled($enable);
            wp_send_json(array(
                'success' => true,
                'enabled' => SEO_AEO_LLMs_Txt::is_enabled(),
                'index_url' => home_url('/llms.txt'),
                'full_url' => home_url('/llms-full.txt'),
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    public function ajax_native_llms_txt_status() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) {
                wp_send_json(array('error' => 'Permessi insufficienti.'));
                return;
            }
            wp_send_json(array(
                'enabled' => SEO_AEO_LLMs_Txt::is_enabled(),
                'index_url' => home_url('/llms.txt'),
                'full_url' => home_url('/llms-full.txt'),
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    /**
     * Schema.org native toggle (3.14.0): JSON-LD nel <head> per Organization/Article/etc.
     */
    public function ajax_toggle_native_schema() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'Solo amministratori.'));
                return;
            }
            $enable = !empty($_POST['enable']);
            SEO_AEO_Schema::set_enabled($enable);
            wp_send_json(array(
                'success' => true,
                'enabled' => SEO_AEO_Schema::is_enabled(),
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    public function ajax_native_schema_status() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) {
                wp_send_json(array('error' => 'Permessi insufficienti.'));
                return;
            }
            wp_send_json(array(
                'enabled' => SEO_AEO_Schema::is_enabled(),
                'home_url' => home_url('/'),
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    /**
     * Redirect Manager (3.15.0)
     */
    public function ajax_toggle_native_redirect() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'Solo amministratori.'));
                return;
            }
            $enable = !empty($_POST['enable']);
            SEO_AEO_Redirect_Manager::set_enabled($enable);
            wp_send_json(array('success' => true, 'enabled' => SEO_AEO_Redirect_Manager::is_enabled()));
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    public function ajax_redirect_status() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) {
                wp_send_json(array('error' => 'Permessi insufficienti.'));
                return;
            }
            wp_send_json(array(
                'enabled' => SEO_AEO_Redirect_Manager::is_enabled(),
                'count' => SEO_AEO_Redirect_Manager::count_redirects(),
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    public function ajax_redirect_list() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'Solo amministratori.'));
                return;
            }
            $search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
            $page = isset($_POST['page']) ? max(1, intval(wp_unslash($_POST['page']))) : 1;
            $per_page = 50;
            $offset = ($page - 1) * $per_page;
            $items = SEO_AEO_Redirect_Manager::list_redirects($per_page, $offset, $search);
            wp_send_json(array(
                'success' => true,
                'items' => $items,
                'total' => SEO_AEO_Redirect_Manager::count_redirects(),
                'page' => $page,
                'per_page' => $per_page,
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    public function ajax_redirect_add() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'Solo amministratori.'));
                return;
            }
            $source = isset($_POST['source_path']) ? sanitize_text_field(wp_unslash($_POST['source_path'])) : '';
            $target = isset($_POST['target_url']) ? esc_url_raw(wp_unslash($_POST['target_url'])) : '';
            $type   = isset($_POST['redirect_type']) ? intval(wp_unslash($_POST['redirect_type'])) : 301;
            $regex  = !empty($_POST['is_regex']);
            $active = !isset($_POST['is_active']) || !empty($_POST['is_active']);
            $notes  = isset($_POST['notes']) ? sanitize_text_field(wp_unslash($_POST['notes'])) : '';

            // Per il sanitize: se non è regex, accettiamo target relativo (sanitize_text_field invece di esc_url_raw)
            if (empty($target) && !empty($_POST['target_url'])) {
                $target = sanitize_text_field(wp_unslash($_POST['target_url']));
            }

            $result = SEO_AEO_Redirect_Manager::add_redirect($source, $target, $type, $regex, $active, $notes);
            wp_send_json($result);
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    public function ajax_redirect_update() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'Solo amministratori.'));
                return;
            }
            $id = isset($_POST['id']) ? intval(wp_unslash($_POST['id'])) : 0;
            if ($id <= 0) {
                wp_send_json(array('error' => 'id mancante.'));
                return;
            }
            $fields = array();
            if (isset($_POST['source_path'])) $fields['source_path'] = sanitize_text_field(wp_unslash($_POST['source_path']));
            if (isset($_POST['target_url'])) $fields['target_url'] = sanitize_text_field(wp_unslash($_POST['target_url']));
            if (isset($_POST['redirect_type'])) $fields['redirect_type'] = intval(wp_unslash($_POST['redirect_type']));
            if (isset($_POST['is_regex'])) $fields['is_regex'] = !empty($_POST['is_regex']) ? 1 : 0;
            if (isset($_POST['is_active'])) $fields['is_active'] = !empty($_POST['is_active']) ? 1 : 0;
            if (isset($_POST['notes'])) $fields['notes'] = sanitize_text_field(wp_unslash($_POST['notes']));

            $result = SEO_AEO_Redirect_Manager::update_redirect($id, $fields);
            wp_send_json($result);
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    public function ajax_redirect_delete() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'Solo amministratori.'));
                return;
            }
            $id = isset($_POST['id']) ? intval(wp_unslash($_POST['id'])) : 0;
            if ($id <= 0) {
                wp_send_json(array('error' => 'id mancante.'));
                return;
            }
            $result = SEO_AEO_Redirect_Manager::delete_redirect($id);
            wp_send_json($result);
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    public function ajax_redirect_404_log() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'Solo amministratori.'));
                return;
            }
            wp_send_json(array(
                'success' => true,
                'items' => SEO_AEO_Redirect_Manager::list_404s(50, 0),
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    // ─── Migration Wizard (3.19.0) ─────────────────────────────────────────
    public function ajax_migration_scan() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'Solo amministratori.'));
                return;
            }
            wp_send_json(array(
                'success' => true,
                'scan' => SEO_AEO_Migration_Wizard::scan(),
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    public function ajax_migration_backup() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'Solo amministratori.'));
                return;
            }
            $backup = SEO_AEO_Migration_Wizard::build_backup();
            wp_send_json(array(
                'success' => true,
                'backup' => $backup,
                'filename' => 'seo-aeo-orchestra-backup-' . gmdate('Y-m-d-His') . '.json',
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    public function ajax_migration_migrate_meta() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'Solo amministratori.'));
                return;
            }
            $result = SEO_AEO_Migration_Wizard::migrate_meta();
            wp_send_json(array('success' => true) + $result);
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    public function ajax_migration_import_redirects() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'Solo amministratori.'));
                return;
            }
            $result = SEO_AEO_Migration_Wizard::import_redirects();
            wp_send_json(array('success' => true) + $result);
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    public function ajax_migration_activate_stack() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'Solo amministratori.'));
                return;
            }
            $opts = array(
                'output'   => !empty($_POST['output']),
                'override' => !empty($_POST['override']),
                'sitemap'  => !empty($_POST['sitemap']),
                'llms'     => !empty($_POST['llms']),
                'schema'   => !empty($_POST['schema']),
                'redirect' => !empty($_POST['redirect']),
            );
            $result = SEO_AEO_Migration_Wizard::activate_stack($opts);
            wp_send_json(array('success' => true) + $result);
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    /**
     * AI 404 Suggester (3.16.0): per un 404 path, l'AI sceglie il target redirect più semantico
     * tra le pagine pubblicate del sito. Costo crediti deciso dal backend (3 di default).
     */
    public function ajax_redirect_suggest_target() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json(array('error' => 'Solo amministratori.'));
                return;
            }
            $request_path = isset($_POST['request_path']) ? sanitize_text_field(wp_unslash($_POST['request_path'])) : '';
            if (empty($request_path)) {
                wp_send_json(array('error' => 'request_path mancante.'));
                return;
            }

            // Raccogli candidate: post + page + custom post types pubblici (max 80 per non saturare prompt)
            $candidates = array();
            $post_types = get_post_types(array('public' => true), 'names');
            unset($post_types['attachment']);

            $posts = get_posts(array(
                'post_type'        => array_values($post_types),
                'post_status'      => 'publish',
                'posts_per_page'   => 80,
                'orderby'          => 'modified',
                'order'            => 'DESC',
                'no_found_rows'    => true,
                'suppress_filters' => true,
            ));
            foreach ($posts as $p) {
                $excerpt = trim((string) $p->post_excerpt);
                if ($excerpt === '') {
                    $stripped = wp_strip_all_tags(strip_shortcodes((string) $p->post_content));
                    $excerpt = trim(preg_replace('/\s+/', ' ', $stripped));
                }
                $candidates[] = array(
                    'url'     => get_permalink($p),
                    'title'   => get_the_title($p),
                    'excerpt' => mb_substr($excerpt, 0, 200),
                );
            }

            if (count($candidates) < 1) {
                wp_send_json(array('error' => 'Nessuna pagina pubblicata da cui scegliere il target.'));
                return;
            }

            $api = $this->require_api();
            if (!$api) return;

            $result = $api->api_request('/seo/redirect-suggest-target', array(
                'request_path' => $request_path,
                'candidates'   => $candidates,
            ));
            wp_send_json($result);
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    public function ajax_native_sitemap_status() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) {
                wp_send_json(array('error' => 'Permessi insufficienti.'));
                return;
            }

            $enabled = SEO_AEO_Sitemap::is_enabled();
            $sitemap_url = home_url('/seo-aeo-sitemap.xml');

            // Stima counts per anteprima
            $stats = array();
            if ($enabled) {
                $post_types = get_post_types(array('public' => true), 'names');
                unset($post_types['attachment']);
                foreach ($post_types as $pt) {
                    $count = wp_count_posts($pt);
                    $published = isset($count->publish) ? (int) $count->publish : 0;
                    if ($published > 0) {
                        $stats[$pt] = $published;
                    }
                }
            }

            wp_send_json(array(
                'enabled' => $enabled,
                'sitemap_url' => $sitemap_url,
                'stats' => $stats,
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    /**
     * Stato corrente del Native Output: setting + plugin SEO attivo + rendering on/off.
     */
    public function ajax_native_output_status() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) {
                wp_send_json(array('error' => 'Permessi insufficienti.'));
                return;
            }
            $other = SEO_AEO_Output_Renderer::detect_other_seo_plugin();
            $labels = array(
                'yoast' => 'Yoast SEO',
                'rankmath' => 'Rank Math',
                'aioseo' => 'All in One SEO',
            );
            wp_send_json(array(
                'enabled_setting'  => get_option(SEO_AEO_Output_Renderer::OPTION_ENABLED, '0') === '1',
                'override_setting' => SEO_AEO_Output_Renderer::is_override_mode(),
                'is_active'        => SEO_AEO_Output_Renderer::is_active(),
                'other_seo_plugin' => $other,
                'other_seo_plugin_label' => ($other && isset($labels[$other])) ? $labels[$other] : null,
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }


    // Brand Voice Learning (3.21.0)
    public function ajax_brand_voice_analyze() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $profile_name = isset($_POST['profile_name']) ? sanitize_text_field(wp_unslash($_POST['profile_name'])) : 'Profilo ' . gmdate('Y-m-d');
            $post_type = isset($_POST['post_type']) ? sanitize_key(wp_unslash($_POST['post_type'])) : 'post';

            $articles = SEO_AEO_Brand_Voice::fetch_recent_articles(15, $post_type);
            if (count($articles) < 3) {
                wp_send_json(array('error' => 'Servono almeno 3 articoli pubblicati. Trovati: ' . count($articles)));
                return;
            }
            $api = $this->require_api();
            if (!$api) return;
            $result = $api->api_request('/seo/brand-voice-analyze', array('articles' => $articles, 'profile_name' => $profile_name));

            if (empty($result['success']) || empty($result['profile'])) { wp_send_json($result); return; }

            $save = SEO_AEO_Brand_Voice::save_profile($profile_name, $result['profile'], count($articles));
            if (empty($save['success'])) { wp_send_json(array('error' => 'DB save fallito')); return; }

            wp_send_json(array(
                'success' => true,
                'profile_id' => $save['id'],
                'profile' => $result['profile'],
                'articles_analyzed' => count($articles),
                'credits_consumed' => isset($result['credits_consumed']) ? $result['credits_consumed'] : 0,
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore interno: ' . $e->getMessage()));
        }
    }

    public function ajax_brand_voice_activate() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            wp_send_json(SEO_AEO_Brand_Voice::activate_profile(intval($_POST['id'] ?? 0)));
        } catch (Throwable $e) { wp_send_json(array('error' => $e->getMessage())); }
    }

    public function ajax_brand_voice_deactivate() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            wp_send_json(SEO_AEO_Brand_Voice::deactivate_all());
        } catch (Throwable $e) { wp_send_json(array('error' => $e->getMessage())); }
    }

    public function ajax_brand_voice_delete() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            wp_send_json(SEO_AEO_Brand_Voice::delete_profile(intval($_POST['id'] ?? 0)));
        } catch (Throwable $e) { wp_send_json(array('error' => $e->getMessage())); }
    }

    public function ajax_brand_voice_get() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $p = SEO_AEO_Brand_Voice::get_profile(intval($_POST['id'] ?? 0));
            if (!$p) { wp_send_json(array('error' => 'Profilo non trovato')); return; }
            wp_send_json(array('success' => true, 'profile' => $p));
        } catch (Throwable $e) { wp_send_json(array('error' => $e->getMessage())); }
    }

    public function ajax_brand_voice_update() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) { wp_send_json(array('error' => 'id mancante')); return; }

            $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : null;
            $updates = array();
            $str_fields = array('tone', 'tone_description', 'avg_sentence_length', 'avg_paragraph_length',
                                'typical_structure', 'audience', 'cta_style', 'summary_for_prompt');
            foreach ($str_fields as $f) {
                if (isset($_POST[$f])) $updates[$f] = sanitize_text_field(wp_unslash($_POST[$f]));
            }
            // Vocab + avoid arrivano come array oppure CSV
            foreach (array('distinctive_vocabulary', 'avoid_words') as $f) {
                if (isset($_POST[$f])) {
                    $val = isset($_POST[$f]) ? wp_unslash($_POST[$f]) : '';
                    if (is_array($val)) {
                        $updates[$f] = array_map('sanitize_text_field', $val);
                    } else {
                        $val = sanitize_text_field((string) $val);
                        $updates[$f] = array_filter(array_map('trim', explode(',', $val)));
                    }
                }
            }

            $result = SEO_AEO_Brand_Voice::update_profile_data($id, $name, $updates);
            wp_send_json($result);
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * AJAX: crea bozza temporanea per anteprima col WP theme reale.
     *
     * Bug originale (auto-draft): WP rifiuta serve preview di post in stato 'auto-draft'
     * (sono placeholder, ritornano 404 quando ci si naviga). Fix: usiamo 'draft' standard
     * + marker meta `_seo_aeo_preview_only=1`. Pulizia: il garbage collector
     * (`maybe_cleanup_preview_drafts`) cancella i draft con quel marker piu' vecchi di 24h.
     */
    public function ajax_create_preview_draft() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) { wp_send_json(array('error' => 'Permessi insufficienti.')); return; }

            $title   = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : 'Anteprima';
            $content = isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : '';
            $image_url = isset($_POST['image_url']) ? esc_url_raw(wp_unslash($_POST['image_url'])) : '';
            $attachment_id = isset($_POST['attachment_id']) ? intval(wp_unslash($_POST['attachment_id'])) : 0;

            $post_id = wp_insert_post(array(
                'post_title'   => $title,
                'post_content' => $content,
                'post_status'  => 'draft',
                'post_type'    => 'post',
                'post_author'  => get_current_user_id(),
                'meta_input'   => array(
                    '_seo_aeo_preview_only' => '1',
                    '_seo_aeo_preview_created_at' => time(),
                ),
            ), true);

            if (is_wp_error($post_id) || !$post_id) {
                $err = is_wp_error($post_id) ? $post_id->get_error_message() : 'wp_insert_post failed';
                wp_send_json(array('error' => $err));
                return;
            }

            // Attach featured image se passata: preferisci attachment_id (gia' caricato in
            // libreria via complete-article), fallback a download da URL esterno.
            if ($attachment_id > 0 && get_post($attachment_id)) {
                set_post_thumbnail($post_id, $attachment_id);
            } elseif (!empty($image_url) && filter_var($image_url, FILTER_VALIDATE_URL)) {
                try {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    require_once ABSPATH . 'wp-admin/includes/media.php';
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                    $tmp = download_url($image_url, 30);
                    if (!is_wp_error($tmp)) {
                        $file_array = array(
                            'name'     => 'preview-' . $post_id . '-' . wp_generate_password(8, false, false) . '.jpg',
                            'tmp_name' => $tmp,
                        );
                        $att_id = media_handle_sideload($file_array, $post_id, $title);
                        if (!is_wp_error($att_id) && $att_id) {
                            set_post_thumbnail($post_id, $att_id);
                        } elseif (file_exists($tmp)) {
                            @wp_delete_file($tmp);
                        }
                    }
                } catch (Throwable $imgEx) {
                }
            }

            // Cleanup opportunistico (max 1/ora) di vecchie preview > 24h.
            $last = (int) get_option('seo_aeo_preview_cleanup_last', 0);
            if (time() - $last > 3600) {
                self::cleanup_preview_drafts();
                update_option('seo_aeo_preview_cleanup_last', time(), false);
            }

            $preview_url = get_preview_post_link($post_id);
            wp_send_json(array(
                'success'     => true,
                'post_id'     => $post_id,
                'preview_url' => $preview_url,
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * Cancella le bozze marcate come "preview only" piu' vecchie di 24h.
     * Limite di sicurezza: max 50 cancellazioni per chiamata.
     */
    public static function cleanup_preview_drafts() {
        $cutoff = time() - 86400;
        $q = new WP_Query(array(
            'post_type'   => 'post',
            'post_status' => 'draft',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            'meta_query'  => array(
                'relation' => 'AND',
                array('key' => '_seo_aeo_preview_only', 'value' => '1'),
                array('key' => '_seo_aeo_preview_created_at', 'value' => $cutoff, 'compare' => '<', 'type' => 'NUMERIC'),
            ),
            'fields'         => 'ids',
            'posts_per_page' => 50,
            'no_found_rows'  => true,
        ));
        if ($q && !empty($q->posts)) {
            foreach ($q->posts as $pid) {
                wp_delete_post((int) $pid, true);
            }
        }
    }

    /**
     * AJAX: lista set keyword salvati (3.23.2).
     * Pesca dalla cronologia gli ultimi N item con section='keyword_research' e li
     * normalizza per il picker (label, niche, count, date, keywords array).
     */
    public function ajax_keyword_sets_list() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }

            $history = new SEO_AEO_Orchestra_History();
            $all = $history->get_history_array();

            $sets = array();
            foreach ($all as $item) {
                if (!isset($item['section']) || $item['section'] !== 'keyword_research') continue;
                if (empty($item['data'])) continue;
                $parsed = json_decode($item['data'], true);
                if (!is_array($parsed) || !isset($parsed['keywords']) || !is_array($parsed['keywords'])) continue;
                $kws = array();
                foreach ($parsed['keywords'] as $kw) {
                    if (!is_array($kw) || empty($kw['keyword'])) continue;
                    $kws[] = array(
                        'keyword'    => (string) $kw['keyword'],
                        'cluster'    => isset($kw['cluster']) ? (string) $kw['cluster'] : '',
                        'volume'     => isset($kw['estimated_volume']) ? (string) $kw['estimated_volume'] : '',
                        'difficulty' => isset($kw['difficulty']) ? (string) $kw['difficulty'] : '',
                        'intent'     => isset($kw['intent']) ? (string) $kw['intent'] : '',
                        'topic'      => isset($kw['suggested_topic']) ? (string) $kw['suggested_topic'] : '',
                    );
                }
                if (empty($kws)) continue;

                $sets[] = array(
                    'id'       => isset($item['id']) ? (string) $item['id'] : '',
                    'niche'    => isset($item['title']) ? (string) $item['title'] : 'Set',
                    'date'     => isset($item['date']) ? (string) $item['date'] : '',
                    'count'    => count($kws),
                    'clusters' => isset($parsed['clusters']) && is_array($parsed['clusters']) ? array_values(array_filter(array_map('strval', $parsed['clusters']))) : array(),
                    'keywords' => $kws,
                );
                if (count($sets) >= 20) break;
            }

            wp_send_json(array('success' => true, 'sets' => $sets));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * AJAX: Auto-Pilot — create job (3.24.0).
     */
    public function ajax_autopilot_create() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $name        = isset($_POST['name'])        ? sanitize_text_field(wp_unslash($_POST['name']))      : '';
            $kw_raw      = isset($_POST['keywords'])    ? wp_unslash($_POST['keywords'])                       : '';
            $freq        = isset($_POST['frequency_days']) ? intval(wp_unslash($_POST['frequency_days']))                  : 7;
            $post_status = isset($_POST['post_status']) ? sanitize_text_field(wp_unslash($_POST['post_status'])) : 'draft';
            $include_image = !empty($_POST['include_image']) ? 1 : 0;
            $length      = isset($_POST['length'])      ? sanitize_text_field(wp_unslash($_POST['length']))    : 'medium';
            $cat_id      = isset($_POST['post_category_id']) ? intval(wp_unslash($_POST['post_category_id']))              : 0;
            $author_id   = isset($_POST['post_author_id'])   ? intval(wp_unslash($_POST['post_author_id']))                : get_current_user_id();

            $keywords = array();
            $decoded = json_decode($kw_raw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $kw) {
                    if (is_array($kw) && !empty($kw['keyword'])) {
                        $keywords[] = array(
                            'keyword' => sanitize_text_field((string) $kw['keyword']),
                            'topic'   => isset($kw['topic']) ? sanitize_text_field((string) $kw['topic']) : sanitize_text_field((string) $kw['keyword']),
                        );
                    }
                }
            }
            if (empty($keywords)) { wp_send_json(array('error' => 'Nessuna keyword selezionata.')); return; }

            // 3.31.5: per-job layout override
            $layout_override = null;
            if (!empty($_POST['layout_override_enabled'])) {
                $layout_override = array(
                    'enabled'   => 1,
                    'post_type' => isset($_POST['layout_post_type']) ? sanitize_text_field(wp_unslash($_POST['layout_post_type'])) : '',
                    'template'  => isset($_POST['layout_template'])  ? sanitize_text_field(wp_unslash($_POST['layout_template']))  : 'default',
                    'category'  => isset($_POST['layout_category'])  ? intval(wp_unslash($_POST['layout_category'])) : 0,
                    'author'    => isset($_POST['layout_author'])    ? intval(wp_unslash($_POST['layout_author'])) : 0,
                );
            }

            $result = SEO_AEO_Autopilot::create_job(array(
                'name'             => $name,
                'keywords'         => $keywords,
                'frequency_days'   => $freq,
                'post_status'      => $post_status,
                'include_image'    => $include_image,
                'length'           => $length,
                'post_category_id' => $cat_id,
                'post_author_id'   => $author_id,
                'layout_override'  => $layout_override,
            ));
            wp_send_json($result);
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    public function ajax_autopilot_list() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $jobs = SEO_AEO_Autopilot::list_jobs();
            wp_send_json(array('success' => true, 'jobs' => $jobs));
        } catch (Throwable $e) { wp_send_json(array('error' => $e->getMessage())); }
    }

    public function ajax_autopilot_toggle() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $id = intval($_POST['id'] ?? 0);
            $status = sanitize_text_field(wp_unslash($_POST['status'] ?? 'paused'));
            $r = SEO_AEO_Autopilot::update_status($id, $status);
            wp_send_json($r);
        } catch (Throwable $e) { wp_send_json(array('error' => $e->getMessage())); }
    }

    public function ajax_autopilot_delete() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $id = intval($_POST['id'] ?? 0);
            $r = SEO_AEO_Autopilot::delete_job($id);
            wp_send_json($r);
        } catch (Throwable $e) { wp_send_json(array('error' => $e->getMessage())); }
    }

    public function ajax_autopilot_run_now() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $id = intval($_POST['id'] ?? 0);
            // Ignora rate limit: il run_now è esplicito dell'utente, può consumare crediti subito
            // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
            @set_time_limit(120);
            $r = SEO_AEO_Autopilot::run_now($id);
            wp_send_json($r);
        } catch (Throwable $e) { wp_send_json(array('error' => $e->getMessage())); }
    }

    public function ajax_autopilot_log() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $job_id = intval($_POST['job_id'] ?? 0);
            $log = SEO_AEO_Autopilot::get_log($job_id, 30);
            wp_send_json(array('success' => true, 'log' => $log));
        } catch (Throwable $e) { wp_send_json(array('error' => $e->getMessage())); }
    }

    /**
     * AJAX: Keyword Research Autopilot (3.23.0).
     * Inoltra al backend /api/seo/keyword-research. Costo: 15 crediti wallet.
     */
    public function ajax_keyword_research() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }

            $niche = isset($_POST['niche']) ? sanitize_text_field(wp_unslash($_POST['niche'])) : '';
            $language = isset($_POST['language']) ? sanitize_text_field(wp_unslash($_POST['language'])) : 'it';
            $max_keywords = isset($_POST['max_keywords']) ? intval(wp_unslash($_POST['max_keywords'])) : 30;
            if ($max_keywords < 10) $max_keywords = 10;
            if ($max_keywords > 50) $max_keywords = 50;

            if (strlen($niche) < 3) {
                wp_send_json(array('error' => 'Inserisci una nicchia di almeno 3 caratteri.'));
                return;
            }

            $api = $this->require_api();
            $payload = array(
                'niche'        => $niche,
                'language'     => $language,
                'max_keywords' => $max_keywords,
            );
            $result = $api->api_request('/seo/keyword-research', $payload);

            if (is_array($result) && !empty($result['error'])) {
                wp_send_json(array('error' => isset($result['message']) ? $result['message'] : 'Errore backend'));
                return;
            }
            wp_send_json($result);
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    // ─── Topup Option B (3.30.0-beta) ───────────────────────────────────────
    // Proxy AJAX handlers for the in-WP "Ricarica" modal. They forward to
    // backend /billing/topup/options and /billing/topup/checkout (license_key
    // injected automatically by SEO_AEO_API_Client::api_request).

    public function ajax_topup_options() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json(array('error' => 'forbidden'));
            return;
        }
        $api = $this->require_api();
        if (!$api) return;
        try {
            $resp = $api->api_request('/billing/topup/options', array());
            if (is_array($resp) && !empty($resp['error'])) {
                wp_send_json(array(
                    'error' => isset($resp['message']) ? $resp['message'] : 'Errore backend',
                ));
                return;
            }
            wp_send_json($resp);
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore di comunicazione: ' . $e->getMessage()));
        }
    }

    public function ajax_topup_checkout() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json(array('error' => 'forbidden'));
            return;
        }
        $package_id = isset($_POST['package_id']) ? sanitize_text_field(wp_unslash($_POST['package_id'])) : '';
        if (empty($package_id)) {
            wp_send_json(array('error' => 'package_id required'));
            return;
        }
        $api = $this->require_api();
        if (!$api) return;
        try {
            $resp = $api->api_request('/billing/topup/checkout', array('package_id' => $package_id));
            if (is_array($resp) && !empty($resp['error'])) {
                wp_send_json(array(
                    'error' => isset($resp['message']) ? $resp['message'] : 'Errore backend',
                ));
                return;
            }
            wp_send_json($resp);
        } catch (Throwable $e) {
            wp_send_json(array('error' => 'Errore di comunicazione: ' . $e->getMessage()));
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3.31.5 — Layout articoli AI (Settings + Preview + Apply to generation)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * AJAX: ritorna metadata necessaria per popolare i 5 dropdown del layout
     * (post_types, templates per type, categorie, autori, page builders detected).
     */
    public function ajax_layout_discover() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }

            // Public post types (escludi attachment, nav_menu_item, ecc)
            $post_types = array();
            foreach (get_post_types(array('public' => true, 'show_ui' => true), 'objects') as $pt) {
                if (in_array($pt->name, array('attachment', 'nav_menu_item', 'wp_block', 'wp_template'), true)) continue;
                $post_types[] = array(
                    'name'  => $pt->name,
                    'label' => isset($pt->labels->singular_name) ? $pt->labels->singular_name : $pt->name,
                    'icon'  => is_string($pt->menu_icon) ? $pt->menu_icon : '',
                );
            }

            // Templates per post type
            $templates_by_type = array();
            $theme = function_exists('wp_get_theme') ? wp_get_theme() : null;
            foreach ($post_types as $pt) {
                $list = array(array('file' => 'default', 'name' => __('Default (theme default)', 'seo-aeo-orchestra')));
                if ($theme) {
                    if (method_exists($theme, 'get_post_templates')) {
                        $tpls = $theme->get_post_templates();
                        if (isset($tpls[$pt['name']]) && is_array($tpls[$pt['name']])) {
                            foreach ($tpls[$pt['name']] as $file => $name) {
                                $list[] = array('file' => $file, 'name' => $name);
                            }
                        }
                    }
                    if ($pt['name'] === 'page' && method_exists($theme, 'get_page_templates')) {
                        $page_tpls = $theme->get_page_templates();
                        if (is_array($page_tpls)) {
                            foreach ($page_tpls as $file => $name) {
                                // Avoid duplicates
                                $already = false;
                                foreach ($list as $row) { if ($row['file'] === $file) { $already = true; break; } }
                                if (!$already) $list[] = array('file' => $file, 'name' => $name);
                            }
                        }
                    }
                }
                $templates_by_type[$pt['name']] = $list;
            }

            // Categorie (solo per post)
            $categories = array(array('id' => 0, 'name' => __('— Nessuna —', 'seo-aeo-orchestra')));
            foreach (get_categories(array('hide_empty' => false)) as $cat) {
                $categories[] = array('id' => (int) $cat->term_id, 'name' => $cat->name);
            }

            // Autori
            $authors = array();
            foreach (get_users(array('capability' => 'edit_posts', 'fields' => array('ID', 'display_name'))) as $u) {
                $authors[] = array('id' => (int) $u->ID, 'name' => $u->display_name);
            }

            // Page builders
            $page_builders = array();
            if (defined('ELEMENTOR_VERSION')) $page_builders[] = 'Elementor';
            if (function_exists('et_setup_theme') || defined('ET_BUILDER_VERSION')) $page_builders[] = 'Divi';
            if (defined('FL_BUILDER_VERSION')) $page_builders[] = 'Beaver Builder';
            if (function_exists('register_block_type')) $page_builders[] = 'Gutenberg';

            // Saved settings
            $saved = array(
                'post_type' => get_option('seo_aeo_orchestra_layout_post_type', 'post'),
                'template'  => get_option('seo_aeo_orchestra_layout_template', 'default'),
                'category'  => (int) get_option('seo_aeo_orchestra_layout_category', 0),
                'author'    => (int) get_option('seo_aeo_orchestra_layout_author', get_current_user_id()),
                'status'    => get_option('seo_aeo_orchestra_layout_status', 'draft'),
                'authorized'=> get_option('seo_aeo_orchestra_layout_authorized', '0') === '1',
            );

            wp_send_json(array(
                'success'         => true,
                'post_types'      => $post_types,
                'templates'       => $templates_by_type,
                'categories'      => $categories,
                'authors'         => $authors,
                'page_builders'   => $page_builders,
                'current_user_id' => get_current_user_id(),
                'saved'           => $saved,
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * AJAX: salva la configurazione layout in WP options.
     */
    public function ajax_layout_save() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }

            $post_type = isset($_POST['post_type']) ? sanitize_text_field(wp_unslash($_POST['post_type'])) : 'post';
            $template  = isset($_POST['template']) ? sanitize_text_field(wp_unslash($_POST['template'])) : 'default';
            $category  = isset($_POST['category']) ? intval(wp_unslash($_POST['category'])) : 0;
            $author    = isset($_POST['author']) ? intval(wp_unslash($_POST['author'])) : get_current_user_id();
            $status    = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : 'draft';
            if (!in_array($status, array('draft', 'publish'), true)) $status = 'draft';

            // Whitelist: post_type must exist
            $existing_pts = get_post_types(array('public' => true), 'names');
            if (!in_array($post_type, $existing_pts, true)) $post_type = 'post';

            update_option('seo_aeo_orchestra_layout_post_type', $post_type, false);
            update_option('seo_aeo_orchestra_layout_template', $template, false);
            update_option('seo_aeo_orchestra_layout_category', $category, false);
            update_option('seo_aeo_orchestra_layout_author', $author, false);
            update_option('seo_aeo_orchestra_layout_status', $status, false);
            update_option('seo_aeo_orchestra_layout_authorized', '1', false);

            wp_send_json(array('success' => true));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * AJAX: crea una bozza con un articolo finto applicando la config layout
     * (post_type + template + categoria + autore) — ritorna preview URL.
     * No AI call, no crediti consumati.
     */
    public function ajax_layout_preview() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }

            $post_type = isset($_POST['post_type']) ? sanitize_text_field(wp_unslash($_POST['post_type'])) : 'post';
            $template  = isset($_POST['template']) ? sanitize_text_field(wp_unslash($_POST['template'])) : 'default';
            $category  = isset($_POST['category']) ? intval(wp_unslash($_POST['category'])) : 0;
            $author    = isset($_POST['author']) ? intval(wp_unslash($_POST['author'])) : get_current_user_id();

            // Whitelist post_type
            $existing_pts = get_post_types(array('public' => true), 'names');
            if (!in_array($post_type, $existing_pts, true)) $post_type = 'post';

            $title = 'Articolo di esempio AEO Orchestra';
            $content = '<h2>Esempio: Cos\'è e come funziona</h2>'
                . '<p>Questo è un articolo di esempio generato per visualizzare l\'anteprima del layout configurato. Il contenuto reale sarà generato dall\'AI con la tua Brand Voice attiva e le tue keyword target.</p>'
                . '<h3>Sottosezione di esempio</h3>'
                . '<p>Contenuto della sottosezione con dettagli tecnici e esempi pratici. Questa anteprima ti permette di verificare come gli articoli generati si integreranno con il tuo theme.</p>'
                . '<h3>Domande frequenti (FAQ esempio)</h3>'
                . '<p><strong>Domanda 1?</strong> Risposta concisa per la FAQ.</p>'
                . '<p><strong>Domanda 2?</strong> Una seconda risposta più articolata per testare il rendering del theme.</p>';

            $post_data = array(
                'post_title'   => $title,
                'post_content' => $content,
                'post_status'  => 'draft',
                'post_type'    => $post_type,
                'post_author'  => $author > 0 ? $author : get_current_user_id(),
                'meta_input'   => array(
                    '_seo_aeo_preview_only'        => '1',
                    '_seo_aeo_preview_created_at'  => time(),
                    '_seo_aeo_layout_preview'      => '1',
                ),
            );
            if ($category > 0 && $post_type === 'post') {
                $post_data['post_category'] = array($category);
            }

            $post_id = wp_insert_post($post_data, true);
            if (is_wp_error($post_id) || !$post_id) {
                $err = is_wp_error($post_id) ? $post_id->get_error_message() : 'wp_insert_post failed';
                wp_send_json(array('error' => $err));
                return;
            }

            // Apply page template
            if ($template && $template !== 'default') {
                update_post_meta($post_id, '_wp_page_template', $template);
            }

            $preview_url = get_preview_post_link($post_id);
            wp_send_json(array(
                'success'     => true,
                'post_id'     => $post_id,
                'preview_url' => $preview_url,
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * AJAX: ritorna gli articoli Auto-Pilot ancora in stato 'draft' (review queue).
     * Filtri: job_id (0=tutti), days (default 7).
     */
    public function ajax_autopilot_queue() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) { wp_send_json(array('error' => 'forbidden')); return; }

            global $wpdb;
            $tbl_log = $wpdb->prefix . 'seo_aeo_autopilot_log';
            $job_id  = isset($_POST['job_id']) ? intval(wp_unslash($_POST['job_id'])) : 0;
            $days    = isset($_POST['days']) ? intval(wp_unslash($_POST['days'])) : 7;
            if ($days < 1) $days = 7;
            if ($days > 365) $days = 365;

            // Pick log rows (success only, post_id > 0) ordinati desc
            $where = "status = 'success' AND post_id > 0 AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)";
            $params = array($days);
            if ($job_id > 0) {
                $where .= " AND job_id = %d";
                $params[] = $job_id;
            }
            $sql = "SELECT id, job_id, keyword, post_id, created_at FROM $tbl_log WHERE $where ORDER BY id DESC LIMIT 100";
            $logs = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
            if (!is_array($logs)) $logs = array();

            // Map post_ids → posts (only drafts) + job names
            $items = array();
            $job_names = array();
            $tbl_jobs = $wpdb->prefix . 'seo_aeo_autopilot_jobs';
            foreach ($logs as $log) {
                $pid = (int) $log['post_id'];
                $p = get_post($pid);
                if (!$p) continue;
                if ($p->post_status !== 'draft') continue; // pubblicato/cestinato → skip
                $jid = (int) $log['job_id'];
                if (!isset($job_names[$jid])) {
                    $row = $wpdb->get_row($wpdb->prepare("SELECT name FROM $tbl_jobs WHERE id = %d", $jid), ARRAY_A);
                    $job_names[$jid] = $row && !empty($row['name']) ? $row['name'] : ('Job #' . $jid);
                }
                $thumb_id = get_post_thumbnail_id($pid);
                $thumb_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'thumbnail') : '';
                $word_count = str_word_count(wp_strip_all_tags((string) $p->post_content));
                $items[] = array(
                    'log_id'    => (int) $log['id'],
                    'post_id'   => $pid,
                    'title'     => $p->post_title,
                    'keyword'   => $log['keyword'],
                    'job_id'    => $jid,
                    'job_name'  => $job_names[$jid],
                    'word_count'=> $word_count,
                    'thumb_url' => $thumb_url,
                    'edit_url'  => get_edit_post_link($pid, ''),
                    'preview_url' => get_preview_post_link($pid),
                    'created_at'=> $log['created_at'],
                );
            }

            wp_send_json(array('success' => true, 'items' => $items));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * AJAX: pubblica un draft Auto-Pilot (status: draft → publish).
     * Accetta `post_id` o array `post_ids` (bulk).
     */
    public function ajax_autopilot_publish() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('publish_posts')) { wp_send_json(array('error' => 'forbidden')); return; }

            $ids = array();
            if (isset($_POST['post_ids']) && is_array($_POST['post_ids'])) {
                foreach ($_POST['post_ids'] as $i) { $i = intval($i); if ($i > 0) $ids[] = $i; }
            } elseif (isset($_POST['post_id'])) {
                $i = intval(wp_unslash($_POST['post_id'])); if ($i > 0) $ids[] = $i;
            }
            if (empty($ids)) { wp_send_json(array('error' => 'No post IDs')); return; }

            $published = 0;
            foreach ($ids as $pid) {
                $p = get_post($pid);
                if (!$p || $p->post_status !== 'draft') continue;
                if (!current_user_can('edit_post', $pid)) continue;
                $r = wp_update_post(array('ID' => $pid, 'post_status' => 'publish'), true);
                if (!is_wp_error($r) && $r) $published++;
            }
            wp_send_json(array('success' => true, 'published' => $published));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * AJAX: scarta (cestina) un draft Auto-Pilot. Bulk supportato.
     */
    public function ajax_autopilot_discard() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('delete_posts')) { wp_send_json(array('error' => 'forbidden')); return; }

            $ids = array();
            if (isset($_POST['post_ids']) && is_array($_POST['post_ids'])) {
                foreach ($_POST['post_ids'] as $i) { $i = intval($i); if ($i > 0) $ids[] = $i; }
            } elseif (isset($_POST['post_id'])) {
                $i = intval(wp_unslash($_POST['post_id'])); if ($i > 0) $ids[] = $i;
            }
            if (empty($ids)) { wp_send_json(array('error' => 'No post IDs')); return; }

            $discarded = 0;
            foreach ($ids as $pid) {
                if (!current_user_can('delete_post', $pid)) continue;
                if (wp_trash_post($pid)) $discarded++;
            }
            wp_send_json(array('success' => true, 'discarded' => $discarded));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * AJAX: rigenera un articolo Auto-Pilot (chiama complete-article con stessa keyword,
     * sostituisce content + featured image dello stesso post).
     */
    public function ajax_autopilot_regenerate() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('edit_posts')) { wp_send_json(array('error' => 'forbidden')); return; }

            $post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;
            $keyword = isset($_POST['keyword']) ? sanitize_text_field(wp_unslash($_POST['keyword'])) : '';
            if ($post_id < 1 || empty($keyword)) {
                wp_send_json(array('error' => 'post_id e keyword obbligatori')); return;
            }
            if (!current_user_can('edit_post', $post_id)) { wp_send_json(array('error' => 'forbidden')); return; }
            $post = get_post($post_id);
            if (!$post) { wp_send_json(array('error' => 'Post non trovato')); return; }

            $api = $this->require_api();
            if (!$api) return;

            $resp = $api->api_request('/ai/complete-article', array(
                'topic'         => $post->post_title,
                'keyword'       => $keyword,
                'length'        => 'medium',
                'include_image' => true,
                'include_meta'  => true,
                'language'      => $this->get_ai_language(),
            ));

            if (!is_array($resp) || !empty($resp['error']) || empty($resp['content'])) {
                $err = is_array($resp) && !empty($resp['message']) ? $resp['message'] : 'Backend error';
                wp_send_json(array('error' => $err));
                return;
            }

            // Update post content
            wp_update_post(array(
                'ID'           => $post_id,
                'post_content' => $resp['content'],
            ));

            // New featured image (override existing)
            if (!empty($resp['image_base64'])) {
                try {
                    $upload = $this->save_base64_to_media($resp['image_base64'], $post->post_title);
                    if (!empty($upload['id'])) set_post_thumbnail($post_id, (int) $upload['id']);
                } catch (Throwable $imgEx) {
                }
            }

            // 3.32.0: refresh marker AI-generated dopo rigenerazione
            $existing_job_id = (int) get_post_meta($post_id, '_seo_aeo_autopilot_job_id', true);
            $this->mark_ai_generated($post_id, 'autopilot_regenerate', $existing_job_id);

            wp_send_json(array('success' => true, 'post_id' => $post_id));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    // ==========================================================
    // 3.33.0 — Content Calendar AJAX handlers
    // ==========================================================

    /**
     * GET helper verso il backend (api_request è solo POST).
     */
    private function backend_get($path) {
        $api_url = defined('SEO_AEO_API_URL') ? SEO_AEO_API_URL : 'https://aeo-orchestra.com';
        $url = rtrim($api_url, '/') . '/api' . $path;
        $resp = wp_remote_get($url, array('timeout' => 30, 'headers' => array('Accept' => 'application/json')));
        if (is_wp_error($resp)) return array('error' => true, 'message' => $resp->get_error_message());
        $body = json_decode(wp_remote_retrieve_body($resp), true);
        return is_array($body) ? $body : array('error' => true, 'message' => 'Invalid JSON');
    }

    /**
     * PATCH/DELETE helper verso il backend.
     */
    private function backend_request($path, $method, $body_arr = array()) {
        $api_url = defined('SEO_AEO_API_URL') ? SEO_AEO_API_URL : 'https://aeo-orchestra.com';
        $url = rtrim($api_url, '/') . '/api' . $path;
        $args = array(
            'method'  => strtoupper($method),
            'timeout' => 30,
            'headers' => array('Content-Type' => 'application/json', 'Accept' => 'application/json'),
        );
        if (!empty($body_arr)) {
            $args['body'] = wp_json_encode($body_arr);
        }
        $resp = wp_remote_request($url, $args);
        if (is_wp_error($resp)) return array('error' => true, 'message' => $resp->get_error_message());
        $b = json_decode(wp_remote_retrieve_body($resp), true);
        return is_array($b) ? $b : array('error' => true, 'message' => 'Invalid JSON');
    }

    public function ajax_calendar_list() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $license_key = get_option('seo_aeo_orchestra_license_key', '');
            if (empty($license_key)) { wp_send_json(array('error' => 'Licenza non configurata.')); return; }
            $from = isset($_POST['from']) ? sanitize_text_field(wp_unslash($_POST['from'])) : gmdate('Y-m-d', time() - 7 * 86400);
            $to   = isset($_POST['to'])   ? sanitize_text_field(wp_unslash($_POST['to']))   : gmdate('Y-m-d', time() + 90 * 86400);
            $path = '/calendar/slots?license_key=' . rawurlencode($license_key) . '&from=' . rawurlencode($from) . '&to=' . rawurlencode($to);
            $r = $this->backend_get($path);
            wp_send_json($r);
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    public function ajax_calendar_add() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $license_key = get_option('seo_aeo_orchestra_license_key', '');
            if (empty($license_key)) { wp_send_json(array('error' => 'Licenza non configurata.')); return; }

            $topic   = isset($_POST['topic'])   ? sanitize_text_field(wp_unslash($_POST['topic']))   : '';
            $keyword = isset($_POST['keyword']) ? sanitize_text_field(wp_unslash($_POST['keyword'])) : '';
            $sched   = isset($_POST['scheduled_at_utc']) ? sanitize_text_field(wp_unslash($_POST['scheduled_at_utc'])) : '';
            $hour    = isset($_POST['hour']) ? intval(wp_unslash($_POST['hour'])) : 9;
            $gdb     = isset($_POST['generate_days_before']) ? intval(wp_unslash($_POST['generate_days_before'])) : 1;
            $auto    = !empty($_POST['auto_publish']);
            $cat     = isset($_POST['category_id']) ? intval(wp_unslash($_POST['category_id'])) : 0;
            $bv_ovr  = isset($_POST['brand_voice_override']) ? sanitize_textarea_field(wp_unslash($_POST['brand_voice_override'])) : '';

            if (empty($topic) || empty($sched)) {
                wp_send_json(array('error' => 'Topic e data sono obbligatori.'));
                return;
            }

            $payload = array(
                'license_key'         => $license_key,
                'topic'               => $topic,
                'keyword'             => $keyword,
                'scheduled_at_utc'    => $sched,
                'hour'                => $hour,
                'generate_days_before'=> $gdb,
                'auto_publish'        => $auto,
                'category_id'         => $cat,
            );
            if (!empty($bv_ovr)) $payload['brand_voice_override'] = $bv_ovr;

            $api_url = defined('SEO_AEO_API_URL') ? SEO_AEO_API_URL : 'https://aeo-orchestra.com';
            $resp = wp_remote_post(rtrim($api_url, '/') . '/api/calendar/slot', array(
                'timeout' => 30,
                'headers' => array('Content-Type' => 'application/json'),
                'body'    => wp_json_encode($payload),
            ));
            if (is_wp_error($resp)) { wp_send_json(array('error' => $resp->get_error_message())); return; }
            $body = json_decode(wp_remote_retrieve_body($resp), true);
            if (!is_array($body)) { wp_send_json(array('error' => 'Risposta non valida')); return; }
            wp_send_json($body);
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    public function ajax_calendar_update() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $slot_id = isset($_POST['slot_id']) ? sanitize_text_field(wp_unslash($_POST['slot_id'])) : '';
            if (empty($slot_id)) { wp_send_json(array('error' => 'slot_id mancante')); return; }

            $fields = array();
            foreach (array('topic','keyword','scheduled_at_utc','brand_voice_override','notes') as $f) {
                if (isset($_POST[$f])) $fields[$f] = sanitize_text_field(wp_unslash($_POST[$f]));
            }
            foreach (array('hour','generate_days_before','category_id') as $f) {
                if (isset($_POST[$f])) $fields[$f] = intval(wp_unslash($_POST[$f]));
            }
            if (isset($_POST['auto_publish'])) {
                $fields['auto_publish'] = !empty($_POST['auto_publish']);
            }
            $r = $this->backend_request('/calendar/slot/' . rawurlencode($slot_id), 'PATCH', $fields);
            wp_send_json($r);
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    public function ajax_calendar_delete() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $license_key = get_option('seo_aeo_orchestra_license_key', '');
            $slot_id = isset($_POST['slot_id']) ? sanitize_text_field(wp_unslash($_POST['slot_id'])) : '';
            $delete_post = !empty($_POST['delete_post']);
            $local_post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;
            if (empty($slot_id)) { wp_send_json(array('error' => 'slot_id mancante')); return; }

            $r = $this->backend_request('/calendar/slot/' . rawurlencode($slot_id) . '?license_key=' . rawurlencode($license_key), 'DELETE');

            // Optionally delete the WP draft too
            if ($delete_post && $local_post_id > 0) {
                $post = get_post($local_post_id);
                if ($post && $post->post_status === 'draft') {
                    wp_delete_post($local_post_id, false); // to trash
                    if (is_array($r)) $r['post_deleted'] = true;
                }
            }
            wp_send_json($r);
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    public function ajax_calendar_generate_now() {
        // 3.35.0 — Refactor preview-first: genera contenuto, salva in transient,
        // NON crea il post WP. UI mostra preview + bottoni Accetta/Rigenera/Rimborsa.
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $slot_id = isset($_POST['slot_id']) ? sanitize_text_field(wp_unslash($_POST['slot_id'])) : '';
            if (empty($slot_id)) { wp_send_json(array('error' => 'slot_id mancante')); return; }
            // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
            @set_time_limit(300);
            // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
            @ini_set('max_execution_time', '300');
            // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
            @ini_set('memory_limit', '256M');
            $t0 = microtime(true);
            $r = SEO_AEO_Calendar::generate_preview_for_slot($slot_id);
            $dt = round(microtime(true) - $t0, 2);
            wp_send_json($r);
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * 3.35.0 — Commit della preview: legge transient → wp_insert_post → meta → notify backend.
     */
    public function ajax_calendar_preview_commit() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $slot_id = isset($_POST['slot_id']) ? sanitize_text_field(wp_unslash($_POST['slot_id'])) : '';
            if (empty($slot_id)) { wp_send_json(array('error' => 'slot_id mancante')); return; }
            // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
            @set_time_limit(120);
            $r = SEO_AEO_Calendar::commit_preview_for_slot($slot_id);
            wp_send_json($r);
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * 3.35.0 — Refund: chiama backend /ai/refund-generation, poi scarta transient.
     */
    public function ajax_calendar_preview_refund() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $slot_id = isset($_POST['slot_id']) ? sanitize_text_field(wp_unslash($_POST['slot_id'])) : '';
            $generation_id = isset($_POST['generation_id']) ? sanitize_text_field(wp_unslash($_POST['generation_id'])) : '';
            $reason = isset($_POST['reason']) ? sanitize_text_field(wp_unslash($_POST['reason'])) : '';
            if (empty($slot_id) || empty($generation_id)) {
                wp_send_json(array('error' => 'slot_id o generation_id mancante'));
                return;
            }
            $r = SEO_AEO_Calendar::refund_preview_for_slot($slot_id, $generation_id, $reason);
            wp_send_json($r);
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * 3.35.0 — Riprendi preview: leggi transient e ritorna i dati.
     */
    public function ajax_calendar_preview_load() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $slot_id = isset($_POST['slot_id']) ? sanitize_text_field(wp_unslash($_POST['slot_id'])) : '';
            if (empty($slot_id)) { wp_send_json(array('error' => 'slot_id mancante')); return; }
            $r = SEO_AEO_Calendar::load_preview_for_slot($slot_id);
            wp_send_json($r);
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    public function ajax_calendar_publish_now() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $slot_id = isset($_POST['slot_id']) ? sanitize_text_field(wp_unslash($_POST['slot_id'])) : '';
            $post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;
            if (empty($slot_id) || $post_id < 1) { wp_send_json(array('error' => 'slot_id o post_id mancante')); return; }
            $r = SEO_AEO_Calendar::publish_now_by_slot_id($slot_id, $post_id);
            wp_send_json($r);
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    public function ajax_calendar_categories() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $cats = get_categories(array('hide_empty' => false, 'number' => 200));
            $out = array();
            foreach ($cats as $c) {
                $out[] = array('id' => (int) $c->term_id, 'name' => $c->name);
            }
            wp_send_json(array('success' => true, 'categories' => $out));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * Bulk topic suggestion via backend AI (5 crediti).
     */
    public function ajax_calendar_suggest() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $license_key = get_option('seo_aeo_orchestra_license_key', '');
            if (empty($license_key)) { wp_send_json(array('error' => 'Licenza non configurata.')); return; }
            $n = isset($_POST['n_articles']) ? intval(wp_unslash($_POST['n_articles'])) : 7;
            $niche = isset($_POST['niche']) ? sanitize_text_field(wp_unslash($_POST['niche'])) : '';

            // Raccoglie ultimi 10 titoli pubblicati come "existing" per evitare duplicati
            $existing = array();
            $recent = get_posts(array('post_type' => 'post', 'posts_per_page' => 10, 'post_status' => 'publish'));
            foreach ($recent as $p) $existing[] = $p->post_title;

            $api = $this->require_api();
            if (!$api) return;
            $payload = array(
                'license_key'    => $license_key,
                'n_articles'     => $n,
                'niche'          => $niche,
                'existing_topics'=> $existing,
                'language'       => $this->get_ai_language(),
            );
            $r = $api->api_request('/calendar/suggest-topics', $payload);
            wp_send_json($r);
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * Bulk-create slots da array di topic (post-AI suggest, dopo conferma user).
     */
    public function ajax_calendar_bulk_create() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'Solo amministratori.')); return; }
            $license_key = get_option('seo_aeo_orchestra_license_key', '');
            if (empty($license_key)) { wp_send_json(array('error' => 'Licenza non configurata.')); return; }

            $items_raw = isset($_POST['items']) ? wp_unslash($_POST['items']) : '';
            $items = json_decode($items_raw, true);
            if (!is_array($items) || empty($items)) {
                wp_send_json(array('error' => 'Nessun item da creare.'));
                return;
            }
            $start_date = isset($_POST['start_date']) ? sanitize_text_field(wp_unslash($_POST['start_date'])) : gmdate('Y-m-d', time() + 86400);
            $spread     = isset($_POST['spread']) ? sanitize_text_field(wp_unslash($_POST['spread'])) : 'daily'; // daily|weekly
            $hour       = isset($_POST['hour']) ? intval(wp_unslash($_POST['hour'])) : intval(get_option('seo_aeo_orchestra_calendar_default_hour', 9));
            $gdb        = isset($_POST['generate_days_before']) ? intval(wp_unslash($_POST['generate_days_before'])) : intval(get_option('seo_aeo_orchestra_calendar_default_days_before', 1));
            $auto       = !empty($_POST['auto_publish']);
            $cat        = isset($_POST['category_id']) ? intval(wp_unslash($_POST['category_id'])) : intval(get_option('seo_aeo_orchestra_calendar_default_category', 0));

            $created = 0;
            $errors = array();
            $api_url = defined('SEO_AEO_API_URL') ? SEO_AEO_API_URL : 'https://aeo-orchestra.com';
            $base_dt = DateTime::createFromFormat('Y-m-d', $start_date);
            if (!$base_dt) $base_dt = new DateTime();
            // 3.35.1: supporta 4 distribuzioni: daily, 2_per_week, 3_per_week, weekly
            foreach ($items as $i => $it) {
                if (!is_array($it) || empty($it['topic'])) continue;
                $dt = clone $base_dt;
                if ($spread === 'weekly') {
                    $dt->modify('+' . ($i * 7) . ' days');
                } else if ($spread === '2_per_week') {
                    // 2/sett: lun-gio (offsets 0, 3, +7)
                    $week_idx = intdiv($i, 2);
                    $in_week = $i % 2;
                    $offset = $week_idx * 7 + ($in_week === 0 ? 0 : 3);
                    $dt->modify('+' . $offset . ' days');
                } else if ($spread === '3_per_week') {
                    // 3/sett: lun-mer-ven (offsets 0, 2, 4, +7)
                    $week_idx = intdiv($i, 3);
                    $in_week = $i % 3;
                    $day_offset = ($in_week === 0) ? 0 : (($in_week === 1) ? 2 : 4);
                    $offset = $week_idx * 7 + $day_offset;
                    $dt->modify('+' . $offset . ' days');
                } else {
                    // daily
                    $dt->modify('+' . $i . ' days');
                }
                $sched = $dt->format('Y-m-d') . sprintf('T%02d:00:00Z', max(0, min(23, $hour)));
                $body = array(
                    'license_key'        => $license_key,
                    'topic'              => sanitize_text_field((string) $it['topic']),
                    'keyword'            => isset($it['keyword']) ? sanitize_text_field((string) $it['keyword']) : '',
                    'scheduled_at_utc'   => $sched,
                    'hour'               => $hour,
                    'generate_days_before'=> $gdb,
                    'auto_publish'       => $auto,
                    'category_id'        => $cat,
                );
                $resp = wp_remote_post(rtrim($api_url, '/') . '/api/calendar/slot', array(
                    'timeout' => 25,
                    'headers' => array('Content-Type' => 'application/json'),
                    'body'    => wp_json_encode($body),
                ));
                if (is_wp_error($resp)) {
                    $errors[] = $resp->get_error_message();
                    continue;
                }
                $b = json_decode(wp_remote_retrieve_body($resp), true);
                if (is_array($b) && !empty($b['success'])) {
                    $created++;
                } else {
                    $errors[] = is_array($b) ? (isset($b['detail']) ? $b['detail'] : 'creation failed') : 'invalid response';
                }
            }
            wp_send_json(array('success' => true, 'created' => $created, 'errors' => $errors));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }


    /**
     * 3.33.3 — Ritorna preview URL nonce'd per il post di uno slot (usato dall'iframe Anteprima rapida).
     */
    public function ajax_calendar_preview_url() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            $post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;
            if ($post_id < 1) { wp_send_json(array('error' => 'post_id mancante')); return; }
            $post = get_post($post_id);
            if (!$post) { wp_send_json(array('error' => 'post non trovato')); return; }
            $preview_url = get_preview_post_link($post_id);
            wp_send_json(array('success' => true, 'preview_url' => $preview_url, 'post_status' => $post->post_status));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * 3.33.3 — Ri-inietta immagine featured come blocco inline nel post_content (per articoli pre-3.33.2).
     * Idempotente: skippa se l'immagine featured e' gia' presente nel content.
     */
    public function ajax_calendar_inject_image() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            $post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;
            if ($post_id < 1) { wp_send_json(array('error' => 'post_id mancante')); return; }
            $post = get_post($post_id);
            if (!$post) { wp_send_json(array('error' => 'post non trovato')); return; }
            $thumb_id = get_post_thumbnail_id($post_id);
            if (!$thumb_id) { wp_send_json(array('error' => 'Nessuna immagine featured impostata')); return; }
            $img_url = wp_get_attachment_url($thumb_id);
            if (!$img_url) { wp_send_json(array('error' => 'URL immagine non trovato')); return; }
            $content = (string) $post->post_content;
            // Idempotency: skip se l'attachment id e' gia' nel content
            if (strpos($content, 'wp-image-' . $thumb_id) !== false || strpos($content, 'wp:image {"id":' . $thumb_id) !== false) {
                wp_send_json(array('success' => true, 'already_present' => true));
                return;
            }
            // 3.33.4: refresh metadata immagine se mancanti
            if (!get_post_meta($thumb_id, '_wp_attachment_image_alt', true)) {
                update_post_meta($thumb_id, '_wp_attachment_image_alt', sanitize_text_field($post->post_title));
            }
            // 3.35.8: helper unificato per build_inline_image_block
            $inline = SEO_AEO_AI_Helpers::build_inline_image_block($thumb_id, $img_url, $post->post_title);
            wp_update_post(array('ID' => $post_id, 'post_content' => $inline . $content));
            wp_send_json(array('success' => true, 'attach_id' => $thumb_id, 'already_present' => false));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }


    /**
     * 3.33.5 — Quick preview inline: ritorna title + content HTML + meta del post.
     * Sostituisce l'iframe (che 404'va per cookie/permission cross-frame).
     */
    public function ajax_calendar_quick_preview() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            $post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;
            if ($post_id < 1) { wp_send_json(array('error' => 'post_id mancante')); return; }
            $post = get_post($post_id);
            if (!$post) { wp_send_json(array('error' => 'Post non trovato (forse eliminato)')); return; }

            // Render content via the_content filter chain (gestisce shortcode, blocks Gutenberg)
            $content_raw = $post->post_content;
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            $content_html = apply_filters('the_content', $content_raw);
            // Sanitize per modal: rimuovi script + iframe per safety
            $content_html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $content_html);
            $content_html = preg_replace('#<iframe\b[^>]*>.*?</iframe>#is', '', $content_html);

            $word_count = str_word_count(wp_strip_all_tags($content_raw));
            $thumb_id = get_post_thumbnail_id($post_id);
            $has_image = !empty($thumb_id) || (strpos($content_raw, '<img') !== false);
            $status_map = array(
                'draft'      => '📝 Bozza',
                'publish'    => '✅ Pubblicato',
                'future'     => '⏰ Programmato',
                'pending'    => '⏳ In attesa',
                'private'    => '🔒 Privato',
                'auto-draft' => '🆕 Auto-draft',
                'trash'      => '🗑 Cestino',
            );
            $status_label = isset($status_map[$post->post_status]) ? $status_map[$post->post_status] : $post->post_status;

            $scheduled_at = '';
            if ($post->post_status === 'future' || $post->post_status === 'publish') {
                $scheduled_at = mysql2gmdate('j M Y H:i', $post->post_date);
            }

            wp_send_json(array(
                'success'      => true,
                'title'        => $post->post_title,
                'content_html' => $content_html,
                'word_count'   => $word_count,
                'has_image'    => $has_image,
                'status'       => $post->post_status,
                'status_label' => $status_label,
                'scheduled_at' => $scheduled_at,
                'permalink'    => get_permalink($post_id),
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    //  3.34.0 — Image SEO Manager AJAX handlers
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Stima recovery traffic heuristic: max 18% di Google Images se tutte le
     * immagini fossero ottimizzate. Range conservativo "+10-25%" per non over-claim.
     */
    private function image_seo_traffic_estimate($total, $to_fix) {
        if ($total <= 0) return array('low_pct' => 0, 'high_pct' => 0);
        $coverage = min(1.0, $to_fix / $total); // % immagini da fixare
        $low  = round($coverage * 10, 1);
        $high = round($coverage * 25, 1);
        return array('low_pct' => $low, 'high_pct' => $high);
    }

    /**
     * Audit: lista immagini paginata con score + filtri.
     */
    public function ajax_image_seo_audit() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }

            $page     = max(1, intval($_POST['page'] ?? 1));
            $per_page = min(50, max(5, intval($_POST['per_page'] ?? 20)));
            $filter   = isset($_POST['filter']) ? sanitize_text_field(wp_unslash($_POST['filter'])) : 'all';
            $search   = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
            $sort     = isset($_POST['sort']) ? sanitize_text_field(wp_unslash($_POST['sort'])) : 'score_asc';

            // Stats globali (count completo, no pagination)
            global $wpdb;
            $total = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='attachment' AND post_mime_type LIKE 'image/%'"
            );

            // Score conteggio "OK" (score 4/4)
            // Trick: serve JOIN con postmeta per leggere alt → uso subquery indipendente.
            $alt_meta = $wpdb->prefix . 'postmeta';
            $sql_ok = "
                SELECT COUNT(*) FROM {$wpdb->posts} p
                LEFT JOIN {$alt_meta} pm ON pm.post_id=p.ID AND pm.meta_key='_wp_attachment_image_alt'
                WHERE p.post_type='attachment' AND p.post_mime_type LIKE 'image/%'
                  AND pm.meta_value <> '' AND pm.meta_value IS NOT NULL
                  AND p.post_title <> ''
                  AND p.post_excerpt <> ''
                  AND p.post_content <> ''
            ";
            $count_ok = (int) $wpdb->get_var($sql_ok);
            $count_to_fix = max(0, $total - $count_ok);
            $score_pct = $total > 0 ? intval(round($count_ok / $total * 100)) : 0;
            $traffic_est = $this->image_seo_traffic_estimate($total, $count_to_fix);

            // Build WHERE clause filter
            $where = "p.post_type='attachment' AND p.post_mime_type LIKE 'image/%'";
            $args = array();
            if ($search !== '') {
                $like = '%' . $wpdb->esc_like($search) . '%';
                $where .= " AND (p.post_title LIKE %s OR p.guid LIKE %s)";
                $args[] = $like;
                $args[] = $like;
            }

            // Per filter "no_alt" / "no_title" / "no_caption" / "no_desc" / "perfect" → JOIN con alt
            $join = "LEFT JOIN {$alt_meta} pm_alt ON pm_alt.post_id=p.ID AND pm_alt.meta_key='_wp_attachment_image_alt'";
            switch ($filter) {
                case 'no_alt':
                    $where .= " AND (pm_alt.meta_value IS NULL OR pm_alt.meta_value='')";
                    break;
                case 'no_title':
                    $where .= " AND (p.post_title IS NULL OR p.post_title='')";
                    break;
                case 'no_caption':
                    $where .= " AND (p.post_excerpt IS NULL OR p.post_excerpt='')";
                    break;
                case 'no_desc':
                    $where .= " AND (p.post_content IS NULL OR p.post_content='')";
                    break;
                case 'to_fix':
                    $where .= " AND ((pm_alt.meta_value IS NULL OR pm_alt.meta_value='') OR p.post_title='' OR p.post_excerpt='' OR p.post_content='')";
                    break;
                case 'perfect':
                    $where .= " AND pm_alt.meta_value <> '' AND p.post_title <> '' AND p.post_excerpt <> '' AND p.post_content <> ''";
                    break;
                case 'all':
                default:
                    break;
            }

            // Order
            // Score = (alt!='') + (title!='') + (caption!='') + (desc!='')
            $score_expr = "(CASE WHEN pm_alt.meta_value<>'' THEN 1 ELSE 0 END)"
                . " + (CASE WHEN p.post_title<>'' THEN 1 ELSE 0 END)"
                . " + (CASE WHEN p.post_excerpt<>'' THEN 1 ELSE 0 END)"
                . " + (CASE WHEN p.post_content<>'' THEN 1 ELSE 0 END)";
            switch ($sort) {
                case 'score_desc': $order_by = "$score_expr DESC, p.ID DESC"; break;
                case 'newest':     $order_by = "p.post_date DESC"; break;
                case 'oldest':     $order_by = "p.post_date ASC"; break;
                case 'score_asc':
                default:           $order_by = "$score_expr ASC, p.ID DESC"; break;
            }

            // Filtered count
            $sql_count = "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p $join WHERE $where";
            $filtered_count = empty($args)
                ? (int) $wpdb->get_var($sql_count)
                : (int) $wpdb->get_var($wpdb->prepare($sql_count, $args));

            // Paginated query
            $offset = ($page - 1) * $per_page;
            $sql = "SELECT p.ID, p.post_title, p.post_excerpt, p.post_content, p.post_parent, p.post_date, pm_alt.meta_value AS alt_text
                    FROM {$wpdb->posts} p $join WHERE $where
                    ORDER BY $order_by LIMIT %d OFFSET %d";
            $bind = array_merge($args, array($per_page, $offset));
            $rows = $wpdb->get_results($wpdb->prepare($sql, $bind));

            $items = array();
            foreach ((array) $rows as $r) {
                $aid   = intval($r->ID);
                $alt   = (string) $r->alt_text;
                $title = (string) $r->post_title;
                $cap   = (string) $r->post_excerpt;
                $desc  = (string) $r->post_content;
                $score = SEO_AEO_Image_SEO::compute_score($alt, $title, $cap, $desc);
                $url   = wp_get_attachment_url($aid);
                $thumb = wp_get_attachment_image_src($aid, 'thumbnail');
                $thumb_url = is_array($thumb) ? $thumb[0] : $url;
                $meta = wp_get_attachment_metadata($aid);
                $w = is_array($meta) && isset($meta['width']) ? intval($meta['width']) : 0;
                $h = is_array($meta) && isset($meta['height']) ? intval($meta['height']) : 0;
                $filename = $url ? basename(wp_parse_url($url, PHP_URL_PATH)) : 'unknown';
                $parent_id = intval($r->post_parent);
                $parent_title = '';
                $parent_url = '';
                $parent_type = '';
                if ($parent_id > 0) {
                    $parent_post = get_post($parent_id);
                    if ($parent_post) {
                        $parent_title = $parent_post->post_title;
                        $parent_url = get_permalink($parent_id);
                        $parent_type = $parent_post->post_type;
                    }
                }
                $items[] = array(
                    'id'             => $aid,
                    'filename'       => $filename,
                    'url'            => $url,
                    'thumb_url'      => $thumb_url,
                    'width'          => $w,
                    'height'         => $h,
                    'parent_post_id' => $parent_id,
                    'parent_title'   => $parent_title,
                    'parent_url'     => $parent_url,
                    'parent_type'    => $parent_type,
                    'alt'            => $alt,
                    'title'          => $title,
                    'caption'        => $cap,
                    'description'    => $desc,
                    'score'          => $score,
                );
            }

            wp_send_json(array(
                'success'  => true,
                'total'    => $total,
                'filtered' => $filtered_count,
                'page'     => $page,
                'per_page' => $per_page,
                'pages'    => max(1, intval(ceil($filtered_count / $per_page))),
                'stats'    => array(
                    'total'        => $total,
                    'ok'           => $count_ok,
                    'to_fix'       => $count_to_fix,
                    'score_pct'    => $score_pct,
                    'traffic_low'  => $traffic_est['low_pct'],
                    'traffic_high' => $traffic_est['high_pct'],
                ),
                'items'    => $items,
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * Genera metadata per UNA singola immagine via backend.
     */
    public function ajax_image_seo_generate_one() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            $aid = intval($_POST['attach_id'] ?? 0);
            if ($aid <= 0) { wp_send_json(array('error' => 'attach_id mancante')); return; }
            $att = get_post($aid);
            if (!$att || $att->post_type !== 'attachment') { wp_send_json(array('error' => 'Attachment non trovato')); return; }
            $url = wp_get_attachment_url($aid);
            if (empty($url)) { wp_send_json(array('error' => 'URL immagine non disponibile')); return; }

            $api = $this->require_api();
            if (!$api) return;

            $parent = ($att->post_parent > 0) ? get_post($att->post_parent) : null;
            $resp = $api->api_request('/ai/generate-image-metadata', array(
                'image_url'           => $url,
                'parent_post_title'   => $parent ? (string) $parent->post_title : '',
                'parent_post_excerpt' => $parent ? (string) $parent->post_excerpt : '',
                'site_language'       => $this->get_ai_language(),
            ));
            wp_send_json($resp);
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * Salva metadata manualmente.
     */
    public function ajax_image_seo_save_one() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            $aid = intval($_POST['attach_id'] ?? 0);
            if ($aid <= 0) { wp_send_json(array('error' => 'attach_id mancante')); return; }
            $att = get_post($aid);
            if (!$att || $att->post_type !== 'attachment') { wp_send_json(array('error' => 'Attachment non trovato')); return; }

            $alt   = isset($_POST['alt'])         ? sanitize_text_field(wp_unslash($_POST['alt'])) : '';
            $title = isset($_POST['title'])       ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
            $cap   = isset($_POST['caption'])     ? sanitize_text_field(wp_unslash($_POST['caption'])) : '';
            $desc  = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';

            update_post_meta($aid, '_wp_attachment_image_alt', $alt);
            wp_update_post(array(
                'ID'           => $aid,
                'post_title'   => $title,
                'post_excerpt' => $cap,
                'post_content' => $desc,
            ));

            $score = SEO_AEO_Image_SEO::compute_score($alt, $title, $cap, $desc);
            wp_send_json(array(
                'success' => true,
                'score'   => $score,
                'alt'     => $alt,
                'title'   => $title,
                'caption' => $cap,
                'description' => $desc,
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * Avvia bulk job: salva queue in option, schedule cron.
     * Supporta mode='apply' (default, scrive direttamente) e mode='preview' (genera senza applicare).
     */
    public function ajax_image_seo_bulk_queue() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            $ids_raw = isset($_POST['attach_ids']) ? $_POST['attach_ids'] : array();
            if (is_string($ids_raw)) {
                // Allow CSV or JSON
                $decoded = json_decode(wp_unslash($ids_raw), true);
                $ids_raw = is_array($decoded) ? $decoded : explode(',', $ids_raw);
            }
            $ids = array_values(array_unique(array_filter(array_map('intval', (array) $ids_raw))));
            if (empty($ids)) { wp_send_json(array('error' => 'Nessuna immagine selezionata')); return; }
            // Cap a 1000 per protezione
            if (count($ids) > 1000) {
                $ids = array_slice($ids, 0, 1000);
            }

            $mode = isset($_POST['mode']) ? sanitize_text_field(wp_unslash($_POST['mode'])) : 'apply';
            if ($mode !== 'preview') $mode = 'apply';

            // Verifica esistenza queue: se esiste già attiva, blocca
            $existing = SEO_AEO_Image_SEO::status();
            if (!empty($existing['active'])) {
                wp_send_json(array(
                    'error' => 'Bulk job già in esecuzione. Attendi il completamento o annulla quello corrente.',
                ));
                return;
            }

            $result = SEO_AEO_Image_SEO::start_bulk($ids, $mode, get_current_user_id());
            wp_send_json(array(
                'success'           => true,
                'queued'            => $result['queued'],
                'estimated_minutes' => $result['estimated_minutes'],
                'estimated_credits' => $result['queued'] * 2,
                'mode'              => $result['mode'],
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    //  3.35.2 — AI fix one-click + bulk changelog + bulk preview
    // ──────────────────────────────────────────────────────────────────────

    /**
     * AI fix one-click: genera metadata via backend e li APPLICA immediatamente.
     * Idempotency: skip campi già popolati (eccetto title=auto-filename, considerato vuoto).
     * Idempotency su rerun: se score già 4/4, skip API call (no spreco crediti).
     * @return array {success, score, applied[], before, after, credits_used, skipped?}
     */
    public function ajax_image_seo_ai_fix_one() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            $aid = intval($_POST['attach_id'] ?? 0);
            if ($aid <= 0) { wp_send_json(array('error' => 'attach_id mancante')); return; }
            $att = get_post($aid);
            if (!$att || $att->post_type !== 'attachment') { wp_send_json(array('error' => 'Attachment non trovato')); return; }

            $url = wp_get_attachment_url($aid);
            if (empty($url)) { wp_send_json(array('error' => 'URL immagine non disponibile')); return; }

            // BEFORE snapshot
            $before = array(
                'alt'         => (string) get_post_meta($aid, '_wp_attachment_image_alt', true),
                'title'       => (string) $att->post_title,
                'caption'     => (string) $att->post_excerpt,
                'description' => (string) $att->post_content,
            );

            // Idempotency early-exit: if score=4 e title non è "filename auto", skip API call
            $cur_score = SEO_AEO_Image_SEO::compute_score($before['alt'], $before['title'], $before['caption'], $before['description']);
            $title_is_filename = ($before['title'] !== '' && $url
                ? (basename(wp_parse_url($url, PHP_URL_PATH), '.' . pathinfo(wp_parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION))
                    === $before['title']
                   || pathinfo($before['title'], PATHINFO_FILENAME) === $before['title'])
                : false);
            if ($cur_score >= 4 && !$title_is_filename) {
                wp_send_json(array(
                    'success'      => true,
                    'skipped'      => true,
                    'reason'       => 'already_complete',
                    'score'        => $cur_score,
                    'before'       => $before,
                    'after'        => $before,
                    'applied'      => array(),
                    'credits_used' => 0,
                ));
                return;
            }

            // Call backend
            $api = $this->require_api();
            if (!$api) return;
            $parent = ($att->post_parent > 0) ? get_post($att->post_parent) : null;
            $resp = $api->api_request('/ai/generate-image-metadata', array(
                'image_url'           => $url,
                'parent_post_title'   => $parent ? (string) $parent->post_title : '',
                'parent_post_excerpt' => $parent ? (string) $parent->post_excerpt : '',
                'site_language'       => $this->get_ai_language(),
            ));

            if (!is_array($resp) || empty($resp['success']) || empty($resp['metadata'])) {
                $err = '';
                if (is_array($resp)) {
                    if (!empty($resp['detail'])) $err = is_string($resp['detail']) ? $resp['detail'] : wp_json_encode($resp['detail']);
                    elseif (!empty($resp['message'])) $err = (string) $resp['message'];
                    elseif (!empty($resp['error']) && is_string($resp['error'])) $err = $resp['error'];
                }
                wp_send_json(array('success' => false, 'error' => $err ?: 'Backend ha ritornato risposta non valida'));
                return;
            }

            // Apply — idempotency: NON sovrascrive valori esistenti (ma considera "title=filename auto" come vuoto)
            $applied = SEO_AEO_Image_SEO::apply_metadata($aid, $resp['metadata'], /*overwrite=*/false);

            // AFTER snapshot
            $att2 = get_post($aid);
            $after = array(
                'alt'         => (string) get_post_meta($aid, '_wp_attachment_image_alt', true),
                'title'       => $att2 ? (string) $att2->post_title : $before['title'],
                'caption'     => $att2 ? (string) $att2->post_excerpt : $before['caption'],
                'description' => $att2 ? (string) $att2->post_content : $before['description'],
            );
            $score_after = SEO_AEO_Image_SEO::compute_score($after['alt'], $after['title'], $after['caption'], $after['description']);

            wp_send_json(array(
                'success'      => true,
                'applied'      => $applied,
                'score'        => $score_after,
                'before'       => $before,
                'after'        => $after,
                'metadata'     => $resp['metadata'],
                'credits_used' => intval($resp['credits_used'] ?? 2),
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * 3.35.4 — AI fix STEP 1: PREVIEW.
     * Genera metadata via backend AI, scala crediti (Gemini cost is real), ma NON applica al DB.
     * Ritorna {before, suggested} al frontend per modal di review prima di applicare.
     * Idempotency early-exit: se score=4 e title non auto-filename → skip API call (no spreco).
     * @return array {success, before, suggested, score_current, score_estimated, credits_used, skipped?}
     */
    public function ajax_image_seo_ai_fix_preview() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            $aid = intval($_POST['attach_id'] ?? 0);
            if ($aid <= 0) { wp_send_json(array('error' => 'attach_id mancante')); return; }
            $att = get_post($aid);
            if (!$att || $att->post_type !== 'attachment') { wp_send_json(array('error' => 'Attachment non trovato')); return; }

            $url = wp_get_attachment_url($aid);
            if (empty($url)) { wp_send_json(array('error' => 'URL immagine non disponibile')); return; }

            // BEFORE snapshot
            $before = array(
                'alt'         => (string) get_post_meta($aid, '_wp_attachment_image_alt', true),
                'title'       => (string) $att->post_title,
                'caption'     => (string) $att->post_excerpt,
                'description' => (string) $att->post_content,
            );

            // Idempotency early-exit: if score=4 e title non è auto-filename, skip API call
            $cur_score = SEO_AEO_Image_SEO::compute_score($before['alt'], $before['title'], $before['caption'], $before['description']);
            $title_is_filename = ($before['title'] !== '' && $url
                ? (basename(wp_parse_url($url, PHP_URL_PATH), '.' . pathinfo(wp_parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION))
                    === $before['title']
                   || pathinfo($before['title'], PATHINFO_FILENAME) === $before['title'])
                : false);
            if ($cur_score >= 4 && !$title_is_filename) {
                wp_send_json(array(
                    'success'      => true,
                    'skipped'      => true,
                    'reason'       => 'already_complete',
                    'score'        => $cur_score,
                    'before'       => $before,
                    'suggested'    => $before,
                    'credits_used' => 0,
                ));
                return;
            }

            // Call backend (consuma crediti — Gemini cost is real)
            $api = $this->require_api();
            if (!$api) return;
            $parent = ($att->post_parent > 0) ? get_post($att->post_parent) : null;
            $resp = $api->api_request('/ai/generate-image-metadata', array(
                'image_url'           => $url,
                'parent_post_title'   => $parent ? (string) $parent->post_title : '',
                'parent_post_excerpt' => $parent ? (string) $parent->post_excerpt : '',
                'site_language'       => $this->get_ai_language(),
            ));

            if (!is_array($resp) || empty($resp['success']) || empty($resp['metadata'])) {
                $err = '';
                if (is_array($resp)) {
                    if (!empty($resp['detail'])) $err = is_string($resp['detail']) ? $resp['detail'] : wp_json_encode($resp['detail']);
                    elseif (!empty($resp['message'])) $err = (string) $resp['message'];
                    elseif (!empty($resp['error']) && is_string($resp['error'])) $err = $resp['error'];
                }
                wp_send_json(array('success' => false, 'error' => $err ?: 'Backend ha ritornato risposta non valida'));
                return;
            }

            $md = $resp['metadata'];
            $suggested = array(
                'alt'         => isset($md['alt']) ? (string) $md['alt'] : '',
                'title'       => isset($md['title']) ? (string) $md['title'] : '',
                'caption'     => isset($md['caption']) ? (string) $md['caption'] : '',
                'description' => isset($md['description']) ? (string) $md['description'] : '',
            );
            // Score stimato post-apply (usa suggested se non vuoto, altrimenti before — riflette idempotency apply)
            $est_alt = ($before['alt'] === '' && $suggested['alt'] !== '') ? $suggested['alt'] : $before['alt'];
            $est_title = (($before['title'] === '' || $title_is_filename) && $suggested['title'] !== '') ? $suggested['title'] : $before['title'];
            $est_caption = ($before['caption'] === '' && $suggested['caption'] !== '') ? $suggested['caption'] : $before['caption'];
            $est_desc = ($before['description'] === '' && $suggested['description'] !== '') ? $suggested['description'] : $before['description'];
            $score_estimated = SEO_AEO_Image_SEO::compute_score($est_alt, $est_title, $est_caption, $est_desc);

            wp_send_json(array(
                'success'         => true,
                'before'          => $before,
                'suggested'       => $suggested,
                'score_current'   => $cur_score,
                'score_estimated' => $score_estimated,
                'credits_used'    => intval($resp['credits_used'] ?? 2),
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * 3.35.4 — AI fix STEP 2: APPLY.
     * Riceve i campi confermati dall'utente (input dal modal preview), li scrive sul DB.
     * NON chiama il backend AI (no extra credits — pagati già nello step preview).
     * Idempotency: rispetta valori esistenti se !force_overwrite (apply_metadata default).
     * @return array {success, applied[], before, after, score}
     */
    public function ajax_image_seo_ai_fix_apply() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            $aid = intval($_POST['attach_id'] ?? 0);
            if ($aid <= 0) { wp_send_json(array('error' => 'attach_id mancante')); return; }
            $att = get_post($aid);
            if (!$att || $att->post_type !== 'attachment') { wp_send_json(array('error' => 'Attachment non trovato')); return; }

            $force_overwrite = !empty($_POST['force_overwrite']);

            // Solo i campi presenti vengono scritti. Frontend manda solo i checked.
            $md = array();
            foreach (array('alt', 'title', 'caption', 'description') as $f) {
                if (isset($_POST[$f])) {
                    $val = wp_unslash($_POST[$f]);
                    if ($f === 'description') {
                        $val = sanitize_textarea_field($val);
                    } else {
                        $val = sanitize_text_field($val);
                    }
                    if ($val !== '') {
                        $md[$f] = $val;
                    }
                }
            }

            if (empty($md)) {
                wp_send_json(array('error' => 'Nessun campo da applicare'));
                return;
            }

            // BEFORE
            $before = array(
                'alt'         => (string) get_post_meta($aid, '_wp_attachment_image_alt', true),
                'title'       => (string) $att->post_title,
                'caption'     => (string) $att->post_excerpt,
                'description' => (string) $att->post_content,
            );

            $applied = SEO_AEO_Image_SEO::apply_metadata($aid, $md, /*overwrite=*/(bool) $force_overwrite);

            // AFTER
            $att2 = get_post($aid);
            $after = array(
                'alt'         => (string) get_post_meta($aid, '_wp_attachment_image_alt', true),
                'title'       => $att2 ? (string) $att2->post_title : $before['title'],
                'caption'     => $att2 ? (string) $att2->post_excerpt : $before['caption'],
                'description' => $att2 ? (string) $att2->post_content : $before['description'],
            );
            $score_after = SEO_AEO_Image_SEO::compute_score($after['alt'], $after['title'], $after['caption'], $after['description']);

            wp_send_json(array(
                'success' => true,
                'applied' => $applied,
                'before'  => $before,
                'after'   => $after,
                'score'   => $score_after,
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * Restituisce il changelog del bulk job corrente o ultimo completato.
     * Lista di {attach_id, filename, thumb_url, before, after, fields, ts}.
     */
    public function ajax_image_seo_bulk_changelog() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            $log = SEO_AEO_Image_SEO::changelog();
            wp_send_json(array(
                'success'   => true,
                'count'     => count($log),
                'changelog' => $log,
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * Restituisce le suggestion preview per l'utente corrente.
     */
    public function ajax_image_seo_bulk_preview_get() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            $uid = get_current_user_id();
            $items = SEO_AEO_Image_SEO::preview_results($uid);
            wp_send_json(array(
                'success' => true,
                'count'   => count($items),
                'items'   => $items,
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * Applica subset di preview suggestion. Riceve array di overrides: [{attach_id, alt, title, caption, description}].
     * Usa overwrite=false di default, ma se 'force_overwrite' true sovrascrive anche i campi non vuoti.
     * Idempotency: se già applicato (transient cleared), no-op.
     */
    public function ajax_image_seo_bulk_preview_apply() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            $uid = get_current_user_id();
            $items_raw = isset($_POST['items']) ? $_POST['items'] : '';
            if (is_string($items_raw)) {
                $items_raw = json_decode(wp_unslash($items_raw), true);
            }
            if (!is_array($items_raw) || empty($items_raw)) {
                wp_send_json(array('error' => 'Nessun item da applicare'));
                return;
            }
            $force_overwrite = !empty($_POST['force_overwrite']);

            $applied_count = 0;
            $skipped = 0;
            $changelog = array();
            $errors = array();

            foreach ($items_raw as $entry) {
                if (!is_array($entry)) continue;
                $aid = intval($entry['attach_id'] ?? 0);
                if ($aid <= 0) continue;
                $att = get_post($aid);
                if (!$att || $att->post_type !== 'attachment') {
                    $errors[] = array('id' => $aid, 'error' => 'not_found');
                    continue;
                }
                $before = array(
                    'alt'         => (string) get_post_meta($aid, '_wp_attachment_image_alt', true),
                    'title'       => (string) $att->post_title,
                    'caption'     => (string) $att->post_excerpt,
                    'description' => (string) $att->post_content,
                );
                $md = array(
                    'alt'         => isset($entry['alt']) ? sanitize_text_field(wp_unslash($entry['alt'])) : '',
                    'title'       => isset($entry['title']) ? sanitize_text_field(wp_unslash($entry['title'])) : '',
                    'caption'     => isset($entry['caption']) ? sanitize_text_field(wp_unslash($entry['caption'])) : '',
                    'description' => isset($entry['description']) ? sanitize_textarea_field(wp_unslash($entry['description'])) : '',
                );
                $applied_fields = SEO_AEO_Image_SEO::apply_metadata($aid, $md, /*overwrite=*/(bool) $force_overwrite);
                if (!empty($applied_fields)) {
                    $applied_count++;
                    $att2 = get_post($aid);
                    $after = array(
                        'alt'         => (string) get_post_meta($aid, '_wp_attachment_image_alt', true),
                        'title'       => $att2 ? (string) $att2->post_title : $before['title'],
                        'caption'     => $att2 ? (string) $att2->post_excerpt : $before['caption'],
                        'description' => $att2 ? (string) $att2->post_content : $before['description'],
                    );
                    $url = wp_get_attachment_url($aid);
                    $thumb = wp_get_attachment_image_src($aid, 'thumbnail');
                    $changelog[] = array(
                        'attach_id' => $aid,
                        'filename'  => $url ? basename(wp_parse_url($url, PHP_URL_PATH)) : '',
                        'thumb_url' => is_array($thumb) ? $thumb[0] : ($url ?: ''),
                        'before'    => $before,
                        'after'     => $after,
                        'fields'    => $applied_fields,
                        'ts'        => time(),
                    );
                } else {
                    $skipped++;
                }
            }

            // Rimuovi solo i transient applicati (mantiene gli altri se user vuole "applica selezionati" più volte)
            // Strategy: rimuoviamo gli applicati dalla preview transient
            $current = SEO_AEO_Image_SEO::preview_results($uid);
            if (is_array($current) && !empty($changelog)) {
                $applied_ids = array_column($changelog, 'attach_id');
                $remaining = array_filter($current, function ($e) use ($applied_ids) {
                    return !in_array(intval($e['attach_id'] ?? 0), $applied_ids, true);
                });
                if (empty($remaining)) {
                    SEO_AEO_Image_SEO::clear_preview($uid);
                } else {
                    set_transient(SEO_AEO_Image_SEO::PREVIEW_OPT_PREFIX . $uid, array_values($remaining), 24 * HOUR_IN_SECONDS);
                }
            }

            wp_send_json(array(
                'success'       => true,
                'applied_count' => $applied_count,
                'skipped'       => $skipped,
                'errors'        => $errors,
                'changelog'     => $changelog,
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * Pulisce il transient preview dell'utente corrente (annulla preview).
     */
    public function ajax_image_seo_bulk_preview_clear() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            SEO_AEO_Image_SEO::clear_preview(get_current_user_id());
            wp_send_json(array('success' => true));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    public function ajax_image_seo_bulk_status() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            $st = SEO_AEO_Image_SEO::status();
            wp_send_json(array_merge(array('success' => true), $st));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    public function ajax_image_seo_bulk_cancel() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            SEO_AEO_Image_SEO::cancel();
            wp_send_json(array('success' => true));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }

    /**
     * Export CSV di tutte le immagini con metadata.
     * Streaming: NO wp_send_json, output diretto + die.
     */
    public function ajax_image_seo_export_csv() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_die('forbidden');
            }
            global $wpdb;
            $alt_meta = $wpdb->prefix . 'postmeta';
            $rows = $wpdb->get_results("
                SELECT p.ID, p.post_title, p.post_excerpt, p.post_content, p.post_parent, p.guid,
                       pm_alt.meta_value AS alt_text
                FROM {$wpdb->posts} p
                LEFT JOIN {$alt_meta} pm_alt ON pm_alt.post_id=p.ID AND pm_alt.meta_key='_wp_attachment_image_alt'
                WHERE p.post_type='attachment' AND p.post_mime_type LIKE 'image/%'
                ORDER BY p.ID DESC LIMIT 5000
            ");

            $filename = 'image-seo-audit-' . gmdate('Y-m-d') . '.csv';
            nocache_headers();
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            $out = fopen('php://output', 'w');
            // BOM per Excel UTF-8
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputs
            fputs($out, "\xEF\xBB\xBF");
            fputcsv($out, array(
                'id', 'filename', 'url', 'parent_post_id', 'parent_post_title', 'parent_url',
                'alt', 'title', 'caption', 'description',
                'score', 'has_alt', 'has_title', 'has_caption', 'has_description'
            ));
            foreach ((array) $rows as $r) {
                $aid = intval($r->ID);
                $url = wp_get_attachment_url($aid);
                $alt = (string) $r->alt_text;
                $title = (string) $r->post_title;
                $cap = (string) $r->post_excerpt;
                $desc = (string) $r->post_content;
                $score = SEO_AEO_Image_SEO::compute_score($alt, $title, $cap, $desc);
                $parent_id = intval($r->post_parent);
                $parent_title = '';
                $parent_url = '';
                if ($parent_id > 0) {
                    $pp = get_post($parent_id);
                    if ($pp) {
                        $parent_title = $pp->post_title;
                        $parent_url = get_permalink($parent_id);
                    }
                }
                $filename_only = $url ? basename(wp_parse_url($url, PHP_URL_PATH)) : '';
                fputcsv($out, array(
                    $aid,
                    $filename_only,
                    $url,
                    $parent_id,
                    $parent_title,
                    $parent_url,
                    $alt,
                    $title,
                    $cap,
                    $desc,
                    $score,
                    !empty($alt) ? '1' : '0',
                    !empty($title) ? '1' : '0',
                    !empty($cap) ? '1' : '0',
                    !empty($desc) ? '1' : '0',
                ));
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
            }
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
            fclose($out);
            wp_die();
        } catch (Throwable $e) {
            wp_die('Errore export: ' . esc_html($e->getMessage()));
        }
    }


    /**
     * 3.35.2 — Bulk delete slot calendar pianificati. Solo status=planned eliminabili.
     */
    public function ajax_calendar_bulk_delete() {
        try {
            check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
            if (!current_user_can('manage_options')) { wp_send_json(array('error' => 'forbidden')); return; }
            // 3.35.6: ajax() helper JSON-stringifies array → decode se stringa
            $raw = isset($_POST['slot_ids']) ? $_POST['slot_ids'] : array();
            if (is_string($raw)) {
                $raw = wp_unslash($raw);
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $raw = $decoded;
                } else {
                    $raw = array($raw);
                }
            }
            $ids = array_values(array_filter(array_map('sanitize_text_field', array_map('wp_unslash', (array) $raw))));
            if (empty($ids)) { wp_send_json(array('error' => 'Nessun slot selezionato')); return; }

            $api_url = defined('SEO_AEO_API_URL') ? SEO_AEO_API_URL : 'https://aeo-orchestra.com';
            $license_key = get_option('seo_aeo_orchestra_license_key', '');
            $deleted = 0;
            $errors = array();
            foreach ($ids as $sid) {
                $url = rtrim($api_url, '/') . '/api/calendar/slot/' . rawurlencode($sid)
                     . '?license_key=' . rawurlencode($license_key);
                $resp = wp_remote_request($url, array(
                    'method'  => 'DELETE',
                    'timeout' => 10,
                ));
                if (is_wp_error($resp)) {
                    $errors[] = $sid . ': ' . $resp->get_error_message();
                    continue;
                }
                $code = wp_remote_retrieve_response_code($resp);
                if ($code >= 200 && $code < 300) {
                    $deleted++;
                } else {
                    $errors[] = $sid . ': HTTP ' . $code;
                }
            }
            wp_send_json(array(
                'success' => true,
                'deleted' => $deleted,
                'total'   => count($ids),
                'errors'  => $errors,
            ));
        } catch (Throwable $e) {
            wp_send_json(array('error' => $e->getMessage()));
        }
    }


    /**
     * 3.35.2 — Bulk delete slot calendar pianificati. Solo status=planned eliminabili.
     */

}

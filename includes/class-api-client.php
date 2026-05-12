<?php
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com. All rights reserved.
 * Unauthorized copying, redistribution or resale is strictly prohibited.
 */
if (!defined('ABSPATH')) exit;

/**
 * API Client for AEO Orchestra with domain locking.
 */
class SEO_AEO_API_Client {
    private $api_url;
    private $license_key;
    private $domain;

    /** @var bool|null Cached connectivity result for this request */
    private $can_reach_api = null;

    public function __construct() {
        try {
            $this->api_url = defined('SEO_AEO_API_URL') ? SEO_AEO_API_URL : get_option('seo_aeo_api_url', 'https://aeo-orchestra.com');
            $this->license_key = get_option('seo_aeo_orchestra_license_key', get_option('seo_aeo_license_key', ''));
            $this->domain = $this->get_site_domain();
        } catch (Throwable $e) {
            seo_aeo_debug_log('SEO AEO API Client __construct error: ' . $e->getMessage());
            $this->api_url = 'https://aeo-orchestra.com';
            $this->license_key = '';
            $this->domain = '';
        }
    }

    /**
     * Get the clean domain of the current WordPress site.
     */
    private function get_site_domain() {
        $url = get_site_url();
        $parsed = wp_parse_url($url);
        $host = isset($parsed['host']) ? $parsed['host'] : $url;
        return strtolower(preg_replace('/^www\./', '', $host));
    }

    /**
     * Quick connectivity pre-check via wp_remote_head (2s timeout).
     * Prevents PHP from hanging on wp_remote_post when outbound HTTP is blocked.
     * Result is cached for 5 minutes (success) / 1 minute (failure).
     *
     * 3.36.0 (WP.org compliance): swapped fsockopen() for wp_remote_head() per
     * WordPress.WP.AlternativeFunctions.file_system_read_fsockopen.
     *
     * @return bool True if the API host is reachable.
     */
    public function can_reach_host() {
        if ($this->can_reach_api !== null) {
            return $this->can_reach_api;
        }

        // Check transient first (avoid HTTP probe on every page load)
        $cached = get_transient('seo_aeo_api_reachable');
        if ($cached === 'yes') {
            $this->can_reach_api = true;
            return true;
        }
        if ($cached === 'no') {
            $this->can_reach_api = false;
            return false;
        }

        // HEAD request with a 2s timeout. Any HTTP response (incl. 401/404)
        // proves the host is reachable; only a WP_Error means the network/DNS
        // itself failed.
        $resp = wp_remote_head($this->api_url, array(
            'timeout'     => 2,
            'redirection' => 0,
            'sslverify'   => true,
        ));
        if (!is_wp_error($resp)) {
            $this->can_reach_api = true;
            set_transient('seo_aeo_api_reachable', 'yes', 300); // Cache 5 min
        } else {
            $parsed = wp_parse_url($this->api_url);
            $host = isset($parsed['host']) ? $parsed['host'] : $this->api_url;
            seo_aeo_debug_log('[SEO_AEO] Cannot reach ' . $host . ' - ' . $resp->get_error_message());
            $this->can_reach_api = false;
            set_transient('seo_aeo_api_reachable', 'no', 60); // Retry in 1 min
        }

        return $this->can_reach_api;
    }

    /**
     * Returns a user-friendly error when the API host is unreachable.
     */
    private function unreachable_error() {
        $parsed = wp_parse_url($this->api_url);
        $host = isset($parsed['host']) ? $parsed['host'] : $this->api_url;
        return array(
            'error' => true,
            'message' => 'Il server non riesce a raggiungere ' . $host . '. Controlla le impostazioni firewall dell\'hosting o contatta il supporto Aruba per abilitare le connessioni HTTP in uscita.',
            'firewall_blocked' => true
        );
    }

    /**
     * Activate the license on this domain.
     */
    public function activate_license() {
        if (empty($this->license_key)) {
            return array('success' => false, 'message' => 'Nessuna license key configurata.');
        }

        if (!$this->can_reach_host()) {
            return $this->unreachable_error();
        }

        $response = @wp_remote_post($this->api_url . '/api/licenses/activate', array(
            'timeout' => 5,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode(array(
                'license_key' => $this->license_key,
                'domain' => $this->domain,
            )),
        ));

        if (is_wp_error($response)) {
            seo_aeo_debug_log('SEO AEO activate_license HTTP error: ' . $response->get_error_message());
            return array('success' => false, 'message' => 'Errore di connessione: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $code = wp_remote_retrieve_response_code($response);

        if ($code === 200 && !empty($body['success'])) {
            update_option('seo_aeo_domain_lock', array(
                'license_key' => $this->license_key,
                'domain' => $this->domain,
                'activated_at' => current_time('mysql'),
            ));
            return $body;
        }

        $detail = isset($body['detail']) ? $body['detail'] : (isset($body['message']) ? $body['message'] : 'Attivazione fallita.');
        seo_aeo_debug_log('SEO AEO activate_license failed: ' . $detail);
        return array('success' => false, 'message' => $detail);
    }

    /**
     * Validate a license key against the API.
     */
    public function validate_license($license_key) {
        if (empty($license_key)) {
            return array('valid' => false, 'message' => 'License key vuota.');
        }

        if (!$this->can_reach_host()) {
            return $this->unreachable_error();
        }

        $response = @wp_remote_post($this->api_url . '/api/licenses/validate', array(
            'timeout' => 5,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode(array(
                'license_key' => $license_key,
                'domain' => $this->domain,
            )),
        ));

        if (is_wp_error($response)) {
            return array('valid' => false, 'message' => 'Errore connessione: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return is_array($body) ? $body : array('valid' => false, 'message' => 'Risposta non valida dal server.');
    }

    /**
     * Validate that the current domain matches the activation.
     */
    public function validate_domain() {
        $lock = get_option('seo_aeo_domain_lock', array());
        if (empty($lock) || empty($lock['domain'])) {
            return false;
        }
        return ($lock['domain'] === $this->domain);
    }

    /**
     * Get credit costs from API with 1-hour cache via wp_transient.
     */
    public function get_credit_costs() {
        $cached = get_transient('seo_aeo_credit_costs');
        if ($cached !== false) {
            return $cached;
        }

        // Fallback defaults (used when API unreachable)
        $defaults = array(
            'seo_analysis' => 5,
            'meta_generation' => 2,
            'content_generation_short' => 5,
            'content_generation_medium' => 10,
            'content_generation_long' => 15,
            'local_seo' => 4,
            'aeo_analysis' => 5,
            'aeo_content' => 12,
            'image_generation' => 15,
            'complete_article' => 25,
            'demo_analysis' => 0
        );

        if (!$this->can_reach_host()) {
            set_transient('seo_aeo_credit_costs', $defaults, 300);
            return $defaults;
        }

        $response = @wp_remote_get($this->api_url . '/api/settings/credit-costs', array(
            'timeout' => 3,
            'sslverify' => false
        ));

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $costs = json_decode(wp_remote_retrieve_body($response), true);
            if (is_array($costs)) {
                set_transient('seo_aeo_credit_costs', $costs, HOUR_IN_SECONDS);
                return $costs;
            }
        }

        set_transient('seo_aeo_credit_costs', $defaults, 300);
        return $defaults;
    }

    /**
     * Generic API request via POST.
     *
     * Auto-inject `brand_voice_addition` per gli endpoint AI/SEO di generazione,
     * letto dal profilo Brand Voice attivo (since 3.21.8).
     */
    public function api_request($endpoint, $data = array()) {
        if (!$this->can_reach_host()) {
            return $this->unreachable_error();
        }

        $data['license_key'] = $this->license_key;
        $data['domain'] = $this->domain;

        // Brand Voice + language auto-injection per endpoint di generazione AI (3.26.0)
        $bv_endpoints = array(
            '/ai/generate-content',
            '/ai/aeo-content',
            '/ai/local-seo',
            '/ai/generate-meta-tags',
            '/ai/complete-article',
            '/seo/cannibalization-fix-proposal',
        );
        $is_bv_endpoint = false;
        foreach ($bv_endpoints as $bv_ep) {
            if (strpos($endpoint, $bv_ep) === 0) { $is_bv_endpoint = true; break; }
        }

        if ($is_bv_endpoint) {
            // Brand Voice
            if (class_exists('SEO_AEO_Brand_Voice') && empty($data['brand_voice_addition'])) {
                $addition = SEO_AEO_Brand_Voice::build_system_prompt_addition();
                if (!empty($addition)) {
                    $data['brand_voice_addition'] = $addition;
                }
            }
            // Language: usa setting plugin (it/en) per far generare l'AI nella lingua attiva
            if (empty($data['language'])) {
                $lang = get_option('seo_aeo_orchestra_language', 'it');
                if (!in_array($lang, array('it', 'en', 'es', 'fr', 'de'), true)) $lang = 'it';
                $data['language'] = $lang;
            }
        }

        // 3.26.2: complete-article fa 3 AI calls (content + meta + image) → serve timeout esteso.
        // image-generation singola può prendere 60-90s. 180s è il safe upper bound.
        $endpoint_timeout = 90;
        if (strpos($endpoint, '/ai/complete-article') === 0 || strpos($endpoint, '/ai/generate-image') === 0) {
            $endpoint_timeout = 180;
        }

        $response = @wp_remote_post($this->api_url . '/api' . $endpoint, array(
            'timeout' => $endpoint_timeout,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($data),
        ));

        if (is_wp_error($response)) {
            return array('error' => true, 'message' => $response->get_error_message());
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);
        return is_array($result) ? $result : array('error' => true, 'message' => 'Risposta non valida dal server.');
    }

    /**
     * Make an API call (legacy method, used by wallet/balance etc).
     */
    public function call($endpoint, $data = array()) {
        if (!$this->can_reach_host()) {
            return $this->unreachable_error();
        }

        $data['license_key'] = $this->license_key;
        $data['domain'] = $this->domain;

        $response = @wp_remote_post($this->api_url . '/api/' . $endpoint, array(
            'timeout' => 10,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($data),
        ));

        if (is_wp_error($response)) {
            return array('error' => true, 'message' => $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $result = json_decode(wp_remote_retrieve_body($response), true);

        if ($code === 403) {
            $msg = isset($result['message']) ? $result['message'] : 'Accesso negato.';
            seo_aeo_debug_log('SEO AEO API 403: ' . $msg);
            return array('error' => true, 'message' => $msg);
        }

        return is_array($result) ? $result : array('error' => true, 'message' => 'Risposta non valida.');
    }

    /**
     * Identity Profile (Onboarding 2.0 Stage 1) — read.
     * GET /api/identity/profile?license_key=...&domain=...
     */
    public function get_identity_profile() {
        if (!$this->can_reach_host()) {
            return $this->unreachable_error();
        }
        $url = $this->api_url . '/api/identity/profile?license_key=' . rawurlencode($this->license_key) . '&domain=' . rawurlencode($this->domain);
        $response = @wp_remote_get($url, array('timeout' => 8));
        if (is_wp_error($response)) {
            return array('error' => true, 'message' => $response->get_error_message());
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return is_array($body) ? $body : array();
    }

    /**
     * Identity Profile — save (upsert). Backend valida tier.
     * POST /api/identity/profile
     */
    public function save_identity_profile($data = array()) {
        if (!$this->can_reach_host()) {
            return $this->unreachable_error();
        }
        $data['license_key'] = $this->license_key;
        $data['domain'] = $this->domain;
        $response = @wp_remote_post($this->api_url . '/api/identity/profile', array(
            'timeout' => 10,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($data),
        ));
        if (is_wp_error($response)) {
            return array('error' => true, 'message' => $response->get_error_message());
        }
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($code === 403) {
            $msg = is_array($body) && isset($body['detail']) ? $body['detail'] : 'Funzione disponibile solo con licenza Pro.';
            return array('error' => true, 'message' => $msg, 'forbidden' => true);
        }
        return is_array($body) ? $body : array('error' => true, 'message' => 'Risposta non valida.');
    }

    /**
     * Identity Profile — preview llms.txt sections (no save).
     * POST /api/identity/preview-llms
     */
    public function preview_llms($data = array()) {
        if (!$this->can_reach_host()) {
            return $this->unreachable_error();
        }
        $data['license_key'] = $this->license_key;
        $data['domain'] = $this->domain;
        $response = @wp_remote_post($this->api_url . '/api/identity/preview-llms', array(
            'timeout' => 8,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($data),
        ));
        if (is_wp_error($response)) {
            return array('error' => true, 'message' => $response->get_error_message());
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return is_array($body) ? $body : array('error' => true, 'message' => 'Risposta non valida.');
    }


    /**
     * Identity Scan-Site (Stage 2.5 — 3.35.55-beta).
     * Extended: mode parameter (top10/full/user_selected), explicit page selection,
     * page_role_candidates for backend LLM augment.
     */
    public function scan_site($mode = 'top10', $pages = array(), $force = false, $page_role_candidates = array()) {
        if (!$this->can_reach_host()) {
            return $this->unreachable_error();
        }
        $body = array(
            'license_key'           => $this->license_key,
            'domain'                => $this->domain,
            'mode'                  => is_string($mode) && in_array($mode, array('top10', 'full', 'user_selected'), true) ? $mode : 'top10',
            'force'                 => (bool) $force,
            'pages'                 => is_array($pages) ? array_values(array_map('intval', $pages)) : array(),
            'page_role_candidates'  => is_array($page_role_candidates) ? $page_role_candidates : array(),
        );
        $response = @wp_remote_post($this->api_url . '/api/identity/scan-site', array(
            'timeout' => 60,  // full scan can take up to 50s with LLM augment
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => wp_json_encode($body),
        ));
        if (is_wp_error($response)) {
            return array('error' => true, 'message' => $response->get_error_message());
        }
        $code = wp_remote_retrieve_response_code($response);
        $body_decoded = json_decode(wp_remote_retrieve_body($response), true);
        if ($code === 403) {
            $msg = is_array($body_decoded) && isset($body_decoded['detail']) ? $body_decoded['detail'] : 'Funzione disponibile solo con licenza Pro.';
            return array('error' => true, 'message' => $msg, 'forbidden' => true);
        }
        if ($code === 429) {
            $msg = is_array($body_decoded) && isset($body_decoded['detail']) ? $body_decoded['detail'] : 'Quota scan esaurita.';
            return array('error' => true, 'message' => $msg, 'quota_exhausted' => true);
        }
        if ($code !== 200) {
            $msg = is_array($body_decoded) && isset($body_decoded['detail']) ? $body_decoded['detail'] : ('HTTP ' . $code);
            return array('error' => true, 'message' => $msg, 'status' => $code, 'body' => $body_decoded);
        }
        return is_array($body_decoded) ? $body_decoded : array('error' => true, 'message' => 'Risposta non valida.');
    }

    /**
     * GET /api/identity/industry-examples?industry=X — free, no tier gating.
     */
    public function industry_examples($industry = '') {
        if (!$this->can_reach_host()) {
            return $this->unreachable_error();
        }
        $url = $this->api_url . '/api/identity/industry-examples?industry=' . rawurlencode((string) $industry);
        $response = @wp_remote_get($url, array('timeout' => 6));
        if (is_wp_error($response)) {
            return array('error' => true, 'message' => $response->get_error_message());
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return is_array($body) ? $body : array('error' => true, 'message' => 'Risposta non valida.');
    }

    /**
     * 3.35.83 — Business Profile read.
     */
    public function get_business_profile() {
        if (!$this->can_reach_host()) {
            return $this->unreachable_error();
        }
        $url = $this->api_url . '/api/business-profile?license_key=' . rawurlencode($this->license_key) . '&domain=' . rawurlencode($this->domain);
        $response = @wp_remote_get($url, array('timeout' => 8));
        if (is_wp_error($response)) {
            return array('error' => true, 'message' => $response->get_error_message());
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return is_array($body) ? $body : array();
    }

    /**
     * 3.35.83 — Business Profile save (PUT, partial fields).
     */
    /**
     * 3.39.1 — Trigger Site Context auto-generation on the backend.
     */
    public function generate_site_context() {
        if (!$this->can_reach_host()) {
            return $this->unreachable_error();
        }
        $body = array(
            'license_key' => $this->license_key,
            'domain'      => $this->domain,
        );
        $response = @wp_remote_post($this->api_url . '/api/business-profile/site-context-generate', array(
            'timeout' => 60,
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => wp_json_encode($body),
        ));
        if (is_wp_error($response)) {
            return array('error' => true, 'message' => $response->get_error_message());
        }
        $body_decoded = json_decode(wp_remote_retrieve_body($response), true);
        return is_array($body_decoded) ? $body_decoded : array('error' => true, 'message' => 'Risposta non valida.');
    }

    public function save_business_profile($fields = array()) {
        if (!$this->can_reach_host()) {
            return $this->unreachable_error();
        }
        $body_data = array(
            'license_key' => $this->license_key,
            'domain'      => $this->domain,
            'fields'      => is_array($fields) ? $fields : array(),
        );
        $response = @wp_remote_request($this->api_url . '/api/business-profile', array(
            'method'  => 'PUT',
            'timeout' => 10,
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => wp_json_encode($body_data),
        ));
        if (is_wp_error($response)) {
            return array('error' => true, 'message' => $response->get_error_message());
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return is_array($body) ? $body : array('error' => true, 'message' => 'Risposta non valida.');
    }

    /**
     * 3.35.83 — Business Profile preview (context block scope-aware).
     */
    public function preview_business_profile($scope = 'full') {
        if (!$this->can_reach_host()) {
            return $this->unreachable_error();
        }
        $scope = in_array($scope, array('full', 'public'), true) ? $scope : 'full';
        $url = $this->api_url . '/api/business-profile/preview?license_key=' . rawurlencode($this->license_key)
             . '&domain=' . rawurlencode($this->domain) . '&scope=' . rawurlencode($scope);
        $response = @wp_remote_get($url, array('timeout' => 8));
        if (is_wp_error($response)) {
            return array('error' => true, 'message' => $response->get_error_message());
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return is_array($body) ? $body : array('error' => true, 'message' => 'Risposta non valida.');
    }

    /**
     * 3.35.83 — Business Profile confirm.
     */
    public function confirm_business_profile() {
        if (!$this->can_reach_host()) {
            return $this->unreachable_error();
        }
        $body_data = array('license_key' => $this->license_key, 'domain' => $this->domain);
        $response = @wp_remote_post($this->api_url . '/api/business-profile/confirm', array(
            'timeout' => 8,
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => wp_json_encode($body_data),
        ));
        if (is_wp_error($response)) {
            return array('error' => true, 'message' => $response->get_error_message());
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return is_array($body) ? $body : array('error' => true, 'message' => 'Risposta non valida.');
    }

    /**
     * 3.35.83 — Business Profile auto-create skeleton from WP basics.
     */
    public function auto_create_business_profile_from_wp($extra = array()) {
        if (!$this->can_reach_host()) {
            return $this->unreachable_error();
        }
        $body_data = array(
            'license_key'          => $this->license_key,
            'domain'               => $this->domain,
            'business_name'        => isset($extra['business_name']) ? (string) $extra['business_name'] : '',
            'business_description' => isset($extra['business_description']) ? (string) $extra['business_description'] : '',
            'prefilled_from_wp'    => isset($extra['prefilled_from_wp']) ? (bool) $extra['prefilled_from_wp'] : true,
        );
        $response = @wp_remote_post($this->api_url . '/api/business-profile/auto-create', array(
            'timeout' => 10,
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => wp_json_encode($body_data),
        ));
        if (is_wp_error($response)) {
            return array('error' => true, 'message' => $response->get_error_message());
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return is_array($body) ? $body : array('error' => true, 'message' => 'Risposta non valida.');
    }

}

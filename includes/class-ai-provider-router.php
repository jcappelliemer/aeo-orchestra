<?php
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com
 *
 * 3.35.78 — Multi-provider AI router (backend-centralized, NO BYOK).
 *
 * All tiers (standard / premium_plus / premium_plus_plus) dispatch via api_request
 * to backend FastAPI which handles provider selection, master API key usage, and
 * credit billing. The plugin no longer holds any third-party API keys.
 *
 * Pricing matrix configurable via WP option `seo_aeo_credit_pricing_matrix`.
 *
 * ARCHITECTURE NOTE: backend endpoints `/api/ai/{task}` (e.g. /api/ai/brand-voice-about,
 * /api/ai/verify-live) are NOT yet implemented in 3.35.78 — they are scheduled for
 * separate backend deploy. Until backend is extended, calls return:
 *   {ok: false, error: 'backend_not_available', user_message: '⚠ Premium+ temporaneamente
 *   non disponibile. Riprova fra qualche minuto.'}
 *
 * NO fallback to Gemini standard from premium tiers — too confusing for users who
 * explicitly selected premium.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_AI_Provider_Router {

    const USAGE_LOG_OPTION = 'seo_aeo_ai_usage_log';
    const USAGE_LOG_MAX = 200;

    public static function default_pricing_matrix() {
        return array(
            'brand-voice-about' => array(
                'standard'          => array('provider' => 'gemini',    'model' => 'gemini-2.5-flash',  'credits_cost' => 1),
                'premium_plus'      => array('provider' => 'anthropic', 'model' => 'claude-haiku-4-5',  'credits_cost' => 10),
                'premium_plus_plus' => array('provider' => 'anthropic', 'model' => 'claude-sonnet-4-6', 'credits_cost' => 50),
            ),
            'verify-live' => array(
                'standard'          => array('provider' => 'gemini',    'model' => 'gemini-2.5-flash',  'credits_cost' => 5),
                'premium_plus_plus' => array('provider' => 'anthropic', 'model' => 'claude-haiku-4-5',  'credits_cost' => 35),
            ),
        );
    }

    public static function get_pricing_matrix() {
        $stored = get_option('seo_aeo_credit_pricing_matrix', null);
        if (is_array($stored)) {
            return array_replace_recursive(self::default_pricing_matrix(), $stored);
        }
        return self::default_pricing_matrix();
    }

    public static function select_provider($task, $tier = 'standard') {
        $matrix = self::get_pricing_matrix();
        if (!isset($matrix[$task])) return null;
        if (isset($matrix[$task][$tier])) return $matrix[$task][$tier];
        return isset($matrix[$task]['standard']) ? $matrix[$task]['standard'] : null;
    }

    /**
     * Main dispatch. ALL tiers go through backend api_request.
     * Returns: array {ok, task, tier, provider, model, credits_cost, response?, error?, user_message?}
     */
    public static function call_for_task($task, $payload, $opts = array()) {
        $tier = isset($opts['tier']) ? (string) $opts['tier'] : 'standard';
        $config = self::select_provider($task, $tier);
        if (!$config) {
            return array(
                'ok'    => false,
                'error' => 'unknown_task: ' . $task,
                'user_message' => '⚠ Task non riconosciuto.',
            );
        }

        $result = array(
            'ok'           => false,
            'task'         => $task,
            'tier'         => $tier,
            'provider'     => $config['provider'],
            'model'        => $config['model'],
            'credits_cost' => (int) $config['credits_cost'],
        );

        // Dispatch via backend (api_request)
        $response = self::call_backend($task, $payload, $tier, $config);

        if (!is_array($response)) {
            $result['error'] = 'invalid_response';
            $result['user_message'] = '⚠ Risposta non valida dal server. Riprova fra qualche minuto.';
            return $result;
        }

        if (!empty($response['error'])) {
            $msg = isset($response['message']) ? (string) $response['message'] : (isset($response['error']) ? (string) $response['error'] : 'unknown');
            // Backend not yet extended (404 / Cannot POST / etc.) — friendly message
            $is_backend_missing = (
                stripos($msg, '404') !== false ||
                stripos($msg, 'not found') !== false ||
                stripos($msg, 'method not allowed') !== false ||
                stripos($msg, 'method_not_allowed') !== false ||
                stripos($msg, 'unreachable') !== false
            );
            $result['error'] = $msg;
            $result['user_message'] = $is_backend_missing
                ? '⚠ Premium+ temporaneamente non disponibile. Riprova fra qualche minuto.'
                : ('⚠ Errore: ' . $msg);
            return $result;
        }

        $result['ok'] = true;
        $result['response'] = array(
            'text'     => isset($response['text']) ? (string) $response['text'] : '',
            'variants' => isset($response['variants']) ? $response['variants'] : null,
            'usage'    => isset($response['usage']) ? $response['usage'] : array(),
            'raw'      => $response,
        );

        // Log usage (backend authoritative for credit billing — local log is telemetry only)
        self::log_usage(array(
            'ts'             => current_time('mysql'),
            'task'           => $task,
            'tier'           => $tier,
            'provider'       => $config['provider'],
            'model'          => $config['model'],
            'credits_cost'   => (int) $config['credits_cost'],
            'input_tokens'   => isset($response['usage']['input_tokens'])  ? (int) $response['usage']['input_tokens']  : 0,
            'output_tokens'  => isset($response['usage']['output_tokens']) ? (int) $response['usage']['output_tokens'] : 0,
            'user_id'        => isset($opts['user_id']) ? (int) $opts['user_id'] : get_current_user_id(),
        ));

        return $result;
    }

    /**
     * Dispatch via existing api_request to backend FastAPI.
     */
    private static function call_backend($task, $payload, $tier, $config) {
        global $seo_aeo_api;
        if (!$seo_aeo_api && class_exists('SEO_AEO_API_Client')) {
            $seo_aeo_api = new SEO_AEO_API_Client();
        }
        if (!$seo_aeo_api || !method_exists($seo_aeo_api, 'api_request')) {
            return array('error' => true, 'message' => 'unreachable: API client unavailable');
        }
        $endpoint = '/ai/' . sanitize_key($task);
        $data = array(
            'tier'         => $tier,
            'provider'     => $config['provider'],
            'model'        => $config['model'],
            'credits_cost' => (int) $config['credits_cost'],
            'payload'      => $payload,
        );
        return $seo_aeo_api->api_request($endpoint, $data);
    }

    public static function log_usage($entry) {
        $log = get_option(self::USAGE_LOG_OPTION, array());
        if (!is_array($log)) $log = array();
        $log[] = $entry;
        if (count($log) > self::USAGE_LOG_MAX) {
            $log = array_slice($log, -self::USAGE_LOG_MAX);
        }
        update_option(self::USAGE_LOG_OPTION, $log, false);
    }

    public static function get_usage_log($limit = 50) {
        $log = get_option(self::USAGE_LOG_OPTION, array());
        if (!is_array($log)) return array();
        return array_slice($log, -max(1, (int) $limit));
    }
}

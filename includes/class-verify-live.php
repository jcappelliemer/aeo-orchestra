<?php
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com
 *
 * 3.35.77 — Verify-live SSE Phase 1 (backend infrastructure).
 *
 * Endpoint: POST /wp-json/aeo-orchestra/v1/verify-live
 * Auth:     Authorization: Bearer {license_key}
 * Body:     {"url": "...", "tier": "standard" | "premium_plus_plus"}
 *
 * Response: text/event-stream (SSE)
 * Events:
 *   start    — verification kicked off
 *   progress — local fetch milestones (head, llms.txt, sitemap)
 *   insight  — AI-detected fact (identity / schema / brand_disambiguation / …)
 *   warning  — flagged but-passed checks
 *   complete — final score + counters + summary
 *
 * Aruba shared hosting safety:
 *   - set_time_limit(0)
 *   - ignore_user_abort(true)
 *   - flush() + ob_flush() per event (with output_buffering=Off check)
 *   - Disable gzip via header Cache-Control: no-cache
 *   - Disable nginx X-Accel-Buffering
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Verify_Live {

    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_rest'));
    }

    public static function register_rest() {
        register_rest_route('aeo-orchestra/v1', '/verify-live', array(
            'methods'             => 'POST',
            'callback'            => array(__CLASS__, 'rest_handler'),
            'permission_callback' => array(__CLASS__, 'rest_auth'),
            'args'                => array(
                'url'  => array('required' => false, 'type' => 'string'),
                'tier' => array('required' => false, 'type' => 'string'),
            ),
        ));
    }

    /**
     * Bearer license_key auth (same pattern as class-debug-snapshot).
     */
    public static function rest_auth($request) {
        // Allow logged-in admins straight through
        if (current_user_can('manage_options')) return true;

        $auth = $request->get_header('authorization');
        if (!$auth) return new WP_Error('no_auth', 'Missing Authorization header', array('status' => 401));
        if (stripos($auth, 'Bearer ') !== 0) return new WP_Error('bad_auth', 'Expected Bearer token', array('status' => 401));
        $token = trim(substr($auth, 7));

        $stored = (string) get_option('seo_aeo_license_key', '');
        if (!$stored || !hash_equals($stored, $token)) {
            return new WP_Error('forbidden', 'Invalid license key', array('status' => 403));
        }
        return true;
    }

    public static function rest_handler($request) {
        $body = $request->get_json_params();
        if (!is_array($body)) $body = array();
        $url  = isset($body['url'])  ? esc_url_raw((string) $body['url'])  : home_url('/');
        $tier = isset($body['tier']) ? sanitize_key((string) $body['tier']) : 'standard';
        if (!filter_var($url, FILTER_VALIDATE_URL)) $url = home_url('/');
        if (!in_array($tier, array('standard', 'premium_plus_plus'), true)) $tier = 'standard';

        // Free tier rate limit
        $today_count = (int) get_transient('seo_aeo_verify_live_count_' . gmdate('Y-m-d'));
        $free_limit  = (int) get_option('seo_aeo_verify_live_free_daily_limit', 1);
        if ($tier === 'standard' && $today_count >= $free_limit) {
            return new WP_Error('rate_limited', 'Daily free tier limit reached (' . $free_limit . '/day). Upgrade to Premium++ for more.', array('status' => 429));
        }

        // Setup SSE
        self::setup_sse_environment();

        try {
            self::run_verification($url, $tier);
            // Increment rate limit counter
            set_transient('seo_aeo_verify_live_count_' . gmdate('Y-m-d'), $today_count + 1, DAY_IN_SECONDS);
        } catch (Throwable $e) {
            self::emit_event('error', array('message' => $e->getMessage()));
        }

        // Hard-exit so WP doesn't append anything else
        exit;
    }

    private static function setup_sse_environment() {
        // 3.35.85.0 (WP.org review compliance): the runtime overrides below are
        // SSE-streaming requirements, NOT global performance tuning. They run
        // exclusively inside an authenticated AJAX handler whose entire purpose
        // is to stream Server-Sent Events back to the admin's browser. The
        // calls have no observable effect on any other request:
        //   - set_time_limit(0): without it, php-fpm kills the connection after
        //     max_execution_time (typically 30s), which is shorter than a real
        //     AI verification roundtrip.
        //   - ini_set output_buffering/zlib.output_compression/implicit_flush:
        //     without these, the server buffers SSE bytes and the browser sees
        //     no progress until the connection closes — defeating SSE.
        // No `ini_set('memory_limit', …)` here; the prior memory-limit override
        // was removed in 3.35.85.0 per the WP.org reviewer note. If this
        // endpoint is reached by a user without a valid license, the upstream
        // gate at routes/verify-live.py returns 401 before we ever get here.
        // phpcs:disable Squiz.PHP.DiscouragedFunctions.Discouraged
        // phpcs:disable WordPress.PHP.IniSet.Risky
        @set_time_limit(0);
        @ignore_user_abort(true);

        // Flush all output buffers (otherwise SSE doesn't stream)
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        // Disable any future buffering
        @ini_set('output_buffering', 'Off');
        @ini_set('zlib.output_compression', 'Off');
        @ini_set('implicit_flush', '1');
        // phpcs:enable Squiz.PHP.DiscouragedFunctions.Discouraged
        // phpcs:enable WordPress.PHP.IniSet.Risky
        ob_implicit_flush(true);

        // SSE headers
        header('Content-Type: text/event-stream; charset=UTF-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('X-Accel-Buffering: no');  // nginx — disable buffering
        header('Connection: keep-alive');

        // Initial flush to start the stream
        echo str_repeat(' ', 2048) . "\n";  // padding for some proxies
        @flush();
    }

    private static function emit_event($event, $data) {
        if (connection_aborted()) return;
        // 3.36.0 (WP.org Issue A): SSE response is text/event-stream, not HTML.
        // The event name is sanitized via sanitize_key — only alphanumerics +
        // hyphen/underscore — so it can never carry HTML or break the SSE
        // framing. The payload is wp_json_encode'd so any user-supplied string
        // inside it is JSON-escaped (control chars and HTML chars become \uXXXX
        // or \"). esc_html() would mangle the JSON for any consumer, so we
        // intentionally skip it here. phpcs:disable hardens against the static
        // analyzer that doesn't understand the SSE context.
        $event_safe = sanitize_key($event);
        $payload    = wp_json_encode($data);
        if (!is_string($payload)) {
            $payload = '{}';
        }
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        echo 'event: ' . $event_safe . "\n";
        echo 'data: '  . $payload    . "\n\n";
        // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
        @flush();
    }

    /**
     * Main verification flow. Emits SSE events as it progresses.
     */
    public static function run_verification($url, $tier) {
        $started_at = microtime(true);

        // 3.35.81.1: license_key for refund (Patch 6)
        $license_key = (string) get_option("seo_aeo_license_key", "");

        self::emit_event('start', array(
            'url'  => $url,
            'tier' => $tier,
            'step' => 1,
            'label' => 'Avvio verifica…',
        ));

        // Step 2: Fetch homepage HTML
        self::emit_event('progress', array('step' => 2, 'label' => 'Lettura homepage…', 'url_fetched' => $url));
        $home_html = self::fetch_url($url);
        $head_html = '';
        $jsonld_blocks = array();
        if ($home_html !== '') {
            if (preg_match('#<head[^>]*>(.*?)</head>#si', $home_html, $hm)) $head_html = $hm[1];
            if (preg_match_all('#<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $head_html, $mm)) {
                foreach ($mm[1] as $blk) $jsonld_blocks[] = trim($blk);
            }
        }
        self::emit_event('progress', array(
            'step' => 3,
            'label' => 'Head HTML estratto · ' . strlen($head_html) . ' bytes',
            'head_size' => strlen($head_html),
            'jsonld_count' => count($jsonld_blocks),
        ));

        // Step 4: Fetch llms.txt
        self::emit_event('progress', array('step' => 4, 'label' => 'Verifica /llms.txt…'));
        $base = self::base_url($url);
        $llms_txt = self::fetch_url($base . '/llms.txt');
        self::emit_event('progress', array(
            'step' => 5,
            'label' => $llms_txt ? '/llms.txt presente · ' . strlen($llms_txt) . ' bytes' : '/llms.txt non trovato',
            'found' => $llms_txt !== '',
            'size' => strlen($llms_txt),
        ));

        // Step 6: Fetch sitemap.xml
        self::emit_event('progress', array('step' => 6, 'label' => 'Verifica /sitemap.xml…'));
        $sitemap_xml = self::fetch_url($base . '/sitemap.xml');
        self::emit_event('progress', array(
            'step' => 7,
            'label' => $sitemap_xml ? '/sitemap.xml presente · ' . strlen($sitemap_xml) . ' bytes' : '/sitemap.xml non trovato',
            'found' => $sitemap_xml !== '',
        ));

        // Step 8: Build context for AI
        self::emit_event('progress', array('step' => 8, 'label' => 'Compilo contesto per AI…'));
        $context = self::build_context($url, $head_html, $jsonld_blocks, $llms_txt, $sitemap_xml);

        // Step 9: Call AI provider
        self::emit_event('progress', array('step' => 9, 'label' => 'Interrogo AI con grounding…'));
        $ai_result = self::call_ai_with_grounding($context, $tier);

        // 3.35.81.1.1: Premium++ pass-through — backend returns rich shape
        // {status, score, breakdown_by_query[5], suggestions[], queries_executed, credits_charged, credits_refunded}
        // No 'text' field → must NOT go through parse_ai_findings.
        // Backend handles credit deduction + refund-on-failure internally for Premium++.
        $raw = (isset($ai_result['response']['raw']) && is_array($ai_result['response']['raw'])) ? $ai_result['response']['raw'] : null;
        if ($raw && (isset($raw['breakdown_by_query']) || isset($raw['suggestions']))) {
            $elapsed = round(microtime(true) - $started_at, 2);
            $tier_used = isset($raw['provider']) ? (string) $raw['provider'] : (isset($ai_result['provider']) ? (string) $ai_result['provider'] : '?');
            $payload = array(
                'status'             => isset($raw['status']) ? (string) $raw['status'] : 'success',
                'status_reason'      => isset($raw['error_code']) ? (string) $raw['error_code'] : '',
                'message'            => isset($raw['message']) ? (string) $raw['message'] : '',
                'credits_refunded'   => isset($raw['credits_refunded']) ? (int) $raw['credits_refunded'] : 0,
                'credits_charged'    => isset($raw['credits_charged']) ? (int) $raw['credits_charged'] : 0,
                'score'              => isset($raw['score']) ? $raw['score'] : null,
                'breakdown_by_query' => isset($raw['breakdown_by_query']) ? $raw['breakdown_by_query'] : array(),
                'suggestions'        => isset($raw['suggestions']) ? $raw['suggestions'] : array(),
                'queries_executed'   => isset($raw['queries_executed']) ? (int) $raw['queries_executed'] : 0,
                'tier_used'          => $tier_used,
                'elapsed_sec'        => $elapsed,
                'provider'           => isset($raw['provider']) ? (string) $raw['provider'] : 'anthropic',
                'model'              => isset($raw['model']) ? (string) $raw['model'] : 'claude-haiku-4-5',
            );
            self::emit_event('complete', $payload);
            return;
        }

        // Emit insights from AI result
        // 3.35.81.1: status-aware + no silent score=50 fallback (Patch 6)
        $passed = 0; $warnings = 0; $errors = 0;
        $score = null;
        $vl_status = 'success';
        $vl_status_reason = '';

        if (!empty($ai_result['ok']) && !empty($ai_result['response']['text'])) {
            $parsed = self::parse_ai_findings($ai_result['response']['text']);
            $has_signal = $parsed && (
                (isset($parsed['insights']) && count($parsed['insights']) > 0)
                || (isset($parsed['warnings']) && count($parsed['warnings']) > 0)
                || (isset($parsed['score']) && $parsed['score'] !== null)
            );
            if ($has_signal) {
                foreach ($parsed['insights'] as $ins) {
                    self::emit_event('insight', $ins);
                    $passed++;
                }
                foreach ($parsed['warnings'] as $w) {
                    self::emit_event('warning', $w);
                    $warnings++;
                }
                $score = isset($parsed['score']) && $parsed['score'] !== null
                    ? (int) $parsed['score']
                    : (50 + ($passed * 5) - ($warnings * 3));
                $score = max(0, min(100, $score));
            } else {
                // Path B: provider responded but parser found nothing -> parsing_failed
                $vl_status = 'parsing_failed';
                $vl_status_reason = 'Parser returned 0 insights/warnings/score from non-empty payload';
                $errors++;
                $raw_snippet = substr((string) $ai_result['response']['text'], 0, 500);
                orch_debug_log('[seo-aeo-orchestra verify-live] parsing_failed; raw payload: ' . $raw_snippet);
                self::emit_event('warning', array(
                    'category' => 'parsing_failed',
                    'finding' => 'AI ha risposto ma non e\' stato possibile estrarre insight strutturati.',
                ));
            }
        } else {
            // Path A: provider unavailable / empty payload -> ai_unavailable
            $vl_status = 'ai_unavailable';
            $vl_status_reason = isset($ai_result['error']) ? ('AI provider error: ' . $ai_result['error']) : 'AI response empty';
            $errors++;
            self::emit_event('warning', array(
                'category' => 'ai_unavailable',
                'finding' => $vl_status_reason,
            ));
        }

        $elapsed = round(microtime(true) - $started_at, 2);
        $tier_used = !empty($ai_result['fallback_used']) ? ($ai_result['provider'] . ':fallback') : (isset($ai_result['provider']) ? $ai_result['provider'] : '?');

        // 3.35.81.1: auto-refund credits on error_state (Patch 6)
        $credits_refunded = 0;
        if ($vl_status !== 'success') {
            // Standard verify-live charges 5 cr (matrix in class-ai-provider-router.php)
            // Default to 5 if not provided by the AI router result
            $charged = isset($ai_result['credits_charged']) ? (int) $ai_result['credits_charged'] : 5;
            if ($charged > 0 && !empty($license_key)) {
                $refund_resp = self::backend_refund($license_key, $charged, $vl_status);
                if ($refund_resp && !empty($refund_resp['success'])) {
                    $credits_refunded = $charged;
                }
            }
        }

        self::emit_event('complete', array(
            'status' => $vl_status,
            'status_reason' => $vl_status_reason,
            'credits_refunded' => $credits_refunded,
            'score' => $score,
            'passed_checks' => $passed,
            'warnings' => $warnings,
            'errors' => $errors,
            'tier_used' => $tier_used,
            'elapsed_sec' => $elapsed,
            'summary' => self::compose_summary($passed, $warnings, $errors, $score, $tier_used),
        ));
    }

    private static function fetch_url($url) {
        if (!$url) return '';
        $resp = wp_remote_get($url, array(
            'timeout' => 8,
            'sslverify' => false,
            'redirection' => 3,
            'user-agent' => 'AEO-Orchestra-VerifyLive/3.35.77',
            'headers' => array('Accept' => 'text/html,text/plain,application/xml,*/*'),
        ));
        if (is_wp_error($resp)) return '';
        $code = wp_remote_retrieve_response_code($resp);
        if ($code >= 400) return '';
        return (string) wp_remote_retrieve_body($resp);
    }

    private static function base_url($url) {
        $parts = wp_parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) return rtrim((string) home_url('/'), '/');
        $scheme = isset($parts['scheme']) ? $parts['scheme'] : 'https';
        return $scheme . '://' . $parts['host'];
    }

    private static function build_context($url, $head_html, $jsonld_blocks, $llms_txt, $sitemap_xml) {
        // Trim head to first 8K to keep prompt small
        $head_excerpt = substr($head_html, 0, 8000);
        $llms_excerpt = substr($llms_txt, 0, 6000);
        // Quickly detect schema types from JSON-LD blocks
        $schema_types = array();
        foreach ($jsonld_blocks as $blk) {
            $j = json_decode($blk, true);
            if (!is_array($j)) continue;
            $candidates = array();
            if (isset($j['@graph']) && is_array($j['@graph'])) {
                foreach ($j['@graph'] as $node) {
                    if (is_array($node) && !empty($node['@type'])) $candidates[] = $node['@type'];
                }
            } elseif (!empty($j['@type'])) {
                $candidates[] = $j['@type'];
            }
            foreach ($candidates as $c) {
                if (is_array($c)) foreach ($c as $cc) $schema_types[] = (string) $cc;
                else $schema_types[] = (string) $c;
            }
        }
        $schema_types = array_values(array_unique($schema_types));

        return array(
            'url'           => $url,
            'head_excerpt'  => $head_excerpt,
            'llms_excerpt'  => $llms_excerpt,
            'has_sitemap'   => $sitemap_xml !== '',
            'schema_types'  => $schema_types,
            'jsonld_count'  => count($jsonld_blocks),
        );
    }


    /**
     * 3.35.82: Assemble full context payload from identity_profile + brand_voice + homepage.
     * Plugin is single source of context (backend cannot reach WP MySQL Brand Voice).
     */
    private static function build_full_context($url, $head_html) {
        return array(
            'identity_profile' => self::fetch_identity_profile_inline(),
            'brand_voice'      => self::fetch_brand_voice_active(),
            'homepage_context' => self::extract_homepage_context($url, $head_html),
        );
    }

    /**
     * Fetch active Brand Voice profile via class-brand-voice.php helper.
     * Returns null if no active profile or class missing.
     */
    private static function fetch_brand_voice_active() {
        if (!class_exists('SEO_AEO_Brand_Voice')) return null;
        $profile = SEO_AEO_Brand_Voice::get_active_profile();
        if (!is_array($profile)) return null;
        // Cap arrays to keep token budget reasonable
        $vocab = isset($profile['distinctive_vocabulary']) && is_array($profile['distinctive_vocabulary'])
                 ? array_slice($profile['distinctive_vocabulary'], 0, 15) : array();
        $avoid = isset($profile['avoid_words']) && is_array($profile['avoid_words'])
                 ? array_slice($profile['avoid_words'], 0, 8) : array();
        return array(
            'name'                   => isset($profile['_name']) ? (string) $profile['_name'] : '',
            'tone'                   => isset($profile['tone']) ? (string) $profile['tone'] : '',
            'tone_description'       => isset($profile['tone_description']) ? (string) $profile['tone_description'] : '',
            'audience'               => isset($profile['audience']) ? (string) $profile['audience'] : '',
            'distinctive_vocabulary' => $vocab,
            'avoid_words'            => $avoid,
            'summary_for_prompt'     => isset($profile['summary_for_prompt']) ? (string) $profile['summary_for_prompt'] : '',
        );
    }

    /**
     * Fetch identity profile via API client (transient-cached 1h by default).
     */
    private static function fetch_identity_profile_inline() {
        // Reuse class-llms-txt cache key if available
        $cache_key = 'seo_aeo_identity_profile';
        $profile = get_transient($cache_key);
        if (!is_array($profile)) {
            global $seo_aeo_api;
            if (!$seo_aeo_api && class_exists('SEO_AEO_API_Client')) {
                $seo_aeo_api = new SEO_AEO_API_Client();
            }
            if ($seo_aeo_api && method_exists($seo_aeo_api, 'get_identity_profile')) {
                $resp = $seo_aeo_api->get_identity_profile();
                if (is_array($resp)) {
                    $profile = $resp;
                    set_transient($cache_key, $profile, HOUR_IN_SECONDS);
                }
            }
        }
        if (!is_array($profile)) return null;

        // Extract Free fields only (paid fields out of scope for .82)
        $diff = isset($profile['differentiators']) && is_array($profile['differentiators'])
                ? array_slice($profile['differentiators'], 0, 5) : array();
        $terr = isset($profile['territories']) && is_array($profile['territories'])
                ? array_slice($profile['territories'], 0, 8) : array();
        $uc = isset($profile['use_cases']) && is_array($profile['use_cases'])
              ? array_slice($profile['use_cases'], 0, 5) : array();

        return array(
            'business_name'        => isset($profile['business_name']) ? (string) $profile['business_name'] : '',
            'business_description' => isset($profile['business_description']) ? (string) $profile['business_description'] : '',
            'industry'             => isset($profile['industry']) ? (string) $profile['industry'] : '',
            'about_strategic'      => isset($profile['about_strategic']) ? (string) $profile['about_strategic'] : '',
            'differentiators'      => $diff,
            'territories'          => $terr,
            'use_cases'            => $uc,
        );
    }

    /**
     * Extract title + meta_description + first paragraphs from homepage HTML.
     * Cached 1h via transient seo_aeo_vl_homepage_ctx_<md5(url)>.
     * Bypass cache if force=true.
     */
    public static function extract_homepage_context($url, $head_html, $force = false) {
        $cache_key = 'seo_aeo_vl_hp_' . md5((string) $url);
        if (!$force) {
            $cached = get_transient($cache_key);
            if (is_array($cached)) return $cached;
        }

        // If head_html not provided, fetch live (force-refresh path)
        if (!$head_html) {
            $head_html = self::fetch_url($url);
        }

        $title = '';
        $meta_desc = '';
        $first_paragraphs = '';

        if ($head_html) {
            // Extract <title>
            if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $head_html, $m)) {
                $title = trim(html_entity_decode(wp_strip_all_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $title = preg_replace('/\s+/u', ' ', $title);
                if (function_exists('mb_substr')) $title = mb_substr($title, 0, 200);
                else $title = substr($title, 0, 200);
            }
            // Extract meta description
            if (preg_match('/<meta\s+name=["\']description["\'][^>]*content=["\'](.*?)["\']/is', $head_html, $m)) {
                $meta_desc = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            } elseif (preg_match('/<meta\s+content=["\'](.*?)["\'][^>]*name=["\']description["\']/is', $head_html, $m)) {
                $meta_desc = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
            // Extract first paragraphs from body — strip script/style/nav/header/footer first
            $body = $head_html;
            // Crude body extraction
            if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $head_html, $m)) {
                $body = $m[1];
            }
            // Remove noise
            $body = preg_replace('/<(script|style|nav|header|footer|noscript|aside)[^>]*>.*?<\/\1>/is', ' ', $body);
            $body = preg_replace('/<!--.*?-->/s', ' ', $body);
            // Extract <p> tags
            if (preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $body, $matches)) {
                $paras = array();
                foreach ($matches[1] as $p) {
                    $clean = trim(html_entity_decode(wp_strip_all_tags($p), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $clean = preg_replace('/\s+/u', ' ', $clean);
                    if (strlen($clean) > 30) $paras[] = $clean;
                    if (count($paras) >= 5) break;
                }
                $first_paragraphs = implode("\n\n", $paras);
            }
            // Cap to 800 chars
            if (function_exists('mb_substr')) $first_paragraphs = mb_substr($first_paragraphs, 0, 800);
            else $first_paragraphs = substr($first_paragraphs, 0, 800);
        }

        $ctx = array(
            'url'              => (string) $url,
            'title'            => $title,
            'meta_description' => (string) $meta_desc,
            'first_paragraphs' => (string) $first_paragraphs,
            'fetched_at'       => current_time('mysql', true),
            'fetch_success'    => ($title || $meta_desc || $first_paragraphs) ? true : false,
            'chars_used'       => strlen($title) + strlen($meta_desc) + strlen($first_paragraphs),
        );

        set_transient($cache_key, $ctx, HOUR_IN_SECONDS);
        return $ctx;
    }

    /**
     * Public wrapper for AJAX preview endpoint.
     */
    public static function get_preview_payload($url, $force_refresh_homepage = false) {
        $head_html = $force_refresh_homepage ? self::fetch_url($url) : '';
        // For non-force, we still need head_html if cache miss; let extract handle it.
        $ctx = self::extract_homepage_context($url, $head_html, $force_refresh_homepage);

        $identity = self::fetch_identity_profile_inline();
        $brand_voice = self::fetch_brand_voice_active();

        // Compute populated counter
        $identity_fields = array('business_name','business_description','industry','about_strategic','differentiators','territories','use_cases');
        $populated = 0;
        $missing = array();
        if (is_array($identity)) {
            foreach ($identity_fields as $f) {
                $v = isset($identity[$f]) ? $identity[$f] : null;
                if (is_string($v) && trim($v) !== '') $populated++;
                elseif (is_array($v) && count($v) > 0) $populated++;
                else $missing[] = $f;
            }
        } else {
            $missing = $identity_fields;
        }

        return array(
            'identity_profile' => $identity,
            'brand_voice'      => $brand_voice,
            'homepage_context' => $ctx,
            'context_stats'    => array(
                'identity_populated_fields' => $populated,
                'identity_total_fields'     => count($identity_fields),
                'identity_missing_fields'   => $missing,
                'brand_voice_active'        => is_array($brand_voice) && !empty($brand_voice['summary_for_prompt']),
                'homepage_chars_used'       => isset($ctx['chars_used']) ? (int) $ctx['chars_used'] : 0,
                'homepage_fetch_success'    => isset($ctx['fetch_success']) ? (bool) $ctx['fetch_success'] : false,
            ),
        );
    }

    /**
     * Call AI provider via router. Premium++ uses Anthropic Sonnet with web_search tool.
     * Standard uses backend dispatch (gracefully fails if endpoint not yet wired).
     */
    private static function call_ai_with_grounding($context, $tier) {
        if (!class_exists('SEO_AEO_AI_Provider_Router')) {
            return array('ok' => false, 'error' => 'router_missing');
        }

        $system = "Sei un AEO auditor. Verifica cosa gli AI assistants (ChatGPT, Claude, Perplexity) capiscono di questo sito. Rispondi in JSON valido con questa shape:\\n"
            . "{\"insights\": [{\"category\": \"identity|schema|content|brand_disambiguation|hreflang|llms\", \"finding\": \"...\"}], \"warnings\": [{\"category\": \"...\", \"finding\": \"...\"}], \"score\": 0-100, \"summary\": \"...\"}\\n\\n"
            . "Categorie insight comuni: identity (chi è l\\'azienda), schema (schema.org graph), content (qualità contenuti), brand_disambiguation (es. brand omonimi paesi diversi), hreflang (multilingua), llms (presenza llms.txt curato).\\n"
            . "Italiano per i finding. Massimo 8 insight, 4 warnings.";

        $user = "URL verificata: " . $context['url'] . "\\n\\n"
            . "JSON-LD trovato: " . $context['jsonld_count'] . " blocchi · types: " . (empty($context['schema_types']) ? '(nessuno)' : implode(', ', $context['schema_types'])) . "\\n"
            . "/sitemap.xml: " . ($context['has_sitemap'] ? 'presente' : 'NON presente') . "\\n"
            . "/llms.txt: " . ($context['llms_excerpt'] ? 'presente · primo blocco:\\n' . substr($context['llms_excerpt'], 0, 2000) : 'NON presente') . "\\n\\n"
            . "Estratto <head>:\\n" . $context['head_excerpt'] . "\\n\\n"
            . "Output: solo JSON valido come da schema sopra.";

        // 3.35.82: enrich payload with full context (plugin = single source)
        $full_context = self::build_full_context($context['url'], $context['head_excerpt']);
        $payload = array(
            'system'      => $system,
            'messages'    => array(array('role' => 'user', 'content' => $user)),
            'max_tokens'  => 2000,
            'temperature' => 0.3,
            // === .82 context injection ===
            'business_name'    => isset($full_context['identity_profile']['business_name']) ? $full_context['identity_profile']['business_name'] : '',
            'domain'           => wp_parse_url($context['url'], PHP_URL_HOST) ?: '',
            'url'              => $context['url'],
            'identity_profile' => $full_context['identity_profile'],
            'brand_voice'      => $full_context['brand_voice'],
            'homepage_context' => $full_context['homepage_context'],
        );

        $opts = array(
            'tier'    => $tier,
            'user_id' => get_current_user_id(),
        );

        return SEO_AEO_AI_Provider_Router::call_for_task('verify-live', $payload, $opts);
    }

    private static function parse_ai_findings($text) {
        $text = trim((string) $text);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```\s*$/', '', $text);
        $j = json_decode($text, true);
        $insights = array(); $warnings = array(); $score = null;
        if (is_array($j)) {
            if (isset($j['insights']) && is_array($j['insights'])) {
                foreach ($j['insights'] as $ins) {
                    if (!is_array($ins)) continue;
                    $insights[] = array(
                        'category' => isset($ins['category']) ? sanitize_key($ins['category']) : 'general',
                        'finding'  => isset($ins['finding'])  ? (string) $ins['finding']  : '',
                    );
                }
            }
            if (isset($j['warnings']) && is_array($j['warnings'])) {
                foreach ($j['warnings'] as $w) {
                    if (!is_array($w)) continue;
                    $warnings[] = array(
                        'category' => isset($w['category']) ? sanitize_key($w['category']) : 'general',
                        'finding'  => isset($w['finding'])  ? (string) $w['finding']  : '',
                    );
                }
            }
            if (isset($j['score'])) $score = (int) $j['score'];
        }
        return array('insights' => $insights, 'warnings' => $warnings, 'score' => $score);
    }

    private static function compose_summary($passed, $warnings, $errors, $score, $tier_used) {
        // 3.35.81.1: handle null score (error state) — Patch 6
        if ($score === null) {
            return '⚠ Errore analisi · 0 insight · 0 warning · ' . $errors . ' error · provider: ' . $tier_used;
        }
        $emoji = $score >= 80 ? '✓' : ($score >= 50 ? '⚠' : '✗');
        return $emoji . ' Score ' . $score . '/100 · ' . $passed . ' insight · ' . $warnings . ' warning · ' . $errors . ' error · provider: ' . $tier_used;
    }
    /**
     * 3.35.81.1: Refund credits via backend on verify-live error_state (Patch 6).
     */
    private static function backend_refund($license_key, $amount, $reason) {
        if ($amount <= 0) return array('success' => false, 'message' => 'amount<=0');
        if (!defined('SEO_AEO_API_URL')) return array('success' => false, 'message' => 'SEO_AEO_API_URL not defined');
        $url = SEO_AEO_API_URL . '/api/wallet/refund';
        $resp = wp_remote_post($url, array(
            'timeout' => 10,
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $license_key,
            ),
            'body' => wp_json_encode(array(
                'license_key' => $license_key,
                'amount'      => (int) $amount,
                'source'      => 'verify-live-auto-refund',
                'note'        => 'verify-live ' . $reason,
            )),
        ));
        if (is_wp_error($resp)) {
            orch_debug_log('[seo-aeo-orchestra] backend_refund WP_Error: ' . $resp->get_error_message());
            return array('success' => false, 'message' => $resp->get_error_message());
        }
        $code = wp_remote_retrieve_response_code($resp);
        $body = json_decode(wp_remote_retrieve_body($resp), true);
        if ($code >= 200 && $code < 300 && is_array($body) && !empty($body['success'])) {
            return array('success' => true, 'amount' => (int) $amount);
        }
        orch_debug_log('[seo-aeo-orchestra] backend_refund non-200: code=' . $code);
        return array('success' => false, 'message' => 'backend non-2xx');
    }

}

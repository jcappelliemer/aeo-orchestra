<?php
/**
 * SEO_AEO_Preview_Cache — v3.42.3 launch-blocker P0
 *
 * WP transient cache for /preview-action responses. Solves the wallet anomaly
 * where back-to-back identical preview clicks silently charge credits twice
 * (the v3.42.2.1 Chrome MCP walkthrough caught 105cr drift in 5 minutes).
 *
 * Architecture decision: WP transient API (not Redis). Per-WP-site isolated
 * naturally, no new infrastructure, idiomatic WordPress pattern. Future
 * Redis upgrade possible if aggregated metrics ever needed (v3.50+).
 *
 * Key shape: aeo_pv_{sanitize_key(action_type)}_{post_id}_{content_hash}
 *   where content_hash = first 16 chars md5(post_content + post_title + relevant_meta)
 *
 * TTL: 1 hour (HOUR_IN_SECONDS).
 *
 * Invalidation: save_post + updated_post_meta delete all transients for the
 * given post_id (across action_types) so any content edit forces a fresh
 * preview on next click. Plugin uninstall hook (registered in main file)
 * cleans up all aeo_pv_* transients to avoid orphans.
 *
 * Metrics: do_action('seo_aeo_preview_cache_hit', $action_type, $post_id)
 * and 'seo_aeo_preview_cache_miss' for observability. Optional Sentry log
 * integration via seo_aeo_debug_log() if available.
 *
 * @package SEO_AEO_Orchestra
 * @since 3.42.3
 */

if (!defined('ABSPATH')) { exit; }

class SEO_AEO_Preview_Cache {

    const TRANSIENT_PREFIX = 'aeo_pv_';
    const TTL = HOUR_IN_SECONDS; // 3600s

    /**
     * Meta keys included in the content_hash computation. Editing any of
     * these via WP admin invalidates the cache (because the hash changes
     * AND save_post fires).
     *
     * v3.42.3.3 — ALLOW-LIST design (replaces v3.42.3.x deny-list approach).
     * Only meta keys explicitly listed here participate in the content_hash
     * + the invalidation hook. Third-party plugin meta (Polylang _pll_*,
     * WPML _wpml_*, analytics, etc.) is automatically EXCLUDED.
     *
     * Maintenance: when adding a new agent that reads OR writes a new meta
     * key the preview output depends on, add the key here AND to TRACKED_KEYS.
     */
    const ALLOWED_META_KEYS = array(
        '_yoast_wpseo_title',
        '_yoast_wpseo_metadesc',
        '_yoast_wpseo_focuskw',
        'rank_math_title',
        'rank_math_description',
        'rank_math_focus_keyword',
        '_seo_aeo_meta_title',
        '_seo_aeo_meta_description',
        '_seo_aeo_focus_keyword',
        '_seo_aeo_custom_schema_html',
        'seo_aeo_schema_jsonld',
        'seo_aeo_faq_section',
        'seo_aeo_identity_profile',
        'seo_aeo_brand_voice',
    );

    /**
     * v3.42.3.3 — TRACKED_KEYS list for invalidation. When ANY of these
     * meta keys is changed (via updated/added/deleted_post_meta hooks),
     * the cache is invalidated. Writes to non-tracked keys (e.g. Polylang
     * _pll_translations) are IGNORED — they don't affect what preview
     * produces, so the cached entry remains valid.
     *
     * For simplicity, TRACKED_KEYS = ALLOWED_META_KEYS. They can diverge
     * if a key influences preview WITHOUT being in the hash (rare).
     */
    const TRACKED_KEYS = self::ALLOWED_META_KEYS;

    /**
     * Backward-compatible alias for any external code referencing the
     * old constant name.
     * @deprecated since 3.42.3.3, use ALLOWED_META_KEYS.
     */
    const RELEVANT_META_KEYS = self::ALLOWED_META_KEYS;

    /**
     * Compute the content-fingerprint hash for a given post. Returns null
     * if post not found.
     *
     * @param int $post_id
     * @return string|null 16-char hex hash, or null if post missing.
     */
    public static function make_content_hash($post_id) {
        $post_id = (int) $post_id;
        if ($post_id <= 0) return null;
        $post = get_post($post_id);
        if (!$post) return null;
        // 3.42.3.3 — content_hash uses post_content + post_title + post_excerpt
        // + an EXPLICIT allow-list of meta keys (ALLOWED_META_KEYS). Third-
        // party plugin meta (Polylang _pll_*, WPML, analytics) is auto-
        // excluded because allow-list is positive enumeration. This makes
        // the cache key intrinsically resilient to ANY plugin that writes
        // meta we don't know about — no maintenance burden when new plugins
        // are installed.
        //
        // The v3.42.3.2 minimal hash (body + title only) was correct but too
        // narrow: real user edits to Yoast title or Orchestra schema_jsonld
        // meta SHOULD invalidate the cache because preview output depends
        // on those keys.
        $all_meta = get_post_meta($post_id);
        $allowed = array_intersect_key(
            is_array($all_meta) ? $all_meta : array(),
            array_flip(self::ALLOWED_META_KEYS)
        );
        ksort($allowed); // deterministic across runs
        $payload = implode('|', array(
            (string) $post->post_content,
            (string) $post->post_title,
            (string) $post->post_excerpt,
            wp_json_encode($allowed),
        ));
        return substr(md5($payload), 0, 16);
    }

    /**
     * Build the transient key. Action_type + post_id + content_hash gives
     * cardinality (action × post × content-version). Sanitized to fit
     * WP transient name rules (max 172 chars).
     */
    public static function make_key($action_type, $post_id, $content_hash) {
        $raw = $action_type . '_' . (int) $post_id . '_' . $content_hash;
        return self::TRANSIENT_PREFIX . sanitize_key($raw);
    }

    /**
     * Get the cached preview for a (action_type, post_id) pair. Recomputes
     * the current content_hash internally so an edited post returns null
     * (cache miss) automatically — even before save_post invalidation fires.
     *
     * @return array|null cached preview payload, or null on miss / error.
     */
    public static function get($action_type, $post_id) {
        if (empty($action_type) || (int) $post_id <= 0) return null;
        $hash = self::make_content_hash($post_id);
        if ($hash === null) return null;
        $key = self::make_key($action_type, $post_id, $hash);
        $cached = get_transient($key);
        if ($cached === false) {
            do_action('seo_aeo_preview_cache_miss', $action_type, $post_id);
            return null;
        }
        do_action('seo_aeo_preview_cache_hit', $action_type, $post_id);
        return $cached;
    }

    /**
     * Persist preview payload to cache. TTL 1 hour. Only call after a
     * successful AI response (skip on error payloads).
     *
     * @return bool true on success.
     */
    public static function set($action_type, $post_id, $preview) {
        if (empty($action_type) || (int) $post_id <= 0) return false;
        if (!is_array($preview)) return false;
        $hash = self::make_content_hash($post_id);
        if ($hash === null) return false;
        $key = self::make_key($action_type, $post_id, $hash);
        return (bool) set_transient($key, $preview, self::TTL);
    }

    /**
     * Invalidate all cached previews for a post (across all action_types).
     * Called on save_post + updated_post_meta. Conservative: rather delete
     * too much than serve stale.
     *
     * Implementation: SELECT matching transient option_names first, then
     * delete_transient() each (which clears BOTH the DB row AND the
     * in-memory wp_cache layer that direct $wpdb->query DELETE misses —
     * smoke test caught this exact bug).
     */
    public static function invalidate_post($post_id) {
        $post_id = (int) $post_id;
        if ($post_id <= 0) return 0;
        // 3.42.3.2 — suppress invalidation during the preview AJAX path.
        // ajax_preview_action writes post_meta as part of its preview
        // generation (schema persist, meta_title/desc preview save).
        // Those writes fire updated_post_meta + save_post which call this
        // method in the SAME request — clearing the cache the wrapper
        // is about to set. The request-scoped guard skips invalidation
        // during that path. Real user edits (outside preview) still
        // invalidate normally because the guard isn't set.
        if (!empty($GLOBALS['_seo_aeo_in_preview'])) {
            return 0;
        }
        global $wpdb;
        $like = $wpdb->esc_like(self::TRANSIENT_PREFIX) . '%_' . $post_id . '_%';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $like
            )
        );
        $count = 0;
        if (is_array($rows)) {
            foreach ($rows as $opt_name) {
                $transient_key = preg_replace('/^_transient_/', '', (string) $opt_name);
                if ($transient_key) {
                    delete_transient($transient_key);
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Purge ALL preview cache entries (used by uninstall hook + admin
     * "clear cache" button). Returns count of transients deleted. Same
     * SELECT-then-delete_transient pattern as invalidate_post().
     */
    public static function purge_all() {
        global $wpdb;
        $like = $wpdb->esc_like(self::TRANSIENT_PREFIX) . '%';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $like
            )
        );
        $count = 0;
        if (is_array($rows)) {
            foreach ($rows as $opt_name) {
                $transient_key = preg_replace('/^_transient_/', '', (string) $opt_name);
                if ($transient_key) {
                    delete_transient($transient_key);
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Best-effort observability log on cache hit/miss. Hook this to
     * 'seo_aeo_preview_cache_hit' / '..._miss' actions so Sentry sees
     * the cardinality.
     */
    public static function log_event($event, $action_type, $post_id) {
        // v3.42.3.3 — also error_log unconditionally so production diagnostics
        // are available without WP_DEBUG_LOG. Format includes content_hash
        // as correlation ID for cross-call cache miss anomaly investigation.
        $hash = self::make_content_hash((int) $post_id) ?: 'null';
        $line = sprintf(
            '[AEO preview_cache] event=%s action_type=%s post_id=%d hash=%s',
            (string) $event,
            (string) $action_type,
            (int) $post_id,
            $hash
        );
        if (function_exists('seo_aeo_debug_log')) {
            seo_aeo_debug_log($line);
        }
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log($line);
    }

    /**
     * Bootstrap: register the invalidation hooks. Called once from main
     * plugin file (no-op safe to call multiple times).
     */
    public static function register_hooks() {
        static $registered = false;
        if ($registered) return;
        $registered = true;

        // Invalidate on real post update (title/content/status). save_post
        // fires on user edits via WP admin OR wp_update_post calls. The
        // v3.42.3.2 in-preview guard suppresses invalidation during the
        // preview AJAX path itself.
        add_action('save_post', array(__CLASS__, 'invalidate_post'), 10, 1);

        // 3.42.3.3 — tracked-keys filter on meta hooks. Only invalidate when
        // ONE OF the keys in TRACKED_KEYS changes. Polylang's _pll_* writes,
        // WPML's _wpml_*, analytics tokens, etc. don't invalidate Orchestra's
        // cache because they don't change what preview produces.
        $meta_filter = function($meta_id, $post_id, $meta_key) {
            if (!in_array($meta_key, self::TRACKED_KEYS, true)) return;
            self::invalidate_post($post_id);
        };
        add_action('updated_post_meta', $meta_filter, 10, 3);
        add_action('added_post_meta',   $meta_filter, 10, 3);
        add_action('deleted_post_meta', $meta_filter, 10, 3);

        // Wire metric observability into Sentry log if available.
        add_action('seo_aeo_preview_cache_hit', function($action_type, $post_id) {
            self::log_event('hit', $action_type, $post_id);
        }, 10, 2);
        add_action('seo_aeo_preview_cache_miss', function($action_type, $post_id) {
            self::log_event('miss', $action_type, $post_id);
        }, 10, 2);
    }
}

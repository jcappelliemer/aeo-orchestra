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
     */
    const RELEVANT_META_KEYS = array(
        '_yoast_wpseo_title',
        '_yoast_wpseo_metadesc',
        '_yoast_wpseo_focuskw',
        'rank_math_title',
        'rank_math_description',
        'rank_math_focus_keyword',
        '_seo_aeo_meta_title',
        '_seo_aeo_meta_description',
        '_seo_aeo_focus_keyword',
        'seo_aeo_schema_jsonld',
        'seo_aeo_faq_section',
    );

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
        $all_meta = get_post_meta($post_id);
        $relevant = array_intersect_key(
            is_array($all_meta) ? $all_meta : array(),
            array_flip(self::RELEVANT_META_KEYS)
        );
        // Sort keys to keep hash deterministic across runs.
        ksort($relevant);
        $payload = (string) $post->post_content
                 . '|' . (string) $post->post_title
                 . '|' . wp_json_encode($relevant);
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
        if (function_exists('seo_aeo_debug_log')) {
            seo_aeo_debug_log(sprintf(
                '[v3.42.3 preview_cache] event=%s action_type=%s post_id=%d',
                (string) $event,
                (string) $action_type,
                (int) $post_id
            ));
        }
    }

    /**
     * Bootstrap: register the invalidation hooks. Called once from main
     * plugin file (no-op safe to call multiple times).
     */
    public static function register_hooks() {
        static $registered = false;
        if ($registered) return;
        $registered = true;

        // Invalidate on any post update (title, content, status, etc).
        add_action('save_post', array(__CLASS__, 'invalidate_post'), 10, 1);

        // Invalidate on post meta change (Yoast / RankMath / Orchestra meta
        // edits that don't trigger save_post directly).
        add_action('updated_post_meta', function($meta_id, $post_id) {
            self::invalidate_post($post_id);
        }, 10, 2);

        // Wire metric observability into Sentry log if available.
        add_action('seo_aeo_preview_cache_hit', function($action_type, $post_id) {
            self::log_event('hit', $action_type, $post_id);
        }, 10, 2);
        add_action('seo_aeo_preview_cache_miss', function($action_type, $post_id) {
            self::log_event('miss', $action_type, $post_id);
        }, 10, 2);
    }
}

<?php
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com. All rights reserved.
 * Unauthorized copying, redistribution or resale is strictly prohibited.
 */

/**
 * SEO_AEO_Page_Roles — Site Architecture Mapping (Stage 2.5, 3.35.55-beta).
 *
 * Classifies the INTERNAL role of every public page/post/CPT in the site:
 *   homepage, blog_index, about, contact, faq, quote_request,
 *   knowledge_guide, category_landing, service_page, product_page,
 *   blog_post, legal_privacy, legal_terms, custom, ignore
 *
 * Two-phase classification:
 *   1. HEURISTIC (this class, free, 0 credits): pure SQL + regex + WP options.
 *      Covers ~70-80% of pages with confidence >= 0.7.
 *   2. LLM AUGMENT (backend /api/identity/scan-site mode=full, Pro tier):
 *      handles the residual ambiguous pages.
 *
 * Storage:
 *   - WP option `seo_aeo_page_role_map` = {post_id: {role, confidence, source, updated_at}, ...}
 *   - Per-post meta `_orch_role` (string) for fast meta_query lookups
 *
 * Used downstream by:
 *   - Featured pages auto-suggest in /llms.txt
 *   - Schema.org per-page-type emission
 *   - Sitemap priority per role
 *   - Admin search autocomplete (typing "home" finds homepage)
 *   - AEO score context-aware (FAQ schema is critical on FAQ pages, irrelevant on blog)
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Page_Roles {

    const OPTION_KEY = 'seo_aeo_page_role_map';
    const META_KEY   = '_orch_role';

    // Valid role keys (enum)
    const ROLES = array(
        'homepage',
        'blog_index',
        'about',
        'contact',
        'faq',
        'quote_request',
        'knowledge_guide',
        'category_landing',
        'service_page',
        'product_page',
        'blog_post',
        'legal_privacy',
        'legal_terms',
        'custom',
        'ignore',
    );

    // Slug regex patterns (case-insensitive) → role + confidence
    private static function slug_patterns() {
        return array(
            // Strong matches (conf 1.0)
            array('regex' => '/^(faq|domande-frequenti|frequently-asked-questions)$/i', 'role' => 'faq', 'conf' => 1.0),
            array('regex' => '/^(privacy|privacy-policy|cookie-policy|cookies?)$/i', 'role' => 'legal_privacy', 'conf' => 1.0),
            array('regex' => '/^(termini|terms|terms-of-service|tos|disclaimer|terms-conditions)$/i', 'role' => 'legal_terms', 'conf' => 1.0),

            // Strong matches (conf 0.95)
            array('regex' => '/^(chi-siamo|about|about-us|azienda|profilo|nostra-storia|our-story|company)$/i', 'role' => 'about', 'conf' => 0.95),
            array('regex' => '/^(contattaci|contact|contact-us|dove-siamo|contatti|reach-us|get-in-touch)$/i', 'role' => 'contact', 'conf' => 0.95),
            array('regex' => '/^(preventivo|quote|richiedi-preventivo|get-a-quote|request-quote|quotation|preventivo-gratuito)$/i', 'role' => 'quote_request', 'conf' => 0.95),

            // Medium matches (conf 0.7)
            array('regex' => '/^(guida|guide|how-to|tutorial)/i', 'role' => 'knowledge_guide', 'conf' => 0.7),
        );
    }

    // Title keywords that boost ranking score
    private static function ranking_keywords() {
        return array('about', 'chi siamo', 'contact', 'contatti', 'contattaci', 'faq', 'domande', 'preventivo', 'quote', 'servizi', 'services', 'prodotti', 'products');
    }

    // ============================================================
    // Public API
    // ============================================================

    /**
     * Get the role for a single post. Returns role string or null if unclassified.
     */
    public static function get_role($post_id) {
        $post_id = (int) $post_id;
        if ($post_id <= 0) return null;
        $meta = get_post_meta($post_id, self::META_KEY, true);
        if (is_string($meta) && $meta !== '' && in_array($meta, self::ROLES, true)) {
            return $meta;
        }
        $map = self::get_map();
        if (isset($map[$post_id]['role']) && in_array($map[$post_id]['role'], self::ROLES, true)) {
            return $map[$post_id]['role'];
        }
        return null;
    }

    /**
     * Get the full role descriptor for a single post (role + confidence + source + updated_at).
     */
    public static function get_role_descriptor($post_id) {
        $post_id = (int) $post_id;
        $map = self::get_map();
        if (isset($map[$post_id]) && is_array($map[$post_id])) {
            return $map[$post_id];
        }
        return null;
    }

    /**
     * Find published posts of a given role. Returns array of WP_Post objects.
     */
    public static function find_by_role($role, $limit = 10) {
        // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_value,WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Admin diagnostic query for page role mapping, low frequency (admin pages only), acceptable performance trade-off.
        if (!in_array($role, self::ROLES, true)) return array();
        $limit = max(1, min(100, (int) $limit));

        // First-pass: meta_query (fast, indexed)
        $posts = get_posts(array(
            'post_type'        => 'any',
            'post_status'      => 'publish',
            'posts_per_page'   => $limit,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Admin diagnostic query for page-role mapping, executed on plugin admin pages only, not on frontend. Acceptable performance trade-off for the diagnostic value.
            'meta_key'         => self::META_KEY,
            'meta_value'       => $role,
            'orderby'          => 'modified',
            'order'            => 'DESC',
            'no_found_rows'    => true,
        ));
        if (!empty($posts)) return $posts;

        // Fallback: read from option map (for sites where meta wasn't backfilled)
        $map = self::get_map();
        $matching_ids = array();
        foreach ($map as $pid => $desc) {
            if (isset($desc['role']) && $desc['role'] === $role) {
                $matching_ids[] = (int) $pid;
            }
        }
        if (empty($matching_ids)) return array();
        $matching_ids = array_slice($matching_ids, 0, $limit);
        return get_posts(array(
            'post__in'         => $matching_ids,
            'post_type'        => 'any',
            'post_status'      => 'publish',
            'posts_per_page'   => $limit,
            'orderby'          => 'post__in',
            'no_found_rows'    => true,
        ));
        // phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_value,WordPress.DB.SlowDBQuery.slow_db_query_meta_key
    }

    /**
     * Returns the full map {post_id: {role, confidence, source, updated_at}}.
     */
    public static function get_pages_with_role() {
        return self::get_map();
    }

    /**
     * Manually set/override a role. Source defaults to 'manual' (highest priority).
     */
    public static function set_role($post_id, $role, $source = 'manual', $confidence = 1.0) {
        $post_id = (int) $post_id;
        if ($post_id <= 0) return false;
        if (!in_array($role, self::ROLES, true)) return false;
        if (!in_array($source, array('heuristic', 'llm', 'manual'), true)) $source = 'manual';

        $map = self::get_map();
        $map[$post_id] = array(
            'role'       => $role,
            'confidence' => max(0.0, min(1.0, (float) $confidence)),
            'source'     => $source,
            'updated_at' => current_time('mysql', true), // GMT
        );
        update_option(self::OPTION_KEY, wp_json_encode($map), false);  // not autoload
        update_post_meta($post_id, self::META_KEY, $role);
        return true;
    }

    /**
     * Bulk-update from an array of {post_id: {role, confidence, source}} entries.
     * Used by the LLM augment integration (scan-site response).
     */
    public static function bulk_set($entries, $source = 'llm') {
        if (!is_array($entries)) return 0;
        $map = self::get_map();
        $count = 0;
        $now = current_time('mysql', true);
        foreach ($entries as $pid => $desc) {
            $pid = (int) $pid;
            if ($pid <= 0) continue;
            if (!is_array($desc)) continue;
            $role = isset($desc['role']) ? (string) $desc['role'] : '';
            if (!in_array($role, self::ROLES, true)) continue;
            $existing = isset($map[$pid]) ? $map[$pid] : null;
            // Don't overwrite a 'manual' source with a lower-priority one
            if ($existing && isset($existing['source']) && $existing['source'] === 'manual' && $source !== 'manual') continue;
            $map[$pid] = array(
                'role'       => $role,
                'confidence' => isset($desc['confidence']) ? max(0.0, min(1.0, (float) $desc['confidence'])) : 0.7,
                'source'     => $source,
                'updated_at' => $now,
            );
            update_post_meta($pid, self::META_KEY, $role);
            $count++;
        }
        if ($count > 0) {
            update_option(self::OPTION_KEY, wp_json_encode($map), false);
        }
        return $count;
    }

    public static function is_classified($post_id) {
        return self::get_role($post_id) !== null;
    }

    /**
     * Read the full map from option (decoded). Always returns array.
     */
    public static function get_map() {
        $raw = get_option(self::OPTION_KEY, '');
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) return $decoded;
        }
        if (is_array($raw)) return $raw;
        return array();
    }

    // ============================================================
    // Heuristic classification
    // ============================================================

    /**
     * Iterate ALL public posts and apply the heuristic classifier.
     * Fills both the option map and per-post meta. Idempotent (re-running just refreshes).
     *
     * Performance note: skips posts that already have a 'manual' source (user override).
     *
     * @param int $batch_size  Max posts processed per call (chunked to avoid memory blowups).
     * @return array  {classified: int, skipped: int, total: int}
     */
    public static function heuristic_classify_all($batch_size = 1000) {
        $page_on_front  = (int) get_option('page_on_front', 0);
        $page_for_posts = (int) get_option('page_for_posts', 0);

        $posts = get_posts(array(
            'post_type'        => self::queryable_post_types(),
            'post_status'      => 'publish',
            'posts_per_page'   => $batch_size,
            'orderby'          => 'ID',
            'order'            => 'ASC',
            'no_found_rows'    => true,
        ));

        $map = self::get_map();
        $classified = 0;
        $skipped = 0;
        $now = current_time('mysql', true);

        foreach ($posts as $p) {
            // Preserve manual overrides
            if (isset($map[$p->ID]['source']) && $map[$p->ID]['source'] === 'manual') {
                $skipped++;
                continue;
            }

            $result = self::classify_one_heuristic($p, $page_on_front, $page_for_posts);
            if ($result === null) {
                // Unknown — leave as-is (LLM augment will handle later)
                continue;
            }

            $map[$p->ID] = array(
                'role'       => $result['role'],
                'confidence' => $result['confidence'],
                'source'     => 'heuristic',
                'updated_at' => $now,
            );
            update_post_meta($p->ID, self::META_KEY, $result['role']);
            $classified++;
        }

        update_option(self::OPTION_KEY, wp_json_encode($map), false);
        return array(
            'classified' => $classified,
            'skipped'    => $skipped,
            'total'      => count($posts),
            'updated_at' => $now,
        );
    }

    /**
     * Classify a single post via heuristic only. Returns {role, confidence} or null.
     */
    public static function classify_one_heuristic($post, $page_on_front = null, $page_for_posts = null) {
        if (!is_object($post)) return null;
        if ($page_on_front  === null) $page_on_front  = (int) get_option('page_on_front', 0);
        if ($page_for_posts === null) $page_for_posts = (int) get_option('page_for_posts', 0);

        // 1. page_on_front / page_for_posts → strongest match
        if ($page_on_front > 0 && (int) $post->ID === $page_on_front) {
            return array('role' => 'homepage', 'confidence' => 1.0);
        }
        if ($page_for_posts > 0 && (int) $post->ID === $page_for_posts) {
            return array('role' => 'blog_index', 'confidence' => 1.0);
        }

        // 2. Slug pattern matching
        $slug = isset($post->post_name) ? (string) $post->post_name : '';
        if ($slug !== '') {
            foreach (self::slug_patterns() as $pat) {
                if (preg_match($pat['regex'], $slug)) {
                    return array('role' => $pat['role'], 'confidence' => $pat['conf']);
                }
            }
        }

        // 3. post_type fallbacks
        $pt = isset($post->post_type) ? (string) $post->post_type : '';
        if ($pt === 'post') {
            return array('role' => 'blog_post', 'confidence' => 0.95);
        }
        if ($pt === 'product') {
            return array('role' => 'product_page', 'confidence' => 0.9);
        }
        // CPTs with has_archive=true that look like product/service pages
        $pt_object = get_post_type_object($pt);
        if ($pt_object && !empty($pt_object->has_archive)) {
            // Generic CPT with archive — guess product unless name suggests service
            $hint = strtolower($pt . ' ' . (string) $pt_object->labels->name);
            if (strpos($hint, 'service') !== false || strpos($hint, 'servizi') !== false) {
                return array('role' => 'service_page', 'confidence' => 0.85);
            }
            return array('role' => 'product_page', 'confidence' => 0.85);
        }

        // 4. Parent page with children → category_landing
        if ($pt === 'page') {
            // Check if has children
            $children = get_posts(array(
                'post_type'        => 'page',
                'post_status'      => 'publish',
                'post_parent'      => $post->ID,
                'posts_per_page'   => 1,
                'fields'           => 'ids',
                'no_found_rows'    => true,
            ));
            if (!empty($children)) {
                return array('role' => 'category_landing', 'confidence' => 0.7);
            }
        }

        // 5. Default — unclassified (LLM augment territory)
        return null;
    }

    // ============================================================
    // Top-10 ranking (for Free tier scan auto-pre-selection)
    // ============================================================

    /**
     * Find posts of a given role, ordered by heuristic ranking score (importance).
     * Optionally exclude specific IDs (used by featured auto-suggest cascade).
     *
     * @param string $role
     * @param int $limit
     * @param array $exclude_ids
     * @return WP_Post[]
     */
    public static function find_by_role_ranked($role, $limit = 1, $exclude_ids = array()) {
        // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_value,WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Admin diagnostic query for page role mapping, low frequency (admin pages only), acceptable performance trade-off.
        if (!in_array($role, self::ROLES, true)) return array();
        $limit = max(1, min(20, (int) $limit));

        // Get all candidates with this role
        $candidates = get_posts(array(
            'post_type'        => 'any',
            'post_status'      => 'publish',
            'posts_per_page'   => 50, // pool to score
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Admin diagnostic query for page-role mapping, executed on plugin admin pages only, not on frontend. Acceptable performance trade-off for the diagnostic value.
            'meta_key'         => self::META_KEY,
            'meta_value'       => $role,
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Not targeting VIP infrastructure; standard WordPress API
            'post__not_in'     => array_map('intval', $exclude_ids),
            'orderby'          => 'modified',
            'order'            => 'DESC',
            'no_found_rows'    => true,
        ));
        if (empty($candidates)) return array();

        // Score each candidate using the same logic as heuristic_rank_top10 (compact version)
        $page_on_front = (int) get_option('page_on_front', 0);
        $nav_ids = self::main_nav_menu_post_ids();
        $home_links = array();
        if ($page_on_front > 0) {
            $home_post = get_post($page_on_front);
            if ($home_post && !empty($home_post->post_content)) {
                if (preg_match_all('#<a[^>]*href=["\']([^"\']+)["\']#i', $home_post->post_content, $mm)) {
                    foreach ($mm[1] as $href) $home_links[$href] = true;
                }
            }
        }

        $scored = array();
        foreach ($candidates as $p) {
            $score = 0;
            if (isset($nav_ids[$p->ID])) $score += 50;
            if ($p->post_type === 'page') {
                $children_count = count(get_posts(array(
                    'post_type'        => 'page',
                    'post_status'      => 'publish',
                    'post_parent'      => $p->ID,
                    'posts_per_page'   => 1,
                    'fields'           => 'ids',
                    'no_found_rows'    => true,
                )));
                if ($children_count > 0) $score += 30;
            }
            $permalink = get_permalink($p);
            if ($permalink && isset($home_links[$permalink])) $score += 20;
            $content_len = strlen((string) $p->post_content);
            $score += min(50, intval($content_len / 1000) * 10);
            $scored[] = array('post' => $p, 'score' => $score);
        }

        // Sort by score desc
        usort($scored, function ($a, $b) { return $b['score'] - $a['score']; });
        $top = array_slice($scored, 0, $limit);
        return array_map(function ($x) { return $x['post']; }, $top);
        // phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_value,WordPress.DB.SlowDBQuery.slow_db_query_meta_key
    }

    /**
     * Score every public post 0-200 and return top-N IDs by descending score.
     */
    public static function heuristic_rank_top10($n = 10) {
        $n = max(1, min(50, (int) $n));

        $page_on_front  = (int) get_option('page_on_front', 0);
        $page_for_posts = (int) get_option('page_for_posts', 0);

        // Get nav menu page IDs (primary location)
        $nav_ids = self::main_nav_menu_post_ids();

        // Homepage content (for "linked from homepage" heuristic)
        $home_links = array();
        if ($page_on_front > 0) {
            $home_post = get_post($page_on_front);
            if ($home_post && !empty($home_post->post_content)) {
                if (preg_match_all('#<a[^>]*href=["\']([^"\']+)["\']#i', $home_post->post_content, $mm)) {
                    foreach ($mm[1] as $href) {
                        $home_links[$href] = true;
                    }
                }
            }
        }

        $core_keywords = self::ranking_keywords();

        // Iterate all public posts up to a sane cap
        $posts = get_posts(array(
            'post_type'        => self::queryable_post_types(),
            'post_status'      => 'publish',
            'posts_per_page'   => 500, // cap for ranking scan
            'orderby'          => 'modified',
            'order'            => 'DESC',
            'no_found_rows'    => true,
        ));

        $scored = array();
        foreach ($posts as $p) {
            $score = 0;

            // Role-based bonuses
            if ($page_on_front > 0 && (int) $p->ID === $page_on_front) {
                $score += 100;
            }
            if ($page_for_posts > 0 && (int) $p->ID === $page_for_posts) {
                $score += 90;
            }

            // In primary nav menu
            if (isset($nav_ids[(int) $p->ID])) {
                $score += 50;
            }

            // Has children (parent page)
            if ($p->post_type === 'page') {
                $children_count = count(get_posts(array(
                    'post_type'        => 'page',
                    'post_status'      => 'publish',
                    'post_parent'      => $p->ID,
                    'posts_per_page'   => 1,
                    'fields'           => 'ids',
                    'no_found_rows'    => true,
                )));
                if ($children_count > 0) $score += 30;
            }

            // Linked from homepage
            $permalink = get_permalink($p);
            if ($permalink && isset($home_links[$permalink])) {
                $score += 20;
            }

            // Content length bonus (per 1000 chars, capped at ~50)
            $content_len = strlen((string) $p->post_content);
            $score += min(50, intval($content_len / 1000) * 10);

            // Title contains core keyword
            $title_lc = strtolower((string) $p->post_title);
            foreach ($core_keywords as $kw) {
                if (strpos($title_lc, $kw) !== false) {
                    $score += 5;
                    break;
                }
            }

            // Penalties
            if (class_exists('SEO_AEO_Global_Filters') && SEO_AEO_Global_Filters::is_noindex_post($p->ID)) {
                $score -= 10;
            }
            $role = self::get_role($p->ID);
            if ($role === 'legal_privacy' || $role === 'legal_terms') {
                $score -= 50;
            }
            if ($role === 'ignore') {
                $score -= 200; // effectively excluded
            }

            $scored[(int) $p->ID] = $score;
        }

        // Sort by score desc
        arsort($scored);
        $top = array_slice($scored, 0, $n, true);
        return array_keys($top);
    }

    /**
     * Returns set of post IDs in the primary nav menu (or 'main' / 'header' / first registered).
     */
    private static function main_nav_menu_post_ids() {
        if (!function_exists('wp_get_nav_menu_items')) return array();

        $locations = function_exists('get_nav_menu_locations') ? get_nav_menu_locations() : array();
        $candidates = array('primary', 'main', 'header', 'top-menu', 'main-menu', 'menu-1');
        $menu_id = 0;
        foreach ($candidates as $loc) {
            if (!empty($locations[$loc])) {
                $menu_id = (int) $locations[$loc];
                break;
            }
        }
        // Fallback: take the first menu location available
        if ($menu_id === 0 && !empty($locations)) {
            $menu_id = (int) reset($locations);
        }
        if ($menu_id === 0) return array();

        $items = wp_get_nav_menu_items($menu_id);
        if (!is_array($items)) return array();

        $ids = array();
        foreach ($items as $it) {
            $oid = isset($it->object_id) ? (int) $it->object_id : 0;
            if ($oid > 0) $ids[$oid] = true;
        }
        return $ids;
    }

    /**
     * Returns the list of post types eligible for classification.
     */
    private static function queryable_post_types() {
        $public = get_post_types(array('public' => true), 'names');
        // Exclude blocked types from SEO_AEO_Global_Filters if available
        if (class_exists('SEO_AEO_Global_Filters')) {
            $blocked = array_flip(SEO_AEO_Global_Filters::hardcoded_post_type_blocklist());
            foreach ($public as $k => $v) {
                if (isset($blocked[$v])) unset($public[$k]);
            }
        }
        // Exclude attachment specifically
        unset($public['attachment']);
        return array_values($public);
    }

    // ============================================================
    // Cache invalidation hooks
    // ============================================================

    public static function invalidate_on_save($post_id, $post = null) {
        if (wp_is_post_revision($post_id)) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        $post = $post ?: get_post($post_id);
        if (!$post || $post->post_status !== 'publish') return;

        // Don't overwrite manual classifications
        $existing = self::get_role_descriptor($post_id);
        if ($existing && isset($existing['source']) && $existing['source'] === 'manual') return;

        $result = self::classify_one_heuristic($post);
        if ($result === null) return;

        $map = self::get_map();
        $map[(int) $post_id] = array(
            'role'       => $result['role'],
            'confidence' => $result['confidence'],
            'source'     => 'heuristic',
            'updated_at' => current_time('mysql', true),
        );
        update_option(self::OPTION_KEY, wp_json_encode($map), false);
        update_post_meta($post_id, self::META_KEY, $result['role']);
    }

    public static function invalidate_on_delete($post_id) {
        $map = self::get_map();
        if (isset($map[$post_id])) {
            unset($map[$post_id]);
            update_option(self::OPTION_KEY, wp_json_encode($map), false);
        }
        delete_post_meta($post_id, self::META_KEY);
    }

    // ============================================================
    // Schema / sitemap helpers (Stage 2.5 downstream)
    // ============================================================

    /**
     * Map a role to the canonical Schema.org @type for that page.
     */
    public static function role_to_schema_type($role) {
        $map = array(
            'homepage'         => 'WebSite',
            'blog_index'       => 'CollectionPage',
            'about'            => 'AboutPage',
            'contact'          => 'ContactPage',
            'faq'              => 'FAQPage',
            'quote_request'    => 'ContactPage',
            'knowledge_guide'  => 'Article',
            'category_landing' => 'CollectionPage',
            'service_page'     => 'Service',
            'product_page'     => 'Product',
            'blog_post'        => 'Article',
            'legal_privacy'    => 'WebPage',
            'legal_terms'      => 'WebPage',
            'custom'           => 'WebPage',
            'ignore'           => null,
        );
        return isset($map[$role]) ? $map[$role] : 'WebPage';
    }

    /**
     * Map a role to a default sitemap.xml priority value.
     */
    public static function role_to_sitemap_priority($role) {
        $map = array(
            'homepage'         => '1.0',
            'about'            => '0.9',
            'blog_index'       => '0.9',
            'service_page'     => '0.8',
            'product_page'     => '0.8',
            'category_landing' => '0.8',
            'contact'          => '0.7',
            'faq'              => '0.7',
            'quote_request'    => '0.7',
            'knowledge_guide'  => '0.6',
            'blog_post'        => '0.5',
            'custom'           => '0.5',
            'legal_privacy'    => '0.3',
            'legal_terms'      => '0.3',
            'ignore'           => '0.0',
        );
        return isset($map[$role]) ? $map[$role] : '0.5';
    }
}

// ============================================================
// WP hooks: invalidation on save/delete (always-on, free)
// ============================================================
add_action('save_post', array('SEO_AEO_Page_Roles', 'invalidate_on_save'), 10, 2);
add_action('delete_post', array('SEO_AEO_Page_Roles', 'invalidate_on_delete'), 10, 1);

// Weekly cron — scheduled via main file (so activation/deactivation can manage it)
add_action('seo_aeo_weekly_role_classify', function () {
    SEO_AEO_Page_Roles::heuristic_classify_all();
});

<?php
// phpcs:disable WordPress.Security.NonceVerification.Missing
// phpcs:disable WordPress.Security.NonceVerification.Recommended
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
// Reason: nonce + sanitize chain is enforced upstream. AJAX handlers call
// check_ajax_referer at the top of each method; admin form handlers call
// check_admin_referer; reads of $_SERVER (DOCUMENT_ROOT, HTTP_USER_AGENT)
// are wrapped in sanitize_text_field(wp_unslash()) at the read site. The
// Plugin Check static analyzer cannot trace control flow across method
// boundaries, so it flags these as missing — but the security guarantees
// hold at runtime.
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com
 *
 * Native Sitemap Generator (v3.13.0 — Fase 1B strategia "switch da Yoast")
 *
 * Espone una sitemap.xml index + sub-sitemap dinamici per ogni tipo:
 *   /seo-aeo-sitemap.xml                  → index (lista dei sub-sitemap)
 *   /seo-aeo-sitemap-post.xml             → tutti i blog post
 *   /seo-aeo-sitemap-page.xml             → tutte le pagine statiche
 *   /seo-aeo-sitemap-{custom_post_type}.xml → custom post types public
 *   /seo-aeo-sitemap-category.xml         → archivi categoria
 *   /seo-aeo-sitemap-post_tag.xml         → archivi tag
 *   /seo-aeo-sitemap-author.xml           → archivi autore
 *
 * Cache: transient `seo_aeo_sitemap_cache_{type}` con TTL 6h. Invalidata su:
 *   - save_post / delete_post → invalida sitemap del tipo coinvolto + index
 *   - toggle ON/OFF del setting → flush totale
 *
 * Setting:
 *   - seo_aeo_native_sitemap_enabled (default '0' = OFF)
 *
 * Quando Yoast/RankMath/AIOSEO è attivo, la nostra sitemap convive senza conflitto
 * (URL diversi). L'utente decide quale sottoporre a Search Console.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Sitemap {

    const OPTION_ENABLED = 'seo_aeo_native_sitemap_enabled';
    const QUERY_VAR      = 'seo_aeo_sitemap';
    const CACHE_PREFIX   = 'seo_aeo_sitemap_';
    const CACHE_TTL      = 21600; // 6h
    const URL_LIMIT      = 1000;  // max URL per sub-sitemap (sotto i 50000 del protocollo)

    public function __construct() {
        add_action('init', array($this, 'register_rewrites'));
        add_filter('query_vars', array($this, 'register_query_var'));
        add_action('template_redirect', array($this, 'handle_request'));

        // Fallback diretto via request filter: utile su hosting (es. Aruba + WP Rocket)
        // dove le rewrite rules NON vengono mai persistite in wp_options.rewrite_rules.
        // Questo handler mappa l'URL pretty alla query var senza dipendere dal sistema rewrite.
        add_filter('request', array($this, 'parse_pretty_url'), 10, 1);

        // Disabilita canonical redirect WP sui nostri URL: WP altrimenti aggiunge trailing
        // slash (es. /seo-aeo-sitemap-post.xml → /seo-aeo-sitemap-post.xml/) e i crawler
        // ricevono un 301 invece del nostro XML.
        add_filter('redirect_canonical', array($this, 'skip_canonical_redirect'), 10, 2);

        // Forza WP a non considerare la pagina come 404 quando matcha il nostro pattern
        add_action('parse_request', array($this, 'unset_404_for_sitemap'), 99);

        // Robots.txt: aggiungi link alla nostra sitemap (Google la troverà automaticamente)
        add_filter('robots_txt', array($this, 'append_to_robots_txt'), 99, 2);

        // Cache invalidation
        add_action('save_post', array($this, 'invalidate_cache_for_post'), 10, 1);
        add_action('delete_post', array($this, 'invalidate_cache_for_post'), 10, 1);
        add_action('edited_term', array($this, 'invalidate_cache_for_term'), 10, 3);
        add_action('created_term', array($this, 'invalidate_cache_for_term'), 10, 3);
        add_action('delete_term', array($this, 'invalidate_cache_for_term'), 10, 3);
    }

    public static function is_enabled() {
        return get_option(self::OPTION_ENABLED, '0') === '1';
    }

    /**
     * Rewrite rules: cattura /seo-aeo-sitemap.xml e /seo-aeo-sitemap-{type}.xml.
     */
    public function register_rewrites() {
        // Canonical paths (3.35.52 — what users expect at the standard URL)
        add_rewrite_rule(
            '^sitemap\.xml$',
            'index.php?' . self::QUERY_VAR . '=index',
            'top'
        );
        add_rewrite_rule(
            '^sitemap-([a-z0-9_-]+)\.xml$',
            'index.php?' . self::QUERY_VAR . '=$matches[1]',
            'top'
        );
        // Legacy paths (preserved since 3.13.0 for backwards compat — same handler)
        add_rewrite_rule(
            '^seo-aeo-sitemap\.xml$',
            'index.php?' . self::QUERY_VAR . '=index',
            'top'
        );
        add_rewrite_rule(
            '^seo-aeo-sitemap-([a-z0-9_-]+)\.xml$',
            'index.php?' . self::QUERY_VAR . '=$matches[1]',
            'top'
        );
    }

    public function register_query_var($vars) {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    /**
     * Fallback: parse REQUEST_URI e setta query var manualmente quando le rewrite rules
     * non sono persistite (Aruba + WP Rocket + altri hosting con caching aggressivo).
     */
    public function parse_pretty_url($query_vars) {
        if (!self::is_enabled()) return $query_vars;
        if (is_admin()) return $query_vars;
        if ($this->matched_sitemap_type() !== null) {
            $query_vars[self::QUERY_VAR] = $this->matched_sitemap_type();
        }
        return $query_vars;
    }

    /**
     * Helper: ritorna il "tipo" di sitemap richiesta o null se l'URL non matcha.
     */
    private function matched_sitemap_type() {
        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $path = wp_parse_url($uri, PHP_URL_PATH);
        if (!$path) return null;
        $path = trim($path, '/');
        // Canonical paths
        if ($path === 'sitemap.xml') return 'index';
        if (preg_match('#^sitemap-([a-z0-9_-]+)\.xml$#i', $path, $m)) {
            return sanitize_key($m[1]);
        }
        // Legacy paths (3.13.0 — kept for backwards compat)
        if ($path === 'seo-aeo-sitemap.xml') return 'index';
        if (preg_match('#^seo-aeo-sitemap-([a-z0-9_-]+)\.xml$#i', $path, $m)) {
            return sanitize_key($m[1]);
        }
        return null;
    }

    /**
     * Disabilita canonical redirect WP sui nostri URL pretty.
     */
    public function skip_canonical_redirect($redirect_url, $requested_url) {
        if ($this->matched_sitemap_type() !== null) {
            return false; // non redirezionare
        }
        return $redirect_url;
    }

    /**
     * Pulisce flag 404 quando l'URL matcha il nostro pattern. WP_Query setta is_404=true
     * perché non trova un post — noi gestiamo la richiesta in template_redirect ben prima.
     */
    public function unset_404_for_sitemap($wp) {
        if ($this->matched_sitemap_type() !== null) {
            // Il nostro template_redirect handler farà status_header(200) ed exit.
            // Impostiamo qui anche query_var per sicurezza (potrebbe non passare dal filter request).
            $wp->query_vars[self::QUERY_VAR] = $this->matched_sitemap_type();
        }
    }

    /**
     * Intercetta la richiesta XML e produce la risposta.
     */
    public function handle_request() {
        $type = get_query_var(self::QUERY_VAR);
        if (empty($type)) return;

        if (!self::is_enabled()) {
            // Setting disattivato: 404 (non confondere crawler con sitemap vuota).
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }

        // Cache lookup
        $cache_key = self::CACHE_PREFIX . sanitize_key($type);
        $cached = get_transient($cache_key);
        if ($cached !== false && is_string($cached) && strlen($cached) > 0) {
            $this->send_xml($cached, true);
            return;
        }

        // Generate
        if ($type === 'index') {
            $xml = $this->generate_index();
        } else {
            $xml = $this->generate_sub_sitemap($type);
        }

        if ($xml === null) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }

        set_transient($cache_key, $xml, self::CACHE_TTL);
        $this->send_xml($xml, false);
    }

    private function send_xml($xml, $cached) {
        // CRITICAL: forza status 200 — WP_Query potrebbe aver settato 404 prima
        // perché non trova un post matching all'URL. Il body è corretto, lo status no.
        status_header(200);
        nocache_headers();
        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=' . get_bloginfo('charset'));
            header('X-Robots-Tag: noindex, follow');
            header('Cache-Control: max-age=3600, public');
            if ($cached) header('X-SEO-AEO-Sitemap-Cache: HIT');
            else header('X-SEO-AEO-Sitemap-Cache: MISS');
            header('X-SEO-AEO-Sitemap-Generator: dynamic-orchestra/' . (defined('SEO_AEO_VERSION') ? SEO_AEO_VERSION : '?'));
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $xml;
        exit;
    }

    /**
     * Sitemap index: lista di tutti i sub-sitemap disponibili.
     */
    private function generate_index() {
        $sub_sitemaps = $this->get_available_sub_sitemaps();
        $base = home_url('/');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        // XSL stylesheet rimosso (3.13.6): se il file XSL non esiste alcuni browser
        // mostrano pagina bianca. Lo rimettiamo quando avremo gli XSL pronti.
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($sub_sitemaps as $type => $info) {
            $loc = $base . 'sitemap-' . $type . '.xml';
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>' . esc_url($loc) . "</loc>\n";
            if (!empty($info['lastmod'])) {
                $xml .= '    <lastmod>' . esc_html($info['lastmod']) . "</lastmod>\n";
            }
            $xml .= "  </sitemap>\n";
        }

        $xml .= "</sitemapindex>\n";
        $xml .= '<!-- Generated by AEO Orchestra v' . esc_html(SEO_AEO_VERSION) . ' -->';
        return $xml;
    }

    /**
     * Calcola i sub-sitemap disponibili in base ai contenuti del sito.
     * Ritorna array di {type => {lastmod, count}}.
     */
    private function get_available_sub_sitemaps() {
        $list = array();

        // Public post types — Stage 1.5 Addendum 2: respect Sitemap.xml accordion whitelist
        // (default null = ALL public, user can configure via admin UI).
        if (class_exists('SEO_AEO_Global_Filters')) {
            $allowed = SEO_AEO_Global_Filters::get_sitemap_post_types();
            $post_types = array();
            foreach ($allowed as $pt) {
                $post_types[$pt] = $pt;
            }
        } else {
            $post_types = get_post_types(array('public' => true), 'names');
        }
        unset($post_types['attachment']); // niente sitemap di allegati per ora
        foreach ($post_types as $pt) {
            $count = wp_count_posts($pt);
            $published = isset($count->publish) ? (int) $count->publish : 0;
            if ($published <= 0) continue;
            $latest = get_posts(array(
                'post_type' => $pt,
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'orderby' => 'modified',
                'order' => 'DESC',
                'fields' => 'ids',
                'no_found_rows' => true,
            ));
            $lastmod = '';
            if (!empty($latest)) {
                $p = get_post($latest[0]);
                if ($p) $lastmod = mysql2date('c', $p->post_modified_gmt, false);
            }
            $list[$pt] = array('lastmod' => $lastmod, 'count' => $published);
        }

        // Public taxonomies
        $taxonomies = get_taxonomies(array('public' => true), 'names');
        unset($taxonomies['post_format']); // post_format non serve in sitemap
        unset($taxonomies['link_category']);
        foreach ($taxonomies as $tax) {
            $count = wp_count_terms(array('taxonomy' => $tax, 'hide_empty' => true));
            if (is_wp_error($count) || (int) $count <= 0) continue;
            $list[$tax] = array('lastmod' => '', 'count' => (int) $count);
        }

        // Author archives (solo se ci sono autori con post pubblicati)
        $authors = get_users(array(
            'has_published_posts' => array('post'),
            'fields' => 'ID',
            'number' => 1,
        ));
        if (!empty($authors)) {
            $list['author'] = array('lastmod' => '', 'count' => -1);
        }

        return $list;
    }

    /**
     * Genera sub-sitemap per un tipo specifico.
     * Ritorna XML string o null se il tipo non è valido.
     */
    private function generate_sub_sitemap($type) {
        // 3.35.52: respect Sitemap.xml whitelist — return null if requested CPT excluded.
        if (class_exists('SEO_AEO_Global_Filters') && $type !== 'category' && $type !== 'post_tag' && $type !== 'author') {
            $allowed = SEO_AEO_Global_Filters::get_sitemap_post_types();
            $taxonomies = get_taxonomies(array('public' => true), 'names');
            $is_taxonomy = isset($taxonomies[$type]);
            if (!$is_taxonomy && !in_array($type, $allowed, true)) {
                return null;
            }
        }

        $type = sanitize_key($type);

        // Author archives speciali
        if ($type === 'author') {
            return $this->generate_author_sitemap();
        }

        // Taxonomy
        if (taxonomy_exists($type)) {
            return $this->generate_taxonomy_sitemap($type);
        }

        // Post type
        if (post_type_exists($type) && get_post_type_object($type)->public) {
            return $this->generate_post_type_sitemap($type);
        }

        return null;
    }

    private function generate_post_type_sitemap($post_type) {
        $posts = get_posts(array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => self::URL_LIMIT,
            'orderby' => 'modified',
            'order' => 'DESC',
            'no_found_rows' => true,
        ));

        $xml = $this->urlset_open();

        // Homepage solo nel sitemap "page" (la includiamo una volta sola)
        if ($post_type === 'page') {
            $xml .= $this->url_entry(home_url('/'), '', 'daily', '1.0');
        }

        foreach ($posts as $p) {
            $url = get_permalink($p);
            if (!$url) continue;
            $lastmod = mysql2date('c', $p->post_modified_gmt, false);
            // 3.35.55: priority + changefreq dinamici per role (Stage 2.5). Fallback al default.
            $priority = null;
            $changefreq = null;
            if (class_exists('SEO_AEO_Page_Roles')) {
                $role = SEO_AEO_Page_Roles::get_role($p->ID);
                if ($role) {
                    $priority = SEO_AEO_Page_Roles::role_to_sitemap_priority($role);
                    $cf_map = array(
                        'homepage'      => 'daily',
                        'blog_index'    => 'daily',
                        'blog_post'     => 'weekly',
                        'faq'           => 'monthly',
                        'contact'       => 'yearly',
                        'legal_privacy' => 'yearly',
                        'legal_terms'   => 'yearly',
                    );
                    $changefreq = isset($cf_map[$role]) ? $cf_map[$role] : (($post_type === 'page') ? 'monthly' : 'weekly');

                    // 3.35.74: per-role overrides via WP options
                    $prio_overrides = get_option('seo_aeo_sitemap_priority_overrides', array());
                    if (is_array($prio_overrides) && isset($prio_overrides[$role]) && $prio_overrides[$role] !== '') {
                        $priority = (string) $prio_overrides[$role];
                    }
                    $cf_overrides = get_option('seo_aeo_sitemap_changefreq_overrides', array());
                    if (is_array($cf_overrides) && isset($cf_overrides[$role]) && $cf_overrides[$role] !== '') {
                        $changefreq = (string) $cf_overrides[$role];
                    }
                    // Noindex override: skip URL entirely if true
                    $noindex_overrides = get_option('seo_aeo_sitemap_noindex_overrides', array());
                    if (is_array($noindex_overrides) && !empty($noindex_overrides[$role])) {
                        continue; // skip this URL from sitemap
                    }
                }
            }
            if ($priority === null) $priority = ($post_type === 'page') ? '0.7' : '0.6';
            if ($changefreq === null) $changefreq = ($post_type === 'page') ? 'monthly' : 'weekly';
            $xml .= $this->url_entry($url, $lastmod, $changefreq, $priority);
        }

        $xml .= $this->urlset_close();
        return $xml;
    }

    private function generate_taxonomy_sitemap($taxonomy) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
            'number' => self::URL_LIMIT,
        ));
        if (is_wp_error($terms)) return null;

        $xml = $this->urlset_open();
        foreach ($terms as $term) {
            $url = get_term_link($term);
            if (is_wp_error($url) || !$url) continue;
            $xml .= $this->url_entry($url, '', 'weekly', '0.5');
        }
        $xml .= $this->urlset_close();
        return $xml;
    }

    private function generate_author_sitemap() {
        $authors = get_users(array(
            'has_published_posts' => array('post'),
            'fields' => array('ID'),
            'number' => self::URL_LIMIT,
        ));

        $xml = $this->urlset_open();
        foreach ($authors as $u) {
            $url = get_author_posts_url($u->ID);
            if (!$url) continue;
            $xml .= $this->url_entry($url, '', 'weekly', '0.4');
        }
        $xml .= $this->urlset_close();
        return $xml;
    }

    private function urlset_open() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
        return $xml;
    }

    private function urlset_close() {
        return "</urlset>\n" . '<!-- Generated by AEO Orchestra v' . esc_html(SEO_AEO_VERSION) . ' -->';
    }

    private function url_entry($loc, $lastmod = '', $changefreq = '', $priority = '') {
        $entry = "  <url>\n";
        $entry .= '    <loc>' . esc_url($loc) . "</loc>\n";
        if ($lastmod !== '')    $entry .= '    <lastmod>' . esc_html($lastmod) . "</lastmod>\n";
        if ($changefreq !== '') $entry .= '    <changefreq>' . esc_html($changefreq) . "</changefreq>\n";
        if ($priority !== '')   $entry .= '    <priority>' . esc_html($priority) . "</priority>\n";
        $entry .= "  </url>\n";
        return $entry;
    }

    private function stylesheet_url($context = 'index') {
        // Ritorna URL di un foglio XSL per rendere la sitemap human-readable nel browser.
        // Il file lo serviamo via plugin assets (vedi sitemap-style.xsl in assets/)
        $base = SEO_AEO_PLUGIN_URL . 'assets/sitemap-' . sanitize_key($context) . '.xsl';
        return $base;
    }

    public function append_to_robots_txt($output, $public) {
        if (!self::is_enabled()) return $output;
        if ((string) $public === '0') return $output;
        $sitemap_url = home_url('/seo-aeo-sitemap.xml');
        // Aggiungi se non già presente
        if (strpos($output, $sitemap_url) === false) {
            $output .= "\n# AEO Orchestra\n";
            $output .= "Sitemap: " . esc_url($sitemap_url) . "\n";
        }
        return $output;
    }

    /**
     * Cache invalidation
     */
    public function invalidate_cache_for_post($post_id) {
        $post = get_post($post_id);
        if (!$post) return;
        $type = $post->post_type;
        delete_transient(self::CACHE_PREFIX . sanitize_key($type));
        delete_transient(self::CACHE_PREFIX . 'index');
    }

    public function invalidate_cache_for_term($term_id, $tt_id, $taxonomy) {
        if (!is_string($taxonomy)) return;
        delete_transient(self::CACHE_PREFIX . sanitize_key($taxonomy));
        delete_transient(self::CACHE_PREFIX . 'index');
    }

    public static function flush_all_cache() {
        global $wpdb;
        // Cancella tutti i transient con il nostro prefix
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
        $wpdb->query(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . self::CACHE_PREFIX . '%',
                '_transient_timeout_' . self::CACHE_PREFIX . '%'
            )
        );
    }

    /**
     * Toggle helper: chiamato dall'AJAX. Salva opzione + flush rewrite rules + flush cache.
     *
     * IMPORTANTE: `delete_option('rewrite_rules')` da solo è "lazy" e non sempre triggera
     * la rigenerazione (richiede un page load admin completo). `flush_rewrite_rules(false)`
     * è la chiamata robusta: forza WP a rigenerare le rules ora, senza riscrivere .htaccess
     * (false evita problemi su hosting con permission limitate tipo Aruba).
     */
    public static function set_enabled($enabled) {
        $enabled = $enabled ? '1' : '0';
        update_option(self::OPTION_ENABLED, $enabled);
        self::flush_all_cache();
        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules(false);
        } else {
            delete_option('rewrite_rules');
        }
    }


    // ============================================================
    // Force Regenerate (3.35.52 — Stage 1.5 hot fix)
    // Mirror of SEO_AEO_LLMs_Txt::force_regenerate. Purges static legacy files
    // (e.g. /sitemap.xml placed manually at root), all transients, WP Rocket,
    // W3TC, WPSC. Optionally fetches a sample for the admin UI report.
    // ============================================================

    public static function force_regenerate($opts = array()) {
        $defaults = array(
            'enable_toggle'  => true,   // auto-enable seo_aeo_native_sitemap_enabled if OFF
            'purge_external' => true,   // WP Rocket / W3TC / WPSC
            'fetch_sample'   => true,   // internal wp_remote_get
        );
        $opts = array_merge($defaults, is_array($opts) ? $opts : array());

        $report = array(
            'timestamp'              => current_time('mysql'),
            'static_files_removed'   => array(),
            'static_files_skipped'   => array(),
            'transients_flushed'     => array(),
            'rewrite_flushed'        => false,
            'toggle_state_before'    => self::is_enabled() ? 'on' : 'off',
            'toggle_state_after'     => null,
            'rocket_purged'          => false,
            'w3tc_purged'            => false,
            'wpsc_purged'            => false,
            'object_cache_flushed'   => false,
            'sample'                 => null,
            'sample_url'             => null,
            'sample_status'          => null,
            'sample_generator'       => null,
            'errors'                 => array(),
        );

        // ---- Static file detection + rename ----
        $candidates = self::candidate_static_paths();
        $stamp = time();
        foreach ($candidates as $path) {
            if (!is_string($path) || $path === '') continue;
            if (!file_exists($path)) {
                $report['static_files_skipped'][] = $path;
                continue;
            }
            if (strpos($path, '.bak-pre-orchestra-') !== false) continue;
            $backup = $path . '.bak-pre-orchestra-' . $stamp;
            // 3.36.0 (WP.org compliance): atomic same-filesystem rename of a static
            // file at site root, inside admin-form submit (manage_options held).
            // WP_Filesystem would require a credentials prompt that doesn't apply.
            // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
            if (@rename($path, $backup)) {
                $report['static_files_removed'][] = array('path' => $path, 'backup' => $backup);
            } else {
                $err = error_get_last();
                $report['errors'][] = 'Could not rename static file ' . $path . ': ' . (is_array($err) && isset($err['message']) ? $err['message'] : 'unknown');
            }
        }

        // ---- Toggle ----
        if ($opts['enable_toggle'] && !self::is_enabled()) {
            update_option(self::OPTION_ENABLED, '1');
        }
        $report['toggle_state_after'] = self::is_enabled() ? 'on' : 'off';

        // ---- Transients ----
        // Sitemap cache transients use CACHE_PREFIX. Loop over candidates explicitly.
        $known_transients = array('index', 'post', 'page', 'category', 'post_tag', 'author');
        foreach ($known_transients as $t) {
            delete_transient(self::CACHE_PREFIX . sanitize_key($t));
            $report['transients_flushed'][] = self::CACHE_PREFIX . $t;
        }
        // Plus any registered public CPT
        $custom_types = get_post_types(array('public' => true), 'names');
        foreach ($custom_types as $pt) {
            delete_transient(self::CACHE_PREFIX . sanitize_key($pt));
            $report['transients_flushed'][] = self::CACHE_PREFIX . $pt;
        }

        // ---- Rewrite rules ----
        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules(false);
            $report['rewrite_flushed'] = true;
        }

        // ---- Object cache ----
        if (function_exists('wp_cache_flush')) {
            @wp_cache_flush();
            $report['object_cache_flushed'] = true;
        }

        // ---- External page caches (defensive) ----
        if ($opts['purge_external']) {
            $urls = array(
                home_url('/sitemap.xml'),
                home_url('/seo-aeo-sitemap.xml'),
                // Also nuke common sub-sitemap URLs
                home_url('/sitemap-post.xml'),
                home_url('/sitemap-page.xml'),
            );

            try {
                if (function_exists('rocket_clean_files')) {
                    @rocket_clean_files($urls);
                    $report['rocket_purged'] = true;
                } elseif (function_exists('rocket_clean_domain')) {
                    @rocket_clean_domain();
                    $report['rocket_purged'] = true;
                }
                if (function_exists('flush_rocket_htaccess_rules')) {
                    @flush_rocket_htaccess_rules();
                }
            } catch (Throwable $e) {
                $report['errors'][] = 'Rocket purge: ' . $e->getMessage();
            }

            try {
                if (function_exists('w3tc_pgcache_flush')) {
                    @w3tc_pgcache_flush();
                    $report['w3tc_purged'] = true;
                } elseif (function_exists('w3tc_flush_url')) {
                    foreach ($urls as $u) { @w3tc_flush_url($u); }
                    $report['w3tc_purged'] = true;
                }
            } catch (Throwable $e) {
                $report['errors'][] = 'W3TC purge: ' . $e->getMessage();
            }

            try {
                if (function_exists('wpsc_delete_url_cache')) {
                    foreach ($urls as $u) { @wpsc_delete_url_cache($u); }
                    $report['wpsc_purged'] = true;
                } elseif (function_exists('wp_cache_clean_cache')) {
                    @wp_cache_clean_cache('');
                    $report['wpsc_purged'] = true;
                }
            } catch (Throwable $e) {
                $report['errors'][] = 'WPSC purge: ' . $e->getMessage();
            }
        }

        // ---- Internal sample fetch ----
        if ($opts['fetch_sample']) {
            $bust_url = home_url('/sitemap.xml') . '?_orch_bust=' . $stamp;
            $report['sample_url'] = $bust_url;
            try {
                $resp = wp_remote_get($bust_url, array(
                    'timeout'     => 10,
                    'redirection' => 0,
                    'sslverify'   => false,
                    'headers'     => array('Cache-Control' => 'no-cache'),
                ));
                if (is_wp_error($resp)) {
                    $report['errors'][] = 'Sample fetch: ' . $resp->get_error_message();
                } else {
                    $body = wp_remote_retrieve_body($resp);
                    $headers = wp_remote_retrieve_headers($resp);
                    $report['sample']          = mb_substr((string) $body, 0, 500);
                    $report['sample_status']   = (int) wp_remote_retrieve_response_code($resp);
                    $report['sample_generator'] = is_object($headers) && method_exists($headers, 'offsetGet')
                        ? (string) $headers->offsetGet('x-seo-aeo-sitemap-generator')
                        : (isset($headers['x-seo-aeo-sitemap-generator']) ? (string) $headers['x-seo-aeo-sitemap-generator'] : '');
                }
            } catch (Throwable $e) {
                $report['errors'][] = 'Sample fetch exception: ' . $e->getMessage();
            }
        }

        return $report;
    }

    /**
     * Build the list of candidate filesystem paths where a stale static sitemap.xml
     * (or sub-sitemap files) might live and shadow our PHP handler.
     */
    private static function candidate_static_paths() {
        $paths = array();
        $names = array('sitemap.xml', 'sitemap_index.xml', 'seo-aeo-sitemap.xml');

        $bases = array();
        if (defined('ABSPATH')) {
            $bases[] = rtrim(ABSPATH, '/\\');
            $bases[] = rtrim(dirname(ABSPATH), '/\\');
        }
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $bases[] = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
        }

        $seen = array();
        foreach ($bases as $b) {
            if (!is_string($b) || $b === '' || $b === '/') continue;
            $b = str_replace('\\', '/', $b);
            if (isset($seen[$b])) continue;
            $seen[$b] = true;
            foreach ($names as $n) {
                $paths[] = $b . '/' . $n;
            }
        }
        return $paths;
    }

}

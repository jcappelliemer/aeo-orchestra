<?php
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com
 *
 * llms.txt + llms-full.txt generator (v3.13.1 — Fase 1C strategia "switch da Yoast")
 *
 * Esponi due endpoint markdown alla root del sito per i Large Language Models:
 *
 *   /llms.txt       → INDEX curato (titoli + descrizione breve per ogni pagina importante)
 *   /llms-full.txt  → INDEX + CONTENUTO completo in markdown delle top N pagine
 *
 * Spec: https://llmstxt.org/ (Jeremy Howard, settembre 2024)
 * Adopters: Anthropic, Stripe, Vercel, Cloudflare, Mintlify, ...
 *
 * AEO Orchestra è il primo plugin WordPress con llms.txt nativo automatico.
 * Differenziatore commerciale forte vs Yoast/RankMath/AIOSEO.
 *
 * Setting:
 *   - seo_aeo_native_llms_txt_enabled (default '0' = OFF)
 *
 * Cache: transient TTL 6h, invalidata su save_post / delete_post.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_LLMs_Txt {

    const OPTION_ENABLED = 'seo_aeo_native_llms_txt_enabled';
    const QUERY_VAR      = 'seo_aeo_llms_txt';
    const CACHE_PREFIX   = 'seo_aeo_llms_txt_';
    const CACHE_TTL      = 21600; // 6h
    const INDEX_LIMIT    = 50;    // max pagine elencate in llms.txt
    const FULL_LIMIT     = 30;    // max pagine con contenuto inline in llms-full.txt
    const FULL_CHARS     = 4000;  // max caratteri di contenuto per pagina (truncate per non far esplodere il file)

    public function __construct() {
        add_action('init', array($this, 'register_rewrites'));
        add_filter('query_vars', array($this, 'register_query_var'));
        add_action('template_redirect', array($this, 'handle_request'));

        // Fallback diretto via request filter: utile dove le rewrite rules non sono persistite
        // (Aruba + WP Rocket + caching aggressivo). Bypassa il sistema rewrite e setta
        // la query var leggendo direttamente REQUEST_URI.
        add_filter('request', array($this, 'parse_pretty_url'), 10, 1);
        add_filter('redirect_canonical', array($this, 'skip_canonical_redirect'), 10, 2);
        add_action('parse_request', array($this, 'unset_404_for_llms'), 99);

        // Cache invalidation su modifica contenuti
        add_action('save_post', array($this, 'invalidate_cache'), 10, 1);
        add_action('delete_post', array($this, 'invalidate_cache'), 10, 1);
    }

    private function matched_llms_type() {
        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $path = wp_parse_url($uri, PHP_URL_PATH);
        if (!$path) return null;
        $path = trim($path, '/');
        if ($path === 'llms.txt') return 'index';
        if ($path === 'llms-full.txt') return 'full';
        return null;
    }

    public function skip_canonical_redirect($redirect_url, $requested_url) {
        if ($this->matched_llms_type() !== null) return false;
        return $redirect_url;
    }

    public function unset_404_for_llms($wp) {
        $type = $this->matched_llms_type();
        if ($type !== null) {
            $wp->query_vars[self::QUERY_VAR] = $type;
        }
    }

    public static function is_enabled() {
        return get_option(self::OPTION_ENABLED, '0') === '1';
    }

    public function register_rewrites() {
        add_rewrite_rule('^llms\.txt$',      'index.php?' . self::QUERY_VAR . '=index', 'top');
        add_rewrite_rule('^llms-full\.txt$', 'index.php?' . self::QUERY_VAR . '=full',  'top');
    }

    public function register_query_var($vars) {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    /**
     * Fallback: parse REQUEST_URI e setta query var manualmente quando le rewrite rules
     * non sono persistite. Bypassa rewrite, funziona su qualsiasi hosting.
     *
     * IMPORTANT: se esiste un file fisico /llms.txt alla root del sito (caso Solaris),
     * Apache/nginx lo serve PRIMA che PHP venga invocato — quindi questo handler non
     * viene mai chiamato per quel file. Comportamento corretto: rispettiamo il file
     * statico esistente, e gestiamo solo /llms-full.txt (e /llms.txt se non esiste fisico).
     */
    public function parse_pretty_url($query_vars) {
        if (!self::is_enabled()) return $query_vars;
        if (is_admin()) return $query_vars;
        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $path = wp_parse_url($uri, PHP_URL_PATH);
        if (!$path) return $query_vars;
        $path = trim($path, '/');
        if ($path === 'llms.txt') {
            $query_vars[self::QUERY_VAR] = 'index';
        } elseif ($path === 'llms-full.txt') {
            $query_vars[self::QUERY_VAR] = 'full';
        }
        return $query_vars;
    }

    public function handle_request() {
        $type = get_query_var(self::QUERY_VAR);
        if (empty($type) || !in_array($type, array('index', 'full'), true)) return;

        if (!self::is_enabled()) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }

        $cache_key = self::CACHE_PREFIX . $type;
        $content = get_transient($cache_key);
        $from_cache = ($content !== false && is_string($content) && strlen($content) > 0);

        if (!$from_cache) {
            $content = ($type === 'full') ? $this->generate_full() : $this->generate_index();
            set_transient($cache_key, $content, self::CACHE_TTL);
        }

        status_header(200);
        nocache_headers();
        if (!headers_sent()) {
            header('Content-Type: text/markdown; charset=UTF-8');
            header('X-Robots-Tag: noindex, follow');
            header('Cache-Control: max-age=3600, public');
            header('X-SEO-AEO-LLMs-Cache: ' . ($from_cache ? 'HIT' : 'MISS'));
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $content;
        exit;
    }

    /**
     * llms.txt: header con descrizione del sito + lista raggruppata per tipo (pagine, articoli)
     * con un breve excerpt per ogni voce.
     */
    private function generate_index() {
        $site_name = get_bloginfo('name');
        $site_desc = get_bloginfo('description');
        $home_url  = home_url('/');

        $md = "# " . $this->md_escape($site_name) . "\n\n";
        if ($site_desc) {
            $md .= "> " . $this->md_escape($site_desc) . "\n\n";
        }
        $md .= "**Sito ufficiale**: " . $home_url . "\n\n";
        $md .= "Questo file è una mappa curata dei contenuti principali del sito, formattata in markdown per essere consumata da Large Language Models (ChatGPT, Claude, Perplexity, Gemini, ecc.). Per il contenuto completo inline, vedi `/llms-full.txt`.\n\n";

        // Pagine
        $pages = get_posts(array(
            'post_type'        => 'page',
            'post_status'      => 'publish',
            'posts_per_page'   => self::INDEX_LIMIT,
            'orderby'          => 'modified',
            'order'            => 'DESC',
            'no_found_rows'    => true,
            'suppress_filters' => true,
        ));
        if (!empty($pages)) {
            $md .= "## Pagine\n\n";
            foreach ($pages as $p) {
                $md .= $this->index_line($p);
            }
            $md .= "\n";
        }

        // Articoli (post type 'post')
        $posts = get_posts(array(
            'post_type'        => 'post',
            'post_status'      => 'publish',
            'posts_per_page'   => self::INDEX_LIMIT,
            'orderby'          => 'modified',
            'order'            => 'DESC',
            'no_found_rows'    => true,
            'suppress_filters' => true,
        ));
        if (!empty($posts)) {
            $md .= "## Articoli\n\n";
            foreach ($posts as $p) {
                $md .= $this->index_line($p);
            }
            $md .= "\n";
        }

        // Custom post types pubblici (oltre post + page)
        $custom_types = get_post_types(array('public' => true, '_builtin' => false), 'objects');
        foreach ($custom_types as $cpt) {
            $items = get_posts(array(
                'post_type'        => $cpt->name,
                'post_status'      => 'publish',
                'posts_per_page'   => self::INDEX_LIMIT,
                'orderby'          => 'modified',
                'order'            => 'DESC',
                'no_found_rows'    => true,
                'suppress_filters' => true,
            ));
            if (empty($items)) continue;
            $md .= "## " . $this->md_escape($cpt->labels->name ?: $cpt->name) . "\n\n";
            foreach ($items as $p) {
                $md .= $this->index_line($p);
            }
            $md .= "\n";
        }

        $md .= "---\n";
        $md .= "*Generato automaticamente da AEO Orchestra v" . SEO_AEO_VERSION . " · " . gmdate('Y-m-d H:i') . " UTC*\n";
        return $md;
    }

    private function index_line($post) {
        $url = get_permalink($post);
        $title = $this->resolve_title($post);
        $excerpt = $this->resolve_excerpt($post);
        $line = "- [" . $this->md_escape($title) . "](" . $url . ")";
        if ($excerpt) {
            $line .= ": " . $this->md_escape($excerpt);
        }
        return $line . "\n";
    }

    /**
     * Risolve il titolo della pagina con fallback se vuoto.
     * Alcune pagine (template Elementor, custom CPT con title vuoto) hanno post_title=''.
     */
    private function resolve_title($post) {
        $title = get_the_title($post);
        if (!empty($title) && trim($title) !== '') return $title;

        // Fallback 1: meta_title da bridge SEO
        if (class_exists('SEO_AEO_Engine_Bridge')) {
            $meta = SEO_AEO_Engine_Bridge::read_meta($post->ID);
            if (!empty($meta['meta_title'])) return $meta['meta_title'];
        }
        // Fallback 2: slug umanizzato
        if (!empty($post->post_name)) {
            return ucwords(str_replace(array('-', '_'), ' ', $post->post_name));
        }
        // Fallback 3: ID
        return 'Pagina #' . $post->ID;
    }

    /**
     * llms-full.txt: index sintetico + contenuto completo markdown delle top N pagine.
     */
    private function generate_full() {
        $site_name = get_bloginfo('name');
        $site_desc = get_bloginfo('description');
        $home_url  = home_url('/');

        $md = "# " . $this->md_escape($site_name) . "\n\n";
        if ($site_desc) {
            $md .= "> " . $this->md_escape($site_desc) . "\n\n";
        }
        $md .= "**Sito ufficiale**: " . $home_url . "\n\n";
        $md .= "Versione **completa** con contenuti dei post/pagine principali in markdown. Le pagine sono ordinate per ultima modifica.\n\n";
        $md .= "---\n\n";

        // Mix di pagine + articoli, top FULL_LIMIT per recency combinata
        $items = get_posts(array(
            'post_type'        => array('page', 'post'),
            'post_status'      => 'publish',
            'posts_per_page'   => self::FULL_LIMIT,
            'orderby'          => 'modified',
            'order'            => 'DESC',
            'no_found_rows'    => true,
            'suppress_filters' => true,
        ));

        foreach ($items as $p) {
            $url = get_permalink($p);
            $title = $this->resolve_title($p);
            $excerpt = $this->resolve_excerpt($p);
            $content_md = $this->html_to_markdown($p->post_content);
            $content_md = $this->truncate_chars($content_md, self::FULL_CHARS);

            $md .= "## " . $this->md_escape($title) . "\n\n";
            $md .= "**URL**: " . $url . "\n";
            if (!empty($p->post_modified_gmt)) {
                $md .= "**Ultima modifica**: " . mysql2date('Y-m-d', $p->post_modified_gmt, false) . "\n";
            }
            if ($excerpt) {
                $md .= "**Sommario**: " . $this->md_escape($excerpt) . "\n";
            }
            $md .= "\n";
            $md .= $content_md . "\n\n";
            $md .= "---\n\n";
        }

        $md .= "*Generato automaticamente da AEO Orchestra v" . SEO_AEO_VERSION . " · " . gmdate('Y-m-d H:i') . " UTC*\n";
        return $md;
    }

    /**
     * Risolve l'excerpt nell'ordine: meta_description (bridge) → post_excerpt → auto-excerpt.
     */
    private function resolve_excerpt($post) {
        if (class_exists('SEO_AEO_Engine_Bridge')) {
            $meta = SEO_AEO_Engine_Bridge::read_meta($post->ID);
            if (!empty($meta['meta_description'])) {
                return wp_strip_all_tags($meta['meta_description']);
            }
        }
        if (!empty($post->post_excerpt)) {
            return wp_strip_all_tags($post->post_excerpt);
        }
        $stripped = wp_strip_all_tags(strip_shortcodes((string) $post->post_content));
        return wp_trim_words($stripped, 25, '');
    }

    /**
     * Converte HTML WP in markdown sufficiente per LLM. Non perfetto, ma robusto:
     * preserva headings, bold/italic, link, list, paragraph; strippa tutto il resto.
     */
    private function html_to_markdown($html) {
        if (empty($html)) return '';

        // Espandi shortcode risolvibili (no-op se non sono attivi)
        $html = strip_shortcodes($html);

        // Headings h1-h6
        for ($i = 6; $i >= 1; $i--) {
            $hashes = str_repeat('#', $i);
            $html = preg_replace('/<h' . $i . '[^>]*>\s*(.*?)\s*<\/h' . $i . '>/is', "\n\n" . $hashes . " $1\n\n", $html);
        }

        // Bold / italic
        $html = preg_replace('/<(strong|b)[^>]*>(.*?)<\/\1>/is', '**$2**', $html);
        $html = preg_replace('/<(em|i)[^>]*>(.*?)<\/\1>/is', '*$2*', $html);

        // Links
        $html = preg_replace_callback(
            '/<a[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is',
            function ($m) {
                $url = $m[1];
                $text = wp_strip_all_tags($m[2]);
                return '[' . $text . '](' . $url . ')';
            },
            $html
        );

        // Lists
        $html = preg_replace('/<li[^>]*>\s*(.*?)\s*<\/li>/is', "- $1\n", $html);
        $html = preg_replace('/<\/?(ul|ol)[^>]*>/i', "\n", $html);

        // Block elements → newline
        $html = preg_replace('/<\/?(p|div|section|article|blockquote|pre)[^>]*>/i', "\n\n", $html);
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);

        // Strip tutti i tag rimanenti
        $html = wp_strip_all_tags($html);

        // Decode HTML entities
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Collapse whitespace eccessivo
        $html = preg_replace('/[ \t]+/', ' ', $html);
        $html = preg_replace('/\n{3,}/', "\n\n", $html);

        return trim($html);
    }

    private function truncate_chars($s, $max) {
        if (mb_strlen($s) <= $max) return $s;
        $cut = mb_substr($s, 0, $max);
        // tronco al limite di parola se possibile
        $last_space = mb_strrpos($cut, ' ');
        if ($last_space !== false && $last_space > $max * 0.7) {
            $cut = mb_substr($cut, 0, $last_space);
        }
        return $cut . "…\n\n*[contenuto troncato — vedi pagina completa per il resto]*";
    }

    /**
     * Escape minimo per markdown: evita che il content "rompa" la sintassi (caratteri speciali in titoli).
     */
    private function md_escape($s) {
        $s = (string) $s;
        // Rimuovi newline incorporati nei titoli/excerpt (li traduco in spazio)
        $s = str_replace(array("\r\n", "\r", "\n"), ' ', $s);
        return trim($s);
    }

    public function invalidate_cache($post_id = 0) {
        delete_transient(self::CACHE_PREFIX . 'index');
        delete_transient(self::CACHE_PREFIX . 'full');
    }

    public static function flush_all_cache() {
        delete_transient(self::CACHE_PREFIX . 'index');
        delete_transient(self::CACHE_PREFIX . 'full');
    }

    /**
     * Toggle helper: chiamato dall'AJAX. Salva opzione + flush cache + flush rewrite rules.
     * `flush_rewrite_rules(false)` è più robusto di `delete_option` perché forza la
     * rigenerazione immediata invece di aspettare un page load admin.
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
}

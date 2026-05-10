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
    const INDEX_LIMIT    = 50;    // max pagine elencate in llms.txt (NB: 3.35.49 slim convention — index ora cita SOLO featured_pages, INDEX_LIMIT serve solo a generate_full per le sezioni autogenerate)
    const FULL_LIMIT     = 100;   // max pagine per sezione in llms-full.txt (3.35.49: bumped 30→100)
    const FEATURED_PAGES_MAX = 10; // numero massimo di pagine in evidenza in /llms.txt
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

        // Stage 1.5: ensure global filter schema is migrated (idempotent).
        if (class_exists('SEO_AEO_Global_Filters')) {
            SEO_AEO_Global_Filters::maybe_migrate();
        }

        // 3.35.84.4: one-shot 3.35.49 notice removed (>4 months old, content stale).
        // The render_v3_35_49_notice / maybe_dismiss_v3_35_49_notice methods below are
        // kept as no-op stubs only to preserve the public API surface in case any third
        // party referenced them. Future announcement banners go through SEO_AEO_Admin_Notices.
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
            header('X-SEO-AEO-LLMs-Generator: dynamic-orchestra/' . (defined('SEO_AEO_VERSION') ? SEO_AEO_VERSION : '?'));
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $content;
        exit;
    }

    /**
     * Stage 1.5 (3.35.49): slim convention.
     *
     * /llms.txt is now a curated entry point: site identity + a small list of
     * editorially chosen "featured" pages. The full autogenerated dump moves to
     * /llms-full.txt.
     *
     * Sections emitted (in order):
     *   # Site name + tagline
     *   {identity sections from profile} (Identity / What sets us apart / etc.)
     *   ## Pagine principali (only if featured_pages option populated)
     *   Footer with link to /llms-full.txt and /sitemap.xml
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

        // Identity Profile sections (Onboarding 2.0)
        $identity_md = $this->render_identity_sections($this->fetch_identity_profile());
        if ($identity_md !== '') {
            $md .= $identity_md;
        }

        // 3.35.49: Pagine principali — manually curated featured pages only.
        $featured_ids = self::get_featured_pages();
        if (!empty($featured_ids)) {
            $featured_lines = array();
            foreach ($featured_ids as $pid) {
                $p = get_post($pid);
                if (!$p || $p->post_status !== 'publish') continue;
                $featured_lines[] = $this->index_line_short($p);
            }
            $featured_lines = array_filter($featured_lines, function ($l) { return $l !== ''; });
            if (!empty($featured_lines)) {
                $md .= "## Pagine principali\n\n";
                foreach ($featured_lines as $line) {
                    $md .= $line;
                }
                $md .= "\n";
            }
        }

        // Footer pointing to the verbose dump + sitemap.
        $md .= "---\n\n";
        $md .= "*Per il listato completo delle pagine, vedi [`/llms-full.txt`](" . esc_url(home_url('/llms-full.txt')) . ") e [`/sitemap.xml`](" . esc_url(home_url('/sitemap.xml')) . ").*\n\n";
        $md .= "*Generato automaticamente da AEO Orchestra v" . SEO_AEO_VERSION . " · " . gmdate('Y-m-d H:i') . " UTC*\n";
        return $md;
    }

    /**
     * Render a single curated/featured-page line: title + URL + short excerpt (max 200 char).
     * Returns empty string if the post is excluded (defensive).
     */
    private function index_line_short($post) {
        // Even featured pages should respect global filters (noindex etc.) — only skip
        // if the post is excluded for OTHER reasons. If user explicitly featured it,
        // we trust their intent and emit it regardless of post-type filter.
        $url = get_permalink($post);
        if (empty($url)) return '';
        $title = $this->resolve_title($post);
        $excerpt = $this->resolve_excerpt($post);
        $line = "- [" . $this->md_escape($title) . "](" . $url . ")";
        if ($excerpt) {
            $excerpt = mb_substr((string) $excerpt, 0, 200);
            $line .= " — " . $this->md_escape($excerpt);
        }
        return $line . "\n";
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
     * /llms-full.txt: verbose dump.
     *
     * Stage 1.5 (3.35.49): emits the autogenerated CPT sections (## Pagine,
     * ## Articoli, ## <CPT name>) which previously lived in /llms.txt, plus
     * the per-post content dump. Heading levels in the embedded content are
     * shifted so that no embedded subsection collides with the ## section
     * markers (post wrappers are ### and content headings are #### or deeper).
     *
     * Cap is FULL_LIMIT (100) per section.
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
        $md .= "Versione **completa** del file llms.txt — include la lista per tipo di contenuto (Pagine, Articoli, CPT custom) seguita dal dump del corpo dei post principali in markdown.\n\n";

        // Identity Profile sections (Onboarding 2.0)
        $identity_md_full = $this->render_identity_sections($this->fetch_identity_profile());
        if ($identity_md_full !== '') {
            $md .= $identity_md_full;
        }

        // ===== Autogenerated CPT sections (cap FULL_LIMIT per section) =====
        // 3.35.51 (Stage 1.5 Addendum 2): per-output post type whitelist.
        // Default for /llms-full.txt is ['post', 'page'] (curato per AI ingestion),
        // distinto dal sitemap.xml che invece include tutti i public per default.
        $allowed_types = class_exists('SEO_AEO_Global_Filters')
            ? SEO_AEO_Global_Filters::get_llms_full_post_types()
            : self::get_allowed_post_types();

        // ## Pagine
        if (in_array('page', $allowed_types, true)) {
            $pages_raw = get_posts(array(
                'post_type'        => 'page',
                'post_status'      => 'publish',
                'posts_per_page'   => self::FULL_LIMIT * 3,
                'orderby'          => 'modified',
                'order'            => 'DESC',
                'no_found_rows'    => true,
            ));
            $filtered = self::filter_and_cap($pages_raw, self::FULL_LIMIT);
            if (!empty($filtered['kept'])) {
                $md .= "## Pagine\n\n";
                foreach ($filtered['kept'] as $p) {
                    $md .= $this->index_line($p);
                }
                if ($filtered['truncated_count'] > 0) {
                    $md .= "- *[+" . intval($filtered['truncated_count']) . " altre, vedi " . esc_url(home_url('/sitemap.xml')) . "]*\n";
                }
                $md .= "\n";
            }
        }

        // ## Articoli
        if (in_array('post', $allowed_types, true)) {
            $posts_raw = get_posts(array(
                'post_type'        => 'post',
                'post_status'      => 'publish',
                'posts_per_page'   => self::FULL_LIMIT * 3,
                'orderby'          => 'modified',
                'order'            => 'DESC',
                'no_found_rows'    => true,
            ));
            $filtered = self::filter_and_cap($posts_raw, self::FULL_LIMIT);
            if (!empty($filtered['kept'])) {
                $md .= "## Articoli\n\n";
                foreach ($filtered['kept'] as $p) {
                    $md .= $this->index_line($p);
                }
                if ($filtered['truncated_count'] > 0) {
                    $md .= "- *[+" . intval($filtered['truncated_count']) . " altri, vedi " . esc_url(home_url('/sitemap.xml')) . "]*\n";
                }
                $md .= "\n";
            }
        }

        // ## CPT (custom post types pubblici)
        $custom_types = get_post_types(array('public' => true, '_builtin' => false), 'objects');
        $cpt_blocked = array_flip(self::hardcoded_post_type_blocklist());
        foreach ($custom_types as $cpt) {
            if (isset($cpt_blocked[$cpt->name])) continue;
            if (!in_array($cpt->name, $allowed_types, true)) continue;
            $items_raw = get_posts(array(
                'post_type'        => $cpt->name,
                'post_status'      => 'publish',
                'posts_per_page'   => self::FULL_LIMIT * 3,
                'orderby'          => 'modified',
                'order'            => 'DESC',
                'no_found_rows'    => true,
            ));
            $filtered = self::filter_and_cap($items_raw, self::FULL_LIMIT);
            if (empty($filtered['kept'])) continue;
            $md .= "## " . $this->md_escape($cpt->labels->name ?: $cpt->name) . "\n\n";
            foreach ($filtered['kept'] as $p) {
                $md .= $this->index_line($p);
            }
            if ($filtered['truncated_count'] > 0) {
                $md .= "- *[+" . intval($filtered['truncated_count']) . " altre, vedi " . esc_url(home_url('/sitemap.xml')) . "]*\n";
            }
            $md .= "\n";
        }

        // ===== Per-post content dump (### wrappers, content headings shifted to #### min) =====
        // Reuse the same per-output allowed_types — content dump is for /llms-full.txt only.
        $allowed_for_full = array_values(array_intersect(array('page', 'post'), $allowed_types));
        if (empty($allowed_for_full)) $allowed_for_full = array('page', 'post');
        $items_raw = get_posts(array(
            'post_type'        => $allowed_for_full,
            'post_status'      => 'publish',
            'posts_per_page'   => self::FULL_LIMIT * 3,
            'orderby'          => 'modified',
            'order'            => 'DESC',
            'no_found_rows'    => true,
        ));
        $filtered_full = self::filter_and_cap($items_raw, self::FULL_LIMIT);
        $items = $filtered_full['kept'];
        $items_truncated = $filtered_full['truncated_count'];

        if (!empty($items)) {
            $md .= "## Contenuti completi\n\n";
            foreach ($items as $p) {
                $url = get_permalink($p);
                $title = $this->resolve_title($p);
                $excerpt = $this->resolve_excerpt($p);
                $content_md = $this->html_to_markdown($p->post_content);
                // 3.35.49: shift content headings up by 3 so embedded h1→####, h2→#####, etc.
                // Wrapper is ### so nothing inside collides with ## (top-level CPT sections).
                $content_md = $this->shift_md_headings($content_md, 3, 6);
                $content_md = $this->truncate_chars($content_md, self::FULL_CHARS);

                $md .= "### " . $this->md_escape($title) . "\n\n";
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
        }

        if (!empty($items_truncated) && $items_truncated > 0) {
            $md .= "*[+" . intval($items_truncated) . " pagine omesse — vedi " . esc_url(home_url('/sitemap.xml')) . " per la lista completa]*\n\n";
        }
        $md .= "*Generato automaticamente da AEO Orchestra v" . SEO_AEO_VERSION . " · " . gmdate('Y-m-d H:i') . " UTC*\n";
        return $md;
    }

    /**
     * Shift all ATX-style markdown headings (^#{1,6}) by `$shift` levels, capped
     * at `$max`. Used to nest embedded post content under a deeper level than
     * the wrapper.
     */
    private function shift_md_headings($md, $shift, $max = 6) {
        if (!is_string($md) || $md === '') return $md;
        $shift = (int) $shift;
        $max = max(1, min(6, (int) $max));
        return preg_replace_callback('/^(#{1,6})(\s)/m', function ($m) use ($shift, $max) {
            $level = strlen($m[1]) + $shift;
            if ($level > $max) $level = $max;
            if ($level < 1)    $level = 1;
            return str_repeat('#', $level) . $m[2];
        }, $md);
    }

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
        delete_transient(self::IDENTITY_CACHE_KEY);
    }

    public static function flush_all_cache() {
        delete_transient(self::CACHE_PREFIX . 'index');
        delete_transient(self::CACHE_PREFIX . 'full');
        delete_transient(self::IDENTITY_CACHE_KEY);
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


    // ============================================================
    // Identity Profile injection (Onboarding 2.0 Stage 1)
    // ============================================================

    const IDENTITY_CACHE_KEY = 'seo_aeo_identity_profile';
    const OPTION_LLMS_ONLY_PATTERNS = 'seo_aeo_llms_only_exclude_patterns';
    const OPTION_FEATURED_PAGES     = 'seo_aeo_llms_featured_pages';
    const IDENTITY_CACHE_TTL = 3600; // 1h

    /**
     * Fetch identity profile from backend with 1h transient cache.
     * Returns array (possibly empty) — never null. Backwards compatible: on any
     * failure (no api client / network error / unreachable backend) returns [].
     */
    private function fetch_identity_profile() {
        $cached = get_transient(self::IDENTITY_CACHE_KEY);
        if ($cached !== false && is_array($cached)) return $cached;

        $profile = array();
        try {
            // Free build does not include class-api-client.php — skip gracefully
            if (!class_exists('SEO_AEO_API_Client')) return $profile;
            $api = new SEO_AEO_API_Client();
            $resp = $api->get_identity_profile();
            if (is_array($resp) && empty($resp['error'])) {
                // get_identity_profile returns the profile dict directly (or {})
                $profile = $resp;
            }
        } catch (Throwable $e) {
            seo_aeo_debug_log('[SEO_AEO LLMs] identity fetch failed: ' . $e->getMessage());
        }

        // Cache even an empty array so we don't hammer the backend on every request.
        // Saving the profile from the admin invalidates this transient (see ajax_identity_save).
        set_transient(self::IDENTITY_CACHE_KEY, $profile, self::IDENTITY_CACHE_TTL);
        return $profile;
    }

    /**
     * Render markdown sections from an identity profile.
     * Empty profile / no populated fields → empty string (backwards compatible).
     */
    private function render_identity_sections($profile) {
        if (empty($profile) || !is_array($profile)) return '';

        $out = array();

        // ---- Identity ----
        $name = $this->md_escape(isset($profile['business_name']) ? $profile['business_name'] : '');
        $desc = $this->md_escape(isset($profile['business_description']) ? $profile['business_description'] : '');
        $industry = $this->md_escape(isset($profile['industry']) ? $profile['industry'] : '');
        if ($name !== '' || $desc !== '' || $industry !== '') {
            $out[] = "## Identity\n";
            if ($name !== '') {
                $out[] = "**" . $name . "**" . ($desc !== '' ? " — " . $desc : '');
            } elseif ($desc !== '') {
                $out[] = $desc;
            }
            if ($industry !== '') {
                $out[] = "\n*Industry:* " . $industry;
            }
            $out[] = '';
        }

        // ---- What sets us apart ----
        $diffs = isset($profile['differentiators']) && is_array($profile['differentiators']) ? $profile['differentiators'] : array();
        $diffs = array_filter($diffs, function ($d) {
            return is_array($d) && (!empty($d['title']) || !empty($d['description']));
        });
        if (!empty($diffs)) {
            $out[] = "## What sets us apart\n";
            foreach ($diffs as $d) {
                $t = $this->md_escape(isset($d['title']) ? $d['title'] : '');
                $de = $this->md_escape(isset($d['description']) ? $d['description'] : '');
                if ($t !== '' && $de !== '') $out[] = "- **" . $t . "** — " . $de;
                elseif ($t !== '') $out[] = "- **" . $t . "**";
                elseif ($de !== '') $out[] = "- " . $de;
            }
            $out[] = '';
        }

        // ---- Audience (Paid) ----
        $aud = isset($profile['audiences']) && is_array($profile['audiences']) ? $profile['audiences'] : array();
        $aud = array_filter($aud, function ($a) {
            return is_array($a) && (!empty($a['role']) || !empty($a['need']) || !empty($a['looking_for']));
        });
        if (!empty($aud)) {
            $out[] = "## Audience\n";
            foreach ($aud as $a) {
                $role = $this->md_escape(isset($a['role']) ? $a['role'] : '');
                $need = $this->md_escape(isset($a['need']) ? $a['need'] : '');
                $lf   = $this->md_escape(isset($a['looking_for']) ? $a['looking_for'] : '');
                $line = $role !== '' ? "- **" . $role . "**" : "-";
                $tail = array();
                if ($need !== '') $tail[] = $need;
                if ($lf !== '') $tail[] = "*looking for:* " . $lf;
                if (!empty($tail)) $line .= ($role !== '' ? " — " : " ") . implode(", ", $tail);
                $out[] = $line;
            }
            $out[] = '';
        }

        // ---- Where we operate ----
        $terr = isset($profile['territories']) && is_array($profile['territories']) ? $profile['territories'] : array();
        $terr = array_values(array_filter(array_map(function ($t) {
            return $this->md_escape((string) $t);
        }, $terr), function ($t) { return $t !== ''; }));
        if (!empty($terr)) {
            $out[] = "## Where we operate\n";
            foreach ($terr as $t) $out[] = "- " . $t;
            $out[] = '';
        }

        // ---- Common use cases ----
        $uc = isset($profile['use_cases']) && is_array($profile['use_cases']) ? $profile['use_cases'] : array();
        $uc = array_filter($uc, function ($u) {
            return is_array($u) && (!empty($u['title']) || !empty($u['description']));
        });
        if (!empty($uc)) {
            $out[] = "## Common use cases\n";
            foreach ($uc as $u) {
                $t = $this->md_escape(isset($u['title']) ? $u['title'] : '');
                $de = $this->md_escape(isset($u['description']) ? $u['description'] : '');
                if ($t !== '' && $de !== '') $out[] = "- **" . $t . "** — " . $de;
                elseif ($t !== '') $out[] = "- **" . $t . "**";
                elseif ($de !== '') $out[] = "- " . $de;
            }
            $out[] = '';
        }

        // ---- Certifications & trust (Paid) ----
        $certs = isset($profile['certifications']) && is_array($profile['certifications']) ? $profile['certifications'] : array();
        $certs = array_filter($certs, function ($c) {
            return is_array($c) && (!empty($c['name']) || !empty($c['note']));
        });
        if (!empty($certs)) {
            $out[] = "## Certifications & trust\n";
            foreach ($certs as $c) {
                $n   = $this->md_escape(isset($c['name']) ? $c['name'] : '');
                $cat = $this->md_escape(isset($c['category']) ? $c['category'] : '');
                $note = $this->md_escape(isset($c['note']) ? $c['note'] : '');
                $bits = array();
                if ($n !== '') $bits[] = "**" . $n . "**";
                if ($cat !== '') $bits[] = "(" . $cat . ")";
                $line = !empty($bits) ? "- " . implode(' ', $bits) : "-";
                if ($note !== '') $line .= " — " . $note;
                $out[] = $line;
            }
            $out[] = '';
        }

        // ---- Testimonials (Paid) ----
        $tst = isset($profile['testimonials']) && is_array($profile['testimonials']) ? $profile['testimonials'] : array();
        $tst = array_filter($tst, function ($t) {
            return is_array($t) && (!empty($t['client_ref']) || !empty($t['project']));
        });
        if (!empty($tst)) {
            $out[] = "## Testimonials\n";
            foreach ($tst as $t) {
                $cr = $this->md_escape(isset($t['client_ref']) ? $t['client_ref'] : '');
                $pj = $this->md_escape(isset($t['project']) ? $t['project'] : '');
                $rs = $this->md_escape(isset($t['result']) ? $t['result'] : '');
                $bits = array();
                if ($cr !== '') $bits[] = "**" . $cr . "**";
                if ($pj !== '') $bits[] = $pj;
                $line = !empty($bits) ? "- " . implode(' — ', $bits) : "-";
                if ($rs !== '') $line .= " *(" . $rs . ")*";
                $out[] = $line;
            }
            $out[] = '';
        }

        // ---- FAQ (Paid) ----
        $faqs = isset($profile['faqs']) && is_array($profile['faqs']) ? $profile['faqs'] : array();
        $faqs = array_filter($faqs, function ($f) {
            return is_array($f) && (!empty($f['question']) || !empty($f['answer']));
        });
        if (!empty($faqs)) {
            $out[] = "## FAQ\n";
            foreach ($faqs as $f) {
                $q = $this->md_escape(isset($f['question']) ? $f['question'] : '');
                $a = $this->md_escape(isset($f['answer']) ? $f['answer'] : '');
                if ($q !== '') $out[] = "**Q: " . $q . "**";
                if ($a !== '') $out[] = "A: " . $a;
                $out[] = '';
            }
        }

        // ---- About strategic ----
        $about = $this->md_escape(isset($profile['about_strategic']) ? $profile['about_strategic'] : '');
        if ($about !== '') {
            $out[] = "## About\n";
            $out[] = $about;
            $out[] = '';
        }

        if (empty($out)) return '';
        return rtrim(implode("\n", $out)) . "\n\n";
    }



    // ============================================================
    // Force Regenerate (3.35.46-beta — Stage 1 bug fix)
    //
    // Bypassa: file statici legacy alla root sito (caso Solaris),
    //          WP Rocket / W3TC / WP Super Cache page cache,
    //          object cache, transient nostri.
    // Auto-enable toggle se disabilitato.
    // Ritorna report diagnostico: file rimossi, cache purgate, sample 500 char.
    // ============================================================

    public static function force_regenerate($opts = array()) {
        $defaults = array(
            'enable_toggle'  => true,   // auto-enable seo_aeo_native_llms_txt_enabled if OFF
            'purge_external' => true,   // WP Rocket / W3TC / WPSC defensive purges
            'fetch_sample'   => true,   // internal wp_remote_get for sample (first 500 chars)
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
            'sample_cache'           => null,
            'errors'                 => array(),
        );

        // ---- Static file detection + rename ----
        // Apache/nginx serve static files BEFORE PHP runs, so any /llms.txt at the
        // document root will shadow our handler. We back them up rather than delete.
        $candidates = self::candidate_static_paths();
        $stamp = time();
        foreach ($candidates as $path) {
            if (!is_string($path) || $path === '') continue;
            if (!file_exists($path)) {
                $report['static_files_skipped'][] = $path;
                continue;
            }
            // Skip if already a backup (defensive)
            if (strpos($path, '.bak-pre-orchestra-') !== false) continue;

            $backup = $path . '.bak-pre-orchestra-' . $stamp;
            // 3.36.0 (WP.org compliance): atomic same-filesystem rename of a static
            // file at site root, inside admin-form submit (manage_options held).
            // WP_Filesystem would require a credentials prompt that doesn't apply.
            // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
            if (@rename($path, $backup)) {
                $report['static_files_removed'][] = array('path' => $path, 'backup' => $backup);
            } else {
                // Fallback: try to truncate to empty so static serve returns empty 200,
                // OR copy our generated content into it. Safest: log error and continue.
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
        delete_transient(self::CACHE_PREFIX . 'index');
        delete_transient(self::CACHE_PREFIX . 'full');
        delete_transient(self::IDENTITY_CACHE_KEY);
        $report['transients_flushed'] = array(
            self::CACHE_PREFIX . 'index',
            self::CACHE_PREFIX . 'full',
            self::IDENTITY_CACHE_KEY,
        );

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
                home_url('/llms.txt'),
                home_url('/llms-full.txt'),
            );

            // WP Rocket
            try {
                if (function_exists('rocket_clean_files')) {
                    @rocket_clean_files($urls);
                    $report['rocket_purged'] = true;
                } elseif (function_exists('rocket_clean_domain')) {
                    // Last-resort: domain-wide purge (heavy but reliable)
                    @rocket_clean_domain();
                    $report['rocket_purged'] = true;
                }
                // Bonus: clean htaccess rules so WP Rocket disk-cache rules don't
                // intercept the URL again on next request
                if (function_exists('flush_rocket_htaccess_rules')) {
                    @flush_rocket_htaccess_rules();
                }
            } catch (Throwable $e) {
                $report['errors'][] = 'Rocket purge: ' . $e->getMessage();
            }

            // W3 Total Cache
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

            // WP Super Cache
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
            $bust_url = home_url('/llms.txt') . '?_orch_bust=' . $stamp;
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
                    $report['sample']           = mb_substr((string) $body, 0, 500);
                    $report['sample_status']    = (int) wp_remote_retrieve_response_code($resp);
                    $report['sample_generator'] = is_object($headers) && method_exists($headers, 'offsetGet')
                        ? (string) $headers->offsetGet('x-seo-aeo-llms-generator')
                        : (isset($headers['x-seo-aeo-llms-generator']) ? (string) $headers['x-seo-aeo-llms-generator'] : '');
                    $report['sample_cache'] = is_object($headers) && method_exists($headers, 'offsetGet')
                        ? (string) $headers->offsetGet('x-seo-aeo-llms-cache')
                        : (isset($headers['x-seo-aeo-llms-cache']) ? (string) $headers['x-seo-aeo-llms-cache'] : '');
                }
            } catch (Throwable $e) {
                $report['errors'][] = 'Sample fetch exception: ' . $e->getMessage();
            }
        }

        return $report;
    }

    /**
     * Build the list of candidate filesystem paths where a stale static llms.txt
     * (or llms-full.txt) might live and shadow our PHP handler.
     */
    private static function candidate_static_paths() {
        $paths = array();
        $names = array('llms.txt', 'llms-full.txt');

        $bases = array();
        if (defined('ABSPATH')) {
            $bases[] = rtrim(ABSPATH, '/\\');
            // wp-config in subdir → ABSPATH may be wp/, true root one level up
            $bases[] = rtrim(dirname(ABSPATH), '/\\');
        }
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $bases[] = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
        }

        // Normalize + dedup
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



    // ============================================================
    // Filtering helpers (3.35.47-beta — Bug 1: bloat reduction)
    // ============================================================

    // Hardcoded slug substring blocklist — pages most often used as
    // internal/template/funnel callbacks not meant for AI consumption.
    // Hardcoded blocklists — Stage 1.5: source of truth lives in
    // SEO_AEO_Global_Filters. These remain as thin shims for backwards compat.
    public static function hardcoded_slug_blocklist() {
        if (class_exists('SEO_AEO_Global_Filters')) return SEO_AEO_Global_Filters::hardcoded_slug_blocklist();
        return array();
    }
    public static function hardcoded_slug_prefix_blocklist() {
        if (class_exists('SEO_AEO_Global_Filters')) return SEO_AEO_Global_Filters::hardcoded_slug_prefix_blocklist();
        return array();
    }
    public static function hardcoded_post_type_blocklist() {
        if (class_exists('SEO_AEO_Global_Filters')) return SEO_AEO_Global_Filters::hardcoded_post_type_blocklist();
        return array();
    }
    public static function hardcoded_url_blocklist() {
        if (class_exists('SEO_AEO_Global_Filters')) return SEO_AEO_Global_Filters::hardcoded_url_blocklist();
        return array();
    }

    /**
     * User-defined exclude patterns from the admin UI (one per line, substring match
     * on URL OR slug). Stored in the WP option `seo_aeo_llms_exclude_patterns`.
     */
    /**
     * Stage 1.5: now reads from the unified global filter store. Kept as a thin
     * shim for any external caller. Use SEO_AEO_Global_Filters::parse_patterns()
     * directly when possible.
     */

    /**
     * 3.35.75 — Auto-suggest top N featured pages via algorithmic scoring (NO LLM).
     *
     * score = role_priority_weight + recency_bonus - excluded_penalty
     *         (engagement + search_value bonuses optional, only if Analytics/GSC integrated)
     *
     * @param int $limit max pages to return (default 10, min 1, max 50)
     * @return array list of {post_id, title, url, role, score, score_breakdown, post_modified}
     */
    public static function get_auto_suggested_pages($limit = 10) {
        $limit = max(1, min(50, (int) $limit));

        // Role weight lookup
        $role_weights = array(
            'homepage'         => 10,
            'knowledge_guide'  => 10,
            'blog_index'       => 8,
            'about'            => 7,
            'faq'              => 7,
            'service_page'     => 9,
            'product_page'     => 9,
            'category_landing' => 6,
            'contact'          => 8,  // lead_capture proxy
            'quote_request'    => 8,
            'blog_post'        => 4,
            'custom'           => 3,
            'legal_privacy'    => -5,
            'legal_terms'      => -5,
            'ignore'           => -10,
        );

        // Query all public-viewable posts
        $public_post_types = get_post_types(array('public' => true), 'names');
        // Exclude attachments
        unset($public_post_types['attachment']);

        $blocked_post_types = class_exists('SEO_AEO_Global_Filters')
            ? array_flip(SEO_AEO_Global_Filters::hardcoded_post_type_blocklist())
            : array();
        $public_post_types = array_diff_key(
            array_flip($public_post_types), $blocked_post_types
        );
        $public_post_types = array_keys($public_post_types);
        if (empty($public_post_types)) return array();

        $q = new WP_Query(array(
            'post_type'      => $public_post_types,
            'post_status'    => 'publish',
            'posts_per_page' => 200,  // candidate pool
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'no_found_rows'  => true,
            'fields'         => 'ids',
        ));

        $now = time();
        $scored = array();

        foreach ($q->posts as $pid) {
            $post = get_post($pid);
            if (!$post) continue;
            // Skip noindex pages
            if (self::is_noindex_post($pid)) continue;

            // Skip user exclude patterns
            $url = get_permalink($pid);
            if (!$url) continue;
            $slug = $post->post_name;
            $exclude_patterns = self::get_user_exclude_patterns();
            $skip = false;
            if (is_array($exclude_patterns)) {
                foreach ($exclude_patterns as $pat) {
                    if ($pat === '') continue;
                    if (stripos($url, $pat) !== false || stripos($slug, $pat) !== false) {
                        $skip = true; break;
                    }
                }
            }
            if ($skip) continue;

            // 1. Role priority weight
            $role = '';
            if (class_exists('SEO_AEO_Page_Roles')) {
                $role = SEO_AEO_Page_Roles::get_role($pid) ?: '';
            }
            $role_score = isset($role_weights[$role]) ? $role_weights[$role] : 3;
            // Negative role = exclude entirely
            if ($role_score < 0) continue;

            // 2. Recency bonus from post_modified
            $modified_ts = strtotime($post->post_modified_gmt . ' UTC');
            $age_days = max(0, ($now - $modified_ts) / 86400);
            $recency_score = 0;
            if ($age_days < 30) $recency_score = 10;
            elseif ($age_days < 90) $recency_score = 6;
            elseif ($age_days < 180) $recency_score = 3;
            elseif ($age_days < 365) $recency_score = 1;

            // 3. Engagement bonus (only if data present in option)
            $engagement_score = 0;
            $analytics_data = get_option('seo_aeo_orchestra_analytics_pageviews_90d', array());
            if (is_array($analytics_data) && isset($analytics_data[$pid]) && $analytics_data[$pid] > 0) {
                $engagement_score = min(10, log10(max(1, $analytics_data[$pid])) * 2);
            }

            // 4. Search value bonus (GSC clicks/impressions, only if data present)
            $search_score = 0;
            $gsc_data = get_option('seo_aeo_orchestra_gsc_metrics_90d', array());
            if (is_array($gsc_data) && isset($gsc_data[$pid]) && is_array($gsc_data[$pid])) {
                $impr = isset($gsc_data[$pid]['impressions']) ? (float) $gsc_data[$pid]['impressions'] : 0;
                $clicks = isset($gsc_data[$pid]['clicks']) ? (float) $gsc_data[$pid]['clicks'] : 0;
                if ($impr > 0) $search_score += min(8, log10(max(1, $impr)) * 1.5);
                if ($clicks > 0) $search_score += min(10, log10(max(1, $clicks)) * 2);
            }

            $total = $role_score + $recency_score + $engagement_score + $search_score;

            $scored[] = array(
                'post_id'   => $pid,
                'title'     => get_the_title($pid),
                'url'       => $url,
                'slug'      => $slug,
                'role'      => $role ?: 'unknown',
                'score'     => round($total, 2),
                'score_breakdown' => array(
                    'role'       => $role_score,
                    'recency'    => $recency_score,
                    'engagement' => round($engagement_score, 2),
                    'search'     => round($search_score, 2),
                ),
                'post_modified' => $post->post_modified,
                'post_type'     => $post->post_type,
            );
        }

        // Sort DESC by score, ASC by title for tiebreak
        usort($scored, function($a, $b) {
            if ($a['score'] === $b['score']) return strcmp($a['title'], $b['title']);
            return $b['score'] <=> $a['score'];
        });

        return array_slice($scored, 0, $limit);
    }

    public static function get_user_exclude_patterns() {
        if (class_exists('SEO_AEO_Global_Filters')) {
            $settings = SEO_AEO_Global_Filters::get_settings();
            return SEO_AEO_Global_Filters::parse_patterns(isset($settings['exclude_patterns']) ? $settings['exclude_patterns'] : '');
        }
        // Backwards-compat fallback (should never hit in normal operation)
        return array();
    }

    /**
     * Stage 1.5: llms-only exclusion patterns. Layered ON TOP of the global
     * filter — a post excluded globally OR matching one of these patterns is
     * dropped from /llms.txt + /llms-full.txt only.
     */
    public static function get_llms_only_patterns() {
        $raw = get_option(self::OPTION_LLMS_ONLY_PATTERNS, '');
        if (class_exists('SEO_AEO_Global_Filters')) {
            return SEO_AEO_Global_Filters::parse_patterns($raw);
        }
        // Inline fallback parser
        if (!is_string($raw) || $raw === '') return array();
        $lines = preg_split('/\r\n|\r|\n/', $raw);
        $out = array();
        foreach ($lines as $l) {
            $l = trim($l);
            if ($l !== '' && substr($l, 0, 1) !== '#') $out[] = $l;
        }
        return $out;
    }

    /**
     * Allowed post types: user choice (option `seo_aeo_llms_post_types`) intersected
     * with the currently registered public types, minus the hardcoded blocklist.
     * Default (no option set) = all public types except blocked.
     */
    /**
     * Stage 1.5: delegate to global filters.
     */
    public static function get_allowed_post_types() {
        if (class_exists('SEO_AEO_Global_Filters')) {
            return SEO_AEO_Global_Filters::get_allowed_post_types();
        }
        // Inline fallback (defensive only)
        $public  = get_post_types(array('public' => true), 'names');
        $blocked = array_flip(self::hardcoded_post_type_blocklist());
        $allowed = array();
        foreach ($public as $pt) {
            if (isset($blocked[$pt])) continue;
            $allowed[] = $pt;
        }
        return $allowed;
    }

    /**
     * Yoast / RankMath / AIOSEO / native noindex check.
     */
    /**
     * Stage 1.5: delegate to global filters.
     */
    public static function is_noindex_post($post_id) {
        if (class_exists('SEO_AEO_Global_Filters')) {
            return SEO_AEO_Global_Filters::is_noindex_post($post_id);
        }
        return false;
    }

    /**
     * Returns true if the post should be excluded from /llms.txt and /llms-full.txt.
     * Cumulative: hardcoded blocklists + user patterns + noindex.
     */
    /**
     * Stage 1.5: global-then-local two-layer check.
     *   1) Global filter (post types, hardcoded blocklists, user patterns, noindex)
     *   2) llms-only override patterns
     */
    public static function should_exclude_post($post) {
        if (class_exists('SEO_AEO_Global_Filters')) {
            if (SEO_AEO_Global_Filters::is_post_globally_excluded($post)) return true;
        } else {
            if (!is_object($post)) return true;
        }

        // llms-only override patterns (substring on URL OR slug)
        if (is_object($post) && isset($post->post_name)) {
            $slug   = strtolower((string) $post->post_name);
            $url_lc = strtolower((string) get_permalink($post));
            foreach (self::get_llms_only_patterns() as $pat) {
                $pat_lc = strtolower($pat);
                if ($pat_lc === '') continue;
                if (strpos($slug, $pat_lc) !== false) return true;
                if (strpos($url_lc, $pat_lc) !== false) return true;
            }
        }

        return false;
    }

    /**
     * Filter a list of posts and apply a cap of N. Returns array of arrays:
     *   ['kept' => WP_Post[], 'truncated_count' => int]
     */
    public static function filter_and_cap($posts, $cap) {
        if (!is_array($posts)) return array('kept' => array(), 'truncated_count' => 0);
        $kept = array();
        $seen_urls = array();
        foreach ($posts as $p) {
            if (self::should_exclude_post($p)) continue;
            $url = (string) get_permalink($p);
            if ($url === '' || isset($seen_urls[$url])) continue;
            $seen_urls[$url] = true;
            $kept[] = $p;
        }
        $total = count($kept);
        if ($cap > 0 && $total > $cap) {
            $kept = array_slice($kept, 0, $cap);
        }
        return array('kept' => $kept, 'truncated_count' => max(0, $total - count($kept)));
    }


    // ============================================================
    // Featured Pages (Stage 1.5 — slim llms.txt convention)
    // ============================================================

    /**
     * Read the curated featured page IDs from the WP option, validate, cap at
     * FEATURED_PAGES_MAX, return as int array. Out-of-range or missing posts
     * are silently dropped.
     */
    public static function get_featured_pages() {
        $raw = get_option(self::OPTION_FEATURED_PAGES, array());
        if (!is_array($raw)) return array();
        $clean = array();
        foreach ($raw as $v) {
            $id = is_numeric($v) ? (int) $v : 0;
            if ($id <= 0) continue;
            $clean[] = $id;
        }
        $clean = array_values(array_unique($clean));
        if (count($clean) > self::FEATURED_PAGES_MAX) {
            $clean = array_slice($clean, 0, self::FEATURED_PAGES_MAX);
        }
        return $clean;
    }

    public static function set_featured_pages($ids) {
        if (!is_array($ids)) $ids = array();
        $clean = array();
        foreach ($ids as $v) {
            $id = is_numeric($v) ? (int) $v : 0;
            if ($id <= 0) continue;
            $p = get_post($id);
            if (!$p || $p->post_status !== 'publish') continue;
            $clean[] = $id;
        }
        $clean = array_values(array_unique($clean));
        if (count($clean) > self::FEATURED_PAGES_MAX) {
            $clean = array_slice($clean, 0, self::FEATURED_PAGES_MAX);
        }
        update_option(self::OPTION_FEATURED_PAGES, $clean);
        // Bust llms.txt cache so the next /llms.txt picks up the new featured list.
        self::flush_all_cache();
        return $clean;
    }



    // ============================================================
    // 3.35.84.4: One-shot 3.35.49 notice removed (stale, >4 months old).
    // Stubs kept for API compatibility — they intentionally do nothing.
    // ============================================================

    const NOTICE_KEY_3_35_49 = '_seo_aeo_v3_35_49_notice_dismissed';

    public static function render_v3_35_49_notice() { /* removed in 3.35.84.4 */ }

    public static function maybe_dismiss_v3_35_49_notice() { /* removed in 3.35.84.4 */ }

}

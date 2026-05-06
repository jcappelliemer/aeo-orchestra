<?php
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com
 *
 * Native SEO Output Renderer (v3.12.0 — Fase 1 strategia "switch da Yoast")
 *
 * Genera nel <head> i tag SEO core: <title>, <meta description>, OpenGraph,
 * Twitter Cards, canonical. Sostituisce funzionalmente Yoast/RankMath/AIOSEO
 * QUANDO sono disinstallati (auto-detect: se uno di loro è attivo, questa
 * classe NON emette nulla per evitare doppi tag).
 *
 * Setting che governano l'attivazione:
 *   - seo_aeo_native_output_enabled  (default '0') — toggle base
 *   - seo_aeo_native_output_override (default '0') — Override Mode (3.12.1):
 *     se ON, RIMUOVE i hook wp_head di Yoast/RankMath/AIOSEO e Orchestra emette
 *     comunque i suoi tag. Yoast resta installato (UI nel post editor utilizzabile),
 *     ma il frontend mostra SOLO Orchestra.
 *
 * Comportamento (3 modalità):
 *   - Override OFF + altro plugin SEO attivo  → silent (modalità co-pilot, default storico)
 *   - Override OFF + nessun plugin SEO + Enabled OFF → silent (sito senza meta description)
 *   - Override OFF + nessun plugin SEO + Enabled ON  → output completo (sostituto pulito)
 *   - Override ON + Enabled ON → strip Yoast/RankMath/AIOSEO da wp_head + output Orchestra
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Output_Renderer {

    const OPTION_ENABLED  = 'seo_aeo_native_output_enabled';
    const OPTION_OVERRIDE = 'seo_aeo_native_output_override';

    public function __construct() {
        // Output buffer wp_head: cattura TUTTO il <head> per pulire residui (Override Mode only).
        // Priority 0 = primo hook in assoluto. Lo chiudiamo con priority 99999 = ultimo.
        add_action('wp_head', array($this, 'maybe_start_head_buffer'), 0);

        // Hook ad altissima priorità così i nostri tag possono superare quelli di WP core.
        add_action('wp_head', array($this, 'render'), 1);

        // Rimuovi il <title> di default WP solo se stiamo davvero per emetterne uno noi
        add_action('init', array($this, 'maybe_remove_default_title_tag'), 20);

        // Override Mode: rimuovi i hook degli altri plugin SEO appena WP li ha caricati
        add_action('template_redirect', array($this, 'maybe_strip_other_seo_plugins'), 1);

        // Chiusura del buffer: ultima cosa di wp_head, dopo tutti i plugin/temi.
        add_action('wp_head', array($this, 'maybe_end_head_buffer'), 99999);
    }

    /**
     * True se il rendering native è attivo:
     *   setting ENABLED ON E (nessun altro plugin SEO attivo OPPURE Override Mode attivo)
     */
    public static function is_active() {
        if (get_option(self::OPTION_ENABLED, '0') !== '1') return false;
        if (self::detect_other_seo_plugin() && !self::is_override_mode()) return false;
        return true;
    }

    public static function is_override_mode() {
        return get_option(self::OPTION_OVERRIDE, '0') === '1';
    }

    /**
     * Rileva la presenza di altri plugin SEO attivi che già emettono <head> tags.
     * Ritorna il nome del plugin ('yoast', 'rankmath', 'aioseo') o false.
     */
    public static function detect_other_seo_plugin() {
        if (defined('WPSEO_VERSION') || class_exists('WPSEO_Frontend')) return 'yoast';
        if (class_exists('RankMath') || defined('RANK_MATH_VERSION')) return 'rankmath';
        if (defined('AIOSEO_VERSION') || class_exists('AIOSEO\\Plugin\\AIOSEO')) return 'aioseo';
        return false;
    }

    public function maybe_remove_default_title_tag() {
        if (self::is_active()) {
            // WP per default genera title tag via 'wp_head' priority 1 con add_theme_support('title-tag')
            // Lo rimuoviamo per evitare doppione (noi emetteremo il nostro a priority 1).
            remove_action('wp_head', '_wp_render_title_tag', 1);
        }
    }

    /**
     * Override Mode (3.12.1): rimuove i callback wp_head registrati da Yoast/RankMath/AIOSEO.
     * Iterazione sul $wp_filter globale: per ogni callback wp_head, se il nome (classe::metodo,
     * funzione, namespace) matcha pattern noti dei plugin SEO concorrenti, viene rimosso.
     *
     * IMPORTANTE: NON disinstalla nulla. Yoast continua a funzionare in admin (post editor box),
     * ma sul frontend i suoi tag <head> non vengono emessi. Orchestra prende il comando.
     * Reversibile: disattivando il toggle, basta una visita di pagina ad admin per ripristinare.
     */
    public function maybe_strip_other_seo_plugins() {
        if (!self::is_active()) return;
        if (!self::is_override_mode()) return;
        if (is_admin()) return; // mai in admin, solo frontend

        global $wp_filter;
        if (!isset($wp_filter['wp_head'])) return;

        $hook = $wp_filter['wp_head'];
        if (empty($hook->callbacks) || !is_array($hook->callbacks)) return;

        $stripped = array();
        foreach ($hook->callbacks as $priority => $callbacks) {
            if (!is_array($callbacks)) continue;
            foreach ($callbacks as $cb_id => $cb_data) {
                $name = $this->callback_name($cb_data['function'] ?? null);
                if ($this->is_competitor_seo_callback($name)) {
                    remove_action('wp_head', $cb_data['function'], $priority);
                    $stripped[] = "[$priority] $name";
                }
            }
        }

        // Rimuovo anche i hook su action specifiche dei plugin (es. wpseo_head, rank_math/head)
        foreach (array('wpseo_head', 'rank_math/head', 'aioseo_head') as $custom_action) {
            if (isset($wp_filter[$custom_action])) {
                remove_all_actions($custom_action);
                $stripped[] = "[action] $custom_action (cleared)";
            }
        }

        if (!empty($stripped)) {
        }
    }

    /**
     * Output buffer: cattura tutto il contenuto emesso in wp_head per fare pulizia
     * a posteriori dei residui dei plugin SEO (es. <meta name="description"> duplicati
     * emessi da hook che non abbiamo individuato col pattern matching).
     * Attivo SOLO in Override Mode (in modalità "pulita" non serve, non c'è altro plugin).
     */
    public function maybe_start_head_buffer() {
        if (!self::is_active()) return;
        if (!self::is_override_mode()) return;
        if (is_admin()) return;
        if (defined('REST_REQUEST') && REST_REQUEST) return;
        ob_start();
    }

    public function maybe_end_head_buffer() {
        if (!self::is_active()) return;
        if (!self::is_override_mode()) return;
        if (is_admin()) return;
        if (defined('REST_REQUEST') && REST_REQUEST) return;
        // Verifica che ob_start sia stato effettivamente aperto da noi
        if (ob_get_level() === 0) return;

        $html = ob_get_clean();
        $cleaned = $this->clean_seo_residuals($html);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $cleaned;
    }

    /**
     * Pulisce dal buffer di wp_head i residui dei plugin SEO concorrenti che hanno
     * "bypassato" il pattern matching dei hook (es. Yoast 22+ con namespaced classes,
     * presenter pattern, tag emessi da action custom, etc.).
     *
     * Strategie applicate (in ordine):
     *  1. Dedupe <meta name="description">: tiene solo la PRIMA occorrenza (i nostri tag
     *     sono emessi a priority 1, quindi sono i primi → tengono il posto, gli altri vanno via).
     *  2. Dedupe <link rel="canonical">: idem.
     *  3. Dedupe <meta property="og:description"> e og:title.
     *  4. Strip <script ... yoast-schema-graph ...>...</script> (residui schema Yoast).
     *  5. Strip <!-- This site is optimized with Yoast SEO -->.
     */
    private function clean_seo_residuals($html) {
        if (empty($html)) return $html;

        // Dedupe meta name=description / canonical / og:description / og:title (case-insensitive)
        $dedupe_patterns = array(
            '/<meta\s+name=["\']description["\'][^>]*\/?>\s*/i',
            '/<link\s+rel=["\']canonical["\'][^>]*\/?>\s*/i',
            '/<meta\s+property=["\']og:description["\'][^>]*\/?>\s*/i',
            '/<meta\s+property=["\']og:title["\'][^>]*\/?>\s*/i',
            '/<meta\s+property=["\']og:url["\'][^>]*\/?>\s*/i',
            '/<meta\s+property=["\']og:type["\'][^>]*\/?>\s*/i',
            '/<meta\s+property=["\']og:image["\'][^>]*\/?>\s*/i',
            '/<meta\s+name=["\']twitter:card["\'][^>]*\/?>\s*/i',
            '/<meta\s+name=["\']twitter:title["\'][^>]*\/?>\s*/i',
            '/<meta\s+name=["\']twitter:description["\'][^>]*\/?>\s*/i',
            '/<meta\s+name=["\']twitter:image["\'][^>]*\/?>\s*/i',
            '/<meta\s+name=["\']robots["\'][^>]*\/?>\s*/i',
            '/<meta\s+property=["\']article:published_time["\'][^>]*\/?>\s*/i',
            '/<meta\s+property=["\']article:modified_time["\'][^>]*\/?>\s*/i',
            '/<meta\s+property=["\']article:author["\'][^>]*\/?>\s*/i',
        );
        foreach ($dedupe_patterns as $pattern) {
            // Trova tutte le occorrenze
            if (preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
                if (count($matches[0]) > 1) {
                    // Rimuovi dalla SECONDA in poi (iterando al contrario per non invalidare gli offset)
                    for ($i = count($matches[0]) - 1; $i >= 1; $i--) {
                        $tag = $matches[0][$i][0];
                        $offset = $matches[0][$i][1];
                        $html = substr($html, 0, $offset) . substr($html, $offset + strlen($tag));
                    }
                }
            }
        }

        // Strip Yoast schema-graph residuo (se per qualche motivo è arrivato qui)
        $html = preg_replace('/<script\s+type=["\']application\/ld\+json["\'][^>]*class=["\']yoast-schema-graph["\'][^>]*>.*?<\/script>\s*/is', '', $html);

        // Strip commenti / generator tags Yoast/RankMath/AIOSEO
        $html = preg_replace('/<!--\s*This site is optimized with the Yoast SEO plugin.*?-->\s*/is', '', $html);
        $html = preg_replace('/<!--\s*\/?\s*Yoast SEO plugin.*?-->\s*/is', '', $html);
        $html = preg_replace('/<meta\s+name=["\']generator["\']\s+content=["\'][^"\']*(yoast|rank ?math|aioseo|all in one seo)[^"\']*["\'][^>]*\/?>\s*/i', '', $html);

        return $html;
    }

    /**
     * True se il nome del callback appartiene a Yoast/RankMath/AIOSEO o varianti note.
     */
    private function is_competitor_seo_callback($name) {
        if (!$name) return false;
        $patterns = array(
            'wpseo', 'yoast', 'WPSEO_', 'Yoast\\\\',
            'rank_math', 'rankmath', 'RankMath\\\\', 'rank-math',
            'aioseo', 'AIOSEO\\\\', 'all_in_one_seo', 'all-in-one-seo',
        );
        foreach ($patterns as $p) {
            if (stripos($name, $p) !== false) return true;
        }
        return false;
    }

    /**
     * Estrae un nome stringa dal callback PHP per match pattern.
     */
    private function callback_name($callback) {
        if (is_string($callback)) return $callback;
        if (is_array($callback) && isset($callback[0], $callback[1])) {
            $obj = $callback[0];
            $method = $callback[1];
            if (is_object($obj)) return get_class($obj) . '::' . $method;
            return $obj . '::' . $method;
        }
        if ($callback instanceof Closure) {
            // Le closure non hanno nome riconoscibile; tentiamo via reflection per file path.
            try {
                $r = new ReflectionFunction($callback);
                return $r->getFileName() ?: 'closure';
            } catch (Throwable $e) {
                return 'closure';
            }
        }
        if (is_object($callback) && method_exists($callback, '__invoke')) {
            return get_class($callback) . '::__invoke';
        }
        return '';
    }

    public function render() {
        if (!self::is_active()) return;

        $data = $this->resolve_current_context();
        if (!$data) return;

        echo "\n<!-- AEO Orchestra · Native Output (v" . esc_html(SEO_AEO_VERSION) . ") -->\n";

        // ─── Title ───────────────────────────────────────────────────
        if (!empty($data['title'])) {
            echo '<title>' . esc_html($data['title']) . "</title>\n";
        }

        // ─── Meta description ────────────────────────────────────────
        if (!empty($data['description'])) {
            echo '<meta name="description" content="' . esc_attr($data['description']) . "\" />\n";
        }

        // ─── Canonical ───────────────────────────────────────────────
        if (!empty($data['canonical'])) {
            echo '<link rel="canonical" href="' . esc_url($data['canonical']) . "\" />\n";
        }

        // ─── Robots ──────────────────────────────────────────────────
        if (!empty($data['noindex'])) {
            echo "<meta name=\"robots\" content=\"noindex, nofollow\" />\n";
        } else {
            echo "<meta name=\"robots\" content=\"index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1\" />\n";
        }

        // ─── OpenGraph ───────────────────────────────────────────────
        $og_title = $data['title'] ?? '';
        $og_desc = $data['description'] ?? '';
        $og_url = $data['canonical'] ?? home_url();
        $og_type = $data['og_type'] ?? 'website';
        $og_site = get_bloginfo('name');

        if ($og_title)   echo '<meta property="og:title" content="' . esc_attr($og_title) . "\" />\n";
        if ($og_desc)    echo '<meta property="og:description" content="' . esc_attr($og_desc) . "\" />\n";
        echo '<meta property="og:url" content="' . esc_url($og_url) . "\" />\n";
        echo '<meta property="og:type" content="' . esc_attr($og_type) . "\" />\n";
        echo '<meta property="og:site_name" content="' . esc_attr($og_site) . "\" />\n";
        echo "<meta property=\"og:locale\" content=\"" . esc_attr(get_locale()) . "\" />\n";

        if (!empty($data['image'])) {
            echo '<meta property="og:image" content="' . esc_url($data['image']) . "\" />\n";
            if (!empty($data['image_width']))  echo '<meta property="og:image:width" content="' . intval($data['image_width']) . "\" />\n";
            if (!empty($data['image_height'])) echo '<meta property="og:image:height" content="' . intval($data['image_height']) . "\" />\n";
            if (!empty($data['image_alt']))    echo '<meta property="og:image:alt" content="' . esc_attr($data['image_alt']) . "\" />\n";
        }

        // Article-specific OG tags
        if ($og_type === 'article' && !empty($data['post'])) {
            $p = $data['post'];
            if (!empty($p->post_date_gmt)) {
                echo '<meta property="article:published_time" content="' . esc_attr(mysql2date('c', $p->post_date_gmt, false)) . "\" />\n";
            }
            if (!empty($p->post_modified_gmt)) {
                echo '<meta property="article:modified_time" content="' . esc_attr(mysql2date('c', $p->post_modified_gmt, false)) . "\" />\n";
            }
            $author = $p->post_author ? get_the_author_meta('display_name', $p->post_author) : '';
            if ($author) echo '<meta property="article:author" content="' . esc_attr($author) . "\" />\n";
        }

        // ─── Twitter Cards ───────────────────────────────────────────
        $twitter_card = !empty($data['image']) ? 'summary_large_image' : 'summary';
        echo '<meta name="twitter:card" content="' . esc_attr($twitter_card) . "\" />\n";
        if ($og_title) echo '<meta name="twitter:title" content="' . esc_attr($og_title) . "\" />\n";
        if ($og_desc)  echo '<meta name="twitter:description" content="' . esc_attr($og_desc) . "\" />\n";
        if (!empty($data['image'])) {
            echo '<meta name="twitter:image" content="' . esc_url($data['image']) . "\" />\n";
        }

        echo "<!-- /AEO Orchestra · Native Output -->\n\n";
    }

    /**
     * Risolve il contesto della pagina corrente in un array unificato:
     *   title, description, canonical, image, og_type, noindex, post (per article OG)
     */
    private function resolve_current_context() {
        if (is_singular()) {
            $post = get_queried_object();
            if (!$post || !($post instanceof WP_Post)) return null;

            // Read from bridge (priorità Yoast keys → native keys)
            $meta = class_exists('SEO_AEO_Engine_Bridge')
                ? SEO_AEO_Engine_Bridge::read_meta($post->ID)
                : array();

            $title = !empty($meta['meta_title']) ? $meta['meta_title'] : get_the_title($post);
            // Append site name al title se non già presente (pattern Yoast: "Title - Site")
            $site_name = get_bloginfo('name');
            if ($site_name && stripos($title, $site_name) === false) {
                $title = $title . ' - ' . $site_name;
            }

            $description = !empty($meta['meta_description'])
                ? $meta['meta_description']
                : wp_trim_words(wp_strip_all_tags(strip_shortcodes($post->post_content)), 30, '…');

            $canonical = get_permalink($post);
            $og_type = ($post->post_type === 'post') ? 'article' : 'website';

            $image_data = $this->resolve_featured_image($post->ID);

            return array(
                'title' => $title,
                'description' => $description,
                'canonical' => $canonical,
                'noindex' => ($post->post_status !== 'publish'),
                'image' => $image_data['url'] ?? '',
                'image_width' => $image_data['width'] ?? 0,
                'image_height' => $image_data['height'] ?? 0,
                'image_alt' => $image_data['alt'] ?? '',
                'og_type' => $og_type,
                'post' => $post,
            );
        }

        if (is_front_page() || is_home()) {
            return array(
                'title' => get_bloginfo('name') . ' - ' . get_bloginfo('description'),
                'description' => get_bloginfo('description'),
                'canonical' => home_url('/'),
                'og_type' => 'website',
                'image' => $this->resolve_site_logo(),
            );
        }

        if (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if (!$term) return null;
            $title = single_term_title('', false) . ' - ' . get_bloginfo('name');
            $description = term_description($term->term_id);
            $description = $description ? wp_trim_words(wp_strip_all_tags($description), 30, '…') : '';
            return array(
                'title' => $title,
                'description' => $description,
                'canonical' => get_term_link($term),
                'og_type' => 'website',
            );
        }

        if (is_author()) {
            $author = get_queried_object();
            return array(
                'title' => $author->display_name . ' - ' . get_bloginfo('name'),
                'description' => $author->description ? wp_trim_words(wp_strip_all_tags($author->description), 30, '…') : '',
                'canonical' => get_author_posts_url($author->ID),
                'og_type' => 'profile',
            );
        }

        if (is_search()) {
            return array(
                'title' => 'Risultati per "' . esc_html(get_search_query()) . '" - ' . get_bloginfo('name'),
                'description' => 'Risultati di ricerca su ' . get_bloginfo('name'),
                'canonical' => get_search_link(),
                'noindex' => true,
                'og_type' => 'website',
            );
        }

        if (is_404()) {
            return array(
                'title' => 'Pagina non trovata - ' . get_bloginfo('name'),
                'description' => 'La pagina richiesta non esiste.',
                'canonical' => home_url('/'),
                'noindex' => true,
                'og_type' => 'website',
            );
        }

        // Fallback generico per archivi non coperti
        return array(
            'title' => wp_get_document_title(),
            'description' => get_bloginfo('description'),
            'canonical' => home_url(add_query_arg(array(), $GLOBALS['wp']->request)),
            'og_type' => 'website',
        );
    }

    private function resolve_featured_image($post_id) {
        $thumb_id = get_post_thumbnail_id($post_id);
        if (!$thumb_id) return array();
        $img = wp_get_attachment_image_src($thumb_id, 'large');
        if (!$img) return array();
        return array(
            'url' => $img[0],
            'width' => $img[1],
            'height' => $img[2],
            'alt' => get_post_meta($thumb_id, '_wp_attachment_image_alt', true),
        );
    }

    private function resolve_site_logo() {
        $logo_id = get_theme_mod('custom_logo');
        if ($logo_id) {
            $img = wp_get_attachment_image_src($logo_id, 'large');
            if ($img) return $img[0];
        }
        return '';
    }
}

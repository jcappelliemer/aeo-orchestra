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
        // 3.35.65: emergency disable kill-switch (set in wp-config.php)
        if (defined('AEO_ORCHESTRA_EMERGENCY_DISABLE') && AEO_ORCHESTRA_EMERGENCY_DISABLE) return false;
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
        // 3.35.59 (A): canonical may be null (disabled by strategy or per-page override)
        if (!empty($data['canonical'])) {
            echo '<link rel="canonical" href="' . esc_url($data['canonical']) . '" />' . "\n";
        }

        // ─── Robots ──────────────────────────────────────────────────
        // 3.35.59 (F): robots from resolved settings (post_types + archives + paginated rules)
        if (!empty($data['robots'])) {
            echo '<meta name="robots" content="' . esc_attr($data['robots']) . '" />' . "\n";
        } elseif (!empty($data['noindex'])) {
            echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
        } else {
            echo '<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />' . "\n";
        }

        // ─── OpenGraph (3.35.61: wires da seo_aeo_og_settings) ─────────
        $og_settings = self::get_og_settings(); if (!is_array($og_settings)) $og_settings = array();
        // 3.35.69 G: per-page OG title/desc override has highest priority
        $og_title = $data['title'] ?? '';
        $og_desc = $data['description'] ?? '';
        if (!empty($data['post']) && is_object($data['post']) && !empty($data['post']->ID)) {
            $page_og_title = (string) get_post_meta($data['post']->ID, '_orch_og_title', true);
            if ($page_og_title !== '') $og_title = $page_og_title;
            $page_og_desc = (string) get_post_meta($data['post']->ID, '_orch_og_description', true);
            if ($page_og_desc !== '') $og_desc = $page_og_desc;
        }
        $og_url = $data['canonical'] ?? home_url();
        $og_type = $data['og_type'] ?? 'website';

        // site_name: override settings → bloginfo
        $og_site = !empty($og_settings['site_name_override']) ? (string) $og_settings['site_name_override'] : get_bloginfo('name');
        // locale: override settings → get_locale (con _ → -, formato OG)
        $og_locale = !empty($og_settings['locale_override']) ? (string) $og_settings['locale_override'] : get_locale();

        if ($og_title)   echo '<meta property="og:title" content="' . esc_attr($og_title) . '" />' . "\n";
        if ($og_desc)    echo '<meta property="og:description" content="' . esc_attr($og_desc) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url($og_url) . '" />' . "\n";
        echo '<meta property="og:type" content="' . esc_attr($og_type) . '" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr($og_site) . '" />' . "\n";
        echo '<meta property="og:locale" content="' . esc_attr($og_locale) . '" />' . "\n";

        if (!empty($data['image'])) {
            echo '<meta property="og:image" content="' . esc_url($data['image']) . '" />' . "\n";
            if (!empty($data['image_width']))  echo '<meta property="og:image:width" content="' . intval($data['image_width']) . '" />' . "\n";
            if (!empty($data['image_height'])) echo '<meta property="og:image:height" content="' . intval($data['image_height']) . '" />' . "\n";
            if (!empty($data['image_alt']))    echo '<meta property="og:image:alt" content="' . esc_attr($data['image_alt']) . '" />' . "\n";
        }

        // Article-specific OG tags (toggle dai settings)
        if ($og_type === 'article' && !empty($data['post'])) {
            $p = $data['post'];
            // emit_article_published default true
            if (!isset($og_settings['emit_article_published']) || $og_settings['emit_article_published']) {
                if (!empty($p->post_date_gmt)) {
                    echo '<meta property="article:published_time" content="' . esc_attr(mysql2date('c', $p->post_date_gmt, false)) . '" />' . "\n";
                }
                if (!empty($p->post_modified_gmt)) {
                    echo '<meta property="article:modified_time" content="' . esc_attr(mysql2date('c', $p->post_modified_gmt, false)) . '" />' . "\n";
                }
            }
            // emit_article_author default true
            if (!isset($og_settings['emit_article_author']) || $og_settings['emit_article_author']) {
                $author = $p->post_author ? get_the_author_meta('display_name', $p->post_author) : '';
                if ($author) echo '<meta property="article:author" content="' . esc_attr($author) . '" />' . "\n";
            }
            // emit_article_section: categoria primaria (3.35.61.1: defensive)
            if ((!isset($og_settings['emit_article_section']) || $og_settings['emit_article_section']) && isset($p->ID)) {
                $cats = get_the_category($p->ID);
                if (!empty($cats) && !is_wp_error($cats)) {
                    $primary = $cats[0];
                    if (isset($primary->name)) {
                        echo '<meta property="article:section" content="' . esc_attr($primary->name) . '" />' . "\n";
                    }
                }
            }
            // emit_article_tag: WP tags (3.35.61.1: defensive)
            if ((!isset($og_settings['emit_article_tag']) || $og_settings['emit_article_tag']) && isset($p->ID)) {
                $tags = get_the_tags($p->ID);
                if (!empty($tags) && !is_wp_error($tags)) {
                    foreach ($tags as $tag) {
                        if (isset($tag->name)) {
                            echo '<meta property="article:tag" content="' . esc_attr($tag->name) . '" />' . "\n";
                        }
                    }
                }
            }
        }

        // ─── Twitter Cards (3.35.61: wires da seo_aeo_twitter_settings) ────
        $tw_settings = self::get_twitter_settings();
        // 3.35.69 G: per-page Twitter card override
        $tw_card = !empty($tw_settings['card_type']) ? $tw_settings['card_type'] : (!empty($data['image']) ? 'summary_large_image' : 'summary');
        if (!empty($data['post']) && is_object($data['post']) && !empty($data['post']->ID)) {
            $page_tw_card = (string) get_post_meta($data['post']->ID, '_orch_twitter_card', true);
            if ($page_tw_card !== '') $tw_card = $page_tw_card;
        }
        // Per-page Twitter title/description overrides (used in echo block below — set vars now)
        $tw_title_page = ''; $tw_desc_page = '';
        if (!empty($data['post']) && is_object($data['post']) && !empty($data['post']->ID)) {
            $tw_title_page = (string) get_post_meta($data['post']->ID, '_orch_twitter_title', true);
            $tw_desc_page  = (string) get_post_meta($data['post']->ID, '_orch_twitter_description', true);
        }
        echo '<meta name="twitter:card" content="' . esc_attr($tw_card) . '" />' . "\n";
        if (!empty($tw_settings['site_handle'])) {
            $handle = $tw_settings['site_handle'];
            if (substr($handle, 0, 1) !== '@') $handle = '@' . $handle;
            echo '<meta name="twitter:site" content="' . esc_attr($handle) . '" />' . "\n";
        }
        // twitter:creator
        $creator = self::_emit_twitter_creator($tw_settings, $data);
        if ($creator) echo '<meta name="twitter:creator" content="' . esc_attr($creator) . '" />' . "\n";

        // 3.35.69 G: twitter:title/description fall back through per-page → og → ''
        $tw_title_emit = $tw_title_page !== '' ? $tw_title_page : $og_title;
        $tw_desc_emit  = $tw_desc_page  !== '' ? $tw_desc_page  : $og_desc;
        if ($tw_title_emit) echo '<meta name="twitter:title" content="' . esc_attr($tw_title_emit) . '" />' . "\n";
        if ($tw_desc_emit)  echo '<meta name="twitter:description" content="' . esc_attr($tw_desc_emit) . '" />' . "\n";
        if (!empty($data['image'])) {
            echo '<meta name="twitter:image" content="' . esc_url($data['image']) . '" />' . "\n";
            if (!empty($data['image_alt'])) echo '<meta name="twitter:image:alt" content="' . esc_attr($data['image_alt']) . '" />' . "\n";
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

            // Per-page title override has highest priority
            $page_title_override = (string) get_post_meta($post->ID, '_orch_title_override', true);
            $base_title = $page_title_override !== '' ? $page_title_override : (
                !empty($meta['meta_title']) ? $meta['meta_title'] : get_the_title($post)
            );

            // 3.35.59 (B): apply title format template per CPT
            $title = $this->_apply_title_format($base_title, array('type' => 'singular', 'post_type' => $post->post_type));

            // 3.35.59 (C): meta description fallback chain
            $page_desc_override = (string) get_post_meta($post->ID, '_orch_meta_desc_override', true);
            $description = $page_desc_override !== '' ? $page_desc_override : $this->_resolve_meta_description($post->ID, $meta);

            // 3.35.59 (A): canonical strategy
            $page_canonical_override = (string) get_post_meta($post->ID, '_orch_canonical_override', true);
            $page_canonical_disabled = get_post_meta($post->ID, '_orch_canonical_disabled', true);
            if ($page_canonical_disabled === '1' || $page_canonical_disabled === 1 || $page_canonical_disabled === true) {
                $canonical = null;
            } elseif ($page_canonical_override !== '') {
                $canonical = $page_canonical_override;
            } else {
                $canonical = $this->_apply_canonical_strategy(get_permalink($post), $post);
            }

            // 3.35.59 (D): og_type per CPT
            $og_settings = self::get_og_settings();
            $og_type = isset($og_settings['og_type_per_cpt'][$post->post_type])
                ? $og_settings['og_type_per_cpt'][$post->post_type]
                : (($post->post_type === 'post') ? 'article' : 'website');

            $image_data = $this->resolve_featured_image($post->ID);
            // 3.35.62: per-page OG image override has highest priority
            $page_og_image = (string) get_post_meta($post->ID, '_orch_og_image_override', true);
            if ($page_og_image !== '') {
                $image_url = $page_og_image;
            } else {
                $image_url = !empty($image_data['url']) ? $image_data['url'] : (
                    !empty($og_settings['image_fallback']) ? $og_settings['image_fallback'] : ''
                );
            }

            return array(
                'title' => $title,
                'description' => $description,
                'canonical' => $canonical,
                'noindex' => ($post->post_status !== 'publish'),
                'robots' => $this->_resolve_robots_directives(array('type' => 'singular', 'post_type' => $post->post_type, 'post_id' => $post->ID)),
                'image' => $image_url,
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


    // ============================================================
    // 3.35.59 (Stage 2.5 Output<head>) — Configurable settings layer
    // ============================================================

    // --- A. Canonical strategy ---
    public static function get_canonical_settings() {
        $defaults = array(
            'strategy'           => 'auto',          // auto | force_same_domain | custom
            'canonical_domain'   => '',              // e.g. 'https://www.solarisfilms.it' (empty = home_url)
            'custom_prefix'      => '',
            'trailing_slash'     => 'wp_default',    // wp_default | with | without
            'paginated_first'    => true,            // canonical of /page/2 → page 1
            'disabled_post_types'=> array(),         // CPTs where no canonical is emitted
        );
        $stored = self::_read_option_json('seo_aeo_canonical_settings');
        return array_merge($defaults, $stored);
    }

    public static function update_canonical_settings($payload) {
        if (!is_array($payload)) return false;
        $cur = self::get_canonical_settings();
        $allowed_strats = array('auto', 'force_same_domain', 'custom');
        $allowed_slashes = array('wp_default', 'with', 'without');
        if (isset($payload['strategy']) && in_array($payload['strategy'], $allowed_strats, true)) $cur['strategy'] = $payload['strategy'];
        if (isset($payload['canonical_domain'])) $cur['canonical_domain'] = esc_url_raw((string) $payload['canonical_domain']);
        if (isset($payload['custom_prefix'])) $cur['custom_prefix'] = esc_url_raw((string) $payload['custom_prefix']);
        if (isset($payload['trailing_slash']) && in_array($payload['trailing_slash'], $allowed_slashes, true)) $cur['trailing_slash'] = $payload['trailing_slash'];
        if (isset($payload['paginated_first'])) $cur['paginated_first'] = (bool) $payload['paginated_first'];
        if (isset($payload['disabled_post_types']) && is_array($payload['disabled_post_types'])) {
            $cur['disabled_post_types'] = array_values(array_map('sanitize_key', $payload['disabled_post_types']));
        }
        update_option('seo_aeo_canonical_settings', wp_json_encode($cur), false);
        return true;
    }

    // --- B. Title format ---
    public static function get_title_formats() {
        $defaults = array(
            'post_types'   => array(),  // {post_type: template}
            'archives'     => array(),  // {category, tag, author, search, date}: template
            'separator'    => '—',      // em-dash by default
            'trim_at_60'   => true,
        );
        $stored = self::_read_option_json('seo_aeo_title_formats');
        return array_merge($defaults, $stored);
    }

    public static function update_title_formats($payload) {
        if (!is_array($payload)) return false;
        $cur = self::get_title_formats();
        if (isset($payload['post_types']) && is_array($payload['post_types'])) {
            $clean = array();
            foreach ($payload['post_types'] as $pt => $tpl) {
                $pt = sanitize_key((string) $pt);
                $tpl = sanitize_text_field((string) $tpl);
                if ($pt !== '' && $tpl !== '') $clean[$pt] = $tpl;
            }
            $cur['post_types'] = $clean;
        }
        if (isset($payload['archives']) && is_array($payload['archives'])) {
            $clean = array();
            foreach ($payload['archives'] as $arc => $tpl) {
                $arc = sanitize_key((string) $arc);
                $tpl = sanitize_text_field((string) $tpl);
                if ($arc !== '' && $tpl !== '') $clean[$arc] = $tpl;
            }
            $cur['archives'] = $clean;
        }
        if (isset($payload['separator'])) {
            $sep = (string) $payload['separator'];
            // accept only safe single-char + a few sequences
            if (in_array($sep, array('—', '|', '·', '-', '/', '·', '–'), true)) $cur['separator'] = $sep;
        }
        if (isset($payload['trim_at_60'])) $cur['trim_at_60'] = (bool) $payload['trim_at_60'];
        update_option('seo_aeo_title_formats', wp_json_encode($cur), false);
        return true;
    }

    // --- C. Meta description settings ---
    public static function get_meta_desc_settings() {
        $defaults = array(
            'priority'        => array('orch', 'yoast', 'rankmath', 'aioseo', 'acf', 'excerpt', 'content'),
            'min_len'         => 120,
            'max_len'         => 160,
            'truncation'      => 'word_boundary',  // word_boundary | hard_cap | sentence_boundary
            'acf_field'       => 'meta_description',
            'archive_pattern' => 'Articoli su {{archive_name}} di {{site_name}}.',
        );
        $stored = self::_read_option_json('seo_aeo_meta_desc_settings');
        return array_merge($defaults, $stored);
    }

    public static function update_meta_desc_settings($payload) {
        if (!is_array($payload)) return false;
        $cur = self::get_meta_desc_settings();
        $valid_sources = array('orch', 'yoast', 'rankmath', 'aioseo', 'acf', 'excerpt', 'content');
        if (isset($payload['priority']) && is_array($payload['priority'])) {
            $clean = array_values(array_filter(array_map(function ($s) {
                return is_string($s) ? sanitize_key($s) : '';
            }, $payload['priority']), function ($s) use ($valid_sources) {
                return in_array($s, $valid_sources, true);
            }));
            if (!empty($clean)) $cur['priority'] = $clean;
        }
        if (isset($payload['min_len'])) $cur['min_len'] = max(50, min(280, (int) $payload['min_len']));
        if (isset($payload['max_len'])) $cur['max_len'] = max(80, min(320, (int) $payload['max_len']));
        if (isset($payload['truncation']) && in_array($payload['truncation'], array('word_boundary', 'hard_cap', 'sentence_boundary'), true)) {
            $cur['truncation'] = $payload['truncation'];
        }
        if (isset($payload['acf_field'])) $cur['acf_field'] = sanitize_key((string) $payload['acf_field']);
        if (isset($payload['archive_pattern'])) $cur['archive_pattern'] = sanitize_textarea_field((string) $payload['archive_pattern']);
        update_option('seo_aeo_meta_desc_settings', wp_json_encode($cur), false);
        return true;
    }

    // --- D. OpenGraph settings ---
    public static function get_og_settings() {
        $defaults = array(
            'image_fallback'      => '',  // URL
            'locale_override'     => '',  // empty = auto from get_locale()
            'site_name_override'  => '',  // empty = auto from get_bloginfo('name')
            'og_type_per_cpt'     => array(),  // {pt: og_type}
            'emit_article_author'      => true,
            'emit_article_published'   => true,
            'emit_article_section'     => true,
            'emit_article_tag'         => true,
        );
        $stored = self::_read_option_json('seo_aeo_og_settings');
        return array_merge($defaults, $stored);
    }

    public static function update_og_settings($payload) {
        if (!is_array($payload)) return false;
        $cur = self::get_og_settings();
        if (isset($payload['image_fallback'])) $cur['image_fallback'] = esc_url_raw((string) $payload['image_fallback']);
        if (isset($payload['locale_override'])) $cur['locale_override'] = sanitize_text_field((string) $payload['locale_override']);
        if (isset($payload['site_name_override'])) $cur['site_name_override'] = sanitize_text_field((string) $payload['site_name_override']);
        if (isset($payload['og_type_per_cpt']) && is_array($payload['og_type_per_cpt'])) {
            $clean = array();
            foreach ($payload['og_type_per_cpt'] as $pt => $type) {
                $pt = sanitize_key((string) $pt);
                $type = sanitize_text_field((string) $type);
                if ($pt !== '' && $type !== '') $clean[$pt] = $type;
            }
            $cur['og_type_per_cpt'] = $clean;
        }
        foreach (array('emit_article_author', 'emit_article_published', 'emit_article_section', 'emit_article_tag') as $f) {
            if (isset($payload[$f])) $cur[$f] = (bool) $payload[$f];
        }
        update_option('seo_aeo_og_settings', wp_json_encode($cur), false);
        return true;
    }

    // --- E. Twitter Cards settings ---

    /**
     * 3.35.61.1: Resolve twitter:creator handle based on creator_mode setting.
     *  - 'disabled' -> null (skip emit)
     *  - 'fixed'    -> use creator_handle from settings
     *  - 'auto'     -> try WP user_meta 'twitter' on the post author (fallback site_handle)
     */
    private static function _emit_twitter_creator($tw_settings, $data) {
        if (!is_array($tw_settings)) $tw_settings = array();
        if (!is_array($data)) $data = array();
        $mode = isset($tw_settings['creator_mode']) ? (string) $tw_settings['creator_mode'] : 'auto';
        if ($mode === 'disabled') return null;

        $handle = '';
        if ($mode === 'fixed') {
            $handle = isset($tw_settings['creator_handle']) ? (string) $tw_settings['creator_handle'] : '';
        } elseif ($mode === 'auto') {
            if (!empty($data['post']) && is_object($data['post']) && !empty($data['post']->post_author)) {
                $maybe = (string) get_the_author_meta('twitter', $data['post']->post_author);
                if ($maybe !== '') $handle = $maybe;
            }
            if ($handle === '' && !empty($tw_settings['site_handle'])) {
                $handle = (string) $tw_settings['site_handle'];
            }
        }
        $handle = trim($handle);
        if ($handle === '') return null;
        if (substr($handle, 0, 1) !== '@') $handle = '@' . $handle;
        $handle = preg_replace('#^@?https?://(?:www\.)?(?:twitter\.com|x\.com)/#i', '@', $handle);
        return $handle;
    }

    public static function get_twitter_settings() {
        $defaults = array(
            'card_type'      => 'summary_large_image',
            'site_handle'    => '',
            'creator_mode'   => 'auto',  // auto | fixed | disabled
            'creator_handle' => '',
        );
        $stored = self::_read_option_json('seo_aeo_twitter_settings');
        return array_merge($defaults, $stored);
    }

    public static function update_twitter_settings($payload) {
        if (!is_array($payload)) return false;
        $cur = self::get_twitter_settings();
        $valid_cards = array('summary', 'summary_large_image', 'player', 'app');
        if (isset($payload['card_type']) && in_array($payload['card_type'], $valid_cards, true)) $cur['card_type'] = $payload['card_type'];
        if (isset($payload['site_handle'])) $cur['site_handle'] = sanitize_text_field((string) $payload['site_handle']);
        if (isset($payload['creator_mode']) && in_array($payload['creator_mode'], array('auto', 'fixed', 'disabled'), true)) $cur['creator_mode'] = $payload['creator_mode'];
        if (isset($payload['creator_handle'])) $cur['creator_handle'] = sanitize_text_field((string) $payload['creator_handle']);
        update_option('seo_aeo_twitter_settings', wp_json_encode($cur), false);
        return true;
    }

    // --- F. Robots settings ---
    public static function get_robots_settings() {
        $defaults = array(
            'post_types' => array(),  // {pt: {index:bool, follow:bool, max_image:'large', max_snippet:-1}}
            'archives'   => array(
                'category' => array('index' => true,  'follow' => true),
                'tag'      => array('index' => true,  'follow' => true),
                'author'   => array('index' => false, 'follow' => true),
                'date'     => array('index' => false, 'follow' => true),
                'search'   => array('index' => false, 'follow' => true),
                'not_found'=> array('index' => false, 'follow' => true),
            ),
            'rules' => array(
                'noindex_paginated'   => true,
                'noindex_low_words'   => false,
                'low_words_threshold' => 300,
            ),
        );
        $stored = self::_read_option_json('seo_aeo_robots_settings');
        $merged = array_replace_recursive($defaults, $stored);
        return $merged;
    }

    public static function update_robots_settings($payload) {
        if (!is_array($payload)) return false;
        $cur = self::get_robots_settings();
        if (isset($payload['post_types']) && is_array($payload['post_types'])) {
            $clean = array();
            foreach ($payload['post_types'] as $pt => $cfg) {
                $pt = sanitize_key((string) $pt);
                if ($pt === '' || !is_array($cfg)) continue;
                $entry = array();
                $entry['index']  = isset($cfg['index'])  ? (bool) $cfg['index']  : true;
                $entry['follow'] = isset($cfg['follow']) ? (bool) $cfg['follow'] : true;
                if (isset($cfg['max_image']))   $entry['max_image']   = sanitize_text_field((string) $cfg['max_image']);
                if (isset($cfg['max_snippet'])) $entry['max_snippet'] = (int) $cfg['max_snippet'];
                $clean[$pt] = $entry;
            }
            $cur['post_types'] = $clean;
        }
        if (isset($payload['archives']) && is_array($payload['archives'])) {
            foreach ($payload['archives'] as $arc => $cfg) {
                $arc = sanitize_key((string) $arc);
                if (!isset($cur['archives'][$arc])) $cur['archives'][$arc] = array();
                if (isset($cfg['index']))  $cur['archives'][$arc]['index']  = (bool) $cfg['index'];
                if (isset($cfg['follow'])) $cur['archives'][$arc]['follow'] = (bool) $cfg['follow'];
            }
        }
        if (isset($payload['rules']) && is_array($payload['rules'])) {
            if (isset($payload['rules']['noindex_paginated']))   $cur['rules']['noindex_paginated']   = (bool) $payload['rules']['noindex_paginated'];
            if (isset($payload['rules']['noindex_low_words']))   $cur['rules']['noindex_low_words']   = (bool) $payload['rules']['noindex_low_words'];
            if (isset($payload['rules']['low_words_threshold'])) $cur['rules']['low_words_threshold'] = max(50, min(2000, (int) $payload['rules']['low_words_threshold']));
        }
        update_option('seo_aeo_robots_settings', wp_json_encode($cur), false);
        return true;
    }

    private static function _read_option_json($key) {
        $raw = get_option($key, '');
        if (is_array($raw)) return $raw;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) return $decoded;
        }
        return array();
    }

    // ============================================================
    // Resolution helpers (called from resolve_current_context)
    // ============================================================

    /**
     * Token replacement for title templates.
     * Tokens: {{title}}, {{site_name}}, {{separator}}, {{archive_name}}, {{author}},
     *         {{date}}, {{search_query}}, {{tagline}}, {{category}}, {{first_post_excerpt}}
     */
    private function _resolve_title_tokens($template, $context) {
        $cfg = self::get_title_formats();
        $sep = isset($cfg['separator']) ? $cfg['separator'] : '—';
        $tokens = array(
            '{{title}}'       => isset($context['title']) ? (string) $context['title'] : '',
            '{{site_name}}'   => get_bloginfo('name'),
            '{{tagline}}'     => get_bloginfo('description'),
            '{{separator}}'   => $sep,
            '{{archive_name}}'=> isset($context['archive_name']) ? (string) $context['archive_name'] : '',
            '{{author}}'      => isset($context['author']) ? (string) $context['author'] : '',
            '{{date}}'        => isset($context['date']) ? (string) $context['date'] : '',
            '{{search_query}}'=> isset($context['search_query']) ? (string) $context['search_query'] : '',
            '{{category}}'    => isset($context['category']) ? (string) $context['category'] : '',
        );
        $out = (string) $template;
        foreach ($tokens as $k => $v) {
            $out = str_replace($k, $v, $out);
        }
        // Collapse multi-spaces
        $out = preg_replace('/\s+/u', ' ', $out);
        $out = trim($out);
        if (!empty($cfg['trim_at_60']) && mb_strlen($out) > 60) {
            $out = mb_substr($out, 0, 60);
        }
        return $out;
    }

    /**
     * Resolve the meta description by walking the priority chain.
     */
    private function _resolve_meta_description($post_id, $orch_meta) {
        $cfg = self::get_meta_desc_settings();
        $priority = isset($cfg['priority']) && is_array($cfg['priority']) ? $cfg['priority'] : array('orch', 'yoast', 'rankmath', 'aioseo', 'excerpt', 'content');

        foreach ($priority as $source) {
            $candidate = '';
            switch ($source) {
                case 'orch':
                    $candidate = isset($orch_meta['meta_description']) ? (string) $orch_meta['meta_description'] : '';
                    if ($candidate === '') {
                        $candidate = (string) get_post_meta($post_id, '_orch_meta_desc_override', true);
                    }
                    if ($candidate === '') {
                        $candidate = (string) get_post_meta($post_id, '_seo_aeo_meta_description', true);
                    }
                    break;
                case 'yoast':
                    $candidate = (string) get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
                    break;
                case 'rankmath':
                    $candidate = (string) get_post_meta($post_id, 'rank_math_description', true);
                    break;
                case 'aioseo':
                    $candidate = (string) get_post_meta($post_id, '_aioseo_description', true);
                    break;
                case 'acf':
                    $field = isset($cfg['acf_field']) ? $cfg['acf_field'] : 'meta_description';
                    $candidate = (string) get_post_meta($post_id, $field, true);
                    break;
                case 'excerpt':
                    $post = get_post($post_id);
                    if ($post && !empty($post->post_excerpt)) {
                        $candidate = wp_strip_all_tags($post->post_excerpt);
                    }
                    break;
                case 'content':
                    $post = get_post($post_id);
                    if ($post) {
                        $candidate = wp_strip_all_tags(strip_shortcodes($post->post_content));
                    }
                    break;
            }
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                return $this->_truncate_description($candidate, $cfg);
            }
        }
        return '';
    }

    private function _truncate_description($text, $cfg) {
        $max = isset($cfg['max_len']) ? (int) $cfg['max_len'] : 160;
        $strategy = isset($cfg['truncation']) ? $cfg['truncation'] : 'word_boundary';
        $text = preg_replace('/\s+/u', ' ', trim((string) $text));
        if (mb_strlen($text) <= $max) return $text;
        if ($strategy === 'hard_cap') return mb_substr($text, 0, $max);
        if ($strategy === 'sentence_boundary') {
            $cut = mb_substr($text, 0, $max);
            $last = max(mb_strrpos($cut, '.'), mb_strrpos($cut, '!'), mb_strrpos($cut, '?'));
            if ($last !== false && $last > $max * 0.5) return mb_substr($cut, 0, $last + 1);
        }
        // word_boundary (default)
        $cut = mb_substr($text, 0, $max);
        $last = mb_strrpos($cut, ' ');
        if ($last !== false && $last > $max * 0.6) {
            return rtrim(mb_substr($cut, 0, $last)) . '…';
        }
        return rtrim($cut) . '…';
    }

    /**
     * Apply canonical strategy (force same-domain, trailing slash, CPT disabled).
     * Returns null if canonical should not be emitted (CPT disabled).
     */
    private function _apply_canonical_strategy($url, $post_or_null = null) {
        if (!$url) return null;
        $cfg = self::get_canonical_settings();

        if ($post_or_null instanceof WP_Post && !empty($cfg['disabled_post_types']) && in_array($post_or_null->post_type, $cfg['disabled_post_types'], true)) {
            return null;
        }

        $strategy = isset($cfg['strategy']) ? $cfg['strategy'] : 'auto';

        if ($strategy === 'force_same_domain' && !empty($cfg['canonical_domain'])) {
            $base = rtrim($cfg['canonical_domain'], '/');
            $parsed = wp_parse_url($url);
            $path = isset($parsed['path']) ? $parsed['path'] : '/';
            $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
            $url = $base . $path . $query;
        } elseif ($strategy === 'custom' && !empty($cfg['custom_prefix'])) {
            $base = rtrim($cfg['custom_prefix'], '/');
            $parsed = wp_parse_url($url);
            $path = isset($parsed['path']) ? $parsed['path'] : '/';
            $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
            $url = $base . $path . $query;
        }

        // Trailing slash policy
        $ts = isset($cfg['trailing_slash']) ? $cfg['trailing_slash'] : 'wp_default';
        if ($ts === 'with') {
            $parsed = wp_parse_url($url);
            $path = isset($parsed['path']) ? $parsed['path'] : '/';
            if (substr($path, -1) !== '/' && !preg_match('/\.[a-z0-9]+$/i', $path)) {
                $url = preg_replace('#^([^?#]+)#', '$1/', $url, 1);
            }
        } elseif ($ts === 'without') {
            $url = preg_replace('#/(\?|$|#)#', '$1', $url, 1);
        }

        return $url;
    }

    /**
     * Resolve robots directives based on context + per-CPT/archive rules.
     * Returns the value for the meta robots content attribute (or 'noindex,nofollow' / 'index,follow').
     */
    private function _resolve_robots_directives($context) {
        $cfg = self::get_robots_settings();
        $index = true;
        $follow = true;

        // 404, search → forced via context
        if (!empty($context['noindex_forced'])) {
            $index = false;
        }

        // is_singular: read post_types config
        if ($context['type'] === 'singular' && isset($context['post_type'])) {
            $pt = $context['post_type'];
            if (isset($cfg['post_types'][$pt])) {
                $index  = !empty($cfg['post_types'][$pt]['index']);
                $follow = !empty($cfg['post_types'][$pt]['follow']);
            }
            // 3.35.62: per-page meta override has highest priority
            if (!empty($context['post_id'])) {
                $page_index  = get_post_meta($context['post_id'], '_orch_robots_index', true);
                $page_follow = get_post_meta($context['post_id'], '_orch_robots_follow', true);
                if ($page_index === '0' || $page_index === 0)  $index  = false;
                if ($page_index === '1' || $page_index === 1)  $index  = true;
                if ($page_follow === '0' || $page_follow === 0) $follow = false;
                if ($page_follow === '1' || $page_follow === 1) $follow = true;
            }
        }

        // Archives
        if (in_array($context['type'], array('category', 'tag', 'author', 'date', 'search', 'not_found'), true)) {
            $arc = $context['type'];
            if ($arc === 'not_found') $arc = 'not_found';
            if (isset($cfg['archives'][$arc])) {
                $index  = !empty($cfg['archives'][$arc]['index']);
                $follow = !empty($cfg['archives'][$arc]['follow']);
            }
        }

        // Auto-noindex paginated pages
        if (!empty($cfg['rules']['noindex_paginated'])) {
            if (is_paged() && (int) get_query_var('paged') > 1) {
                $index = false;
            }
        }

        // Auto-noindex low word count (singular only)
        if (!empty($cfg['rules']['noindex_low_words']) && $context['type'] === 'singular' && !empty($context['post_id'])) {
            $threshold = (int) $cfg['rules']['low_words_threshold'];
            $post = get_post($context['post_id']);
            if ($post) {
                $words = str_word_count(wp_strip_all_tags(strip_shortcodes($post->post_content)));
                if ($words < $threshold) $index = false;
            }
        }

        $parts = array();
        $parts[] = $index ? 'index' : 'noindex';
        $parts[] = $follow ? 'follow' : 'nofollow';
        if ($index) {
            $parts[] = 'max-image-preview:large';
            $parts[] = 'max-snippet:-1';
            $parts[] = 'max-video-preview:-1';
        }
        return implode(', ', $parts);
    }

    /**
     * Apply title format template based on context.
     * Falls back to legacy "Title - Site Name" if no template configured.
     */
    private function _apply_title_format($base_title, $context) {
        $cfg = self::get_title_formats();
        $template = '';
        $ctx_for_tokens = array_merge(array('title' => $base_title), $context);

        if ($context['type'] === 'singular' && !empty($context['post_type'])) {
            if (isset($cfg['post_types'][$context['post_type']])) {
                $template = $cfg['post_types'][$context['post_type']];
            }
        } elseif (isset($cfg['archives'][$context['type']])) {
            $template = $cfg['archives'][$context['type']];
        }

        if ($template === '') {
            // Legacy fallback: "Title - Site Name"
            $site = get_bloginfo('name');
            if ($site && stripos($base_title, $site) === false) {
                return $base_title . ' - ' . $site;
            }
            return $base_title;
        }

        return $this->_resolve_title_tokens($template, $ctx_for_tokens);
    }

}

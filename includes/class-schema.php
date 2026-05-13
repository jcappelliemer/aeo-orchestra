<?php
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com
 *
 * Native Schema.org generator (v3.14.0 — Fase 1D strategia "switch da Yoast")
 *
 * Genera JSON-LD strutturato nel <head> per ogni tipo di pagina:
 *   - Homepage / front page → Organization + WebSite (con SearchAction se search abilitato)
 *   - Singular post → Article + WebPage + BreadcrumbList
 *   - Singular page → WebPage + BreadcrumbList
 *   - Singular CPT → WebPage + BreadcrumbList
 *   - Category/Tag/Tax archive → CollectionPage + BreadcrumbList
 *   - Author archive → ProfilePage
 *
 * Tutti gli items sono collegati via @graph (un solo <script>) con @id univoci,
 * pattern simile a Yoast SEO ma più leggero. Schema-graph valido secondo Schema.org.
 *
 * Setting:
 *   - seo_aeo_native_schema_enabled (default '0' = OFF)
 *
 * Quando Override Mode è attivo, i schema graph emessi da Yoast/RankMath/AIOSEO
 * vengono comunque strippati (gestito in class-output-renderer). Quindi è importante
 * non attivare Schema nativo se la pagina ha già schema custom da theme/altro plugin
 * — si genererebbero duplicati. La UI mostra detection automatica.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Schema {

    const OPTION_ENABLED = 'seo_aeo_native_schema_enabled';

    public function __construct() {
        // Priority 5: dopo i nostri meta tags (priority 1) ma prima del resto del head
        add_action('wp_head', array($this, 'render'), 5);
        // 3.40.6 - emit AI-generated custom schema saved per-post.
        add_action('wp_head', array($this, 'emit_custom_schema'), 12);
        // 3.40.8 P0c-B - expose the schema post_meta via REST so external
        // verifiers (Chrome MCP, curl /wp-json/wp/v2/<type>/N?_fields=meta)
        // can confirm the apply landed. Schema only meta needed in REST,
        // not the keyword/title meta which already work via Yoast bridge.
        add_action('init', array($this, 'register_rest_meta'), 20);
    }

    /**
     * 3.40.8 P0c-B - expose _seo_aeo_custom_schema_html in REST API.
     * Registered for every public post type so the auto-applied JSON-LD
     * block is visible to verifier scripts that GET
     * /wp-json/wp/v2/<type>/N?_fields=meta.
     */
    public function register_rest_meta() {
        if (!function_exists('register_post_meta')) return;
        $types = get_post_types(array('public' => true), 'names');
        foreach ((array) $types as $t) {
            register_post_meta($t, '_seo_aeo_custom_schema_html', array(
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
                'auth_callback' => function() { return current_user_can('edit_posts'); },
            ));
        }
    }

    public static function is_enabled() {
        return get_option(self::OPTION_ENABLED, '0') === '1';
    }

    public function render() {
        if (!self::is_enabled()) return;
        if (is_admin()) return;

        $graph = $this->build_graph();
        if (empty($graph)) return;

        $payload = array(
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        );

        echo "\n<!-- AEO Orchestra · Schema.org @graph (v" . esc_html(SEO_AEO_VERSION) . ") -->\n";
        echo '<script type="application/ld+json">';
        // 3.35.85.0 (WP.org Issue 5): drop JSON_UNESCAPED_SLASHES so a `</script>`
        // appearing inside any string value cannot break out of the JSON-LD block.
        // The default escaped-slash output (`<\/script>`) parses identically as
        // JSON but cannot terminate the surrounding <script> tag.
        echo wp_json_encode($payload, JSON_UNESCAPED_UNICODE);
        echo "</script>\n";
        echo "<!-- /AEO Orchestra · Schema.org -->\n\n";
    }

    /**
     * Costruisce l'array @graph per la pagina corrente.
     */
    private function build_graph() {
        $graph = array();

        // Organization (sempre presente, è il fulcro del graph)
        $graph[] = $this->organization_node();

        // WebSite (sempre presente, contiene SearchAction)
        $graph[] = $this->website_node();

        if (is_front_page() || is_home()) {
            // Homepage: solo Organization + WebSite + WebPage homepage
            $graph[] = $this->webpage_node_homepage();
        } elseif (is_singular()) {
            $post = get_queried_object();
            if ($post instanceof WP_Post) {
                // 3.35.56: dispatch per role (Stage 2.5 downstream).
                $role_nodes = $this->_resolve_schema_nodes_for_post($post);
                foreach ($role_nodes as $node) {
                    $graph[] = $node;
                }
                if (!empty($role_nodes)) {
                    // 3.35.58 A.3: respect breadcrumb toggle
                    $bc_settings = self::get_breadcrumb_settings();
                    if (!empty($bc_settings['enabled'])) {
                        $graph[] = $this->breadcrumb_node_singular($post);
                    }
                    $is_article_like = ($post->post_type === 'post');
                    if (!$is_article_like && get_post_thumbnail_id($post->ID)) {
                        $graph[] = $this->image_node($post);
                    }
                }
            }
        } elseif (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term) {
                $graph[] = $this->collection_node($term);
                $graph[] = $this->breadcrumb_node_term($term);
            }
        } elseif (is_author()) {
            $author = get_queried_object();
            if ($author) {
                $graph[] = $this->profile_node($author);
            }
        }

        return $graph;
    }

    // ─── Node builders ─────────────────────────────────────────────────────

    private function organization_node() {
        $home = home_url('/');
        $name = get_bloginfo('name');
        $logo = $this->resolve_site_logo();

        // 3.35.58 (Stage 2.5 A.1): merge in user-configured Organization defaults
        $defaults = self::get_org_defaults();
        $logo_url = !empty($defaults['logo_url']) ? (string) $defaults['logo_url'] : $logo;
        $legal_name = !empty($defaults['legal_name']) ? (string) $defaults['legal_name'] : '';

        $node = array(
            '@type' => 'Organization',
            '@id'   => $home . '#organization',
            'name'  => $legal_name !== '' ? $legal_name : $name,
            'url'   => $home,
        );
        if ($legal_name !== '' && $legal_name !== $name) {
            $node['legalName'] = $legal_name;
            $node['alternateName'] = $name;
        }
        if ($logo_url) {
            $node['logo'] = array(
                '@type'   => 'ImageObject',
                '@id'     => $home . '#logo',
                'url'     => $logo_url,
                'caption' => $name,
            );
            $node['image'] = array('@id' => $home . '#logo');
        }
        $description = get_bloginfo('description');
        if ($description) {
            $node['description'] = $description;
        }
        if (!empty($defaults['founding_date'])) {
            $node['foundingDate'] = (string) $defaults['founding_date'];
        }
        if (!empty($defaults['vat'])) {
            $node['vatID'] = (string) $defaults['vat'];
        }
        // Address
        $addr = isset($defaults['address']) && is_array($defaults['address']) ? $defaults['address'] : array();
        if (!empty($addr['street_address']) || !empty($addr['locality'])) {
            $address_node = array('@type' => 'PostalAddress');
            if (!empty($addr['street_address'])) $address_node['streetAddress'] = (string) $addr['street_address'];
            if (!empty($addr['postal_code']))    $address_node['postalCode']    = (string) $addr['postal_code'];
            if (!empty($addr['locality']))       $address_node['addressLocality'] = (string) $addr['locality'];
            if (!empty($addr['region']))         $address_node['addressRegion']   = (string) $addr['region'];
            if (!empty($addr['country']))        $address_node['addressCountry']  = (string) $addr['country'];
            $node['address'] = $address_node;
        }
        // ContactPoint
        $cp = isset($defaults['contact_point']) && is_array($defaults['contact_point']) ? $defaults['contact_point'] : array();
        if (!empty($cp['telephone']) || !empty($cp['email'])) {
            $cp_node = array(
                '@type'       => 'ContactPoint',
                'contactType' => !empty($cp['contact_type']) ? (string) $cp['contact_type'] : 'customer support',
            );
            if (!empty($cp['telephone'])) $cp_node['telephone'] = (string) $cp['telephone'];
            if (!empty($cp['email']) && filter_var($cp['email'], FILTER_VALIDATE_EMAIL)) {
                $cp_node['email'] = (string) $cp['email'];
            }
            if (!empty($cp['available_languages'])) {
                $langs = is_array($cp['available_languages'])
                    ? $cp['available_languages']
                    : array_filter(array_map('trim', explode(',', (string) $cp['available_languages'])));
                if (!empty($langs)) $cp_node['availableLanguage'] = $langs;
            }
            if (!empty($cp['area_served'])) {
                $area = is_array($cp['area_served'])
                    ? $cp['area_served']
                    : array_filter(array_map('trim', explode(',', (string) $cp['area_served'])));
                if (!empty($area)) $cp_node['areaServed'] = $area;
            }
            $node['contactPoint'] = $cp_node;
        }
        // sameAs (social profiles)
        if (!empty($defaults['same_as']) && is_array($defaults['same_as'])) {
            $valid_urls = array();
            foreach ($defaults['same_as'] as $u) {
                if (!is_string($u)) continue;
                $u = trim($u);
                if ($u !== '' && filter_var($u, FILTER_VALIDATE_URL)) {
                    $valid_urls[] = $u;
                }
            }
            if (!empty($valid_urls)) {
                $node['sameAs'] = $valid_urls;
            }
        }
        return $node;
    }

    /**
     * Read the user-configured Organization defaults from WP option.
     * Returns array (possibly empty). Schema:
     *   {
     *     logo_url, legal_name, founding_date, vat,
     *     address: {street_address, postal_code, locality, region, country},
     *     contact_point: {telephone, email, contact_type, available_languages, area_served},
     *     same_as: [url1, url2, ...]
     *   }
     */
    public static function get_org_defaults() {
        $raw = get_option('seo_aeo_org_defaults', '');
        if (is_array($raw)) return $raw;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) return $decoded;
        }
        return array();
    }

    public static function update_org_defaults($payload) {
        if (!is_array($payload)) return false;
        $clean = array();
        $string_fields = array('logo_url', 'legal_name', 'founding_date', 'vat');
        foreach ($string_fields as $f) {
            if (isset($payload[$f])) {
                $v = sanitize_text_field((string) $payload[$f]);
                if ($v !== '') $clean[$f] = $v;
            }
        }
        if (isset($payload['address']) && is_array($payload['address'])) {
            $addr = array();
            $addr_fields = array('street_address', 'postal_code', 'locality', 'region', 'country');
            foreach ($addr_fields as $af) {
                if (isset($payload['address'][$af])) {
                    $v = sanitize_text_field((string) $payload['address'][$af]);
                    if ($v !== '') $addr[$af] = $v;
                }
            }
            if (!empty($addr)) $clean['address'] = $addr;
        }
        if (isset($payload['contact_point']) && is_array($payload['contact_point'])) {
            $cp = array();
            if (isset($payload['contact_point']['telephone'])) {
                $cp['telephone'] = sanitize_text_field((string) $payload['contact_point']['telephone']);
            }
            if (isset($payload['contact_point']['email'])) {
                $email = sanitize_email((string) $payload['contact_point']['email']);
                if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) $cp['email'] = $email;
            }
            if (isset($payload['contact_point']['contact_type'])) {
                $cp['contact_type'] = sanitize_text_field((string) $payload['contact_point']['contact_type']);
            }
            if (isset($payload['contact_point']['available_languages'])) {
                $cp['available_languages'] = sanitize_text_field((string) $payload['contact_point']['available_languages']);
            }
            if (isset($payload['contact_point']['area_served'])) {
                $cp['area_served'] = sanitize_text_field((string) $payload['contact_point']['area_served']);
            }
            if (!empty($cp)) $clean['contact_point'] = $cp;
        }
        if (isset($payload['same_as']) && is_array($payload['same_as'])) {
            $urls = array();
            foreach ($payload['same_as'] as $u) {
                if (!is_string($u)) continue;
                $u = esc_url_raw(trim($u));
                if ($u !== '' && filter_var($u, FILTER_VALIDATE_URL)) $urls[] = $u;
            }
            $clean['same_as'] = array_values(array_unique($urls));
        }
        update_option('seo_aeo_org_defaults', wp_json_encode($clean), false);
        return true;
    }

    private function website_node() {
        $home = home_url('/');
        $name = get_bloginfo('name');
        $description = get_bloginfo('description');

        $node = array(
            '@type'     => 'WebSite',
            '@id'       => $home . '#website',
            'url'       => $home,
            'name'      => $name,
            'publisher' => array('@id' => $home . '#organization'),
            'inLanguage' => str_replace('_', '-', get_locale()),
        );
        if ($description) {
            $node['description'] = $description;
        }
        // SearchAction (Google sitelinks searchbox)
        $node['potentialAction'] = array(array(
            '@type'  => 'SearchAction',
            'target' => array(
                '@type' => 'EntryPoint',
                'urlTemplate' => $home . '?s={search_term_string}',
            ),
            'query-input' => 'required name=search_term_string',
        ));
        return $node;
    }

    private function webpage_node_homepage() {
        $home = home_url('/');
        return array(
            '@type'   => 'WebPage',
            '@id'     => $home,
            'url'     => $home,
            'name'    => get_bloginfo('name') . ' - ' . get_bloginfo('description'),
            'isPartOf' => array('@id' => $home . '#website'),
            'about'   => array('@id' => $home . '#organization'),
            'description' => get_bloginfo('description'),
            'inLanguage' => str_replace('_', '-', get_locale()),
        );
    }

    private function webpage_node_singular($post) {
        $home = home_url('/');
        $url  = get_permalink($post);
        $title = $this->resolve_meta_title($post);
        $description = $this->resolve_meta_description($post);

        $node = array(
            '@type'    => 'WebPage',
            '@id'      => $url . '#webpage',
            'url'      => $url,
            'name'     => $title,
            'isPartOf' => array('@id' => $home . '#website'),
            'datePublished' => mysql2date('c', $post->post_date_gmt, false),
            'dateModified'  => mysql2date('c', $post->post_modified_gmt, false),
            'inLanguage' => str_replace('_', '-', get_locale()),
        );
        if ($description) {
            $node['description'] = $description;
        }
        $thumb_id = get_post_thumbnail_id($post->ID);
        if ($thumb_id) {
            $node['primaryImageOfPage'] = array('@id' => $url . '#primaryimage');
            $node['image'] = array('@id' => $url . '#primaryimage');
        }
        return $node;
    }

    private function article_node($post) {
        $url = get_permalink($post);
        $title = $this->resolve_meta_title($post);
        $description = $this->resolve_meta_description($post);
        $home = home_url('/');

        $author_id = (int) $post->post_author;
        $author_name = $author_id ? get_the_author_meta('display_name', $author_id) : '';

        $node = array(
            '@type'    => 'Article',
            '@id'      => $url . '#article',
            'isPartOf' => array('@id' => $url . '#webpage'),
            'mainEntityOfPage' => array('@id' => $url . '#webpage'),
            'headline' => $title,
            'datePublished' => mysql2date('c', $post->post_date_gmt, false),
            'dateModified'  => mysql2date('c', $post->post_modified_gmt, false),
            'publisher' => array('@id' => $home . '#organization'),
            'inLanguage' => str_replace('_', '-', get_locale()),
        );
        if ($description) {
            $node['description'] = $description;
        }
        if ($author_name) {
            $node['author'] = array(
                '@type' => 'Person',
                '@id'   => $home . '#/schema/person/' . md5($author_name),
                'name'  => $author_name,
            );
        }
        $thumb_id = get_post_thumbnail_id($post->ID);
        if ($thumb_id) {
            $node['image'] = array('@id' => $url . '#primaryimage');
            $node['thumbnailUrl'] = wp_get_attachment_image_url($thumb_id, 'large');
        }

        // Categories as articleSection
        $cats = get_the_category($post->ID);
        if (!empty($cats) && !is_wp_error($cats)) {
            $node['articleSection'] = array_map(function ($c) { return $c->name; }, $cats);
        }
        // Word count
        $word_count = str_word_count(wp_strip_all_tags(strip_shortcodes($post->post_content)));
        if ($word_count > 0) {
            $node['wordCount'] = $word_count;
        }
        return $node;
    }

    private function image_node($post) {
        $url = get_permalink($post);
        $thumb_id = get_post_thumbnail_id($post->ID);
        $img = wp_get_attachment_image_src($thumb_id, 'large');
        if (!$img) return null;

        return array(
            '@type'  => 'ImageObject',
            '@id'    => $url . '#primaryimage',
            'url'    => $img[0],
            'contentUrl' => $img[0],
            'width'  => $img[1],
            'height' => $img[2],
            'caption' => get_post_meta($thumb_id, '_wp_attachment_image_alt', true) ?: get_the_title($post),
            'inLanguage' => str_replace('_', '-', get_locale()),
        );
    }

    private function breadcrumb_node_singular($post) {
        $home = home_url('/');
        $items = array();
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => 1,
            'name'     => 'Home',
            'item'     => $home,
        );

        // Per le pagine: usa il parent chain
        if ($post->post_type === 'page' && $post->post_parent) {
            $parents = array();
            $parent_id = $post->post_parent;
            while ($parent_id) {
                $parent = get_post($parent_id);
                if (!$parent) break;
                array_unshift($parents, $parent);
                $parent_id = $parent->post_parent;
            }
            foreach ($parents as $p) {
                $items[] = array(
                    '@type'    => 'ListItem',
                    'position' => count($items) + 1,
                    'name'     => get_the_title($p),
                    'item'     => get_permalink($p),
                );
            }
        }

        // Per i post: aggiungi la categoria principale
        if ($post->post_type === 'post') {
            $cats = get_the_category($post->ID);
            if (!empty($cats) && !is_wp_error($cats)) {
                $primary = $cats[0];
                $items[] = array(
                    '@type'    => 'ListItem',
                    'position' => count($items) + 1,
                    'name'     => $primary->name,
                    'item'     => get_category_link($primary->term_id),
                );
            }
        }

        // Sé stesso (l'item finale del breadcrumb di solito non ha "item" perché è la pagina corrente)
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => count($items) + 1,
            'name'     => get_the_title($post),
        );

        return array(
            '@type'           => 'BreadcrumbList',
            '@id'             => get_permalink($post) . '#breadcrumb',
            'itemListElement' => $items,
        );
    }

    private function collection_node($term) {
        $url = get_term_link($term);
        if (is_wp_error($url)) $url = home_url('/');
        $home = home_url('/');

        $name = single_term_title('', false);
        if (empty($name)) $name = $term->name;

        $description = term_description($term->term_id);
        if ($description) {
            $description = wp_trim_words(wp_strip_all_tags($description), 50, '');
        }

        return array(
            '@type'    => 'CollectionPage',
            '@id'      => $url . '#webpage',
            'url'      => $url,
            'name'     => $name . ' - ' . get_bloginfo('name'),
            'isPartOf' => array('@id' => $home . '#website'),
            'description' => $description ?: ('Archivio ' . $name),
            'inLanguage' => str_replace('_', '-', get_locale()),
        );
    }

    private function breadcrumb_node_term($term) {
        $home = home_url('/');
        $items = array();
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => 1,
            'name'     => 'Home',
            'item'     => $home,
        );
        // Eventuali parent terms
        if (!empty($term->parent)) {
            $parents = array();
            $parent_id = $term->parent;
            while ($parent_id) {
                $parent = get_term($parent_id, $term->taxonomy);
                if (is_wp_error($parent) || !$parent) break;
                array_unshift($parents, $parent);
                $parent_id = $parent->parent;
            }
            foreach ($parents as $p) {
                $link = get_term_link($p);
                if (is_wp_error($link)) continue;
                $items[] = array(
                    '@type'    => 'ListItem',
                    'position' => count($items) + 1,
                    'name'     => $p->name,
                    'item'     => $link,
                );
            }
        }
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => count($items) + 1,
            'name'     => $term->name,
        );
        $url = get_term_link($term);
        if (is_wp_error($url)) $url = $home;
        return array(
            '@type'           => 'BreadcrumbList',
            '@id'             => $url . '#breadcrumb',
            'itemListElement' => $items,
        );
    }

    private function profile_node($author) {
        $url = get_author_posts_url($author->ID);
        $home = home_url('/');
        return array(
            '@type'    => 'ProfilePage',
            '@id'      => $url . '#webpage',
            'url'      => $url,
            'name'     => $author->display_name . ' - ' . get_bloginfo('name'),
            'isPartOf' => array('@id' => $home . '#website'),
            'mainEntity' => array(
                '@type' => 'Person',
                'name'  => $author->display_name,
                'description' => $author->description ?: '',
                'url'   => $url,
            ),
            'inLanguage' => str_replace('_', '-', get_locale()),
        );
    }

    // ─── Helpers ───────────────────────────────────────────────────────────

    private function resolve_meta_title($post) {
        if (class_exists('SEO_AEO_Engine_Bridge')) {
            $meta = SEO_AEO_Engine_Bridge::read_meta($post->ID);
            if (!empty($meta['meta_title'])) return $meta['meta_title'];
        }
        return get_the_title($post);
    }

    private function resolve_meta_description($post) {
        if (class_exists('SEO_AEO_Engine_Bridge')) {
            $meta = SEO_AEO_Engine_Bridge::read_meta($post->ID);
            if (!empty($meta['meta_description'])) return $meta['meta_description'];
        }
        if (!empty($post->post_excerpt)) return $post->post_excerpt;
        return wp_trim_words(wp_strip_all_tags(strip_shortcodes($post->post_content)), 30, '…');
    }

    private function resolve_site_logo() {
        $logo_id = get_theme_mod('custom_logo');
        if ($logo_id) {
            $img = wp_get_attachment_image_src($logo_id, 'large');
            if ($img) return $img[0];
        }
        return '';
    }

    public static function set_enabled($enabled) {
        update_option(self::OPTION_ENABLED, $enabled ? '1' : '0');
    }


    // ============================================================
    // 3.35.56 (Stage 2.5 downstream) — Role-aware schema emission
    // ============================================================

    /**
     * Build the @graph nodes for a single post based on its classified role
     * (SEO_AEO_Page_Roles). Falls back to the previous post_type-based logic
     * when role is null/custom.
     *
     * Returns array of node arrays (caller appends to $graph).
     */
    private function _resolve_schema_nodes_for_post($post) {
        if (!$post instanceof WP_Post) return array();

        // 3.35.58 (Stage 2.5 A.2): per-page meta override → CPT override → role-based → fallback.
        // Per-page meta has highest priority.
        $page_override = (string) get_post_meta($post->ID, '_orch_schema_type_override', true);
        $page_excluded = get_post_meta($post->ID, '_orch_schema_excluded', true);
        if ($page_excluded === '1' || $page_excluded === 1 || $page_excluded === true) {
            return array(); // user explicitly excluded this page from schema
        }
        if ($page_override !== '' && $page_override !== 'auto') {
            // Force a specific schema type for this page only
            return $this->_build_overridden_schema_node($post, $page_override);
        }

        // CPT-level override (admin UI)
        $cpt_overrides = self::get_schema_cpt_overrides();
        if (!empty($cpt_overrides[$post->post_type]) && $cpt_overrides[$post->post_type] !== 'auto') {
            return $this->_build_overridden_schema_node($post, $cpt_overrides[$post->post_type]);
        }

        $role = null;
        if (class_exists('SEO_AEO_Page_Roles')) {
            $role = SEO_AEO_Page_Roles::get_role($post->ID);
        }

        // Build the base WebPage descriptor (always the canonical anchor for #webpage @id)
        $url = get_permalink($post);
        $home = home_url('/');
        $title = $this->resolve_meta_title($post);
        $description = $this->resolve_meta_description($post);

        $base = array(
            '@type'         => 'WebPage',  // overridden per role below
            '@id'           => $url . '#webpage',
            'url'           => $url,
            'name'          => $title,
            'isPartOf'      => array('@id' => $home . '#website'),
            'datePublished' => mysql2date('c', $post->post_date_gmt, false),
            'dateModified'  => mysql2date('c', $post->post_modified_gmt, false),
            'inLanguage'    => str_replace('_', '-', get_locale()),
        );
        if ($description) $base['description'] = $description;
        $thumb_id = get_post_thumbnail_id($post->ID);
        if ($thumb_id) {
            $base['primaryImageOfPage'] = array('@id' => $url . '#primaryimage');
            $base['image'] = array('@id' => $url . '#primaryimage');
        }

        $nodes = array();

        switch ($role) {
            case 'about':
                $base['@type'] = 'AboutPage';
                $base['mainEntity'] = array('@id' => $home . '#organization');
                $nodes[] = $base;
                break;

            case 'contact':
                $base['@type'] = 'ContactPage';
                $cp = $this->_contact_point_node();
                if ($cp) $base['mainEntity'] = $cp;
                $nodes[] = $base;
                break;

            case 'quote_request':
                $base['@type'] = 'ContactPage';
                $base['potentialAction'] = array(
                    '@type'  => 'RequestQuoteAction',
                    'target' => $url,
                    'name'   => 'Richiedi preventivo',
                );
                $nodes[] = $base;
                break;

            case 'faq':
                $base['@type'] = 'FAQPage';
                $qa = $this->_parse_faq_pairs($post->post_content);
                if (!empty($qa)) {
                    $base['mainEntity'] = $qa;
                    $nodes[] = $base;
                } else {
                    // Not enough Q&A pairs detected — degrade gracefully to plain WebPage
                    $base['@type'] = 'WebPage';
                    $nodes[] = $base;
                }
                break;

            case 'knowledge_guide':
                $nodes[] = $base;
                $article = $this->article_node($post);
                $article['articleSection'] = 'Guide tecniche';
                $nodes[] = $article;
                break;

            case 'category_landing':
                $base['@type'] = 'CollectionPage';
                $items = $this->_collection_items_for_landing($post, 10);
                if (!empty($items)) {
                    $base['mainEntity'] = array(
                        '@type'           => 'ItemList',
                        'itemListElement' => $items,
                    );
                }
                $nodes[] = $base;
                break;

            case 'service_page':
                $nodes[] = $base;
                $service = array(
                    '@type'       => 'Service',
                    '@id'         => $url . '#service',
                    'name'        => $title,
                    'provider'    => array('@id' => $home . '#organization'),
                    'url'         => $url,
                );
                if ($description) $service['description'] = $description;
                if ($thumb_id) $service['image'] = array('@id' => $url . '#primaryimage');
                $nodes[] = $service;
                break;

            case 'product_page':
                $nodes[] = $base;
                $product = array(
                    '@type' => 'Product',
                    '@id'   => $url . '#product',
                    'name'  => $title,
                    'url'   => $url,
                );
                if ($description) $product['description'] = $description;
                if ($thumb_id) $product['image'] = array('@id' => $url . '#primaryimage');
                $offer = $this->_extract_product_offer($post, $url);
                if ($offer) $product['offers'] = $offer;
                $nodes[] = $product;
                break;

            case 'blog_post':
                $nodes[] = $base;
                $nodes[] = $this->article_node($post);
                break;

            case 'legal_privacy':
            case 'legal_terms':
                // Plain WebPage with explicit inLanguage (already in $base)
                $nodes[] = $base;
                break;

            case 'ignore':
                // Emit nothing for ignored pages
                return array();

            case 'homepage':
            case 'blog_index':
                // These are handled by the front_page / is_home branches in build_graph.
                // If we're here it's because the page IS singular but ALSO front/home —
                // emit a WebPage as fallback.
                $nodes[] = $base;
                break;

            default:
                // No role classified, OR role='custom' → fallback to legacy post_type logic.
                $is_article = ($post->post_type === 'post');
                if ($is_article) {
                    $nodes[] = $this->article_node($post);
                }
                $nodes[] = $this->webpage_node_singular($post);
                break;
        }

        return $nodes;
    }

    /**
     * Best-effort extraction of FAQ Question/Answer pairs from post_content.
     * Pairs an <h2> with the next sibling element containing meaningful text.
     * Returns empty array if fewer than 3 valid pairs detected.
     */
    /**
     * Multi-strategy FAQ parser (3.35.57).
     * Tries strategies in priority order; first to yield >=3 pairs wins.
     * Supports: Elementor accordion/toggle, Divi accordion, Gutenberg <details>,
     * <dl>/<dt>/<dd>, and heading+sibling fallback.
     *
     * @param string $html  post_content
     * @return array  list of {@type:Question, name, acceptedAnswer{...}} dicts
     */
    private function _parse_faq_pairs($html) {
        if (!is_string($html) || trim($html) === '') return array();
        if (!class_exists('DOMDocument') || !class_exists('DOMXPath')) return array();

        $stripped = strip_shortcodes($html);
        if (mb_strlen($stripped) < 100) return array();

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $loaded = @$dom->loadHTML('<?xml encoding="UTF-8"?>' . $stripped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        if (!$loaded) return array();

        $xpath = new DOMXPath($dom);

        $strategies = array(
            '_faq_strategy_elementor',
            '_faq_strategy_divi',
            '_faq_strategy_details_summary',
            '_faq_strategy_definition_list',
            '_faq_strategy_heading_sibling',
        );

        foreach ($strategies as $strat) {
            if (!method_exists($this, $strat)) continue;
            $pairs = $this->{$strat}($dom, $xpath);
            if (is_array($pairs) && count($pairs) >= 3) {
                return array_slice($pairs, 0, 20);
            }
        }
        return array();
    }

    /**
     * XPath idiom for matching a CSS class (handles multi-class attributes).
     */
    private function _xpath_class($class) {
        $class = (string) $class;
        return "contains(concat(' ', normalize-space(@class), ' '), ' " . $class . " ')";
    }

    /**
     * Normalize and validate a Q/A pair. Returns null if invalid.
     */
    private function _faq_make_pair($question, $answer) {
        $q = trim(preg_replace('/\s+/u', ' ', (string) $question));
        $a = trim(preg_replace('/\s+/u', ' ', (string) $answer));
        if (mb_strlen($q) < 5 || mb_strlen($q) > 280) return null;
        if (mb_strlen($a) < 20) return null;
        return array(
            '@type'          => 'Question',
            'name'           => $q,
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => mb_substr($a, 0, 1500),
            ),
        );
    }

    // ──────────────────────────────────────────────────
    // Strategy 1: Elementor accordion + toggle widgets
    // ──────────────────────────────────────────────────
    private function _faq_strategy_elementor($dom, $xpath) {
        $pairs = array();
        $item_query = '//*[' . $this->_xpath_class('elementor-accordion-item') . ' or ' . $this->_xpath_class('elementor-toggle-item') . ']';
        $items = $xpath->query($item_query);
        if ($items === false || $items->length === 0) return $pairs;

        foreach ($items as $item) {
            // Question: .elementor-accordion-title or .elementor-toggle-title (descendant)
            $q_query = './/*[' . $this->_xpath_class('elementor-accordion-title') . ' or ' . $this->_xpath_class('elementor-toggle-title') . ']';
            $q_nodes = $xpath->query($q_query, $item);
            if (!$q_nodes || $q_nodes->length === 0) continue;
            $question = (string) $q_nodes->item(0)->textContent;

            // Answer: .elementor-tab-content / .elementor-toggle-content (descendant)
            $a_query = './/*[' . $this->_xpath_class('elementor-tab-content') . ' or ' . $this->_xpath_class('elementor-toggle-content') . ']';
            $a_nodes = $xpath->query($a_query, $item);
            if (!$a_nodes || $a_nodes->length === 0) continue;
            $answer = (string) $a_nodes->item(0)->textContent;

            $pair = $this->_faq_make_pair($question, $answer);
            if ($pair) $pairs[] = $pair;
            if (count($pairs) >= 20) break;
        }
        return $pairs;
    }

    // ──────────────────────────────────────────────────
    // Strategy 2: Divi accordion (.et_pb_toggle)
    // ──────────────────────────────────────────────────
    private function _faq_strategy_divi($dom, $xpath) {
        $pairs = array();
        $items = $xpath->query('//*[' . $this->_xpath_class('et_pb_toggle') . ']');
        if ($items === false || $items->length === 0) return $pairs;

        foreach ($items as $item) {
            $q_nodes = $xpath->query('.//*[' . $this->_xpath_class('et_pb_toggle_title') . ']', $item);
            if (!$q_nodes || $q_nodes->length === 0) continue;
            $question = (string) $q_nodes->item(0)->textContent;

            $a_nodes = $xpath->query('.//*[' . $this->_xpath_class('et_pb_toggle_content') . ']', $item);
            if (!$a_nodes || $a_nodes->length === 0) continue;
            $answer = (string) $a_nodes->item(0)->textContent;

            $pair = $this->_faq_make_pair($question, $answer);
            if ($pair) $pairs[] = $pair;
            if (count($pairs) >= 20) break;
        }
        return $pairs;
    }

    // ──────────────────────────────────────────────────
    // Strategy 3: Gutenberg native <details><summary>
    // ──────────────────────────────────────────────────
    private function _faq_strategy_details_summary($dom, $xpath) {
        $pairs = array();
        $details = $dom->getElementsByTagName('details');
        if ($details === null) return $pairs;

        foreach ($details as $d) {
            $summary = null;
            $answer_parts = array();
            foreach ($d->childNodes as $child) {
                if ($child->nodeType !== XML_ELEMENT_NODE) {
                    // accumulate text nodes too as part of answer
                    if ($child->nodeType === XML_TEXT_NODE) {
                        $t = trim((string) $child->textContent);
                        if ($t !== '') $answer_parts[] = $t;
                    }
                    continue;
                }
                if (strtolower($child->nodeName) === 'summary') {
                    if ($summary === null) $summary = $child;
                    continue;
                }
                $answer_parts[] = trim((string) $child->textContent);
            }
            if ($summary === null) continue;
            $question = (string) $summary->textContent;
            $answer = trim(implode(' ', array_filter($answer_parts, function ($x) { return $x !== ''; })));

            $pair = $this->_faq_make_pair($question, $answer);
            if ($pair) $pairs[] = $pair;
            if (count($pairs) >= 20) break;
        }
        return $pairs;
    }

    // ──────────────────────────────────────────────────
    // Strategy 4: Definition list <dl><dt><dd>
    // ──────────────────────────────────────────────────
    private function _faq_strategy_definition_list($dom, $xpath) {
        $pairs = array();
        $dls = $dom->getElementsByTagName('dl');
        if ($dls === null) return $pairs;

        foreach ($dls as $dl) {
            $current_q = null;
            foreach ($dl->childNodes as $child) {
                if ($child->nodeType !== XML_ELEMENT_NODE) continue;
                $tag = strtolower($child->nodeName);
                if ($tag === 'dt') {
                    $current_q = (string) $child->textContent;
                } elseif ($tag === 'dd' && $current_q !== null) {
                    $answer = (string) $child->textContent;
                    $pair = $this->_faq_make_pair($current_q, $answer);
                    if ($pair) $pairs[] = $pair;
                    $current_q = null;
                    if (count($pairs) >= 20) break 2;
                }
            }
        }
        return $pairs;
    }

    // ──────────────────────────────────────────────────
    // Strategy 5: heading + sibling block (legacy fallback, h2/h3)
    // ──────────────────────────────────────────────────
    private function _faq_strategy_heading_sibling($dom, $xpath) {
        $pairs = array();
        // h2 OR h3
        $headings = $xpath->query('//h2 | //h3');
        if ($headings === false || $headings->length === 0) return $pairs;

        foreach ($headings as $h) {
            $question = (string) $h->textContent;
            // Walk forward siblings up to 8 hops looking for a block element with substantial text
            $next = $h->nextSibling;
            $answer = '';
            $hops = 0;
            while ($next && $hops < 8) {
                if ($next->nodeType === XML_ELEMENT_NODE) {
                    $tag = strtolower($next->nodeName);
                    if (in_array($tag, array('h1', 'h2', 'h3', 'h4', 'h5', 'h6'), true)) break;
                    if (in_array($tag, array('p', 'div', 'ul', 'ol', 'section'), true)) {
                        $text = trim((string) $next->textContent);
                        if (mb_strlen($text) >= 20) {
                            $answer = $text;
                            break;
                        }
                    }
                }
                $next = $next->nextSibling;
                $hops++;
            }
            if ($answer === '') continue;
            $pair = $this->_faq_make_pair($question, $answer);
            if ($pair) $pairs[] = $pair;
            if (count($pairs) >= 20) break;
        }
        return $pairs;
    }


    /**
     * Build a ContactPoint node from site-level data (Organization defaults).
     * Best effort — if no email/phone is available returns null.
     */
    private function _contact_point_node() {
        $email = get_option('admin_email', '');
        // Try common locations for site phone/contact phone
        $phone = get_option('seo_aeo_org_phone', '');
        $cp = array('@type' => 'ContactPoint', 'contactType' => 'customer support');
        $has_data = false;
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $cp['email'] = $email;
            $has_data = true;
        }
        if ($phone) {
            $cp['telephone'] = (string) $phone;
            $has_data = true;
        }
        return $has_data ? $cp : null;
    }

    /**
     * Build ItemList items for a category_landing page.
     * Strategy: if the page has children (post_type=page parent), list those.
     * Otherwise list other published posts of the same post_type as siblings.
     */
    private function _collection_items_for_landing($post, $limit = 10) {
        $items = array();

        // 1. Direct children (page hierarchy)
        $children = get_posts(array(
            'post_type'        => $post->post_type,
            'post_status'      => 'publish',
            'post_parent'      => $post->ID,
            'posts_per_page'   => $limit,
            'orderby'          => 'menu_order',
            'order'            => 'ASC',
            'no_found_rows'    => true,
        ));
        if (!empty($children)) {
            $i = 1;
            foreach ($children as $c) {
                $items[] = array(
                    '@type'    => 'ListItem',
                    'position' => $i++,
                    'url'      => (string) get_permalink($c),
                    'name'     => get_the_title($c),
                );
            }
            return $items;
        }

        // 2. Fallback: siblings (same post_type, not the page itself)
        $siblings = get_posts(array(
            'post_type'        => $post->post_type,
            'post_status'      => 'publish',
            'posts_per_page'   => $limit,
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Not targeting VIP infrastructure; standard WordPress API
            'post__not_in'     => array($post->ID),
            'orderby'          => 'modified',
            'order'            => 'DESC',
            'no_found_rows'    => true,
        ));
        $i = 1;
        foreach ($siblings as $c) {
            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $i++,
                'url'      => (string) get_permalink($c),
                'name'     => get_the_title($c),
            );
        }
        return $items;
    }

    /**
     * Best-effort price extraction. Currently only handles WooCommerce products;
     * non-WC sites get no offers (which is valid Schema.org).
     */
    private function _extract_product_offer($post, $url) {
        if (function_exists('wc_get_product')) {
            $wc_product = wc_get_product($post->ID);
            if ($wc_product) {
                $price = $wc_product->get_price();
                if ($price !== '' && is_numeric($price)) {
                    return array(
                        '@type'         => 'Offer',
                        'url'           => $url,
                        'price'         => (string) $price,
                        'priceCurrency' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'EUR',
                        'availability'  => $wc_product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    );
                }
            }
        }
        return null;
    }



    public static function get_schema_cpt_overrides() {
        $raw = get_option('seo_aeo_schema_type_per_cpt', '');
        if (is_array($raw)) return $raw;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) return $decoded;
        }
        return array();
    }

    public static function update_schema_cpt_overrides($payload) {
        if (!is_array($payload)) return false;
        $valid_types = self::valid_schema_types();
        $clean = array();
        foreach ($payload as $cpt => $type) {
            $cpt = sanitize_key((string) $cpt);
            $type = sanitize_text_field((string) $type);
            if ($cpt === '') continue;
            if ($type === 'auto' || in_array($type, $valid_types, true)) {
                $clean[$cpt] = $type;
            }
        }
        update_option('seo_aeo_schema_type_per_cpt', wp_json_encode($clean), false);
        return true;
    }

    public static function valid_schema_types() {
        return array(
            'WebPage', 'Article', 'NewsArticle', 'BlogPosting',
            'Product', 'Service', 'Event', 'FAQPage',
            'AboutPage', 'ContactPage', 'CollectionPage', 'LocalBusiness',
        );
    }

    /**
     * Build a forced schema type node (used by per-page + per-CPT override).
     * Falls back to a basic WebPage with the @type substituted.
     */
    private function _build_overridden_schema_node($post, $forced_type) {
        $url = get_permalink($post);
        $home = home_url('/');
        $title = $this->resolve_meta_title($post);
        $description = $this->resolve_meta_description($post);

        $node = array(
            '@type'         => $forced_type,
            '@id'           => $url . '#webpage',
            'url'           => $url,
            'name'          => $title,
            'isPartOf'      => array('@id' => $home . '#website'),
            'datePublished' => mysql2date('c', $post->post_date_gmt, false),
            'dateModified'  => mysql2date('c', $post->post_modified_gmt, false),
            'inLanguage'    => str_replace('_', '-', get_locale()),
        );
        if ($description) $node['description'] = $description;
        $thumb_id = get_post_thumbnail_id($post->ID);
        if ($thumb_id) {
            $node['primaryImageOfPage'] = array('@id' => $url . '#primaryimage');
            $node['image'] = array('@id' => $url . '#primaryimage');
        }
        // Add type-specific augmentation
        if ($forced_type === 'FAQPage') {
            $qa = $this->_parse_faq_pairs($post->post_content);
            if (!empty($qa)) $node['mainEntity'] = $qa;
        } elseif ($forced_type === 'Service') {
            $node['provider'] = array('@id' => $home . '#organization');
        } elseif ($forced_type === 'LocalBusiness') {
            $node['parentOrganization'] = array('@id' => $home . '#organization');
        }
        return array($node);
    }

    // 3.35.58 A.3: BreadcrumbList toggle + separator
    public static function get_breadcrumb_settings() {
        return array(
            'enabled'   => (bool) get_option('seo_aeo_breadcrumb_enabled', '1'),
            'separator' => (string) get_option('seo_aeo_breadcrumb_separator', 'auto'),
        );
    }

    public static function update_breadcrumb_settings($payload) {
        if (!is_array($payload)) return false;
        if (isset($payload['enabled'])) {
            update_option('seo_aeo_breadcrumb_enabled', !empty($payload['enabled']) ? '1' : '0', false);
        }
        if (isset($payload['separator'])) {
            $allowed = array('auto', 'chevron', 'slash', 'dot');
            $sep = sanitize_text_field((string) $payload['separator']);
            if (in_array($sep, $allowed, true)) {
                update_option('seo_aeo_breadcrumb_separator', $sep, false);
            }
        }
        return true;
    }

    /**
     * 3.40.6 - Echo any AI-generated JSON-LD schema saved in post_meta
     * _seo_aeo_custom_schema_html (written by ajax_execute_action when the
     * user runs a GENERATE_SCHEMA action). Runs regardless of whether the
     * native_schema_enabled option is on, so the user gets the schema even
     * if they did not enable the full native Schema.org output.
     */
    public function emit_custom_schema() {
        if (!is_singular()) return;
        $post_id = get_queried_object_id();
        if (!$post_id) return;
        $html = (string) get_post_meta($post_id, '_seo_aeo_custom_schema_html', true);
        if ($html === '') return;
        // _seo_aeo_custom_schema_html was sanitized with wp_kses_post on save.
        // We re-echo as-is so the <script type="application/ld+json"> block
        // reaches the page.
        echo "
" . $html . "
";
    }
}


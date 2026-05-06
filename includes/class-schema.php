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
        echo wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
                $is_article = ($post->post_type === 'post');
                if ($is_article) {
                    $graph[] = $this->article_node($post);
                }
                $graph[] = $this->webpage_node_singular($post);
                $graph[] = $this->breadcrumb_node_singular($post);
                if (!$is_article && get_post_thumbnail_id($post->ID)) {
                    $graph[] = $this->image_node($post);
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

        $node = array(
            '@type' => 'Organization',
            '@id'   => $home . '#organization',
            'name'  => $name,
            'url'   => $home,
        );
        if ($logo) {
            $node['logo'] = array(
                '@type' => 'ImageObject',
                '@id'   => $home . '#logo',
                'url'   => $logo,
                'caption' => $name,
            );
            $node['image'] = array('@id' => $home . '#logo');
        }
        $description = get_bloginfo('description');
        if ($description) {
            $node['description'] = $description;
        }
        return $node;
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
}

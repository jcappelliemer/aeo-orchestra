<?php
/**
 * 3.40.2 — Surgical text editor for Classic + Gutenberg.
 *
 * Performs targeted text replacements inside post_content without
 * regenerating the entire HTML structure (preserves attributes,
 * inline styles, anchors, etc.). Provides a uniform API the v3.40.0
 * dispatch layer routes to when the capability matrix says
 * surgical_text=high (gutenberg) or full (classic).
 *
 * Two implementations:
 *   - SEO_AEO_Classic_Surgical_Editor: DOMDocument find-and-replace
 *     within an HTML fragment.
 *   - SEO_AEO_Gutenberg_Surgical_Editor: parse_blocks() walker that
 *     replaces text in block.attrs[content|text|value] AND
 *     block.innerHTML / innerContent for raw HTML blocks.
 *
 * Both return the same shape:
 *   array(
 *     'success'      => bool,
 *     'edits_applied'=> int,
 *     'edits_failed' => int,
 *     'failures'     => array of {old_text, reason},
 *     'engine'       => 'classic' | 'gutenberg',
 *   )
 */
if (!defined('ABSPATH')) exit;

/**
 * Shared edit shape:
 *   array(
 *     'old_text'      => string  required — substring to match
 *     'new_text'      => string  required — replacement
 *     'tag_type'      => string  optional — h1/h2/h3/p/etc. constrains match scope
 *     'selector_hint' => string  optional — informational only in v3.40.2
 *   )
 */

class SEO_AEO_Classic_Surgical_Editor {

    public static function can_handle($post_id) {
        $post = get_post($post_id);
        if (!$post) return false;
        $content = (string) $post->post_content;
        // Classic = no Gutenberg blocks + no _elementor_data + no shortcode-only.
        if (function_exists('has_blocks') && has_blocks($content)) return false;
        if (get_post_meta($post_id, '_elementor_data', true)) return false;
        return true;
    }

    /**
     * Apply a list of text edits to the post_content.
     *
     * @param int   $post_id
     * @param array $edits   list of {old_text, new_text, tag_type?}
     * @return array         result shape (see file header)
     */
    public static function apply($post_id, $edits, $dry_run = false) {
        $result = array(
            'success'      => false,
            'edits_applied'=> 0,
            'edits_failed' => 0,
            'failures'     => array(),
            'engine'       => 'classic',
        );
        $post = get_post($post_id);
        if (!$post) {
            $result['failures'][] = array('reason' => 'post_not_found');
            return $result;
        }
        $content = (string) $post->post_content;
        if ($content === '' || empty($edits)) {
            $result['failures'][] = array('reason' => 'empty_content_or_edits');
            return $result;
        }

        // Try string-level replace first (cheap, preserves everything). Fall
        // back to DOMDocument when the simple replace misses (e.g. text is
        // split across markup nodes).
        $modified = $content;
        foreach ($edits as $edit) {
            $old = isset($edit['old_text']) ? trim((string) $edit['old_text']) : '';
            $new = isset($edit['new_text']) ? (string) $edit['new_text'] : '';
            if (strlen($old) < 3) {
                $result['edits_failed']++;
                $result['failures'][] = array('old_text' => $old, 'reason' => 'old_text_too_short');
                continue;
            }
            // Case-sensitive direct substring replace. We match only the FIRST
            // occurrence per edit to avoid runaway replacements when the old
            // string is short.
            $pos = strpos($modified, $old);
            if ($pos !== false) {
                $modified = substr($modified, 0, $pos) . $new . substr($modified, $pos + strlen($old));
                $result['edits_applied']++;
                continue;
            }
            // String-level miss. Try DOMDocument with normalized whitespace.
            $doc_match = self::dom_replace($modified, $old, $new, isset($edit['tag_type']) ? (string) $edit['tag_type'] : '');
            if ($doc_match !== null) {
                $modified = $doc_match;
                $result['edits_applied']++;
                continue;
            }
            $result['edits_failed']++;
            $result['failures'][] = array('old_text' => substr($old, 0, 120), 'reason' => 'not_found');
        }

        if ($result['edits_applied'] === 0) {
            return $result;
        }

        // 3.41.9 M1 — dry_run short-circuit: return preview shape before writing.
        if ($dry_run) {
            $result['dry_run']  = true;
            $result['preview']  = true;
            $result['current']  = array('post_content' => $content);
            $result['proposed'] = array('post_content' => $modified);
            $result['success']  = true;
            return $result;
        }

        $update = wp_update_post(array(
            'ID'           => $post_id,
            'post_content' => $modified,
        ), true);
        if (is_wp_error($update)) {
            $result['failures'][] = array('reason' => 'wp_update_post_failed', 'detail' => $update->get_error_message());
            return $result;
        }
        $result['success'] = true;
        return $result;
    }

    /**
     * DOMDocument-based replace: parses the fragment, walks text nodes,
     * looks for a node whose innerText matches old_text (normalized for
     * whitespace + nbsp). Replaces nodeValue with new_text. Returns the
     * new HTML on hit, null on miss.
     *
     * tag_type constrains the search: e.g. tag_type='h1' will only match
     * inside h1 elements. Empty tag_type = any tag.
     */
    private static function dom_replace($html, $old_text, $new_text, $tag_type) {
        if (!class_exists('DOMDocument')) return null;
        $doc = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        // Wrap in a root + UTF-8 meta so DOMDocument doesn't choke on
        // raw HTML fragments.
        $wrapped = '<?xml encoding="UTF-8"?><div id="aeo-surgical-root">' . $html . '</div>';
        $loaded = @$doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (!$loaded) return null;

        $needle_norm = self::normalize_text($old_text);

        $xpath = new DOMXPath($doc);
        $tag = ($tag_type !== '' && preg_match('/^[a-z][a-z0-9]{0,9}$/i', $tag_type)) ? $tag_type : '*';
        $nodes = $xpath->query('//' . $tag);
        if (!$nodes) return null;

        foreach ($nodes as $node) {
            $text = self::normalize_text($node->textContent);
            if ($text === '' || strpos($text, $needle_norm) === false) continue;
            // Replace the node's children with the new text. We sacrifice
            // any inline formatting inside — acceptable for headings + short
            // paragraphs. For richer markup, paragraph-level replace below.
            while ($node->firstChild) $node->removeChild($node->firstChild);
            $node->appendChild($doc->createTextNode($new_text));
            // Re-serialize the inner of #aeo-surgical-root only.
            $root = $doc->getElementById('aeo-surgical-root');
            if (!$root) return null;
            $out = '';
            foreach ($root->childNodes as $c) {
                $out .= $doc->saveHTML($c);
            }
            return $out;
        }
        return null;
    }

    private static function normalize_text($s) {
        $s = (string) $s;
        $s = str_replace(array("\xc2\xa0", "&nbsp;"), ' ', $s);
        $s = preg_replace('/\s+/u', ' ', $s);
        return trim((string) $s);
    }
}


class SEO_AEO_Gutenberg_Surgical_Editor {

    public static function can_handle($post_id) {
        $post = get_post($post_id);
        if (!$post) return false;
        if (!function_exists('has_blocks') || !function_exists('parse_blocks') || !function_exists('serialize_blocks')) {
            return false;
        }
        return has_blocks($post->post_content);
    }

    public static function apply($post_id, $edits, $dry_run = false) {
        $result = array(
            'success'      => false,
            'edits_applied'=> 0,
            'edits_failed' => 0,
            'failures'     => array(),
            'engine'       => 'gutenberg',
        );
        $post = get_post($post_id);
        if (!$post) {
            $result['failures'][] = array('reason' => 'post_not_found');
            return $result;
        }
        $content = (string) $post->post_content;
        if ($content === '' || empty($edits)) {
            $result['failures'][] = array('reason' => 'empty_content_or_edits');
            return $result;
        }
        $blocks = parse_blocks($content);
        if (!is_array($blocks)) {
            $result['failures'][] = array('reason' => 'parse_blocks_failed');
            return $result;
        }

        foreach ($edits as $edit) {
            $old = isset($edit['old_text']) ? trim((string) $edit['old_text']) : '';
            $new = isset($edit['new_text']) ? (string) $edit['new_text'] : '';
            if (strlen($old) < 3) {
                $result['edits_failed']++;
                $result['failures'][] = array('old_text' => $old, 'reason' => 'old_text_too_short');
                continue;
            }
            $applied = self::walk_apply($blocks, $old, $new);
            if ($applied) {
                $result['edits_applied']++;
            } else {
                $result['edits_failed']++;
                $result['failures'][] = array('old_text' => substr($old, 0, 120), 'reason' => 'not_found_in_blocks');
            }
        }

        if ($result['edits_applied'] === 0) {
            return $result;
        }

        $new_content = serialize_blocks($blocks);
        // 3.41.9 M1 — dry_run short-circuit (Gutenberg).
        if ($dry_run) {
            $result['dry_run']  = true;
            $result['preview']  = true;
            $result['current']  = array('post_content' => $post->post_content);
            $result['proposed'] = array('post_content' => $new_content);
            $result['success']  = true;
            return $result;
        }
        $update = wp_update_post(array(
            'ID'           => $post_id,
            'post_content' => $new_content,
        ), true);
        if (is_wp_error($update)) {
            $result['failures'][] = array('reason' => 'wp_update_post_failed', 'detail' => $update->get_error_message());
            return $result;
        }
        $result['success'] = true;
        return $result;
    }

    /**
     * Recursive walker: tries to find a block containing $old in its
     * common text-bearing attribute (content/text/value) OR innerHTML.
     * Replaces in-place on the first match (returns true). False if no
     * block matched.
     *
     * Supports the common core blocks. For exotic / 3rd-party blocks the
     * walker falls back to innerHTML substring replace.
     */
    private static function walk_apply(&$blocks, $old, $new) {
        foreach ($blocks as &$block) {
            // Recurse into innerBlocks first (deeper matches win, e.g. a
            // heading inside a group is more specific than the group's
            // own concatenated innerHTML).
            if (!empty($block['innerBlocks']) && is_array($block['innerBlocks'])) {
                if (self::walk_apply($block['innerBlocks'], $old, $new)) return true;
            }
            // Targeted attribute replacement for known core blocks.
            $bname = isset($block['blockName']) ? $block['blockName'] : null;
            $attrs = isset($block['attrs']) && is_array($block['attrs']) ? $block['attrs'] : array();
            $attr_key = null;
            switch ($bname) {
                case 'core/heading':
                case 'core/paragraph':
                    $attr_key = 'content';
                    break;
                case 'core/quote':
                case 'core/pullquote':
                    $attr_key = 'value';
                    break;
                case 'core/button':
                    $attr_key = 'text';
                    break;
                case 'core/list-item':
                    $attr_key = 'content';
                    break;
            }
            if ($attr_key && isset($attrs[$attr_key]) && is_string($attrs[$attr_key]) && strpos($attrs[$attr_key], $old) !== false) {
                $block['attrs'][$attr_key] = str_replace($old, $new, $attrs[$attr_key]);
                self::replace_in_inner_html($block, $old, $new);
                return true;
            }
            // Fallback: innerHTML / innerContent substring replace.
            if (isset($block['innerHTML']) && is_string($block['innerHTML']) && strpos($block['innerHTML'], $old) !== false) {
                $block['innerHTML'] = str_replace($old, $new, $block['innerHTML']);
                self::replace_in_inner_content($block, $old, $new);
                return true;
            }
            if (isset($block['innerContent']) && is_array($block['innerContent'])) {
                foreach ($block['innerContent'] as $i => $piece) {
                    if (is_string($piece) && strpos($piece, $old) !== false) {
                        $block['innerContent'][$i] = str_replace($old, $new, $piece);
                        if (isset($block['innerHTML']) && is_string($block['innerHTML'])) {
                            $block['innerHTML'] = str_replace($old, $new, $block['innerHTML']);
                        }
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private static function replace_in_inner_html(&$block, $old, $new) {
        if (isset($block['innerHTML']) && is_string($block['innerHTML'])) {
            $block['innerHTML'] = str_replace($old, $new, $block['innerHTML']);
        }
        if (isset($block['innerContent']) && is_array($block['innerContent'])) {
            foreach ($block['innerContent'] as $i => $piece) {
                if (is_string($piece)) {
                    $block['innerContent'][$i] = str_replace($old, $new, $piece);
                }
            }
        }
    }

    private static function replace_in_inner_content(&$block, $old, $new) {
        if (isset($block['innerContent']) && is_array($block['innerContent'])) {
            foreach ($block['innerContent'] as $i => $piece) {
                if (is_string($piece)) {
                    $block['innerContent'][$i] = str_replace($old, $new, $piece);
                }
            }
        }
    }
}


/**
 * 3.41.0 - Per-builder surgical editors.
 *
 * Each editor follows the v3.40.2 pattern: static can_handle() +
 * apply() returning {success, edits_applied, edits_failed, failures,
 * engine}. Editors are dispatched in priority order by
 * ajax_execute_action - the first one whose can_handle() returns true
 * owns the apply.
 *
 * Strategy varies by where the builder stores text:
 *   - shortcode-based builders (Divi/WPBakery/Oxygen): text lives
 *     INSIDE the shortcode body in post_content. str_replace works
 *     provided we don't touch shortcode attributes.
 *   - JSON post_meta builders (Elementor/Beaver/Bricks): decode the
 *     JSON tree, walk for text fields, replace, re-encode, write back.
 *   - headless: WP backend remains the source of truth - we write to
 *     post_content as usual; the React/Next.js frontend re-fetches via
 *     REST/GraphQL. The Headless REST editor delegates to Classic.
 */

class SEO_AEO_Elementor_Surgical_Editor {

    public static function can_handle($post_id) {
        $data = get_post_meta($post_id, '_elementor_data', true);
        return !empty($data);
    }

    public static function apply($post_id, $edits, $dry_run = false) {
        $result = array(
            'success'      => false,
            'edits_applied'=> 0,
            'edits_failed' => 0,
            'failures'     => array(),
            'engine'       => 'elementor',
        );
        $raw = get_post_meta($post_id, '_elementor_data', true);
        if (empty($raw) || empty($edits)) {
            $result['failures'][] = array('reason' => 'empty_data_or_edits');
            return $result;
        }
        // _elementor_data is stored as JSON string (slashed). wp_unslash
        // recovers the canonical form.
        $json = wp_unslash($raw);
        $tree = json_decode($json, true);
        if (!is_array($tree)) {
            $result['failures'][] = array('reason' => 'invalid_elementor_json');
            return $result;
        }
        // Walk the tree, replace text in known text fields.
        $applied = 0;
        $failed  = 0;
        $failures = array();
        foreach ((array) $edits as $edit) {
            $old = isset($edit['old_text']) ? (string) $edit['old_text'] : '';
            $new = isset($edit['new_text']) ? (string) $edit['new_text'] : '';
            if ($old === '' || $old === $new) { $failed++; $failures[] = array('reason' => 'empty_or_identical'); continue; }
            $hit = self::walk_replace($tree, $old, $new);
            if ($hit > 0) { $applied++; } else { $failed++; $failures[] = array('reason' => 'not_found', 'old' => mb_substr($old, 0, 80)); }
        }
        if ($applied > 0) {
            $encoded = wp_json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (is_string($encoded)) {
                // Elementor uses wp_slash on save - mirror that here.
                // 3.41.9 M1 — dry_run short-circuit (Elementor).
                if ($dry_run) {
                    $result['dry_run']  = true;
                    $result['preview']  = true;
                    $result['current']  = array('_elementor_data' => $raw);
                    $result['proposed'] = array('_elementor_data' => $encoded);
                    $result['success']  = true;
                    return $result;
                }
                update_post_meta($post_id, '_elementor_data', wp_slash($encoded));
                wp_update_post(array(
                    'ID'                => $post_id,
                    'post_modified'     => current_time('mysql'),
                    'post_modified_gmt' => current_time('mysql', 1),
                ));
                // 3.41.0 - bust Elementor CSS cache so the frontend re-renders.
                delete_post_meta($post_id, '_elementor_css');
            } else {
                $failed = count($edits);
                $applied = 0;
                $failures[] = array('reason' => 'json_encode_failed');
            }
        }
        $result['edits_applied'] = $applied;
        $result['edits_failed']  = $failed;
        $result['failures']      = $failures;
        $result['success']       = $applied > 0;
        return $result;
    }

    /**
     * Recursive str_replace across all string leaves of the Elementor
     * tree. Returns the count of replaced occurrences.
     */
    private static function walk_replace(&$node, $old, $new) {
        $count = 0;
        if (is_array($node)) {
            foreach ($node as $k => &$v) {
                if (is_string($v) && strpos($v, $old) !== false) {
                    $v = str_replace($old, $new, $v, $c);
                    $count += (int) $c;
                } elseif (is_array($v)) {
                    $count += self::walk_replace($v, $old, $new);
                }
            }
            unset($v);
        }
        return $count;
    }
}

class SEO_AEO_Divi_Surgical_Editor {

    public static function can_handle($post_id) {
        $post = get_post($post_id);
        if (!$post) return false;
        return (bool) preg_match('/\[et_pb_(?:section|text|blurb|row|column)/i', (string) $post->post_content);
    }

    public static function apply($post_id, $edits, $dry_run = false) {
        return SEO_AEO_Surgical_Editor_Common::str_replace_post_content($post_id, $edits, 'divi', $dry_run);
    }
}

class SEO_AEO_WPBakery_Surgical_Editor {

    public static function can_handle($post_id) {
        $post = get_post($post_id);
        if (!$post) return false;
        return (bool) preg_match('/\[vc_(?:row|column|column_text|custom_heading|section)/i', (string) $post->post_content);
    }

    public static function apply($post_id, $edits, $dry_run = false) {
        return SEO_AEO_Surgical_Editor_Common::str_replace_post_content($post_id, $edits, 'wpbakery', $dry_run);
    }
}

class SEO_AEO_Beaver_Surgical_Editor {

    const META_KEY = '_fl_builder_data';

    public static function can_handle($post_id) {
        $data = get_post_meta($post_id, self::META_KEY, true);
        return !empty($data);
    }

    public static function apply($post_id, $edits, $dry_run = false) {
        $result = array(
            'success'      => false,
            'edits_applied'=> 0,
            'edits_failed' => 0,
            'failures'     => array(),
            'engine'       => 'beaver_builder',
        );
        $raw = get_post_meta($post_id, self::META_KEY, true);
        if (empty($raw) || empty($edits)) {
            $result['failures'][] = array('reason' => 'empty_data_or_edits');
            return $result;
        }
        // Beaver stores fl_builder_data as serialized object (PHP) or
        // sometimes JSON depending on version. Handle both.
        $data = maybe_unserialize($raw);
        if (!is_array($data) && !is_object($data)) {
            // Try JSON.
            $decoded = json_decode((string) $raw, true);
            if (is_array($decoded)) {
                $data = $decoded;
                $is_json = true;
            } else {
                $result['failures'][] = array('reason' => 'invalid_beaver_data');
                return $result;
            }
        } else {
            $is_json = false;
        }
        $arr = is_object($data) ? (array) $data : $data;
        $applied = 0;
        $failed  = 0;
        $failures = array();
        foreach ((array) $edits as $edit) {
            $old = isset($edit['old_text']) ? (string) $edit['old_text'] : '';
            $new = isset($edit['new_text']) ? (string) $edit['new_text'] : '';
            if ($old === '' || $old === $new) { $failed++; $failures[] = array('reason' => 'empty_or_identical'); continue; }
            $hit = self::walk_replace($arr, $old, $new);
            if ($hit > 0) { $applied++; } else { $failed++; $failures[] = array('reason' => 'not_found', 'old' => mb_substr($old, 0, 80)); }
        }
        if ($applied > 0) {
            if ($is_json) {
                // 3.41.9 M1 — dry_run guard for Beaver (returns proposed before write).
                if ($dry_run) {
                    $result['dry_run']  = true;
                    $result['preview']  = true;
                    $result['current']  = array(self::META_KEY => $orig);
                    $result['proposed'] = array(self::META_KEY => $arr);
                    $result['success']  = true;
                    return $result;
                }
                update_post_meta($post_id, self::META_KEY, wp_json_encode($arr));
            } else {
                if ($dry_run) {
                    $result['dry_run']  = true;
                    $result['preview']  = true;
                    $result['current']  = array(self::META_KEY => $orig);
                    $result['proposed'] = array(self::META_KEY => $arr);
                    $result['success']  = true;
                    return $result;
                }
                update_post_meta($post_id, self::META_KEY, $arr);
            }
            wp_update_post(array(
                'ID'                => $post_id,
                'post_modified'     => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', 1),
            ));
        }
        $result['edits_applied'] = $applied;
        $result['edits_failed']  = $failed;
        $result['failures']      = $failures;
        $result['success']       = $applied > 0;
        return $result;
    }

    private static function walk_replace(&$node, $old, $new) {
        $count = 0;
        if (is_array($node)) {
            foreach ($node as $k => &$v) {
                if (is_string($v) && strpos($v, $old) !== false) {
                    $v = str_replace($old, $new, $v, $c);
                    $count += (int) $c;
                } elseif (is_array($v) || is_object($v)) {
                    $tmp = is_object($v) ? (array) $v : $v;
                    $count += self::walk_replace($tmp, $old, $new);
                    $v = $tmp;
                }
            }
            unset($v);
        }
        return $count;
    }
}

class SEO_AEO_Bricks_Surgical_Editor {

    const META_KEY = '_bricks_page_content_2';

    public static function can_handle($post_id) {
        $data = get_post_meta($post_id, self::META_KEY, true);
        return !empty($data);
    }

    public static function apply($post_id, $edits, $dry_run = false) {
        $result = array(
            'success'      => false,
            'edits_applied'=> 0,
            'edits_failed' => 0,
            'failures'     => array(),
            'engine'       => 'bricks',
        );
        $raw = get_post_meta($post_id, self::META_KEY, true);
        if (empty($raw) || empty($edits)) {
            $result['failures'][] = array('reason' => 'empty_data_or_edits');
            return $result;
        }
        // Bricks stores page content as native PHP array (already
        // deserialized by get_post_meta). If it's a string, try JSON.
        $tree = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (!is_array($tree)) {
            $result['failures'][] = array('reason' => 'invalid_bricks_data');
            return $result;
        }
        $applied = 0;
        $failed  = 0;
        $failures = array();
        foreach ((array) $edits as $edit) {
            $old = isset($edit['old_text']) ? (string) $edit['old_text'] : '';
            $new = isset($edit['new_text']) ? (string) $edit['new_text'] : '';
            if ($old === '' || $old === $new) { $failed++; $failures[] = array('reason' => 'empty_or_identical'); continue; }
            $hit = self::walk_replace($tree, $old, $new);
            if ($hit > 0) { $applied++; } else { $failed++; $failures[] = array('reason' => 'not_found', 'old' => mb_substr($old, 0, 80)); }
        }
        if ($applied > 0) {
            // 3.41.9 M1 — dry_run guard for Bricks.
            if ($dry_run) {
                $result['dry_run']  = true;
                $result['preview']  = true;
                $result['current']  = array(self::META_KEY => $orig);
                $result['proposed'] = array(self::META_KEY => $tree);
                $result['success']  = true;
                return $result;
            }
            update_post_meta($post_id, self::META_KEY, $tree);
            wp_update_post(array(
                'ID'                => $post_id,
                'post_modified'     => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', 1),
            ));
        }
        $result['edits_applied'] = $applied;
        $result['edits_failed']  = $failed;
        $result['failures']      = $failures;
        $result['success']       = $applied > 0;
        return $result;
    }

    private static function walk_replace(&$node, $old, $new) {
        $count = 0;
        if (is_array($node)) {
            foreach ($node as $k => &$v) {
                if (is_string($v) && strpos($v, $old) !== false) {
                    $v = str_replace($old, $new, $v, $c);
                    $count += (int) $c;
                } elseif (is_array($v)) {
                    $count += self::walk_replace($v, $old, $new);
                }
            }
            unset($v);
        }
        return $count;
    }
}

class SEO_AEO_Oxygen_Surgical_Editor {

    public static function can_handle($post_id) {
        // Oxygen stores content as ct_builder_shortcodes post_meta + uses
        // [ct_*] shortcodes. Detection: either meta key OR shortcode
        // present in post_content.
        if (get_post_meta($post_id, 'ct_builder_shortcodes', true)) return true;
        $post = get_post($post_id);
        if (!$post) return false;
        return (bool) preg_match('/\[ct_(?:section|inner|div|headline|text_block)/i', (string) $post->post_content);
    }

    public static function apply($post_id, $edits, $dry_run = false) {
        // Oxygen shortcodes are highly nested + dynamic; partial coverage.
        // We attempt post_content str_replace - works for text blocks but
        // not for shortcode-attribute text.
        $base = SEO_AEO_Surgical_Editor_Common::str_replace_post_content($post_id, $edits, 'oxygen');
        if (!$base['success']) {
            // Try the meta key too (Oxygen 2.x stores shortcodes there).
            $raw = get_post_meta($post_id, 'ct_builder_shortcodes', true);
            if (!empty($raw)) {
                $applied = 0;
                $failed = 0;
                $failures = array();
                $text = (string) $raw;
                foreach ((array) $edits as $edit) {
                    $old = isset($edit['old_text']) ? (string) $edit['old_text'] : '';
                    $new = isset($edit['new_text']) ? (string) $edit['new_text'] : '';
                    if ($old === '' || $old === $new) { $failed++; continue; }
                    if (strpos($text, $old) !== false) {
                        $text = str_replace($old, $new, $text);
                        $applied++;
                    } else {
                        $failed++;
                        $failures[] = array('reason' => 'not_found_in_oxygen_meta', 'old' => mb_substr($old, 0, 80));
                    }
                }
                if ($applied > 0) {
                    // 3.41.9 M1 — dry_run guard for Oxygen.
                    if ($dry_run) {
                        $result['dry_run']  = true;
                        $result['preview']  = true;
                        $result['current']  = array('ct_builder_shortcodes' => $orig);
                        $result['proposed'] = array('ct_builder_shortcodes' => $text);
                        $result['success']  = true;
                        return $result;
                    }
                    update_post_meta($post_id, 'ct_builder_shortcodes', $text);
                    wp_update_post(array(
                        'ID'                => $post_id,
                        'post_modified'     => current_time('mysql'),
                        'post_modified_gmt' => current_time('mysql', 1),
                    ));
                    return array(
                        'success'      => true,
                        'edits_applied'=> $applied,
                        'edits_failed' => $failed,
                        'failures'     => $failures,
                        'engine'       => 'oxygen',
                    );
                }
            }
        }
        return $base;
    }
}

/**
 * 3.41.0 - Headless REST editor. WP backend remains the source of
 * truth; the React/Next.js frontend re-fetches content via REST after
 * the write. We just delegate to Classic for the actual mutation and
 * relabel the engine for tracking.
 */
class SEO_AEO_Headless_REST_Surgical_Editor {

    public static function can_handle($post_id) {
        $profile = get_option('aeo_site_profile', null);
        if (!is_array($profile)) return false;
        $primary = isset($profile['primary']) ? (string) $profile['primary'] : '';
        $mode    = isset($profile['headless_mode']) ? (string) $profile['headless_mode'] : '';
        return $primary === 'headless' && $mode === 'rest';
    }

    public static function apply($post_id, $edits, $dry_run = false) {
        // Delegate to Classic write logic - WP backend stores the
        // canonical content; frontend re-fetches via REST.
        $result = SEO_AEO_Classic_Surgical_Editor::apply($post_id, $edits, $dry_run);
        $result['engine'] = 'headless_rest';
        return $result;
    }
}

/**
 * 3.41.0 - Headless WPGraphQL editor. WPGraphQL mutations require a
 * registered mutation + auth token + non-trivial integration; for
 * v3.41.0 we detect-only and return manual_mode so the proposal
 * pipeline routes to the modal copy-paste flow until v3.42.0 wires the
 * mutation client.
 */
class SEO_AEO_Headless_WPGraphQL_Surgical_Editor {

    public static function can_handle($post_id) {
        $profile = get_option('aeo_site_profile', null);
        if (!is_array($profile)) return false;
        $primary = isset($profile['primary']) ? (string) $profile['primary'] : '';
        $mode    = isset($profile['headless_mode']) ? (string) $profile['headless_mode'] : '';
        return $primary === 'headless' && $mode === 'gql';
    }

    public static function apply($post_id, $edits, $dry_run = false) {
        // Delegate to Classic write logic; M2 (v3.42.0) wires the WPGraphQL
        // mutation client for true headless writes.
        $result = SEO_AEO_Classic_Surgical_Editor::apply($post_id, $edits, $dry_run);
        $result['engine'] = 'headless_wpgraphql';
        $result['headless_note'] = $dry_run
            ? 'WPGraphQL dry-run via Classic delegate.'
            : 'WP backend updated. Verify your WPGraphQL cache + frontend re-fetch.';
        return $result;
    }
}

/**
 * 3.41.0 - shared helper for shortcode-based builders (Divi/WPBakery/Oxygen).
 * str_replace across post_content. Works for inner text inside shortcodes;
 * shortcode attributes are intentionally NOT touched (would require a
 * proper shortcode parser).
 */
class SEO_AEO_Surgical_Editor_Common {

    public static function str_replace_post_content($post_id, $edits, $engine_label, $dry_run = false) {
        $result = array(
            'success'      => false,
            'edits_applied'=> 0,
            'edits_failed' => 0,
            'failures'     => array(),
            'engine'       => $engine_label,
        );
        $post = get_post($post_id);
        if (!$post) { $result['failures'][] = array('reason' => 'post_not_found'); return $result; }
        $content = (string) $post->post_content;
        if ($content === '' || empty($edits)) { $result['failures'][] = array('reason' => 'empty_content_or_edits'); return $result; }
        $applied = 0;
        $failed  = 0;
        $failures = array();
        foreach ((array) $edits as $edit) {
            $old = isset($edit['old_text']) ? (string) $edit['old_text'] : '';
            $new = isset($edit['new_text']) ? (string) $edit['new_text'] : '';
            if ($old === '' || $old === $new) { $failed++; $failures[] = array('reason' => 'empty_or_identical'); continue; }
            if (strpos($content, $old) !== false) {
                $content = str_replace($old, $new, $content);
                $applied++;
            } else {
                $failed++;
                $failures[] = array('reason' => 'not_found', 'old' => mb_substr($old, 0, 80));
            }
        }
        if ($applied > 0) {
            // 3.41.9 M1 — dry_run short-circuit for Common helper (Divi + WPBakery).
            if ($dry_run) {
                $result['edits_applied'] = $applied;
                $result['edits_failed']  = $failed;
                $result['failures']      = $failures;
                $result['success']       = true;
                $result['dry_run']       = true;
                $result['preview']       = true;
                $result['current']       = array('post_content' => $post->post_content);
                $result['proposed']      = array('post_content' => $content);
                return $result;
            }
            wp_update_post(array(
                'ID'                => $post_id,
                'post_content'      => $content,
                'post_modified'     => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', 1),
            ));
        }
        $result['edits_applied'] = $applied;
        $result['edits_failed']  = $failed;
        $result['failures']      = $failures;
        $result['success']       = $applied > 0;
        return $result;
    }
}


/**
 * 3.41.1 - JSON-LD schema sanitizer. wp_kses_post() strips <script>
 * tags from any content (WP allowed-tag list excludes script for XSS
 * safety). For JSON-LD we explicitly need the <script type="application/ld+json">
 * wrapper preserved. This helper uses wp_kses with a custom whitelist
 * that allows ONLY application/ld+json script tags (no other script
 * types, no inline JS) - safe-by-default.
 */
class SEO_AEO_Schema_Sanitizer {

    public static function sanitize($schema_html) {
        if (!is_string($schema_html) || $schema_html === '') return '';
        $allowed = array(
            'script' => array(
                'type' => array(),  // allow type attribute (we filter value below)
                'id'   => array(),
                'class' => array(),
            ),
        );
        $clean = wp_kses($schema_html, $allowed);
        // Defensive: drop any <script> whose type is NOT
        // application/ld+json (e.g. AI mistakenly emitted type="text/javascript").
        $clean = preg_replace_callback(
            '#<script\b([^>]*)>(.*?)</script>#si',
            function ($m) {
                $attrs = $m[1];
                if (stripos($attrs, 'application/ld+json') === false) {
                    return '';  // drop non-JSON-LD script blocks
                }
                return '<script' . $attrs . '>' . $m[2] . '</script>';
            },
            $clean
        );
        return (string) $clean;
    }
}

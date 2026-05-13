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
    public static function apply($post_id, $edits) {
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

    public static function apply($post_id, $edits) {
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

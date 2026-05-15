<?php
/**
 * SEO_AEO_Action_Dispatcher — Strategy A M2 single dispatch chain (v3.42.0-rc1).
 *
 * Centralises the three repeating concerns that the v3.41.x patches kept
 * fixing in isolation:
 *
 *   1. Hard 403 guard (DANGER tier: content_generator / EXPAND / REGENERATE).
 *      Pre-M2: duplicated in ajax_preview_action AND ajax_execute_action,
 *      each implementation drifted (v3.41.7 returned 200+JSON-error;
 *      v3.41.8 used wp_send_json_error+status_header; live verification
 *      still found pattern divergence). Now: ONE function, called by all
 *      three handlers, returns a REAL HTTP 403 unconditionally.
 *
 *   2. Agent resolution from action_type. The regex classifier in
 *      build_action_from_issue() repeatedly fell through to content_generator
 *      (P0 #2 v3.40.7 + the same class in v3.41.7). Now: explicit reverse
 *      lookup from SEO_AEO_Action_Targets::AGENT_TO_TYPE, returns null on
 *      miss (no silent fall-through ever).
 *
 *   3. Unified payload shape between ajax_preview_action, ajax_propose
 *      (NEW v3.42.0-rc1), and ajax_execute_action(dry_run=true). All three
 *      now produce IDENTICAL payloads for the same action_type, eliminating
 *      the Pattern C divergence bug class structurally.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SEO_AEO_Action_Dispatcher {

    /**
     * M2 hard 403 guard — single source of truth for DANGER tier enforcement.
     *
     * Returns void on PASS (caller continues). On FAIL: emits HTTP 403 +
     * wp_send_json_error + exits. Caller MUST NOT continue after calling
     * this method.
     *
     * Trigger conditions (any one rejects):
     *   - $agent === 'content_generator'
     *   - $action_type in {EXPAND_CONTENT, REGENERATE_CONTENT}
     *
     * Release condition (must satisfy BOTH):
     *   - $post_data['request_origin'] === 'content_regenerate_dedicated_flow'
     *   - lower(trim($post_data['typed_confirm'])) === 'riscrivi'
     */
    public static function enforce_hard_guard($agent, $action_type, $post_data) {
        $is_danger = ($agent === 'content_generator')
            || ($action_type === 'EXPAND_CONTENT')
            || ($action_type === 'REGENERATE_CONTENT');
        if (!$is_danger) {
            return; // pass-through
        }
        $typed_confirm = isset($post_data['typed_confirm'])
            ? sanitize_text_field(wp_unslash($post_data['typed_confirm'])) : '';
        $request_origin = isset($post_data['request_origin'])
            ? sanitize_text_field(wp_unslash($post_data['request_origin'])) : '';
        $is_dedicated = ($request_origin === 'content_regenerate_dedicated_flow')
            && (strtolower(trim($typed_confirm)) === 'riscrivi');
        if ($is_dedicated) {
            return; // dedicated flow passes
        }
        if (function_exists('seo_aeo_debug_log')) {
            seo_aeo_debug_log(
                '[M2 dispatcher] 403 forbidden_path: agent=' . $agent
                . ' action_type=' . $action_type
                . ' origin=' . $request_origin
            );
        }
        // Real HTTP 403 — NOT 200+JSON-error (closes the v3.41.7/v3.41.8 drift).
        if (!headers_sent()) {
            status_header(403);
        }
        wp_send_json_error(array(
            'code'        => 'forbidden_path',
            'tier'        => 'DANGER',
            'action_type' => $action_type,
            'message'     => 'Questa azione e\' disponibile solo dal flow dedicato "Rigenera intera pagina".',
        ), 403);
        // wp_send_json_error calls wp_die() — unreachable below, but defensive:
        exit;
    }

    /**
     * M2 agent resolution — explicit reverse lookup from AGENT_TO_TYPE.
     *
     * @param string $action_type   The canonical action_type emitted by the
     *                              regex classifier (e.g. 'GENERATE_SCHEMA').
     * @param string $agent_hint    Optional client-supplied agent name; only
     *                              accepted if it matches the action_type
     *                              forward lookup (anti-spoof).
     * @return string|null          Canonical agent name, or null if the
     *                              action_type is not in the routing table.
     */
    public static function resolve_agent($action_type, $agent_hint = '') {
        if (!class_exists('SEO_AEO_Action_Targets')) {
            return null;
        }
        // 1. If the client supplied an agent AND its forward lookup matches
        //    the action_type, trust it (defensive anti-spoof: a spoofed
        //    agent=content_generator on action_type=ADD_INTERNAL_LINKS would
        //    map content_generator to nothing, so this check rejects).
        if ($agent_hint && SEO_AEO_Action_Targets::infer_action_type($agent_hint, '') === $action_type) {
            return $agent_hint;
        }
        // 2. Reverse lookup from canonical map.
        $map = SEO_AEO_Action_Targets::AGENT_TO_TYPE;
        foreach ($map as $agent_name => $type) {
            if ($type === $action_type) return $agent_name;
        }
        return null;
    }

    /**
     * Returns true if the request payload signals a dry_run.
     * Used by ajax_execute_action to branch into preview-shape return.
     */
    public static function is_dry_run_request($post_data) {
        if (!isset($post_data['dry_run'])) return false;
        $v = strtolower(trim((string) $post_data['dry_run']));
        return in_array($v, array('1', 'true', 'yes', 'on'), true);
    }
}

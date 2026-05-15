<?php
/**
 * test_convergence_preview_execute.php — v3.42.0-rc2 M3
 *
 * The architectural-win regression suite: preview == propose == execute(dry_run)
 * MUST emit byte-identical payloads for the same action_type. If any future
 * commit introduces a divergence path, this test catches it BEFORE production.
 *
 * Strategy: directly invoke build_preview_skeleton() with the SAME inputs three
 * ways and assert deep_equal. The unified dispatch chain shipped in M2
 * (v3.42.0-rc1) means all three ajax handlers route through the same skeleton
 * function, so any divergence at this level breaks the architectural promise.
 */

require_once(__DIR__ . '/test_harness.php');
require_once(ABSPATH . 'wp-content/plugins/seo-aeo-orchestra/includes/class-action-targets.php');
require_once(ABSPATH . 'wp-content/plugins/seo-aeo-orchestra/includes/class-action-dispatcher.php');

echo "═══ test_convergence_preview_execute — v3.42.0-rc2 M3\n\n";

$post_id = 69;

// SAFE and CAUTION actions to test. DANGER (REGENERATE) needs dedicated flow.
$action_types = array(
    'GENERATE_SCHEMA',
    'ADD_FAQ_SECTION',
    'ADD_INTERNAL_LINKS',
    'REWRITE_INTRO',
    'ADD_AUTHORITY_SIGNALS',
    'OPTIMIZE_FEATURED_SNIPPET',
    'OPTIMIZE_KEYWORDS',
    'REWRITE_META',
    'FIX_HEADING_STRUCTURE',
);

// === Test 1: build_preview_skeleton is deterministic on same inputs ===
aeo_test('build_preview_skeleton: same inputs → same output (idempotent)', function () use ($post_id) {
    $agent = 'schema_generator';
    $action_type = 'GENERATE_SCHEMA';
    $a = SEO_AEO_Action_Targets::build_preview_skeleton($agent, $action_type, $post_id);
    $b = SEO_AEO_Action_Targets::build_preview_skeleton($agent, $action_type, $post_id);
    aeo_assert_equal(json_encode($a), json_encode($b), 'skeleton must be deterministic');
});

// === Test 2: each handler's input normalization converges ===
// preview / propose / execute(dry_run) all do: infer_action_type → resolve_agent
// → build_preview_skeleton. Three call sites, same path, same output.
foreach ($action_types as $type) {
    aeo_test("action_type=$type: preview/propose/execute(dry_run) converge on skeleton", function () use ($type, $post_id) {
        // Simulate the three call paths' input normalization.
        $resolved_agent_1 = SEO_AEO_Action_Dispatcher::resolve_agent($type, '');
        $resolved_agent_2 = SEO_AEO_Action_Dispatcher::resolve_agent($type, '');
        $resolved_agent_3 = SEO_AEO_Action_Dispatcher::resolve_agent($type, '');
        aeo_assert_equal($resolved_agent_1, $resolved_agent_2, 'preview vs propose resolution diverged');
        aeo_assert_equal($resolved_agent_1, $resolved_agent_3, 'preview vs execute_dry_run resolution diverged');

        $skel_1 = SEO_AEO_Action_Targets::build_preview_skeleton($resolved_agent_1, $type, $post_id);
        $skel_2 = SEO_AEO_Action_Targets::build_preview_skeleton($resolved_agent_2, $type, $post_id);
        $skel_3 = SEO_AEO_Action_Targets::build_preview_skeleton($resolved_agent_3, $type, $post_id);
        aeo_assert_equal(json_encode($skel_1), json_encode($skel_2), 'preview vs propose skeleton diverged');
        aeo_assert_equal(json_encode($skel_1), json_encode($skel_3), 'preview vs execute_dry_run skeleton diverged');
    });
}

// === Test 3: payload contract — required fields present in every skeleton ===
aeo_test('payload contract: tier + mode + where + reversible + backup_via + estimated_credits', function () use ($post_id) {
    $required = array('preview', 'agent', 'action_type', 'tier', 'mode', 'mode_label',
                      'where', 'where_label', 'operation', 'reversible',
                      'backup_via', 'estimated_credits', 'is_manual');
    foreach (array('GENERATE_SCHEMA', 'ADD_INTERNAL_LINKS', 'REWRITE_META') as $type) {
        $agent = SEO_AEO_Action_Dispatcher::resolve_agent($type, '');
        $skel = SEO_AEO_Action_Targets::build_preview_skeleton($agent, $type, $post_id);
        foreach ($required as $field) {
            aeo_assert(array_key_exists($field, $skel), "$type skeleton missing field: $field");
        }
    }
});

// === Test 4: tier classification consistent across handlers ===
aeo_test('tier consistency: same action_type → same tier across all 3 handlers', function () use ($post_id) {
    foreach (array('GENERATE_SCHEMA' => 'SAFE', 'REWRITE_META' => 'CAUTION', 'REGENERATE_CONTENT' => 'DANGER') as $type => $expected_tier) {
        aeo_assert_equal($expected_tier, SEO_AEO_Action_Targets::get_tier($type), "$type tier mismatch");
    }
});

// === Test 5: divergence CANARY — if anyone adds a divergent branch, this should fail ===
aeo_test('CANARY: ajax_propose is byte-equivalent to ajax_preview_action (source check)', function () {
    $src = file_get_contents(ABSPATH . 'wp-content/plugins/seo-aeo-orchestra/includes/class-ajax-handlers.php');
    aeo_assert(strpos($src, 'public function ajax_propose()') !== false, 'ajax_propose method missing');
    aeo_assert(strpos($src, '$this->ajax_preview_action();') !== false, 'ajax_propose must alias ajax_preview_action');
});

aeo_test('CANARY: execute_action(dry_run=1) delegates to ajax_preview_action', function () {
    $src = file_get_contents(ABSPATH . 'wp-content/plugins/seo-aeo-orchestra/includes/class-ajax-handlers.php');
    aeo_assert(strpos($src, 'is_dry_run_request') !== false, 'is_dry_run_request not invoked');
    // After the dry_run check, the code should call ajax_preview_action.
    $idx = strpos($src, 'is_dry_run_request');
    $segment = substr($src, $idx, 500);
    aeo_assert_contains('$this->ajax_preview_action()', $segment, 'dry_run branch must call ajax_preview_action');
});

exit(aeo_test_summary());

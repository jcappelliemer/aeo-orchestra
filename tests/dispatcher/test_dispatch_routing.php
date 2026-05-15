<?php
/**
 * test_dispatch_routing.php — v3.42.0-rc2 M3
 *
 * Regression suite for the action_type → agent routing table.
 * Captures the architectural rule: every action_type MUST map to a
 * dedicated agent. NO content_generator fall-through (except via the
 * v3.41.8 dedicated flow with typed_confirm + request_origin).
 *
 * Covers:
 *   - P0 #2 v3.40.7 class: "tutte le azioni routate ad agent sbagliato"
 *   - v3.41.7 routing regression: 3 actions fall-through to content_generator
 *   - M2 v3.42.0-rc1: SEO_AEO_Action_Dispatcher::resolve_agent reverse lookup
 *
 * Run via:
 *   docker exec aeo-cms-wp wp eval-file /var/www/html/wp-content/plugins/seo-aeo-orchestra/tests/dispatcher/test_dispatch_routing.php --allow-root
 */

require_once(__DIR__ . '/test_harness.php');
require_once(ABSPATH . 'wp-content/plugins/seo-aeo-orchestra/includes/class-action-targets.php');
require_once(ABSPATH . 'wp-content/plugins/seo-aeo-orchestra/includes/class-action-dispatcher.php');

echo "═══ test_dispatch_routing — v3.42.0-rc2 M3\n\n";

// Expected agent map. Synced with Action_Targets::AGENT_TO_TYPE (reversed).
$expected = array(
    'GENERATE_SCHEMA'           => 'schema_generator',
    'ADD_FAQ_SECTION'           => 'faq_generator',
    'ADD_AUTHORITY_SIGNALS'     => 'authority_generator',
    'REWRITE_INTRO'             => 'intro_rewriter',
    'OPTIMIZE_FEATURED_SNIPPET' => 'snippet_optimizer',
    'OPTIMIZE_KEYWORDS'         => 'keyword_optimizer',
    'REWRITE_META'              => 'meta_optimizer',
    'ADD_INTERNAL_LINKS'        => 'internal_links_generator',
    'FIX_HEADING_STRUCTURE'     => 'heading_optimizer',
    'MANUAL_REVIEW'             => 'manual_review',
);

// === Test 1: every action_type resolves to its expected agent ===
foreach ($expected as $type => $agent) {
    aeo_test("resolve_agent('$type') === '$agent'", function () use ($type, $agent) {
        $resolved = SEO_AEO_Action_Dispatcher::resolve_agent($type, '');
        aeo_assert_equal($agent, $resolved);
    });
}

// === Test 2: anti-spoof — agent hint that DOESN'T match action_type is rejected ===
aeo_test("anti-spoof: agent_hint='content_generator' on action_type='ADD_INTERNAL_LINKS' → fallback to map", function () {
    $resolved = SEO_AEO_Action_Dispatcher::resolve_agent('ADD_INTERNAL_LINKS', 'content_generator');
    aeo_assert_equal('internal_links_generator', $resolved, 'spoofed hint must not poison resolution');
});

// === Test 3: anti-spoof — agent hint that DOES match is accepted ===
aeo_test("anti-spoof: matching hint accepted (perf shortcut)", function () {
    $resolved = SEO_AEO_Action_Dispatcher::resolve_agent('GENERATE_SCHEMA', 'schema_generator');
    aeo_assert_equal('schema_generator', $resolved);
});

// === Test 4: unknown action_type returns null (no silent fall-through) ===
aeo_test("unknown action_type 'NOT_A_REAL_TYPE' → null (no silent fall-through)", function () {
    $resolved = SEO_AEO_Action_Dispatcher::resolve_agent('NOT_A_REAL_TYPE', '');
    aeo_assert_null($resolved);
});

aeo_test("empty action_type → null", function () {
    $resolved = SEO_AEO_Action_Dispatcher::resolve_agent('', '');
    aeo_assert_null($resolved);
});

// === Test 5: REGRESSION TEST for v3.41.7 P0 — content_generator must NOT appear in AGENT_TO_TYPE ===
aeo_test("REGRESSION v3.41.7: content_generator NOT in AGENT_TO_TYPE map", function () {
    $map = SEO_AEO_Action_Targets::AGENT_TO_TYPE;
    aeo_assert(!array_key_exists('content_generator', $map),
        'content_generator MUST NOT appear in the canonical routing map; it is the DANGER catch-all that only the dedicated flow uses');
});

// === Test 6: REGRESSION TEST for v3.40.7 P0#2 — action_type list is locked-down ===
aeo_test("REGRESSION v3.40.7 P0#2: AGENT_TO_TYPE has exactly the canonical agents", function () {
    $expected_agents = array(
        'schema_generator',
        'faq_generator',
        'authority_generator',
        'intro_rewriter',
        'snippet_optimizer',
        'keyword_optimizer',
        'meta_optimizer',
        'internal_links_generator',  // v3.41.7
        'heading_optimizer',          // v3.41.7
        'manual_review',              // v3.41.7
    );
    $map = SEO_AEO_Action_Targets::AGENT_TO_TYPE;
    foreach ($expected_agents as $agent) {
        aeo_assert(array_key_exists($agent, $map), "agent '$agent' missing from AGENT_TO_TYPE — has routing been re-broken?");
    }
});

// === Test 7: tier classification regression — DANGER actions stay locked ===
aeo_test("DANGER tier locks: REGENERATE_CONTENT, EXPAND_CONTENT", function () {
    aeo_assert_equal('DANGER', SEO_AEO_Action_Targets::get_tier('REGENERATE_CONTENT'));
    aeo_assert_equal('DANGER', SEO_AEO_Action_Targets::get_tier('EXPAND_CONTENT'));
});

exit(aeo_test_summary());

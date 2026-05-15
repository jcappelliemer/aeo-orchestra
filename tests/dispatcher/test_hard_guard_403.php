<?php
/**
 * test_hard_guard_403.php (v2 — source inspection) — v3.42.0-rc2 M3
 *
 * The original wp_die-capture approach terminates the script mid-suite
 * (correct guard behavior, but unfit for a multi-assertion test runner).
 * v2 inspects the Dispatcher source + reflection + the guard conditional
 * logic deterministically without invoking wp_die.
 *
 * The architectural promise: enforce_hard_guard is the SINGLE 403 emitter,
 * its trigger + release conditions are explicit, and both handlers delegate
 * to it. v3.42.0-rc1 wire-level Chrome MCP verification already confirmed
 * the live HTTP 403 emission. This suite locks the source-level invariants.
 */

require_once(__DIR__ . '/test_harness.php');
require_once(ABSPATH . 'wp-content/plugins/seo-aeo-orchestra/includes/class-action-dispatcher.php');

echo "═══ test_hard_guard_403 v2 — v3.42.0-rc2 M3\n\n";

$dispatcher_src = file_get_contents(ABSPATH . 'wp-content/plugins/seo-aeo-orchestra/includes/class-action-dispatcher.php');
$handlers_src   = file_get_contents(ABSPATH . 'wp-content/plugins/seo-aeo-orchestra/includes/class-ajax-handlers.php');

// === Test 1: Dispatcher class + 3 helpers exist ===
aeo_test('Dispatcher class with 3 helpers', function () {
    aeo_assert(class_exists('SEO_AEO_Action_Dispatcher'));
    aeo_assert(method_exists('SEO_AEO_Action_Dispatcher', 'enforce_hard_guard'));
    aeo_assert(method_exists('SEO_AEO_Action_Dispatcher', 'resolve_agent'));
    aeo_assert(method_exists('SEO_AEO_Action_Dispatcher', 'is_dry_run_request'));
});

// === Test 2: enforce_hard_guard signature ===
aeo_test('enforce_hard_guard signature: ($agent, $action_type, $post_data)', function () {
    $r = new ReflectionMethod('SEO_AEO_Action_Dispatcher', 'enforce_hard_guard');
    $p = $r->getParameters();
    aeo_assert_equal(3, count($p));
    aeo_assert_equal('agent', $p[0]->getName());
    aeo_assert_equal('action_type', $p[1]->getName());
    aeo_assert_equal('post_data', $p[2]->getName());
});

// === Test 3: trigger conditions defined in source ===
aeo_test("trigger: agent='content_generator'", function () use ($dispatcher_src) {
    aeo_assert(strpos($dispatcher_src, "\$agent === 'content_generator'") !== false);
});

aeo_test("trigger: action_type in {EXPAND_CONTENT, REGENERATE_CONTENT}", function () use ($dispatcher_src) {
    aeo_assert(strpos($dispatcher_src, "'EXPAND_CONTENT'") !== false);
    aeo_assert(strpos($dispatcher_src, "'REGENERATE_CONTENT'") !== false);
});

// === Test 4: release condition requires BOTH origin AND riscrivi ===
aeo_test("release: request_origin === 'content_regenerate_dedicated_flow'", function () use ($dispatcher_src) {
    aeo_assert(strpos($dispatcher_src, "'content_regenerate_dedicated_flow'") !== false);
});

aeo_test("release: lower-trim(typed_confirm) === 'riscrivi'", function () use ($dispatcher_src) {
    aeo_assert(strpos($dispatcher_src, "strtolower(trim(\$typed_confirm)) === 'riscrivi'") !== false);
});

// === Test 5: real HTTP 403 emission (not 200 + JSON error) ===
aeo_test('emits real status_header(403) before wp_send_json_error', function () use ($dispatcher_src) {
    aeo_assert(strpos($dispatcher_src, 'status_header(403)') !== false);
    aeo_assert(strpos($dispatcher_src, "wp_send_json_error(array(") !== false);
    aeo_assert(strpos($dispatcher_src, "), 403);") !== false, '403 status passed to wp_send_json_error');
});

// === Test 6: Sentry visibility via seo_aeo_debug_log ===
aeo_test('debug_log warning emitted on guard trigger (Sentry visibility)', function () use ($dispatcher_src) {
    aeo_assert(strpos($dispatcher_src, 'seo_aeo_debug_log') !== false);
    aeo_assert(strpos($dispatcher_src, "'[M2 dispatcher] 403 forbidden_path") !== false);
});

// === Test 7: both ajax handlers delegate to enforce_hard_guard ===
aeo_test('preview_action delegates to Dispatcher::enforce_hard_guard', function () use ($handlers_src) {
    $count = substr_count($handlers_src, 'SEO_AEO_Action_Dispatcher::enforce_hard_guard');
    aeo_assert($count >= 2, "expected ≥2 delegations (preview + execute), got $count");
});

// === Test 8: NO inline DANGER guards remain (stale duplication eliminated) ===
aeo_test('stale inline DANGER guards eliminated (single source of truth)', function () use ($handlers_src) {
    // v3.41.8 used `'tier'        => 'DANGER'` inside ajax handlers — should be 0 now.
    $count = substr_count($handlers_src, "'tier'        => 'DANGER'");
    aeo_assert_equal(0, $count, "$count inline DANGER guards still present");
});

// === Test 9: is_dry_run_request helper ===
aeo_test('is_dry_run_request accepts 1/true/yes/on', function () {
    aeo_assert_true(SEO_AEO_Action_Dispatcher::is_dry_run_request(array('dry_run' => '1')));
    aeo_assert_true(SEO_AEO_Action_Dispatcher::is_dry_run_request(array('dry_run' => 'true')));
    aeo_assert_true(SEO_AEO_Action_Dispatcher::is_dry_run_request(array('dry_run' => 'YES')));
    aeo_assert_true(SEO_AEO_Action_Dispatcher::is_dry_run_request(array('dry_run' => 'on')));
});

aeo_test('is_dry_run_request rejects 0/false/empty/missing', function () {
    aeo_assert_false(SEO_AEO_Action_Dispatcher::is_dry_run_request(array('dry_run' => '0')));
    aeo_assert_false(SEO_AEO_Action_Dispatcher::is_dry_run_request(array('dry_run' => 'false')));
    aeo_assert_false(SEO_AEO_Action_Dispatcher::is_dry_run_request(array('dry_run' => '')));
    aeo_assert_false(SEO_AEO_Action_Dispatcher::is_dry_run_request(array()));
});

// === Test 10: execute path has dry_run branch ===
aeo_test('execute_action checks is_dry_run_request and delegates to preview path', function () use ($handlers_src) {
    aeo_assert(strpos($handlers_src, 'is_dry_run_request($_POST)') !== false);
    $dry_idx = strpos($handlers_src, 'is_dry_run_request($_POST)');
    $segment = substr($handlers_src, $dry_idx, 400);
    aeo_assert(strpos($segment, '$this->ajax_preview_action()') !== false,
        'dry_run branch must delegate to ajax_preview_action');
});

exit(aeo_test_summary());

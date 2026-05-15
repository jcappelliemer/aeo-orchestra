<?php
/**
 * Minimal test harness for the dispatcher PHPUnit-shaped suite (v3.42.0-rc2 M3).
 *
 * Not PHPUnit — PHPUnit isn't installed in the runtime container. The suite
 * is designed to be promotable to PHPUnit later (one-to-one assert mapping).
 * For now: standalone PHP scripts you can run via `php test_X.php` or via
 * `wp eval-file test_X.php` in the WordPress context.
 *
 * Usage:
 *   require_once 'test_harness.php';
 *   aeo_test('description of test', function () {
 *     aeo_assert_equal($expected, $actual, 'optional message');
 *   });
 *   aeo_test_summary();
 */

if (!defined('AEO_TEST_RESULTS')) {
    define('AEO_TEST_RESULTS', '__aeo_test_results');
    $GLOBALS[AEO_TEST_RESULTS] = array('pass' => 0, 'fail' => 0, 'errors' => array());
}

function aeo_test($name, callable $body) {
    try {
        $body();
        $GLOBALS[AEO_TEST_RESULTS]['pass']++;
        echo "  ✓ $name\n";
    } catch (Throwable $e) {
        $GLOBALS[AEO_TEST_RESULTS]['fail']++;
        $GLOBALS[AEO_TEST_RESULTS]['errors'][] = "$name → " . $e->getMessage();
        echo "  ✗ $name\n      " . $e->getMessage() . "\n";
    }
}

function aeo_assert($cond, $msg = '') {
    if (!$cond) throw new RuntimeException($msg !== '' ? $msg : 'assertion failed');
}

function aeo_assert_equal($expected, $actual, $msg = '') {
    if ($expected !== $actual) {
        $e = is_scalar($expected) ? var_export($expected, true) : json_encode($expected);
        $a = is_scalar($actual)   ? var_export($actual, true)   : json_encode($actual);
        throw new RuntimeException(($msg !== '' ? "$msg: " : '') . "expected $e, got $a");
    }
}

function aeo_assert_null($v, $msg = '') {
    if ($v !== null) {
        throw new RuntimeException(($msg !== '' ? "$msg: " : '') . "expected null, got " . var_export($v, true));
    }
}

function aeo_assert_true($v, $msg = '')  { aeo_assert($v === true, $msg); }
function aeo_assert_false($v, $msg = '') { aeo_assert($v === false, $msg); }
function aeo_assert_contains($needle, $haystack, $msg = '') {
    if (strpos((string) $haystack, (string) $needle) === false) {
        throw new RuntimeException(($msg !== '' ? "$msg: " : '') . "'$needle' not found in haystack");
    }
}

function aeo_test_summary() {
    $r = $GLOBALS[AEO_TEST_RESULTS];
    echo "\n═══ " . ($r['pass'] + $r['fail']) . " tests · ✓ " . $r['pass'] . " pass · ✗ " . $r['fail'] . " fail ═══\n";
    if ($r['fail']) {
        foreach ($r['errors'] as $e) echo "  ✗ $e\n";
        return 1;
    }
    return 0;
}

<?php
/**
 * test_surgical_editor_dry_run.php — v3.42.0-rc2 M3
 *
 * Verifies the M1 (v3.41.9) primitive: every surgical-editor's apply() has
 * a `$dry_run = false` 3rd param, dry_run=true returns preview shape WITHOUT
 * writing to the DB, default 2-arg call preserves backward-compat.
 *
 * Covers all 11 surgical hooks:
 *   Classic, Gutenberg, Elementor, Divi, WPBakery, Beaver, Bricks, Oxygen,
 *   Headless_REST, Headless_WPGraphQL, Surgical_Editor_Common::str_replace_post_content
 *
 * Run via:
 *   docker exec aeo-cms-wp wp eval-file .../tests/dispatcher/test_surgical_editor_dry_run.php --allow-root
 */

require_once(__DIR__ . '/test_harness.php');
require_once(ABSPATH . 'wp-content/plugins/seo-aeo-orchestra/includes/class-surgical-editor.php');

echo "═══ test_surgical_editor_dry_run — v3.42.0-rc2 M3\n\n";

$editors = array(
    'SEO_AEO_Classic_Surgical_Editor',
    'SEO_AEO_Gutenberg_Surgical_Editor',
    'SEO_AEO_Elementor_Surgical_Editor',
    'SEO_AEO_Divi_Surgical_Editor',
    'SEO_AEO_WPBakery_Surgical_Editor',
    'SEO_AEO_Beaver_Surgical_Editor',
    'SEO_AEO_Bricks_Surgical_Editor',
    'SEO_AEO_Oxygen_Surgical_Editor',
    'SEO_AEO_Headless_REST_Surgical_Editor',
    'SEO_AEO_Headless_WPGraphQL_Surgical_Editor',
);

// === Test 1: signature contract — apply($post_id, $edits, $dry_run = false) ===
foreach ($editors as $cls) {
    aeo_test("$cls::apply has 3rd param \$dry_run = false", function () use ($cls) {
        aeo_assert(class_exists($cls), "class missing: $cls");
        $r = new ReflectionMethod($cls, 'apply');
        $params = $r->getParameters();
        aeo_assert(count($params) >= 3, "$cls::apply expected ≥3 params, got " . count($params));
        aeo_assert_equal('dry_run', $params[2]->getName());
        aeo_assert($params[2]->isDefaultValueAvailable(), 'dry_run must have default');
        aeo_assert_equal(false, $params[2]->getDefaultValue());
    });
}

// === Test 2: Surgical_Editor_Common helper has dry_run param ===
aeo_test("SEO_AEO_Surgical_Editor_Common::str_replace_post_content has \$dry_run param", function () {
    aeo_assert(class_exists('SEO_AEO_Surgical_Editor_Common'));
    $r = new ReflectionMethod('SEO_AEO_Surgical_Editor_Common', 'str_replace_post_content');
    $params = $r->getParameters();
    aeo_assert(count($params) >= 4, 'expected ≥4 params');
    aeo_assert_equal('dry_run', $params[3]->getName());
});

// === Test 3: live dry_run on Headless REST (Classic delegate) — no DB write ===
$post_id = 69;
$post = get_post($post_id);
if (!$post) {
    aeo_test('live dry_run no-write proof', function () { throw new RuntimeException('page 69 not available'); });
} else {
    $pre_modified = $post->post_modified_gmt;
    $pre_content  = $post->post_content;
    $pre_len      = strlen($pre_content);

    $snippet = mb_substr(wp_strip_all_tags($pre_content), 0, 50);
    $edits = array(array(
        'old_text' => $snippet,
        'new_text' => '[DRY_RUN_M3_MARKER_DO_NOT_PERSIST]',
        'tag_type' => '',
    ));

    aeo_test('Headless_REST::apply(dry_run=true) returns preview shape with current+proposed', function () use ($post_id, $edits) {
        $r = SEO_AEO_Headless_REST_Surgical_Editor::apply($post_id, $edits, true);
        aeo_assert_true(!empty($r['dry_run']), 'dry_run flag must be true');
        aeo_assert_true(!empty($r['preview']), 'preview flag must be true');
        aeo_assert(isset($r['current']['post_content']), 'current.post_content missing');
        aeo_assert(isset($r['proposed']['post_content']), 'proposed.post_content missing');
        aeo_assert_contains('[DRY_RUN_M3_MARKER_DO_NOT_PERSIST]', $r['proposed']['post_content'], 'proposed must contain the would-be replacement');
    });

    aeo_test('dry_run did NOT mutate post_modified_gmt', function () use ($post_id, $pre_modified) {
        clean_post_cache($post_id);
        $p = get_post($post_id);
        aeo_assert_equal($pre_modified, $p->post_modified_gmt);
    });

    aeo_test('dry_run did NOT mutate post_content (length identical)', function () use ($post_id, $pre_len) {
        clean_post_cache($post_id);
        $p = get_post($post_id);
        aeo_assert_equal($pre_len, strlen($p->post_content));
    });
}

// === Test 4: backward compat — 2-arg call defaults dry_run=false ===
aeo_test('backward compat: Classic::apply 2-arg call defaults dry_run=false', function () {
    $r = new ReflectionMethod('SEO_AEO_Classic_Surgical_Editor', 'apply');
    aeo_assert_equal(false, $r->getParameters()[2]->getDefaultValue());
});

exit(aeo_test_summary());

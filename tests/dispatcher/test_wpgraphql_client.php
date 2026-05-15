<?php
/**
 * test_wpgraphql_client.php — v3.42.0 M4
 *
 * PHPUnit-shaped mock test for SEO_AEO_WPGraphQL_Client. Exercises the
 * client logic against a stub HTTP layer (no real network calls).
 *
 * What this proves:
 *   1. Client correctly serializes the updatePost mutation body
 *   2. JWT bearer token is set on the Authorization header
 *   3. Post ID is converted to WPGraphQL's Global Relay ID format
 *   4. Successful response is parsed and `success:true` returned
 *   5. GraphQL errors path: `success:false` + errors array surfaced
 *   6. Transport errors path: `success:false` + transport error surfaced
 *   7. Unconfigured client refuses to call (no endpoint or no token)
 *
 * TODO real-staging-test v3.42.1: replace the mock http_client with real
 * wp_remote_post against a WPGraphQL + JWT test install.
 */

require_once(__DIR__ . '/test_harness.php');
require_once(ABSPATH . 'wp-content/plugins/seo-aeo-orchestra/includes/helper-wpgraphql-client.php');

echo "═══ test_wpgraphql_client — v3.42.0 M4\n\n";

// === Test 1: unconfigured client refuses ===
aeo_test('unconfigured client returns success:false without HTTP call', function () {
    $client = new SEO_AEO_WPGraphQL_Client('', '', null);
    aeo_assert_false($client->is_configured());
    $r = $client->update_post_content(69, '<p>new content</p>');
    aeo_assert_false($r['success']);
    aeo_assert_contains('not configured', $r['errors'][0]['message']);
});

// === Test 2: mutation body shape ===
aeo_test('build_update_post_mutation: Relay ID + content + clientMutationId', function () {
    $client = new SEO_AEO_WPGraphQL_Client('https://x.test/graphql', 'TOKEN', null);
    $body = $client->build_update_post_mutation(69, '<p>hello</p>');
    aeo_assert(isset($body['query']), 'mutation query missing');
    aeo_assert_contains('updatePost', $body['query']);
    aeo_assert_contains('UpdatePostInput', $body['query']);
    aeo_assert_equal(base64_encode('post:69'), $body['variables']['input']['id'], 'must use WPGraphQL Global Relay ID');
    aeo_assert_equal('<p>hello</p>', $body['variables']['input']['content']);
    aeo_assert_contains('aeo-orchestra-69-', $body['variables']['input']['clientMutationId']);
});

// === Test 3: successful mutation round-trip ===
aeo_test('success path: mock returns data → success:true', function () {
    $captured_args = array();
    $mock_http = function ($url, $args) use (&$captured_args) {
        $captured_args[] = array('url' => $url, 'args' => $args);
        return array(
            'response' => array('code' => 200),
            'body' => json_encode(array(
                'data' => array(
                    'updatePost' => array(
                        'post' => array('id' => base64_encode('post:69'), 'modified' => '2026-05-15T20:00:00', 'modifiedGmt' => '2026-05-15T18:00:00'),
                        'clientMutationId' => 'aeo-orchestra-69-test',
                    ),
                ),
            )),
        );
    };
    $client = new SEO_AEO_WPGraphQL_Client('https://x.test/graphql', 'TEST_JWT', $mock_http);
    $r = $client->update_post_content(69, '<p>updated</p>');
    aeo_assert_true($r['success']);
    aeo_assert_equal(200, $r['status_code']);
    aeo_assert(isset($r['data']['updatePost']['post']['id']));

    // Verify HTTP request was well-formed.
    aeo_assert_equal('https://x.test/graphql', $captured_args[0]['url']);
    aeo_assert_equal('Bearer TEST_JWT', $captured_args[0]['args']['headers']['Authorization']);
    aeo_assert_equal('application/json', $captured_args[0]['args']['headers']['Content-Type']);
    aeo_assert_equal('POST', $captured_args[0]['args']['method']);
    $sent_body = json_decode($captured_args[0]['args']['body'], true);
    aeo_assert_equal('<p>updated</p>', $sent_body['variables']['input']['content']);
});

// === Test 4: GraphQL errors path ===
aeo_test('GraphQL errors path: success:false + errors surfaced', function () {
    $mock_http = function ($url, $args) {
        return array(
            'response' => array('code' => 200),
            'body' => json_encode(array(
                'data' => null,
                'errors' => array(
                    array('message' => 'Post not found', 'extensions' => array('category' => 'user')),
                ),
            )),
        );
    };
    $client = new SEO_AEO_WPGraphQL_Client('https://x.test/graphql', 'TOKEN', $mock_http);
    $r = $client->update_post_content(999, '<p>x</p>');
    aeo_assert_false($r['success']);
    aeo_assert_equal('Post not found', $r['errors'][0]['message']);
});

// === Test 5: HTTP 401 unauthorized ===
aeo_test('HTTP 401 unauthorized → success:false', function () {
    $mock_http = function ($url, $args) {
        return array(
            'response' => array('code' => 401),
            'body' => '{"errors":[{"message":"invalid token"}]}',
        );
    };
    $client = new SEO_AEO_WPGraphQL_Client('https://x.test/graphql', 'BAD_TOKEN', $mock_http);
    $r = $client->update_post_content(69, '<p>x</p>');
    aeo_assert_false($r['success']);
    aeo_assert_equal(401, $r['status_code']);
});

// === Test 6: transport error (WP_Error) ===
aeo_test('transport error: success:false + transport error message', function () {
    $mock_http = function ($url, $args) {
        return new WP_Error('http_request_failed', 'cURL: timeout 30s');
    };
    $client = new SEO_AEO_WPGraphQL_Client('https://x.test/graphql', 'TOKEN', $mock_http);
    $r = $client->update_post_content(69, '<p>x</p>');
    aeo_assert_false($r['success']);
    aeo_assert_contains('Transport error', $r['errors'][0]['message']);
});

// === Test 7: configured-state check ===
aeo_test('is_configured() requires BOTH endpoint AND token', function () {
    aeo_assert_false((new SEO_AEO_WPGraphQL_Client('', '', null))->is_configured());
    aeo_assert_false((new SEO_AEO_WPGraphQL_Client('https://x.test', '', null))->is_configured());
    aeo_assert_false((new SEO_AEO_WPGraphQL_Client('', 'TOKEN', null))->is_configured());
    aeo_assert_true((new SEO_AEO_WPGraphQL_Client('https://x.test', 'TOKEN', null))->is_configured());
});

exit(aeo_test_summary());

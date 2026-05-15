<?php
/**
 * SEO_AEO_WPGraphQL_Client — Mutation client + JWT auth (v3.42.0 M4).
 *
 * Targets WordPress sites that use WPGraphQL + JWT Authentication plugins
 * as their headless write path. Pre-M4: the Headless WPGraphQL surgical
 * editor delegated to Classic (best-effort, frontend cache reliance).
 * M4 introduces a real mutation client that issues an updatePost mutation
 * against the GraphQL endpoint, authenticated via JWT.
 *
 * Coverage target: 50-70% → 90%+ for headless WPGraphQL sites.
 *
 * Configuration (per-site, stored in wp_options):
 *   aeo_wpgraphql_endpoint  — e.g. "https://example.com/graphql"
 *   aeo_wpgraphql_jwt_token — bearer token obtained from the JWT login mutation
 *
 * Usage (called by Headless_WPGraphQL_Surgical_Editor::apply when dry_run=false):
 *   $client = new SEO_AEO_WPGraphQL_Client();
 *   $result = $client->update_post_content($post_id, $new_content);
 *   // Returns { success, status_code, data, errors }
 *
 * Real-staging integration test deferred to v3.42.1 — this file ships with
 * a PHPUnit mock test that exercises the client logic against a stub HTTP
 * layer. The mock proves the JWT header is set, the mutation body is
 * well-formed, and the error path correctly surfaces GraphQL errors.
 *
 * TODO real-staging-test v3.42.1: deploy a fresh WP install with WPGraphQL
 * + JWT Authentication plugins, exchange admin creds for a JWT token,
 * exercise this client against the real /graphql endpoint, assert post
 * content updates round-trip cleanly.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SEO_AEO_WPGraphQL_Client {

    const OPTION_ENDPOINT  = 'aeo_wpgraphql_endpoint';
    const OPTION_JWT_TOKEN = 'aeo_wpgraphql_jwt_token';

    private $endpoint;
    private $jwt_token;
    private $http_client; // injection point for mock testing

    public function __construct($endpoint = null, $jwt_token = null, $http_client = null) {
        $this->endpoint  = $endpoint !== null ? $endpoint : (string) get_option(self::OPTION_ENDPOINT, '');
        $this->jwt_token = $jwt_token !== null ? $jwt_token : (string) get_option(self::OPTION_JWT_TOKEN, '');
        $this->http_client = $http_client; // null in production → use wp_remote_post
    }

    public function is_configured() {
        return $this->endpoint !== '' && $this->jwt_token !== '';
    }

    /**
     * Build the updatePost mutation body. WPGraphQL's standard mutation
     * convention: input object with `id` (Global Relay ID), `content`,
     * `clientMutationId`.
     *
     * Note: WPGraphQL uses Global Relay IDs (base64-encoded "post:123"),
     * not raw integer IDs. We compute the Relay ID via the same encoding
     * WPGraphQL uses internally.
     */
    public function build_update_post_mutation($post_id, $new_content) {
        $relay_id = base64_encode('post:' . (int) $post_id);
        return array(
            'query' => '
                mutation UpdatePostContent($input: UpdatePostInput!) {
                    updatePost(input: $input) {
                        post {
                            id
                            modified
                            modifiedGmt
                        }
                        clientMutationId
                    }
                }
            ',
            'variables' => array(
                'input' => array(
                    'id'               => $relay_id,
                    'content'          => $new_content,
                    'clientMutationId' => 'aeo-orchestra-' . (int) $post_id . '-' . time(),
                ),
            ),
        );
    }

    /**
     * Issue the updatePost mutation. Returns:
     *   {
     *     success: bool,
     *     status_code: int,
     *     data: array|null,       // mutation result on success
     *     errors: array,          // GraphQL errors + transport errors
     *   }
     */
    public function update_post_content($post_id, $new_content) {
        if (!$this->is_configured()) {
            return array(
                'success'     => false,
                'status_code' => 0,
                'data'        => null,
                'errors'      => array(array('message' => 'WPGraphQL client not configured (missing endpoint or JWT token)')),
            );
        }

        $body = $this->build_update_post_mutation($post_id, $new_content);
        $headers = array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->jwt_token,
        );
        $args = array(
            'method'  => 'POST',
            'headers' => $headers,
            'body'    => wp_json_encode($body),
            'timeout' => 30,
        );

        // Mock injection point for testing.
        if ($this->http_client !== null && is_callable($this->http_client)) {
            $resp = call_user_func($this->http_client, $this->endpoint, $args);
        } else {
            $resp = wp_remote_post($this->endpoint, $args);
        }

        if (is_wp_error($resp)) {
            return array(
                'success'     => false,
                'status_code' => 0,
                'data'        => null,
                'errors'      => array(array('message' => 'Transport error: ' . $resp->get_error_message())),
            );
        }

        $status_code = isset($resp['response']['code']) ? (int) $resp['response']['code'] : 0;
        $raw_body    = isset($resp['body']) ? (string) $resp['body'] : '';
        $parsed      = json_decode($raw_body, true);

        if ($status_code !== 200 || !is_array($parsed)) {
            return array(
                'success'     => false,
                'status_code' => $status_code,
                'data'        => null,
                'errors'      => array(array('message' => 'HTTP ' . $status_code . ' or non-JSON body')),
            );
        }
        if (!empty($parsed['errors'])) {
            return array(
                'success'     => false,
                'status_code' => $status_code,
                'data'        => isset($parsed['data']) ? $parsed['data'] : null,
                'errors'      => $parsed['errors'],
            );
        }
        return array(
            'success'     => true,
            'status_code' => $status_code,
            'data'        => isset($parsed['data']) ? $parsed['data'] : null,
            'errors'      => array(),
        );
    }
}

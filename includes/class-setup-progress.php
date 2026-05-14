<?php
/**
 * 3.38.0 — Onboarding 3.0 — Setup Guidato state machine.
 *
 * 7-step guided setup. State lives in WP option `seo_aeo_setup_progress`
 * (per site, JSON value). Resume anytime. Persona branching at Step 1.
 *
 * Step IDs (canonical, stable):
 *   perimeter           STEP 1 — Definisci il tuo perimetro       (free)
 *   business_profile    STEP 2 — Profilo Business                  (free)
 *   keyword_research    STEP 3 — Keyword Research                  (15 cr)
 *   brand_voice         STEP 4 — Brand Voice                       (25 cr)
 *   orchestrator        STEP 5 — Analizza il sito (free 1st page)  (5 cr/page)
 *   native_output       STEP 6 — SEO + AEO Output stack            (free)
 *   auto_pilot          STEP 7 — Auto-Pilot scheduler              (free setup)
 *
 * Status values per step: done | todo | skipped
 *
 * Auto-detection: on first access we sync the option from the actual plugin
 * state — many users have ALREADY done some steps before Onboarding 3.0
 * existed (e.g. Business Profile filled), so we should not show them as TODO.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Setup_Progress {

    const OPTION_KEY     = 'seo_aeo_setup_progress';
    const SCHEMA_VERSION = '3.0';

    /**
     * Canonical step order. Frontend renders in this order.
     */
    const STEPS = array(
        'perimeter',
        'business_profile',
        'keyword_research',
        'brand_voice',
        'orchestrator',
        // 3.40.14 P1.4 - Compatibilita Sito step. Mark done when the
        // SiteScanner profile (aeo_site_profile option) exists. The user
        // edits override + re-scans from Impostazioni -> Compatibilita Sito.
        'site_compat',
        'native_output',
        'auto_pilot',
    );

    public function __construct() {
        add_action('wp_ajax_seo_aeo_setup_get_state',       array($this, 'ajax_get_state'));
        add_action('wp_ajax_seo_aeo_setup_update_step',     array($this, 'ajax_update_step'));
        add_action('wp_ajax_seo_aeo_setup_set_persona',     array($this, 'ajax_set_persona'));
        add_action('wp_ajax_seo_aeo_setup_dismiss_widget',  array($this, 'ajax_dismiss_widget'));
        add_action('wp_ajax_seo_aeo_setup_sync',            array($this, 'ajax_sync_state'));
    }

    // ─────────────────────────────────────────────────────────────────────
    // State read/write
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Return the full state structure. Always returns a well-formed array
     * (initializes from default skeleton if option is missing/empty).
     */
    public static function get_state() {
        $raw = get_option(self::OPTION_KEY, '');
        $state = null;
        if (!empty($raw) && is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $state = $decoded;
            }
        } elseif (is_array($raw)) {
            $state = $raw;
        }
        if (!$state) {
            $state = self::default_state();
        }
        // Ensure schema version + steps array are well-formed.
        $state = self::normalize($state);
        return $state;
    }

    public static function save_state($state) {
        $state['last_active_at'] = gmdate('c');
        $payload = wp_json_encode(self::normalize($state), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($payload)) return false;
        update_option(self::OPTION_KEY, $payload, false);
        return true;
    }

    public static function default_state() {
        $now = gmdate('c');
        $steps = array();
        foreach (self::STEPS as $sid) {
            $steps[$sid] = array('status' => 'todo');
        }
        return array(
            'version'                          => self::SCHEMA_VERSION,
            'persona_type'                     => null,  // consultant | wp_owner | exploring | null
            'started_at'                       => $now,
            'last_active_at'                   => $now,
            'steps'                            => $steps,
            'widget_dismissed_until_step_done' => false,
            'ambassador_banner_dismissed'      => false,
        );
    }

    /**
     * Coerce arbitrary input into a valid state structure. Defensive against
     * older schema versions or corrupted JSON.
     */
    public static function normalize($state) {
        if (!is_array($state)) $state = array();
        if (empty($state['version'])) $state['version'] = self::SCHEMA_VERSION;
        if (!isset($state['steps']) || !is_array($state['steps'])) $state['steps'] = array();
        foreach (self::STEPS as $sid) {
            if (empty($state['steps'][$sid]) || !is_array($state['steps'][$sid])) {
                $state['steps'][$sid] = array('status' => 'todo');
            }
            $status = isset($state['steps'][$sid]['status']) ? $state['steps'][$sid]['status'] : 'todo';
            if (!in_array($status, array('done', 'todo', 'skipped'), true)) {
                $state['steps'][$sid]['status'] = 'todo';
            }
        }
        if (empty($state['persona_type']) || !in_array($state['persona_type'], array('consultant', 'wp_owner', 'exploring'), true)) {
            $state['persona_type'] = isset($state['persona_type']) ? $state['persona_type'] : null;
        }
        if (empty($state['started_at']))     $state['started_at'] = gmdate('c');
        if (empty($state['last_active_at'])) $state['last_active_at'] = gmdate('c');
        return $state;
    }

    public static function done_count() {
        $state = self::get_state();
        $count = 0;
        foreach (self::STEPS as $sid) {
            if (isset($state['steps'][$sid]['status']) && $state['steps'][$sid]['status'] === 'done') {
                $count++;
            }
        }
        return $count;
    }

    public static function all_done() {
        return self::done_count() >= count(self::STEPS);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Auto-detection (sync from actual plugin state)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Inspect the actual plugin state and flip step statuses to 'done' if
     * the underlying data exists. Idempotent. Called from the Setup Guidato
     * page render and via ajax_sync_state. Never flips done → todo (only
     * the user can do that via a manual reset).
     */
    public static function sync_from_plugin_state() {
        $state = self::get_state();
        $changed = false;

        foreach (self::STEPS as $sid) {
            // Don't downgrade an already-done step.
            if (($state['steps'][$sid]['status'] ?? 'todo') === 'done') continue;
            // Skip user-explicit skipped status — they chose to skip.
            if (($state['steps'][$sid]['status'] ?? 'todo') === 'skipped') continue;

            if (self::check_step_done($sid)) {
                $state['steps'][$sid]['status']       = 'done';
                $state['steps'][$sid]['completed_at'] = gmdate('c');
                $state['steps'][$sid]['auto_detected'] = true;
                $changed = true;
            }
        }

        if ($changed) self::save_state($state);
        return $state;
    }

    /**
     * Per-step heuristic for auto-detection.
     *
     * Each check below errs on the side of FALSE — better to show "TODO" for
     * an incomplete step than to mark a step "done" when the user really
     * hasn't engaged with it. So thresholds are generous (e.g. business
     * profile description must be > 100 chars, not just non-empty).
     */
    public static function check_step_done($step) {
        switch ($step) {
            case 'perimeter':
                $state = self::get_state();
                $perim = $state['steps']['perimeter']['data'] ?? array();
                return is_array($perim) && !empty($perim['industry']) && !empty($perim['site_type']);

            case 'business_profile':
                $profile = get_option('seo_aeo_business_profile_data', array());
                if (!is_array($profile)) $profile = array();
                $desc = isset($profile['description']) ? trim((string) $profile['description']) : '';
                return strlen($desc) >= 100;

            case 'keyword_research':
                return self::history_has('keyword_research') || self::history_has('keyword-research');

            case 'brand_voice':
                $profiles = get_option('seo_aeo_brand_voice_profiles', array());
                return is_array($profiles) && count($profiles) > 0;

            case 'orchestrator':
                return self::history_has('orchestrator') || self::history_has('analysis');

            case 'site_compat':
                // 3.40.14 - done when the SiteScanner has run at least once.
                // aeo_site_profile is written by SEO_AEO_Site_Scanner::scan_full,
                // which runs on plugin activation (v3.40.2) and on every
                // Re-scansiona click. Existing installations may not have it
                // until the first manual rescan, in which case the step shows
                // as TODO with the "Apri Impostazioni" button.
                $profile = get_option('aeo_site_profile', null);
                return is_array($profile) && !empty($profile['builder']);

            case 'native_output':
                return (bool) get_option('seo_aeo_native_output_enabled', false);

            case 'auto_pilot':
                $auto_cfg = get_option('seo_aeo_autopilot_jobs', array());
                return is_array($auto_cfg) && count($auto_cfg) > 0;
        }
        return false;
    }

    /**
     * Helper: does the orchestrator history option contain at least one
     * entry whose `section` matches one of the candidate names?
     */
    private static function history_has($section_or_type) {
        $raw = get_option('seo_aeo_orchestra_history_json', '');
        if (empty($raw) || !is_string($raw)) return false;
        $items = json_decode($raw, true);
        if (!is_array($items)) return false;
        foreach ($items as $it) {
            $sec  = isset($it['section']) ? (string) $it['section'] : '';
            $type = isset($it['type']) ? (string) $it['type'] : '';
            if ($sec === $section_or_type || $type === $section_or_type) return true;
        }
        return false;
    }

    // ─────────────────────────────────────────────────────────────────────
    // AJAX handlers
    // ─────────────────────────────────────────────────────────────────────

    public function ajax_get_state() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'forbidden'), 403);
        }
        $state = self::sync_from_plugin_state();
        wp_send_json_success($state);
    }

    public function ajax_update_step() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'forbidden'), 403);
        }
        $step_id = isset($_POST['step_id']) ? sanitize_key(wp_unslash($_POST['step_id'])) : '';
        $status  = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : '';
        if (!in_array($step_id, self::STEPS, true)) {
            wp_send_json_error(array('message' => 'unknown step_id'), 400);
        }
        if (!in_array($status, array('done', 'todo', 'skipped'), true)) {
            wp_send_json_error(array('message' => 'invalid status'), 400);
        }
        $state = self::get_state();
        $state['steps'][$step_id]['status'] = $status;
        if ($status === 'done')    $state['steps'][$step_id]['completed_at'] = gmdate('c');
        if ($status === 'skipped') $state['steps'][$step_id]['skipped_at']   = gmdate('c');
        // Optional payload (for perimeter — industry, site_type, language, markets).
        if (isset($_POST['data'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- JSON; sanitized below
            $raw = wp_unslash($_POST['data']);
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $state['steps'][$step_id]['data'] = self::sanitize_tree($decoded);
                }
            } elseif (is_array($raw)) {
                $state['steps'][$step_id]['data'] = self::sanitize_tree($raw);
            }
        }
        self::save_state($state);
        wp_send_json_success($state);
    }

    public function ajax_set_persona() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'forbidden'), 403);
        }
        $persona = isset($_POST['persona_type']) ? sanitize_key(wp_unslash($_POST['persona_type'])) : '';
        if (!in_array($persona, array('consultant', 'wp_owner', 'exploring'), true)) {
            wp_send_json_error(array('message' => 'invalid persona'), 400);
        }
        $state = self::get_state();
        $state['persona_type'] = $persona;
        self::save_state($state);
        wp_send_json_success($state);
    }

    public function ajax_dismiss_widget() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'forbidden'), 403);
        }
        // Per-session dismissal — uses a transient (24h) keyed by user_id.
        $uid = get_current_user_id();
        if ($uid > 0) {
            set_transient('seo_aeo_setup_widget_dismissed_' . $uid, 1, 24 * HOUR_IN_SECONDS);
        }
        wp_send_json_success(array('dismissed' => true));
    }

    public function ajax_sync_state() {
        check_ajax_referer('seo_aeo_orchestra_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'forbidden'), 403);
        }
        $state = self::sync_from_plugin_state();
        wp_send_json_success($state);
    }

    /**
     * Recursive sanitization for step.data payloads. Strings → sanitize_text_field;
     * keys → sanitize_key; ints/floats/bools/null pass through. Same shape as
     * SEO_AEO_History::sanitize_recursive but module-local to avoid coupling.
     */
    public static function sanitize_tree($val) {
        if (is_array($val)) {
            $out = array();
            foreach ($val as $k => $v) {
                $ck = is_string($k) ? sanitize_key($k) : (int) $k;
                if ($ck === '' && is_string($k)) continue;
                $out[$ck] = self::sanitize_tree($v);
            }
            return $out;
        }
        if (is_string($val)) return sanitize_text_field($val);
        if (is_int($val) || is_float($val) || is_bool($val) || is_null($val)) return $val;
        return '';
    }
}

new SEO_AEO_Setup_Progress();

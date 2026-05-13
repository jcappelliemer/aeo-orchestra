<?php
/**
 * 3.40.0 — Capability matrix for per-builder action mode dispatch.
 *
 * The matrix encodes how reliably AEO Orchestra can apply each class
 * of action across the 10 environments we support (or document as
 * "unknown"). Three orthogonal dimensions per environment:
 *
 *   - surgical_text : surgical mid-content text edits
 *                     (rewrite intro, optimize keyword, expand content)
 *   - block_append  : appending whole content blocks
 *                     (FAQ section, authority block)
 *   - schema        : head-level JSON-LD / meta tag injection
 *                     (Schema.org, breadcrumbs)
 *
 * Each cell yields one of five mode levels:
 *
 *   - full   : 95-100% reliable, apply automatically without confirmation
 *   - high   : 85-95% reliable, apply automatically then verify post-apply
 *   - medium : 70-85% reliable, try automatic, fallback to manual on failure
 *   - low    : <70% reliable, default to manual mode, opt-in automatic
 *   - manual : 0% automatic, manual paste mode only
 *
 * Action-type → capability-dimension mapping in ACTION_TYPE_MAP.
 *
 * V3.40.0 ships the matrix + dispatch logic. The per-builder surgical
 * editors that take advantage of high/medium modes land in v3.40.1+.
 * For this release, full/high/medium all behave like the current
 * "execute and write to WP" path (which works for Schema + Meta but
 * is unreliable for surgical_text on non-classic environments); low
 * and manual route into the new manual-mode UX so the user gets a
 * graceful experience instead of a broken-looking automatic edit.
 */
if (!defined('ABSPATH')) exit;

class SEO_AEO_Capability_Matrix {

    /**
     * Capability matrix. Keys are environment identifiers used by
     * SEO_AEO_Site_Scanner::detect_builder(). For headless modes the
     * key is prefixed "headless_" + the mode (rest / gql / ssg).
     */
    const MATRIX = array(
        'classic'       => array('surgical_text' => 'full',   'block_append' => 'full',    'schema' => 'full'),
        'gutenberg'     => array('surgical_text' => 'high',   'block_append' => 'full',    'schema' => 'full'),
        'elementor'     => array('surgical_text' => 'high',   'block_append' => 'medium',  'schema' => 'full'),
        'divi'          => array('surgical_text' => 'medium', 'block_append' => 'medium',  'schema' => 'full'),
        'wpbakery'      => array('surgical_text' => 'medium', 'block_append' => 'medium',  'schema' => 'full'),
        'beaver'        => array('surgical_text' => 'medium', 'block_append' => 'medium',  'schema' => 'full'),
        'bricks'        => array('surgical_text' => 'low',    'block_append' => 'low',     'schema' => 'full'),
        'oxygen'        => array('surgical_text' => 'low',    'block_append' => 'low',     'schema' => 'full'),
        'headless_rest' => array('surgical_text' => 'high',   'block_append' => 'high',    'schema' => 'full'),
        'headless_gql'  => array('surgical_text' => 'medium', 'block_append' => 'medium',  'schema' => 'full'),
        'headless_ssg'  => array('surgical_text' => 'manual', 'block_append' => 'manual',  'schema' => 'high'),
        'unknown'       => array('surgical_text' => 'manual', 'block_append' => 'manual',  'schema' => 'manual'),
    );

    /**
     * Maps an action_type (as emitted by build_action_from_issue +
     * the AI prompts) to one of the three capability dimensions.
     * Action types not in this map default to surgical_text.
     */
    const ACTION_TYPE_MAP = array(
        'REWRITE_INTRO'             => 'surgical_text',
        'OPTIMIZE_FEATURED_SNIPPET' => 'surgical_text',
        'OPTIMIZE_KEYWORDS'         => 'surgical_text',
        'EXPAND_CONTENT'            => 'surgical_text',
        'FIX_HEADING_STRUCTURE'     => 'surgical_text',
        'REWRITE_META'              => 'schema',        // meta tags are head-level
        'ADD_FAQ_SECTION'           => 'block_append',
        'ADD_AUTHORITY_SIGNALS'     => 'block_append',
        'ADD_INTERNAL_LINKS'        => 'surgical_text',
        'ADD_ALT_TEXT'              => 'block_append',
        'GENERATE_SCHEMA'           => 'schema',
        'GENERATE_BREADCRUMB'       => 'schema',
        'FIX_DUPLICATE_CONTENT'     => 'surgical_text',
        'MANUAL_REVIEW'             => null,  // sentinel — always manual
    );

    /**
     * Compose the environment key from the saved options.
     * Returns 'unknown' when nothing has been detected yet.
     */
    public static function get_environment_key() {
        $builder    = get_option('aeo_site_builder', '');
        $is_headless = (bool) get_option('aeo_site_is_headless', false);
        $headless_mode = get_option('aeo_headless_mode', '');
        if ($is_headless && in_array($headless_mode, array('rest', 'gql', 'ssg'), true)) {
            return 'headless_' . $headless_mode;
        }
        $allowed = array_keys(self::MATRIX);
        return in_array($builder, $allowed, true) ? $builder : 'unknown';
    }

    /**
     * Resolve the action_type → mode for the current environment.
     * Returns one of full/high/medium/low/manual (always a string).
     */
    public static function get_mode_for_action($action_type) {
        if ($action_type === 'MANUAL_REVIEW' || !is_string($action_type)) {
            return 'manual';
        }
        $env = self::get_environment_key();
        $cap = isset(self::MATRIX[$env]) ? self::MATRIX[$env] : self::MATRIX['unknown'];
        $dim = isset(self::ACTION_TYPE_MAP[$action_type]) ? self::ACTION_TYPE_MAP[$action_type] : 'surgical_text';
        if ($dim === null) return 'manual';
        return isset($cap[$dim]) ? $cap[$dim] : 'manual';
    }

    /**
     * Returns true when the mode requires manual paste from the user
     * (i.e. the executor should NOT write to WP, just return text +
     * instructions).
     */
    public static function is_manual_mode($mode) {
        return $mode === 'manual' || $mode === 'low';
    }

    /**
     * Returns true when the mode is automatic but should verify after
     * apply. v3.40.0 doesn't yet implement post-apply verification;
     * v3.40.1 will. For now this is documentary.
     */
    public static function is_verify_mode($mode) {
        return $mode === 'high' || $mode === 'medium';
    }

    /**
     * Human-readable summary for the setup wizard / settings page.
     * Returns an array suitable for JSON-encoding to the JS template.
     */
    public static function get_capability_summary() {
        $env = self::get_environment_key();
        $cap = isset(self::MATRIX[$env]) ? self::MATRIX[$env] : self::MATRIX['unknown'];

        $env_labels = array(
            'classic'       => 'WordPress Classic Editor',
            'gutenberg'     => 'WordPress Gutenberg / Block Editor',
            'elementor'     => 'Elementor',
            'divi'          => 'Divi Builder',
            'wpbakery'      => 'WPBakery (Visual Composer)',
            'beaver'        => 'Beaver Builder',
            'bricks'        => 'Bricks Builder',
            'oxygen'        => 'Oxygen Builder',
            'headless_rest' => 'Headless (WP REST API)',
            'headless_gql'  => 'Headless (WPGraphQL)',
            'headless_ssg'  => 'Headless (Static Site Generator)',
            'hybrid'        => 'Sito hybrid (WP + React/JS islands)',
            'unknown'       => 'Ambiente sconosciuto',
        );

        $action_rows = array(
            array('label' => 'Schema markup (JSON-LD)',     'dim' => 'schema'),
            array('label' => 'Meta title / description',    'dim' => 'schema'),
            array('label' => 'Aggiunta FAQ section',        'dim' => 'block_append'),
            array('label' => 'Aggiunta authority block',    'dim' => 'block_append'),
            array('label' => 'Riscrittura intro / testo',   'dim' => 'surgical_text'),
            array('label' => 'Modifica heading / H1',       'dim' => 'surgical_text'),
            array('label' => 'Espansione contenuto',        'dim' => 'surgical_text'),
        );

        $mode_descriptions = array(
            'full'   => 'Automatica',
            'high'   => 'Automatica + verifica',
            'medium' => 'Tentativo automatico + fallback manuale',
            'low'    => 'Modalita\' manuale (opt-in automatico)',
            'manual' => 'Solo manuale (copia + paste)',
        );

        $rows = array();
        foreach ($action_rows as $r) {
            $mode = isset($cap[$r['dim']]) ? $cap[$r['dim']] : 'manual';
            $rows[] = array(
                'label'      => $r['label'],
                'dimension'  => $r['dim'],
                'mode'       => $mode,
                'mode_label' => isset($mode_descriptions[$mode]) ? $mode_descriptions[$mode] : $mode,
                'auto'       => !self::is_manual_mode($mode),
            );
        }

        return array(
            'environment_key'   => $env,
            'environment_label' => isset($env_labels[$env]) ? $env_labels[$env] : $env,
            'rows'              => $rows,
            'has_manual'        => count(array_filter($rows, function($r){ return !$r['auto']; })) > 0,
        );
    }
}

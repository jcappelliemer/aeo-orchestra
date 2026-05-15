<?php
/**
 * SEO_AEO_Action_Targets — single source of truth for action_type → write target.
 *
 * Introduced in v3.41.6 to converge the preview and execute dispatch paths.
 * Before v3.41.6 `ajax_preview_action` was a switch on $agent with 5 cases,
 * which fell through to manual_review/default for every semantic agent name
 * introduced in v3.40.7+ (schema_generator, faq_generator, etc.), telling the
 * user the action was non-applicable when execute would have applied it.
 *
 * This class provides:
 *   - Action_Targets::TARGETS  — the canonical map (used by docs + preview + execute)
 *   - Action_Targets::get_target($action_type)  — lookup with defaults
 *   - Action_Targets::detect_mode_and_builder($post_id, $action_type)
 *       — runs the SAME dispatch chain as ajax_execute_action,
 *         returns the editor that WOULD be picked + a human mode label.
 *   - Action_Targets::format_mode_label($editor_class, $builder)
 *
 * NEVER references "Gemini" / "OpenAI" / "Claude" in user-facing strings.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SEO_AEO_Action_Targets {

    /**
     * action_type → static metadata about where and how the action writes.
     * Field reference:
     *   - where.type     : 'post_meta' | 'post_content' | 'post_meta_dual' | 'none'
     *   - where.key      : meta_key (for post_meta), e.g. '_seo_aeo_custom_schema_html'
     *   - where.engines  : [yoast, rankmath, aioseo, native] for post_meta_dual
     *   - where.section  : 'end' | 'intro' | 'replace_block' | 'replace_substring' (for post_content)
     *   - where.emit_at  : human description of when the content surfaces (frontend curl, wp_head, etc.)
     *   - operation      : 'overwrite' | 'append' | 'replace_section' | 'replace_substring' | 'none'
     *   - reversible     : true | false
     *   - backup_via     : 'snapshot_manager' | 'meta_backup_key' | 'none'
     *   - fallback       : 'manual_mode_modal' | 'refund_with_message' | 'silent_skip'
     *   - endpoint       : backend FastAPI path the preview/execute should hit
     *   - estimated_credits : default cost (DB-overridable)
     *   - notes          : free text for docs/action-types.md
     */
    const TARGETS = array(
        'GENERATE_SCHEMA' => array(
            'where' => array(
                'type'    => 'post_meta',
                'key'     => '_seo_aeo_custom_schema_html',
                'emit_at' => 'wp_head priority 12',
            ),
            'operation'         => 'overwrite',
            'reversible'        => true,
            'backup_via'        => 'snapshot_manager',
            'fallback'          => 'manual_mode_modal',
            'endpoint'          => '/ai/generate-schema',
            'estimated_credits' => 3,
            'notes'             => 'Dedicated endpoint /ai/generate-schema (v3.41.2). Sanitizer preserves <script type="application/ld+json"> via custom wp_kses whitelist (v3.41.1).',
        ),
        'REWRITE_META' => array(
            'where' => array(
                'type'    => 'post_meta_dual',
                'engines' => array('yoast', 'rankmath', 'aioseo', 'native'),
                'keys'    => array(
                    'native'   => array('_seo_aeo_meta_title', '_seo_aeo_meta_description', '_seo_aeo_meta_keywords'),
                    'yoast'    => array('_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw'),
                    'rankmath' => array('rank_math_title', 'rank_math_description', 'rank_math_focus_keyword'),
                    'aioseo'   => array('_aioseo_title', '_aioseo_description', '_aioseo_keywords'),
                ),
                'emit_at' => '<title> + <meta name="description"> in <head> (engine-dependent)',
            ),
            'operation'         => 'overwrite',
            'reversible'        => true,
            'backup_via'        => 'snapshot_manager',
            'fallback'          => 'manual_mode_modal',
            'endpoint'          => '/ai/generate-meta',
            'estimated_credits' => 2,
            'notes'             => 'Dual-write: active SEO plugin keys + Orchestra native keys (via SEO_AEO_Engine_Bridge::write_meta). Restore via Engine_Bridge::restore_meta.',
        ),
        'OPTIMIZE_KEYWORDS' => array(
            'where' => array(
                'type'    => 'post_meta_dual',
                'engines' => array('yoast', 'rankmath', 'aioseo', 'native'),
                'keys'    => array(
                    'native'   => array('_seo_aeo_meta_keywords'),
                    'yoast'    => array('_yoast_wpseo_focuskw'),
                    'rankmath' => array('rank_math_focus_keyword'),
                    'aioseo'   => array('_aioseo_keywords'),
                ),
                'emit_at' => 'meta keywords / focus keyword (engine-dependent)',
            ),
            'operation'         => 'overwrite',
            'reversible'        => true,
            'backup_via'        => 'snapshot_manager',
            'fallback'          => 'manual_mode_modal',
            'endpoint'          => '/ai/generate-meta',
            'estimated_credits' => 2,
            'notes'             => 'Same dual-write path as REWRITE_META but only the keywords field.',
        ),
        'ADD_FAQ_SECTION' => array(
            'where' => array(
                'type'    => 'post_content',
                'section' => 'append',
                'marker'  => '<!-- aeo-orchestra:faq-block -->',
                'emit_at' => 'rendered post body (the_content filter)',
            ),
            'operation'         => 'append',
            'reversible'        => true,
            'backup_via'        => 'snapshot_manager',
            'fallback'          => 'manual_mode_modal',
            'endpoint'          => '/ai/aeo-content',
            'estimated_credits' => 10,
            'notes'             => 'Appended at end of post_content, idempotently wrapped with HTML comments for re-application detection.',
        ),
        'ADD_AUTHORITY_SIGNALS' => array(
            'where' => array(
                'type'    => 'post_content',
                'section' => 'replace_substring',
                'emit_at' => 'rendered post body (the_content filter)',
            ),
            'operation'         => 'replace_substring',
            'reversible'        => true,
            'backup_via'        => 'snapshot_manager',
            'fallback'          => 'manual_mode_modal',
            'endpoint'          => '/ai/aeo-content',
            'estimated_credits' => 10,
            'notes'             => 'Surgical text replacement via the appropriate editor (Classic / Gutenberg / Elementor / Divi / WPBakery / Beaver / Bricks / Oxygen / Headless REST / Headless WPGraphQL).',
        ),
        'REWRITE_INTRO' => array(
            'where' => array(
                'type'    => 'post_content',
                'section' => 'replace_substring',
                'emit_at' => 'rendered post body (the_content filter)',
            ),
            'operation'         => 'replace_substring',
            'reversible'        => true,
            'backup_via'        => 'snapshot_manager',
            'fallback'          => 'manual_mode_modal',
            'endpoint'          => '/ai/aeo-content',
            'estimated_credits' => 10,
            'notes'             => 'Surgical text replacement of the first paragraph(s).',
        ),
        'OPTIMIZE_FEATURED_SNIPPET' => array(
            'where' => array(
                'type'    => 'post_content',
                'section' => 'replace_substring',
                'emit_at' => 'rendered post body (the_content filter)',
            ),
            'operation'         => 'replace_substring',
            'reversible'        => true,
            'backup_via'        => 'snapshot_manager',
            'fallback'          => 'manual_mode_modal',
            'endpoint'          => '/ai/aeo-content',
            'estimated_credits' => 10,
            'notes'             => 'Replaces a paragraph with a featured-snippet-optimized version (40-50 word answer-box format).',
        ),
        'ADD_INTERNAL_LINKS' => array(
            'where' => array(
                'type'    => 'post_content',
                'section' => 'append',
                'emit_at' => 'rendered post body (the_content filter)',
            ),
            'operation'         => 'append',
            'reversible'        => true,
            'backup_via'        => 'snapshot_manager',
            'fallback'          => 'manual_mode_modal',
            'endpoint'          => '/ai/generate-content',
            'estimated_credits' => 15,
            'notes'             => 'Currently returns manual_mode by default — full auto-apply lands in v3.42.0. Plugin appends generated link block when surgical editor accepts.',
        ),
        'MANUAL_REVIEW' => array(
            'where' => array(
                'type'    => 'none',
                'emit_at' => 'no automatic write',
            ),
            'operation'         => 'none',
            'reversible'        => false,
            'backup_via'        => 'none',
            'fallback'          => 'manual_mode_modal',
            'endpoint'          => null,
            'estimated_credits' => 0,
            'notes'             => 'Always-manual: user opens the page in WordPress and applies the suggestion themselves.',
        ),
    );

    public static function get_target($action_type) {
        if (isset(self::TARGETS[$action_type])) {
            return self::TARGETS[$action_type];
        }
        return self::TARGETS['MANUAL_REVIEW'];
    }

    /**
     * agent → action_type inference table (mirrors ajax_preview_action /
     * ajax_execute_action defensive backstop). Kept here so the same map is
     * accessible from any caller.
     */
    const AGENT_TO_TYPE = array(
        'schema_generator'    => 'GENERATE_SCHEMA',
        'faq_generator'       => 'ADD_FAQ_SECTION',
        'authority_generator' => 'ADD_AUTHORITY_SIGNALS',
        'intro_rewriter'      => 'REWRITE_INTRO',
        'snippet_optimizer'   => 'OPTIMIZE_FEATURED_SNIPPET',
        'keyword_optimizer'   => 'OPTIMIZE_KEYWORDS',
        'meta_optimizer'      => 'REWRITE_META',
    );

    public static function infer_action_type($agent, $passed_action_type = '') {
        if (is_string($passed_action_type) && $passed_action_type !== '') {
            return $passed_action_type;
        }
        if (isset(self::AGENT_TO_TYPE[$agent])) {
            return self::AGENT_TO_TYPE[$agent];
        }
        return '';
    }

    /**
     * Dry-run dispatch — runs the same can_handle() chain as ajax_execute_action
     * for surgical_text / block_append actions, returns the winning editor
     * class name + a human label, WITHOUT calling apply().
     *
     * For schema/meta actions (post_meta writes) the editor concept doesn't
     * apply; we return a fixed label.
     *
     * Returns: array(
     *   'mode_label'   => 'Headless REST' | 'Surgical Editor — Elementor' | 'Direct post_meta write' | 'Manual mode',
     *   'editor_class' => 'SEO_AEO_..._Surgical_Editor' | null,
     *   'builder'      => 'classic' | 'elementor' | ...
     *   'is_manual'    => bool,
     * )
     */
    public static function detect_mode_and_builder($post_id, $action_type) {
        $builder = get_option('aeo_site_builder', 'unknown');
        $mode = class_exists('SEO_AEO_Capability_Matrix')
            ? SEO_AEO_Capability_Matrix::get_mode_for_action($action_type)
            : 'manual';

        // 3.41.6 - empty action_type always falls back to manual (defense in depth).
        if ($action_type === '' || $action_type === null) {
            return array(
                'mode_label'   => 'Modalita\' manuale (azione non specificata)',
                'editor_class' => null,
                'builder'      => $builder,
                'mode'         => 'manual',
                'is_manual'    => true,
            );
        }

        // MANUAL_REVIEW / capability matrix says manual.
        if (class_exists('SEO_AEO_Capability_Matrix') && SEO_AEO_Capability_Matrix::is_manual_mode($mode)) {
            return array(
                'mode_label'   => 'Modalità manuale',
                'editor_class' => null,
                'builder'      => $builder,
                'mode'         => $mode,
                'is_manual'    => true,
            );
        }

        // Schema / meta writes — direct post_meta path, no editor.
        $target = self::get_target($action_type);
        $where_type = isset($target['where']['type']) ? $target['where']['type'] : 'none';
        if ($where_type === 'post_meta' || $where_type === 'post_meta_dual') {
            // Headless detection still applies (REST goes through WP fine for post_meta).
            $is_headless = (bool) get_option('aeo_site_is_headless', false);
            $headless_mode = get_option('aeo_headless_mode', '');
            if ($is_headless && in_array($headless_mode, array('rest', 'gql', 'ssg'), true)) {
                $label = $headless_mode === 'rest'
                    ? 'Headless REST (post_meta diretto)'
                    : ($headless_mode === 'gql' ? 'Headless WPGraphQL (post_meta diretto)' : 'SSG (post_meta diretto)');
                return array(
                    'mode_label'   => $label,
                    'editor_class' => null,
                    'builder'      => $builder,
                    'mode'         => $mode,
                    'is_manual'    => false,
                );
            }
            return array(
                'mode_label'   => 'Scrittura diretta post_meta',
                'editor_class' => null,
                'builder'      => $builder,
                'mode'         => $mode,
                'is_manual'    => false,
            );
        }

        // post_content writes — run the same dispatch chain as execute.
        if ($post_id > 0) {
            $chain = array(
                'SEO_AEO_Headless_WPGraphQL_Surgical_Editor' => 'Headless WPGraphQL',
                'SEO_AEO_Headless_REST_Surgical_Editor'      => 'Headless REST',
                'SEO_AEO_Elementor_Surgical_Editor'          => 'Surgical Editor — Elementor',
                'SEO_AEO_Divi_Surgical_Editor'               => 'Surgical Editor — Divi',
                'SEO_AEO_WPBakery_Surgical_Editor'           => 'Surgical Editor — WPBakery',
                'SEO_AEO_Beaver_Surgical_Editor'             => 'Surgical Editor — Beaver Builder',
                'SEO_AEO_Bricks_Surgical_Editor'             => 'Surgical Editor — Bricks',
                'SEO_AEO_Oxygen_Surgical_Editor'             => 'Surgical Editor — Oxygen',
                'SEO_AEO_Gutenberg_Surgical_Editor'          => 'Surgical Editor — Gutenberg',
                'SEO_AEO_Classic_Surgical_Editor'            => 'Surgical Editor — Classic Editor',
            );
            foreach ($chain as $cls => $label) {
                if (class_exists($cls) && call_user_func(array($cls, 'can_handle'), $post_id)) {
                    return array(
                        'mode_label'   => $label,
                        'editor_class' => $cls,
                        'builder'      => $builder,
                        'mode'         => $mode,
                        'is_manual'    => false,
                    );
                }
            }
        }

        // post_content action but no editor matched → manual fallback.
        return array(
            'mode_label'   => 'Modalità manuale (nessun editor compatibile)',
            'editor_class' => null,
            'builder'      => $builder,
            'mode'         => 'manual',
            'is_manual'    => true,
        );
    }

    /**
     * Build a "where" human label parametrized by the post_id, used by the
     * frontend transparency panel.
     */
    public static function format_where_label($action_type, $post_id) {
        $target = self::get_target($action_type);
        $where = isset($target['where']) ? $target['where'] : array();
        $type = isset($where['type']) ? $where['type'] : 'none';
        $post_ref = $post_id > 0 ? ('Page #' . $post_id) : '(no post)';
        if ($type === 'post_meta') {
            $key = isset($where['key']) ? $where['key'] : '?';
            $emit = isset($where['emit_at']) ? $where['emit_at'] : '';
            return "post_meta.{$key} su {$post_ref}" . ($emit ? " — emit: {$emit}" : '');
        }
        if ($type === 'post_meta_dual') {
            $emit = isset($where['emit_at']) ? $where['emit_at'] : '';
            return "post_meta dual-write (Yoast/RankMath/AIOSEO + native) su {$post_ref}" . ($emit ? " — emit: {$emit}" : '');
        }
        if ($type === 'post_content') {
            $section = isset($where['section']) ? $where['section'] : '?';
            return "post_content.{$section} su {$post_ref}";
        }
        return 'nessuna scrittura automatica';
    }

    /**
     * Backup-location label for the transparency panel.
     */
    public static function format_backup_label($action_type) {
        $target = self::get_target($action_type);
        $via = isset($target['backup_via']) ? $target['backup_via'] : 'none';
        if ($via === 'snapshot_manager') {
            return 'Sì — snapshot completo via SEO_AEO_Snapshot_Manager (TTL 7 giorni). Ripristinabile dalla cronologia.';
        }
        if ($via === 'meta_backup_key') {
            return 'Sì — valore precedente salvato in chiave _backup affiancata.';
        }
        return 'No (operazione non reversibile automaticamente).';
    }

    /**
     * Build the unified preview payload "skeleton" that ajax_preview_action
     * augments with `proposed.value`, `current.value`, and `estimated_credits`.
     */
    public static function build_preview_skeleton($agent, $action_type, $post_id) {
        $target = self::get_target($action_type);
        $detect = self::detect_mode_and_builder($post_id, $action_type);
        return array(
            'preview'              => true,
            'agent'                => $agent,
            'action_type'          => $action_type,
            'mode'                 => $detect['mode'],
            'mode_label'           => $detect['mode_label'],
            'builder'              => $detect['builder'],
            'editor_class'         => $detect['editor_class'],
            'where'                => isset($target['where']) ? $target['where'] : array(),
            'where_label'          => self::format_where_label($action_type, $post_id),
            'operation'            => isset($target['operation']) ? $target['operation'] : 'none',
            'reversible'           => !empty($target['reversible']),
            'backup_via'           => isset($target['backup_via']) ? $target['backup_via'] : 'none',
            'backup_label'         => self::format_backup_label($action_type),
            'fallback'             => isset($target['fallback']) ? $target['fallback'] : 'manual_mode_modal',
            'estimated_credits'    => isset($target['estimated_credits']) ? $target['estimated_credits'] : 0,
            'is_manual'            => !empty($detect['is_manual']),
        );
    }

    /**
     * Reads the CURRENT value at the action_type's write target — used to
     * populate the "Prima" field of the transparency panel.
     */
    public static function read_current_value($action_type, $post_id) {
        if ($post_id <= 0) return null;
        $target = self::get_target($action_type);
        $where = isset($target['where']) ? $target['where'] : array();
        $type = isset($where['type']) ? $where['type'] : 'none';
        if ($type === 'post_meta') {
            $key = isset($where['key']) ? $where['key'] : '';
            return $key ? (string) get_post_meta($post_id, $key, true) : null;
        }
        if ($type === 'post_meta_dual') {
            $native_keys = isset($where['keys']['native']) ? $where['keys']['native'] : array();
            $out = array();
            foreach ($native_keys as $k) {
                $out[$k] = (string) get_post_meta($post_id, $k, true);
            }
            if (class_exists('SEO_AEO_Engine_Bridge')) {
                try {
                    $bridge = SEO_AEO_Engine_Bridge::read_meta($post_id);
                    if (is_array($bridge)) $out['__bridge__'] = $bridge;
                } catch (Throwable $e) { /* ignore */ }
            }
            return $out;
        }
        if ($type === 'post_content') {
            $content = (string) get_post_field('post_content', $post_id);
            $section = isset($where['section']) ? $where['section'] : '';
            if ($section === 'append') return '(append — il contenuto attuale viene mantenuto, nuovo blocco aggiunto in coda)';
            // For replace_substring we don't know which substring without the
            // edits array; return the first ~500 chars as context.
            return mb_substr(wp_strip_all_tags($content), 0, 500);
        }
        return null;
    }
}

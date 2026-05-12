<?php
/**
 * 3.38.0 — Setup Guidato sticky widget.
 *
 * Renders a fixed bottom-right pill on every admin page that contains
 * "seo-aeo-" in the page slug, EXCEPT on the Setup Guidato page itself.
 * Click → expand panel above with step list + progress.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Setup_Widget {

    public function __construct() {
        add_action('admin_footer', array($this, 'maybe_render'));
    }

    public function maybe_render() {
        if (!current_user_can('manage_options')) return;

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || !is_object($screen)) return;
        $base = isset($screen->id) ? (string) $screen->id : '';
        // Only on plugin admin pages — match the parent menu slug prefix.
        if (strpos($base, 'seo-aeo') === false) return;
        // Hide on Setup Guidato page itself (no point: user is already there).
        if (strpos($base, 'seo-aeo-setup-guidato') !== false) return;

        // Per-user 24h dismissal via transient.
        $uid = get_current_user_id();
        if ($uid > 0 && get_transient('seo_aeo_setup_widget_dismissed_' . $uid)) return;

        if (!class_exists('SEO_AEO_Setup_Progress')) return;

        $state       = SEO_AEO_Setup_Progress::get_state();
        $done_count  = SEO_AEO_Setup_Progress::done_count();
        $total_steps = count(SEO_AEO_Setup_Progress::STEPS);

        // Hide once everything is done — replace with a minimal "completed" pill
        // for 7 days after completion, then disappear entirely.
        $all_done = ($done_count >= $total_steps);

        $T = function ($s) {
            return class_exists('SEO_AEO_T') ? SEO_AEO_T::t($s) : $s;
        };

        $setup_url = admin_url('admin.php?page=seo-aeo-setup-guidato');

        $step_labels = array(
            'perimeter'        => $T('Perimetro'),
            'business_profile' => $T('Profilo Business'),
            'keyword_research' => $T('Keyword Research'),
            'brand_voice'      => $T('Brand Voice'),
            'orchestrator'     => $T('Orchestratore'),
            'native_output'    => $T('SEO + AEO Output'),
            'auto_pilot'       => $T('Auto-Pilot'),
        );
        ?>
        <div id="orch-setup-widget" class="orch-setup-widget<?php echo $all_done ? ' orch-setup-widget--done' : ''; ?>" role="region" aria-label="<?php echo esc_attr($T('Setup Guidato progresso')); ?>">
            <button type="button" class="orch-setup-widget-toggle" id="orch-setup-widget-toggle" aria-expanded="false" aria-controls="orch-setup-widget-panel">
                <?php if ($all_done): ?>
                    <span class="orch-setup-widget-icon">✓</span>
                    <span class="orch-setup-widget-pill"><?php echo esc_html($T('Setup ok')); ?></span>
                <?php else: ?>
                    <span class="orch-setup-widget-icon">🎯</span>
                    <span class="orch-setup-widget-pill"><?php echo esc_html($done_count); ?>/<?php echo esc_html($total_steps); ?></span>
                <?php endif; ?>
            </button>
            <div class="orch-setup-widget-panel" id="orch-setup-widget-panel" hidden>
                <div class="orch-setup-widget-head">
                    <strong>🎯 <?php echo esc_html($T('Setup AEO Orchestra')); ?></strong>
                    <button type="button" class="orch-setup-widget-close" aria-label="<?php echo esc_attr($T('Chiudi')); ?>">&times;</button>
                </div>
                <div class="orch-setup-widget-progress">
                    <div class="orch-setup-widget-bar"><div class="orch-setup-widget-bar-fill" style="width: <?php echo esc_attr($total_steps > 0 ? round(($done_count / $total_steps) * 100) : 0); ?>%"></div></div>
                    <span><?php echo esc_html($done_count); ?>/<?php echo esc_html($total_steps); ?> <?php echo esc_html($T('completati')); ?></span>
                </div>
                <ul class="orch-setup-widget-steps">
                    <?php
                    $first_todo_seen = false;
                    foreach (SEO_AEO_Setup_Progress::STEPS as $sid):
                        $status = $state['steps'][$sid]['status'] ?? 'todo';
                        $is_done = ($status === 'done');
                        $is_skipped = ($status === 'skipped');
                        $is_next = (!$first_todo_seen && !$is_done && !$is_skipped);
                        if ($is_next) $first_todo_seen = true;
                        $cls = 'orch-setup-widget-step';
                        if ($is_done) $cls .= ' is-done';
                        if ($is_skipped) $cls .= ' is-skipped';
                        if ($is_next) $cls .= ' is-next';
                        ?>
                        <li class="<?php echo esc_attr($cls); ?>">
                            <span class="orch-setup-widget-step-icon">
                                <?php echo $is_done ? '✓' : ($is_skipped ? '⊘' : ($is_next ? '→' : '○')); ?>
                            </span>
                            <span class="orch-setup-widget-step-label"><?php echo esc_html($step_labels[$sid]); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?php echo esc_url($setup_url); ?>" class="orch-setup-widget-cta"><?php echo esc_html($all_done ? $T('Vedi recap') : $T('Apri Setup Guidato')); ?></a>
            </div>
        </div>

        <style>
        .orch-setup-widget { position: fixed; bottom: 24px; right: 24px; z-index: 99500; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .orch-setup-widget-toggle { display:flex; align-items:center; gap:8px; background: linear-gradient(135deg, #0A0E27, #0055FF); color:#fff; border:0; border-radius: 999px; padding: 10px 16px; cursor: pointer; box-shadow: 0 4px 16px rgba(0,85,255,0.3); font-weight: 600; font-size: 13px; transition: all 160ms; }
        .orch-setup-widget-toggle:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,85,255,0.4); }
        .orch-setup-widget-icon { font-size: 16px; }
        .orch-setup-widget-pill { font-weight: 700; }
        .orch-setup-widget--done .orch-setup-widget-toggle { background: linear-gradient(135deg, #059669, #10b981); box-shadow: 0 4px 16px rgba(16,185,129,0.3); }
        .orch-setup-widget-panel { position: absolute; bottom: calc(100% + 12px); right: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; box-shadow: 0 10px 30px rgba(15,23,42,0.18); width: 280px; }
        .orch-setup-widget-panel[hidden] { display: none; }
        .orch-setup-widget-head { display:flex; justify-content:space-between; align-items:center; margin-bottom: 10px; font-size: 13px; color:#0f172a; }
        .orch-setup-widget-close { background:transparent; border:0; cursor:pointer; font-size:18px; color:#64748b; line-height:1; padding:2px 6px; }
        .orch-setup-widget-close:hover { color:#0f172a; }
        .orch-setup-widget-progress { display:flex; align-items:center; gap:10px; margin-bottom: 12px; font-size: 11px; color:#64748b; }
        .orch-setup-widget-bar { flex:1; height: 6px; background:#e2e8f0; border-radius:999px; overflow:hidden; }
        .orch-setup-widget-bar-fill { height:100%; background: linear-gradient(90deg, #0055FF, #00E5FF); border-radius:999px; transition: width 350ms; }
        .orch-setup-widget-steps { list-style:none; margin:0 0 12px; padding:0; font-size:12px; }
        .orch-setup-widget-step { display:flex; align-items:center; gap:8px; padding:5px 0; color:#475569; }
        .orch-setup-widget-step.is-done { color:#065f46; }
        .orch-setup-widget-step.is-next { color:#0055FF; font-weight:600; }
        .orch-setup-widget-step.is-skipped { color:#94a3b8; text-decoration: line-through; }
        .orch-setup-widget-step-icon { width:16px; text-align:center; font-weight:700; }
        .orch-setup-widget-cta { display:block; background:#0055FF; color:#fff; text-align:center; padding:9px 14px; border-radius:8px; text-decoration:none; font-weight:600; font-size:12.5px; transition: background 160ms; }
        .orch-setup-widget-cta:hover { background:#0042CC; color:#fff; }
        @media (max-width: 600px) {
            .orch-setup-widget { bottom: 16px; right: 16px; }
            .orch-setup-widget-panel { width: calc(100vw - 32px); right: -8px; }
        }
        </style>

        <script>
        (function(){
            var widget = document.getElementById('orch-setup-widget');
            if (!widget) return;
            var toggle = document.getElementById('orch-setup-widget-toggle');
            var panel  = document.getElementById('orch-setup-widget-panel');
            var closeBtn = widget.querySelector('.orch-setup-widget-close');
            if (!toggle || !panel) return;

            function openPanel() {
                panel.hidden = false;
                toggle.setAttribute('aria-expanded', 'true');
            }
            function closePanel() {
                panel.hidden = true;
                toggle.setAttribute('aria-expanded', 'false');
            }
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                if (panel.hidden) openPanel(); else closePanel();
            });
            if (closeBtn) closeBtn.addEventListener('click', function(e) { e.stopPropagation(); closePanel(); });
            // Click outside → close.
            document.addEventListener('click', function(e) {
                if (!widget.contains(e.target) && !panel.hidden) closePanel();
            });
            // Escape → close.
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !panel.hidden) closePanel();
            });
        })();
        </script>
        <?php
    }
}

new SEO_AEO_Setup_Widget();

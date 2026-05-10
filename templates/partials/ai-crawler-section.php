<?php
/**
 * 3.35.84-beta — AI Performance Phase 1 main partial.
 * Renders crawler activity tracking from class-ai-crawler-detector logs.
 *
 * Phase 2 (Bing Citation tracking) lives in bing-citation-section.php (preserved).
 *
 * Empty state (hits_total === 0): primo accesso, render CTA Verify-Live + Profilo Business.
 * Live state: 4 stat card + bar chart top 5 + table top 10 + sparkline + compliance.
 */
if (!defined('ABSPATH')) exit;
$T = isset($T) ? $T : function($s) { return class_exists('SEO_AEO_T') ? SEO_AEO_T::t($s) : $s; };

// Detect empty state (no hits last 28 days)
$aip_hits_total = 0;
$aip_first_hit_at = null;
if (class_exists('SEO_AEO_AI_Crawler_Detector')) {
    global $wpdb;
    $log_table = $wpdb->prefix . SEO_AEO_AI_Crawler_Detector::TABLE_NAME;
    if ($wpdb->get_var("SHOW TABLES LIKE '$log_table'") === $log_table) {
        $aip_hits_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $log_table WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 28 DAY)");
        $aip_first_hit_at = $wpdb->get_var("SELECT MIN(visited_at) FROM $log_table");
    }
}
$aip_is_empty = ($aip_hits_total === 0);
?>

<div class="orch3-card aip-section" id="aip-section">
    <div class="aip-section-head">
        <div>
            <h2 class="aip-section-title">🤖 <?php echo $T('AI Performance · Quanto le AI scansionano il tuo sito'); ?></h2>
            <p class="aip-section-subtitle">
                <?php echo $T('Tracking attività bot AI ultimi 28 giorni. Dati locali, GDPR-compliant.'); ?>
                <span class="aip-phase2-hint"><?php echo $T('Phase 2 con citation count Bing API in arrivo in v3.35.85.'); ?></span>
            </p>
        </div>
        <button type="button" class="orch3-btn orch3-btn-ghost aip-toggle" aria-expanded="true" title="<?php echo esc_attr($T('Espandi/comprimi sezione')); ?>">▼</button>
    </div>

    <div class="aip-section-body">
        <?php if ($aip_is_empty): ?>
            <?php
                $aip_first_hit_at_str = $aip_first_hit_at;
                include __DIR__ . '/ai-crawler-empty-state.php';
            ?>
        <?php else: ?>
            <?php include __DIR__ . '/ai-crawler-live-state.php'; ?>
        <?php endif; ?>
    </div>
</div>

<?php ob_start(); ?>
:root {
    --aip-bg:               #fafafa;
    --aip-card-bg:          #ffffff;
    --aip-stat-accent:      #6366f1;
    --aip-bot-green:        #16a34a;
    --aip-bot-yellow:       #d97706;
    --aip-bot-red:          #b91c1c;
    --aip-bot-green-tint:   #f0fdf4;
    --aip-bot-yellow-tint:  #fffbeb;
    --aip-bot-red-tint:     #fef2f2;
    --aip-spark-line:       #6366f1;
    --aip-empty-bg:         linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
    --aip-phase2-hint:      #6366f1;
}
.aip-section { background: var(--aip-bg); padding: 18px 22px; margin: 16px 0; border-radius: 10px; }
.aip-section-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 14px; flex-wrap: wrap; }
.aip-section-title { margin: 0; font-size: 18px; font-weight: 600; color: var(--orch-ink, #0f172a); }
.aip-section-subtitle { font-size: 13px; color: var(--orch-muted, #475569); margin: 4px 0 0; line-height: 1.55; }
.aip-phase2-hint { color: var(--aip-phase2-hint); font-style: italic; }
.aip-toggle { font-size: 12px; padding: 4px 10px; }

/* === 4 stat card === */
.aip-stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
.aip-stat-card { background: var(--aip-card-bg); border: 1px solid var(--orch-line, #e4e4e7); border-left: 4px solid var(--aip-stat-accent); border-radius: 8px; padding: 14px 16px; }
.aip-stat-card-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--orch-muted, #475569); }
.aip-stat-card-value { font-size: 28px; font-weight: 700; color: var(--orch-ink, #0f172a); margin: 4px 0; line-height: 1; }
.aip-stat-card-sub { font-size: 11px; color: var(--orch-muted, #475569); }
.aip-stat-card-trend--up { color: #0f766e; }
.aip-stat-card-trend--down { color: #b91c1c; }
.aip-stat-card-trend--flat { color: var(--orch-muted, #475569); }
.aip-stat-card--alert { border-left-color: var(--aip-bot-red); }

/* === Bar chart top 5 bot === */
.aip-bot-row { display: grid; grid-template-columns: 220px 1fr 70px; gap: 10px; align-items: center; padding: 6px 0; font-size: 13px; }
.aip-bot-name { display: flex; align-items: center; gap: 8px; }
.aip-bot-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.aip-bot-dot--green  { background: var(--aip-bot-green); }
.aip-bot-dot--yellow { background: var(--aip-bot-yellow); }
.aip-bot-dot--red    { background: var(--aip-bot-red); }
.aip-bot-bar { height: 18px; background: var(--orch-line-soft, #f1f5f9); border-radius: 4px; overflow: hidden; }
.aip-bot-bar-fill { height: 100%; transition: width 0.4s ease-out; }
.aip-bot-bar-fill--green  { background: linear-gradient(90deg, #16a34a, #22c55e); }
.aip-bot-bar-fill--yellow { background: linear-gradient(90deg, #d97706, #f59e0b); }
.aip-bot-bar-fill--red    { background: linear-gradient(90deg, #b91c1c, #ef4444); }
.aip-bot-count { text-align: right; font-weight: 600; color: var(--orch-ink, #0f172a); font-variant-numeric: tabular-nums; }
.aip-bot-legend { margin-top: 10px; font-size: 11px; color: var(--orch-muted, #475569); display: flex; gap: 14px; flex-wrap: wrap; }

/* === Top pages table === */
.aip-pages-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 6px; }
.aip-pages-table th { text-align: left; padding: 8px 10px; background: var(--orch-line-soft, #f1f5f9); font-size: 11px; text-transform: uppercase; color: var(--orch-muted, #475569); border-bottom: 1px solid var(--orch-line, #e4e4e7); }
.aip-pages-table td { padding: 8px 10px; border-bottom: 1px solid var(--orch-line-soft, #f1f5f9); vertical-align: top; }
.aip-pages-url { font-family: ui-monospace, monospace; font-size: 12px; color: #2563eb; word-break: break-all; }
.aip-pages-hits { font-weight: 600; text-align: right; width: 70px; font-variant-numeric: tabular-nums; }
.aip-pages-bots { font-size: 11px; color: var(--orch-muted, #475569); }

/* === Sparkline === */
.aip-sparkline { width: 100%; height: 100px; margin: 12px 0; }
.aip-sparkline polyline { stroke-linejoin: round; stroke-linecap: round; transition: stroke-width 0.15s; }
.aip-sparkline polyline:hover { stroke-width: 3; }
.aip-spark-legend { font-size: 11px; color: var(--orch-muted, #475569); display: flex; gap: 12px; flex-wrap: wrap; }
.aip-spark-legend-dot { display: inline-block; width: 12px; height: 3px; vertical-align: middle; margin-right: 4px; border-radius: 2px; }

/* === Compliance === */
.aip-compliance-card { padding: 14px 18px; border-radius: 8px; background: var(--aip-bot-green-tint); border-left: 4px solid var(--aip-bot-green); }
.aip-compliance-card--violations { background: var(--aip-bot-yellow-tint); border-left-color: var(--aip-bot-yellow); }
.aip-compliance-actions { margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap; }

/* === Phase 2 footer === */
.aip-phase2-footer { margin-top: 16px; padding: 12px 16px; background: rgba(99, 102, 241, 0.06); border-left: 3px solid #6366f1; border-radius: 6px; font-size: 12px; color: var(--orch-muted, #475569); line-height: 1.55; }

/* === Subsection wrapper === */
.aip-subsection { margin: 18px 0; }
.aip-subsection-title { font-size: 14px; font-weight: 600; color: var(--orch-ink, #0f172a); margin: 0 0 10px; display: flex; justify-content: space-between; align-items: center; }
.aip-subsection-title-actions { font-size: 11px; }

/* === Empty state === */
.aip-empty { background: var(--aip-empty-bg); border-radius: 12px; padding: 36px 32px; text-align: center; }
.aip-empty-icon { font-size: 48px; animation: aipPulse 2s ease-in-out infinite; }
@keyframes aipPulse { 0%, 100% { opacity: 0.6; transform: scale(1); } 50% { opacity: 1; transform: scale(1.05); } }
.aip-empty-title { font-size: 18px; font-weight: 600; color: var(--orch-ink, #0f172a); margin: 16px 0 8px; }
.aip-empty-checklist { display: inline-block; text-align: left; margin: 18px 0; }
.aip-empty-cta { display: inline-block; padding: 10px 20px; background: #0055FF; color: #ffffff; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 10px; }
.aip-empty-cta:hover { background: #0044cc; color: #fff; text-decoration: none; }

/* Mobile */
@media (max-width: 880px) {
    .aip-stats-row { grid-template-columns: repeat(2, 1fr); }
    .aip-bot-row { grid-template-columns: 140px 1fr 50px; font-size: 12px; }
    .aip-pages-table { font-size: 12px; }
    .aip-pages-bots { display: none; }
}
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

<?php ob_start(); ?>
(function($){
    'use strict';
    if (typeof $ === 'undefined') return;

    var $section = $('#aip-section');
    if (!$section.length) return;

    // Toggle expand/collapse with localStorage memory
    var COLLAPSED_KEY = 'orch_aip_collapsed';
    var $toggle = $section.find('.aip-toggle');
    var $body = $section.find('.aip-section-body');

    function applyCollapseState() {
        var collapsed = false;
        try { collapsed = window.localStorage.getItem(COLLAPSED_KEY) === '1'; } catch(e) {}
        if (collapsed) {
            $body.hide();
            $toggle.text('▶').attr('aria-expanded', 'false');
        } else {
            $body.show();
            $toggle.text('▼').attr('aria-expanded', 'true');
        }
    }
    applyCollapseState();

    $toggle.on('click', function(){
        var collapsed = $body.is(':visible');
        try { window.localStorage.setItem(COLLAPSED_KEY, collapsed ? '1' : '0'); } catch(e) {}
        applyCollapseState();
    });

    // Auto-refresh stat cards every 5 min (live state only)
    if (window.aipLiveState) {
        var ajaxurl = window.aipLiveState.ajaxurl;
        var nonce = window.aipLiveState.nonce;
        function refreshSummary() {
            $.post(ajaxurl, {action: 'orch_ai_crawler_summary', nonce: nonce})
             .done(function(resp) {
                 if (resp && resp.success && resp.data) {
                     var d = resp.data;
                     $('#aip-stat-hits-total').text((d.hits_total || 0).toLocaleString('it-IT'));
                     $('#aip-stat-bots-active').text(d.bots_active || 0);
                     var trend = d.trend_pct || 0;
                     var $trendEl = $('#aip-stat-trend');
                     $trendEl.text((trend >= 0 ? '+' : '') + trend + '%');
                     $trendEl.removeClass('aip-stat-card-trend--up aip-stat-card-trend--down aip-stat-card-trend--flat');
                     $trendEl.addClass(trend > 0 ? 'aip-stat-card-trend--up' : (trend < 0 ? 'aip-stat-card-trend--down' : 'aip-stat-card-trend--flat'));
                     $('#aip-stat-blocked').text(d.blocked_bypass || 0);
                 }
             });
        }
        setInterval(refreshSummary, 5 * 60 * 1000);
    }
})(jQuery);
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>

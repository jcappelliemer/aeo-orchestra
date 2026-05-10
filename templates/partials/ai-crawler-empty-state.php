<?php
/**
 * 3.35.84-beta — AI Performance empty state.
 * Rendered when hits_total === 0 (primo accesso, primi 24-72h tracking).
 */
if (!defined('ABSPATH')) exit;
$T = isset($T) ? $T : function($s) { return class_exists('SEO_AEO_T') ? SEO_AEO_T::t($s) : $s; };

// Days since first hit (or since plugin activation if no hit)
$days_since = 0;
if (!empty($aip_first_hit_at_str)) {
    $days_since = max(0, (int) floor((time() - strtotime($aip_first_hit_at_str)) / 86400));
}

// Profilo Business completion stats (read transient if available)
$bp_percent = 0;
$bp_confirmed = false;
$bp_stats = get_transient('seo_aeo_bp_dashboard_stats');
if (is_array($bp_stats)) {
    $bp_percent = (int) ($bp_stats['percent'] ?? 0);
    $bp_confirmed = !empty($bp_stats['confirmed']);
}

// Verify-Live URL
$vl_url = admin_url('admin.php?page=seo-aeo-native-output');

// Profilo Business URL
$bp_url = admin_url('admin.php?page=seo-aeo-business-profile');
?>

<div class="aip-empty">
    <div class="aip-empty-icon">📡</div>
    <div class="aip-empty-title">
        <?php
        if ($days_since === 0) {
            echo $T('Tracking AI bot attivo da poche ore');
        } elseif ($days_since === 1) {
            echo $T('Tracking AI bot attivo da 1 giorno');
        } else {
            echo sprintf($T('Tracking AI bot attivo da %d giorni'), $days_since);
        }
        ?>
    </div>
    <p style="color: var(--orch-muted, #475569); margin: 0 0 8px;"><?php echo $T('Nessun bot AI rilevato ancora.'); ?></p>
    <p style="color: var(--orch-muted, #475569); margin: 0 0 16px; font-size: 13px;">
        <?php echo $T('I dati AI bot iniziano ad apparire entro 24-72h dalla prima scansione (dipende dal traffico organico del tuo sito).'); ?>
    </p>

    <p style="color: var(--orch-ink, #0f172a); font-weight: 600; margin: 18px 0 6px;">
        <?php echo $T('Mentre attendi, ottimizza il tuo sito per essere scansionato:'); ?>
    </p>

    <div class="aip-empty-checklist">
        <div style="margin: 4px 0; color: #16a34a;">
            ✓ <?php echo $T('llms.txt nativo'); ?> <strong><?php echo $T('ATTIVO'); ?></strong>
        </div>
        <div style="margin: 4px 0; color: #16a34a;">
            ✓ <?php echo $T('Schema markup'); ?> <strong><?php echo $T('ATTIVO'); ?></strong>
        </div>
        <div style="margin: 4px 0; color: <?php echo $bp_confirmed ? '#16a34a' : '#d97706'; ?>;">
            <?php echo $bp_confirmed ? '✓' : '⚠'; ?>
            <?php echo $T('Profilo Business'); ?>
            <strong><?php echo $bp_percent; ?>%</strong>
            <?php if (!$bp_confirmed): ?>
                <a href="<?php echo esc_url($bp_url); ?>" style="margin-left: 6px; font-size: 12px;"><?php echo $T('Completa →'); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div style="margin-top: 22px;">
        <a href="<?php echo esc_url($vl_url); ?>" class="aip-empty-cta">
            ▶ <?php echo $T('Verifica come le AI vedono il tuo sito ora →'); ?>
        </a>
        <p style="font-size: 11px; color: var(--orch-muted, #475569); margin: 8px 0 0;">
            (<?php echo $T('Verify-Live · 5 crediti · risultato in ~30s'); ?>)
        </p>
    </div>
</div>

<div class="aip-phase2-footer">
    ℹ <?php echo $T('Phase 2 (Bing Webmaster Tools): citation count reale ChatGPT/Copilot/Bing Chat. Coming in v3.35.85.'); ?>
</div>

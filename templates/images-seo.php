<?php
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com. All rights reserved.
 *
 * Image SEO Manager template (3.34.0 → 3.35.4 hardening)
 * Audit + bulk fix metadata immagini con AI Vision.
 *
 * 3.35.2 changes:
 *  - Bug 1: bottone "AI fix" usa nuovo endpoint ai_fix_one (genera+applica server-side)
 *  - Bug 2: changelog post-bulk (banner sticky + modale before/after)
 *  - Bug 3: stat card cliccabili (filtro tabella + modale info traffic)
 *  - Bug 4: bulk preview flow (toggle apply-now vs review, modale con checkbox)
 *
 * 3.35.4 changes (cliente bug-fix):
 *  - Bug 1: AI fix singolo ora 2-step (preview -> review -> apply). Cliente: "nessuno fa modifiche alla cieca".
 *  - Bug 2: state.selected stale fix (clear su cancel modal + DOM-truth al click "Avvia bulk job").
 *  - Bug 3: bulk preview ora scala crediti correttamente (fix backend image_metadata.py user_id).
 */
if (!defined('ABSPATH')) exit;

$nonce = wp_create_nonce('seo_aeo_orchestra_nonce');
$ajaxurl = admin_url('admin-ajax.php');
$tt = function ($s) { return class_exists('SEO_AEO_T') ? SEO_AEO_T::t($s) : $s; };
?>
<?php ob_start(); ?>
/* Image SEO Manager (3.34.0) — coerente con brand Orchestra */
.iseo-wrap { padding: 20px; max-width: 1400px; }
.iseo-hero {
    background: linear-gradient(135deg, #0A0E27 0%, #0055FF 50%, #00E5FF 100%);
    color: #fff; border-radius: 16px; padding: 36px 40px;
    margin-bottom: 28px; box-shadow: 0 8px 32px rgba(0,85,255,.18);
    display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;
}
.iseo-hero h1 { color: #fff; margin: 0 0 8px 0; font-size: 28px; font-weight: 800; }
.iseo-hero p { margin: 0; color: rgba(255,255,255,.85); font-size: 15px; }

.iseo-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
.iseo-stat-card { background: #fff; border-radius: 12px; padding: 22px; border: 1px solid #E5E7EB;
    box-shadow: 0 2px 8px rgba(0,0,0,.04); cursor: pointer; transition: all .15s ease; position: relative; }
.iseo-stat-card:hover { background: #F9FAFB; box-shadow: 0 4px 14px rgba(0,85,255,.10); transform: translateY(-1px); }
.iseo-stat-card .hint { position: absolute; top: 10px; right: 12px; font-size: 11px; color: #9CA3AF; opacity: 0; transition: opacity .15s; }
.iseo-stat-card:hover .hint { opacity: 1; }
.iseo-stat-card.iseo-stat-active { border-color: #0055FF; box-shadow: 0 0 0 2px rgba(0,85,255,.18); }
.iseo-stat-card .label { font-size: 12px; text-transform: uppercase; letter-spacing: .8px; color: #6B7280; font-weight: 600; }
.iseo-stat-card .value { font-size: 32px; font-weight: 800; color: #0A0E27; line-height: 1.2; margin: 6px 0 4px; }
.iseo-stat-card .meta { font-size: 13px; color: #6B7280; }
.iseo-stat-card.iseo-stat-ok .value { color: #10B981; }
.iseo-stat-card.iseo-stat-warn .value { color: #F59E0B; }
.iseo-stat-card.iseo-stat-traffic .value { color: #0055FF; }

.iseo-toolbar { background: #fff; border-radius: 12px; padding: 16px; border: 1px solid #E5E7EB;
    margin-bottom: 16px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
.iseo-toolbar select, .iseo-toolbar input[type="text"] {
    padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 14px;
}
.iseo-toolbar input[type="text"] { min-width: 220px; }
.iseo-toolbar .iseo-spacer { flex: 1; }
.iseo-toolbar .iseo-selected-info { font-weight: 600; color: #374151; }

.iseo-btn {
    padding: 9px 16px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;
    border: 1px solid transparent; transition: all .15s ease; display: inline-flex; align-items: center; gap: 6px;
}
.iseo-btn-primary { background: linear-gradient(135deg, #0055FF, #00E5FF); color: #fff; }
.iseo-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,85,255,.35); }
.iseo-btn-primary:disabled { opacity: .55; cursor: not-allowed; transform: none; box-shadow: none; }
.iseo-btn-secondary { background: #F3F4F6; color: #1F2937; border-color: #D1D5DB; }
.iseo-btn-secondary:hover { background: #E5E7EB; }
.iseo-btn-danger { background: #DC2626; color: #fff; }
.iseo-btn-ghost { background: transparent; color: #0055FF; border-color: #0055FF; }
.iseo-btn-ghost:hover { background: #EEF4FF; }
.iseo-btn-sm { padding: 6px 10px; font-size: 12px; }

.iseo-table { width: 100%; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #E5E7EB;
    border-collapse: separate; border-spacing: 0; }
.iseo-table th, .iseo-table td { padding: 12px; text-align: left; vertical-align: middle; border-bottom: 1px solid #F3F4F6; }
.iseo-table th { background: #F9FAFB; font-weight: 700; font-size: 12px; text-transform: uppercase; color: #6B7280; }
.iseo-table tr:hover td { background: #F9FAFB; }
.iseo-table .iseo-thumb { width: 80px; height: 60px; object-fit: cover; border-radius: 6px; background: #F3F4F6; }
.iseo-table .iseo-meta-cell { font-size: 13px; color: #374151; }
.iseo-table .iseo-meta-cell .filename { font-weight: 600; color: #0A0E27; }
.iseo-table .iseo-meta-cell .parent { color: #6B7280; font-size: 12px; margin-top: 2px; }
.iseo-table .iseo-checks { display: flex; flex-wrap: wrap; gap: 6px; font-size: 11px; }
.iseo-check { padding: 2px 8px; border-radius: 99px; background: #FEE2E2; color: #991B1B; font-weight: 600; }
.iseo-check.ok { background: #D1FAE5; color: #065F46; }
.iseo-score-badge { display: inline-block; padding: 4px 12px; border-radius: 99px; font-weight: 700; font-size: 12px; }
.iseo-score-0 { background: #FEE2E2; color: #991B1B; }
.iseo-score-1 { background: #FED7AA; color: #9A3412; }
.iseo-score-2 { background: #FEF3C7; color: #92400E; }
.iseo-score-3 { background: #DBEAFE; color: #1E40AF; }
.iseo-score-4 { background: #D1FAE5; color: #065F46; }
.iseo-actions-cell { display: flex; flex-direction: column; gap: 6px; align-items: stretch; min-width: 130px; }

.iseo-pagination { display: flex; gap: 8px; justify-content: center; align-items: center;
    margin-top: 20px; padding: 12px; }
.iseo-pagination button { padding: 6px 12px; border: 1px solid #D1D5DB; background: #fff; border-radius: 8px;
    cursor: pointer; font-weight: 600; }
.iseo-pagination button.active { background: #0055FF; color: #fff; border-color: #0055FF; }
.iseo-pagination button:disabled { opacity: .4; cursor: not-allowed; }
.iseo-pagination .iseo-page-info { font-size: 13px; color: #6B7280; padding: 0 8px; }

/* Modal generico */
.iseo-modal-backdrop { position: fixed; inset: 0; background: rgba(10,14,39,.55); z-index: 100000;
    display: none; align-items: center; justify-content: center; padding: 20px; }
.iseo-modal-backdrop.show { display: flex; }
.iseo-modal { background: #fff; border-radius: 16px; padding: 28px; max-width: 900px; width: 100%;
    max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,.3); }
.iseo-modal h2 { margin: 0 0 20px; color: #0A0E27; font-size: 22px; font-weight: 800; }
.iseo-modal .iseo-modal-close { float: right; cursor: pointer; font-size: 24px; color: #9CA3AF; line-height: 1; }
.iseo-modal-grid { display: grid; grid-template-columns: 320px 1fr; gap: 24px; }
@media (max-width: 720px) { .iseo-modal-grid { grid-template-columns: 1fr; } }
.iseo-modal-img { width: 100%; height: auto; border-radius: 8px; background: #F3F4F6; max-height: 400px; object-fit: contain; }
.iseo-field-row { margin-bottom: 16px; }
.iseo-field-row label { display: block; font-weight: 600; color: #374151; margin-bottom: 6px; font-size: 13px; }
.iseo-field-row .iseo-counter { float: right; font-weight: 400; color: #9CA3AF; font-size: 12px; }
.iseo-field-row input, .iseo-field-row textarea { width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB;
    border-radius: 8px; font-size: 14px; font-family: inherit; box-sizing: border-box; }
.iseo-field-row textarea { min-height: 90px; resize: vertical; }
.iseo-suggested { display: none; margin-top: 6px; padding: 8px 12px; background: #EEF4FF;
    border-radius: 6px; font-size: 13px; color: #1E40AF; border-left: 3px solid #0055FF; }
.iseo-suggested.show { display: flex; gap: 8px; align-items: flex-start; justify-content: space-between; }
.iseo-suggested .text { flex: 1; }
.iseo-modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #F3F4F6; flex-wrap: wrap; }

/* Bulk progress */
.iseo-progress-bar { height: 28px; background: #F3F4F6; border-radius: 99px; overflow: hidden;
    position: relative; margin: 14px 0; }
.iseo-progress-fill { height: 100%; background: linear-gradient(135deg, #0055FF, #00E5FF); transition: width .3s ease; }
.iseo-progress-text { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    font-weight: 700; color: #0A0E27; font-size: 13px; }
.iseo-bulk-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; margin: 16px 0; }
.iseo-bulk-stat { background: #F9FAFB; padding: 10px; border-radius: 8px; text-align: center; }
.iseo-bulk-stat .lbl { font-size: 11px; color: #6B7280; text-transform: uppercase; }
.iseo-bulk-stat .val { font-size: 18px; font-weight: 800; color: #0A0E27; }

.iseo-empty { text-align: center; padding: 60px 20px; color: #6B7280; }
.iseo-empty .icon { font-size: 56px; margin-bottom: 12px; }
.iseo-spinner { display: inline-block; width: 14px; height: 14px; border: 2px solid #E5E7EB;
    border-top-color: #0055FF; border-radius: 50%; animation: iseo-spin .8s linear infinite; }
@keyframes iseo-spin { to { transform: rotate(360deg); } }

.iseo-banner { padding: 12px 16px; border-radius: 10px; margin: 12px 0; font-size: 14px; }
.iseo-banner-info { background: #EEF4FF; color: #1E40AF; border-left: 4px solid #0055FF; }
.iseo-banner-success { background: #D1FAE5; color: #065F46; border-left: 4px solid #10B981; }
.iseo-banner-warn { background: #FEF3C7; color: #92400E; border-left: 4px solid #F59E0B; }
.iseo-banner-error { background: #FEE2E2; color: #991B1B; border-left: 4px solid #DC2626; }

/* Sticky success banner post-bulk */
.iseo-bulk-done-banner { position: sticky; top: 36px; z-index: 50;
    background: linear-gradient(135deg, #10B981, #059669); color: #fff;
    border-radius: 12px; padding: 14px 18px; margin-bottom: 16px;
    display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
    box-shadow: 0 6px 18px rgba(16,185,129,.28); }
.iseo-bulk-done-banner .iseo-bdb-text { font-weight: 700; font-size: 15px; }
.iseo-bulk-done-banner .iseo-bdb-text small { display: block; font-weight: 400; font-size: 12px; opacity: .9; margin-top: 2px; }
.iseo-bulk-done-banner button { white-space: nowrap; }
.iseo-bulk-done-banner .iseo-btn-primary { background: #fff; color: #065F46; }
.iseo-bulk-done-banner .iseo-btn-primary:hover { background: #ECFDF5; }
.iseo-bulk-done-banner .iseo-btn-ghost { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,.55); }
.iseo-bulk-done-banner .iseo-btn-ghost:hover { background: rgba(255,255,255,.12); }

/* Diff table (changelog + preview) */
.iseo-diff-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.iseo-diff-table th, .iseo-diff-table td { padding: 8px 10px; text-align: left; vertical-align: top;
    border-bottom: 1px solid #F3F4F6; }
.iseo-diff-table th { background: #F9FAFB; font-size: 11px; text-transform: uppercase; color: #6B7280; font-weight: 700; }
.iseo-diff-table .iseo-diff-thumb { width: 56px; height: 42px; object-fit: cover; border-radius: 4px; }
.iseo-diff-cell-old { color: #9CA3AF; font-style: italic; max-width: 180px; word-break: break-word; }
.iseo-diff-cell-old.empty { color: #DC2626; font-style: normal; }
.iseo-diff-cell-new { color: #065F46; font-weight: 600; max-width: 220px; word-break: break-word; background: #ECFDF5; }
.iseo-diff-fields-tags { display: flex; flex-wrap: wrap; gap: 4px; }
.iseo-diff-tag { background: #DBEAFE; color: #1E40AF; padding: 2px 7px; border-radius: 99px; font-size: 11px; font-weight: 600; }

/* Preview modal table */
.iseo-preview-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.iseo-preview-table th, .iseo-preview-table td { padding: 8px 10px; text-align: left; vertical-align: top;
    border-bottom: 1px solid #F3F4F6; }
.iseo-preview-table th { background: #F9FAFB; font-size: 11px; text-transform: uppercase; color: #6B7280; font-weight: 700; position: sticky; top: 0; }
.iseo-preview-table tr.row-excluded { opacity: .45; background: #FAFAFA; }
.iseo-preview-table .iseo-pv-thumb { width: 50px; height: 38px; object-fit: cover; border-radius: 4px; }
.iseo-preview-table textarea, .iseo-preview-table input[type="text"] {
    width: 100%; border: 1px solid #E5E7EB; padding: 4px 6px; font-size: 12px; border-radius: 4px;
    font-family: inherit; box-sizing: border-box;
}
.iseo-preview-table textarea { min-height: 38px; resize: vertical; }
.iseo-preview-summary { background: #F9FAFB; padding: 10px 14px; border-radius: 8px; margin: 12px 0;
    display: flex; gap: 18px; align-items: center; font-size: 13px; color: #374151; }
.iseo-preview-summary strong { color: #0A0E27; }
.iseo-pv-controls { display: flex; gap: 8px; align-items: center; padding: 8px 0; border-bottom: 1px solid #F3F4F6; margin-bottom: 8px; }

/* Bulk mode toggle (preview vs apply) */
.iseo-mode-toggle { display: flex; gap: 8px; margin: 12px 0; }
.iseo-mode-card { flex: 1; padding: 12px; border: 2px solid #E5E7EB; border-radius: 10px; cursor: pointer; transition: all .15s ease; }
.iseo-mode-card:hover { border-color: #93C5FD; }
.iseo-mode-card.active { border-color: #0055FF; background: #EEF4FF; }
.iseo-mode-card .iseo-mode-title { font-weight: 700; color: #0A0E27; font-size: 13px; }
.iseo-mode-card .iseo-mode-desc { font-size: 12px; color: #6B7280; margin-top: 4px; line-height: 1.4; }
.iseo-mode-card input[type="radio"] { margin-right: 6px; }
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

<div class="wrap iseo-wrap">

    <div class="iseo-hero">
        <div>
            <h1>🖼 <?php echo esc_html($tt('Image SEO Manager')); ?></h1>
            <p><?php echo esc_html($tt('Audit + ottimizzazione metadata immagini con AI')); ?></p>
        </div>
        <div style="display:flex; gap:10px;">
            <button class="iseo-btn iseo-btn-secondary" id="iseo-export-csv">📊 <?php echo esc_html($tt('Esporta CSV')); ?></button>
            <button class="iseo-btn iseo-btn-primary" id="iseo-bulk-selected" disabled>⚡ <?php echo esc_html($tt('Bulk fix selezionati')); ?></button>
        </div>
    </div>

    <div class="iseo-stats" id="iseo-stats">
        <div class="iseo-stat-card" data-stat-filter="all" title="<?php echo esc_attr($tt('Mostra tutte')); ?>">
            <span class="hint">→ <?php echo esc_html($tt('Filtra')); ?></span>
            <div class="label"><?php echo esc_html($tt('Totale immagini')); ?></div>
            <div class="value" id="iseo-stat-total">—</div><div class="meta">&nbsp;</div></div>
        <div class="iseo-stat-card iseo-stat-ok" data-stat-filter="perfect" title="<?php echo esc_attr($tt('Mostra solo ottimizzate')); ?>">
            <span class="hint">→ <?php echo esc_html($tt('Filtra')); ?></span>
            <div class="label"><?php echo esc_html($tt('Ottimizzate')); ?></div>
            <div class="value" id="iseo-stat-ok">—</div><div class="meta" id="iseo-stat-ok-pct">&nbsp;</div></div>
        <div class="iseo-stat-card iseo-stat-warn" data-stat-filter="to_fix" title="<?php echo esc_attr($tt('Mostra solo da ottimizzare')); ?>">
            <span class="hint">→ <?php echo esc_html($tt('Filtra')); ?></span>
            <div class="label"><?php echo esc_html($tt('Da ottimizzare')); ?></div>
            <div class="value" id="iseo-stat-tofix">—</div><div class="meta">&nbsp;</div></div>
        <div class="iseo-stat-card iseo-stat-traffic" data-stat-info="traffic" title="<?php echo esc_attr($tt('Come è calcolata?')); ?>">
            <span class="hint">→ <?php echo esc_html($tt('Info')); ?></span>
            <div class="label"><?php echo esc_html($tt('Stima recovery traffic')); ?></div>
            <div class="value" id="iseo-stat-traffic">—</div><div class="meta"><?php echo esc_html($tt('Google Images, stima conservativa')); ?></div></div>
    </div>

    <div id="iseo-bulk-done-container"></div>

    <div class="iseo-toolbar">
        <label style="display:flex; align-items:center; gap:6px; font-size:14px;">
            <input type="checkbox" id="iseo-select-all"> <strong><?php echo esc_html($tt('Seleziona tutti')); ?></strong>
        </label>
        <select id="iseo-filter">
            <option value="all"><?php echo esc_html($tt('Tutti')); ?></option>
            <option value="to_fix" selected><?php echo esc_html($tt('Da ottimizzare')); ?></option>
            <option value="no_alt"><?php echo esc_html($tt('Senza alt')); ?></option>
            <option value="no_title"><?php echo esc_html($tt('Senza title')); ?></option>
            <option value="no_caption"><?php echo esc_html($tt('Senza caption')); ?></option>
            <option value="no_desc"><?php echo esc_html($tt('Senza description')); ?></option>
            <option value="perfect"><?php echo esc_html($tt('Ottimizzate')); ?></option>
        </select>
        <select id="iseo-sort">
            <option value="score_asc"><?php echo esc_html($tt('Score crescente')); ?></option>
            <option value="score_desc"><?php echo esc_html($tt('Score decrescente')); ?></option>
            <option value="newest"><?php echo esc_html($tt('Più recenti')); ?></option>
            <option value="oldest"><?php echo esc_html($tt('Più vecchie')); ?></option>
        </select>
        <input type="text" id="iseo-search" placeholder="<?php echo esc_attr($tt('Cerca per filename...')); ?>">
        <span class="iseo-spacer"></span>
        <span class="iseo-selected-info"><?php echo esc_html($tt('Selezionate')); ?>: <span id="iseo-selected-count">0</span> / <span id="iseo-filtered-count">0</span></span>
    </div>

    <div id="iseo-list-container">
        <div class="iseo-empty" id="iseo-loading"><div class="iseo-spinner"></div> <?php echo esc_html($tt('Caricamento...')); ?></div>
    </div>

    <div class="iseo-pagination" id="iseo-pagination" style="display:none;"></div>
</div>

<!-- Modal Edit -->
<div class="iseo-modal-backdrop" id="iseo-edit-modal">
    <div class="iseo-modal">
        <span class="iseo-modal-close" onclick="iseoCloseModal('iseo-edit-modal')">×</span>
        <h2>✏️ <?php echo esc_html($tt('Modifica metadata immagine')); ?></h2>
        <div class="iseo-modal-grid">
            <div>
                <img id="iseo-edit-img" class="iseo-modal-img" alt="">
                <div style="margin-top:10px; font-size:13px; color:#6B7280;">
                    <div><strong><?php echo esc_html($tt('Filename')); ?>:</strong> <span id="iseo-edit-filename"></span></div>
                    <div><strong><?php echo esc_html($tt('Dimensioni')); ?>:</strong> <span id="iseo-edit-size"></span></div>
                    <div><strong><?php echo esc_html($tt('Post associato')); ?>:</strong> <span id="iseo-edit-parent"></span></div>
                </div>
            </div>
            <div>
                <input type="hidden" id="iseo-edit-id">
                <div class="iseo-field-row">
                    <label><?php echo esc_html($tt('Alt text')); ?> <span class="iseo-counter"><span id="iseo-cnt-alt">0</span>/125</span></label>
                    <input type="text" id="iseo-f-alt" maxlength="125">
                    <div class="iseo-suggested" id="iseo-sug-alt">
                        <div class="text"><strong><?php echo esc_html($tt('Suggerito da AI')); ?>:</strong> <span class="ai-text"></span></div>
                        <button class="iseo-btn iseo-btn-ghost iseo-btn-sm" onclick="iseoUseSuggested('alt')"><?php echo esc_html($tt('Usa')); ?></button>
                    </div>
                </div>
                <div class="iseo-field-row">
                    <label><?php echo esc_html($tt('Title')); ?> <span class="iseo-counter"><span id="iseo-cnt-title">0</span>/60</span></label>
                    <input type="text" id="iseo-f-title" maxlength="60">
                    <div class="iseo-suggested" id="iseo-sug-title">
                        <div class="text"><strong><?php echo esc_html($tt('Suggerito da AI')); ?>:</strong> <span class="ai-text"></span></div>
                        <button class="iseo-btn iseo-btn-ghost iseo-btn-sm" onclick="iseoUseSuggested('title')"><?php echo esc_html($tt('Usa')); ?></button>
                    </div>
                </div>
                <div class="iseo-field-row">
                    <label><?php echo esc_html($tt('Caption')); ?> <span class="iseo-counter"><span id="iseo-cnt-caption">0</span>/200</span></label>
                    <input type="text" id="iseo-f-caption" maxlength="200">
                    <div class="iseo-suggested" id="iseo-sug-caption">
                        <div class="text"><strong><?php echo esc_html($tt('Suggerito da AI')); ?>:</strong> <span class="ai-text"></span></div>
                        <button class="iseo-btn iseo-btn-ghost iseo-btn-sm" onclick="iseoUseSuggested('caption')"><?php echo esc_html($tt('Usa')); ?></button>
                    </div>
                </div>
                <div class="iseo-field-row">
                    <label><?php echo esc_html($tt('Description')); ?> <span class="iseo-counter"><span id="iseo-cnt-description">0</span>/600</span></label>
                    <textarea id="iseo-f-description" maxlength="600"></textarea>
                    <div class="iseo-suggested" id="iseo-sug-description">
                        <div class="text"><strong><?php echo esc_html($tt('Suggerito da AI')); ?>:</strong> <span class="ai-text"></span></div>
                        <button class="iseo-btn iseo-btn-ghost iseo-btn-sm" onclick="iseoUseSuggested('description')"><?php echo esc_html($tt('Usa')); ?></button>
                    </div>
                </div>
                <div class="iseo-modal-actions">
                    <button class="iseo-btn iseo-btn-ghost" id="iseo-edit-genai">⚡ <?php echo esc_html($tt('Genera tutti con AI (2 cr)')); ?></button>
                    <button class="iseo-btn iseo-btn-secondary" onclick="iseoCloseModal('iseo-edit-modal')"><?php echo esc_html($tt('Annulla')); ?></button>
                    <button class="iseo-btn iseo-btn-primary" id="iseo-edit-save">💾 <?php echo esc_html($tt('Salva')); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Bulk Confirm -->
<div class="iseo-modal-backdrop" id="iseo-bulk-confirm">
    <div class="iseo-modal" style="max-width:620px;">
        <span class="iseo-modal-close" onclick="iseoCloseModal('iseo-bulk-confirm')">×</span>
        <h2>⚡ <?php echo esc_html($tt('Conferma bulk fix')); ?></h2>
        <div class="iseo-banner iseo-banner-info">
            <strong><?php echo esc_html($tt('Stai per generare metadata AI per')); ?> <span id="iseo-bulk-count">0</span> <?php echo esc_html($tt('immagini')); ?>.</strong><br>
            <?php echo esc_html($tt('Costo stimato')); ?>: <strong><span id="iseo-bulk-cost">0</span> <?php echo esc_html($tt('crediti')); ?></strong> (2 <?php echo esc_html($tt('per immagine')); ?>).<br>
            <?php echo esc_html($tt('Tempo stimato')); ?>: <strong><span id="iseo-bulk-time">0</span> <?php echo esc_html($tt('minuti')); ?></strong> (5 <?php echo esc_html($tt('immagini al minuto')); ?>).
        </div>

        <div class="iseo-mode-toggle">
            <label class="iseo-mode-card active" data-mode="preview">
                <input type="radio" name="iseo-bulk-mode" value="preview" checked>
                <span class="iseo-mode-title">👁 <?php echo esc_html($tt('Genera anteprima per review')); ?></span>
                <div class="iseo-mode-desc"><?php echo esc_html($tt('Genera tutto, poi ti mostro tabella per scegliere cosa applicare. Consigliato.')); ?></div>
            </label>
            <label class="iseo-mode-card" data-mode="apply">
                <input type="radio" name="iseo-bulk-mode" value="apply">
                <span class="iseo-mode-title">⚡ <?php echo esc_html($tt('Genera + applica subito')); ?></span>
                <div class="iseo-mode-desc"><?php echo esc_html($tt('Applica direttamente al sito i suggerimenti AI (modalità rapida).')); ?></div>
            </label>
        </div>

        <div class="iseo-banner iseo-banner-warn">
            🛡️ <?php echo esc_html($tt('Idempotency: il bulk preserva valori esistenti settati manualmente. Solo i campi VUOTI verranno popolati.')); ?>
        </div>
        <div class="iseo-modal-actions">
            <button class="iseo-btn iseo-btn-secondary" onclick="iseoCloseModal('iseo-bulk-confirm')"><?php echo esc_html($tt('Annulla')); ?></button>
            <button class="iseo-btn iseo-btn-primary" id="iseo-bulk-confirm-go">🚀 <?php echo esc_html($tt('Avvia bulk job')); ?></button>
        </div>
    </div>
</div>

<!-- Modal Changelog (Bug 2) -->
<div class="iseo-modal-backdrop" id="iseo-changelog-modal">
    <div class="iseo-modal" style="max-width:1100px;">
        <span class="iseo-modal-close" onclick="iseoCloseModal('iseo-changelog-modal')">×</span>
        <h2>📋 <?php echo esc_html($tt('Cosa è cambiato')); ?></h2>
        <div id="iseo-changelog-summary" class="iseo-banner iseo-banner-success" style="margin-bottom:12px;"></div>
        <div id="iseo-changelog-body" style="max-height:60vh; overflow-y:auto;"></div>
        <div class="iseo-modal-actions">
            <button class="iseo-btn iseo-btn-secondary" onclick="iseoCloseModal('iseo-changelog-modal')"><?php echo esc_html($tt('Chiudi')); ?></button>
        </div>
    </div>
</div>

<!-- Modal Traffic Info (Bug 3) -->
<div class="iseo-modal-backdrop" id="iseo-traffic-info-modal">
    <div class="iseo-modal" style="max-width:560px;">
        <span class="iseo-modal-close" onclick="iseoCloseModal('iseo-traffic-info-modal')">×</span>
        <h2>📈 <?php echo esc_html($tt('Come è calcolata la stima traffic')); ?></h2>
        <p style="line-height:1.6; color:#374151;"><?php echo esc_html($tt('La stima è basata sulla copertura: percentuale di immagini con metadata mancanti × range di recupero atteso.')); ?></p>
        <div class="iseo-banner iseo-banner-info" style="font-family:ui-monospace,monospace; font-size:13px;">
            recovery_low % &nbsp;=&nbsp; (<?php echo esc_html($tt('da fixare')); ?> / <?php echo esc_html($tt('totale')); ?>) &times; 10<br>
            recovery_high % &nbsp;=&nbsp; (<?php echo esc_html($tt('da fixare')); ?> / <?php echo esc_html($tt('totale')); ?>) &times; 25
        </div>
        <p style="font-size:13px; color:#6B7280; line-height:1.6;"><?php echo esc_html($tt('Benchmark: studi su Google Images mostrano che pagine con alt text completo ricevono 10–25% più traffico organico immagini rispetto a quelle senza.')); ?></p>
        <p style="font-size:13px; color:#6B7280; line-height:1.6;"><?php echo esc_html($tt('La stima è conservativa e per il proprio sito (non aggregata): il valore reale dipende da volume keyword, autorità del dominio e click-through rate. Considera la stima come limite superiore raggiungibile, non garantito.')); ?></p>
        <div class="iseo-modal-actions">
            <button class="iseo-btn iseo-btn-primary" onclick="iseoCloseModal('iseo-traffic-info-modal')"><?php echo esc_html($tt('Ho capito')); ?></button>
        </div>
    </div>
</div>

<!-- Modal Bulk Preview Review (Bug 4) -->
<div class="iseo-modal-backdrop" id="iseo-preview-modal">
    <div class="iseo-modal" style="max-width:1200px;">
        <span class="iseo-modal-close" onclick="iseoCloseModal('iseo-preview-modal')">×</span>
        <h2>👁 <?php echo esc_html($tt('Anteprima bulk fix')); ?></h2>
        <div class="iseo-preview-summary" id="iseo-pv-summary">
            <span><strong><span id="iseo-pv-total">0</span></strong> <?php echo esc_html($tt('immagini elaborate')); ?></span>
            <span>• <strong style="color:#10B981;"><span id="iseo-pv-selected">0</span></strong> <?php echo esc_html($tt('selezionati per applicare')); ?></span>
            <span>• <span style="color:#9CA3AF;"><span id="iseo-pv-excluded">0</span> <?php echo esc_html($tt('esclusi')); ?></span></span>
        </div>
        <div class="iseo-pv-controls">
            <button class="iseo-btn iseo-btn-secondary iseo-btn-sm" id="iseo-pv-select-all">☑ <?php echo esc_html($tt('Seleziona tutti')); ?></button>
            <button class="iseo-btn iseo-btn-secondary iseo-btn-sm" id="iseo-pv-deselect-all">☐ <?php echo esc_html($tt('Deseleziona tutti')); ?></button>
            <span style="flex:1;"></span>
            <label style="font-size:13px; color:#6B7280;">
                <input type="checkbox" id="iseo-pv-force-overwrite"> <?php echo esc_html($tt('Sovrascrivi anche valori esistenti')); ?>
            </label>
        </div>
        <div id="iseo-pv-body" style="max-height:55vh; overflow-y:auto; border:1px solid #E5E7EB; border-radius:8px;"></div>
        <div class="iseo-modal-actions">
            <button class="iseo-btn iseo-btn-secondary" id="iseo-pv-discard"><?php echo esc_html($tt('Annulla anteprima')); ?></button>
            <button class="iseo-btn iseo-btn-primary" id="iseo-pv-apply">✓ <?php echo esc_html($tt('Applica selezionati')); ?> (<span id="iseo-pv-apply-count">0</span>)</button>
        </div>
    </div>
</div>

<!-- Modal AI Fix Preview (3.35.4 — Bug 1: review prima di applicare) -->
<div class="iseo-modal-backdrop" id="iseo-aifix-preview-modal">
    <div class="iseo-modal" style="max-width:920px;">
        <span class="iseo-modal-close" onclick="iseoCloseModal('iseo-aifix-preview-modal')">×</span>
        <h2>👁 <?php echo esc_html($tt('Anteprima modifiche AI')); ?></h2>
        <div class="iseo-banner iseo-banner-info" style="margin-bottom:12px;">
            <strong><?php echo esc_html($tt('Rivedi e conferma:')); ?></strong>
            <?php echo esc_html($tt('I crediti sono stati scalati per la generazione. Scegli quali campi applicare al sito (puoi anche modificarli prima).')); ?>
        </div>
        <div id="iseo-aifix-pv-meta" style="font-size:13px; color:#6B7280; margin-bottom:10px;"></div>
        <div id="iseo-aifix-pv-table" style="max-height:55vh; overflow-y:auto;"></div>
        <div class="iseo-modal-actions">
            <button class="iseo-btn iseo-btn-secondary" onclick="iseoCloseModal('iseo-aifix-preview-modal')"><?php echo esc_html($tt('Annulla')); ?></button>
            <button class="iseo-btn iseo-btn-primary" id="iseo-aifix-pv-apply">✓ <?php echo esc_html($tt('Applica selezionati')); ?> (<span id="iseo-aifix-pv-count">0</span>)</button>
        </div>
    </div>
</div>

<!-- Modal Bulk Progress -->
<div class="iseo-modal-backdrop" id="iseo-bulk-progress">
    <div class="iseo-modal" style="max-width:600px;">
        <h2>⏳ <?php echo esc_html($tt('Bulk fix in corso')); ?>...</h2>
        <div class="iseo-progress-bar">
            <div class="iseo-progress-fill" id="iseo-bp-fill" style="width:0%"></div>
            <div class="iseo-progress-text" id="iseo-bp-text">0/0</div>
        </div>
        <div class="iseo-bulk-stats">
            <div class="iseo-bulk-stat"><div class="lbl"><?php echo esc_html($tt('Processate')); ?></div><div class="val" id="iseo-bp-done">0</div></div>
            <div class="iseo-bulk-stat"><div class="lbl"><?php echo esc_html($tt('Crediti usati')); ?></div><div class="val" id="iseo-bp-credits">0</div></div>
            <div class="iseo-bulk-stat"><div class="lbl"><?php echo esc_html($tt('Errori')); ?></div><div class="val" id="iseo-bp-errors">0</div></div>
            <div class="iseo-bulk-stat"><div class="lbl"><?php echo esc_html($tt('Stima rimanente')); ?></div><div class="val" id="iseo-bp-eta">—</div></div>
        </div>
        <div id="iseo-bp-banner"></div>
        <div class="iseo-modal-actions">
            <button class="iseo-btn iseo-btn-danger" id="iseo-bp-cancel"><?php echo esc_html($tt('Annulla bulk')); ?></button>
            <button class="iseo-btn iseo-btn-secondary" id="iseo-bp-close" style="display:none;"><?php echo esc_html($tt('Chiudi')); ?></button>
        </div>
    </div>
</div>

<?php ob_start(); ?>
(function(){
    const ajaxurl = <?php echo wp_json_encode($ajaxurl); ?>;
    const nonce = <?php echo wp_json_encode($nonce); ?>;
    const T = {
        loading: <?php echo wp_json_encode($tt('Caricamento...')); ?>,
        no_images: <?php echo wp_json_encode($tt('Nessuna immagine trovata.')); ?>,
        ai_fix: <?php echo wp_json_encode($tt('AI fix')); ?>,
        edit: <?php echo wp_json_encode($tt('Edit')); ?>,
        score: <?php echo wp_json_encode($tt('Score')); ?>,
        post: <?php echo wp_json_encode($tt('Post associato')); ?>,
        prev: <?php echo wp_json_encode($tt('Precedente')); ?>,
        next: <?php echo wp_json_encode($tt('Successiva')); ?>,
        page: <?php echo wp_json_encode($tt('Pagina')); ?>,
        of: <?php echo wp_json_encode($tt('di')); ?>,
        ai_generating: <?php echo wp_json_encode($tt('Generazione AI in corso...')); ?>,
        save_ok: <?php echo wp_json_encode($tt('Salvato.')); ?>,
        ai_fail: <?php echo wp_json_encode($tt('Errore AI:')); ?>,
        confirm_cancel: <?php echo wp_json_encode($tt('Annullare il bulk job in corso?')); ?>,
        bulk_done: <?php echo wp_json_encode($tt('Bulk fix completato!')); ?>,
        bulk_running_warn: <?php echo wp_json_encode($tt('Bulk job già in esecuzione.')); ?>,
        none_selected: <?php echo wp_json_encode($tt('Nessuna immagine selezionata.')); ?>,
        ai_use: <?php echo wp_json_encode($tt('Usa')); ?>,
        not_set: <?php echo wp_json_encode($tt('non assegnato')); ?>,
        unknown: <?php echo wp_json_encode($tt('sconosciuto')); ?>,
        already_complete: <?php echo wp_json_encode($tt('Immagine già completa, nessuna modifica necessaria.')); ?>,
        ai_applied: <?php echo wp_json_encode($tt('Modifiche applicate')); ?>,
        bulk_done_apply: <?php echo wp_json_encode($tt('Bulk fix completato:')); ?>,
        bulk_done_preview: <?php echo wp_json_encode($tt('Anteprima generata:')); ?>,
        view_changelog: <?php echo wp_json_encode($tt('Vedi cosa è cambiato')); ?>,
        view_preview: <?php echo wp_json_encode($tt('Rivedi e applica')); ?>,
        no_changes: <?php echo wp_json_encode($tt('Nessuna modifica registrata.')); ?>,
        before: <?php echo wp_json_encode($tt('Prima')); ?>,
        after: <?php echo wp_json_encode($tt('Dopo')); ?>,
        empty_field: <?php echo wp_json_encode($tt('vuoto')); ?>,
        fields_changed: <?php echo wp_json_encode($tt('Campi aggiornati')); ?>,
        confirm_discard_preview: <?php echo wp_json_encode($tt('Scartare l\'anteprima generata? I crediti spesi non saranno rimborsati.')); ?>,
        none_to_apply: <?php echo wp_json_encode($tt('Seleziona almeno una immagine.')); ?>,
        applying: <?php echo wp_json_encode($tt('Applicazione in corso...')); ?>,
        applied_count: <?php echo wp_json_encode($tt('Applicate:')); ?>,
        skipped: <?php echo wp_json_encode($tt('saltate (campi già pieni)')); ?>,
        dismiss: <?php echo wp_json_encode($tt('Chiudi')); ?>,
        // 3.35.4 — AI fix preview modal
        aifix_pv_loading: <?php echo wp_json_encode($tt('Generazione AI in corso...')); ?>,
        aifix_pv_field: <?php echo wp_json_encode($tt('Campo')); ?>,
        aifix_pv_current: <?php echo wp_json_encode($tt('Valore attuale')); ?>,
        aifix_pv_suggested: <?php echo wp_json_encode($tt('Valore AI (modificabile)')); ?>,
        aifix_pv_apply_col: <?php echo wp_json_encode($tt('Applica')); ?>,
        aifix_pv_empty_field: <?php echo wp_json_encode($tt('(vuoto)')); ?>,
        aifix_pv_no_changes: <?php echo wp_json_encode($tt('L\'AI non ha proposto modifiche utili.')); ?>,
        aifix_pv_score_meta: <?php echo wp_json_encode($tt('Score: attuale')); ?>,
        aifix_pv_score_after: <?php echo wp_json_encode($tt('stimato dopo apply')); ?>,
        aifix_pv_credits_used: <?php echo wp_json_encode($tt('Crediti usati per la generazione')); ?>,
        aifix_pv_none_selected: <?php echo wp_json_encode($tt('Seleziona almeno un campo da applicare.')); ?>,
        aifix_apply_ok: <?php echo wp_json_encode($tt('Modifiche applicate.')); ?>,
    };

    let state = {
        page: 1,
        per_page: 20,
        filter: 'to_fix',
        sort: 'score_asc',
        search: '',
        items: [],
        totalPages: 1,
        filtered: 0,
        selected: new Set(),
        currentEditItem: null,
        currentSuggested: null,
        bulkPolling: null,
        bulkMode: 'preview',          // 'preview' | 'apply'
        lastBulkMode: null,
        previewItems: [],             // staged preview entries dal transient
        previewExcluded: new Set(),   // attach_ids esclusi dal commit
        previewEdited: {},            // {attach_id: {alt,title,caption,description}} overrides
        bulkBannerShown: false,
    };

    function escapeHtml(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    }

    function postAjax(action, data) {
        const fd = new FormData();
        fd.append('action', action);
        fd.append('nonce', nonce);
        for (const k in data) {
            if (Array.isArray(data[k])) {
                fd.append(k, JSON.stringify(data[k]));
            } else {
                fd.append(k, data[k]);
            }
        }
        return fetch(ajaxurl, { method: 'POST', credentials: 'same-origin', body: fd })
            .then(r => r.json())
            .catch(e => ({ error: 'network_error', message: String(e) }));
    }

    function loadList() {
        document.getElementById('iseo-list-container').innerHTML =
            '<div class="iseo-empty"><div class="iseo-spinner"></div> ' + escapeHtml(T.loading) + '</div>';
        document.getElementById('iseo-pagination').style.display = 'none';
        postAjax('seo_aeo_orchestra_image_seo_audit', {
            page: state.page,
            per_page: state.per_page,
            filter: state.filter,
            sort: state.sort,
            search: state.search,
        }).then(resp => {
            if (!resp || !resp.success) {
                document.getElementById('iseo-list-container').innerHTML =
                    '<div class="iseo-banner iseo-banner-error">' + escapeHtml((resp && (resp.error || resp.message)) || 'Errore caricamento') + '</div>';
                return;
            }
            state.items = resp.items || [];
            state.totalPages = resp.pages || 1;
            state.filtered = resp.filtered || 0;
            renderStats(resp.stats);
            renderList();
            renderPagination();
            document.getElementById('iseo-filtered-count').textContent = resp.filtered || 0;
            updateSelectedCount();
        });
    }

    function renderStats(s) {
        if (!s) return;
        document.getElementById('iseo-stat-total').textContent = s.total;
        document.getElementById('iseo-stat-ok').textContent = s.ok;
        document.getElementById('iseo-stat-ok-pct').textContent = s.score_pct + '%';
        document.getElementById('iseo-stat-tofix').textContent = s.to_fix;
        const tr = (s.traffic_low || 0) + '–' + (s.traffic_high || 0) + '%';
        document.getElementById('iseo-stat-traffic').textContent = '+' + tr;
    }

    function renderList() {
        const c = document.getElementById('iseo-list-container');
        if (!state.items.length) {
            c.innerHTML = '<div class="iseo-empty"><div class="icon">🖼</div>' + escapeHtml(T.no_images) + '</div>';
            return;
        }
        let html = '<table class="iseo-table"><thead><tr>';
        html += '<th style="width:40px;"><input type="checkbox" id="iseo-h-checkall"></th>';
        html += '<th><?php echo esc_html__('Immagine'); ?></th>';
        html += '<th><?php echo esc_html__('Metadata'); ?></th>';
        html += '<th><?php echo esc_html__('Score'); ?></th>';
        html += '<th><?php echo esc_html__('Azioni'); ?></th>';
        html += '</tr></thead><tbody>';
        state.items.forEach(it => {
            const cls = 'iseo-score-' + it.score;
            const checks = [
                { f: 'alt',         ok: !!it.alt },
                { f: 'title',       ok: !!it.title },
                { f: 'caption',     ok: !!it.caption },
                { f: 'description', ok: !!it.description },
            ];
            const sel = state.selected.has(it.id) ? 'checked' : '';
            const parentInfo = it.parent_title
                ? '<div class="parent">📄 ' + escapeHtml(it.parent_title) + ' (' + escapeHtml(it.parent_type || '') + ')</div>'
                : '<div class="parent">📄 ' + escapeHtml(T.not_set) + '</div>';
            html += '<tr data-id="' + it.id + '">'
                + '<td><input type="checkbox" class="iseo-row-cb" data-id="' + it.id + '" ' + sel + '></td>'
                + '<td><img class="iseo-thumb" src="' + escapeHtml(it.thumb_url || it.url) + '" loading="lazy" alt=""></td>'
                + '<td class="iseo-meta-cell"><div class="filename">' + escapeHtml(it.filename) + '</div>'
                + parentInfo
                + '<div class="iseo-checks" style="margin-top:6px;">'
                + checks.map(c => '<span class="iseo-check ' + (c.ok ? 'ok' : '') + '">' + (c.ok ? '✓' : '✗') + ' ' + c.f + '</span>').join('')
                + '</div></td>'
                + '<td><span class="iseo-score-badge ' + cls + '">' + it.score + '/4</span></td>'
                + '<td><div class="iseo-actions-cell">'
                + '<button class="iseo-btn iseo-btn-primary iseo-btn-sm iseo-row-aifix" data-id="' + it.id + '">⚡ ' + escapeHtml(T.ai_fix) + '</button>'
                + '<button class="iseo-btn iseo-btn-secondary iseo-btn-sm iseo-row-edit" data-id="' + it.id + '">✏️ ' + escapeHtml(T.edit) + '</button>'
                + '</div></td>'
                + '</tr>';
        });
        html += '</tbody></table>';
        c.innerHTML = html;

        // Attach handlers
        c.querySelectorAll('.iseo-row-cb').forEach(cb => {
            cb.addEventListener('change', e => {
                const id = parseInt(e.target.getAttribute('data-id'), 10);
                if (e.target.checked) state.selected.add(id);
                else state.selected.delete(id);
                updateSelectedCount();
            });
        });
        c.querySelectorAll('.iseo-row-edit').forEach(b => {
            b.addEventListener('click', e => openEditModal(parseInt(e.currentTarget.getAttribute('data-id'), 10)));
        });
        c.querySelectorAll('.iseo-row-aifix').forEach(b => {
            b.addEventListener('click', e => quickAIFix(parseInt(e.currentTarget.getAttribute('data-id'), 10), e.currentTarget));
        });
        const cb = document.getElementById('iseo-h-checkall');
        if (cb) cb.addEventListener('change', e => {
            // BUG 2 FIX: header checkbox "deseleziona tutto" clears ENTIRE state.selected (cross-page),
            // not just current-page IDs (che lasciava residui da pagine precedenti).
            const checked = e.target.checked;
            if (checked) {
                state.items.forEach(it => state.selected.add(it.id));
            } else {
                state.selected.clear();
            }
            renderList();
            updateSelectedCount();
        });
    }

    function renderPagination() {
        const p = document.getElementById('iseo-pagination');
        if (state.totalPages <= 1) { p.style.display = 'none'; return; }
        p.style.display = 'flex';
        const cur = state.page;
        const total = state.totalPages;
        let html = '<button id="iseo-pg-prev"' + (cur === 1 ? ' disabled' : '') + '>← ' + escapeHtml(T.prev) + '</button>';
        html += '<span class="iseo-page-info">' + escapeHtml(T.page) + ' ' + cur + ' ' + escapeHtml(T.of) + ' ' + total + '</span>';
        html += '<button id="iseo-pg-next"' + (cur === total ? ' disabled' : '') + '>' + escapeHtml(T.next) + ' →</button>';
        p.innerHTML = html;
        const prev = document.getElementById('iseo-pg-prev');
        const next = document.getElementById('iseo-pg-next');
        if (prev) prev.addEventListener('click', () => { if (cur > 1) { state.page = cur - 1; loadList(); } });
        if (next) next.addEventListener('click', () => { if (cur < total) { state.page = cur + 1; loadList(); } });
    }

    function updateSelectedCount() {
        document.getElementById('iseo-selected-count').textContent = state.selected.size;
        document.getElementById('iseo-bulk-selected').disabled = state.selected.size === 0;
    }

    function openEditModal(id) {
        const it = state.items.find(x => x.id === id);
        if (!it) return;
        state.currentEditItem = it;
        state.currentSuggested = null;
        document.getElementById('iseo-edit-id').value = it.id;
        document.getElementById('iseo-edit-img').src = it.url;
        document.getElementById('iseo-edit-filename').textContent = it.filename;
        document.getElementById('iseo-edit-size').textContent = (it.width && it.height) ? (it.width + '×' + it.height) : T.unknown;
        document.getElementById('iseo-edit-parent').textContent = it.parent_title || T.not_set;
        document.getElementById('iseo-f-alt').value = it.alt || '';
        document.getElementById('iseo-f-title').value = it.title || '';
        document.getElementById('iseo-f-caption').value = it.caption || '';
        document.getElementById('iseo-f-description').value = it.description || '';
        ['alt','title','caption','description'].forEach(f => {
            document.getElementById('iseo-cnt-' + f).textContent = (document.getElementById('iseo-f-' + f).value || '').length;
            document.getElementById('iseo-sug-' + f).classList.remove('show');
        });
        document.getElementById('iseo-edit-modal').classList.add('show');
    }

    window.iseoCloseModal = function(id) {
        document.getElementById(id).classList.remove('show');
    };

    window.iseoUseSuggested = function(field) {
        if (!state.currentSuggested) return;
        const v = state.currentSuggested[field] || '';
        const inp = document.getElementById('iseo-f-' + field);
        if (inp) {
            inp.value = v;
            document.getElementById('iseo-cnt-' + field).textContent = v.length;
        }
    };

    function bindCounters() {
        ['alt','title','caption','description'].forEach(f => {
            const inp = document.getElementById('iseo-f-' + f);
            if (inp) inp.addEventListener('input', () => {
                document.getElementById('iseo-cnt-' + f).textContent = inp.value.length;
            });
        });
    }
    bindCounters();

    document.getElementById('iseo-edit-genai').addEventListener('click', () => {
        const it = state.currentEditItem;
        if (!it) return;
        const btn = document.getElementById('iseo-edit-genai');
        btn.disabled = true;
        btn.innerHTML = '<span class="iseo-spinner"></span> ' + escapeHtml(T.ai_generating);
        postAjax('seo_aeo_orchestra_image_seo_generate_one', { attach_id: it.id }).then(resp => {
            btn.disabled = false;
            btn.innerHTML = '⚡ <?php echo esc_js($tt('Genera tutti con AI (2 cr)')); ?>';
            if (!resp || !resp.success) {
                const msg = (resp && (resp.detail || resp.message || resp.error)) || 'AI error';
                alert(T.ai_fail + ' ' + msg);
                return;
            }
            const md = resp.metadata || {};
            state.currentSuggested = md;
            ['alt','title','caption','description'].forEach(f => {
                const sug = document.getElementById('iseo-sug-' + f);
                if (md[f]) {
                    sug.querySelector('.ai-text').textContent = md[f];
                    sug.classList.add('show');
                }
            });
        });
    });

    document.getElementById('iseo-edit-save').addEventListener('click', () => {
        const it = state.currentEditItem;
        if (!it) return;
        const data = {
            attach_id:   it.id,
            alt:         document.getElementById('iseo-f-alt').value,
            title:       document.getElementById('iseo-f-title').value,
            caption:     document.getElementById('iseo-f-caption').value,
            description: document.getElementById('iseo-f-description').value,
        };
        const btn = document.getElementById('iseo-edit-save');
        btn.disabled = true;
        postAjax('seo_aeo_orchestra_image_seo_save_one', data).then(resp => {
            btn.disabled = false;
            if (resp && resp.success) {
                window.iseoCloseModal('iseo-edit-modal');
                loadList();
            } else {
                alert((resp && (resp.error || resp.message)) || 'Errore salvataggio');
            }
        });
    });

    /**
     * BUG 1 FIX (3.35.4): AI fix 2-step (preview + apply).
     * Cliente: "nessuno fa modifiche alla cieca". Mostra modal con before/after editabile + checkbox per campo.
     * STEP 1: chiama backend, scala crediti, ritorna suggested → render modal review.
     * STEP 2: utente conferma campi → AJAX apply (no extra credits).
     */
    let aifixPreviewState = { id: null, before: null, suggested: null, score_current: 0, score_estimated: 0, credits_used: 0 };

    function quickAIFix(id, btn) {
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="iseo-spinner"></span>';
        postAjax('seo_aeo_orchestra_image_seo_ai_fix_preview', { attach_id: id }).then(resp => {
            btn.disabled = false;
            btn.innerHTML = orig;
            if (!resp || !resp.success) {
                alert(T.ai_fail + ' ' + ((resp && (resp.detail || resp.message || resp.error)) || ''));
                return;
            }
            if (resp.skipped) {
                // already complete — visual feedback ma niente reload
                btn.innerHTML = '✓';
                setTimeout(() => { btn.innerHTML = orig; }, 1600);
                return;
            }
            // Open preview modal con before/after
            aifixPreviewState = {
                id: id,
                before: resp.before || {},
                suggested: resp.suggested || {},
                score_current: resp.score_current || 0,
                score_estimated: resp.score_estimated || 0,
                credits_used: resp.credits_used || 2,
            };
            renderAifixPreview();
            document.getElementById('iseo-aifix-preview-modal').classList.add('show');
        });
    }

    function renderAifixPreview() {
        const meta = document.getElementById('iseo-aifix-pv-meta');
        meta.innerHTML = '<span>' + escapeHtml(T.aifix_pv_score_meta) + ': <strong>' + aifixPreviewState.score_current + '/4</strong></span>'
            + ' &nbsp;→&nbsp; '
            + '<span>' + escapeHtml(T.aifix_pv_score_after) + ': <strong style="color:#10B981;">' + aifixPreviewState.score_estimated + '/4</strong></span>'
            + ' &nbsp;•&nbsp; '
            + '<span style="color:#6B7280;">' + escapeHtml(T.aifix_pv_credits_used) + ': <strong>' + aifixPreviewState.credits_used + '</strong></span>';
        const before = aifixPreviewState.before || {};
        const sug = aifixPreviewState.suggested || {};
        const fields = [
            { key: 'alt',         max: 125 },
            { key: 'title',       max: 60 },
            { key: 'caption',     max: 200 },
            { key: 'description', max: 600 },
        ];
        // Helper: per ogni campo, rileva se title è "auto-filename" (consider as empty per idempotency)
        let html = '<table class="iseo-aifix-pv-table" style="width:100%; border-collapse:collapse;">'
            + '<thead><tr style="background:#F9FAFB;">'
            + '<th style="text-align:left; padding:8px 10px; border-bottom:1px solid #E5E7EB; width:48px;">' + escapeHtml(T.aifix_pv_apply_col) + '</th>'
            + '<th style="text-align:left; padding:8px 10px; border-bottom:1px solid #E5E7EB; width:90px;">' + escapeHtml(T.aifix_pv_field) + '</th>'
            + '<th style="text-align:left; padding:8px 10px; border-bottom:1px solid #E5E7EB;">' + escapeHtml(T.aifix_pv_current) + '</th>'
            + '<th style="text-align:left; padding:8px 10px; border-bottom:1px solid #E5E7EB;">' + escapeHtml(T.aifix_pv_suggested) + '</th>'
            + '</tr></thead><tbody>';
        let anyDiff = false;
        fields.forEach(f => {
            const cur = (before[f.key] || '').trim();
            const aiV = (sug[f.key] || '').trim();
            // Default checked: se valore AI presente E (cur vuoto OR cur != aiV)
            const defaultCheck = (aiV !== '' && (cur === '' || cur !== aiV));
            if (defaultCheck) anyDiff = true;
            const inpTag = (f.key === 'description')
                ? '<textarea class="iseo-aifix-pv-input" data-field="' + f.key + '" maxlength="' + f.max + '" style="width:100%; min-height:60px; font-size:13px; padding:6px 8px; border:1px solid #D1D5DB; border-radius:6px;">' + escapeHtml(aiV) + '</textarea>'
                : '<input class="iseo-aifix-pv-input" data-field="' + f.key + '" type="text" maxlength="' + f.max + '" value="' + escapeHtml(aiV) + '" style="width:100%; font-size:13px; padding:6px 8px; border:1px solid #D1D5DB; border-radius:6px;">';
            html += '<tr style="border-bottom:1px solid #F3F4F6;">'
                + '<td style="padding:10px;"><input type="checkbox" class="iseo-aifix-pv-cb" data-field="' + f.key + '" ' + (defaultCheck ? 'checked' : '') + '></td>'
                + '<td style="padding:10px; font-weight:600; text-transform:uppercase; font-size:11px; color:#374151;">' + escapeHtml(f.key) + '</td>'
                + '<td style="padding:10px; vertical-align:top;">'
                + (cur === '' ? '<em style="color:#9CA3AF;">' + escapeHtml(T.aifix_pv_empty_field) + '</em>'
                    : '<span style="color:#6B7280; font-size:13px;">' + escapeHtml(cur) + '</span>')
                + '</td>'
                + '<td style="padding:10px; vertical-align:top;">'
                + (aiV === '' ? '<em style="color:#9CA3AF;">' + escapeHtml(T.aifix_pv_empty_field) + '</em>' : inpTag)
                + '</td>'
                + '</tr>';
        });
        html += '</tbody></table>';
        if (!anyDiff) {
            html = '<div class="iseo-banner iseo-banner-info">' + escapeHtml(T.aifix_pv_no_changes) + '</div>' + html;
        }
        document.getElementById('iseo-aifix-pv-table').innerHTML = html;
        // Bind change for checkboxes to update count
        document.querySelectorAll('.iseo-aifix-pv-cb').forEach(cb => {
            cb.addEventListener('change', updateAifixCount);
        });
        updateAifixCount();
    }

    function updateAifixCount() {
        const n = document.querySelectorAll('.iseo-aifix-pv-cb:checked').length;
        document.getElementById('iseo-aifix-pv-count').textContent = n;
        document.getElementById('iseo-aifix-pv-apply').disabled = (n === 0);
    }

    document.getElementById('iseo-aifix-pv-apply').addEventListener('click', () => {
        if (!aifixPreviewState.id) return;
        const data = { attach_id: aifixPreviewState.id };
        const checked = document.querySelectorAll('.iseo-aifix-pv-cb:checked');
        if (!checked.length) { alert(T.aifix_pv_none_selected); return; }
        checked.forEach(cb => {
            const f = cb.getAttribute('data-field');
            const inp = document.querySelector('.iseo-aifix-pv-input[data-field="' + f + '"]');
            if (inp) data[f] = inp.value;
        });
        const btn = document.getElementById('iseo-aifix-pv-apply');
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="iseo-spinner"></span> ' + escapeHtml(T.applying);
        postAjax('seo_aeo_orchestra_image_seo_ai_fix_apply', data).then(resp => {
            btn.disabled = false;
            btn.innerHTML = orig;
            if (!resp || !resp.success) {
                alert(T.ai_fail + ' ' + ((resp && (resp.detail || resp.message || resp.error)) || ''));
                return;
            }
            window.iseoCloseModal('iseo-aifix-preview-modal');
            aifixPreviewState = { id: null, before: null, suggested: null, score_current: 0, score_estimated: 0, credits_used: 0 };
            loadList();
        });
    });

    // ─── Bug 2: changelog + sticky banner ───
    function showBulkDoneBanner(mode, processed, errors) {
        state.bulkBannerShown = true;
        const c = document.getElementById('iseo-bulk-done-container');
        if (!c) return;
        const isPreview = mode === 'preview';
        const intro = isPreview ? T.bulk_done_preview : T.bulk_done_apply;
        const cta = isPreview ? T.view_preview : T.view_changelog;
        const ctaIcon = isPreview ? '👁' : '📋';
        const ctaAction = isPreview ? 'open-preview' : 'open-changelog';
        c.innerHTML =
            '<div class="iseo-bulk-done-banner">'
              + '<div class="iseo-bdb-text">' + (isPreview ? '👁' : '✓') + ' ' + escapeHtml(intro)
              + ' <strong>' + (processed) + '</strong>'
              + (errors > 0 ? ' <small>(' + errors + ' errori)</small>' : '')
              + '</div>'
              + '<div style="display:flex; gap:8px; flex-wrap:wrap;">'
                + '<button class="iseo-btn iseo-btn-primary iseo-btn-sm" data-bdb-act="' + ctaAction + '">' + ctaIcon + ' ' + escapeHtml(cta) + '</button>'
                + '<button class="iseo-btn iseo-btn-ghost iseo-btn-sm" data-bdb-act="dismiss">' + escapeHtml(T.dismiss) + '</button>'
              + '</div>'
            + '</div>';
        c.querySelectorAll('[data-bdb-act]').forEach(b => {
            b.addEventListener('click', e => {
                const act = e.currentTarget.getAttribute('data-bdb-act');
                if (act === 'open-changelog') openChangelogModal();
                else if (act === 'open-preview') openPreviewModal();
                else if (act === 'dismiss') c.innerHTML = '';
            });
        });
    }

    function openChangelogModal() {
        const body = document.getElementById('iseo-changelog-body');
        const summ = document.getElementById('iseo-changelog-summary');
        body.innerHTML = '<div class="iseo-empty"><div class="iseo-spinner"></div> ' + escapeHtml(T.loading) + '</div>';
        document.getElementById('iseo-changelog-modal').classList.add('show');
        postAjax('seo_aeo_orchestra_image_seo_bulk_changelog', {}).then(resp => {
            if (!resp || !resp.success) {
                body.innerHTML = '<div class="iseo-banner iseo-banner-error">' + escapeHtml((resp && (resp.error || resp.message)) || 'error') + '</div>';
                return;
            }
            const log = resp.changelog || [];
            summ.innerHTML = '<strong>' + log.length + '</strong> ' + escapeHtml(T.fields_changed.toLowerCase());
            if (!log.length) {
                body.innerHTML = '<div class="iseo-empty">' + escapeHtml(T.no_changes) + '</div>';
                return;
            }
            let html = '<table class="iseo-diff-table"><thead><tr>'
                + '<th></th><th>' + escapeHtml('Filename') + '</th>'
                + '<th>' + escapeHtml(T.fields_changed) + '</th>'
                + '<th>' + escapeHtml(T.before) + '</th>'
                + '<th>' + escapeHtml(T.after) + '</th>'
                + '</tr></thead><tbody>';
            log.forEach(e => {
                const fields = (e.fields || []);
                const beforeCells = fields.map(f => {
                    const v = (e.before && e.before[f]) || '';
                    return '<div><strong style="font-size:10px; color:#9CA3AF; text-transform:uppercase;">' + escapeHtml(f) + ':</strong><br>'
                        + (v ? '<span class="iseo-diff-cell-old">' + escapeHtml(truncate(v, 100)) + '</span>'
                            : '<span class="iseo-diff-cell-old empty">— ' + escapeHtml(T.empty_field) + ' —</span>')
                        + '</div>';
                }).join('<br>');
                const afterCells = fields.map(f => {
                    const v = (e.after && e.after[f]) || '';
                    return '<div><strong style="font-size:10px; color:#9CA3AF; text-transform:uppercase;">' + escapeHtml(f) + ':</strong><br>'
                        + '<span class="iseo-diff-cell-new">' + escapeHtml(truncate(v, 100)) + '</span></div>';
                }).join('<br>');
                html += '<tr>'
                    + '<td><img class="iseo-diff-thumb" src="' + escapeHtml(e.thumb_url || '') + '" alt=""></td>'
                    + '<td style="font-size:12px; max-width:160px; word-break:break-all;">' + escapeHtml(e.filename || '') + '</td>'
                    + '<td><div class="iseo-diff-fields-tags">' + fields.map(f => '<span class="iseo-diff-tag">' + escapeHtml(f) + '</span>').join('') + '</div></td>'
                    + '<td>' + beforeCells + '</td>'
                    + '<td>' + afterCells + '</td>'
                    + '</tr>';
            });
            html += '</tbody></table>';
            body.innerHTML = html;
        });
    }

    function truncate(s, n) {
        s = String(s || '');
        return s.length > n ? s.slice(0, n - 1) + '…' : s;
    }

    // ─── Bug 4: bulk preview review ───
    function openPreviewModal() {
        const body = document.getElementById('iseo-pv-body');
        body.innerHTML = '<div class="iseo-empty"><div class="iseo-spinner"></div> ' + escapeHtml(T.loading) + '</div>';
        document.getElementById('iseo-preview-modal').classList.add('show');
        postAjax('seo_aeo_orchestra_image_seo_bulk_preview_get', {}).then(resp => {
            if (!resp || !resp.success) {
                body.innerHTML = '<div class="iseo-banner iseo-banner-error">' + escapeHtml((resp && (resp.error || resp.message)) || 'error') + '</div>';
                return;
            }
            state.previewItems = resp.items || [];
            state.previewExcluded = new Set();
            state.previewEdited = {};
            renderPreviewTable();
        });
    }

    function renderPreviewTable() {
        const body = document.getElementById('iseo-pv-body');
        if (!state.previewItems.length) {
            body.innerHTML = '<div class="iseo-empty">' + escapeHtml(T.no_changes) + '</div>';
            updatePreviewSummary();
            return;
        }
        let html = '<table class="iseo-preview-table"><thead><tr>'
            + '<th style="width:36px;"></th>'
            + '<th style="width:60px;"></th>'
            + '<th>' + escapeHtml('Filename') + '</th>'
            + '<th>Alt</th>'
            + '<th>Title</th>'
            + '<th>Caption</th>'
            + '<th>Description</th>'
            + '</tr></thead><tbody>';
        state.previewItems.forEach(it => {
            const aid = parseInt(it.attach_id, 10);
            const excluded = state.previewExcluded.has(aid);
            const sug = it.suggested || {};
            const edits = state.previewEdited[aid] || {};
            const v = (f) => (edits[f] !== undefined ? edits[f] : (sug[f] || ''));
            html += '<tr class="' + (excluded ? 'row-excluded' : '') + '" data-aid="' + aid + '">'
                + '<td><input type="checkbox" class="iseo-pv-cb" data-aid="' + aid + '" ' + (excluded ? '' : 'checked') + '></td>'
                + '<td><img class="iseo-pv-thumb" src="' + escapeHtml(it.thumb_url || it.url || '') + '" alt=""></td>'
                + '<td style="font-size:12px; max-width:140px; word-break:break-all;">' + escapeHtml(it.filename || '') + '</td>'
                + '<td><input type="text" data-aid="' + aid + '" data-field="alt" value="' + escapeHtml(v('alt')) + '"></td>'
                + '<td><input type="text" data-aid="' + aid + '" data-field="title" value="' + escapeHtml(v('title')) + '"></td>'
                + '<td><input type="text" data-aid="' + aid + '" data-field="caption" value="' + escapeHtml(v('caption')) + '"></td>'
                + '<td><textarea data-aid="' + aid + '" data-field="description">' + escapeHtml(v('description')) + '</textarea></td>'
                + '</tr>';
        });
        html += '</tbody></table>';
        body.innerHTML = html;
        // bind checkboxes
        body.querySelectorAll('.iseo-pv-cb').forEach(cb => {
            cb.addEventListener('change', e => {
                const aid = parseInt(e.target.getAttribute('data-aid'), 10);
                if (e.target.checked) state.previewExcluded.delete(aid);
                else state.previewExcluded.add(aid);
                const tr = e.target.closest('tr');
                if (tr) tr.classList.toggle('row-excluded', !e.target.checked);
                updatePreviewSummary();
            });
        });
        // bind input edits
        body.querySelectorAll('input[type="text"][data-field], textarea[data-field]').forEach(inp => {
            inp.addEventListener('input', e => {
                const aid = parseInt(e.target.getAttribute('data-aid'), 10);
                const field = e.target.getAttribute('data-field');
                if (!state.previewEdited[aid]) state.previewEdited[aid] = {};
                state.previewEdited[aid][field] = e.target.value;
            });
        });
        updatePreviewSummary();
    }

    function updatePreviewSummary() {
        const total = state.previewItems.length;
        const excluded = state.previewExcluded.size;
        const sel = total - excluded;
        document.getElementById('iseo-pv-total').textContent = total;
        document.getElementById('iseo-pv-selected').textContent = sel;
        document.getElementById('iseo-pv-excluded').textContent = excluded;
        document.getElementById('iseo-pv-apply-count').textContent = sel;
    }

    document.getElementById('iseo-pv-select-all').addEventListener('click', () => {
        state.previewExcluded.clear();
        renderPreviewTable();
    });
    document.getElementById('iseo-pv-deselect-all').addEventListener('click', () => {
        state.previewItems.forEach(it => state.previewExcluded.add(parseInt(it.attach_id, 10)));
        renderPreviewTable();
    });
    document.getElementById('iseo-pv-discard').addEventListener('click', () => {
        if (!confirm(T.confirm_discard_preview)) return;
        postAjax('seo_aeo_orchestra_image_seo_bulk_preview_clear', {}).then(() => {
            state.previewItems = [];
            state.previewExcluded.clear();
            state.previewEdited = {};
            window.iseoCloseModal('iseo-preview-modal');
            const c = document.getElementById('iseo-bulk-done-container');
            if (c) c.innerHTML = '';
        });
    });
    document.getElementById('iseo-pv-apply').addEventListener('click', () => {
        const force_overwrite = document.getElementById('iseo-pv-force-overwrite').checked ? '1' : '';
        const items = [];
        state.previewItems.forEach(it => {
            const aid = parseInt(it.attach_id, 10);
            if (state.previewExcluded.has(aid)) return;
            const sug = it.suggested || {};
            const edits = state.previewEdited[aid] || {};
            items.push({
                attach_id: aid,
                alt:         edits.alt !== undefined ? edits.alt : (sug.alt || ''),
                title:       edits.title !== undefined ? edits.title : (sug.title || ''),
                caption:     edits.caption !== undefined ? edits.caption : (sug.caption || ''),
                description: edits.description !== undefined ? edits.description : (sug.description || ''),
            });
        });
        if (!items.length) { alert(T.none_to_apply); return; }
        const btn = document.getElementById('iseo-pv-apply');
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="iseo-spinner"></span> ' + escapeHtml(T.applying);
        postAjax('seo_aeo_orchestra_image_seo_bulk_preview_apply', {
            items: JSON.stringify(items),
            force_overwrite: force_overwrite,
        }).then(resp => {
            btn.disabled = false;
            btn.innerHTML = orig;
            if (!resp || !resp.success) {
                alert((resp && (resp.error || resp.message)) || 'error');
                return;
            }
            window.iseoCloseModal('iseo-preview-modal');
            const c = document.getElementById('iseo-bulk-done-container');
            if (c) c.innerHTML = '';
            // Show changelog modal directly with returned data
            showInlineChangelog(resp.changelog || [], resp.applied_count || 0, resp.skipped || 0);
            loadList();
        });
    });

    function showInlineChangelog(log, applied, skipped) {
        const summ = document.getElementById('iseo-changelog-summary');
        const body = document.getElementById('iseo-changelog-body');
        summ.innerHTML = '<strong>✓ ' + escapeHtml(T.applied_count) + ' ' + applied + '</strong>'
            + (skipped > 0 ? ' • <span style="color:#92400E;">' + skipped + ' ' + escapeHtml(T.skipped) + '</span>' : '');
        if (!log.length) {
            body.innerHTML = '<div class="iseo-empty">' + escapeHtml(T.no_changes) + '</div>';
        } else {
            let html = '<table class="iseo-diff-table"><thead><tr>'
                + '<th></th><th>Filename</th><th>' + escapeHtml(T.fields_changed) + '</th>'
                + '<th>' + escapeHtml(T.before) + '</th><th>' + escapeHtml(T.after) + '</th></tr></thead><tbody>';
            log.forEach(e => {
                const fields = e.fields || [];
                const beforeCells = fields.map(f => {
                    const v = (e.before && e.before[f]) || '';
                    return '<div><strong style="font-size:10px; color:#9CA3AF; text-transform:uppercase;">' + escapeHtml(f) + ':</strong><br>'
                        + (v ? '<span class="iseo-diff-cell-old">' + escapeHtml(truncate(v, 100)) + '</span>'
                            : '<span class="iseo-diff-cell-old empty">— ' + escapeHtml(T.empty_field) + ' —</span>')
                        + '</div>';
                }).join('<br>');
                const afterCells = fields.map(f => {
                    const v = (e.after && e.after[f]) || '';
                    return '<div><strong style="font-size:10px; color:#9CA3AF; text-transform:uppercase;">' + escapeHtml(f) + ':</strong><br>'
                        + '<span class="iseo-diff-cell-new">' + escapeHtml(truncate(v, 100)) + '</span></div>';
                }).join('<br>');
                html += '<tr>'
                    + '<td><img class="iseo-diff-thumb" src="' + escapeHtml(e.thumb_url || '') + '" alt=""></td>'
                    + '<td style="font-size:12px; max-width:160px; word-break:break-all;">' + escapeHtml(e.filename || '') + '</td>'
                    + '<td><div class="iseo-diff-fields-tags">' + fields.map(f => '<span class="iseo-diff-tag">' + escapeHtml(f) + '</span>').join('') + '</div></td>'
                    + '<td>' + beforeCells + '</td>'
                    + '<td>' + afterCells + '</td>'
                    + '</tr>';
            });
            html += '</tbody></table>';
            body.innerHTML = html;
        }
        document.getElementById('iseo-changelog-modal').classList.add('show');
    }

    // ─── Bug 3: stat cards click ───
    document.querySelectorAll('.iseo-stat-card').forEach(card => {
        card.addEventListener('click', () => {
            const filter = card.getAttribute('data-stat-filter');
            const info = card.getAttribute('data-stat-info');
            if (filter) {
                state.filter = filter;
                state.page = 1;
                const sel = document.getElementById('iseo-filter');
                if (sel) sel.value = filter;
                document.querySelectorAll('.iseo-stat-card').forEach(c => c.classList.remove('iseo-stat-active'));
                card.classList.add('iseo-stat-active');
                loadList();
                // scroll to table
                const tableTop = document.getElementById('iseo-list-container');
                if (tableTop && tableTop.scrollIntoView) tableTop.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else if (info === 'traffic') {
                document.getElementById('iseo-traffic-info-modal').classList.add('show');
            }
        });
    });

    // Toolbar handlers
    document.getElementById('iseo-filter').addEventListener('change', e => { state.filter = e.target.value; state.page = 1; loadList(); });
    document.getElementById('iseo-sort').addEventListener('change', e => { state.sort = e.target.value; state.page = 1; loadList(); });
    let searchT = null;
    document.getElementById('iseo-search').addEventListener('input', e => {
        clearTimeout(searchT);
        searchT = setTimeout(() => { state.search = e.target.value; state.page = 1; loadList(); }, 350);
    });
    document.getElementById('iseo-select-all').addEventListener('change', e => {
        // BUG 2 FIX: "Seleziona tutti" toolbar — su uncheck, clear ENTIRE state.selected (cross-page).
        const cb = e.target.checked;
        if (cb) {
            state.items.forEach(it => state.selected.add(it.id));
        } else {
            state.selected.clear();
        }
        renderList();
        updateSelectedCount();
    });

    document.getElementById('iseo-export-csv').addEventListener('click', () => {
        const url = ajaxurl + '?action=seo_aeo_orchestra_image_seo_export_csv&nonce=' + encodeURIComponent(nonce);
        window.location.href = url;
    });

    // Bulk flow
    document.getElementById('iseo-bulk-selected').addEventListener('click', () => {
        if (state.selected.size === 0) { alert(T.none_selected); return; }
        const n = state.selected.size;
        document.getElementById('iseo-bulk-count').textContent = n;
        document.getElementById('iseo-bulk-cost').textContent = n * 2;
        document.getElementById('iseo-bulk-time').textContent = Math.max(1, Math.ceil(n / 5));
        document.getElementById('iseo-bulk-confirm').classList.add('show');
    });

    // Bulk mode toggle (Bug 4)
    document.querySelectorAll('.iseo-mode-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.iseo-mode-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            const inp = card.querySelector('input[type="radio"]');
            if (inp) {
                inp.checked = true;
                state.bulkMode = inp.value === 'apply' ? 'apply' : 'preview';
            }
        });
    });

    document.getElementById('iseo-bulk-confirm-go').addEventListener('click', () => {
        // BUG 2 FIX (3.35.4): leggi sempre dal DOM al momento del click — non da state cached.
        // Cliente: "selezionato tutte → annullato → deselezionato e selezionato 2 → bulk fix → partito con 20".
        // Cause: state.selected accumula IDs cross-page; "Deseleziona tutti" itera solo state.items (pagina visibile)
        // lasciando residui da pagine precedenti. Soluzione: DOM-truth al submit.
        const domIds = Array.from(document.querySelectorAll('.iseo-row-cb:checked'))
            .map(cb => parseInt(cb.getAttribute('data-id'), 10))
            .filter(n => Number.isInteger(n) && n > 0);
        // Sync state da DOM (riallinea state.selected con DOM autoritativo)
        state.selected = new Set(domIds);
        const ids = domIds;
        if (!ids.length) { alert(T.none_selected); return; }
        document.getElementById('iseo-bulk-confirm-go').disabled = true;
        const mode = state.bulkMode;
        state.lastBulkMode = mode;
        postAjax('seo_aeo_orchestra_image_seo_bulk_queue', { attach_ids: ids, mode: mode }).then(resp => {
            document.getElementById('iseo-bulk-confirm-go').disabled = false;
            if (!resp || !resp.success) {
                alert((resp && (resp.error || resp.message)) || T.bulk_running_warn);
                return;
            }
            window.iseoCloseModal('iseo-bulk-confirm');
            document.getElementById('iseo-bulk-progress').classList.add('show');
            document.getElementById('iseo-bp-cancel').style.display = '';
            document.getElementById('iseo-bp-close').style.display = 'none';
            document.getElementById('iseo-bp-banner').innerHTML = '';
            startBulkPolling();
        });
    });

    function startBulkPolling() {
        if (state.bulkPolling) clearInterval(state.bulkPolling);
        const tick = () => {
            postAjax('seo_aeo_orchestra_image_seo_bulk_status', {}).then(resp => {
                if (!resp) return;
                const total = resp.total || 0;
                const processed = resp.processed || 0;
                const errors = resp.errors || 0;
                const remaining = resp.remaining || 0;
                const credits = resp.credits_used || 0;
                const pct = total > 0 ? Math.min(100, Math.round((processed + errors) / total * 100)) : 0;
                document.getElementById('iseo-bp-fill').style.width = pct + '%';
                document.getElementById('iseo-bp-text').textContent = (processed + errors) + '/' + total + ' (' + pct + '%)';
                document.getElementById('iseo-bp-done').textContent = processed;
                document.getElementById('iseo-bp-credits').textContent = credits;
                document.getElementById('iseo-bp-errors').textContent = errors;
                document.getElementById('iseo-bp-eta').textContent = remaining > 0 ? Math.max(1, Math.ceil(remaining / 5)) + ' min' : '—';
                if (!resp.active && !resp.paused) {
                    if (state.bulkPolling) { clearInterval(state.bulkPolling); state.bulkPolling = null; }
                    const jobMode = resp.mode || 'apply';
                    document.getElementById('iseo-bp-banner').innerHTML =
                        '<div class="iseo-banner iseo-banner-success">✓ ' + escapeHtml(T.bulk_done) + '</div>';
                    document.getElementById('iseo-bp-cancel').style.display = 'none';
                    document.getElementById('iseo-bp-close').style.display = '';
                    state.selected.clear();
                    // Sticky completion banner (Bug 2 + Bug 4)
                    showBulkDoneBanner(jobMode, processed, errors);
                    if (jobMode === 'preview') {
                        // Auto-open preview review modal
                        setTimeout(() => { openPreviewModal(); }, 600);
                    }
                    loadList();
                }
            });
        };
        tick();
        state.bulkPolling = setInterval(tick, 3000);
    }

    document.getElementById('iseo-bp-cancel').addEventListener('click', () => {
        if (!confirm(T.confirm_cancel)) return;
        postAjax('seo_aeo_orchestra_image_seo_bulk_cancel', {}).then(() => {
            if (state.bulkPolling) { clearInterval(state.bulkPolling); state.bulkPolling = null; }
            window.iseoCloseModal('iseo-bulk-progress');
            loadList();
        });
    });
    document.getElementById('iseo-bp-close').addEventListener('click', () => {
        window.iseoCloseModal('iseo-bulk-progress');
    });

    // Resume bulk modal automatically se c'è un job in corso al boot
    // + ripristina banner "completato" se l'ultimo job è chiuso ma changelog/preview disponibili
    function maybeResumeBulk() {
        postAjax('seo_aeo_orchestra_image_seo_bulk_status', {}).then(resp => {
            if (resp && resp.active) {
                document.getElementById('iseo-bulk-progress').classList.add('show');
                state.lastBulkMode = resp.mode || 'apply';
                startBulkPolling();
                return;
            }
            // Se job già completo e ha changelog (apply mode) → mostra banner
            if (resp && resp.completed_at && resp.mode === 'apply' && (resp.changelog_count > 0)) {
                showBulkDoneBanner('apply', resp.processed || 0, resp.errors || 0);
            }
            // Preview pending → check transient via dedicated endpoint
            if (resp && resp.completed_at && resp.mode === 'preview') {
                postAjax('seo_aeo_orchestra_image_seo_bulk_preview_get', {}).then(pv => {
                    if (pv && pv.success && pv.count > 0) {
                        showBulkDoneBanner('preview', pv.count, resp.errors || 0);
                    }
                });
            }
        });
    }

    // Initial load
    loadList();
    maybeResumeBulk();
})();
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>

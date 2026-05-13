<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.VariableNotPrefixed
// Reason: template scope. Variables are local to this include/template,
// passed by the calling function via include/require. The Plugin Check
// heuristic doesn't distinguish template-scope locals from globals.
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com
 *
 * Content Calendar (3.33.0) — submenu "📅 Calendario contenuti AI"
 * Pianifica articoli AI, genera N giorni prima, auto-pubblica se opt-in.
 */
if (!defined('ABSPATH')) exit;
$T = function($s) { return class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t($s)) : esc_html($s); };
$Tjs = function($s) { return class_exists('SEO_AEO_T') ? esc_js(SEO_AEO_T::t($s)) : esc_js($s); };
$default_hour = (int) get_option('seo_aeo_orchestra_calendar_default_hour', 9);
$default_days = (int) get_option('seo_aeo_orchestra_calendar_default_days_before', 1);
$default_auto = get_option('seo_aeo_orchestra_calendar_default_auto_publish', '1') === '1';
$default_cat  = (int) get_option('seo_aeo_orchestra_calendar_default_category', 0);
$ajax_url = admin_url('admin-ajax.php');
$nonce = wp_create_nonce('seo_aeo_orchestra_nonce');
?>
<?php ob_start(); ?>
.orch-cal-wrap { max-width: 100%; margin-top:20px; box-sizing:border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
.orch-cal-hero { background:linear-gradient(135deg,#0A0E27 0%,#0055FF 50%,#00E5FF 100%); color:#fff; border-radius:14px; padding:28px 32px; margin-bottom:18px; box-shadow:0 8px 24px rgba(10,14,39,.18); }
.orch-cal-hero h1 { color:#fff; font-size:28px; margin:0 0 6px; font-weight:700; }
.orch-cal-hero p { margin:0; opacity:.92; font-size:15px; }
.orch-cal-hero .orch-cal-month-nav { display:flex; gap:8px; align-items:center; margin-top:14px; }
.orch-cal-hero .orch-cal-month-nav button { background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.3); color:#fff; padding:6px 14px; border-radius:8px; cursor:pointer; font-weight:600; }
.orch-cal-hero .orch-cal-month-nav button:hover { background:rgba(255,255,255,.28); }
.orch-cal-hero .orch-cal-current-label { font-size:18px; font-weight:600; min-width:200px; text-align:center; }

.orch-cal-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:18px; }
.orch-cal-stat { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:14px 16px; }
.orch-cal-stat .v { font-size:28px; font-weight:700; color:#0055FF; line-height:1; }
.orch-cal-stat .l { font-size:12px; text-transform:uppercase; color:#6b7280; letter-spacing:.5px; margin-top:6px; }

.orch-cal-actions { display:flex; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.orch-cal-btn { background:#0055FF; color:#fff; border:none; padding:9px 16px; border-radius:8px; cursor:pointer; font-weight:600; font-size:13px; }
.orch-cal-btn:hover { background:#003fcc; }
.orch-cal-btn.secondary { background:#f3f4f6; color:#111; border:1px solid #d1d5db; }
.orch-cal-btn.secondary:hover { background:#e5e7eb; }
.orch-cal-btn.danger { background:#dc2626; color:#fff; }
.orch-cal-btn.danger:hover { background:#b91c1c; }

.orch-cal-grid-wrap { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:18px; }
.orch-cal-weekdays { display:grid; grid-template-columns:repeat(7,1fr); gap:6px; margin-bottom:8px; }
.orch-cal-weekdays > div { font-weight:600; font-size:12px; text-transform:uppercase; color:#6b7280; padding:6px 8px; text-align:center; letter-spacing:.5px; }
.orch-cal-grid { display:grid !important; grid-template-columns:repeat(7,minmax(0,1fr)) !important; gap:6px; width:100%; box-sizing:border-box; }
.orch-cal-grid-wrap { overflow:hidden; }
.orch-cal-weekdays { display:grid !important; grid-template-columns:repeat(7,minmax(0,1fr)) !important; gap:6px; }
.orch-cal-cell { min-width:0 !important; overflow:hidden; }
.orch-cal-slots { min-width:0; }
.orch-cal-slot { max-width:100%; }
.orch-cal-cell { border:1px solid #e5e7eb; border-radius:8px; min-height:96px; padding:6px 8px; background:#fff; cursor:pointer; transition:all .15s; position:relative; display:flex; flex-direction:column; }
.orch-cal-cell:hover { border-color:#0055FF; box-shadow:0 2px 6px rgba(0,85,255,.12); }
.orch-cal-cell.other-month { background:#f9fafb; opacity:.55; }
.orch-cal-cell.today { border:2px solid #0055FF; background:#eff6ff; }
.orch-cal-cell.past { background:#fafafa; cursor:default; }
.orch-cal-cell.past:hover { border-color:#e5e7eb; box-shadow:none; }
.orch-cal-day-num { font-weight:600; font-size:13px; color:#111; }
.orch-cal-cell.other-month .orch-cal-day-num { color:#9ca3af; }
.orch-cal-slots { margin-top:4px; display:flex; flex-direction:column; gap:2px; flex:1; }
.orch-cal-slot { font-size:11px; padding:2px 6px; border-radius:4px; background:#f3f4f6; cursor:pointer; line-height:1.4; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.orch-cal-slot:hover { background:#e5e7eb; }
.orch-cal-slot.s-planned   { background:#f3f4f6; color:#374151; }
.orch-cal-slot.s-generating{ background:#fef3c7; color:#92400e; }
.orch-cal-slot.s-preview   { background:#fde68a; color:#78350f; }
.orch-cal-slot.s-generated { background:#dbeafe; color:#1e40af; }
.orch-cal-slot.s-published { background:#d1fae5; color:#065f46; }
.orch-cal-slot.s-skipped   { background:#e5e7eb; color:#6b7280; text-decoration:line-through; }
.orch-cal-slot.s-error     { background:#fee2e2; color:#991b1b; }

/* 3.35.0 — Preview modal styling */
.orch-cal-preview-meta-banner { background:linear-gradient(135deg,#0A0E27 0%,#0055FF 50%,#00E5FF 100%); color:#fff; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:14px; display:flex; gap:14px; flex-wrap:wrap; }
.orch-cal-preview-meta-banner span { display:inline-flex; align-items:center; gap:5px; }
.orch-cal-preview-body { max-height:60vh; overflow-y:auto; padding:18px 22px; background:#fff; border:1px solid #e5e7eb; border-radius:8px; box-sizing:border-box; }
.orch-cal-preview-body h1 { font-size:24px; font-weight:700; margin:0 0 14px; color:#0A0E27; line-height:1.3; }
.orch-cal-preview-body h2 { font-size:18px; font-weight:600; margin:18px 0 10px; color:#111; }
.orch-cal-preview-body h3 { font-size:15px; font-weight:600; margin:14px 0 8px; color:#111; }
.orch-cal-preview-body p, .orch-cal-preview-body li { font-size:14px; line-height:1.65; color:#222; }
.orch-cal-preview-body ul, .orch-cal-preview-body ol { padding-left:24px; margin:10px 0; }
.orch-cal-preview-body strong { color:#111; }
.orch-cal-preview-body img { max-width:780px; width:100%; height:auto; aspect-ratio:16/9; object-fit:cover; border-radius:8px; display:block; margin:0 0 16px; }
.orch-cal-preview-image-wrap { margin-bottom:14px; }
.orch-cal-preview-meta-tags { background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:12px 14px; margin-top:14px; font-size:13px; line-height:1.7; color:#374151; }
.orch-cal-preview-meta-tags strong { color:#111; }
.orch-cal-preview-info { background:#fff7ed; border:1px solid #fed7aa; border-radius:8px; padding:10px 12px; margin-top:12px; font-size:12px; color:#78350f; line-height:1.5; }
.orch-cal-preview-actions { display:flex; gap:8px; margin-top:16px; flex-wrap:wrap; align-items:center; }
.orch-cal-preview-actions .orch-cal-btn[disabled] { opacity:.55; cursor:not-allowed; }
.orch-cal-btn.success { background:#16a34a; color:#fff; }
.orch-cal-btn.success:hover { background:#15803d; }

.orch-cal-legend { display:flex; gap:14px; margin-top:14px; font-size:12px; color:#6b7280; flex-wrap:wrap; }
.orch-cal-legend .l { display:flex; align-items:center; gap:5px; }
.orch-cal-legend .dot { width:10px; height:10px; border-radius:50%; display:inline-block; }

.orch-cal-upcoming { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px; }
.orch-cal-upcoming h3 { margin:0 0 12px; font-size:15px; font-weight:600; }
.orch-cal-upcoming .item { padding:10px 12px; background:#f9fafb; border-radius:8px; display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:8px; }
.orch-cal-upcoming .item .info { flex:1; min-width:0; }
.orch-cal-upcoming .item .info .when { font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:.5px; }
.orch-cal-upcoming .item .info .what { font-weight:600; margin-top:2px; font-size:13px; }
.orch-cal-upcoming .item .info .meta { font-size:11px; color:#6b7280; margin-top:2px; }
.orch-cal-upcoming .item .actions { display:flex; gap:6px; flex-wrap:wrap; }

/* 3.33.1: z-index 999999 sopra WP admin bar (99999); position:fixed forzato per evitare
   conflitti con CSS theme/admin che possono override; inset:0 + fallback top/right/bottom/left
   per browser legacy; html/body scroll-lock quando modal aperta per evitare 'pagina spagina'
   (calendar grid sotto il backdrop che scrolla independentemente). */
.orch-cal-modal { position:fixed !important; top:0 !important; right:0 !important; bottom:0 !important; left:0 !important; inset:0; background:rgba(0,0,0,.5); z-index:999999 !important; display:none; align-items:center; justify-content:center; padding:20px; box-sizing:border-box; }
.orch-cal-modal.open { display:flex !important; }
body.orch-cal-modal-lock { overflow:hidden !important; }
.orch-cal-modal-content { background:#fff; border-radius:12px; padding:24px 28px; max-width:760px; width:100%; max-height:90vh; overflow-y:auto; box-shadow:0 20px 50px rgba(0,0,0,.2); position:relative; box-sizing:border-box; }
.orch-cal-modal-content h2 { margin:0 0 14px; font-size:20px; font-weight:700; }
.orch-cal-modal-content .field { margin-bottom:14px; }
.orch-cal-modal-content label { font-weight:600; font-size:13px; display:block; margin-bottom:5px; color:#374151; }
.orch-cal-modal-content input[type=text], .orch-cal-modal-content textarea, .orch-cal-modal-content select { width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; box-sizing:border-box; }
.orch-cal-modal-content textarea { min-height:60px; resize:vertical; }
.orch-cal-modal-content .row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.orch-cal-modal-content .modal-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:20px; }
.orch-cal-modal-content .help { font-size:12px; color:#6b7280; margin-top:4px; }

.orch-cal-suggestions { max-height:380px; overflow-y:auto; border:1px solid #e5e7eb; border-radius:8px; padding:8px; }
.orch-cal-suggestions .sugg { padding:8px 10px; border-bottom:1px solid #f3f4f6; display:flex; gap:10px; align-items:flex-start; }
.orch-cal-suggestions .sugg:last-child { border-bottom:none; }
.orch-cal-suggestions .sugg input[type=checkbox] { margin-top:4px; }
.orch-cal-suggestions .sugg .stxt { flex:1; }
.orch-cal-suggestions .sugg .stxt .t { font-weight:600; font-size:13px; }
.orch-cal-suggestions .sugg .stxt .k { font-size:11px; color:#0055FF; margin-top:2px; }
.orch-cal-suggestions .sugg .stxt .r { font-size:11px; color:#6b7280; margin-top:2px; font-style:italic; }

.orch-cal-toast { position:fixed; bottom:20px; right:20px; background:#111; color:#fff; padding:12px 18px; border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,.2); z-index:99999; opacity:0; transform:translateY(10px); transition:all .2s; pointer-events:none; }
.orch-cal-toast.show { opacity:1; transform:translateY(0); }
.orch-cal-toast.success { background:#10b981; }
.orch-cal-toast.error { background:#dc2626; }

.orch-cal-stepper { display:flex; gap:6px; margin-bottom:16px; }
.orch-cal-stepper .step { flex:1; padding:8px; text-align:center; border-radius:6px; background:#f3f4f6; color:#6b7280; font-size:11px; font-weight:600; }
.orch-cal-stepper .step.active { background:#0055FF; color:#fff; }
.orch-cal-stepper .step.done { background:#10b981; color:#fff; }
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

<div class="wrap orch-cal-wrap">
    <div class="orch-cal-hero">
        <h1><?php echo esc_html(SEO_AEO_T::t('Calendario contenuti AI')); ?></h1>
        <p><?php echo esc_html(SEO_AEO_T::t('Pianifica articoli, genera in automatico, pubblica quando vuoi tu')); ?></p>
        <div style="margin-top:12px;background:rgba(16,185,129,.18);border:1px solid rgba(16,185,129,.4);padding:10px 14px;border-radius:8px;display:inline-flex;align-items:center;gap:10px;font-size:13px;color:#fff;">
            <span style="font-size:18px;">💚</span>
            <span>
                <strong><?php echo esc_html(SEO_AEO_T::t('Soddisfatto o rimborsato')); ?>:</strong>
                <?php echo esc_html(SEO_AEO_T::t('genera senza paura, hai 5 minuti per ottenere il rimborso completo dei crediti se l\'articolo non ti convince')); ?>
                (<?php echo esc_html(SEO_AEO_T::t('max 3 rimborsi al giorno')); ?>)
            </span>
        </div>
        
        <div class="orch-cal-month-nav">
            <button id="orch-cal-prev">&laquo;</button>
            <span class="orch-cal-current-label" id="orch-cal-current-label">—</span>
            <button id="orch-cal-next">&raquo;</button>
            <button id="orch-cal-today" style="margin-left:8px;"><?php echo esc_html(SEO_AEO_T::t('Oggi')); ?></button>
        </div>
    </div>

    <div class="orch-cal-stats">
        <div class="orch-cal-stat">
            <div class="v" id="orch-cal-stat-planned">—</div>
            <div class="l"><?php echo esc_html(SEO_AEO_T::t('Pianificati')); ?></div>
        </div>
        <div class="orch-cal-stat">
            <div class="v" id="orch-cal-stat-drafts">—</div>
            <div class="l"><?php echo esc_html(SEO_AEO_T::t('Bozze')); ?></div>
        </div>
        <div class="orch-cal-stat">
            <div class="v" id="orch-cal-stat-pub">—</div>
            <div class="l"><?php echo esc_html(SEO_AEO_T::t('Pubblicati ultimi 30 giorni')); ?></div>
        </div>
    </div>

    <div class="orch-cal-actions">
        <button class="orch-cal-btn" id="orch-cal-bulk-btn">🚀 <?php echo esc_html(SEO_AEO_T::t('Pianifica 7 o 30 articoli (1 al giorno) con AI')); ?></button>
        <button class="orch-cal-btn secondary" id="orch-cal-refresh-btn">🔄 <?php echo esc_html(SEO_AEO_T::t('Aggiorna')); ?></button>
    </div>

    <div class="orch-cal-grid-wrap">
        <div class="orch-cal-weekdays">
            <div><?php echo esc_html(SEO_AEO_T::t('Lun')); ?></div>
            <div><?php echo esc_html(SEO_AEO_T::t('Mar')); ?></div>
            <div><?php echo esc_html(SEO_AEO_T::t('Mer')); ?></div>
            <div><?php echo esc_html(SEO_AEO_T::t('Gio')); ?></div>
            <div><?php echo esc_html(SEO_AEO_T::t('Ven')); ?></div>
            <div><?php echo esc_html(SEO_AEO_T::t('Sab')); ?></div>
            <div><?php echo esc_html(SEO_AEO_T::t('Dom')); ?></div>
        </div>
        <div class="orch-cal-grid" id="orch-cal-grid"></div>
        <div class="orch-cal-legend">
            <span class="l"><span class="dot" style="background:#9ca3af"></span> 📅 <?php echo esc_html(SEO_AEO_T::t('Pianificato')); ?></span>
            <span class="l"><span class="dot" style="background:#fbbf24"></span> ⏳ <?php echo esc_html(SEO_AEO_T::t('Generazione')); ?></span>
            <span class="l"><span class="dot" style="background:#fde68a"></span> 📋 <?php echo esc_html(SEO_AEO_T::t('Anteprima da rivedere')); ?></span>
            <span class="l"><span class="dot" style="background:#3b82f6"></span> 🤖 <?php echo esc_html(SEO_AEO_T::t('Bozza generata')); ?></span>
            <span class="l"><span class="dot" style="background:#10b981"></span> ✅ <?php echo esc_html(SEO_AEO_T::t('Pubblicato')); ?></span>
            <span class="l"><span class="dot" style="background:#dc2626"></span> ⚠️ <?php echo esc_html(SEO_AEO_T::t('Errore')); ?></span>
            <span class="l"><span class="dot" style="background:#6b7280"></span> ⏸ <?php echo esc_html(SEO_AEO_T::t('Saltato')); ?></span>
        </div>
    </div>

    <div class="orch-cal-upcoming">
        <h3><?php echo esc_html(SEO_AEO_T::t('Prossimi 7 giorni')); ?></h3>
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:12px;flex-wrap:wrap;">
            <label style="display:flex;align-items:center;gap:6px;font-weight:400;font-size:13px;cursor:pointer;">
                <input type="checkbox" id="orch-cal-bulk-select-all"> <?php echo esc_html(SEO_AEO_T::t('Seleziona tutti i pianificati')); ?>
            </label>
            <button class="orch-cal-btn danger" id="orch-cal-bulk-delete-btn" style="display:none;">
                🗑 <span id="orch-cal-bulk-delete-label"><?php echo esc_html(SEO_AEO_T::t('Elimina selezionati')); ?></span>
            </button>
        </div>
        <div id="orch-cal-upcoming-list"><p style="color:#6b7280;font-size:13px;"><?php echo esc_html(SEO_AEO_T::t('Caricamento…')); ?></p></div>
    </div>
</div>

<!-- Modal: Add/Edit slot -->
<div class="orch-cal-modal" id="orch-cal-modal-slot">
    <div class="orch-cal-modal-content">
        <h2 id="orch-cal-modal-title">&nbsp;</h2>
        <div class="field">
            <label><?php echo esc_html(SEO_AEO_T::t('Topic / Argomento')); ?> *</label>
            <input type="text" id="orch-cal-f-topic" placeholder="<?php echo esc_html(SEO_AEO_T::t('Es. Come scegliere un plugin SEO per WordPress')); ?>" />
        </div>
        <div class="field">
            <label><?php echo esc_html(SEO_AEO_T::t('Keyword target')); ?></label>
            <input type="text" id="orch-cal-f-keyword" placeholder="<?php echo esc_html(SEO_AEO_T::t('Es. plugin seo wordpress')); ?>" />
            <div class="help"><?php echo esc_html(SEO_AEO_T::t('Lascia vuoto per usare il topic come keyword.')); ?></div>
        </div>
        <div class="row">
            <div class="field">
                <label><?php echo esc_html(SEO_AEO_T::t('Ora pubblicazione')); ?></label>
                <select id="orch-cal-f-hour">
                    <?php for ($h = 0; $h < 24; $h++): ?>
                        <option value="<?php echo esc_attr($h); ?>" <?php selected($h, $default_hour); ?>><?php echo esc_html(sprintf('%02d:00', $h)); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="field">
                <label><?php echo esc_html(SEO_AEO_T::t('Genera N giorni prima')); ?></label>
                <select id="orch-cal-f-days">
                    <option value="0" <?php selected(0, $default_days); ?>><?php echo esc_html(SEO_AEO_T::t('Stesso giorno')); ?></option>
                    <option value="1" <?php selected(1, $default_days); ?>><?php echo esc_html(SEO_AEO_T::t('1 giorno')); ?></option>
                    <option value="2" <?php selected(2, $default_days); ?>>2 <?php echo esc_html(SEO_AEO_T::t('giorni')); ?></option>
                    <option value="3" <?php selected(3, $default_days); ?>>3 <?php echo esc_html(SEO_AEO_T::t('giorni')); ?></option>
                    <option value="7" <?php selected(7, $default_days); ?>>7 <?php echo esc_html(SEO_AEO_T::t('giorni')); ?></option>
                </select>
            </div>
        </div>
        <div class="field">
            <label><?php echo esc_html(SEO_AEO_T::t('Auto-pubblicazione')); ?></label>
            <label style="font-weight:400;display:block;margin-bottom:4px;">
                <input type="radio" name="orch-cal-auto" value="1" <?php echo esc_attr($default_auto ? 'checked' : ''); ?>> <?php echo esc_html(SEO_AEO_T::t('Sì auto-pubblica se non rivedo')); ?>
            </label>
            <label style="font-weight:400;display:block;">
                <input type="radio" name="orch-cal-auto" value="0" <?php echo esc_attr(!$default_auto ? 'checked' : ''); ?>> <?php echo esc_html(SEO_AEO_T::t('No lascia come draft')); ?>
            </label>
        </div>
        <div class="field">
            <label><?php echo esc_html(SEO_AEO_T::t('Categoria')); ?></label>
            <select id="orch-cal-f-cat"><option value="0">— <?php echo esc_html(SEO_AEO_T::t('Nessuna')); ?> —</option></select>
        </div>
        <input type="hidden" id="orch-cal-f-slot-id" value="" />
        <input type="hidden" id="orch-cal-f-date" value="" />
        <input type="hidden" id="orch-cal-f-post-id" value="0" />
        <input type="hidden" id="orch-cal-f-status" value="planned" />
        <div id="orch-cal-quick-preview-wrap" style="display:none; margin-top:14px;">
            <div id="orch-cal-quick-preview-frame" style="width:100%; max-height:60vh; overflow-y:auto; border:1px solid #e5e7eb; border-radius:8px; background:#fff; padding:20px 24px; box-sizing:border-box;">
                <div id="orch-cal-quick-preview-loading" style="text-align:center; padding:40px 0; color:#6b7280;">⏳ Caricamento anteprima...</div>
                <h2 id="orch-cal-quick-preview-title" style="display:none; margin:0 0 12px; font-size:22px; font-weight:700; color:#0A0E27;"></h2>
                <div id="orch-cal-quick-preview-meta" style="display:none; font-size:12px; color:#6b7280; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid #e5e7eb;"></div>
                <div id="orch-cal-quick-preview-content" style="display:none; font-size:14px; line-height:1.7; color:#111;"></div>
            </div>
        </div>
        <div class="modal-actions">
            <button class="orch-cal-btn danger" id="orch-cal-modal-delete" style="display:none; margin-right:auto;">🗑 <?php echo esc_html(SEO_AEO_T::t('Elimina slot')); ?></button>
            <button class="orch-cal-btn secondary" id="orch-cal-modal-generate-now" style="display:none;">🤖 <?php echo esc_html(SEO_AEO_T::t('Genera ora')); ?></button>
            <button class="orch-cal-btn secondary" id="orch-cal-modal-resume-preview" style="display:none;">📋 <?php echo esc_html(SEO_AEO_T::t('Riprendi anteprima')); ?></button>
            <button class="orch-cal-btn secondary" id="orch-cal-modal-quick-preview" style="display:none;">👁 <?php echo esc_html(SEO_AEO_T::t('Anteprima rapida')); ?></button>
            <button class="orch-cal-btn secondary" id="orch-cal-modal-edit-draft" style="display:none;">✏️ <?php echo esc_html(SEO_AEO_T::t('Modifica draft')); ?></button>
            <button id="orch-cal-modal-inject-image" style="display:none !important;"></button>
            <button class="orch-cal-btn secondary" id="orch-cal-modal-publish-now" style="display:none;">▶ <?php echo esc_html(SEO_AEO_T::t('Pubblica subito')); ?></button>
            <button class="orch-cal-btn secondary" id="orch-cal-modal-cancel"><?php echo esc_html(SEO_AEO_T::t('Annulla')); ?></button>
            <button class="orch-cal-btn" id="orch-cal-modal-save">💾 <?php echo esc_html(SEO_AEO_T::t('Salva')); ?></button>
        </div>
    </div>
</div>

<!-- Modal: Bulk wizard -->
<div class="orch-cal-modal" id="orch-cal-modal-bulk">
    <div class="orch-cal-modal-content" style="max-width:680px;">
        <h2 id="orch-cal-bulk-title">🚀 <?php echo esc_html(SEO_AEO_T::t('Pianifica articoli con AI')); ?></h2>
        <div class="orch-cal-stepper">
            <div class="step active" data-step="1">1. <?php echo esc_html(SEO_AEO_T::t('Quanti')); ?></div>
            <div class="step" data-step="2">2. <?php echo esc_html(SEO_AEO_T::t('Topic AI')); ?></div>
            <div class="step" data-step="3">3. <?php echo esc_html(SEO_AEO_T::t('Rivedi')); ?></div>
            <div class="step" data-step="4">4. <?php echo esc_html(SEO_AEO_T::t('Distribuisci')); ?></div>
            <div class="step" data-step="5">5. <?php echo esc_html(SEO_AEO_T::t('Conferma')); ?></div>
        </div>

        <!-- Step 1 -->
        <div data-bulk-step="1">
            <div class="field">
                <label><?php echo esc_html(SEO_AEO_T::t('Quanti articoli vuoi pianificare?')); ?></label>
                <select id="orch-cal-bulk-n">
                    <option value="7">7 <?php echo esc_html(SEO_AEO_T::t('articoli (~1 settimana)')); ?></option>
                    <option value="14">14 <?php echo esc_html(SEO_AEO_T::t('articoli (~2 settimane)')); ?></option>
                    <option value="30" selected>30 <?php echo esc_html(SEO_AEO_T::t('articoli (~1 mese)')); ?></option>
                </select>
            </div>
            <div class="field">
                <label><?php echo esc_html(SEO_AEO_T::t('Nicchia / settore')); ?> (<?php echo esc_html(SEO_AEO_T::t('opzionale')); ?>)</label>
                <input type="text" id="orch-cal-bulk-niche" placeholder="<?php echo esc_html(SEO_AEO_T::t('Es. software b2b, marketing automation')); ?>" />
                <div class="help"><?php echo esc_html(SEO_AEO_T::t('Aiuta l\'AI a generare topic più rilevanti. Costo: 5 crediti.')); ?></div>
            </div>
        </div>

        <!-- Step 2 (loading) -->
        <div data-bulk-step="2" style="display:none;">
            <p style="text-align:center;padding:20px;"><span style="font-size:32px;">⏳</span><br><?php echo esc_html(SEO_AEO_T::t('L\'AI sta suggerendo i topic…')); ?></p>
        </div>

        <!-- Step 3 (review) -->
        <div data-bulk-step="3" style="display:none;">
            <div class="field">
                <label><?php echo esc_html(SEO_AEO_T::t('Seleziona i topic da pianificare')); ?></label>
                <div class="orch-cal-suggestions" id="orch-cal-suggestions"></div>
            </div>
        </div>

        <!-- Step 4 (distribute) -->
        <div data-bulk-step="4" style="display:none;">
            <div class="field">
                <label><?php echo esc_html(SEO_AEO_T::t('Data primo articolo')); ?></label>
                <input type="text" id="orch-cal-bulk-start" placeholder="YYYY-MM-DD" />
            </div>
            <div class="field">
                <label><?php echo esc_html(SEO_AEO_T::t('Distribuzione')); ?></label>
                <select id="orch-cal-bulk-spread">
                    <option value="daily"><?php echo esc_html(SEO_AEO_T::t('1 articolo al giorno')); ?></option>
                    <option value="2_per_week"><?php echo esc_html(SEO_AEO_T::t('2 articoli a settimana')); ?></option>
                    <option value="3_per_week"><?php echo esc_html(SEO_AEO_T::t('3 articoli a settimana')); ?></option>
                    <option value="weekly"><?php echo esc_html(SEO_AEO_T::t('1 articolo a settimana')); ?></option>
                </select>
            </div>
            <div class="field">
                <label><?php echo esc_html(SEO_AEO_T::t('Auto-pubblica?')); ?></label>
                <label style="font-weight:400;"><input type="checkbox" id="orch-cal-bulk-auto" <?php echo esc_attr($default_auto ? 'checked' : ''); ?>> <?php echo esc_html(SEO_AEO_T::t('Sì auto-pubblica nelle date scelte')); ?></label>
            </div>
        </div>

        <!-- Step 5 (confirm) -->
        <div data-bulk-step="5" style="display:none;">
            <div id="orch-cal-bulk-summary" style="background:#f9fafb;padding:14px;border-radius:8px;margin-bottom:14px;"></div>
            <p style="font-size:12px;color:#6b7280;"><?php echo esc_html(SEO_AEO_T::t('Costo stimato per le generazioni AI: 25 crediti per articolo (Articolo Completo).')); ?></p>
        </div>

        <div class="modal-actions">
            <button class="orch-cal-btn secondary" id="orch-cal-bulk-prev"><?php echo esc_html(SEO_AEO_T::t('Indietro')); ?></button>
            <button class="orch-cal-btn secondary" id="orch-cal-bulk-cancel"><?php echo esc_html(SEO_AEO_T::t('Annulla')); ?></button>
            <button class="orch-cal-btn" id="orch-cal-bulk-next"><?php echo esc_html(SEO_AEO_T::t('Avanti')); ?> →</button>
            <button class="orch-cal-btn" id="orch-cal-bulk-confirm" style="display:none;">✓ <?php echo esc_html(SEO_AEO_T::t('Crea slot')); ?></button>
        </div>
    </div>
</div>

<!-- 3.35.0 — Preview modal (Calendar Preview & Refund flow) -->
<!-- 3.35.1 — Modal informativo policy crediti+rimborso -->
<div class="orch-cal-modal" id="orch-cal-modal-genconfirm">
    <div class="orch-cal-modal-content" style="max-width:560px;">
        <h2>🤖 <?php echo esc_html(SEO_AEO_T::t('Stai per generare un articolo')); ?></h2>
        <div style="background:#eff6ff;border-left:4px solid #0055FF;padding:14px 16px;border-radius:8px;margin:14px 0;font-size:14px;line-height:1.6;">
            <strong>💰 <?php echo esc_html(SEO_AEO_T::t('Costo: 25 crediti')); ?></strong> (<?php echo esc_html(SEO_AEO_T::t('Articolo Completo con immagine')); ?>)<br>
            <strong>👁 <?php echo esc_html(SEO_AEO_T::t('Come funziona')); ?>:</strong>
            <ol style="margin:6px 0 0 20px;padding:0;">
                <li><?php echo esc_html(SEO_AEO_T::t('L\'AI genera l\'articolo (60-90s, 25 crediti scalati subito)')); ?></li>
                <li><?php echo esc_html(SEO_AEO_T::t('Vedi l\'anteprima nel modal: titolo, immagine, contenuto, meta')); ?></li>
                <li><?php echo esc_html(SEO_AEO_T::t('Decidi: Accetta (crea draft WP), Rigenera (-25cr), o Rimborsa')); ?></li>
            </ol>
        </div>
        <div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:14px 16px;border-radius:8px;margin:14px 0;font-size:13px;line-height:1.6;">
            <strong>🔄 <?php echo esc_html(SEO_AEO_T::t('Rimborso disponibile se non ti piace')); ?>:</strong>
            <ul style="margin:6px 0 0 20px;padding:0;">
                <li><?php echo esc_html(SEO_AEO_T::t('Finestra rimborso: 5 minuti dalla generazione')); ?></li>
                <li><?php echo esc_html(SEO_AEO_T::t('Limite: max 3 rimborsi al giorno per licenza')); ?></li>
                <li><?php echo esc_html(SEO_AEO_T::t('Dopo refund: crediti restituiti al wallet, contenuto scartato')); ?></li>
            </ul>
        </div>
        <label style="display:block;margin:14px 0 4px;font-size:13px;">
            <input type="checkbox" id="orch-cal-genconfirm-skip"> <?php echo esc_html(SEO_AEO_T::t('Non mostrare più questa spiegazione')); ?>
        </label>
        <div class="modal-actions" style="margin-top:18px;">
            <button class="orch-cal-btn secondary" id="orch-cal-genconfirm-cancel"><?php echo esc_html(SEO_AEO_T::t('Annulla')); ?></button>
            <button class="orch-cal-btn" id="orch-cal-genconfirm-go">✅ <?php echo esc_html(SEO_AEO_T::t('Procedi (-25 cr)')); ?></button>
        </div>
    </div>
</div>

<div class="orch-cal-modal" id="orch-cal-modal-preview">
    <div class="orch-cal-modal-content" style="max-width:900px;">
        <h2 id="orch-cal-preview-title">📋 <?php echo esc_html(SEO_AEO_T::t('Anteprima articolo generato')); ?></h2>

        <div class="orch-cal-preview-meta-banner">
            <span>💰 <?php echo esc_html(SEO_AEO_T::t('Costo: 25 crediti')); ?></span>
            <span>⏱ <span id="orch-cal-preview-generated-at"><?php echo esc_html(SEO_AEO_T::t('Generato adesso')); ?></span></span>
            <span>🔄 <span id="orch-cal-preview-refund-window"><?php echo esc_html(SEO_AEO_T::t('Rimborsabile per 5 minuti')); ?></span></span>
        </div>

        <div class="orch-cal-preview-body">
            <h1 id="orch-cal-preview-h1"></h1>
            <div class="orch-cal-preview-image-wrap" id="orch-cal-preview-image"></div>
            <div id="orch-cal-preview-content"></div>
        </div>

        <div class="orch-cal-preview-meta-tags" id="orch-cal-preview-meta-wrap" style="display:none;">
            <strong><?php echo esc_html(SEO_AEO_T::t('Meta title')); ?>:</strong> <span id="orch-cal-preview-meta-title"></span><br>
            <strong><?php echo esc_html(SEO_AEO_T::t('Meta description')); ?>:</strong> <span id="orch-cal-preview-meta-desc"></span><br>
            <strong><?php echo esc_html(SEO_AEO_T::t('Focus keyword')); ?>:</strong> <span id="orch-cal-preview-meta-kw"></span>
            &nbsp;·&nbsp; <strong><?php echo esc_html(SEO_AEO_T::t('Parole')); ?>:</strong> <span id="orch-cal-preview-meta-wc"></span>
        </div>

        <div class="orch-cal-preview-info">
            ℹ️ <?php echo esc_html(SEO_AEO_T::t('Se chiudi senza accettare, l\'anteprima resta salvata 30 minuti. Riapri lo slot per recuperarla.')); ?>
            <br>
            <?php echo esc_html(SEO_AEO_T::t('Il rimborso e disponibile per 5 minuti dalla generazione (massimo 3 rimborsi al giorno).')); ?>
        </div>

        <div class="orch-cal-preview-actions">
            <button class="orch-cal-btn danger" id="orch-cal-preview-refund">🔄 <?php echo esc_html(SEO_AEO_T::t('Rimborsa e scarta')); ?></button>
            <button class="orch-cal-btn secondary" id="orch-cal-preview-regen">↻ <?php echo esc_html(SEO_AEO_T::t('Rigenera (-25 cr)')); ?></button>
            <button class="orch-cal-btn secondary" id="orch-cal-preview-cancel"><?php echo esc_html(SEO_AEO_T::t('Chiudi (mantieni anteprima)')); ?></button>
            <button class="orch-cal-btn success" id="orch-cal-preview-accept" style="margin-left:auto;">✓ <?php echo esc_html(SEO_AEO_T::t('Accetta e crea draft')); ?></button>
        </div>
    </div>
</div>

<div class="orch-cal-toast" id="orch-cal-toast"></div>

<script type="text/javascript">
(function() {
    var ajaxUrl = <?php echo wp_json_encode($ajax_url); ?>;
    var nonce = <?php echo wp_json_encode($nonce); ?>;
    var T = {
        gen: <?php echo wp_json_encode($Tjs('Genera ora')); ?>,
        sched: <?php echo wp_json_encode($Tjs('Pianifica articolo per')); ?>,
        editSlot: <?php echo wp_json_encode($Tjs('Modifica slot')); ?>,
        confirmDelete: <?php echo wp_json_encode($Tjs('Eliminare lo slot? Operazione irreversibile.')); ?>,
        confirmDeletePost: <?php echo wp_json_encode($Tjs('Vuoi eliminare anche la draft generata?')); ?>,
        confirmGenerateNow: <?php echo wp_json_encode($Tjs('Generare ora? Consuma ~25 crediti.')); ?>,
        confirmPublishNow: <?php echo wp_json_encode($Tjs('Pubblicare subito?')); ?>,
        slotCreated: <?php echo wp_json_encode($Tjs('Slot pianificato con successo')); ?>,
        slotUpdated: <?php echo wp_json_encode($Tjs('Slot aggiornato')); ?>,
        slotDeleted: <?php echo wp_json_encode($Tjs('Slot eliminato')); ?>,
        errorGeneric: <?php echo wp_json_encode($Tjs('Errore')); ?>,
        loadingNow: <?php echo wp_json_encode($Tjs('Generazione in corso…')); ?>,
        publishedOk: <?php echo wp_json_encode($Tjs('Pubblicato!')); ?>,
        generatedOk: <?php echo wp_json_encode($Tjs('Bozza generata!')); ?>,
        topicRequired: <?php echo wp_json_encode($Tjs('Topic obbligatorio.')); ?>,
        emptySuggestions: <?php echo wp_json_encode($Tjs('L\'AI non ha restituito suggerimenti. Riprova con una nicchia più specifica.')); ?>,
        bulkCreated: <?php echo wp_json_encode($Tjs('Slot creati')); ?>,
        next7days: <?php echo wp_json_encode($Tjs('Nessuno slot nei prossimi 7 giorni.')); ?>,
        autopublishOn: <?php echo wp_json_encode($Tjs('Auto-pubblica: ON')); ?>,
        autopublishOff: <?php echo wp_json_encode($Tjs('Auto-pubblica: OFF')); ?>,
        edit: <?php echo wp_json_encode($Tjs('Modifica')); ?>,
        preview: <?php echo wp_json_encode($Tjs('Anteprima')); ?>,
        delete: <?php echo wp_json_encode($Tjs('Elimina')); ?>,
        seeDraft: <?php echo wp_json_encode($Tjs('Vedi draft')); ?>,
        // 3.35.0 — Calendar Preview & Refund i18n
        previewLoading: <?php echo wp_json_encode($Tjs('Generazione anteprima in corso (60-90s)…')); ?>,
        previewReady: <?php echo wp_json_encode($Tjs('Anteprima pronta!')); ?>,
        previewExpired: <?php echo wp_json_encode($Tjs('Rimborso scaduto (>5 min)')); ?>,
        previewRefundWindow: <?php echo wp_json_encode($Tjs('Rimborsa e scarta')); ?>,
        previewRefundConfirm: <?php echo wp_json_encode($Tjs('Confermi il rimborso? L\'articolo verra scartato e i 25 crediti restituiti. Limite: massimo 3 rimborsi al giorno.')); ?>,
        previewRegenConfirm: <?php echo wp_json_encode($Tjs('Generare di nuovo? Scaleremo altri 25 crediti.')); ?>,
        previewAcceptCreating: <?php echo wp_json_encode($Tjs('Creazione draft in corso…')); ?>,
        previewDraftCreated: <?php echo wp_json_encode($Tjs('Draft creato con successo!')); ?>,
        previewRefunded: <?php echo wp_json_encode($Tjs('Rimborso effettuato')); ?>,
        previewRefundsRemaining: <?php echo wp_json_encode($Tjs('rimborsi rimanenti oggi')); ?>,
        previewDailyLimit: <?php echo wp_json_encode($Tjs('Limite giornaliero raggiunto (3 rimborsi)')); ?>,
        previewExpiredCannotRefund: <?php echo wp_json_encode($Tjs('Finestra rimborso scaduta. Puoi solo accettare o rigenerare.')); ?>,
        previewLoadFailed: <?php echo wp_json_encode($Tjs('Anteprima non disponibile (scaduta o mai generata).')); ?>,
        previewMinRemaining: <?php echo wp_json_encode($Tjs('min rimanenti')); ?>,
    };
    var monthNames = <?php echo wp_json_encode(array(
        $Tjs('Gennaio'),$Tjs('Febbraio'),$Tjs('Marzo'),$Tjs('Aprile'),$Tjs('Maggio'),$Tjs('Giugno'),
        $Tjs('Luglio'),$Tjs('Agosto'),$Tjs('Settembre'),$Tjs('Ottobre'),$Tjs('Novembre'),$Tjs('Dicembre')
    )); ?>;

    var state = {
        currentMonth: new Date().getMonth(),
        currentYear: new Date().getFullYear(),
        slots: [],
        stats: { planned: 0, drafts: 0, published_30d: 0 },
        categories: [],
        bulkStep: 1,
        bulkSuggestions: [],
        bulkSelected: [],
        // 3.35.0 — Preview & Refund flow state
        currentPreview: null, // {slot_id, generation_id, refundable_until, preview}
        refundCountdownTimer: null,
    };

    function $(id) { return document.getElementById(id); }
    function escHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function fmtDate(d) { return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()); }
    function ymdToDate(s) {
        var p = String(s).split(/[-T:]/);
        return new Date(parseInt(p[0],10), parseInt(p[1],10)-1, parseInt(p[2],10));
    }
    function isoToLocal(iso) {
        try { return new Date(iso); } catch(e) { return null; }
    }

    function toast(msg, kind) {
        var el = $('orch-cal-toast');
        el.textContent = msg;
        el.className = 'orch-cal-toast show ' + (kind || '');
        setTimeout(function(){ el.classList.remove('show'); }, 3000);
    }

    function ajax(action, data, cb, errCb) {
        data = data || {};
        data.action = action;
        data.nonce = nonce;
        var fd = new FormData();
        Object.keys(data).forEach(function(k) {
            var v = data[k];
            if (typeof v === 'object' && v !== null) v = JSON.stringify(v);
            if (v === true) v = '1';
            if (v === false) v = '';
            fd.append(k, v == null ? '' : v);
        });
        fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r){ return r.text(); })
            .then(function(t){
                var json;
                try { json = JSON.parse(t); } catch(e) { json = { error: 'Invalid JSON: ' + t.substring(0,200) }; }
                if (json && json.error && errCb) errCb(json);
                else if (cb) cb(json);
            })
            .catch(function(e){ if (errCb) errCb({ error: e.message }); else toast(T.errorGeneric + ': ' + e.message, 'error'); });
    }

    function loadCategories() {
        ajax('seo_aeo_orchestra_calendar_categories', {}, function(r) {
            if (r && r.success) {
                state.categories = r.categories || [];
                var sel = $('orch-cal-f-cat');
                sel.innerHTML = '<option value="0">— Nessuna —</option>';
                state.categories.forEach(function(c) {
                    var opt = document.createElement('option');
                    opt.value = c.id; opt.textContent = c.name;
                    sel.appendChild(opt);
                });
                var def = <?php echo absint($default_cat); ?>;
                if (def > 0) sel.value = def;
            }
        });
    }

    function loadSlots() {
        var fromD = new Date(state.currentYear, state.currentMonth - 1, 1);
        var toD = new Date(state.currentYear, state.currentMonth + 2, 0);
        ajax('seo_aeo_orchestra_calendar_list', { from: fmtDate(fromD), to: fmtDate(toD) }, function(r) {
            if (r && r.success) {
                state.slots = r.slots || [];
                state.stats = r.stats || state.stats;
                renderStats();
                renderGrid();
                renderUpcoming();
            }
        }, function(err) {
            toast((err && err.message) || (err && err.detail) || T.errorGeneric, 'error');
        });
    }

    function renderStats() {
        $('orch-cal-stat-planned').textContent = state.stats.planned || 0;
        $('orch-cal-stat-drafts').textContent = state.stats.drafts || 0;
        $('orch-cal-stat-pub').textContent = state.stats.published_30d || 0;
    }

    function getSlotsForDate(year, month, day) {
        var ymd = year + '-' + pad(month + 1) + '-' + pad(day);
        return state.slots.filter(function(s) {
            var d = isoToLocal(s.scheduled_at_utc);
            if (!d) return false;
            return d.getFullYear() === year && d.getMonth() === month && d.getDate() === day;
        });
    }

    function renderGrid() {
        var label = monthNames[state.currentMonth] + ' ' + state.currentYear;
        $('orch-cal-current-label').textContent = label;
        var grid = $('orch-cal-grid');
        grid.innerHTML = '';

        var first = new Date(state.currentYear, state.currentMonth, 1);
        var lastDay = new Date(state.currentYear, state.currentMonth + 1, 0).getDate();
        // Monday-first: getDay() returns 0=Sun..6=Sat
        var startWd = (first.getDay() + 6) % 7;
        var today = new Date(); today.setHours(0,0,0,0);

        // Leading from prev month
        var prevLast = new Date(state.currentYear, state.currentMonth, 0).getDate();
        for (var i = startWd; i > 0; i--) {
            var dnum = prevLast - i + 1;
            var cell = mkCell(state.currentYear, state.currentMonth - 1, dnum, true, today);
            grid.appendChild(cell);
        }
        for (var d = 1; d <= lastDay; d++) {
            var cell2 = mkCell(state.currentYear, state.currentMonth, d, false, today);
            grid.appendChild(cell2);
        }
        // Trailing to fill 42 cells
        var totalCells = startWd + lastDay;
        var trail = (7 - (totalCells % 7)) % 7;
        for (var t = 1; t <= trail; t++) {
            var cell3 = mkCell(state.currentYear, state.currentMonth + 1, t, true, today);
            grid.appendChild(cell3);
        }
    }

    function mkCell(year, month, day, otherMonth, today) {
        var realDate = new Date(year, month, day);
        var isToday = realDate.toDateString() === today.toDateString();
        var isPast = realDate < today;
        var c = document.createElement('div');
        c.className = 'orch-cal-cell' + (otherMonth ? ' other-month' : '') + (isToday ? ' today' : '') + (isPast ? ' past' : '');
        c.dataset.date = fmtDate(realDate);

        var num = document.createElement('div');
        num.className = 'orch-cal-day-num';
        num.textContent = day;
        c.appendChild(num);

        var slotsContainer = document.createElement('div');
        slotsContainer.className = 'orch-cal-slots';
        var slots = getSlotsForDate(year, month, day);
        var max = 3;
        slots.slice(0, max).forEach(function(s) {
            var sl = document.createElement('div');
            sl.className = 'orch-cal-slot s-' + (s.status || 'planned');
            sl.title = (s.topic || '') + ' — ' + (s.status || 'planned');
            var icon = ({planned:'📅',generating:'⏳',generated:'🤖',published:'✅',skipped:'⏸',error:'⚠️'})[s.status] || '📅';
            sl.textContent = icon + ' ' + (s.topic || '(no topic)');
            sl.addEventListener('click', function(e) {
                e.stopPropagation();
                openSlotModal(s);
            });
            slotsContainer.appendChild(sl);
        });
        if (slots.length > max) {
            var more = document.createElement('div');
            more.className = 'orch-cal-slot';
            more.style.fontStyle = 'italic';
            more.textContent = '+' + (slots.length - max) + ' …';
            slotsContainer.appendChild(more);
        }
        c.appendChild(slotsContainer);

        if (!isPast && !otherMonth) {
            c.addEventListener('click', function() {
                openSlotModal(null, fmtDate(realDate));
            });
        }
        return c;
    }

    function renderUpcoming() {
        var box = $('orch-cal-upcoming-list');
        var now = new Date();
        var in7 = new Date(); in7.setDate(in7.getDate() + 7);
        var upcoming = state.slots.filter(function(s) {
            var d = isoToLocal(s.scheduled_at_utc);
            if (!d) return false;
            return d >= now && d <= in7;
        }).sort(function(a,b){
            return isoToLocal(a.scheduled_at_utc) - isoToLocal(b.scheduled_at_utc);
        });
        if (!upcoming.length) {
            box.innerHTML = '<p style="color:#6b7280;font-size:13px;">' + escHtml(T.next7days) + '</p>';
            return;
        }
        var html = '';
        upcoming.forEach(function(s) {
            var d = isoToLocal(s.scheduled_at_utc);
            var when = d.toLocaleDateString(undefined, { weekday: 'short', day: '2-digit', month: '2-digit' }) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
            var icon = ({planned:'📅',generating:'⏳',generated:'🤖',published:'✅',skipped:'⏸',error:'⚠️'})[s.status] || '📅';
            var actions = '';
            if (s.status === 'planned') {
                actions += '<button class="orch-cal-btn secondary" data-act="generate-now" data-slot="' + escHtml(s.slot_id) + '">🤖 ' + escHtml(T.gen) + '</button>';
            } else if (s.status === 'generated' && s.post_id) {
                actions += '<button class="orch-cal-btn secondary" data-act="see-draft" data-post="' + s.post_id + '">👁 ' + escHtml(T.seeDraft) + '</button>';
                actions += '<button class="orch-cal-btn" data-act="publish-now" data-slot="' + escHtml(s.slot_id) + '" data-post="' + s.post_id + '">▶ Pubblica</button>';
            }
            actions += '<button class="orch-cal-btn secondary" data-act="edit" data-slot="' + escHtml(s.slot_id) + '">✏️</button>';
            actions += '<button class="orch-cal-btn danger" data-act="delete" data-slot="' + escHtml(s.slot_id) + '" data-post="' + (s.post_id||0) + '">🗑</button>';
            html += '<div class="item" data-slot-id="' + (s.slot_id || s.id || '') + '" data-status="' + (s.status || 'planned') + '">' +
                '<div class="info">' +
                  '<div class="when">' + escHtml(when) + '</div>' +
                  '<div class="what">' + icon + ' ' + escHtml(s.topic || '(senza topic)') + '</div>' +
                  '<div class="meta">' + (s.auto_publish ? '⚡ ' + escHtml(T.autopublishOn) : '⏸ ' + escHtml(T.autopublishOff)) + (s.keyword ? ' · KW: ' + escHtml(s.keyword) : '') + '</div>' +
                '</div>' +
                '<div class="actions">' + actions + '</div>' +
              '</div>';
        });
        box.innerHTML = html;
        // 3.35.5: re-inject checkbox dopo render (era via MutationObserver, ora esplicito)
        if (typeof injectBulkCheckboxes === 'function') { injectBulkCheckboxes(); updateBulkSelection(); }
        // Bind actions
        box.querySelectorAll('button[data-act]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var act = btn.dataset.act;
                var slotId = btn.dataset.slot;
                var postId = parseInt(btn.dataset.post || '0', 10);
                var slot = state.slots.find(function(x){ return x.slot_id === slotId; });
                if (act === 'edit' && slot) openSlotModal(slot);
                else if (act === 'delete') doDelete(slotId, postId);
                else if (act === 'generate-now') doGenerateNow(slotId);
                else if (act === 'publish-now') doPublishNow(slotId, postId);
                else if (act === 'see-draft' && postId) window.open('post.php?post=' + postId + '&action=edit', '_blank');
            });
        });
    }

    function openSlotModal(slot, dateStr) {
        // 3.34.1 — Reset preview state ad ogni apertura modal (era persistente cross-slot)
        var pvWrap = document.getElementById('orch-cal-quick-preview-wrap');
        var pvContent = document.getElementById('orch-cal-quick-preview-content');
        var pvTitle = document.getElementById('orch-cal-quick-preview-title');
        var pvMeta = document.getElementById('orch-cal-quick-preview-meta');
        var pvBtn = document.getElementById('orch-cal-modal-quick-preview');
        if (pvWrap) pvWrap.style.display = 'none';
        if (pvContent) { pvContent.innerHTML = ''; pvContent.style.display = 'none'; }
        if (pvTitle) { pvTitle.textContent = ''; pvTitle.style.display = 'none'; }
        if (pvMeta) { pvMeta.innerHTML = ''; pvMeta.style.display = 'none'; }
        if (pvBtn) { pvBtn.innerHTML = '\u{1F441} Anteprima rapida'; pvBtn.disabled = false; }

        var modal = $('orch-cal-modal-slot');
        if (slot) {
            $('orch-cal-modal-title').textContent = T.editSlot + ' — ' + (slot.topic || '');
            $('orch-cal-f-slot-id').value = slot.slot_id;
            $('orch-cal-f-topic').value = slot.topic || '';
            $('orch-cal-f-keyword').value = slot.keyword || '';
            $('orch-cal-f-hour').value = slot.hour != null ? slot.hour : <?php echo absint($default_hour); ?>;
            $('orch-cal-f-days').value = slot.generate_days_before != null ? slot.generate_days_before : <?php echo absint($default_days); ?>;
            $('orch-cal-f-cat').value = slot.category_id || 0;
            $('orch-cal-f-status').value = slot.status || 'planned';
            $('orch-cal-f-post-id').value = slot.post_id || 0;
            var d = isoToLocal(slot.scheduled_at_utc);
            $('orch-cal-f-date').value = d ? fmtDate(d) : '';
            var radios = document.getElementsByName('orch-cal-auto');
            for (var i = 0; i < radios.length; i++) radios[i].checked = (radios[i].value === (slot.auto_publish ? '1' : '0'));
            $('orch-cal-modal-delete').style.display = '';
            $('orch-cal-modal-generate-now').style.display = (slot.status === 'planned' || slot.status === 'error') ? '' : 'none';
            // 3.35.0 — Riprendi anteprima visibile solo se status=preview
            var rp = $('orch-cal-modal-resume-preview');
            if (rp) rp.style.display = (slot.status === 'preview') ? '' : 'none';
            $('orch-cal-modal-edit-draft').style.display = (slot.status === 'generated' && slot.post_id) ? '' : 'none';
            $('orch-cal-modal-quick-preview').style.display = (slot.status === 'generated' && slot.post_id) ? '' : 'none';
            $('orch-cal-modal-inject-image').style.display = 'none'; // 3.34.1 disabled
            $('orch-cal-modal-publish-now').style.display = (slot.status === 'generated' && slot.post_id) ? '' : 'none';
        } else {
            $('orch-cal-modal-title').textContent = T.sched + ' ' + (dateStr || '');
            $('orch-cal-f-slot-id').value = '';
            $('orch-cal-f-topic').value = '';
            $('orch-cal-f-keyword').value = '';
            $('orch-cal-f-hour').value = <?php echo absint($default_hour); ?>;
            $('orch-cal-f-days').value = <?php echo absint($default_days); ?>;
            $('orch-cal-f-cat').value = <?php echo absint($default_cat); ?>;
            $('orch-cal-f-status').value = 'planned';
            $('orch-cal-f-post-id').value = 0;
            $('orch-cal-f-date').value = dateStr || '';
            var radios2 = document.getElementsByName('orch-cal-auto');
            for (var j = 0; j < radios2.length; j++) radios2[j].checked = (radios2[j].value === (<?php echo $default_auto ? '1' : '0'; ?> ? '1' : '0'));
            $('orch-cal-modal-delete').style.display = 'none';
            $('orch-cal-modal-generate-now').style.display = 'none';
            var rp2 = $('orch-cal-modal-resume-preview');
            if (rp2) rp2.style.display = 'none';
            $('orch-cal-modal-edit-draft').style.display = 'none';
            $('orch-cal-modal-quick-preview').style.display = 'none';
            $('orch-cal-modal-inject-image').style.display = 'none';
            $('orch-cal-quick-preview-wrap').style.display = 'none';
            var qpf = $('orch-cal-quick-preview-frame');
            if (qpf && 'src' in qpf) { try { qpf.src = 'about:blank'; } catch(e) {} }
            $('orch-cal-modal-publish-now').style.display = 'none';
        }
        // 3.33.1: lock body scroll
        document.body.classList.add('orch-cal-modal-lock');
        modal.classList.add('open');
    }
    function closeSlotModal() {
        $('orch-cal-modal-slot').classList.remove('open');
        if (!document.querySelector('.orch-cal-modal.open')) document.body.classList.remove('orch-cal-modal-lock');
    }

    function getAutoVal() {
        var radios = document.getElementsByName('orch-cal-auto');
        for (var i = 0; i < radios.length; i++) if (radios[i].checked) return radios[i].value === '1';
        return true;
    }

    function doSaveSlot() {
        var topic = $('orch-cal-f-topic').value.trim();
        if (!topic) { toast(T.topicRequired, 'error'); return; }
        var date = $('orch-cal-f-date').value;
        var hour = parseInt($('orch-cal-f-hour').value, 10) || 9;
        var iso = date + 'T' + pad(hour) + ':00:00Z';
        var slotId = $('orch-cal-f-slot-id').value;
        var data = {
            topic: topic,
            keyword: $('orch-cal-f-keyword').value.trim(),
            hour: hour,
            generate_days_before: parseInt($('orch-cal-f-days').value, 10) || 0,
            auto_publish: getAutoVal(),
            category_id: parseInt($('orch-cal-f-cat').value, 10) || 0,
        };
        if (slotId) {
            data.slot_id = slotId;
            data.scheduled_at_utc = iso;
            ajax('seo_aeo_orchestra_calendar_update', data, function(r) {
                if (r && r.success) { toast(T.slotUpdated, 'success'); closeSlotModal(); loadSlots(); }
                else toast((r && r.error) || (r && r.detail) || T.errorGeneric, 'error');
            });
        } else {
            data.scheduled_at_utc = iso;
            ajax('seo_aeo_orchestra_calendar_add', data, function(r) {
                if (r && r.success) { toast(T.slotCreated, 'success'); closeSlotModal(); loadSlots(); }
                else toast((r && r.error) || (r && r.detail) || T.errorGeneric, 'error');
            });
        }
    }

    function doDelete(slotId, postId) {
        if (!confirm(T.confirmDelete)) return;
        var deletePost = false;
        if (postId && postId > 0) {
            deletePost = confirm(T.confirmDeletePost);
        }
        ajax('seo_aeo_orchestra_calendar_delete', { slot_id: slotId, delete_post: deletePost, post_id: postId || 0 }, function(r) {
            if (r && r.success) { toast(T.slotDeleted, 'success'); closeSlotModal(); loadSlots(); }
            else toast((r && r.error) || (r && r.detail) || T.errorGeneric, 'error');
        });
    }

    
        function refreshCreditPill() {
            // 3.35.1 — Aggiorna pill crediti in UI dopo generation/refund/commit
            try {
                jQuery.post(ajaxUrl, {
                    action: 'seo_aeo_orchestra_get_credits',
                    nonce: nonce
                }, function(r) {
                    if (r && (r.plan_credits !== undefined || r.total !== undefined)) {
                        var total = r.total !== undefined ? r.total : (parseInt(r.plan_credits||0) + parseInt(r.topup_credits||0));
                        // Aggiorna tutti i selector noti del pill
                        jQuery('#orch-cb-value, .orch-cb-value').text(total);
                        jQuery('#orch-topup-current-bal').text(total);
                        jQuery(document).trigger('seo_aeo_credits_updated', [total]);
                    }
                });
            } catch(e) { /* silent */ }
        }

        function doGenerateNow(slotId) {
        // 3.35.0 — Preview-first flow: backend genera, plugin salva in transient,
        // mostra modal preview con bottoni Accetta / Rigenera / Rimborsa.
        // 3.35.1 — Mostra info modal policy crediti+rimborso PRIMA (sostituisce JS confirm)
        var skipKey = 'seo_aeo_calendar_genconfirm_skipped';
        var alreadySkipped = false;
        try { alreadySkipped = localStorage.getItem(skipKey) === '1'; } catch(e) {}
        if (!alreadySkipped) {
            var modal = document.getElementById('orch-cal-modal-genconfirm');
            if (modal) {
                modal.classList.add('open');
                document.body.classList.add('orch-cal-modal-lock');
                var goBtn = document.getElementById('orch-cal-genconfirm-go');
                var cancelBtn = document.getElementById('orch-cal-genconfirm-cancel');
                var skipChk = document.getElementById('orch-cal-genconfirm-skip');
                var goNew = goBtn.cloneNode(true);
                var cancelNew = cancelBtn.cloneNode(true);
                goBtn.parentNode.replaceChild(goNew, goBtn);
                cancelBtn.parentNode.replaceChild(cancelNew, cancelBtn);
                var close = function() {
                    modal.classList.remove('open');
                    document.body.classList.remove('orch-cal-modal-lock');
                };
                goNew.addEventListener('click', function() {
                    if (skipChk && skipChk.checked) { try { localStorage.setItem(skipKey, '1'); } catch(e) {} }
                    close();
                    doGenerateNow_actual(slotId);
                });
                cancelNew.addEventListener('click', close);
                return;
            }
        }
        doGenerateNow_actual(slotId);
    }

    function doGenerateNow_actual(slotId) {
        var btnGen = $('orch-cal-modal-generate-now');
        var btnSave = $('orch-cal-modal-save');
        if (btnGen) { btnGen.disabled = true; btnGen.dataset._txt = btnGen.textContent; btnGen.textContent = '⏳ ' + T.previewLoading; }
        if (btnSave) btnSave.disabled = true;
        toast(T.previewLoading);
        ajax('seo_aeo_orchestra_calendar_generate_now', { slot_id: slotId }, function(r) {
            if (btnGen) { btnGen.disabled = false; if (btnGen.dataset._txt) btnGen.textContent = btnGen.dataset._txt; }
            if (btnSave) btnSave.disabled = false;
            if (r && r.success && r.preview) {
                toast(T.previewReady, 'success');
                openPreviewModal(slotId, r.preview, r.generation_id || '', r.refundable_until || '');
                refreshCreditPill();
                // Refresh background list (slot status changed → preview)
                loadSlots();
            } else {
                toast((r && r.error) || (r && r.detail) || T.errorGeneric, 'error');
            }
        }, function(err) {
            if (btnGen) { btnGen.disabled = false; if (btnGen.dataset._txt) btnGen.textContent = btnGen.dataset._txt; }
            if (btnSave) btnSave.disabled = false;
            toast((err && err.error) || (err && err.message) || T.errorGeneric, 'error');
        });
    }

    // 3.35.0 — Preview & Refund flow ===========================================

    function openPreviewModal(slotId, preview, generationId, refundableUntil) {
        state.currentPreview = {
            slot_id: slotId,
            generation_id: generationId,
            refundable_until: refundableUntil,
            preview: preview,
        };
        // Title
        $('orch-cal-preview-h1').textContent = preview.topic || preview.title || '';
        // Image (base64 from preview, NOT yet uploaded to Media Library)
        var imgWrap = $('orch-cal-preview-image');
        if (preview.image_base64) {
            var img = document.createElement('img');
            img.src = 'data:image/png;base64,' + preview.image_base64;
            img.alt = preview.topic || '';
            imgWrap.innerHTML = '';
            imgWrap.appendChild(img);
            imgWrap.style.display = '';
        } else {
            imgWrap.innerHTML = '';
            imgWrap.style.display = 'none';
        }
        // Body
        $('orch-cal-preview-content').innerHTML = preview.content_html || '';
        // Meta
        var metaWrap = $('orch-cal-preview-meta-wrap');
        var hasMeta = preview.meta_title || preview.meta_description || preview.focus_keyword;
        if (hasMeta) {
            $('orch-cal-preview-meta-title').textContent = preview.meta_title || '—';
            $('orch-cal-preview-meta-desc').textContent = preview.meta_description || '—';
            $('orch-cal-preview-meta-kw').textContent = preview.focus_keyword || preview.keyword || '—';
            $('orch-cal-preview-meta-wc').textContent = preview.word_count != null ? preview.word_count : '—';
            metaWrap.style.display = '';
        } else {
            metaWrap.style.display = 'none';
        }

        // Reset action buttons
        var bAcc = $('orch-cal-preview-accept');
        var bRef = $('orch-cal-preview-refund');
        var bReg = $('orch-cal-preview-regen');
        if (bAcc) { bAcc.disabled = false; bAcc.innerHTML = '✓ <?php echo esc_js(SEO_AEO_T::t('Accetta e crea draft')); ?>'; }
        if (bReg) { bReg.disabled = false; }

        // Refund countdown
        startRefundCountdown(refundableUntil);

        // Close slot modal, open preview modal
        closeSlotModal();
        document.body.classList.add('orch-cal-modal-lock');
        $('orch-cal-modal-preview').classList.add('open');
    }

    function closePreviewModal() {
        $('orch-cal-modal-preview').classList.remove('open');
        if (state.refundCountdownTimer) {
            clearInterval(state.refundCountdownTimer);
            state.refundCountdownTimer = null;
        }
        if (!document.querySelector('.orch-cal-modal.open')) document.body.classList.remove('orch-cal-modal-lock');
    }

    function startRefundCountdown(refundableUntilIso) {
        var btn = $('orch-cal-preview-refund');
        var span = $('orch-cal-preview-refund-window');
        if (state.refundCountdownTimer) {
            clearInterval(state.refundCountdownTimer);
            state.refundCountdownTimer = null;
        }
        if (!refundableUntilIso) {
            // Senza window info, lascia il bottone disabilitato (anteprima riaperta dopo expire)
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '🔒 ' + T.previewExpired;
            }
            if (span) span.textContent = T.previewExpired;
            return;
        }
        var until = Date.parse(refundableUntilIso);
        if (isNaN(until)) {
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '🔒 ' + T.previewExpired;
            }
            return;
        }
        function tick() {
            var rem = until - Date.now();
            if (rem <= 0) {
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '🔒 ' + T.previewExpired;
                }
                if (span) span.textContent = T.previewExpired;
                if (state.refundCountdownTimer) {
                    clearInterval(state.refundCountdownTimer);
                    state.refundCountdownTimer = null;
                }
                return;
            }
            var s = Math.ceil(rem / 1000);
            var m = Math.floor(s / 60);
            var ss = s - m * 60;
            var label = (m > 0 ? (m + ':' + (ss < 10 ? '0' + ss : ss)) : (s + 's'));
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '🔄 ' + T.previewRefundWindow + ' (' + label + ')';
            }
            if (span) {
                span.textContent = label + ' ' + T.previewMinRemaining;
            }
        }
        tick();
        state.refundCountdownTimer = setInterval(tick, 1000);
    }

    function doPreviewAccept() {
        var p = state.currentPreview;
        if (!p) return;
        var btn = $('orch-cal-preview-accept');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '⏳ ' + T.previewAcceptCreating;
        }
        ajax('seo_aeo_orchestra_calendar_preview_commit', { slot_id: p.slot_id }, function(r) {
            if (r && r.success) {
                toast(T.previewDraftCreated, 'success');
                refreshCreditPill();
                closePreviewModal();
                state.currentPreview = null;
                loadSlots();
            } else {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '✓ <?php echo esc_js(SEO_AEO_T::t('Accetta e crea draft')); ?>';
                }
                toast((r && r.error) || (r && r.detail) || T.errorGeneric, 'error');
            }
        }, function(err) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '✓ <?php echo esc_js(SEO_AEO_T::t('Accetta e crea draft')); ?>';
            }
            toast((err && err.error) || (err && err.message) || T.errorGeneric, 'error');
        });
    }

    function doPreviewRefund() {
        var p = state.currentPreview;
        if (!p) return;
        if (!p.generation_id) {
            toast(T.previewExpiredCannotRefund, 'error');
            return;
        }
        if (!confirm(T.previewRefundConfirm)) return;
        var btn = $('orch-cal-preview-refund');
        if (btn) btn.disabled = true;
        ajax('seo_aeo_orchestra_calendar_preview_refund', {
            slot_id: p.slot_id,
            generation_id: p.generation_id,
            reason: 'user_dismissed',
        }, function(r) {
            if (r && r.success) {
                var msg = T.previewRefunded + ': ' + (r.refunded || 0) + ' cr · ' + (r.refunds_remaining_today || 0) + ' ' + T.previewRefundsRemaining;
                toast(msg, 'success');
                    refreshCreditPill();
                closePreviewModal();
                state.currentPreview = null;
                loadSlots();
            } else {
                if (btn) btn.disabled = false;
                toast((r && r.error) || (r && r.detail) || T.errorGeneric, 'error');
            }
        }, function(err) {
            if (btn) btn.disabled = false;
            toast((err && err.error) || (err && err.message) || T.errorGeneric, 'error');
        });
    }

    function doPreviewRegen() {
        var p = state.currentPreview;
        if (!p) return;
        if (!confirm(T.previewRegenConfirm)) return;
        var slotId = p.slot_id;
        // Refund della generation corrente (se ancora valida) + chiama Genera ora.
        // Approccio: se generation_id ancora rimborsabile, refund prima → libera transient,
        // poi rigenera. Se window scaduta, rigenera comunque (transient verra sovrascritto
        // ma niente refund).
        if (p.generation_id && state.refundCountdownTimer) {
            ajax('seo_aeo_orchestra_calendar_preview_refund', {
                slot_id: slotId,
                generation_id: p.generation_id,
                reason: 'user_regenerate',
            }, function() {
                closePreviewModal();
                state.currentPreview = null;
                doGenerateNow(slotId);
            }, function() {
                closePreviewModal();
                state.currentPreview = null;
                doGenerateNow(slotId);
            });
        } else {
            closePreviewModal();
            state.currentPreview = null;
            doGenerateNow(slotId);
        }
    }

    function doResumePreview(slotId) {
        ajax('seo_aeo_orchestra_calendar_preview_load', { slot_id: slotId }, function(r) {
            if (r && r.success && r.preview) {
                openPreviewModal(slotId, r.preview, r.generation_id || '', r.refundable_until || '');
            } else {
                toast((r && r.error) || T.previewLoadFailed, 'error');
            }
        });
    }

    function doPublishNow(slotId, postId) {
        if (!confirm(T.confirmPublishNow)) return;
        ajax('seo_aeo_orchestra_calendar_publish_now', { slot_id: slotId, post_id: postId }, function(r) {
            if (r && r.success) { toast(T.publishedOk, 'success'); closeSlotModal(); loadSlots(); }
            else toast((r && r.error) || T.errorGeneric, 'error');
        });
    }

    // Bulk wizard
    function setBulkStep(n) {
        state.bulkStep = n;
        document.querySelectorAll('.orch-cal-stepper .step').forEach(function(s) {
            var sn = parseInt(s.dataset.step, 10);
            s.classList.toggle('active', sn === n);
            s.classList.toggle('done', sn < n);
        });
        document.querySelectorAll('[data-bulk-step]').forEach(function(el) {
            el.style.display = (parseInt(el.dataset.bulkStep, 10) === n) ? '' : 'none';
        });
        $('orch-cal-bulk-confirm').style.display = (n === 5) ? '' : 'none';
        $('orch-cal-bulk-next').style.display = (n === 5) ? 'none' : '';
        $('orch-cal-bulk-prev').style.display = (n > 1) ? '' : 'none';
    }

    function openBulk() {
        state.bulkStep = 1;
        state.bulkSuggestions = [];
        state.bulkSelected = [];
        $('orch-cal-bulk-niche').value = '';
        $('orch-cal-bulk-n').value = '30';
        var def = new Date(); def.setDate(def.getDate() + 1);
        $('orch-cal-bulk-start').value = fmtDate(def);
        setBulkStep(1);
        // 3.33.1: lock body scroll prima di aprire modal (evita 'pagina spagina')
        document.body.classList.add('orch-cal-modal-lock');
        $('orch-cal-modal-bulk').classList.add('open');
    }
    function closeBulk() {
        $('orch-cal-modal-bulk').classList.remove('open');
        // 3.33.1: rilascia scroll-lock se nessun altro modal e aperto
        if (!document.querySelector('.orch-cal-modal.open')) document.body.classList.remove('orch-cal-modal-lock');
    }

    function bulkNext() {
        if (state.bulkStep === 1) {
            // Trigger AI suggest
            setBulkStep(2);
            ajax('seo_aeo_orchestra_calendar_suggest', {
                n_articles: parseInt($('orch-cal-bulk-n').value, 10) || 7,
                niche: $('orch-cal-bulk-niche').value.trim(),
            }, function(r) {
                if (r && r.success && r.suggestions && r.suggestions.length) {
                    state.bulkSuggestions = r.suggestions;
                    renderBulkSuggestions();
                    setBulkStep(3);
                } else {
                    toast(T.emptySuggestions, 'error');
                    setBulkStep(1);
                }
            }, function(err) {
                toast((err && err.error) || (err && err.message) || (err && err.detail) || T.errorGeneric, 'error');
                setBulkStep(1);
            });
        } else if (state.bulkStep === 3) {
            // Collect selected
            state.bulkSelected = [];
            document.querySelectorAll('#orch-cal-suggestions input[type=checkbox]:checked').forEach(function(cb) {
                var idx = parseInt(cb.dataset.idx, 10);
                var s = state.bulkSuggestions[idx];
                if (s) state.bulkSelected.push(s);
            });
            if (!state.bulkSelected.length) { toast('Seleziona almeno un topic', 'error'); return; }
            setBulkStep(4);
        } else if (state.bulkStep === 4) {
            renderBulkSummary();
            setBulkStep(5);
        }
    }
    function bulkPrev() { if (state.bulkStep > 1) setBulkStep(state.bulkStep - 1); }

    function renderBulkSuggestions() {
        var box = $('orch-cal-suggestions');
        var html = '';
        state.bulkSuggestions.forEach(function(s, i) {
            html += '<div class="sugg">' +
                '<input type="checkbox" data-idx="' + i + '" checked />' +
                '<div class="stxt">' +
                  '<div class="t">' + escHtml(s.topic) + '</div>' +
                  (s.keyword ? '<div class="k">KW: ' + escHtml(s.keyword) + '</div>' : '') +
                  (s.rationale ? '<div class="r">' + escHtml(s.rationale) + '</div>' : '') +
                '</div>' +
              '</div>';
        });
        box.innerHTML = html;
    }

    function renderBulkSummary() {
        var n = state.bulkSelected.length;
        var spread = $('orch-cal-bulk-spread').value;
        var auto = $('orch-cal-bulk-auto').checked;
        var totalCredits = n * 25;
        var html = '<strong>' + n + '</strong> articoli verranno pianificati<br>';
        html += 'Distribuzione: ' + ({
                'daily':'1/giorno',
                '2_per_week':'2/settimana',
                '3_per_week':'3/settimana',
                'weekly':'1/settimana'
            }[spread] || spread) + '<br>';
        html += 'Auto-pubblica: ' + (auto ? 'sì' : 'no') + '<br>';
        html += 'Costo stimato totale: <strong>~' + totalCredits + ' crediti</strong> (al momento della generazione automatica)';
        $('orch-cal-bulk-summary').innerHTML = html;
    }

    function doBulkConfirm() {
        var items = state.bulkSelected.map(function(s){ return { topic: s.topic, keyword: s.keyword || '' }; });
        ajax('seo_aeo_orchestra_calendar_bulk_create', {
            items: items,
            start_date: $('orch-cal-bulk-start').value,
            spread: $('orch-cal-bulk-spread').value,
            auto_publish: $('orch-cal-bulk-auto').checked,
        }, function(r) {
            if (r && r.success) {
                toast(T.bulkCreated + ': ' + r.created, 'success');
                closeBulk();
                loadSlots();
            } else toast((r && r.error) || T.errorGeneric, 'error');
        });
    }

    // Wire up
    document.addEventListener('DOMContentLoaded', function() {
        $('orch-cal-prev').addEventListener('click', function(){
            state.currentMonth--;
            if (state.currentMonth < 0) { state.currentMonth = 11; state.currentYear--; }
            loadSlots();
        });
        $('orch-cal-next').addEventListener('click', function(){
            state.currentMonth++;
            if (state.currentMonth > 11) { state.currentMonth = 0; state.currentYear++; }
            loadSlots();
        });
        $('orch-cal-today').addEventListener('click', function(){
            var t = new Date();
            state.currentMonth = t.getMonth();
            state.currentYear = t.getFullYear();
            loadSlots();
        });
        $('orch-cal-refresh-btn').addEventListener('click', loadSlots);
        $('orch-cal-bulk-btn').addEventListener('click', openBulk);

        $('orch-cal-modal-cancel').addEventListener('click', closeSlotModal);
        $('orch-cal-modal-save').addEventListener('click', doSaveSlot);
        $('orch-cal-modal-delete').addEventListener('click', function() {
            var sid = $('orch-cal-f-slot-id').value;
            var pid = parseInt($('orch-cal-f-post-id').value, 10) || 0;
            if (sid) doDelete(sid, pid);
        });
        $('orch-cal-modal-generate-now').addEventListener('click', function() {
            var sid = $('orch-cal-f-slot-id').value;
            if (sid) doGenerateNow(sid);
        });
        $('orch-cal-modal-edit-draft').addEventListener('click', function() {
            var pid = parseInt($('orch-cal-f-post-id').value, 10) || 0;
            if (pid) window.open('post.php?post=' + pid + '&action=edit', '_blank');
        });
        $('orch-cal-modal-quick-preview').addEventListener('click', function() {
            var pid = parseInt($('orch-cal-f-post-id').value, 10) || 0;
            if (!pid) return;
            var btn = this;
            var wrap  = $('orch-cal-quick-preview-wrap');
            var loading = $('orch-cal-quick-preview-loading');
            var titleEl = $('orch-cal-quick-preview-title');
            var metaEl = $('orch-cal-quick-preview-meta');
            var contentEl = $('orch-cal-quick-preview-content');
            if (wrap.style.display === 'none' || !wrap.style.display) {
                btn.disabled = true;
                btn.innerHTML = '⏳ Caricamento...';
                wrap.style.display = 'block';
                loading.style.display = 'block';
                titleEl.style.display = 'none';
                metaEl.style.display = 'none';
                contentEl.style.display = 'none';
                ajax('seo_aeo_orchestra_calendar_quick_preview', { post_id: pid }, function(r) {
                    btn.disabled = false;
                    btn.innerHTML = '✕ Chiudi anteprima';
                    loading.style.display = 'none';
                    if (r && r.success) {
                        titleEl.textContent = r.title || '';
                        titleEl.style.display = 'block';
                        var metaParts = [];
                        if (r.status_label) metaParts.push('Stato: ' + r.status_label);
                        if (r.scheduled_at) metaParts.push('Programmato: ' + r.scheduled_at);
                        if (r.has_image) metaParts.push('🖼 con immagine');
                        if (r.word_count) metaParts.push(r.word_count + ' parole');
                        metaEl.innerHTML = metaParts.join(' · ');
                        metaEl.style.display = 'block';
                        contentEl.innerHTML = r.content_html || '';
                        contentEl.style.display = 'block';
                    } else {
                        contentEl.innerHTML = '<div style="color:#dc2626;padding:20px;text-align:center;">⚠️ ' + (r && r.error ? r.error : 'Errore caricamento anteprima') + '</div>';
                        contentEl.style.display = 'block';
                    }
                });
            } else {
                wrap.style.display = 'none';
                this.innerHTML = '👁 Anteprima rapida';
            }
        });
        $('orch-cal-modal-inject-image').addEventListener('click', function() {
            var pid = parseInt($('orch-cal-f-post-id').value, 10) || 0;
            if (!pid) return;
            var btn = this;
            btn.disabled = true;
            var origLabel = btn.innerHTML;
            btn.innerHTML = '⏳ Iniezione...';
            ajax('seo_aeo_orchestra_calendar_inject_image', { post_id: pid }, function(r) {
                btn.disabled = false;
                btn.innerHTML = origLabel;
                if (r && r.success) {
                    if (r.already_present) {
                        toast('Immagine gia\' presente nel contenuto', 'success');
                    } else {
                        toast('Immagine iniettata in cima al contenuto ✓', 'success');
                    }
                    // Refresh iframe se aperto
                    var wrap = $('orch-cal-quick-preview-wrap');
                    if (wrap.style.display === 'block') {
                        $('orch-cal-modal-quick-preview').click(); // chiude
                        setTimeout(function() { $('orch-cal-modal-quick-preview').click(); }, 200); // riapre
                    }
                } else {
                    toast(r && r.error ? r.error : 'Errore iniezione', 'error');
                }
            });
        });
        $('orch-cal-modal-publish-now').addEventListener('click', function() {
            var sid = $('orch-cal-f-slot-id').value;
            var pid = parseInt($('orch-cal-f-post-id').value, 10) || 0;
            if (sid && pid) doPublishNow(sid, pid);
        });
        $('orch-cal-modal-slot').addEventListener('click', function(e) {
            if (e.target === this) closeSlotModal();
        });

        // 3.35.0 — Preview modal wiring
        var btnResume = $('orch-cal-modal-resume-preview');
        if (btnResume) {
            btnResume.addEventListener('click', function() {
                var sid = $('orch-cal-f-slot-id').value;
                if (sid) doResumePreview(sid);
            });
        }
        var btnPAccept = $('orch-cal-preview-accept');
        var btnPRefund = $('orch-cal-preview-refund');
        var btnPRegen  = $('orch-cal-preview-regen');
        var btnPCancel = $('orch-cal-preview-cancel');
        if (btnPAccept) btnPAccept.addEventListener('click', doPreviewAccept);
        if (btnPRefund) btnPRefund.addEventListener('click', doPreviewRefund);
        if (btnPRegen)  btnPRegen.addEventListener('click', doPreviewRegen);
        if (btnPCancel) btnPCancel.addEventListener('click', closePreviewModal);
        var pvModal = $('orch-cal-modal-preview');
        if (pvModal) {
            pvModal.addEventListener('click', function(e) {
                if (e.target === this) closePreviewModal();
            });
        }

        $('orch-cal-bulk-cancel').addEventListener('click', closeBulk);
        $('orch-cal-bulk-next').addEventListener('click', bulkNext);
        $('orch-cal-bulk-prev').addEventListener('click', bulkPrev);
        $('orch-cal-bulk-confirm').addEventListener('click', doBulkConfirm);
        $('orch-cal-modal-bulk').addEventListener('click', function(e) {
            if (e.target === this) closeBulk();
        });

        loadCategories();
        loadSlots();
    });
        // 3.35.2 — Iniezione checkbox bulk delete su slot pianificati nella upcoming list
        function injectBulkCheckboxes() {
            var items = document.querySelectorAll('.orch-cal-upcoming .item');
            items.forEach(function(it) {
                if (it.querySelector('.orch-bulk-cb')) return; // già presente
                var when = it.querySelector('.when');
                var status = it.dataset.status || '';
                // Inserisci solo per planned (preview/generated/published non eliminabili in bulk)
                if (status && status !== 'planned') return;
                var slotId = it.dataset.slotId || '';
                if (!slotId) return;
                var cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.className = 'orch-bulk-cb';
                cb.dataset.slotId = slotId;
                cb.style.cssText = 'margin-right:10px;cursor:pointer;flex-shrink:0;';
                cb.addEventListener('change', updateBulkSelection);
                cb.addEventListener('click', function(e) { e.stopPropagation(); });
                it.insertBefore(cb, it.firstChild);
            });
        }

        function updateBulkSelection() {
            var checked = document.querySelectorAll('.orch-bulk-cb:checked');
            var btn = document.getElementById('orch-cal-bulk-delete-btn');
            var label = document.getElementById('orch-cal-bulk-delete-label');
            if (checked.length > 0) {
                btn.style.display = '';
                if (label) label.textContent = (T.bulkDeleteN || 'Elimina selezionati') + ' (' + checked.length + ')';
            } else {
                btn.style.display = 'none';
            }
        }

        function bulkDeleteSelected() {
            var checked = document.querySelectorAll('.orch-bulk-cb:checked');
            if (!checked.length) return;
            var ids = Array.from(checked).map(function(c) { return c.dataset.slotId; });
            if (!confirm((T.bulkDeleteConfirm || 'Eliminare') + ' ' + ids.length + ' ' + (T.bulkDeleteConfirmTail || 'slot pianificati?'))) return;
            var btn = document.getElementById('orch-cal-bulk-delete-btn');
            var label = document.getElementById('orch-cal-bulk-delete-label');
            var origLabel = label ? label.textContent : 'Elimina selezionati';
            btn.disabled = true;
            btn.innerHTML = '⏳ ...';
            // 3.35.7: reset state helper (chiamato sia su success che error)
            var resetBtn = function() {
                btn.disabled = false;
                btn.innerHTML = '🗑 <span id="orch-cal-bulk-delete-label">' + (T.bulkDelete || origLabel || 'Elimina selezionati') + '</span>';
                // Reset select-all checkbox + nascondi bottone se nessuna selezione
                var sa = document.getElementById('orch-cal-bulk-select-all');
                if (sa) sa.checked = false;
                if (typeof updateBulkSelection === 'function') updateBulkSelection();
            };
            ajax('seo_aeo_orchestra_calendar_bulk_delete', { slot_ids: ids }, function(r) {
                if (r && r.success) {
                    toast((T.bulkDeleteDone || 'Slot eliminati') + ': ' + (r.deleted || 0), 'success');
                    loadSlots();
                } else {
                    toast((r && r.error) || 'Errore eliminazione', 'error');
                }
                resetBtn();
            }, function(err) {
                toast((err && err.message) || 'Errore di rete', 'error');
                resetBtn();
            });
        }

        // Hook su un mutation observer di .orch-cal-upcoming per ri-iniettare checkbox dopo re-render
        document.addEventListener('DOMContentLoaded', function() {
            var deleteBtn = document.getElementById('orch-cal-bulk-delete-btn');
            if (deleteBtn) deleteBtn.addEventListener('click', bulkDeleteSelected);
            var selectAll = document.getElementById('orch-cal-bulk-select-all');
            if (selectAll) selectAll.addEventListener('change', function() {
                document.querySelectorAll('.orch-bulk-cb').forEach(function(c) { c.checked = selectAll.checked; });
                updateBulkSelection();
            });
            var upcoming = document.querySelector('.orch-cal-upcoming');
            if (upcoming) {
                // 3.35.5: MutationObserver removed (interferiva con click handler)
                // 3.35.5: observer.observe removed
                injectBulkCheckboxes();
            }
        });
        // 3.35.2 — Iniezione checkbox bulk delete su slot pianificati nella upcoming list
        function injectBulkCheckboxes() {
            var items = document.querySelectorAll('.orch-cal-upcoming .item');
            items.forEach(function(it) {
                if (it.querySelector('.orch-bulk-cb')) return; // già presente
                var when = it.querySelector('.when');
                var status = it.dataset.status || '';
                // Inserisci solo per planned (preview/generated/published non eliminabili in bulk)
                if (status && status !== 'planned') return;
                var slotId = it.dataset.slotId || '';
                if (!slotId) return;
                var cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.className = 'orch-bulk-cb';
                cb.dataset.slotId = slotId;
                cb.style.cssText = 'margin-right:10px;cursor:pointer;flex-shrink:0;';
                cb.addEventListener('change', updateBulkSelection);
                cb.addEventListener('click', function(e) { e.stopPropagation(); });
                it.insertBefore(cb, it.firstChild);
            });
        }

        function updateBulkSelection() {
            var checked = document.querySelectorAll('.orch-bulk-cb:checked');
            var btn = document.getElementById('orch-cal-bulk-delete-btn');
            var label = document.getElementById('orch-cal-bulk-delete-label');
            if (checked.length > 0) {
                btn.style.display = '';
                if (label) label.textContent = (T.bulkDeleteN || 'Elimina selezionati') + ' (' + checked.length + ')';
            } else {
                btn.style.display = 'none';
            }
        }

        function bulkDeleteSelected() {
            var checked = document.querySelectorAll('.orch-bulk-cb:checked');
            if (!checked.length) return;
            var ids = Array.from(checked).map(function(c) { return c.dataset.slotId; });
            if (!confirm((T.bulkDeleteConfirm || 'Eliminare') + ' ' + ids.length + ' ' + (T.bulkDeleteConfirmTail || 'slot pianificati?'))) return;
            var btn = document.getElementById('orch-cal-bulk-delete-btn');
            btn.disabled = true;
            btn.innerHTML = '⏳ ...';
            ajax('seo_aeo_orchestra_calendar_bulk_delete', { slot_ids: ids }, function(r) {
                btn.disabled = false;
                if (r && r.success) {
                    toast(T.bulkDeleteDone || 'Slot eliminati: ' + r.deleted, 'success');
                    loadSlots();
                } else {
                    toast((r && r.error) || 'Errore eliminazione', 'error');
                }
            });
        }

        // Hook su un mutation observer di .orch-cal-upcoming per ri-iniettare checkbox dopo re-render
        document.addEventListener('DOMContentLoaded', function() {
            var deleteBtn = document.getElementById('orch-cal-bulk-delete-btn');
            if (deleteBtn) deleteBtn.addEventListener('click', bulkDeleteSelected);
            var selectAll = document.getElementById('orch-cal-bulk-select-all');
            if (selectAll) selectAll.addEventListener('change', function() {
                document.querySelectorAll('.orch-bulk-cb').forEach(function(c) { c.checked = selectAll.checked; });
                updateBulkSelection();
            });
            var upcoming = document.querySelector('.orch-cal-upcoming');
            if (upcoming) {
                // 3.35.5: MutationObserver removed (interferiva con click handler)
                // 3.35.5: observer.observe removed
                injectBulkCheckboxes();
            }
        });


})();
</script>

<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.VariableNotPrefixed
// Reason: template scope. Variables are local to this include/template,
// passed by the calling function via include/require. The Plugin Check
// heuristic doesn't distinguish template-scope locals from globals.
/*
 * 3.35.83-beta — Business Profile pannello dedicato
 *
 * URL: ?page=seo-aeo-business-profile (registered in class-admin-ui.php position 15)
 * Render: SEO_AEO_Business_Profile::render_page() -> requires this template
 *
 * 3 sezioni: 🌐 Public + 🔒 Internal + 👁 Preview Context AI
 * Auto-save 800ms debounce. Validation real-time. Tag chips. Repeatable struct cards.
 * Section state cross-session via wp_user_meta.
 */

if (!defined('ABSPATH')) exit;

$bp_section_states = get_user_meta(get_current_user_id(), 'seo_aeo_bp_section_states', true);
if (!is_array($bp_section_states)) $bp_section_states = array();
$is_section_open = function($key, $default = true) use ($bp_section_states) {
    return isset($bp_section_states[$key]) ? !empty($bp_section_states[$key]) : $default;
};
?>

<?php ob_start(); ?>
:root {
    --orch-ink:        #0f172a;
    --orch-muted:      #475569;
    --orch-subtle:     #64748b;
    --orch-faint:      #94a3b8;
    --orch-line:       #e4e4e7;
    --orch-line-soft:  #f1f5f9;
    --orch-bg:         #fafafa;
    --orch-good:       #0f766e;
    --orch-warn:       #d97706;
    --orch-bad:        #b91c1c;
    --orch-info:       #2563eb;
    --orch-accent:     #0f172a;

    /* .83 visibility scope */
    --bp-public-bg:        #ffffff;
    --bp-public-accent:    #16a34a;
    --bp-public-tint:      #f0fdf4;
    --bp-public-text:      #14532d;
    --bp-internal-bg:      #f8fafc;
    --bp-internal-accent:  #d97706;
    --bp-internal-tint:    #fffbeb;
    --bp-internal-text:    #92400e;
    --bp-preview-bg:       #0f172a;
    --bp-preview-text:     #e5e7eb;
}

.orch-bp-wrap { max-width: 1100px; margin: 12px 16px 80px; }
.orch-bp-wrap h1 { font-size: 24px; font-weight: 700; color: var(--orch-ink); margin: 12px 0 6px; }
.orch-bp-intro { color: var(--orch-muted); font-size: 14px; margin: 0 0 16px; line-height: 1.55; }

/* Completion header */
.orch-bp-completion {
    background: var(--orch-bg);
    border: 1px solid var(--orch-line);
    border-radius: 8px;
    padding: 14px 18px;
    margin-bottom: 16px;
}
.orch-bp-completion-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    font-size: 13px;
    color: var(--orch-muted);
}
.orch-bp-completion-stats strong { color: var(--orch-ink); font-size: 14px; }
.orch-bp-completion-bar { height: 8px; background: var(--orch-line-soft); border-radius: 4px; overflow: hidden; margin: 10px 0; }
.orch-bp-completion-bar-fill { height: 100%; background: linear-gradient(90deg, var(--bp-public-accent), var(--orch-info)); transition: width 0.3s; }
.orch-bp-completion-status { font-size: 12px; color: var(--orch-warn); margin-top: 6px; }
.orch-bp-completion-status--confirmed { color: var(--bp-public-accent); }

/* Sections */
.orch-bp-section {
    margin: 14px 0;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid var(--orch-line);
}
.orch-bp-section--public { background: var(--bp-public-bg); border-left: 4px solid var(--bp-public-accent); }
.orch-bp-section--internal { background: var(--bp-internal-bg); border-left: 4px solid var(--bp-internal-accent); }
.orch-bp-section--site-context { background: #fffbeb; border-left: 4px solid #d97706; }
.orch-bp-section-badge--site-context { background: #fef3c7; color: #92400e; }
.orch-bp-sc-status { padding: 10px 12px; border-radius: 6px; margin-top: 10px; font-size: 13px; }
.orch-bp-sc-status.is-loading { background: #eff6ff; color: #1e40af; border-left: 3px solid #3b82f6; }
.orch-bp-sc-status.is-ok { background: #ecfdf5; color: #065f46; border-left: 3px solid #10b981; }
.orch-bp-sc-status.is-err { background: #fef2f2; color: #991b1b; border-left: 3px solid #ef4444; }
.orch-bp-term-row { display: grid; grid-template-columns: 1fr 1.4fr 1.4fr auto; gap: 8px; margin-bottom: 8px; align-items: start; }
.orch-bp-term-row input { padding: 6px 8px; font-size: 13px; border: 1px solid #d1d5db; border-radius: 4px; }
.orch-bp-term-row .orch-bp-term-remove { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 13px; }
.orch-bp-term-row .orch-bp-term-remove:hover { background: #fee2e2; }
@media (max-width: 720px) {
    .orch-bp-term-row { grid-template-columns: 1fr; }
}
.orch-bp-section--preview { background: var(--bp-preview-bg); color: var(--bp-preview-text); border-left: 4px solid var(--orch-accent); }

.orch-bp-section-head {
    cursor: pointer;
    padding: 14px 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    list-style: none;
    user-select: none;
}
.orch-bp-section-head::-webkit-details-marker { display: none; }
.orch-bp-section-head::before { content: '▶'; font-size: 11px; color: var(--orch-faint); transition: transform 0.2s; }
.orch-bp-section[open] > .orch-bp-section-head::before { transform: rotate(90deg); }
.orch-bp-section--preview .orch-bp-section-head::before { color: rgba(255,255,255,0.5); }

.orch-bp-section-badge {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.orch-bp-section--public .orch-bp-section-badge { background: var(--bp-public-tint); color: var(--bp-public-text); }
.orch-bp-section--internal .orch-bp-section-badge { background: var(--bp-internal-tint); color: var(--bp-internal-text); }
.orch-bp-section--preview .orch-bp-section-badge { background: rgba(255,255,255,0.12); color: var(--bp-preview-text); }

.orch-bp-section-body { padding: 14px 18px; }
.orch-bp-section-info {
    background: rgba(255,255,255,0.4);
    padding: 10px 14px;
    border-radius: 6px;
    font-size: 12px;
    color: var(--orch-muted);
    margin-bottom: 14px;
}
.orch-bp-section--internal .orch-bp-section-info { background: rgba(255,255,255,0.65); }
.orch-bp-section-info ul { margin: 6px 0 0 18px; padding: 0; }
.orch-bp-section-info li { margin: 2px 0; }

/* Field rows */
.orch-bp-field {
    margin: 10px 0;
    padding: 10px 14px;
    background: rgba(255,255,255,0.5);
    border-radius: 6px;
    border: 1px solid var(--orch-line-soft);
}
.orch-bp-section--internal .orch-bp-field { background: rgba(255,255,255,0.65); }
.orch-bp-field-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--orch-ink);
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 6px;
    gap: 8px;
}
.orch-bp-field-icon { font-size: 12px; }
.orch-bp-field-required::after { content: ' *'; color: var(--orch-bad); }
.orch-bp-field-help { font-size: 11px; color: var(--orch-subtle); margin: 4px 0; line-height: 1.4; }
.orch-bp-input, .orch-bp-textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid var(--orch-line);
    border-radius: 6px;
    font-size: 13px;
    color: var(--orch-ink);
    background: #ffffff;
    box-sizing: border-box;
    font-family: inherit;
}
.orch-bp-textarea { resize: vertical; min-height: 60px; line-height: 1.5; }
.orch-bp-input:focus, .orch-bp-textarea:focus {
    outline: none;
    border-color: var(--bp-public-accent);
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.12);
}
.orch-bp-section--internal .orch-bp-input:focus, .orch-bp-section--internal .orch-bp-textarea:focus {
    border-color: var(--bp-internal-accent);
    box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.12);
}
.orch-bp-counter { font-size: 11px; color: var(--orch-faint); margin-top: 4px; text-align: right; }
.orch-bp-counter--invalid { color: var(--orch-bad); }
.orch-bp-counter--valid { color: var(--orch-good); }

/* Tag chips */
.orch-bp-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin: 6px 0;
    align-items: center;
    min-height: 32px;
}
.orch-bp-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    background: var(--bp-public-tint);
    border: 1px solid var(--bp-public-accent);
    color: var(--bp-public-text);
    border-radius: 14px;
    font-size: 12px;
}
.orch-bp-section--internal .orch-bp-chip {
    background: var(--bp-internal-tint);
    border-color: var(--bp-internal-accent);
    color: var(--bp-internal-text);
}
.orch-bp-chip-remove {
    background: transparent;
    border: 0;
    font-size: 16px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    opacity: 0.6;
    color: inherit;
}
.orch-bp-chip-remove:hover { opacity: 1; }
.orch-bp-chip-input {
    flex: 1;
    min-width: 200px;
    padding: 6px 10px;
    border: 1px dashed var(--orch-line);
    border-radius: 14px;
    font-size: 12px;
    background: transparent;
    outline: none;
}

/* Repeatable struct cards (differentiators, use_cases) */
.orch-bp-repeat-card {
    background: var(--orch-bg);
    border: 1px solid var(--orch-line);
    border-radius: 6px;
    padding: 10px 14px;
    margin: 8px 0;
}
.orch-bp-repeat-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 11px;
    color: var(--orch-faint);
    margin-bottom: 6px;
}
.orch-bp-repeat-actions { display: flex; gap: 4px; }
.orch-bp-repeat-actions button {
    background: transparent;
    border: 0;
    cursor: pointer;
    color: var(--orch-muted);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 14px;
}
.orch-bp-repeat-actions button:hover { color: var(--orch-ink); background: rgba(0,0,0,0.05); }
.orch-bp-repeat-actions .orch-bp-repeat-remove:hover { color: var(--orch-bad); }
.orch-bp-repeat-card label {
    font-size: 11px;
    font-weight: 600;
    color: var(--orch-muted);
    display: block;
    margin: 6px 0 2px;
}
.orch-bp-add-btn {
    margin-top: 6px;
    background: transparent;
    border: 1px dashed var(--orch-line);
    color: var(--orch-info);
    padding: 8px 14px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
    width: 100%;
}
.orch-bp-add-btn:hover { border-color: var(--orch-info); background: rgba(37, 99, 235, 0.06); }

/* Collapsible repeat groups (within section) */
.orch-bp-collapse {
    margin: 10px 0;
    padding: 8px 14px;
    background: rgba(255,255,255,0.5);
    border-radius: 6px;
    border: 1px solid var(--orch-line-soft);
}
.orch-bp-section--internal .orch-bp-collapse { background: rgba(255,255,255,0.65); }
.orch-bp-collapse > summary {
    cursor: pointer;
    list-style: none;
    user-select: none;
    font-size: 13px;
    font-weight: 500;
    color: var(--orch-ink);
    display: flex;
    align-items: center;
    gap: 8px;
}
.orch-bp-collapse > summary::-webkit-details-marker { display: none; }
.orch-bp-collapse > summary::before { content: '▶'; font-size: 10px; color: var(--orch-faint); transition: transform 0.2s; }
.orch-bp-collapse[open] > summary::before { transform: rotate(90deg); }
.orch-bp-collapse-count { color: var(--orch-faint); font-weight: 400; font-size: 11px; }

/* Preview section (dark) */
.orch-bp-preview-controls {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
    color: var(--bp-preview-text);
    font-size: 13px;
}
.orch-bp-preview-scope-select {
    background: rgba(255,255,255,0.08);
    color: var(--bp-preview-text);
    border: 1px solid rgba(255,255,255,0.18);
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
}
.orch-bp-preview-code {
    background: rgba(255,255,255,0.04);
    color: var(--bp-preview-text);
    font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
    font-size: 12px;
    padding: 14px 18px;
    border-radius: 6px;
    line-height: 1.55;
    white-space: pre;
    overflow-x: auto;
    margin: 10px 0;
    max-height: 500px;
}
.orch-bp-preview-stats {
    font-size: 11px;
    color: rgba(255,255,255,0.62);
    margin-top: 6px;
    font-family: ui-monospace, monospace;
}
.orch-bp-preview-info {
    font-size: 11px;
    color: rgba(255,255,255,0.62);
    margin-top: 8px;
    font-style: italic;
}

/* Sticky bottom bar */
.orch-bp-bottom-bar {
    position: sticky;
    bottom: 0;
    background: #ffffff;
    border-top: 1px solid var(--orch-line);
    border-radius: 8px;
    padding: 12px 20px;
    margin-top: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    z-index: 5;
    box-shadow: 0 -4px 12px rgba(0,0,0,0.05);
}
.orch-bp-autosave { font-size: 11px; color: var(--orch-subtle); }
.orch-bp-autosave--saving { color: var(--orch-info); }
.orch-bp-autosave--saved { color: var(--orch-good); }
.orch-bp-autosave--error { color: var(--orch-bad); }
.orch-bp-confirm-btn {
    background: var(--bp-public-accent);
    color: #ffffff;
    border: 0;
    padding: 8px 18px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}
.orch-bp-confirm-btn:hover { background: #15803d; }
.orch-bp-confirm-btn[disabled] { background: var(--orch-faint); cursor: default; }

@media (max-width: 768px) {
    .orch-bp-wrap { margin: 8px 8px 80px; }
    .orch-bp-bottom-bar { flex-direction: column; align-items: stretch; }
    .orch-bp-section-head { padding: 12px 14px; }
    .orch-bp-field { padding: 8px 10px; }
}
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

<div class="wrap orch-bp-wrap">
    <h1>🏢 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Profilo Business')) : 'Profilo Business'; ?></h1>
    <p class="orch-bp-intro">
        <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Compila il profilo per fornire all\'AI un contesto preciso del tuo business. I dati sono usati da TUTTI gli specialisti AI di Orchestra (Verify-Live, Contenuti AI, Analisi AEO, ecc.).')) : 'Compila il profilo per fornire all\'AI un contesto preciso del tuo business. I dati sono usati da TUTTI gli specialisti AI di Orchestra.'; ?>
    </p>

    <div class="orch-bp-completion">
        <div class="orch-bp-completion-stats">
            <span><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Completamento profilo')) : 'Completamento profilo'; ?>: <strong id="orch-bp-pop-count">—</strong> / <span id="orch-bp-pop-total">14</span> campi (<span id="orch-bp-pop-percent">—%</span>)</span>
            <span><span id="orch-bp-critical">— /</span> 4 campi critici <span id="orch-bp-critical-icon">—</span></span>
        </div>
        <div class="orch-bp-completion-bar">
            <div class="orch-bp-completion-bar-fill" id="orch-bp-pop-fill" style="width:0%"></div>
        </div>
        <div class="orch-bp-completion-status" id="orch-bp-status-line">
            <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Caricamento profilo…')) : 'Caricamento profilo…'; ?>
        </div>
    </div>

    <form id="orch-bp-form"
          data-ajaxurl="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
          data-nonce="<?php echo esc_attr(wp_create_nonce('seo_aeo_orchestra_nonce')); ?>"
          onsubmit="return false;">

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- 🌐 SECTION PUBLIC                                              -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <details class="orch-bp-section orch-bp-section--public" data-section-id="public" <?php if ($is_section_open('public', true)) echo 'open'; ?>>
            <summary class="orch-bp-section-head">
                <span class="orch-bp-section-badge">🌐 ESPOSTO AGLI AI ENGINES</span>
            </summary>
            <div class="orch-bp-section-body">
                <div class="orch-bp-section-info">
                    <strong>Questi dati saranno inclusi in:</strong>
                    <ul>
                        <li>llms.txt (mappa per ChatGPT/Claude/Perplexity)</li>
                        <li>Schema markup JSON-LD (Google, Bing, ecc.)</li>
                        <li>Open Graph tags (Facebook, Twitter, LinkedIn)</li>
                        <li>Tool AI di Orchestra (Verify-Live, Contenuti AI, Meta Tags, ecc.)</li>
                    </ul>
                </div>

                <div class="orch-bp-field" data-field="business_name">
                    <label class="orch-bp-field-label orch-bp-field-required">Nome azienda <span class="orch-bp-field-icon">🌐</span></label>
                    <input type="text" class="orch-bp-input" name="business_name" maxlength="200" data-min="2" data-max="200">
                    <div class="orch-bp-counter">0 / 200</div>
                </div>

                <div class="orch-bp-field" data-field="industry">
                    <label class="orch-bp-field-label orch-bp-field-required">Settore <span class="orch-bp-field-icon">🌐</span></label>
                    <input type="text" class="orch-bp-input" name="industry" maxlength="120" data-min="2" data-max="120" placeholder="es. edilizia, software B2B, consulenza HR">
                    <div class="orch-bp-counter">0 / 120</div>
                </div>

                <div class="orch-bp-field" data-field="business_description">
                    <label class="orch-bp-field-label orch-bp-field-required">Descrizione business <span class="orch-bp-field-icon">🌐</span></label>
                    <p class="orch-bp-field-help">Una frase breve che descrive cosa fai (50-300 caratteri).</p>
                    <textarea class="orch-bp-textarea" name="business_description" rows="2" maxlength="300" data-min="30" data-max="300"></textarea>
                    <div class="orch-bp-counter">0 / 300</div>
                </div>

                <div class="orch-bp-field" data-field="about_strategic">
                    <label class="orch-bp-field-label orch-bp-field-required">About strategico <span class="orch-bp-field-icon">🌐</span></label>
                    <p class="orch-bp-field-help">Story-form esteso: chi sei, da quanto, cosa ti distingue (100-1500 caratteri).</p>
                    <textarea class="orch-bp-textarea" name="about_strategic" rows="6" maxlength="1500" data-min="100" data-max="1500"></textarea>
                    <div class="orch-bp-counter">0 / 1500</div>
                </div>

                <div class="orch-bp-field" data-field="value_proposition">
                    <label class="orch-bp-field-label orch-bp-field-required">Value proposition <span class="orch-bp-field-icon">🌐</span></label>
                    <p class="orch-bp-field-help">Una frase che cattura il vantaggio principale per il cliente (20-200 caratteri).</p>
                    <input type="text" class="orch-bp-input" name="value_proposition" maxlength="200" data-min="20" data-max="200">
                    <div class="orch-bp-counter">0 / 200</div>
                </div>

                <div class="orch-bp-field" data-field="target_audience">
                    <label class="orch-bp-field-label orch-bp-field-required">Target audience <span class="orch-bp-field-icon">🌐</span></label>
                    <p class="orch-bp-field-help">Profilo cliente ideale (B2B/B2C, dimensione, settore, problema risolto). 20-200 caratteri.</p>
                    <input type="text" class="orch-bp-input" name="target_audience" maxlength="200" data-min="20" data-max="200">
                    <div class="orch-bp-counter">0 / 200</div>
                </div>

                <div class="orch-bp-field" data-field="founded_year">
                    <label class="orch-bp-field-label">Anno di fondazione <span class="orch-bp-field-icon">🌐</span></label>
                    <input type="number" class="orch-bp-input" name="founded_year" min="1800" max="<?php echo (int) gmdate('Y'); ?>" style="max-width: 160px;">
                </div>

                <details class="orch-bp-collapse" data-field="differentiators">
                    <summary>Differenziatori (max 7) <span class="orch-bp-collapse-count">[<span data-count>0</span> compilati]</span> 🌐</summary>
                    <p class="orch-bp-field-help">Cosa ti rende unico — vantaggi concreti vs concorrenti.</p>
                    <div class="orch-bp-repeat-list" data-orch-bp-repeat="differentiators" data-max="7"></div>
                    <button type="button" class="orch-bp-add-btn" data-orch-bp-add="differentiators">+ Aggiungi differenziatore</button>
                </details>

                <details class="orch-bp-collapse" data-field="products_services">
                    <summary>Prodotti / Servizi (max 15) <span class="orch-bp-collapse-count">[<span data-count>0</span> compilati]</span> 🌐</summary>
                    <p class="orch-bp-field-help">Catalogo concreto dell'offerta (es. "Software B2B", "Servizi consulenza"). Premi Enter o virgola dopo ogni voce.</p>
                    <div class="orch-bp-chips" data-orch-bp-tags="products_services" data-max="15">
                        <input type="text" class="orch-bp-chip-input" placeholder="Aggiungi prodotto/servizio... (Enter o ,)">
                    </div>
                </details>

                <details class="orch-bp-collapse" data-field="use_cases">
                    <summary>Casi d'uso (max 5) <span class="orch-bp-collapse-count">[<span data-count>0</span> compilati]</span> 🌐</summary>
                    <p class="orch-bp-field-help">Scenari applicativi tipici (es. "PMI: migrazione SEO da Yoast", "Agenzie: audit multi-cliente").</p>
                    <div class="orch-bp-repeat-list" data-orch-bp-repeat="use_cases" data-max="5"></div>
                    <button type="button" class="orch-bp-add-btn" data-orch-bp-add="use_cases">+ Aggiungi caso d'uso</button>
                </details>

                <details class="orch-bp-collapse" data-field="suppliers_partners">
                    <summary>Fornitori e partner (max 5) <span class="orch-bp-collapse-count">[<span data-count>0</span> compilati]</span> 🌐</summary>
                    <p class="orch-bp-field-help">Brand partner / fornitori chiave (es. "Anthropic" provider AI, "Hostinger" hosting). Premi Enter o virgola dopo ogni voce.</p>
                    <div class="orch-bp-chips" data-orch-bp-tags="suppliers_partners" data-max="5">
                        <input type="text" class="orch-bp-chip-input" placeholder="Aggiungi fornitore... (Enter o ,)">
                    </div>
                </details>

                <details class="orch-bp-collapse" data-field="territories">
                    <summary>Area di servizio (max 8) <span class="orch-bp-collapse-count">[<span data-count>0</span> compilati]</span> 🌐</summary>
                    <p class="orch-bp-field-help">Territori coperti (es. "Italia", "Spagna", "Europa"). Premi Enter o virgola dopo ogni voce.</p>
                    <div class="orch-bp-chips" data-orch-bp-tags="territories" data-max="8">
                        <input type="text" class="orch-bp-chip-input" placeholder="Aggiungi area... (Enter o ,)">
                    </div>
                </details>
            </div>
        </details>

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- 🎯 SECTION SITE CONTEXT (3.39.1) — anti-hallucination          -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <details class="orch-bp-section orch-bp-section--site-context" data-section-id="site-context" <?php if ($is_section_open('site-context', true)) echo 'open'; ?>>
            <summary class="orch-bp-section-head">
                <span class="orch-bp-section-badge orch-bp-section-badge--site-context">🎯 CONTESTO SITO</span>
            </summary>
            <div class="orch-bp-section-body">
                <div class="orch-bp-section-info">
                    <strong>Aiuta Orchestra a evitare interpretazioni errate del tuo sito da parte degli AI engine.</strong>
                    Più preciso il contesto, più accurate le analisi. Questi campi vengono iniettati come blocco autoritativo in ogni analisi SEO/AEO.
                </div>

                <div class="orch-bp-field" style="margin:8px 0 16px;">
                    <button type="button" id="orch-bp-sc-generate" class="orch3-btn orch3-btn-primary">
                        🔍 Genera automaticamente dal sito (3 cr)
                    </button>
                    <p class="orch-bp-field-help" style="margin-top:6px;">Analizza homepage + pagine /chi-siamo, /servizi, /about, /services se presenti. Verifica e modifica i suggerimenti prima di salvare.</p>
                    <div id="orch-bp-sc-status" class="orch-bp-sc-status" style="display:none;"></div>
                </div>

                <div class="orch-bp-field" data-field="site_context_description">
                    <label class="orch-bp-field-label">📝 Cosa fa il sito <span class="orch-bp-field-icon">🎯</span></label>
                    <p class="orch-bp-field-help">Descrizione concreta in 2-3 frasi (es. "Plugin WordPress per SEO + AEO che automatizza meta tags, schema, sitemap, llms.txt").</p>
                    <textarea class="orch-bp-textarea" name="site_context_description" rows="3" maxlength="600" data-max="600" placeholder="Es. AEO Orchestra e' un plugin WordPress che automatizza SEO + Answer Engine Optimization..."></textarea>
                    <div class="orch-bp-counter">0 / 600</div>
                </div>

                <div class="orch-bp-field" data-field="site_context_value_prop">
                    <label class="orch-bp-field-label">💎 Value proposition <span class="orch-bp-field-icon">🎯</span></label>
                    <p class="orch-bp-field-help">La promessa di valore distintiva (1-2 frasi).</p>
                    <textarea class="orch-bp-textarea" name="site_context_value_prop" rows="2" maxlength="400" data-max="400" placeholder="Es. L'unico plugin che disambigua brand ambigui per ChatGPT, Perplexity, Claude..."></textarea>
                    <div class="orch-bp-counter">0 / 400</div>
                </div>

                <div class="orch-bp-field" data-field="site_context_target_audience">
                    <label class="orch-bp-field-label">👥 Target audience dettagliato <span class="orch-bp-field-icon">🎯</span></label>
                    <p class="orch-bp-field-help">Chi sono i clienti tipo (settore, ruolo, dimensione, problema).</p>
                    <textarea class="orch-bp-textarea" name="site_context_target_audience" rows="2" maxlength="400" data-max="400" placeholder="Es. PMI italiane 5-50 dipendenti, agenzie SEO, founder che vogliono SEO senza skill tecniche..."></textarea>
                    <div class="orch-bp-counter">0 / 400</div>
                </div>

                <details class="orch-bp-collapse" data-field="site_context_ambiguous_terms">
                    <summary>⚠️ Termini ambigui da disambiguare (max 12) <span class="orch-bp-collapse-count">[<span data-count>0</span> compilati]</span> 🎯</summary>
                    <p class="orch-bp-field-help">I LLM possono interpretare male nomi/brand. Aggiungi qui i termini con il significato corretto + quello che NON significano. Esempio: "Orchestra" → significa "Plugin AEO software", NON significa "Orchestra musicale".</p>
                    <div class="orch-bp-terms-list" data-orch-bp-terms="site_context_ambiguous_terms" data-max="12"></div>
                    <button type="button" class="orch-bp-add-btn" data-orch-bp-add-term="site_context_ambiguous_terms">+ Aggiungi termine</button>
                </details>
            </div>
        </details>

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- 🔒 SECTION INTERNAL                                            -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <details class="orch-bp-section orch-bp-section--internal" data-section-id="internal" <?php if ($is_section_open('internal', true)) echo 'open'; ?>>
            <summary class="orch-bp-section-head">
                <span class="orch-bp-section-badge">🔒 USO INTERNO PIATTAFORMA</span>
            </summary>
            <div class="orch-bp-section-body">
                <div class="orch-bp-section-info">
                    <strong>Questi dati restano nel tuo MongoDB privato Orchestra.</strong>
                    <ul>
                        <li>❌ NON inseriti in llms.txt</li>
                        <li>❌ NON in schema markup</li>
                        <li>❌ NON in Open Graph</li>
                        <li>❌ NON in frontend del sito</li>
                        <li>✅ Solo per analisi AI private nel tuo pannello admin</li>
                    </ul>
                </div>

                <div class="orch-bp-field" data-field="competitors">
                    <label class="orch-bp-field-label">Concorrenti diretti (max 10) <span class="orch-bp-field-icon">🔒</span></label>
                    <p class="orch-bp-field-help">Inserisci concorrenti percepiti per ottenere analisi comparative AI ("come ti posizioni rispetto a loro"). Premi Enter o virgola dopo ogni voce.</p>
                    <div class="orch-bp-chips" data-orch-bp-tags="competitors" data-max="10">
                        <input type="text" class="orch-bp-chip-input" placeholder="Aggiungi concorrente... (Enter o ,)">
                    </div>
                </div>

                <div class="orch-bp-field" data-field="additional_notes">
                    <label class="orch-bp-field-label">Note aggiuntive <span class="orch-bp-field-icon">🔒</span></label>
                    <p class="orch-bp-field-help">Free-form per disambiguazioni custom o context strategy (max 500 caratteri).</p>
                    <textarea class="orch-bp-textarea" name="additional_notes" rows="3" maxlength="500" data-max="500"></textarea>
                    <div class="orch-bp-counter">0 / 500</div>
                </div>

                <div class="orch-bp-field" data-field="internal_pricing_strategy">
                    <label class="orch-bp-field-label">Strategia pricing interna <span class="orch-bp-field-icon">🔒</span></label>
                    <p class="orch-bp-field-help">Margine target, offerta entry, anchor price... (max 300 caratteri).</p>
                    <textarea class="orch-bp-textarea" name="internal_pricing_strategy" rows="2" maxlength="300" data-max="300"></textarea>
                    <div class="orch-bp-counter">0 / 300</div>
                </div>
            </div>
        </details>

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- 👁 PREVIEW CONTEXT AI                                          -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <details class="orch-bp-section orch-bp-section--preview" data-section-id="preview" <?php if ($is_section_open('preview', true)) echo 'open'; ?>>
            <summary class="orch-bp-section-head">
                <span class="orch-bp-section-badge">👁 ANTEPRIMA CONTEXT AI</span>
            </summary>
            <div class="orch-bp-section-body">
                <div class="orch-bp-preview-controls">
                    <label for="orch-bp-preview-scope">Scope:</label>
                    <select id="orch-bp-preview-scope" class="orch-bp-preview-scope-select">
                        <option value="full">Verify-Live / Contenuti AI / Analisi AEO (full)</option>
                        <option value="public">llms.txt / Schema markup / OG tags (public only)</option>
                    </select>
                </div>
                <pre id="orch-bp-preview-code" class="orch-bp-preview-code">— Caricamento anteprima —</pre>
                <div id="orch-bp-preview-stats" class="orch-bp-preview-stats">—</div>
                <div class="orch-bp-preview-info">ℹ️ Questo è il context block esatto che riceveranno gli AI engines quando esegui Verify-Live, Contenuti AI o altri tool AI.</div>
            </div>
        </details>

    </form>

    <div class="orch-bp-bottom-bar">
        <span id="orch-bp-autosave" class="orch-bp-autosave">💾 Auto-save attivo</span>
        <button type="button" id="orch-bp-confirm-btn" class="orch-bp-confirm-btn">✓ Conferma profilo</button>
    </div>
</div>

<?php ob_start(); ?>
(function($) {
    'use strict';

    var $form = $('#orch-bp-form');
    var ajaxurl = $form.attr('data-ajaxurl');
    var nonce = $form.attr('data-nonce');
    var saveTimer = null;
    var currentProfile = {};

    function escapeHtml(s) {
        if (s === null || typeof s === 'undefined') return '';
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function setAutosave(state, msg) {
        var $a = $('#orch-bp-autosave');
        $a.removeClass('orch-bp-autosave--saving orch-bp-autosave--saved orch-bp-autosave--error');
        if (state === 'saving') $a.addClass('orch-bp-autosave--saving').text('💾 Salvataggio…');
        else if (state === 'saved') $a.addClass('orch-bp-autosave--saved').text('✓ Salvato' + (msg ? ' · ' + msg : ''));
        else if (state === 'error') $a.addClass('orch-bp-autosave--error').text('⚠ Errore: ' + (msg || 'sconosciuto'));
        else $a.text('💾 Auto-save attivo');
    }

    function updateCompletion(stats) {
        if (!stats) return;
        $('#orch-bp-pop-count').text(stats.populated_count || 0);
        $('#orch-bp-pop-total').text(stats.total_count || 14);
        $('#orch-bp-pop-percent').text((stats.percent || 0) + '%');
        $('#orch-bp-pop-fill').css('width', (stats.percent || 0) + '%');
        $('#orch-bp-critical').text((stats.critical_satisfied || 0) + ' /');
        $('#orch-bp-critical-icon').text(stats.is_complete ? '✓' : '⚠');

        var $status = $('#orch-bp-status-line');
        if (currentProfile.customer_confirmed) {
            $status.text('✓ Profilo confermato' + (currentProfile.confirmed_at ? ' ' + currentProfile.confirmed_at : '')).addClass('orch-bp-completion-status--confirmed');
            $('#orch-bp-confirm-btn').prop('disabled', true).text('✓ Profilo confermato');
        } else if (stats.is_complete) {
            $status.text('Tutti i campi critici compilati. Pronto per la conferma.').removeClass('orch-bp-completion-status--confirmed');
        } else {
            var miss = (stats.missing_critical || []).join(', ');
            $status.text('⚠ Campi critici mancanti: ' + miss).removeClass('orch-bp-completion-status--confirmed');
        }
    }

    function setFieldValue(name, value) {
        var $f = $form.find('[name="' + name + '"]');
        if ($f.length && !$f.hasClass('orch-bp-chip-input')) {
            $f.val(value || '');
            $f.trigger('input.silent');
        }
    }

    // Counter update on input
    function updateCounter($input) {
        var len = String($input.val() || '').length;
        var max = parseInt($input.attr('data-max'), 10) || parseInt($input.attr('maxlength'), 10) || 0;
        var min = parseInt($input.attr('data-min'), 10) || 0;
        var $counter = $input.closest('.orch-bp-field').find('.orch-bp-counter');
        if (!$counter.length) return;
        $counter.text(len + ' / ' + max);
        $counter.removeClass('orch-bp-counter--invalid orch-bp-counter--valid');
        if (min > 0 && len > 0 && len < min) $counter.addClass('orch-bp-counter--invalid');
        else if (min > 0 && len >= min) $counter.addClass('orch-bp-counter--valid');
    }

    // Tag chips
    // 3.38.2 Task 6 — counter sync for chip-input fields. The existing
    // updateStructCount($list) only walks data-orch-bp-repeat-card children
    // (used by the text-repeater fields like differentiators/use_cases), so
    // chip fields (products_services / suppliers_partners / territories /
    // competitors) always showed "0 compilati" regardless of actual content.
    function updateChipCount($container) {
        var n = $container.find('.orch-bp-chip').length;
        $container.closest('details').find('[data-count]').text(n);
    }

    // 3.38.6 — Universal counter sync. The previous version iterated
    // [data-orch-bp-tags] + [data-orch-bp-repeat] separately, which worked
    // when the chip/struct render paths matched their expected wrappers.
    // But if a chip is inserted under a different DOM path (e.g. server-
    // rendered chips that JS doesn't recognize as its own), the per-wrapper
    // count returns 0 even though the user sees chips on screen.
    //
    // This version walks every details.orch-bp-collapse, locates the
    // [data-count] span in its summary, and counts ANY item-child inside —
    // .orch-bp-chip OR [data-orch-bp-repeat-card] OR .orch-bp-repeat-card.
    // Survives all hydration paths since it operates on the visible DOM
    // after everything has finished rendering.
    function syncAllCounters() {
        $form.find('details.orch-bp-collapse').each(function() {
            var $det = $(this);
            var $countSpan = $det.find('[data-count]').first();
            if (!$countSpan.length) return;
            // Count any item-child regardless of class. Both chip + struct.
            var n = $det.find('.orch-bp-chip, [data-orch-bp-repeat-card], .orch-bp-repeat-card').length;
            $countSpan.text(n);
        });
    }

    // 3.38.6 — MutationObserver belt-and-braces. Re-run syncAllCounters
    // whenever ANY DOM change happens inside $form. This catches every
    // possible mutation source: chip add/remove, struct add/remove, drag-
    // reorder, autosave-triggered re-render, or future code paths we haven't
    // anticipated. The observer is throttled to fire at most once per
    // animation frame to avoid pathological CPU usage.
    var _syncRaf = null;
    function _scheduleSync() {
        if (_syncRaf) return;
        _syncRaf = requestAnimationFrame(function () {
            _syncRaf = null;
            syncAllCounters();
        });
    }
    $(function () {
        var formEl = $form[0];
        if (!formEl || typeof MutationObserver === 'undefined') return;
        var obs = new MutationObserver(_scheduleSync);
        obs.observe(formEl, { childList: true, subtree: true });
    });

    function addChip($container, value) {
        if (!value || !value.trim()) return;
        value = value.trim();
        var max = parseInt($container.attr('data-max'), 10) || 99;
        var existing = $container.find('.orch-bp-chip').length;
        if (existing >= max) return;
        // Avoid duplicates
        var dup = false;
        $container.find('.orch-bp-chip-label').each(function() {
            if ($(this).text() === value) dup = true;
        });
        if (dup) return;
        var html = '<span class="orch-bp-chip"><span class="orch-bp-chip-label">' + escapeHtml(value) + '</span><button type="button" class="orch-bp-chip-remove" aria-label="Rimuovi">×</button></span>';
        $container.find('.orch-bp-chip-input').before(html);
        updateChipCount($container);
        triggerSave();
    }

    function getChipValues($container) {
        var arr = [];
        $container.find('.orch-bp-chip-label').each(function() { arr.push($(this).text()); });
        return arr;
    }

    function setChipValues($container, values) {
        $container.find('.orch-bp-chip').remove();
        if (Array.isArray(values)) {
            values.forEach(function(v) {
                var label = (typeof v === 'string') ? v : (v && v.name ? v.name : '');
                if (label) {
                    $container.find('.orch-bp-chip-input').before(
                        '<span class="orch-bp-chip"><span class="orch-bp-chip-label">' + escapeHtml(label) + '</span><button type="button" class="orch-bp-chip-remove" aria-label="Rimuovi">×</button></span>'
                    );
                }
            });
        }
        // 3.38.2 Task 6 — sync counter after bulk render from saved data.
        updateChipCount($container);
    }

    $(document).on('keydown', '.orch-bp-chip-input', function(e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            var $input = $(this);
            var $container = $input.closest('.orch-bp-chips');
            addChip($container, $input.val());
            $input.val('');
        }
    });
    $(document).on('click', '.orch-bp-chip-remove', function(e) {
        e.preventDefault();
        var $container = $(this).closest('.orch-bp-chips');
        $(this).closest('.orch-bp-chip').remove();
        // 3.38.2 Task 6 — sync counter after remove.
        if ($container.length) updateChipCount($container);
        triggerSave();
    });

    // Repeatable struct cards
    function buildStructCard(field, idx, item) {
        item = item || {title: '', description: ''};
        return '<div class="orch-bp-repeat-card" data-orch-bp-repeat-card>' +
               '<div class="orch-bp-repeat-head"><span>#' + (idx + 1) + '</span>' +
               '<div class="orch-bp-repeat-actions">' +
               '<button type="button" class="orch-bp-repeat-up" title="Sposta su">↑</button>' +
               '<button type="button" class="orch-bp-repeat-down" title="Sposta giù">↓</button>' +
               '<button type="button" class="orch-bp-repeat-remove" title="Rimuovi">×</button>' +
               '</div></div>' +
               '<label>Titolo</label>' +
               '<input type="text" class="orch-bp-input" data-repeat-field="title" maxlength="120" value="' + escapeHtml(item.title || '') + '">' +
               '<label>Descrizione</label>' +
               '<textarea class="orch-bp-textarea" data-repeat-field="description" rows="2" maxlength="500">' + escapeHtml(item.description || '') + '</textarea>' +
               '</div>';
    }

    function getStructValues($list) {
        var out = [];
        $list.find('[data-orch-bp-repeat-card]').each(function() {
            var $c = $(this);
            var t = ($c.find('[data-repeat-field="title"]').val() || '').trim();
            var d = ($c.find('[data-repeat-field="description"]').val() || '').trim();
            if (t || d) out.push({title: t, description: d});
        });
        return out;
    }

    function setStructValues($list, values) {
        $list.empty();
        if (Array.isArray(values)) {
            values.forEach(function(item, i) {
                $list.append(buildStructCard($list.attr('data-orch-bp-repeat'), i, item));
            });
        }
        updateStructCount($list);
    }

    function updateStructCount($list) {
        var n = $list.find('[data-orch-bp-repeat-card]').length;
        $list.closest('details').find('[data-count]').text(n);
    }

    $(document).on('click', '.orch-bp-add-btn[data-orch-bp-add]', function() {
        var field = $(this).attr('data-orch-bp-add');
        var $list = $form.find('[data-orch-bp-repeat="' + field + '"]');
        var max = parseInt($list.attr('data-max'), 10) || 99;
        var existing = $list.find('[data-orch-bp-repeat-card]').length;
        if (existing >= max) return;
        $list.append(buildStructCard(field, existing, {}));
        updateStructCount($list);
        triggerSave();
    });
    $(document).on('click', '.orch-bp-repeat-remove', function() {
        var $list = $(this).closest('[data-orch-bp-repeat]');
        $(this).closest('[data-orch-bp-repeat-card]').remove();
        updateStructCount($list);
        triggerSave();
    });
    $(document).on('click', '.orch-bp-repeat-up', function() {
        var $card = $(this).closest('[data-orch-bp-repeat-card]');
        var $prev = $card.prev('[data-orch-bp-repeat-card]');
        if ($prev.length) { $card.insertBefore($prev); triggerSave(); }
    });
    $(document).on('click', '.orch-bp-repeat-down', function() {
        var $card = $(this).closest('[data-orch-bp-repeat-card]');
        var $next = $card.next('[data-orch-bp-repeat-card]');
        if ($next.length) { $card.insertAfter($next); triggerSave(); }
    });

    function serializeProfile() {
        var data = {};
        // Scalars
        ['business_name','industry','business_description','about_strategic','value_proposition','target_audience','additional_notes','internal_pricing_strategy','site_context_description','site_context_value_prop','site_context_target_audience'].forEach(function(f) {
            data[f] = ($form.find('[name="' + f + '"]').val() || '').trim();
        });
        // Number
        var fy = $form.find('[name="founded_year"]').val();
        if (fy && parseInt(fy, 10)) data.founded_year = parseInt(fy, 10);
        // Tags
        ['products_services','suppliers_partners','territories','competitors'].forEach(function(f) {
            var $c = $form.find('[data-orch-bp-tags="' + f + '"]');
            if ($c.length) data[f] = getChipValues($c);
        });
        // Struct
        ['differentiators','use_cases'].forEach(function(f) {
            var $l = $form.find('[data-orch-bp-repeat="' + f + '"]');
            if ($l.length) data[f] = getStructValues($l);
        });
        // 3.39.1 — Site Context ambiguous terms (3-field rows).
        var $terms = $form.find('[data-orch-bp-terms="site_context_ambiguous_terms"]');
        if ($terms.length) data.site_context_ambiguous_terms = getTermsValues($terms);
        return data;
    }

    // 3.39.1 — Site Context terms helpers.
    function getTermsValues($container) {
        var out = [];
        $container.find('.orch-bp-term-row').each(function() {
            var $row = $(this);
            var term = ($row.find('[data-term-field="term"]').val() || '').trim();
            var correct = ($row.find('[data-term-field="correct_meaning"]').val() || '').trim();
            var not_m = ($row.find('[data-term-field="not_meaning"]').val() || '').trim();
            if (term && correct) out.push({term: term, correct_meaning: correct, not_meaning: not_m});
        });
        return out;
    }
    function setTermsValues($container, items) {
        $container.empty();
        if (!Array.isArray(items)) return;
        items.forEach(function(it) {
            addTermRow($container, it || {});
        });
        updateTermsCount($container);
    }
    function addTermRow($container, init) {
        init = init || {};
        var max = parseInt($container.data('max') || 12, 10);
        if ($container.find('.orch-bp-term-row').length >= max) return;
        var safeT = (init.term || '').toString().replace(/"/g, '&quot;');
        var safeC = (init.correct_meaning || '').toString().replace(/"/g, '&quot;');
        var safeN = (init.not_meaning || '').toString().replace(/"/g, '&quot;');
        var row = '<div class="orch-bp-term-row">' +
            '<input type="text" data-term-field="term" maxlength="80" placeholder="Termine" value="' + safeT + '">' +
            '<input type="text" data-term-field="correct_meaning" maxlength="200" placeholder="Significato corretto" value="' + safeC + '">' +
            '<input type="text" data-term-field="not_meaning" maxlength="200" placeholder="NON significa (opzionale)" value="' + safeN + '">' +
            '<button type="button" class="orch-bp-term-remove" title="Rimuovi">×</button>' +
            '</div>';
        $container.append(row);
    }
    function updateTermsCount($container) {
        var n = $container.find('.orch-bp-term-row').length;
        $container.closest('details').find('[data-count]').text(n);
    }
    $(document).on('click', '[data-orch-bp-add-term]', function() {
        var key = $(this).data('orch-bp-add-term');
        var $c = $form.find('[data-orch-bp-terms="' + key + '"]');
        addTermRow($c, {});
        updateTermsCount($c);
        $c.find('.orch-bp-term-row:last input:first').focus();
        triggerSave();
    });
    $(document).on('click', '.orch-bp-term-remove', function() {
        var $row = $(this).closest('.orch-bp-term-row');
        var $c = $row.closest('[data-orch-bp-terms]');
        $row.remove();
        updateTermsCount($c);
        triggerSave();
    });
    $(document).on('input change', '.orch-bp-term-row input', function() {
        var $c = $(this).closest('[data-orch-bp-terms]');
        updateTermsCount($c);
        triggerSave();
    });

    // 3.39.1 — Auto-generate Site Context from the live site.
    $(document).on('click', '#orch-bp-sc-generate', function() {
        var $btn = $(this);
        var $status = $('#orch-bp-sc-status');
        if (!confirm('Avviare la generazione automatica del Contesto Sito? Costa 3 crediti.')) return;
        $btn.prop('disabled', true).text('⏳ Generazione in corso...');
        $status.attr('class', 'orch-bp-sc-status is-loading').text('Sto analizzando homepage + pagine chi siamo/servizi...').show();
        $.post(ajaxurl, {
            action: 'seo_aeo_business_profile_generate_site_context',
            nonce: nonce
        }).done(function(resp) {
            if (!resp || !resp.success) {
                var msg = (resp && resp.data && resp.data.message) ? resp.data.message : 'Errore sconosciuto';
                $status.attr('class', 'orch-bp-sc-status is-err').text('✗ ' + msg);
                return;
            }
            var fields = (resp.data && resp.data.fields) || {};
            // Populate the textareas (preserve user-typed content if non-empty).
            ['site_context_description','site_context_value_prop','site_context_target_audience'].forEach(function(k) {
                var $el = $form.find('[name="' + k + '"]');
                if ($el.length && fields[k]) {
                    if (!$el.val() || confirm('Sovrascrivere ' + k + ' esistente?')) {
                        $el.val(fields[k]);
                        updateCounter($el);
                    }
                }
            });
            // Populate ambiguous terms (append, don't overwrite — user may have custom).
            var $terms = $form.find('[data-orch-bp-terms="site_context_ambiguous_terms"]');
            if ($terms.length && Array.isArray(fields.site_context_ambiguous_terms)) {
                fields.site_context_ambiguous_terms.forEach(function(t) {
                    addTermRow($terms, t);
                });
                updateTermsCount($terms);
            }
            var _n = (resp.data.pages_analyzed || 0); var _label = _n === 1 ? ' pagina analizzata' : ' pagine analizzate'; $status.attr('class', 'orch-bp-sc-status is-ok').text('✓ Contesto generato (' + _n + _label + '). Verifica e modifica prima di salvare.');
            triggerSave();
        }).fail(function(xhr) {
            var msg = 'HTTP ' + (xhr ? xhr.status : '?');
            try {
                var r = JSON.parse(xhr.responseText || '{}');
                if (r && r.data && r.data.message) msg = r.data.message;
            } catch (e) {}
            $status.attr('class', 'orch-bp-sc-status is-err').text('✗ ' + msg);
        }).always(function() {
            $btn.prop('disabled', false).text('🔍 Genera automaticamente dal sito (3 cr)');
        });
    });

    function triggerSave() {
        setAutosave('saving');
        clearTimeout(saveTimer);
        saveTimer = setTimeout(saveProfile, 800);
    }

    function saveProfile() {
        var data = serializeProfile();
        $.post(ajaxurl, {
            action: 'seo_aeo_business_profile_save',
            nonce: nonce,
            fields_json: JSON.stringify(data)
        }).done(function(resp) {
            if (resp && resp.success) {
                setAutosave('saved');
                if (resp.data && resp.data.profile) {
                    currentProfile = resp.data.profile;
                    // 3.39.2 Bug #3 — the backend returns the cleaned/canonical
                    // version of the profile; re-render so the DOM matches what
                    // was actually stored (truncation, normalization, etc.).
                    // We skip scalar re-hydration to avoid clobbering whatever
                    // the user just typed, but chip/struct/term repeaters get
                    // refreshed so list views stay in sync.
                    ['products_services','suppliers_partners','territories','competitors'].forEach(function(f) {
                        var $c = $form.find('[data-orch-bp-tags="' + f + '"]');
                        if ($c.length && document.activeElement !== $c.find('input')[0]) {
                            setChipValues($c, currentProfile[f] || []);
                        }
                    });
                    updateCompletion(resp.data.stats);
                }
                updatePreview();
            } else {
                setAutosave('error', (resp && resp.data && resp.data.message) ? resp.data.message : '');
            }
        }).fail(function(xhr) {
            setAutosave('error', 'HTTP ' + (xhr ? xhr.status : '?'));
        });
    }

    function updatePreview() {
        var scope = $('#orch-bp-preview-scope').val() || 'full';
        $.post(ajaxurl, {
            action: 'seo_aeo_business_profile_preview',
            nonce: nonce,
            scope: scope
        }).done(function(resp) {
            if (resp && resp.success && resp.data) {
                $('#orch-bp-preview-code').text(resp.data.context_block || '— vuoto —');
                $('#orch-bp-preview-stats').html('characters: ' + (resp.data.chars || 0) + ' / 3000 limit · ~' + (resp.data.tokens || 0) + ' token · scope=' + (resp.data.scope || scope));
            }
        });
    }

    // 3.39.2 — extracted hydration so it can run twice (defensive against
    // any timing race that leaves a chip/struct/term wrapper empty on the
    // first pass). All setXValues functions are idempotent.
    function hydrateProfile(profile) {
        if (!profile) profile = {};
        ['business_name','industry','business_description','about_strategic','value_proposition','target_audience','additional_notes','internal_pricing_strategy','founded_year','site_context_description','site_context_value_prop','site_context_target_audience'].forEach(function(f) {
            setFieldValue(f, profile[f]);
        });
        ['products_services','suppliers_partners','territories','competitors'].forEach(function(f) {
            var $c = $form.find('[data-orch-bp-tags="' + f + '"]');
            if ($c.length) setChipValues($c, profile[f] || []);
        });
        ['differentiators','use_cases'].forEach(function(f) {
            var $l = $form.find('[data-orch-bp-repeat="' + f + '"]');
            if ($l.length) setStructValues($l, profile[f] || []);
        });
        var $sc_terms = $form.find('[data-orch-bp-terms="site_context_ambiguous_terms"]');
        if ($sc_terms.length) setTermsValues($sc_terms, profile.site_context_ambiguous_terms || []);
        $form.find('.orch-bp-input, .orch-bp-textarea').each(function() { updateCounter($(this)); });
    }

    function loadProfile() {
        $.post(ajaxurl, {
            action: 'seo_aeo_business_profile_get',
            nonce: nonce
        }).done(function(resp) {
            if (!resp || !resp.success) {
                $('#orch-bp-status-line').text('⚠ Errore caricamento profilo');
                return;
            }
            var data = resp.data || {};
            currentProfile = data.profile || {};
            hydrateProfile(currentProfile);
            // 3.39.2 Bug #3 — defensive second pass one tick later, in case
            // details/collapsibles weren't fully rendered when the first pass
            // ran. Idempotent: setChipValues + setStructValues + setTermsValues
            // all clear-and-rebuild, so a re-run produces the same DOM.
            setTimeout(function() { hydrateProfile(currentProfile); syncAllCounters(); }, 0);
            updateCompletion(data.stats);
            updatePreview();
            setAutosave('saved');
        }).fail(function() {
            $('#orch-bp-status-line').text('⚠ Errore caricamento profilo (network)');
        });
    }

    // Confirm button
    // 3.35.83.1.2: event delegation (resilient to disabled state + DOM re-render)
    $(document).on('click', '#orch-bp-confirm-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        if ($btn.prop('disabled')) {
            console.log('[BP] confirm button click ignored — already disabled');
            return;
        }
        console.log('[BP] confirm button clicked → AJAX call');
        if (!confirm('Confermi i dati del profilo? Saranno usati da tutti i tool AI di Orchestra.')) return;
        $btn.prop('disabled', true).text('Confermando…');
        $.post(ajaxurl, {
            action: 'seo_aeo_business_profile_confirm',
            nonce: nonce
        }).done(function(resp) {
            if (resp && resp.success) {
                $btn.prop('disabled', true).text('✓ Profilo confermato');
                currentProfile.customer_confirmed = true;
                currentProfile.confirmed_at = (resp.data && resp.data.confirmed_at) ? resp.data.confirmed_at : '';
                $('#orch-bp-status-line').text('✓ Profilo confermato' + (currentProfile.confirmed_at ? ' ' + currentProfile.confirmed_at : '')).addClass('orch-bp-completion-status--confirmed');
                console.log('[BP] confirm success at', currentProfile.confirmed_at);
            } else {
                $btn.prop('disabled', false).text('✓ Conferma profilo');
                console.error('[BP] confirm failed', resp);
                alert('Errore conferma: ' + ((resp && resp.data && resp.data.message) ? resp.data.message : 'sconosciuto'));
            }
        }).fail(function(xhr) {
            $btn.prop('disabled', false).text('✓ Conferma profilo');
            console.error('[BP] confirm AJAX failed', xhr ? xhr.status : '?', xhr ? xhr.responseText : '');
            alert('Errore conferma (network): HTTP ' + (xhr ? xhr.status : '?'));
        });
    });

    // Section state save on toggle
    $(document).on('toggle', '[data-section-id]', function() {
        var $s = $(this);
        var section = $s.attr('data-section-id');
        $.post(ajaxurl, {
            action: 'seo_aeo_bp_section_state',
            nonce: nonce,
            section: section,
            open: $s.prop('open') ? 1 : 0
        });
    });

    // Auto-save trigger on any input change (debounce 800ms)
    $(document).on('input change', '.orch-bp-input, .orch-bp-textarea', function(e) {
        var $i = $(this);
        if (e.namespace === 'silent') return; // skip silent updates from setFieldValue
        updateCounter($i);
        triggerSave();
    });

    // Preview scope dropdown change (no debounce)
    $(document).on('change', '#orch-bp-preview-scope', updatePreview);

    // Bootstrap
    $(function() {
        loadProfile();
    });

})(jQuery);
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>

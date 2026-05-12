<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.VariableNotPrefixed
// Reason: template scope. Variables are local to this include/template,
// passed by the calling function via include/require. The Plugin Check
// heuristic doesn't distinguish template-scope locals from globals.
/*
 * Template: SEO Output Nativo (v3.18.0 — refactor completo)
 * Tutte le card "Migrazione SEO" (Output, Sitemap, llms.txt, Schema, Redirect Manager)
 * vivono SOLO qui. Dashboard plugin trimmed.
 */
if (!defined('ABSPATH')) exit;

$seo_aeo_is_other_seo_active = class_exists('SEO_AEO_Output_Renderer') ? SEO_AEO_Output_Renderer::detect_other_seo_plugin() : false;
$seo_aeo_labels = array('yoast' => 'Yoast SEO', 'rankmath' => 'Rank Math', 'aioseo' => 'All in One SEO');
$seo_aeo_plugin_name_label = $seo_aeo_is_other_seo_active && isset($seo_aeo_labels[$seo_aeo_is_other_seo_active]) ? $seo_aeo_labels[$seo_aeo_is_other_seo_active] : '';
?>
<?php
// 3.36.1 (WP.org A.5 compliance): Prism syntax-highlighter assets routed
// through wp_enqueue_* instead of inline <link>/<script src=> tags.
if (is_admin() && defined('SEO_AEO_URL')) {
    $orch_prism_base = SEO_AEO_URL . 'assets/vendor/prism/';
    $orch_prism_ver  = defined('SEO_AEO_VERSION') ? SEO_AEO_VERSION : '1';
    wp_enqueue_style('seo-aeo-prism', $orch_prism_base . 'prism-tomorrow.min.css', array(), $orch_prism_ver);
    wp_enqueue_script('seo-aeo-prism', $orch_prism_base . 'prism.min.js', array(), $orch_prism_ver, true);
    wp_enqueue_script('seo-aeo-prism-md', $orch_prism_base . 'components/prism-markdown.min.js', array('seo-aeo-prism'), $orch_prism_ver, true);
    wp_enqueue_script('seo-aeo-prism-json', $orch_prism_base . 'components/prism-json.min.js', array('seo-aeo-prism'), $orch_prism_ver, true);
    foreach (array('seo-aeo-prism', 'seo-aeo-prism-md', 'seo-aeo-prism-json') as $orch_prism_h) {
        wp_script_add_data($orch_prism_h, 'data-manual', 'true');
    }
}
?>
<div class="wrap orchestra-v3 orch-native-page">

    <div class="orch-native-hero">
        <div class="orch-native-hero-icon">⚡</div>
        <div class="orch-native-hero-text">
            <div class="orch-native-hero-tagline"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('AEO Orchestra · Native Stack')) : 'AEO Orchestra · Native Stack'; ?></div>
            <h1 class="orch-native-hero-title"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('SEO Output Nativo')) : 'SEO Output Nativo'; ?></h1>
            <p class="orch-native-hero-sub"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Sostituisce il <head> di Yoast/RankMath/AIOSEO con output nativo Orchestra. Title, meta description, OpenGraph, Twitter Cards, canonical, sitemap.xml, llms.txt e schema JSON-LD strutturato.')) : 'Sostituisce il <head> di Yoast/RankMath/AIOSEO con output nativo Orchestra. Title, meta description, OpenGraph, Twitter Cards, canonical, sitemap.xml, llms.txt e schema JSON-LD strutturato.'; ?></p>
        </div>
    </div>

    <div class="orch-native-quickstats">
        <?php
        $seo_aeo_features = array(
            array('label' => 'Output &lt;head&gt;',  'enabled' => class_exists('SEO_AEO_Output_Renderer') && SEO_AEO_Output_Renderer::is_active(),    'icon' => '🎯', 'desc' => 'Title, meta, OG, Twitter, canonical'),
            array('label' => 'Sitemap.xml',          'enabled' => class_exists('SEO_AEO_Sitemap')         && SEO_AEO_Sitemap::is_enabled(),          'icon' => '🗺️', 'desc' => 'Index per tutti i CPT/taxonomies'),
            array('label' => 'llms.txt',             'enabled' => class_exists('SEO_AEO_LLMs_Txt')        && SEO_AEO_LLMs_Txt::is_enabled(),         'icon' => '🤖', 'desc' => 'Mappa ChatGPT/Claude/Perplexity'),
            array('label' => 'Schema.org',           'enabled' => class_exists('SEO_AEO_Schema')          && SEO_AEO_Schema::is_enabled(),           'icon' => '📊', 'desc' => 'JSON-LD @graph dinamico'),
        );
        foreach ($seo_aeo_features as $seo_aeo_f) {
            $seo_aeo_cls = $seo_aeo_f['enabled'] ? 'enabled' : 'disabled';
            $seo_aeo_dot = $seo_aeo_f['enabled'] ? '✓ ON' : '○ OFF';
            echo '<div class="orch-native-quickstat ' . esc_attr($seo_aeo_cls) . '">';
            echo '<div class="orch-native-quickstat-icon">' . esc_html($seo_aeo_f['icon']) . '</div>';
            echo '<div class="orch-native-quickstat-label">' . esc_html($seo_aeo_f['label']) . '</div>';
            echo '<div class="orch-native-quickstat-desc">' . esc_html($seo_aeo_f['desc']) . '</div>';
            echo '<div class="orch-native-quickstat-status">' . esc_html($seo_aeo_dot) . '</div>';
            echo '</div>';
        }
        ?>
    </div>

    <?php if ($seo_aeo_is_other_seo_active): ?>
    <div class="orch-native-warn-banner">
        ⚠️ <strong>Rilevato plugin SEO esterno: <?php echo esc_html($seo_aeo_plugin_name_label); ?>.</strong>
        Per attivare l'output nativo di Orchestra, disinstalla <?php echo esc_html($seo_aeo_plugin_name_label); ?>
        oppure attiva <strong>Override Mode</strong> dalla card sotto (modalità avanzata che silenzia <?php echo esc_html($seo_aeo_plugin_name_label); ?> sul frontend mantenendolo installato).
    </div>
    <?php endif; ?>

    <?php
    // 3.20.5 — Fallback nonce/ajaxurl se admin.js non viene enqueueato sulla submenu page
    $seo_aeo_nonce_fallback = wp_create_nonce('seo_aeo_orchestra_nonce');
    ?>
    <?php ob_start(); ?>
// Fallback: garantisce window.ajaxurl e window.seoAeoOrchestra anche se admin.js non parte
        if (typeof window.ajaxurl === 'undefined') {
            window.ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
        }
        if (typeof window.seoAeoOrchestra === 'undefined') {
            window.seoAeoOrchestra = {
                ajaxUrl:    '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
                nonce:      '<?php echo esc_js($seo_aeo_nonce_fallback); ?>',
                licenseKey: '<?php echo esc_js(get_option('seo_aeo_orchestra_license_key', '')); ?>',
                apiUrl:     '<?php echo esc_js(defined('SEO_AEO_API_URL') ? SEO_AEO_API_URL : 'https://aeo-orchestra.com'); ?>'
            };
        }
        console.log('[SEO_AEO Native] init: nonce=' + (window.seoAeoOrchestra.nonce ? 'OK' : 'MISSING') + ', ajaxurl=' + window.ajaxurl);
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>


    
    <nav class="orch-native-tabs" role="tablist" aria-label="SEO Output Nativo sections">
        <button type="button" class="orch-native-tab is-active" data-orch-tab-target="status" role="tab" aria-selected="true">
            <span class="orch-native-tab-icon">⚙️</span>
            <span class="orch-native-tab-label"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Stato')) : 'Stato'; ?></span>
        </button>
        <button type="button" class="orch-native-tab" data-orch-tab-target="content" role="tab" aria-selected="false">
            <span class="orch-native-tab-icon">📝</span>
            <span class="orch-native-tab-label"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Configurazione contenuti')) : 'Configurazione contenuti'; ?></span>
        </button>
        <button type="button" class="orch-native-tab" data-orch-tab-target="verify" role="tab" aria-selected="false">
            <span class="orch-native-tab-icon">🔍</span>
            <span class="orch-native-tab-label"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Verifica & lingue')) : 'Verifica & lingue'; ?></span>
        </button>
    </nav>

    <div class="orch-native-tab-panel is-active" data-orch-tab="status" role="tabpanel">

    <!-- ═══ Native SEO Output (v3.12.0) — sostituto di Yoast/RankMath/AIOSEO ═══ -->
    <div class="orch3-card orch-native-card" id="orch-output-card">
        <a id="orch-sitemap"></a>
        <a id="orch-llms"></a>
        <a id="orch-schema"></a>
        <div class="orch-native-head">
            <div>
                <h2 class="orch3-h2"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('SEO Output Nativo')) : 'SEO Output Nativo'; ?></h2>
                <p class="orch3-muted" style="margin:4px 0 0; font-size:13px;"><?php echo class_exists('SEO_AEO_T') ? wp_kses_post(SEO_AEO_T::t('Genera direttamente <code>&lt;title&gt;</code>, meta description, OpenGraph, Twitter Cards e canonical, senza dipendere da Yoast/RankMath/AIOSEO.')) : 'Genera direttamente <code>&lt;title&gt;</code>, meta description, OpenGraph, Twitter Cards e canonical, senza dipendere da Yoast/RankMath/AIOSEO.'; ?></p>
            </div>
            <button type="button" class="orch3-btn orch3-btn-ghost orch-native-toggle-card" aria-expanded="true"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Nascondi')) : 'Nascondi'; ?></button>
        </div>
        <div class="orch-native-body">
            <div id="orch-native-status" class="orch3-muted" style="padding:8px 0;">
                <span class="rv-spinner"></span> <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Verifica stato…')) : 'Verifica stato…'; ?>
            </div>
        </div>
    </div>

    </div><!-- end orch-native-tab-panel status -->

    <div class="orch-native-tab-panel" data-orch-tab="content" role="tabpanel" hidden>

        <!-- ============================================================
             SECTION 2 — Configurazione per output (4 accordion details)
             ============================================================ -->
        <div class="orch3-card orch-native-card orch-section orch-section-outputs">
            <div class="orch-native-head">
                <div>
                    <h2 class="orch3-h2">
                        <span class="orch-section-icon">⚙️</span>
                        <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Configurazione per output')) : 'Configurazione per output'; ?>
                    </h2>
                    <p class="orch3-muted" style="margin:4px 0 0; font-size:13px;">
                        <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Cinque output nativi indipendenti. Ognuno ha la propria whitelist di tipi di contenuto e i propri default — la sitemap.xml include tutto per Google, /llms-full.txt è curato per AI ingestion.')) : 'Cinque output nativi indipendenti. Ognuno ha la propria whitelist di tipi di contenuto e i propri default — la sitemap.xml include tutto per Google, /llms-full.txt è curato per AI ingestion.'; ?></p>
                </div>
            </div>

            <!-- 3.35.79: Floating save bar -->
            <div class="orch-floating-savebar" id="orch-floating-savebar" role="region" aria-label="Modifiche non salvate" aria-hidden="true">
                <span class="orch-floating-savebar-icon">●</span>
                <span class="orch-floating-savebar-label">Modifiche non salvate</span>
                <button type="button" class="orch3-btn orch3-btn-primary orch-floating-save-btn" id="orch-floating-save-btn">💾 Salva ora</button>
                <button type="button" class="orch3-btn orch3-btn-ghost orch-floating-cancel-btn" id="orch-floating-cancel-btn">↺ Annulla</button>
            </div>

            <!-- 3.35.81: Verify-Live hero card -->
            <div class="orch-vl-card" id="orch-vl-card"
                 data-wp-rest-nonce="<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>"
                 data-wp-rest-root="<?php echo esc_url_raw(rest_url()); ?>"
                 data-site-url="<?php echo esc_url(home_url('/')); ?>">
                <h3>🔬 Verifica come le AI vedono il tuo sito</h3>
                <p class="orch-vl-card-sub">
                    Inviamo il profilo identità del tuo sito a un motore AI con web search e verifichiamo se ti cita correttamente.
                    Risultato in ~30 secondi: <strong>Citation Accuracy Score</strong> 0-100 + suggerimenti specifici per migliorare.
                </p>

                <!-- 3.35.82: Pre-verify preview panel — Trasparenza Dati -->
                <details class="orch-vl-preview-panel" id="orch-vl-preview-panel"
                         data-collapsed-pref="<?php echo esc_attr(get_user_meta(get_current_user_id(), 'seo_aeo_vl_preview_collapsed', true) ? '1' : '0'); ?>"
                         data-ajaxurl="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
                         data-nonce="<?php echo esc_attr(wp_create_nonce('seo_aeo_orchestra_nonce')); ?>"
                         data-edit-identity-url="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-business-profile')); ?>"
                         data-edit-brandvoice-url="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-brand-voice')); ?>">
                    <summary class="orch-vl-preview-summary">
                        <span class="orch-vl-preview-icon">🔍</span>
                        <strong>Cosa invieremo al motore AI</strong>
                        <span class="orch-vl-preview-toggle-hint">(clicca per espandere/comprimere)</span>
                    </summary>
                    <div class="orch-vl-preview-body" id="orch-vl-preview-body">
                        <div class="orch-vl-preview-skeleton">
                            <div class="orch-vl-skel-line"></div>
                            <div class="orch-vl-skel-line"></div>
                            <div class="orch-vl-skel-line"></div>
                            <div style="text-align:center; padding: 8px; color: rgba(255,255,255,0.7); font-size: 12px;">Caricamento dati profilo…</div>
                        </div>
                    </div>
                </details>

                <div class="orch-vl-cta-row">
                    <button type="button" class="orch-vl-btn-primary" id="orch-vl-start-btn">▶ Verifica ora · 5 crediti</button>
                    <button type="button" class="orch-vl-btn-premium" id="orch-vl-deep-btn">⚡ Verifica Deep · 35 crediti</button>
                </div>
                <p class="orch-vl-tier-note">
                    Standard: AI veloce con grounding · Premium++ analisi semantica profonda
                </p>
            </div>
            <div class="orch-vl-pipeline" id="orch-vl-pipeline"></div>
            <div class="orch-vl-error" id="orch-vl-error"></div>
            <div class="orch-vl-report" id="orch-vl-report"></div>

            <details class="orch-output-accordion" data-orch-output="head">
                <summary>
                    <span class="orch-acc-icon">🎯</span>
                    <span class="orch-acc-title"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Output <head>')) : 'Output <head>'; ?></span>
                    <span class="orch-acc-status orch-acc-active"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Active')) : 'Active'; ?></span>
                </summary>
                <div class="orch-acc-body">
                    <p class="orch-head-intro">
                        <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Il <head> è la carta d’identità che la tua pagina mostra a Google, ChatGPT, Facebook e altre piattaforme. Lo abbiamo già configurato con le best practice 2026 — non serve toccare nulla.')) : 'Il <head> è la carta d’identità che la tua pagina mostra a Google, ChatGPT, Facebook e altre piattaforme. Lo abbiamo già configurato con le best practice 2026 — non serve toccare nulla.'; ?>
                    </p>
                    <p class="orch3-muted" style="margin:6px 0 14px; font-size:12px;">
                        Vuoi personalizzare un aspetto specifico? Clicca su una sezione qui sotto.
                    </p>

                    <!-- 3.35.68 D.5: Live preview <head> panel -->
                    <div class="orch-head-preview-panel" id="orch-head-preview-panel">
                        <div class="orch-head-preview-head">
                            <h5 style="margin:0;display:flex;align-items:center;gap:8px;">
                                <span>👁</span>
                                <span><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Anteprima live <head>')) : 'Anteprima live <head>'; ?></span>
                            </h5>
                            <div class="orch-head-preview-controls">
                                <label style="font-size:11px;display:inline-flex;align-items:center;gap:4px;cursor:pointer;" title="Solo i tag emessi da Orchestra (no WP Rocket prefetch, no Elementor, no Site Kit)">
                                    <input type="checkbox" id="orch-preview-orch-only" checked />
                                    <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Solo Orchestra')) : 'Solo Orchestra'; ?>
                                </label>
                                <label style="font-size:11px;display:inline-flex;align-items:center;gap:4px;cursor:pointer;" title="Auto-refresh quando cambi un setting (debounce 300ms)">
                                    <input type="checkbox" id="orch-preview-autorefresh" checked />
                                    🔄 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Auto')) : 'Auto'; ?>
                                </label>
                                <button type="button" class="orch3-btn orch3-btn-ghost" id="orch-preview-refresh-btn" style="font-size:11px;padding:4px 10px;">
                                    🔄 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Aggiorna')) : 'Aggiorna'; ?>
                                </button>
                            </div>
                        </div>
                        <p class="orch3-muted" style="font-size:11px;margin:4px 0 8px;">
                            <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('HTML del <head> emesso da Orchestra sulla homepage. Aggiorna dopo aver salvato un setting per vedere l\'effetto.')) : 'HTML del <head> emesso da Orchestra sulla homepage. Aggiorna dopo aver salvato un setting per vedere l\'effetto.'; ?>
                        </p>
                        <div class="orch-head-preview-meta" id="orch-head-preview-meta" style="font-size:11px;color:#64748b;margin-bottom:6px;display:none;"></div>
                        <pre class="orch-head-preview-code" id="orch-head-preview-code"><code class="orch3-muted"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Click "Aggiorna" per caricare la preview.')) : 'Click "Aggiorna" per caricare la preview.'; ?></code></pre>
                    </div>
                    <div class="orch-head-config-summary" id="orch-head-config-summary">
                        <h5 class="orch-head-config-summary-title">Riepilogo configurazione attiva</h5><p class="orch-head-config-summary-hint orch3-muted">💡 Clicca su una voce per saltare alla sezione modificabile.</p>
                        <ul class="orch-head-config-summary-list">
                            <li data-orch-summary-row="canonical" role="button" tabindex="0"><span class="orch-summary-icon">…</span> <span class="orch-summary-label">Canonical:</span> <span class="orch-summary-value">caricamento…</span></li>
                            <li data-orch-summary-row="title" role="button" tabindex="0"><span class="orch-summary-icon">…</span> <span class="orch-summary-label">Title format:</span> <span class="orch-summary-value">caricamento…</span></li>
                            <li data-orch-summary-row="meta_desc" role="button" tabindex="0"><span class="orch-summary-icon">…</span> <span class="orch-summary-label">Meta description:</span> <span class="orch-summary-value">caricamento…</span></li>
                            <li data-orch-summary-row="og" role="button" tabindex="0"><span class="orch-summary-icon">…</span> <span class="orch-summary-label">OpenGraph type:</span> <span class="orch-summary-value">caricamento…</span></li>
                            <li data-orch-summary-row="twitter" role="button" tabindex="0"><span class="orch-summary-icon">…</span> <span class="orch-summary-label">Twitter Cards:</span> <span class="orch-summary-value">caricamento…</span></li>
                            <li data-orch-summary-row="robots" role="button" tabindex="0"><span class="orch-summary-icon">…</span> <span class="orch-summary-label">Robots:</span> <span class="orch-summary-value">caricamento…</span></li>
                        </ul>
                    </div>

                    <!-- A — Canonical strategy -->
                    <details class="orch-identity-fieldset orch-schema-suboption" id="orch-head-canonical"><summary><span class="orch-head-subpanel-title"><strong>🔗 Canonical strategy</strong></span><span class="orch-head-subpanel-subtitle">Dice a Google qual è la URL ufficiale. Default funziona per il 95% dei siti.</span></summary><div class="orch-schema-suboption-body"><div class="orch-explainer"><h5>📖 Che cos’è</h5><p>Il <strong>canonical URL</strong> dice a Google qual è la versione ufficiale di una pagina. Se hai contenuti duplicati su URL diverse (es. con/senza trailing slash, con/senza www), Google sceglie il canonico. AEO Orchestra lo gestisce automaticamente usando il permalink WordPress — non serve toccare nulla salvo casi speciali.</p></div><div class="orch-current-config" data-orch-current-config="canonical"><h5>Configurazione attiva</h5><ul class="orch-current-config-list"><li class="orch3-muted">caricamento…</li></ul></div><details class="orch-advanced-options"><summary>Personalizza (opzioni avanzate)</summary><div class="orch-advanced-form">
                            <label class="orch-identity-label">Strategia base</label>
                            <div class="orch-schema-modes">
                                <label class="orch-radio-row"><input type="radio" name="orch-canonical-strategy" value="auto" data-orch-tooltip="AEO Orchestra usa il permalink standard di WordPress come canonical. Funziona per il 95% dei siti senza configurazione." /> <span><strong>Auto</strong> — usa permalink WordPress (default)</span></label>
                                <label class="orch-radio-row"><input type="radio" name="orch-canonical-strategy" value="force_same_domain" data-orch-tooltip="Usa questa opzione se hai più domini che puntano allo stesso sito (es. example.com ED example.it) e vuoi indicare a Google quello principale." /> <span><strong>Force same-domain</strong> — forza tutto al dominio canonico</span></label>
                                <label class="orch-radio-row"><input type="radio" name="orch-canonical-strategy" value="custom" data-orch-tooltip="Caso avanzato: forza un prefisso URL specifico. Utile per architetture multi-dominio non standard." /> <span><strong>Custom prefix</strong> — prefisso URL custom</span></label>
                            </div>
                            <label class="orch-identity-label">Dominio canonico</label>
                            <input type="text" id="orch-canonical-domain" class="orch-identity-input" placeholder="https://www.example.com" />
                            <label class="orch-identity-label">Custom prefix (solo per strategy=custom)</label>
                            <input type="text" id="orch-canonical-custom-prefix" class="orch-identity-input" placeholder="https://example.com/some/prefix" />

                            <label class="orch-identity-label">Trailing slash policy</label>
                            <div class="orch-schema-modes">
                                <label class="orch-radio-row"><input type="radio" name="orch-canonical-trailing" value="wp_default" data-orch-tooltip="Lascia che WordPress decida (con / per pagine, senza per post). Consigliato salvo casi specifici." /> <span>Mantieni come WordPress (default)</span></label>
                                <label class="orch-radio-row"><input type="radio" name="orch-canonical-trailing" value="with" data-orch-tooltip="Forza tutte le URL canoniche con / finale. Utile se .htaccess è configurato per redirect 301 verso URL con slash." /> <span>Forza con trailing /</span></label>
                                <label class="orch-radio-row"><input type="radio" name="orch-canonical-trailing" value="without" data-orch-tooltip="Forza tutte le URL canoniche SENZA / finale. Pattern usato da molti siti grandi (GitHub, Twitter)." /> <span>Forza senza trailing /</span></label>
                            </div>

                            <label class="orch-toggle-row">
                                <input type="checkbox" id="orch-canonical-paginated-first" />
                                <span class="orch-toggle-label">Canonical su pagine paginate punta alla pagina 1</span>
                            </label>

                            <label class="orch-identity-label">Disabilita canonical su CPT (uno per riga)</label>
                            <textarea id="orch-canonical-disabled-pts" class="orch-identity-textarea" rows="2" placeholder="es.&#10;thank-you&#10;preview"></textarea>
                        </div></details></div></details>

                    <!-- B — Title format -->
                    <details class="orch-identity-fieldset orch-schema-suboption" id="orch-head-title"><summary><span class="orch-head-subpanel-title"><strong>📝 Title format</strong></span><span class="orch-head-subpanel-subtitle">Il title visibile nei risultati Google. Personalizza per ottimizzare i clic.</span></summary><div class="orch-schema-suboption-body"><div class="orch-explainer"><h5>📖 Che cos’è</h5><p>Il <strong>title</strong> è il primo testo che vedi nei risultati Google e nei tab del browser. Influenza fortemente il CTR. Default: <code>{{title}} - {{site_name}}</code>.</p></div><div class="orch-current-config" data-orch-current-config="title"><h5>Configurazione attiva</h5><ul class="orch-current-config-list"><li class="orch3-muted">caricamento…</li></ul></div><details class="orch-advanced-options"><summary>Personalizza (opzioni avanzate)</summary><div class="orch-advanced-form">
                            <p class="orch3-muted orch-identity-help">Variabili: <code>{{title}}</code> · <code>{{site_name}}</code> · <code>{{separator}}</code> · <code>{{archive_name}}</code> · <code>{{author}}</code> · <code>{{date}}</code> · <code>{{search_query}}</code> · <code>{{tagline}}</code></p>

                            <label class="orch-identity-label">Separator</label>
                            <div class="orch-schema-modes">
                                <label class="orch-radio-row"><input type="radio" name="orch-title-sep" value="—" data-orch-tooltip="Em-dash. Stile classico, usato da grandi pubblicazioni. Si distingue bene nei risultati Google." /> <span>— (em-dash)</span></label>
                                <label class="orch-radio-row"><input type="radio" name="orch-title-sep" value="|" data-orch-tooltip="Pipe. Compatto, usato da siti tech. Occupa poco spazio nei title lunghi." /> <span>| (pipe)</span></label>
                                <label class="orch-radio-row"><input type="radio" name="orch-title-sep" value="·" data-orch-tooltip="Middle dot. Visivamente moderno. Attenzione: alcuni AI engine possono spezzare la frase in due entity." /> <span>· (dot)</span></label>
                                <label class="orch-radio-row"><input type="radio" name="orch-title-sep" value="-" data-orch-tooltip="Trattino semplice. Pattern Yoast classico. Compatibile con qualsiasi tema." /> <span>- (hyphen)</span></label>
                            </div>

                            <h5 class="orch-schema-suboption-h5">Format per Custom Post Type</h5>
                            <table class="orch-cpt-override-table">
                                <thead><tr><th>Post Type</th><th>Template</th></tr></thead>
                                <tbody id="orch-title-cpt-tbody"></tbody>
                            </table>

                            <h5 class="orch-schema-suboption-h5">Format per archivi</h5>
                            <div class="orch-grid-2">
                                <div><label class="orch-identity-label">Category</label><input type="text" id="orch-title-arc-category" class="orch-identity-input" placeholder="Categoria: {{title}} {{separator}} {{site_name}}" /></div>
                                <div><label class="orch-identity-label">Tag</label><input type="text" id="orch-title-arc-tag" class="orch-identity-input" placeholder="Tag: {{title}} {{separator}} {{site_name}}" /></div>
                                <div><label class="orch-identity-label">Author</label><input type="text" id="orch-title-arc-author" class="orch-identity-input" placeholder="Articoli di {{author}} {{separator}} {{site_name}}" /></div>
                                <div><label class="orch-identity-label">Search</label><input type="text" id="orch-title-arc-search" class="orch-identity-input" placeholder="Risultati per &quot;{{search_query}}&quot; {{separator}} {{site_name}}" /></div>
                            </div>

                            <label class="orch-toggle-row">
                                <input type="checkbox" id="orch-title-trim-60" />
                                <span class="orch-toggle-label">Trim title se >60 caratteri (Google SERP cap)</span>
                            </label>
                        </div></details></div></details>

                    <!-- C — Meta description -->
                    <details class="orch-identity-fieldset orch-schema-suboption" id="orch-head-metadesc"><summary><span class="orch-head-subpanel-title"><strong>📄 Meta description fallback</strong></span><span class="orch-head-subpanel-subtitle">Lo snippet sotto il titolo nei risultati Google. Decide da dove prenderlo.</span></summary><div class="orch-schema-suboption-body"><div class="orch-explainer"><h5>📖 Che cos’è</h5><p>La <strong>meta description</strong> è il testo descrittivo (~160 char) sotto il titolo nei risultati Google. AEO Orchestra cerca la descrizione in priorità: prima Orchestra, poi Yoast/RankMath/AIOSEO se attivi, poi excerpt, infine primi N char del content.</p></div><div class="orch-current-config" data-orch-current-config="metadesc"><h5>Configurazione attiva</h5><ul class="orch-current-config-list"><li class="orch3-muted">caricamento…</li></ul></div><details class="orch-advanced-options"><summary>Personalizza (opzioni avanzate)</summary><div class="orch-advanced-form">
                            <label class="orch-identity-label">Source priority (in ordine di preferenza, drag-reorder coming soon)</label>
                            <div id="orch-metadesc-priority-list" class="orch-metadesc-priority-list"></div>
                            <p class="orch3-muted" style="font-size:11px;">Drag-reorder UI verrà aggiunta in Stage 3. Per ora modifica via JSON in admin → bulk save.</p>

                            <div class="orch-grid-2">
                                <div><label class="orch-identity-label">Lunghezza min</label><input type="number" id="orch-metadesc-min" class="orch-identity-input" min="50" max="280" /></div>
                                <div><label class="orch-identity-label">Lunghezza max</label><input type="number" id="orch-metadesc-max" class="orch-identity-input" min="80" max="320" /></div>
                            </div>

                            <label class="orch-identity-label">Truncation strategy</label>
                            <div class="orch-schema-modes">
                                <label class="orch-radio-row"><input type="radio" name="orch-metadesc-trunc" value="word_boundary" data-orch-tooltip="Taglia all'ultima parola completa + ellissi. Più leggibile, consigliato." /> <span><strong>Word boundary + "..."</strong> (consigliato)</span></label>
                                <label class="orch-radio-row"><input type="radio" name="orch-metadesc-trunc" value="hard_cap" data-orch-tooltip="Taglio netto al limite max, anche a metà parola. Solo se serve controllo rigoroso." /> <span><strong>Hard cap</strong> a max</span></label>
                                <label class="orch-radio-row"><input type="radio" name="orch-metadesc-trunc" value="sentence_boundary" data-orch-tooltip="Cerca l'ultima frase completa (punto/!/?). Più naturale ma può tagliare prima del max." /> <span><strong>Sentence boundary</strong> (più naturale)</span></label>
                            </div>

                            <label class="orch-identity-label">Nome ACF custom field (se ACF attivo)</label>
                            <input type="text" id="orch-metadesc-acf-field" class="orch-identity-input" placeholder="meta_description" />

                            <label class="orch-identity-label">Pattern per archivi</label>
                            <input type="text" id="orch-metadesc-archive-pattern" class="orch-identity-input" placeholder="Articoli su {{archive_name}} di {{site_name}}." />
                        </div></details></div></details>

                    <!-- D — OpenGraph -->
                    <details class="orch-identity-fieldset orch-schema-suboption" id="orch-head-og"><summary><span class="orch-head-subpanel-title"><strong>🔗 OpenGraph defaults (Facebook, LinkedIn, AI engine)</strong></span><span class="orch-head-subpanel-subtitle">Come il tuo sito appare quando viene condiviso sui social.</span></summary><div class="orch-schema-suboption-body"><div class="orch-explainer"><h5>📖 Che cos’è</h5><p><strong>OpenGraph</strong> è lo standard usato da Facebook, LinkedIn, Pinterest, WhatsApp e dagli AI engine (ChatGPT, Claude) per generare anteprime quando un link viene condiviso. Servono <code>og:image</code> (1200x630px), <code>og:type</code>, <code>og:locale</code>, <code>og:site_name</code>.</p></div><div class="orch-current-config" data-orch-current-config="og"><h5>Configurazione attiva</h5><ul class="orch-current-config-list"><li class="orch3-muted">caricamento…</li></ul></div><details class="orch-advanced-options"><summary>Personalizza (opzioni avanzate)</summary><div class="orch-advanced-form">
                            <div class="orch-image-picker-row" data-orch-img-target="orch-og-image-fallback" data-orch-img-ratio="1.91">
                                <label class="orch-identity-label">og:image fallback (consigliato 1200×630px)</label>
                                <p class="description orch3-muted" style="margin:0 0 6px;font-size:11px;">Immagine OG default per pagine senza featured image. Mostrata su Facebook/LinkedIn quando un utente condivide un link al tuo sito.</p>
                                <div class="orch-image-input-group">
                                    <input type="url" id="orch-og-image-fallback" class="orch-identity-input orch-image-input" placeholder="https://www.example.com/wp-content/.../og-fallback.png" />
                                    <button type="button" class="button orch-media-picker-btn" data-orch-img-target="orch-og-image-fallback" data-orch-img-title="Scegli OG image fallback">📁 Scegli da Media Library</button>
                                    <button type="button" class="button orch-media-remove-btn" data-orch-img-target="orch-og-image-fallback" style="display:none;">🗑 Rimuovi</button>
                                </div>
                                <div class="orch-image-preview" data-orch-img-preview="orch-og-image-fallback"></div>
                                <div class="orch-image-validation" data-orch-img-validation="orch-og-image-fallback"></div>
                            </div>

                            <div class="orch-grid-2">
                                <div><label class="orch-identity-label">og:locale (override, vuoto = auto da WP)</label><input type="text" id="orch-og-locale-override" class="orch-identity-input" placeholder="it_IT" /></div>
                                <div><label class="orch-identity-label">og:site_name (override)</label><input type="text" id="orch-og-site-name-override" class="orch-identity-input" /></div>
                            </div>

                            <h5 class="orch-schema-suboption-h5">og:type per Custom Post Type</h5>
                            <table class="orch-cpt-override-table">
                                <thead><tr><th>Post Type</th><th>og:type</th></tr></thead>
                                <tbody id="orch-og-cpt-tbody"></tbody>
                            </table>

                            <h5 class="orch-schema-suboption-h5">Article-specific (per og:type=article)</h5>
                            <label class="orch-toggle-row"><input type="checkbox" id="orch-og-article-author" /> <span class="orch-toggle-label">Emetti article:author (link al WP author profile)</span></label>
                            <label class="orch-toggle-row"><input type="checkbox" id="orch-og-article-published" /> <span class="orch-toggle-label">Emetti article:published_time + modified_time</span></label>
                            <label class="orch-toggle-row"><input type="checkbox" id="orch-og-article-section" /> <span class="orch-toggle-label">Emetti article:section (da categoria primaria)</span></label>
                            <label class="orch-toggle-row"><input type="checkbox" id="orch-og-article-tag" /> <span class="orch-toggle-label">Emetti article:tag (da tag/keywords)</span></label>
                        </div></details></div></details>

                    <!-- E — Twitter Cards -->
                    <details class="orch-identity-fieldset orch-schema-suboption" id="orch-head-twitter"><summary><span class="orch-head-subpanel-title"><strong>🐦 Twitter Cards</strong></span><span class="orch-head-subpanel-subtitle">Anteprima specifica per Twitter/X quando condividi un link.</span></summary><div class="orch-schema-suboption-body"><div class="orch-explainer"><h5>📖 Che cos’è</h5><p>Twitter/X ignora gli OpenGraph e usa il proprio formato (<code>twitter:card</code>). Card consigliato: <strong>summary_large_image</strong>. Servi anche il tuo handle Twitter aziendale.</p></div><div class="orch-current-config" data-orch-current-config="twitter"><h5>Configurazione attiva</h5><ul class="orch-current-config-list"><li class="orch3-muted">caricamento…</li></ul></div><details class="orch-advanced-options"><summary>Personalizza (opzioni avanzate)</summary><div class="orch-advanced-form">
                            <label class="orch-identity-label">Card type</label>
                            <div class="orch-schema-modes">
                                <label class="orch-radio-row"><input type="radio" name="orch-twitter-card" value="summary_large_image" data-orch-tooltip="Card grande con immagine prominente. Migliore CTR per articoli su Twitter/X." /> <span><strong>summary_large_image</strong> (consigliato per articoli)</span></label>
                                <label class="orch-radio-row"><input type="radio" name="orch-twitter-card" value="summary" data-orch-tooltip="Card compatta con immagine piccola. Per contenuti senza featured image prominente." /> <span><strong>summary</strong> (compatto)</span></label>
                                <label class="orch-radio-row"><input type="radio" name="orch-twitter-card" value="player" data-orch-tooltip="Card con player video embedded. Richiede markup specifico + approvazione Twitter." /> <span><strong>player</strong> (per video)</span></label>
                                <label class="orch-radio-row"><input type="radio" name="orch-twitter-card" value="app" data-orch-tooltip="Card per linkare a una mobile app. Caso avanzato." /> <span><strong>app</strong></span></label>
                            </div>

                            <label class="orch-identity-label">twitter:site (handle azienda)</label>
                            <input type="text" id="orch-twitter-site" class="orch-identity-input" placeholder="@yourbrand" />

                            <label class="orch-identity-label">twitter:creator</label>
                            <div class="orch-schema-modes">
                                <label class="orch-radio-row"><input type="radio" name="orch-twitter-creator-mode" value="auto" /> <span>Auto (da WP user profile field "twitter")</span></label>
                                <label class="orch-radio-row"><input type="radio" name="orch-twitter-creator-mode" value="fixed" /> <span>Fisso per tutti i post</span></label>
                                <label class="orch-radio-row"><input type="radio" name="orch-twitter-creator-mode" value="disabled" /> <span>Disabilita twitter:creator</span></label>
                            </div>
                            <input type="text" id="orch-twitter-creator-handle" class="orch-identity-input" placeholder="@handle (solo per Fisso)" />
                        </div></details></div></details>

                    <!-- F — Robots -->
                    <details class="orch-identity-fieldset orch-schema-suboption" id="orch-head-hreflang">
                        <summary>
                            <span class="orch-head-subpanel-title"><strong>🌍 Hreflang multilingua</strong></span>
                            <span class="orch-head-subpanel-subtitle">Tag <code>&lt;link rel="alternate" hreflang&gt;</code> per siti tradotti (WPML/Polylang/TranslatePress).</span>
                        </summary>
                        <div class="orch-schema-suboption-body">
                            <div class="orch-explainer">
                                <h5>📖 Che cos'è</h5>
                                <p>I tag <strong>hreflang</strong> dicono a Google quali sono le versioni tradotte di una pagina. Senza, Google potrebbe mostrare la versione sbagliata della pagina ai tuoi utenti (es. inglese a un italiano). Necessari SOLO se hai sito multilingua.</p>
                            </div>
                            <div class="orch-current-config" data-orch-current-config="hreflang">
                                <h5>Detection plugin multilingua</h5>
                                <div id="orch-hreflang-detection" class="orch3-muted" style="font-size:12px;">caricamento…</div>
                                <div id="orch-hreflang-toggle-row" style="display:none;margin-top:10px;">
                                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;">
                                        <input type="checkbox" id="orch-hreflang-enabled-toggle" />
                                        <span><strong>Emetti hreflang automaticamente</strong></span>
                                    </label>
                                </div>
                                <div id="orch-hreflang-preview" style="display:none;margin-top:10px;font-size:11px;font-family:ui-monospace,monospace;background:#0f172a;color:#e2e8f0;padding:10px;border-radius:6px;max-height:160px;overflow:auto;"></div>
                            </div>
                        </div>
                    </details>

                    <details class="orch-identity-fieldset orch-schema-suboption" id="orch-head-robots"><summary><span class="orch-head-subpanel-title"><strong>🤖 Robots default</strong></span><span class="orch-head-subpanel-subtitle">Quali pagine Google deve indicizzare e quali no.</span></summary><div class="orch-schema-suboption-body"><div class="orch-explainer"><h5>📖 Che cos’è</h5><p>Il tag <code>meta robots</code> dice a Google se può indicizzare la pagina (index) e seguire i link interni (follow). Default index/follow per tutti i CPT pubblici. Disabilita index su pagine tipo thank-you, redirect, preview che non vuoi nei risultati Google.</p></div><div class="orch-current-config" data-orch-current-config="robots"><h5>Configurazione attiva</h5><ul class="orch-current-config-list"><li class="orch3-muted">caricamento…</li></ul></div><details class="orch-advanced-options"><summary>Personalizza (opzioni avanzate)</summary><div class="orch-advanced-form">
                            <h5 class="orch-schema-suboption-h5">Per Custom Post Type</h5>
                            <table class="orch-cpt-override-table" id="orch-robots-cpt-table">
                                <thead><tr><th>Post Type</th><th>index</th><th>follow</th></tr></thead>
                                <tbody id="orch-robots-cpt-tbody"></tbody>
                            </table>

                            <h5 class="orch-schema-suboption-h5">Per archivi</h5>
                            <table class="orch-cpt-override-table">
                                <thead><tr><th>Archive</th><th>index</th><th>follow</th></tr></thead>
                                <tbody id="orch-robots-arc-tbody">
                                    <tr><td>Category</td><td><input type="checkbox" data-orch-arc-robots="category" data-key="index"></td><td><input type="checkbox" data-orch-arc-robots="category" data-key="follow"></td></tr>
                                    <tr><td>Tag</td><td><input type="checkbox" data-orch-arc-robots="tag" data-key="index"></td><td><input type="checkbox" data-orch-arc-robots="tag" data-key="follow"></td></tr>
                                    <tr><td>Author</td><td><input type="checkbox" data-orch-arc-robots="author" data-key="index"></td><td><input type="checkbox" data-orch-arc-robots="author" data-key="follow"></td></tr>
                                    <tr><td>Date</td><td><input type="checkbox" data-orch-arc-robots="date" data-key="index"></td><td><input type="checkbox" data-orch-arc-robots="date" data-key="follow"></td></tr>
                                    <tr><td>Search</td><td><input type="checkbox" data-orch-arc-robots="search" data-key="index"></td><td><input type="checkbox" data-orch-arc-robots="search" data-key="follow"></td></tr>
                                    <tr><td>404 pages</td><td><input type="checkbox" data-orch-arc-robots="not_found" data-key="index"></td><td><input type="checkbox" data-orch-arc-robots="not_found" data-key="follow"></td></tr>
                                </tbody>
                            </table>

                            <h5 class="orch-schema-suboption-h5">Regole automatiche</h5>
                            <label class="orch-toggle-row"><input type="checkbox" id="orch-robots-paginated" /> <span class="orch-toggle-label">Auto-noindex pagine paginate (page/2, page/3, ecc.)</span></label>
                            <label class="orch-toggle-row"><input type="checkbox" id="orch-robots-low-words" /> <span class="orch-toggle-label">Auto-noindex post con &lt; N parole</span></label>
                            <label class="orch-identity-label">Threshold (parole)</label>
                            <input type="number" id="orch-robots-low-words-threshold" class="orch-identity-input" min="50" max="2000" />
                        </div></details></div></details>                    <div class="orch-preview-box" data-orch-preview="head">
                        <div class="orch-preview-header">
                            <h4 class="orch-preview-title">📡 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Anteprima live')) : 'Anteprima live'; ?></h4>
                            <div class="orch-preview-actions">
                                <button type="button" class="orch3-btn orch3-btn-ghost orch-preview-refresh" data-orch-preview-refresh="head">
                                    🔄 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Aggiorna anteprima')) : 'Aggiorna anteprima'; ?>
                                </button>
                                <a href="<?php echo esc_url(home_url('/')); ?>?_orch_preview_bust=<?php echo esc_attr(time()); ?>" target="_blank" rel="noopener" class="orch3-btn orch3-btn-ghost orch-preview-open">
                                    🔗 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Apri homepage')) : 'Apri homepage'; ?>
                                </a>
                            </div>
                        </div>
                        <div class="orch-preview-meta ">
                            <span class="orch-preview-loading orch3-muted">— <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Click \'Aggiorna anteprima\' per caricare')) : 'Click \'Aggiorna anteprima\' per caricare'; ?> —</span>
                        </div>
                        <pre class="orch-preview-content language-markup" data-orch-preview-loaded="false"><code class="language-markup"></code></pre>
                        
                    </div>
                    </div>
            </details>

            <details class="orch-output-accordion" data-orch-output="sitemap">
                <summary>
                    <span class="orch-acc-icon">🗺️</span>
                    <span class="orch-acc-title"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Sitemap.xml')) : 'Sitemap.xml'; ?></span>
                    <span class="orch-acc-status orch-acc-active"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Active')) : 'Active'; ?></span>
                </summary>
                <div class="orch-acc-body">
                    <!-- 3.35.68 status summary scaffold (sitemap) -->
                    <div class="orch-acc-status-summary" id="orch-status-summary-sitemap" data-orch-status-kind="sitemap">
                        <h5 class="orch-acc-status-summary-title">📊 Riepilogo configurazione attiva</h5>
                        <ul class="orch-acc-status-summary-list">
                            <li><span class="orch-acc-status-icon">…</span><span class="orch3-muted">caricamento…</span></li>
                        </ul>
                    </div>
                    
                    <!-- 3.35.72: plain-language intro -->
                    <div class="orch-acc-intro" data-orch-sitemap-tripletta="intro">
                        <p style="margin:8px 0;font-size:13px;line-height:1.55;color:#334155;">
                            <strong>🗺️ La sitemap.xml è la mappa del tuo sito che dai a Google.</strong> Senza, Google deve scoprire le pagine da solo (lento). Con, gli dici subito tutte le URL importanti + ogni quanto cambiano + quale priorità hanno.
                            Lo abbiamo già configurato dinamicamente in base ai ruoli del tuo sito — non serve toccare nulla.
                        </p>
                    </div>

                    <fieldset class="orch-identity-fieldset">
                        <legend><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Detection ambiente')) : 'Detection ambiente'; ?></legend>
                        <div id="orch-sitemap-detection" class="orch-schema-detection">…</div>
                    </fieldset>

                    <fieldset class="orch-identity-fieldset">
                        <legend><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Sostituisci sitemap esistente')) : 'Sostituisci sitemap esistente'; ?></legend>
                        <label class="orch-toggle-row">
                            <input type="checkbox" id="orch-sitemap-takeover" />
                            <span class="orch-toggle-label">
                                <strong><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('AEO Orchestra è la sitemap canonica')) : 'AEO Orchestra è la sitemap canonica'; ?></strong>
                                <span class="orch3-muted"> — <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('quando attivo, /sitemap.xml è servito da AEO Orchestra (non da Yoast/RankMath/AIOSEO se installati). Lascia disattivato per coesistere con sitemap di altri plugin.')) : 'quando attivo, /sitemap.xml è servito da AEO Orchestra (non da Yoast/RankMath/AIOSEO se installati). Lascia disattivato per coesistere con sitemap di altri plugin.'; ?></span>
                            </span>
                        </label>
                    </fieldset>

                    <!-- 3.35.72 Sub-panel 1: Index types -->
                    <details class="orch-identity-fieldset orch-schema-suboption" id="orch-sitemap-index-types" data-orch-sitemap-tripletta="index">
                        <summary>
                            <span class="orch-head-subpanel-title"><strong>📂 Index types (sitemap per CPT)</strong></span>
                            <span class="orch-head-subpanel-subtitle">Per ogni tipo di contenuto, una sitemap separata aggregata nel sitemap-index.</span>
                        </summary>
                        <div class="orch-schema-suboption-body">
                            <div class="orch-explainer">
                                <h5>📖 Che cos'è</h5>
                                <p>Per ogni tipo di contenuto (post, page, prodotto, blog, custom post type) Orchestra genera una sitemap separata. Google le aggrega tutte tramite il <strong>sitemap-index.xml</strong> principale. Limite di 50.000 URL per sub-sitemap (best practice Google) — Orchestra rispetta questo limite automaticamente.</p>
                            </div>
                            <div class="orch-current-config" data-orch-current-config="sitemap-index">
                                <h5>✅ Configurazione attiva</h5>
                                <ul class="orch-current-config-list"><li class="orch3-muted">caricamento…</li></ul>
                            </div>
                            <details class="orch-advanced-options">
                                <summary>🛠 Personalizza CPT inclusi</summary>
                                <div class="orch-advanced-form">
                                    <p class="orch3-muted" style="font-size:11px;margin:0 0 8px;">CPT con <code>public => false</code> sono esclusi automaticamente.</p>
                                    <div id="orch-sitemap-pt-grid" class="orch-filt-pt-grid">
                                        <span class="orch3-muted" style="font-size:12px;">…</span>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </details>

                    <!-- 3.35.72 Sub-panel 2: Priority per role (read-only — full slider customization in 3.35.73) -->
                    <details class="orch-identity-fieldset orch-schema-suboption" id="orch-sitemap-priority" data-orch-sitemap-tripletta="priority">
                        <summary>
                            <span class="orch-head-subpanel-title"><strong>🎚️ Priority per role</strong></span>
                            <span class="orch-head-subpanel-subtitle">Quanto importanti sono le pagine — calcolato dinamicamente per ruolo.</span>
                        </summary>
                        <div class="orch-schema-suboption-body">
                            <div class="orch-explainer">
                                <h5>📖 Che cos'è</h5>
                                <p>Priority dice a Google quali sono le pagine più importanti del tuo sito (range 0.0-1.0). Homepage di solito 1.0, privacy 0.1-0.3, articoli 0.7-0.9. Orchestra calcola priority dinamicamente per ognuno dei ruoli identificati nel sito — non serve toccare nulla.</p>
                            </div>
                            <div class="orch-current-config" data-orch-current-config="sitemap-priority">
                                <h5>✅ Configurazione attiva</h5>
                                <ul class="orch-current-config-list"><li class="orch3-muted">caricamento…</li></ul>
                            </div>
                            <details class="orch-advanced-options">
                                <summary>🛠 Personalizza priority per role</summary>
                                <div class="orch-advanced-form">
                                    <div style="margin:0 0 8px;display:flex;justify-content:space-between;align-items:center;gap:8px;">
                                        <p class="orch3-muted" style="margin:0;font-size:11px;">Slider 0.0-1.0. <strong>Cambio salvato all\'istante</strong> (sitemap cache busted).</p>
                                        <button type="button" class="orch3-btn orch3-btn-ghost" id="orch-sitemap-priority-reset-all" style="font-size:11px;">↺ Reset all</button>
                                    </div>
                                    <table class="orch-cpt-override-table" id="orch-sitemap-priority-table">
                                        <thead><tr><th>Role</th><th style="width:55%;">Priority</th><th>Reset</th></tr></thead>
                                        <tbody><tr><td colspan="3" class="orch3-muted">Caricamento…</td></tr></tbody>
                                    </table>
                                </div>
                            </details>
                        </div>
                    </details>

                    <!-- 3.35.72 Sub-panel 3: Changefreq + Robots in sitemap (read-only) -->
                    <details class="orch-identity-fieldset orch-schema-suboption" id="orch-sitemap-changefreq" data-orch-sitemap-tripletta="changefreq">
                        <summary>
                            <span class="orch-head-subpanel-title"><strong>🕐 Changefreq + Robots in sitemap</strong></span>
                            <span class="orch-head-subpanel-subtitle">Quanto spesso Google deve ricontrollare ogni pagina + quali indicizzare.</span>
                        </summary>
                        <div class="orch-schema-suboption-body">
                            <div class="orch-explainer">
                                <h5>📖 Che cos'è</h5>
                                <p><strong>Changefreq</strong> dice a Google quanto spesso aspettarsi cambiamenti (always / hourly / daily / weekly / monthly / yearly / never). <strong>Robots in sitemap</strong> dice se l\'URL deve essere indicizzato. Orchestra setta entrambi automaticamente in base al ruolo della pagina.</p>
                            </div>
                            <div class="orch-current-config" data-orch-current-config="sitemap-changefreq">
                                <h5>✅ Configurazione attiva</h5>
                                <ul class="orch-current-config-list"><li class="orch3-muted">caricamento…</li></ul>
                            </div>
                            <details class="orch-advanced-options">
                                <summary>🛠 Personalizza changefreq + noindex per role</summary>
                                <div class="orch-advanced-form">
                                    <p class="orch3-muted" style="margin:0 0 8px;font-size:11px;">Cambio salvato all\'istante. Noindex checked = URL completamente esclusa dal sitemap.</p>
                                    <table class="orch-cpt-override-table" id="orch-sitemap-changefreq-table">
                                        <thead><tr><th>Role</th><th>Changefreq</th><th>Noindex</th><th>Reset</th></tr></thead>
                                        <tbody><tr><td colspan="4" class="orch3-muted">Caricamento…</td></tr></tbody>
                                    </table>
                                </div>
                            </details>
                        </div>
                    </details>
                    <p class="orch3-muted" style="font-size:12px; margin-top:14px;">
                        <strong><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('In arrivo Stage 2:')) : 'In arrivo Stage 2:'; ?></strong>
                        <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('priority + frequenza per tipo, taxonomies, silenziamento attivo via filter hooks dei competitor.')) : 'priority + frequenza per tipo, taxonomies, silenziamento attivo via filter hooks dei competitor.'; ?>
                    </p>
                
                    <div class="orch-preview-box" data-orch-preview="sitemap">
                        <div class="orch-preview-header">
                            <h4 class="orch-preview-title">📡 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Anteprima live')) : 'Anteprima live'; ?></h4>
                            <div class="orch-preview-actions">
                                <button type="button" class="orch3-btn orch3-btn-ghost orch-preview-refresh" data-orch-preview-refresh="sitemap">
                                    🔄 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Aggiorna anteprima')) : 'Aggiorna anteprima'; ?>
                                </button>
                                <a href="<?php echo esc_url(home_url('/sitemap.xml')); ?>?_orch_preview_bust=<?php echo esc_attr(time()); ?>" target="_blank" rel="noopener" class="orch3-btn orch3-btn-ghost orch-preview-open">
                                    🔗 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Apri /sitemap.xml')) : 'Apri /sitemap.xml'; ?>
                                </a>
                            </div>
                        </div>
                        <div class="orch-preview-meta ">
                            <span class="orch-preview-loading orch3-muted">— <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Click \'Aggiorna anteprima\' per caricare')) : 'Click \'Aggiorna anteprima\' per caricare'; ?> —</span>
                        </div>
                        <pre class="orch-preview-content language-markup" data-orch-preview-loaded="false"><code class="language-markup"></code></pre>
                        
                    </div>
                    </div>
            </details>

            <details class="orch-output-accordion" open>
                <summary>
                    <span class="orch-acc-icon">🤖</span>
                    <span class="orch-acc-title"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('llms.txt')) : 'llms.txt'; ?></span>
                    <span class="orch-acc-status orch-acc-active"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Active')) : 'Active'; ?></span>
                </summary>
                <div class="orch-acc-body">
                    <!-- 3.35.68 status summary scaffold (llms) -->
                    <div class="orch-acc-status-summary" id="orch-status-summary-llms" data-orch-status-kind="llms">
                        <h5 class="orch-acc-status-summary-title">📊 Riepilogo configurazione attiva</h5>
                        <ul class="orch-acc-status-summary-list">
                            <li><span class="orch-acc-status-icon">…</span><span class="orch3-muted">caricamento…</span></li>
                        </ul>
                    </div>
                    
                    <!-- 3.35.73: plain-language intro -->
                    <div class="orch-acc-intro" data-orch-llms-tripletta="intro">
                        <p style="margin:8px 0 14px;font-size:13px;line-height:1.55;color:#334155;">
                            <strong>🤖 llms.txt è la "mappa strategica" del tuo sito per ChatGPT, Claude, Perplexity e gli altri AI engine.</strong>
                            Mentre Google ha sitemap.xml (lista TUTTE le URL), gli LLM si aspettano una mappa CURATA con SOLO le pagine importanti + un breve about strategico. Lo abbiamo configurato con le pagine più rappresentative del tuo sito — visibile su <code>/llms.txt</code>.
                        </p>
                    </div>

                    <!-- 3.35.73 sub-panel 1: Strategy mode -->
                    <details class="orch-identity-fieldset orch-schema-suboption" id="orch-llms-strategy" data-orch-llms-tripletta="strategy">
                        <summary>
                            <span class="orch-head-subpanel-title"><strong>🎯 Strategy mode</strong></span>
                            <span class="orch-head-subpanel-subtitle">Come selezionare le pagine in llms.txt: curato manualmente / auto / mixed.</span>
                        </summary>
                        <div class="orch-schema-suboption-body">
                            <div class="orch-explainer">
                                <h5>📖 Che cos'è</h5>
                                <p>Strategy mode decide come vengono selezionate le pagine in llms.txt. <strong>Curated</strong> = scegli tu manualmente le N pagine più importanti. <strong>Auto-curated</strong> = AEO Orchestra sceglie automaticamente in base ai ruoli del sito (preferendo guide + offer commerciali). <strong>Mixed</strong> = top N curated + auto fill.</p>
                            </div>
                            <div class="orch-current-config" data-orch-current-config="llms-strategy">
                                <h5>✅ Configurazione attiva</h5>
                                <ul class="orch-current-config-list"><li class="orch3-muted">caricamento…</li></ul>
                            </div>
                            <p class="orch3-muted" style="font-size:11px;margin:8px 0 0;">🛠 <strong>Customizzazione strategy mode + count selector:</strong> in arrivo nel prossimo deploy (richiede backend extension class-llms-txt). Per ora la modalità è gestita automaticamente in base ai ruoli configurati.</p>
                        </div>
                    </details>

                    <!-- 3.35.73 sub-panel 2: Featured pages -->
                    <details class="orch-identity-fieldset orch-schema-suboption" id="orch-llms-featured-info" data-orch-llms-tripletta="featured">
                        <summary>
                            <span class="orch-head-subpanel-title"><strong>📌 Featured pages</strong></span>
                            <span class="orch-head-subpanel-subtitle">Le 10-30 pagine più rappresentative che AI ingestion vede subito.</span>
                        </summary>
                        <div class="orch-schema-suboption-body">
                            <div class="orch-explainer">
                                <h5>📖 Che cos'è</h5>
                                <p>Featured pages sono le 10-30 pagine più rappresentative del tuo sito che AI ingestion dovrebbe vedere subito. Le scegliamo manualmente per controllare cosa ChatGPT/Claude impara su di te. <strong>Suggerimento</strong>: home, about, top blog post, FAQ, top 3 prodotti/servizi.</p>
                            </div>
                            <div class="orch-current-config" data-orch-current-config="llms-featured">
                                <h5>✅ Configurazione attiva</h5>
                                <ul class="orch-current-config-list"><li class="orch3-muted">caricamento…</li></ul>
                            </div>
                            <details class="orch-advanced-options" open>
                                <summary>🪄 Auto-suggest top N pagine</summary>
                                <div class="orch-advanced-form">
                                    <p class="orch3-muted" style="margin:0 0 10px;font-size:12px;">AEO Orchestra sceglie le pagine più rappresentative del tuo sito via scoring algoritmico (role + recency + engagement + search value). <strong>Nessun costo AI</strong>, solo PHP.</p>
                                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:8px;">
                                        <label style="font-size:12px;display:inline-flex;align-items:center;gap:4px;">
                                            Top
                                            <select id="orch-llms-autosuggest-count" style="font-size:12px;">
                                                <option value="5">5</option>
                                                <option value="10" selected>10</option>
                                                <option value="15">15</option>
                                                <option value="20">20</option>
                                                <option value="30">30</option>
                                            </select>
                                            pagine
                                        </label>
                                        <button type="button" class="orch3-btn orch3-btn-primary" id="orch-llms-autosuggest-btn">🪄 Auto-suggest</button>
                                    </div>
                                    <p class="orch3-muted" style="font-size:11px;margin:0;">💡 Per personalizzazione manuale (aggiungere/togliere pagine), usa il form più sotto in questo accordion.</p>
                                </div>
                            </details>
                        </div>
                    </details>

                    <!-- 3.35.75 Auto-suggest preview modal -->
                    <div id="orch-llms-autosuggest-modal" class="orch-modal-backdrop" style="display:none;">
                        <div class="orch-modal-window" style="max-width:780px;">
                            <div class="orch-modal-head">
                                <h3 style="margin:0;">🪄 Top pagine suggerite</h3>
                                <button type="button" class="orch-modal-close" id="orch-llms-autosuggest-close">×</button>
                            </div>
                            <div class="orch-modal-body">
                                <p class="orch3-muted" style="margin:0 0 10px;font-size:12px;">Score breakdown disponibile in hover sul punteggio. Deseleziona quelle che NON vuoi includere.</p>
                                <div id="orch-llms-autosuggest-list" style="max-height:380px;overflow:auto;border:1px solid #e2e8f0;border-radius:6px;padding:8px;"></div>
                                <div style="margin-top:10px;display:flex;align-items:center;gap:10px;">
                                    <label style="font-size:12px;display:inline-flex;align-items:center;gap:4px;">
                                        <input type="checkbox" id="orch-llms-autosuggest-merge" />
                                        Aggiungi alla selezione esistente (merge invece di replace)
                                    </label>
                                </div>
                            </div>
                            <div class="orch-modal-foot">
                                <button type="button" class="orch3-btn orch3-btn-ghost" id="orch-llms-autosuggest-cancel">Annulla</button>
                                <button type="button" class="orch3-btn orch3-btn-primary" id="orch-llms-autosuggest-apply">✓ Accept (applica selezione)</button>
                            </div>
                        </div>
                    </div>

                    <!-- 3.35.73 sub-panel 3: About strategico -->
                    <details class="orch-identity-fieldset orch-schema-suboption" id="orch-llms-about-info" data-orch-llms-tripletta="about">
                        <summary>
                            <span class="orch-head-subpanel-title"><strong>📝 About strategico</strong></span>
                            <span class="orch-head-subpanel-subtitle">Paragrafo iniziale di llms.txt: cosa fai, per chi, dove, da quando.</span>
                        </summary>
                        <div class="orch-schema-suboption-body">
                            <div class="orch-explainer">
                                <h5>📖 Che cos'è</h5>
                                <p>L\'about strategico è il paragrafo iniziale di llms.txt che presenta la tua azienda agli AI. Dovrebbe contenere: cosa fai, per chi, dove, da quando, differenziatori. <strong>Lunghezza ottimale 200-500 caratteri.</strong></p>
                            </div>
                            <div class="orch-current-config" data-orch-current-config="llms-about">
                                <h5>✅ Configurazione attiva</h5>
                                <ul class="orch-current-config-list"><li class="orch3-muted">caricamento…</li></ul>
                            </div>
                            <p class="orch3-muted" style="font-size:11px;margin:8px 0 0;">🛠 <strong>Personalizza About</strong>: usa il form "Identità business" più sotto in questo accordion (sezione 1) per editare la descrizione strategica.</p>
                        </div>
                    </details>

                    <div class="orch-stage25-banner" id="orch-stage25-banner" data-orch-stage25-banner="loading">
                        <div class="orch-stage25-banner-loading">
                            <span class="rv-spinner"></span> <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Caricamento profilo + ruoli pagine…')) : 'Caricamento profilo + ruoli pagine…'; ?>
                        </div>
                        <!-- Banner content rendered dynamically by JS based on tier + state -->
                    </div>
                    <!-- Legacy scan status pill (kept for backward compat with renderRegenReport etc.) -->
                    <div class="orch-scan-status" id="orch-scan-status"></div>

                    <div id="orch-identity-loading" class="orch3-muted" style="padding:14px 0;">
                        <span class="rv-spinner"></span> <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Caricamento profilo…')) : 'Caricamento profilo…'; ?>
                    </div>

                                        <!-- 3.35.83: Info notice — link al nuovo pannello dedicato -->
                    <div class="orch3-card orch-bp-migration-notice" style="margin: 12px 0; padding: 12px 16px; border-left: 4px solid #2563eb; background: #eff6ff;">
                        <strong>🆕 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Nuovo pannello dedicato disponibile')) : 'Nuovo pannello dedicato disponibile'; ?></strong>:
                        <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-business-profile')); ?>" style="font-weight: 600;">
                            🏢 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Profilo Business')) : 'Profilo Business'; ?> →
                        </a>
                        <p style="margin: 6px 0 0; font-size: 12px; color: var(--orch-muted, #475569);">
                            <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Editor completo con sezione public/private, anteprima context AI live, auto-save e Brand Voice integration. Questo form resta funzionante per backward compat — entrambe le UI editano la stessa identità.')) : 'Editor completo con sezione public/private, anteprima context AI live, auto-save. Questo form resta funzionante.'; ?>
                        </p>
                    </div>

                    <form id="orch-identity-form" class="orch-identity-form" style="display:none;" onsubmit="return false;">
                        <div class="orch-identity-status-bar" id="orch-identity-status"></div>

                        <fieldset class="orch-identity-fieldset">
                            <legend>1. <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Identità business')) : 'Identità business'; ?></legend>
                            <p class="orch3-muted orch-identity-help"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Nome, descrizione in una riga, settore.')) : 'Nome, descrizione in una riga, settore.'; ?></p>
                            <label class="orch-identity-label" for="orch-id-name"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Nome azienda / sito')) : 'Nome azienda / sito'; ?></label>
                            <input type="text" id="orch-id-name" name="business_name" class="orch-identity-input" maxlength="200" />
                            <label class="orch-identity-label" for="orch-id-desc"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Descrizione (1 riga)')) : 'Descrizione (1 riga)'; ?></label>
                            <textarea id="orch-id-desc" name="business_description" class="orch-identity-textarea" rows="2" maxlength="280"></textarea>
                            <label class="orch-identity-label" for="orch-id-industry"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Settore / industria')) : 'Settore / industria'; ?></label>
                            <input type="text" id="orch-id-industry" name="industry" class="orch-identity-input" maxlength="120" placeholder="es. edilizia, software B2B, consulenza HR" />
                        </fieldset>

                        <fieldset class="orch-identity-fieldset">
                            <legend>2. <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Differenzianti strategici')) : 'Differenzianti strategici'; ?></legend>
                            <p class="orch3-muted orch-identity-help"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Cosa ti rende diverso. Massimo 5 voci — titolo + 1-2 frasi naturali (evita separatori speciali).')) : 'Cosa ti rende diverso. Massimo 5 voci — titolo + 1-2 frasi naturali (evita separatori speciali).'; ?></p>
                            <div class="orch-identity-list" data-orch-list="differentiators" data-orch-list-max="5"></div>
                            <button type="button" class="orch3-btn orch3-btn-ghost orch-identity-add" data-orch-list-add="differentiators">+ <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Aggiungi differenziante')) : 'Aggiungi differenziante'; ?></button>
                        </fieldset>

                        <fieldset class="orch-identity-fieldset">
                            <legend>3. <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Territori serviti')) : 'Territori serviti'; ?></legend>
                            <p class="orch3-muted orch-identity-help"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Paesi, regioni o aree dove operi. Una voce per riga.')) : 'Paesi, regioni o aree dove operi. Una voce per riga.'; ?></p>
                            <div class="orch-identity-list" data-orch-list="territories" data-orch-list-max="20"></div>
                            <button type="button" class="orch3-btn orch3-btn-ghost orch-identity-add" data-orch-list-add="territories">+ <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Aggiungi territorio')) : 'Aggiungi territorio'; ?></button>
                        </fieldset>

                        <fieldset class="orch-identity-fieldset">
                            <legend>4. <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Casi d\'uso tipici')) : 'Casi d\'uso tipici'; ?></legend>
                            <p class="orch3-muted orch-identity-help"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Per cosa i clienti scelgono il tuo sito/servizio. Massimo 5.')) : 'Per cosa i clienti scelgono il tuo sito/servizio. Massimo 5.'; ?></p>
                            <div class="orch-identity-list" data-orch-list="use_cases" data-orch-list-max="5"></div>
                            <button type="button" class="orch3-btn orch3-btn-ghost orch-identity-add" data-orch-list-add="use_cases">+ <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Aggiungi caso d\'uso')) : 'Aggiungi caso d\'uso'; ?></button>
                        </fieldset>

                        <fieldset class="orch-identity-fieldset">
                            <legend>5. <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('About strategico')) : 'About strategico'; ?></legend>
                            <p class="orch3-muted orch-identity-help"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('200-500 caratteri che sintetizzano chi sei e perché esisti.')) : '200-500 caratteri che sintetizzano chi sei e perché esisti.'; ?></p>

                            <!-- 3.35.79.1: Brand Voice About AI generation -->
                            <div class="orch-bv-about-gen-wrap" style="margin:0 0 10px;padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;">
                                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                    <strong style="font-size:12px;">🪄 Genera con AI:</strong>
                                    <label style="font-size:12px;display:inline-flex;align-items:center;gap:4px;">
                                        <select id="orch-bv-about-tier" style="font-size:12px;">
                                            <option value="standard">Standard — Veloce · 1 credito</option>
                                            <option value="premium_plus">Premium+ — Qualità superiore · 10 crediti</option>
                                        </select>
                                    </label>
                                    <button type="button" class="orch3-btn orch3-btn-primary" id="orch-bv-about-generate-btn" style="font-size:12px;">✨ Genera 3 varianti</button>
                                </div>
                                <p class="orch3-muted" style="font-size:11px;margin:6px 0 0;">3 varianti per tono (formal / conversational / authoritative). Crediti scalati dal wallet AEO Orchestra. Premium+ produce risultati di qualità superiore con voice match più preciso.</p>
                            </div>

                            <textarea id="orch-id-about" name="about_strategic" class="orch-identity-textarea" rows="5" maxlength="1500"></textarea>
                        </fieldset>

                    <!-- 3.35.79.1: Brand Voice About generation modal (rendered outside form) -->
                    <div id="orch-bv-about-modal" class="orch-modal-backdrop" style="display:none;">
                        <div class="orch-modal-window" style="max-width:840px;">
                            <div class="orch-modal-head">
                                <h3 style="margin:0;">🪄 3 varianti About strategico</h3>
                                <button type="button" class="orch-modal-close" id="orch-bv-about-close">×</button>
                            </div>
                            <div class="orch-modal-body">
                                <div id="orch-bv-about-meta" class="orch3-muted" style="font-size:11px;margin:0 0 10px;"></div>
                                <div id="orch-bv-about-variants" style="display:flex;flex-direction:column;gap:14px;"></div>
                            </div>
                            <div class="orch-modal-foot">
                                <button type="button" class="orch3-btn orch3-btn-ghost" id="orch-bv-about-cancel">Annulla</button>
                            </div>
                        </div>
                    </div>

                        <fieldset class="orch-identity-fieldset" data-orch-llms-only="featured">
                            <legend>6. <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Pagine in evidenza')) : 'Pagine in evidenza'; ?> <span class="orch-identity-legend-badge">/llms.txt</span></legend>
                            <p class="orch3-muted orch-identity-help"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Le 5-10 pagine principali del sito che vuoi che gli AI engine vedano per prime nel /llms.txt slim. Cerca per titolo. Le altre pagine restano nel /llms-full.txt e nel /sitemap.xml.')) : 'Le 5-10 pagine principali del sito che vuoi che gli AI engine vedano per prime nel /llms.txt slim. Cerca per titolo. Le altre pagine restano nel /llms-full.txt e nel /sitemap.xml.'; ?></p>
                            <div class="orch-featured-search-wrap">
                                <button type="button" id="orch-featured-auto-suggest" class="orch3-btn orch3-btn-ghost orch-featured-auto-btn" title="Auto-suggerisci 5 pagine basandosi sui ruoli classificati (homepage, about, contact, faq, quote)">
                                    ✨ <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Compila automaticamente')) : 'Compila automaticamente'; ?>
                                </button>
                                <input type="text" id="orch-featured-search" class="orch-identity-input" placeholder="<?php echo class_exists('SEO_AEO_T') ? esc_attr(SEO_AEO_T::t('Cerca pagine da mettere in evidenza…')) : 'Cerca pagine da mettere in evidenza…'; ?>" autocomplete="off" />
                                <div id="orch-featured-results" class="orch-featured-results" hidden></div>
                            </div>
                            <div id="orch-featured-chips" class="orch-featured-chips"></div>
                            <p class="orch3-muted" style="font-size:11px; margin:6px 0 0;"><span id="orch-featured-counter">0</span> / <span id="orch-featured-max">10</span> <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('pagine selezionate')) : 'pagine selezionate'; ?></p>
                        </fieldset>

                    </form>
                
                    <div class="orch-preview-box" data-orch-preview="llms">
                        <div class="orch-preview-header">
                            <h4 class="orch-preview-title">📡 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Anteprima live')) : 'Anteprima live'; ?></h4>
                            <div class="orch-preview-actions">
                                <button type="button" class="orch3-btn orch3-btn-ghost orch-preview-refresh" data-orch-preview-refresh="llms">
                                    🔄 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Aggiorna anteprima')) : 'Aggiorna anteprima'; ?>
                                </button>
                                <a href="<?php echo esc_url(home_url('/llms.txt')); ?>?_orch_preview_bust=<?php echo esc_attr(time()); ?>" target="_blank" rel="noopener" class="orch3-btn orch3-btn-ghost orch-preview-open">
                                    🔗 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Apri /llms.txt')) : 'Apri /llms.txt'; ?>
                                </a>
                            </div>
                        </div>
                        <div class="orch-preview-meta ">
                            <span class="orch-preview-loading orch3-muted">— <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Click \'Aggiorna anteprima\' per caricare')) : 'Click \'Aggiorna anteprima\' per caricare'; ?> —</span>
                        </div>
                        <pre class="orch-preview-content language-markdown" data-orch-preview-loaded="false"><code class="language-markdown"></code></pre>
                        
                    </div>
                    </div>
            </details>

            <details class="orch-output-accordion" data-orch-output="llms-full">
                <summary>
                    <span class="orch-acc-icon">📚</span>
                    <span class="orch-acc-title"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('/llms-full.txt')) : '/llms-full.txt'; ?></span>
                    <span class="orch-acc-status orch-acc-active"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Active')) : 'Active'; ?></span>
                </summary>
                <div class="orch-acc-body">
                    <!-- 3.35.68 status summary scaffold (llmsfull) -->
                    <div class="orch-acc-status-summary" id="orch-status-summary-llmsfull" data-orch-status-kind="llmsfull">
                        <h5 class="orch-acc-status-summary-title">📊 Riepilogo configurazione attiva</h5>
                        <ul class="orch-acc-status-summary-list">
                            <li><span class="orch-acc-status-icon">…</span><span class="orch3-muted">caricamento…</span></li>
                        </ul>
                    </div>
                    
                    <!-- 3.35.73: plain-language intro -->
                    <div class="orch-acc-intro" data-orch-llmsfull-tripletta="intro">
                        <p style="margin:8px 0 14px;font-size:13px;line-height:1.55;color:#334155;">
                            <strong>📚 llms-full.txt è la versione "piena" di llms.txt</strong>: include il CONTENUTO MARKDOWN completo di ogni pagina selezionata, non solo i link. Pensato per AI che vogliono fare deep ingestion (es. fine-tuning su contenuto azienda). Lo generiamo automaticamente dalle Featured pages selezionate in llms.txt — non serve toccare nulla.
                        </p>
                    </div>

                    <!-- 3.35.73 sub-panel 1: Auto-generation -->
                    <details class="orch-identity-fieldset orch-schema-suboption" id="orch-llmsfull-autogen" data-orch-llmsfull-tripletta="autogen">
                        <summary>
                            <span class="orch-head-subpanel-title"><strong>🔄 Auto-generation</strong></span>
                            <span class="orch-head-subpanel-subtitle">Quando rigenerare llms-full.txt: cambio pagina featured / featured list / cron.</span>
                        </summary>
                        <div class="orch-schema-suboption-body">
                            <div class="orch-explainer">
                                <h5>📖 Che cos'è</h5>
                                <p>Auto-generation rigenera llms-full.txt automaticamente quando: <strong>(a)</strong> cambi una pagina featured, <strong>(b)</strong> modifichi la lista featured pages in llms.txt, <strong>(c)</strong> ogni 7 giorni via cron. Disattivalo solo se vuoi controllo manuale.</p>
                            </div>
                            <div class="orch-current-config" data-orch-current-config="llmsfull-autogen">
                                <h5>✅ Configurazione attiva</h5>
                                <ul class="orch-current-config-list"><li class="orch3-muted">caricamento…</li></ul>
                            </div>
                            <details class="orch-advanced-options">
                                <summary>🛠 Personalizza CPT inclusi</summary>
                                <div class="orch-advanced-form">
                                    <p class="orch3-muted" style="font-size:11px;margin:0 0 8px;">Default: solo Articoli + Pagine. Aggiungi i CPT che vuoi siano leggibili dagli AI engine — diverso da sitemap.xml che invece include tutto per Google.</p>
                                    <div id="orch-llmsfull-pt-grid" class="orch-filt-pt-grid">
                                        <span class="orch3-muted" style="font-size:12px;">…</span>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </details>

                    <!-- 3.35.73 sub-panel 2: Content selection -->
                    <details class="orch-identity-fieldset orch-schema-suboption" id="orch-llmsfull-content" data-orch-llmsfull-tripletta="content">
                        <summary>
                            <span class="orch-head-subpanel-title"><strong>📦 Content selection / esclusioni</strong></span>
                            <span class="orch-head-subpanel-subtitle">Quali parti includere nel contenuto markdown emesso ad AI.</span>
                        </summary>
                        <div class="orch-schema-suboption-body">
                            <div class="orch-explainer">
                                <h5>📖 Che cos'è</h5>
                                <p>Content selection controlla quali parti vengono incluse in llms-full.txt: di default, solo il body markdown delle pagine featured. Puoi escludere pagine specifiche tramite pattern URL (es. <code>changelog</code>, <code>press-release-</code>, <code>archivio/</code>) — quelle escluse rimangono in sitemap.xml e Schema.org per Google ma non vengono date agli AI.</p>
                            </div>
                            <div class="orch-current-config" data-orch-current-config="llmsfull-content">
                                <h5>✅ Configurazione attiva</h5>
                                <ul class="orch-current-config-list"><li class="orch3-muted">caricamento…</li></ul>
                            </div>
                            <details class="orch-advanced-options" data-orch-llms-only="patterns">
                                <summary>🛠 Personalizza esclusioni</summary>
                                <div class="orch-advanced-form">
                                    <p class="orch3-muted orch-identity-help">Pattern aggiuntivi che escludono pagine SOLO dai due output llms.* (rimangono in sitemap.xml e schema.org). Una voce per riga, substring case-insensitive su URL O slug.</p>
                                    <textarea id="orch-llms-only-exclude" class="orch-identity-textarea" rows="4" placeholder="es.&#10;changelog&#10;press-release-&#10;archivio/"></textarea>
                                </div>
                            </details>
                        </div>
                    </details>
                    <p class="orch3-muted" style="font-size:12px; margin-top:14px;">
                        <strong><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('In arrivo Stage 2:')) : 'In arrivo Stage 2:'; ?></strong>
                        <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('toggle solo-link / link+content per CPT, cap configurabile, heading normalization avanzata.')) : 'toggle solo-link / link+content per CPT, cap configurabile, heading normalization avanzata.'; ?>
                    </p>
                
                    <div class="orch-preview-box" data-orch-preview="llms-full">
                        <div class="orch-preview-header">
                            <h4 class="orch-preview-title">📡 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Anteprima live')) : 'Anteprima live'; ?></h4>
                            <div class="orch-preview-actions">
                                <button type="button" class="orch3-btn orch3-btn-ghost orch-preview-refresh" data-orch-preview-refresh="llms-full">
                                    🔄 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Aggiorna anteprima')) : 'Aggiorna anteprima'; ?>
                                </button>
                                <a href="<?php echo esc_url(home_url('/llms-full.txt')); ?>?_orch_preview_bust=<?php echo esc_attr(time()); ?>" target="_blank" rel="noopener" class="orch3-btn orch3-btn-ghost orch-preview-open">
                                    🔗 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Apri /llms-full.txt')) : 'Apri /llms-full.txt'; ?>
                                </a>
                            </div>
                        </div>
                        <div class="orch-preview-meta ">
                            <span class="orch-preview-loading orch3-muted">— <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Click \'Aggiorna anteprima\' per caricare')) : 'Click \'Aggiorna anteprima\' per caricare'; ?> —</span>
                        </div>
                        <pre class="orch-preview-content language-markdown" data-orch-preview-loaded="false"><code class="language-markdown"></code></pre>
                        
                    </div>
                    </div>
            </details>

            <details class="orch-output-accordion" data-orch-output="page-roles">
                <summary>
                    <span class="orch-acc-icon">🗺️</span>
                    <span class="orch-acc-title"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Mappa del sito (ruoli pagine)')) : 'Mappa del sito (ruoli pagine)'; ?></span>
                    <span class="orch-acc-status orch-acc-active"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Active')) : 'Active'; ?></span>
                </summary>
                <div class="orch-acc-body">
                    <p class="orch3-muted" style="margin-top:4px;">
                        <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('AEO Orchestra classifica automaticamente il ruolo interno di ogni pagina (homepage, about, faq, ecc.). Questi ruoli alimentano: featured_pages auto-suggest, schema.org per page type, sitemap priority.')) : 'AEO Orchestra classifica automaticamente il ruolo interno di ogni pagina (homepage, about, faq, ecc.). Questi ruoli alimentano: featured_pages auto-suggest, schema.org per page type, sitemap priority.'; ?>
                    </p>

                    <div class="orch-page-roles-actions">
                        <button type="button" id="orch-roles-reclassify-heuristic" class="orch3-btn orch3-btn-ghost">
                            🔄 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Re-classifica heuristic')) : 'Re-classifica heuristic'; ?>
                        </button>
                        <span class="orch-page-roles-meta orch3-muted" id="orch-roles-meta">…</span>
                    </div>

                    <div class="orch-page-roles-filters">
                        <input type="text" id="orch-roles-search" class="orch-identity-input" placeholder="Cerca per titolo / slug…" />
                        <select id="orch-roles-filter-role" class="orch-identity-input" style="width:auto;">
                            <option value="">Tutti i ruoli</option>
                        </select>
                        <select id="orch-roles-filter-source" class="orch-identity-input" style="width:auto;">
                            <option value="">Tutte le fonti</option>
                            <option value="heuristic">Heuristic</option>
                            <option value="llm">LLM</option>
                            <option value="manual">Manual</option>
                        </select>
                    </div>

                    <div id="orch-roles-loading" class="orch3-muted" style="padding:14px 0;">
                        <span class="rv-spinner"></span> <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Caricamento mappa…')) : 'Caricamento mappa…'; ?>
                    </div>

                    <div id="orch-roles-table-wrap" style="display:none;">
                        <table class="orch-roles-table">
                            <thead>
                                <tr>
                                    <th><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Ruolo')) : 'Ruolo'; ?></th>
                                    <th><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Pagina')) : 'Pagina'; ?></th>
                                    <th><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Confidence')) : 'Confidence'; ?></th>
                                    <th><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Fonte')) : 'Fonte'; ?></th>
                                    <th><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Override')) : 'Override'; ?></th>
                                </tr>
                            </thead>
                            <tbody id="orch-roles-tbody"></tbody>
                        </table>
                        <p class="orch3-muted" style="font-size:11px; margin-top:6px;" id="orch-roles-count"></p>
                    </div>
                </div>
            </details>

                        <details class="orch-output-accordion" data-orch-output="schema">
                <summary>
                    <span class="orch-acc-icon">📊</span>
                    <span class="orch-acc-title"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Schema.org')) : 'Schema.org'; ?></span>
                    <span class="orch-acc-status orch-acc-active"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Smart Override')) : 'Smart Override'; ?></span>
                </summary>
                <div class="orch-acc-body">
                    <!-- 3.35.68 status summary scaffold (schema) -->
                    <div class="orch-acc-status-summary" id="orch-status-summary-schema" data-orch-status-kind="schema">
                        <h5 class="orch-acc-status-summary-title">📊 Riepilogo configurazione attiva</h5>
                        <ul class="orch-acc-status-summary-list">
                            <li><span class="orch-acc-status-icon">…</span><span class="orch3-muted">caricamento…</span></li>
                        </ul>
                    </div>
                    
                    <!-- 3.35.71: plain-language intro -->
                    <div class="orch-acc-intro" data-orch-schema-tripletta="intro">
                        <p style="margin:8px 0;font-size:13px;line-height:1.55;color:#334155;">
                            <strong>📊 Schema.org dice ai search engine cosa significa il contenuto delle tue pagine.</strong> Senza, Google e ChatGPT vedono solo testo. Con, capiscono che "questo è un prodotto", "questo è un articolo", "questo è un'azienda".
                            Lo abbiamo già configurato con i tipi più appropriati per ognuna delle pagine del tuo sito — non serve toccare nulla salvo casi speciali.
                        </p>
                    </div>

                    <fieldset class="orch-identity-fieldset">
                        <legend><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Detection ambiente')) : 'Detection ambiente'; ?></legend>
                        <div id="orch-schema-detection" class="orch-schema-detection">…</div>
                    </fieldset>

                    <!-- 3.35.71: Smart Override mode sub-panel with tripletta -->
                    <details class="orch-identity-fieldset orch-schema-suboption" id="orch-schema-mode-panel" data-orch-schema-tripletta="mode">
                        <summary>
                            <span class="orch-head-subpanel-title"><strong>⚙️ Smart Override mode</strong></span>
                            <span class="orch-head-subpanel-subtitle">Decide come Orchestra si comporta verso eventuali Schema esistenti emessi da altri plugin.</span>
                        </summary>
                        <div class="orch-schema-suboption-body">
                            <div class="orch-explainer">
                                <h5>📖 Che cos'è</h5>
                                <p>Smart Override decide come Orchestra interagisce con eventuali tag Schema.org già emessi da Yoast / RankMath / AIOSEO. <strong>Auto</strong> = rileva conflitti e decide automaticamente. <strong>Replace</strong> = sostituisce sempre. <strong>Augment</strong> = aggiunge solo i tipi mancanti. <strong>Off</strong> = lascia inalterato.</p>
                            </div>
                            <div class="orch-current-config" data-orch-current-config="schema-mode">
                                <h5>✅ Configurazione attiva</h5>
                                <ul class="orch-current-config-list"><li class="orch3-muted">caricamento…</li></ul>
                            </div>
                            <details class="orch-advanced-options">
                                <summary>🛠 Personalizza (4 modalità — testare sempre in staging)</summary>
                                <div class="orch-advanced-form">
                                    <fieldset class="orch-identity-fieldset" style="border:0;padding:0;">
                                        <legend class="screen-reader-text">Modalità</legend>
                                        <div class="orch-schema-modes">
                            <label class="orch-radio-row">
                                <input type="radio" name="orch-schema-mode" value="auto" />
                                <div>
                                    <strong><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Auto')) : 'Auto'; ?></strong>
                                    <span class="orch3-muted"> — <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('detection-based: usa la modalità raccomandata in base ai provider rilevati')) : 'detection-based: usa la modalità raccomandata in base ai provider rilevati'; ?></span>
                                </div>
                            </label>
                            <label class="orch-radio-row">
                                <input type="radio" name="orch-schema-mode" value="replace" />
                                <div>
                                    <strong><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Replace')) : 'Replace'; ?></strong>
                                    <span class="orch3-muted"> — <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('disattiva schema altri plugin via filter hooks, AEO Orchestra è il sole provider')) : 'disattiva schema altri plugin via filter hooks, AEO Orchestra è il sole provider'; ?></span>
                                </div>
                            </label>
                            <label class="orch-radio-row">
                                <input type="radio" name="orch-schema-mode" value="augment" />
                                <div>
                                    <strong><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Augment')) : 'Augment'; ?></strong>
                                    <span class="orch3-muted"> — <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('rileva tipi mancanti, aggiunge solo quelli (es. FAQPage se Yoast non lo fa)')) : 'rileva tipi mancanti, aggiunge solo quelli (es. FAQPage se Yoast non lo fa)'; ?></span>
                                </div>
                            </label>
                            <label class="orch-radio-row">
                                <input type="radio" name="orch-schema-mode" value="off" />
                                <div>
                                    <strong><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Off')) : 'Off'; ?></strong>
                                    <span class="orch3-muted"> — <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('AEO Orchestra schema disabilitato')) : 'AEO Orchestra schema disabilitato'; ?></span>
                                </div>
                            </label>
                                        </div>
                                    </fieldset>
                                </div>
                            </details>
                        </div>
                    </details>


                    <!-- A.1: Organization defaults (3.35.58) -->
                    <details class="orch-identity-fieldset orch-schema-suboption" id="orch-schema-org-defaults" data-orch-schema-tripletta="org">
                        <summary>
                            <span class="orch-head-subpanel-title"><strong>🏢 Organization defaults</strong></span>
                            <span class="orch-head-subpanel-subtitle">Card "Sai cos'è questa azienda" — alimentano il @graph di TUTTE le pagine.</span>
                        </summary>
                        <div class="orch-schema-suboption-body">
                            <div class="orch-explainer">
                                <h5>📖 Che cos'è</h5>
                                <p>Organization è la card "Sai cos'è questa azienda" che Google mostra a destra delle ricerche brand. Influenza i Knowledge Panel. Configura una volta, viene emesso ovunque sul sito (homepage + ogni pagina). Logo è il campo più importante: <strong>missing logo = no Knowledge Panel</strong>.</p>
                            </div>
                            <div class="orch-current-config" data-orch-current-config="schema-org">
                                <h5>✅ Configurazione attiva</h5>
                                <ul class="orch-current-config-list"><li class="orch3-muted">caricamento…</li></ul>
                            </div>
                            <details class="orch-advanced-options">
                                <summary>🛠 Personalizza Organization (logo / VAT / address / sameAs)</summary>
                                <div class="orch-advanced-form">
                            <div class="orch-image-picker-row" data-orch-img-target="orch-org-logo-url" data-orch-img-ratio="1.0">
                                <label class="orch-identity-label">Logo URL</label>
                                <p class="description orch3-muted" style="margin:0 0 6px;font-size:11px;">Logo dell\'azienda. Usato in Schema.org Organization e Knowledge Panel Google. Aspect ratio consigliato: <strong>quadrato 1:1</strong> o vicino.</p>
                                <div class="orch-image-input-group">
                                    <input type="text" id="orch-org-logo-url" class="orch-identity-input orch-image-input" placeholder="https://www.example.com/wp-content/uploads/logo.png" />
                                    <button type="button" class="button orch-media-picker-btn" data-orch-img-target="orch-org-logo-url" data-orch-img-title="Scegli logo Organization">📁 Scegli da Media Library</button>
                                    <button type="button" class="button orch-media-remove-btn" data-orch-img-target="orch-org-logo-url" style="display:none;">🗑 Rimuovi</button>
                                </div>
                                <div class="orch-image-preview" data-orch-img-preview="orch-org-logo-url"></div>
                                <div class="orch-image-validation" data-orch-img-validation="orch-org-logo-url"></div>
                            </div>

                            <div class="orch-grid-2">
                                <div>
                                    <label class="orch-identity-label">Legal name</label>
                                    <input type="text" id="orch-org-legal-name" class="orch-identity-input" placeholder="Solaris Films srl" />
                                </div>
                                <div>
                                    <label class="orch-identity-label">Founding date</label>
                                    <input type="date" id="orch-org-founding-date" class="orch-identity-input" />
                                </div>
                            </div>
                            <label class="orch-identity-label">P.IVA / VAT</label>
                            <input type="text" id="orch-org-vat" class="orch-identity-input" placeholder="IT01234567890" />

                            <h5 class="orch-schema-suboption-h5">Address</h5>
                            <div class="orch-grid-2">
                                <div>
                                    <label class="orch-identity-label">Via + civico</label>
                                    <input type="text" id="orch-org-addr-street" class="orch-identity-input" placeholder="Via Roma, 42" />
                                </div>
                                <div>
                                    <label class="orch-identity-label">CAP</label>
                                    <input type="text" id="orch-org-addr-cap" class="orch-identity-input" placeholder="00100" />
                                </div>
                            </div>
                            <div class="orch-grid-3">
                                <div>
                                    <label class="orch-identity-label">Città</label>
                                    <input type="text" id="orch-org-addr-locality" class="orch-identity-input" placeholder="Roma" />
                                </div>
                                <div>
                                    <label class="orch-identity-label">Provincia/Regione</label>
                                    <input type="text" id="orch-org-addr-region" class="orch-identity-input" placeholder="RM" />
                                </div>
                                <div>
                                    <label class="orch-identity-label">Paese (ISO 2)</label>
                                    <input type="text" id="orch-org-addr-country" class="orch-identity-input" maxlength="2" placeholder="IT" />
                                </div>
                            </div>

                            <h5 class="orch-schema-suboption-h5">ContactPoint</h5>
                            <div class="orch-grid-2">
                                <div>
                                    <label class="orch-identity-label">Telefono</label>
                                    <input type="text" id="orch-org-cp-tel" class="orch-identity-input" placeholder="+39 06 1234567" />
                                </div>
                                <div>
                                    <label class="orch-identity-label">Email</label>
                                    <input type="email" id="orch-org-cp-email" class="orch-identity-input" placeholder="info@example.com" />
                                </div>
                            </div>
                            <div class="orch-grid-3">
                                <div>
                                    <label class="orch-identity-label">Tipo contatto</label>
                                    <select id="orch-org-cp-type" class="orch-identity-input">
                                        <option value="customer support">customer support</option>
                                        <option value="sales">sales</option>
                                        <option value="technical support">technical support</option>
                                        <option value="billing support">billing support</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="orch-identity-label">Lingue (csv)</label>
                                    <input type="text" id="orch-org-cp-langs" class="orch-identity-input" placeholder="it, en" />
                                </div>
                                <div>
                                    <label class="orch-identity-label">Area servita (csv)</label>
                                    <input type="text" id="orch-org-cp-area" class="orch-identity-input" placeholder="Italia, Spagna" />
                                </div>
                            </div>

                            <h5 class="orch-schema-suboption-h5">Social profiles (sameAs)</h5>
                            <div id="orch-org-same-as-list" class="orch-org-same-as-list"></div>
                            <button type="button" id="orch-org-same-as-add" class="orch3-btn orch3-btn-ghost" style="font-size:12px;">+ Aggiungi profilo</button>
                                </div>
                            </details>
                        </div>
                    </details>

                    <!-- A.2: Schema type per CPT override (3.35.58) -->
                    <details class="orch-identity-fieldset orch-schema-suboption" id="orch-schema-cpt-overrides" data-orch-schema-tripletta="cpt">
                        <summary>
                            <span class="orch-head-subpanel-title"><strong>🎨 @types per role / CPT</strong></span>
                            <span class="orch-head-subpanel-subtitle">Per ogni Custom Post Type, scegli quale Schema.org @type emettere.</span>
                        </summary>
                        <div class="orch-schema-suboption-body">
                            <div class="orch-explainer">
                                <h5>📖 Che cos'è</h5>
                                <p>Per ognuno dei ruoli/CPT identificati nel sito, Orchestra emette uno Schema.org @type appropriato. Es. blog post → Article, prodotto → Product, FAQ → FAQPage. Queste pagine sono già classificate automaticamente — override solo se l'AI ha sbagliato la categorizzazione.</p>
                            </div>
                            <div class="orch-current-config" data-orch-current-config="schema-cpt">
                                <h5>✅ Configurazione attiva</h5>
                                <ul class="orch-current-config-list"><li class="orch3-muted">caricamento…</li></ul>
                            </div>
                            <details class="orch-advanced-options">
                                <summary>🛠 Override @type per CPT</summary>
                                <div class="orch-advanced-form">
                                    <p class="orch3-muted orch-identity-help">Lascia "Auto" per usare classificazione basata su page role.</p>
                                    <table class="orch-cpt-override-table">
                                <thead>
                                    <tr><th>Post Type</th><th>Override</th></tr>
                                </thead>
                                <tbody id="orch-cpt-override-tbody"></tbody>
                                    </table>
                                </div>
                            </details>
                        </div>
                    </details>

                    <!-- A.3: BreadcrumbList toggle (3.35.58) -->
                    <details class="orch-identity-fieldset orch-schema-suboption" id="orch-schema-breadcrumb" data-orch-schema-tripletta="bc">
                        <summary>
                            <span class="orch-head-subpanel-title"><strong>🍞 BreadcrumbList</strong></span>
                            <span class="orch-head-subpanel-subtitle">Il "percorso" della pagina (Home › Categoria › Pagina) emesso come schema.</span>
                        </summary>
                        <div class="orch-schema-suboption-body">
                            <div class="orch-explainer">
                                <h5>📖 Che cos'è</h5>
                                <p>I breadcrumb sono il "percorso" della pagina (Home › Categoria › Prodotto). Orchestra li emette automaticamente per ogni pagina non-home, derivandoli dalla struttura URL WP. Google li mostra nei risultati al posto della URL piena (più leggibili nei SERP).</p>
                            </div>
                            <div class="orch-current-config" data-orch-current-config="schema-bc">
                                <h5>✅ Configurazione attiva</h5>
                                <ul class="orch-current-config-list"><li class="orch3-muted">caricamento…</li></ul>
                            </div>
                            <details class="orch-advanced-options">
                                <summary>🛠 Personalizza emission BreadcrumbList</summary>
                                <div class="orch-advanced-form">
                            <label class="orch-toggle-row">
                                <input type="checkbox" id="orch-breadcrumb-enabled" />
                                <span class="orch-toggle-label">Genera BreadcrumbList su ogni pagina (gerarchia URL + parent pages)</span>
                            </label>
                            <label class="orch-identity-label">Separator visualizzato (Google SERP)</label>
                            <div class="orch-schema-modes">
                                <label class="orch-radio-row"><input type="radio" name="orch-breadcrumb-sep" value="auto" /> <span>Auto (Google decide)</span></label>
                                <label class="orch-radio-row"><input type="radio" name="orch-breadcrumb-sep" value="chevron" /> <span>"›" (chevron)</span></label>
                                <label class="orch-radio-row"><input type="radio" name="orch-breadcrumb-sep" value="slash" /> <span>"/" (slash)</span></label>
                                <label class="orch-radio-row"><input type="radio" name="orch-breadcrumb-sep" value="dot" /> <span>"·" (dot)</span></label>
                            </div>
                                </div>
                            </details>
                        </div>
                    </details>

                                        <p class="orch3-muted" style="font-size:12px; margin-top:14px;">
                        <strong><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('In arrivo Stage 2/3:')) : 'In arrivo Stage 2/3:'; ?></strong>
                        <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('organization defaults (logo, contactPoint, sameAs), schema type per CPT, BreadcrumbList toggle.')) : 'organization defaults (logo, contactPoint, sameAs), schema type per CPT, BreadcrumbList toggle.'; ?>
                    </p>
                
                    <div class="orch-preview-box" data-orch-preview="schema">
                        <div class="orch-preview-header">
                            <h4 class="orch-preview-title">📡 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Anteprima live')) : 'Anteprima live'; ?></h4>
                            <div class="orch-preview-actions">
                                <button type="button" class="orch3-btn orch3-btn-ghost orch-preview-refresh" data-orch-preview-refresh="schema">
                                    🔄 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Aggiorna anteprima')) : 'Aggiorna anteprima'; ?>
                                </button>
                                <a href="<?php echo esc_url(home_url('/')); ?>?_orch_preview_bust=<?php echo esc_attr(time()); ?>" target="_blank" rel="noopener" class="orch3-btn orch3-btn-ghost orch-preview-open">
                                    🔗 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Apri homepage')) : 'Apri homepage'; ?>
                                </a>
                            </div>
                        </div>
                        <div class="orch-preview-meta ">
                            <span class="orch-preview-loading orch3-muted">— <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Click \'Aggiorna anteprima\' per caricare')) : 'Click \'Aggiorna anteprima\' per caricare'; ?> —</span>
                        </div>
                        <pre class="orch-preview-content language-json" data-orch-preview-loaded="false"><code class="language-json"></code></pre>
                        <div class="orch-preview-detection"></div>
                    </div>
                    </div>
            </details>
        </div>

        <!-- ============================================================
             SECTION 3 — Bottoni globali (sempre visibili)
             ============================================================ -->
        <div class="orch3-card orch-native-card orch-section orch-section-actions">
            <div class="orch-identity-actions">
                <button type="button" id="orch-identity-save" class="orch3-btn orch3-btn-primary"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Salva profilo')) : 'Salva profilo'; ?></button>
                <button type="button" id="orch-identity-flush" class="orch3-btn orch3-btn-ghost"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Rigenera /llms.txt + sitemap.xml')) : 'Rigenera /llms.txt + sitemap.xml'; ?></button>
            </div>
        </div>



        <!-- ============================================================
             FOOTER BAR — single global toggle (respect noindex)
             ============================================================ -->
        <div class="orch-footer-bar" data-orch-section="footer-bar">
            <label class="orch-toggle-row">
                <input type="checkbox" id="orch-respect-noindex" />
                <span class="orch-toggle-label">
                    <strong><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Rispetta noindex')) : 'Rispetta noindex'; ?></strong>
                    <span class="orch3-muted"> — <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('escludi automaticamente le pagine con meta robots noindex (Yoast / RankMath / AIOSEO / native) da TUTTI i 5 native output. Setting globale.')) : 'escludi automaticamente le pagine con meta robots noindex (Yoast / RankMath / AIOSEO / native) da TUTTI i 5 native output. Setting globale.'; ?></span>
                </span>
            </label>
        </div>

    </div><!-- end orch-native-tab-panel content -->

    <div class="orch-native-tab-panel" data-orch-tab="verify" role="tabpanel" hidden>
        <div class="orch3-card orch-native-card">
            <div class="orch-native-head">
                <div>
                    <h2 class="orch3-h2"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Verifica & lingue')) : 'Verifica & lingue'; ?></h2>
                    <p class="orch3-muted" style="margin:4px 0 0; font-size:13px;">
                        <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Verifica AI live e gestione llms.txt multilingua. In arrivo nelle prossime release.')) : 'Verifica AI live e gestione llms.txt multilingua. In arrivo nelle prossime release.'; ?>
                    </p>
                </div>
            </div>
            <div class="orch-identity-stub">
                <div class="orch-identity-stub-icon">🚧</div>
                <h3><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('In arrivo')) : 'In arrivo'; ?></h3>
                <p class="orch3-muted">
                    <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Stage 3 (verifica AI live con browsing su /llms.txt) e Stage 4 (llms.txt multilingua per IT/EN/ES/FR/DE) saranno disponibili nelle prossime release. Stage 1 — il salvataggio del profilo identità — è già attivo nella tab "Configurazione contenuti".')) : 'Stage 3 (verifica AI live con browsing su /llms.txt) e Stage 4 (llms.txt multilingua per IT/EN/ES/FR/DE) saranno disponibili nelle prossime release. Stage 1 — il salvataggio del profilo identità — è già attivo nella tab "Configurazione contenuti".'; ?>
                </p>
            </div>
        </div>
    </div><!-- end orch-native-tab-panel verify -->

    <div class="orch-native-footer">
        <strong><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('SEO Output Nativo')) : 'SEO Output Nativo'; ?></strong> · <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Versione')) : 'Versione'; ?> <?php echo esc_html(defined('SEO_AEO_VERSION') ? SEO_AEO_VERSION : '?'); ?> ·
        <a href="https://aeo-orchestra.com/changelog" target="_blank" rel="noopener"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Changelog')) : 'Changelog'; ?></a> ·
        <a href="https://aeo-orchestra.com/contact" target="_blank" rel="noopener"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Assistenza')) : 'Assistenza'; ?></a>
    </div>


    <!-- ============================================================
         Stage 2.5 modals (Page Picker + Pro Onboarding)
         ============================================================ -->
    <div class="orch-modal-backdrop" id="orch-stage25-page-picker" hidden>
        <div class="orch-modal-window orch-modal-window-large">
            <div class="orch-modal-head">
                <h3>✨ <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Scegli fino a 10 pagine più importanti')) : 'Scegli fino a 10 pagine più importanti'; ?></h3>
                <button type="button" class="orch-modal-close" data-orch-modal-close="picker">×</button>
            </div>
            <div class="orch-modal-body">
                <p class="orch3-muted" style="font-size:13px;">
                    <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Abbiamo pre-selezionato le 10 pagine che SEMBRANO più importanti basandoci sulla struttura del tuo sito. Modifica liberamente la selezione per riflettere meglio la tua priorità.')) : 'Abbiamo pre-selezionato le 10 pagine che SEMBRANO più importanti basandoci sulla struttura del tuo sito. Modifica liberamente la selezione per riflettere meglio la tua priorità.'; ?>
                </p>
                <div class="orch-picker-search-wrap">
                    <input type="text" id="orch-picker-search" class="orch-identity-input" placeholder="🔍 Cerca pagine per aggiungerne…" autocomplete="off" />
                    <div id="orch-picker-search-results" class="orch-featured-results" hidden></div>
                </div>
                <div id="orch-picker-list" class="orch-picker-list"></div>
                <p class="orch-picker-counter orch3-muted">
                    <span id="orch-picker-count">0</span> / 10 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('pagine selezionate')) : 'pagine selezionate'; ?>
                    (<?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('min 3 per attivare scan')) : 'min 3 per attivare scan'; ?>)
                </p>
            </div>
            <div class="orch-modal-foot">
                <button type="button" class="orch3-btn orch3-btn-ghost" data-orch-modal-close="picker"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Annulla')) : 'Annulla'; ?></button>
                <button type="button" class="orch3-btn orch3-btn-primary" id="orch-picker-confirm" disabled><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Scansiona queste pagine')) : 'Scansiona queste pagine'; ?></button>
            </div>
        </div>
    </div>

    <div class="orch-modal-backdrop" id="orch-scan-diff-modal" hidden>
        <div class="orch-modal-window orch-modal-window-large">
            <div class="orch-modal-head">
                <h3>📋 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Risultati scan disponibili — conferma sostituzioni')) : 'Risultati scan disponibili — conferma sostituzioni'; ?></h3>
                <button type="button" class="orch-modal-close" data-orch-diff-cancel>×</button>
            </div>
            <div class="orch-modal-body">
                <p class="orch3-muted" style="font-size:13px;">
                    <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Lo scan ha trovato differenze con i tuoi valori attuali. Per ognuna scegli cosa fare:')) : 'Lo scan ha trovato differenze con i tuoi valori attuali. Per ognuna scegli cosa fare:'; ?>
                </p>
                <div class="orch-scan-diff-body"></div>
            </div>
            <div class="orch-modal-foot">
                <button type="button" class="orch3-btn orch3-btn-ghost" data-orch-diff-cancel><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Annulla scan')) : 'Annulla scan'; ?></button>
                <button type="button" class="orch3-btn orch3-btn-primary" data-orch-diff-confirm><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Conferma scelte')) : 'Conferma scelte'; ?></button>
            </div>
        </div>
    </div>

        <div class="orch-modal-backdrop" id="orch-stage25-pro-onboarding" hidden>
        <div class="orch-modal-window">
            <div class="orch-modal-head">
                <h3>🎁 <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Bonus benvenuto Pro')) : 'Bonus benvenuto Pro'; ?></h3>
                <button type="button" class="orch-modal-close" data-orch-modal-close="onboarding">×</button>
            </div>
            <div class="orch-modal-body">
                <p>
                    <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Per impostare il tuo profilo AEO al meglio, scansioniamo tutte le pagine pubbliche del tuo sito. È gratis e una tantum, incluso nel piano Pro.')) : 'Per impostare il tuo profilo AEO al meglio, scansioniamo tutte le pagine pubbliche del tuo sito. È gratis e una tantum, incluso nel piano Pro.'; ?>
                </p>
                <div class="orch-onboarding-grid">
                    <div class="orch-onboarding-col orch-onboarding-do">
                        <strong>✓ <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Cosa facciamo')) : 'Cosa facciamo'; ?></strong>
                        <ul>
                            <li>Identifichiamo industry, differenzianti, casi d'uso</li>
                            <li>Classifichiamo automaticamente i ruoli delle pagine</li>
                            <li>Pre-popoliamo l'identity profile con candidati AI</li>
                        </ul>
                    </div>
                    <div class="orch-onboarding-col orch-onboarding-dont">
                        <strong>✗ <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Cosa NON facciamo')) : 'Cosa NON facciamo'; ?></strong>
                        <ul>
                            <li>Non scarichiamo immagini né media</li>
                            <li>Non leggiamo aree riservate o login-protected</li>
                            <li>Non condividiamo dati con terze parti</li>
                        </ul>
                    </div>
                </div>
                <p class="orch3-muted" style="font-size:12px;">
                    <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Tempo stimato: ~30 secondi · Costo: 0 (incluso Pro)')) : 'Tempo stimato: ~30 secondi · Costo: 0 (incluso Pro)'; ?>
                </p>
            </div>
            <div class="orch-modal-foot">
                <button type="button" class="orch3-btn orch3-btn-ghost" id="orch-onboarding-skip"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Imposterò manualmente')) : 'Imposterò manualmente'; ?></button>
                <button type="button" class="orch3-btn orch3-btn-primary" id="orch-onboarding-start"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Avvia scansione completa')) : 'Avvia scansione completa'; ?></button>
            </div>
        </div>
    </div>

</div>

<?php ob_start(); ?>
.orch-native-page { max-width: 1100px; }
.orch-native-hero { display: flex; gap: 22px; align-items: center; padding: 28px 30px; margin: 16px 0 24px; background: linear-gradient(135deg, #0A0E27 0%, #0055FF 50%, #00E5FF 100%); border-radius: 12px; color: #ffffff; box-shadow: 0 4px 24px rgba(0,85,255,0.18); }
.orch-native-hero-icon { width: 72px; height: 72px; flex-shrink: 0; background: rgba(255,255,255,0.18); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 38px; }
.orch-native-hero-tagline { font-size: 11px; letter-spacing: 2px; text-transform: uppercase; opacity: 0.85; }
.orch-native-hero-title { color: #fff; font-size: 28px; font-weight: 700; margin: 4px 0 10px; line-height: 1.1; }
.orch-native-hero-sub { font-size: 14px; line-height: 1.55; margin: 0; opacity: 0.95; max-width: 720px; }
.orch-native-quickstats { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 24px; }
@media (max-width: 1100px) { .orch-native-quickstats { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 700px)  { .orch-native-quickstats { grid-template-columns: repeat(2, 1fr); } }
.orch-native-quickstat { display: block; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px 12px; text-align: center; transition: all 0.2s; }
.orch-native-quickstat.enabled { border-color: #10b981; background: linear-gradient(180deg, #f0fdf4, #ffffff); }
.orch-native-quickstat.disabled { opacity: 0.7; }
.orch-native-quickstat-icon { font-size: 28px; margin-bottom: 4px; }
.orch-native-quickstat-label { font-size: 13px; font-weight: 600; color: #0a0e27; }
.orch-native-quickstat-desc { font-size: 11px; color: #64748b; margin-top: 4px; line-height: 1.4; min-height: 28px; }
.orch-native-quickstat-status { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 6px; font-weight: 600; }
.orch-native-quickstat.enabled .orch-native-quickstat-status { color: #10b981; }
.orch-native-quickstat.disabled .orch-native-quickstat-status { color: #94a3b8; }
.orch-native-warn-banner { padding: 14px 18px; background: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 8px; color: #78350f; font-size: 13px; line-height: 1.6; margin-bottom: 22px; }
.orch-native-footer { margin-top: 30px; padding: 14px; text-align: center; color: #64748b; font-size: 12px; border-top: 1px solid #e2e8f0; }
.orch-native-footer a { color: #0055ff; text-decoration: none; }
.orch-native-footer a:hover { text-decoration: underline; }

.orchestra-v3 { font-family: -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif; }
.orchestra-v3 .orch3-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px 22px; margin-bottom: 16px; }
.orchestra-v3 .orch3-h2 { margin: 0 0 8px; font-size: 18px; color: #0a0e27; font-weight: 600; }
.orchestra-v3 .orch3-muted { color: #64748b; }
.orchestra-v3 .orch3-btn { display: inline-block; padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; border: 1px solid transparent; transition: all 0.15s; text-decoration: none; }
.orchestra-v3 .orch3-btn-primary { background: #0055FF; color: #ffffff; border-color: #0055FF; }
.orchestra-v3 .orch3-btn-primary:hover { background: #003fcc; }
.orchestra-v3 .orch3-btn-ghost { background: transparent; color: #475569; border-color: #e2e8f0; }
.orchestra-v3 .orch3-btn-ghost:hover { background: #f8fafc; }
.orchestra-v3 .orch3-btn-sm { padding: 5px 10px; font-size: 12px; }

.orch-modal-backdrop { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(10, 14, 39, 0.6); z-index: 100000; display: flex; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(2px); }
.orch-modal-window { background: #ffffff; border-radius: 10px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); width: 100%; max-width: 540px; max-height: 90vh; overflow: auto; font-family: inherit; }
.orch-modal-head { padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
.orch-modal-head h3 { margin: 0; font-size: 16px; color: #0a0e27; }
.orch-modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; line-height: 1; padding: 0; width: 28px; height: 28px; }
.orch-modal-close:hover { color: #0a0e27; }
.orch-modal-body { padding: 20px; font-size: 13px; color: #334155; line-height: 1.55; }
.orch-modal-body code { background: #f1f5f9; border: 1px solid #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 12px; color: #0a0e27; }
.orch-modal-foot { padding: 14px 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 10px; }
.orch-cannibal-error { padding: 8px 12px; background: #fee2e2; border-left: 3px solid #ef4444; color: #991b1b; border-radius: 4px; font-size: 12px; }
.orch-cannibal-fix-banner { margin: -4px -4px 12px -4px; padding: 10px 12px; background: linear-gradient(90deg, #ecfeff 0%, #f0f9ff 100%); border: 1px solid #67e8f9; border-radius: 6px; font-size: 12px; color: #155e75; line-height: 1.55; display: flex; gap: 10px; align-items: flex-start; }
.rv-spinner { display: inline-block; width: 14px; height: 14px; border: 2px solid #e2e8f0; border-top-color: #0055FF; border-radius: 50%; animation: rv-spin 0.8s linear infinite; vertical-align: middle; }
@keyframes rv-spin { to { transform: rotate(360deg); } }

/* CSS estratto: Native + Redirect */
/* 3.12.0 — Native SEO Output card */
.orchestra-v3 .orch-native-card { margin-top: 14px; border-left: 3px solid #0055FF; }
.orchestra-v3 .orch-native-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
}
.orchestra-v3 .orch-native-head .orch3-h2 { margin: 0; display: inline-flex; align-items: center; gap: 8px; }
.orchestra-v3 .orch-native-beta-badge {
    background: linear-gradient(90deg, #0055FF, #00E5FF);
    color: white;
    font-size: 10px;
    padding: 2px 8px;
    border-radius: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}
.orchestra-v3 .orch-native-toggle-card { flex-shrink: 0; }
.orchestra-v3 .orch-native-body { margin-top: 14px; }

.orchestra-v3 .orch-native-warn,
.orchestra-v3 .orch-native-active,
.orchestra-v3 .orch-native-disabled {
    padding: 14px 16px;
    border-radius: 8px;
    line-height: 1.6;
    font-size: 13px;
    margin-bottom: 14px;
}
.orchestra-v3 .orch-native-warn { background: #fffbeb; border-left: 3px solid #f59e0b; color: #78350f; }
.orchestra-v3 .orch-native-warn-head { font-weight: 600; margin-bottom: 6px; font-size: 14px; color: #92400e; }
.orchestra-v3 .orch-native-active { background: #d1fae5; border-left: 3px solid #10b981; color: #065f46; }
.orchestra-v3 .orch-native-active-head { font-weight: 600; margin-bottom: 6px; font-size: 14px; color: #047857; }
.orchestra-v3 .orch-native-disabled { background: #f1f5f9; border-left: 3px solid #94a3b8; color: #475569; }
.orchestra-v3 .orch-native-disabled-head { font-weight: 600; margin-bottom: 6px; font-size: 14px; color: #334155; }

.orchestra-v3 .orch-native-toggle-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 10px;
}
.orchestra-v3 .orch-native-override-row {
    background: #fffbeb;
    border-color: #fde68a;
}
.orchestra-v3 .orch-native-toggle-label { font-size: 13px; color: #334155; line-height: 1.5; }
.orchestra-v3 .orch-native-switch {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 26px;
    flex-shrink: 0;
}
.orchestra-v3 .orch-native-switch input { opacity: 0; width: 0; height: 0; }
.orchestra-v3 .orch-native-switch-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background: #cbd5e1;
    transition: 0.3s;
    border-radius: 26px;
}
.orchestra-v3 .orch-native-switch-slider::before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background: white;
    transition: 0.3s;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.orchestra-v3 .orch-native-switch input:checked + .orch-native-switch-slider { background: linear-gradient(90deg, #0055FF, #00E5FF); }
.orchestra-v3 .orch-native-switch input:checked + .orch-native-switch-slider::before { transform: translateX(22px); }
.orchestra-v3 .orch-native-switch input:disabled + .orch-native-switch-slider { opacity: 0.5; cursor: not-allowed; }

.orchestra-v3 .orch-native-features {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 13px;
}
.orchestra-v3 .orch-native-features-title { font-weight: 600; color: #0a0e27; margin-bottom: 8px; }
.orchestra-v3 .orch-native-features ul { margin: 0; padding-left: 18px; line-height: 1.7; color: #334155; }
.orchestra-v3 .orch-native-features code {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    padding: 1px 5px;
    border-radius: 3px;
    font-size: 11px;
    color: #0a0e27;
}

/* 3.15.0 — Redirect Manager */
.orchestra-v3 .orch-redirect-controls {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    flex-wrap: wrap;
    align-items: center;
}
.orchestra-v3 .orch-redirect-search-input {
    flex: 1;
    min-width: 200px;
    padding: 6px 10px;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    font-size: 13px;
}
.orchestra-v3 .orch-redirect-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 6px;
    font-size: 12px;
}
.orchestra-v3 .orch-redirect-table th {
    background: #f8fafc;
    padding: 8px 10px;
    text-align: left;
    border-bottom: 2px solid #e2e8f0;
    font-weight: 600;
    color: #0a0e27;
}
.orchestra-v3 .orch-redirect-table td {
    padding: 8px 10px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.orchestra-v3 .orch-redirect-table tbody tr:hover { background: #fafbfc; }
.orchestra-v3 .orch-redirect-code {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    color: #0a0e27;
    max-width: 280px;
    display: inline-block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    vertical-align: middle;
}
.orchestra-v3 .orch-redirect-badge {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.orchestra-v3 .orch-redirect-badge-active   { background: #d1fae5; color: #047857; }
.orchestra-v3 .orch-redirect-badge-inactive { background: #fee2e2; color: #b91c1c; }
.orchestra-v3 .orch-redirect-badge-regex    { background: #ede9fe; color: #6d28d9; }
.orchestra-v3 .orch-redirect-field {
    display: block;
    margin-bottom: 12px;
    font-size: 13px;
    color: #334155;
}
.orchestra-v3 .orch-redirect-field > span {
    display: block;
    font-weight: 600;
    margin-bottom: 4px;
    color: #0a0e27;
}
.orchestra-v3 .orch-redirect-field small {
    display: block;
    margin-top: 4px;
    font-size: 11px;
    line-height: 1.5;
}
.orchestra-v3 .orch-redirect-field small code {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 1px 4px;
    border-radius: 3px;
    font-size: 10px;
}
.orchestra-v3 .orch-redirect-input {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    font-size: 13px;
    box-sizing: border-box;
    font-family: inherit;
}
.orchestra-v3 .orch-redirect-checkbox-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 8px 0;
    font-size: 13px;
    color: #334155;
}
.orchestra-v3 .orch-redirect-checkbox-row code {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 1px 4px;
    border-radius: 3px;
    font-size: 11px;
}




/* ═══ Onboarding 2.0 — Tabs + Identity form ═══ */
.orch-native-tabs { display: flex; gap: 4px; margin: 0 0 18px; padding: 6px; background: #f1f5f9; border-radius: 12px; }
.orch-native-tab { flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px; background: transparent; border: 0; border-radius: 8px; font-size: 14px; font-weight: 500; color: #475569; cursor: pointer; transition: background .15s, color .15s, box-shadow .15s; }
.orch-native-tab:hover { background: rgba(255,255,255,0.7); color: #0f172a; }
.orch-native-tab.is-active { background: #ffffff; color: #0f172a; font-weight: 600; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
.orch-native-tab-icon { font-size: 18px; line-height: 1; }
.orch-native-tab-label { white-space: nowrap; }
@media (max-width: 700px) { .orch-native-tab-label { display: none; } }
.orch-native-tab-panel[hidden] { display: none; }

.orch-identity-form { padding: 8px 0 4px; }
.orch-identity-fieldset { border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px 20px 14px; margin: 0 0 18px; background: #fafbfc; }
.orch-identity-fieldset legend { padding: 0 8px; font-size: 14px; font-weight: 600; color: #0f172a; }
.orch-identity-help { margin: -4px 0 12px !important; font-size: 12px !important; color: #64748b; }
.orch-identity-label { display: block; margin: 10px 0 4px; font-size: 12px; font-weight: 500; color: #334155; text-transform: uppercase; letter-spacing: 0.4px; }
.orch-identity-input, .orch-identity-textarea { width: 100%; box-sizing: border-box; padding: 9px 12px; font-size: 14px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; }
.orch-identity-input:focus, .orch-identity-textarea:focus { outline: none; border-color: #0055FF; box-shadow: 0 0 0 3px rgba(0,85,255,0.12); }
.orch-identity-textarea { resize: vertical; min-height: 60px; font-family: inherit; }
.orch-identity-list { display: flex; flex-direction: column; gap: 8px; margin: 0 0 10px; }
.orch-identity-list-item { display: flex; gap: 8px; align-items: flex-start; padding: 10px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; }
.orch-identity-list-item-fields { flex: 1; display: flex; flex-direction: column; gap: 6px; }
.orch-identity-list-item-fields input, .orch-identity-list-item-fields textarea { width: 100%; box-sizing: border-box; padding: 7px 10px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 5px; }
.orch-identity-list-item-fields textarea { resize: vertical; min-height: 38px; font-family: inherit; }
.orch-identity-list-item-remove { width: 28px; height: 28px; padding: 0; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 6px; cursor: pointer; flex-shrink: 0; font-size: 16px; line-height: 1; }
.orch-identity-list-item-remove:hover { background: #fee2e2; }
.orch-identity-add { font-size: 13px; }
.orch-identity-actions { display: flex; gap: 10px; flex-wrap: wrap; padding: 14px 0 4px; border-top: 1px solid #e2e8f0; margin-top: 10px; }
.orch-identity-status-bar { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin: 0 0 14px; display: none; }
.orch-identity-status-bar.is-success { display: block; background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
.orch-identity-status-bar.is-error { display: block; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.orch-identity-status-bar.is-loading { display: block; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
.orch-identity-preview-wrap { margin-top: 18px; }
.orch-identity-preview-out { background: #0f172a; color: #e2e8f0; border-radius: 8px; padding: 16px 18px; font-family: ui-monospace, 'SFMono-Regular', Menlo, monospace; font-size: 12px; line-height: 1.55; white-space: pre-wrap; word-break: break-word; max-height: 420px; overflow: auto; }
.orch-identity-stub { padding: 36px 24px; text-align: center; }
.orch-identity-stub-icon { font-size: 42px; margin-bottom: 12px; }
.orch-identity-stub h3 { margin: 0 0 8px; font-size: 18px; color: #0f172a; }


.orch-filt-pt-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 8px; }
.orch-filt-pt-grid label { display: flex; align-items: center; gap: 8px; padding: 8px 10px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; cursor: pointer; transition: border-color .12s, background .12s; }
.orch-filt-pt-grid label:hover { background: #f8fafc; border-color: #cbd5e1; }
.orch-filt-pt-grid label input[type=checkbox] { margin: 0; }
.orch-filt-pt-grid label .orch-filt-pt-slug { color: #94a3b8; font-size: 11px; font-family: ui-monospace, Menlo, monospace; margin-left: auto; }



/* ═══ Stage 1.5 (3.35.49) — sections + accordion + featured pages autocomplete ═══ */
.orch-section { margin-bottom: 16px; }
.orch-section-icon { display: inline-block; margin-right: 6px; }
.orch-section-actions { padding: 14px 20px; }
.orch-section-actions .orch-identity-actions { padding: 0; border-top: 0; margin: 0; }
.orch-toggle-row { display: flex; align-items: flex-start; gap: 10px; padding: 8px 0; cursor: pointer; }
.orch-toggle-row input[type=checkbox] { margin-top: 3px; flex-shrink: 0; }
.orch-toggle-label { font-size: 13px; color: #334155; line-height: 1.5; }

.orch-output-accordion { border: 1px solid #e2e8f0; border-radius: 8px; margin: 8px 0; background: #ffffff; overflow: hidden; }
.orch-output-accordion[open] { border-color: #0055FF; box-shadow: 0 1px 3px rgba(0,85,255,0.05); }
.orch-output-accordion summary { display: flex; align-items: center; gap: 12px; padding: 14px 18px; cursor: pointer; font-size: 14px; user-select: none; list-style: none; transition: background .12s; }
.orch-output-accordion summary::-webkit-details-marker { display: none; }
.orch-output-accordion summary:hover { background: #f8fafc; }
.orch-output-accordion summary::before { content: '▶'; font-size: 10px; color: #94a3b8; transition: transform .15s; flex-shrink: 0; }
.orch-output-accordion[open] summary::before { transform: rotate(90deg); }
.orch-acc-icon { font-size: 18px; line-height: 1; flex-shrink: 0; }
.orch-acc-title { font-weight: 600; color: #0f172a; flex: 1; }
.orch-acc-status { font-size: 11px; padding: 3px 8px; border-radius: 10px; text-transform: uppercase; letter-spacing: 0.4px; font-weight: 600; }
.orch-acc-status.orch-acc-stub { background: #f1f5f9; color: #64748b; }
.orch-acc-status.orch-acc-active { background: #dcfce7; color: #166534; }
.orch-acc-body { padding: 4px 22px 18px; border-top: 1px solid #f1f5f9; }
.orch-acc-stub-list { margin: 8px 0 0 18px; padding: 0; font-size: 13px; color: #475569; }
.orch-acc-stub-list li { margin: 4px 0; }

.orch-identity-legend-badge { display: inline-block; margin-left: 6px; padding: 2px 7px; background: #eff6ff; color: #1e40af; font-size: 10px; font-weight: 600; border-radius: 8px; vertical-align: middle; }

.orch-featured-search-wrap { position: relative; margin-bottom: 8px; }
.orch-featured-results { position: absolute; top: 100%; left: 0; right: 0; max-height: 280px; overflow-y: auto; background: #ffffff; border: 1px solid #cbd5e1; border-top: 0; border-radius: 0 0 6px 6px; box-shadow: 0 6px 18px rgba(0,0,0,0.08); z-index: 10; }
.orch-featured-result-item { display: flex; align-items: center; gap: 10px; padding: 9px 12px; cursor: pointer; border-bottom: 1px solid #f1f5f9; }
.orch-featured-result-item:hover, .orch-featured-result-item.is-active { background: #eff6ff; }
.orch-featured-result-item:last-child { border-bottom: 0; }
.orch-featured-result-title { flex: 1; font-size: 13px; color: #0f172a; font-weight: 500; }
.orch-featured-result-type { font-size: 10px; padding: 2px 6px; background: #f1f5f9; color: #475569; border-radius: 4px; font-family: ui-monospace, monospace; text-transform: uppercase; letter-spacing: 0.3px; }
.orch-featured-result-empty { padding: 14px; text-align: center; font-size: 12px; color: #94a3b8; }
.orch-featured-chips { display: flex; flex-wrap: wrap; gap: 6px; min-height: 0; }
.orch-featured-chip { display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; border-radius: 14px; font-size: 12px; }
.orch-featured-chip-remove { background: transparent; border: 0; color: #1e40af; font-size: 16px; cursor: pointer; padding: 0; line-height: 1; opacity: 0.6; }
.orch-featured-chip-remove:hover { opacity: 1; color: #b91c1c; }



/* ═══ Stage 1.5 Addendum 2 (3.35.51) — footer-bar + per-output blocks ═══ */
.orch-footer-bar { position: sticky; bottom: 0; margin: 18px 0 10px; padding: 14px 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 -2px 8px rgba(0,0,0,0.04); z-index: 1; }
.orch-footer-bar .orch-toggle-row { padding: 0; cursor: pointer; }
.orch-radio-row { display: flex; align-items: flex-start; gap: 10px; padding: 8px 6px; cursor: pointer; }
.orch-radio-row input[type=radio] { margin-top: 4px; flex-shrink: 0; }
.orch-radio-row strong { color: #0f172a; }
.orch-schema-detection { padding: 10px 0; }
.orch-schema-detection-line { padding: 8px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; margin: 6px 0; font-size: 13px; color: #334155; }
.orch-schema-detection-empty { padding: 10px 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; color: #166534; font-size: 13px; }
.orch-schema-detection-types { display: inline-flex; flex-wrap: wrap; gap: 4px; margin-left: 6px; }
.orch-schema-detection-type { padding: 2px 7px; background: #e0e7ff; color: #3730a3; border-radius: 10px; font-size: 11px; font-family: ui-monospace, monospace; }
.orch-schema-recommendation { padding: 10px 12px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; color: #1e40af; font-size: 13px; margin-top: 10px; }
.orch-schema-recommendation strong { color: #1e3a8a; }



/* ═══ Stage 1.5 final (3.35.53) — Preview boxes ═══ */
.orch-preview-box { margin-top: 20px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; overflow: hidden; }
.orch-preview-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 14px; background: #ffffff; border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; }
.orch-preview-title { margin: 0; font-size: 13px; font-weight: 600; color: #0f172a; }
.orch-preview-actions { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
.orch-preview-actions .orch3-btn { font-size: 12px; padding: 5px 10px; }
.orch-preview-meta { display: flex; gap: 14px; padding: 8px 14px; font-size: 11px; color: #64748b; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; }
.orch-preview-meta strong { color: #0f172a; }
.orch-preview-meta code { padding: 1px 5px; background: #ffffff; border-radius: 3px; border: 1px solid #e2e8f0; font-size: 11px; }
.orch-preview-content { margin: 0; padding: 14px 16px; max-height: 400px; overflow: auto; font-size: 12px; line-height: 1.55; background: #1e293b; color: #e2e8f0; white-space: pre-wrap; word-break: break-word; }
.orch-preview-content code { background: transparent !important; padding: 0; font-family: ui-monospace, 'SFMono-Regular', Menlo, monospace; }
/* Override Prism tomorrow when loaded — keep header padding/margins clean */
.orch-preview-content[class*="language-"] { background: #2d2d2d !important; }
.orch-preview-detection { padding: 10px 14px; background: #ffffff; border-top: 1px solid #e2e8f0; font-size: 12px; color: #334155; }
.orch-preview-detection-mode-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; margin-left: 6px; }
.orch-preview-detection-mode-badge.is-replace { background: #dcfce7; color: #166534; }
.orch-preview-detection-mode-badge.is-augment { background: #dbeafe; color: #1e40af; }
.orch-preview-detection-mode-badge.is-off { background: #fee2e2; color: #991b1b; }
.orch-preview-detection-mode-badge.is-auto { background: #f1f5f9; color: #475569; }
.orch-preview-detection-providers { margin: 6px 0 0; font-family: ui-monospace, monospace; font-size: 11px; color: #475569; }



/* ═══ Stage 2 (3.35.54) — Scan banner + suggested badge + brand-country coach ═══ */
.orch-scan-banner { display: flex; align-items: flex-start; gap: 14px; margin: 0 0 18px; padding: 14px 18px; background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%); border: 1px solid #bfdbfe; border-left: 4px solid #0055FF; border-radius: 8px; }
.orch-scan-banner-icon { font-size: 26px; line-height: 1; flex-shrink: 0; }
.orch-scan-banner-body { flex: 1; font-size: 13px; color: #0f172a; line-height: 1.5; }
.orch-scan-actions { flex-shrink: 0; }
.orch-scan-status { margin-top: 8px; font-size: 12px; padding: 6px 10px; border-radius: 6px; display: none; }
.orch-scan-status.is-visible { display: block; }
.orch-scan-status.is-loading { background: #e0e7ff; color: #1e3a8a; }
.orch-scan-status.is-success { background: #dcfce7; color: #166534; }
.orch-scan-status.is-error { background: #fee2e2; color: #991b1b; }
.orch-scan-status.is-locked { background: #fef3c7; color: #92400e; }
.orch-suggested-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 7px; background: #dbeafe; color: #1e40af; border-radius: 10px; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; margin-left: 6px; vertical-align: middle; }
.orch-suggested-badge.is-stale { background: #f1f5f9; color: #64748b; }
.orch-industry-help { margin-top: 6px; padding: 8px 12px; background: #f8fafc; border-left: 2px solid #94a3b8; border-radius: 4px; font-size: 11px; color: #475569; line-height: 1.55; }
.orch-industry-help strong { color: #0f172a; }
.orch-industry-help-list { margin: 4px 0 0 14px; padding: 0; list-style: disc; }
.orch-industry-help-list li { margin: 2px 0; }
.orch-coach-inline { margin: 6px 0 0; padding: 8px 12px; background: #fef3c7; border-left: 2px solid #f59e0b; border-radius: 4px; font-size: 12px; color: #78350f; line-height: 1.5; }
.orch-coach-inline strong { color: #451a03; }
.orch-coach-inline-close { margin-left: 8px; color: #78350f; cursor: pointer; opacity: 0.6; font-size: 14px; line-height: 1; }
.orch-coach-inline-close:hover { opacity: 1; }



/* ═══ Stage 2.5 (3.35.55) — banner + modals + roles table ═══ */
.orch-stage25-banner { margin: 0 0 18px; padding: 16px 20px; border-radius: 10px; background: #fafbfc; border: 1px solid #e2e8f0; transition: all .15s; }
.orch-stage25-banner[data-orch-stage25-banner="loading"] { background: #f1f5f9; color: #64748b; font-size: 13px; }
.orch-stage25-banner[data-orch-stage25-banner="free"] { background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%); border-color: #bfdbfe; border-left: 4px solid #0055FF; }
.orch-stage25-banner[data-orch-stage25-banner="pro-onboarding"] { background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border-color: #bbf7d0; border-left: 4px solid #10b981; }
.orch-stage25-banner[data-orch-stage25-banner="pro-rescan"] { background: #f8fafc; border-left: 4px solid #6366f1; }
.orch-stage25-banner[data-orch-stage25-banner="pro-quota-exhausted"] { background: #fefce8; border-left: 4px solid #eab308; }
.orch-stage25-banner h4 { margin: 0 0 8px; font-size: 15px; color: #0f172a; }
.orch-stage25-banner p { margin: 0 0 10px; font-size: 13px; line-height: 1.5; color: #334155; }
.orch-stage25-banner ul { margin: 6px 0 10px 18px; padding: 0; font-size: 13px; color: #475569; }
.orch-stage25-banner ul li { margin: 2px 0; }
.orch-stage25-banner-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-top: 8px; }
.orch-stage25-banner-counter { font-size: 12px; color: #64748b; }
.orch-stage25-banner-upsell { margin-top: 12px; padding: 10px 12px; background: rgba(255,255,255,0.5); border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12px; color: #475569; }
.orch-stage25-banner-upsell a { color: #0055FF; font-weight: 600; }

/* Modals */
.orch-modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); display: flex; align-items: center; justify-content: center; z-index: 999999; padding: 20px; }
.orch-modal-backdrop[hidden] { display: none; }
.orch-modal-window { background: #ffffff; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.25); max-width: 600px; width: 100%; max-height: 90vh; display: flex; flex-direction: column; }
.orch-modal-window-large { max-width: 720px; }
.orch-modal-head { display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; border-bottom: 1px solid #e2e8f0; }
.orch-modal-head h3 { margin: 0; font-size: 17px; color: #0f172a; }
.orch-modal-close { background: transparent; border: 0; font-size: 24px; line-height: 1; color: #94a3b8; cursor: pointer; padding: 0; }
.orch-modal-close:hover { color: #0f172a; }
.orch-modal-body { padding: 18px 22px; overflow-y: auto; flex: 1; }
.orch-modal-foot { display: flex; justify-content: flex-end; gap: 10px; padding: 14px 22px; border-top: 1px solid #e2e8f0; background: #f8fafc; border-radius: 0 0 12px 12px; }

/* Page Picker Modal */
.orch-picker-search-wrap { position: relative; margin: 14px 0; }
.orch-picker-list { display: flex; flex-direction: column; gap: 4px; max-height: 360px; overflow-y: auto; padding: 4px 0; border: 1px solid #e2e8f0; border-radius: 8px; }
.orch-picker-list-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background .12s; }
.orch-picker-list-item:hover { background: #f8fafc; }
.orch-picker-list-item:last-child { border-bottom: 0; }
.orch-picker-list-item input[type="checkbox"] { margin: 0; }
.orch-picker-list-item-title { flex: 1; font-size: 13px; color: #0f172a; }
.orch-picker-list-item-meta { display: flex; gap: 6px; align-items: center; }
.orch-picker-list-item-type { font-size: 10px; padding: 2px 6px; background: #f1f5f9; color: #475569; border-radius: 4px; font-family: ui-monospace, monospace; text-transform: uppercase; }
.orch-picker-list-item-role { font-size: 10px; padding: 2px 6px; background: #dbeafe; color: #1e40af; border-radius: 4px; font-family: ui-monospace, monospace; }
.orch-picker-counter { text-align: center; margin: 10px 0 0; font-size: 12px; }

/* Onboarding modal */
.orch-onboarding-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 16px 0; }
@media (max-width: 600px) { .orch-onboarding-grid { grid-template-columns: 1fr; } }
.orch-onboarding-col { padding: 12px 14px; border-radius: 8px; }
.orch-onboarding-col strong { display: block; margin-bottom: 6px; font-size: 13px; }
.orch-onboarding-col ul { margin: 0; padding-left: 18px; font-size: 12px; line-height: 1.6; color: #475569; }
.orch-onboarding-do { background: #f0fdf4; border: 1px solid #bbf7d0; }
.orch-onboarding-do strong { color: #166534; }
.orch-onboarding-dont { background: #fef2f2; border: 1px solid #fecaca; }
.orch-onboarding-dont strong { color: #991b1b; }

/* Page Roles table */
.orch-page-roles-actions { display: flex; gap: 10px; align-items: center; margin: 12px 0; flex-wrap: wrap; }
.orch-page-roles-meta { font-size: 12px; }
.orch-page-roles-filters { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
.orch-page-roles-filters input, .orch-page-roles-filters select { font-size: 13px; padding: 7px 10px; }
.orch-roles-table { width: 100%; border-collapse: collapse; font-size: 12px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; }
.orch-roles-table th { padding: 8px 10px; background: #f8fafc; font-weight: 600; text-align: left; color: #334155; border-bottom: 1px solid #e2e8f0; }
.orch-roles-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.orch-roles-table tbody tr:hover { background: #f8fafc; }
.orch-role-pill { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; font-family: ui-monospace, monospace; }
.orch-role-pill.role-homepage { background: #dbeafe; color: #1e40af; }
.orch-role-pill.role-blog_index { background: #ede9fe; color: #5b21b6; }
.orch-role-pill.role-about { background: #cffafe; color: #155e75; }
.orch-role-pill.role-contact { background: #ccfbf1; color: #115e59; }
.orch-role-pill.role-faq { background: #fef3c7; color: #854d0e; }
.orch-role-pill.role-quote_request { background: #fed7aa; color: #9a3412; }
.orch-role-pill.role-knowledge_guide { background: #f5d0fe; color: #701a75; }
.orch-role-pill.role-category_landing { background: #fce7f3; color: #831843; }
.orch-role-pill.role-service_page { background: #dcfce7; color: #14532d; }
.orch-role-pill.role-product_page { background: #fef9c3; color: #713f12; }
.orch-role-pill.role-blog_post { background: #f1f5f9; color: #475569; }
.orch-role-pill.role-legal_privacy, .orch-role-pill.role-legal_terms { background: #fee2e2; color: #7f1d1d; }
.orch-role-pill.role-custom { background: #f5f5f4; color: #57534e; }
.orch-role-pill.role-ignore { background: #fafafa; color: #a8a29e; text-decoration: line-through; }
.orch-role-source-pill { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 10px; font-family: ui-monospace, monospace; background: #f1f5f9; color: #64748b; text-transform: uppercase; }
.orch-role-source-pill.is-llm { background: #ede9fe; color: #5b21b6; }
.orch-role-source-pill.is-manual { background: #fef3c7; color: #854d0e; }
.orch-role-confidence-bar { display: inline-block; width: 60px; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; }
.orch-role-confidence-bar-fill { height: 100%; background: linear-gradient(90deg, #f59e0b, #10b981); }
.orch-role-override-select { font-size: 11px; padding: 3px 6px; border-radius: 4px; }

.orch-featured-auto-btn { font-size: 12px; padding: 5px 10px; flex-shrink: 0; }
.orch-featured-search-wrap { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
.orch-featured-search-wrap input { flex: 1; min-width: 200px; }



/* ═══ Stage 2.5 (3.35.56) — Scan diff modal + featured confirm ═══ */
.orch-scan-diff-body { display: flex; flex-direction: column; gap: 16px; padding: 8px 0; }
.orch-scan-diff-row { padding: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; }
.orch-scan-diff-label { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
.orch-scan-diff-versions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 10px; }
@media (max-width: 600px) { .orch-scan-diff-versions { grid-template-columns: 1fr; } }
.orch-scan-diff-current, .orch-scan-diff-candidate { padding: 10px 12px; border-radius: 6px; font-size: 12px; line-height: 1.5; }
.orch-scan-diff-current { background: #fef9c3; border-left: 3px solid #ca8a04; color: #713f12; }
.orch-scan-diff-candidate { background: #eff6ff; border-left: 3px solid #0055FF; color: #1e3a8a; }
.orch-scan-diff-current strong, .orch-scan-diff-candidate strong { display: block; margin-bottom: 4px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px; }
.orch-scan-diff-text { word-wrap: break-word; }
.orch-scan-diff-choices { display: flex; gap: 16px; padding: 6px 0; flex-wrap: wrap; }
.orch-scan-diff-choices label { display: flex; align-items: center; gap: 4px; cursor: pointer; font-size: 13px; }
.orch-scan-diff-edit textarea { width: 100%; min-height: 60px; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; font-family: inherit; resize: vertical; margin-top: 8px; }



/* ═══ Stage 2.5 A.1-A.3 (3.35.58) — Schema accordion sub-fieldsets ═══ */
.orch-schema-suboption { margin-top: 10px; }
.orch-schema-suboption summary { cursor: pointer; padding: 8px 12px; background: #f8fafc; border-radius: 6px; font-size: 13px; }
.orch-schema-suboption[open] summary { background: #e0e7ff; color: #1e3a8a; border-radius: 6px 6px 0 0; }
.orch-schema-suboption-body { padding: 14px; border: 1px solid #e2e8f0; border-top: 0; border-radius: 0 0 6px 6px; }
.orch-schema-suboption-h5 { margin: 14px 0 6px; font-size: 12px; font-weight: 600; color: #334155; text-transform: uppercase; letter-spacing: 0.5px; }
.orch-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.orch-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
@media (max-width: 600px) { .orch-grid-2, .orch-grid-3 { grid-template-columns: 1fr; } }
.orch-org-same-as-list { display: flex; flex-direction: column; gap: 6px; margin-bottom: 10px; }
.orch-org-same-as-row { display: flex; gap: 6px; }
.orch-org-same-as-row select { width: 100px; flex-shrink: 0; }
.orch-org-same-as-row input { flex: 1; }
.orch-org-same-as-row button { width: 28px; height: 28px; padding: 0; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 6px; cursor: pointer; flex-shrink: 0; }
.orch-cpt-override-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.orch-cpt-override-table th { padding: 6px 10px; background: #f1f5f9; text-align: left; font-weight: 600; }
.orch-cpt-override-table td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; }
.orch-cpt-override-table select { width: 180px; font-size: 12px; padding: 4px 6px; }



/* ═══ Stage 2.5 (3.35.59) — Head accordion sub-panels ═══ */
.orch-metadesc-priority-list { display: flex; flex-direction: column; gap: 4px; padding: 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; }
.orch-metadesc-priority-row { display: flex; align-items: center; gap: 8px; padding: 6px 8px; background: #ffffff; border-radius: 4px; font-size: 12px; }
.orch-metadesc-priority-row .orch-priority-rank { font-weight: 700; color: #64748b; min-width: 16px; }
.orch-cpt-override-table input[type="checkbox"] { margin: 0; }
.orch-cpt-override-table input[type="text"] { width: 100%; font-size: 12px; padding: 4px 6px; }



/* ═══ Stage 2.5 (3.35.60) — Head accordion UX overhaul ═══ */
.orch-head-intro { margin-top: 4px; font-size: 14px; line-height: 1.55; color: #0f172a; }

.orch-head-config-summary { margin: 8px 0 18px; padding: 14px 16px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid #e2e8f0; border-radius: 8px; }
.orch-head-config-summary-title { margin: 0 0 8px; font-size: 13px; font-weight: 600; color: #0f172a; }
.orch-head-config-summary-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 4px; font-size: 13px; }
.orch-head-config-summary-list li { display: flex; align-items: center; gap: 8px; }
.orch-summary-icon { width: 18px; flex-shrink: 0; text-align: center; font-size: 14px; font-weight: 700; }
.orch-summary-icon[data-orch-summary-status="ok"] { color: #16a34a; }
.orch-summary-icon[data-orch-summary-status="warn"] { color: #ca8a04; }
.orch-summary-icon[data-orch-summary-status="error"] { color: #dc2626; }
.orch-summary-label { font-weight: 600; color: #334155; min-width: 130px; }
.orch-summary-value { color: #475569; }

.orch-schema-suboption summary { display: flex; flex-direction: column; gap: 2px; cursor: pointer; }
.orch-head-subpanel-title { font-size: 13px; }
.orch-head-subpanel-subtitle { font-size: 12px; color: #64748b; font-weight: 400; line-height: 1.4; }

.orch-explainer { margin: 0 0 14px; padding: 12px 14px; background: #fefce8; border-left: 3px solid #ca8a04; border-radius: 6px; font-size: 13px; line-height: 1.55; color: #713f12; }
.orch-explainer h5 { margin: 0 0 6px; font-size: 12px; color: #92400e; font-weight: 600; }
.orch-explainer p { margin: 0; }
.orch-explainer code { padding: 1px 4px; background: rgba(255,255,255,0.5); border-radius: 3px; font-size: 11px; }

.orch-current-config { margin: 0 0 14px; padding: 12px 14px; background: #f0fdf4; border-left: 3px solid #16a34a; border-radius: 6px; font-size: 13px; }
.orch-current-config h5 { margin: 0 0 6px; font-size: 12px; color: #166534; font-weight: 600; }
.orch-current-config-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 3px; color: #14532d; }
.orch-current-config-list li { padding: 0 0 0 16px; position: relative; }
.orch-current-config-list li::before { content: "•"; position: absolute; left: 4px; color: #16a34a; }
.orch-current-config-list li strong { color: #052e16; }
.orch-current-config-list li code { padding: 1px 4px; background: rgba(22,163,74,0.1); border-radius: 3px; font-size: 11px; }

.orch-advanced-options { margin-top: 8px; }
.orch-advanced-options > summary { padding: 8px 12px; background: #f1f5f9; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500; color: #334155; user-select: none; }
.orch-advanced-options > summary:hover { background: #e2e8f0; }
.orch-advanced-options[open] > summary { background: #e0e7ff; color: #1e3a8a; }
.orch-advanced-form { padding: 14px 0 4px; }

[data-orch-tooltip] { position: relative; }
[data-orch-tooltip]:hover::after {
    content: attr(data-orch-tooltip);
    position: absolute;
    bottom: calc(100% + 6px);
    left: 0;
    background: #0f172a;
    color: #f1f5f9;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 11px;
    line-height: 1.5;
    max-width: 320px;
    width: max-content;
    white-space: normal;
    z-index: 9999;
    box-shadow: 0 6px 16px rgba(0,0,0,0.25);
    pointer-events: none;
}



/* ═══ Stage 2.5 (3.35.60.2) — Clickable summary rows with auto-jump ═══ */
.orch-head-config-summary-hint { margin: 0 0 8px; font-size: 11px; line-height: 1.4; }

.orch-head-config-summary-list li[role="button"] {
    cursor: pointer;
    padding: 6px 10px;
    border-radius: 5px;
    transition: background .12s, box-shadow .12s;
    user-select: none;
}
.orch-head-config-summary-list li[role="button"]:hover {
    background: rgba(0, 85, 255, 0.08);
}
.orch-head-config-summary-list li[role="button"]:focus {
    outline: 2px solid #0055FF;
    outline-offset: 2px;
}
.orch-head-config-summary-list li[role="button"]:active {
    background: rgba(0, 85, 255, 0.16);
}

.orch-fix-pill {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    margin-left: auto;
    padding: 2px 8px;
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    flex-shrink: 0;
}
.orch-head-config-summary-list li.is-warn .orch-fix-pill { display: inline-flex; }
.orch-head-config-summary-list li:not(.is-warn) .orch-fix-pill { display: none; }

@keyframes orchPulseWarn {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.55);
        border-color: #f59e0b;
    }
    50% {
        box-shadow: 0 0 0 8px rgba(245, 158, 11, 0);
        border-color: #d97706;
    }
}
.orch-pulse-warn {
    animation: orchPulseWarn 1s ease-in-out 0s 2;
    border: 2px solid #f59e0b !important;
    border-radius: 6px !important;
    transition: none !important;
}



/* ═══ 3.35.68 D.5: Live preview <head> ═══ */
.orch-head-preview-panel {
    margin: 8px 0 14px;
    padding: 12px 14px;
    background: #0f172a;
    color: #e2e8f0;
    border-radius: 8px;
    border: 1px solid #1e293b;
}
.orch-head-preview-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
.orch-head-preview-head h5 { color: #e2e8f0; font-size: 13px; }
.orch-head-preview-controls { display: flex; align-items: center; gap: 10px; }
.orch-head-preview-controls label { color: #cbd5e1; }
.orch-head-preview-panel .orch3-muted { color: #94a3b8; }
.orch-head-preview-code {
    margin: 0;
    padding: 10px 12px;
    background: #020617;
    color: #94a3b8;
    border-radius: 6px;
    font-size: 11px;
    line-height: 1.45;
    max-height: 280px;
    overflow: auto;
    white-space: pre-wrap;
    word-break: break-all;
    font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
}
.orch-head-preview-code code { color: inherit; background: transparent; }
.orch-head-preview-code .tag-name { color: #f472b6; }
.orch-head-preview-code .attr-name { color: #fbbf24; }
.orch-head-preview-code .attr-value { color: #86efac; }
.orch-head-preview-code .highlight {
    background: rgba(250, 204, 21, 0.18);
    border-left: 2px solid #fbbf24;
    padding-left: 6px;
    transition: background 1.5s;
}

/* 3.35.68 — accordion-level status summary scaffold (light version of orch-head-config-summary) */
.orch-acc-status-summary {
    margin: 6px 0 14px;
    padding: 10px 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 12px;
}
.orch-acc-status-summary-title { margin: 0 0 6px; font-size: 12px; font-weight: 600; color: #0f172a; }
.orch-acc-status-summary-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 3px; }
.orch-acc-status-summary-list li { display: flex; align-items: center; gap: 6px; }
.orch-acc-status-summary-list .orch-acc-status-icon { width: 16px; flex-shrink: 0; text-align: center; }



/* ═══ 3.35.72.1: Global media picker UI ═══ */
.orch-image-picker-row { margin: 8px 0; padding: 10px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; }
.orch-image-input-group { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; margin: 6px 0; }
.orch-image-input-group input { flex: 1; min-width: 280px; }
.orch-image-preview img { max-width: 200px; max-height: 100px; border: 1px solid #e2e8f0; border-radius: 4px; padding: 4px; background: #fff; margin-top: 6px; }
.orch-image-preview-meta { font-size: 11px; color: #64748b; margin-top: 4px; font-family: ui-monospace, monospace; }
.orch-image-validation { margin-top: 6px; font-size: 12px; }
.orch-image-validation.orch-img-warn { color: #92400e; background: #fef3c7; padding: 6px 10px; border-radius: 4px; border: 1px solid #fde68a; }
.orch-image-validation.orch-img-ok { color: #14532d; background: #dcfce7; padding: 6px 10px; border-radius: 4px; border: 1px solid #86efac; }
.orch-image-validation.orch-img-err { color: #991b1b; background: #fee2e2; padding: 6px 10px; border-radius: 4px; border: 1px solid #fca5a5; }



/* ═══ 3.35.79: Floating save bar ═══ */
.orch-floating-savebar {
    position: fixed;
    /* 3.35.80.1: repositioned bottom-right (Yoast/RankMath/AIOSEO pattern) */
    top: auto;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-left: 4px solid #f59e0b;
    border-radius: 8px;
    box-shadow: 0 6px 24px rgba(15, 23, 42, 0.18);
    padding: 12px 16px;
    display: none;
    align-items: center;
    gap: 12px;
    font-size: 13px;
    transform: translateY(12px);
    opacity: 0;
    transition: opacity 250ms ease, transform 250ms ease;
}
.orch-floating-savebar.is-dirty {
    display: flex;
    transform: translateY(0);
    opacity: 1;
}
.orch-floating-savebar.is-saving {
    border-left-color: #0055FF;
}
.orch-floating-savebar.is-saved {
    border-left-color: #10b981;
}
.orch-floating-savebar-icon {
    color: #f59e0b;
    font-size: 14px;
    animation: orchFloatingPulse 1.5s ease-in-out infinite;
}
.orch-floating-savebar.is-saved .orch-floating-savebar-icon {
    color: #10b981;
    animation: none;
}
.orch-floating-savebar-label {
    color: #334155;
    font-weight: 500;
}
@keyframes orchFloatingPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}
.orch-floating-toast {
    position: fixed;
    /* 3.35.80.1: toast follows save bar to bottom-right */
    top: auto;
    bottom: 80px;
    right: 24px;
    z-index: 10000;
    background: #10b981;
    color: #fff;
    padding: 10px 16px;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    font-size: 13px;
    font-weight: 500;
    opacity: 0;
    transform: translateY(8px);
    transition: opacity 250ms ease, transform 250ms ease;
    pointer-events: none;
}
.orch-floating-toast.is-visible {
    opacity: 1;
    transform: translateY(0);
}
@media (max-width: 880px) {
    .orch-floating-savebar { right: 12px; bottom: 12px; top: auto; padding: 10px 12px; gap: 8px; }
}



/* ═══ 3.35.79.4: empty-state clickable link inside ✅ Configurazione attiva ═══ */
.orch-current-config-list .orch-empty-link {
    color: #92400e;
    text-decoration: underline dotted;
    cursor: pointer;
}
.orch-current-config-list .orch-empty-link:hover {
    color: #78350f;
    text-decoration: underline solid;
}
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

<?php ob_start(); ?>
/* ═════════════════════════════════════════════════════════
       3.12.0 — Native SEO Output (sostituto di Yoast/RankMath/AIOSEO)
       ═════════════════════════════════════════════════════════ */
    jQuery(function($) {
        var $card = $('.orch-native-card');
        if (!$card.length) return;
        var $body = $card.find('.orch-native-body');
        var $toggle = $card.find('.orch-native-toggle-card');
        var $status = $('#orch-native-status');
        var loaded = false;

        $toggle.on('click', function() {
            var open = $body.is(':visible');
            $body.slideToggle(180);
            $toggle.attr('aria-expanded', open ? 'false' : 'true').text(open ? 'Mostra' : 'Nascondi');
            if (!open && !loaded) loadStatus();
        });

        // 3.20.4 — Auto-load se card già visibile al mount (pagina dedicata)
        if ($body.is(':visible') && !loaded) {
            loadStatus();
        }

        function loadStatus() {
            loaded = true;
            console.log('[SEO_AEO Native] loadStatus() chiamato, nonce=' + (seoAeoOrchestra && seoAeoOrchestra.nonce ? 'OK' : 'MISSING') + ', ajaxurl=' + ajaxurl);
            $.post(ajaxurl, {
                action: 'seo_aeo_orchestra_native_output_status',
                nonce: seoAeoOrchestra.nonce
            }).done(function(resp) {
                console.log('[SEO_AEO Native] response:', resp);
                if (!resp || resp.error) {
                    $status.html('<div class="orch-cannibal-error">Stato non disponibile: ' + (resp && resp.error ? resp.error : 'no response') + '</div>');
                    return;
                }
                renderStatus(resp);
            }).fail(function(xhr, textStatus, errorThrown) {
                console.error('[SEO_AEO Native] AJAX fail:', xhr.status, textStatus, errorThrown, xhr.responseText);
                $status.html('<div class="orch-cannibal-error">Errore rete (HTTP ' + xhr.status + '): ' + (textStatus || 'unknown') + '. Vedi console DevTools.</div>');
            });
        }

        function renderStatus(s) {
            var html = '';
            if (s.is_active) {
                var modeLabel = s.override_setting && s.other_seo_plugin
                    ? 'Override Mode (' + escapeHtml(s.other_seo_plugin_label || '') + ' è installato ma silenziato)'
                    : 'Modalità native pulita';
                html += '<div class="orch-native-active">' +
                          '<div class="orch-native-active-head">✅ Output nativo <strong>ATTIVO</strong> · ' + modeLabel + '</div>' +
                          '<div>Orchestra sta generando direttamente nel <code>&lt;head&gt;</code>: <code>&lt;title&gt;</code>, meta description, OpenGraph, Twitter Cards, canonical, robots.</div>' +
                          '<div style="margin-top:8px;font-size:12px;">Verifica: apri il source code (CTRL+U) di una pagina del sito e cerca <code>&lt;!-- AEO Orchestra · Native Output --&gt;</code>. Cerca anche che NON ci siano tag <code>&lt;meta name="description"&gt;</code> di Yoast/RankMath duplicati.</div>' +
                        '</div>';
            } else if (s.other_seo_plugin) {
                var plug = s.other_seo_plugin_label || s.other_seo_plugin;
                html += '<div class="orch-native-warn">' +
                          '<div class="orch-native-warn-head">⚠️ Rilevato plugin SEO: <strong>' + escapeHtml(plug) + '</strong></div>' +
                          '<div>Hai due strade per attivare l\'output di Orchestra:</div>' +
                          '<ul style="margin:6px 0 0 18px;">' +
                            '<li><strong>Opzione 1 (consigliata, pulita)</strong>: disinstalla ' + escapeHtml(plug) + ' dai Plugin → poi attiva il toggle "Output nativo" qui sotto.</li>' +
                            '<li><strong>Opzione 2 (Override Mode, expert)</strong>: lascia ' + escapeHtml(plug) + ' installato MA silenziato sul frontend. Orchestra prende il comando del <code>&lt;head&gt;</code>. UI di ' + escapeHtml(plug) + ' resta usabile nel post editor (per editare meta), ma nessuno dei suoi tag arriva al sito pubblico.</li>' +
                          '</ul>' +
                        '</div>';
            } else {
                html += '<div class="orch-native-disabled">' +
                          '<div class="orch-native-disabled-head">💤 Output nativo non attivo</div>' +
                          '<div>Nessun plugin SEO rilevato e l\'output nativo è disattivato. Le pagine pubbliche non hanno <code>&lt;meta description&gt;</code> automatici nel <code>&lt;head&gt;</code> finché non lo attivi.</div>' +
                        '</div>';
            }

            // Toggle 1: enable base
            html += '<div class="orch-native-toggle-row">' +
                      '<label class="orch-native-switch">' +
                        '<input type="checkbox" id="orch-native-toggle" data-field="enable"' + (s.enabled_setting ? ' checked' : '') + '>' +
                        '<span class="orch-native-switch-slider"></span>' +
                      '</label>' +
                      '<span class="orch-native-toggle-label">' +
                        '<strong>Output nativo</strong>: ' + (s.enabled_setting ? 'attivo. ' : 'disattivato. ') +
                        (s.enabled_setting
                            ? 'Orchestra emette i tag SEO nel &lt;head&gt; (se non c\'è blocco da altro plugin).'
                            : 'Attivalo per far emettere a Orchestra i tag SEO nel &lt;head&gt;.') +
                      '</span>' +
                    '</div>';

            // Toggle 2: override mode (expert) - mostra solo se altro plugin SEO presente OPPURE override già ON
            if (s.other_seo_plugin || s.override_setting) {
                html += '<div class="orch-native-toggle-row orch-native-override-row">' +
                          '<label class="orch-native-switch">' +
                            '<input type="checkbox" id="orch-native-override-toggle" data-field="override"' + (s.override_setting ? ' checked' : '') + '>' +
                            '<span class="orch-native-switch-slider"></span>' +
                          '</label>' +
                          '<span class="orch-native-toggle-label">' +
                            '<strong>🔧 Override Mode (avanzato)</strong>: ' +
                            (s.override_setting
                                ? 'attivo. I hook di ' + escapeHtml(s.other_seo_plugin_label || 'altri plugin SEO') + ' sul &lt;head&gt; vengono rimossi runtime. Solo Orchestra emette tag SEO.'
                                : 'spento. Attivalo per silenziare ' + escapeHtml(s.other_seo_plugin_label || 'altri plugin SEO') + ' senza disinstallarlo.') +
                            '<br><span style="color:#92400e; font-size:11px;">⚠️ Modalità per esperti. Test sempre prima in staging. Lo disattivi qui sotto per tornare indietro.</span>' +
                          '</span>' +
                        '</div>';
            }

            html += '<div class="orch-native-features">' +
                      '<div class="orch-native-features-title">Cosa viene generato quando attivo:</div>' +
                      '<ul>' +
                        '<li>✓ <code>&lt;title&gt;</code> SEO con suffisso "- Nome Sito" automatico</li>' +
                        '<li>✓ <code>&lt;meta name="description"&gt;</code> da focus keyword o auto-excerpt</li>' +
                        '<li>✓ <code>&lt;link rel="canonical"&gt;</code> per ogni URL</li>' +
                        '<li>✓ OpenGraph completo (title, description, image, url, type, locale)</li>' +
                        '<li>✓ Twitter Cards (summary_large_image se c\'è featured image)</li>' +
                        '<li>✓ Robots tag (noindex automatico per search/404, index per il resto)</li>' +
                        '<li>✓ Article published_time / modified_time / author per i post</li>' +
                      '</ul>' +
                      '<div class="orch3-muted" style="font-size:11px; margin-top:6px;">In arrivo nei prossimi giorni: Schema.org dinamico, Redirect manager, Migration wizard da Yoast.</div>' +
                    '</div>';

            // ─── Sezione Sitemap.xml (3.13.0) ───
            html += '<div id="orch-native-sitemap-section"></div>';
            // ─── Sezione llms.txt (3.13.1) ───
            html += '<div id="orch-native-llms-section"></div>';
            // ─── Sezione Schema.org (3.14.0) ───
            html += '<div id="orch-native-schema-section"></div>';
            // ─── Sezione Redirect Manager (3.15.0) ───

            $status.html(html);

            // Carica stato sitemap + llms.txt + schema + redirect (separati perché usano altri endpoint)
            loadSitemapStatus();
            loadLlmsTxtStatus();
            loadSchemaStatus();

            $(document).off('change.orchToggle1', '#orch-native-toggle, #orch-native-override-toggle').on('change.orchToggle1', '#orch-native-toggle, #orch-native-override-toggle', function() {
                var $sw = $(this);
                var field = $sw.data('field') || 'enable';
                var enable = $sw.prop('checked');

                // Confirm extra per Override Mode (azione invasiva)
                if (field === 'override' && enable) {
                    var ok = confirm('Stai per attivare Override Mode.\n\nCosa succederà:\n• I tag SEO di Yoast/RankMath/AIOSEO non verranno più emessi sul frontend\n• Orchestra prenderà il controllo completo del <head> SEO\n• Yoast resta installato (UI utilizzabile nel post editor)\n• Reversibile: basta disattivare questo toggle\n\nTi consigliamo di verificare il sito (CTRL+U source code) subito dopo per controllare che ci siano i tag Orchestra e NON ci siano duplicati.\n\nProcedere?');
                    if (!ok) {
                        $sw.prop('checked', false);
                        return;
                    }
                }

                $sw.prop('disabled', true);
                $.post(ajaxurl, {
                    action: 'seo_aeo_orchestra_toggle_native_output',
                    nonce: seoAeoOrchestra.nonce,
                    field: field,
                    enable: enable ? 1 : 0
                }).done(function(resp) {
                    if (!resp || resp.error) {
                        alert('Errore: ' + ((resp && (resp.message || (typeof resp.error === 'string' ? resp.error : ''))) || 'sconosciuto'));
                        $sw.prop('checked', !enable).prop('disabled', false);
                        return;
                    }
                    loaded = false;
                    loadStatus();
                }).fail(function(xhr) {
                    alert('Errore rete (' + xhr.status + ')');
                    $sw.prop('checked', !enable).prop('disabled', false);
                });
            });
        }

        // ─── Sitemap.xml status + toggle (3.13.0) ───
        function loadSitemapStatus() {
            $.post(ajaxurl, {
                action: 'seo_aeo_orchestra_native_sitemap_status',
                nonce: seoAeoOrchestra.nonce
            }).done(function(resp) {
                if (!resp || resp.error) return;
                renderSitemapSection(resp);
            });
        }

        function renderSitemapSection(s) {
            var statsHtml = '';
            if (s.enabled && s.stats) {
                var rows = [];
                for (var pt in s.stats) {
                    if (s.stats.hasOwnProperty(pt)) {
                        rows.push('<li><strong>' + escapeHtml(pt) + '</strong>: ' + parseInt(s.stats[pt],10) + ' URL pubblicati</li>');
                    }
                }
                statsHtml = '<ul style="margin:6px 0 0 18px; line-height:1.7; color:#334155;">' + rows.join('') + '</ul>';
            }

            var statusBlock = s.enabled
                ? '<div class="orch-native-active">' +
                    '<div class="orch-native-active-head">✅ Sitemap.xml <strong>ATTIVA</strong></div>' +
                    '<div>Disponibile pubblicamente all\'URL: <a href="' + escapeHtml(s.sitemap_url) + '" target="_blank"><code>' + escapeHtml(s.sitemap_url) + '</code></a></div>' +
                    '<div style="margin-top:8px;font-size:12px;">Cache 6h, invalidata automaticamente quando crei/modifichi/elimini post o termini. Aggiunta automaticamente in robots.txt.</div>' +
                    statsHtml +
                  '</div>'
                : '<div class="orch-native-disabled">' +
                    '<div class="orch-native-disabled-head">💤 Sitemap.xml non attiva</div>' +
                    '<div>Attivala per esporre tutti i post/pagine/tassonomie a Google in formato XML standard. Convive con eventuale sitemap di Yoast (URL diversi).</div>' +
                  '</div>';

            var html = '<hr style="margin:18px 0; border:none; border-top:1px solid #e5e7eb;">' +
                       '<h3 style="margin:0 0 8px; font-size:15px; color:#0a0e27;">🗺️ Sitemap.xml automatica</h3>' +
                       '<p class="orch3-muted" style="margin:0 0 14px; font-size:13px;">Espone una sitemap XML che lista tutti i contenuti pubblici del sito, ordinati per tipo. Google la trova automaticamente via robots.txt.</p>' +
                       statusBlock +
                       '<div class="orch-native-toggle-row">' +
                         '<label class="orch-native-switch">' +
                           '<input type="checkbox" id="orch-native-sitemap-toggle"' + (s.enabled ? ' checked' : '') + '>' +
                           '<span class="orch-native-switch-slider"></span>' +
                         '</label>' +
                         '<span class="orch-native-toggle-label">' +
                           '<strong>Sitemap.xml nativa</strong>: ' + (s.enabled
                              ? 'attiva. Esposta a <a href="' + escapeHtml(s.sitemap_url) + '" target="_blank">/seo-aeo-sitemap.xml</a>. Sottoponi questo URL a Google Search Console.'
                              : 'disattivata. Attivala per generare la sitemap XML.') +
                         '</span>' +
                       '</div>';

            $('#orch-native-sitemap-section').html(html);

            $(document).off('change.orchToggle2', '#orch-native-sitemap-toggle').on('change.orchToggle2', '#orch-native-sitemap-toggle', function() {
                var $sw = $(this);
                var enable = $sw.prop('checked');
                $sw.prop('disabled', true);
                $.post(ajaxurl, {
                    action: 'seo_aeo_orchestra_toggle_native_sitemap',
                    nonce: seoAeoOrchestra.nonce,
                    enable: enable ? 1 : 0
                }).done(function(resp) {
                    if (!resp || resp.error) {
                        alert('Errore: ' + ((resp && (resp.message || (typeof resp.error === 'string' ? resp.error : ''))) || 'sconosciuto'));
                        $sw.prop('checked', !enable).prop('disabled', false);
                        return;
                    }
                    loadSitemapStatus();
                }).fail(function(xhr) {
                    alert('Errore rete (' + xhr.status + ')');
                    $sw.prop('checked', !enable).prop('disabled', false);
                });
            });
        }

        // ─── llms.txt status + toggle (3.13.1) ───
        function loadLlmsTxtStatus() {
            $.post(ajaxurl, {
                action: 'seo_aeo_orchestra_native_llms_txt_status',
                nonce: seoAeoOrchestra.nonce
            }).done(function(resp) {
                if (!resp || resp.error) return;
                renderLlmsTxtSection(resp);
            });
        }

        function renderLlmsTxtSection(s) {
            var statusBlock = s.enabled
                ? '<div class="orch-native-active">' +
                    '<div class="orch-native-active-head">✅ llms.txt <strong>ATTIVO</strong> · primo plugin WordPress al mondo!</div>' +
                    '<div>Due endpoint markdown disponibili per i Large Language Models:</div>' +
                    '<ul style="margin:6px 0 0 18px;">' +
                      '<li><code>/llms.txt</code> → <a href="' + escapeHtml(s.index_url) + '" target="_blank">' + escapeHtml(s.index_url) + '</a> · index curato (titoli + descrizioni delle pagine principali)</li>' +
                      '<li><code>/llms-full.txt</code> → <a href="' + escapeHtml(s.full_url) + '" target="_blank">' + escapeHtml(s.full_url) + '</a> · contenuto markdown completo delle top 30 pagine</li>' +
                    '</ul>' +
                    '<div style="margin-top:8px; font-size:12px;">Crawler AI di ChatGPT (GPTBot), Claude (ClaudeBot), Perplexity (PerplexityBot) e Gemini possono trovare e usare questi file per le loro risposte. Cache 6h, invalidata ad ogni save_post.</div>' +
                  '</div>'
                : '<div class="orch-native-disabled">' +
                    '<div class="orch-native-disabled-head">💤 llms.txt non attivo</div>' +
                    '<div>Attivalo per esporre una mappa markdown del sito ottimizzata per Large Language Models. Aiuta ChatGPT/Claude/Perplexity/Gemini a citare correttamente i tuoi contenuti.</div>' +
                    '<div style="margin-top:6px; font-size:12px; color:#475569;"><strong>Vantaggio competitivo</strong>: nessun plugin SEO mainstream (Yoast/RankMath/AIOSEO) ha llms.txt automatico. Sei tra i primi siti italiani con questa capability.</div>' +
                  '</div>';

            var html = '<hr style="margin:18px 0; border:none; border-top:1px solid #e5e7eb;">' +
                       '<h3 style="margin:0 0 8px; font-size:15px; color:#0a0e27;">🤖 llms.txt + llms-full.txt <span style="background:linear-gradient(90deg,#10B981,#0055FF); color:white; font-size:10px; padding:2px 8px; border-radius:10px; text-transform:uppercase; letter-spacing:0.5px; vertical-align:middle; margin-left:6px;">Novità · primi al mondo</span></h3>' +
                       '<p class="orch3-muted" style="margin:0 0 14px; font-size:13px;">Mappe markdown del sito pensate per i Large Language Models (spec di Anthropic / Jeremy Howard 2024). Adopters: Anthropic, Stripe, Vercel, Cloudflare, Mintlify. Esponi i tuoi contenuti agli AI engine in formato leggibile e semanticamente ricco.</p>' +
                       statusBlock +
                       '<div class="orch-native-toggle-row">' +
                         '<label class="orch-native-switch">' +
                           '<input type="checkbox" id="orch-native-llms-toggle"' + (s.enabled ? ' checked' : '') + '>' +
                           '<span class="orch-native-switch-slider"></span>' +
                         '</label>' +
                         '<span class="orch-native-toggle-label">' +
                           '<strong>llms.txt + llms-full.txt</strong>: ' + (s.enabled
                              ? 'attivi. Esposti su <a href="' + escapeHtml(s.index_url) + '" target="_blank">/llms.txt</a> e <a href="' + escapeHtml(s.full_url) + '" target="_blank">/llms-full.txt</a>.'
                              : 'disattivati. Attivali per esporre il sito agli AI engine.') +
                         '</span>' +
                       '</div>';

            $('#orch-native-llms-section').html(html);

            $(document).off('change.orchToggle3', '#orch-native-llms-toggle').on('change.orchToggle3', '#orch-native-llms-toggle', function() {
                var $sw = $(this);
                var enable = $sw.prop('checked');
                $sw.prop('disabled', true);
                $.post(ajaxurl, {
                    action: 'seo_aeo_orchestra_toggle_native_llms_txt',
                    nonce: seoAeoOrchestra.nonce,
                    enable: enable ? 1 : 0
                }).done(function(resp) {
                    if (!resp || resp.error) {
                        alert('Errore: ' + ((resp && (resp.message || (typeof resp.error === 'string' ? resp.error : ''))) || 'sconosciuto'));
                        $sw.prop('checked', !enable).prop('disabled', false);
                        return;
                    }
                    loadLlmsTxtStatus();
                }).fail(function(xhr) {
                    alert('Errore rete (' + xhr.status + ')');
                    $sw.prop('checked', !enable).prop('disabled', false);
                });
            });
        }

        // ─── Schema.org status + toggle (3.14.0) ───
        function loadSchemaStatus() {
            $.post(ajaxurl, {
                action: 'seo_aeo_orchestra_native_schema_status',
                nonce: seoAeoOrchestra.nonce
            }).done(function(resp) {
                if (!resp || resp.error) return;
                renderSchemaSection(resp);
            });
        }

        function renderSchemaSection(s) {
            var statusBlock = s.enabled
                ? '<div class="orch-native-active">' +
                    '<div class="orch-native-active-head">✅ Schema.org dinamico <strong>ATTIVO</strong></div>' +
                    '<div>Genera automaticamente JSON-LD <code>@graph</code> per ogni pagina pubblica:</div>' +
                    '<ul style="margin:6px 0 0 18px;">' +
                      '<li><strong>Homepage</strong>: Organization + WebSite (con SearchAction per sitelinks searchbox)</li>' +
                      '<li><strong>Articoli</strong>: Article + WebPage + BreadcrumbList + ImageObject + Person (autore)</li>' +
                      '<li><strong>Pagine</strong>: WebPage + BreadcrumbList</li>' +
                      '<li><strong>Tassonomie/CPT</strong>: CollectionPage + BreadcrumbList</li>' +
                      '<li><strong>Author archive</strong>: ProfilePage</li>' +
                    '</ul>' +
                    '<div style="margin-top:8px;font-size:12px;">Verifica con <a href="https://search.google.com/test/rich-results?url=' + encodeURIComponent(s.home_url) + '" target="_blank">Google Rich Results Test</a> — vedrai gli schema rilevati come "validi".</div>' +
                  '</div>'
                : '<div class="orch-native-disabled">' +
                    '<div class="orch-native-disabled-head">💤 Schema.org non attivo</div>' +
                    '<div>Le pagine non hanno markup strutturato di Orchestra. Attivalo SOLO se NON hai già altri plugin/temi che generano JSON-LD (es. Yoast schema graph se Override Mode è OFF, oppure schema custom del theme).</div>' +
                    '<div style="margin-top:6px; font-size:12px; color:#92400e;"><strong>⚠️ Attenzione duplicati</strong>: se hai già schema custom nel sito (es. dato dal theme), attivare il nostro creerebbe schema duplicati. Verifica prima ispezionando il source code della homepage e cercando <code>application/ld+json</code>.</div>' +
                  '</div>';

            var html = '<hr style="margin:18px 0; border:none; border-top:1px solid #e5e7eb;">' +
                       '<h3 style="margin:0 0 8px; font-size:15px; color:#0a0e27;">📊 Schema.org dinamico (JSON-LD)</h3>' +
                       '<p class="orch3-muted" style="margin:0 0 14px; font-size:13px;">Markup strutturato Schema.org generato automaticamente. Aiuta Google e gli AI engine a comprendere il contenuto del sito (rich snippets, knowledge graph).</p>' +
                       statusBlock +
                       '<div class="orch-native-toggle-row">' +
                         '<label class="orch-native-switch">' +
                           '<input type="checkbox" id="orch-native-schema-toggle"' + (s.enabled ? ' checked' : '') + '>' +
                           '<span class="orch-native-switch-slider"></span>' +
                         '</label>' +
                         '<span class="orch-native-toggle-label">' +
                           '<strong>Schema.org dinamico</strong>: ' + (s.enabled
                              ? 'attivo. JSON-LD emesso automaticamente nel &lt;head&gt; di ogni pagina.'
                              : 'disattivato. Attivalo se non hai altre fonti di JSON-LD nel sito.') +
                         '</span>' +
                       '</div>';

            $('#orch-native-schema-section').html(html);

            $(document).off('change.orchToggle4', '#orch-native-schema-toggle').on('change.orchToggle4', '#orch-native-schema-toggle', function() {
                var $sw = $(this);
                var enable = $sw.prop('checked');
                if (enable) {
                    var ok = confirm('Stai per attivare Schema.org dinamico.\n\nIMPORTANTE: se il tuo theme o un altro plugin emette già JSON-LD (es. schema custom Organization+LocalBusiness), attivare questo creerebbe DUPLICATI di schema, che Google segnalerebbe come errore.\n\nVerifica prima:\n1. Apri il source code della homepage (CTRL+U)\n2. Cerca "application/ld+json"\n3. Se trovi già schema → meglio NON attivare, oppure rimuovere il custom prima\n\nProcedere?');
                    if (!ok) {
                        $sw.prop('checked', false);
                        return;
                    }
                }
                $sw.prop('disabled', true);
                $.post(ajaxurl, {
                    action: 'seo_aeo_orchestra_toggle_native_schema',
                    nonce: seoAeoOrchestra.nonce,
                    enable: enable ? 1 : 0
                }).done(function(resp) {
                    if (!resp || resp.error) {
                        alert('Errore: ' + ((resp && (resp.message || (typeof resp.error === 'string' ? resp.error : ''))) || 'sconosciuto'));
                        $sw.prop('checked', !enable).prop('disabled', false);
                        return;
                    }
                    loadSchemaStatus();
                }).fail(function(xhr) {
                    alert('Errore rete (' + xhr.status + ')');
                    $sw.prop('checked', !enable).prop('disabled', false);
                });
            });
        }

        // ─── Redirect Manager (3.15.0) ───
        function loadRedirectStatus() {
            $.post(ajaxurl, {
                action: 'seo_aeo_orchestra_redirect_status',
                nonce: seoAeoOrchestra.nonce
            }).done(function(resp) {
                if (!resp || resp.error) return;
                renderRedirectSection(resp);
            });
        }

        function renderRedirectSection(s) {
            var statusBlock = s.enabled
                ? '<div class="orch-native-active">' +
                    '<div class="orch-native-active-head">✅ Redirect manager <strong>ATTIVO</strong></div>' +
                    '<div><strong>' + parseInt(s.count, 10) + ' redirect</strong> configurati. Le richieste 404 vengono loggate per suggerirti redirect mancanti.</div>' +
                    '<div style="margin-top:6px;font-size:12px;">Supporta: redirect 301/302/307/308, regex con backreferences ($1, $2, ...), trailing slash auto-match, hit counter.</div>' +
                  '</div>'
                : '<div class="orch-native-disabled">' +
                    '<div class="orch-native-disabled-head">💤 Redirect manager non attivo</div>' +
                    '<div>Sostituisce Yoast Premium Redirect / Redirection plugin / .htaccess RewriteRule. Quando attivo, traccia anche tutti i 404 ricevuti dal sito (utile per scoprire link rotti).</div>' +
                  '</div>';

            var html = '<hr style="margin:18px 0; border:none; border-top:1px solid #e5e7eb;">' +
                       '<h3 style="margin:0 0 8px; font-size:15px; color:#0a0e27;">🔀 Redirect Manager</h3>' +
                       '<p class="orch3-muted" style="margin:0 0 14px; font-size:13px;">Gestisci redirect 301/302/307/308 dal database WP. Sostituisce funzionalmente Yoast Premium Redirects o il plugin Redirection.</p>' +
                       statusBlock +
                       '<div class="orch-native-toggle-row">' +
                         '<label class="orch-native-switch">' +
                           '<input type="checkbox" id="orch-native-redirect-toggle"' + (s.enabled ? ' checked' : '') + '>' +
                           '<span class="orch-native-switch-slider"></span>' +
                         '</label>' +
                         '<span class="orch-native-toggle-label">' +
                           '<strong>Redirect manager</strong>: ' + (s.enabled ? 'attivo' : 'disattivato') +
                         '</span>' +
                       '</div>';

            if (s.enabled) {
                html += '<div class="orch-redirect-controls">' +
                          '<button type="button" class="orch3-btn orch3-btn-primary orch3-btn-sm" id="orch-redirect-add-btn">+ Aggiungi redirect</button>' +
                          '<button type="button" class="orch3-btn orch3-btn-ghost orch3-btn-sm" id="orch-redirect-show-404-btn">📋 Mostra 404 recenti</button>' +
                          '<input type="text" id="orch-redirect-search" placeholder="Cerca per source/target..." class="orch-redirect-search-input">' +
                        '</div>' +
                        '<div id="orch-redirect-list-wrap" style="margin-top:12px;"></div>' +
                        '<div id="orch-redirect-404-wrap"></div>';
            }

            $('#orch-native-redirect-section').html(html);

            $(document).off('change.orchToggle5', '#orch-native-redirect-toggle').on('change.orchToggle5', '#orch-native-redirect-toggle', function() {
                var $sw = $(this);
                var enable = $sw.prop('checked');
                $sw.prop('disabled', true);
                $.post(ajaxurl, {
                    action: 'seo_aeo_orchestra_toggle_native_redirect',
                    nonce: seoAeoOrchestra.nonce,
                    enable: enable ? 1 : 0
                }).done(function(resp) {
                    if (!resp || resp.error) {
                        alert('Errore: ' + ((resp && (resp.message || (typeof resp.error === 'string' ? resp.error : ''))) || 'sconosciuto'));
                        $sw.prop('checked', !enable).prop('disabled', false);
                        return;
                    }
                    loadRedirectStatus();
                }).fail(function() {
                    $sw.prop('disabled', false);
                });
            });

            if (s.enabled) {
                loadRedirectList();
                $('#orch-redirect-add-btn').on('click', openRedirectAddModal);
                $('#orch-redirect-show-404-btn').on('click', loadRedirect404Log);
                var searchTimer = null;
                $('#orch-redirect-search').on('input', function() {
                    clearTimeout(searchTimer);
                    var v = this.value;
                    searchTimer = setTimeout(function() { loadRedirectList(v); }, 300);
                });
            }
        }

        function loadRedirectList(search) {
            search = search || '';
            $.post(ajaxurl, {
                action: 'seo_aeo_orchestra_redirect_list',
                nonce: seoAeoOrchestra.nonce,
                search: search
            }).done(function(resp) {
                if (!resp || resp.error) {
                    $('#orch-redirect-list-wrap').html('<div class="orch-cannibal-error">' + escapeHtml(resp && resp.error || 'Errore') + '</div>');
                    return;
                }
                renderRedirectTable(resp.items || [], resp.total);
            });
        }

        function renderRedirectTable(items, total) {
            if (!items.length) {
                $('#orch-redirect-list-wrap').html('<div class="orch3-muted" style="padding:14px; text-align:center; background:#f8fafc; border-radius:6px;">Nessun redirect ancora. Click "Aggiungi redirect" per iniziare.</div>');
                return;
            }
            var rows = items.map(function(r) {
                var typeLabel = r.redirect_type + (r.redirect_type === '301' || parseInt(r.redirect_type,10) === 301 ? ' permanente' : ' temp');
                var isRegex = parseInt(r.is_regex, 10) === 1;
                var isActive = parseInt(r.is_active, 10) === 1;
                var statusBadge = isActive
                    ? '<span class="orch-redirect-badge orch-redirect-badge-active">ON</span>'
                    : '<span class="orch-redirect-badge orch-redirect-badge-inactive">OFF</span>';
                var regexBadge = isRegex ? ' <span class="orch-redirect-badge orch-redirect-badge-regex">regex</span>' : '';
                return '<tr data-id="' + parseInt(r.id, 10) + '">' +
                         '<td>' + statusBadge + '</td>' +
                         '<td><code class="orch-redirect-code">' + escapeHtml(r.source_path) + '</code>' + regexBadge + '</td>' +
                         '<td><code class="orch-redirect-code">' + escapeHtml(r.target_url) + '</code></td>' +
                         '<td>' + escapeHtml(typeLabel) + '</td>' +
                         '<td style="text-align:right;">' + parseInt(r.hits, 10) + '</td>' +
                         '<td>' +
                           '<button class="orch-redirect-edit-btn orch3-btn orch3-btn-ghost orch3-btn-sm" data-id="' + r.id + '">Modifica</button> ' +
                           '<button class="orch-redirect-delete-btn orch3-btn orch3-btn-ghost orch3-btn-sm" data-id="' + r.id + '" style="color:#dc2626;">Elimina</button>' +
                         '</td>' +
                       '</tr>';
            }).join('');

            var html = '<table class="orch-redirect-table">' +
                         '<thead><tr><th></th><th>Source path</th><th>Target URL</th><th>Tipo</th><th style="text-align:right;">Hits</th><th>Azioni</th></tr></thead>' +
                         '<tbody>' + rows + '</tbody>' +
                       '</table>' +
                       '<div class="orch3-muted" style="margin-top:6px; font-size:11px;">Totale: ' + parseInt(total, 10) + ' redirect</div>';
            $('#orch-redirect-list-wrap').html(html);

            $('.orch-redirect-edit-btn').on('click', function() {
                var id = $(this).data('id');
                var r = items.find(function(x) { return parseInt(x.id, 10) === parseInt(id, 10); });
                if (r) openRedirectEditModal(r);
            });
            $('.orch-redirect-delete-btn').on('click', function() {
                var id = $(this).data('id');
                if (!confirm('Eliminare il redirect #' + id + '? Questa azione è permanente.')) return;
                $.post(ajaxurl, {
                    action: 'seo_aeo_orchestra_redirect_delete',
                    nonce: seoAeoOrchestra.nonce,
                    id: id
                }).done(function(resp) {
                    if (resp && resp.success) loadRedirectList($('#orch-redirect-search').val());
                    else alert('Errore eliminazione');
                });
            });
        }

        function openRedirectAddModal() {
            openRedirectModal({source_path:'', target_url:'', redirect_type:301, is_regex:0, is_active:1, notes:''}, false);
        }

        function openRedirectEditModal(r) {
            openRedirectModal(r, true);
        }

        function openRedirectModal(r, isEdit) {
            $('#orch-redirect-modal').remove();
            var title = isEdit ? 'Modifica redirect #' + r.id : 'Nuovo redirect';
            var html =
                '<div id="orch-redirect-modal" class="orch-modal-backdrop">' +
                  '<div class="orch-modal-window" style="max-width:600px;">' +
                    '<div class="orch-modal-head">' +
                      '<h3>' + escapeHtml(title) + '</h3>' +
                      '<button type="button" class="orch-modal-close" aria-label="Chiudi">×</button>' +
                    '</div>' +
                    '<div class="orch-modal-body">' +
                      '<label class="orch-redirect-field">' +
                        '<span>Source path</span>' +
                        '<input type="text" id="orch-redirect-source" value="' + escapeHtml(r.source_path || '') + '" placeholder="/old/page/" class="orch-redirect-input">' +
                        '<small class="orch3-muted">Path relativo al sito (es. <code>/old/page/</code>) oppure regex se attivi il flag sotto.</small>' +
                      '</label>' +
                      '<label class="orch-redirect-field">' +
                        '<span>Target URL</span>' +
                        '<input type="text" id="orch-redirect-target" value="' + escapeHtml(r.target_url || '') + '" placeholder="/new/page/ oppure https://..." class="orch-redirect-input">' +
                        '<small class="orch3-muted">URL relativo o assoluto. Per regex puoi usare <code>$1</code>, <code>$2</code> per backreferences.</small>' +
                      '</label>' +
                      '<div style="display:flex; gap:14px; margin-top:6px;">' +
                        '<label class="orch-redirect-field" style="flex:1;">' +
                          '<span>Tipo redirect</span>' +
                          '<select id="orch-redirect-type" class="orch-redirect-input">' +
                            '<option value="301"' + (parseInt(r.redirect_type,10)===301?' selected':'') + '>301 - Permanente (consigliato)</option>' +
                            '<option value="302"' + (parseInt(r.redirect_type,10)===302?' selected':'') + '>302 - Temporaneo</option>' +
                            '<option value="307"' + (parseInt(r.redirect_type,10)===307?' selected':'') + '>307 - Temporaneo (preserva method)</option>' +
                            '<option value="308"' + (parseInt(r.redirect_type,10)===308?' selected':'') + '>308 - Permanente (preserva method)</option>' +
                          '</select>' +
                        '</label>' +
                      '</div>' +
                      '<label class="orch-redirect-checkbox-row">' +
                        '<input type="checkbox" id="orch-redirect-is-regex"' + (parseInt(r.is_regex,10)===1?' checked':'') + '>' +
                        '<span>Source è una regex (con delimitatori # tipo <code>#^/old/(.*)$#</code>)</span>' +
                      '</label>' +
                      '<label class="orch-redirect-checkbox-row">' +
                        '<input type="checkbox" id="orch-redirect-is-active"' + (parseInt(r.is_active,10)===1?' checked':'') + '>' +
                        '<span>Attivo (se OFF, redirect ignorato)</span>' +
                      '</label>' +
                      '<label class="orch-redirect-field">' +
                        '<span>Note (opzionale)</span>' +
                        '<input type="text" id="orch-redirect-notes" value="' + escapeHtml(r.notes || '') + '" placeholder="Perché questo redirect" class="orch-redirect-input">' +
                      '</label>' +
                      '<div id="orch-redirect-modal-status" style="margin-top:10px;"></div>' +
                    '</div>' +
                    '<div class="orch-modal-foot">' +
                      '<button type="button" class="orch3-btn orch3-btn-ghost orch-modal-cancel">Annulla</button>' +
                      '<button type="button" class="orch3-btn orch3-btn-primary" id="orch-redirect-save-btn">Salva</button>' +
                    '</div>' +
                  '</div>' +
                '</div>';
            var $modal = $(html).appendTo('body');
            $modal.find('.orch-modal-close, .orch-modal-cancel').on('click', function() { $modal.remove(); });
            $modal.find('#orch-redirect-save-btn').on('click', function() {
                var $btn = $(this);
                var data = {
                    action: isEdit ? 'seo_aeo_orchestra_redirect_update' : 'seo_aeo_orchestra_redirect_add',
                    nonce: seoAeoOrchestra.nonce,
                    source_path: $modal.find('#orch-redirect-source').val(),
                    target_url:  $modal.find('#orch-redirect-target').val(),
                    redirect_type: $modal.find('#orch-redirect-type').val(),
                    is_regex: $modal.find('#orch-redirect-is-regex').prop('checked') ? 1 : 0,
                    is_active: $modal.find('#orch-redirect-is-active').prop('checked') ? 1 : 0,
                    notes: $modal.find('#orch-redirect-notes').val()
                };
                if (isEdit) data.id = r.id;
                $btn.prop('disabled', true).text('Salvo…');
                $.post(ajaxurl, data).done(function(resp) {
                    if (!resp || !resp.success) {
                        $modal.find('#orch-redirect-modal-status').html('<div class="orch-cannibal-error">' + escapeHtml(resp && (resp.error || resp.message) || 'Errore') + '</div>');
                        $btn.prop('disabled', false).text('Salva');
                        return;
                    }
                    $modal.remove();
                    loadRedirectList($('#orch-redirect-search').val());
                });
            });
        }

        function loadRedirect404Log() {
            $.post(ajaxurl, {
                action: 'seo_aeo_orchestra_redirect_404_log',
                nonce: seoAeoOrchestra.nonce
            }).done(function(resp) {
                if (!resp || !resp.success) return;
                var items = resp.items || [];
                if (!items.length) {
                    $('#orch-redirect-404-wrap').html('<div class="orch3-muted" style="margin-top:12px; padding:12px; background:#f0fdf4; border-radius:6px;">🎉 Nessun 404 recente. Il sito è in salute.</div>');
                    return;
                }
                var rows = items.map(function(it) {
                    return '<tr>' +
                             '<td><code>' + escapeHtml(it.request_path) + '</code></td>' +
                             '<td style="text-align:right;">' + parseInt(it.hits, 10) + '</td>' +
                             '<td>' + escapeHtml((it.last_seen_at || '').substring(0, 16).replace('T', ' ')) + '</td>' +
                             '<td>' +
                               '<button class="orch3-btn orch3-btn-primary orch3-btn-sm orch-redirect-from-404-btn" data-path="' + escapeHtml(it.request_path) + '">Crea →</button> ' +
                               '<button class="orch3-btn orch3-btn-ghost orch3-btn-sm orch-redirect-ai-suggest-btn" data-path="' + escapeHtml(it.request_path) + '" title="L\'AI sceglie il target redirect più semantico (3 crediti)">🤖 Suggerisci con AI</button>' +
                             '</td>' +
                           '</tr>';
                }).join('');
                $('#orch-redirect-404-wrap').html(
                    '<h4 style="margin:18px 0 8px; font-size:14px;">📋 404 recenti (' + items.length + ')</h4>' +
                    '<table class="orch-redirect-table"><thead><tr><th>Path richiesto</th><th style="text-align:right;">Hits</th><th>Ultimo</th><th>Azioni</th></tr></thead><tbody>' + rows + '</tbody></table>' +
                    '<div class="orch3-muted" style="font-size:11px; margin-top:6px;">🤖 Suggerisci con AI: l\'AI sceglie il redirect più sensato analizzando il path 404 + le pagine pubblicate del sito (3 crediti).</div>'
                );
                $('.orch-redirect-from-404-btn').on('click', function() {
                    var path = $(this).data('path');
                    openRedirectModal({source_path: path, target_url: '/', redirect_type: 301, is_regex: 0, is_active: 1, notes: 'Da 404 log'}, false);
                });
                $('.orch-redirect-ai-suggest-btn').on('click', function() {
                    var $btn = $(this);
                    var path = $btn.data('path');
                    var origLabel = $btn.html();
                    $btn.prop('disabled', true).html('<span class="rv-spinner"></span> l\'AI analizza…');
                    $.post(ajaxurl, {
                        action: 'seo_aeo_orchestra_redirect_suggest_target',
                        nonce: seoAeoOrchestra.nonce,
                        request_path: path
                    }).done(function(resp) {
                        $btn.prop('disabled', false).html(origLabel);
                        if (!resp || resp.error || !resp.success) {
                            alert('Errore AI: ' + ((resp && (resp.message || resp.error || resp.detail)) || 'sconosciuto'));
                            return;
                        }
                        showAiSuggestionModal(path, resp);
                    }).fail(function(xhr) {
                        $btn.prop('disabled', false).html(origLabel);
                        alert('Errore rete (' + xhr.status + ')');
                    });
                });
            });
        }

        function showAiSuggestionModal(requestPath, resp) {
            $('#orch-redirect-ai-modal').remove();

            var confColors = { high: '#10b981', medium: '#f59e0b', low: '#94a3b8' };
            var confLabels = { high: 'Alta confidenza', medium: 'Media confidenza', low: 'Bassa confidenza' };
            var confColor = confColors[resp.confidence] || '#94a3b8';
            var confLabel = confLabels[resp.confidence] || 'Confidenza sconosciuta';

            var bodyHtml = '';
            if (resp.suggested_target) {
                bodyHtml =
                    '<div class="orch-cannibal-fix-banner" style="margin-bottom:14px;">' +
                      '<span>🤖</span>' +
                      '<span><strong>L\'AI ha scelto un target.</strong> Qui sotto la proposta. Click "Crea redirect" per applicare.</span>' +
                    '</div>' +
                    '<div style="padding:12px 14px; background:#f8fafc; border-radius:6px; border-left:3px solid ' + confColor + ';">' +
                      '<div style="font-size:11px; color:' + confColor + '; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">' + confLabel + '</div>' +
                      '<div style="font-size:12px; color:#475569; margin-bottom:8px;">' +
                        '<code style="background:#fff; border:1px solid #e2e8f0; padding:2px 6px; border-radius:3px;">' + escapeHtml(requestPath) + '</code>' +
                        ' <span style="color:#0a0e27; font-size:14px; margin:0 6px;">→</span>' +
                        '<br><code style="background:#fff; border:1px solid #e2e8f0; padding:2px 6px; border-radius:3px; margin-top:6px; display:inline-block;">' + escapeHtml(resp.suggested_target) + '</code>' +
                      '</div>' +
                      (resp.reasoning ? '<div style="font-size:12px; color:#64748b; font-style:italic; margin-top:8px; line-height:1.5;">' + escapeHtml(resp.reasoning) + '</div>' : '') +
                    '</div>';
            } else {
                bodyHtml =
                    '<div class="orch-native-warn">' +
                      '<div class="orch-native-warn-head">⚠️ Nessuna pagina abbastanza pertinente</div>' +
                      '<div>L\'AI non ha trovato una pagina pubblicata con match semantico sufficiente per redirezionare <code>' + escapeHtml(requestPath) + '</code>.</div>' +
                      (resp.reasoning ? '<div style="margin-top:6px; font-style:italic; font-size:12px;">' + escapeHtml(resp.reasoning) + '</div>' : '') +
                      '<div style="margin-top:8px; font-size:12px;">Suggerimenti:<ul style="margin:4px 0 0 18px;"><li>Crea il redirect a mano scegliendo tu il target</li><li>Lascia il 404 (legittimo se il contenuto è stato eliminato)</li><li>Crea una nuova pagina rilevante e poi rilancia la suggestion</li></ul></div>' +
                    '</div>';
            }

            var creditsLine = resp.credits_consumed
                ? '<div class="orch3-muted" style="font-size:11px; margin-top:8px;">Hai consumato ' + parseInt(resp.credits_consumed,10) + ' crediti per questa analisi.</div>'
                : '';

            var html =
                '<div id="orch-redirect-ai-modal" class="orch-modal-backdrop">' +
                  '<div class="orch-modal-window" style="max-width:600px;">' +
                    '<div class="orch-modal-head">' +
                      '<h3>🤖 Suggerimento AI per redirect</h3>' +
                      '<button type="button" class="orch-modal-close" aria-label="Chiudi">×</button>' +
                    '</div>' +
                    '<div class="orch-modal-body">' +
                      bodyHtml +
                      creditsLine +
                    '</div>' +
                    '<div class="orch-modal-foot">' +
                      '<button type="button" class="orch3-btn orch3-btn-ghost orch-modal-cancel">Chiudi</button>' +
                      (resp.suggested_target ? '<button type="button" class="orch3-btn orch3-btn-primary" id="orch-redirect-ai-create-btn">✓ Crea redirect 301</button>' : '') +
                    '</div>' +
                  '</div>' +
                '</div>';

            var $modal = $(html).appendTo('body');
            $modal.find('.orch-modal-close, .orch-modal-cancel').on('click', function() { $modal.remove(); });
            $modal.find('#orch-redirect-ai-create-btn').on('click', function() {
                $modal.remove();
                openRedirectModal({
                    source_path: requestPath,
                    target_url: resp.suggested_target,
                    redirect_type: resp.redirect_type || 301,
                    is_regex: 0,
                    is_active: 1,
                    notes: 'Suggerito da AI · ' + (resp.confidence || 'low') + ' confidence'
                }, false);
            });
        }

        function escapeHtml(s) {
            return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
        }
    });
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>

<?php ob_start(); ?>
(function () {
    'use strict';

    // 3.35.54: cross-industry rotated placeholders for the Differenzianti title input.
    // Random pick on each list-item creation. Avoids Solaris-specific examples.
    var DIFF_TITLE_PLACEHOLDERS = [
        'Es. 15 anni di esperienza nel settore',
        'Es. Certificazione ISO 9001 dal 2010',
        'Es. Distribuzione in oltre 30 paesi',
        'Es. Brevetto esclusivo registrato',
        'Es. Premio innovazione 2024',
        'Es. Garanzia estesa 10 anni',
        'Es. Filiale produttiva interna in Europa',
        'Es. Team certificato dalla casa madre',
        'Es. Prodotti 100% tracciabili e sostenibili',
        'Es. Network di partner in tutto il Paese',
        'Es. R&D interno con 5 brevetti depositati',
        'Es. Tempo di risposta entro 2 ore lavorative'
    ];

    var ajaxUrl = (window.seoAeoOrchestra && window.seoAeoOrchestra.ajaxUrl) || window.ajaxurl;
    var nonce = (window.seoAeoOrchestra && window.seoAeoOrchestra.nonce) || '';

    // ============ Tab switching ============
    var tabs = document.querySelectorAll('.orch-native-tab');
    var panels = document.querySelectorAll('.orch-native-tab-panel');
    tabs.forEach(function (t) {
        t.addEventListener('click', function () {
            var target = t.getAttribute('data-orch-tab-target');
            tabs.forEach(function (x) { x.classList.remove('is-active'); x.setAttribute('aria-selected', 'false'); });
            panels.forEach(function (p) {
                if (p.getAttribute('data-orch-tab') === target) {
                    p.removeAttribute('hidden');
                    p.classList.add('is-active');
                } else {
                    p.setAttribute('hidden', '');
                    p.classList.remove('is-active');
                }
            });
            t.classList.add('is-active');
            t.setAttribute('aria-selected', 'true');

            if (target === 'content' && !window.__orchIdentityLoaded) {
                window.__orchIdentityLoaded = true;
                identityLoad();
            }
        });
    });

    // ============ DOM refs ============
    var $form         = document.getElementById('orch-identity-form');
    var $loading      = document.getElementById('orch-identity-loading');
    var $status       = document.getElementById('orch-identity-status');
    var $btnSave      = document.getElementById('orch-identity-save');
    var $btnFlush     = document.getElementById('orch-identity-flush');

    // Per-output grids
    var $sitemapGrid  = document.getElementById('orch-sitemap-pt-grid');
    var $llmsfullGrid = document.getElementById('orch-llmsfull-pt-grid');

    // Schema
    var $schemaDetection = document.getElementById('orch-schema-detection');

    // Featured pages refs
    var $featSearch   = document.getElementById('orch-featured-search');
    var $featResults  = document.getElementById('orch-featured-results');
    var $featChips    = document.getElementById('orch-featured-chips');
    var $featCounter  = document.getElementById('orch-featured-counter');
    var $featMax      = document.getElementById('orch-featured-max');

    // Footer-bar
    var $respectNoindex = document.getElementById('orch-respect-noindex');

    var FEATURED_MAX  = 10;
    var featuredState = []; // array of {id, title, url, type}
    var detectedTypes = []; // [{slug,label}]

    // ============ Utilities ============
    function setStatus(kind, msg) {
        if (!$status) return;
        $status.className = 'orch-identity-status-bar is-' + kind;
        $status.textContent = msg;
        if (kind === 'success') {
            setTimeout(function () { $status.className = 'orch-identity-status-bar'; $status.textContent = ''; }, 4000);
        }
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function ajaxPost(action, extra) {
        var fd = new FormData();
        fd.append('action', 'seo_aeo_orchestra_' + action);
        fd.append('nonce', nonce);
        Object.keys(extra || {}).forEach(function (k) { fd.append(k, extra[k]); });
        return fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
            .then(function (r) { return r.json(); });
    }

    // ============ Identity list builder (differentiators, territories, use_cases) ============
    function makeListItem(kind, value) {
        var div = document.createElement('div');
        div.className = 'orch-identity-list-item';
        div.setAttribute('data-orch-list-item', kind);
        var inner = '<div class="orch-identity-list-item-fields">';
        if (kind === 'territories') {
            inner += '<input type="text" data-orch-field="value" value="' + escapeHtml(value) + '" placeholder="es. Italia, Spagna, Canarie" maxlength="120" />';
        } else {
            var t = (value && value.title) || '';
            var d = (value && value.description) || '';
            var diffPh = (typeof DIFF_TITLE_PLACEHOLDERS !== 'undefined') ? DIFF_TITLE_PLACEHOLDERS[Math.floor(Math.random() * DIFF_TITLE_PLACEHOLDERS.length)] : 'Titolo del punto di forza';
            inner += '<input type="text" data-orch-field="title" value="' + escapeHtml(t) + '" placeholder="' + escapeHtml(diffPh) + '" maxlength="140" />';
            inner += '<textarea data-orch-field="description" rows="2" placeholder="1-2 frasi di contesto naturale" maxlength="500">' + escapeHtml(d) + '</textarea>';
        }
        inner += '</div>';
        inner += '<button type="button" class="orch-identity-list-item-remove" title="Rimuovi" aria-label="Rimuovi">×</button>';
        div.innerHTML = inner;
        div.querySelector('.orch-identity-list-item-remove').addEventListener('click', function () {
            div.parentNode.removeChild(div);
        });
        return div;
    }

    document.querySelectorAll('[data-orch-list-add]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var kind = btn.getAttribute('data-orch-list-add');
            var list = document.querySelector('[data-orch-list="' + kind + '"]');
            if (!list) return;
            var max = parseInt(list.getAttribute('data-orch-list-max') || '5', 10);
            var current = list.querySelectorAll('.orch-identity-list-item').length;
            if (current >= max) {
                setStatus('error', 'Massimo ' + max + ' voci per questa sezione.');
                return;
            }
            var emptyVal = (kind === 'territories') ? '' : { title: '', description: '' };
            list.appendChild(makeListItem(kind, emptyVal));
        });
    });

    function readListItems(kind) {
        var list = document.querySelector('[data-orch-list="' + kind + '"]');
        if (!list) return [];
        var items = [];
        list.querySelectorAll('.orch-identity-list-item').forEach(function (el) {
            if (kind === 'territories') {
                var v = el.querySelector('[data-orch-field="value"]').value.trim();
                if (v) items.push(v);
            } else {
                var t = el.querySelector('[data-orch-field="title"]').value.trim();
                var d = el.querySelector('[data-orch-field="description"]').value.trim();
                if (t || d) items.push({ title: t, description: d });
            }
        });
        return items;
    }

    function fillListItems(kind, values) {
        var list = document.querySelector('[data-orch-list="' + kind + '"]');
        if (!list) return;
        list.innerHTML = '';
        (values || []).forEach(function (v) {
            list.appendChild(makeListItem(kind, v));
        });
        if (list.children.length === 0) {
            var emptyVal = (kind === 'territories') ? '' : { title: '', description: '' };
            list.appendChild(makeListItem(kind, emptyVal));
        }
    }

    // ============ Per-output post type grids ============
    function renderPostTypeGrid(gridEl, dataAttr, selectedSlugs) {
        if (!gridEl || !Array.isArray(detectedTypes)) return;
        if (detectedTypes.length === 0) {
            gridEl.innerHTML = '<span class="orch3-muted" style="font-size:12px;">Nessun tipo di contenuto pubblico detected.</span>';
            return;
        }
        var sel = (selectedSlugs || []).reduce(function (acc, k) { acc[k] = true; return acc; }, {});
        gridEl.innerHTML = detectedTypes.map(function (pt) {
            var checked = sel[pt.slug] ? 'checked' : '';
            return '<label><input type="checkbox" data-' + dataAttr + '="' + escapeHtml(pt.slug) + '" ' + checked + '><span>' + escapeHtml(pt.label) + '</span><span class="orch-filt-pt-slug">' + escapeHtml(pt.slug) + '</span></label>';
        }).join('');
    }

    function readPostTypeGrid(gridEl, dataAttr) {
        if (!gridEl) return [];
        var out = [];
        gridEl.querySelectorAll('input[data-' + dataAttr + ']:checked').forEach(function (cb) {
            out.push(cb.getAttribute('data-' + dataAttr));
        });
        return out;
    }

    // ============ Schema detection rendering ============
    function renderSchemaDetection(detection, recommended, resolved) {
        if (!$schemaDetection) return;
        var html = '';
        if (!detection || detection.length === 0) {
            html += '<div class="orch-schema-detection-empty">'
                +  '✓ Nessun altro plugin Schema.org rilevato sul sito. AEO Orchestra può essere il sole provider in modalità <strong>Replace</strong>.'
                +  '</div>';
        } else {
            html += '<p class="orch3-muted" style="font-size:12px; margin:0 0 6px;">Provider Schema.org rilevati:</p>';
            detection.forEach(function (p) {
                html += '<div class="orch-schema-detection-line">'
                    +  '<strong>' + escapeHtml(p.plugin) + '</strong>'
                    +  '<span class="orch-schema-detection-types">'
                    +  (p.types_emitted || []).map(function (t) {
                        return '<span class="orch-schema-detection-type">' + escapeHtml(t) + '</span>';
                       }).join('')
                    +  '</span>'
                    +  '</div>';
            });
        }
        if (recommended) {
            var modeLabel = { replace: 'Replace', augment: 'Augment', off: 'Off', auto: 'Auto' }[recommended] || recommended;
            html += '<div class="orch-schema-recommendation">'
                +  '💡 <strong>Modalità raccomandata: ' + escapeHtml(modeLabel) + '</strong>'
                +  (resolved && resolved !== recommended ? ' (attualmente attiva: <strong>' + escapeHtml(resolved) + '</strong>)' : '')
                +  '</div>';
        }
        $schemaDetection.innerHTML = html;
    }

    function renderSitemapDetection(detection, takeoverRecommended) {
        var el = document.getElementById('orch-sitemap-detection');
        if (!el) return;
        var html = '';
        // Filter out WP Core for the "competitor" list (informational only)
        var competitors = (detection || []).filter(function (p) { return p.plugin !== 'WordPress Core'; });
        if (competitors.length === 0) {
            html += '<div class="orch-schema-detection-empty">'
                +  '✓ Nessun plugin SEO concorrente con sitemap rilevato. AEO Orchestra può servire /sitemap.xml senza conflitti.'
                +  '</div>';
        } else {
            html += '<p class="orch3-muted" style="font-size:12px; margin:0 0 6px;">Plugin SEO con sitemap rilevati:</p>';
            competitors.forEach(function (p) {
                html += '<div class="orch-schema-detection-line">'
                    +  '<strong>' + escapeHtml(p.plugin) + '</strong>'
                    +  ' — <code style="font-size:11px;">' + escapeHtml(p.sitemap_url) + '</code>'
                    +  '</div>';
            });
            html += '<div class="orch-schema-recommendation">'
                +  '⚠️ <strong>Conflitto possibile:</strong> più plugin servono sitemap. Attiva "Sostituisci sitemap esistente" per dare la priorità ad AEO Orchestra.'
                +  '</div>';
        }
        // WP Core sitemap info
        var wpCore = (detection || []).filter(function (p) { return p.plugin === 'WordPress Core'; });
        if (wpCore.length) {
            html += '<p class="orch3-muted" style="font-size:11px; margin:6px 0 0;">'
                +  'WordPress core sitemap continua ad esistere a <code>' + escapeHtml(wpCore[0].sitemap_url) + '</code> — non confliggono i due URL.'
                +  '</p>';
        }
        el.innerHTML = html;
    }

    // ============ A.1: Organization defaults render/collect ============
    function fillOrgDefaults(d) {
        var setVal = function (id, v) { var el = document.getElementById(id); if (el) el.value = v == null ? '' : String(v); };
        setVal('orch-org-logo-url', d.logo_url);
        setVal('orch-org-legal-name', d.legal_name);
        setVal('orch-org-founding-date', d.founding_date);
        setVal('orch-org-vat', d.vat);
        var addr = d.address || {};
        setVal('orch-org-addr-street', addr.street_address);
        setVal('orch-org-addr-cap', addr.postal_code);
        setVal('orch-org-addr-locality', addr.locality);
        setVal('orch-org-addr-region', addr.region);
        setVal('orch-org-addr-country', addr.country);
        var cp = d.contact_point || {};
        setVal('orch-org-cp-tel', cp.telephone);
        setVal('orch-org-cp-email', cp.email);
        var sel = document.getElementById('orch-org-cp-type');
        if (sel) sel.value = cp.contact_type || 'customer support';
        setVal('orch-org-cp-langs', cp.available_languages);
        setVal('orch-org-cp-area', cp.area_served);
        // sameAs
        var sameAs = Array.isArray(d.same_as) ? d.same_as : [];
        renderSameAsList(sameAs);
    }

    function renderSameAsList(urls) {
        var wrap = document.getElementById('orch-org-same-as-list');
        if (!wrap) return;
        var presets = ['Facebook', 'LinkedIn', 'Instagram', 'YouTube', 'X (Twitter)', 'TikTok', 'GitHub', 'Other'];
        wrap.innerHTML = '';
        (urls.length ? urls : ['']).forEach(function (url) {
            var row = document.createElement('div');
            row.className = 'orch-org-same-as-row';
            row.innerHTML = ''
                + '<select><option value="">— platform —</option>' + presets.map(function (p) { return '<option>' + escapeHtml(p) + '</option>'; }).join('') + '</select>'
                + '<input type="url" placeholder="https://..." value="' + escapeHtml(url) + '" />'
                + '<button type="button" class="orch-org-same-as-row-remove" aria-label="Rimuovi">×</button>';
            wrap.appendChild(row);
            // Auto-select platform from URL
            var input = row.querySelector('input');
            var select = row.querySelector('select');
            if (url) {
                var detected = detectSocialPlatform(url);
                if (detected) select.value = detected;
            }
            input.addEventListener('input', function () {
                var v = input.value;
                if (v && !select.value) {
                    var p = detectSocialPlatform(v);
                    if (p) select.value = p;
                }
            });
            row.querySelector('.orch-org-same-as-row-remove').addEventListener('click', function () { row.remove(); });
        });
    }

    function detectSocialPlatform(url) {
        var l = (url || '').toLowerCase();
        if (l.indexOf('facebook.com') >= 0) return 'Facebook';
        if (l.indexOf('linkedin.com') >= 0) return 'LinkedIn';
        if (l.indexOf('instagram.com') >= 0) return 'Instagram';
        if (l.indexOf('youtube.com') >= 0 || l.indexOf('youtu.be') >= 0) return 'YouTube';
        if (l.indexOf('twitter.com') >= 0 || l.indexOf('x.com') >= 0) return 'X (Twitter)';
        if (l.indexOf('tiktok.com') >= 0) return 'TikTok';
        if (l.indexOf('github.com') >= 0) return 'GitHub';
        return '';
    }

    // Add same-as button
    var $sameAsAdd = document.getElementById('orch-org-same-as-add');
    if ($sameAsAdd) $sameAsAdd.addEventListener('click', function () {
        var wrap = document.getElementById('orch-org-same-as-list');
        if (!wrap) return;
        var current = [];
        wrap.querySelectorAll('input[type="url"]').forEach(function (i) { if (i.value) current.push(i.value); });
        current.push('');
        renderSameAsList(current);
    });

    function collectOrgDefaults() {
        var get = function (id) { var el = document.getElementById(id); return el ? el.value.trim() : ''; };
        var sameAs = [];
        document.querySelectorAll('#orch-org-same-as-list input[type="url"]').forEach(function (i) {
            var v = i.value.trim();
            if (v) sameAs.push(v);
        });
        return {
            logo_url: get('orch-org-logo-url'),
            legal_name: get('orch-org-legal-name'),
            founding_date: get('orch-org-founding-date'),
            vat: get('orch-org-vat'),
            address: {
                street_address: get('orch-org-addr-street'),
                postal_code:    get('orch-org-addr-cap'),
                locality:       get('orch-org-addr-locality'),
                region:         get('orch-org-addr-region'),
                country:        get('orch-org-addr-country').toUpperCase(),
            },
            contact_point: {
                telephone:    get('orch-org-cp-tel'),
                email:        get('orch-org-cp-email'),
                contact_type: get('orch-org-cp-type'),
                available_languages: get('orch-org-cp-langs'),
                area_served:  get('orch-org-cp-area'),
            },
            same_as: sameAs,
        };
    }

    // ============ A.2: CPT override render/collect ============
    function renderCptOverrides(cpts, current, types) {
        var tbody = document.getElementById('orch-cpt-override-tbody');
        if (!tbody) return;
        if (!cpts || cpts.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2" class="orch3-muted">Nessun custom post type detected.</td></tr>';
            return;
        }
        var typeOpts = '<option value="auto">Auto</option>' + types.map(function (t) { return '<option value="' + escapeHtml(t) + '">' + escapeHtml(t) + '</option>'; }).join('');
        tbody.innerHTML = cpts.map(function (cpt) {
            var sel = current[cpt.slug] || 'auto';
            return '<tr>'
                + '<td><strong>' + escapeHtml(cpt.label) + '</strong> <span class="orch3-muted" style="font-size:11px;">(' + escapeHtml(cpt.slug) + ')</span></td>'
                + '<td><select data-orch-cpt-override="' + escapeHtml(cpt.slug) + '">' + typeOpts.replace('value="' + escapeHtml(sel) + '"', 'value="' + escapeHtml(sel) + '" selected') + '</select></td>'
                + '</tr>';
        }).join('');
    }

    function collectCptOverrides() {
        var out = {};
        document.querySelectorAll('[data-orch-cpt-override]').forEach(function (sel) {
            var slug = sel.getAttribute('data-orch-cpt-override');
            var v = sel.value;
            if (slug && v) out[slug] = v;
        });
        return out;
    }

    // ============ A.3: Breadcrumb settings ============
    // ============ 3.35.59: Output <head> settings render/collect (6 panels) ============

    function fillHeadSettings(hs) {
        if (!hs) return;
        var c = hs.canonical || {};
        document.querySelectorAll('input[name="orch-canonical-strategy"]').forEach(function (r) { r.checked = (r.value === (c.strategy || 'auto')); });
        var setVal = function (id, v) { var el = document.getElementById(id); if (el) el.value = v == null ? '' : String(v); };
        setVal('orch-canonical-domain', c.canonical_domain);
        setVal('orch-canonical-custom-prefix', c.custom_prefix);
        document.querySelectorAll('input[name="orch-canonical-trailing"]').forEach(function (r) { r.checked = (r.value === (c.trailing_slash || 'wp_default')); });
        var $pf = document.getElementById('orch-canonical-paginated-first'); if ($pf) $pf.checked = !!c.paginated_first;
        setVal('orch-canonical-disabled-pts', (c.disabled_post_types || []).join('\n'));

        var t = hs.title || {};
        document.querySelectorAll('input[name="orch-title-sep"]').forEach(function (r) { r.checked = (r.value === (t.separator || '—')); });
        setVal('orch-title-arc-category', (t.archives || {}).category);
        setVal('orch-title-arc-tag', (t.archives || {}).tag);
        setVal('orch-title-arc-author', (t.archives || {}).author);
        setVal('orch-title-arc-search', (t.archives || {}).search);
        var $tt60 = document.getElementById('orch-title-trim-60'); if ($tt60) $tt60.checked = !!t.trim_at_60;
        renderHeadCptTable('orch-title-cpt-tbody', 'orch-title-cpt', detectedTypes, t.post_types || {}, function (slug) { return '{{title}} {{separator}} {{site_name}}'; });

        var md = hs.meta_desc || {};
        renderMetaDescPriority(md.priority || ['orch', 'yoast', 'rankmath', 'aioseo', 'acf', 'excerpt', 'content']);
        setVal('orch-metadesc-min', md.min_len || 120);
        setVal('orch-metadesc-max', md.max_len || 160);
        document.querySelectorAll('input[name="orch-metadesc-trunc"]').forEach(function (r) { r.checked = (r.value === (md.truncation || 'word_boundary')); });
        setVal('orch-metadesc-acf-field', md.acf_field);
        setVal('orch-metadesc-archive-pattern', md.archive_pattern);

        var og = hs.og || {};
        setVal('orch-og-image-fallback', og.image_fallback);
        setVal('orch-og-locale-override', og.locale_override);
        setVal('orch-og-site-name-override', og.site_name_override);
        var $aa = document.getElementById('orch-og-article-author'); if ($aa) $aa.checked = og.emit_article_author !== false;
        var $ap = document.getElementById('orch-og-article-published'); if ($ap) $ap.checked = og.emit_article_published !== false;
        var $as = document.getElementById('orch-og-article-section'); if ($as) $as.checked = og.emit_article_section !== false;
        var $at = document.getElementById('orch-og-article-tag'); if ($at) $at.checked = og.emit_article_tag !== false;
        renderHeadCptDropdown('orch-og-cpt-tbody', 'orch-og-cpt', detectedTypes, og.og_type_per_cpt || {}, ['website', 'article', 'product', 'video.other', 'profile', 'business.business']);

        var tw = hs.twitter || {};
        document.querySelectorAll('input[name="orch-twitter-card"]').forEach(function (r) { r.checked = (r.value === (tw.card_type || 'summary_large_image')); });
        setVal('orch-twitter-site', tw.site_handle);
        document.querySelectorAll('input[name="orch-twitter-creator-mode"]').forEach(function (r) { r.checked = (r.value === (tw.creator_mode || 'auto')); });
        setVal('orch-twitter-creator-handle', tw.creator_handle);

        var rb = hs.robots || {};
        renderRobotsCptTable(detectedTypes, rb.post_types || {});
        var arc = rb.archives || {};
        document.querySelectorAll('[data-orch-arc-robots]').forEach(function (cb) {
            var name = cb.getAttribute('data-orch-arc-robots');
            var key  = cb.getAttribute('data-key');
            cb.checked = !!(arc[name] && arc[name][key]);
        });
        var rules = rb.rules || {};
        var $pag = document.getElementById('orch-robots-paginated'); if ($pag) $pag.checked = !!rules.noindex_paginated;
        var $lw  = document.getElementById('orch-robots-low-words'); if ($lw) $lw.checked = !!rules.noindex_low_words;
        setVal('orch-robots-low-words-threshold', rules.low_words_threshold || 300);
    }

    function renderHeadCptTable(tbodyId, prefix, cpts, current, defaultTplFn) {
        var tbody = document.getElementById(tbodyId);
        if (!tbody) return;
        if (!cpts || cpts.length === 0) { tbody.innerHTML = '<tr><td colspan="2" class="orch3-muted">Nessun CPT detected.</td></tr>'; return; }
        tbody.innerHTML = cpts.map(function (pt) {
            var v = current[pt.slug] != null ? current[pt.slug] : (defaultTplFn ? defaultTplFn(pt.slug) : '');
            return '<tr><td><strong>' + escapeHtml(pt.label) + '</strong> <span class="orch3-muted" style="font-size:11px;">(' + escapeHtml(pt.slug) + ')</span></td>'
                +  '<td><input type="text" data-' + prefix + '="' + escapeHtml(pt.slug) + '" value="' + escapeHtml(v) + '" /></td></tr>';
        }).join('');
    }

    function renderHeadCptDropdown(tbodyId, prefix, cpts, current, options) {
        var tbody = document.getElementById(tbodyId);
        if (!tbody) return;
        if (!cpts || cpts.length === 0) { tbody.innerHTML = '<tr><td colspan="2" class="orch3-muted">Nessun CPT detected.</td></tr>'; return; }
        tbody.innerHTML = cpts.map(function (pt) {
            var v = current[pt.slug] || '';
            var opts = options.map(function (o) { return '<option value="' + escapeHtml(o) + '"' + (o === v ? ' selected' : '') + '>' + escapeHtml(o) + '</option>'; }).join('');
            return '<tr><td><strong>' + escapeHtml(pt.label) + '</strong> <span class="orch3-muted" style="font-size:11px;">(' + escapeHtml(pt.slug) + ')</span></td>'
                +  '<td><select data-' + prefix + '="' + escapeHtml(pt.slug) + '"><option value="">— auto —</option>' + opts + '</select></td></tr>';
        }).join('');
    }

    function renderRobotsCptTable(cpts, current) {
        var tbody = document.getElementById('orch-robots-cpt-tbody');
        if (!tbody) return;
        if (!cpts || cpts.length === 0) { tbody.innerHTML = '<tr><td colspan="3" class="orch3-muted">Nessun CPT detected.</td></tr>'; return; }
        tbody.innerHTML = cpts.map(function (pt) {
            var cfg = current[pt.slug] || { index: true, follow: true };
            return '<tr><td><strong>' + escapeHtml(pt.label) + '</strong> <span class="orch3-muted" style="font-size:11px;">(' + escapeHtml(pt.slug) + ')</span></td>'
                +  '<td><input type="checkbox" data-orch-robots-cpt="' + escapeHtml(pt.slug) + '" data-key="index"' + (cfg.index !== false ? ' checked' : '') + '></td>'
                +  '<td><input type="checkbox" data-orch-robots-cpt="' + escapeHtml(pt.slug) + '" data-key="follow"' + (cfg.follow !== false ? ' checked' : '') + '></td></tr>';
        }).join('');
    }

    function renderMetaDescPriority(priority) {
        var wrap = document.getElementById('orch-metadesc-priority-list');
        if (!wrap) return;
        var labels = { orch: 'AEO Orchestra meta', yoast: 'Yoast SEO meta', rankmath: 'RankMath meta', aioseo: 'AIOSEO meta', acf: 'ACF custom field', excerpt: 'Post excerpt', content: 'Primi N caratteri del content' };
        wrap.innerHTML = priority.map(function (k, i) {
            return '<div class="orch-metadesc-priority-row" data-orch-priority="' + escapeHtml(k) + '">'
                +  '<span class="orch-priority-rank">' + (i + 1) + '.</span>'
                +  '<input type="checkbox" checked disabled />'
                +  '<span>' + escapeHtml(labels[k] || k) + '</span>'
                +  '</div>';
        }).join('');
    }

    // ============ 3.35.60 — Live config summary ============

    function _summaryRow(rowKey, status, value) {
        var row = document.querySelector('[data-orch-summary-row="' + rowKey + '"]');
        if (!row) return;
        var icon = row.querySelector('.orch-summary-icon');
        var val = row.querySelector('.orch-summary-value');
        if (icon) {
            icon.setAttribute('data-orch-summary-status', status);
            icon.textContent = status === 'ok' ? '✓' : (status === 'warn' ? '⚠' : (status === 'error' ? '✗' : '…'));
        }
        if (val) val.innerHTML = value;
        // 3.35.60.2: toggle is-warn class for Fix pill visibility
        row.classList.toggle('is-warn', status === 'warn' || status === 'error');
        // Append Fix pill (idempotent — only once)
        if (!row.querySelector('.orch-fix-pill')) {
            var pill = document.createElement('span');
            pill.className = 'orch-fix-pill';
            pill.textContent = 'Fix ⤴';
            pill.setAttribute('aria-hidden', 'true');
            row.appendChild(pill);
        }
    }

    function _subPanelConfigList(panelId, items) {
        var wrap = document.querySelector('[data-orch-current-config="' + panelId + '"] .orch-current-config-list');
        if (!wrap) return;
        if (!items || items.length === 0) {
            wrap.innerHTML = '<li class="orch3-muted">Configurazione default attiva</li>';
            return;
        }
        wrap.innerHTML = items.map(function (it) { return '<li>' + it + '</li>'; }).join('');
    }

    // ============ 3.35.60.2 — Click-to-fix jump ============

    var _FIX_FIELD_RESOLVERS = {
        canonical: function (status) {
            if (status === 'warn') {
                var sel = document.querySelector('input[name="orch-canonical-strategy"]:checked');
                var v = sel ? sel.value : 'auto';
                if (v === 'force_same_domain') return '#orch-canonical-domain';
                if (v === 'custom') return '#orch-canonical-custom-prefix';
            }
            return null;
        },
        og: function (status) {
            if (status === 'warn') return '#orch-og-image-fallback';
            return null;
        },
        twitter: function (status) {
            if (status === 'warn') return '#orch-twitter-site';
            return null;
        },
        title:     function () { return null; },
        meta_desc: function () { return null; },
        robots:    function (status) {
            if (status === 'error') return '#orch-robots-cpt-tbody';
            return null;
        }
    };

    var _SUBPANEL_BY_ROW = {
        canonical: 'orch-head-canonical',
        title:     'orch-head-title',
        meta_desc: 'orch-head-metadesc',
        og:        'orch-head-og',
        twitter:   'orch-head-twitter',
        robots:    'orch-head-robots'
    };

    function _jumpToFix(rowKey) {
        var headPanel = document.querySelector('[data-orch-output="head"]');
        if (headPanel && !headPanel.open) headPanel.open = true;

        var subpanelId = _SUBPANEL_BY_ROW[rowKey];
        if (!subpanelId) return;
        var subpanel = document.getElementById(subpanelId);
        if (!subpanel) return;

        // Open sub-panel + Personalizza
        subpanel.open = true;
        var personalizza = subpanel.querySelector('details.orch-advanced-options');
        if (personalizza) personalizza.open = true;

        // Get status from icon to decide pulse
        var row = document.querySelector('[data-orch-summary-row="' + rowKey + '"]');
        var status = 'ok';
        if (row) {
            var iconEl = row.querySelector('.orch-summary-icon');
            if (iconEl) status = iconEl.getAttribute('data-orch-summary-status') || 'ok';
        }

        // Resolve target field for warning, else just scroll to subpanel header
        var targetSel = (_FIX_FIELD_RESOLVERS[rowKey] || function () { return null; })(status);
        var target = targetSel ? document.querySelector(targetSel) : subpanel;
        if (!target) target = subpanel;

        // Defer to allow <details> open animation to finish
        setTimeout(function () {
            try {
                target.scrollIntoView({ block: 'center', behavior: 'smooth' });
            } catch (e) { /* older browsers */ }

            // Focus + pulse only on warnings/errors
            if (status === 'warn' || status === 'error') {
                if (targetSel && target.focus) {
                    setTimeout(function () { try { target.focus(); } catch (e) {} }, 320);
                }
                target.classList.add('orch-pulse-warn');
                setTimeout(function () { target.classList.remove('orch-pulse-warn'); }, 2100);
            }
        }, 220);
    }

    // Delegated click + keyboard handler on summary rows
    document.addEventListener('click', function (e) {
        var li = e.target.closest('[data-orch-summary-row]');
        if (!li) return;
        var rowKey = li.getAttribute('data-orch-summary-row');
        if (rowKey) _jumpToFix(rowKey);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        var li = e.target.closest('[data-orch-summary-row]');
        if (!li) return;
        e.preventDefault();
        var rowKey = li.getAttribute('data-orch-summary-row');
        if (rowKey) _jumpToFix(rowKey);
    });

    function renderHeadConfigSummary() {
        if (typeof collectHeadSettings !== 'function') return;
        var hs;
        try { hs = collectHeadSettings(); } catch (e) { return; }

        var c = hs.canonical || {};
        var canonical_status = 'ok';
        var canonical_value = '';
        if (c.strategy === 'force_same_domain') {
            if (!c.canonical_domain) { canonical_status = 'warn'; canonical_value = 'Force same-domain (dominio canonico mancante)'; }
            else canonical_value = 'Force same-domain → ' + escapeHtml(c.canonical_domain);
        } else if (c.strategy === 'custom') {
            if (!c.custom_prefix) { canonical_status = 'warn'; canonical_value = 'Custom prefix (prefisso mancante)'; }
            else canonical_value = 'Custom prefix → ' + escapeHtml(c.custom_prefix);
        } else {
            canonical_value = 'Auto (permalink WordPress)';
        }
        _summaryRow('canonical', canonical_status, canonical_value);
        var canonItems = [
            'Strategia: <strong>' + canonical_value + '</strong>',
            'Trailing slash: <strong>' + escapeHtml(c.trailing_slash === 'wp_default' ? 'Mantieni come WordPress' : (c.trailing_slash === 'with' ? 'Forza con /' : 'Forza senza /')) + '</strong>',
            'Pagine paginate puntano alla pag.1: ' + (c.paginated_first ? '<strong>✓</strong>' : '<strong>✗</strong>'),
            'CPT disabilitati: ' + ((c.disabled_post_types && c.disabled_post_types.length) ? '<strong>' + escapeHtml(c.disabled_post_types.join(', ')) + '</strong>' : '<em>nessuno</em>'),
        ];
        _subPanelConfigList('canonical', canonItems);

        var t = hs.title || {};
        var titleSepLabel = ({ '—': 'em-dash (—)', '|': 'pipe (|)', '·': 'middle dot (·)', '-': 'trattino (-)' })[t.separator] || t.separator || '—';
        var ptCount = Object.keys(t.post_types || {}).filter(function (k) { return (t.post_types || {})[k]; }).length;
        var titleStatus = ptCount > 0 ? 'ok' : 'ok';
        var titleValue = ptCount > 0 ? (ptCount + ' template configurati · separator ' + titleSepLabel) : ('Default WordPress · separator ' + titleSepLabel);
        _summaryRow('title', titleStatus, titleValue);
        _subPanelConfigList('title', [
            'Separator: <strong>' + escapeHtml(titleSepLabel) + '</strong>',
            'Template per CPT: <strong>' + ptCount + '</strong> configurati',
            'Trim a 60 caratteri: ' + (t.trim_at_60 ? '<strong>✓</strong>' : '<strong>✗</strong>'),
        ]);

        var md = hs.meta_desc || {};
        var prCount = (md.priority || []).length;
        var mdStatus = prCount > 0 ? 'ok' : 'warn';
        var mdValue = prCount > 0 ? ('Priorità: ' + escapeHtml((md.priority || []).slice(0, 3).join(' → ')) + (prCount > 3 ? '…' : '')) : 'Nessuna priority configurata';
        _summaryRow('meta_desc', mdStatus, mdValue);
        var mdTruncLabel = ({ word_boundary: 'taglio a parola + …', hard_cap: 'hard cap', sentence_boundary: 'taglio a frase' })[md.truncation] || md.truncation;
        _subPanelConfigList('metadesc', [
            'Source priority: <strong>' + escapeHtml((md.priority || []).join(' → ')) + '</strong>',
            'Lunghezza: <strong>' + (md.min_len || 120) + '–' + (md.max_len || 160) + '</strong> caratteri',
            'Truncation: <strong>' + escapeHtml(mdTruncLabel || 'word_boundary') + '</strong>',
        ]);

        var og = hs.og || {};
        var ogPtCount = Object.keys(og.og_type_per_cpt || {}).length;
        var ogStatus = og.image_fallback ? 'ok' : 'warn';
        var ogValue = (og.image_fallback ? 'OG image fallback ✓' : 'OG image fallback non caricato') + ' · ' + ogPtCount + ' og:type per CPT';
        _summaryRow('og', ogStatus, ogValue);
        var artTags = ['author', 'published', 'section', 'tag'].filter(function (k) { return og['emit_article_' + k]; }).map(function (k) { return '<code>' + k + '</code>'; }).join(', ');
        _subPanelConfigList('og', [
            'OG image fallback: ' + (og.image_fallback ? '<strong>✓ caricato</strong>' : '<em>non caricato</em>'),
            'og:type per CPT: <strong>' + ogPtCount + '</strong> configurati',
            'article:* tags: ' + (artTags || '<em>nessuno</em>'),
        ]);

        var tw = hs.twitter || {};
        var twStatus = tw.site_handle ? 'ok' : 'warn';
        var twValue = escapeHtml(tw.card_type || 'summary_large_image') + (tw.site_handle ? ' · ' + escapeHtml(tw.site_handle) : ' · handle vuoto');
        _summaryRow('twitter', twStatus, twValue);
        _subPanelConfigList('twitter', [
            'Card type: <strong>' + escapeHtml(tw.card_type || 'summary_large_image') + '</strong>',
            'twitter:site: ' + (tw.site_handle ? '<strong>' + escapeHtml(tw.site_handle) + '</strong>' : '<em>vuoto (raccomandato di settarlo)</em>'),
            'twitter:creator mode: <strong>' + escapeHtml(tw.creator_mode || 'auto') + '</strong>',
        ]);

        var rb = hs.robots || {};
        var indexedCpts = Object.keys(rb.post_types || {}).filter(function (k) { return rb.post_types[k] && rb.post_types[k].index; }).length;
        var totalCpts = Object.keys(rb.post_types || {}).length;
        var rbStatus = 'ok';
        var rbValue = '';
        if (totalCpts === 0) { rbValue = 'Tutti i CPT pubblici: index/follow (default)'; }
        else if (indexedCpts === 0) { rbStatus = 'error'; rbValue = '⚠ TUTTI i CPT impostati a noindex (errore?)'; }
        else { rbValue = indexedCpts + ' / ' + totalCpts + ' CPT con index ON'; }
        _summaryRow('robots', rbStatus, rbValue);
        var arc = rb.archives || {};
        var arcSummary = ['category', 'tag', 'author', 'date', 'search'].map(function (k) {
            var cfg = arc[k] || {};
            return k + ': ' + (cfg.index ? 'index' : 'noindex');
        }).join(' · ');
        var rules = rb.rules || {};
        _subPanelConfigList('robots', [
            'CPT con index: <strong>' + indexedCpts + '/' + totalCpts + '</strong>',
            'Archivi: <code>' + escapeHtml(arcSummary) + '</code>',
            'Auto-noindex paginate: ' + (rules.noindex_paginated ? '<strong>✓</strong>' : '<strong>✗</strong>'),
            'Auto-noindex low-words: ' + (rules.noindex_low_words ? '<strong>✓ (≤' + (rules.low_words_threshold || 300) + ')</strong>' : '<strong>✗</strong>'),
        ]);
    }

    var $headPanel = document.querySelector('[data-orch-output="head"]');
    if ($headPanel) {
        var summaryDebounce = null;
        var trigger = function () {
            clearTimeout(summaryDebounce);
            summaryDebounce = setTimeout(renderHeadConfigSummary, 80);
        };
        $headPanel.addEventListener('input', trigger);
        $headPanel.addEventListener('change', trigger);
    }

    function collectHeadSettings() {
        var $ = function (id) { var el = document.getElementById(id); return el ? el.value.trim() : ''; };
        var checked = function (sel) { var el = document.querySelector(sel); return el ? el.checked : false; };
        var radio = function (name) { var el = document.querySelector('input[name="' + name + '"]:checked'); return el ? el.value : ''; };

        var disabledPts = $('orch-canonical-disabled-pts').split(/\r?\n/).map(function (s) { return s.trim(); }).filter(Boolean);
        var canonical = {
            strategy:           radio('orch-canonical-strategy') || 'auto',
            canonical_domain:   $('orch-canonical-domain'),
            custom_prefix:      $('orch-canonical-custom-prefix'),
            trailing_slash:     radio('orch-canonical-trailing') || 'wp_default',
            paginated_first:    checked('#orch-canonical-paginated-first'),
            disabled_post_types: disabledPts,
        };

        var titlePts = {};
        document.querySelectorAll('[data-orch-title-cpt]').forEach(function (i) { titlePts[i.getAttribute('data-orch-title-cpt')] = i.value; });
        var title = {
            post_types: titlePts,
            archives: {
                category: $('orch-title-arc-category'),
                tag:      $('orch-title-arc-tag'),
                author:   $('orch-title-arc-author'),
                search:   $('orch-title-arc-search'),
            },
            separator: radio('orch-title-sep') || '—',
            trim_at_60: checked('#orch-title-trim-60'),
        };

        var priority = [];
        document.querySelectorAll('#orch-metadesc-priority-list [data-orch-priority]').forEach(function (r) { priority.push(r.getAttribute('data-orch-priority')); });
        var meta_desc = {
            priority:        priority,
            min_len:         parseInt($('orch-metadesc-min'), 10) || 120,
            max_len:         parseInt($('orch-metadesc-max'), 10) || 160,
            truncation:      radio('orch-metadesc-trunc') || 'word_boundary',
            acf_field:       $('orch-metadesc-acf-field') || 'meta_description',
            archive_pattern: $('orch-metadesc-archive-pattern'),
        };

        var ogPts = {};
        document.querySelectorAll('[data-orch-og-cpt]').forEach(function (i) { var v = i.value; if (v) ogPts[i.getAttribute('data-orch-og-cpt')] = v; });
        var og = {
            image_fallback:           $('orch-og-image-fallback'),
            locale_override:          $('orch-og-locale-override'),
            site_name_override:       $('orch-og-site-name-override'),
            og_type_per_cpt:          ogPts,
            emit_article_author:      checked('#orch-og-article-author'),
            emit_article_published:   checked('#orch-og-article-published'),
            emit_article_section:     checked('#orch-og-article-section'),
            emit_article_tag:         checked('#orch-og-article-tag'),
        };

        var twitter = {
            card_type:      radio('orch-twitter-card') || 'summary_large_image',
            site_handle:    $('orch-twitter-site'),
            creator_mode:   radio('orch-twitter-creator-mode') || 'auto',
            creator_handle: $('orch-twitter-creator-handle'),
        };

        var robotsPts = {};
        document.querySelectorAll('[data-orch-robots-cpt]').forEach(function (cb) {
            var pt = cb.getAttribute('data-orch-robots-cpt');
            var key = cb.getAttribute('data-key');
            if (!robotsPts[pt]) robotsPts[pt] = {};
            robotsPts[pt][key] = !!cb.checked;
        });
        var arcRobots = {};
        document.querySelectorAll('[data-orch-arc-robots]').forEach(function (cb) {
            var name = cb.getAttribute('data-orch-arc-robots');
            var key = cb.getAttribute('data-key');
            if (!arcRobots[name]) arcRobots[name] = {};
            arcRobots[name][key] = !!cb.checked;
        });
        var robots = {
            post_types: robotsPts,
            archives:   arcRobots,
            rules: {
                noindex_paginated:   checked('#orch-robots-paginated'),
                noindex_low_words:   checked('#orch-robots-low-words'),
                low_words_threshold: parseInt($('orch-robots-low-words-threshold'), 10) || 300,
            },
        };

        return { canonical: canonical, title: title, meta_desc: meta_desc, og: og, twitter: twitter, robots: robots };
    }

        function fillBreadcrumbSettings(b) {
        var $en = document.getElementById('orch-breadcrumb-enabled');
        if ($en) $en.checked = !!b.enabled;
        var sep = b.separator || 'auto';
        document.querySelectorAll('input[name="orch-breadcrumb-sep"]').forEach(function (r) {
            r.checked = (r.value === sep);
        });
    }

    function collectBreadcrumbSettings() {
        var $en = document.getElementById('orch-breadcrumb-enabled');
        var sep = (document.querySelector('input[name="orch-breadcrumb-sep"]:checked') || {}).value || 'auto';
        return {
            enabled: $en ? !!$en.checked : true,
            separator: sep,
        };
    }

        function getSchemaMode() {
        var checked = document.querySelector('input[name="orch-schema-mode"]:checked');
        return checked ? checked.value : 'auto';
    }

    function setSchemaMode(mode) {
        var radios = document.querySelectorAll('input[name="orch-schema-mode"]');
        radios.forEach(function (r) { r.checked = (r.value === mode); });
    }

    // ============ Featured pages autocomplete ============
    function renderFeaturedChips() {
        if (!$featChips) return;
        $featChips.innerHTML = featuredState.map(function (p) {
            return '<span class="orch-featured-chip" data-orch-feat-id="' + p.id + '">'
                +  '<span>' + escapeHtml(p.title) + '</span>'
                +  '<button type="button" class="orch-featured-chip-remove" aria-label="Rimuovi" data-orch-feat-remove="' + p.id + '">×</button>'
                +  '</span>';
        }).join('');
        $featChips.querySelectorAll('[data-orch-feat-remove]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(btn.getAttribute('data-orch-feat-remove'), 10);
                featuredState = featuredState.filter(function (p) { return p.id !== id; });
                renderFeaturedChips();
                updateFeaturedCounter();
            });
        });
    }

    function updateFeaturedCounter() {
        if ($featCounter) $featCounter.textContent = String(featuredState.length);
        if ($featMax) $featMax.textContent = String(FEATURED_MAX);
    }

    function addFeatured(p) {
        if (featuredState.length >= FEATURED_MAX) {
            setStatus('error', 'Massimo ' + FEATURED_MAX + ' pagine in evidenza in /llms.txt.');
            return false;
        }
        if (featuredState.some(function (q) { return q.id === p.id; })) return false;
        featuredState.push(p);
        renderFeaturedChips();
        updateFeaturedCounter();
        return true;
    }

    var featSearchTimer = null;
    function onFeatSearchInput() {
        clearTimeout(featSearchTimer);
        var q = ($featSearch.value || '').trim();
        if (q.length < 2) {
            $featResults.innerHTML = '';
            $featResults.setAttribute('hidden', '');
            return;
        }
        featSearchTimer = setTimeout(function () { runFeatSearch(q); }, 220);
    }

    function runFeatSearch(q) {
        ajaxPost('search_posts', { q: q, limit: 15 }).then(function (resp) {
            if (!resp || !resp.success) return;
            var html;
            if (!resp.results || resp.results.length === 0) {
                html = '<div class="orch-featured-result-empty">Nessun risultato per "' + escapeHtml(q) + '"</div>';
            } else {
                html = resp.results.map(function (r) {
                    var already = featuredState.some(function (q) { return q.id === r.id; });
                    var cls = 'orch-featured-result-item' + (already ? ' is-already' : '');
                    return '<div class="' + cls + '" data-orch-feat-id="' + r.id + '">'
                        +  '<span class="orch-featured-result-title">' + escapeHtml(r.title) + (already ? ' <span style="color:#94a3b8;font-size:11px;">(già selezionato)</span>' : '') + '</span>'
                        +  '<span class="orch-featured-result-type">' + escapeHtml(r.type) + '</span>'
                        +  '</div>';
                }).join('');
            }
            $featResults.innerHTML = html;
            $featResults.removeAttribute('hidden');
            $featResults.querySelectorAll('.orch-featured-result-item').forEach(function (it) {
                it.addEventListener('click', function () {
                    var id = parseInt(it.getAttribute('data-orch-feat-id'), 10);
                    var p = (resp.results || []).filter(function (x) { return x.id === id; })[0];
                    if (!p) return;
                    if (addFeatured(p)) {
                        $featSearch.value = '';
                        $featResults.innerHTML = '';
                        $featResults.setAttribute('hidden', '');
                        $featSearch.focus();
                    }
                });
            });
        }).catch(function () { /* silent */ });
    }

    if ($featSearch) {
        $featSearch.addEventListener('input', onFeatSearchInput);
        $featSearch.addEventListener('blur', function () {
            setTimeout(function () { $featResults.setAttribute('hidden', ''); }, 180);
        });
        $featSearch.addEventListener('focus', function () {
            if ($featResults.innerHTML) $featResults.removeAttribute('hidden');
        });
    }

    // ============ Identity profile fill / collect ============
    function collectIdentity() {
        return {
            business_name:        (document.getElementById('orch-id-name')     || {}).value || '',
            business_description: (document.getElementById('orch-id-desc')     || {}).value || '',
            industry:             (document.getElementById('orch-id-industry') || {}).value || '',
            differentiators:      readListItems('differentiators'),
            territories:          readListItems('territories'),
            use_cases:            readListItems('use_cases'),
            about_strategic:      (document.getElementById('orch-id-about')    || {}).value || ''
        };
    }

    function fillIdentity(profile) {
        if (!profile) profile = {};
        var setVal = function (id, v) { var el = document.getElementById(id); if (el) el.value = v || ''; };
        setVal('orch-id-name',     profile.business_name);
        setVal('orch-id-desc',     profile.business_description);
        setVal('orch-id-industry', profile.industry);
        setVal('orch-id-about',    profile.about_strategic);
        fillListItems('differentiators', profile.differentiators || []);
        fillListItems('territories',     (profile.territories || []).map(function (t) { return t; }));
        fillListItems('use_cases',       profile.use_cases || []);
    }

    function collectPayload() {
        var llmsOnlyTA = document.getElementById('orch-llms-only-exclude');
        return {
            identity: collectIdentity(),
            sitemap_settings: {
                post_types: readPostTypeGrid($sitemapGrid, 'orch-sitemap-pt'),
                takeover_competitors: (function () {
                    var el = document.getElementById('orch-sitemap-takeover');
                    return el ? !!el.checked : null;
                })()
            },
            llms_full_settings: {
                post_types: readPostTypeGrid($llmsfullGrid, 'orch-llmsfull-pt')
            },
            head_settings: collectHeadSettings(),
            schema_settings: {
                mode: getSchemaMode(),
                // 3.35.58 (Stage 2.5 A.1+A.2+A.3)
                org_defaults: collectOrgDefaults(),
                cpt_overrides: collectCptOverrides(),
                breadcrumb: collectBreadcrumbSettings()
            },
            llms_settings: {
                exclude_patterns: llmsOnlyTA ? llmsOnlyTA.value : '',
                featured_pages: featuredState.map(function (p) { return p.id; })
            },
            respect_noindex: $respectNoindex ? !!$respectNoindex.checked : true
        };
    }

    // ============ Stage 2 (3.35.54) — Scan-site + Industry helper + Brand-country coach ============

    var $scanBtn    = document.getElementById('orch-scan-button');
    var $scanStatus = document.getElementById('orch-scan-status');
    var currentIndustry = null;

    function setScanStatus(kind, msg) {
        if (!$scanStatus) return;
        $scanStatus.className = 'orch-scan-status is-visible is-' + kind;
        $scanStatus.textContent = msg;
    }

    function clearScanStatus() {
        if (!$scanStatus) return;
        $scanStatus.className = 'orch-scan-status';
        $scanStatus.textContent = '';
    }

    function badgeSuggested() {
        return '<span class="orch-suggested-badge" data-orch-suggested="1">✨ Suggerito</span>';
    }

    /**
     * 3.35.56 (D): Diff-aware apply. Compares candidates against current values; if any
     * conflict (current non-empty AND different from candidate) shows modal for per-field
     * resolution. Empty fields are populated silently.
     */
    function applyScanCandidatesWithDiff(c) {
        if (!c || typeof c !== 'object') return;

        // Track candidates we'll apply and conflicts we need user input for
        var fieldsToCheck = [
            { key: 'business_name',        elId: 'orch-id-name',     label: 'Nome azienda / sito' },
            { key: 'business_description', elId: 'orch-id-desc',     label: 'Descrizione (1 riga)' },
            { key: 'industry',             elId: 'orch-id-industry', label: 'Settore / industria' },
            { key: 'about_strategic',      elId: 'orch-id-about',    label: 'About strategico' },
        ];
        var safeApply = {}; // {elId: value} — populate silently
        var conflicts = []; // [{key, elId, label, current, candidate}]
        fieldsToCheck.forEach(function (f) {
            var el = document.getElementById(f.elId);
            if (!el) return;
            var current = (el.value || '').trim();
            var candidate = typeof c[f.key] === 'string' ? c[f.key].trim() : '';
            if (!candidate) return; // no candidate, nothing to do
            if (!current) {
                safeApply[f.elId] = candidate;
            } else if (current !== candidate) {
                conflicts.push({ key: f.key, elId: f.elId, label: f.label, current: current, candidate: candidate });
            }
        });

        // List-type fields: apply only if list is empty (no diff modal for lists for v1)
        var listFields = [
            { key: 'differentiators', listKey: 'differentiators' },
            { key: 'territories',     listKey: 'territories' },
            { key: 'use_cases',       listKey: 'use_cases' },
        ];
        listFields.forEach(function (lf) {
            var arr = c[lf.key];
            if (!Array.isArray(arr) || arr.length === 0) return;
            var existing = readListItems(lf.listKey);
            // If existing list is empty (or only blank slots), apply silently
            var hasContent = existing.some(function (it) {
                if (typeof it === 'string') return it.trim() !== '';
                return (it && (it.title || it.description));
            });
            if (!hasContent) {
                fillListItems(lf.listKey, arr);
            }
            // else: keep existing (skipped — list diff is too complex for v1 modal)
        });

        // Apply non-conflicting fields immediately
        Object.keys(safeApply).forEach(function (id) {
            var el = document.getElementById(id);
            if (el) { el.value = safeApply[id]; el.dispatchEvent(new Event('input')); }
        });

        // Refresh industry helpers if industry was applied
        if (safeApply['orch-id-industry']) {
            currentIndustry = safeApply['orch-id-industry'];
            refreshIndustryHelpers(currentIndustry);
        }

        // If there are conflicts, open the diff modal
        if (conflicts.length > 0) {
            openScanDiffModal(conflicts, c);
        }
    }

    /**
     * Backwards-compat shim for any caller that still uses applyScanCandidates.
     */
    function applyScanCandidates(c) {
        applyScanCandidatesWithDiff(c);
    }

    function openScanDiffModal(conflicts, fullCandidates) {
        var modal = document.getElementById('orch-scan-diff-modal');
        if (!modal) return;
        var body = modal.querySelector('.orch-scan-diff-body');
        if (!body) return;
        body.innerHTML = '';

        conflicts.forEach(function (cf, idx) {
            var row = document.createElement('div');
            row.className = 'orch-scan-diff-row';
            row.setAttribute('data-orch-diff-index', String(idx));
            row.innerHTML = ''
                + '<div class="orch-scan-diff-label">' + escapeHtml(cf.label) + '</div>'
                + '<div class="orch-scan-diff-versions">'
                +   '<div class="orch-scan-diff-current">'
                +     '<strong>Tuo valore attuale:</strong>'
                +     '<div class="orch-scan-diff-text">' + escapeHtml(cf.current) + '</div>'
                +   '</div>'
                +   '<div class="orch-scan-diff-candidate">'
                +     '<strong>Nuovo candidato AI:</strong>'
                +     '<div class="orch-scan-diff-text">' + escapeHtml(cf.candidate) + '</div>'
                +   '</div>'
                + '</div>'
                + '<div class="orch-scan-diff-choices">'
                +   '<label><input type="radio" name="orch-diff-choice-' + idx + '" value="keep" checked> Mantieni mio</label>'
                +   '<label><input type="radio" name="orch-diff-choice-' + idx + '" value="use_new"> Usa nuovo</label>'
                +   '<label><input type="radio" name="orch-diff-choice-' + idx + '" value="edit"> Modifica</label>'
                + '</div>'
                + '<div class="orch-scan-diff-edit" style="display:none;">'
                +   '<textarea rows="3" placeholder="Scrivi la tua versione custom (entrambi sopra come riferimento)"></textarea>'
                + '</div>';
            body.appendChild(row);

            // Wire up "edit" expansion
            row.querySelectorAll('input[type="radio"]').forEach(function (r) {
                r.addEventListener('change', function () {
                    var editPane = row.querySelector('.orch-scan-diff-edit');
                    if (r.value === 'edit') {
                        editPane.style.display = 'block';
                        var ta = editPane.querySelector('textarea');
                        if (ta && !ta.value) ta.value = cf.candidate; // default to candidate
                    } else {
                        editPane.style.display = 'none';
                    }
                });
            });
        });

        // Wire up confirm button
        var btnConfirm = modal.querySelector('[data-orch-diff-confirm]');
        if (btnConfirm) {
            btnConfirm.onclick = function () {
                conflicts.forEach(function (cf, idx) {
                    var row = body.querySelector('[data-orch-diff-index="' + idx + '"]');
                    if (!row) return;
                    var checked = row.querySelector('input[type="radio"]:checked');
                    if (!checked) return;
                    var choice = checked.value;
                    var el = document.getElementById(cf.elId);
                    if (!el) return;
                    var newVal = el.value;
                    if (choice === 'use_new') newVal = cf.candidate;
                    else if (choice === 'edit') {
                        var ta = row.querySelector('.orch-scan-diff-edit textarea');
                        if (ta) newVal = ta.value;
                    }
                    // 'keep' → leave el.value as-is
                    if (choice !== 'keep') {
                        el.value = newVal;
                        el.dispatchEvent(new Event('input'));
                    }
                });
                // Industry refresh if changed
                var indEl = document.getElementById('orch-id-industry');
                if (indEl && indEl.value) {
                    currentIndustry = indEl.value;
                    refreshIndustryHelpers(currentIndustry);
                }
                modal.setAttribute('hidden', '');
                identityRefreshPreview();
            };
        }
        var btnCancel = modal.querySelector('[data-orch-diff-cancel]');
        if (btnCancel) {
            btnCancel.onclick = function () {
                // Cancel → revert NON-conflicting populated fields too?
                // No: safeApply already happened. User can re-edit if needed.
                modal.setAttribute('hidden', '');
            };
        }

        modal.removeAttribute('hidden');
    }

    function runScan(force) {
        if (!$scanBtn) return;
        $scanBtn.disabled = true;
        setScanStatus('loading', '⏳ Scan in corso (homepage + about + servizi)…');
        ajaxPost('identity_scan', force ? { force: '1' } : {}).then(function (resp) {
            $scanBtn.disabled = false;
            if (!resp || resp.error) {
                if (resp && resp.forbidden) {
                    setScanStatus('locked', '🔒 ' + (resp.message || 'Auto-scan disponibile solo con licenza Pro.'));
                } else {
                    setScanStatus('error', '⚠️ ' + (resp && (resp.message || resp.error) ? (resp.message || resp.error) : 'Errore scan.'));
                }
                return;
            }
            applyScanCandidates(resp.candidates || {});
            var pages = (resp.pages_scanned || []).length;
            var msg = (resp.from_cache ? '✓ Risultato dalla cache (' + (resp.cache_age_hours || '?') + 'h fa)' : '✓ Scan completato')
                + ' · ' + pages + ' pagine analizzate · industry rilevata: ' + (resp.industry_recommendation || '?')
                + (resp.from_cache ? '' : ' · 5 crediti consumati');
            setScanStatus('success', msg);
            identityRefreshPreview();
        }).catch(function (err) {
            $scanBtn.disabled = false;
            setScanStatus('error', '⚠️ Errore di rete: ' + (err && err.message ? err.message : 'sconosciuto'));
        });
    }

    if ($scanBtn) {
        $scanBtn.addEventListener('click', function () { runScan(false); });
    }

    // ============ Stage 2.5 (3.35.55) — Banner state machine + Page Picker + Onboarding + Roles ============

    var $banner = document.getElementById('orch-stage25-banner');
    var $pickerModal = document.getElementById('orch-stage25-page-picker');
    var $onboardingModal = document.getElementById('orch-stage25-pro-onboarding');
    var $featuredAutoBtn = document.getElementById('orch-featured-auto-suggest');

    var pageRolesData = null;     // {roles_map, top10, roles_enum, pro_onboarding_seen, total_classified}
    var userTier = 'free';         // 'free' | 'paid'
    var pickerSelected = {};       // {post_id: {id, title, url, type, role}}

    function renderBannerLoading() {
        if (!$banner) return;
        $banner.setAttribute('data-orch-stage25-banner', 'loading');
        $banner.innerHTML = '<div class="orch-stage25-banner-loading"><span class="rv-spinner"></span> Caricamento profilo + ruoli pagine…</div>';
    }

    function renderBannerFree(rolesData) {
        if (!$banner) return;
        $banner.setAttribute('data-orch-stage25-banner', 'free');
        var totalPages = rolesData ? (rolesData.total_classified || 0) : 0;
        var selectedCount = pickerSelected ? Object.keys(pickerSelected).length : 0;
        $banner.innerHTML = ''
            + '<h4>✨ Imposta il tuo profilo AEO</h4>'
            + '<p>Scegli fino a 10 pagine più importanti del tuo sito — quelle che meglio rappresentano chi sei. Le analizzeremo a fondo per pre-popolare il tuo profilo identità e creare un /llms.txt ottimizzato per ChatGPT, Claude, Perplexity, Gemini.</p>'
            + '<div class="orch-stage25-banner-actions">'
            +   '<button type="button" class="orch3-btn orch3-btn-primary" id="orch-banner-pick-pages">✨ Scegli le pagine →</button>'
            +   '<span class="orch-stage25-banner-counter">' + selectedCount + ' / 10 selezionate</span>'
            + '</div>'
            + '<div class="orch-stage25-banner-upsell">Vuoi mappare TUTTO il sito (' + totalPages + ' pagine totali rilevate)?<br>'
            +   '→ Pro Studio scansiona tutto in 30 secondi · €129 lifetime <a href="https://aeo-orchestra.com/pricing" target="_blank" rel="noopener">[Vai a /pricing →]</a>'
            + '</div>';
        var btn = document.getElementById('orch-banner-pick-pages');
        if (btn) btn.addEventListener('click', openPagePickerModal);
    }

    function renderBannerProOnboarding() {
        if (!$banner) return;
        $banner.setAttribute('data-orch-stage25-banner', 'pro-onboarding');
        $banner.innerHTML = ''
            + '<h4>🎁 Bonus benvenuto Pro: analisi completa del tuo sito</h4>'
            + '<p><strong>Cosa facciamo per te:</strong></p>'
            + '<ul>'
            +   '<li>✓ Classifichiamo i <strong>RUOLI</strong> di tutte le pagine pubbliche del sito (homepage, about, FAQ, prodotti, servizi, ecc.) — heuristic locale gratuita</li>'
            +   '<li>✓ Analizziamo in profondità le pagine core (homepage, chi siamo, servizi, contatti) per estrarre business identity — industry, differenzianti, casi d\'uso, certificazioni</li>'
            +   '<li>✓ Classifichiamo via <strong>LLM</strong> le pagine residue ambigue (cap 50)</li>'
            +   '<li>✓ Pre-popoliamo l\'identity profile con candidati AI</li>'
            + '</ul>'
            + '<p style="font-size:12px;color:#64748b;"><strong>Cosa NON facciamo:</strong> non scarichiamo immagini né media · non leggiamo aree riservate o login-protected · non condividiamo dati con terze parti</p>'
            + '<p style="font-size:12px;color:#64748b;">Tempo stimato: ~30 secondi · Costo: 0 (incluso Pro)</p>'
            + '<div class="orch-stage25-banner-actions">'
            +   '<button type="button" class="orch3-btn orch3-btn-primary" id="orch-banner-start-onboarding">Avvia analisi completa</button>'
            +   '<button type="button" class="orch3-btn orch3-btn-ghost" id="orch-banner-skip-onboarding">Imposterò manualmente</button>'
            + '</div>';
        var btn1 = document.getElementById('orch-banner-start-onboarding');
        var btn2 = document.getElementById('orch-banner-skip-onboarding');
        if (btn1) btn1.addEventListener('click', function () { openProOnboardingModal(); });
        if (btn2) btn2.addEventListener('click', function () {
            ajaxPost('pro_onboarding_seen', {}).then(function () {
                renderBannerProRescan(null); // Or just hide
            });
        });
    }

    function renderBannerProRescan(quotaInfo) {
        if (!$banner) return;
        $banner.setAttribute('data-orch-stage25-banner', 'pro-rescan');
        $banner.innerHTML = ''
            + '<h4>🔄 Re-scan disponibile</h4>'
            + '<p>L\'ultima scansione è stata fatta. Vuoi rifare la scansione gratuitamente? (1/1 quota mensile inclusa Pro)</p>'
            + '<div class="orch-stage25-banner-actions">'
            +   '<button type="button" class="orch3-btn orch3-btn-primary" id="orch-banner-rescan">Re-scansiona</button>'
            + '</div>';
        var btn = document.getElementById('orch-banner-rescan');
        if (btn) btn.addEventListener('click', function () { runFullScan(false); });
    }

    function renderBannerProQuotaExhausted() {
        if (!$banner) return;
        $banner.setAttribute('data-orch-stage25-banner', 'pro-quota-exhausted');
        $banner.innerHTML = ''
            + '<h4>⏱ Quota mensile esaurita</h4>'
            + '<p>Hai già usato la tua scansione gratuita di questo mese. Prossimo refresh quota: tra qualche giorno. Vuoi forzare un re-scan adesso? Costo: 5 crediti.</p>'
            + '<div class="orch-stage25-banner-actions">'
            +   '<button type="button" class="orch3-btn orch3-btn-primary" id="orch-banner-force-rescan">Re-scansiona ora (5 crediti)</button>'
            +   '<button type="button" class="orch3-btn orch3-btn-ghost" id="orch-banner-wait">Aspetta</button>'
            + '</div>';
        var btn1 = document.getElementById('orch-banner-force-rescan');
        if (btn1) btn1.addEventListener('click', function () { runFullScan(true); });
    }

    function renderBannerByTier() {
        if (!pageRolesData) {
            renderBannerLoading();
            return;
        }
        if (userTier !== 'paid') {
            renderBannerFree(pageRolesData);
            return;
        }
        // Pro
        if (!pageRolesData.pro_onboarding_seen) {
            renderBannerProOnboarding();
            return;
        }
        // Default Pro state (after onboarding done): show re-scan button
        renderBannerProRescan(null);
    }

    // ============ Page Picker Modal logic ============
    function openPagePickerModal() {
        if (!$pickerModal) return;
        // Pre-fill from heuristic top10
        pickerSelected = {};
        var top10 = (pageRolesData && pageRolesData.top10) || [];
        top10.slice(0, 10).forEach(function (p) {
            pickerSelected[p.id] = p;
        });
        renderPickerList();
        $pickerModal.removeAttribute('hidden');
    }

    function closePickerModal() {
        if ($pickerModal) $pickerModal.setAttribute('hidden', '');
    }

    function renderPickerList() {
        var list = document.getElementById('orch-picker-list');
        if (!list) return;
        var ids = Object.keys(pickerSelected);
        var top10 = (pageRolesData && pageRolesData.top10) || [];
        // Show all top10 + any added via search
        var byId = {};
        top10.forEach(function (p) { byId[p.id] = p; });
        ids.forEach(function (id) { if (!byId[id]) byId[id] = pickerSelected[id]; });

        var html = Object.keys(byId).map(function (id) {
            var p = byId[id];
            var checked = pickerSelected[id] ? 'checked' : '';
            var roleHtml = p.role ? '<span class="orch-picker-list-item-role">' + escapeHtml(p.role) + '</span>' : '';
            return '<label class="orch-picker-list-item">'
                +    '<input type="checkbox" data-orch-picker-id="' + id + '" ' + checked + '>'
                +    '<span class="orch-picker-list-item-title">' + escapeHtml(p.title || '(senza titolo)') + '</span>'
                +    '<div class="orch-picker-list-item-meta">'
                +      '<span class="orch-picker-list-item-type">' + escapeHtml(p.post_type || p.type || 'page') + '</span>'
                +      roleHtml
                +    '</div>'
                +  '</label>';
        }).join('');
        list.innerHTML = html || '<p class="orch3-muted" style="padding:20px;text-align:center;">Nessuna pagina classificata. Usa la ricerca per aggiungerne.</p>';

        list.querySelectorAll('input[data-orch-picker-id]').forEach(function (cb) {
            cb.addEventListener('change', function () {
                var id = cb.getAttribute('data-orch-picker-id');
                if (cb.checked) {
                    pickerSelected[id] = byId[id];
                } else {
                    delete pickerSelected[id];
                }
                updatePickerCounter();
            });
        });

        updatePickerCounter();
    }

    function updatePickerCounter() {
        var n = Object.keys(pickerSelected).length;
        var $counter = document.getElementById('orch-picker-count');
        if ($counter) $counter.textContent = n;
        var $confirm = document.getElementById('orch-picker-confirm');
        if ($confirm) {
            $confirm.disabled = (n < 3 || n > 10);
        }
    }

    // Picker search (reuses search_posts ajax)
    var $pickerSearch = document.getElementById('orch-picker-search');
    var $pickerResults = document.getElementById('orch-picker-search-results');
    var pickerSearchTimer = null;
    if ($pickerSearch && $pickerResults) {
        $pickerSearch.addEventListener('input', function () {
            clearTimeout(pickerSearchTimer);
            var q = $pickerSearch.value.trim();
            if (q.length < 2) {
                $pickerResults.innerHTML = '';
                $pickerResults.setAttribute('hidden', '');
                return;
            }
            pickerSearchTimer = setTimeout(function () {
                ajaxPost('search_posts', { q: q, limit: 10 }).then(function (resp) {
                    if (!resp || !resp.success || !resp.results || resp.results.length === 0) {
                        $pickerResults.innerHTML = '<div class="orch-featured-result-empty">Nessun risultato</div>';
                        $pickerResults.removeAttribute('hidden');
                        return;
                    }
                    $pickerResults.innerHTML = resp.results.map(function (r) {
                        return '<div class="orch-featured-result-item" data-orch-picker-add="' + r.id + '">'
                            + '<span class="orch-featured-result-title">' + escapeHtml(r.title) + '</span>'
                            + '<span class="orch-featured-result-type">' + escapeHtml(r.type) + '</span>'
                            + '</div>';
                    }).join('');
                    $pickerResults.removeAttribute('hidden');
                    $pickerResults.querySelectorAll('[data-orch-picker-add]').forEach(function (it) {
                        it.addEventListener('click', function () {
                            var id = it.getAttribute('data-orch-picker-add');
                            var p = (resp.results || []).filter(function (x) { return String(x.id) === id; })[0];
                            if (!p) return;
                            pickerSelected[id] = { id: parseInt(id, 10), title: p.title, url: p.url, post_type: p.type, role: 'custom' };
                            $pickerSearch.value = '';
                            $pickerResults.innerHTML = '';
                            $pickerResults.setAttribute('hidden', '');
                            renderPickerList();
                        });
                    });
                });
            }, 220);
        });
    }

    var $pickerConfirm = document.getElementById('orch-picker-confirm');
    if ($pickerConfirm) {
        $pickerConfirm.addEventListener('click', function () {
            var ids = Object.keys(pickerSelected).map(function (id) { return parseInt(id, 10); });
            closePickerModal();
            runScanWithPages(ids, 'top10', false);
        });
    }

    // Modal close handlers
    document.querySelectorAll('[data-orch-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var which = btn.getAttribute('data-orch-modal-close');
            if (which === 'picker') closePickerModal();
            if (which === 'onboarding' && $onboardingModal) $onboardingModal.setAttribute('hidden', '');
        });
    });

    // ============ Pro Onboarding Modal logic ============
    function openProOnboardingModal() {
        if ($onboardingModal) $onboardingModal.removeAttribute('hidden');
    }
    var $onboardingStart = document.getElementById('orch-onboarding-start');
    var $onboardingSkip = document.getElementById('orch-onboarding-skip');
    if ($onboardingStart) $onboardingStart.addEventListener('click', function () {
        if ($onboardingModal) $onboardingModal.setAttribute('hidden', '');
        ajaxPost('pro_onboarding_seen', {}).then(function () {});
        runFullScan(false);
    });
    if ($onboardingSkip) $onboardingSkip.addEventListener('click', function () {
        if ($onboardingModal) $onboardingModal.setAttribute('hidden', '');
        ajaxPost('pro_onboarding_seen', {}).then(function () { renderBannerProRescan(null); });
    });

    // ============ Scan flows ============
    function runFullScan(force) {
        setScanStatus('loading', '⏳ Scan completo in corso (homepage + about + servizi + classificazione ruoli pagine via LLM)…');
        ajaxPost('identity_scan', { mode: 'full', force: force ? '1' : '0' }).then(function (resp) {
            handleScanResponse(resp, 'full');
        }).catch(function (err) {
            setScanStatus('error', '⚠️ Errore di rete: ' + (err && err.message ? err.message : 'sconosciuto'));
        });
    }
    function runScanWithPages(ids, mode, force) {
        setScanStatus('loading', '⏳ Scan in corso su ' + ids.length + ' pagine selezionate…');
        ajaxPost('identity_scan', { mode: mode, force: force ? '1' : '0', pages: JSON.stringify(ids) }).then(function (resp) {
            handleScanResponse(resp, mode);
        }).catch(function (err) {
            setScanStatus('error', '⚠️ Errore di rete: ' + (err && err.message ? err.message : 'sconosciuto'));
        });
    }
    function handleScanResponse(resp, mode) {
        if (!resp || resp.error) {
            if (resp && resp.forbidden) {
                setScanStatus('locked', '🔒 ' + (resp.message || 'Funzione disponibile solo con licenza Pro.'));
            } else if (resp && resp.quota_exhausted) {
                setScanStatus('error', '⏱ ' + resp.message);
                renderBannerProQuotaExhausted();
            } else {
                var msg = (resp && (resp.message || resp.error)) || 'Errore scan.';
                if (resp && resp.debug && resp.debug.attempted_urls) {
                    msg += ' (Tentativi: ' + resp.debug.attempted_urls.length + ')';
                }
                setScanStatus('error', '⚠️ ' + msg);
            }
            return;
        }
        // 3.35.56 (D): show diff modal if candidates conflict with manually-edited fields
        applyScanCandidatesWithDiff(resp.candidates || {});

        // 3.35.56 (B): status pill format with classification breakdown
        var total = resp.pages_classified_total || 0;
        var heur = resp.pages_classified_heuristic || 0;
        var llm = resp.pages_classified_llm_augment || 0;
        var manual = resp.pages_classified_manual || 0;
        var identityCount = resp.pages_identity_analyzed || (resp.pages_scanned || []).length;
        var industry = resp.industry_recommendation || '?';

        var pieces = ['✓ Analisi completata'];
        if (total > 0) {
            var breakdown = heur + ' heuristic';
            if (llm > 0) breakdown += ' + ' + llm + ' LLM';
            if (manual > 0) breakdown += ' + ' + manual + ' manual';
            pieces.push(total + ' pagine classificate (' + breakdown + ')');
        }
        if (identityCount > 0) pieces.push(identityCount + ' pagine core analizzate');
        pieces.push('industry: ' + industry);
        if (resp.credits_consumed) pieces.push(resp.credits_consumed + ' crediti');

        setScanStatus('success', pieces.join(' · '));
        // Refresh roles map
        loadPageRoles();
        identityRefreshPreview();
    }

    // ============ Page Roles loader + table ============
    function loadPageRoles() {
        ajaxPost('get_page_roles', {}).then(function (resp) {
            if (!resp || !resp.success) return;
            pageRolesData = resp;
            renderRolesTable();
            renderBannerByTier();
        }).catch(function () { /* silent */ });
    }

    function renderRolesTable() {
        var $tbody = document.getElementById('orch-roles-tbody');
        var $loading = document.getElementById('orch-roles-loading');
        var $wrap = document.getElementById('orch-roles-table-wrap');
        var $count = document.getElementById('orch-roles-count');
        var $meta = document.getElementById('orch-roles-meta');
        var $filterRole = document.getElementById('orch-roles-filter-role');
        if (!$tbody || !pageRolesData) return;

        // Populate role filter dropdown if first time
        if ($filterRole && $filterRole.options.length <= 1) {
            (pageRolesData.roles_enum || []).forEach(function (r) {
                var opt = document.createElement('option');
                opt.value = r;
                opt.textContent = r;
                $filterRole.appendChild(opt);
            });
        }

        var rows = pageRolesData.roles_map || [];
        var filterRole = $filterRole ? $filterRole.value : '';
        var filterSource = (document.getElementById('orch-roles-filter-source') || {}).value || '';
        var searchQ = ((document.getElementById('orch-roles-search') || {}).value || '').toLowerCase();

        var filtered = rows.filter(function (r) {
            if (filterRole && r.role !== filterRole) return false;
            if (filterSource && r.source !== filterSource) return false;
            if (searchQ && (r.title || '').toLowerCase().indexOf(searchQ) === -1 && (r.slug || '').toLowerCase().indexOf(searchQ) === -1) return false;
            return true;
        });

        var enumOpts = (pageRolesData.roles_enum || []).map(function (r) {
            return '<option value="' + r + '">' + r + '</option>';
        }).join('');

        $tbody.innerHTML = filtered.slice(0, 200).map(function (r) {
            var conf = Math.round((r.confidence || 0) * 100);
            return '<tr>'
                + '<td><span class="orch-role-pill role-' + escapeHtml(r.role) + '">' + escapeHtml(r.role) + '</span></td>'
                + '<td><strong>' + escapeHtml(r.title || '(senza titolo)') + '</strong><br><small class="orch3-muted">' + escapeHtml(r.slug) + ' · ' + escapeHtml(r.post_type) + '</small></td>'
                + '<td><span class="orch-role-confidence-bar"><span class="orch-role-confidence-bar-fill" style="width:' + conf + '%"></span></span> ' + conf + '%</td>'
                + '<td><span class="orch-role-source-pill is-' + escapeHtml(r.source) + '">' + escapeHtml(r.source) + '</span></td>'
                + '<td><select class="orch-role-override-select" data-orch-role-override="' + r.id + '"><option value="">—</option>' + enumOpts + '</select></td>'
                + '</tr>';
        }).join('');

        // Wire up override selects
        $tbody.querySelectorAll('[data-orch-role-override]').forEach(function (sel) {
            sel.addEventListener('change', function () {
                var pid = sel.getAttribute('data-orch-role-override');
                var role = sel.value;
                if (!role) return;
                ajaxPost('set_role', { post_id: pid, role: role }).then(function (resp) {
                    if (resp && resp.success) {
                        loadPageRoles();
                    }
                });
            });
        });

        if ($loading) $loading.style.display = 'none';
        if ($wrap) $wrap.style.display = 'block';
        if ($count) $count.textContent = filtered.length + ' / ' + rows.length + ' pagine';
        if ($meta) {
            $meta.textContent = (pageRolesData.total_classified || 0) + ' classificate · top-10 ranked: ' + (pageRolesData.top10 || []).length;
        }
    }

    // Hook filter inputs
    ['orch-roles-search', 'orch-roles-filter-role', 'orch-roles-filter-source'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', renderRolesTable);
        if (el) el.addEventListener('change', renderRolesTable);
    });

    // Re-classify heuristic button
    var $reclassBtn = document.getElementById('orch-roles-reclassify-heuristic');
    if ($reclassBtn) {
        $reclassBtn.addEventListener('click', function () {
            $reclassBtn.disabled = true;
            $reclassBtn.textContent = '⏳ Re-classificando…';
            ajaxPost('run_heuristic_classify', {}).then(function (resp) {
                $reclassBtn.disabled = false;
                $reclassBtn.innerHTML = '🔄 Re-classifica heuristic';
                if (resp && resp.success) {
                    setScanStatus('success', '✓ Heuristic re-classified · ' + resp.report.classified + ' classified · ' + resp.report.skipped + ' skipped');
                    loadPageRoles();
                }
            }).catch(function () {
                $reclassBtn.disabled = false;
                $reclassBtn.innerHTML = '🔄 Re-classifica heuristic';
            });
        });
    }

    // Featured auto-suggest
    if ($featuredAutoBtn) {
        $featuredAutoBtn.addEventListener('click', function () {
            // 3.35.58 E2: confirm overwrite if featured already has chips
            var existingCount = featuredState ? featuredState.length : 0;
            var doRun = function () {
                $featuredAutoBtn.disabled = true;
                ajaxPost('apply_featured_from_roles', {}).then(function (resp) {
                    $featuredAutoBtn.disabled = false;
                    if (resp && resp.success && Array.isArray(resp.picked)) {
                        featuredState = resp.picked.slice(0, 10);
                        renderFeaturedChips();
                        updateFeaturedCounter();
                        setStatus('success', '✓ Featured pages auto-popolate da ruoli (' + resp.picked.length + ' selezionate). Clicca Salva profilo per persistere.');
                    }
                }).catch(function () {
                    $featuredAutoBtn.disabled = false;
                });
            };
            if (existingCount > 0) {
                if (window.confirm('Sostituire ' + existingCount + ' chip esistenti con le nuove suggerite (max 10)?')) {
                    doRun();
                }
                return;
            }
            doRun();
        });
    }

    // Boot: detect tier from license key (heuristic — non-empty key + saved profile assumes Paid;
    // backend will enforce. This is just for UI hint.)
    if (window.seoAeoOrchestra && window.seoAeoOrchestra.licenseKey) {
        userTier = 'paid';
    }
    loadPageRoles();


    // ---- Industry-aware helper text under differentiators / use_cases / about ----
    var industryCache = {};
    function refreshIndustryHelpers(industry) {
        if (!industry) return;
        if (industryCache[industry]) {
            renderIndustryHelpers(industryCache[industry]);
            return;
        }
        ajaxPost('industry_examples', { industry: industry }).then(function (resp) {
            if (!resp || !resp.examples) return;
            industryCache[industry] = resp;
            renderIndustryHelpers(resp);
        }).catch(function () { /* silent */ });
    }

    function renderIndustryHelpers(resp) {
        var ex = resp.examples || {};
        var resolved = resp.industry_resolved || resp.industry_requested || '';
        var labelMap = {
            differentiators: ex.differentiators || [],
            use_cases:       ex.use_cases || [],
            certifications:  ex.certifications || []
        };
        Object.keys(labelMap).forEach(function (kind) {
            var list = document.querySelector('[data-orch-list="' + kind + '"]');
            if (!list) return;
            // Place helper under the list block (not for certifications since not in current Free UI)
            var helperId = 'orch-industry-help-' + kind;
            var helper = document.getElementById(helperId);
            var examples = labelMap[kind].slice(0, 5);
            if (examples.length === 0) {
                if (helper) helper.remove();
                return;
            }
            var html = '<div class="orch-industry-help" id="' + helperId + '">'
                + '<strong>💡 Esempi per ' + escapeHtml(resolved) + ':</strong>'
                + '<ul class="orch-industry-help-list">'
                + examples.map(function (e) {
                    if (typeof e === 'string') return '<li>' + escapeHtml(e) + '</li>';
                    var t = (e && e.title) || '';
                    var d = (e && e.description) || '';
                    return '<li>' + escapeHtml(t) + (d ? ' — ' + escapeHtml(d) : '') + '</li>';
                }).join('')
                + '</ul></div>';
            if (helper) {
                helper.outerHTML = html;
            } else {
                // Insert AFTER the list element
                list.insertAdjacentHTML('afterend', html);
            }
        });
    }

    // Watch the industry input — update helpers when user types/edits manually
    var $industryInput = document.getElementById('orch-id-industry');
    if ($industryInput) {
        var industryDebounce = null;
        $industryInput.addEventListener('input', function () {
            clearTimeout(industryDebounce);
            var v = $industryInput.value.trim();
            if (v.length < 3) return;
            industryDebounce = setTimeout(function () {
                currentIndustry = v;
                refreshIndustryHelpers(v);
            }, 800);
        });
    }

    // ---- Brand+Country copy coaching ----
    // Detect ambiguous patterns like "Brand (Country)", "Brand · Country", "Brand, Country" in
    // textarea fields; show inline tip suggesting natural-language rephrase.
    // Triggers on blur (user finishes typing) — non-intrusive.

    // 3.35.55 Bug 2 fix: parentheses (...) are the CORRECT disambiguation, NOT the problem.
    // Match only ambiguous separators: middle-dot ·, comma without closing paren, OR adjacency
    // (whitespace-only between brand and country). Brand (Country) pattern is excluded explicitly.
    var BRAND_COUNTRY_REGEX_PARENS_SAFE = true;
    var COUNTRIES_PATTERN = '(USA|US|Italia|UK|Germany|France|Spain|Spagna|Italy|Stati Uniti|Cina|China|Giappone|Japan)';
    // 1. Adjacency without separator: "Acme USA" — match if whitespace-only between brand and country
    //    (excluded if directly preceded by '(' which means the user used the parenthetical disambiguation)
    var BRAND_COUNTRY_RE_ADJACENT = new RegExp('(?<![\\(])\\b([A-Z][A-Za-z0-9&-]{2,})\\s+' + COUNTRIES_PATTERN + '\\b(?!\\))', '');
    // 2. Middle-dot or comma: "Acme · USA" or "Acme, USA" — match if NOT followed by closing paren
    var BRAND_COUNTRY_RE_SEP = new RegExp('\\b([A-Z][A-Za-z0-9&-]{2,})\\s*[·,]\\s*' + COUNTRIES_PATTERN + '\\b(?!\\))', '');
    // Combined matcher used by attachBrandCountryCoach
    function _detectBrandCountryProblem(text) {
        var s = String(text || '');
        // Check parenthetical disambiguation first — if present anywhere, skip the entire detection
        // for that brand/country pair.
        var paren = s.match(new RegExp('\\b([A-Z][A-Za-z0-9&-]{2,})\\s*\\(' + COUNTRIES_PATTERN + '\\)', ''));
        var paren_brand = paren ? paren[1] : null;
        // Try adjacency
        var m = s.match(BRAND_COUNTRY_RE_ADJACENT);
        if (m && m[1] !== paren_brand) return { brand: m[1], country: m[2], pattern: 'adjacent' };
        // Try separator (· or ,)
        m = s.match(BRAND_COUNTRY_RE_SEP);
        if (m && m[1] !== paren_brand) return { brand: m[1], country: m[2], pattern: 'separator' };
        return null;
    }
    var COUNTRY_REWRITE_HINTS = {
        'USA': 'statunitense', 'US': 'statunitense', 'Stati Uniti': 'statunitense',
        'Italia': 'italiano', 'Italy': 'italiano',
        'UK': 'britannico', 'Germany': 'tedesco', 'France': 'francese',
        'Spain': 'spagnolo', 'Spagna': 'spagnolo',
        'Cina': 'cinese', 'China': 'cinese',
        'Giappone': 'giapponese', 'Japan': 'giapponese'
    };

    function attachBrandCountryCoach(el) {
        if (!el || el._orch_coach_attached) return;
        el._orch_coach_attached = true;
        el.addEventListener('blur', function () {
            var v = (el.value || '').trim();
            // 3.35.55 Bug 1 fix: dedup at ITEM level, not field level. Find the item wrapper
            // (or the element itself) and clear any existing coach card before re-evaluating.
            var item = el.closest('.orch-identity-list-item') || el.closest('fieldset') || el.parentElement;
            // Walk all fields in this item — only show ONE coach card based on the FIRST detected pattern
            // across all fields combined. This prevents duplicate cards on title+description blur.
            if (!item) return;

            // Remove any existing coach card belonging to this item
            var existing = item.parentElement && item.parentElement.querySelectorAll('.orch-coach-inline[data-orch-coach-item="' + (item.getAttribute('data-orch-coach-id') || '') + '"]');
            // Tag the item once for stable scoping
            if (!item.getAttribute('data-orch-coach-id')) {
                item.setAttribute('data-orch-coach-id', 'oc' + Math.random().toString(36).slice(2));
            }
            var itemId = item.getAttribute('data-orch-coach-id');
            // Now remove existing card (single one expected)
            var existingCard = document.querySelector('.orch-coach-inline[data-orch-coach-item="' + itemId + '"]');
            if (existingCard) existingCard.remove();

            // Concatenate all field values in this item — coach evaluates the WHOLE item content
            var combined = '';
            item.querySelectorAll('input[type="text"], textarea').forEach(function (f) {
                combined += ' ' + (f.value || '');
            });
            combined = combined.trim();
            if (combined === '') return;

            var detected = _detectBrandCountryProblem(combined);
            if (!detected) return;

            var brand = detected.brand;
            var country = detected.country;
            var demonym = COUNTRY_REWRITE_HINTS[country] || (country.toLowerCase());
            var tip = document.createElement('div');
            tip.className = 'orch-coach-inline';
            tip.setAttribute('data-orch-coach-item', itemId);
            tip.innerHTML = '<strong>💡 Suggerimento copy:</strong> il pattern <code>"' + escapeHtml(brand) + ' ' + escapeHtml(country) + '"</code> '
                + 'può essere parsato dagli AI engine come due entità separate. '
                + 'Prova: <em>"Distributore esclusivo di ' + escapeHtml(brand) + ', marchio ' + escapeHtml(demonym) + '"</em> '
                + 'oppure <em>"' + escapeHtml(brand) + ' (' + escapeHtml(country) + ')"</em> con parentesi.'
                + '<span class="orch-coach-inline-close" title="Chiudi">×</span>';
            tip.querySelector('.orch-coach-inline-close').addEventListener('click', function () {
                tip.remove();
            });
            // Insert AFTER the item element (sibling, not child — keeps the item layout clean)
            item.parentNode.insertBefore(tip, item.nextSibling);
        });
    }

    // Attach coach to already-existing inputs + observe future ones via MutationObserver
    function attachCoachToExisting() {
        document.querySelectorAll('input[data-orch-field="title"], textarea[data-orch-field="description"], #orch-id-name, #orch-id-desc, #orch-id-about').forEach(attachBrandCountryCoach);
    }
    attachCoachToExisting();

    var coachObserver = new MutationObserver(function () { attachCoachToExisting(); });
    document.querySelectorAll('[data-orch-list]').forEach(function (list) {
        coachObserver.observe(list, { childList: true, subtree: true });
    });

    // ============ Boot: load config + identity ============
    function identityLoad() {
        ajaxPost('config_get', {}).then(function (resp) {
            if (resp && resp.success) {
                detectedTypes = resp.detected_types || [];

                // Sitemap.xml grid + detection + takeover
                renderPostTypeGrid($sitemapGrid, 'orch-sitemap-pt', (resp.sitemap || {}).post_types || []);
                if (resp.sitemap) {
                    renderSitemapDetection(resp.sitemap.detected_providers || [], resp.sitemap.takeover_recommended);
                    var $takeover = document.getElementById('orch-sitemap-takeover');
                    if ($takeover) {
                        var v = resp.sitemap.takeover_competitors;
                        if (v === null || typeof v === 'undefined') {
                            $takeover.checked = !!resp.sitemap.takeover_recommended;
                        } else {
                            $takeover.checked = !!v;
                        }
                    }
                }

                // /llms-full.txt grid
                renderPostTypeGrid($llmsfullGrid, 'orch-llmsfull-pt', (resp.llms_full || {}).post_types || []);

                // Schema accordion
                if (resp.schema) {
                    setSchemaMode(resp.schema.mode || 'auto');
                    renderSchemaDetection(resp.schema.detected_providers || [], resp.schema.recommended_mode, resp.schema.resolved_mode);
                    // 3.35.58 A.1: org defaults
                    fillOrgDefaults(resp.schema.org_defaults || {});
                    // 3.35.58 A.2: CPT overrides table
                    renderCptOverrides(resp.schema.detected_cpts || [], resp.schema.cpt_overrides || {}, resp.schema.available_types || []);
                    // 3.35.58 A.3: breadcrumb settings
                    fillBreadcrumbSettings(resp.schema.breadcrumb || {});
                }
                if (resp.head_settings) {
                    fillHeadSettings(resp.head_settings);
                    if (typeof renderHeadConfigSummary === 'function') {
                        setTimeout(renderHeadConfigSummary, 50);
                    }
                }

                // llms-only patterns + featured pages
                if (resp.llms_only) {
                    var llmsOnlyTA = document.getElementById('orch-llms-only-exclude');
                    if (llmsOnlyTA) llmsOnlyTA.value = resp.llms_only.exclude_patterns || '';
                    if (typeof resp.llms_only.featured_max === 'number') FEATURED_MAX = resp.llms_only.featured_max;
                    featuredState = (resp.llms_only.featured_pages || []).slice();
                    renderFeaturedChips();
                    updateFeaturedCounter();
                }

                // Footer-bar respect_noindex
                if ($respectNoindex) $respectNoindex.checked = !!resp.respect_noindex;
            }
        }).catch(function () { /* silent */ });

        // Identity profile (separate call)
        ajaxPost('identity_get', {}).then(function (resp) {
            if ($loading) $loading.style.display = 'none';
            if ($form)    $form.style.display = 'block';
            if (resp && resp.success && resp.profile) {
                fillIdentity(resp.profile);
                identityRefreshPreview();
                // Stage 2: load industry helpers if profile has an industry value
                if (resp.profile.industry && typeof refreshIndustryHelpers === 'function') {
                    currentIndustry = resp.profile.industry;
                    refreshIndustryHelpers(resp.profile.industry);
                }
            } else {
                fillIdentity({});
            }
        }).catch(function (err) {
            if ($loading) $loading.textContent = 'Errore caricamento profilo: ' + (err && err.message ? err.message : 'sconosciuto');
        });
    }

    function identitySave() {
        var payload = collectPayload();
        setStatus('loading', 'Salvataggio in corso…');
        ajaxPost('identity_save', { payload: JSON.stringify(payload) }).then(function (resp) {
            if (resp && (resp.success || resp.profile)) {
                setStatus('success', 'Settings salvati. /llms.txt rigenerato al prossimo accesso.');
                if (resp.profile) fillIdentity(resp.profile);
                identityRefreshPreview();
            } else {
                var msg = (resp && (resp.message || resp.error)) || 'Errore salvataggio.';
                setStatus('error', String(msg));
            }
        }).catch(function (err) {
            setStatus('error', 'Errore di rete: ' + (err && err.message ? err.message : 'sconosciuto'));
        });
    }

    function identityRefreshPreview() {
        // 3.35.53: redirect to the per-accordion llms preview (top-level pane removed).
        // After save, the llms preview reflects the new content; force a fresh fetch.
        previewLoad('llms', true);
    }

    function renderRegenReport(report) {
        if (!report) return '';
        var lines = [];
        if (report.timestamp) lines.push('Rigenerato: ' + report.timestamp);
        if (report.toggle_state_before && report.toggle_state_after) {
            if (report.toggle_state_before === 'off' && report.toggle_state_after === 'on') {
                lines.push('Toggle dinamico: AUTO-ENABLED (era OFF)');
            } else {
                lines.push('Toggle dinamico: ' + report.toggle_state_after);
            }
        }
        var removed = report.static_files_removed || [];
        if (removed.length > 0) {
            lines.push('File statici legacy backuppati e rimossi:');
            removed.forEach(function (it) {
                lines.push('  · ' + (it.path || it) + ' → ' + (it.backup || ''));
            });
        } else {
            lines.push('Nessun file statico legacy trovato.');
        }
        var purges = [];
        if (report.transients_flushed && report.transients_flushed.length) purges.push(report.transients_flushed.length + ' transient');
        if (report.object_cache_flushed) purges.push('object cache');
        if (report.rocket_purged) purges.push('WP Rocket');
        if (report.w3tc_purged) purges.push('W3 Total Cache');
        if (report.wpsc_purged) purges.push('WP Super Cache');
        if (report.rewrite_flushed) purges.push('rewrite rules');
        if (purges.length) lines.push('Cache purgate: ' + purges.join(', '));
        if (report.sample_status) {
            lines.push('Sample fetch: HTTP ' + report.sample_status + (report.sample_generator ? ' · generator=' + report.sample_generator : '') + (report.sample_cache ? ' · cache=' + report.sample_cache : ''));
        }
        if (report.errors && report.errors.length) {
            lines.push('⚠️ Errori non fatali:');
            report.errors.forEach(function (e) { lines.push('  · ' + e); });
        }
        return lines.join('\n');
    }

    function identityFlush() {
        setStatus('loading', 'Rigenerazione in corso…');
        ajaxPost('regenerate_llms', {}).then(function (resp) {
            if (!resp || !resp.success) {
                var msg = (resp && (resp.message || resp.error)) || 'Errore rigenerazione.';
                setStatus('error', String(msg));
                return;
            }
            var report = resp.report || {};
            var summary = renderRegenReport(report);
            $status.className = 'orch-identity-status-bar is-success';
            $status.textContent = '/llms.txt rigenerato.';
            // 3.35.53: regen report goes into the /llms.txt accordion preview content area.
            var llmsBox = document.querySelector('[data-orch-preview="llms"]');
            if (llmsBox) {
                var content = '— diagnostica rigenerazione —\n' + summary;
                if (report.sample) {
                    content += '\n\n— sample (prime 500 char di ' + (report.sample_url || '/llms.txt') + ') —\n\n' + report.sample;
                } else {
                    content += '\n\n(sample non disponibile — vedi errori sopra)';
                }
                setPreviewContent(llmsBox, content, 'language-markdown');
                // Refresh sitemap preview too — both were regenerated
                setTimeout(function () { previewLoad('sitemap', true); }, 200);
            }
        }).catch(function (err) {
            setStatus('error', 'Errore di rete: ' + (err && err.message ? err.message : 'sconosciuto'));
        });
    }

    // ============ Stage 1.5 final (3.35.53) — Preview boxes ============
    function formatBytes(n) {
        n = Number(n) || 0;
        if (n < 1024) return n + ' B';
        if (n < 1024 * 1024) return (n / 1024).toFixed(1) + ' KB';
        return (n / 1024 / 1024).toFixed(2) + ' MB';
    }

    function setPreviewMeta(box, html) {
        var meta = box.querySelector('.orch-preview-meta');
        if (meta) meta.innerHTML = html;
    }

    function setPreviewContent(box, text, langClass) {
        var pre = box.querySelector('.orch-preview-content');
        var code = pre.querySelector('code');
        if (langClass) {
            pre.className = 'orch-preview-content ' + langClass;
            code.className = langClass;
        }
        code.textContent = text || '';
        pre.setAttribute('data-orch-preview-loaded', 'true');
        if (window.Prism && langClass && langClass.indexOf('language-') === 0) {
            try { window.Prism.highlightElement(code); } catch (e) { /* silent */ }
        }
    }

    function renderPreviewSchemaDetection(box, resp) {
        var det = box.querySelector('.orch-preview-detection');
        if (!det) return;
        var mode = (resp.mode || 'auto');
        var resolved = (resp.active_strategy || resp.resolved_mode || mode);
        var providers = resp.detected_providers || [];
        var competitors = providers.filter(function (p) { return p.plugin !== 'WordPress Core'; });
        var html = '<strong>Modalità:</strong> ' + escapeHtml(mode);
        if (mode === 'auto') {
            html += ' → <span class="orch-preview-detection-mode-badge is-' + escapeHtml(resolved) + '">ATTIVA: ' + escapeHtml(resolved.toUpperCase()) + '</span>';
        } else {
            html += ' <span class="orch-preview-detection-mode-badge is-' + escapeHtml(mode) + '">' + escapeHtml(mode.toUpperCase()) + '</span>';
        }
        html += '<div class="orch-preview-detection-providers">';
        if (providers.length === 0) {
            html += '🔎 Detection: nessun provider rilevato';
        } else {
            html += '🔎 Detection ambiente:';
            providers.forEach(function (p) {
                var types = (p.types_emitted || []).join(', ');
                html += '<br>  · <strong>' + escapeHtml(p.plugin) + '</strong>' + (types ? ': ' + escapeHtml(types) : '');
            });
        }
        if (competitors.length === 0 && resolved === 'replace') {
            html += '<br>✓ AEO Orchestra è il sole provider Schema.org';
        }
        html += '</div>';
        det.innerHTML = html;
    }

    var PREVIEW_RENDERERS = {
        'head': function (box, resp) {
            var meta = 'Size: <strong>' + formatBytes(resp.size || 0) + '</strong>';
            if (resp.parsed && resp.parsed.title) meta += ' · title: <code>' + escapeHtml(resp.parsed.title) + '</code>';
            if (resp.parsed && resp.parsed.canonical) meta += ' · canonical: <code>' + escapeHtml(resp.parsed.canonical) + '</code>';
            if (resp.parsed && resp.parsed.robots) meta += ' · robots: <code>' + escapeHtml(resp.parsed.robots) + '</code>';
            setPreviewMeta(box, meta);
            setPreviewContent(box, resp.html || '', 'language-markup');
        },
        'sitemap': function (box, resp) {
            var meta = 'URL totali: <strong>' + (resp.total_urls || 0) + '</strong>';
            meta += ' · Sub-sitemap: <strong>' + (resp.sub_sitemaps || []).length + '</strong>';
            meta += ' · Size: <strong>' + formatBytes(resp.total_size || 0) + '</strong>';
            if (resp.generator) meta += ' · <code>' + escapeHtml(resp.generator) + '</code>';
            setPreviewMeta(box, meta);
            setPreviewContent(box, resp.xml_first_20 || '', 'language-markup');
        },
        'llms': function (box, resp) {
            var meta = 'Size: <strong>' + formatBytes(resp.size || 0) + '</strong>';
            meta += ' · Sezioni: <strong>' + (resp.section_count || 0) + '</strong>';
            meta += ' · Pagine in evidenza: <strong>' + (resp.featured_pages_count || 0) + '</strong>';
            if (resp.generator) meta += ' · <code>' + escapeHtml(resp.generator) + '</code>';
            setPreviewMeta(box, meta);
            setPreviewContent(box, resp.markdown || '', 'language-markdown');
        },
        'llms-full': function (box, resp) {
            var meta = 'Size totale: <strong>' + formatBytes(resp.total_size || 0) + '</strong>';
            meta += ' · Sezioni: <strong>' + (resp.section_count || 0) + '</strong>';
            if ((resp.total_size || 0) > 1500) meta += ' · <em>(prime 1500 char)</em>';
            setPreviewMeta(box, meta);
            setPreviewContent(box, resp.markdown_first_1500 || '', 'language-markdown');
        },
        'schema': function (box, resp) {
            var meta = 'Strategy: <strong>' + escapeHtml(resp.active_strategy || resp.resolved_mode || 'replace') + '</strong>';
            var types = (resp.parsed && resp.parsed.types) || [];
            meta += ' · Tipi: ' + (types.length ? types.map(escapeHtml).join(', ') : '<em>none</em>');
            meta += ' · Graph nodes: <strong>' + ((resp.parsed && resp.parsed.graph_count) || 0) + '</strong>';
            setPreviewMeta(box, meta);
            setPreviewContent(box, resp.jsonld || '', 'language-json');
            renderPreviewSchemaDetection(box, resp);
        }
    };

    function previewLoad(output, force) {
        var box = document.querySelector('[data-orch-preview="' + output + '"]');
        if (!box) return;
        var meta = box.querySelector('.orch-preview-meta');
        if (meta) meta.innerHTML = '<span class="orch3-muted">⏳ Caricamento…</span>';
        var endpoint = 'preview_' + output.replace('-', '_');
        var args = {};
        if (force) args.bust = '1';
        ajaxPost(endpoint, args).then(function (resp) {
            if (!resp || resp.error) {
                var msg = (resp && (resp.message || resp.error)) || 'Errore caricamento preview.';
                if (meta) meta.innerHTML = '<span style="color:#b91c1c;">⚠️ ' + escapeHtml(String(msg)) + '</span>';
                return;
            }
            var renderer = PREVIEW_RENDERERS[output];
            if (renderer) renderer(box, resp);
        }).catch(function (err) {
            if (meta) meta.innerHTML = '<span style="color:#b91c1c;">⚠️ Network error: ' + escapeHtml(String(err && err.message || 'unknown')) + '</span>';
        });
    }

    document.querySelectorAll('[data-orch-preview-refresh]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var output = btn.getAttribute('data-orch-preview-refresh');
            previewLoad(output, true);
        });
    });

    // First-time auto-load on accordion expand for the open llms accordion
    var firstAutoLoadDone = {};
    document.querySelectorAll('details.orch-output-accordion').forEach(function (details) {
        details.addEventListener('toggle', function () {
            if (!details.open) return;
            var box = details.querySelector('[data-orch-preview]');
            if (!box) return;
            var output = box.getAttribute('data-orch-preview');
            if (firstAutoLoadDone[output]) return;
            firstAutoLoadDone[output] = true;
            previewLoad(output, false);
        });
        // Auto-load once for accordions that are open by default
        if (details.open) {
            var box = details.querySelector('[data-orch-preview]');
            if (box && !firstAutoLoadDone[box.getAttribute('data-orch-preview')]) {
                firstAutoLoadDone[box.getAttribute('data-orch-preview')] = true;
                // Defer to next tick so AJAX URL/nonce are ready
                setTimeout(function () { previewLoad(box.getAttribute('data-orch-preview'), false); }, 100);
            }
        }
    });

        if ($btnSave)    $btnSave.addEventListener('click', identitySave);
    if ($btnFlush)   $btnFlush.addEventListener('click', identityFlush);

    var activePanel = document.querySelector('.orch-native-tab-panel.is-active');
    if (activePanel && activePanel.getAttribute('data-orch-tab') === 'content' && !window.__orchIdentityLoaded) {
        window.__orchIdentityLoaded = true;
        identityLoad();
    }

    // 3.35.79.5: expose helpers for consumers in subsequent IIFEs
    // (_subPanelConfigList consumed by 13 sub-panel populators added in 3.35.71/72/73/79.4)
    window._subPanelConfigList = _subPanelConfigList;
    window._summaryRow = _summaryRow;

})();

/* 3.35.68 D.5 JS */
(function($) {
    if (!$ || typeof window.seoAeoOrchestra === 'undefined') return;
    var ajaxurl = window.ajaxurl || (window.seoAeoOrchestra && window.seoAeoOrchestra.ajaxurl);
    var nonce = window.seoAeoOrchestra && window.seoAeoOrchestra.nonce;
    if (!ajaxurl || !nonce) return;

    function escapeHtml(s) { return String(s || '').replace(/[&<>"']/g, function(c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    }); }

    function highlightHtml(s) {
        // Basic syntax highlight: tag names, attr names, attr values
        return escapeHtml(s)
            .replace(/(&lt;\/?)([a-zA-Z][a-zA-Z0-9-]*)/g, '$1<span class="tag-name">$2</span>')
            .replace(/([a-zA-Z-]+)(=)(&quot;[^&]*?&quot;)/g, '<span class="attr-name">$1</span>$2<span class="attr-value">$3</span>');
    }

    /* D.5 — Live preview <head> */
    function refreshPreview() {
        var $btn = $('#orch-preview-refresh-btn').prop('disabled', true).text('🔄 …');
        var $code = $('#orch-head-preview-code code');
        var $meta = $('#orch-head-preview-meta');
        $code.text('Caricamento…');
        $.post(ajaxurl, {
            action: 'orch_head_preview',
            nonce: nonce,
        }).done(function(resp) {
            if (!resp || resp.error) {
                $code.text('Errore: ' + (resp && resp.message ? resp.message : 'unknown'));
                return;
            }
            var orchOnly = $('#orch-preview-orch-only').is(':checked');
            var raw = orchOnly ? (resp.head_orch_only || '') : (resp.head_full || '');
            $code.html(highlightHtml(raw.trim() || '(empty)'));
            $meta.show().text('Source: ' + resp.url + ' · HTTP ' + resp.http_code + ' · ' + resp.tags_count + ' tag · ' + resp.head_size + 'B · ' + resp.fetched_at + ' UTC');
        }).fail(function(xhr) {
            $code.text('Errore rete (HTTP ' + xhr.status + ')');
        }).always(function() {
            $btn.prop('disabled', false).text('🔄 Aggiorna');
        });
    }

    $(document).off('click.orchPreview').on('click.orchPreview', '#orch-preview-refresh-btn', refreshPreview);

    // 3.35.72 D.5b: live debounce on settings inputs
    var __previewDebounceTimer = null;
    function _schedulePreviewRefresh() {
        if (!$('#orch-preview-autorefresh').is(':checked')) return;
        if (__previewDebounceTimer) clearTimeout(__previewDebounceTimer);
        __previewDebounceTimer = setTimeout(function() {
            // Only refresh if the head preview panel exists and is visible (head accordion open)
            if ($('#orch-head-preview-panel').length === 0) return;
            var headAcc = document.querySelector('[data-orch-output="head"]');
            if (headAcc && !headAcc.open) return;
            refreshPreview();
        }, 300);
    }
    $(document).off('input.orchPreviewDebounce change.orchPreviewDebounce')
        .on('input.orchPreviewDebounce change.orchPreviewDebounce',
            '[data-orch-output="head"] input, [data-orch-output="head"] select, [data-orch-output="head"] textarea',
            _schedulePreviewRefresh);

    $(document).off('change.orchPreviewToggle').on('change.orchPreviewToggle', '#orch-preview-orch-only', function() {
        // Re-render based on toggle without re-fetching
        var $btn = $('#orch-preview-refresh-btn');
        if ($btn.length && !$btn.prop('disabled')) refreshPreview();
    });

    /* 3.35.68 — Status summary loaders (basic 3-row scaffolds) */
    function row(icon, label) {
        return '<li><span class="orch-acc-status-icon">' + icon + '</span><span>' + escapeHtml(label) + '</span></li>';
    }
    function setSummary(kind, items) {
        var $ul = $('#orch-status-summary-' + kind + ' .orch-acc-status-summary-list');
        if (!$ul.length) return;
        $ul.html(items.join(''));
    }

    function loadSchemaSummary() {
        $.post(ajaxurl, {action: 'seo_aeo_orchestra_native_schema_status', nonce: nonce}).done(function(s) {
            if (!s || s.error) { setSummary('schema', [row('✗', 'Stato non disponibile')]); return; }

            // 3.35.71: clickable rows with jump-to-fix
            var rowsHtml = [];
            function addRow(icon, text, target, isWarn) {
                var li = '<li role="button" tabindex="0" data-orch-schema-jump="' + (target || '') + '"' + (isWarn ? ' class="is-warn"' : '') + '>';
                li += '<span class="orch-acc-status-icon">' + icon + '</span>';
                li += '<span>' + escapeHtml(text) + '</span>';
                if (target) li += '<span class="orch-fix-pill" aria-hidden="true">Fix ⤴</span>';
                li += '</li>';
                rowsHtml.push(li);
            }
            var enabled = s.enabled || s.is_active;
            addRow(enabled ? '✓' : '⚠', 'Schema.org native: ' + (enabled ? 'attivo' : 'disattivato'), enabled ? '' : 'mode', !enabled);
            addRow('•', 'Smart Override mode: ' + (s.mode || 'auto'), 'mode', false);
            var types_total = s.types_count || (s.role_types_count || 14);
            addRow('•', 'Schema types per role: ' + types_total + ' ruoli configurati', 'cpt', false);

            // Organization status
            var orgLogoMissing = s.organization && !s.organization.logo;
            if (s.organization) {
                var label = 'Organization: ' + (s.organization.name || 'configurata');
                if (s.organization.vat) label += ' · ' + s.organization.vat;
                addRow(orgLogoMissing ? '⚠' : '✓', label, 'org', orgLogoMissing);
                if (orgLogoMissing) addRow('⚠', 'logo Organization: missing → click per aggiungere', 'org', true);
            } else {
                addRow('⚠', 'Organization: non configurata → click per impostare', 'org', true);
            }

            // BreadcrumbList
            var bc_on = (s.breadcrumb_enabled === true || s.breadcrumb_enabled === 1 || s.breadcrumb_enabled === '1');
            addRow(bc_on ? '✓' : '·', 'BreadcrumbList: ' + (bc_on ? 'emesso su tutte le pagine non-home' : 'disabilitato'), 'bc', false);

            var $ul = $('#orch-status-summary-schema .orch-acc-status-summary-list');
            $ul.html(rowsHtml.join(''));

            // 3.35.79.4: populate 4 orphan ✅ Configurazione attiva blocks
            window._subPanelConfigList('schema-mode', [
                'Modalità: <strong>' + escapeHtml(s.mode || 'auto') + '</strong>',
                'Schema.org native: ' + (s.enabled || s.is_active ? '✓ attivo' : '✗ disattivato'),
            ]);
            var typesTotal = s.types_count || s.role_types_count || 14;
            window._subPanelConfigList('schema-cpt', [
                'Schema types per role: <strong>' + typesTotal + ' ruoli</strong> configurati',
                '<span class="orch3-muted">Override CPT: tabella in 🛠 Personalizza</span>',
            ]);
            if (s.organization) {
                var orgItems = ['Nome: <strong>' + escapeHtml(s.organization.name || 'configurata') + '</strong>'];
                if (s.organization.vat) orgItems.push('VAT: <code>' + escapeHtml(s.organization.vat) + '</code>');
                if (s.organization.logo) {
                    orgItems.push('Logo: ✓ caricato');
                } else {
                    orgItems.push('<a href="#" data-orch-scroll-target="orch-org-logo-url" class="orch-empty-link">⚠ Logo: missing → click per aggiungere</a>');
                }
                window._subPanelConfigList('schema-org', orgItems);
            } else {
                window._subPanelConfigList('schema-org', [
                    '<a href="#" data-orch-scroll-target="orch-org-logo-url" class="orch-empty-link">⚠ Organization non configurata → click per impostare</a>'
                ]);
            }
            var bcOn = (s.breadcrumb_enabled === true || s.breadcrumb_enabled === 1 || s.breadcrumb_enabled === '1');
            window._subPanelConfigList('schema-bc', [
                'BreadcrumbList: ' + (bcOn ? '✓ emesso su tutte le pagine non-home' : '· disabilitato'),
                bcOn ? '<span class="orch3-muted">Separator: visibile in 🛠 Personalizza (default Auto)</span>'
                     : '<a href="#" data-orch-scroll-target="orch-breadcrumb-enabled" class="orch-empty-link">Attiva BreadcrumbList</a>',
            ]);

            // Add hint
            if (!$('#orch-status-summary-schema .orch-acc-status-summary-hint').length) {
                $('#orch-status-summary-schema .orch-acc-status-summary-title').after(
                    '<p class="orch-acc-status-summary-hint orch3-muted" style="margin:0 0 6px;font-size:11px;">💡 Clicca su una voce per saltare alla sezione modificabile.</p>'
                );
            }
        });
    }

    // 3.35.71: schema click-to-fix jump handler
    var SCHEMA_PANEL_BY_TARGET = {
        mode: 'orch-schema-mode-panel',
        cpt:  'orch-schema-cpt-overrides',
        org:  'orch-schema-org-defaults',
        bc:   'orch-schema-breadcrumb',
    };
    function _schemaJump(target) {
        var panelId = SCHEMA_PANEL_BY_TARGET[target];
        if (!panelId) return;
        var panel = document.getElementById(panelId);
        if (!panel) return;
        var schemaAcc = document.querySelector('[data-orch-output="schema"]');
        if (schemaAcc && !schemaAcc.open) schemaAcc.open = true;
        panel.open = true;
        var advanced = panel.querySelector('details.orch-advanced-options');
        if (advanced) advanced.open = true;
        setTimeout(function() {
            try { panel.scrollIntoView({block: 'center', behavior: 'smooth'}); } catch (e) {}
            panel.classList.add('orch-pulse-warn');
            setTimeout(function() { panel.classList.remove('orch-pulse-warn'); }, 2100);
        }, 220);
    }
    $(document).off('click.orchSchemaJump').on('click.orchSchemaJump', '[data-orch-schema-jump]', function() {
        var t = $(this).data('orch-schema-jump');
        if (t) _schemaJump(t);
    });
    $(document).off('keydown.orchSchemaJump').on('keydown.orchSchemaJump', '[data-orch-schema-jump]', function(e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        e.preventDefault();
        var t = $(this).data('orch-schema-jump');
        if (t) _schemaJump(t);
    });
    function loadSitemapSummary() {
        $.post(ajaxurl, {action: 'seo_aeo_orchestra_native_sitemap_status', nonce: nonce}).done(function(s) {
            if (!s || s.error) { setSummary('sitemap', [row('✗', 'Stato non disponibile')]); return; }
            var rowsHtml = [];
            function addRow(icon, text, target, isWarn) {
                var li = '<li role="button" tabindex="0" data-orch-sitemap-jump="' + (target || '') + '"' + (isWarn ? ' class="is-warn"' : '') + '>';
                li += '<span class="orch-acc-status-icon">' + icon + '</span>';
                li += '<span>' + escapeHtml(text) + '</span>';
                if (target) li += '<span class="orch-fix-pill" aria-hidden="true">Fix ⤴</span>';
                li += '</li>';
                rowsHtml.push(li);
            }
            addRow(s.enabled ? '✓' : '⚠', 'Sitemap.xml: ' + (s.enabled ? 'attiva su /sitemap.xml' : 'disattivata'), 'index', !s.enabled);
            if (s.stats) {
                var typesIn = 0; var totalUrls = 0;
                for (var pt in s.stats) {
                    typesIn++;
                    if (s.stats[pt] && s.stats[pt].count) totalUrls += s.stats[pt].count;
                }
                addRow('•', 'Index types: ' + typesIn + ' sitemap', 'index', false);
                if (totalUrls) addRow('•', 'URL totali: ' + totalUrls + ' negli index', 'index', false);
            }
            addRow('•', 'Priority: range 0.3 (low-priority pages) → 1.0 (homepage)', 'priority', false);
            addRow('•', 'Changefreq: range monthly (legali) → weekly (blog)', 'changefreq', false);

            var $ul = $('#orch-status-summary-sitemap .orch-acc-status-summary-list');
            $ul.html(rowsHtml.join(''));

            // 3.35.79.4: populate 3 orphan ✅ Configurazione attiva blocks
            if (s.enabled && s.stats) {
                var typesIn = 0; var totalUrls = 0;
                for (var pt in s.stats) {
                    typesIn++;
                    if (s.stats[pt] && s.stats[pt].count) totalUrls += s.stats[pt].count;
                }
                window._subPanelConfigList('sitemap-index', [
                    '<strong>' + typesIn + ' sitemap</strong> nell\'index (CPT pubblici)',
                    'URL totali: <strong>' + totalUrls + '</strong>',
                    'sitemap-index: <a href="' + escapeHtml(s.sitemap_url || '/sitemap.xml') + '" target="_blank">' + escapeHtml(s.sitemap_url || '/sitemap.xml') + '</a>',
                ]);
            } else {
                window._subPanelConfigList('sitemap-index', [
                    '<a href="#" data-orch-scroll-target="orch-sitemap-takeover" class="orch-empty-link">⚠ Sitemap.xml disattivata → click per attivare</a>'
                ]);
            }
            // Priority + Changefreq read from _sitemapOverrides global (loaded by loadSitemapRoleSettings)
            var prioOverridden = (window._sitemapOverrides && window._sitemapOverrides.priority) ? Object.keys(window._sitemapOverrides.priority).length : 0;
            var cfOverridden = (window._sitemapOverrides && window._sitemapOverrides.changefreq) ? Object.keys(window._sitemapOverrides.changefreq).length : 0;
            var niOverridden = (window._sitemapOverrides && window._sitemapOverrides.noindex) ? Object.keys(window._sitemapOverrides.noindex).length : 0;
            window._subPanelConfigList('sitemap-priority', [
                'Range default: <strong>0.0 → 1.0</strong> (homepage 1.0, legali 0.3)',
                prioOverridden > 0 ? '<strong>' + prioOverridden + ' role override attivi</strong> (visibili in 🛠 Personalizza)' : '<span class="orch3-muted">Nessun override — uso default per tutti i ruoli</span>',
            ]);
            window._subPanelConfigList('sitemap-changefreq', [
                'Range default: monthly (legali) → weekly (blog) → daily (homepage)',
                cfOverridden > 0 ? '<strong>' + cfOverridden + ' changefreq override</strong> attivi' : '<span class="orch3-muted">Nessun override changefreq</span>',
                niOverridden > 0 ? '<strong>' + niOverridden + ' role esclusi</strong> dal sitemap (noindex)' : '<span class="orch3-muted">Nessun ruolo escluso</span>',
            ]);

            if (!$('#orch-status-summary-sitemap .orch-acc-status-summary-hint').length) {
                $('#orch-status-summary-sitemap .orch-acc-status-summary-title').after(
                    '<p class="orch-acc-status-summary-hint orch3-muted" style="margin:0 0 6px;font-size:11px;">💡 Clicca su una voce per saltare alla sezione modificabile.</p>'
                );
            }
        });
    }

    // 3.35.72: Sitemap click-to-fix
    var SITEMAP_PANEL_BY_TARGET = {
        index:      'orch-sitemap-index-types',
        priority:   'orch-sitemap-priority',
        changefreq: 'orch-sitemap-changefreq',
    };
    function _sitemapJump(target) {
        var panelId = SITEMAP_PANEL_BY_TARGET[target];
        if (!panelId) return;
        var panel = document.getElementById(panelId);
        if (!panel) return;
        var sitemapAcc = document.querySelector('[data-orch-output="sitemap"]');
        if (sitemapAcc && !sitemapAcc.open) sitemapAcc.open = true;
        panel.open = true;
        var advanced = panel.querySelector('details.orch-advanced-options');
        if (advanced) advanced.open = true;
        setTimeout(function() {
            try { panel.scrollIntoView({block: 'center', behavior: 'smooth'}); } catch (e) {}
            panel.classList.add('orch-pulse-warn');
            setTimeout(function() { panel.classList.remove('orch-pulse-warn'); }, 2100);
        }, 220);
    }
    $(document).off('click.orchSitemapJump').on('click.orchSitemapJump', '[data-orch-sitemap-jump]', function() {
        var t = $(this).data('orch-sitemap-jump');
        if (t) _sitemapJump(t);
    });
    $(document).off('keydown.orchSitemapJump').on('keydown.orchSitemapJump', '[data-orch-sitemap-jump]', function(e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        e.preventDefault();
        var t = $(this).data('orch-sitemap-jump');
        if (t) _sitemapJump(t);
    });
    function loadLlmsSummary() {
        $.post(ajaxurl, {action: 'seo_aeo_orchestra_native_llms_txt_status', nonce: nonce}).done(function(s) {
            if (!s || s.error) { setSummary('llms', [row('✗', 'Stato non disponibile')]); return; }
            var rowsHtml = [];
            function addRow(icon, text, target, isWarn) {
                var li = '<li role="button" tabindex="0" data-orch-llms-jump="' + (target || '') + '"' + (isWarn ? ' class="is-warn"' : '') + '>';
                li += '<span class="orch-acc-status-icon">' + icon + '</span><span>' + escapeHtml(text) + '</span>';
                if (target) li += '<span class="orch-fix-pill" aria-hidden="true">Fix ⤴</span>';
                li += '</li>';
                rowsHtml.push(li);
            }
            addRow(s.enabled ? '✓' : '⚠', 'llms.txt: ' + (s.enabled ? 'attivo su /llms.txt' : 'disattivato'), 'strategy', !s.enabled);
            addRow('•', 'Strategy mode: ' + (s.mode || 'curated'), 'strategy', false);
            var fp = (s.featured_pages_count !== undefined) ? s.featured_pages_count : 0;
            var fpWarn = fp === 0;
            addRow(fpWarn ? '⚠' : '•', 'Featured pages: ' + fp + (fp ? ' selezionate' : ' — nessuna selezionata'), 'featured', fpWarn);
            var aboutLen = (s.about_length || 0);
            var aboutWarn = aboutLen < 200 || aboutLen > 500;
            addRow(aboutWarn ? '⚠' : '•', 'About strategico: ' + aboutLen + ' caratteri (target 200-500)', 'about', aboutWarn);

            var $ul = $('#orch-status-summary-llms .orch-acc-status-summary-list');
            $ul.html(rowsHtml.join(''));

            // 3.35.79.4: populate 3 orphan ✅ Configurazione attiva blocks
            window._subPanelConfigList('llms-strategy', [
                'Strategy mode: <strong>' + escapeHtml(s.mode || 'curated') + '</strong>',
                'llms.txt: ' + (s.enabled ? '✓ attivo su /llms.txt' : '✗ disattivato'),
            ]);
            var fpCount = s.featured_pages_count || 0;
            if (fpCount > 0) {
                window._subPanelConfigList('llms-featured', [
                    '<strong>' + fpCount + ' pagine</strong> selezionate manualmente',
                    fpCount < 5 ? '<span class="orch3-muted">⚠ numero basso (consigliato 10-20)</span>' : (fpCount > 30 ? '<span class="orch3-muted">⚠ numero alto (best practice max 20-30)</span>' : '<span class="orch3-muted">✓ in target</span>'),
                ]);
            } else {
                window._subPanelConfigList('llms-featured', [
                    '<a href="#" data-orch-scroll-target="orch-featured-auto-suggest" class="orch-empty-link">⚠ Nessuna pagina featured → click per Auto-suggest</a>'
                ]);
            }
            var aboutLen = s.about_length || 0;
            if (aboutLen === 0) {
                window._subPanelConfigList('llms-about', [
                    '<a href="#" data-orch-scroll-target="orch-id-about" class="orch-empty-link">⚠ About strategico non configurato → click per scrivere</a>',
                ]);
            } else {
                var aboutStatus = aboutLen < 200 ? '<span class="orch3-muted">⚠ troppo corto (target 200-500)</span>'
                                : aboutLen > 500 ? '<span class="orch3-muted">⚠ troppo lungo (target 200-500)</span>'
                                : '<span class="orch3-muted">✓ in target 200-500</span>';
                window._subPanelConfigList('llms-about', [
                    'Lunghezza: <strong>' + aboutLen + ' caratteri</strong>',
                    aboutStatus,
                ]);
            }

            if (!$('#orch-status-summary-llms .orch-acc-status-summary-hint').length) {
                $('#orch-status-summary-llms .orch-acc-status-summary-title').after(
                    '<p class="orch-acc-status-summary-hint orch3-muted" style="margin:0 0 6px;font-size:11px;">💡 Clicca su una voce per saltare alla sezione modificabile.</p>'
                );
            }
        });
    }
    function loadLlmsFullSummary() {
        $.post(ajaxurl, {action: 'seo_aeo_orchestra_native_llms_txt_status', nonce: nonce}).done(function(s) {
            if (!s || s.error) { setSummary('llmsfull', [row('✗', 'Stato non disponibile')]); return; }
            var rowsHtml = [];
            function addRow(icon, text, target, isWarn) {
                var li = '<li role="button" tabindex="0" data-orch-llmsfull-jump="' + (target || '') + '"' + (isWarn ? ' class="is-warn"' : '') + '>';
                li += '<span class="orch-acc-status-icon">' + icon + '</span><span>' + escapeHtml(text) + '</span>';
                if (target) li += '<span class="orch-fix-pill" aria-hidden="true">Fix ⤴</span>';
                li += '</li>';
                rowsHtml.push(li);
            }
            addRow(s.enabled ? '✓' : '⚠', 'llms-full.txt: ' + (s.enabled ? 'attivo su /llms-full.txt' : 'disattivato'), 'autogen', !s.enabled);
            var cpts = s.full_post_types ? s.full_post_types.length : 2;
            addRow('•', 'CPT inclusi: ' + cpts + ' tipi (default: Articoli + Pagine)', 'autogen', false);
            if (s.full_size) addRow('•', 'Dimensione: ' + s.full_size, 'content', false);
            else addRow('•', 'Auto-generated da Featured pages di llms.txt', 'autogen', false);

            var $ul = $('#orch-status-summary-llmsfull .orch-acc-status-summary-list');
            $ul.html(rowsHtml.join(''));

            // 3.35.79.4: populate 2 orphan ✅ Configurazione attiva blocks
            var cpts = s.full_post_types ? s.full_post_types.length : 2;
            window._subPanelConfigList('llmsfull-autogen', [
                'Auto-generation: ' + (s.enabled ? '<strong>✓ ON</strong>' : '✗ OFF'),
                'CPT inclusi: <strong>' + cpts + ' tipi</strong>' + (cpts === 2 ? ' <span class="orch3-muted">(default: Articoli + Pagine)</span>' : ''),
                s.full_size ? 'Dimensione attuale: <strong>' + escapeHtml(s.full_size) + '</strong>' : '<span class="orch3-muted">Generato dinamicamente da Featured pages</span>',
            ]);
            var excludeCount = s.exclude_patterns_count || 0;
            window._subPanelConfigList('llmsfull-content', [
                'Body markdown: <strong>incluso</strong> <span class="orch3-muted">(default)</span>',
                'Esclusioni custom: ' + (excludeCount > 0 ? '<strong>' + excludeCount + ' pattern</strong> attivi' : '<span class="orch3-muted">nessuno</span>'),
            ]);

            if (!$('#orch-status-summary-llmsfull .orch-acc-status-summary-hint').length) {
                $('#orch-status-summary-llmsfull .orch-acc-status-summary-title').after(
                    '<p class="orch-acc-status-summary-hint orch3-muted" style="margin:0 0 6px;font-size:11px;">💡 Clicca su una voce per saltare alla sezione modificabile.</p>'
                );
            }
        });
    }

    // 3.35.73: llms + llmsfull click-to-fix
    var LLMS_PANEL_BY_TARGET = {
        strategy: 'orch-llms-strategy',
        featured: 'orch-llms-featured-info',
        about:    'orch-llms-about-info',
    };
    var LLMSFULL_PANEL_BY_TARGET = {
        autogen: 'orch-llmsfull-autogen',
        content: 'orch-llmsfull-content',
    };
    function _genericJump(panelMap, target, accordionSelector) {
        var panelId = panelMap[target];
        if (!panelId) return;
        var panel = document.getElementById(panelId);
        if (!panel) return;
        var acc = document.querySelector(accordionSelector);
        if (acc && !acc.open) acc.open = true;
        panel.open = true;
        var advanced = panel.querySelector('details.orch-advanced-options');
        if (advanced) advanced.open = true;
        setTimeout(function() {
            try { panel.scrollIntoView({block: 'center', behavior: 'smooth'}); } catch (e) {}
            panel.classList.add('orch-pulse-warn');
            setTimeout(function() { panel.classList.remove('orch-pulse-warn'); }, 2100);
        }, 220);
    }
    $(document).off('click.orchLlmsJump').on('click.orchLlmsJump', '[data-orch-llms-jump]', function() {
        // llms.txt accordion uses class 'open' attr — match by closest details
        _genericJump(LLMS_PANEL_BY_TARGET, $(this).data('orch-llms-jump'),
                     '#orch-status-summary-llms');
        // Force open the llms accordion (it's the third orch-output-accordion)
        var llmsAccordion = $('#orch-status-summary-llms').closest('.orch-output-accordion').get(0);
        if (llmsAccordion) llmsAccordion.open = true;
    });
    $(document).off('click.orchLlmsFullJump').on('click.orchLlmsFullJump', '[data-orch-llmsfull-jump]', function() {
        _genericJump(LLMSFULL_PANEL_BY_TARGET, $(this).data('orch-llmsfull-jump'),
                     '[data-orch-output="llms-full"]');
    });

    // Defer load until DOM ready
    $(function() {
        if ($('#orch-status-summary-schema').length) loadSchemaSummary();
        if ($('#orch-status-summary-sitemap').length) loadSitemapSummary();
        if ($('#orch-status-summary-llms').length) loadLlmsSummary();
        if ($('#orch-status-summary-llmsfull').length) loadLlmsFullSummary();
    });
})(window.jQuery);


/* 3.35.70 — Hreflang sub-panel JS */
(function($) {
    if (!$ || typeof window.seoAeoOrchestra === 'undefined') return;
    var ajaxurl = window.ajaxurl || (window.seoAeoOrchestra && window.seoAeoOrchestra.ajaxurl);
    var nonce = window.seoAeoOrchestra && window.seoAeoOrchestra.nonce;
    if (!ajaxurl || !nonce) return;

    function escapeHtml(s) { return String(s || '').replace(/[&<>"']/g, function(c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    }); }

    function loadHreflangStatus() {
        $.post(ajaxurl, {action: 'orch_hreflang_status', nonce: nonce}).done(function(resp) {
            if (!resp || !resp.success) {
                $('#orch-hreflang-detection').html('<span style="color:#dc2626;">Errore: ' + escapeHtml((resp && resp.message) || 'unknown') + '</span>');
                return;
            }
            var $det = $('#orch-hreflang-detection');
            var $row = $('#orch-hreflang-toggle-row');
            var $tog = $('#orch-hreflang-enabled-toggle');
            var $prev = $('#orch-hreflang-preview');
            if (!resp.plugin) {
                $det.html('<span>⚪ Nessun plugin multilingua attivo. Sito mono-lingua: skip silenzioso (no hreflang emessi).</span>');
                $row.hide();
                $prev.hide();
                return;
            }
            var pluginNames = {wpml: 'WPML', polylang: 'Polylang', translatepress: 'TranslatePress'};
            var name = pluginNames[resp.plugin] || resp.plugin;
            var langs = resp.preview && resp.preview.tags ? resp.preview.tags.length : 0;
            $det.html('<span style="color:#10b981;">✓ ' + escapeHtml(name) + ' rilevato</span> · ' + langs + ' lingue · default: ' + (resp.preview && resp.preview.tags ? findDefault(resp.preview.tags) : 'n/a'));
            $row.show();
            $tog.prop('checked', resp.enabled);

            // 3.35.79.4: populate orphan ✅ Configurazione attiva block
            if (resp.plugin) {
                var hreflangItems = [
                    'Plugin multilingua: <strong>' + escapeHtml(name) + '</strong>',
                    'Lingue rilevate: ' + langs,
                    'Hreflang emission: ' + (resp.enabled ? '✓ attiva' : '✗ disattivata'),
                ];
                if (resp.preview && resp.preview.tags && resp.preview.tags.length) {
                    var defaultTag = null;
                    for (var i = 0; i < resp.preview.tags.length; i++) {
                        if (resp.preview.tags[i].is_default && resp.preview.tags[i].hreflang !== 'x-default') {
                            defaultTag = resp.preview.tags[i]; break;
                        }
                    }
                    if (defaultTag) hreflangItems.push('Lingua di default: ' + escapeHtml(defaultTag.hreflang));
                }
                window._subPanelConfigList('hreflang', hreflangItems);
            } else {
                // Plain text — sito monolingua è correct-state, non actionable
                window._subPanelConfigList('hreflang', [
                    '<span class="orch3-muted">⚪ Nessun plugin multilingua attivo — sito monolingua, hreflang non emesso (corretto).</span>'
                ]);
            }

            if (resp.preview && resp.preview.tags && resp.preview.tags.length) {
                var html = '<strong style="color:#94a3b8;">Preview per: ' + escapeHtml(resp.preview.post_title || '?') + '</strong>\n\n';
                resp.preview.tags.forEach(function(t) {
                    var marker = t.is_default ? ' <span style="color:#fbbf24;">[default]</span>' : '';
                    html += '&lt;link rel="alternate" hreflang="<span style="color:#86efac;">' + escapeHtml(t.hreflang) + '</span>" href="<span style="color:#fbbf24;">' + escapeHtml(t.href) + '</span>" /&gt;' + marker + '\n';
                });
                $prev.html(html).show();
            } else {
                $prev.hide();
            }
        }).fail(function() {
            $('#orch-hreflang-detection').text('Errore rete.');
        });
    }
    function findDefault(tags) {
        for (var i = 0; i < tags.length; i++) {
            if (tags[i].is_default && tags[i].hreflang !== 'x-default') return tags[i].hreflang;
        }
        return tags[0] ? tags[0].hreflang : '?';
    }

    $(document).off('change.orchHreflang').on('change.orchHreflang', '#orch-hreflang-enabled-toggle', function() {
        var enable = $(this).is(':checked') ? 1 : 0;
        $.post(ajaxurl, {action: 'orch_hreflang_toggle', nonce: nonce, enable: enable}).done(function() {
            loadHreflangStatus();
        });
    });

    // Lazy-load when sub-panel opens
    $(document).on('toggle', '#orch-head-hreflang', function() {
        if (this.open && !this.dataset.loaded) {
            this.dataset.loaded = '1';
            loadHreflangStatus();
        }
    });
    // Also trigger on first DOM ready if panel is already open
    $(function() {
        var p = document.getElementById('orch-head-hreflang');
        if (p && p.open) loadHreflangStatus();
    });
})(window.jQuery);


/* 3.35.72.1: Global media picker JS */
(function($) {
    if (!$ || typeof window.seoAeoOrchestra === 'undefined') return;

    function escapeHtml(s) { return String(s || '').replace(/[&<>"']/g, function(c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    }); }

    function aspectRatioWarning(actualRatio, expectedRatio, w, h) {
        var tolerance = 0.20;
        var deviation = Math.abs(actualRatio - expectedRatio) / expectedRatio;
        if (deviation > tolerance) {
            var expectedLabel = expectedRatio > 1.5 ? '1200×630 (1.91:1, OG)' : '1:1 (square, logo)';
            return '⚠ Aspect ratio non ottimale. Atteso: ' + expectedLabel + ' · Tua immagine: ' + w + '×' + h + ' (' + actualRatio.toFixed(2) + ':1). Potrebbe essere croppata.';
        }
        return null;
    }

    function refreshPreview(targetId) {
        var $row = $('[data-orch-img-target="' + targetId + '"]').first();
        var input = document.getElementById(targetId);
        if (!input || !$row.length) return;
        var url = (input.value || '').trim();
        var $prev = $('[data-orch-img-preview="' + targetId + '"]');
        var $remove = $row.find('.orch-media-remove-btn');
        var $val = $('[data-orch-img-validation="' + targetId + '"]');
        if (!url) {
            $prev.html('');
            $val.removeClass('orch-img-ok orch-img-warn orch-img-err').html('');
            $remove.hide();
            return;
        }
        $remove.show();
        var ratio = parseFloat($row.data('orch-img-ratio') || '1.91');
        var img = new Image();
        img.onload = function() {
            var w = img.naturalWidth, h = img.naturalHeight;
            $prev.html('<img src="' + escapeHtml(url) + '" alt="preview" /><div class="orch-image-preview-meta">' + w + '×' + h + ' · ' + (img.src.length) + 'B URL</div>');
            var warn = aspectRatioWarning(w / h, ratio, w, h);
            if (warn) {
                $val.removeClass('orch-img-ok orch-img-err').addClass('orch-img-warn').text(warn);
            } else {
                $val.removeClass('orch-img-warn orch-img-err').addClass('orch-img-ok').text('✓ Aspect ratio OK · ' + w + '×' + h);
            }
        };
        img.onerror = function() {
            $prev.html('');
            $val.removeClass('orch-img-ok orch-img-warn').addClass('orch-img-err').text('✗ Immagine non caricabile da questa URL.');
        };
        img.src = url;
    }

    $(document).off('click.orchMediaPicker').on('click.orchMediaPicker', '.orch-media-picker-btn', function(e) {
        e.preventDefault();
        if (typeof wp === 'undefined' || !wp.media) {
            alert('WP Media Library non disponibile. Ricarica la pagina.');
            return;
        }
        var targetId = $(this).data('orch-img-target');
        var title = $(this).data('orch-img-title') || 'Scegli immagine';
        var frame = wp.media({
            title: title,
            button: {text: 'Usa questa immagine'},
            multiple: false,
            library: {type: 'image'}
        });
        frame.on('select', function() {
            var att = frame.state().get('selection').first().toJSON();
            var input = document.getElementById(targetId);
            if (input) {
                input.value = att.url;
                $(input).trigger('input').trigger('change');
                refreshPreview(targetId);
            }
        });
        frame.open();
    });

    $(document).off('click.orchMediaRemove').on('click.orchMediaRemove', '.orch-media-remove-btn', function(e) {
        e.preventDefault();
        var targetId = $(this).data('orch-img-target');
        var input = document.getElementById(targetId);
        if (input) {
            input.value = '';
            $(input).trigger('input').trigger('change');
            refreshPreview(targetId);
        }
    });

    // Live re-preview when input value changes (manual paste)
    $(document).off('input.orchMediaInput change.orchMediaInput').on('input.orchMediaInput change.orchMediaInput', '.orch-image-input', function() {
        refreshPreview($(this).attr('id'));
    });

    // Initial preview on page load for already-populated fields
    $(function() {
        $('.orch-image-input').each(function() {
            refreshPreview($(this).attr('id'));
        });
    });
})(window.jQuery);


/* 3.35.74: Sitemap role settings JS */
(function($) {
    if (!$ || typeof window.seoAeoOrchestra === 'undefined') return;
    var ajaxurl = window.ajaxurl || (window.seoAeoOrchestra && window.seoAeoOrchestra.ajaxurl);
    var nonce = window.seoAeoOrchestra && window.seoAeoOrchestra.nonce;
    if (!ajaxurl || !nonce) return;
    function escHtml(s) { return String(s || '').replace(/[&<>"']/g, function(c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    }); }

    var ROLES = ['homepage','blog_index','about','contact','faq','quote_request','knowledge_guide','category_landing','service_page','product_page','blog_post','legal_privacy','legal_terms','custom','ignore'];
    var CF_OPTIONS = ['always','hourly','daily','weekly','monthly','yearly','never'];

    var _sitemapDefaults = {priority: {}, changefreq: {}};
    var _sitemapOverrides = {priority: {}, changefreq: {}, noindex: {}};

    function loadSitemapRoleSettings() {
        $.post(ajaxurl, {action: 'orch_sitemap_role_settings_get', nonce: nonce}).done(function(resp) {
            if (!resp || !resp.success) return;
            _sitemapDefaults = resp.defaults || _sitemapDefaults;
            _sitemapOverrides.priority   = resp.priority   || {};
            _sitemapOverrides.changefreq = resp.changefreq || {};
            _sitemapOverrides.noindex    = resp.noindex    || {};
            renderPriorityTable();
            renderChangefreqTable();
        });
    }

    function priorityFor(role) {
        if (_sitemapOverrides.priority[role] !== undefined && _sitemapOverrides.priority[role] !== '') return _sitemapOverrides.priority[role];
        return _sitemapDefaults.priority[role] || '0.5';
    }
    function changefreqFor(role) {
        if (_sitemapOverrides.changefreq[role] !== undefined && _sitemapOverrides.changefreq[role] !== '') return _sitemapOverrides.changefreq[role];
        return _sitemapDefaults.changefreq[role] || 'weekly';
    }
    function noindexFor(role) {
        return _sitemapOverrides.noindex[role] ? true : false;
    }

    function renderPriorityTable() {
        var $tb = $('#orch-sitemap-priority-table tbody');
        if (!$tb.length) return;
        var rows = '';
        ROLES.forEach(function(role) {
            var val = priorityFor(role);
            var def = _sitemapDefaults.priority[role] || '0.5';
            var modified = (_sitemapOverrides.priority[role] !== undefined && _sitemapOverrides.priority[role] !== '');
            var warn = (parseFloat(val) < 0.3 && (role === 'product_page' || role === 'service_page' || role === 'quote_request'));
            rows += '<tr data-role="' + escHtml(role) + '"><td><strong>' + escHtml(role) + '</strong>' + (modified ? ' <span style="color:#0055FF;font-size:10px;">●</span>' : '') + '</td>';
            rows += '<td><div style="display:flex;align-items:center;gap:8px;"><input type="range" min="0" max="1" step="0.1" value="' + val + '" data-role="' + escHtml(role) + '" data-kind="priority" class="orch-sitemap-prio-slider" style="flex:1;" /><span class="orch3-muted" style="min-width:46px;font-family:ui-monospace,monospace;">' + val + '</span></div>';
            if (warn) rows += '<div style="font-size:10px;color:#92400e;margin-top:3px;">⚠ Priority bassa per pagina commerciale</div>';
            rows += '<div class="orch3-muted" style="font-size:10px;">Default: ' + def + '</div></td>';
            rows += '<td><button type="button" class="button orch-sitemap-prio-reset" data-role="' + escHtml(role) + '" data-kind="priority" style="font-size:11px;padding:2px 6px;" title="Reset to default">↺</button></td></tr>';
        });
        $tb.html(rows);
    }

    function renderChangefreqTable() {
        var $tb = $('#orch-sitemap-changefreq-table tbody');
        if (!$tb.length) return;
        var rows = '';
        ROLES.forEach(function(role) {
            var cf = changefreqFor(role);
            var ni = noindexFor(role);
            var def = _sitemapDefaults.changefreq[role] || '(auto)';
            var modCf = (_sitemapOverrides.changefreq[role] !== undefined && _sitemapOverrides.changefreq[role] !== '');
            var modNi = !!_sitemapOverrides.noindex[role];
            rows += '<tr data-role="' + escHtml(role) + '"><td><strong>' + escHtml(role) + '</strong>' + ((modCf || modNi) ? ' <span style="color:#0055FF;font-size:10px;">●</span>' : '') + '<div class="orch3-muted" style="font-size:10px;">Default: ' + def + '</div></td>';
            rows += '<td><select data-role="' + escHtml(role) + '" data-kind="changefreq" class="orch-sitemap-cf-select">';
            CF_OPTIONS.forEach(function(opt) {
                rows += '<option value="' + opt + '"' + (cf === opt ? ' selected' : '') + '>' + opt + '</option>';
            });
            rows += '</select></td>';
            rows += '<td><label><input type="checkbox" data-role="' + escHtml(role) + '" data-kind="noindex" class="orch-sitemap-ni-check"' + (ni ? ' checked' : '') + ' /> noindex</label></td>';
            rows += '<td><button type="button" class="button orch-sitemap-cf-reset" data-role="' + escHtml(role) + '" style="font-size:11px;padding:2px 6px;" title="Reset to default">↺</button></td></tr>';
        });
        $tb.html(rows);
    }

    function saveOverride(kind, role, value) {
        return $.post(ajaxurl, {action: 'orch_sitemap_role_settings_save', nonce: nonce, kind: kind, role: role, value: value});
    }

    $(document).off('input.orchSitemapPrio').on('input.orchSitemapPrio', '.orch-sitemap-prio-slider', function() {
        var $row = $(this).closest('tr');
        var role = $(this).data('role');
        var val = $(this).val();
        $row.find('span').first().text(val);
    });
    $(document).off('change.orchSitemapPrio').on('change.orchSitemapPrio', '.orch-sitemap-prio-slider', function() {
        var role = $(this).data('role');
        var val = $(this).val();
        saveOverride('priority', role, val).done(function(resp) {
            if (resp && resp.success) {
                _sitemapOverrides.priority = resp.overrides || _sitemapOverrides.priority;
                renderPriorityTable();
            }
        });
    });
    $(document).off('change.orchSitemapCf').on('change.orchSitemapCf', '.orch-sitemap-cf-select', function() {
        var role = $(this).data('role');
        var val = $(this).val();
        saveOverride('changefreq', role, val).done(function(resp) {
            if (resp && resp.success) {
                _sitemapOverrides.changefreq = resp.overrides || _sitemapOverrides.changefreq;
                renderChangefreqTable();
            }
        });
    });
    $(document).off('change.orchSitemapNi').on('change.orchSitemapNi', '.orch-sitemap-ni-check', function() {
        var role = $(this).data('role');
        var val = $(this).is(':checked') ? '1' : '0';
        saveOverride('noindex', role, val).done(function(resp) {
            if (resp && resp.success) {
                _sitemapOverrides.noindex = resp.overrides || _sitemapOverrides.noindex;
                renderChangefreqTable();
            }
        });
    });
    $(document).off('click.orchSitemapReset').on('click.orchSitemapReset', '.orch-sitemap-prio-reset', function() {
        var role = $(this).data('role');
        saveOverride('priority', role, '__default__').done(function() { delete _sitemapOverrides.priority[role]; renderPriorityTable(); });
    });
    $(document).off('click.orchSitemapCfReset').on('click.orchSitemapCfReset', '.orch-sitemap-cf-reset', function() {
        var role = $(this).data('role');
        saveOverride('changefreq', role, '__default__').done(function() { delete _sitemapOverrides.changefreq[role]; renderChangefreqTable(); });
    });
    $(document).off('click.orchSitemapResetAll').on('click.orchSitemapResetAll', '#orch-sitemap-priority-reset-all', function() {
        if (!confirm('Reset TUTTI i priority overrides ai default?')) return;
        saveOverride('priority', '__all__', '__reset__').done(function() {
            _sitemapOverrides.priority = {};
            renderPriorityTable();
        });
    });

    // Lazy load when sitemap accordion opens
    $(document).on('toggle', '[data-orch-output="sitemap"]', function() {
        if (this.open && !this.dataset.rolesLoaded) {
            this.dataset.rolesLoaded = '1';
            loadSitemapRoleSettings();
        }
    });
    $(function() {
        var sm = document.querySelector('[data-orch-output="sitemap"]');
        if (sm && sm.open) loadSitemapRoleSettings();
    });
})(window.jQuery);


/* 3.35.75: Featured pages auto-suggest JS */
(function($) {
    if (!$ || typeof window.seoAeoOrchestra === 'undefined') return;
    var ajaxurl = window.ajaxurl || (window.seoAeoOrchestra && window.seoAeoOrchestra.ajaxurl);
    var nonce = window.seoAeoOrchestra && window.seoAeoOrchestra.nonce;
    if (!ajaxurl || !nonce) return;
    function escapeHtml(s) { return String(s || '').replace(/[&<>"']/g, function(c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    }); }

    var _suggestedCache = [];

    function renderSuggestedList(items, currentIds) {
        var html = '';
        if (!items.length) {
            html = '<div class="orch3-muted">Nessuna pagina trovata. Verifica che ci siano post pubblicati.</div>';
        } else {
            html = '<table class="widefat striped" style="border:0;">';
            html += '<thead><tr><th style="width:30px;"></th><th>Titolo</th><th>Role</th><th style="width:80px;">Score</th></tr></thead><tbody>';
            items.forEach(function(p) {
                var isCurrent = currentIds.indexOf(p.post_id) >= 0;
                var bd = p.score_breakdown || {};
                var tooltip = 'role +' + bd.role + ' · recency +' + bd.recency + ' · engagement +' + bd.engagement + ' · search +' + bd.search;
                html += '<tr><td><input type="checkbox" class="orch-llms-suggest-row" value="' + p.post_id + '" checked /></td>';
                html += '<td><strong>' + escapeHtml(p.title) + '</strong>' + (isCurrent ? ' <span style="color:#10b981;font-size:10px;">[già featured]</span>' : '') + '<br><span class="orch3-muted" style="font-size:11px;">' + escapeHtml(p.slug) + ' · ' + escapeHtml(p.post_type) + '</span></td>';
                html += '<td><span style="background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:10px;font-size:11px;">' + escapeHtml(p.role) + '</span></td>';
                html += '<td><strong title="' + escapeHtml(tooltip) + '">' + p.score + '</strong><br><span class="orch3-muted" style="font-size:10px;">hover: breakdown</span></td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
        }
        $('#orch-llms-autosuggest-list').html(html);
    }

    function openAutosuggest() {
        var limit = parseInt($('#orch-llms-autosuggest-count').val(), 10) || 10;
        var $btn = $('#orch-llms-autosuggest-btn').prop('disabled', true).text('🪄 Calcolando…');
        $.post(ajaxurl, {action: 'orch_llms_featured_auto_suggest', nonce: nonce, limit: limit}).done(function(resp) {
            if (!resp || !resp.success) {
                alert('Errore: ' + ((resp && resp.message) || 'unknown'));
                return;
            }
            _suggestedCache = resp.suggested || [];
            renderSuggestedList(_suggestedCache, resp.current_featured_ids || []);
            $('#orch-llms-autosuggest-modal').css('display', 'flex');
        }).fail(function(xhr) {
            alert('Errore rete: HTTP ' + xhr.status);
        }).always(function() {
            $btn.prop('disabled', false).text('🪄 Auto-suggest');
        });
    }

    function applySuggested() {
        var ids = [];
        $('#orch-llms-autosuggest-list .orch-llms-suggest-row:checked').each(function() {
            ids.push(parseInt($(this).val(), 10));
        });
        if (!ids.length) {
            alert('Seleziona almeno una pagina.');
            return;
        }
        var merge = $('#orch-llms-autosuggest-merge').is(':checked') ? 1 : 0;
        var $btn = $('#orch-llms-autosuggest-apply').prop('disabled', true).text('Salvando…');
        $.post(ajaxurl, {action: 'orch_llms_featured_apply_suggested', nonce: nonce, 'ids[]': ids, merge: merge}).done(function(resp) {
            if (resp && resp.success) {
                $('#orch-llms-autosuggest-modal').hide();
                alert('✓ ' + resp.count + ' featured pages salvate. /llms.txt aggiornato.');
                // Refresh status box if present
                if (typeof loadLlmsSummary === 'function') loadLlmsSummary();
            } else {
                alert('Errore: ' + ((resp && resp.message) || 'unknown'));
            }
        }).always(function() {
            $btn.prop('disabled', false).text('✓ Accept (applica selezione)');
        });
    }

    $(document).off('click.orchLlmsAutosuggest').on('click.orchLlmsAutosuggest', '#orch-llms-autosuggest-btn', openAutosuggest);
    $(document).off('click.orchLlmsAutosuggestApply').on('click.orchLlmsAutosuggestApply', '#orch-llms-autosuggest-apply', applySuggested);
    $(document).off('click.orchLlmsAutosuggestClose').on('click.orchLlmsAutosuggestClose',
        '#orch-llms-autosuggest-close, #orch-llms-autosuggest-cancel', function() {
            $('#orch-llms-autosuggest-modal').hide();
        });
})(window.jQuery);


/* 3.35.79: Floating save bar JS */
(function($) {
    if (!$) return;

    var TRACKED_FORM_SEL = '#orch-identity-form';
    var SAVE_BTN_SEL = '#orch-identity-save';

    var $bar = $('#orch-floating-savebar');
    if (!$bar.length) return;

    var initialState = {};
    var snapshotTaken = false;
    var ignoreInputs = false;

    function captureSnapshot() {
        initialState = {};
        $(TRACKED_FORM_SEL).find('input, select, textarea').each(function() {
            var el = this;
            var key = el.name || el.id;
            if (!key) return;
            if (el.type === 'checkbox' || el.type === 'radio') {
                initialState[key + '::' + (el.value || '')] = el.checked ? '1' : '0';
            } else {
                initialState[key] = $(el).val();
            }
        });
        snapshotTaken = true;
    }

    function isDirty() {
        var dirty = false;
        $(TRACKED_FORM_SEL).find('input, select, textarea').each(function() {
            if (dirty) return;
            var el = this;
            var key = el.name || el.id;
            if (!key) return;
            var current;
            var stored;
            if (el.type === 'checkbox' || el.type === 'radio') {
                var fullKey = key + '::' + (el.value || '');
                current = el.checked ? '1' : '0';
                stored = initialState[fullKey];
                if (stored === undefined) stored = '0';
            } else {
                current = $(el).val();
                stored = initialState[key];
                if (stored === undefined) stored = '';
            }
            if (String(current) !== String(stored)) dirty = true;
        });
        return dirty;
    }

    function setBarState(state) {
        $bar.removeClass('is-dirty is-saving is-saved');
        if (state) $bar.addClass(state);
        $bar.attr('aria-hidden', state ? 'false' : 'true');
    }

    function showToast(message, kind) {
        var $toast = $('<div class="orch-floating-toast"></div>').text(message);
        if (kind === 'error') $toast.css('background', '#dc2626');
        $('body').append($toast);
        // force reflow
        $toast[0].offsetWidth;
        $toast.addClass('is-visible');
        setTimeout(function() {
            $toast.removeClass('is-visible');
            setTimeout(function() { $toast.remove(); }, 350);
        }, 2200);
    }

    function refreshBar() {
        if (ignoreInputs) return;
        if (!snapshotTaken) {
            // Form may not yet be visible; defer
            if ($(TRACKED_FORM_SEL).is(':visible')) captureSnapshot();
            else return;
        }
        var dirty = isDirty();
        setBarState(dirty ? 'is-dirty' : null);
    }

    // Watch input/change/blur on tracked form
    $(document).on('input change blur', TRACKED_FORM_SEL + ' input, ' + TRACKED_FORM_SEL + ' select, ' + TRACKED_FORM_SEL + ' textarea', refreshBar);

    // Capture initial snapshot when form becomes visible (loaded async)
    var snapshotPoll = setInterval(function() {
        if ($(TRACKED_FORM_SEL).is(':visible')) {
            captureSnapshot();
            clearInterval(snapshotPoll);
        }
    }, 500);
    setTimeout(function() { clearInterval(snapshotPoll); }, 30000);

    // Save click → trigger existing save button
    $(document).on('click', '#orch-floating-save-btn', function() {
        setBarState('is-saving');
        $bar.find('.orch-floating-savebar-label').text('Salvataggio…');
        var $saveBtn = $(SAVE_BTN_SEL);
        if (!$saveBtn.length) {
            showToast('Pulsante Salva non trovato', 'error');
            setBarState('is-dirty');
            return;
        }
        // Listen for AJAX completion to detect save success
        var doneHandler = function(event, xhr, settings) {
            if (settings && settings.data && String(settings.data).indexOf('seo_aeo_orchestra_identity_save') !== -1) {
                $(document).off('ajaxComplete.orchSavebar', doneHandler);
                try {
                    var resp = JSON.parse(xhr.responseText || '{}');
                    if (resp && (resp.success || resp.ok)) {
                        captureSnapshot();
                        setBarState('is-saved');
                        $bar.find('.orch-floating-savebar-label').text('✓ Salvato');
                        showToast('✓ Profilo salvato');
                        setTimeout(function() {
                            setBarState(null);
                            $bar.find('.orch-floating-savebar-label').text('Modifiche non salvate');
                        }, 1800);
                    } else {
                        showToast('Errore salvataggio: ' + (resp.message || 'unknown'), 'error');
                        setBarState('is-dirty');
                        $bar.find('.orch-floating-savebar-label').text('Modifiche non salvate');
                    }
                } catch (e) {
                    setBarState('is-dirty');
                    $bar.find('.orch-floating-savebar-label').text('Modifiche non salvate');
                }
            }
        };
        $(document).on('ajaxComplete.orchSavebar', doneHandler);
        $saveBtn.trigger('click');
    });

    // Cancel click → restore from snapshot
    $(document).on('click', '#orch-floating-cancel-btn', function() {
        if (!confirm('Ripristinare i valori al momento del caricamento? Le modifiche non salvate verranno perse.')) return;
        ignoreInputs = true;
        try {
            $(TRACKED_FORM_SEL).find('input, select, textarea').each(function() {
                var el = this;
                var key = el.name || el.id;
                if (!key) return;
                if (el.type === 'checkbox' || el.type === 'radio') {
                    var fullKey = key + '::' + (el.value || '');
                    var stored = initialState[fullKey];
                    el.checked = (stored === '1');
                } else {
                    var stored2 = initialState[key];
                    if (stored2 === undefined) stored2 = '';
                    $(el).val(stored2);
                }
                $(el).trigger('change');
            });
        } finally {
            ignoreInputs = false;
            setBarState(null);
            showToast('↺ Modifiche annullate');
        }
    });

    // beforeunload warning
    window.addEventListener('beforeunload', function(e) {
        if ($bar.hasClass('is-dirty')) {
            e.preventDefault();
            e.returnValue = 'Hai modifiche non salvate. Vuoi davvero uscire?';
            return e.returnValue;
        }
    });

    // Mark namespace
    window.orchFloatingSaveBar = {
        captureSnapshot: captureSnapshot,
        refresh: refreshBar,
    };
})(window.jQuery);


/* 3.35.79.1: Brand Voice About generation JS (restored from 3.35.76) */
(function($) {
    if (!$ || typeof window.seoAeoOrchestra === 'undefined') return;
    var ajaxurl = window.ajaxurl || (window.seoAeoOrchestra && window.seoAeoOrchestra.ajaxurl);
    var nonce = window.seoAeoOrchestra && window.seoAeoOrchestra.nonce;
    if (!ajaxurl || !nonce) return;
    function escapeHtml(s) { return String(s || '').replace(/[&<>"']/g, function(c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    }); }

    function generateAbout() {
        var tier = $('#orch-bv-about-tier').val() || 'standard';
        var $btn = $('#orch-bv-about-generate-btn').prop('disabled', true).text('✨ Generando…');
        $.post(ajaxurl, {action: 'orch_brand_voice_about_generate', nonce: nonce, tier: tier}, null, 'json').done(function(resp) {
            if (!resp || resp.error) {
                var msg = (resp && resp.user_message) || (resp && resp.message) || 'Errore sconosciuto';
                alert(msg);
                return;
            }
            var variants = resp.variants || [];
            if (!variants.length) {
                // 3.35.79.3: log raw to console for debug, show friendly message to user
                if (window.console) {
                    console.error('[BV About] empty variants. raw_text length:', (resp.raw_text || '').length);
                    console.error('[BV About] raw_text excerpt:', (resp.raw_text || '(empty)').slice(0, 800));
                    console.error('[BV About] full response:', resp);
                }
                alert('Risposta incompleta dal backend. Riprova tra qualche secondo.');
                return;
            }
            // 3.35.79.2: white-label — show only tier + credits, not provider/model
            var tierLabel = (resp.tier_used === 'premium_plus' || resp.tier_used === 'premium_plus_plus') ? 'Premium+' : 'Standard';
            var meta = '✓ ' + variants.length + ' varianti · tier: ' + tierLabel + ' · credits: ' + (resp.credits_cost || 0);
            // Provider/model logged to console for debug only
            if (window.console && resp.provider_used) {
                console.log('[BV About] tier=' + resp.tier_used + ' provider=' + resp.provider_used + ' model=' + resp.model_used);
            }
            $('#orch-bv-about-meta').text(meta);

            var html = '';
            variants.forEach(function(v, idx) {
                var len = (v.text || '').length;
                var lenColor = (len < 200 ? '#92400e' : (len > 500 ? '#dc2626' : '#10b981'));
                var lenLabel = (len < 200 ? 'Troppo corto' : (len > 500 ? 'Troppo lungo' : 'OK'));
                html += '<div style="border:1px solid #e2e8f0;border-radius:8px;padding:14px;background:#f8fafc;">';
                html += '<div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:8px;flex-wrap:wrap;">';
                html += '<strong>' + escapeHtml(v.tone || ('Variante ' + (idx+1))) + '</strong>';
                html += '<span style="font-size:11px;color:' + lenColor + ';">' + len + ' chars · ' + lenLabel + '</span>';
                html += '</div>';
                html += '<div style="font-size:13px;line-height:1.55;color:#334155;white-space:pre-wrap;">' + escapeHtml(v.text || '') + '</div>';
                html += '<div style="margin-top:10px;display:flex;gap:6px;">';
                html += '<button type="button" class="orch3-btn orch3-btn-primary orch-bv-about-use" data-text="' + escapeHtml(v.text || '') + '">✓ Usa questa variante</button>';
                html += '</div>';
                html += '</div>';
            });
            $('#orch-bv-about-variants').html(html);
            $('#orch-bv-about-modal').css('display', 'flex');
        }).fail(function(xhr) {
            alert('Errore rete HTTP ' + xhr.status + ': ' + (xhr.responseText || '').slice(0, 300));
        }).always(function() {
            $btn.prop('disabled', false).text('✨ Genera 3 varianti');
        });
    }

    function applyVariant(text) {
        var $textarea = $('#orch-id-about');
        if (!$textarea.length) {
            // Fallback: copy to clipboard
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    alert('Variante copiata in clipboard.');
                });
            }
            $('#orch-bv-about-modal').hide();
            return;
        }
        $textarea.val(text).trigger('input').trigger('change');
        $('#orch-bv-about-modal').hide();
        try { $textarea[0].scrollIntoView({block: 'center', behavior: 'smooth'}); $textarea.focus(); } catch (e) {}
        $textarea.css('background', '#fef3c7');
        setTimeout(function() { $textarea.css('background', ''); }, 2000);
    }

    $(document).off('click.orchBvAboutGenerate').on('click.orchBvAboutGenerate', '#orch-bv-about-generate-btn', generateAbout);
    $(document).off('click.orchBvAboutClose').on('click.orchBvAboutClose', '#orch-bv-about-close, #orch-bv-about-cancel', function() {
        $('#orch-bv-about-modal').hide();
    });
    $(document).off('click.orchBvAboutUse').on('click.orchBvAboutUse', '.orch-bv-about-use', function() {
        applyVariant($(this).data('text'));
    });
})(window.jQuery);


/* 3.35.79.4: empty-state clickable links in ✅ Configurazione attiva */
(function($) {
    if (!$) return;
    $(document).off('click.orchEmptyScroll').on('click.orchEmptyScroll', '.orch-empty-link', function(e) {
        e.preventDefault();
        var targetId = $(this).data('orch-scroll-target');
        if (!targetId) return;
        var target = document.getElementById(targetId);
        if (!target) return;
        // Open ancestor <details> chain
        var parent = target.closest('details');
        while (parent) {
            parent.open = true;
            parent = parent.parentElement ? parent.parentElement.closest('details') : null;
        }
        setTimeout(function() {
            try { target.scrollIntoView({block: 'center', behavior: 'smooth'}); target.focus(); } catch (e) {}
            target.classList.add('orch-pulse-warn');
            setTimeout(function() { target.classList.remove('orch-pulse-warn'); }, 2100);
        }, 220);
    });
})(window.jQuery);
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>


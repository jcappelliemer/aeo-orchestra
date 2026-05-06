<?php
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
    <script>
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
    </script>


    <!-- ═══ Native SEO Output (v3.12.0) — sostituto di Yoast/RankMath/AIOSEO ═══ -->
    <div class="orch3-card orch-native-card" id="orch-output-card">
        <a id="orch-sitemap"></a>
        <a id="orch-llms"></a>
        <a id="orch-schema"></a>
        <div class="orch-native-head">
            <div>
                <h2 class="orch3-h2"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('SEO Output Nativo')) : 'SEO Output Nativo'; ?> <span class="orch-native-beta-badge">Beta</span></h2>
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

    <div class="orch-native-footer">
        <strong><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('SEO Output Nativo')) : 'SEO Output Nativo'; ?></strong> · <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Versione')) : 'Versione'; ?> <?php echo esc_html(defined('SEO_AEO_VERSION') ? SEO_AEO_VERSION : '?'); ?> ·
        <a href="https://aeo-orchestra.com/changelog" target="_blank" rel="noopener"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Changelog')) : 'Changelog'; ?></a> ·
        <a href="https://aeo-orchestra.com/contact" target="_blank" rel="noopener"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Assistenza')) : 'Assistenza'; ?></a>
    </div>

</div>

<style>
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


</style>

<script>
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

            $('#orch-native-toggle, #orch-native-override-toggle').on('change', function() {
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

            $('#orch-native-sitemap-toggle').on('change', function() {
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

            $('#orch-native-llms-toggle').on('change', function() {
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

            $('#orch-native-schema-toggle').on('change', function() {
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

            $('#orch-native-redirect-toggle').on('change', function() {
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
</script>

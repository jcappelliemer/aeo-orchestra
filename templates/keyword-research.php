<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.VariableNotPrefixed
// Reason: template scope. Variables are local to this include/template,
// passed by the calling function via include/require. The Plugin Check
// heuristic doesn't distinguish template-scope locals from globals.
/*
 * Template: Keyword Research Autopilot (since 3.23.0)
 * UI per generare 30+ keyword strategiche da una nicchia, con cluster/intent/volume/difficoltà.
 * 3.31.4: i18n auto-default lingua dropdown a locale admin, +DE option,
 *         tutti i label tabelle/bottoni passano da SEO_AEO_T::t().
 */
if (!defined('ABSPATH')) exit;

// 3.31.4: lingua dropdown default = locale admin (in/out it/en/es/fr/de) per evitare
// che keyword research generi output IT quando l'admin lavora in altra lingua.
$_orch_kr_admin_locale = class_exists('SEO_AEO_T') ? SEO_AEO_T::current_locale() : 'it';
$_orch_kr_supported = array('it', 'en', 'es', 'fr', 'de');
$_orch_kr_default_lang = in_array($_orch_kr_admin_locale, $_orch_kr_supported, true) ? $_orch_kr_admin_locale : 'it';
?>
<div class="wrap orch-kr-page">

    <!-- HERO -->
    <div class="orch-kr-hero">
        <div class="orch-kr-hero-bg"></div>
        <div class="orch-kr-hero-inner">
            <div class="orch-kr-hero-icon">🎯</div>
            <div>
                <div class="orch-kr-hero-eyebrow"><?php SEO_AEO_T::e('AEO Orchestra · Keyword Engine'); ?></div>
                <h1 class="orch-kr-hero-title"><?php SEO_AEO_T::e('Keyword Research Autopilot'); ?></h1>
                <p class="orch-kr-hero-sub"><?php SEO_AEO_T::e('Da nicchia generica a 30 keyword strategiche organizzate in cluster semantici, con intent + difficoltà + idea articolo. L\'AI fa il lavoro che richiederebbe ore di SEMrush/Ahrefs.'); ?></p>
            </div>
        </div>
    </div>

    <!-- SEARCH FORM -->
    <div class="orch-kr-search">
        <div class="orch-kr-search-head">
            <h2><?php SEO_AEO_T::e('🔎 Esplora la tua nicchia'); ?></h2>
            <span class="orch-kr-cost-pill"><?php SEO_AEO_T::e('15 crediti per ricerca'); ?></span>
        </div>
        <p class="orch-kr-search-sub"><?php SEO_AEO_T::e('Descrivi nicchia, settore o argomento principale. Piu specifico, migliori i risultati.'); ?></p>

        <div class="orch-kr-form">
            <input type="text" id="orch-kr-niche" placeholder="<?php echo esc_attr(SEO_AEO_T::t('es: Software B2B per PMI, oppure Marketing automation, oppure Plugin SEO WordPress, oppure Consulenza IT...')); ?>" class="orch-kr-input">
            <div class="orch-kr-form-row">
                <select id="orch-kr-language" class="orch-kr-input-sm">
                    <option value="it" <?php selected($_orch_kr_default_lang, 'it'); ?>><?php SEO_AEO_T::e('Italiano'); ?></option>
                    <option value="en" <?php selected($_orch_kr_default_lang, 'en'); ?>><?php SEO_AEO_T::e('English'); ?></option>
                    <option value="es" <?php selected($_orch_kr_default_lang, 'es'); ?>>Español</option>
                    <option value="fr" <?php selected($_orch_kr_default_lang, 'fr'); ?>>Français</option>
                    <option value="de" <?php selected($_orch_kr_default_lang, 'de'); ?>>Deutsch</option>
                </select>
                <select id="orch-kr-max" class="orch-kr-input-sm">
                    <option value="20">20 <?php SEO_AEO_T::e('keyword'); ?></option>
                    <option value="30" selected>30 <?php SEO_AEO_T::e('keyword'); ?></option>
                    <option value="50">50 <?php SEO_AEO_T::e('keyword'); ?></option>
                </select>
                <button type="button" class="orch-kr-btn orch-kr-btn-primary" id="orch-kr-search-btn">
                    <span class="dashicons dashicons-search" style="margin-top:3px;"></span> <?php SEO_AEO_T::e('Genera Keyword'); ?>
                </button>
            </div>
        </div>

        <!-- LOADING -->
        <div class="orch-kr-loading" id="orch-kr-loading" style="display:none;">
            <div class="orch-kr-loading-bar"><div class="orch-kr-loading-fill"></div></div>
            <p><?php SEO_AEO_T::e('L\'AI sta esplorando la nicchia... circa 20 secondi.'); ?></p>
        </div>
    </div>

    <!-- RESULTS -->
    <div id="orch-kr-results"></div>

    <!-- HISTORY -->
    <div id="history-keyword-research" class="orchestra-history-container" style="margin-top:28px;"></div>
</div>

<?php ob_start(); ?>
.orch-kr-page { max-width: 1280px; margin-right: 20px; }

/* HERO */
.orch-kr-hero { position: relative; margin: 14px 0 26px; padding: 32px 40px; border-radius: 16px; overflow: hidden; color: #fff; }
.orch-kr-hero-bg { position: absolute; inset: 0; background: linear-gradient(120deg, #0A0E27 0%, #134e4a 40%, #059669 75%, #34d399 100%); }
.orch-kr-hero-bg::after { content: ''; position: absolute; inset: 0; background: radial-gradient(700px 350px at 90% -20%, rgba(255,255,255,0.18), transparent 60%); }
.orch-kr-hero-inner { position: relative; display: flex; gap: 24px; align-items: center; }
.orch-kr-hero-icon { font-size: 70px; line-height: 1; }
.orch-kr-hero-eyebrow { font-size: 12px; letter-spacing: 1.2px; text-transform: uppercase; opacity: 0.85; font-weight: 600; }
.orch-kr-hero-title { margin: 8px 0 10px; font-size: 28px; font-weight: 800; color: #fff; }
.orch-kr-hero-sub { margin: 0; font-size: 14.5px; line-height: 1.55; opacity: 0.92; max-width: 760px; }

/* SEARCH */
.orch-kr-search { background: #fff; border: 1.5px solid #e5e7eb; border-radius: 12px; padding: 24px 28px; margin-bottom: 22px; }
.orch-kr-search-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-wrap: wrap; gap: 10px; }
.orch-kr-search-head h2 { margin: 0; font-size: 18px; color: #0A0E27; }
.orch-kr-cost-pill { background: linear-gradient(90deg, #ecfdf5, #d1fae5); color: #065f46; padding: 5px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; border: 1px solid #6ee7b7; }
.orch-kr-search-sub { color: #6b7280; font-size: 13px; margin: 0 0 16px; }
.orch-kr-form { display: flex; flex-direction: column; gap: 12px; }
.orch-kr-input { width: 100%; padding: 12px 16px; border: 1.5px solid #e5e7eb; border-radius: 10px; font-size: 14px; }
.orch-kr-input:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5,150,105,0.1); }
.orch-kr-form-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: stretch; }
.orch-kr-input-sm { padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 10px; font-size: 13px; background: #fff; }
.orch-kr-btn { padding: 10px 20px; border-radius: 10px; font-weight: 600; cursor: pointer; border: none; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; }
.orch-kr-btn-primary { background: linear-gradient(135deg, #059669, #10b981); color: #fff; }
.orch-kr-btn-primary:hover { background: linear-gradient(135deg, #047857, #059669); }
.orch-kr-btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

/* LOADING */
.orch-kr-loading { margin-top: 20px; padding: 16px 20px; background: #f0fdf4; border-radius: 10px; }
.orch-kr-loading p { margin: 8px 0 0; color: #065f46; font-size: 13px; }
.orch-kr-loading-bar { width: 100%; height: 4px; background: #d1fae5; border-radius: 2px; overflow: hidden; }
.orch-kr-loading-fill { width: 30%; height: 100%; background: linear-gradient(90deg, #10b981, #34d399); animation: orchKrSlide 1.5s linear infinite; }
@keyframes orchKrSlide { 0% { margin-left: -30%; } 100% { margin-left: 100%; } }

/* RESULTS */
.orch-kr-result-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 22px; }
.orch-kr-stat { background: #fff; border: 1.5px solid #e5e7eb; border-radius: 10px; padding: 16px; }
.orch-kr-stat-value { font-size: 26px; font-weight: 800; color: #059669; line-height: 1; }
.orch-kr-stat-label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; font-weight: 600; }

.orch-kr-cluster { background: #fff; border: 1.5px solid #e5e7eb; border-radius: 12px; margin-bottom: 16px; overflow: hidden; }
.orch-kr-cluster-head { padding: 14px 20px; background: linear-gradient(90deg, #f0fdf4, #dcfce7); border-bottom: 1px solid #bbf7d0; }
.orch-kr-cluster-name { font-size: 14px; font-weight: 700; color: #065f46; margin: 0; }
.orch-kr-cluster-count { color: #059669; font-size: 12px; margin-left: 8px; font-weight: 600; }

.orch-kr-table { width: 100%; border-collapse: collapse; }
.orch-kr-table th { background: #fafafa; text-align: left; padding: 10px 14px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px; color: #6b7280; font-weight: 700; border-bottom: 1px solid #e5e7eb; }
.orch-kr-table td { padding: 12px 14px; font-size: 13px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
.orch-kr-table tr:hover { background: #fafafa; }

.orch-kr-keyword { font-weight: 600; color: #0A0E27; }
.orch-kr-suggested-topic { display: block; font-size: 11.5px; color: #6b7280; margin-top: 2px; line-height: 1.4; font-style: italic; }

.orch-kr-tag { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
.orch-kr-tag-volume-alto { background: #dcfce7; color: #065f46; }
.orch-kr-tag-volume-medio { background: #fef3c7; color: #92400e; }
.orch-kr-tag-volume-basso { background: #f3f4f6; color: #4b5563; }
.orch-kr-tag-volume-nicchia { background: #ede9fe; color: #5b21b6; }

.orch-kr-tag-diff-facile { background: #dcfce7; color: #065f46; }
.orch-kr-tag-diff-medio { background: #fef3c7; color: #92400e; }
.orch-kr-tag-diff-difficile { background: #fee2e2; color: #991b1b; }

.orch-kr-tag-intent-informational { background: #dbeafe; color: #1e40af; }
.orch-kr-tag-intent-transactional { background: #fee2e2; color: #991b1b; }
.orch-kr-tag-intent-commercial { background: #fef3c7; color: #92400e; }
.orch-kr-tag-intent-navigational { background: #f3f4f6; color: #4b5563; }

.orch-kr-actions-cell { white-space: nowrap; }
.orch-kr-action-btn { padding: 4px 10px; background: #fff; border: 1px solid #d1d5db; border-radius: 6px; font-size: 11.5px; cursor: pointer; color: #374151; margin-right: 4px; }
.orch-kr-action-btn:hover { border-color: #059669; color: #059669; }
.orch-kr-action-btn-primary { background: #059669; color: #fff; border-color: #059669; }
.orch-kr-action-btn-primary:hover { background: #047857; color: #fff; }

.orch-kr-export-bar { display: flex; gap: 10px; justify-content: flex-end; margin: 14px 0 18px; flex-wrap: wrap; }
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

<?php ob_start(); ?>
(function() {
    if (typeof window.ajaxurl === 'undefined') {
        window.ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
    }
    if (typeof window.seoAeoOrchestra === 'undefined') {
        window.seoAeoOrchestra = {
            ajaxUrl: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
            nonce: '<?php echo esc_js(wp_create_nonce('seo_aeo_orchestra_nonce')); ?>'
        };
    }
})();

jQuery(function($) {
    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    // i18n strings for JS-generated UI (3.31.4)
    var krT = {
        noKeywords: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Nessuna keyword generata. Riprova con una nicchia diversa.') : 'Nessuna keyword generata. Riprova con una nicchia diversa.'); ?>,
        keywordTotal: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Keyword totali') : 'Keyword totali'); ?>,
        clusterSemantic: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Cluster semantici') : 'Cluster semantici'); ?>,
        easyToRank: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Facili da rankare') : 'Facili da rankare'); ?>,
        commercialIntent: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Intent commerciale') : 'Intent commerciale'); ?>,
        exportCsv: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('📊 Esporta CSV') : '📊 Esporta CSV'); ?>,
        copyAll: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('📋 Copia tutte le keyword') : '📋 Copia tutte le keyword'); ?>,
        keywordCol: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Keyword + Idea articolo') : 'Keyword + Idea articolo'); ?>,
        volumeCol: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Volume') : 'Volume'); ?>,
        difficultyCol: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Difficoltà') : 'Difficoltà'); ?>,
        intentCol: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Intent') : 'Intent'); ?>,
        actionsCol: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Azioni') : 'Azioni'); ?>,
        articleBtn: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('✨ Articolo') : '✨ Articolo'); ?>,
        keywordSuffix: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('keyword') : 'keyword'); ?>,
        confirmSearch: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Ricerca keyword AI: 15 crediti. Procedere?') : 'Ricerca keyword AI: 15 crediti. Procedere?'); ?>,
        nicheTooShort: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Inserisci una nicchia di almeno 3 caratteri.') : 'Inserisci una nicchia di almeno 3 caratteri.'); ?>,
        keywordCopied: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Keyword copiata:') : 'Keyword copiata:'); ?>,
        keywordsCopied: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('keyword copiate') : 'keyword copiate'); ?>,
        networkError: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Errore rete (HTTP') : 'Errore rete (HTTP'); ?>,
        genericError: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Errore') : 'Errore'); ?>
    };

    function renderResults(data) {
        var keywords = data.keywords || [];
        var clusters = data.clusters || [];
        if (!keywords.length) {
            return '<div class="orchestra-notice warning">' + escapeHtml(krT.noKeywords) + '</div>';
        }

        // Group by cluster
        var grouped = {};
        keywords.forEach(function(kw) {
            var c = kw.cluster || 'Generale';
            if (!grouped[c]) grouped[c] = [];
            grouped[c].push(kw);
        });

        var totalEasy = keywords.filter(function(k){ return k.difficulty === 'facile'; }).length;
        var totalInfo = keywords.filter(function(k){ return k.intent === 'informational'; }).length;
        var totalTrans = keywords.filter(function(k){ return k.intent === 'transactional' || k.intent === 'commercial'; }).length;

        var html = '<div class="orch-kr-result-summary">' +
            '<div class="orch-kr-stat"><div class="orch-kr-stat-value">' + keywords.length + '</div><div class="orch-kr-stat-label">' + escapeHtml(krT.keywordTotal) + '</div></div>' +
            '<div class="orch-kr-stat"><div class="orch-kr-stat-value">' + clusters.length + '</div><div class="orch-kr-stat-label">' + escapeHtml(krT.clusterSemantic) + '</div></div>' +
            '<div class="orch-kr-stat"><div class="orch-kr-stat-value">' + totalEasy + '</div><div class="orch-kr-stat-label">' + escapeHtml(krT.easyToRank) + '</div></div>' +
            '<div class="orch-kr-stat"><div class="orch-kr-stat-value">' + totalTrans + '</div><div class="orch-kr-stat-label">' + escapeHtml(krT.commercialIntent) + '</div></div>' +
        '</div>';

        // Export bar
        html += '<div class="orch-kr-export-bar">' +
            '<button type="button" class="button orch-kr-export-csv">' + escapeHtml(krT.exportCsv) + '</button>' +
            '<button type="button" class="button orch-kr-copy-all">' + escapeHtml(krT.copyAll) + '</button>' +
        '</div>';

        // Per cluster
        clusters.forEach(function(cluster) {
            var rows = grouped[cluster] || [];
            html += '<div class="orch-kr-cluster">';
            html += '<div class="orch-kr-cluster-head"><h3 class="orch-kr-cluster-name">📁 ' + escapeHtml(cluster) + '<span class="orch-kr-cluster-count">' + rows.length + ' ' + escapeHtml(krT.keywordSuffix) + '</span></h3></div>';
            html += '<table class="orch-kr-table"><thead><tr>' +
                '<th>' + escapeHtml(krT.keywordCol) + '</th>' +
                '<th>' + escapeHtml(krT.volumeCol) + '</th>' +
                '<th>' + escapeHtml(krT.difficultyCol) + '</th>' +
                '<th>' + escapeHtml(krT.intentCol) + '</th>' +
                '<th>' + escapeHtml(krT.actionsCol) + '</th>' +
            '</tr></thead><tbody>';
            rows.forEach(function(kw) {
                html += '<tr data-keyword="' + escapeHtml(kw.keyword) + '" data-topic="' + escapeHtml(kw.suggested_topic || '') + '">';
                html += '<td><span class="orch-kr-keyword">' + escapeHtml(kw.keyword) + '</span>';
                if (kw.suggested_topic) {
                    html += '<span class="orch-kr-suggested-topic">💡 ' + escapeHtml(kw.suggested_topic) + '</span>';
                }
                html += '</td>';
                html += '<td><span class="orch-kr-tag orch-kr-tag-volume-' + kw.estimated_volume + '">' + escapeHtml(kw.estimated_volume) + '</span></td>';
                html += '<td><span class="orch-kr-tag orch-kr-tag-diff-' + kw.difficulty + '">' + escapeHtml(kw.difficulty) + '</span></td>';
                html += '<td><span class="orch-kr-tag orch-kr-tag-intent-' + kw.intent + '">' + escapeHtml(kw.intent) + '</span></td>';
                html += '<td class="orch-kr-actions-cell">' +
                    '<button class="orch-kr-action-btn orch-kr-action-btn-primary orch-kr-generate-article">' + escapeHtml(krT.articleBtn) + '</button>' +
                    '<button class="orch-kr-action-btn orch-kr-copy-kw">📋</button>' +
                '</td>';
                html += '</tr>';
            });
            html += '</tbody></table></div>';
        });

        return html;
    }

    $('#orch-kr-search-btn').on('click', function() {
        var $btn = $(this);
        var niche = $('#orch-kr-niche').val().trim();
        if (niche.length < 3) {
            alert(krT.nicheTooShort);
            return;
        }
        if (!confirm(krT.confirmSearch)) return;

        var lang = $('#orch-kr-language').val() || 'it';
        var maxKw = parseInt($('#orch-kr-max').val(), 10) || 30;

        $btn.prop('disabled', true);
        $('#orch-kr-loading').slideDown(150);
        $('#orch-kr-results').empty();

        $.post(ajaxurl, {
            action: 'seo_aeo_orchestra_keyword_research',
            nonce: seoAeoOrchestra.nonce,
            niche: niche,
            language: lang,
            max_keywords: maxKw
        }).done(function(resp) {
            $('#orch-kr-loading').slideUp(150);
            $btn.prop('disabled', false);
            // 3.38.2 Task 8 — distinguish typed empty_result from generic error.
            // Backend Module 13 returns {error: "empty_result", message, meta:
            // {refunded: 15}} with HTTP 422; PHP wp_send_json passes the body
            // through (HTTP 200 from JS perspective). Show GREEN refund banner
            // + skip history save (no debit to log).
            if (resp && typeof resp.error === 'string' && resp.error === 'empty_result') {
                var refunded = (resp.meta && resp.meta.refunded) || 15;
                $('#orch-kr-results').html(
                    '<div class="orchestra-notice success" style="background:#dcfce7;border-color:#86efac;color:#065f46;padding:14px 18px;border-radius:8px;">' +
                    '<strong>✓ ' + (krT.refundedTitle || 'Crediti rimborsati') + '</strong><br>' +
                    (resp.message || krT.emptyResultMsg || 'Il modello AI non ha generato keyword sufficienti.') +
                    ' <span style="opacity:0.8;">(' + refunded + ' cr ' + (krT.refundedLabel || 'rimborsati') + ')</span><br>' +
                    '<span style="font-size:12px;opacity:0.85;">' + (krT.emptyResultSuggest || 'Prova un input più specifico o in inglese.') + '</span>' +
                    '</div>'
                );
                return;
            }
            // Other typed errors (license, credits, upstream_unavailable, etc.)
            // → backend returned JSON with typed error code. Show backend message
            // with REFUND notice if reservation was refunded (server-side log).
            if (resp && typeof resp.error === 'string') {
                var refunded = (resp.meta && resp.meta.refunded) || 0;
                if (refunded > 0) {
                    $('#orch-kr-results').html(
                        '<div class="orchestra-notice success" style="background:#dcfce7;border-color:#86efac;color:#065f46;padding:14px 18px;border-radius:8px;">' +
                        '<strong>✓ ' + (krT.refundedTitle || 'Crediti rimborsati') + '</strong><br>' +
                        escapeHtml(resp.message || resp.error) +
                        ' <span style="opacity:0.8;">(' + refunded + ' cr ' + (krT.refundedLabel || 'rimborsati') + ')</span>' +
                        '</div>'
                    );
                } else {
                    $('#orch-kr-results').html('<div class="orchestra-notice error">' + escapeHtml(resp.message || resp.error) + '</div>');
                }
                return;
            }
            // 3.38.7 — WP-side network / nginx failure (error === true bool, NOT a
            // typed error). Most common cause: LLM call took longer than nginx
            // proxy_read_timeout (was 120s, now 300s in v3.38.7). Show a
            // timeout-flavoured banner with retry suggestion. Backend Module 13
            // refund already triggered server-side when reservation expires
            // via TTL or when the next reserve call's reconcile loop runs.
            if (!resp || resp.error) {
                var rawMsg = (resp && (resp.message || resp.error)) || '';
                var msgStr = String(rawMsg).toLowerCase();
                var isTimeoutLike = msgStr.indexOf('timeout') !== -1 ||
                                    msgStr.indexOf('timed out') !== -1 ||
                                    msgStr.indexOf('risposta non valida') !== -1 ||
                                    msgStr.indexOf('curl') !== -1;
                if (isTimeoutLike) {
                    $('#orch-kr-results').html(
                        '<div class="orchestra-notice error" style="padding:14px 18px;border-radius:8px;">' +
                        '<strong>⏱ ' + (krT.timeoutTitle || 'Tempo esaurito') + '</strong><br>' +
                        (krT.timeoutMsg || 'Il modello AI ha impiegato più tempo del previsto. Riprova con meno keyword (10-20) o un input più specifico.') + '<br>' +
                        '<span style="font-size:12px;opacity:0.85;">' + (krT.refundDelayed || 'Se i crediti sono stati addebitati erroneamente, verranno rimborsati automaticamente entro 10 minuti.') + '</span>' +
                        '</div>'
                    );
                } else {
                    $('#orch-kr-results').html('<div class="orchestra-notice error">' + escapeHtml(rawMsg || krT.genericError) + '</div>');
                }
                return;
            }
            var html = renderResults(resp);
            $('#orch-kr-results').html(html);
            $('html, body').animate({ scrollTop: $('#orch-kr-results').offset().top - 60 }, 400);

            // Save history (success path only — typed errors above already returned)
            var krRestore = {
                fields: { '#orch-kr-niche': niche, '#orch-kr-language': lang, '#orch-kr-max': maxKw },
                outputs: { '#orch-kr-results': $('#orch-kr-results').html() }
            };
            if (typeof SeoAeoOrchestra !== 'undefined' && SeoAeoOrchestra.saveHistory) {
                SeoAeoOrchestra.saveHistory('keyword_research', 'analysis', niche, resp, resp.credits_consumed || 15, krRestore);
            }
        }).fail(function(xhr) {
            $('#orch-kr-loading').slideUp(150);
            $btn.prop('disabled', false);
            $('#orch-kr-results').html('<div class="orchestra-notice error">' + escapeHtml(krT.networkError) + ' ' + xhr.status + ')</div>');
        });
    });

    // Action: copy single keyword
    $(document).on('click', '.orch-kr-copy-kw', function() {
        var kw = $(this).closest('tr').data('keyword');
        if (kw && navigator.clipboard) {
            navigator.clipboard.writeText(String(kw)).then(function() {
                if (typeof SeoAeoOrchestra !== 'undefined') SeoAeoOrchestra.showNotice(krT.keywordCopied + ' ' + kw, 'success');
            });
        }
    });

    // Action: generate article from keyword
    $(document).on('click', '.orch-kr-generate-article', function() {
        var $row = $(this).closest('tr');
        var kw = $row.data('keyword');
        var topic = $row.data('topic') || kw;
        // Naviga a Contenuti AI con topic+keyword pre-popolati
        var url = '<?php echo esc_js(admin_url('admin.php?page=seo-aeo-content')); ?>';
        url += '&prefill_topic=' + encodeURIComponent(topic);
        url += '&prefill_keyword=' + encodeURIComponent(kw);
        window.location.href = url;
    });

    // Export CSV
    $(document).on('click', '.orch-kr-export-csv', function() {
        var rows = [['keyword','cluster','volume','difficolta','intent','suggested_topic']];
        $('.orch-kr-table tbody tr').each(function() {
            var $tr = $(this);
            rows.push([
                $tr.data('keyword') || '',
                $tr.closest('.orch-kr-cluster').find('.orch-kr-cluster-name').text().replace(/^📁\s*/, '').replace(/\d+\s*keyword$/, '').trim(),
                $tr.find('.orch-kr-tag-volume-alto, .orch-kr-tag-volume-medio, .orch-kr-tag-volume-basso, .orch-kr-tag-volume-nicchia').text(),
                $tr.find('.orch-kr-tag-diff-facile, .orch-kr-tag-diff-medio, .orch-kr-tag-diff-difficile').text(),
                $tr.find('.orch-kr-tag-intent-informational, .orch-kr-tag-intent-transactional, .orch-kr-tag-intent-commercial, .orch-kr-tag-intent-navigational').text(),
                $tr.data('topic') || ''
            ]);
        });
        var csv = rows.map(function(r) {
            return r.map(function(c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(',');
        }).join('\n');
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'keyword-research-' + new Date().toISOString().slice(0,10) + '.csv';
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
    });

    // Copy all
    $(document).on('click', '.orch-kr-copy-all', function() {
        var keywords = [];
        $('.orch-kr-table tbody tr').each(function() { keywords.push($(this).data('keyword')); });
        if (navigator.clipboard) {
            navigator.clipboard.writeText(keywords.join('\n')).then(function() {
                if (typeof SeoAeoOrchestra !== 'undefined') SeoAeoOrchestra.showNotice(keywords.length + ' ' + krT.keywordsCopied, 'success');
            });
        }
    });

    // Load history if container exists
    if (typeof SeoAeoOrchestra !== 'undefined' && SeoAeoOrchestra.loadHistory && $('#history-keyword-research').length) {
        SeoAeoOrchestra.loadHistory('keyword_research', 'history-keyword-research');
    }
});
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>

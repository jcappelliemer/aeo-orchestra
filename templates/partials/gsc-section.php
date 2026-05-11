<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.VariableNotPrefixed
// Reason: template scope. Variables are local to this include/template,
// passed by the calling function via include/require. The Plugin Check
// heuristic doesn't distinguish template-scope locals from globals.
/**
 * Partial: Google Search Console insights section.
 *
 * Self-contained block (HTML + inline CSS + inline JS) che renderizza la card
 * GSC con tutti gli stati (not configured / disconnected / waiting admin /
 * connected con sites + top pages). Originariamente parte di
 * templates/admin-dashboard.php (3.10.0+, 3.11.0 hybrid mode).
 * Estratto in 3.28.0 per la Dashboard home wizard.
 *
 * Tutti i selettori CSS/JS (`.orch-gsc-*`) e gli action AJAX restano identici
 * all'implementazione originale: handler in includes/class-ajax-handlers.php
 * (`ajax_gsc_status`, `ajax_gsc_connect_url`, `ajax_gsc_list_sites`,
 *  `ajax_gsc_top_pages`, `ajax_gsc_disconnect`).
 */
if (!defined('ABSPATH')) exit;

if (!isset($T) || !is_callable($T)) {
    $T = function($s) { return class_exists('SEO_AEO_T') ? SEO_AEO_T::t($s) : $s; };
}

// Quando il partial è incluso nella Dashboard home, vogliamo l'auto-load
// del status alla page load (senza click "Mostra"). Possibile override:
// $orch_gsc_auto_load (bool, default true).
$orch_gsc_auto_load = isset($orch_gsc_auto_load) ? (bool)$orch_gsc_auto_load : true;
$body_style = $orch_gsc_auto_load ? '' : 'style="display:none;"';
$toggle_label = $orch_gsc_auto_load ? $T('Nascondi') : $T('Mostra');
$toggle_aria = $orch_gsc_auto_load ? 'true' : 'false';

// Chart.js: enqueue once, idempotent. Si limita a inserire il script CDN
// se non è già stato registrato/enqueued altrove (più istanze del partial sicure).
if (function_exists('wp_script_is') && !wp_script_is('chartjs', 'enqueued') && !wp_script_is('chartjs', 'registered')) {
    wp_register_script('chartjs', SEO_AEO_URL . 'assets/vendor/chart.umd.min.js', array(), '4.4.0', true);
    wp_enqueue_script('chartjs');
}
?>

<div class="orchestra-v3">
<div class="orch3-card orch-gsc-card">
    <div class="orch-gsc-head">
        <h2 class="orch3-h2">📊 <?php echo esc_html($T('Insights da Search Console')); ?></h2>
        <button type="button" class="orch3-btn orch3-btn-ghost orch-gsc-toggle" aria-expanded="<?php echo esc_attr($toggle_aria); ?>"><?php echo esc_html($toggle_label); ?></button>
    </div>
    <div class="orch-gsc-body" <?php echo esc_html($body_style); ?>>
        <div id="orch-gsc-status-loader" class="orch3-muted" style="padding:8px 0;">
            <span class="rv-spinner"></span> <?php echo esc_html($T('Verifico stato connessione…')); ?>
        </div>
        <div id="orch-gsc-content" style="display:none;"></div>
    </div>
</div>
</div>

<?php ob_start(); ?>
/* 3.10.0 — GSC card */
.orchestra-v3 .orch-gsc-card { margin-top: 14px; }
.orchestra-v3 .orch-gsc-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
.orchestra-v3 .orch-gsc-head .orch3-h2 { margin: 0; }
.orchestra-v3 .orch-gsc-body { margin-top: 10px; }
.orchestra-v3 .orch-gsc-disabled,
.orchestra-v3 .orch-gsc-pitch { padding: 14px 16px; background: #f1f5f9; border-radius: 8px; font-size: 13px; line-height: 1.55; color: #1f2937; }
.orchestra-v3 .orch-gsc-pitch p { margin: 0 0 10px; }
.orchestra-v3 .orch-gsc-pitch p:last-of-type { margin-bottom: 14px; }
.orchestra-v3 .orch-gsc-pitch code { background: #e2e8f0; padding: 1px 5px; border-radius: 3px; font-size: 11.5px; }
.orchestra-v3 .orch-gsc-err { padding: 10px 12px; background: #fee2e2; color: #991b1b; border-left: 3px solid #dc2626; border-radius: 6px; font-size: 13px; margin-top: 8px; }
.orchestra-v3 .orch-gsc-warn { padding: 10px 12px; background: #fff7ed; color: #7c2d12; border-left: 3px solid #f59e0b; border-radius: 6px; font-size: 13px; }
.orchestra-v3 .orch-gsc-connected-bar { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 10px 14px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; font-size: 13px; color: #065f46; flex-wrap: wrap; }
.orchestra-v3 .orch-gsc-connected-info { display: flex; align-items: center; gap: 8px; flex: 1 1 auto; }
.orchestra-v3 .orch-gsc-connected-dot { display: inline-block; width: 8px; height: 8px; background: #16a34a; border-radius: 50%; box-shadow: 0 0 0 4px rgba(22,163,74,0.15); }
.orchestra-v3 .orch-gsc-site-row { margin-top: 12px; display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; padding: 10px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; }
.orchestra-v3 .orch-gsc-site-row label { display: flex; flex-direction: column; font-size: 11.5px; color: #475569; gap: 3px; }
.orchestra-v3 .orch-gsc-site-row select { min-width: 140px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 5px; font-size: 13px; background: #fff; }
.orchestra-v3 .orch-gsc-pages-meta { margin-top: 12px; font-size: 11.5px; color: #64748b; }
.orchestra-v3 .orch-gsc-cached { background: #e0e7ff; color: #3730a3; padding: 1px 6px; border-radius: 999px; font-size: 10.5px; text-transform: uppercase; margin-left: 4px; }
.orchestra-v3 .orch-gsc-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 12.5px; }
.orchestra-v3 .orch-gsc-table th { text-align: left; padding: 8px 10px; background: #f1f5f9; color: #1f2937; font-weight: 600; font-size: 11.5px; border-bottom: 1px solid #cbd5e1; }
.orchestra-v3 .orch-gsc-table td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; color: #1f2937; }
.orchestra-v3 .orch-gsc-table tbody tr:hover { background: #f8fafc; }
.orchestra-v3 .orch-gsc-rank { color: #94a3b8; width: 26px; }
.orchestra-v3 .orch-gsc-page { max-width: 380px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.orchestra-v3 .orch-gsc-page a { color: #2563eb; text-decoration: none; }
.orchestra-v3 .orch-gsc-page a:hover { text-decoration: underline; }
.orchestra-v3 .orch-gsc-num { text-align: right; font-variant-numeric: tabular-nums; width: 90px; }

/* 3.28.2 — Chart timeline */
.orchestra-v3 .orch-gsc-chart-title { font-size: 13px; font-weight: 600; color: #0a0e27; margin: 0 0 8px; }
.orchestra-v3 .orch-gsc-summary { display: flex; gap: 20px; flex-wrap: wrap; margin: 0 0 12px; padding: 10px 14px; background: linear-gradient(90deg, rgba(0,85,255,0.06), rgba(16,185,129,0.04)); border-radius: 8px; }
.orchestra-v3 .orch-gsc-stat { display: inline-flex; align-items: baseline; gap: 6px; }
.orchestra-v3 .orch-gsc-stat-num { font-weight: 800; font-size: 18px; color: #0a0e27; }
.orchestra-v3 .orch-gsc-stat-lbl { font-size: 12px; color: #64748b; }
.orchestra-v3 .orch-gsc-chart-canvas-wrap { position: relative; width: 100%; height: 220px; padding: 8px 4px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; }

/* 3.31.0 — Pie charts pre/post */
.orchestra-v3 .orch-gsc-pies { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px; }
@media (max-width: 720px) { .orchestra-v3 .orch-gsc-pies { grid-template-columns: 1fr; } }
.orchestra-v3 .orch-gsc-pie-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px 8px; }
.orchestra-v3 .orch-gsc-pie-title { font-size: 13.5px; font-weight: 700; color: #0a0e27; margin: 0 0 4px; }
.orchestra-v3 .orch-gsc-pie-meta { font-size: 11.5px; color: #64748b; margin: 0 0 8px; }
.orchestra-v3 .orch-gsc-pie-canvas-wrap { position: relative; width: 100%; height: 240px; }

/* 3.28.2 — AEO Impact panel */
.orchestra-v3 .orch-gsc-impact { padding: 16px 18px; background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #a7f3d0; border-radius: 10px; }
.orchestra-v3 .orch-gsc-impact-title { margin: 0 0 8px; font-size: 16px; color: #065f46; font-weight: 700; }
.orchestra-v3 .orch-gsc-impact-meta { margin: 0 0 12px; font-size: 12.5px; color: #14532d; line-height: 1.5; }
.orchestra-v3 .orch-gsc-impact-pending { padding: 10px 4px 0; font-size: 13px; color: #065f46; line-height: 1.55; }
.orchestra-v3 .orch-gsc-impact-pending p { margin: 0 0 6px; }
.orchestra-v3 .orch-gsc-impact-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
@media (max-width: 880px) { .orchestra-v3 .orch-gsc-impact-grid { grid-template-columns: repeat(2, 1fr); } }
.orchestra-v3 .orch-gsc-impact-tile { padding: 14px 14px 12px; background: #fff; border: 1.5px solid #e5e7eb; border-radius: 10px; min-height: 110px; display: flex; flex-direction: column; gap: 4px; }
.orchestra-v3 .orch-gsc-impact-tile-arrow { font-size: 18px; line-height: 1; font-weight: 700; }
.orchestra-v3 .orch-gsc-impact-tile-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.6px; color: #475569; font-weight: 600; }
.orchestra-v3 .orch-gsc-impact-tile-value { font-size: 15px; font-weight: 700; color: #0a0e27; line-height: 1.3; margin-top: 2px; }
.orchestra-v3 .orch-gsc-impact-tile-sub { font-size: 11.5px; color: #64748b; line-height: 1.4; margin-top: auto; }
.orchestra-v3 .orch-gsc-impact-tile-up { border-color: #86efac; background: #f0fdf4; }
.orchestra-v3 .orch-gsc-impact-tile-up .orch-gsc-impact-tile-arrow { color: #16a34a; }
.orchestra-v3 .orch-gsc-impact-tile-down { border-color: #fca5a5; background: #fef2f2; }
.orchestra-v3 .orch-gsc-impact-tile-down .orch-gsc-impact-tile-arrow { color: #dc2626; }
.orchestra-v3 .orch-gsc-impact-tile-neutral { border-color: #cbd5e1; background: #f8fafc; }
.orchestra-v3 .orch-gsc-impact-tile-neutral .orch-gsc-impact-tile-arrow { color: #64748b; }
.orchestra-v3 .orch-gsc-impact-note { margin-top: 10px; font-size: 11.5px; color: #475569; }
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

<?php ob_start(); /* 3.36.7: buffer GSC inline JS for SEO_AEO_Inline_Assets::add_inline_script(); previous literal script tag broke admin dashboard layout */ ?>
/* ═════════════════════════════════════════════════════════
   3.10.0 — GSC Insights (extracted to partial 3.28.0)
   ═════════════════════════════════════════════════════════ */
jQuery(function($) {
    var $card = $('.orch-gsc-card');
    if (!$card.length) return;
    if ($card.data('orch-gsc-init')) return;
    $card.data('orch-gsc-init', true);

    var $body = $card.find('.orch-gsc-body');
    var $toggle = $card.find('.orch-gsc-toggle');
    var $loader = $('#orch-gsc-status-loader');
    var $content = $('#orch-gsc-content');

    var statusLoaded = false;
    var currentStatus = null;

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    function gscExtractErr(resp, fallback) {
        if (!resp) return fallback || SeoAeoOrchestra.t('Errore sconosciuto');
        if (typeof resp.message === 'string' && resp.message) return resp.message;
        if (typeof resp.error === 'string' && resp.error) return resp.error;
        if (typeof resp.detail === 'string' && resp.detail) return resp.detail;
        return fallback || SeoAeoOrchestra.t('Errore sconosciuto');
    }

    $toggle.on('click', function() {
        var open = $body.is(':visible');
        $body.slideToggle(180);
        $toggle.attr('aria-expanded', open ? 'false' : 'true').text(open ? SeoAeoOrchestra.t('Mostra') : SeoAeoOrchestra.t('Nascondi'));
        if (!open && !statusLoaded) loadStatus();
    });

    function loadStatus() {
        statusLoaded = true;
        $loader.show();
        $content.hide();
        $.post(ajaxurl, {
            action: 'seo_aeo_orchestra_gsc_status',
            nonce: seoAeoOrchestra.nonce
        }).done(function(resp) {
            $loader.hide();
            $content.show();
            if (!resp || resp.error) {
                $content.html(renderError(gscExtractErr(resp, SeoAeoOrchestra.t('Errore stato GSC'))));
                return;
            }
            currentStatus = resp;
            if (!resp.configured_on_server) {
                $content.html(renderNotConfigured());
            } else if (!resp.connected) {
                if (resp.system_mode && !resp.is_admin) {
                    $content.html(renderClientWaitingForAdmin());
                } else {
                    $content.html(renderDisconnected(resp));
                }
            } else {
                $content.html(renderConnected(resp));
                loadSites();
            }
        }).fail(function(xhr) {
            $loader.hide();
            $content.show().html(renderError(SeoAeoOrchestra.t('Errore rete') + ' (' + xhr.status + ')'));
        });
    }

    function renderError(msg) {
        return '<div class="orch-gsc-err">' + escapeHtml(msg) + '</div>';
    }

    function renderNotConfigured() {
        return '<div class="orch-gsc-disabled">' +
                 '<strong>' + SeoAeoOrchestra.t('Integrazione GSC non ancora attiva sul server.') + '</strong><br>' +
                 SeoAeoOrchestra.t('Il provider del plugin sta ultimando la configurazione OAuth con Google. Riprova fra qualche giorno o contatta il supporto.') +
               '</div>';
    }

    function renderDisconnected(s) {
        var adminNote = (s && s.system_mode && s.is_admin)
            ? '<p class="orch3-muted"><strong>' + SeoAeoOrchestra.t('Modalità admin centralizzata:') + '</strong> ' + SeoAeoOrchestra.t('connettendoti qui abiliti GSC per tutti i clienti del team. Una sola autorizzazione necessaria.') + '</p>'
            : '';
        return '<div class="orch-gsc-pitch">' +
                 '<p><strong>' + SeoAeoOrchestra.t('Collega Search Console') + '</strong> ' + SeoAeoOrchestra.t('per vedere quali pagine ricevono impressioni e clic da Google, e priorizzare le ottimizzazioni dove c\'è già traffico.') + '</p>' +
                 adminNote +
                 '<p class="orch3-muted">' + SeoAeoOrchestra.t('L\'autorizzazione è in sola lettura (scope') + ' <code>webmasters.readonly</code>).</p>' +
                 '<button type="button" class="orch3-btn orch3-btn-primary" id="orch-gsc-connect-btn">🔍 ' + SeoAeoOrchestra.t('Connetti Google Search Console') + '</button>' +
               '</div>';
    }

    function renderClientWaitingForAdmin() {
        return '<div class="orch-gsc-disabled">' +
                 '<strong>' + SeoAeoOrchestra.t('Search Console gestito centralmente dal team Orchestra.') + '</strong><br>' +
                 SeoAeoOrchestra.t('L\'amministratore non ha ancora attivato la connessione, oppure il tuo dominio non risulta tra le property GSC autorizzate. Contatta il supporto per attivare gli insights di Search Console su questo sito.') +
               '</div>';
    }

    function renderConnected(s) {
        var connectedAt = s.connected_at ? new Date(s.connected_at).toLocaleDateString(SeoAeoOrchestra.bcp47(), {day:'2-digit',month:'short',year:'numeric'}) : '—';
        var disconnectBtn = (s.system_mode && !s.is_admin)
            ? ''
            : '<button type="button" class="orch3-btn orch3-btn-ghost orch3-btn-sm" id="orch-gsc-disconnect-btn">' + SeoAeoOrchestra.t('Disconnetti') + '</button>';
        var connectedLabel = (s.system_mode && !s.is_admin)
            ? SeoAeoOrchestra.t('GSC attivo · gestito dal team Orchestra')
            : SeoAeoOrchestra.t('Connesso come') + ' <strong>' + escapeHtml(s.email || '?') + '</strong> · ' + SeoAeoOrchestra.t('dal') + ' ' + escapeHtml(connectedAt);
        return '<div class="orch-gsc-connected-bar">' +
                 '<div class="orch-gsc-connected-info">' +
                   '<span class="orch-gsc-connected-dot"></span>' +
                   connectedLabel +
                 '</div>' +
                 disconnectBtn +
               '</div>' +
               '<div id="orch-gsc-sites" class="orch-gsc-sites">' +
                 '<div class="orch3-muted" style="padding:8px 0;"><span class="rv-spinner"></span> ' + SeoAeoOrchestra.t('Carico siti GSC…') + '</div>' +
               '</div>' +
               '<div id="orch-gsc-impact" class="orch-gsc-impact" style="display:none;margin:24px 0 0;"></div>' +
               '<div id="orch-gsc-chart-wrap" style="display:block;margin:18px 0 0;">' +
                 '<div class="orch-gsc-chart-title">' + SeoAeoOrchestra.t('Distribuzione clicks ultimi 28 giorni') + '</div>' +
                 '<div id="orch-gsc-summary" class="orch-gsc-summary"></div>' +
                 '<div class="orch-gsc-chart-canvas-wrap" style="height:280px;position:relative;"><canvas id="orch-gsc-chart"></canvas></div>' +
               '</div>' +
               '<div id="orch-gsc-pages-wrap"></div>';
    }

    function loadSites() {
        $.post(ajaxurl, {
            action: 'seo_aeo_orchestra_gsc_list_sites',
            nonce: seoAeoOrchestra.nonce
        }).done(function(resp) {
            var $sites = $('#orch-gsc-sites');
            if (!resp || resp.error) {
                $sites.html(renderError(gscExtractErr(resp, 'Lista siti fallita')));
                return;
            }
            var sites = Array.isArray(resp.sites) ? resp.sites.filter(function(s){
                return s.permission_level && s.permission_level.indexOf('siteUnverifiedUser') === -1;
            }) : [];
            if (!sites.length) {
                var msg = (currentStatus && currentStatus.system_mode && !currentStatus.is_admin)
                    ? SeoAeoOrchestra.t('Il tuo dominio non risulta tra le property GSC del team Orchestra. Aggiungi l\'email amministratore Orchestra come Utente o Proprietario nella tua property Search Console, poi ricarica la dashboard.')
                    : SeoAeoOrchestra.t('Nessun sito verificato in GSC con questo account.');
                $sites.html('<div class="orch-gsc-warn">' + msg + '</div>');
                return;
            }
            var currentHost = (function() {
                try { return window.location.hostname.replace(/^www\./, '').toLowerCase(); } catch(e) { return ''; }
            })();
            var preferred = sites.find(function(s) {
                var u = (s.site_url || '').toLowerCase();
                return u.indexOf(currentHost) !== -1;
            }) || sites[0];

            var optionsHtml = sites.map(function(s) {
                var sel = (s === preferred) ? ' selected' : '';
                return '<option value="' + escapeHtml(s.site_url) + '"' + sel + '>' +
                       escapeHtml(s.site_url) + ' (' + escapeHtml(s.permission_level || '') + ')</option>';
            }).join('');

            $sites.html(
                '<div class="orch-gsc-site-row">' +
                  '<label>' + SeoAeoOrchestra.t('Sito GSC:') + ' <select id="orch-gsc-site-select">' + optionsHtml + '</select></label>' +
                  '<label>' + SeoAeoOrchestra.t('Periodo:') + ' <select id="orch-gsc-days-select">' +
                    '<option value="7">' + SeoAeoOrchestra.t('Ultimi 7gg') + '</option>' +
                    '<option value="30" selected>' + SeoAeoOrchestra.t('Ultimi 30gg') + '</option>' +
                    '<option value="90">' + SeoAeoOrchestra.t('Ultimi 90gg') + '</option>' +
                  '</select></label>' +
                  '<label>' + SeoAeoOrchestra.t('Ordina per:') + ' <select id="orch-gsc-order-select">' +
                    '<option value="impressions" selected>Impressions</option>' +
                    '<option value="clicks">Clicks</option>' +
                  '</select></label>' +
                  '<button type="button" class="orch3-btn orch3-btn-primary orch3-btn-sm" id="orch-gsc-fetch-btn">' + SeoAeoOrchestra.t('Carica top pagine') + '</button>' +
                '</div>'
            );
            if (preferred) loadAllForSite(preferred.site_url, 30, 'impressions');
        }).fail(function(xhr) {
            $('#orch-gsc-sites').html(renderError(SeoAeoOrchestra.t('Errore rete') + ' (' + xhr.status + ')'));
        });
    }

    /* Carica IN PARALLELO: top-pages, timeline-28gg, aeo-impact (3.28.2). */
    function loadAllForSite(siteUrl, days, orderBy) {
        loadTopPages(siteUrl, days, orderBy);
        loadTimeline(siteUrl);
        loadAeoImpact(siteUrl);
    }

    var gscChart = null;
    function loadTimeline(siteUrl) {
        var $wrap = $('#orch-gsc-chart-wrap');
        $wrap.show();
        $.post(ajaxurl, {
            action: 'seo_aeo_orchestra_gsc_timeline',
            nonce: seoAeoOrchestra.nonce,
            site_url: siteUrl,
            days: 28
        }).done(function(resp) {
            if (!resp || resp.error || !Array.isArray(resp.rows) || !resp.rows.length) {
                $wrap.hide();
                return;
            }
            renderTimelineChart(resp.rows);
        }).fail(function() {
            $wrap.hide();
        });
    }

    /* 3.31.1 — Sostituito line chart con DOUGHNUT top 5 pagine.
       Più immediato per utenti business: "ecco le 5 pagine che ti portano traffico". */
    function renderTimelineChart(rows) {
        // rows è la timeline 28gg (giornaliero). Aggreghiamo per i totali, ma per il
        // pie usiamo le top-pages già caricate altrove. Quindi: nascondiamo il canvas
        // qui e lasciamo che renderTopPages renderizzi la torta sui propri dati.
        var canvas = document.getElementById('orch-gsc-chart');
        if (!canvas) return;

        // Calcola totali 28gg da timeline (per overlay summary sopra la torta)
        var totalClicks = 0, totalImp = 0;
        rows.forEach(function(r) {
            totalClicks += (r.clicks || 0);
            totalImp += (r.impressions || 0);
        });
        var summaryEl = document.getElementById('orch-gsc-summary');
        if (summaryEl) {
            summaryEl.innerHTML =
                '<div class="orch-gsc-stat"><span class="orch-gsc-stat-num">' + totalClicks.toLocaleString() + '</span> <span class="orch-gsc-stat-lbl">' + SeoAeoOrchestra.t('clicks 28gg') + '</span></div>' +
                '<div class="orch-gsc-stat"><span class="orch-gsc-stat-num">' + totalImp.toLocaleString() + '</span> <span class="orch-gsc-stat-lbl">' + SeoAeoOrchestra.t('impressions 28gg') + '</span></div>';
        }

        // Hide il canvas (verrà reusato da renderPieTopPages)
        canvas.style.display = 'none';
    }

    /* Renderizza torta top 5 pagine per clicks (più intuitivo del line chart) */
    function renderPieTopPages(rows) {
        var canvas = document.getElementById('orch-gsc-chart');
        if (!canvas || typeof Chart === 'undefined' || !Array.isArray(rows) || !rows.length) return;
        canvas.style.display = '';

        if (gscChart) {
            try { gscChart.destroy(); } catch(e) {}
            gscChart = null;
        }

        var top = rows.slice(0, 5);
        var others = rows.slice(5);
        var othersSum = others.reduce(function(s, r) { return s + (r.clicks || 0); }, 0);

        var labels = top.map(function(r) {
            // Take last URL segment for compact label
            var p = (r.page || '').replace(/\/$/, '').split('/').pop() || '/';
            if (p.length > 35) p = p.slice(0, 32) + '…';
            return p || '/';
        });
        var values = top.map(function(r) { return r.clicks || 0; });
        if (othersSum > 0) {
            labels.push(SeoAeoOrchestra.t('Altre pagine'));
            values.push(othersSum);
        }

        var palette = ['#0055FF', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#94A3B8'];

        gscChart = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: palette.slice(0, values.length),
                    borderColor: '#fff',
                    borderWidth: 2,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '55%',
                plugins: {
                    legend: { position: 'right', labels: { font: { size: 11 } } },
                    title: { display: true, text: SeoAeoOrchestra.t('Top 5 pagine per clicks (ultimi 28 giorni)'), font: { size: 13, weight: '600' } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                var val = ctx.parsed;
                                var total = ctx.dataset.data.reduce(function(a,b){return a+b;},0);
                                var pct = total ? ((val/total)*100).toFixed(1) : '0';
                                return ctx.label + ': ' + val + ' clicks (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    function loadAeoImpact(siteUrl) {
        var $box = $('#orch-gsc-impact');
        $box.show().html('<div class="orch3-muted" style="padding:8px 0;"><span class="rv-spinner"></span> ' + SeoAeoOrchestra.t('Calcolo impatto AEO Orchestra…') + '</div>');
        $.post(ajaxurl, {
            action: 'seo_aeo_orchestra_gsc_aeo_impact',
            nonce: seoAeoOrchestra.nonce,
            site_url: siteUrl
        }).done(function(resp) {
            if (!resp || resp.error) {
                $box.hide();
                return;
            }
            $box.html(renderImpact(resp));
        }).fail(function() {
            $box.hide();
        });
    }

    function fmtPct(n) {
        if (n == null || isNaN(n)) return '—';
        var sign = n > 0 ? '+' : '';
        return sign + Number(n).toFixed(1) + '%';
    }
    function fmtNum(n) {
        if (n == null) return '—';
        try { return Number(n).toLocaleString(SeoAeoOrchestra.bcp47()); } catch(e) { return String(n); }
    }
    function fmtCtr(n) {
        if (n == null) return '—';
        return (Number(n) * 100).toFixed(2) + '%';
    }
    function fmtPos(n) {
        if (n == null) return '—';
        return Number(n).toFixed(1);
    }

    var gscImpactChartPre = null, gscImpactChartPost = null;
    function _shortenPath(p) {
        if (!p) return '/';
        try {
            var u = new URL(p);
            var path = u.pathname || '/';
            if (path.length > 30) path = path.slice(0, 27) + '…';
            return path;
        } catch(e) {
            var s = String(p);
            return s.length > 30 ? s.slice(0, 27) + '…' : s;
        }
    }
    function renderPieCharts(resp) {
        var pre = (resp.pre && Array.isArray(resp.pre.top_pages)) ? resp.pre.top_pages : [];
        var post = (resp.post && Array.isArray(resp.post.top_pages)) ? resp.post.top_pages : [];
        var palette = ['#0055FF', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];

        function buildChart(canvasId, rows, fallbackTotal) {
            var canvas = document.getElementById(canvasId);
            if (!canvas || typeof Chart === 'undefined') return;
            var labels = rows.map(function(r) { return _shortenPath(r.page); });
            var data = rows.map(function(r) { return Math.max(0, r.clicks || 0); });
            // Se la lista è vuota o tutti zero, costruisci un placeholder con totale aggregato
            var totalShown = data.reduce(function(a,b) { return a+b; }, 0);
            if (totalShown === 0 && fallbackTotal > 0) {
                labels = [SeoAeoOrchestra.t('Tutto il sito')];
                data = [fallbackTotal];
            }
            var prev = (canvasId === 'orch-gsc-pie-pre') ? gscImpactChartPre : gscImpactChartPost;
            if (prev) { try { prev.destroy(); } catch(e) {} }
            var instance = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: palette.slice(0, Math.max(1, data.length)),
                        borderWidth: 2,
                        borderColor: '#fff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 10 } },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    var label = ctx.label || '';
                                    var val = ctx.parsed || 0;
                                    return label + ': ' + val + ' ' + SeoAeoOrchestra.t('clicks');
                                }
                            }
                        }
                    },
                    cutout: '55%',
                }
            });
            if (canvasId === 'orch-gsc-pie-pre') gscImpactChartPre = instance;
            else gscImpactChartPost = instance;
        }
        // defer per assicurarsi che canvas sia in DOM
        setTimeout(function() {
            buildChart('orch-gsc-pie-pre', pre, (resp.pre && resp.pre.clicks) || 0);
            buildChart('orch-gsc-pie-post', post, (resp.post && resp.post.clicks) || 0);
        }, 50);
    }

    function renderImpact(resp) {
        var head = '<h3 class="orch-gsc-impact-title">📈 ' + SeoAeoOrchestra.t('Miglioramento da quando usi AEO Orchestra') + '</h3>';
        if (resp.available === false) {
            if (resp.reason === 'too_recent') {
                var daysLeft = Math.max(0, (resp.days_needed || 14) - (resp.days_since_activation || 0));
                var actDate = resp.activation_date ? new Date(resp.activation_date).toLocaleDateString(SeoAeoOrchestra.bcp47(), {day:'2-digit',month:'short',year:'numeric'}) : '—';
                return head +
                    '<div class="orch-gsc-impact-pending">' +
                      '<p>' + SeoAeoOrchestra.t('I dati pre/post AEO Orchestra saranno disponibili tra') + ' <strong>' + daysLeft + '</strong> ' + SeoAeoOrchestra.t('giorni') +
                      ' (' + SeoAeoOrchestra.t('servono almeno 14 giorni di utilizzo per un confronto significativo') + ').</p>' +
                      '<p class="orch3-muted">' + SeoAeoOrchestra.t('Hai attivato GSC il') + ' <strong>' + escapeHtml(actDate) + '</strong>.</p>' +
                    '</div>';
            }
            // Altri casi (es. non connesso): nascondi del tutto
            return '';
        }

        var post = resp.post || {}, pre = resp.pre || {}, delta = resp.delta || {};
        var daysPre = resp.days_pre || 0;

        // Tile helpers
        function tile(label, deltaVal, isPositionMetric, postValue, preValue, postFmt, preFmt) {
            var positive;
            if (isPositionMetric) {
                positive = deltaVal < 0; // posizione che scende = miglioramento
            } else {
                positive = deltaVal > 0;
            }
            var neutral = (deltaVal === 0 || deltaVal == null);
            var cls = neutral ? 'orch-gsc-impact-tile-neutral' : (positive ? 'orch-gsc-impact-tile-up' : 'orch-gsc-impact-tile-down');
            var arrow = neutral ? '→' : (positive ? '▲' : '▼');
            var bigText;
            if (isPositionMetric) {
                if (deltaVal < 0) {
                    bigText = SeoAeoOrchestra.t('miglioramento di') + ' ' + Math.abs(deltaVal).toFixed(1) + ' ' + SeoAeoOrchestra.t('posizioni');
                } else if (deltaVal > 0) {
                    bigText = '−' + deltaVal.toFixed(1) + ' ' + SeoAeoOrchestra.t('posizioni');
                } else {
                    bigText = '0 ' + SeoAeoOrchestra.t('posizioni');
                }
            } else {
                bigText = fmtPct(deltaVal) + ' ' + label.toLowerCase();
            }
            var sub = postFmt + ' (post) vs ' + preFmt + ' (pre, ' + SeoAeoOrchestra.t('ultimi') + ' ' + daysPre + ' ' + SeoAeoOrchestra.t('gg') + ')';
            return '<div class="orch-gsc-impact-tile ' + cls + '">' +
                     '<div class="orch-gsc-impact-tile-arrow">' + arrow + '</div>' +
                     '<div class="orch-gsc-impact-tile-label">' + escapeHtml(label) + '</div>' +
                     '<div class="orch-gsc-impact-tile-value">' + escapeHtml(bigText) + '</div>' +
                     '<div class="orch-gsc-impact-tile-sub">' + escapeHtml(sub) + '</div>' +
                   '</div>';
        }

        var grid = '<div class="orch-gsc-impact-grid">' +
            tile(SeoAeoOrchestra.t('Clicks'),         delta.clicks_pct,      false, post.clicks,      pre.clicks,      fmtNum(post.clicks),      fmtNum(pre.clicks)) +
            tile(SeoAeoOrchestra.t('Impressions'),    delta.impressions_pct, false, post.impressions, pre.impressions, fmtNum(post.impressions), fmtNum(pre.impressions)) +
            tile(SeoAeoOrchestra.t('CTR'),            delta.ctr_pct,         false, post.ctr,         pre.ctr,         fmtCtr(post.ctr),         fmtCtr(pre.ctr)) +
            tile(SeoAeoOrchestra.t('Posizione media'), delta.position_delta,  true,  post.position,    pre.position,    fmtPos(post.position),    fmtPos(pre.position)) +
            '</div>';

        var note = '';
        if (resp.activation_date_inaccurate) {
            note = '<p class="orch3-muted orch-gsc-impact-note">' + SeoAeoOrchestra.t('Nota: la connessione GSC è stata rifatta in passato — il periodo pre potrebbe essere meno accurato.') + '</p>';
        }
        var actDate = resp.activation_date ? new Date(resp.activation_date).toLocaleDateString(SeoAeoOrchestra.bcp47(), {day:'2-digit',month:'short',year:'numeric'}) : '—';
        var meta = '<p class="orch-gsc-impact-meta">' + SeoAeoOrchestra.t('Confronto') + ' <strong>' + (resp.days_post || daysPre) + ' ' + SeoAeoOrchestra.t('gg') + '</strong> ' +
                   SeoAeoOrchestra.t('post-attivazione (dal') + ' ' + escapeHtml(actDate) + ') ' + SeoAeoOrchestra.t('vs equivalente periodo immediatamente precedente') + '.</p>';

        // 3.31.0: 2 pie charts (top 5 pagine pre vs post) per visualizzazione immediata.
        var pies = '<div class="orch-gsc-pies">' +
            '<div class="orch-gsc-pie-card">' +
              '<div class="orch-gsc-pie-title">📊 ' + SeoAeoOrchestra.t('Top pagine PRIMA di Orchestra') + '</div>' +
              '<div class="orch-gsc-pie-meta">' + SeoAeoOrchestra.t('Periodo') + ': ' + daysPre + ' ' + SeoAeoOrchestra.t('gg') + ' ' + SeoAeoOrchestra.t('pre attivazione') + '</div>' +
              '<div class="orch-gsc-pie-canvas-wrap"><canvas id="orch-gsc-pie-pre"></canvas></div>' +
            '</div>' +
            '<div class="orch-gsc-pie-card">' +
              '<div class="orch-gsc-pie-title">🚀 ' + SeoAeoOrchestra.t('Top pagine DOPO Orchestra') + '</div>' +
              '<div class="orch-gsc-pie-meta">' + SeoAeoOrchestra.t('Periodo') + ': ' + (resp.days_post || daysPre) + ' ' + SeoAeoOrchestra.t('gg') + ' ' + SeoAeoOrchestra.t('post attivazione') + '</div>' +
              '<div class="orch-gsc-pie-canvas-wrap"><canvas id="orch-gsc-pie-post"></canvas></div>' +
            '</div>' +
        '</div>';

        // Render pie charts asincronamente (canvas dev'essere già in DOM)
        renderPieCharts(resp);

        return head + meta + grid + pies + note;
    }

    function loadTopPages(siteUrl, days, orderBy) {
        var $wrap = $('#orch-gsc-pages-wrap');
        $wrap.html('<div class="orch3-muted" style="padding:8px 0;"><span class="rv-spinner"></span> ' + SeoAeoOrchestra.t('Carico top pagine…') + '</div>');
        $.post(ajaxurl, {
            action: 'seo_aeo_orchestra_gsc_top_pages',
            nonce: seoAeoOrchestra.nonce,
            site_url: siteUrl,
            days: days,
            row_limit: 10,
            order_by: orderBy
        }).done(function(resp) {
            if (!resp || resp.error) {
                $wrap.html(renderError(gscExtractErr(resp, SeoAeoOrchestra.t('Top pagine fallito'))));
                return;
            }
            $wrap.html(renderTopPages(resp));
            // 3.31.1 — Renderizza la torta delle top pagine (sostituto del line chart)
            if (Array.isArray(resp.rows) && resp.rows.length) {
                renderPieTopPages(resp.rows);
            }
        }).fail(function(xhr) {
            $wrap.html(renderError(SeoAeoOrchestra.t('Errore rete') + ' (' + xhr.status + ')'));
        });
    }

    function renderTopPages(resp) {
        var rows = Array.isArray(resp.rows) ? resp.rows : [];
        if (!rows.length) {
            return '<div class="orch-gsc-warn" style="margin-top:10px;">' + SeoAeoOrchestra.t('Nessun dato GSC nel periodo selezionato.') + '</div>';
        }
        var fetchedAt = resp.fetched_at ? new Date(resp.fetched_at).toLocaleString(SeoAeoOrchestra.bcp47()) : '—';
        var cachedTag = resp.cached ? ' <span class="orch-gsc-cached" title="' + SeoAeoOrchestra.t('Cache fino a 1h') + '">cached</span>' : '';
        var rowsHtml = rows.map(function(r, i) {
            return '<tr>' +
                     '<td class="orch-gsc-rank">' + (i + 1) + '</td>' +
                     '<td class="orch-gsc-page"><a href="' + escapeHtml(r.page) + '" target="_blank" rel="noopener">' + escapeHtml(r.page) + '</a></td>' +
                     '<td class="orch-gsc-num">' + (r.impressions || 0).toLocaleString(SeoAeoOrchestra.bcp47()) + '</td>' +
                     '<td class="orch-gsc-num">' + (r.clicks || 0).toLocaleString(SeoAeoOrchestra.bcp47()) + '</td>' +
                     '<td class="orch-gsc-num">' + (r.ctr || 0).toFixed(2) + '%</td>' +
                     '<td class="orch-gsc-num">' + (r.position || 0).toFixed(1) + '</td>' +
                   '</tr>';
        }).join('');
        return '<div class="orch-gsc-pages-meta">' + SeoAeoOrchestra.t('Ultimo fetch:') + ' ' + escapeHtml(fetchedAt) + cachedTag + '</div>' +
               '<table class="orch-gsc-table">' +
                 '<thead><tr>' +
                   '<th>#</th><th>' + SeoAeoOrchestra.t('Pagina') + '</th><th>Impressions</th><th>Clicks</th><th>CTR</th><th>' + SeoAeoOrchestra.t('Posizione media') + '</th>' +
                 '</tr></thead>' +
                 '<tbody>' + rowsHtml + '</tbody>' +
               '</table>';
    }

    $card.on('click', '#orch-gsc-connect-btn', function() {
        var $btn = $(this);
        var origLabel = $btn.html();
        $btn.prop('disabled', true).html('<span class="rv-spinner"></span> ' + SeoAeoOrchestra.t('Apro Google…'));
        $.post(ajaxurl, {
            action: 'seo_aeo_orchestra_gsc_connect_url',
            nonce: seoAeoOrchestra.nonce
        }).done(function(resp) {
            if (!resp || resp.error || !resp.auth_url) {
                $btn.prop('disabled', false).html(origLabel);
                alert(SeoAeoOrchestra.t('Errore: ') + ((resp && resp.error) || SeoAeoOrchestra.t('auth_url non disponibile')));
                return;
            }
            var popup = window.open(resp.auth_url, 'gsc_oauth', 'width=560,height=720');
            $btn.prop('disabled', false).html(origLabel);
            window.addEventListener('message', function handler(ev) {
                if (ev.data && ev.data.orch_gsc_connected) {
                    window.removeEventListener('message', handler);
                    try { popup.close(); } catch(e) {}
                    loadStatus();
                }
            });
            var pollAttempts = 0;
            var poll = setInterval(function() {
                pollAttempts++;
                if (pollAttempts > 60) { clearInterval(poll); return; }
                if (popup && popup.closed) {
                    clearInterval(poll);
                    loadStatus();
                }
            }, 3000);
        }).fail(function(xhr) {
            $btn.prop('disabled', false).html(origLabel);
            alert(SeoAeoOrchestra.t('Errore rete') + ' (' + xhr.status + ')');
        });
    });

    $card.on('click', '#orch-gsc-disconnect-btn', function() {
        if (!confirm(SeoAeoOrchestra.t('Disconnettere Search Console? Per riconnettere dovrai autorizzare di nuovo.'))) return;
        var $btn = $(this);
        $btn.prop('disabled', true).text(SeoAeoOrchestra.t('Disconnetto…'));
        $.post(ajaxurl, {
            action: 'seo_aeo_orchestra_gsc_disconnect',
            nonce: seoAeoOrchestra.nonce
        }).done(function(resp) {
            statusLoaded = false;
            loadStatus();
        });
    });

    $card.on('change', '#orch-gsc-site-select, #orch-gsc-days-select, #orch-gsc-order-select', function() {});
    $card.on('click', '#orch-gsc-fetch-btn', function() {
        var siteUrl = $('#orch-gsc-site-select').val();
        var days = parseInt($('#orch-gsc-days-select').val(), 10);
        var orderBy = $('#orch-gsc-order-select').val();
        if (siteUrl) loadAllForSite(siteUrl, days, orderBy);
    });

    // Auto-load se body già visibile (Dashboard wizard) o ?gsc_connected=1 redirect
    if ($body.is(':visible') && !statusLoaded) {
        loadStatus();
    } else if (window.location.search.indexOf('gsc_connected=1') !== -1) {
        $body.show();
        $toggle.attr('aria-expanded', 'true').text(SeoAeoOrchestra.t('Nascondi'));
        loadStatus();
    }
});
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>

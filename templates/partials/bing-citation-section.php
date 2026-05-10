<?php
/**
 * Partial: Bing Webmaster Tools — AI Performance section (since 3.29.0).
 *
 * Killer differentiator: tracks how often the site is cited in AI answers
 * (ChatGPT / Microsoft Copilot / Bing Chat) via Bing Webmaster Tools API.
 *
 * States rendered:
 *   - not_configured (admin hasn't filled BING_OAUTH_CLIENT_ID/SECRET in env)
 *     → "Coming soon — admin setup required"
 *   - disconnected → CTA "Connetti Bing Webmaster Tools" + 3 benefits
 *   - managed (system mode, non-admin) → "Gestito centralmente" notice
 *   - connected → 3 metric tiles, stacked timeline chart, top-10 cited pages
 *
 * AJAX actions handled in includes/class-ajax-handlers.php:
 *   ajax_bing_status, ajax_bing_auth_url, ajax_bing_disconnect,
 *   ajax_bing_list_sites, ajax_bing_ai_performance.
 */
if (!defined('ABSPATH')) exit;

if (!isset($T) || !is_callable($T)) {
    $T = function($s) { return class_exists('SEO_AEO_T') ? SEO_AEO_T::t($s) : $s; };
}

$orch_bing_auto_load = isset($orch_bing_auto_load) ? (bool)$orch_bing_auto_load : true;
$body_style = $orch_bing_auto_load ? '' : 'style="display:none;"';
$toggle_label = $orch_bing_auto_load ? $T('Nascondi') : $T('Mostra');
$toggle_aria = $orch_bing_auto_load ? 'true' : 'false';

// Chart.js: enqueue once, idempotent (gsc-section.php may already have done it).
if (function_exists('wp_script_is') && !wp_script_is('chartjs', 'enqueued') && !wp_script_is('chartjs', 'registered')) {
    wp_register_script('chartjs', SEO_AEO_URL . 'assets/vendor/chart.umd.min.js', array(), '4.4.0', true);
    wp_enqueue_script('chartjs');
}
?>

<div class="orchestra-v3">
<div class="orch3-card orch-bing-card">
    <div class="orch-bing-head">
        <h2 class="orch3-h2">🤖 <?php echo $T('AI Performance'); ?></h2>
        <button type="button" class="orch3-btn orch3-btn-ghost orch-bing-toggle" aria-expanded="<?php echo $toggle_aria; ?>"><?php echo $toggle_label; ?></button>
    </div>
    <p class="orch-bing-sub"><?php echo $T('Quanto le AI citano il tuo sito (ChatGPT, Microsoft Copilot, Bing Chat). Dati esclusivi da Bing Webmaster Tools — nessun altro plugin SEO li espone.'); ?></p>
    <div class="orch-bing-body" <?php echo $body_style; ?>>
        <div id="orch-bing-status-loader" class="orch3-muted" style="padding:8px 0;">
            <span class="rv-spinner"></span> <?php echo $T('Verifico stato connessione…'); ?>
        </div>
        <div id="orch-bing-content" style="display:none;"></div>
    </div>
</div>
</div>

<?php ob_start(); ?>
.orchestra-v3 .orch-bing-card { margin-top: 14px; }
.orchestra-v3 .orch-bing-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
.orchestra-v3 .orch-bing-head .orch3-h2 { margin: 0; }
.orchestra-v3 .orch-bing-sub { margin: 6px 0 12px; color: #475569; font-size: 13px; line-height: 1.5; }
.orchestra-v3 .orch-bing-body { margin-top: 4px; }

.orchestra-v3 .orch-bing-disabled,
.orchestra-v3 .orch-bing-pitch { padding: 16px 18px; background: linear-gradient(135deg, #faf5ff, #f3e8ff); border: 1px solid #d8b4fe; border-radius: 10px; font-size: 13px; line-height: 1.55; color: #1f2937; }
.orchestra-v3 .orch-bing-pitch p { margin: 0 0 10px; }
.orchestra-v3 .orch-bing-pitch .orch-bing-benefits { margin: 12px 0 16px; padding: 0; list-style: none; }
.orchestra-v3 .orch-bing-pitch .orch-bing-benefits li { display: flex; gap: 10px; padding: 6px 0; align-items: flex-start; font-size: 13px; }
.orchestra-v3 .orch-bing-pitch .orch-bing-benefits .orch-bing-bullet { color: #7c3aed; font-weight: 700; flex-shrink: 0; }
.orchestra-v3 .orch-bing-soon { padding: 14px 16px; background: #f1f5f9; border-left: 4px solid #6366f1; border-radius: 8px; }
.orchestra-v3 .orch-bing-soon strong { color: #312e81; }

.orchestra-v3 .orch-bing-err { padding: 10px 12px; background: #fee2e2; color: #991b1b; border-left: 3px solid #dc2626; border-radius: 6px; font-size: 13px; margin-top: 8px; }
.orchestra-v3 .orch-bing-warn { padding: 10px 12px; background: #fff7ed; color: #7c2d12; border-left: 3px solid #f59e0b; border-radius: 6px; font-size: 13px; }
.orchestra-v3 .orch-bing-disclaimer { margin-top: 10px; padding: 8px 12px; background: #fef9c3; border-left: 3px solid #eab308; color: #713f12; border-radius: 6px; font-size: 12px; line-height: 1.45; }

.orchestra-v3 .orch-bing-connected-bar { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 10px 14px; background: linear-gradient(90deg, #eef2ff, #ede9fe); border: 1px solid #c4b5fd; border-radius: 8px; font-size: 13px; color: #312e81; flex-wrap: wrap; }
.orchestra-v3 .orch-bing-connected-info { display: flex; align-items: center; gap: 8px; flex: 1 1 auto; }
.orchestra-v3 .orch-bing-connected-dot { display: inline-block; width: 8px; height: 8px; background: #7c3aed; border-radius: 50%; box-shadow: 0 0 0 4px rgba(124,58,237,0.15); }

.orchestra-v3 .orch-bing-site-row { margin-top: 12px; display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; padding: 10px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; }
.orchestra-v3 .orch-bing-site-row label { display: flex; flex-direction: column; font-size: 11.5px; color: #475569; gap: 3px; }
.orchestra-v3 .orch-bing-site-row select { min-width: 140px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 5px; font-size: 13px; background: #fff; }

.orchestra-v3 .orch-bing-tiles { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 18px; }
@media (max-width: 880px) { .orchestra-v3 .orch-bing-tiles { grid-template-columns: repeat(2, 1fr); } }
.orchestra-v3 .orch-bing-tile { padding: 14px 14px 12px; background: #fff; border: 1.5px solid #e5e7eb; border-radius: 10px; min-height: 110px; display: flex; flex-direction: column; gap: 4px; transition: border-color 0.15s; }
.orchestra-v3 .orch-bing-tile-icon { font-size: 22px; line-height: 1; }
.orchestra-v3 .orch-bing-tile-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.6px; color: #475569; font-weight: 600; }
.orchestra-v3 .orch-bing-tile-value { font-size: 22px; font-weight: 800; color: #0a0e27; line-height: 1.2; margin-top: 2px; }
.orchestra-v3 .orch-bing-tile-trend { font-size: 12px; line-height: 1.4; margin-top: auto; }
.orchestra-v3 .orch-bing-tile-trend.up { color: #16a34a; }
.orchestra-v3 .orch-bing-tile-trend.down { color: #dc2626; }
.orchestra-v3 .orch-bing-tile-trend.neutral { color: #64748b; }
.orchestra-v3 .orch-bing-tile-total { background: linear-gradient(135deg, #faf5ff, #f5f3ff); border-color: #c4b5fd; }
.orchestra-v3 .orch-bing-tile-total .orch-bing-tile-value { color: #6d28d9; }

.orchestra-v3 .orch-bing-chart-title { font-size: 13px; font-weight: 600; color: #0a0e27; margin: 18px 0 8px; }
.orchestra-v3 .orch-bing-chart-canvas-wrap { position: relative; width: 100%; height: 220px; padding: 8px 4px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; }

.orchestra-v3 .orch-bing-pages-title { font-size: 13px; font-weight: 600; color: #0a0e27; margin: 22px 0 8px; }
.orchestra-v3 .orch-bing-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.orchestra-v3 .orch-bing-table th { text-align: left; padding: 8px 10px; background: #f1f5f9; color: #1f2937; font-weight: 600; font-size: 11.5px; border-bottom: 1px solid #cbd5e1; }
.orchestra-v3 .orch-bing-table td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; color: #1f2937; }
.orchestra-v3 .orch-bing-table tbody tr:hover { background: #f8fafc; }
.orchestra-v3 .orch-bing-rank { color: #94a3b8; width: 26px; }
.orchestra-v3 .orch-bing-page { max-width: 380px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.orchestra-v3 .orch-bing-page a { color: #7c3aed; text-decoration: none; }
.orchestra-v3 .orch-bing-page a:hover { text-decoration: underline; }
.orchestra-v3 .orch-bing-num { text-align: right; font-variant-numeric: tabular-nums; width: 90px; }
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

<script type="text/javascript">
/* ═════════════════════════════════════════════════════════
   3.29.0 — Bing AI Performance section
   ═════════════════════════════════════════════════════════ */
jQuery(function($) {
    var $card = $('.orch-bing-card');
    if (!$card.length) return;
    if ($card.data('orch-bing-init')) return;
    $card.data('orch-bing-init', true);

    var $body = $card.find('.orch-bing-body');
    var $toggle = $card.find('.orch-bing-toggle');
    var $loader = $('#orch-bing-status-loader');
    var $content = $('#orch-bing-content');

    var statusLoaded = false;
    var currentStatus = null;
    var bingChart = null;

    function T(s) {
        return (typeof SeoAeoOrchestra !== 'undefined' && SeoAeoOrchestra.t) ? SeoAeoOrchestra.t(s) : s;
    }
    function bcp() {
        try { return SeoAeoOrchestra.bcp47(); } catch(e) { return 'it-IT'; }
    }
    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
    function bingExtractErr(resp, fallback) {
        if (!resp) return fallback || T('Errore sconosciuto');
        if (typeof resp.message === 'string' && resp.message) return resp.message;
        if (typeof resp.error === 'string' && resp.error) return resp.error;
        if (typeof resp.detail === 'string' && resp.detail) return resp.detail;
        return fallback || T('Errore sconosciuto');
    }

    $toggle.on('click', function() {
        var open = $body.is(':visible');
        $body.slideToggle(180);
        $toggle.attr('aria-expanded', open ? 'false' : 'true').text(open ? T('Mostra') : T('Nascondi'));
        if (!open && !statusLoaded) loadStatus();
    });

    function loadStatus() {
        statusLoaded = true;
        $loader.show();
        $content.hide();
        $.post(ajaxurl, {
            action: 'seo_aeo_orchestra_bing_status',
            nonce: seoAeoOrchestra.nonce
        }).done(function(resp) {
            $loader.hide();
            $content.show();
            if (!resp || resp.error) {
                $content.html(renderError(bingExtractErr(resp, T('Errore stato Bing'))));
                return;
            }
            currentStatus = resp;
            // not_configured short-circuit: server has no client_id/secret yet
            if (resp.reason === 'not_configured' || (!resp.configured_on_server && resp.connected !== true)) {
                $content.html(renderNotConfigured());
                return;
            }
            if (!resp.connected) {
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
            $content.show().html(renderError(T('Errore rete') + ' (' + xhr.status + ')'));
        });
    }

    function renderError(msg) {
        return '<div class="orch-bing-err">' + escapeHtml(msg) + '</div>';
    }

    function renderNotConfigured() {
        return '<div class="orch-bing-soon">' +
                 '<strong>🤖 ' + T('AI Performance — Coming soon') + '</strong><br>' +
                 T('L\'integrazione Bing Webmaster Tools è in fase di setup amministrativo (registrazione app Microsoft Azure). Sarà disponibile entro pochi giorni: vedrai automaticamente quante volte ChatGPT, Copilot e Bing Chat citano le tue pagine.') +
               '</div>' +
               '<ul class="orch-bing-benefits" style="margin-top:14px;">' +
                 '<li><span class="orch-bing-bullet">▸</span> ' + T('Citazioni in ChatGPT, Microsoft Copilot e Bing Chat — esclusiva di Orchestra') + '</li>' +
                 '<li><span class="orch-bing-bullet">▸</span> ' + T('Trend ultimi 28 giorni: in quali AI stai crescendo o calando') + '</li>' +
                 '<li><span class="orch-bing-bullet">▸</span> ' + T('Top 10 pagine più citate dalle AI: priorizzi i contenuti che già funzionano') + '</li>' +
               '</ul>';
    }

    function renderDisconnected(s) {
        var adminNote = (s && s.system_mode && s.is_admin)
            ? '<p class="orch3-muted"><strong>' + T('Modalità admin centralizzata:') + '</strong> ' + T('connettendoti qui abiliti AI Performance per tutti i clienti del team.') + '</p>'
            : '';
        return '<div class="orch-bing-pitch">' +
                 '<p><strong>' + T('Collega Bing Webmaster Tools') + '</strong> ' + T('per vedere quante volte le AI citano il tuo sito nelle loro risposte. Esclusiva di Orchestra: nessun altro plugin SEO al mondo lo fa.') + '</p>' +
                 adminNote +
                 '<ul class="orch-bing-benefits">' +
                   '<li><span class="orch-bing-bullet">▸</span> ' + T('Citazioni in ChatGPT, Microsoft Copilot e Bing Chat') + '</li>' +
                   '<li><span class="orch-bing-bullet">▸</span> ' + T('Trend ultimi 28 giorni con grafico per sorgente') + '</li>' +
                   '<li><span class="orch-bing-bullet">▸</span> ' + T('Top 10 pagine più citate dalle AI') + '</li>' +
                 '</ul>' +
                 '<p class="orch3-muted" style="font-size:12px;">' + T('Autorizzazione in sola lettura tramite Microsoft account / Azure AD.') + '</p>' +
                 '<button type="button" class="orch3-btn orch3-btn-primary" id="orch-bing-connect-btn">🤖 ' + T('Connetti Bing Webmaster Tools') + '</button>' +
               '</div>';
    }

    function renderClientWaitingForAdmin() {
        return '<div class="orch-bing-disabled">' +
                 '<strong>' + T('Bing Webmaster gestito centralmente dal team Orchestra.') + '</strong><br>' +
                 T('L\'amministratore non ha ancora attivato la connessione, oppure il tuo dominio non risulta tra le property Bing autorizzate. Contatta il supporto per attivare AI Performance su questo sito.') +
               '</div>';
    }

    function renderConnected(s) {
        var connectedAt = s.connected_at ? new Date(s.connected_at).toLocaleDateString(bcp(), {day:'2-digit',month:'short',year:'numeric'}) : '—';
        var disconnectBtn = (s.system_mode && !s.is_admin)
            ? ''
            : '<button type="button" class="orch3-btn orch3-btn-ghost orch3-btn-sm" id="orch-bing-disconnect-btn">' + T('Disconnetti') + '</button>';
        var connectedLabel = (s.system_mode && !s.is_admin)
            ? T('Bing attivo · gestito dal team Orchestra')
            : T('Connesso come') + ' <strong>' + escapeHtml(s.email || '?') + '</strong> · ' + T('dal') + ' ' + escapeHtml(connectedAt);
        return '<div class="orch-bing-connected-bar">' +
                 '<div class="orch-bing-connected-info">' +
                   '<span class="orch-bing-connected-dot"></span>' +
                   connectedLabel +
                 '</div>' +
                 disconnectBtn +
               '</div>' +
               '<div id="orch-bing-sites" class="orch-bing-sites">' +
                 '<div class="orch3-muted" style="padding:8px 0;"><span class="rv-spinner"></span> ' + T('Carico siti Bing…') + '</div>' +
               '</div>' +
               '<div id="orch-bing-perf-wrap"></div>';
    }

    function loadSites() {
        $.post(ajaxurl, {
            action: 'seo_aeo_orchestra_bing_list_sites',
            nonce: seoAeoOrchestra.nonce
        }).done(function(resp) {
            var $sites = $('#orch-bing-sites');
            if (!resp || resp.error) {
                $sites.html(renderError(bingExtractErr(resp, T('Lista siti fallita'))));
                return;
            }
            var sites = Array.isArray(resp.sites) ? resp.sites : [];
            if (!sites.length) {
                var msg = (currentStatus && currentStatus.system_mode && !currentStatus.is_admin)
                    ? T('Il tuo dominio non risulta tra le property Bing del team Orchestra.')
                    : T('Nessun sito verificato in Bing Webmaster Tools con questo account.');
                $sites.html('<div class="orch-bing-warn">' + msg + '</div>');
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
                return '<option value="' + escapeHtml(s.site_url) + '"' + sel + '>' + escapeHtml(s.site_url) + '</option>';
            }).join('');

            $sites.html(
                '<div class="orch-bing-site-row">' +
                  '<label>' + T('Sito Bing:') + ' <select id="orch-bing-site-select">' + optionsHtml + '</select></label>' +
                  '<label>' + T('Periodo:') + ' <select id="orch-bing-days-select">' +
                    '<option value="7">' + T('Ultimi 7gg') + '</option>' +
                    '<option value="28" selected>' + T('Ultimi 28gg') + '</option>' +
                    '<option value="90">' + T('Ultimi 90gg') + '</option>' +
                  '</select></label>' +
                  '<button type="button" class="orch3-btn orch3-btn-primary orch3-btn-sm" id="orch-bing-fetch-btn">' + T('Aggiorna AI Performance') + '</button>' +
                '</div>'
            );
            if (preferred) loadAIPerformance(preferred.site_url, 28);
        }).fail(function(xhr) {
            $('#orch-bing-sites').html(renderError(T('Errore rete') + ' (' + xhr.status + ')'));
        });
    }

    function loadAIPerformance(siteUrl, days) {
        var $wrap = $('#orch-bing-perf-wrap');
        $wrap.html('<div class="orch3-muted" style="padding:14px 0;"><span class="rv-spinner"></span> ' + T('Calcolo AI citations…') + '</div>');
        $.post(ajaxurl, {
            action: 'seo_aeo_orchestra_bing_ai_performance',
            nonce: seoAeoOrchestra.nonce,
            site_url: siteUrl,
            days: days
        }).done(function(resp) {
            if (!resp || resp.error) {
                $wrap.html(renderError(bingExtractErr(resp, T('Recupero AI Performance fallito'))));
                return;
            }
            $wrap.html(renderPerformance(resp));
        }).fail(function(xhr) {
            $wrap.html(renderError(T('Errore rete') + ' (' + xhr.status + ')'));
        });
    }

    function renderPerformance(resp) {
        if (resp.available === false) {
            var reason = resp.reason || resp.fallback_reason || 'no_data';
            var msg;
            if (reason === 'no_data') {
                msg = T('Nessun dato AI nel periodo selezionato. Bing Webmaster aggiorna le statistiche AI ogni 24-48 ore.');
            } else if (reason === 'managed_not_yet_activated') {
                msg = T('Bing non ancora attivato dal team Orchestra. Contatta il supporto.');
            } else if (reason === 'not_connected') {
                msg = T('Bing non connesso. Connetti prima il tuo account.');
            } else {
                msg = T('Dati AI non ancora disponibili: ') + reason;
            }
            return '<div class="orch-bing-warn" style="margin-top:14px;">' + escapeHtml(msg) + '</div>';
        }

        var src = resp.sources || {};
        var chatgpt = src.chatgpt || {citations:0, trend_pct:0};
        var bingChat = src.bing_chat || {citations:0, trend_pct:0};
        var copilot = src.copilot || {citations:0, trend_pct:0};
        var total = src.total != null ? src.total : 0;

        var disclaimerHtml = '';
        if (resp.disclaimer) {
            disclaimerHtml = '<div class="orch-bing-disclaimer">⚠ ' + escapeHtml(resp.disclaimer) + '</div>';
        }

        var tiles = '<div class="orch-bing-tiles">' +
            tile('🤖', T('Citazioni ChatGPT'), chatgpt.citations, chatgpt.trend_pct) +
            tile('🔷', T('Citazioni Bing Chat'), bingChat.citations, bingChat.trend_pct) +
            tile('🟦', T('Citazioni Copilot'), copilot.citations, copilot.trend_pct) +
            tileTotal('✨', T('Totale citazioni AI'), total, T('ultimi') + ' ' + (resp.period_days || 28) + ' ' + T('gg')) +
        '</div>';

        var chartHtml = '';
        if (Array.isArray(resp.timeline) && resp.timeline.length) {
            chartHtml = '<div class="orch-bing-chart-title">' + T('Andamento citazioni AI ultimi') + ' ' + (resp.period_days || 28) + ' ' + T('giorni') + '</div>' +
                        '<div class="orch-bing-chart-canvas-wrap"><canvas id="orch-bing-chart" height="80"></canvas></div>';
        }

        var topPagesHtml = '';
        if (Array.isArray(resp.top_pages) && resp.top_pages.length) {
            var rowsHtml = resp.top_pages.map(function(r, i) {
                return '<tr>' +
                         '<td class="orch-bing-rank">' + (i + 1) + '</td>' +
                         '<td class="orch-bing-page"><a href="' + escapeHtml(r.page) + '" target="_blank" rel="noopener">' + escapeHtml(r.page) + '</a></td>' +
                         '<td class="orch-bing-num">' + (r.citations || 0).toLocaleString(bcp()) + '</td>' +
                       '</tr>';
            }).join('');
            topPagesHtml = '<div class="orch-bing-pages-title">' + T('Top 10 pagine più citate dalle AI') + '</div>' +
                           '<table class="orch-bing-table">' +
                             '<thead><tr>' +
                               '<th>#</th><th>' + T('Pagina') + '</th><th>' + T('Citazioni') + '</th>' +
                             '</tr></thead>' +
                             '<tbody>' + rowsHtml + '</tbody>' +
                           '</table>';
        }

        var html = disclaimerHtml + tiles + chartHtml + topPagesHtml;

        // Render chart after DOM injection
        if (chartHtml) {
            setTimeout(function() { renderTimelineChart(resp.timeline); }, 30);
        }
        return html;
    }

    function tile(icon, label, value, trendPct) {
        var trendCls = (trendPct > 0) ? 'up' : (trendPct < 0 ? 'down' : 'neutral');
        var trendArrow = (trendPct > 0) ? '▲' : (trendPct < 0 ? '▼' : '→');
        var trendText = (trendPct === 0 || trendPct == null) ? T('Stabile') :
                        (trendArrow + ' ' + (trendPct > 0 ? '+' : '') + Number(trendPct).toFixed(1) + '% ' + T('vs periodo precedente'));
        return '<div class="orch-bing-tile">' +
                 '<div class="orch-bing-tile-icon">' + icon + '</div>' +
                 '<div class="orch-bing-tile-label">' + escapeHtml(label) + '</div>' +
                 '<div class="orch-bing-tile-value">' + (value || 0).toLocaleString(bcp()) + '</div>' +
                 '<div class="orch-bing-tile-trend ' + trendCls + '">' + escapeHtml(trendText) + '</div>' +
               '</div>';
    }

    function tileTotal(icon, label, value, periodLabel) {
        return '<div class="orch-bing-tile orch-bing-tile-total">' +
                 '<div class="orch-bing-tile-icon">' + icon + '</div>' +
                 '<div class="orch-bing-tile-label">' + escapeHtml(label) + '</div>' +
                 '<div class="orch-bing-tile-value">' + (value || 0).toLocaleString(bcp()) + '</div>' +
                 '<div class="orch-bing-tile-trend neutral">' + escapeHtml(periodLabel) + '</div>' +
               '</div>';
    }

    function renderTimelineChart(rows) {
        var canvas = document.getElementById('orch-bing-chart');
        if (!canvas || typeof Chart === 'undefined') return;
        if (bingChart) {
            try { bingChart.destroy(); } catch(e) {}
            bingChart = null;
        }
        bingChart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: rows.map(function(r) { return (r.date || '').slice(5); }),
                datasets: [
                    {
                        label: 'ChatGPT',
                        data: rows.map(function(r) { return r.chatgpt || 0; }),
                        borderColor: '#10a37f',
                        backgroundColor: 'rgba(16,163,127,0.18)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 2
                    },
                    {
                        label: 'Bing Chat',
                        data: rows.map(function(r) { return r.bing_chat || 0; }),
                        borderColor: '#0078d4',
                        backgroundColor: 'rgba(0,120,212,0.18)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 2
                    },
                    {
                        label: 'Copilot',
                        data: rows.map(function(r) { return r.copilot || 0; }),
                        borderColor: '#7c3aed',
                        backgroundColor: 'rgba(124,58,237,0.18)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true, title: { display: true, text: T('Citazioni') } }
                },
                plugins: { legend: { position: 'top' } }
            }
        });
    }

    $card.on('click', '#orch-bing-connect-btn', function() {
        var $btn = $(this);
        var origLabel = $btn.html();
        $btn.prop('disabled', true).html('<span class="rv-spinner"></span> ' + T('Apro Microsoft…'));
        $.post(ajaxurl, {
            action: 'seo_aeo_orchestra_bing_auth_url',
            nonce: seoAeoOrchestra.nonce
        }).done(function(resp) {
            if (resp && resp.reason === 'not_configured') {
                $btn.prop('disabled', false).html(origLabel);
                $content.html(renderNotConfigured());
                return;
            }
            if (!resp || resp.error || !resp.auth_url) {
                $btn.prop('disabled', false).html(origLabel);
                alert(T('Errore: ') + bingExtractErr(resp, T('auth_url non disponibile')));
                return;
            }
            var popup = window.open(resp.auth_url, 'bing_oauth', 'width=560,height=720');
            $btn.prop('disabled', false).html(origLabel);
            window.addEventListener('message', function handler(ev) {
                if (ev.data && ev.data.orch_bing_connected) {
                    window.removeEventListener('message', handler);
                    try { popup.close(); } catch(e) {}
                    statusLoaded = false;
                    loadStatus();
                }
            });
            var pollAttempts = 0;
            var poll = setInterval(function() {
                pollAttempts++;
                if (pollAttempts > 60) { clearInterval(poll); return; }
                if (popup && popup.closed) {
                    clearInterval(poll);
                    statusLoaded = false;
                    loadStatus();
                }
            }, 3000);
        }).fail(function(xhr) {
            $btn.prop('disabled', false).html(origLabel);
            alert(T('Errore rete') + ' (' + xhr.status + ')');
        });
    });

    $card.on('click', '#orch-bing-disconnect-btn', function() {
        if (!confirm(T('Disconnettere Bing Webmaster Tools? Per riconnettere dovrai autorizzare di nuovo.'))) return;
        var $btn = $(this);
        $btn.prop('disabled', true).text(T('Disconnetto…'));
        $.post(ajaxurl, {
            action: 'seo_aeo_orchestra_bing_disconnect',
            nonce: seoAeoOrchestra.nonce
        }).done(function() {
            statusLoaded = false;
            loadStatus();
        });
    });

    $card.on('click', '#orch-bing-fetch-btn', function() {
        var siteUrl = $('#orch-bing-site-select').val();
        var days = parseInt($('#orch-bing-days-select').val(), 10) || 28;
        if (siteUrl) loadAIPerformance(siteUrl, days);
    });

    if ($body.is(':visible') && !statusLoaded) {
        loadStatus();
    } else if (window.location.search.indexOf('bing_connected=1') !== -1) {
        $body.show();
        $toggle.attr('aria-expanded', 'true').text(T('Nascondi'));
        loadStatus();
    }
});
</script>

<?php if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.VariableNotPrefixed
// Reason: template scope. Variables are local to this include/template,
// passed by the calling function via include/require. The Plugin Check
// heuristic doesn't distinguish template-scope locals from globals.
$T = function($s) { return class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t($s)) : esc_html($s); };
?>
<div class="wrap orchestra-admin">
    <h1><span class="dashicons dashicons-chart-pie" style="font-size:28px;margin-right:8px;color:#0055FF;"></span> <?php echo esc_html(SEO_AEO_T::t('Consumo Crediti')); ?></h1>
    <p style="color:#666;"><?php echo esc_html(SEO_AEO_T::t('Monitora l\'utilizzo dei crediti AI e le operazioni effettuate.')); ?></p>

    <!-- Summary Cards -->
    <div id="usage-stats-cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:15px;margin:20px 0;">
        <div class="orchestra-card" style="padding:20px;text-align:center;">
            <div id="usage-total-credits" style="font-size:32px;font-weight:bold;color:#0055FF;">-</div>
            <div style="font-size:13px;color:#666;"><?php echo esc_html(SEO_AEO_T::t('Crediti Totali Usati')); ?></div>
        </div>
        <div class="orchestra-card" style="padding:20px;text-align:center;">
            <div id="usage-total-ops" style="font-size:32px;font-weight:bold;color:#10B981;">-</div>
            <div style="font-size:13px;color:#666;"><?php echo esc_html(SEO_AEO_T::t('Operazioni Totali')); ?></div>
        </div>
        <div class="orchestra-card" style="padding:20px;text-align:center;">
            <div id="usage-top-agent" style="font-size:18px;font-weight:bold;color:#7c3aed;">-</div>
            <div style="font-size:13px;color:#666;"><?php echo esc_html(SEO_AEO_T::t('Agente Piu Usato')); ?></div>
        </div>
        <div class="orchestra-card" style="padding:20px;text-align:center;">
            <div id="usage-today" style="font-size:32px;font-weight:bold;color:#F59E0B;">-</div>
            <div style="font-size:13px;color:#666;"><?php echo esc_html(SEO_AEO_T::t('Crediti Oggi')); ?></div>
        </div>
    </div>

    <!-- Breakdown by Type -->
    <div class="orchestra-card" style="padding:20px;margin-bottom:20px;">
        <h3><span class="dashicons dashicons-chart-bar" style="color:#0055FF;"></span> <?php echo esc_html(SEO_AEO_T::t('Suddivisione per Agente')); ?></h3>
        <div id="usage-by-type" style="margin-top:15px;">
            <div class="orchestra-loading"><span class="spinner is-active"></span> <?php echo esc_html(SEO_AEO_T::t('Caricamento statistiche...')); ?></div>
        </div>
    </div>

    <!-- Daily Usage -->
    <div class="orchestra-card" style="padding:20px;margin-bottom:20px;">
        <h3><span class="dashicons dashicons-calendar-alt" style="color:#10B981;"></span> <?php echo esc_html(SEO_AEO_T::t('Consumo Giornaliero (ultimi 14 giorni)')); ?></h3>
        <div id="usage-by-day" style="margin-top:15px;">
            <div class="orchestra-loading"><span class="spinner is-active"></span> <?php echo esc_html(SEO_AEO_T::t('Caricamento...')); ?></div>
        </div>
    </div>

    <!-- Usage Log Table -->
    <div class="orchestra-card" style="padding:20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
            <h3 style="margin:0;"><span class="dashicons dashicons-list-view" style="color:#6366F1;"></span> <?php echo esc_html(SEO_AEO_T::t('Registro Operazioni')); ?></h3>
            <div>
                <select id="usage-filter-type" style="padding:6px 10px;border-radius:6px;border:1px solid #ddd;">
                    <option value=""><?php echo esc_html(SEO_AEO_T::t('Tutti gli agenti')); ?></option>
                    <option value="seo_analysis"><?php echo esc_html(SEO_AEO_T::t('Analisi SEO')); ?></option>
                    <option value="aeo_analysis"><?php echo esc_html(SEO_AEO_T::t('Analisi AEO')); ?></option>
                    <option value="meta_generation"><?php echo esc_html(SEO_AEO_T::t('Meta Tags')); ?></option>
                    <option value="content_generation"><?php echo esc_html(SEO_AEO_T::t('Contenuti SEO')); ?></option>
                    <option value="aeo_content"><?php echo esc_html(SEO_AEO_T::t('Contenuti AEO')); ?></option>
                    <option value="local_seo"><?php echo esc_html(SEO_AEO_T::t('Local SEO')); ?></option>
                    <option value="image_generation"><?php echo esc_html(SEO_AEO_T::t('Immagine AI')); ?></option>
                    <option value="complete_article"><?php echo esc_html(SEO_AEO_T::t('Articolo Completo')); ?></option>
                    <option value="orchestrator"><?php echo esc_html(SEO_AEO_T::t('Orchestratore')); ?></option>
                    <option value="keyword_suggestions"><?php echo esc_html(SEO_AEO_T::t('Suggerimenti Keyword')); ?></option>
                </select>
            </div>
        </div>
        <table class="widefat striped" id="usage-log-table">
            <thead>
                <tr>
                    <th><?php echo esc_html(SEO_AEO_T::t('Data')); ?></th>
                    <th><?php echo esc_html(SEO_AEO_T::t('Agente')); ?></th>
                    <th><?php echo esc_html(SEO_AEO_T::t('Crediti')); ?></th>
                    <th><?php echo esc_html(SEO_AEO_T::t('Pagina')); ?></th>
                    <th><?php echo esc_html(SEO_AEO_T::t('Dettaglio')); ?></th>
                    <th><?php echo esc_html(SEO_AEO_T::t('Utente')); ?></th>
                </tr>
            </thead>
            <tbody id="usage-log-body">
                <tr><td colspan="6" style="text-align:center;padding:20px;color:#999;"><?php echo esc_html(SEO_AEO_T::t('Caricamento...')); ?></td></tr>
            </tbody>
        </table>
        <div id="usage-pagination" style="display:flex;justify-content:center;gap:8px;margin-top:15px;"></div>
    </div>
</div>

<?php ob_start(); ?>
jQuery(function($) {
    var agentLabels = {
        'seo_analysis': 'Analisi SEO',
        'aeo_analysis': 'Analisi AEO',
        'meta_generation': 'Meta Tags',
        'content_generation': 'Contenuti SEO',
        'aeo_content': 'Contenuti AEO',
        'local_seo': 'Local SEO',
        'image_generation': 'Immagine AI',
        'complete_article': 'Articolo Completo',
        'orchestrator': 'Orchestratore',
        'keyword_suggestions': 'Suggerimenti Keyword'
    };

    var agentColors = {
        'seo_analysis': '#0055FF',
        'aeo_analysis': '#7c3aed',
        'meta_generation': '#10B981',
        'content_generation': '#F59E0B',
        'aeo_content': '#8B5CF6',
        'local_seo': '#EF4444',
        'image_generation': '#EC4899',
        'complete_article': '#6366F1',
        'orchestrator': '#14B8A6',
        'keyword_suggestions': '#84CC16'
    };

    function loadStats() {
        $.post(seoAeoOrchestra.ajaxUrl, {
            action: 'seo_aeo_orchestra_get_usage_stats',
            nonce: seoAeoOrchestra.nonce
        }, function(data) {
            if (!data) return;

            $('#usage-total-credits').text(data.total_credits || 0);
            $('#usage-total-ops').text(data.total_entries || 0);

            // Today's credits
            var today = new Date().toISOString().substring(0, 10);
            var todayData = (data.by_day && data.by_day[today]) ? data.by_day[today].credits : 0;
            $('#usage-today').text(todayData);

            // Top agent
            var topAgent = '-';
            var topCount = 0;
            if (data.by_type) {
                for (var type in data.by_type) {
                    if (data.by_type[type].count > topCount) {
                        topCount = data.by_type[type].count;
                        topAgent = agentLabels[type] || type;
                    }
                }
            }
            $('#usage-top-agent').text(topAgent);

            // By type breakdown
            if (data.by_type) {
                var html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;">';
                for (var t in data.by_type) {
                    var info = data.by_type[t];
                    var color = agentColors[t] || '#666';
                    var label = agentLabels[t] || t;
                    html += '<div style="display:flex;align-items:center;gap:10px;padding:10px;background:#f8fafc;border-radius:8px;border-left:4px solid ' + color + ';">';
                    html += '<div style="flex:1;"><strong style="font-size:13px;">' + label + '</strong><br>';
                    html += '<span style="font-size:12px;color:#666;">' + info.count + ' operazioni</span></div>';
                    html += '<div style="text-align:right;"><strong style="font-size:18px;color:' + color + ';">' + info.credits + '</strong><br>';
                    html += '<span style="font-size:11px;color:#999;">crediti</span></div></div>';
                }
                html += '</div>';
                $('#usage-by-type').html(html);
            } else {
                $('#usage-by-type').html('<p style="color:#999;">Nessun dato disponibile.</p>');
            }

            // Daily chart (last 14 days)
            if (data.by_day) {
                var days = Object.keys(data.by_day).slice(0, 14);
                if (days.length > 0) {
                    var maxCredits = 0;
                    days.forEach(function(d) { if (data.by_day[d].credits > maxCredits) maxCredits = data.by_day[d].credits; });
                    var chartHtml = '<div style="display:flex;align-items:flex-end;gap:4px;height:120px;">';
                    days.forEach(function(d) {
                        var pct = maxCredits > 0 ? Math.max(5, (data.by_day[d].credits / maxCredits) * 100) : 5;
                        var dayLabel = d.substring(5);
                        chartHtml += '<div style="flex:1;display:flex;flex-direction:column;align-items:center;">';
                        chartHtml += '<div style="font-size:10px;color:#666;margin-bottom:4px;">' + data.by_day[d].credits + '</div>';
                        chartHtml += '<div style="width:100%;background:#0055FF;border-radius:4px 4px 0 0;height:' + pct + '%;min-height:4px;"></div>';
                        chartHtml += '<div style="font-size:9px;color:#999;margin-top:4px;">' + dayLabel + '</div>';
                        chartHtml += '</div>';
                    });
                    chartHtml += '</div>';
                    $('#usage-by-day').html(chartHtml);
                } else {
                    $('#usage-by-day').html('<p style="color:#999;">Nessun dato giornaliero.</p>');
                }
            }
        });
    }

    function loadLog(page, filterType) {
        $.post(seoAeoOrchestra.ajaxUrl, {
            action: 'seo_aeo_orchestra_get_usage',
            nonce: seoAeoOrchestra.nonce,
            page_num: page || 1,
            filter_type: filterType || ''
        }, function(data) {
            if (!data || !data.entries || data.entries.length === 0) {
                $('#usage-log-body').html('<tr><td colspan="6" style="text-align:center;padding:20px;color:#999;">Nessuna operazione registrata.</td></tr>');
                $('#usage-pagination').html('');
                return;
            }

            var html = '';
            data.entries.forEach(function(e) {
                var color = agentColors[e.type] || '#666';
                var label = agentLabels[e.type] || e.type;
                html += '<tr>';
                html += '<td style="white-space:nowrap;">' + (e.timestamp || '-') + '</td>';
                html += '<td><span style="background:' + color + '15;color:' + color + ';padding:2px 8px;border-radius:12px;font-size:12px;font-weight:500;">' + label + '</span></td>';
                html += '<td style="font-weight:bold;">' + (e.credits || 0) + '</td>';
                html += '<td>' + (e.page || '-') + '</td>';
                html += '<td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + (e.detail || '-') + '</td>';
                html += '<td>' + (e.user || '-') + '</td>';
                html += '</tr>';
            });
            $('#usage-log-body').html(html);

            // Pagination
            if (data.pages > 1) {
                var pagHtml = '';
                for (var i = 1; i <= data.pages; i++) {
                    var active = i === data.page ? 'background:#0055FF;color:#fff;' : 'background:#f0f0f0;';
                    pagHtml += '<button style="padding:6px 12px;border:none;border-radius:6px;cursor:pointer;' + active + '" onclick="loadUsagePage(' + i + ')">' + i + '</button>';
                }
                $('#usage-pagination').html(pagHtml);
            } else {
                $('#usage-pagination').html('');
            }
        });
    }

    window.loadUsagePage = function(page) {
        loadLog(page, $('#usage-filter-type').val());
    };

    $(document).on('change', '#usage-filter-type', function() {
        loadLog(1, $(this).val());
    });

    // Init
    loadStats();
    loadLog(1, '');
});
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>

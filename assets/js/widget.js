(function($) {
    'use strict';

    var Widget = {
        loaded: false,

        init: function() {
            var $container = $('#seo-aeo-score-widget');
            if (!$container.length) return;

            $container.show();

            $('#seo-aeo-widget-toggle').on('click', function() {
                var $panel = $('#seo-aeo-widget-panel');
                if ($panel.is(':visible')) {
                    $panel.hide();
                } else {
                    $panel.show();
                    if (!Widget.loaded) {
                        Widget.loadScore();
                    }
                }
            });

            $('#seo-aeo-widget-close').on('click', function(e) {
                e.stopPropagation();
                $('#seo-aeo-widget-panel').hide();
            });

            // Auto-load score on page load (shows in badge immediately)
            Widget.loadScore();
        },

        loadScore: function() {
            Widget.loaded = true;
            var $content = $('#seo-aeo-widget-content');
            $content.html('<div class="seo-aeo-widget-loading">Caricamento score...</div>');

            $.post(seoAeoWidget.ajaxUrl, {
                action: 'seo_aeo_orchestra_widget_score',
                nonce: seoAeoWidget.nonce,
                url: seoAeoWidget.pageUrl,
                post_id: seoAeoWidget.postId || 0,
                keyword: ''
            }, function(data) {
                if (data.error) {
                    $content.html('<div class="seo-aeo-widget-error">' + data.error + '</div>');
                    return;
                }
                Widget.renderScores(data);
            }).fail(function() {
                $content.html('<div class="seo-aeo-widget-error">Errore di connessione</div>');
            });
        },

        getScoreClass: function(score) {
            if (score >= 70) return 'score-good';
            if (score >= 40) return 'score-medium';
            return 'score-bad';
        },

        getVisibilityLabel: function(v) {
            if (v === 'high') return 'Alta';
            if (v === 'medium') return 'Media';
            return 'Bassa';
        },

        renderScores: function(data) {
            var seoScore = data.seo_score !== null && data.seo_score !== undefined ? data.seo_score : '--';
            var aeoScore = data.aeo_score !== null && data.aeo_score !== undefined ? data.aeo_score : '--';

            // Update badge
            var seoClass = typeof seoScore === 'number' ? Widget.getScoreClass(seoScore) : '';
            var aeoClass = typeof aeoScore === 'number' ? Widget.getScoreClass(aeoScore) : '';
            $('#seo-aeo-badge-seo').text('SEO ' + seoScore).attr('class', seoClass);
            $('#seo-aeo-badge-aeo').text('AEO ' + aeoScore).attr('class', aeoClass);

            // Build panel — numbers only, no suggestions for public visitors
            var html = '<div class="seo-aeo-widget-scores-grid">';
            html += '<div class="seo-aeo-widget-score-box seo">';
            html += '<span class="score-value ' + seoClass + '">' + seoScore + '</span>';
            html += '<span class="score-label">SEO Score</span></div>';
            html += '<div class="seo-aeo-widget-score-box aeo">';
            html += '<span class="score-value ' + aeoClass + '">' + aeoScore + '</span>';
            html += '<span class="score-label">AEO Score</span></div>';
            html += '</div>';

            // Compact details — only numeric values
            html += '<div class="seo-aeo-widget-details">';
            if (data.ai_visibility) {
                html += '<div class="seo-aeo-widget-detail-row">';
                html += '<span class="seo-aeo-widget-detail-label">Visibilita AI</span>';
                html += '<span class="seo-aeo-widget-detail-value">' + Widget.getVisibilityLabel(data.ai_visibility) + '</span></div>';
            }
            if (data.citability !== null && data.citability !== undefined) {
                html += '<div class="seo-aeo-widget-detail-row">';
                html += '<span class="seo-aeo-widget-detail-label">Citabilita</span>';
                html += '<span class="seo-aeo-widget-detail-value ' + Widget.getScoreClass(data.citability) + '">' + data.citability + '/100</span></div>';
            }
            html += '<div class="seo-aeo-widget-detail-row">';
            html += '<span class="seo-aeo-widget-detail-label">Problemi SEO</span>';
            html += '<span class="seo-aeo-widget-detail-value">' + (data.issues_count || 0) + '</span></div>';
            html += '<div class="seo-aeo-widget-detail-row">';
            html += '<span class="seo-aeo-widget-detail-label">Problemi AEO</span>';
            html += '<span class="seo-aeo-widget-detail-value">' + (data.aeo_issues_count || 0) + '</span></div>';
            html += '</div>';

            // Source info — no suggestions shown publicly
            var sourceLabel = data.source === 'orchestrator' ? 'Dall\'ultima analisi Orchestra' :
                              data.source === 'cache' ? 'Cache' : 'Analisi live';
            html += '<div style="text-align:center;margin-top:8px;font-size:10px;color:rgba(255,255,255,0.3);">';
            html += sourceLabel;
            if (data.last_analysis) html += ' (' + data.last_analysis + ')';
            html += '</div>';

            $('#seo-aeo-widget-content').html(html);
        }
    };

    $(document).ready(function() {
        Widget.init();
    });

})(jQuery);

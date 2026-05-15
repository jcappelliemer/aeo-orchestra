/*
 * 3.35.82 - Verify-Live UI (consolidated + Trasparenza Dati)
 *
 * Patches accumulated:
 *   .81   Streaming Phase 2 (hero card + step pipeline + final report)
 *   .81.1 White-label CTA + Premium++ multi-query 5x sub-step UI
 *   .81.1.1 Premium++ pass-through + status code switch + auto-refund note
 *   .82   Pre-verify preview panel (3 boxes: identity + brand_voice + homepage)
 *         + transparency footer post-run
 */
(function ($) {
    'use strict';

    if (typeof $ === 'undefined') return;

    var PREMIUM_QUERIES = [
        { id: 'what_company_does', label: "Cosa fa l'azienda" },
        { id: 'company_suppliers', label: 'Fornitori e partner' },
        { id: 'competitors',       label: 'Concorrenti' },
        { id: 'target_audience',   label: "Per chi e' adatto" },
        { id: 'pricing_purchase',  label: 'Prezzi e acquisto' }
    ];

    var STEP_LABELS_STANDARD = [
        'Raccogliamo identita sito',
        'Componiamo profilo per AI',
        'Interrogo AI engine',
        'Calcolo Citation Accuracy Score',
        'Generazione report'
    ];

    var STEP_LABELS_PREMIUM = [
        'Raccogliamo identita sito',
        'Componiamo profilo per AI',
        'Interrogo AI engine (5 query parallele)',
        'Calcolo Citation Accuracy Score',
        'Generazione report strutturato'
    ];

    // === 3.35.82: Identity field labels (IT) ===
    var IDENTITY_FIELD_LABELS = {
        'business_name':        'Nome azienda',
        'business_description': 'Descrizione business',
        'industry':             'Settore/industry',
        'about_strategic':      'About strategico',
        'differentiators':      'Differenziatori',
        'territories':          'Territori',
        'use_cases':            'Use cases'
    };

    function escapeHtml(s) {
        if (s === null || typeof s === 'undefined') return '';
        return String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function renderPipeline(tier) {
        var labels = (tier === 'premium_plus_plus') ? STEP_LABELS_PREMIUM : STEP_LABELS_STANDARD;
        var html = '';
        labels.forEach(function (label, i) {
            var n = i + 1;
            html += '<div class="orch-vl-step is-pending" data-step="' + n + '">';
            html += '  <div class="orch-vl-step-icon">' + n + '</div>';
            html += '  <div class="orch-vl-step-body">';
            html += '    <div class="orch-vl-step-label">' + escapeHtml(label) + '<span class="orch-vl-spinner"></span></div>';
            html += '    <div class="orch-vl-step-detail"></div>';
            if (tier === 'premium_plus_plus' && n === 3) {
                html += '    <div class="orch-vl-substeps">';
                PREMIUM_QUERIES.forEach(function (q) {
                    html += '<div class="orch-vl-substep is-pending" data-query-id="' + q.id + '">';
                    html += '<span class="orch-vl-substep-icon">o</span> ' + escapeHtml(q.label);
                    html += '</div>';
                });
                html += '    </div>';
            }
            html += '  </div>';
            html += '</div>';
        });
        return html;
    }

    function activateStep($pipeline, n, detail) {
        $pipeline.find('.orch-vl-step').each(function () {
            var $s = $(this);
            var sn = parseInt($s.attr('data-step'), 10);
            $s.removeClass('is-active is-error');
            if (sn < n) $s.addClass('is-done').removeClass('is-pending');
            else if (sn === n) $s.addClass('is-active').removeClass('is-pending');
        });
        if (typeof detail === 'string') {
            $pipeline.find('[data-step="' + n + '"] .orch-vl-step-detail').text(detail);
        }
        var $sub3 = $pipeline.find('[data-step="3"] .orch-vl-substeps .orch-vl-substep');
        if ($sub3.length && n === 3) {
            $sub3.removeClass('is-pending is-done').addClass('is-active');
        }
    }

    function completePipeline($pipeline) {
        $pipeline.find('.orch-vl-step').removeClass('is-active is-pending').addClass('is-done');
        $pipeline.find('.orch-vl-substep').removeClass('is-active is-pending').addClass('is-done');
        $pipeline.find('.orch-vl-substep-icon').text('OK');
    }

    function errorPipeline($pipeline, n) {
        $pipeline.find('[data-step="' + n + '"]').removeClass('is-active is-pending').addClass('is-error');
    }

    function refreshCredits() {
        if (typeof window.seoAeoRefreshCredits === 'function') {
            try { window.seoAeoRefreshCredits(); } catch (e) {}
            return;
        }
        if (window.seoAeoOrchestra && window.seoAeoOrchestra.ajaxurl) {
            $.post(window.seoAeoOrchestra.ajaxurl, {
                action: 'seo_aeo_orchestra_get_credits',
                nonce:  window.seoAeoOrchestra.nonce || ''
            }).done(function (resp) {
                if (resp && resp.success && resp.data) {
                    var c = (typeof resp.data.credits !== 'undefined') ? resp.data.credits : resp.data.credits_remaining;
                    if (typeof c !== 'undefined') {
                        $('.orch-credits-display, .seo-aeo-credits-num').text(c);
                    }
                }
            });
        }
    }

    function renderErrorReport($report, data) {
        var refunded = parseInt(data.credits_refunded || 0, 10);
        var statusCode = parseInt(data.status_code || (data.error && data.error.status) || 0, 10);
        var title, message;
        switch (statusCode) {
            case 429:
                title = 'Hai effettuato troppe verifiche';
                message = "Riprova tra qualche minuto. La quota giornaliera del tier Standard e' limitata.";
                break;
            case 402:
                title = 'Crediti insufficienti';
                message = 'Ricarica il tuo wallet per continuare.';
                break;
            case 403:
                title = 'Permessi insufficienti';
                message = 'Verifica la tua licenza nelle Impostazioni.';
                break;
            default:
                title = "Errore durante l'analisi";
                message = data.message || 'Il motore AI non ha risposto correttamente. Riprova tra qualche minuto.';
                break;
        }
        var html = '<div class="orch-vl-report-head">';
        html += '<h4>&#9888; ' + escapeHtml(title) + '</h4>';
        html += '</div>';
        html += '<div class="orch-vl-error-body">';
        html += '  <p>' + escapeHtml(message) + '</p>';
        if (refunded > 0) {
            html += '  <p class="orch-vl-refund-note">&#10003; I tuoi <strong>' + refunded + ' crediti</strong> sono stati rimborsati automaticamente.</p>';
        }
        html += '</div>';
        $report.html(html).addClass('is-visible');
    }

    // === 3.35.82: Transparency footer ===
    function renderTransparencyFooter(data) {
        var ctx = data.context_used || data.context_stats;
        if (!ctx) return '';
        var html = '<div class="orch-vl-transparency-footer">';

        // 3.35.82 Patch C: is_complete=false warning banner (amber, prominent, top)
        var idCtx = ctx.identity_profile || {};
        if (idCtx.is_complete === false) {
            var critSat = idCtx.critical_satisfied !== undefined ? idCtx.critical_satisfied : 0;
            var critTot = idCtx.critical_total !== undefined ? idCtx.critical_total : 4;
            html += '<div class="orch-vl-trans-incomplete">';
            html += '<strong>&#9888; Profilo Business incompleto</strong> &mdash; ' + critSat + '/' + critTot + ' campi critici compilati. ';
            html += 'I suggerimenti potrebbero essere generici. ';
            html += '<em>UI di modifica in arrivo nel prossimo update.</em>';
            html += '</div>';
        }

        html += '<h5>&#128203; Analisi basata su:</h5>';
        html += '<ul class="orch-vl-trans-list">';

        // Identity (idCtx already extracted above)
        var pop = idCtx.populated_fields !== undefined ? idCtx.populated_fields : (idCtx.identity_populated_fields || 0);
        var tot = idCtx.total_fields !== undefined ? idCtx.total_fields : (idCtx.identity_total_fields || 7);
        var missing = idCtx.missing || idCtx.identity_missing_fields || [];
        html += '<li>Profilo identita: <strong>' + pop + '/' + tot + '</strong> campi compilati';
        if (missing.length > 0) {
            var missLabels = missing.map(function(f) { return IDENTITY_FIELD_LABELS[f] || f; });
            html += ' <span class="orch-vl-missing">(mancanti: ' + escapeHtml(missLabels.join(', ')) + ')</span>';
        }
        html += '</li>';

        // Brand voice
        var bvCtx = ctx.brand_voice || {};
        if (bvCtx.profile_name || bvCtx.brand_voice_active) {
            var name = bvCtx.profile_name || 'attivo';
            var vocab = bvCtx.vocabulary_count || bvCtx.distinctive_vocabulary_count || 0;
            html += '<li>Brand Voice: profilo &quot;' + escapeHtml(name) + '&quot;';
            if (vocab > 0) html += ' (' + vocab + ' termini vocabolario)';
            html += '</li>';
        } else {
            html += '<li>Brand Voice: <em>nessun profilo attivo</em></li>';
        }

        // Homepage
        var hpCtx = ctx.homepage || {};
        var chars = hpCtx.chars_used || ctx.homepage_chars_used || 0;
        var ok = hpCtx.fetch_success !== undefined ? hpCtx.fetch_success : ctx.homepage_fetch_success;
        if (chars > 0 && ok) {
            html += '<li>Homepage: ' + chars + ' caratteri (Title + Meta + paragrafi)</li>';
        } else {
            html += '<li>Homepage: <em>fetch fallito o vuoto</em></li>';
        }

        html += '</ul>';

        if (missing.length > 0) {
            html += '<div class="orch-vl-trans-cta">';
            html += "<strong>&#x2139; Vuoi suggerimenti più precisi al tuo business?</strong> ";
            html += 'Compila i campi mancanti del profilo identita.';
            var $card = $('#orch-vl-card');
            var editUrl = $card.attr('data-edit-identity-url') || '#';
            html += ' <a href="' + escapeHtml(editUrl) + '" target="_blank" rel="noopener">Vai a Configurazione contenuti &#8594;</a>';
            html += '</div>';
        }

        html += '</div>';
        return html;
    }

    function renderStandardReport($report, data) {
        var score = (typeof data.score === 'number') ? data.score : 0;
        var sclass = score >= 80 ? 'is-good' : (score >= 50 ? 'is-medium' : 'is-poor');
        var insights = data.insights || [];
        var warnings = data.warnings || [];
        var summary = data.summary || '';
        var provider = data.provider || data.tier_used || '?';

        var html = '<div class="orch-vl-report-head">';
        html += '<h4>Citation Accuracy Score</h4>';
        html += '<span class="orch-vl-score ' + sclass + '"><span class="orch-vl-score-num">' + score + '</span><span>/100</span></span>';
        html += '</div>';
        if (summary) html += '<p class="orch-vl-summary">' + escapeHtml(summary) + '</p>';

        if (insights.length) {
            html += '<div class="orch-vl-section"><h5>Insight (' + insights.length + ')</h5>';
            html += '<ul class="orch-vl-list">';
            insights.forEach(function (i) {
                var cat = i.category ? '<span class="orch-vl-cat">' + escapeHtml(i.category) + '</span>' : '';
                html += '<li>' + cat + escapeHtml(i.finding || i.text || '') + '</li>';
            });
            html += '</ul></div>';
        }
        if (warnings.length) {
            html += '<div class="orch-vl-section"><h5>Warning (' + warnings.length + ')</h5>';
            html += '<ul class="orch-vl-list is-warnings">';
            warnings.forEach(function (w) {
                var cat = w.category ? '<span class="orch-vl-cat">' + escapeHtml(w.category) + '</span>' : '';
                html += '<li>' + cat + escapeHtml(w.finding || w.text || '') + '</li>';
            });
            html += '</ul></div>';
        }
        html += '<div class="orch-vl-meta">provider: ' + escapeHtml(provider) + '</div>';
        // .82: transparency footer
        html += renderTransparencyFooter(data);
        $report.html(html).addClass('is-visible');
    }

    function renderPremiumReport($report, data) {
        var score = (typeof data.score === 'number') ? data.score : 0;
        var sclass = score >= 80 ? 'is-good' : (score >= 50 ? 'is-medium' : 'is-poor');
        var breakdown = data.breakdown_by_query || [];
        var suggestions = data.suggestions || [];

        var html = '<div class="orch-vl-report-head">';
        html += '<h4>Citation Accuracy Score - Premium Deep</h4>';
        html += '<span class="orch-vl-score ' + sclass + '"><span class="orch-vl-score-num">' + score + '</span><span>/100</span></span>';
        html += '</div>';

        if (breakdown.length) {
            html += '<div class="orch-vl-section"><h5>Breakdown per query</h5>';
            html += '<div class="orch-vl-breakdown">';
            breakdown.forEach(function (b) {
                var qScore = (b.score === null || typeof b.score === 'undefined') ? '-' : b.score;
                var bclass = (b.status === 'success') ? 'is-success' : 'is-error';
                var conf = b.confidence ? ('<span class="orch-vl-conf is-' + escapeHtml(b.confidence) + '">' + escapeHtml(b.confidence) + '</span>') : '';
                html += '<div class="orch-vl-bd-card ' + bclass + '">';
                html += '  <div class="orch-vl-bd-head"><strong>' + escapeHtml(b.label) + '</strong> ' + conf + '</div>';
                html += '  <div class="orch-vl-bd-score">Score: ' + qScore + '</div>';
                html += '  <div class="orch-vl-bd-answer">' + escapeHtml(b.answer || '') + '</div>';
                html += '</div>';
            });
            html += '</div></div>';
        }

        if (suggestions.length) {
            var byCat = {};
            suggestions.forEach(function (s) {
                var c = s.category || 'other';
                if (!byCat[c]) byCat[c] = [];
                byCat[c].push(s);
            });

            html += '<div class="orch-vl-section">';
            html += '<h5>Suggerimenti (' + suggestions.length + ')</h5>';
            html += '<div class="orch-vl-filters">';
            html += '  <button type="button" class="orch-vl-filter is-active" data-filter="all">Tutti</button>';
            html += '  <button type="button" class="orch-vl-filter" data-filter="high">High</button>';
            html += '  <button type="button" class="orch-vl-filter" data-filter="medium">Medium</button>';
            html += '  <button type="button" class="orch-vl-filter" data-filter="low">Low</button>';
            html += '</div>';
            html += '<div class="orch-vl-cats">';
            Object.keys(byCat).forEach(function (cat) {
                html += '<details class="orch-vl-cat-group" open>';
                html += '<summary>' + escapeHtml(cat) + ' <span class="orch-vl-cat-count">(' + byCat[cat].length + ')</span></summary>';
                html += '<ul class="orch-vl-list orch-vl-suglist">';
                byCat[cat].forEach(function (s) {
                    var sev = s.severity || 'low';
                    var sevBadge = '<span class="orch-vl-sev is-' + escapeHtml(sev) + '">' + escapeHtml(sev) + '</span>';
                    html += '<li class="orch-vl-sug-item" data-severity="' + escapeHtml(sev) + '">';
                    html += '  <div class="orch-vl-sug-head">' + sevBadge + '<strong>' + escapeHtml(s.problem || '') + '</strong></div>';
                    html += '  <div class="orch-vl-sug-fix">' + escapeHtml(s.fix || '') + '</div>';
                    html += '</li>';
                });
                html += '</ul></details>';
            });
            html += '</div></div>';
        }

        html += '<div class="orch-vl-actions">';
        html += '<button type="button" class="orch-vl-btn-secondary orch-vl-export-md">Esporta report Markdown</button>';
        html += '</div>';

        html += '<div class="orch-vl-meta">queries: ' + (data.queries_executed || 5) + '</div>';
        // .82: transparency footer
        html += renderTransparencyFooter(data);
        $report.html(html).addClass('is-visible');

        $report.off('click.vlFilter').on('click.vlFilter', '.orch-vl-filter', function () {
            var f = $(this).attr('data-filter');
            $report.find('.orch-vl-filter').removeClass('is-active');
            $(this).addClass('is-active');
            $report.find('.orch-vl-sug-item').each(function () {
                var sev = $(this).attr('data-severity');
                $(this).toggle(f === 'all' || sev === f);
            });
            $report.find('.orch-vl-cat-group').each(function () {
                var hasVisible = $(this).find('.orch-vl-sug-item:visible').length > 0;
                $(this).toggle(hasVisible || f === 'all');
            });
        });

        $report.off('click.vlExport').on('click.vlExport', '.orch-vl-export-md', function () {
            var md = buildMarkdown(data);
            downloadText(md, 'verify-live-report-' + Date.now() + '.md');
        });
    }

    function buildMarkdown(data) {
        var lines = [];
        lines.push('# Verify Live - Premium Deep Report');
        lines.push('');
        lines.push('**Citation Accuracy Score**: ' + (data.score || 0) + '/100');
        lines.push('**Queries**: ' + (data.queries_executed || 5));
        lines.push('');
        lines.push('## Breakdown per query');
        lines.push('');
        (data.breakdown_by_query || []).forEach(function (b) {
            lines.push('### ' + b.label + ' - ' + (b.score === null ? 'N/A' : b.score + '/100'));
            lines.push('Confidence: `' + (b.confidence || 'medium') + '`');
            lines.push('');
            lines.push(b.answer || '');
            lines.push('');
        });
        lines.push('## Suggerimenti');
        lines.push('');
        var byCat = {};
        (data.suggestions || []).forEach(function (s) {
            var c = s.category || 'other';
            if (!byCat[c]) byCat[c] = [];
            byCat[c].push(s);
        });
        Object.keys(byCat).forEach(function (c) {
            lines.push('### ' + c);
            lines.push('');
            byCat[c].forEach(function (s) {
                lines.push('- **[' + (s.severity || 'low').toUpperCase() + ']** ' + (s.problem || ''));
                lines.push('  - **Fix**: ' + (s.fix || ''));
                lines.push('');
            });
        });
        return lines.join('\n');
    }

    function downloadText(text, filename) {
        var blob = new Blob([text], { type: 'text/markdown;charset=utf-8' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url; a.download = filename;
        document.body.appendChild(a); a.click();
        setTimeout(function () { document.body.removeChild(a); URL.revokeObjectURL(url); }, 100);
    }

    function startVerify(tier) {
        var $card = $('#orch-vl-card');
        if (!$card.length) return;

        var $pipeline = $('#orch-vl-pipeline');
        var $report = $('#orch-vl-report');
        var $error = $('#orch-vl-error');

        $error.removeClass('is-visible').empty();
        $report.removeClass('is-visible').empty();
        $pipeline.html(renderPipeline(tier)).addClass('is-active');

        $('#orch-vl-start-btn, #orch-vl-deep-btn').prop('disabled', true);

        var nonce = $card.attr('data-wp-rest-nonce');
        var root = $card.attr('data-wp-rest-root') || '/wp-json/';
        var siteUrl = $card.attr('data-site-url') || window.location.origin + '/';
        var endpoint = root.replace(/\/$/, '') + '/aeo-orchestra/v1/verify-live';

        activateStep($pipeline, 1);

        fetch(endpoint, {
            method:  'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce':   nonce || '',
                'Accept':       'text/event-stream'
            },
            body: JSON.stringify({ tier: tier, url: siteUrl })
        }).then(function (resp) {
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            var reader = resp.body.getReader();
            var decoder = new TextDecoder('utf-8');
            var buffer = '';

            function pump() {
                return reader.read().then(function (chunk) {
                    if (chunk.done) return;
                    buffer += decoder.decode(chunk.value, { stream: true });
                    var lines = buffer.split('\n');
                    buffer = lines.pop() || '';
                    var event = null, data = null;
                    lines.forEach(function (line) {
                        if (line.indexOf('event:') === 0) event = line.slice(6).trim();
                        else if (line.indexOf('data:') === 0) data = line.slice(5).trim();
                        else if (line === '' && event && data) {
                            try { handleEvent(event, JSON.parse(data), $pipeline, $report, $error, tier); }
                            catch (e) { console.error('VL parse fail:', e, data); }
                            event = null; data = null;
                        }
                    });
                    return pump();
                });
            }
            return pump();
        }).catch(function (err) {
            errorPipeline($pipeline, 1);
            var m = (err.message || '').match(/HTTP\s*(\d+)/i);
            var statusCode = m ? parseInt(m[1], 10) : 0;
            renderErrorReport($report, {
                status: 'http_error',
                status_code: statusCode,
                message: err.message || 'Errore di rete'
            });
            $('#orch-vl-start-btn, #orch-vl-deep-btn').prop('disabled', false);
        });
    }

    function handleEvent(evt, data, $pipeline, $report, $error, tier) {
        switch (evt) {
            case 'start':
                activateStep($pipeline, 1, data.detail || '');
                break;
            case 'progress':
                if (data.step) activateStep($pipeline, parseInt(data.step, 10), data.detail || '');
                break;
            case 'insight':
            case 'warning':
                break;
            case 'complete':
                completePipeline($pipeline);
                if (data.status && data.status !== 'success') {
                    renderErrorReport($report, data);
                } else if (tier === 'premium_plus_plus' && (data.breakdown_by_query || data.suggestions)) {
                    renderPremiumReport($report, data);
                } else {
                    renderStandardReport($report, data);
                }
                refreshCredits();
                $('#orch-vl-start-btn, #orch-vl-deep-btn').prop('disabled', false);
                break;
            case 'error':
                errorPipeline($pipeline, data.step || 1);
                $error.text('Errore: ' + (data.message || 'sconosciuto')).addClass('is-visible');
                $('#orch-vl-start-btn, #orch-vl-deep-btn').prop('disabled', false);
                break;
        }
    }

    // === 3.35.82: Pre-verify preview panel ===

    function loadPreviewPanel(force) {
        var $panel = $('#orch-vl-preview-panel');
        if (!$panel.length) return;
        var ajaxurl = $panel.attr('data-ajaxurl');
        var nonce = $panel.attr('data-nonce');
        var $card = $('#orch-vl-card');
        var siteUrl = $card.attr('data-site-url') || '';

        $('#orch-vl-preview-body').addClass('is-loading');

        $.post(ajaxurl, {
            action: 'seo_aeo_verify_live_preview',
            nonce:  nonce,
            url:    siteUrl,
            force:  force ? 1 : 0
        }).done(function (resp) {
            if (resp && resp.success && resp.data) {
                renderPreviewPanel(resp.data);
            } else {
                renderPreviewError(resp && resp.data ? resp.data.message : 'unknown');
            }
        }).fail(function (xhr) {
            renderPreviewError('AJAX error ' + (xhr ? xhr.status : ''));
        }).always(function () {
            $('#orch-vl-preview-body').removeClass('is-loading');
        });
    }

    function renderPreviewError(msg) {
        $('#orch-vl-preview-body').html(
            '<p style="padding:12px; color:#fff; background: rgba(220,38,38,0.18); border-radius:6px;">' +
            'Errore caricamento preview: ' + escapeHtml(String(msg || 'unknown')) +
            '</p>'
        );
    }

    function renderPreviewPanel(data) {
        var $panel = $('#orch-vl-preview-panel');
        var editIdentityUrl = $panel.attr('data-edit-identity-url');
        var editBrandVoiceUrl = $panel.attr('data-edit-brandvoice-url');

        var idp = data.identity_profile || {};
        var bv = data.brand_voice;
        var hp = data.homepage_context || {};
        var stats = data.context_stats || {};

        var html = '';

        // Box 1: Identity profile
        // 3.35.83: Modifica button restored — punta al nuovo pannello dedicato
        html += '<div class="orch-vl-pv-box">';
        html += '  <div class="orch-vl-pv-box-head">';
        html += '    <span>&#128203; Profilo identita del business</span>';
        html += '    <a href="' + escapeHtml(editIdentityUrl) + '" target="_blank" rel="noopener" class="orch-vl-pv-edit">&#9998; Modifica</a>';
        html += '  </div>';
        html += '  <ul class="orch-vl-pv-fields">';
        var fields = ['business_name','industry','business_description','about_strategic','differentiators','territories','use_cases'];
        fields.forEach(function(f) {
            var v = idp[f];
            var label = IDENTITY_FIELD_LABELS[f] || f;
            var displayValue, isEmpty;
            if (Array.isArray(v)) {
                isEmpty = v.length === 0;
                displayValue = isEmpty ? '<em>vuoto</em>' : v.length + ' element' + (v.length === 1 ? 'o' : 'i');
            } else if (typeof v === 'string') {
                isEmpty = v.trim() === '';
                if (isEmpty) displayValue = '<em>vuoto</em>';
                else if (v.length > 50) displayValue = '<em>' + v.length + ' caratteri</em>';
                else displayValue = escapeHtml(v);
            } else {
                isEmpty = true;
                displayValue = '<em>vuoto</em>';
            }
            html += '<li class="' + (isEmpty ? 'is-empty' : '') + '">';
            html += '  <span class="orch-vl-pv-label">' + escapeHtml(label) + ':</span> ';
            html += '  <span class="orch-vl-pv-value">' + displayValue + '</span>';
            if (isEmpty) html += ' <span class="orch-vl-pv-warn">&#9888;</span>';
            html += '</li>';
        });
        html += '  </ul>';
        html += '</div>';

        // Box 2: Brand Voice
        html += '<div class="orch-vl-pv-box">';
        html += '  <div class="orch-vl-pv-box-head">';
        html += '    <span>&#127908; Brand Voice profile</span>';
        html += '    <a href="' + escapeHtml(editBrandVoiceUrl) + '" target="_blank" rel="noopener" class="orch-vl-pv-edit">&#9998; Modifica</a>';
        html += '  </div>';
        if (bv && bv.summary_for_prompt) {
            html += '  <ul class="orch-vl-pv-fields">';
            if (bv.name)             html += '<li><span class="orch-vl-pv-label">Profilo attivo:</span> ' + escapeHtml(bv.name) + '</li>';
            if (bv.audience)         html += '<li><span class="orch-vl-pv-label">Audience:</span> ' + escapeHtml(bv.audience.length > 80 ? bv.audience.slice(0, 80) + '...' : bv.audience) + '</li>';
            if (bv.tone)             html += '<li><span class="orch-vl-pv-label">Tono:</span> ' + escapeHtml(bv.tone) + '</li>';
            if (bv.distinctive_vocabulary && bv.distinctive_vocabulary.length) {
                html += '<li><span class="orch-vl-pv-label">Vocabolario distintivo (' + bv.distinctive_vocabulary.length + '):</span> ' + escapeHtml(bv.distinctive_vocabulary.slice(0, 5).join(', ')) + (bv.distinctive_vocabulary.length > 5 ? '...' : '') + '</li>';
            }
            if (bv.avoid_words && bv.avoid_words.length) {
                html += '<li><span class="orch-vl-pv-label">Anti-cliche (' + bv.avoid_words.length + '):</span> ' + escapeHtml(bv.avoid_words.slice(0, 4).join(', ')) + (bv.avoid_words.length > 4 ? '...' : '') + '</li>';
            }
            html += '  </ul>';
        } else {
            html += '  <p class="orch-vl-pv-empty"><em>Nessun profilo Brand Voice attivo. Crealo in <a href="' + escapeHtml(editBrandVoiceUrl) + '" target="_blank" rel="noopener">Brand Voice &#8594;</a></em></p>';
        }
        html += '</div>';

        // Box 3: Homepage
        html += '<div class="orch-vl-pv-box">';
        html += '  <div class="orch-vl-pv-box-head">';
        html += '    <span>&#128196; Contenuto homepage (auto-fetch)</span>';
        html += '    <button type="button" class="orch-vl-pv-refresh">&#8635; Aggiorna ora</button>';
        html += '  </div>';
        if (hp && hp.fetch_success) {
            html += '  <ul class="orch-vl-pv-fields">';
            if (hp.url)              html += '<li><span class="orch-vl-pv-label">URL:</span> <code>' + escapeHtml(hp.url) + '</code></li>';
            if (hp.title)            html += '<li><span class="orch-vl-pv-label">Title:</span> ' + escapeHtml(hp.title) + '</li>';
            if (hp.meta_description) {
                var md = hp.meta_description.length > 100 ? hp.meta_description.slice(0, 100) + '...' : hp.meta_description;
                html += '<li><span class="orch-vl-pv-label">Meta description:</span> ' + escapeHtml(md) + ' <em>(' + hp.meta_description.length + ' chars)</em></li>';
            }
            if (hp.first_paragraphs) {
                var fp = hp.first_paragraphs.length > 150 ? hp.first_paragraphs.slice(0, 150) + '...' : hp.first_paragraphs;
                html += '<li><span class="orch-vl-pv-label">Primi paragrafi:</span> ' + escapeHtml(fp) + '</li>';
            }
            if (hp.fetched_at) html += '<li class="orch-vl-pv-meta"><em>Fetched: ' + escapeHtml(hp.fetched_at) + '</em></li>';
            html += '  </ul>';
        } else {
            html += '  <p class="orch-vl-pv-empty"><em>Fetch homepage fallito o vuoto. Verifica che il sito sia raggiungibile.</em></p>';
        }
        html += '</div>';

        // Warning banner if any critical fields empty
        var critical = ['business_description','about_strategic','differentiators','industry'];
        var critMissing = critical.filter(function(f) {
            var v = idp[f];
            if (Array.isArray(v)) return v.length === 0;
            if (typeof v === 'string') return v.trim() === '';
            return true;
        });
        if (critMissing.length > 0) {
            html += '<div class="orch-vl-pv-warning-banner">';
            var verb = (critMissing.length === 1) ? "e' vuoto" : "sono vuoti";
            var nounSing = (critMissing.length === 1) ? "campo critico" : "campi critici";
            html += "  <strong>&#9888; " + critMissing.length + " " + nounSing + " del profilo identita " + verb + ".</strong> ";
            html += "I suggerimenti AI saranno generici. Per risultati precisi compila almeno: ";
            html += critMissing.map(function(f) { return IDENTITY_FIELD_LABELS[f] || f; }).join(", ") + ".";
            html += '</div>';
        }

        html += '<div class="orch-vl-pv-footer">';
        html += "  &#8505; Più questi dati sono completi, più i suggerimenti saranno precisi al tuo business reale.";
        html += '</div>';

        $('#orch-vl-preview-body').html(html);
    }

    // Save collapsed pref via AJAX (lightweight — using user_meta REST or custom AJAX)
    function savePreviewCollapsedPref(collapsed) {
        var $panel = $('#orch-vl-preview-panel');
        var ajaxurl = $panel.attr('data-ajaxurl');
        var nonce = $panel.attr('data-nonce');
        // Reuse existing preview AJAX with extra param? Cleaner: separate AJAX,
        // but to keep .82 minimal we just store in localStorage (cross-session for browser).
        try {
            window.localStorage.setItem('orch_vl_preview_collapsed', collapsed ? '1' : '0');
        } catch (e) {}
    }

    function getPreviewCollapsedPref() {
        try {
            var v = window.localStorage.getItem('orch_vl_preview_collapsed');
            if (v === '1') return true;
            if (v === '0') return false;
        } catch (e) {}
        // Fall back to data-collapsed-pref (server-side wp_user_meta — currently empty for new users)
        var $panel = $('#orch-vl-preview-panel');
        return $panel.attr('data-collapsed-pref') === '1';
    }

    $(function () {
        $(document).on('click', '#orch-vl-start-btn', function () {
            startVerify('standard');
        });
        $(document).on('click', '#orch-vl-deep-btn', function () {
            var msg = "Verifica Premium++: 5 query parallele all'AI per analisi semantica profonda.\nCosta 35 crediti.\n\nProcedere?";
            if (!confirm(msg)) return;
            startVerify('premium_plus_plus');
        });

        // .82: preview panel init
        var $panel = $('#orch-vl-preview-panel');
        if ($panel.length) {
            // Set initial collapsed state per pref
            var collapsed = getPreviewCollapsedPref();
            if (!collapsed) $panel.attr('open', 'open'); // default expanded for new users

            // Load preview data on first expand
            var loaded = false;
            function ensureLoaded() {
                if (!loaded) {
                    loaded = true;
                    loadPreviewPanel(false);
                }
            }
            // If panel is already open on load, fetch immediately
            if ($panel.prop('open')) ensureLoaded();
            $panel.on('toggle', function () {
                if ($panel.prop('open')) ensureLoaded();
                savePreviewCollapsedPref(!$panel.prop('open'));
            });

            // Refresh homepage button
            $(document).on('click', '.orch-vl-pv-refresh', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $('#orch-vl-preview-body').addClass('is-loading');
                loadPreviewPanel(true);
            });
        }
    });

})(window.jQuery);

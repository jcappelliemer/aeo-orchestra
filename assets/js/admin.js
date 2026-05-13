(function($) {
    'use strict';

    // 3.37.0 — Centralized typed-error handler. Backend (FastAPI) returns
    // {error:"<code>", message:"...", meta:{...}} on license/credit
    // failures. PHP wp_send_json() passes the JSON through unchanged.
    // We intercept any AJAX response whose top-level "error" is a string
    // (typed) and render a banner with the right CTA.
    var SeoAeoTypedError = {

        codes: {
            'invalid_license':      { icon: 'shield-alt',  ctaKey: 'contact_url',   ctaLabel: 'Contatta supporto' },
            'license_expired':      { icon: 'update',      ctaKey: 'renew_url',     ctaLabel: 'Rinnova licenza' },
            'license_wrong_domain': { icon: 'admin-site',  ctaKey: 'dashboard_url', ctaLabel: 'Gestisci domini' },
            'insufficient_credits': { icon: 'cart',        ctaKey: 'topup_url',     ctaLabel: 'Ricarica crediti' },
            'insufficient_tier':    { icon: 'star-filled', ctaKey: 'upgrade_url',   ctaLabel: 'Aggiorna piano' },
            'daily_cap_exceeded':   { icon: 'clock',       ctaKey: 'upgrade_url',   ctaLabel: 'Aggiorna piano' },
            'rate_limited':         { icon: 'controls-pause', ctaKey: null, ctaLabel: '' }
        },

        // Returns true if the response was a typed error and we handled it.
        // Returns false otherwise (caller continues normal processing).
        handle: function(response) {
            if (!response || typeof response !== 'object') return false;
            if (typeof response.error !== 'string') return false;
            if (!response.error) return false;

            var code = response.error;
            var msg  = response.message || 'Operazione non riuscita.';
            var meta = response.meta || {};
            var conf = this.codes[code] || { icon: 'warning', ctaKey: null, ctaLabel: '' };

            var ctaUrl = conf.ctaKey ? (meta[conf.ctaKey] || '') : '';

            this.render({
                code: code,
                message: msg,
                meta: meta,
                icon: conf.icon,
                ctaUrl: ctaUrl,
                ctaLabel: conf.ctaLabel
            });
            return true;
        },

        render: function(args) {
            // Idempotent: keep only one banner per code on screen.
            var existing = $('#orch-typed-err-' + args.code);
            if (existing.length) {
                existing.find('.orch-typed-err-msg').text(args.message);
                return;
            }

            var $banner = $(
                '<div id="orch-typed-err-' + args.code + '" class="notice notice-error orch-typed-err" ' +
                'style="margin:12px 0;padding:14px 16px;border-left-width:4px;background:#fef6f6;display:flex;align-items:flex-start;gap:12px;">' +
                  '<span class="dashicons dashicons-' + args.icon + '" style="font-size:24px;color:#b32d2e;flex-shrink:0;margin-top:2px;"></span>' +
                  '<div style="flex:1;min-width:0;">' +
                    '<div class="orch-typed-err-msg" style="font-weight:600;color:#111;margin-bottom:4px;"></div>' +
                    '<div class="orch-typed-err-code" style="font-size:11px;color:#666;font-family:monospace;"></div>' +
                  '</div>' +
                  (args.ctaUrl ? '<a class="button button-primary" target="_blank" rel="noopener" style="flex-shrink:0;">' + args.ctaLabel + '</a>' : '') +
                  '<button type="button" class="notice-dismiss" aria-label="Chiudi"></button>' +
                '</div>'
            );
            $banner.find('.orch-typed-err-msg').text(args.message);
            $banner.find('.orch-typed-err-code').text('error: ' + args.code);
            if (args.ctaUrl) $banner.find('a.button').attr('href', args.ctaUrl);
            $banner.find('.notice-dismiss').on('click', function() { $banner.fadeOut(180, function() { $(this).remove(); }); });

            // Mount: prefer the .wrap container of the current admin page;
            // fall back to body if not found.
            var $mount = $('.wrap').first();
            if (!$mount.length) $mount = $('body');
            $mount.prepend($banner);

            try { window.scrollTo({ top: 0, behavior: 'smooth' }); } catch (e) {}
        },

        // Bootstrap: wire a global ajaxSuccess listener on admin-ajax.php
        // responses. Note: wp_send_json() responds HTTP 200 even when the
        // body carries a typed error, so .fail() handlers never fire.
        bootstrap: function() {
            var self = this;
            $(document).on('ajaxSuccess', function(event, jqXHR, ajaxOptions, data) {
                try {
                    if (!ajaxOptions || !ajaxOptions.url) return;
                    if (ajaxOptions.url.indexOf('admin-ajax.php') === -1) return;
                    if (!data) return;
                    // jQuery may give us already-parsed object or raw string.
                    var payload = data;
                    if (typeof data === 'string') {
                        try { payload = JSON.parse(data); } catch (e) { return; }
                    }
                    // WP usually wraps with {success:bool, data:...}; raw
                    // wp_send_json($result) passes through unchanged.
                    if (payload && typeof payload === 'object') {
                        if (payload.data && typeof payload.data === 'object') {
                            self.handle(payload.data);
                        }
                        self.handle(payload);
                    }
                } catch (e) {
                    if (window.console && console.warn) console.warn('[SeoAeoTypedError]', e);
                }
            });
        }
    };

    // Expose for callers who want imperative checking inside their own
    // success handler (e.g. `if (SeoAeoTypedError.handle(resp)) return;`).
    window.SeoAeoTypedError = SeoAeoTypedError;

    $(function() { SeoAeoTypedError.bootstrap(); });

    var SeoAeoOrchestra = {

        // i18n helper (3.25.1): translate string via window.seoAeoOrchestra.i18n map.
        // Fallback all'originale italiano se chiave non in mappa o locale=it.
        t: function(s) {
            if (!s || !window.seoAeoOrchestra || !window.seoAeoOrchestra.i18n) return s;
            return window.seoAeoOrchestra.i18n[s] || s;
        },

        // 3.26.4: BCP-47 locale tag dinamico per toLocaleString()/toLocaleDateString().
        bcp47: function() {
            var l = (window.seoAeoOrchestra && window.seoAeoOrchestra.locale) || 'it';
            switch (l) {
                case 'en': return 'en-US';
                case 'es': return 'es-ES';
                case 'fr': return 'fr-FR';
                case 'de': return 'de-DE';
                default:   return 'it-IT';
            }
        },

        // Tier check for premium features
        isPremiumTier: function() {
            var tier = (seoAeoOrchestra.licenseType || '').toLowerCase();
            return ['professional', 'team', 'subscription_pro', 'b2b_custom'].indexOf(tier) !== -1;
        },

        showUpgradePrompt: function() {
            // Remove existing if any
            $('.orch-upgrade-overlay').remove();
            var html = '<div class="orch-upgrade-overlay" onclick="jQuery(this).remove();">';
            html += '<div class="orch-upgrade-card" onclick="event.stopPropagation();">';
            html += '<div class="orch-upgrade-icon"><span class="dashicons dashicons-star-filled"></span></div>';
            html += '<h3>Articolo Completo AI</h3>';
            html += '<p>Genera in <strong>un solo click</strong>:</p>';
            html += '<ul>';
            html += '<li><span class="dashicons dashicons-yes-alt"></span> Articolo SEO + AEO ottimizzato</li>';
            html += '<li><span class="dashicons dashicons-yes-alt"></span> Immagine AI professionale</li>';
            html += '<li><span class="dashicons dashicons-yes-alt"></span> Meta Title + Description + Keywords</li>';
            html += '<li><span class="dashicons dashicons-yes-alt"></span> Pubblicazione con immagine in evidenza</li>';
            html += '</ul>';
            html += '<p style="font-size:13px;color:#64748b;margin-top:12px;">Disponibile nei piani <strong>Professional</strong>, <strong>Team</strong> e <strong>Pro</strong>.</p>';
            html += '<div style="display:flex;gap:8px;margin-top:16px;">';
            html += '<a href="' + (seoAeoOrchestra.apiUrl || '').replace('/api', '') + '/#pricing" target="_blank" class="button button-primary" style="background:#7c3aed;border-color:#7c3aed;flex:1;text-align:center;">Aggiorna Piano</a>';
            html += '<button class="button" onclick="jQuery(\'.orch-upgrade-overlay\').remove();" style="flex:1;">Chiudi</button>';
            html += '</div></div></div>';
            $('body').append(html);
        },

        init: function() {
            this.creditCosts = seoAeoOrchestra.creditCosts || {};
            this.userCredits = null;
            this.orchestrateCancelled = false;
            this.bindEvents();
            this.loadStats();
            this.initCreditCostLabels();
            this.loadCreditBalance();
            this.checkFirstUse();
            this.loadQuickWin();
        },

        // ── Credit Balance Header Badge ── (3.5.0: surgical update, no full overwrite)
        loadCreditBalance: function() {
            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_get_credits',
                nonce: seoAeoOrchestra.nonce
            }, function(res) {
                var credits = res.credits !== undefined ? res.credits : '?';
                var planCr = (typeof res.plan_credits === 'number') ? res.plan_credits : null;
                var topupCr = (typeof res.topup_credits === 'number') ? res.topup_credits : null;
                SeoAeoOrchestra.userCredits = (typeof credits === 'number') ? credits : null;

                var $badge = $('#seo-aeo-credit-badge');
                // 3.31.7: rimosso inject fallback badge "Crediti: N" floating — pillola persistente di class-credits-bar.php (3.28.1) e' la fonte di verita' UI.
                if (false && !$badge.length) {
                    // legacy fallback disabled
                }

                // Aggiornamento chirurgico — solo la parte numerica + breakdown,
                // senza distruggere la struttura template (.orch4-credit-top, .orch4-credit-bottom)
                var $num = $badge.find('.num');
                if ($num.length) {
                    $num.text(typeof credits === 'number' ? credits.toLocaleString(SeoAeoOrchestra.bcp47()) : credits);
                } else {
                    // Fallback: legacy badge con <strong>
                    $badge.find('strong').first().text(credits);
                }

                // Breakdown plan vs topup (solo se entrambi disponibili)
                var $bottom = $badge.find('.orch4-credit-bottom');
                if ($bottom.length && planCr !== null && topupCr !== null) {
                    var $bd = $bottom.find('.orch4-credit-breakdown');
                    if (!$bd.length) {
                        $bd = $('<span class="orch4-credit-breakdown"></span>').prependTo($bottom);
                    }
                    if (planCr > 0 || topupCr > 0) {
                        $bd.text('Piano: ' + planCr + ' · Top-up: ' + topupCr + ' · ');
                    } else {
                        $bd.text('');
                    }
                }

                // Stato low-credits via classe CSS (governabile dal foglio stile, niente inline color override)
                $badge.toggleClass('seo-aeo-low-credits', typeof credits === 'number' && credits < 20);
            });
        },

        // ── Insufficient Credits Check ──
        checkCredits: function(costKey, callback) {
            var cost = SeoAeoOrchestra.creditCosts[costKey] || 0;
            if (SeoAeoOrchestra.userCredits !== null && cost > 0 && SeoAeoOrchestra.userCredits < cost) {
                SeoAeoOrchestra.showInsufficientCredits(cost, SeoAeoOrchestra.userCredits);
                return false;
            }
            if (callback) callback();
            return true;
        },

        showInsufficientCredits: function(needed, available) {
            var dashUrl = seoAeoOrchestra.dashboardUrl || 'https://aeo-orchestra.com/dashboard';
            var html = '<div class="seo-aeo-insufficient-credits" style="background:#FEF3C7;border:1px solid #F59E0B;border-radius:10px;padding:16px;margin:16px 0;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">';
            html += '<span class="dashicons dashicons-warning" style="color:#D97706;font-size:24px;"></span>';
            html += '<div style="flex:1;"><strong style="color:#92400E;">Crediti insufficienti.</strong><br><span style="color:#78350F;font-size:13px;">Ti servono <strong>' + needed + '</strong> crediti, ne hai <strong>' + available + '</strong>.</span></div>';
            html += '<a href="' + dashUrl + '" target="_blank" class="button button-primary" style="background:#F59E0B;border-color:#D97706;color:#000;font-weight:600;">Ricarica crediti</a>';
            html += '</div>';
            // Remove existing and show new
            $('.seo-aeo-insufficient-credits').remove();
            // Insert before the clicked button's parent card
            var $output = $('.orchestra-output:visible, .orchestra-tool-card:visible').first();
            if ($output.length) {
                $output.before(html);
            } else {
                $('.orchestra-admin').prepend(html);
            }
            $('html, body').animate({scrollTop: $('.seo-aeo-insufficient-credits').offset().top - 50}, 300);
        },

        // ── First Use Onboarding Wizard ──
        checkFirstUse: function() {
            if (seoAeoOrchestra.firstUse !== '1') return;
            // Only show on the main Orchestrator page
            if (window.location.href.indexOf('seo-aeo-orchestra') === -1) return;

            var credits = SeoAeoOrchestra.userCredits || '...';
            var costs = SeoAeoOrchestra.creditCosts;

            var overlayHtml = '<div id="seo-aeo-first-use-overlay" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:100200;display:flex;align-items:center;justify-content:center;">';
            overlayHtml += '<div style="background:#fff;border-radius:16px;max-width:620px;width:94%;box-shadow:0 20px 80px rgba(0,0,0,0.4);overflow:hidden;">';

            // Step 1: Welcome
            overlayHtml += '<div id="fw-step-1" class="fw-step">';
            overlayHtml += '<div style="background:linear-gradient(135deg,#0a0a0a,#0055FF);padding:28px 32px;color:#fff;">';
            overlayHtml += '<h2 style="margin:0 0 8px;font-size:22px;color:#fff;">Benvenuto in AEO Orchestra!</h2>';
            overlayHtml += '<p style="margin:0;opacity:0.9;font-size:14px;">Hai <strong id="fw-credits-num" style="color:#10B981;">' + credits + '</strong> crediti disponibili. Cosa vuoi fare per primo?</p>';
            overlayHtml += '</div>';
            overlayHtml += '<div style="padding:24px 32px;">';
            overlayHtml += '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">';

            // Card 1: Analizza SEO
            overlayHtml += '<div class="fw-card" data-page="seo-aeo-analyze" data-agent="seo" style="border:2px solid #e2e8f0;border-radius:12px;padding:20px 16px;text-align:center;cursor:pointer;transition:all 0.2s;">';
            overlayHtml += '<span class="dashicons dashicons-search" style="font-size:28px;color:#0055FF;display:block;margin-bottom:8px;"></span>';
            overlayHtml += '<strong style="font-size:13px;display:block;margin-bottom:4px;">Analizza SEO</strong>';
            overlayHtml += '<span style="font-size:11px;color:#64748b;">' + (costs.seo_analysis || 5) + ' crediti</span>';
            overlayHtml += '</div>';

            // Card 2: Genera Meta Tags
            overlayHtml += '<div class="fw-card" data-page="seo-aeo-meta" data-agent="meta" style="border:2px solid #e2e8f0;border-radius:12px;padding:20px 16px;text-align:center;cursor:pointer;transition:all 0.2s;">';
            overlayHtml += '<span class="dashicons dashicons-admin-generic" style="font-size:28px;color:#10B981;display:block;margin-bottom:8px;"></span>';
            overlayHtml += '<strong style="font-size:13px;display:block;margin-bottom:4px;">Genera Meta Tags</strong>';
            overlayHtml += '<span style="font-size:11px;color:#64748b;">' + (costs.meta_generation || 2) + ' crediti</span>';
            overlayHtml += '</div>';

            // Card 3: Analisi AEO
            overlayHtml += '<div class="fw-card" data-page="seo-aeo-aeo-analysis" data-agent="aeo" style="border:2px solid #e2e8f0;border-radius:12px;padding:20px 16px;text-align:center;cursor:pointer;transition:all 0.2s;">';
            overlayHtml += '<span class="dashicons dashicons-visibility" style="font-size:28px;color:#8B5CF6;display:block;margin-bottom:8px;"></span>';
            overlayHtml += '<strong style="font-size:13px;display:block;margin-bottom:4px;">Analisi AEO</strong>';
            overlayHtml += '<span style="font-size:11px;color:#64748b;">' + (costs.aeo_analysis || 5) + ' crediti</span>';
            overlayHtml += '</div>';

            overlayHtml += '</div>'; // grid
            overlayHtml += '<div style="margin-top:16px;text-align:center;">';
            overlayHtml += '<button class="button fw-dismiss" style="color:#64748b;">Salta tour &rarr;</button>';
            overlayHtml += '</div>';
            overlayHtml += '</div>'; // padding
            overlayHtml += '</div>'; // step 1

            // Step 2: Agent explanation (populated dynamically)
            overlayHtml += '<div id="fw-step-2" class="fw-step" style="display:none;">';
            overlayHtml += '<div style="padding:32px;" id="fw-step-2-content"></div>';
            overlayHtml += '</div>';

            overlayHtml += '</div></div>'; // card + overlay
            $('body').append(overlayHtml);

            // Update credits once loaded
            if (SeoAeoOrchestra.userCredits === null) {
                setTimeout(function() {
                    if (SeoAeoOrchestra.userCredits !== null) {
                        $('#fw-credits-num').text(SeoAeoOrchestra.userCredits);
                    }
                }, 2000);
            }

            // Card hover
            $(document).on('mouseenter', '.fw-card', function() {
                $(this).css({borderColor: '#0055FF', background: '#f0f9ff'});
            }).on('mouseleave', '.fw-card', function() {
                $(this).css({borderColor: '#e2e8f0', background: '#fff'});
            });

            // Card click → Step 2
            $(document).on('click', '.fw-card', function() {
                var agent = $(this).data('agent');
                var page = $(this).data('page');
                var descriptions = {
                    seo: {title: 'Analisi SEO', icon: 'dashicons-search', color: '#0055FF', desc: 'Analizza qualsiasi pagina del tuo sito e ottieni un punteggio SEO dettagliato con problemi, suggerimenti e keyword mancanti. L\'AI valuta meta tags, struttura heading, velocita e contenuto.', cost: costs.seo_analysis || 5},
                    meta: {title: 'Generatore Meta Tags', icon: 'dashicons-admin-generic', color: '#10B981', desc: 'Genera automaticamente Title, Description e Keywords ottimizzati per ogni pagina del tuo sito. L\'AI analizza il contenuto esistente e crea meta tag che massimizzano il CTR su Google.', cost: costs.meta_generation || 2},
                    aeo: {title: 'Analisi AEO', icon: 'dashicons-visibility', color: '#8B5CF6', desc: 'Answer Engine Optimization: verifica se le tue pagine sono pronte per le AI come ChatGPT e Perplexity. Ricevi un punteggio AEO con suggerimenti per FAQ strutturate e contenuti ottimizzati.', cost: costs.aeo_analysis || 5}
                };
                var d = descriptions[agent];
                var html = '<div style="text-align:center;margin-bottom:20px;">';
                html += '<span class="dashicons ' + d.icon + '" style="font-size:40px;color:' + d.color + ';"></span>';
                html += '<h2 style="margin:12px 0 8px;font-size:20px;">' + d.title + '</h2>';
                html += '<span style="background:#f0f9ff;color:#0055FF;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;">' + d.cost + ' crediti per analisi</span>';
                html += '</div>';
                html += '<p style="color:#475569;font-size:14px;line-height:1.7;text-align:center;margin-bottom:24px;">' + d.desc + '</p>';
                html += '<div style="display:flex;gap:8px;justify-content:center;">';
                html += '<a href="' + window.location.pathname + '?page=' + page + '" class="button button-primary" style="background:' + d.color + ';border-color:' + d.color + ';">Inizia ora</a>';
                html += '<button class="button fw-dismiss">Chiudi</button>';
                html += '</div>';
                $('#fw-step-2-content').html(html);
                $('#fw-step-1').hide();
                $('#fw-step-2').show();
            });

            // Dismiss
            $(document).on('click', '.fw-dismiss', function() {
                $('#seo-aeo-first-use-overlay').fadeOut(200, function() { $(this).remove(); });
                $.post(seoAeoOrchestra.ajaxUrl, {
                    action: 'seo_aeo_orchestra_dismiss_first_use',
                    nonce: seoAeoOrchestra.nonce
                });
            });
        },

        // ── Quick Win Widget ──
        loadQuickWin: function() {
            var $widget = $('#quick-win-widget');
            if (!$widget.length) return;

            // Load SEO analysis history to find lowest score
            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_get_history',
                nonce: seoAeoOrchestra.nonce,
                section: 'seo_analysis'
            }, function(items) {
                if (!items || !items.length) {
                    // No history: suggest first analysis on homepage
                    $widget.show();
                    var costs = SeoAeoOrchestra.creditCosts;
                    $('#qw-description').html('<strong>' + (window.location.hostname || 'La tua homepage') + '</strong> non e ancora stata analizzata. Fai la prima analisi SEO!');
                    $('#qw-score').text('?').css('color', '#6B7280');
                    $('#qw-action-btn').attr('href', window.location.pathname + '?page=seo-aeo-analyze').text('').html('<span class="dashicons dashicons-search" style="font-size:16px;width:16px;height:16px;margin-top:3px;margin-right:4px;"></span> Analizza ora — ' + (costs.seo_analysis || 5) + ' cr');
                    return;
                }

                // Find page with lowest score
                var worstItem = null;
                var worstScore = 101;
                $.each(items, function(i, item) {
                    var data = item.data;
                    if (typeof data === 'string') {
                        try { data = JSON.parse(data); } catch(e) { return; }
                    }
                    var score = data ? (data.score || data.seo_score || 0) : 0;
                    if (typeof score === 'number' && score < worstScore && score >= 0) {
                        worstScore = score;
                        worstItem = item;
                    }
                });

                if (!worstItem || worstScore >= 85) {
                    // All scores are good, no quick win needed
                    return;
                }

                $widget.show();
                var title = worstItem.title || 'Pagina';
                var scoreColor = worstScore < 40 ? '#DC2626' : worstScore < 70 ? '#F59E0B' : '#10B981';
                var label = worstScore < 40 ? SeoAeoOrchestra.t('Critico') : worstScore < 70 ? SeoAeoOrchestra.t('Da migliorare') : SeoAeoOrchestra.t('Buono');

                $('#qw-description').html(SeoAeoOrchestra.t('"{TITLE}" ha il punteggio SEO piu basso del sito. Ottimizzala per un impatto immediato sul posizionamento!').replace('{TITLE}', '<strong>' + title + '</strong>'));
                $('#qw-score').text(worstScore).css('color', scoreColor);
                // 3.31.2: passo URL e keyword come query params così la pagina Analisi SEO si auto-popola
                var qwData = worstItem.data;
                if (typeof qwData === 'string') {
                    try { qwData = JSON.parse(qwData); } catch(e) { qwData = {}; }
                }
                var qwUrl = (qwData && qwData.url) ? qwData.url : (worstItem.url || '');
                var qwKeyword = (qwData && (qwData.main_keyword || qwData.primary_keyword || qwData.keyword)) || '';
                var qwHref = window.location.pathname + '?page=seo-aeo-analyze';
                if (qwUrl) qwHref += '&url=' + encodeURIComponent(qwUrl);
                if (qwKeyword) qwHref += '&keyword=' + encodeURIComponent(qwKeyword);
                $('#qw-action-btn').attr('href', qwHref);
            });

            // Dismiss handler
            $(document).on('click', '#qw-dismiss', function() {
                $widget.slideUp(200);
            });
        },

        // ── Credit cost labels ──
        initCreditCostLabels: function() {
            var costs = this.creditCosts;
            // Update all .credit-cost spans with dynamic values
            $('.credit-cost').each(function() {
                var key = $(this).data('cost-key');
                if (key && costs[key] !== undefined) {
                    $(this).text(costs[key]);
                }
            });
            // Update orchestrator estimate
            this.updateOrchestratorEstimate();
            // Bind SERP preview live editing
            this.initSerpPreviewBindings();
        },

        // ── SERP Preview ──
        showSerpPreview: function(title, description, pageInfo) {
            var $section = $('#serp-preview-section');
            $section.show();
            $('#serp-title-input').val(title);
            $('#serp-desc-input').val(description);
            // Extract domain from page info
            var domain = window.location.hostname || 'example.com';
            $('#serp-domain').text(domain + ' > ' + (pageInfo || '').substring(0, 40));
            $('#serp-favicon').text(domain.charAt(0).toUpperCase());
            SeoAeoOrchestra.updateSerpCharCounts();
        },

        initSerpPreviewBindings: function() {
            $(document).on('input', '#serp-title-input', function() {
                SeoAeoOrchestra.updateSerpCharCounts();
            });
            $(document).on('input', '#serp-desc-input', function() {
                SeoAeoOrchestra.updateSerpCharCounts();
            });
        },

        updateSerpCharCounts: function() {
            var titleLen = ($('#serp-title-input').val() || '').length;
            var descLen = ($('#serp-desc-input').val() || '').length;
            var $tc = $('#serp-title-chars');
            var $dc = $('#serp-desc-chars');
            $tc.text(SeoAeoOrchestra.t('Titolo: {N}/60 caratteri').replace('{N}', titleLen));
            $tc.css('color', titleLen > 60 ? '#EF4444' : titleLen > 50 ? '#F59E0B' : '#10B981');
            $dc.text(SeoAeoOrchestra.t('Descrizione: {N}/160 caratteri').replace('{N}', descLen));
            $dc.css('color', descLen > 160 ? '#EF4444' : descLen > 140 ? '#F59E0B' : '#10B981');
        },

        updateContentCostLabel: function() {
            var length = $('#content-length').val();
            var key = 'content_generation_' + length;
            var cost = SeoAeoOrchestra.creditCosts[key];
            if (cost !== undefined) {
                $('#seo-aeo-generate-content-btn .credit-cost').text(cost);
            }
        },

        updateOrchestratorEstimate: function() {
            var count = $('.orch-page-check:checked').length;
            var costs = this.creditCosts;
            var perPage = (costs.seo_analysis || 2) + (costs.aeo_analysis || 3);
            var total = count * perPage;
            var $est = $('#orch-credits-estimate');
            if (count > 0) {
                var estTpl = SeoAeoOrchestra.t('Stima: ~{TOTAL} crediti ({COUNT} pagine × {PERPAGE} crediti/pag)')
                    .replace('{TOTAL}', '<strong>' + total + '</strong>')
                    .replace('{COUNT}', count)
                    .replace('{PERPAGE}', perPage);
                $est.html('<span class="dashicons dashicons-info" style="font-size:14px;vertical-align:middle;"></span> ' + estTpl);
                $est.show();
            } else {
                $est.hide();
            }
        },

        bindEvents: function() {
            // Meta box generation
            $(document).on('click', '#seo-aeo-generate-meta', this.generateMetaFromBox);

            // SEO Agents
            $(document).on('click', '#seo-aeo-analyze-btn', this.analyzeSEO);
            $(document).on('click', '#seo-aeo-generate-content-btn', this.generateContent);
            $(document).on('click', '#seo-aeo-local-seo-btn', this.localSEO);
            $(document).on('click', '#seo-aeo-meta-generate-btn', this.generateMetaSingle);
            $(document).on('click', '#seo-aeo-bulk-meta-btn', this.generateMetaBulk);

            // AEO Agents
            $(document).on('click', '#seo-aeo-aeo-analyze-btn', this.aeoAnalyze);
            $(document).on('click', '#seo-aeo-aeo-content-btn', this.aeoContent);

            // Orchestratore
            $(document).on('click', '#orch-start-analysis', this.orchestrateStart);
            $(document).on('click', '#orch-restart', function() { location.reload(); });
            $(document).on('click', '#orch-select-all', function() { $('.orch-page-check').prop('checked', true); SeoAeoOrchestra.updateOrchestratorCount(); });
            $(document).on('click', '#orch-select-none', function() { $('.orch-page-check').prop('checked', false); SeoAeoOrchestra.updateOrchestratorCount(); });
            $(document).on('click', '#orch-select-pages', function() { $('.orch-page-check').prop('checked', false).filter('[data-type="page"]').prop('checked', true); SeoAeoOrchestra.updateOrchestratorCount(); });
            $(document).on('click', '#orch-select-posts', function() { $('.orch-page-check').prop('checked', false).filter('[data-type="post"]').prop('checked', true); SeoAeoOrchestra.updateOrchestratorCount(); });
            $(document).on('change', '.orch-page-check', this.updateOrchestratorCount);
            $(document).on('click', '.orch-execute-btn', this.executeAction);
            // 3.39.6 — preview-before-apply
            $(document).on('click', '.orch-preview-btn', SeoAeoOrchestra.previewAction);

            // Cancel orchestrator
            // 3.37.3 Module 12 — actually stop the in-flight AJAX + request refund.
            $(document).on('click', '#orch-cancel-analysis', function() {
                SeoAeoOrchestra.orchestrateCancelled = true;
                $(this).prop('disabled', true).text(SeoAeoOrchestra.t('Annullamento...'));
                // Abort the XHR for the page currently being analyzed (if any).
                var xhr = SeoAeoOrchestra._orchestrateXhr;
                if (xhr && typeof xhr.abort === 'function') {
                    try { xhr.abort(); } catch (e) {}
                }
                SeoAeoOrchestra._orchestrateXhr = null;
                // Request server-side refund for the generation_id of the page
                // that was mid-flight, if we have one. Reason='cancelled' skips
                // the 3/day cap inside the 5-min window.
                var pendingGen = SeoAeoOrchestra._orchestratePendingGenId;
                if (pendingGen) {
                    SeoAeoOrchestra._orchestratePendingGenId = null;
                    $.post(seoAeoOrchestra.ajaxUrl, {
                        action: 'seo_aeo_orchestra_refund_generation',
                        nonce: seoAeoOrchestra.nonce,
                        generation_id: pendingGen,
                        reason: 'cancelled',
                    }).always(function() {
                        SeoAeoOrchestra.showNotice(SeoAeoOrchestra.t('Analisi annullata. Crediti rimborsati.'), 'success');
                    });
                } else {
                    SeoAeoOrchestra.showNotice(SeoAeoOrchestra.t('Analisi annullata.'), 'info');
                }
            });

            // History filter
            $(document).on('click', '.history-filter-btn', function() {
                var filter = $(this).data('filter');
                $(this).siblings().removeClass('active').end().addClass('active');
                var $items = $(this).closest('.orchestra-history').find('.history-item');
                if (filter === 'all') {
                    $items.show().next('.history-detail').hide();
                } else {
                    $items.each(function() {
                        var match = $(this).data('type') === filter;
                        $(this).toggle(match).next('.history-detail').hide();
                    });
                }
            });

            // History restore (3.22.1)
            // 3.26.4: Cancella cronologia di una sezione
            $(document).on('click', '.orch-history-clear-section', function(e) {
                e.stopPropagation();
                var section = $(this).data('section');
                var container = $(this).data('container');
                if (!section) return;
                if (!confirm(SeoAeoOrchestra.t('Cancellare tutta la cronologia di questa sezione? Operazione irreversibile.'))) return;
                $.post(seoAeoOrchestra.ajaxUrl, {
                    action: 'seo_aeo_orchestra_clear_history_section',
                    nonce: seoAeoOrchestra.nonce,
                    section: section
                }, function(resp) {
                    if (resp && resp.success) {
                        SeoAeoOrchestra.loadHistory(section, container);
                        SeoAeoOrchestra.showNotice('✓ Cronologia svuotata', 'success');
                    } else {
                        SeoAeoOrchestra.showNotice('Errore: ' + (resp && resp.error || 'sconosciuto'), 'error');
                    }
                }).fail(function() {
                    SeoAeoOrchestra.showNotice('Errore di rete', 'error');
                });
            });

            // 3.38.8 Task 3 — toggle the affected-pages list under each problem card.
            $(document).on('click', '.orch-problem-toggle-pages', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var targetId = $btn.data('target');
                var $list = $('#' + targetId);
                var nowHidden = !$list.prop('hidden');
                $list.prop('hidden', nowHidden);
                var T = SeoAeoOrchestra.t || function(s){return s;};
                var count = $list.find('li').length;
                $btn.html((nowHidden ? '👁 ' + T('Mostra pagine') : '👁 ' + T('Nascondi pagine')) + ' (' + count + ')');
            });

            // 3.38.8 Task 1 — confirmation modal before restoring cronologia.
            // Prevents the (formerly direct) restore which was invisible to users
            // when the legacy fields/outputs selectors didn't match the current
            // page layout. Also writes ?history_id=<id> for shareable links.
            $(document).on('click', '.history-restore-btn', function(e) {
                e.stopPropagation();
                e.preventDefault();
                var $btn = $(this);
                var itemId = $btn.data('item-id');
                if (!itemId) return;
                var itemDate = $btn.data('item-date') || '';
                var itemTitle = $btn.data('item-title') || '';
                if (SeoAeoOrchestra.showHistoryRestoreConfirm) {
                    SeoAeoOrchestra.showHistoryRestoreConfirm(itemId, itemDate, itemTitle);
                } else {
                    SeoAeoOrchestra.restoreFromHistory(itemId);
                }
            });

            // History re-run (3.31.2: forward params da backend per auto-fill)
            $(document).on('click', '.history-rerun-btn', function(e) {
                e.stopPropagation();
                e.preventDefault();
                var type = $(this).data('type');
                var rerunData = $(this).data('rerun');
                console.log('[orch] history rerun clicked', type, rerunData);
                $.post(seoAeoOrchestra.ajaxUrl, {
                    action: 'seo_aeo_orchestra_rerun_history',
                    nonce: seoAeoOrchestra.nonce,
                    rerun_type: type,
                    rerun_data: typeof rerunData === 'string' ? rerunData : JSON.stringify(rerunData)
                }, function(res) {
                    console.log('[orch] history rerun response', res);
                    if (res && res.error) {
                        SeoAeoOrchestra.showNotice('Errore: ' + res.error, 'error');
                        return;
                    }
                    if (res && res.redirect) {
                        var href = window.location.pathname + '?page=' + res.redirect;
                        if (res.params && typeof res.params === 'object') {
                            for (var k in res.params) {
                                if (res.params.hasOwnProperty(k) && res.params[k]) {
                                    href += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(res.params[k]);
                                }
                            }
                        }
                        window.location.href = href;
                    } else {
                        SeoAeoOrchestra.showNotice('Riesecuzione non disponibile per questo tipo.', 'info');
                    }
                }).fail(function(xhr) {
                    console.error('[orch] history rerun failed', xhr.status, xhr.responseText);
                    SeoAeoOrchestra.showNotice('Errore di rete (' + xhr.status + ')', 'error');
                });
            });

            // Bulk select
            $(document).on('click', '#select-all-bulk', function() {
                $('input[name="bulk_posts[]"]').prop('checked', true);
            });

            // Character counters
            $(document).on('input', '#seo_aeo_meta_title', function() {
                $('#title-count').text($(this).val().length);
                var len = $(this).val().length;
                $(this).css('border-color', len > 60 ? '#dc3545' : len > 50 ? '#ffc107' : '#28a745');
            });
            $(document).on('input', '#seo_aeo_meta_description', function() {
                $('#desc-count').text($(this).val().length);
                var len = $(this).val().length;
                $(this).css('border-color', len > 160 ? '#dc3545' : len > 140 ? '#ffc107' : '#28a745');
            });
        },

        loadStats: function() {
            if ($('#total-analyses').length) {
                $('#total-analyses').text('-');
                $('#meta-generated').text('-');
                $('#content-created').text('-');
                $('#aeo-analyses').text('-');
                $('#api-remaining').text('-');
            }
            // Load history into dedicated containers
            if ($('#history-seo-analysis').length) SeoAeoOrchestra.loadHistory('seo_analysis', 'history-seo-analysis');
            if ($('#history-seo-content').length) SeoAeoOrchestra.loadHistory('seo_content', 'history-seo-content');
            if ($('#history-local-seo').length) SeoAeoOrchestra.loadHistory('local_seo', 'history-local-seo');
            if ($('#history-aeo-analysis').length) SeoAeoOrchestra.loadHistory('aeo_analysis', 'history-aeo-analysis');
            if ($('#history-aeo-content').length) SeoAeoOrchestra.loadHistory('aeo_content', 'history-aeo-content');
            if ($('#history-orchestrator').length) SeoAeoOrchestra.loadHistory('orchestrator', 'history-orchestrator');
        },

        // ── SEO: Analyze ──
        analyzeSEO: function(e) {
            e.preventDefault();
            var $button = $(this);
            var selectVal = $('#analyze-page-select').val();
            var url = (selectVal && selectVal !== '__custom') ? selectVal : $('#analyze-url').val();
            var keyword = $('#analyze-keyword').val();

            if (!url || !keyword) {
                SeoAeoOrchestra.showNotice('Seleziona una pagina e inserisci la keyword', 'error');
                return;
            }

            // Credit check
            if (!SeoAeoOrchestra.checkCredits('seo_analysis')) return;

            $button.prop('disabled', true).html('<span class="orchestra-spinner"></span> Analisi in corso...');
            $('.seo-aeo-insufficient-credits').remove();

            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_analyze',
                nonce: seoAeoOrchestra.nonce,
                url: url,
                keyword: keyword
            }, function(response) {
                if (response && !response.error) {
                    SeoAeoOrchestra.displayAnalysisResults(response);
                } else {
                    SeoAeoOrchestra.showNotice('Errore: ' + (response.error || 'Analisi fallita'), 'error');
                }
            }).fail(function() {
                SeoAeoOrchestra.showNotice('Errore di connessione', 'error');
            }).always(function() {
                $button.prop('disabled', false).html('<span class="dashicons dashicons-search"></span> Analizza SEO — <span class="credit-cost" data-cost-key="seo_analysis">' + (SeoAeoOrchestra.creditCosts.seo_analysis || 5) + '</span> crediti');
            });
        },

        displayAnalysisResults: function(data) {
            var html = '<div class="analysis-results">';
            var scoreClass = data.score >= 80 ? 'good' : data.score >= 60 ? 'medium' : 'bad';
            html += '<div class="score-display ' + scoreClass + '">';
            html += '<span class="score-number">' + (data.score || 0) + '</span>';
            html += '<span class="score-label">SEO Score /100</span></div>';
            html += '<div style="margin:10px 0;"><span class="token-badge">' + (SeoAeoOrchestra.creditCosts.seo_analysis || 2) + ' crediti utilizzati</span></div>';

            if (data.issues && data.issues.length) {
                html += '<div class="result-section"><h4><span class="dashicons dashicons-warning"></span> Problemi trovati:</h4><ul>';
                data.issues.forEach(function(issue) {
                    var text = typeof issue === 'string' ? issue : (issue.description || issue.message || JSON.stringify(issue));
                    html += '<li>' + text + '</li>';
                });
                html += '</ul></div>';
            }

            if (data.main_keyword || data.primary_keyword) {
                html += '<div class="result-section"><h4><span class="dashicons dashicons-tag"></span> Keyword Principale:</h4>';
                html += '<span class="keyword-tag" style="font-size:14px;font-weight:bold;">' + (data.main_keyword || data.primary_keyword) + '</span></div>';
            }

            if (data.secondary_keywords && data.secondary_keywords.length) {
                html += '<div class="result-section"><h4><span class="dashicons dashicons-tag"></span> Keyword Secondarie:</h4><div class="keyword-tags">';
                data.secondary_keywords.forEach(function(kw) {
                    var kwText = typeof kw === 'string' ? kw : (kw.keyword || kw.text || JSON.stringify(kw));
                    html += '<span class="keyword-tag">' + kwText + '</span>';
                });
                html += '</div></div>';
            }

            if (data.missing_keywords && data.missing_keywords.length) {
                html += '<div class="result-section"><h4><span class="dashicons dashicons-plus-alt"></span> Keywords Mancanti:</h4><div class="keyword-tags">';
                data.missing_keywords.forEach(function(kw) { html += '<span class="keyword-tag" style="background:#FEF3C7;color:#92400E;">' + kw + '</span>'; });
                html += '</div></div>';
            }

            if (data.suggestions && data.suggestions.length) {
                html += '<div class="result-section"><h4><span class="dashicons dashicons-lightbulb"></span> Suggerimenti:</h4><ul>';
                data.suggestions.forEach(function(s) {
                    var text = typeof s === 'string' ? s : (s.description || s.message || JSON.stringify(s));
                    html += '<li>' + text + '</li>';
                });
                html += '</ul></div>';
            }

            html += '</div>';
            $('#analysis-output').html(html);
            /* Save to history */
            var urlVal = $('#analyze-page-select').val() || $('#analyze-url').val() || '';
            var seoAnalysisRestore = {
                fields: {
                    '#analyze-page-select': $('#analyze-page-select').val() || '',
                    '#analyze-url':         $('#analyze-url').val() || '',
                    '#analyze-keyword':     $('#analyze-keyword').val() || ''
                },
                outputs: { '#analysis-output': $('#analysis-output').html() }
            };
            SeoAeoOrchestra.saveHistory('seo_analysis', 'analysis', urlVal, data, 2, seoAnalysisRestore);
        },

        // ── SEO: Meta Tags from box ──
        generateMetaFromBox: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $spinner = $button.siblings('.spinner');
            var postId = $button.data('post-id');
            var keyword = $('#seo_aeo_focus_keyword').val();
            if (!keyword) {
                keyword = prompt('Inserisci la keyword principale:');
                if (!keyword) return;
            }
            $button.prop('disabled', true);
            $spinner.addClass('is-active');

            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_generate_meta',
                nonce: seoAeoOrchestra.nonce,
                post_id: postId, keyword: keyword, title: '', content: ''
            }, function(response) {
                if (response && !response.error) {
                    if (response.title) $('#seo_aeo_meta_title').val(response.title).trigger('input');
                    if (response.description) $('#seo_aeo_meta_description').val(response.description).trigger('input');
                    if (response.keywords && Array.isArray(response.keywords)) $('#seo_aeo_meta_keywords').val(response.keywords.join(', '));
                    SeoAeoOrchestra.showNotice('Meta tags generati con successo!', 'success');
                } else {
                    SeoAeoOrchestra.showNotice('Errore: ' + (response.error || 'Generazione fallita'), 'error');
                }
            }).always(function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            });
        },

        // ── SEO: Meta Single ──
        generateMetaSingle: function(e) {
            e.preventDefault();
            var $button = $(this);
            var postId = $('#meta-post-select').val();
            var keyword = $('#meta-keyword').val();
            if (!postId || !keyword) {
                SeoAeoOrchestra.showNotice('Seleziona un post e inserisci una keyword', 'error');
                return;
            }
            if (!SeoAeoOrchestra.checkCredits('meta_generation')) return;
            $('.seo-aeo-insufficient-credits').remove();
            $button.prop('disabled', true).html('<span class="orchestra-spinner"></span> Generazione...');

            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_generate_meta',
                nonce: seoAeoOrchestra.nonce,
                post_id: postId, keyword: keyword, title: '', content: ''
            }, function(response) {
                if (response && !response.error) {
                    var html = '<div class="meta-result">';
                    html += '<p><strong>Title:</strong> ' + (response.title || '') + '</p>';
                    html += '<p><strong>Description:</strong> ' + (response.description || '') + '</p>';
                    html += '<p><strong>Keywords:</strong> ' + (response.keywords ? response.keywords.join(', ') : '') + '</p>';
                    html += '<p class="success-message"><span class="dashicons dashicons-yes"></span> Meta tags salvati!</p></div>';
                    $('#meta-output').html(html);

                    // Populate and show SERP preview
                    var pageUrl = $('#meta-post-select option:selected').text() || 'la tua pagina';
                    SeoAeoOrchestra.showSerpPreview(response.title || '', response.description || '', pageUrl);

                    SeoAeoOrchestra.showNotice('Meta tags generati e salvati!', 'success');
                } else {
                    SeoAeoOrchestra.showNotice('Errore: ' + (response.error || 'Generazione fallita'), 'error');
                }
            }).always(function() {
                $button.prop('disabled', false).html('<span class="dashicons dashicons-admin-generic"></span> Genera Meta Tags — <span class="credit-cost" data-cost-key="meta_generation">' + (SeoAeoOrchestra.creditCosts.meta_generation || 2) + '</span> crediti');
            });
        },

        // ── SEO: Meta Tags Bulk (3.26.1: review-then-apply flow) ──
        generateMetaBulk: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $checked = $('input[name="bulk_posts[]"]:checked');
            if ($checked.length === 0) {
                SeoAeoOrchestra.showNotice('Seleziona almeno una pagina nella lista bulk.', 'error');
                return;
            }
            var sharedKeyword = ($('#meta-keyword').val() || '').trim();
            var costPerItem = (SeoAeoOrchestra.creditCosts && SeoAeoOrchestra.creditCosts.meta_generation) || 2;
            var totalCost = costPerItem * $checked.length;
            if (!confirm(SeoAeoOrchestra.t('Generare meta tags per') + ' ' + $checked.length + ' ' + SeoAeoOrchestra.t('pagine') + '?\n' + SeoAeoOrchestra.t('Costo stimato:') + ' ' + totalCost + ' ' + SeoAeoOrchestra.t('crediti') + ' (' + costPerItem + ' ' + SeoAeoOrchestra.t('per pagina') + ').' + (sharedKeyword ? '\n' + SeoAeoOrchestra.t('Keyword condivisa:') + ' "' + sharedKeyword + '"' : '\n' + SeoAeoOrchestra.t('Keyword: titolo della pagina come fallback.')) + '\n\n' + SeoAeoOrchestra.t('Le proposte verranno mostrate per la revisione PRIMA di essere salvate.'))) {
                return;
            }
            $('.seo-aeo-insufficient-credits').remove();

            var items = $checked.map(function() {
                var $cb = $(this);
                var labelText = $cb.parent().text().trim();
                return { id: parseInt($cb.val(), 10), title: labelText };
            }).get();

            var $output = $('#meta-output');
            $output.html(
                '<div class="meta-bulk-review">' +
                  '<h3 style="margin:10px 0 6px;">' + SeoAeoOrchestra.t('Generazione bulk in corso...') + '</h3>' +
                  '<p class="description">' + SeoAeoOrchestra.t('Genero le proposte per ogni pagina. Le potrai modificare PRIMA di salvarle.') + '</p>' +
                  '<table class="widefat striped" id="orch-bulk-meta-table" style="margin-top:14px;">' +
                    '<thead><tr>' +
                      '<th style="width:34px;"><input type="checkbox" id="orch-bulk-meta-all" checked title="' + SeoAeoOrchestra.t('Seleziona/Deseleziona tutti') + '"></th>' +
                      '<th style="width:200px;">' + SeoAeoOrchestra.t('Pagina') + '</th>' +
                      '<th>' + SeoAeoOrchestra.t('Meta Title proposto') + '</th>' +
                      '<th>' + SeoAeoOrchestra.t('Meta Description proposta') + '</th>' +
                      '<th style="width:80px;">' + SeoAeoOrchestra.t('Stato') + '</th>' +
                    '</tr></thead><tbody id="orch-bulk-meta-body"></tbody>' +
                  '</table>' +
                  '<div style="margin-top:16px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">' +
                    '<button type="button" class="button button-primary button-hero" id="orch-bulk-meta-apply" disabled>' +
                      '<span class="dashicons dashicons-yes" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Salva le selezionate') +
                    '</button>' +
                    '<button type="button" class="button" id="orch-bulk-meta-cancel">' + SeoAeoOrchestra.t('Annulla / Scarta proposte') + '</button>' +
                    '<span id="orch-bulk-meta-status" class="orch3-muted" style="font-size:12px;"></span>' +
                  '</div>' +
                '</div>'
            );
            var $tbody = $('#orch-bulk-meta-body');
            var $applyBtn = $('#orch-bulk-meta-apply');
            var $statusLine = $('#orch-bulk-meta-status');
            $button.prop('disabled', true).html('<span class="orchestra-spinner"></span> ' + SeoAeoOrchestra.t('Generazione in corso...'));

            var done = 0, ok = 0, fail = 0;
            var proposals = {};  // { post_id: { title, description, keywords } }

            function escapeHtml(s) {
                return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
            }

            function renderRow(item, status, proposal, errMsg) {
                var titleVal = proposal && proposal.title ? proposal.title : '';
                var descVal = proposal && proposal.description ? proposal.description : '';
                var statusBadge = '';
                if (status === 'pending') statusBadge = '<span style="color:#94a3b8;">⏳ ' + SeoAeoOrchestra.t('Generazione…') + '</span>';
                else if (status === 'ok') statusBadge = '<span style="color:#059669;">✓ ' + SeoAeoOrchestra.t('Pronto') + '</span>';
                else statusBadge = '<span style="color:#b91c1c;" title="' + escapeHtml(errMsg || '') + '">✗ ' + SeoAeoOrchestra.t('Errore') + '</span>';
                var disabled = status !== 'ok' ? 'disabled' : '';
                return '<tr data-post-id="' + item.id + '" data-status="' + status + '">' +
                    '<td><input type="checkbox" class="orch-bulk-meta-cb" ' + (status === 'ok' ? 'checked' : '') + ' ' + disabled + '></td>' +
                    '<td><strong>' + escapeHtml(item.title) + '</strong></td>' +
                    '<td><textarea class="orch-bulk-meta-title-edit" rows="2" style="width:100%;font-size:12px;padding:5px;border-radius:5px;border:1px solid #e5e7eb;" maxlength="80" ' + disabled + '>' + escapeHtml(titleVal) + '</textarea><div style="font-size:10px;color:#94a3b8;text-align:right;margin-top:2px;">' + (titleVal.length || 0) + '/80</div></td>' +
                    '<td><textarea class="orch-bulk-meta-desc-edit" rows="3" style="width:100%;font-size:12px;padding:5px;border-radius:5px;border:1px solid #e5e7eb;" maxlength="200" ' + disabled + '>' + escapeHtml(descVal) + '</textarea><div style="font-size:10px;color:#94a3b8;text-align:right;margin-top:2px;">' + (descVal.length || 0) + '/200</div></td>' +
                    '<td>' + statusBadge + '</td>' +
                '</tr>';
            }

            // Render rows pendenti subito per dare feedback visuale
            items.forEach(function(item) {
                $tbody.append(renderRow(item, 'pending'));
            });

            function updateApplyState() {
                var selected = $tbody.find('.orch-bulk-meta-cb:checked').length;
                $applyBtn.prop('disabled', selected === 0).html('<span class="dashicons dashicons-yes" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Salva le') + ' ' + selected + ' ' + SeoAeoOrchestra.t('selezionate'));
            }

            function processNext(i) {
                if (i >= items.length) {
                    $button.prop('disabled', false).html(SeoAeoOrchestra.t('Genera per Selezionati'));
                    $statusLine.text(SeoAeoOrchestra.t('Generazione completata:') + ' ' + ok + ' OK, ' + fail + ' ' + SeoAeoOrchestra.t('errori') + '. ' + SeoAeoOrchestra.t('Rivedi e modifica le proposte, poi clicca Salva.'));
                    updateApplyState();
                    if (typeof SeoAeoOrchestra.refreshCredits === 'function') SeoAeoOrchestra.refreshCredits();
                    return;
                }
                var item = items[i];
                var keyword = sharedKeyword || (item.title.split('(')[0] || item.title).trim();

                $.post(seoAeoOrchestra.ajaxUrl, {
                    action: 'seo_aeo_orchestra_generate_meta',
                    nonce: seoAeoOrchestra.nonce,
                    post_id: item.id,
                    keyword: keyword,
                    title: '',
                    content: '',
                    dry_run: 1  // 3.26.1: solo proposta, no save
                }).done(function(response) {
                    var $row = $tbody.find('tr[data-post-id="' + item.id + '"]');
                    if (response && !response.error) {
                        ok++;
                        proposals[item.id] = {
                            title: response.title || '',
                            description: response.description || '',
                            keywords: response.keywords || []
                        };
                        $row.replaceWith(renderRow(item, 'ok', proposals[item.id]));
                    } else {
                        fail++;
                        $row.replaceWith(renderRow(item, 'error', null, response && response.error || 'sconosciuto'));
                    }
                }).fail(function(xhr) {
                    fail++;
                    var $row = $tbody.find('tr[data-post-id="' + item.id + '"]');
                    $row.replaceWith(renderRow(item, 'error', null, 'rete ' + xhr.status));
                }).always(function() {
                    done++;
                    setTimeout(function() { processNext(i + 1); }, 400);
                });
            }
            processNext(0);

            // Master checkbox toggle
            $output.off('change.bulkmeta').on('change.bulkmeta', '#orch-bulk-meta-all', function() {
                $tbody.find('.orch-bulk-meta-cb:not(:disabled)').prop('checked', $(this).is(':checked'));
                updateApplyState();
            });
            $output.on('change.bulkmeta', '.orch-bulk-meta-cb', updateApplyState);

            // Cancel: discard all proposals
            $output.on('click.bulkmeta', '#orch-bulk-meta-cancel', function() {
                if (!confirm(SeoAeoOrchestra.t('Scartare tutte le proposte? Le modifiche non saranno salvate.'))) return;
                $output.empty();
            });

            // Apply: save selected
            $output.on('click.bulkmeta', '#orch-bulk-meta-apply', function() {
                var $rows = $tbody.find('tr').filter(function() {
                    return $(this).find('.orch-bulk-meta-cb').is(':checked');
                });
                if (!$rows.length) return;
                var $apply = $(this);
                $apply.prop('disabled', true).html('<span class="orchestra-spinner"></span> ' + SeoAeoOrchestra.t('Salvataggio...'));
                var savedOk = 0, savedFail = 0;
                var queue = $rows.toArray();
                function saveNext() {
                    if (!queue.length) {
                        $apply.html('<span class="dashicons dashicons-yes" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Salvati') + ' ' + savedOk + (savedFail ? ' (' + savedFail + ' ' + SeoAeoOrchestra.t('falliti') + ')' : ''));
                        SeoAeoOrchestra.showNotice(SeoAeoOrchestra.t('Bulk salvato:') + ' ' + savedOk + ' OK' + (savedFail ? ', ' + savedFail + ' ' + SeoAeoOrchestra.t('falliti') : ''), savedFail === 0 ? 'success' : 'error');
                        return;
                    }
                    var $row = $(queue.shift());
                    var pid = parseInt($row.attr('data-post-id'), 10);
                    var titleVal = $row.find('.orch-bulk-meta-title-edit').val() || '';
                    var descVal = $row.find('.orch-bulk-meta-desc-edit').val() || '';
                    var kwsArr = (proposals[pid] && proposals[pid].keywords) || [];
                    var kwsStr = Array.isArray(kwsArr) ? kwsArr.join(', ') : kwsArr;
                    $.post(seoAeoOrchestra.ajaxUrl, {
                        action: 'seo_aeo_orchestra_save_meta_manual',
                        nonce: seoAeoOrchestra.nonce,
                        post_id: pid,
                        meta_title: titleVal,
                        meta_description: descVal,
                        meta_keywords: kwsStr
                    }).done(function(r) {
                        if (r && r.success) { savedOk++; $row.find('td:last').html('<span style="color:#059669;">✓ ' + SeoAeoOrchestra.t('Salvato') + '</span>'); }
                        else { savedFail++; $row.find('td:last').html('<span style="color:#b91c1c;">✗ ' + SeoAeoOrchestra.t('Errore') + '</span>'); }
                    }).fail(function() {
                        savedFail++;
                        $row.find('td:last').html('<span style="color:#b91c1c;">✗ ' + SeoAeoOrchestra.t('Errore rete') + '</span>');
                    }).always(function() {
                        setTimeout(saveNext, 200);
                    });
                }
                saveNext();
            });
        },

        // ── SEO: Content ──
        generateContent: function(e) {
            e.preventDefault();
            var $button = $(this);
            var topic = $('#content-topic').val();
            var keywords = $('#content-keywords').val();
            var length = $('#content-length').val();
            var includeFaq = $('#include-faq').is(':checked');
            if (!topic) {
                SeoAeoOrchestra.showNotice('Inserisci un argomento', 'error');
                return;
            }
            var costKey = 'content_generation_' + length;
            if (!SeoAeoOrchestra.checkCredits(costKey)) return;
            $('.seo-aeo-insufficient-credits').remove();
            $button.prop('disabled', true).html('<span class="orchestra-spinner"></span> ' + SeoAeoOrchestra.t('Generazione in corso (30-60 sec)...'));

            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_generate_content',
                nonce: seoAeoOrchestra.nonce,
                topic: topic, keywords: keywords, length: length, include_faq: includeFaq ? 'true' : 'false'
            }, function(response) {
                if (response && response.content) {
                    var wc = response.word_count || (response.content.split(/\s+/).length);
                    var contentCost = SeoAeoOrchestra.creditCosts['content_generation_' + length] || 8;
                    var ciKeyword = (keywords ? keywords.split(',')[0] : topic).replace(/'/g, "\\'");
                    var completeBtn = SeoAeoOrchestra.isPremiumTier()
                        ? '<button type="button" class="button" style="background:#7c3aed;border-color:#7c3aed;color:#fff;" title="' + SeoAeoOrchestra.t('Rigenera questa pagina come Articolo Completo: testo + immagine AI + meta tags. ~25 crediti.') + '" onclick="SeoAeoOrchestra.generateCompleteArticle(\'' + topic.replace(/'/g, "\\'") + '\',\'' + ciKeyword + '\',\'seo_content\',jQuery(\'#content-output\'));"><span class="dashicons dashicons-star-filled" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Articolo Completo AI') + '</button>'
                        : '';
                    $('#content-output').html(
                        '<div style="margin-bottom:10px;"><span class="token-badge">~' + wc + ' parole</span> <span class="token-badge">' + contentCost + ' crediti utilizzati</span></div>' +
                        '<div class="generated-content" style="max-height:500px;overflow:auto;border:1px solid #eee;padding:15px;border-radius:8px;">' + response.content + '</div>' +
                        '<div class="content-actions" style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">' +
                        '<button type="button" class="button button-primary" onclick="SeoAeoOrchestra.publishContent(\'' + topic.replace(/'/g, "\\'") + '\', jQuery(\'.generated-content\').first().html(), \'draft\')"><span class="dashicons dashicons-upload" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Pubblica Bozza') + '</button>' +
                        '<button type="button" class="button" onclick="SeoAeoOrchestra.publishContent(\'' + topic.replace(/'/g, "\\'") + '\', jQuery(\'.generated-content\').first().html(), \'publish\')"><span class="dashicons dashicons-yes" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Pubblica Subito') + '</button>' +
                        '<button type="button" class="button" onclick="SeoAeoOrchestra.previewArticle(\'' + topic.replace(/'/g, "\\'") + '\', jQuery(\'.generated-content\').first().html())"><span class="dashicons dashicons-visibility" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Anteprima') + '</button>' +
                        '<button type="button" class="button" onclick="SeoAeoOrchestra.copyContent()"><span class="dashicons dashicons-admin-page" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Copia') + '</button>' +
                        '<button type="button" class="button" onclick="SeoAeoOrchestra.openMediaUploader(jQuery(\'.generated-content\').first().parent());"><span class="dashicons dashicons-admin-media" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Foto da Media') + '</button>' +
                        '<button type="button" class="button" onclick="SeoAeoOrchestra.openAiImageGenerator(jQuery(\'.generated-content\').first().parent());"><span class="dashicons dashicons-format-image" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Genera Foto AI') + '</button>' +
                        completeBtn +
                        '</div>');
                    // Wrap per generateCompleteArticle
                    var $coOut = $('#content-output');
                    if (!$coOut.find('.expand-content-actions').length) {
                        $coOut.addClass('expand-content-card');
                        $coOut.append('<div class="expand-content-actions"></div>');
                    }
                    SeoAeoOrchestra.showNotice('Contenuto generato! ' + wc + ' parole', 'success');
                    var seoContentRestore = {
                        fields: {
                            '#content-topic':    topic,
                            '#content-keywords': $('#content-keywords').val(),
                            '#content-length':   $('#content-length').val(),
                            '#include-faq':      $('#include-faq').is(':checked'),
                            '#include-cta':      $('#include-cta').is(':checked'),
                            '#include-summary':  $('#include-summary').is(':checked')
                        },
                        outputs: { '#content-output': $('#content-output').html() }
                    };
                    SeoAeoOrchestra.saveHistory('seo_content', 'content', topic, response.content, 5, seoContentRestore);
                } else {
                    SeoAeoOrchestra.showNotice('Errore: ' + (response.error || 'Generazione fallita'), 'error');
                }
            }).always(function() {
                $button.prop('disabled', false).html('<span class="dashicons dashicons-edit-large"></span> Genera Contenuto — <span class="credit-cost" data-cost-key="content_generation_medium">' + (SeoAeoOrchestra.creditCosts['content_generation_' + ($('#content-length').val() || 'medium')] || 10) + '</span> crediti');
            });
        },

        // ── SEO: Local ──
        localSEO: function(e) {
            e.preventDefault();
            var $button = $(this);
            var service = $('#local-service').val();
            var city = $('#local-city').val();
            var template = $('#local-template').val();
            if (!service || !city) {
                SeoAeoOrchestra.showNotice('Inserisci servizio e citta', 'error');
                return;
            }
            if (!SeoAeoOrchestra.checkCredits('local_seo')) return;
            $('.seo-aeo-insufficient-credits').remove();
            $button.prop('disabled', true).html('<span class="orchestra-spinner"></span> ' + SeoAeoOrchestra.t('Generazione in corso...'));

            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_local_seo',
                nonce: seoAeoOrchestra.nonce,
                service: service, city: city, template: template
            }, function(response) {
                if (response && response.content) {
                    var html = '<div class="local-result">';
                    html += '<div style="margin-bottom:10px;"><span class="token-badge">' + (SeoAeoOrchestra.creditCosts.local_seo || 4) + ' crediti utilizzati</span></div>';
                    html += '<div class="suggested-meta"><p><strong>Title:</strong> ' + (response.suggested_title || '') + '</p>';
                    html += '<p><strong>Description:</strong> ' + (response.suggested_description || '') + '</p></div>';
                    html += '<div class="generated-content" style="max-height:500px;overflow:auto;border:1px solid #eee;padding:15px;border-radius:8px;">' + response.content + '</div>';
                    html += '<div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">';
                    html += '<button type="button" class="button button-primary" onclick="SeoAeoOrchestra.publishContent(\'' + (service + ' ' + city).replace(/'/g, "\\'") + '\', jQuery(\'.generated-content\').last().html(), \'draft\')"><span class="dashicons dashicons-upload" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Pubblica Bozza') + '</button>';
                    html += '<button type="button" class="button" onclick="SeoAeoOrchestra.publishContent(\'' + (service + ' ' + city).replace(/'/g, "\\'") + '\', jQuery(\'.generated-content\').last().html(), \'publish\')"><span class="dashicons dashicons-yes" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Pubblica Subito') + '</button>';
                    html += '<button type="button" class="button" onclick="SeoAeoOrchestra.previewArticle(\'' + (service + ' ' + city).replace(/'/g, "\\'") + '\', jQuery(\'.generated-content\').last().html())"><span class="dashicons dashicons-visibility" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Anteprima') + '</button>';
                    html += '<button type="button" class="button" onclick="SeoAeoOrchestra.copyContent()"><span class="dashicons dashicons-admin-page" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Copia') + '</button>';
                    html += '<button type="button" class="button" onclick="SeoAeoOrchestra.openMediaUploader(jQuery(\'.generated-content\').last().parent());"><span class="dashicons dashicons-admin-media" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Foto da Media') + '</button>';
                    if (SeoAeoOrchestra.isPremiumTier()) {
                        html += '<button type="button" class="button" onclick="SeoAeoOrchestra.openAiImageGenerator(jQuery(\'.generated-content\').last().parent());"><span class="dashicons dashicons-format-image" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Genera Foto AI') + '</button>';
                        // 3.26.2: bottone "Articolo Completo" anche su Local SEO (rigenera la pagina con immagine + meta)
                        html += '<button type="button" class="button" style="background:#7c3aed;border-color:#7c3aed;color:#fff;" title="' + SeoAeoOrchestra.t('Rigenera questa pagina come Articolo Completo: testo + immagine AI + meta tags. ~25 crediti.') + '" onclick="SeoAeoOrchestra.generateCompleteArticle(\'' + (service + ' ' + city).replace(/'/g, "\\'") + '\',\'' + service.replace(/'/g, "\\'") + '\',\'local_seo\',jQuery(\'#local-output\').find(\'.local-result\'));"><span class="dashicons dashicons-star-filled" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Articolo Completo AI') + '</button>';
                    }
                    html += '</div></div>';
                    $('#local-output').html(html);
                    // Wrap container per generateCompleteArticle che si aspetta una struttura .expand-content-card
                    $('#local-output .local-result').addClass('expand-content-card');
                    $('#local-output .local-result').append('<div class="expand-content-actions"></div>');
                    SeoAeoOrchestra.showNotice('Pagina Local SEO generata!', 'success');
                    var localRestore = {
                        fields: {
                            '#local-service':  service,
                            '#local-city':     city,
                            '#local-template': template
                        },
                        outputs: { '#local-output': $('#local-output').html() }
                    };
                    SeoAeoOrchestra.saveHistory('local_seo', 'content', service + ' ' + city, response.content, 5, localRestore);
                } else {
                    SeoAeoOrchestra.showNotice('Errore: ' + (response.error || 'Generazione fallita'), 'error');
                }
            }).always(function() {
                $button.prop('disabled', false).html('<span class="dashicons dashicons-location"></span> Genera Pagina Local — <span class="credit-cost" data-cost-key="local_seo">' + (SeoAeoOrchestra.creditCosts.local_seo || 4) + '</span> crediti');
            });
        },

        // ── AEO: Analysis ──
        aeoAnalyze: function(e) {
            e.preventDefault();
            var $button = $(this);
            var selectVal = $('#aeo-page-select').val();
            var url = (selectVal && selectVal !== '__custom') ? selectVal : $('#aeo-url').val();
            var keyword = $('#aeo-keyword').val();
            if (!url || !keyword) {
                SeoAeoOrchestra.showNotice('Seleziona una pagina e inserisci la keyword', 'error');
                return;
            }
            if (!SeoAeoOrchestra.checkCredits('aeo_analysis')) return;
            $('.seo-aeo-insufficient-credits').remove();
            $button.prop('disabled', true).html('<span class="orchestra-spinner"></span> Analisi AEO in corso...');

            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_aeo_analyze',
                nonce: seoAeoOrchestra.nonce,
                url: url, keyword: keyword
            }, function(response) {
                if (response && !response.error) {
                    SeoAeoOrchestra.displayAEOResults(response);
                } else {
                    SeoAeoOrchestra.showNotice('Errore: ' + (response.error || response.detail || 'Analisi AEO fallita'), 'error');
                }
            }).fail(function() {
                SeoAeoOrchestra.showNotice('Errore di connessione', 'error');
            }).always(function() {
                $button.prop('disabled', false).html('<span class="dashicons dashicons-visibility"></span> Analizza AEO — <span class="credit-cost" data-cost-key="aeo_analysis">' + (SeoAeoOrchestra.creditCosts.aeo_analysis || 5) + '</span> crediti');
            });
        },

        displayAEOResults: function(data) {
            var html = '<div class="analysis-results">';

            // AEO Scores
            html += '<div class="aeo-scores-grid">';
            html += '<div class="aeo-score-box"><span class="score-value">' + (data.aeo_score || 0) + '</span><span class="score-label">AEO Score</span></div>';
            html += '<div class="aeo-score-box"><span class="score-value">' + (data.seo_score || 0) + '</span><span class="score-label">SEO Score</span></div>';
            html += '<div class="aeo-score-box"><span class="score-value">' + (data.citability_score || 0) + '</span><span class="score-label">Citabilita</span></div>';
            html += '<div class="aeo-score-box"><span class="score-value">' + (data.direct_answer_quality || 0) + '</span><span class="score-label">Risposte dirette</span></div>';
            html += '<div class="aeo-score-box"><span class="score-value" style="font-size:24px">' + (data.ai_visibility || 'N/A') + '</span><span class="score-label">Visibilita AI</span></div>';
            html += '</div>';

            // Featured Snippet
            html += '<div class="result-section"><h4>Featured Snippet Ready: ';
            html += data.featured_snippet_ready ? '<span style="color:green">Si</span>' : '<span style="color:red">No</span>';
            html += '</h4></div>';

            // Issues
            if (data.issues && data.issues.length) {
                html += '<div class="result-section"><h4><span class="dashicons dashicons-warning"></span> Problemi AEO:</h4><ul>';
                data.issues.forEach(function(issue) {
                    var text = typeof issue === 'string' ? issue : (issue.description || issue.message || JSON.stringify(issue));
                    html += '<li>' + text + '</li>';
                });
                html += '</ul></div>';
            }

            // Secondary Keywords
            if (data.secondary_keywords && data.secondary_keywords.length) {
                html += '<div class="result-section"><h4><span class="dashicons dashicons-tag"></span> Keyword Secondarie:</h4><div class="keyword-tags">';
                data.secondary_keywords.forEach(function(kw) {
                    var kwText = typeof kw === 'string' ? kw : (kw.keyword || kw.text || JSON.stringify(kw));
                    html += '<span class="keyword-tag aeo">' + kwText + '</span>';
                });
                html += '</div></div>';
            }

            html += '<div style="margin:10px 0;"><span class="token-badge">' + (SeoAeoOrchestra.creditCosts.aeo_analysis || 3) + ' crediti utilizzati</span></div>';

            // Improvements
            if (data.improvements && data.improvements.length) {
                html += '<div class="result-section"><h4><span class="dashicons dashicons-lightbulb"></span> Suggerimenti AEO:</h4><ul>';
                data.improvements.forEach(function(s) {
                    var text = typeof s === 'string' ? s : (s.description || s.message || JSON.stringify(s));
                    html += '<li>' + text + '</li>';
                });
                html += '</ul></div>';
            }

            // Recommended Schema
            if (data.recommended_schema && data.recommended_schema.length) {
                html += '<div class="result-section"><h4><span class="dashicons dashicons-editor-code"></span> Schema.org consigliati:</h4><div class="keyword-tags">';
                data.recommended_schema.forEach(function(s) { html += '<span class="keyword-tag aeo">' + s + '</span>'; });
                html += '</div></div>';
            }

            // Sample Questions
            if (data.sample_questions && data.sample_questions.length) {
                html += '<div class="result-section"><h4><span class="dashicons dashicons-format-chat"></span> Domande AI potenziali:</h4><ul>';
                data.sample_questions.forEach(function(q) { html += '<li>' + q + '</li>'; });
                html += '</ul></div>';
            }

            html += '</div>';
            $('#aeo-analysis-output').html(html);
            var aeoUrl = $('#aeo-page-select').val() || $('#aeo-url').val() || '';
            var aeoAnalysisRestore = {
                fields: {
                    '#aeo-page-select': $('#aeo-page-select').val() || '',
                    '#aeo-url':         $('#aeo-url').val() || '',
                    '#aeo-keyword':     $('#aeo-keyword').val() || ''
                },
                outputs: { '#aeo-analysis-output': $('#aeo-analysis-output').html() }
            };
            SeoAeoOrchestra.saveHistory('aeo_analysis', 'analysis', aeoUrl, data, 3, aeoAnalysisRestore);
        },

        // ── AEO: Content ──
        aeoContent: function(e) {
            e.preventDefault();
            var $button = $(this);
            var topic = $('#aeo-content-topic').val();
            var keywords = $('#aeo-content-keywords').val();
            var includeSchema = $('#aeo-include-schema').is(':checked');
            var includeFaq = $('#aeo-include-faq').is(':checked');
            var engines = [];
            $('input[name="aeo_engines[]"]:checked').each(function() { engines.push($(this).val()); });

            if (!topic) {
                SeoAeoOrchestra.showNotice('Inserisci un argomento', 'error');
                return;
            }
            if (!SeoAeoOrchestra.checkCredits('aeo_content')) return;
            $('.seo-aeo-insufficient-credits').remove();
            $button.prop('disabled', true).html('<span class="orchestra-spinner"></span> Generazione AEO in corso (30-60 sec)...');

            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_aeo_content',
                nonce: seoAeoOrchestra.nonce,
                topic: topic,
                keywords: keywords,
                target_engines: engines,
                include_schema: includeSchema ? 'true' : 'false',
                include_faq: includeFaq ? 'true' : 'false'
            }, function(response) {
                if (response && (response.content || response.article)) {
                    var content = response.content || response.article || '';
                    var wc = (content.split(/\s+/) || []).length;
                    var html = '<div style="margin-bottom:10px;"><span class="token-badge">~' + wc + ' parole</span> <span class="token-badge">' + (SeoAeoOrchestra.creditCosts.aeo_content || 10) + ' crediti utilizzati</span></div>';
                    html += '<div class="generated-content" style="max-height:500px;overflow:auto;border:1px solid #eee;padding:15px;border-radius:8px;">' + content + '</div>';
                    if (response.schema_markup) {
                        html += '<div class="result-section"><h4><span class="dashicons dashicons-editor-code"></span> Schema Markup generato:</h4>';
                        html += '<pre style="background:#f0f0f0;padding:15px;border-radius:8px;overflow-x:auto;font-size:12px">' +
                            (typeof response.schema_markup === 'string' ? response.schema_markup : JSON.stringify(response.schema_markup, null, 2)) + '</pre></div>';
                    }
                    html += '<div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">';
                    html += '<button type="button" class="button button-primary" onclick="SeoAeoOrchestra.publishContent(\'' + topic.replace(/'/g, "\\'") + '\', jQuery(\'.generated-content\').first().html(), \'draft\')"><span class="dashicons dashicons-upload" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Pubblica Bozza') + '</button>';
                    html += '<button type="button" class="button" onclick="SeoAeoOrchestra.publishContent(\'' + topic.replace(/'/g, "\\'") + '\', jQuery(\'.generated-content\').first().html(), \'publish\')"><span class="dashicons dashicons-yes" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Pubblica Subito') + '</button>';
                    html += '<button type="button" class="button" onclick="SeoAeoOrchestra.previewArticle(\'' + topic.replace(/'/g, "\\'") + '\', jQuery(\'.generated-content\').first().html())"><span class="dashicons dashicons-visibility" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Anteprima') + '</button>';
                    html += '<button type="button" class="button" onclick="SeoAeoOrchestra.copyContent()"><span class="dashicons dashicons-admin-page" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Copia') + '</button>';
                    html += '<button type="button" class="button" onclick="SeoAeoOrchestra.openMediaUploader(jQuery(\'.generated-content\').first().parent());"><span class="dashicons dashicons-admin-media" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Foto da Media') + '</button>';
                    html += '<button type="button" class="button" onclick="SeoAeoOrchestra.openAiImageGenerator(jQuery(\'.generated-content\').first().parent());"><span class="dashicons dashicons-format-image" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Genera Foto AI') + '</button>';
                    if (SeoAeoOrchestra.isPremiumTier()) {
                        // 3.26.2: bottone "Articolo Completo AI" su Contenuti AEO (rigenera completo con immagine + meta)
                        html += '<button type="button" class="button" style="background:#7c3aed;border-color:#7c3aed;color:#fff;" title="' + SeoAeoOrchestra.t('Rigenera questa pagina come Articolo Completo: testo + immagine AI + meta tags. ~25 crediti.') + '" onclick="SeoAeoOrchestra.generateCompleteArticle(\'' + topic.replace(/'/g, "\\'") + '\',\'' + (keywords ? keywords.split(',')[0].replace(/'/g, "\\'") : topic.replace(/'/g, "\\'")) + '\',\'aeo_content\',jQuery(\'#aeo-content-output\'));"><span class="dashicons dashicons-star-filled" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Articolo Completo AI') + '</button>';
                    }
                    html += '</div>';
                    $('#aeo-content-output').html(html);
                    // Wrap container per generateCompleteArticle che si aspetta una struttura .expand-content-card
                    var $aeoOut = $('#aeo-content-output');
                    if (!$aeoOut.find('.expand-content-actions').length) {
                        $aeoOut.addClass('expand-content-card');
                        $aeoOut.append('<div class="expand-content-actions"></div>');
                    }
                    SeoAeoOrchestra.showNotice('Contenuto AEO generato!', 'success');
                    var aeoEngines = $('input[name="aeo_engines[]"]:checked').map(function(){ return this.value; }).get();
                    var aeoContentRestore = {
                        fields: {
                            '#aeo-content-topic':    topic,
                            '#aeo-content-keywords': $('#aeo-content-keywords').val(),
                            '#aeo-include-schema':   $('#aeo-include-schema').is(':checked'),
                            '#aeo-include-faq':      $('#aeo-include-faq').is(':checked')
                        },
                        outputs: { '#aeo-content-output': $('#aeo-content-output').html() },
                        notes: { aeo_engines: aeoEngines }
                    };
                    SeoAeoOrchestra.saveHistory('aeo_content', 'content', topic, content, 5, aeoContentRestore);
                } else {
                    SeoAeoOrchestra.showNotice('Errore: ' + (response.error || response.detail || 'Generazione AEO fallita'), 'error');
                }
            }).fail(function() {
                SeoAeoOrchestra.showNotice('Errore di connessione o timeout', 'error');
            }).always(function() {
                $button.prop('disabled', false).html('<span class="dashicons dashicons-format-chat"></span> Genera Contenuto AEO — <span class="credit-cost" data-cost-key="aeo_content">' + (SeoAeoOrchestra.creditCosts.aeo_content || 12) + '</span> crediti');
            });
        },

        // ── Utilities ──
        copyContent: function() {
            var content = $('.generated-content').html();
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(content).select();
            document.execCommand('copy');
            $temp.remove();
            SeoAeoOrchestra.showNotice('Contenuto copiato!', 'success');
        },

        // ── Preview article as final page ──
        // 3.26.1: anteprima usando il theme reale del sito (auto-draft + WP preview URL).
        // Fallback al popup HTML custom se il salvataggio draft fallisce.
        previewArticle: function(title, content) {
            // 3.26.6: estrae il primo <img> dal content e lo passa come featured image al
            // draft cosi' il theme non mostra una "banda alta" vuota nell'header.
            // 3.31.5: rimuove anche il tag <img> matchato dal content per evitare duplicato
            // (featured image + img inline che mostrava la stessa immagine due volte).
            var imageUrl = '';
            var contentForPreview = String(content || '');
            try {
                var m = contentForPreview.match(/<img\s+[^>]*src\s*=\s*["']([^"']+)["'][^>]*>/i);
                if (m && m[1]) {
                    imageUrl = m[1];
                    // Strip the matched <img> tag (only first occurrence)
                    contentForPreview = contentForPreview.replace(m[0], '').trim();
                }
            } catch (e) {}
            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_create_preview_draft',
                nonce: seoAeoOrchestra.nonce,
                title: title,
                content: contentForPreview,
                image_url: imageUrl
            }, function(resp) {
                if (resp && resp.success && resp.preview_url) {
                    var win = window.open(resp.preview_url, '_blank');
                    if (!win) {
                        SeoAeoOrchestra.showNotice('Il popup è stato bloccato. Consenti i popup per questo sito.', 'error');
                    } else {
                        SeoAeoOrchestra.showNotice('✓ Anteprima aperta col theme del sito · La bozza viene eliminata dopo 24h', 'success');
                    }
                    return;
                }
                SeoAeoOrchestra._previewArticleFallback(title, content);
            }).fail(function() {
                SeoAeoOrchestra._previewArticleFallback(title, content);
            });
        },

        _previewArticleFallback: function(title, content) {
            var siteUrl = window.location.origin;
            var siteName = $('meta[property="og:site_name"]').attr('content') || document.title.split(' - ').pop() || 'Il Mio Sito';
            var themeColor = getComputedStyle(document.body).getPropertyValue('--wp--preset--color--primary') || '#0055FF';
            var previewHtml = '<!DOCTYPE html><html lang="it"><head>';
            previewHtml += '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
            previewHtml += '<title>' + title + ' - ' + siteName + '</title>';
            previewHtml += '<style>';
            previewHtml += '*, *::before, *::after { box-sizing: border-box; margin:0; padding:0; }';
            previewHtml += 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, sans-serif; color:#1a1a2e; background:#fafafa; line-height:1.8; }';
            previewHtml += 'header { background:#111827; padding:16px 0; border-bottom:3px solid #0055FF; }';
            previewHtml += 'header .inner { max-width:800px; margin:0 auto; padding:0 24px; display:flex; align-items:center; justify-content:space-between; }';
            previewHtml += 'header .site-name { color:#fff; font-size:18px; font-weight:700; }';
            previewHtml += 'header nav a { color:#94a3b8; font-size:14px; margin-left:20px; text-decoration:none; }';
            previewHtml += '.hero { background:linear-gradient(135deg,#0a0a0a 0%,#111827 50%,#0055FF 100%); padding:60px 24px; text-align:center; color:#fff; }';
            previewHtml += '.hero h1 { font-size:36px; max-width:700px; margin:0 auto 16px; line-height:1.3; }';
            previewHtml += '.hero .meta { font-size:14px; color:rgba(255,255,255,0.6); }';
            previewHtml += 'article { max-width:800px; margin:0 auto; padding:40px 24px 80px; }';
            previewHtml += 'article h2 { font-size:24px; margin:32px 0 12px; color:#111827; border-left:4px solid #0055FF; padding-left:12px; }';
            previewHtml += 'article h3 { font-size:18px; margin:24px 0 8px; color:#1e293b; }';
            previewHtml += 'article p { margin:0 0 16px; font-size:16px; color:#334155; }';
            previewHtml += 'article ul, article ol { margin:0 0 16px 24px; color:#334155; }';
            previewHtml += 'article li { margin-bottom:6px; }';
            previewHtml += 'article img { max-width:100%; border-radius:12px; margin:20px 0; box-shadow:0 4px 20px rgba(0,0,0,0.08); }';
            previewHtml += 'article table { width:100%; border-collapse:collapse; margin:20px 0; border-radius:8px; overflow:hidden; }';
            previewHtml += 'article th { background:#f1f5f9; padding:10px 14px; text-align:left; font-size:13px; font-weight:600; color:#475569; border-bottom:2px solid #e2e8f0; }';
            previewHtml += 'article td { padding:10px 14px; border-bottom:1px solid #f1f5f9; font-size:14px; }';
            previewHtml += 'article tr:hover td { background:#f8fafc; }';
            previewHtml += 'article blockquote { border-left:4px solid #0055FF; padding:12px 20px; margin:20px 0; background:#f0f9ff; border-radius:0 8px 8px 0; font-style:italic; color:#1e40af; }';
            previewHtml += 'article pre { background:#1e293b; color:#e2e8f0; padding:16px; border-radius:8px; overflow-x:auto; font-size:13px; margin:16px 0; }';
            previewHtml += 'article code { font-family:monospace; background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:14px; }';
            previewHtml += '.faq-section { background:#f8fafc; border-radius:12px; padding:24px; margin:30px 0; }';
            previewHtml += '.preview-bar { position:fixed; top:0; left:0; right:0; background:#111827; color:#fff; padding:8px 16px; font-size:13px; display:flex; align-items:center; justify-content:space-between; z-index:9999; box-shadow:0 2px 10px rgba(0,0,0,0.2); }';
            previewHtml += '.preview-bar button { background:#0055FF; color:#fff; border:none; padding:6px 16px; border-radius:6px; cursor:pointer; font-size:13px; }';
            previewHtml += 'footer { background:#111827; color:#64748b; padding:30px 24px; text-align:center; font-size:13px; }';
            previewHtml += '@media print { .preview-bar { display:none; } }';
            previewHtml += '</style></head><body>';
            previewHtml += '<div class="preview-bar"><span>Anteprima articolo - Come apparira sul sito</span><button onclick="window.print();">Stampa / PDF</button></div>';
            previewHtml += '<header><div class="inner"><span class="site-name">' + siteName + '</span><nav><a href="#">Home</a><a href="#">Blog</a><a href="#">Contatti</a></nav></div></header>';
            previewHtml += '<div class="hero"><h1>' + title + '</h1><div class="meta">Pubblicato il ' + new Date().toLocaleDateString(SeoAeoOrchestra.bcp47(), {day:'numeric',month:'long',year:'numeric'}) + ' &bull; AEO Orchestra</div></div>';
            previewHtml += '<article>' + content + '</article>';
            previewHtml += '<footer>&copy; ' + new Date().getFullYear() + ' ' + siteName + ' &bull; Generato con AEO Orchestra</footer>';
            previewHtml += '</body></html>';

            var previewWin = window.open('', '_blank');
            if (previewWin) {
                previewWin.document.write(previewHtml);
                previewWin.document.close();
            } else {
                SeoAeoOrchestra.showNotice('Il popup e stato bloccato. Consenti i popup per questo sito.', 'error');
            }
        },

        showNotice: function(message, type) {
            if (type === 'success') { SeoAeoOrchestra.loadCreditBalance(); }
            // i18n (3.25.2): auto-translate the message via SeoAeoOrchestra.t().
            // Pattern "Errore: " + dynamic mantiene il prefisso tradotto se presente in mappa.
            var translated = SeoAeoOrchestra.t(message);
            if (translated === message && typeof message === 'string' && message.indexOf('Errore: ') === 0) {
                var prefix = SeoAeoOrchestra.t('Errore: ');
                if (prefix !== 'Errore: ') translated = prefix + message.substring(8);
            }
            var $notice = $('<div class="orchestra-notice ' + type + '">' + translated + '</div>');
            $('.wrap.orchestra-admin').prepend($notice);
            setTimeout(function() { $notice.fadeOut(function() { $(this).remove(); }); }, 5000);
        },

        // ── Track usage (P1 - credit consumption auditing) ──
        trackUsage: function(usageType, credits, pageTitle, detail) {
            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_track_usage',
                nonce: seoAeoOrchestra.nonce,
                usage_type: usageType,
                credits: credits || 0,
                page_title: pageTitle || '',
                detail: detail || ''
            });
        },

        // ── Save to history ──
        // Args: section, type, title, data, credits, [restore]
        // restore = { fields: {selector:value, ...}, outputs: {selector:innerHTML, ...} } per "Riapri" (3.22.1)
        saveHistory: function(section, type, title, data, credits, restore) {
            SeoAeoOrchestra.trackUsage(section, credits, title, typeof data === 'string' ? data.substring(0, 100) : '');
            var payload = {
                action: 'seo_aeo_orchestra_save_history',
                nonce: seoAeoOrchestra.nonce,
                section: section, type: type, title: title,
                data: typeof data === 'string' ? data : JSON.stringify(data),
                credits: credits || 0
            };
            if (restore && typeof restore === 'object') {
                try { payload.restore_payload = JSON.stringify(restore); } catch (e) {}
            }
            $.post(seoAeoOrchestra.ajaxUrl, payload, function(response) {
                if (response && response.success) {
                    console.log('[AEO Orchestra] Cronologia salvata: ' + section + ' (' + (response.count || 0) + ' totali)');
                    var containerId = 'history-' + section.replace('_', '-');
                    if ($('#' + containerId).length) {
                        SeoAeoOrchestra.loadHistory(section, containerId);
                    }
                } else {
                    console.warn('[AEO Orchestra] Errore salvataggio cronologia:', response);
                }
            }).fail(function(xhr, status, error) {
                console.error('[AEO Orchestra] AJAX errore cronologia:', status, error);
            });
        },

        // ── Restore from history (3.22.1) ──
        // Fetch full item by id, apply form fields + output HTML, scroll to first output.
        // 3.38.8 Task 1 — confirmation modal for cronologia "Riapri".
        showHistoryRestoreConfirm: function(itemId, itemDate, itemTitle) {
            if (!itemId) return;
            var $ = jQuery;
            $('#orch-hist-confirm-modal').remove();
            var T = SeoAeoOrchestra.t || function(s){return s;};
            var dateStr = itemDate ? (' ' + T('del') + ' ' + itemDate) : '';
            var titleLine = itemTitle ? ('<div class="orch-hcm-title">"' + jQuery('<div>').text(itemTitle).html() + '"</div>') : '';
            var html = '<div class="orch-hcm-backdrop" id="orch-hist-confirm-modal">'
                + '<div class="orch-hcm-modal">'
                +   '<div class="orch-hcm-head">'
                +     '<h3>' + T('Vuoi caricare questa analisi storica?') + '</h3>'
                +     '<button type="button" class="orch-hcm-close" aria-label="' + T('Chiudi') + '">×</button>'
                +   '</div>'
                +   '<div class="orch-hcm-body">'
                +     titleLine
                +     '<p>' + T('I risultati e il Piano d\'Azione attuali verranno sostituiti con quelli dell\'analisi') + dateStr + '. ' + T('Le modifiche già applicate al sito non vengono toccate.') + '</p>'
                +   '</div>'
                +   '<div class="orch-hcm-foot">'
                +     '<button type="button" class="button orch-hcm-cancel">' + T('Annulla') + '</button>'
                +     '<button type="button" class="button button-primary orch-hcm-confirm">→ ' + T('Carica analisi') + '</button>'
                +   '</div>'
                + '</div>'
                + '</div>';
            if (!$('#orch-hcm-styles').length) {
                $('head').append(
                    '<style id="orch-hcm-styles">' +
                    '.orch-hcm-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,0.5); display: flex; align-items: center; justify-content: center; z-index: 100001; animation: orchHcmFade 0.15s ease; }' +
                    '@keyframes orchHcmFade { from { opacity: 0; } to { opacity: 1; } }' +
                    '.orch-hcm-modal { background: #fff; border-radius: 12px; box-shadow: 0 24px 48px rgba(0,0,0,0.25); max-width: 480px; width: calc(100% - 32px); }' +
                    '.orch-hcm-head { padding: 18px 22px 12px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e5e7eb; }' +
                    '.orch-hcm-head h3 { margin: 0; font-size: 17px; font-weight: 600; color: #0F172A; }' +
                    '.orch-hcm-close { background: none; border: 0; font-size: 24px; line-height: 1; color: #94A3B8; cursor: pointer; padding: 0 4px; }' +
                    '.orch-hcm-close:hover { color: #475569; }' +
                    '.orch-hcm-body { padding: 16px 22px; color: #475569; font-size: 14px; line-height: 1.55; }' +
                    '.orch-hcm-title { font-weight: 600; color: #0F172A; margin-bottom: 8px; }' +
                    '.orch-hcm-foot { padding: 12px 22px 18px; display: flex; gap: 8px; justify-content: flex-end; }' +
                    '</style>'
                );
            }
            $('body').append(html);
            var $modal = $('#orch-hist-confirm-modal');
            function close() { $modal.remove(); }
            $modal.on('click', function(ev){ if (ev.target === $modal[0]) close(); });
            $modal.find('.orch-hcm-close, .orch-hcm-cancel').on('click', close);
            $modal.find('.orch-hcm-confirm').on('click', function() {
                close();
                // Update URL for shareable link (don't reload).
                try {
                    var url = new URL(window.location.href);
                    url.searchParams.set('history_id', itemId);
                    history.replaceState(null, '', url.toString());
                } catch (e) {}
                SeoAeoOrchestra.restoreFromHistory(itemId);
            });
        },

        restoreFromHistory: function(itemId) {
            if (!itemId) return;
            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_get_history_item',
                nonce: seoAeoOrchestra.nonce,
                id: itemId
            }, function(resp) {
                if (!resp || !resp.success || !resp.item) {
                    SeoAeoOrchestra.showNotice('Item cronologia non trovato', 'error');
                    return;
                }
                var item = resp.item;
                var rp = item.restore_payload || '';
                if (!rp) {
                    SeoAeoOrchestra.showNotice('Questo elemento non è ripristinabile (creato con vecchia versione)', 'error');
                    return;
                }
                try {
                    var restore = typeof rp === 'string' ? JSON.parse(rp) : rp;

                    // Viewer mode (3.22.4): per sub-flow nested (article, complete_article, ai_image)
                    if (restore.view_html) {
                        SeoAeoOrchestra.showHistoryViewerModal(item.title || 'Cronologia', restore.view_html, restore.meta || null);
                        return;
                    }

                    // 3.38.9 — orchestrator-aware restore. When the payload carries the
                    // full state snapshot (saved since 3.38.9), rehydrate the in-memory
                    // arrays used by clickable problem cards, fill scalar counters,
                    // reveal the results container, close the confirm modal, toast,
                    // and scroll the user to the result section.
                    if (restore.state && restore.state.results) {
                        var st = restore.state;
                        SeoAeoOrchestra._allSeoIssues   = st.allSeoIssues || [];
                        SeoAeoOrchestra._allAeoIssues   = st.allAeoIssues || [];
                        SeoAeoOrchestra._allActions     = st.allActions   || [];
                        SeoAeoOrchestra._results        = st.results      || [];
                        SeoAeoOrchestra.orchestrateResults = st.results   || [];
                        SeoAeoOrchestra.orchestratePages   = st.pages     || [];
                        var c = st.counters || {};
                        if (c.avgSeo != null)         $('#orch-avg-seo').text(c.avgSeo);
                        if (c.avgAeo != null)         $('#orch-avg-aeo').text(c.avgAeo);
                        if (c.pagesCount != null)     $('#orch-pages-analyzed').text(c.pagesCount);
                        if (c.totalSeoIssues != null) $('#orch-seo-issues-count').text(c.totalSeoIssues);
                        if (c.totalAeoIssues != null) $('#orch-aeo-issues-count').text(c.totalAeoIssues);
                        if (c.totalActions != null)   $('#orch-total-actions').text(c.totalActions);
                        // 3.39.8 Bug A — rebuild Piano d'Azione from in-memory
                        // state (not the saved-HTML outputs map, which on older
                        // history entries can contain merged/stale markup).
                        if (SeoAeoOrchestra.renderActionPlan) {
                            SeoAeoOrchestra.renderActionPlan(SeoAeoOrchestra._allActions);
                        }
                        if (SeoAeoOrchestra.renderLlmFailedBanner) {
                            var _failed = 0;
                            (SeoAeoOrchestra._results || []).forEach(function(r) {
                                if ((r.seo_detail && r.seo_detail._llm_failed) || (r.aeo_detail && r.aeo_detail._llm_failed)) _failed++;
                            });
                            SeoAeoOrchestra.renderLlmFailedBanner(_failed, (SeoAeoOrchestra._results || []).length);
                        }
                    }

                    // Restore form fields
                    if (restore.fields) {
                        $.each(restore.fields, function(selector, val) {
                            var $el = $(selector);
                            if (!$el.length) return;
                            if ($el.is(':checkbox')) {
                                $el.prop('checked', !!val);
                            } else if ($el.is(':radio')) {
                                $('[name="' + $el.attr('name') + '"][value="' + val + '"]').prop('checked', true);
                            } else {
                                $el.val(val);
                            }
                            $el.trigger('change');
                        });
                    }
                    // Restore output HTML
                    var firstOutputSel = null;
                    if (restore.outputs) {
                        var _orchStateActive = !!(restore.state && restore.state.results);
                        $.each(restore.outputs, function(selector, html) {
                            // 3.39.8 — when orchestrator state hydration ran,
                            // skip #orch-action-plan in the legacy outputs map
                            // so renderActionPlan() (which already ran above)
                            // is not overwritten by potentially stale markup.
                            if (_orchStateActive && selector === '#orch-action-plan') return;
                            var $el = $(selector);
                            if (!$el.length) return;
                            $el.html(html);
                            if (!firstOutputSel) firstOutputSel = selector;
                        });
                    }
                    // 3.38.9 — orchestrator-specific finalize: ensure the results
                    // container is visible, close the confirm modal, show a richer
                    // success toast, and scroll to the start of the result section.
                    if (restore.state && restore.state.results) {
                        var $orchResults = $('#orchestrator-results');
                        if ($orchResults.length) {
                            $orchResults.show();
                            firstOutputSel = firstOutputSel || '#orchestrator-results';
                        }
                        $('#orch-hist-confirm-modal').remove();
                        var when = item.date ? (' ' + SeoAeoOrchestra.t('del') + ' ' + item.date) : '';
                        SeoAeoOrchestra.showNotice(SeoAeoOrchestra.t('✓ Analisi') + when + ' ' + SeoAeoOrchestra.t('caricata dalla cronologia'), 'success');
                    } else {
                        SeoAeoOrchestra.showNotice('✓ Ripreso dalla cronologia: "' + (item.title || '') + '"', 'success');
                    }

                    // Scroll
                    if (firstOutputSel && $(firstOutputSel).length) {
                        $('html, body').animate({ scrollTop: $(firstOutputSel).offset().top - 60 }, 350);
                    }
                } catch (e) {
                    console.error('[AEO Orchestra] restore parse error:', e);
                    SeoAeoOrchestra.showNotice('Errore nel ripristino', 'error');
                }
            }).fail(function() {
                SeoAeoOrchestra.showNotice('Errore di rete nel ripristino', 'error');
            });
        },

        // ── History viewer modal (3.22.4) ──
        // Per sub-flow nested (article generato da suggestion, complete_article premium,
        // ai_image): mostra il contenuto in un overlay standalone, non in-place.
        showHistoryViewerModal: function(title, viewHtml, meta) {
            $('#orch-hist-viewer-modal').remove();
            var metaBlock = '';
            if (meta) {
                metaBlock = '<div class="orch-histv-meta">';
                if (meta.topic) metaBlock += '<span><strong>Argomento:</strong> ' + meta.topic + '</span>';
                if (meta.keyword) metaBlock += '<span><strong>Keyword:</strong> ' + meta.keyword + '</span>';
                if (meta.words) metaBlock += '<span><strong>Parole:</strong> ~' + meta.words + '</span>';
                if (meta.credits) metaBlock += '<span><strong>Crediti:</strong> ' + meta.credits + '</span>';
                metaBlock += '</div>';
            }
            var modal = '<div class="orch-histv-backdrop" id="orch-hist-viewer-modal">' +
                '<div class="orch-histv-modal">' +
                    '<div class="orch-histv-head">' +
                        '<div>' +
                            '<div class="orch-histv-eyebrow">📚 Riaperto dalla cronologia</div>' +
                            '<h3 class="orch-histv-title">' + (title || '') + '</h3>' +
                        '</div>' +
                        '<button class="orch-histv-close">×</button>' +
                    '</div>' +
                    '<div class="orch-histv-body">' +
                        metaBlock +
                        '<div class="orch-histv-content">' + viewHtml + '</div>' +
                    '</div>' +
                    '<div class="orch-histv-foot">' +
                        '<button class="button orch-histv-copy-btn">📋 Copia HTML</button>' +
                        '<button class="button button-primary orch-histv-close2">Chiudi</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
            $('body').append(modal);

            // Inject styles once
            if (!$('#orch-histv-styles').length) {
                $('head').append('<style id="orch-histv-styles">' +
                    '.orch-histv-backdrop { position: fixed; inset: 0; background: rgba(10,14,39,0.7); backdrop-filter: blur(4px); z-index: 100000; display: flex; align-items: center; justify-content: center; padding: 20px; }' +
                    '.orch-histv-modal { background: #fff; border-radius: 14px; max-width: 900px; width: 100%; max-height: 92vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 24px 64px rgba(0,0,0,0.4); }' +
                    '.orch-histv-head { padding: 18px 24px; background: linear-gradient(135deg, #0A0E27, #0055FF); color: #fff; display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; }' +
                    '.orch-histv-eyebrow { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; font-weight: 600; }' +
                    '.orch-histv-title { margin: 6px 0 0; font-size: 20px; font-weight: 700; }' +
                    '.orch-histv-close { background: rgba(255,255,255,0.18); border: none; color: #fff; width: 34px; height: 34px; border-radius: 50%; font-size: 22px; cursor: pointer; }' +
                    '.orch-histv-body { flex: 1; overflow: auto; padding: 22px 26px; }' +
                    '.orch-histv-meta { display: flex; flex-wrap: wrap; gap: 12px 22px; padding: 12px 16px; background: #f9fafb; border-radius: 8px; margin-bottom: 16px; font-size: 13px; color: #4b5563; }' +
                    '.orch-histv-content { border: 1px solid #e5e7eb; border-radius: 8px; padding: 18px; background: #fff; max-height: 480px; overflow: auto; line-height: 1.6; }' +
                    '.orch-histv-content img { max-width: 100%; height: auto; border-radius: 6px; }' +
                    '.orch-histv-foot { padding: 14px 24px; border-top: 1px solid #e5e7eb; background: #fafafa; display: flex; gap: 10px; justify-content: flex-end; }' +
                '</style>');
            }

            $('#orch-hist-viewer-modal .orch-histv-close, #orch-hist-viewer-modal .orch-histv-close2').on('click', function() {
                $('#orch-hist-viewer-modal').remove();
            });
            $('#orch-hist-viewer-modal').on('click', function(e) {
                if (e.target === this) $(this).remove();
            });
            $('#orch-hist-viewer-modal .orch-histv-copy-btn').on('click', function() {
                var html = $('#orch-hist-viewer-modal .orch-histv-content').html();
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(html).then(function() {
                        SeoAeoOrchestra.showNotice('HTML copiato negli appunti', 'success');
                    });
                } else {
                    var ta = document.createElement('textarea'); ta.value = html;
                    document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
                    SeoAeoOrchestra.showNotice('HTML copiato negli appunti', 'success');
                }
            });
        },

        // ── Load history for a section (formatted display) ──
        loadHistory: function(section, containerId) {
            var $container = $('#' + containerId);
            if (!$container.length) return;
            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_get_history',
                nonce: seoAeoOrchestra.nonce,
                section: section
            }, function(items) {
                if (!items || !items.length) {
                    $container.html('');
                    return;
                }

                var types = {};
                $.each(items, function(i, item) {
                    if (item.type) types[item.type] = true;
                });

                var html = '<div class="orchestra-history">';
                html += '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px;">';
                html += '<h3 style="margin:0;"><span class="dashicons dashicons-backup"></span> ' + SeoAeoOrchestra.t('Cronologia') + ' (' + items.length + ')</h3>';
                html += '<div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">';
                // 3.26.4: bottone Cancella su ogni sezione cronologia
                html += '<button class="button button-small orch-history-clear-section" title="' + SeoAeoOrchestra.t('Cancella tutta la cronologia di questa sezione') + '" data-section="' + section + '" data-container="' + containerId + '"><span class="dashicons dashicons-trash" style="font-size:14px;width:14px;height:14px;margin-top:2px;"></span> ' + SeoAeoOrchestra.t('Svuota') + '</button>';

                // Filter buttons
                var typeKeys = Object.keys(types);
                if (typeKeys.length > 1) {
                    html += '<div class="history-filters" style="display:flex;gap:4px;flex-wrap:wrap;">';
                    html += '<button class="button button-small history-filter-btn active" data-filter="all" data-section="' + section + '" data-container="' + containerId + '">' + SeoAeoOrchestra.t('Tutti') + '</button>';
                    var typeLabels = {analysis: SeoAeoOrchestra.t('Analisi'), content: SeoAeoOrchestra.t('Contenuto'), meta: SeoAeoOrchestra.t('Meta'), ai_image: SeoAeoOrchestra.t('Immagini')};
                    $.each(typeKeys, function(i, type) {
                        var label = typeLabels[type] || type;
                        html += '<button class="button button-small history-filter-btn" data-filter="' + type + '" data-section="' + section + '" data-container="' + containerId + '">' + label + '</button>';
                    });
                    html += '</div>';
                }
                html += '</div>';
                $.each(items, function(i, item) {
                    html += '<div class="history-item" data-type="' + (item.type || '') + '" data-idx="' + i + '">';
                    html += '<div style="flex:1;cursor:pointer;" onclick="jQuery(this).parent().next(\'.history-detail\').slideToggle(200);">';
                    html += '<span class="hi-title">' + (item.title || SeoAeoOrchestra.t('Senza titolo')) + '</span><br>';
                    html += '<span class="hi-meta">' + (item.date || '') + ' &mdash; ' + (item.type || '') + '</span>';
                    html += '</div>';
                    html += '<div style="display:flex;align-items:center;gap:8px;">';
                    html += '<span class="hi-credits">' + (item.credits || 0) + ' cr</span>';
                    // Restore button (3.22.1) — riapre form + output dove eri
                    if (item.has_restore && item.id) {
                        html += '<button class="button button-small button-primary history-restore-btn" title="' + SeoAeoOrchestra.t('Ripristina nel modulo') + '" data-item-id="' + item.id + '" data-item-date="' + (item.date || '') + '" data-item-title="' + (item.title || '').replace(/"/g, '&quot;') + '" style="background:#7C3AED;border-color:#6b21a8;color:#fff;"><span class="dashicons dashicons-update" style="font-size:14px;width:14px;height:14px;margin-top:2px;"></span> ' + SeoAeoOrchestra.t('Riapri') + '</button>';
                    }
                    // Re-run button (legacy, per analisi)
                    if (item.type === 'analysis') {
                        html += '<button class="button button-small history-rerun-btn" title="' + SeoAeoOrchestra.t('Riesegui analisi') + '" data-type="' + (item.section || section) + '" data-rerun=\'' + (item.data || '{}').replace(/'/g, "&#39;") + '\'><span class="dashicons dashicons-controls-repeat" style="font-size:14px;width:14px;height:14px;margin-top:2px;"></span></button>';
                    }
                    html += '</div>';
                    html += '</div>';
                    // Expandable preview
                    html += '<div class="history-detail" style="display:none;padding:12px 15px;background:#fff;border:1px solid #eee;border-radius:0 0 8px 8px;margin-top:-8px;margin-bottom:8px;font-size:13px;max-height:400px;overflow:auto;">';
                    html += SeoAeoOrchestra.formatHistoryData(item.data, item.section || section, item.type);
                    html += '</div>';
                });
                html += '</div>';
                $container.html(html);
            }).fail(function(xhr, status, error) {
                console.error('[AEO Orchestra] Errore caricamento cronologia:', section, error);
            });
        },

        // ── Format history data for human-readable display ──
        formatHistoryData: function(rawData, section, type) {
            if (!rawData) return '<em>' + SeoAeoOrchestra.t('Nessun dato') + '</em>';
            var data = rawData;
            try { data = JSON.parse(rawData); } catch(e) { /* already string or invalid */ }

            // If data is a string (HTML content or plain text)
            if (typeof data === 'string') {
                if (data.indexOf('<') !== -1 && data.indexOf('>') !== -1) {
                    return '<div style="border:1px solid #eee;padding:10px;border-radius:6px;max-height:250px;overflow:auto;">' + data + '</div>';
                }
                return '<div>' + data.substring(0, 1500) + '</div>';
            }

            // Object data — format based on section
            var html = '';

            // SEO Analysis results
            if (data.score !== undefined || data.seo_score !== undefined || section === 'seo_analysis') {
                var score = data.score || data.seo_score || 0;
                var scoreClass = score >= 70 ? '#059669' : score >= 40 ? '#D97706' : '#DC2626';
                html += '<div style="display:flex;gap:15px;align-items:center;margin-bottom:12px;">';
                html += '<div style="font-size:28px;font-weight:bold;color:' + scoreClass + ';">' + score + '<span style="font-size:14px;color:#999;">/100</span></div>';
                html += '<div style="font-size:13px;color:#666;">SEO Score</div></div>';
                if (data.primary_keyword) html += '<div style="margin-bottom:8px;"><strong>Keyword:</strong> <span style="background:#e0e7ff;color:#3730a3;padding:2px 8px;border-radius:12px;font-size:12px;">' + data.primary_keyword + '</span></div>';
                if (data.secondary_keywords && data.secondary_keywords.length) {
                    html += '<div style="margin-bottom:8px;"><strong>Keyword correlate:</strong> ';
                    data.secondary_keywords.forEach(function(kw) {
                        var t = typeof kw === 'string' ? kw : (kw.keyword || kw.text || '');
                        if (t) html += '<span style="background:#f0f9ff;color:#0369a1;padding:1px 6px;border-radius:10px;font-size:11px;margin:2px;">' + t + '</span> ';
                    });
                    html += '</div>';
                }
                if (data.issues && data.issues.length) {
                    html += '<div style="margin-bottom:8px;"><strong>Problemi (' + data.issues.length + '):</strong><ul style="margin:4px 0 0 16px;padding:0;">';
                    data.issues.forEach(function(issue) {
                        var t = typeof issue === 'string' ? issue : (issue.description || issue.message || JSON.stringify(issue));
                        html += '<li style="margin-bottom:3px;color:#b91c1c;">' + t + '</li>';
                    });
                    html += '</ul></div>';
                }
                if (data.suggestions && data.suggestions.length) {
                    html += '<div><strong>Suggerimenti:</strong><ul style="margin:4px 0 0 16px;padding:0;">';
                    data.suggestions.forEach(function(s) {
                        var t = typeof s === 'string' ? s : (s.description || s.message || JSON.stringify(s));
                        html += '<li style="margin-bottom:3px;color:#059669;">' + t + '</li>';
                    });
                    html += '</ul></div>';
                }
                if (data.missing_keywords && data.missing_keywords.length) {
                    html += '<div style="margin-top:8px;"><strong>Keyword mancanti:</strong> ';
                    data.missing_keywords.forEach(function(kw) { html += '<span style="background:#FEF3C7;color:#92400E;padding:1px 6px;border-radius:10px;font-size:11px;margin:2px;">' + kw + '</span> '; });
                    html += '</div>';
                }
                return html || '<pre style="white-space:pre-wrap;font-size:12px;">' + JSON.stringify(data, null, 2) + '</pre>';
            }

            // AEO Analysis results
            if (data.aeo_score !== undefined || section === 'aeo_analysis') {
                var aScore = data.aeo_score || 0;
                var aClass = aScore >= 60 ? '#059669' : aScore >= 30 ? '#D97706' : '#DC2626';
                html += '<div style="display:flex;gap:20px;margin-bottom:12px;">';
                html += '<div><span style="font-size:28px;font-weight:bold;color:' + aClass + ';">' + aScore + '</span><span style="font-size:14px;color:#999;">/100 AEO</span></div>';
                if (data.citability_score !== undefined) html += '<div><span style="font-size:20px;font-weight:bold;">' + data.citability_score + '</span><span style="font-size:12px;color:#999;">/100 Citabilita</span></div>';
                if (data.ai_visibility) html += '<div><span style="font-size:16px;font-weight:bold;">' + data.ai_visibility + '</span><br><span style="font-size:11px;color:#999;">Visibilita AI</span></div>';
                html += '</div>';
                if (data.issues && data.issues.length) {
                    html += '<div style="margin-bottom:8px;"><strong>Problemi AEO (' + data.issues.length + '):</strong><ul style="margin:4px 0 0 16px;padding:0;">';
                    data.issues.forEach(function(issue) {
                        var t = typeof issue === 'string' ? issue : (issue.description || issue.message || JSON.stringify(issue));
                        html += '<li style="margin-bottom:3px;color:#b91c1c;">' + t + '</li>';
                    });
                    html += '</ul></div>';
                }
                if (data.improvements && data.improvements.length) {
                    html += '<div><strong>Miglioramenti suggeriti:</strong><ul style="margin:4px 0 0 16px;padding:0;">';
                    data.improvements.forEach(function(s) {
                        var t = typeof s === 'string' ? s : (s.description || s.message || JSON.stringify(s));
                        html += '<li style="margin-bottom:3px;color:#059669;">' + t + '</li>';
                    });
                    html += '</ul></div>';
                }
                if (data.recommended_schema && data.recommended_schema.length) {
                    html += '<div style="margin-top:8px;"><strong>Schema.org consigliati:</strong> ';
                    data.recommended_schema.forEach(function(s) { html += '<span style="background:#e0e7ff;color:#3730a3;padding:1px 6px;border-radius:10px;font-size:11px;margin:2px;">' + s + '</span> '; });
                    html += '</div>';
                }
                if (data.sample_questions && data.sample_questions.length) {
                    html += '<div style="margin-top:8px;"><strong>Domande AI:</strong><ul style="margin:4px 0 0 16px;padding:0;">';
                    data.sample_questions.forEach(function(q) { html += '<li style="color:#6B21A8;">' + q + '</li>'; });
                    html += '</ul></div>';
                }
                return html || '<pre style="white-space:pre-wrap;font-size:12px;">' + JSON.stringify(data, null, 2) + '</pre>';
            }

            // Orchestrator summary
            if (data.pages_count !== undefined || section === 'orchestrator') {
                html += '<div style="display:flex;gap:15px;flex-wrap:wrap;margin-bottom:12px;">';
                if (data.avg_seo !== undefined) html += '<div style="text-align:center;padding:8px 12px;background:#f0f9ff;border-radius:8px;"><strong style="font-size:20px;">' + data.avg_seo + '</strong><br><small>' + SeoAeoOrchestra.t('SEO Medio') + '</small></div>';
                if (data.avg_aeo !== undefined) html += '<div style="text-align:center;padding:8px 12px;background:#f5f3ff;border-radius:8px;"><strong style="font-size:20px;">' + data.avg_aeo + '</strong><br><small>' + SeoAeoOrchestra.t('AEO Medio') + '</small></div>';
                if (data.pages_count !== undefined) html += '<div style="text-align:center;padding:8px 12px;background:#f0fdf4;border-radius:8px;"><strong style="font-size:20px;">' + data.pages_count + '</strong><br><small>' + SeoAeoOrchestra.t('Pagine') + '</small></div>';
                if (data.total_issues !== undefined) html += '<div style="text-align:center;padding:8px 12px;background:#fef2f2;border-radius:8px;"><strong style="font-size:20px;">' + data.total_issues + '</strong><br><small>' + SeoAeoOrchestra.t('Problemi') + '</small></div>';
                if (data.total_actions !== undefined) html += '<div style="text-align:center;padding:8px 12px;background:#fffbeb;border-radius:8px;"><strong style="font-size:20px;">' + data.total_actions + '</strong><br><small>' + SeoAeoOrchestra.t('Azioni') + '</small></div>';
                html += '</div>';
                if (data.pages && data.pages.length) {
                    html += '<div><strong>' + SeoAeoOrchestra.t('Dettaglio pagine:') + '</strong>';
                    data.pages.forEach(function(p) {
                        html += '<div style="padding:6px 0;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;">';
                        html += '<span>' + (p.title || p.url || '') + '</span>';
                        html += '<span>';
                        if (p.seo_score !== null && p.seo_score !== undefined) html += '<span style="margin-right:8px;">SEO: <strong>' + p.seo_score + '</strong></span>';
                        if (p.aeo_score !== null && p.aeo_score !== undefined) html += '<span>AEO: <strong>' + p.aeo_score + '</strong></span>';
                        html += '</span></div>';
                    });
                    html += '</div>';
                }
                return html || '<pre style="white-space:pre-wrap;font-size:12px;">' + JSON.stringify(data, null, 2) + '</pre>';
            }

            // Suggestions array
            if (Array.isArray(data)) {
                html += '<div>';
                data.forEach(function(s) {
                    if (typeof s === 'object') {
                        html += '<div style="padding:6px 0;border-bottom:1px solid #f0f0f0;">';
                        html += '<strong>' + (s.title || s.keyword || '') + '</strong>';
                        if (s.description) html += '<br><span style="font-size:12px;color:#666;">' + s.description + '</span>';
                        if (s.improvement_score) html += ' <span style="color:#059669;font-weight:bold;">+' + s.improvement_score + '%</span>';
                        html += '</div>';
                    } else {
                        html += '<div style="padding:3px 0;">' + s + '</div>';
                    }
                });
                html += '</div>';
                return html;
            }

            // Fallback: formatted JSON
            return '<pre style="white-space:pre-wrap;font-size:12px;">' + JSON.stringify(data, null, 2) + '</pre>';
        },

        // ── Publish content as WP post ──
        publishContent: function(title, content, status) {
            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_publish_content',
                nonce: seoAeoOrchestra.nonce,
                title: title,
                content: content,
                post_type: 'post',
                status: status || 'draft'
            }, function(response) {
                if (response && response.success) {
                    SeoAeoOrchestra.showNotice('Articolo creato! <a href="' + response.edit_url + '" target="_blank">Modifica &rarr;</a>', 'success');
                } else {
                    SeoAeoOrchestra.showNotice('Errore: ' + (response.error || 'Pubblicazione fallita'), 'error');
                }
            });
        },

        // ── Keyword Suggestions with expandable articles ──
        suggestKeywords: function(agentType, inputId, outputId) {
            var context = $('#' + inputId).val();
            if (!context || context.length < 3) {
                SeoAeoOrchestra.showNotice('Inserisci almeno 3 caratteri per ottenere suggerimenti.', 'error');
                return;
            }
            var $output = $('#' + outputId);
            $output.html('<div class="orchestra-loading"><span class="spinner is-active"></span> ' + SeoAeoOrchestra.t('Analisi AI in corso...') + ' <span class="token-badge">2 ' + SeoAeoOrchestra.t('crediti') + '</span></div>');
            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_suggest_keywords',
                nonce: seoAeoOrchestra.nonce,
                context: context,
                agent_type: agentType
            }, function(response) {
                if (response && response.suggestions && response.suggestions.length) {
                    var html = '<div class="suggestions-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:15px;margin-top:15px;">';
                    $.each(response.suggestions, function(i, s) {
                        var scoreColor = s.improvement_score >= 70 ? '#10B981' : (s.improvement_score >= 40 ? '#F59E0B' : '#EF4444');
                        var safeTitle = (s.title || s.keyword || '').replace(/'/g, "\\'");
                        var safeKw = (s.keyword || '').replace(/'/g, "\\'");
                        html += '<div class="expand-content-card">';
                        html += '<div class="expand-content-header" onclick="jQuery(this).next().slideToggle(200);">';
                        html += '<div><strong>' + (s.title || s.keyword) + '</strong><br><span style="font-size:12px;color:#666;">' + (s.description || '') + '</span></div>';
                        html += '<div style="text-align:right;"><strong style="color:' + scoreColor + ';font-size:20px;">+' + (s.improvement_score || 0) + '%</strong></div>';
                        html += '</div>';
                        html += '<div style="display:none;">';
                        html += '<div style="padding:12px 20px;display:flex;gap:8px;flex-wrap:wrap;">';
                        html += '<span style="background:#e0e7ff;color:#3730a3;padding:2px 8px;border-radius:12px;font-size:11px;">🔑 ' + (s.keyword || '-') + '</span>';
                        if (s.volume) html += '<span style="background:#ecfdf5;color:#065f46;padding:2px 8px;border-radius:12px;font-size:11px;">📊 ' + s.volume + '</span>';
                        if (s.difficulty) html += '<span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:12px;font-size:11px;">💪 ' + s.difficulty + '</span>';
                        if (s.ai_potential) html += '<span style="background:#f3e8ff;color:#7c3aed;padding:2px 8px;border-radius:12px;font-size:11px;">🤖 ' + s.ai_potential + '</span>';
                        if (s.city) html += '<span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:12px;font-size:11px;">📍 ' + s.city + '</span>';
                        html += '</div>';
                        // 3.26.1: tooltip esplicativi sui bottoni di generazione
                        var tipGen = SeoAeoOrchestra.t('Genera solo il testo dell\'articolo (~8 crediti). Veloce. Niente immagini, niente meta tags automatici.');
                        var tipCompl = SeoAeoOrchestra.t('Genera testo + immagine AI generata + meta tags ottimizzati (~25 crediti). Pronto da pubblicare.');
                        html += '<div class="expand-content-actions">';
                        html += '<button class="button button-primary button-small" title="' + tipGen + '" onclick="SeoAeoOrchestra.expandArticle(\'' + safeTitle + '\',\'' + safeKw + '\',\'' + agentType + '\',jQuery(this).closest(\'.expand-content-card\'));"><span class="dashicons dashicons-edit" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Genera Articolo') + '</button>';
                        if (SeoAeoOrchestra.isPremiumTier()) {
                            html += '<button class="button button-primary button-small" style="background:#7c3aed;border-color:#7c3aed;" title="' + tipCompl + '" onclick="SeoAeoOrchestra.generateCompleteArticle(\'' + safeTitle + '\',\'' + safeKw + '\',\'' + agentType + '\',jQuery(this).closest(\'.expand-content-card\'));"><span class="dashicons dashicons-star-filled" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Articolo Completo AI') + '</button>';
                        } else {
                            html += '<button class="button button-small orch-locked-btn" title="' + SeoAeoOrchestra.t('Aggiorna al piano Professional per sbloccare Articolo Completo AI (testo + immagine + meta)') + '" onclick="SeoAeoOrchestra.showUpgradePrompt();"><span class="dashicons dashicons-lock" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Articolo Completo AI') + '</button>';
                        }
                        html += '<button class="button button-small" onclick="SeoAeoOrchestra.publishContent(\'' + safeTitle + '\', \'<p>Articolo su: ' + safeKw + '</p>\', \'draft\')"><span class="dashicons dashicons-media-text" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Bozza Veloce') + '</button>';
                        html += '<span class="token-badge" title="' + tipGen + ' &#10; ' + tipCompl + '">~8 ' + SeoAeoOrchestra.t('per testo') + (SeoAeoOrchestra.isPremiumTier() ? ' · ~25 ' + SeoAeoOrchestra.t('per completo') : '') + ' ' + SeoAeoOrchestra.t('crediti') + '</span>';
                        html += '<div style="font-size:11px;color:#6b7280;margin-top:6px;width:100%;">💡 <strong>' + SeoAeoOrchestra.t('Genera Articolo') + '</strong>: ' + SeoAeoOrchestra.t('solo testo, veloce.') + (SeoAeoOrchestra.isPremiumTier() ? ' &nbsp; <strong style="color:#7c3aed;">' + SeoAeoOrchestra.t('Articolo Completo AI') + '</strong>: ' + SeoAeoOrchestra.t('testo + immagine AI + meta tags, pronto per pubblicazione.') : '') + '</div>';
                        html += '</div></div></div>';
                    });
                    html += '</div>';
                    $output.html(html);
                    var suggestRestore = {
                        fields: {},
                        outputs: {}
                    };
                    suggestRestore.fields['#' + inputId] = context;
                    suggestRestore.outputs['#' + outputId] = $output.html();
                    SeoAeoOrchestra.saveHistory(agentType, 'suggestions', context, response.suggestions, 2, suggestRestore);
                } else {
                    $output.html('<div class="orchestra-notice warning">Nessun suggerimento trovato. Prova con un termine diverso.</div>');
                }
            }).fail(function() {
                $output.html('<div class="orchestra-notice error">Errore nella richiesta. Verifica la connessione e riprova.</div>');
            });
        },

        // ── Expand suggestion into full article ──
        expandArticle: function(title, keyword, agentType, $card) {
            var $body = $card.find('.expand-content-actions').first();
            $body.before('<div class="expand-article-loading" style="padding:15px 20px;"><span class="spinner is-active"></span> Generazione articolo in corso... <span class="token-badge">~8 crediti</span></div>');
            var action = (agentType === 'aeo_content') ? 'seo_aeo_orchestra_aeo_content' : 'seo_aeo_orchestra_generate_content';
            var postData = {
                action: action,
                nonce: seoAeoOrchestra.nonce
            };
            if (agentType === 'aeo_content') {
                postData.topic = title;
                postData.keywords = keyword;
                postData['target_engines[]'] = ['google_ai', 'chatgpt', 'perplexity'];
                postData.include_schema = 'true';
                postData.include_faq = 'true';
            } else {
                postData.topic = title;
                postData.keywords = keyword;
                postData.length = 'medium';
                postData.include_faq = 'true';
            }
            $.post(seoAeoOrchestra.ajaxUrl, postData, function(response) {
                $card.find('.expand-article-loading').remove();
                if (response && (response.content || response.html)) {
                    var content = response.content || response.html || '';
                    var wordCount = (content.split(/\s+/) || []).length;
                    var html = '<div class="expand-content-body">';
                    html += '<div style="margin-bottom:10px;display:flex;gap:8px;align-items:center;">';
                    html += '<span class="token-badge">~' + wordCount + ' parole</span>';
                    html += '<span class="token-badge">8 crediti usati</span>';
                    html += '</div>';
                    html += '<div class="generated-content" style="max-height:400px;overflow:auto;border:1px solid #eee;padding:15px;border-radius:8px;background:#fff;">' + content + '</div>';
                    html += '<div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">';
                    html += '<button class="button button-primary" onclick="SeoAeoOrchestra.publishContent(\'' + title.replace(/'/g, "\\'") + '\', jQuery(this).closest(\'.expand-content-body\').find(\'.generated-content\').html(), \'draft\')"><span class="dashicons dashicons-upload" style="margin-top:3px;"></span> Pubblica come Bozza</button>';
                    html += '<button class="button" onclick="SeoAeoOrchestra.publishContent(\'' + title.replace(/'/g, "\\'") + '\', jQuery(this).closest(\'.expand-content-body\').find(\'.generated-content\').html(), \'publish\')"><span class="dashicons dashicons-yes" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Pubblica Subito') + '</button>';
                    html += '<button class="button" onclick="SeoAeoOrchestra.previewArticle(\'' + title.replace(/'/g, "\\'") + '\', jQuery(this).closest(\'.expand-content-body\').find(\'.generated-content\').html())"><span class="dashicons dashicons-visibility" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Anteprima') + '</button>';
                    html += '<button class="button" onclick="SeoAeoOrchestra.copyContent()"><span class="dashicons dashicons-admin-page" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Copia') + '</button>';
                    html += '<button class="button" onclick="SeoAeoOrchestra.openMediaUploader(jQuery(this).closest(\'.expand-content-body\'));"><span class="dashicons dashicons-admin-media" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Foto da Media') + '</button>';
                    if (SeoAeoOrchestra.isPremiumTier()) {
                        html += '<button class="button" onclick="SeoAeoOrchestra.openAiImageGenerator(jQuery(this).closest(\'.expand-content-body\'));"><span class="dashicons dashicons-format-image" style="margin-top:3px;"></span> Genera Foto AI — ' + (SeoAeoOrchestra.creditCosts.image_generation || 15) + ' crediti</button>';
                    } else {
                        html += '<button class="button orch-locked-btn" title="Disponibile dal piano Professional" onclick="SeoAeoOrchestra.showUpgradePrompt();"><span class="dashicons dashicons-lock" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Genera Foto AI') + '</button>';
                    }
                    html += '</div></div>';
                    $card.find('.expand-content-actions').before(html);
                    var articleRestore = {
                        view_html: '<div class="generated-content">' + content + '</div>',
                        meta: { topic: title, keyword: keyword || '', credits: 8 }
                    };
                    SeoAeoOrchestra.saveHistory(agentType, 'article', title, content, 8, articleRestore);
                }
            }).fail(function() {
                $card.find('.expand-article-loading').remove();
                SeoAeoOrchestra.showNotice('Errore nella generazione.', 'error');
            });
        },

        // ══════════ ORCHESTRATORE ══════════

        updateOrchestratorCount: function() {
            var count = $('.orch-page-check:checked').length;
            $('#orch-selected-count').text(SeoAeoOrchestra.t('{N} selezionate').replace('{N}', count));
            SeoAeoOrchestra.updateOrchestratorEstimate();
        },

        orchestrateStart: function(e) {
            if (e && typeof e.preventDefault === 'function') e.preventDefault();
            // 3.37.2 Module 14 — idempotency: prevent re-entry from a double-bound
            // trigger (the button used to have BOTH an inline onclick AND a jQuery
            // delegated handler, causing two parallel runs of orchestrateNext()
            // → duplicate log lines + duplicate AJAX + duplicate credit consumption).
            // The inline onclick has been removed in v3.37.2 but the guard stays as
            // defence-in-depth for any future double-bind path.
            if (SeoAeoOrchestra._orchestrateInFlight) {
                return;
            }
            SeoAeoOrchestra._orchestrateInFlight = true;

            var pages = [];
            $('.orch-page-check:checked').each(function() {
                pages.push({
                    post_id: $(this).val(),
                    url: $(this).data('url'),
                    title: $(this).data('title')
                });
            });

            if (pages.length === 0) {
                SeoAeoOrchestra.showNotice(SeoAeoOrchestra.t('Seleziona almeno una pagina da analizzare.'), 'error');
                SeoAeoOrchestra._orchestrateInFlight = false;
                return;
            }

            // Show progress, hide setup
            $('#orchestrator-setup').hide();
            $('#orchestrator-progress').show();
            $('#orchestrator-results').hide();

            SeoAeoOrchestra.orchestrateResults = [];
            SeoAeoOrchestra.orchestratePages = pages;
            SeoAeoOrchestra.orchestrateIndex = 0;
            SeoAeoOrchestra.orchestrateStartTime = Date.now();
            SeoAeoOrchestra.orchestrateCancelled = false;
            // 3.38.1 Task 3 — read ?is_free_first=1 once. Applied ONLY to the
            // first page of this run (idx === 0 inside orchestrateNext).
            try {
                var qs = new URLSearchParams(window.location.search);
                SeoAeoOrchestra.orchestrateIsFreeFirst = qs.get('is_free_first') === '1';
            } catch (e) {
                SeoAeoOrchestra.orchestrateIsFreeFirst = false;
            }
            SeoAeoOrchestra.orchestrateNext();
        },

        // 3.39.9 — ETA tick driver (restored after v3.39.8 lost the
        // v3.39.7 helper insertion). Updates progress bar + ETA every
        // 500ms based on the rolling median page duration (localStorage)
        // or 25s fallback. All helpers are properties on the
        // SeoAeoOrchestra object literal.
        _ORCH_DUR_KEY: 'seo_aeo_orch_page_durations_v1',
        _ORCH_DUR_MAX: 10,
        _ORCH_DEFAULT_ETA: 50,  // 3.39.10 — measured real p50 is ~50s, was 25s
        _orchEtaTimer: null,

        _loadOrchDurations: function() {
            try {
                var raw = localStorage.getItem(SeoAeoOrchestra._ORCH_DUR_KEY);
                if (!raw) return [];
                var parsed = JSON.parse(raw);
                return Array.isArray(parsed) ? parsed.filter(function(n){ return typeof n === 'number' && n > 0 && n < 600; }) : [];
            } catch (e) { return []; }
        },
        _recordOrchPageDuration: function(sec) {
            try {
                var list = SeoAeoOrchestra._loadOrchDurations();
                list.push(sec);
                while (list.length > SeoAeoOrchestra._ORCH_DUR_MAX) list.shift();
                localStorage.setItem(SeoAeoOrchestra._ORCH_DUR_KEY, JSON.stringify(list));
            } catch (e) {}
        },
        _getOrchMedianDuration: function() {
            var list = SeoAeoOrchestra._loadOrchDurations();
            if (!list.length) return SeoAeoOrchestra._ORCH_DEFAULT_ETA;
            var sorted = list.slice().sort(function(a, b){ return a - b; });
            var mid = Math.floor(sorted.length / 2);
            return sorted.length % 2 ? sorted[mid] : Math.round((sorted[mid - 1] + sorted[mid]) / 2);
        },

        _stopOrchEtaTick: function(success) {
            if (SeoAeoOrchestra._orchEtaTimer) {
                clearInterval(SeoAeoOrchestra._orchEtaTimer);
                SeoAeoOrchestra._orchEtaTimer = null;
            }
            if (success === true) {
                var $ = jQuery;
                $('#orch-progress-bar').css('width', '100%');
                $('#orch-progress-pct').text('100%');
            }
        },

        _startOrchEtaTick: function(pageIdx, totalPages) {
            if (typeof SeoAeoOrchestra._stopOrchEtaTick === 'function') SeoAeoOrchestra._stopOrchEtaTick();
            var $ = jQuery;
            var etaPerPage = SeoAeoOrchestra._getOrchMedianDuration();
            var T = SeoAeoOrchestra.t || function(s){return s;};

            function tick() {
                var now = Date.now();
                var elapsedThisPage = (now - (SeoAeoOrchestra._orchPageStartedAt || now)) / 1000;
                var pageFraction = Math.min(0.95, elapsedThisPage / Math.max(1, etaPerPage));
                var totalFraction = (pageIdx + pageFraction) / Math.max(1, totalPages);
                var pct = Math.min(95, Math.round(totalFraction * 100));
                $('#orch-progress-bar').css('width', pct + '%');
                $('#orch-progress-pct').text(pct + '%');
                // 3.39.10 — track signed remaining so the overage branch can
                // compute how many extra seconds we've already gone past the
                // estimate (instead of freezing on "Quasi finito...").
                var signedRemainingThisPage = etaPerPage - elapsedThisPage;
                var remainingPages = Math.max(0, totalPages - pageIdx - 1) * etaPerPage;
                var totalRemaining = Math.round(signedRemainingThisPage + remainingPages);
                var etaText;
                if (totalRemaining <= 0) {
                    // 3.39.10 — show overage so the clock keeps ticking visibly
                    // instead of freezing on "Quasi finito..." for 30+ seconds.
                    var extraSec = Math.ceil(-totalRemaining);
                    etaText = extraSec > 0
                        ? T('Quasi finito...') + ' (+' + extraSec + 's extra)'
                        : T('Quasi finito...');
                } else {
                    var min = Math.floor(totalRemaining / 60);
                    var sec = totalRemaining % 60;
                    etaText = '~ ' + (min > 0 ? (min + ' min ' + sec + ' sec') : (sec + ' sec ' + T('rimanenti')));
                }
                $('#orch-progress-eta').text(etaText);
            }
            tick();
            SeoAeoOrchestra._orchEtaTimer = setInterval(tick, 500);
        },

        orchestrateNext: function() {
            var pages = SeoAeoOrchestra.orchestratePages;
            var idx = SeoAeoOrchestra.orchestrateIndex;

            // Check if cancelled
            if (SeoAeoOrchestra.orchestrateCancelled) {
                $('#orch-progress-text').text(SeoAeoOrchestra.t('Analisi annullata dall\'utente.'));
                $('#orch-progress-eta').text(SeoAeoOrchestra.t('{N} pagine analizzate su {TOT}').replace('{N}', idx).replace('{TOT}', pages.length));
                $('#orch-cancel-analysis').hide();
                setTimeout(function() {
                    if (SeoAeoOrchestra.orchestrateResults.length > 0) {
                        SeoAeoOrchestra.orchestrateComplete();
                    } else {
                        $('#orchestrator-progress').hide();
                        $('#orchestrator-setup').show();
                        // 3.37.2 Module 14 — also release the latch on the
                        // cancel-with-zero-results branch.
                        SeoAeoOrchestra._orchestrateInFlight = false;
                    }
                }, 1500);
                return;
            }

            if (idx >= pages.length) {
                SeoAeoOrchestra.orchestrateComplete();
                return;
            }

            var page = pages[idx];
            $('#orch-progress-counter').text((idx + 1) + ' / ' + pages.length);
            $('#orch-progress-text').text(SeoAeoOrchestra.t('Analizzando:') + ' ' + page.title);

            // 3.39.7 — record page-start timestamp + start the 500ms tick.
            SeoAeoOrchestra._orchPageStartedAt = Date.now();
            if (typeof SeoAeoOrchestra._startOrchEtaTick === 'function') {
                SeoAeoOrchestra._startOrchEtaTick(idx, pages.length);
            }

            $('#orch-progress-log').prepend('<div>&#9654; Analizzando pagina ' + (idx + 1) + '/' + pages.length + ': ' + page.title + '...</div>');

            // 3.37.3 Module 12 — keep the XHR handle for cancel-abort; clear
            // generation_id from prior iteration.
            SeoAeoOrchestra._orchestratePendingGenId = null;
            // 3.38.1 Task 3 — pass is_free_first ONLY on the first page of the
            // run. Backend claims the free slot on first call; subsequent
            // pages in the same orchestrate session pay normal credits.
            var _isFree = (idx === 0 && SeoAeoOrchestra.orchestrateIsFreeFirst) ? 1 : 0;
            SeoAeoOrchestra._orchestrateXhr = $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_orchestrate_single',
                nonce: seoAeoOrchestra.nonce,
                url: page.url,
                title: page.title,
                post_id: page.post_id,
                keyword: '',
                is_free_first: _isFree
            }, function(result) {
                // Capture generation_id (if the backend returned one) so cancel
                // can call /refund-generation with it.
                if (result && result.generation_id) {
                    SeoAeoOrchestra._orchestratePendingGenId = result.generation_id;
                }
                // 3.39.7 — stop ETA tick + record this page's duration for
                // future median ETA. Capped to a sensible window so a single
                // outlier doesn't poison the rolling list.
                if (typeof SeoAeoOrchestra._stopOrchEtaTick === 'function') SeoAeoOrchestra._stopOrchEtaTick();
                if (SeoAeoOrchestra._orchPageStartedAt) {
                    var _dur = Math.round((Date.now() - SeoAeoOrchestra._orchPageStartedAt) / 1000);
                    if (_dur > 0 && _dur < 600 && typeof SeoAeoOrchestra._recordOrchPageDuration === 'function') SeoAeoOrchestra._recordOrchPageDuration(_dur);
                }
                if (result && !result.error) {
                    SeoAeoOrchestra.orchestrateResults.push(result);
                    var seo = result.seo_score !== null ? result.seo_score : '?';
                    var aeo = result.aeo_score !== null ? result.aeo_score : '?';
                    $('#orch-progress-log').prepend('<div style="color:#10B981;">&#10003; ' + page.title + ' - SEO: ' + seo + ' | AEO: ' + aeo + '</div>');
                } else {
                    $('#orch-progress-log').prepend('<div style="color:#EF4444;">&#10007; ' + page.title + ' - ' + SeoAeoOrchestra.t('Errore: ') + (result.error || SeoAeoOrchestra.t('sconosciuto')) + '</div>');
                    SeoAeoOrchestra.orchestrateResults.push({
                        url: page.url, title: page.title, post_id: page.post_id,
                        seo_score: null, aeo_score: null, actions: [], error: true
                    });
                }
                SeoAeoOrchestra.orchestrateIndex++;
                // Small delay to not overwhelm server
                setTimeout(function() { SeoAeoOrchestra.orchestrateNext(); }, 500);
            }).fail(function() {
                if (typeof SeoAeoOrchestra._stopOrchEtaTick === 'function') SeoAeoOrchestra._stopOrchEtaTick();
                $('#orch-progress-log').prepend('<div style="color:#EF4444;">&#10007; ' + page.title + ' - ' + SeoAeoOrchestra.t('Connessione fallita') + '</div>');
                SeoAeoOrchestra.orchestrateResults.push({
                    url: page.url, title: page.title, post_id: page.post_id,
                    seo_score: null, aeo_score: null, actions: [], error: true
                });
                SeoAeoOrchestra.orchestrateIndex++;
                setTimeout(function() { SeoAeoOrchestra.orchestrateNext(); }, 1000);
            });
        },

        // 3.39.8 P1 — single LLM-failure banner instead of cluttering the
        // recommendation list with hardcoded fallback actions per page.
        renderLlmFailedBanner: function(failedCount, totalCount) {
            var $host = jQuery('#orch-action-plan');
            if (!$host.length) return;
            jQuery('#orch-llm-failed-banner').remove();
            if (!failedCount || failedCount <= 0) return;
            var T = SeoAeoOrchestra.t || function(s){return s;};
            var label = failedCount === 1
                ? T('Analisi AI temporaneamente non disponibile per 1 pagina')
                : (T('Analisi AI temporaneamente non disponibile per') + ' ' + failedCount + ' ' + T('pagine'));
            var pct = totalCount > 0 ? Math.round((failedCount / totalCount) * 100) : 0;
            var sub = T("Riesegui l\u0027analisi tra qualche minuto. Le altre pagine sono state analizzate correttamente.");
            var html = '<div id="orch-llm-failed-banner" class="orch-llm-failed-banner" style="margin:0 0 12px;padding:10px 14px;background:#fffbeb;border-left:3px solid #f59e0b;border-radius:4px;font-size:13px;color:#78350f;">' +
                '<strong>\u26a0 ' + label + (pct > 0 ? ' (' + pct + '%)' : '') + '</strong><br>' +
                '<span style="font-size:12px;color:#92400e;">' + sub + '</span>' +
                '</div>';
            $host.before(html);
        },

        // 3.39.8 Bug A — Piano d'Azione renderer extracted so the
        // restoreFromHistory path uses identical HTML scaffolding as fresh.
        // Idempotent: clear-and-rebuild.
        renderActionPlan: function(actions) {
            var $list = jQuery('#orch-action-plan');
            if (!$list.length) return;
            actions = Array.isArray(actions) ? actions : [];
            if (actions.length === 0) {
                $list.html('<p style="color:#10B981;font-weight:bold;">' + SeoAeoOrchestra.t('Nessuna azione critica necessaria. Il sito e ben ottimizzato!') + '</p>');
                return;
            }
            var planHtml = '';
            actions.forEach(function(action, idx) {
                var detailDesc = SeoAeoOrchestra.getActionDetailDescription(action);
                planHtml += '<div class="orch-action-item">';
                planHtml += '<span class="priority-badge priority-' + action.priority + '">' + SeoAeoOrchestra.t(action.priority) + '</span>';
                planHtml += '<div style="flex:1;">';
                planHtml += '<strong>' + (action.label || '') + '</strong>';
                planHtml += '<br><small style="color:#666;">' + (action.page_title || '') + '</small>';
                planHtml += '<div style="margin-top:6px;padding:8px 10px;background:#f8fafc;border-left:3px solid #3b82f6;border-radius:0 6px 6px 0;font-size:12px;color:#334155;">' + detailDesc + '</div>';
                planHtml += '</div>';
                planHtml += '<button type="button" class="button orch-action-btn orch-preview-btn" ';
                planHtml += 'data-agent="' + (action.agent || '') + '" ';
                planHtml += 'data-action-data=\'' + JSON.stringify(action.data || {}) + '\' ';
                planHtml += 'data-idx="' + idx + '" ';
                planHtml += 'title="' + SeoAeoOrchestra.t('Anteprima delle modifiche prima di applicarle') + '">';
                planHtml += '👁 ' + SeoAeoOrchestra.t('Mostra modifiche') + '</button> ';
                planHtml += '<button type="button" class="button button-primary orch-action-btn orch-execute-btn" ';
                planHtml += 'data-agent="' + (action.agent || '') + '" ';
                planHtml += 'data-action-data=\'' + JSON.stringify(action.data || {}) + '\' ';
                planHtml += 'data-idx="' + idx + '">';
                planHtml += '<span class="dashicons dashicons-controls-play"></span> ' + SeoAeoOrchestra.t('Esegui') + '</button>';
                planHtml += '<div id="orch-action-result-' + idx + '" class="orch-action-result" style="display:none;"></div>';
                planHtml += '</div>';
            });
            $list.html(planHtml);
        },

        orchestrateComplete: function() {
            // 3.37.2 Module 14 — release the in-flight latch so the user can
            // start a new analysis.
            SeoAeoOrchestra._orchestrateInFlight = false;
            // 3.39.7 — kill the ETA tick before jumping the bar to 100%.
            if (typeof SeoAeoOrchestra._stopOrchEtaTick === 'function') SeoAeoOrchestra._stopOrchEtaTick();
            $('#orch-progress-bar').css('width', '100%');
            $('#orch-progress-pct').text('100%');
            var elapsed = Math.round((Date.now() - SeoAeoOrchestra.orchestrateStartTime) / 1000);
            var min = Math.floor(elapsed / 60);
            var sec = elapsed % 60;
            var timeText = min > 0 ? min + ' min ' + sec + ' sec' : sec + ' sec';
            $('#orch-progress-text').text(SeoAeoOrchestra.t('Analisi completata! Generazione report...'));
            $('#orch-progress-eta').text(SeoAeoOrchestra.t('Completato in') + ' ' + timeText);
            $('#orch-progress-counter').text(SeoAeoOrchestra.orchestratePages.length + ' / ' + SeoAeoOrchestra.orchestratePages.length);

            var results = SeoAeoOrchestra.orchestrateResults;
            var totalSeo = 0, totalAeo = 0, countSeo = 0, countAeo = 0;
            var totalSeoIssues = 0, totalAeoIssues = 0, allActions = [];
            var allSeoIssues = [], allAeoIssues = [];
            // 3.39.8 P1 — count pages where the LLM permanently failed so we
            // can show ONE consolidated banner instead of cluttering the
            // recommendation list with fake "Analisi LLM non riuscita" items.
            var llmFailedPages = 0;

            results.forEach(function(r) {
                if (r.seo_score !== null && r.seo_score !== undefined) { totalSeo += r.seo_score; countSeo++; }
                if (r.aeo_score !== null && r.aeo_score !== undefined) { totalAeo += r.aeo_score; countAeo++; }
                if ((r.seo_detail && r.seo_detail._llm_failed) || (r.aeo_detail && r.aeo_detail._llm_failed)) llmFailedPages++;
                var sIssues = (r.seo_issues || 0);
                var aIssues = (r.aeo_issues || 0);
                totalSeoIssues += sIssues;
                totalAeoIssues += aIssues;
                // Collect detailed issues from seo_detail / aeo_detail
                if (r.seo_detail && r.seo_detail.issues) {
                    r.seo_detail.issues.forEach(function(iss) {
                        var t = typeof iss === 'string' ? iss : (iss.description || iss.message || JSON.stringify(iss));
                        allSeoIssues.push({page: r.title, issue: t});
                    });
                }
                if (r.aeo_detail && r.aeo_detail.issues) {
                    r.aeo_detail.issues.forEach(function(iss) {
                        var t = typeof iss === 'string' ? iss : (iss.description || iss.message || JSON.stringify(iss));
                        allAeoIssues.push({page: r.title, issue: t});
                    });
                }
                if (r.actions) {
                    r.actions.forEach(function(a) {
                        a.page_title = r.title;
                        a.page_url = r.url;
                        allActions.push(a);
                    });
                }
            });

            var totalIssues = totalSeoIssues + totalAeoIssues;
            var pagesCount = SeoAeoOrchestra.orchestratePages.length;

            // Sort actions by priority
            var priorityOrder = { 'critica': 0, 'alta': 1, 'media': 2, 'bassa': 3 };
            allActions.sort(function(a, b) {
                return (priorityOrder[a.priority] || 99) - (priorityOrder[b.priority] || 99);
            });

            // Sort pages by worst score first
            results.sort(function(a, b) {
                var scoreA = (a.seo_score || 0) + (a.aeo_score || 0);
                var scoreB = (b.seo_score || 0) + (b.aeo_score || 0);
                return scoreA - scoreB;
            });

            // Update global stats
            var avgSeo = countSeo > 0 ? Math.round(totalSeo / countSeo) : '--';
            var avgAeo = countAeo > 0 ? Math.round(totalAeo / countAeo) : '--';
            $('#orch-avg-seo').text(avgSeo);
            $('#orch-avg-aeo').text(avgAeo);
            $('#orch-pages-analyzed').text(pagesCount);
            $('#orch-seo-issues-count').text(totalSeoIssues);
            $('#orch-aeo-issues-count').text(totalAeoIssues);
            $('#orch-total-actions').text(allActions.length);

            // Store issues for clickable cards
            SeoAeoOrchestra._allSeoIssues = allSeoIssues;
            SeoAeoOrchestra._allAeoIssues = allAeoIssues;
            SeoAeoOrchestra._allActions = allActions;
            SeoAeoOrchestra._results = results;
            SeoAeoOrchestra._llmFailedPages = llmFailedPages;
            SeoAeoOrchestra.renderLlmFailedBanner(llmFailedPages, results.length);

            // 3.39.8 Bug A — render via shared helper. Fresh-analysis path AND
            // restoreFromHistory orchestrator branch both call the same code,
            // so the cards are always built from data (state.allActions),
            // never from stale outputs-map markup.
            SeoAeoOrchestra.renderActionPlan(allActions);

            // Build per-page results with full detail
            var pageHtml = '';
            results.forEach(function(r) {
                var seoClass = (r.seo_score !== null && r.seo_score !== undefined) ? (r.seo_score >= 70 ? 'score-good' : r.seo_score >= 40 ? 'score-medium' : 'score-bad') : '';
                var aeoClass = (r.aeo_score !== null && r.aeo_score !== undefined) ? (r.aeo_score >= 60 ? 'score-good' : r.aeo_score >= 30 ? 'score-medium' : 'score-bad') : '';
                pageHtml += '<div class="orch-page-result">';
                pageHtml += '<div style="display:flex;justify-content:space-between;align-items:center;cursor:pointer;" onclick="jQuery(this).siblings(\'.orch-page-detail\').slideToggle(200);">';
                pageHtml += '<strong>' + r.title + '</strong>';
                pageHtml += ' <a href="' + r.url + '" target="_blank" style="font-size:12px;color:#999;" onclick="event.stopPropagation();">&#8599;</a>';
                pageHtml += '<span style="font-size:11px;color:#999;">' + SeoAeoOrchestra.t('Clicca per dettagli') + '</span>';
                pageHtml += '</div>';
                pageHtml += '<div class="scores">';
                if (r.seo_score !== null && r.seo_score !== undefined) pageHtml += '<span class="score-pill ' + seoClass + '">SEO ' + r.seo_score + '</span>';
                if (r.aeo_score !== null && r.aeo_score !== undefined) pageHtml += '<span class="score-pill ' + aeoClass + '">AEO ' + r.aeo_score + '</span>';
                if (r.has_meta) pageHtml += '<span class="score-pill score-good">' + SeoAeoOrchestra.t('Meta OK') + '</span>';
                else pageHtml += '<span class="score-pill score-bad">' + SeoAeoOrchestra.t('Meta mancanti') + '</span>';
                if (r.ai_visibility) pageHtml += '<span class="score-pill">AI: ' + r.ai_visibility + '</span>';
                pageHtml += '</div>';
                if (r.actions && r.actions.length > 0) {
                    pageHtml += '<div style="font-size:12px;color:#666;margin-top:5px;">';
                    r.actions.forEach(function(a) { pageHtml += '&#8226; ' + a.label + ' (' + a.priority + ')<br>'; });
                    pageHtml += '</div>';
                }
                // Expandable detail panel
                pageHtml += '<div class="orch-page-detail" style="display:none;margin-top:10px;padding:10px;background:#f8fafc;border-radius:8px;">';
                if (r.seo_detail) {
                    pageHtml += '<h4 style="margin:0 0 6px;">' + SeoAeoOrchestra.t('Dettaglio SEO') + '</h4>';
                    if (r.seo_detail.issues && r.seo_detail.issues.length) {
                        pageHtml += '<div style="margin-bottom:8px;"><strong style="color:#b91c1c;">' + SeoAeoOrchestra.t('Problemi SEO') + ' (' + r.seo_detail.issues.length + '):</strong><ul style="margin:4px 0 0 16px;padding:0;">';
                        r.seo_detail.issues.forEach(function(iss) {
                            var t = typeof iss === 'string' ? iss : (iss.description || iss.message || JSON.stringify(iss));
                            pageHtml += '<li style="margin-bottom:2px;">' + t + '</li>';
                        });
                        pageHtml += '</ul></div>';
                    }
                    if (r.seo_detail.suggestions && r.seo_detail.suggestions.length) {
                        pageHtml += '<div><strong style="color:#059669;">' + SeoAeoOrchestra.t('Suggerimenti:') + '</strong><ul style="margin:4px 0 0 16px;padding:0;">';
                        r.seo_detail.suggestions.forEach(function(s) {
                            var t = typeof s === 'string' ? s : (s.description || s.message || JSON.stringify(s));
                            pageHtml += '<li style="margin-bottom:2px;">' + t + '</li>';
                        });
                        pageHtml += '</ul></div>';
                    }
                }
                if (r.aeo_detail) {
                    pageHtml += '<h4 style="margin:12px 0 6px;">' + SeoAeoOrchestra.t('Dettaglio AEO') + '</h4>';
                    if (r.aeo_detail.issues && r.aeo_detail.issues.length) {
                        pageHtml += '<div style="margin-bottom:8px;"><strong style="color:#b91c1c;">' + SeoAeoOrchestra.t('Problemi AEO') + ' (' + r.aeo_detail.issues.length + '):</strong><ul style="margin:4px 0 0 16px;padding:0;">';
                        r.aeo_detail.issues.forEach(function(iss) {
                            var t = typeof iss === 'string' ? iss : (iss.description || iss.message || JSON.stringify(iss));
                            pageHtml += '<li style="margin-bottom:2px;">' + t + '</li>';
                        });
                        pageHtml += '</ul></div>';
                    }
                    if (r.aeo_detail.improvements && r.aeo_detail.improvements.length) {
                        pageHtml += '<div><strong style="color:#059669;">' + SeoAeoOrchestra.t('Miglioramenti AEO:') + '</strong><ul style="margin:4px 0 0 16px;padding:0;">';
                        r.aeo_detail.improvements.forEach(function(s) {
                            var t = typeof s === 'string' ? s : (s.description || s.message || JSON.stringify(s));
                            pageHtml += '<li style="margin-bottom:2px;">' + t + '</li>';
                        });
                        pageHtml += '</ul></div>';
                    }
                }
                pageHtml += '</div>';
                pageHtml += '</div>';
            });
            $('#orch-page-results').html(pageHtml);

            // Save orchestrator results to history
            var historyData = {
                avg_seo: avgSeo, avg_aeo: avgAeo,
                pages_count: pagesCount,
                total_issues: totalIssues, total_actions: allActions.length,
                pages: results.map(function(r) {
                    return {title: r.title, url: r.url, seo_score: r.seo_score, aeo_score: r.aeo_score};
                })
            };
            // 3.38.9 — full state snapshot so the new restore path can rehydrate
            // problem cards + scalar counters, not just paint a few innerHTMLs.
            // The legacy selectors stay for backward compat with old history
            // entries; new fields drive the v3.38.9+ render in restoreFromHistory.
            var orchRestore = {
                outputs: {
                    '#orch-page-results': $('#orch-page-results').html(),
                    '#orch-results-summary': $('#orch-results-summary').html(),
                    '#orch-action-plan': $('#orch-action-plan').html()
                },
                state: {
                    allSeoIssues:  allSeoIssues,
                    allAeoIssues:  allAeoIssues,
                    allActions:    allActions,
                    results:       results,
                    pages:         SeoAeoOrchestra.orchestratePages,
                    counters: {
                        avgSeo:        avgSeo,
                        avgAeo:        avgAeo,
                        pagesCount:    pagesCount,
                        totalSeoIssues: totalSeoIssues,
                        totalAeoIssues: totalAeoIssues,
                        totalActions:  allActions.length
                    }
                }
            };
            SeoAeoOrchestra.saveHistory('orchestrator', 'analysis', SeoAeoOrchestra.t('{N} pagine analizzate').replace('{N}', pagesCount), historyData, pagesCount * 5, orchRestore);
            // 3.6.0 — Live update Health Score box senza refresh pagina
            if (typeof SeoAeoOrchestra.refreshHealthScoreHero === 'function') {
                setTimeout(SeoAeoOrchestra.refreshHealthScoreHero, 800);
            }

            // Show results
            setTimeout(function() {
                $('#orchestrator-progress').hide();
                $('#orchestrator-results').show();
                SeoAeoOrchestra.showNotice(SeoAeoOrchestra.t('Analisi completata! {N} pagine analizzate, {A} azioni suggerite.').replace('{N}', pagesCount).replace('{A}', allActions.length), 'success');
            }, 1000);
        },

        // 3.6.0 — Live refresh Health Score hero box
        refreshHealthScoreHero: function() {
            if (typeof seoAeoOrchestra === 'undefined') return;
            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_get_health_score',
                nonce: seoAeoOrchestra.nonce
            }, function(res) {
                if (!res || res.error) return;
                var $box = $('.orch4-health').first();
                if (!$box.length) return;

                if (res.score_current === null || !res.has_history) {
                    // Ancora nessuna analisi: mantiene placeholder
                    return;
                }

                var sc = res.score_current;
                var deltaTxt = '';
                if (res.score_delta !== null && res.score_delta !== 0) {
                    var arrow = res.score_delta > 0 ? '▲' : '▼';
                    var deltaClass = res.score_delta > 0 ? 'orch4-delta-up' : 'orch4-delta-down';
                    var deltaSign = res.score_delta > 0 ? '+' : '';
                    deltaTxt = '<span class="orch4-delta ' + deltaClass + '">' + deltaSign + res.score_delta + ' ' + arrow + '</span>';
                }
                var foot = '';
                if (res.days_since_last === 0) foot = 'Analisi oggi';
                else if (res.days_since_last === 1) foot = 'Analisi ieri';
                else if (res.days_since_last !== null) foot = 'Ultima analisi ' + res.days_since_last + ' giorni fa';

                // Costruisci HTML completo del box (sostituisce sia stato empty che filled)
                var html =
                    '<div class="orch4-health-top">' +
                      '<span class="orch4-health-label">Health Score</span>' +
                      deltaTxt +
                    '</div>' +
                    '<div class="orch4-health-number">' + sc + '<span class="orch4-health-over">/100</span></div>' +
                    '<div class="orch4-health-bar">' +
                      '<div class="orch4-health-fill" style="width: ' + sc + '%;"></div>' +
                    '</div>' +
                    '<div class="orch4-health-foot">' + foot + '</div>';
                $box.removeClass('orch4-health-empty')
                    .attr('data-score-class', res.score_class)
                    .html(html);
            });
        },

        // Generate detailed description for orchestrator actions
        // 3.39.0 — context-aware fallback when no action matches an issue.
        // Returns an Italian Italian copy snippet pointing the user to the
        // specific Orchestra page or workflow where the issue is fixable.
        // Never returns the generic "rivedi manualmente" text.
        contextHintForIssue: function(issueText) {
            var T = SeoAeoOrchestra.t || function(s){return s;};
            var t = String(issueText || '').toLowerCase();
            var rules = [
                {rx: /schema|markup|json[\s\-]?ld|structured\s+data|dati\s+strutturati|rich\s+result/i,
                 txt: T('Vai a SEO+AEO Output Nativo → Schema → abilita la generazione automatica del markup JSON-LD per questa pagina.')},
                {rx: /\bfaq\b|domand[ae]\s+frequent|domanda[\-\s]+risposta|sezione\s+faq|q&a/i,
                 txt: T('Vai a Contenuti AEO → genera sezione FAQ con domande tipiche per questo argomento (Brand Voice consigliato per tono coerente).')},
                {rx: /meta\s+(title|description)|meta[\-\s]?tag|description\s+(mancante|corta|generica)/i,
                 txt: T('Vai a Meta Tags AI → genera Meta Title + Meta Description ottimizzati per la keyword di questa pagina, oppure abilita Override Mode in SEO+AEO Output.')},
                {rx: /\bh1\b|\bh2\b|\bh3\b|intestazion|heading|gerarchia\s+titoli/i,
                 txt: T('Apri la pagina nell\'editor WordPress e correggi la gerarchia titoli (un solo H1, H2 per sezioni principali, H3 per sotto-sezioni).')},
                {rx: /internal\s+link|link\s+intern|collegamenti?\s+intern/i,
                 txt: T('Vai a Contenuti AI → Suggerimenti, oppure usa il Piano d\'Azione per ottenere proposte di internal link contestuali.')},
                {rx: /citab|cita(zione|bilita)|autorit|e[\-\s]?e[\-\s]?a[\-\s]?t|expertise|fonte/i,
                 txt: T('Aggiungi segnali E-E-A-T: autore con bio, data di pubblicazione, fonti verificabili. Vai a Contenuti AEO per generare passaggi con citazioni.')},
                {rx: /featured\s+snippet|snippet|risposta\s+(diretta|breve)/i,
                 txt: T('Vai a Contenuti AEO → riformula l\'apertura della pagina come risposta diretta + breve (formato definizione/lista) per Featured Snippet e AI Overviews.')},
                {rx: /keyword|parol[ae]\s+chiave|densita/i,
                 txt: T('Vai a Keyword Research per identificare le keyword secondarie (LSI) piu\' rilevanti, poi aggiorna Meta Tags AI.')},
                {rx: /brand\s+voice|tono|voce|style/i,
                 txt: T('Configura Brand Voice → analizza 10-20 articoli del sito per estrarre il tuo profilo di tono e usarlo come system message in tutte le generazioni AI.')},
                {rx: /profilo\s+business|business\s+profile|chi\s+sei|territor|prodott|servizi|industry/i,
                 txt: T('Apri Setup Guidato → Profilo Business per compilare i campi business core (nome, industry, value proposition, territori). Migliora le analisi future.')}
            ];
            for (var i = 0; i < rules.length; i++) {
                if (rules[i].rx.test(t)) return '<em>' + rules[i].txt + '</em>';
            }
            return '<em>' + T('Apri il Piano d\'Azione (sopra) per vedere azioni eseguibili o configura Brand Voice + Profilo Business per analisi piu\' mirate.') + '</em>';
        },

        getActionDetailDescription: function(action) {
            var T = SeoAeoOrchestra.t;
            var label = '<strong>' + T('Cosa fara:') + '</strong> ';
            switch (action.agent) {
                case 'meta_tags':
                    return label + T('Generera automaticamente Meta Title e Meta Description ottimizzati per la keyword "{KW}". Questi meta tag verranno salvati direttamente nel post WordPress per migliorare il CTR nei risultati di ricerca.').replace('{KW}', action.data.keyword || '');
                case 'seo_analysis':
                    return label + T('Rianalizzera la pagina e identifichera problemi specifici come: struttura heading H1-H6, densita keyword, internal linking, alt text immagini, lunghezza contenuto. Fornira suggerimenti attuabili per migliorare il punteggio SEO.');
                case 'aeo_content':
                    return label + T('Generera contenuto ottimizzato per le risposte AI (Google AI Overviews, ChatGPT, Perplexity). Riscrivera i paragrafi in formato domanda-risposta, aggiungera Schema.org FAQ e Article markup, migliorera la citabilita del contenuto.');
                case 'content_generator':
                    return label + T('Rigenerera il contenuto della pagina con ottimizzazione SEO + AEO. Il nuovo articolo avra: struttura H2/H3 corretta, keyword integrate naturalmente, sezione FAQ, formato ottimizzato per Featured Snippet e risposte AI.');
                default:
                    return action.description || '';
            }
        },

        // Toggle detail panel for clickable stat cards
        toggleIssueDetail: function(type) {
            var $panel = $('#orch-detail-' + type);
            if ($panel.is(':visible')) {
                $panel.slideUp(200);
                return;
            }
            // Hide all other panels
            $('.orch-stat-detail').slideUp(200);

            var html = '';
            // 3.38.8 Task 3 — inline "Come risolvere" + executors under each
            // unique problem. Duplicate issues across pages collapse to one card
            // with an N-pages badge; "Mostra pagine" expands the affected list.
            function escHtml(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){
                    return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
                });
            }
            function buildProblemCards(issues, kind) {
                if (!issues || !issues.length) return '<p style="color:#10B981;font-weight:600;padding:8px;">' + SeoAeoOrchestra.t('Nessun problema rilevato!') + '</p>';
                // Group by issue text.
                var groups = {};
                issues.forEach(function(it) {
                    var key = it.issue || '(senza descrizione)';
                    if (!groups[key]) groups[key] = { issue: key, pages: [] };
                    groups[key].pages.push(it.page || '');
                });
                var allActions = SeoAeoOrchestra._allActions || [];
                var out = '<div class="orch-problem-cards">';
                Object.keys(groups).forEach(function(issueText, idx) {
                    var g = groups[issueText];
                    // 3.39.0 — prefer exact issue_ref match (per-issue actions emitted
                    // by build_action_from_issue), then fall back to page-level match.
                    var matchAction = null;
                    for (var i = 0; i < allActions.length; i++) {
                        var aa = allActions[i];
                        if (aa.issue_ref && aa.issue_ref === g.issue) { matchAction = aa; break; }
                    }
                    if (!matchAction) {
                        for (var j = 0; j < allActions.length; j++) {
                            if (g.pages.indexOf(allActions[j].page_title) !== -1) {
                                matchAction = allActions[j];
                                break;
                            }
                        }
                    }
                    var icon = (kind === 'aeo') ? '🟡' : '🔴';
                    var cardId = 'orch-prob-' + kind + '-' + idx;
                    out += '<div class="orch-problem-card" data-card="' + cardId + '">';
                    out += '<div class="orch-problem-head">';
                    out += '<span class="orch-problem-icon">' + icon + '</span>';
                    out += '<span class="orch-problem-title"><strong>' + escHtml(g.issue) + '</strong>';
                    if (g.pages.length > 1) {
                        out += ' <span class="orch-problem-count">' + SeoAeoOrchestra.t('su {N} pagine').replace('{N}', g.pages.length) + '</span>';
                    } else if (g.pages.length === 1) {
                        out += ' <span class="orch-problem-count">(' + escHtml(g.pages[0]) + ')</span>';
                    }
                    out += '</span>';
                    out += '</div>';
                    // 3.39.0 — Solution block. Prefer the rich Italian copy emitted
                    // by build_action_from_issue (matchAction.description), fall back
                    // to legacy by-agent text, then to a context-aware hint mapped on
                    // issue keywords. Generic "rivedi manualmente" is NEVER shown.
                    var desc;
                    if (matchAction && matchAction.description && matchAction.description.length > 0) {
                        desc = '<strong>' + SeoAeoOrchestra.t('Cosa fara:') + '</strong> ' + matchAction.description;
                    } else if (matchAction) {
                        desc = SeoAeoOrchestra.getActionDetailDescription(matchAction);
                    } else {
                        desc = SeoAeoOrchestra.contextHintForIssue(g.issue);
                    }
                    out += '<div class="orch-problem-solution"><strong>💡 ' + SeoAeoOrchestra.t('Come risolvere') + ':</strong> ' + desc + '</div>';
                    // Actions — show Esegui only when the action is auto-executable.
                    out += '<div class="orch-problem-actions">';
                    var canAutoExec = matchAction && (matchAction.auto_executable !== false) && matchAction.agent && matchAction.agent !== 'manual_review';
                    if (canAutoExec) {
                        var credits = matchAction.estimated_credits || '';
                        var creditsLabel = credits ? ' (' + credits + ' cr)' : '';
                        // 3.39.6 — preview button precedes Esegui on Problemi cards too.
                        out += '<button type="button" class="button orch-preview-btn orch-problem-preview" ';
                        out += 'data-agent="' + escHtml(matchAction.agent) + '" ';
                        out += 'data-action-data=\'' + JSON.stringify(matchAction.data || {}).replace(/\'/g, '&apos;') + '\' ';
                        out += 'title="' + SeoAeoOrchestra.t('Anteprima delle modifiche prima di applicarle') + '">';
                        out += '👁 ' + SeoAeoOrchestra.t('Mostra modifiche') + '</button> ';
                        out += '<button type="button" class="button button-primary orch-execute-btn orch-problem-exec" ';
                        out += 'data-agent="' + escHtml(matchAction.agent) + '" ';
                        out += 'data-action-data=\'' + JSON.stringify(matchAction.data || {}).replace(/\'/g, '&apos;') + '\'>';
                        out += '⚡ ' + SeoAeoOrchestra.t('Esegui automaticamente') + creditsLabel + '</button>';
                    }
                    out += '<button type="button" class="button orch-problem-toggle-pages" data-target="' + cardId + '-pages">';
                    out += '👁 ' + SeoAeoOrchestra.t('Mostra pagine') + ' (' + g.pages.length + ')';
                    out += '</button>';
                    out += '</div>';
                    out += '<ul class="orch-problem-pages" id="' + cardId + '-pages" hidden>';
                    g.pages.forEach(function(p) { out += '<li>' + escHtml(p) + '</li>'; });
                    out += '</ul>';
                    out += '</div>';
                });
                out += '</div>';
                return out;
            }
            if (type === 'seo-issues' && SeoAeoOrchestra._allSeoIssues) {
                html = '<h4>' + SeoAeoOrchestra.t('Problemi SEO') + ' (' + SeoAeoOrchestra._allSeoIssues.length + ')</h4>';
                html += buildProblemCards(SeoAeoOrchestra._allSeoIssues, 'seo');
            } else if (type === 'aeo-issues' && SeoAeoOrchestra._allAeoIssues) {
                html = '<h4>' + SeoAeoOrchestra.t('Problemi AEO') + ' (' + SeoAeoOrchestra._allAeoIssues.length + ')</h4>';
                html += buildProblemCards(SeoAeoOrchestra._allAeoIssues, 'aeo');
            } else if (type === 'actions' && SeoAeoOrchestra._allActions) {
                html = '<h4>' + SeoAeoOrchestra.t('Azioni Suggerite') + ' (' + SeoAeoOrchestra._allActions.length + ')</h4>';
                SeoAeoOrchestra._allActions.forEach(function(a) {
                    html += '<div style="padding:4px 0;border-bottom:1px solid #f0f0f0;"><span class="priority-badge priority-' + a.priority + '" style="font-size:10px;">' + SeoAeoOrchestra.t(a.priority) + '</span> ';
                    html += '<strong>' + a.label + '</strong> - ' + a.page_title + '</div>';
                });
            }
            $panel.html(html).slideDown(200);
        },

        // ── Generate Complete Article (content + image + meta in one click) ──
        generateCompleteArticle: function(title, keyword, agentType, $card) {
            if (!SeoAeoOrchestra.isPremiumTier()) {
                SeoAeoOrchestra.showNotice('Funzione disponibile solo per i piani Professional, Team, Pro e B2B.', 'error');
                return;
            }
            var $body = $card.find('.expand-content-actions').first();
            $body.before('<div class="expand-article-loading" style="padding:15px 20px;"><span class="spinner is-active"></span> Generazione articolo completo in corso (testo + immagine AI + meta tags)... <span class="token-badge">~15 crediti</span><br><small style="color:#666;margin-top:5px;display:block;">L\'immagine AI potrebbe richiedere fino a 60 secondi.</small></div>');

            $.ajax({
                url: seoAeoOrchestra.ajaxUrl,
                type: 'POST',
                timeout: 180000,
                data: {
                    action: 'seo_aeo_orchestra_complete_article',
                    nonce: seoAeoOrchestra.nonce,
                    topic: title,
                    keyword: keyword,
                    length: 'medium'
                },
                success: function(response) {
                    $card.find('.expand-article-loading').remove();
                    if (response && response.content) {
                        var content = response.content || '';
                        var wordCount = (content.split(/\s+/) || []).length;

                        // Prepend image if available
                        if (response.image_url) {
                            content = '<img src="' + response.image_url + '" alt="' + title + '" style="max-width:100%;border-radius:8px;margin-bottom:20px;" />\n' + content;
                        }

                        var html = '<div class="expand-content-body">';
                        html += '<div style="margin-bottom:10px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">';
                        html += '<span class="token-badge" style="background:#7c3aed;color:#fff;">Articolo Completo AI</span>';
                        html += '<span class="token-badge">~' + wordCount + ' parole</span>';
                        html += '<span class="token-badge">' + (response.credits_used || 15) + ' crediti usati</span>';
                        if (response.image_url) html += '<span class="token-badge" style="background:#059669;color:#fff;">Immagine AI inclusa</span>';
                        if (response.meta) html += '<span class="token-badge" style="background:#2563EB;color:#fff;">Meta Tags generati</span>';
                        html += '</div>';

                        // Show meta tags preview
                        if (response.meta) {
                            html += '<div style="background:#f0f9ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:12px;">';
                            html += '<strong>Meta Title:</strong> ' + (response.meta.title || '') + '<br>';
                            html += '<strong>Meta Description:</strong> ' + (response.meta.description || '');
                            if (response.meta.keywords && response.meta.keywords.length) {
                                html += '<br><strong>Keywords:</strong> ';
                                response.meta.keywords.forEach(function(kw) {
                                    html += '<span style="background:#e0e7ff;color:#3730a3;padding:1px 6px;border-radius:10px;font-size:11px;margin:2px;">' + kw + '</span> ';
                                });
                            }
                            html += '</div>';
                        }

                        html += '<div class="generated-content" style="max-height:400px;overflow:auto;border:1px solid #eee;padding:15px;border-radius:8px;background:#fff;">' + content + '</div>';
                        html += '<div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">';
                        html += '<button class="button button-primary" onclick="SeoAeoOrchestra.publishCompleteArticle(jQuery(this).closest(\'.expand-content-body\'))"><span class="dashicons dashicons-upload" style="margin-top:3px;"></span> Pubblica Bozza con Meta</button>';
                        html += '<button class="button" onclick="SeoAeoOrchestra.publishContent(\'' + title.replace(/'/g, "\\'") + '\', jQuery(this).closest(\'.expand-content-body\').find(\'.generated-content\').html(), \'publish\')"><span class="dashicons dashicons-yes" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Pubblica Subito') + '</button>';
                        html += '<button class="button" onclick="SeoAeoOrchestra.previewArticle(\'' + title.replace(/'/g, "\\'") + '\', jQuery(this).closest(\'.expand-content-body\').find(\'.generated-content\').html())"><span class="dashicons dashicons-visibility" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Anteprima') + '</button>';
                        html += '<button class="button" onclick="SeoAeoOrchestra.copyContent()"><span class="dashicons dashicons-admin-page" style="margin-top:3px;"></span> ' + SeoAeoOrchestra.t('Copia') + '</button>';
                        html += '</div></div>';

                        // Store meta and image data for publish
                        $card.data('complete-meta', response.meta || {});
                        $card.data('complete-image-id', response.attachment_id || 0);

                        $card.find('.expand-content-actions').before(html);
                        SeoAeoOrchestra.showNotice('Articolo completo generato! Testo + ' + (response.image_url ? 'immagine AI + ' : '') + 'meta tags.', 'success');
                        var caViewHtml = '';
                        if (response.image_url) caViewHtml += '<img src="' + response.image_url + '" style="max-width:100%;border-radius:8px;margin-bottom:14px;" alt="">';
                        if (response.meta) {
                            caViewHtml += '<div style="background:#f0f9ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:12px;">' +
                                '<strong>Meta Title:</strong> ' + (response.meta.title || '') + '<br>' +
                                '<strong>Meta Description:</strong> ' + (response.meta.description || '') + '</div>';
                        }
                        caViewHtml += '<div class="generated-content">' + content + '</div>';
                        var caRestore = {
                            view_html: caViewHtml,
                            meta: { topic: title, keyword: keyword, words: wordCount, credits: response.credits_used || 15 }
                        };
                        SeoAeoOrchestra.saveHistory(agentType, 'complete_article', title, JSON.stringify({
                            topic: title, keyword: keyword, words: wordCount,
                            has_image: !!response.image_url, has_meta: !!response.meta,
                            credits: response.credits_used || 15
                        }), response.credits_used || 15, caRestore);
                    } else {
                        SeoAeoOrchestra.showNotice('Errore: ' + (response.error || 'Generazione fallita'), 'error');
                    }
                },
                error: function() {
                    $card.find('.expand-article-loading').remove();
                    SeoAeoOrchestra.showNotice('Timeout o errore di connessione. L\'operazione potrebbe richiedere piu tempo.', 'error');
                }
            });
        },

        // ── Publish complete article with meta tags and featured image ──
        publishCompleteArticle: function($body) {
            var content = $body.find('.generated-content').html();
            var $card = $body.closest('.expand-content-card');
            var meta = $card.data('complete-meta') || {};
            var imageId = $card.data('complete-image-id') || 0;
            var title = meta.title || $card.find('.expand-content-title').first().text() || 'Articolo AI';

            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_publish_content',
                nonce: seoAeoOrchestra.nonce,
                title: title,
                content: content,
                post_type: 'post',
                status: 'draft'
            }, function(response) {
                if (response && response.success && response.post_id) {
                    // Save meta tags to the new post
                    if (meta.title) {
                        $.post(seoAeoOrchestra.ajaxUrl, {
                            action: 'seo_aeo_orchestra_save_post_meta',
                            nonce: seoAeoOrchestra.nonce,
                            post_id: response.post_id,
                            meta_title: meta.title || '',
                            meta_description: meta.description || '',
                            meta_keywords: (meta.keywords || []).join(', ')
                        });
                    }
                    // Set featured image
                    if (imageId) {
                        $.post(seoAeoOrchestra.ajaxUrl, {
                            action: 'seo_aeo_orchestra_upload_media',
                            nonce: seoAeoOrchestra.nonce,
                            post_id: response.post_id,
                            attachment_id: imageId,
                            position: 'featured'
                        });
                    }
                    SeoAeoOrchestra.showNotice('Articolo pubblicato come bozza con meta tags' + (imageId ? ' e immagine in evidenza' : '') + '! <a href="' + response.edit_url + '" target="_blank">Modifica</a>', 'success');
                } else {
                    SeoAeoOrchestra.showNotice('Errore: ' + (response.error || 'Pubblicazione fallita'), 'error');
                }
            });
        },

        // ── Open WP Media Library uploader ──
        openMediaUploader: function($container) {
            if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                SeoAeoOrchestra.showNotice('WP Media Library non disponibile. Verifica che wp.media sia caricato.', 'error');
                return;
            }
            var frame = wp.media({
                title: 'Seleziona immagine',
                button: { text: 'Inserisci nell\'articolo' },
                multiple: false,
                library: { type: 'image' }
            });
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                var imgHtml = '<img src="' + attachment.url + '" alt="' + (attachment.alt || attachment.title || '') + '" style="max-width:100%;border-radius:8px;margin-bottom:15px;" />';
                $container.find('.generated-content').prepend(imgHtml);
                SeoAeoOrchestra.showNotice('Immagine inserita!', 'success');
            });
            frame.open();
        },

        // ── AI Image Generation dialog ──
        openAiImageGenerator: function($container) {
            // 3.26.1: auto-derive prompt dall'articolo nel container — no più input vuoto
            var articleHtml = '';
            try {
                var $gen = $container.find('.generated-content').first();
                if ($gen.length) articleHtml = $gen.html() || '';
                else articleHtml = $container.html() || '';
            } catch (e) {}
            var articleText = String(articleHtml).replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
            var firstH = '';
            var hMatch = String(articleHtml).match(/<h[12][^>]*>([^<]+)<\/h[12]>/i);
            if (hMatch) firstH = hMatch[1].trim();
            var derivedPrompt = '';
            if (firstH) {
                derivedPrompt = 'Editorial blog header image for article: "' + firstH + '". Clean, modern style, no text overlay, professional photography.';
            } else if (articleText) {
                derivedPrompt = 'Editorial blog header image based on: "' + articleText.substring(0, 200) + '". Clean modern style, no text overlay.';
            }

            // Remove existing dialog if any
            $('.ai-image-dialog').remove();
            var dialogHtml = '<div class="ai-image-dialog" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:100100;display:flex;align-items:center;justify-content:center;">';
            dialogHtml += '<div style="background:#fff;border-radius:12px;padding:24px;max-width:540px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);">';
            dialogHtml += '<h3 style="margin:0 0 16px;display:flex;align-items:center;gap:8px;"><span class="dashicons dashicons-format-image" style="color:#0055FF;"></span> ' + SeoAeoOrchestra.t('Genera Immagine con AI') + '</h3>';
            if (derivedPrompt) {
                dialogHtml += '<div style="background:#ecfdf5;border:1px solid #6ee7b7;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:12px;color:#065f46;">✨ ' + SeoAeoOrchestra.t('Prompt derivato automaticamente dal tuo articolo. Modificalo se vuoi.') + '</div>';
            }
            dialogHtml += '<p style="font-size:13px;color:#666;margin-bottom:12px;">' + SeoAeoOrchestra.t('Descrivi l\'immagine che vuoi generare. L\'AI creera un\'immagine unica e la inserira nel tuo articolo.') + '</p>';
            dialogHtml += '<textarea id="ai-image-prompt" rows="4" style="width:100%;border:1px solid #ddd;border-radius:8px;padding:10px;font-size:14px;resize:vertical;" placeholder="Es: Un\'infografica professionale per un articolo blog tecnico...">' + (derivedPrompt || '') + '</textarea>';
            dialogHtml += '<div style="display:flex;gap:8px;margin-top:12px;align-items:center;">';
            dialogHtml += '<span class="token-badge" style="font-size:11px;">~5 crediti</span>';
            dialogHtml += '<div style="flex:1;"></div>';
            dialogHtml += '<button type="button" class="button ai-image-cancel" style="margin-right:4px;">Annulla</button>';
            dialogHtml += '<button type="button" class="button button-primary ai-image-generate"><span class="dashicons dashicons-format-image" style="margin-top:3px;"></span> Genera</button>';
            dialogHtml += '</div>';
            dialogHtml += '<div id="ai-image-status" style="display:none;margin-top:12px;text-align:center;"></div>';
            dialogHtml += '</div></div>';
            $('body').append(dialogHtml);

            // Cancel
            $(document).on('click.aiimg', '.ai-image-cancel, .ai-image-dialog', function(e) {
                if (e.target === this || $(e.target).hasClass('ai-image-cancel')) {
                    $('.ai-image-dialog').remove();
                    $(document).off('click.aiimg');
                }
            });

            // Generate
            $(document).on('click.aiimg', '.ai-image-generate', function() {
                var prompt = $('#ai-image-prompt').val().trim();
                if (!prompt) {
                    SeoAeoOrchestra.showNotice('Inserisci una descrizione per l\'immagine', 'error');
                    return;
                }
                var $btn = $(this);
                $btn.prop('disabled', true).html('<span class="orchestra-spinner" style="display:inline-block;width:14px;height:14px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;animation:spin 0.8s linear infinite;vertical-align:middle;"></span> Generazione in corso...');
                $('#ai-image-status').html('<div style="padding:10px;background:#f0f9ff;border-radius:8px;font-size:13px;"><span class="spinner is-active" style="float:none;margin:0 8px 0 0;"></span> L\'AI sta generando l\'immagine. Potrebbe richiedere fino a 60 secondi...</div>').show();

                $.ajax({
                    url: seoAeoOrchestra.ajaxUrl,
                    type: 'POST',
                    timeout: 120000,
                    data: {
                        action: 'seo_aeo_orchestra_generate_image',
                        nonce: seoAeoOrchestra.nonce,
                        prompt: prompt
                    },
                    success: function(response) {
                        if (response && response.success && response.image_url) {
                            var imgHtml = '<img src="' + response.image_url + '" alt="' + prompt.substring(0, 100) + '" style="max-width:100%;border-radius:8px;margin-bottom:15px;" />';
                            $container.find('.generated-content').prepend(imgHtml);
                            $('.ai-image-dialog').remove();
                            $(document).off('click.aiimg');
                            SeoAeoOrchestra.showNotice('Immagine AI generata e inserita! (' + (response.credits_used || 5) + ' crediti usati)', 'success');
                            var imgViewHtml = '<img src="' + response.image_url + '" style="max-width:100%;border-radius:10px;" alt="">' +
                                '<div style="margin-top:14px;padding:12px 16px;background:#f9fafb;border-radius:8px;font-size:13px;"><strong>Prompt:</strong><br>' + prompt + '</div>';
                            var imgRestore = {
                                view_html: imgViewHtml,
                                meta: { credits: response.credits_used || 5 }
                            };
                            SeoAeoOrchestra.saveHistory('image_generation', 'ai_image', prompt.substring(0, 80), JSON.stringify({prompt: prompt, image_url: response.image_url, credits: response.credits_used || 5}), response.credits_used || 5, imgRestore);
                        } else {
                            $('#ai-image-status').html('<div style="padding:10px;background:#fef2f2;border-radius:8px;color:#b91c1c;font-size:13px;">' + (response.error || 'Errore nella generazione') + '</div>');
                            $btn.prop('disabled', false).html('<span class="dashicons dashicons-format-image" style="margin-top:3px;"></span> Riprova');
                        }
                    },
                    error: function() {
                        $('#ai-image-status').html('<div style="padding:10px;background:#fef2f2;border-radius:8px;color:#b91c1c;font-size:13px;">Timeout o errore di connessione. Riprova.</div>');
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-format-image" style="margin-top:3px;"></span> Riprova');
                    }
                });
            });
        },

        // 3.39.6 — Preview the action's proposed output before applying.
        previewAction: function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $btn = jQuery(this);
            var agent = $btn.data('agent');
            var rawData = $btn.attr('data-action-data') || '{}';
            // data-action-data on Problemi cards has single-quote → &apos; escaping.
            rawData = String(rawData).replace(/&apos;/g, "'");
            var actionData;
            try { actionData = JSON.parse(rawData); } catch (err) { actionData = {}; }

            if (agent === 'manual_review') {
                SeoAeoOrchestra.showPreviewModal({
                    agent: agent,
                    current: {},
                    proposed: {manual_review: true},
                    message: SeoAeoOrchestra.t('Questa azione richiede una revisione manuale. Apri la pagina nell\'editor WordPress per esaminare il problema.'),
                });
                return;
            }

            var origLabel = $btn.html();
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + SeoAeoOrchestra.t('Generando preview...'));
            console.log('[PREVIEW] click', {agent: agent, actionData: actionData});

            // 3.39.10 — explicit 60s timeout + better diagnostics + always-
            // restore guard. No button can hang in loading state forever.
            jQuery.ajax({
                url: seoAeoOrchestra.ajaxUrl,
                type: 'POST',
                timeout: 60000,
                data: {
                    action: 'seo_aeo_orchestra_preview_action',
                    nonce: seoAeoOrchestra.nonce,
                    agent: agent,
                    action_data: actionData,
                },
            }).done(function(resp) {
                console.log('[PREVIEW] response', resp);
                if (!resp || resp.error) {
                    SeoAeoOrchestra.showNotice(SeoAeoOrchestra.t('Errore preview') + ': ' + ((resp && resp.error) || 'sconosciuto'), 'error');
                    return;
                }
                try {
                    resp._sourceBtn = $btn.get(0);
                    SeoAeoOrchestra.showPreviewModal(resp);
                } catch (renderErr) {
                    console.error('[PREVIEW] showPreviewModal threw', renderErr);
                    SeoAeoOrchestra.showNotice(SeoAeoOrchestra.t('Errore rendering preview') + ': ' + (renderErr && renderErr.message ? renderErr.message : ''), 'error');
                }
            }).fail(function(xhr, status) {
                console.error('[PREVIEW] fail', {status: xhr ? xhr.status : null, ajaxStatus: status, body: (xhr && xhr.responseText ? xhr.responseText.substring(0, 300) : '')});
                var msg;
                if (status === 'timeout') {
                    msg = SeoAeoOrchestra.t('Preview impiega troppo tempo (>60s). Riprova tra qualche secondo.');
                } else {
                    msg = SeoAeoOrchestra.t('Errore di rete') + ' (' + (xhr ? xhr.status : '?') + ')';
                }
                SeoAeoOrchestra.showNotice(msg, 'error');
            }).always(function() {
                console.log('[PREVIEW] always — restoring button');
                $btn.prop('disabled', false).html(origLabel);
            });
        },

        // 3.39.6 — Render the preview-vs-current modal. Branches per agent.
        showPreviewModal: function(payload) {
            var $ = jQuery;
            $('#orch-action-preview-modal').remove();
            var T = SeoAeoOrchestra.t || function(s){return s;};
            var agent = payload.agent || 'unknown';
            var current = payload.current || {};
            var proposed = payload.proposed || {};
            var credits = payload.estimated_credits || 0;

            function escHtml(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){
                    return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
                });
            }

            function renderSideBySide(label, currentVal, proposedVal) {
                return '<div class="orch-apv-section">' +
                       '<h4>' + escHtml(label) + '</h4>' +
                       '<div class="orch-apv-diff">' +
                         '<div class="orch-apv-col orch-apv-current"><div class="orch-apv-col-h">' + T('Attuale') + '</div><div class="orch-apv-val">' + (currentVal ? escHtml(currentVal) : '<em>' + T('vuoto') + '</em>') + '</div></div>' +
                         '<div class="orch-apv-col orch-apv-proposed"><div class="orch-apv-col-h">' + T('Proposto') + '</div><div class="orch-apv-val">' + (proposedVal ? escHtml(proposedVal) : '<em>' + T('vuoto') + '</em>') + '</div></div>' +
                       '</div></div>';
            }

            var bodyHtml = '';
            var canApply = true;

            if (proposed && proposed.error) {
                bodyHtml = '<p class="orch-apv-err">' + T('Backend ha restituito un errore') + ': ' + escHtml(proposed.error || proposed.message || '') + '</p>';
                canApply = false;
            } else if (payload.message || (proposed && proposed.manual_review)) {
                bodyHtml = '<p class="orch-apv-info">' + escHtml(payload.message || T('Revisione manuale richiesta.')) + '</p>';
                canApply = false;
            } else if (agent === 'meta_tags') {
                bodyHtml += '<p class="orch-apv-pageinfo">' + T('Pagina') + ': <strong>' + escHtml(current.page_title || '—') + '</strong>' + (current.page_url ? ' — <a href="' + escHtml(current.page_url) + '" target="_blank">' + escHtml(current.page_url) + '</a>' : '') + '</p>';
                bodyHtml += renderSideBySide('Meta Title', current.meta_title, proposed.title);
                bodyHtml += renderSideBySide('Meta Description', current.meta_description, proposed.description);
                if (proposed.keywords && proposed.keywords.length) {
                    bodyHtml += '<div class="orch-apv-section"><h4>' + T('Keywords proposte') + '</h4><p>' + escHtml(proposed.keywords.join(', ')) + '</p></div>';
                }
            } else if (agent === 'aeo_content' || agent === 'content_generator') {
                bodyHtml += '<p class="orch-apv-pageinfo">' + T('Pagina') + ': <strong>' + escHtml(current.page_title || '—') + '</strong></p>';
                var content = proposed.content || '';
                if (!content) {
                    bodyHtml += '<p class="orch-apv-err">' + T('Nessun contenuto generato dall\'AI.') + '</p>';
                    canApply = false;
                } else {
                    bodyHtml += '<div class="orch-apv-section"><h4>' + T('Contenuto proposto') + ' (' + (proposed.word_count || '~') + ' ' + T('parole') + ')</h4>';
                    bodyHtml += '<div class="orch-apv-content-preview">' + content + '</div></div>';
                    if (proposed.schema) {
                        bodyHtml += '<div class="orch-apv-section"><h4>' + T('Schema JSON-LD incluso') + '</h4><pre class="orch-apv-code">' + escHtml(JSON.stringify(proposed.schema, null, 2)) + '</pre></div>';
                    }
                }
            } else if (agent === 'seo_analysis') {
                bodyHtml += '<p class="orch-apv-pageinfo">' + T('Pagina') + ': <strong>' + escHtml(current.page_title || '—') + '</strong></p>';
                bodyHtml += '<div class="orch-apv-section"><h4>' + T('Analisi SEO') + '</h4>';
                if (typeof proposed.score === 'number') bodyHtml += '<p>' + T('Score') + ': <strong>' + proposed.score + '/100</strong></p>';
                if (proposed.issues && proposed.issues.length) {
                    bodyHtml += '<h5>' + T('Problemi identificati') + ':</h5><ul>';
                    proposed.issues.slice(0, 8).forEach(function(it) {
                        var txt = typeof it === 'string' ? it : (it.description || JSON.stringify(it));
                        bodyHtml += '<li>' + escHtml(txt) + '</li>';
                    });
                    bodyHtml += '</ul>';
                }
                if (proposed.suggestions && proposed.suggestions.length) {
                    bodyHtml += '<h5>' + T('Suggerimenti') + ':</h5><ul>';
                    proposed.suggestions.slice(0, 5).forEach(function(s) { bodyHtml += '<li>' + escHtml(typeof s === 'string' ? s : JSON.stringify(s)) + '</li>'; });
                    bodyHtml += '</ul>';
                }
                bodyHtml += '</div>';
            } else {
                bodyHtml = '<pre class="orch-apv-code">' + escHtml(JSON.stringify(proposed, null, 2)) + '</pre>';
            }

            var ctaHtml = '<button type="button" class="button orch-apv-cancel">' + T('Annulla') + '</button>';
            ctaHtml += '<button type="button" class="button orch-apv-regen">' + T('Rigenera') + '</button>';
            if (canApply) {
                ctaHtml += '<button type="button" class="button button-primary orch-apv-apply">' + T('Applica modifiche') + (credits ? ' (' + credits + ' cr)' : '') + '</button>';
            }

            if (!$('#orch-apv-styles').length) {
                $('head').append(
                    '<style id="orch-apv-styles">' +
                    '.orch-apv-backdrop{position:fixed;inset:0;background:rgba(15,23,42,0.55);display:flex;align-items:center;justify-content:center;z-index:100002;animation:orchApvFade 0.15s ease;}' +
                    '@keyframes orchApvFade{from{opacity:0}to{opacity:1}}' +
                    '.orch-apv-modal{background:#fff;border-radius:12px;box-shadow:0 24px 48px rgba(0,0,0,0.3);max-width:760px;width:calc(100% - 32px);max-height:88vh;display:flex;flex-direction:column;}' +
                    '.orch-apv-head{padding:16px 22px 12px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e5e7eb;}' +
                    '.orch-apv-head h3{margin:0;font-size:17px;font-weight:600;color:#0F172A;}' +
                    '.orch-apv-close{background:none;border:0;font-size:24px;line-height:1;color:#94A3B8;cursor:pointer;padding:0 4px;}' +
                    '.orch-apv-body{padding:14px 22px;overflow:auto;flex:1;font-size:13px;color:#475569;}' +
                    '.orch-apv-pageinfo{color:#475569;margin:0 0 12px;}' +
                    '.orch-apv-section{margin:0 0 16px;}' +
                    '.orch-apv-section h4{margin:8px 0 6px;font-size:13px;font-weight:700;color:#0F172A;text-transform:uppercase;letter-spacing:0.05em;}' +
                    '.orch-apv-diff{display:grid;grid-template-columns:1fr 1fr;gap:8px;}' +
                    '.orch-apv-col{padding:10px 12px;border-radius:6px;background:#f8fafc;border:1px solid #e2e8f0;}' +
                    '.orch-apv-current{background:#fef2f2;border-color:#fecaca;}' +
                    '.orch-apv-proposed{background:#ecfdf5;border-color:#a7f3d0;}' +
                    '.orch-apv-col-h{font-size:11px;text-transform:uppercase;color:#94A3B8;font-weight:700;margin-bottom:6px;}' +
                    '.orch-apv-val{font-size:13px;color:#0F172A;word-wrap:break-word;}' +
                    '.orch-apv-content-preview{max-height:360px;overflow:auto;padding:12px 14px;border:1px solid #e5e7eb;border-radius:6px;background:#fff;font-size:13px;line-height:1.55;}' +
                    '.orch-apv-code{max-height:240px;overflow:auto;background:#0F172A;color:#e0e7ff;padding:12px;border-radius:6px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:11px;line-height:1.5;}' +
                    '.orch-apv-err{color:#991b1b;background:#fef2f2;padding:10px 14px;border-radius:6px;border-left:3px solid #ef4444;}' +
                    '.orch-apv-info{color:#1e3a8a;background:#eff6ff;padding:10px 14px;border-radius:6px;border-left:3px solid #3b82f6;}' +
                    '.orch-apv-foot{padding:12px 22px 16px;border-top:1px solid #e5e7eb;display:flex;gap:8px;justify-content:flex-end;}' +
                    '@media (max-width:680px){.orch-apv-diff{grid-template-columns:1fr;}}' +
                    '@keyframes orchApvSpin{from{transform:rotate(0)}to{transform:rotate(360deg)}}' +
                    '.dashicons.spin{animation:orchApvSpin 0.8s linear infinite;display:inline-block;}' +
                    '</style>'
                );
            }

            var html = '<div class="orch-apv-backdrop" id="orch-action-preview-modal">' +
                '<div class="orch-apv-modal">' +
                  '<div class="orch-apv-head">' +
                    '<div>' +
                      '<h3 style="margin:0;">' + T('Anteprima modifiche') + ' — ' + escHtml(payload.agent || '') + '</h3>' +
                      '<div class="orch-apv-subtitle" style="font-size:11px;color:#94A3B8;margin-top:3px;">' + T('Preview con tier veloce (1cr). Applica usa il tier configurato.') + '</div>' +
                    '</div>' +
                    '<button type="button" class="orch-apv-close" aria-label="' + T('Chiudi') + '">×</button>' +
                  '</div>' +
                  '<div class="orch-apv-body">' + bodyHtml + '</div>' +
                  '<div class="orch-apv-foot">' + ctaHtml + '</div>' +
                '</div>' +
              '</div>';
            $('body').append(html);
            var $modal = $('#orch-action-preview-modal');
            function close() { $modal.remove(); }
            $modal.on('click', function(ev) { if (ev.target === $modal[0]) close(); });
            $modal.find('.orch-apv-close, .orch-apv-cancel').on('click', close);
            $modal.find('.orch-apv-regen').on('click', function() {
                close();
                if (payload._sourceBtn) jQuery(payload._sourceBtn).trigger('click');
            });
            $modal.find('.orch-apv-apply').on('click', function() {
                close();
                // Trigger the sibling execute button so the existing
                // executor handler runs (writes to DB, deducts credits).
                if (!payload._sourceBtn) return;
                var $src = jQuery(payload._sourceBtn);
                // Each card has a paired .orch-execute-btn with the same data-agent.
                // Find the nearest sibling execute button in the same .orch-action-item / .orch-problem-card.
                var $card = $src.closest('.orch-action-item, .orch-problem-card');
                var $exec = $card.find('.orch-execute-btn').first();
                if ($exec.length) {
                    $exec.trigger('click');
                } else {
                    SeoAeoOrchestra.showNotice(T('Pulsante Esegui non trovato'), 'error');
                }
            });
        },

        executeAction: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var agent = $btn.data('agent');
            var actionData = $btn.data('action-data');
            var idx = $btn.data('idx');
            var $result = $('#orch-action-result-' + idx);

            $btn.addClass('executing').html('<span class="orchestra-spinner"></span> Esecuzione...');

            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_execute_action',
                nonce: seoAeoOrchestra.nonce,
                agent: agent,
                action_data: actionData
            }, function(response) {
                $btn.removeClass('executing');
                if (response && !response.error) {
                    $btn.html('<span class="dashicons dashicons-yes"></span> Completato').prop('disabled', true).css({'background':'#10B981','border-color':'#10B981'});
                    var resultHtml = '';
                    if (agent === 'meta_tags' && response.saved) {
                        resultHtml = '<strong style="color:#10B981;">Meta tags salvati!</strong><br>Title: ' + (response.title || '') + '<br>Description: ' + (response.description || '');
                    } else if (response.content || response.article) {
                        var content = response.content || response.article || '';
                        resultHtml = '<strong style="color:#10B981;">Contenuto generato!</strong><br>' + content.substring(0, 300) + '...';
                    } else {
                        resultHtml = '<strong style="color:#10B981;">Azione eseguita.</strong><br><pre style="font-size:11px">' + JSON.stringify(response, null, 2).substring(0, 500) + '</pre>';
                    }
                    $result.html(resultHtml).show();
                } else {
                    $btn.html('<span class="dashicons dashicons-warning"></span> Errore').css({'background':'#EF4444','border-color':'#EF4444'});
                    $result.html('<span style="color:red;">' + (response.error || 'Errore sconosciuto') + '</span>').show();
                }
            }).fail(function() {
                $btn.removeClass('executing').html('<span class="dashicons dashicons-warning"></span> Errore connessione');
            });
        }
    };

    window.SeoAeoOrchestra = SeoAeoOrchestra;

    $(document).ready(function() {
        SeoAeoOrchestra.init();
    });

})(jQuery);

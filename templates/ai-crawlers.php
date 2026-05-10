<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.VariableNotPrefixed
// Reason: template scope. Variables are local to this include/template,
// passed by the calling function via include/require. The Plugin Check
// heuristic doesn't distinguish template-scope locals from globals.
/*
 * Template: 🛡️ AI Crawlers — 3-tab UI (Allowlist / Crawler Log / Robots.txt)
 * Pattern allineato a SEO Output Nativo (3 tab + sub-panel pattern).
 */
if (!defined('ABSPATH')) exit;

$bot_defs = SEO_AEO_AI_Crawler_Detector::bot_definitions();
$allowlist = SEO_AEO_AI_Crawler_Detector::get_allowlist();
$log_ip_enabled = SEO_AEO_AI_Crawler_Detector::is_ip_logging_enabled();
$enabled_count = count(array_filter($allowlist));
$disabled_count = count($allowlist) - $enabled_count;

// Group bots by provider
$by_provider = array();
foreach ($bot_defs as $slug => $def) {
    $by_provider[$def['provider']][$slug] = $def;
}
$provider_labels = array(
    'openai' => 'OpenAI (ChatGPT)',
    'anthropic' => 'Anthropic (Claude)',
    'perplexity' => 'Perplexity AI',
    'google' => 'Google AI / Gemini',
    'microsoft' => 'Microsoft (Copilot/Bing)',
    'meta' => 'Meta AI',
    'xai' => 'xAI (Grok)',
    'deepseek' => 'DeepSeek',
    'apple' => 'Apple Intelligence',
    'commoncrawl' => 'Common Crawl',
    'cohere' => 'Cohere',
    'mistral' => 'Mistral AI',
    'you' => 'You.com',
);
?>
<div class="wrap orch-ai-crawlers-page">

    <h1 style="display:flex;align-items:center;gap:10px;">
        <span style="font-size:28px;">🛡️</span>
        <span>AI Crawlers</span>
        <span style="font-size:12px;background:#dbeafe;color:#1e40af;padding:2px 10px;border-radius:12px;font-weight:500;">v3.35.80</span>
    </h1>

    <p class="orch3-muted" style="margin:8px 0 18px;font-size:13px;line-height:1.55;max-width:780px;">
        <strong>Controllo dedicato per i crawler AI</strong> (ChatGPT, Claude, Perplexity, Gemini, Copilot, Meta, Grok, DeepSeek, Apple Intelligence, e altri).
        Allowlist con toggle per provider · log delle visite reali ultimi 30 giorni · robots.txt automatico generato dalle tue scelte.
    </p>

    <!-- Tab navigation -->
    <h2 class="nav-tab-wrapper" style="margin-bottom:0;">
        <a href="#tab-allowlist" class="nav-tab nav-tab-active" data-orch-tab="allowlist">🛡️ Allowlist</a>
        <a href="#tab-log" class="nav-tab" data-orch-tab="log">📊 Crawler Log</a>
        <a href="#tab-robots" class="nav-tab" data-orch-tab="robots">🤖 Robots.txt</a>
    </h2>

    <!-- ═══════════════ TAB 1: ALLOWLIST ═══════════════ -->
    <div class="orch-tab-content" id="tab-allowlist" style="background:#fff;border:1px solid #ccd0d4;border-top:0;padding:18px 22px;">

        <details class="orch-acc-explainer" style="margin-bottom:16px;">
            <summary style="cursor:pointer;font-weight:600;font-size:14px;color:#0f172a;">📖 Che cos'è</summary>
            <div style="padding:10px 0;font-size:13px;line-height:1.55;color:#334155;max-width:780px;">
                Gli AI crawler sono i bot di ChatGPT, Claude, Perplexity, Gemini, Copilot ecc. che leggono il tuo sito per indicizzare il contenuto e citarlo nelle risposte.
                <strong>Bloccarli ti rende invisibile a quel motore.</strong> Default: tutti permessi (allowlist permissiva).
                Block solo se hai motivi specifici di policy aziendale o licenze contenuti.
            </div>
        </details>

        <div class="orch-acc-status-summary" style="margin:8px 0 18px;padding:10px 14px;background:#f0fdf4;border:1px solid #86efac;border-left:4px solid #10b981;border-radius:6px;">
            <strong style="color:#14532d;">✅ Configurazione attiva</strong><br>
            <span style="font-size:13px;color:#14532d;"><strong id="orch-allowlist-enabled-count"><?php echo esc_html($enabled_count); ?></strong> bot permessi · <strong id="orch-allowlist-disabled-count"><?php echo esc_html($disabled_count); ?></strong> bot bloccati</span>
        </div>

        <!-- Privacy toggle -->
        <div style="margin:0 0 18px;padding:10px 14px;background:#fef3c7;border:1px solid #fde68a;border-radius:6px;font-size:12px;">
            <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;">
                <input type="checkbox" id="orch-ai-crawler-log-ip" <?php checked($log_ip_enabled); ?> />
                <span>
                    <strong>Logga IP del bot</strong><br>
                    <span class="orch3-muted">L'IP viene loggato per audit di traffico bot. Disattiva per privacy mode (legitimate interest GDPR).</span>
                </span>
            </label>
        </div>

        <!-- Provider groups -->
        <?php foreach ($by_provider as $provider_slug => $bots): ?>
        <fieldset class="orch-identity-fieldset" style="margin-bottom:14px;">
            <legend style="font-size:13px;font-weight:600;color:#0f172a;"><?php echo esc_html($provider_labels[$provider_slug] ?? ucfirst($provider_slug)); ?></legend>
            <?php foreach ($bots as $slug => $def): $enabled = isset($allowlist[$slug]) ? $allowlist[$slug] : true; ?>
            <div class="orch-bot-row" style="display:flex;align-items:flex-start;gap:10px;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;flex:1;">
                    <input type="checkbox" class="orch-bot-toggle" data-bot-slug="<?php echo esc_attr($slug); ?>" <?php checked($enabled); ?> />
                    <span>
                        <strong><?php echo esc_html($def['name']); ?></strong>
                        <span class="orch3-muted" style="font-size:11px;display:block;margin-top:2px;"><?php echo esc_html($def['description']); ?></span>
                    </span>
                </label>
                <a href="#tab-log" class="button button-small orch-bot-view-log" data-bot-slug="<?php echo esc_attr($slug); ?>" title="Vedi log visite di questo bot">→ vedi log</a>
                <?php if (!empty($def['doc_url'])): ?>
                <a href="<?php echo esc_url($def['doc_url']); ?>" target="_blank" rel="noopener" class="button button-small" title="Documentazione ufficiale">📄 docs</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </fieldset>
        <?php endforeach; ?>

        <p class="orch3-muted" style="font-size:11px;margin-top:14px;">
            💡 Default: tutti i bot sono permessi. Bloccare un bot fa apparire una direttiva <code>User-agent: ... / Disallow: /</code> nel tuo /robots.txt.
            Il blocco non è enforced server-side — è un signal che bot rispettosi seguono.
        </p>
    </div>

    <!-- ═══════════════ TAB 2: CRAWLER LOG ═══════════════ -->
    <div class="orch-tab-content" id="tab-log" style="background:#fff;border:1px solid #ccd0d4;border-top:0;padding:18px 22px;display:none;">

        <details class="orch-acc-explainer" style="margin-bottom:16px;">
            <summary style="cursor:pointer;font-weight:600;font-size:14px;color:#0f172a;">📖 Che cos'è</summary>
            <div style="padding:10px 0;font-size:13px;line-height:1.55;color:#334155;max-width:780px;">
                Tabella delle visite reali dei bot AI sul tuo sito (ultimi 30 giorni rolling, retention auto-cleanup).
                Vedi quale bot quando visita quale URL. Stats card sopra per top bot / top page del periodo selezionato.
            </div>
        </details>

        <!-- Stats cards -->
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
            <div class="orch-stat-card" style="flex:1;min-width:200px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 16px;">
                <div style="font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;display:flex;justify-content:space-between;align-items:center;">
                    <span>Visite totali</span>
                    <select id="orch-stats-window" style="font-size:11px;">
                        <option value="7" selected>7 giorni</option>
                        <option value="28">28 giorni</option>
                    </select>
                </div>
                <div id="orch-stat-total" style="font-size:28px;font-weight:700;color:#0f172a;margin-top:6px;">—</div>
            </div>
            <div class="orch-stat-card" style="flex:1;min-width:200px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 16px;">
                <div style="font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Bot più attivo</div>
                <div id="orch-stat-top-bot" style="font-size:18px;font-weight:600;color:#0f172a;margin-top:6px;">—</div>
            </div>
            <div class="orch-stat-card" style="flex:1;min-width:240px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 16px;">
                <div style="font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Pagina più crawlata</div>
                <div id="orch-stat-top-page" style="font-size:13px;font-weight:500;color:#0f172a;margin-top:6px;font-family:ui-monospace,monospace;word-break:break-all;">—</div>
            </div>
        </div>

        <!-- Filter row -->
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">
            <label style="font-size:12px;display:inline-flex;align-items:center;gap:4px;">
                Filter bot:
                <select id="orch-log-bot-filter" style="font-size:12px;">
                    <option value="">— Tutti —</option>
                    <?php foreach ($bot_defs as $slug => $def): ?>
                    <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($def['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="button" class="button" id="orch-log-refresh-btn">🔄 Refresh</button>
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=orch_ai_crawler_export_csv'), 'orch_ai_crawler_export_csv')); ?>" id="orch-log-csv-btn" class="button button-secondary">📥 Export CSV</a>
        </div>

        <!-- Log table -->
        <div id="orch-log-table-wrap">
            <table class="widefat striped" id="orch-log-table">
                <thead>
                    <tr>
                        <th style="width:140px;">Visited</th>
                        <th style="width:120px;">Bot</th>
                        <th style="width:90px;">Provider</th>
                        <th>Request URI</th>
                        <th style="width:120px;">IP</th>
                        <th style="width:60px;">Code</th>
                    </tr>
                </thead>
                <tbody><tr><td colspan="6" class="orch3-muted">Caricamento…</td></tr></tbody>
            </table>
            <div id="orch-log-pagination" style="margin-top:10px;font-size:12px;"></div>
        </div>
    </div>

    <!-- ═══════════════ TAB 3: ROBOTS.TXT ═══════════════ -->
    <div class="orch-tab-content" id="tab-robots" style="background:#fff;border:1px solid #ccd0d4;border-top:0;padding:18px 22px;display:none;">

        <details class="orch-acc-explainer" style="margin-bottom:16px;">
            <summary style="cursor:pointer;font-weight:600;font-size:14px;color:#0f172a;">📖 Che cos'è</summary>
            <div style="padding:10px 0;font-size:13px;line-height:1.55;color:#334155;max-width:780px;">
                <code>/robots.txt</code> è il file standard che dice ai bot quali parti del tuo sito possono visitare.
                Orchestra <strong>auto-genera</strong> il robots.txt aggregando le tue scelte Allowlist (bot bloccati ricevono <code>Disallow: /</code>).
                Override manuale disponibile sotto se vuoi sostituire integralmente il robots.txt.
            </div>
        </details>

        <h3>Anteprima live <code><?php echo esc_html(home_url('/robots.txt')); ?></code></h3>
        <pre id="orch-robots-preview" style="background:#0f172a;color:#e2e8f0;padding:14px 18px;border-radius:8px;font-size:12px;font-family:ui-monospace,monospace;line-height:1.6;max-height:280px;overflow:auto;white-space:pre-wrap;">Caricamento…</pre>

        <p style="margin:14px 0 6px;"><strong>Override manuale</strong> <span class="orch3-muted" style="font-size:11px;">(se popolato, sostituisce integralmente il robots.txt auto-generated)</span></p>
        <textarea id="orch-robots-override" rows="10" style="width:100%;font-family:ui-monospace,monospace;font-size:12px;" placeholder="(default — disabled)"></textarea>

        <div style="margin-top:8px;display:flex;gap:8px;">
            <button type="button" class="button button-primary" id="orch-robots-save-btn">💾 Salva override</button>
            <button type="button" class="button" id="orch-robots-clear-btn">🗑 Rimuovi override</button>
            <a href="<?php echo esc_url(home_url('/robots.txt')); ?>?_v=<?php echo esc_attr(time()); ?>" target="_blank" rel="noopener" class="button">🔗 Apri /robots.txt live</a>
        </div>

        <div id="orch-robots-diff" style="margin-top:14px;display:none;"></div>
    </div>

</div>

<?php ob_start(); ?>
/* 3.35.80.1: tab content visibility fallback (if JS fails to load, first tab stays visible) */
.orch-ai-crawlers-page .orch-tab-content { display: none; }
.orch-ai-crawlers-page .orch-tab-content#tab-allowlist { display: block; }
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>
<?php ob_start(); ?>
(function($) {
    if (!$ || typeof window.seoAeoOrchestra === 'undefined') return;
    var ajaxurl = window.ajaxurl || (window.seoAeoOrchestra && window.seoAeoOrchestra.ajaxurl);
    var nonce = window.seoAeoOrchestra && window.seoAeoOrchestra.nonce;
    if (!ajaxurl || !nonce) return;

    function escapeHtml(s) { return String(s || '').replace(/[&<>"']/g, function(c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    }); }

    // 3.35.80.2: tab switching extracted to dedicated jQuery(document).ready block
    // (independent of seoAeoOrchestra global) — see end of template.

    // ── Allowlist ──────────────────────────────────────────────
    $(document).on('change', '.orch-bot-toggle', function() {
        var map = {};
        $('.orch-bot-toggle').each(function() {
            map[$(this).data('bot-slug')] = $(this).is(':checked') ? 1 : 0;
        });
        $.post(ajaxurl, {action: 'orch_ai_crawler_save_allowlist', nonce: nonce, allowlist: map}).done(function(resp) {
            if (resp && resp.success) {
                var enabled = 0, disabled = 0;
                for (var k in resp.allowlist) {
                    if (resp.allowlist[k]) enabled++; else disabled++;
                }
                $('#orch-allowlist-enabled-count').text(enabled);
                $('#orch-allowlist-disabled-count').text(disabled);
            }
        });
    });

    $(document).on('change', '#orch-ai-crawler-log-ip', function() {
        var v = $(this).is(':checked') ? 1 : 0;
        $.post(ajaxurl, {action: 'orch_ai_crawler_save_privacy', nonce: nonce, log_ip: v});
    });

    $(document).on('click', '.orch-bot-view-log', function(e) {
        e.preventDefault();
        var slug = $(this).data('bot-slug');
        $('#orch-log-bot-filter').val(slug);
        $('.nav-tab[data-orch-tab="log"]').click();
    });

    // ── Crawler Log ────────────────────────────────────────────
    function loadStats() {
        var window_days = $('#orch-stats-window').val() || 7;
        $.post(ajaxurl, {action: 'orch_ai_crawler_stats', nonce: nonce, window: window_days}).done(function(resp) {
            if (!resp || !resp.success) return;
            $('#orch-stat-total').text(resp.total_visits);
            if (resp.top_bot) {
                $('#orch-stat-top-bot').html(escapeHtml(resp.top_bot.bot_name) + ' <span class="orch3-muted" style="font-size:11px;">(' + resp.top_bot.visits + ' visite)</span>');
            } else {
                $('#orch-stat-top-bot').html('<span class="orch3-muted">— nessuna visita —</span>');
            }
            if (resp.top_page) {
                $('#orch-stat-top-page').html('<code style="font-size:12px;">' + escapeHtml(resp.top_page.request_uri) + '</code> <span class="orch3-muted" style="font-size:11px;">(' + resp.top_page.hits + ' hits)</span>');
            } else {
                $('#orch-stat-top-page').html('<span class="orch3-muted">— nessuna visita —</span>');
            }
        });
    }
    $(document).on('change', '#orch-stats-window', loadStats);

    function loadLog(page) {
        var bot = $('#orch-log-bot-filter').val() || '';
        $.post(ajaxurl, {action: 'orch_ai_crawler_log_query', nonce: nonce, page: page || 1, bot: bot}).done(function(resp) {
            if (!resp || !resp.success) {
                $('#orch-log-table tbody').html('<tr><td colspan="6">Errore caricamento log</td></tr>');
                return;
            }
            var rows = resp.rows || [];
            if (!rows.length) {
                $('#orch-log-table tbody').html('<tr><td colspan="6" class="orch3-muted">Nessuna visita registrata' + (bot ? ' per ' + escapeHtml(bot) : '') + '. I bot AI verranno loggati al prossimo crawl.</td></tr>');
            } else {
                var html = '';
                rows.forEach(function(r) {
                    html += '<tr>';
                    html += '<td>' + escapeHtml(r.visited_at) + '</td>';
                    html += '<td><strong>' + escapeHtml(r.bot_name) + '</strong></td>';
                    html += '<td><span style="background:#dbeafe;color:#1e40af;padding:2px 6px;border-radius:8px;font-size:11px;">' + escapeHtml(r.bot_provider) + '</span></td>';
                    html += '<td><code style="font-size:11px;">' + escapeHtml(r.request_uri) + '</code></td>';
                    html += '<td><code style="font-size:11px;">' + escapeHtml(r.ip || '—') + '</code></td>';
                    html += '<td>' + escapeHtml(String(r.response_code)) + '</td>';
                    html += '</tr>';
                });
                $('#orch-log-table tbody').html(html);
            }
            // Pagination
            var pag = '';
            pag += 'Pagina ' + resp.page + ' di ' + resp.pages_count + ' · totale ' + resp.total + ' entries';
            if (resp.pages_count > 1) {
                if (resp.page > 1) pag += ' · <a href="#" class="orch-log-page-link" data-page="' + (resp.page - 1) + '">← prec</a>';
                if (resp.page < resp.pages_count) pag += ' · <a href="#" class="orch-log-page-link" data-page="' + (resp.page + 1) + '">succ →</a>';
            }
            $('#orch-log-pagination').html(pag);
            // CSV link with bot filter
            var csvUrl = $('#orch-log-csv-btn').attr('href').split('&bot=')[0];
            if (bot) csvUrl += '&bot=' + encodeURIComponent(bot);
            $('#orch-log-csv-btn').attr('href', csvUrl);
        });
    }
    $(document).on('change', '#orch-log-bot-filter', function() { loadLog(1); });
    $(document).on('click', '#orch-log-refresh-btn', function() { loadStats(); loadLog(1); });
    $(document).on('click', '.orch-log-page-link', function(e) {
        e.preventDefault();
        loadLog(parseInt($(this).data('page'), 10));
    });

    // ── Robots.txt ─────────────────────────────────────────────
    function loadRobots() {
        $.post(ajaxurl, {action: 'orch_ai_crawler_robots_preview', nonce: nonce}).done(function(resp) {
            if (!resp || !resp.success) return;
            $('#orch-robots-preview').text(resp.override_active ? resp.override : resp.auto_generated);
            $('#orch-robots-override').val(resp.override || '');
        });
    }
    $(document).on('click', '#orch-robots-save-btn', function() {
        var $btn = $(this).prop('disabled', true).text('Salvataggio…');
        var override = $('#orch-robots-override').val();
        $.post(ajaxurl, {action: 'orch_ai_crawler_robots_save', nonce: nonce, override: override}).done(function(resp) {
            if (resp && resp.success) {
                alert('✓ Override salvato. Ricarica /robots.txt per verificare.');
                loadRobots();
            } else {
                alert('Errore salvataggio override.');
            }
        }).always(function() { $btn.prop('disabled', false).text('💾 Salva override'); });
    });
    $(document).on('click', '#orch-robots-clear-btn', function() {
        if (!confirm('Rimuovere override? Il robots.txt tornerà ad essere auto-generated dalle Allowlist.')) return;
        $('#orch-robots-override').val('');
        $.post(ajaxurl, {action: 'orch_ai_crawler_robots_save', nonce: nonce, override: ''}).done(function() {
            loadRobots();
        });
    });

    // 3.35.80.2: expose AJAX loaders on window for cross-IIFE access
    // (consumed by tab switching block at end of template)
    window.orchAiCrawlers = window.orchAiCrawlers || {};
    window.orchAiCrawlers.loadStats = loadStats;
    window.orchAiCrawlers.loadLog = loadLog;
    window.orchAiCrawlers.loadRobots = loadRobots;

    // Initial load on page open: if log tab is initial, load
    if ($('#tab-allowlist').is(':visible')) {
        // default tab — nothing to preload
    }
})(window.jQuery);
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>

<?php ob_start(); ?>
/* 3.35.80.2: Tab switching — DEDICATED jQuery(document).ready block.
 * Runs independently from main IIFE which may bail on missing seoAeoOrchestra
 * global. Pure DOM manipulation, no AJAX dependency at this layer. */
jQuery(document).ready(function($) {
    function activateTab(targetId) {
        if (!targetId || targetId.indexOf('tab-') !== 0) targetId = 'tab-allowlist';

        // Hide all panels
        $('.orch-ai-crawlers-page .orch-tab-content').hide();
        // Remove active class from all tabs
        $('.orch-ai-crawlers-page .nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');

        var $targetPanel = $('#' + targetId);
        if ($targetPanel.length === 0) {
            // Fallback to first panel if id mismatch
            $('#tab-allowlist').show();
            $('.orch-ai-crawlers-page .nav-tab[href="#tab-allowlist"]').addClass('nav-tab-active');
            return;
        }

        $targetPanel.show();
        $('.orch-ai-crawlers-page .nav-tab[href="#' + targetId + '"]').addClass('nav-tab-active');

        // Trigger lazy-load callbacks if exposed on window.orchAiCrawlers
        var loaders = window.orchAiCrawlers || {};
        if (targetId === 'tab-log') {
            if (typeof loaders.loadStats === 'function') loaders.loadStats();
            if (typeof loaders.loadLog === 'function') loaders.loadLog(1);
        }
        if (targetId === 'tab-robots') {
            if (typeof loaders.loadRobots === 'function') loaders.loadRobots();
        }
    }

    // Expose globally for debug + workaround + diagnostic
    window.activateTab = activateTab;

    // Click handler via event delegation (resilient to DOM re-render)
    $(document).on('click', '.orch-ai-crawlers-page .nav-tab-wrapper .nav-tab', function(e) {
        e.preventDefault();
        var href = $(this).attr('href') || '';
        var targetId = href.replace('#', '');
        activateTab(targetId);

        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', '#' + targetId);
        } else {
            window.location.hash = targetId;
        }
    });

    // Initial activation: deep-link via hash, fallback to default
    var initialHash = (window.location.hash || '').replace('#', '');
    if (initialHash && initialHash.indexOf('tab-') === 0) {
        activateTab(initialHash);
    } else {
        activateTab('tab-allowlist');
    }
});
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>

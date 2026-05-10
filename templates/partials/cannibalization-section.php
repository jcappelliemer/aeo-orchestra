<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.VariableNotPrefixed
// Reason: template scope. Variables are local to this include/template,
// passed by the calling function via include/require. The Plugin Check
// heuristic doesn't distinguish template-scope locals from globals.
/**
 * Partial: Cannibalization detector section.
 *
 * Self-contained block (HTML + inline CSS + inline JS) that renders the
 * cannibalization scan card. Originariamente parte di templates/admin-dashboard.php
 * (3.9.0+, 3.11.3 fix proposal, 3.11.6 apply-with-one-click). Estratto in
 * 3.28.0 per consentire il riutilizzo nella Dashboard home e in una pagina
 * dedicata (templates/cannibalization.php).
 *
 * Tutti i selettori CSS/JS (`.orch-cannibal-*`) e gli action AJAX sono identici
 * all'implementazione originale: handler in includes/class-ajax-handlers.php
 * (`ajax_scan_cannibalization`, `ajax_cannibal_fix_proposal`, `ajax_cannibal_apply`).
 */
if (!defined('ABSPATH')) exit;

$license_key   = isset($license_key) ? $license_key : get_option('seo_aeo_orchestra_license_key', '');
$license_valid = !empty($license_key);

if (!isset($T) || !is_callable($T)) {
    $T = function($s) { return class_exists('SEO_AEO_T') ? SEO_AEO_T::t($s) : $s; };
}
?>

<div class="orchestra-v3">
<div class="orch3-card orch-cannibal-card">
    <div class="orch-cannibal-head">
        <h2 class="orch3-h2"><?php echo esc_html($T('Cannibalizzazione SEO')); ?></h2>
        <button type="button" class="orch3-btn orch3-btn-ghost orch-cannibal-toggle" aria-expanded="true"><?php echo esc_html($T('Nascondi')); ?></button>
    </div>
    <div class="orch-cannibal-body">
        <p class="orch3-muted">
            <?php echo esc_html($T('Trova pagine del sito che competono per la stessa focus keyword (cannibalizzazione). Quando 2 o più pagine targetizzano la stessa query, Google fatica a capire quale mostrare e il ranking di entrambe peggiora.')); ?>
        </p>
        <div class="orch-cannibal-runbar">
            <button type="button" class="orch3-btn orch3-btn-primary" id="orch-cannibal-scan-btn"
                <?php if (!$license_valid) echo 'disabled'; ?>>
                <?php echo esc_html($T('Esegui scansione')); ?>
            </button>
            <span class="orch3-muted orch-cannibal-hint"><?php echo esc_html($T('Gratis · scansiona post + pagine pubblicate')); ?></span>
        </div>
        <div id="orch-cannibal-results"></div>
    </div>
</div>
</div>

<?php ob_start(); ?>
/* 3.9.0 — Cannibalization detector card */
.orchestra-v3 .orch-cannibal-card { margin-top: 14px; }
.orchestra-v3 .orch-cannibal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0; gap: 10px; }
.orchestra-v3 .orch-cannibal-head .orch3-h2 { margin: 0; }
.orchestra-v3 .orch-cannibal-toggle { flex-shrink: 0; }
.orchestra-v3 .orch-cannibal-body { margin-top: 12px; }
.orchestra-v3 .orch-cannibal-runbar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin: 12px 0 6px; }
.orchestra-v3 .orch-cannibal-hint { font-size: 12px; }
.orchestra-v3 .orch-cannibal-error { margin-top: 10px; padding: 10px 12px; background: #fee2e2; color: #991b1b; border-left: 3px solid #dc2626; border-radius: 6px; font-size: 13px; }
.orchestra-v3 .orch-cannibal-stats { margin-top: 14px; display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px; }
.orchestra-v3 .orch-cannibal-stat { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; text-align: center; }
.orchestra-v3 .orch-cannibal-stat strong { display: block; font-size: 22px; font-weight: 700; color: #1f2937; line-height: 1.1; }
.orchestra-v3 .orch-cannibal-stat span { display: block; font-size: 11.5px; color: #475569; margin-top: 2px; }
.orchestra-v3 .orch-cannibal-ok strong   { color: #166534; }
.orchestra-v3 .orch-cannibal-warn strong { color: #92400e; }
.orchestra-v3 .orch-cannibal-bad strong  { color: #991b1b; }
.orchestra-v3 .orch-cannibal-empty { margin-top: 12px; padding: 14px 16px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; color: #065f46; font-size: 13px; }
.orchestra-v3 .orch-cannibal-groups { margin-top: 16px; display: flex; flex-direction: column; gap: 12px; }
.orchestra-v3 .orch-cannibal-group { background: #fff; border: 1px solid #e5e7eb; border-left: 4px solid #dc2626; border-radius: 8px; padding: 12px 14px; }
.orchestra-v3 .orch-cannibal-group-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 8px; }
.orchestra-v3 .orch-cannibal-kw { font-weight: 600; font-size: 14px; color: #1f2937; }
.orchestra-v3 .orch-cannibal-count { background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
.orchestra-v3 .orch-cannibal-pages { margin: 0 0 10px 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 6px; }
.orchestra-v3 .orch-cannibal-page { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; }
.orchestra-v3 .orch-cannibal-page-title { font-weight: 500; font-size: 13px; color: #1f2937; }
.orchestra-v3 .orch-cannibal-page-meta { margin-top: 3px; font-size: 11.5px; color: #475569; }
.orchestra-v3 .orch-cannibal-page-meta a { color: #2563eb; text-decoration: none; }
.orchestra-v3 .orch-cannibal-page-meta a:hover { text-decoration: underline; }
.orchestra-v3 .orch-cannibal-kw-variant { color: #92400e; font-size: 12px; font-style: italic; }
.orchestra-v3 .orch-cannibal-suggest { margin-top: 6px; padding: 8px 10px; background: #fff7ed; border-radius: 6px; font-size: 12px; color: #7c2d12; line-height: 1.5; }
.orchestra-v3 .orch-cannibal-suggest code { background: #fde68a; padding: 1px 5px; border-radius: 3px; font-size: 11px; }

/* 3.11.3 — Risolvi con AI (cannibalization fix proposal) */
.orchestra-v3 .orch-cannibal-fix-bar { margin-top: 10px; padding-top: 10px; border-top: 1px dashed #e5e7eb; }
.orchestra-v3 .orch-cannibal-fix-cost { font-size: 11px; opacity: 0.85; font-weight: 400; }
.orchestra-v3 .orch-cannibal-fix-disclaimer { margin-top: 8px; padding: 8px 10px; background: #f0f9ff; border-left: 3px solid #0284c7; border-radius: 4px; font-size: 12px; color: #075985; line-height: 1.55; display: flex; gap: 8px; align-items: flex-start; }
.orchestra-v3 .orch-cannibal-fix-shield { font-size: 14px; flex-shrink: 0; }
.orchestra-v3 .orch-cannibal-fix-banner { margin: -4px -4px 12px -4px; padding: 10px 12px; background: linear-gradient(90deg, #ecfeff 0%, #f0f9ff 100%); border: 1px solid #67e8f9; border-radius: 6px; font-size: 12px; color: #155e75; line-height: 1.55; display: flex; gap: 10px; align-items: flex-start; }
.orchestra-v3 .orch-cannibal-fix-title code { background: #e0e7ff; color: #3730a3; padding: 2px 6px; border-radius: 4px; font-size: 12px; font-weight: 500; }
.orchestra-v3 .orch-cannibal-fix-result:empty { display: none; }
.orchestra-v3 .orch-cannibal-fix-box { margin-top: 10px; padding: 14px; background: linear-gradient(180deg, #eff6ff 0%, #f8fafc 100%); border: 1px solid #bfdbfe; border-radius: 8px; }
.orchestra-v3 .orch-cannibal-fix-title { font-size: 13px; font-weight: 600; color: #0055FF; margin-bottom: 10px; }
.orchestra-v3 .orch-cannibal-fix-primary,
.orchestra-v3 .orch-cannibal-fix-support { background: #ffffff; border-radius: 6px; padding: 10px 12px; margin-bottom: 8px; border-left: 3px solid transparent; }
.orchestra-v3 .orch-cannibal-fix-primary { border-left-color: #10B981; }
.orchestra-v3 .orch-cannibal-fix-support { border-left-color: #F59E0B; }
.orchestra-v3 .orch-cannibal-fix-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.orchestra-v3 .orch-cannibal-fix-badge-primary { background: #d1fae5; color: #047857; }
.orchestra-v3 .orch-cannibal-fix-badge-support { background: #fef3c7; color: #B45309; }
.orchestra-v3 .orch-cannibal-fix-pid { font-size: 12px; color: #475569; margin-bottom: 4px; }
.orchestra-v3 .orch-cannibal-fix-pid a { color: #0055FF; text-decoration: none; }
.orchestra-v3 .orch-cannibal-fix-keep { font-size: 13px; }
.orchestra-v3 .orch-cannibal-fix-newkw { font-size: 13px; margin: 4px 0; }
.orchestra-v3 .orch-cannibal-fix-keep code,
.orchestra-v3 .orch-cannibal-fix-newkw code { background: #fff; border: 1px solid #d1d5db; padding: 2px 6px; border-radius: 4px; font-size: 12px; color: #0a0e27; }
.orchestra-v3 .orch-cannibal-fix-reason { font-size: 12px; color: #64748b; margin-top: 4px; line-height: 1.5; font-style: italic; }
.orchestra-v3 .orch-cannibal-fix-link { margin-top: 6px; padding: 8px 10px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 5px; font-size: 12px; line-height: 1.5; }
.orchestra-v3 .orch-cannibal-fix-link-label { font-weight: 600; color: #047857; margin-bottom: 3px; }
.orchestra-v3 .orch-cannibal-fix-hint-text { color: #4b5563; margin-top: 3px; }
.orchestra-v3 .orch-cannibal-fix-cta { margin-top: 10px; padding: 10px 12px; background: #fffbeb; border-left: 3px solid #f59e0b; border-radius: 5px; font-size: 12px; color: #78350f; line-height: 1.55; }

/* 3.11.6 — Apply con un click (cannibalization) */
.orchestra-v3 .orch-cannibal-apply-bar { margin-top: 10px; padding-top: 8px; border-top: 1px dashed #e5e7eb; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.orchestra-v3 .orch-cannibal-apply-hint { font-size: 11px; color: #475569; line-height: 1.5; }
.orchestra-v3 .orch-cannibal-apply-result:empty { display: none; }
.orchestra-v3 .orch-cannibal-apply-success-inline { margin-top: 8px; padding: 8px 10px; background: #d1fae5; border-left: 3px solid #10b981; border-radius: 4px; font-size: 12px; color: #065f46; line-height: 1.55; }
.orchestra-v3 .orch-cannibal-apply-success-inline code { background: #ffffff; padding: 1px 5px; border-radius: 3px; font-size: 11px; }
.orch-cannibal-apply-warn { padding: 10px 12px; background: #f0f9ff; border-left: 3px solid #0284c7; border-radius: 4px; font-size: 12px; color: #075985; display: flex; gap: 8px; align-items: flex-start; line-height: 1.55; }
.orch-cannibal-apply-opt { display: flex; gap: 10px; align-items: flex-start; padding: 10px 12px; margin-top: 6px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; line-height: 1.55; }
.orch-cannibal-apply-opt:hover { background: #f1f5f9; border-color: #cbd5e1; }
.orch-cannibal-apply-opt input[type="checkbox"] { margin-top: 3px; flex-shrink: 0; }
.orch-cannibal-apply-success { padding: 12px 14px; background: #d1fae5; border-left: 3px solid #10b981; border-radius: 5px; color: #065f46; font-size: 13px; line-height: 1.6; }
.orch-cannibal-apply-success code { background: #ffffff; padding: 2px 6px; border-radius: 3px; font-size: 12px; color: #065f46; }

/* Modal generico (riutilizzabile) — duplicato qui per autonomia del partial */
.orch-modal-backdrop { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(10, 14, 39, 0.6); z-index: 100000; display: flex; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(2px); }
.orch-modal-window { background: #ffffff; border-radius: 10px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); width: 100%; max-width: 540px; max-height: 90vh; overflow: auto; font-family: -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif; }
.orch-modal-head { padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
.orch-modal-head h3 { margin: 0; font-size: 16px; color: #0a0e27; }
.orch-modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; line-height: 1; padding: 0; width: 28px; height: 28px; }
.orch-modal-close:hover { color: #0a0e27; }
.orch-modal-body { padding: 20px; font-size: 13px; color: #334155; line-height: 1.55; }
.orch-modal-body code { background: #f1f5f9; border: 1px solid #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 12px; color: #0a0e27; }
.orch-modal-foot { padding: 14px 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 10px; }
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

<script type="text/javascript">
/* ═════════════════════════════════════════════════════════
   3.9.0 — Cannibalization detector (extracted to partial 3.28.0)
   ═════════════════════════════════════════════════════════ */
jQuery(function($) {
    var $card = $('.orch-cannibal-card');
    if (!$card.length) return;
    if ($card.data('orch-cannibal-init')) return;
    $card.data('orch-cannibal-init', true);

    var $body = $card.find('.orch-cannibal-body');
    var $toggle = $card.find('.orch-cannibal-toggle');
    var $btn = $('#orch-cannibal-scan-btn');
    var $results = $('#orch-cannibal-results');

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    $toggle.on('click', function() {
        var open = $body.is(':visible');
        $body.slideToggle(180);
        $toggle.attr('aria-expanded', open ? 'false' : 'true').text(open ? SeoAeoOrchestra.t('Mostra') : SeoAeoOrchestra.t('Nascondi'));
    });

    $btn.on('click', function() {
        var origLabel = $btn.text();
        $btn.prop('disabled', true).html('<span class="rv-spinner"></span> ' + SeoAeoOrchestra.t('Scansione…'));
        $results.html('<div class="orch3-muted" style="padding:10px 0;">' + SeoAeoOrchestra.t('Lettura focus keywords da tutte le pagine pubblicate…') + '</div>');

        $.post(ajaxurl, {
            action: 'seo_aeo_orchestra_scan_cannibalization',
            nonce: seoAeoOrchestra.nonce
        }).done(function(resp) {
            if (!resp || resp.error) {
                var errMsg = resp && (resp.message || (typeof resp.error === 'string' ? resp.error : '')) || resp.detail || SeoAeoOrchestra.t('Errore sconosciuto');
                $results.html('<div class="orch-cannibal-error">' + escapeHtml(errMsg) + '</div>');
                return;
            }
            $results.html(renderCannibalResults(resp));
        }).fail(function(xhr) {
            $results.html('<div class="orch-cannibal-error">' + SeoAeoOrchestra.t('Errore rete') + ' (' + xhr.status + ')</div>');
        }).always(function() {
            $btn.prop('disabled', false).text(origLabel);
        });
    });

    function renderCannibalResults(r) {
        var groups = Array.isArray(r.groups) ? r.groups : [];
        var total = parseInt(r.total_pages_scanned || 0, 10);
        var withKw = parseInt(r.pages_with_keywords || 0, 10);
        var withoutKw = parseInt(r.pages_without_keywords || 0, 10);
        var rate = parseFloat(r.cannibalization_rate || 0) * 100;

        var summary = '<div class="orch-cannibal-stats">' +
                        '<div class="orch-cannibal-stat"><strong>' + total + '</strong><span>' + SeoAeoOrchestra.t('pagine scansionate') + '</span></div>' +
                        '<div class="orch-cannibal-stat"><strong>' + withKw + '</strong><span>' + SeoAeoOrchestra.t('con focus keyword') + '</span></div>' +
                        '<div class="orch-cannibal-stat orch-cannibal-warn"><strong>' + withoutKw + '</strong><span>' + SeoAeoOrchestra.t('senza focus keyword') + '</span></div>' +
                        '<div class="orch-cannibal-stat ' + (groups.length ? 'orch-cannibal-bad' : 'orch-cannibal-ok') + '">' +
                          '<strong>' + groups.length + '</strong><span>' + SeoAeoOrchestra.t('conflitti rilevati') + '</span>' +
                        '</div>' +
                        '<div class="orch-cannibal-stat ' + (rate > 0 ? 'orch-cannibal-bad' : 'orch-cannibal-ok') + '">' +
                          '<strong>' + rate.toFixed(1) + '%</strong><span>' + SeoAeoOrchestra.t('tasso cannibalizzazione') + '</span>' +
                        '</div>' +
                      '</div>';

        if (!groups.length) {
            return summary +
                '<div class="orch-cannibal-empty">' +
                  '<strong>' + SeoAeoOrchestra.t('Nessuna cannibalizzazione rilevata.') + '</strong> ' + SeoAeoOrchestra.t('Ogni focus keyword è univoca tra le pagine pubblicate.') +
                '</div>';
        }

        var groupsHtml = groups.map(function(g, gi) {
            var pageIds = g.pages.map(function(p) { return parseInt(p.post_id, 10); }).filter(function(x){return x>0;});
            var pagesHtml = g.pages.map(function(p) {
                var titleSafe = escapeHtml(p.title || '(senza titolo)');
                var urlSafe = escapeHtml(p.url || '');
                var origKw = p.original_keyword !== g.keyword
                    ? ' <span class="orch-cannibal-kw-variant">(' + escapeHtml(p.original_keyword) + ')</span>'
                    : '';
                var editUrl = p.post_id ?
                    ('post.php?post=' + parseInt(p.post_id, 10) + '&action=edit') : '';
                return '<li class="orch-cannibal-page" data-post-id="' + parseInt(p.post_id, 10) + '">' +
                         '<div class="orch-cannibal-page-title">' + titleSafe + origKw + '</div>' +
                         '<div class="orch-cannibal-page-meta">' +
                           '<a href="' + urlSafe + '" target="_blank" rel="noopener">' + urlSafe + '</a>' +
                           (editUrl ? ' · <a href="' + editUrl + '">' + SeoAeoOrchestra.t('Modifica') + '</a>' : '') +
                         '</div>' +
                       '</li>';
            }).join('');

            return '<div class="orch-cannibal-group" data-group-idx="' + gi + '" data-keyword="' + escapeHtml(g.keyword) + '" data-post-ids="' + pageIds.join(',') + '">' +
                     '<div class="orch-cannibal-group-head">' +
                       '<span class="orch-cannibal-kw">"' + escapeHtml(g.keyword) + '"</span>' +
                       '<span class="orch-cannibal-count">' + g.count + ' ' + SeoAeoOrchestra.t('pagine') + '</span>' +
                     '</div>' +
                     '<ul class="orch-cannibal-pages">' + pagesHtml + '</ul>' +
                     '<div class="orch-cannibal-suggest">' +
                       '<strong>' + SeoAeoOrchestra.t('Cosa fare:') + '</strong> ' + SeoAeoOrchestra.t('consolida il contenuto migliore su una sola pagina e re-targetizza le altre con keyword correlate ma distinte (long-tail, varianti semantiche). In alternativa, usa') + ' <code>rel=canonical</code> ' + SeoAeoOrchestra.t('verso la pagina principale.') +
                     '</div>' +
                     '<div class="orch-cannibal-fix-bar">' +
                       '<button type="button" class="orch3-btn orch3-btn-primary orch3-btn-sm orch-cannibal-fix-btn">' +
                         '⚡ ' + SeoAeoOrchestra.t('Genera proposta AI') + ' ' +
                         '<span class="orch-cannibal-fix-cost">(15 ' + SeoAeoOrchestra.t('crediti') + ')</span>' +
                       '</button>' +
                       '<div class="orch-cannibal-fix-disclaimer">' +
                         '<span class="orch-cannibal-fix-shield">🔒</span>' +
                         '<span><strong>' + SeoAeoOrchestra.t('Nessuna modifica al sito.') + '</strong> ' + SeoAeoOrchestra.t('Genera solo una proposta da rivedere: l\'AI sceglie la pagina primaria, suggerisce keyword alternative per le altre e propone link interni. Decidi tu cosa applicare.') + '</span>' +
                       '</div>' +
                     '</div>' +
                     '<div class="orch-cannibal-fix-result"></div>' +
                   '</div>';
        }).join('');

        return summary + '<div class="orch-cannibal-groups">' + groupsHtml + '</div>';
    }

    // ══════ Risolvi con AI (3.11.3) ══════════════════════════════════
    $(document).on('click', '.orch-cannibal-fix-btn', function() {
        var $btn = $(this);
        var $group = $btn.closest('.orch-cannibal-group');
        var $result = $group.find('.orch-cannibal-fix-result');
        var keyword = $group.attr('data-keyword') || '';
        var postIdsStr = $group.attr('data-post-ids') || '';
        var postIds = postIdsStr.split(',').map(function(x){return parseInt(x,10);}).filter(function(x){return x>0;});

        if (!keyword || postIds.length < 2) {
            $result.html('<div class="orch-cannibal-error">' + SeoAeoOrchestra.t('Dati gruppo non validi.') + '</div>');
            return;
        }

        var origLabel = $btn.html();
        $btn.prop('disabled', true).html('<span class="rv-spinner"></span> ' + SeoAeoOrchestra.t('Analisi AI in corso…'));
        $result.html('<div class="orch3-muted" style="padding:12px 0;"><span class="rv-spinner"></span> ' + SeoAeoOrchestra.t('L\'AI sta valutando le pagine…') + '</div>');

        $.post(ajaxurl, {
            action: 'seo_aeo_orchestra_cannibal_fix_proposal',
            nonce: seoAeoOrchestra.nonce,
            keyword: keyword,
            post_ids: postIds
        }).done(function(resp) {
            if (!resp || resp.error) {
                var errMsg = resp && (resp.message || (typeof resp.error === 'string' ? resp.error : '')) || resp.detail || SeoAeoOrchestra.t('Analisi AI fallita.');
                $result.html('<div class="orch-cannibal-error">' + escapeHtml(errMsg) + '</div>');
                return;
            }
            $result.html(renderCannibalFix(resp));
        }).fail(function(xhr) {
            $result.html('<div class="orch-cannibal-error">' + SeoAeoOrchestra.t('Errore rete') + ' (' + xhr.status + ')</div>');
        }).always(function() {
            $btn.prop('disabled', false).html(origLabel);
        });
    });

    function renderCannibalFix(r) {
        var suggestions = Array.isArray(r.suggestions) ? r.suggestions : [];
        var primaryId = parseInt(r.primary_post_id, 10);
        var primary = suggestions.find(function(s){return s.role==='primary';});
        var supporting = suggestions.filter(function(s){return s.role==='supporting';});

        var primaryHtml = primary ? (
            '<div class="orch-cannibal-fix-primary">' +
              '<div class="orch-cannibal-fix-badge orch-cannibal-fix-badge-primary">⭐ ' + SeoAeoOrchestra.t('Pagina primaria') + '</div>' +
              '<div class="orch-cannibal-fix-pid">Post ID #' + primaryId + ' · <a href="post.php?post=' + primaryId + '&action=edit">' + SeoAeoOrchestra.t('Modifica') + '</a></div>' +
              '<div class="orch-cannibal-fix-keep">' + SeoAeoOrchestra.t('Mantiene la keyword') + ' <code>' + escapeHtml(r.keyword) + '</code></div>' +
              (primary.reasoning ? '<div class="orch-cannibal-fix-reason">' + escapeHtml(primary.reasoning) + '</div>' : '') +
            '</div>'
        ) : '';

        var supportHtml = supporting.map(function(s) {
            var pid = parseInt(s.post_id, 10);
            var newKw = s.suggested_keyword || '';
            var link = s.internal_link || {};
            var hasKw = !!newKw;
            var hasLink = !!link.anchor_text;
            var dataAttrs =
                ' data-post-id="' + pid + '"' +
                ' data-new-keyword="' + escapeHtml(newKw) + '"' +
                ' data-link-anchor="' + escapeHtml(link.anchor_text || '') + '"' +
                ' data-link-target-pid="' + parseInt(link.target_post_id || 0, 10) + '"' +
                ' data-link-hint="' + escapeHtml(link.paragraph_hint || '') + '"';

            var linkBlock = hasLink ? (
                '<div class="orch-cannibal-fix-link">' +
                  '<div class="orch-cannibal-fix-link-label">🔗 ' + SeoAeoOrchestra.t('Link interno suggerito') + '</div>' +
                  '<div>' + SeoAeoOrchestra.t('Anchor:') + ' <strong>' + escapeHtml(link.anchor_text) + '</strong></div>' +
                  (link.paragraph_hint ? '<div class="orch-cannibal-fix-hint-text">' + SeoAeoOrchestra.t('Dove inserirlo:') + ' ' + escapeHtml(link.paragraph_hint) + '</div>' : '') +
                  '<div class="orch3-muted" style="font-size:11px;margin-top:4px;">' + SeoAeoOrchestra.t('Punta a Post #') + parseInt(link.target_post_id,10) + '</div>' +
                '</div>'
            ) : '';

            var applyBar = (hasKw || hasLink) ? (
                '<div class="orch-cannibal-apply-bar">' +
                  '<button type="button" class="orch3-btn orch3-btn-primary orch3-btn-sm orch-cannibal-apply-btn"' + dataAttrs + '>' +
                    '✏️ ' + SeoAeoOrchestra.t('Applica con un click') +
                  '</button>' +
                  '<span class="orch-cannibal-apply-hint">🔒 ' + SeoAeoOrchestra.t('Sicurezza: viene creato uno snapshot per ripristino. Niente è irreversibile.') + '</span>' +
                '</div>'
            ) : '';

            return '<div class="orch-cannibal-fix-support" data-support-pid="' + pid + '">' +
                     '<div class="orch-cannibal-fix-badge orch-cannibal-fix-badge-support">↪ ' + SeoAeoOrchestra.t('Supporting') + '</div>' +
                     '<div class="orch-cannibal-fix-pid">Post ID #' + pid + ' · <a href="post.php?post=' + pid + '&action=edit" target="_blank">' + SeoAeoOrchestra.t('Modifica nel CMS') + '</a></div>' +
                     (newKw ? '<div class="orch-cannibal-fix-newkw">' + SeoAeoOrchestra.t('Nuova focus keyword:') + ' <code>' + escapeHtml(newKw) + '</code></div>' : '') +
                     (s.reasoning ? '<div class="orch-cannibal-fix-reason">' + escapeHtml(s.reasoning) + '</div>' : '') +
                     linkBlock +
                     applyBar +
                     '<div class="orch-cannibal-apply-result"></div>' +
                   '</div>';
        }).join('');

        var creditsLine = r.credits_consumed
            ? '<div class="orch3-muted" style="font-size:11px;margin-top:8px;">Hai consumato ' + parseInt(r.credits_consumed, 10) + ' crediti per questa analisi.</div>'
            : '';

        return '<div class="orch-cannibal-fix-box">' +
                 '<div class="orch-cannibal-fix-banner">' +
                   '<span class="orch-cannibal-fix-shield">🔒</span>' +
                   '<span><strong>Nessuna modifica è stata applicata al sito.</strong> Quella che vedi sotto è una proposta dell\'AI. Per metterla in atto, segui le istruzioni a fondo riquadro.</span>' +
                 '</div>' +
                 '<div class="orch-cannibal-fix-title">💡 Proposta AI per la keyword <code>' + escapeHtml(r.keyword) + '</code></div>' +
                 primaryHtml +
                 supportHtml +
                 '<div class="orch-cannibal-fix-cta">' +
                   '<strong>📋 Come applicare la proposta:</strong>' +
                   '<ol style="margin:8px 0 0 18px; padding:0;">' +
                     '<li><strong>Click "Applica con un click"</strong> sul riquadro di ogni pagina Supporting.</li>' +
                     '<li>Conferma cosa applicare nel popup (keyword e/o link interno).</li>' +
                     '<li>Le modifiche vengono fatte in automatico, con uno snapshot di ripristino per ogni pagina.</li>' +
                     '<li>Il link "Modifica nel CMS" su ogni Supporting ti permette di rivedere/correggere a mano.</li>' +
                   '</ol>' +
                 '</div>' +
                 creditsLine +
               '</div>';
    }

    // ══════ Apply con un click (3.11.6) ════════════════════════════════
    $(document).on('click', '.orch-cannibal-apply-btn', function() {
        var $btn = $(this);
        var pid = parseInt($btn.attr('data-post-id'), 10);
        var newKw = $btn.attr('data-new-keyword') || '';
        var anchor = $btn.attr('data-link-anchor') || '';
        var targetPid = parseInt($btn.attr('data-link-target-pid'), 10);
        var hint = $btn.attr('data-link-hint') || '';

        openCannibalApplyModal({
            $btn: $btn,
            post_id: pid,
            new_keyword: newKw,
            link_anchor: anchor,
            link_target_pid: targetPid,
            link_hint: hint
        });
    });

    function openCannibalApplyModal(data) {
        $('#orch-cannibal-apply-modal').remove();

        var hasKw = !!data.new_keyword;
        var hasLink = !!data.link_anchor && data.link_target_pid > 0;

        var kwRow = hasKw ? (
            '<label class="orch-cannibal-apply-opt">' +
              '<input type="checkbox" id="orch-cannibal-apply-kw" checked>' +
              '<span><strong>Cambia focus keyword</strong> a <code>' + escapeHtml(data.new_keyword) + '</code></span>' +
            '</label>'
        ) : '';

        var linkRow = hasLink ? (
            '<label class="orch-cannibal-apply-opt">' +
              '<input type="checkbox" id="orch-cannibal-apply-link" checked>' +
              '<span><strong>Inserisci link interno</strong> con anchor "<em>' + escapeHtml(data.link_anchor) + '</em>" verso la pagina primaria' +
              (data.link_hint ? '<br><span class="orch3-muted" style="font-size:11px;">L\'AI suggerisce: ' + escapeHtml(data.link_hint) + '</span>' : '') +
              '</span>' +
            '</label>'
        ) : '';

        var html =
            '<div id="orch-cannibal-apply-modal" class="orch-modal-backdrop">' +
              '<div class="orch-modal-window">' +
                '<div class="orch-modal-head">' +
                  '<h3>Applica modifiche al post #' + data.post_id + '</h3>' +
                  '<button type="button" class="orch-modal-close" aria-label="Chiudi">×</button>' +
                '</div>' +
                '<div class="orch-modal-body">' +
                  '<div class="orch-cannibal-apply-warn">' +
                    '<span>🔒</span>' +
                    '<span><strong>Le modifiche sono reversibili.</strong> Per ogni cambio viene creato uno snapshot. Puoi annullare in qualsiasi momento dalla cronologia "Modifiche recenti".</span>' +
                  '</div>' +
                  '<p style="margin:10px 0 8px;">Seleziona cosa applicare:</p>' +
                  kwRow +
                  linkRow +
                  '<div id="orch-cannibal-apply-status" style="margin-top:10px;"></div>' +
                '</div>' +
                '<div class="orch-modal-foot">' +
                  '<button type="button" class="orch3-btn orch3-btn-ghost orch-modal-cancel">Annulla</button>' +
                  '<button type="button" class="orch3-btn orch3-btn-primary" id="orch-cannibal-apply-confirm">✏️ Applica selezionati</button>' +
                '</div>' +
              '</div>' +
            '</div>';

        var $modal = $(html).appendTo('body');

        $modal.find('.orch-modal-close, .orch-modal-cancel').on('click', function() {
            $modal.remove();
        });
        $modal.find('.orch-modal-backdrop, #orch-cannibal-apply-modal').on('click', function(e) {
            if (e.target === this) $modal.remove();
        });

        $modal.find('#orch-cannibal-apply-confirm').on('click', function() {
            var $confirmBtn = $(this);
            var apply_kw = $modal.find('#orch-cannibal-apply-kw').prop('checked') ? 1 : 0;
            var apply_link = $modal.find('#orch-cannibal-apply-link').prop('checked') ? 1 : 0;
            var $status = $modal.find('#orch-cannibal-apply-status');

            if (!apply_kw && !apply_link) {
                $status.html('<div class="orch-cannibal-error">Seleziona almeno un\'azione.</div>');
                return;
            }

            $confirmBtn.prop('disabled', true).text('Applico…');
            $status.html('<div class="orch3-muted"><span class="rv-spinner"></span> Modifica in corso…</div>');

            $.post(ajaxurl, {
                action: 'seo_aeo_orchestra_cannibal_apply',
                nonce: seoAeoOrchestra.nonce,
                post_id: data.post_id,
                apply_keyword: apply_kw,
                new_keyword: data.new_keyword,
                apply_link: apply_link,
                link_anchor: data.link_anchor,
                link_target_post_id: data.link_target_pid,
                link_paragraph_hint: data.link_hint
            }).done(function(resp) {
                if (!resp || resp.error) {
                    var errMsg = resp && (resp.message || (typeof resp.error === 'string' ? resp.error : '')) || resp.detail || 'Errore sconosciuto';
                    $status.html('<div class="orch-cannibal-error">' + escapeHtml(errMsg) + '</div>');
                    $confirmBtn.prop('disabled', false).text('✏️ Applica selezionati');
                    return;
                }

                var appliedList = (resp.applied || []).map(function(a) {
                    if (a.type === 'keyword') return '✓ Keyword cambiata: <code>' + escapeHtml(a.previous || '(vuota)') + '</code> → <code>' + escapeHtml(a.new) + '</code>';
                    if (a.type === 'link') return '✓ Link inserito: "' + escapeHtml(a.anchor) + '" → ' + escapeHtml(a.target_url);
                    if (a.type === 'link_skipped') return '⏭ Link non inserito: ' + escapeHtml(a.reason || '');
                    return '✓ ' + escapeHtml(JSON.stringify(a));
                }).join('<br>');

                $status.html(
                    '<div class="orch-cannibal-apply-success">' +
                      '<strong>✅ Modifiche applicate</strong><br>' +
                      appliedList +
                      (resp.snapshot_ids && resp.snapshot_ids.length
                        ? '<div style="margin-top:6px;font-size:11px;opacity:0.85;">Snapshot ID: ' + escapeHtml(resp.snapshot_ids.join(', ')) + ' (puoi ripristinare dalla cronologia)</div>'
                        : '') +
                    '</div>'
                );

                if (data.$btn && data.$btn.length) {
                    data.$btn.prop('disabled', true).html('✓ Applicato');
                    data.$btn.closest('.orch-cannibal-fix-support').find('.orch-cannibal-apply-result').html(
                        '<div class="orch-cannibal-apply-success-inline">✓ ' + appliedList + '</div>'
                    );
                }

                $confirmBtn.text('Chiudi').prop('disabled', false).off('click').on('click', function() {
                    $modal.remove();
                });
            }).fail(function(xhr) {
                $status.html('<div class="orch-cannibal-error">Errore rete (' + xhr.status + ')</div>');
                $confirmBtn.prop('disabled', false).text('✏️ Applica selezionati');
            });
        });
    }
});
</script>

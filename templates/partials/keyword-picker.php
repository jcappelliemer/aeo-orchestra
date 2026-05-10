<?php
/*
 * Partial: Keyword Picker (since 3.23.2)
 *
 * Pill + drawer per pescare keyword dai set salvati di Keyword Research Autopilot
 * e iniettarle in input form della pagina corrente. Riutilizzabile.
 *
 * Configurazione tramite array `$seo_aeo_kp` definito PRIMA dell'include:
 *   $seo_aeo_kp = array(
 *     'mode'      => 'csv' | 'single',          // csv = aggiunge a campo CSV (Contenuti AI/AEO), single = sostituisce campo singolo
 *     'targets'   => array(
 *        '#input-id-1' => 'keyword',            // valore: 'keyword' (la kw) o 'topic' (idea articolo)
 *        '#input-id-2' => 'topic',
 *     ),
 *     'separator' => ', ',                       // per mode csv
 *     'label'     => 'Pesca da Keyword Research', // label custom pill
 *   );
 *
 * Esempio d'uso (Contenuti AI):
 *   $seo_aeo_kp = array('mode' => 'csv', 'targets' => array('#content-keywords' => 'keyword', '#content-topic' => 'topic'));
 *   include __DIR__ . '/partials/keyword-picker.php';
 */
if (!defined('ABSPATH')) exit;

if (!isset($seo_aeo_kp) || !is_array($seo_aeo_kp)) $seo_aeo_kp = array();
$seo_aeo_kp_mode      = isset($seo_aeo_kp['mode']) ? $seo_aeo_kp['mode'] : 'csv';
$seo_aeo_kp_targets   = isset($seo_aeo_kp['targets']) && is_array($seo_aeo_kp['targets']) ? $seo_aeo_kp['targets'] : array();
$seo_aeo_kp_separator = isset($seo_aeo_kp['separator']) ? $seo_aeo_kp['separator'] : ', ';
$seo_aeo_kp_label     = isset($seo_aeo_kp['label']) ? $seo_aeo_kp['label'] : 'Pesca da Keyword Research';
if (class_exists('SEO_AEO_T')) $seo_aeo_kp_label = SEO_AEO_T::t($seo_aeo_kp_label);
$seo_aeo_kp_uid       = 'kp-' . substr(md5(serialize($seo_aeo_kp_targets) . microtime(true)), 0, 8);
?>

<div class="orch-kp-bar">
    <button type="button" class="orch-kp-pill" data-uid="<?php echo esc_attr($seo_aeo_kp_uid); ?>">
        <span class="orch-kp-icon">📥</span>
        <span class="orch-kp-label"><?php echo esc_html($seo_aeo_kp_label); ?></span>
        <span class="orch-kp-count" data-orch-kp-count>—</span>
    </button>
    <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-keyword-research')); ?>" class="orch-kp-link"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('+ Nuova ricerca →')) : '+ Nuova ricerca →'; ?></a>
</div>

<div class="orch-kp-backdrop" id="orch-kp-bd-<?php echo esc_attr($seo_aeo_kp_uid); ?>">
    <aside class="orch-kp-drawer">
        <div class="orch-kp-drawer-head">
            <div>
                <div class="orch-kp-drawer-eyebrow">📥 Set di keyword salvati</div>
                <h3 class="orch-kp-drawer-title">Pesca dalle ricerche fatte</h3>
                <p class="orch-kp-drawer-sub">Seleziona uno o piu set, scegli le keyword, click "Usa selezionate" → il modulo si compila.</p>
            </div>
            <button type="button" class="orch-kp-drawer-close">×</button>
        </div>

        <div class="orch-kp-drawer-body" data-orch-kp-body>
            <div class="orch-kp-empty" data-orch-kp-empty>
                <div class="orch-kp-empty-icon">🎯</div>
                <h4>Nessun set ancora</h4>
                <p>Crea il tuo primo set da <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-keyword-research')); ?>">🎯 Keyword Research</a>.</p>
            </div>
        </div>

        <div class="orch-kp-drawer-foot">
            <div class="orch-kp-selection-status" data-orch-kp-selection>0 keyword selezionate</div>
            <div class="orch-kp-drawer-actions">
                <button type="button" class="button orch-kp-cancel">Annulla</button>
                <button type="button" class="button button-primary orch-kp-apply" disabled>📥 Usa selezionate</button>
            </div>
        </div>
    </aside>
</div>

<?php ob_start(); ?>
.orch-kp-bar { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin: 12px 0 16px; }
.orch-kp-pill { display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; background: linear-gradient(90deg, #ecfdf5, #d1fae5); border: 1px solid #6ee7b7; border-radius: 999px; cursor: pointer; font-size: 13px; font-weight: 600; color: #065f46; transition: all 0.15s; }
.orch-kp-pill:hover { background: linear-gradient(90deg, #d1fae5, #a7f3d0); transform: translateY(-1px); }
.orch-kp-icon { font-size: 16px; line-height: 1; }
.orch-kp-count { background: #059669; color: #fff; padding: 1px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; }
.orch-kp-link { font-size: 12px; color: #059669; text-decoration: none; font-weight: 600; }
.orch-kp-link:hover { color: #047857; }

/* Drawer */
.orch-kp-backdrop { position: fixed; inset: 0; background: rgba(10,14,39,0.6); backdrop-filter: blur(3px); z-index: 100000; display: none; }
.orch-kp-backdrop.open { display: block; }
.orch-kp-drawer { position: fixed; top: 0; right: -640px; width: 620px; max-width: 100vw; height: 100vh; background: #fff; box-shadow: -16px 0 48px rgba(0,0,0,0.3); display: flex; flex-direction: column; transition: right 0.28s cubic-bezier(.2,.8,.2,1); z-index: 100001; }
.orch-kp-backdrop.open .orch-kp-drawer { right: 0; }
.orch-kp-drawer-head { padding: 22px 26px; background: linear-gradient(135deg, #0A0E27, #134e4a, #059669); color: #fff; display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
.orch-kp-drawer-eyebrow { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.85; font-weight: 600; }
.orch-kp-drawer-title { margin: 6px 0 4px; font-size: 22px; font-weight: 700; }
.orch-kp-drawer-sub { margin: 0; font-size: 13px; opacity: 0.85; line-height: 1.45; }
.orch-kp-drawer-close { background: rgba(255,255,255,0.18); border: none; color: #fff; width: 36px; height: 36px; border-radius: 50%; font-size: 22px; cursor: pointer; flex-shrink: 0; }
.orch-kp-drawer-body { flex: 1; overflow-y: auto; padding: 18px 22px; }

.orch-kp-empty { text-align: center; padding: 60px 20px; color: #6b7280; }
.orch-kp-empty-icon { font-size: 48px; margin-bottom: 12px; opacity: 0.4; }
.orch-kp-empty h4 { margin: 0 0 8px; color: #1f2937; }
.orch-kp-empty a { color: #059669; font-weight: 600; }

.orch-kp-set { background: #fff; border: 1.5px solid #e5e7eb; border-radius: 10px; margin-bottom: 12px; overflow: hidden; }
.orch-kp-set-head { padding: 12px 16px; background: #f9fafb; cursor: pointer; display: flex; justify-content: space-between; align-items: center; gap: 10px; }
.orch-kp-set-head:hover { background: #f3f4f6; }
.orch-kp-set-niche { font-weight: 700; font-size: 14px; color: #0A0E27; }
.orch-kp-set-meta { font-size: 11px; color: #6b7280; margin-top: 2px; }
.orch-kp-set-count { background: #ecfdf5; color: #065f46; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
.orch-kp-set-arrow { font-size: 14px; color: #9ca3af; transition: transform 0.15s; }
.orch-kp-set.open .orch-kp-set-arrow { transform: rotate(90deg); }

.orch-kp-set-body { display: none; padding: 8px 12px 14px; border-top: 1px solid #f3f4f6; }
.orch-kp-set.open .orch-kp-set-body { display: block; }
.orch-kp-set-controls { display: flex; gap: 6px; margin: 6px 4px 10px; }
.orch-kp-mini-btn { font-size: 11px; padding: 3px 8px; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 5px; cursor: pointer; color: #4b5563; }
.orch-kp-mini-btn:hover { background: #e5e7eb; }

.orch-kp-kw { display: flex; align-items: flex-start; gap: 10px; padding: 8px 10px; border-radius: 6px; cursor: pointer; transition: background 0.1s; }
.orch-kp-kw:hover { background: #f9fafb; }
.orch-kp-kw input[type="checkbox"] { margin: 3px 0 0; }
.orch-kp-kw-body { flex: 1; }
.orch-kp-kw-text { font-size: 13px; font-weight: 600; color: #0A0E27; }
.orch-kp-kw-topic { display: block; font-size: 11.5px; color: #6b7280; margin-top: 2px; font-style: italic; line-height: 1.4; }
.orch-kp-kw-tags { display: flex; gap: 4px; margin-top: 4px; flex-wrap: wrap; }
.orch-kp-tag { display: inline-block; padding: 1px 6px; border-radius: 999px; font-size: 10px; font-weight: 600; }
.orch-kp-tag-vol-alto { background: #dcfce7; color: #065f46; }
.orch-kp-tag-vol-medio { background: #fef3c7; color: #92400e; }
.orch-kp-tag-vol-basso { background: #f3f4f6; color: #4b5563; }
.orch-kp-tag-vol-nicchia { background: #ede9fe; color: #5b21b6; }
.orch-kp-tag-diff-facile { background: #dcfce7; color: #065f46; }
.orch-kp-tag-diff-medio { background: #fef3c7; color: #92400e; }
.orch-kp-tag-diff-difficile { background: #fee2e2; color: #991b1b; }
.orch-kp-tag-int-informational { background: #dbeafe; color: #1e40af; }
.orch-kp-tag-int-transactional { background: #fee2e2; color: #991b1b; }
.orch-kp-tag-int-commercial { background: #fef3c7; color: #92400e; }
.orch-kp-tag-int-navigational { background: #f3f4f6; color: #4b5563; }

.orch-kp-drawer-foot { padding: 14px 22px; border-top: 1px solid #e5e7eb; background: #fafafa; display: flex; justify-content: space-between; align-items: center; gap: 10px; }
.orch-kp-selection-status { font-size: 12px; color: #4b5563; }
.orch-kp-drawer-actions { display: flex; gap: 8px; }
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

(function() {
    var UID = <?php echo wp_json_encode($seo_aeo_kp_uid); ?>;
    var TARGETS = <?php echo wp_json_encode($seo_aeo_kp_targets); ?>;
    var MODE = <?php echo wp_json_encode($seo_aeo_kp_mode); ?>;
    var SEP = <?php echo wp_json_encode($seo_aeo_kp_separator); ?>;

    jQuery(function($) {
        var $pill = $('.orch-kp-pill[data-uid="' + UID + '"]');
        var $bd = $('#orch-kp-bd-' + UID);
        var $body = $bd.find('[data-orch-kp-body]');
        var $countBadge = $pill.find('[data-orch-kp-count]');
        var loaded = false;

        function escHtml(s) {
            return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
        }

        function refreshCount() {
            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_keyword_sets_list',
                nonce: seoAeoOrchestra.nonce
            }, function(resp) {
                if (resp && resp.success && resp.sets) {
                    $countBadge.text(resp.sets.length);
                } else {
                    $countBadge.text('0');
                }
            });
        }
        refreshCount();

        function loadSets() {
            $body.html('<div style="padding:40px;text-align:center;color:#6b7280;"><span class="spinner is-active" style="float:none;"></span> Caricamento set salvati...</div>');
            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_keyword_sets_list',
                nonce: seoAeoOrchestra.nonce
            }, function(resp) {
                if (!resp || !resp.success || !resp.sets || !resp.sets.length) {
                    $body.html(
                        '<div class="orch-kp-empty">' +
                            '<div class="orch-kp-empty-icon">🎯</div>' +
                            '<h4>Nessun set ancora</h4>' +
                            '<p>Crea il tuo primo set da <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-keyword-research')); ?>">🎯 Keyword Research</a>.</p>' +
                        '</div>'
                    );
                    return;
                }
                var html = '';
                $.each(resp.sets, function(i, set) {
                    html += '<div class="orch-kp-set" data-set-idx="' + i + '">';
                    html += '<div class="orch-kp-set-head">' +
                        '<div>' +
                            '<div class="orch-kp-set-niche">' + escHtml(set.niche) + '</div>' +
                            '<div class="orch-kp-set-meta">' + escHtml(set.date || '') + ' · ' + (set.clusters || []).length + ' cluster</div>' +
                        '</div>' +
                        '<div style="display:flex;align-items:center;gap:8px;">' +
                            '<span class="orch-kp-set-count">' + set.count + ' kw</span>' +
                            '<span class="orch-kp-set-arrow">▶</span>' +
                        '</div>' +
                    '</div>';
                    html += '<div class="orch-kp-set-body">';
                    html += '<div class="orch-kp-set-controls">' +
                        '<button type="button" class="orch-kp-mini-btn orch-kp-select-all">Seleziona tutte</button>' +
                        '<button type="button" class="orch-kp-mini-btn orch-kp-select-easy">Solo facili</button>' +
                        '<button type="button" class="orch-kp-mini-btn orch-kp-deselect-all">Deseleziona</button>' +
                    '</div>';
                    $.each(set.keywords || [], function(j, kw) {
                        var kData = JSON.stringify(kw).replace(/'/g, '&#39;').replace(/"/g, '&quot;');
                        html += '<label class="orch-kp-kw">' +
                            '<input type="checkbox" class="orch-kp-kw-cb" data-kw="' + kData + '">' +
                            '<div class="orch-kp-kw-body">' +
                                '<div class="orch-kp-kw-text">' + escHtml(kw.keyword) + '</div>' +
                                (kw.topic ? '<span class="orch-kp-kw-topic">💡 ' + escHtml(kw.topic) + '</span>' : '') +
                                '<div class="orch-kp-kw-tags">' +
                                    (kw.cluster ? '<span class="orch-kp-tag" style="background:#ede9fe;color:#5b21b6;">' + escHtml(kw.cluster) + '</span>' : '') +
                                    (kw.volume ? '<span class="orch-kp-tag orch-kp-tag-vol-' + escHtml(kw.volume) + '">' + escHtml(kw.volume) + '</span>' : '') +
                                    (kw.difficulty ? '<span class="orch-kp-tag orch-kp-tag-diff-' + escHtml(kw.difficulty) + '">' + escHtml(kw.difficulty) + '</span>' : '') +
                                    (kw.intent ? '<span class="orch-kp-tag orch-kp-tag-int-' + escHtml(kw.intent) + '">' + escHtml(kw.intent) + '</span>' : '') +
                                '</div>' +
                            '</div>' +
                        '</label>';
                    });
                    html += '</div></div>';
                });
                $body.html(html);
                // Apri il primo set automaticamente
                $body.find('.orch-kp-set').first().addClass('open');
                updateSelectionStatus();
            });
        }

        function updateSelectionStatus() {
            var n = $bd.find('.orch-kp-kw-cb:checked').length;
            $bd.find('[data-orch-kp-selection]').text(n + ' keyword selezionate');
            $bd.find('.orch-kp-apply').prop('disabled', n === 0);
        }

        $pill.on('click', function() {
            $bd.addClass('open');
            if (!loaded) { loadSets(); loaded = true; }
            else updateSelectionStatus();
        });
        $bd.find('.orch-kp-drawer-close, .orch-kp-cancel').on('click', function() { $bd.removeClass('open'); });
        $bd.on('click', function(e) { if (e.target === this) $bd.removeClass('open'); });

        // Toggle set expand
        $bd.on('click', '.orch-kp-set-head', function() {
            $(this).closest('.orch-kp-set').toggleClass('open');
        });

        // Select all / easy / none in a set
        $bd.on('click', '.orch-kp-select-all', function(e) {
            e.stopPropagation();
            $(this).closest('.orch-kp-set').find('.orch-kp-kw-cb').prop('checked', true);
            updateSelectionStatus();
        });
        $bd.on('click', '.orch-kp-deselect-all', function(e) {
            e.stopPropagation();
            $(this).closest('.orch-kp-set').find('.orch-kp-kw-cb').prop('checked', false);
            updateSelectionStatus();
        });
        $bd.on('click', '.orch-kp-select-easy', function(e) {
            e.stopPropagation();
            $(this).closest('.orch-kp-set').find('.orch-kp-kw-cb').each(function() {
                var kw = JSON.parse($(this).attr('data-kw').replace(/&#39;/g, "'").replace(/&quot;/g, '"'));
                $(this).prop('checked', kw.difficulty === 'facile');
            });
            updateSelectionStatus();
        });
        $bd.on('change', '.orch-kp-kw-cb', updateSelectionStatus);

        // Apply selection → fill targets
        $bd.find('.orch-kp-apply').on('click', function() {
            var selected = [];
            $bd.find('.orch-kp-kw-cb:checked').each(function() {
                try {
                    var kw = JSON.parse($(this).attr('data-kw').replace(/&#39;/g, "'").replace(/&quot;/g, '"'));
                    selected.push(kw);
                } catch (e) {}
            });
            if (!selected.length) return;

            $.each(TARGETS, function(selector, fieldKind) {
                var $el = $(selector);
                if (!$el.length) return;
                if (MODE === 'csv') {
                    var existing = ($el.val() || '').split(SEP).map(function(s){ return s.trim(); }).filter(Boolean);
                    selected.forEach(function(kw) {
                        var v = (fieldKind === 'topic' && kw.topic) ? kw.topic : kw.keyword;
                        if (v && existing.indexOf(v) === -1) existing.push(v);
                    });
                    $el.val(existing.join(SEP)).trigger('change');
                } else {
                    // single mode: use first selected
                    var first = selected[0];
                    var v = (fieldKind === 'topic' && first.topic) ? first.topic : first.keyword;
                    $el.val(v).trigger('change');
                }
            });

            // 3.26.0: emit custom event PRIMA di clearare le checkbox, così
            // gli host (es. Auto-Pilot) possono leggere le keyword complete.
            try {
                jQuery(document).trigger('orch-kp:applied', [selected, UID]);
            } catch (e) {}

            $bd.removeClass('open');
            $bd.find('.orch-kp-kw-cb').prop('checked', false);
            updateSelectionStatus();

            if (typeof SeoAeoOrchestra !== 'undefined' && SeoAeoOrchestra.showNotice) {
                SeoAeoOrchestra.showNotice('✓ ' + selected.length + ' keyword inserite nel modulo', 'success');
            }
        });
    });
})();
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>

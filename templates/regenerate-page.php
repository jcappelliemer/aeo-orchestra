<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $nonce, $p, $post are local template-scope variables (template is included from within a class method via PHP include; not globals).
/**
 * Template: Rigenera intera pagina (v3.41.8) — dedicated DANGER flow.
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) wp_die('forbidden');

$nonce = wp_create_nonce('seo_aeo_orchestra_regen_nonce');
$pages = get_pages(array(
    'sort_column' => 'post_title',
    'post_status' => array('publish', 'private'),
    'number'      => 500,
));
?>
<div class="wrap orchestra-v3">
    <h1>🔥 Rigenera intera pagina</h1>

    <div style="background:#fef9c3;border:1px solid #facc15;border-left:4px solid #ca8a04;padding:14px 18px;border-radius:6px;margin:16px 0;">
        <strong style="color:#854d0e;">⚠️ AZIONE AD ALTO RISCHIO (Tier DANGER).</strong>
        <p style="margin:6px 0 0 0;color:#713f12;">
            Questo strumento <strong>riscrive completamente</strong> il <code>post_content</code> della pagina selezionata
            con contenuto generato dall'AI. Pensaci due volte prima di applicare. Costo stimato: <strong>50 crediti</strong>
            per la generazione, applica solo se hai verificato la proposta.
            Backup automatico doppio (field + Snapshot Manager TTL 7gg) garantisce il rollback.
        </p>
    </div>

    <!-- ─────────────── STEP 1 — Page selection ─────────────── -->
    <div id="regen-step-1" class="regen-step" style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:20px;margin-bottom:16px;">
        <h2 style="margin-top:0;color:#0f172a;">Step 1 · Seleziona pagina</h2>
        <label for="regen-page-select" style="display:block;font-weight:600;margin-bottom:6px;">Pagina da rigenerare</label>
        <select id="regen-page-select" style="width:100%;max-width:600px;padding:8px;">
            <option value="">— Seleziona una pagina —</option>
            <?php foreach ($pages as $p): ?>
                <option value="<?php echo esc_attr($p->ID); ?>">
                    <?php echo esc_html($p->post_title); ?>
                    (<?php echo esc_html($p->post_name); ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <div id="regen-page-info" style="margin-top:16px;display:none;background:#f8fafc;border:1px solid #cbd5e1;border-radius:6px;padding:14px;"></div>

        <div style="margin-top:16px;">
            <button id="regen-step1-next" class="button button-primary" disabled>Continua a Step 2 →</button>
        </div>
    </div>

    <!-- ─────────────── STEP 2 — Generate proposal ─────────────── -->
    <div id="regen-step-2" class="regen-step" style="display:none;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:20px;margin-bottom:16px;">
        <h2 style="margin-top:0;color:#0f172a;">Step 2 · Genera proposta AI</h2>
        <p>Genera la proposta. Costo: <strong>50 crediti</strong>. Tempo stimato: ~30s.</p>
        <button id="regen-step2-generate" class="button button-primary">⚡ Genera proposta AI (50 cr)</button>

        <div id="regen-step2-progress" style="display:none;margin-top:16px;color:#475569;">
            <span class="spinner is-active" style="float:left;margin:0 8px 0 0;"></span>
            Generazione in corso… questo richiede ~30 secondi.
        </div>

        <div id="regen-step2-result" style="display:none;margin-top:20px;">
            <div id="regen-delta-banner" style="background:#fee2e2;border:1px solid #fca5a5;border-left:4px solid #dc2626;color:#7f1d1d;padding:14px 18px;border-radius:6px;margin-bottom:16px;">
                <strong style="font-size:14px;">⚠️ ATTENZIONE: questa azione sostituirà il contenuto attuale</strong>
                <div id="regen-delta-numbers" style="font-size:18px;margin-top:8px;font-weight:600;"></div>
                <p style="margin:6px 0 0 0;font-size:13px;">
                    Il contenuto attuale verrà salvato come backup automatico (<code>_seo_aeo_content_backup</code> + Snapshot Manager TTL 7 giorni).
                </p>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <div style="background:#f1f5f9;padding:8px 12px;font-weight:600;border-radius:6px 6px 0 0;border-bottom:2px solid #94a3b8;">
                        Contenuto attuale
                        <span id="regen-current-stats" style="float:right;font-weight:400;color:#64748b;font-size:12px;"></span>
                    </div>
                    <textarea id="regen-current-content" readonly style="width:100%;height:400px;font-family:monospace;font-size:11px;padding:10px;border:1px solid #cbd5e1;border-top:none;border-radius:0 0 6px 6px;"></textarea>
                </div>
                <div>
                    <div style="background:#dbeafe;padding:8px 12px;font-weight:600;border-radius:6px 6px 0 0;border-bottom:2px solid #3b82f6;">
                        Proposta AI
                        <span id="regen-proposed-stats" style="float:right;font-weight:400;color:#1e40af;font-size:12px;"></span>
                    </div>
                    <textarea id="regen-proposed-content" readonly style="width:100%;height:400px;font-family:monospace;font-size:11px;padding:10px;border:1px solid #3b82f6;border-top:none;border-radius:0 0 6px 6px;"></textarea>
                </div>
            </div>

            <div style="margin-top:16px;display:flex;gap:8px;">
                <button id="regen-step2-back" class="button">← Annulla, torna a Step 1</button>
                <button id="regen-step2-next" class="button button-primary">Continua a conferma →</button>
            </div>
        </div>
    </div>

    <!-- ─────────────── STEP 3 — Typed confirm ─────────────── -->
    <div id="regen-step-3" class="regen-step" style="display:none;background:#fff;border:1px solid #fca5a5;border-radius:8px;padding:20px;margin-bottom:16px;">
        <h2 style="margin-top:0;color:#991b1b;">Step 3 · Conferma esplicita</h2>
        <div style="background:#fee2e2;border:1px solid #fca5a5;padding:12px 16px;border-radius:6px;margin-bottom:16px;">
            <strong style="color:#7f1d1d;">⚠️ Stai per sostituire definitivamente il contenuto della pagina.</strong>
            <p style="margin:6px 0 0 0;color:#7f1d1d;font-size:13px;">
                Anche se il backup automatico funziona, la pagina sarà offline (con il nuovo contenuto) finché non clicchi "Ripristina".
                Verifica di aver controllato bene la proposta nello Step 2 prima di proseguire.
            </p>
        </div>

        <label for="regen-typed-confirm" style="display:block;font-weight:600;margin-bottom:6px;">
            Digita <code>riscrivi</code> qui sotto per confermare:
        </label>
        <input type="text" id="regen-typed-confirm" autocomplete="off" placeholder="riscrivi" style="width:100%;max-width:300px;padding:8px;font-size:16px;font-family:monospace;">

        <div style="margin-top:16px;">
            <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;">
                <input type="checkbox" id="regen-checkbox-confirm" style="margin-top:3px;">
                <span style="font-size:13px;color:#475569;">
                    Ho letto e accetto che il contenuto attuale della pagina verrà sostituito completamente.
                    Comprendo che il backup automatico mi permetterà di ripristinare la versione precedente,
                    ma la pagina sarà temporaneamente diversa fino al ripristino.
                </span>
            </label>
        </div>

        <div style="margin-top:20px;display:flex;gap:8px;">
            <button id="regen-step3-back" class="button">← Torna a Step 2</button>
            <button id="regen-step3-apply" class="button button-primary" disabled style="background:#dc2626;border-color:#b91c1c;">⚡ Applica modifiche</button>
        </div>

        <div id="regen-step3-progress" style="display:none;margin-top:16px;color:#475569;">
            <span class="spinner is-active" style="float:left;margin:0 8px 0 0;"></span>
            Applicazione in corso…
        </div>
    </div>

    <!-- ─────────────── STEP 4 — Success + rollback ─────────────── -->
    <div id="regen-step-4" class="regen-step" style="display:none;background:#fff;border:1px solid #86efac;border-radius:8px;padding:20px;margin-bottom:16px;">
        <h2 style="margin-top:0;color:#166534;">✅ Operazione completata</h2>
        <div style="background:#dcfce7;border:1px solid #86efac;padding:14px 18px;border-radius:6px;margin-bottom:16px;">
            <strong style="color:#166534;">Contenuto sostituito con successo.</strong>
            <p style="margin:6px 0 0 0;color:#14532d;">
                Backup salvati: <code>_seo_aeo_content_backup</code> + Snapshot Manager TTL 7 giorni.
            </p>
            <div id="regen-step4-stats" style="margin-top:8px;font-size:13px;color:#14532d;"></div>
        </div>

        <div style="display:flex;gap:8px;align-items:center;">
            <a id="regen-step4-view" href="#" target="_blank" class="button">🔗 Visualizza pagina</a>
            <button id="regen-step4-rollback" class="button" style="background:#fef3c7;border-color:#fcd34d;color:#854d0e;">↩ Ripristina versione precedente</button>
            <button id="regen-step4-new" class="button">↺ Nuova rigenerazione</button>
        </div>

        <div id="regen-step4-rollback-msg" style="display:none;margin-top:16px;background:#dbeafe;border:1px solid #93c5fd;padding:12px 16px;border-radius:6px;color:#1e3a8a;"></div>
    </div>
</div>

<script>
(function(){
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;
    var nonce = <?php echo wp_json_encode($nonce); ?>;
    var ORIGIN = 'content_regenerate_dedicated_flow';
    var ajaxUrl = (typeof seoAeoOrchestra !== 'undefined' && seoAeoOrchestra.ajaxUrl) ? seoAeoOrchestra.ajaxUrl : ajaxurl;

    var state = { post_id: 0, proposed: '', current: '', view_url: '' };

    function fmt(n) { return (n || 0).toLocaleString('it-IT'); }

    function show(step) {
        $('.regen-step').hide();
        $('#regen-step-' + step).show();
        $('html,body').animate({ scrollTop: $('#regen-step-' + step).offset().top - 50 }, 200);
    }

    // ── STEP 1 ───────────────────────────────────────────────
    $('#regen-page-select').on('change', function() {
        var pid = parseInt(this.value, 10) || 0;
        state.post_id = pid;
        $('#regen-page-info').hide().empty();
        $('#regen-step1-next').prop('disabled', true);
        if (!pid) return;
        $.post(ajaxUrl, {
            action: 'seo_aeo_orchestra_regen_get_page_info',
            nonce: nonce,
            post_id: pid,
        }).done(function(r) {
            if (!r || !r.success) { $('#regen-page-info').show().text('Errore caricamento info pagina.'); return; }
            var d = r.data;
            var html = ''
                + '<div><strong>Titolo:</strong> ' + $('<i>').text(d.title || '').html() + '</div>'
                + '<div><strong>Slug:</strong> ' + $('<i>').text(d.slug || '').html() + '</div>'
                + '<div><strong>URL:</strong> <a href="' + d.url + '" target="_blank">' + d.url + '</a></div>'
                + '<div><strong>Stato:</strong> ' + d.post_status + '</div>'
                + '<div><strong>Modificato:</strong> ' + d.modified_gmt + ' UTC</div>'
                + '<div style="margin-top:8px;font-weight:600;">'
                    + 'Contenuto: <span style="color:#0f172a;">' + fmt(d.current_byte_size) + ' byte</span> · '
                    + fmt(d.current_word_count) + ' parole · '
                    + d.reading_time_min + ' min lettura'
                + '</div>'
                + '<div style="margin-top:6px;color:' + (d.has_existing_backup ? '#16a34a' : '#64748b') + ';">'
                    + (d.has_existing_backup ? '✓ Backup precedente esistente (recuperabile via "Ripristina")' : 'ℹ Nessun backup precedente — questo sarà il primo')
                + '</div>';
            $('#regen-page-info').show().html(html);
            $('#regen-step1-next').prop('disabled', false);
            state.view_url = d.url;
        });
    });

    $('#regen-step1-next').on('click', function() { show(2); });

    // ── STEP 2 ───────────────────────────────────────────────
    $('#regen-step2-generate').on('click', function() {
        $('#regen-step2-generate').prop('disabled', true);
        $('#regen-step2-progress').show();
        $('#regen-step2-result').hide();
        $.post(ajaxUrl, {
            action: 'seo_aeo_orchestra_regen_generate',
            nonce: nonce,
            post_id: state.post_id,
        }).done(function(r) {
            $('#regen-step2-progress').hide();
            $('#regen-step2-generate').prop('disabled', false);
            if (!r || !r.success) {
                var msg = (r && r.data && r.data.message) ? r.data.message : 'Errore generazione proposta.';
                alert('Errore: ' + msg);
                return;
            }
            var d = r.data;
            state.current = (d.current && d.current.value) || '';
            state.proposed = (d.proposed && d.proposed.value) || '';
            $('#regen-current-content').val(state.current);
            $('#regen-proposed-content').val(state.proposed);
            $('#regen-current-stats').text(fmt(d.current.byte_size) + ' B · ' + fmt(d.current.word_count) + ' parole');
            $('#regen-proposed-stats').text(fmt(d.proposed.byte_size) + ' B · ' + fmt(d.proposed.word_count) + ' parole');
            var arrow = d.delta.byte_diff >= 0 ? '↗' : '↘';
            $('#regen-delta-numbers').html(
                fmt(d.current.byte_size) + ' → ' + fmt(d.proposed.byte_size) + ' byte · '
                + arrow + ' ' + (d.delta.byte_diff >= 0 ? '+' : '') + fmt(d.delta.byte_diff) + ' byte · '
                + (d.delta.byte_pct >= 0 ? '+' : '') + d.delta.byte_pct + '%'
            );
            $('#regen-step2-result').show();
        }).fail(function(xhr) {
            $('#regen-step2-progress').hide();
            $('#regen-step2-generate').prop('disabled', false);
            alert('Errore HTTP ' + xhr.status + ': ' + (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : 'request failed'));
        });
    });

    $('#regen-step2-back').on('click', function() { show(1); });
    $('#regen-step2-next').on('click', function() { show(3); });

    // ── STEP 3 ───────────────────────────────────────────────
    function recomputeApplyGate() {
        var tc = ($('#regen-typed-confirm').val() || '').trim().toLowerCase();
        var cb = $('#regen-checkbox-confirm').is(':checked');
        $('#regen-step3-apply').prop('disabled', !(tc === 'riscrivi' && cb));
    }
    $('#regen-typed-confirm').on('input', recomputeApplyGate);
    $('#regen-checkbox-confirm').on('change', recomputeApplyGate);
    $('#regen-step3-back').on('click', function() { show(2); });

    $('#regen-step3-apply').on('click', function() {
        if ($('#regen-step3-apply').prop('disabled')) return;
        $('#regen-step3-apply').prop('disabled', true);
        $('#regen-step3-progress').show();
        $.post(ajaxUrl, {
            action: 'seo_aeo_orchestra_regen_apply',
            nonce: nonce,
            post_id: state.post_id,
            proposed_content: state.proposed,
            typed_confirm: $('#regen-typed-confirm').val(),
            checkbox_accepted: $('#regen-checkbox-confirm').is(':checked') ? '1' : '0',
            request_origin: ORIGIN,
        }).done(function(r) {
            $('#regen-step3-progress').hide();
            if (!r || !r.success) {
                var msg = (r && r.data && r.data.message) ? r.data.message : 'Errore apply.';
                alert('Apply fallito: ' + msg);
                $('#regen-step3-apply').prop('disabled', false);
                return;
            }
            var d = r.data;
            $('#regen-step4-view').attr('href', d.view_url || state.view_url);
            $('#regen-step4-stats').html(
                'Nuovo contenuto: <strong>' + fmt(d.new_byte_size) + ' byte</strong> · ' +
                fmt(d.new_word_count) + ' parole · backup: ' +
                JSON.stringify(d.backup_results)
            );
            show(4);
        }).fail(function(xhr) {
            $('#regen-step3-progress').hide();
            $('#regen-step3-apply').prop('disabled', false);
            alert('HTTP ' + xhr.status + ': ' + (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : 'request failed'));
        });
    });

    // ── STEP 4 ───────────────────────────────────────────────
    $('#regen-step4-rollback').on('click', function() {
        if (!confirm('Ripristinare il contenuto precedente? Il contenuto AI-generato verrà sostituito con il backup.')) return;
        $('#regen-step4-rollback').prop('disabled', true);
        $.post(ajaxUrl, {
            action: 'seo_aeo_orchestra_regen_rollback',
            nonce: nonce,
            post_id: state.post_id,
        }).done(function(r) {
            $('#regen-step4-rollback').prop('disabled', false);
            if (!r || !r.success) {
                alert('Rollback fallito: ' + ((r && r.data && r.data.message) || 'unknown'));
                return;
            }
            var d = r.data;
            $('#regen-step4-rollback-msg').show().html(
                '<strong>✓ Ripristinato.</strong> Contenuto: ' + fmt(d.restored_byte_size) + ' byte · ' + fmt(d.restored_word_count) + ' parole.'
            );
        }).fail(function(xhr) {
            $('#regen-step4-rollback').prop('disabled', false);
            alert('HTTP ' + xhr.status);
        });
    });

    $('#regen-step4-new').on('click', function() {
        state = { post_id: 0, proposed: '', current: '', view_url: '' };
        $('#regen-page-select').val('');
        $('#regen-page-info').hide();
        $('#regen-typed-confirm').val('');
        $('#regen-checkbox-confirm').prop('checked', false);
        recomputeApplyGate();
        show(1);
    });
})();
</script>

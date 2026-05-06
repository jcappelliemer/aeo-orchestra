<?php
/*
 * Template: Migration Wizard "Switch da Yoast" (v3.19.0)
 * Wizard step-by-step per migrare da Yoast/RankMath/AIOSEO a Orchestra Native.
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap orchestra-v3 orch-wizard-page">

    <div class="orch-wizard-hero">
        <div class="orch-wizard-hero-icon">🚀</div>
        <div class="orch-wizard-hero-text">
            <div class="orch-wizard-hero-tagline"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('AEO Orchestra · Migrazione SEO')) : 'AEO Orchestra · Migrazione SEO'; ?></div>
            <h1 class="orch-wizard-hero-title"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Migrazione SEO da plugin esterno')) : 'Migrazione SEO da plugin esterno'; ?></h1>
            <p class="orch-wizard-hero-sub"><?php echo class_exists('SEO_AEO_T') ? wp_kses_post(SEO_AEO_T::t('Procedura guidata per migrare meta tags, redirect e schema dal tuo plugin SEO attuale a Orchestra Native. Tutti i passaggi sono reversibili e creiamo un backup JSON prima di ogni modifica.')) : 'Procedura guidata per migrare meta tags, redirect e schema dal tuo plugin SEO attuale a Orchestra Native. Tutti i passaggi sono <strong>reversibili</strong> e creiamo un backup JSON prima di ogni modifica.'; ?></p>
        </div>
    </div>

    <?php $seo_aeo_T = function($s) { return class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t($s)) : esc_html($s); }; ?>
    <div class="orch-wizard-stepper">
        <div class="orch-wizard-step" data-step="1"><span>1</span> <?php echo esc_html(SEO_AEO_T::t('Scansione')); ?></div>
        <div class="orch-wizard-step" data-step="2"><span>2</span> <?php echo esc_html(SEO_AEO_T::t('Backup')); ?></div>
        <div class="orch-wizard-step" data-step="3"><span>3</span> <?php echo esc_html(SEO_AEO_T::t('Migra meta')); ?></div>
        <div class="orch-wizard-step" data-step="4"><span>4</span> <?php echo esc_html(SEO_AEO_T::t('Importa redirect')); ?></div>
        <div class="orch-wizard-step" data-step="5"><span>5</span> <?php echo esc_html(SEO_AEO_T::t('Attiva stack')); ?></div>
        <div class="orch-wizard-step" data-step="6"><span>6</span> <?php echo esc_html(SEO_AEO_T::t('Disinstall')); ?></div>
    </div>

    <div id="orch-wizard-stage-1" class="orch-wizard-stage active">
        <h2><?php echo esc_html(SEO_AEO_T::t('Step 1 — Scansione')); ?></h2>
        <p class="orch3-muted"><?php echo esc_html(SEO_AEO_T::t('Analizziamo il sito per identificare il plugin SEO attivo, contare meta tag esistenti, redirect Yoast Premium e schema custom. Solo lettura, niente modifiche.')); ?></p>
        <button type="button" class="orch3-btn orch3-btn-primary" id="orch-wizard-scan-btn"><?php echo esc_html(SEO_AEO_T::t('🔍 Avvia scansione')); ?></button>
        <div id="orch-wizard-scan-result" style="margin-top:14px;"></div>
    </div>

    <div id="orch-wizard-stage-2" class="orch-wizard-stage">
        <h2><?php echo esc_html(SEO_AEO_T::t('Step 2 — Backup')); ?></h2>
        <p class="orch3-muted"><?php echo esc_html(SEO_AEO_T::t('Generiamo un file JSON con tutti i dati attuali: meta tag Yoast, redirect, settings. Lo scarichi sul tuo computer come "rete di sicurezza" — se qualcosa va storto puoi ripristinare manualmente.')); ?></p>
        <button type="button" class="orch3-btn orch3-btn-primary" id="orch-wizard-backup-btn"><?php echo esc_html(SEO_AEO_T::t('💾 Genera + scarica backup JSON')); ?></button>
        <div id="orch-wizard-backup-result" style="margin-top:14px;"></div>
    </div>

    <div id="orch-wizard-stage-3" class="orch-wizard-stage">
        <h2><?php echo esc_html(SEO_AEO_T::t('Step 3 — Migra meta tag')); ?></h2>
        <p class="orch3-muted"><?php echo class_exists('SEO_AEO_T') ? wp_kses_post(SEO_AEO_T::t('Copia i meta tag da Yoast keys (<code>_yoast_wpseo_*</code>) a Orchestra keys (<code>_seo_aeo_meta_*</code>). I dati originali NON vengono cancellati: pattern shadow-copy. Se Orchestra ha già un valore per un post, viene preservato.')) : 'Copia i meta tag da Yoast keys (<code>_yoast_wpseo_*</code>) a Orchestra keys (<code>_seo_aeo_meta_*</code>). I dati originali NON vengono cancellati: pattern shadow-copy. Se Orchestra ha già un valore per un post, viene preservato.'; ?></p>
        <button type="button" class="orch3-btn orch3-btn-primary" id="orch-wizard-migrate-btn"><?php echo esc_html(SEO_AEO_T::t('📥 Avvia migrazione meta')); ?></button>
        <div id="orch-wizard-migrate-result" style="margin-top:14px;"></div>
    </div>

    <div id="orch-wizard-stage-4" class="orch-wizard-stage">
        <h2><?php echo esc_html(SEO_AEO_T::t('Step 4 — Importa redirect')); ?></h2>
        <p class="orch3-muted"><?php echo class_exists('SEO_AEO_T') ? wp_kses_post(SEO_AEO_T::t('Importa redirect da Yoast Premium (option <code>wpseo-premium-redirects-base</code>) e dal plugin Redirection (tabella <code>wp_redirection_items</code>). Skip duplicati. Note: "Importato da X · data".')) : 'Importa redirect da Yoast Premium (option <code>wpseo-premium-redirects-base</code>) e dal plugin Redirection (tabella <code>wp_redirection_items</code>). Skip duplicati. Note: "Importato da X · data".'; ?></p>
        <button type="button" class="orch3-btn orch3-btn-primary" id="orch-wizard-redirects-btn"><?php echo esc_html(SEO_AEO_T::t('🔀 Importa redirect')); ?></button>
        <div id="orch-wizard-redirects-result" style="margin-top:14px;"></div>
    </div>

    <div id="orch-wizard-stage-5" class="orch-wizard-stage">
        <h2><?php echo esc_html(SEO_AEO_T::t('Step 5 — Attiva stack Native')); ?></h2>
        <p class="orch3-muted"><?php echo esc_html(SEO_AEO_T::t('Attiva i toggle Output Renderer, Override Mode, Sitemap.xml, llms.txt, Redirect Manager. Schema.org è opt-in (default off): attiva manualmente se vuoi sostituire schema custom esistente.')); ?></p>

        <div class="orch-wizard-options">
            <label><input type="checkbox" name="output"   checked> 🎯 <?php echo esc_html(SEO_AEO_T::t('Output <head> nativo')); ?></label>
            <label><input type="checkbox" name="override" checked> 🔧 <?php echo esc_html(SEO_AEO_T::t('Override Mode (silenzia altro plugin SEO)')); ?></label>
            <label><input type="checkbox" name="sitemap"  checked> 🗺️ Sitemap.xml</label>
            <label><input type="checkbox" name="llms"     checked> 🤖 llms.txt</label>
            <label><input type="checkbox" name="schema">          📊 <?php echo esc_html(SEO_AEO_T::t('Schema.org (skip se hai schema custom)')); ?></label>
            <label><input type="checkbox" name="redirect" checked> 🔀 <?php echo esc_html(SEO_AEO_T::t('Redirect Manager')); ?></label>
        </div>

        <button type="button" class="orch3-btn orch3-btn-primary" id="orch-wizard-activate-btn"><?php echo esc_html(SEO_AEO_T::t('✓ Attiva selezionati')); ?></button>
        <div id="orch-wizard-activate-result" style="margin-top:14px;"></div>
    </div>

    <div id="orch-wizard-stage-6" class="orch-wizard-stage">
        <h2><?php echo esc_html(SEO_AEO_T::t('Step 6 — Disinstall plugin SEO esterno')); ?></h2>
        <p class="orch3-muted"><?php echo esc_html(SEO_AEO_T::t('Migrazione completata! Ora puoi disinstallare il plugin SEO esterno. Per safety lo facciamo manualmente: vai sul pannello Plugin di WP, trova Yoast/RankMath/AIOSEO e click "Disinstalla".')); ?></p>

        <div class="orch-wizard-success-box">
            <div class="orch-wizard-success-head"><?php echo esc_html(SEO_AEO_T::t('🎉 Sei migrato a Orchestra Native!')); ?></div>
            <p><?php echo esc_html(SEO_AEO_T::t('Verifica prima di disinstallare:')); ?></p>
            <ol>
                <li><?php echo class_exists('SEO_AEO_T') ? wp_kses_post(SEO_AEO_T::t('Apri il source code della homepage (CTRL+U) e verifica che ci siano i tag Orchestra (<code>&lt;!-- AEO Orchestra · Native Output --&gt;</code>)')) : 'Apri il source code della homepage (CTRL+U) e verifica che ci siano i tag Orchestra (<code>&lt;!-- AEO Orchestra · Native Output --&gt;</code>)'; ?></li>
                <li><?php echo class_exists('SEO_AEO_T') ? wp_kses_post(SEO_AEO_T::t('Apri <code>/seo-aeo-sitemap.xml</code> in browser → deve mostrare il sitemap index')) : 'Apri <code>/seo-aeo-sitemap.xml</code> in browser → deve mostrare il sitemap index'; ?></li>
                <li><?php echo class_exists('SEO_AEO_T') ? wp_kses_post(SEO_AEO_T::t('Apri <code>/llms.txt</code> e <code>/llms-full.txt</code> → deve mostrare markdown')) : 'Apri <code>/llms.txt</code> e <code>/llms-full.txt</code> → deve mostrare markdown'; ?></li>
                <li><?php echo esc_html(SEO_AEO_T::t('Verifica con Google Rich Results Test che gli schema esistenti siano ancora validi')); ?></li>
            </ol>
            <p><?php echo esc_html(SEO_AEO_T::t('Se tutto è ok:')); ?></p>
            <a href="<?php echo esc_url(admin_url('plugins.php')); ?>" class="orch3-btn orch3-btn-primary"><?php echo esc_html(SEO_AEO_T::t('Vai al pannello Plugin')); ?></a>
            <button type="button" class="orch3-btn orch3-btn-ghost" id="orch-wizard-restart-btn"><?php echo esc_html(SEO_AEO_T::t('↺ Ricomincia wizard')); ?></button>
        </div>
    </div>

</div>

<style>
.orch-wizard-page { max-width: 900px; }

.orch-wizard-hero { display: flex; gap: 22px; align-items: center; padding: 28px 30px; margin: 16px 0 24px; background: linear-gradient(135deg, #047857 0%, #10b981 50%, #00E5FF 100%); border-radius: 12px; color: #ffffff; box-shadow: 0 4px 24px rgba(16,185,129,0.18); }
.orch-wizard-hero-icon { width: 72px; height: 72px; flex-shrink: 0; background: rgba(255,255,255,0.18); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 38px; }
.orch-wizard-hero-tagline { font-size: 11px; letter-spacing: 2px; text-transform: uppercase; opacity: 0.85; }
.orch-wizard-hero-title { color: #fff; font-size: 26px; font-weight: 700; margin: 4px 0 10px; line-height: 1.1; }
.orch-wizard-hero-sub { font-size: 14px; line-height: 1.55; margin: 0; opacity: 0.95; max-width: 720px; }

.orch-wizard-stepper { display: flex; gap: 4px; margin-bottom: 24px; flex-wrap: wrap; }
.orch-wizard-step { flex: 1; min-width: 100px; padding: 10px 8px; background: #f1f5f9; border-radius: 6px; font-size: 12px; color: #64748b; text-align: center; font-weight: 500; }
.orch-wizard-step.done { background: #d1fae5; color: #047857; }
.orch-wizard-step.active { background: #0055ff; color: #ffffff; }
.orch-wizard-step span { display: inline-block; width: 22px; height: 22px; line-height: 22px; border-radius: 50%; background: rgba(255,255,255,0.2); margin-right: 6px; font-weight: 700; }
.orch-wizard-step.done span { background: #047857; color: #ffffff; }
.orch-wizard-step.active span { background: rgba(255,255,255,0.3); color: #ffffff; }
.orch-wizard-step:not(.active):not(.done) span { background: #cbd5e1; color: #ffffff; }

.orch-wizard-stage { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 24px; display: none; }
.orch-wizard-stage.active { display: block; }
.orch-wizard-stage h2 { margin: 0 0 8px; font-size: 18px; color: #0a0e27; }
.orch-wizard-stage p code { background: #f1f5f9; border: 1px solid #e2e8f0; padding: 1px 5px; border-radius: 3px; font-size: 11px; }

.orch-wizard-options { margin: 14px 0 18px; display: flex; flex-direction: column; gap: 10px; padding: 14px 16px; background: #f8fafc; border-radius: 6px; }
.orch-wizard-options label { font-size: 13px; color: #334155; display: flex; align-items: center; gap: 10px; cursor: pointer; }

.orch-wizard-success-box { padding: 18px 20px; background: linear-gradient(180deg, #ecfdf5, #ffffff); border: 2px solid #10b981; border-radius: 10px; margin-top: 14px; }
.orch-wizard-success-head { font-size: 18px; font-weight: 700; color: #047857; margin-bottom: 10px; }
.orch-wizard-success-box ol { margin: 8px 0 14px 18px; line-height: 1.7; color: #334155; font-size: 13px; }

.orch-wizard-result-block { padding: 14px 16px; border-radius: 8px; font-size: 13px; line-height: 1.6; }
.orch-wizard-result-block.ok { background: #d1fae5; color: #065f46; border-left: 3px solid #10b981; }
.orch-wizard-result-block.warn { background: #fffbeb; color: #78350f; border-left: 3px solid #f59e0b; }
.orch-wizard-result-block.err { background: #fee2e2; color: #991b1b; border-left: 3px solid #ef4444; }
.orch-wizard-result-block ul { margin: 6px 0 0 18px; line-height: 1.6; }

.orch-wizard-actions { margin-top: 14px; display: flex; gap: 10px; flex-wrap: wrap; }
</style>

<script>
jQuery(function($) {
    var currentStage = 1;
    var maxStage = 6;
    var scanData = null;

    function gotoStage(n) {
        currentStage = n;
        $('.orch-wizard-stage').removeClass('active');
        $('#orch-wizard-stage-' + n).addClass('active');
        $('.orch-wizard-step').each(function() {
            var step = parseInt($(this).data('step'), 10);
            $(this).removeClass('active done');
            if (step < n) $(this).addClass('done');
            else if (step === n) $(this).addClass('active');
        });
        $('html, body').animate({ scrollTop: $('.orch-wizard-stepper').offset().top - 40 }, 200);
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    function nextBtn(target, label) {
        return '<div class="orch-wizard-actions"><button type="button" class="orch3-btn orch3-btn-primary orch-wizard-next-btn" data-target="' + target + '">' + escapeHtml(label || 'Avanti →') + '</button></div>';
    }

    // STEP 1 — Scan
    $('#orch-wizard-scan-btn').on('click', function() {
        var $btn = $(this).prop('disabled', true).html('<span class="rv-spinner"></span> Scansiono…');
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_migration_scan', nonce: seoAeoOrchestra.nonce })
            .done(function(resp) {
                $btn.prop('disabled', false).html('🔍 Avvia scansione');
                if (!resp || !resp.success) {
                    $('#orch-wizard-scan-result').html('<div class="orch-wizard-result-block err">' + escapeHtml(resp && resp.error || 'Errore') + '</div>');
                    return;
                }
                scanData = resp.scan;
                var s = resp.scan;
                var pluginLine = s.detected_plugin
                    ? '<strong>Rilevato</strong>: ' + escapeHtml(s.detected_plugin_label || s.detected_plugin)
                    : '<strong>Nessun plugin SEO esterno rilevato.</strong> Puoi saltare gli step di migrazione e attivare direttamente Orchestra Native.';

                var metaLines = [];
                for (var k in s.meta_counts) metaLines.push('<li><code>' + escapeHtml(k) + '</code>: ' + s.meta_counts[k] + ' post</li>');
                var nativeLines = [];
                for (var n in s.native_state) nativeLines.push('<li>' + escapeHtml(n) + ': ' + (s.native_state[n] ? '✅ ON' : '○ OFF') + '</li>');

                var html = '<div class="orch-wizard-result-block ok">' +
                    '<div>' + pluginLine + '</div>' +
                    '<div style="margin-top:8px;"><strong>Meta tag esistenti:</strong> ' + (s.meta_total_posts || 0) + ' valori totali</div>' +
                    (metaLines.length ? '<ul>' + metaLines.join('') + '</ul>' : '<p>Nessun meta tag Yoast trovato.</p>') +
                    '<div style="margin-top:6px;"><strong>Redirect Yoast Premium:</strong> ' + (s.yoast_redirects_count || 0) + '</div>' +
                    '<div><strong>Redirect plugin Redirection:</strong> ' + (s.redirection_plugin_count || 0) + '</div>' +
                    '<div style="margin-top:8px;"><strong>Stato Orchestra Native:</strong></div>' +
                    '<ul>' + nativeLines.join('') + '</ul>' +
                    '</div>' + nextBtn(2);
                $('#orch-wizard-scan-result').html(html);
            });
    });

    // STEP 2 — Backup
    $('#orch-wizard-backup-btn').on('click', function() {
        var $btn = $(this).prop('disabled', true).html('<span class="rv-spinner"></span> Genero backup…');
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_migration_backup', nonce: seoAeoOrchestra.nonce })
            .done(function(resp) {
                $btn.prop('disabled', false).html('💾 Genera + scarica backup JSON');
                if (!resp || !resp.success) {
                    $('#orch-wizard-backup-result').html('<div class="orch-wizard-result-block err">' + escapeHtml(resp && resp.error || 'Errore') + '</div>');
                    return;
                }
                // Force download JSON
                var json = JSON.stringify(resp.backup, null, 2);
                var blob = new Blob([json], { type: 'application/json' });
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url; a.download = resp.filename;
                document.body.appendChild(a); a.click();
                setTimeout(function() { document.body.removeChild(a); URL.revokeObjectURL(url); }, 200);

                var sizeKb = Math.round(json.length / 1024);
                $('#orch-wizard-backup-result').html(
                    '<div class="orch-wizard-result-block ok">' +
                    '✅ Backup scaricato: <code>' + escapeHtml(resp.filename) + '</code> (' + sizeKb + ' KB).' +
                    '<br>Conservalo in un posto sicuro prima di proseguire.' +
                    '</div>' + nextBtn(3)
                );
            });
    });

    // STEP 3 — Migrate meta
    $('#orch-wizard-migrate-btn').on('click', function() {
        if (!confirm('Stai per copiare i meta tag Yoast nelle key Orchestra. Operazione reversibile (i dati originali non vengono cancellati). Procedere?')) return;
        var $btn = $(this).prop('disabled', true).html('<span class="rv-spinner"></span> Migro meta…');
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_migration_migrate_meta', nonce: seoAeoOrchestra.nonce })
            .done(function(resp) {
                $btn.prop('disabled', false).html('📥 Avvia migrazione meta');
                if (!resp || !resp.success) {
                    $('#orch-wizard-migrate-result').html('<div class="orch-wizard-result-block err">' + escapeHtml(resp && resp.error || 'Errore') + '</div>');
                    return;
                }
                var perKeyLines = [];
                for (var k in resp.per_key) perKeyLines.push('<li><code>' + escapeHtml(k) + '</code>: ' + resp.per_key[k] + ' copiati</li>');
                $('#orch-wizard-migrate-result').html(
                    '<div class="orch-wizard-result-block ok">' +
                    '✅ <strong>' + (resp.total_copied || 0) + ' meta totali copiati.</strong>' +
                    (perKeyLines.length ? '<ul>' + perKeyLines.join('') + '</ul>' : '') +
                    '</div>' + nextBtn(4)
                );
            });
    });

    // STEP 4 — Import redirects
    $('#orch-wizard-redirects-btn').on('click', function() {
        var $btn = $(this).prop('disabled', true).html('<span class="rv-spinner"></span> Importo redirect…');
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_migration_import_redirects', nonce: seoAeoOrchestra.nonce })
            .done(function(resp) {
                $btn.prop('disabled', false).html('🔀 Importa redirect');
                if (!resp || !resp.success) {
                    $('#orch-wizard-redirects-result').html('<div class="orch-wizard-result-block err">' + escapeHtml(resp && resp.error || 'Errore') + '</div>');
                    return;
                }
                $('#orch-wizard-redirects-result').html(
                    '<div class="orch-wizard-result-block ok">' +
                    '✅ <strong>' + (resp.total_imported || 0) + ' redirect importati.</strong>' +
                    '<ul>' +
                    '<li>Da Yoast Premium: ' + (resp.imported_yoast_premium || 0) + '</li>' +
                    '<li>Da plugin Redirection: ' + (resp.imported_redirection || 0) + '</li>' +
                    '<li>Saltati come duplicati: ' + (resp.skipped_duplicates || 0) + '</li>' +
                    '</ul>' +
                    '</div>' + nextBtn(5)
                );
            });
    });

    // STEP 5 — Activate stack
    $('#orch-wizard-activate-btn').on('click', function() {
        var $btn = $(this).prop('disabled', true).html('<span class="rv-spinner"></span> Attivo stack…');
        var data = { action: 'seo_aeo_orchestra_migration_activate_stack', nonce: seoAeoOrchestra.nonce };
        $('.orch-wizard-options input[type="checkbox"]:checked').each(function() {
            data[$(this).attr('name')] = 1;
        });
        $.post(ajaxurl, data)
            .done(function(resp) {
                $btn.prop('disabled', false).html('✓ Attiva selezionati');
                if (!resp || !resp.success) {
                    $('#orch-wizard-activate-result').html('<div class="orch-wizard-result-block err">' + escapeHtml(resp && resp.error || 'Errore') + '</div>');
                    return;
                }
                var actLines = (resp.activated || []).map(function(x) { return '<li>' + escapeHtml(x) + '</li>'; }).join('');
                $('#orch-wizard-activate-result').html(
                    '<div class="orch-wizard-result-block ok">' +
                    '✅ <strong>Stack attivato.</strong>' +
                    (actLines ? '<ul>' + actLines + '</ul>' : '') +
                    '</div>' + nextBtn(6, 'Vai allo step finale →')
                );
            });
    });

    // Restart
    $('#orch-wizard-restart-btn').on('click', function() {
        if (!confirm('Ricominciare il wizard? I dati migrati restano (operazioni eseguite), ma puoi rivedere i passaggi.')) return;
        gotoStage(1);
        $('#orch-wizard-scan-result, #orch-wizard-backup-result, #orch-wizard-migrate-result, #orch-wizard-redirects-result, #orch-wizard-activate-result').empty();
    });

    // Next button (delegato perché viene aggiunto dinamicamente)
    $(document).on('click', '.orch-wizard-next-btn', function() {
        var target = parseInt($(this).data('target'), 10);
        if (target >= 1 && target <= maxStage) gotoStage(target);
    });

    // Inizializza step 1
    gotoStage(1);
});
</script>

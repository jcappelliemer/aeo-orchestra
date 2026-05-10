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
    <!-- 3.35.66 — H feature: Migration Importer (per-post meta keys) -->
<div class="orch-card orch-migration-importer" id="orch-migration-importer">
    <div class="orch-card-head">
        <h2 style="margin:0;display:flex;align-items:center;gap:10px;">
            <span style="font-size:22px;">📥</span>
            <span><?php echo esc_html(SEO_AEO_T::t('Importazione metadata per-post')); ?></span>
        </h2>
        <p class="orch3-muted" style="margin:8px 0 0;font-size:13px;">
            <?php echo esc_html(SEO_AEO_T::t('Estende la migrazione: importa title/description/canonical/robots/OG/Twitter di ogni singolo articolo da Yoast / RankMath / AIOSEO. Senza questo, perdi 5 anni di metadata.')); ?>
        </p>
    </div>

    <div id="orch-mi-status" style="padding:14px 18px;">
        <button type="button" class="orch3-btn orch3-btn-primary" id="orch-mi-detect-btn">
            <?php echo esc_html(SEO_AEO_T::t('🔍 Rileva plugin SEO attivi')); ?>
        </button>
    </div>

    <div id="orch-mi-detection-result" style="display:none;padding:0 18px 18px;"></div>

    <!-- Pre-import modal -->
    <div id="orch-mi-modal" class="orch-modal-backdrop" style="display:none;">
        <div class="orch-modal-window" style="max-width:640px;">
            <div class="orch-modal-head">
                <h3 id="orch-mi-modal-title" style="margin:0;"></h3>
                <button type="button" class="orch-modal-close" id="orch-mi-modal-close">×</button>
            </div>
            <div class="orch-modal-body">
                <p id="orch-mi-modal-summary" class="orch3-muted"></p>
                <div id="orch-mi-modal-keys" style="margin:14px 0;"></div>
                <hr/>
                <label style="display:flex;align-items:flex-start;gap:8px;margin:10px 0;">
                    <input type="checkbox" id="orch-mi-create-backup" checked />
                    <span><strong><?php echo esc_html(SEO_AEO_T::t('Crea backup prima dell\'import')); ?></strong><br>
                    <span class="orch3-muted" style="font-size:12px;"><?php echo esc_html(SEO_AEO_T::t('Snapshot dei valori esistenti per rollback (auto-purge dopo 7 giorni).')); ?></span></span>
                </label>
                <label style="display:flex;align-items:flex-start;gap:8px;margin:10px 0;">
                    <input type="checkbox" id="orch-mi-override-existing" />
                    <span><strong><?php echo esc_html(SEO_AEO_T::t('Sovrascrivi se Orchestra ha già un valore')); ?></strong><br>
                    <span class="orch3-muted" style="font-size:12px;"><?php echo esc_html(SEO_AEO_T::t('Default OFF: skipparmiamo gli articoli che hanno già un valore Orchestra.')); ?></span></span>
                </label>
                <div id="orch-mi-modal-multilingual" style="display:none;background:#fef3c7;border:1px solid #fde68a;color:#92400e;padding:10px;border-radius:6px;margin-top:10px;font-size:13px;"></div>
            </div>
            <div class="orch-modal-foot">
                <button type="button" class="orch3-btn orch3-btn-ghost" id="orch-mi-modal-cancel"><?php echo esc_html(SEO_AEO_T::t('Annulla')); ?></button>
                <button type="button" class="orch3-btn orch3-btn-primary" id="orch-mi-modal-start"><?php echo esc_html(SEO_AEO_T::t('▶ Avvia import')); ?></button>
            </div>
        </div>
    </div>

    <!-- Progress modal -->
    <div id="orch-mi-progress" class="orch-modal-backdrop" style="display:none;">
        <div class="orch-modal-window" style="max-width:640px;">
            <div class="orch-modal-head"><h3 style="margin:0;"><?php echo esc_html(SEO_AEO_T::t('Importazione in corso…')); ?></h3></div>
            <div class="orch-modal-body">
                <div style="background:#f1f5f9;border-radius:8px;height:24px;overflow:hidden;margin-bottom:10px;">
                    <div id="orch-mi-progress-bar" style="background:linear-gradient(90deg,#10b981,#0055FF);height:100%;width:0%;transition:width .25s;"></div>
                </div>
                <p id="orch-mi-progress-text" class="orch3-muted" style="margin:6px 0;font-size:13px;"></p>
                <p id="orch-mi-progress-last" class="orch3-muted" style="margin:0;font-size:12px;font-style:italic;"></p>
            </div>
        </div>
    </div>

    <!-- Final summary -->
    <div id="orch-mi-final" style="display:none;padding:0 18px 18px;"></div>

    <!-- Backups list -->
    <div id="orch-mi-backups" style="padding:0 18px 18px;"></div>
</div>

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

<?php ob_start(); ?>
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
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

<?php ob_start(); ?>
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

/* ─────────── 3.35.66 — Migration Importer JS ─────────── */
(function($) {
    if (!$ || typeof window.seoAeoOrchestra === 'undefined') return;
    var ajaxurl = window.ajaxurl || (window.seoAeoOrchestra && window.seoAeoOrchestra.ajaxurl);
    var nonce = window.seoAeoOrchestra && window.seoAeoOrchestra.nonce;
    if (!ajaxurl || !nonce) return;

    var currentPlugin = null;
    var currentBackupId = '';
    var currentMapping = {};

    function escapeHtml(s) { return String(s || '').replace(/[&<>"']/g, function(c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    }); }

    function ajax(action, data) {
        return $.post(ajaxurl, $.extend({action: action, nonce: nonce}, data || {}));
    }

    function renderDetected(detected, multilingual, backups) {
        var $r = $('#orch-mi-detection-result');
        var html = '';
        var detectedKeys = Object.keys(detected || {});
        if (detectedKeys.length === 0) {
            html = '<div style="padding:18px;background:#f1f5f9;border-radius:8px;color:#64748b;">' +
                'Nessun plugin SEO concorrente attivo. Yoast / RankMath / AIOSEO non rilevati.</div>';
        } else {
            detectedKeys.forEach(function(k) {
                var d = detected[k] || {};
                var name = String(d.name || k);
                var version = String(d.version || 'unknown');
                var postsCount = (typeof d.posts_count === 'number') ? d.posts_count : (parseInt(d.posts_count, 10) || 0);
                var mappable = (typeof d.mappable_keys === 'number') ? d.mappable_keys : (parseInt(d.mappable_keys, 10) || 0);
                html += '<div style="border:1px solid #e2e8f0;border-radius:10px;padding:14px;margin-bottom:10px;">' +
                    '<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">' +
                    '<div><strong>✓ ' + escapeHtml(name) + ' rilevato</strong> ' +
                    '<span class="orch3-muted" style="font-size:12px;">v' + escapeHtml(version) + '</span><br>' +
                    '<span class="orch3-muted" style="font-size:13px;">' + postsCount + ' articoli con meta keys popolati · ' +
                    mappable + ' tipi di meta key mappabili</span></div>' +
                    '<button type="button" class="orch3-btn orch3-btn-primary orch-mi-import" data-plugin="' + escapeHtml(k) + '" data-name="' + escapeHtml(name) + '" data-count="' + postsCount + '">📥 Importa da ' + escapeHtml(name) + ' →</button>' +
                    '</div></div>';
            });
        }

        if (multilingual && multilingual.length) {
            html += '<div style="background:#fef3c7;border:1px solid #fde68a;color:#92400e;padding:10px;border-radius:6px;margin-top:10px;font-size:13px;">' +
                '⚠ Plugin multilingua rilevato: ' + escapeHtml(multilingual.join(', ')) +
                '. L\'import processerà solo la lingua di default. Gestione completa multilingua in deploy futuro.</div>';
        }

        if (backups && backups.length) {
            html += '<h4 style="margin:18px 0 8px;">Backup disponibili</h4>';
            backups.forEach(function(b) {
                var when = b.created_at ? new Date(b.created_at * 1000).toISOString().slice(0,16).replace('T',' ') : '?';
                var rolled = b.rolled_back_at ? ' <span style="color:#10b981;">(rolled back)</span>' : '';
                html += '<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:6px;font-size:13px;">' +
                    '<span><strong>' + escapeHtml(b.plugin) + '</strong> · ' + b.post_count + ' post · ' + when + rolled + '</span>' +
                    (b.rolled_back_at ? '' :
                    '<button type="button" class="orch3-btn orch3-btn-ghost orch-mi-rollback" data-plugin="' + escapeHtml(b.plugin) + '" data-bid="' + escapeHtml(b.backup_id) + '">↺ Rollback</button>') +
                    '</div>';
            });
        }

        $r.html(html).show();
    }

    function openImportModal(plugin, name, count) {
        currentPlugin = plugin;
        $('#orch-mi-modal-title').text('Importa da ' + name);
        $('#orch-mi-modal-summary').text(count + ' articoli verranno processati. Le meta keys mappate sono:');

        // Hardcoded mapping display per plugin (UI-only, server has authoritative version)
        var mappings = {
            yoast: [
                ['_yoast_wpseo_title', '_orch_meta_title (Title tag)'],
                ['_yoast_wpseo_metadesc', '_orch_meta_desc_override (Meta description)'],
                ['_yoast_wpseo_focuskw', '_orch_focus_keyword (Focus keyword)'],
                ['_yoast_wpseo_canonical', '_orch_canonical_override (Canonical URL)'],
                ['_yoast_wpseo_meta-robots-noindex', '_orch_robots_index (translated: 1→noindex 2→index)'],
                ['_yoast_wpseo_meta-robots-nofollow', '_orch_robots_follow (translated)'],
                ['_yoast_wpseo_opengraph-title', '_orch_og_title'],
                ['_yoast_wpseo_opengraph-description', '_orch_og_description'],
                ['_yoast_wpseo_opengraph-image', '_orch_og_image_override'],
                ['_yoast_wpseo_twitter-title', '_orch_twitter_title'],
                ['_yoast_wpseo_twitter-description', '_orch_twitter_description'],
            ],
            rankmath: [
                ['rank_math_title', '_orch_meta_title'],
                ['rank_math_description', '_orch_meta_desc_override'],
                ['rank_math_focus_keyword', '_orch_focus_keyword'],
                ['rank_math_canonical_url', '_orch_canonical_override'],
                ['rank_math_robots', '_orch_robots_directive (parsed)'],
                ['rank_math_facebook_title', '_orch_og_title'],
                ['rank_math_facebook_description', '_orch_og_description'],
                ['rank_math_facebook_image', '_orch_og_image_override'],
                ['rank_math_twitter_title', '_orch_twitter_title'],
                ['rank_math_twitter_description', '_orch_twitter_description'],
            ],
            aioseo: [
                ['_aioseo_title', '_orch_meta_title'],
                ['_aioseo_description', '_orch_meta_desc_override'],
                ['_aioseo_keywords', '_orch_focus_keyword'],
            ],
        };

        currentMapping = mappings[plugin] || [];
        var keysHtml = '<p style="font-size:12px;font-weight:600;color:#64748b;margin:0 0 6px;">Deseleziona quelle che NON vuoi importare:</p>';
        currentMapping.forEach(function(pair) {
            keysHtml += '<label style="display:flex;align-items:center;gap:8px;padding:4px 0;font-size:13px;">' +
                '<input type="checkbox" class="orch-mi-key-toggle" value="' + escapeHtml(pair[0]) + '" checked />' +
                '<code style="font-size:11px;background:#f1f5f9;padding:2px 6px;border-radius:3px;">' + escapeHtml(pair[0]) + '</code>' +
                '<span style="color:#64748b;">→</span>' +
                '<code style="font-size:11px;background:#dbeafe;padding:2px 6px;border-radius:3px;">' + escapeHtml(pair[1]) + '</code>' +
                '</label>';
        });
        $('#orch-mi-modal-keys').html(keysHtml);
        $('#orch-mi-modal').css('display', 'flex');
    }

    function startImport() {
        $('#orch-mi-modal').hide();
        $('#orch-mi-progress').css('display', 'flex');
        $('#orch-mi-progress-bar').css('width', '0%');
        $('#orch-mi-progress-text').text('Inizio…');
        $('#orch-mi-progress-last').text('');

        var skipKeys = [];
        $('#orch-mi-modal-keys input.orch-mi-key-toggle').each(function() {
            if (!$(this).is(':checked')) skipKeys.push($(this).val());
        });
        var override = $('#orch-mi-override-existing').is(':checked') ? 1 : 0;
        var createBackup = $('#orch-mi-create-backup').is(':checked') ? 1 : 0;

        currentBackupId = '';
        var totalImported = 0;
        var totalKeysWritten = {};
        var firstSamples = [];

        function runBatch(offset) {
            var data = {
                plugin: currentPlugin,
                offset: offset,
                limit: 50,
                override_existing: override,
                create_backup: (offset === 0) ? createBackup : 0,
                backup_id: currentBackupId,
            };
            if (skipKeys.length) {
                skipKeys.forEach(function(k, i) { data['skip_keys[' + i + ']'] = k; });
            }
            ajax('orch_migration_importer_batch', data).done(function(resp) {
                if (!resp.success) {
                    $('#orch-mi-progress').hide();
                    alert('Errore batch: ' + (resp.message || 'unknown'));
                    return;
                }
                var r = resp.result;
                if (r.backup_id && !currentBackupId) currentBackupId = r.backup_id;
                totalImported += (r.processed_in_batch || 0);
                Object.keys(r.keys_written || {}).forEach(function(k) {
                    totalKeysWritten[k] = (totalKeysWritten[k] || 0) + r.keys_written[k];
                });
                if (offset === 0 && r.samples) firstSamples = r.samples;

                var pct = r.total_to_process ? Math.min(100, Math.round((r.total_processed / r.total_to_process) * 100)) : 100;
                $('#orch-mi-progress-bar').css('width', pct + '%');
                $('#orch-mi-progress-text').text('Processati ' + r.total_processed + '/' + r.total_to_process + ' · ' + totalImported + ' modificati');
                if (r.last_title) $('#orch-mi-progress-last').text('Ultimo: "' + r.last_title + '"');

                if (r.next_offset !== null && r.next_offset !== undefined) {
                    setTimeout(function() { runBatch(r.next_offset); }, 100);
                } else {
                    setTimeout(function() {
                        $('#orch-mi-progress').hide();
                        showFinal(totalImported, totalKeysWritten, firstSamples);
                    }, 400);
                }
            }).fail(function() {
                $('#orch-mi-progress').hide();
                alert('Errore di rete durante batch.');
            });
        }
        runBatch(0);
    }

    function showFinal(imported, keysWritten, samples) {
        var html = '<div style="background:linear-gradient(135deg,#10b981,#0055FF);color:white;padding:18px;border-radius:10px;">' +
            '<h3 style="margin:0 0 8px;color:white;">✓ Import completato</h3>' +
            '<p style="margin:0;font-size:14px;">Articoli aggiornati: <strong>' + imported + '</strong></p></div>';

        if (Object.keys(keysWritten).length) {
            html += '<h4 style="margin:14px 0 6px;">Meta keys scritte</h4><ul style="font-size:13px;">';
            Object.keys(keysWritten).forEach(function(k) {
                html += '<li><code>' + escapeHtml(k) + '</code>: ' + keysWritten[k] + ' post</li>';
            });
            html += '</ul>';
        }

        if (samples && samples.length) {
            html += '<h4 style="margin:14px 0 6px;">Esempi (primi 5)</h4>';
            samples.forEach(function(s) {
                html += '<div style="border:1px solid #e2e8f0;border-radius:6px;padding:8px;margin-bottom:6px;font-size:12px;">' +
                    '<strong>' + escapeHtml(s.title) + '</strong><br>';
                Object.keys(s.changes || {}).forEach(function(k) {
                    var v = String(s.changes[k] || '').slice(0, 80);
                    html += '<code style="font-size:11px;">' + escapeHtml(k) + '</code> = ' + escapeHtml(v) + (s.changes[k].length > 80 ? '…' : '') + '<br>';
                });
                html += '</div>';
            });
        }

        if (currentBackupId) {
            html += '<button type="button" class="orch3-btn orch3-btn-ghost orch-mi-rollback" data-plugin="' + escapeHtml(currentPlugin) + '" data-bid="' + escapeHtml(currentBackupId) + '" style="margin-top:10px;">↺ Rollback questo import</button>';
        }
        $('#orch-mi-final').html(html).show();
        // Refresh detection so backup list updates
        setTimeout(function() { $('#orch-mi-detect-btn').click(); }, 1000);
    }

    function rollback(plugin, bid) {
        if (!confirm('Confermi rollback? I valori Orchestra di questo import verranno ripristinati allo stato pre-import.')) return;
        ajax('orch_migration_importer_rollback', {plugin: plugin, backup_id: bid}).done(function(resp) {
            if (resp.success) {
                alert('Rollback OK: ' + (resp.result.posts_restored || 0) + ' post ripristinati.');
                $('#orch-mi-detect-btn').click();
            } else {
                alert('Errore rollback: ' + (resp.result && resp.result.error || resp.message || 'unknown'));
            }
        });
    }

    $(document).on('click', '#orch-mi-detect-btn', function() {
        var $btn = $(this).prop('disabled', true).text('Rilevamento…');
        console.log('[H detect] click fired, sending AJAX...');
        ajax('orch_migration_importer_detect').done(function(resp) {
            console.log('[H detect] response:', resp);
            if (!resp) {
                $('#orch-mi-detection-result').html('<div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:6px;">Errore: response empty.</div>').show();
                return;
            }
            if (resp.error) {
                $('#orch-mi-detection-result').html('<div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:6px;">Errore backend: ' + escapeHtml(resp.message || 'unknown') + '</div>').show();
                return;
            }
            if (!resp.success) {
                // Show raw response for diagnosis
                $('#orch-mi-detection-result').html(
                    '<div style="background:#fef3c7;color:#92400e;padding:12px;border-radius:6px;"><strong>Risposta non success</strong><br><pre style="font-size:11px;margin-top:6px;white-space:pre-wrap;">' +
                    escapeHtml(JSON.stringify(resp, null, 2)) + '</pre></div>'
                ).show();
                return;
            }
            try {
                renderDetected(resp.detected || {}, resp.multilingual || [], resp.backups || []);
            } catch (e) {
                console.error('[H detect] render exception:', e);
                $('#orch-mi-detection-result').html(
                    '<div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:6px;">' +
                    'Render error: ' + escapeHtml(e.message || String(e)) +
                    '<br><small>Response shape:</small><pre style="font-size:11px;">' +
                    escapeHtml(JSON.stringify(resp, null, 2)) + '</pre></div>'
                ).show();
            }
        }).fail(function(xhr, status, err) {
            console.error('[H detect] AJAX fail:', xhr.status, status, err, xhr.responseText);
            $('#orch-mi-detection-result').html(
                '<div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:6px;">' +
                'AJAX fail HTTP ' + xhr.status + ': ' + escapeHtml(status || 'unknown') +
                '<br><small>Response body:</small><pre style="font-size:11px;max-height:200px;overflow:auto;">' +
                escapeHtml((xhr.responseText || '').slice(0, 1000)) + '</pre></div>'
            ).show();
        }).always(function() {
            $btn.prop('disabled', false).text('🔍 Rileva plugin SEO attivi');
        });
    });

    $(document).on('click', '.orch-mi-import', function() {
        openImportModal($(this).data('plugin'), $(this).data('name'), $(this).data('count'));
    });
    $(document).on('click', '#orch-mi-modal-close, #orch-mi-modal-cancel', function() {
        $('#orch-mi-modal').hide();
    });
    $(document).on('click', '#orch-mi-modal-start', startImport);
    $(document).on('click', '.orch-mi-rollback', function() {
        rollback($(this).data('plugin'), $(this).data('bid'));
    });
})(window.jQuery);
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>

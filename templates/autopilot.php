<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.VariableNotPrefixed
// Reason: template scope. Variables are local to this include/template,
// passed by the calling function via include/require. The Plugin Check
// heuristic doesn't distinguish template-scope locals from globals.
/*
 * Template: Auto-Pilot Scheduler (since 3.24.0)
 * Crea job ricorrenti che generano articoli automaticamente da set keyword salvati.
 */
if (!defined('ABSPATH')) exit;

$current_user_id = get_current_user_id();
$categories = get_categories(array('hide_empty' => false));
$authors = get_users(array('capability' => 'edit_posts', 'fields' => array('ID', 'display_name')));
$bv_active = class_exists('SEO_AEO_Brand_Voice') ? SEO_AEO_Brand_Voice::get_active_profile() : null;
?>

<div class="wrap orch-ap-page">

    <!-- 3.31.5: container per notice persistenti (run-now feedback, bulk ops) -->
    <div id="orch-ap-notices"></div>

    <!-- HERO -->
    <div class="orch-ap-hero">
        <div class="orch-ap-hero-bg"></div>
        <div class="orch-ap-hero-inner">
            <div class="orch-ap-hero-icon">🤖</div>
            <div>
                <div class="orch-ap-hero-eyebrow"><?php SEO_AEO_T::e('AEO Orchestra · Auto-Pilot Engine'); ?></div>
                <h1 class="orch-ap-hero-title"><?php SEO_AEO_T::e('Auto-Pilot Scheduler'); ?></h1>
                <p class="orch-ap-hero-sub"><?php SEO_AEO_T::e('Configura job ricorrenti che generano articoli completi (testo + immagine + meta) automaticamente con la tua Brand Voice. Imposta una volta, dimentica.'); ?></p>
                <?php if ($bv_active): ?>
                    <div class="orch-ap-hero-badge"><?php SEO_AEO_T::e('🎙️ Brand Voice attiva: '); ?><?php echo esc_html($bv_active['_name']); ?></div>
                <?php else: ?>
                    <div class="orch-ap-hero-badge orch-ap-hero-badge-warn"><?php SEO_AEO_T::e('⚠️ Nessuna Brand Voice attiva — gli articoli useranno il tono predefinito.'); ?> <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-brand-voice')); ?>" style="margin-left:6px;font-weight:600;text-decoration:underline;color:#7c2d12;">→ <?php SEO_AEO_T::e('Configura Brand Voice ora'); ?></a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- INFO BAR -->
    <div class="orch-ap-info">
        <div class="orch-ap-info-item">
            <strong><?php SEO_AEO_T::e('Come funziona'); ?></strong>
            <span><?php SEO_AEO_T::e('Un cron WP ogni ora controlla i job attivi. Quando arriva il momento, pesca la prossima keyword del set, chiama l\'AI, crea il post (bozza o pubblicato).'); ?></span>
        </div>
        <div class="orch-ap-info-item">
            <strong><?php SEO_AEO_T::e('Costo per articolo'); ?></strong>
            <span><?php SEO_AEO_T::e('~25 crediti (Articolo Completo Premium: testo + immagine + meta tags).'); ?></span>
        </div>
        <div class="orch-ap-info-item">
            <strong><?php SEO_AEO_T::e('Premium-only'); ?></strong>
            <span><?php SEO_AEO_T::e('Disponibile dai piani Professional, Team e Pro.'); ?></span>
        </div>
    </div>

    <!-- CREA JOB -->
    <div class="orch-ap-create">
        <h2><?php SEO_AEO_T::e('➕ Crea nuovo job'); ?></h2>

        <div class="orch-ap-form">
            <div class="orch-ap-field">
                <label><?php SEO_AEO_T::e('Nome job'); ?></label>
                <input type="text" id="orch-ap-name" placeholder="<?php echo esc_attr(SEO_AEO_T::t('es. Blog SEO settimanale, Articoli prodotto mensili...')); ?>">
            </div>

            <div class="orch-ap-field">
                <label><?php SEO_AEO_T::e('Set di keyword'); ?></label>
                <button type="button" class="button" id="orch-ap-pick-keywords"><?php SEO_AEO_T::e('📥 Seleziona keyword da Keyword Research'); ?></button>
                <small id="orch-ap-kw-status"><?php SEO_AEO_T::e('Nessuna keyword selezionata'); ?></small>
                <input type="hidden" id="orch-ap-keywords-json" value="[]">
            </div>

            <div class="orch-ap-row">
                <div class="orch-ap-field">
                    <label><?php SEO_AEO_T::e('Frequenza generazione'); ?></label>
                    <select id="orch-ap-freq">
                        <option value="1"><?php SEO_AEO_T::e('Ogni giorno'); ?></option>
                        <option value="3"><?php SEO_AEO_T::e('Ogni 3 giorni'); ?></option>
                        <option value="7" selected><?php SEO_AEO_T::e('Settimanale'); ?></option>
                        <option value="14"><?php SEO_AEO_T::e('Bisettimanale'); ?></option>
                        <option value="30"><?php SEO_AEO_T::e('Mensile'); ?></option>
                    </select>
                </div>
                <div class="orch-ap-field">
                    <label><?php SEO_AEO_T::e('Lunghezza articolo'); ?></label>
                    <select id="orch-ap-length">
                        <option value="short"><?php SEO_AEO_T::e('Breve (~500 parole)'); ?></option>
                        <option value="medium" selected><?php SEO_AEO_T::e('Media (~1000 parole)'); ?></option>
                        <option value="long"><?php SEO_AEO_T::e('Lunga (~2000 parole)'); ?></option>
                    </select>
                </div>
                <div class="orch-ap-field">
                    <label><?php SEO_AEO_T::e('Stato post creato'); ?></label>
                    <select id="orch-ap-post-status">
                        <option value="draft" selected><?php SEO_AEO_T::e('Bozza (revisione manuale)'); ?></option>
                        <option value="publish"><?php SEO_AEO_T::e('Pubblicato direttamente'); ?></option>
                    </select>
                </div>
            </div>

            <div class="orch-ap-row">
                <div class="orch-ap-field">
                    <label><?php SEO_AEO_T::e('Categoria'); ?></label>
                    <select id="orch-ap-category">
                        <option value="0"><?php SEO_AEO_T::e('— Nessuna —'); ?></option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int) $cat->term_id; ?>"><?php echo esc_html($cat->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="orch-ap-field">
                    <label><?php SEO_AEO_T::e('Autore'); ?></label>
                    <select id="orch-ap-author">
                        <?php foreach ($authors as $au): ?>
                            <option value="<?php echo (int) $au->ID; ?>" <?php if ((int) $au->ID === $current_user_id) echo 'selected'; ?>><?php echo esc_html($au->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="orch-ap-field orch-ap-field-checkbox">
                    <label><input type="checkbox" id="orch-ap-include-image" checked> <?php SEO_AEO_T::e('Includi immagine AI'); ?></label>
                    <small><?php SEO_AEO_T::e('+15 crediti per immagine'); ?></small>
                </div>
            </div>

            <!-- 3.31.5: per-job layout override -->
            <details class="orch-ap-layout-override">
                <summary><?php SEO_AEO_T::e('🎨 Override layout (opzionale)'); ?></summary>
                <p class="orch-ap-layout-help"><?php SEO_AEO_T::e('Per default questo job usa il layout configurato in Impostazioni. Attiva l\'override per usare un layout custom solo per questo job.'); ?></p>
                <label class="orch-ap-layout-toggle">
                    <input type="checkbox" id="orch-ap-layout-enabled">
                    <?php SEO_AEO_T::e('Usa layout custom per questo job'); ?>
                </label>
                <div id="orch-ap-layout-fields" style="display:none;margin-top:12px;">
                    <div class="orch-ap-row">
                        <div class="orch-ap-field">
                            <label><?php SEO_AEO_T::e('Tipo contenuto'); ?></label>
                            <select id="orch-ap-layout-post-type"></select>
                        </div>
                        <div class="orch-ap-field">
                            <label><?php SEO_AEO_T::e('Template'); ?></label>
                            <select id="orch-ap-layout-template"></select>
                        </div>
                    </div>
                    <div class="orch-ap-row">
                        <div class="orch-ap-field" id="orch-ap-layout-category-wrap">
                            <label><?php SEO_AEO_T::e('Categoria'); ?></label>
                            <select id="orch-ap-layout-category"></select>
                        </div>
                        <div class="orch-ap-field">
                            <label><?php SEO_AEO_T::e('Autore'); ?></label>
                            <select id="orch-ap-layout-author"></select>
                        </div>
                    </div>
                </div>
            </details>

            <div style="text-align:right;">
                <button type="button" class="orch-ap-btn-primary" id="orch-ap-create-btn"><?php SEO_AEO_T::e('🚀 Crea job'); ?></button>
            </div>
        </div>
    </div>

    <!-- 3.31.5: REVIEW QUEUE (articoli generati in attesa) -->
    <div class="orch-ap-queue-section">
        <h2><?php SEO_AEO_T::e('📋 Coda articoli generati · in attesa di review'); ?></h2>
        <div class="orch-ap-queue-toolbar">
            <label><?php SEO_AEO_T::e('Job:'); ?>
                <select id="orch-ap-queue-job">
                    <option value="0"><?php SEO_AEO_T::e('Tutti'); ?></option>
                </select>
            </label>
            <label><?php SEO_AEO_T::e('Periodo:'); ?>
                <select id="orch-ap-queue-days">
                    <option value="7" selected><?php SEO_AEO_T::e('Ultimi 7 giorni'); ?></option>
                    <option value="30"><?php SEO_AEO_T::e('Ultimi 30 giorni'); ?></option>
                    <option value="90"><?php SEO_AEO_T::e('Ultimi 90 giorni'); ?></option>
                </select>
            </label>
            <button type="button" class="button" id="orch-ap-queue-refresh"><?php SEO_AEO_T::e('Aggiorna'); ?></button>
            <span class="orch-ap-queue-bulk" style="margin-left:auto;display:none;">
                <span id="orch-ap-queue-selected-count">0</span>
                <button type="button" class="button button-primary" id="orch-ap-queue-bulk-publish"><?php SEO_AEO_T::e('Pubblica selezionati'); ?></button>
                <button type="button" class="button" id="orch-ap-queue-bulk-discard" style="color:#b91c1c;"><?php SEO_AEO_T::e('Scarta selezionati'); ?></button>
            </span>
        </div>
        <div id="orch-ap-queue-list">
            <div style="padding:30px;text-align:center;color:#9ca3af;font-size:13px;"><?php SEO_AEO_T::e('Caricamento coda...'); ?></div>
        </div>
    </div>

    <!-- JOB LIST -->
    <div class="orch-ap-jobs">
        <h2><?php SEO_AEO_T::e('📋 Job attivi'); ?></h2>
        <div id="orch-ap-jobs-list">
            <div style="padding:30px;text-align:center;color:#6b7280;"><span class="spinner is-active" style="float:none;"></span> <?php SEO_AEO_T::e('Caricamento job...'); ?></div>
        </div>
    </div>

    <!-- LOG -->
    <div class="orch-ap-log-section">
        <h2><?php SEO_AEO_T::e('📜 Log ultimi run'); ?></h2>
        <div id="orch-ap-log-list"></div>
    </div>

    <!-- KEYWORD PICKER (riusabile) -->
    <?php
    $seo_aeo_kp = array(
        'mode'    => 'csv',
        'targets' => array(),  // verra' gestito custom da JS
        'label'   => 'Pesca keyword per il job',
    );
    $seo_aeo_kp_partial = dirname(__FILE__) . '/partials/keyword-picker.php';
    if (file_exists($seo_aeo_kp_partial)) include $seo_aeo_kp_partial;
    ?>
</div>

<?php ob_start(); ?>
.orch-ap-page { max-width: 1200px; margin-right: 20px; }

.orch-ap-hero { position: relative; margin: 14px 0 22px; padding: 32px 40px; border-radius: 16px; overflow: hidden; color: #fff; }
.orch-ap-hero-bg { position: absolute; inset: 0; background: linear-gradient(120deg, #0A0E27 0%, #1e3a8a 35%, #2563EB 70%, #06B6D4 100%); }
.orch-ap-hero-bg::after { content: ''; position: absolute; inset: 0; background: radial-gradient(700px 350px at 90% -20%, rgba(255,255,255,0.18), transparent 60%); }
.orch-ap-hero-inner { position: relative; display: flex; gap: 26px; align-items: center; }
.orch-ap-hero-icon { font-size: 76px; line-height: 1; }
.orch-ap-hero-eyebrow { font-size: 12px; letter-spacing: 1.2px; text-transform: uppercase; opacity: 0.85; font-weight: 600; }
.orch-ap-hero-title { margin: 8px 0 10px; font-size: 30px; font-weight: 800; color: #fff; }
.orch-ap-hero-sub { margin: 0; font-size: 14.5px; line-height: 1.55; opacity: 0.92; max-width: 720px; }
.orch-ap-hero-badge { display: inline-block; margin-top: 14px; padding: 6px 14px; background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.3); border-radius: 999px; font-size: 12px; font-weight: 600; }
.orch-ap-hero-badge-warn { background: rgba(245,158,11,0.25); border-color: rgba(245,158,11,0.5); }

.orch-ap-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px; margin-bottom: 24px; }
.orch-ap-info-item { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px 16px; font-size: 13px; line-height: 1.5; }
.orch-ap-info-item strong { display: block; margin-bottom: 4px; color: #0A0E27; }
.orch-ap-info-item span { color: #4b5563; }

.orch-ap-create { background: #fff; border: 1.5px solid #e5e7eb; border-radius: 12px; padding: 26px 28px; margin-bottom: 26px; }
.orch-ap-create h2 { margin: 0 0 20px; font-size: 18px; color: #0A0E27; }
.orch-ap-form { display: flex; flex-direction: column; gap: 16px; }
.orch-ap-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; }
.orch-ap-field label { display: block; font-weight: 600; font-size: 13px; color: #1a0b2e; margin-bottom: 6px; }
.orch-ap-field input[type="text"], .orch-ap-field select { width: 100%; padding: 9px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 13px; }
.orch-ap-field input:focus, .orch-ap-field select:focus { outline: none; border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
.orch-ap-field small { display: block; margin-top: 5px; font-size: 11.5px; color: #6b7280; }
.orch-ap-field-checkbox { padding-top: 22px; }
.orch-ap-field-checkbox label { font-weight: 500; }
.orch-ap-btn-primary { padding: 12px 26px; background: linear-gradient(135deg, #2563EB, #06B6D4); color: #fff; border: none; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; }
.orch-ap-btn-primary:hover { background: linear-gradient(135deg, #1d4ed8, #0891b2); }
.orch-ap-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

.orch-ap-jobs, .orch-ap-log-section { margin-top: 24px; }
.orch-ap-jobs h2, .orch-ap-log-section h2 { font-size: 18px; color: #0A0E27; margin: 0 0 14px; }

.orch-ap-job-card { background: #fff; border: 1.5px solid #e5e7eb; border-radius: 12px; padding: 18px 22px; margin-bottom: 12px; }
.orch-ap-job-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; flex-wrap: wrap; }
.orch-ap-job-name { font-size: 16px; font-weight: 700; color: #0A0E27; margin: 0; }
.orch-ap-job-meta { display: flex; gap: 10px 18px; flex-wrap: wrap; margin-top: 8px; font-size: 12px; color: #6b7280; }
.orch-ap-job-meta b { color: #1a0b2e; font-weight: 700; }
.orch-ap-status-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
.orch-ap-status-active { background: #dcfce7; color: #065f46; }
.orch-ap-status-paused { background: #fef3c7; color: #92400e; }
.orch-ap-job-actions { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px; }
.orch-ap-progress { background: #f3f4f6; border-radius: 999px; height: 6px; margin-top: 10px; overflow: hidden; }
.orch-ap-progress-fill { background: linear-gradient(90deg, #2563EB, #06B6D4); height: 100%; transition: width 0.3s; }

.orch-ap-log-table { width: 100%; border-collapse: collapse; background: #fff; border: 1.5px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
.orch-ap-log-table th { background: #f9fafb; padding: 10px 14px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px; color: #6b7280; text-align: left; }
.orch-ap-log-table td { padding: 10px 14px; font-size: 12.5px; border-top: 1px solid #f3f4f6; }
.orch-ap-log-success { color: #059669; font-weight: 600; }
.orch-ap-log-error { color: #dc2626; font-weight: 600; }
.orch-ap-log-completed { color: #6b7280; font-style: italic; }

/* 3.31.5 — Layout override accordion */
.orch-ap-layout-override { border: 1px dashed #cbd5e1; border-radius: 10px; padding: 14px 18px; background: #f8fafc; }
.orch-ap-layout-override summary { cursor: pointer; font-weight: 600; color: #1e293b; font-size: 13px; }
.orch-ap-layout-help { margin: 8px 0 10px; font-size: 12px; color: #64748b; }
.orch-ap-layout-toggle { display: flex; gap: 8px; align-items: center; font-size: 13px; color: #1e293b; }

/* 3.31.5 — Review queue */
.orch-ap-queue-section { margin-top: 28px; }
.orch-ap-queue-section h2 { font-size: 18px; color: #0A0E27; margin: 0 0 14px; }
.orch-ap-queue-toolbar { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; padding: 12px 14px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; margin-bottom: 12px; }
.orch-ap-queue-toolbar label { display: inline-flex; gap: 6px; align-items: center; font-size: 12.5px; color: #475569; }
.orch-ap-queue-toolbar select { padding: 5px 8px; border-radius: 6px; border: 1px solid #cbd5e1; }
.orch-ap-queue-bulk { display: flex; gap: 8px; align-items: center; font-size: 12px; color: #475569; }

.orch-ap-queue-card { display: grid; grid-template-columns: 32px 84px 1fr auto; gap: 14px; align-items: center; padding: 12px 16px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; margin-bottom: 8px; transition: background .15s; }
.orch-ap-queue-card:hover { background: #f8fafc; }
.orch-ap-queue-thumb { width: 84px; height: 64px; border-radius: 8px; background: #f1f5f9 center/cover no-repeat; flex-shrink: 0; }
.orch-ap-queue-info { min-width: 0; }
.orch-ap-queue-title { font-weight: 600; color: #0A0E27; font-size: 14px; margin: 0 0 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.orch-ap-queue-meta { font-size: 12px; color: #64748b; display: flex; gap: 12px; flex-wrap: wrap; }
.orch-ap-queue-meta b { color: #1e293b; font-weight: 600; }
.orch-ap-queue-actions { display: flex; gap: 6px; flex-wrap: wrap; }
.orch-ap-queue-actions .button { font-size: 12px; padding: 4px 10px; }

/* 3.31.5 — Run-now success notice */
.orch-ap-success-notice { background: linear-gradient(135deg, #ecfdf5, #d1fae5); border: 1.5px solid #10B981; border-radius: 12px; padding: 16px 20px; margin: 14px 0 18px; box-shadow: 0 4px 14px rgba(16,185,129,0.15); position: relative; animation: orch-ap-slide-in 0.4s ease; }
.orch-ap-success-notice .orch-ap-notice-title { font-size: 15px; font-weight: 700; color: #065f46; margin: 0 0 6px; }
.orch-ap-success-notice .orch-ap-notice-meta { font-size: 13px; color: #047857; margin-bottom: 10px; }
.orch-ap-success-notice .orch-ap-notice-meta a { color: #065f46; font-weight: 600; }
.orch-ap-success-notice .orch-ap-notice-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.orch-ap-success-notice .orch-ap-notice-actions .button { font-size: 12.5px; }
.orch-ap-success-notice .orch-ap-notice-close { position: absolute; top: 10px; right: 12px; cursor: pointer; color: #047857; font-size: 18px; line-height: 1; background: none; border: none; padding: 0; }
@keyframes orch-ap-slide-in { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
@keyframes orch-ap-flash-row { 0% { background: #fef3c7; } 100% { background: transparent; } }
.orch-ap-log-flash { animation: orch-ap-flash-row 3s ease; }
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

<?php ob_start(); ?>
jQuery(function($) {
    var ajaxurl = window.ajaxurl || '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
    var nonce = (window.seoAeoOrchestra && window.seoAeoOrchestra.nonce) || '<?php echo esc_js(wp_create_nonce('seo_aeo_orchestra_nonce')); ?>';

    function escHtml(s) {
        return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    function loadJobs() {
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_autopilot_list', nonce: nonce }, function(resp) {
            if (!resp || !resp.success || !resp.jobs || !resp.jobs.length) {
                $('#orch-ap-jobs-list').html('<div style="padding:30px;text-align:center;color:#6b7280;background:#fafafa;border-radius:10px;">Nessun job ancora. Crea il primo qui sopra.</div>');
                return;
            }
            var html = '';
            $.each(resp.jobs, function(i, j) {
                var pct = j.keywords_total > 0 ? Math.round((j.keywords_used / j.keywords_total) * 100) : 0;
                var nextRun = j.next_run ? new Date(j.next_run.replace(' ', 'T') + 'Z').toLocaleString(SeoAeoOrchestra.bcp47()) : '—';
                html += '<div class="orch-ap-job-card" data-job-id="' + j.id + '">';
                html += '<div class="orch-ap-job-head">';
                html +=   '<div>';
                html +=     '<h3 class="orch-ap-job-name">' + escHtml(j.name) + '</h3>';
                html +=     '<div class="orch-ap-job-meta">' +
                                '<span><b>Stato:</b> <span class="orch-ap-status-badge orch-ap-status-' + j.status + '">' + (j.status === 'active' ? 'Attivo' : 'In pausa') + '</span></span>' +
                                '<span><b>Frequenza:</b> ogni ' + j.frequency_days + 'gg</span>' +
                                '<span><b>Lunghezza:</b> ' + escHtml(j.length) + '</span>' +
                                '<span><b>Stato post:</b> ' + (j.post_status === 'publish' ? 'Pubblicato' : 'Bozza') + '</span>' +
                                '<span><b>Generati:</b> ' + j.generated_count + '</span>' +
                                '<span><b>Prossimo run:</b> ' + nextRun + '</span>' +
                            '</div>';
                html +=   '</div>';
                html +=   '<div class="orch-ap-job-actions">';
                if (j.status === 'active') {
                    html += '<button class="button orch-ap-toggle" data-id="' + j.id + '" data-target="paused">⏸ Pausa</button>';
                } else {
                    html += '<button class="button button-primary orch-ap-toggle" data-id="' + j.id + '" data-target="active">▶ Riattiva</button>';
                }
                html +=     '<button class="button orch-ap-run-now" data-id="' + j.id + '">⚡ Esegui ora</button>';
                html +=     '<button class="button orch-ap-delete" data-id="' + j.id + '" style="color:#b91c1c;">🗑 Elimina</button>';
                html +=   '</div>';
                html += '</div>';
                html += '<div class="orch-ap-progress"><div class="orch-ap-progress-fill" style="width:' + pct + '%;"></div></div>';
                html += '<div style="font-size:11px;color:#6b7280;margin-top:6px;">' + j.keywords_used + ' di ' + j.keywords_total + ' keyword usate (' + pct + '%) · ' + j.keywords_remaining + ' restanti</div>';
                html += '</div>';
            });
            $('#orch-ap-jobs-list').html(html);
        });
    }

    function loadLog() {
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_autopilot_log', nonce: nonce, job_id: 0 }, function(resp) {
            if (!resp || !resp.success || !resp.log || !resp.log.length) {
                $('#orch-ap-log-list').html('<div style="padding:20px;text-align:center;color:#9ca3af;font-size:13px;">Nessuna esecuzione ancora.</div>');
                return;
            }
            var html = '<table class="orch-ap-log-table"><thead><tr>' +
                '<th>Data</th><th>Job</th><th>Keyword</th><th>Stato</th><th>Risultato</th>' +
                '</tr></thead><tbody>';
            $.each(resp.log, function(i, l) {
                var statusClass = 'orch-ap-log-' + l.status;
                var date = l.created_at ? new Date(l.created_at.replace(' ', 'T') + 'Z').toLocaleString(SeoAeoOrchestra.bcp47()) : '';
                var result = '';
                if (l.status === 'success' && l.post_id > 0) {
                    result = '<a href="<?php echo esc_js(admin_url('post.php?action=edit&post=')); ?>' + l.post_id + '" target="_blank">Apri post #' + l.post_id + ' →</a>';
                } else if (l.error_message) {
                    result = '<span style="color:#b91c1c;">' + escHtml(l.error_message) + '</span>';
                }
                html += '<tr>' +
                    '<td>' + escHtml(date) + '</td>' +
                    '<td>#' + l.job_id + '</td>' +
                    '<td>' + escHtml(l.keyword) + '</td>' +
                    '<td class="' + statusClass + '">' + escHtml(l.status) + '</td>' +
                    '<td>' + result + '</td>' +
                '</tr>';
            });
            html += '</tbody></table>';
            $('#orch-ap-log-list').html(html);
        });
    }

    // Auto-Pilot: il keyword picker apre il drawer e poi cattura le keyword via custom event
    $('#orch-ap-pick-keywords').on('click', function() {
        $('.orch-kp-pill').first().trigger('click');
    });

    // 3.26.0 — Listen `orch-kp:applied` event dal picker (sparato PRIMA del clear checkbox)
    $(document).on('orch-kp:applied', function(e, selected) {
        if (!selected || !selected.length) return;
        var sel = selected.map(function(kw) {
            return { keyword: kw.keyword, topic: kw.topic || kw.keyword };
        });
        $('#orch-ap-keywords-json').val(JSON.stringify(sel));
        $('#orch-ap-kw-status').html('<span style="color:#059669;font-weight:600;">✓ ' + sel.length + ' keyword selezionate</span>');
    });

    $('#orch-ap-create-btn').on('click', function() {
        var name = $('#orch-ap-name').val().trim();
        var kwJson = $('#orch-ap-keywords-json').val();
        var kws = [];
        try { kws = JSON.parse(kwJson) || []; } catch (e) {}

        if (!name) { alert('Inserisci un nome per il job.'); return; }
        if (!kws.length) { alert('Seleziona almeno 1 keyword dal Keyword Picker.'); return; }

        var $btn = $(this).prop('disabled', true).text('Creo...');
        var layoutEnabled = $('#orch-ap-layout-enabled').is(':checked') ? 1 : 0;
        $.post(ajaxurl, {
            action: 'seo_aeo_orchestra_autopilot_create',
            nonce: nonce,
            name: name,
            keywords: JSON.stringify(kws),
            frequency_days: $('#orch-ap-freq').val(),
            length: $('#orch-ap-length').val(),
            post_status: $('#orch-ap-post-status').val(),
            post_category_id: $('#orch-ap-category').val(),
            post_author_id: $('#orch-ap-author').val(),
            include_image: $('#orch-ap-include-image').is(':checked') ? 1 : 0,
            // 3.31.5: per-job layout override
            layout_override_enabled: layoutEnabled,
            layout_post_type: $('#orch-ap-layout-post-type').val() || '',
            layout_template:  $('#orch-ap-layout-template').val() || 'default',
            layout_category:  $('#orch-ap-layout-category').val() || 0,
            layout_author:    $('#orch-ap-layout-author').val() || 0,
        }).done(function(resp) {
            $btn.prop('disabled', false).text('🚀 Crea job');
            if (!resp || !resp.success) {
                alert('Errore: ' + (resp && resp.error || 'sconosciuto'));
                return;
            }
            // Reset form
            $('#orch-ap-name').val('');
            $('#orch-ap-keywords-json').val('[]');
            $('#orch-ap-kw-status').text('Nessuna keyword selezionata');
            loadJobs();
        }).fail(function() {
            $btn.prop('disabled', false).text('🚀 Crea job');
            alert('Errore di rete');
        });
    });

    $(document).on('click', '.orch-ap-toggle', function() {
        var id = $(this).data('id');
        var target = $(this).data('target');
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_autopilot_toggle', nonce: nonce, id: id, status: target }, loadJobs);
    });

    $(document).on('click', '.orch-ap-delete', function() {
        if (!confirm('Eliminare il job? Operazione irreversibile (i post creati restano).')) return;
        var id = $(this).data('id');
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_autopilot_delete', nonce: nonce, id: id }, loadJobs);
    });

    // 3.31.5: notice persistente anziché alert()
    function pushSuccessNotice(opts) {
        var elapsed = opts.elapsed != null ? opts.elapsed : '';
        var keyword = opts.keyword || '';
        var title   = opts.title || '';
        var postId  = opts.post_id || 0;
        var editUrl = opts.edit_url || '';
        var previewUrl = opts.preview_url || '';
        var html = '<div class="orch-ap-success-notice">';
        html +=     '<button class="orch-ap-notice-close" type="button" aria-label="Chiudi">&times;</button>';
        html +=     '<div class="orch-ap-notice-title">✓ Articolo creato' + (elapsed ? ' in ' + elapsed + 's' : '') + '</div>';
        html +=     '<div class="orch-ap-notice-meta">Keyword: <b>' + escHtml(keyword) + '</b>';
        if (title) html += ' · Post: <a href="' + escHtml(editUrl) + '" target="_blank">"' + escHtml(title) + '"</a>';
        html +=     '</div>';
        html +=     '<div class="orch-ap-notice-actions">';
        if (editUrl)   html += '<a href="' + escHtml(editUrl) + '" target="_blank" class="button">Apri editor →</a>';
        if (previewUrl)html += '<a href="' + escHtml(previewUrl) + '" target="_blank" class="button">Anteprima →</a>';
        html += '<button type="button" class="button button-primary orch-ap-notice-publish" data-post-id="' + postId + '">Pubblica subito →</button>';
        html +=     '</div>';
        html += '</div>';
        var $n = $(html);
        $('#orch-ap-notices').prepend($n);
        $('html, body').animate({ scrollTop: 0 }, 250);
        // Auto-dismiss after 30s
        setTimeout(function() { $n.fadeOut(400, function() { $(this).remove(); }); }, 30000);
    }
    $(document).on('click', '.orch-ap-notice-close', function() {
        $(this).closest('.orch-ap-success-notice').fadeOut(200, function() { $(this).remove(); });
    });
    $(document).on('click', '.orch-ap-notice-publish', function() {
        var pid = $(this).data('post-id');
        var $btn = $(this).prop('disabled', true).text('Pubblico...');
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_autopilot_publish', nonce: nonce, post_id: pid }, function(resp) {
            if (resp && resp.success) {
                $btn.text('✓ Pubblicato').css('background','#10B981');
                loadQueue();
            } else {
                $btn.prop('disabled', false).text('Pubblica subito →');
            }
        }).fail(function() { $btn.prop('disabled', false).text('Pubblica subito →'); });
    });

    $(document).on('click', '.orch-ap-run-now', function() {
        var id = $(this).data('id');
        if (!confirm('Esegue 1 articolo subito (consuma ~25 crediti). Procedere?')) return;
        var $btn = $(this).prop('disabled', true).text('⏳ Generazione...');
        var t0 = Date.now();
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_autopilot_run_now', nonce: nonce, id: id }, function(resp) {
            $btn.prop('disabled', false).text('⚡ Esegui ora');
            var elapsed = Math.round((Date.now() - t0) / 1000);
            if (resp && resp.success && resp.post_id) {
                pushSuccessNotice({
                    elapsed: elapsed,
                    keyword: resp.keyword || '',
                    title: resp.title || '',
                    post_id: resp.post_id,
                    edit_url: resp.edit_url || ('<?php echo esc_js(admin_url('post.php?action=edit&post=')); ?>' + resp.post_id),
                    preview_url: resp.preview_url || ''
                });
                // Highlight più recente nel Log
                setTimeout(function() {
                    $('.orch-ap-log-table tbody tr').first().addClass('orch-ap-log-flash');
                }, 600);
            } else if (resp && resp.completed) {
                alert('Tutte le keyword del set sono state generate. Job pausato.');
            } else {
                alert('Errore: ' + (resp && resp.error || 'sconosciuto'));
            }
            loadJobs();
            loadLog();
            loadQueue();
        }).fail(function() {
            $btn.prop('disabled', false).text('⚡ Esegui ora');
            alert('Errore di rete o timeout (la generazione potrebbe richiedere fino a 90s — controlla i log).');
            loadJobs();
            loadLog();
        });
    });

    // ─────────────────────────────────────────────────────────────────────
    // 3.31.5 — LAYOUT OVERRIDE per-job (popolamento dropdown via ajax_layout_discover)
    // ─────────────────────────────────────────────────────────────────────
    var layoutData = null;
    function loadLayoutData() {
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_layout_discover', nonce: nonce }, function(resp) {
            if (!resp || !resp.success) return;
            layoutData = resp;
            // Post types
            var $pt = $('#orch-ap-layout-post-type').empty();
            (resp.post_types || []).forEach(function(p) { $pt.append('<option value="' + escHtml(p.name) + '">' + escHtml(p.label) + '</option>'); });
            // Categories
            var $cat = $('#orch-ap-layout-category').empty();
            (resp.categories || []).forEach(function(c) { $cat.append('<option value="' + escHtml(c.id) + '">' + escHtml(c.name) + '</option>'); });
            // Authors
            var $au = $('#orch-ap-layout-author').empty();
            (resp.authors || []).forEach(function(a) { $au.append('<option value="' + escHtml(a.id) + '">' + escHtml(a.name) + '</option>'); });
            populateLayoutTemplates($pt.val() || 'post');
        });
    }
    function populateLayoutTemplates(postType) {
        var $sel = $('#orch-ap-layout-template').empty();
        var list = (layoutData && layoutData.templates && layoutData.templates[postType]) ? layoutData.templates[postType] : [{file:'default', name:'Default'}];
        list.forEach(function(t) { $sel.append('<option value="' + escHtml(t.file) + '">' + escHtml(t.name) + '</option>'); });
    }
    $(document).on('change', '#orch-ap-layout-post-type', function() {
        var pt = $(this).val();
        populateLayoutTemplates(pt);
        $('#orch-ap-layout-category-wrap').toggle(pt === 'post');
    });
    $(document).on('change', '#orch-ap-layout-enabled', function() {
        $('#orch-ap-layout-fields').toggle($(this).is(':checked'));
        if ($(this).is(':checked') && !layoutData) loadLayoutData();
    });

    // ─────────────────────────────────────────────────────────────────────
    // 3.31.5 — REVIEW QUEUE
    // ─────────────────────────────────────────────────────────────────────
    function loadQueue() {
        var jobId = parseInt($('#orch-ap-queue-job').val() || 0, 10);
        var days  = parseInt($('#orch-ap-queue-days').val() || 7, 10);
        $.post(ajaxurl, {
            action: 'seo_aeo_orchestra_autopilot_queue',
            nonce: nonce,
            job_id: jobId,
            days: days,
        }, function(resp) {
            if (!resp || !resp.success) {
                $('#orch-ap-queue-list').html('<div style="padding:20px;color:#b91c1c;">Errore caricamento coda.</div>');
                return;
            }
            var items = resp.items || [];
            if (items.length === 0) {
                $('#orch-ap-queue-list').html('<div style="padding:30px;text-align:center;color:#9ca3af;font-size:13px;background:#fff;border:1px dashed #e5e7eb;border-radius:10px;">Nessun articolo in attesa di review nel periodo selezionato.</div>');
                $('.orch-ap-queue-bulk').hide();
                return;
            }
            var html = '';
            items.forEach(function(it) {
                var thumbStyle = it.thumb_url ? 'background-image:url(' + it.thumb_url + ');' : '';
                html += '<div class="orch-ap-queue-card" data-post-id="' + it.post_id + '">';
                html +=   '<input type="checkbox" class="orch-ap-queue-check" value="' + it.post_id + '">';
                html +=   '<div class="orch-ap-queue-thumb" style="' + thumbStyle + '"></div>';
                html +=   '<div class="orch-ap-queue-info">';
                html +=     '<div class="orch-ap-queue-title">' + escHtml(it.title) + '</div>';
                html +=     '<div class="orch-ap-queue-meta">' +
                                '<span><b>Keyword:</b> ' + escHtml(it.keyword) + '</span>' +
                                '<span><b>Job:</b> ' + escHtml(it.job_name) + '</span>' +
                                '<span><b>Parole:</b> ' + it.word_count + '</span>' +
                                '<span><b>Generato:</b> ' + escHtml(it.created_at) + '</span>' +
                            '</div>';
                html +=   '</div>';
                html +=   '<div class="orch-ap-queue-actions">';
                html +=     '<a href="' + escHtml(it.preview_url) + '" target="_blank" class="button">👁 Anteprima</a>';
                html +=     '<a href="' + escHtml(it.edit_url) + '" target="_blank" class="button">✏️ Modifica</a>';
                html +=     '<button class="button orch-ap-queue-regen" data-post-id="' + it.post_id + '" data-keyword="' + escHtml(it.keyword) + '">🔄 Rigenera</button>';
                html +=     '<button class="button button-primary orch-ap-queue-publish" data-post-id="' + it.post_id + '">✅ Pubblica</button>';
                html +=     '<button class="button orch-ap-queue-discard" data-post-id="' + it.post_id + '" style="color:#b91c1c;">🗑 Scarta</button>';
                html +=   '</div>';
                html += '</div>';
            });
            $('#orch-ap-queue-list').html(html);
            updateBulkUI();
        });
    }
    function updateBulkUI() {
        var n = $('.orch-ap-queue-check:checked').length;
        $('#orch-ap-queue-selected-count').text(n + ' selezionati');
        $('.orch-ap-queue-bulk').toggle(n > 0);
    }
    function syncQueueJobFilter() {
        // Popola filtro Job dalla lista jobs già caricata
        var $sel = $('#orch-ap-queue-job');
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_autopilot_list', nonce: nonce }, function(resp) {
            if (!resp || !resp.jobs) return;
            var cur = $sel.val();
            $sel.find('option[value!="0"]').remove();
            resp.jobs.forEach(function(j) { $sel.append('<option value="' + j.id + '">' + escHtml(j.name) + '</option>'); });
            if (cur) $sel.val(cur);
        });
    }
    $(document).on('change', '.orch-ap-queue-check', updateBulkUI);
    $('#orch-ap-queue-refresh').on('click', loadQueue);
    $('#orch-ap-queue-job, #orch-ap-queue-days').on('change', loadQueue);

    $(document).on('click', '.orch-ap-queue-publish', function() {
        var pid = $(this).data('post-id');
        var $btn = $(this).prop('disabled', true).text('Pubblico...');
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_autopilot_publish', nonce: nonce, post_id: pid }, function(resp) {
            if (resp && resp.success) loadQueue();
            else { $btn.prop('disabled', false).text('✅ Pubblica'); alert('Errore pubblicazione'); }
        }).fail(function() { $btn.prop('disabled', false).text('✅ Pubblica'); });
    });
    $(document).on('click', '.orch-ap-queue-discard', function() {
        if (!confirm('Scartare l\'articolo (cestino)?')) return;
        var pid = $(this).data('post-id');
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_autopilot_discard', nonce: nonce, post_id: pid }, function(resp) {
            if (resp && resp.success) loadQueue();
            else alert('Errore scarto');
        });
    });
    $(document).on('click', '.orch-ap-queue-regen', function() {
        if (!confirm('Rigenera l\'articolo? Consuma ~25 crediti e sostituisce contenuto + immagine.')) return;
        var pid = $(this).data('post-id');
        var kw  = $(this).data('keyword');
        var $btn = $(this).prop('disabled', true).text('⏳ Rigenero...');
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_autopilot_regenerate', nonce: nonce, post_id: pid, keyword: kw }, function(resp) {
            $btn.prop('disabled', false).text('🔄 Rigenera');
            if (resp && resp.success) {
                alert('✓ Articolo rigenerato. Apri l\'editor per vedere il nuovo contenuto.');
                loadQueue();
            } else alert('Errore: ' + (resp && resp.error || 'sconosciuto'));
        }).fail(function() { $btn.prop('disabled', false).text('🔄 Rigenera'); });
    });
    $('#orch-ap-queue-bulk-publish').on('click', function() {
        var ids = $('.orch-ap-queue-check:checked').map(function() { return parseInt(this.value, 10); }).get();
        if (!ids.length) return;
        if (!confirm('Pubblicare ' + ids.length + ' articoli?')) return;
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_autopilot_publish', nonce: nonce, post_ids: ids }, function(resp) {
            if (resp && resp.success) loadQueue();
            else alert('Errore bulk publish');
        });
    });
    $('#orch-ap-queue-bulk-discard').on('click', function() {
        var ids = $('.orch-ap-queue-check:checked').map(function() { return parseInt(this.value, 10); }).get();
        if (!ids.length) return;
        if (!confirm('Scartare ' + ids.length + ' articoli? Operazione irreversibile.')) return;
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_autopilot_discard', nonce: nonce, post_ids: ids }, function(resp) {
            if (resp && resp.success) loadQueue();
            else alert('Errore bulk discard');
        });
    });

    loadJobs();
    loadLog();
    loadQueue();
    syncQueueJobFilter();
    // Auto-refresh ogni 30s mentre la pagina è aperta
    setInterval(function() { loadJobs(); loadLog(); loadQueue(); }, 30000);
});
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>

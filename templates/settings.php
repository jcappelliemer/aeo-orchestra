<?php if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.VariableNotPrefixed
// Reason: template scope. Variables are local to this include/template,
// passed by the calling function via include/require. The Plugin Check
// heuristic doesn't distinguish template-scope locals from globals.
$seo_aeo_license_type = get_option('seo_aeo_orchestra_license_type', 'starter');
$seo_aeo_can_hide_branding = in_array($seo_aeo_license_type, array('professional', 'team', 'b2b_custom'));
$seo_aeo_T = function($s) { return class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t($s)) : esc_html($s); };
?>
<div class="wrap orchestra-admin">
    <h1 style="display:none;"><?php echo esc_html(SEO_AEO_T::t('Impostazioni AEO Orchestra')); ?></h1>

    <form method="post" action="options.php">
        <?php settings_fields('seo_aeo_orchestra_settings'); ?>

        <h2><?php echo esc_html(SEO_AEO_T::t('Licenza')); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">License Key</th>
                <td>
                    <input type="text" name="seo_aeo_orchestra_license_key" id="license_key"
                           value="<?php echo esc_attr(get_option('seo_aeo_orchestra_license_key')); ?>"
                           class="regular-text" placeholder="ORCH-XXXX-XXXX-XXXX" />
                    <button type="button" class="button" id="validate-license"><?php echo esc_html(SEO_AEO_T::t('Verifica Licenza')); ?></button>
                    <button type="button" class="button" id="test-connection"><?php echo esc_html(SEO_AEO_T::t('Test Connessione')); ?></button>
                    <div id="license-status" style="margin-top: 10px;"></div>
                    <div id="connection-status" style="margin-top: 5px;"></div>
                    <p class="description">
                        <?php echo esc_html(SEO_AEO_T::t('Inserisci la license key ricevuta dopo l\'acquisto.')); ?><br>
                        <strong>API URL:</strong> <code><?php echo esc_html(defined('SEO_AEO_API_URL') ? SEO_AEO_API_URL : 'Non definita'); ?></code>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html(SEO_AEO_T::t('Lingua interfaccia')); ?></th>
                <td>
                    <?php $seo_aeo_cur_lang = get_option('seo_aeo_orchestra_language', 'it'); ?>
                    <select name="seo_aeo_orchestra_language">
                        <option value="it" <?php selected($seo_aeo_cur_lang, 'it'); ?>>Italiano</option>
                        <option value="en" <?php selected($seo_aeo_cur_lang, 'en'); ?>>English</option>
                        <option value="es" <?php selected($seo_aeo_cur_lang, 'es'); ?>>Espanol</option>
                        <option value="fr" <?php selected($seo_aeo_cur_lang, 'fr'); ?>>Francais</option>
                        <option value="de" <?php selected($seo_aeo_cur_lang, 'de'); ?>>Deutsch</option>
                    </select>
                    <p class="description">
                        <?php echo esc_html(SEO_AEO_T::t('Lingua dei menu, titoli e CTA del plugin. Salva e aggiorna la pagina per applicare.')); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html(SEO_AEO_T::t('Auto Meta Tags')); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="seo_aeo_orchestra_auto_meta" value="yes"
                               <?php checked(get_option('seo_aeo_orchestra_auto_meta'), 'yes'); ?> />
                        <?php echo esc_html(SEO_AEO_T::t('Genera automaticamente meta tags per i nuovi post')); ?>
                    </label>
                </td>
            </tr>
        </table>

        <hr>
        <h2><?php echo esc_html(SEO_AEO_T::t('Score Widget')); ?></h2>
        <p class="description"><?php echo esc_html(SEO_AEO_T::t('Mostra un badge SEO + AEO in basso a destra del tuo sito. I visitatori vedranno il punteggio della pagina corrente.')); ?></p>
        <table class="form-table">
            <tr>
                <th scope="row"><?php echo esc_html(SEO_AEO_T::t('Abilita Widget')); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="seo_aeo_orchestra_widget_enabled" value="yes"
                               <?php checked(get_option('seo_aeo_orchestra_widget_enabled'), 'yes'); ?> />
                        <?php echo esc_html(SEO_AEO_T::t('Mostra Score Widget nel frontend')); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html(SEO_AEO_T::t('Visibilita')); ?></th>
                <td>
                    <select name="seo_aeo_orchestra_widget_visibility">
                        <option value="admin_only" <?php selected(get_option('seo_aeo_orchestra_widget_visibility'), 'admin_only'); ?>><?php echo esc_html(SEO_AEO_T::t('Solo Admin (loggato)')); ?></option>
                        <option value="everyone" <?php selected(get_option('seo_aeo_orchestra_widget_visibility'), 'everyone'); ?>><?php echo esc_html(SEO_AEO_T::t('Tutti i visitatori')); ?></option>
                    </select>
                    <p class="description"><?php echo esc_html(SEO_AEO_T::t('Scegli chi puo vedere il widget. "Solo Admin" e utile per monitorare senza mostrarlo ai visitatori.')); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html(SEO_AEO_T::t('Branding')); ?></th>
                <td>
                    <select name="seo_aeo_orchestra_widget_branding" <?php if (!$seo_aeo_can_hide_branding) echo 'disabled'; ?>>
                        <option value="auto" <?php selected(get_option('seo_aeo_orchestra_widget_branding'), 'auto'); ?>><?php echo esc_html(SEO_AEO_T::t('Automatico (basato sul piano)')); ?></option>
                        <?php if ($seo_aeo_can_hide_branding): ?>
                        <option value="always" <?php selected(get_option('seo_aeo_orchestra_widget_branding'), 'always'); ?>><?php echo esc_html(SEO_AEO_T::t('Mostra sempre "Powered by"')); ?></option>
                        <option value="never" <?php selected(get_option('seo_aeo_orchestra_widget_branding'), 'never'); ?>><?php echo esc_html(SEO_AEO_T::t('Nascondi branding')); ?></option>
                        <?php endif; ?>
                    </select>
                    <?php if (!$seo_aeo_can_hide_branding): ?>
                    <p class="description" style="color: #F59E0B;">
                        <?php echo esc_html(SEO_AEO_T::t('Piano Starter: il branding "Powered by AEO Orchestra" e sempre visibile.')); ?><br>
                        <?php echo esc_html(SEO_AEO_T::t('Passa a Professional o Team per nascondere il branding.')); ?>
                    </p>
                    <?php else: ?>
                    <p class="description" style="color: #10B981;">
                        <?php echo esc_html(SEO_AEO_T::t('Il tuo piano')) . ' ' . esc_html(ucfirst($seo_aeo_license_type)) . ' ' . esc_html(SEO_AEO_T::t('ti permette di nascondere il branding.')); ?>
                    </p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <h2><?php echo esc_html(SEO_AEO_T::t('📅 Calendario contenuti — defaults')); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php echo esc_html(SEO_AEO_T::t('Genera draft N giorni prima')); ?></th>
                <td>
                    <?php $seo_aeo_cal_days = (int) get_option('seo_aeo_orchestra_calendar_default_days_before', 1); ?>
                    <select name="seo_aeo_orchestra_calendar_default_days_before">
                        <option value="0" <?php selected($seo_aeo_cal_days, 0); ?>><?php echo esc_html(SEO_AEO_T::t('Stesso giorno')); ?></option>
                        <option value="1" <?php selected($seo_aeo_cal_days, 1); ?>>1</option>
                        <option value="2" <?php selected($seo_aeo_cal_days, 2); ?>>2</option>
                        <option value="3" <?php selected($seo_aeo_cal_days, 3); ?>>3</option>
                        <option value="7" <?php selected($seo_aeo_cal_days, 7); ?>>7</option>
                    </select>
                    <p class="description"><?php echo esc_html(SEO_AEO_T::t('Default per nuovi slot del Calendario contenuti AI.')); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html(SEO_AEO_T::t('Ora pubblicazione default')); ?></th>
                <td>
                    <?php $seo_aeo_cal_hour = (int) get_option('seo_aeo_orchestra_calendar_default_hour', 9); ?>
                    <select name="seo_aeo_orchestra_calendar_default_hour">
                        <?php for ($seo_aeo_h = 0; $seo_aeo_h < 24; $seo_aeo_h++): ?>
                            <option value="<?php echo esc_attr($seo_aeo_h); ?>" <?php selected($seo_aeo_cal_hour, $seo_aeo_h); ?>><?php echo esc_html(sprintf('%02d:00', $seo_aeo_h)); ?></option>
                        <?php endfor; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html(SEO_AEO_T::t('Auto-publish default')); ?></th>
                <td>
                    <?php $seo_aeo_cal_auto = get_option('seo_aeo_orchestra_calendar_default_auto_publish', '1'); ?>
                    <label><input type="radio" name="seo_aeo_orchestra_calendar_default_auto_publish" value="1" <?php checked($seo_aeo_cal_auto, '1'); ?>> <?php echo esc_html(SEO_AEO_T::t('Sì auto-pubblica')); ?></label>
                    &nbsp;
                    <label><input type="radio" name="seo_aeo_orchestra_calendar_default_auto_publish" value="0" <?php checked($seo_aeo_cal_auto, '0'); ?>> <?php echo esc_html(SEO_AEO_T::t('No lascia come draft')); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html(SEO_AEO_T::t('Categoria default')); ?></th>
                <td>
                    <?php
                    $seo_aeo_cal_cat = (int) get_option('seo_aeo_orchestra_calendar_default_category', 0);
                    $seo_aeo_cats = get_categories(array('hide_empty' => false, 'number' => 200));
                    ?>
                    <select name="seo_aeo_orchestra_calendar_default_category">
                        <option value="0">— <?php echo esc_html(SEO_AEO_T::t('Nessuna')); ?> —</option>
                        <?php foreach ($seo_aeo_cats as $seo_aeo_c): ?>
                            <option value="<?php echo absint($seo_aeo_c->term_id); ?>" <?php selected($seo_aeo_cal_cat, (int) $seo_aeo_c->term_id); ?>><?php echo esc_html($seo_aeo_c->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php echo esc_html(SEO_AEO_T::t('Eredita da Layout articoli AI se non impostata.')); ?></p>
                </td>
            </tr>
        </table>

        <?php
        // 3.40.2 — Compatibilità Sito section.
        $aeo_profile = class_exists('SEO_AEO_Site_Scanner') ? SEO_AEO_Site_Scanner::get_profile() : array();
        $aeo_summary = class_exists('SEO_AEO_Capability_Matrix') ? SEO_AEO_Capability_Matrix::get_capability_summary() : array('rows' => array(), 'environment_label' => 'sconosciuto');
        $aeo_env_label = isset($aeo_summary['environment_label']) ? $aeo_summary['environment_label'] : 'sconosciuto';
        ?>
        <h2 style="margin-top:24px;">🧩 <?php echo esc_html(SEO_AEO_T::t('Compatibilita\' Sito')); ?></h2>
        <p class="description">
            <?php echo esc_html(SEO_AEO_T::t('Come AEO Orchestra interagisce con il tuo ambiente WordPress: page builder rilevato, modalita\' headless, e mappa delle capacita\' per ogni tipo di azione AI.')); ?>
        </p>
        <table class="form-table aeo-compat-table">
            <tr>
                <th scope="row"><?php echo esc_html(SEO_AEO_T::t('Page builder rilevato')); ?></th>
                <td>
                    <strong id="aeo-compat-builder"><?php echo esc_html(isset($aeo_profile['builder']) ? $aeo_profile['builder'] : 'unknown'); ?></strong>
                    <span class="aeo-compat-confidence" id="aeo-compat-builder-conf">
                        (<?php echo (int) (isset($aeo_profile['builder_confidence']) ? $aeo_profile['builder_confidence'] : 0); ?>%)
                    </span>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html(SEO_AEO_T::t('Sito headless')); ?></th>
                <td>
                    <strong id="aeo-compat-headless"><?php echo !empty($aeo_profile['is_headless']) ? esc_html(SEO_AEO_T::t('Si')) . ' (' . esc_html(isset($aeo_profile['headless_mode']) ? $aeo_profile['headless_mode'] : 'rest') . ')' : esc_html(SEO_AEO_T::t('No')); ?></strong>
                    <span class="aeo-compat-confidence" id="aeo-compat-headless-conf">
                        (<?php echo (int) (isset($aeo_profile['headless_confidence']) ? $aeo_profile['headless_confidence'] : 0); ?>%)
                    </span>
                    <?php if (!empty($aeo_profile['headless_signals']) && is_array($aeo_profile['headless_signals'])): ?>
                    <details style="margin-top:8px;">
                        <summary style="cursor:pointer;font-size:12px;color:#5b6478;"><?php echo esc_html(SEO_AEO_T::t('Vedi segnali rilevati')); ?></summary>
                        <ul style="margin:8px 0 0 18px;font-size:12px;" id="aeo-compat-signals">
                            <?php foreach ($aeo_profile['headless_signals'] as $sig): ?>
                                <li style="margin:2px 0;color:<?php echo !empty($sig['matched']) ? '#065f46' : '#64748b'; ?>;">
                                    <?php echo !empty($sig['matched']) ? '✓' : '×'; ?>
                                    <strong><?php echo esc_html($sig['name']); ?></strong>
                                    (<?php echo (int) $sig['weight']; ?>%):
                                    <?php echo esc_html($sig['note']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html(SEO_AEO_T::t('Ambiente effettivo')); ?></th>
                <td>
                    <strong id="aeo-compat-env"><?php echo esc_html($aeo_env_label); ?></strong>
                    <p class="description"><?php echo esc_html(SEO_AEO_T::t('Determina quali azioni AI verranno applicate automaticamente vs in modalita\' manuale.')); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html(SEO_AEO_T::t('Capacita\' per azione')); ?></th>
                <td>
                    <table class="aeo-compat-matrix" style="border-collapse:collapse;width:100%;max-width:520px;">
                        <thead>
                            <tr style="background:#f1f5f9;">
                                <th style="text-align:left;padding:6px 10px;border:1px solid #e2e8f0;font-size:12px;"><?php echo esc_html(SEO_AEO_T::t('Tipo di azione')); ?></th>
                                <th style="text-align:left;padding:6px 10px;border:1px solid #e2e8f0;font-size:12px;"><?php echo esc_html(SEO_AEO_T::t('Modalita\'')); ?></th>
                            </tr>
                        </thead>
                        <tbody id="aeo-compat-matrix-body">
                            <?php foreach ((isset($aeo_summary['rows']) ? $aeo_summary['rows'] : array()) as $row):
                                $mode = isset($row['mode']) ? $row['mode'] : 'manual';
                                $colors = array(
                                    'full'   => array('#065f46', '#ecfdf5'),
                                    'high'   => array('#1e40af', '#eff6ff'),
                                    'medium' => array('#92400e', '#fffbeb'),
                                    'low'    => array('#9a3412', '#fff7ed'),
                                    'manual' => array('#7f1d1d', '#fef2f2'),
                                );
                                $c = isset($colors[$mode]) ? $colors[$mode] : array('#475569', '#f8fafc');
                            ?>
                            <tr>
                                <td style="padding:6px 10px;border:1px solid #e2e8f0;font-size:13px;"><?php echo esc_html($row['label']); ?></td>
                                <td style="padding:6px 10px;border:1px solid #e2e8f0;font-size:13px;">
                                    <span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:<?php echo esc_attr($c[1]); ?>;color:<?php echo esc_attr($c[0]); ?>;">
                                        <?php echo esc_html($row['mode_label']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </td>
            </tr>
            <tr>
                <th scope="row">&nbsp;</th>
                <td>
                    <button type="button" class="button button-primary" id="aeo-compat-rescan">🔄 <?php echo esc_html(SEO_AEO_T::t('Re-scansiona sito')); ?></button>
                    <span id="aeo-compat-rescan-status" style="margin-left:8px;color:#64748b;font-size:12px;"></span>
                </td>
            </tr>
        </table>

        <script>
        jQuery(function ($) {
            $('#aeo-compat-rescan').on('click', function () {
                var $btn = $(this);
                var $status = $('#aeo-compat-rescan-status');
                $btn.prop('disabled', true);
                $status.text('<?php echo esc_js(SEO_AEO_T::t('Scansione in corso...')); ?>');
                $.post(ajaxurl, {
                    action: 'seo_aeo_orchestra_rescan_site',
                    nonce: (window.seoAeoOrchestra && window.seoAeoOrchestra.nonce) || ''
                }).done(function (resp) {
                    if (resp && resp.success && resp.data && resp.data.profile) {
                        var p = resp.data.profile;
                        $('#aeo-compat-builder').text(p.builder || 'unknown');
                        $('#aeo-compat-builder-conf').text('(' + (p.builder_confidence || 0) + '%)');
                        $('#aeo-compat-headless').text(p.is_headless ? ('<?php echo esc_js(SEO_AEO_T::t('Si')); ?> (' + (p.headless_mode || 'rest') + ')') : '<?php echo esc_js(SEO_AEO_T::t('No')); ?>');
                        $('#aeo-compat-headless-conf').text('(' + (p.headless_confidence || 0) + '%)');
                        $('#aeo-compat-env').text(resp.data.env_label || '');
                        $status.css('color', '#065f46').text('✓ <?php echo esc_js(SEO_AEO_T::t('Sito ri-scansionato.')); ?>');
                        setTimeout(function () { window.location.reload(); }, 800);
                    } else {
                        $status.css('color', '#991b1b').text('× <?php echo esc_js(SEO_AEO_T::t('Scansione fallita.')); ?>');
                    }
                }).fail(function (xhr) {
                    $status.css('color', '#991b1b').text('× HTTP ' + (xhr ? xhr.status : '?'));
                }).always(function () {
                    $btn.prop('disabled', false);
                });
            });
        });
        </script>

        <?php submit_button(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Salva Impostazioni') : 'Salva Impostazioni'); ?>
    </form>

    <!-- 3.31.5 — Layout articoli AI -->
    <div class="orchestra-tool-card" style="margin-top:24px;">
        <h2>
            <span class="dashicons dashicons-layout" style="color:#7C3AED"></span>
            <?php echo esc_html(SEO_AEO_T::t('🎨 Layout articoli AI')); ?>
        </h2>
        <p>
            <?php echo esc_html(SEO_AEO_T::t('Configura il layout che AEO Orchestra userà per pubblicare gli articoli generati. Una volta autorizzato, tutti i nuovi articoli AI rispetteranno questa configurazione.')); ?>
        </p>

        <div id="orch-layout-loading" style="padding:20px;text-align:center;color:#64748b;">
            <span class="spinner is-active" style="float:none;"></span>
            <?php echo esc_html(SEO_AEO_T::t('Caricamento configurazione...')); ?>
        </div>

        <div id="orch-layout-container" style="display:none;">
            <div id="orch-layout-authorized-banner" style="display:none;background:#ecfdf5;border:1px solid #10B981;border-radius:8px;padding:12px 16px;margin-bottom:16px;color:#065f46;">
                <strong><?php echo esc_html(SEO_AEO_T::t('✓ Layout autorizzato.')); ?></strong>
                <?php echo esc_html(SEO_AEO_T::t('I prossimi articoli AI useranno questa configurazione.')); ?>
            </div>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html(SEO_AEO_T::t('Tipo contenuto')); ?></th>
                    <td>
                        <select id="orch-layout-post-type"></select>
                        <p class="description"><?php echo esc_html(SEO_AEO_T::t('Tipo di post WordPress in cui verranno creati gli articoli AI.')); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html(SEO_AEO_T::t('Template')); ?></th>
                    <td>
                        <select id="orch-layout-template"></select>
                        <p class="description"><?php echo esc_html(SEO_AEO_T::t('Template del theme da usare per il rendering. Cambia se il tuo theme propone layout custom.')); ?></p>
                    </td>
                </tr>
                <tr id="orch-layout-category-row">
                    <th scope="row"><?php echo esc_html(SEO_AEO_T::t('Categoria')); ?></th>
                    <td>
                        <select id="orch-layout-category"></select>
                        <p class="description"><?php echo esc_html(SEO_AEO_T::t('Categoria di default. Visibile solo per il tipo Post.')); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html(SEO_AEO_T::t('Autore default')); ?></th>
                    <td>
                        <select id="orch-layout-author"></select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html(SEO_AEO_T::t('Stato pubblicazione')); ?></th>
                    <td>
                        <label style="margin-right:18px;">
                            <input type="radio" name="orch-layout-status" value="draft" checked>
                            <?php echo esc_html(SEO_AEO_T::t('Bozza (review manuale)')); ?>
                        </label>
                        <label>
                            <input type="radio" name="orch-layout-status" value="publish">
                            <?php echo esc_html(SEO_AEO_T::t('Pubblicato direttamente')); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <p style="margin-top:14px;">
                <button type="button" class="button" id="orch-layout-preview-btn">
                    <span class="dashicons dashicons-visibility" style="margin-top:3px;"></span>
                    <?php echo esc_html(SEO_AEO_T::t('👁 Anteprima con fake article')); ?>
                </button>
                <button type="button" class="button button-primary" id="orch-layout-save-btn" style="margin-left:8px;">
                    <span class="dashicons dashicons-saved" style="margin-top:3px;"></span>
                    <?php echo esc_html(SEO_AEO_T::t('💾 Salva configurazione layout')); ?>
                </button>
                <span id="orch-layout-save-status" style="margin-left:10px;color:#64748b;"></span>
            </p>

            <p id="orch-layout-builders" style="margin-top:14px;font-size:12px;color:#64748b;"></p>
        </div>
    </div>

    <?php ob_start(); ?>
jQuery(function($) {
        var ajaxUrl = (window.seoAeoOrchestra && window.seoAeoOrchestra.ajaxUrl) || ajaxurl;
        var nonce = (window.seoAeoOrchestra && window.seoAeoOrchestra.nonce) || '';
        var data = null;

        function escHtml(s) {
            return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
        }

        function populateTemplates(postType) {
            var $sel = $('#orch-layout-template');
            $sel.empty();
            var list = (data && data.templates && data.templates[postType]) ? data.templates[postType] : [{file:'default', name:'Default'}];
            list.forEach(function(t) {
                $sel.append('<option value="' + escHtml(t.file) + '">' + escHtml(t.name) + '</option>');
            });
        }

        function applySaved() {
            if (!data || !data.saved) return;
            var s = data.saved;
            $('#orch-layout-post-type').val(s.post_type || 'post');
            populateTemplates(s.post_type || 'post');
            $('#orch-layout-template').val(s.template || 'default');
            $('#orch-layout-category').val(s.category || 0);
            $('#orch-layout-author').val(s.author || data.current_user_id);
            $('input[name="orch-layout-status"][value="' + (s.status === 'publish' ? 'publish' : 'draft') + '"]').prop('checked', true);
            $('#orch-layout-category-row').toggle((s.post_type || 'post') === 'post');
            if (s.authorized) $('#orch-layout-authorized-banner').show();
        }

        function load() {
            $.post(ajaxUrl, { action: 'seo_aeo_orchestra_layout_discover', nonce: nonce }, function(resp) {
                if (!resp || !resp.success) {
                    $('#orch-layout-loading').html('<span style="color:#b91c1c;"><?php echo esc_js($seo_aeo_T('Errore caricamento layout.')); ?></span>');
                    return;
                }
                data = resp;
                // Post types
                var $pt = $('#orch-layout-post-type').empty();
                resp.post_types.forEach(function(p) {
                    $pt.append('<option value="' + escHtml(p.name) + '">' + escHtml(p.label) + '</option>');
                });
                // Categories
                var $cat = $('#orch-layout-category').empty();
                (resp.categories || []).forEach(function(c) {
                    $cat.append('<option value="' + escHtml(c.id) + '">' + escHtml(c.name) + '</option>');
                });
                // Authors
                var $au = $('#orch-layout-author').empty();
                (resp.authors || []).forEach(function(a) {
                    $au.append('<option value="' + escHtml(a.id) + '">' + escHtml(a.name) + '</option>');
                });
                // Page builders detected
                if (resp.page_builders && resp.page_builders.length) {
                    $('#orch-layout-builders').text('<?php echo esc_js($seo_aeo_T('Page builder rilevati')); ?>: ' + resp.page_builders.join(', '));
                }
                applySaved();
                $('#orch-layout-loading').hide();
                $('#orch-layout-container').show();
            }).fail(function() {
                $('#orch-layout-loading').html('<span style="color:#b91c1c;"><?php echo esc_js($seo_aeo_T('Errore di rete.')); ?></span>');
            });
        }

        $(document).on('change', '#orch-layout-post-type', function() {
            var pt = $(this).val();
            populateTemplates(pt);
            $('#orch-layout-category-row').toggle(pt === 'post');
        });

        $('#orch-layout-save-btn').on('click', function() {
            var $btn = $(this).prop('disabled', true);
            $('#orch-layout-save-status').text('').css('color','#64748b');
            $.post(ajaxUrl, {
                action: 'seo_aeo_orchestra_layout_save',
                nonce: nonce,
                post_type: $('#orch-layout-post-type').val(),
                template:  $('#orch-layout-template').val(),
                category:  $('#orch-layout-category').val(),
                author:    $('#orch-layout-author').val(),
                status:    $('input[name="orch-layout-status"]:checked').val()
            }, function(resp) {
                $btn.prop('disabled', false);
                if (resp && resp.success) {
                    $('#orch-layout-save-status').css('color','#059669').text('<?php echo esc_js($seo_aeo_T('✓ Salvato')); ?>');
                    $('#orch-layout-authorized-banner').show();
                } else {
                    $('#orch-layout-save-status').css('color','#b91c1c').text(resp && resp.error || 'Errore');
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                $('#orch-layout-save-status').css('color','#b91c1c').text('<?php echo esc_js($seo_aeo_T('Errore di rete.')); ?>');
            });
        });

        $('#orch-layout-preview-btn').on('click', function() {
            var $btn = $(this).prop('disabled', true);
            $('#orch-layout-save-status').text('<?php echo esc_js($seo_aeo_T('Generazione anteprima...')); ?>').css('color','#64748b');
            $.post(ajaxUrl, {
                action: 'seo_aeo_orchestra_layout_preview',
                nonce: nonce,
                post_type: $('#orch-layout-post-type').val(),
                template:  $('#orch-layout-template').val(),
                category:  $('#orch-layout-category').val(),
                author:    $('#orch-layout-author').val()
            }, function(resp) {
                $btn.prop('disabled', false);
                if (resp && resp.success && resp.preview_url) {
                    $('#orch-layout-save-status').css('color','#059669').text('<?php echo esc_js($seo_aeo_T('✓ Anteprima aperta in nuova tab')); ?>');
                    var w = window.open(resp.preview_url, '_blank');
                    if (!w) $('#orch-layout-save-status').css('color','#b91c1c').text('<?php echo esc_js($seo_aeo_T('Popup bloccato. Consenti i popup per questo sito.')); ?>');
                } else {
                    $('#orch-layout-save-status').css('color','#b91c1c').text(resp && resp.error || 'Errore');
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                $('#orch-layout-save-status').css('color','#b91c1c').text('<?php echo esc_js($seo_aeo_T('Errore di rete.')); ?>');
            });
        });

        load();
    });
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>

    <!-- 3.7.0 — Storico crediti (audit cliente) -->
    <div class="orchestra-tool-card" style="margin-top:24px;">
        <h2><span class="dashicons dashicons-list-view" style="color:#10B981"></span> <?php echo esc_html(SEO_AEO_T::t('Storico crediti')); ?></h2>
        <p><?php echo esc_html(SEO_AEO_T::t('Ultime 50 transazioni del tuo wallet (ricariche, consumi, bonus admin).')); ?></p>
        <div style="margin-bottom:12px;">
            <button type="button" class="button" id="orch-load-transactions"><?php echo esc_html(SEO_AEO_T::t('Carica storico')); ?></button>
            <span id="orch-tx-loading" style="margin-left:8px;color:#666;display:none;"><?php echo esc_html(SEO_AEO_T::t('Caricamento...')); ?></span>
        </div>
        <div id="orch-transactions-table" style="max-height:500px;overflow-y:auto;"></div>
    </div>
</div>

<?php ob_start(); ?>
jQuery(document).ready(function($) {
    $('#orch-load-transactions').on('click', function() {
        var $btn = $(this);
        var $loading = $('#orch-tx-loading');
        var $table = $('#orch-transactions-table');
        $btn.prop('disabled', true);
        $loading.show();
        $.post(seoAeoOrchestra.ajaxUrl, {
            action: 'seo_aeo_orchestra_get_transactions',
            nonce: seoAeoOrchestra.nonce
        }, function(resp) {
            $btn.prop('disabled', false);
            $loading.hide();
            if (!resp || resp.error) {
                $table.html('<div style="color:#b91c1c;padding:12px;background:#fef2f2;border-radius:6px;">' + ((resp && resp.error) || 'Errore caricamento') + '</div>');
                return;
            }
            var txs = resp.transactions || [];
            if (txs.length === 0) {
                $table.html('<div style="color:#64748b;padding:12px;background:#f8fafc;border-radius:6px;">Nessuna transazione registrata.</div>');
                return;
            }
            var labelMap = {
                'meta_generation': 'Generazione meta tags',
                'seo_analysis': 'Analisi SEO',
                'aeo_analysis': 'Analisi AEO',
                'aeo_content': 'Contenuti AEO',
                'local_seo': 'Local SEO',
                'content_generation_short': 'Contenuto breve',
                'content_generation_medium': 'Contenuto medio',
                'content_generation_long': 'Contenuto lungo',
                'image_generation': 'Generazione immagine',
                'complete_article': 'Articolo completo',
                'team_plan_backfill_2026-04-28': 'Backfill iniziale piano',
                'subscription_renewal': 'Rinnovo abbonamento mensile'
            };
            function formatLabel(s) {
                if (!s) return '—';
                if (labelMap[s]) return labelMap[s];
                if (s.indexOf('renewal_') === 0) return 'Rinnovo ' + s.substr(8);
                if (s.indexOf('admin_grant') === 0) return 'Bonus admin';
                return s.replace(/_/g, ' ');
            }
            function formatDate(ts) {
                try {
                    var d = new Date(ts);
                    var pad = function(n){return n<10?'0'+n:n;};
                    return pad(d.getDate())+'/'+pad(d.getMonth()+1)+'/'+d.getFullYear()+' '+pad(d.getHours())+':'+pad(d.getMinutes());
                } catch(e) { return ts || '—'; }
            }

            var html = '<table class="wp-list-table widefat fixed striped" style="margin-top:6px;">';
            html += '<thead><tr><th style="width:130px;">Data</th><th>Operazione</th><th style="width:80px;text-align:right;">Variazione</th><th style="width:90px;text-align:right;">Saldo</th></tr></thead><tbody>';
            txs.forEach(function(t) {
                var amount = t.amount || 0;
                var color = amount >= 0 ? '#059669' : '#b91c1c';
                var sign = amount >= 0 ? '+' : '';
                var src = t.source || t.operation || '';
                var typeLabel = (t.credit_type === 'plan' ? 'piano' : (t.credit_type === 'topup' ? 'top-up' : ''));
                var typeBadge = typeLabel ? ' <span style="font-size:10px;color:#64748b;background:#f1f5f9;padding:2px 6px;border-radius:4px;margin-left:6px;">' + typeLabel + '</span>' : '';
                html += '<tr>';
                html += '<td style="font-size:12px;color:#475569;">' + formatDate(t.timestamp) + '</td>';
                html += '<td>' + formatLabel(src) + typeBadge + '</td>';
                html += '<td style="text-align:right;font-weight:600;color:' + color + ';">' + sign + amount + '</td>';
                html += '<td style="text-align:right;font-family:monospace;font-size:12px;color:#475569;">' + (t.balance_after !== undefined ? t.balance_after : '—') + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            html += '<div style="margin-top:8px;color:#94a3b8;font-size:11px;text-align:right;">' + txs.length + ' transazioni mostrate (max 50)</div>';
            $table.html(html);
        }).fail(function(xhr) {
            $btn.prop('disabled', false);
            $loading.hide();
            $table.html('<div style="color:#b91c1c;padding:12px;background:#fef2f2;border-radius:6px;">Errore rete (HTTP ' + xhr.status + ')</div>');
        });
    });
});
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>

<?php ob_start(); ?>
jQuery(document).ready(function($) {
    $('#validate-license').on('click', function() {
        var licenseKey = $('#license_key').val();
        var $status = $('#license-status');
        if (!licenseKey) {
            $status.html('<span style="color:red;">Inserisci una license key.</span>');
            return;
        }
        $status.html('<span style="color:#666;">Verifica in corso...</span>');

        $.post(seoAeoOrchestra.ajaxUrl, {
            action: 'seo_aeo_orchestra_validate_license',
            nonce: seoAeoOrchestra.nonce,
            license_key: licenseKey
        }, function(response) {
            if (!response || typeof response !== 'object') {
                $status.html('<span style="color:red;">Risposta non valida dal server.</span>');
                return;
            }
            if (response.valid) {
                // 3.5.2 — Mostra crediti wallet (allineato con plugin hero), api_calls solo come info secondaria
                var creditsLine = '';
                if (typeof response.credits_remaining !== 'undefined') {
                    var plan = response.plan_credits || 0;
                    var topup = response.topup_credits || 0;
                    creditsLine = ' | Crediti: <strong>' + response.credits_remaining + '</strong>'
                                + ' <span style="font-weight:normal;color:#666;">(Piano: ' + plan + ' · Top-up: ' + topup + ')</span>';
                } else {
                    // fallback su versioni backend pre-3.5.2
                    creditsLine = ' | Crediti: ' + (response.api_calls_remaining || 0);
                }
                $status.html('<span style="color:green;font-weight:bold;">&#10003; Licenza valida! Piano: ' + (response.package || 'N/D') + creditsLine + '</span>');
            } else {
                var msg = response.message || 'Licenza non valida';
                $status.html('<span style="color:red;">&#10007; ' + msg + '</span>');
                if (response.debug && response.debug.error) {
                    $status.append('<br><small style="color:#666;">Errore: ' + response.debug.error + '</small>');
                    if (response.debug.tip) $status.append('<br><small style="color:#666;">Suggerimento: ' + response.debug.tip + '</small>');
                    if (response.debug.api_url) $status.append('<br><small style="color:#666;">API URL: ' + response.debug.api_url + '</small>');
                }
            }
        }).fail(function(xhr) {
            $status.html('<span style="color:red;">Errore AJAX (HTTP ' + xhr.status + '). La pagina potrebbe aver bisogno di un refresh.</span>');
        });
    });

    $('#test-connection').on('click', function() {
        var $status = $('#connection-status');
        $status.html('<span style="color:#666;">Test connessione in corso...</span>');

        $.post(seoAeoOrchestra.ajaxUrl, {
            action: 'seo_aeo_orchestra_test_connection',
            nonce: seoAeoOrchestra.nonce
        }, function(response) {
            if (!response || typeof response !== 'object') {
                $status.html('<span style="color:red;">Risposta non valida. Ricarica la pagina.</span>');
                return;
            }
            if (response.connected) {
                $status.html('<span style="color:green;font-weight:bold;">&#10003; Connessione OK! Server: ' + (response.api_url || '?') + '</span>');
            } else {
                var errMsg = response.error || ('HTTP ' + (response.status_code || '?'));
                $status.html('<span style="color:red;">&#10007; Server non raggiungibile: ' + (response.api_url || '?') + '<br>Errore: ' + errMsg + '</span>');
                if (response.tip) $status.append('<br><small style="color:#666;">' + response.tip + '</small>');
            }
        }).fail(function(xhr) {
            $status.html('<span style="color:red;">Errore AJAX (HTTP ' + xhr.status + '). Ricarica la pagina e riprova.</span>');
        });
    });
});
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>

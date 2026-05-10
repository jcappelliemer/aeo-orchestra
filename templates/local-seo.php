<?php if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.VariableNotPrefixed
// Reason: template scope. Variables are local to this include/template,
// passed by the calling function via include/require. The Plugin Check
// heuristic doesn't distinguish template-scope locals from globals.
$license_key = get_option('seo_aeo_orchestra_license_key', '');
$license_valid = !empty($license_key);
$T = function($s) { return class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t($s)) : esc_html($s); };
?>
<div class="wrap orchestra-admin">
    <h1 style="display:none;"><?php echo esc_html(SEO_AEO_T::t('Local SEO')); ?></h1>

    <div class="orchestra-header">
        <h2><?php SEO_AEO_T::e('Local SEO'); ?></h2>
        <p class="description"><?php SEO_AEO_T::e('Genera pagine ottimizzate per la ricerca locale e posizionati nelle citta target.'); ?></p>
    </div>

    <?php
    $seo_aeo_kp = array(
        'mode'    => 'single',
        'targets' => array('#local-service' => 'keyword'),
        'label'   => 'Pesca servizio da Keyword Research',
    );
    $seo_aeo_kp_partial = dirname(__DIR__) . '/templates/partials/keyword-picker.php';
    if (file_exists($seo_aeo_kp_partial)) include $seo_aeo_kp_partial;
    ?>

    <?php if (!$license_valid): ?>
    <div class="orchestra-notice warning">
        <p><strong><?php echo esc_html(SEO_AEO_T::t('Licenza non attiva.')); ?></strong> <?php echo esc_html(SEO_AEO_T::t('Attiva la licenza nelle')); ?> <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-settings')); ?>"><?php echo esc_html(SEO_AEO_T::t('Impostazioni')); ?></a>.</p>
    </div>
    <?php endif; ?>

    <div class="orchestra-tool-card">
        <h2><span class="dashicons dashicons-location" style="color:#EF4444"></span> <?php echo esc_html(SEO_AEO_T::t('Genera pagine ottimizzate per citta')); ?></h2>
        <p><?php echo esc_html(SEO_AEO_T::t('Crea pagine local SEO per i tuoi servizi in diverse citta.')); ?></p>

        <table class="form-table">
            <tr>
                <th><?php echo esc_html(SEO_AEO_T::t('Servizio')); ?></th>
                <td><input type="text" id="local-service" class="regular-text" placeholder="<?php echo esc_attr(SEO_AEO_T::t('es. Installazione caldaie')); ?>" /></td>
            </tr>
            <tr>
                <th><?php echo esc_html(SEO_AEO_T::t('Citta')); ?></th>
                <td>
                    <input type="text" id="local-city" class="regular-text" placeholder="<?php echo esc_attr(SEO_AEO_T::t('es. Milano')); ?>" />
                    <p class="description"><?php echo esc_html(SEO_AEO_T::t('Per piu citta, separale con virgola')); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php echo esc_html(SEO_AEO_T::t('Template')); ?></th>
                <td>
                    <select id="local-template">
                        <option value="service"><?php echo esc_html(SEO_AEO_T::t('Pagina Servizio')); ?></option>
                        <option value="landing"><?php echo esc_html(SEO_AEO_T::t('Landing Page')); ?></option>
                        <option value="article"><?php echo esc_html(SEO_AEO_T::t('Articolo Informativo')); ?></option>
                    </select>
                </td>
            </tr>
        </table>

        <p>
            <button type="button" class="button button-primary button-hero" id="seo-aeo-local-seo-btn" <?php if (!$license_valid) echo 'disabled'; ?>>
                <span class="dashicons dashicons-location"></span> <?php echo esc_html(SEO_AEO_T::t('Genera Pagina Local')); ?> - <span class="credit-cost" data-cost-key="local_seo">4</span> <?php echo esc_html(SEO_AEO_T::t('crediti')); ?>
            </button>
        </p>
    </div>

    <div id="local-output" class="orchestra-output"></div>
    <div id="history-local-seo" class="orchestra-history-container"></div>

    <hr>

    <!-- Local Keyword Suggestions -->
    <div class="orchestra-tool-card" id="local-keyword-suggestions">
        <h2><span class="dashicons dashicons-lightbulb" style="color:#EF4444"></span> <?php echo esc_html(SEO_AEO_T::t('Suggerimenti Local SEO')); ?></h2>
        <p><?php echo esc_html(SEO_AEO_T::t('Inserisci il tuo settore e la zona per scoprire le keyword locali piu efficaci.')); ?></p>

        <div style="display:flex;gap:10px;align-items:flex-end;margin-bottom:15px;">
            <div style="flex:1;">
                <label style="font-weight:600;display:block;margin-bottom:5px;"><?php echo esc_html(SEO_AEO_T::t('Settore + Zona')); ?></label>
                <input type="text" id="suggest-local-context" class="large-text" placeholder="<?php echo esc_attr(SEO_AEO_T::t('es. Idraulico a Roma, servizi caldaie Lombardia')); ?>" />
            </div>
            <button type="button" class="button button-primary" id="local-suggest-keywords-btn"
                onclick="if(window.SeoAeoOrchestra)SeoAeoOrchestra.suggestKeywords('local_seo','suggest-local-context','local-suggestions-list');" <?php if (!$license_valid) echo 'disabled'; ?>>
                <span class="dashicons dashicons-lightbulb"></span> <?php echo esc_html(SEO_AEO_T::t('Suggerisci')); ?>
            </button>
        </div>

        <div id="local-suggestions-list"></div>
    </div>
</div>

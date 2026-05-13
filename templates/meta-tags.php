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
    <h1 style="display:none;"><?php echo esc_html(SEO_AEO_T::t('Generatore Meta Tags')); ?></h1>

    <div class="orchestra-header">
        <h2><?php SEO_AEO_T::e('Generatore Meta Tags'); ?></h2>
        <p class="description"><?php SEO_AEO_T::e('Genera title, description e keywords ottimizzati per ogni pagina del tuo sito.'); ?></p>
    </div>

    <?php
    $seo_aeo_kp = array(
        'mode'    => 'single',
        'targets' => array('#meta-keyword' => 'keyword'),
        'label'   => 'Pesca keyword da Research',
    );
    $seo_aeo_kp_partial = dirname(__DIR__) . '/templates/partials/keyword-picker.php';
    if (file_exists($seo_aeo_kp_partial)) include $seo_aeo_kp_partial;
    ?>

    <div class="orchestra-tool-card">
        <h2><span class="dashicons dashicons-admin-generic" style="color:#10B981"></span> <?php echo esc_html(SEO_AEO_T::t('Genera meta tags per le tue pagine')); ?></h2>
        <p><?php echo esc_html(SEO_AEO_T::t('Seleziona una pagina e genera automaticamente title, description e keywords ottimizzati.')); ?></p>

        <table class="form-table">
            <tr>
                <th><?php echo esc_html(SEO_AEO_T::t('Seleziona Post/Pagina')); ?></th>
                <td>
                    <?php $posts = get_posts(array('numberposts' => -1, 'post_type' => array('post', 'page'), 'post_status' => 'publish')); ?>
                    <select id="meta-post-select" class="regular-text">
                        <option value="">-- <?php echo esc_html(SEO_AEO_T::t('Seleziona')); ?> --</option>
                        <?php foreach ($posts as $post): ?>
                        <option value="<?php echo absint($post->ID); ?>"><?php echo esc_html($post->post_title); ?> (<?php echo esc_attr($post->post_type); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php echo esc_html(SEO_AEO_T::t('Keyword Focus')); ?></th>
                <td><input type="text" id="meta-keyword" class="regular-text" placeholder="<?php echo esc_attr(SEO_AEO_T::t('keyword principale')); ?>" /></td>
            </tr>
        </table>

        <p>
            <button type="button" class="button button-primary button-hero" id="seo-aeo-meta-generate-btn" <?php if (!$license_valid) echo 'disabled'; ?>>
                <span class="dashicons dashicons-admin-generic"></span> <?php echo esc_html(SEO_AEO_T::t('Genera Meta Tags')); ?> - <span class="credit-cost" data-cost-key="meta_generation">2</span> <?php echo esc_html(SEO_AEO_T::t('crediti')); ?>
            </button>
        </p>
    </div>

    <hr>

    <div class="orchestra-tool-card">
        <h2><span class="dashicons dashicons-admin-page" style="color:#0055FF"></span> <?php echo esc_html(SEO_AEO_T::t('Generazione di massa')); ?></h2>
        <p><?php echo esc_html(SEO_AEO_T::t('Genera meta tags per piu pagine contemporaneamente.')); ?></p>
        <div id="bulk-meta-list">
            <?php foreach ($posts as $post):
                $has_meta = get_post_meta($post->ID, '_seo_aeo_meta_title', true);
            ?>
            <div class="bulk-item">
                <label>
                    <input type="checkbox" name="bulk_posts[]" value="<?php echo absint($post->ID); ?>" />
                    <?php echo esc_html($post->post_title); ?>
                    <?php if ($has_meta): ?>
                    <span class="dashicons dashicons-yes" style="color: green;" title="<?php echo esc_html(SEO_AEO_T::t('Meta presente')); ?>"></span>
                    <?php endif; ?>
                </label>
            </div>
            <?php endforeach; ?>
        </div>
        <p>
            <button type="button" class="button button-secondary" id="select-all-bulk"><?php echo esc_html(SEO_AEO_T::t('Seleziona Tutti')); ?></button>
            <button type="button" class="button button-primary" id="seo-aeo-bulk-meta-btn" <?php if (!$license_valid) echo 'disabled'; ?>><?php echo esc_html(SEO_AEO_T::t('Genera per Selezionati')); ?></button>
        </p>
    </div>

    <div id="meta-output" class="orchestra-output"></div>

    <?php ob_start(); ?>
/* 3.31.2: auto-fill da URL/keyword query params (Quick Win, tile Esegui, ecc.) */
    jQuery(function($) {
        try {
            var p = new URLSearchParams(window.location.search);
            var qUrl = p.get('url');
            var qKw = p.get('keyword');
            if (qKw) $('#meta-keyword').val(qKw);
            if (qUrl) {
                // Cerco l'option che ha questo permalink (corrisponde post_id)
                var matched = false;
                $('#meta-post-select option').each(function() {
                    var v = $(this).val();
                    if (!v) return;
                    // confronto via permalink: serve un'AJAX, ma più facile testare per coincidenza title
                    if ($(this).text() && qUrl.indexOf(encodeURIComponent($(this).text().split(' (')[0])) !== -1) {
                        $('#meta-post-select').val(v);
                        matched = true;
                        return false;
                    }
                });
                if (!matched) {
                    // Provo via AJAX get_pages per matchare URL → post_id
                    if (typeof seoAeoOrchestra !== 'undefined') {
                        $.post(seoAeoOrchestra.ajaxUrl, {
                            action: 'seo_aeo_orchestra_get_pages',
                            nonce: seoAeoOrchestra.nonce
                        }, function(pages) {
                            if (!pages || !pages.length) return;
                            var match = pages.find(function(pg) { return pg.url === qUrl; });
                            if (match && match.id) $('#meta-post-select').val(match.id);
                        });
                    }
                }
            }
        } catch (e) { /* IE11 silent */ }
    });
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>

    <!-- SERP Preview -->
    <div id="serp-preview-section" style="display:none;">
        <div class="orchestra-tool-card">
            <h2><span class="dashicons dashicons-google" style="color:#4285F4"></span> <?php echo esc_html(SEO_AEO_T::t('Anteprima Google SERP')); ?></h2>
            <p style="color:#666;font-size:13px;margin-bottom:16px;"><?php echo esc_html(SEO_AEO_T::t('Ecco come apparira il tuo risultato su Google. Modifica i campi per aggiornare in tempo reale.')); ?></p>
            <div style="font-family:Arial,sans-serif;max-width:600px;padding:20px;background:#fff;border-radius:10px;border:1px solid #e0e0e0;">
                <div style="margin-bottom:4px;">
                    <span id="serp-url" style="font-size:14px;color:#202124;line-height:1.3;">
                        <span id="serp-favicon" style="display:inline-block;width:26px;height:26px;border-radius:50%;background:#f1f3f4;vertical-align:middle;text-align:center;line-height:26px;font-size:12px;margin-right:8px;color:#5f6368;">S</span>
                        <span id="serp-domain" style="font-size:12px;color:#4d5156;">example.com</span>
                    </span>
                </div>
                <div style="margin-bottom:2px;">
                    <input type="text" id="serp-title-input" style="width:100%;border:none;outline:none;font-size:20px;color:#1a0dab;line-height:1.3;padding:0;font-family:Arial,sans-serif;cursor:text;background:transparent;" placeholder="Titolo della pagina" />
                </div>
                <div>
                    <textarea id="serp-desc-input" rows="2" style="width:100%;border:none;outline:none;font-size:14px;color:#4d5156;line-height:1.58;padding:0;font-family:Arial,sans-serif;resize:none;background:transparent;" placeholder="Descrizione della pagina..."></textarea>
                </div>
                <div style="margin-top:8px;display:flex;gap:16px;font-size:11px;">
                    <span id="serp-title-chars" style="color:#10B981;">Titolo: 0/60 caratteri</span>
                    <span id="serp-desc-chars" style="color:#10B981;">Descrizione: 0/160 caratteri</span>
                </div>
            </div>
        </div>
    </div>
</div>

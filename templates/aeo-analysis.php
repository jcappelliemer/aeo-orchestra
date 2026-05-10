<?php error_reporting(0); if (!defined('ABSPATH')) exit;
$license_key = get_option('seo_aeo_orchestra_license_key', '');
$license_valid = !empty($license_key);
$T = function($s) { return class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t($s)) : esc_html($s); };
?>
<div class="wrap orchestra-admin">
    <h1 style="display:none;"><?php echo esc_html(SEO_AEO_T::t('Analisi AEO')); ?></h1>

    <div class="orchestra-header aeo-header">
        <h2><?php echo esc_html(SEO_AEO_T::t('Analisi AEO')); ?></h2>
        <p class="description"><?php echo esc_html(SEO_AEO_T::t('Verifica l\'ottimizzazione per Google AI Overviews, ChatGPT, Perplexity e Bing Copilot')); ?></p>
    </div>

    <?php if (!$license_valid): ?>
    <div class="orchestra-notice warning">
        <p><strong><?php echo esc_html(SEO_AEO_T::t('Licenza non attiva.')); ?></strong> <?php echo esc_html(SEO_AEO_T::t('Attiva la licenza nelle')); ?> <a href="<?php echo admin_url('admin.php?page=seo-aeo-settings'); ?>"><?php echo esc_html(SEO_AEO_T::t('Impostazioni')); ?></a>.</p>
    </div>
    <?php endif; ?>

    <div class="orchestra-tool-card">
        <h2><span class="dashicons dashicons-visibility" style="color:#8B5CF6"></span> <?php echo esc_html(SEO_AEO_T::t('Analizza visibilita AI delle tue pagine')); ?></h2>
        <p><?php echo esc_html(SEO_AEO_T::t('Seleziona una pagina del sito per scoprire come viene percepita dai motori AI.')); ?></p>

        <div style="margin-bottom:15px;">
            <label style="font-weight:600;display:block;margin-bottom:5px;"><?php echo esc_html(SEO_AEO_T::t('Pagina del sito')); ?></label>
            <select id="aeo-page-select" class="regular-text" style="width:100%;max-width:600px;">
                <option value="">-- <?php echo esc_html(SEO_AEO_T::t('Seleziona una pagina')); ?> --</option>
                <option value="__custom"><?php echo esc_html(SEO_AEO_T::t('Inserisci URL manualmente...')); ?></option>
            </select>
            <p class="description"><?php echo esc_html(SEO_AEO_T::t('Caricamento pagine...')); ?></p>
        </div>

        <div id="aeo-manual-url" style="display:none;margin-bottom:15px;">
            <label style="font-weight:600;display:block;margin-bottom:5px;"><?php echo esc_html(SEO_AEO_T::t('URL da analizzare')); ?></label>
            <input type="url" id="aeo-url" class="regular-text" style="width:100%;max-width:600px;" placeholder="https://tuosito.it/pagina" />
        </div>

        <div style="margin-bottom:15px;">
            <label style="font-weight:600;display:block;margin-bottom:5px;"><?php echo esc_html(SEO_AEO_T::t('Keyword target')); ?></label>
            <input type="text" id="aeo-keyword" class="regular-text" style="width:100%;max-width:600px;" placeholder="<?php echo esc_attr(SEO_AEO_T::t('es: consulente seo roma')); ?>" />
        </div>

        <p>
            <button type="button" class="button button-primary button-hero button-aeo" id="seo-aeo-aeo-analyze-btn" <?php if (!$license_valid) echo 'disabled'; ?>>
                <span class="dashicons dashicons-visibility"></span> <?php echo esc_html(SEO_AEO_T::t('Analizza AEO')); ?> - <span class="credit-cost" data-cost-key="aeo_analysis">5</span> <?php echo esc_html(SEO_AEO_T::t('crediti')); ?>
            </button>
        </p>
    </div>

    <div id="aeo-analysis-output" class="orchestra-output"></div>
    <div id="history-aeo-analysis" class="orchestra-history-container"></div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    /* Load site pages for AEO analysis */
    if (typeof seoAeoOrchestra !== 'undefined') {
        $.post(seoAeoOrchestra.ajaxUrl, {
            action: 'seo_aeo_orchestra_get_pages',
            nonce: seoAeoOrchestra.nonce
        }, function(pages) {
            var $sel = $('#aeo-page-select');
            $sel.find('option:not(:first):not([value="__custom"])').remove();
            if (pages && pages.length) {
                $.each(pages, function(i, p) {
                    var icon = p.type === 'homepage' ? '(home) ' : (p.type === 'page' ? '(page) ' : '(post) ');
                    $sel.find('option[value="__custom"]').before(
                        '<option value="' + p.url + '" data-title="' + p.title + '">' + icon + p.title + ' (' + p.type + ')</option>'
                    );
                });
                $sel.next('.description').text(pages.length + ' pagine trovate');
            }
        });
    }

    $('#aeo-page-select').on('change', function() {
        var val = $(this).val();
        if (val === '__custom') {
            $('#aeo-manual-url').show();
            $('#aeo-url').val('');
        } else {
            $('#aeo-manual-url').hide();
            $('#aeo-url').val(val);
            var title = $(this).find(':selected').data('title');
            if (title && !$('#aeo-keyword').val()) {
                var words = title.toLowerCase().split(/\s+/).filter(function(w) { return w.length > 3; });
                $('#aeo-keyword').val(words.slice(0, 3).join(' '));
            }
        }
    });

    /* 3.31.2: auto-fill da URL/keyword query params */
    try {
        var p = new URLSearchParams(window.location.search);
        var qUrl = p.get('url');
        var qKw = p.get('keyword');
        if (qUrl) {
            $('#aeo-manual-url').show();
            $('#aeo-url').val(qUrl);
            var attempts = 0;
            var matchInterval = setInterval(function() {
                attempts++;
                var matched = false;
                $('#aeo-page-select option').each(function() {
                    if ($(this).val() === qUrl) {
                        $('#aeo-page-select').val(qUrl);
                        matched = true;
                        return false;
                    }
                });
                if (matched || attempts > 20) {
                    if (!matched) $('#aeo-page-select').val('__custom');
                    clearInterval(matchInterval);
                }
            }, 300);
        }
        if (qKw) $('#aeo-keyword').val(qKw);
    } catch (e) {}
});
</script>

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
    <h1 style="display:none;"><?php echo esc_html(SEO_AEO_T::t('Analisi SEO')); ?></h1>

    <div class="orchestra-header">
        <h2><?php SEO_AEO_T::e('Analisi SEO'); ?></h2>
        <p class="description"><?php SEO_AEO_T::e('Analizza il posizionamento delle tue pagine, scopri le keyword e ottieni suggerimenti di miglioramento.'); ?></p>
    </div>

    <?php
    $seo_aeo_kp = array(
        'mode'    => 'single',
        'targets' => array('#analyze-keyword' => 'keyword'),
        'label'   => 'Pesca keyword da Research',
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
        <h2><span class="dashicons dashicons-search" style="color:#0055FF"></span> <?php echo esc_html(SEO_AEO_T::t('Seleziona pagina o inserisci URL')); ?></h2>
        <p><?php echo esc_html(SEO_AEO_T::t('Scegli una pagina del tuo sito oppure inserisci un URL manualmente.')); ?></p>

        <div style="margin-bottom:15px;">
            <label style="font-weight:600;display:block;margin-bottom:5px;"><?php echo esc_html(SEO_AEO_T::t('Pagina del sito')); ?></label>
            <select id="analyze-page-select" class="regular-text" style="width:100%;max-width:600px;">
                <option value="">-- <?php echo esc_html(SEO_AEO_T::t('Seleziona una pagina')); ?> --</option>
                <option value="__custom"><?php echo esc_html(SEO_AEO_T::t('Inserisci URL manualmente...')); ?></option>
            </select>
            <p class="description"><?php echo esc_html(SEO_AEO_T::t('Caricamento pagine...')); ?></p>
        </div>

        <div id="analyze-manual-url" style="display:none;margin-bottom:15px;">
            <label style="font-weight:600;display:block;margin-bottom:5px;"><?php echo esc_html(SEO_AEO_T::t('URL da analizzare')); ?></label>
            <input type="url" id="analyze-url" class="regular-text" style="width:100%;max-width:600px;" placeholder="https://tuosito.it/pagina" />
        </div>

        <div style="margin-bottom:15px;">
            <label style="font-weight:600;display:block;margin-bottom:5px;"><?php echo esc_html(SEO_AEO_T::t('Keyword principale')); ?></label>
            <input type="text" id="analyze-keyword" class="regular-text" style="width:100%;max-width:600px;" placeholder="<?php echo esc_attr(SEO_AEO_T::t('parola chiave')); ?>" />
        </div>

        <p>
            <button type="button" class="button button-primary button-hero" id="seo-aeo-analyze-btn" <?php if (!$license_valid) echo 'disabled'; ?>>
                <span class="dashicons dashicons-search"></span> <?php echo esc_html(SEO_AEO_T::t('Analizza SEO')); ?> - <span class="credit-cost" data-cost-key="seo_analysis">5</span> <?php echo esc_html(SEO_AEO_T::t('crediti')); ?>
            </button>
        </p>
    </div>

    <div id="analysis-output" class="orchestra-output"></div>
    <div id="history-seo-analysis" class="orchestra-history-container"></div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    /* Load site pages */
    if (typeof seoAeoOrchestra !== 'undefined') {
        $.post(seoAeoOrchestra.ajaxUrl, {
            action: 'seo_aeo_orchestra_get_pages',
            nonce: seoAeoOrchestra.nonce
        }, function(pages) {
            var $sel = $('#analyze-page-select');
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

    $('#analyze-page-select').on('change', function() {
        var val = $(this).val();
        if (val === '__custom') {
            $('#analyze-manual-url').show();
            $('#analyze-url').val('');
        } else {
            $('#analyze-manual-url').hide();
            $('#analyze-url').val(val);
            /* Auto-fill keyword from page title */
            var title = $(this).find(':selected').data('title');
            if (title && !$('#analyze-keyword').val()) {
                var words = title.toLowerCase().split(/\s+/).filter(function(w) { return w.length > 3; });
                $('#analyze-keyword').val(words.slice(0, 3).join(' '));
            }
        }
    });

    /* 3.31.2: auto-fill URL/keyword da query params (Quick Win, Riesegui da cronologia, ecc.) */
    try {
        var params = new URLSearchParams(window.location.search);
        var qUrl = params.get('url');
        var qKw = params.get('keyword');
        if (qUrl) {
            // Mostra il blocco URL manuale + popola
            $('#analyze-manual-url').show();
            $('#analyze-url').val(qUrl);
            // Setta select sul valore custom così l'utente vede coerenza
            var $sel = $('#analyze-page-select');
            // Quando le pagine arrivano via AJAX, prova a matchare; altrimenti usa __custom
            var attempts = 0;
            var matchInterval = setInterval(function() {
                attempts++;
                var matched = false;
                $sel.find('option').each(function() {
                    if ($(this).val() === qUrl) {
                        $sel.val(qUrl);
                        matched = true;
                        return false;
                    }
                });
                if (matched || attempts > 20) {
                    if (!matched) $sel.val('__custom');
                    clearInterval(matchInterval);
                }
            }, 300);
        }
        if (qKw) {
            $('#analyze-keyword').val(qKw);
        }
        if (qUrl || qKw) {
            // Notifica visiva che i campi sono stati pre-popolati
            setTimeout(function() {
                var $btn = $('#seo-aeo-analyze-btn');
                if ($btn.length) {
                    $btn.css('animation', 'orch-pulse 1.4s ease-in-out 2');
                }
            }, 400);
        }
    } catch (e) { /* URLSearchParams non supportato (IE11): silent fail */ }
});
</script>
<?php ob_start(); ?>
@keyframes orch-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(0,85,255,0.45); }
    50% { box-shadow: 0 0 0 8px rgba(0,85,255,0); }
}
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

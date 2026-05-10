<?php if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.VariableNotPrefixed
// Reason: template scope. Variables are local to this include/template,
// passed by the calling function via include/require. The Plugin Check
// heuristic doesn't distinguish template-scope locals from globals.
$license_key = get_option('seo_aeo_orchestra_license_key', '');
$license_valid = !empty($license_key);
// Prefill da Keyword Research (3.23.0): query string ?prefill_topic=...&prefill_keyword=...
$prefill_topic   = isset($_GET['prefill_topic'])   ? sanitize_text_field(wp_unslash($_GET['prefill_topic']))   : '';
$prefill_keyword = isset($_GET['prefill_keyword']) ? sanitize_text_field(wp_unslash($_GET['prefill_keyword'])) : '';
// 3.31.2: prefill anche da ?url=...&keyword=... (Quick Win, tile Esegui, action plan rerun)
if (empty($prefill_keyword) && isset($_GET['keyword'])) {
    $prefill_keyword = sanitize_text_field(wp_unslash($_GET['keyword']));
}
if (empty($prefill_topic) && isset($_GET['url'])) {
    $url_param = sanitize_text_field(wp_unslash($_GET['url']));
    // Estrai slug come topic readable
    $slug = trim(wp_parse_url($url_param, PHP_URL_PATH), '/');
    $slug = $slug ? basename($slug) : '';
    $slug = preg_replace('/\.\w+$/', '', $slug);
    $slug = str_replace(array('-', '_'), ' ', $slug);
    if ($slug) $prefill_topic = $slug;
}
if (empty($prefill_topic) && !empty($prefill_keyword)) {
    $prefill_topic = $prefill_keyword;
}
?>
<div class="wrap orchestra-admin">
    <h1 style="display:none;"><?php SEO_AEO_T::e('Generatore Contenuti AI'); ?></h1>

    <div class="orchestra-header">
        <h2><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Generatore Contenuti SEO')) : 'Generatore Contenuti SEO'; ?></h2>
        <p class="description"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Crea articoli ottimizzati e scopri le keyword piu efficaci per il tuo sito.')) : 'Crea articoli ottimizzati e scopri le keyword piu efficaci per il tuo sito.'; ?></p>
    </div>

    <?php
    $seo_aeo_qe_partial = dirname(__DIR__) . '/templates/partials/brand-voice-quick-edit.php';
    if (file_exists($seo_aeo_qe_partial)) include $seo_aeo_qe_partial;

    // Keyword picker (3.23.2): inietta keyword da set salvati nel form
    $seo_aeo_kp = array(
        'mode'    => 'csv',
        'targets' => array(
            '#content-keywords' => 'keyword',
            '#content-topic'    => 'topic',
        ),
        'label'   => 'Pesca da Keyword Research',
    );
    $seo_aeo_kp_partial = dirname(__DIR__) . '/templates/partials/keyword-picker.php';
    if (file_exists($seo_aeo_kp_partial)) include $seo_aeo_kp_partial;
    ?>

    <div class="orchestra-tool-card">
        <h2><span class="dashicons dashicons-edit-large" style="color:#F59E0B"></span> <?php SEO_AEO_T::e('Crea contenuti SEO-ottimizzati'); ?></h2>
        <p><?php SEO_AEO_T::e('Genera articoli completi con sezioni H2, FAQ e contenuti strutturati per la SEO.'); ?></p>

        <table class="form-table">
            <tr>
                <th><?php SEO_AEO_T::e('Argomento'); ?></th>
                <td><input type="text" id="content-topic" class="large-text" placeholder="<?php echo esc_attr(SEO_AEO_T::t('es. Installazione pannelli solari residenziali')); ?>" value="<?php echo esc_attr($prefill_topic); ?>" /></td>
            </tr>
            <tr>
                <th><?php SEO_AEO_T::e('Keywords'); ?></th>
                <td>
                    <input type="text" id="content-keywords" class="large-text" placeholder="<?php echo esc_attr(SEO_AEO_T::t('keyword1, keyword2, keyword3')); ?>" value="<?php echo esc_attr($prefill_keyword); ?>" />
                    <p class="description"><?php SEO_AEO_T::e('Separa le keywords con virgola'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php SEO_AEO_T::e('Lunghezza'); ?></th>
                <td>
                    <select id="content-length" onchange="if(window.SeoAeoOrchestra)SeoAeoOrchestra.updateContentCostLabel();">
                        <option value="short"><?php SEO_AEO_T::e('Breve (~500 parole)'); ?></option>
                        <option value="medium" selected><?php SEO_AEO_T::e('Media (~1000 parole)'); ?></option>
                        <option value="long"><?php SEO_AEO_T::e('Lunga (~2000 parole)'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php SEO_AEO_T::e('Includi'); ?></th>
                <td>
                    <label><input type="checkbox" id="include-faq" checked /> <?php SEO_AEO_T::e('Sezione FAQ'); ?></label><br>
                    <label><input type="checkbox" id="include-cta" checked /> <?php SEO_AEO_T::e('Call to Action'); ?></label><br>
                    <label><input type="checkbox" id="include-summary" checked /> <?php SEO_AEO_T::e('Sommario'); ?></label>
                </td>
            </tr>
        </table>

        <p style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <?php
            // 3.31.0: bottone primario "Articolo Completo AI" (testo + immagine + meta)
            // affiancato al "Genera solo testo" classico. Riusa la funzione globale
            // SeoAeoOrchestra.generateCompleteArticle che era prima disponibile solo
            // come azione post-generazione.
            $is_premium = false;
            if (function_exists('get_option')) {
                $tier_opt = get_option('seo_aeo_orchestra_license_type', '');
                $is_premium = in_array(strtolower((string) $tier_opt), array('professional', 'team', 'subscription_pro', 'b2b_custom'), true);
            }
            ?>
            <?php if ($is_premium): ?>
            <button type="button" class="button button-primary button-hero" id="seo-aeo-complete-article-btn"
                    style="background:linear-gradient(135deg,#7c3aed,#a855f7);border-color:#7c3aed;color:#fff;box-shadow:0 4px 14px rgba(124,58,237,0.3);"
                    <?php if (!$license_valid) echo 'disabled'; ?>>
                <span class="dashicons dashicons-star-filled"></span>
                🌟 <?php SEO_AEO_T::e('Articolo Completo AI'); ?>
                <small style="opacity:0.9;font-weight:400;margin-left:6px;">(<?php SEO_AEO_T::e('testo + immagine + meta'); ?>)</small>
                — <span class="credit-cost" data-cost-key="complete_article">25</span> <?php SEO_AEO_T::e('crediti'); ?>
            </button>
            <?php endif; ?>

            <button type="button"
                    class="button <?php echo $is_premium ? 'button-secondary' : 'button-primary button-hero'; ?>"
                    id="seo-aeo-generate-content-btn" <?php if (!$license_valid) echo 'disabled'; ?>>
                <span class="dashicons dashicons-edit-large"></span>
                <?php echo esc_html($is_premium ? SEO_AEO_T::t('Genera solo testo') : SEO_AEO_T::t('Genera Contenuto')); ?>
                — <span class="credit-cost" data-cost-key="content_generation_medium">10</span> <?php SEO_AEO_T::e('crediti'); ?>
            </button>
        </p>
    </div>

    <?php if ($is_premium): ?>
    <?php ob_start(); ?>
jQuery(function($){
        $(document).on('click', '#seo-aeo-complete-article-btn', function(e){
            e.preventDefault();
            var topic = $('#content-topic').val().trim();
            var kwRaw = $('#content-keywords').val().trim();
            if (!topic) {
                if (window.SeoAeoOrchestra) SeoAeoOrchestra.showNotice('Inserisci un argomento.', 'error');
                else alert('Inserisci un argomento.');
                return;
            }
            var firstKw = kwRaw ? kwRaw.split(',')[0].trim() : topic;
            // Prepara container risultato compatibile con generateCompleteArticle
            var $out = $('#content-output');
            $out.html('<div class="expand-content-card"><div class="expand-content-body"></div><div class="expand-content-actions"></div></div>');
            if (window.SeoAeoOrchestra && SeoAeoOrchestra.generateCompleteArticle) {
                SeoAeoOrchestra.generateCompleteArticle(topic, firstKw, 'seo_content', $out);
            } else {
                alert('Script non caricato. Ricarica la pagina.');
            }
        });
    });
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>
    <?php endif; ?>

    <div id="content-output" class="orchestra-output">
        <p class="description"><?php SEO_AEO_T::e('Il contenuto generato apparira qui. Potrai copiarlo o creare direttamente un nuovo post.'); ?></p>
    </div>

    <div id="history-seo-content" class="orchestra-history-container"></div>

    <hr>

    <!-- Keyword Suggestions -->
    <div class="orchestra-tool-card" id="seo-keyword-suggestions">
        <h2><span class="dashicons dashicons-lightbulb" style="color:#F59E0B"></span> <?php SEO_AEO_T::e('Suggerimenti Keyword & Argomenti'); ?></h2>
        <p><?php SEO_AEO_T::e('Inserisci il tuo settore o argomento principale e l\'AI ti suggerira keyword e argomenti ad alto potenziale.'); ?></p>

        <div style="display:flex;gap:10px;align-items:flex-end;margin-bottom:15px;">
            <div style="flex:1;">
                <label style="font-weight:600;display:block;margin-bottom:5px;"><?php SEO_AEO_T::e('Settore / Argomento del sito'); ?></label>
                <input type="text" id="suggest-seo-context" class="large-text" placeholder="<?php echo esc_attr(SEO_AEO_T::t('es. Energia solare, pannelli fotovoltaici, impianti residenziali')); ?>" />
            </div>
            <button type="button" class="button button-primary" id="seo-suggest-keywords-btn"
                onclick="if(window.SeoAeoOrchestra)SeoAeoOrchestra.suggestKeywords('seo_content','suggest-seo-context','seo-suggestions-list');" <?php if (!$license_valid) echo 'disabled'; ?>>
                <span class="dashicons dashicons-lightbulb"></span> <?php SEO_AEO_T::e('Suggerisci'); ?>
            </button>
        </div>

        <div id="seo-suggestions-list"></div>
    </div>
</div>

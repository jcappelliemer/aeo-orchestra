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
    <h1 style="display:none;"><?php echo esc_html($T('Generatore Contenuti AEO')); ?></h1>

    <div class="orchestra-header aeo-header">
        <h2><?php SEO_AEO_T::e('Generatore Contenuti AEO'); ?></h2>
        <p class="description"><?php SEO_AEO_T::e('Genera contenuti ottimizzati per le risposte AI di Google, ChatGPT e Perplexity'); ?></p>
    </div>

    <?php
    $seo_aeo_qe_partial = dirname(__DIR__) . '/templates/partials/brand-voice-quick-edit.php';
    if (file_exists($seo_aeo_qe_partial)) include $seo_aeo_qe_partial;

    $seo_aeo_kp = array(
        'mode'    => 'csv',
        'targets' => array(
            '#aeo-content-keywords' => 'keyword',
            '#aeo-content-topic'    => 'topic',
        ),
        'label'   => 'Pesca da Keyword Research',
    );
    $seo_aeo_kp_partial = dirname(__DIR__) . '/templates/partials/keyword-picker.php';
    if (file_exists($seo_aeo_kp_partial)) include $seo_aeo_kp_partial;
    ?>

    <div class="orchestra-tool-card">
        <h2><span class="dashicons dashicons-format-chat" style="color:#EC4899"></span> <?php echo esc_html($T('Crea contenuti citabili dalle AI')); ?></h2>
        <p><?php echo esc_html($T('Genera articoli con struttura domanda-risposta, Schema.org markup e formato ottimizzato per essere citati dai motori AI.')); ?></p>

        <table class="form-table">
            <tr>
                <th><?php SEO_AEO_T::e('Argomento'); ?></th>
                <td>
                    <input type="text" id="aeo-content-topic" class="large-text" placeholder="<?php echo esc_attr(SEO_AEO_T::t('es: Come installare pannelli solari')); ?>" />
                </td>
            </tr>
            <tr>
                <th><?php SEO_AEO_T::e('Keywords'); ?></th>
                <td>
                    <input type="text" id="aeo-content-keywords" class="large-text" placeholder="<?php echo esc_attr(SEO_AEO_T::t('keyword1, keyword2, keyword3')); ?>" />
                    <p class="description"><?php SEO_AEO_T::e('Separa le keywords con virgola'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php SEO_AEO_T::e('Target AI Engines'); ?></th>
                <td>
                    <label><input type="checkbox" name="aeo_engines[]" value="google_ai" checked /> Google AI Overviews / Gemini</label><br>
                    <label><input type="checkbox" name="aeo_engines[]" value="chatgpt" checked /> ChatGPT (OpenAI)</label><br>
                    <label><input type="checkbox" name="aeo_engines[]" value="claude" checked /> Claude (Anthropic)</label><br>
                    <label><input type="checkbox" name="aeo_engines[]" value="perplexity" checked /> Perplexity AI</label><br>
                    <label><input type="checkbox" name="aeo_engines[]" value="grok" /> Grok (xAI)</label><br>
                    <label><input type="checkbox" name="aeo_engines[]" value="bing_copilot" /> Bing Copilot (Microsoft)</label><br>
                    <label><input type="checkbox" name="aeo_engines[]" value="meta_ai" /> Meta AI</label><br>
                    <label><input type="checkbox" name="aeo_engines[]" value="duckduckgo_ai" /> DuckDuckGo AI</label>
                </td>
            </tr>
            <tr>
                <th><?php SEO_AEO_T::e('Opzioni'); ?></th>
                <td>
                    <label><input type="checkbox" id="aeo-include-schema" checked /> <?php SEO_AEO_T::e('Includi Schema.org markup'); ?></label><br>
                    <label><input type="checkbox" id="aeo-include-faq" checked /> <?php SEO_AEO_T::e('Includi sezione FAQ strutturata'); ?></label>
                </td>
            </tr>
        </table>

        <p>
            <button type="button" class="button button-primary button-hero button-aeo" id="seo-aeo-aeo-content-btn" <?php if (!$license_valid) echo 'disabled'; ?>>
                <span class="dashicons dashicons-format-chat"></span> <?php echo esc_html($T('Genera Contenuto AEO')); ?> - <span class="credit-cost" data-cost-key="aeo_content">12</span> <?php echo esc_html($T('crediti')); ?>
            </button>
        </p>
    </div>

    <div id="aeo-content-output" class="orchestra-output">
        <p class="description"><?php echo esc_html($T('Il contenuto AEO generato apparira qui. Sara ottimizzato per essere citato dalle AI.')); ?></p>
    </div>
    <div id="history-aeo-content" class="orchestra-history-container"></div>

    <hr>

    <!-- AEO Keyword Suggestions -->
    <div class="orchestra-tool-card" id="aeo-keyword-suggestions">
        <h2><span class="dashicons dashicons-lightbulb" style="color:#EC4899"></span> <?php echo esc_html($T('Suggerimenti Argomenti AEO')); ?></h2>
        <p><?php echo esc_html($T('Scopri gli argomenti con il maggiore potenziale di citazione AI per il tuo settore.')); ?></p>

        <div style="display:flex;gap:10px;align-items:flex-end;margin-bottom:15px;">
            <div style="flex:1;">
                <label style="font-weight:600;display:block;margin-bottom:5px;"><?php echo esc_html($T('Settore / Argomento principale')); ?></label>
                <input type="text" id="suggest-aeo-context" class="large-text" placeholder="<?php echo esc_attr(SEO_AEO_T::t('es. Impianti fotovoltaici, risparmio energetico, bonus 110%')); ?>" />
            </div>
            <button type="button" class="button button-primary button-aeo" id="aeo-suggest-keywords-btn"
                onclick="if(window.SeoAeoOrchestra)SeoAeoOrchestra.suggestKeywords('aeo_content','suggest-aeo-context','aeo-suggestions-list');" <?php if (!$license_valid) echo 'disabled'; ?>>
                <span class="dashicons dashicons-lightbulb"></span> <?php echo esc_html($T('Suggerisci')); ?>
            </button>
        </div>

        <div id="aeo-suggestions-list"></div>
    </div>
</div>
<?php ob_start(); ?>
/* 3.31.2: auto-fill da URL/keyword query params */
jQuery(function($) {
    try {
        var p = new URLSearchParams(window.location.search);
        var qKw = p.get('keyword');
        var qUrl = p.get('url');
        if (qKw) {
            $('#aeo-content-keywords').val(qKw);
            if (!$('#aeo-content-topic').val()) $('#aeo-content-topic').val(qKw);
        }
        if (qUrl && !$('#aeo-content-topic').val()) {
            // Estrai un topic readable dall'URL (slug)
            var slug = qUrl.replace(/^https?:\/\/[^\/]+\//, '').replace(/\/$/, '').split('/').pop();
            slug = slug.replace(/-/g, ' ').replace(/\.\w+$/, '');
            if (slug) $('#aeo-content-topic').val(slug);
        }
    } catch (e) {}
});
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>

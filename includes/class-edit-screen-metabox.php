<?php
// phpcs:disable WordPress.Security.NonceVerification.Missing
// phpcs:disable WordPress.Security.NonceVerification.Recommended
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
// Reason: nonce + sanitize chain is enforced upstream. AJAX handlers call
// check_ajax_referer at the top of each method; admin form handlers call
// check_admin_referer; reads of $_SERVER (DOCUMENT_ROOT, HTTP_USER_AGENT)
// are wrapped in sanitize_text_field(wp_unslash()) at the read site. The
// Plugin Check static analyzer cannot trace control flow across method
// boundaries, so it flags these as missing — but the security guarantees
// hold at runtime.
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com. All rights reserved.
 */

/**
 * SEO_AEO_Edit_Screen_Metabox — Per-page SEO + AEO override metabox.
 *
 * Stage 2.5 Output<head> G (3.35.62-beta).
 *
 * Adds a sidebar metabox in wp-admin/post.php on every public post type with
 * collapsible sections to override site-wide settings on a per-page basis.
 *
 * Sections:
 *   📝 Title format         → _orch_title_override (string)
 *   📄 Meta description     → _orch_meta_desc_override (textarea)
 *   🔗 Canonical            → _orch_canonical_override + _orch_canonical_disabled
 *   🔗 OpenGraph image      → _orch_og_image_override (URL)
 *   🤖 Robots               → _orch_robots_index + _orch_robots_follow (tristate '': default | '1': on | '0': off)
 *   🎨 Schema type          → _orch_schema_type_override + _orch_schema_excluded
 *
 * Auto-detected info shown for context:
 *   - Current title (computed via class-output-renderer logic)
 *   - Detected role from SEO_AEO_Page_Roles
 *   - Default schema type for that role
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Edit_Screen_Metabox {

    const NONCE_FIELD  = '_orch_metabox_nonce';
    const NONCE_ACTION = 'seo_aeo_orchestra_edit_screen_metabox';

    public static function init() {
        add_action('add_meta_boxes', array(__CLASS__, 'register'));
        add_action('save_post',      array(__CLASS__, 'save'), 10, 2);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_styles'));
    }

    public static function register() {
        // Apply to all public post types (excluded blocked CPTs)
        $public = get_post_types(array('public' => true), 'names');
        $blocked = class_exists('SEO_AEO_Global_Filters')
            ? array_flip(SEO_AEO_Global_Filters::hardcoded_post_type_blocklist())
            : array();
        foreach ($public as $pt) {
            if (isset($blocked[$pt])) continue;
            if ($pt === 'attachment') continue;
            add_meta_box(
                'seo_aeo_orchestra_seo_metabox',
                '🎯 AEO Orchestra · SEO + AEO',
                array(__CLASS__, 'render'),
                $pt,
                'normal',  // full-width below editor
                'default'
            );
        }
    }

    public static function enqueue_styles($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'), true)) return;
        // 3.35.69: enqueue media library for OG image picker
        wp_enqueue_media();
        ?>
        <style id="seo-aeo-orchestra-metabox-css">
            #seo_aeo_orchestra_seo_metabox .inside { padding: 0 12px 12px; }
            .orch-mb-intro { margin: 12px 0; padding: 10px 14px; background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%); border: 1px solid #bfdbfe; border-left: 4px solid #0055FF; border-radius: 6px; font-size: 12px; line-height: 1.5; }
            .orch-mb-detected { margin-top: 6px; padding: 8px 10px; background: #f0fdf4; border-left: 3px solid #16a34a; border-radius: 4px; font-size: 11px; color: #14532d; }
            .orch-mb-detected strong { color: #052e16; }
            .orch-mb-section { margin: 8px 0; border: 1px solid #e2e8f0; border-radius: 6px; background: #ffffff; }
            .orch-mb-section > summary { padding: 10px 14px; cursor: pointer; font-size: 13px; font-weight: 500; user-select: none; }
            .orch-mb-section[open] > summary { background: #f8fafc; border-bottom: 1px solid #e2e8f0; border-radius: 6px 6px 0 0; }
            .orch-mb-section > summary .orch-mb-modified-badge { display: none; margin-left: 6px; padding: 1px 6px; background: #fef3c7; color: #92400e; border-radius: 8px; font-size: 10px; font-weight: 600; text-transform: uppercase; }
            .orch-mb-section.is-modified > summary .orch-mb-modified-badge { display: inline-block; }
            .orch-mb-section-body { padding: 12px 14px; }
            .orch-mb-section-body p.description { margin: 0 0 6px; font-size: 11px; color: #64748b; }
            .orch-mb-section-body input[type="text"],
            .orch-mb-section-body input[type="url"],
            .orch-mb-section-body textarea,
            .orch-mb-section-body select { width: 100%; box-sizing: border-box; }
            .orch-mb-tristate { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin: 4px 0; }
            .orch-mb-tristate label { font-size: 12px; }
            .orch-mb-default-info { margin-top: 6px; padding: 4px 8px; background: #f1f5f9; color: #475569; font-size: 11px; border-radius: 4px; font-family: ui-monospace, monospace; }
            .orch-mb-counter { float: right; font-size: 10px; color: #94a3b8; font-family: ui-monospace, monospace; }
            .orch-mb-overrides-indicator { margin: 12px 0 0; }
            .orch-mb-ov-pill { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
            .orch-mb-ov-default { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
            .orch-mb-ov-active { background: #dcfce7; color: #14532d; border: 1px solid #86efac; }
        </style>
        <?php ob_start(); ?>
document.addEventListener('DOMContentLoaded', function () {
            // 3.35.69: Char counters + override count indicator + media picker
            function updateCounter(input, max, $counter) {
                var len = (input.value || '').length;
                $counter.textContent = len + ' / ' + max + ' caratteri';
                $counter.style.color = (len > max) ? '#dc2626' : (len > max * 0.9 ? '#f59e0b' : '#94a3b8');
            }
            function attachCounter(inputId, counterId, max) {
                var input = document.getElementById(inputId);
                var counter = document.getElementById(counterId);
                if (!input || !counter) return;
                updateCounter(input, max, counter);
                input.addEventListener('input', function() { updateCounter(input, max, counter); });
            }
            attachCounter('orch-mb-title', 'orch-mb-title-counter', 60);
            attachCounter('orch-mb-og-title', 'orch-mb-og-title-counter', 60);
            attachCounter('orch-mb-tw-title', 'orch-mb-tw-title-counter', 70);

            // Override count indicator
            function updateOverridesCount() {
                var inputs = document.querySelectorAll('#seo_aeo_orchestra_seo_metabox input[type="text"], #seo_aeo_orchestra_seo_metabox input[type="url"], #seo_aeo_orchestra_seo_metabox textarea, #seo_aeo_orchestra_seo_metabox select');
                var radios = document.querySelectorAll('#seo_aeo_orchestra_seo_metabox input[type="radio"]:checked');
                var checks = document.querySelectorAll('#seo_aeo_orchestra_seo_metabox input[type="checkbox"]:checked');
                var count = 0;
                inputs.forEach(function(i) {
                    if (i.value && i.value.trim() !== '' && i.value !== i.placeholder) count++;
                });
                radios.forEach(function(r) { if (r.value !== '') count++; });
                checks.forEach(function(c) { if (c.value === '1') count++; });

                var indicator = document.getElementById('orch-mb-overrides-indicator');
                if (!indicator) return;
                if (count === 0) {
                    indicator.innerHTML = '<span class="orch-mb-ov-pill orch-mb-ov-default">⚪ 0 override (usa default)</span>';
                } else {
                    indicator.innerHTML = '<span class="orch-mb-ov-pill orch-mb-ov-active">✓ ' + count + ' override attivi</span>';
                }
            }
            updateOverridesCount();
            document.querySelectorAll('#seo_aeo_orchestra_seo_metabox input, #seo_aeo_orchestra_seo_metabox textarea, #seo_aeo_orchestra_seo_metabox select').forEach(function(el) {
                el.addEventListener('input', updateOverridesCount);
                el.addEventListener('change', updateOverridesCount);
            });

            // WP media library picker for OG image
            var ogImagePickBtn = document.getElementById('orch-mb-og-image-pick-btn');
            if (ogImagePickBtn && typeof wp !== 'undefined' && wp.media) {
                ogImagePickBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var frame = wp.media({
                        title: 'Seleziona immagine OG',
                        button: { text: 'Usa questa immagine' },
                        multiple: false,
                        library: { type: 'image' }
                    });
                    frame.on('select', function() {
                        var att = frame.state().get('selection').first().toJSON();
                        var input = document.querySelector('input[name="orch_og_image_override"]');
                        if (input) {
                            input.value = att.url;
                            input.dispatchEvent(new Event('input'));
                        }
                    });
                    frame.open();
                });
            }

            // // Mark sections as "modified" when user changes any input
            document.querySelectorAll('.orch-mb-section').forEach(function (section) {
                var inputs = section.querySelectorAll('input[type="text"], input[type="url"], textarea, select');
                inputs.forEach(function (i) {
                    i.addEventListener('input', function () {
                        if (i.value && i.value !== i.defaultValue) {
                            section.classList.add('is-modified');
                        }
                    });
                });
                // Initial state
                inputs.forEach(function (i) {
                    if (i.value && i.value.trim() !== '') section.classList.add('is-modified');
                });
            });
            // Char counter for meta description textarea
            var descTextarea = document.getElementById('orch-mb-meta-desc');
            var counter = document.getElementById('orch-mb-meta-desc-counter');
            if (descTextarea && counter) {
                var update = function () { counter.textContent = descTextarea.value.length + ' / 160 caratteri'; };
                descTextarea.addEventListener('input', update);
                update();
            }
        });
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>
        <?php
    }

    public static function render($post) {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);

        // Read current values
        $title_override         = (string) get_post_meta($post->ID, '_orch_title_override', true);
        $meta_desc_override     = (string) get_post_meta($post->ID, '_orch_meta_desc_override', true);
        $canonical_override     = (string) get_post_meta($post->ID, '_orch_canonical_override', true);
        $canonical_disabled     = (string) get_post_meta($post->ID, '_orch_canonical_disabled', true);
        $og_image_override      = (string) get_post_meta($post->ID, '_orch_og_image_override', true);
        $robots_index           = get_post_meta($post->ID, '_orch_robots_index', true);
        $robots_follow          = get_post_meta($post->ID, '_orch_robots_follow', true);
        $schema_type_override   = (string) get_post_meta($post->ID, '_orch_schema_type_override', true);
        $schema_excluded        = (string) get_post_meta($post->ID, '_orch_schema_excluded', true);
        // 3.35.69 G feature: extended OG / Twitter overrides
        $og_title       = (string) get_post_meta($post->ID, '_orch_og_title', true);
        $og_description = (string) get_post_meta($post->ID, '_orch_og_description', true);
        $tw_title       = (string) get_post_meta($post->ID, '_orch_twitter_title', true);
        $tw_description = (string) get_post_meta($post->ID, '_orch_twitter_description', true);
        $tw_card        = (string) get_post_meta($post->ID, '_orch_twitter_card', true);

        // Detected role + default schema type for context
        $role = '';
        $default_schema = 'WebPage';
        if (class_exists('SEO_AEO_Page_Roles')) {
            $role = SEO_AEO_Page_Roles::get_role($post->ID) ?: '';
            $default_schema = SEO_AEO_Page_Roles::role_to_schema_type($role) ?: 'WebPage';
        }

        // Schema type options
        $schema_types = class_exists('SEO_AEO_Schema') ? SEO_AEO_Schema::valid_schema_types() : array(
            'WebPage', 'Article', 'Product', 'Service', 'FAQPage', 'AboutPage', 'ContactPage', 'CollectionPage', 'LocalBusiness',
        );

        // Default title preview (best-effort — actual computation happens at frontend render)
        $default_title_preview = get_the_title($post);
        if ($default_title_preview === '' || $default_title_preview === null) $default_title_preview = '(senza titolo)';

        ?>
        <div class="orch-mb-overrides-indicator" id="orch-mb-overrides-indicator"></div>
        <div class="orch-mb-intro">
            🎯 <strong>AEO Orchestra · SEO + AEO override per questa pagina.</strong><br>
            I valori che imposti qui sovrascrivono i settings globali del plugin <em>solo per questa pagina</em>. Lascia vuoto per usare i default automatici.
            <?php if ($role !== ''): ?>
            <div class="orch-mb-detected">
                Ruolo rilevato: <strong><?php echo esc_html($role); ?></strong> ·
                Schema type default: <strong><?php echo esc_html($default_schema); ?></strong>
            </div>
            <?php endif; ?>
        </div>

        <details class="orch-mb-section">
            <summary>📝 Title format <span class="orch-mb-modified-badge">modified</span></summary>
            <div class="orch-mb-section-body">
                <p class="description">Sovrascrivi il <code>&lt;title&gt;</code> per questa pagina specifica. Lascia vuoto per usare il template del CPT.</p>
                <span id="orch-mb-title-counter" class="orch-mb-counter">0 / 60 caratteri</span><input type="text" id="orch-mb-title" name="orch_title_override" value="<?php echo esc_attr($title_override); ?>" placeholder="<?php echo esc_attr($default_title_preview); ?>" maxlength="200" />
                <div class="orch-mb-default-info">Default: <em><?php echo esc_html($default_title_preview); ?></em></div>
            </div>
        </details>

        <details class="orch-mb-section">
            <summary>📄 Meta description <span class="orch-mb-modified-badge">modified</span></summary>
            <div class="orch-mb-section-body">
                <p class="description">Sovrascrivi la meta description per questa pagina. <span id="orch-mb-meta-desc-counter" class="orch-mb-counter">0 / 160 caratteri</span></p>
                <textarea id="orch-mb-meta-desc" name="orch_meta_desc_override" rows="3" maxlength="320" placeholder="Lascia vuoto per usare il fallback automatico (Yoast/RankMath/AIOSEO/excerpt/content)"><?php echo esc_textarea($meta_desc_override); ?></textarea>
            </div>
        </details>

        <details class="orch-mb-section">
            <summary>🔗 Canonical URL <span class="orch-mb-modified-badge">modified</span></summary>
            <div class="orch-mb-section-body">
                <p class="description">Sovrascrivi l'URL canonical. Default: permalink WordPress.</p>
                <input type="url" name="orch_canonical_override" value="<?php echo esc_attr($canonical_override); ?>" placeholder="<?php echo esc_attr(get_permalink($post)); ?>" />
                <div class="orch-mb-default-info">Default: <em><?php echo esc_html(get_permalink($post)); ?></em></div>
                <label style="display:block; margin-top:8px;">
                    <input type="checkbox" name="orch_canonical_disabled" value="1" <?php checked($canonical_disabled, '1'); ?> />
                    Disabilita canonical su questa pagina (utile per redirect, thank-you, preview)
                </label>
            </div>
        </details>

        <details class="orch-mb-section">
            <summary>🔗 OpenGraph image <span class="orch-mb-modified-badge">modified</span></summary>
            <div class="orch-mb-section-body">
                <p class="description">URL immagine personalizzata per OG/Twitter. Default: featured image del post → fallback globale.</p>
                <input type="url" name="orch_og_image_override" value="<?php echo esc_attr($og_image_override); ?>" placeholder="https://www.example.com/wp-content/uploads/.../og-image.png" /> <button type="button" id="orch-mb-og-image-pick-btn" class="button" style="margin-top:6px;">📁 Scegli da Media Library</button>
                <p class="description" style="margin-top:6px;">Dimensione consigliata: <strong>1200x630px</strong>. Suggerimento: usa la "Featured image" di WordPress quando possibile.</p>
            </div>
        </details>

        <details class="orch-mb-section">
            <summary>🤖 Robots <span class="orch-mb-modified-badge">modified</span></summary>
            <div class="orch-mb-section-body">
                <p class="description">Sovrascrivi i meta robots per questa pagina specifica. Default: usa setting CPT.</p>
                <div class="orch-mb-tristate">
                    <strong>index:</strong>
                    <label><input type="radio" name="orch_robots_index" value="" <?php checked($robots_index === '', true); ?> /> Default</label>
                    <label><input type="radio" name="orch_robots_index" value="1" <?php checked($robots_index, '1'); ?> /> Force <code>index</code></label>
                    <label><input type="radio" name="orch_robots_index" value="0" <?php checked($robots_index, '0'); ?> /> Force <code>noindex</code></label>
                </div>
                <div class="orch-mb-tristate">
                    <strong>follow:</strong>
                    <label><input type="radio" name="orch_robots_follow" value="" <?php checked($robots_follow === '', true); ?> /> Default</label>
                    <label><input type="radio" name="orch_robots_follow" value="1" <?php checked($robots_follow, '1'); ?> /> Force <code>follow</code></label>
                    <label><input type="radio" name="orch_robots_follow" value="0" <?php checked($robots_follow, '0'); ?> /> Force <code>nofollow</code></label>
                </div>
            </div>
        </details>

        <details class="orch-mb-section">
            <summary>🎨 Schema type <span class="orch-mb-modified-badge">modified</span></summary>
            <div class="orch-mb-section-body">
                <p class="description">Forza un tipo Schema.org specifico per questa pagina. Default: <strong><?php echo esc_html($default_schema); ?></strong> (basato sul ruolo rilevato).</p>
                <select name="orch_schema_type_override">
                    <option value="">— Auto (usa <?php echo esc_html($default_schema); ?>) —</option>
                    <?php foreach ($schema_types as $t): ?>
                    <option value="<?php echo esc_attr($t); ?>" <?php selected($schema_type_override, $t); ?>><?php echo esc_html($t); ?></option>
                    <?php endforeach; ?>
                </select>
                <label style="display:block; margin-top:8px;">
                    <input type="checkbox" name="orch_schema_excluded" value="1" <?php checked($schema_excluded, '1'); ?> />
                    Escludi questa pagina dall'emissione Schema.org JSON-LD
                </label>
            </div>
        </details>

        <details class="orch-mb-section">
            <summary>🌐 OpenGraph (Facebook, LinkedIn, AI) <span class="orch-mb-modified-badge">modified</span></summary>
            <div class="orch-mb-section-body">
                <p class="description">Sovrascrivi title/description per OpenGraph. Default: usa il title + meta description della pagina.</p>
                <label style="display:block;margin-top:8px;font-size:12px;font-weight:500;">og:title</label>
                <span id="orch-mb-og-title-counter" class="orch-mb-counter">0 / 60 caratteri</span><input type="text" id="orch-mb-og-title" name="orch_og_title" value="<?php echo esc_attr($og_title); ?>" maxlength="120" placeholder="(usa title della pagina)" />
                <label style="display:block;margin-top:8px;font-size:12px;font-weight:500;">og:description</label>
                <textarea id="orch-mb-og-description" name="orch_og_description" rows="2" maxlength="240" placeholder="(usa meta description)"><?php echo esc_textarea($og_description); ?></textarea>
            </div>
        </details>

        <details class="orch-mb-section">
            <summary>🐦 Twitter Cards <span class="orch-mb-modified-badge">modified</span></summary>
            <div class="orch-mb-section-body">
                <p class="description">Sovrascrivi Twitter card per questa pagina. Default: usa OG fallback + card type da settings globali.</p>
                <label style="display:block;margin-top:8px;font-size:12px;font-weight:500;">twitter:title</label>
                <span id="orch-mb-tw-title-counter" class="orch-mb-counter">0 / 70 caratteri</span><input type="text" id="orch-mb-tw-title" name="orch_twitter_title" value="<?php echo esc_attr($tw_title); ?>" maxlength="70" placeholder="(usa og:title)" />
                <label style="display:block;margin-top:8px;font-size:12px;font-weight:500;">twitter:description</label>
                <textarea id="orch-mb-tw-description" name="orch_twitter_description" rows="2" maxlength="200" placeholder="(usa og:description)"><?php echo esc_textarea($tw_description); ?></textarea>
                <label style="display:block;margin-top:8px;font-size:12px;font-weight:500;">twitter:card</label>
                <select name="orch_twitter_card">
                    <option value=""<?php selected($tw_card, ''); ?>>(usa setting globale)</option>
                    <option value="summary"<?php selected($tw_card, 'summary'); ?>>summary</option>
                    <option value="summary_large_image"<?php selected($tw_card, 'summary_large_image'); ?>>summary_large_image</option>
                    <option value="app"<?php selected($tw_card, 'app'); ?>>app</option>
                    <option value="player"<?php selected($tw_card, 'player'); ?>>player</option>
                </select>
            </div>
        </details>
        <?php
    }

    public static function save($post_id, $post) {
        // Nonce + capability + autosave checks
        if (!isset($_POST[self::NONCE_FIELD])) return;
        if (!wp_verify_nonce($_POST[self::NONCE_FIELD], self::NONCE_ACTION)) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        if (!current_user_can('edit_post', $post_id)) return;

        // Title override
        if (isset($_POST['orch_title_override'])) {
            $v = sanitize_text_field(wp_unslash($_POST['orch_title_override']));
            if ($v === '') delete_post_meta($post_id, '_orch_title_override');
            else update_post_meta($post_id, '_orch_title_override', $v);
        }

        // Meta description override
        if (isset($_POST['orch_meta_desc_override'])) {
            $v = sanitize_textarea_field(wp_unslash($_POST['orch_meta_desc_override']));
            if ($v === '') delete_post_meta($post_id, '_orch_meta_desc_override');
            else update_post_meta($post_id, '_orch_meta_desc_override', $v);
        }

        // Canonical override + disabled
        if (isset($_POST['orch_canonical_override'])) {
            $v = esc_url_raw(wp_unslash($_POST['orch_canonical_override']));
            if ($v === '') delete_post_meta($post_id, '_orch_canonical_override');
            else update_post_meta($post_id, '_orch_canonical_override', $v);
        }
        if (!empty($_POST['orch_canonical_disabled'])) {
            update_post_meta($post_id, '_orch_canonical_disabled', '1');
        } else {
            delete_post_meta($post_id, '_orch_canonical_disabled');
        }

        // OG image override
        if (isset($_POST['orch_og_image_override'])) {
            $v = esc_url_raw(wp_unslash($_POST['orch_og_image_override']));
            if ($v === '') delete_post_meta($post_id, '_orch_og_image_override');
            else update_post_meta($post_id, '_orch_og_image_override', $v);
        }

        // Robots index/follow tristate
        if (isset($_POST['orch_robots_index'])) {
            $v = sanitize_text_field(wp_unslash($_POST['orch_robots_index']));
            if ($v === '') delete_post_meta($post_id, '_orch_robots_index');
            elseif ($v === '0' || $v === '1') update_post_meta($post_id, '_orch_robots_index', $v);
        }
        if (isset($_POST['orch_robots_follow'])) {
            $v = sanitize_text_field(wp_unslash($_POST['orch_robots_follow']));
            if ($v === '') delete_post_meta($post_id, '_orch_robots_follow');
            elseif ($v === '0' || $v === '1') update_post_meta($post_id, '_orch_robots_follow', $v);
        }

        // Schema type override + excluded
        if (isset($_POST['orch_schema_type_override'])) {
            $v = sanitize_text_field(wp_unslash($_POST['orch_schema_type_override']));
            $valid = class_exists('SEO_AEO_Schema') ? SEO_AEO_Schema::valid_schema_types() : array();
            if ($v === '' || !in_array($v, $valid, true)) {
                delete_post_meta($post_id, '_orch_schema_type_override');
            } else {
                update_post_meta($post_id, '_orch_schema_type_override', $v);
            }
        }
        if (!empty($_POST['orch_schema_excluded'])) {
            update_post_meta($post_id, '_orch_schema_excluded', '1');
        } else {
            delete_post_meta($post_id, '_orch_schema_excluded');
        }

        // 3.35.69 G feature: OG title/description overrides
        if (isset($_POST['orch_og_title'])) {
            $v = sanitize_text_field(wp_unslash($_POST['orch_og_title']));
            if ($v === '') delete_post_meta($post_id, '_orch_og_title');
            else update_post_meta($post_id, '_orch_og_title', $v);
        }
        if (isset($_POST['orch_og_description'])) {
            $v = sanitize_textarea_field(wp_unslash($_POST['orch_og_description']));
            if ($v === '') delete_post_meta($post_id, '_orch_og_description');
            else update_post_meta($post_id, '_orch_og_description', $v);
        }
        // Twitter Cards overrides
        if (isset($_POST['orch_twitter_title'])) {
            $v = sanitize_text_field(wp_unslash($_POST['orch_twitter_title']));
            if ($v === '') delete_post_meta($post_id, '_orch_twitter_title');
            else update_post_meta($post_id, '_orch_twitter_title', $v);
        }
        if (isset($_POST['orch_twitter_description'])) {
            $v = sanitize_textarea_field(wp_unslash($_POST['orch_twitter_description']));
            if ($v === '') delete_post_meta($post_id, '_orch_twitter_description');
            else update_post_meta($post_id, '_orch_twitter_description', $v);
        }
        if (isset($_POST['orch_twitter_card'])) {
            $v = sanitize_text_field(wp_unslash($_POST['orch_twitter_card']));
            $valid = array('summary', 'summary_large_image', 'app', 'player');
            if ($v === '' || !in_array($v, $valid, true)) {
                delete_post_meta($post_id, '_orch_twitter_card');
            } else {
                update_post_meta($post_id, '_orch_twitter_card', $v);
            }
        }

        // Bust the LLMs txt cache so the next /llms.txt reflects changes (cheap)
        if (class_exists('SEO_AEO_LLMs_Txt') && method_exists('SEO_AEO_LLMs_Txt', 'flush_all_cache')) {
            SEO_AEO_LLMs_Txt::flush_all_cache();
        }
    }
}

SEO_AEO_Edit_Screen_Metabox::init();

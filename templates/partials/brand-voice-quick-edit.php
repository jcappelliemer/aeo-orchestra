<?php
/*
 * Partial: Brand Voice Quick Edit (since 3.21.9)
 * Pill in alto al template + drawer laterale per modifica veloce del profilo attivo,
 * senza dover navigare al submenu Brand Voice. Salva direttamente sul profilo attivo
 * via AJAX `seo_aeo_orchestra_brand_voice_update`.
 *
 * Include path: include __DIR__ . '/partials/brand-voice-quick-edit.php';
 */
if (!defined('ABSPATH')) exit;

$seo_aeo_bv_active = class_exists('SEO_AEO_Brand_Voice') ? SEO_AEO_Brand_Voice::get_active_profile() : null;
$seo_aeo_bv_url = admin_url('admin.php?page=seo-aeo-brand-voice');
?>

<div class="orch-bvq-bar">
    <?php if ($seo_aeo_bv_active): ?>
        <div class="orch-bvq-pill orch-bvq-pill-active" data-profile-id="<?php echo (int) $seo_aeo_bv_active['_id']; ?>">
            <span class="orch-bvq-icon">🎙️</span>
            <div class="orch-bvq-pill-text">
                <span class="orch-bvq-pill-label"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Voce attiva')) : 'Voce attiva'; ?></span>
                <span class="orch-bvq-pill-name"><?php echo esc_html($seo_aeo_bv_active['_name']); ?></span>
                <span class="orch-bvq-pill-tone"><?php echo esc_html(ucfirst($seo_aeo_bv_active['tone'] ?? '?')); ?></span>
            </div>
            <button type="button" class="orch-bvq-edit-btn"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('✏️ Modifica voce')) : '✏️ Modifica voce'; ?></button>
        </div>
    <?php else: ?>
        <div class="orch-bvq-pill orch-bvq-pill-inactive">
            <span class="orch-bvq-icon orch-bvq-icon-muted">🎙️</span>
            <div class="orch-bvq-pill-text">
                <span class="orch-bvq-pill-label"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Nessuna voce attiva')) : 'Nessuna voce attiva'; ?></span>
                <span class="orch-bvq-pill-name-muted"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Le generazioni useranno il tono AI predefinito')) : 'Le generazioni useranno il tono AI predefinito'; ?></span>
            </div>
            <a href="<?php echo esc_url($seo_aeo_bv_url); ?>" class="orch-bvq-config-btn"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('→ Configura Brand Voice')) : '→ Configura Brand Voice'; ?></a>
        </div>
    <?php endif; ?>
</div>

<?php if ($seo_aeo_bv_active): ?>
<!-- DRAWER -->
<div class="orch-bvq-backdrop" id="orch-bvq-backdrop">
    <aside class="orch-bvq-drawer" id="orch-bvq-drawer">
        <div class="orch-bvq-drawer-head">
            <div>
                <div class="orch-bvq-drawer-eyebrow">🎙️ Modifica Brand Voice</div>
                <h3 class="orch-bvq-drawer-title"><?php echo esc_html($seo_aeo_bv_active['_name']); ?></h3>
                <p class="orch-bvq-drawer-sub">Le modifiche si applicano subito alle prossime generazioni AI di questa pagina.</p>
            </div>
            <button type="button" class="orch-bvq-drawer-close" id="orch-bvq-drawer-close">×</button>
        </div>

        <div class="orch-bvq-drawer-body">
            <div class="orch-bvq-field">
                <label>Tono</label>
                <select data-bvq-field="tone">
                    <?php
                    $seo_aeo_tones = array('professionale', 'formale', 'casual', 'amichevole', 'tecnico', 'narrativo', 'ibrido', 'autorevole', 'consulenziale', 'commerciale');
                    $seo_aeo_cur_tone = strtolower($seo_aeo_bv_active['tone'] ?? '');
                    foreach ($seo_aeo_tones as $seo_aeo_t):
                        $seo_aeo_sel = $seo_aeo_cur_tone === $seo_aeo_t ? ' selected' : '';
                        echo '<option value="' . esc_attr($seo_aeo_t) . '"' . esc_attr($seo_aeo_sel) . '>' . esc_html(ucfirst($seo_aeo_t)) . '</option>';
                    endforeach;
                    ?>
                </select>
            </div>

            <div class="orch-bvq-field">
                <label>Descrizione tono</label>
                <textarea rows="2" data-bvq-field="tone_description"><?php echo esc_textarea($seo_aeo_bv_active['tone_description'] ?? ''); ?></textarea>
            </div>

            <div class="orch-bvq-field">
                <label>Audience target</label>
                <textarea rows="2" data-bvq-field="audience"><?php echo esc_textarea($seo_aeo_bv_active['audience'] ?? ''); ?></textarea>
            </div>

            <div class="orch-bvq-field">
                <label>🎨 Vocabolario distintivo (separato da virgole, max 30)</label>
                <textarea rows="3" data-bvq-field="distinctive_vocabulary" placeholder="<?php echo esc_attr(SEO_AEO_T::t('parola1, frase chiave 2, ...')); ?>"><?php
                    $seo_aeo_vocab = $seo_aeo_bv_active['distinctive_vocabulary'] ?? array();
                    echo esc_textarea(is_array($seo_aeo_vocab) ? implode(', ', $seo_aeo_vocab) : '');
                ?></textarea>
            </div>

            <div class="orch-bvq-field">
                <label>🚫 Anti-cliché — parole/frasi da evitare (max 20)</label>
                <textarea rows="2" data-bvq-field="avoid_words" placeholder="<?php echo esc_attr(SEO_AEO_T::t('cliché 1, cliché 2, ...')); ?>"><?php
                    $seo_aeo_avoid = $seo_aeo_bv_active['avoid_words'] ?? array();
                    echo esc_textarea(is_array($seo_aeo_avoid) ? implode(', ', $seo_aeo_avoid) : '');
                ?></textarea>
            </div>

            <div class="orch-bvq-field">
                <label>📝 Summary per prompt AI</label>
                <textarea rows="6" data-bvq-field="summary_for_prompt"><?php echo esc_textarea($seo_aeo_bv_active['summary_for_prompt'] ?? ''); ?></textarea>
                <small>Questo testo viene aggiunto a ogni prompt AI. Riassumi tono, stile e brand voice in modo accionabile.</small>
            </div>
        </div>

        <div class="orch-bvq-drawer-foot">
            <div class="orch-bvq-drawer-status" id="orch-bvq-drawer-status"></div>
            <div class="orch-bvq-drawer-actions">
                <button type="button" class="button" id="orch-bvq-cancel">Annulla</button>
                <button type="button" class="button button-primary" id="orch-bvq-save">💾 Salva e applica</button>
            </div>
        </div>
    </aside>
</div>
<?php endif; ?>

<?php ob_start(); ?>
.orch-bvq-bar { margin: 14px 0 18px; }
.orch-bvq-pill { display: flex; align-items: center; gap: 14px; padding: 12px 16px; border-radius: 12px; border: 1px solid; }
.orch-bvq-pill-active { background: linear-gradient(90deg, #fdf4ff, #fce7f3); border-color: #e9d5ff; }
.orch-bvq-pill-inactive { background: #f9fafb; border-color: #e5e7eb; }
.orch-bvq-icon { font-size: 28px; line-height: 1; }
.orch-bvq-icon-muted { opacity: 0.4; }
.orch-bvq-pill-text { flex: 1; display: flex; flex-direction: column; gap: 2px; }
.orch-bvq-pill-label { font-size: 11px; color: #6b21a8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; }
.orch-bvq-pill-inactive .orch-bvq-pill-label { color: #6b7280; }
.orch-bvq-pill-name { font-size: 16px; font-weight: 700; color: #1a0b2e; }
.orch-bvq-pill-name-muted { font-size: 13px; color: #6b7280; }
.orch-bvq-pill-tone { display: inline-block; padding: 2px 10px; background: rgba(124,58,237,0.12); border-radius: 999px; font-size: 11px; color: #6b21a8; font-weight: 600; width: fit-content; margin-top: 2px; }
.orch-bvq-edit-btn { padding: 8px 16px; background: #fff; color: #6b21a8; border: 1px solid #d8b4fe; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.15s; }
.orch-bvq-edit-btn:hover { background: #f3e8ff; border-color: #a855f7; }
.orch-bvq-config-btn { padding: 8px 14px; color: #4b5563; text-decoration: none; font-weight: 600; font-size: 13px; }
.orch-bvq-config-btn:hover { color: #6b21a8; }

/* Drawer */
.orch-bvq-backdrop { position: fixed; inset: 0; background: rgba(10,14,39,0.6); backdrop-filter: blur(3px); z-index: 100000; display: none; }
.orch-bvq-backdrop.open { display: block; }
.orch-bvq-drawer { position: fixed; top: 0; right: -560px; width: 540px; max-width: 100vw; height: 100vh; background: #fff; box-shadow: -16px 0 48px rgba(0,0,0,0.3); display: flex; flex-direction: column; transition: right 0.28s cubic-bezier(.2,.8,.2,1); z-index: 100001; }
.orch-bvq-backdrop.open .orch-bvq-drawer { right: 0; }
.orch-bvq-drawer-head { padding: 22px 26px; background: linear-gradient(135deg, #1a0b2e, #7C3AED, #EC4899); color: #fff; display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
.orch-bvq-drawer-eyebrow { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; font-weight: 600; }
.orch-bvq-drawer-title { margin: 6px 0 4px; font-size: 22px; font-weight: 700; }
.orch-bvq-drawer-sub { margin: 0; font-size: 13px; opacity: 0.85; line-height: 1.45; }
.orch-bvq-drawer-close { background: rgba(255,255,255,0.18); border: none; color: #fff; width: 36px; height: 36px; border-radius: 50%; font-size: 22px; cursor: pointer; flex-shrink: 0; }
.orch-bvq-drawer-close:hover { background: rgba(255,255,255,0.3); }
.orch-bvq-drawer-body { flex: 1; overflow-y: auto; padding: 22px 26px; display: flex; flex-direction: column; gap: 16px; }
.orch-bvq-field label { display: block; font-weight: 600; font-size: 13px; color: #1a0b2e; margin-bottom: 6px; }
.orch-bvq-field input, .orch-bvq-field select, .orch-bvq-field textarea { width: 100%; padding: 9px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 13px; font-family: inherit; resize: vertical; }
.orch-bvq-field input:focus, .orch-bvq-field select:focus, .orch-bvq-field textarea:focus { outline: none; border-color: #7C3AED; box-shadow: 0 0 0 3px rgba(124,58,237,0.1); }
.orch-bvq-field small { display: block; margin-top: 5px; font-size: 11px; color: #6b7280; line-height: 1.4; }
.orch-bvq-drawer-foot { padding: 16px 26px; border-top: 1px solid #e5e7eb; background: #fafafa; display: flex; flex-direction: column; gap: 10px; }
.orch-bvq-drawer-actions { display: flex; gap: 10px; justify-content: flex-end; }
.orch-bvq-drawer-status:empty { display: none; }
.orch-bvq-drawer-status .ok { padding: 8px 12px; background: #d1fae5; color: #065f46; border-radius: 6px; font-size: 12px; }
.orch-bvq-drawer-status .err { padding: 8px 12px; background: #fee2e2; color: #991b1b; border-radius: 6px; font-size: 12px; }
.orch-bvq-drawer-status .info { padding: 8px 12px; background: #dbeafe; color: #1e40af; border-radius: 6px; font-size: 12px; }
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

<?php if ($seo_aeo_bv_active): ?>
<?php ob_start(); ?>
jQuery(function($) {
    var ajaxurl = window.ajaxurl || '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
    var nonce = (window.seoAeoOrchestra && window.seoAeoOrchestra.nonce) || '<?php echo esc_js(wp_create_nonce('seo_aeo_orchestra_nonce')); ?>';
    var profileId = <?php echo (int) $seo_aeo_bv_active['_id']; ?>;

    $('.orch-bvq-edit-btn').on('click', function() {
        $('#orch-bvq-backdrop').addClass('open');
    });
    $('#orch-bvq-drawer-close, #orch-bvq-cancel').on('click', function() {
        $('#orch-bvq-backdrop').removeClass('open');
    });
    $('#orch-bvq-backdrop').on('click', function(e) {
        if (e.target === this) $(this).removeClass('open');
    });

    $('#orch-bvq-save').on('click', function() {
        var $btn = $(this);
        var $status = $('#orch-bvq-drawer-status');
        var payload = { action: 'seo_aeo_orchestra_brand_voice_update', nonce: nonce, id: profileId };
        $('[data-bvq-field]').each(function() {
            payload[$(this).data('bvq-field')] = $(this).val();
        });
        $btn.prop('disabled', true).text('Salvo…');
        $status.html('<div class="info">Aggiorno profilo…</div>');
        $.post(ajaxurl, payload).done(function(resp) {
            $btn.prop('disabled', false).text('💾 Salva e applica');
            if (!resp || !resp.success) {
                $status.html('<div class="err">❌ ' + (resp && resp.error ? resp.error : 'Errore') + '</div>');
                return;
            }
            $status.html('<div class="ok">✅ Salvato. Applicato alle prossime generazioni di questa pagina.</div>');
            // Aggiorna il pill col nuovo tono se cambiato
            var newTone = payload.tone || '';
            if (newTone) {
                $('.orch-bvq-pill-tone').text(newTone.charAt(0).toUpperCase() + newTone.slice(1));
            }
            setTimeout(function() {
                $('#orch-bvq-backdrop').removeClass('open');
                $status.empty();
            }, 1400);
        }).fail(function(xhr) {
            $btn.prop('disabled', false).text('💾 Salva e applica');
            $status.html('<div class="err">❌ Errore rete (HTTP ' + xhr.status + ')</div>');
        });
    });
});
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>
<?php endif; ?>

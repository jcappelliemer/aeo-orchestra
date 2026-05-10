<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.VariableNotPrefixed
// Reason: template scope. Variables are local to this include/template,
// passed by the calling function via include/require. The Plugin Check
// heuristic doesn't distinguish template-scope locals from globals.
/*
 * Template: Brand Voice Learning — Premium UX (v3.31.4)
 * Esperienza riprogettata: hero visuale, wizard analyze, profilo come "carta d'identità",
 * profili in card grid invece di table.
 * 3.31.4: i18n completo profile detail labels (Tono/Audience/Lunghezza frasi/Vocabolario/Anti-cliché),
 *         cost bump 25 crediti propagato anche in JS confirms.
 */
if (!defined('ABSPATH')) exit;

$active = SEO_AEO_Brand_Voice::get_active_profile();
$profiles = SEO_AEO_Brand_Voice::list_profiles();
?>
<div class="wrap orchestra-v3 orch-bv2-page">

    <!-- HERO -->
    <div class="orch-bv2-hero">
        <div class="orch-bv2-hero-bg"></div>
        <div class="orch-bv2-hero-content">
            <div class="orch-bv2-hero-icon">🎙️</div>
            <div>
                <div class="orch-bv2-hero-tagline"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('AEO Orchestra · AI Style Engine')) : 'AEO Orchestra · AI Style Engine'; ?></div>
                <h1 class="orch-bv2-hero-title"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('La tua voce. Insegnata all\'AI.')) : 'La tua voce. Insegnata all\'AI.'; ?></h1>
                <p class="orch-bv2-hero-sub"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Smetti di pubblicare contenuti che "sembrano AI". Orchestra analizza il tuo stile dai tuoi articoli pubblicati e lo applica a ogni generazione AI futura. Articoli, meta, contenuti AEO — tutto con la tua voce.')) : 'Smetti di pubblicare contenuti che "sembrano AI". Orchestra analizza il tuo stile dai tuoi articoli pubblicati e lo applica a ogni generazione AI futura. Articoli, meta, contenuti AEO — tutto con la tua voce.'; ?></p>
            </div>
        </div>
    </div>

    <!-- ACTIVE PROFILE BANNER (se c'è) -->
    <?php if ($active): ?>
    <div class="orch-bv2-active-card">
        <div class="orch-bv2-active-pulse"></div>
        <div class="orch-bv2-active-icon">✓</div>
        <div class="orch-bv2-active-text">
            <div class="orch-bv2-active-label"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('PROFILO ATTIVO')) : 'PROFILO ATTIVO'; ?></div>
            <div class="orch-bv2-active-name"><?php echo esc_html($active['_name']); ?></div>
            <div class="orch-bv2-active-meta">
                <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Tono')) : 'Tono'; ?>: <strong><?php echo esc_html($active['tone'] ?? '?'); ?></strong> ·
                <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Audience')) : 'Audience'; ?>: <strong><?php echo esc_html(mb_substr($active['audience'] ?? '?', 0, 60)); ?></strong>
            </div>
        </div>
        <button type="button" class="orch-bv2-btn orch-bv2-btn-ghost orch-bv-view-btn" data-id="<?php echo (int) $active['_id']; ?>"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Vedi profilo')) : 'Vedi profilo'; ?></button>
    </div>
    <?php endif; ?>

    <!-- STEP 1: COME FUNZIONA (zero state) -->
    <?php if (empty($profiles)): ?>
    <div class="orch-bv2-howto">
        <div class="orch-bv2-howto-title"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Come funziona')) : 'Come funziona'; ?></div>
        <div class="orch-bv2-howto-grid">
            <div class="orch-bv2-howto-step">
                <div class="orch-bv2-howto-step-num">1</div>
                <div class="orch-bv2-howto-step-icon">📚</div>
                <div class="orch-bv2-howto-step-title"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Scegli che cosa analizzare')) : 'Scegli che cosa analizzare'; ?></div>
                <div class="orch-bv2-howto-step-desc"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Articoli del blog, schede prodotto, pagine servizio. Pesco automaticamente i 15 più recenti.')) : 'Articoli del blog, schede prodotto, pagine servizio. Pesco automaticamente i 15 più recenti.'; ?></div>
            </div>
            <div class="orch-bv2-howto-arrow">→</div>
            <div class="orch-bv2-howto-step">
                <div class="orch-bv2-howto-step-num">2</div>
                <div class="orch-bv2-howto-step-icon">🧠</div>
                <div class="orch-bv2-howto-step-title"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('L\'AI studia il tuo stile')) : 'L\'AI studia il tuo stile'; ?></div>
                <div class="orch-bv2-howto-step-desc"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Tono, vocabolario, lunghezza frasi, anti-cliché. Tutto estratto in 30 secondi.')) : 'Tono, vocabolario, lunghezza frasi, anti-cliché. Tutto estratto in 30 secondi.'; ?></div>
            </div>
            <div class="orch-bv2-howto-arrow">→</div>
            <div class="orch-bv2-howto-step">
                <div class="orch-bv2-howto-step-num">3</div>
                <div class="orch-bv2-howto-step-icon">✨</div>
                <div class="orch-bv2-howto-step-title"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Tutta l\'AI parla come te')) : 'Tutta l\'AI parla come te'; ?></div>
                <div class="orch-bv2-howto-step-desc"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Articoli, meta, AEO content, redirect AI. Ogni output ha la tua voce.')) : 'Articoli, meta, AEO content, redirect AI. Ogni output ha la tua voce.'; ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- WIZARD ANALYZE -->
    <div class="orch-bv2-wizard">
        <div class="orch-bv2-wizard-head">
            <div>
                <h2 class="orch-bv2-wizard-title"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('🎯 Crea nuovo profilo')) : '🎯 Crea nuovo profilo'; ?></h2>
                <p class="orch-bv2-wizard-sub"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('L\'AI analizza i tuoi contenuti per ~30s e produce un profilo dettagliato. Potrai personalizzarlo dopo (tono, vocabolario, anti-cliché). ')) : 'L\'AI analizza i tuoi contenuti per ~30s e produce un profilo dettagliato. Potrai personalizzarlo dopo (tono, vocabolario, anti-cliché). '; ?><span class="orch-bv2-cost-pill"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('25 crediti')) : '25 crediti'; ?></span></p>
            </div>
        </div>

        <div class="orch-bv2-wizard-form">
            <div class="orch-bv2-field">
                <label><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Nome del profilo')) : 'Nome del profilo'; ?></label>
                <input type="text" id="orch-bv-name" placeholder="<?php echo esc_attr(SEO_AEO_T::t('es. Stile editoriale principale, Tono ufficio commerciale, ...')); ?>" class="orch-bv2-input">
                <small><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Dagli un nome chiaro: utile se hai più clienti o brand diversi.')) : 'Dagli un nome chiaro: utile se hai più clienti o brand diversi.'; ?></small>
            </div>

            <div class="orch-bv2-field">
                <label><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Da quali contenuti imparo?')) : 'Da quali contenuti imparo?'; ?></label>
                <select id="orch-bv-post-type" class="orch-bv2-input">
                    <option value="post" selected>📝 Articoli del blog (Post)</option>
                    <?php
                    $cpts = get_post_types(array('public' => true, '_builtin' => false), 'objects');
                    foreach ($cpts as $cpt) {
                        echo '<option value="' . esc_attr($cpt->name) . '">📄 ' . esc_html($cpt->labels->name) . '</option>';
                    }
                    ?>
                    <option value="page">📃 Pagine</option>
                </select>
                <small><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Pesco automaticamente i 15 più recenti con almeno 200 caratteri di testo.')) : 'Pesco automaticamente i 15 più recenti con almeno 200 caratteri di testo.'; ?></small>
            </div>

            <button type="button" id="orch-bv-analyze-btn" class="orch-bv2-btn orch-bv2-btn-primary orch-bv2-btn-cta">
                <span class="orch-bv2-btn-icon">🚀</span>
                <span><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Avvia analisi AI')) : 'Avvia analisi AI'; ?></span>
                <span class="orch-bv2-btn-cost"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('25 crediti')) : '25 crediti'; ?></span>
            </button>
        </div>

        <!-- Loading sequence -->
        <div id="orch-bv-loading" class="orch-bv2-loading" style="display:none;">
            <div class="orch-bv2-loading-progress"></div>
            <div class="orch-bv2-loading-steps">
                <div class="orch-bv2-loading-step" data-step="fetch">
                    <div class="orch-bv2-loading-step-icon">📚</div>
                    <div class="orch-bv2-loading-step-text"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Raccolgo articoli pubblicati…')) : 'Raccolgo articoli pubblicati…'; ?></div>
                </div>
                <div class="orch-bv2-loading-step" data-step="analyze">
                    <div class="orch-bv2-loading-step-icon">🧠</div>
                    <div class="orch-bv2-loading-step-text"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('AI estrae tono, vocabolario, struttura…')) : 'AI estrae tono, vocabolario, struttura…'; ?></div>
                </div>
                <div class="orch-bv2-loading-step" data-step="profile">
                    <div class="orch-bv2-loading-step-icon">🎙️</div>
                    <div class="orch-bv2-loading-step-text"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Costruisco il profilo della tua voce…')) : 'Costruisco il profilo della tua voce…'; ?></div>
                </div>
                <div class="orch-bv2-loading-step" data-step="save">
                    <div class="orch-bv2-loading-step-icon">💾</div>
                    <div class="orch-bv2-loading-step-text"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Salvo nel database…')) : 'Salvo nel database…'; ?></div>
                </div>
            </div>
        </div>

        <div id="orch-bv-result" style="margin-top:18px;"></div>
    </div>

    <!-- LISTA PROFILI -->
    <?php if (!empty($profiles)): ?>
    <div class="orch-bv2-profiles">
        <div class="orch-bv2-profiles-head">
            <h2><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('📚 I tuoi profili')) : '📚 I tuoi profili'; ?></h2>
            <div class="orch3-muted"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Puoi creare più profili (es. uno per blog, uno per pagine prodotto) e attivare quello che ti serve. Solo il profilo attivo viene applicato alle generazioni AI.')) : 'Puoi creare più profili (es. uno per blog, uno per pagine prodotto) e attivare quello che ti serve. Solo il profilo attivo viene applicato alle generazioni AI.'; ?></div>
        </div>

        <div class="orch-bv2-profiles-grid">
            <?php foreach ($profiles as $p):
                $profile_data = json_decode($p['profile_json'] ?? '{}', true);
                $tone = $profile_data['tone'] ?? '?';
                $audience = mb_substr($profile_data['audience'] ?? '?', 0, 80);
                $tone_class = 'tone-' . preg_replace('/[^a-z]/', '', strtolower($tone));
            ?>
            <div class="orch-bv2-profile-card <?php echo $p['is_active'] ? 'is-active' : ''; ?> <?php echo esc_attr($tone_class); ?>" data-id="<?php echo (int) $p['id']; ?>">
                <?php if ($p['is_active']): ?>
                <div class="orch-bv2-profile-active-badge"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('✓ ATTIVO')) : '✓ ATTIVO'; ?></div>
                <?php endif; ?>

                <div class="orch-bv2-profile-tone-badge"><?php echo esc_html($tone); ?></div>
                <h3 class="orch-bv2-profile-name"><?php echo esc_html($p['name']); ?></h3>
                <div class="orch-bv2-profile-meta">
                    <span>📚 <?php echo (int) $p['articles_count']; ?> <?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('articoli')) : 'articoli'; ?></span>
                    <span>🕒 <?php echo esc_html(gmdate('d/m/Y', strtotime($p['updated_at']))); ?></span>
                </div>
                <div class="orch-bv2-profile-audience">
                    <strong><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('Audience:')) : 'Audience:'; ?></strong> <?php echo esc_html($audience); ?>
                </div>

                <div class="orch-bv2-profile-actions">
                    <button class="orch-bv2-btn orch-bv2-btn-ghost orch-bv2-btn-sm orch-bv-view-btn" data-id="<?php echo (int) $p['id']; ?>"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('👁 Vedi')) : '👁 Vedi'; ?></button>
                    <?php if (!$p['is_active']): ?>
                        <button class="orch-bv2-btn orch-bv2-btn-primary orch-bv2-btn-sm orch-bv-activate-btn" data-id="<?php echo (int) $p['id']; ?>"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('✓ Attiva')) : '✓ Attiva'; ?></button>
                    <?php else: ?>
                        <button class="orch-bv2-btn orch-bv2-btn-ghost orch-bv2-btn-sm orch-bv-deactivate-btn" data-id="<?php echo (int) $p['id']; ?>"><?php echo class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t('○ Disattiva')) : '○ Disattiva'; ?></button>
                    <?php endif; ?>
                    <button class="orch-bv2-btn orch-bv2-btn-danger orch-bv2-btn-sm orch-bv-delete-btn" data-id="<?php echo (int) $p['id']; ?>">🗑</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php ob_start(); ?>
.orch-bv2-page { max-width: 1200px; --bv-purple: #7C3AED; --bv-pink: #EC4899; --bv-amber: #F59E0B; --bv-cyan: #06B6D4; }

/* HERO */
.orch-bv2-hero { position: relative; padding: 36px 38px; margin: 16px 0 22px; border-radius: 16px; color: #fff; overflow: hidden; box-shadow: 0 8px 40px rgba(124,58,237,0.25); }
.orch-bv2-hero-bg { position: absolute; inset: 0; background: linear-gradient(135deg, #1a0b2e 0%, #7C3AED 25%, #EC4899 60%, #F59E0B 100%); }
.orch-bv2-hero-bg::before { content:''; position:absolute; top:-50%; right:-20%; width:60%; height:200%; background: radial-gradient(circle, rgba(255,255,255,0.18), transparent 60%); }
.orch-bv2-hero-bg::after { content:''; position:absolute; bottom:-30%; left:-10%; width:50%; height:150%; background: radial-gradient(circle, rgba(6,182,212,0.3), transparent 60%); }
.orch-bv2-hero-content { position: relative; z-index: 2; display: flex; gap: 24px; align-items: center; }
.orch-bv2-hero-icon { font-size: 56px; width: 90px; height: 90px; flex-shrink:0; background: rgba(255,255,255,0.18); backdrop-filter: blur(10px); border-radius: 22px; display:flex; align-items:center; justify-content:center; box-shadow: 0 4px 16px rgba(0,0,0,0.2); }
.orch-bv2-hero-tagline { font-size: 11px; letter-spacing: 2.5px; text-transform: uppercase; opacity: 0.85; font-weight: 600; }
.orch-bv2-hero-title { color: #fff; font-size: 32px; font-weight: 800; margin: 6px 0 10px; line-height: 1.1; letter-spacing: -0.5px; }
.orch-bv2-hero-sub { font-size: 15px; line-height: 1.6; margin: 0; opacity: 0.95; max-width: 720px; }
.orch-bv2-hero-sub em { color: #fff; font-style: italic; opacity: 0.85; }

/* ACTIVE BANNER */
.orch-bv2-active-card { display: flex; gap: 18px; align-items: center; padding: 18px 22px; margin-bottom: 20px; background: linear-gradient(90deg, #d1fae5 0%, #a7f3d0 100%); border: 2px solid #10b981; border-radius: 14px; position: relative; }
.orch-bv2-active-pulse { position: absolute; top: 14px; right: 14px; width: 8px; height: 8px; border-radius: 50%; background: #10b981; box-shadow: 0 0 0 0 rgba(16,185,129,0.7); animation: bv-pulse 1.5s infinite; }
@keyframes bv-pulse { 0% { box-shadow: 0 0 0 0 rgba(16,185,129,0.7); } 70% { box-shadow: 0 0 0 12px rgba(16,185,129,0); } 100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); } }
.orch-bv2-active-icon { width: 44px; height: 44px; border-radius: 50%; background: #10b981; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: bold; flex-shrink: 0; box-shadow: 0 2px 8px rgba(16,185,129,0.4); }
.orch-bv2-active-label { font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: #047857; font-weight: 700; }
.orch-bv2-active-name { font-size: 18px; font-weight: 700; color: #065f46; margin: 2px 0 4px; }
.orch-bv2-active-meta { font-size: 13px; color: #065f46; opacity: 0.85; }
.orch-bv2-active-text { flex: 1; }

/* HOW IT WORKS */
.orch-bv2-howto { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 30px; margin-bottom: 20px; }
.orch-bv2-howto-title { font-size: 13px; letter-spacing: 1.5px; text-transform: uppercase; color: #7C3AED; font-weight: 700; text-align: center; margin-bottom: 20px; }
.orch-bv2-howto-grid { display: flex; gap: 16px; align-items: stretch; justify-content: center; flex-wrap: wrap; }
.orch-bv2-howto-step { flex: 1; min-width: 200px; max-width: 280px; padding: 24px 20px; background: linear-gradient(180deg, #faf5ff 0%, #ffffff 100%); border: 1px solid #e9d5ff; border-radius: 14px; text-align: center; position: relative; transition: all 0.2s; }
.orch-bv2-howto-step:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(124,58,237,0.12); border-color: #c084fc; }
.orch-bv2-howto-step-num { position: absolute; top: -14px; left: 50%; transform: translateX(-50%); width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, #7C3AED, #EC4899); color: #fff; font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: 14px; }
.orch-bv2-howto-step-icon { font-size: 36px; margin: 8px 0 12px; }
.orch-bv2-howto-step-title { font-size: 15px; font-weight: 700; color: #0a0e27; margin-bottom: 6px; }
.orch-bv2-howto-step-desc { font-size: 13px; color: #64748b; line-height: 1.5; }
.orch-bv2-howto-arrow { font-size: 24px; color: #c084fc; align-self: center; flex-shrink: 0; }
@media (max-width: 900px) { .orch-bv2-howto-arrow { display: none; } }

/* WIZARD */
.orch-bv2-wizard { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 30px; margin-bottom: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
.orch-bv2-wizard-head { margin-bottom: 22px; }
.orch-bv2-wizard-title { font-size: 22px; font-weight: 700; color: #0a0e27; margin: 0 0 6px; }
.orch-bv2-wizard-sub { font-size: 14px; color: #64748b; margin: 0; line-height: 1.6; }
.orch-bv2-cost-pill { display: inline-block; padding: 2px 10px; background: linear-gradient(90deg, #7C3AED, #EC4899); color: #fff; border-radius: 12px; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; vertical-align: middle; }
.orch-bv2-wizard-form { display: flex; flex-direction: column; gap: 18px; }
.orch-bv2-field { display: flex; flex-direction: column; gap: 6px; }
.orch-bv2-field label { font-size: 13px; font-weight: 600; color: #0a0e27; }
.orch-bv2-field small { font-size: 12px; color: #94a3b8; }
.orch-bv2-input { padding: 12px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-family: inherit; transition: all 0.15s; box-sizing: border-box; background: #fff; }
.orch-bv2-input:focus { outline: none; border-color: #7C3AED; box-shadow: 0 0 0 3px rgba(124,58,237,0.1); }

/* BUTTONS */
.orch-bv2-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: 2px solid transparent; transition: all 0.15s; text-decoration: none; font-family: inherit; }
.orch-bv2-btn-primary { background: linear-gradient(135deg, #7C3AED, #EC4899); color: #fff; box-shadow: 0 2px 8px rgba(124,58,237,0.3); }
.orch-bv2-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(124,58,237,0.4); }
.orch-bv2-btn-ghost { background: #fff; color: #475569; border-color: #e2e8f0; }
.orch-bv2-btn-ghost:hover { background: #f8fafc; border-color: #cbd5e1; }
.orch-bv2-btn-danger { background: #fff; color: #dc2626; border-color: #fecaca; }
.orch-bv2-btn-danger:hover { background: #fef2f2; }
.orch-bv2-btn-sm { padding: 6px 12px; font-size: 12px; }
.orch-bv2-btn-cta { padding: 16px 24px; font-size: 15px; align-self: flex-start; }
.orch-bv2-btn-icon { font-size: 18px; }
.orch-bv2-btn-cost { padding: 2px 8px; background: rgba(255,255,255,0.2); border-radius: 10px; font-size: 11px; font-weight: 700; }
.orch-bv2-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none !important; }

/* LOADING */
.orch-bv2-loading { margin-top: 22px; padding: 24px; background: linear-gradient(180deg, #faf5ff, #fff); border: 2px solid #e9d5ff; border-radius: 12px; }
.orch-bv2-loading-progress { height: 4px; background: #e9d5ff; border-radius: 4px; overflow: hidden; margin-bottom: 18px; position: relative; }
.orch-bv2-loading-progress::after { content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 30%; background: linear-gradient(90deg, transparent, #7C3AED, #EC4899, transparent); animation: bv-progress 1.5s infinite; }
@keyframes bv-progress { 0% { left: -30%; } 100% { left: 100%; } }
.orch-bv2-loading-steps { display: flex; flex-direction: column; gap: 10px; }
.orch-bv2-loading-step { display: flex; gap: 12px; align-items: center; padding: 10px 14px; border-radius: 8px; transition: all 0.3s; opacity: 0.4; }
.orch-bv2-loading-step.active { opacity: 1; background: #f3e8ff; }
.orch-bv2-loading-step.done { opacity: 1; background: #d1fae5; }
.orch-bv2-loading-step-icon { font-size: 22px; width: 36px; text-align: center; }
.orch-bv2-loading-step.done .orch-bv2-loading-step-icon::after { content: ' ✓'; color: #10b981; }
.orch-bv2-loading-step-text { font-size: 13px; color: #475569; }

/* RESULT (carta d'identità) */
.orch-bv2-result-card { background: linear-gradient(135deg, #fef3c7 0%, #fce7f3 50%, #ddd6fe 100%); border-radius: 16px; padding: 0; overflow: hidden; box-shadow: 0 8px 32px rgba(124,58,237,0.18); margin-top: 18px; animation: bv-slide-in 0.4s ease-out; }
@keyframes bv-slide-in { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.orch-bv2-result-header { padding: 24px 28px; background: linear-gradient(135deg, #1a0b2e, #7C3AED); color: #fff; }
.orch-bv2-result-header-label { font-size: 11px; letter-spacing: 2px; text-transform: uppercase; opacity: 0.85; font-weight: 600; }
.orch-bv2-result-header-name { font-size: 22px; font-weight: 700; margin: 4px 0 8px; }
.orch-bv2-result-tone-display { display: inline-block; padding: 6px 14px; background: rgba(255,255,255,0.2); border-radius: 20px; font-weight: 700; font-size: 14px; backdrop-filter: blur(10px); }
.orch-bv2-result-body { padding: 24px 28px; background: rgba(255,255,255,0.9); }
.orch-bv2-result-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px; }
@media (max-width: 700px) { .orch-bv2-result-grid { grid-template-columns: 1fr; } }
.orch-bv2-result-metric { padding: 14px 16px; background: #fff; border-radius: 10px; border: 1px solid #e9d5ff; }
.orch-bv2-result-metric-label { font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: #7C3AED; font-weight: 700; margin-bottom: 4px; }
.orch-bv2-result-metric-value { font-size: 14px; color: #0a0e27; line-height: 1.5; font-weight: 500; }
.orch-bv2-result-tags-section { margin-top: 18px; padding-top: 18px; border-top: 1px solid #e9d5ff; }
.orch-bv2-result-tags-label { font-size: 12px; font-weight: 700; color: #7C3AED; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 10px; }
.orch-bv2-result-tags { display: flex; flex-wrap: wrap; gap: 6px; }
.orch-bv2-result-tag { padding: 4px 10px; background: linear-gradient(90deg, #f3e8ff, #fce7f3); color: #7C3AED; border-radius: 12px; font-size: 12px; font-weight: 500; border: 1px solid rgba(124,58,237,0.2); }
.orch-bv2-result-tag.avoid { background: linear-gradient(90deg, #fee2e2, #fecaca); color: #b91c1c; border-color: rgba(220,38,38,0.2); }
.orch-bv2-result-summary { margin-top: 18px; padding: 16px 18px; background: #faf5ff; border-left: 4px solid #7C3AED; border-radius: 6px; font-size: 14px; line-height: 1.6; color: #4c1d95; font-style: italic; }
.orch-bv2-result-customize-hint { margin: 18px 28px 0; padding: 14px 16px; background: linear-gradient(90deg, #fef3c7, #fde68a); border-left: 4px solid #d97706; border-radius: 8px; display: flex; gap: 12px; align-items: flex-start; font-size: 13px; line-height: 1.55; color: #78350f; }
.orch-bv2-result-customize-icon { font-size: 22px; flex-shrink: 0; line-height: 1; }
.orch-bv2-result-customize-hint em { background: rgba(217,119,6,0.15); padding: 1px 6px; border-radius: 4px; font-style: normal; font-weight: 600; }
.orch-bv2-result-cta { padding: 20px 28px; background: linear-gradient(90deg, #f3e8ff, #fce7f3); display: flex; gap: 12px; justify-content: space-between; align-items: center; flex-wrap: wrap; }
.orch-bv2-result-cta-msg { flex: 1; min-width: 220px; font-size: 13px; color: #581c87; font-weight: 500; }

/* PROFILES GRID */
.orch-bv2-profiles { margin-top: 28px; }
.orch-bv2-profiles-head { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 8px; }
.orch-bv2-profiles-head h2 { font-size: 20px; font-weight: 700; margin: 0; }
.orch-bv2-profiles-head .orch3-muted { font-size: 12px; color: #94a3b8; }
.orch-bv2-profiles-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
.orch-bv2-profile-card { position: relative; background: #fff; border: 2px solid #e2e8f0; border-radius: 14px; padding: 22px 22px 18px; transition: all 0.2s; overflow: hidden; }
.orch-bv2-profile-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #7C3AED, #EC4899); }
.orch-bv2-profile-card.is-active { border-color: #10b981; }
.orch-bv2-profile-card.is-active::before { background: linear-gradient(90deg, #10b981, #06B6D4); }
.orch-bv2-profile-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
.orch-bv2-profile-active-badge { position: absolute; top: 12px; right: 12px; padding: 3px 10px; background: #10b981; color: #fff; border-radius: 10px; font-size: 10px; font-weight: 700; letter-spacing: 0.5px; }
.orch-bv2-profile-tone-badge { display: inline-block; padding: 4px 12px; background: linear-gradient(90deg, #7C3AED, #EC4899); color: #fff; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
.orch-bv2-profile-name { font-size: 17px; font-weight: 700; color: #0a0e27; margin: 0 0 8px; line-height: 1.3; }
.orch-bv2-profile-meta { display: flex; gap: 10px; font-size: 12px; color: #64748b; margin-bottom: 12px; }
.orch-bv2-profile-audience { font-size: 12px; color: #475569; line-height: 1.5; padding: 10px 12px; background: #faf5ff; border-radius: 8px; margin-bottom: 14px; min-height: 56px; }
.orch-bv2-profile-actions { display: flex; gap: 6px; flex-wrap: wrap; }

/* MODAL DETAIL */
.orch-bv2-modal-backdrop { position: fixed; inset: 0; background: rgba(10,14,39,0.7); backdrop-filter: blur(4px); z-index: 100000; display: flex; align-items: center; justify-content: center; padding: 20px; }
.orch-bv2-modal { background: #fff; border-radius: 16px; max-width: 720px; width: 100%; max-height: 92vh; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 24px 64px rgba(0,0,0,0.4); }
.orch-bv2-modal-head { padding: 22px 26px; background: linear-gradient(135deg, #1a0b2e, #7C3AED, #EC4899); color: #fff; display: flex; justify-content: space-between; align-items: center; }
.orch-bv2-modal-head h3 { margin: 0; font-size: 20px; font-weight: 700; }
.orch-bv2-modal-close { background: rgba(255,255,255,0.18); border: none; color: #fff; width: 32px; height: 32px; border-radius: 50%; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.orch-bv2-modal-close:hover { background: rgba(255,255,255,0.3); }
.orch-bv2-modal-body { padding: 24px 26px; overflow: auto; flex: 1; }
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

<?php ob_start(); ?>
(function() {
    if (typeof window.ajaxurl === 'undefined') {
        window.ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
    }
    if (typeof window.seoAeoOrchestra === 'undefined') {
        window.seoAeoOrchestra = {
            ajaxUrl: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
            nonce: '<?php echo esc_js(wp_create_nonce('seo_aeo_orchestra_nonce')); ?>'
        };
    }
})();

jQuery(function($) {
    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    // i18n strings for JS-generated UI (3.31.4)
    var bvT = {
        profileCreated: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Profilo creato') : 'Profilo creato'); ?>,
        articlesAnalyzed: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('articoli analizzati') : 'articoli analizzati'); ?>,
        creditsLabel: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('crediti') : 'crediti'); ?>,
        profileFallback: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Profilo') : 'Profilo'); ?>,
        tone: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Tono') : 'Tono'); ?>,
        audience: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Audience') : 'Audience'); ?>,
        sentenceLength: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Lunghezza frasi') : 'Lunghezza frasi'); ?>,
        paragraphLength: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Lunghezza paragrafi') : 'Lunghezza paragrafi'); ?>,
        typicalStructure: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Struttura tipica') : 'Struttura tipica'); ?>,
        ctaStyle: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Stile CTA') : 'Stile CTA'); ?>,
        vocabSection: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('🎨 Vocabolario distintivo') : '🎨 Vocabolario distintivo'); ?>,
        avoidSection: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('🚫 Anti-cliché (da evitare)') : '🚫 Anti-cliché (da evitare)'); ?>,
        customizableStrong: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Puoi personalizzarlo in qualsiasi momento.') : 'Puoi personalizzarlo in qualsiasi momento.'); ?>,
        customizableHint: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Trovi questo profilo nella lista qui sotto: clic su {view} e poi su {edit} per cambiare tono, vocabolario distintivo, anti-cliché o summary.') : 'Trovi questo profilo nella lista qui sotto: clic su {view} e poi su {edit} per cambiare tono, vocabolario distintivo, anti-cliché o summary.'); ?>,
        viewLabelEm: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('👁 Vedi') : '👁 Vedi'); ?>,
        editLabelEm: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('✏️ Modifica') : '✏️ Modifica'); ?>,
        profileSavedCta: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('✨ Profilo salvato. Attivalo per usarlo in tutte le generazioni AI.') : '✨ Profilo salvato. Attivalo per usarlo in tutte le generazioni AI.'); ?>,
        customize: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('✏️ Personalizza') : '✏️ Personalizza'); ?>,
        activateNow: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('✓ Attiva ora') : '✓ Attiva ora'); ?>,
        confirmAnalyze: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Analisi: 25 crediti. Procedere?') : 'Analisi: 25 crediti. Procedere?'); ?>,
        confirmDelete: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Eliminare il profilo? Operazione irreversibile.') : 'Eliminare il profilo? Operazione irreversibile.'); ?>,
        unknownError: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('errore sconosciuto') : 'errore sconosciuto'); ?>,
        networkError: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Errore rete (HTTP') : 'Errore rete (HTTP'); ?>,
        genericError: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Errore') : 'Errore'); ?>,
        // Edit form
        profileName: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Nome profilo') : 'Nome profilo'); ?>,
        toneDescription: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Descrizione tono') : 'Descrizione tono'); ?>,
        audienceTarget: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Audience target') : 'Audience target'); ?>,
        articleStructure: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Struttura tipica articolo') : 'Struttura tipica articolo'); ?>,
        vocabFieldLabel: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('🎨 Vocabolario distintivo (separato da virgole)') : '🎨 Vocabolario distintivo (separato da virgole)'); ?>,
        vocabHint: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Massimo 30 elementi. Sono le parole che il tuo brand usa di frequente.') : 'Massimo 30 elementi. Sono le parole che il tuo brand usa di frequente.'); ?>,
        avoidFieldLabel: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('🚫 Anti-cliché — parole/frasi da evitare (separate da virgole)') : '🚫 Anti-cliché — parole/frasi da evitare (separate da virgole)'); ?>,
        avoidHint: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Massimo 20 elementi. Es: in conclusione, nel mondo digitale, ecc.') : 'Massimo 20 elementi. Es: in conclusione, nel mondo digitale, ecc.'); ?>,
        summaryFieldLabel: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('📝 Summary per prompt AI (la stringa che viene inserita come system message)') : '📝 Summary per prompt AI (la stringa che viene inserita come system message)'); ?>,
        summaryHint: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Questo è il testo che viene aggiunto al prompt di OGNI generazione AI. Riassumi tono, stile e brand voice in modo accionabile.') : 'Questo è il testo che viene aggiunto al prompt di OGNI generazione AI. Riassumi tono, stile e brand voice in modo accionabile.'); ?>,
        cancel: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Annulla') : 'Annulla'); ?>,
        saveChanges: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('💾 Salva modifiche') : '💾 Salva modifiche'); ?>,
        saving: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Salvo…') : 'Salvo…'); ?>,
        savingProfile: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('Aggiorno profilo…') : 'Aggiorno profilo…'); ?>,
        saved: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('✅ Salvato! Aggiorno la vista…') : '✅ Salvato! Aggiorno la vista…'); ?>,
        viewMode: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('👁 Vista') : '👁 Vista'); ?>,
        editMode: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::t('✏️ Modifica') : '✏️ Modifica'); ?>,
        profileLocaleTag: <?php echo wp_json_encode(class_exists('SEO_AEO_T') ? SEO_AEO_T::current_locale() : 'it'); ?>
    };

    // Helper: substitute placeholders {view} {edit} with HTML <em> labels
    function bvFormatHint(template, viewLabel, editLabel) {
        return template
            .replace('{view}', '<em>' + escapeHtml(viewLabel) + '</em>')
            .replace('{edit}', '<em>' + escapeHtml(editLabel) + '</em>');
    }

    function setLoadingStep(step) {
        var order = ['fetch', 'analyze', 'profile', 'save'];
        $('.orch-bv2-loading-step').removeClass('active done');
        var idx = order.indexOf(step);
        if (idx === -1) return;
        for (var i = 0; i < idx; i++) {
            $('.orch-bv2-loading-step[data-step="' + order[i] + '"]').addClass('done');
        }
        $('.orch-bv2-loading-step[data-step="' + step + '"]').addClass('active');
    }

    function renderResult(data) {
        var p = data.profile || {};
        var vocab = (p.distinctive_vocabulary || []).map(function(w) {
            return '<span class="orch-bv2-result-tag">' + escapeHtml(w) + '</span>';
        }).join('');
        var avoid = (p.avoid_words || []).map(function(w) {
            return '<span class="orch-bv2-result-tag avoid">' + escapeHtml(w) + '</span>';
        }).join('');

        return '<div class="orch-bv2-result-card">' +
            '<div class="orch-bv2-result-header">' +
                '<div class="orch-bv2-result-header-label">' + escapeHtml(bvT.profileCreated) + ' · ' + parseInt(data.articles_analyzed,10) + ' ' + escapeHtml(bvT.articlesAnalyzed) + ' · ' + parseInt(data.credits_consumed,10) + ' ' + escapeHtml(bvT.creditsLabel) + '</div>' +
                '<div class="orch-bv2-result-header-name">' + escapeHtml(p.name || bvT.profileFallback) + '</div>' +
                '<div class="orch-bv2-result-tone-display">🎙️ ' + escapeHtml(p.tone || '?') + '</div>' +
            '</div>' +
            '<div class="orch-bv2-result-body">' +
                '<div class="orch-bv2-result-grid">' +
                    '<div class="orch-bv2-result-metric"><div class="orch-bv2-result-metric-label">' + escapeHtml(bvT.tone) + '</div><div class="orch-bv2-result-metric-value">' + escapeHtml(p.tone_description || '?') + '</div></div>' +
                    '<div class="orch-bv2-result-metric"><div class="orch-bv2-result-metric-label">' + escapeHtml(bvT.audience) + '</div><div class="orch-bv2-result-metric-value">' + escapeHtml(p.audience || '?') + '</div></div>' +
                    '<div class="orch-bv2-result-metric"><div class="orch-bv2-result-metric-label">' + escapeHtml(bvT.sentenceLength) + '</div><div class="orch-bv2-result-metric-value">' + escapeHtml(p.avg_sentence_length || '?') + '</div></div>' +
                    '<div class="orch-bv2-result-metric"><div class="orch-bv2-result-metric-label">' + escapeHtml(bvT.paragraphLength) + '</div><div class="orch-bv2-result-metric-value">' + escapeHtml(p.avg_paragraph_length || '?') + '</div></div>' +
                    '<div class="orch-bv2-result-metric" style="grid-column:1/-1;"><div class="orch-bv2-result-metric-label">' + escapeHtml(bvT.typicalStructure) + '</div><div class="orch-bv2-result-metric-value">' + escapeHtml(p.typical_structure || '?') + '</div></div>' +
                    '<div class="orch-bv2-result-metric" style="grid-column:1/-1;"><div class="orch-bv2-result-metric-label">' + escapeHtml(bvT.ctaStyle) + '</div><div class="orch-bv2-result-metric-value">' + escapeHtml(p.cta_style || '?') + '</div></div>' +
                '</div>' +
                (vocab ? '<div class="orch-bv2-result-tags-section"><div class="orch-bv2-result-tags-label">' + escapeHtml(bvT.vocabSection) + '</div><div class="orch-bv2-result-tags">' + vocab + '</div></div>' : '') +
                (avoid ? '<div class="orch-bv2-result-tags-section"><div class="orch-bv2-result-tags-label">' + escapeHtml(bvT.avoidSection) + '</div><div class="orch-bv2-result-tags">' + avoid + '</div></div>' : '') +
                (p.summary_for_prompt ? '<div class="orch-bv2-result-summary">"' + escapeHtml(p.summary_for_prompt) + '"</div>' : '') +
            '</div>' +
            '<div class="orch-bv2-result-customize-hint">' +
                '<span class="orch-bv2-result-customize-icon">✏️</span>' +
                '<div>' +
                    '<strong>' + escapeHtml(bvT.customizableStrong) + '</strong> ' +
                    bvFormatHint(bvT.customizableHint, bvT.viewLabelEm, bvT.editLabelEm) +
                '</div>' +
            '</div>' +
            '<div class="orch-bv2-result-cta">' +
                '<div class="orch-bv2-result-cta-msg">' + escapeHtml(bvT.profileSavedCta) + '</div>' +
                '<div style="display:flex; gap:10px; flex-wrap:wrap;">' +
                    '<button type="button" class="orch-bv2-btn orch-bv2-btn-ghost orch-bv-view-btn" data-id="' + parseInt(data.profile_id,10) + '">' + escapeHtml(bvT.customize) + '</button>' +
                    '<button type="button" class="orch-bv2-btn orch-bv2-btn-primary orch-bv-activate-btn" data-id="' + parseInt(data.profile_id,10) + '">' + escapeHtml(bvT.activateNow) + '</button>' +
                '</div>' +
            '</div>' +
        '</div>';
    }

    // ANALYZE
    $('#orch-bv-analyze-btn').on('click', function() {
        var $btn = $(this);
        var localeMap = { it: 'it-IT', en: 'en-US', es: 'es-ES', fr: 'fr-FR', de: 'de-DE' };
        var dateLocale = localeMap[bvT.profileLocaleTag] || 'it-IT';
        var name = $('#orch-bv-name').val().trim() || bvT.profileFallback + ' ' + new Date().toLocaleDateString(dateLocale);
        var postType = $('#orch-bv-post-type').val() || 'post';

        if (!confirm(bvT.confirmAnalyze)) return;

        $btn.prop('disabled', true);
        $('#orch-bv-result').empty();
        $('#orch-bv-loading').slideDown(200);

        // Step animation
        setLoadingStep('fetch');
        setTimeout(function() { setLoadingStep('analyze'); }, 1500);

        $.post(ajaxurl, {
            action: 'seo_aeo_orchestra_brand_voice_analyze',
            nonce: seoAeoOrchestra.nonce,
            profile_name: name,
            post_type: postType
        }).done(function(resp) {
            setLoadingStep('save');
            setTimeout(function() {
                $('#orch-bv-loading').slideUp(200);
                $btn.prop('disabled', false);

                if (!resp || !resp.success) {
                    var err = (resp && (resp.error || resp.message)) || bvT.unknownError;
                    $('#orch-bv-result').html('<div style="padding:14px 16px;background:#fee2e2;border-left:3px solid #dc2626;color:#991b1b;border-radius:8px;">❌ ' + escapeHtml(err) + '</div>');
                    return;
                }
                $('#orch-bv-result').html(renderResult(resp));
                // Scroll fluido al risultato
                $('html, body').animate({ scrollTop: $('#orch-bv-result').offset().top - 60 }, 400);
            }, 600);
        }).fail(function(xhr) {
            $('#orch-bv-loading').slideUp(200);
            $btn.prop('disabled', false);
            $('#orch-bv-result').html('<div style="padding:14px 16px;background:#fee2e2;border-left:3px solid #dc2626;color:#991b1b;border-radius:8px;">❌ ' + escapeHtml(bvT.networkError) + ' ' + xhr.status + ')</div>');
        });
    });

    // ACTIVATE/DEACTIVATE/DELETE
    $(document).on('click', '.orch-bv-activate-btn', function() {
        var id = $(this).data('id');
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_brand_voice_activate', nonce: seoAeoOrchestra.nonce, id: id })
         .done(function(resp) { if (resp && resp.success) location.reload(); else alert(bvT.genericError); });
    });
    $(document).on('click', '.orch-bv-deactivate-btn', function() {
        var id = $(this).data('id');
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_brand_voice_deactivate', nonce: seoAeoOrchestra.nonce, id: id })
         .done(function(resp) { if (resp && resp.success) location.reload(); else alert(bvT.genericError); });
    });
    $(document).on('click', '.orch-bv-delete-btn', function() {
        var id = $(this).data('id');
        if (!confirm(bvT.confirmDelete)) return;
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_brand_voice_delete', nonce: seoAeoOrchestra.nonce, id: id })
         .done(function(resp) { if (resp && resp.success) location.reload(); else alert(bvT.genericError); });
    });

    // VIEW DETAIL MODAL — display + edit toggle
    function renderEditForm(id, name, p) {
        var vocabCsv = (p.distinctive_vocabulary || []).join(', ');
        var avoidCsv = (p.avoid_words || []).join(', ');
        return '<div class="orch-bv2-edit-form" data-id="' + parseInt(id, 10) + '">' +
            '<div class="orch-bv2-edit-grid">' +
                '<div class="orch-bv2-field">' +
                    '<label>' + escapeHtml(bvT.profileName) + '</label>' +
                    '<input type="text" data-bv-field="name" value="' + escapeHtml(name) + '" class="orch-bv2-input">' +
                '</div>' +
                '<div class="orch-bv2-field">' +
                    '<label>' + escapeHtml(bvT.tone) + '</label>' +
                    '<select data-bv-field="tone" class="orch-bv2-input">' +
                        ['professionale', 'formale', 'casual', 'amichevole', 'tecnico', 'narrativo', 'ibrido', 'autorevole', 'consulenziale', 'commerciale'].map(function(t) {
                            return '<option value="' + t + '"' + ((p.tone || '').toLowerCase() === t ? ' selected' : '') + '>' + t.charAt(0).toUpperCase() + t.slice(1) + '</option>';
                        }).join('') +
                    '</select>' +
                '</div>' +
            '</div>' +
            '<div class="orch-bv2-field">' +
                '<label>' + escapeHtml(bvT.toneDescription) + '</label>' +
                '<textarea data-bv-field="tone_description" rows="2" class="orch-bv2-input">' + escapeHtml(p.tone_description || '') + '</textarea>' +
            '</div>' +
            '<div class="orch-bv2-edit-grid">' +
                '<div class="orch-bv2-field">' +
                    '<label>' + escapeHtml(bvT.sentenceLength) + '</label>' +
                    '<select data-bv-field="avg_sentence_length" class="orch-bv2-input">' +
                        ['corte', 'medie', 'lunghe', 'miste'].map(function(t) {
                            return '<option value="' + t + '"' + ((p.avg_sentence_length || '').toLowerCase() === t ? ' selected' : '') + '>' + t + '</option>';
                        }).join('') +
                    '</select>' +
                '</div>' +
                '<div class="orch-bv2-field">' +
                    '<label>' + escapeHtml(bvT.paragraphLength) + '</label>' +
                    '<input type="text" data-bv-field="avg_paragraph_length" value="' + escapeHtml(p.avg_paragraph_length || '') + '" class="orch-bv2-input">' +
                '</div>' +
            '</div>' +
            '<div class="orch-bv2-field">' +
                '<label>' + escapeHtml(bvT.audienceTarget) + '</label>' +
                '<textarea data-bv-field="audience" rows="2" class="orch-bv2-input">' + escapeHtml(p.audience || '') + '</textarea>' +
            '</div>' +
            '<div class="orch-bv2-field">' +
                '<label>' + escapeHtml(bvT.articleStructure) + '</label>' +
                '<textarea data-bv-field="typical_structure" rows="3" class="orch-bv2-input">' + escapeHtml(p.typical_structure || '') + '</textarea>' +
            '</div>' +
            '<div class="orch-bv2-field">' +
                '<label>' + escapeHtml(bvT.ctaStyle) + '</label>' +
                '<textarea data-bv-field="cta_style" rows="2" class="orch-bv2-input">' + escapeHtml(p.cta_style || '') + '</textarea>' +
            '</div>' +
            '<div class="orch-bv2-field">' +
                '<label>' + escapeHtml(bvT.vocabFieldLabel) + '</label>' +
                '<textarea data-bv-field="distinctive_vocabulary" rows="3" class="orch-bv2-input" placeholder="<?php echo esc_js(SEO_AEO_T::t('parola1, parola2, frase chiave 3, ...')); ?>">' + escapeHtml(vocabCsv) + '</textarea>' +
                '<small>' + escapeHtml(bvT.vocabHint) + '</small>' +
            '</div>' +
            '<div class="orch-bv2-field">' +
                '<label>' + escapeHtml(bvT.avoidFieldLabel) + '</label>' +
                '<textarea data-bv-field="avoid_words" rows="2" class="orch-bv2-input" placeholder="<?php echo esc_js(SEO_AEO_T::t('cliché 1, cliché 2, ...')); ?>">' + escapeHtml(avoidCsv) + '</textarea>' +
                '<small>' + escapeHtml(bvT.avoidHint) + '</small>' +
            '</div>' +
            '<div class="orch-bv2-field">' +
                '<label>' + escapeHtml(bvT.summaryFieldLabel) + '</label>' +
                '<textarea data-bv-field="summary_for_prompt" rows="6" class="orch-bv2-input">' + escapeHtml(p.summary_for_prompt || '') + '</textarea>' +
                '<small>' + escapeHtml(bvT.summaryHint) + '</small>' +
            '</div>' +
            '<div class="orch-bv2-edit-actions">' +
                '<button type="button" class="orch-bv2-btn orch-bv2-btn-ghost orch-bv2-cancel-edit">' + escapeHtml(bvT.cancel) + '</button>' +
                '<button type="button" class="orch-bv2-btn orch-bv2-btn-primary orch-bv2-save-edit">' + escapeHtml(bvT.saveChanges) + '</button>' +
            '</div>' +
            '<div class="orch-bv2-edit-status"></div>' +
        '</div>';
    }

    function openProfileModal(id, profile_full, mode) {
        $('#orch-bv2-modal-x').remove();
        var p = profile_full.profile || {};
        var name = profile_full.name || 'Profilo';
        var data = {
            profile_id: id,
            profile: Object.assign({ name: name }, p),
            articles_analyzed: profile_full.articles_count || 0,
            credits_consumed: 0
        };
        var bodyContent = mode === 'edit' ? renderEditForm(id, name, p) : renderResult(data);
        var headerActions = mode === 'edit'
            ? '<button class="orch-bv2-btn orch-bv2-btn-ghost orch-bv2-btn-sm orch-bv2-toggle-mode" data-mode="view">' + escapeHtml(bvT.viewMode) + '</button>'
            : '<button class="orch-bv2-btn orch-bv2-btn-ghost orch-bv2-btn-sm orch-bv2-toggle-mode" data-mode="edit">' + escapeHtml(bvT.editMode) + '</button>';
        var html = '<div class="orch-bv2-modal-backdrop" id="orch-bv2-modal-x">' +
            '<div class="orch-bv2-modal">' +
                '<div class="orch-bv2-modal-head">' +
                    '<h3>🎙️ ' + escapeHtml(name) + '</h3>' +
                    '<div style="display:flex;gap:8px;align-items:center;">' + headerActions + '<button class="orch-bv2-modal-close">×</button></div>' +
                '</div>' +
                '<div class="orch-bv2-modal-body">' + bodyContent + '</div>' +
            '</div>' +
        '</div>';
        $('body').append(html);

        $('.orch-bv2-modal-close').on('click', function() { $('#orch-bv2-modal-x').remove(); });
        $('.orch-bv2-modal-backdrop').on('click', function(e) { if (e.target === this) $(this).remove(); });

        // Toggle mode
        $('.orch-bv2-toggle-mode').on('click', function() {
            var newMode = $(this).data('mode');
            openProfileModal(id, profile_full, newMode);
        });

        // Cancel edit → go back to view
        $('.orch-bv2-cancel-edit').on('click', function() {
            openProfileModal(id, profile_full, 'view');
        });

        // Save edit
        $('.orch-bv2-save-edit').on('click', function() {
            var $btn = $(this);
            var $form = $btn.closest('.orch-bv2-edit-form');
            var $status = $form.find('.orch-bv2-edit-status');
            var payload = { action: 'seo_aeo_orchestra_brand_voice_update', nonce: seoAeoOrchestra.nonce, id: id };
            $form.find('[data-bv-field]').each(function() {
                payload[$(this).data('bv-field')] = $(this).val();
            });
            $btn.prop('disabled', true).text(bvT.saving);
            $status.html('<div class="orch3-muted" style="margin-top:8px;font-size:12px;">' + escapeHtml(bvT.savingProfile) + '</div>');
            $.post(ajaxurl, payload).done(function(resp) {
                $btn.prop('disabled', false).text(bvT.saveChanges);
                if (!resp || !resp.success) {
                    $status.html('<div style="margin-top:8px; padding:8px 12px; background:#fee2e2; color:#991b1b; border-radius:6px; font-size:12px;">❌ ' + escapeHtml(resp && resp.error || bvT.genericError) + '</div>');
                    return;
                }
                $status.html('<div style="margin-top:8px; padding:8px 12px; background:#d1fae5; color:#065f46; border-radius:6px; font-size:12px;">' + escapeHtml(bvT.saved) + '</div>');
                // Refresh profile_full + torna a view
                $.post(ajaxurl, { action: 'seo_aeo_orchestra_brand_voice_get', nonce: seoAeoOrchestra.nonce, id: id })
                 .done(function(r2) {
                    if (r2 && r2.success) {
                        setTimeout(function() { openProfileModal(id, r2.profile, 'view'); }, 600);
                    }
                 });
            }).fail(function(xhr) {
                $btn.prop('disabled', false).text(bvT.saveChanges);
                $status.html('<div style="margin-top:8px; padding:8px 12px; background:#fee2e2; color:#991b1b; border-radius:6px; font-size:12px;">❌ ' + escapeHtml(bvT.networkError) + ' ' + xhr.status + ')</div>');
            });
        });
    }

    $(document).on('click', '.orch-bv-view-btn', function() {
        var id = $(this).data('id');
        $.post(ajaxurl, { action: 'seo_aeo_orchestra_brand_voice_get', nonce: seoAeoOrchestra.nonce, id: id })
         .done(function(resp) {
            if (!resp || !resp.success) { alert(bvT.genericError); return; }
            openProfileModal(id, resp.profile, 'view');
         });
    });
});
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>

<?php ob_start(); ?>
/* Edit form */
.orch-bv2-edit-form { display: flex; flex-direction: column; gap: 14px; }
.orch-bv2-edit-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 600px) { .orch-bv2-edit-grid { grid-template-columns: 1fr; } }
.orch-bv2-edit-form textarea.orch-bv2-input { font-family: inherit; resize: vertical; min-height: 60px; }
.orch-bv2-edit-actions { display: flex; gap: 10px; justify-content: flex-end; padding-top: 14px; border-top: 1px solid #e9d5ff; margin-top: 6px; }
.orch-bv2-edit-status:empty { display: none; }
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

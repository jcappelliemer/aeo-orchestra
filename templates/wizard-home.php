<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.VariableNotPrefixed
// Reason: template scope. Variables are local to this include/template,
// passed by the calling function via include/require. The Plugin Check
// heuristic doesn't distinguish template-scope locals from globals.
/*
 * Template: Wizard / Home onboarding (since 3.22.0)
 * Landing iniziale del plugin: spiega cosa fa Orchestra, naviga alle feature,
 * mostra novità versione e link a guide. È la prima cosa che vede chi clicca
 * "AEO Orchestra" dal menu admin.
 */
if (!defined('ABSPATH')) exit;

$seo_aeo_license_key = get_option('seo_aeo_orchestra_license_key', '');
$seo_aeo_has_license = !empty($seo_aeo_license_key);
$seo_aeo_plugin_version = defined('SEO_AEO_VERSION') ? SEO_AEO_VERSION : '?';
$seo_aeo_bv_active = class_exists('SEO_AEO_Brand_Voice') ? SEO_AEO_Brand_Voice::get_active_profile() : null;

$seo_aeo_native_output_on   = get_option('seo_aeo_native_output_enabled', '0') === '1';
$seo_aeo_native_sitemap_on  = get_option('seo_aeo_native_sitemap_enabled', '0') === '1';
$seo_aeo_native_llms_on     = get_option('seo_aeo_native_llms_enabled', '0') === '1';
$seo_aeo_native_redirect_on = get_option('seo_aeo_native_redirect_enabled', '0') === '1';

$seo_aeo_active_count = 0;
foreach (array($seo_aeo_native_output_on, $seo_aeo_native_sitemap_on, $seo_aeo_native_llms_on, $seo_aeo_native_redirect_on, (bool) $seo_aeo_bv_active) as $seo_aeo_f) {
    if ($seo_aeo_f) $seo_aeo_active_count++;
}
$seo_aeo_total_features = 5;
?>

<?php
// 3.35.83: load Business Profile completion stats (transient cache 5min)
$seo_aeo_bp_stats = get_transient('seo_aeo_bp_dashboard_stats');
if ($seo_aeo_bp_stats === false) {
    $seo_aeo_bp_stats = array('populated' => 0, 'total' => 14, 'percent' => 0, 'is_complete' => false, 'confirmed' => false);
    if (class_exists('SEO_AEO_API_Client')) {
        $api = new SEO_AEO_API_Client();
        if (method_exists($api, 'get_business_profile')) {
            $resp = $api->get_business_profile();
            if (is_array($resp) && !empty($resp['stats'])) {
                $stats = $resp['stats'];
                $tot = isset($stats['total_count']) ? max(1, (int) $stats['total_count']) : 14;
                $pop = isset($stats['populated_count']) ? (int) $stats['populated_count'] : 0;
                $seo_aeo_bp_stats = array(
                    'populated'    => $pop,
                    'total'        => $tot,
                    'percent'      => (int) round(100 * $pop / $tot),
                    'is_complete'  => !empty($stats['is_complete']),
                    'confirmed'    => isset($resp['profile']) && !empty($resp['profile']['customer_confirmed']),
                );
            }
        }
    }
    set_transient('seo_aeo_bp_dashboard_stats', $seo_aeo_bp_stats, 5 * MINUTE_IN_SECONDS);
}
?>
<div class="wrap orch-wiz-page">

    <?php // 3.35.84.4: BP banner now rendered by SEO_AEO_Admin_Notices on `admin_notices` hook (above wrap). ?>

    <!-- HERO -->
    <div class="orch-wiz-hero">
        <div class="orch-wiz-hero-bg"></div>
        <div class="orch-wiz-hero-inner">
            <div class="orch-wiz-hero-icon">🪄</div>
            <div class="orch-wiz-hero-text">
                <div class="orch-wiz-hero-eyebrow">AEO Orchestra · v<?php echo esc_html($seo_aeo_plugin_version); ?></div>
                <h1 class="orch-wiz-hero-title"><?php SEO_AEO_T::e('Benvenuto. Tutta la SEO + AEO del tuo sito, guidata dall\'AI.'); ?></h1>
                <p class="orch-wiz-hero-sub"><?php SEO_AEO_T::e('Orchestra unisce SEO tradizionale, AEO (Answer Engine Optimization), generazione AI con la tua voce, redirect manager e sitemap nativa. Sostituisce Yoast/RankMath/AIOSEO e in più aggiunge feature uniche al mondo.'); ?></p>
                <div class="orch-wiz-hero-stats">
                    <div class="orch-wiz-stat">
                        <div class="orch-wiz-stat-value"><?php echo esc_html($seo_aeo_has_license ? SEO_AEO_T::t('✓ Attiva') : SEO_AEO_T::t('⚠ Non attiva')); ?></div>
                        <div class="orch-wiz-stat-label"><?php SEO_AEO_T::e('Licenza'); ?></div>
                    </div>
                    <div class="orch-wiz-stat" title="<?php echo esc_attr(SEO_AEO_T::t('Funzioni dello stack Native attive: Output Renderer, Override Mode, Sitemap.xml, llms.txt, Schema, Redirect Manager. Più sono attive, più Yoast/RankMath sono sostituiti.')); ?>">
                        <div class="orch-wiz-stat-value"><?php echo (int) $seo_aeo_active_count; ?>/<?php echo (int) $seo_aeo_total_features; ?></div>
                        <div class="orch-wiz-stat-label"><?php SEO_AEO_T::e('Stack Native attive'); ?> <span style="cursor:help;opacity:.6;font-size:.8em;">ⓘ</span></div>
                    </div>
                    <div class="orch-wiz-stat">
                        <div class="orch-wiz-stat-value"><?php echo $seo_aeo_bv_active ? '🎙️ ' . esc_html($seo_aeo_bv_active['_name']) : '—'; ?></div>
                        <div class="orch-wiz-stat-label"><?php SEO_AEO_T::e('Brand Voice'); ?></div>
                    </div>
                    <!-- 3.35.83: 5° stat — Profilo Business completion -->
                    <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-business-profile')); ?>" class="orch-wiz-stat orch-wiz-stat--clickable<?php echo !empty($seo_aeo_bp_stats['confirmed']) ? ' orch-wiz-stat--confirmed' : ''; ?>" title="<?php echo esc_attr(SEO_AEO_T::t('Completamento del profilo business — clicca per editarlo')); ?>">
                        <div class="orch-wiz-stat-value"><?php echo (int) $seo_aeo_bp_stats['percent']; ?>%</div>
                        <div class="orch-wiz-stat-label">
                            <?php SEO_AEO_T::e('Profilo Business'); ?>
                            <?php if (empty($seo_aeo_bp_stats['confirmed'])): ?>
                                <span style="color:#d97706; font-weight:700; margin-left:4px;">⚠</span>
                            <?php else: ?>
                                <span style="color:#16a34a; font-weight:700; margin-left:4px;" title="<?php echo esc_attr(SEO_AEO_T::t('Profilo confermato')); ?>">✓</span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="orch-wiz-stat" title="<?php echo esc_attr(SEO_AEO_T::t('Specialisti AI orchestrati e strumenti integrati nel plugin. Cresce a ogni release.')); ?>">
                        <div class="orch-wiz-stat-value"><?php echo (int) SEO_AEO_AGENTS_COUNT; ?> &middot; <?php echo (int) SEO_AEO_TOOLS_COUNT; ?></div>
                        <div class="orch-wiz-stat-label"><?php SEO_AEO_T::e('Specialisti AI &middot; Strumenti'); ?> <span style="cursor:help;opacity:.6;font-size:.8em;">&#9432;</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- QUICKSTART 4 STEP -->
    <section class="orch-wiz-section">
        <h2 class="orch-wiz-section-title"><?php SEO_AEO_T::e('🚀 Per iniziare in 5 mosse'); ?></h2>
        <p class="orch-wiz-section-sub"><?php SEO_AEO_T::e('Se sei alla prima volta, segui questi passi nell\'ordine. Ogni step è autonomo: puoi fermarti dove vuoi.'); ?></p>

        <div class="orch-wiz-steps">
            <!-- 3.35.83: Step ⭐ Business Profile -->
            <?php $bp_done = !empty($seo_aeo_bp_stats['confirmed']); ?>
            <div class="orch-wiz-step <?php echo $bp_done ? 'orch-wiz-step--done' : 'orch-wiz-step--required'; ?>">
                <div class="orch-wiz-step-num <?php echo $bp_done ? 'orch-wiz-step-num--done' : 'orch-wiz-step-num--required'; ?>"><?php echo $bp_done ? '✓' : '⭐'; ?></div>
                <div class="orch-wiz-step-body">
                    <div class="orch-wiz-step-badge<?php echo $bp_done ? ' orch-wiz-step-badge--done' : ''; ?>"><?php echo $bp_done ? '✓ DONE' : 'RICHIESTO'; ?></div>
                    <h3><?php SEO_AEO_T::e('Compila il Profilo Business'); ?></h3>
                    <p><?php SEO_AEO_T::e('I tool AI di Orchestra (Verify-Live, Contenuti AI, Analisi AEO) usano il profilo per generare suggerimenti specifici al tuo business. Senza questi dati, le risposte AI sono generiche. 4 campi critici richiesti — bastano 5 minuti.'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-business-profile')); ?>" class="orch-wiz-step-cta<?php echo $bp_done ? ' orch-wiz-step-cta--done' : ' orch-wiz-step-cta--required'; ?>"><?php echo $bp_done ? esc_html(SEO_AEO_T::t('→ Modifica profilo')) : esc_html(SEO_AEO_T::t('→ Apri Profilo Business')); ?></a>
                </div>
            </div>
            <div class="orch-wiz-step">
                <div class="orch-wiz-step-num">1</div>
                <div class="orch-wiz-step-body">
                    <h3><?php SEO_AEO_T::e('Migra da Yoast / RankMath / AIOSEO (opzionale)'); ?></h3>
                    <p><?php SEO_AEO_T::e('Hai già un plugin SEO? Il Wizard fa shadow-copy reversibile dei tuoi meta + redirect e attiva lo stack Native — Yoast resta installato e silenziato (Override Mode). Niente perdita di ranking, niente downtime, e puoi tornare indietro in 30 secondi se cambi idea. Se preferisci testare prima senza migrare, salta questo step e prova le altre funzioni: Orchestra coesiste con Yoast.'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-migration-wizard')); ?>" class="orch-wiz-step-cta"><?php SEO_AEO_T::e('→ Apri Migrazione SEO'); ?></a>
                </div>
            </div>

            <div class="orch-wiz-step">
                <div class="orch-wiz-step-num">2</div>
                <div class="orch-wiz-step-body">
                    <h3><?php SEO_AEO_T::e('Crea il tuo profilo Brand Voice'); ?></h3>
                    <p><?php SEO_AEO_T::e('L\'AI analizza i tuoi articoli pubblicati ed estrae il tuo stile. Ogni generazione futura suonerà come te, non come AI.'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-brand-voice')); ?>" class="orch-wiz-step-cta"><?php SEO_AEO_T::e('→ Apri Brand Voice'); ?></a>
                </div>
            </div>

            <div class="orch-wiz-step">
                <div class="orch-wiz-step-num">3</div>
                <div class="orch-wiz-step-body">
                    <h3><?php SEO_AEO_T::e('Genera il tuo primo articolo AI'); ?></h3>
                    <p><?php SEO_AEO_T::e('Scegli un argomento, definisci le keyword e l\'AI scrive un articolo SEO+AEO con la tua voce. Include FAQ e immagine generata.'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-content')); ?>" class="orch-wiz-step-cta"><?php SEO_AEO_T::e('→ Apri Contenuti AI'); ?></a>
                </div>
            </div>

            <div class="orch-wiz-step">
                <div class="orch-wiz-step-num">4</div>
                <div class="orch-wiz-step-body">
                    <h3><?php SEO_AEO_T::e('Pubblica e verifica i risultati'); ?></h3>
                    <p><?php SEO_AEO_T::e('Connetti Google Search Console per vedere posizionamento e CTR. Risolvi cannibalizzazioni con 1 click. Imposta redirect dai 404.'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-orchestratore')); ?>" class="orch-wiz-step-cta"><?php SEO_AEO_T::e('→ Apri Orchestratore'); ?></a>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURE GRID -->
    <section class="orch-wiz-section">
        <h2 class="orch-wiz-section-title"><?php SEO_AEO_T::e('🧩 Tutte le sezioni del plugin'); ?></h2>
        <p class="orch-wiz-section-sub"><?php SEO_AEO_T::e('Specialisti AI orchestrati. Clicca per andare alla sezione.'); ?></p>

        <div class="orch-cards-categories">
        <div class="orch-cards-section orch-cards-section--foundation">
            <div class="orch-cards-section-header" style="border-bottom-color: #16a34a; color: #15803d;">
                <span class="orch-cards-section-icon">🟢</span>
                <span class="orch-cards-section-label">Foundation & Profili</span>
                <span class="orch-cards-section-count">3 <?php SEO_AEO_T::e('strumenti'); ?></span>
            </div>
            <div class="orch-wiz-grid orch-wiz-grid--foundation">
<a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-business-profile')); ?>" class="orch-wiz-card orch-wiz-card--foundation" data-category="foundation">
                <span class="orch-wiz-card-badge">⭐ FOUNDATION v3.35.83</span>
                <div class="orch-wiz-card-icon">🏢</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Profilo Business'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('14 campi compilabili (11 public + 3 internal) usati da tutti i tool AI. Anteprima context AI live + visibility scope public/internal + zero leak in llms.txt.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-brand-voice')); ?>" class="orch-wiz-card orch-wiz-card--foundation" data-category="foundation">
                
                <div class="orch-wiz-card-icon">🎙️</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Brand Voice'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('L\'AI analizza i tuoi articoli pubblicati ed estrae il tuo stile. Ogni generazione futura suonerà come te, non come AI.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-migration-wizard')); ?>" class="orch-wiz-card orch-wiz-card--foundation" data-category="foundation">
                
                <div class="orch-wiz-card-icon">🚀</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Migrazione SEO'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Wizard 6-step: detect Yoast/RankMath/AIOSEO + shadow-copy meta + import redirect + attiva stack Native + disinstalla. Reversibile.'); ?></div>
            </a>
            </div>
        </div>

        <div class="orch-cards-section orch-cards-section--analisi">
            <div class="orch-cards-section-header" style="border-bottom-color: #3b82f6; color: #1d4ed8;">
                <span class="orch-cards-section-icon">🔵</span>
                <span class="orch-cards-section-label">Analisi & Verifica</span>
                <span class="orch-cards-section-count">8 <?php SEO_AEO_T::e('strumenti'); ?></span>
            </div>
            <div class="orch-wiz-grid orch-wiz-grid--analisi">
<a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-orchestratore')); ?>" class="orch-wiz-card orch-wiz-card--analisi" data-category="analisi">
                
                <div class="orch-wiz-card-icon">🎯</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Orchestratore'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Strumento operativo: scansiona pagine, genera proposte AI, applica modifiche, gestisci cronologia.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-native-output')); ?>" class="orch-wiz-card orch-wiz-card--analisi" data-category="analisi">
                <span class="orch-wiz-card-badge">🆕 NEW v3.35.82</span>
                <div class="orch-wiz-card-icon">🔬</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Verify-Live'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Test in 30 secondi sui motori AI. Score 0-100 + 8-15 suggerimenti specifici per migliorare visibilità nei motori AI.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-orchestra')); ?>" class="orch-wiz-card orch-wiz-card--analisi" data-category="analisi">
                <span class="orch-wiz-card-badge">🆕 NEW v3.35.84</span>
                <div class="orch-wiz-card-icon">🤖</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('AI Performance'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Tracking attività di 22 bot AI con dato GDPR-compliant. Top 10 pagine + trend 28gg + compliance robots.txt. Phase 2 Bing API in arrivo.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-analyze')); ?>" class="orch-wiz-card orch-wiz-card--analisi" data-category="analisi">
                
                <div class="orch-wiz-card-icon">🔍</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Analisi SEO'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Score 0-100 per pagina, problemi rilevati, keyword secondarie LSI, suggerimenti azionabili.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-aeo-analysis')); ?>" class="orch-wiz-card orch-wiz-card--analisi" data-category="analisi">
                
                <div class="orch-wiz-card-icon">🧠</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Analisi AEO'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Verifica visibilità nei motori AI (ChatGPT, Claude, Perplexity, Gemini). Score per pagina + suggerimenti GEO.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-cannibalization')); ?>" class="orch-wiz-card orch-wiz-card--analisi" data-category="analisi">
                
                <div class="orch-wiz-card-icon">🌳</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Cannibalizzazione SEO'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Detect pagine che competono sulla stessa query + AI fix proposal con keyword distinct + internal linking.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-keyword-research')); ?>" class="orch-wiz-card orch-wiz-card--analisi" data-category="analisi">
                
                <div class="orch-wiz-card-icon">🎯</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Keyword Research'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Discover keyword opportunità per il tuo settore via AI + GSC integration + intent classification.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-orchestra-analytics')); ?>" class="orch-wiz-card orch-wiz-card--analisi" data-category="analisi">
                
                <div class="orch-wiz-card-icon">📈</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Analytics'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Top queries + top pages dal GSC integrato. Daily aggregate scalabile.'); ?></div>
            </a>
            </div>
        </div>

        <div class="orch-cards-section orch-cards-section--creazione">
            <div class="orch-cards-section-header" style="border-bottom-color: #9333ea; color: #7e22ce;">
                <span class="orch-cards-section-icon">🟣</span>
                <span class="orch-cards-section-label">Creazione & Generazione</span>
                <span class="orch-cards-section-count">5 <?php SEO_AEO_T::e('strumenti'); ?></span>
            </div>
            <div class="orch-wiz-grid orch-wiz-grid--creazione">
<a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-content')); ?>" class="orch-wiz-card orch-wiz-card--creazione" data-category="creazione">
                
                <div class="orch-wiz-card-icon">✨</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Contenuti AI'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Articoli SEO completi con H2/H3, FAQ, sommario. Brand Voice applicato in linea.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-aeo-content')); ?>" class="orch-wiz-card orch-wiz-card--creazione" data-category="creazione">
                
                <div class="orch-wiz-card-icon">💬</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Contenuti AEO'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Articoli AEO-first ottimizzati per Answer Engine Optimization. Struttura Q&A + listicle + comparison.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-meta')); ?>" class="orch-wiz-card orch-wiz-card--creazione" data-category="creazione">
                
                <div class="orch-wiz-card-icon">🏷️</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Meta Tags'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Generazione AI title + description ottimizzati. Scrive su Yoast/RankMath/AIOSEO/native automaticamente.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-orchestra-images')); ?>" class="orch-wiz-card orch-wiz-card--creazione" data-category="creazione">
                
                <div class="orch-wiz-card-icon">🖼</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Image SEO'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Manager bulk per alt text, file naming, compression. AI auto-suggest alt text contestuale per immagini.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-local')); ?>" class="orch-wiz-card orch-wiz-card--creazione" data-category="creazione">
                
                <div class="orch-wiz-card-icon">📍</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Local SEO'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Schema LocalBusiness + Google Business Profile sync + NAP consistency check + local citation suggestions.'); ?></div>
            </a>
            </div>
        </div>

        <div class="orch-cards-section orch-cards-section--operations">
            <div class="orch-cards-section-header" style="border-bottom-color: #d97706; color: #b45309;">
                <span class="orch-cards-section-icon">🟡</span>
                <span class="orch-cards-section-label">Operations & Automazione</span>
                <span class="orch-cards-section-count">5 <?php SEO_AEO_T::e('strumenti'); ?></span>
            </div>
            <div class="orch-wiz-grid orch-wiz-grid--operations">
<a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-native-output')); ?>" class="orch-wiz-card orch-wiz-card--operations" data-category="operations">
                
                <div class="orch-wiz-card-icon">⚡</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('SEO Output Nativo'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Sostituisce Yoast: meta + OG + Twitter + canonical + sitemap.xml + llms.txt + schema. Override mode silenzia plugin esistenti.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-autopilot')); ?>" class="orch-wiz-card orch-wiz-card--operations" data-category="operations">
                
                <div class="orch-wiz-card-icon">🤖</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Auto-Pilot'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Cron WP genera articoli automaticamente: pesca keyword + crea draft (default) o pubblica (opt-in). 1/giorno · 2-sett · 3-sett · 1-sett.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-orchestra-calendar')); ?>" class="orch-wiz-card orch-wiz-card--operations" data-category="operations">
                
                <div class="orch-wiz-card-icon">📅</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Pianificazione articoli'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Pianifica articoli su date specifiche. AI genera N giorni prima, auto-pubblica. Bulk wizard 7/30 articoli.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-redirect')); ?>" class="orch-wiz-card orch-wiz-card--operations" data-category="operations">
                
                <div class="orch-wiz-card-icon">🔀</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Redirect Manager'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Tabella redirect 301/302/307 + 404 log + AI suggester per fix dei link rotti. Import da Yoast Premium / Redirection plugin.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-ai-crawlers')); ?>" class="orch-wiz-card orch-wiz-card--operations" data-category="operations">
                
                <div class="orch-wiz-card-icon">🛡️</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('AI Crawlers'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Allowlist + robots.txt management per 22 bot AI. Privacy IP toggle + log query + export CSV. Plus blocco/allow granulare per provider.'); ?></div>
            </a>
            </div>
        </div>

        <div class="orch-cards-section orch-cards-section--account">
            <div class="orch-cards-section-header" style="border-bottom-color: #64748b; color: #475569;">
                <span class="orch-cards-section-icon">⚫</span>
                <span class="orch-cards-section-label">Account & Settings</span>
                <span class="orch-cards-section-count">2 <?php SEO_AEO_T::e('strumenti'); ?></span>
            </div>
            <div class="orch-wiz-grid orch-wiz-grid--account">
<a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-usage')); ?>" class="orch-wiz-card orch-wiz-card--account" data-category="account">
                
                <div class="orch-wiz-card-icon">💳</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Consumo Crediti'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Storico uso wallet: plan_credits + topup + per-task breakdown. Auto-topup configurabile.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-settings')); ?>" class="orch-wiz-card orch-wiz-card--account" data-category="account">
                
                <div class="orch-wiz-card-icon">⚙️</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Impostazioni'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Setup license key, lingua plugin, privacy preferences, advanced toggles.'); ?></div>
            </a>
            </div>
        </div>
        </div>
    </section>

    <!-- NEWS — 3.35.84.4: dynamic from readme.txt; auto-hidden if user already dismissed for this version -->
    <?php
    $seo_aeo_news_entry = class_exists('SEO_AEO_Whats_New') ? SEO_AEO_Whats_New::current_entry() : null;
    $seo_aeo_news_dismissed = class_exists('SEO_AEO_Whats_New') ? SEO_AEO_Whats_New::is_dismissed_for_current_user() : false;
    if (!$seo_aeo_news_dismissed):
    ?>
    <section class="orch-wiz-section orch-wiz-news-section">
        <h2 class="orch-wiz-section-title">
            <?php echo esc_html(SEO_AEO_T::t('✨ Cosa c\'è di nuovo in v')); ?><?php echo esc_html($seo_aeo_plugin_version); ?>
        </h2>
        <?php if ($seo_aeo_news_entry && !empty($seo_aeo_news_entry['bullets'])): ?>
            <div class="orch-wiz-news">
                <?php foreach ($seo_aeo_news_entry['bullets'] as $seo_aeo_bullet):
                    // Bullet may already start with an emoji; if not, fall back to a generic icon.
                    $seo_aeo_first_char = mb_substr($seo_aeo_bullet, 0, 1, 'UTF-8');
                    $seo_aeo_has_emoji  = preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $seo_aeo_first_char);
                    $seo_aeo_icon       = $seo_aeo_has_emoji ? $seo_aeo_first_char : '✨';
                    $seo_aeo_text       = $seo_aeo_has_emoji ? trim(mb_substr($seo_aeo_bullet, 1, null, 'UTF-8')) : $seo_aeo_bullet;
                ?>
                <div class="orch-wiz-news-item">
                    <div class="orch-wiz-news-icon"><?php echo esc_html($seo_aeo_icon); ?></div>
                    <div><?php echo wp_kses(
                        $seo_aeo_text,
                        array('strong' => array(), 'em' => array(), 'code' => array(), 'a' => array('href' => array()))
                    ); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <p class="orch-wiz-news-actions" style="margin-top:14px;font-size:12px;color:#64748b;">
                <a href="<?php echo esc_url(SEO_AEO_Whats_New::dismiss_url()); ?>" style="color:#64748b;">
                    <?php SEO_AEO_T::e('Ho capito, non mostrare più'); ?>
                </a>
                &nbsp;·&nbsp;
                <a href="<?php echo esc_url(admin_url('plugin-install.php?tab=plugin-information&plugin=seo-aeo-orchestra&TB_iframe=true&width=772&height=600')); ?>" class="thickbox" style="color:#0055FF;">
                    <?php SEO_AEO_T::e('Changelog completo →'); ?>
                </a>
            </p>
        <?php else: ?>
            <p class="orch-wiz-news-fallback" style="color:#64748b;">
                <?php SEO_AEO_T::e('Aggiornamento minor — vedi il changelog completo per i dettagli.'); ?>
                <a href="<?php echo esc_url(admin_url('plugin-install.php?tab=plugin-information&plugin=seo-aeo-orchestra&TB_iframe=true&width=772&height=600')); ?>" class="thickbox">
                    <?php SEO_AEO_T::e('Changelog completo →'); ?>
                </a>
            </p>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- GSC INSIGHTS (3.31.0: spostato sotto "Cosa c'è di nuovo") -->
    <section class="orch-wiz-section orch-wiz-gsc-section">
        <h2 class="orch-wiz-section-title"><?php SEO_AEO_T::e('📊 Insights da Google Search Console'); ?></h2>
        <p class="orch-wiz-section-sub"><?php SEO_AEO_T::e('Vedi quali pagine ricevono impressioni e clic da Google, l\'andamento giornaliero e l\'impatto di AEO Orchestra sul tuo posizionamento.'); ?></p>
        <?php
        $seo_aeo_gsc_auto_load = true;
        include __DIR__ . '/partials/gsc-section.php';
        ?>
    </section>

    <!-- 3.35.84.3: AI Performance — heading + sub-tagline NOW provided by ai-crawler-section.php partial (no double rendering) -->
    <section class="orch-wiz-section">
        <?php
        $seo_aeo_bing_auto_load = true;
        include __DIR__ . '/partials/ai-crawler-section.php';
        ?>
    </section>

    <!-- GUIDE -->
    <section class="orch-wiz-section">
        <h2 class="orch-wiz-section-title"><?php SEO_AEO_T::e('📚 Guide e risorse'); ?></h2>
        <p class="orch-wiz-section-sub"><?php SEO_AEO_T::e('Articoli sul sito ufficiale per approfondire ogni feature.'); ?></p>

        <div class="orch-wiz-guides">
            <a href="https://aeo-orchestra.com/guide/migrazione-da-yoast" target="_blank" rel="noopener" class="orch-wiz-guide">
                <div class="orch-wiz-guide-eyebrow"><?php SEO_AEO_T::e('Guida'); ?></div>
                <div class="orch-wiz-guide-title"><?php SEO_AEO_T::e('Come migrare da Yoast in 3 minuti'); ?></div>
                <div class="orch-wiz-guide-arrow">→</div>
            </a>
            <a href="https://aeo-orchestra.com/guide/brand-voice" target="_blank" rel="noopener" class="orch-wiz-guide">
                <div class="orch-wiz-guide-eyebrow"><?php SEO_AEO_T::e('Guida'); ?></div>
                <div class="orch-wiz-guide-title"><?php SEO_AEO_T::e('Come funziona Brand Voice (e perché conta)'); ?></div>
                <div class="orch-wiz-guide-arrow">→</div>
            </a>
            <a href="https://aeo-orchestra.com/guide/llms-txt" target="_blank" rel="noopener" class="orch-wiz-guide">
                <div class="orch-wiz-guide-eyebrow"><?php SEO_AEO_T::e('Guida'); ?></div>
                <div class="orch-wiz-guide-title"><?php SEO_AEO_T::e('llms.txt: cos\'è e perché ti serve nel 2026'); ?></div>
                <div class="orch-wiz-guide-arrow">→</div>
            </a>
            <a href="https://aeo-orchestra.com/guide/aeo-vs-seo" target="_blank" rel="noopener" class="orch-wiz-guide">
                <div class="orch-wiz-guide-eyebrow"><?php SEO_AEO_T::e('Guida'); ?></div>
                <div class="orch-wiz-guide-title"><?php SEO_AEO_T::e('AEO vs SEO: la differenza in 5 minuti'); ?></div>
                <div class="orch-wiz-guide-arrow">→</div>
            </a>
            <a href="https://aeo-orchestra.com/guide/cannibalizzazione" target="_blank" rel="noopener" class="orch-wiz-guide">
                <div class="orch-wiz-guide-eyebrow"><?php SEO_AEO_T::e('Guida'); ?></div>
                <div class="orch-wiz-guide-title"><?php SEO_AEO_T::e('Cannibalizzazione keyword: identificare e risolvere'); ?></div>
                <div class="orch-wiz-guide-arrow">→</div>
            </a>
            <a href="https://aeo-orchestra.com/changelog" target="_blank" rel="noopener" class="orch-wiz-guide">
                <div class="orch-wiz-guide-eyebrow"><?php SEO_AEO_T::e('Changelog'); ?></div>
                <div class="orch-wiz-guide-title"><?php SEO_AEO_T::e('Tutte le novità versione per versione'); ?></div>
                <div class="orch-wiz-guide-arrow">→</div>
            </a>
        </div>
    </section>

    <!-- SUPPORTO -->
    <section class="orch-wiz-section orch-wiz-support">
        <div class="orch-wiz-support-card">
            <div class="orch-wiz-support-icon">🆘</div>
            <div>
                <h3><?php SEO_AEO_T::e('Hai bisogno di aiuto?'); ?></h3>
                <p><?php SEO_AEO_T::e('Scrivici da '); ?><a href="https://aeo-orchestra.com/supporto" target="_blank" rel="noopener">aeo-orchestra.com/supporto</a><?php SEO_AEO_T::e(' oppure manda mail a '); ?><a href="mailto:supporto@aeo-orchestra.com">supporto@aeo-orchestra.com</a><?php SEO_AEO_T::e('. Risposta tipica entro 24 ore.'); ?></p>
            </div>
        </div>
    </section>
</div>

<?php ob_start(); ?>
.orch-wiz-page { max-width: 1280px; margin-right: 20px; }

/* HERO */
.orch-wiz-hero { position: relative; margin: 14px 0 26px; padding: 38px 44px; border-radius: 16px; overflow: hidden; color: #fff; }
.orch-wiz-hero-bg { position: absolute; inset: 0; background: linear-gradient(120deg, #0A0E27 0%, #1a0b2e 30%, #0055FF 65%, #00E5FF 100%); }
.orch-wiz-hero-bg::after { content: ''; position: absolute; inset: 0; background: radial-gradient(800px 400px at 80% -20%, rgba(255,255,255,0.18), transparent 60%); }
.orch-wiz-hero-inner { position: relative; display: flex; gap: 28px; align-items: center; }
.orch-wiz-hero-icon { font-size: 78px; line-height: 1; flex-shrink: 0; }
.orch-wiz-hero-eyebrow { font-size: 12px; letter-spacing: 1.2px; text-transform: uppercase; opacity: 0.85; font-weight: 600; }
.orch-wiz-hero-title { margin: 8px 0 10px; font-size: 30px; font-weight: 800; line-height: 1.2; color: #fff; }
.orch-wiz-hero-sub { margin: 0; font-size: 15px; line-height: 1.6; opacity: 0.92; max-width: 760px; }
.orch-wiz-hero-stats { display: flex; gap: 18px; margin-top: 22px; flex-wrap: wrap; }
.orch-wiz-stat { background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.18); padding: 10px 16px; border-radius: 10px; min-width: 140px; }
.orch-wiz-stat-value { font-size: 16px; font-weight: 700; line-height: 1.3; }
.orch-wiz-stat-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8; margin-top: 2px; }

/* SECTION */
.orch-wiz-section { margin: 36px 0; }
.orch-wiz-section-title { font-size: 22px; font-weight: 700; color: #0A0E27; margin: 0 0 6px; }
.orch-wiz-section-sub { color: #6b7280; font-size: 14px; margin: 0 0 20px; }

/* QUICKSTART STEPS */
.orch-wiz-steps { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
@media (max-width: 900px) { .orch-wiz-steps { grid-template-columns: 1fr; } }
.orch-wiz-step { display: flex; gap: 16px; padding: 22px 24px; background: #fff; border: 1.5px solid #e5e7eb; border-radius: 12px; transition: border-color 0.15s, transform 0.15s; }
.orch-wiz-step:hover { border-color: #0055FF; transform: translateY(-2px); }
.orch-wiz-step-num { width: 42px; height: 42px; flex-shrink: 0; background: linear-gradient(135deg, #0055FF, #00E5FF); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 18px; }
.orch-wiz-step-body h3 { margin: 4px 0 6px; font-size: 16px; font-weight: 700; color: #0A0E27; }
.orch-wiz-step-body p { margin: 0 0 10px; font-size: 13px; color: #4b5563; line-height: 1.5; }
.orch-wiz-step-cta { color: #0055FF; font-weight: 600; font-size: 13px; text-decoration: none; }
.orch-wiz-step-cta:hover { color: #00E5FF; }

/* FEATURE GRID */
.orch-wiz-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; }
.orch-wiz-card { position: relative; display: block; padding: 20px; background: #fff; border: 1.5px solid #e5e7eb; border-radius: 12px; text-decoration: none; color: inherit; transition: all 0.15s; }
.orch-wiz-card:hover { border-color: #0055FF; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,85,255,0.1); }
.orch-wiz-card-on { background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border-color: #86efac; }
.orch-wiz-card-on:hover { border-color: #22c55e; }
.orch-wiz-card-icon { font-size: 32px; margin-bottom: 10px; line-height: 1; }
.orch-wiz-card-title { font-size: 15px; font-weight: 700; color: #0A0E27; margin-bottom: 6px; }
.orch-wiz-card-desc { font-size: 12.5px; color: #6b7280; line-height: 1.5; }
.orch-wiz-card-badge { display: inline-block; margin-top: 10px; padding: 3px 9px; background: #22c55e; color: #fff; border-radius: 999px; font-size: 11px; font-weight: 600; }

/* NEWS */
.orch-wiz-news { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
@media (max-width: 800px) { .orch-wiz-news { grid-template-columns: 1fr; } }
.orch-wiz-news-item { display: flex; gap: 14px; padding: 16px 18px; background: #fefce8; border-left: 4px solid #eab308; border-radius: 8px; font-size: 13px; line-height: 1.55; color: #422006; }
.orch-wiz-news-icon { font-size: 22px; line-height: 1; flex-shrink: 0; }

/* GUIDES */
.orch-wiz-guides { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px; }
.orch-wiz-guide { display: flex; align-items: center; gap: 14px; padding: 16px 20px; background: #fff; border: 1.5px solid #e5e7eb; border-radius: 10px; text-decoration: none; color: inherit; transition: all 0.15s; }
.orch-wiz-guide:hover { border-color: #0055FF; }
.orch-wiz-guide-eyebrow { font-size: 10px; letter-spacing: 1px; text-transform: uppercase; color: #0055FF; font-weight: 700; margin-bottom: 4px; }
.orch-wiz-guide > div:nth-child(2) { flex: 1; }
.orch-wiz-guide-title { font-size: 13.5px; font-weight: 600; color: #0A0E27; line-height: 1.4; }
.orch-wiz-guide-arrow { font-size: 22px; color: #0055FF; font-weight: 600; flex-shrink: 0; }

/* GSC SECTION (3.28.0) */
.orch-wiz-gsc-section .orch-gsc-card { margin-top: 0; padding: 18px 22px; background: #fff; border: 1.5px solid #e5e7eb; border-radius: 12px; }
.orch-wiz-gsc-section .orch-gsc-head .orch3-h2 { font-size: 16px; color: #0a0e27; }

/* BING AI PERFORMANCE SECTION (3.29.0) */
.orch-wiz-bing-section .orch-bing-card { margin-top: 0; padding: 18px 22px; background: #fff; border: 1.5px solid #e5e7eb; border-radius: 12px; }
.orch-wiz-bing-section .orch-bing-head .orch3-h2 { font-size: 16px; color: #0a0e27; }

/* SUPPORTO */
.orch-wiz-support-card { display: flex; gap: 18px; align-items: center; padding: 22px 26px; background: linear-gradient(90deg, #eff6ff, #dbeafe); border: 1px solid #93c5fd; border-radius: 12px; }
.orch-wiz-support-icon { font-size: 38px; line-height: 1; }
.orch-wiz-support-card h3 { margin: 0 0 4px; font-size: 17px; color: #0A0E27; }
.orch-wiz-support-card p { margin: 0; font-size: 13.5px; color: #1e3a8a; line-height: 1.55; }
.orch-wiz-support-card a { color: #0055FF; font-weight: 600; }

/* 3.35.83: Step ⭐ RICHIESTO visual treatment */
.orch-wiz-step--required {
    border: 2px solid #d97706;
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    position: relative;
}
.orch-wiz-step-num--required {
    background: #d97706 !important;
    color: #fff !important;
    font-size: 24px !important;
}
.orch-wiz-step-badge {
    display: inline-block;
    padding: 2px 10px;
    background: #d97706;
    color: #ffffff;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.5px;
    border-radius: 10px;
    text-transform: uppercase;
    margin-bottom: 8px;
}
.orch-wiz-step-cta--required {
    background: #d97706 !important;
    color: #ffffff !important;
}
.orch-wiz-step-cta--required:hover {
    background: #b45309 !important;
}
.orch-wiz-stat--clickable {
    cursor: pointer;
    text-decoration: none !important;
    transition: transform 0.15s, box-shadow 0.15s;
}
.orch-wiz-stat--clickable:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    text-decoration: none !important;
}

/* 3.35.83.1.2: Step ✓ DONE post-confirm */
.orch-wiz-step--done {
    border: 2px solid #16a34a;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    position: relative;
}
.orch-wiz-step-num--done {
    background: #16a34a !important;
    color: #fff !important;
    font-size: 24px !important;
}
.orch-wiz-step-badge--done {
    background: #16a34a !important;
    color: #fff !important;
}
.orch-wiz-step-cta--done {
    background: #16a34a !important;
    color: #fff !important;
}
.orch-wiz-step-cta--done:hover {
    background: #15803d !important;
}
.orch-wiz-stat--confirmed .orch-wiz-stat-value {
    color: #16a34a;
}

/* 3.35.84.2: card category color-coding (5 buckets — customer journey) */
.orch-wiz-card { position: relative; transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s; }
.orch-wiz-card--foundation { border-left: 4px solid #16a34a; }
.orch-wiz-card--foundation .orch-wiz-card-icon { background: #dcfce7; }
.orch-wiz-card--analisi { border-left: 4px solid #3b82f6; }
.orch-wiz-card--analisi .orch-wiz-card-icon { background: #dbeafe; }
.orch-wiz-card--creazione { border-left: 4px solid #9333ea; }
.orch-wiz-card--creazione .orch-wiz-card-icon { background: #f3e8ff; }
.orch-wiz-card--operations { border-left: 4px solid #d97706; }
.orch-wiz-card--operations .orch-wiz-card-icon { background: #fef3c7; }
.orch-wiz-card--account { border-left: 4px solid #64748b; }
.orch-wiz-card--account .orch-wiz-card-icon { background: #f1f5f9; }

/* Card icon: pill background for category color */
.orch-wiz-card-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border-radius: 10px;
    font-size: 22px;
    margin-bottom: 8px;
}

/* Badge top-right NEW / FOUNDATION */
.orch-wiz-card-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 3px 7px;
    border-radius: 999px;
    background: linear-gradient(90deg, #f59e0b, #f97316);
    color: #ffffff;
    white-space: nowrap;
}
.orch-wiz-card[data-category="foundation"] .orch-wiz-card-badge {
    background: linear-gradient(90deg, #16a34a, #22c55e);
}

/* 3.35.84.3: section headers per category + card background tinted (combo Opzione C) */
.orch-cards-categories { margin-top: 12px; }
.orch-cards-section { margin: 0 0 18px; }
.orch-cards-section-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 1.5rem 0 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--orch-line, #e4e4e7);
    font-size: 0.875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.orch-cards-section-icon { font-size: 1.25rem; }
.orch-cards-section-label { font-weight: 700; }
.orch-cards-section-count {
    font-weight: 400;
    color: var(--orch-faint, #94a3b8);
    margin-left: auto;
    font-size: 0.75rem;
    text-transform: none;
    letter-spacing: 0.02em;
}

/* 3.35.84.3.1: card backgrounds saturation upgrade (96-98% → 92-94% medium) */
.orch-card--foundation { background: #dcfce7; }
.orch-card--analisi    { background: #dbeafe; }
.orch-card--creazione  { background: #ede9fe; }
.orch-card--operations { background: #fef3c7; }
.orch-card--account    { background: #e2e8f0; }

/* Icon backgrounds: +1 shade saturato */
.orch-wiz-card.orch-card--foundation .orch-wiz-card-icon { background: #86efac; }
.orch-wiz-card.orch-card--analisi    .orch-wiz-card-icon { background: #93c5fd; }
.orch-wiz-card.orch-card--creazione  .orch-wiz-card-icon { background: #c4b5fd; }
.orch-wiz-card.orch-card--operations .orch-wiz-card-icon { background: #fcd34d; }
.orch-wiz-card.orch-card--account    .orch-wiz-card-icon { background: #cbd5e1; }

/* Hover: -3% lightness per categoria (visual feedback più intenso) */
.orch-card--foundation:hover { background: #bbf7d0; }
.orch-card--analisi:hover    { background: #bfdbfe; }
.orch-card--creazione:hover  { background: #ddd6fe; }
.orch-card--operations:hover { background: #fde68a; }
.orch-card--account:hover    { background: #cbd5e1; }
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

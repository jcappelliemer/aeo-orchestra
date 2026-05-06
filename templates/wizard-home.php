<?php
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

<div class="wrap orch-wiz-page">

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
                </div>
            </div>
        </div>
    </div>

    <!-- QUICKSTART 4 STEP -->
    <section class="orch-wiz-section">
        <h2 class="orch-wiz-section-title"><?php SEO_AEO_T::e('🚀 Per iniziare in 4 mosse'); ?></h2>
        <p class="orch-wiz-section-sub"><?php SEO_AEO_T::e('Se sei alla prima volta, segui questi passi nell\'ordine. Ogni step è autonomo: puoi fermarti dove vuoi.'); ?></p>

        <div class="orch-wiz-steps">
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
                    <a href="<?php echo esc_url((defined('SEO_AEO_IS_FREE') && SEO_AEO_IS_FREE ? admin_url('admin.php?page=seo-aeo-pro') : admin_url('admin.php?page=seo-aeo-brand-voice'))); ?>" class="orch-wiz-step-cta"><?php SEO_AEO_T::e('→ Apri Brand Voice'); ?></a>
                </div>
            </div>

            <div class="orch-wiz-step">
                <div class="orch-wiz-step-num">3</div>
                <div class="orch-wiz-step-body">
                    <h3><?php SEO_AEO_T::e('Genera il tuo primo articolo AI'); ?></h3>
                    <p><?php SEO_AEO_T::e('Scegli un argomento, definisci le keyword e l\'AI scrive un articolo SEO+AEO con la tua voce. Include FAQ e immagine generata.'); ?></p>
                    <a href="<?php echo esc_url((defined('SEO_AEO_IS_FREE') && SEO_AEO_IS_FREE ? admin_url('admin.php?page=seo-aeo-pro') : admin_url('admin.php?page=seo-aeo-content'))); ?>" class="orch-wiz-step-cta"><?php SEO_AEO_T::e('→ Apri Contenuti AI'); ?></a>
                </div>
            </div>

            <div class="orch-wiz-step">
                <div class="orch-wiz-step-num">4</div>
                <div class="orch-wiz-step-body">
                    <h3><?php SEO_AEO_T::e('Pubblica e verifica i risultati'); ?></h3>
                    <p><?php SEO_AEO_T::e('Connetti Google Search Console per vedere posizionamento e CTR. Risolvi cannibalizzazioni con 1 click. Imposta redirect dai 404.'); ?></p>
                    <a href="<?php echo esc_url((defined('SEO_AEO_IS_FREE') && SEO_AEO_IS_FREE ? admin_url('admin.php?page=seo-aeo-pro') : admin_url('admin.php?page=seo-aeo-orchestratore'))); ?>" class="orch-wiz-step-cta"><?php SEO_AEO_T::e('→ Apri Orchestratore'); ?></a>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURE GRID -->
    <section class="orch-wiz-section">
        <h2 class="orch-wiz-section-title"><?php SEO_AEO_T::e('🧩 Tutte le sezioni del plugin'); ?></h2>
        <p class="orch-wiz-section-sub"><?php SEO_AEO_T::e('18 strumenti integrati. Clicca per andare alla sezione.'); ?></p>

        <div class="orch-wiz-grid">
            <a href="<?php echo esc_url((defined('SEO_AEO_IS_FREE') && SEO_AEO_IS_FREE ? admin_url('admin.php?page=seo-aeo-pro') : admin_url('admin.php?page=seo-aeo-orchestratore'))); ?>" class="orch-wiz-card">
                <div class="orch-wiz-card-icon">🎯</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Orchestratore'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Strumento operativo: scansiona pagine, genera proposte AI, applica modifiche, gestisci cronologia.'); ?></div>
            </a>

            <a href="<?php echo esc_url((defined('SEO_AEO_IS_FREE') && SEO_AEO_IS_FREE ? admin_url('admin.php?page=seo-aeo-pro') : admin_url('admin.php?page=seo-aeo-analyze'))); ?>" class="orch-wiz-card">
                <div class="orch-wiz-card-icon">🔍</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Analisi SEO'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Score 0-100 per pagina, problemi rilevati, keyword secondarie LSI, suggerimenti azionabili.'); ?></div>
            </a>

            <a href="<?php echo esc_url((defined('SEO_AEO_IS_FREE') && SEO_AEO_IS_FREE ? admin_url('admin.php?page=seo-aeo-pro') : admin_url('admin.php?page=seo-aeo-meta'))); ?>" class="orch-wiz-card">
                <div class="orch-wiz-card-icon">🏷️</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Meta Tags'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Generazione AI title + description ottimizzati. Scrive su Yoast/RankMath/AIOSEO/native automaticamente.'); ?></div>
            </a>

            <a href="<?php echo esc_url((defined('SEO_AEO_IS_FREE') && SEO_AEO_IS_FREE ? admin_url('admin.php?page=seo-aeo-pro') : admin_url('admin.php?page=seo-aeo-content'))); ?>" class="orch-wiz-card">
                <div class="orch-wiz-card-icon">✨</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Contenuti AI'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Articoli SEO completi con H2/H3, FAQ, sommario. Brand Voice applicato in linea.'); ?></div>
            </a>

            <a href="<?php echo esc_url((defined('SEO_AEO_IS_FREE') && SEO_AEO_IS_FREE ? admin_url('admin.php?page=seo-aeo-pro') : admin_url('admin.php?page=seo-aeo-local'))); ?>" class="orch-wiz-card">
                <div class="orch-wiz-card-icon">📍</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Local SEO'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Pagine servizio + landing per attività locali con città-target e schema.'); ?></div>
            </a>

            <a href="<?php echo esc_url((defined('SEO_AEO_IS_FREE') && SEO_AEO_IS_FREE ? admin_url('admin.php?page=seo-aeo-pro') : admin_url('admin.php?page=seo-aeo-cannibalization'))); ?>" class="orch-wiz-card">
                <div class="orch-wiz-card-icon">⚔️</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Cannibalizzazione SEO'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Trova pagine in conflitto sulla stessa keyword. AI propone fix con 1 click.'); ?></div>
            </a>

            <a href="<?php echo esc_url((defined('SEO_AEO_IS_FREE') && SEO_AEO_IS_FREE ? admin_url('admin.php?page=seo-aeo-pro') : admin_url('admin.php?page=seo-aeo-aeo-analysis'))); ?>" class="orch-wiz-card">
                <div class="orch-wiz-card-icon">🧠</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Analisi AEO'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Verifica visibilità su Google AI Overviews, ChatGPT, Perplexity, Bing Copilot. Score citability.'); ?></div>
            </a>

            <a href="<?php echo esc_url((defined('SEO_AEO_IS_FREE') && SEO_AEO_IS_FREE ? admin_url('admin.php?page=seo-aeo-pro') : admin_url('admin.php?page=seo-aeo-aeo-content'))); ?>" class="orch-wiz-card">
                <div class="orch-wiz-card-icon">💬</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Contenuti AEO'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Articoli ottimizzati per essere CITATI dalle AI: domanda-risposta, schema.org, dati concreti.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-native-output')); ?>" class="orch-wiz-card<?php if ($seo_aeo_active_count >= 3) echo ' orch-wiz-card-on'; ?>">
                <div class="orch-wiz-card-icon">⚡</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('SEO Output Nativo'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Sostituto completo di Yoast/RankMath: meta head, sitemap.xml, llms.txt, schema, redirect, AI 404 suggester.'); ?></div>
                <?php if ($seo_aeo_active_count > 0): ?><div class="orch-wiz-card-badge">⚡ <?php echo (int) $seo_aeo_active_count; ?> <?php SEO_AEO_T::e('attivi'); ?></div><?php endif; ?>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-redirect')); ?>" class="orch-wiz-card<?php if ($seo_aeo_native_redirect_on) echo ' orch-wiz-card-on'; ?>">
                <div class="orch-wiz-card-icon">🔀</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Redirect Manager'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Crea e gestisci redirect 301/302, importa da Yoast/Redirection, AI 404 suggester.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-migration-wizard')); ?>" class="orch-wiz-card">
                <div class="orch-wiz-card-icon">🚀</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Migrazione SEO'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Wizard 6 step per migrare da Yoast/RankMath/AIOSEO. Backup, copia meta, import redirect, attivazione.'); ?></div>
            </a>

            <a href="<?php echo esc_url((defined('SEO_AEO_IS_FREE') && SEO_AEO_IS_FREE ? admin_url('admin.php?page=seo-aeo-pro') : admin_url('admin.php?page=seo-aeo-brand-voice'))); ?>" class="orch-wiz-card<?php if ($seo_aeo_bv_active) echo ' orch-wiz-card-on'; ?>">
                <div class="orch-wiz-card-icon">🎙️</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Brand Voice'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('L\'AI impara il tuo stile dai tuoi articoli. Applicato a ogni generazione per non "sembrare AI".'); ?></div>
                <?php if ($seo_aeo_bv_active): ?><div class="orch-wiz-card-badge">🎙️ <?php SEO_AEO_T::e('Attivo'); ?>: <?php echo esc_html($seo_aeo_bv_active['_name']); ?></div><?php endif; ?>
            </a>

            <a href="<?php echo esc_url((defined('SEO_AEO_IS_FREE') && SEO_AEO_IS_FREE ? admin_url('admin.php?page=seo-aeo-pro') : admin_url('admin.php?page=seo-aeo-keyword-research'))); ?>" class="orch-wiz-card">
                <div class="orch-wiz-card-icon">🎯</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Keyword Research'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Da nicchia a 30 keyword strategiche con cluster, intent, difficolta. Genera articoli direttamente.'); ?></div>
            </a>

            <a href="<?php echo esc_url((defined('SEO_AEO_IS_FREE') && SEO_AEO_IS_FREE ? admin_url('admin.php?page=seo-aeo-pro') : admin_url('admin.php?page=seo-aeo-orchestra-calendar'))); ?>" class="orch-wiz-card">
                <div class="orch-wiz-card-icon">📅</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Calendario AI'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Pianifica articoli su date specifiche. AI genera N giorni prima, auto-pubblica. Bulk wizard.'); ?></div>
                <div class="orch-wiz-card-badge">💚 Money-back</div>
            </a>

            <a href="<?php echo esc_url((defined('SEO_AEO_IS_FREE') && SEO_AEO_IS_FREE ? admin_url('admin.php?page=seo-aeo-pro') : admin_url('admin.php?page=seo-aeo-orchestra-images'))); ?>" class="orch-wiz-card">
                <div class="orch-wiz-card-icon">🖼</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Image SEO'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Audit + bulk fix metadata immagini con AI Vision. Alt/title/caption auto-generati.'); ?></div>
            </a>

            <a href="<?php echo esc_url((defined('SEO_AEO_IS_FREE') && SEO_AEO_IS_FREE ? admin_url('admin.php?page=seo-aeo-pro') : admin_url('admin.php?page=seo-aeo-orchestra-analytics'))); ?>" class="orch-wiz-card">
                <div class="orch-wiz-card-icon">📈</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Analytics'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Insights GSC + 5 KPI Orchestra proprietari. Sparkline period 7/28/90gg.'); ?></div>
            </a>

            <a href="<?php echo esc_url((defined('SEO_AEO_IS_FREE') && SEO_AEO_IS_FREE ? admin_url('admin.php?page=seo-aeo-pro') : admin_url('admin.php?page=seo-aeo-autopilot'))); ?>" class="orch-wiz-card">
                <div class="orch-wiz-card-icon">🤖</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Auto-Pilot'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Cron WP che genera articoli automaticamente da set keyword. Configura una volta, dimentica. Premium.'); ?></div>
            </a>

            <a href="<?php echo esc_url((defined('SEO_AEO_IS_FREE') && SEO_AEO_IS_FREE ? admin_url('admin.php?page=seo-aeo-pro') : admin_url('admin.php?page=seo-aeo-usage'))); ?>" class="orch-wiz-card">
                <div class="orch-wiz-card-icon">💳</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Consumo Crediti'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Storia chiamate AI, costi per tipo operazione, residuo wallet. Esportabile.'); ?></div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-settings')); ?>" class="orch-wiz-card">
                <div class="orch-wiz-card-icon">⚙️</div>
                <div class="orch-wiz-card-title"><?php SEO_AEO_T::e('Impostazioni'); ?></div>
                <div class="orch-wiz-card-desc"><?php SEO_AEO_T::e('Licenza, configurazione tecnica, opzioni avanzate.'); ?></div>
            </a>
        </div>
    </section>

    <!-- NEWS -->
    <section class="orch-wiz-section">
        <h2 class="orch-wiz-section-title"><?php SEO_AEO_T::e('✨ Cosa c\'è di nuovo in v'); ?><?php echo esc_html($seo_aeo_plugin_version); ?></h2>
        <div class="orch-wiz-news">
            <div class="orch-wiz-news-item">
                <div class="orch-wiz-news-icon">🎙️</div>
                <div>
                    <strong><?php SEO_AEO_T::e('Brand Voice Learning'); ?></strong> — <?php SEO_AEO_T::e('l\'AI impara il tuo stile dai tuoi articoli e lo applica a ogni generazione. Modificabile inline dalla pagina di generazione.'); ?>
                </div>
            </div>
            <div class="orch-wiz-news-item">
                <div class="orch-wiz-news-icon">🖼️</div>
                <div>
                    <strong><?php SEO_AEO_T::e('Auto-image dal contenuto'); ?></strong> — <?php SEO_AEO_T::e('l\'immagine dell\'articolo viene generata in base ai temi visivi estratti dal testo, non dal solo titolo.'); ?>
                </div>
            </div>
            <div class="orch-wiz-news-item">
                <div class="orch-wiz-news-icon">🪄</div>
                <div>
                    <strong><?php SEO_AEO_T::e('Wizard di onboarding'); ?></strong> — <?php SEO_AEO_T::e('questa pagina. Per orientarti tra le 11 sezioni e iniziare nel modo giusto.'); ?>
                </div>
            </div>
            <div class="orch-wiz-news-item">
                <div class="orch-wiz-news-icon">🚀</div>
                <div>
                    <strong><?php SEO_AEO_T::e('Stack Native completo'); ?></strong> (v3.20) — <?php SEO_AEO_T::e('sitemap, llms.txt, schema dinamico, redirect manager, AI 404 suggester. Sostituto totale di Yoast.'); ?>
                </div>
            </div>
        </div>
    </section>

    <!-- GSC INSIGHTS (3.31.0: spostato sotto "Cosa c'è di nuovo") -->
    <section class="orch-wiz-section orch-wiz-gsc-section">
        <h2 class="orch-wiz-section-title"><?php SEO_AEO_T::e('📊 Insights da Google Search Console'); ?></h2>
        <p class="orch-wiz-section-sub"><?php SEO_AEO_T::e('Vedi quali pagine ricevono impressioni e clic da Google, l\'andamento giornaliero e l\'impatto di AEO Orchestra sul tuo posizionamento.'); ?></p>
        <?php
        $seo_aeo_gsc_auto_load = true;
        include __DIR__ . '/partials/gsc-section.php';
        ?>
    </section>

    <!-- BING AI PERFORMANCE (3.31.0: spostato sotto GSC) -->
    <section class="orch-wiz-section orch-wiz-bing-section">
        <h2 class="orch-wiz-section-title"><?php SEO_AEO_T::e('🤖 AI Performance · Quanto le AI citano il tuo sito'); ?></h2>
        <p class="orch-wiz-section-sub"><?php SEO_AEO_T::e('Tracciamento esclusivo delle citazioni in ChatGPT, Microsoft Copilot e Bing Chat tramite Bing Webmaster Tools. Nessun altro plugin SEO al mondo lo offre.'); ?></p>
        <?php
        $seo_aeo_bing_auto_load = true;
        include __DIR__ . '/partials/ai-performance-section.php';
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

<style>
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
</style>

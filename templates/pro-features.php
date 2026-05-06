<?php
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com
 *
 * Pro Features landing page (FREE build only).
 * Pagina promo completa con feature, comparazione Free/Pro, pricing, social proof.
 */
if (!defined('ABSPATH')) exit;

$seo_aeo_upgrade_url = 'https://aeo-orchestra.com/pricing?utm_source=wp-plugin&utm_medium=pro-features&utm_campaign=upgrade';
$seo_aeo_start_url   = 'https://aeo-orchestra.com/?utm_source=wp-plugin&utm_medium=pro-features&utm_campaign=trial';
?>
<div class="wrap orch-pro-page">

    <!-- HERO -->
    <div class="orch-pro-hero">
        <div class="orch-pro-hero-eyebrow">⚡ AEO Orchestra Pro</div>
        <h1 class="orch-pro-hero-title">Smetti di scrivere articoli. Inizia a venderli.</h1>
        <p class="orch-pro-hero-sub">
            La versione gratuita gestisce gli output SEO. <strong>La Pro genera l'intero contenuto con l'AI</strong>:
            articolo, immagine, meta tags, schema, all'interno della tua brand voice. Pubblichi articoli in 12 secondi
            invece di 4 ore.
        </p>
        <div class="orch-pro-hero-cta-row">
            <a href="<?php echo esc_url($seo_aeo_start_url); ?>" target="_blank" rel="noopener" class="orch-pro-btn-primary">🚀 Inizia trial 7 giorni gratis</a>
            <a href="<?php echo esc_url($seo_aeo_upgrade_url); ?>" target="_blank" rel="noopener" class="orch-pro-btn-ghost">Vedi i piani →</a>
        </div>
        <div class="orch-pro-hero-trust">
            ✓ Niente carta di credito richiesta &nbsp;·&nbsp; ✓ Garanzia rimborso 5 minuti per articolo &nbsp;·&nbsp; ✓ Funziona con il plugin che hai già installato
        </div>
    </div>

    <!-- SOCIAL PROOF METRICS -->
    <div class="orch-pro-stats">
        <div class="orch-pro-stat">
            <div class="orch-pro-stat-num">12 sec</div>
            <div class="orch-pro-stat-label">tempo medio per generare un articolo completo (testo + immagine + meta)</div>
        </div>
        <div class="orch-pro-stat">
            <div class="orch-pro-stat-num">+157%</div>
            <div class="orch-pro-stat-label">incremento medio click GSC su pagine generate con Brand Voice attiva</div>
        </div>
        <div class="orch-pro-stat">
            <div class="orch-pro-stat-num">7 gg</div>
            <div class="orch-pro-stat-label">trial gratuito completo. Nessuna feature limitata.</div>
        </div>
    </div>

    <!-- COMPARISON TABLE -->
    <h2 class="orch-pro-section-title">Free vs Pro</h2>
    <p class="orch-pro-section-sub">Cosa cambia quando passi alla Pro</p>

    <div class="orch-pro-compare-wrap">
        <table class="orch-pro-compare">
            <thead>
                <tr>
                    <th></th>
                    <th class="orch-pro-col-free">Free <span class="orch-pro-tag-free">Stai usando questa</span></th>
                    <th class="orch-pro-col-pro">Pro <span class="orch-pro-tag-pro">€129 una tantum</span></th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Output SEO nativo (title, meta, OG, Twitter, canonical)</td><td>✓</td><td>✓</td></tr>
                <tr><td>Sitemap.xml automatica + sub-sitemap CPT</td><td>✓</td><td>✓</td></tr>
                <tr><td>llms.txt + llms-full.txt (mappa per AI Search)</td><td>✓</td><td>✓</td></tr>
                <tr><td>Schema.org JSON-LD dinamico</td><td>✓</td><td>✓</td></tr>
                <tr><td>Redirect Manager (301/302 manuale, 404 log)</td><td>✓</td><td>✓</td></tr>
                <tr><td>Migration Wizard da Yoast/RankMath/AIOSEO</td><td>✓</td><td>✓</td></tr>
                <tr class="orch-pro-divider"><td colspan="3">— Tutto quello sotto è solo Pro —</td></tr>
                <tr><td><strong>Generazione articoli AI</strong> (1000-3000 parole, FAQ, schema, struttura H2/H3)</td><td>—</td><td>✓</td></tr>
                <tr><td><strong>Brand Voice Learning</strong> (l'AI impara il tuo stile dai post esistenti)</td><td>—</td><td>✓</td></tr>
                <tr><td><strong>Auto-Pilot Scheduler</strong> (cron WP che pubblica articoli da solo)</td><td>—</td><td>✓</td></tr>
                <tr><td><strong>Calendario contenuti AI</strong> (pianifica 7-30 articoli in anticipo)</td><td>—</td><td>✓</td></tr>
                <tr><td><strong>Keyword Research Autopilot</strong> (30 keyword strategiche da AI semantic graph)</td><td>—</td><td>✓</td></tr>
                <tr><td><strong>Meta Tags AI bulk</strong> (title + description per centinaia di pagine)</td><td>—</td><td>✓</td></tr>
                <tr><td><strong>Image SEO bulk</strong> (alt-text AI per la libreria media)</td><td>—</td><td>✓</td></tr>
                <tr><td><strong>AI 404 Suggester</strong> (l'AI sceglie il target redirect dei 404)</td><td>—</td><td>✓</td></tr>
                <tr><td><strong>Cannibalization AI Fix</strong> (rileva + risolve in 1 click)</td><td>—</td><td>✓</td></tr>
                <tr><td><strong>Search Console Analytics integrata</strong> (top pagine, keyword, andamento)</td><td>—</td><td>✓</td></tr>
                <tr><td><strong>Local SEO AI</strong> (per business locali, schema LocalBusiness, GMB)</td><td>—</td><td>✓</td></tr>
                <tr><td><strong>Contenuti AEO</strong> (Q&A formato per ChatGPT/Claude/Perplexity)</td><td>—</td><td>✓</td></tr>
                <tr><td><strong>Garanzia rimborso 5 minuti</strong> per articolo non soddisfacente</td><td>—</td><td>✓</td></tr>
            </tbody>
        </table>
    </div>

    <!-- KILLER FEATURES (deep dive) -->
    <h2 class="orch-pro-section-title">Le 5 feature che ti faranno upgrade</h2>

    <div class="orch-pro-feature-deep">
        <div class="orch-pro-feat-icon">✨</div>
        <div class="orch-pro-feat-body">
            <h3>Genera articoli completi in 12 secondi</h3>
            <p>Inserisci una keyword o lascia che il sistema la peschi automaticamente dal tuo Keyword Research.
            L'AI scrive l'articolo (struttura H2/H3, paragrafi, FAQ, schema markup), genera l'immagine in evidenza,
            scrive meta title + description ottimizzati per CTR, applica la tua Brand Voice. Pubblichi come draft
            (default) o auto-publish. Tutto rimborsabile entro 5 minuti se non ti convince.</p>
        </div>
    </div>

    <div class="orch-pro-feature-deep">
        <div class="orch-pro-feat-icon">🎙️</div>
        <div class="orch-pro-feat-body">
            <h3>Brand Voice: gli articoli AI suonano come scritti da te</h3>
            <p>Il problema #1 dei plugin AI è che gli articoli "sembrano AI". Brand Voice analizza i tuoi ultimi
            10-20 articoli pubblicati, estrae il profilo (tono, lunghezza frasi, vocabolario, struttura, formality
            level), salva in DB e applica come system prompt a ogni generazione successiva. Risultato: gli articoli
            sono indistinguibili dai tuoi. Per agenzie: 1 voice-profile per cliente, switch al volo.</p>
        </div>
    </div>

    <div class="orch-pro-feature-deep">
        <div class="orch-pro-feat-icon">🤖</div>
        <div class="orch-pro-feat-body">
            <h3>Auto-Pilot: il sito che si scrive da solo</h3>
            <p>Configuri un job: "ogni 3 giorni pubblica un articolo da 1500 parole su keyword di nicchia
            'pellicole vetri', categoria Blog, autore Mario, pubblica in draft". Il cron WP gira da solo, pesca
            la keyword successiva dal pool, genera articolo + immagine + meta, crea il post WordPress in draft
            (o pubblica se opt-in). Sito sempre fresco senza che tu scriva. Trasforma Orchestra da tool one-shot
            a abbonamento always-on.</p>
        </div>
    </div>

    <div class="orch-pro-feature-deep">
        <div class="orch-pro-feat-icon">📈</div>
        <div class="orch-pro-feat-body">
            <h3>Search Console integrata: priorità data dai dati reali</h3>
            <p>Connetti Google Search Console una sola volta (anche centralizzato per agenzie). Vedi top pagine,
            top keyword, andamento clicks/impressions/CTR/posizione direttamente nel plugin. L'AI usa questi dati
            per priorizzare quali pagine ottimizzare ("hai 12 keyword in posizione 11-15 con tanto traffico:
            ottimizziamo queste prima"). Niente più jumping tra dashboard esterne.</p>
        </div>
    </div>

    <div class="orch-pro-feature-deep">
        <div class="orch-pro-feat-icon">🌳</div>
        <div class="orch-pro-feat-body">
            <h3>Cannibalization AI Fix: 1 click risolve duplicati keyword</h3>
            <p>Quando hai 3 pagine che si combattono per la stessa keyword, il ranking soffre. Il sistema rileva
            i conflitti, l'AI sceglie la pagina primaria (in base a clicks GSC + qualità contenuto), suggerisce
            keyword long-tail alternative per le secondarie, propone internal link verso la primaria. Un click
            apply: keyword aggiornate via meta tags + internal link inseriti nei post. Tutto reversibile via
            snapshot.</p>
        </div>
    </div>

    <!-- PRICING -->
    <h2 class="orch-pro-section-title">Pricing</h2>
    <p class="orch-pro-section-sub">Pagamento una tantum. No subscription. No costi nascosti.</p>

    <div class="orch-pro-pricing">
        <div class="orch-pro-plan">
            <div class="orch-pro-plan-name">Starter</div>
            <div class="orch-pro-plan-price">€69</div>
            <div class="orch-pro-plan-meta">una tantum · 1 sito</div>
            <ul class="orch-pro-plan-feats">
                <li>500 crediti AI inclusi</li>
                <li>Generazione articoli AI</li>
                <li>Meta Tags AI</li>
                <li>Brand Voice Learning</li>
                <li>Update gratuiti 1 anno</li>
            </ul>
            <a href="<?php echo esc_url($seo_aeo_upgrade_url); ?>" target="_blank" rel="noopener" class="orch-pro-plan-cta">Prendi Starter</a>
        </div>

        <div class="orch-pro-plan orch-pro-plan-featured">
            <div class="orch-pro-plan-badge">Più scelto</div>
            <div class="orch-pro-plan-name">Professional</div>
            <div class="orch-pro-plan-price">€129</div>
            <div class="orch-pro-plan-meta">una tantum · 3 siti</div>
            <ul class="orch-pro-plan-feats">
                <li><strong>2000 crediti AI inclusi</strong></li>
                <li>Tutto di Starter +</li>
                <li>Auto-Pilot Scheduler</li>
                <li>Keyword Research Autopilot</li>
                <li>GSC Analytics integrata</li>
                <li>Image SEO bulk</li>
                <li>AI 404 Suggester</li>
                <li>Cannibalization AI Fix</li>
                <li>Calendario contenuti</li>
            </ul>
            <a href="<?php echo esc_url($seo_aeo_upgrade_url); ?>" target="_blank" rel="noopener" class="orch-pro-plan-cta orch-pro-plan-cta-featured">Prendi Professional</a>
        </div>

        <div class="orch-pro-plan">
            <div class="orch-pro-plan-name">Agency</div>
            <div class="orch-pro-plan-price">€349</div>
            <div class="orch-pro-plan-meta">una tantum · 10 siti</div>
            <ul class="orch-pro-plan-feats">
                <li>10000 crediti AI inclusi</li>
                <li>Tutto di Professional +</li>
                <li>Multi-sito (10 domini)</li>
                <li>Brand Voice multi-cliente</li>
                <li>White-label opzionale</li>
                <li>Priority support</li>
            </ul>
            <a href="<?php echo esc_url($seo_aeo_upgrade_url); ?>" target="_blank" rel="noopener" class="orch-pro-plan-cta">Prendi Agency</a>
        </div>
    </div>

    <p class="orch-pro-pricing-foot">
        Crediti aggiuntivi acquistabili in qualsiasi momento (~€1 ogni 50 crediti).
        Un articolo completo costa 25 crediti. Una rigenerazione meta tags 5 crediti.
    </p>

    <!-- FAQ -->
    <h2 class="orch-pro-section-title">Domande frequenti</h2>

    <div class="orch-pro-faq">
        <details class="orch-pro-faq-item">
            <summary>Devo disinstallare la versione gratuita?</summary>
            <p>No. La Pro è un upgrade in-place: dopo l'acquisto inserisci la licenza nelle Impostazioni e il plugin
            sblocca tutte le feature. Tutti i dati (redirect, sitemap settings, ecc.) restano intatti.</p>
        </details>

        <details class="orch-pro-faq-item">
            <summary>Cos'è un "credito AI"?</summary>
            <p>I crediti misurano il consumo di chiamate AI. Un articolo completo = 25 crediti. Una generazione meta
            tags = 5 crediti. Una keyword research = 10 crediti. I piani Pro includono crediti sufficienti per
            mesi di lavoro normale. Quando finiscono, ricarichi a €1/50 crediti.</p>
        </details>

        <details class="orch-pro-faq-item">
            <summary>Funziona se non ho mai usato Yoast/RankMath?</summary>
            <p>Sì. La Free version (che hai già installata) non richiede altri plugin SEO. Genera title, meta description,
            OpenGraph, Twitter Cards, sitemap.xml e schema.org direttamente dal contenuto WordPress. La Pro aggiunge
            generazione AI sopra a quella base.</p>
        </details>

        <details class="orch-pro-faq-item">
            <summary>Come funziona la garanzia rimborso 5 minuti?</summary>
            <p>Quando l'AI genera un articolo, hai 5 minuti per cliccare "Rimborsa" e i crediti spesi tornano nel wallet.
            Massimo 3 rimborsi al giorno (per evitare abusi). Se l'articolo non ti convince, non lo paghi.</p>
        </details>

        <details class="orch-pro-faq-item">
            <summary>Posso usare la stessa licenza su più siti?</summary>
            <p>Starter: 1 sito. Professional: 3 siti (gestibili dal pannello unico). Agency: 10 siti gestibili
            dal pannello unico. Per più di 10 siti, contattaci per piani enterprise.</p>
        </details>
    </div>

    <!-- FINAL CTA -->
    <div class="orch-pro-final-cta">
        <h2>Prova la Pro per 7 giorni. Gratis.</h2>
        <p>Niente carta di credito richiesta. Cancellazione in 1 click. Se non ti piace, torni alla Free senza perdere niente.</p>
        <a href="<?php echo esc_url($seo_aeo_start_url); ?>" target="_blank" rel="noopener" class="orch-pro-btn-primary orch-pro-btn-large">🚀 Inizia trial gratis ora</a>
        <p class="orch-pro-final-trust">Già usato da agenzie SEO italiane su 47+ siti clienti.</p>
    </div>

</div>

<style>
.orch-pro-page { max-width: 1200px; margin: 20px 0; }

.orch-pro-hero {
    background: linear-gradient(135deg, #0A0E27 0%, #0055FF 50%, #00E5FF 100%);
    color: #fff;
    border-radius: 16px;
    padding: 56px 48px;
    text-align: center;
    box-shadow: 0 12px 36px rgba(0, 85, 255, 0.22);
    margin-bottom: 32px;
}
.orch-pro-hero-eyebrow {
    font-size: 12px;
    letter-spacing: 3px;
    text-transform: uppercase;
    opacity: 0.85;
    margin-bottom: 14px;
}
.orch-pro-hero-title {
    color: #fff;
    font-size: 42px;
    font-weight: 800;
    margin: 0 0 18px;
    line-height: 1.1;
    letter-spacing: -0.5px;
}
.orch-pro-hero-sub {
    font-size: 17px;
    line-height: 1.55;
    margin: 0 auto 28px;
    max-width: 760px;
    opacity: 0.95;
}
.orch-pro-hero-cta-row {
    display: flex;
    gap: 14px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 22px;
}
.orch-pro-btn-primary {
    display: inline-block;
    padding: 16px 32px;
    background: #fff;
    color: #0055FF;
    font-weight: 700;
    font-size: 15px;
    border-radius: 10px;
    text-decoration: none;
    transition: transform 0.15s, box-shadow 0.15s;
}
.orch-pro-btn-primary:hover { transform: translateY(-2px); color: #0055FF; box-shadow: 0 6px 18px rgba(0,0,0,0.18); }
.orch-pro-btn-ghost {
    display: inline-block;
    padding: 16px 28px;
    background: transparent;
    color: #fff;
    font-weight: 600;
    font-size: 15px;
    border-radius: 10px;
    text-decoration: none;
    border: 1.5px solid rgba(255,255,255,0.4);
    transition: background 0.15s;
}
.orch-pro-btn-ghost:hover { background: rgba(255,255,255,0.12); color: #fff; }
.orch-pro-btn-large { font-size: 17px; padding: 18px 42px; }
.orch-pro-hero-trust {
    font-size: 13px;
    opacity: 0.85;
}

.orch-pro-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 40px;
}
.orch-pro-stat {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
    text-align: center;
}
.orch-pro-stat-num {
    font-size: 36px;
    font-weight: 800;
    color: #0055FF;
    margin-bottom: 6px;
    line-height: 1;
}
.orch-pro-stat-label {
    font-size: 13px;
    color: #475569;
    line-height: 1.45;
}

.orch-pro-section-title {
    font-size: 28px;
    margin: 48px 0 6px;
    color: #0a0e27;
    font-weight: 800;
    letter-spacing: -0.3px;
}
.orch-pro-section-sub {
    font-size: 15px;
    color: #64748b;
    margin-bottom: 22px;
}

.orch-pro-compare-wrap {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 40px;
}
.orch-pro-compare {
    width: 100%;
    border-collapse: collapse;
}
.orch-pro-compare th, .orch-pro-compare td {
    padding: 14px 18px;
    text-align: left;
    border-bottom: 1px solid #f1f5f9;
}
.orch-pro-compare th { background: #f8fafc; font-weight: 700; font-size: 14px; color: #0f172a; }
.orch-pro-compare td:nth-child(2), .orch-pro-compare td:nth-child(3),
.orch-pro-compare th:nth-child(2), .orch-pro-compare th:nth-child(3) {
    text-align: center;
    width: 130px;
}
.orch-pro-col-pro { background: linear-gradient(135deg, #0055FF, #00E5FF); color: #fff !important; }
.orch-pro-tag-free { display: block; font-size: 11px; font-weight: 500; opacity: 0.6; margin-top: 4px; }
.orch-pro-tag-pro  { display: block; font-size: 11px; font-weight: 500; opacity: 0.95; margin-top: 4px; }
.orch-pro-divider td { background: #f1f5f9; text-align: center !important; font-size: 12px; color: #64748b; font-style: italic; padding: 8px; }

.orch-pro-feature-deep {
    display: flex;
    gap: 22px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px 28px;
    margin-bottom: 14px;
    align-items: flex-start;
}
.orch-pro-feat-icon {
    font-size: 36px;
    flex-shrink: 0;
}
.orch-pro-feat-body h3 {
    font-size: 18px;
    margin: 0 0 8px;
    color: #0a0e27;
}
.orch-pro-feat-body p {
    font-size: 14px;
    line-height: 1.6;
    color: #475569;
    margin: 0;
}

.orch-pro-pricing {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 16px;
}
.orch-pro-plan {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 28px 24px;
    position: relative;
    text-align: center;
}
.orch-pro-plan-featured {
    border: 2px solid #0055FF;
    box-shadow: 0 8px 24px rgba(0,85,255,0.12);
    transform: scale(1.02);
}
.orch-pro-plan-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: #0055FF;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 14px;
    border-radius: 12px;
    letter-spacing: 1px;
    text-transform: uppercase;
}
.orch-pro-plan-name { font-size: 16px; font-weight: 700; color: #0a0e27; margin-bottom: 6px; }
.orch-pro-plan-price { font-size: 44px; font-weight: 800; color: #0055FF; margin-bottom: 4px; line-height: 1; }
.orch-pro-plan-meta { font-size: 12px; color: #64748b; margin-bottom: 18px; }
.orch-pro-plan-feats {
    list-style: none;
    padding: 0 0 18px;
    margin: 0 0 18px;
    border-bottom: 1px solid #f1f5f9;
    text-align: left;
}
.orch-pro-plan-feats li {
    padding: 6px 0 6px 24px;
    position: relative;
    font-size: 13px;
    color: #334155;
    line-height: 1.5;
}
.orch-pro-plan-feats li::before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #10B981;
    font-weight: 700;
}
.orch-pro-plan-cta {
    display: block;
    padding: 12px 18px;
    background: #f1f5f9;
    color: #0a0e27;
    font-weight: 700;
    font-size: 14px;
    border-radius: 8px;
    text-decoration: none;
    transition: background 0.15s;
}
.orch-pro-plan-cta:hover { background: #e2e8f0; color: #0a0e27; }
.orch-pro-plan-cta-featured { background: #0055FF; color: #fff; }
.orch-pro-plan-cta-featured:hover { background: #0044cc; color: #fff; }

.orch-pro-pricing-foot {
    text-align: center;
    font-size: 13px;
    color: #64748b;
    margin: 0 0 30px;
    font-style: italic;
}

.orch-pro-faq { margin-bottom: 40px; }
.orch-pro-faq-item {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px 22px;
    margin-bottom: 10px;
}
.orch-pro-faq-item summary {
    cursor: pointer;
    font-weight: 600;
    font-size: 15px;
    color: #0a0e27;
}
.orch-pro-faq-item p {
    margin: 12px 0 0;
    font-size: 14px;
    line-height: 1.6;
    color: #475569;
}

.orch-pro-final-cta {
    background: linear-gradient(135deg, #0A0E27 0%, #0055FF 100%);
    color: #fff;
    border-radius: 16px;
    padding: 48px 32px;
    text-align: center;
}
.orch-pro-final-cta h2 {
    color: #fff;
    font-size: 32px;
    font-weight: 800;
    margin: 0 0 12px;
}
.orch-pro-final-cta p {
    color: rgba(255,255,255,0.95);
    font-size: 15px;
    margin: 0 0 24px;
    line-height: 1.55;
}
.orch-pro-final-trust {
    font-size: 13px;
    margin-top: 18px !important;
    opacity: 0.7;
}

@media (max-width: 768px) {
    .orch-pro-hero-title { font-size: 30px; }
    .orch-pro-stats, .orch-pro-pricing { grid-template-columns: 1fr; }
    .orch-pro-plan-featured { transform: none; }
    .orch-pro-feature-deep { flex-direction: column; }
}
</style>

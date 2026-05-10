<?php
/*
 * Template: Analytics page (since 3.32.0)
 *
 * Pagina dedicata che aggrega:
 *  - GSC summary 7d/28d/90d (clicks, impressions, ctr, position)
 *  - Sparkline trend per ogni metrica
 *  - Top Queries (con isNew badge)
 *  - Top Pages (con expand → keywords lazy)
 *  - 5 KPI esclusivi Orchestra (AI articles, Brand Voice impact,
 *    Redirect rescues, Auto-Pilot ROI, Meta freshness)
 *
 * Backend endpoint atteso: POST /api/gsc/analytics body {license_key, period}.
 * Lazy keywords endpoint: POST /api/gsc/page-keywords.
 *
 * Color logic critica (NON cambiare):
 *  - clicks/impressions/ctr: ↑ è verde (migliora), ↓ rosso
 *  - position: il backend ritorna positionChange in "spots" → noi invertiamo
 *    semanticamente. Position SCESA (numero più piccolo) = MIGLIORATO = verde.
 *    Position SALITA (numero più grande) = peggiorato = rosso.
 *  - isNew badge: keyword/page mai vista nel periodo precedente.
 */
if (!defined('ABSPATH')) exit;

$plugin_version = defined('SEO_AEO_VERSION') ? SEO_AEO_VERSION : '?';
$T = function($s) { return class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t($s)) : esc_html($s); };
$Traw = function($s) { return class_exists('SEO_AEO_T') ? SEO_AEO_T::t($s) : $s; };
?>

<div class="wrap orch-analytics-wrap">

    <!-- HERO -->
    <section class="orch-analytics-hero">
        <div class="orch-analytics-hero-inner">
            <div class="orch-analytics-hero-text">
                <div class="orch-analytics-hero-eyebrow">AEO Orchestra · v<?php echo esc_html($plugin_version); ?></div>
                <h1 class="orch-analytics-hero-title">
                    <span class="orch-analytics-hero-icon">📈</span>
                    <?php echo esc_html(SEO_AEO_T::t('Analytics')); ?>
                </h1>
                <p class="orch-analytics-hero-sub"><?php echo esc_html(SEO_AEO_T::t('Insights da Google Search Console + metriche esclusive Orchestra.')); ?></p>
            </div>
            <div class="orch-analytics-hero-actions">
                <div class="orch-period-toggle" role="group" aria-label="<?php echo esc_attr($Traw('Periodo')); ?>">
                    <button type="button" data-period="last7Days"><?php echo esc_html(SEO_AEO_T::t('7g')); ?></button>
                    <button type="button" data-period="last28Days" class="active"><?php echo esc_html(SEO_AEO_T::t('28g')); ?></button>
                    <button type="button" data-period="last90Days"><?php echo esc_html(SEO_AEO_T::t('90g')); ?></button>
                </div>
            </div>
        </div>
    </section>

    <!-- STATUS BANNER -->
    <div id="orch-analytics-status" class="orch-analytics-status orch-analytics-status-loading">
        <span class="orch-analytics-status-icon">⏳</span>
        <span class="orch-analytics-status-text"><?php echo esc_html(SEO_AEO_T::t('Caricamento dati in corso…')); ?></span>
    </div>

    <!-- GLOBAL ERROR BANNER (oculto by default) -->
    <div id="orch-analytics-error" class="orch-analytics-error" style="display:none;"></div>

    <!-- KPI HERO 6 CARDS (3+3 grid) -->
    <section class="orch-analytics-kpi-grid">
        <!-- Visitors -->
        <article class="orch-kpi-card" data-metric="clicks">
            <div class="orch-kpi-card-head">
                <span class="orch-kpi-card-label"><?php echo esc_html(SEO_AEO_T::t('Visitatori')); ?></span>
                <span class="orch-kpi-card-delta" id="orch-kpi-clicks-delta">—</span>
            </div>
            <div class="orch-kpi-card-value" id="orch-kpi-clicks-value">—</div>
            <div class="orch-kpi-card-spark" id="orch-kpi-clicks-spark"></div>
        </article>

        <!-- Impressions -->
        <article class="orch-kpi-card" data-metric="impressions">
            <div class="orch-kpi-card-head">
                <span class="orch-kpi-card-label"><?php echo esc_html(SEO_AEO_T::t('Impressions')); ?></span>
                <span class="orch-kpi-card-delta" id="orch-kpi-impressions-delta">—</span>
            </div>
            <div class="orch-kpi-card-value" id="orch-kpi-impressions-value">—</div>
            <div class="orch-kpi-card-spark" id="orch-kpi-impressions-spark"></div>
        </article>

        <!-- Click rate (CTR) -->
        <article class="orch-kpi-card" data-metric="ctr">
            <div class="orch-kpi-card-head">
                <span class="orch-kpi-card-label"><?php echo esc_html(SEO_AEO_T::t('Click rate')); ?></span>
                <span class="orch-kpi-card-delta" id="orch-kpi-ctr-delta">—</span>
            </div>
            <div class="orch-kpi-card-value" id="orch-kpi-ctr-value">—</div>
            <div class="orch-kpi-card-spark" id="orch-kpi-ctr-spark"></div>
        </article>

        <!-- Avg position -->
        <article class="orch-kpi-card" data-metric="position">
            <div class="orch-kpi-card-head">
                <span class="orch-kpi-card-label"><?php echo esc_html(SEO_AEO_T::t('Posizione media')); ?></span>
                <span class="orch-kpi-card-delta" id="orch-kpi-position-delta">—</span>
            </div>
            <div class="orch-kpi-card-value" id="orch-kpi-position-value">—</div>
            <div class="orch-kpi-card-spark" id="orch-kpi-position-spark"></div>
        </article>

        <!-- AI Articles in TOP 10 -->
        <article class="orch-kpi-card orch-kpi-card-orchestra" data-metric="ai_top10">
            <div class="orch-kpi-card-head">
                <span class="orch-kpi-card-label">🤖 <?php echo esc_html(SEO_AEO_T::t('Articoli AI nel TOP 10')); ?></span>
                <span class="orch-kpi-card-delta" id="orch-kpi-aitop10-delta">—</span>
            </div>
            <div class="orch-kpi-card-value" id="orch-kpi-aitop10-value">—</div>
            <div class="orch-kpi-card-foot" id="orch-kpi-aitop10-foot"><?php echo esc_html(SEO_AEO_T::t('articoli')); ?></div>
        </article>

        <!-- Brand Voice impact -->
        <article class="orch-kpi-card orch-kpi-card-orchestra" data-metric="brand_voice">
            <div class="orch-kpi-card-head">
                <span class="orch-kpi-card-label">🎙️ <?php echo esc_html(SEO_AEO_T::t('Impatto Brand Voice')); ?></span>
                <span class="orch-kpi-card-delta" id="orch-kpi-bv-delta">—</span>
            </div>
            <div class="orch-kpi-card-value" id="orch-kpi-bv-value">—</div>
            <div class="orch-kpi-card-foot" id="orch-kpi-bv-foot"><?php echo esc_html(SEO_AEO_T::t('CTR vs senza Brand Voice')); ?></div>
        </article>
    </section>

    <!-- TOP KEYWORDS -->
    <section class="orch-analytics-block" id="orch-analytics-queries">
        <header class="orch-analytics-block-head">
            <h2 class="orch-analytics-block-title"><?php echo esc_html(SEO_AEO_T::t('Top Keywords')); ?></h2>
            <p class="orch-analytics-block-sub"><?php echo esc_html(SEO_AEO_T::t('Le keyword che portano più clic. NEW = entrata nuova nel periodo.')); ?></p>
        </header>
        <div class="orch-analytics-table-wrap">
            <table class="orch-analytics-table" id="orch-queries-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php echo esc_html(SEO_AEO_T::t('Keyword')); ?></th>
                        <th class="num"><?php echo esc_html(SEO_AEO_T::t('Clicks')); ?></th>
                        <th class="num"><?php echo esc_html(SEO_AEO_T::t('Posizione')); ?></th>
                        <th class="num"><?php echo esc_html(SEO_AEO_T::t('Impressions')); ?></th>
                        <th class="num"><?php echo esc_html(SEO_AEO_T::t('CTR')); ?></th>
                    </tr>
                </thead>
                <tbody id="orch-queries-tbody">
                    <tr class="orch-empty-row"><td colspan="6"><?php echo esc_html(SEO_AEO_T::t('Caricamento dati in corso…')); ?></td></tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="orch3-btn orch3-btn-ghost" id="orch-queries-toggle-all" style="display:none;"><?php echo esc_html(SEO_AEO_T::t('Mostra tutte le keyword')); ?></button>
    </section>

    <!-- TOP PAGES -->
    <section class="orch-analytics-block" id="orch-analytics-pages">
        <header class="orch-analytics-block-head">
            <h2 class="orch-analytics-block-title"><?php echo esc_html(SEO_AEO_T::t('Top Pages')); ?></h2>
            <p class="orch-analytics-block-sub"><?php echo esc_html(SEO_AEO_T::t('Le pagine con più clic. Espandi una riga per vedere le keyword di traino.')); ?></p>
        </header>
        <div class="orch-analytics-pages-list" id="orch-pages-list">
            <div class="orch-empty-row"><?php echo esc_html(SEO_AEO_T::t('Caricamento dati in corso…')); ?></div>
        </div>
    </section>

    <!-- ORCHESTRA DIFFERENTIATORS (5 KPI cards) -->
    <section class="orch-analytics-block orch-analytics-orchestra-block">
        <header class="orch-analytics-block-head">
            <h2 class="orch-analytics-block-title">🎯 <?php echo esc_html(SEO_AEO_T::t('Performance esclusive Orchestra')); ?></h2>
            <p class="orch-analytics-block-sub"><?php echo esc_html(SEO_AEO_T::t('Metriche che nessun altro plugin SEO può calcolare: misurano l\'impatto reale di Orchestra sul tuo traffico.')); ?></p>
        </header>
        <div class="orch-analytics-orchestra-grid">

            <article class="orch-orchestra-card" data-kpi="ai_articles_top10">
                <div class="orch-orchestra-card-icon">🤖</div>
                <div class="orch-orchestra-card-body">
                    <h3 class="orch-orchestra-card-title"><?php echo esc_html(SEO_AEO_T::t('Articoli AI nel TOP 10 GSC')); ?></h3>
                    <p class="orch-orchestra-card-desc"><?php echo esc_html(SEO_AEO_T::t('Quanti articoli generati con l\'AI Orchestra sono entrati nelle prime 10 posizioni Google.')); ?></p>
                    <div class="orch-orchestra-card-value" id="orch-od-ai-value">—</div>
                    <div class="orch-orchestra-card-foot" id="orch-od-ai-foot"><?php echo esc_html(SEO_AEO_T::t('Dati insufficienti')); ?></div>
                </div>
            </article>

            <article class="orch-orchestra-card" data-kpi="brand_voice_impact">
                <div class="orch-orchestra-card-icon">🎙️</div>
                <div class="orch-orchestra-card-body">
                    <h3 class="orch-orchestra-card-title"><?php echo esc_html(SEO_AEO_T::t('Impatto Brand Voice')); ?></h3>
                    <p class="orch-orchestra-card-desc"><?php echo esc_html(SEO_AEO_T::t('CTR medio degli articoli generati con Brand Voice attiva vs articoli senza profilo.')); ?></p>
                    <div class="orch-orchestra-card-value" id="orch-od-bv-value">—</div>
                    <div class="orch-orchestra-card-foot" id="orch-od-bv-foot"><?php echo esc_html(SEO_AEO_T::t('Dati insufficienti')); ?></div>
                </div>
            </article>

            <article class="orch-orchestra-card" data-kpi="redirect_rescues">
                <div class="orch-orchestra-card-icon">🔀</div>
                <div class="orch-orchestra-card-body">
                    <h3 class="orch-orchestra-card-title"><?php echo esc_html(SEO_AEO_T::t('Redirect Manager Rescues')); ?></h3>
                    <p class="orch-orchestra-card-desc"><?php echo esc_html(SEO_AEO_T::t('Click salvati grazie ai redirect 301/302 attivi. Senza questi sarebbero stati 404.')); ?></p>
                    <div class="orch-orchestra-card-value" id="orch-od-rr-value">—</div>
                    <div class="orch-orchestra-card-foot" id="orch-od-rr-foot"><?php echo esc_html(SEO_AEO_T::t('Dati insufficienti')); ?></div>
                </div>
            </article>

            <article class="orch-orchestra-card" data-kpi="autopilot_roi">
                <div class="orch-orchestra-card-icon">⚡</div>
                <div class="orch-orchestra-card-body">
                    <h3 class="orch-orchestra-card-title"><?php echo esc_html(SEO_AEO_T::t('Auto-Pilot ROI')); ?></h3>
                    <p class="orch-orchestra-card-desc"><?php echo esc_html(SEO_AEO_T::t('Clic medi per articolo Auto-Pilot vs articoli scritti manualmente.')); ?></p>
                    <div class="orch-orchestra-card-value" id="orch-od-ap-value">—</div>
                    <div class="orch-orchestra-card-foot" id="orch-od-ap-foot"><?php echo esc_html(SEO_AEO_T::t('Dati insufficienti')); ?></div>
                </div>
            </article>

            <article class="orch-orchestra-card" data-kpi="meta_freshness">
                <div class="orch-orchestra-card-icon">🏷️</div>
                <div class="orch-orchestra-card-body">
                    <h3 class="orch-orchestra-card-title"><?php echo esc_html(SEO_AEO_T::t('Meta Freshness Score')); ?></h3>
                    <p class="orch-orchestra-card-desc"><?php echo esc_html(SEO_AEO_T::t('Percentuale di pagine con meta tags ottimizzati e aggiornati negli ultimi 6 mesi.')); ?></p>
                    <div class="orch-orchestra-card-value" id="orch-od-mf-value">—</div>
                    <div class="orch-orchestra-card-foot" id="orch-od-mf-foot"><?php echo esc_html(SEO_AEO_T::t('Dati insufficienti')); ?></div>
                </div>
            </article>

        </div>
    </section>

</div>

<?php ob_start(); ?>
/* ───────── Analytics page (3.32.0) ───────── */
.orch-analytics-wrap { max-width: 1280px; }

.orch-analytics-hero {
    background: linear-gradient(135deg, #0A0E27 0%, #0055FF 50%, #00E5FF 100%);
    color: #fff;
    border-radius: 16px;
    padding: 28px 32px;
    margin: 16px 0 20px;
    box-shadow: 0 8px 24px rgba(10,14,39,0.18);
}
.orch-analytics-hero-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    flex-wrap: wrap;
}
.orch-analytics-hero-eyebrow {
    font-size: 11px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    opacity: 0.8;
    margin-bottom: 6px;
}
.orch-analytics-hero-title {
    font-size: 28px;
    font-weight: 800;
    margin: 0 0 6px;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 12px;
}
.orch-analytics-hero-icon { font-size: 32px; }
.orch-analytics-hero-sub {
    font-size: 14px;
    opacity: 0.88;
    margin: 0;
    max-width: 600px;
}

.orch-period-toggle {
    display: inline-flex;
    background: rgba(255,255,255,0.12);
    border-radius: 10px;
    padding: 4px;
    backdrop-filter: blur(8px);
}
.orch-period-toggle button {
    background: transparent;
    border: 0;
    color: rgba(255,255,255,0.85);
    font-size: 13px;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 8px;
    cursor: pointer;
    transition: all .15s ease;
    min-width: 56px;
}
.orch-period-toggle button:hover { background: rgba(255,255,255,0.08); color: #fff; }
.orch-period-toggle button.active {
    background: #fff;
    color: #0055FF;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}

.orch-analytics-status {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 13px;
    margin-bottom: 16px;
}
.orch-analytics-status-loading { background: #eff6ff; color: #1e3a8a; border: 1px solid #bfdbfe; }
.orch-analytics-status-cached  { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.orch-analytics-status-fresh   { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.orch-analytics-status-icon { font-size: 16px; }

.orch-analytics-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
    padding: 12px 14px;
    border-radius: 10px;
    margin-bottom: 16px;
    font-size: 13px;
}

/* KPI hero 6-card grid 3+3 */
.orch-analytics-kpi-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 28px;
}
@media (max-width: 980px) {
    .orch-analytics-kpi-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .orch-analytics-kpi-grid { grid-template-columns: 1fr; }
}

.orch-kpi-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 18px 20px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    transition: box-shadow .15s ease, transform .15s ease;
}
.orch-kpi-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); transform: translateY(-1px); }

.orch-kpi-card-orchestra {
    background: linear-gradient(180deg, #fafbff, #fff);
    border-color: #c7d2fe;
}

.orch-kpi-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}
.orch-kpi-card-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6b7280;
    font-weight: 600;
}
.orch-kpi-card-value {
    font-size: 28px;
    font-weight: 800;
    color: #0A0E27;
    line-height: 1.1;
    font-family: -apple-system, BlinkMacSystemFont, "Satoshi", system-ui, sans-serif;
}
.orch-kpi-card-foot {
    font-size: 11px;
    color: #6b7280;
    margin-top: 6px;
}
.orch-kpi-card-delta {
    font-size: 12px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 12px;
    background: #f3f4f6;
    color: #6b7280;
}
.orch-kpi-card-delta.delta-up   { background: #dcfce7; color: #166534; }
.orch-kpi-card-delta.delta-down { background: #fee2e2; color: #991b1b; }
.orch-kpi-card-delta.delta-flat { background: #f3f4f6; color: #6b7280; }
.orch-kpi-card-spark { margin-top: 12px; min-height: 32px; }

/* TABLES + PAGES */
.orch-analytics-block {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}
.orch-analytics-block-head { margin-bottom: 12px; }
.orch-analytics-block-title { margin: 0 0 4px; font-size: 18px; font-weight: 700; color: #0A0E27; }
.orch-analytics-block-sub { margin: 0; color: #6b7280; font-size: 13px; }

.orch-analytics-table-wrap { overflow-x: auto; }
.orch-analytics-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.orch-analytics-table th {
    text-align: left;
    padding: 10px 8px;
    border-bottom: 1px solid #e5e7eb;
    font-weight: 600;
    color: #475569;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.05em;
}
.orch-analytics-table th.num,
.orch-analytics-table td.num { text-align: right; }
.orch-analytics-table td {
    padding: 10px 8px;
    border-bottom: 1px solid #f3f4f6;
    color: #1f2937;
    vertical-align: middle;
}
.orch-analytics-table tr:last-child td { border-bottom: 0; }
.orch-analytics-table tr.orch-row-hidden { display: none; }
.orch-analytics-table .orch-rank { color: #94a3b8; font-variant-numeric: tabular-nums; }
.orch-analytics-table .orch-kw { font-weight: 500; color: #0A0E27; }
.orch-analytics-table .orch-num-with-delta { display: inline-flex; align-items: baseline; gap: 6px; justify-content: flex-end; }
.orch-analytics-table .orch-delta-pct { font-size: 11px; font-weight: 600; }
.orch-analytics-table .orch-delta-pct.up   { color: #166534; }
.orch-analytics-table .orch-delta-pct.down { color: #991b1b; }
.orch-analytics-table .orch-delta-pct.flat { color: #94a3b8; }

.orch-empty-row { padding: 20px; text-align: center; color: #94a3b8; font-size: 13px; }

.orch-badge-new {
    background: #FEF3C7;
    color: #92400E;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
    margin-left: 6px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    vertical-align: middle;
}

/* PAGES list (expandable rows) */
.orch-analytics-pages-list { display: flex; flex-direction: column; gap: 4px; }
.orch-page-row {
    padding: 12px 14px;
    border-bottom: 1px solid #f3f4f6;
    cursor: pointer;
    transition: background .15s ease;
    border-radius: 8px;
}
.orch-page-row:hover { background: #f9fafb; }
.orch-page-row.expanded { background: #f9fafb; }
.orch-page-row-head {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.orch-page-row-caret {
    font-size: 12px;
    color: #94a3b8;
    transition: transform .15s ease;
    display: inline-block;
    width: 14px;
    text-align: center;
}
.orch-page-row.expanded .orch-page-row-caret { transform: rotate(90deg); }
.orch-page-row-url {
    flex: 1 1 auto;
    color: #0055FF;
    font-size: 13px;
    word-break: break-all;
    text-decoration: none;
}
.orch-page-row-url:hover { text-decoration: underline; }
.orch-page-row-stats {
    display: flex;
    gap: 14px;
    font-size: 12px;
    color: #475569;
    flex-wrap: wrap;
}
.orch-page-row-stats strong { color: #0A0E27; font-variant-numeric: tabular-nums; }
.orch-page-keywords {
    margin-top: 12px;
    margin-left: 24px;
    padding: 10px 12px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}
.orch-page-keywords-loading { color: #94a3b8; font-size: 12px; padding: 6px 0; }
.orch-page-keywords-table { width: 100%; font-size: 12px; }
.orch-page-keywords-table th, .orch-page-keywords-table td {
    padding: 6px 8px;
    border-bottom: 1px solid #f3f4f6;
    text-align: left;
}
.orch-page-keywords-table th.num, .orch-page-keywords-table td.num { text-align: right; }
.orch-page-keywords-empty { color: #94a3b8; font-size: 12px; padding: 6px 0; }

/* Orchestra differentiator cards */
.orch-analytics-orchestra-block {
    background: linear-gradient(180deg, #fafbff, #fff);
    border-color: #c7d2fe;
}
.orch-analytics-orchestra-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 14px;
}
.orch-orchestra-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 18px;
    display: flex;
    gap: 14px;
    align-items: flex-start;
}
.orch-orchestra-card-icon {
    font-size: 28px;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #eff6ff;
    border-radius: 10px;
    flex-shrink: 0;
}
.orch-orchestra-card-body { flex: 1 1 auto; min-width: 0; }
.orch-orchestra-card-title { margin: 0 0 4px; font-size: 14px; font-weight: 700; color: #0A0E27; }
.orch-orchestra-card-desc { margin: 0 0 10px; font-size: 12px; color: #6b7280; line-height: 1.4; }
.orch-orchestra-card-value {
    font-size: 22px;
    font-weight: 800;
    color: #0055FF;
    line-height: 1.1;
}
.orch-orchestra-card-value.value-empty { color: #94a3b8; }
.orch-orchestra-card-foot { font-size: 11px; color: #6b7280; margin-top: 4px; }
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

<script type="text/javascript">
jQuery(function($) {
    'use strict';

    var Analytics = {
        currentPeriod: 'last28Days',
        cache: {},

        // ───── i18n helpers ─────
        i18n: function(k) {
            if (window.seoAeoOrchestra && seoAeoOrchestra.i18n && seoAeoOrchestra.i18n[k]) {
                return seoAeoOrchestra.i18n[k];
            }
            return k;
        },

        init: function() {
            var self = this;
            $('.orch-period-toggle button').on('click', function() {
                var p = $(this).data('period');
                if (!p || p === self.currentPeriod) return;
                $('.orch-period-toggle button').removeClass('active');
                $(this).addClass('active');
                self.currentPeriod = p;
                self.fetch();
            });

            // Page row expand
            $(document).on('click', '.orch-page-row', function(e) {
                if ($(e.target).is('a')) return; // click on URL link → don't expand
                var $row = $(this);
                var url = $row.data('page-url');
                if (!url) return;
                if ($row.hasClass('expanded')) {
                    $row.removeClass('expanded');
                    $row.find('.orch-page-keywords').slideUp(140, function() { $(this).remove(); });
                } else {
                    $row.addClass('expanded');
                    self.loadPageKeywords($row, url);
                }
            });

            this.fetch();
        },

        fetch: function() {
            var self = this;
            $('#orch-analytics-error').hide().empty();
            self.setStatus('loading', self.i18n('Caricamento dati in corso…'));

            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_analytics_fetch',
                nonce: seoAeoOrchestra.nonce,
                period: self.currentPeriod
            }).done(function(resp) {
                if (!resp || resp.error) {
                    self.showError(resp && resp.error ? resp.error : self.i18n('Errore sconosciuto'));
                    return;
                }
                self.cache[self.currentPeriod] = resp;
                self.render(resp);
            }).fail(function(xhr, status, err) {
                self.showError(self.i18n('Errore di rete') + ': ' + (err || status));
            });
        },

        showError: function(msg) {
            $('#orch-analytics-error').text(msg).show();
            this.setStatus('fresh', '');
            $('#orch-analytics-status').hide();
        },

        setStatus: function(type, text) {
            var $s = $('#orch-analytics-status');
            $s.show().removeClass('orch-analytics-status-loading orch-analytics-status-cached orch-analytics-status-fresh');
            $s.addClass('orch-analytics-status-' + type);
            var icon = type === 'cached' ? '✅' : (type === 'fresh' ? '🔄' : '⏳');
            $s.find('.orch-analytics-status-icon').text(icon);
            $s.find('.orch-analytics-status-text').text(text);
        },

        render: function(data) {
            // Status banner: cachedAt vs liveFetched
            if (data.cached_at) {
                var d = new Date(data.cached_at);
                var when = isNaN(d.getTime()) ? data.cached_at : d.toLocaleString();
                var nextRefresh = data.next_refresh_in ? (' · ' + this.i18n('Prossimo refresh fra') + ' ' + data.next_refresh_in) : '';
                this.setStatus('cached', this.i18n('Cache aggiornata al') + ' ' + when + nextRefresh);
            } else {
                this.setStatus('fresh', this.i18n('Dati live appena recuperati.'));
            }

            // KPI hero
            var s = data.summary || {};
            this.renderMetric('clicks',      s.clicks,      s.clicksDelta || s.clicks_delta_pct);
            this.renderMetric('impressions', s.impressions, s.impressionsDelta || s.impressions_delta_pct);
            this.renderMetric('ctr',         s.ctr,         s.ctrDelta || s.ctr_delta_pct, true);
            this.renderPosition(s.position, s.positionChange || s.position_change);

            // Sparklines (timeSeries: array of {date, clicks, impressions, ctr, position})
            var ts = data.timeSeries || data.time_series || [];
            this.renderSparkline('orch-kpi-clicks-spark',      ts.map(function(p){return {value: p.clicks||0};}),      '#0055FF');
            this.renderSparkline('orch-kpi-impressions-spark', ts.map(function(p){return {value: p.impressions||0};}), '#10B981');
            this.renderSparkline('orch-kpi-ctr-spark',         ts.map(function(p){return {value: p.ctr||0};}),         '#F59E0B');
            this.renderSparkline('orch-kpi-position-spark',    ts.map(function(p){return {value: -(p.position||0)};}), '#8B5CF6'); // invert: lower is better

            // Orchestra mini KPIs in hero (AI top10 + Brand Voice)
            var ok = data.orchestra_kpi || {};
            this.renderAiTop10Hero(ok.ai_articles_in_top10);
            this.renderBrandVoiceHero(ok.brand_voice_impact);

            // Top queries
            this.renderQueries(data.topQueries || data.top_queries || []);

            // Top pages
            this.renderPages(data.topPages || data.top_pages || []);

            // Orchestra full differentiator cards
            this.renderOrchestraKPI(ok);
        },

        // ───── Metric formatters ─────
        formatCompact: function(n) {
            if (n === null || n === undefined || isNaN(n)) return '—';
            n = Number(n);
            if (Math.abs(n) >= 1000000) return (n/1000000).toFixed(1).replace(/\.0$/,'') + 'M';
            if (Math.abs(n) >= 1000)    return (n/1000).toFixed(1).replace(/\.0$/,'') + 'k';
            return n.toLocaleString();
        },
        formatPct: function(n, decimals) {
            if (n === null || n === undefined || isNaN(n)) return '—';
            return (Number(n) * 100).toFixed(decimals === undefined ? 1 : decimals) + '%';
        },

        renderMetric: function(key, value, deltaPct, isCtr) {
            var $val = $('#orch-kpi-' + key + '-value');
            var $delta = $('#orch-kpi-' + key + '-delta');
            if (value === undefined || value === null) {
                $val.text('—');
            } else if (isCtr) {
                $val.text(this.formatPct(value));
            } else {
                $val.text(this.formatCompact(value));
            }
            this.renderDelta($delta, deltaPct, false);
        },

        // Position is INVERTED: lower = better. positionChange < 0 means moved up (good).
        renderPosition: function(pos, change) {
            var $val = $('#orch-kpi-position-value');
            var $delta = $('#orch-kpi-position-delta');
            if (pos === undefined || pos === null) {
                $val.text('—');
            } else {
                $val.text('#' + Number(pos).toFixed(1));
            }
            // For position: change < 0 = improved (green up), change > 0 = worsened (red down)
            this.renderDelta($delta, change, true);
        },

        renderDelta: function($el, deltaVal, isPositionMetric) {
            $el.removeClass('delta-up delta-down delta-flat');
            if (deltaVal === undefined || deltaVal === null || isNaN(deltaVal) || Number(deltaVal) === 0) {
                $el.text('—').addClass('delta-flat');
                return;
            }
            var n = Number(deltaVal);
            var improved, arrow, label;
            if (isPositionMetric) {
                // n is signed "spots"; e.g. -2 = improved by 2 positions (green ↑)
                improved = n < 0;
                var abs = Math.abs(n).toFixed(1);
                if (improved) {
                    arrow = '↑'; label = arrow + ' ' + abs + ' ' + this.i18n('posizioni');
                } else {
                    arrow = '↓'; label = arrow + ' ' + abs + ' ' + this.i18n('posizioni');
                }
            } else {
                improved = n > 0;
                arrow = improved ? '↑' : '↓';
                label = arrow + ' ' + Math.abs(n).toFixed(0) + '%';
            }
            $el.text(label).addClass(improved ? 'delta-up' : 'delta-down');
        },

        // ───── Sparkline SVG ─────
        renderSparkline: function(elId, points, color) {
            var $el = $('#' + elId);
            if (!$el.length) return;
            if (!points || points.length < 2) { $el.empty(); return; }
            var W = 220, H = 40, P = 3;
            var values = points.map(function(p){ return Number(p.value) || 0; });
            var min = Math.min.apply(null, values);
            var max = Math.max.apply(null, values);
            var range = (max - min) || 1;
            var dx = (W - 2*P) / (points.length - 1);
            var path = '';
            var area = '';
            for (var i = 0; i < points.length; i++) {
                var x = P + i*dx;
                var y = H - P - ((values[i] - min) / range) * (H - 2*P);
                if (i === 0) { path = 'M' + x.toFixed(1) + ' ' + y.toFixed(1); area = 'M' + x.toFixed(1) + ' ' + (H-P).toFixed(1) + ' L' + x.toFixed(1) + ' ' + y.toFixed(1); }
                else         { path += ' L' + x.toFixed(1) + ' ' + y.toFixed(1); area += ' L' + x.toFixed(1) + ' ' + y.toFixed(1); }
            }
            area += ' L' + (P + (points.length-1)*dx).toFixed(1) + ' ' + (H-P).toFixed(1) + ' Z';
            var fillId = elId + '-grad';
            var svg =
                '<svg viewBox="0 0 ' + W + ' ' + H + '" preserveAspectRatio="none" width="100%" height="40" style="overflow:visible;display:block;">' +
                  '<defs><linearGradient id="' + fillId + '" x1="0" y1="0" x2="0" y2="1">' +
                    '<stop offset="0%" stop-color="' + color + '" stop-opacity="0.18"/>' +
                    '<stop offset="100%" stop-color="' + color + '" stop-opacity="0"/>' +
                  '</linearGradient></defs>' +
                  '<path d="' + area + '" fill="url(#' + fillId + ')" stroke="none"/>' +
                  '<path d="' + path + '" fill="none" stroke="' + color + '" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>' +
                '</svg>';
            $el.html(svg);
        },

        // ───── Hero AI top10 mini KPI ─────
        renderAiTop10Hero: function(d) {
            var $val = $('#orch-kpi-aitop10-value');
            var $foot = $('#orch-kpi-aitop10-foot');
            var $delta = $('#orch-kpi-aitop10-delta');
            $delta.text('—').removeClass('delta-up delta-down').addClass('delta-flat');
            if (!d || d.count === undefined || d.count === null) {
                $val.text('—'); $foot.text(this.i18n('Dati insufficienti'));
                return;
            }
            var total = d.total_ai_articles || d.total || 0;
            $val.text(d.count);
            $foot.text(this.i18n('su') + ' ' + total + ' ' + this.i18n('articoli AI'));
        },
        renderBrandVoiceHero: function(d) {
            var $val = $('#orch-kpi-bv-value');
            var $foot = $('#orch-kpi-bv-foot');
            var $delta = $('#orch-kpi-bv-delta');
            $delta.text('—').removeClass('delta-up delta-down').addClass('delta-flat');
            if (!d || d.delta_pct === undefined || d.delta_pct === null) {
                $val.text('—'); $foot.text(this.i18n('Dati insufficienti'));
                return;
            }
            var sign = d.delta_pct >= 0 ? '+' : '';
            $val.text(sign + Number(d.delta_pct).toFixed(1) + '%');
            $foot.text(this.i18n('CTR vs senza Brand Voice'));
        },

        // ───── Queries table ─────
        renderQueries: function(items) {
            var $tbody = $('#orch-queries-tbody');
            $tbody.empty();
            if (!items || !items.length) {
                $tbody.html('<tr class="orch-empty-row"><td colspan="6">' + this.i18n('Nessun dato GSC nel periodo selezionato.') + '</td></tr>');
                $('#orch-queries-toggle-all').hide();
                return;
            }
            var self = this;
            items.forEach(function(it, idx) {
                var hidden = idx >= 10;
                var newBadge = it.isNew || it.is_new ? '<span class="orch-badge-new">' + self.i18n('NEW') + '</span>' : '';
                var clicksDelta = it.clicksDelta !== undefined ? it.clicksDelta : it.clicks_delta_pct;
                var deltaHtml = self.formatDeltaInline(clicksDelta, false);
                var ctrText = (it.ctr !== undefined && it.ctr !== null) ? self.formatPct(it.ctr) : '—';
                var posText = (it.position !== undefined && it.position !== null) ? Number(it.position).toFixed(1) : '—';
                var imprText = (it.impressions !== undefined) ? self.formatCompact(it.impressions) : '—';
                var clicksText = (it.clicks !== undefined) ? self.formatCompact(it.clicks) : '—';
                var $tr = $('<tr/>').toggleClass('orch-row-hidden', hidden);
                $tr.append('<td class="orch-rank">' + (idx + 1) + '</td>');
                $tr.append('<td><span class="orch-kw">' + self.escape(it.query || it.keyword || (Array.isArray(it.keys) ? it.keys[0] : '') || '') + '</span>' + newBadge + '</td>');
                $tr.append('<td class="num"><span class="orch-num-with-delta"><strong>' + clicksText + '</strong>' + deltaHtml + '</span></td>');
                $tr.append('<td class="num">' + posText + '</td>');
                $tr.append('<td class="num">' + imprText + '</td>');
                $tr.append('<td class="num">' + ctrText + '</td>');
                $tbody.append($tr);
            });
            if (items.length > 10) {
                var $btn = $('#orch-queries-toggle-all');
                $btn.show().off('click').on('click', function() {
                    var hidden = $tbody.find('tr.orch-row-hidden').length;
                    if (hidden > 0) {
                        $tbody.find('tr.orch-row-hidden').removeClass('orch-row-hidden');
                        $btn.text(self.i18n('Nascondi extra'));
                    } else {
                        $tbody.find('tr').each(function(i){ if (i >= 10) $(this).addClass('orch-row-hidden'); });
                        $btn.text(self.i18n('Mostra tutte le keyword'));
                    }
                });
                $btn.text(self.i18n('Mostra tutte le keyword') + ' (' + items.length + ')');
            } else {
                $('#orch-queries-toggle-all').hide();
            }
        },

        formatDeltaInline: function(deltaPct, isPosition) {
            if (deltaPct === undefined || deltaPct === null || isNaN(deltaPct) || Number(deltaPct) === 0) return '';
            var n = Number(deltaPct);
            var improved, arrow, cls, label;
            if (isPosition) {
                improved = n < 0;
                arrow = improved ? '↑' : '↓';
                cls = improved ? 'up' : 'down';
                label = arrow + Math.abs(n).toFixed(1);
            } else {
                improved = n > 0;
                arrow = improved ? '↑' : '↓';
                cls = improved ? 'up' : 'down';
                label = arrow + Math.abs(n).toFixed(0) + '%';
            }
            return '<span class="orch-delta-pct ' + cls + '">' + label + '</span>';
        },

        // ───── Pages list ─────
        renderPages: function(items) {
            var $list = $('#orch-pages-list');
            $list.empty();
            if (!items || !items.length) {
                $list.html('<div class="orch-empty-row">' + this.i18n('Nessun dato GSC nel periodo selezionato.') + '</div>');
                return;
            }
            var self = this;
            items.slice(0, 30).forEach(function(it) {
                var url = it.page || it.page_url || it.url || (Array.isArray(it.keys) ? it.keys[0] : '') || '';
                var newBadge = it.isNew || it.is_new ? '<span class="orch-badge-new">' + self.i18n('NEW') + '</span>' : '';
                var clicksDelta = it.clicksDelta !== undefined ? it.clicksDelta : it.clicks_delta_pct;
                var posText = (it.position !== undefined && it.position !== null) ? Number(it.position).toFixed(1) : '—';
                var ctrText = (it.ctr !== undefined && it.ctr !== null) ? self.formatPct(it.ctr) : '—';
                var clicksText = (it.clicks !== undefined) ? self.formatCompact(it.clicks) : '—';
                var deltaHtml = self.formatDeltaInline(clicksDelta, false);

                var $row = $('<div class="orch-page-row"></div>').attr('data-page-url', url);
                var displayUrl = url;
                try {
                    var u = new URL(url);
                    displayUrl = u.pathname + (u.search || '');
                } catch (e) {}
                $row.append(
                    '<div class="orch-page-row-head">' +
                      '<span class="orch-page-row-caret">▶</span>' +
                      '<a class="orch-page-row-url" href="' + self.escapeAttr(url) + '" target="_blank" rel="noopener">' + self.escape(displayUrl) + '</a>' +
                      newBadge +
                      '<div class="orch-page-row-stats">' +
                        '<span><strong>' + clicksText + '</strong> ' + self.i18n('clicks') + ' ' + deltaHtml + '</span>' +
                        '<span>' + self.i18n('pos') + ' <strong>' + posText + '</strong></span>' +
                        '<span>CTR <strong>' + ctrText + '</strong></span>' +
                      '</div>' +
                    '</div>'
                );
                $list.append($row);
            });
        },

        loadPageKeywords: function($row, url) {
            var self = this;
            $row.find('.orch-page-keywords').remove();
            var $kw = $('<div class="orch-page-keywords"><div class="orch-page-keywords-loading">' + self.i18n('Caricamento keyword…') + '</div></div>');
            $row.append($kw);

            $.post(seoAeoOrchestra.ajaxUrl, {
                action: 'seo_aeo_orchestra_analytics_page_keywords',
                nonce: seoAeoOrchestra.nonce,
                page_url: url,
                period: self.currentPeriod
            }).done(function(resp) {
                if (!resp || resp.error) {
                    $kw.html('<div class="orch-page-keywords-empty">' + self.escape(resp && resp.error ? resp.error : self.i18n('Errore caricamento keyword')) + '</div>');
                    return;
                }
                var kws = resp.topKeywords || resp.top_keywords || resp.keywords || [];
                if (!kws.length) {
                    $kw.html('<div class="orch-page-keywords-empty">' + self.i18n('Nessuna keyword nel periodo per questa pagina.') + '</div>');
                    return;
                }
                var html = '<table class="orch-page-keywords-table"><thead><tr>' +
                    '<th>' + self.i18n('Keyword') + '</th>' +
                    '<th class="num">' + self.i18n('Clicks') + '</th>' +
                    '<th class="num">' + self.i18n('Posizione') + '</th>' +
                    '<th class="num">' + self.i18n('Impressions') + '</th>' +
                    '<th class="num">' + self.i18n('CTR') + '</th>' +
                    '</tr></thead><tbody>';
                kws.slice(0, 15).forEach(function(k) {
                    var newBadge = k.isNew || k.is_new ? '<span class="orch-badge-new">' + self.i18n('NEW') + '</span>' : '';
                    html += '<tr>' +
                        '<td>' + self.escape(k.query || k.keyword || '') + newBadge + '</td>' +
                        '<td class="num">' + ((k.clicks !== undefined) ? self.formatCompact(k.clicks) : '—') + '</td>' +
                        '<td class="num">' + ((k.position !== undefined && k.position !== null) ? Number(k.position).toFixed(1) : '—') + '</td>' +
                        '<td class="num">' + ((k.impressions !== undefined) ? self.formatCompact(k.impressions) : '—') + '</td>' +
                        '<td class="num">' + ((k.ctr !== undefined && k.ctr !== null) ? self.formatPct(k.ctr) : '—') + '</td>' +
                        '</tr>';
                });
                html += '</tbody></table>';
                $kw.html(html);
            }).fail(function() {
                $kw.html('<div class="orch-page-keywords-empty">' + self.i18n('Errore di rete') + '</div>');
            });
        },

        // ───── Orchestra differentiator cards ─────
        renderOrchestraKPI: function(ok) {
            var T = this.i18n.bind(this);
            ok = ok || {};

            // 1) AI articles in TOP10
            var ai = ok.ai_articles_in_top10;
            this.fillOrchestraCard(
                'ai',
                ai && ai.count !== undefined && ai.count !== null,
                ai ? (ai.count + ' ' + T('articoli')) : null,
                ai ? (T('su') + ' ' + (ai.total_ai_articles || ai.total || 0) + ' ' + T('articoli AI generati')) : null
            );

            // 2) Brand Voice impact
            var bv = ok.brand_voice_impact;
            var bvHasData = bv && bv.delta_pct !== undefined && bv.delta_pct !== null;
            this.fillOrchestraCard(
                'bv',
                bvHasData,
                bvHasData ? ((bv.delta_pct >= 0 ? '+' : '') + Number(bv.delta_pct).toFixed(1) + '% CTR') : null,
                bvHasData ? T('vs articoli senza profilo') : null
            );

            // 3) Redirect rescues
            var rr = ok.redirect_rescues;
            var rrHasData = rr && rr.period_hits !== undefined && rr.period_hits !== null;
            this.fillOrchestraCard(
                'rr',
                rrHasData,
                rrHasData ? (this.formatCompact(rr.period_hits) + ' ' + T('clicks salvati')) : null,
                rrHasData && rr.active_redirects !== undefined ? (rr.active_redirects + ' ' + T('redirect attivi')) : null
            );

            // 4) Auto-Pilot ROI
            var ap = ok.auto_pilot_roi;
            var apHasData = ap && ap.autopilot_avg_clicks !== undefined && ap.autopilot_avg_clicks !== null;
            var apValue = null, apFoot = null;
            if (apHasData) {
                if (ap.manual_avg_clicks && ap.manual_avg_clicks > 0) {
                    var deltaPct = ((ap.autopilot_avg_clicks - ap.manual_avg_clicks) / ap.manual_avg_clicks) * 100;
                    var sign = deltaPct >= 0 ? '+' : '';
                    apValue = sign + deltaPct.toFixed(0) + '% ' + T('vs manuali');
                    apFoot = T('Auto-Pilot media') + ' ' + ap.autopilot_avg_clicks.toFixed(1) + ' · ' + T('manuali') + ' ' + ap.manual_avg_clicks.toFixed(1);
                } else {
                    apValue = ap.autopilot_avg_clicks.toFixed(1) + ' ' + T('clicks/articolo');
                    apFoot = T('Nessun articolo manuale di confronto');
                }
            }
            this.fillOrchestraCard('ap', apHasData, apValue, apFoot);

            // 5) Meta freshness
            var mf = ok.meta_freshness_score;
            var mfHasData = mf && mf.score_pct !== undefined && mf.score_pct !== null;
            this.fillOrchestraCard(
                'mf',
                mfHasData,
                mfHasData ? (Number(mf.score_pct).toFixed(0) + '%') : null,
                mfHasData && mf.fresh_count !== undefined ? (mf.fresh_count + '/' + (mf.total_pages || 0) + ' ' + T('pagine fresche')) : null
            );
        },

        fillOrchestraCard: function(suffix, hasData, valueText, footText) {
            var $val = $('#orch-od-' + suffix + '-value');
            var $foot = $('#orch-od-' + suffix + '-foot');
            if (hasData && valueText) {
                $val.text(valueText).removeClass('value-empty');
                $foot.text(footText || '');
            } else {
                $val.text('—').addClass('value-empty');
                $foot.text(this.i18n('Dati insufficienti'));
            }
        },

        // ───── Utility ─────
        escape: function(s) {
            if (s === undefined || s === null) return '';
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        },
        escapeAttr: function(s) {
            return this.escape(s);
        }
    };

    $(function() { Analytics.init(); });

});
</script>

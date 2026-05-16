<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.VariableNotPrefixed
// Reason: template scope. Variables are local to this include/template,
// passed by the calling function via include/require. The Plugin Check
// heuristic doesn't distinguish template-scope locals from globals.
if (!defined('ABSPATH')) exit;
$license_key = get_option('seo_aeo_orchestra_license_key', '');
$license_valid = !empty($license_key);

// Homepage
$front_page_id = get_option('page_on_front');

// Pagine ordinate per menu_order
$pages = get_posts(array(
    'numberposts' => -1,
    'post_type' => 'page',
    'post_status' => 'publish',
    'orderby' => 'menu_order',
    'order' => 'ASC'
));

// Post ordinati per data
$posts = get_posts(array(
    'numberposts' => -1,
    'post_type' => 'post',
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC'
));

// Lista: homepage prima, poi pagine, poi post
$all_content = array();
$seen_ids = array();
if ($front_page_id) {
    $home = get_post($front_page_id);
    if ($home && $home->post_status === 'publish') {
        $all_content[] = $home;
        $seen_ids[] = $front_page_id;
    }
}
foreach ($pages as $p) if (!in_array($p->ID, $seen_ids)) { $all_content[] = $p; $seen_ids[] = $p->ID; }
foreach ($posts as $p) if (!in_array($p->ID, $seen_ids)) { $all_content[] = $p; $seen_ids[] = $p->ID; }

// Crediti utente (opzione WP; il badge "live" nell'header usa AJAX, questo è fallback)
$user_credits  = get_option('seo_aeo_orchestra_user_credits', 0);
$cost_per_page = 5;
$analyses_left = (int) floor(max(0, (int)$user_credits) / max(1, $cost_per_page));

// ═══════════════════════════════════════════════════════════════
// 2.4.0: Health Score + Trend + Alert — calcolati da cronologia
// ═══════════════════════════════════════════════════════════════
$history_raw = get_option('seo_aeo_orchestra_history_json', '');
$orch_history = array();
if (!empty($history_raw)) {
    $decoded = json_decode($history_raw, true);
    if (is_array($decoded)) {
        foreach ($decoded as $h) {
            if (isset($h['section']) && $h['section'] === 'orchestrator') {
                $orch_history[] = $h;
            }
        }
    }
}

/**
 * Estrae Health Score (0-100) da un item di cronologia orchestrator.
 * Media di avg_seo e avg_aeo, ignorando "--" o null. Ritorna null se non calcolabile.
 */
function seo_aeo_v240_extract_score($history_item) {
    if (empty($history_item['data'])) return null;
    $data = json_decode($history_item['data'], true);
    if (!is_array($data)) return null;
    $scores = array();
    foreach (array('avg_seo', 'avg_aeo') as $key) {
        if (isset($data[$key]) && is_numeric($data[$key])) {
            $scores[] = (float) $data[$key];
        }
    }
    if (empty($scores)) return null;
    return (int) round(array_sum($scores) / count($scores));
}

// Score corrente (ultimo item) + precedente per delta
$score_current   = !empty($orch_history) ? seo_aeo_v240_extract_score($orch_history[0]) : null;
$score_previous  = (count($orch_history) > 1) ? seo_aeo_v240_extract_score($orch_history[1]) : null;
$score_delta     = ($score_current !== null && $score_previous !== null) ? ($score_current - $score_previous) : null;

// Data ultima analisi
$last_analysis_date = !empty($orch_history) ? $orch_history[0]['date'] : null;
$days_since_last    = null;
if ($last_analysis_date) {
    $ts = strtotime($last_analysis_date);
    if ($ts) $days_since_last = (int) floor((time() - $ts) / 86400);
}

// ═══ Alert logic ═══
// Un solo alert alla volta, in ordine di priorità
$orch_alert = null;

// Priorità 1: mai analizzato
if (empty($orch_history)) {
    $orch_alert = array(
        'level' => 'info',
        'text'  => class_exists('SEO_AEO_T')
            ? SEO_AEO_T::t('Benvenuto. Avvia la tua prima analisi per ottenere un Health Score del sito e un piano d\'azione.')
            : 'Benvenuto. Avvia la tua prima analisi per ottenere un Health Score del sito e un piano d\'azione.',
        'icon'  => 'spark'
    );
}
// Priorità 2: score calato molto
elseif ($score_delta !== null && $score_delta <= -5) {
    $orch_alert = array(
        'level' => 'bad',
        'text'  => sprintf(
            class_exists('SEO_AEO_T')
                ? SEO_AEO_T::t('Health Score sceso da %1$s a %2$s (%3$s). Rilancia un\'analisi per vedere cosa è cambiato.')
                : 'Health Score sceso da %1$s a %2$s (%3$s). Rilancia un\'analisi per vedere cosa è cambiato.',
            $score_previous, $score_current, $score_delta
        ),
        'icon'  => 'trend-down'
    );
}
// Priorità 3: analisi vecchia (oltre 30gg)
elseif ($days_since_last !== null && $days_since_last >= 30) {
    $orch_alert = array(
        'level' => 'warn',
        'text'  => sprintf(
            class_exists('SEO_AEO_T')
                ? SEO_AEO_T::t('Ultima analisi %d giorni fa. Ti consigliamo di rianalizzare le pagine principali per vedere come stanno.')
                : 'Ultima analisi %d giorni fa. Ti consigliamo di rianalizzare le pagine principali per vedere come stanno.',
            $days_since_last
        ),
        'icon'  => 'clock'
    );
}
// Priorità 4: pagine nuove non analizzate
else {
    // Cerco URL analizzati nell'ultimo report
    $analyzed_urls = array();
    if (!empty($orch_history)) {
        $last_data = json_decode($orch_history[0]['data'], true);
        if (!empty($last_data['pages']) && is_array($last_data['pages'])) {
            foreach ($last_data['pages'] as $p) {
                if (!empty($p['url'])) $analyzed_urls[] = $p['url'];
            }
        }
    }
    // Conto pagine nuove pubblicate dopo l'ultima analisi
    // 3.31.2: raccolgo anche la LISTA degli ID per CTA pre-selezione
    $new_count = 0;
    $new_pages_for_cta = array();
    if ($last_analysis_date && !empty($analyzed_urls)) {
        foreach ($all_content as $item) {
            $item_date = strtotime($item->post_date);
            if ($item_date && $item_date > strtotime($last_analysis_date)) {
                $new_count++;
                $new_pages_for_cta[] = array(
                    'id'    => $item->ID,
                    'title' => $item->post_title,
                    'url'   => get_permalink($item->ID),
                    'edit'  => get_edit_post_link($item->ID, ''),
                );
            }
        }
    }
    if ($new_count >= 2) {
        $orch_alert = array(
            'level' => 'warn',
            'text'  => sprintf(
                class_exists('SEO_AEO_T')
                    ? SEO_AEO_T::t('%d pagine pubblicate dopo la tua ultima analisi. Considera di analizzarle.')
                    : '%d pagine pubblicate dopo la tua ultima analisi. Considera di analizzarle.',
                $new_count
            ),
            'icon'  => 'new',
            'new_pages' => array_slice($new_pages_for_cta, 0, 8), // max 8 nella UI
            'new_count' => $new_count,
        );
    }
}

// Color class per lo score (per CSS)
$score_class = 'neutral';
if ($score_current !== null) {
    if ($score_current >= 75)       $score_class = 'good';
    elseif ($score_current >= 50)   $score_class = 'mid';
    else                            $score_class = 'bad';
}

// ═══════════════════════════════════════════════════════════════
// 2.5.0: Sparkline trend + Pagine critiche
// ═══════════════════════════════════════════════════════════════

// Sparkline: prendo gli score (da oldest a newest) delle ultime 10 analisi.
// orch_history è ordinato newest-first, reverto per avere sequenza temporale naturale.
$sparkline_scores = array();
foreach (array_reverse(array_slice($orch_history, 0, 10)) as $h) {
    $s = seo_aeo_v240_extract_score($h);
    if ($s !== null) $sparkline_scores[] = $s;
}

// Pagine critiche: top N pagine con score più basso dall'ultima analisi.
// Escludo pagine con score null/--, ordino per score asc.
$critical_pages = array();
$critical_partial = false; // 3.31.2: true se ho meno di 3 pagine nell'ultima analisi
if (!empty($orch_history)) {
    $last_data = json_decode($orch_history[0]['data'], true);
    if (!empty($last_data['pages']) && is_array($last_data['pages'])) {
        foreach ($last_data['pages'] as $p) {
            // Il "peggior" score della pagina è il minimo tra seo_score e aeo_score validi
            $page_scores = array();
            foreach (array('seo_score', 'aeo_score') as $k) {
                if (isset($p[$k]) && is_numeric($p[$k])) $page_scores[] = (float)$p[$k];
            }
            if (empty($page_scores)) continue;
            $worst = min($page_scores);
            $critical_pages[] = array(
                'title' => isset($p['title']) ? $p['title'] : '(senza titolo)',
                'url'   => isset($p['url']) ? $p['url'] : '',
                'score' => (int) round($worst),
                'seo'   => isset($p['seo_score']) && is_numeric($p['seo_score']) ? (int)$p['seo_score'] : null,
                'aeo'   => isset($p['aeo_score']) && is_numeric($p['aeo_score']) ? (int)$p['aeo_score'] : null,
            );
        }
    }
    // Ordino per score asc (peggiori prima) e tengo top 3
    // Prima dedup per URL (può capitare di avere la stessa pagina 2 volte nel JSON)
    $seen_urls = array();
    $critical_pages = array_filter($critical_pages, function($p) use (&$seen_urls) {
        if (empty($p['url'])) return true; // lascia pagine senza url (edge case)
        if (in_array($p['url'], $seen_urls)) return false;
        $seen_urls[] = $p['url'];
        return true;
    });
    $critical_pages = array_values($critical_pages);
    usort($critical_pages, function($a, $b) { return $a['score'] - $b['score']; });
    // 3.31.2: se ho >= 3 pagine analizzate, mostro top 3 con score < 75 ("davvero critiche").
    // Se ho meno di 3 pagine analizzate, mostro TUTTE quelle disponibili indipendentemente
    // dallo score, così l'utente vede sempre qualcosa (e capisce perché solo 1).
    $analyzed_count = count($critical_pages);
    if ($analyzed_count >= 3) {
        $critical_pages = array_slice($critical_pages, 0, 3);
        // Solo pagine con score < 75 sono davvero "critiche"
        $critical_pages = array_filter($critical_pages, function($p) { return $p['score'] < 75; });
        $critical_pages = array_values($critical_pages);
        $critical_partial = false;
    } else {
        // Meno di 3 pagine analizzate: mostro tutto + flag per messaggio CTA
        $critical_partial = ($analyzed_count > 0 && $analyzed_count < 3);
    }
}

// ═══════════════════════════════════════════════════════════════
// 2.6.0: To-do prioritizzato permanente (3.31.2: skip keyword vuote, includi URL)
// ═══════════════════════════════════════════════════════════════
// Estraggo le azioni suggerite dall'ultimo report orchestrator, dedup per titolo+url,
// ordino per priority, tengo top 3 non-done (done è tracciato via localStorage lato client).
$todo_items = array();
if (!empty($orch_history)) {
    $last_data = json_decode($orch_history[0]['data'], true);
    if (!empty($last_data['pages']) && is_array($last_data['pages'])) {
        foreach ($last_data['pages'] as $p) {
            $page_title = isset($p['title']) ? $p['title'] : '';
            $page_url   = isset($p['url']) ? $p['url'] : '';
            // Estraggo possibili keyword di pagina come fallback
            $page_kw = '';
            foreach (array('main_keyword', 'primary_keyword', 'keyword', 'focus_keyword') as $kk) {
                if (!empty($p[$kk]) && is_string($p[$kk])) { $page_kw = $p[$kk]; break; }
            }
            $actions = isset($p['actions']) && is_array($p['actions']) ? $p['actions'] : array();
            foreach ($actions as $a) {
                $title = isset($a['title']) ? $a['title'] : (isset($a['action']) ? $a['action'] : '');
                if (empty($title)) continue;
                $priority = isset($a['priority']) ? strtolower($a['priority']) : 'media';
                $action_kw = isset($a['keyword']) && is_string($a['keyword']) ? trim($a['keyword']) : '';
                if ($action_kw === '' && $page_kw !== '') { $action_kw = $page_kw; }
                $description = isset($a['description']) ? $a['description'] : '';
                $agent_type = isset($a['agent']) ? $a['agent'] : (isset($a['type']) ? $a['type'] : '');
                // 3.31.2: Skip action items che richiedono keyword ma ne sono privi
                // (es. "Genera Meta Tags per la keyword ''" è inutile per l'utente)
                $needs_keyword = (
                    stripos($title, 'meta tag') !== false ||
                    stripos($title, 'keyword') !== false ||
                    stripos($description, 'per la keyword') !== false ||
                    stripos($description, 'per le keyword') !== false
                );
                if ($needs_keyword && $action_kw === '') {
                    // Tento un fallback estremo: usa il post_title come keyword "umana"
                    if ($page_title !== '') {
                        $action_kw = $page_title;
                    } else {
                        continue; // skip definitivo
                    }
                }
                $todo_items[] = array(
                    // ID deterministico così lo stesso todo rilanciato non si duplica
                    'id'         => substr(md5($page_url . '|' . $title), 0, 12),
                    'title'      => $title,
                    'desc'       => $description,
                    'priority'   => $priority,
                    'page_title' => $page_title,
                    'page_url'   => $page_url,
                    'keyword'    => $action_kw,
                    'agent'      => $agent_type,
                );
            }
        }
    }
}
// Dedup per id
$seen_todo = array();
$todo_items = array_filter($todo_items, function($t) use (&$seen_todo) {
    if (in_array($t['id'], $seen_todo)) return false;
    $seen_todo[] = $t['id'];
    return true;
});
$todo_items = array_values($todo_items);
// Ordino: critica > alta > media > bassa
$priority_rank = array('critica' => 0, 'alta' => 1, 'media' => 2, 'bassa' => 3);
usort($todo_items, function($a, $b) use ($priority_rank) {
    $ra = isset($priority_rank[$a['priority']]) ? $priority_rank[$a['priority']] : 2;
    $rb = isset($priority_rank[$b['priority']]) ? $priority_rank[$b['priority']] : 2;
    return $ra - $rb;
});
// Top 5 (mostro 3 iniziali, altri espandibili)
$todo_items_top = array_slice($todo_items, 0, 5);

/**
 * Genera SVG sparkline da una lista di valori 0-100.
 * Largh fissa, altezza fissa. Linea + area sottostante tenue.
 */
function seo_aeo_v250_sparkline($values, $width = 110, $height = 28, $color = '#93c5fd') {
    if (count($values) < 2) return '';
    $max = 100; $min = 0; // range fisso per readability
    $step = $width / (count($values) - 1);
    $points = array();
    foreach ($values as $i => $v) {
        $x = round($i * $step, 1);
        $y = round($height - (($v - $min) / ($max - $min)) * $height, 1);
        $points[] = $x . ',' . $y;
    }
    $path = implode(' ', $points);
    $area_path = 'M0,' . $height . ' L' . $path . ' L' . $width . ',' . $height . ' Z';
    // Pallino ultimo valore
    $last = end($points);
    list($lx, $ly) = explode(',', $last);
    return sprintf(
        '<svg class="orch5-spark" viewBox="0 0 %d %d" width="%d" height="%d" aria-hidden="true">' .
            '<path d="%s" fill="%s" fill-opacity="0.18" stroke="none"/>' .
            '<polyline points="%s" fill="none" stroke="%s" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>' .
            '<circle cx="%s" cy="%s" r="2.2" fill="%s"/>' .
        '</svg>',
        $width, $height, $width, $height,
        $area_path, $color,
        $path, $color,
        $lx, $ly, $color
    );
}
?>

<?php
// 3.36.1 (WP.org A.5 compliance): font CSS routed through wp_enqueue_style;
// preconnect via wp_resource_hints. No more inline <link> tags.
add_filter('wp_resource_hints', function ($urls, $relation_type) {
    if ($relation_type === 'preconnect') $urls[] = 'https://api.fontshare.com';
    return $urls;
}, 10, 2);
wp_enqueue_style(
    'seo-aeo-fontshare',
    'https://api.fontshare.com/v2/css?f[]=satoshi@400,500,700,900&f[]=general-sans@400,500,600&display=swap',
    array(),
    defined('SEO_AEO_VERSION') ? SEO_AEO_VERSION : '3.36.2'
);
?>

<?php $T = function($s) { return class_exists('SEO_AEO_T') ? esc_html(SEO_AEO_T::t($s)) : esc_html($s); }; ?>
<div class="wrap orchestra-admin orchestra-v3">
    <h1 style="display:none;">AEO Orchestra</h1>

    <!-- ═══ Header navy con Health Score ═══ -->
    <div class="orch3-header orch4-header">
        <div class="orch4-header-left">
            <h1 class="orch3-h1"><?php echo esc_html(SEO_AEO_T::t('Orchestratore')); ?></h1>
            <p class="orch3-sub"><?php echo esc_html(SEO_AEO_T::t('Panoramica del tuo sito: cosa serve per essere visibili su Google e citati da ChatGPT, Claude, Gemini, Perplexity, Grok, Copilot, Meta AI e DuckDuckGo.')); ?></p>
        </div>

        <?php if ($score_current !== null): ?>
        <!-- ═ Health Score (quando c'è almeno un'analisi) ═ -->
        <div class="orch4-health" data-score-class="<?php echo esc_attr($score_class); ?>">
            <div class="orch4-health-top">
                <span class="orch4-health-label"><?php echo esc_html(SEO_AEO_T::t('Health Score')); ?></span>
                <?php if ($score_delta !== null && $score_delta !== 0): ?>
                <span class="orch4-delta orch4-delta-<?php echo $score_delta > 0 ? 'up' : 'down'; ?>">
                    <?php echo esc_html(($score_delta > 0 ? '+' : '') . (int) $score_delta); ?>
                    <?php echo $score_delta > 0 ? '▲' : '▼'; ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="orch4-health-number"><?php echo absint($score_current); ?><span class="orch4-health-over">/100</span></div>
            <div class="orch4-health-bar">
                <div class="orch4-health-fill" style="width: <?php echo absint($score_current); ?>%;"></div>
            </div>
            <?php if (count($sparkline_scores) >= 2): ?>
            <div class="orch5-spark-row" title="<?php echo class_exists('SEO_AEO_T') ? esc_attr(SEO_AEO_T::t('Andamento Health Score')) : 'Andamento Health Score'; ?>">
                <?php echo seo_aeo_v250_sparkline($sparkline_scores); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- function returns sanitized SVG ?>
                <span class="orch5-spark-label"><?php echo count($sparkline_scores); ?> <?php echo esc_html(SEO_AEO_T::t('analisi')); ?></span>
            </div>
            <?php endif; ?>
            <div class="orch4-health-foot">
                <?php if ($days_since_last !== null): ?>
                    <?php if ($days_since_last == 0): ?>
                        <?php echo esc_html(SEO_AEO_T::t('Analisi oggi')); ?>
                    <?php elseif ($days_since_last == 1): ?>
                        <?php echo esc_html(SEO_AEO_T::t('Analisi ieri')); ?>
                    <?php else: ?>
                        <?php echo esc_html(SEO_AEO_T::t('Ultima analisi')); ?> <?php echo absint($days_since_last); ?> <?php echo esc_html(SEO_AEO_T::t('giorni fa')); ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <!-- ═ Se nessuna analisi, mostra chiamata all'azione ═ -->
        <div class="orch4-health orch4-health-empty">
            <div class="orch4-health-label"><?php echo esc_html(SEO_AEO_T::t('Health Score')); ?></div>
            <div class="orch4-health-placeholder">—</div>
            <div class="orch4-health-foot"><?php echo esc_html(SEO_AEO_T::t('Avvia la prima analisi per calcolarlo')); ?></div>
        </div>
        <?php endif; ?>

        <!-- 3.31.0: Badge crediti rimosso (ridondante con la pillola persistente globale) -->
    </div>

    <?php if ($orch_alert): ?>
    <!-- ═══ Alert (solo se rilevante) ═══ -->
    <div class="orch4-alert orch4-alert-<?php echo esc_attr($orch_alert['level']); ?>">
        <span class="orch4-alert-icon" data-icon="<?php echo esc_attr($orch_alert['icon']); ?>"></span>
        <div style="flex:1;min-width:0;">
            <span class="orch4-alert-text"><?php echo esc_html($orch_alert['text']); ?></span>
            <?php if (!empty($orch_alert['new_pages'])): ?>
            <ul class="orch4-alert-pages" style="list-style:none;margin:8px 0 0;padding:0;display:flex;flex-direction:column;gap:4px;">
                <?php foreach ($orch_alert['new_pages'] as $np): ?>
                <li style="font-size:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <span style="opacity:.7;">→</span>
                    <a href="<?php echo esc_url($np['edit']); ?>" target="_blank" style="color:inherit;text-decoration:underline;"><?php echo esc_html($np['title']); ?></a>
                    <span style="opacity:.55;font-size:11px;word-break:break-all;"><?php echo esc_html($np['url']); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <div style="margin-top:10px;">
                <a href="#orchestrator-setup"
                   class="orch4-alert-cta"
                   data-orch-preselect="<?php echo esc_attr(implode(',', array_map(function($p){return (int)$p['id'];}, $orch_alert['new_pages']))); ?>"
                   style="display:inline-block;padding:6px 14px;background:#0055FF;color:#fff;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;">
                    <?php echo esc_html(SEO_AEO_T::t('→ Analizza queste')); ?> <?php echo absint($orch_alert['new_count']); ?> <?php echo esc_html(SEO_AEO_T::t('pagine')); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$license_valid): ?>
    <div class="orchestra-notice warning">
        <p><strong><?php echo esc_html(SEO_AEO_T::t('Licenza non attiva.')); ?></strong> <?php echo esc_html(SEO_AEO_T::t('Attiva la licenza nelle')); ?> <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-settings')); ?>"><?php echo esc_html(SEO_AEO_T::t('Impostazioni')); ?></a>.</p>
    </div>
    <?php endif; ?>

    <!-- 3.7.0 — Onboarding wizard prima installazione -->
    <?php if (!get_option('seo_aeo_orchestra_onboarding_dismissed')): ?>
    <div id="orch-onboarding-overlay" class="orch-onboarding-overlay">
        <div class="orch-onboarding-modal">
            <button type="button" class="orch-onboarding-close" id="orch-onboarding-skip" aria-label="<?php echo class_exists('SEO_AEO_T') ? esc_attr(SEO_AEO_T::t('Chiudi')) : 'Chiudi'; ?>">&times;</button>
            <div class="orch-onboarding-content">
                <div class="orch-onboarding-eyebrow"><?php echo esc_html(SEO_AEO_T::t('Benvenuto in AEO Orchestra')); ?></div>
                <h2 class="orch-onboarding-title" id="orch-onboarding-title"><?php echo esc_html(SEO_AEO_T::t('Iniziamo!')); ?></h2>
                <p class="orch-onboarding-sub" id="orch-onboarding-sub"><?php echo esc_html(SEO_AEO_T::t('3 passi rapidi per configurare il plugin e lanciare la prima analisi.')); ?></p>
                <div class="orch-onboarding-steps" id="orch-onboarding-steps">
                    <div class="orch-onb-step active" data-step="1">
                        <div class="orch-onb-num">1</div>
                        <div class="orch-onb-text">
                            <strong><?php echo esc_html(SEO_AEO_T::t('Verifica licenza')); ?></strong>
                            <span><?php echo esc_html(SEO_AEO_T::t('Controlla che la tua license key sia attiva e che ci sia connessione col server.')); ?></span>
                        </div>
                        <div class="orch-onb-status"><?php echo $license_valid ? '✓' : '·'; ?></div>
                    </div>
                    <div class="orch-onb-step" data-step="2">
                        <div class="orch-onb-num">2</div>
                        <div class="orch-onb-text">
                            <strong><?php echo esc_html(SEO_AEO_T::t('Lancia la prima analisi')); ?></strong>
                            <span><?php echo esc_html(SEO_AEO_T::t('L\'orchestratore scansiona le tue pagine e calcola Health Score, problemi SEO + AEO.')); ?></span>
                        </div>
                        <div class="orch-onb-status">·</div>
                    </div>
                    <div class="orch-onb-step" data-step="3">
                        <div class="orch-onb-num">3</div>
                        <div class="orch-onb-text">
                            <strong><?php echo esc_html(SEO_AEO_T::t('Applica i suggerimenti')); ?></strong>
                            <span><?php echo esc_html(SEO_AEO_T::t('Per ogni problema trovato, click "Propose" → "Apply" per salvare le ottimizzazioni sul sito.')); ?></span>
                        </div>
                        <div class="orch-onb-status">·</div>
                    </div>
                </div>
                <div class="orch-onboarding-foot">
                    <button type="button" class="orch3-btn orch3-btn-ghost" id="orch-onboarding-skip-btn"><?php echo esc_html(SEO_AEO_T::t('Salta tutorial')); ?></button>
                    <a href="<?php echo esc_url($license_valid ? '#orchestrator-start' : admin_url('admin.php?page=seo-aeo-settings')); ?>"
                       class="orch3-btn orch3-btn-primary" id="orch-onboarding-cta">
                        <?php echo wp_kses_post($license_valid ? $T('Inizia ora →') : $T('Vai a Impostazioni →')); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ Body con sidebar (2.5.0) ═══ -->
    <div class="orch5-body">
    <div class="orch5-main">

    <!-- ═══ Quick Win ═══ -->
    <div id="quick-win-widget" style="display:none;">
        <div class="orch3-quickwin">
            <div class="qw-body">
                <div class="qw-eyebrow"><?php echo esc_html(SEO_AEO_T::t('Azione rapida')); ?></div>
                <h3 id="qw-title"><?php echo esc_html(SEO_AEO_T::t('Quick Win disponibile')); ?></h3>
                <p id="qw-description"></p>
            </div>
            <div class="qw-side">
                <div id="qw-score-box" class="qw-score-box">
                    <div id="qw-score" class="qw-score-num"></div>
                    <div class="qw-score-lbl"><?php echo esc_html(SEO_AEO_T::t('score')); ?></div>
                </div>
                <a id="qw-action-btn" href="#" class="orch3-btn orch3-btn-primary"><?php echo esc_html(SEO_AEO_T::t('Ottimizza subito')); ?></a>
                <button type="button" id="qw-dismiss" class="orch3-btn orch3-btn-ghost" title="<?php echo esc_html(SEO_AEO_T::t('Nascondi')); ?>">&times;</button>
            </div>
        </div>
    </div>

        <?php if (!empty($critical_pages)): ?>
    <!-- ═══ Pagine critiche (2.5.0, partial fix 3.31.2) ═══ -->
    <div class="orch3-card orch5-critical">
        <div class="orch3-card-head">
            <h2 class="orch3-h2"><?php echo esc_html(SEO_AEO_T::t('Pagine che richiedono attenzione')); ?></h2>
            <p class="orch3-muted"><?php echo esc_html(SEO_AEO_T::t('Dall\'ultima analisi, queste pagine hanno il punteggio più basso. Iniziare da qui massimizza l\'impatto.')); ?></p>
        </div>
        <div class="orch5-critical-list">
            <?php foreach ($critical_pages as $cp):
                $cp_class = $cp['score'] >= 50 ? 'mid' : 'bad';
            ?>
            <div class="orch5-critical-item">
                <div class="orch5-cp-score orch5-cp-<?php echo esc_attr($cp_class); ?>">
                    <?php echo absint($cp['score']); ?>
                </div>
                <div class="orch5-cp-body">
                    <div class="orch5-cp-title"><?php echo esc_html($cp['title']); ?></div>
                    <?php if (!empty($cp['url'])): ?>
                    <div class="orch5-cp-url" style="font-size:11px;color:#94a3b8;word-break:break-all;margin-top:2px;"><?php echo esc_html($cp['url']); ?></div>
                    <?php endif; ?>
                    <div class="orch5-cp-meta">
                        <?php if ($cp['seo'] !== null): ?>SEO <strong><?php echo absint($cp['seo']); ?></strong><?php endif; ?>
                        <?php if ($cp['seo'] !== null && $cp['aeo'] !== null): ?> · <?php endif; ?>
                        <?php if ($cp['aeo'] !== null): ?>AEO <strong><?php echo absint($cp['aeo']); ?></strong><?php endif; ?>
                    </div>
                </div>
                <button type="button" class="orch3-btn orch3-btn-ghost orch5-cp-btn"
                    data-cp-url="<?php echo esc_attr($cp['url']); ?>"
                    data-cp-title="<?php echo esc_attr($cp['title']); ?>">Rianalizza</button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($critical_partial)): ?>
        <p class="orch3-muted" style="margin:12px 16px 0;font-size:12px;padding:10px 12px;background:#f8fafc;border-radius:8px;border-left:3px solid #94a3b8;">
            <?php echo esc_html(SEO_AEO_T::t('Analizza più pagine per vedere top 3 con priorità più alta.')); ?>
        </p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($todo_items_top)):
        // 3.31.2: raggruppo per priorità per il nuovo layout a tile
        $todo_groups = array('critica' => array(), 'alta' => array(), 'media' => array(), 'bassa' => array());
        foreach ($todo_items_top as $t) {
            $pkey = isset($todo_groups[$t['priority']]) ? $t['priority'] : 'media';
            $todo_groups[$pkey][] = $t;
        }
        $priority_meta = array(
            'critica' => array('icon' => '🔴', 'label' => $T('critica priorità'), 'class' => 'crit'),
            'alta'    => array('icon' => '🔴', 'label' => $T('alta priorità'),    'class' => 'high'),
            'media'   => array('icon' => '🟡', 'label' => $T('media priorità'),   'class' => 'med'),
            'bassa'   => array('icon' => '🟢', 'label' => $T('bassa priorità'),   'class' => 'low'),
        );
    ?>
    <!-- ═══ To-do prioritizzato (3.31.2: tile layout + group headers) ═══ -->
    <div class="orch3-card orch6-todo orch6-todo-tiles" data-has-todos="1">
        <div class="orch3-card-head orch6-todo-head">
            <div>
                <h2 class="orch3-h2"><?php echo esc_html(SEO_AEO_T::t('Prossimi passi per il tuo sito')); ?></h2>
                <p class="orch3-muted"><?php echo esc_html(SEO_AEO_T::t('Azioni prioritarie dall\'ultima analisi. Marca come fatto ciò che hai completato — resteranno nascoste.')); ?></p>
            </div>
            <div class="orch6-todo-counter" id="orch6-todo-counter"></div>
            <button type="button" class="orch6-todo-toggle-done" id="orch6-todo-toggle-done" hidden>👁 <span data-label><?php echo esc_html(SEO_AEO_T::t('Mostra completati')); ?></span></button>
        </div>
        <div class="orch6-todo-list" id="orch6-todo-list">
            <?php foreach ($priority_meta as $pkey => $pmeta):
                $group = $todo_groups[$pkey];
                if (empty($group)) continue;
            ?>
            <div class="orch6-tile-group orch6-tile-group-<?php echo esc_attr($pmeta['class']); ?>" data-priority="<?php echo esc_attr($pkey); ?>">
                <h4 class="orch6-tile-group-head">
                    <span class="orch6-tile-group-icon"><?php echo esc_html($pmeta['icon']); ?></span>
                    <span><?php echo count($group); ?> <?php echo esc_html($pmeta['label']); ?></span>
                </h4>
                <div class="orch6-tile-grid">
                    <?php foreach ($group as $t): ?>
                    <div class="orch6-tile orch6-todo-item priority-<?php echo esc_attr($t['priority']); ?>"
                         data-todo-id="<?php echo esc_attr($t['id']); ?>">
                        <label class="orch6-todo-check orch6-tile-check">
                            <input type="checkbox" class="orch6-todo-done" />
                            <span class="orch6-todo-mark"></span>
                        </label>
                        <div class="orch6-tile-body">
                            <div class="orch6-tile-action"><?php echo esc_html($t['title']); ?></div>
                            <?php if (!empty($t['page_title'])): ?>
                            <div class="orch6-tile-page" title="<?php echo esc_attr($t['page_title']); ?>"><?php echo esc_html($t['page_title']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($t['page_url'])): ?>
                            <a href="<?php echo esc_url($t['page_url']); ?>" target="_blank" class="orch6-tile-url"><?php echo esc_html($t['page_url']); ?></a>
                            <?php endif; ?>
                        </div>
                        <button type="button"
                                class="orch6-tile-cta"
                                data-todo-action="<?php echo esc_attr($t['title']); ?>"
                                data-todo-page-url="<?php echo esc_attr($t['page_url']); ?>"
                                data-todo-keyword="<?php echo esc_attr(isset($t['keyword']) ? $t['keyword'] : ''); ?>"
                                data-todo-agent="<?php echo esc_attr(isset($t['agent']) ? $t['agent'] : ''); ?>">
                            <?php echo esc_html(SEO_AEO_T::t('Esegui')); ?>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ Nuova analisi ═══ -->
    <div class="orch3-card" id="orchestrator-setup">
        <div class="orch3-card-head">
            <h2 class="orch3-h2"><?php echo esc_html(SEO_AEO_T::t('Nuova analisi')); ?></h2>
            <p class="orch3-muted"><?php echo esc_html(SEO_AEO_T::t('Scegli quali pagine analizzare. La homepage è selezionata per default.')); ?></p>
        </div>

        <div class="orch3-toolbar">
            <button type="button" class="orch3-chip" id="orch-select-all"
                onclick="jQuery('.orch-page-check').prop('checked',true);if(window.SeoAeoOrchestra)SeoAeoOrchestra.updateOrchestratorCount();"><?php echo esc_html(SEO_AEO_T::t('Tutte')); ?> (<?php echo count($all_content); ?>)</button>
            <button type="button" class="orch3-chip" id="orch-select-none"
                onclick="jQuery('.orch-page-check').prop('checked',false);if(window.SeoAeoOrchestra)SeoAeoOrchestra.updateOrchestratorCount();"><?php echo esc_html(SEO_AEO_T::t('Nessuna')); ?></button>
            <button type="button" class="orch3-chip" id="orch-select-pages"
                onclick="jQuery('.orch-page-check').prop('checked',false).filter('[data-type=page]').prop('checked',true);if(window.SeoAeoOrchestra)SeoAeoOrchestra.updateOrchestratorCount();"><?php echo esc_html(SEO_AEO_T::t('Solo Pagine')); ?></button>
            <button type="button" class="orch3-chip" id="orch-select-posts"
                onclick="jQuery('.orch-page-check').prop('checked',false).filter('[data-type=post]').prop('checked',true);if(window.SeoAeoOrchestra)SeoAeoOrchestra.updateOrchestratorCount();"><?php echo esc_html(SEO_AEO_T::t('Solo Post')); ?></button>
            <span class="orch3-count" id="orch-selected-count">0 <?php echo esc_html(SEO_AEO_T::t('selezionate')); ?></span>
        </div>

        <div class="orch3-page-list orchestrator-pages-list">
            <?php foreach ($all_content as $item):
                $has_meta = (bool)get_post_meta($item->ID, '_seo_aeo_meta_title', true);
                $url = get_permalink($item->ID);
                $is_home = ($front_page_id && $item->ID == $front_page_id);
                $type_label = $is_home ? 'homepage' : $item->post_type;
            ?>
            <label class="orch3-page-item orch-page-item<?php if ($is_home) echo ' is-home'; ?>">
                <input type="checkbox" name="orch_pages[]" value="<?php echo absint($item->ID); ?>"
                       data-url="<?php echo esc_url($url); ?>"
                       data-title="<?php echo esc_attr($item->post_title); ?>"
                       data-type="<?php echo esc_attr($item->post_type); ?>"
                       class="orch-page-check" <?php if ($is_home) echo 'checked'; ?> />
                <span class="pi-title">
                    <strong><?php echo esc_html($item->post_title); ?></strong>
                    <?php if ($is_home): ?>
                        <span class="pi-badge-home">HOMEPAGE</span>
                    <?php else: ?>
                        <span class="pi-type"><?php echo esc_html($type_label); ?></span>
                    <?php endif; ?>
                </span>
                <?php if ($has_meta): ?>
                <span class="pi-flag ok" title="Meta tags presenti — pagina già ottimizzata"></span>
                <?php else: ?>
                <span class="pi-flag warn" title="Meta tags mancanti — da ottimizzare"></span>
                <?php endif; ?>
            </label>
            <?php endforeach; ?>
        </div>

        <div class="orch3-runbar">
            <button type="button" class="orch3-btn orch3-btn-primary orch3-btn-large" id="orch-start-analysis"
                data-orch-fallback-msg="Script non caricato. Svuota la cache del browser e ricarica." onclick="if(!window.SeoAeoOrchestra){alert(this.dataset.orchFallbackMsg||'Script non caricato.');return false;}"
                <?php if (!$license_valid) echo 'disabled'; ?>>
                <span id="orch-start-label"><?php echo esc_html(SEO_AEO_T::t('Analizza 1 pagina')); ?></span>
            </button>
            <span id="orch-credits-estimate" class="orch3-estimate"></span>
        </div>
    </div>


    <!-- ═══ Progress ═══ -->
    <div id="orchestrator-progress" style="display:none;">
        <div class="orch3-card">
            <h2 class="orch3-h2"><span class="orch3-spin"></span> <?php echo esc_html(SEO_AEO_T::t('Analisi in corso')); ?></h2>
            <div class="orch3-progress-meta">
                <span id="orch-progress-counter">0 / 0</span>
                <span id="orch-progress-pct">0%</span>
            </div>
            <div class="orch3-progress-track">
                <div id="orch-progress-bar" class="orch3-progress-fill"></div>
            </div>
            <p id="orch-progress-text" class="orch3-progress-text"><?php echo esc_html(SEO_AEO_T::t('Preparazione...')); ?></p>
            <p id="orch-progress-eta" class="orch3-progress-eta"><?php echo esc_html(SEO_AEO_T::t('Tempo stimato: calcolo...')); ?></p>
            <div class="orch3-center">
                <button type="button" id="orch-cancel-analysis" class="orch3-btn orch3-btn-ghost-danger"><?php echo esc_html(SEO_AEO_T::t('Annulla analisi')); ?></button>
            </div>
            <div id="orch-progress-log" class="orch3-log"></div>
        </div>
    </div>

    <!-- ═══ Results ═══ -->
    <div id="orchestrator-results" style="display:none;">
        <!-- Global scores -->
        <div class="orch3-card">
            <h2 class="orch3-h2"><?php echo esc_html(SEO_AEO_T::t('Risultato Analisi Sito')); ?></h2>
            <div class="stats-grid orch3-stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:15px;">
                <div class="stat-box orch3-stat">
                    <span class="stat-value orch3-stat-value" id="orch-avg-seo">--</span>
                    <span class="stat-label orch3-stat-label"><?php echo esc_html(SEO_AEO_T::t('SEO Medio')); ?></span>
                </div>
                <div class="stat-box orch3-stat">
                    <span class="stat-value orch3-stat-value" id="orch-avg-aeo">--</span>
                    <span class="stat-label orch3-stat-label"><?php echo esc_html(SEO_AEO_T::t('AEO Medio')); ?></span>
                </div>
                <div class="stat-box orch3-stat">
                    <span class="stat-value orch3-stat-value" id="orch-pages-analyzed">--</span>
                    <span class="stat-label orch3-stat-label"><?php echo esc_html(SEO_AEO_T::t('Pagine')); ?></span>
                </div>
            </div>
            <div class="stats-grid orch3-stats-grid" style="grid-template-columns:repeat(3,1fr);">
                <div class="stat-box stat-box-clickable orch3-stat orch3-stat-click" onclick="if(window.SeoAeoOrchestra)SeoAeoOrchestra.toggleIssueDetail('seo-issues');">
                    <span class="stat-value orch3-stat-value orch3-bad" id="orch-seo-issues-count">--</span>
                    <span class="stat-label orch3-stat-label"><?php echo esc_html(SEO_AEO_T::t('Problemi SEO')); ?></span>
                    <span class="stat-click-hint orch3-hint"><?php echo esc_html(SEO_AEO_T::t('clicca per dettagli')); ?></span>
                </div>
                <div class="stat-box stat-box-clickable orch3-stat orch3-stat-click" onclick="if(window.SeoAeoOrchestra)SeoAeoOrchestra.toggleIssueDetail('aeo-issues');">
                    <span class="stat-value orch3-stat-value orch3-warn" id="orch-aeo-issues-count">--</span>
                    <span class="stat-label orch3-stat-label"><?php echo esc_html(SEO_AEO_T::t('Problemi AEO')); ?></span>
                    <span class="stat-click-hint orch3-hint"><?php echo esc_html(SEO_AEO_T::t('clicca per dettagli')); ?></span>
                </div>
                <div class="stat-box stat-box-clickable orch3-stat orch3-stat-click" onclick="if(window.SeoAeoOrchestra)SeoAeoOrchestra.toggleIssueDetail('actions');">
                    <span class="stat-value orch3-stat-value orch3-info" id="orch-total-actions">--</span>
                    <span class="stat-label orch3-stat-label"><?php echo esc_html(SEO_AEO_T::t('Azioni Suggerite')); ?></span>
                    <span class="stat-click-hint orch3-hint"><?php echo esc_html(SEO_AEO_T::t('clicca per dettagli')); ?></span>
                </div>
            </div>
            <div id="orch-detail-seo-issues" class="orch-stat-detail orch3-detail orch3-detail-bad"></div>
            <div id="orch-detail-aeo-issues" class="orch-stat-detail orch3-detail orch3-detail-warn"></div>
            <div id="orch-detail-actions" class="orch-stat-detail orch3-detail orch3-detail-info"></div>
        </div>

        <div class="orch3-card">
            <h2 class="orch3-h2"><?php echo esc_html(SEO_AEO_T::t('Piano d\'Azione Prioritizzato')); ?></h2>
            <p class="orch3-muted"><?php echo esc_html(SEO_AEO_T::t('Azioni ordinate per priorità. Clicca "Esegui" per delegare all\'agente specifico.')); ?></p>
            <div id="orch-action-plan"></div>
        </div>

        <div class="orch3-card">
            <h2 class="orch3-h2"><?php echo esc_html(SEO_AEO_T::t('Dettaglio per Pagina')); ?></h2>
            <p class="orch3-muted"><?php echo esc_html(SEO_AEO_T::t('Classifica dalla pagina peggiore alla migliore. Clicca su una pagina per vedere problemi e suggerimenti.')); ?></p>
            <div id="orch-page-results"></div>
        </div>

        <div class="orch3-center" style="margin:20px 0;">
            <button type="button" class="orch3-btn orch3-btn-primary orch3-btn-large" id="orch-restart"><?php echo esc_html(SEO_AEO_T::t('Nuova analisi')); ?></button>
        </div>
    </div>

    </div><!-- /.orch5-main -->

    <!-- 3.4.6 — Rail con 2 aside indipendenti -->
    <div class="orch5-side-rail">
    <aside class="orch5-side orch5-side--history">
        <div class="orch5-side-head">
            <h2 class="orch5-side-title"><?php echo esc_html(SEO_AEO_T::t('Cronologia analisi')); ?> <span class="orch5-side-count" id="orch-history-count"></span></h2>
            <div class="orch5-side-filters" role="tablist">
                <button type="button" class="orch5-side-filter active" data-filter-range="all"><?php echo esc_html(SEO_AEO_T::t('Tutte')); ?></button>
                <button type="button" class="orch5-side-filter" data-filter-range="7">7g</button>
                <button type="button" class="orch5-side-filter" data-filter-range="30">30g</button>
                <button type="button" class="orch5-side-clear" id="orch-history-clear" title="<?php echo class_exists('SEO_AEO_T') ? esc_attr(SEO_AEO_T::t('Svuota cronologia')) : 'Svuota cronologia'; ?>">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path></svg>
                    <span><?php echo esc_html(SEO_AEO_T::t('Svuota')); ?></span>
                </button>
            </div>
        </div>
        <div id="history-orchestrator" class="orchestra-history-container"></div>
    </aside>

    <!-- 3.4.6 — Modifiche recenti come aside indipendente -->
    <aside class="orch5-side orch5-side--mods collapsed" id="orch-mods-card">
        <div class="orch5-side-head orch-mods-head" id="orch-mods-toggle" role="button" tabindex="0" aria-expanded="false" aria-controls="orch-mods-list">
            <span class="orch-mods-chevron" aria-hidden="true">&#9656;</span>
            <h2 class="orch5-side-title orch-mods-title"><?php echo esc_html(SEO_AEO_T::t('Modifiche recenti')); ?> <span class="orch5-side-count orch-mods-count" id="orch-mods-count"></span></h2>
            <div class="orch-mods-sub"><?php echo esc_html(SEO_AEO_T::t('Reversibili per 7 giorni')); ?></div>
        </div>
        <div class="orch-mods-list" id="orch-mods-list" aria-hidden="true">
            <div class="orch-mods-empty"><?php echo esc_html(SEO_AEO_T::t('Nessuna modifica ancora applicata.')); ?></div>
        </div>
    </aside>
    </div><!-- /.orch5-side-rail -->

    <!-- 3.0.0 — Modal conferma undo -->
    <div id="rv-undo-overlay" role="dialog" aria-modal="true">
        <div class="rv-modal">
            <h3><?php echo esc_html(SEO_AEO_T::t('Ripristinare versione precedente?')); ?></h3>
            <p id="rv-undo-msg"><?php echo esc_html(SEO_AEO_T::t('Il contenuto tornerà allo stato precedente la modifica. Dopo il ripristino perderai la versione applicata.')); ?></p>
            <div class="rv-modal-foot">
                <button type="button" class="rv-btn rv-btn-ghost" id="rv-undo-cancel"><?php echo esc_html(SEO_AEO_T::t('Annulla')); ?></button>
                <button type="button" class="rv-btn rv-btn-danger" id="rv-undo-confirm"><?php echo esc_html(SEO_AEO_T::t('Ripristina')); ?></button>
            </div>
        </div>
    </div>

    <!-- 3.4.5 — Modal conferma svuota cronologia analisi -->
    <div id="rv-clear-history-overlay" role="dialog" aria-modal="true">
        <div class="rv-modal">
            <h3><?php echo esc_html(SEO_AEO_T::t('Cancella cronologia analisi')); ?></h3>
            <p id="rv-clear-history-msg"><?php echo esc_html(SEO_AEO_T::t('Verranno cancellate')); ?> <span id="rv-clear-history-count"><?php echo esc_html(SEO_AEO_T::t('tutte')); ?></span> <?php echo esc_html(SEO_AEO_T::t('le analisi precedenti. I crediti già consumati')); ?> <strong><?php echo esc_html(SEO_AEO_T::t('non vengono rimborsati')); ?></strong>. <?php echo esc_html(SEO_AEO_T::t('Procedere?')); ?></p>
            <div class="rv-modal-foot">
                <button type="button" class="rv-btn rv-btn-ghost" id="rv-clear-history-cancel"><?php echo esc_html(SEO_AEO_T::t('Annulla')); ?></button>
                <button type="button" class="rv-btn rv-btn-danger" id="rv-clear-history-confirm"><?php echo esc_html(SEO_AEO_T::t('Cancella')); ?></button>
            </div>
        </div>
    </div>

    <!-- 3.4.3 — Modal dettagli snapshot (riusato per banner "Rivedi") -->
    <div id="rv-details-overlay" role="dialog" aria-modal="true" aria-labelledby="rv-details-title">
        <div class="rv-modal rv-modal-wide">
            <div class="rv-details-head">
                <div>
                    <h3 id="rv-details-title"><?php echo esc_html(SEO_AEO_T::t('Dettagli modifica')); ?></h3>
                    <div class="rv-details-meta" id="rv-details-meta"></div>
                </div>
                <button type="button" class="rv-details-close" id="rv-details-close" aria-label="<?php echo class_exists('SEO_AEO_T') ? esc_attr(SEO_AEO_T::t('Chiudi')) : 'Chiudi'; ?>">&times;</button>
            </div>
            <div class="rv-details-body" id="rv-details-body">
                <div class="rv-details-loading"><?php echo esc_html(SEO_AEO_T::t('Caricamento dettagli...')); ?></div>
            </div>
            <div class="rv-modal-foot">
                <button type="button" class="rv-btn rv-btn-ghost" id="rv-details-cancel"><?php echo esc_html(SEO_AEO_T::t('Chiudi')); ?></button>
                <button type="button" class="rv-btn rv-btn-danger" id="rv-details-restore" data-snapshot-id=""><?php echo esc_html(SEO_AEO_T::t('Ripristina')); ?></button>
            </div>
        </div>
    </div>

    <div id="rv-toast" role="status"></div>

    </div><!-- /.orch5-body -->
</div><!-- /.wrap.orchestra-admin -->

<?php ob_start(); ?>
/* ═══════════════════════════════════════════════════════════════════
   AEO Orchestra — Orchestratore 3.41.5
   Restyling fedele al mockup: Satoshi + General Sans, palette slate.
   Tutto scoped sotto .orchestra-v3 per non contaminare altre pagine WP.
   ═══════════════════════════════════════════════════════════════════ */

.orchestra-v3 {
    --orch-ink:        #0f172a;
    --orch-muted:      #475569;
    --orch-subtle:     #64748b;
    --orch-faint:      #94a3b8;
    --orch-line:       #e4e4e7;
    --orch-line-soft:  #f1f5f9;
    --orch-bg:         #fafafa;
    --orch-good:       #0f766e;
    --orch-warn:       #d97706;
    --orch-bad:        #b91c1c;
    --orch-info:       #2563eb;
    --orch-accent:     #0f172a;

    font-family: "General Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: var(--orch-ink);
    max-width: 1140px;
}
.orchestra-v3 *, .orchestra-v3 *::before, .orchestra-v3 *::after { box-sizing: border-box; }

/* Header navy */
.orchestra-v3 .orch3-header {
    background: linear-gradient(135deg, #0b1220 0%, #1a2540 100%);
    color: #e2e8f0;
    padding: 24px 28px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    margin: 10px 0 20px;
    position: relative;
    overflow: hidden;
    flex-wrap: wrap;
}
.orchestra-v3 .orch3-header::before {
    content: "";
    position: absolute; inset: 0;
    background: radial-gradient(circle at 85% 20%, rgba(120, 150, 230, 0.18), transparent 45%);
    pointer-events: none;
}
.orchestra-v3 .orch3-h1 {
    font-family: "Satoshi", sans-serif;
    font-weight: 700;
    font-size: 28px;
    letter-spacing: -0.025em;
    margin: 0 0 4px;
    color: #fff;
    padding: 0;
    line-height: 1.15;
}
.orchestra-v3 .orch3-sub {
    margin: 0;
    color: #b8c2d9;
    font-size: 13.5px;
    max-width: 620px;
    line-height: 1.5;
}
.orchestra-v3 .orch3-credit-badge {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.12);
    padding: 12px 16px;
    border-radius: 8px;
    color: #e2e8f0;
    font-size: 12px;
    min-width: 200px;
    position: relative;
    z-index: 1;
    backdrop-filter: blur(6px);
}
.orchestra-v3 .orch3-credit-badge .num {
    font-family: "Satoshi", sans-serif;
    font-size: 26px;
    font-weight: 700;
    letter-spacing: -0.03em;
    display: block;
    line-height: 1.05;
    font-variant-numeric: tabular-nums;
    color: #fff;
}
.orchestra-v3 .orch3-credit-badge .sub {
    color: #94a3b8;
    font-size: 11.5px;
    margin-top: 4px;
    line-height: 1.4;
    display: block;
}
.orchestra-v3 .orch3-credit-badge .sub a {
    color: #93c5fd;
    text-decoration: none;
    font-weight: 500;
    border-bottom: 1px solid rgba(147,197,253,0.35);
}
.orchestra-v3 .orch3-credit-badge .sub a:hover { border-bottom-color: #93c5fd; }

/* Card base */
.orchestra-v3 .orch3-card {
    background: #fff;
    border: 1px solid var(--orch-line);
    border-radius: 10px;
    padding: 22px 24px;
    margin-bottom: 14px;
}
.orchestra-v3 .orch3-card-head { margin-bottom: 14px; }
.orchestra-v3 .orch3-h2 {
    font-family: "Satoshi", sans-serif;
    font-weight: 700;
    font-size: 18px;
    letter-spacing: -0.02em;
    color: var(--orch-ink);
    margin: 0 0 4px;
    padding: 0;
    line-height: 1.3;
}
.orchestra-v3 .orch3-muted { color: var(--orch-muted); font-size: 13px; margin: 0; line-height: 1.55; }


/* ═══ Feature grid (3.35.11) ═══ */
.orchestra-v3 .orch3-features-grid-card { background:#fff; }
.orchestra-v3 .orch3-feature-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 14px;
    margin-top: 4px;
}
.orchestra-v3 .orch3-feature-card {
    display: block;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 18px 18px 16px;
    text-decoration: none;
    color: inherit;
    transition: all 0.18s ease;
    position: relative;
    cursor: pointer;
    min-height: 116px;
}
.orchestra-v3 .orch3-feature-card:hover,
.orchestra-v3 .orch3-feature-card:focus {
    border-color: #0055FF;
    box-shadow: 0 8px 24px rgba(0,85,255,.12);
    transform: translateY(-2px);
    text-decoration: none;
    color: inherit;
}
.orchestra-v3 .orch3-feature-card.orch3-fc-new::before {
    content: 'NEW';
    position: absolute;
    top: 10px;
    right: 10px;
    background: linear-gradient(135deg, #0055FF, #00E5FF);
    color: #fff;
    font-size: 9px;
    padding: 3px 7px;
    border-radius: 10px;
    font-weight: 700;
    letter-spacing: 0.05em;
}
.orchestra-v3 .orch3-feature-card.orch3-fc-new:has(.orch3-fc-badge)::before { display: none; }
.orchestra-v3 .orch3-fc-icon { font-size: 30px; line-height: 1; margin-bottom: 10px; }
.orchestra-v3 .orch3-fc-title {
    font-family: "Satoshi", sans-serif;
    font-size: 15px;
    font-weight: 700;
    color: #0A0E27;
    margin-bottom: 4px;
    letter-spacing: -0.01em;
}
.orchestra-v3 .orch3-fc-desc {
    font-size: 12px;
    color: #6b7280;
    line-height: 1.45;
}
.orchestra-v3 .orch3-fc-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    font-size: 10px;
    padding: 3px 8px;
    border-radius: 12px;
    font-weight: 600;
    box-shadow: 0 2px 6px rgba(16,185,129,.25);
}
.orchestra-v3 .orch3-features-callout {
    margin-top: 16px;
    padding: 12px 14px;
    background: linear-gradient(90deg, rgba(16,185,129,.06), rgba(0,229,255,.04));
    border: 1px solid rgba(16,185,129,.18);
    border-radius: 10px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 13px;
    color: #0A0E27;
    line-height: 1.5;
}
.orchestra-v3 .orch3-fc-callout-icon { font-size: 18px; line-height: 1.4; flex: 0 0 auto; }
.orchestra-v3 .orch3-features-callout strong { color: #059669; font-weight: 700; }
@media (max-width: 600px) {
    .orchestra-v3 .orch3-feature-cards { grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px; }
    .orchestra-v3 .orch3-feature-card { padding: 14px; min-height: 100px; }
    .orchestra-v3 .orch3-fc-icon { font-size: 26px; margin-bottom: 8px; }
    .orchestra-v3 .orch3-fc-title { font-size: 14px; }
}
/* Quick win */
.orchestra-v3 .orch3-quickwin {
    background: #fff;
    border: 1px solid var(--orch-line);
    border-left: 4px solid var(--orch-good);
    border-radius: 10px;
    padding: 18px 22px;
    margin-bottom: 14px;
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}
.orchestra-v3 .qw-body { flex: 1 1 300px; min-width: 0; }
.orchestra-v3 .qw-eyebrow {
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: var(--orch-good);
    font-weight: 600;
    margin-bottom: 6px;
}
.orchestra-v3 .qw-body h3 {
    font-family: "Satoshi", sans-serif;
    font-weight: 700;
    font-size: 18px;
    margin: 0 0 6px;
    letter-spacing: -0.02em;
    color: var(--orch-ink);
}
.orchestra-v3 .qw-body p {
    margin: 0;
    font-size: 13.5px;
    color: var(--orch-muted);
    line-height: 1.6;
}
.orchestra-v3 .qw-side { display: flex; gap: 12px; align-items: center; }
.orchestra-v3 .qw-score-box { text-align: center; }
.orchestra-v3 .qw-score-num {
    font-family: "Satoshi", sans-serif;
    font-size: 28px;
    font-weight: 900;
    line-height: 1;
    color: var(--orch-good);
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.03em;
}
.orchestra-v3 .qw-score-lbl {
    font-size: 10px;
    color: var(--orch-subtle);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-top: 2px;
}

/* Buttons */
.orchestra-v3 .orch3-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    border-radius: 8px;
    font-family: "General Sans", sans-serif;
    font-size: 13.5px;
    font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer;
    transition: background 0.12s, border-color 0.12s, transform 0.06s;
    text-decoration: none;
    line-height: 1.2;
    letter-spacing: -0.005em;
}
.orchestra-v3 .orch3-btn:active { transform: translateY(1px); }
.orchestra-v3 .orch3-btn-primary {
    background: var(--orch-accent);
    color: #fff;
    border-color: var(--orch-accent);
}
.orchestra-v3 .orch3-btn-primary:hover { background: #1e293b; color: #fff; }
.orchestra-v3 .orch3-btn-large { padding: 12px 22px; font-size: 14px; }
.orchestra-v3 .orch3-btn-ghost {
    background: transparent;
    color: var(--orch-muted);
    border-color: var(--orch-line);
    padding: 8px 12px;
}
.orchestra-v3 .orch3-btn-ghost:hover { background: var(--orch-line-soft); color: var(--orch-ink); }
.orchestra-v3 .orch3-btn-ghost-danger {
    background: transparent;
    color: var(--orch-bad);
    border-color: var(--orch-bad);
    padding: 8px 14px;
}
.orchestra-v3 .orch3-btn-ghost-danger:hover { background: #fef2f2; }

/* Toolbar */
.orchestra-v3 .orch3-toolbar {
    display: flex;
    gap: 6px;
    align-items: center;
    flex-wrap: wrap;
    margin: 0 0 12px;
}
.orchestra-v3 .orch3-chip {
    background: #fff;
    border: 1px solid var(--orch-line);
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12.5px;
    font-weight: 500;
    color: var(--orch-ink);
    cursor: pointer;
    transition: background 0.12s, border-color 0.12s;
    font-family: "General Sans", sans-serif;
}
.orchestra-v3 .orch3-chip:hover { background: var(--orch-bg); border-color: var(--orch-faint); }
.orchestra-v3 .orch3-count {
    margin-left: 8px;
    font-size: 12.5px;
    color: var(--orch-muted);
    font-weight: 500;
}

/* Page list */
.orchestra-v3 .orch3-page-list {
    max-height: 320px;
    overflow-y: auto;
    border: 1px solid var(--orch-line);
    border-radius: 8px;
    background: #fff;
    margin: 14px 0 16px;
}
.orchestra-v3 .orch3-page-item {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 12px;
    align-items: center;
    padding: 10px 14px;
    border-bottom: 1px solid var(--orch-line-soft);
    font-size: 13px;
    cursor: pointer;
    transition: background 0.08s;
}
.orchestra-v3 .orch3-page-item:last-child { border-bottom: 0; }
.orchestra-v3 .orch3-page-item:hover { background: var(--orch-bg); }
.orchestra-v3 .orch3-page-item.is-home { background: #eff6ff; }
.orchestra-v3 .orch3-page-item.is-home:hover { background: #dbeafe; }
.orchestra-v3 .orch3-page-item input[type="checkbox"] {
    accent-color: var(--orch-ink);
    width: 16px;
    height: 16px;
    margin: 0;
}
.orchestra-v3 .pi-title { display: flex; align-items: center; gap: 8px; min-width: 0; }
.orchestra-v3 .pi-title strong {
    font-weight: 600;
    color: var(--orch-ink);
    font-size: 13px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.orchestra-v3 .pi-type {
    font-size: 10.5px;
    color: var(--orch-faint);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-weight: 600;
}
.orchestra-v3 .pi-badge-home {
    background: var(--orch-info);
    color: #fff;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.03em;
}
.orchestra-v3 .pi-flag {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}
.orchestra-v3 .pi-flag.ok { background: #10b981; }
.orchestra-v3 .pi-flag.warn { background: var(--orch-warn); }

/* Run bar */
.orchestra-v3 .orch3-runbar {
    display: flex;
    gap: 14px;
    align-items: center;
    flex-wrap: wrap;
    padding-top: 4px;
}
.orchestra-v3 .orch3-estimate {
    font-size: 13px;
    color: var(--orch-muted);
    font-weight: 500;
}
.orchestra-v3 .orch3-estimate strong { color: var(--orch-ink); font-weight: 600; }

/* Progress */
.orchestra-v3 .orch3-progress-meta {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    font-weight: 600;
    color: var(--orch-ink);
    margin-bottom: 10px;
    font-variant-numeric: tabular-nums;
}
.orchestra-v3 .orch3-progress-track {
    background: var(--orch-line-soft);
    border-radius: 999px;
    height: 10px;
    overflow: hidden;
    margin-bottom: 14px;
}
.orchestra-v3 .orch3-progress-fill {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, var(--orch-info), #7c3aed);
    border-radius: 999px;
    transition: width 0.5s ease;
}
.orchestra-v3 .orch3-progress-text {
    text-align: center;
    color: var(--orch-muted);
    margin: 0 0 4px;
    font-size: 13px;
    font-weight: 500;
}
.orchestra-v3 .orch3-progress-eta {
    text-align: center;
    color: var(--orch-faint);
    font-size: 12px;
    margin: 0;
}
.orchestra-v3 .orch3-center { text-align: center; margin-top: 14px; }
.orchestra-v3 .orch3-log {
    max-height: 200px;
    overflow-y: auto;
    font-size: 12px;
    color: var(--orch-subtle);
    padding: 12px;
    background: var(--orch-bg);
    border: 1px solid var(--orch-line-soft);
    border-radius: 8px;
    margin-top: 14px;
    font-family: ui-monospace, "SF Mono", Menlo, monospace;
}
.orchestra-v3 .orch3-spin {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid var(--orch-line);
    border-top-color: var(--orch-info);
    border-radius: 50%;
    animation: orch3-spin 0.8s linear infinite;
    vertical-align: -2px;
    margin-right: 6px;
}
@keyframes orch3-spin { to { transform: rotate(360deg); } }

/* Stats (risultati) */
.orchestra-v3 .orch3-stats-grid { display: grid; gap: 10px; }
.orchestra-v3 .orch3-stat {
    background: #fff;
    border: 1px solid var(--orch-line);
    border-radius: 8px;
    padding: 16px 18px;
    text-align: left;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.orchestra-v3 .orch3-stat-value {
    font-family: "Satoshi", sans-serif;
    font-size: 30px;
    font-weight: 900;
    letter-spacing: -0.03em;
    line-height: 1;
    color: var(--orch-ink);
    font-variant-numeric: tabular-nums;
    display: block;
}
.orchestra-v3 .orch3-stat-value.orch3-bad { color: var(--orch-bad); }
.orchestra-v3 .orch3-stat-value.orch3-warn { color: var(--orch-warn); }
.orchestra-v3 .orch3-stat-value.orch3-info { color: var(--orch-info); }
.orchestra-v3 .orch3-stat-label {
    font-size: 11.5px;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--orch-subtle);
    font-weight: 600;
    display: block;
    margin-top: 6px;
}
.orchestra-v3 .orch3-stat-click { cursor: pointer; transition: background 0.12s, box-shadow 0.12s, transform 0.08s; }
.orchestra-v3 .orch3-stat-click:hover { background: var(--orch-bg); box-shadow: 0 2px 8px rgba(0,0,0,0.04); transform: translateY(-1px); }
.orchestra-v3 .orch3-hint {
    font-size: 10px;
    color: var(--orch-faint);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    display: block;
    margin-top: 2px;
}
.orchestra-v3 .orch3-detail {
    display: none;
    margin-top: 12px;
    padding: 14px 16px;
    border-radius: 8px;
    max-height: 300px;
    overflow: auto;
    font-size: 13px;
    line-height: 1.55;
}
.orchestra-v3 .orch3-detail-bad { background: #fef2f2; color: #7f1d1d; }
.orchestra-v3 .orch3-detail-warn { background: #fffbeb; color: #78350f; }
.orchestra-v3 .orch3-detail-info { background: #eff6ff; color: #1e3a8a; }
/* 3.38.8 Task 3 — Inline problem cards with "Come risolvere" + executors. */
.orchestra-v3 .orch-problem-cards { display: flex; flex-direction: column; gap: 10px; }
.orchestra-v3 .orch3-detail { max-height: none; }
.orchestra-v3 .orch-problem-card { background: #fff; border: 1px solid rgba(127,29,29,0.18); border-radius: 8px; padding: 12px 14px; }
.orchestra-v3 .orch3-detail-warn .orch-problem-card { border-color: rgba(120,53,15,0.20); }
.orchestra-v3 .orch-problem-head { display: flex; align-items: flex-start; gap: 8px; }
.orchestra-v3 .orch-problem-icon { font-size: 14px; line-height: 1.6; }
.orchestra-v3 .orch-problem-title { flex: 1; color: var(--orch-text); font-size: 14px; line-height: 1.5; }
.orchestra-v3 .orch-problem-count { color: var(--orch-muted); font-weight: 500; font-size: 12px; }
.orchestra-v3 .orch-problem-solution { margin: 8px 0 10px; padding: 9px 12px; background: #f8fafc; border-left: 3px solid #3b82f6; border-radius: 0 6px 6px 0; font-size: 13px; color: #334155; line-height: 1.55; }
.orchestra-v3 .orch-problem-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.orchestra-v3 .orch-problem-exec { background: #0055FF; color: #fff; border-color: #0040cc; }
.orchestra-v3 .orch-problem-exec:hover { background: #0040cc; color: #fff; }
.orchestra-v3 .orch-problem-toggle-pages { background: #fff; }
.orchestra-v3 .orch-problem-pages { margin: 10px 0 0; padding: 8px 14px 8px 28px; background: #f9fafb; border-radius: 6px; max-height: 200px; overflow: auto; list-style: disc; font-size: 12px; color: var(--orch-muted); }
.orchestra-v3 .orch-problem-pages li { margin: 2px 0; }
.orchestra-v3 .orch-problem-pages[hidden] { display: none; }

   2.6.0 — To-do prioritizzato + fix credit badge
   ═══════════════════════════════════════════════════════════════ */

/* To-do card */
.orchestra-v3 .orch6-todo-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 14px;
}
.orchestra-v3 .orch6-todo-counter {
    font-size: 11.5px;
    color: var(--orch-subtle);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-weight: 600;
    padding: 4px 10px;
    background: var(--orch-line-soft);
    border-radius: 999px;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}
.orchestra-v3 .orch6-todo-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.orchestra-v3 .orch6-todo-item {
    display: flex;
    gap: 12px;
    padding: 14px 16px;
    border: 1px solid var(--orch-line);
    border-radius: 8px;
    background: #fff;
    transition: border-color 0.12s, opacity 0.2s, max-height 0.2s;
    align-items: flex-start;
}
.orchestra-v3 .orch6-todo-item:hover { border-color: var(--orch-faint); }
.orchestra-v3 .orch6-todo-item.orch6-todo-extra { display: none; }
.orchestra-v3 .orch6-todo-list.is-expanded .orch6-todo-extra { display: flex; }
.orchestra-v3 .orch6-todo-item.is-done {
    opacity: 0.5;
    background: var(--orch-bg);
}
/* 3.38.8 Task 2 — hide completed items unless the user opts in. */
.orchestra-v3 .orch6-todo-list:not(.show-done) .orch6-todo-item.is-done { display: none !important; }
.orchestra-v3 .orch6-todo-toggle-done {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    margin-left: 8px;
    font-size: 12px;
    border-radius: 14px;
    border: 1px solid var(--orch-faint);
    background: #fff;
    color: var(--orch-muted);
    cursor: pointer;
    transition: all 0.15s ease;
}
.orchestra-v3 .orch6-todo-toggle-done:hover { color: var(--orch-text); border-color: var(--orch-accent); }
.orchestra-v3 .orch6-todo-toggle-done.is-on { color: var(--orch-accent); border-color: var(--orch-accent); background: #f0f7ff; }
.orchestra-v3 .orch6-todo-toggle-done[hidden] { display: none; }
.orchestra-v3 .orch6-todo-item.is-done .orch6-todo-title strong {
    text-decoration: line-through;
    color: var(--orch-muted);
}

/* Checkbox customizzata */
.orchestra-v3 .orch6-todo-check {
    display: block;
    position: relative;
    cursor: pointer;
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    margin-top: 2px;
}
.orchestra-v3 .orch6-todo-check input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    width: 20px;
    height: 20px;
    margin: 0;
    z-index: 1;
}
.orchestra-v3 .orch6-todo-mark {
    position: absolute;
    top: 0; left: 0;
    width: 20px; height: 20px;
    border: 1.5px solid var(--orch-faint);
    border-radius: 5px;
    background: #fff;
    transition: background 0.12s, border-color 0.12s;
}
.orchestra-v3 .orch6-todo-mark::after {
    content: "";
    position: absolute;
    top: 2px; left: 6px;
    width: 5px; height: 10px;
    border: solid #fff;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
    opacity: 0;
    transition: opacity 0.12s;
}
.orchestra-v3 .orch6-todo-check input:checked ~ .orch6-todo-mark {
    background: var(--orch-good);
    border-color: var(--orch-good);
}
.orchestra-v3 .orch6-todo-check input:checked ~ .orch6-todo-mark::after {
    opacity: 1;
}

.orchestra-v3 .orch6-todo-body { flex: 1; min-width: 0; }
.orchestra-v3 .orch6-todo-title {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 4px;
}
.orchestra-v3 .orch6-todo-title strong {
    font-weight: 600;
    color: var(--orch-ink);
    font-size: 13.5px;
    letter-spacing: -0.005em;
}
.orchestra-v3 .orch6-todo-desc {
    font-size: 12.5px;
    color: var(--orch-muted);
    line-height: 1.5;
    margin-top: 2px;
}
.orchestra-v3 .orch6-todo-page {
    font-size: 11.5px;
    color: var(--orch-subtle);
    margin-top: 4px;
}
.orchestra-v3 .orch6-todo-page em {
    color: var(--orch-muted);
    font-style: italic;
}

.orchestra-v3 .orch6-todo-more {
    margin-top: 10px;
    width: 100%;
    background: #fff;
    border: 1px dashed var(--orch-line);
    padding: 9px 14px;
    border-radius: 8px;
    font-size: 12.5px;
    color: var(--orch-muted);
    cursor: pointer;
    font-family: "General Sans", sans-serif;
    font-weight: 500;
    transition: background 0.12s, border-color 0.12s;
}
.orchestra-v3 .orch6-todo-more:hover {
    background: var(--orch-bg);
    border-color: var(--orch-faint);
    color: var(--orch-ink);
}
.orchestra-v3 .orch6-todo-more.is-expanded { display: none; }

/* Empty state to-do (tutti completati) */
.orchestra-v3 .orch6-todo-empty {
    padding: 20px 14px;
    text-align: center;
    color: var(--orch-subtle);
    font-size: 13px;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    border-radius: 8px;
}
.orchestra-v3 .orch6-todo-empty strong {
    color: #047857;
    font-weight: 600;
}

/* ═══════════════════════════════════════════════════════════════
   3.31.2 — Action plan tile layout (alternative to vertical list)
   ═══════════════════════════════════════════════════════════════ */
.orchestra-v3 .orch6-todo-tiles .orch6-todo-list {
    gap: 18px;
}
.orchestra-v3 .orch6-tile-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.orchestra-v3 .orch6-tile-group-head {
    margin: 0 0 4px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--orch-subtle);
    display: flex;
    align-items: center;
    gap: 6px;
}
.orchestra-v3 .orch6-tile-group-icon { font-size: 14px; }
.orchestra-v3 .orch6-tile-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}
@media (max-width: 900px) {
    .orchestra-v3 .orch6-tile-grid { grid-template-columns: 1fr; }
}
.orchestra-v3 .orch6-tile {
    display: flex;
    gap: 10px;
    padding: 12px 14px;
    border: 1px solid var(--orch-line);
    border-radius: 10px;
    background: #fff;
    transition: transform 0.12s ease, box-shadow 0.12s ease, border-color 0.12s ease;
    align-items: flex-start;
}
.orchestra-v3 .orch6-tile:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(0,0,0,0.06);
    border-color: #cbd5e1;
}
.orchestra-v3 .orch6-tile.is-done {
    opacity: 0.5;
    background: #f8fafc;
}
.orchestra-v3 .orch6-tile.is-done .orch6-tile-action { text-decoration: line-through; }
.orchestra-v3 .orch6-tile.priority-critica { border-left: 4px solid #dc2626; }
.orchestra-v3 .orch6-tile.priority-alta    { border-left: 4px solid #ef4444; }
.orchestra-v3 .orch6-tile.priority-media   { border-left: 4px solid #f59e0b; }
.orchestra-v3 .orch6-tile.priority-bassa   { border-left: 4px solid #10b981; }
.orchestra-v3 .orch6-tile-check {
    flex-shrink: 0;
    margin-top: 2px;
}
.orchestra-v3 .orch6-tile-body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.orchestra-v3 .orch6-tile-action {
    font-weight: 600;
    color: var(--orch-ink);
    font-size: 13.5px;
    line-height: 1.3;
}
.orchestra-v3 .orch6-tile-page {
    font-size: 12px;
    color: var(--orch-subtle);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.orchestra-v3 .orch6-tile-url {
    font-size: 11px;
    color: #94a3b8 !important;
    word-break: break-all;
    text-decoration: none;
    line-height: 1.3;
}
.orchestra-v3 .orch6-tile-url:hover {
    color: #0055FF !important;
    text-decoration: underline;
}
.orchestra-v3 .orch6-tile-cta {
    flex-shrink: 0;
    align-self: center;
    padding: 6px 12px;
    background: #0055FF;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s ease;
}
.orchestra-v3 .orch6-tile-cta:hover { background: #0040CC; }
.orchestra-v3 .orch6-tile-cta:disabled { opacity: 0.5; cursor: not-allowed; }

/* Fix 2.6.0: credit badge forzato sopra stili legacy */
.orchestra-v3 .orch4-credit-badge .num,
.orchestra-v3 .orch4-credit-badge .orch4-credit-top .num {
    font-family: "Satoshi", sans-serif !important;
    font-size: 26px !important;
    font-weight: 700 !important;
    letter-spacing: -0.03em !important;
    color: #fff !important;
    line-height: 1.05 !important;
    display: inline-block !important;
}
.orchestra-v3 .orch4-credit-badge .orch4-credit-word {
    font-size: 11.5px !important;
    color: #94a3b8 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.12em !important;
    font-weight: 600 !important;
}
/* 3.5.0 — Low credits state (overrides background + ensures readable text) */
.orchestra-v3 .orch4-credit-badge.seo-aeo-low-credits {
    background: rgba(245, 158, 11, 0.15) !important;
    border-color: rgba(245, 158, 11, 0.4) !important;
}
.orchestra-v3 .orch4-credit-badge.seo-aeo-low-credits .num {
    color: #fbbf24 !important;
}
.orchestra-v3 .orch4-credit-badge.seo-aeo-low-credits .orch4-credit-word,
.orchestra-v3 .orch4-credit-badge.seo-aeo-low-credits .orch4-credit-bottom,
.orchestra-v3 .orch4-credit-badge.seo-aeo-low-credits .orch4-credit-breakdown {
    color: #fde68a !important;
}
.orchestra-v3 .orch4-credit-breakdown {
    color: #94a3b8 !important;
    font-weight: 500;
}

.orchestra-v3 .orch4-credit-badge .orch4-credit-bottom {
    color: #94a3b8 !important;
    font-size: 11.5px !important;
    margin-top: 6px !important;
}

/* Clickable history item (2.6.0): cursor + hover riga visibile */
.orchestra-v3 .orch5-side .history-item {
    cursor: pointer;
}
.orchestra-v3 .orch5-side .history-item.is-loaded {
    background: #eff6ff;
    border-left: 3px solid var(--orch-info);
    padding-left: 13px;
}

/* ═══════════════════════════════════════════════════════════════
   2.5.0 — Sidebar cronologia + Sparkline + Pagine critiche
   ═══════════════════════════════════════════════════════════════ */

/* Layout grid a 2 colonne */
.orchestra-v3 .orch5-body {
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 16px;
    align-items: start;
}
.orchestra-v3 .orch5-main { min-width: 0; }

.orchestra-v3 .orch5-side {
    background: #fff;
    border: 1px solid var(--orch-line);
    border-radius: 10px;
    padding: 0;
    position: sticky;
    top: 46px; /* wp adminbar height */
    max-height: calc(100vh - 60px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.orchestra-v3 .orch5-side-head {
    padding: 14px 16px 10px;
    border-bottom: 1px solid var(--orch-line-soft);
    flex-shrink: 0;
}
.orchestra-v3 .orch5-side-title {
    font-family: "Satoshi", sans-serif;
    font-weight: 700;
    font-size: 14px;
    letter-spacing: -0.01em;
    margin: 0 0 8px;
    color: var(--orch-ink);
    padding: 0;
    line-height: 1.3;
}
.orchestra-v3 .orch5-side-filters {
    display: flex;
    gap: 4px;
}
.orchestra-v3 .orch5-side-filter {
    background: #fff;
    border: 1px solid var(--orch-line);
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 500;
    color: var(--orch-muted);
    cursor: pointer;
    font-family: "General Sans", sans-serif;
    transition: background 0.12s, border-color 0.12s, color 0.12s;
}
.orchestra-v3 .orch5-side-filter:hover { background: var(--orch-line-soft); }
.orchestra-v3 .orch5-side-filter.active {
    background: var(--orch-ink);
    color: #fff;
    border-color: var(--orch-ink);
}

/* La container history dentro la sidebar: scrollabile */
.orchestra-v3 .orch5-side #history-orchestrator {
    flex: 1 1 auto;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 0;
    min-height: 180px;
}

/* 3.4.6 — Rail con 2 aside indipendenti (cronologia + modifiche).
   Wrapper sticky che contiene 2 sidebar separate, ognuna con propria altezza. */
.orchestra-v3 .orch5-side-rail {
    display: flex;
    flex-direction: column;
    gap: 16px;
    align-self: start;
    position: sticky;
    top: 46px;
    max-height: calc(100vh - 60px);
}
.orchestra-v3 .orch5-side-rail .orch5-side {
    position: static;
    max-height: none;
}
.orchestra-v3 .orch5-side-rail .orch5-side--history {
    flex: 1 1 auto;
    min-height: 220px;
    max-height: 60vh;
}
.orchestra-v3 .orch5-side-rail .orch5-side--mods {
    flex: 0 0 auto;
    max-height: 50vh;
    display: flex;
    flex-direction: column;
}
/* Modifiche collapsed: solo header visibile, niente max-height fissa */
.orchestra-v3 .orch5-side--mods.collapsed {
    flex: 0 0 auto;
}
.orchestra-v3 .orch5-side--mods .orch-mods-list {
    flex: 1 1 auto;
    overflow-y: auto;
    max-height: none;
}
/* Header modifiche: stessa griglia/spaziatura del header cronologia */
.orchestra-v3 .orch5-side--mods .orch-mods-head {
    padding: 14px 16px 10px;
    border-bottom: 1px solid var(--orch-line-soft);
    border-top: 0;
    flex: 0 0 auto;
    cursor: pointer;
    user-select: none;
    transition: background 0.12s ease;
    display: flex;
    align-items: baseline;
    gap: 6px;
    background: transparent;
}
.orchestra-v3 .orch5-side--mods .orch-mods-head:hover {
    background: #fafbfc;
}
.orchestra-v3 .orch5-side--mods.collapsed .orch-mods-head {
    border-bottom: 0;
}
.orchestra-v3 .orch5-side--mods .orch-mods-title {
    margin: 0;
}
.orchestra-v3 .orch5-side--mods .orch-mods-sub {
    margin-left: auto;
    font-size: 11.5px;
    color: #64748b;
}

/* Quando #history-orchestrator è dentro la sidebar, il suo inner box perde bordo ridondante */
.orchestra-v3 .orch5-side .orchestra-history {
    border: 0;
    border-radius: 0;
    background: transparent;
}
.orchestra-v3 .orch5-side .orchestra-history > div:first-child {
    padding: 12px 16px 8px !important;
    border-bottom: 1px solid var(--orch-line-soft);
}
.orchestra-v3 .orch5-side .orchestra-history h3 {
    font-size: 12.5px !important;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--orch-subtle) !important;
    font-weight: 600 !important;
}
.orchestra-v3 .orch5-side .history-item {
    padding: 10px 16px;
    font-size: 12.5px;
    gap: 8px;
    flex-wrap: wrap;
    align-items: flex-start;
}
.orchestra-v3 .orch5-side .history-item > div:first-child {
    flex: 1 1 100%;
    min-width: 0;
}
.orchestra-v3 .orch5-side .history-item .hi-title { font-size: 12.5px; }
.orchestra-v3 .orch5-side .history-item .hi-meta { font-size: 11px; }
.orchestra-v3 .orch5-side .history-item .hi-credits { font-size: 10.5px; padding: 2px 7px; }
.orchestra-v3 .orch5-side .history-rerun-btn {
    padding: 3px 8px !important;
    font-size: 11px !important;
}
.orchestra-v3 .orch5-side .history-detail {
    padding: 10px 16px !important;
    font-size: 12px;
}
.orchestra-v3 .orch5-side .history-filters { display: none; }

/* Empty state dentro sidebar — più compatto */
.orchestra-v3 .orch5-side .orch-history-empty {
    padding: 24px 16px;
    margin: 0;
    border: 0;
}
.orchestra-v3 .orch5-side .orch-history-empty .oe-title { font-size: 14px; }
.orchestra-v3 .orch5-side .orch-history-empty .oe-desc { font-size: 12px; margin-bottom: 12px; }
.orchestra-v3 .orch5-side .orch-history-empty .oe-steps { display: none; }

/* Sparkline dentro health box */
.orchestra-v3 .orch5-spark-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
}
.orchestra-v3 .orch5-spark { display: block; }
.orchestra-v3 .orch5-spark-label {
    font-size: 10.5px;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-weight: 600;
}

/* Pagine critiche */
.orchestra-v3 .orch5-critical .orch5-critical-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.orchestra-v3 .orch5-critical-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border: 1px solid var(--orch-line);
    border-radius: 8px;
    background: #fff;
    transition: border-color 0.12s;
}
.orchestra-v3 .orch5-critical-item:hover { border-color: var(--orch-faint); }
.orchestra-v3 .orch5-cp-score {
    font-family: "Satoshi", sans-serif;
    font-weight: 900;
    font-size: 22px;
    letter-spacing: -0.03em;
    line-height: 1;
    font-variant-numeric: tabular-nums;
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.orchestra-v3 .orch5-cp-bad { background: #fef2f2; color: var(--orch-bad); }
.orchestra-v3 .orch5-cp-mid { background: #fffbeb; color: var(--orch-warn); }
.orchestra-v3 .orch5-cp-body { flex: 1; min-width: 0; }
.orchestra-v3 .orch5-cp-title {
    font-weight: 600;
    color: var(--orch-ink);
    font-size: 13.5px;
    letter-spacing: -0.005em;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.orchestra-v3 .orch5-cp-meta {
    font-size: 12px;
    color: var(--orch-subtle);
    margin-top: 2px;
    font-variant-numeric: tabular-nums;
}
.orchestra-v3 .orch5-cp-meta strong {
    color: var(--orch-ink);
    font-weight: 600;
}
.orchestra-v3 .orch5-cp-btn {
    padding: 6px 12px !important;
    font-size: 12px !important;
}

/* Responsive: sidebar scende sotto il main su schermi stretti */
@media (max-width: 1100px) {
    .orchestra-v3 .orch5-body {
        grid-template-columns: 1fr;
    }
    .orchestra-v3 .orch5-side {
        position: static;
        max-height: none;
    }
    .orchestra-v3 .orch5-side #history-orchestrator {
        max-height: 500px;
    }
    /* 3.4.6 — Su mobile: rail diventa flow normale, aside con altezza propria */
    .orchestra-v3 .orch5-side-rail {
        position: static;
        max-height: none;
        gap: 16px;
    }
    .orchestra-v3 .orch5-side-rail .orch5-side--history {
        flex: 0 0 auto;
        max-height: none;
    }
    .orchestra-v3 .orch5-side-rail .orch5-side--mods {
        flex: 0 0 auto;
        max-height: none;
    }
}

/* ═══════════════════════════════════════════════════════════════
   2.4.0 — Health Score + Alert + Credit badge riprogettato
   ═══════════════════════════════════════════════════════════════ */

/* Header 2.4 — nuova griglia a 3 colonne per ospitare anche lo score */
.orchestra-v3 .orch4-header {
    display: grid;
    grid-template-columns: 1fr auto auto;
    gap: 20px;
    align-items: stretch;
}
.orchestra-v3 .orch4-header-left { align-self: center; }

/* Health Score box */
.orchestra-v3 .orch4-health {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    padding: 14px 18px;
    min-width: 200px;
    position: relative;
    z-index: 1;
    backdrop-filter: blur(4px);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.orchestra-v3 .orch4-health-top {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 4px;
    gap: 8px;
}
.orchestra-v3 .orch4-health-label {
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: #94a3b8;
    font-weight: 600;
}
.orchestra-v3 .orch4-delta {
    font-size: 12px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.005em;
    display: inline-flex;
    align-items: center;
    gap: 2px;
}
.orchestra-v3 .orch4-delta-up { color: #6ee7b7; }
.orchestra-v3 .orch4-delta-down { color: #fca5a5; }

.orchestra-v3 .orch4-health-number {
    font-family: "Satoshi", sans-serif;
    font-weight: 900;
    font-size: 40px;
    line-height: 1;
    letter-spacing: -0.035em;
    color: #fff;
    font-variant-numeric: tabular-nums;
    display: flex;
    align-items: baseline;
    gap: 2px;
}
.orchestra-v3 .orch4-health-over {
    font-size: 14px;
    color: #94a3b8;
    font-weight: 500;
    letter-spacing: -0.01em;
}

.orchestra-v3 .orch4-health-bar {
    height: 6px;
    background: rgba(255,255,255,0.08);
    border-radius: 999px;
    overflow: hidden;
    margin-top: 10px;
}
.orchestra-v3 .orch4-health-fill {
    height: 100%;
    border-radius: 999px;
    transition: width 0.5s ease;
}
.orchestra-v3 .orch4-health[data-score-class="good"] .orch4-health-fill {
    background: linear-gradient(90deg, #10b981, #34d399);
}
.orchestra-v3 .orch4-health[data-score-class="mid"] .orch4-health-fill {
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
}
.orchestra-v3 .orch4-health[data-score-class="bad"] .orch4-health-fill {
    background: linear-gradient(90deg, #dc2626, #ef4444);
}
.orchestra-v3 .orch4-health[data-score-class="neutral"] .orch4-health-fill {
    background: #64748b;
}

.orchestra-v3 .orch4-health-foot {
    font-size: 11.5px;
    color: #94a3b8;
    margin-top: 8px;
    line-height: 1.4;
}

.orchestra-v3 .orch4-health-empty .orch4-health-placeholder {
    font-family: "Satoshi", sans-serif;
    font-size: 40px;
    font-weight: 300;
    line-height: 1;
    color: #64748b;
    letter-spacing: -0.035em;
    margin-top: 4px;
}

/* Credit badge 2.4 — 2 righe, più ricco */
.orchestra-v3 .orch4-credit-badge {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 170px;
    padding: 14px 16px;
}
.orchestra-v3 .orch4-credit-top {
    display: flex;
    align-items: baseline;
    gap: 6px;
}
.orchestra-v3 .orch4-credit-top .num {
    font-family: "Satoshi", sans-serif;
    font-size: 26px;
    font-weight: 700;
    letter-spacing: -0.03em;
    line-height: 1.05;
    color: #fff;
    font-variant-numeric: tabular-nums;
}
.orchestra-v3 .orch4-credit-word {
    font-size: 11.5px;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    font-weight: 600;
}
.orchestra-v3 .orch4-credit-bottom {
    color: #94a3b8;
    font-size: 11.5px;
    margin-top: 6px;
    line-height: 1.4;
}
.orchestra-v3 .orch4-credit-bottom a {
    color: #93c5fd;
    text-decoration: none;
    font-weight: 500;
    border-bottom: 1px solid rgba(147,197,253,0.35);
}
.orchestra-v3 .orch4-credit-bottom a:hover { border-bottom-color: #93c5fd; }

/* Alert row */
.orchestra-v3 .orch4-alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 18px;
    border-radius: 8px;
    margin-bottom: 14px;
    font-size: 13.5px;
    line-height: 1.5;
    border: 1px solid transparent;
}
.orchestra-v3 .orch4-alert-info {
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #1e3a8a;
}
.orchestra-v3 .orch4-alert-warn {
    background: #fffbeb;
    border-color: #fde68a;
    color: #451a03;        /* 3.4.9 — contrasto migliorato (era #78350f, troppo chiaro) */
    font-weight: 500;
}
.orchestra-v3 .orch4-alert-warn .orch4-alert-text { color: #451a03; }
.orchestra-v3 .orch4-alert-warn .orch4-alert-text strong { color: #1e293b; }
.orchestra-v3 .orch4-alert-bad {
    background: #fef2f2;
    border-color: #fecaca;
    color: #7f1d1d;
}
.orchestra-v3 .orch4-alert-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
    display: inline-block;
    background: currentColor;
    opacity: 0.85;
    mask-size: contain;
    mask-repeat: no-repeat;
    mask-position: center;
    -webkit-mask-size: contain;
    -webkit-mask-repeat: no-repeat;
    -webkit-mask-position: center;
}
.orchestra-v3 .orch4-alert-icon[data-icon="spark"] {
    mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v4M12 17v4M3 12h4M17 12h4M5.6 5.6l2.8 2.8M15.6 15.6l2.8 2.8M5.6 18.4l2.8-2.8M15.6 8.4l2.8-2.8"/></svg>');
    -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v4M12 17v4M3 12h4M17 12h4M5.6 5.6l2.8 2.8M15.6 15.6l2.8 2.8M5.6 18.4l2.8-2.8M15.6 8.4l2.8-2.8"/></svg>');
}
.orchestra-v3 .orch4-alert-icon[data-icon="trend-down"] {
    mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/></svg>');
    -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/></svg>');
}
.orchestra-v3 .orch4-alert-icon[data-icon="clock"] {
    mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>');
    -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>');
}
.orchestra-v3 .orch4-alert-icon[data-icon="new"] {
    mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>');
    -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>');
}

/* Responsive header su schermi piccoli */
@media (max-width: 900px) {
    .orchestra-v3 .orch4-header {
        grid-template-columns: 1fr;
    }
    .orchestra-v3 .orch4-health, .orchestra-v3 .orch4-credit-badge {
        min-width: 0;
    }
}

/* History — stile pulito con pill (il JS inietta .orchestra-history > .history-item) */
.orchestra-v3 .orchestra-history-container { margin: 0 0 14px; }
.orchestra-v3 .orchestra-history {
    background: #fff;
    border: 1px solid var(--orch-line);
    border-radius: 10px;
    padding: 6px 0;
    overflow: hidden;
}
.orchestra-v3 .orchestra-history > div:first-child {
    padding: 14px 22px 12px !important;
    border-bottom: 1px solid var(--orch-line-soft);
    margin-bottom: 0 !important;
}
.orchestra-v3 .orchestra-history h3 {
    font-family: "Satoshi", sans-serif !important;
    font-size: 15px !important;
    font-weight: 700 !important;
    letter-spacing: -0.01em !important;
    color: var(--orch-ink) !important;
    margin: 0 !important;
    padding: 0 !important;
    display: flex;
    align-items: center;
    gap: 8px;
}
.orchestra-v3 .history-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 22px;
    border-bottom: 1px solid var(--orch-line-soft);
    transition: background 0.08s;
    cursor: pointer;
}
.orchestra-v3 .history-item:last-of-type { border-bottom: 0; }
.orchestra-v3 .history-item:hover { background: var(--orch-bg); }
.orchestra-v3 .history-item > div:first-child { flex: 1; min-width: 0; }
.orchestra-v3 .history-item .hi-title {
    font-weight: 600;
    color: var(--orch-ink);
    font-size: 13.5px;
    letter-spacing: -0.005em;
    display: block;
}
.orchestra-v3 .history-item .hi-meta {
    font-size: 12px;
    color: var(--orch-subtle);
    margin-top: 2px;
    display: block;
}
.orchestra-v3 .history-item .hi-credits {
    font-size: 11.5px;
    color: var(--orch-faint);
    font-variant-numeric: tabular-nums;
    font-weight: 500;
    padding: 3px 9px;
    background: var(--orch-line-soft);
    border-radius: 999px;
}
.orchestra-v3 .history-rerun-btn {
    background: transparent !important;
    border: 1px solid var(--orch-line) !important;
    border-radius: 6px !important;
    padding: 5px 10px !important;
    color: var(--orch-muted) !important;
    font-size: 12px !important;
    font-weight: 500 !important;
    transition: background 0.12s, border-color 0.12s !important;
    cursor: pointer;
    height: auto !important;
    line-height: 1.3 !important;
}
.orchestra-v3 .history-rerun-btn:hover {
    background: var(--orch-line-soft) !important;
    border-color: var(--orch-faint) !important;
    color: var(--orch-ink) !important;
}
.orchestra-v3 .history-detail {
    padding: 14px 22px !important;
    background: var(--orch-bg) !important;
    border-bottom: 1px solid var(--orch-line-soft);
    font-size: 13px;
    line-height: 1.55;
}

/* Filter history */
.orchestra-v3 .history-filters .button {
    background: #fff !important;
    border: 1px solid var(--orch-line) !important;
    color: var(--orch-muted) !important;
    font-size: 11.5px !important;
    padding: 4px 10px !important;
    border-radius: 6px !important;
    font-family: "General Sans", sans-serif !important;
    font-weight: 500 !important;
    height: auto !important;
    line-height: 1.4 !important;
}
.orchestra-v3 .history-filters .button.active {
    background: var(--orch-ink) !important;
    color: #fff !important;
    border-color: var(--orch-ink) !important;
}

/* Empty state (primo utilizzo) */
.orchestra-v3 .orch-history-empty {
    padding: 36px 28px;
    border: 1px dashed var(--orch-line);
    border-radius: 10px;
    background: #fff;
    text-align: center;
    color: var(--orch-muted);
    margin-bottom: 14px;
}
.orchestra-v3 .orch-history-empty .oe-title {
    font-family: "Satoshi", sans-serif;
    font-size: 17px;
    font-weight: 700;
    letter-spacing: -0.015em;
    color: var(--orch-ink);
    margin: 0 0 8px;
}
.orchestra-v3 .orch-history-empty .oe-desc {
    font-size: 13.5px;
    margin: 0 0 18px;
    line-height: 1.6;
    max-width: 520px;
    margin-left: auto;
    margin-right: auto;
}
.orchestra-v3 .orch-history-empty .oe-steps {
    display: flex;
    gap: 24px;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 12px;
}
.orchestra-v3 .orch-history-empty .oe-step {
    font-size: 12.5px;
    color: var(--orch-subtle);
    display: flex;
    align-items: center;
    gap: 6px;
}
.orchestra-v3 .orch-history-empty .oe-step b {
    color: var(--orch-ink);
    font-weight: 700;
    font-family: "Satoshi", sans-serif;
    font-size: 13px;
    padding: 2px 8px;
    background: var(--orch-line-soft);
    border-radius: 999px;
}

/* Priority & score pills (ri-stilate per il JS che le inietta nei results) */
.orchestra-v3 .orch-action-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border: 1px solid var(--orch-line);
    border-radius: 8px;
    margin-bottom: 8px;
    background: #fff;
    transition: border-color 0.12s, box-shadow 0.12s;
}
.orchestra-v3 .orch-action-item:hover { border-color: var(--orch-faint); box-shadow: 0 2px 6px rgba(0,0,0,0.04); }
.orchestra-v3 .priority-badge {
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.orchestra-v3 .priority-critica { background: #fef2f2; color: var(--orch-bad); }
.orchestra-v3 .priority-alta { background: #fffbeb; color: var(--orch-warn); }
.orchestra-v3 .priority-media { background: #eff6ff; color: var(--orch-info); }
.orchestra-v3 .priority-bassa { background: var(--orch-line-soft); color: var(--orch-muted); }
.orchestra-v3 .orch-page-result {
    padding: 16px 18px;
    border: 1px solid var(--orch-line);
    border-radius: 8px;
    margin-bottom: 10px;
    background: #fff;
}
.orchestra-v3 .orch-page-result .scores { display: flex; gap: 10px; margin: 8px 0; }
.orchestra-v3 .score-pill {
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}
.orchestra-v3 .score-good { background: #ecfdf5; color: #047857; }
.orchestra-v3 .score-medium { background: #fffbeb; color: #b45309; }
.orchestra-v3 .score-bad { background: #fef2f2; color: var(--orch-bad); }
.orchestra-v3 .orch-action-btn {
    padding: 6px 12px !important;
    font-size: 12px !important;
    background: var(--orch-ink) !important;
    color: #fff !important;
    border: 1px solid var(--orch-ink) !important;
    border-radius: 6px !important;
    font-weight: 500 !important;
    font-family: "General Sans", sans-serif !important;
    height: auto !important;
}
.orchestra-v3 .orch-action-btn:hover { background: #1e293b !important; }
.orchestra-v3 .orch-action-btn.executing { opacity: 0.6; pointer-events: none; }
.orchestra-v3 .orch-action-result {
    margin-top: 8px;
    padding: 12px;
    background: var(--orch-bg);
    border: 1px solid var(--orch-line-soft);
    border-radius: 6px;
    font-size: 12.5px;
    max-height: 200px;
    overflow-y: auto;
    color: var(--orch-muted);
    line-height: 1.5;
}

/* Nasconde dashicons vestigiali che il JS potrebbe iniettare in h3 (style pulito) */
.orchestra-v3 .orchestra-history h3 .dashicons,
.orchestra-v3 .orch3-h2 .dashicons { display: none; }



/* ═══════════════════════════════════════════════════════════════
   3.0.0 — Propose / Review / Apply flow
   ═══════════════════════════════════════════════════════════════ */

/* Review panel — appare sotto ogni .orch-action-item dopo propose */
.orchestra-v3 .review-panel {
    margin-top: 14px;
    padding: 16px;
    background: #fafafa;
    border: 1px dashed #94a3b8;
    border-radius: 8px;
}
.orchestra-v3 .orch-action-item.expanded {
    border-color: #2563eb !important;
    background: #fff !important;
}
.orchestra-v3 .orch-action-item.applied {
    border-color: #0f766e !important;
    background: #ecfdf5 !important;
}
.orchestra-v3 .review-head {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 14px;
    gap: 16px;
    flex-wrap: wrap;
}
.orchestra-v3 .review-title {
    font-family: "Satoshi", sans-serif;
    font-weight: 600;
    font-size: 14px;
    letter-spacing: -0.01em;
    color: #0f172a;
}
.orchestra-v3 .review-proposal-id {
    font-family: ui-monospace, Consolas, monospace;
    font-size: 11px;
    color: #64748b;
    background: #fff;
    padding: 2px 7px;
    border-radius: 4px;
    border: 1px solid #e4e4e7;
}
.orchestra-v3 .review-sub {
    font-size: 12px;
    color: #64748b;
    margin-top: 2px;
}

/* FIX 2: Summary + improvements box */
.orchestra-v3 .rv-summary-box {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 7px;
    padding: 12px 14px;
    margin-bottom: 14px;
}
.orchestra-v3 .rv-summary-text {
    font-size: 13.5px;
    line-height: 1.55;
    color: #1e3a8a;
    font-weight: 500;
    margin-bottom: 8px;
}
.orchestra-v3 .rv-summary-text:last-child { margin-bottom: 0; }
.orchestra-v3 .rv-improvements {
    margin: 6px 0 0;
    padding-left: 20px;
    font-size: 12.5px;
    color: #1e3a8a;
    line-height: 1.6;
}
.orchestra-v3 .rv-improvements li { margin-bottom: 3px; }
.orchestra-v3 .rv-improvements li::marker { color: #2563eb; }

/* Metrics row */
.orchestra-v3 .rv-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 10px;
    margin-bottom: 14px;
}
.orchestra-v3 .rv-metric {
    background: #fff;
    border: 1px solid #e4e4e7;
    border-radius: 7px;
    padding: 10px 12px;
}
.orchestra-v3 .rv-metric-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 4px;
}
.orchestra-v3 .rv-metric-value {
    font-family: "Satoshi", sans-serif;
    font-size: 15px;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: #0f172a;
    display: flex;
    align-items: baseline;
    gap: 6px;
    font-variant-numeric: tabular-nums;
    flex-wrap: wrap;
}
.orchestra-v3 .rv-metric-before { color: #64748b; font-weight: 400; font-size: 13px; }
.orchestra-v3 .rv-metric-arrow  { color: #94a3b8; font-weight: 400; font-size: 12px; }
.orchestra-v3 .rv-metric-delta {
    font-size: 12px;
    font-weight: 500;
    margin-left: auto;
}
.orchestra-v3 .rv-delta-up { color: #0f766e; }
.orchestra-v3 .rv-delta-down { color: #b91c1c; }

/* Diff affiancato */
.orchestra-v3 .rv-diff-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 14px;
}
.orchestra-v3 .rv-diff-col {
    background: #fff;
    border: 1px solid #e4e4e7;
    border-radius: 7px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-height: 140px;
}
.orchestra-v3 .rv-diff-head {
    padding: 8px 12px;
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-weight: 600;
    color: #64748b;
    border-bottom: 1px solid #f1f5f9;
    background: #fafafa;
}
.orchestra-v3 .rv-diff-head.was  { color: #b91c1c; }
.orchestra-v3 .rv-diff-head.will { color: #0f766e; }
.orchestra-v3 .rv-diff-body {
    padding: 12px;
    font-size: 13px;
    line-height: 1.6;
    overflow-wrap: anywhere;
    flex: 1;
    color: #0f172a;
    max-height: 260px;
    overflow-y: auto;
}
.orchestra-v3 .rv-diff-body del {
    background: #fef2f2;
    color: #b91c1c;
    text-decoration: line-through;
    padding: 1px 3px;
    border-radius: 3px;
}
.orchestra-v3 .rv-diff-body ins {
    background: #ecfdf5;
    color: #0f766e;
    text-decoration: none;
    padding: 1px 3px;
    border-radius: 3px;
    font-weight: 500;
}

/* Textarea editabile con proposta */
.orchestra-v3 .rv-edit-label {
    font-size: 11.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.orchestra-v3 .rv-edit-label::before {
    content: "✎";
    color: #2563eb;
    font-size: 12px;
}
.orchestra-v3 .rv-edit-area {
    width: 100%;
    min-height: 100px;
    padding: 10px;
    border: 1px solid #e4e4e7;
    border-radius: 7px;
    font-family: "General Sans", sans-serif;
    font-size: 13px;
    line-height: 1.55;
    resize: vertical;
    margin-bottom: 6px;
    color: #0f172a;
    background: #fff;
}
.orchestra-v3 .rv-edit-area:focus {
    outline: 2px solid #2563eb;
    outline-offset: 1px;
    border-color: #2563eb;
}
.orchestra-v3 .rv-edit-hint {
    font-size: 11.5px;
    color: #64748b;
    margin-bottom: 14px;
}
.orchestra-v3 .rv-edit-block { margin-bottom: 14px; }

/* Footer actions */
.orchestra-v3 .rv-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    padding-top: 12px;
    border-top: 1px solid #e4e4e7;
    flex-wrap: wrap;
}
.orchestra-v3 .rv-actions-meta {
    font-size: 11.5px;
    color: #64748b;
}
.orchestra-v3 .rv-actions-btns { display: flex; gap: 8px; }
.orchestra-v3 .rv-btn {
    padding: 8px 14px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    border: 1px solid transparent;
    font-family: "General Sans", sans-serif;
    letter-spacing: -0.005em;
}
.orchestra-v3 .rv-btn-primary { background: #0f172a; border-color: #0f172a; color: #fff; }
.orchestra-v3 .rv-btn-primary:hover { background: #1e293b; }
.orchestra-v3 .rv-btn-ghost { background: #fff; border-color: #e4e4e7; color: #475569; }
.orchestra-v3 .rv-btn-ghost:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }
.orchestra-v3 .rv-btn-danger { background: #b91c1c; border-color: #b91c1c; color: #fff; }
.orchestra-v3 .rv-btn-danger:hover { background: #991b1b; }
.orchestra-v3 .rv-btn-sm { padding: 5px 10px; font-size: 11.5px; }
.orchestra-v3 .rv-btn:disabled { opacity: 0.55; cursor: not-allowed; }

/* Applied banner */
.orchestra-v3 .rv-applied-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 12px 16px;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    border-radius: 7px;
    margin-top: 10px;
    flex-wrap: wrap;
}
.orchestra-v3 .rv-applied-msg {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #065f46;
    font-size: 13px;
}
.orchestra-v3 .rv-applied-check {
    width: 22px; height: 22px;
    background: #0f766e;
    border-radius: 50%;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
    font-size: 13px;
}

/* Loading spinner inline */
.orchestra-v3 .rv-spinner {
    display: inline-block;
    width: 12px;
    height: 12px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: rv-spin 0.7s linear infinite;
    margin-right: 4px;
    vertical-align: middle;
}
.orchestra-v3 .rv-spinner.dark {
    border-color: #e4e4e7;
    border-top-color: #2563eb;
}
@keyframes rv-spin { to { transform: rotate(360deg); } }

/* Sidebar "Modifiche recenti" */
.orchestra-v3 .orch-mods-card {
    background: #fff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    margin-top: 16px;
    overflow: hidden;
}
.orchestra-v3 .orch-mods-head {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
}
.orchestra-v3 .orch-mods-title {
    font-family: "Satoshi", sans-serif;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: -0.01em;
    color: #0f172a;
    margin: 0 0 2px;
}
.orchestra-v3 .orch-mods-sub {
    font-size: 11.5px;
    color: #64748b;
}
.orchestra-v3 .orch-mods-list { max-height: 420px; overflow-y: auto; }
.orchestra-v3 .orch-mods-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 12.5px;
}
.orchestra-v3 .orch-mods-item:last-child { border-bottom: 0; }
.orchestra-v3 .orch-mods-empty {
    padding: 20px 16px;
    text-align: center;
    color: #94a3b8;
    font-size: 12px;
}
.orchestra-v3 .orch-mods-item-title {
    font-weight: 500;
    color: #0f172a;
    margin-bottom: 2px;
    font-size: 12.5px;
}
.orchestra-v3 .orch-mods-item-meta {
    font-size: 11px;
    color: #64748b;
    margin-bottom: 6px;
}
.orchestra-v3 .orch-mods-item-foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
}
.orchestra-v3 .orch-mods-remaining {
    font-size: 10.5px;
    color: #64748b;
    font-variant-numeric: tabular-nums;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 500;
}
.orchestra-v3 .orch-mods-remaining.warn { color: #d97706; }
.orchestra-v3 .orch-mods-remaining.expired { color: #94a3b8; }

/* 3.4.3 — Card header collapsibile */
.orchestra-v3 .orch-mods-head {
    display: flex;
    align-items: baseline;
    gap: 6px;
    cursor: pointer;
    user-select: none;
    transition: background 0.12s ease;
}
.orchestra-v3 .orch-mods-head:hover { background: #fafbfc; }
.orchestra-v3 .orch-mods-chevron {
    display: inline-block;
    color: #94a3b8;
    font-size: 10px;
    line-height: 1;
    transform: rotate(0deg);
    transition: transform 0.18s ease;
    margin-right: 2px;
}
.orchestra-v3 .orch-mods-card:not(.collapsed) .orch-mods-chevron {
    transform: rotate(90deg);
}
.orchestra-v3 .orch-mods-count {
    font-weight: 500;
    color: #64748b;
    font-size: 12px;
    margin-left: 4px;
}
.orchestra-v3 .orch-mods-card.collapsed .orch-mods-list {
    display: none;
}
.orchestra-v3 .orch-mods-card.collapsed .orch-mods-head {
    border-bottom: 0;
}
.orchestra-v3 .orch-mods-head .orch-mods-sub {
    margin-left: auto;
}

/* 3.4.3 — Bottoni per item (Dettagli + Ripristina) */
.orchestra-v3 .orch-mods-item-actions {
    display: flex;
    gap: 6px;
    align-items: center;
}
.orchestra-v3 .rv-btn.rv-btn-xs {
    font-size: 11px;
    padding: 4px 9px;
    border-radius: 6px;
    line-height: 1.2;
    height: auto;
    min-height: 0;
}

/* 3.4.3 — Modal dettagli snapshot (overlay) */
.orchestra-v3 ~ #rv-details-overlay,
#rv-details-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,0.55);
    backdrop-filter: blur(2px);
    z-index: 100001;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
    font-family: "General Sans", -apple-system, sans-serif;
}
#rv-details-overlay.show { display: flex; }
#rv-details-overlay .rv-modal-wide {
    background: #fff;
    border-radius: 12px;
    max-width: 720px;
    width: 100%;
    max-height: 86vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25);
    overflow: hidden;
}
#rv-details-overlay .rv-details-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    padding: 18px 22px 14px;
    border-bottom: 1px solid #f1f5f9;
}
#rv-details-overlay .rv-details-head h3 {
    margin: 0 0 4px;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: -0.01em;
    font-family: "Satoshi", sans-serif;
    color: #0f172a;
}
#rv-details-overlay .rv-details-meta {
    font-size: 12px;
    color: #64748b;
    line-height: 1.5;
}
#rv-details-overlay .rv-details-close {
    background: none;
    border: 0;
    font-size: 22px;
    color: #94a3b8;
    cursor: pointer;
    padding: 0 4px;
    line-height: 1;
    transition: color 0.12s ease;
}
#rv-details-overlay .rv-details-close:hover { color: #0f172a; }
#rv-details-overlay .rv-details-body {
    flex: 1 1 auto;
    overflow-y: auto;
    padding: 16px 22px 18px;
    background: #fafbfc;
}
#rv-details-overlay .rv-details-loading,
#rv-details-overlay .rv-details-error {
    text-align: center;
    color: #64748b;
    padding: 32px 12px;
    font-size: 13px;
}
#rv-details-overlay .rv-details-error { color: #b91c1c; }
#rv-details-overlay .rv-modal-foot {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 12px 22px 16px;
    border-top: 1px solid #f1f5f9;
    background: #fff;
}

/* 3.4.3 — Field diff blocks */
.orchestra-v3 .rv-field-block,
.rv-field-block {
    background: #fff;
    border: 1px solid #e4e4e7;
    border-radius: 8px;
    margin-bottom: 12px;
    overflow: hidden;
}
.rv-field-label {
    padding: 8px 12px;
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
    font-size: 11.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #475569;
    font-family: "Satoshi", sans-serif;
}
.rv-field-row {
    display: flex;
    align-items: stretch;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13px;
    line-height: 1.55;
}
.rv-field-row:last-child { border-bottom: 0; }
.rv-field-row .rv-field-tag {
    flex: 0 0 70px;
    padding: 10px 12px;
    font-size: 10.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #94a3b8;
    background: #fafbfc;
    border-right: 1px solid #f1f5f9;
    font-family: "Satoshi", sans-serif;
}
.rv-field-row.rv-field-before .rv-field-tag { color: #b45309; }
.rv-field-row.rv-field-after .rv-field-tag  { color: #047857; }
.rv-field-row .rv-field-val {
    flex: 1 1 auto;
    padding: 10px 12px;
    color: #0f172a;
    word-break: break-word;
    white-space: pre-wrap;
}
.rv-field-row .rv-field-val.rv-empty {
    color: #94a3b8;
    font-style: italic;
}
.rv-diff-del {
    background: #fee2e2;
    color: #991b1b;
    text-decoration: line-through;
    padding: 0 2px;
    border-radius: 2px;
}
.rv-diff-add {
    background: #d1fae5;
    color: #065f46;
    padding: 0 2px;
    border-radius: 2px;
}

/* 3.4.3 — Bottone "Rivedi modifica" sul banner applied */
.rv-applied-banner .rv-applied-actions {
    display: flex;
    gap: 6px;
    align-items: center;
    flex-wrap: wrap;
}

/* 3.7.0 — Onboarding wizard prima installazione */
.orch-onboarding-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(4px);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    animation: orch-onb-fade 0.25s ease;
}
@keyframes orch-onb-fade {
    from { opacity: 0; }
    to { opacity: 1; }
}
.orch-onboarding-modal {
    background: #fff;
    border-radius: 16px;
    max-width: 580px;
    width: 100%;
    padding: 32px 36px;
    position: relative;
    box-shadow: 0 25px 70px rgba(15, 23, 42, 0.4);
    font-family: "General Sans", -apple-system, sans-serif;
}
.orch-onboarding-close {
    position: absolute;
    top: 14px;
    right: 16px;
    background: none;
    border: 0;
    font-size: 26px;
    color: #94a3b8;
    cursor: pointer;
    padding: 4px 8px;
    line-height: 1;
}
.orch-onboarding-close:hover { color: #0f172a; }
.orch-onboarding-eyebrow {
    text-transform: uppercase;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.08em;
    color: #2563eb;
    margin-bottom: 8px;
}
.orch-onboarding-title {
    font-family: "Satoshi", sans-serif;
    font-size: 26px;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: #0f172a;
    margin: 0 0 8px;
}
.orch-onboarding-sub {
    color: #475569;
    font-size: 14px;
    line-height: 1.55;
    margin: 0 0 22px;
}
.orch-onboarding-steps {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 24px;
}
.orch-onb-step {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    padding: 14px 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    transition: all 0.18s ease;
}
.orch-onb-step.active {
    background: #eff6ff;
    border-color: #93c5fd;
}
.orch-onb-num {
    flex: 0 0 28px;
    height: 28px;
    border-radius: 50%;
    background: #2563eb;
    color: white;
    font-weight: 700;
    font-family: "Satoshi", sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
}
.orch-onb-step:not(.active) .orch-onb-num {
    background: #cbd5e1;
}
.orch-onb-text { flex: 1; }
.orch-onb-text strong {
    display: block;
    font-size: 14px;
    color: #0f172a;
    margin-bottom: 2px;
    font-weight: 600;
}
.orch-onb-text span {
    color: #64748b;
    font-size: 12.5px;
    line-height: 1.5;
}
.orch-onb-status {
    flex: 0 0 24px;
    color: #10b981;
    font-weight: 700;
    font-size: 18px;
    text-align: right;
}
.orch-onboarding-foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding-top: 8px;
    border-top: 1px solid #f1f5f9;
}
.orch-onboarding-foot .orch3-btn-ghost {
    color: #64748b;
    background: transparent;
    border: 0;
    font-size: 13px;
    cursor: pointer;
}
.orch-onboarding-foot .orch3-btn-ghost:hover { color: #0f172a; }

/* 3.4.7 — Warning Elementor (banner giallo) + bottone "Copia testo" */
.orchestra-v3 .rv-warning {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    padding: 10px 14px;
    background: #fffbeb;
    border: 1px solid #fcd34d;
    border-left: 3px solid #d97706;
    border-radius: 6px;
    color: #78350f;
    font-size: 12.5px;
    line-height: 1.5;
    margin: 10px 0 6px;
}
.orchestra-v3 .rv-warning svg {
    flex-shrink: 0;
    color: #d97706;
    margin-top: 1px;
}
.orchestra-v3 .rv-warning strong { color: #78350f; font-weight: 600; }

/* 3.8.0 — Schema validator section in review panel */
.orchestra-v3 .rv-schema-section {
    margin: 14px 0 4px;
    padding: 12px 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}
.orchestra-v3 .rv-schema-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.orchestra-v3 .rv-schema-title {
    font-weight: 600;
    font-size: 13px;
    color: #1f2937;
}
.orchestra-v3 .rv-schema-status {
    font-size: 12px;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.orchestra-v3 .rv-schema-err { color: #b91c1c; }
.orchestra-v3 .rv-schema-pill {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 600;
    line-height: 1.6;
}
.orchestra-v3 .rv-schema-pill-ok   { background: #dcfce7; color: #166534; }
.orchestra-v3 .rv-schema-pill-warn { background: #fef3c7; color: #92400e; }
.orchestra-v3 .rv-schema-pill-err  { background: #fee2e2; color: #991b1b; }
.orchestra-v3 .rv-schema-pill-info { background: #e2e8f0; color: #475569; }
.orchestra-v3 .rv-schema-list {
    margin-top: 10px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.orchestra-v3 .rv-schema-item {
    padding: 8px 10px;
    border-radius: 6px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-left: 3px solid #94a3b8;
    font-size: 12.5px;
}
.orchestra-v3 .rv-schema-valid    { border-left-color: #16a34a; }
.orchestra-v3 .rv-schema-warning  { border-left-color: #d97706; }
.orchestra-v3 .rv-schema-error    { border-left-color: #dc2626; }
.orchestra-v3 .rv-schema-unknown  { border-left-color: #94a3b8; }
.orchestra-v3 .rv-schema-item-head {
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 6px;
}
.orchestra-v3 .rv-schema-icon {
    display: inline-block;
    width: 16px;
    text-align: center;
    font-weight: 700;
}
.orchestra-v3 .rv-schema-valid .rv-schema-icon   { color: #16a34a; }
.orchestra-v3 .rv-schema-warning .rv-schema-icon { color: #d97706; }
.orchestra-v3 .rv-schema-error .rv-schema-icon   { color: #dc2626; }
.orchestra-v3 .rv-schema-issues {
    margin: 6px 0 0 0;
    padding-left: 22px;
    font-size: 12px;
    color: #475569;
    list-style: disc;
}
.orchestra-v3 .rv-schema-issues code {
    background: #f1f5f9;
    padding: 1px 5px;
    border-radius: 3px;
    font-size: 11.5px;
}
.orchestra-v3 .rv-iss-error   { color: #991b1b; }
.orchestra-v3 .rv-iss-warning { color: #92400e; }
.orchestra-v3 .rv-iss-info    { color: #475569; }
.orchestra-v3 .rv-schema-missing {
    margin-top: 10px;
    padding: 10px 12px;
    background: #fff7ed;
    border: 1px dashed #fdba74;
    border-radius: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.orchestra-v3 .rv-schema-missing-text {
    font-size: 12.5px;
    color: #7c2d12;
    flex: 1 1 auto;
    min-width: 200px;
}
.orchestra-v3 .rv-schema-generate-btn {
    flex-shrink: 0;
}
.orchestra-v3 .rv-schema-generate-btn[disabled] {
    opacity: 0.55;
    cursor: not-allowed;
}

/* 3.8.1 — Impact section after apply */
.orchestra-v3 .rv-schema-impact {
    margin-top: 10px;
    padding: 10px 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}
.orchestra-v3 .rv-schema-impact-head {
    font-weight: 600;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.orchestra-v3 .rv-impact-better  { color: #166534; }
.orchestra-v3 .rv-impact-worse   { color: #991b1b; }
.orchestra-v3 .rv-impact-neutral { color: #475569; }
.orchestra-v3 .rv-schema-impact-stats {
    margin-top: 8px;
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    font-size: 12.5px;
    color: #475569;
}
.orchestra-v3 .rv-impact-stat strong {
    color: #1f2937;
    font-weight: 600;
    margin-left: 4px;
}
.orchestra-v3 .rv-impact-good.rv-impact-stat { color: #166534; }
.orchestra-v3 .rv-impact-bad.rv-impact-stat  { color: #991b1b; }


.orchestra-v3 .rv-copy-text-btn {
    background: #fff;
    border: 1px solid #d1d5db;
    color: #475569;
}
.orchestra-v3 .rv-copy-text-btn:hover {
    border-color: #2563eb;
    color: #2563eb;
}
.orchestra-v3 .rv-copy-text-btn.rv-copied {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #065f46;
}

/* 3.4.5 — Bottone "Svuota" cronologia + count */
.orchestra-v3 .orch5-side-count {
    font-weight: 500;
    color: #64748b;
    font-size: 12px;
    margin-left: 4px;
}
.orchestra-v3 .orch5-side-clear {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: transparent;
    border: 1px solid transparent;
    color: #94a3b8;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 500;
    margin-left: auto;
    transition: all 0.15s ease;
    font-family: inherit;
}
.orchestra-v3 .orch5-side-clear:hover {
    color: #b91c1c;
    background: #fef2f2;
    border-color: #fee2e2;
}
.orchestra-v3 .orch5-side-clear svg { flex-shrink: 0; }
.orchestra-v3 .orch5-side-clear[hidden] { display: none; }
.orchestra-v3 .orch5-side-filters {
    display: flex;
    align-items: center;
}

/* 3.4.5 — Modal svuota cronologia (riusa stile undo) */
.orchestra-v3 ~ #rv-clear-history-overlay,
#rv-clear-history-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,0.55);
    backdrop-filter: blur(2px);
    z-index: 100000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    font-family: "General Sans", -apple-system, sans-serif;
}
#rv-clear-history-overlay.show { display: flex; }
#rv-clear-history-overlay .rv-modal {
    background: #fff;
    border-radius: 12px;
    max-width: 440px;
    width: 100%;
    padding: 22px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25);
}
#rv-clear-history-overlay h3 {
    margin: 0 0 6px;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: -0.01em;
    font-family: "Satoshi", sans-serif;
    color: #0f172a;
}
#rv-clear-history-overlay p {
    color: #475569;
    font-size: 13.5px;
    margin: 0 0 16px;
    line-height: 1.55;
}
#rv-clear-history-overlay .rv-modal-foot {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

/* Undo modal overlay */
.orchestra-v3 ~ #rv-undo-overlay,
#rv-undo-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,0.55);
    backdrop-filter: blur(2px);
    z-index: 100000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    font-family: "General Sans", -apple-system, sans-serif;
}
#rv-undo-overlay.show { display: flex; }
#rv-undo-overlay .rv-modal {
    background: #fff;
    border-radius: 12px;
    max-width: 440px;
    width: 100%;
    padding: 22px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25);
}
#rv-undo-overlay h3 {
    margin: 0 0 6px;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: -0.01em;
    font-family: "Satoshi", sans-serif;
    color: #0f172a;
}
#rv-undo-overlay p {
    color: #475569;
    font-size: 13.5px;
    margin: 0 0 16px;
    line-height: 1.55;
}
#rv-undo-overlay .rv-modal-foot {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

/* Toast */
#rv-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: #0f172a;
    color: #fff;
    padding: 12px 18px;
    border-radius: 8px;
    font-size: 13px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    z-index: 200000;
    transform: translateY(80px);
    opacity: 0;
    transition: all 0.25s;
    max-width: 400px;
    font-family: "General Sans", sans-serif;
}
#rv-toast.show { transform: none; opacity: 1; }

/* Responsive diff */
@media (max-width: 900px) {
    .orchestra-v3 .rv-diff-row { grid-template-columns: 1fr; }
}
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

<script type="text/javascript">
/* Fallback conteggio se admin.js non carica */
jQuery(document).ready(function($) {
    function orchUpdateCount() {
        var count = $('.orch-page-check:checked').length;
        var T = (window.SeoAeoOrchestra && SeoAeoOrchestra.t) ? SeoAeoOrchestra.t : function(s){return s;};
        $('#orch-selected-count').text(T('{N} selezionate').replace('{N}', count));
        var label = count === 0
            ? T('Seleziona almeno una pagina')
            : T('Analizza {N} pagine').replace('{N}', count);
        $('#orch-start-label').text(label);
    }
    orchUpdateCount();
    $(document).on('change', '.orch-page-check', orchUpdateCount);

    /* ─── 2.5.0: Filtri cronologia (Tutte / 7gg / 30gg) ─── */
    function applyHistoryFilter(range) {
        var now = Date.now();
        var maxAge = range === 'all' ? Infinity : parseInt(range, 10) * 86400000;
        $('#history-orchestrator .history-item').each(function() {
            var $item = $(this);
            // cerco un timestamp parsable dentro hi-meta (formato "2026-04-20 11:41:44 — analysis")
            var metaText = $item.find('.hi-meta').text() || '';
            var m = metaText.match(/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/);
            if (!m) return; // se non parseabile, lascio visibile
            var ts = new Date(m[1] + '-' + m[2] + '-' + m[3] + 'T' + m[4] + ':' + m[5] + ':' + m[6]).getTime();
            var age = now - ts;
            $item.toggle(age <= maxAge);
            // Toggle anche l'eventuale .history-detail che segue
            var $detail = $item.next('.history-detail');
            if ($detail.length) $detail.toggle(age <= maxAge);
        });
    }
    $(document).on('click', '.orch5-side-filter', function() {
        var range = $(this).data('filter-range');
        $('.orch5-side-filter').removeClass('active');
        $(this).addClass('active');
        applyHistoryFilter(range);
    });
    // Applica filtro anche dopo refresh history (MutationObserver leggero)
    var histEl = document.getElementById('history-orchestrator');
    if (histEl && window.MutationObserver) {
        new MutationObserver(function() {
            var activeRange = $('.orch5-side-filter.active').data('filter-range') || 'all';
            if (activeRange !== 'all') applyHistoryFilter(activeRange);
        }).observe(histEl, { childList: true, subtree: true });
    }

    /* ─── 2.5.0: Rianalizza pagina critica ─── */
    $(document).on('click', '.orch5-cp-btn', function() {
        var url = $(this).data('cp-url');
        if (!url) return;
        // Deseleziono tutte le checkbox, seleziono solo quella corrispondente
        $('.orch-page-check').prop('checked', false);
        var $match = $('.orch-page-check').filter(function() {
            return $(this).data('url') === url;
        });
        if ($match.length) {
            $match.prop('checked', true).first().trigger('change');
        }
        if (window.SeoAeoOrchestra && window.SeoAeoOrchestra.updateOrchestratorCount) {
            window.SeoAeoOrchestra.updateOrchestratorCount();
        } else {
            orchUpdateCount();
        }
        // Scroll al form
        var setup = document.getElementById('orchestrator-setup');
        if (setup) setup.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    /* ─── 3.31.2: tile "Esegui" — routing intelligente con URL+keyword nel link ─── */
    $(document).on('click', '.orch6-tile-cta', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $btn = $(this);
        var action = ($btn.data('todo-action') || '').toString();
        var pageUrl = ($btn.data('todo-page-url') || '').toString();
        var keyword = ($btn.data('todo-keyword') || '').toString();
        var agent = ($btn.data('todo-agent') || '').toString().toLowerCase();

        // Mapping action → submenu page
        var lower = action.toLowerCase();
        var target = 'seo-aeo-analyze'; // default
        if (agent === 'meta_tags' || /meta\s*tag/.test(lower)) target = 'seo-aeo-meta-tags';
        else if (agent === 'aeo_content' || /aeo|risposta|domanda|faq/.test(lower)) target = 'seo-aeo-aeo-content';
        else if (agent === 'content_generator' || /contenuto|articolo|generat/.test(lower)) target = 'seo-aeo-content';
        else if (/local\s*seo|gmb|google\s*my\s*business/.test(lower)) target = 'seo-aeo-local-seo';
        else if (/keyword\s*research|ricerca\s*keyword/.test(lower)) target = 'seo-aeo-keyword-research';
        else if (/aeo\s*analisi|analisi\s*aeo/.test(lower)) target = 'seo-aeo-aeo-analysis';

        var href = window.location.pathname + '?page=' + target;
        if (pageUrl) href += '&url=' + encodeURIComponent(pageUrl);
        if (keyword) href += '&keyword=' + encodeURIComponent(keyword);
        console.log('[orch] tile cta -> ' + target + ' url=' + pageUrl + ' kw=' + keyword);
        window.location.href = href;
    });

    /* ─── 3.31.2: CTA "Analizza queste N pagine" da yellow alert ─── */
    $(document).on('click', '.orch4-alert-cta', function(e) {
        e.preventDefault();
        var ids = ($(this).data('orch-preselect') || '').toString().split(',').filter(Boolean);
        if (!ids.length) return;
        $('.orch-page-check').prop('checked', false);
        var found = 0;
        $('.orch-page-check').each(function() {
            if (ids.indexOf(String($(this).val())) !== -1) {
                $(this).prop('checked', true);
                found++;
            }
        });
        if (window.SeoAeoOrchestra && window.SeoAeoOrchestra.updateOrchestratorCount) {
            window.SeoAeoOrchestra.updateOrchestratorCount();
        } else if (typeof orchUpdateCount === 'function') {
            orchUpdateCount();
        }
        var setup = document.getElementById('orchestrator-setup');
        if (setup) setup.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // Highlight visivo del form per feedback
        if (setup) {
            setup.style.transition = 'box-shadow 600ms ease';
            setup.style.boxShadow = '0 0 0 4px rgba(0,85,255,0.25)';
            setTimeout(function() { setup.style.boxShadow = ''; }, 1500);
        }
        console.log('[orch] preselect from new-pages CTA: ' + found + '/' + ids.length + ' matched');
    });

    /* ─── 2.5.0: Ripeti analisi identica (bottone in history-item) ─── */
    $(document).on('click', '.orch5-rerun-same', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var urlsJson = $(this).attr('data-urls');
        if (!urlsJson) return;
        var urls;
        try { urls = JSON.parse(urlsJson); } catch (err) { return; }
        if (!Array.isArray(urls) || !urls.length) return;
        // Deseleziono tutto e seleziono solo gli URL specificati
        $('.orch-page-check').prop('checked', false);
        var found = 0;
        $('.orch-page-check').each(function() {
            if (urls.indexOf($(this).data('url')) !== -1) {
                $(this).prop('checked', true);
                found++;
            }
        });
        if (window.SeoAeoOrchestra && window.SeoAeoOrchestra.updateOrchestratorCount) {
            window.SeoAeoOrchestra.updateOrchestratorCount();
        } else {
            orchUpdateCount();
        }
        var setup = document.getElementById('orchestrator-setup');
        if (setup) setup.scrollIntoView({ behavior: 'smooth', block: 'start' });
        if (!found) alert((window.SeoAeoOrchestra && SeoAeoOrchestra.t) ? SeoAeoOrchestra.t('Nessuna delle pagine dell\'analisi passata è più disponibile.') : 'Nessuna delle pagine dell\'analisi passata è più disponibile.');
    });

    /* ─── 2.5.0: Aggiungo bottone "Ripeti" agli item cronologia dopo render ─── */
    function augmentHistoryItems() {
        $('#history-orchestrator .history-item').each(function() {
            var $item = $(this);
            if ($item.find('.orch5-rerun-same').length) return; // già aggiunto
            // Estraggo URL pagine dall'item: il dettaglio espanso contiene links/titles
            // Fallback: uso data-pages se il JS originale lo mette. Altrimenti parsing testo.
            var pagesData = $item.data('pages-urls') || $item.attr('data-pages-urls');
            if (!pagesData) {
                // Cerco nel .history-detail (visibile se espanso) oppure estraggo dal data-raw
                var $detail = $item.next('.history-detail');
                if ($detail.length) {
                    var urls = [];
                    $detail.find('a[href^="http"]').each(function() { urls.push(this.href); });
                    if (urls.length) pagesData = JSON.stringify(urls);
                }
            }
            // Se proprio non ho URL, salto (il bottone "rerun" originale del plugin c'è già)
            if (!pagesData) return;
            var btn = $('<button type="button" class="orch3-btn orch3-btn-ghost orch5-rerun-same" title="Ripeti con le stesse pagine">⟲ Ripeti</button>');
            btn.attr('data-urls', pagesData);
            $item.append(btn);
        });
    }
    // Applica augment quando history cambia
    if (histEl && window.MutationObserver) {
        new MutationObserver(augmentHistoryItems).observe(histEl, { childList: true, subtree: true });
    }
    augmentHistoryItems();

    /* ═════════════════════════════════════════════════════════
       2.6.0 — To-do mark done persistente + load results
       ═════════════════════════════════════════════════════════ */

    /* ─── 2.6.0: mark done persistito in localStorage ─── */
    var TODO_STORAGE_KEY = 'seo_aeo_orch_todo_done_v1';
    function loadDoneIds() {
        try {
            var raw = localStorage.getItem(TODO_STORAGE_KEY);
            if (!raw) return [];
            var parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) { return []; }
    }
    function saveDoneIds(ids) {
        try { localStorage.setItem(TODO_STORAGE_KEY, JSON.stringify(ids)); } catch (e) {}
    }
    function updateTodoCounter() {
        var $list = $('#orch6-todo-list');
        if (!$list.length) return;
        var total = $list.find('.orch6-todo-item').length;
        var done = $list.find('.orch6-todo-item.is-done').length;
        var pending = total - done;
        var $counter = $('#orch6-todo-counter');
        // 3.38.8 Task 2 — show toggle only if at least one done item exists.
        var $toggle = $('#orch6-todo-toggle-done');
        if (done > 0) {
            $toggle.prop('hidden', false);
            var T3 = (window.SeoAeoOrchestra && SeoAeoOrchestra.t) ? SeoAeoOrchestra.t : function(s){return s;};
            var on = $list.hasClass('show-done');
            var labelKey = on ? 'Nascondi completati' : 'Mostra completati';
            $toggle.find('[data-label]').text(T3(labelKey) + ' (' + done + ')');
            $toggle.toggleClass('is-on', on);
        } else {
            $toggle.prop('hidden', true);
        }
        if (pending === 0 && total > 0) {
            var T = (window.SeoAeoOrchestra && SeoAeoOrchestra.t) ? SeoAeoOrchestra.t : function(s){return s;};
            $counter.text(T('✓ Tutto fatto'));
            // Mostra empty state
            if (!$list.find('.orch6-todo-empty').length) {
                $list.append('<div class="orch6-todo-empty"><strong>' + T('Ottimo lavoro!') + '</strong> ' + T('Hai completato tutte le azioni prioritarie.') + '</div>');
            }
        } else {
            var T2 = (window.SeoAeoOrchestra && SeoAeoOrchestra.t) ? SeoAeoOrchestra.t : function(s){return s;};
            $counter.text(T2('{N} da fare').replace('{N}', pending));
            $list.find('.orch6-todo-empty').remove();
        }
    }
    // 3.38.8 Task 2 — toggle Mostra/Nascondi completati, persisted across navigations.
    var TODO_SHOW_DONE_KEY = 'seo_aeo_orch_todo_show_done_v1';
    function loadShowDone() {
        try { return localStorage.getItem(TODO_SHOW_DONE_KEY) === '1'; } catch (e) { return false; }
    }
    function saveShowDone(v) {
        try { localStorage.setItem(TODO_SHOW_DONE_KEY, v ? '1' : '0'); } catch (e) {}
    }
    $(document).on('click', '#orch6-todo-toggle-done', function() {
        var $list = $('#orch6-todo-list');
        var next = !$list.hasClass('show-done');
        $list.toggleClass('show-done', next);
        saveShowDone(next);
        updateTodoCounter();
    });

    // Init stato dei todo al page-load
    (function initTodos() {
        var $list = $('#orch6-todo-list');
        if (!$list.length) return;
        if (loadShowDone()) $list.addClass('show-done');
        var doneIds = loadDoneIds();
        $list.find('.orch6-todo-item').each(function() {
            var $it = $(this);
            var id = $it.data('todo-id');
            if (doneIds.indexOf(String(id)) !== -1) {
                $it.addClass('is-done');
                $it.find('.orch6-todo-done').prop('checked', true);
            }
        });
        updateTodoCounter();
    })();
    // Click checkbox -> toggle done + persist
    $(document).on('change', '.orch6-todo-done', function() {
        var $it = $(this).closest('.orch6-todo-item');
        var id = String($it.data('todo-id') || '');
        if (!id) return;
        var doneIds = loadDoneIds();
        if ($(this).is(':checked')) {
            $it.addClass('is-done');
            if (doneIds.indexOf(id) === -1) doneIds.push(id);
        } else {
            $it.removeClass('is-done');
            doneIds = doneIds.filter(function(x) { return x !== id; });
        }
        saveDoneIds(doneIds);
        updateTodoCounter();
    });
    // Bottone "Mostra altri"
    $(document).on('click', '#orch6-todo-more', function() {
        var $list = $('#orch6-todo-list');
        $list.addClass('is-expanded');
        $(this).addClass('is-expanded');
    });

    /* ─── 2.6.0: click su history-item per caricare nei risultati ─── */
    function loadHistoryIntoResults($item) {
        var $detail = $item.next('.history-detail');
        // Evito di interferire con click sui bottoni interni
        if (!$detail.length) return;
        // Se l'item ha un payload utile, popola la sezione risultati
        // Usiamo la stessa logica del backend: cerco di trovare i dati grezzi
        // Se il JS originale del plugin ha un metodo per questo, usiamolo
        var $results = $('#orchestrator-results');
        if (!$results.length) return;

        // Prova a estrarre JSON dell'item dall'attribute, poi dal testo del detail
        var payload = $item.data('analysis-raw') || $item.attr('data-analysis-raw');
        var data = null;
        if (payload) {
            try { data = JSON.parse(payload); } catch (e) { data = null; }
        }
        if (!data && $detail.length) {
            // Parsing testo minimale dai campi visibili
            var text = $detail.text();
            var avgAeo = (text.match(/AEO Medio[:\s]+(\d+)/i) || [])[1];
            var avgSeo = (text.match(/SEO Medio[:\s]+(\d+)/i) || [])[1];
            var pagesCount = (text.match(/(\d+)\s+pagin/i) || [])[1];
            var issuesCount = (text.match(/(\d+)\s+problem/i) || [])[1];
            var actionsCount = (text.match(/(\d+)\s+azion/i) || [])[1];
            data = {
                avg_seo: avgSeo || '--',
                avg_aeo: avgAeo || '--',
                pages_count: pagesCount || '',
                total_issues: issuesCount || '',
                total_actions: actionsCount || ''
            };
        }
        if (!data) return;

        // Popola i campi visibili nei risultati
        if (data.avg_seo !== undefined) $('#orch-avg-seo').text(data.avg_seo);
        if (data.avg_aeo !== undefined) $('#orch-avg-aeo').text(data.avg_aeo);
        if (data.pages_count !== undefined) $('#orch-pages-analyzed').text(data.pages_count);
        if (data.total_issues !== undefined) $('#orch-seo-issues-count').text(data.total_issues);
        if (data.total_actions !== undefined) $('#orch-total-actions').text(data.total_actions);
        $results.show();
        // Marca item come "caricato"
        $('.history-item.is-loaded').removeClass('is-loaded');
        $item.addClass('is-loaded');
        // Scroll ai risultati
        $results[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    // Click sulla riga history-item (non sui bottoni interni)
    $(document).on('click', '#history-orchestrator .history-item', function(e) {
        // Se il click è su un bottone o link, lascia passare
        if ($(e.target).closest('button, a').length) return;
        var $row = $(e.currentTarget);
        // Tempo brevissimo perché il JS originale potrebbe espandere la row in parallelo
        setTimeout(function() { loadHistoryIntoResults($row); }, 100);
    });

    /* ═════════════════════════════════════════════════════════
       2.6.1 — Fix credit badge + to-do auto-populate from live analysis
       ═════════════════════════════════════════════════════════ */

    /* ─── 2.6.1 FIX 1: Credit badge — ri-inietta struttura ricca ─── */
    // Il JS plugin (loadCreditBalance) sovrascrive innerHTML del badge con
    // "<span class='dashicons dashicons-database'></span><strong>Crediti: N</strong>"
    // dopo ogni AJAX di saldo. Io osservo e ri-applico il template ricco.
    (function() {
        var badge = document.getElementById('seo-aeo-credit-badge');
        if (!badge || !window.MutationObserver) return;
        var COST_PER_PAGE = 5;
        var enriching = false; // evita loop infinito

        function enrichBadge() {
            if (enriching) return;
            var strongEl = badge.querySelector('strong');
            // Se già ricco (contiene .orch4-credit-top), non fare nulla
            if (badge.querySelector('.orch4-credit-top')) return;
            // Estrai il numero dal formato "Crediti: 875"
            var text = strongEl ? strongEl.textContent : badge.textContent;
            var m = text.match(/(\d[\d\.,]*)/);
            if (!m) return;
            var num = m[1];
            var numInt = parseInt(num.replace(/[^\d]/g, ''), 10) || 0;
            var analisi = Math.floor(numInt / COST_PER_PAGE);
            enriching = true;
            badge.innerHTML =
                '<div class="orch4-credit-top">' +
                    '<span class="num">' + num + '</span>' +
                    '<span class="orch4-credit-word">crediti</span>' +
                '</div>' +
                '<div class="orch4-credit-bottom">' +
                    '~' + analisi + ' analisi pagina · ' +
                    '<a href="' + (document.querySelector('[data-settings-url]')?.getAttribute('data-settings-url') || '#') + '">ricarica</a>' +
                '</div>';
            setTimeout(function() { enriching = false; }, 50);
        }

        // Inject iniziale + observer per re-inject ad ogni update
        setTimeout(enrichBadge, 600); // aspetta che loadCreditBalance abbia girato
        setTimeout(enrichBadge, 1500); // second pass di sicurezza

        var creditObs = new MutationObserver(function(mutations) {
            // Se il DOM del badge è stato riscritto (strong tag appare), ri-arricchisco
            if (enriching) return;
            var wasResetToSimple = false;
            for (var i = 0; i < mutations.length; i++) {
                var added = mutations[i].addedNodes;
                for (var j = 0; j < added.length; j++) {
                    if (added[j].nodeName === 'STRONG' || added[j].nodeName === 'SPAN' && added[j].className === 'dashicons dashicons-database') {
                        wasResetToSimple = true;
                    }
                }
            }
            if (wasResetToSimple) enrichBadge();
        });
        creditObs.observe(badge, { childList: true });
    })();

    /* ─── 2.6.1 FIX 2: To-do auto-populate da analisi live ─── */
    // Il plugin popola #orch-action-plan dopo ogni analisi con
    // <div class="orch-action-item"><span class="priority-badge priority-X">X</span>
    //     <strong>title</strong><p>descrizione</p>...
    //     <button class="orch-action-btn">Esegui</button></div>
    // Quando osservo che questo elemento viene popolato, estraggo le azioni,
    // le salvo in localStorage e rigenero la card To-do (o la aggiorno).
    (function() {
        var planEl = document.getElementById('orch-action-plan');
        if (!planEl || !window.MutationObserver) return;
        var TODO_ITEMS_KEY = 'seo_aeo_orch_todo_items_v1';
        var PRIO_RANK = { 'critica': 0, 'alta': 1, 'media': 2, 'bassa': 3 };

        function extractActionsFromPlan() {
            var items = planEl.querySelectorAll('.orch-action-item');
            var out = [];
            items.forEach(function(it) {
                var titleEl = it.querySelector('strong, h4, .action-title');
                var title = titleEl ? titleEl.textContent.trim() : '';
                if (!title) return;
                var prioEl = it.querySelector('.priority-badge');
                var prio = 'media';
                if (prioEl) {
                    var cls = prioEl.className;
                    var pm = cls.match(/priority-(critica|alta|media|bassa)/);
                    if (pm) prio = pm[1];
                    else prio = prioEl.textContent.trim().toLowerCase();
                }
                var descEl = it.querySelector('p, .action-desc');
                var desc = descEl ? descEl.textContent.trim() : '';
                var pageEl = it.querySelector('.action-page, .orch-action-page');
                var page = pageEl ? pageEl.textContent.trim().replace(/^Su:?\s*/i, '') : '';
                // 3.31.2: estraggo URL pagina + keyword dal data-attr del bottone Esegui
                var btn = it.querySelector('.orch-action-btn');
                var pageUrl = '';
                var keyword = '';
                var agent = '';
                if (btn) {
                    agent = btn.getAttribute('data-agent') || '';
                    try {
                        var ad = JSON.parse(btn.getAttribute('data-action-data') || '{}');
                        pageUrl = ad.url || '';
                        keyword = ad.keyword || ad.main_keyword || ad.primary_keyword || '';
                    } catch (e) { /* ignore */ }
                }
                // 3.31.2: skip action items che richiedono keyword ma ne sono privi
                var needsKw = /meta\s*tag/i.test(title) || /keyword/i.test(title) ||
                              /per la keyword/i.test(desc) || /per le keyword/i.test(desc);
                if (needsKw && !keyword) {
                    if (page) keyword = page; // fallback page title come keyword
                    else return;
                }
                // ID deterministico
                var id = (title + '|' + page).split('').reduce(function(h, c) {
                    return ((h << 5) - h + c.charCodeAt(0)) | 0;
                }, 0).toString(16).replace('-', 'n').substring(0, 12);
                out.push({ id: id, title: title, priority: prio, desc: desc, page_title: page, page_url: pageUrl, keyword: keyword, agent: agent });
            });
            return out;
        }

        function saveActions(items) {
            try { localStorage.setItem(TODO_ITEMS_KEY, JSON.stringify(items)); } catch (e) {}
        }
        function loadActions() {
            try {
                var raw = localStorage.getItem(TODO_ITEMS_KEY);
                if (!raw) return [];
                var p = JSON.parse(raw);
                return Array.isArray(p) ? p : [];
            } catch (e) { return []; }
        }

        function renderTodoCard(items) {
            if (!items || !items.length) return;
            // Ordina per priority
            items.sort(function(a, b) {
                var ra = PRIO_RANK[a.priority] != null ? PRIO_RANK[a.priority] : 2;
                var rb = PRIO_RANK[b.priority] != null ? PRIO_RANK[b.priority] : 2;
                return ra - rb;
            });
            var top = items.slice(0, 5);

            // Se la card non esiste nel DOM, la creo dopo la card critical
            var $existing = $('.orch6-todo');
            var doneIds = (function() {
                try {
                    var raw = localStorage.getItem('seo_aeo_orch_todo_done_v1');
                    return raw ? JSON.parse(raw) : [];
                } catch (e) { return []; }
            })();

            var T = (window.SeoAeoOrchestra && SeoAeoOrchestra.t) ? SeoAeoOrchestra.t : function(s){return s;};
            var esc = function(s) { return $('<div>').text(s == null ? '' : s).html(); };

            // 3.31.2: raggruppo per priorità per il tile layout
            var groups = { 'critica': [], 'alta': [], 'media': [], 'bassa': [] };
            top.forEach(function(t) {
                var pkey = groups.hasOwnProperty(t.priority) ? t.priority : 'media';
                groups[pkey].push(t);
            });
            var META = {
                'critica': { icon: '🔴', label: T('critica priorità'), klass: 'crit' },
                'alta':    { icon: '🔴', label: T('alta priorità'),    klass: 'high' },
                'media':   { icon: '🟡', label: T('media priorità'),   klass: 'med' },
                'bassa':   { icon: '🟢', label: T('bassa priorità'),   klass: 'low' }
            };
            // 3.42.0 M4 — tier dot indicator on Prossimi passi cards (consistency
            // with Piano d'Azione Prioritizzato badge shipped in v3.41.7).
            // Mirrors SEO_AEO_Action_Targets agent → tier mapping (PHP) so we
            // stay aligned with the canonical TARGETS map without an AJAX hop.
            var AGENT_TIER = {
                'schema_generator':         'safe',
                'faq_generator':            'safe',
                'internal_links_generator': 'safe',
                'manual_review':            'safe',
                'meta_optimizer':           'caution',
                'keyword_optimizer':        'caution',
                'intro_rewriter':           'caution',
                'authority_generator':      'caution',
                'snippet_optimizer':        'caution',
                'heading_optimizer':        'caution',
                'content_generator':        'danger'
            };
            function agentTierDot(agent) {
                var tier = AGENT_TIER[String(agent || '')];
                if (!tier) return '';
                var color = tier === 'safe' ? '#22c55e' : (tier === 'caution' ? '#eab308' : '#ef4444');
                var label = tier === 'safe' ? 'SAFE' : (tier === 'caution' ? 'CAUTION' : 'DANGER');
                return '<span class="tier-dot tier-' + tier + '" title="Tier: ' + label + '" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' + color + ';margin-right:8px;vertical-align:middle;"> </span>';
            }

            var html = '<div class="orch3-card orch6-todo orch6-todo-tiles" data-has-todos="1">' +
                '<div class="orch3-card-head orch6-todo-head">' +
                    '<div>' +
                        '<h2 class="orch3-h2">' + T('Prossimi passi per il tuo sito') + '</h2>' +
                        '<p class="orch3-muted">' + T('Azioni prioritarie dall\'ultima analisi. Marca come fatto ciò che hai completato.') + '</p>' +
                    '</div>' +
                    '<div class="orch6-todo-counter" id="orch6-todo-counter"></div>' +
                '</div>' +
                '<div class="orch6-todo-list" id="orch6-todo-list">';

            ['critica','alta','media','bassa'].forEach(function(pkey) {
                var grp = groups[pkey];
                if (!grp.length) return;
                var pmeta = META[pkey];
                html += '<div class="orch6-tile-group orch6-tile-group-' + pmeta.klass + '" data-priority="' + pkey + '">' +
                    '<h4 class="orch6-tile-group-head">' +
                        '<span class="orch6-tile-group-icon">' + pmeta.icon + '</span>' +
                        '<span>' + grp.length + ' ' + esc(pmeta.label) + '</span>' +
                    '</h4>' +
                    '<div class="orch6-tile-grid">';
                grp.forEach(function(t) {
                    var isDone = doneIds.indexOf(String(t.id)) !== -1;
                    html += '<div class="orch6-tile orch6-todo-item priority-' + esc(t.priority) + (isDone ? ' is-done' : '') + '" data-todo-id="' + esc(t.id) + '">' +
                        '<label class="orch6-todo-check orch6-tile-check">' +
                            '<input type="checkbox" class="orch6-todo-done"' + (isDone ? ' checked' : '') + ' />' +
                            '<span class="orch6-todo-mark"></span>' +
                        '</label>' +
                        '<div class="orch6-tile-body">' +
                            '<div class="orch6-tile-action">' + agentTierDot(t.agent) + esc(t.title) + '</div>' +
                            (t.page_title ? '<div class="orch6-tile-page" title="' + esc(t.page_title) + '">' + esc(t.page_title) + '</div>' : '') +
                            (t.page_url ? '<a href="' + esc(t.page_url) + '" target="_blank" class="orch6-tile-url">' + esc(t.page_url) + '</a>' : '') +
                        '</div>' +
                        '<button type="button" class="orch6-tile-cta" ' +
                            'data-todo-action="' + esc(t.title) + '" ' +
                            'data-todo-page-url="' + esc(t.page_url || '') + '" ' +
                            'data-todo-keyword="' + esc(t.keyword || '') + '" ' +
                            'data-todo-agent="' + esc(t.agent || '') + '">' +
                            T('Esegui') +
                        '</button>' +
                    '</div>';
                });
                html += '</div></div>';
            });

            html += '</div></div>';

            if ($existing.length) {
                $existing.replaceWith(html);
            } else {
                var $critical = $('.orch5-critical');
                var $setup = $('#orchestrator-setup');
                if ($critical.length) {
                    $critical.after(html);
                } else if ($setup.length) {
                    $setup.before(html);
                } else {
                    $('.orch5-main').prepend(html);
                }
            }
            // Aggiorna counter
            var $list = $('#orch6-todo-list');
            var total = $list.find('.orch6-todo-item').length;
            var done = $list.find('.orch6-todo-item.is-done').length;
            var pending = total - done;
            if (pending === 0 && total > 0) {
                $('#orch6-todo-counter').text(T('✓ Tutto fatto'));
            } else {
                $('#orch6-todo-counter').text(T('{N} da fare').replace('{N}', pending));
            }
        }

        // 1) Al page-load: rendi to-do da localStorage se presente
        var saved = loadActions();
        if (saved.length) {
            renderTodoCard(saved);
        }

        // 2) Observer: quando #orch-action-plan si popola con nuovi action-items
        var planObs = new MutationObserver(function() {
            var actions = extractActionsFromPlan();
            if (actions.length) {
                saveActions(actions);
                renderTodoCard(actions);
            }
        });
        planObs.observe(planEl, { childList: true, subtree: true });
    })();


    /* ═════════════════════════════════════════════════════════
       3.0.0 — Propose / Review / Apply flow
       ═════════════════════════════════════════════════════════ */
    (function() {
        var SUPPORTED_AGENTS = ['meta_tags', 'aeo_content', 'content_generator', 'seo_analysis'];
        var AGENT_LABELS = {
            'meta_tags': 'Meta title e description',
            'aeo_content': 'Contenuto AEO',
            'content_generator': 'Rigenerazione contenuto',
            'seo_analysis': 'Analisi SEO e struttura'
        };

        // Normalizza l'agente ricevuto dal plugin legacy (potrebbe usare altri nomi)
        function mapAgent(a) {
            var m = {
                'seo_analysis': 'seo_analysis',
                'aeo_analysis': 'seo_analysis',    // fallback: usa seo_analysis
                'meta_tags': 'meta_tags',
                'aeo_content': 'aeo_content',
                'content_generator': 'content_generator'
            };
            return m[a] || a;
        }

        // ═══ Intercetta click sui bottoni azione ═══
        document.addEventListener('click', function(e) {
            var btn = e.target.closest && e.target.closest('.orch-action-btn');
            if (!btn) return;
            if (btn.disabled || btn.classList.contains('executing')) return;
            // 3.40.3 — .orch-preview-btn falls through to admin.js previewAction
            // (modal flow, v3.39.6+). This legacy capture-phase listener only
            // owns Esegui-style buttons now.
            if (btn.classList.contains('orch-preview-btn')) return;
            var rawAgent = btn.getAttribute('data-agent') || '';
            var agent = mapAgent(rawAgent);
            if (SUPPORTED_AGENTS.indexOf(agent) === -1) {
                // Agente non supportato dal nuovo flow: lascia passare alla logica legacy
                return;
            }
            // Blocca il click legacy e apri flow propose
            e.preventDefault();
            e.stopImmediatePropagation();
            // 3.40.3 — diagnostic trace for the legacy capture-phase flow.
            try { console.log('[PROPOSE] click', {agent: agent, rawAgent: rawAgent, dataActionData: btn.getAttribute('data-action-data')}); } catch(_) {}
            startPropose(btn, agent);
        }, true); // capture phase

        // ═══ Start propose: chiama ajax_propose_action ═══
        function startPropose(btn, agent) {
            var $item = $(btn).closest('.orch-action-item');
            var $row  = $(btn).closest('.action-row, .orch-action-row, div');
            var originalHtml = btn.innerHTML;

            // Spinner
            btn.disabled = true;
            btn.innerHTML = '<span class="rv-spinner dark"></span> Sto generando…';

            // Parse data-action-data
            var actionData = {};
            try { actionData = JSON.parse(btn.getAttribute('data-action-data') || '{}'); } catch(e) {}

            var url = actionData.url || window.location.origin + '/';
            var keyword = actionData.keyword || '';
            var postId = parseInt(btn.getAttribute('data-post-id') || actionData.post_id || '0', 10);

            // Se post_id manca, tentiamo di estrarlo dall'URL
            if (!postId && url) {
                // Non possiamo risolverlo senza chiamata aggiuntiva, uso 0
                postId = 0;
            }

            // Se non abbiamo post_id valido, prima cerchiamo con get_pages
            var proposeCall = function(pid) {
                // 3.40.3 — explicit 60s timeout + comprehensive diagnostic trace.
                // Without this an SSE-stalled backend leaves the button in
                // "Sto generando…" forever.
                try { console.log('[PROPOSE] ajax sent', {agent: agent, post_id: pid, url: url, keywords: keyword}); } catch(_) {}
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    timeout: 60000,
                    data: {
                        action: 'seo_aeo_orchestra_propose',
                        nonce: seoAeoOrchestra.nonce,
                        agent: agent,
                        post_id: pid,
                        url: url,
                        keywords: keyword
                    }
                }).done(function(resp) {
                    try { console.log('[PROPOSE] ajax done', resp ? Object.keys(resp) : 'null'); } catch(_) {}
                    if (resp && resp.error) {
                        restoreBtn(btn, originalHtml);
                        toast('Errore: ' + resp.error);
                        return;
                    }
                    if (resp && typeof resp === 'object') resp.post_id = pid;
                    try {
                        try { console.log('[PROPOSE] modal about to render', {proposalId: resp && resp.proposal_id, hasProposedState: !!(resp && resp.proposed_state)}); } catch(_) {}
                        showReview($item, btn, resp, originalHtml);
                        try { console.log('[PROPOSE] modal rendered', true); } catch(_) {}
                    } catch (renderErr) {
                        // 3.40.3 — never let a render exception leave the button stuck.
                        try { console.error('[PROPOSE] error caught (showReview threw)', renderErr); } catch(_) {}
                        restoreBtn(btn, originalHtml);
                        toast('Errore rendering proposta: ' + (renderErr && renderErr.message ? renderErr.message : 'sconosciuto'));
                    }
                }).fail(function(xhr, status) {
                    restoreBtn(btn, originalHtml);
                    if (status === 'timeout') {
                        toast('Proposta impiega troppo tempo (>60s). Riprova tra qualche secondo.');
                    } else {
                        toast('Errore rete (' + (xhr ? xhr.status : '?') + ')');
                    }
                });
            };

            if (postId > 0) {
                proposeCall(postId);
            } else {
                // Cerco il post_id via get_pages matchando URL
                $.post(ajaxurl, {
                    action: 'seo_aeo_orchestra_get_pages',
                    nonce: seoAeoOrchestra.nonce
                }).done(function(pages) {
                    var list = Array.isArray(pages) ? pages : (pages.pages || []);
                    var match = list.find(function(p) { return p.url && (p.url === url || url.indexOf(p.url) === 0); });
                    if (match && match.id) {
                        proposeCall(match.id);
                    } else {
                        // Fallback: usa il primo id disponibile (homepage tipicamente)
                        if (list.length > 0) proposeCall(list[0].id);
                        else {
                            restoreBtn(btn, originalHtml);
                            toast('Impossibile determinare il post_id');
                        }
                    }
                }).fail(function() {
                    restoreBtn(btn, originalHtml);
                    toast('Errore risoluzione post_id');
                });
            }
        }

        function restoreBtn(btn, html) {
            btn.disabled = false;
            btn.innerHTML = html;
        }

        // ═══ Word-level diff (LCS) — v3.8.2 ═══
        // Tokenizza su whitespace + tag HTML come token separati,
        // calcola LCS, ritorna stringhe HTML per before/after side-by-side
        // dove le parti uguali restano in nero e solo le differenze sono colorate.
        function tokenizeForDiff(s) {
            if (!s) return [];
            // Split mantenendo whitespace e tag come token; filtra stringhe vuote
            var parts = s.split(/(\s+|<[^>]+>)/);
            var out = [];
            for (var i = 0; i < parts.length; i++) {
                if (parts[i] !== undefined && parts[i] !== '') out.push(parts[i]);
            }
            return out;
        }

        function buildSideBySideDiff(before, after) {
            var a = tokenizeForDiff(before);
            var b = tokenizeForDiff(after);
            var m = a.length, n = b.length;

            // Performance guard: oltre 5M celle → fallback simple per non bloccare il browser
            // (a 5M celle siamo ~250-300ms su laptop medio, oltre diventa fastidioso)
            if (m * n > 5000000) {
                return {
                    before: '<del>' + escapeHtml(before) + '</del>',
                    after:  '<ins>' + escapeHtml(after) + '</ins>'
                };
            }

            // LCS DP
            var dp = new Array(m + 1);
            for (var i = 0; i <= m; i++) dp[i] = new Int32Array(n + 1);
            for (var i = 1; i <= m; i++) {
                var ai = a[i - 1];
                var dpi = dp[i], dpim1 = dp[i - 1];
                for (var j = 1; j <= n; j++) {
                    if (ai === b[j - 1]) dpi[j] = dpim1[j - 1] + 1;
                    else dpi[j] = (dpim1[j] >= dpi[j - 1]) ? dpim1[j] : dpi[j - 1];
                }
            }

            // Backtrack costruendo direttamente le due stringhe HTML
            var beforeHtml = '', afterHtml = '';
            var beforeBuf = [], afterBuf = [];
            // Costruisco in reverse, poi join.reverse non funziona con HTML tag,
            // quindi accumulo in array e poi reverse + join.
            var i2 = m, j2 = n;
            while (i2 > 0 && j2 > 0) {
                if (a[i2 - 1] === b[j2 - 1]) {
                    var esc = escapeHtml(a[i2 - 1]);
                    beforeBuf.push(esc);
                    afterBuf.push(esc);
                    i2--; j2--;
                } else if (dp[i2 - 1][j2] >= dp[i2][j2 - 1]) {
                    beforeBuf.push('<del>' + escapeHtml(a[i2 - 1]) + '</del>');
                    i2--;
                } else {
                    afterBuf.push('<ins>' + escapeHtml(b[j2 - 1]) + '</ins>');
                    j2--;
                }
            }
            while (i2 > 0) { beforeBuf.push('<del>' + escapeHtml(a[--i2]) + '</del>'); }
            while (j2 > 0) { afterBuf.push('<ins>' + escapeHtml(b[--j2]) + '</ins>'); }

            beforeBuf.reverse();
            afterBuf.reverse();
            return {
                before: beforeBuf.join(''),
                after:  afterBuf.join('')
            };
        }

        // ═══ Show review panel ═══
        function showReview($item, originalBtn, data, originalBtnHtml) {
            // Hide bottone originale
            $(originalBtn).hide();
            $item.addClass('expanded');

            var proposalId = data.proposal_id || 'p_unknown';
            var metrics = data.metrics || [];
            var proposed = data.proposed_state || {};
            var diffHtml = data.diff_html || {};
            var creditsCost = data.credits_cost || 0;

            // Metrics HTML
            var metricsHtml = metrics.map(function(m) {
                var dirClass = 'rv-delta-up';
                if (m.direction === 'down') dirClass = 'rv-delta-down';
                return '<div class="rv-metric">' +
                       '<div class="rv-metric-label">' + escapeHtml(m.label) + '</div>' +
                       '<div class="rv-metric-value">' +
                       '<span class="rv-metric-before">' + escapeHtml(String(m.before)) + '</span>' +
                       '<span class="rv-metric-arrow">→</span>' +
                       escapeHtml(String(m.after)) +
                       '<span class="rv-metric-delta ' + dirClass + '">' + escapeHtml(m.delta) + '</span>' +
                       '</div></div>';
            }).join('');

            // Diff HTML (semplice: mostro before / after testuale)
            var diffRows = '';
            var fieldsToShow = ['meta_title', 'meta_description', 'post_content'];
            var labelMap = {
                'meta_title': 'Meta title',
                'meta_description': 'Meta description',
                'post_content': 'Contenuto pagina'
            };
            fieldsToShow.forEach(function(field) {
                if (proposed[field] !== undefined && proposed[field] !== null) {
                    var beforeRaw = String(getCurrentValueFromData(data, field) || '');
                    var afterRaw  = String(proposed[field] || '');
                    if (beforeRaw === afterRaw) return;

                    // 3.8.2 — word-level diff: porzioni identiche restano in nero,
                    // solo le parti realmente modificate sono evidenziate.
                    var pair = buildSideBySideDiff(beforeRaw, afterRaw);

                    diffRows += '<div class="rv-diff-row">' +
                                '<div class="rv-diff-col">' +
                                '<div class="rv-diff-head was">' + labelMap[field] + ' — attuale</div>' +
                                '<div class="rv-diff-body">' + pair.before + '</div>' +
                                '</div>' +
                                '<div class="rv-diff-col">' +
                                '<div class="rv-diff-head will">' + labelMap[field] + ' — proposto</div>' +
                                '<div class="rv-diff-body">' + pair.after + '</div>' +
                                '</div>' +
                                '</div>';
                }
            });

            // Keywords se presenti
            if (Array.isArray(proposed.meta_keywords) && proposed.meta_keywords.length) {
                diffRows += '<div class="rv-diff-row"><div class="rv-diff-col" style="grid-column: 1 / -1;">' +
                            '<div class="rv-diff-head will">Keywords proposte</div>' +
                            '<div class="rv-diff-body"><ins>' + proposed.meta_keywords.map(escapeHtml).join(', ') + '</ins></div>' +
                            '</div></div>';
            }

            // Edit textarea per ogni campo proposto
            var editHtml = '';
            if (proposed.meta_title) {
                editHtml += '<div class="rv-edit-block">' +
                            '<div class="rv-edit-label">Meta title (modificabile)</div>' +
                            '<textarea class="rv-edit-area" data-field="meta_title" style="min-height:50px;">' +
                            escapeHtml(proposed.meta_title) + '</textarea></div>';
            }
            if (proposed.meta_description) {
                editHtml += '<div class="rv-edit-block">' +
                            '<div class="rv-edit-label">Meta description (modificabile)</div>' +
                            '<textarea class="rv-edit-area" data-field="meta_description" style="min-height:70px;">' +
                            escapeHtml(proposed.meta_description) + '</textarea></div>';
            }
            if (proposed.post_content) {
                editHtml += '<div class="rv-edit-block">' +
                            '<div class="rv-edit-label">Contenuto (modificabile)</div>' +
                            '<textarea class="rv-edit-area" data-field="post_content" style="min-height:120px;">' +
                            escapeHtml(proposed.post_content) + '</textarea></div>';
            }
            if (Array.isArray(proposed.meta_keywords) && proposed.meta_keywords.length) {
                editHtml += '<div class="rv-edit-block">' +
                            '<div class="rv-edit-label">Keywords (modificabili, separate da virgola)</div>' +
                            '<textarea class="rv-edit-area" data-field="meta_keywords" style="min-height:50px;">' +
                            escapeHtml(proposed.meta_keywords.join(', ')) + '</textarea></div>';
            }
            if (editHtml) {
                editHtml = '<div class="rv-edit-hint">ℹ Puoi correggere i campi qui sotto prima di applicare. Verrà salvata la tua versione.</div>' + editHtml;
            }

            // 3.4.7 — Warning Elementor: pagine Elementor non propagano post_content modificato
            var isElementor = !!(data && data.is_elementor);
            var agentName = (data && data.agent) ? String(data.agent) : '';
            var showElementorWarning = isElementor && agentName !== 'meta_tags';
            var elementorWarningHtml = showElementorWarning ?
                '<div class="rv-warning rv-warning-elementor">' +
                  '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>' +
                  '<div><strong>Pagina Elementor rilevata.</strong> Le modifiche al contenuto verranno salvate ma <strong>NON saranno visibili sul sito pubblico</strong> finché non aggiorni la pagina con Elementor stesso. I meta tag (Yoast) funzionano normalmente.</div>' +
                '</div>' : '';
            var canCopyText = !!(proposed && proposed.post_content);
            var copyBtnHtml = canCopyText ?
                '<button type="button" class="rv-btn rv-btn-ghost rv-copy-text-btn" data-proposal-id="' + escapeHtml(proposalId) + '" title="Copia il contenuto proposto negli appunti">' +
                  '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align:-2px;margin-right:5px;"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>' +
                  'Copia testo' +
                '</button>'
                : '';

            // Panel HTML
            var panel = $('<div class="review-panel"></div>');
            panel.html(
                '<div class="review-head">' +
                  '<div>' +
                    '<div class="review-title">Proposta di modifica</div>' +
                    '<div class="review-sub">Costo: ' + creditsCost + ' crediti. Esamina e correggi prima di applicare.</div>' +
                  '</div>' +
                  '<span class="review-proposal-id">' + escapeHtml(proposalId) + '</span>' +
                '</div>' +
                elementorWarningHtml +
                ((data.summary || (data.improvements && data.improvements.length) || (proposed.post_content && !data.summary)) ?
                    '<div class="rv-summary-box">' +
                    (data.summary ? '<div class="rv-summary-text">' + escapeHtml(data.summary) + '</div>'
                      : (proposed.post_content ? '<div class="rv-summary-text">Contenuto riscritto dall\'AI. Esamina la proposta qui sotto e, se ti sembra valida, applicala.</div>' : '')) +
                    (data.improvements && data.improvements.length ?
                        '<ul class="rv-improvements">' +
                        data.improvements.map(function(imp) {
                            return '<li>' + escapeHtml(String(imp)) + '</li>';
                        }).join('') +
                        '</ul>' : '') +
                    '</div>' : '') +
                (metricsHtml ? '<div class="rv-metrics">' + metricsHtml + '</div>' : '') +
                diffRows +
                editHtml +
                '<div class="rv-actions">' +
                  '<span class="rv-actions-meta">⏱ Scade tra 24 ore se non applicata</span>' +
                  '<div class="rv-actions-btns">' +
                    copyBtnHtml +
                    '<button type="button" class="rv-btn rv-btn-ghost rv-discard-btn" data-proposal-id="' + escapeHtml(proposalId) + '">Scarta proposta</button>' +
                    '<button type="button" class="rv-btn rv-btn-primary rv-apply-btn" data-proposal-id="' + escapeHtml(proposalId) + '">Applica modifiche</button>' +
                  '</div>' +
                '</div>'
            );

            // Memo del contenuto proposto sul panel per il bottone "Copia testo"
            if (canCopyText) panel.data('proposed-text', String(proposed.post_content));

            // Salva reference originale per ripristino in caso di discard
            panel.data('original-btn', originalBtn);
            panel.data('original-btn-html', originalBtnHtml);

            $item.append(panel);

            // 3.8.0 — Schema validator: lazy-load async dopo render
            var postIdForSchema = parseInt(data.post_id || $item.data('post-id') || 0, 10);
            if (postIdForSchema > 0) {
                loadSchemaSection(panel, postIdForSchema);
            }
        }

        // ═══ Schema validator (v3.8.0) ═══
        function loadSchemaSection(panel, postId) {
            var $section = $('<div class="rv-schema-section"></div>');
            $section.html(
                '<div class="rv-schema-head">' +
                  '<div class="rv-schema-title">' + SeoAeoOrchestra.t('Schema markup (Rich Results)') + '</div>' +
                  '<div class="rv-schema-status"><span class="rv-spinner"></span> ' + SeoAeoOrchestra.t('Analisi in corso…') + '</div>' +
                '</div>'
            );
            panel.find('.rv-actions').before($section);
            panel.data('schema-post-id', postId);

            $.post(ajaxurl, {
                action: 'seo_aeo_orchestra_validate_schema',
                nonce: seoAeoOrchestra.nonce,
                post_id: postId
            }).done(function(resp) {
                if (!resp || resp.error) {
                    var schemaErr = resp && (resp.message || (typeof resp.error === 'string' ? resp.error : '')) || resp.detail || SeoAeoOrchestra.t('Errore sconosciuto');
                    $section.html(renderSchemaError(schemaErr));
                    return;
                }
                panel.data('schema-before', resp);
                $section.html(renderSchemaResults(resp));
            }).fail(function(xhr) {
                $section.html(renderSchemaError(SeoAeoOrchestra.t('Rete') + ' (' + xhr.status + ')'));
            });
        }

        // Aggregate counts: { ok, warn, err, info, total }
        function schemaCounts(resp) {
            var found = (resp && Array.isArray(resp.schemas_found)) ? resp.schemas_found : [];
            return {
                ok:    found.filter(function(s){ return s.status === 'valid'; }).length,
                warn:  found.filter(function(s){ return s.status === 'warning'; }).length,
                err:   found.filter(function(s){ return s.status === 'error'; }).length,
                info:  found.filter(function(s){ return s.status === 'unknown'; }).length,
                total: found.length,
                blocks: parseInt((resp && resp.total_blocks) || 0, 10)
            };
        }

        function renderSchemaError(msg) {
            return '<div class="rv-schema-head">' +
                     '<div class="rv-schema-title">' + SeoAeoOrchestra.t('Schema markup') + '</div>' +
                     '<div class="rv-schema-status rv-schema-err">' + escapeHtml(String(msg)) + '</div>' +
                   '</div>';
        }

        function renderSchemaResults(resp) {
            var found = Array.isArray(resp.schemas_found) ? resp.schemas_found : [];
            var missing = Array.isArray(resp.missing_recommended_types) ? resp.missing_recommended_types : [];
            var c = schemaCounts(resp);

            var summary;
            if (c.blocks === 0) {
                summary = '<span class="rv-schema-pill rv-schema-pill-err">' + SeoAeoOrchestra.t('Nessun JSON-LD rilevato') + '</span>';
            } else {
                summary = '<span class="rv-schema-pill rv-schema-pill-ok">' + c.ok + ' OK</span>' +
                          (c.warn ? ' <span class="rv-schema-pill rv-schema-pill-warn">' + c.warn + ' ' + SeoAeoOrchestra.t('warning') + '</span>' : '') +
                          (c.err ? ' <span class="rv-schema-pill rv-schema-pill-err">' + c.err + ' ' + SeoAeoOrchestra.t('errori') + '</span>' : '') +
                          (c.info ? ' <span class="rv-schema-pill rv-schema-pill-info">' + c.info + ' ' + SeoAeoOrchestra.t('non validati') + '</span>' : '');
            }

            var html = '<div class="rv-schema-head">' +
                         '<div class="rv-schema-title">' + SeoAeoOrchestra.t('Schema markup (Rich Results)') + '</div>' +
                         '<div class="rv-schema-status">' + summary + '</div>' +
                       '</div>';

            if (found.length) {
                html += '<div class="rv-schema-list">';
                found.forEach(function(s) {
                    var cls = 'rv-schema-item rv-schema-' + (s.status || 'unknown');
                    var icon = s.status === 'valid' ? '✓' :
                               s.status === 'warning' ? '⚠' :
                               s.status === 'error' ? '✗' : 'ℹ';
                    var label = escapeHtml(s.label || s.type || 'Schema');
                    var issuesHtml = '';
                    if (Array.isArray(s.issues) && s.issues.length) {
                        issuesHtml = '<ul class="rv-schema-issues">';
                        s.issues.forEach(function(iss) {
                            var sevCls = 'rv-iss-' + (iss.severity || 'info');
                            var fieldTxt = iss.field ? '<code>' + escapeHtml(iss.field) + '</code>: ' : '';
                            issuesHtml += '<li class="' + sevCls + '">' + fieldTxt + escapeHtml(iss.message || '') + '</li>';
                        });
                        issuesHtml += '</ul>';
                    }
                    html += '<div class="' + cls + '">' +
                              '<div class="rv-schema-item-head"><span class="rv-schema-icon">' + icon + '</span> ' + label + '</div>' +
                              issuesHtml +
                            '</div>';
                });
                html += '</div>';
            }

            if (missing.length) {
                html += '<div class="rv-schema-missing">' +
                          '<div class="rv-schema-missing-text">' +
                            '<strong>' + SeoAeoOrchestra.t('Schema consigliati mancanti:') + '</strong> ' + missing.map(escapeHtml).join(', ') +
                          '</div>' +
                          '<button type="button" class="rv-btn rv-btn-ghost rv-schema-generate-btn" disabled title="' + SeoAeoOrchestra.t('Disponibile nella prossima sessione') + '">' + SeoAeoOrchestra.t('Genera schema mancante (presto)') + '</button>' +
                        '</div>';
            }

            return html;
        }

        // 3.8.1 — Re-validate schema dopo apply per mostrare impatto
        function schedulePostApplySchemaCheck($panel, postId, before) {
            var $impact = $('<div class="rv-schema-impact"></div>');
            $impact.html(
                '<div class="rv-schema-impact-head">' +
                  '<span class="rv-spinner"></span> ' + SeoAeoOrchestra.t('Verifica schema dopo le modifiche…') +
                '</div>'
            );
            $panel.append($impact);

            // Ritardo 2.5s per dare tempo a WP/cache plugin di rigenerare la pagina
            setTimeout(function() {
                $.post(ajaxurl, {
                    action: 'seo_aeo_orchestra_validate_schema',
                    nonce: seoAeoOrchestra.nonce,
                    post_id: postId,
                    nocache: 1
                }).done(function(resp) {
                    if (!resp || resp.error) {
                        $impact.html(
                            '<div class="rv-schema-impact-head rv-schema-err">' +
                              SeoAeoOrchestra.t('Verifica schema fallita:') + ' ' + escapeHtml(String((resp && (resp.message || (typeof resp.error === 'string' ? resp.error : '') || resp.detail)) || SeoAeoOrchestra.t('errore sconosciuto'))) +
                            '</div>'
                        );
                        return;
                    }
                    $impact.html(renderSchemaImpact(before, resp));
                }).fail(function(xhr) {
                    $impact.html(
                        '<div class="rv-schema-impact-head rv-schema-err">' +
                          SeoAeoOrchestra.t('Verifica schema fallita:') + ' ' + SeoAeoOrchestra.t('rete') + ' (' + xhr.status + ')' +
                        '</div>'
                    );
                });
            }, 2500);
        }

        function renderSchemaImpact(before, after) {
            var b = schemaCounts(before);
            var a = schemaCounts(after);
            var changed = (b.ok !== a.ok) || (b.warn !== a.warn) || (b.err !== a.err) || (b.info !== a.info) || (b.blocks !== a.blocks);

            // Verdetto
            var verdict, verdictCls;
            if (!changed) {
                verdict = SeoAeoOrchestra.t('Nessuna variazione (Orchestra non ha modificato lo schema markup di questa pagina)');
                verdictCls = 'rv-impact-neutral';
            } else if (a.err < b.err || (a.err === b.err && a.warn < b.warn)) {
                verdict = '✓ ' + SeoAeoOrchestra.t('Migliorato');
                verdictCls = 'rv-impact-better';
            } else if (a.err > b.err || (a.err === b.err && a.warn > b.warn)) {
                verdict = '✗ ' + SeoAeoOrchestra.t('Peggiorato');
                verdictCls = 'rv-impact-worse';
            } else {
                verdict = SeoAeoOrchestra.t('Variazione neutra');
                verdictCls = 'rv-impact-neutral';
            }

            // goodDir: 'up' means more is better (OK), 'down' means less is better (warn/err)
            function row(label, before, after, goodDir) {
                if (before === after) {
                    return '<span class="rv-impact-stat">' + label + ' <strong>' + before + '</strong></span>';
                }
                var dir = after > before ? 'up' : 'down';
                var good = (dir === goodDir);
                var cls = good ? 'rv-impact-good' : 'rv-impact-bad';
                var arrow = dir === 'up' ? '↑' : '↓';
                return '<span class="rv-impact-stat ' + cls + '">' +
                       label + ' <strong>' + before + ' ' + arrow + ' ' + after + '</strong></span>';
            }

            return '<div class="rv-schema-impact-head ' + verdictCls + '">' + SeoAeoOrchestra.t('Impatto sullo schema:') + ' ' + verdict + '</div>' +
                   '<div class="rv-schema-impact-stats">' +
                     row('OK', b.ok, a.ok, 'up') +
                     row(SeoAeoOrchestra.t('Warning'), b.warn, a.warn, 'down') +
                     row(SeoAeoOrchestra.t('Errori'), b.err, a.err, 'down') +
                     (b.info || a.info ? row(SeoAeoOrchestra.t('Non validati'), b.info, a.info, 'down') : '') +
                   '</div>';
        }

        // Estrae il valore attuale di un campo dalla response del backend (FIX 3)
        // La response propose include `current_state` che il backend compila
        // leggendo lo stato attuale da WordPress prima di generare la proposta.
        function getCurrentValueFromData(data, field) {
            if (!data || !data.current_state) return '';
            var v = data.current_state[field];
            if (v === null || v === undefined) return '';
            if (Array.isArray(v)) return v.join(', ');
            return String(v);
        }
        function getCurrentValue(field, $item) {
            // Legacy: ritorna stringa vuota se chiamata senza data
            return '';
        }

        // ═══ Apply ═══
        $(document).on('click', '.rv-apply-btn', function() {
            var $btn = $(this);
            var $panel = $btn.closest('.review-panel');
            var $item = $btn.closest('.orch-action-item');
            var proposalId = $btn.attr('data-proposal-id');

            // Raccolgo override dai textarea modificati
            var override = {};
            $panel.find('.rv-edit-area').each(function() {
                var field = $(this).attr('data-field');
                var val = $(this).val();
                if (field && val !== undefined) {
                    if (field === 'post_content') override.override_post_content = val;
                    else if (field === 'meta_title') override.override_meta_title = val;
                    else if (field === 'meta_description') override.override_meta_description = val;
                    else if (field === 'meta_keywords') override.override_meta_keywords = val;
                }
            });

            var originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<span class="rv-spinner"></span> ' + SeoAeoOrchestra.t('Applico…'));
            $panel.find('.rv-discard-btn').prop('disabled', true);

            var payload = $.extend({
                action: 'seo_aeo_orchestra_apply_proposal',
                nonce: seoAeoOrchestra.nonce,
                proposal_id: proposalId
            }, override);

            // 3.8.1 — capture schema-before BEFORE replacing the panel
            var schemaBefore = $panel.data('schema-before');
            var schemaPostId = $panel.data('schema-post-id');

            $.post(ajaxurl, payload).done(function(resp) {
                if (resp && resp.error) {
                    $btn.prop('disabled', false).html(originalHtml);
                    $panel.find('.rv-discard-btn').prop('disabled', false);
                    toast('Errore: ' + resp.error);
                    return;
                }
                // Success: replace panel con banner applicato
                $item.removeClass('expanded').addClass('applied');
                var snapshotId = resp.snapshot_id || '';
                var snapshotIdSafe = escapeHtml(snapshotId);
                $panel.html(
                    '<div class="rv-applied-banner">' +
                      '<div class="rv-applied-msg">' +
                        '<span class="rv-applied-check">✓</span>' +
                        '<div>' +
                          '<div style="font-weight:600;margin-bottom:1px;">Modifiche applicate</div>' +
                          '<div style="font-size:12px;color:#065f46;">Reversibile per 7 giorni</div>' +
                        '</div>' +
                      '</div>' +
                      (snapshotId ?
                        '<div class="rv-applied-actions">' +
                          '<button type="button" class="rv-btn rv-btn-ghost rv-btn-sm rv-review-applied-btn" data-snapshot-id="' + snapshotIdSafe + '">Rivedi modifica</button>' +
                          '<button type="button" class="rv-btn rv-btn-ghost rv-btn-sm rv-undo-btn" data-snapshot-id="' + snapshotIdSafe + '">Annulla modifica</button>' +
                        '</div>'
                      : '') +
                    '</div>'
                );

                // 3.8.1 — Schema impact section (re-validate after apply)
                if (schemaBefore && schemaPostId > 0) {
                    schedulePostApplySchemaCheck($panel, schemaPostId, schemaBefore);
                }

                toast('Modifiche salvate sul sito.');
                loadSnapshots({ expand: true }); // Refresh sidebar e aprila per dare feedback visivo
            }).fail(function(xhr) {
                $btn.prop('disabled', false).html(originalHtml);
                $panel.find('.rv-discard-btn').prop('disabled', false);
                toast('Errore rete (' + xhr.status + ')');
            });
        });

        // ═══ Discard ═══
        $(document).on('click', '.rv-discard-btn', function() {
            var $btn = $(this);
            var $panel = $btn.closest('.review-panel');
            var $item = $btn.closest('.orch-action-item');
            var proposalId = $btn.attr('data-proposal-id');
            var originalBtn = $panel.data('original-btn');
            var originalHtml = $panel.data('original-btn-html');

            $btn.prop('disabled', true);

            $.post(ajaxurl, {
                action: 'seo_aeo_orchestra_discard_proposal',
                nonce: seoAeoOrchestra.nonce,
                proposal_id: proposalId
            }).done(function(resp) {
                $panel.remove();
                $item.removeClass('expanded');
                if (originalBtn) {
                    originalBtn.disabled = false;
                    originalBtn.innerHTML = originalHtml;
                    $(originalBtn).show();
                }
                var msg = 'Proposta scartata.';
                if (resp && resp.credits_refunded > 0) msg += ' Rimborsati ' + resp.credits_refunded + ' crediti.';
                toast(msg);
            }).fail(function(xhr) {
                $btn.prop('disabled', false);
                toast('Errore rete (' + xhr.status + ')');
            });
        });

        // ═══ Undo (da banner appena applicato o da sidebar) ═══
        var pendingUndoSnapshot = null;
        var pendingUndoContext = null;
        $(document).on('click', '.rv-undo-btn, .rv-sidebar-undo-btn', function(e) {
            e.preventDefault();
            pendingUndoSnapshot = $(this).attr('data-snapshot-id');
            pendingUndoContext = $(this).hasClass('rv-undo-btn') ? 'banner' : 'sidebar';
            $('#rv-undo-overlay').addClass('show');
        });
        $(document).on('click', '#rv-undo-cancel', function() {
            $('#rv-undo-overlay').removeClass('show');
            pendingUndoSnapshot = null;
        });
        $(document).on('click', '#rv-undo-overlay', function(e) {
            if (e.target.id === 'rv-undo-overlay') {
                $('#rv-undo-overlay').removeClass('show');
                pendingUndoSnapshot = null;
            }
        });
        $(document).on('click', '#rv-undo-confirm', function() {
            if (!pendingUndoSnapshot) { $('#rv-undo-overlay').removeClass('show'); return; }
            var sid = pendingUndoSnapshot;
            $('#rv-undo-overlay').removeClass('show');
            pendingUndoSnapshot = null;
            $.post(ajaxurl, {
                action: 'seo_aeo_orchestra_undo_snapshot',
                nonce: seoAeoOrchestra.nonce,
                snapshot_id: sid
            }).done(function(resp) {
                if (resp && resp.error) {
                    toast('Errore ripristino: ' + resp.error);
                    return;
                }
                toast('Versione precedente ripristinata.');
                loadSnapshots();
            }).fail(function(xhr) {
                toast('Errore rete (' + xhr.status + ')');
            });
        });
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#rv-undo-overlay').hasClass('show')) {
                $('#rv-undo-overlay').removeClass('show');
                pendingUndoSnapshot = null;
            }
        });

        // ═══ Sidebar "Modifiche recenti" ═══ (3.4.3 — collapsible + dettagli)
        // 3.7.0 — Onboarding wizard dismiss handlers
        function dismissOnboarding() {
            $('#orch-onboarding-overlay').fadeOut(200, function(){ $(this).remove(); });
            $.post(ajaxurl, {
                action: 'seo_aeo_orchestra_dismiss_onboarding',
                nonce: seoAeoOrchestra.nonce
            });
        }
        $(document).on('click', '#orch-onboarding-skip, #orch-onboarding-skip-btn', function(e){ e.preventDefault(); dismissOnboarding(); });
        $(document).on('click', '#orch-onboarding-cta', function(){
            // Click CTA: dismiss + lascia che il link nativo navighi
            dismissOnboarding();
        });
        // Click overlay (non sul modal stesso) → chiudi
        $(document).on('click', '#orch-onboarding-overlay', function(e){
            if (e.target.id === 'orch-onboarding-overlay') dismissOnboarding();
        });

        var modsUserToggled = false;
        function setModsCollapsed(collapsed) {
            // 3.4.6 — La card "Modifiche" è ora un aside indipendente.
            // Toggle solo .collapsed su #orch-mods-card (che ora è l'aside stesso).
            var $card = $('#orch-mods-card');
            var $head = $('#orch-mods-toggle');
            var $list = $('#orch-mods-list');
            $card.toggleClass('collapsed', !!collapsed);
            $head.attr('aria-expanded', collapsed ? 'false' : 'true');
            $list.attr('aria-hidden', collapsed ? 'true' : 'false');
        }
        $(document).on('click keydown', '#orch-mods-toggle', function(e) {
            if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
            e.preventDefault();
            modsUserToggled = true;
            setModsCollapsed(!$('#orch-mods-card').hasClass('collapsed'));
        });

        function loadSnapshots(opts) {
            opts = opts || {};
            $.post(ajaxurl, {
                action: 'seo_aeo_orchestra_list_snapshots',
                nonce: seoAeoOrchestra.nonce,
                limit: 20
            }).done(function(resp) {
                var $list = $('#orch-mods-list');
                var $count = $('#orch-mods-count');
                var items = (resp && Array.isArray(resp.items)) ? resp.items : [];
                $count.text(items.length > 0 ? '(' + items.length + ')' : '');
                var T = (window.SeoAeoOrchestra && SeoAeoOrchestra.t) ? SeoAeoOrchestra.t : function(s){return s;};
                if (items.length === 0) {
                    $list.html('<div class="orch-mods-empty">' + T('Nessuna modifica ancora applicata.') + '</div>');
                    if (!modsUserToggled) setModsCollapsed(true);
                    return;
                }
                // 3.42.1 #1 — agent → human action label + tier dot. Mirrors
                // the M4 AGENT_TIER map for consistency.
                var AGENT_ACTION_LABEL = {
                    'schema_generator':          T('Schema JSON-LD aggiunto'),
                    'faq_generator':             T('FAQ generata'),
                    'intro_rewriter':            T('Intro pagina riscritta'),
                    'meta_optimizer':            T('Meta tags riscritti'),
                    'authority_generator':       T('Segnali autorità aggiunti'),
                    'internal_links_generator':  T('Link interni suggeriti'),
                    'snippet_optimizer':         T('Featured Snippet ottimizzato'),
                    'heading_optimizer':         T('Heading H1-H6 riorganizzati'),
                    'content_generator':         T('Contenuto rigenerato (full)')
                };
                var AGENT_TIER_DOT = {
                    'schema_generator': 'safe', 'faq_generator': 'safe', 'internal_links_generator': 'safe',
                    'meta_optimizer': 'caution', 'intro_rewriter': 'caution', 'authority_generator': 'caution',
                    'snippet_optimizer': 'caution', 'heading_optimizer': 'caution',
                    'content_generator': 'danger'
                };
                function tierDotHtml(tier) {
                    if (!tier) return '';
                    var color = tier === 'safe' ? '#22c55e' : (tier === 'caution' ? '#eab308' : '#ef4444');
                    return '<span class="tier-dot tier-' + tier + '" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' + color + ';margin-right:6px;vertical-align:middle;"></span>';
                }
                function formatByteDelta(d) {
                    var n = parseInt(d || 0, 10);
                    if (isNaN(n) || n === 0) return '';
                    var sign = n > 0 ? '+' : '';
                    var abs = Math.abs(n);
                    var formatted = abs >= 1000 ? Math.round(abs / 100) / 10 + 'k' : abs;
                    return sign + (n > 0 ? '' : '-') + formatted + ' byte';
                }
                var html = items.map(function(it) {
                    var daysLeft = parseInt(it.days_remaining || 0, 10);
                    var isExpired = it.is_expired || daysLeft <= 0;
                    var remClass = isExpired ? 'expired' : (daysLeft <= 1 ? 'warn' : '');
                    var remText = isExpired ? T('Scaduto')
                                 : (daysLeft === 1 ? T('1 giorno rimasto') : T('{N} giorni rimasti').replace('{N}', daysLeft));
                    var when = it.applied_at ? timeAgo(it.applied_at) : '';
                    var sid = escapeHtml(it.snapshot_id);
                    // 3.42.1 #1 — action-label (not page-title) as primary,
                    // page_short as subtitle, byte_delta visible in foot,
                    // snapshot_id moved to data-attribute on Dettagli only.
                    var agent = (it.agent || '').toString();
                    var actionLabel = AGENT_ACTION_LABEL[agent] || (agent ? agent.replace(/_/g, ' ') : T('Modifica applicata'));
                    var tier = AGENT_TIER_DOT[agent] || '';
                    var pageShort = it.page_short || it.post_title || ('Post #' + it.post_id);
                    var byteDeltaText = formatByteDelta(it.byte_delta);
                    var detailsBtn = '<button type="button" class="rv-btn rv-btn-ghost rv-btn-xs rv-sidebar-details-btn" data-snapshot-id="' + sid + '" title="' + sid + '">' + T('Dettagli') + '</button>';
                    var undoBtn = isExpired ? ''
                        : '<button type="button" class="rv-btn rv-btn-ghost rv-btn-xs rv-sidebar-undo-btn" data-snapshot-id="' + sid + '">' + T('Ripristina') + '</button>';
                    return '<div class="orch-mods-item"' + (isExpired ? ' style="opacity:0.55;"' : '') + '>' +
                           '<div class="orch-mods-item-title">' + tierDotHtml(tier) + escapeHtml(actionLabel) + '</div>' +
                           '<div class="orch-mods-item-meta">' + escapeHtml(pageShort) + (when ? ' · ' + escapeHtml(when) : '') + '</div>' +
                           '<div class="orch-mods-item-foot">' +
                             '<span class="orch-mods-remaining ' + remClass + '">' +
                                (byteDeltaText ? escapeHtml(byteDeltaText) + ' · ' : '') + remText +
                             '</span>' +
                             '<span class="orch-mods-item-actions">' + detailsBtn + undoBtn + '</span>' +
                           '</div>' +
                           '</div>';
                }).join('');
                $list.html(html);
                // Auto-expand: subito dopo apply forziamo l'apertura per dare feedback.
                // Altrimenti default collapsed se >3 items (rispetta scelta utente).
                if (opts.expand) {
                    setModsCollapsed(false);
                } else if (!modsUserToggled) {
                    setModsCollapsed(items.length > 3);
                }
            }).fail(function() {
                var T = (window.SeoAeoOrchestra && SeoAeoOrchestra.t) ? SeoAeoOrchestra.t : function(s){return s;};
                $('#orch-mods-list').html('<div class="orch-mods-empty">' + T('Errore caricamento.') + '</div>');
            });
        }

        // ═══ Modal dettagli snapshot ═══ (3.4.3 — riusato da sidebar e da banner "Rivedi")
        var FIELD_LABELS = {
            meta_title:       'Meta Title',
            meta_description: 'Meta Description',
            meta_keywords:    'Meta Keywords',
            focus_keyword:    'Focus Keyword',
            focuskw:          'Focus Keyword',
            post_title:       'Titolo pagina',
            post_content:     'Contenuto',
            post_excerpt:     'Riassunto',
            seo_title:        'SEO Title',
            seo_description:  'SEO Description'
        };
        function fieldLabel(key) {
            return FIELD_LABELS[key] || key.replace(/_/g, ' ').replace(/\b\w/g, function(c){return c.toUpperCase();});
        }
        function isPlainScalar(v) {
            return v === null || v === undefined || typeof v === 'string' || typeof v === 'number' || typeof v === 'boolean';
        }
        function valToString(v) {
            if (v === null || v === undefined || v === '') return '';
            if (isPlainScalar(v)) return String(v);
            try { return JSON.stringify(v, null, 2); } catch (e) { return String(v); }
        }
        // Word-level diff: tokenizza per spazio + punteggiatura, LCS, render add/del.
        function tokenize(s) {
            return String(s || '').split(/(\s+|[.,;:!?()\[\]{}"'\-—–…])/).filter(function(t){ return t.length > 0; });
        }
        function lcsTable(a, b) {
            var n = a.length, m = b.length;
            var dp = new Array(n + 1);
            for (var i = 0; i <= n; i++) {
                dp[i] = new Array(m + 1).fill(0);
            }
            for (var i = 1; i <= n; i++) {
                for (var j = 1; j <= m; j++) {
                    if (a[i-1] === b[j-1]) dp[i][j] = dp[i-1][j-1] + 1;
                    else dp[i][j] = Math.max(dp[i-1][j], dp[i][j-1]);
                }
            }
            return dp;
        }
        function simpleWordDiff(before, after) {
            var a = tokenize(before);
            var b = tokenize(after);
            // Cap on huge contents to avoid O(n*m) explosion
            if (a.length > 800 || b.length > 800) {
                return {
                    before: '<span class="rv-diff-del">' + escapeHtml(before) + '</span>',
                    after:  '<span class="rv-diff-add">' + escapeHtml(after) + '</span>'
                };
            }
            var dp = lcsTable(a, b);
            var i = a.length, j = b.length;
            var beforeOut = [], afterOut = [];
            while (i > 0 && j > 0) {
                if (a[i-1] === b[j-1]) {
                    beforeOut.unshift(escapeHtml(a[i-1]));
                    afterOut.unshift(escapeHtml(b[j-1]));
                    i--; j--;
                } else if (dp[i-1][j] >= dp[i][j-1]) {
                    beforeOut.unshift('<span class="rv-diff-del">' + escapeHtml(a[i-1]) + '</span>');
                    i--;
                } else {
                    afterOut.unshift('<span class="rv-diff-add">' + escapeHtml(b[j-1]) + '</span>');
                    j--;
                }
            }
            while (i > 0) { beforeOut.unshift('<span class="rv-diff-del">' + escapeHtml(a[i-1]) + '</span>'); i--; }
            while (j > 0) { afterOut.unshift('<span class="rv-diff-add">' + escapeHtml(b[j-1]) + '</span>'); j--; }
            return { before: beforeOut.join(''), after: afterOut.join('') };
        }
        function renderDiffField(key, beforeVal, afterVal) {
            var beforeStr = valToString(beforeVal);
            var afterStr  = valToString(afterVal);
            // Skip identical values (nothing changed)
            if (beforeStr === afterStr) return '';
            var diff = simpleWordDiff(beforeStr, afterStr);
            var T = (window.SeoAeoOrchestra && SeoAeoOrchestra.t) ? SeoAeoOrchestra.t : function(s){return s;};
            var beforeHtml = beforeStr === '' ? '<span class="rv-empty">' + T('(vuoto)') + '</span>' : diff.before;
            var afterHtml  = afterStr  === '' ? '<span class="rv-empty">' + T('(vuoto)') + '</span>' : diff.after;
            return '<div class="rv-field-block">' +
                     '<div class="rv-field-label">' + escapeHtml(fieldLabel(key)) + '</div>' +
                     '<div class="rv-field-row rv-field-before"><div class="rv-field-tag">' + T('Prima') + '</div><div class="rv-field-val">' + beforeHtml + '</div></div>' +
                     '<div class="rv-field-row rv-field-after"><div class="rv-field-tag">' + T('Dopo') + '</div><div class="rv-field-val">' + afterHtml + '</div></div>' +
                   '</div>';
        }
        function formatAppliedAt(unix) {
            var ts = parseInt(unix, 10);
            if (!ts) return '';
            try {
                var d = new Date(ts * 1000);
                var pad = function(n){ return n < 10 ? '0'+n : n; };
                return pad(d.getDate()) + '/' + pad(d.getMonth()+1) + '/' + d.getFullYear() +
                       ', ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
            } catch (e) { return ''; }
        }

        function openSnapshotDetailsModal(snapshotId) {
            if (!snapshotId) return;
            var T = (window.SeoAeoOrchestra && SeoAeoOrchestra.t) ? SeoAeoOrchestra.t : function(s){return s;};
            var $overlay = $('#rv-details-overlay');
            $('#rv-details-title').text(T('Dettagli modifica'));
            $('#rv-details-meta').text('');
            $('#rv-details-body').html('<div class="rv-details-loading">' + T('Caricamento dettagli...') + '</div>');
            $('#rv-details-restore').attr('data-snapshot-id', snapshotId).hide();
            $overlay.addClass('show');

            $.post(ajaxurl, {
                action: 'seo_aeo_orchestra_get_snapshot_details',
                nonce: seoAeoOrchestra.nonce,
                snapshot_id: snapshotId
            }).done(function(resp) {
                if (!resp || resp.error) {
                    var rvDetailsErr = resp && (resp.message || (typeof resp.error === 'string' ? resp.error : '')) || resp.detail || T('Errore caricamento.');
                    $('#rv-details-body').html('<div class="rv-details-error">' + escapeHtml(rvDetailsErr) + '</div>');
                    return;
                }
                var prev = (resp.previous_state && typeof resp.previous_state === 'object') ? resp.previous_state : {};
                var appl = (resp.applied_state  && typeof resp.applied_state  === 'object') ? resp.applied_state  : {};
                // Union of keys
                var keys = {};
                Object.keys(prev).forEach(function(k){ keys[k] = true; });
                Object.keys(appl).forEach(function(k){ keys[k] = true; });
                var ordered = Object.keys(keys).sort(function(a,b){
                    var order = ['meta_title','seo_title','meta_description','seo_description','focus_keyword','focuskw','meta_keywords','post_title','post_excerpt','post_content'];
                    var ia = order.indexOf(a), ib = order.indexOf(b);
                    if (ia === -1) ia = 999;
                    if (ib === -1) ib = 999;
                    if (ia !== ib) return ia - ib;
                    return a.localeCompare(b);
                });

                var blocks = ordered.map(function(k){ return renderDiffField(k, prev[k], appl[k]); }).filter(Boolean);
                if (blocks.length === 0) {
                    $('#rv-details-body').html('<div class="rv-details-loading">' + T('Nessuna modifica registrata in questo snapshot.') + '</div>');
                } else {
                    $('#rv-details-body').html(blocks.join(''));
                }

                var metaParts = [];
                if (resp.applied_at) metaParts.push(T('Modifica del') + ' ' + formatAppliedAt(resp.applied_at));
                if (resp.post_title) metaParts.push(T('Pagina:') + ' ' + resp.post_title);
                if (resp.proposal_id) metaParts.push(T('ID proposta:') + ' ' + resp.proposal_id);
                $('#rv-details-meta').text(metaParts.join(' · '));
                $('#rv-details-title').text(T('Dettagli modifica'));

                // Mostra "Ripristina" solo se non scaduto
                var nowSec = Math.floor(Date.now()/1000);
                var stillRestorable = resp.expires_at && parseInt(resp.expires_at, 10) > nowSec;
                if (stillRestorable) {
                    $('#rv-details-restore').attr('data-snapshot-id', snapshotId).show();
                }
            }).fail(function(xhr) {
                $('#rv-details-body').html('<div class="rv-details-error">' + T('Errore rete') + ' (' + xhr.status + ')</div>');
            });
        }
        window.openSnapshotDetailsModal = openSnapshotDetailsModal;

        function closeDetailsModal() {
            $('#rv-details-overlay').removeClass('show');
        }
        $(document).on('click', '#rv-details-close, #rv-details-cancel', closeDetailsModal);
        $(document).on('click', '#rv-details-overlay', function(e) {
            if (e.target.id === 'rv-details-overlay') closeDetailsModal();
        });
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#rv-details-overlay').hasClass('show')) closeDetailsModal();
        });
        $(document).on('click', '#rv-details-restore', function() {
            var sid = $(this).attr('data-snapshot-id');
            if (!sid) return;
            closeDetailsModal();
            // Riusa il flusso conferma undo
            pendingUndoSnapshot = sid;
            pendingUndoContext  = 'details';
            $('#rv-undo-overlay').addClass('show');
        });
        // Click su "Dettagli" sidebar o su "Rivedi modifica" banner applied
        $(document).on('click', '.rv-sidebar-details-btn, .rv-review-applied-btn', function(e) {
            e.preventDefault();
            var sid = $(this).attr('data-snapshot-id');
            openSnapshotDetailsModal(sid);
        });

        // 3.4.7 — Click "Copia testo" nel review panel
        $(document).on('click', '.rv-copy-text-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $panel = $btn.closest('.review-panel');
            // Usa textarea editato se presente, altrimenti il proposed-text salvato
            var $textarea = $panel.find('.rv-edit-area[data-field="post_content"]');
            var text = $textarea.length ? $textarea.val() : ($panel.data('proposed-text') || '');
            var T = (window.SeoAeoOrchestra && SeoAeoOrchestra.t) ? SeoAeoOrchestra.t : function(s){return s;};
            if (!text) { toast(T('Nessun testo da copiare')); return; }
            var done = function(ok) {
                if (ok) {
                    var origHtml = $btn.html();
                    $btn.addClass('rv-copied').html('<span aria-hidden="true">✓</span> ' + T('Copiato'));
                    setTimeout(function(){ $btn.removeClass('rv-copied').html(origHtml); }, 1800);
                    toast(T('Testo copiato negli appunti'));
                } else {
                    toast(T('Impossibile copiare. Seleziona e copia manualmente.'));
                }
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function(){ done(true); }, function(){ done(false); });
            } else {
                // Fallback execCommand per browser vecchi
                var $ta = $('<textarea>').css({position:'fixed',top:'-1000px',left:'-1000px'}).val(text).appendTo('body');
                $ta[0].focus(); $ta[0].select();
                var ok = false;
                try { ok = document.execCommand('copy'); } catch (err) { ok = false; }
                $ta.remove();
                done(ok);
            }
        });

        // ═══ 3.4.5 — Cancella cronologia analisi ═══
        function updateHistoryCount() {
            // Conta gli item di cronologia visibili e aggiorna count + visibility bottone "Svuota"
            var count = $('#history-orchestrator .history-item').length;
            $('#orch-history-count').text(count > 0 ? '(' + count + ')' : '');
            $('#orch-history-clear').prop('hidden', count === 0);
        }
        // Aggiorno il count su load + dopo ogni mutazione del container
        $(document).ready(updateHistoryCount);
        var historyEl = document.getElementById('history-orchestrator');
        if (historyEl && window.MutationObserver) {
            new MutationObserver(updateHistoryCount).observe(historyEl, { childList: true, subtree: true });
        }
        $(document).on('click', '#orch-history-clear', function(e) {
            e.preventDefault();
            var count = $('#history-orchestrator .history-item').length;
            $('#rv-clear-history-count').text(count > 0 ? count : 'tutte le');
            $('#rv-clear-history-overlay').addClass('show');
        });
        $(document).on('click', '#rv-clear-history-cancel', function() {
            $('#rv-clear-history-overlay').removeClass('show');
        });
        $(document).on('click', '#rv-clear-history-overlay', function(e) {
            if (e.target.id === 'rv-clear-history-overlay') $('#rv-clear-history-overlay').removeClass('show');
        });
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#rv-clear-history-overlay').hasClass('show')) {
                $('#rv-clear-history-overlay').removeClass('show');
            }
        });
        $(document).on('click', '#rv-clear-history-confirm', function() {
            var $btn = $(this);
            var T = (window.SeoAeoOrchestra && SeoAeoOrchestra.t) ? SeoAeoOrchestra.t : function(s){return s;};
            $btn.prop('disabled', true).text(T('Cancellazione...'));
            $.post(ajaxurl, {
                action: 'seo_aeo_orchestra_clear_analyses_history',
                nonce: seoAeoOrchestra.nonce
            }).done(function(resp) {
                $btn.prop('disabled', false).text(T('Cancella'));
                $('#rv-clear-history-overlay').removeClass('show');
                if (resp && resp.error) {
                    toast(T('Errore:') + ' ' + resp.error);
                    return;
                }
                var n = (resp && typeof resp.cleared === 'number') ? resp.cleared : 0;
                toast(n > 0 ? T('{N} analisi cancellate').replace('{N}', n) : T('Cronologia svuotata'));
                $('#history-orchestrator').empty();
                updateHistoryCount();
                // Forza reload della cronologia dopo svuotamento (rimostra "empty state")
                if (typeof window.reloadOrchestratorHistory === 'function') {
                    window.reloadOrchestratorHistory();
                } else {
                    // Fallback: trigger evento custom; il render handler della sidebar potrebbe ascoltarlo
                    $(document).trigger('orch:history:cleared');
                }
            }).fail(function(xhr) {
                $btn.prop('disabled', false).text(T('Cancella'));
                toast(T('Errore rete') + ' (' + xhr.status + ')');
            });
        });

        function timeAgo(unix) {
            var T = (window.SeoAeoOrchestra && SeoAeoOrchestra.t) ? SeoAeoOrchestra.t : function(s){return s;};
            var seconds = Math.floor((Date.now()/1000) - parseInt(unix, 10));
            if (seconds < 60) return T('Ora');
            if (seconds < 3600) return T('{N} m fa').replace('{N}', Math.floor(seconds/60));
            if (seconds < 86400) return T('{N} h fa').replace('{N}', Math.floor(seconds/3600));
            return T('{N} g fa').replace('{N}', Math.floor(seconds/86400));
        }

        // ═══ Utility ═══
        function escapeHtml(s) {
            return String(s || '').replace(/[&<>"']/g, function(m) {
                return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[m];
            });
        }

        var toastTimer = null;
        window.__rv_toast = function(msg) { toast(msg); };
        function toast(msg) {
            var $t = $('#rv-toast');
            if (!$t.length) return;
            $t.text(msg).addClass('show');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(function() { $t.removeClass('show'); }, 3500);
        }

        // Init: carica snapshot appena caricata la pagina
        $(function() { setTimeout(loadSnapshots, 300); });
    })();

    setTimeout(function() {
        if (typeof window.SeoAeoOrchestra === 'undefined') {
            console.warn('AEO Orchestra: admin.js non caricato. Attivazione fallback inline.');
        }
    }, 2000);
});


    /* ═════════════════════════════════════════════════════════
       3.4.0 — Deduplica piano d'azione (stesso agent + url/post_id)
       ═════════════════════════════════════════════════════════ */
    (function() {
        // 3.6.0 — Anti-flash: nascondo TUTTI gli items finché dedup non ha decisione.
        // Stile inline con setTimeout 0 per applicarsi prima del paint.
        var styleEl = document.createElement('style');
        styleEl.textContent = '.orch-action-item:not(.orch-dedup-shown):not(.orch-dedup-hidden){visibility:hidden!important}';
        document.head.appendChild(styleEl);

        function dedupActions() {
            var items = document.querySelectorAll('.orch-action-item');
            var seen = {};
            items.forEach(function(it) {
                var btn = it.querySelector('.orch-action-btn');
                if (!btn) {
                    // Senza bottone non possiamo fare dedup, mostra comunque
                    it.classList.add('orch-dedup-shown');
                    return;
                }
                var agent = btn.getAttribute('data-agent') || '';
                var raw = btn.getAttribute('data-action-data') || '{}';
                var data = {};
                try { data = JSON.parse(raw); } catch(e) {}
                var key = agent + '|' + (data.post_id || data.url || data.topic || '');
                if (seen[key]) {
                    // Duplicato: nascondi
                    it.style.display = 'none';
                    it.classList.add('orch-dedup-hidden');
                } else {
                    seen[key] = true;
                    it.classList.add('orch-dedup-shown');
                }
            });
        }
        // Esegui al load + dopo eventuale aggiornamento DOM (mutationObserver leggero)
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', dedupActions);
        } else {
            dedupActions();
        }
        // Polling intelligente: ogni 2s, se il count e' cambiato esegue dedup
        var lastCount = 0;
        setInterval(function() {
            var count = document.querySelectorAll('.orch-action-item').length;
            if (count !== lastCount) {
                lastCount = count;
                if (count > 0) dedupActions();
            }
        }, 2000);
    })();

</script>

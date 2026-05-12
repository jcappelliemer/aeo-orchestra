<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.VariableNotPrefixed
/*
 * 3.38.0 — Setup Guidato (Onboarding 3.0)
 *
 * 7-step guided setup with persona branching, auto-detection, resume.
 * Persistent state in wp_options.seo_aeo_setup_progress (JSON).
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('SEO_AEO_Setup_Progress')) {
    echo '<div class="wrap"><h1>Setup Guidato</h1><p>Classe state machine non caricata.</p></div>';
    return;
}

// Auto-detect and sync state on every page render — keeps the display in
// sync with actions the user took on other admin pages.
$state = SEO_AEO_Setup_Progress::sync_from_plugin_state();
$done_count  = SEO_AEO_Setup_Progress::done_count();
$total_steps = count(SEO_AEO_Setup_Progress::STEPS);
$persona     = $state['persona_type'] ?? null;
$show_persona_pick = empty($persona);

// 3.38.1 Task 4 — first-run mode (?first_run=1 from activation redirect).
$is_first_run = isset($_GET['first_run']) && $_GET['first_run'] === '1';

// Mark setup-completed-once flag the first time all_done() becomes true.
if ($done_count >= $total_steps && !get_option('seo_aeo_setup_completed_once', false)) {
    update_option('seo_aeo_setup_completed_once', true, false);
}

// Handle "Esplora liberamente" POST — set skipped flag + redirect to Dashboard.
if (isset($_POST['seo_aeo_setup_skip']) && check_admin_referer('seo_aeo_setup_skip')) {
    update_option('seo_aeo_setup_skipped_by_user', true, false);
    wp_safe_redirect(admin_url('admin.php?page=seo-aeo-orchestra'));
    exit;
}

// Persona-agnostic step catalog. Title/description/outcome are translated.
$T = function ($s) {
    return class_exists('SEO_AEO_T') ? SEO_AEO_T::t($s) : $s;
};

$steps_catalog = array(
    'perimeter' => array(
        'num'      => 1,
        'icon'     => '🎯',
        'title'    => $T('Definisci il tuo perimetro'),
        'time'     => '2 min',
        'cost'     => $T('gratis'),
        'desc'     => $T('Settore, tipo sito, lingua principale e mercati geografici. 4 campi per dare contesto all\'AI.'),
        'outcomes' => array(
            $T('Schema Organization base applicato'),
            $T('Context AI ha settore + lingua per ogni generazione'),
        ),
        'url'      => '',  // inline form on this page
        'inline'   => true,
        'skippable' => false,
    ),
    'business_profile' => array(
        'num'      => 2,
        'icon'     => '🏢',
        'title'    => $T('Profilo Business'),
        'time'     => '5 min',
        'cost'     => $T('gratis'),
        'desc'     => $T('Cosa offri, per chi, cosa ti distingue, casi d\'uso, brand voice traits. È il foundation di tutta la generazione AI.'),
        'outcomes' => array(
            $T('Context AI: arricchimento profilo'),
            $T('Schema Organization + LocalBusiness completo'),
            $T('Tutti i contenuti AI generati con il tuo posizionamento'),
        ),
        'url'      => admin_url('admin.php?page=seo-aeo-business-profile'),
        'skippable' => false,
    ),
    'keyword_research' => array(
        'num'      => 3,
        'icon'     => '🔍',
        'title'    => $T('Keyword Research'),
        'time'     => '3 min',
        'cost'     => '15 ' . $T('crediti'),
        'desc'     => $T('Identifica 30+ keyword strategiche organizzate per cluster + intent. Base per content calendar e ottimizzazione esistenti.'),
        'outcomes' => array(
            $T('30+ keyword con volume, difficoltà, intent'),
            $T('Suggested topics per ogni cluster'),
        ),
        'url'      => admin_url('admin.php?page=seo-aeo-keyword-research'),
        'skippable' => true,
    ),
    'brand_voice' => array(
        'num'      => 4,
        'icon'     => '🎙️',
        'title'    => $T('Insegna la tua voce all\'AI'),
        'time'     => '8 min',
        'cost'     => '25 ' . $T('crediti'),
        'desc'     => $T('Estraiamo lo stile editoriale dai tuoi contenuti già pubblicati. Tutti gli articoli AI futuri suoneranno come te, non come ChatGPT.'),
        'outcomes' => array(
            $T('Brand Voice profile salvato'),
            $T('Tono, lunghezza frasi, vocabolario distintivo estratti'),
            $T('Auto-injection nei prompt di generazione contenuti'),
        ),
        'url'      => admin_url('admin.php?page=seo-aeo-brand-voice'),
        'skippable' => true,
    ),
    'orchestrator' => array(
        'num'      => 5,
        'icon'     => '⭐',
        'title'    => $T('Analizza il sito (1ª pagina gratis)'),
        'time'     => '5 min',
        'cost'     => $T('GRATIS la prima · 5 crediti/pagina successiva'),
        'desc'     => $T('La prima analisi sul home è offerta da AEO Orchestra. Te la regaliamo per provare l\'esperienza completa: score SEO + AEO, problemi rilevati, piano d\'azione prioritizzato.'),
        'outcomes' => array(
            $T('Health Score sito (0-100)'),
            $T('Lista problemi SEO + AEO con priorità'),
            $T('Piano d\'azione concrete con bottoni "Risolvi con AI"'),
        ),
        'url'      => admin_url('admin.php?page=seo-aeo-orchestratore&is_free_first=1'),
        'skippable' => false,
        'badge'     => $T('PRIMA PAGINA GRATIS'),
    ),
    'native_output' => array(
        'num'      => 6,
        'icon'     => '⚡',
        'title'    => $T('Configura SEO + AEO Output'),
        'time'     => '2 min',
        'cost'     => $T('gratis'),
        'desc'     => $T('Attiva lo stack nativo: meta tag + sitemap + llms.txt + schema. Opzionale Override Mode per silenziare Yoast/RankMath senza disinstallarli.'),
        'outcomes' => array(
            $T('Output SEO unificato nel <head>'),
            $T('Sitemap.xml + llms.txt automatici'),
            $T('Schema.org dinamico per ogni tipo di pagina'),
        ),
        'url'      => admin_url('admin.php?page=seo-aeo-native-output'),
        'skippable' => false,
    ),
    'auto_pilot' => array(
        'num'      => 7,
        'icon'     => '🤖',
        'title'    => $T('Pianifica produzione contenuti'),
        'time'     => '5 min setup',
        'cost'     => $T('setup gratis · gen articoli da pricing'),
        'desc'     => $T('Configura Auto-Pilot: pesca keyword dal Research, genera N articoli a settimana, auto-pubblica come draft o live (a scelta).'),
        'outcomes' => array(
            $T('Calendar editoriale ricorrente'),
            $T('Articoli generati con brand voice + Context AI'),
            $T('Plugin lavora 24/7 anche quando sei offline'),
        ),
        'url'      => admin_url('admin.php?page=seo-aeo-autopilot'),
        'skippable' => false,
    ),
);

$progress_pct = $total_steps > 0 ? round(($done_count / $total_steps) * 100) : 0;
?>

<div class="wrap orch-setup-guidato">

    <?php if ($is_first_run): ?>
    <section class="orch-setup-firstrun-hero">
        <div class="orch-setup-firstrun-inner">
            <h1 class="orch-setup-firstrun-title">🎉 <?php echo esc_html($T('Benvenuto in AEO Orchestra!')); ?></h1>
            <p class="orch-setup-firstrun-sub"><?php echo esc_html($T('In 7 step (~25 min) configuriamo il plugin sul tuo sito così il tuo brand sia visibile sia su Google che su ChatGPT, Claude, Perplexity, Gemini.')); ?></p>
            <p class="orch-setup-firstrun-gift">⭐ <strong><?php echo esc_html($T('La prima analisi della home è gratis')); ?></strong> — <?php echo esc_html($T('offerta da AEO Orchestra')); ?></p>
            <div class="orch-setup-firstrun-cta">
                <button type="button" class="orch3-btn orch3-btn-primary orch3-btn-large" id="orch-setup-firstrun-start">→ <?php echo esc_html($T('Inizia il setup')); ?></button>
                <form method="post" style="display:inline;">
                    <?php wp_nonce_field('seo_aeo_setup_skip'); ?>
                    <input type="hidden" name="seo_aeo_setup_skip" value="1" />
                    <button type="submit" class="orch3-btn orch3-btn-ghost"><?php echo esc_html($T('Esplora liberamente')); ?></button>
                </form>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <header class="orch-setup-hero">
        <div class="orch-setup-hero-inner">
            <h1 class="orch-setup-hero-title">
                <span class="orch-setup-hero-emoji">🎯</span>
                <?php echo esc_html($T('Setup Guidato AEO Orchestra')); ?>
            </h1>
            <p class="orch-setup-hero-sub"><?php echo esc_html($T('Adatta il plugin alle tue esigenze. Riprendi quando vuoi — lo stato si salva automaticamente.')); ?></p>

            <div class="orch-setup-progress-bar-wrap">
                <div class="orch-setup-progress-bar">
                    <div class="orch-setup-progress-fill" style="width: <?php echo esc_attr($progress_pct); ?>%"></div>
                </div>
                <div class="orch-setup-progress-label">
                    <span class="orch-setup-progress-num"><?php echo esc_html($done_count); ?>/<?php echo esc_html($total_steps); ?></span>
                    <?php echo esc_html($T('step completati')); ?>
                </div>
            </div>
        </div>
    </header>

    <?php if ($show_persona_pick): ?>
    <section class="orch-setup-persona-card" id="orch-setup-persona-card">
        <h2 class="orch-setup-persona-title">👋 <?php echo esc_html($T('Benvenuto in AEO Orchestra')); ?></h2>
        <p class="orch-setup-persona-q"><?php echo esc_html($T('Come usi Orchestra? Una sola domanda — ci aiuta a tarare il tono e i suggerimenti.')); ?></p>
        <div class="orch-setup-persona-options">
            <button type="button" class="orch-setup-persona-btn" data-persona="wp_owner">
                <span class="orch-setup-persona-icon">🌐</span>
                <span class="orch-setup-persona-label"><?php echo esc_html($T('Per il mio sito (singolo)')); ?></span>
                <span class="orch-setup-persona-sub"><?php echo esc_html($T('Owner WordPress')); ?></span>
            </button>
            <button type="button" class="orch-setup-persona-btn" data-persona="consultant">
                <span class="orch-setup-persona-icon">💼</span>
                <span class="orch-setup-persona-label"><?php echo esc_html($T('Per siti clienti (consulenza)')); ?></span>
                <span class="orch-setup-persona-sub"><?php echo esc_html($T('SEO/freelance/agenzia')); ?></span>
            </button>
            <button type="button" class="orch-setup-persona-btn" data-persona="exploring">
                <span class="orch-setup-persona-icon">🔍</span>
                <span class="orch-setup-persona-label"><?php echo esc_html($T('Sto valutando')); ?></span>
                <span class="orch-setup-persona-sub"><?php echo esc_html($T('Fammi esplorare')); ?></span>
            </button>
        </div>
    </section>
    <?php endif; ?>

    <?php
    // 3.38.1 Task 1 — build per-step preview data. Read each step's
    // underlying source so the card can show a compact summary instead of
    // just a status badge. Each value is escaped at render time.
    $previews = array();
    // Perimetro — saved in state.data
    $perim = $state['steps']['perimeter']['data'] ?? array();
    if (!empty($perim) && is_array($perim)) {
        $previews['perimeter'] = array(
            array('label' => $T('Settore'),  'value' => (string)($perim['industry']  ?? '')),
            array('label' => $T('Tipo sito'),'value' => (string)($perim['site_type'] ?? '')),
            array('label' => $T('Lingua'),   'value' => (string)($perim['language']  ?? '')),
            array('label' => $T('Mercati'),  'value' => (string)($perim['markets']   ?? '')),
        );
    }
    // Profilo Business — read get_option('seo_aeo_business_profile_data')
    $bp = get_option('seo_aeo_business_profile_data', array());
    if (is_array($bp) && !empty($bp)) {
        $desc = isset($bp['description']) ? trim((string)$bp['description']) : '';
        $cx_chars = 0;
        foreach ($bp as $v) {
            if (is_string($v)) $cx_chars += strlen($v);
            elseif (is_array($v)) {
                foreach ($v as $vv) { if (is_string($vv)) $cx_chars += strlen($vv); }
            }
        }
        $previews['business_profile'] = array(
            array('label' => $T('Descrizione'), 'value' => (mb_strlen($desc) > 80 ? mb_substr($desc, 0, 77).'…' : $desc)),
            array('label' => $T('Context AI'),  'value' => $cx_chars > 0 ? ($cx_chars . ' ' . $T('caratteri')) : ''),
        );
    }
    // Keyword Research — read history for last keyword_research entry
    $kw_preview = null;
    $hist_raw = get_option('seo_aeo_orchestra_history_json', '');
    $hist_items = (is_string($hist_raw) && !empty($hist_raw)) ? json_decode($hist_raw, true) : array();
    if (is_array($hist_items)) {
        foreach ($hist_items as $h) {
            if ((($h['section'] ?? '') === 'keyword_research') || (($h['type'] ?? '') === 'keyword_research')) {
                $data_field = $h['data'] ?? '';
                $count = 0;
                if (is_string($data_field)) {
                    $dec = json_decode($data_field, true);
                    if (is_array($dec) && isset($dec['keywords']) && is_array($dec['keywords'])) {
                        $count = count($dec['keywords']);
                    } elseif (is_array($dec)) {
                        $count = count($dec);
                    }
                } elseif (is_array($data_field)) {
                    $count = count($data_field);
                }
                $ts = isset($h['date']) ? (string)$h['date'] : '';
                $previews['keyword_research'] = array(
                    array('label' => $T('Keyword generate'), 'value' => $count > 0 ? (string)$count : ''),
                    array('label' => $T('Ultima'),          'value' => $ts),
                );
                break;
            }
        }
    }
    // Brand Voice — read profiles
    $bv_profiles = get_option('seo_aeo_brand_voice_profiles', array());
    if (is_array($bv_profiles) && count($bv_profiles) > 0) {
        $first = reset($bv_profiles);
        if (is_array($first)) {
            $previews['brand_voice'] = array(
                array('label' => $T('Profilo'),  'value' => (string)($first['name'] ?? $T('Default'))),
                array('label' => $T('Tono'),     'value' => (string)($first['tone'] ?? '')),
                array('label' => $T('Audience'), 'value' => isset($first['audience']) ? mb_substr((string)$first['audience'], 0, 80) : ''),
            );
        }
    }
    // Orchestratore — read history for last analysis
    if (is_array($hist_items)) {
        foreach ($hist_items as $h) {
            $sec = (string)($h['section'] ?? '');
            if ($sec === 'orchestrator' || $sec === 'analysis' || (($h['type'] ?? '') === 'analysis')) {
                $data_field = $h['data'] ?? '';
                $score_seo = null; $score_aeo = null; $pages_count = null;
                if (is_string($data_field)) {
                    $dec = json_decode($data_field, true);
                    if (is_array($dec)) {
                        $score_seo = $dec['avg_seo'] ?? null;
                        $score_aeo = $dec['avg_aeo'] ?? null;
                        $pages_count = $dec['pages_count'] ?? null;
                    }
                } elseif (is_array($data_field)) {
                    $score_seo = $data_field['avg_seo'] ?? null;
                    $score_aeo = $data_field['avg_aeo'] ?? null;
                    $pages_count = $data_field['pages_count'] ?? null;
                }
                $ts = isset($h['date']) ? (string)$h['date'] : '';
                $score_text = '';
                if ($score_seo !== null) $score_text .= 'SEO ' . intval($score_seo);
                if ($score_aeo !== null) $score_text .= ($score_text ? ' · ' : '') . 'AEO ' . intval($score_aeo);
                $previews['orchestrator'] = array(
                    array('label' => $T('Health Score'),    'value' => $score_text),
                    array('label' => $T('Pagine analizzate'),'value' => $pages_count !== null ? (string)$pages_count : ''),
                    array('label' => $T('Ultima'),          'value' => $ts),
                );
                break;
            }
        }
    }
    // SEO+AEO Output — read 4 toggle states
    $no_enabled  = get_option('seo_aeo_native_output_enabled', false);
    $no_override = get_option('seo_aeo_native_output_override', false);
    $no_sitemap  = get_option('seo_aeo_native_sitemap_enabled', false);
    $no_llms     = get_option('seo_aeo_native_llms_enabled', false);
    $no_schema   = get_option('seo_aeo_native_schema_enabled', false);
    if ($no_enabled || $no_sitemap || $no_llms || $no_schema) {
        $on = $T('ON'); $off = $T('OFF');
        $previews['native_output'] = array(
            array('label' => $T('Output nativo'), 'value' => $no_enabled  ? $on : $off),
            array('label' => $T('Override Mode'), 'value' => $no_override ? $on : $off),
            array('label' => $T('Sitemap.xml'),   'value' => $no_sitemap  ? $on : $off),
            array('label' => $T('llms.txt'),      'value' => $no_llms     ? $on : $off),
            array('label' => $T('Schema.org'),    'value' => $no_schema   ? $on : $off),
        );
    }
    // Auto-Pilot — read autopilot_jobs
    $ap_jobs = get_option('seo_aeo_autopilot_jobs', array());
    if (is_array($ap_jobs) && count($ap_jobs) > 0) {
        $active_count = 0;
        $next_run = '';
        foreach ($ap_jobs as $job) {
            if (is_array($job) && !empty($job['active'])) $active_count++;
            if (is_array($job) && !empty($job['next_run']) && (!$next_run || strcmp((string)$job['next_run'], $next_run) < 0)) {
                $next_run = (string)$job['next_run'];
            }
        }
        $previews['auto_pilot'] = array(
            array('label' => $T('Job attivi'),  'value' => (string)$active_count),
            array('label' => $T('Prossima run'),'value' => $next_run),
        );
    }
    ?>
    <section class="orch-setup-steps" id="orch-setup-steps" data-persona="<?php echo esc_attr($persona ?? ''); ?>">
        <?php foreach (SEO_AEO_Setup_Progress::STEPS as $sid): ?>
            <?php
            $cat = $steps_catalog[$sid];
            $st  = $state['steps'][$sid] ?? array('status' => 'todo');
            $status = $st['status'] ?? 'todo';
            $is_done    = ($status === 'done');
            $is_skipped = ($status === 'skipped');
            ?>
            <article class="orch-setup-step orch-setup-step--<?php echo esc_attr($status); ?>" data-step-id="<?php echo esc_attr($sid); ?>">
                <div class="orch-setup-step-head">
                    <div class="orch-setup-step-num">
                        <?php if ($is_done): ?>
                            <span class="orch-setup-step-check" aria-label="done">✓</span>
                        <?php else: ?>
                            <span class="orch-setup-step-numlabel"><?php echo esc_html((string) $cat['num']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="orch-setup-step-headtxt">
                        <h3 class="orch-setup-step-title">
                            <span class="orch-setup-step-icon"><?php echo esc_html($cat['icon']); ?></span>
                            <?php echo esc_html($cat['title']); ?>
                            <?php if (!empty($cat['badge']) && !$is_done): ?>
                                <span class="orch-setup-step-badge"><?php echo esc_html($cat['badge']); ?></span>
                            <?php endif; ?>
                        </h3>
                        <div class="orch-setup-step-meta">
                            <span class="orch-setup-step-time">⏱ <?php echo esc_html($cat['time']); ?></span>
                            <span class="orch-setup-step-cost">· <?php echo esc_html($cat['cost']); ?></span>
                        </div>
                    </div>
                </div>

                <p class="orch-setup-step-desc"><?php echo esc_html($cat['desc']); ?></p>

                <?php if (!empty($cat['outcomes'])): ?>
                <ul class="orch-setup-step-outcomes">
                    <?php foreach ($cat['outcomes'] as $o): ?>
                        <li><?php echo esc_html($o); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <?php if ($is_done && !empty($previews[$sid])): ?>
                <div class="orch-setup-step-preview">
                    <div class="orch-setup-step-preview-head"><?php echo esc_html($T('Dati salvati')); ?></div>
                    <ul class="orch-setup-step-preview-list">
                        <?php foreach ($previews[$sid] as $row): ?>
                            <?php if (empty($row['value'])) continue; ?>
                            <li>
                                <span class="orch-setup-step-preview-label"><?php echo esc_html($row['label']); ?>:</span>
                                <span class="orch-setup-step-preview-value"><?php echo esc_html($row['value']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="orch-setup-step-actions">
                    <?php if ($is_done): ?>
                        <span class="orch-setup-step-status orch-setup-step-status--done">✓ <?php echo esc_html($T('Completato')); ?></span>
                        <?php if (!empty($cat['url'])): ?>
                            <a href="<?php echo esc_url($cat['url']); ?>" class="orch3-btn orch3-btn-ghost orch3-btn-sm"><?php echo esc_html($T('Rivedi')); ?></a>
                        <?php endif; ?>
                        <button type="button" class="orch3-btn orch3-btn-ghost orch3-btn-sm orch-setup-step-redo" data-step-id="<?php echo esc_attr($sid); ?>" title="<?php echo esc_attr($T('Resetta lo step a TODO. I dati sottostanti restano salvati.')); ?>">✗ <?php echo esc_html($T('Marca da rifare')); ?></button>
                    <?php elseif ($is_skipped): ?>
                        <span class="orch-setup-step-status orch-setup-step-status--skipped">⊘ <?php echo esc_html($T('Saltato')); ?></span>
                        <button type="button" class="orch3-btn orch3-btn-ghost orch3-btn-sm orch-setup-step-unskip" data-step-id="<?php echo esc_attr($sid); ?>"><?php echo esc_html($T('Riprova')); ?></button>
                        <?php if (!empty($cat['url'])): ?>
                            <a href="<?php echo esc_url($cat['url']); ?>" class="orch3-btn orch3-btn-primary orch3-btn-sm">→ <?php echo esc_html($T('Apri Step')); ?></a>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (!empty($cat['url'])): ?>
                            <a href="<?php echo esc_url($cat['url']); ?>" class="orch3-btn orch3-btn-primary"><?php echo esc_html($T('→ Apri Step')); ?></a>
                        <?php elseif (!empty($cat['inline'])): ?>
                            <button type="button" class="orch3-btn orch3-btn-primary orch-setup-step-inline-open" data-step-id="<?php echo esc_attr($sid); ?>"><?php echo esc_html($T('→ Compila ora')); ?></button>
                        <?php endif; ?>
                        <?php if (!empty($cat['skippable'])): ?>
                            <button type="button" class="orch3-btn orch3-btn-ghost orch3-btn-sm orch-setup-step-skip" data-step-id="<?php echo esc_attr($sid); ?>"><?php echo esc_html($T('Salta per ora')); ?></button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php if ($sid === 'perimeter'): ?>
                    <?php
                    // Prepopulate Perimetro form with saved state — so user
                    // can edit existing values instead of starting from blank.
                    $pf_industry  = isset($perim['industry'])  ? (string) $perim['industry']  : '';
                    $pf_site_type = isset($perim['site_type']) ? (string) $perim['site_type'] : '';
                    $pf_language  = isset($perim['language'])  ? (string) $perim['language']  : 'it';
                    $pf_markets   = isset($perim['markets'])   ? (string) $perim['markets']   : '';
                    ?>
                    <div class="orch-setup-perimeter-form" style="display:none;" id="orch-setup-perimeter-form">
                        <label class="orch-setup-field">
                            <span class="orch-setup-field-label"><?php echo esc_html($T('Settore principale')); ?></span>
                            <input type="text" name="industry" maxlength="80" value="<?php echo esc_attr($pf_industry); ?>" placeholder="<?php echo esc_attr($T('Es. Software B2B, Consulenza HR, E-commerce moda')); ?>" />
                        </label>
                        <label class="orch-setup-field">
                            <span class="orch-setup-field-label"><?php echo esc_html($T('Tipo di sito')); ?></span>
                            <select name="site_type">
                                <option value=""><?php echo esc_html($T('Scegli...')); ?></option>
                                <option value="ecommerce" <?php selected($pf_site_type, 'ecommerce'); ?>><?php echo esc_html($T('E-commerce / Shop')); ?></option>
                                <option value="blog" <?php selected($pf_site_type, 'blog'); ?>><?php echo esc_html($T('Blog / Editoriale')); ?></option>
                                <option value="corporate" <?php selected($pf_site_type, 'corporate'); ?>><?php echo esc_html($T('Corporate / Aziendale')); ?></option>
                                <option value="services" <?php selected($pf_site_type, 'services'); ?>><?php echo esc_html($T('Servizi professionali')); ?></option>
                                <option value="saas" <?php selected($pf_site_type, 'saas'); ?>><?php echo esc_html($T('SaaS / App')); ?></option>
                                <option value="local" <?php selected($pf_site_type, 'local'); ?>><?php echo esc_html($T('Business locale')); ?></option>
                            </select>
                        </label>
                        <label class="orch-setup-field">
                            <span class="orch-setup-field-label"><?php echo esc_html($T('Lingua principale')); ?></span>
                            <select name="language">
                                <option value="it" <?php selected($pf_language, 'it'); ?>>Italiano</option>
                                <option value="en" <?php selected($pf_language, 'en'); ?>>English</option>
                                <option value="es" <?php selected($pf_language, 'es'); ?>>Español</option>
                                <option value="fr" <?php selected($pf_language, 'fr'); ?>>Français</option>
                                <option value="de" <?php selected($pf_language, 'de'); ?>>Deutsch</option>
                            </select>
                        </label>
                        <label class="orch-setup-field">
                            <span class="orch-setup-field-label"><?php echo esc_html($T('Mercati target (separati da virgola)')); ?></span>
                            <input type="text" name="markets" maxlength="200" value="<?php echo esc_attr($pf_markets); ?>" placeholder="<?php echo esc_attr($T('Italia, Europa, USA')); ?>" />
                        </label>
                        <div class="orch-setup-form-actions">
                            <button type="button" class="orch3-btn orch3-btn-primary orch-setup-perimeter-save"><?php echo esc_html($T('Salva e procedi')); ?></button>
                            <button type="button" class="orch3-btn orch3-btn-ghost orch-setup-perimeter-cancel"><?php echo esc_html($T('Annulla')); ?></button>
                        </div>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>

        <?php if ($done_count >= $total_steps): ?>
        <article class="orch-setup-celebration">
            <h2>🎉 <?php echo esc_html($T('Setup Guidato completato!')); ?></h2>
            <p><?php echo esc_html($T('Orchestra è ora completamente configurato per il tuo sito. Buon lavoro!')); ?></p>
        </article>
        <?php endif; ?>
    </section>

    <?php
    // Localize state for JS.
    $localized = array(
        'state'       => $state,
        'progress'    => array('done' => $done_count, 'total' => $total_steps),
        'ajaxUrl'     => admin_url('admin-ajax.php'),
        'nonce'       => wp_create_nonce('seo_aeo_orchestra_nonce'),
    );
    ?>
    <script>window.seoAeoSetupGuidato = <?php echo wp_json_encode($localized); ?>;</script>
</div>

<?php
// CSS — captured + emitted via SEO_AEO_Inline_Assets so the WP enqueue
// pipeline handles it (compliant with Module 8 timing fix).
ob_start();
?>
.orch-setup-guidato { max-width: 920px; margin: 20px auto; font-family: 'General Sans', -apple-system, sans-serif; color: var(--orch-ink, #0f172a); }

.orch-setup-firstrun-hero { background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 2px solid #fbbf24; border-radius: 16px; padding: 28px 32px; margin-bottom: 20px; box-shadow: 0 4px 16px rgba(251,191,36,0.15); }
.orch-setup-firstrun-title { font-size: 28px; font-weight: 800; color: #78350f; margin: 0 0 10px; letter-spacing: -0.02em; }
.orch-setup-firstrun-sub { color: #78350f; font-size: 15px; line-height: 1.55; margin: 0 0 14px; max-width: 720px; }
.orch-setup-firstrun-gift { font-size: 14px; color: #92400e; margin: 0 0 18px; padding: 10px 14px; background: rgba(255,255,255,0.6); border-radius: 8px; display: inline-block; }
.orch-setup-firstrun-gift strong { color: #78350f; }
.orch-setup-firstrun-cta { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }

.orch-setup-hero { background: linear-gradient(135deg, #0A0E27 0%, #0055FF 55%, #00E5FF 100%); border-radius: 16px; padding: 28px 32px; color: #fff; margin-bottom: 24px; box-shadow: 0 8px 24px rgba(0, 85, 255, 0.18); position: relative; overflow: hidden; }
.orch-setup-hero::after { content:''; position:absolute; right:-40px; top:-40px; width:180px; height:180px; background:radial-gradient(circle, rgba(255,255,255,0.12), transparent 70%); pointer-events:none; }
.orch-setup-hero-inner { position: relative; }
.orch-setup-hero-title { color: #fff; font-size: 26px; font-weight: 700; margin: 0 0 6px; letter-spacing: -0.015em; display:flex; align-items:center; gap:10px; }
.orch-setup-hero-emoji { font-size: 32px; }
.orch-setup-hero-sub { color: rgba(255,255,255,0.85); font-size: 14px; margin: 0 0 18px; max-width: 620px; line-height: 1.5; }
.orch-setup-progress-bar-wrap { display:flex; align-items:center; gap:14px; }
.orch-setup-progress-bar { flex:1; background: rgba(255,255,255,0.18); height: 10px; border-radius: 999px; overflow: hidden; }
.orch-setup-progress-fill { background: #00E5FF; height: 100%; border-radius: 999px; transition: width 350ms cubic-bezier(.2,.7,.2,1); box-shadow: 0 0 12px rgba(0,229,255,0.5); }
.orch-setup-progress-label { color: #fff; font-size: 13px; white-space: nowrap; }
.orch-setup-progress-num { font-weight: 700; font-size: 16px; }

.orch-setup-persona-card { background: #fff; border: 1px solid var(--orch-line, #e2e8f0); border-radius: 12px; padding: 24px; margin-bottom: 20px; }
.orch-setup-persona-title { font-size: 18px; margin: 0 0 6px; font-weight: 700; }
.orch-setup-persona-q { color: var(--orch-muted, #475569); margin: 0 0 16px; }
.orch-setup-persona-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
.orch-setup-persona-btn { background: var(--orch-bg-soft, #f8fafc); border: 1px solid var(--orch-line, #e2e8f0); border-radius: 10px; padding: 16px 14px; cursor: pointer; text-align: left; display:flex; flex-direction:column; gap:6px; font-family:inherit; transition: all 160ms; }
.orch-setup-persona-btn:hover { background: #fff; border-color: var(--orch-accent, #0055FF); box-shadow: 0 2px 8px rgba(0,85,255,0.12); transform: translateY(-1px); }
.orch-setup-persona-btn[data-selected="1"] { background: rgba(0,85,255,0.06); border-color: var(--orch-accent, #0055FF); }
.orch-setup-persona-icon { font-size: 22px; }
.orch-setup-persona-label { font-weight: 600; color: var(--orch-ink, #0f172a); font-size: 14px; }
.orch-setup-persona-sub { font-size: 12px; color: var(--orch-subtle, #64748b); }

.orch-setup-steps { display: flex; flex-direction: column; gap: 14px; }
.orch-setup-step { background: #fff; border: 1px solid var(--orch-line, #e2e8f0); border-radius: 12px; padding: 20px 24px; transition: border-color 160ms; }
.orch-setup-step:hover { border-color: var(--orch-subtle, #94a3b8); }
.orch-setup-step--done { background: rgba(16,185,129,0.04); border-color: rgba(16,185,129,0.3); }
.orch-setup-step--skipped { opacity: 0.7; background: var(--orch-bg-soft, #f8fafc); }
.orch-setup-step-head { display:flex; gap:16px; align-items:flex-start; margin-bottom: 10px; }
.orch-setup-step-num { width:34px; height:34px; border-radius: 50%; background: var(--orch-bg-soft, #f8fafc); border: 1px solid var(--orch-line, #e2e8f0); display:flex; align-items:center; justify-content:center; font-weight:700; color:var(--orch-muted, #475569); flex-shrink:0; }
.orch-setup-step--done .orch-setup-step-num { background: #10b981; border-color: #10b981; color: #fff; }
.orch-setup-step-check { color: #fff; font-size: 18px; line-height:1; }
.orch-setup-step-headtxt { flex:1; min-width:0; }
.orch-setup-step-title { font-size: 16px; font-weight: 700; margin: 0; display:flex; align-items:center; gap:8px; flex-wrap: wrap; color: var(--orch-ink, #0f172a); }
.orch-setup-step-icon { font-size: 20px; }
.orch-setup-step-badge { background: #fbbf24; color: #78350f; padding: 2px 10px; border-radius: 999px; font-size: 10px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.orch-setup-step-meta { font-size: 12px; color: var(--orch-subtle, #64748b); margin-top: 4px; }
.orch-setup-step-desc { color: var(--orch-muted, #475569); margin: 0 0 10px; font-size: 13px; line-height: 1.55; padding-left: 50px; }
.orch-setup-step-outcomes { color: var(--orch-muted, #475569); margin: 0 0 14px; padding: 0 0 0 70px; font-size: 12.5px; line-height: 1.55; }
.orch-setup-step-outcomes li { margin-bottom: 3px; }
.orch-setup-step-actions { display:flex; gap:8px; align-items:center; padding-left: 50px; flex-wrap: wrap; }
.orch-setup-step-status { font-size: 12px; padding: 6px 12px; border-radius: 6px; font-weight: 600; }
.orch-setup-step-status--done { background: rgba(16,185,129,0.12); color: #065f46; }
.orch-setup-step-status--skipped { background: var(--orch-line-soft, #f1f5f9); color: var(--orch-subtle, #64748b); }

.orch-setup-perimeter-form { margin-top: 14px; padding: 16px; background: var(--orch-bg-soft, #f8fafc); border-radius: 10px; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; }
.orch-setup-field { display: flex; flex-direction: column; gap: 4px; font-size: 13px; }
.orch-setup-field-label { font-weight: 600; color: var(--orch-ink, #0f172a); font-size: 12px; }
.orch-setup-field input, .orch-setup-field select { padding: 8px 10px; border: 1px solid var(--orch-line, #e2e8f0); border-radius: 6px; font-family: inherit; font-size: 13px; background: #fff; }
.orch-setup-field input:focus, .orch-setup-field select:focus { outline: none; border-color: var(--orch-accent, #0055FF); box-shadow: 0 0 0 3px rgba(0,85,255,0.12); }
.orch-setup-form-actions { grid-column: 1 / -1; display: flex; gap: 8px; }

.orch-setup-celebration { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; border-radius: 12px; padding: 24px; text-align: center; }
.orch-setup-celebration h2 { color: #fff; margin: 0 0 6px; font-size: 22px; }
.orch-setup-celebration p { color: rgba(255,255,255,0.9); margin: 0; }

/* 3.38.1 Task 1 — step data preview */
.orch-setup-step-preview { margin: 0 0 14px; padding: 10px 14px; background: var(--orch-bg-soft, #f8fafc); border: 1px solid var(--orch-line, #e2e8f0); border-radius: 8px; margin-left: 50px; }
.orch-setup-step-preview-head { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: var(--orch-subtle, #64748b); font-weight: 700; margin-bottom: 6px; }
.orch-setup-step-preview-list { margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 4px; font-size: 12.5px; }
.orch-setup-step-preview-label { font-weight: 600; color: var(--orch-muted, #475569); margin-right: 4px; }
.orch-setup-step-preview-value { color: var(--orch-ink, #0f172a); word-break: break-word; }
.orch-setup-step-redo { color: #b91c1c !important; }
.orch-setup-step-redo:hover { background: #fee2e2 !important; color: #991b1b !important; }
@media (max-width: 600px) { .orch-setup-step-preview { margin-left: 0; } }

@media (max-width: 600px) {
    .orch-setup-hero { padding: 20px; }
    .orch-setup-hero-title { font-size: 22px; }
    .orch-setup-step { padding: 16px; }
    .orch-setup-step-desc, .orch-setup-step-outcomes, .orch-setup-step-actions { padding-left: 0; }
    .orch-setup-persona-options { grid-template-columns: 1fr; }
}
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

<?php ob_start(); ?>
(function($){
    'use strict';
    if (typeof window.seoAeoSetupGuidato === 'undefined') return;
    var SG = window.seoAeoSetupGuidato;

    function ajax(action, data) {
        return $.post(SG.ajaxUrl, $.extend({action: action, nonce: SG.nonce}, data || {}));
    }

    function showToast(msg, type) {
        type = type || 'success';
        var $t = $('<div class="orch-setup-toast orch-setup-toast--' + type + '">' + msg + '</div>');
        $t.css({position:'fixed', top:'24px', right:'24px', padding:'14px 18px', background: type==='error' ? '#fee2e2' : '#dcfce7', color: type==='error' ? '#991b1b' : '#065f46', border:'1px solid ' + (type==='error' ? '#fca5a5' : '#86efac'), borderRadius:'8px', boxShadow:'0 4px 16px rgba(0,0,0,0.1)', zIndex: 99999, fontWeight:600, fontSize:'13px'});
        $('body').append($t);
        setTimeout(function(){ $t.fadeOut(300, function(){ $(this).remove(); }); }, 3000);
    }

    // Persona pick
    $(document).on('click', '.orch-setup-persona-btn', function(){
        var persona = $(this).data('persona');
        var $btn = $(this);
        $btn.attr('data-selected', '1').siblings().removeAttr('data-selected');
        ajax('seo_aeo_setup_set_persona', {persona_type: persona}).done(function(resp){
            if (resp && resp.success) {
                $('#orch-setup-persona-card').slideUp(180);
                showToast(SG._t ? SG._t('Persona impostata.') : 'Persona impostata.');
            }
        });
    });

    // Skip / unskip
    $(document).on('click', '.orch-setup-step-skip', function(){
        var sid = $(this).data('step-id');
        ajax('seo_aeo_setup_update_step', {step_id: sid, status: 'skipped'}).done(function(){
            window.location.reload();
        });
    });
    $(document).on('click', '.orch-setup-step-unskip', function(){
        var sid = $(this).data('step-id');
        ajax('seo_aeo_setup_update_step', {step_id: sid, status: 'todo'}).done(function(){
            window.location.reload();
        });
    });

    // Inline perimeter form
    $(document).on('click', '.orch-setup-step-inline-open', function(){
        var sid = $(this).data('step-id');
        if (sid === 'perimeter') {
            $('#orch-setup-perimeter-form').slideToggle(180);
        }
    });

    $(document).on('click', '.orch-setup-perimeter-cancel', function(){
        $('#orch-setup-perimeter-form').slideUp(180);
    });

    // 3.38.1 Task 4 — first-run "Inizia il setup" → scroll to persona pick or step 1.
    $(document).on('click', '#orch-setup-firstrun-start', function(){
        var target = document.getElementById('orch-setup-persona-card') || document.getElementById('orch-setup-steps');
        if (target) {
            target.scrollIntoView({behavior:'smooth', block:'start'});
        }
    });

    // 3.38.1 Task 1 — Mark step as TODO (resetta status, dati restano in DB).
    $(document).on('click', '.orch-setup-step-redo', function(){
        var sid = $(this).data('step-id');
        var stepName = $(this).closest('.orch-setup-step').find('.orch-setup-step-title').text().trim();
        if (!confirm('Marcare "' + stepName + '" come da rifare? Lo step torna a TODO ma i dati sottostanti restano salvati.')) return;
        $.post(SG.ajaxUrl, {action: 'seo_aeo_setup_update_step', nonce: SG.nonce, step_id: sid, status: 'todo'}).done(function(){
            window.location.reload();
        });
    });

    $(document).on('click', '.orch-setup-perimeter-save', function(){
        var $form = $('#orch-setup-perimeter-form');
        var data = {
            industry:  ($form.find('[name=industry]').val() || '').trim().substring(0, 80),
            site_type: $form.find('[name=site_type]').val() || '',
            language:  $form.find('[name=language]').val() || 'it',
            markets:   ($form.find('[name=markets]').val() || '').trim().substring(0, 200),
        };
        if (!data.industry || !data.site_type) {
            showToast('Settore e tipo sito sono obbligatori.', 'error');
            return;
        }
        ajax('seo_aeo_setup_update_step', {step_id: 'perimeter', status: 'done', data: JSON.stringify(data)}).done(function(resp){
            if (resp && resp.success) {
                showToast('✓ Perimetro salvato!');
                setTimeout(function(){ window.location.reload(); }, 800);
            } else {
                showToast('Errore salvataggio. Riprova.', 'error');
            }
        });
    });
})(jQuery);
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>

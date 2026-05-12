<?php
// phpcs:disable WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.InputNotValidated
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com. All rights reserved.
 * Unauthorized copying, redistribution or resale is strictly prohibited.
 */
/**
 * AEO Orchestra - Admin UI
 * Admin menu, scripts, meta boxes, and page rendering.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Orchestra_Admin_UI {

    private $main;

    public function __construct($main = null) {
        try {
            $this->main = $main;
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_menu', array($this, 'inject_menu_separators'), 999);
            add_action('admin_head', array($this, 'output_menu_styles'));
            add_action('admin_init', array($this, 'register_settings'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
            add_action('add_meta_boxes', array($this, 'add_seo_meta_box'));
            add_action('save_post', array($this, 'save_seo_meta'));
            add_action('wp_head', array($this, 'output_meta_tags'), 1);
        } catch (Throwable $e) {
        }
    }

    public function add_admin_menu() {
        // 3.35.65: Riorganizzazione menu in 5 macro-aree con $position per ordering esplicito.
        add_menu_page(
            'AEO Orchestra',
            'AEO Orchestra',
            'manage_options',
            'seo-aeo-orchestra',
            array($this, 'render_wizard_page'),
            'dashicons-chart-area',
            30
        );

        $tt = function($s) { return class_exists('SEO_AEO_T') ? SEO_AEO_T::t($s) : $s; };

        // ─── Top of menu ───
        add_submenu_page('seo-aeo-orchestra', $tt('Dashboard'),               $tt('📊 Dashboard'),               'manage_options', 'seo-aeo-orchestra',           array($this, 'render_wizard_page'),         10);
        // 3.38.0 — Onboarding 3.0 Setup Guidato.
        add_submenu_page('seo-aeo-orchestra', $tt('Setup Guidato'),          $tt('🎯 Setup Guidato'),          'manage_options', 'seo-aeo-setup-guidato',       array($this, 'render_setup_guidato_page'), 12);
        // 3.35.83: Business Profile — foundation feature (single source of context per tutti i tool AI)
        add_submenu_page('seo-aeo-orchestra', $tt('Profilo Business'),       $tt('🏢 Profilo Business'),        'manage_options', 'seo-aeo-business-profile',    array('SEO_AEO_Business_Profile', 'render_page'), 15);
        // SEO + AEO Output (3.35.65: rename da "SEO Output Nativo", slug invariato per backward compat)
        add_submenu_page('seo-aeo-orchestra', $tt('SEO + AEO Output'),        $tt('⚡ SEO + AEO Output'),        'manage_options', 'seo-aeo-native-output',       array($this, 'render_native_output_page'),  20);

        // 3.35.85.0 (WP.org compliance): every submenu registers unconditionally.
        // Premium operations are gated server-side by license_key, not by hiding UI.
        // The standalone "Pro Features" upsell submenu has been removed; the same
        // CTA now lives inside Settings → Account & Plan (admin-facing per Guideline 11).

        // ─── ANALISI ───
        add_submenu_page('seo-aeo-orchestra', $tt('Analisi SEO'),             $tt('🔍 Analisi SEO'),             'manage_options', 'seo-aeo-analyze',             array($this, 'render_analyze_page'),         40);
        add_submenu_page('seo-aeo-orchestra', $tt('Analisi AEO'),             $tt('🧠 Analisi AEO'),             'manage_options', 'seo-aeo-aeo-analysis',        array($this, 'render_aeo_analysis_page'),    50);
        add_submenu_page('seo-aeo-orchestra', $tt('Cannibalizzazione SEO'),   $tt('🌳 Cannibalizzazione SEO'),   'manage_options', 'seo-aeo-cannibalization',     array($this, 'render_cannibalization_page'), 60);
        add_submenu_page('seo-aeo-orchestra', $tt('Analytics'),               $tt('📈 Analytics'),               'manage_options', 'seo-aeo-orchestra-analytics', array($this, 'render_analytics_page'),       70);

        // ─── CREAZIONE ───
        add_submenu_page('seo-aeo-orchestra', $tt('Orchestratore'),           $tt('🎯 Orchestratore'),           'manage_options', 'seo-aeo-orchestratore',       array($this, 'render_admin_page'),           38);
        add_submenu_page('seo-aeo-orchestra', $tt('Brand Voice'),             $tt('🎙️ Brand Voice'),             'manage_options', 'seo-aeo-brand-voice',         array($this, 'render_brand_voice_page'),     110);
        add_submenu_page('seo-aeo-orchestra', $tt('Contenuti AEO'),           $tt('💬 Contenuti AEO'),           'manage_options', 'seo-aeo-aeo-content',         array($this, 'render_aeo_content_page'),     120);
        add_submenu_page('seo-aeo-orchestra', $tt('Contenuti AI'),            $tt('✨ Contenuti AI'),            'manage_options', 'seo-aeo-content',             array($this, 'render_content_page'),         130);
        add_submenu_page('seo-aeo-orchestra', $tt('Keyword Research'),        $tt('🎯 Keyword Research'),        'manage_options', 'seo-aeo-keyword-research',    array($this, 'render_keyword_research_page'),65);
        add_submenu_page('seo-aeo-orchestra', $tt('Meta Tags'),               $tt('🏷️ Meta Tags'),               'manage_options', 'seo-aeo-meta',                array($this, 'render_meta_page'),            150);
        add_submenu_page('seo-aeo-orchestra', $tt('Immagini SEO'),            $tt('🖼 Immagini SEO'),             'manage_options', 'seo-aeo-orchestra-images',    array($this, 'render_images_page'),          160);
        add_submenu_page('seo-aeo-orchestra', $tt('Local SEO'),               $tt('📍 Local SEO'),               'manage_options', 'seo-aeo-local',               array($this, 'render_local_page'),           170);
        // 3.35.65: rename "Calendario contenuti AI" → "Pianificazione articoli" (slug invariato)
        add_submenu_page('seo-aeo-orchestra', $tt('Pianificazione articoli'), $tt('📅 Pianificazione articoli'), 'manage_options', 'seo-aeo-orchestra-calendar',  array($this, 'render_calendar_page'),        180);
        add_submenu_page('seo-aeo-orchestra', $tt('Auto-Pilot'),              $tt('🤖 Auto-Pilot'),              'manage_options', 'seo-aeo-autopilot',           array($this, 'render_autopilot_page'),       190);

        // ─── OPERATIONS ───
        add_submenu_page('seo-aeo-orchestra', $tt('Redirect Manager'),        $tt('🔀 Redirect Manager'),        'manage_options', 'seo-aeo-redirect',            array($this, 'render_redirect_page'),        200);
        add_submenu_page('seo-aeo-orchestra', $tt('Migrazione SEO'),          $tt('🚀 Migrazione SEO'),          'manage_options', 'seo-aeo-migration-wizard',    array($this, 'render_migration_wizard_page'),210);

        // 3.35.80: AI Crawlers (OPERATIONS macro-area)
        add_submenu_page('seo-aeo-orchestra', $tt('AI Crawlers'),            $tt('🛡️ AI Crawlers'),            'manage_options', 'seo-aeo-ai-crawlers',         array($this, 'render_ai_crawlers_page'),     220);

        // ─── ACCOUNT ───
        add_submenu_page('seo-aeo-orchestra', $tt('Consumo Crediti'),         $tt('💳 Consumo Crediti'),         'manage_options', 'seo-aeo-usage',               array($this, 'render_usage_page'),           300);
        add_submenu_page('seo-aeo-orchestra', $tt('Impostazioni'),            $tt('⚙️ Impostazioni'),            'manage_options', 'seo-aeo-settings',            array($this, 'render_settings_page'),        310);
    }

    /**
     * 3.35.65: Inietta separator categoriali nel global $submenu.
     * Eseguito a admin_menu priority 999 (dopo tutti gli add_submenu_page).
     */
    public function inject_menu_separators() {
        global $submenu;
        if (!isset($submenu['seo-aeo-orchestra']) || !is_array($submenu['seo-aeo-orchestra'])) return;

        $tt = function($s) { return class_exists('SEO_AEO_T') ? SEO_AEO_T::t($s) : $s; };

        // 3.35.67.1: sequence-based menu rebuild. WP $position parameter on add_submenu_page
        // is unreliable across WP versions / themes, so instead we declare the explicit
        // ORDER here and rebuild $submenu by walking this sequence. Slugs not present in
        // the original $submenu (e.g. free-build SKUs) are simply skipped.
        $sequence = array(
            'seo-aeo-orchestra',              // Dashboard
            'seo-aeo-native-output',          // SEO + AEO Output
            '__SEP__:📊 ANALISI',
            'seo-aeo-orchestratore',          // Orchestratore (was 100→38, now in ANALISI)
            'seo-aeo-analyze',
            'seo-aeo-aeo-analysis',
            'seo-aeo-cannibalization',
            'seo-aeo-keyword-research',       // Keyword Research (was 140→65, now in ANALISI)
            'seo-aeo-orchestra-analytics',
            '__SEP__:✨ CREAZIONE',
            'seo-aeo-brand-voice',
            'seo-aeo-aeo-content',
            'seo-aeo-content',
            'seo-aeo-meta',
            'seo-aeo-orchestra-images',
            'seo-aeo-local',
            '__SEP__:🔧 OPERATIONS',
            'seo-aeo-orchestra-calendar',     // Pianificazione articoli (now in OPERATIONS)
            'seo-aeo-autopilot',
            'seo-aeo-redirect',
            'seo-aeo-migration-wizard',
            'seo-aeo-ai-crawlers',
            '__SEP__:⚙️ ACCOUNT',
            'seo-aeo-pro',                    // Pro Features (free build only)
            'seo-aeo-usage',
            'seo-aeo-settings',
        );

        // Build slug -> item lookup from current $submenu
        $by_slug = array();
        foreach ($submenu['seo-aeo-orchestra'] as $item) {
            if (isset($item[2])) $by_slug[(string) $item[2]] = $item;
        }

        $new = array();
        foreach ($sequence as $slug) {
            if (strpos($slug, '__SEP__:') === 0) {
                $label = $tt('─── ' . substr($slug, 8) . ' ───');
                $new[] = array(
                    $label,
                    'read',
                    '#orch-sep-' . substr(md5($slug), 0, 8),
                );
                continue;
            }
            if (isset($by_slug[$slug])) {
                $new[] = $by_slug[$slug];
                unset($by_slug[$slug]);
            }
        }
        // Append any remaining items (e.g. items added by other plugins or hooks)
        // so we never silently drop registered submenus
        foreach ($by_slug as $remaining_slug => $item) {
            $new[] = $item;
        }
        $submenu['seo-aeo-orchestra'] = $new;
    }

    /**
     * 3.35.65: Inline CSS+JS for menu separators + nowrap labels.
     * Hooked on admin_head -- runs on every admin page.
     */
    public function output_menu_styles() {
        ?>
        <style id="orch-menu-styles">
        /* Macro-area separators (non-clickable headers) */
        #adminmenu .wp-submenu li a[href*="#orch-sep-"] {
            pointer-events: none !important;
            cursor: default !important;
            opacity: 0.55;
            font-size: 11px !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding-top: 12px !important;
            padding-bottom: 4px !important;
            color: #c3c4c7 !important;
            font-weight: 600;
        }
        #adminmenu .wp-submenu li a[href*="#orch-sep-"]:hover {
            color: #c3c4c7 !important;
            background: transparent !important;
        }
        /* Nowrap labels su tutto il submenu Orchestra */
        #adminmenu .toplevel_page_seo-aeo-orchestra .wp-submenu li a {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 220px;
        }
        </style>
        <?php ob_start(); ?>
// 3.35.65: prevent default navigation on separator clicks (defensive)
        document.addEventListener("DOMContentLoaded", function() {
            var seps = document.querySelectorAll('#adminmenu .wp-submenu li a[href*="#orch-sep-"]');
            for (var i = 0; i < seps.length; i++) {
                seps[i].addEventListener("click", function(e) { e.preventDefault(); return false; });
                seps[i].setAttribute("tabindex", "-1");
                seps[i].setAttribute("aria-hidden", "true");
            }
        });
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>
        <?php
    }

    /**
     * 3.35.80: AI Crawlers section render — delegates to SEO_AEO_AI_Crawler_Admin.
     */
    public function render_ai_crawlers_page() {
        if (class_exists('SEO_AEO_AI_Crawler_Admin')) {
            SEO_AEO_AI_Crawler_Admin::render_page();
        } else {
            echo '<div class="wrap"><h1>AI Crawlers</h1><p>Class missing.</p></div>';
        }
    }

    /**
     * Pagina dedicata "SEO Output Nativo" (3.17.0): le feature "Switch da Yoast"
     * raccolte in una landing pulita con focus commerciale.
     */
    public function render_native_output_page() {
        $template = SEO_AEO_DIR . 'templates/native-output.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>SEO Output Nativo</h1><p>Template mancante.</p></div>';
        }
    }

    public function render_redirect_page() {
        $template = SEO_AEO_DIR . 'templates/redirect-manager.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>Redirect Manager</h1><p>Template mancante.</p></div>';
        }
    }

    public function render_pro_features_page() {
        $template = SEO_AEO_DIR . 'templates/pro-features.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>Pro Features</h1><p>Template mancante.</p></div>';
        }
    }

    public function render_brand_voice_page() {
        $template = SEO_AEO_DIR . 'templates/brand-voice.php';
        if (file_exists($template)) include $template;
        else echo '<div class="wrap"><h1>Brand Voice</h1><p>Template mancante.</p></div>';
    }

    /**
     * Pagina dedicata "Cannibalizzazione SEO" (3.28.0): estratta dall'Orchestratore
     * per declutter. Riusa il partial templates/partials/cannibalization-section.php.
     */
    public function render_cannibalization_page() {
        $template = SEO_AEO_DIR . 'templates/cannibalization.php';
        if (file_exists($template)) include $template;
        else echo '<div class="wrap"><h1>Cannibalizzazione SEO</h1><p>Template mancante.</p></div>';
    }

    public function render_wizard_page() {
        $template = SEO_AEO_DIR . 'templates/wizard-home.php';
        if (file_exists($template)) include $template;
        else echo '<div class="wrap"><h1>Wizard</h1><p>Template mancante.</p></div>';
    }

    public function render_setup_guidato_page() {
        $template = SEO_AEO_DIR . 'templates/setup-guidato.php';
        if (file_exists($template)) include $template;
        else echo '<div class="wrap"><h1>Setup Guidato</h1><p>Template mancante.</p></div>';
    }

    public function render_keyword_research_page() {
        $template = SEO_AEO_DIR . 'templates/keyword-research.php';
        if (file_exists($template)) include $template;
        else echo '<div class="wrap"><h1>Keyword Research</h1><p>Template mancante.</p></div>';
    }

    public function render_autopilot_page() {
        $template = SEO_AEO_DIR . 'templates/autopilot.php';
        if (file_exists($template)) include $template;
        else echo '<div class="wrap"><h1>Auto-Pilot</h1><p>Template mancante.</p></div>';
    }

    public function render_migration_wizard_page() {
        $template = SEO_AEO_DIR . 'templates/migration-wizard.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>Migration Wizard</h1><p>Template mancante.</p></div>';
        }
    }

    /**
     * Pagina dedicata "Calendario contenuti AI" (3.33.0): grid mese-view per
     * pianificare articoli con auto-publish opzionale.
     */
    public function render_calendar_page() {
        try {
            $seo_aeo_dir = defined('SEO_AEO_PLUGIN_DIR') ? SEO_AEO_PLUGIN_DIR : plugin_dir_path(dirname(__FILE__));
            $seo_aeo_tpl = $seo_aeo_dir . 'templates/calendar.php';
            if (!file_exists($seo_aeo_tpl)) { echo '<div class="wrap"><p>Template non trovato.</p></div>'; return; }
            include $seo_aeo_tpl;
        } catch (Throwable $e) {
            echo '<div class="wrap"><div class="notice notice-error"><p>Errore: ' . esc_html($e->getMessage()) . '</p></div></div>';
        }
    }

    /**
     * Pagina dedicata "Image SEO Manager" (3.34.0): audit + bulk fix metadata
     * (alt/title/caption/description) per tutte le immagini del sito via Gemini Vision.
     */
    public function render_images_page() {
        try {
            $seo_aeo_dir = defined('SEO_AEO_PLUGIN_DIR') ? SEO_AEO_PLUGIN_DIR : plugin_dir_path(dirname(__FILE__));
            $seo_aeo_tpl = $seo_aeo_dir . 'templates/images-seo.php';
            if (!file_exists($seo_aeo_tpl)) { echo '<div class="wrap"><p>Template non trovato.</p></div>'; return; }
            include $seo_aeo_tpl;
        } catch (Throwable $e) {
            echo '<div class="wrap"><p>Errore caricamento Image SEO Manager. Riprova.</p></div>';
        }
    }

    /**
     * Pagina dedicata "Analytics" (3.32.0): GSC insights + Orchestra KPI
     * differenziatori. Replica leggera ispirata al pattern Soro /analytics ma
     * estesa con i 5 KPI esclusivi Orchestra (AI articles, Brand Voice impact,
     * Redirect rescues, Auto-Pilot ROI, Meta freshness).
     */
    public function render_analytics_page() {
        try {
            $seo_aeo_dir = defined('SEO_AEO_PLUGIN_DIR') ? SEO_AEO_PLUGIN_DIR : plugin_dir_path(dirname(__FILE__));
            $seo_aeo_tpl = $seo_aeo_dir . 'templates/analytics.php';
            if (!file_exists($seo_aeo_tpl)) { echo '<div class="wrap"><p>Template non trovato.</p></div>'; return; }
            include $seo_aeo_tpl;
        } catch (Throwable $e) {
            echo '<div class="wrap"><div class="notice notice-error"><p>Errore: ' . esc_html($e->getMessage()) . '</p></div></div>';
        }
    }

    public function register_settings() {
        register_setting('seo_aeo_orchestra_settings', 'seo_aeo_orchestra_license_key', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('seo_aeo_orchestra_settings', 'seo_aeo_orchestra_auto_meta', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('seo_aeo_orchestra_settings', 'seo_aeo_orchestra_language', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('seo_aeo_orchestra_settings', 'seo_aeo_orchestra_widget_enabled', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('seo_aeo_orchestra_settings', 'seo_aeo_orchestra_widget_visibility', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('seo_aeo_orchestra_settings', 'seo_aeo_orchestra_widget_branding', array('sanitize_callback' => 'sanitize_text_field'));
        // 3.33.0 — Content Calendar defaults
        register_setting('seo_aeo_orchestra_settings', 'seo_aeo_orchestra_calendar_default_days_before', array('sanitize_callback' => 'absint'));
        register_setting('seo_aeo_orchestra_settings', 'seo_aeo_orchestra_calendar_default_hour', array('sanitize_callback' => 'absint'));
        register_setting('seo_aeo_orchestra_settings', 'seo_aeo_orchestra_calendar_default_auto_publish', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('seo_aeo_orchestra_settings', 'seo_aeo_orchestra_calendar_default_category', array('sanitize_callback' => 'absint'));
    }

    public function enqueue_admin_scripts($hook) {
        try {
            if (!is_admin()) return;

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- read-only menu routing
            $page = isset($_GET['page']) ? $_GET['page'] : '';

            if (strpos($hook, 'seo-aeo') === false && $hook !== 'post.php' && $hook !== 'post-new.php') {
                return;
            }


            $plugin_url = defined('SEO_AEO_PLUGIN_URL') ? SEO_AEO_PLUGIN_URL : plugins_url('/', dirname(__FILE__));
            $plugin_dir = defined('SEO_AEO_PLUGIN_DIR') ? SEO_AEO_PLUGIN_DIR : plugin_dir_path(dirname(__FILE__));
            $version = defined('SEO_AEO_VERSION') ? SEO_AEO_VERSION : '2.0.0';

            $css_path = $plugin_dir . 'assets/css/admin.css';
            $js_path  = $plugin_dir . 'assets/js/admin.js';

            if (file_exists($css_path)) {
                wp_enqueue_style('seo-aeo-admin', $plugin_url . 'assets/css/admin.css', array(), $version);
            }

            $js_enqueued = false;
            if (file_exists($js_path)) {
                wp_enqueue_script('seo-aeo-admin', $plugin_url . 'assets/js/admin.js', array('jquery'), $version, true);

            // 3.35.81: Verify-Live UI streaming (only on SEO + AEO Output page)
            if (isset($_GET['page']) && $_GET['page'] === 'seo-aeo-native-output') {
                if (file_exists($plugin_dir . 'assets/css/verify-live.css')) {
                    wp_enqueue_style('seo-aeo-verify-live', $plugin_url . 'assets/css/verify-live.css', array(), $version);
                }
                if (file_exists($plugin_dir . 'assets/js/verify-live-streaming.js')) {
                    wp_enqueue_script('seo-aeo-verify-live', $plugin_url . 'assets/js/verify-live-streaming.js', array('jquery'), $version, true);
                }
            }
                $js_enqueued = true;
            }


            if ($js_enqueued) {
                $license_key = '';
                $license_type = 'starter';
                if (isset($this->main) && is_object($this->main)) {
                    $license_key  = isset($this->main->license_key) ? $this->main->license_key : '';
                    $license_type = isset($this->main->license_type) ? $this->main->license_type : 'starter';
                }

                $api_url = defined('SEO_AEO_API_URL') ? SEO_AEO_API_URL : 'https://aeo-orchestra.com';

                // i18n strings exposed to JS (3.25.1): solo se locale=en, altrimenti map vuota
                $i18n_js = array();
                if (class_exists('SEO_AEO_T') && SEO_AEO_T::current_locale() !== 'it') {
                    $js_strings = array(
                        'Errore', 'Errore: ', 'Caricamento...', 'Generazione in corso...',
                        'Procedere?', 'Operazione irreversibile.', 'Eliminare il job? Operazione irreversibile (i post creati restano).',
                        'Esegue 1 articolo subito (consuma ~25 crediti). Procedere?',
                        'Inserisci una nicchia di almeno 3 caratteri.',
                        'Inserisci almeno 3 caratteri per ottenere suggerimenti.',
                        'Inserisci una descrizione per l\'immagine',
                        'Ricerca keyword AI: 15 crediti. Procedere?',
                        'Analisi: 10 crediti. Procedere?',
                        'Profilo creato! Attivalo per usarlo.',
                        'Contenuto generato!', 'Contenuto AEO generato!', 'Pagina Local SEO generata!',
                        'Errore di rete', 'Errore di connessione o timeout',
                        'Inserisci un nome per il job.',
                        'Seleziona almeno 1 keyword dal Keyword Picker.',
                        'Nessuna keyword generata. Riprova con una nicchia diversa.',
                        'Nessun suggerimento trovato. Prova con un termine diverso.',
                        'Pesca da Keyword Research', 'Pesca keyword da Research', 'Pesca servizio da Keyword Research', 'Pesca keyword per il job',
                        'Nessun set ancora', 'Set di keyword salvati', 'Pesca dalle ricerche fatte',
                        'Seleziona tutte', 'Solo facili', 'Deseleziona', 'Usa selezionate',
                        'keyword selezionate', 'Salva', 'Annulla', 'Elimina', 'Modifica', 'Genera',
                        'Pubblica', 'Bozza', 'Anteprima', 'Copia', 'crediti',
                        'Italiano', 'English',
                        // 3.25.2 extension
                        'Riesecuzione non disponibile per questo tipo.',
                        'Seleziona una pagina e inserisci la keyword',
                        'Errore di connessione',
                        'Meta tags generati con successo!',
                        'Meta tags generati e salvati!',
                        'Seleziona un post e inserisci una keyword',
                        'Seleziona almeno una pagina nella lista bulk.',
                        'Inserisci un argomento',
                        'Inserisci servizio e citta',
                        'HTML copiato negli appunti', 'Keyword copiata: ',
                        'Item cronologia non trovato',
                        'Questo elemento non è ripristinabile (creato con vecchia versione)',
                        'Errore nel ripristino', 'Errore di rete nel ripristino',
                        'Profilo creato!',
                        'Cronologia', 'Tutti', 'Analisi', 'Contenuto',
                        'Senza titolo', 'parole', 'Riapri', 'Riesegui analisi',
                        'Pubblica come Bozza', 'Pubblica Bozza con Meta',
                        'Aggiungi Foto', 'Foto da Media', 'Genera Foto AI',
                        'Articolo Completo AI', 'Genera Articolo', 'Bozza Veloce',
                        'Suggerimenti', 'Articolo', 'Cancella cronologia',
                        'Errore di rete o timeout',
                        'Eliminare il profilo? Operazione irreversibile.',
                        'Pubblica Bozza', 'Pubblica Subito',
                        'Contenuto generato!',
                        // 3.25.10 Orchestrator render
                        'sconosciuto', 'Connessione fallita',
                        'Analisi completata! Generazione report...', 'Completato in',
                        'Nessuna azione critica necessaria. Il sito e ben ottimizzato!',
                        'Esegui', 'Clicca per dettagli', 'Meta OK', 'Meta mancanti',
                        'Dettaglio SEO', 'Dettaglio AEO', 'Suggerimenti:', 'Miglioramenti AEO:',
                        // Cannibal + GSC inline JS (3.25.10)
                        'pagine scansionate', 'con focus keyword', 'senza focus keyword',
                        'conflitti rilevati', 'tasso cannibalizzazione',
                        'Nessuna cannibalizzazione rilevata.',
                        'Ogni focus keyword è univoca tra le pagine pubblicate.',
                        'pagine', 'Cosa fare:',
                        'consolida il contenuto migliore su una sola pagina e re-targetizza le altre con keyword correlate ma distinte (long-tail, varianti semantiche). In alternativa, usa',
                        'verso la pagina principale.',
                        'Genera proposta AI', 'Nessuna modifica al sito.',
                        'Genera solo una proposta da rivedere: l\'AI sceglie la pagina primaria, suggerisce keyword alternative per le altre e propone link interni. Decidi tu cosa applicare.',
                        'Dati gruppo non validi.', 'Analisi AI in corso…',
                        'L\'AI sta valutando le pagine…', 'Analisi AI fallita.',
                        'Errore rete', 'Pagina primaria', 'Mantiene la keyword',
                        'Link interno suggerito', 'Anchor:', 'Dove inserirlo:',
                        'Punta a Post #', 'Applica con un click',
                        'Sicurezza: viene creato uno snapshot per ripristino. Niente è irreversibile.',
                        'Supporting', 'Modifica nel CMS', 'Nuova focus keyword:',
                        'Scansione…', 'Lettura focus keywords da tutte le pagine pubblicate…',
                        'Errore sconosciuto',
                        'Errore stato GSC',
                        'Integrazione GSC non ancora attiva sul server.',
                        'Il provider del plugin sta ultimando la configurazione OAuth con Google. Riprova fra qualche giorno o contatta il supporto.',
                        'Modalità admin centralizzata:',
                        'connettendoti qui abiliti GSC per tutti i clienti del team. Una sola autorizzazione necessaria.',
                        'Collega Search Console',
                        'per vedere quali pagine ricevono impressioni e clic da Google, e priorizzare le ottimizzazioni dove c\'è già traffico.',
                        'L\'autorizzazione è in sola lettura (scope',
                        'Connetti GSC',
                        'Search Console gestito centralmente dal team Orchestra.',
                        'L\'amministratore non ha ancora attivato la connessione, oppure il tuo dominio non risulta tra le property GSC autorizzate. Contatta il supporto per attivare gli insights di Search Console su questo sito.',
                        'Disconnetti', 'GSC attivo · gestito dal team Orchestra',
                        'Connesso come', 'dal', 'Carico siti GSC…', 'Lista siti fallita',
                        'Il tuo dominio non risulta tra le property GSC del team Orchestra. Aggiungi l\'email amministratore Orchestra come Utente o Proprietario nella tua property Search Console, poi ricarica la dashboard.',
                        'Nessun sito verificato in GSC con questo account.',
                        'Sito GSC:', 'Periodo:', 'Ultimi 7gg', 'Ultimi 30gg', 'Ultimi 90gg',
                        'Ordina per:', 'Carica top pagine', 'Carico top pagine…',
                        'Top pagine fallito', 'Nessun dato GSC nel periodo selezionato.',
                        'Cache fino a 1h', 'Ultimo fetch:',
                        'Posizione media',
                        'Apro Google…', 'auth_url non disponibile',
                        'Disconnettere Search Console? Per riconnettere dovrai autorizzare di nuovo.',
                        'Disconnetto…',
                        'Mostra', 'Nascondi',
                        // Schema validator (3.25.11)
                        'Schema markup (Rich Results)', 'Schema markup',
                        'Analisi in corso…', 'Rete',
                        'Nessun JSON-LD rilevato', 'warning', 'errori', 'non validati',
                        'Schema consigliati mancanti:', 'Disponibile nella prossima sessione',
                        'Genera schema mancante (presto)',
                        'Verifica schema dopo le modifiche…', 'Verifica schema fallita:',
                        'errore sconosciuto', 'rete',
                        'Nessuna variazione (Orchestra non ha modificato lo schema markup di questa pagina)',
                        'Migliorato', 'Peggiorato', 'Variazione neutra',
                        'Impatto sullo schema:', 'Warning', 'Errori', 'Non validati',
                        'Applico…',
                        'Generare meta tags per', 'Costo stimato:', 'per pagina',
                        'Keyword condivisa:', 'Keyword: titolo della pagina come fallback.',
                        // 3.26.1
                        'Genera Articolo', 'Articolo Completo AI', 'Bozza Veloce',
                        'Genera solo il testo dell\'articolo (~8 crediti). Veloce. Niente immagini, niente meta tags automatici.',
                        'Genera testo + immagine AI generata + meta tags ottimizzati (~25 crediti). Pronto da pubblicare.',
                        'Aggiorna al piano Professional per sbloccare Articolo Completo AI (testo + immagine + meta)',
                        'per testo', 'per completo', 'solo testo, veloce.',
                        'testo + immagine AI + meta tags, pronto per pubblicazione.',
                        'Genera Immagine con AI',
                        'Prompt derivato automaticamente dal tuo articolo. Modificalo se vuoi.',
                        'Descrivi l\'immagine che vuoi generare. L\'AI creera un\'immagine unica e la inserira nel tuo articolo.',
                        'Le proposte verranno mostrate per la revisione PRIMA di essere salvate.',
                        'Generazione bulk in corso...',
                        'Genero le proposte per ogni pagina. Le potrai modificare PRIMA di salvarle.',
                        'Seleziona/Deseleziona tutti', 'Meta Title proposto', 'Meta Description proposta',
                        'Stato', 'Salva le selezionate', 'Annulla / Scarta proposte',
                        'Generazione…', 'Pronto', 'Generazione completata:',
                        'Rivedi e modifica le proposte, poi clicca Salva.',
                        'Generazione in corso...', 'Salva le',
                        'Scartare tutte le proposte? Le modifiche non saranno salvate.',
                        'Salvataggio...', 'Salvati', 'falliti', 'Bulk salvato:',
                        'Genera per Selezionati', 'Salvato',
                        'Rigenera questa pagina come Articolo Completo: testo + immagine AI + meta tags. ~25 crediti.',
                        'Cancella tutta la cronologia di questa sezione',
                        'Cancellare tutta la cronologia di questa sezione? Operazione irreversibile.',
                        '✓ Cronologia svuotata',
                        // 3.27.2 — Orchestrator dynamic strings (admin-dashboard inline JS + admin.js)
                        '{N} selezionate', 'Seleziona almeno una pagina', 'Analizza {N} pagine',
                        'Nessuna delle pagine dell\'analisi passata è più disponibile.',
                        '✓ Tutto fatto', 'Ottimo lavoro!', 'Hai completato tutte le azioni prioritarie.',
                        '{N} da fare',
                        'Azioni prioritarie dall\'ultima analisi. Marca come fatto ciò che hai completato.',
                        'Su:', 'Mostra altri', 'Prossimi passi per il tuo sito',
                        'Nessuna modifica ancora applicata.',
                        'Scaduto', '1 giorno rimasto', '{N} giorni rimasti',
                        'Dettagli', 'Ripristina', 'Errore caricamento.',
                        '(vuoto)', 'Prima', 'Dopo',
                        'Dettagli modifica', 'Caricamento dettagli...',
                        'Nessuna modifica registrata in questo snapshot.',
                        'Modifica del', 'Pagina:', 'ID proposta:', 'Errore rete',
                        'Nessun testo da copiare', 'Copiato',
                        'Testo copiato negli appunti',
                        'Impossibile copiare. Seleziona e copia manualmente.',
                        'Cancellazione...', 'Cancella', 'Errore:',
                        '{N} analisi cancellate', 'Cronologia svuotata',
                        'Ora', '{N} m fa', '{N} h fa', '{N} g fa',
                        '"{TITLE}" ha il punteggio SEO piu basso del sito. Ottimizzala per un impatto immediato sul posizionamento!',
                        'Critico', 'Da migliorare', 'Buono',
                        'Titolo: {N}/60 caratteri', 'Descrizione: {N}/160 caratteri',
                        'Stima: ~{TOTAL} crediti ({COUNT} pagine × {PERPAGE} crediti/pag)',
                        'Ripristina nel modulo', 'Riapri', 'Riesegui analisi',
                        'Dettaglio pagine:',
                        'Seleziona almeno una pagina da analizzare.',
                        'Analisi annullata dall\'utente.',
                        '{N} pagine analizzate su {TOT}', 'Analizzando:',
                        '{N} pagine analizzate',
                        'Analisi completata! {N} pagine analizzate, {A} azioni suggerite.',
                        'Senza titolo', 'Nessun dato',
                        'SEO Medio', 'AEO Medio', 'Pagine', 'Problemi', 'Azioni',
                        'Problemi SEO', 'Problemi AEO', 'Azioni Suggerite',
                        'Cosa fara:',
                        'Generera automaticamente Meta Title e Meta Description ottimizzati per la keyword "{KW}". Questi meta tag verranno salvati direttamente nel post WordPress per migliorare il CTR nei risultati di ricerca.',
                        'Rianalizzera la pagina e identifichera problemi specifici come: struttura heading H1-H6, densita keyword, internal linking, alt text immagini, lunghezza contenuto. Fornira suggerimenti attuabili per migliorare il punteggio SEO.',
                        'Generera contenuto ottimizzato per le risposte AI (Google AI Overviews, ChatGPT, Perplexity). Riscrivera i paragrafi in formato domanda-risposta, aggiungera Schema.org FAQ e Article markup, migliorera la citabilita del contenuto.',
                        'Rigenerera il contenuto della pagina con ottimizzazione SEO + AEO. Il nuovo articolo avra: struttura H2/H3 corretta, keyword integrate naturalmente, sezione FAQ, formato ottimizzato per Featured Snippet e risposte AI.',
                        'critica', 'alta', 'media', 'bassa',
                    );
                    foreach ($js_strings as $s) {
                        $tr = SEO_AEO_T::t($s);
                        if ($tr !== $s) $i18n_js[$s] = $tr;
                    }
                }

                wp_localize_script('seo-aeo-admin', 'seoAeoOrchestra', array(
                    'ajaxUrl'      => admin_url('admin-ajax.php'),
                    'nonce'        => wp_create_nonce('seo_aeo_orchestra_nonce'),
                    'licenseKey'   => $license_key,
                    'apiUrl'       => $api_url,
                    'licenseType'  => $license_type,
                    'creditCosts'  => array(),
                    'dashboardUrl' => rtrim(str_replace('/api', '', $api_url), '/') . '/dashboard',
                    'firstUse'     => get_option('seo_aeo_orchestra_first_use_done', '0') === '0' ? '1' : '0',
                    'locale'       => class_exists('SEO_AEO_T') ? SEO_AEO_T::current_locale() : 'it',
                    'i18n'         => $i18n_js,
                ));

            }

            if (in_array($page, array('seo-aeo-orchestra', 'seo-aeo-content', 'seo-aeo-native-output'))) {
                wp_enqueue_media();
            }

        } catch (Throwable $e) {
        }
    }

    public function add_seo_meta_box() {
        foreach (array('post', 'page') as $post_type) {
            add_meta_box('seo_aeo_orchestra_meta', 'AEO Orchestra', array($this, 'render_seo_meta_box'), $post_type, 'normal', 'high');
        }
    }

    public function render_seo_meta_box($post) {
        wp_nonce_field('seo_aeo_orchestra_meta_box', 'seo_aeo_orchestra_meta_nonce');
        $meta_title = get_post_meta($post->ID, '_seo_aeo_meta_title', true);
        $meta_desc = get_post_meta($post->ID, '_seo_aeo_meta_description', true);
        $meta_keywords = get_post_meta($post->ID, '_seo_aeo_meta_keywords', true);
        ?>
        <div class="orchestra-meta-box">
            <div class="orchestra-field">
                <label for="seo_aeo_meta_title">Meta Title</label>
                <input type="text" id="seo_aeo_meta_title" name="seo_aeo_meta_title" value="<?php echo esc_attr($meta_title); ?>" />
                <span class="char-count"><span id="title-count"><?php echo (int) strlen($meta_title); ?></span>/60</span>
            </div>
            <div class="orchestra-field">
                <label for="seo_aeo_meta_description">Meta Description</label>
                <textarea id="seo_aeo_meta_description" name="seo_aeo_meta_description" rows="3"><?php echo esc_textarea($meta_desc); ?></textarea>
                <span class="char-count"><span id="desc-count"><?php echo (int) strlen($meta_desc); ?></span>/160</span>
            </div>
            <div class="orchestra-field">
                <label for="seo_aeo_meta_keywords">Keywords</label>
                <input type="text" id="seo_aeo_meta_keywords" name="seo_aeo_meta_keywords" value="<?php echo esc_attr($meta_keywords); ?>" placeholder="keyword1, keyword2, keyword3" />
            </div>
            <div class="orchestra-field">
                <label for="seo_aeo_focus_keyword">Focus Keyword per AI</label>
                <input type="text" id="seo_aeo_focus_keyword" placeholder="Inserisci keyword per generazione AI" />
            </div>
            <div class="orchestra-actions">
                <button type="button" class="button button-primary" id="seo-aeo-generate-meta" data-post-id="<?php echo absint($post->ID); ?>">
                    <span class="dashicons dashicons-admin-generic"></span> Genera con AI - <span class="credit-cost" data-cost-key="meta_generation">2</span> crediti
                </button>
                <span class="spinner" style="float: none;"></span>
            </div>
        </div>
        <?php
    }

    public function save_seo_meta($post_id) {
        if (
            !isset($_POST['seo_aeo_orchestra_meta_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['seo_aeo_orchestra_meta_nonce'])),
                'seo_aeo_orchestra_meta_box'
            )
        ) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['seo_aeo_meta_title'])) update_post_meta($post_id, '_seo_aeo_meta_title', sanitize_text_field(wp_unslash($_POST['seo_aeo_meta_title'])));
        if (isset($_POST['seo_aeo_meta_description'])) update_post_meta($post_id, '_seo_aeo_meta_description', sanitize_textarea_field(wp_unslash($_POST['seo_aeo_meta_description'])));
        if (isset($_POST['seo_aeo_meta_keywords'])) update_post_meta($post_id, '_seo_aeo_meta_keywords', sanitize_text_field(wp_unslash($_POST['seo_aeo_meta_keywords'])));
    }

    public function output_meta_tags() {
        if (!is_singular()) return;
        $post_id = get_the_ID();
        if (!$post_id) return;
        $meta_title = get_post_meta($post_id, '_seo_aeo_meta_title', true);
        $meta_desc = get_post_meta($post_id, '_seo_aeo_meta_description', true);
        $meta_keywords = get_post_meta($post_id, '_seo_aeo_meta_keywords', true);

        if ($meta_desc) echo '<meta name="description" content="' . esc_attr($meta_desc) . '" />' . "\n";
        if ($meta_keywords) echo '<meta name="keywords" content="' . esc_attr($meta_keywords) . '" />' . "\n";
        if ($meta_title) echo '<meta property="og:title" content="' . esc_attr($meta_title) . '" />' . "\n";
        if ($meta_desc) echo '<meta property="og:description" content="' . esc_attr($meta_desc) . '" />' . "\n";
    }

    /* --------------------------------------------------------
     * Template rendering
     * Each render method includes the template DIRECTLY
     * with try/catch to prevent fatal errors.
     * -------------------------------------------------------- */

    public function render_admin_page() {
        try {
            $seo_aeo_dir = defined('SEO_AEO_PLUGIN_DIR') ? SEO_AEO_PLUGIN_DIR : plugin_dir_path(dirname(__FILE__));
            $seo_aeo_tpl = $seo_aeo_dir . 'templates/admin-dashboard.php';
            if (!file_exists($seo_aeo_tpl)) { echo '<div class="wrap"><p>Template non trovato.</p></div>'; return; }
            include $seo_aeo_tpl;
        } catch (Throwable $e) {
            echo '<div class="wrap"><div class="notice notice-error"><p>Errore: ' . esc_html($e->getMessage()) . '</p></div></div>';
        }
    }

    public function render_analyze_page() {
        try {
            $seo_aeo_dir = defined('SEO_AEO_PLUGIN_DIR') ? SEO_AEO_PLUGIN_DIR : plugin_dir_path(dirname(__FILE__));
            $seo_aeo_tpl = $seo_aeo_dir . 'templates/analyze.php';
            if (!file_exists($seo_aeo_tpl)) { echo '<div class="wrap"><p>Template non trovato.</p></div>'; return; }
            include $seo_aeo_tpl;
        } catch (Throwable $e) {
            echo '<div class="wrap"><div class="notice notice-error"><p>Errore: ' . esc_html($e->getMessage()) . '</p></div></div>';
        }
    }

    public function render_meta_page() {
        try {
            $seo_aeo_dir = defined('SEO_AEO_PLUGIN_DIR') ? SEO_AEO_PLUGIN_DIR : plugin_dir_path(dirname(__FILE__));
            $seo_aeo_tpl = $seo_aeo_dir . 'templates/meta-tags.php';
            if (!file_exists($seo_aeo_tpl)) { echo '<div class="wrap"><p>Template non trovato.</p></div>'; return; }
            include $seo_aeo_tpl;
        } catch (Throwable $e) {
            echo '<div class="wrap"><div class="notice notice-error"><p>Errore: ' . esc_html($e->getMessage()) . '</p></div></div>';
        }
    }

    public function render_content_page() {
        try {
            $seo_aeo_dir = defined('SEO_AEO_PLUGIN_DIR') ? SEO_AEO_PLUGIN_DIR : plugin_dir_path(dirname(__FILE__));
            $seo_aeo_tpl = $seo_aeo_dir . 'templates/content-generator.php';
            if (!file_exists($seo_aeo_tpl)) { echo '<div class="wrap"><p>Template non trovato.</p></div>'; return; }
            include $seo_aeo_tpl;
        } catch (Throwable $e) {
            echo '<div class="wrap"><div class="notice notice-error"><p>Errore: ' . esc_html($e->getMessage()) . '</p></div></div>';
        }
    }

    public function render_local_page() {
        try {
            $seo_aeo_dir = defined('SEO_AEO_PLUGIN_DIR') ? SEO_AEO_PLUGIN_DIR : plugin_dir_path(dirname(__FILE__));
            $seo_aeo_tpl = $seo_aeo_dir . 'templates/local-seo.php';
            if (!file_exists($seo_aeo_tpl)) { echo '<div class="wrap"><p>Template non trovato.</p></div>'; return; }
            include $seo_aeo_tpl;
        } catch (Throwable $e) {
            echo '<div class="wrap"><div class="notice notice-error"><p>Errore: ' . esc_html($e->getMessage()) . '</p></div></div>';
        }
    }

    public function render_aeo_analysis_page() {
        try {
            $seo_aeo_dir = defined('SEO_AEO_PLUGIN_DIR') ? SEO_AEO_PLUGIN_DIR : plugin_dir_path(dirname(__FILE__));
            $seo_aeo_tpl = $seo_aeo_dir . 'templates/aeo-analysis.php';
            if (!file_exists($seo_aeo_tpl)) { echo '<div class="wrap"><p>Template non trovato.</p></div>'; return; }
            include $seo_aeo_tpl;
        } catch (Throwable $e) {
            echo '<div class="wrap"><div class="notice notice-error"><p>Errore: ' . esc_html($e->getMessage()) . '</p></div></div>';
        }
    }

    public function render_aeo_content_page() {
        try {
            $seo_aeo_dir = defined('SEO_AEO_PLUGIN_DIR') ? SEO_AEO_PLUGIN_DIR : plugin_dir_path(dirname(__FILE__));
            $seo_aeo_tpl = $seo_aeo_dir . 'templates/aeo-content.php';
            if (!file_exists($seo_aeo_tpl)) { echo '<div class="wrap"><p>Template non trovato.</p></div>'; return; }
            include $seo_aeo_tpl;
        } catch (Throwable $e) {
            echo '<div class="wrap"><div class="notice notice-error"><p>Errore: ' . esc_html($e->getMessage()) . '</p></div></div>';
        }
    }

    public function render_usage_page() {
        try {
            $seo_aeo_dir = defined('SEO_AEO_PLUGIN_DIR') ? SEO_AEO_PLUGIN_DIR : plugin_dir_path(dirname(__FILE__));
            $seo_aeo_tpl = $seo_aeo_dir . 'templates/usage-tracker.php';
            if (!file_exists($seo_aeo_tpl)) { echo '<div class="wrap"><p>Template non trovato.</p></div>'; return; }
            include $seo_aeo_tpl;
        } catch (Throwable $e) {
            echo '<div class="wrap"><div class="notice notice-error"><p>Errore: ' . esc_html($e->getMessage()) . '</p></div></div>';
        }
    }

    public function render_settings_page() {
        try {
            $seo_aeo_dir = defined('SEO_AEO_PLUGIN_DIR') ? SEO_AEO_PLUGIN_DIR : plugin_dir_path(dirname(__FILE__));
            $seo_aeo_tpl = $seo_aeo_dir . 'templates/settings.php';
            if (!file_exists($seo_aeo_tpl)) { echo '<div class="wrap"><p>Template non trovato.</p></div>'; return; }
            include $seo_aeo_tpl;
        } catch (Throwable $e) {
            echo '<div class="wrap"><div class="notice notice-error"><p>Errore: ' . esc_html($e->getMessage()) . '</p></div></div>';
        }
    }
}

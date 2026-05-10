<?php
/*
 * Template: Cannibalizzazione SEO (since 3.28.0)
 * Pagina dedicata per il detector di cannibalizzazione, raggiungibile dal
 * submenu "🌳 Cannibalizzazione SEO". Riusa il partial
 * templates/partials/cannibalization-section.php.
 */
if (!defined('ABSPATH')) exit;

$plugin_version = defined('SEO_AEO_VERSION') ? SEO_AEO_VERSION : '?';
$license_key = get_option('seo_aeo_orchestra_license_key', '');
$license_valid = !empty($license_key);
?>

<div class="wrap orch-cannibal-page">

    <!-- HERO -->
    <div class="orch-cannibal-page-hero">
        <div class="orch-cannibal-page-hero-bg"></div>
        <div class="orch-cannibal-page-hero-inner">
            <div class="orch-cannibal-page-hero-icon">⚔️</div>
            <div class="orch-cannibal-page-hero-text">
                <div class="orch-cannibal-page-hero-eyebrow">AEO Orchestra · v<?php echo esc_html($plugin_version); ?></div>
                <h1 class="orch-cannibal-page-hero-title"><?php SEO_AEO_T::e('Cannibalizzazione SEO'); ?></h1>
                <p class="orch-cannibal-page-hero-sub"><?php SEO_AEO_T::e('Trova le pagine in conflitto sulla stessa keyword. L\'AI propone una pagina primaria, suggerisce keyword alternative per le supporting e link interni — il tutto applicabile con un click, sempre reversibile.'); ?></p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/partials/cannibalization-section.php'; ?>

    <p class="orch-cannibal-page-back">
        <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-orchestra')); ?>">← <?php SEO_AEO_T::e('Torna alla Dashboard'); ?></a>
    </p>
</div>

<?php ob_start(); ?>
.orch-cannibal-page { max-width: 1280px; margin-right: 20px; }
.orch-cannibal-page-hero { position: relative; margin: 14px 0 22px; padding: 30px 36px; border-radius: 14px; overflow: hidden; color: #fff; }
.orch-cannibal-page-hero-bg { position: absolute; inset: 0; background: linear-gradient(120deg, #7f1d1d 0%, #b91c1c 35%, #dc2626 70%, #f59e0b 100%); }
.orch-cannibal-page-hero-bg::after { content: ''; position: absolute; inset: 0; background: radial-gradient(700px 360px at 80% -20%, rgba(255,255,255,0.18), transparent 60%); }
.orch-cannibal-page-hero-inner { position: relative; display: flex; gap: 24px; align-items: center; }
.orch-cannibal-page-hero-icon { font-size: 64px; line-height: 1; flex-shrink: 0; }
.orch-cannibal-page-hero-eyebrow { font-size: 12px; letter-spacing: 1.2px; text-transform: uppercase; opacity: 0.85; font-weight: 600; }
.orch-cannibal-page-hero-title { margin: 8px 0 8px; font-size: 28px; font-weight: 800; line-height: 1.2; color: #fff; }
.orch-cannibal-page-hero-sub { margin: 0; font-size: 14.5px; line-height: 1.6; opacity: 0.95; max-width: 760px; }
.orch-cannibal-page-back { margin-top: 24px; font-size: 13px; }
.orch-cannibal-page-back a { color: #0055FF; text-decoration: none; font-weight: 600; }
.orch-cannibal-page-back a:hover { color: #00E5FF; }
<?php SEO_AEO_Inline_Assets::add_inline_style(ob_get_clean()); ?>

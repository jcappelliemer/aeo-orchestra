<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.VariableNotPrefixed
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Template scope: table names from $wpdb->prefix (schema-controlled); IN() clause placeholders array_fill() + $wpdb->prepare() pattern.
// Reason: template scope. Variables are local to this include/template,
// passed by the calling function via include/require. The Plugin Check
// heuristic doesn't distinguish template-scope locals from globals.
/**
 * 3.35.84-beta — AI Performance live state.
 * 4 stat card + bar chart top 5 + table top 10 + sparkline + compliance + Phase 2 footer.
 *
 * Server-side rendering from transient cache (5min summary, 15min top, 1h trend/compliance).
 * Auto-refresh stat cards every 5 min via JS setInterval (see parent partial).
 */
if (!defined('ABSPATH')) exit;
$T = isset($T) ? $T : function($s) { return class_exists('SEO_AEO_T') ? SEO_AEO_T::t($s) : $s; };

global $wpdb;
$log_table = $wpdb->prefix . SEO_AEO_AI_Crawler_Detector::TABLE_NAME;
$stats_table = $wpdb->prefix . SEO_AEO_AI_Crawler_Detector::TABLE_DAILY_STATS;

// === 1. Summary stats (transient 5min) ===
$summary = get_transient('seo_aeo_aip_summary_28d');
if ($summary === false) {
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
    $hits_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $log_table WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 28 DAY)");
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
    $bots_active = (int) $wpdb->get_var("SELECT COUNT(DISTINCT bot_name) FROM $log_table WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 28 DAY)");
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
    $row = $wpdb->get_row("
        SELECT
            SUM(CASE WHEN visited_at >= DATE_SUB(NOW(), INTERVAL 28 DAY) THEN 1 ELSE 0 END) AS current_28d,
            SUM(CASE WHEN visited_at >= DATE_SUB(NOW(), INTERVAL 56 DAY) AND visited_at < DATE_SUB(NOW(), INTERVAL 28 DAY) THEN 1 ELSE 0 END) AS prev_28d
        FROM $log_table WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 56 DAY)
    ", ARRAY_A);
    $current = (int) ($row['current_28d'] ?? 0);
    $prev = (int) ($row['prev_28d'] ?? 0);
    $trend_pct = ($prev > 0) ? (int) round(100 * ($current - $prev) / $prev) : ($current > 0 ? 100 : 0);

    // blocked_bypass
    $allowlist = get_option(SEO_AEO_AI_Crawler_Detector::ALLOWLIST_OPT, array());
    $defs = SEO_AEO_AI_Crawler_Detector::bot_definitions();
    $blocked_names = array();
    if (is_array($allowlist)) {
        foreach ($allowlist as $slug => $action) {
            if ($action === 'block' && isset($defs[$slug])) $blocked_names[] = $defs[$slug]['name'];
        }
    }
    $blocked_bypass = 0;
    if (!empty($blocked_names)) {
        $placeholders = implode(',', array_fill(0, count($blocked_names), '%s'));
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
        $blocked_bypass = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $log_table WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 28 DAY) AND bot_name IN ($placeholders)",
            $blocked_names
        ));
    }

    $summary = array(
        'hits_total'     => $hits_total,
        'bots_active'    => $bots_active,
        'trend_pct'      => $trend_pct,
        'blocked_bypass' => $blocked_bypass,
    );
    set_transient('seo_aeo_aip_summary_28d', $summary, 5 * MINUTE_IN_SECONDS);
}

// === 2. Top 5 bot (transient 15min) ===
$top_bots = get_transient('seo_aeo_aip_top_bots_28d');
if ($top_bots === false) {
    // 3.35.84.3: GROUP BY bot_name only — consolidate empty-provider rows + dedupe legacy slug forms
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
    $rows = $wpdb->get_results("
        SELECT bot_name,
               COALESCE(MAX(NULLIF(bot_provider, '')), 'unknown') AS bot_provider,
               COUNT(*) AS hits
        FROM $log_table
        WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 28 DAY)
        GROUP BY bot_name
        ORDER BY hits DESC LIMIT 5
    ", ARRAY_A);
    if (!is_array($rows)) $rows = array();
    foreach ($rows as &$r) {
        $slug = SEO_AEO_AI_Crawler_Detector::slug_from_bot_name($r['bot_name']);
        $r['classification'] = SEO_AEO_AI_Crawler_Detector::classification($slug);
        $r['hits'] = (int) $r['hits'];
    }
    $top_bots = array('rows' => $rows);
    set_transient('seo_aeo_aip_top_bots_28d', $top_bots, 15 * MINUTE_IN_SECONDS);
}

$top_bot_max = 0;
foreach ($top_bots['rows'] as $b) {
    if ($b['hits'] > $top_bot_max) $top_bot_max = $b['hits'];
}

// === 3. Top 10 pages (transient 15min) — query inline (skip transient if first render) ===
$top_pages_data = get_transient('seo_aeo_aip_top_pages_28d');
if ($top_pages_data === false) {
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
    $top = $wpdb->get_results("
        SELECT url_path, COUNT(*) AS hits
        FROM $log_table
        WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 28 DAY) AND url_path != ''
        GROUP BY url_path ORDER BY hits DESC LIMIT 10
    ", ARRAY_A);
    if (!is_array($top)) $top = array();

    $rows = array();
    if (!empty($top)) {
        $paths = array_column($top, 'url_path');
        $placeholders = implode(',', array_fill(0, count($paths), '%s'));
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
        $break = $wpdb->get_results($wpdb->prepare(
            "SELECT url_path, bot_name, COUNT(*) AS hits FROM $log_table
             WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 28 DAY) AND url_path IN ($placeholders)
             GROUP BY url_path, bot_name ORDER BY url_path, hits DESC",
            $paths
        ), ARRAY_A);
        $break_map = array();
        if (is_array($break)) {
            foreach ($break as $b) {
                if (!isset($break_map[$b['url_path']])) $break_map[$b['url_path']] = array();
                $break_map[$b['url_path']][] = array('bot_name' => $b['bot_name'], 'hits' => (int) $b['hits']);
            }
        }
        foreach ($top as $t) {
            $rows[] = array(
                'url_path' => $t['url_path'],
                'hits'     => (int) $t['hits'],
                'bots'     => isset($break_map[$t['url_path']]) ? array_slice($break_map[$t['url_path']], 0, 5) : array(),
            );
        }
    }
    $top_pages_data = array('rows' => $rows);
    set_transient('seo_aeo_aip_top_pages_28d', $top_pages_data, 15 * MINUTE_IN_SECONDS);
}

// === 4. Trend 28gg sparkline (transient 1h) ===
$trend = get_transient('seo_aeo_aip_trend_28d');
if ($trend === false) {
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
    $top4 = $wpdb->get_col("
        SELECT bot_name FROM $log_table
        WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 28 DAY)
        GROUP BY bot_name ORDER BY COUNT(*) DESC LIMIT 4
    ");
    $labels = array();
    for ($i = 27; $i >= 0; $i--) $labels[] = gmdate('Y-m-d', strtotime("-$i days"));
    $series = array();
    if (is_array($top4) && !empty($top4)) {
        $placeholders = implode(',', array_fill(0, count($top4), '%s'));
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
        $rows_t = $wpdb->get_results($wpdb->prepare("
            SELECT DATE(visited_at) AS stat_date, bot_name, COUNT(*) AS hits
            FROM $log_table
            WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 28 DAY) AND bot_name IN ($placeholders)
            GROUP BY DATE(visited_at), bot_name
        ", $top4), ARRAY_A);
        if (!is_array($rows_t)) $rows_t = array();
        foreach ($top4 as $bot_name) {
            $values = array_fill_keys($labels, 0);
            foreach ($rows_t as $r) {
                if ($r['bot_name'] === $bot_name && isset($values[$r['stat_date']])) {
                    $values[$r['stat_date']] = (int) $r['hits'];
                }
            }
            $color_map = SEO_AEO_AI_Crawler_Detector::TREND_COLORS;
            $color = isset($color_map[$bot_name]) ? $color_map[$bot_name] : '#6366f1';
            $series[] = array(
                'bot_name' => $bot_name,
                'color'    => $color,
                'values'   => array_values($values),
            );
        }
    }
    $trend = array('labels' => $labels, 'series' => $series);
    set_transient('seo_aeo_aip_trend_28d', $trend, HOUR_IN_SECONDS);
}

// === 5. Compliance (transient 1h) ===
$compliance = get_transient('seo_aeo_aip_compliance_28d');
if ($compliance === false) {
    $allowlist = get_option(SEO_AEO_AI_Crawler_Detector::ALLOWLIST_OPT, array());
    $defs = SEO_AEO_AI_Crawler_Detector::bot_definitions();
    $blocked_names = array();
    if (is_array($allowlist)) {
        foreach ($allowlist as $slug => $action) {
            if ($action === 'block' && isset($defs[$slug])) $blocked_names[] = $defs[$slug]['name'];
        }
    }
    $blocked_count = count($blocked_names);
    $violations = array();
    if ($blocked_count > 0) {
        $placeholders = implode(',', array_fill(0, count($blocked_names), '%s'));
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names come from $wpdb->prefix + literal constant (schema-controlled, never user input). IN() clause placeholders generated via array_fill() then passed to $wpdb->prepare() with values array (WordPress core documented pattern). $sql variable often returned from a prior $wpdb->prepare() call on a separate line, which the Plugin Check static analyzer cannot trace. Admin diagnostic queries (one-shot per admin page load), caching not applicable.
        $rows_v = $wpdb->get_results($wpdb->prepare("
            SELECT bot_name, COUNT(*) AS violations
            FROM $log_table
            WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 28 DAY) AND bot_name IN ($placeholders)
            GROUP BY bot_name HAVING violations > 0 ORDER BY violations DESC
        ", $blocked_names), ARRAY_A);
        if (is_array($rows_v)) {
            foreach ($rows_v as &$r) $r['violations'] = (int) $r['violations'];
            $violations = $rows_v;
        }
    }
    $compliance = array(
        'compliant'          => empty($violations),
        'violations'         => $violations,
        'blocked_bots_count' => $blocked_count,
    );
    set_transient('seo_aeo_aip_compliance_28d', $compliance, HOUR_IN_SECONDS);
}
?>

<!-- ═══ 4 STAT CARD ═══ -->
<div class="aip-stats-row">
    <div class="aip-stat-card">
        <div class="aip-stat-card-label"><?php echo esc_html($T('Hits totali')); ?></div>
        <div class="aip-stat-card-value" id="aip-stat-hits-total"><?php echo esc_html(number_format_i18n($summary['hits_total'])); ?></div>
        <div class="aip-stat-card-sub"><?php echo esc_html($T('ultimi 28gg')); ?></div>
    </div>
    <div class="aip-stat-card">
        <div class="aip-stat-card-label"><?php echo esc_html($T('Bot attivi')); ?></div>
        <div class="aip-stat-card-value" id="aip-stat-bots-active"><?php echo (int) $summary['bots_active']; ?></div>
        <div class="aip-stat-card-sub"><?php echo esc_html($T('AI engine distinti')); ?></div>
    </div>
    <div class="aip-stat-card">
        <div class="aip-stat-card-label"><?php echo esc_html($T('Trend 28gg')); ?></div>
        <?php
            $trend_pct = (int) $summary['trend_pct'];
            $trend_class = $trend_pct > 0 ? 'aip-stat-card-trend--up' : ($trend_pct < 0 ? 'aip-stat-card-trend--down' : 'aip-stat-card-trend--flat');
            $trend_arrow = $trend_pct > 0 ? '↗' : ($trend_pct < 0 ? '↘' : '→');
        ?>
        <div class="aip-stat-card-value <?php echo esc_html($trend_class); ?>" id="aip-stat-trend">
            <?php echo esc_html(($trend_pct >= 0 ? '+' : '') . (float) $trend_pct); ?>%
        </div>
        <div class="aip-stat-card-sub"><?php echo esc_html($trend_arrow); ?> <?php echo esc_html($T('vs precedenti 28gg')); ?></div>
    </div>
    <div class="aip-stat-card <?php if ((int) $summary['blocked_bypass'] > 0) echo 'aip-stat-card--alert'; ?>">
        <div class="aip-stat-card-label"><?php echo esc_html($T('Bypass robots.txt')); ?></div>
        <div class="aip-stat-card-value" id="aip-stat-blocked"><?php echo (int) $summary['blocked_bypass']; ?></div>
        <div class="aip-stat-card-sub"><?php echo esc_html($T('bot bloccati che ignorano')); ?></div>
    </div>
</div>

<!-- ═══ TOP 5 BOT BAR CHART ═══ -->
<div class="aip-subsection">
    <h3 class="aip-subsection-title">📊 <?php echo esc_html($T('Top 5 bot AI più attivi (28gg)')); ?></h3>
    <?php if (!empty($top_bots['rows'])): foreach ($top_bots['rows'] as $b): ?>
        <?php
            $pct = $top_bot_max > 0 ? round(100 * $b['hits'] / $top_bot_max) : 0;
            $cls = $b['classification'];
            $emoji = $cls === 'green' ? '🟢' : ($cls === 'yellow' ? '🟡' : '🔴');
        ?>
        <div class="aip-bot-row">
            <div class="aip-bot-name">
                <span class="aip-bot-dot aip-bot-dot--<?php echo esc_html($cls); ?>"></span>
                <span><?php echo esc_html($b['bot_name']); ?> <span style="color: var(--orch-muted, #475569); font-size: 11px;">(<?php echo esc_html($b['bot_provider']); ?>)</span></span>
            </div>
            <div class="aip-bot-bar"><div class="aip-bot-bar-fill aip-bot-bar-fill--<?php echo esc_html($cls); ?>" style="width: <?php echo esc_html($pct); ?>%;"></div></div>
            <div class="aip-bot-count"><?php echo esc_html(number_format_i18n($b['hits'])); ?></div>
        </div>
    <?php endforeach; else: ?>
        <p style="color: var(--orch-muted, #475569); font-size: 13px;"><?php echo esc_html($T('Nessun bot rilevato negli ultimi 28 giorni.')); ?></p>
    <?php endif; ?>
    <div class="aip-bot-legend">
        <span><span class="aip-bot-dot aip-bot-dot--green" style="display:inline-block; margin-right: 4px;"></span>🟢 <?php echo esc_html($T('AI engine diretto')); ?></span>
        <span><span class="aip-bot-dot aip-bot-dot--yellow" style="display:inline-block; margin-right: 4px;"></span>🟡 <?php echo esc_html($T('Multi-purpose')); ?></span>
        <span><span class="aip-bot-dot aip-bot-dot--red" style="display:inline-block; margin-right: 4px;"></span>🔴 <?php echo esc_html($T('Crawler aggregato')); ?></span>
    </div>
</div>

<!-- ═══ TOP 10 PAGES TABLE ═══ -->
<div class="aip-subsection">
    <h3 class="aip-subsection-title">
        📄 <?php echo esc_html($T('Top 10 pagine più scansionate (28gg)')); ?>
        <span class="aip-subsection-title-actions">
            <a href="<?php echo esc_url(admin_url('admin-post.php?action=orch_ai_crawler_export_csv')); ?>" class="orch3-btn orch3-btn-ghost orch3-btn-sm" style="font-size: 11px; padding: 4px 10px;">⬇ <?php echo esc_html($T('Esporta CSV')); ?></a>
        </span>
    </h3>
    <?php if (!empty($top_pages_data['rows'])): ?>
    <table class="aip-pages-table">
        <thead>
            <tr>
                <th><?php echo esc_html($T('URL path')); ?></th>
                <th class="aip-pages-hits"><?php echo esc_html($T('Hits')); ?></th>
                <th><?php echo esc_html($T('Bots breakdown')); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($top_pages_data['rows'] as $p): ?>
                <tr>
                    <td class="aip-pages-url"><?php echo esc_html($p['url_path']); ?></td>
                    <td class="aip-pages-hits"><?php echo esc_html(number_format_i18n($p['hits'])); ?></td>
                    <td class="aip-pages-bots">
                        <?php
                        $bot_strs = array();
                        foreach ($p['bots'] as $b) $bot_strs[] = esc_html($b['bot_name']) . ' ' . (int) $b['hits'];
                        echo wp_kses_post(implode(', ', $bot_strs));
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="color: var(--orch-muted, #475569); font-size: 13px;"><?php echo esc_html($T('Nessuna pagina scansionata da bot AI negli ultimi 28 giorni.')); ?></p>
    <?php endif; ?>
</div>

<!-- ═══ SPARKLINE TREND 28gg ═══ -->
<?php if (!empty($trend['series'])): ?>
<div class="aip-subsection">
    <h3 class="aip-subsection-title">📈 <?php echo esc_html($T('Trend 28 giorni (top 4 bot)')); ?></h3>
    <?php
    // Build SVG sparkline. Find max value across all series for scaling.
    $max_val = 1;
    foreach ($trend['series'] as $s) {
        foreach ($s['values'] as $v) if ($v > $max_val) $max_val = $v;
    }
    $svg_w = 600; $svg_h = 80;
    $pt_w = $svg_w / max(1, count($trend['labels']) - 1);
    $aria_parts = array();
    foreach ($trend['series'] as $s) {
        $total = array_sum($s['values']);
        $aria_parts[] = $s['bot_name'] . ' ' . $total . ' hits';
    }
    $aria_label = sprintf($T('Trend AI bot ultimi 28 giorni: %s'), implode(', ', $aria_parts));
    ?>
    <svg class="aip-sparkline" viewBox="0 0 <?php echo esc_html($svg_w); ?> <?php echo esc_html($svg_h); ?>" preserveAspectRatio="none" role="img" aria-label="<?php echo esc_attr($aria_label); ?>">
        <?php foreach ($trend['series'] as $s):
            $points = array();
            foreach ($s['values'] as $i => $v) {
                $x = round($i * $pt_w, 1);
                $y = $svg_h - round(($v / $max_val) * ($svg_h - 8), 1) - 2;
                $points[] = "$x,$y";
            }
        ?>
            <polyline points="<?php echo esc_attr(implode(' ', $points)); ?>" fill="none" stroke="<?php echo esc_attr($s['color']); ?>" stroke-width="2" />
        <?php endforeach; ?>
    </svg>
    <div class="aip-spark-legend">
        <?php foreach ($trend['series'] as $s): ?>
            <span><span class="aip-spark-legend-dot" style="background: <?php echo esc_attr($s['color']); ?>;"></span><?php echo esc_html($s['bot_name']); ?></span>
        <?php endforeach; ?>
        <span style="margin-left: auto; color: var(--orch-faint, #94a3b8);"><?php echo esc_html($T('28gg fa')); ?> ────────→ <?php echo esc_html($T('ieri')); ?></span>
    </div>
</div>
<?php endif; ?>

<!-- ═══ COMPLIANCE CHECK ═══ -->
<div class="aip-subsection">
    <h3 class="aip-subsection-title">🛡 <?php echo esc_html($T('Robots.txt compliance')); ?></h3>
    <?php if ($compliance['compliant']): ?>
        <?php if ($compliance['blocked_bots_count'] === 0): ?>
            <div class="aip-compliance-card">
                <strong>✅ <?php echo esc_html($T('Nessun bot bloccato in AI Crawlers settings.')); ?></strong>
                <p style="margin: 6px 0 0; font-size: 13px;"><?php echo esc_html($T('Per controllare quali bot scansionano il tuo sito, configura allowlist e robots.txt:')); ?></p>
                <div class="aip-compliance-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-ai-crawlers')); ?>" class="orch3-btn orch3-btn-ghost"><?php echo esc_html($T('Configura AI Crawlers →')); ?></a>
                </div>
            </div>
        <?php else: ?>
            <div class="aip-compliance-card">
                <strong>✅ <?php echo esc_html($T('Tutti i bot rispettano le tue settings AI Crawlers (28gg)')); ?></strong>
                <p style="margin: 6px 0 0; font-size: 13px;"><?php echo esc_html(sprintf($T('%d bot bloccati, 0 violations rilevate. Compliance perfetta.'), (int) $compliance['blocked_bots_count'])); ?></p>
                <div class="aip-compliance-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-ai-crawlers')); ?>" class="orch3-btn orch3-btn-ghost"><?php echo esc_html($T('Configura AI Crawlers →')); ?></a>
                    <a href="<?php echo esc_url(home_url('/robots.txt')); ?>" target="_blank" class="orch3-btn orch3-btn-ghost"><?php echo esc_html($T('Vedi tuo robots.txt →')); ?></a>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="aip-compliance-card aip-compliance-card--violations">
            <?php $tot_violations = array_sum(array_column($compliance['violations'], 'violations')); ?>
            <strong>⚠ <?php echo esc_html(sprintf($T('%d hits da bot bloccati ultimi 28gg'), (int) $tot_violations)); ?></strong>
            <ul style="margin: 8px 0 0; padding-left: 20px; font-size: 13px;">
                <?php foreach ($compliance['violations'] as $v): ?>
                    <li><strong><?php echo esc_html($v['bot_name']); ?></strong> — <?php echo esc_html(number_format_i18n($v['violations'])); ?> <?php echo esc_html($T('hits su URL bloccati in robots.txt')); ?></li>
                <?php endforeach; ?>
            </ul>
            <p style="margin: 8px 0 0; font-size: 12px; color: #92400e;"><?php echo esc_html($T('Possibile non compliance. Considera blocking server-level.')); ?></p>
            <div class="aip-compliance-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=seo-aeo-ai-crawlers')); ?>" class="orch3-btn orch3-btn-ghost"><?php echo esc_html($T('Configura AI Crawlers →')); ?></a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ═══ Phase 2 footer ═══ -->
<div class="aip-phase2-footer">
    ℹ <?php echo esc_html($T('Phase 2 (Bing Webmaster Tools): citation count reale ChatGPT/Copilot/Bing Chat. Coming in v3.35.85 quando setup Microsoft Azure completato.')); ?>
</div>

<?php ob_start(); ?>
// Expose ajax info for parent partial auto-refresh
window.aipLiveState = {
    ajaxurl: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
    nonce:   '<?php echo esc_js(wp_create_nonce('seo_aeo_orchestra_nonce')); ?>'
};
<?php SEO_AEO_Inline_Assets::add_inline_script(ob_get_clean()); ?>

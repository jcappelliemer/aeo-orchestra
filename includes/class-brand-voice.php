<?php
// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com
 *
 * Brand Voice Learning (v3.21.0 — roadmap "Soro Killer")
 *
 * Analizza N articoli del sito e genera un profilo di brand voice via AI.
 * Il profilo viene salvato in DB e applicato automaticamente alle generazioni AI
 * future (Articolo Completo, Meta Tags, AEO Content, Cannibalization fix, ecc.).
 *
 * Tabella: wp_seo_aeo_brand_voices (id, name, profile_json, is_active, created_at, updated_at)
 *
 * Setting: nessuno necessario — il profilo "is_active=1" viene applicato automaticamente.
 *
 * Use case agenzia: creare profili distinti per ogni cliente, attivarne uno alla volta
 * in base al sito su cui si lavora.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Brand_Voice {

    const TABLE = 'seo_aeo_brand_voices';
    const DB_VERSION_OPT = 'seo_aeo_brand_voice_db_version';
    const DB_VERSION = '1.0';

    public function __construct() {
        add_action('init', array(__CLASS__, 'maybe_create_table'));
    }

    public static function maybe_create_table() {
        $stored = get_option(self::DB_VERSION_OPT, '');
        if ($stored === self::DB_VERSION) return;

        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();
        $tbl = $wpdb->prefix . self::TABLE;

        $sql = "CREATE TABLE $tbl (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(120) NOT NULL,
            profile_json LONGTEXT NOT NULL,
            articles_count INT UNSIGNED NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_active (is_active)
        ) $charset_collate;";

        dbDelta($sql);
        update_option(self::DB_VERSION_OPT, self::DB_VERSION);
    }

    /**
     * Ritorna il profilo attivo (is_active=1) o null se nessuno.
     */
    public static function get_active_profile() {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE;
        $row = $wpdb->get_row("SELECT * FROM $tbl WHERE is_active = 1 ORDER BY id DESC LIMIT 1", ARRAY_A);
        if (!$row) return null;
        $profile = json_decode($row['profile_json'], true);
        if (!is_array($profile)) return null;
        $profile['_id'] = (int) $row['id'];
        $profile['_name'] = $row['name'];
        return $profile;
    }

    /**
     * Ritorna il blocco "system message" per il prompt AI dal profilo attivo.
     * Se nessun profilo attivo, ritorna stringa vuota.
     *
     * Da chiamare in qualsiasi flusso AI di generazione contenuti, e prependere
     * al system_message principale.
     */
    public static function build_system_prompt_addition() {
        $profile = self::get_active_profile();
        if (!$profile) return '';

        $summary = isset($profile['summary_for_prompt']) ? trim($profile['summary_for_prompt']) : '';
        if (empty($summary)) return '';

        $vocab = isset($profile['distinctive_vocabulary']) && is_array($profile['distinctive_vocabulary'])
            ? implode(', ', array_slice($profile['distinctive_vocabulary'], 0, 15))
            : '';
        $avoid = isset($profile['avoid_words']) && is_array($profile['avoid_words'])
            ? implode(', ', array_slice($profile['avoid_words'], 0, 8))
            : '';

        $addition  = "\n\n--- BRAND VOICE PROFILE: " . esc_html($profile['_name']) . " ---\n";
        $addition .= $summary;
        if ($vocab) $addition .= "\n\nVOCABOLARIO DISTINTIVO da usare: " . $vocab;
        if ($avoid) $addition .= "\n\nDA EVITARE (anti-cliché): " . $avoid;
        $addition .= "\n--- FINE BRAND VOICE ---";
        return $addition;
    }

    public static function list_profiles() {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE;
        return $wpdb->get_results("SELECT id, name, articles_count, is_active, created_at, updated_at FROM $tbl ORDER BY is_active DESC, updated_at DESC LIMIT 100", ARRAY_A);
    }

    public static function get_profile($id) {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tbl WHERE id = %d", (int) $id), ARRAY_A);
        if (!$row) return null;
        $profile = json_decode($row['profile_json'], true);
        if (is_array($profile)) {
            $row['profile'] = $profile;
        }
        return $row;
    }

    public static function save_profile($name, $profile_data, $articles_count) {
        global $wpdb;
        self::maybe_create_table();
        $tbl = $wpdb->prefix . self::TABLE;
        $now = current_time('mysql', true);
        $name = sanitize_text_field($name);
        if (empty($name)) $name = 'Profilo ' . date('Y-m-d H:i');

        $insert = $wpdb->insert(
            $tbl,
            array(
                'name'           => $name,
                'profile_json'   => wp_json_encode($profile_data),
                'articles_count' => (int) $articles_count,
                'is_active'      => 0,
                'created_at'     => $now,
                'updated_at'     => $now,
            ),
            array('%s', '%s', '%d', '%d', '%s', '%s')
        );
        if ($insert === false) return array('success' => false, 'error' => $wpdb->last_error);
        return array('success' => true, 'id' => (int) $wpdb->insert_id);
    }

    public static function activate_profile($id) {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE;
        // Disattiva tutti gli altri (solo 1 attivo alla volta)
        $wpdb->query("UPDATE $tbl SET is_active = 0");
        $result = $wpdb->update($tbl, array('is_active' => 1, 'updated_at' => current_time('mysql', true)), array('id' => (int) $id), array('%d', '%s'), array('%d'));
        return array('success' => $result !== false);
    }

    public static function deactivate_all() {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE;
        $wpdb->query("UPDATE $tbl SET is_active = 0, updated_at = '" . current_time('mysql', true) . "'");
        return array('success' => true);
    }

    public static function delete_profile($id) {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE;
        $result = $wpdb->delete($tbl, array('id' => (int) $id), array('%d'));
        return array('success' => $result !== false);
    }

    /**
     * Aggiorna i campi modificabili del profilo (post-creazione, edit utente).
     */
    public static function update_profile_data($id, $name, $profile_updates) {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tbl WHERE id = %d", (int) $id), ARRAY_A);
        if (!$row) return array('success' => false, 'error' => 'Profilo non trovato');

        $current = json_decode($row['profile_json'], true);
        if (!is_array($current)) $current = array();

        $short_fields = array('tone', 'avg_sentence_length', 'avg_paragraph_length');
        foreach ($short_fields as $f) {
            if (isset($profile_updates[$f])) {
                $current[$f] = sanitize_text_field((string) $profile_updates[$f]);
            }
        }
        $long_fields = array('tone_description', 'typical_structure', 'audience', 'cta_style', 'summary_for_prompt');
        foreach ($long_fields as $f) {
            if (isset($profile_updates[$f])) {
                $current[$f] = wp_kses_post(substr((string) $profile_updates[$f], 0, 2000));
            }
        }
        if (isset($profile_updates['distinctive_vocabulary']) && is_array($profile_updates['distinctive_vocabulary'])) {
            $current['distinctive_vocabulary'] = array_values(array_filter(array_map(function ($w) {
                return sanitize_text_field(substr((string) $w, 0, 80));
            }, $profile_updates['distinctive_vocabulary'])));
            $current['distinctive_vocabulary'] = array_slice($current['distinctive_vocabulary'], 0, 30);
        }
        if (isset($profile_updates['avoid_words']) && is_array($profile_updates['avoid_words'])) {
            $current['avoid_words'] = array_values(array_filter(array_map(function ($w) {
                return sanitize_text_field(substr((string) $w, 0, 80));
            }, $profile_updates['avoid_words'])));
            $current['avoid_words'] = array_slice($current['avoid_words'], 0, 20);
        }

        $update_fields = array('profile_json' => wp_json_encode($current), 'updated_at' => current_time('mysql', true));
        $update_format = array('%s', '%s');
        if ($name !== null && $name !== '') {
            $update_fields['name'] = sanitize_text_field($name);
            $update_format[] = '%s';
        }

        $result = $wpdb->update($tbl, $update_fields, array('id' => (int) $id), $update_format, array('%d'));
        if ($result === false) return array('success' => false, 'error' => $wpdb->last_error ?: 'DB update fallito');
        return array('success' => true, 'profile' => $current);
    }

    /**
     * Helper per raccogliere i contenuti degli ultimi N articoli pubblicati.
     * Utilizzato dall'AJAX handler per costruire il payload dell'analisi.
     */
    public static function fetch_recent_articles($limit = 15, $post_type = 'post') {
        $posts = get_posts(array(
            'post_type'        => $post_type,
            'post_status'      => 'publish',
            'posts_per_page'   => max(3, min(20, $limit)),
            'orderby'          => 'modified',
            'order'            => 'DESC',
            'no_found_rows'    => true,
            'suppress_filters' => true,
        ));
        $out = array();
        foreach ($posts as $p) {
            $stripped = wp_strip_all_tags(strip_shortcodes((string) $p->post_content));
            $stripped = preg_replace('/\s+/', ' ', $stripped);
            $stripped = trim(mb_substr($stripped, 0, 3000));
            if (mb_strlen($stripped) < 200) continue; // skip stub articles
            $out[] = array(
                'title'   => get_the_title($p),
                'content' => $stripped,
                'excerpt' => trim((string) $p->post_excerpt),
            );
        }
        return $out;
    }
}

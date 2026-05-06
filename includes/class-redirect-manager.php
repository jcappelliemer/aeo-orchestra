<?php
// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.InputNotValidated
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com
 *
 * Native Redirect Manager (v3.15.0 — Fase 1E strategia "switch da Yoast")
 *
 * Tabelle:
 *   - {prefix}seo_aeo_redirects: source_path → target_url + tipo (301/302/307) + hit counter
 *   - {prefix}seo_aeo_404_log: log dei 404 ricevuti per suggerire redirect
 *
 * Hook:
 *   - template_redirect priority 1: intercetta TUTTE le richieste, match contro
 *     i redirect attivi, esegue wp_redirect() se trova match.
 *   - template_redirect priority 99: se la richiesta è arrivata a 404, logga in 404_log
 *
 * Setting:
 *   - seo_aeo_native_redirect_enabled (default '0' = OFF)
 *
 * Match supportati:
 *   - Exact match: source_path = '/old/page/' → target
 *   - Regex match: source_path = '#^/old/(.+)$#' → target con $1 sostituibile
 *
 * NOT in scope MVP: import da Yoast Premium / Redirection plugin (futuro 3.15.x)
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Redirect_Manager {

    const OPTION_ENABLED = 'seo_aeo_native_redirect_enabled';
    const TABLE_REDIRECTS = 'seo_aeo_redirects';
    const TABLE_404_LOG   = 'seo_aeo_404_log';
    const MAX_404_LOG     = 5000;
    const DB_VERSION_OPT  = 'seo_aeo_redirect_db_version';
    const DB_VERSION      = '1.0';

    public function __construct() {
        // Crea tabelle se non esistono (lazy migration)
        add_action('init', array(__CLASS__, 'maybe_create_tables'));

        // Hook redirect (priority 1: prima di qualsiasi cosa)
        add_action('template_redirect', array($this, 'maybe_redirect'), 1);

        // Logger 404 (priority 99: alla fine di template_redirect)
        add_action('template_redirect', array($this, 'maybe_log_404'), 99);
    }

    public static function is_enabled() {
        return get_option(self::OPTION_ENABLED, '0') === '1';
    }

    /**
     * Crea le tabelle DB. Chiamato lazy ad ogni admin_init.
     * Usa dbDelta per essere idempotente.
     */
    public static function maybe_create_tables() {
        $stored = get_option(self::DB_VERSION_OPT, '');
        if ($stored === self::DB_VERSION) return;

        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $tbl_redirects = $wpdb->prefix . self::TABLE_REDIRECTS;
        $tbl_404 = $wpdb->prefix . self::TABLE_404_LOG;

        $sql_redirects = "CREATE TABLE $tbl_redirects (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            source_path VARCHAR(255) NOT NULL,
            target_url VARCHAR(500) NOT NULL,
            redirect_type SMALLINT NOT NULL DEFAULT 301,
            is_regex TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            hits INT UNSIGNED NOT NULL DEFAULT 0,
            last_used_at DATETIME DEFAULT NULL,
            notes VARCHAR(500) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_source (source_path(191)),
            KEY idx_active (is_active)
        ) $charset_collate;";

        $sql_404 = "CREATE TABLE $tbl_404 (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            request_path VARCHAR(500) NOT NULL,
            referer VARCHAR(500) DEFAULT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            hits INT UNSIGNED NOT NULL DEFAULT 1,
            first_seen_at DATETIME NOT NULL,
            last_seen_at DATETIME NOT NULL,
            resolved TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY idx_path (request_path(191)),
            KEY idx_resolved (resolved)
        ) $charset_collate;";

        dbDelta($sql_redirects);
        dbDelta($sql_404);

        update_option(self::DB_VERSION_OPT, self::DB_VERSION);
    }

    /**
     * Hook principale: intercetta la richiesta corrente, cerca un match nei redirect attivi,
     * esegue wp_redirect() con il tipo corretto se trovato.
     */
    public function maybe_redirect() {
        if (!self::is_enabled()) return;
        if (is_admin()) return;
        if (defined('REST_REQUEST') && REST_REQUEST) return;
        if (defined('DOING_CRON') && DOING_CRON) return;

        $request_path = $this->normalize_path(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) ?? '');
        if (empty($request_path)) return;

        $match = $this->find_redirect_match($request_path);
        if (!$match) return;

        $target = $match['target_url'];
        // Sostituisci backreferences regex se applicabile
        if (!empty($match['is_regex']) && !empty($match['_regex_groups'])) {
            $target = preg_replace_callback('/\$(\d+)/', function ($m) use ($match) {
                $idx = (int) $m[1];
                return isset($match['_regex_groups'][$idx]) ? $match['_regex_groups'][$idx] : '';
            }, $target);
        }

        // Track hit (best-effort, non bloccare il redirect se fallisce)
        try {
            $this->increment_hit($match['id']);
        } catch (Throwable $e) {
        }

        $type = (int) $match['redirect_type'];
        if (!in_array($type, array(301, 302, 307, 308), true)) $type = 301;

        // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- intentional, user-defined redirect targets in Redirect Manager
        wp_redirect(esc_url_raw($target), $type);
        exit;
    }

    /**
     * Logger dei 404: esegue alla fine di template_redirect.
     */
    public function maybe_log_404() {
        if (!self::is_enabled()) return;
        if (is_admin()) return;
        if (!is_404()) return;

        $request_path = $this->normalize_path(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) ?? '');
        if (empty($request_path) || $request_path === '/') return;

        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE_404_LOG;
        $now = current_time('mysql', true);

        // Upsert: se esiste, incrementa hits e aggiorna last_seen_at
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, hits FROM $tbl WHERE request_path = %s LIMIT 1",
            $request_path
        ));

        if ($existing) {
            $wpdb->update(
                $tbl,
                array(
                    'hits' => (int) $existing->hits + 1,
                    'last_seen_at' => $now,
                ),
                array('id' => $existing->id),
                array('%d', '%s'),
                array('%d')
            );
        } else {
            // Limita la dimensione del log
            $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tbl");
            if ($count >= self::MAX_404_LOG) {
                // Cancella il più vecchio non risolto
                            $wpdb->query("DELETE FROM $tbl WHERE resolved = 0 ORDER BY last_seen_at ASC LIMIT 1");
            }
            $wpdb->insert(
                $tbl,
                array(
                    'request_path'  => $request_path,
                    'referer'       => substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER'])) ?? '', 0, 500),
                    'user_agent'    => substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) ?? '', 0, 255),
                    'hits'          => 1,
                    'first_seen_at' => $now,
                    'last_seen_at'  => $now,
                    'resolved'      => 0,
                ),
                array('%s', '%s', '%s', '%d', '%s', '%s', '%d')
            );
        }
    }

    /**
     * Trova un redirect attivo che matcha $request_path.
     * Strategy:
     *   1. Cerca exact match (più veloce, query indicizzata)
     *   2. Se non trova, scorre i redirect regex e prova ognuno
     */
    private function find_redirect_match($request_path) {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE_REDIRECTS;

        // Exact match
        $exact = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tbl WHERE source_path = %s AND is_active = 1 AND is_regex = 0 LIMIT 1",
            $request_path
        ), ARRAY_A);
        if ($exact) return $exact;

        // Anche con/senza trailing slash
        $alt = (substr($request_path, -1) === '/') ? rtrim($request_path, '/') : $request_path . '/';
        $exact_alt = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tbl WHERE source_path = %s AND is_active = 1 AND is_regex = 0 LIMIT 1",
            $alt
        ), ARRAY_A);
        if ($exact_alt) return $exact_alt;

        // Regex match
        $regexes = $wpdb->get_results(
            "SELECT * FROM $tbl WHERE is_active = 1 AND is_regex = 1 ORDER BY id ASC LIMIT 500",
            ARRAY_A
        );
        foreach ($regexes as $r) {
            $pattern = $r['source_path'];
            // Auto-wrap se non delimitato (sicurezza: usiamo '#')
            if (!preg_match('/^[\/#~|@!].*[\/#~|@!][a-z]*$/i', $pattern)) {
                $pattern = '#' . str_replace('#', '\\#', $pattern) . '#';
            }
            $matches = array();
            $result = @preg_match($pattern, $request_path, $matches);
            if ($result === 1) {
                $r['_regex_groups'] = $matches;
                return $r;
            }
        }

        return null;
    }

    private function increment_hit($id) {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE_REDIRECTS;
        $wpdb->query($wpdb->prepare(
            "UPDATE $tbl SET hits = hits + 1, last_used_at = %s WHERE id = %d",
            current_time('mysql', true),
            (int) $id
        ));
    }

    /**
     * Normalizza un URI: rimuove host, query string, fragment. Tiene solo il path.
     */
    private function normalize_path($uri) {
        $path = wp_parse_url($uri, PHP_URL_PATH);
        if (!$path) return '';
        // Aggiungi sempre / iniziale
        if (substr($path, 0, 1) !== '/') $path = '/' . $path;
        return $path;
    }

    // ─── CRUD API per AJAX handlers ────────────────────────────────────────

    public static function list_redirects($limit = 100, $offset = 0, $search = '') {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE_REDIRECTS;
        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tbl WHERE source_path LIKE %s OR target_url LIKE %s ORDER BY updated_at DESC LIMIT %d OFFSET %d",
                $like, $like, $limit, $offset
            ), ARRAY_A);
        }
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tbl ORDER BY updated_at DESC LIMIT %d OFFSET %d",
            $limit, $offset
        ), ARRAY_A);
    }

    public static function count_redirects() {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE_REDIRECTS;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $tbl");
    }

    public static function add_redirect($source, $target, $type = 301, $is_regex = false, $is_active = true, $notes = '') {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE_REDIRECTS;
        $now = current_time('mysql', true);

        // Validazioni
        $source = trim($source);
        $target = trim($target);
        if (empty($source) || empty($target)) {
            return array('success' => false, 'error' => 'source_path e target_url sono obbligatori.');
        }
        if (!in_array((int) $type, array(301, 302, 307, 308), true)) {
            return array('success' => false, 'error' => 'redirect_type non valido. Usa 301, 302, 307 o 308.');
        }
        // Valida regex se è regex
        if ($is_regex) {
            $pattern = $source;
            if (!preg_match('/^[\/#~|@!].*[\/#~|@!][a-z]*$/i', $pattern)) {
                $pattern = '#' . str_replace('#', '\\#', $pattern) . '#';
            }
            if (@preg_match($pattern, '') === false) {
                return array('success' => false, 'error' => 'Pattern regex non valido: ' . $source);
            }
        }
        // Previene loop banali
        if ($source === $target) {
            return array('success' => false, 'error' => 'source e target sono identici (creerebbe loop).');
        }

        $inserted = $wpdb->insert($tbl, array(
            'source_path'   => $source,
            'target_url'    => $target,
            'redirect_type' => (int) $type,
            'is_regex'      => $is_regex ? 1 : 0,
            'is_active'     => $is_active ? 1 : 0,
            'hits'          => 0,
            'notes'         => substr((string) $notes, 0, 500),
            'created_at'    => $now,
            'updated_at'    => $now,
        ), array('%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s'));

        if ($inserted === false) {
            return array('success' => false, 'error' => $wpdb->last_error ?: 'DB insert fallito.');
        }
        return array('success' => true, 'id' => $wpdb->insert_id);
    }

    public static function update_redirect($id, $fields) {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE_REDIRECTS;
        $allowed = array('source_path', 'target_url', 'redirect_type', 'is_regex', 'is_active', 'notes');
        $update = array();
        foreach ($allowed as $f) {
            if (isset($fields[$f])) $update[$f] = $fields[$f];
        }
        if (empty($update)) {
            return array('success' => false, 'error' => 'Nessun campo da aggiornare.');
        }
        $update['updated_at'] = current_time('mysql', true);
        $result = $wpdb->update($tbl, $update, array('id' => (int) $id));
        if ($result === false) {
            return array('success' => false, 'error' => $wpdb->last_error);
        }
        return array('success' => true);
    }

    public static function delete_redirect($id) {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE_REDIRECTS;
        $result = $wpdb->delete($tbl, array('id' => (int) $id), array('%d'));
        return array('success' => $result !== false);
    }

    public static function list_404s($limit = 100, $offset = 0) {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE_404_LOG;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tbl WHERE resolved = 0 ORDER BY hits DESC, last_seen_at DESC LIMIT %d OFFSET %d",
            $limit, $offset
        ), ARRAY_A);
    }

    public static function set_enabled($enabled) {
        update_option(self::OPTION_ENABLED, $enabled ? '1' : '0');
        if ($enabled) self::maybe_create_tables();
    }
}

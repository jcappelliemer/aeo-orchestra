<?php
/**
 * AEO Orchestra - Snapshot Manager v2
 *
 * Cambiamenti rispetto a v1:
 * - Fix bug undo "Snapshot non trovato": compressione gzdeflate + base64 dei dati
 *   per evitare problemi di size limit di wp_postmeta (default 65KB) con post_content
 *   pieni di Elementor JSON / HTML lungo
 * - Logging dettagliato di create/get/restore per diagnostica futura
 * - Verifica immediata read-after-write per fail fast
 * - Safety net: se update_post_meta non scrive, ritorna null e NON aggiorna l'index
 *   (evita stato inconsistente come quello che ti era successo)
 *
 * @package SEO_AEO_Orchestra
 * @since 3.2.2
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Snapshot_Manager {

    const SNAPSHOT_META_PREFIX = '_seo_aeo_snapshot_';
    const INDEX_OPTION_KEY     = 'seo_aeo_snapshot_index';
    const TTL_SECONDS          = 604800; // 7 giorni
    const MAX_INDEX_ENTRIES    = 500;
    const COMPRESS_THRESHOLD   = 2048;   // se serializzato > 2KB, comprime

    /**
     * Crea uno snapshot. Ritorna snapshot_id o null.
     */
    public function create_snapshot($post_id, $proposal_id, $previous_state, $applied_state, $meta = array()) {
        $post_id = intval($post_id);
        if ($post_id <= 0) {
            return null;
        }

        $now         = time();
        $snapshot_id = 'snap_' . $now . '_' . wp_generate_password(6, false, false);
        $meta_key    = self::SNAPSHOT_META_PREFIX . $snapshot_id;

        $applied_by = get_current_user_id();
        $post       = get_post($post_id);

        // 3.42.1 #1 — enrichment: derive agent + byte_delta + page_short
        // from caller-supplied $meta for Modifiche recenti UI label rewrite.
        $agent_name = isset($meta['agent']) ? (string) $meta['agent'] : '';
        $byte_delta = isset($meta['byte_delta']) ? (int) $meta['byte_delta'] : 0;
        if ($byte_delta === 0) {
            // Fallback: compute from state strlen diff if both sides serializable.
            $prev_size = is_string($previous_state) ? strlen($previous_state) : strlen(maybe_serialize($previous_state));
            $next_size = is_string($applied_state) ? strlen($applied_state)  : strlen(maybe_serialize($applied_state));
            $byte_delta = $next_size - $prev_size;
        }
        $page_title_full = $post ? $post->post_title : '';
        $page_short = mb_strlen($page_title_full) > 40 ? (mb_substr($page_title_full, 0, 38) . '…') : $page_title_full;

        $data = array(
            'snapshot_id'    => $snapshot_id,
            'proposal_id'    => $proposal_id,
            'post_id'        => $post_id,
            'post_title'     => $page_title_full,
            'page_short'     => $page_short,   // 3.42.1 #1
            'agent'          => $agent_name,   // 3.42.1 #1
            'byte_delta'     => $byte_delta,   // 3.42.1 #1
            'applied_at'     => $now,
            'applied_by'     => $applied_by,
            'expires_at'     => $now + self::TTL_SECONDS,
            'previous_state' => $previous_state,
            'applied_state'  => $applied_state,
        );

        // Serializzazione + compressione opzionale
        $payload = $this->encode_payload($data);
        if ($payload === false) {
            return null;
        }

        $payload_size = strlen($payload);

        // Salva nel postmeta
        $saved = update_post_meta($post_id, $meta_key, $payload);

        // saved può essere: meta_id (int>0), true (update), false (errore o stesso valore)
        // Verifico SEMPRE con read-after-write
        $verify = get_post_meta($post_id, $meta_key, true);
        if (empty($verify) || strlen($verify) !== $payload_size) {
            // Pulizia: cancello eventuale meta parziale
            delete_post_meta($post_id, $meta_key);
            return null;
        }


        // Solo dopo verifica OK aggiorno l'index
        $this->add_to_index(array(
            'snapshot_id' => $snapshot_id,
            'post_id'     => $post_id,
            'post_title'  => $page_title_full,
            'page_short'  => $page_short,   // 3.42.1 #1
            'agent'       => $agent_name,   // 3.42.1 #1
            'byte_delta'  => $byte_delta,   // 3.42.1 #1
            'proposal_id' => $proposal_id,
            'applied_at'  => $now,
            'expires_at'  => $now + self::TTL_SECONDS,
            'applied_by'  => $applied_by,
        ));

        return $snapshot_id;
    }

    /**
     * Recupera uno snapshot completo.
     */
    public function get_snapshot($snapshot_id) {
        $snapshot_id = sanitize_text_field($snapshot_id);
        if (empty($snapshot_id)) {
            return null;
        }

        // Trovo il post_id dall'index
        $index = $this->get_index();
        $entry = null;
        foreach ($index as $e) {
            if (isset($e['snapshot_id']) && $e['snapshot_id'] === $snapshot_id) {
                $entry = $e;
                break;
            }
        }
        if (!$entry) {
            return null;
        }

        $meta_key = self::SNAPSHOT_META_PREFIX . $snapshot_id;
        // FIX: invalida cache prima di leggere (Elementor/Yoast possono avere cache stale)
        wp_cache_delete($entry['post_id'], 'post_meta');
        $raw = get_post_meta($entry['post_id'], $meta_key, true);
        if (empty($raw)) {
            // Self-heal: rimuovo la entry orfana dall'index per evitare ripetizioni
            $this->remove_from_index($snapshot_id);
            return null;
        }

        $data = $this->decode_payload($raw);
        if (!is_array($data)) {
            // Self-heal: payload corrotto, rimuovo dall'index e cancello postmeta orfano
            delete_post_meta($entry['post_id'], $meta_key);
            $this->remove_from_index($snapshot_id);
            return null;
        }
        return $data;
    }

    /**
     * Ripristina uno snapshot.
     */
    public function restore_snapshot($snapshot_id) {
        $snapshot = $this->get_snapshot($snapshot_id);
        if (!$snapshot) {
            return array('success' => false, 'message' => 'Snapshot non trovato.');
        }

        $expires_at = isset($snapshot['expires_at']) ? intval($snapshot['expires_at']) : 0;
        if ($expires_at > 0 && $expires_at < time()) {
            return array('success' => false, 'message' => 'Snapshot scaduto (>7 giorni).');
        }

        $post_id = intval($snapshot['post_id']);
        if ($post_id <= 0) {
            return array('success' => false, 'message' => 'post_id non valido.');
        }

        $previous = isset($snapshot['previous_state']) ? $snapshot['previous_state'] : array();

        // Ripristino wp_posts
        $updates = array('ID' => $post_id);
        if (isset($previous['post_content'])) {
            $updates['post_content'] = $previous['post_content'];
        }
        if (isset($previous['post_title']) && $previous['post_title'] !== '') {
            $updates['post_title'] = $previous['post_title'];
        }
        if (count($updates) > 1) {
            wp_update_post($updates);
        }

        // FIX 3.3.9: ripristino selettivo per-campo via restore_meta() del bridge
        // Per ogni campo: se previous era vuoto → cancella; se popolato → scrivi
        // Risolve bug 3.3.8 dove write_meta() ignorava stringhe vuote lasciando orfani
        $has_meta_to_restore = isset($previous['meta_title']) || isset($previous['meta_description']) || isset($previous['meta_keywords']);
        if ($has_meta_to_restore && class_exists('SEO_AEO_SEO_Engine_Bridge')) {
            $bridge_result = SEO_AEO_SEO_Engine_Bridge::restore_meta($post_id, $previous);
        } else {
            // Fallback se bridge non caricato: solo Orchestra native
            if (isset($previous['meta_title'])) {
                update_post_meta($post_id, '_seo_aeo_meta_title', $previous['meta_title']);
            }
            if (isset($previous['meta_description'])) {
                update_post_meta($post_id, '_seo_aeo_meta_description', $previous['meta_description']);
            }
            if (isset($previous['meta_keywords'])) {
                update_post_meta($post_id, '_seo_aeo_meta_keywords', $previous['meta_keywords']);
            }
        }

        // Cancella lo snapshot (undo è una-tantum)
        $meta_key = self::SNAPSHOT_META_PREFIX . $snapshot_id;
        delete_post_meta($post_id, $meta_key);
        $this->remove_from_index($snapshot_id);

        return array('success' => true, 'message' => 'Ripristinato.', 'post_id' => $post_id);
    }

    /**
     * Lista snapshot attivi.
     */
    public function list_snapshots($limit = 20) {
        $index = $this->get_index();

        usort($index, function($a, $b) {
            return intval($b['applied_at']) - intval($a['applied_at']);
        });

        $now = time();
        $out = array();
        foreach (array_slice($index, 0, $limit) as $entry) {
            $expires_at     = intval($entry['expires_at']);
            $seconds_left   = max(0, $expires_at - $now);
            $days_remaining = intval(ceil($seconds_left / 86400));

            $entry['days_remaining'] = $days_remaining;
            $entry['is_expired']     = ($seconds_left <= 0);
            $entry['post_edit_url']  = get_edit_post_link($entry['post_id'], 'raw');
            $out[] = $entry;
        }
        return $out;
    }

    /**
     * Cancella snapshot scaduti. Chiamato dal cron.
     */
    public function cleanup_expired() {
        $index = $this->get_index();
        $now = time();
        $kept = array();
        $deleted = 0;

        foreach ($index as $entry) {
            $expires_at = intval($entry['expires_at']);
            if ($expires_at > 0 && $expires_at < $now) {
                $meta_key = self::SNAPSHOT_META_PREFIX . $entry['snapshot_id'];
                delete_post_meta($entry['post_id'], $meta_key);
                $deleted++;
            } else {
                $kept[] = $entry;
            }
        }

        if ($deleted > 0) {
            update_option(self::INDEX_OPTION_KEY, $kept, false);
        }

        return $deleted;
    }

    // ────────────────────────────────────────────────────────
    // Encoding payload (FIX BUG: compressione per size>2KB)
    // ────────────────────────────────────────────────────────

    /**
     * Serializza + (se grande) comprime + base64.
     * Prefisso 'gz:' indica payload compresso, 'js:' JSON puro.
     */
    private function encode_payload($data) {
        $json = wp_json_encode($data);
        if ($json === false) {
            // Fallback: serialize PHP
            $serialized = @serialize($data);
            if ($serialized === false) return false;
            return 'sr:' . base64_encode($serialized);
        }

        // Se piccolo, lascio JSON plain (più leggibile in debug)
        if (strlen($json) < self::COMPRESS_THRESHOLD) {
            return 'js:' . $json;
        }

        // Comprimo con gzdeflate (raw deflate, no header)
        $compressed = gzdeflate($json, 9);
        if ($compressed === false) {
            // Se compressione fallisce, salvo JSON plain
            return 'js:' . $json;
        }
        return 'gz:' . base64_encode($compressed);
    }

    /**
     * Inverso di encode_payload.
     */
    private function decode_payload($raw) {
        if (!is_string($raw) || strlen($raw) < 4) return null;
        // FIX: WordPress applica wp_slash() su update_post_meta, dobbiamo unslash prima di JSON parse
        $raw_unslashed = wp_unslash($raw);
        $prefix = substr($raw_unslashed, 0, 3);
        $body   = substr($raw_unslashed, 3);

        switch ($prefix) {
            case 'js:':
                $data = json_decode($body, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
                return is_array($data) ? $data : null;

            case 'gz:':
                $compressed = base64_decode($body, true);
                if ($compressed === false) return null;
                $json = gzinflate($compressed);
                if ($json === false) return null;
                $data = json_decode($json, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
                return is_array($data) ? $data : null;

            case 'sr:':
                $serialized = base64_decode($body, true);
                if ($serialized === false) return null;
                $data = @unserialize($serialized);
                return is_array($data) ? $data : null;

            default:
                // Compatibilità v1: JSON plain con eventuale slash escape + UTF8 substitute per encoding bug
                $data = json_decode($raw_unslashed, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
                return is_array($data) ? $data : null;
        }
    }

    // ────────────────────────────────────────────────────────
    // Index management (wp_options)
    // ────────────────────────────────────────────────────────

    private function get_index() {
        $idx = get_option(self::INDEX_OPTION_KEY, array());
        return is_array($idx) ? $idx : array();
    }

    private function add_to_index($entry) {
        $index = $this->get_index();
        $index[] = $entry;

        if (count($index) > self::MAX_INDEX_ENTRIES) {
            usort($index, function($a, $b) {
                return intval($b['applied_at']) - intval($a['applied_at']);
            });
            $index = array_slice($index, 0, self::MAX_INDEX_ENTRIES);
        }

        update_option(self::INDEX_OPTION_KEY, $index, false);
    }

    private function remove_from_index($snapshot_id) {
        $index = $this->get_index();
        $filtered = array_values(array_filter($index, function($e) use ($snapshot_id) {
            return isset($e['snapshot_id']) && $e['snapshot_id'] !== $snapshot_id;
        }));
        update_option(self::INDEX_OPTION_KEY, $filtered, false);
    }

    // ────────────────────────────────────────────────────────
    // Cron setup
    // ────────────────────────────────────────────────────────

    const CRON_HOOK = 'seo_aeo_snapshot_cleanup_cron';

    public static function register_cron() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 3600, 'daily', self::CRON_HOOK);
        }
    }

    public static function unregister_cron() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
    }

    public static function cron_callback() {
        $mgr = new self();
        $mgr->cleanup_expired();
    }
}

add_action(SEO_AEO_Snapshot_Manager::CRON_HOOK, array('SEO_AEO_Snapshot_Manager', 'cron_callback'));

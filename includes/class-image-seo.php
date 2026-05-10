<?php
error_reporting(0);
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com. All rights reserved.
 * Unauthorized copying, redistribution or resale is strictly prohibited.
 */
/**
 * Image SEO Manager (3.34.0)
 *
 * Audit di tutte le immagini del sito (post_type=attachment, mime image/*) +
 * bulk fix metadata (alt/title/caption/description) via Gemini Vision API.
 *
 * Storage: WP options + WP postmeta (no tabelle custom — usa standard WP).
 *  - `_wp_attachment_image_alt` postmeta → alt
 *  - wp_posts.post_title → title
 *  - wp_posts.post_excerpt → caption
 *  - wp_posts.post_content → description
 *
 * Cron WP `seo_aeo_orchestra_image_seo_tick` (ogni minuto, batch=5):
 *   1. legge queue da option `seo_aeo_image_seo_bulk_queue`
 *   2. per ogni attach_id: chiama backend /ai/generate-image-metadata
 *   3. persiste solo i campi vuoti (idempotency: NON sovrascrive valori esistenti)
 *   4. update progress, log errori
 *
 * Rate limit Gemini Vision: max 5 immagini per tick → throttle naturale 5/min.
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_Image_SEO {

    const QUEUE_OPT = 'seo_aeo_image_seo_bulk_queue';
    const PREVIEW_OPT_PREFIX = 'seo_aeo_image_seo_bulk_preview_'; // + user_id
    const CRON_HOOK = 'seo_aeo_orchestra_image_seo_tick';
    const CRON_SCHEDULE = 'orch_image_seo_minutely';
    const TICK_BATCH = 5;
    const CHANGELOG_CAP = 500; // cap entries to avoid option bloat

    public static function init() {
        add_filter('cron_schedules', array(__CLASS__, 'register_schedule'));
        add_action(self::CRON_HOOK, array(__CLASS__, 'process_tick'));
        // Auto-resume cron se queue esistente al boot (es. dopo restart WP)
        add_action('init', array(__CLASS__, 'maybe_resume_cron'));
    }

    public static function register_schedule($schedules) {
        if (!isset($schedules[self::CRON_SCHEDULE])) {
            $schedules[self::CRON_SCHEDULE] = array(
                'interval' => 60,
                'display'  => 'Every minute (Image SEO bulk)',
            );
        }
        return $schedules;
    }

    public static function maybe_resume_cron() {
        $state = get_option(self::QUEUE_OPT);
        if (!is_array($state) || empty($state['queue'])) return;
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 30, self::CRON_SCHEDULE, self::CRON_HOOK);
        }
    }

    public static function deactivate_cron() {
        $next = wp_next_scheduled(self::CRON_HOOK);
        if ($next) wp_unschedule_event($next, self::CRON_HOOK);
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    /**
     * Avvia bulk job per array di attachment IDs.
     * @param array $attach_ids
     * @param string $mode 'apply' (default, scrive direttamente) o 'preview' (genera senza applicare, results in transient)
     * @param int $user_id User che ha lanciato il job (usato per chiave transient preview)
     * @return array {queued, estimated_minutes}
     */
    public static function start_bulk($attach_ids, $mode = 'apply', $user_id = 0) {
        $queue = array_values(array_unique(array_filter(array_map('intval', (array) $attach_ids))));
        $now = time();
        $mode = ($mode === 'preview') ? 'preview' : 'apply';
        $user_id = intval($user_id);
        if ($user_id <= 0 && function_exists('get_current_user_id')) {
            $user_id = (int) get_current_user_id();
        }
        update_option(self::QUEUE_OPT, array(
            'started_at'   => $now,
            'total'        => count($queue),
            'processed'    => 0,
            'errors'       => 0,
            'error_log'    => array(),
            'queue'        => $queue,
            'completed_at' => null,
            'paused'       => false,
            'credits_used' => 0,
            'mode'         => $mode,
            'user_id'      => $user_id,
            'changelog'    => array(),
        ));
        // Reset preview transient se preview mode
        if ($mode === 'preview' && $user_id > 0) {
            delete_transient(self::PREVIEW_OPT_PREFIX . $user_id);
        }
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event($now + 5, self::CRON_SCHEDULE, self::CRON_HOOK);
        }
        return array(
            'queued'             => count($queue),
            'estimated_minutes'  => max(1, ceil(count($queue) / self::TICK_BATCH)),
            'mode'               => $mode,
        );
    }

    /**
     * Status snapshot della queue.
     * @return array
     */
    public static function status() {
        $state = get_option(self::QUEUE_OPT);
        if (!is_array($state)) {
            return array('active' => false, 'total' => 0, 'processed' => 0, 'errors' => 0, 'remaining' => 0);
        }
        $remaining = is_array($state['queue'] ?? null) ? count($state['queue']) : 0;
        $active = $remaining > 0 && empty($state['paused']);
        $changelog_count = is_array($state['changelog'] ?? null) ? count($state['changelog']) : 0;
        return array(
            'active'           => $active,
            'paused'           => !empty($state['paused']),
            'total'            => intval($state['total'] ?? 0),
            'processed'        => intval($state['processed'] ?? 0),
            'errors'           => intval($state['errors'] ?? 0),
            'remaining'        => $remaining,
            'credits_used'     => intval($state['credits_used'] ?? 0),
            'started_at'       => $state['started_at'] ?? null,
            'completed_at'     => $state['completed_at'] ?? null,
            'error_log'        => array_slice((array) ($state['error_log'] ?? array()), -10),
            'mode'             => $state['mode'] ?? 'apply',
            'changelog_count'  => $changelog_count,
        );
    }

    /**
     * Restituisce changelog raw del job corrente o ultimo completato.
     * @return array
     */
    public static function changelog() {
        $state = get_option(self::QUEUE_OPT);
        if (!is_array($state)) return array();
        $log = is_array($state['changelog'] ?? null) ? $state['changelog'] : array();
        return $log;
    }

    /**
     * Restituisce preview results dal transient utente.
     * @param int $user_id
     * @return array (lista di {attach_id, filename, thumb_url, before, suggested})
     */
    public static function preview_results($user_id) {
        $user_id = intval($user_id);
        if ($user_id <= 0) return array();
        $data = get_transient(self::PREVIEW_OPT_PREFIX . $user_id);
        return is_array($data) ? $data : array();
    }

    /**
     * Salva preview entry per un attachment dato.
     */
    public static function append_preview($user_id, $entry) {
        $user_id = intval($user_id);
        if ($user_id <= 0 || !is_array($entry)) return;
        $key = self::PREVIEW_OPT_PREFIX . $user_id;
        $data = get_transient($key);
        if (!is_array($data)) $data = array();
        $data[] = $entry;
        // Cap a 1000 entries
        if (count($data) > 1000) {
            $data = array_slice($data, -1000);
        }
        set_transient($key, $data, 24 * HOUR_IN_SECONDS);
    }

    public static function clear_preview($user_id) {
        $user_id = intval($user_id);
        if ($user_id <= 0) return;
        delete_transient(self::PREVIEW_OPT_PREFIX . $user_id);
    }

    public static function pause() {
        $state = get_option(self::QUEUE_OPT);
        if (is_array($state)) {
            $state['paused'] = true;
            update_option(self::QUEUE_OPT, $state);
        }
    }

    public static function cancel() {
        delete_option(self::QUEUE_OPT);
        self::deactivate_cron();
    }

    /**
     * Cron tick: processa fino a TICK_BATCH immagini.
     */
    public static function process_tick() {
        try {
            $state = get_option(self::QUEUE_OPT);
            if (!is_array($state) || empty($state['queue'])) {
                self::deactivate_cron();
                return;
            }
            if (!empty($state['paused'])) return;

            $batch = array_splice($state['queue'], 0, self::TICK_BATCH);
            $mode = isset($state['mode']) && $state['mode'] === 'preview' ? 'preview' : 'apply';
            $user_id = intval($state['user_id'] ?? 0);
            foreach ($batch as $aid) {
                try {
                    $result = self::process_one($aid, $mode, $user_id);
                    if (!empty($result['success'])) {
                        $state['processed']++;
                        $state['credits_used'] += intval($result['credits_used'] ?? 0);
                        // Track changelog (only for apply mode; preview entries stored in transient)
                        if ($mode === 'apply' && !empty($result['changelog_entry'])) {
                            $state['changelog'][] = $result['changelog_entry'];
                            if (count($state['changelog']) > self::CHANGELOG_CAP) {
                                $state['changelog'] = array_slice($state['changelog'], -self::CHANGELOG_CAP);
                            }
                        }
                    } else {
                        $state['errors']++;
                        $state['error_log'][] = array(
                            'id'    => $aid,
                            'error' => isset($result['error']) ? substr((string) $result['error'], 0, 200) : 'unknown',
                            'ts'    => time(),
                        );
                    }
                    // Anti-burst: pausa 200ms tra immagini (rate-limit Gemini Vision ~5/s)
                    usleep(200000);
                } catch (Throwable $e) {
                    $state['errors']++;
                    $state['error_log'][] = array('id' => $aid, 'error' => substr($e->getMessage(), 0, 200), 'ts' => time());
                }
            }

            if (empty($state['queue'])) {
                $state['completed_at'] = time();
                self::deactivate_cron();
            }
            // Cap error_log a ultimi 50 entries (anti-bloat option)
            if (count($state['error_log']) > 50) {
                $state['error_log'] = array_slice($state['error_log'], -50);
            }
            update_option(self::QUEUE_OPT, $state);
        } catch (Throwable $e) {
            error_log('[SEO_AEO Image SEO] tick FATAL: ' . $e->getMessage());
        }
    }

    /**
     * Processa una singola immagine: chiama backend, salva metadata
     * SOLO sui campi vuoti (idempotency) o stage in preview transient.
     * @param int $attach_id
     * @param string $mode 'apply' (default) o 'preview' (no write, stage in transient)
     * @param int $user_id (richiesto per preview)
     * @return array {success, credits_used, error?, changelog_entry?}
     */
    public static function process_one($attach_id, $mode = 'apply', $user_id = 0) {
        $att = get_post($attach_id);
        if (!$att || $att->post_type !== 'attachment') {
            return array('success' => false, 'error' => 'Attachment non trovato');
        }
        $url = wp_get_attachment_url($attach_id);
        if (empty($url)) {
            return array('success' => false, 'error' => 'URL immagine non disponibile');
        }
        // Skip non-image
        $mime = get_post_mime_type($attach_id);
        if (!is_string($mime) || strpos($mime, 'image/') !== 0) {
            return array('success' => false, 'error' => 'Non è un\'immagine (mime: ' . $mime . ')');
        }
        // Skip se troppo grande (oltre 10MB)
        $file = get_attached_file($attach_id);
        if ($file && file_exists($file) && filesize($file) > 10 * 1024 * 1024) {
            return array('success' => false, 'error' => 'Immagine troppo grande (>10MB), skip');
        }

        $parent = ($att->post_parent > 0) ? get_post($att->post_parent) : null;
        $parent_title = $parent ? (string) $parent->post_title : '';
        $parent_excerpt = $parent ? (string) $parent->post_excerpt : '';

        $api = isset($GLOBALS['seo_aeo_api']) ? $GLOBALS['seo_aeo_api'] : null;
        if (!$api) {
            return array('success' => false, 'error' => 'API client non disponibile');
        }

        $lang = get_option('seo_aeo_orchestra_language', '');
        if (!in_array($lang, array('it', 'en', 'es', 'fr', 'de'), true)) {
            $loc = function_exists('determine_locale') ? determine_locale() : get_locale();
            $lang = strtolower(substr((string) $loc, 0, 2));
            if (!in_array($lang, array('it', 'en', 'es', 'fr', 'de'), true)) $lang = 'it';
        }

        $resp = $api->api_request('/ai/generate-image-metadata', array(
            'image_url'           => $url,
            'parent_post_title'   => $parent_title,
            'parent_post_excerpt' => $parent_excerpt,
            'site_language'       => $lang,
        ));

        if (!is_array($resp) || empty($resp['success']) || empty($resp['metadata'])) {
            $err = '';
            if (is_array($resp)) {
                if (!empty($resp['detail'])) $err = is_string($resp['detail']) ? $resp['detail'] : wp_json_encode($resp['detail']);
                elseif (!empty($resp['message'])) $err = (string) $resp['message'];
                elseif (!empty($resp['error']) && is_string($resp['error'])) $err = $resp['error'];
            }
            return array('success' => false, 'error' => $err ?: 'Backend ha ritornato risposta non valida');
        }

        $md = $resp['metadata'];

        // Snapshot BEFORE values (for changelog/preview)
        $before = array(
            'alt'         => (string) get_post_meta($attach_id, '_wp_attachment_image_alt', true),
            'title'       => (string) $att->post_title,
            'caption'     => (string) $att->post_excerpt,
            'description' => (string) $att->post_content,
        );

        if ($mode === 'preview') {
            // Stage in transient — DO NOT write to DB
            $thumb = wp_get_attachment_image_src($attach_id, 'thumbnail');
            $thumb_url = is_array($thumb) ? $thumb[0] : $url;
            self::append_preview($user_id, array(
                'attach_id'  => intval($attach_id),
                'filename'   => basename(parse_url($url, PHP_URL_PATH)),
                'thumb_url'  => $thumb_url,
                'url'        => $url,
                'before'     => $before,
                'suggested'  => array(
                    'alt'         => isset($md['alt']) ? (string) $md['alt'] : '',
                    'title'       => isset($md['title']) ? (string) $md['title'] : '',
                    'caption'     => isset($md['caption']) ? (string) $md['caption'] : '',
                    'description' => isset($md['description']) ? (string) $md['description'] : '',
                ),
                'ts'         => time(),
            ));
            return array(
                'success'      => true,
                'credits_used' => intval($resp['credits_used'] ?? 2),
            );
        }

        $applied = self::apply_metadata($attach_id, $md, /*overwrite=*/false);

        // Snapshot AFTER values
        $att_after = get_post($attach_id);
        $after = array(
            'alt'         => (string) get_post_meta($attach_id, '_wp_attachment_image_alt', true),
            'title'       => $att_after ? (string) $att_after->post_title : $before['title'],
            'caption'     => $att_after ? (string) $att_after->post_excerpt : $before['caption'],
            'description' => $att_after ? (string) $att_after->post_content : $before['description'],
        );

        $changelog_entry = null;
        if (!empty($applied)) {
            $thumb = wp_get_attachment_image_src($attach_id, 'thumbnail');
            $thumb_url = is_array($thumb) ? $thumb[0] : $url;
            $changelog_entry = array(
                'attach_id' => intval($attach_id),
                'filename'  => basename(parse_url($url, PHP_URL_PATH)),
                'thumb_url' => $thumb_url,
                'before'    => $before,
                'after'     => $after,
                'fields'    => $applied,
                'ts'        => time(),
            );
        }

        return array(
            'success'         => true,
            'credits_used'    => intval($resp['credits_used'] ?? 2),
            'fields_applied'  => $applied,
            'before'          => $before,
            'after'           => $after,
            'changelog_entry' => $changelog_entry,
        );
    }

    /**
     * Persiste metadata su attachment.
     * @param int $attach_id
     * @param array $md {alt, title, caption, description}
     * @param bool $overwrite True per sovrascrivere valori esistenti, False per skip-if-set (idempotency)
     * @return array fields_applied (lista campi effettivamente scritti)
     */
    public static function apply_metadata($attach_id, $md, $overwrite = false) {
        $att = get_post($attach_id);
        if (!$att) return array();

        $applied = array();

        // ALT
        $cur_alt = (string) get_post_meta($attach_id, '_wp_attachment_image_alt', true);
        $new_alt = isset($md['alt']) ? sanitize_text_field((string) $md['alt']) : '';
        if ($new_alt !== '' && ($overwrite || $cur_alt === '')) {
            update_post_meta($attach_id, '_wp_attachment_image_alt', $new_alt);
            $applied[] = 'alt';
        }

        // TITLE / CAPTION / DESCRIPTION via wp_update_post
        $update = array('ID' => $attach_id);
        $changed = false;
        if (!empty($md['title'])) {
            $new_title = sanitize_text_field((string) $md['title']);
            if ($overwrite || empty($att->post_title) || $att->post_title === pathinfo($att->post_title, PATHINFO_FILENAME)) {
                // Considera "filename auto-title" come vuoto (tipico WP che imposta il filename come post_title)
                $update['post_title'] = $new_title;
                $applied[] = 'title';
                $changed = true;
            }
        }
        if (!empty($md['caption'])) {
            $new_cap = sanitize_text_field((string) $md['caption']);
            if ($overwrite || empty($att->post_excerpt)) {
                $update['post_excerpt'] = $new_cap;
                $applied[] = 'caption';
                $changed = true;
            }
        }
        if (!empty($md['description'])) {
            $new_desc = sanitize_textarea_field((string) $md['description']);
            if ($overwrite || empty($att->post_content)) {
                $update['post_content'] = $new_desc;
                $applied[] = 'description';
                $changed = true;
            }
        }
        if ($changed) {
            wp_update_post($update);
        }

        return $applied;
    }

    /**
     * Calcola score 0-4 di un'immagine.
     */
    public static function compute_score($alt, $title, $caption, $description) {
        $s = 0;
        if (!empty(trim((string) $alt))) $s++;
        if (!empty(trim((string) $title))) $s++;
        if (!empty(trim((string) $caption))) $s++;
        if (!empty(trim((string) $description))) $s++;
        return $s;
    }
}

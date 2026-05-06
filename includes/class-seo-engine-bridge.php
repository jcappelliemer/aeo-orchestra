<?php
/**
 * AEO Orchestra - SEO Engine Bridge
 *
 * Rileva quale plugin SEO è attivo (Yoast, RankMath, AIOSEO) e legge/scrive
 * i postmeta nei campi giusti, così Orchestra non viene oscurata.
 *
 * Strategia "doppia scrittura": scrive sia sul plugin SEO rilevato sia su
 * Orchestra native, così disattivando il plugin SEO i dati restano.
 *
 * @since 3.3.0
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_SEO_Engine_Bridge {

    const ENGINE_YOAST    = 'yoast';
    const ENGINE_RANKMATH = 'rankmath';
    const ENGINE_AIOSEO   = 'aioseo';
    const ENGINE_NATIVE   = 'native';

    /**
     * Detect attivo. Cache per request.
     */
    private static $detected_engine = null;

    public static function detect_engine() {
        if (self::$detected_engine !== null) return self::$detected_engine;

        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Priorità: Yoast > RankMath > AIOSEO > native
        if (defined('WPSEO_VERSION') || is_plugin_active('wordpress-seo/wp-seo.php')
            || is_plugin_active('wordpress-seo-premium/wp-seo-premium.php')) {
            self::$detected_engine = self::ENGINE_YOAST;
        } elseif (defined('RANK_MATH_VERSION') || is_plugin_active('seo-by-rank-math/rank-math.php')
                  || is_plugin_active('seo-by-rank-math-pro/rank-math-pro.php')) {
            self::$detected_engine = self::ENGINE_RANKMATH;
        } elseif (defined('AIOSEO_VERSION') || is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')
                  || is_plugin_active('aioseo-pro/aioseo-pro.php')) {
            self::$detected_engine = self::ENGINE_AIOSEO;
        } else {
            self::$detected_engine = self::ENGINE_NATIVE;
        }

        return self::$detected_engine;
    }

    public static function engine_label() {
        $map = array(
            self::ENGINE_YOAST    => 'Yoast SEO',
            self::ENGINE_RANKMATH => 'Rank Math',
            self::ENGINE_AIOSEO   => 'All in One SEO',
            self::ENGINE_NATIVE   => 'Orchestra (nativo)',
        );
        $engine = self::detect_engine();
        return isset($map[$engine]) ? $map[$engine] : 'Sconosciuto';
    }

    /**
     * Legge i meta tag attuali del post, dal plugin SEO attivo + fallback Orchestra.
     * Ritorna array con meta_title, meta_description, meta_keywords (sempre array).
     */
    public static function read_meta($post_id) {
        $post_id = intval($post_id);
        if ($post_id <= 0) return self::empty_meta();

        $engine = self::detect_engine();

        $title = '';
        $desc  = '';
        $kws   = '';

        switch ($engine) {
            case self::ENGINE_YOAST:
                $title = (string) get_post_meta($post_id, '_yoast_wpseo_title', true);
                $desc  = (string) get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
                // Yoast salva una sola "focus keyword"
                $kws   = (string) get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
                break;

            case self::ENGINE_RANKMATH:
                $title = (string) get_post_meta($post_id, 'rank_math_title', true);
                $desc  = (string) get_post_meta($post_id, 'rank_math_description', true);
                // RankMath salva CSV
                $kws   = (string) get_post_meta($post_id, 'rank_math_focus_keyword', true);
                break;

            case self::ENGINE_AIOSEO:
                $title = (string) get_post_meta($post_id, '_aioseo_title', true);
                $desc  = (string) get_post_meta($post_id, '_aioseo_description', true);
                // AIOSEO salva keywords come JSON: [{"label":"kw1","value":"kw1"},...]
                $raw_kws = get_post_meta($post_id, '_aioseo_keywords', true);
                if (!empty($raw_kws)) {
                    $decoded = json_decode($raw_kws, true);
                    if (is_array($decoded)) {
                        $kws = implode(', ', array_map(function($e) {
                            return is_array($e) && isset($e['label']) ? $e['label'] : (string) $e;
                        }, $decoded));
                    } else {
                        $kws = (string) $raw_kws;
                    }
                }
                break;

            case self::ENGINE_NATIVE:
            default:
                // Già Orchestra native, niente da fare qui
                break;
        }

        // Fallback: se il plugin SEO non aveva valori, prova Orchestra native
        if (empty($title)) $title = (string) get_post_meta($post_id, '_seo_aeo_meta_title', true);
        if (empty($desc))  $desc  = (string) get_post_meta($post_id, '_seo_aeo_meta_description', true);
        if (empty($kws))   $kws   = (string) get_post_meta($post_id, '_seo_aeo_meta_keywords', true);

        // Normalizzo keywords in array
        $kws_array = array();
        if (!empty($kws)) {
            $kws_array = array_filter(array_map('trim', explode(',', $kws)));
        }

        return array(
            'meta_title'       => $title,
            'meta_description' => $desc,
            'meta_keywords'    => array_values($kws_array),
            'engine'           => $engine,
            'engine_label'     => self::engine_label(),
        );
    }

    /**
     * Scrive i meta tag sul post. Doppia scrittura: plugin SEO attivo + Orchestra native.
     * $data deve avere meta_title, meta_description, meta_keywords (string CSV o array).
     * Ritorna array con campi scritti e engine usato.
     */
    public static function write_meta($post_id, $data) {
        $post_id = intval($post_id);
        if ($post_id <= 0) return array('success' => false, 'message' => 'post_id non valido');

        $engine = self::detect_engine();
        $written = array();

        // Normalizzo keywords in CSV
        $kws_csv = '';
        if (isset($data['meta_keywords'])) {
            if (is_array($data['meta_keywords'])) {
                $kws_csv = implode(', ', array_filter(array_map('trim', $data['meta_keywords'])));
            } else {
                $kws_csv = (string) $data['meta_keywords'];
            }
        }

        // 1. Scrittura sul plugin SEO attivo
        switch ($engine) {
            case self::ENGINE_YOAST:
                if (isset($data['meta_title']) && $data['meta_title'] !== '') {
                    update_post_meta($post_id, '_yoast_wpseo_title', sanitize_text_field($data['meta_title']));
                    $written[] = '_yoast_wpseo_title';
                }
                if (isset($data['meta_description']) && $data['meta_description'] !== '') {
                    update_post_meta($post_id, '_yoast_wpseo_metadesc', sanitize_textarea_field($data['meta_description']));
                    $written[] = '_yoast_wpseo_metadesc';
                }
                // Yoast: una sola focus keyword (prima del CSV)
                if (!empty($kws_csv)) {
                    $first_kw = trim(explode(',', $kws_csv)[0]);
                    if ($first_kw !== '') {
                        update_post_meta($post_id, '_yoast_wpseo_focuskw', sanitize_text_field($first_kw));
                        $written[] = '_yoast_wpseo_focuskw';
                    }
                }
                break;

            case self::ENGINE_RANKMATH:
                if (isset($data['meta_title']) && $data['meta_title'] !== '') {
                    update_post_meta($post_id, 'rank_math_title', sanitize_text_field($data['meta_title']));
                    $written[] = 'rank_math_title';
                }
                if (isset($data['meta_description']) && $data['meta_description'] !== '') {
                    update_post_meta($post_id, 'rank_math_description', sanitize_textarea_field($data['meta_description']));
                    $written[] = 'rank_math_description';
                }
                if (!empty($kws_csv)) {
                    update_post_meta($post_id, 'rank_math_focus_keyword', sanitize_text_field($kws_csv));
                    $written[] = 'rank_math_focus_keyword';
                }
                break;

            case self::ENGINE_AIOSEO:
                if (isset($data['meta_title']) && $data['meta_title'] !== '') {
                    update_post_meta($post_id, '_aioseo_title', sanitize_text_field($data['meta_title']));
                    $written[] = '_aioseo_title';
                }
                if (isset($data['meta_description']) && $data['meta_description'] !== '') {
                    update_post_meta($post_id, '_aioseo_description', sanitize_textarea_field($data['meta_description']));
                    $written[] = '_aioseo_description';
                }
                if (!empty($kws_csv)) {
                    // Format JSON che AIOSEO si aspetta
                    $kws_arr = array_filter(array_map('trim', explode(',', $kws_csv)));
                    $aioseo_format = array_map(function($k) {
                        return array('label' => $k, 'value' => $k);
                    }, array_values($kws_arr));
                    update_post_meta($post_id, '_aioseo_keywords', wp_json_encode($aioseo_format));
                    $written[] = '_aioseo_keywords';
                }
                break;
        }

        // 2. Doppia scrittura SEMPRE su Orchestra native (fallback se disattivo Yoast/etc)
        if (isset($data['meta_title']) && $data['meta_title'] !== '') {
            update_post_meta($post_id, '_seo_aeo_meta_title', sanitize_text_field($data['meta_title']));
        }
        if (isset($data['meta_description']) && $data['meta_description'] !== '') {
            update_post_meta($post_id, '_seo_aeo_meta_description', sanitize_textarea_field($data['meta_description']));
        }
        if (!empty($kws_csv)) {
            update_post_meta($post_id, '_seo_aeo_meta_keywords', sanitize_text_field($kws_csv));
        }

        return array(
            'success'      => true,
            'engine'       => $engine,
            'engine_label' => self::engine_label(),
            'written_keys' => $written,
        );
    }

    /**
     * Cancella i meta tag dal plugin SEO attivo + Orchestra native.
     * Usato in restore_snapshot quando il previous_state aveva valori vuoti.
     */
    public static function delete_meta($post_id) {
        $engine = self::detect_engine();

        switch ($engine) {
            case self::ENGINE_YOAST:
                delete_post_meta($post_id, '_yoast_wpseo_title');
                delete_post_meta($post_id, '_yoast_wpseo_metadesc');
                delete_post_meta($post_id, '_yoast_wpseo_focuskw');
                break;
            case self::ENGINE_RANKMATH:
                delete_post_meta($post_id, 'rank_math_title');
                delete_post_meta($post_id, 'rank_math_description');
                delete_post_meta($post_id, 'rank_math_focus_keyword');
                break;
            case self::ENGINE_AIOSEO:
                delete_post_meta($post_id, '_aioseo_title');
                delete_post_meta($post_id, '_aioseo_description');
                delete_post_meta($post_id, '_aioseo_keywords');
                break;
        }
        delete_post_meta($post_id, '_seo_aeo_meta_title');
        delete_post_meta($post_id, '_seo_aeo_meta_description');
        delete_post_meta($post_id, '_seo_aeo_meta_keywords');
    }

    /**
     * Ripristina i meta tag a uno stato specifico.
     * Per ogni campo: se nel previous_state era vuoto → cancella; se popolato → scrive.
     * Risolve il bug dove bridge.write_meta() ignorava stringhe vuote, lasciando
     * orfani i valori dell'apply dopo un undo.
     *
     * @since 3.3.9
     */
    public static function restore_meta($post_id, $previous_state) {
        $post_id = intval($post_id);
        if ($post_id <= 0) return array('success' => false, 'message' => 'post_id non valido');

        $engine = self::detect_engine();
        $actions = array();

        $engine_keys = array(
            self::ENGINE_YOAST    => array(
                'meta_title'       => '_yoast_wpseo_title',
                'meta_description' => '_yoast_wpseo_metadesc',
                'meta_keywords'    => '_yoast_wpseo_focuskw',
            ),
            self::ENGINE_RANKMATH => array(
                'meta_title'       => 'rank_math_title',
                'meta_description' => 'rank_math_description',
                'meta_keywords'    => 'rank_math_focus_keyword',
            ),
            self::ENGINE_AIOSEO   => array(
                'meta_title'       => '_aioseo_title',
                'meta_description' => '_aioseo_description',
                'meta_keywords'    => '_aioseo_keywords',
            ),
            self::ENGINE_NATIVE   => array(),
        );

        $native_keys = array(
            'meta_title'       => '_seo_aeo_meta_title',
            'meta_description' => '_seo_aeo_meta_description',
            'meta_keywords'    => '_seo_aeo_meta_keywords',
        );

        $current_engine_keys = isset($engine_keys[$engine]) ? $engine_keys[$engine] : array();

        foreach (array('meta_title', 'meta_description', 'meta_keywords') as $field) {
            $value = isset($previous_state[$field]) ? $previous_state[$field] : null;

            if ($field === 'meta_keywords') {
                if (is_array($value)) {
                    $value = implode(', ', array_filter(array_map('trim', $value)));
                } else {
                    $value = (string) $value;
                }
            } else {
                $value = ($value === null) ? '' : (string) $value;
            }

            $is_empty = ($value === '' || $value === null);

            $engine_key = isset($current_engine_keys[$field]) ? $current_engine_keys[$field] : null;
            $native_key = $native_keys[$field];

            if ($is_empty) {
                if ($engine_key) {
                    delete_post_meta($post_id, $engine_key);
                    $actions[] = "delete:$engine_key";
                }
                delete_post_meta($post_id, $native_key);
                $actions[] = "delete:$native_key";
            } else {
                $sanitized = ($field === 'meta_description')
                    ? sanitize_textarea_field($value)
                    : sanitize_text_field($value);

                if ($engine_key) {
                    if ($engine === self::ENGINE_AIOSEO && $field === 'meta_keywords') {
                        $kws_arr = array_filter(array_map('trim', explode(',', $sanitized)));
                        $aioseo_format = array_map(function($k) {
                            return array('label' => $k, 'value' => $k);
                        }, array_values($kws_arr));
                        update_post_meta($post_id, $engine_key, wp_json_encode($aioseo_format));
                    } elseif ($engine === self::ENGINE_YOAST && $field === 'meta_keywords') {
                        $first_kw = trim(explode(',', $sanitized)[0]);
                        if ($first_kw !== '') {
                            update_post_meta($post_id, $engine_key, $first_kw);
                        }
                    } else {
                        update_post_meta($post_id, $engine_key, $sanitized);
                    }
                    $actions[] = "write:$engine_key";
                }

                update_post_meta($post_id, $native_key, $sanitized);
                $actions[] = "write:$native_key";
            }
        }


        return array(
            'success'      => true,
            'engine'       => $engine,
            'engine_label' => self::engine_label(),
            'actions'      => $actions,
        );
    }

    private static function empty_meta() {
        return array(
            'meta_title'       => '',
            'meta_description' => '',
            'meta_keywords'    => array(),
            'engine'           => self::ENGINE_NATIVE,
            'engine_label'     => 'Orchestra (nativo)',
        );
    }
}

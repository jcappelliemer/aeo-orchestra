<?php
/*
 * Copyright 2026 Solaris Code SL - aeo-orchestra.com
 *
 * Translator (3.25.0 — i18n foundation, scope: scale globale)
 *
 * Sistema di traduzione lightweight basato su array PHP, no dipendenza da
 * gettext/msgfmt sul server. La lingua è impostata dall'utente via setting
 * `seo_aeo_orchestra_language` (default: it). I file di traduzione vivono
 * in `languages/strings-{locale}.php` e sono array PHP `[chiave_it => valore_locale]`.
 *
 * Uso nei template/class:
 *   echo esc_html(SEO_AEO_T::t('Benvenuto'));   // string
 *   SEO_AEO_T::e('Benvenuto');                  // echo with esc_html
 *   echo esc_attr(SEO_AEO_T::t('Salva'));       // for attributes
 *
 * Per estendere: aggiungi nuove righe in languages/strings-en.php.
 * Strings non in mappa fallback all'originale italiano (no errori).
 */

if (!defined('ABSPATH')) exit;

class SEO_AEO_T {

    private static $loaded = false;
    private static $current_locale = 'it';
    private static $strings = array();

    public static function init() {
        if (self::$loaded) return;
        self::$current_locale = self::detect_locale();
        if (self::$current_locale !== 'it') {
            self::load_strings(self::$current_locale);
        }
        self::$loaded = true;
    }

    /**
     * Detect locale: setting utente ha priorità, poi WP locale, poi default IT.
     * 3.27.1: aggiunti es/fr/de oltre a it/en.
     */
    public static function detect_locale() {
        $supported = array('it', 'en', 'es', 'fr', 'de');
        $opt = get_option('seo_aeo_orchestra_language', '');
        if ($opt && in_array($opt, $supported, true)) return $opt;
        // Fallback su WP locale (es. 'es_ES' → 'es', 'fr_FR' → 'fr', 'de_DE' → 'de')
        $wp = function_exists('get_user_locale') ? get_user_locale() : get_locale();
        if (is_string($wp) && strlen($wp) >= 2) {
            $code = strtolower(substr($wp, 0, 2));
            if (in_array($code, $supported, true)) return $code;
        }
        return 'it';
    }

    private static function load_strings($locale) {
        $dir = defined('SEO_AEO_PLUGIN_DIR') ? SEO_AEO_PLUGIN_DIR : plugin_dir_path(dirname(__FILE__));
        $file = trailingslashit($dir) . 'languages/strings-' . $locale . '.php';
        if (file_exists($file)) {
            $arr = include $file;
            if (is_array($arr)) self::$strings = $arr;
        }
    }

    /**
     * Translate string. Fallback all'originale italiano se chiave non trovata.
     */
    public static function t($it) {
        if (!self::$loaded) self::init();
        if (self::$current_locale === 'it' || empty(self::$strings)) return $it;
        return isset(self::$strings[$it]) ? self::$strings[$it] : $it;
    }

    /**
     * Echo translated string (already esc_html'd).
     */
    public static function e($it) {
        echo esc_html(self::t($it));
    }

    /**
     * Return translated string already escaped for HTML body.
     * Use as: echo SEO_AEO_T::eh('Benvenuto');
     * (3.35.10 — security helper esposto per template che non usano il wrapper $T)
     */
    public static function eh($it) {
        return esc_html(self::t($it));
    }

    /**
     * Return translated string already escaped for HTML attributes.
     * Use as: <input title="<?php echo SEO_AEO_T::ea('Salva'); ?>">
     */
    public static function ea($it) {
        return esc_attr(self::t($it));
    }

    public static function current_locale() {
        if (!self::$loaded) self::init();
        return self::$current_locale;
    }
}

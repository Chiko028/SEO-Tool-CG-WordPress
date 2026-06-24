<?php
/**
 * Sichere Verwaltung des MiniMax API-Keys.
 *
 * Speichert den Key verschlüsselt in der WP-Options-Tabelle.
 * Verwendet wp_options mit autoload=yes — wird also bei jedem Request geladen.
 *
 * Verschlüsselung: WordPress bietet keine eingebaute Verschlüsselung für Options,
 * aber wir nutzen die "SALTs" aus wp-config.php als Verschlüsselungs-Key.
 * Das ist nicht 100% sicher gegen Root-Zugriff, aber deutlich besser als Klartext.
 */

if (!defined('ABSPATH')) exit;

class SEO_Tool_CG_Key_Manager {

    /**
     * Verschlüsselt einen Wert mit den WP-SALTs.
     */
    public static function encrypt($plaintext) {
        if (empty($plaintext)) return '';

        $key = self::get_encryption_key();
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($iv_length);
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-cbc',
            hash('sha256', $key, true),
            OPENSSL_RAW_DATA,
            $iv
        );

        // IV + Ciphertext zusammen base64-encoden
        return base64_encode($iv . $ciphertext);
    }

    /**
     * Entschlüsselt einen Wert.
     */
    public static function decrypt($encrypted) {
        if (empty($encrypted)) return '';

        $key = self::get_encryption_key();
        $data = base64_decode($encrypted, true);
        if ($data === false) return '';

        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        if (strlen($data) < $iv_length) return '';

        $iv = substr($data, 0, $iv_length);
        $ciphertext = substr($data, $iv_length);

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-cbc',
            hash('sha256', $key, true),
            OPENSSL_RAW_DATA,
            $iv
        );

        return $plaintext === false ? '' : $plaintext;
    }

    /**
     * Speichert den API-Key verschlüsselt.
     */
    public static function set_api_key($key) {
        // Whitespace + unsichtbare Zeichen entfernen (manchmal kopiert mit Newlines)
        $key = preg_replace('/\s+/', '', trim((string) $key));

        if (empty($key)) {
            delete_option(SEO_TOOL_CG_OPTION_KEY);
            return true;
        }

        // Minimale Validierung
        if (strlen($key) < 10) {
            return new WP_Error('invalid_key', __('API-Key zu kurz (mindestens 10 Zeichen).', 'seo-tool-cg'));
        }

        // Format-Check — die meisten Keys fangen mit sk- an
        // Wir lassen aber auch andere Formate zu
        $result = update_option(SEO_TOOL_CG_OPTION_KEY, self::encrypt($key));

        // wp_cache löschen damit der nächste get_api_key frisch lädt
        wp_cache_delete(SEO_TOOL_CG_OPTION_KEY, 'options');

        return $result;
    }

    /**
     * Holt den entschlüsselten API-Key.
     */
    public static function get_api_key() {
        $encrypted = get_option(SEO_TOOL_CG_OPTION_KEY);
        if (empty($encrypted)) return '';

        // Rückwärtskompatibilität: Wenn alter Klartext-Key gespeichert war
        if (strpos($encrypted, 'sk-') === 0 || strpos($encrypted, 'sk-api-') === 0) {
            // War Klartext — verschlüsseln und neu speichern
            self::set_api_key($encrypted);
            return $encrypted;
        }

        return self::decrypt($encrypted);
    }

    /**
     * Prüft ob ein Key konfiguriert ist.
     */
    public static function has_api_key() {
        return !empty(self::get_api_key());
    }

    /**
     * Löscht den Key.
     */
    public static function delete_api_key() {
        return delete_option(SEO_TOOL_CG_OPTION_KEY);
    }

    /**
     * Maskiert den Key für die Anzeige (z.B. "sk-api-...X9nc").
     */
    public static function mask_api_key($key) {
        if (empty($key)) return '';
        if (strlen($key) <= 12) return str_repeat('•', strlen($key));
        return substr($key, 0, 8) . '...' . substr($key, -4);
    }

    /**
     * Erstellt einen Verschlüsselungs-Key aus den WP-SALTs.
     */
    private static function get_encryption_key() {
        $salts = [
            defined('LOGGED_IN_KEY') ? LOGGED_IN_KEY : '',
            defined('LOGGED_IN_SALT') ? LOGGED_IN_SALT : '',
            defined('AUTH_KEY') ? AUTH_KEY : '',
            defined('AUTH_SALT') ? AUTH_SALT : '',
            defined('NONCE_KEY') ? NONCE_KEY : '',
            defined('NONCE_SALT') ? NONCE_SALT : '',
            defined('SECURE_AUTH_KEY') ? SECURE_AUTH_KEY : '',
            defined('SECURE_AUTH_SALT') ? SECURE_AUTH_SALT : '',
            defined('DB_NAME') ? DB_NAME : '',
            defined('DB_PASSWORD') ? DB_PASSWORD : '',
        ];

        return implode('|', array_filter($salts));
    }
}

<?php
/**
 * Plugin Name: SEO Tool CG
 * Plugin URI: https://github.com/Chiko028/SEO-Tool-CG-WP
 * Description: AI-gestützte Content-Generierung für WordPress. Erstellt SEO-optimierte Seiten-Entwürfe basierend auf Keywords. Verwendet die MiniMax API (kompatibel mit OpenAI).
 * Version: 1.0.1
 * Author: Chiko028
 * License: MIT
 * Text Domain: seo-tool-cg
 *
 * Sicherheit: Der MiniMax API-Key wird verschlüsselt in der WP-Options-Tabelle gespeichert.
 * Niemals wird etwas direkt veröffentlicht — alle generierten Inhalte sind Drafts, die der Admin manuell prüfen und freigeben muss.
 */

if (!defined('ABSPATH')) {
    exit; // Verhindert direkten Zugriff
}

// Konstanten
define('SEO_TOOL_CG_VERSION', '1.0.1');
define('SEO_TOOL_CG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SEO_TOOL_CG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SEO_TOOL_CG_OPTION_KEY', 'seo_tool_cg_minimax_api_key');
define('SEO_TOOL_CG_OPTION_MODEL', 'seo_tool_cg_minimax_model');
define('SEO_TOOL_CG_OPTION_BRAND', 'seo_tool_cg_brand_settings');
define('SEO_TOOL_CG_API_BASE', 'https://api.minimax.io/v1');

// Plugin-Klassen laden
require_once SEO_TOOL_CG_PLUGIN_DIR . 'includes/class-api-client.php';
require_once SEO_TOOL_CG_PLUGIN_DIR . 'includes/class-content-generator.php';
require_once SEO_TOOL_CG_PLUGIN_DIR . 'includes/class-key-manager.php';

// Admin-Klassen
if (is_admin()) {
    require_once SEO_TOOL_CG_PLUGIN_DIR . 'admin/class-settings-page.php';
    require_once SEO_TOOL_CG_PLUGIN_DIR . 'admin/class-meta-box.php';
    require_once SEO_TOOL_CG_PLUGIN_DIR . 'admin/class-ajax-handler.php';
    require_once SEO_TOOL_CG_PLUGIN_DIR . 'admin/class-history-page.php';
}

// Initialisierung
add_action('plugins_loaded', function () {
    load_plugin_textdomain('seo-tool-cg', false, dirname(plugin_basename(__FILE__)) . '/languages');

    if (is_admin()) {
        (new SEO_Tool_CG_Settings_Page())->register();
        (new SEO_Tool_CG_Meta_Box())->register();
        (new SEO_Tool_CG_Ajax_Handler())->register();
        (new SEO_Tool_CG_History_Page())->register();
    }
});

// Aktivierungs-Hook: Defaults setzen
register_activation_hook(__FILE__, function () {
    if (get_option(SEO_TOOL_CG_OPTION_MODEL) === false) {
        update_option(SEO_TOOL_CG_OPTION_MODEL, 'MiniMax-M2');
    }
});

// Deaktivierungs-Hook: nichts tun (Daten bleiben erhalten für Reaktivierung)
register_deactivation_hook(__FILE__, function () {
    // Bewusst leer: User-Daten (API-Key, Drafts) bleiben erhalten
});

<?php
/**
 * Admin-Einstellungs-Seite für den MiniMax API-Key und Brand-Settings.
 */

if (!defined('ABSPATH')) exit;

class SEO_Tool_CG_Settings_Page {

    const MENU_SLUG = 'seo-tool-cg';
    const SETTINGS_GROUP = 'seo_tool_cg_settings';

    public function register() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_menu() {
        // Eigenes Top-Level-Menü statt unter Einstellungen
        add_menu_page(
            __('SEO Tool CG', 'seo-tool-cg'),
            __('SEO Tool CG', 'seo-tool-cg'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render_page'],
            'data:image/svg+xml;base64,' . base64_encode(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="#a7aaad">
                    <path d="M11 2.5a8.5 8.5 0 1 0 6.4 14.03l-1.41-1.41A6.5 6.5 0 1 1 11 4.5v3.7L7.4 5.1 11 1.5v1z"/>
                </svg>'
            ),
            81 // Position: direkt nach "Werkzeuge" (80)
        );
    }

    public function register_settings() {
        register_setting(self::SETTINGS_GROUP, SEO_TOOL_CG_OPTION_KEY, [
            'sanitize_callback' => function($input) {
                // Bei Update via Settings API: encrypt + save
                if (empty($input)) {
                    SEO_Tool_CG_Key_Manager::delete_api_key();
                    return '';
                }
                $result = SEO_Tool_CG_Key_Manager::set_api_key($input);
                return is_wp_error($result) ? '' : $input;
            },
        ]);

        register_setting(self::SETTINGS_GROUP, SEO_TOOL_CG_OPTION_MODEL);

        register_setting(self::SETTINGS_GROUP, SEO_TOOL_CG_OPTION_BRAND, [
            'sanitize_callback' => function($input) {
                return is_array($input) ? array_map('sanitize_text_field', $input) : [];
            },
        ]);
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'settings_page_' . self::MENU_SLUG) return;

        wp_enqueue_style(
            'seo-tool-cg-admin',
            SEO_TOOL_CG_PLUGIN_URL . 'assets/css/admin.css',
            [],
            SEO_TOOL_CG_VERSION
        );

        wp_enqueue_script(
            'seo-tool-cg-admin',
            SEO_TOOL_CG_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            SEO_TOOL_CG_VERSION,
            true
        );

        wp_localize_script('seo-tool-cg-admin', 'seoToolCG', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('seo_tool_cg_test'),
            'i18n' => [
                'testing' => __('Teste…', 'seo-tool-cg'),
                'success' => __('Verbindung erfolgreich!', 'seo-tool-cg'),
                'failed' => __('Verbindung fehlgeschlagen.', 'seo-tool-cg'),
            ],
        ]);
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Keine Berechtigung.', 'seo-tool-cg'));
        }

        $has_key = SEO_Tool_CG_Key_Manager::has_api_key();
        $masked_key = $has_key
            ? SEO_Tool_CG_Key_Manager::mask_api_key(SEO_Tool_CG_Key_Manager::get_api_key())
            : '';
        $model = get_option(SEO_TOOL_CG_OPTION_MODEL, 'MiniMax-M2');
        $brand = get_option(SEO_TOOL_CG_OPTION_BRAND, []);
        ?>
        <div class="wrap seo-tool-cg-wrap">
            <h1>🔍 SEO Tool CG</h1>

            <div class="seo-tool-cg-card">
                <h2>🤖 MiniMax API-Verbindung</h2>
                <p>
                    <?php esc_html_e('Verbinde dein WordPress mit der MiniMax API, um KI-gestützte SEO-Inhalte zu generieren.', 'seo-tool-cg'); ?>
                </p>

                <?php if ($has_key): ?>
                    <div class="seo-tool-cg-status success">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e('API-Key konfiguriert:', 'seo-tool-cg'); ?>
                        <code><?php echo esc_html($masked_key); ?></code>
                    </div>
                <?php else: ?>
                    <div class="seo-tool-cg-status warning">
                        <span class="dashicons dashicons-warning"></span>
                        <?php esc_html_e('Kein API-Key konfiguriert. Generierung deaktiviert.', 'seo-tool-cg'); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="options.php">
                    <?php settings_fields(self::SETTINGS_GROUP); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="seo_tool_cg_api_key">
                                    <?php esc_html_e('API-Key', 'seo-tool-cg'); ?>
                                </label>
                            </th>
                            <td>
                                <input
                                    type="password"
                                    id="seo_tool_cg_api_key"
                                    name="<?php echo esc_attr(SEO_TOOL_CG_OPTION_KEY); ?>"
                                    value=""
                                    placeholder="<?php echo $has_key ? esc_attr__('Neuen Key eingeben (überschreibt aktuellen)', 'seo-tool-cg') : esc_attr__('sk-api-...', 'seo-tool-cg'); ?>"
                                    class="regular-text"
                                    autocomplete="off"
                                />
                                <p class="description">
                                    <?php esc_html_e('Wird verschlüsselt in der Datenbank gespeichert. Hole deinen Key unter', 'seo-tool-cg'); ?>
                                    <a href="https://platform.minimax.io" target="_blank">platform.minimax.io</a>.
                                </p>
                                <button type="button" id="seo-tool-cg-test-connection" class="button">
                                    <?php esc_html_e('Verbindung testen', 'seo-tool-cg'); ?>
                                </button>
                                <span id="seo-tool-cg-test-result"></span>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="seo_tool_cg_model">
                                    <?php esc_html_e('Modell', 'seo-tool-cg'); ?>
                                </label>
                            </th>
                            <td>
                                <select id="seo_tool_cg_model" name="<?php echo esc_attr(SEO_TOOL_CG_OPTION_MODEL); ?>">
                                    <option value="MiniMax-M2" <?php selected($model, 'MiniMax-M2'); ?>>
                                        MiniMax M2 (empfohlen, 200K Context)
                                    </option>
                                    <option value="MiniMax-Text-01" <?php selected($model, 'MiniMax-Text-01'); ?>>
                                        MiniMax Text-01
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <h2>🎨 Brand-Einstellungen</h2>
                    <p class="description">
                        <?php esc_html_e('Diese Infos werden in jeden generierten Artikel eingebaut.', 'seo-tool-cg'); ?>
                    </p>

                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('Firmenname', 'seo-tool-cg'); ?></th>
                            <td>
                                <input type="text" name="<?php echo esc_attr(SEO_TOOL_CG_OPTION_BRAND); ?>[company_name]"
                                       value="<?php echo esc_attr($brand['company_name'] ?? ''); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Stadt/Region', 'seo-tool-cg'); ?></th>
                            <td>
                                <input type="text" name="<?php echo esc_attr(SEO_TOOL_CG_OPTION_BRAND); ?>[city]"
                                       value="<?php echo esc_attr($brand['city'] ?? ''); ?>" class="regular-text"
                                       placeholder="<?php esc_attr_e('z.B. Wien, Berlin, München', 'seo-tool-cg'); ?>" />
                                <p class="description"><?php esc_html_e('Für lokale SEO-Bezüge.', 'seo-tool-cg'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Tonalität', 'seo-tool-cg'); ?></th>
                            <td>
                                <input type="text" name="<?php echo esc_attr(SEO_TOOL_CG_OPTION_BRAND); ?>[tone]"
                                       value="<?php echo esc_attr($brand['tone'] ?? 'professionell, aber zugänglich'); ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e('z.B. "locker und freundlich", "sachlich und technisch"', 'seo-tool-cg'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(); ?>
                </form>
            </div>

            <div class="seo-tool-cg-card">
                <h2>📋 Wie es funktioniert</h2>
                <ol>
                    <li><?php esc_html_e('API-Key oben eintragen und testen', 'seo-tool-cg'); ?></li>
                    <li><?php esc_html_e('Brand-Einstellungen ausfüllen (für personalisierte Inhalte)', 'seo-tool-cg'); ?></li>
                    <li><?php esc_html_e('Neuen Post/Page erstellen → "Mit AI generieren" klicken', 'seo-tool-cg'); ?></li>
                    <li><?php esc_html_e('Keyword + Optionen eingeben → "Generieren"', 'seo-tool-cg'); ?></li>
                    <li><?php esc_html_e('Artikel wird als Draft erstellt — du reviewst und veröffentlichst selbst', 'seo-tool-cg'); ?></li>
                </ol>
                <p class="seo-tool-cg-warning">
                    ⚠️ <?php esc_html_e('Aus Sicherheitsgründen werden Inhalte IMMER als Draft erstellt. Das Plugin veröffentlicht nie automatisch.', 'seo-tool-cg'); ?>
                </p>
            </div>
        </div>
        <?php
    }
}

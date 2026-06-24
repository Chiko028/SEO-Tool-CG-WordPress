<?php
/**
 * Meta-Box im Post/Page-Editor mit "Mit AI generieren"-Funktion.
 */

if (!defined('ABSPATH')) exit;

class SEO_Tool_CG_Meta_Box {

    public function register() {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_meta_box() {
        $post_types = ['post', 'page'];
        foreach ($post_types as $post_type) {
            add_meta_box(
                'seo_tool_cg_generator',
                '🔍 SEO Tool CG — Content Generator',
                [$this, 'render'],
                $post_type,
                'normal',
                'high'
            );
        }
    }

    public function enqueue_assets($hook) {
        if (!in_array($hook, ['post.php', 'post-new.php'])) return;

        wp_enqueue_style(
            'seo-tool-cg-meta-box',
            SEO_TOOL_CG_PLUGIN_URL . 'assets/css/meta-box.css',
            [],
            SEO_TOOL_CG_VERSION
        );

        wp_enqueue_script(
            'seo-tool-cg-meta-box',
            SEO_TOOL_CG_PLUGIN_URL . 'assets/js/meta-box.js',
            ['jquery'],
            SEO_TOOL_CG_VERSION,
            true
        );

        wp_localize_script('seo-tool-cg-meta-box', 'seoToolCG', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('seo_tool_cg_generate'),
            'hasKey' => SEO_Tool_CG_Key_Manager::has_api_key(),
            'i18n' => [
                'generating' => __('Generiere Artikel… das kann 30-60 Sekunden dauern.', 'seo-tool-cg'),
                'success' => __('Draft erstellt! Du wirst zum Editor weitergeleitet.', 'seo-tool-cg'),
                'error' => __('Fehler:', 'seo-tool-cg'),
                'noKey' => __('Bitte zuerst API-Key in den Einstellungen hinterlegen.', 'seo-tool-cg'),
                'confirmReplace' => __('Aktueller Inhalt wird ersetzt. Wirklich generieren?', 'seo-tool-cg'),
            ],
        ]);
    }

    public function render($post) {
        $has_key = SEO_Tool_CG_Key_Manager::has_api_key();
        $brand = get_option(SEO_TOOL_CG_OPTION_BRAND, []);
        ?>
        <div class="seo-tool-cg-meta-box">
            <?php if (!$has_key): ?>
                <div class="seo-tool-cg-notice error">
                    <strong><?php esc_html_e('Kein API-Key konfiguriert.', 'seo-tool-cg'); ?></strong>
                    <?php esc_html_e('Bitte zuerst unter Einstellungen → SEO Tool CG hinterlegen.', 'seo-tool-cg'); ?>
                </div>
            <?php endif; ?>

            <div class="seo-tool-cg-form">
                <div class="seo-tool-cg-row">
                    <label for="stcg-keyword">
                        <strong>🎯 Hauptkeyword / Thema</strong>
                    </label>
                    <input type="text" id="stcg-keyword" placeholder="<?php esc_attr_e('z.B. Webdesign Wien 2026', 'seo-tool-cg'); ?>"
                           class="widefat" <?php echo !$has_key ? 'disabled' : ''; ?> />
                </div>

                <div class="seo-tool-cg-row seo-tool-cg-grid">
                    <div>
                        <label for="stcg-intent"><strong>Suchintention</strong></label>
                        <select id="stcg-intent" class="widefat" <?php echo !$has_key ? 'disabled' : ''; ?>>
                            <option value="informational">Informativ (Wissen)</option>
                            <option value="commercial">Kommerziell (Vergleich)</option>
                            <option value="transactional">Transaktional (Kauf)</option>
                            <option value="navigational">Navigational</option>
                        </select>
                    </div>
                    <div>
                        <label for="stcg-word-count"><strong>Wörter ca.</strong></label>
                        <select id="stcg-word-count" class="widefat" <?php echo !$has_key ? 'disabled' : ''; ?>>
                            <option value="1000">1.000</option>
                            <option value="1500">1.500</option>
                            <option value="1800" selected>1.800</option>
                            <option value="2500">2.500</option>
                            <option value="3500">3.500</option>
                        </select>
                    </div>
                </div>

                <div class="seo-tool-cg-row seo-tool-cg-checks">
                    <label>
                        <input type="checkbox" id="stcg-include-faq" checked <?php echo !$has_key ? 'disabled' : ''; ?> />
                        📋 FAQ-Block mit aufnehmen
                    </label>
                    <label>
                        <input type="checkbox" id="stcg-include-meta" checked <?php echo !$has_key ? 'disabled' : ''; ?> />
                        📊 Meta-Daten (Title/Description/Slug) generieren
                    </label>
                </div>

                <div class="seo-tool-cg-row seo-tool-cg-actions">
                    <button type="button" id="stcg-generate-btn" class="button button-primary button-large"
                            <?php echo !$has_key ? 'disabled' : ''; ?>>
                        ✨ Mit AI generieren
                    </button>
                    <span id="stcg-status"></span>
                </div>

                <div class="seo-tool-cg-info">
                    <p>
                        <span class="dashicons dashicons-info"></span>
                        <?php if (!empty($brand['company_name'])): ?>
                            <?php printf(esc_html__('Generiert Inhalte für: %s', 'seo-tool-cg'),
                                '<strong>' . esc_html($brand['company_name']) . '</strong>'); ?>
                            <?php if (!empty($brand['city'])): ?>
                                <?php printf(esc_html__(' (Standort: %s)', 'seo-tool-cg'),
                                    esc_html($brand['city'])); ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php esc_html_e('Tipp: Trage deine Brand-Infos in den Einstellungen ein für personalisierte Inhalte.', 'seo-tool-cg'); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div id="stcg-preview" class="seo-tool-cg-preview" style="display:none;">
                <h3>📄 Vorschau</h3>
                <pre id="stcg-preview-content"></pre>
            </div>
        </div>
        <?php
    }
}

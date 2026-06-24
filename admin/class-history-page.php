<?php
/**
 * History-Seite: zeigt alle generierten Drafts.
 */

if (!defined('ABSPATH')) exit;

class SEO_Tool_CG_History_Page {

    public function register() {
        add_action('admin_menu', [$this, 'add_submenu']);
    }

    public function add_submenu() {
        add_submenu_page(
            'options-general.php',
            __('SEO Tool CG — Verlauf', 'seo-tool-cg'),
            __('SEO Tool CG Verlauf', 'seo-tool-cg'),
            'manage_options',
            'seo-tool-cg-history',
            [$this, 'render']
        );
    }

    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Keine Berechtigung.', 'seo-tool-cg'));
        }

        $history = get_option('seo_tool_cg_history', []);
        $history = array_reverse($history);

        $usage = get_option('seo_tool_cg_usage_log', []);
        $total_tokens = array_sum(array_column($usage, 'total_tokens'));
        $estimated_cost = ($total_tokens / 1_000_000) * 0.5; // grobe Schätzung
        ?>
        <div class="wrap">
            <h1>📋 Generierte Inhalte — Verlauf</h1>

            <div class="seo-tool-cg-stats">
                <div class="seo-tool-cg-stat-card">
                    <div class="seo-tool-cg-stat-value"><?php echo count($history); ?></div>
                    <div class="seo-tool-cg-stat-label">Generierungen gesamt</div>
                </div>
                <div class="seo-tool-cg-stat-card">
                    <div class="seo-tool-cg-stat-value"><?php echo number_format($total_tokens); ?></div>
                    <div class="seo-tool-cg-stat-label">Tokens verbraucht</div>
                </div>
                <div class="seo-tool-cg-stat-card">
                    <div class="seo-tool-cg-stat-value">~$<?php echo number_format($estimated_cost, 4); ?></div>
                    <div class="seo-tool-cg-stat-label">Geschätzte Kosten</div>
                </div>
            </div>

            <?php if (empty($history)): ?>
                <p style="margin-top: 30px; color: #666;">
                    <?php esc_html_e('Noch keine Inhalte generiert.', 'seo-tool-cg'); ?>
                </p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Datum', 'seo-tool-cg'); ?></th>
                            <th><?php esc_html_e('Keyword', 'seo-tool-cg'); ?></th>
                            <th><?php esc_html_e('Titel', 'seo-tool-cg'); ?></th>
                            <th><?php esc_html_e('Status', 'seo-tool-cg'); ?></th>
                            <th><?php esc_html_e('Aktion', 'seo-tool-cg'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $entry): ?>
                            <?php
                            $post = get_post($entry['post_id']);
                            $status_label = $post ? ucfirst($post->post_status) : __('Gelöscht', 'seo-tool-cg');
                            ?>
                            <tr>
                                <td><?php echo esc_html($entry['timestamp']); ?></td>
                                <td><code><?php echo esc_html($entry['keyword']); ?></code></td>
                                <td><?php echo esc_html($entry['title']); ?></td>
                                <td><?php echo esc_html($status_label); ?></td>
                                <td>
                                    <?php if ($post): ?>
                                        <a href="<?php echo esc_url(get_edit_post_link($entry['post_id'])); ?>" class="button button-small">
                                            Bearbeiten
                                        </a>
                                    <?php else: ?>
                                        <em>Post gelöscht</em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
}

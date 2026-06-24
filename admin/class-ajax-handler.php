<?php
/**
 * AJAX-Handler für Test-Connection + Content-Generierung.
 */

if (!defined('ABSPATH')) exit;

class SEO_Tool_CG_Ajax_Handler {

    public function register() {
        add_action('wp_ajax_seo_tool_cg_test_connection', [$this, 'test_connection']);
        add_action('wp_ajax_seo_tool_cg_generate', [$this, 'generate']);
    }

    public function test_connection() {
        check_ajax_referer('seo_tool_cg_test', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'seo-tool-cg')]);
        }

        // Vorrang: Key aus POST-Daten (vom Frontend direkt mitgeschickt)
        // Fallback: Gespeicherter Key aus der DB
        $key_from_form = sanitize_text_field($_POST['api_key'] ?? '');
        $key_to_use = !empty($key_from_form) ? $key_from_form : SEO_Tool_CG_Key_Manager::get_api_key();

        if (empty($key_to_use)) {
            wp_send_json_error(['message' => __('Bitte zuerst API-Key eingeben.', 'seo-tool-cg')]);
        }

        $client = new SEO_Tool_CG_API_Client($key_to_use);
        $result = $client->test_connection();

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success($result);
    }

    public function generate() {
        check_ajax_referer('seo_tool_cg_generate', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'seo-tool-cg')]);
        }

        $params = [
            'keyword' => sanitize_text_field($_POST['keyword'] ?? ''),
            'intent' => sanitize_text_field($_POST['intent'] ?? 'informational'),
            'word_count' => intval($_POST['word_count'] ?? 1800),
            'include_faq' => !empty($_POST['include_faq']),
            'include_meta' => !empty($_POST['include_meta']),
        ];

        if (empty($params['keyword'])) {
            wp_send_json_error(['message' => __('Bitte ein Hauptkeyword eingeben.', 'seo-tool-cg')]);
        }

        $generator = new SEO_Tool_CG_Content_Generator();
        $result = $generator->generate_draft($params);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success($result);
    }
}

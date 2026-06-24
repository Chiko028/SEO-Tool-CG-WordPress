<?php
/**
 * Content-Generator: erstellt aus dem API-Output einen WordPress-Draft.
 */

if (!defined('ABSPATH')) exit;

class SEO_Tool_CG_Content_Generator {

    /**
     * Generiert Content und erstellt einen Draft-Post.
     */
    public function generate_draft($params) {
        // 1. Content von MiniMax holen
        $client = new SEO_Tool_CG_API_Client();
        $generated = $client->generate_content($params);

        if (is_wp_error($generated)) {
            return $generated;
        }

        // 2. Draft-Post erstellen
        $post_data = [
            'post_title' => $generated['title'] ?: $params['keyword'],
            'post_content' => $this->format_content_for_wp($generated['content']),
            'post_excerpt' => $generated['description'],
            'post_status' => 'draft', // IMMER Draft — niemals auto-publish
            'post_type' => 'post',
            'post_author' => get_current_user_id(),
        ];

        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // 3. Meta-Daten setzen (falls vorhanden)
        if (!empty($generated['description'])) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $generated['description']);
            update_post_meta($post_id, '_aioseop_description', $generated['description']);
        }

        if (!empty($generated['slug'])) {
            // Slug erst nach Erstellung setzen (sonst Konflikte)
            wp_update_post([
                'ID' => $post_id,
                'post_name' => sanitize_title($generated['slug']),
            ]);
        }

        // 4. Tags setzen
        if (!empty($generated['tags'])) {
            wp_set_post_tags($post_id, $generated['tags']);
        }

        // 5. In History speichern
        $this->log_generation($post_id, $params, $generated);

        return [
            'post_id' => $post_id,
            'edit_url' => get_edit_post_link($post_id, 'raw'),
            'preview_url' => get_preview_post_link($post_id),
            'title' => $generated['title'],
            'tags' => $generated['tags'],
        ];
    }

    /**
     * Bereitet den Content für den WP-Editor auf.
     * Konvertiert Markdown → HTML.
     */
    private function format_content_for_wp($markdown) {
        // H1 (sollte nur einmal vorkommen — wenn doch, zu H2 machen)
        $markdown = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $markdown);

        // H2 / H3
        $markdown = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $markdown);
        $markdown = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $markdown);
        $markdown = preg_replace('/^#### (.+)$/m', '<h4>$1</h4>', $markdown);

        // Bold / Italic
        $markdown = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $markdown);
        $markdown = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $markdown);

        // Bullet Lists
        $markdown = preg_replace('/^- (.+)$/m', '<li>$1</li>', $markdown);
        $markdown = preg_replace('/(<li>.*<\/li>\n?)+/', '<ul>$0</ul>', $markdown);

        // Numbered Lists
        $markdown = preg_replace('/^\d+\. (.+)$/m', '<li>$1</li>', $markdown);

        // Interne Link-Platzhalter [INTERN:name] → HTML-Kommentar (für späteren Review)
        $markdown = preg_replace('/\[INTERN:([^\]]+)\]/', '<!-- TODO: Interner Link zu "$1" -->', $markdown);

        // Absätze: Doppelte Newlines → </p><p>
        $blocks = preg_split('/\n\n+/', trim($markdown));
        $html_blocks = [];
        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block)) continue;

            // Wenn schon HTML-Block-Tag, nicht in <p> wrappen
            if (preg_match('/^<(h[1-6]|ul|ol|li|blockquote|hr)/', $block)) {
                $html_blocks[] = $block;
            } else {
                // Einzelne Newlines → <br>
                $block = nl2br($block);
                $html_blocks[] = '<p>' . $block . '</p>';
            }
        }

        return implode("\n\n", $html_blocks);
    }

    /**
     * Speichert die Generierung in einer History-Tabelle.
     */
    private function log_generation($post_id, $params, $generated) {
        $history = get_option('seo_tool_cg_history', []);
        $history[] = [
            'timestamp' => current_time('mysql'),
            'post_id' => $post_id,
            'keyword' => $params['keyword'],
            'intent' => $params['intent'] ?? 'informational',
            'word_count' => $params['word_count'] ?? 1800,
            'title' => $generated['title'],
            'user_id' => get_current_user_id(),
        ];

        // Nur letzte 50 Einträge
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }

        update_option('seo_tool_cg_history', $history, false);
    }
}

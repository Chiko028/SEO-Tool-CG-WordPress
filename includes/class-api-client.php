<?php
/**
 * MiniMax API-Client.
 *
 * Kommuniziert mit der MiniMax Chat-Completions-API (OpenAI-kompatibel).
 * Sendet Prompts, empfängt Antworten, behandelt Fehler.
 */

if (!defined('ABSPATH')) exit;

class SEO_Tool_CG_API_Client {

    private $api_key;
    private $base_url = SEO_TOOL_CG_API_BASE;
    private $timeout = 90; // Sekunden — Content-Generierung kann dauern

    public function __construct($api_key = null) {
        $this->api_key = $api_key ?: SEO_Tool_CG_Key_Manager::get_api_key();
    }

    /**
     * Testet die Verbindung zur API.
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return new WP_Error(
                'no_api_key',
                __('Kein API-Key konfiguriert. Bitte unter Einstellungen → SEO Tool CG hinterlegen.', 'seo-tool-cg')
            );
        }

        // Minimaler Test-Request
        $response = $this->request([
            'model' => get_option(SEO_TOOL_CG_OPTION_MODEL, 'MiniMax-M2'),
            'messages' => [
                ['role' => 'user', 'content' => 'Antworte nur mit "OK".'],
            ],
            'max_tokens' => 10,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        return [
            'success' => true,
            'message' => __('Verbindung erfolgreich.', 'seo-tool-cg'),
            'model' => get_option(SEO_TOOL_CG_OPTION_MODEL, 'MiniMax-M2'),
        ];
    }

    /**
     * Generiert Content basierend auf einem strukturierten Prompt.
     */
    public function generate_content($prompt_data) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Kein API-Key konfiguriert.', 'seo-tool-cg'));
        }

        $messages = $this->build_messages($prompt_data);

        $response = $this->request([
            'model' => get_option(SEO_TOOL_CG_OPTION_MODEL, 'MiniMax-M2'),
            'messages' => $messages,
            'max_tokens' => 4000,
            'temperature' => 0.7,
            'top_p' => 0.9,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $content = $response['choices'][0]['message']['content'] ?? '';

        // Token-Usage loggen
        if (isset($response['usage'])) {
            $this->log_usage($prompt_data, $response['usage']);
        }

        return $this->parse_content($content);
    }

    /**
     * Baut den Prompt für die Content-Generierung.
     */
    private function build_messages($data) {
        $brand = get_option(SEO_TOOL_CG_OPTION_BRAND, []);
        $company_name = $brand['company_name'] ?? get_bloginfo('name');
        $city = $brand['city'] ?? '';
        $language = $brand['language'] ?? 'de';
        $tone = $brand['tone'] ?? 'professionell, aber zugänglich';

        $system_prompt = sprintf(
            'Du bist ein erfahrener SEO-Texter für die Webdesign-Agentur "%s"%s.
Du schreibst in %s.
Dein Ton: %s.
Du kennst aktuelle SEO-Best-Practices (Google E-E-A-T, hilfreiche Inhalte, korrekte HTML-Struktur).
Du lieferst vollständige, sofort einsetzbare WordPress-Inhalte mit klarer H-Tag-Struktur.
Du erfindest keine erfundenen Statistiken oder Fakten.',
            $company_name,
            $city ? " aus/in $city" : '',
            $language === 'de' ? 'Deutsch auf muttersprachlichem Niveau' : 'Englisch',
            $tone
        );

        $user_prompt = $this->build_user_prompt($data, $brand);

        return [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $user_prompt],
        ];
    }

    /**
     * Baut den User-Prompt aus den Eingabedaten.
     */
    private function build_user_prompt($data, $brand) {
        $keyword = $data['keyword'];
        $intent = $data['intent'] ?? 'informational';
        $word_count = $data['word_count'] ?? 1800;
        $include_faq = $data['include_faq'] ?? true;
        $include_meta = $data['include_meta'] ?? true;

        $prompt = "Erstelle einen SEO-optimierten Blogartikel zum Hauptkeyword: \"$keyword\"\n\n";

        $prompt .= "Anforderungen:\n";
        $prompt .= "- Suchintention: " . $this->intent_label($intent) . "\n";
        $prompt .= "- Wortanzahl: ca. $word_count Wörter\n";
        $prompt .= "- Sprache: " . ($brand['language'] ?? 'de') . "\n";

        if (!empty($brand['city'])) {
            $prompt .= "- Lokaler Bezug: " . $brand['city'] . " (verwende konkrete Stadtteile/Bezirke wenn passend)\n";
        }

        $prompt .= "\nStruktur:\n";
        $prompt .= "- Genau eine H1 (= Hauptkeyword, fängt den Artikel ein)\n";
        $prompt .= "- 4-6 H2-Überschriften mit klarer Hierarchie\n";
        $prompt .= "- Bei Bedarf H3 unter H2 für Unterpunkte\n";
        $prompt .= "- Absätze maximal 3-4 Sätze, gut scanbar\n";
        $prompt .= "- Aufzählungen wo passend (Bullet-Listen)\n";

        $prompt .= "\nSEO-Anforderungen:\n";
        $prompt .= "- Hauptkeyword in H1, erster Absatz, einer H2, Meta-Title\n";
        $prompt .= "- 2-3 verwandte Begriffe/Synonyme natürlich einbauen\n";
        $prompt .= "- Interne Verlinkungen: An passenden Stellen [INTERN:name-des-anderen-artikels] als Platzhalter\n";
        $prompt .= "- Keine erfundenen Fakten/Statistiken\n";

        if ($include_meta) {
            $prompt .= "\nMeta-Daten (am Ende des Artikels in einem separaten Block):\n";
            $prompt .= "TITLE: [50-60 Zeichen, Hauptkeyword vorne]\n";
            $prompt .= "DESCRIPTION: [150-160 Zeichen, Call-to-Action, Hauptkeyword]\n";
            $prompt .= "SLUG: [keyword-freundlich, max 50 Zeichen]\n";
            $prompt .= "TAGS: [3-5 relevante Tags, kommagetrennt]\n";
        }

        if ($include_faq) {
            $prompt .= "\nFAQ-Block (am Ende, vor den Meta-Daten):\n";
            $prompt .= "- 5 Fragen, die echte Suchanfragen abbilden (People Also Ask-Stil)\n";
            $prompt .= "- Jeweils kurze, präzise Antwort (2-4 Sätze)\n";
            $prompt .= "- Format: **Frage:** ... **Antwort:** ...\n";
        }

        $prompt .= "\nFormat-Anforderung:\n";
        $prompt .= "Gib nur den Artikel zurück — keine Einleitung wie \"Hier ist der Artikel:\", keine Erklärungen.\n";
        $prompt .= "Beginne direkt mit der H1.\n";

        return $prompt;
    }

    /**
     * Parst den generierten Content in strukturierte Daten.
     */
    private function parse_content($raw_content) {
        $result = [
            'content' => '',
            'title' => '',
            'description' => '',
            'slug' => '',
            'tags' => [],
        ];

        // Meta-Block extrahieren (steht am Ende)
        if (preg_match('/TITLE:\s*(.+?)(?=\n|$)/m', $raw_content, $m)) {
            $result['title'] = trim($m[1]);
        }
        if (preg_match('/DESCRIPTION:\s*(.+?)(?=\n|$)/m', $raw_content, $m)) {
            $result['description'] = trim($m[1]);
        }
        if (preg_match('/SLUG:\s*(.+?)(?=\n|$)/m', $raw_content, $m)) {
            $result['slug'] = trim($m[1]);
        }
        if (preg_match('/TAGS:\s*(.+?)(?=\n|$)/m', $raw_content, $m)) {
            $tags = explode(',', $m[1]);
            $result['tags'] = array_map('trim', $tags);
        }

        // Content ist alles bis zum Meta-Block
        $content = $raw_content;

        // FAQ-Block + Meta-Block abschneiden, damit nur Body bleibt
        $cut_markers = ['FAQ-Block', 'FAQ:', 'TITLE:', '**Frage:**'];
        $earliest_cut = strlen($content);
        foreach ($cut_markers as $marker) {
            $pos = strpos($content, $marker);
            if ($pos !== false && $pos < $earliest_cut) {
                $earliest_cut = $pos;
            }
        }
        if ($earliest_cut < strlen($content)) {
            $content = substr($content, 0, $earliest_cut);
        }

        $result['content'] = trim($content);

        // Wenn kein Title gefunden, erste H1 nehmen
        if (empty($result['title']) && preg_match('/^#\s+(.+?)$/m', $result['content'], $m)) {
            $result['title'] = trim($m[1]);
        }

        return $result;
    }

    /**
     * Macht den HTTP-Request.
     */
    private function request($body) {
        $response = wp_remote_post($this->base_url . '/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($body),
            'timeout' => $this->timeout,
            'sslverify' => true,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($status !== 200) {
            $message = $data['error']['message'] ?? "HTTP $status";
            return new WP_Error('api_error', sprintf(
                /* translators: %s: API error message */
                __('API-Fehler: %s', 'seo-tool-cg'),
                $message
            ));
        }

        return $data;
    }

    /**
     * Loggt Token-Verbrauch (optional).
     */
    private function log_usage($prompt_data, $usage) {
        $entry = [
            'timestamp' => current_time('mysql'),
            'keyword' => $prompt_data['keyword'] ?? '',
            'input_tokens' => $usage['prompt_tokens'] ?? 0,
            'output_tokens' => $usage['completion_tokens'] ?? 0,
            'total_tokens' => $usage['total_tokens'] ?? 0,
        ];

        $log = get_option('seo_tool_cg_usage_log', []);
        $log[] = $entry;

        // Nur letzte 100 Einträge behalten
        if (count($log) > 100) {
            $log = array_slice($log, -100);
        }

        update_option('seo_tool_cg_usage_log', $log, false);
    }

    /**
     * Hilfsfunktion: Suchintention-Label.
     */
    private function intent_label($intent) {
        $map = [
            'informational' => 'informativ (User sucht Wissen/Antworten)',
            'commercial' => 'kommerziell (User vergleicht Optionen vor Kauf)',
            'transactional' => 'transaktional (User will kaufen/buchen)',
            'navigational' => 'navigational (User sucht bestimmte Seite)',
        ];
        return $map[$intent] ?? $map['informational'];
    }
}

=== SEO Tool CG ===
Contributors: Chiko028
Tags: seo, ai, content, generation, minimax
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

AI-gestützte Content-Generierung für WordPress. Erstellt SEO-optimierte Seiten-Entwürfe basierend auf Keywords. Verwendet die MiniMax API.

== Beschreibung ==

SEO Tool CG ist ein WordPress-Plugin, das SEO-optimierte Content-Entwürfe direkt im WP-Editor generiert.

**Features:**
* Generiert vollständige Blogartikel als Draft (niemals auto-publish)
* Verwendet die MiniMax API (kompatibel mit OpenAI)
* Verschlüsselte Speicherung des API-Keys in der WP-Datenbank
* Strukturierte Prompts für SEO-Texte (H1/H2/H3, Meta-Daten, FAQ)
* Brand-Settings (Firmenname, Standort, Tonalität) für personalisierte Inhalte
* Token-Tracking + Kostenschätzung
* Verlauf aller generierten Artikel

**Sicherheit:**
* API-Key wird mit AES-256-CBC verschlüsselt
* Inhalte werden IMMER als Draft erstellt (Admin muss manuell veröffentlichen)
* Keine externen Requests außer zur MiniMax API

== Installation ==

1. Lade das Plugin hoch unter `wp-content/plugins/seo-tool-cg/` oder installiere die ZIP über WP-Admin → Plugins → Installieren → Hochladen
2. Aktiviere das Plugin
3. Gehe zu Einstellungen → SEO Tool CG
4. Trage deinen MiniMax API-Key ein (erhältlich auf https://platform.minimax.io)
5. Klicke "Verbindung testen"
6. Optional: Trage deine Brand-Informationen ein
7. Erstelle einen neuen Post/Page → "Mit AI generieren" in der Meta-Box

== Frequently Asked Questions ==

= Ist der API-Key sicher? =

Ja. Der Key wird mit AES-256-CBC verschlüsselt in der WP-Options-Tabelle gespeichert, basierend auf den WordPress-SALTs aus wp-config.php.

= Veröffentlicht das Plugin automatisch? =

Nein. Alle generierten Inhalte sind Drafts. Du musst manuell im Editor veröffentlichen.

= Welche Kosten fallen an? =

Die MiniMax API nutzt Pay-as-you-go. Eine typische 1800-Wörter-Seite kostet ca. $0.01-0.05. 100 Seiten ≈ $1-5.

= Was ist die "MiniMax API"? =

MiniMax ist ein KI-Anbieter mit OpenAI-kompatibler API. Du kannst auch andere OpenAI-kompatible Anbieter verwenden, indem du den Endpoint im Source-Code anpasst.

== Screenshots ==

1. Einstellungen-Seite mit API-Key-Eingabe
2. Brand-Settings
3. Meta-Box im Post-Editor mit Generierungs-Optionen
4. Verlauf generierter Artikel

== Changelog ==

= 1.0.0 =
* Initial Release
* MiniMax API-Integration
* Verschlüsselter API-Key-Storage
* Meta-Box im Editor
* Verlauf + Token-Tracking

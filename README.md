# SEO Tool CG — WordPress Plugin

AI-gestützte Content-Generierung für WordPress. Erstellt SEO-optimierte Seiten-Entwürfe basierend auf Keywords, mit der **MiniMax API**.

> **Schwesterprojekt zu [SEO-Tool-CG](https://github.com/Chiko028/SEO-Tool-CG) (das Backend-Tool mit Audits + Tracking + Maßnahmenplänen).**

## Was macht das Plugin?

Im WordPress-Post-Editor gibt es eine neue Meta-Box mit "Mit AI generieren":

1. Du gibst ein **Hauptkeyword** ein (z.B. "Webdesign Wien 2026")
2. Wählst **Suchintention** + **Wortanzahl** + Optionen (FAQ, Meta-Daten)
3. Klickst **"Mit AI generieren"**
4. Plugin ruft die MiniMax API auf
5. **~30-60 Sekunden** später ist der Artikel als **Draft** in deinem WP-Editor
6. Du reviewst, passt an, klickst **selbst** auf "Veröffentlichen"

**Niemals** wird etwas automatisch veröffentlicht.

## Features

- ✅ **MiniMax API Integration** (OpenAI-kompatibel)
- ✅ **AES-256-CBC verschlüsselter API-Key** in WP-DB
- ✅ **Strukturierte Prompts** für SEO-Texte (H1/H2/H3 Hierarchie)
- ✅ **Meta-Daten-Generierung** (Title, Description, Slug, Tags)
- ✅ **FAQ-Block** (Schema.org-stil, People Also Ask)
- ✅ **Brand-Settings** (Firmenname, Standort, Tonalität)
- ✅ **Token-Tracking + Kostenschätzung**
- ✅ **Verlauf aller Drafts**
- ✅ **WordPress-SALTs-basierte Verschlüsselung** für den API-Key

## Installation

### Option A — ZIP-Upload in WP-Admin

1. Lade `seo-tool-cg.zip` herunter (siehe Releases)
2. WP-Admin → **Plugins** → **Installieren** → **Plugin hochladen**
3. ZIP auswählen → Installieren → Aktivieren

### Option B — Manuell per FTP

1. Ordner `seo-tool-cg` nach `wp-content/plugins/` kopieren
2. WP-Admin → Plugins → "SEO Tool CG" → Aktivieren

### Option C — WP-CLI

```bash
wp plugin install seo-tool-cg.zip --activate
```

## Setup nach Installation

1. **MiniMax API-Key holen:**
   - Account auf https://platform.minimax.io erstellen
   - Guthaben laden (z.B. $5)
   - API-Key erstellen

2. **Im WordPress-Admin:**
   - **Einstellungen** → **SEO Tool CG**
   - API-Key einfügen
   - **"Verbindung testen"** klicken
   - Brand-Informationen eintragen (optional aber empfohlen)
   - Speichern

3. **Ersten Artikel generieren:**
   - **Posts** → **Erstellen**
   - "Mit AI generieren" Meta-Box rechts/seitlich
   - Keyword eingeben → generieren
   - **WICHTIG:** Inhalt erscheint als Draft, du musst selbst veröffentlichen

## Sicherheit

### API-Key-Speicherung

Der MiniMax API-Key wird **verschlüsselt** in `wp_options` gespeichert:

```php
// Verschlüsselung mit AES-256-CBC
// Schlüssel wird aus WordPress-SALTs abgeleitet
// IV wird zufällig pro Verschlüsselung erzeugt
```

Das ist **nicht 100% sicher gegen Root-Zugriff** auf den Server (jemand mit DB-Zugriff + wp-config.php-Zugriff kann entschlüsseln), aber deutlich besser als Klartext-Storage.

### Draft-only Policy

**Das Plugin erstellt IMMER Drafts, niemals direkt veröffentlichte Posts.** Du behältst 100% Kontrolle.

### Nonce-Protection

Alle AJAX-Requests nutzen WordPress-Nonces — kein Cross-Site-Request-Forgery möglich.

### Permission-Checks

- Settings-Seite: nur `manage_options` (Admin)
- Content-Generierung: nur `edit_posts` (Editor + Admin)

## Entwicklung

### Projektstruktur

```
seo-tool-cg/
├── seo-tool-cg.php              # Haupt-Plugin-Datei
├── readme.txt                   # WordPress-Plugin-Readme
├── README.md                    # Diese Datei
├── includes/
│   ├── class-key-manager.php    # Verschlüsselte Key-Verwaltung
│   ├── class-api-client.php     # MiniMax API-Kommunikation
│   └── class-content-generator.php # Markdown → WP-Draft
├── admin/
│   ├── class-settings-page.php  # Einstellungen-Seite
│   ├── class-meta-box.php       # Post-Editor Meta-Box
│   ├── class-ajax-handler.php   # AJAX-Endpoints
│   └── class-history-page.php   # Verlauf
└── assets/
    ├── css/
    │   ├── admin.css
    │   └── meta-box.css
    └── js/
        ├── admin.js
        └── meta-box.js
```

### Andere LLM-APIs nutzen

Der Endpoint ist auf `https://api.minimax.io/v1/chat/completions` hardcoded. Für andere OpenAI-kompatible Anbieter:

```php
// includes/class-api-client.php
private $base_url = 'https://api.dein-provider.com/v1';
```

Das Model-Format ist OpenAI-kompatibel, sollte mit den meisten Anbietern funktionieren.

## Lizenz

MIT

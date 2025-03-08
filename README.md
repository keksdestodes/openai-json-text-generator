# OpenAI JSON Text Transformer

Diese PHP-Bibliothek ermöglicht die Umschreibung von Texten mit Hilfe der OpenAI API im JSON-Modus. Die Bibliothek nutzt einen mehrstufigen Prozess, um aus vorhandenen Texten neue, strukturierte Inhalte zu generieren.

## Funktionsweise

Die Bibliothek führt folgende Schritte durch:

1. **Erstellung einer Gliederung (Outline)**: Analysiert den Eingabetext und erstellt eine strukturierte Gliederung.
2. **Generierung eines Titels**: Erstellt einen passenden Titel basierend auf der Gliederung.
3. **Generierung einer Einleitung**: Erstellt eine einleitende Zusammenfassung.
4. **Generierung von Abschnitten**: Erstellt jeden Abschnitt und Unterabschnitt basierend auf der Gliederung.
5. **Zusammenstellung des Ergebnisses**: Fasst alle generierten Teile zu einem strukturierten JSON-Dokument zusammen.

## Voraussetzungen

- PHP 8.2 oder höher
- cURL-Erweiterung für PHP
- OpenAI API-Schlüssel

## Installation

1. Klone oder lade dieses Repository herunter
2. Kopiere `config.example.php` zu `config.php` und passe die Konfiguration an
3. Stelle sicher, dass dein OpenAI API-Schlüssel in der Konfigurationsdatei eingetragen ist

## Konfiguration

Die Konfigurationsdatei `config.php` enthält folgende Abschnitte:

### API-Einstellungen

```php
'api' => [
    'key' => 'DEIN_OPENAI_API_KEY_HIER', // OpenAI API Schlüssel
    'model' => 'gpt-4o-mini', // Modell für die Texterzeugung
    'temperature' => 0.7, // Standard-Temperatur (0.0 - 1.0)
    'presence_penalty' => 0.0, // Penalty für neue Themen (-2.0 - 2.0)
    'frequency_penalty' => 0.0, // Penalty für Wiederholungen (-2.0 - 2.0)
    'max_retries' => 5, // Maximale Anzahl an Wiederholungsversuchen bei Fehlern
    'retry_delay' => 1, // Wartezeit in Sekunden zwischen Wiederholungsversuchen
],
```

### Spezifische Temperatureinstellungen

```php
'temperatures' => [
    'outline' => 0.7,  // Temperatur für die Erstellung der Gliederung
    'title' => 0.8,    // Temperatur für Titel (höher für kreativere Überschriften)
    'introduction' => 0.7, // Temperatur für Einleitungen
    'section' => 0.6,   // Temperatur für Textabschnitte (niedriger für sachlicheren Text)
],
```

Du kannst diese Temperaturen nach Bedarf anpassen:
- Höhere Werte (0.8-1.0) sorgen für kreativere, überraschendere Texte
- Niedrigere Werte (0.2-0.5) sorgen für fokussiertere, deterministischere Texte
- Mittlere Werte (0.6-0.7) bieten eine gute Balance

### Logging-Einstellungen

```php
'logging' => [
    'enabled' => true, // Logging aktivieren/deaktivieren
    'path' => __DIR__ . '/logs/api.log', // Pfad zur Logdatei
    'level' => 'DEBUG', // Log-Level: DEBUG, INFO, WARNING, ERROR
],
```

### Prompts

Die Prompts bestimmen, wie OpenAI die Texte umschreiben soll. Du kannst sie an deine Bedürfnisse anpassen:

- `outline`: Prompt für die Erstellung der Gliederung
- `title`: Prompt für die Erzeugung des Titels
- `introduction`: Prompt für die Erzeugung der Einleitung
- `section`: Prompt für die Erzeugung der Abschnitte

## Verwendung

Das folgende Beispiel zeigt, wie du die Bibliothek verwenden kannst:

```php
// Lade die erforderlichen Klassen
use OpenAIJsonTransformer\Utils\ConfigManager;
use OpenAIJsonTransformer\Utils\Logger;
use OpenAIJsonTransformer\OpenAI\OpenAIClient;
use OpenAIJsonTransformer\OpenAI\TextTransformer;

// Initialisiere die Komponenten
$configManager = new ConfigManager('config.php');
$logger = new Logger($configManager->getLoggingConfig());
$openaiClient = new OpenAIClient($configManager->getApiConfig(), $logger);
$textTransformer = new TextTransformer($openaiClient, $configManager, $logger);

// Lade die Artikel aus einer JSON-Datei
$articlesJson = file_get_contents('texte.json');
$articles = json_decode($articlesJson, true);

// Transformiere die Artikel
$result = $textTransformer->transformArticles($articles);

// Speichere das Ergebnis
file_put_contents('output.json', json_encode($result, JSON_PRETTY_PRINT));
```

### Beispielskript

Im Ordner `examples` findest du ein vollständiges Beispielskript:

```bash
php examples/transform_article.php
```

## Eingabeformat

Die Bibliothek erwartet Artikel im folgenden JSON-Format:

```json
{
  "articles": [
    {
      "title": "Artikel-Titel",
      "text": "Artikel-Text..."
    },
    {
      "title": "Weiterer Artikel-Titel",
      "text": "Weiterer Artikel-Text..."
    }
  ]
}
```

## Ausgabeformat

Die Bibliothek erzeugt JSON im folgenden Format:

```json
{
  "title": "Generierter Haupttitel",
  "introduction": "Generierte Einleitung...",
  "sections": [
    {
      "heading": "Abschnittsüberschrift",
      "content": "Abschnittsinhalt..."
    },
    {
      "heading": "Abschnittsüberschrift mit Unterabschnitten",
      "content": "Abschnittsinhalt...",
      "subsections": [
        {
          "heading": "Unterabschnittsüberschrift",
          "content": "Unterabschnittsinhalt..."
        }
      ]
    }
  ]
}
```

## Fehlerbehandlung

Die Bibliothek implementiert eine robuste Fehlerbehandlung mit automatischen Wiederholungsversuchen bei API-Fehlern. Alle Fehler werden in der Logdatei protokolliert.

## Erweiterungen

Die Bibliothek ist so konzipiert, dass sie leicht erweitert werden kann:

- **Weitere LLM-Anbieter**: Die Struktur ermöglicht die Integration weiterer Anbieter wie Anthropic.
- **Zusätzliche Transformationen**: Das Transformationssystem kann um weitere Textverarbeitungsfunktionen erweitert werden.

## Lizenz

Dieses Projekt steht unter der [MIT-Lizenz](LICENSE). 
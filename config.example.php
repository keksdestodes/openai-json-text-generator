<?php
/**
 * Beispiel-Konfigurationsdatei für die OpenAI JSON Text-Transformation
 * 
 * Diese Datei enthält alle erforderlichen Einstellungen und Prompts für die Text-Transformation
 * mittels OpenAI API im JSON-Modus.
 */

return [
    // OpenAI API-Einstellungen
    'api' => [
        'key' => 'DEIN_OPENAI_API_KEY_HIER', // OpenAI API Schlüssel
        'model' => 'gpt-4o-mini', // Modell für die Texterzeugung
        'temperature' => 0.7, // Standard-Temperatur (0.0 - 1.0)
        'presence_penalty' => 0.0, // Penalty für neue Themen (-2.0 - 2.0)
        'frequency_penalty' => 0.0, // Penalty für Wiederholungen (-2.0 - 2.0)
        'max_retries' => 5, // Maximale Anzahl an Wiederholungsversuchen bei Fehlern
        'retry_delay' => 1, // Wartezeit in Sekunden zwischen Wiederholungsversuchen
    ],
    
    // Spezifische Temperatureinstellungen für verschiedene Teile
    'temperatures' => [
        'outline' => 0.7,  // Temperatur für die Erstellung der Gliederung
        'title' => 0.8,    // Temperatur für Titel (höher für kreativere Überschriften)
        'introduction' => 0.7, // Temperatur für Einleitungen
        'section' => 0.6,   // Temperatur für Textabschnitte (niedriger für sachlicheren Text)
    ],
    
    // Logging-Einstellungen
    'logging' => [
        'enabled' => true, // Logging aktivieren/deaktivieren
        'path' => __DIR__ . '/logs/api.log', // Pfad zur Logdatei
        'level' => 'DEBUG', // Log-Level: DEBUG, INFO, WARNING, ERROR
    ],
    
    // Prompts für die verschiedenen Schritte der Text-Transformation
    'prompts' => [
        // Prompt für die Erstellung einer Gliederung (Outline)
        'outline' => "Analysiere die folgenden Artikel und erstelle eine klare, strukturierte Gliederung (Outline) für einen neuen Artikel, der die wichtigsten Informationen zusammenfasst und neu organisiert. Deine Outline sollte eine Hauptüberschrift, eine Einleitung und verschiedene Abschnitte mit Zwischenüberschriften enthalten. Jeder Abschnitt kann Unterabschnitte haben. 

Bitte gib deine Antwort ausschließlich als valides JSON-Objekt in folgendem Format zurück:
{
  \"title\": \"Haupttitel\",
  \"introduction\": \"Einleitungszusammenfassung\",
  \"sections\": [
    {
      \"heading\": \"Abschnittsüberschrift\",
      \"summary\": \"Zusammenfassung des Abschnitts\"
    },
    {
      \"heading\": \"Abschnittsüberschrift mit Unterabschnitten\",
      \"subsections\": [
        {
          \"heading\": \"Unterabschnittsüberschrift\",
          \"summary\": \"Zusammenfassung des Unterabschnitts\"
        }
      ]
    }
  ]
}",

        // Prompt für die Erzeugung des Titels
        'title' => "Basierend auf der folgenden Gliederung, erstelle einen prägnanten, ansprechenden Titel für den Artikel. Der Titel sollte das Hauptthema genau wiedergeben und Interesse wecken.

Bitte gib deine Antwort ausschließlich als valides JSON-Objekt in folgendem Format zurück:
{
  \"title\": \"Der generierte Titel\"
}",

        // Prompt für die Erzeugung der Einleitung
        'introduction' => "Basierend auf der folgenden Gliederung, erstelle eine fesselnde Einleitung für den Artikel. Die Einleitung sollte das Thema vorstellen, den Leser interessieren und einen Überblick über den Inhalt des Artikels geben.

Bitte gib deine Antwort ausschließlich als valides JSON-Objekt in folgendem Format zurück:
{
  \"introduction\": \"Der Text der Einleitung\"
}",

        // Prompt für die Erzeugung der Abschnitte
        'section' => "Basierend auf der folgenden Gliederung, erstelle den folgenden Abschnitt des Artikels. Der Text sollte informativ, gut strukturiert und leicht verständlich sein. Verwende einen ansprechenden, professionellen Schreibstil.

Abschnitt: {{section_heading}}
Zusammenfassung: {{section_summary}}

Bitte gib deine Antwort ausschließlich als valides JSON-Objekt in folgendem Format zurück:
{
  \"heading\": \"Die finale Abschnittsüberschrift\",
  \"content\": \"Der vollständige Text des Abschnitts\"
}",
    ],
]; 
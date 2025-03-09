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
    
    // Prompts für die Textgenerierung
    'prompts' => [
      'outline' => "Analysiere den folgenden Text und erstelle eine strukturierte Gliederung mit Hauptüberschriften (H2) und jeweils 2-3 Absätzen Text pro Überschrift. Die Gliederung sollte die wichtigsten Themen und Informationen des Textes erfassen und logisch strukturiert sein. Gib das Ergebnis als JSON-Objekt zurück mit dem folgenden Format: {\"sections\": [{\"heading\": \"Überschrift\", \"content\": \"2-3 Absätze Text\"}]}",
      
      'title' => "Basierend auf der folgenden Gliederung, erstelle einen prägnanten, ansprechenden Titel, der das Hauptthema des Textes widerspiegelt. Der Titel sollte informativ, aber auch interessant sein, um Leser anzuziehen. Gib das Ergebnis als JSON-Objekt zurück mit dem folgenden Format: {\"title\": \"Generierter Titel\"}",
      
      'introduction' => "Erstelle eine einleitende Zusammenfassung für einen Artikel mit dem folgenden Titel und der folgenden Gliederung. Die Einleitung sollte das Hauptthema vorstellen, die wichtigsten Punkte andeuten und das Interesse des Lesers wecken. Gib das Ergebnis als JSON-Objekt zurück mit dem folgenden Format: {\"introduction\": \"Generierte Einleitung\"}",
      
      'section' => "Erstelle einen detaillierten Abschnitt für den folgenden Punkt aus der Gliederung eines Artikels. Der Abschnitt sollte informativ, gut strukturiert und leicht verständlich sein. Verwende einen sachlichen, aber ansprechenden Schreibstil. Gib das Ergebnis als JSON-Objekt zurück mit dem folgenden Format: {\"content\": \"Generierter Inhalt\"}",
  ],
]; 
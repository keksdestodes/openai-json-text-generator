<?php
/**
 * Beispielskript zur Demonstration der OpenAI JSON Texttransformation
 * 
 * Dieses Skript zeigt, wie man die Bibliothek verwendet, um einen oder mehrere Artikel zu transformieren.
 */

// Autoloading (in einem echten Projekt würde man Composer verwenden)
spl_autoload_register(function($class) {
    $prefix = 'OpenAIJsonTransformer\\';
    
    // Überprüfe, ob die angeforderte Klasse den Namespace-Präfix verwendet
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Entferne den Namespace-Präfix für den relativen Klassennamen
    $relative_class = substr($class, $len);
    
    // Ersetze Namespace-Separatoren durch Verzeichnistrenner
    // Füge '.php' am Ende hinzu
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', $relative_class) . '.php';
    
    // Wenn die Datei existiert, erfordere sie
    if (file_exists($file)) {
        require $file;
    }
});

use OpenAIJsonTransformer\Utils\ConfigManager;
use OpenAIJsonTransformer\Utils\Logger;
use OpenAIJsonTransformer\OpenAI\OpenAIClient;
use OpenAIJsonTransformer\OpenAI\TextTransformer;

// Pfad zur Konfigurationsdatei
$configFile = __DIR__ . '/../config.php';

// Überprüfen, ob die Konfigurationsdatei existiert
if (!file_exists($configFile)) {
    echo "Konfigurationsdatei nicht gefunden. Bitte erstelle eine config.php basierend auf config.example.php" . PHP_EOL;
    exit(1);
}

try {
    // Initialisiere Konfigurationsmanager
    $configManager = new ConfigManager($configFile);
    
    // Initialisiere Logger
    $logger = new Logger($configManager->getLoggingConfig());
    
    // Initialisiere OpenAI Client
    $openaiClient = new OpenAIClient($configManager->getApiConfig(), $logger);
    
    // Initialisiere TextTransformer
    $textTransformer = new TextTransformer($openaiClient, $configManager, $logger);
    
    // Lade JSON-Datei mit Artikeln
    $jsonFile = __DIR__ . '/../texte.json';
    
    if (!file_exists($jsonFile)) {
        echo "Artikel-JSON-Datei nicht gefunden: {$jsonFile}" . PHP_EOL;
        exit(1);
    }
    
    $articlesJson = file_get_contents($jsonFile);
    $articles = json_decode($articlesJson, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Fehler beim Parsen der JSON-Datei: " . json_last_error_msg() . PHP_EOL;
        exit(1);
    }
    
    // Führe die Transformation durch
    echo "Starte Transformation der Artikel..." . PHP_EOL;
    $result = $textTransformer->transformArticles($articles);
    
    if ($result) {
        echo "Transformation erfolgreich!" . PHP_EOL;
        echo "Neuer Titel: " . $result['title'] . PHP_EOL;
        echo "Anzahl der Abschnitte: " . count($result['sections']) . PHP_EOL;
        
        // Speichere das Ergebnis in einer Datei
        $outputFile = __DIR__ . '/../output.json';
        file_put_contents($outputFile, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        
        echo "Das Ergebnis wurde in '{$outputFile}' gespeichert." . PHP_EOL;
    } else {
        echo "Transformation fehlgeschlagen. Bitte überprüfe die Logdatei für Details." . PHP_EOL;
    }
} catch (\Exception $e) {
    echo "Fehler: " . $e->getMessage() . PHP_EOL;
    exit(1);
} 
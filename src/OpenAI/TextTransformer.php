<?php

namespace OpenAIJsonTransformer\OpenAI;

use OpenAIJsonTransformer\Utils\ConfigManager;
use OpenAIJsonTransformer\Utils\Logger;

/**
 * Klasse für die Transformation von Texten mit OpenAI
 */
class TextTransformer {
    /**
     * @var OpenAIClient OpenAI-Client
     */
    private OpenAIClient $openaiClient;

    /**
     * @var ConfigManager Konfigurations-Manager
     */
    private ConfigManager $configManager;

    /**
     * @var Logger Logger-Instanz
     */
    private Logger $logger;
    
    /**
     * @var bool Gibt an, ob Fortschrittsinformationen angezeigt werden sollen
     */
    private bool $verbose = true;
    
    /**
     * @var bool Gibt an, ob Caching verwendet werden soll
     */
    private bool $useCache = false;
    
    /**
     * @var string Pfad zum Cache-Verzeichnis
     */
    private string $cachePath = '';

    /**
     * Konstruktor
     * 
     * @param OpenAIClient $openaiClient OpenAI-Client
     * @param ConfigManager $configManager Konfigurations-Manager
     * @param Logger $logger Logger-Instanz
     * @param bool $verbose Gibt an, ob Fortschrittsinformationen angezeigt werden sollen
     * @param bool $useCache Gibt an, ob Caching verwendet werden soll
     * @param string $cachePath Pfad zum Cache-Verzeichnis
     */
    public function __construct(
        OpenAIClient $openaiClient,
        ConfigManager $configManager,
        Logger $logger,
        bool $verbose = true,
        bool $useCache = false,
        string $cachePath = ''
    ) {
        $this->openaiClient = $openaiClient;
        $this->configManager = $configManager;
        $this->logger = $logger;
        $this->verbose = $verbose;
        $this->useCache = $useCache;
        $this->cachePath = $cachePath ?: __DIR__ . '/../../cache';
        
        // Erstelle das Cache-Verzeichnis, falls es noch nicht existiert
        if ($this->useCache && !is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }
    }
    
    /**
     * Gibt eine Fortschrittsmeldung aus, wenn der verbose-Modus aktiviert ist
     * 
     * @param string $message Die Meldung
     * @return void
     */
    private function showProgress(string $message): void
    {
        if ($this->verbose) {
            $timestamp = "[" . date('H:i:s') . "] ";
            echo $timestamp . $message . PHP_EOL;
            if (function_exists('ob_get_level') && ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }
    }

    /**
     * Transformiert eine Liste von Artikeln
     * 
     * @param array $articles Die zu transformierenden Artikel
     * @return array Die transformierten Artikel
     */
    public function transformArticles(array $articles): array {
        $this->logger->info('Starte Transformation von Artikeln', [
            'article_count' => count($articles)
        ]);

        $results = [];

        foreach ($articles as $index => $article) {
            $this->logger->info("Transformiere Artikel {$index} mit Titel: {$article['title']}");
            
            try {
                // Cache-Schlüssel generieren
                $cacheKey = $this->getCacheKey($article);
                $cachedResult = $this->getFromCache($cacheKey);
                
                if ($cachedResult !== null) {
                    $this->showProgress("  Artikel aus Cache geladen: " . $article['title']);
                    $result = $cachedResult;
                } else {
                    $result = $this->transformArticle($article);
                    // Speichere das Ergebnis im Cache
                    $this->saveToCache($cacheKey, $result);
                }
                
                $results[] = $result;
                
                $this->logger->info("Artikel {$index} erfolgreich transformiert");
            } catch (\Exception $e) {
                $this->logger->error("Fehler bei der Transformation von Artikel {$index}", [
                    'error' => $e->getMessage(),
                    'article_title' => $article['title']
                ]);
                
                // Füge einen Fehlereintrag hinzu
                $results[] = [
                    'error' => true,
                    'title' => $article['title'],
                    'message' => $e->getMessage()
                ];
            }
        }

        $this->logger->info('Transformation aller Artikel abgeschlossen', [
            'success_count' => count(array_filter($results, fn($r) => !isset($r['error']))),
            'error_count' => count(array_filter($results, fn($r) => isset($r['error'])))
        ]);

        return $results;
    }

    /**
     * Transformiert einen einzelnen Artikel
     * 
     * @param array $article Der zu transformierende Artikel
     * @return array Der transformierte Artikel
     */
    private function transformArticle(array $article): array {
        // Schritt 1: Erstelle eine Gliederung
        $this->showProgress("  Erstelle Gliederung für Artikel: " . $article['title']);
        $outline = $this->createOutline($article['text']);
        $this->logger->debug('Gliederung erstellt', ['outline' => $outline]);
        $this->showProgress("  ✓ Gliederung erstellt");

        // Schritt 2: Generiere einen Titel
        $this->showProgress("  Generiere Titel...");
        $title = $this->generateTitle($outline);
        $this->logger->debug('Titel generiert', ['title' => $title]);
        $this->showProgress("  ✓ Titel generiert: " . $title);

        // Schritt 3: Generiere eine Einleitung
        $this->showProgress("  Generiere Einleitung...");
        $introduction = $this->generateIntroduction($title, $outline);
        $this->logger->debug('Einleitung generiert', ['introduction_length' => strlen($introduction)]);
        $this->showProgress("  ✓ Einleitung generiert (" . strlen($introduction) . " Zeichen)");

        // Schritt 4: Generiere die Abschnitte
        $this->showProgress("  Generiere Abschnitte...");
        $sections = $this->generateSections($outline);
        $this->logger->debug('Abschnitte generiert', ['section_count' => count($sections)]);
        $this->showProgress("  ✓ " . count($sections) . " Abschnitte generiert");

        // Schritt 5: Stelle das Ergebnis zusammen
        $this->showProgress("  Erstelle Gesamtergebnis...");
        $result = [
            'title' => $title,
            'introduction' => $introduction,
            'sections' => $sections
        ];
        $this->showProgress("  ✓ Transformation abgeschlossen");
        
        return $result;
    }

    /**
     * Holt die Systemrolle für den angegebenen Typ aus der Konfiguration
     * 
     * @param string $type Der Typ (outline, title, introduction, section)
     * @return string Die Systemrolle
     */
    private function getSystemRole(string $type): string
    {
        // Versuche, die Rolle aus der Konfiguration zu laden
        $role = $this->configManager->getSystemRole($type);
        
        // Fallback zu Standard-Rollen, wenn keine konfiguriert ist
        if (empty($role)) {
            $defaultRoles = [
                'outline' => 'Du bist ein Assistent, der Texte analysiert und strukturierte Gliederungen als JSON erstellt. Gib immer ein gültiges JSON-Format zurück.',
                'title' => 'Du bist ein Assistent, der prägnante und ansprechende Titel basierend auf Textgliederungen erstellt. Gib immer ein gültiges JSON-Format zurück.',
                'introduction' => 'Du bist ein Assistent, der fesselnde Einleitungen basierend auf Titel und Gliederungen erstellt. Gib immer ein gültiges JSON-Format zurück.',
                'section' => 'Du bist ein Assistent, der informative und gut strukturierte Textabschnitte erstellt. Gib immer ein gültiges JSON-Format zurück.'
            ];
            
            $role = $defaultRoles[$type] ?? 'Du bist ein hilfreicher Assistent, der strukturierte Texte erstellt. Gib immer ein gültiges JSON-Format zurück.';
        }
        
        return $role;
    }

    /**
     * Erstellt eine Gliederung aus dem Text
     * 
     * @param string $text Der zu analysierende Text
     * @return array Die erstellte Gliederung
     */
    private function createOutline(string $text): array {
        $prompt = $this->configManager->getPrompt('outline');
        $temperature = $this->configManager->getTemperatures()['outline'];
        $systemRole = $this->getSystemRole('outline');

        $messages = [
            ['role' => 'system', 'content' => $systemRole],
            ['role' => 'user', 'content' => $prompt . "\n\nText:\n" . $text]
        ];

        $this->showProgress("    Sende Outline-Anfrage an API (Temperatur: $temperature)...");
        $response = $this->openaiClient->sendRequest($messages, $temperature);
        $this->showProgress("    ✓ API-Antwort für Outline erhalten");
        
        return $this->openaiClient->extractJsonContent($response);
    }

    /**
     * Generiert einen Titel basierend auf der Gliederung
     * 
     * @param array $outline Die Gliederung
     * @return string Der generierte Titel
     */
    private function generateTitle(array $outline): string {
        $prompt = $this->configManager->getPrompt('title');
        $temperature = $this->configManager->getTemperatures()['title'];
        $systemRole = $this->getSystemRole('title');

        $outlineJson = json_encode($outline, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $messages = [
            ['role' => 'system', 'content' => $systemRole],
            ['role' => 'user', 'content' => $prompt . "\n\nGliederung:\n" . $outlineJson]
        ];

        $this->showProgress("    Sende Titel-Anfrage an API (Temperatur: $temperature)...");
        $response = $this->openaiClient->sendRequest($messages, $temperature);
        $this->showProgress("    ✓ API-Antwort für Titel erhalten");
        
        $result = $this->openaiClient->extractJsonContent($response);
        return $result['title'];
    }

    /**
     * Generiert eine Einleitung basierend auf dem Titel und der Gliederung
     * 
     * @param string $title Der Titel
     * @param array $outline Die Gliederung
     * @return string Die generierte Einleitung
     */
    private function generateIntroduction(string $title, array $outline): string {
        $prompt = $this->configManager->getPrompt('introduction');
        $temperature = $this->configManager->getTemperatures()['introduction'];
        $systemRole = $this->getSystemRole('introduction');

        $outlineJson = json_encode($outline, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $messages = [
            ['role' => 'system', 'content' => $systemRole],
            ['role' => 'user', 'content' => $prompt . "\n\nTitel: " . $title . "\n\nGliederung:\n" . $outlineJson]
        ];

        $this->showProgress("    Sende Einleitungs-Anfrage an API (Temperatur: $temperature)...");
        $response = $this->openaiClient->sendRequest($messages, $temperature);
        $this->showProgress("    ✓ API-Antwort für Einleitung erhalten");
        
        $result = $this->openaiClient->extractJsonContent($response);
        return $result['introduction'];
    }

    /**
     * Generiert die Abschnitte basierend auf der Gliederung
     * 
     * @param array $outline Die Gliederung
     * @return array Die generierten Abschnitte
     */
    private function generateSections(array $outline): array {
        $prompt = $this->configManager->getPrompt('section');
        $temperature = $this->configManager->getTemperatures()['section'];
        $sections = [];

        if (!isset($outline['sections']) || !is_array($outline['sections'])) {
            $this->showProgress("    FEHLER: Ungültiges Outline-Format - 'sections' nicht gefunden oder kein Array");
            return [];
        }

        foreach ($outline['sections'] as $index => $section) {
            $sectionNumber = $index + 1;
            $this->showProgress("    Generiere Abschnitt $sectionNumber/" . count($outline['sections']) . ": " . ($section['heading'] ?? 'Ohne Überschrift'));
            
            $section = [
                'heading' => $section['heading'] ?? 'Ohne Überschrift',
                'content' => $section['content'] ?? $this->generateSectionContent($section, $prompt, $temperature)
            ];

            $sections[] = $section;
        }

        return $sections;
    }

    /**
     * Generiert den Inhalt eines Abschnitts
     * 
     * @param array $point Der Gliederungspunkt
     * @param string $prompt Der zu verwendende Prompt
     * @param float $temperature Die zu verwendende Temperatur
     * @return string Der generierte Inhalt
     */
    private function generateSectionContent(array $point, string $prompt, float $temperature): string {
        $systemRole = $this->getSystemRole('section');
        
        $pointJson = json_encode($point, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        $messages = [
            ['role' => 'system', 'content' => $systemRole],
            ['role' => 'user', 'content' => $prompt . "\n\nGliederungspunkt:\n" . $pointJson]
        ];
        
        $this->showProgress("      Sende Abschnitts-Anfrage an API (Temperatur: $temperature)...");
        $response = $this->openaiClient->sendRequest($messages, $temperature);
        $this->showProgress("      ✓ API-Antwort für Abschnitt erhalten");
        
        $result = $this->openaiClient->extractJsonContent($response);
        return $result['content'];
    }

    /**
     * Generiert einen Cache-Schlüssel für einen Artikel
     * 
     * @param array $article Der Artikel
     * @return string Der Cache-Schlüssel
     */
    private function getCacheKey(array $article): string
    {
        // Verwende MD5-Hash des Artikeltitels und -textes als Schlüssel
        return md5($article['title'] . '|' . $article['text']);
    }
    
    /**
     * Lädt ein Ergebnis aus dem Cache
     * 
     * @param string $cacheKey Der Cache-Schlüssel
     * @return array|null Das geladene Ergebnis oder null, wenn es nicht im Cache ist
     */
    private function getFromCache(string $cacheKey): ?array
    {
        if (!$this->useCache) {
            return null;
        }
        
        $cacheFile = $this->cachePath . '/' . $cacheKey . '.json';
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        $this->logger->debug('Lade Ergebnis aus Cache', ['cache_key' => $cacheKey]);
        
        try {
            $content = file_get_contents($cacheFile);
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            return $data;
        } catch (\Exception $e) {
            $this->logger->warning('Fehler beim Laden aus dem Cache', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Speichert ein Ergebnis im Cache
     * 
     * @param string $cacheKey Der Cache-Schlüssel
     * @param array $data Die zu speichernden Daten
     * @return bool Gibt an, ob das Speichern erfolgreich war
     */
    private function saveToCache(string $cacheKey, array $data): bool
    {
        if (!$this->useCache) {
            return false;
        }
        
        $cacheFile = $this->cachePath . '/' . $cacheKey . '.json';
        
        $this->logger->debug('Speichere Ergebnis im Cache', ['cache_key' => $cacheKey]);
        
        try {
            $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            file_put_contents($cacheFile, $content);
            return true;
        } catch (\Exception $e) {
            $this->logger->warning('Fehler beim Speichern im Cache', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
} 
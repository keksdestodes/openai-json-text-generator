<?php
/**
 * TextTransformer-Klasse für die Umschreibung von Texten mit OpenAI
 * 
 * @package OpenAIJsonTransformer
 */

namespace OpenAIJsonTransformer\OpenAI;

use OpenAIJsonTransformer\Utils\Logger;
use OpenAIJsonTransformer\Utils\ConfigManager;

class TextTransformer {
    /**
     * OpenAI-Client
     * 
     * @var OpenAIClient
     */
    private $openai;
    
    /**
     * Logger
     * 
     * @var Logger
     */
    private $logger;
    
    /**
     * Konfigurationsmanager
     * 
     * @var ConfigManager
     */
    private $config;

    /**
     * Konstruktor
     * 
     * @param OpenAIClient $openai OpenAI-Client
     * @param ConfigManager $config Konfigurationsmanager
     * @param Logger $logger Logger
     */
    public function __construct(OpenAIClient $openai, ConfigManager $config, Logger $logger) {
        $this->openai = $openai;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Transformiert einen Artikel basierend auf einer Gliederung
     * 
     * @param array $articles Die Artikel-Daten als Array
     * @return array|null Das transformierte Artikel-Objekt oder null bei Fehler
     */
    public function transformArticles(array $articles): ?array {
        try {
            // 1. Outline erstellen
            $outline = $this->createOutline($articles);
            if (!$outline) {
                $this->logger->error('Konnte keine Gliederung (Outline) erstellen');
                return null;
            }

            $this->logger->info('Gliederung erstellt', ['title' => $outline['title']]);

            // 2. Titel generieren
            $title = $this->generateTitle($outline);
            if (!$title) {
                $this->logger->error('Konnte keinen Titel generieren');
                return null;
            }

            $this->logger->info('Titel generiert', ['title' => $title]);

            // 3. Einleitung generieren
            $introduction = $this->generateIntroduction($outline);
            if (!$introduction) {
                $this->logger->error('Konnte keine Einleitung generieren');
                return null;
            }

            $this->logger->info('Einleitung generiert');

            // 4. Abschnitte generieren
            $sections = $this->generateSections($outline);
            if (empty($sections)) {
                $this->logger->error('Konnte keine Abschnitte generieren');
                return null;
            }

            $this->logger->info('Abschnitte generiert', ['count' => count($sections)]);

            // 5. Ergebnis zusammensetzen
            return [
                'title' => $title,
                'introduction' => $introduction,
                'sections' => $sections
            ];
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei der Texttransformation', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Holt die spezifische Temperatur für den angegebenen Generierungstyp
     * 
     * @param string $type Der Typ der Generierung (outline, title, introduction, section)
     * @return float Die zu verwendende Temperatur
     */
    private function getTemperature(string $type): float {
        // Holen aus spezifischen Temperatureinstellungen oder Fallback auf Standard
        $temperature = $this->config->get('temperatures.' . $type);
        if ($temperature === null) {
            $temperature = $this->config->get('api.temperature', 0.7);
        }
        return (float)$temperature;
    }

    /**
     * Erstellt eine Gliederung (Outline) für die Artikel
     * 
     * @param array $articles Die Artikel-Daten
     * @return array|null Die Gliederung oder null bei Fehler
     */
    private function createOutline(array $articles): ?array {
        $prompt = $this->config->getPrompt('outline');
        $temperature = $this->getTemperature('outline');
        
        $messages = [
            ['role' => 'system', 'content' => 'Du bist ein erfahrener Redakteur, der klar strukturierte Gliederungen erstellt.'],
            ['role' => 'user', 'content' => $prompt . "\n\n" . json_encode($articles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        ];
        
        try {
            $response = $this->openai->sendRequest($messages, ['temperature' => $temperature]);
            return $this->openai->extractJson($response);
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Erstellen der Gliederung', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generiert einen Titel basierend auf der Gliederung
     * 
     * @param array $outline Die Gliederung
     * @return string|null Der generierte Titel oder null bei Fehler
     */
    private function generateTitle(array $outline): ?string {
        $prompt = $this->config->getPrompt('title');
        $temperature = $this->getTemperature('title');
        
        $messages = [
            ['role' => 'system', 'content' => 'Du bist ein erfahrener Redakteur, der prägnante und ansprechende Titel erstellt.'],
            ['role' => 'user', 'content' => $prompt . "\n\n" . json_encode($outline, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        ];
        
        try {
            $response = $this->openai->sendRequest($messages, ['temperature' => $temperature]);
            $json = $this->openai->extractJson($response);
            
            return $json['title'] ?? null;
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Generieren des Titels', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generiert eine Einleitung basierend auf der Gliederung
     * 
     * @param array $outline Die Gliederung
     * @return string|null Die generierte Einleitung oder null bei Fehler
     */
    private function generateIntroduction(array $outline): ?string {
        $prompt = $this->config->getPrompt('introduction');
        $temperature = $this->getTemperature('introduction');
        
        $messages = [
            ['role' => 'system', 'content' => 'Du bist ein erfahrener Redakteur, der fesselnde Einleitungen schreibt.'],
            ['role' => 'user', 'content' => $prompt . "\n\n" . json_encode($outline, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        ];
        
        try {
            $response = $this->openai->sendRequest($messages, ['temperature' => $temperature]);
            $json = $this->openai->extractJson($response);
            
            return $json['introduction'] ?? null;
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Generieren der Einleitung', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generiert alle Abschnitte basierend auf der Gliederung
     * 
     * @param array $outline Die Gliederung
     * @return array|null Die generierten Abschnitte oder null bei Fehler
     */
    private function generateSections(array $outline): ?array {
        if (!isset($outline['sections']) || !is_array($outline['sections'])) {
            return null;
        }
        
        $sections = [];
        $prompt = $this->config->getPrompt('section');
        
        foreach ($outline['sections'] as $sectionOutline) {
            $section = $this->generateSection($sectionOutline, $prompt);
            
            if ($section) {
                $sections[] = $section;
                
                // Wenn der Abschnitt Unterabschnitte hat, diese auch generieren
                if (isset($sectionOutline['subsections']) && is_array($sectionOutline['subsections'])) {
                    $section['subsections'] = [];
                    
                    foreach ($sectionOutline['subsections'] as $subsectionOutline) {
                        $subsection = $this->generateSection($subsectionOutline, $prompt);
                        
                        if ($subsection) {
                            $section['subsections'][] = $subsection;
                        }
                    }
                }
            }
        }
        
        return $sections;
    }

    /**
     * Generiert einen einzelnen Abschnitt
     * 
     * @param array $sectionOutline Die Gliederung des Abschnitts
     * @param string $promptTemplate Der Prompt-Template für Abschnitte
     * @return array|null Der generierte Abschnitt oder null bei Fehler
     */
    private function generateSection(array $sectionOutline, string $promptTemplate): ?array {
        if (!isset($sectionOutline['heading'])) {
            return null;
        }
        
        $heading = $sectionOutline['heading'];
        $summary = $sectionOutline['summary'] ?? 'Keine Zusammenfassung verfügbar';
        $temperature = $this->getTemperature('section');
        
        // Platzhalter im Prompt ersetzen
        $prompt = str_replace(
            ['{{section_heading}}', '{{section_summary}}'],
            [$heading, $summary],
            $promptTemplate
        );
        
        $messages = [
            ['role' => 'system', 'content' => 'Du bist ein erfahrener Redakteur, der informative und gut strukturierte Abschnitte schreibt.'],
            ['role' => 'user', 'content' => $prompt]
        ];
        
        try {
            $response = $this->openai->sendRequest($messages, ['temperature' => $temperature]);
            $json = $this->openai->extractJson($response);
            
            if (isset($json['heading']) && isset($json['content'])) {
                return [
                    'heading' => $json['heading'],
                    'content' => $json['content']
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Generieren eines Abschnitts', [
                'heading' => $heading,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
} 
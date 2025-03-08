<?php
/**
 * Konfigurationsmanager für die Verwaltung der Anwendungseinstellungen
 * 
 * @package OpenAIJsonTransformer
 */

namespace OpenAIJsonTransformer\Utils;

class ConfigManager {
    /**
     * Konfigurationsdaten
     * 
     * @var array
     */
    private $config;

    /**
     * Konstruktor
     * 
     * @param string $configFile Pfad zur Konfigurationsdatei
     * @throws \Exception wenn die Konfigurationsdatei nicht gefunden wird
     */
    public function __construct(string $configFile) {
        if (!file_exists($configFile)) {
            throw new \Exception("Konfigurationsdatei nicht gefunden: {$configFile}");
        }

        $this->config = require $configFile;
        $this->validateConfig();
    }

    /**
     * Konfiguration validieren
     * 
     * @throws \Exception wenn wichtige Konfigurationseinstellungen fehlen
     * @return void
     */
    private function validateConfig(): void {
        // Überprüfe API-Konfiguration
        if (!isset($this->config['api'])) {
            throw new \Exception("API-Konfiguration fehlt");
        }
        
        if (!isset($this->config['api']['key']) || empty($this->config['api']['key'])) {
            throw new \Exception("API-Schlüssel fehlt oder ist leer");
        }
        
        if (!isset($this->config['api']['model']) || empty($this->config['api']['model'])) {
            throw new \Exception("API-Modell fehlt oder ist leer");
        }

        // Überprüfe Prompts
        if (!isset($this->config['prompts'])) {
            throw new \Exception("Prompts-Konfiguration fehlt");
        }
        
        $requiredPrompts = ['outline', 'title', 'introduction', 'section'];
        foreach ($requiredPrompts as $prompt) {
            if (!isset($this->config['prompts'][$prompt]) || empty($this->config['prompts'][$prompt])) {
                throw new \Exception("Prompt für '{$prompt}' fehlt oder ist leer");
            }
        }
    }

    /**
     * Gibt die gesamte Konfiguration zurück
     * 
     * @return array
     */
    public function getConfig(): array {
        return $this->config;
    }

    /**
     * Gibt eine bestimmte Konfigurationseinstellung zurück
     * 
     * @param string $key Konfigurationsschlüssel (mit Punktnotation für verschachtelte Werte)
     * @param mixed $default Standardwert, wenn der Schlüssel nicht existiert
     * @return mixed
     */
    public function get(string $key, $default = null) {
        $parts = explode('.', $key);
        $config = $this->config;
        
        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                return $default;
            }
            $config = $config[$part];
        }
        
        return $config;
    }

    /**
     * Gibt die API-Konfiguration zurück
     * 
     * @return array
     */
    public function getApiConfig(): array {
        return $this->config['api'] ?? [];
    }

    /**
     * Gibt die Logging-Konfiguration zurück
     * 
     * @return array
     */
    public function getLoggingConfig(): array {
        return $this->config['logging'] ?? [];
    }

    /**
     * Gibt einen bestimmten Prompt zurück
     * 
     * @param string $promptName Name des Prompts
     * @return string|null
     */
    public function getPrompt(string $promptName): ?string {
        return $this->config['prompts'][$promptName] ?? null;
    }
} 
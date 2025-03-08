<?php
/**
 * Logger-Klasse für die Protokollierung von API-Anfragen, Antworten und Fehlern
 * 
 * @package OpenAIJsonTransformer
 */

namespace OpenAIJsonTransformer\Utils;

class Logger {
    /**
     * Verfügbare Log-Level
     */
    const DEBUG = 0;
    const INFO = 1;
    const WARNING = 2;
    const ERROR = 3;

    /**
     * Mapping von String-Log-Levels zu Konstanten
     */
    private static $levelMap = [
        'DEBUG' => self::DEBUG,
        'INFO' => self::INFO,
        'WARNING' => self::WARNING,
        'ERROR' => self::ERROR
    ];

    /**
     * Pfad zur Logdatei
     * 
     * @var string
     */
    private $logFile;

    /**
     * Aktuelles Log-Level
     * 
     * @var int
     */
    private $logLevel;

    /**
     * Ist Logging aktiviert?
     * 
     * @var bool
     */
    private $enabled;

    /**
     * Logger-Konstruktor
     * 
     * @param array $config Logging-Konfiguration
     */
    public function __construct(array $config) {
        $this->logFile = $config['path'] ?? __DIR__ . '/../../logs/api.log';
        $this->logLevel = self::$levelMap[$config['level'] ?? 'INFO'] ?? self::INFO;
        $this->enabled = $config['enabled'] ?? true;

        // Stelle sicher, dass das Log-Verzeichnis existiert
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Debug-Nachricht loggen
     * 
     * @param string $message Nachricht
     * @param array $context Kontext-Daten
     * @return void
     */
    public function debug(string $message, array $context = []): void {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Info-Nachricht loggen
     * 
     * @param string $message Nachricht
     * @param array $context Kontext-Daten
     * @return void
     */
    public function info(string $message, array $context = []): void {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Warnungs-Nachricht loggen
     * 
     * @param string $message Nachricht
     * @param array $context Kontext-Daten
     * @return void
     */
    public function warning(string $message, array $context = []): void {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Fehler-Nachricht loggen
     * 
     * @param string $message Nachricht
     * @param array $context Kontext-Daten
     * @return void
     */
    public function error(string $message, array $context = []): void {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Nachricht loggen
     * 
     * @param int $level Log-Level
     * @param string $message Nachricht
     * @param array $context Kontext-Daten
     * @return void
     */
    private function log(int $level, string $message, array $context = []): void {
        if (!$this->enabled || $level < $this->logLevel) {
            return;
        }

        $levelNames = array_flip(self::$levelMap);
        $levelName = $levelNames[$level] ?? 'UNKNOWN';
        
        $timestamp = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        
        $logMessage = "[{$timestamp}] [{$levelName}] {$message}{$contextString}" . PHP_EOL;
        
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
} 
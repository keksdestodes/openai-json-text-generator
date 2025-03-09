<?php
/**
 * OpenAI API Client
 * 
 * Diese Klasse kümmert sich um die Kommunikation mit der OpenAI API.
 * 
 * @package OpenAIJsonTransformer
 */

namespace OpenAIJsonTransformer\OpenAI;

use OpenAIJsonTransformer\Utils\Logger;

class OpenAIClient {
    /**
     * OpenAI API Endpoint
     */
    const API_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    /**
     * OpenAI API Schlüssel
     * 
     * @var string
     */
    private $apiKey;

    /**
     * OpenAI Modell
     * 
     * @var string
     */
    private $model;

    /**
     * API-Konfiguration
     * 
     * @var array
     */
    private $config;

    /**
     * Logger
     * 
     * @var Logger
     */
    private $logger;

    /**
     * Anzahl der Wiederholungsversuche
     * 
     * @var int
     */
    private $maxRetries;

    /**
     * Verzögerung zwischen Wiederholungsversuchen (in Sekunden)
     * 
     * @var int
     */
    private $retryDelay;

    /**
     * OpenAI Client Konstruktor
     * 
     * @param array $config API-Konfiguration
     * @param Logger $logger Logger-Instanz
     */
    public function __construct(array $config, Logger $logger) {
        $this->apiKey = $config['key'] ?? '';
        $this->model = $config['model'] ?? 'gpt-4o-mini';
        $this->config = $config;
        $this->logger = $logger;
        $this->maxRetries = $config['max_retries'] ?? 5;
        $this->retryDelay = $config['retry_delay'] ?? 1;
    }

    /**
     * Sendet eine Anfrage an die OpenAI API
     * 
     * @param array $messages Die Nachrichten (Konversation) für die API
     * @param array|float $options Zusätzliche Optionen für die API oder nur die Temperatur als Float
     * @return array|null Die API-Antwort als Array oder null bei Fehler
     * @throws \Exception wenn die API-Anfrage nach allen Wiederholungsversuchen fehlschlägt
     */
    public function sendRequest(array $messages, $options = []): ?array {
        // Behandlung von $options als Float (Temperatur)
        if (is_float($options) || is_int($options)) {
            $options = ['temperature' => (float)$options];
        }

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? $this->config['temperature'] ?? 0.7,
            'response_format' => ['type' => 'json_object'],
        ];

        // Optionale Parameter hinzufügen, falls sie in der Konfiguration oder den Optionen gesetzt sind
        $optionalParams = [
            'presence_penalty', 'frequency_penalty', 'top_p', 'seed'
        ];

        foreach ($optionalParams as $param) {
            if (isset($options[$param])) {
                $payload[$param] = $options[$param];
            } elseif (isset($this->config[$param])) {
                $payload[$param] = $this->config[$param];
            }
        }

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];

        $retryCount = 0;
        $lastException = null;

        // Anfrage mit Wiederholungsversuchen senden
        while ($retryCount <= $this->maxRetries) {
            try {
                $this->logger->debug('Sende Anfrage an OpenAI API', [
                    'model' => $this->model,
                    'messagesCount' => count($messages),
                    'attempt' => $retryCount + 1
                ]);

                $response = $this->executeRequest($payload, $headers);
                
                $this->logger->debug('Antwort von OpenAI API erhalten', [
                    'success' => true
                ]);
                
                return $response;
            } catch (\Exception $e) {
                $lastException = $e;
                $retryCount++;
                
                $this->logger->warning('Fehler bei OpenAI API-Anfrage', [
                    'error' => $e->getMessage(),
                    'attempt' => $retryCount,
                    'maxRetries' => $this->maxRetries
                ]);

                // Wenn maximale Wiederholungsversuche erreicht, Fehler werfen
                if ($retryCount > $this->maxRetries) {
                    $this->logger->error('Maximale Anzahl an Wiederholungsversuchen erreicht', [
                        'error' => $e->getMessage()
                    ]);
                    
                    throw new \Exception('OpenAI API-Anfrage fehlgeschlagen nach ' . $this->maxRetries . ' Versuchen: ' . $e->getMessage());
                }

                // Warten vor dem nächsten Versuch
                sleep($this->retryDelay);
            }
        }

        return null;
    }

    /**
     * Führt die eigentliche HTTP-Anfrage aus
     * 
     * @param array $payload Die Anfrage-Daten
     * @param array $headers Die HTTP-Header
     * @return array Die API-Antwort als Array
     * @throws \Exception wenn die Anfrage fehlschlägt
     */
    private function executeRequest(array $payload, array $headers): array {
        $ch = curl_init(self::API_ENDPOINT);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $responseJson = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        curl_close($ch);

        if ($curlError) {
            throw new \Exception('CURL-Fehler: ' . $curlError);
        }

        if ($httpCode >= 400) {
            $errorData = json_decode($responseJson, true);
            $errorMessage = $errorData['error']['message'] ?? 'Unbekannter API-Fehler';
            throw new \Exception('API-Fehler (HTTP ' . $httpCode . '): ' . $errorMessage);
        }

        $response = json_decode($responseJson, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Ungültige JSON-Antwort von der API: ' . json_last_error_msg());
        }

        return $response;
    }

    /**
     * Extrahiert den Text aus der OpenAI-Antwort
     * 
     * @param array $response Die API-Antwort
     * @return string|null Der extrahierte Text oder null
     */
    public function extractText(array $response): ?string {
        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }
        
        return null;
    }

    /**
     * Extrahiert und dekodiert das JSON aus der OpenAI-Antwort
     * 
     * @param array $response Die API-Antwort
     * @return array|null Das dekodierte JSON oder null
     */
    public function extractJson(array $response): ?array {
        $text = $this->extractText($response);
        
        if ($text === null) {
            return null;
        }
        
        // Versuche, JSON zu dekodieren
        $json = json_decode($text, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->warning('Fehler beim Dekodieren des JSON', [
                'error' => json_last_error_msg(),
                'content' => $text
            ]);
            
            return null;
        }
        
        return $json;
    }

    /**
     * Alias für extractJson - extrahiert den JSON-Inhalt aus der API-Antwort
     * 
     * @param array $response Die API-Antwort
     * @return array|null Das extrahierte JSON oder null bei Fehler
     */
    public function extractJsonContent(array $response): ?array {
        return $this->extractJson($response);
    }
} 
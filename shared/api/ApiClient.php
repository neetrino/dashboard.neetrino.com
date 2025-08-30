<?php
/**
 * Общий API клиент для связи между Dashboard и Plugin
 * 
 * @package Neetrino\Shared\Api
 * @version 1.0.0
 */

namespace Neetrino\Shared\Api;

class ApiClient
{
    private $baseUrl;
    private $secretKey;
    private $timeout = 30;
    
    public function __construct($baseUrl, $secretKey)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->secretKey = $secretKey;
    }
    
    /**
     * Отправляет запрос к API
     */
    public function request($endpoint, $method = 'GET', $data = [], $headers = [])
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        // Добавляем стандартные заголовки
        $headers['Content-Type'] = 'application/json';
        $headers['X-Request-ID'] = $this->generateRequestId();
        $headers['X-Timestamp'] = time();
        
        // Добавляем подпись
        $signature = $this->generateSignature($method, $endpoint, $data, $headers['X-Timestamp']);
        $headers['X-Signature'] = $signature;
        
        // Подготавливаем данные
        $jsonData = !empty($data) ? json_encode($data) : '';
        
        // Выполняем запрос
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => $this->formatHeaders($headers),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception("cURL Error: " . $error);
        }
        
        return [
            'status' => $httpCode,
            'body' => $response,
            'headers' => $headers
        ];
    }
    
    /**
     * Генерирует подпись для запроса
     */
    private function generateSignature($method, $endpoint, $data, $timestamp)
    {
        $payload = $method . '|' . $endpoint . '|' . $timestamp . '|' . json_encode($data);
        return hash_hmac('sha256', $payload, $this->secretKey);
    }
    
    /**
     * Генерирует уникальный ID запроса
     */
    private function generateRequestId()
    {
        return uniqid('req_', true);
    }
    
    /**
     * Форматирует заголовки для cURL
     */
    private function formatHeaders($headers)
    {
        $formatted = [];
        foreach ($headers as $key => $value) {
            $formatted[] = $key . ': ' . $value;
        }
        return $formatted;
    }
    
    /**
     * GET запрос
     */
    public function get($endpoint, $params = [])
    {
        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }
        return $this->request($endpoint, 'GET');
    }
    
    /**
     * POST запрос
     */
    public function post($endpoint, $data = [])
    {
        return $this->request($endpoint, 'POST', $data);
    }
    
    /**
     * PUT запрос
     */
    public function put($endpoint, $data = [])
    {
        return $this->request($endpoint, 'PUT', $data);
    }
    
    /**
     * DELETE запрос
     */
    public function delete($endpoint)
    {
        return $this->request($endpoint, 'DELETE');
    }
}

<?php
/**
 * Общий менеджер безопасности для Dashboard и Plugin
 * 
 * @package Neetrino\Shared\Security
 * @version 1.0.0
 */

namespace Neetrino\Shared\Security;

class SecurityManager
{
    private $secretKey;
    private $nonceTtl = 600; // 10 минут
    
    public function __construct($secretKey)
    {
        $this->secretKey = $secretKey;
    }
    
    /**
     * Генерирует подпись для данных
     */
    public function generateSignature($data, $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }
        
        $payload = $timestamp . '|' . json_encode($data);
        return hash_hmac('sha256', $payload, $this->secretKey);
    }
    
    /**
     * Проверяет подпись
     */
    public function verifySignature($data, $signature, $timestamp)
    {
        $expectedSignature = $this->generateSignature($data, $timestamp);
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Проверяет timestamp (защита от replay атак)
     */
    public function verifyTimestamp($timestamp, $tolerance = 300)
    {
        $currentTime = time();
        $diff = abs($currentTime - $timestamp);
        return $diff <= $tolerance;
    }
    
    /**
     * Генерирует nonce (number used once)
     */
    public function generateNonce()
    {
        return bin2hex(random_bytes(16));
    }
    
    /**
     * Проверяет nonce
     */
    public function verifyNonce($nonce, $siteId = null)
    {
        // В реальной реализации здесь должна быть проверка в БД
        // что nonce не использовался ранее
        return true;
    }
    
    /**
     * Полная валидация запроса
     */
    public function validateRequest($data, $signature, $timestamp, $nonce = null, $siteId = null)
    {
        // Проверяем timestamp
        if (!$this->verifyTimestamp($timestamp)) {
            return [
                'valid' => false,
                'error' => 'Invalid timestamp',
                'code' => 401
            ];
        }
        
        // Проверяем nonce если передан
        if ($nonce && !$this->verifyNonce($nonce, $siteId)) {
            return [
                'valid' => false,
                'error' => 'Invalid nonce',
                'code' => 401
            ];
        }
        
        // Проверяем подпись
        if (!$this->verifySignature($data, $signature, $timestamp)) {
            return [
                'valid' => false,
                'error' => 'Invalid signature',
                'code' => 401
            ];
        }
        
        return [
            'valid' => true,
            'error' => null,
            'code' => 200
        ];
    }
    
    /**
     * Генерирует JWT токен
     */
    public function generateJwt($payload, $expiration = 3600)
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiration;
        
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', 
            $headerEncoded . '.' . $payloadEncoded, 
            $this->secretKey, 
            true
        );
        
        $signatureEncoded = $this->base64UrlEncode($signature);
        
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }
    
    /**
     * Проверяет JWT токен
     */
    public function verifyJwt($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        $signature = $this->base64UrlDecode($signatureEncoded);
        $expectedSignature = hash_hmac('sha256', 
            $headerEncoded . '.' . $payloadEncoded, 
            $this->secretKey, 
            true
        );
        
        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }
        
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
        
        if ($payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Base64 URL encode
     */
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }
    
    /**
     * Генерирует безопасный пароль
     */
    public function generateSecurePassword($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
    
    /**
     * Хеширует пароль
     */
    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
    
    /**
     * Проверяет пароль
     */
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
}

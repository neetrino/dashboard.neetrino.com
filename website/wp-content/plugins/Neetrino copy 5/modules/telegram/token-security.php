<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Система безопасного шифрования токенов для Telegram модуля
 * Шифрует и дешифрует токены в базе данных
 */

class TelegramTokenSecurity {
    
    private static $encryption_key = null;
    
    /**
     * Получение ключа шифрования из настроек WordPress
     */
    private static function get_encryption_key() {
        if (self::$encryption_key === null) {
            // Используем AUTH_KEY из wp-config.php или создаем уникальный ключ
            if (defined('AUTH_KEY') && AUTH_KEY !== 'put your unique phrase here') {
                self::$encryption_key = substr(AUTH_KEY, 0, 32);
            } else {
                // Создаем уникальный ключ для сайта
                $site_key = get_option('neetrino_telegram_encryption_key');
                if (!$site_key) {
                    $site_key = bin2hex(random_bytes(16));
                    update_option('neetrino_telegram_encryption_key', $site_key);
                }
                self::$encryption_key = $site_key;
            }
        }
        return self::$encryption_key;
    }
    
    /**
     * Шифрование токена
     */
    public static function encrypt_token($token) {
        if (empty($token)) {
            return '';
        }
        
        $key = self::get_encryption_key();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($token, 'AES-256-CBC', $key, 0, $iv);
        
        // Возвращаем base64 закодированный результат с IV
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Дешифрование токена
     */
    public static function decrypt_token($encrypted_token) {
        if (empty($encrypted_token)) {
            return '';
        }
        
        try {
            $key = self::get_encryption_key();
            $data = base64_decode($encrypted_token);
            
            if ($data === false || strlen($data) < 16) {
                return '';
            }
            
            $iv = substr($data, 0, 16);
            $encrypted = substr($data, 16);
            
            $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
            
            return $decrypted !== false ? $decrypted : '';
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Проверка, установлен ли токен
     */
    public static function is_token_set() {
        $encrypted_token = get_option('neetrino_telegram_bot_token_encrypted', '');
        return !empty($encrypted_token);
    }
    
    /**
     * Получение расшифрованного токена для использования
     */
    public static function get_decrypted_token() {
        $encrypted_token = get_option('neetrino_telegram_bot_token_encrypted', '');
        return self::decrypt_token($encrypted_token);
    }
    
    /**
     * Сохранение зашифрованного токена
     */
    public static function save_encrypted_token($token) {
        if (empty($token)) {
            delete_option('neetrino_telegram_bot_token_encrypted');
            return true;
        }
        
        $encrypted_token = self::encrypt_token($token);
        return update_option('neetrino_telegram_bot_token_encrypted', $encrypted_token);
    }
    
    /**
     * Удаление токена
     */
    public static function delete_token() {
        delete_option('neetrino_telegram_bot_token_encrypted');
        // Также удаляем старый незашифрованный токен, если он есть
        $settings = get_option('neetrino_telegram_settings', array());
        if (isset($settings['bot_token'])) {
            unset($settings['bot_token']);
            update_option('neetrino_telegram_settings', $settings);
        }
        return true;
    }
}
?>

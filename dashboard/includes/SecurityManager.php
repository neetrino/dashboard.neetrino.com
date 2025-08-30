<?php
/**
 * Менеджер безопасности для Dashboard
 * STAGE 2: SHA256 хеширование + Rate Limiting
 */
class SecurityManager {
    private $my_ip;
    private $pdo;
    
    // STAGE 2: Настройки Rate Limiting
    private $rate_limit_requests = 10; // запросов в минуту
    private $rate_limit_window = 60;   // окно в секундах
    private $block_duration = 300;     // блокировка на 5 минут
    
    public function __construct($pdo) {
        $this->my_ip = $this->get_dashboard_ip();
        $this->pdo = $pdo;
    }
    
    private function get_dashboard_ip() {
        // Для локального тестирования
        if (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1')) {
            return '127.0.0.1';
        }
        
        // Для продакшена - получить IP Beget
        return @file_get_contents('https://api.ipify.org') ?: '127.0.0.1';
    }
    
    public function register_plugin($site_data) {
        // STAGE 2: Генерируем API ключ и хеш
        $api_key = $this->generate_api_key();
        $api_key_hash = $this->hash_api_key($api_key);
        
        $registration_data = [
            'dashboard_ip' => $this->my_ip,
            'dashboard_domain' => 'dashboard.neetrino.com',
            'api_key' => $api_key,
            'api_key_hash' => $api_key_hash,
            'status' => 'registered',
            'security_level' => 'STAGE_2'
        ];
        
        return $registration_data;
    }
    
    private function generate_api_key() {
        return 'ntr_dash_' . bin2hex(random_bytes(16));
    }
    
    /**
     * STAGE 2: SHA256 хеширование с солью
     */
    private function hash_api_key($api_key) {
        $salt = 'neetrino_dashboard_salt_2025';
        return hash('sha256', $salt . $api_key . $salt);
    }
    
    /**
     * STAGE 2: Проверка API ключа по хешу
     */
    public function verify_api_key($provided_key, $stored_hash) {
        if (empty($provided_key) || empty($stored_hash)) {
            return false;
        }
        
        $computed_hash = $this->hash_api_key($provided_key);
        return hash_equals($stored_hash, $computed_hash);
    }
    
    /**
     * STAGE 2: Rate Limiting проверка
     */
    public function check_rate_limit($ip_address, $site_id = null) {
        try {
            // Проверяем не заблокирован ли IP
            $stmt = $this->pdo->prepare("
                SELECT * FROM rate_limits 
                WHERE ip_address = ? AND blocked_until > NOW()
            ");
            $stmt->execute([$ip_address]);
            
            if ($stmt->fetch()) {
                $this->log_security_event($site_id, $ip_address, 'rate_limit_blocked', [], false);
                return false;
            }
            
            // Проверяем количество запросов в текущем окне
            $window_start = date('Y-m-d H:i:s', time() - $this->rate_limit_window);
            
            $stmt = $this->pdo->prepare("
                SELECT request_count FROM rate_limits 
                WHERE ip_address = ? AND last_request > ?
            ");
            $stmt->execute([$ip_address, $window_start]);
            $current_requests = $stmt->fetchColumn() ?: 0;
            
            if ($current_requests >= $this->rate_limit_requests) {
                // Блокируем IP
                $block_until = date('Y-m-d H:i:s', time() + $this->block_duration);
                
                $stmt = $this->pdo->prepare("
                    INSERT OR REPLACE INTO rate_limits 
                    (ip_address, site_id, request_count, last_request, blocked_until) 
                    VALUES (?, ?, ?, NOW(), ?)
                ");
                $stmt->execute([$ip_address, $site_id, $current_requests + 1, $block_until]);
                
                $this->log_security_event($site_id, $ip_address, 'rate_limit_exceeded', [
                    'requests' => $current_requests + 1,
                    'limit' => $this->rate_limit_requests
                ], false);
                
                return false;
            }
            
            // Обновляем счетчик запросов
            $stmt = $this->pdo->prepare("
                INSERT INTO rate_limits 
                (ip_address, site_id, request_count, last_request) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                request_count = VALUES(request_count), 
                last_request = VALUES(last_request)
            ");
            $stmt->execute([$ip_address, $site_id, $current_requests + 1]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Rate limit check failed: " . $e->getMessage());
            return true; // В случае ошибки разрешаем доступ
        }
    }
    
    /**
     * STAGE 2: Расширенное логирование безопасности
     */
    public function log_security_event($site_id, $ip_address, $event_type, $event_data = [], $success = true) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO security_logs 
                (site_id, ip_address, event_type, event_data, success) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $site_id,
                $ip_address,
                $event_type,
                json_encode($event_data),
                $success ? 1 : 0
            ]);
        } catch (Exception $e) {
            error_log("Security logging failed: " . $e->getMessage());
        }
    }
}
?>

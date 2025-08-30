<?php
/**
 * Neetrino Connection Guard
 * Отвечает за проверку и принуждение к подключению дашборда
 * Защищает от обхода через клиентские методы
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Connection_Guard {
    
    const MAX_AUTO_ATTEMPTS = 10;
    const RETRY_INTERVAL = 300; // 5 минут между попытками
    
    /**
     * Проверяет подключен ли плагин к дашборду
     */
    public static function is_connected() {
        $registration_status = get_option('neetrino_registration_status');
        $api_key = get_option('neetrino_dashboard_api_key');
        
        return ($registration_status === 'registered' && !empty($api_key));
    }
    
    /**
     * Проверяет нужно ли показывать страницу принудительного подключения
     */
    public static function should_force_connection() {
        // Если уже подключены - не принуждаем
        if (self::is_connected()) {
            return false;
        }
        
        // Проверяем не превышено ли количество попыток
        $attempts = get_option('neetrino_connection_attempts', 0);
        if ($attempts >= self::MAX_AUTO_ATTEMPTS) {
            // Превышено количество попыток - показываем ручное подключение
            return true;
        }
        
        // Проверяем не слишком ли рано для новой попытки
        $last_attempt = get_option('neetrino_last_connection_attempt', 0);
        $current_time = time();
        
        if (($current_time - $last_attempt) < self::RETRY_INTERVAL) {
            // Слишком рано - показываем страницу ожидания
            return true;
        }
        
        // Время для новой попытки
        return true;
    }
    
    /**
     * Выполняет автоматическую попытку подключения
     */
    public static function attempt_auto_connection() {
        if (self::is_connected()) {
            return ['success' => true, 'message' => 'Already connected'];
        }
        
        $attempts = get_option('neetrino_connection_attempts', 0);
        if ($attempts >= self::MAX_AUTO_ATTEMPTS) {
            return ['success' => false, 'message' => 'Max attempts reached', 'manual_required' => true];
        }
        
        $last_attempt = get_option('neetrino_last_connection_attempt', 0);
        $current_time = time();
        
        if (($current_time - $last_attempt) < self::RETRY_INTERVAL) {
            $next_attempt = $last_attempt + self::RETRY_INTERVAL;
            return [
                'success' => false, 
                'message' => 'Too early for retry',
                'next_attempt' => $next_attempt,
                'wait_time' => $next_attempt - $current_time
            ];
        }
        
        // Увеличиваем счетчик попыток
        update_option('neetrino_connection_attempts', $attempts + 1);
        update_option('neetrino_last_connection_attempt', $current_time);
        
        // Пытаемся подключиться
        $result = Neetrino_Registration::register_with_dashboard();
        
        if ($result['success']) {
            // Успешно подключились - сбрасываем счетчики
            delete_option('neetrino_connection_attempts');
            delete_option('neetrino_last_connection_attempt');
            delete_option('neetrino_connection_force_manual');
            
            error_log('NEETRINO Connection Guard: Auto-connection successful after ' . ($attempts + 1) . ' attempts');
        } else {
            error_log('NEETRINO Connection Guard: Auto-connection failed, attempt ' . ($attempts + 1) . '/' . self::MAX_AUTO_ATTEMPTS);
            
            // Если достигли максимума попыток
            if (($attempts + 1) >= self::MAX_AUTO_ATTEMPTS) {
                update_option('neetrino_connection_force_manual', true);
                error_log('NEETRINO Connection Guard: Max auto-attempts reached, forcing manual connection');
            }
        }
        
        return $result;
    }
    
    /**
     * Принудительно сбрасывает статус подключения (для ручного переподключения)
     */
    public static function reset_connection_status() {
        delete_option('neetrino_connection_attempts');
        delete_option('neetrino_last_connection_attempt');
        delete_option('neetrino_connection_force_manual');
        
        // Также очищаем данные подключения
        Neetrino_Dashboard_Connect::reset_connection();
        
        error_log('NEETRINO Connection Guard: Connection status reset for manual retry');
    }
    
    /**
     * Проверяет, должны ли мы блокировать загрузку модулей
     */
    public static function should_block_modules() {
        return !self::is_connected();
    }
    
    /**
     * Проверяет, должны ли мы блокировать AJAX запросы
     */
    public static function should_block_ajax() {
        // Разрешаем только AJAX запросы связанные с подключением
        $allowed_actions = [
            'neetrino_check_connection_status',
            'neetrino_manual_connect',
            'neetrino_dashboard_reconnect'
        ];
        
        $current_action = isset($_POST['action']) ? $_POST['action'] : '';
        
        if (in_array($current_action, $allowed_actions)) {
            return false; // Не блокируем разрешенные действия
        }
        
        return !self::is_connected();
    }
    
    /**
     * Получает информацию о текущем статусе подключения для фронтенда
     */
    public static function get_status_info() {
        $connected = self::is_connected();
        $attempts = get_option('neetrino_connection_attempts', 0);
        $last_attempt = get_option('neetrino_last_connection_attempt', 0);
        $force_manual = get_option('neetrino_connection_force_manual', false);
        $current_time = time();
        
        $status = [
            'connected' => $connected,
            'attempts' => $attempts,
            'max_attempts' => self::MAX_AUTO_ATTEMPTS,
            'force_manual' => $force_manual,
            'last_attempt' => $last_attempt,
            'current_time' => $current_time
        ];
        
        if (!$connected && !$force_manual) {
            $next_attempt = $last_attempt + self::RETRY_INTERVAL;
            $status['next_attempt'] = $next_attempt;
            $status['wait_time'] = max(0, $next_attempt - $current_time);
            $status['can_retry_now'] = ($current_time >= $next_attempt);
        }
        
        return $status;
    }
    
    /**
     * Возвращает время до следующей попытки в человекочитаемом виде
     */
    public static function get_next_attempt_time_formatted() {
        $status = self::get_status_info();
        
        if ($status['connected'] || $status['force_manual']) {
            return null;
        }
        
        if ($status['wait_time'] <= 0) {
            return 'Попытка доступна сейчас';
        }
        
        $minutes = ceil($status['wait_time'] / 60);
        if ($minutes == 1) {
            return '1 минута';
        } elseif ($minutes < 5) {
            return $minutes . ' минуты';
        } else {
            return $minutes . ' минут';
        }
    }
}

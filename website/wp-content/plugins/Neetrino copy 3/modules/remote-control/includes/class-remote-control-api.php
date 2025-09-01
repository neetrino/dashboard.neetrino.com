<?php
/**
 * Remote Control API Handler
 * 
 * Обрабатывает все удаленные API запросы
 * Использует существующие настройки из базы данных
 */

if (!defined('ABSPATH')) {
    exit;
}

class Remote_Control_API {
    
    // Используем те же константы что и в оригинальном Bitrix24 модуле
    const SECRET_HASH_OPTION = 'bitrix24_remote_control_secret_hash';
    const RATE_LIMIT_OPTION = 'bitrix24_remote_control_rate_limit';
    const MAX_REQUESTS_PER_HOUR = 10;
    const ALLOWED_IPS_OPTION = 'bitrix24_remote_control_allowed_ips';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Обработчик удаленных запросов - вызываем прямо сейчас
        $this->handle_remote_requests();
        
        // AJAX обработчики
        add_action('wp_ajax_remote_control_generate_key', [$this, 'ajax_generate_key']);
        add_action('wp_ajax_remote_control_delete_key', [$this, 'ajax_delete_key']);
        add_action('wp_ajax_remote_control_clear_key_transient', [$this, 'ajax_clear_key_transient']);
        
        // Периодическая очистка старых данных
        if (mt_rand(1, 100) <= 5) {
            $this->cleanup_old_data();
        }
        
        error_log('Remote Control API: Constructor completed, remote handler called directly');
    }
    
    /**
     * Обработка удаленных запросов
     */
    public function handle_remote_requests() {
        // Логируем все GET параметры для отладки
        if (isset($_GET['remote_control'])) {
            error_log('Remote Control: Request received with params: ' . json_encode($_GET));
        }
        
        // Проверяем наличие параметра remote_control
        if (!isset($_GET['remote_control']) || !isset($_GET['key'])) {
            return;
        }
        
        // Получаем команду и ключ
        $command = sanitize_text_field($_GET['remote_control']);
        $key = sanitize_text_field($_GET['key']);
        
        error_log("Remote Control: Processing command '$command' with key '$key'");
        
        // Проверяем валидность ключа
        if (!$this->verify_key($key)) {
            error_log('Remote Control: Invalid API key provided');
            $this->send_error_response('Invalid API key', 403);
            return;
        }
        
        // Проверяем лимиты запросов
        if (!$this->check_rate_limit()) {
            error_log('Remote Control: Rate limit exceeded');
            $this->send_error_response('Rate limit exceeded', 429);
            return;
        }
        
        error_log("Remote Control: Key verified, processing command '$command'");
        
        // Обрабатываем команду
        switch ($command) {
            case 'maintenance':
                $this->handle_maintenance_command();
                break;
                
            case 'bitrix24_sync':
                $this->handle_bitrix24_sync_command();
                break;
                
            case 'status':
                $this->handle_status_command();
                break;
                
            case 'delete_plugin':
                $this->handle_delete_plugin_command();
                break;
                
            default:
                error_log("Remote Control: Unknown command '$command'");
                $this->send_error_response('Unknown command', 400);
        }
    }
    
    /**
     * Обработка команды maintenance
     */
    private function handle_maintenance_command() {
        if (!isset($_GET['mode'])) {
            error_log('Remote Control: Missing mode parameter for maintenance command');
            $this->send_error_response('Missing mode parameter', 400);
            return;
        }
        
        $mode = sanitize_text_field($_GET['mode']);
        
        error_log("Remote Control: Attempting to set maintenance mode to '$mode'");
        
        // Проверяем допустимые режимы
        if (!in_array($mode, ['open', 'maintenance', 'closed'])) {
            error_log("Remote Control: Invalid mode '$mode'");
            $this->send_error_response('Invalid mode. Allowed values: open, maintenance, closed', 400);
            return;
        }
        
        // Получаем текущие настройки
        $options = get_option('neetrino_maintenance_mode', [
            'mode' => 'open',
        ]);
        
        $old_mode = isset($options['mode']) ? $options['mode'] : 'open';
        error_log("Remote Control: Current mode: '$old_mode', changing to: '$mode'");
        
        // Обновляем режим
        $options['mode'] = $mode;
        $update_result = update_option('neetrino_maintenance_mode', $options);
        
        error_log("Remote Control: Option update result: " . ($update_result ? 'SUCCESS' : 'FAILED'));
        
        // Проверяем, что изменение применилось
        $verification = get_option('neetrino_maintenance_mode', []);
        error_log("Remote Control: Verification - current mode: " . (isset($verification['mode']) ? $verification['mode'] : 'NOT SET'));
        
        // Логируем изменение
        error_log("Remote Control: Maintenance mode changed from '$old_mode' to '$mode' by IP: " . $this->get_client_ip());
        
        // Отправляем ответ
        wp_send_json_success([
            'message' => 'Maintenance mode updated successfully',
            'mode' => $mode,
            'old_mode' => $old_mode,
            'timestamp' => current_time('mysql')
        ]);
    }
    
    /**
     * Обработка команды принудительной синхронизации с Bitrix24
     */
    private function handle_bitrix24_sync_command() {
        // Проверяем наличие модуля Bitrix24
        if (!Neetrino::is_module_active('bitrix24')) {
            $this->send_error_response('Bitrix24 module is not active', 400);
            return;
        }
        
        // Вызываем принудительную синхронизацию
        $sync_result = $this->trigger_bitrix24_sync();
        
        if ($sync_result) {
            wp_send_json_success([
                'message' => 'Bitrix24 sync triggered successfully',
                'timestamp' => current_time('mysql')
            ]);
        } else {
            $this->send_error_response('Failed to trigger Bitrix24 sync', 500);
        }
    }
    
    /**
     * Обработка команды статуса
     */
    private function handle_status_command() {
        // Собираем информацию о статусе
        $status = [
            'timestamp' => current_time('mysql'),
            'maintenance_mode' => get_option('neetrino_maintenance_mode', ['mode' => 'open']),
            'modules' => [
                'bitrix24' => Neetrino::is_module_active('bitrix24'),
                'maintenance-mode' => Neetrino::is_module_active('maintenance-mode'),
                'remote-control' => Neetrino::is_module_active('remote-control'),
            ],
            'last_bitrix24_sync' => get_option('remote_control_last_bitrix24_sync', 'never')
        ];
        
        wp_send_json_success($status);
    }
    
    /**
     * Обработка команды полного удаления плагина
     */
    private function handle_delete_plugin_command() {
        error_log('Remote Control: Processing delete plugin command');
        
        // Дополнительные проверки безопасности для деструктивной операции
        $confirm = isset($_GET['confirm']) ? sanitize_text_field($_GET['confirm']) : '';
        
        if ($confirm !== 'YES_DELETE_PLUGIN') {
            error_log('Remote Control: Delete plugin command requires confirmation');
            $this->send_error_response('Delete plugin command requires confirmation parameter: confirm=YES_DELETE_PLUGIN', 400);
            return;
        }
        
        try {
            // Логируем начало операции удаления
            error_log("Remote Control: Starting plugin deletion process. IP: " . $this->get_client_ip());
            
            // Получаем путь к плагину
            $plugin_file = 'Neetrino/neetrino.php'; // Основной файл плагина
            $plugin_path = WP_PLUGIN_DIR . '/Neetrino/';
            
            // Проверяем, что плагин существует
            if (!file_exists($plugin_path)) {
                error_log('Remote Control: Plugin directory not found: ' . $plugin_path);
                $this->send_error_response('Plugin directory not found', 404);
                return;
            }
            
            // Деактивируем плагин если он активен
            if (is_plugin_active($plugin_file)) {
                error_log('Remote Control: Deactivating plugin before deletion');
                deactivate_plugins($plugin_file);
            }
            
            // Удаляем все опции плагина из базы данных
            error_log('Remote Control: Cleaning up plugin options from database');
            $this->cleanup_plugin_options();
            
            // Удаляем все файлы плагина
            error_log('Remote Control: Deleting plugin files');
            $this->recursive_delete($plugin_path);
            
            // Очищаем кеш
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }
            
            error_log('Remote Control: Plugin deletion completed successfully');
            
            // Отправляем успешный ответ
            wp_send_json_success([
                'message' => 'Plugin deleted successfully',
                'deleted_files' => true,
                'cleaned_database' => true,
                'timestamp' => current_time('mysql')
            ]);
            
        } catch (Exception $e) {
            error_log('Remote Control: Error during plugin deletion: ' . $e->getMessage());
            $this->send_error_response('Plugin deletion failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Очистка опций плагина из базы данных
     */
    private function cleanup_plugin_options() {
        global $wpdb;
        
        // Список префиксов опций для удаления
        $option_prefixes = [
            'neetrino_',
            'bitrix24_',
            'remote_control_'
        ];
        
        foreach ($option_prefixes as $prefix) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $prefix . '%'
            ));
        }
        
        // Удаляем конкретные опции
        $specific_options = [
            'neetrino_active_modules',
            'neetrino_maintenance_mode',
            'neetrino_version'
        ];
        
        foreach ($specific_options as $option) {
            delete_option($option);
        }
        
        error_log('Remote Control: Database cleanup completed');
    }
    
    /**
     * Рекурсивное удаление директории
     */
    private function recursive_delete($directory) {
        if (!is_dir($directory)) {
            return;
        }
        
        $files = array_diff(scandir($directory), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $directory . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($path)) {
                $this->recursive_delete($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($directory);
    }
    
    /**
     * Проверка API ключа
     */
    private function verify_key($input_key) {
        $stored_key_data = get_option(self::SECRET_HASH_OPTION, '');
        
        error_log("Remote Control: Verifying key. Stored data exists: " . (!empty($stored_key_data) ? 'YES' : 'NO'));
        
        if (empty($stored_key_data)) {
            error_log('Remote Control: No stored API key found');
            return false;
        }
        
        $key_data = json_decode($stored_key_data, true);
        if (!$key_data || !isset($key_data['hash']) || !isset($key_data['salt'])) {
            error_log('Remote Control: Invalid key data structure');
            return false;
        }
        
        // Сначала проверяем новый формат (hash с конкатенацией)
        $new_format_hash = hash('sha256', $input_key . $key_data['salt']);
        error_log("Remote Control: NEW format test: hash('sha256', key + salt) = $new_format_hash");
        
        if (hash_equals($key_data['hash'], $new_format_hash)) {
            error_log("Remote Control: SUCCESS - NEW format matched!");
            return true;
        }
        
        // Проверяем старый формат (hash_hmac) для обратной совместимости  
        $old_format_hash = hash_hmac('sha256', $input_key, $key_data['salt']);
        error_log("Remote Control: OLD format test: hash_hmac('sha256', key, salt) = $old_format_hash");
        
        if (hash_equals($key_data['hash'], $old_format_hash)) {
            error_log("Remote Control: SUCCESS - OLD format matched! (Legacy compatibility)");
            return true;
        }

        error_log("Remote Control: FAILED - No format matched");
        return false;
    }
    
    /**
     * Проверка лимитов запросов
     */
    private function check_rate_limit() {
        $client_ip = $this->get_client_ip();
        $rate_limit_data = get_option(self::RATE_LIMIT_OPTION, []);
        
        $current_hour = date('Y-m-d H');
        
        if (!isset($rate_limit_data[$client_ip])) {
            $rate_limit_data[$client_ip] = [];
        }
        
        if (!isset($rate_limit_data[$client_ip][$current_hour])) {
            $rate_limit_data[$client_ip][$current_hour] = 0;
        }
        
        if ($rate_limit_data[$client_ip][$current_hour] >= self::MAX_REQUESTS_PER_HOUR) {
            return false;
        }
        
        // Увеличиваем счетчик
        $rate_limit_data[$client_ip][$current_hour]++;
        
        // Очищаем старые данные
        foreach ($rate_limit_data as $ip => $hours) {
            foreach ($hours as $hour => $count) {
                if ($hour < date('Y-m-d H', strtotime('-2 hours'))) {
                    unset($rate_limit_data[$ip][$hour]);
                }
            }
            if (empty($rate_limit_data[$ip])) {
                unset($rate_limit_data[$ip]);
            }
        }
        
        update_option(self::RATE_LIMIT_OPTION, $rate_limit_data);
        return true;
    }
    
    /**
     * Получение IP клиента
     */
    private function get_client_ip() {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Отправка ошибки
     */
    private function send_error_response($message, $code = 400) {
        status_header($code);
        wp_send_json_error([
            'message' => $message,
            'code' => $code
        ], $code);
    }
    
    /**
     * Генерация нового API ключа
     */
    public function generate_secure_api_key($key = '') {
        if (empty($key)) {
            $key = bin2hex(random_bytes(32));
            $key = 'ntr_v2_' . $key;
        }
        
        // Генерируем соль
        $salt = bin2hex(random_bytes(16));
        
        // Создаем хеш
        $hash = hash('sha256', $key . $salt);
        
        // Сохраняем в базе данных
        $key_data = [
            'hash' => $hash,
            'salt' => $salt,
            'created' => time()
        ];
        
        update_option(self::SECRET_HASH_OPTION, json_encode($key_data));
        
        return $key;
    }
    
    /**
     * Проверка существования ключа
     */
    public function key_exists() {
        $stored_key_data = get_option(self::SECRET_HASH_OPTION, '');
        return !empty($stored_key_data);
    }
    
    /**
     * Удаление ключа
     */
    public function delete_key() {
        delete_option(self::SECRET_HASH_OPTION);
    }
    
    /**
     * Синхронизация с Bitrix24 с проверкой месяца
     */
    private function trigger_bitrix24_sync() {
        // Получаем глобальный экземпляр Bitrix24
        global $neetrino_bitrix24_instance;
        
        if ($neetrino_bitrix24_instance && method_exists($neetrino_bitrix24_instance, 'trigger_send')) {
            // Вызываем отправку с проверкой месяца (НЕ принудительно)
            return $neetrino_bitrix24_instance->trigger_send(false);
        }
        
        return false;
    }
    
    /**
     * Очистка старых данных
     */
    private function cleanup_old_data() {
        // Очищаем старые данные лимитов
        $rate_limit_data = get_option(self::RATE_LIMIT_OPTION, []);
        $cutoff_time = date('Y-m-d H', strtotime('-24 hours'));
        
        foreach ($rate_limit_data as $ip => $hours) {
            foreach ($hours as $hour => $count) {
                if ($hour < $cutoff_time) {
                    unset($rate_limit_data[$ip][$hour]);
                }
            }
            if (empty($rate_limit_data[$ip])) {
                unset($rate_limit_data[$ip]);
            }
        }
        
        update_option(self::RATE_LIMIT_OPTION, $rate_limit_data);
    }
    
    /**
     * AJAX: Генерация нового ключа
     */
    public function ajax_generate_key() {
        check_ajax_referer('remote_control_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Недостаточно прав');
        }
        
        $new_key = $this->generate_secure_api_key();
        
        // Сохраняем ключ для одноразового отображения
        set_transient('remote_control_new_key', $new_key, 60);
        
        wp_send_json_success([
            'message' => 'Новый API ключ создан',
            'key' => $new_key
        ]);
    }
    
    /**
     * AJAX: Удаление ключа
     */
    public function ajax_delete_key() {
        check_ajax_referer('remote_control_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Недостаточно прав');
        }
        
        $this->delete_key();
        
        wp_send_json_success([
            'message' => 'API ключ удален'
        ]);
    }
    
    /**
     * AJAX: Очистка временного ключа
     */
    public function ajax_clear_key_transient() {
        check_ajax_referer('remote_control_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Недостаточно прав');
        }
        
        delete_transient('remote_control_new_key');
        
        wp_send_json_success([
            'message' => 'Временный ключ очищен'
        ]);
    }
}

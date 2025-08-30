<?php
/**
 * Подключение к Dashboard для плагина Neetrino
 * Этап 1: Простое подключение без безопасности
 */

class Neetrino_Dashboard_Connect {
    
    // URL dashboard (реальный сервер)
    const DASHBOARD_URL = 'http://dashboard.local/';
    
    /**
     * Инициализация
     */
    public static function init() {
        // Добавляем кастомный интервал 1 день
        add_filter('cron_schedules', [__CLASS__, 'add_cron_intervals']);
        
        // Отправка статуса раз в день
        add_action('wp', [__CLASS__, 'schedule_ping']);
        add_action('neetrino_dashboard_ping', [__CLASS__, 'send_ping']);
        
        // Проверка подключения в админке для неподключенных сайтов
        add_action('admin_init', [__CLASS__, 'maybe_register']);
        
        // Логируем инициализацию
        error_log('Neetrino Dashboard Connect initialized');
    }
    
    /**
     * Добавляем кастомные интервалы cron
     */
    public static function add_cron_intervals($schedules) {
        $schedules['neetrino_daily'] = array(
            'interval' => 24 * 60 * 60, // 24 часа = 86400 секунд
            'display' => __('Once Daily')
        );
        return $schedules;
    }
    
    /**
     * Проверяем нужна ли регистрация (вызывается только в админке)
     */
    public static function maybe_register() {
        // Только в админке и только для администраторов
        if (!is_admin() || !current_user_can('administrator')) {
            return;
        }
        
        // Проверяем новую систему регистрации
        $registration_status = get_option('neetrino_registration_status');
        $dashboard_api_key = get_option('neetrino_dashboard_api_key');
        
        // Если уже зарегистрирован - ничего не делаем
        if ($registration_status === 'registered' && !empty($dashboard_api_key)) {
            return;
        }
        
        // Используем новую систему Connection_Guard для автоматических попыток
        if (class_exists('Neetrino_Connection_Guard')) {
            $status = Neetrino_Connection_Guard::get_status_info();
            
            // Если можем попробовать подключиться автоматически
            if (!$status['force_manual'] && $status['can_retry_now']) {
                Neetrino_Connection_Guard::attempt_auto_connection();
            }
            return;
        }
        
        // Fallback для старой системы (если Connection_Guard недоступен)
        $last_attempt = get_option('neetrino_last_registration_attempt');
        $current_time = time();
        
        // Если последняя попытка была менее 1 часа назад - не пытаемся снова  
        if ($last_attempt && ($current_time - $last_attempt) < 3600) {
            return;
        }
        
        // Сохраняем время попытки ПЕРЕД регистрацией
        update_option('neetrino_last_registration_attempt', $current_time);
        
        error_log('Neetrino: Site not registered in new system, starting registration (admin_init)');
        Neetrino_Registration::register_with_dashboard();
    }
    
    /**
     * Проверка статуса на дашборде (раз в день)
     */
    public static function verify_dashboard_status() {
        $api_key = get_option('neetrino_dashboard_api_key');
        if (empty($api_key)) {
            error_log('Neetrino: No API key for dashboard verification');
            return false;
        }
        
        $data = [
            'action' => 'verify_site',
            'site_url' => home_url(),
            'api_key' => $api_key
        ];
        
        $response = wp_remote_post(self::DASHBOARD_URL . '/api.php', [
            'body' => $data,
            'timeout' => 15,
            'headers' => [
                'User-Agent' => 'Neetrino-Plugin/3.0'
            ]
        ]);
        
        if (is_wp_error($response)) {
            error_log('Neetrino: Dashboard verification failed - ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if ($result && isset($result['status'])) {
            if ($result['status'] === 'not_found') {
                // Сайт не найден на дашборде - сбрасываем локальный статус
                error_log('Neetrino: Site not found on dashboard, resetting local status');
                delete_option('neetrino_registration_status');
                delete_option('neetrino_dashboard_api_key');
                delete_option('neetrino_dashboard_ip');
                delete_option('neetrino_dashboard_domain');
                
                // Запускаем перерегистрацию
                Neetrino_Registration::register_with_dashboard();
                return false;
            } elseif ($result['status'] === 'verified') {
                error_log('Neetrino: Dashboard status verified successfully');
                return true;
            }
        }
        
        error_log('Neetrino: Dashboard verification - unexpected response');
        return false;
    }
    
    /**
     * Планируем отправку статуса
     */
    public static function schedule_ping() {
        if (!wp_next_scheduled('neetrino_dashboard_ping')) {
            // Проверка раз в день вместо каждые 10 секунд
            wp_schedule_event(time(), 'neetrino_daily', 'neetrino_dashboard_ping');
            error_log('Neetrino: Scheduled daily ping to dashboard');
        }
    }
    
    /**
     * Ежедневная проверка статуса на dashboard
     */
    public static function send_ping() {
        error_log('Neetrino: Starting daily dashboard verification');
        
        // Сначала проверяем статус регистрации
        self::maybe_register();
        
        // Затем проверяем статус на дашборде
        self::verify_dashboard_status();
    }
    
    /**
     * Принудительная отправка ping (для тестирования)
     */
    public static function force_ping() {
        self::send_ping();
    }
    
    /**
     * Получение информации о подключении (только новая система)
     */
    public static function get_connection_info() {
        $registration_status = get_option('neetrino_registration_status');
        $dashboard_ip = get_option('neetrino_dashboard_ip');
        $api_key = get_option('neetrino_dashboard_api_key');
        $site_id = get_option('neetrino_site_id', '');
        
        return [
            'registered' => $registration_status === 'registered',
            'site_id' => $site_id,
            'dashboard_url' => self::DASHBOARD_URL,
            'dashboard_ip' => $dashboard_ip,
            'api_key_set' => !empty($api_key),
            'security_version' => '2.0',
            'next_ping' => wp_next_scheduled('neetrino_dashboard_ping') ? 
                date('Y-m-d H:i:s', wp_next_scheduled('neetrino_dashboard_ping')) : 
                'Not scheduled'
        ];
    }
    
    /**
     * Сброс подключения (только новая система)
     */
    public static function reset_connection() {
        // Удаляем данные новой системы
        delete_option('neetrino_registration_status');
        delete_option('neetrino_dashboard_api_key');
        delete_option('neetrino_dashboard_ip');
        delete_option('neetrino_dashboard_domain');
        delete_option('neetrino_temp_registration_key');
        delete_option('neetrino_last_registration_attempt'); // Очищаем флаг попыток
        
        // Удаляем старые данные (для полной очистки)
        delete_option('neetrino_dashboard_registered');
        delete_option('neetrino_site_id');
        delete_option('neetrino_dashboard_url');
        
        wp_clear_scheduled_hook('neetrino_dashboard_ping');
        
        error_log('Neetrino: Dashboard connection reset - all registration data cleared');
    }
    
    /**
     * Обработка команд от dashboard
     */
    private static function process_commands($commands) {
        foreach ($commands as $command) {
            if (!isset($command['id']) || !isset($command['command_type'])) {
                continue;
            }
            
            error_log('Neetrino: Processing command: ' . $command['command_type'] . ' (ID: ' . $command['id'] . ')');
            
            $result = self::execute_command($command);
            
            // Отправляем результат обратно на dashboard
            $response_data = [
                'action' => 'command_result',
                'command_id' => $command['id'],
                'site_url' => home_url(),
                'result' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ];
            
            wp_remote_post(self::DASHBOARD_URL . '/api.php', [
                'body' => $response_data,
                'timeout' => 15
            ]);
            
            error_log('Neetrino: Command ' . $command['id'] . ' result: ' . ($result['success'] ? 'SUCCESS' : 'ERROR') . ' - ' . $result['message']);
        }
    }
    
    /**
     * Выполнение конкретной команды
     */
    private static function execute_command($command) {
        try {
            switch ($command['command_type']) {
                case 'delete_plugin':
                    return self::delete_plugin();
                    
                case 'toggle_module':
                    $data = json_decode($command['command_data'], true);
                    $module = isset($data['module']) ? $data['module'] : '';
                    $enable = isset($data['enable']) ? (bool)$data['enable'] : false;
                    return self::toggle_module($module, $enable);
                    
                case 'get_status':
                    return [
                        'success' => true,
                        'message' => 'Status retrieved',
                        'data' => self::get_site_status()
                    ];
                    
                default:
                    return [
                        'success' => false,
                        'message' => 'Unknown command: ' . $command['command_type']
                    ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Command execution failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Удаление плагина
     */
    private static function delete_plugin() {
        if (!current_user_can('activate_plugins')) {
            return [
                'success' => false,
                'message' => 'Insufficient permissions'
            ];
        }
        
        try {
            // ВАЖНО: Очищаем статус регистрации на dashboard
            // чтобы при следующей установке плагин регистрировался заново
            delete_option('neetrino_dashboard_registered');
            delete_option('neetrino_site_id');
            delete_option('neetrino_dashboard_url');
            wp_clear_scheduled_hook('neetrino_dashboard_ping');
            
            error_log('Neetrino: Dashboard registration cleared due to delete command');
            
            // Деактивируем плагин
            $plugin_file = plugin_basename(__FILE__);
            deactivate_plugins($plugin_file);
            
            return [
                'success' => true,
                'message' => 'Plugin deactivated and dashboard registration cleared'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Plugin deactivation failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Включение/отключение модуля
     */
    private static function toggle_module($module_name, $enable) {
        if (empty($module_name)) {
            return [
                'success' => false,
                'message' => 'Module name not specified'
            ];
        }
        
        try {
            // Получаем текущие активные модули
            $active_modules = get_option('neetrino_active_modules', []);
            
            if ($enable) {
                if (!in_array($module_name, $active_modules)) {
                    $active_modules[] = $module_name;
                }
            } else {
                $active_modules = array_diff($active_modules, [$module_name]);
            }
            
            // Сохраняем изменения
            update_option('neetrino_active_modules', $active_modules);
            
            return [
                'success' => true,
                'message' => 'Module ' . $module_name . ' ' . ($enable ? 'enabled' : 'disabled')
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Module toggle failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение статуса сайта
     */
    private static function get_site_status() {
        return [
            'site_url' => home_url(),
            'site_name' => get_bloginfo('name'),
            'wp_version' => get_bloginfo('version'),
            'plugin_version' => '2.10',
            'active_modules' => get_option('neetrino_active_modules', []),
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit')
        ];
    }
}
?>

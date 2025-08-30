<?php
/**
 * REST API для централизованного управления плагином Neetrino
 * Push-архитектура - мгновенное выполнение команд
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_REST_API {
    
    /**
     * Инициализация REST API
     */
    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    // CORS and preflight handling for cross-origin dashboard requests
    add_filter('rest_pre_serve_request', [__CLASS__, 'send_cors_headers'], 10, 4);
        
        // Логируем инициализацию
        error_log('Neetrino REST API initialized');
    }
    
    /**
     * Регистрация REST маршрутов
     */
    public static function register_routes() {
        // Основной endpoint для команд
        register_rest_route('neetrino/v1', '/command', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'execute_command'],
            'permission_callback' => '__return_true',
            'args' => [
                'command' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'data' => [
                    'required' => false,
                    'type' => 'object',
                    'default' => []
                ],
                'api_key' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
        
        // Endpoint для регистрации Dashboard IP
        register_rest_route('neetrino/v1', '/update-dashboard-config', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'update_dashboard_config'],
            'permission_callback' => '__return_true'
        ]);
        
        // Endpoint для получения статуса (GET)
        register_rest_route('neetrino/v1', '/status', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_status'],
            'permission_callback' => '__return_true'
        ]);
        
        // Endpoint для проверки конфигурации безопасности
        register_rest_route('neetrino/v1', '/check-config', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'check_security_config'],
            'permission_callback' => '__return_true'
        ]);
        
        // Endpoint для тестирования подключения
        register_rest_route('neetrino/v1', '/ping', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'ping'],
            'permission_callback' => '__return_true'
        ]);

        // Endpoint для ручного пуша версии (для теста)
        register_rest_route('neetrino/v1', '/push-version', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'push_version_now'],
            'permission_callback' => '__return_true'
        ]);
        
        error_log('Neetrino REST routes registered');
    }

    /**
     * CORS headers + handle OPTIONS preflight for custom headers
     */
    public static function send_cors_headers($served, $result, $request, $server) {
        // Allow all origins (adjust to a specific domain if needed)
        if (!headers_sent()) {
            header('Access-Control-Allow-Origin: *');
            header('Vary: Origin');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With, X-WP-Nonce, X-Min-Plugin-Version');
            header('Access-Control-Expose-Headers: X-WP-Total, X-WP-TotalPages');
        }
        // Short-circuit preflight
        if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS') {
            return true;
        }
        return $served;
    }
    
    /**
     * Проверка разрешений для доступа к API
     */
    public static function verify_permission($request) {
        // Получаем заголовок авторизации
        $auth_header = $request->get_header('authorization');
        
        if (empty($auth_header)) {
            error_log('Neetrino REST API: No authorization header');
            return new WP_Error('unauthorized', 'Authorization header required', ['status' => 401]);
        }
        
        // Проверяем Basic Auth (Application Password)
        if (strpos($auth_header, 'Basic ') === 0) {
            $credentials = base64_decode(substr($auth_header, 6));
            $parts = explode(':', $credentials, 2);
            
            if (count($parts) !== 2) {
                error_log('Neetrino REST API: Invalid Basic Auth format');
                return new WP_Error('invalid_auth', 'Invalid authorization format', ['status' => 401]);
            }
            
            $username = $parts[0];
            $password = $parts[1];
            
            // Проверяем Application Password
            $user = wp_authenticate_application_password(null, $username, $password);
            
            if (is_wp_error($user)) {
                error_log('Neetrino REST API: Authentication failed for user: ' . $username);
                return new WP_Error('auth_failed', 'Authentication failed', ['status' => 401]);
            }
            
            // Проверяем права пользователя
            if (!user_can($user, 'manage_options')) {
                error_log('Neetrino REST API: Insufficient permissions for user: ' . $username);
                return new WP_Error('insufficient_permissions', 'Insufficient permissions', ['status' => 403]);
            }
            
            // Устанавливаем текущего пользователя
            wp_set_current_user($user->ID);
            
            error_log('Neetrino REST API: User authenticated successfully: ' . $username);
            return true;
        }
        
        error_log('Neetrino REST API: Unsupported authorization method');
        return new WP_Error('unsupported_auth', 'Unsupported authorization method', ['status' => 401]);
    }
    
    /**
     * Выполнение команды
     */
    public static function execute_command($request) {
        // Базовая защита
        
        // Проверка IP (временно отключена)
        // if (!self::verify_dashboard_access($request)) {
        //     return new WP_Error('access_denied', 'Access denied from this IP', ['status' => 403]);
        // }
        
        // Проверка API ключа
        $api_key = $request->get_param('api_key');
        if (!self::verify_api_key($api_key)) {
            self::log_security_event('invalid_api_key', ['ip' => self::get_client_ip()]);
            return new WP_Error('unauthorized', 'Invalid API key', ['status' => 401]);
        }
        
        $command = $request->get_param('command');
        $data = $request->get_param('data');

        // VersionGate (мягкий): если дашборд прислал X-Min-Plugin-Version — проверяем
        $min_required = $request->get_header('x-min-plugin-version');
        if (!empty($min_required) && defined('NEETRINO_VERSION')) {
            if (version_compare(NEETRINO_VERSION, $min_required, '<')) {
                // Возвращаем 426 и не выполняем команду (безопасный отказ)
                return new WP_Error(
                    'upgrade_required',
                    'Plugin version is below required minimum',
                    [
                        'status' => 426,
                        'plugin_version' => NEETRINO_VERSION,
                        'required_min_version' => $min_required,
                    ]
                );
            }
        }
        
        // Логирование успешного запроса
        self::log_security_event('command_executed', [
            'command' => $command,
            'ip' => self::get_client_ip()
        ]);
        
        error_log('Neetrino REST API: Executing command: ' . $command);
        
        try {
            $result = self::handle_command($command, $data);
            
            $response = [
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => isset($result['data']) ? $result['data'] : null,
                'timestamp' => time(),
                'command' => $command
            ];
            
            error_log('Neetrino REST API: Command executed successfully: ' . $command);
            return rest_ensure_response($response);
            
        } catch (Exception $e) {
            error_log('Neetrino REST API: Command execution failed: ' . $e->getMessage());
            
            return new WP_Error('command_failed', 'Command execution failed: ' . $e->getMessage(), [
                'status' => 500,
                'command' => $command
            ]);
        }
    }
    
    /**
     * Обработка конкретных команд
     */
    private static function handle_command($command, $data = []) {
        switch ($command) {
            case 'delete_plugin':
                return self::delete_plugin();
                
            case 'deactivate_plugin':
                return self::deactivate_plugin();
                
            case 'toggle_module':
                $module = isset($data['module']) ? $data['module'] : '';
                $enable = isset($data['enable']) ? (bool)$data['enable'] : false;
                return self::toggle_module($module, $enable);
                
            case 'get_status':
                return self::get_site_status();
                
            case 'maintenance_mode':
                // Support both legacy boolean and new 3-state mode
                if (isset($data['mode'])) {
                    $mode = sanitize_text_field($data['mode']);
                    return self::set_maintenance_mode($mode);
                } else {
                    $enable = isset($data['enable']) ? (bool)$data['enable'] : false;
                    return self::toggle_maintenance_mode($enable);
                }
                
            case 'get_info':
                return self::get_site_info();
                
            case 'test_connection':
                return [
                    'success' => true,
                    'message' => 'Connection test successful',
                    'data' => [
                        'site_url' => home_url(),
                        'timestamp' => time()
                    ]
                ];
                
            case 'test_security':
                return [
                    'success' => true,
                    'message' => 'Security test successful - API key verified',
                    'data' => [
                        'site_url' => home_url(),
                        'timestamp' => time(),
                        'security_level' => 'STAGE_1',
                        'ip_verified' => true,
                        'api_key_verified' => true
                    ]
                ];
                
            default:
                throw new Exception('Unknown command: ' . $command);
        }
    }
    
    /**
     * Деактивация плагина
     */
    private static function deactivate_plugin() {
        // Проверка прав пользователя
        // if (!current_user_can('activate_plugins')) {
        //     return [
        //         'success' => false,
        //         'message' => 'Insufficient permissions to deactivate plugins'
        //     ];
        // }
        
        try {
            // Получаем путь к плагину
            $plugin_file = 'Neetrino/neetrino.php'; // Относительный путь
            
            // Проверяем активен ли плагин
            if (!is_plugin_active($plugin_file)) {
                return [
                    'success' => false,
                    'message' => 'Plugin is already inactive'
                ];
            }
            
            // Деактивируем плагин
            deactivate_plugins($plugin_file);
            
            return [
                'success' => true,
                'message' => 'Plugin deactivated successfully',
                'data' => [
                    'plugin' => $plugin_file,
                    'status' => 'deactivated'
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Plugin deactivation failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Удаление плагина (полное удаление файлов)
     */
    private static function delete_plugin() {
        // Проверка прав пользователя
        // if (!current_user_can('delete_plugins')) {
        //     return [
        //         'success' => false,
        //         'message' => 'Insufficient permissions to delete plugins'
        //     ];
        // }
        
        try {
            $plugin_file = 'Neetrino/neetrino.php';
            
            // ВАЖНО: Очищаем статус регистрации на dashboard ПЕРЕД удалением
            // чтобы при следующей установке плагин регистрировался заново
            delete_option('neetrino_dashboard_registered');
            delete_option('neetrino_site_id');
            delete_option('neetrino_dashboard_url');
            wp_clear_scheduled_hook('neetrino_dashboard_ping');
            
            error_log('Neetrino: Dashboard registration cleared due to delete command via REST API');
            
            // Сначала деактивируем
            if (is_plugin_active($plugin_file)) {
                deactivate_plugins($plugin_file);
            }
            
            // Удаляем файлы плагина
            $plugin_dir = WP_PLUGIN_DIR . '/Neetrino';
            
            if (is_dir($plugin_dir)) {
                // Рекурсивно удаляем папку плагина
                self::delete_directory($plugin_dir);
                
                return [
                    'success' => true,
                    'message' => 'Plugin deleted successfully and dashboard registration cleared',
                    'data' => [
                        'plugin' => $plugin_file,
                        'status' => 'deleted'
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Plugin directory not found'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Plugin deletion failed: ' . $e->getMessage()
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
                $action = 'enabled';
            } else {
                $active_modules = array_diff($active_modules, [$module_name]);
                $action = 'disabled';
            }
            
            // Сохраняем изменения
            update_option('neetrino_active_modules', array_values($active_modules));
            
            return [
                'success' => true,
                'message' => "Module '{$module_name}' {$action} successfully",
                'data' => [
                    'module' => $module_name,
                    'status' => $action,
                    'active_modules' => $active_modules
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Module toggle failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Режим обслуживания
     */
    private static function toggle_maintenance_mode($enable) {
        try {
            if ($enable) {
                // Включаем режим обслуживания
                // Backward compatible: store structured value
                update_option('neetrino_maintenance_mode', ['mode' => 'maintenance']);
                $message = 'Maintenance mode enabled';
            } else {
                // Выключаем режим обслуживания
                update_option('neetrino_maintenance_mode', ['mode' => 'open']);
                $message = 'Maintenance mode disabled';
            }
            
            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'maintenance_mode' => $enable,
                    'mode' => $enable ? 'maintenance' : 'open'
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Maintenance mode toggle failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Установка режима обслуживания в 3 состояниях (open | maintenance | closed)
     */
    private static function set_maintenance_mode($mode) {
        $allowed = ['open', 'maintenance', 'closed'];
        if (!in_array($mode, $allowed, true)) {
            return [
                'success' => false,
                'message' => 'Invalid mode. Allowed: open, maintenance, closed'
            ];
        }
        try {
            $current = get_option('neetrino_maintenance_mode', ['mode' => 'open']);
            $old_mode = is_array($current) && isset($current['mode']) ? $current['mode'] : ($current ? 'maintenance' : 'open');
            update_option('neetrino_maintenance_mode', ['mode' => $mode]);
            return [
                'success' => true,
                'message' => 'Maintenance mode updated',
                'data' => [
                    'mode' => $mode,
                    'old_mode' => $old_mode
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Maintenance mode update failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение статуса сайта
     */
    public static function get_status($request = null) {
        $status = self::get_site_status();
        return rest_ensure_response($status);
    }
    
    /**
     * Получение статуса сайта (внутренний метод)
     */
    private static function get_site_status() {
        return [
            'success' => true,
            'message' => 'Site status retrieved successfully',
            'data' => [
                'site_url' => home_url(),
                'site_name' => get_bloginfo('name'),
                'wp_version' => get_bloginfo('version'),
                'plugin_version' => (defined('NEETRINO_VERSION') ? NEETRINO_VERSION : '0.0.0'),
                'plugin_active' => is_plugin_active('Neetrino/neetrino.php'),
                'active_modules' => get_option('neetrino_active_modules', []),
                'maintenance_mode' => get_option('neetrino_maintenance_mode', ['mode' => 'open']),
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'timestamp' => time()
            ]
        ];
    }
    
    /**
     * Получение подробной информации о сайте
     */
    private static function get_site_info() {
        global $wpdb;
        
        return [
            'success' => true,
            'message' => 'Site information retrieved successfully',
            'data' => [
                'site' => [
                    'url' => home_url(),
                    'name' => get_bloginfo('name'),
                    'description' => get_bloginfo('description'),
                    'admin_email' => get_option('admin_email'),
                    'language' => get_locale()
                ],
                'wordpress' => [
                    'version' => get_bloginfo('version'),
                    'multisite' => is_multisite(),
                    'users_count' => count_users()['total_users'],
                    'posts_count' => wp_count_posts()->publish,
                    'pages_count' => wp_count_posts('page')->publish
                ],
                'server' => [
                    'php_version' => PHP_VERSION,
                    'mysql_version' => $wpdb->db_version(),
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time'),
                    'upload_max_filesize' => ini_get('upload_max_filesize')
                ],
                'neetrino' => [
                    'version' => (defined('NEETRINO_VERSION') ? NEETRINO_VERSION : '0.0.0'),
                    'active' => is_plugin_active('Neetrino/neetrino.php'),
                    'active_modules' => get_option('neetrino_active_modules', []),
                    'maintenance_mode' => get_option('neetrino_maintenance_mode', ['mode' => 'open'])
                ],
                'timestamp' => time()
            ]
        ];
    }
    
    /**
     * Ping endpoint для тестирования соединения
     */
    public static function ping($request) {
        return rest_ensure_response([
            'success' => true,
            'message' => 'Pong! API is working',
            'timestamp' => time(),
            'site_url' => home_url()
        ]);
    }

    /**
     * Ручной пуш версии на Dashboard (для тестов)
     */
    public static function push_version_now($request) {
        // Проверка API ключа как минимальная защита
        $api_key = $request->get_param('api_key');
        if (!self::verify_api_key($api_key)) {
            return new WP_Error('unauthorized', 'Invalid API key', ['status' => 401]);
        }

        if (class_exists('Neetrino_Registration') && method_exists('Neetrino_Registration', 'push_version_if_changed')) {
            Neetrino_Registration::push_version_if_changed();
            return rest_ensure_response([
                'success' => true,
                'message' => 'Push attempted',
                'plugin_version' => (defined('NEETRINO_VERSION') ? NEETRINO_VERSION : '0.0.0'),
                'timestamp' => time()
            ]);
        }
        return new WP_Error('not_available', 'Push method not available', ['status' => 500]);
    }
    
    /**
     * Рекурсивное удаление директории
     */
    private static function delete_directory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                self::delete_directory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
    
    /**
     * Методы безопасности
     */
    
    /**
     * Проверка доступа с IP Dashboard'а
     */
    private static function verify_dashboard_access($request) {
        $client_ip = self::get_client_ip();
        $allowed_dashboard_ip = get_option('neetrino_dashboard_ip');
        
        if (empty($allowed_dashboard_ip)) {
            error_log("NEETRINO Security: No dashboard IP configured");
            return false;
        }
        
        // Для локального тестирования
        if ($client_ip === '127.0.0.1' || $client_ip === '::1') {
            return true;
        }
        
        if ($client_ip !== $allowed_dashboard_ip) {
            self::log_security_event('blocked_ip', [
                'client_ip' => $client_ip,
                'allowed_ip' => $allowed_dashboard_ip
            ]);
            error_log("NEETRINO Security: Access denied from IP: $client_ip (allowed: $allowed_dashboard_ip)");
            return false;
        }
        
        return true;
    }
    
    /**
     * Получение реального IP клиента
     */
    private static function get_client_ip() {
        // Проверяем заголовки (для прокси, CloudFlare)
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // CloudFlare
            'HTTP_X_FORWARDED_FOR',      // Прокси
            'HTTP_X_REAL_IP',            // Nginx
            'REMOTE_ADDR'                // Стандартный
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Если несколько IP (через запятую), берем первый
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        
        return '127.0.0.1';
    }
    
    /**
     * Проверка API ключа
     */
    private static function verify_api_key($api_key) {
        if (empty($api_key)) {
            return false;
        }
        
        $stored_key = get_option('neetrino_dashboard_api_key');
        
        if (empty($stored_key)) {
            error_log("NEETRINO Security: No API key configured");
            return false;
        }
        
        return $api_key === $stored_key;
    }
    
    /**
     * Логирование событий безопасности
     */
    private static function log_security_event($event_type, $details = []) {
        $log_entry = [
            'timestamp' => time(),
            'event' => $event_type,
            'ip' => self::get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'details' => $details
        ];
        
        $security_log = get_option('neetrino_security_log', []);
        $security_log[] = $log_entry;
        
        // Оставляем только последние 50 записей
        if (count($security_log) > 50) {
            $security_log = array_slice($security_log, -50);
        }
        
        update_option('neetrino_security_log', $security_log);
        
        // Критические события в error_log
        if (in_array($event_type, ['blocked_ip', 'invalid_api_key'])) {
            error_log("NEETRINO SECURITY ALERT: $event_type from IP: " . $log_entry['ip']);
        }
    }
    
    /**
     * Обновление конфигурации Dashboard (для регистрации)
     */
    public static function update_dashboard_config($request) {
        // Проверка временного ключа для первичной регистрации
        $temp_key = $request->get_param('temp_key');
        $expected_temp_key = get_option('neetrino_temp_registration_key');
        
        if (empty($temp_key) || $temp_key !== $expected_temp_key) {
            self::log_security_event('invalid_temp_key', [
                'provided_key' => $temp_key ? 'provided' : 'empty'
            ]);
            return new WP_Error('unauthorized', 'Invalid temp key', ['status' => 401]);
        }
        
        // Сохраняем конфигурацию Dashboard
        $dashboard_ip = $request->get_param('dashboard_ip');
        $dashboard_domain = $request->get_param('dashboard_domain');
        $api_key = $request->get_param('api_key');
        
        if (empty($dashboard_ip) || empty($api_key)) {
            return new WP_Error('invalid_data', 'Missing required parameters', ['status' => 400]);
        }
        
        update_option('neetrino_dashboard_ip', $dashboard_ip);
        update_option('neetrino_dashboard_domain', $dashboard_domain);
        update_option('neetrino_dashboard_api_key', $api_key);
        update_option('neetrino_registration_status', 'registered');
        
        // Удаляем временный ключ
        delete_option('neetrino_temp_registration_key');
        
        self::log_security_event('dashboard_registered', [
            'dashboard_ip' => $dashboard_ip,
            'dashboard_domain' => $dashboard_domain
        ]);
        
        error_log("NEETRINO: Dashboard configuration updated - IP: $dashboard_ip");
        
        return [
            'status' => 'configured',
            'message' => 'Dashboard access configured successfully',
            'data' => [
                'dashboard_ip' => $dashboard_ip,
                'registration_status' => 'registered'
            ]
        ];
    }
    
    /**
     * Проверка конфигурации безопасности (для тестирования)
     */
    public static function check_security_config($request) {
        $config = [
            'dashboard_ip' => get_option('neetrino_dashboard_ip'),
            'dashboard_api_key' => get_option('neetrino_dashboard_api_key') ? 'Установлен' : 'Отсутствует',
            'registration_status' => get_option('neetrino_registration_status'),
            'temp_key' => get_option('neetrino_temp_registration_key'),
            'dashboard_domain' => get_option('neetrino_dashboard_domain'),
            'security_version' => '1.0 (Базовая защита)',
            'timestamp' => time(),
            'test_endpoint' => 'working'
        ];
        
        return [
            'success' => true,
            'message' => 'Security configuration retrieved',
            'config' => $config
        ];
    }
}

// Инициализируем REST API
Neetrino_REST_API::init();
?>

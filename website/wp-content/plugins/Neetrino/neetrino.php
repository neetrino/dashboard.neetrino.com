<?php
/**
 * Plugin Name: Neetrino
 * Description: Modular WordPress plugin for enhanced website management and integration
 * Version: 3.8.2
 * Author: Neetrino
 * Text Domain: neetrino
 */

/*
 * ⚠️ ВНИМАНИЕ РАЗРАБОТЧИКАМ МОДУЛЕЙ! ⚠️
 * 
 * ПЕРЕД СОЗДАНИЕМ ЛЮБОГО МОДУЛЯ ОБЯЗАТЕЛЬНО ПРОЧИТАЙТЕ:
 * wp-content/plugins/Neetrino/MODULE-DEVELOPMENT.md
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin file constant
define('NEETRINO_PLUGIN_FILE', __FILE__);
// Define plugin version constant from header to keep a single source of truth
if (!defined('NEETRINO_VERSION')) {
    if (function_exists('get_file_data')) {
        $data = get_file_data(__FILE__, ['Version' => 'Version'], 'plugin');
        $ver = isset($data['Version']) ? trim($data['Version']) : '';
        define('NEETRINO_VERSION', $ver !== '' ? $ver : '0.0.0');
    } else {
        // Fallback if get_file_data is not available for some reason
        define('NEETRINO_VERSION', '0..0.0');
    }
}

// Dashboard Control System - подключаем рано для использования в хуках активации
require_once plugin_dir_path(__FILE__) . 'includes/dashboard-control/Registration.php';
require_once plugin_dir_path(__FILE__) . 'includes/dashboard-control/Dashboard_Connect.php';
require_once plugin_dir_path(__FILE__) . 'includes/dashboard-control/REST_API.php';
require_once plugin_dir_path(__FILE__) . 'includes/dashboard-control/GET_Controller.php';
require_once plugin_dir_path(__FILE__) . 'includes/dashboard-control/Connection_Guard.php';
require_once plugin_dir_path(__FILE__) . 'includes/dashboard-control/Connection_Page.php';
require_once plugin_dir_path(__FILE__) . 'includes/dashboard-control/Connection_Admin.php';

class Neetrino {
    private static $neetrino_modules = [];

    /**
     * Автоматическое обнаружение и регистрация модулей
     */
    private static function detect_modules() {
        // Сканирование директории модулей
        $modules_dir = plugin_dir_path(__FILE__) . 'modules';
        if (is_dir($modules_dir)) {
            $module_folders = array_filter(glob($modules_dir . '/*'), 'is_dir');
            
            foreach ($module_folders as $module_folder) {
                $module_name = basename($module_folder);
                $module_slug = sanitize_title($module_name);
                $main_file = $module_folder . '/' . $module_name . '.php';
                
                // Проверка существования основного файла модуля
                if (file_exists($main_file)) {
                    // Регистрация модуля с простыми настройками
                    self::$neetrino_modules[$module_slug] = [
                        'file' => 'modules/' . $module_name . '/' . $module_name . '.php',
                        'title' => ucfirst(str_replace('-', ' ', $module_name)),
                        'active' => self::is_module_active($module_slug)
                    ];
                }
            }
        }
    }

    /**
     * Проверяет активен ли модуль
     */
    public static function is_module_active($module_slug) {
        $active_modules = get_option('neetrino_active_modules', self::get_default_active_modules());
        return in_array($module_slug, $active_modules);
    }

    /**
     * Возвращает список модулей активных по умолчанию
     */
    private static function get_default_active_modules() {
        return ['login-page', 'maintenance-mode'];
    }

    /**
     * Активирует модуль
     */
    public static function activate_module($module_slug) {
        $active_modules = get_option('neetrino_active_modules', self::get_default_active_modules());
        
        if (!in_array($module_slug, $active_modules)) {
            $active_modules[] = $module_slug;
            update_option('neetrino_active_modules', $active_modules);
            
            // Обновляем статус в массиве
            if (isset(self::$neetrino_modules[$module_slug])) {
                self::$neetrino_modules[$module_slug]['active'] = true;
            }
        }
    }

    /**
     * Деактивирует модуль
     */
    public static function deactivate_module($module_slug) {
        $active_modules = get_option('neetrino_active_modules', self::get_default_active_modules());
        
        $key = array_search($module_slug, $active_modules);
        if ($key !== false) {
            unset($active_modules[$key]);
            update_option('neetrino_active_modules', array_values($active_modules));
            
            // Обновляем статус в массиве
            if (isset(self::$neetrino_modules[$module_slug])) {
                self::$neetrino_modules[$module_slug]['active'] = false;
            }
        }
    }

    /**
     * Получает все модули
     */
    public static function get_all_modules() {
        return self::$neetrino_modules;
    }

    /**
     * Получает отсортированные модули
     */
    public static function get_sorted_modules() {
        $modules = self::$neetrino_modules;
        
        // Сортируем по номеру модуля
        uasort($modules, function($a, $b) {
            $num_a = self::get_module_number(array_search($a, self::$neetrino_modules));
            $num_b = self::get_module_number(array_search($b, self::$neetrino_modules));
            return $num_a - $num_b;
        });
        
        return $modules;
    }

    /**
     * Получает модули для отображения
     */
    public static function get_modules() {
        // Возвращаем только активные модули для корректного отображения в меню
        return array_filter(self::$neetrino_modules, function($module) {
            return $module['active'];
        });
    }

    /**
     * Получает статичный номер модуля
     */
    public static function get_module_number($module_slug) {
        // Статичные номера для модулей
        $module_numbers = [
            'login-page' => 1,
            'maintenance-mode' => 2,
            'bitrix24' => 3,
            'remote-control' => 4,
            'redirect-301' => 5,
            'auto-translate' => 6,
            'telegram' => 7,
            'delete' => 8,
            'menu-hierarchy' => 9,
             'reset' => 10,
            'user-switching' => 11,
            'wordpress-design' => 12,
            'delivery' => 13,
            'chat' => 14,
            'app-manager' => 15,
            'checkout-fields' => 16
        ];
        
        // Если модуль есть в списке - возвращаем его номер
        if (isset($module_numbers[$module_slug])) {
            return $module_numbers[$module_slug];
        }
        
        // Для новых модулей - генерируем номер на основе хеша имени
        $hash = crc32($module_slug);
        $number = ($hash % 90) + 10; // Номера от 10 до 99 для новых модулей
        return abs($number);
    }

    public static function init() {
        // ВАЖНО: Проверяем статус подключения перед инициализацией
        if (Neetrino_Connection_Guard::should_block_modules()) {
            // Загружаем только минимально необходимые файлы для подключения
            self::init_connection_only();
            return;
        }
        
        // Load required core files
        require_once plugin_dir_path(__FILE__) . 'includes/Module_Config.php';
        require_once plugin_dir_path(__FILE__) . 'includes/Assets.php';
        require_once plugin_dir_path(__FILE__) . 'includes/Dashboard.php';
        require_once plugin_dir_path(__FILE__) . 'includes/Admin.php';
        
        // Plugin Update System - система отслеживания обновлений плагина
        require_once plugin_dir_path(__FILE__) . 'includes/Plugin_Update_Manager.php';
        
        // Инициализация Dashboard Connect (файлы уже подключены выше)
        Neetrino_Dashboard_Connect::init();
        // REST API инициализируется автоматически в классе
        
        new Neetrino_Admin();
        
        // Инициализируем менеджер обновлений
        new Neetrino_Plugin_Update_Manager();

        // Hide WordPress footer on all admin pages
        add_action('admin_head', function() {
            echo '<style>#wpfooter { display: none !important; }</style>';
        });

        // Initialize only active modules
        self::detect_modules();
        foreach (self::$neetrino_modules as $slug => $module) {
            // Загружаем только активные модули
            if ($module['active']) {
                $module_path = plugin_dir_path(__FILE__) . $module['file'];
                if (file_exists($module_path)) {
                    require_once $module_path;
                }
            }
        }
    }
    
    /**
     * Инициализация только для подключения к дашборду
     */
    private static function init_connection_only() {
        // Загружаем только необходимые файлы для админки подключения
        require_once plugin_dir_path(__FILE__) . 'includes/Assets.php';
        
        // Инициализация Dashboard Connect для попыток подключения
        Neetrino_Dashboard_Connect::init();
        
        // Добавляем специальную админку только для подключения
        new Neetrino_Connection_Admin();
        
        // Блокируем все AJAX запросы кроме связанных с подключением
        add_action('wp_ajax_neetrino_check_connection_status', [__CLASS__, 'handle_connection_status_check']);
        add_action('wp_ajax_neetrino_manual_connect', [__CLASS__, 'handle_manual_connect']);
        
        // Блокируем все остальные AJAX запросы плагина
        add_action('wp_ajax_neetrino_toggle_module', [__CLASS__, 'block_ajax_request']);
        
        error_log('NEETRINO: Plugin initialized in connection-only mode');
    }
    
    /**
     * Блокировка AJAX запросов
     */
    public static function block_ajax_request() {
        wp_send_json_error([
            'message' => 'Плагин заблокирован до установки подключения с дашбордом',
            'code' => 'connection_required'
        ]);
    }
    
    /**
     * Обработчик проверки статуса подключения
     */
    public static function handle_connection_status_check() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_connection_check')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Пытаемся автоматическое подключение если возможно
        if (!Neetrino_Connection_Guard::is_connected()) {
            Neetrino_Connection_Guard::attempt_auto_connection();
        }
        
        $status = Neetrino_Connection_Guard::get_status_info();
        wp_send_json_success($status);
    }
    
    /**
     * Обработчик ручного подключения
     */
    public static function handle_manual_connect() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_manual_connect')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $dashboard_url = sanitize_text_field($_POST['dashboard_url']);
        $api_key = sanitize_text_field($_POST['api_key']);
        
        if (empty($dashboard_url) || empty($api_key)) {
            wp_send_json_error('Dashboard URL and API key are required');
        }
        
        // Пытаемся подключиться
        $result = Neetrino_Registration::register_with_dashboard($dashboard_url, $api_key);
        
        if ($result['success']) {
            wp_send_json_success('Successfully connected to dashboard');
        } else {
            wp_send_json_error($result['message']);
        }
    }
}

// Инициализация плагина
add_action('plugins_loaded', ['Neetrino', 'init']);

// Хуки активации и деактивации
register_activation_hook(__FILE__, function() {
    // Устанавливаем активные модули по умолчанию
    $default_modules = ['login-page', 'maintenance-mode'];
    if (!get_option('neetrino_active_modules')) {
        update_option('neetrino_active_modules', $default_modules);
    }
    
    // Очищаем старые настройки если есть
    delete_option('neetrino_dashboard_domain');
    delete_option('neetrino_dashboard_api_key');
    
    // Автоматическая перерегистрация после обновления
    if (class_exists('Neetrino_Registration')) {
        Neetrino_Registration::register_with_dashboard();
    }
    
    error_log('NEETRINO: Plugin activated successfully');
});

register_deactivation_hook(__FILE__, function() {
    // Очищаем CRON события
    wp_clear_scheduled_hook('neetrino_check_update_event');
    
    error_log('NEETRINO: Plugin deactivated');
});



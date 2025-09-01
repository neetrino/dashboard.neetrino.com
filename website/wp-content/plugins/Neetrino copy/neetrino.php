<?php
/**
 * Plugin Name: Neetrino
 * Description: Modular WordPress plugin for enhanced website management and integration
 * Version: 3.6.3
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
        define('NEETRINO_VERSION', '0.0.0');
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
        }
    }

    /**
     * Получает все модули (активные и неактивные)
     */
    public static function get_all_modules() {
        if (empty(self::$neetrino_modules)) {
            self::detect_modules();
        }
        return self::$neetrino_modules;
    }

    /**
     * Получает все модули отсортированные по активности (активные первыми)
     */
    public static function get_sorted_modules() {
        $all_modules = self::get_all_modules();
        
        // Разделяем на активные и неактивные
        $active_modules = [];
        $inactive_modules = [];
        
        foreach ($all_modules as $slug => $module) {
            if ($module['active']) {
                $active_modules[$slug] = $module;
            } else {
                $inactive_modules[$slug] = $module;
            }
        }
        
        // Сортируем активные модули по номерам
        uksort($active_modules, function($a, $b) {
            return self::get_module_number($a) - self::get_module_number($b);
        });
        
        // Сортируем неактивные модули по номерам
        uksort($inactive_modules, function($a, $b) {
            return self::get_module_number($a) - self::get_module_number($b);
        });
        
        // Объединяем: сначала активные, потом неактивные
        return array_merge($active_modules, $inactive_modules);
    }

    /**
     * Получает только активные модули (для обратной совместимости)
     */
    public static function get_modules() {
        if (empty(self::$neetrino_modules)) {
            self::detect_modules();
        }
        // Возвращаем только активные модули для обратной совместимости
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
        require_once plugin_dir_path(__FILE__) . 'includes/New_Plugin.php';
        require_once plugin_dir_path(__FILE__) . 'includes/Module_Config.php';
        require_once plugin_dir_path(__FILE__) . 'includes/Assets.php';
        require_once plugin_dir_path(__FILE__) . 'includes/Dashboard.php';
        require_once plugin_dir_path(__FILE__) . 'includes/Admin.php';
        
        // Инициализация Dashboard Connect (файлы уже подключены выше)
        Neetrino_Dashboard_Connect::init();
        // REST API инициализируется автоматически в классе
        
        new Neetrino_Admin();

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
        
        if (!current_user_can('administrator')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Сбрасываем состояние и пытаемся подключиться
        Neetrino_Connection_Guard::reset_connection_status();
        $result = Neetrino_Registration::register_with_dashboard();
        
        if ($result['success']) {
            wp_send_json_success([
                'connected' => true,
                'message' => 'Подключение установлено успешно'
            ]);
        } else {
            wp_send_json_error([
                'connected' => false,
                'message' => $result['error'] ?? 'Ошибка подключения'
            ]);
        }
    }

}

// Hook для активации плагина - устанавливаем дефолтные настройки
register_activation_hook(__FILE__, function() {
    // Устанавливаем дефолтные активные модули только если опция еще не существует
    if (!get_option('neetrino_active_modules')) {
        update_option('neetrino_active_modules', ['login-page', 'maintenance-mode']);
    }
    
    // Сбрасываем статус подключения для новой попытки
    delete_option('neetrino_connection_attempts');
    delete_option('neetrino_last_connection_attempt');
    delete_option('neetrino_connection_force_manual');
    
    // Автоматическая регистрация с Dashboard
    $result = Neetrino_Registration::register_with_dashboard();
    
    if (!$result['success']) {
        // Если не удалось подключиться сразу, планируем попытки
        error_log('NEETRINO Activation: Initial connection failed, will retry automatically');
        update_option('neetrino_connection_attempts', 1);
        update_option('neetrino_last_connection_attempt', time());
    } else {
        error_log('NEETRINO Activation: Connected successfully on activation');
    }
});

// Инициализация плагина
add_action('plugins_loaded', ['Neetrino', 'init']);

// После загрузки плагинов: если версия изменилась, пушим новую версию на Dashboard (лёгкая операция)
add_action('plugins_loaded', function() {
    // Проверяем версию плагина для обновлений
    if (function_exists('Neetrino_Registration::push_version_if_changed')) {
        Neetrino_Registration::push_version_if_changed();
    } else {
        // Для ранней совместимости, если метод ещё не доступен
        if (class_exists('Neetrino_Registration') && method_exists('Neetrino_Registration', 'push_version_if_changed')) {
            Neetrino_Registration::push_version_if_changed();
        }
    }
    
    // Проверяем подключение после обновления плагина
    $current_version = defined('NEETRINO_VERSION') ? NEETRINO_VERSION : '0.0.0';
    $stored_version = get_option('neetrino_last_checked_version', '0.0.0');
    
    if (version_compare($current_version, $stored_version, '>')) {
        // Версия изменилась - проверяем подключение
        update_option('neetrino_last_checked_version', $current_version);
        
        if (!Neetrino_Connection_Guard::is_connected()) {
            // Сбрасываем ограничения для попыток подключения после обновления
            delete_option('neetrino_connection_attempts');
            delete_option('neetrino_last_connection_attempt');
            delete_option('neetrino_connection_force_manual');
            
            error_log('NEETRINO: Plugin updated to ' . $current_version . ', resetting connection attempts');
        }
    }
});

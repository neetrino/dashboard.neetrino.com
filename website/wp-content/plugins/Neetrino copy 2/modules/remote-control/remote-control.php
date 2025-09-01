<?php
/**
 * Module: Remote Control
 * Description: Универсальное удаленное управление через API
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Remote_Control {
    
    public function __construct() {
        error_log('Remote Control: Module constructor called');
        
        // Инициализация модуля только если он активен
        if (!Neetrino::is_module_active('remote-control')) {
            error_log('Remote Control: Module is not active, skipping initialization');
            return;
        }
        
        error_log('Remote Control: Module is active, proceeding with initialization');
        
        // Подключаем необходимые классы
        $this->load_dependencies();
        
        // Хуки и действия модуля
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        
        error_log('Remote Control: Module initialization complete');
    }
    
    /**
     * Загрузка зависимостей
     */
    private function load_dependencies() {
        require_once plugin_dir_path(__FILE__) . 'includes/class-remote-control-api.php';
        require_once plugin_dir_path(__FILE__) . 'includes/admin-interface.php';
    }
    
    /**
     * Инициализация модуля
     */
    public function init() {
        // Инициализация API обработчика с логированием
        error_log('Remote Control: Initializing API handler');
        $api = new Remote_Control_API();
        error_log('Remote Control: API handler initialized successfully');
    }
    
    /**
     * Подключение скриптов для фронтенда
     */
    public function enqueue_scripts() {
        // Пока не нужно
    }
    
    /**
     * Подключение скриптов для админки
     */
    public function admin_enqueue_scripts($hook) {
        // Загружаем только на странице нашего модуля
        if (strpos($hook, 'neetrino_remote-control') !== false) {
            wp_enqueue_style(
                'neetrino-remote-control-admin',
                plugin_dir_url(__FILE__) . 'assets/css/admin.css',
                [],
                '1.0.0'
            );
            
            wp_enqueue_script(
                'neetrino-remote-control-admin',
                plugin_dir_url(__FILE__) . 'assets/js/admin.js',
                ['jquery'],
                '1.0.0',
                true
            );
            
            // Передаем данные в JavaScript
            wp_localize_script('neetrino-remote-control-admin', 'remoteControlAjax', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('remote_control_nonce')
            ]);
        }
    }
    
    /**
     * Статический метод для админ-страницы
     * Вызывается автоматически если модуль активен
     */
    public static function admin_page() {
        // Подключаем интерфейс
        if (function_exists('remote_control_render_admin_interface')) {
            remote_control_render_admin_interface();
        } else {
            ?>
            <div class="wrap neetrino-dashboard">
                <div class="neetrino-header">
                    <div class="neetrino-header-left">
                        <h1>Remote Control</h1>
                    </div>
                </div>
                <div class="neetrino-content">
                    <div class="neetrino-card">
                        <h2>Загрузка...</h2>
                        <p>Интерфейс Remote Control загружается...</p>
                    </div>
                </div>
            </div>
            <?php
        }
    }
}

// Инициализация модуля
new Neetrino_Remote_Control();

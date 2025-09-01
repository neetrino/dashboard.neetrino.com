<?php
/**
 * Neetrino Connection Admin
 * Админка только для управления подключением к дашборду
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Connection_Admin {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_connection_menu']);
        add_action('admin_init', [$this, 'handle_connection_actions']);
        
        // Скрываем все остальные страницы плагина, если не подключены
        add_action('admin_menu', [$this, 'hide_other_menu_items'], 999);
        
        // Перенаправляем на страницу подключения если пытаются зайти на другие страницы
        add_action('admin_init', [$this, 'redirect_to_connection_page']);
    }
    
    /**
     * Добавляет меню только для подключения
     */
    public function add_connection_menu() {
        if (!current_user_can('administrator')) {
            return;
        }
        
        // Главная страница - подключение
        add_menu_page(
            'Neetrino - Подключение к Dashboard',
            'Neetrino',
            'administrator',
            'neetrino_dashboard',
            [$this, 'render_connection_page'],
            'dashicons-admin-generic',
            1
        );
    }
    
    /**
     * Скрывает другие пункты меню плагина
     */
    public function hide_other_menu_items() {
        global $submenu;
        
        // Убираем все подменю Neetrino кроме основного
        if (isset($submenu['neetrino_dashboard'])) {
            foreach ($submenu['neetrino_dashboard'] as $key => $item) {
                if ($item[2] !== 'neetrino_dashboard') {
                    unset($submenu['neetrino_dashboard'][$key]);
                }
            }
        }
    }
    
    /**
     * Перенаправляет на страницу подключения
     */
    public function redirect_to_connection_page() {
        // Проверяем только в админке
        if (!is_admin()) {
            return;
        }
        
        // Проверяем права доступа
        if (!current_user_can('administrator')) {
            return;
        }
        
        $current_page = isset($_GET['page']) ? $_GET['page'] : '';
        
        // Если пытаются зайти на страницы Neetrino, но не на страницу подключения
        if (strpos($current_page, 'neetrino_') === 0 && $current_page !== 'neetrino_dashboard') {
            wp_redirect(admin_url('admin.php?page=neetrino_dashboard'));
            exit;
        }
    }
    
    /**
     * Обрабатывает действия связанные с подключением
     */
    public function handle_connection_actions() {
        // Обработка сброса подключения
        if (isset($_GET['reset_connection']) && $_GET['reset_connection'] == '1') {
            if (current_user_can('administrator')) {
                Neetrino_Connection_Guard::reset_connection_status();
                wp_redirect(admin_url('admin.php?page=neetrino_dashboard'));
                exit;
            }
        }
        
        // Запуск автоматической попытки подключения в фоне
        if (is_admin() && current_user_can('administrator')) {
            // Проверяем только раз за сессию
            if (!get_transient('neetrino_connection_attempt_this_session')) {
                set_transient('neetrino_connection_attempt_this_session', true, 300); // 5 минут
                
                // Планируем попытку подключения в фоне
                wp_schedule_single_event(time() + 5, 'neetrino_background_connection_attempt');
                add_action('neetrino_background_connection_attempt', function() {
                    Neetrino_Connection_Guard::attempt_auto_connection();
                });
            }
        }
    }
    
    /**
     * Рендерит страницу подключения
     */
    public function render_connection_page() {
        // Если уже подключены, показываем обычный дашборд
        if (Neetrino_Connection_Guard::is_connected()) {
            // Загружаем обычную админку
            if (class_exists('Neetrino_Dashboard')) {
                Neetrino_Dashboard::render();
                return;
            }
        }
        
        // Показываем страницу принудительного подключения
        Neetrino_Connection_Page::render();
    }
}

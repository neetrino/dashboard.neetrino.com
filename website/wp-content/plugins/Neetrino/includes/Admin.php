<?php
/**
 * Neetrino Admin Interface
 * 
 * @package Neetrino
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Admin {
    private $assets;

    public function __construct() {
        try {
            if (class_exists('Neetrino_Assets')) {
                $this->assets = new Neetrino_Assets();
            }
            
            $this->init_hooks();
        } catch (Exception $e) {
            error_log('NEETRINO: Error in Admin constructor: ' . $e->getMessage());
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        try {
            add_action('admin_menu', [$this, 'add_admin_menu']);
            
            // Хук для обработки перерегистрации Dashboard
            add_action('admin_post_neetrino_dashboard_reconnect', [$this, 'handle_dashboard_reconnect']);
            
            // AJAX хуки для управления модулями
            add_action('wp_ajax_neetrino_toggle_module', [$this, 'handle_toggle_module']);
            
            // Удаляем все уведомления WordPress на странице dashboard плагина
            add_action('admin_print_scripts', [$this, 'remove_admin_notices']);
        } catch (Exception $e) {
            error_log('NEETRINO: Error in init_hooks: ' . $e->getMessage());
        }
    }
    
    /**
     * Обработчик AJAX для переключения модуля
     */
    public function handle_toggle_module() {
        try {
            // Проверка безопасности
            if (!wp_verify_nonce($_POST['nonce'], 'neetrino_module_nonce')) {
                wp_send_json_error('Ошибка nonce');
            }
            
            if (!current_user_can('administrator')) {
                wp_send_json_error('Недостаточно прав');
            }

            if (!isset($_POST['module_slug']) || !isset($_POST['active'])) {
                wp_send_json_error('Отсутствуют необходимые данные');
            }

            $module_slug = sanitize_text_field($_POST['module_slug']);
            $active = intval($_POST['active']);

            // Проверяем, что модуль существует
            if (class_exists('Neetrino')) {
                $all_modules = Neetrino::get_all_modules();
                if (!isset($all_modules[$module_slug])) {
                    wp_send_json_error('Модуль не найден: ' . $module_slug);
                }

                if ($active) {
                    Neetrino::activate_module($module_slug);
                } else {
                    Neetrino::deactivate_module($module_slug);
                }
                
                wp_send_json_success([
                    'module' => $module_slug, 
                    'active' => $active,
                    'message' => $active ? 'Модуль активирован' : 'Модуль деактивирован'
                ]);
            } else {
                wp_send_json_error('Neetrino class not available');
            }
        } catch (Exception $e) {
            error_log('NEETRINO: Error in handle_toggle_module: ' . $e->getMessage());
            wp_send_json_error('Ошибка при обновлении модуля: ' . $e->getMessage());
        }
    }

    public function add_admin_menu() {
        try {
            if (!current_user_can('administrator')) {
                return;
            }

            // Add main menu page
            add_menu_page(
                __('Neetrino', 'neetrino'),
                __('Neetrino', 'neetrino'),
                'administrator',
                'neetrino_dashboard',
                [$this, 'render_dashboard'],
                'dashicons-admin-generic',
                1 // Поднимаем в самый верх меню админки (после Dashboard)
            );

            // WordPress автоматически создаст первый пункт подменю с названием главного меню
            // Поэтому убираем дублирующий add_submenu_page
            
            // Добавление подменю для каждого АКТИВНОГО модуля
            if (class_exists('Neetrino')) {
                $modules = Neetrino::get_modules(); // Это возвращает только активные модули
                foreach ($modules as $slug => $module) {
                    $page_title = isset($module['title']) ? $module['title'] : ucfirst($slug);
                    $menu_title = isset($module['title']) ? $module['title'] : ucfirst($slug);
                    
                    // Создаем стандартный callback для модуля
                    $callback = function() use ($slug, $menu_title) {
                        // Проверяем существование класса модуля и метода admin_page
                        $class_name = 'Neetrino_' . str_replace([' ', '-'], '_', ucwords(str_replace('-', ' ', $slug)));
                        if (class_exists($class_name) && method_exists($class_name, 'admin_page')) {
                            call_user_func([$class_name, 'admin_page']);
                        } else {
                            // Запасной вариант для модулей без интерфейса
                            echo '<div class="wrap neetrino-dashboard"><div class="neetrino-header"><div class="neetrino-header-left"><h1>' . esc_html($menu_title) . '</h1></div></div>';
                            echo '<div class="neetrino-content"><div class="neetrino-card"><p>Модуль загружен, но интерфейс админки не найден.</p>';
                            echo '<p>Ожидаемый класс: <code>' . esc_html($class_name) . '</code></p></div></div></div>';
                        }
                    };
                    
                    add_submenu_page(
                        'neetrino_dashboard',
                        $page_title,
                        $menu_title,
                        'administrator',
                        'neetrino_' . $slug,
                        $callback
                    );
                }
            }
        } catch (Exception $e) {
            error_log('NEETRINO: Error in add_admin_menu: ' . $e->getMessage());
        }
    }


    

    

    




    /**
     * Удаляет все уведомления WordPress на страницах плагина Neetrino
     * и централизованно управляет показом уведомлений
     */
    public function remove_admin_notices() {
        try {
            // Проверяем права доступа
            if (!current_user_can('administrator')) {
                return;
            }
            
            $current_screen = get_current_screen();
            
            // Проверяем, что мы находимся на страницах Neetrino
            // Все страницы плагина имеют 'neetrino' в ID экрана
            if (!$current_screen || strpos($current_screen->id, 'neetrino') === false) {
                return;
            }
            
            // Удаляем все WordPress уведомления на страницах плагина
            global $wp_filter;
            
            // Сохраняем копию наших обработчиков уведомлений перед удалением
            $neetrino_notice_handlers = [];
            if (isset($wp_filter['admin_notices']->callbacks)) {
                foreach ($wp_filter['admin_notices']->callbacks as $priority => $callbacks) {
                    foreach ($callbacks as $key => $callback) {
                        // Проверяем, принадлежит ли обработчик нашему плагину
                        if (is_array($callback['function']) && is_object($callback['function'][0]) && 
                            (strpos(get_class($callback['function'][0]), 'Neetrino') !== false)) {
                            $neetrino_notice_handlers[$priority][$key] = $callback;
                        }
                    }
                }
            }
            
            // Удаляем все стандартные уведомления WordPress
            unset($wp_filter['user_admin_notices'], $wp_filter['admin_notices'], $wp_filter['all_admin_notices']);
            
            // Восстанавливаем только наши обработчики уведомлений
            if (!empty($neetrino_notice_handlers) && isset($wp_filter['admin_notices'])) {
                foreach ($neetrino_notice_handlers as $priority => $callbacks) {
                    foreach ($callbacks as $key => $callback) {
                        $wp_filter['admin_notices']->callbacks[$priority][$key] = $callback;
                    }
                }
            }
        } catch (Exception $e) {
            error_log('NEETRINO: Error in remove_admin_notices: ' . $e->getMessage());
        }
    }

    public function render_dashboard() {
        try {
            if (class_exists('Neetrino_Dashboard')) {
                Neetrino_Dashboard::render();
            } else {
                echo '<div class="wrap"><h1>Neetrino Dashboard</h1><p>Dashboard class not available</p></div>';
            }
        } catch (Exception $e) {
            error_log('NEETRINO: Error rendering dashboard: ' . $e->getMessage());
            echo '<div class="wrap"><h1>Neetrino Dashboard</h1><p>Error loading dashboard</p></div>';
        }
    }
    
    /**
     * Обработчик перерегистрации Dashboard
     * Обновлено для новой системы безопасности (Этап 1)
     */
    public function handle_dashboard_reconnect() {
        try {
            // Проверка безопасности
            if (!wp_verify_nonce($_POST['_wpnonce'], 'neetrino_dashboard_reconnect')) {
                wp_die('Ошибка безопасности');
            }
            
            // Проверка прав доступа
            if (!current_user_can('administrator')) {
                wp_die('Недостаточно прав доступа');
            }
            
            error_log("NEETRINO Security: Dashboard reconnect initiated by admin");
            
            // Очищаем старые данные подключения
            delete_option('neetrino_dashboard_registered');
            delete_option('neetrino_site_id');
            delete_option('neetrino_dashboard_url');
            
            // Очищаем новые данные безопасности
            delete_option('neetrino_dashboard_ip');
            delete_option('neetrino_dashboard_api_key');
            delete_option('neetrino_registration_status');
            delete_option('neetrino_temp_registration_key');
            
            // Останавливаем старый cron
            wp_clear_scheduled_hook('neetrino_dashboard_ping');
            
            error_log("NEETRINO Security: Old connection data cleared");
            
            // Используем общий класс регистрации
            if (class_exists('Neetrino_Registration')) {
                $result = Neetrino_Registration::register_with_dashboard();
                
                if ($result['success']) {
                    error_log("NEETRINO Security: New registration successful");
                    // После перерегистрации отправим текущую версию на Dashboard (принудительно)
                    if (method_exists('Neetrino_Registration', 'push_version_if_changed')) {
                        Neetrino_Registration::push_version_if_changed(true);
                    }
                    wp_redirect(admin_url('admin.php?page=neetrino_dashboard&reconnect=success&method=security_v2'));
                } else {
                    error_log("NEETRINO Security: New registration failed - " . $result['error']);
                    wp_redirect(admin_url('admin.php?page=neetrino_dashboard&reconnect=error&error=' . urlencode($result['error'])));
                }
            } else {
                error_log("NEETRINO Security: Registration class not available");
                wp_redirect(admin_url('admin.php?page=neetrino_dashboard&reconnect=error&error=Registration class not available'));
            }
            exit;
        } catch (Exception $e) {
            error_log("NEETRINO Security: Error in handle_dashboard_reconnect: " . $e->getMessage());
            wp_redirect(admin_url('admin.php?page=neetrino_dashboard&reconnect=error&error=' . urlencode($e->getMessage())));
            exit;
        }
    }
    

}

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
        $this->assets = new Neetrino_Assets();
        
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Хуки для обработки действий обновления - только прямое обновление
        add_action('admin_post_neetrino_direct_update', [$this, 'handle_direct_update']);
        
        // Хук для обработки перерегистрации Dashboard
        add_action('admin_post_neetrino_dashboard_reconnect', [$this, 'handle_dashboard_reconnect']);
        
        // AJAX хуки для управления модулями
        add_action('wp_ajax_neetrino_toggle_module', [$this, 'handle_toggle_module']);
        
        add_action('admin_notices', [$this, 'show_update_notices']);
        
        // Удаляем все уведомления WordPress на странице dashboard плагина
        add_action('admin_print_scripts', [$this, 'remove_admin_notices']);
    }
    
    /**
     * Обработчик AJAX для переключения модуля
     */
    public function handle_toggle_module() {
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
        $all_modules = Neetrino::get_all_modules();
        if (!isset($all_modules[$module_slug])) {
            wp_send_json_error('Модуль не найден: ' . $module_slug);
        }

        try {
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
        } catch (Exception $e) {
            wp_send_json_error('Ошибка при обновлении модуля: ' . $e->getMessage());
        }
    }

    public function add_admin_menu() {
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


    

    
    /**
     * Обрабатывает запрос на прямое обновление плагина
     */
    public function handle_direct_update() {
        if (!current_user_can('administrator') || !check_admin_referer('neetrino_direct_update')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'neetrino'));
        }
        
        // Сразу запускаем процесс обновления без проверки
        // Получаем URL архива плагина
        $remote_plugin_url = 'http://costom-scripts.neetrino.net/Plugin/Neetrino.zip';
        
        // Инициализируем обновление плагина
        require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        
        // Временно деактивируем плагин
        $plugin_file = plugin_basename(NEETRINO_PLUGIN_FILE);
        deactivate_plugins($plugin_file, true);
        
        // Создаем апгрейдер с тихим выводом
        $skin = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader($skin);
        
        // Отключаем проверки на совместимость и т.д.
        add_filter('upgrader_package_options', function($options) {
            $options['abort_if_destination_exists'] = false;
            $options['hook_extra']['plugin'] = plugin_basename(NEETRINO_PLUGIN_FILE);
            return $options;
        });
        
        // Очищаем кэш обновлений
        wp_clean_plugins_cache(false);
        
        // Выполняем установку напрямую из URL
        $upgraded = $upgrader->install($remote_plugin_url, ['overwrite_package' => true]);
        
        // Активируем плагин снова
        activate_plugin($plugin_file);
        
        // ВАЖНО: После обновления проверяем регистрацию на дашборде
        Neetrino_Dashboard_Connect::maybe_register();
        
        if (!is_wp_error($upgraded)) {
            // Удаляем флаг обновления
            delete_transient('neetrino_update_available');
            delete_option('neetrino_current_version');
            
            $result = [
                'success' => true,
                'message' => __('Plugin updated successfully!', 'neetrino')
            ];
        } else {
            $result = [
                'success' => false,
                'message' => __('An error occurred during the update process: ', 'neetrino') . $upgraded->get_error_message()
            ];
        }
        
        set_transient('neetrino_update_result', $result, 30);
        wp_redirect(admin_url('admin.php?page=neetrino_dashboard'));
        exit;
    }
    


    public function show_update_notices() {
        $result = get_transient('neetrino_update_result');
        if (!$result) {
            return;
        }

        delete_transient('neetrino_update_result');
        $class = $result['success'] ? 'notice-success' : 'notice-error';
        ?>
        <div class="notice <?php echo esc_attr($class); ?> is-dismissible">
            <p><?php echo esc_html($result['message']); ?></p>
        </div>
        <?php
    }

    /**
     * Удаляет все уведомления WordPress на страницах плагина Neetrino
     * и централизованно управляет показом уведомлений
     */
    public function remove_admin_notices() {
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
        } else {
            // Добавляем наш обработчик уведомлений об обновлениях, если других нет
            add_action('admin_notices', [$this, 'show_update_notices'], 1);
        }
    }

    public function render_dashboard() {
        Neetrino_Dashboard::render();
    }
    
    /**
     * Обработчик перерегистрации Dashboard
     * Обновлено для новой системы безопасности (Этап 1)
     */
    public function handle_dashboard_reconnect() {
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
        $result = Neetrino_Registration::register_with_dashboard();
        
        if ($result['success']) {
            error_log("NEETRINO Security: New registration successful");
            // После перерегистрации отправим текущую версию на Dashboard (принудительно)
            if (class_exists('Neetrino_Registration') && method_exists('Neetrino_Registration', 'push_version_if_changed')) {
                Neetrino_Registration::push_version_if_changed(true);
            }
            wp_redirect(admin_url('admin.php?page=neetrino_dashboard&reconnect=success&method=security_v2'));
        } else {
            error_log("NEETRINO Security: New registration failed - " . $result['error']);
            wp_redirect(admin_url('admin.php?page=neetrino_dashboard&reconnect=error&error=' . urlencode($result['error'])));
        }
        exit;
    }
    
    /**
     * Публичный метод для обновления плагина (вызывается из REST_API.php)
     * Возвращает результат обновления в формате массива
     */
    public function perform_plugin_update() {
        error_log("NEETRINO Admin: perform_plugin_update() вызван из REST API");
        
        // Получаем URL архива плагина
        $remote_plugin_url = 'http://costom-scripts.neetrino.net/Plugin/Neetrino.zip';
        
        // Инициализируем обновление плагина
        require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        
        // Временно деактивируем плагин
        $plugin_file = plugin_basename(NEETRINO_PLUGIN_FILE);
        deactivate_plugins($plugin_file, true);
        
        // Создаем апгрейдер с тихим выводом
        $skin = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader($skin);
        
        // Отключаем проверки на совместимость и т.д.
        add_filter('upgrader_package_options', function($options) {
            $options['abort_if_destination_exists'] = false;
            $options['hook_extra']['plugin'] = plugin_basename(NEETRINO_PLUGIN_FILE);
            return $options;
        });
        
        // Очищаем кэш обновлений
        wp_clean_plugins_cache(false);
        
        // Выполняем установку напрямую из URL
        $upgraded = $upgrader->install($remote_plugin_url, ['overwrite_package' => true]);
        
        // Активируем плагин снова
        activate_plugin($plugin_file);
        
        // ВАЖНО: После обновления проверяем регистрацию на дашборде
        Neetrino_Dashboard_Connect::maybe_register();
        
        if (!is_wp_error($upgraded)) {
            // Удаляем флаг обновления
            delete_transient('neetrino_update_available');
            delete_option('neetrino_current_version');
            
            error_log("NEETRINO Admin: Обновление плагина успешно завершено");
            
            return [
                'success' => true,
                'message' => 'Plugin updated successfully!',
                'old_version' => '3.7.0', // TODO: получать реальную версию
                'new_version' => '3.7.1'  // TODO: получать реальную версию
            ];
        } else {
            error_log("NEETRINO Admin: Ошибка обновления плагина - " . $upgraded->get_error_message());
            
            return [
                'success' => false,
                'message' => 'An error occurred during the update process: ' . $upgraded->get_error_message(),
                'old_version' => '3.7.0',
                'new_version' => '3.7.0'
            ];
        }
    }
}

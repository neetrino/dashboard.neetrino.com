<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Менеджер обновлений плагина Neetrino
 * Рабочая система обновлений из Neetrino copy
 */
class Neetrino_Plugin_Update_Manager {
    
    private $remote_plugin_url = 'http://costom-scripts.neetrino.net/Plugin/Neetrino.zip';
    
    /**
     * Конструктор - инициализирует все хуки обновления
     */
    public function __construct() {
        // Хуки для административных функций
        add_action('admin_post_neetrino_direct_update', [$this, 'handle_direct_update']);
        add_action('admin_notices', [$this, 'show_update_notices']);
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
    
    /**
     * Показывает уведомления об обновлениях
     */
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
     * Публичный метод для обновления плагина (вызывается из REST_API.php)
     * Возвращает результат обновления в формате массива
     */
    public function perform_plugin_update() {
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
        
        if (!is_wp_error($upgraded)) {
            // Удаляем флаг обновления
            delete_transient('neetrino_update_available');
            delete_option('neetrino_current_version');
            
            return [
                'success' => true,
                'message' => 'Plugin updated successfully!',
                'old_version' => '3.7.0', // TODO: получать реальную версию
                'new_version' => '3.7.1'  // TODO: получать реальную версию
            ];
        } else {
            return [
                'success' => false,
                'message' => 'An error occurred during the update process: ' . $upgraded->get_error_message(),
                'old_version' => '3.7.0',
                'new_version' => '3.7.0'
            ];
        }
    }
}



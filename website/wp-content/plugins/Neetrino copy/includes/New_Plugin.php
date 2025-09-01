<?php

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Plugin_Updater {
    private $remote_plugin_url = 'http://costom-scripts.neetrino.net/Plugin/Neetrino.zip';
    private $plugin_slug = 'neetrino';
    
    /**
     * Конструктор класса
     */
    public function __construct() {
        // Убираем фильтр для проверки обновлений - больше не нужен
        // add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_plugin_update']);
        
        // Добавляем хук для отключения флага обновления после обновления плагина
        add_action('upgrader_process_complete', [$this, 'after_plugin_update'], 10, 2);
        
        // CRON system removed - only manual updates through dashboard buttons remain
        // Clear any existing cron events from previous system
        wp_clear_scheduled_hook('neetrino_check_update_event');
    }

    /**
     * Проверяет доступность файла обновления (для внутреннего использования)
     */
    public function check_file_availability() {
        // Проверяем доступность файла обновления
        $response = wp_remote_head($this->remote_plugin_url, [
            'timeout' => 5,
            'sslverify' => false
        ]);
        
        // Проверяем, есть ли файл обновления на сервере
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            return [
                'success' => true,
                'available' => true,
                'message' => 'Файл обновления доступен'
            ];
        } else {
            return [
                'success' => false,
                'available' => false,
                'message' => 'Файл обновления недоступен'
            ];
        }
    }

    /**
     * Выполняет прямое обновление плагина
     */
    public function perform_direct_update() {
        // Проверяем доступность файла
        $availability = $this->check_file_availability();
        if (!$availability['available']) {
            return [
                'success' => false,
                'message' => 'Обновление недоступно: ' . $availability['message']
            ];
        }

        // Получаем текущую версию плагина
        $plugin_data = get_plugin_data(NEETRINO_PLUGIN_FILE);
        $current_version = $plugin_data['Version'];
        
        try {
            // Создаем экземпляр WordPress Upgrader
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
            
            // Создаем upgrader
            $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
            
            // Выполняем обновление
            $result = $upgrader->upgrade($this->remote_plugin_url);
            
            if (is_wp_error($result)) {
                return [
                    'success' => false,
                    'message' => 'Ошибка обновления: ' . $result->get_error_message(),
                    'old_version' => $current_version
                ];
            }
            
            // Получаем новую версию после обновления
            $plugin_data_after = get_plugin_data(NEETRINO_PLUGIN_FILE);
            $new_version = $plugin_data_after['Version'];
            
            // Очищаем кэш плагинов
            wp_clean_plugins_cache();
            
            return [
                'success' => true,
                'message' => 'Плагин Neetrino успешно обновлен',
                'old_version' => $current_version,
                'new_version' => $new_version
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка обновления: ' . $e->getMessage(),
                'old_version' => $current_version
            ];
        }
    }

    /**
     * Обработчик после обновления плагина
     */
    public function after_plugin_update($upgrader, $hook_extra) {
        // Очищаем флаги обновления
        delete_transient('neetrino_update_available');
        delete_option('neetrino_update_info');
        
        // Логируем успешное обновление
        error_log('Neetrino Plugin: Обновление завершено успешно');
    }

    /**
     * Получает информацию о текущем плагине
     */
    public function get_plugin_info() {
        $plugin_data = get_plugin_data(NEETRINO_PLUGIN_FILE);
        return [
            'name' => $plugin_data['Name'],
            'version' => $plugin_data['Version'],
            'description' => $plugin_data['Description'],
            'author' => $plugin_data['Author']
        ];
    }
}

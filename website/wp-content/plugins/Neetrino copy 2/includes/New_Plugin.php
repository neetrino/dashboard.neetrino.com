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
        
        // Вычисляем новую версию
        $version_parts = explode('.', $current_version);
        $last_part = (int)array_pop($version_parts);
        $version_parts[] = $last_part + 1;
        $new_version = implode('.', $version_parts);
        
        // Сохраняем информацию об обновлении
        update_option('neetrino_update_info', [
            'current_version' => $current_version,
            'new_version' => $new_version,
            'package' => $this->remote_plugin_url
        ]);
        
        // Устанавливаем флаг обновления
        set_transient('neetrino_update_available', true, 12 * HOUR_IN_SECONDS);
        
        return [
            'success' => true,
            'message' => 'Обновление инициировано',
            'current_version' => $current_version,
            'new_version' => $new_version
        ];
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

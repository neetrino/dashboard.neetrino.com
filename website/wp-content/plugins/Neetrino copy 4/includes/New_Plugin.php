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
        error_log('Neetrino Plugin Updater: check_file_availability() вызван');
        error_log('Neetrino Plugin Updater: URL файла обновления: ' . $this->remote_plugin_url);
        
        // Проверяем доступность файла обновления
        error_log('Neetrino Plugin Updater: Выполняем wp_remote_head()');
        $response = wp_remote_head($this->remote_plugin_url, [
            'timeout' => 5,
            'sslverify' => false
        ]);
        
        error_log('Neetrino Plugin Updater: Результат wp_remote_head(): ' . json_encode($response));
        
        // Проверяем, есть ли файл обновления на сервере
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            error_log('Neetrino Plugin Updater: Файл обновления доступен (HTTP 200)');
            return [
                'success' => true,
                'available' => true,
                'message' => 'Файл обновления доступен'
            ];
        } else {
            if (is_wp_error($response)) {
                error_log('Neetrino Plugin Updater: Ошибка wp_remote_head(): ' . $response->get_error_message());
            } else {
                error_log('Neetrino Plugin Updater: HTTP код ответа: ' . wp_remote_retrieve_response_code($response));
            }
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
        error_log('Neetrino Plugin Updater: perform_direct_update() вызван');
        
        // Проверяем доступность файла
        error_log('Neetrino Plugin Updater: Проверяем доступность файла обновления');
        $availability = $this->check_file_availability();
        error_log('Neetrino Plugin Updater: Результат проверки доступности: ' . json_encode($availability));
        
        if (!$availability['available']) {
            error_log('Neetrino Plugin Updater: Файл обновления недоступен');
            return [
                'success' => false,
                'message' => 'Обновление недоступно: ' . $availability['message']
            ];
        }

        // Получаем текущую версию плагина
        if (!defined('NEETRINO_PLUGIN_FILE')) {
            error_log('Neetrino Plugin Updater: Константа NEETRINO_PLUGIN_FILE не определена');
            return [
                'success' => false,
                'message' => 'Константа NEETRINO_PLUGIN_FILE не определена'
            ];
        }
        
        error_log('Neetrino Plugin Updater: NEETRINO_PLUGIN_FILE = ' . NEETRINO_PLUGIN_FILE);
        
        $plugin_data = get_plugin_data(NEETRINO_PLUGIN_FILE);
        if (!$plugin_data) {
            error_log('Neetrino Plugin Updater: Не удалось получить данные плагина');
            return [
                'success' => false,
                'message' => 'Не удалось получить данные плагина'
            ];
        }
        
        $current_version = $plugin_data['Version'];
        
        try {
            error_log('Neetrino Plugin Updater: Текущая версия плагина: ' . $current_version);
            error_log('Neetrino Plugin Updater: URL для обновления: ' . $this->remote_plugin_url);
            
            // Создаем экземпляр WordPress Upgrader
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
            
            error_log('Neetrino Plugin Updater: Классы WordPress Upgrader загружены');
            
            // Создаем upgrader
            $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
            error_log('Neetrino Plugin Updater: Plugin_Upgrader создан');
            
            // Выполняем обновление
            error_log('Neetrino Plugin Updater: Вызываем upgrade() с URL: ' . $this->remote_plugin_url);
            $result = $upgrader->upgrade($this->remote_plugin_url);
            error_log('Neetrino Plugin Updater: Результат upgrade(): ' . json_encode($result));
            
            if (is_wp_error($result)) {
                error_log('Neetrino Plugin Updater: Ошибка обновления: ' . $result->get_error_message());
                return [
                    'success' => false,
                    'message' => 'Ошибка обновления: ' . $result->get_error_message(),
                    'old_version' => $current_version
                ];
            }
            
            error_log('Neetrino Plugin Updater: Обновление выполнено, результат: ' . $result);
            
            // Получаем новую версию после обновления
            $plugin_data_after = get_plugin_data(NEETRINO_PLUGIN_FILE);
            $new_version = $plugin_data_after['Version'];
            error_log('Neetrino Plugin Updater: Версия после обновления: ' . $new_version);
            
            // Очищаем кэш плагинов
            wp_clean_plugins_cache();
            error_log('Neetrino Plugin Updater: Кэш плагинов очищен');
            
            return [
                'success' => true,
                'message' => 'Плагин Neetrino успешно обновлен',
                'old_version' => $current_version,
                'new_version' => $new_version
            ];
            
        } catch (Exception $e) {
            error_log('Neetrino Plugin Updater: Исключение при обновлении: ' . $e->getMessage());
            error_log('Neetrino Plugin Updater: Стек вызовов: ' . $e->getTraceAsString());
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

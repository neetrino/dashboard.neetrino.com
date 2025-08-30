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
        // Добавляем фильтр для проверки обновлений
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_plugin_update']);
        
        // Добавляем хук для отключения флага обновления после обновления плагина
        add_action('upgrader_process_complete', [$this, 'after_plugin_update'], 10, 2);
        
        // CRON system removed - only manual updates through dashboard buttons remain
        // Clear any existing cron events from previous system
        wp_clear_scheduled_hook('neetrino_check_update_event');
    }

    /**
     * Проверяет доступность обновления плагина (ручная проверка)
     */
    public function check_update_availability() {
        // Проверяем доступность файла обновления
        $response = wp_remote_head($this->remote_plugin_url, [
            'timeout' => 5,
            'sslverify' => false
        ]);
        
        // Проверяем, есть ли файл обновления на сервере
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            // Файл доступен
            set_transient('neetrino_update_available', true, 12 * HOUR_IN_SECONDS);
            
            // Сохраняем текущую версию плагина
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
            
            // Устанавливаем простой флаг обновления
            // Объект с деталями обновления будет добавлен функцией check_for_plugin_update
            
            return [
                'success' => true,
                'message' => __('Update is available! Click "Update Now" to install it.', 'neetrino')
            ];
        } else {
            // Файл недоступен
            delete_transient('neetrino_update_available');
            delete_option('neetrino_update_info');
            
            return [
                'success' => false,
                'message' => __('No updates found or update server is not accessible.', 'neetrino')
            ];
        }
    }
    
    /**
     * Вызывается после завершения обновления плагина
     */
    public function after_plugin_update($upgrader, $options) {
        // Проверяем, что это обновление плагина
        if ($options['action'] == 'update' && $options['type'] == 'plugin') {
            // Получаем имя нашего плагина
            $plugin_slug = plugin_basename(NEETRINO_PLUGIN_FILE);
            
            // Проверяем, был ли обновлен наш плагин
            if (isset($options['plugins']) && in_array($plugin_slug, $options['plugins'])) {
                // Получаем текущую версию плагина
                $plugin_data = get_plugin_data(NEETRINO_PLUGIN_FILE);
                $current_version = $plugin_data['Version'];
                
                // Получаем сохраненную версию
                $saved_version = get_option('neetrino_current_version', '');
                
                // Если версия изменилась, сбрасываем флаг обновления
                if ($current_version != $saved_version) {
                    delete_transient('neetrino_update_available');
                    delete_option('neetrino_current_version');
                    // Пушим новую версию на Dashboard сразу после обновления
                    if (class_exists('Neetrino_Registration') && method_exists('Neetrino_Registration', 'push_version_if_changed')) {
                        Neetrino_Registration::push_version_if_changed(true);
                    }
                }
            }
        }
    }
    
    /**
     * Добавляет плагин в список обновлений WordPress
     */
    public function check_for_plugin_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Проверяем наличие флага обновления или информации об обновлении
        $update_info = get_option('neetrino_update_info');
        $update_available = get_transient('neetrino_update_available');
        
        // Если есть флаг обновления или информация об обновлении
        if ($update_available && $update_info) {
            $plugin_slug = plugin_basename(NEETRINO_PLUGIN_FILE);
            
            // Создаем объект с информацией об обновлении
            $obj = new stdClass();
            $obj->slug = $this->plugin_slug;
            $obj->plugin = $plugin_slug;
            $obj->new_version = $update_info['new_version'];
            $obj->url = 'http://plugin.neetrino.net/';
            $obj->package = $update_info['package'];
            
            // Добавляем в список обновлений WordPress
            $transient->response[$plugin_slug] = $obj;
        }
        
        return $transient;
    }
}

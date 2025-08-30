<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс для автоматического обновления плагина
 * Проверяет наличие плагина на сервере и автоматически его устанавливает
 */
class Neetrino_Auto_Updater {
    private $remote_plugin_url = 'http://costom-scripts.neetrino.net/Plugin/Neetrino.zip';
    private $plugin_slug = 'neetrino';
    private $check_interval = 54000; // 15 часов в секундах (15 * 60 * 60)
    
    /**
     * Конструктор класса
     */
    public function __construct() {
        // Регистрируем событие для проверки обновлений
        add_action('init', [$this, 'schedule_update_check']);
        add_action('init', [$this, 'maybe_schedule_cron']); // Автоматическая перерегистрация
        add_action('neetrino_auto_update_check', [$this, 'check_and_update']);
        
        // Добавляем интервал в 15 часов
        add_filter('cron_schedules', function($schedules) {
            $schedules['neetrino_daily'] = [
                'interval' => $this->check_interval,
                'display' => __('Every 15 Hours', 'neetrino')
            ];
            return $schedules;
        });
    }

    /**
     * Планирует регулярную проверку обновлений
     */
    public function schedule_update_check() {
        // Планируем событие, если оно еще не запланировано
        if (!wp_next_scheduled('neetrino_auto_update_check')) {
            $result = wp_schedule_event(time(), 'neetrino_daily', 'neetrino_auto_update_check');
            if ($result === false) {
                error_log('Neetrino Auto Update: Ошибка при регистрации cron события');
            } else {
                error_log('Neetrino Auto Update: Cron событие успешно зарегистрировано (каждые 15 часов)');
            }
        }
    }
    
    /**
     * Обеспечивает регистрацию cron при каждой загрузке (если его нет)
     */
    public function maybe_schedule_cron() {
        if (!wp_next_scheduled('neetrino_auto_update_check')) {
            $this->schedule_update_check();
        }
    }
    
    /**
     * Проверяет наличие плагина на сервере и запускает обновление
     */
    public function check_and_update() {
        // Логируем начало проверки
        error_log('Neetrino Auto Update: Начало проверки обновлений');
        
        // Проверяем доступность файла обновления
        $response = wp_remote_head($this->remote_plugin_url, [
            'timeout' => 5,
            'sslverify' => false
        ]);
        
        // Если файл недоступен, прерываем процесс
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            error_log('Neetrino Auto Update: Файл обновления недоступен');
            return;
        }
        
        // Файл доступен, запускаем процесс обновления
        error_log('Neetrino Auto Update: Файл обновления найден, начинаем установку');
        $this->update_plugin();
    }
    
    /**
     * Выполняет обновление плагина
     */
    private function update_plugin() {
        // Подключаем необходимые файлы для обновления
        require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        
        // Создаем тихий скин для апгрейдера
        $skin = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader($skin);
        
        // Отключаем проверки на совместимость
        add_filter('upgrader_package_options', function($options) {
            $options['abort_if_destination_exists'] = false;
            $options['hook_extra']['plugin'] = plugin_basename(NEETRINO_PLUGIN_FILE);
            return $options;
        });
        
        // Получаем путь к файлу плагина
        $plugin_file = plugin_basename(NEETRINO_PLUGIN_FILE);
        
        // Очищаем кэш обновлений
        wp_clean_plugins_cache(false);
        
        // Временно деактивируем плагин
        deactivate_plugins($plugin_file, true);
        
        // Выполняем установку напрямую из URL
        $result = $upgrader->install($this->remote_plugin_url, ['overwrite_package' => true]);
        
        // Активируем плагин снова
        activate_plugin($plugin_file);
        
        // Логируем результат установки
        if (!is_wp_error($result)) {
            error_log('Neetrino Auto Update: Плагин успешно обновлен');
        } else {
            error_log('Neetrino Auto Update: Ошибка при обновлении: ' . $result->get_error_message());
        }
    }
}

// Инициализация класса
new Neetrino_Auto_Updater();
<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Подключаем необходимые файлы
require_once plugin_dir_path(__FILE__) . 'class-data-collector.php';
require_once plugin_dir_path(__FILE__) . 'class-admin-ui.php';
require_once plugin_dir_path(__FILE__) . 'class-api.php';



/**
 * Основной класс плагина Bitrix24
 */
class WPBitrixSync {
    const OPTION_NAME = 'wp_bitrix_sync_options';
    const CRON_HOOK   = 'wp_bitrix_sync_daily_check';
    const ENCRYPTION_KEY_OPTION = 'wp_bitrix_sync_encryption_key';
    const WEBHOOK_SALT_OPTION = 'wp_bitrix_sync_webhook_salt';

    /**
     * @var WPBitrixDataCollector Экземпляр класса для сбора данных
     */
    private $data_collector;

    /**
     * @var WPBitrixAdminUI Экземпляр класса для управления админкой
     */
    private $admin_ui;

    /**
     * @var WPBitrixAPI Экземпляр класса для работы с API
     */
    private $api;

    public function __construct() {
        // Инициализация компонентов
        $this->data_collector = new WPBitrixDataCollector();
        $this->admin_ui = new WPBitrixAdminUI();
        $this->api = new WPBitrixAPI();

        // Хуки активации/деактивации
        register_activation_hook(plugin_dir_path(__DIR__) . 'neetrino.php', [$this, 'activate']);
        register_deactivation_hook(plugin_dir_path(__DIR__) . 'neetrino.php', [$this, 'deactivate']);

        // Регистрируем cron событие при каждой загрузке (если его нет)
        add_action('init', [$this, 'maybe_schedule_cron']);

        // Ежедневное событие
        add_action(self::CRON_HOOK, [$this, 'cron_check_day']);

        // Menu is automatically added by Neetrino system

        // Регистрация настроек
        add_action('admin_init', [$this->admin_ui, 'register_settings']);

        // Обработчик ручного запуска
        add_action('admin_post_wp_bitrix_manual_send', [$this, 'manual_send_handler']);
        
        // Обработчик сброса статуса синхронизации
        add_action('admin_post_wp_bitrix_reset_status', [$this, 'reset_status_handler']);
        
        // Добавляем фильтр для шифрования вебхука перед сохранением
        add_filter('pre_update_option_' . self::OPTION_NAME, [$this, 'encrypt_webhook_before_save'], 10, 2);
    }
    
    /**
     * Проверяет и регистрирует cron событие при необходимости
     */
    public function maybe_schedule_cron() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            $this->schedule_cron_event();
        }
    }

    /**
     * Активация плагина
     */
    public function activate() {
        $this->schedule_cron_event();

        // Генерируем ключ шифрования, если его нет
        if (!get_option(self::ENCRYPTION_KEY_OPTION)) {
            update_option(self::ENCRYPTION_KEY_OPTION, bin2hex(openssl_random_pseudo_bytes(32)));
        }
        
        // Генерируем соль для вебхука, если её нет
        if (!get_option(self::WEBHOOK_SALT_OPTION)) {
            update_option(self::WEBHOOK_SALT_OPTION, wp_generate_password(64, true, true));
        }

        // Set default options if they don't exist
        $current_options = get_option(self::OPTION_NAME);
        if (!$current_options) {
            $default_options = array(
                'send_day' => 1,
                'webhook_url' => '', // Пустой вебхук для ручного ввода
                'entity_type_id' => '1226',
                'item_name' => parse_url(home_url(), PHP_URL_HOST)
            );
            update_option(self::OPTION_NAME, $default_options);
        }
    }
    
    /**
     * Регистрация cron события
     */
    public function schedule_cron_event() {
        // Очищаем старые события
        wp_clear_scheduled_hook(self::CRON_HOOK);
        
        // Регистрируем новое событие
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            $result = wp_schedule_event(time(), 'daily', self::CRON_HOOK);
            if ($result === false) {
                error_log('Bitrix24: Ошибка при регистрации cron события');
            } else {
                error_log('Bitrix24: Cron событие успешно зарегистрировано');
            }
        }
    }

    /**
     * Деактивация плагина
     */
    public function deactivate() {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    /**
     * Ежемесячная проверка и отправка данных
     * Начинает попытки отправки с указанного дня и продолжает каждый день до успешной отправки
     */
    public function cron_check_day() {
        $options = get_option(self::OPTION_NAME);
        $day_to_send = isset($options['send_day']) ? intval($options['send_day']) : 1;
        $current_day = intval(current_time('j'));
        
        // Логирование для отладки
        error_log("Bitrix24 Cron: текущий день = {$current_day}, день начала отправки = {$day_to_send}");

        // Проверяем, нужна ли отправка в этом месяце
        if (!$this->should_send_this_month()) {
            error_log("Bitrix24 Cron: отправка не нужна - уже была успешная отправка в этом месяце");
            return;
        }

        // Если сегодня >= дня начала отправки, пытаемся отправить
        if ($current_day >= $day_to_send) {
            error_log("Bitrix24 Cron: пытаемся отправить данные (день {$current_day} >= дня начала {$day_to_send})");
            
            if ($this->trigger_send(false)) {
                error_log("Bitrix24 Cron: отправка выполнена успешно");
            } else {
                error_log("Bitrix24 Cron: отправка не выполнена, попробуем завтра");
            }
        } else {
            error_log("Bitrix24 Cron: еще рано для отправки (день {$current_day} < дня начала {$day_to_send})");
        }
    }

    /**
     * Обработчик ручного запуска из админ-панели
     */
    public function manual_send_handler() {
        if (!current_user_can('manage_options')) {
            wp_die('У вас нет прав для этого действия.');
        }

        check_admin_referer('wp_bitrix_manual_send');
        // Ручная отправка всегда принудительная
        $this->trigger_send(true);
        wp_redirect(admin_url('admin.php?page=neetrino_bitrix24&manual_send=success'));
        exit;
    }

    /**
     * Обработчик сброса статуса синхронизации
     */
    public function reset_status_handler() {
        if (!current_user_can('manage_options')) {
            wp_die('У вас нет прав для этого действия.');
        }

        check_admin_referer('wp_bitrix_reset_status');
        
        // Сбрасываем статус через публичный метод
        $result = $this->reset_sync_status();
        
        if ($result) {
            wp_redirect(admin_url('admin.php?page=neetrino_bitrix24&reset_status=success'));
        } else {
            wp_redirect(admin_url('admin.php?page=neetrino_bitrix24&reset_status=error'));
        }
        exit;
    }

    // Функция set_defaults_handler удалена

    /**
     * Основная функция отправки данных в Bitrix24
     */
    private function send_data_to_bitrix($manual = false) {
        // Собираем данные
        $data = $this->data_collector->collect_all_data();
        
        // Отправляем данные через API
        $result = $this->api->send_data_to_bitrix($data, $manual);
        
        // Записываем статус отправки
        $current_time = current_time('mysql');
        if ($result) {
            update_option('neetrino_bitrix24_last_send_date', $current_time);
            update_option('neetrino_bitrix24_last_send_status', 'success');
            // Вычисляем дату следующей отправки (первое число следующего месяца)
            $next_month = date('Y-m-01', strtotime('first day of next month'));
            update_option('neetrino_bitrix24_next_send_date', $next_month);
        } else {
            update_option('neetrino_bitrix24_last_send_date', $current_time);
            update_option('neetrino_bitrix24_last_send_status', 'error');
        }
        
        return $result;
    }
    
    /**
     * Проверяет, нужна ли отправка данных в этом месяце
     * @return bool true если отправка нужна, false если уже была
     */
    private function should_send_this_month() {
        $last_send_date = get_option('neetrino_bitrix24_last_send_date');
        $last_send_status = get_option('neetrino_bitrix24_last_send_status');
        
        if (empty($last_send_date)) {
            return true; // Никогда не отправлялось
        }
        
        // Проверяем, что последняя отправка была успешной
        if ($last_send_status !== 'success') {
            return true; // Последняя отправка была неудачной, нужно попробовать снова
        }
        
        $last_send_month = date('Y-m', strtotime($last_send_date));
        $current_month = date('Y-m');
        
        return $last_send_month !== $current_month;
    }
    
    /**
     * Публичный метод для запуска отправки из внешних модулей
     * @param bool $force Принудительная отправка (игнорировать проверку месяца)
     * @return bool Результат отправки
     */
    public function trigger_send($force = false) {
        error_log("Bitrix24: trigger_send вызван с force=" . ($force ? 'true' : 'false'));
        
        // Если принудительная отправка или нужна отправка в этом месяце
        if ($force || $this->should_send_this_month()) {
            if ($force) {
                error_log("Bitrix24: Принудительная отправка данных");
            } else {
                error_log("Bitrix24: Отправка разрешена - нужна отправка в этом месяце");
            }
            return $this->send_data_to_bitrix($force);
        }
        
        // Отправка не нужна - уже была в этом месяце
        error_log("Bitrix24: Отправка отклонена - уже была успешная отправка в этом месяце");
        return false;
    }
    
    /**
     * Шифрует вебхук для безопасного хранения
     * @param string $webhook_url URL вебхука
     * @return string Зашифрованный вебхук
     */
    public function encrypt_webhook($webhook_url) {
        if (empty($webhook_url)) {
            return '';
        }
        
        $encryption_key = get_option(self::ENCRYPTION_KEY_OPTION);
        $salt = get_option(self::WEBHOOK_SALT_OPTION);
        
        if (!$encryption_key || !$salt) {
            return $webhook_url; // Возвращаем без шифрования, если нет ключей
        }

        // Создаем initialization vector (IV)
        $iv = openssl_random_pseudo_bytes(16);
        
        // Шифруем URL вебхука
        $encrypted = openssl_encrypt(
            $webhook_url,
            'AES-256-CBC',
            hex2bin($encryption_key),
            0,
            $iv
        );
        
        if ($encrypted === false) {
            return '';
        }
        
        // Сохраняем IV вместе с зашифрованным текстом
        return base64_encode($iv . base64_decode($encrypted));
    }
    
    /**
     * Расшифровывает вебхук
     * @param string $encrypted_webhook Зашифрованный вебхук
     * @return string Расшифрованный URL вебхука
     */
    public function decrypt_webhook($encrypted_webhook) {
        if (empty($encrypted_webhook)) {
            return '';
        }
        
        $encryption_key = get_option(self::ENCRYPTION_KEY_OPTION);
        $salt = get_option(self::WEBHOOK_SALT_OPTION);
        
        if (!$encryption_key || !$salt) {
            return $encrypted_webhook; // Возвращаем без расшифровки, если нет ключей
        }
        
        $decoded = base64_decode($encrypted_webhook);
        if ($decoded === false) {
            return '';
        }
        
        // Извлекаем IV (первые 16 байт)
        $iv = substr($decoded, 0, 16);
        $encrypted_data = substr($decoded, 16);
        
        // Расшифровываем
        $decrypted = openssl_decrypt(
            base64_encode($encrypted_data),
            'AES-256-CBC',
            hex2bin($encryption_key),
            0,
            $iv
        );
        
        return $decrypted !== false ? $decrypted : '';
    }

    /**
     * Получает вебхук из настроек и расшифровывает его при необходимости
     * @return string URL вебхука
     */
    /**
     * Проверяет, установлен ли вебхук
     * @return bool true если вебхук установлен, false - если нет
     */
    public function has_webhook() {
        $options = get_option(self::OPTION_NAME);
        $encrypted_webhook = isset($options['webhook_url']) ? $options['webhook_url'] : '';
        
        return !empty($encrypted_webhook);
    }

    /**
     * Получает вебхук из настроек и расшифровывает его при необходимости
     * Максимальный уровень защиты - вебхук нельзя просмотреть в интерфейсе
     * 
     * @param bool $internal_only Если true, этот метод можно использовать только внутри системы
     * @return string URL вебхука или пустая строка
     */
    public function get_webhook_url($internal_only = true) {
        // Проверка, что метод вызывается только изнутри системы
        if (!$internal_only) {
            error_log('Attempt to access webhook externally blocked for security reasons');
            return '';
        }
        
        $options = get_option(self::OPTION_NAME);
        $encrypted_webhook = isset($options['webhook_url']) ? $options['webhook_url'] : '';
        
        // Проверяем, зашифрован ли вебхук (проверка на base64)
        if (!empty($encrypted_webhook) && base64_decode($encrypted_webhook, true) !== false) {
            return $this->decrypt_webhook($encrypted_webhook);
        }
        
        // Если вебхук не зашифрован, возвращаем его для последующего шифрования
        return $encrypted_webhook;
    }

    /**
     * Шифрует вебхук перед сохранением в базе данных
     * @param mixed $new_value Новое значение опции
     * @param mixed $old_value Старое значение опции
     * @return mixed Зашифрованное значение опции
     */
    public function encrypt_webhook_before_save($new_value, $old_value) {
        // Проверяем действие с вебхуком
        if (isset($new_value['webhook_url_action'])) {
            // Если действие - сохранить существующий вебхук
            if ($new_value['webhook_url_action'] === 'keep_existing' && isset($old_value['webhook_url'])) {
                // Сохраняем старое значение вебхука
                $new_value['webhook_url'] = $old_value['webhook_url'];
            } 
            // Если действие - заменить вебхук
            else if ($new_value['webhook_url_action'] === 'replace' && !empty($new_value['webhook_url'])) {
                // Шифруем новое значение вебхука
                $new_value['webhook_url'] = $this->encrypt_webhook($new_value['webhook_url']);
            }
            // Если действие - новый вебхук
            else if ($new_value['webhook_url_action'] === 'new' && !empty($new_value['webhook_url'])) {
                // Шифруем новое значение вебхука
                $new_value['webhook_url'] = $this->encrypt_webhook($new_value['webhook_url']);
            }
            // Если действие - удалить вебхук
            else if ($new_value['webhook_url_action'] === 'delete') {
                // Устанавливаем пустое значение для вебхука
                $new_value['webhook_url'] = '';
            }
            // Если не указан новый вебхук при замене, сохраняем старый
            else if ($new_value['webhook_url_action'] === 'replace' && empty($new_value['webhook_url']) && isset($old_value['webhook_url'])) {
                $new_value['webhook_url'] = $old_value['webhook_url'];
            }
            
            // Удаляем служебное поле из сохраняемых настройек
            unset($new_value['webhook_url_action']);
        } 
        // Случай, если поле webhook_url_action не передано (обратная совместимость)
        else if (isset($new_value['webhook_url']) && isset($old_value['webhook_url']) && $new_value['webhook_url'] !== $old_value['webhook_url']) {
            // Шифруем новое значение вебхука только если оно не пустое
            if (!empty($new_value['webhook_url'])) {
                $new_value['webhook_url'] = $this->encrypt_webhook($new_value['webhook_url']);
            }
        }
        
        return $new_value;
    }

    /**
     * Статический метод для отображения админ-страницы
     * Требуется для совместимости с системой Neetrino
     */
    public static function admin_page() {
        // Создаем экземпляр админ UI и отображаем страницу
        $admin_ui = new WPBitrixAdminUI();
        $admin_ui->settings_page_html();
    }
    
    /**
     * Получает информацию о состоянии cron
     * @return array Информация о cron событии
     */
    public function get_cron_info() {
        $next_scheduled = wp_next_scheduled(self::CRON_HOOK);
        $options = get_option(self::OPTION_NAME);
        $day_to_send = isset($options['send_day']) ? intval($options['send_day']) : 1;
        $current_day = intval(current_time('j'));
        
        return array(
            'is_scheduled' => (bool) $next_scheduled,
            'next_run' => $next_scheduled ? date('Y-m-d H:i:s', $next_scheduled) : 'Не запланировано',
            'next_run_timestamp' => $next_scheduled,
            'current_time' => date('Y-m-d H:i:s'),
            'current_timestamp' => time(),
            'send_day' => $day_to_send,
            'current_day' => $current_day,
            'will_run_today' => ($current_day >= $day_to_send && $this->should_send_this_month()),
            'cron_hook' => self::CRON_HOOK,
            'wp_cron_disabled' => (defined('DISABLE_WP_CRON') && constant('DISABLE_WP_CRON'))
        );
    }
    
    /**
     * Принудительно запускает cron задачу для тестирования
     * @return array Результат выполнения
     */
    public function test_cron_execution() {
        $start_time = microtime(true);
        
        try {
            error_log('Bitrix24: Принудительный запуск cron задачи для тестирования');
            $this->cron_check_day();
            
            $execution_time = microtime(true) - $start_time;
            
            return array(
                'success' => true,
                'message' => 'Cron задача выполнена успешно',
                'execution_time' => round($execution_time, 4) . ' сек',
                'timestamp' => date('Y-m-d H:i:s')
            );
            
        } catch (Exception $e) {
            $execution_time = microtime(true) - $start_time;
            
            return array(
                'success' => false,
                'message' => 'Ошибка при выполнении cron задачи: ' . $e->getMessage(),
                'execution_time' => round($execution_time, 4) . ' сек',
                'timestamp' => date('Y-m-d H:i:s')
            );
        }
    }
    
    /**
     * Сбрасывает и перерегистрирует cron событие
     * @return array Результат операции
     */
    public function reset_cron() {
        try {
            // Очищаем старое событие
            wp_clear_scheduled_hook(self::CRON_HOOK);
            
            // Регистрируем новое
            $this->schedule_cron_event();
            
            return array(
                'success' => true,
                'message' => 'Cron событие успешно перерегистрировано',
                'info' => $this->get_cron_info()
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Ошибка при перерегистрации cron: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Публичный метод для сброса статуса синхронизации
     * Сбрасывает информацию о последней отправке, делая возможной новую отправку
     * @return bool Результат операции
     */
    public function reset_sync_status() {
        try {
            delete_option('neetrino_bitrix24_last_send_date');
            delete_option('neetrino_bitrix24_last_send_status');
            delete_option('neetrino_bitrix24_next_send_date');
            
            error_log('Bitrix24: Статус синхронизации успешно сброшен');
            return true;
        } catch (Exception $e) {
            error_log('Bitrix24: Ошибка при сбросе статуса синхронизации: ' . $e->getMessage());
            return false;
        }
    }
}

// Инициализация плагина
$wp_bitrix_sync = new WPBitrixSync();

// Делаем экземпляр доступным глобально
global $neetrino_bitrix24_instance;
$neetrino_bitrix24_instance = $wp_bitrix_sync;

/**
 * Класс для интеграции с системой меню Neetrino
 */
class Neetrino_Bitrix24 {
    /**
     * Метод для отображения админ-интерфейса
     */
    public static function admin_page() {
        // Используем статический метод из основного класса
        WPBitrixSync::admin_page();
    }
}
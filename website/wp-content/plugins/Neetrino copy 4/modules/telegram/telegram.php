<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include required files
require_once plugin_dir_path(__FILE__) . 'telegram-api.php';
require_once plugin_dir_path(__FILE__) . 'telegram-admin.php';
require_once plugin_dir_path(__FILE__) . 'token-security.php';
require_once plugin_dir_path(__FILE__) . 'token-ajax-handlers.php';

/**
 * Telegram Module
 * Main class for WooCommerce order notifications to Telegram
 */
class Telegram {
    
    const OPTION_NAME = 'telegram_settings';
    const CHATS_DATA_OPTION = 'telegram_chats_data';
    private $bot_token;
    private $chat_ids;
    private $api;
    private static $instance = null;
    private $hooks_initialized = false;    public function __init() {
        // Защита от повторной инициализации
        if ($this->hooks_initialized) {
            return;
        }
        
        // Load settings
        $this->load_settings();
        
        // Initialize API class
        $this->api = new Telegram_API($this->bot_token, $this->chat_ids);
        
        // Дополнительная проверка - убеждаемся что хуки еще не добавлены
        if (!has_action('woocommerce_checkout_order_processed', array($this, 'send_order_notification'))) {
            add_action('woocommerce_checkout_order_processed', array($this, 'send_order_notification'));
        }
        
        // Добавляем проверку на реальное изменение статуса
        if (!has_action('woocommerce_order_status_changed', array($this, 'send_status_notification'))) {
            add_action('woocommerce_order_status_changed', array($this, 'send_status_notification'), 10, 4);
        }
        
        // Add admin settings registration
        if (!has_action('admin_init', array($this, 'register_settings'))) {
            add_action('admin_init', array($this, 'register_settings'));
        }
        
        // Initialize admin assets with simple approach like other working modules
        if (!has_action('admin_enqueue_scripts', array($this, 'enqueue_telegram_admin_styles'))) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_telegram_admin_styles'));
        }
        
        // Отмечаем что хуки уже инициализированы
        $this->hooks_initialized = true;
        
        // Добавляем ежедневную очистку старых транзиентов
        if (!wp_next_scheduled('telegram_cleanup_old_transients')) {
            wp_schedule_event(time(), 'daily', 'telegram_cleanup_old_transients');
        }
        if (!has_action('telegram_cleanup_old_transients', array($this, 'cleanup_old_transients'))) {
            add_action('telegram_cleanup_old_transients', array($this, 'cleanup_old_transients'));
        }
    }
    
    /**
     * Refresh settings and update API class with new token/chat_ids
     */
    public function refresh_settings() {
        $this->load_settings();
        if ($this->api) {
            $this->api->set_bot_token($this->bot_token);
            $this->api->set_chat_ids($this->chat_ids);
        }
    }
    
    /**
     * Check if notification was already sent recently to prevent duplicates
     */
    private function is_notification_sent($key, $expiry = 300) {
        return get_transient($key) !== false;
    }
    
    /**
     * Mark notification as sent
     */
    private function mark_notification_sent($key, $expiry = 300) {
        set_transient($key, time(), $expiry);
    }
    
    /**
     * Cleanup old notification transients
     */
    public function cleanup_old_transients() {
        global $wpdb;
        
        // Удаляем транзиенты старше 24 часов
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_timeout_telegram_%' 
             AND option_value < UNIX_TIMESTAMP()"
        );
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_telegram_%' 
             AND option_name NOT IN (
                 SELECT CONCAT('_transient_', SUBSTRING(option_name, 19)) 
                 FROM {$wpdb->options} o2 
                 WHERE o2.option_name LIKE '_transient_timeout_telegram_%'
             )"
        );
    }
      /**
     * Load settings from database
     */
    private function load_settings() {
        $settings = get_option(self::OPTION_NAME, array());
        
        // Сначала проверяем зашифрованный токен
        $this->bot_token = TelegramTokenSecurity::get_decrypted_token();
        
        // Если зашифрованного токена нет, проверяем старые настройки
        if (empty($this->bot_token)) {
            $this->bot_token = isset($settings['bot_token']) ? $settings['bot_token'] : '';
        }
        
        $chat_ids_raw = isset($settings['chat_ids']) ? $settings['chat_ids'] : '';
        
        // Преобразуем строку с Chat ID в массив
        if (!empty($chat_ids_raw)) {
            $this->chat_ids = array_map('trim', explode(',', $chat_ids_raw));
            // Убираем пустые элементы
            $this->chat_ids = array_filter($this->chat_ids);
        } else {
            $this->chat_ids = array();
        }
    }
      /**
     * Send notification when new order is created
     */
    public function send_order_notification($order_id) {
        // Проверяем, не отправляли ли мы уже уведомление для этого заказа
        $notification_key = 'telegram_order_notification_' . $order_id;
        if ($this->is_notification_sent($notification_key)) {
            error_log("Telegram: Duplicate order notification prevented for order #$order_id");
            return; // Уведомление уже отправлено в течение последних 5 минут
        }
        
        // Обновляем настройки перед отправкой
        $this->refresh_settings();
        
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log("Telegram: Order #$order_id not found");
            return;
        }
        
        $message = $this->api->format_order_message($order);
        $result = $this->api->send_telegram_message($message);
        
        // Если отправка успешна, сохраняем транзиент на 5 минут
        if ($result) {
            $this->mark_notification_sent($notification_key, 5 * MINUTE_IN_SECONDS);
            error_log("Telegram: Order notification sent for order #$order_id");
        } else {
            error_log("Telegram: Failed to send order notification for order #$order_id");
        }
    }

    /**
     * Send notification when order status changes
     */
    public function send_status_notification($order_id, $old_status, $new_status, $order) {
        // Проверяем, не отправляли ли мы уже уведомление об изменении статуса
        $notification_key = 'telegram_status_notification_' . $order_id . '_' . $old_status . '_' . $new_status;
        if ($this->is_notification_sent($notification_key)) {
            error_log("Telegram: Duplicate status notification prevented for order #$order_id ($old_status -> $new_status)");
            return; // Уведомление уже отправлено в течение последних 5 минут
        }
        
        // Обновляем настройки перед отправкой
        $this->refresh_settings();
        
        // Игнорируем изменения статуса при создании заказа
        if ($old_status === 'pending' && $new_status === 'processing') {
            error_log("Telegram: Ignoring pending->processing status change for order #$order_id (normal order creation)");
            return; // Это обычное изменение при создании заказа
        }
        
        // Игнорируем если это не реальное изменение статуса
        if ($old_status === $new_status) {
            error_log("Telegram: Ignoring same status change for order #$order_id ($old_status -> $new_status)");
            return;
        }
        
        if (!$order) {
            $order = wc_get_order($order_id);
        }
        
        if (!$order) {
            error_log("Telegram: Order #$order_id not found for status change notification");
            return;
        }

        $message = $this->api->format_status_change_message($order_id, $old_status, $new_status, $order);
        $result = $this->api->send_telegram_message($message);
        
        // Если отправка успешна, сохраняем транзиент на 5 минут
        if ($result) {
            $this->mark_notification_sent($notification_key, 5 * MINUTE_IN_SECONDS);
            error_log("Telegram: Status change notification sent for order #$order_id ($old_status -> $new_status)");
        } else {
            error_log("Telegram: Failed to send status change notification for order #$order_id ($old_status -> $new_status)");
        }
    }

    /**
     * Static method for Neetrino admin integration
     */
    public static function admin_page() {
        return Telegram_Admin::admin_page();
    }
    
    /**
     * Force cleanup all telegram notification transients (for debugging)
     */
    public static function force_cleanup_notifications() {
        global $wpdb;
        
        $count = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient%telegram_%notification%'"
        );
        
        error_log("Telegram: Force cleanup removed $count notification transients");
        return $count;
    }
      /**
     * Register settings
     */
    public function register_settings() {
        register_setting('telegram_group', self::OPTION_NAME);
    }    /**
     * Enqueue admin styles and scripts - simple approach like other working modules
     */
    public function enqueue_telegram_admin_styles($hook) {
        if (strpos($hook, 'neetrino') !== false) {
            wp_enqueue_style('dashicons');
              $plugin_url = plugin_dir_url(__FILE__);
            $version = '1.0.8.1.' . time(); // Add timestamp for cache busting
              // Load the telegram CSS file
            wp_enqueue_style(
                'telegram-admin-styles',
                $plugin_url . 'admin-styles.css',
                array('dashicons'),
                $version
            );
            
            // Load the telegram JavaScript file
            wp_enqueue_script(
                'telegram-admin-scripts',
                $plugin_url . 'admin-scripts.js',
                array('jquery'),
                $version,
                true
            );
            
            // Pass data to JavaScript
            wp_localize_script('telegram-admin-scripts', 'telegramAdminAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('telegram_token_nonce'),
                'isTokenSaved' => TelegramTokenSecurity::is_token_set(),
                'pluginUrl' => $plugin_url
            ));
        }
    }
}

// Создаем класс с именем, которое ожидает система Neetrino
class Neetrino_Telegram {
    public static function admin_page() {
        return Telegram::admin_page();
    }
}

// Initialize the module and make it globally accessible
global $neetrino_telegram_instance;

// Проверяем, что экземпляр еще не создан
if (!isset($neetrino_telegram_instance) || !$neetrino_telegram_instance) {
    $neetrino_telegram_instance = new Telegram();
    $neetrino_telegram_instance->__init();
}

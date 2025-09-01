<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Telegram Admin Interface and AJAX Handlers
 * Handles admin settings page and all AJAX operations
 */
class Telegram_Admin {
    
    const OPTION_NAME = 'telegram_settings';
    const CHATS_DATA_OPTION = 'telegram_chats_data';
    
    /**
     * Static method for Neetrino admin integration
     */
    public static function admin_page() {
        // Создаем временный экземпляр для отображения страницы
        $instance = new self();
        $instance->load_settings();
        $instance->render_admin_page();
    }
    
    /**
     * Load settings from database
     */
    private function load_settings() {
        $this->settings = get_option(self::OPTION_NAME, array());
        $this->bot_token = isset($this->settings['bot_token']) ? $this->settings['bot_token'] : '';
        $this->chat_ids_raw = isset($this->settings['chat_ids']) ? $this->settings['chat_ids'] : '';
    }
    
    /**
     * Render admin page for Neetrino integration with modern design
     */
    public function render_admin_page() {
        // Register settings if not already done
        $this->register_settings();
        $settings = get_option(self::OPTION_NAME, array());
        $bot_token = isset($settings['bot_token']) ? $settings['bot_token'] : '';
        $chat_ids_raw = isset($settings['chat_ids']) ? $settings['chat_ids'] : '';
        
        // Load stored chats data
        $stored_chats_data = get_option(self::CHATS_DATA_OPTION, array());
        
        // Parse existing chat IDs into structured data
        $existing_chats = array();
        if (!empty($chat_ids_raw)) {
            $chat_ids_array = array_map('trim', explode(',', $chat_ids_raw));
            $chat_ids_array = array_filter($chat_ids_array);
            
            foreach ($chat_ids_array as $chat_id) {
                // Try to get full data from stored chats data
                if (isset($stored_chats_data[$chat_id])) {
                    $existing_chats[] = $stored_chats_data[$chat_id];
                } else {
                    // Fallback to basic data
                    $existing_chats[] = array(
                        'id' => $chat_id,
                        'type' => 'unknown',
                        'title' => '',
                        'username' => '',
                        'first_name' => '',
                        'last_name' => ''
                    );
                }
            }
        }
        
        // Prepare template variables
        $template_vars = array(
            'bot_token' => $bot_token,
            'chat_ids_raw' => $chat_ids_raw,
            'existing_chats' => $existing_chats,
            'option_name' => self::OPTION_NAME,
            'admin_url' => admin_url('admin-ajax.php')
        );
          // Include the external admin template
        $template_path = plugin_dir_path(__FILE__) . 'admin-template-html.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Fallback if template file doesn't exist
            echo '<div class="notice notice-error"><p><strong>Ошибка:</strong> Файл шаблона админки не найден: ' . esc_html($template_path) . '</p></div>';
        }
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('telegram_group', self::OPTION_NAME);
    }
    
    /**
     * Initialize admin hooks and enqueue assets
     */
    public static function init_admin_assets() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
    }    /**
     * Enqueue admin styles and scripts
     */
    public static function enqueue_admin_assets($hook) {
        // Всегда подключаем ресурсы в админке для максимальной совместимости
        if (!is_admin()) {
            return;
        }
        
        // Дополнительная проверка для специфических страниц
        $current_screen = get_current_screen();
        $is_telegram_page = (
            strpos($hook, 'neetrino') !== false ||
            strpos($hook, 'telegram') !== false ||
            (isset($_GET['page']) && strpos($_GET['page'], 'telegram') !== false) ||
            (isset($_GET['module']) && $_GET['module'] === 'telegram') ||
            ($current_screen && strpos($current_screen->id, 'telegram') !== false)
        );
        
        // Подключаем для страниц Telegram или если не можем точно определить
        if (!$is_telegram_page && $current_screen && $current_screen->id !== 'dashboard') {
            // Не подключаем только если точно знаем, что это не наша страница
            $known_other_pages = array('edit-post', 'edit-page', 'plugins', 'themes', 'users');
            if (in_array($current_screen->base, $known_other_pages)) {
                return;
            }
        }
          $plugin_url = plugin_dir_url(__FILE__);
        $version = '1.0.8.1.' . time(); // Add timestamp for cache busting
          // Подключаем CSS с версией для обновления кэша
        wp_enqueue_style(
            'telegram-admin-styles',
            $plugin_url . 'admin-styles.css',
            array(),
            $version
        );
        
        // Подключаем JavaScript с зависимостью от jQuery
        wp_enqueue_script(
            'telegram-admin-scripts',
            $plugin_url . 'admin-scripts.js',
            array('jquery'),
            $version,
            true
        );
        
        // Передаем данные в JavaScript
        wp_localize_script('telegram-admin-scripts', 'telegramAdminAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('telegram_token_nonce'),
            'isTokenSaved' => TelegramTokenSecurity::is_token_set(),
            'pluginUrl' => $plugin_url
        ));
    }
}

// AJAX Handlers

// Add AJAX handler for test message
add_action('wp_ajax_telegram_test_message', function() {
    $settings = get_option(Telegram_Admin::OPTION_NAME, array());
    
    // Сначала проверяем зашифрованный токен
    $bot_token = TelegramTokenSecurity::get_decrypted_token();
    
    // Если зашифрованного токена нет, проверяем старые настройки
    if (empty($bot_token)) {
        $bot_token = isset($settings['bot_token']) ? $settings['bot_token'] : '';
    }
    
    $chat_ids_raw = isset($settings['chat_ids']) ? $settings['chat_ids'] : '';
    
    // Преобразуем строку с Chat ID в массив
    $chat_ids = array();
    if (!empty($chat_ids_raw)) {
        $chat_ids = array_map('trim', explode(',', $chat_ids_raw));
        $chat_ids = array_filter($chat_ids);
    }
    
    $test_message = "🧪 *Тестовое сообщение*\n\nМодуль Telegram Orders работает корректно!\n📅 " . current_time('d.m.Y H:i:s');
    
    if (empty($bot_token) || empty($chat_ids)) {
        wp_send_json_error('Bot token или Chat IDs не настроены');
        return;
    }
    
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    $success_count = 0;
    $error_messages = array();
    
    // Отправляем тестовое сообщение во все чаты
    foreach ($chat_ids as $chat_id) {
        if (empty($chat_id)) continue;
        
        $data = array(
            'chat_id' => $chat_id,
            'text' => $test_message,
            'parse_mode' => 'Markdown'
        );
        
        $args = array(
            'body' => $data,
            'timeout' => 10,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded'
            )
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            $error_messages[] = "Чат {$chat_id}: " . $response->get_error_message();
        } else {
            $success_count++;
        }
    }
    
    if ($success_count > 0) {
        $message = "Сообщение отправлено в {$success_count} из " . count($chat_ids) . " чатов";
        if (!empty($error_messages)) {
            $message .= ". Ошибки: " . implode('; ', $error_messages);
        }
        wp_send_json_success($message);
    } else {
        wp_send_json_error('Не удалось отправить ни в один чат. Ошибки: ' . implode('; ', $error_messages));
    }
});

// Add AJAX handler for chat search
add_action('wp_ajax_telegram_search_chats', function() {
    $bot_token = isset($_POST['bot_token']) ? sanitize_text_field($_POST['bot_token']) : '';
    
    if (empty($bot_token)) {
        wp_send_json_error('Bot token не указан');
        return;
    }
    
    $url = "https://api.telegram.org/bot{$bot_token}/getUpdates";
    
    $args = array(
        'timeout' => 10,
        'headers' => array(
            'Content-Type' => 'application/json'
        )
    );
    
    $response = wp_remote_get($url, $args);
    
    if (is_wp_error($response)) {
        wp_send_json_error('Ошибка подключения к Telegram API: ' . $response->get_error_message());
        return;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!isset($data['ok']) || !$data['ok']) {
        $error_message = isset($data['description']) ? $data['description'] : 'Неизвестная ошибка';
        wp_send_json_error('Ошибка Telegram API: ' . $error_message);
        return;
    }
    
    if (!isset($data['result'])) {
        wp_send_json_success(array());
        return;
    }
    
    $chats = array();
    foreach ($data['result'] as $update) {
        if (isset($update['message']['chat'])) {
            $chat = $update['message']['chat'];
            $chat_id = $chat['id'];
            
            if (!isset($chats[$chat_id])) {
                $chats[$chat_id] = array(
                    'id' => (string)$chat_id,
                    'type' => $chat['type'],
                    'title' => isset($chat['title']) ? $chat['title'] : '',
                    'username' => isset($chat['username']) ? $chat['username'] : '',
                    'first_name' => isset($chat['first_name']) ? $chat['first_name'] : '',
                    'last_name' => isset($chat['last_name']) ? $chat['last_name'] : ''
                );
            }
        }
    }
    
    wp_send_json_success(array_values($chats));
});

// Add AJAX handler for saving chat data
add_action('wp_ajax_telegram_save_chat_data', function() {
    $chat_data = isset($_POST['chat_data']) ? $_POST['chat_data'] : '';
    
    if (empty($chat_data)) {
        wp_send_json_error('Нет данных для сохранения');
        return;
    }
    
    $decoded_data = json_decode(stripslashes($chat_data), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Ошибка декодирования данных');
        return;
    }
    
    // Load existing chats data
    $stored_chats = get_option(Telegram_Admin::CHATS_DATA_OPTION, array());
    
    // Update stored data
    foreach ($decoded_data as $chat) {
        if (isset($chat['id'])) {
            $stored_chats[$chat['id']] = $chat;
        }
    }
    
    // Save updated data
    update_option(Telegram_Admin::CHATS_DATA_OPTION, $stored_chats);
    
    wp_send_json_success('Данные чатов сохранены');
});

// Add AJAX handler for removing chat data
add_action('wp_ajax_telegram_remove_chat_data', function() {
    $chat_id = isset($_POST['chat_id']) ? sanitize_text_field($_POST['chat_id']) : '';
    
    if (empty($chat_id)) {
        wp_send_json_error('Chat ID не указан');
        return;
    }
    
    // Load existing chats data
    $stored_chats = get_option(Telegram_Admin::CHATS_DATA_OPTION, array());
    
    // Remove chat data
    if (isset($stored_chats[$chat_id])) {
        unset($stored_chats[$chat_id]);
        update_option(Telegram_Admin::CHATS_DATA_OPTION, $stored_chats);
        wp_send_json_success('Данные чата удалены');
    } else {
        wp_send_json_success('Данные чата уже отсутствуют');
    }
});

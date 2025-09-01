<?php
/**
 * AJAX обработчики для безопасного управления токенами
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Подключаем класс безопасности токенов
require_once plugin_dir_path(__FILE__) . 'token-security.php';

/**
 * AJAX: Сохранение зашифрованного токена
 */
add_action('wp_ajax_telegram_save_token', 'telegram_save_token_handler');
function telegram_save_token_handler() {
    // Проверка nonce для безопасности
    if (!check_ajax_referer('telegram_token_nonce', 'security', false)) {
        wp_die(json_encode(['success' => false, 'message' => 'Ошибка безопасности']));
    }
    
    // Проверка прав доступа
    if (!current_user_can('manage_options')) {
        wp_die(json_encode(['success' => false, 'message' => 'Недостаточно прав']));
    }
    
    $token = sanitize_text_field($_POST['token'] ?? '');
    
    if (empty($token)) {
        wp_die(json_encode(['success' => false, 'message' => 'Токен не может быть пустым']));
    }
    
    // Простая валидация формата токена
    if (!preg_match('/^\d+:[A-Za-z0-9_-]+$/', $token)) {
        wp_die(json_encode(['success' => false, 'message' => 'Неверный формат токена']));
    }
      // Сохраняем зашифрованный токен
    $result = TelegramTokenSecurity::save_encrypted_token($token);
    
    if ($result) {
        // Обновляем настройки в основном модуле
        global $neetrino_telegram_instance;
        if ($neetrino_telegram_instance) {
            $neetrino_telegram_instance->refresh_settings();
        }
        
        wp_die(json_encode([
            'success' => true, 
            'message' => 'Токен успешно сохранен и зашифрован'
        ]));
    } else {
        wp_die(json_encode([
            'success' => false, 
            'message' => 'Ошибка при сохранении токена'
        ]));
    }
}

/**
 * AJAX: Удаление токена
 */
add_action('wp_ajax_telegram_delete_token', 'telegram_delete_token_handler');
function telegram_delete_token_handler() {
    // Проверка nonce для безопасности
    if (!check_ajax_referer('telegram_token_nonce', 'security', false)) {
        wp_die(json_encode(['success' => false, 'message' => 'Ошибка безопасности']));
    }
    
    // Проверка прав доступа
    if (!current_user_can('manage_options')) {
        wp_die(json_encode(['success' => false, 'message' => 'Недостаточно прав']));
    }
      // Удаляем токен
    $result = TelegramTokenSecurity::delete_token();
    
    if ($result) {
        // Обновляем настройки в основном модуле
        global $neetrino_telegram_instance;
        if ($neetrino_telegram_instance) {
            $neetrino_telegram_instance->refresh_settings();
        }
        
        wp_die(json_encode([
            'success' => true, 
            'message' => 'Токен успешно удален'
        ]));
    } else {
        wp_die(json_encode([
            'success' => false, 
            'message' => 'Ошибка при удалении токена'
        ]));
    }
}

/**
 * AJAX: Проверка статуса токена
 */
add_action('wp_ajax_telegram_check_token_status', 'telegram_check_token_status_handler');
function telegram_check_token_status_handler() {
    // Проверка nonce для безопасности
    if (!check_ajax_referer('telegram_token_nonce', 'security', false)) {
        wp_die(json_encode(['success' => false, 'message' => 'Ошибка безопасности']));
    }
    
    // Проверка прав доступа
    if (!current_user_can('manage_options')) {
        wp_die(json_encode(['success' => false, 'message' => 'Недостаточно прав']));
    }
    
    $is_token_saved = TelegramTokenSecurity::is_token_set();
    $encrypted_token = get_option('neetrino_telegram_bot_token_encrypted', '');
    
    wp_die(json_encode([
        'success' => true,
        'token_saved' => $is_token_saved,
        'encrypted_token_exists' => !empty($encrypted_token),
        'encrypted_token_length' => strlen($encrypted_token),
        'message' => $is_token_saved ? 'Токен сохранен и зашифрован' : 'Токен не сохранен'
    ]));
}

/**
 * AJAX: Получение токена для использования в поиске чатов
 */
add_action('wp_ajax_telegram_get_token', 'telegram_get_token_handler');
function telegram_get_token_handler() {
    // Проверка nonce для безопасности
    if (!check_ajax_referer('telegram_token_nonce', 'security', false)) {
        wp_die(json_encode(['success' => false, 'message' => 'Ошибка безопасности']));
    }
    
    // Проверка прав доступа
    if (!current_user_can('manage_options')) {
        wp_die(json_encode(['success' => false, 'message' => 'Недостаточно прав']));
    }
    
    // Получаем расшифрованный токен
    $token = TelegramTokenSecurity::get_decrypted_token();
    
    if (!empty($token)) {
        wp_die(json_encode([
            'success' => true, 
            'token' => $token
        ]));
    } else {
        wp_die(json_encode([
            'success' => false, 
            'message' => 'Токен не найден'
        ]));
    }
}

/**
 * AJAX: Получение информации о боте
 */
add_action('wp_ajax_telegram_get_bot_info', 'telegram_get_bot_info_handler');
function telegram_get_bot_info_handler() {
    // Проверка nonce для безопасности
    if (!check_ajax_referer('telegram_token_nonce', 'security', false)) {
        wp_die(json_encode(['success' => false, 'message' => 'Ошибка безопасности']));
    }
    
    // Проверка прав доступа
    if (!current_user_can('manage_options')) {
        wp_die(json_encode(['success' => false, 'message' => 'Недостаточно прав']));
    }
    
    // Получаем расшифрованный токен
    $bot_token = TelegramTokenSecurity::get_decrypted_token();
    
    if (empty($bot_token)) {
        wp_die(json_encode(['success' => false, 'message' => 'Токен не найден']));
    }
    
    // Создаем экземпляр API и получаем информацию о боте
    require_once plugin_dir_path(__FILE__) . 'telegram-api.php';
    $api = new Telegram_API($bot_token);
    $bot_info = $api->get_bot_info();
    
    if (empty($bot_info)) {
        wp_die(json_encode(['success' => false, 'message' => 'Не удалось получить информацию о боте']));
    }
    
    wp_die(json_encode([
        'success' => true, 
        'bot_info' => $bot_info
    ]));
}
?>

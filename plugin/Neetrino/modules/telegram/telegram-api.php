<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Telegram API and Message Formatting
 * Handles all communication with Telegram Bot API and message formatting
 */
class Telegram_API {
    
    private $bot_token;
    private $chat_ids;
    
    public function __construct($bot_token = '', $chat_ids = array()) {
        $this->bot_token = $bot_token;
        $this->chat_ids = $chat_ids;
    }
    
    /**
     * Set bot token
     */
    public function set_bot_token($token) {
        $this->bot_token = $token;
    }
    
    /**
     * Set chat IDs
     */
    public function set_chat_ids($chat_ids) {
        $this->chat_ids = $chat_ids;
    }
    
    /**
     * Format order information for Telegram message
     */
    public function format_order_message($order) {
        $site_url = get_site_url();
        
        // Убираем протокол из URL, оставляем только домен
        $domain = str_replace(['http://', 'https://'], '', $site_url);
        
        $message = "🛒 *Новый заказ #{$order->get_id()}*\n";
        $message .= "🌐 *{$domain}*\n\n";
        
        // Customer information
        $message .= "👤 *Клиент:*\n";
        $message .= "Имя: " . $order->get_billing_first_name() . " " . $order->get_billing_last_name() . "\n";
        $message .= "Email: " . $order->get_billing_email() . "\n";
        $message .= "Телефон: " . $order->get_billing_phone() . "\n\n";
        
        // Order details - исправленная версия
        $message .= "📦 *Товары:*\n";
        $items = $order->get_items();
        if (!empty($items)) {
            foreach ($items as $item) {
                $product_name = $item->get_name();
                $quantity = $item->get_quantity();
                
                if (!empty($product_name)) {
                    $message .= "• " . $product_name . " x" . $quantity;
                    
                    // Добавляем SKU если есть
                    $product = $item->get_product();
                    if ($product && $product->get_sku()) {
                        $message .= " (SKU: " . $product->get_sku() . ")";
                    }
                    $message .= "\n";
                }
            }
        } else {
            $message .= "Товары не найдены\n";
        }
        
        // Исправляем отображение суммы - убираем HTML теги и декодируем символы
        $total = html_entity_decode(strip_tags($order->get_formatted_order_total()), ENT_QUOTES, 'UTF-8');
        $message .= "\n💰 *Сумма заказа:* " . $total . "\n";
        $message .= "📍 *Статус:* " . wc_get_order_status_name($order->get_status()) . "\n";
        
        // Исправляем отображение времени - используем часовой пояс WordPress
        $order_date = $order->get_date_created();
        if ($order_date) {
            // Устанавливаем часовой пояс WordPress
            $order_date->setTimezone(wp_timezone());
            $message .= "📅 *Дата:* " . $order_date->format('d.m.Y H:i');
        }
        
        return $message;
    }
    
    /**
     * Format status change message
     */
    public function format_status_change_message($order_id, $old_status, $new_status, $order) {
        $site_url = get_site_url();
        
        // Убираем протокол из URL, оставляем только домен
        $domain = str_replace(['http://', 'https://'], '', $site_url);
        
        $message = "🔄 *Изменение статуса заказа #{$order_id}*\n";
        $message .= "🌐 *{$domain}*\n\n";
        $message .= "Старый статус: " . wc_get_order_status_name($old_status) . "\n";
        $message .= "Новый статус: " . wc_get_order_status_name($new_status) . "\n";
        $message .= "Клиент: " . $order->get_billing_first_name() . " " . $order->get_billing_last_name() . "\n";
        
        // Исправляем отображение суммы - убираем HTML теги и декодируем символы
        $total = html_entity_decode(strip_tags($order->get_formatted_order_total()), ENT_QUOTES, 'UTF-8');
        $message .= "Сумма: " . $total;
        
        return $message;
    }
    
    /**
     * Send message to Telegram
     */
    public function send_telegram_message($message) {
        if (empty($this->bot_token) || empty($this->chat_ids)) {
            error_log('Telegram Orders: Bot token или Chat IDs не настроены в админке');
            return false;
        }
        
        $url = "https://api.telegram.org/bot{$this->bot_token}/sendMessage";
        $success_count = 0;
        
        // Отправляем сообщение во все чаты
        foreach ($this->chat_ids as $chat_id) {
            if (empty($chat_id)) continue;
            
            $data = array(
                'chat_id' => $chat_id,
                'text' => $message,
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
                error_log('Telegram notification error for chat ' . $chat_id . ': ' . $response->get_error_message());
            } else {
                $success_count++;
            }
        }
        
        return $success_count > 0;
    }
    
    /**
     * Get bot updates to find available chats
     */
    public function get_bot_chats() {
        if (empty($this->bot_token)) {
            return array();
        }
        
        $url = "https://api.telegram.org/bot{$this->bot_token}/getUpdates";
        
        $args = array(
            'timeout' => 10,
            'headers' => array(
                'Content-Type' => 'application/json'
            )
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['ok']) || !$data['ok'] || !isset($data['result'])) {
            return array();
        }
        
        $chats = array();
        foreach ($data['result'] as $update) {
            if (isset($update['message']['chat'])) {
                $chat = $update['message']['chat'];
                $chat_id = $chat['id'];
                
                if (!isset($chats[$chat_id])) {
                    $chats[$chat_id] = array(
                        'id' => $chat_id,
                        'type' => $chat['type'],
                        'title' => isset($chat['title']) ? $chat['title'] : '',
                        'username' => isset($chat['username']) ? $chat['username'] : '',
                        'first_name' => isset($chat['first_name']) ? $chat['first_name'] : '',
                        'last_name' => isset($chat['last_name']) ? $chat['last_name'] : ''
                    );
                }
            }
        }
        
        return array_values($chats);
    }
    
    /**
     * Get bot information from Telegram API
     */
    public function get_bot_info() {
        if (empty($this->bot_token)) {
            return array();
        }
        
        $url = "https://api.telegram.org/bot{$this->bot_token}/getMe";
        
        $args = array(
            'timeout' => 10,
            'headers' => array(
                'Content-Type' => 'application/json'
            )
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['ok']) || !$data['ok'] || !isset($data['result'])) {
            return array();
        }
        
        return $data['result'];
    }
}

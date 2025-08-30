<?php
/**
 * Класс для автозаполнения адресов через Google Places API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Delivery_Autocomplete {
    
    private $settings;
    
    public function __construct($settings) {
        $this->settings = $settings;
        
        if ($this->settings['enable_autocomplete'] && !empty($this->settings['google_api_key'])) {
            $this->init();
        }
    }
    
    /**
     * Инициализация автозаполнения
     */
    private function init() {
        // Основная логика автозаполнения теперь в JavaScript
        // Этот класс используется для дополнительной серверной логики
        
        // AJAX обработчики для геокодирования
        add_action('wp_ajax_neetrino_geocode_address', [$this, 'ajax_geocode_address']);
        add_action('wp_ajax_nopriv_neetrino_geocode_address', [$this, 'ajax_geocode_address']);
        
        // Валидация адресов
        add_action('woocommerce_checkout_process', [$this, 'validate_address_fields']);
    }
    
    /**
     * AJAX геокодирование адреса
     */
    public function ajax_geocode_address() {
        check_ajax_referer('neetrino_delivery_nonce', 'nonce');
        
        $address = sanitize_text_field($_POST['address'] ?? '');
        
        if (empty($address)) {
            wp_send_json_error(['message' => 'Адрес не указан']);
        }
        
        $result = $this->geocode_address($address);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }
    
    /**
     * Геокодирование адреса через Google API
     */
    public function geocode_address($address) {
        if (empty($this->settings['google_api_key'])) {
            return [
                'success' => false,
                'message' => 'Google API ключ не настроен'
            ];
        }
        
        $api_key = $this->settings['google_api_key'];
        $url = "https://maps.googleapis.com/maps/api/geocode/json";
        
        $params = [
            'address' => $address,
            'key' => $api_key,
            'language' => $this->settings['language'] ?? 'ru'
        ];
        
        if (!empty($this->settings['allowed_countries']) && $this->settings['restrict_countries']) {
            $params['components'] = 'country:' . implode('|country:', $this->settings['allowed_countries']);
        }
        
        $response = wp_remote_get($url . '?' . http_build_query($params));
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Ошибка подключения к Google API: ' . $response->get_error_message()
            ];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data['status'] === 'OK' && !empty($data['results'])) {
            return [
                'success' => true,
                'data' => [
                    'formatted_address' => $data['results'][0]['formatted_address'],
                    'geometry' => $data['results'][0]['geometry'],
                    'address_components' => $data['results'][0]['address_components']
                ]
            ];
        } else {
            $error_message = $data['error_message'] ?? 'Адрес не найден';
            return [
                'success' => false,
                'message' => $error_message
            ];
        }
    }
    
    /**
     * Валидация полей адреса
     */
    public function validate_address_fields() {
        // Дополнительная валидация адресов если нужна
        // Основная валидация выполняется в delivery.php
    }
    
    /**
     * Проверка доступности Google API
     */
    public function test_api() {
        return $this->geocode_address('Москва, Красная площадь');
    }
}

<?php
/**
 * Класс для расчета стоимости доставки через Google Distance Matrix API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Delivery_Calculator {
    
    private $settings;
    
    public function __construct($settings) {
        $this->settings = $settings;
    }
    
    /**
     * Расчет стоимости доставки между двумя адресами
     */
    public function calculate_delivery_cost($from_address, $to_address) {
        if (empty($this->settings['google_api_key'])) {
            return ['success' => false, 'message' => 'API ключ Google не настроен'];
        }
        
        if (empty($from_address) || empty($to_address)) {
            return ['success' => false, 'message' => 'Не указаны адреса для расчета'];
        }
        
        // Проверяем кэш
        $cache_key = 'neetrino_delivery_' . md5($from_address . $to_address);
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            return ['success' => true, 'data' => $cached_result, 'cached' => true];
        }
        
        // Делаем запрос к Google Distance Matrix API
        $distance_data = $this->get_distance_from_google($from_address, $to_address);
        
        if (!$distance_data['success']) {
            return $distance_data;
        }
        
        // Рассчитываем стоимость
        $cost_data = $this->calculate_cost_from_distance($distance_data['data']);
        
        // Сохраняем в кэш
        if ($cost_data['success']) {
            $cache_duration = $this->settings['cache_duration'] * DAY_IN_SECONDS;
            set_transient($cache_key, $cost_data['data'], $cache_duration);
        }
        
        return $cost_data;
    }
    
    /**
     * Получение расстояния через Google Distance Matrix API
     */
    private function get_distance_from_google($from, $to) {
        $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?' . http_build_query([
            'origins' => $from,
            'destinations' => $to,
            'key' => $this->settings['google_api_key'],
            'units' => 'metric',
            'language' => substr(get_locale(), 0, 2),
            'avoid' => 'tolls' // Избегаем платных дорог
        ]);
        
        $response = wp_remote_get($url, ['timeout' => 15]);
        
        if (is_wp_error($response)) {
            return ['success' => false, 'message' => 'Ошибка соединения с Google API: ' . $response->get_error_message()];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || $data['status'] !== 'OK') {
            $error_message = isset($data['error_message']) ? $data['error_message'] : 'Неизвестная ошибка Google API';
            return ['success' => false, 'message' => $error_message];
        }
        
        $element = $data['rows'][0]['elements'][0];
        
        if ($element['status'] !== 'OK') {
            return ['success' => false, 'message' => 'Не удалось рассчитать маршрут между адресами'];
        }
        
        return [
            'success' => true,
            'data' => [
                'distance_meters' => $element['distance']['value'],
                'distance_text' => $element['distance']['text'],
                'duration_seconds' => $element['duration']['value'],
                'duration_text' => $element['duration']['text']
            ]
        ];
    }
    
    /**
     * Расчет стоимости на основе расстояния
     */
    private function calculate_cost_from_distance($distance_data) {
        $distance_km = $distance_data['distance_meters'] / 1000;
        
        // Базовый расчет: расстояние * цена за км
        $base_cost = $distance_km * floatval($this->settings['price_per_km']);
        
        // Применяем ограничения
        $min_cost = floatval($this->settings['min_delivery_cost']);
        $max_cost = floatval($this->settings['max_delivery_cost']);
        
        if ($min_cost > 0 && $base_cost < $min_cost) {
            $final_cost = $min_cost;
        } elseif ($max_cost > 0 && $base_cost > $max_cost) {
            $final_cost = $max_cost;
        } else {
            $final_cost = $base_cost;
        }
        
        // Проверяем бесплатную доставку
        $free_delivery_from = floatval($this->settings['free_delivery_from']);
        $cart_total = 0;
        
        if (function_exists('WC') && WC()->cart) {
            $cart_total = WC()->cart->get_subtotal();
        }
        
        if ($free_delivery_from > 0 && $cart_total >= $free_delivery_from) {
            $final_cost = 0;
        }
        
        return [
            'success' => true,
            'data' => [
                'distance_km' => round($distance_km, 2),
                'distance_text' => $distance_data['distance_text'],
                'duration_text' => $distance_data['duration_text'],
                'base_cost' => round($base_cost, 2),
                'final_cost' => round($final_cost, 2),
                'currency' => $this->get_currency(),
                'currency_symbol' => $this->get_currency_symbol(),
                'free_delivery' => ($final_cost == 0 && $free_delivery_from > 0),
                'cart_total' => $cart_total,
                'free_delivery_threshold' => $free_delivery_from
            ]
        ];
    }
    
    /**
     * Геокодирование адреса (получение координат)
     */
    public function geocode_address($address) {
        if (empty($this->settings['google_api_key'])) {
            return ['success' => false, 'message' => 'API ключ Google не настроен'];
        }
        
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
            'address' => $address,
            'key' => $this->settings['google_api_key'],
            'language' => substr(get_locale(), 0, 2)
        ]);
        
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return ['success' => false, 'message' => 'Ошибка соединения с Google API'];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data['status'] !== 'OK' || empty($data['results'])) {
            return ['success' => false, 'message' => 'Адрес не найден'];
        }
        
        $result = $data['results'][0];
        
        return [
            'success' => true,
            'data' => [
                'formatted_address' => $result['formatted_address'],
                'lat' => $result['geometry']['location']['lat'],
                'lng' => $result['geometry']['location']['lng'],
                'address_components' => $result['address_components']
            ]
        ];
    }
    
    /**
     * Обратное геокодирование (получение адреса по координатам)
     */
    public function reverse_geocode($lat, $lng) {
        if (empty($this->settings['google_api_key'])) {
            return ['success' => false, 'message' => 'API ключ Google не настроен'];
        }
        
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
            'latlng' => $lat . ',' . $lng,
            'key' => $this->settings['google_api_key'],
            'language' => substr(get_locale(), 0, 2)
        ]);
        
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return ['success' => false, 'message' => 'Ошибка соединения с Google API'];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data['status'] !== 'OK' || empty($data['results'])) {
            return ['success' => false, 'message' => 'Адрес не найден'];
        }
        
        return [
            'success' => true,
            'data' => [
                'formatted_address' => $data['results'][0]['formatted_address'],
                'address_components' => $data['results'][0]['address_components']
            ]
        ];
    }
    
    /**
     * Парсинг компонентов адреса Google Places
     */
    public function parse_address_components($components) {
        $address_data = [
            'street_number' => '',
            'route' => '',
            'locality' => '',
            'administrative_area_level_1' => '',
            'country' => '',
            'postal_code' => ''
        ];
        
        foreach ($components as $component) {
            $type = $component['types'][0];
            if (isset($address_data[$type])) {
                $address_data[$type] = $component['long_name'];
            }
        }
        
        return [
            'address_1' => trim($address_data['street_number'] . ' ' . $address_data['route']),
            'city' => $address_data['locality'],
            'state' => $address_data['administrative_area_level_1'],
            'country' => $address_data['country'],
            'postcode' => $address_data['postal_code']
        ];
    }
    
    /**
     * Получение валюты из WooCommerce
     */
    private function get_currency() {
        if (function_exists('get_woocommerce_currency')) {
            return get_woocommerce_currency();
        }
        return 'USD';
    }
    
    /**
     * Получение символа валюты из WooCommerce
     */
    private function get_currency_symbol() {
        if (function_exists('get_woocommerce_currency_symbol')) {
            return get_woocommerce_currency_symbol();
        }
        return '$';
    }
    
    /**
     * Очистка кэша расчетов
     */
    public function clear_cache() {
        global $wpdb;
        
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_neetrino_delivery_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_neetrino_delivery_%'");
        
        return true;
    }
    
    /**
     * Получение статистики использования API
     */
    public function get_api_usage_stats() {
        $stats = get_option('neetrino_delivery_api_stats', [
            'requests_today' => 0,
            'requests_month' => 0,
            'last_request_date' => '',
            'errors_count' => 0
        ]);
        
        return $stats;
    }
    
    /**
     * Обновление статистики API
     */
    public function update_api_stats($success = true) {
        $stats = $this->get_api_usage_stats();
        $today = date('Y-m-d');
        $current_month = date('Y-m');
        
        // Сброс счетчика если новый день
        if ($stats['last_request_date'] !== $today) {
            $stats['requests_today'] = 0;
        }
        
        // Сброс месячного счетчика если новый месяц
        if (substr($stats['last_request_date'], 0, 7) !== $current_month) {
            $stats['requests_month'] = 0;
        }
        
        $stats['requests_today']++;
        $stats['requests_month']++;
        $stats['last_request_date'] = $today;
        
        if (!$success) {
            $stats['errors_count']++;
        }
        
        update_option('neetrino_delivery_api_stats', $stats);
    }
}

<?php
/**
 * Module: Delivery
 * Description: Автозаполнение адресов и расчет доставки через Google API.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Delivery {
    
    private $settings;
    
    public function __construct() {
        // ОБЯЗАТЕЛЬНО: Проверка активности модуля
        if (!Neetrino::is_module_active('delivery')) {
            return;
        }
        
        // Инициализация модуля только если он активен
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }
    
    public function init() {
        // Основная логика модуля
        $this->load_settings();
        
        // Отладочная информация
        error_log('Neetrino Delivery: Инициализация модуля');
        error_log('Neetrino Delivery: API ключ ' . (!empty($this->settings['google_api_key']) ? 'настроен' : 'НЕ настроен'));
        error_log('Neetrino Delivery: Автозаполнение ' . ($this->settings['enable_autocomplete'] ? 'включено' : 'выключено'));
        
        $this->load_dependencies();
        $this->setup_hooks();
    }
    
    /**
     * Загрузка настроек модуля
     */
    private function load_settings() {
        $this->settings = get_option('neetrino_delivery_settings', [
            'google_api_key' => '',
            'shop_address' => 'Москва, Россия', // Адрес магазина по умолчанию
            'price_per_km' => 50, // Цена за км по умолчанию
            'min_delivery_cost' => 200, // Минимальная стоимость доставки
            'max_delivery_cost' => 2000, // Максимальная стоимость доставки
            'free_delivery_from' => 5000, // Бесплатная доставка от суммы
            'enable_autocomplete' => true,
            'enable_geolocation' => true,
            'allowed_countries' => ['RU', 'US', 'GB', 'AM'], // Добавлена Армения (AM)
            'default_country' => 'RU',
            'language' => 'ru',
            'restrict_countries' => true, // Ограничение по странам
            'cache_duration' => 7 // дней
        ]);
        
        // Убеждаемся что allowed_countries всегда массив
        if (!is_array($this->settings['allowed_countries'])) {
            $this->settings['allowed_countries'] = ['RU'];
        }
        
        // Логируем настройки для отладки
        error_log('Neetrino Delivery Settings: ' . json_encode([
            'shop_address' => $this->settings['shop_address'],
            'price_per_km' => $this->settings['price_per_km'],
            'min_delivery_cost' => $this->settings['min_delivery_cost'],
            'api_key_set' => !empty($this->settings['google_api_key'])
        ]));
    }
    
    /**
     * Подключение зависимостей
     */
    private function load_dependencies() {
        $base_path = plugin_dir_path(__FILE__) . 'includes/';
        
        $dependencies = [
            'class-delivery-autocomplete.php',
            'class-delivery-calculator.php', 
            'class-delivery-shipping-method.php',
            'admin-interface.php'
        ];
        
        foreach ($dependencies as $file) {
            $file_path = $base_path . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log("Neetrino Delivery: Файл не найден: " . $file_path);
            }
        }
    }
    
    /**
     * Настройка хуков
     */
    private function setup_hooks() {
        // Проверяем что WooCommerce активен
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        // Хуки для автозаполнения адресов
        add_filter('woocommerce_checkout_fields', [$this, 'modify_checkout_fields']);
        add_action('woocommerce_checkout_process', [$this, 'validate_delivery_fields']);
        
        // Инициализируем компоненты модуля
        if (!empty($this->settings['google_api_key'])) {
            new Neetrino_Delivery_Autocomplete($this->settings);
            new Neetrino_Delivery_Calculator($this->settings);
            
            // Регистрируем метод доставки в WooCommerce
            add_action('woocommerce_shipping_init', [$this, 'init_shipping_method']);
            add_filter('woocommerce_shipping_methods', [$this, 'add_shipping_method']);
        }
        
        // AJAX обработчики
        add_action('wp_ajax_delivery_calculate_cost', [$this, 'ajax_calculate_delivery']);
        add_action('wp_ajax_nopriv_delivery_calculate_cost', [$this, 'ajax_calculate_delivery']);
        add_action('wp_ajax_delivery_geocode', [$this, 'ajax_geocode_address']);
        add_action('wp_ajax_nopriv_delivery_geocode', [$this, 'ajax_geocode_address']);
        add_action('wp_ajax_test_google_api', [$this, 'test_google_api']);
        add_action('wp_ajax_nopriv_test_google_api', [$this, 'test_google_api']);
    }
    
    /**
     * Подключение скриптов для фронтенда
     */
    public function enqueue_scripts() {
        // Подключаем только на страницах checkout и cart
        if (is_checkout() || is_cart()) {
            $api_key = $this->settings['google_api_key'];
            $enable_autocomplete = $this->settings['enable_autocomplete'];
            
            // Передаем данные в JavaScript ВСЕГДА (даже без API ключа)
            wp_localize_script('jquery', 'neetrinoDelivery', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('neetrino_delivery_nonce'),
                'settings' => [
                    'google_api_key' => $api_key,
                    'shop_address' => $this->settings['shop_address'],
                    'price_per_km' => $this->settings['price_per_km'],
                    'min_delivery_cost' => $this->settings['min_delivery_cost'],
                    'max_delivery_cost' => $this->settings['max_delivery_cost'],
                    'free_delivery_from' => $this->settings['free_delivery_from'],
                    'enable_autocomplete' => $enable_autocomplete,
                    'enable_geolocation' => $this->settings['enable_geolocation'],
                    'allowed_countries' => $this->settings['allowed_countries'],
                    'restrict_countries' => $this->settings['restrict_countries'],
                    'language' => $this->settings['language']
                ],
                'messages' => [
                    'autocomplete_ready' => __('Автозаполнение адресов готово к работе', 'neetrino'),
                    'calculating' => __('Расчет стоимости доставки...', 'neetrino'),
                    'error' => __('Ошибка при расчете доставки', 'neetrino'),
                    'location_error' => __('Не удалось определить местоположение', 'neetrino')
                ]
            ]);
            
            if (!empty($api_key) && $enable_autocomplete) {
                // Google Places API с callback функцией
                $language = get_locale();
                if (strlen($language) > 0) {
                    $language = explode('_', $language)[0];
                } else {
                    $language = 'en';
                }
                
                wp_enqueue_script(
                    'google-places-api',
                    'https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&loading=async&language=' . $language . '&libraries=places&v=weekly&callback=initNeetrinoDelivery',
                    [],
                    '1.0.0',
                    true
                );
                
                // Обработчик ошибок Google API
                wp_add_inline_script('google-places-api', '
                    window.gm_authFailure = function() {
                        console.error("❌ Google API Auth Failure - проверьте API ключ");
                        const notice = document.createElement("div");
                        notice.style.cssText = "background:#f8d7da;color:#721c24;padding:10px;margin:10px 0;border:1px solid #f5c6cb;border-radius:4px;";
                        notice.innerHTML = "❌ <strong>Google API Error:</strong> Проверьте настройки API ключа в Google Cloud Console";
                        const form = document.querySelector(".woocommerce-checkout");
                        if (form) form.insertBefore(notice, form.firstChild);
                    };
                ', 'before');
                
                // Скрипт автозаполнения
                wp_enqueue_script(
                    'neetrino-delivery-autocomplete',
                    plugin_dir_url(__FILE__) . 'assets/delivery-autocomplete.js',
                    ['jquery', 'google-places-api'],
                    '1.0.2', // Увеличиваем версию для принудительного обновления
                    true
                );
            } else {
                // Подключаем скрипт автозаполнения без Google API для fallback функциональности
                wp_enqueue_script(
                    'neetrino-delivery-autocomplete',
                    plugin_dir_url(__FILE__) . 'assets/delivery-autocomplete.js',
                    ['jquery'],
                    '1.0.2', // Увеличиваем версию для принудительного обновления
                    true
                );
            }
            
            // Скрипт расчета доставки
            wp_enqueue_script(
                'neetrino-delivery-calculator',
                plugin_dir_url(__FILE__) . 'assets/delivery-calculator.js',
                ['jquery'],
                '1.0.0',
                true
            );
            
            // Стили
            wp_enqueue_style(
                'neetrino-delivery-frontend',
                plugin_dir_url(__FILE__) . 'assets/delivery-frontend.css',
                [],
                '1.0.1' // Увеличиваем версию
            );
        }
    }
    
    /**
     * Подключение скриптов для админки
     */
    public function enqueue_admin_scripts($hook) {
        // Подключаем только на странице настроек модуля
        if (strpos($hook, 'neetrino') !== false) {
            wp_enqueue_style(
                'neetrino-delivery-admin',
                plugin_dir_url(__FILE__) . 'assets/delivery-admin.css',
                [],
                '1.0.0'
            );
            
            wp_enqueue_script(
                'neetrino-delivery-admin',
                plugin_dir_url(__FILE__) . 'assets/delivery-admin.js',
                ['jquery'],
                '1.0.0',
                true
            );
        }
    }
    
    /**
     * Инициализация метода доставки WooCommerce
     */
    public function init_shipping_method() {
        if (class_exists('Neetrino_Delivery_Shipping_Method')) {
            new Neetrino_Delivery_Shipping_Method($this->settings);
        }
    }
    
    /**
     * Добавление метода доставки в WooCommerce
     */
    public function add_shipping_method($methods) {
        $methods['neetrino_delivery'] = 'Neetrino_Delivery_Shipping_Method';
        return $methods;
    }
    
    /**
     * AJAX обработчик расчета стоимости доставки
     */
    public function ajax_calculate_delivery() {
        check_ajax_referer('neetrino_delivery_nonce', 'nonce');
        
        $from = sanitize_text_field($_POST['from'] ?? '');
        $to = sanitize_text_field($_POST['to'] ?? '');
        
        if (empty($from) || empty($to)) {
            wp_send_json_error(['message' => 'Не указаны адреса']);
        }
        
        $calculator = new Neetrino_Delivery_Calculator($this->settings);
        $result = $calculator->calculate_delivery_cost($from, $to);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }
    
    /**
     * AJAX обработчик геокодирования адреса
     */
    public function ajax_geocode_address() {
        check_ajax_referer('neetrino_delivery_nonce', 'nonce');
        
        $address = sanitize_text_field($_POST['address'] ?? '');
        
        if (empty($address)) {
            wp_send_json_error(['message' => 'Адрес не указан']);
        }
        
        $calculator = new Neetrino_Delivery_Calculator($this->settings);
        $result = $calculator->geocode_address($address);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }
    
    /**
     * Модификация полей checkout для автозаполнения
     */
    public function modify_checkout_fields($fields) {
        error_log('Neetrino Delivery: modify_checkout_fields вызвана');
        
        if (!$this->settings['enable_autocomplete'] || empty($this->settings['google_api_key'])) {
            error_log('Neetrino Delivery: Автозаполнение отключено или API ключ пустой');
            return $fields;
        }
        
        // Добавляем класс для автозаполнения к полям адреса
        $address_fields = ['billing_address_1', 'shipping_address_1'];
        
        foreach ($address_fields as $field_key) {
            $type = strpos($field_key, 'billing') === 0 ? 'billing' : 'shipping';
            
            if (isset($fields[$type][$field_key])) {
                if (!isset($fields[$type][$field_key]['class'])) {
                    $fields[$type][$field_key]['class'] = [];
                }
                $fields[$type][$field_key]['class'][] = 'neetrino-autocomplete-field';
                $fields[$type][$field_key]['custom_attributes']['data-field-type'] = $type;
                $fields[$type][$field_key]['placeholder'] = 'Начните вводить адрес для автозаполнения...';
                
                // Добавляем дополнительные атрибуты для лучшей совместимости
                $fields[$type][$field_key]['custom_attributes']['autocomplete'] = 'address-line1';
                $fields[$type][$field_key]['custom_attributes']['data-autocomplete'] = 'enabled';
                
                error_log("Neetrino Delivery: Добавлен класс автозаполнения к полю {$field_key}");
            } else {
                error_log("Neetrino Delivery: Поле {$field_key} не найдено в {$type}");
            }
        }
        
        return $fields;
    }
    
    /**
     * Валидация полей доставки
     */
    public function validate_delivery_fields() {
        // Базовая валидация может быть расширена
        if (empty($_POST['billing_address_1'])) {
            wc_add_notice('Пожалуйста, укажите адрес.', 'error');
        }
    }
    
    /**
     * Тестирование Google API
     */
    public function test_google_api() {
        check_ajax_referer('neetrino_delivery_nonce', 'nonce');
        
        $api_key = $this->settings['google_api_key'];
        if (empty($api_key)) {
            wp_send_json_error('API ключ не указан');
        }
        
        // Тест Places API
        $places_url = "https://maps.googleapis.com/maps/api/place/autocomplete/json?input=Moscow&key={$api_key}";
        $response = wp_remote_get($places_url);
        
        if (is_wp_error($response)) {
            wp_send_json_error('Ошибка подключения к Google API: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['status']) && $data['status'] === 'OK') {
            wp_send_json_success('Google Places API работает корректно');
        } else {
            $error_message = isset($data['error_message']) ? $data['error_message'] : 'Неизвестная ошибка API';
            wp_send_json_error('Ошибка Google API: ' . $error_message);
        }
    }

    /**
     * Страница настроек модуля (вызывается автоматически)
     */
    public static function admin_page() {
        require_once plugin_dir_path(__FILE__) . 'includes/admin-interface.php';
        render_delivery_admin_interface();
    }
}

// Инициализация модуля
new Neetrino_Delivery();

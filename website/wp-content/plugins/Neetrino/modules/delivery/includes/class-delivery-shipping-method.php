<?php
/**
 * Класс метода доставки для WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Delivery_Shipping_Method extends WC_Shipping_Method {
    
    private $delivery_settings;
    
    public function __construct($instance_id = 0) {
        $this->id = 'neetrino_delivery';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('Neetrino Delivery', 'neetrino');
        $this->method_description = __('Автоматический расчет стоимости доставки через Google API', 'neetrino');
        
        // Поддерживаемые функции
        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal'
        ];
        
        // Загружаем настройки
        $this->delivery_settings = get_option('neetrino_delivery_settings', []);
        
        $this->init();
    }
    
    /**
     * Инициализация метода доставки
     */
    public function init() {
        // Настройки формы
        $this->init_form_fields();
        $this->init_settings();
        
        // Основные настройки
        $this->title = $this->get_option('title', 'Доставка');
        $this->enabled = $this->get_option('enabled', 'yes');
        
        // Сохранение настроек
        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }
    
    /**
     * Настройка полей формы
     */
    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Включить/Отключить', 'neetrino'),
                'type' => 'checkbox',
                'description' => __('Включить этот метод доставки', 'neetrino'),
                'default' => 'yes'
            ],
            'title' => [
                'title' => __('Название метода', 'neetrino'),
                'type' => 'text',
                'description' => __('Название, которое увидит клиент', 'neetrino'),
                'default' => __('Доставка', 'neetrino'),
                'desc_tip' => true
            ]
        ];
    }
    
    /**
     * Расчет стоимости доставки
     */
    public function calculate_shipping($package = []) {
        // Проверяем что Google API настроен
        if (empty($this->delivery_settings['google_api_key']) || 
            empty($this->delivery_settings['shop_address'])) {
            return;
        }
        
        // Получаем адрес доставки
        $destination_address = $this->get_destination_address($package);
        
        if (empty($destination_address)) {
            return;
        }
        
        // Рассчитываем стоимость
        $calculator = new Neetrino_Delivery_Calculator($this->delivery_settings);
        $result = $calculator->calculate_delivery_cost(
            $this->delivery_settings['shop_address'],
            $destination_address
        );
        
        if (!$result['success']) {
            // Логируем ошибку но не показываем клиенту
            error_log('Neetrino Delivery Error: ' . $result['message']);
            return;
        }
        
        $cost_data = $result['data'];
        
        // Создаем тариф доставки
        $rate = [
            'id' => $this->id . ':' . $this->instance_id,
            'label' => $this->title,
            'cost' => $cost_data['final_cost'],
            'package' => $package,
            'meta_data' => [
                'distance' => $cost_data['distance_text'],
                'duration' => $cost_data['duration_text'],
                'delivery_method' => 'neetrino_google_api'
            ]
        ];
        
        // Добавляем информацию о расстоянии в название если нужно
        if (!empty($cost_data['distance_text'])) {
            $rate['label'] .= ' (' . $cost_data['distance_text'] . ')';
        }
        
        // Если бесплатная доставка
        if ($cost_data['free_delivery']) {
            $rate['label'] .= ' - ' . __('Бесплатно!', 'neetrino');
        }
        
        $this->add_rate($rate);
    }
    
    /**
     * Получение адреса доставки из пакета
     */
    private function get_destination_address($package) {
        $destination = $package['destination'];
        
        if (empty($destination['address_1']) && empty($destination['city'])) {
            return '';
        }
        
        // Формируем полный адрес
        $address_parts = [];
        
        if (!empty($destination['address_1'])) {
            $address_parts[] = $destination['address_1'];
        }
        
        if (!empty($destination['address_2'])) {
            $address_parts[] = $destination['address_2'];
        }
        
        if (!empty($destination['city'])) {
            $address_parts[] = $destination['city'];
        }
        
        if (!empty($destination['state'])) {
            $address_parts[] = $destination['state'];
        }
        
        if (!empty($destination['postcode'])) {
            $address_parts[] = $destination['postcode'];
        }
        
        if (!empty($destination['country'])) {
            // Получаем название страны
            if (function_exists('WC') && WC()->countries) {
                $countries = WC()->countries->get_countries();
                if (isset($countries[$destination['country']])) {
                    $address_parts[] = $countries[$destination['country']];
                } else {
                    $address_parts[] = $destination['country'];
                }
            } else {
                $address_parts[] = $destination['country'];
            }
        }
        
        return implode(', ', array_filter($address_parts));
    }
    
    /**
     * Проверка доступности метода доставки
     */
    public function is_available($package) {
        // Базовая проверка
        if (!parent::is_available($package)) {
            return false;
        }
        
        // Проверяем настройки модуля
        if (empty($this->delivery_settings['google_api_key']) || 
            empty($this->delivery_settings['shop_address'])) {
            return false;
        }
        
        // Проверяем что модуль активен
        if (!Neetrino::is_module_active('delivery')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Получение описания метода доставки для админки
     */
    public function get_method_description() {
        $description = parent::get_method_description();
        
        if (empty($this->delivery_settings['google_api_key'])) {
            $description .= '<br><strong style="color: #dc3232;">⚠️ Google API ключ не настроен!</strong>';
        }
        
        if (empty($this->delivery_settings['shop_address'])) {
            $description .= '<br><strong style="color: #dc3232;">⚠️ Адрес магазина не указан!</strong>';
        }
        
        return $description;
    }
    
    /**
     * Валидация настроек
     */
    public function validate_settings() {
        $errors = [];
        
        if (empty($this->delivery_settings['google_api_key'])) {
            $errors[] = __('Не указан Google API ключ', 'neetrino');
        }
        
        if (empty($this->delivery_settings['shop_address'])) {
            $errors[] = __('Не указан адрес магазина', 'neetrino');
        }
        
        return $errors;
    }
}

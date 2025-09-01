<?php
/**
 * Module: WooCommerce Checkout Fields Manager
 * Description: Управление полями на странице оформления заказа WooCommerce
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Checkout_Fields {
    
    private $fields_config = [];
    
    public function __construct() {
        // Инициализация модуля только если он активен
        if (!Neetrino::is_module_active('checkout-fields')) {
            return;
        }
        
        // Хуки и действия модуля
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_filter('woocommerce_checkout_fields', [$this, 'modify_checkout_fields']);
        add_filter('woocommerce_billing_fields', [$this, 'modify_billing_fields']);
        add_filter('woocommerce_shipping_fields', [$this, 'modify_shipping_fields']);
        
        // Хуки для управления адресом доставки
        add_action('woocommerce_checkout_process', [$this, 'enforce_shipping_destination']);
        add_filter('woocommerce_checkout_posted_data', [$this, 'modify_checkout_posted_data']);
    }
    
    public function init() {
        // Загружаем настройки полей
        $this->load_fields_config();
    }
    
    /**
     * Загружает конфигурацию полей из настроек
     */
    private function load_fields_config() {
        $default_config = [
            'billing_fields' => [
                'billing_first_name' => ['enabled' => true, 'required' => true],
                'billing_last_name' => ['enabled' => true, 'required' => true],
                'billing_company' => ['enabled' => false, 'required' => false],
                'billing_address_1' => ['enabled' => true, 'required' => true],
                'billing_address_2' => ['enabled' => false, 'required' => false],
                'billing_city' => ['enabled' => true, 'required' => true],
                'billing_postcode' => ['enabled' => true, 'required' => true],
                'billing_country' => ['enabled' => true, 'required' => true],
                'billing_state' => ['enabled' => true, 'required' => true],
                'billing_phone' => ['enabled' => true, 'required' => true],
                'billing_email' => ['enabled' => true, 'required' => true],
            ],
            'shipping_fields' => [
                'shipping_first_name' => ['enabled' => true, 'required' => true],
                'shipping_last_name' => ['enabled' => true, 'required' => true],
                'shipping_company' => ['enabled' => false, 'required' => false],
                'shipping_address_1' => ['enabled' => true, 'required' => true],
                'shipping_address_2' => ['enabled' => false, 'required' => false],
                'shipping_city' => ['enabled' => true, 'required' => true],
                'shipping_postcode' => ['enabled' => true, 'required' => true],
                'shipping_country' => ['enabled' => true, 'required' => true],
                'shipping_state' => ['enabled' => true, 'required' => true],
            ],
            'order_fields' => [
                'order_comments' => ['enabled' => true, 'required' => false],
            ]
        ];
        
        $this->fields_config = get_option('neetrino_checkout_fields_config', $default_config);
    }
    
    /**
     * Модифицирует поля чекаута
     */
    public function modify_checkout_fields($fields) {
        // Обрабатываем поля заказа
        if (isset($fields['order']) && isset($this->fields_config['order_fields'])) {
            foreach ($this->fields_config['order_fields'] as $field_key => $config) {
                if (!$config['enabled']) {
                    unset($fields['order'][$field_key]);
                } else {
                    if (isset($fields['order'][$field_key])) {
                        $fields['order'][$field_key]['required'] = $config['required'];
                    }
                }
            }
        }
        
        return $fields;
    }
    
    /**
     * Модифицирует поля биллинга
     */
    public function modify_billing_fields($fields) {
        if (isset($this->fields_config['billing_fields'])) {
            foreach ($this->fields_config['billing_fields'] as $field_key => $config) {
                if (!$config['enabled']) {
                    unset($fields[$field_key]);
                } else {
                    if (isset($fields[$field_key])) {
                        $fields[$field_key]['required'] = $config['required'];
                    }
                }
            }
        }
        
        return $fields;
    }
    
    /**
     * Модифицирует поля доставки
     */
    public function modify_shipping_fields($fields) {
        if (isset($this->fields_config['shipping_fields'])) {
            foreach ($this->fields_config['shipping_fields'] as $field_key => $config) {
                if (!$config['enabled']) {
                    unset($fields[$field_key]);
                } else {
                    if (isset($fields[$field_key])) {
                        $fields[$field_key]['required'] = $config['required'];
                    }
                }
            }
        }
        
        return $fields;
    }
    
    /**
     * Применяет логику адреса доставки в процессе оформления заказа
     */
    public function enforce_shipping_destination() {
        $shipping_destination = get_option('woocommerce_ship_to_destination', 'billing');
        
        if ($shipping_destination === 'billing_only') {
            // Принудительно копируем данные биллинга в доставку
            if (isset($_POST['billing_first_name'])) {
                $_POST['shipping_first_name'] = $_POST['billing_first_name'];
            }
            if (isset($_POST['billing_last_name'])) {
                $_POST['shipping_last_name'] = $_POST['billing_last_name'];
            }
            if (isset($_POST['billing_company'])) {
                $_POST['shipping_company'] = $_POST['billing_company'];
            }
            if (isset($_POST['billing_address_1'])) {
                $_POST['shipping_address_1'] = $_POST['billing_address_1'];
            }
            if (isset($_POST['billing_address_2'])) {
                $_POST['shipping_address_2'] = $_POST['billing_address_2'];
            }
            if (isset($_POST['billing_city'])) {
                $_POST['shipping_city'] = $_POST['billing_city'];
            }
            if (isset($_POST['billing_state'])) {
                $_POST['shipping_state'] = $_POST['billing_state'];
            }
            if (isset($_POST['billing_postcode'])) {
                $_POST['shipping_postcode'] = $_POST['billing_postcode'];
            }
            if (isset($_POST['billing_country'])) {
                $_POST['shipping_country'] = $_POST['billing_country'];
            }
        }
    }
    
    /**
     * Модифицирует данные чекаута в зависимости от настройки доставки
     */
    public function modify_checkout_posted_data($data) {
        $shipping_destination = get_option('woocommerce_ship_to_destination', 'billing');
        
        // Устанавливаем ship_to_different_address в зависимости от настройки
        if ($shipping_destination === 'billing_only') {
            $data['ship_to_different_address'] = 0;
        } elseif ($shipping_destination === 'shipping') {
            // Если не указано явно, по умолчанию разрешаем отдельный адрес
            if (!isset($data['ship_to_different_address'])) {
                $data['ship_to_different_address'] = 1;
            }
        }
        // При 'billing' оставляем как есть (по умолчанию billing, но можно изменить)
        
        return $data;
    }
    
    public function enqueue_scripts() {
        if (is_admin()) {
            wp_enqueue_style(
                'neetrino-checkout-fields-admin',
                plugin_dir_url(__FILE__) . 'assets/admin.css',
                [],
                '2.3.0'
            );
            
            wp_enqueue_script(
                'neetrino-checkout-fields-admin',
                plugin_dir_url(__FILE__) . 'assets/admin.js',
                ['jquery'],
                '2.3.0',
                true
            );
        } elseif (is_checkout()) {
            // Добавляем JavaScript для фронтенда чекаута
            wp_enqueue_script(
                'neetrino-checkout-fields-frontend',
                plugin_dir_url(__FILE__) . 'assets/frontend.js',
                ['jquery'],
                '1.0.0',
                true
            );
            
            // Передаем настройки в JavaScript
            wp_localize_script('neetrino-checkout-fields-frontend', 'neetrino_checkout', [
                'shipping_destination' => get_option('woocommerce_ship_to_destination', 'billing'),
                'ajax_url' => admin_url('admin-ajax.php')
            ]);
        }
    }
    
    /**
     * Статический метод для админ-страницы
     * Вызывается автоматически если модуль активен
     */
    public static function admin_page() {
        // Обработка сохранения настроек
        if (isset($_POST['save_checkout_fields']) && wp_verify_nonce($_POST['checkout_fields_nonce'], 'save_checkout_fields')) {
            
            // Обрабатываем настройку адреса доставки (трёхуровневый переключатель)
            if (isset($_POST['shipping_destination'])) {
                $shipping_destination = sanitize_text_field($_POST['shipping_destination']);
                if (in_array($shipping_destination, ['shipping', 'billing', 'billing_only'])) {
                    update_option('woocommerce_ship_to_destination', $shipping_destination);
                }
            }
            $config = [
                'billing_fields' => [],
                'shipping_fields' => [],
                'order_fields' => []
            ];
            
            // Обрабатываем поля биллинга
            $billing_fields = [
                'billing_first_name', 'billing_last_name', 'billing_company',
                'billing_address_1', 'billing_address_2', 'billing_city',
                'billing_postcode', 'billing_country', 'billing_state',
                'billing_phone', 'billing_email'
            ];
            
            foreach ($billing_fields as $field) {
                $config['billing_fields'][$field] = [
                    'enabled' => isset($_POST['fields'][$field]['enabled']),
                    'required' => isset($_POST['fields'][$field]['required'])
                ];
            }
            
            // Обрабатываем поля доставки
            $shipping_fields = [
                'shipping_first_name', 'shipping_last_name', 'shipping_company',
                'shipping_address_1', 'shipping_address_2', 'shipping_city',
                'shipping_postcode', 'shipping_country', 'shipping_state'
            ];
            
            foreach ($shipping_fields as $field) {
                $config['shipping_fields'][$field] = [
                    'enabled' => isset($_POST['fields'][$field]['enabled']),
                    'required' => isset($_POST['fields'][$field]['required'])
                ];
            }
            
            // Обрабатываем поля заказа
            $config['order_fields']['order_comments'] = [
                'enabled' => isset($_POST['fields']['order_comments']['enabled']),
                'required' => isset($_POST['fields']['order_comments']['required'])
            ];
            
            update_option('neetrino_checkout_fields_config', $config);
            echo '<div class="neetrino-success-notice">
                <div class="neetrino-success-content">
                    <i class="fa-solid fa-check-circle"></i>
                    <span>Настройки успешно сохранены!</span>
                </div>
            </div>';
        }
        
        // Получаем текущие настройки
        $default_config = [
            'billing_fields' => [
                'billing_first_name' => ['enabled' => true, 'required' => true],
                'billing_last_name' => ['enabled' => true, 'required' => true],
                'billing_company' => ['enabled' => false, 'required' => false],
                'billing_address_1' => ['enabled' => true, 'required' => true],
                'billing_address_2' => ['enabled' => false, 'required' => false],
                'billing_city' => ['enabled' => true, 'required' => true],
                'billing_postcode' => ['enabled' => true, 'required' => true],
                'billing_country' => ['enabled' => true, 'required' => true],
                'billing_state' => ['enabled' => true, 'required' => true],
                'billing_phone' => ['enabled' => true, 'required' => true],
                'billing_email' => ['enabled' => true, 'required' => true],
            ],
            'shipping_fields' => [
                'shipping_first_name' => ['enabled' => true, 'required' => true],
                'shipping_last_name' => ['enabled' => true, 'required' => true],
                'shipping_company' => ['enabled' => false, 'required' => false],
                'shipping_address_1' => ['enabled' => true, 'required' => true],
                'shipping_address_2' => ['enabled' => false, 'required' => false],
                'shipping_city' => ['enabled' => true, 'required' => true],
                'shipping_postcode' => ['enabled' => true, 'required' => true],
                'shipping_country' => ['enabled' => true, 'required' => true],
                'shipping_state' => ['enabled' => true, 'required' => true],
            ],
            'order_fields' => [
                'order_comments' => ['enabled' => true, 'required' => false],
            ]
        ];
        
        $config = get_option('neetrino_checkout_fields_config', $default_config);
        
        // Получаем текущее значение настройки доставки
        $current_shipping_destination = get_option('woocommerce_ship_to_destination', 'billing');
        
        // Названия полей на русском
        $field_labels = [
            'billing_first_name' => 'Имя',
            'billing_last_name' => 'Фамилия',
            'billing_company' => 'Компания',
            'billing_address_1' => 'Адрес (строка 1)',
            'billing_address_2' => 'Адрес (строка 2)',
            'billing_city' => 'Город',
            'billing_postcode' => 'Почтовый индекс',
            'billing_country' => 'Страна',
            'billing_state' => 'Область/регион',
            'billing_phone' => 'Телефон',
            'billing_email' => 'Email',
            'shipping_first_name' => 'Имя получателя',
            'shipping_last_name' => 'Фамилия получателя',
            'shipping_company' => 'Компания получателя',
            'shipping_address_1' => 'Адрес доставки (строка 1)',
            'shipping_address_2' => 'Адрес доставки (строка 2)',
            'shipping_city' => 'Город доставки',
            'shipping_postcode' => 'Почтовый индекс доставки',
            'shipping_country' => 'Страна доставки',
            'shipping_state' => 'Область/регион доставки',
            'order_comments' => 'Комментарии к заказу'
        ];
        ?>
        <div class="wrap neetrino-dashboard">
            <div class="neetrino-header">
                <div class="neetrino-header-content">
                    <div class="neetrino-header-icon">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <div class="neetrino-header-text">
                        <h1>Управление полями чекаута</h1>
                        <p class="neetrino-header-description">Настройте отображение и обязательность полей на странице оформления заказа WooCommerce</p>
                    </div>
                </div>
            </div>
            
            <div class="neetrino-content">
                <form method="post" action="">
                    <?php wp_nonce_field('save_checkout_fields', 'checkout_fields_nonce'); ?>
                    
                    <!-- Поля биллинга -->
                    <div class="neetrino-card">
                        <h2>
                            <i class="fa-solid fa-credit-card" style="color: #3498db; margin-right: 8px;"></i>
                            Платёжные данные
                        </h2>
                        <p class="neetrino-card-description">Настройка полей для платёжного адреса клиента</p>
                        
                        <div class="neetrino-fields-grid">
                            <?php foreach ($config['billing_fields'] as $field_key => $field_config): ?>
                                <div class="neetrino-field-item">
                                    <div class="neetrino-field-header">
                                        <label class="neetrino-toggle">
                                            <input type="checkbox" 
                                                   name="fields[<?php echo $field_key; ?>][enabled]" 
                                                   <?php checked($field_config['enabled']); ?>>
                                            <span class="neetrino-toggle-slider"></span>
                                        </label>
                                        <span class="neetrino-field-name"><?php echo $field_labels[$field_key]; ?></span>
                                    </div>
                                    <div class="neetrino-field-options">
                                        <label class="neetrino-checkbox">
                                            <input type="checkbox" 
                                                   name="fields[<?php echo $field_key; ?>][required]" 
                                                   <?php checked($field_config['required']); ?>>
                                            <span class="neetrino-checkbox-mark"></span>
                                            Обязательное поле
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Поля доставки -->
                    <div class="neetrino-card">
                        <h2>
                            <i class="fa-solid fa-shipping-fast" style="color: #1abc9c; margin-right: 8px;"></i>
                            Адрес доставки
                        </h2>
                        
                        <!-- Компактный переключатель режима доставки -->
                        <div class="neetrino-compact-shipping-toggle">
                            <div class="neetrino-compact-header">
                                <i class="fa-solid fa-location-dot"></i>
                                <span>Глобальный режим адреса доставки</span>
                            </div>
                            
                            <div class="neetrino-compact-toggle-group">
                                <input type="radio" id="ship_open" name="shipping_destination" value="shipping" <?php checked($current_shipping_destination, 'shipping'); ?>>
                                <label for="ship_open" class="neetrino-compact-option" data-state="open">
                                    <span class="neetrino-compact-icon">📭</span>
                                    <span class="neetrino-compact-text">Открыт</span>
                                </label>
                                
                                <input type="radio" id="ship_on" name="shipping_destination" value="billing" <?php checked($current_shipping_destination, 'billing'); ?>>
                                <label for="ship_on" class="neetrino-compact-option" data-state="on">
                                    <span class="neetrino-compact-icon">📫</span>
                                    <span class="neetrino-compact-text">Включён</span>
                                </label>
                                
                                <input type="radio" id="ship_off" name="shipping_destination" value="billing_only" <?php checked($current_shipping_destination, 'billing_only'); ?>>
                                <label for="ship_off" class="neetrino-compact-option" data-state="off">
                                    <span class="neetrino-compact-icon">📪</span>
                                    <span class="neetrino-compact-text">Отключён</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Контейнер для полей доставки -->
                        <div class="neetrino-shipping-fields-container" <?php echo ($current_shipping_destination === 'billing_only') ? 'style="display: none;"' : ''; ?>>
                            <div class="neetrino-fields-grid">
                                <?php foreach ($config['shipping_fields'] as $field_key => $field_config): ?>
                                    <div class="neetrino-field-item">
                                        <div class="neetrino-field-header">
                                            <label class="neetrino-toggle">
                                                <input type="checkbox" 
                                                       name="fields[<?php echo $field_key; ?>][enabled]" 
                                                       <?php checked($field_config['enabled']); ?>>
                                                <span class="neetrino-toggle-slider"></span>
                                            </label>
                                            <span class="neetrino-field-name"><?php echo $field_labels[$field_key]; ?></span>
                                        </div>
                                        <div class="neetrino-field-options">
                                            <label class="neetrino-checkbox">
                                                <input type="checkbox" 
                                                       name="fields[<?php echo $field_key; ?>][required]" 
                                                       <?php checked($field_config['required']); ?>>
                                                <span class="neetrino-checkbox-mark"></span>
                                                Обязательное поле
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Дополнительные поля -->
                    <div class="neetrino-card">
                        <h2>
                            <i class="fa-solid fa-comment" style="color: #f39c12; margin-right: 8px;"></i>
                            Дополнительная информация
                        </h2>
                        <p class="neetrino-card-description">Настройка дополнительных полей заказа</p>
                        
                        <div class="neetrino-fields-grid">
                            <?php foreach ($config['order_fields'] as $field_key => $field_config): ?>
                                <div class="neetrino-field-item">
                                    <div class="neetrino-field-header">
                                        <label class="neetrino-toggle">
                                            <input type="checkbox" 
                                                   name="fields[<?php echo $field_key; ?>][enabled]" 
                                                   <?php checked($field_config['enabled']); ?>>
                                            <span class="neetrino-toggle-slider"></span>
                                        </label>
                                        <span class="neetrino-field-name"><?php echo $field_labels[$field_key]; ?></span>
                                    </div>
                                    <div class="neetrino-field-options">
                                        <label class="neetrino-checkbox">
                                            <input type="checkbox" 
                                                   name="fields[<?php echo $field_key; ?>][required]" 
                                                   <?php checked($field_config['required']); ?>>
                                            <span class="neetrino-checkbox-mark"></span>
                                            Обязательное поле
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="neetrino-form-actions">
                        <button type="submit" name="save_checkout_fields" class="neetrino-btn neetrino-btn-primary">
                            <i class="fa-solid fa-save"></i>
                            Сохранить настройки
                        </button>
                        
                        <div class="neetrino-help-text">
                            <i class="fa-solid fa-info-circle"></i>
                            Изменения применятся к странице оформления заказа в WooCommerce
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <style>
        /* Новый дизайн заголовка */
        .neetrino-header-content {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 24px 0;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 24px;
        }
        
        .neetrino-header-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            color: white;
            font-size: 24px;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .neetrino-header-text h1 {
            margin: 0 0 8px 0;
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            line-height: 1.2;
        }
        
        .neetrino-header-description {
            margin: 0;
            font-size: 16px;
            color: #6c757d;
            line-height: 1.5;
        }
        
        /* Красивое уведомление об успехе */
        .neetrino-success-notice {
            background: linear-gradient(135deg, #48bb78, #38a169);
            border: none;
            border-radius: 12px;
            padding: 16px 20px;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
            animation: slideInDown 0.5s ease;
        }
        
        .neetrino-success-content {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            font-size: 15px;
            font-weight: 500;
        }
        
        .neetrino-success-content i {
            font-size: 18px;
            color: #e6fffa;
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .neetrino-fields-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .neetrino-field-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 16px;
            transition: all 0.3s ease;
        }
        
        .neetrino-field-item:hover {
            border-color: #9c88ff;
            box-shadow: 0 2px 8px rgba(156, 136, 255, 0.1);
        }
        
        .neetrino-field-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .neetrino-field-name {
            font-weight: 500;
            color: #2c3e50;
            flex: 1;
        }
        
        .neetrino-field-options {
            padding-left: 8px;
        }
        
        .neetrino-toggle {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 28px;
        }
        
        .neetrino-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .neetrino-toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 28px;
        }
        
        .neetrino-toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        .neetrino-toggle input:checked + .neetrino-toggle-slider {
            background-color: #9c88ff;
        }
        
        .neetrino-toggle input:checked + .neetrino-toggle-slider:before {
            transform: translateX(22px);
        }
        
        .neetrino-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #6c757d;
            cursor: pointer;
        }
        
        .neetrino-checkbox input {
            display: none;
        }
        
        .neetrino-checkbox-mark {
            width: 16px;
            height: 16px;
            border: 2px solid #dee2e6;
            border-radius: 3px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .neetrino-checkbox input:checked + .neetrino-checkbox-mark {
            background-color: #9c88ff;
            border-color: #9c88ff;
        }
        
        .neetrino-checkbox input:checked + .neetrino-checkbox-mark:after {
            content: "✓";
            position: absolute;
            top: -2px;
            left: 2px;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        
        .neetrino-form-actions {
            margin-top: 30px;
            text-align: center;
        }
        
        .neetrino-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .neetrino-btn-primary {
            background: linear-gradient(135deg, #9c88ff 0%, #8b7cf6 100%);
            color: white;
        }
        
        .neetrino-btn-primary:hover {
            background: linear-gradient(135deg, #8b7cf6 0%, #7c6ce8 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(156, 136, 255, 0.3);
        }
        
        .neetrino-help-text {
            margin-top: 16px;
            color: #6c757d;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        
        .neetrino-card-description {
            color: #6c757d;
            font-size: 14px;
            margin: 8px 0 0 0;
        }
        </style>
        
        <style>
        /* Принудительные стили для нового переключателя */
        .neetrino-compact-shipping-toggle {
            margin: 20px 0 !important;
            padding: 24px !important;
            background: #ffffff !important;
            border-radius: 16px !important;
            border: 1px solid #f0f0f0 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
        }

        .neetrino-compact-header {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            margin-bottom: 20px !important;
            font-weight: 600 !important;
            font-size: 16px !important;
            color: #1a1a1a !important;
        }

        .neetrino-compact-header i {
            color: #1abc9c !important;
            font-size: 18px !important;
        }

        .neetrino-compact-toggle-group {
            display: flex !important;
            gap: 12px !important;
            position: relative !important;
        }

        .neetrino-compact-toggle-group input[type="radio"] {
            display: none !important;
        }

        .neetrino-compact-option {
            flex: 1 !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            gap: 8px !important;
            padding: 20px 12px !important;
            border: 2px solid #f5f5f5 !important;
            border-radius: 12px !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            background: #fafafa !important;
            position: relative !important;
            min-height: 90px !important;
            justify-content: center !important;
        }

        .neetrino-compact-option:hover {
            border-color: #e0e0e0 !important;
            background: #f8f8f8 !important;
            transform: translateY(-2px) !important;
        }

        .neetrino-compact-icon {
            font-size: 24px !important;
            margin-bottom: 4px !important;
            transition: transform 0.2s ease !important;
        }

        .neetrino-compact-text {
            font-weight: 600 !important;
            font-size: 13px !important;
            color: #666 !important;
            text-align: center !important;
        }

        .neetrino-compact-toggle-group input[type="radio"]:checked + .neetrino-compact-option {
            background: #ffffff !important;
            border-color: #1abc9c !important;
            transform: translateY(-3px) !important;
            box-shadow: 0 8px 25px rgba(26, 188, 156, 0.15) !important;
        }

        .neetrino-compact-toggle-group input[type="radio"]:checked + .neetrino-compact-option .neetrino-compact-text {
            color: #1abc9c !important;
        }

        .neetrino-compact-toggle-group input[type="radio"]:checked + .neetrino-compact-option .neetrino-compact-icon {
            transform: scale(1.15) !important;
        }

        .neetrino-compact-toggle-group input[type="radio"]:checked + .neetrino-compact-option[data-state="open"] {
            background: rgba(0, 212, 170, 0.15) !important;
            border-color: #00d4aa !important;
            box-shadow: 0 8px 25px rgba(0, 212, 170, 0.25) !important;
        }

        .neetrino-compact-toggle-group input[type="radio"]:checked + .neetrino-compact-option[data-state="open"] .neetrino-compact-text {
            color: #00d4aa !important;
        }

        .neetrino-compact-toggle-group input[type="radio"]:checked + .neetrino-compact-option[data-state="on"] {
            background: rgba(52, 152, 219, 0.15) !important;
            border-color: #3498db !important;
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.25) !important;
        }

        .neetrino-compact-toggle-group input[type="radio"]:checked + .neetrino-compact-option[data-state="on"] .neetrino-compact-text {
            color: #3498db !important;
        }

        .neetrino-compact-toggle-group input[type="radio"]:checked + .neetrino-compact-option[data-state="off"] {
            background: rgba(231, 76, 60, 0.15) !important;
            border-color: #e74c3c !important;
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.25) !important;
        }

        .neetrino-compact-toggle-group input[type="radio"]:checked + .neetrino-compact-option[data-state="off"] .neetrino-compact-text {
            color: #e74c3c !important;
        }
        </style>
        <?php
    }
}

// Инициализация модуля
new Neetrino_Checkout_Fields();

<?php
/**
 * Интерфейс настроек модуля доставки в админке
 */

if (!defined('ABSPATH')) {
    exit;
}

function render_delivery_admin_interface() {
    $settings = get_option('neetrino_delivery_settings', [
        'google_api_key' => '',
        'shop_address' => '',
        'price_per_km' => 1,
        'min_delivery_cost' => 0,
        'max_delivery_cost' => 100,
        'free_delivery_from' => 0,
        'enable_autocomplete' => true,
        'enable_geolocation' => true,
        'allowed_countries' => [],
        'cache_duration' => 7
    ]);
    
    // Обработка сохранения настроек
    if (isset($_POST['save_delivery_settings']) && wp_verify_nonce($_POST['delivery_nonce'], 'save_delivery_settings')) {
        $settings['google_api_key'] = sanitize_text_field($_POST['google_api_key'] ?? '');
        $settings['shop_address'] = sanitize_textarea_field($_POST['shop_address'] ?? '');
        $settings['price_per_km'] = floatval($_POST['price_per_km'] ?? 1);
        $settings['min_delivery_cost'] = floatval($_POST['min_delivery_cost'] ?? 0);
        $settings['max_delivery_cost'] = floatval($_POST['max_delivery_cost'] ?? 100);
        $settings['free_delivery_from'] = floatval($_POST['free_delivery_from'] ?? 0);
        $settings['enable_autocomplete'] = isset($_POST['enable_autocomplete']);
        $settings['enable_geolocation'] = isset($_POST['enable_geolocation']);
        $settings['cache_duration'] = absint($_POST['cache_duration'] ?? 7);
        
        // Обработка разрешенных стран
        if (isset($_POST['allowed_countries']) && is_array($_POST['allowed_countries'])) {
            $settings['allowed_countries'] = array_map('sanitize_text_field', $_POST['allowed_countries']);
        }
        
        update_option('neetrino_delivery_settings', $settings);
        
        echo '<div class="notice notice-success"><p>✅ Настройки сохранены!</p></div>';
    }
    
    // Обработка тестирования API
    $test_result = null;
    if (isset($_POST['test_api']) && wp_verify_nonce($_POST['delivery_nonce'], 'save_delivery_settings')) {
        $test_result = test_google_api($settings);
    }
    
    // Обработка очистки кэша
    if (isset($_POST['clear_cache']) && wp_verify_nonce($_POST['delivery_nonce'], 'save_delivery_settings')) {
        $calculator = new Neetrino_Delivery_Calculator($settings);
        $calculator->clear_cache();
        echo '<div class="notice notice-success"><p>✅ Кэш очищен!</p></div>';
    }
    
    // Получаем список стран WooCommerce
    $wc_countries = [];
    if (function_exists('WC') && WC()->countries) {
        $wc_countries = WC()->countries->get_countries();
    }
    
    // Получаем валюту
    $currency = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'USD';
    $currency_symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '$';
    
    ?>
    <div class="wrap neetrino-dashboard">
        <div class="neetrino-header">
            <div class="neetrino-header-left">
                <h1>🚚 Настройки доставки</h1>
                <p>Автозаполнение адресов и расчет стоимости доставки через Google API</p>
            </div>
            <div class="neetrino-header-right">
                <span class="neetrino-version">v1.0.0</span>
            </div>
        </div>

        <div class="neetrino-content">
            
            <!-- Статус модуля -->
            <div class="neetrino-card">
                <h2>📊 Статус модуля</h2>
                <div class="delivery-status-grid">
                    <div class="status-item">
                        <span class="status-label">Google API:</span>
                        <span class="status-value <?php echo !empty($settings['google_api_key']) ? 'status-ok' : 'status-error'; ?>">
                            <?php echo !empty($settings['google_api_key']) ? '✅ Настроен' : '❌ Не настроен'; ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Адрес магазина:</span>
                        <span class="status-value <?php echo !empty($settings['shop_address']) ? 'status-ok' : 'status-error'; ?>">
                            <?php echo !empty($settings['shop_address']) ? '✅ Указан' : '❌ Не указан'; ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">WooCommerce:</span>
                        <span class="status-value <?php echo class_exists('WooCommerce') ? 'status-ok' : 'status-error'; ?>">
                            <?php echo class_exists('WooCommerce') ? '✅ Активен' : '❌ Не активен'; ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Валюта:</span>
                        <span class="status-value status-info"><?php echo esc_html($currency . ' (' . $currency_symbol . ')'); ?></span>
                    </div>
                </div>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('save_delivery_settings', 'delivery_nonce'); ?>
                
                <!-- Настройки Google API -->
                <div class="neetrino-card">
                    <h2>🗝️ Настройки Google API</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Google API ключ</th>
                            <td>
                                <input type="text" name="google_api_key" value="<?php echo esc_attr($settings['google_api_key']); ?>" 
                                       class="regular-text" placeholder="AIzaSyB...">
                                <p class="description">
                                    Получите ключ в <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a>. 
                                    Включите API: Places, Distance Matrix, Geocoding.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Адрес магазина</th>
                            <td>
                                <textarea name="shop_address" rows="3" class="large-text" 
                                          placeholder="ул. Примерная, 123, Москва, Россия"><?php echo esc_textarea($settings['shop_address']); ?></textarea>
                                <p class="description">Полный адрес вашего магазина/склада для расчета доставки</p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php if (!empty($settings['google_api_key'])): ?>
                    <p>
                        <button type="submit" name="test_api" class="button button-secondary">🧪 Тестировать API</button>
                    </p>
                    
                    <?php if ($test_result): ?>
                        <div class="notice notice-<?php echo $test_result['success'] ? 'success' : 'error'; ?> inline">
                            <p><?php echo esc_html($test_result['message']); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Настройки расчета стоимости -->
                <div class="neetrino-card">
                    <h2>💰 Настройки расчета стоимости</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Цена за километр</th>
                            <td>
                                <input type="number" name="price_per_km" value="<?php echo esc_attr($settings['price_per_km']); ?>" 
                                       step="0.01" min="0" class="small-text"> <?php echo esc_html($currency_symbol); ?>
                                <p class="description">Базовая стоимость доставки за 1 км</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Минимальная стоимость</th>
                            <td>
                                <input type="number" name="min_delivery_cost" value="<?php echo esc_attr($settings['min_delivery_cost']); ?>" 
                                       step="0.01" min="0" class="small-text"> <?php echo esc_html($currency_symbol); ?>
                                <p class="description">Минимальная стоимость доставки (0 = без ограничений)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Максимальная стоимость</th>
                            <td>
                                <input type="number" name="max_delivery_cost" value="<?php echo esc_attr($settings['max_delivery_cost']); ?>" 
                                       step="0.01" min="0" class="small-text"> <?php echo esc_html($currency_symbol); ?>
                                <p class="description">Максимальная стоимость доставки (0 = без ограничений)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Бесплатная доставка от</th>
                            <td>
                                <input type="number" name="free_delivery_from" value="<?php echo esc_attr($settings['free_delivery_from']); ?>" 
                                       step="0.01" min="0" class="small-text"> <?php echo esc_html($currency_symbol); ?>
                                <p class="description">Сумма заказа для бесплатной доставки (0 = отключено)</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Настройки автозаполнения -->
                <div class="neetrino-card">
                    <h2>🗺️ Настройки автозаполнения</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Автозаполнение адресов</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_autocomplete" <?php checked($settings['enable_autocomplete']); ?>>
                                    Включить автозаполнение адресов через Google Places
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Геолокация</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_geolocation" <?php checked($settings['enable_geolocation']); ?>>
                                    Разрешить определение местоположения пользователя
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Разрешенные страны</th>
                            <td>
                                <select name="allowed_countries[]" multiple class="large-text" style="height: 120px;">
                                    <?php foreach ($wc_countries as $code => $name): ?>
                                        <option value="<?php echo esc_attr($code); ?>" 
                                                <?php selected(in_array($code, $settings['allowed_countries'])); ?>>
                                            <?php echo esc_html($name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    Страны для автозаполнения. Если не выбрано, используются настройки WooCommerce. 
                                    <br>Удерживайте Ctrl для множественного выбора.
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Настройки производительности -->
                <div class="neetrino-card">
                    <h2>⚡ Настройки производительности</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Кэширование</th>
                            <td>
                                <input type="number" name="cache_duration" value="<?php echo esc_attr($settings['cache_duration']); ?>" 
                                       min="1" max="30" class="small-text"> дней
                                <p class="description">Время хранения результатов расчета в кэше</p>
                                <p>
                                    <button type="submit" name="clear_cache" class="button button-secondary">🗑️ Очистить кэш</button>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Кнопка сохранения -->
                <div class="neetrino-card">
                    <p class="submit">
                        <button type="submit" name="save_delivery_settings" class="button button-primary button-large">
                            💾 Сохранить настройки
                        </button>
                    </p>
                </div>
            </form>

            <!-- Справочная информация -->
            <div class="neetrino-card">
                <h2>📚 Справочная информация</h2>
                <div class="delivery-help">
                    <h3>🚀 Как настроить модуль:</h3>
                    <ol>
                        <li><strong>Получите Google API ключ:</strong> перейдите в <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a></li>
                        <li><strong>Включите необходимые API:</strong> Places API, Distance Matrix API, Geocoding API</li>
                        <li><strong>Укажите адрес магазина</strong> для расчета доставки</li>
                        <li><strong>Настройте стоимость</strong> доставки и ограничения</li>
                        <li><strong>Добавьте метод доставки</strong> в зонах доставки WooCommerce</li>
                    </ol>

                    <h3>⚙️ Настройка в WooCommerce:</h3>
                    <p>После сохранения настроек перейдите в <strong>WooCommerce → Настройки → Доставка</strong> и добавьте метод "Neetrino Delivery" в нужные зоны доставки.</p>

                    <h3>💡 Функции модуля:</h3>
                    <ul>
                        <li>✅ Автозаполнение адресов при вводе</li>
                        <li>✅ Автоматический расчет стоимости доставки</li>
                        <li>✅ Определение местоположения пользователя</li>
                        <li>✅ Кэширование результатов</li>
                        <li>✅ Настройка бесплатной доставки</li>
                        <li>✅ Поддержка любых валют</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <style>
    .delivery-status-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }
    
    .status-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        background: #f9f9f9;
        border-radius: 4px;
    }
    
    .status-label {
        font-weight: 500;
    }
    
    .status-ok { color: #46b450; }
    .status-error { color: #dc3232; }
    .status-info { color: #0073aa; }
    
    .delivery-help h3 {
        margin-top: 20px;
        color: #1abc9c;
    }
    
    .delivery-help ul, .delivery-help ol {
        margin-left: 20px;
    }
    
    .notice.inline {
        margin: 15px 0;
        padding: 8px 12px;
    }
    </style>
    <?php
}

/**
 * Тестирование Google API
 */
function test_google_api($settings) {
    if (empty($settings['google_api_key'])) {
        return ['success' => false, 'message' => 'API ключ не указан'];
    }
    
    // Тест геокодирования
    $test_address = 'Красная площадь, Москва, Россия';
    $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
        'address' => $test_address,
        'key' => $settings['google_api_key']
    ]);
    
    $response = wp_remote_get($url, ['timeout' => 10]);
    
    if (is_wp_error($response)) {
        return ['success' => false, 'message' => 'Ошибка соединения: ' . $response->get_error_message()];
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!$data) {
        return ['success' => false, 'message' => 'Некорректный ответ от Google API'];
    }
    
    if ($data['status'] === 'REQUEST_DENIED') {
        return ['success' => false, 'message' => 'Доступ запрещен. Проверьте API ключ и включенные сервисы'];
    }
    
    if ($data['status'] === 'OVER_QUERY_LIMIT') {
        return ['success' => false, 'message' => 'Превышен лимит запросов к API'];
    }
    
    if ($data['status'] !== 'OK') {
        return ['success' => false, 'message' => 'Ошибка API: ' . $data['status']];
    }
    
    return ['success' => true, 'message' => 'API работает корректно! Тестовый адрес успешно найден.'];
}

<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Общий класс для регистрации плагина с Dashboard
 * Используется как при активации, так и при ручной перерегистрации
 */
class Neetrino_Registration {
    
    /**
     * Единственный метод регистрации - используется везде
     */
    public static function register_with_dashboard() {
        error_log("NEETRINO Registration: Starting registration process");
        
        try {
            // Очищаем старые данные
            self::clear_old_registration_data();
            
            // Генерируем временный ключ
            $temp_key = 'temp_' . bin2hex(random_bytes(16));
            update_option('neetrino_temp_registration_key', $temp_key);
            
            // URL дашборда: используем сохранённый домен (после регистрации) или дефолт
            $saved_domain = get_option('neetrino_dashboard_domain');
            if (!empty($saved_domain)) {
                // Для локалки используем http
                $scheme = (strpos($saved_domain, 'localhost') !== false || strpos($saved_domain, '127.0.0.1') !== false) ? 'http' : 'https';
                $dashboard_url = $scheme . '://' . rtrim($saved_domain, '/');
            } else {
                $dashboard_url = Neetrino_Dashboard_Connect::get_dashboard_url();
            }
            
            error_log("NEETRINO Registration: Using Dashboard URL: " . $dashboard_url);
            
            // Данные для регистрации
            $registration_data = [
                'action' => 'register_site',
                'site_url' => get_site_url(),
                'admin_email' => get_option('admin_email'),
                'site_title' => get_option('blogname'),
                'plugin_version' => (defined('NEETRINO_VERSION') ? NEETRINO_VERSION : ''),
                'temp_key' => $temp_key,
                'request_time' => time()
            ];
            
            error_log("NEETRINO Registration: Sending request with temp_key: " . substr($temp_key, 0, 10) . "...");
            
            // Отправляем запрос регистрации
            $response = wp_remote_post(rtrim($dashboard_url, '/') . '/api.php', [
                'body' => $registration_data,
                'timeout' => 30,
                'sslverify' => false
            ]);
            
            if (is_wp_error($response)) {
                $error_msg = 'Connection failed: ' . $response->get_error_message();
                error_log('NEETRINO Registration: ' . $error_msg);
                update_option('neetrino_last_registration_error', $error_msg);
                return [
                    'success' => false,
                    'error' => $error_msg
                ];
            }
            
            $response_body = wp_remote_retrieve_body($response);
            $result = json_decode($response_body, true);
            
            if (!$result) {
                $error_msg = 'Invalid response from Dashboard';
                error_log('NEETRINO Registration: ' . $error_msg);
                update_option('neetrino_last_registration_error', $error_msg);
                return [
                    'success' => false,
                    'error' => $error_msg
                ];
            }
            
            error_log("NEETRINO Registration: Dashboard response: " . print_r($result, true));
            
            if (isset($result['status']) && $result['status'] === 'success') {
                error_log("NEETRINO Registration: Registration successful");
                
                // Сохраняем полученную конфигурацию
                if (isset($result['dashboard_ip'])) {
                    update_option('neetrino_dashboard_ip', $result['dashboard_ip']);
                }
                if (isset($result['dashboard_domain'])) {
                    update_option('neetrino_dashboard_domain', $result['dashboard_domain']);
                }
                if (isset($result['api_key'])) {
                    update_option('neetrino_dashboard_api_key', $result['api_key']);
                    error_log("NEETRINO Registration: API Key saved: " . substr($result['api_key'], 0, 10) . "...");
                }
                
                // Устанавливаем статус регистрации
                update_option('neetrino_registration_status', 'registered');
                delete_option('neetrino_temp_registration_key');
                delete_option('neetrino_last_registration_error');
                delete_option('neetrino_last_registration_attempt'); // Очищаем флаг попыток
                
                error_log('NEETRINO Registration: Plugin registered successfully');
                
                // Attempt to push current plugin version right after successful registration (force)
                if (method_exists(__CLASS__, 'push_version_if_changed')) {
                    self::push_version_if_changed(true);
                }

                return [
                    'success' => true,
                    'message' => 'Successfully registered with Dashboard'
                ];
                
            } else {
                $error_message = $result['error'] ?? 'Registration failed';
                error_log('NEETRINO Registration: Dashboard registration failed: ' . $error_message);
                update_option('neetrino_last_registration_error', $error_message);
                return [
                    'success' => false,
                    'error' => $error_message
                ];
            }
            
        } catch (Exception $e) {
            $error_msg = 'Exception: ' . $e->getMessage();
            error_log('NEETRINO Registration: ' . $error_msg);
            update_option('neetrino_last_registration_error', $error_msg);
            return [
                'success' => false,
                'error' => $error_msg
            ];
        }
    }

    /**
     * Пуш версии плагина на Dashboard при изменении
     * @param bool $force Принудительная отправка
     * @param string|null $specific_version Конкретная версия для отправки (если null, берется из константы)
     */
    public static function push_version_if_changed($force = false, $specific_version = null) {
        try {
            error_log('NEETRINO Version Push: push_version_if_changed() вызван с force=' . ($force ? 'true' : 'false') . ', specific_version=' . ($specific_version ?: 'null'));
            
            // Определяем версию для отправки
            if ($specific_version !== null) {
                $current_version = $specific_version;
                error_log('NEETRINO Version Push: Используем переданную версию: ' . $current_version);
            } elseif (defined('NEETRINO_VERSION')) {
                $current_version = NEETRINO_VERSION;
                error_log('NEETRINO Version Push: Используем версию из константы: ' . $current_version);
            } else {
                error_log('NEETRINO Version Push: NEETRINO_VERSION не определена и specific_version не передан, выходим');
                return; // Нет версии — выходим тихо
            }
            $stored_version = get_option('neetrino_reported_version');
            
            error_log('NEETRINO Version Push: Текущая версия: ' . $current_version . ', Сохраненная версия: ' . ($stored_version ?: 'null'));

            // Если версия не изменилась и не принудительно — выходим
            if (!$force && $stored_version === $current_version) {
                error_log('NEETRINO Version Push: Версия не изменилась и не принудительно, выходим');
                return;
            }

            // Готовим данные
            // URL дашборда: домен из настроек, иначе дефолт
            $saved_domain = get_option('neetrino_dashboard_domain');
            error_log('NEETRINO Version Push: Сохраненный домен: ' . ($saved_domain ?: 'null'));
            
            if (!empty($saved_domain)) {
                $scheme = (strpos($saved_domain, 'localhost') !== false || strpos($saved_domain, '127.0.0.1') !== false) ? 'http' : 'https';
                $dashboard_url = $scheme . '://' . rtrim($saved_domain, '/');
            } else {
                $dashboard_url = Neetrino_Dashboard_Connect::get_dashboard_url();
            }
            
            error_log('NEETRINO Version Push: URL дашборда: ' . $dashboard_url);
            
            $site_url = get_site_url();
            $api_key = get_option('neetrino_dashboard_api_key');
            
            error_log('NEETRINO Version Push: URL сайта: ' . $site_url . ', API ключ: ' . ($api_key ? 'установлен' : 'не установлен'));
            
            // Определяем статус плагина
            $plugin_status = 'active';
            if (!is_plugin_active(plugin_basename(NEETRINO_PLUGIN_FILE))) {
                $plugin_status = 'inactive';
            }
            
            $payload = [
                'action' => 'plugin_version_push',
                'site_url' => $site_url,
                'plugin_version' => $current_version,
                'plugin_status' => $plugin_status,
                'timestamp' => time(),
                'update_type' => $force ? 'forced' : 'automatic',
                // Базовая защита на первом этапе — API ключ (усилим позже подписью)
                'api_key' => $api_key,
            ];

            error_log('NEETRINO Version Push: Payload: ' . json_encode($payload));
            error_log('NEETRINO Version Push: Sending version ' . $current_version . ' with status ' . $plugin_status . ' to dashboard');

            // Отправляем на Dashboard
            $final_url = rtrim($dashboard_url, '/') . '/api.php';
            error_log('NEETRINO Version Push: Отправляем POST запрос на: ' . $final_url);
            
            $response = wp_remote_post($final_url, [
                'body' => $payload,
                'timeout' => 15,
                'sslverify' => false,
            ]);

            if (is_wp_error($response)) {
                error_log('NEETRINO Version Push: failed — ' . $response->get_error_message());
                return;
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $json = json_decode($body, true);

            if ($code === 200 && is_array($json) && !empty($json['success'])) {
                update_option('neetrino_reported_version', $current_version);
                update_option('neetrino_reported_version_at', time());
                error_log('NEETRINO Version Push: success — ' . $current_version . ' sent to dashboard');
            } else {
                error_log('NEETRINO Version Push: unexpected response (' . $code . '): ' . $body);
            }
        } catch (Exception $e) {
            error_log('NEETRINO Version Push: exception — ' . $e->getMessage());
        }
    }
    
    /**
     * Очистка старых данных регистрации
     */
    private static function clear_old_registration_data() {
        delete_option('neetrino_dashboard_registered');
        delete_option('neetrino_site_id');
        delete_option('neetrino_dashboard_url');
        delete_option('neetrino_dashboard_ip');
        delete_option('neetrino_dashboard_api_key');
        delete_option('neetrino_dashboard_domain');
        delete_option('neetrino_registration_status');
        delete_option('neetrino_temp_registration_key');
        wp_clear_scheduled_hook('neetrino_dashboard_ping');
    }
}

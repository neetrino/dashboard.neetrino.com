<?php
/**
 * Module: Redirect 301
 * Description: Перенаправление пользователей на разные сайты в зависимости от страны
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Redirect_301 {
    
    private $default_action = 'stay'; // 'stay' или 'redirect'
    private $default_redirect_url = '';
    private $country_rules = [];
    private $enable_logging = false;
    private $exclude_admin_users = true;
    private $excluded_user_agents = [];
    private $geo_services = [
        'http://ip-api.com/json/{ip}?fields=countryCode,country',
        'https://ipapi.co/{ip}/json/',
        'http://www.geoplugin.net/json.gp?ip={ip}',
        'https://freegeoip.app/json/{ip}'
    ];
    
    public function __construct() {
        // Инициализация модуля только если он активен
        if (!Neetrino::is_module_active('redirect-301')) {
            return;
        }
        
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        
        // Загружаем настройки
        $this->load_settings();
        
        // Запускаем проверку перенаправления
        add_action('template_redirect', [$this, 'check_redirect'], 1);
        
        // Добавляем AJAX обработчики
        add_action('wp_ajax_neetrino_test_redirect_ip', [$this, 'ajax_test_ip']);
        add_action('wp_ajax_neetrino_clear_redirect_cache', [$this, 'ajax_clear_geo_cache']);
    }
    
    /**
     * Инициализация модуля
     */
    public function init() {
        // Регистрируем настройки
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    /**
     * Загрузка настроек из базы данных
     */    private function load_settings() {
        $settings = get_option('neetrino_redirect_301_settings', []);
        
        $this->default_action = $settings['default_action'] ?? 'redirect';
        $this->default_redirect_url = $settings['default_redirect_url'] ?? '';
        $this->country_rules = $settings['country_rules'] ?? [];
        $this->enable_logging = $settings['enable_logging'] ?? false;
        $this->exclude_admin_users = $settings['exclude_admin_users'] ?? true;
        
        // Исключаемые User-Agent (боты, поисковики)
        $this->excluded_user_agents = $settings['excluded_user_agents'] ?? [
            'Googlebot', 'Bingbot', 'YandexBot', 'facebookexternalhit',
            'Twitterbot', 'crawler', 'spider', 'bot', 'crawl'
        ];
    }
    
    /**
     * Регистрация настроек
     */
    public function register_settings() {
        register_setting('neetrino_redirect_301_settings', 'neetrino_redirect_301_settings');
    }
    
    /**
     * Основная функция проверки и выполнения перенаправления
     */
    public function check_redirect() {
        // Проверяем исключения
        if ($this->should_exclude_user()) {
            return;
        }
        
        // Получаем страну пользователя
        $country = $this->get_user_country();
        
        if (!$country) {
            return;
        }
        
        // Проверяем правила для конкретной страны
        $redirect_url = $this->get_redirect_url_for_country($country);
        
        if ($redirect_url) {
            $this->log_redirect($country, $redirect_url);
            $this->perform_redirect($redirect_url);
        }
    }
    
    /**
     * Проверяет, нужно ли исключить пользователя из перенаправления
     */
    private function should_exclude_user() {
        // Исключаем админов
        if ($this->exclude_admin_users && current_user_can('manage_options')) {
            return true;
        }
        
        // Исключаем ботов
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        foreach ($this->excluded_user_agents as $excluded_agent) {
            if (stripos($user_agent, $excluded_agent) !== false) {
                return true;
            }
        }
        
        // Исключаем админку
        if (is_admin()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Получает страну пользователя по IP
     */
    private function get_user_country() {
        $user_ip = $this->get_user_ip();
        
        if (!$user_ip) {
            return null;
        }
        
        // Проверяем кеш
        $cache_key = 'neetrino_country_' . md5($user_ip);
        $cached_country = get_transient($cache_key);
        
        if ($cached_country !== false) {
            return $cached_country === 'none' ? null : $cached_country;
        }
        
        // Получаем страну через геосервисы
        $country = $this->detect_country_by_ip($user_ip);
        
        // Кешируем результат на 24 часа
        set_transient($cache_key, $country ?: 'none', 24 * HOUR_IN_SECONDS);
        
        return $country;
    }
    
    /**
     * Получает IP адрес пользователя
     */
    private function get_user_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }
    
    /**
     * Определяет страну по IP через геосервисы
     */
    private function detect_country_by_ip($ip) {
        foreach ($this->geo_services as $service) {
            $url = str_replace('{ip}', $ip, $service);
            
            $response = wp_remote_get($url, [
                'timeout' => 5,
                'headers' => [
                    'User-Agent' => 'WordPress/Neetrino'
                ]
            ]);
            
            if (is_wp_error($response)) {
                continue;
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!$data) {
                continue;
            }
            
            // Обрабатываем разные форматы ответов
            $country_code = null;
            
            if (isset($data['countryCode'])) {
                $country_code = $data['countryCode'];
            } elseif (isset($data['country_code'])) {
                $country_code = $data['country_code'];
            } elseif (isset($data['geoplugin_countryCode'])) {
                $country_code = $data['geoplugin_countryCode'];
            }
            
            if ($country_code && strlen($country_code) === 2) {
                return strtoupper($country_code);
            }
        }
        
        return null;
    }
    
    /**
     * Получает URL для перенаправления для указанной страны
     */
    private function get_redirect_url_for_country($country) {
        // Проверяем правила для конкретной страны
        foreach ($this->country_rules as $rule) {
            if ($rule['country'] === $country) {
                if ($rule['action'] === 'redirect' && !empty($rule['url'])) {
                    return $rule['url'];
                } elseif ($rule['action'] === 'stay') {
                    return null; // Остаемся на сайте
                }
            }
        }
        
        // Применяем настройки по умолчанию
        if ($this->default_action === 'redirect' && !empty($this->default_redirect_url)) {
            return $this->default_redirect_url;
        }
        
        return null;
    }
    
    /**
     * Выполняет перенаправление
     */
    private function perform_redirect($url) {
        // Добавляем протокол если отсутствует
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }
        
        // Валидация URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return;
        }
        
        // Выполняем 301 редирект
        wp_redirect($url, 301);
        exit;
    }
    
    /**
     * Логирование перенаправлений
     */
    private function log_redirect($country, $url) {
        if (!$this->enable_logging) {
            return;
        }
        
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'ip' => $this->get_user_ip(),
            'country' => $country,
            'redirect_url' => $url,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        $logs = get_option('neetrino_redirect_301_logs', []);
        array_unshift($logs, $log_entry);
        
        // Ограничиваем количество записей в логе
        $logs = array_slice($logs, 0, 1000);
        
        update_option('neetrino_redirect_301_logs', $logs);
    }
    
    /**
     * Подключение скриптов
     */
    public function enqueue_scripts() {
        // Пока не требуется
    }
    
    /**
     * Подключение админских скриптов
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'neetrino') === false) {
            return;
        }
        
        wp_enqueue_style(
            'neetrino-redirect-301-admin',
            plugin_dir_url(__FILE__) . 'assets/admin.css',
            [],
            '1.0.0'
        );
        
        wp_enqueue_script(
            'neetrino-redirect-301-admin',
            plugin_dir_url(__FILE__) . 'assets/admin.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('neetrino-redirect-301-admin', 'neetrinoRedirect301', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('neetrino_redirect_301_ajax'),
        ]);
    }
    
    /**
     * AJAX тестирование IP
     */
    public function ajax_test_ip() {
        check_ajax_referer('neetrino_redirect_301_ajax', 'nonce');
        
        $ip = sanitize_text_field($_POST['ip'] ?? '');
        
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            wp_send_json_error('Некорректный IP адрес');
        }
        
        $country = $this->detect_country_by_ip($ip);
        $redirect_url = $country ? $this->get_redirect_url_for_country($country) : null;
        
        wp_send_json_success([
            'country' => $country,
            'redirect_url' => $redirect_url,
            'action' => $redirect_url ? 'redirect' : 'stay'
        ]);
    }
    
    /**
     * AJAX очистка кеша
     */
    public function ajax_clear_geo_cache() {
        check_ajax_referer('neetrino_redirect_301_ajax', 'nonce');
        
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_neetrino_country_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_neetrino_country_%'");
        
        wp_send_json_success('Кеш очищен');
    }
    
    /**
     * Статический метод для админ-страницы
     * Вызывается автоматически если модуль активен
     */
    public static function admin_page() {        // Обработка сохранения настроек
        if (isset($_POST['submit']) && check_admin_referer('neetrino_redirect_301_settings')) {            // Обработка URL - добавляем протокол если нужно
            $redirect_url = sanitize_text_field($_POST['default_redirect_url'] ?? '');
            if ($redirect_url && !preg_match('/^https?:\/\//', $redirect_url)) {
                $redirect_url = 'https://' . $redirect_url;
            }
            
            $settings = [
                'default_action' => sanitize_text_field($_POST['default_action'] ?? 'redirect'),
                'default_redirect_url' => esc_url_raw($redirect_url),
                'country_rules' => [],
                'enable_logging' => $_POST['enable_logging'] === '1',
                'exclude_admin_users' => $_POST['exclude_admin_users'] === '1',
            ];
            
            // Обработка правил для стран
            if (isset($_POST['country_rules']) && is_array($_POST['country_rules'])) {
                foreach ($_POST['country_rules'] as $rule) {
                    if (!empty($rule['country'])) {
                        $settings['country_rules'][] = [
                            'country' => sanitize_text_field($rule['country']),
                            'action' => sanitize_text_field($rule['action']),
                            'url' => esc_url_raw($rule['url'] ?? '')
                        ];
                    }
                }
            }
            
            update_option('neetrino_redirect_301_settings', $settings);
            echo '<div class="notice notice-success"><p>Настройки сохранены!</p></div>';
        }
          // Загружаем текущие настройки
        $settings = get_option('neetrino_redirect_301_settings', []);
        $default_action = $settings['default_action'] ?? 'redirect';
        $default_redirect_url = $settings['default_redirect_url'] ?? '';
        $country_rules = $settings['country_rules'] ?? [];
        $enable_logging = $settings['enable_logging'] ?? false;
        $exclude_admin_users = $settings['exclude_admin_users'] ?? true;
        
        // Подключаем страницу администрирования
        include_once(__DIR__ . '/admin-page.php');
    }
}

// Инициализация модуля
new Neetrino_Redirect_301();

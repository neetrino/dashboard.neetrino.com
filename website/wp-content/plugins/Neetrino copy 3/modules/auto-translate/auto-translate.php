<?php
/**
 * Module: Auto Translate
 * Description: Автоматическое определение языка по стране пользователя с минимальными настройками
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Auto_Translate {
    
    private $default_language = 'en';
    private $country_languages = [];
    private $excluded_user_agents = [];
    private $enable_logging = false;
    private $exclude_admin_users = true;
    private $geo_services = [
        'http://ip-api.com/json/{ip}?fields=countryCode,country',
        'https://ipapi.co/{ip}/json/',
        'http://www.geoplugin.net/json.gp?ip={ip}',
        'https://freegeoip.app/json/{ip}'
    ];
    
    public function __construct() {
        // Инициализация модуля только если он активен
        if (!Neetrino::is_module_active('auto-translate')) {
            return;
        }
        
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        
        // Загружаем настройки
        $this->load_settings();
        
        // Запускаем автоматическое определение языка
        add_action('template_redirect', [$this, 'auto_detect_language'], 1);
        
        // Добавляем AJAX обработчики для тестирования
        add_action('wp_ajax_neetrino_test_ip', [$this, 'ajax_test_ip']);
        add_action('wp_ajax_neetrino_clear_geo_cache', [$this, 'ajax_clear_geo_cache']);
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
     */
    private function load_settings() {
        $settings = get_option('neetrino_auto_translate_settings', []);
        
        // Автоматическое определение основного языка сайта
        $site_language = $this->detect_site_language();
        
        $this->default_language = $settings['default_language'] ?? $site_language;
        $this->country_languages = $settings['country_languages'] ?? [];
        $this->enable_logging = $settings['enable_logging'] ?? false;
        $this->exclude_admin_users = $settings['exclude_admin_users'] ?? true;
        
        // Исключаемые User-Agent (боты, поисковики)
        $this->excluded_user_agents = $settings['excluded_user_agents'] ?? [
            'Googlebot', 'Bingbot', 'YandexBot', 'facebookexternalhit',
            'Twitterbot', 'crawler', 'spider', 'bot', 'crawl'
        ];        // Настройки по умолчанию - все языковые переадресации должны быть настроены вручную
        // $this->country_languages остается пустым массивом для ручной настройки всех переадресаций
        // Пользователь сам выберет все необходимые страны и языки для редиректа
    }
    
    /**
     * Автоматическое определение основного языка сайта
     */
    private function detect_site_language() {
        // Метод 1: Из настроек WordPress
        $wp_locale = get_locale();
        $language_code = substr($wp_locale, 0, 2);        // Метод 2: Проверяем активные языковые плагины
        if (function_exists('pll_default_language')) {
            // Polylang - динамический вызов функции
            $default_lang = call_user_func('pll_default_language');
            if ($default_lang) {
                return $default_lang;
            }
        } elseif (defined('ICL_SITEPRESS_VERSION') && function_exists('apply_filters')) {
            // WPML через фильтры (безопасный способ)
            $default_lang = apply_filters('wpml_default_language', null);
            if ($default_lang) {
                return $default_lang;
            }
        } elseif (class_exists('SitePress') && isset($GLOBALS['sitepress'])) {
            // WPML (альтернативный способ)
            $sitepress = $GLOBALS['sitepress'];
            if (method_exists($sitepress, 'get_default_language')) {
                $default_lang = $sitepress->get_default_language();
                if ($default_lang) {
                    return $default_lang;
                }
            }
        }
        
        // Метод 3: Из URL структуры (если есть языковые папки)
        $home_url = home_url();
        if (preg_match('/\/([a-z]{2})\//', $home_url, $matches)) {
            return $matches[1];
        }
        
        // Метод 4: По умолчанию из WordPress locale
        $locale_map = [
            'ru_RU' => 'ru',
            'en_US' => 'en', 
            'en_GB' => 'en',
            'uk' => 'uk',
            'hy' => 'hy',
            'ka_GE' => 'ka',
            'de_DE' => 'de',
            'fr_FR' => 'fr',
            'es_ES' => 'es',
            'it_IT' => 'it'
        ];
        
        return $locale_map[$wp_locale] ?? $language_code ?? 'en';
    }
      /**
     * Получение URL для конкретного языка
     */
    public function get_language_url($language_code) {
        $base_url = home_url('/');
        $site_language = $this->detect_site_language();
        
        // Если это основной язык сайта (из WordPress), возвращаем корневой URL
        if ($language_code === $site_language) {
            return $base_url;
        }
        
        // Для всех остальных языков добавляем языковой префикс
        return $base_url . $language_code . '/';
    }
    
    /**
     * Получение информации о текущих настройках (для отладки)
     */
    public static function get_debug_info() {
        $instance = new self();
        $instance->load_settings();
        
        return [
            'site_language' => $instance->detect_site_language(),
            'default_language' => $instance->default_language,
            'wp_locale' => get_locale(),
            'home_url' => home_url(),
            'country_languages' => $instance->country_languages
        ];
    }
    
    /**
     * Получение реального IP пользователя
     */
    private function get_real_user_ip() {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Прокси клиента
            'HTTP_X_FORWARDED_FOR',      // Стандартный заголовок прокси
            'HTTP_X_FORWARDED',          // Вариант прокси
            'HTTP_X_CLUSTER_CLIENT_IP',  // Кластер            'HTTP_FORWARDED_FOR',        // RFC 7239
            'HTTP_FORWARDED',            // RFC 7239
            'HTTP_X_REAL_IP',            // Nginx
            'REMOTE_ADDR'                // Прямое соединение
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Определение страны по IP с кэшированием
     */
    private function get_country_by_ip($ip) {
        if (empty($ip)) {
            return null;
        }
        
        // Проверяем кэш (24 часа)
        $cache_key = 'neetrino_geo_' . md5($ip);
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            return $cached_result;
        }
        
        // Пробуем каждый сервис по очереди
        foreach ($this->geo_services as $service_url) {
            $url = str_replace('{ip}', $ip, $service_url);
            
            $response = wp_remote_get($url, [
                'timeout' => 5,
                'headers' => [
                    'User-Agent' => 'WordPress/Auto-Translate'
                ]
            ]);
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                
                $country_code = null;
                
                // Парсим ответ в зависимости от сервиса
                if (isset($data['countryCode'])) {
                    $country_code = $data['countryCode']; // ip-api.com
                } elseif (isset($data['country_code'])) {
                    $country_code = $data['country_code']; // ipapi.co
                } elseif (isset($data['geoplugin_countryCode'])) {
                    $country_code = $data['geoplugin_countryCode']; // geoplugin
                } elseif (isset($data['country_code'])) {
                    $country_code = $data['country_code']; // freegeoip
                }
                
                if (!empty($country_code)) {
                    // Сохраняем в кэш на 24 часа
                    set_transient($cache_key, $country_code, 24 * HOUR_IN_SECONDS);
                    return $country_code;
                }
            }
            
            // Небольшая задержка между попытками
            usleep(100000); // 0.1 секунды
        }
        
        return null;
    }    /**
     * Автоматическое определение и редирект на нужный язык
     */
    public function auto_detect_language() {
        // Исключаем ботов и поисковиков
        if ($this->is_bot_request()) {
            $this->log_event('Запрос от бота игнорирован', ['user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']);
            return;
        }
        
        // Исключаем администраторов если включена опция
        if ($this->exclude_admin_users && is_user_logged_in() && current_user_can('manage_options')) {
            $this->log_event('Запрос от администратора игнорирован');
            return;
        }
        
        // Проверяем только на главной странице и если язык не установлен
        if (!is_front_page() || isset($_GET['country']) || isset($_GET['lang']) || isset($_COOKIE['neetrino_language_set'])) {
            return;
        }
        
        $user_ip = $this->get_real_user_ip();
        
        if (!$user_ip) {
            $this->log_event('Не удалось определить IP пользователя');
            return;
        }
        
        $country_code = $this->get_country_by_ip($user_ip);
        
        if (!$country_code) {
            $this->log_event('Не удалось определить страну по IP', ['ip' => $user_ip]);
            return;
        }
          // Определяем нужный язык
        $target_language = $this->country_languages[$country_code] ?? $this->default_language;
        
        // Логируем успешное определение
        $this->log_event('Успешное определение геолокации', [
            'ip' => $user_ip,
            'country' => $country_code,
            'language' => $target_language
        ]);
        
        // Формируем правильный URL
        $redirect_url = $this->get_language_url($target_language);
        $redirect_url .= '?country=' . $country_code;
        
        // Устанавливаем cookie чтобы не повторять редирект
        setcookie('neetrino_language_set', '1', time() + (24 * 60 * 60), '/'); // 24 часа
        
        // Делаем редирект только если нужен другой язык
        $current_url = home_url('/');
        if ($redirect_url !== $current_url . '?country=' . $country_code) {
            $this->log_event('Выполнен редирект', [
                'from' => $current_url,
                'to' => $redirect_url
            ]);
            
            wp_safe_redirect($redirect_url, 302);
            exit;
        }
    }/**
     * Статический метод для админ-страницы
     * Вызывается автоматически если модуль активен
     */
    public static function admin_page() {
        // Отладочная информация
        error_log('Auto-translate admin_page() вызван');        // Подключаем стили и скрипты для админки
        wp_enqueue_style(
            'neetrino-auto-translate-admin',
            plugin_dir_url(__FILE__) . 'assets/admin-style.css',
            [],
            '1.0.9'
        );
        
        wp_enqueue_script(
            'neetrino-auto-translate-admin',
            plugin_dir_url(__FILE__) . 'assets/admin-script.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        if (isset($_POST['submit'])) {
            self::save_settings();
        }        $settings = get_option('neetrino_auto_translate_settings', []);
        $default_language = $settings['default_language'] ?? 'en';
        
        // Получаем отладочную информацию
        $debug_info = self::get_debug_info();
          // Убираем дефолтные языки для стран - все настройки должны быть ручными
        // Пользователь сам выберет все необходимые переадресации по языкам
        $country_languages = $settings['country_languages'] ?? [];
          // Проверяем существование файла
        $admin_file = __DIR__ . '/admin-page.php';
        if (file_exists($admin_file)) {
            include $admin_file;
        } else {
            echo '<div class="wrap"><h1>Auto Translate</h1><p>Ошибка: файл admin-page.php не найден в ' . __DIR__ . '</p></div>';
        }
    }
    
    /**
     * Регистрация настроек
     */
    public function register_settings() {
        register_setting('neetrino_auto_translate_group', 'neetrino_auto_translate_settings');
    }
    
    /**
     * Подключение стилей для фронтенда
     */
    public function enqueue_scripts() {
        // Пока не нужно
    }
    
    /**
     * Подключение стилей для админки
     */
    public function admin_enqueue_scripts($hook) {
        if ($hook !== 'tools_page_neetrino-auto-translate') {
            return;
        }        wp_enqueue_style(
            'neetrino-auto-translate-admin',
            plugin_dir_url(__FILE__) . 'assets/admin-style.css',
            [],
            '1.0.9'
        );
        
        wp_enqueue_script(
            'neetrino-auto-translate-admin',
            plugin_dir_url(__FILE__) . 'assets/admin-script.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }    /**
     * Сохранение настроек
     */
    public static function save_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        check_admin_referer('neetrino_auto_translate_settings');
          $settings = [
            'default_language' => sanitize_text_field($_POST['default_language'] ?? 'en'),
            'exclude_admin_users' => isset($_POST['exclude_admin_users']) ? 1 : 0,
            'enable_logging' => isset($_POST['enable_logging']) ? 1 : 0,
            'country_languages' => []
        ];// Сохраняем языки для стран
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'country_') === 0) {
                $index = str_replace('country_', '', $key);
                $country = sanitize_text_field($value);
                $language_key = 'language_' . $index;
                
                if (!empty($_POST[$language_key]) && !empty($country)) {
                    $language = sanitize_text_field($_POST[$language_key]);
                    $settings['country_languages'][$country] = $language;
                }
            }
        }
          // Сохраняем языки для стран как есть (без принудительных значений по умолчанию)
        // Если пользователь удалил все страны, то так и должно быть
        
        update_option('neetrino_auto_translate_settings', $settings);
        
        echo '<div class="notice notice-success"><p>Настройки сохранены!</p></div>';
    }
    
    /**
     * Проверка, является ли запрос от бота или поисковика
     */
    private function is_bot_request() {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return true; // Запросы без User-Agent считаем ботами
        }
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        foreach ($this->excluded_user_agents as $bot_signature) {
            if (stripos($user_agent, $bot_signature) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Логирование событий автоперевода
     */
    private function log_event($message, $data = []) {
        if (!$this->enable_logging) {
            return;
        }
        
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'ip' => $this->get_real_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'message' => $message,
            'data' => $data
        ];
        
        // Сохраняем лог в опцию WordPress (ограничиваем до 100 записей)
        $logs = get_option('neetrino_auto_translate_logs', []);
        array_unshift($logs, $log_entry);
        
        // Ограничиваем количество записей
        if (count($logs) > 100) {
            $logs = array_slice($logs, 0, 100);
        }
        
        update_option('neetrino_auto_translate_logs', $logs);
    }
    
    /**
     * AJAX обработчик для тестирования IP
     */
    public function ajax_test_ip() {
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        check_ajax_referer('neetrino_auto_translate_test', 'nonce');
        
        $test_ip = sanitize_text_field($_POST['test_ip'] ?? '');
        
        if (empty($test_ip) || !filter_var($test_ip, FILTER_VALIDATE_IP)) {
            wp_send_json_error('Некорректный IP адрес');
        }
        
        $country = $this->get_country_by_ip($test_ip);
        $language = $country ? ($this->country_languages[$country] ?? $this->default_language) : $this->default_language;
        $url = $this->get_language_url($language);
        
        wp_send_json_success([
            'ip' => $test_ip,
            'country' => $country,
            'language' => $language,
            'url' => $url
        ]);
    }
    
    /**
     * AJAX обработчик для очистки кэша геолокации
     */
    public function ajax_clear_geo_cache() {
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        check_ajax_referer('neetrino_auto_translate_clear_cache', 'nonce');
        
        global $wpdb;
        
        // Удаляем все transient с геолокацией
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_neetrino_geo_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_neetrino_geo_%'");
        
        wp_send_json_success('Кэш геолокации очищен');
    }
}

// Инициализация модуля
new Neetrino_Auto_Translate();

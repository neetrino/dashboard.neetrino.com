<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Класс для сбора данных с сайта WordPress
 */
class WPBitrixDataCollector {
    // Используем ту же константу, что и в основном классе
    const OPTION_NAME = 'wp_bitrix_sync_options';
    /**
     * Получение информации о версии WordPress с индикатором обновления
     */
    public function get_wp_version_info() {
        $wp_version = get_bloginfo('version');
        
        // Подключаем необходимые файлы для работы с обновлениями
        if (!function_exists('get_core_updates')) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }
        
        // Проверяем наличие обновлений ядра WordPress
        if (function_exists('wp_version_check')) {
            wp_version_check(); // Обновляем кэш обновлений
        }
        $core_updates = get_core_updates();
        
        if (!is_array($core_updates) || empty($core_updates[0]) || $core_updates[0]->response == 'latest') {
            return "✅ " . $wp_version; // Актуальная версия
        } else {
            return "⚠️ " . $wp_version; // Требуется обновление
        }
    }

    /**
     * Форматирование размера хранилища в MB или GB
     */
    public function format_storage_size($bytes) {
        $mb = $bytes / (1024 * 1024);
        if ($mb >= 1024) {
            // Конвертируем в GB если больше 1024 MB
            return round($mb / 1024, 2) . ' GB';
        }
        return round($mb, 2) . ' MB';
    }

    /**
     * Рекурсивный подсчёт размера директории
     */
    public function folder_size($dir) {
        // Получаем путь к корневой директории сайта
        $root_path = $_SERVER['DOCUMENT_ROOT'];
        
        // Проверяем существование директории
        if (!is_dir($root_path)) {
            return 0;
        }
        
        // Используем системную команду du для получения размера директории
        if (function_exists('shell_exec')) {
            $output = shell_exec('du -sb ' . escapeshellarg($root_path));
            if ($output) {
                return (int) $output;
            }
        }
        
        // Fallback: если shell_exec недоступен, используем disk_free_space и disk_total_space
        $total = disk_total_space($root_path);
        $free = disk_free_space($root_path);
        return $total - $free;
    }

    /**
     * Получение информации о дочерней теме
     */
    public function get_child_theme_info() {
        $current_theme = wp_get_theme();
        if ($current_theme->parent()) {
            // Если есть родительская тема, значит текущая тема - дочерняя
            return $current_theme->get('Name');
        }
        return '❌ Дочерняя тема не используется';
    }

    /**
     * Получение информации об активной теме
     */
    public function get_active_theme_info() {
        $current_theme = wp_get_theme();
        // Если это дочерняя тема, получаем имя родительской темы
        if ($current_theme->parent()) {
            return $current_theme->parent()->get('Name');
        }
        // Иначе возвращаем имя текущей темы
        return $current_theme->get('Name');
    }

    /**
     * Получение информации о всех темах
     */
    public function get_themes_list() {
        $themes = wp_get_themes();
        $current_theme = wp_get_theme();
        $update_themes = get_site_transient('update_themes');
        $themes_list = [];
        $parent_theme = null;
        $parent_stylesheet = null;
        
        // Если активна дочерняя тема, получаем родительскую
        if ($current_theme->parent()) {
            $parent_theme = $current_theme->parent();
            $parent_stylesheet = $parent_theme->get_stylesheet();
        }
        
        foreach ($themes as $theme_dir => $theme) {
            // Пропускаем родительскую тему в списке неактивных, если она является родителем активной дочерней темы
            if ($parent_theme && $theme->get_stylesheet() === $parent_stylesheet) {
                continue;
            }
            
            $theme_name = $theme->get('Name');
            $needs_update = !empty($update_themes->response[$theme_dir]);
            
            // Проверяем статус темы
            if ($current_theme->get_stylesheet() === $theme->get_stylesheet() || 
                ($parent_theme && $theme->get_stylesheet() === $parent_stylesheet)) {
                // Если это активная тема или её родитель
                if ($parent_theme) {
                    $theme_name = $parent_theme->get('Name');
                    // Проверяем обновление для родительской темы
                    if (!empty($update_themes->response[$parent_stylesheet])) {
                        $theme_name .= ' ⚠️';
                    }
                } else {
                    if ($needs_update) {
                        $theme_name .= ' ⚠️';
                    }
                }
                $theme_name = '✅ ' . $theme_name;
            } else {
                if ($needs_update) {
                    $theme_name .= ' ⚠️';
                }
                $theme_name = '❌ ' . $theme_name;
            }
            
            $themes_list[] = $theme_name;
        }
        
        return empty($themes_list) ? ['❌ Нет установленных тем'] : $themes_list;
    }

    /**
     * Получение списка плагинов с их статусами
     */
    public function get_plugins_list() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        $update_plugins = get_site_transient('update_plugins');
        $plugins_need_update = $update_plugins ? array_keys((array)$update_plugins->response) : [];
        
        $formatted_list = [];
        
        foreach ($all_plugins as $plugin_path => $plugin_data) {
            $status = [];
            
            // Статус активности
            if (in_array($plugin_path, $active_plugins)) {
                $status[] = '✅';
            } else {
                $status[] = '❌';
            }
            
            // Статус обновления
            if (in_array($plugin_path, $plugins_need_update)) {
                $status[] = '⚠️';
            }
            
            $formatted_list[] = implode(' ', $status) . ' ' . $plugin_data['Name'];
        }
        
        return $formatted_list;
    }

    /**
     * Получение списка платежных плагинов
     */
    public function get_payment_plugins() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        $update_plugins = get_site_transient('update_plugins');
        $plugins_need_update = $update_plugins ? array_keys((array)$update_plugins->response) : [];
        $payment_plugins = [];
        
        // Список ключевых слов для определения платежных плагинов
        $payment_keywords = [
            'woocommerce',
            'payment',
            'stripe',
            'paypal',
            'checkout',
            'pay',
            'merchant',
            'gateway',
            'банк',
            'оплата',
            'платеж'
        ];
        
        foreach ($all_plugins as $plugin_path => $plugin_data) {
            $plugin_text = strtolower($plugin_data['Name'] . ' ' . $plugin_data['Description']);
            
            foreach ($payment_keywords as $keyword) {
                if (strpos($plugin_text, strtolower($keyword)) !== false) {
                    $status = [];
                    
                    // Статус активности
                    if (in_array($plugin_path, $active_plugins)) {
                        $status[] = '✅';
                    } else {
                        $status[] = '❌';
                    }
                    
                    // Статус обновления
                    if (in_array($plugin_path, $plugins_need_update)) {
                        $status[] = '⚠️';
                    }
                    
                    $payment_plugins[] = implode(' ', $status) . ' ' . $plugin_data['Name'];
                    break;
                }
            }
        }
        
        return $payment_plugins;
    }

    /**
     * Получение списка языковых плагинов
     */
    public function get_language_plugins() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        $language_plugins = [];

        $language_keywords = [
            'translate',
            'translation',
            'language',
            'multilingual',
            'wpml',
            'polylang',
            'loco',
            'transposh',
            'gtranslate'
        ];

        foreach ($all_plugins as $plugin_path => $plugin_data) {
            $plugin_name = strtolower($plugin_data['Name']);
            $plugin_desc = strtolower($plugin_data['Description']);

            foreach ($language_keywords as $keyword) {
                if (strpos($plugin_name, $keyword) !== false || strpos($plugin_desc, $keyword) !== false) {
                    $status = in_array($plugin_path, $active_plugins) ? '✅' : '❌';
                    $language_plugins[] = $status . ' ' . $plugin_data['Name'];
                    break;
                }
            }
        }

        return !empty($language_plugins) ? $language_plugins : ['❌ Нет языковых плагинов'];
    }

    /**
     * Получение списка плагинов кэширования
     */
    public function get_cache_plugins() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        $update_plugins = get_site_transient('update_plugins');
        $plugins_need_update = $update_plugins ? array_keys((array)$update_plugins->response) : [];
        $cache_plugins = [];
        
        // Список ключевых слов для определения плагинов кэширования
        $cache_keywords = [
            'cache',
            'caching',
            'performance',
            'memcache',
            'redis',
            'varnish',
            'cloudflare',
            'litespeed',
            'w3 total cache',
            'wp super cache',
            'wp fastest cache',
            'wp rocket',
            'кэш',
            'кеш',
            'производительность'
        ];
        
        foreach ($all_plugins as $plugin_path => $plugin_data) {
            $plugin_text = strtolower($plugin_data['Name'] . ' ' . $plugin_data['Description']);
            
            foreach ($cache_keywords as $keyword) {
                if (strpos($plugin_text, strtolower($keyword)) !== false) {
                    $status = [];
                    
                    // Статус активности
                    if (in_array($plugin_path, $active_plugins)) {
                        $status[] = '✅';
                    } else {
                        $status[] = '❌';
                    }
                    
                    // Статус обновления
                    if (in_array($plugin_path, $plugins_need_update)) {
                        $status[] = '⚠️';
                    }
                    
                    $cache_plugins[] = implode(' ', $status) . ' ' . $plugin_data['Name'];
                    break;
                }
            }
        }
        
        return $cache_plugins;
    }

    /**
     * Получение статуса плагина Easy Updates Manager
     */
    public function get_easy_updates_manager_status() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        $update_plugins = get_site_transient('update_plugins');
        $plugins_need_update = $update_plugins ? array_keys((array)$update_plugins->response) : [];

        $easy_updates_manager_keywords = [
            'easy updates manager',
            'easy-updates-manager',
            'easy-updates-manager/easy-updates-manager.php',
        ];

        foreach ($all_plugins as $plugin_path => $plugin_data) {
            $plugin_text = strtolower($plugin_data['Name'] . ' ' . $plugin_data['Description']);

            foreach ($easy_updates_manager_keywords as $keyword) {
                if (strpos($plugin_text, strtolower($keyword)) !== false) {
                    $status = '';
                    
                    // Статус активности
                    if (in_array($plugin_path, $active_plugins)) {
                        $status .= '✅';
                    } else {
                        $status .= '❌';
                    }
                    
                    // Статус обновления
                    if (in_array($plugin_path, $plugins_need_update)) {
                        $status .= ' ⚠️';
                    }
                    
                    return $status . ' ' . $plugin_data['Name'];
                }
            }
        }

        return '❌ Easy Updates Manager';
    }

    /**
     * Получение статуса плагина Slider Revolution
     */
    public function get_slider_revolution_status() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');

        $slider_revolution_keywords = [
            'revslider',
            'slider revolution',
            'revolution slider'
        ];

        foreach ($all_plugins as $plugin_path => $plugin_data) {
            $plugin_name = strtolower($plugin_data['Name']);
            $plugin_desc = strtolower($plugin_data['Description']);

            foreach ($slider_revolution_keywords as $keyword) {
                if (strpos($plugin_name, $keyword) !== false || strpos($plugin_desc, $keyword) !== false) {
                    if (in_array($plugin_path, $active_plugins)) {
                        return '✅ Slider Revolution';
                    } else {
                        return '❌ Slider Revolution';
                    }
                }
            }
        }

        return '❌ Slider Revolution';
    }

    /**
     * Получение общего количества комментариев на сайте
     */
    public function get_total_comments_count() {
        global $wpdb;
        
        // Считаем все одобренные комментарии
        $approved_comments = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = '1'"
        );
        
        // Считаем комментарии ожидающие модерации
        $pending_comments = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = '0'"
        );
        
        // Считаем спам комментарии
        $spam_comments = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'spam'"
        );
        
        $approved_comments = (int) $approved_comments;
        $pending_comments = (int) $pending_comments;
        $spam_comments = (int) $spam_comments;
        $total_comments = $approved_comments + $pending_comments + $spam_comments;
        
        // Формируем информативную строку
        if ($total_comments === 0) {
            return "0 комментариев";
        }
        
        $status_parts = [];
        
        if ($approved_comments > 0) {
            $status_parts[] = "✅ {$approved_comments} одобренных";
        }
        
        if ($pending_comments > 0) {
            $status_parts[] = "⏳ {$pending_comments} на модерации";
        }
        
        if ($spam_comments > 0) {
            $status_parts[] = "🚫 {$spam_comments} спам";
        }
        
        return $total_comments . " комментариев (" . implode(", ", $status_parts) . ")";
    }

    /**
     * Сбор всех данных для отправки в Bitrix24
     */
    public function collect_all_data() {
        // Получение данных из WordPress
        $wp_version = $this->get_wp_version_info();
        $upload_dir = wp_get_upload_dir();
        $folder_size = $this->folder_size($upload_dir['basedir']);
        $storage_size = $this->format_storage_size($folder_size);
        $user_count = count(get_users());
        $admin_email = get_option('admin_email'); // Получаем email администратора
        $child_theme = $this->get_child_theme_info();
        $active_theme = $this->get_active_theme_info();
        $themes_list = $this->get_themes_list();

        // Получение информации об обновлениях плагинов и тем
        $update_plugins = get_site_transient('update_plugins');
        $plugins_updates_count = (!empty($update_plugins->response) && is_array($update_plugins->response))
            ? count($update_plugins->response) : 0;
        
        $update_themes = get_site_transient('update_themes');
        $themes_updates_count = (!empty($update_themes->response) && is_array($update_themes->response))
            ? count($update_themes->response) : 0;
            
        // Получение списка плагинов с их статусами
        $plugins_list = $this->get_plugins_list();
        
        // Получение списка платежных плагинов
        $payment_plugins = $this->get_payment_plugins();
        
        // Получение списка плагинов кэширования
        $cache_plugins = $this->get_cache_plugins();
        
        // Получение списка языковых плагинов
        $language_plugins = $this->get_language_plugins();
        
        // Получение статуса плагина Easy Updates Manager
        $easy_updates_manager = $this->get_easy_updates_manager_status();
        
        // Получение статуса плагина Slider Revolution
        $slider_revolution = $this->get_slider_revolution_status();
        
        // Получение общего количества комментариев
        $total_comments = $this->get_total_comments_count();
        
        // Формирование массива данных
        return [
            'wp_version'             => $wp_version,
            'storage_size'           => $storage_size,
            'user_count'             => $user_count,
            'plugins_updates_count'  => $plugins_updates_count,
            'themes_updates_count'   => $themes_updates_count,
            'plugins_list'           => $plugins_list,
            'payment_plugins'        => $payment_plugins,
            'cache_plugins'          => $cache_plugins,
            'language_plugins'       => $language_plugins,
            'admin_email'            => $admin_email,
            'child_theme'            => $child_theme,
            'active_theme'           => $themes_list,
            'easy_updates_manager'   => $easy_updates_manager,
            'slider_revolution'      => $slider_revolution,
            'total_comments'         => $total_comments,
        ];
    }
}

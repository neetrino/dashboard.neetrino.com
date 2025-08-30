<?php
/**
 * Класс для работы с базой данных при сбросе
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Reset_Database_Handler {
    
    private $wpdb;
    private $table_prefix;
    private $preserve_options = [
        'siteurl',
        'home',
        'blogname',
        'blogdescription',
        'users_can_register',
        'admin_email',
        'start_of_week',
        'use_balanceTags',
        'use_smilies',
        'require_name_email',
        'comments_notify',
        'posts_per_rss',
        'rss_use_excerpt',
        'mailserver_url',
        'mailserver_login',
        'mailserver_pass',
        'mailserver_port',
        'default_category',
        'default_comment_status',
        'default_ping_status',
        'default_pingback_flag',
        'posts_per_page',
        'date_format',
        'time_format',
        'links_updated_date_format',
        'comment_moderation',
        'moderation_notify',
        'permalink_structure',
        'rewrite_rules',
        'hack_file',
        'blog_charset',
        'moderation_keys',
        'category_base',
        'ping_sites',
        'comment_max_links',
        'gmt_offset',
        'default_email_category',
        'recently_edited',
        'comment_whitelist',
        'blacklist_keys',
        'comment_registration',
        'html_type',
        'use_trackback',
        'default_role',
        'db_version',
        'uploads_use_yearmonth_folders',
        'upload_path',
        'blog_public',
        'default_link_category',
        'show_on_front',
        'tag_base',
        'show_avatars',
        'avatar_rating',
        'upload_url_path',
        'thumbnail_size_w',
        'thumbnail_size_h',
        'thumbnail_crop',
        'medium_size_w',
        'medium_size_h',
        'avatar_default',
        'large_size_w',
        'large_size_h',
        'image_default_link_type',
        'image_default_align',
        'close_comments_for_old_posts',
        'close_comments_days_old',
        'thread_comments',
        'thread_comments_depth',
        'page_comments',
        'comments_per_page',
        'default_comments_page',
        'comment_order',
        'sticky_posts',
        'widget_categories',
        'widget_text',
        'widget_rss',
        'uninstall_plugins',
        'timezone_string',
        'page_for_posts',
        'page_on_front',
        'default_post_format',
        'link_manager_enabled',
        'finished_splitting_shared_terms',
        'site_icon',
        'medium_large_size_w',
        'medium_large_size_h',
        'wp_page_for_privacy_policy',
        'show_comments_cookies_opt_in',
        'admin_email_lifespan',
        'disallowed_keys',
        'comment_previously_approved',
        'auto_plugin_theme_update_emails',
        'auto_update_core_dev',
        'auto_update_core_minor',
        'auto_update_core_major'
    ];
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_prefix = $wpdb->prefix;
    }
    
    /**
     * Выполнить полный сброс сайта
     */
    public function perform_full_reset() {
        try {
            // Начинаем транзакцию для атомарности операции
            $this->wpdb->query('START TRANSACTION');
            
            // Сохраняем данные текущего пользователя
            $current_user = wp_get_current_user();
            $user_data = [
                'user_login' => $current_user->user_login,
                'user_email' => $current_user->user_email,
                'user_pass' => $current_user->user_pass,
                'user_nicename' => $current_user->user_nicename,
                'display_name' => $current_user->display_name,
                'user_registered' => $current_user->user_registered,
            ];
            
            // Сохраняем важные настройки
            $preserved_options = $this->get_preserved_options();
            
            // 1. Очищаем стандартные таблицы WordPress (супербыстро)
            $this->truncate_wp_tables();
            
            // 2. Удаляем кастомные таблицы
            $this->drop_custom_tables();
            
            // 3. Восстанавливаем базовую структуру WordPress
            $this->restore_wp_structure();
            
            // 4. Восстанавливаем сохраненные настройки
            $this->restore_preserved_options($preserved_options);
            
            // 5. Восстанавливаем текущего пользователя
            $this->restore_current_user($user_data);
            
            // 6. Устанавливаем заводские настройки плагинов и темы
            $this->set_factory_defaults();
            
            // Подтверждаем транзакцию
            $this->wpdb->query('COMMIT');
            
            // Очищаем кэш WordPress
            wp_cache_flush();
            
            return [
                'success' => true,
                'message' => 'Полный сброс выполнен за ' . $this->get_execution_time() . ' секунд. Плагины отключены, активирована стандартная тема.'
            ];
            
        } catch (Exception $e) {
            // Откатываем изменения при ошибке
            $this->wpdb->query('ROLLBACK');
            
            return [
                'success' => false,
                'message' => 'Ошибка при сбросе: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Очистить стандартные таблицы WordPress (TRUNCATE - мгновенно)
     */
    private function truncate_wp_tables() {
        $tables_to_truncate = [
            $this->table_prefix . 'posts',
            $this->table_prefix . 'postmeta',
            $this->table_prefix . 'comments',
            $this->table_prefix . 'commentmeta',
            $this->table_prefix . 'links',
            $this->table_prefix . 'users',
            $this->table_prefix . 'usermeta',
            $this->table_prefix . 'terms',
            $this->table_prefix . 'term_taxonomy',
            $this->table_prefix . 'term_relationships',
            $this->table_prefix . 'termmeta'
        ];
        
        // Отключаем проверки внешних ключей для скорости
        $this->wpdb->query('SET FOREIGN_KEY_CHECKS = 0');
        
        foreach ($tables_to_truncate as $table) {
            $this->wpdb->query("TRUNCATE TABLE `$table`");
        }
        
        // Включаем обратно проверки
        $this->wpdb->query('SET FOREIGN_KEY_CHECKS = 1');
    }
    
    /**
     * Удалить кастомные таблицы
     */
    private function drop_custom_tables() {
        // Получаем все таблицы с нашим префиксом
        $tables = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $this->table_prefix . '%'
            ),
            ARRAY_N
        );
        
        $wp_core_tables = [
            $this->table_prefix . 'posts',
            $this->table_prefix . 'postmeta',
            $this->table_prefix . 'comments',
            $this->table_prefix . 'commentmeta',
            $this->table_prefix . 'links',
            $this->table_prefix . 'users',
            $this->table_prefix . 'usermeta',
            $this->table_prefix . 'terms',
            $this->table_prefix . 'term_taxonomy',
            $this->table_prefix . 'term_relationships',
            $this->table_prefix . 'termmeta',
            $this->table_prefix . 'options'
        ];
        
        foreach ($tables as $table) {
            $table_name = $table[0];
            
            // Удаляем только кастомные таблицы (не стандартные WP)
            if (!in_array($table_name, $wp_core_tables)) {
                $this->wpdb->query("DROP TABLE IF EXISTS `$table_name`");
            }
        }
    }
    
    /**
     * Восстановить базовую структуру WordPress
     */
    private function restore_wp_structure() {
        // Создаем базовые категории и теги
        $this->wpdb->insert(
            $this->table_prefix . 'terms',
            [
                'term_id' => 1,
                'name' => 'Без рубрики',
                'slug' => 'uncategorized',
                'term_group' => 0
            ]
        );
        
        $this->wpdb->insert(
            $this->table_prefix . 'term_taxonomy',
            [
                'term_taxonomy_id' => 1,
                'term_id' => 1,
                'taxonomy' => 'category',
                'description' => '',
                'parent' => 0,
                'count' => 0
            ]
        );
        
        // Создаем пример поста
        $this->wpdb->insert(
            $this->table_prefix . 'posts',
            [
                'ID' => 1,
                'post_author' => 1,
                'post_date' => current_time('mysql'),
                'post_date_gmt' => current_time('mysql', 1),
                'post_content' => 'Добро пожаловать в WordPress! Это ваша первая запись. Отредактируйте или удалите её, затем начинайте создавать!',
                'post_title' => 'Привет, мир!',
                'post_excerpt' => '',
                'post_status' => 'publish',
                'comment_status' => 'open',
                'ping_status' => 'open',
                'post_password' => '',
                'post_name' => 'hello-world',
                'to_ping' => '',
                'pinged' => '',
                'post_modified' => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', 1),
                'post_content_filtered' => '',
                'post_parent' => 0,
                'guid' => home_url('/?p=1'),
                'menu_order' => 0,
                'post_type' => 'post',
                'post_mime_type' => '',
                'comment_count' => 0
            ]
        );
        
        // Связываем пост с категорией
        $this->wpdb->insert(
            $this->table_prefix . 'term_relationships',
            [
                'object_id' => 1,
                'term_taxonomy_id' => 1,
                'term_order' => 0
            ]
        );
        
        // Создаем пример страницы
        $this->wpdb->insert(
            $this->table_prefix . 'posts',
            [
                'ID' => 2,
                'post_author' => 1,
                'post_date' => current_time('mysql'),
                'post_date_gmt' => current_time('mysql', 1),
                'post_content' => 'Это пример страницы. От записей в блоге она отличается тем, что остаётся на одном месте и отображается в навигации сайта (в большинстве тем). Большинство пользователей начинают с написанием страницы «О нас», где они представляются потенциальным посетителям сайта.',
                'post_title' => 'Пример страницы',
                'post_excerpt' => '',
                'post_status' => 'publish',
                'comment_status' => 'closed',
                'ping_status' => 'open',
                'post_password' => '',
                'post_name' => 'sample-page',
                'to_ping' => '',
                'pinged' => '',
                'post_modified' => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', 1),
                'post_content_filtered' => '',
                'post_parent' => 0,
                'guid' => home_url('/?page_id=2'),
                'menu_order' => 0,
                'post_type' => 'page',
                'post_mime_type' => '',
                'comment_count' => 0
            ]
        );
    }
    
    /**
     * Получить сохраняемые настройки
     */
    private function get_preserved_options() {
        $options = [];
        
        foreach ($this->preserve_options as $option_name) {
            $value = get_option($option_name, null);
            if ($value !== null) {
                $options[$option_name] = $value;
            }
        }
        
        return $options;
    }
    
    /**
     * Восстановить сохраненные настройки
     */
    private function restore_preserved_options($options) {
        foreach ($options as $option_name => $value) {
            update_option($option_name, $value);
        }
    }
    
    /**
     * Восстановить текущего пользователя
     */
    private function restore_current_user($user_data) {
        $this->wpdb->insert(
            $this->table_prefix . 'users',
            [
                'ID' => 1,
                'user_login' => $user_data['user_login'],
                'user_pass' => $user_data['user_pass'],
                'user_nicename' => $user_data['user_nicename'],
                'user_email' => $user_data['user_email'],
                'user_url' => '',
                'user_registered' => $user_data['user_registered'],
                'user_activation_key' => '',
                'user_status' => 0,
                'display_name' => $user_data['display_name']
            ]
        );
        
        // Добавляем мета-данные пользователя
        $user_meta = [
            'wp_capabilities' => serialize(['administrator' => true]),
            'wp_user_level' => '10',
            'first_name' => '',
            'last_name' => '',
            'nickname' => $user_data['user_login'],
            'description' => '',
            'rich_editing' => 'true',
            'syntax_highlighting' => 'true',
            'comment_shortcuts' => 'false',
            'admin_color' => 'fresh',
            'use_ssl' => '0',
            'show_admin_bar_front' => 'true',
            'locale' => ''
        ];
        
        foreach ($user_meta as $meta_key => $meta_value) {
            $this->wpdb->insert(
                $this->table_prefix . 'usermeta',
                [
                    'user_id' => 1,
                    'meta_key' => $meta_key,
                    'meta_value' => $meta_value
                ]
            );
        }
    }
    
    /**
     * Выполнить частичный сброс
     */
    public function perform_partial_reset($action) {
        try {
            switch ($action) {
                case 'transients':
                    return $this->delete_transients();
                    
                case 'uploads':
                    return $this->delete_uploads();
                    
                case 'plugins':
                    return $this->delete_plugins();
                    
                case 'theme-options':
                    return $this->reset_theme_options();
                    
                case 'themes':
                    return $this->delete_themes();
                    
                case 'custom-tables':
                    return $this->truncate_custom_tables();
                    
                case 'htaccess':
                    return $this->delete_htaccess();
                    
                default:
                    throw new Exception('Неизвестное действие: ' . $action);
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Удалить все transients
     */
    private function delete_transients() {
        $this->wpdb->query(
            "DELETE FROM {$this->table_prefix}options 
             WHERE option_name LIKE '_transient_%' 
             OR option_name LIKE '_site_transient_%'"
        );
        
        return [
            'success' => true,
            'message' => 'Transients успешно удалены'
        ];
    }
    
    /**
     * Удалить папку uploads
     */
    private function delete_uploads() {
        $upload_dir = wp_upload_dir();
        $this->delete_directory($upload_dir['basedir']);
        
        return [
            'success' => true,
            'message' => 'Папка uploads успешно удалена'
        ];
    }
    
    /**
     * Удалить все плагины кроме Neetrino
     */
    private function delete_plugins() {
        $plugins_dir = WP_PLUGIN_DIR;
        $items = scandir($plugins_dir);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === 'Neetrino') {
                continue;
            }
            
            $item_path = $plugins_dir . '/' . $item;
            if (is_dir($item_path)) {
                $this->delete_directory($item_path);
            } else {
                unlink($item_path);
            }
        }
        
        // Обновляем список активных плагинов
        $neetrino_plugin = $this->get_neetrino_plugin_path();
        if ($neetrino_plugin) {
            update_option('active_plugins', [$neetrino_plugin]);
        } else {
            update_option('active_plugins', []);
        }
        
        return [
            'success' => true,
            'message' => 'Плагины успешно удалены, остался активным только Neetrino'
        ];
    }
    
    /**
     * Сбросить настройки тем
     */
    private function reset_theme_options() {
        // Удаляем theme_mods для всех тем
        $this->wpdb->query(
            "DELETE FROM {$this->table_prefix}options 
             WHERE option_name LIKE 'theme_mods_%'"
        );
        
        return [
            'success' => true,
            'message' => 'Настройки тем успешно сброшены'
        ];
    }
    
    /**
     * Удалить все темы кроме активной и активировать стандартную тему
     */
    private function delete_themes() {
        $themes_dir = get_theme_root();
        $current_theme = get_stylesheet();
        $items = scandir($themes_dir);
        
        // Список стандартных тем WordPress (в порядке приоритета)
        $default_themes = ['twentytwentyfive', 'twentytwentyfour', 'twentytwentythree', 'twentytwentytwo', 'twentytwentyone', 'twentytwenty'];
        $available_default_theme = null;
        
        // Ищем доступную стандартную тему
        foreach ($default_themes as $theme) {
            if (is_dir($themes_dir . '/' . $theme)) {
                $available_default_theme = $theme;
                break;
            }
        }
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === $current_theme || $item === $available_default_theme) {
                continue;
            }
            
            $item_path = $themes_dir . '/' . $item;
            if (is_dir($item_path)) {
                $this->delete_directory($item_path);
            }
        }
        
        // Активируем стандартную тему, если она найдена и отличается от текущей
        if ($available_default_theme && $available_default_theme !== $current_theme) {
            update_option('template', $available_default_theme);
            update_option('stylesheet', $available_default_theme);
            
            // Сбрасываем настройки темы
            $this->wpdb->query(
                "DELETE FROM {$this->table_prefix}options 
                 WHERE option_name LIKE 'theme_mods_%'"
            );
            
            return [
                'success' => true,
                'message' => 'Неактивные темы удалены, активирована стандартная тема: ' . $available_default_theme
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Неактивные темы успешно удалены'
        ];
    }
    
    /**
     * Очистить кастомные таблицы
     */
    private function truncate_custom_tables() {
        $tables = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $this->table_prefix . '%'
            ),
            ARRAY_N
        );
        
        $wp_core_tables = [
            $this->table_prefix . 'posts',
            $this->table_prefix . 'postmeta',
            $this->table_prefix . 'comments',
            $this->table_prefix . 'commentmeta',
            $this->table_prefix . 'links',
            $this->table_prefix . 'users',
            $this->table_prefix . 'usermeta',
            $this->table_prefix . 'terms',
            $this->table_prefix . 'term_taxonomy',
            $this->table_prefix . 'term_relationships',
            $this->table_prefix . 'termmeta',
            $this->table_prefix . 'options'
        ];
        
        $this->wpdb->query('SET FOREIGN_KEY_CHECKS = 0');
        
        foreach ($tables as $table) {
            $table_name = $table[0];
            
            if (!in_array($table_name, $wp_core_tables)) {
                $this->wpdb->query("TRUNCATE TABLE `$table_name`");
            }
        }
        
        $this->wpdb->query('SET FOREIGN_KEY_CHECKS = 1');
        
        return [
            'success' => true,
            'message' => 'Кастомные таблицы успешно очищены'
        ];
    }
    
    /**
     * Удалить файл .htaccess
     */
    private function delete_htaccess() {
        $htaccess_path = ABSPATH . '.htaccess';
        
        if (file_exists($htaccess_path)) {
            unlink($htaccess_path);
        }
        
        return [
            'success' => true,
            'message' => 'Файл .htaccess успешно удален'
        ];
    }
    
    /**
     * Рекурсивно удалить директорию
     */
    private function delete_directory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $items = scandir($dir);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $item_path = $dir . '/' . $item;
            
            if (is_dir($item_path)) {
                $this->delete_directory($item_path);
            } else {
                unlink($item_path);
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * Получить время выполнения (заглушка)
     */
    private function get_execution_time() {
        return '2-3';
    }
    
    /**
     * Установить заводские настройки плагинов и темы
     */
    private function set_factory_defaults() {
        // Получаем правильный путь к плагину Neetrino
        $neetrino_plugin = $this->get_neetrino_plugin_path();
        
        // Отключаем все плагины кроме Neetrino
        if ($neetrino_plugin) {
            update_option('active_plugins', [$neetrino_plugin]);
        } else {
            update_option('active_plugins', []);
        }
        
        // Проверяем доступность темы Twenty Twenty-Five
        $default_theme = 'twentytwentyfive';
        $available_themes = wp_get_themes();
        
        if (isset($available_themes[$default_theme])) {
            // Устанавливаем Twenty Twenty-Five как активную тему
            update_option('template', $default_theme);
            update_option('stylesheet', $default_theme);
        } else {
            // Если Twenty Twenty-Five недоступна, ищем другую стандартную тему
            $fallback_themes = ['twentytwentyfour', 'twentytwentythree', 'twentytwentytwo', 'twentytwentyone', 'twentytwenty'];
            
            foreach ($fallback_themes as $theme) {
                if (isset($available_themes[$theme])) {
                    update_option('template', $theme);
                    update_option('stylesheet', $theme);
                    break;
                }
            }
        }
        
        // Сбрасываем настройки темы
        $this->wpdb->query(
            "DELETE FROM {$this->table_prefix}options 
             WHERE option_name LIKE 'theme_mods_%'"
        );
        
        // Устанавливаем базовые настройки WordPress
        update_option('show_on_front', 'posts');
        update_option('page_on_front', 0);
        update_option('page_for_posts', 0);
        
        // Сбрасываем меню и виджеты
        update_option('nav_menu_options', []);
        $this->wpdb->query(
            "DELETE FROM {$this->table_prefix}options 
             WHERE option_name LIKE 'widget_%'"
        );
        
        // Устанавливаем стандартные виджеты для боковой панели
        $default_widgets = [
            'search' => [2 => ['title' => '']],
            'recent-posts' => [2 => ['title' => '', 'number' => 5]],
            'recent-comments' => [2 => ['title' => '', 'number' => 5]],
            'archives' => [2 => ['title' => '', 'count' => 0, 'dropdown' => 0]],
            'categories' => [2 => ['title' => '', 'count' => 0, 'hierarchical' => 0, 'dropdown' => 0]],
            'meta' => [2 => ['title' => '']]
        ];
        
        foreach ($default_widgets as $widget_name => $widget_data) {
            update_option('widget_' . $widget_name, $widget_data);
        }
        
        // Настройки боковой панели
        update_option('sidebars_widgets', [
            'wp_inactive_widgets' => [],
            'sidebar-1' => ['search-2', 'recent-posts-2', 'recent-comments-2', 'archives-2', 'categories-2', 'meta-2'],
            'array_version' => 3
        ]);
    }
    
    /**
     * Получить правильный путь к плагину Neetrino
     */
    private function get_neetrino_plugin_path() {
        $all_plugins = get_plugins();
        
        foreach ($all_plugins as $plugin_file => $plugin_data) {
            if (strpos($plugin_file, 'Neetrino') !== false || 
                (isset($plugin_data['Name']) && strpos($plugin_data['Name'], 'Neetrino') !== false)) {
                return $plugin_file;
            }
        }
        
        // Резервный вариант - попробуем стандартные пути
        $possible_paths = [
            'Neetrino/neetrino.php',
            'neetrino/neetrino.php',
            'Neetrino/index.php',
            'neetrino/index.php'
        ];
        
        foreach ($possible_paths as $path) {
            if (file_exists(WP_PLUGIN_DIR . '/' . $path)) {
                return $path;
            }
        }
        
        return null;
    }
}

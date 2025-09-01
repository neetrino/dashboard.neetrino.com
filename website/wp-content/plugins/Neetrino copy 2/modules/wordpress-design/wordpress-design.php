<?php
/**
 * Module: WordPress Design
 * Description: Современный минималистичный дизайн админ-панели
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_WordPress_Design {
    
    public function __construct() {
        // Инициализация модуля только если он активен
        if (!Neetrino::is_module_active('wordpress-design')) {
            return;
        }
        
        // Хуки только для админ-панели
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
        add_action('admin_head', [$this, 'add_custom_styles']);
        add_action('admin_body_class', [$this, 'add_body_class']);
        
        // Хуки для отображения размеров изображений в медиа-библиотеке
        add_filter('manage_media_columns', [$this, 'add_media_columns']);
        add_action('manage_media_custom_column', [$this, 'display_media_column'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_media_scripts']);
        add_filter('manage_upload_sortable_columns', [$this, 'add_sortable_columns']);
        add_action('pre_get_posts', [$this, 'handle_media_sorting']);
        
        // Хук для сохранения размера файла при загрузке
        add_action('add_attachment', [$this, 'save_file_size_meta']);
        
        // Обновляем размеры для существующих файлов при первом запуске
        if (!get_option('neetrino_file_sizes_updated')) {
            add_action('admin_init', [$this, 'update_existing_file_sizes']);
            update_option('neetrino_file_sizes_updated', true);
        }
    }
    
    /**
     * Подключение CSS файлов
     */
    public function enqueue_admin_styles() {
        $module_url = plugin_dir_url(__FILE__);
        
        wp_enqueue_style(
            'neetrino-wordpress-design',
            $module_url . 'assets/css/admin-modern.css',
            [],
            '1.0.0'
        );
    }
      /**
     * Добавление дополнительных стилей в head
     */
    public function add_custom_styles() {
        $settings = get_option('neetrino_wordpress_design_settings', [
            'enable_modern_buttons' => true,
            'enable_rounded_corners' => true,
            'enable_clean_forms' => true,
            'enable_better_typography' => true,
            'enable_modern_menu' => true,
            'enable_media_dimensions' => true
        ]);
        
        echo '<style>
            /* Переменные для современной цветовой схемы */
            :root {
                --neetrino-primary: #6c5ce7;
                --neetrino-primary-light: #a29bfe;
                --neetrino-success: #00b894;
                --neetrino-warning: #fdcb6e;
                --neetrino-danger: #e84393;
                --neetrino-dark: #2d3436;
                --neetrino-light: #ddd;
                --neetrino-bg: #f8f9fa;
                --neetrino-white: #ffffff;
                --neetrino-border: #e9ecef;
                --neetrino-shadow: 0 2px 10px rgba(0,0,0,0.1);
                --neetrino-radius: 8px;
            }';
        
        // Дополнительные стили в зависимости от настроек
        if (!$settings['enable_modern_menu']) {
            echo '
            /* Отключение стилей левого меню */
            .neetrino-modern-design #adminmenuwrap {
                background: #23282d !important;
                box-shadow: none !important;
            }
            .neetrino-modern-design #adminmenu li {
                margin: 0 !important;
                border-radius: 0 !important;
                animation: none !important;
            }
            .neetrino-modern-design #adminmenu a {
                border-radius: 0 !important;
                transform: none !important;
            }';
        }
        
        echo '</style>';
    }
    
    /**
     * Добавление класса к body админки
     */
    public function add_body_class($classes) {
        return $classes . ' neetrino-modern-design';
    }
    
    /**
     * Добавление колонок "Размеры" и "Размер файла" в медиа-библиотеку
     */
    public function add_media_columns($columns) {
        $settings = get_option('neetrino_wordpress_design_settings', []);
        
        if (!isset($settings['enable_media_dimensions']) || $settings['enable_media_dimensions']) {
            // Добавляем колонку размеров изображения
            $columns['image_dimensions'] = 'Размеры';
            // Добавляем колонку размера файла
            $columns['file_size'] = 'Вес';
        }
        
        return $columns;
    }
    
    /**
     * Отображение размеров изображения и размера файла в колонках
     */
    public function display_media_column($column_name, $attachment_id) {
        if ($column_name == 'image_dimensions') {
            $metadata = wp_get_attachment_metadata($attachment_id);
            
            if (isset($metadata['width']) && isset($metadata['height'])) {
                echo '<div class="neetrino-dimensions">';
                echo '<span class="dimensions">' . $metadata['width'] . ' × ' . $metadata['height'] . ' px</span>';
                echo '</div>';
            } else {
                echo '<span class="no-dimensions">—</span>';
            }
        }
        
        if ($column_name == 'file_size') {
            $file_path = get_attached_file($attachment_id);
            if ($file_path && file_exists($file_path)) {
                $file_size = filesize($file_path);
                $file_size_formatted = size_format($file_size);
                echo '<div class="neetrino-file-size" data-size="' . $file_size . '">';
                echo '<span class="file-size">' . $file_size_formatted . '</span>';
                echo '</div>';
            } else {
                echo '<span class="no-size">—</span>';
            }
        }
    }
    
    /**
     * Подключение скриптов для медиа-библиотеки
     */
    public function enqueue_media_scripts($hook) {
        if ($hook === 'upload.php') {
            $settings = get_option('neetrino_wordpress_design_settings', []);
            
            if (!isset($settings['enable_media_dimensions']) || $settings['enable_media_dimensions']) {
                // Добавляем стили для колонок
                echo '<style>
                    .column-image_dimensions {
                        width: 120px;
                    }
                    .column-file_size {
                        width: 100px;
                    }
                    .neetrino-dimensions {
                        font-size: 12px;
                        line-height: 1.4;
                    }
                    .neetrino-dimensions .dimensions {
                        font-weight: 600;
                        color: #2271b1;
                        font-size: 12px;
                    }
                    .neetrino-file-size {
                        font-size: 12px;
                        line-height: 1.4;
                    }
                    .neetrino-file-size .file-size {
                        color: #646970;
                        font-size: 13px;
                        font-weight: 600;
                    }
                    .no-dimensions, .no-size {
                        color: #a7aaad;
                        font-style: italic;
                    }
                    /* Стили для сортировочных стрелок */
                    .sortable a {
                        position: relative;
                    }
                    /* Убираем кастомные стрелки, используем стандартные WordPress */
                    .column-image_dimensions.sortable a::after,
                    .column-file_size.sortable a::after {
                        display: none !important;
                    }
                </style>';
            }
        }
    }
    
    /**
     * Добавление возможности сортировки для колонок
     */
    public function add_sortable_columns($columns) {
        $settings = get_option('neetrino_wordpress_design_settings', []);
        
        if (!isset($settings['enable_media_dimensions']) || $settings['enable_media_dimensions']) {
            $columns['image_dimensions'] = 'image_dimensions';
            $columns['file_size'] = 'file_size';
        }
        
        return $columns;
    }
    
    /**
     * Обработка сортировки медиа-файлов
     */
    public function handle_media_sorting($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        if ($orderby == 'image_dimensions') {
            // Добавляем пользовательскую сортировку по размерам
            add_filter('posts_orderby', [$this, 'sort_by_image_dimensions'], 10, 2);
            add_filter('posts_join', [$this, 'join_postmeta_for_dimensions'], 10, 2);
        }
        
        if ($orderby == 'file_size') {
            // Добавляем пользовательскую сортировку по размеру файла
            add_filter('posts_orderby', [$this, 'sort_by_file_size'], 10, 2);
            add_filter('posts_join', [$this, 'join_postmeta_for_file_size'], 10, 2);
        }
    }
    
    /**
     * JOIN для метаданных изображений
     */
    public function join_postmeta_for_dimensions($join, $query) {
        global $wpdb;
        
        if ($query->get('orderby') == 'image_dimensions') {
            $join .= " LEFT JOIN {$wpdb->postmeta} pm_dimensions ON {$wpdb->posts}.ID = pm_dimensions.post_id AND pm_dimensions.meta_key = '_wp_attachment_metadata'";
        }
        
        return $join;
    }
    
    /**
     * JOIN для размера файла
     */
    public function join_postmeta_for_file_size($join, $query) {
        global $wpdb;
        
        if ($query->get('orderby') == 'file_size') {
            $join .= " LEFT JOIN {$wpdb->postmeta} pm_file_size ON {$wpdb->posts}.ID = pm_file_size.post_id AND pm_file_size.meta_key = '_neetrino_file_size'";
        }
        
        return $join;
    }
    
    /**
     * Сортировка по размерам изображения
     */
    public function sort_by_image_dimensions($orderby, $query) {
        global $wpdb;
        
        if ($query->get('orderby') == 'image_dimensions') {
            $order = $query->get('order') ?: 'ASC';
            
            // Простая сортировка по ширине изображения
            $orderby = "
                CAST(
                    REPLACE(
                        REPLACE(
                            SUBSTRING_INDEX(
                                SUBSTRING_INDEX(pm_dimensions.meta_value, 'width\";i:', -1), 
                                ';', 1
                            ), 
                            ':', ''
                        ),
                        '\"', ''
                    ) AS UNSIGNED
                ) {$order}
            ";
        }
        
        return $orderby;
    }
    
    /**
     * Сортировка по размеру файла (упрощенная версия)
     */
    public function sort_by_file_size($orderby, $query) {
        global $wpdb;
        
        if ($query->get('orderby') == 'file_size') {
            $order = $query->get('order') ?: 'ASC';
            
            // Используем сохраненный размер файла или ID поста как запасной вариант
            $orderby = "CAST(COALESCE(pm_file_size.meta_value, {$wpdb->posts}.ID) AS UNSIGNED) {$order}";
        }
        
        return $orderby;
    }
    
    /**
     * Сохранение размера файла при загрузке
     */
    public function save_file_size_meta($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        if ($file_path && file_exists($file_path)) {
            $file_size = filesize($file_path);
            update_post_meta($attachment_id, '_neetrino_file_size', $file_size);
        }
    }
    
    /**
     * Обновление размеров файлов для уже существующих медиа
     */
    public function update_existing_file_sizes() {
        $attachments = get_posts([
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_neetrino_file_size',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);
        
        foreach ($attachments as $attachment) {
            $this->save_file_size_meta($attachment->ID);
        }
    }
    
    /**
     * Статический метод для админ-страницы
     */
    public static function admin_page() {        $settings = get_option('neetrino_wordpress_design_settings', [
            'enable_modern_buttons' => true,
            'enable_rounded_corners' => true,
            'enable_clean_forms' => true,
            'enable_better_typography' => true,
            'enable_modern_menu' => true,
            'enable_media_dimensions' => true
        ]);
        
        // Сохранение настроек
        if (isset($_POST['save_settings']) && wp_verify_nonce($_POST['neetrino_design_nonce'], 'save_design_settings')) {            $settings = [
                'enable_modern_buttons' => isset($_POST['enable_modern_buttons']),
                'enable_rounded_corners' => isset($_POST['enable_rounded_corners']),
                'enable_clean_forms' => isset($_POST['enable_clean_forms']),
                'enable_better_typography' => isset($_POST['enable_better_typography']),
                'enable_modern_menu' => isset($_POST['enable_modern_menu']),
                'enable_media_dimensions' => isset($_POST['enable_media_dimensions'])
            ];
            update_option('neetrino_wordpress_design_settings', $settings);
            echo '<div class="neetrino-success-notification" style="position: fixed; top: 50px; right: 20px; background: linear-gradient(135deg, #00b894 0%, #00cec9 100%); color: white; padding: 15px 25px; border-radius: 8px; font-weight: 600; font-size: 14px; box-shadow: 0 4px 15px rgba(0, 184, 148, 0.3); z-index: 9999; animation: slideInRight 0.5s ease;">
                <span style="margin-right: 8px;">✅</span> Настройки сохранены!
            </div>
            <script>
                setTimeout(function() {
                    var notification = document.querySelector(".neetrino-success-notification");
                    if (notification) {
                        notification.style.animation = "slideOutRight 0.5s ease";
                        setTimeout(function() { 
                            notification.remove(); 
                        }, 500);
                    }
                }, 3000);
            </script>
            <style>
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOutRight {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            </style>';
        }
        ?>
        <div class="wrap neetrino-dashboard">
            <div class="neetrino-header">
                <div class="neetrino-header-left">
                    <h1><span class="dashicons dashicons-admin-appearance"></span> WordPress Design</h1>
                </div>
            </div>
            
            <style>
                .neetrino-header h1 {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                    font-size: 2.5em;
                    font-weight: 700;
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 15px;
                }
                .neetrino-header h1 .dashicons {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    font-size: 40px;
                }
                .neetrino-settings-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                    gap: 15px;
                    margin: 20px 0;
                }
                .neetrino-setting-card {
                    background: #fff;
                    border: 1px solid #e1e5e9;
                    border-radius: 8px;
                    padding: 18px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
                    transition: all 0.3s ease;
                    position: relative;
                    overflow: hidden;
                    min-height: 140px;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                }
                .neetrino-setting-card:hover {
                    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
                    transform: translateY(-1px);
                }
                .neetrino-setting-card::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 3px;
                    background: linear-gradient(90deg, #667eea, #764ba2);
                }
                .neetrino-setting-header {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    text-align: center;
                    margin-bottom: 15px;
                }
                .neetrino-setting-icon {
                    font-size: 24px;
                    width: 45px;
                    height: 45px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border-radius: 8px;
                    margin-bottom: 8px;
                }
                .neetrino-setting-title {
                    font-size: 15px;
                    font-weight: 600;
                    color: #2c3e50;
                    margin: 0;
                    line-height: 1.3;
                }
                .neetrino-toggle-section {
                    position: absolute;
                    top: 12px;
                    right: 12px;
                }
                .neetrino-toggle {
                    position: relative;
                    display: inline-block;
                    width: 50px;
                    height: 26px;
                }
                .neetrino-toggle input {
                    opacity: 0;
                    width: 0;
                    height: 0;
                }
                .neetrino-slider {
                    position: absolute;
                    cursor: pointer;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background-color: #ccc;
                    transition: .4s;
                    border-radius: 26px;
                }
                .neetrino-slider:before {
                    position: absolute;
                    content: "";
                    height: 20px;
                    width: 20px;
                    left: 3px;
                    bottom: 3px;
                    background-color: white;
                    transition: .4s;
                    border-radius: 50%;
                }
                input:checked + .neetrino-slider {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                }
                input:checked + .neetrino-slider:before {
                    transform: translateX(24px);
                }
                .neetrino-setting-description {
                    color: #7f8c8d;
                    font-size: 11px;
                    line-height: 1.4;
                    margin: 0;
                    text-align: center;
                }
                .neetrino-save-section {
                    background: #fff;
                    border: 1px solid #e1e5e9;
                    border-radius: 8px;
                    padding: 20px;
                    text-align: center;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
                    margin-top: 15px;
                }
                .neetrino-save-btn {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                    border: none !important;
                    color: white !important;
                    padding: 10px 25px !important;
                    font-size: 14px !important;
                    font-weight: 600 !important;
                    border-radius: 6px !important;
                    cursor: pointer !important;
                    transition: all 0.3s ease !important;
                }
                .neetrino-save-btn:hover {
                    transform: translateY(-1px) !important;
                    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3) !important;
                }
            </style>
            
            <div class="neetrino-content">
                <form method="post" action="">
                    <?php wp_nonce_field('save_design_settings', 'neetrino_design_nonce'); ?>
                    
                    <div class="neetrino-settings-grid">
                        <div class="neetrino-setting-card">
                            <div class="neetrino-setting-header">
                                <div class="neetrino-setting-icon">🎨</div>
                                <h3 class="neetrino-setting-title">Современные кнопки</h3>
                            </div>
                            <div class="neetrino-toggle-section">
                                <label class="neetrino-toggle">
                                    <input type="checkbox" name="enable_modern_buttons" 
                                           <?php checked($settings['enable_modern_buttons']); ?>>
                                    <span class="neetrino-slider"></span>
                                </label>
                            </div>
                            <p class="neetrino-setting-description">Применить современный стиль кнопок с красивыми цветами и hover-эффектами</p>
                        </div>
                        
                        <div class="neetrino-setting-card">
                            <div class="neetrino-setting-header">
                                <div class="neetrino-setting-icon">🔘</div>
                                <h3 class="neetrino-setting-title">Скругленные углы</h3>
                            </div>
                            <div class="neetrino-toggle-section">
                                <label class="neetrino-toggle">
                                    <input type="checkbox" name="enable_rounded_corners" 
                                           <?php checked($settings['enable_rounded_corners']); ?>>
                                    <span class="neetrino-slider"></span>
                                </label>
                            </div>
                            <p class="neetrino-setting-description">Современные скругленные края для карточек и форм</p>
                        </div>
                        
                        <div class="neetrino-setting-card">
                            <div class="neetrino-setting-header">
                                <div class="neetrino-setting-icon">📝</div>
                                <h3 class="neetrino-setting-title">Чистые формы</h3>
                            </div>
                            <div class="neetrino-toggle-section">
                                <label class="neetrino-toggle">
                                    <input type="checkbox" name="enable_clean_forms" 
                                           <?php checked($settings['enable_clean_forms']); ?>>
                                    <span class="neetrino-slider"></span>
                                </label>
                            </div>
                            <p class="neetrino-setting-description">Минималистичный стиль полей ввода и форм</p>
                        </div>
                        
                        <div class="neetrino-setting-card">
                            <div class="neetrino-setting-header">
                                <div class="neetrino-setting-icon">🔤</div>
                                <h3 class="neetrino-setting-title">Улучшенная типографика</h3>
                            </div>
                            <div class="neetrino-toggle-section">
                                <label class="neetrino-toggle">
                                    <input type="checkbox" name="enable_better_typography" 
                                           <?php checked($settings['enable_better_typography']); ?>>
                                    <span class="neetrino-slider"></span>
                                </label>
                            </div>
                            <p class="neetrino-setting-description">Улучшенная читаемость и современная типографика</p>
                        </div>
                        
                        <div class="neetrino-setting-card">
                            <div class="neetrino-setting-header">
                                <div class="neetrino-setting-icon">🎯</div>
                                <h3 class="neetrino-setting-title">Современное левое меню</h3>
                            </div>
                            <div class="neetrino-toggle-section">
                                <label class="neetrino-toggle">
                                    <input type="checkbox" name="enable_modern_menu" 
                                           <?php checked($settings['enable_modern_menu']); ?>>
                                    <span class="neetrino-slider"></span>
                                </label>
                            </div>
                            <p class="neetrino-setting-description">Градиентный фон, улучшенные hover-эффекты, современные иконки</p>
                        </div>
                        
                        <div class="neetrino-setting-card">
                            <div class="neetrino-setting-header">
                                <div class="neetrino-setting-icon">📷</div>
                                <h3 class="neetrino-setting-title">Размеры файлов в медиа</h3>
                            </div>
                            <div class="neetrino-toggle-section">
                                <label class="neetrino-toggle">
                                    <input type="checkbox" name="enable_media_dimensions" 
                                           <?php checked($settings['enable_media_dimensions']); ?>>
                                    <span class="neetrino-slider"></span>
                                </label>
                            </div>
                            <p class="neetrino-setting-description">Добавляет отдельные колонки с размерами изображений и размером файла в медиа-библиотеке с возможностью сортировки</p>
                        </div>
                    </div>
                    
                    <div class="neetrino-save-section">
                        <input type="submit" name="save_settings" class="neetrino-save-btn" value="💾 Сохранить настройки">
                        <p style="margin-top: 10px; color: #7f8c8d; font-size: 14px;">Изменения будут применены немедленно после сохранения</p>
                    </div>
                </form>
                
                <div class="neetrino-card" style="background: #fff; border: 1px solid #e1e5e9; border-radius: 8px; padding: 20px; margin-top: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                        <div style="width: 30px; height: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px;">🎯</div>
                        <h2 style="color: #2c3e50; margin: 0; font-size: 18px;">Превью изменений</h2>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <h4 style="color: #2c3e50; margin: 0 0 10px 0; font-size: 14px;">Кнопки:</h4>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <button style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 12px; cursor: pointer;">Главная</button>
                                <button style="background: #f8f9fa; color: #2c3e50; border: 1px solid #e1e5e9; padding: 8px 16px; border-radius: 6px; font-size: 12px; cursor: pointer;">Вторичная</button>
                                <button style="background: transparent; color: #667eea; border: 1px solid #667eea; padding: 8px 16px; border-radius: 6px; font-size: 12px; cursor: pointer;">Обычная</button>
                            </div>
                        </div>
                        
                        <div>
                            <h4 style="color: #2c3e50; margin: 0 0 10px 0; font-size: 14px;">Поля ввода:</h4>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <input type="text" placeholder="Современное поле" style="border: 1px solid #e1e5e9; border-radius: 6px; padding: 8px 12px; font-size: 12px; width: 100%; box-sizing: border-box;">
                                <select style="border: 1px solid #e1e5e9; border-radius: 6px; padding: 8px 12px; font-size: 12px; width: 100%; box-sizing: border-box;">
                                    <option>Выберите опцию</option>
                                    <option>Опция 1</option>
                                    <option>Опция 2</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 15px; padding: 12px; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); border-radius: 6px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #2c3e50; font-size: 12px; font-weight: 600;">Медиа-библиотека:</span>
                            <div style="display: flex; gap: 20px; align-items: center;">
                                <div style="text-align: center;">
                                    <div style="font-size: 10px; color: #7f8c8d; margin-bottom: 2px;">Размеры</div>
                                    <div style="font-size: 12px; font-weight: 600; color: #2271b1;">1920 × 1080 px</div>
                                    <div style="font-size: 12px; font-weight: 600; color: #2271b1;">800 × 600 px</div>
                                </div>
                                <div style="text-align: center;">
                                    <div style="font-size: 10px; color: #7f8c8d; margin-bottom: 2px;">Вес</div>
                                    <div style="font-size: 13px; font-weight: 600; color: #646970;">2.5 MB</div>
                                    <div style="font-size: 13px; font-weight: 600; color: #646970;">1.2 MB</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

// Инициализация модуля
new Neetrino_WordPress_Design();

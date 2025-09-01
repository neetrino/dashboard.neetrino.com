<?php
/**
 * Module: Chat
 * Description: Плавающие кнопки связи для коммуникации с посетителями
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Chat {
    
    private $settings;
    
    public function __construct() {
        // Инициализация модуля только если он активен
        if (!Neetrino::is_module_active('chat')) {
            return;
        }
        
        // Загрузка настроек
        $default_settings = $this->get_default_settings();
        $saved_settings = get_option('neetrino_chat_settings', []);
        $this->settings = array_merge($default_settings, $saved_settings);
        
        // Хуки и действия модуля
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_footer', [$this, 'render_chat_buttons']);
        add_action('wp_ajax_neetrino_chat_click', [$this, 'track_click']);
        add_action('wp_ajax_nopriv_neetrino_chat_click', [$this, 'track_click']);
        
        // AJAX обработчики для работы с цветами
        add_action('wp_ajax_neetrino_chat_apply_brand_color', [$this, 'ajax_apply_brand_color']);
        add_action('wp_ajax_neetrino_chat_reset_default_color', [$this, 'ajax_reset_default_color']);
        add_action('wp_ajax_neetrino_chat_get_color_info', [$this, 'ajax_get_color_info']);
    }
    
    /**
     * Подключение CSS и JS файлов
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'neetrino-chat-style',
            plugin_dir_url(__FILE__) . 'assets/chat.css',
            [],
            '1.0.0'
        );
        
        wp_enqueue_script(
            'neetrino-chat-script',
            plugin_dir_url(__FILE__) . 'assets/chat.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        // Подключаем Iconify для иконок
        wp_enqueue_script(
            'iconify',
            'https://code.iconify.design/3/3.1.1/iconify.min.js',
            [],
            '3.1.1',
            true
        );
        
        // Передача настроек в JavaScript
        wp_localize_script('neetrino-chat-script', 'neetrino_chat', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('neetrino_chat_nonce'),
            'settings' => $this->settings,
            'single_channel' => $this->get_single_channel(),
            'active_channels' => $this->get_active_channels(),
            'optimal_color' => $this->get_optimal_color(),
            'brand_colors' => $this->get_channel_brand_colors()
        ]);
    }
    
    /**
     * Отрисовка кнопок чата
     */
    public function render_chat_buttons() {
        // Проверка времени работы
        if (!$this->is_working_hours()) {
            return;
        }
        
        // Проверка страниц-исключений
        if ($this->is_excluded_page()) {
            return;
        }
        
        // Определяем позицию - всегда используем точные координаты
        $position_x = isset($this->settings['position_x']) ? $this->settings['position_x'] : '90';
        $position_y = isset($this->settings['position_y']) ? $this->settings['position_y'] : '85';
        $mobile_position_x = isset($this->settings['mobile_position_x']) ? $this->settings['mobile_position_x'] : '90';
        $mobile_position_y = isset($this->settings['mobile_position_y']) ? $this->settings['mobile_position_y'] : '90';
        
        // Проверяем есть ли только один активный канал
        $single_channel = $this->get_single_channel();
        $active_channels = $this->get_active_channels();
        
        // Создаем CSS с медиазапросами для правильного позиционирования
        $position_style = "left: {$position_x}%; top: {$position_y}%; transform: translate(-50%, -50%);";
        $position_class = 'neetrino-chat-custom';
        
        $layout_class = 'neetrino-chat-' . $this->settings['layout'];
        $style_class = 'neetrino-chat-' . $this->settings['style'];
        
        // Добавляем inline CSS для правильного позиционирования с самого начала
        // Ограничиваем позицию чтобы виджет не выходил за пределы экрана
        $desktop_x = max(5, min(95, $position_x));
        $desktop_y = max(5, min(95, $position_y));
        $mobile_x = max(10, min(90, $mobile_position_x));
        $mobile_y = max(10, min(90, $mobile_position_y));
        
        ?>
        <style>
            @media (min-width: 769px) {
                #neetrino-chat-widget {
                    left: <?php echo esc_attr($desktop_x); ?>% !important;
                    top: <?php echo esc_attr($desktop_y); ?>% !important;
                }
            }
            @media (max-width: 768px) {
                #neetrino-chat-widget {
                    left: <?php echo esc_attr($mobile_x); ?>% !important;
                    top: <?php echo esc_attr($mobile_y); ?>% !important;
                }
            }
        </style>
        
        <div id="neetrino-chat-widget" class="neetrino-chat-widget <?php echo esc_attr($position_class . ' ' . $layout_class . ' ' . $style_class); ?>" 
             style="transform: translate(-50%, -50%);"
             data-position="<?php echo esc_attr($this->settings['position']); ?>"
             data-layout="<?php echo esc_attr($this->settings['layout']); ?>"
             data-position-x="<?php echo esc_attr($desktop_x); ?>"
             data-position-y="<?php echo esc_attr($desktop_y); ?>"
             data-mobile-position-x="<?php echo esc_attr($mobile_x); ?>"
             data-mobile-position-y="<?php echo esc_attr($mobile_y); ?>"
             data-single-channel="<?php echo $single_channel ? 'true' : 'false'; ?>"
             <?php if ($single_channel): ?>
             data-single-type="<?php echo esc_attr($single_channel['type']); ?>"
             data-single-contact="<?php echo esc_attr($single_channel['contact']); ?>"
             <?php endif; ?>>
            
            <!-- Главная кнопка -->
            <div class="neetrino-chat-main-button" title="<?php echo esc_attr($single_channel ? $single_channel['title'] : __('Связаться с нами', 'neetrino')); ?>">
                <?php if ($single_channel): ?>
                    <span class="iconify" data-icon="<?php echo esc_attr($single_channel['icon']); ?>" data-width="24" data-height="24" style="color: white;"></span>
                <?php else: ?>
                    <span class="iconify" data-icon="material-symbols:chat-bubble-outline" data-width="24" data-height="24" style="color: white;"></span>
                <?php endif; ?>
                <span class="neetrino-chat-pulse"></span>
            </div>
            
            <!-- Кнопки мессенджеров (скрываем если только один канал) -->
            <div class="neetrino-chat-buttons" <?php echo $single_channel ? 'style="display: none;"' : ''; ?>>
                
                <?php if (!empty($this->settings['phone'])): ?>
                <div class="neetrino-chat-button" data-type="phone" data-contact="<?php echo esc_attr($this->settings['phone']); ?>">
                    <span class="iconify" data-icon="material-symbols:call" data-width="24" data-height="24" style="color: white;"></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($this->settings['whatsapp'])): ?>
                <div class="neetrino-chat-button" data-type="whatsapp" data-contact="<?php echo esc_attr($this->settings['whatsapp']); ?>">
                    <span class="iconify" data-icon="simple-icons:whatsapp" data-width="24" data-height="24" style="color: white;"></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($this->settings['telegram'])): ?>
                <div class="neetrino-chat-button" data-type="telegram" data-contact="<?php echo esc_attr($this->settings['telegram']); ?>">
                    <span class="iconify" data-icon="simple-icons:telegram" data-width="24" data-height="24" style="color: white;"></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($this->settings['viber'])): ?>
                <div class="neetrino-chat-button" data-type="viber" data-contact="<?php echo esc_attr($this->settings['viber']); ?>">
                    <span class="iconify" data-icon="simple-icons:viber" data-width="24" data-height="24" style="color: white;"></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($this->settings['email'])): ?>
                <div class="neetrino-chat-button" data-type="email" data-contact="<?php echo esc_attr($this->settings['email']); ?>">
                    <span class="iconify" data-icon="material-symbols:mail-outline" data-width="24" data-height="24" style="color: white;"></span>
                </div>
                <?php endif; ?>
                
            </div>
            
            <!-- Индикатор статуса -->
            <div class="neetrino-chat-status">
                <span class="neetrino-chat-status-dot"></span>
                <span class="neetrino-chat-status-text"><?php echo esc_html(!empty($this->settings['status_text']) ? $this->settings['status_text'] : __('Онлайн', 'neetrino')); ?></span>
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Трекинг кликов для статистики
     */
    public function track_click() {
        check_ajax_referer('neetrino_chat_nonce', 'nonce');
        
        $type = sanitize_text_field($_POST['type']);
        $contact = sanitize_text_field($_POST['contact']);
        
        // Получаем текущую статистику
        $stats = get_option('neetrino_chat_stats', []);
        
        if (!isset($stats[$type])) {
            $stats[$type] = 0;
        }
        
        $stats[$type]++;
        
        // Обновляем статистику
        update_option('neetrino_chat_stats', $stats);
        
        wp_send_json_success(['message' => 'Click tracked']);
    }
    
    /**
     * Проверка рабочих часов
     */
    private function is_working_hours() {
        if (!$this->settings['working_hours_enabled']) {
            return true;
        }
        
        $current_time = current_time('H:i');
        $current_day = current_time('w'); // 0 = воскресенье, 1 = понедельник, ...
        
        // Проверка дня недели
        if (!is_array($this->settings['working_days']) || !in_array($current_day, $this->settings['working_days'])) {
            return false;
        }
        
        // Проверка времени
        $start_time = $this->settings['working_hours_start'];
        $end_time = $this->settings['working_hours_end'];
        
        return ($current_time >= $start_time && $current_time <= $end_time);
    }
    
    /**
     * Проверка исключенных страниц
     */
    private function is_excluded_page() {
        if (empty($this->settings['excluded_pages']) || !is_array($this->settings['excluded_pages'])) {
            return false;
        }
        
        global $post;
        
        if (is_page() && $post) {
            return in_array($post->ID, $this->settings['excluded_pages']);
        }
        
        return false;
    }
    
    /**
     * Получение активных каналов связи
     */
    private function get_active_channels() {
        $channels = [];
        
        if (!empty($this->settings['phone'])) {
            $channels['phone'] = [
                'type' => 'phone',
                'contact' => $this->settings['phone'],
                'icon' => 'material-symbols:call',
                'title' => __('Позвонить', 'neetrino')
            ];
        }
        
        if (!empty($this->settings['whatsapp'])) {
            $channels['whatsapp'] = [
                'type' => 'whatsapp',
                'contact' => $this->settings['whatsapp'],
                'icon' => 'simple-icons:whatsapp',
                'title' => __('WhatsApp', 'neetrino')
            ];
        }
        
        if (!empty($this->settings['telegram'])) {
            $channels['telegram'] = [
                'type' => 'telegram',
                'contact' => $this->settings['telegram'],
                'icon' => 'simple-icons:telegram',
                'title' => __('Telegram', 'neetrino')
            ];
        }
        
        if (!empty($this->settings['viber'])) {
            $channels['viber'] = [
                'type' => 'viber',
                'contact' => $this->settings['viber'],
                'icon' => 'simple-icons:viber',
                'title' => __('Viber', 'neetrino')
            ];
        }
        
        if (!empty($this->settings['email'])) {
            $channels['email'] = [
                'type' => 'email',
                'contact' => $this->settings['email'],
                'icon' => 'material-symbols:mail-outline',
                'title' => __('Email', 'neetrino')
            ];
        }
        
        return $channels;
    }
    
    /**
     * Проверка есть ли только один активный канал
     */
    private function get_single_channel() {
        $active_channels = $this->get_active_channels();
        
        if (count($active_channels) === 1) {
            return reset($active_channels); // Возвращает первый (единственный) элемент
        }
        
        return false;
    }
    
    /**
     * Получение фирменных цветов каналов
     */
    public function get_channel_brand_colors() {
        return [
            'phone' => '#3498db',      // Синий для телефона
            'whatsapp' => '#25d366',   // Зеленый WhatsApp
            'telegram' => '#0088cc',   // Синий Telegram
            'viber' => '#665cac',      // Фиолетовый Viber
            'email' => '#ea4335',      // Красный Gmail/Email
            'default' => '#2ecc71'     // Дефолтный зеленый
        ];
    }
    
    /**
     * Получение оптимального цвета для единственного канала
     */
    public function get_optimal_color() {
        $single_channel = $this->get_single_channel();
        
        if (!$single_channel) {
            return $this->settings['color']; // Текущий цвет если несколько каналов
        }
        
        $brand_colors = $this->get_channel_brand_colors();
        $channel_type = $single_channel['type'];
        
        // Проверяем, был ли установлен пользовательский цвет для единственного канала
        $custom_single_color = get_option('neetrino_chat_single_channel_custom_color', false);
        
        if ($custom_single_color) {
            return $this->settings['color']; // Используем пользовательский цвет
        }
        
        // Возвращаем фирменный цвет канала
        return isset($brand_colors[$channel_type]) ? $brand_colors[$channel_type] : $brand_colors['default'];
    }
    
    /**
     * Применение фирменного цвета канала
     */
    public function apply_channel_brand_color() {
        $single_channel = $this->get_single_channel();
        
        if (!$single_channel) {
            return false;
        }
        
        $brand_colors = $this->get_channel_brand_colors();
        $channel_type = $single_channel['type'];
        $brand_color = isset($brand_colors[$channel_type]) ? $brand_colors[$channel_type] : $brand_colors['default'];
        
        // Обновляем настройки
        $this->settings['color'] = $brand_color;
        $saved_settings = get_option('neetrino_chat_settings', []);
        $saved_settings['color'] = $brand_color;
        update_option('neetrino_chat_settings', $saved_settings);
        
        // Убираем флаг пользовательского цвета
        delete_option('neetrino_chat_single_channel_custom_color');
        
        return true;
    }
    
    /**
     * Сброс к дефолтному цвету
     */
    public function reset_to_default_color() {
        $default_color = '#2ecc71';
        
        // Обновляем настройки
        $this->settings['color'] = $default_color;
        $saved_settings = get_option('neetrino_chat_settings', []);
        $saved_settings['color'] = $default_color;
        update_option('neetrino_chat_settings', $saved_settings);
        
        // Устанавливаем флаг пользовательского цвета
        update_option('neetrino_chat_single_channel_custom_color', true);
        
        return true;
    }
    
    /**
     * AJAX: Применение фирменного цвета канала
     */
    public function ajax_apply_brand_color() {
        check_ajax_referer('neetrino_chat_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Недостаточно прав']);
        }
        
        $result = $this->apply_channel_brand_color();
        
        if ($result) {
            $single_channel = $this->get_single_channel();
            $brand_colors = $this->get_channel_brand_colors();
            $new_color = $brand_colors[$single_channel['type']];
            
            wp_send_json_success([
                'message' => 'Фирменный цвет применен',
                'color' => $new_color,
                'channel_type' => $single_channel['type']
            ]);
        } else {
            wp_send_json_error(['message' => 'Ошибка применения цвета']);
        }
    }
    
    /**
     * AJAX: Сброс к дефолтному цвету
     */
    public function ajax_reset_default_color() {
        check_ajax_referer('neetrino_chat_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Недостаточно прав']);
        }
        
        $result = $this->reset_to_default_color();
        
        if ($result) {
            wp_send_json_success([
                'message' => 'Цвет сброшен к дефолтному',
                'color' => '#2ecc71'
            ]);
        } else {
            wp_send_json_error(['message' => 'Ошибка сброса цвета']);
        }
    }
    
    /**
     * AJAX: Получение информации о цветах
     */
    public function ajax_get_color_info() {
        check_ajax_referer('neetrino_chat_nonce', 'nonce');
        
        $single_channel = $this->get_single_channel();
        $brand_colors = $this->get_channel_brand_colors();
        $current_color = $this->settings['color'];
        $optimal_color = $this->get_optimal_color();
        
        wp_send_json_success([
            'single_channel' => $single_channel,
            'brand_colors' => $brand_colors,
            'current_color' => $current_color,
            'optimal_color' => $optimal_color,
            'is_custom' => get_option('neetrino_chat_single_channel_custom_color', false)
        ]);
    }
    
    /**
     * Настройки по умолчанию
     */
    private function get_default_settings() {
        return [
            'phone' => '',
            'whatsapp' => '',
            'telegram' => '',
            'viber' => '',
            'email' => '',
            'position' => 'bottom-right',
            'position_x' => 95, // 95% от левого края
            'position_y' => 85, // 85% от верхнего края
            'mobile_position_x' => 90, // 90% от левого края для мобильных
            'mobile_position_y' => 90, // 90% от верхнего края для мобильных
            'layout' => 'vertical',
            'style' => 'round',
            'size' => 'medium',
            'color' => '#2ecc71',
            'animation' => 'bounce',
            'working_hours_enabled' => false,
            'working_hours_start' => '09:00',
            'working_hours_end' => '18:00',
            'working_days' => [1, 2, 3, 4, 5], // Пн-Пт
            'excluded_pages' => [],
            'mobile_visible' => true,
            'desktop_visible' => true,
            'pulse_effect' => true
        ];
    }
    
    /**
     * Статический метод для админ-страницы
     */
    public static function admin_page() {
        // Подключаем класс админки
        require_once __DIR__ . '/admin.php';
        
        // Создаем экземпляр админки и отображаем
        $admin = new Neetrino_Chat_Admin();
        $admin->render();
    }
}

// Инициализация модуля
new Neetrino_Chat();

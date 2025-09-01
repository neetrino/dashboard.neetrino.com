<?php
/**
 * Chat Module Admin Interface
 * Description: Админская страница для настройки чата с табами
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Chat_Admin {
    
    private $settings;
    private $stats;
    
    public function __construct() {
        $this->load_settings();
        $this->handle_form_submission();
    }
    
    /**
     * Загрузка настроек
     */
    private function load_settings() {
        $default_settings = $this->get_default_settings();
        $saved_settings = get_option('neetrino_chat_settings', []);
        $this->settings = array_merge($default_settings, $saved_settings);
        
        // Загрузка статистики
        $this->stats = get_option('neetrino_chat_stats', []);
        
        // Очистка некорректных данных статистики
        if (is_array($this->stats)) {
            $clean_stats = [];
            foreach ($this->stats as $type => $count) {
                if (is_numeric($count)) {
                    $clean_stats[$type] = (int)$count;
                } else {
                    $clean_stats[$type] = 0;
                }
            }
            $this->stats = $clean_stats;
            update_option('neetrino_chat_stats', $this->stats);
        } else {
            $this->stats = [];
        }
    }
    
    /**
     * Обработка отправки формы
     */
    private function handle_form_submission() {
        // Обработка сохранения настроек
        if (isset($_POST['save_settings'])) {
            $new_settings = [
                'phone' => sanitize_text_field($_POST['phone']),
                'whatsapp' => sanitize_text_field($_POST['whatsapp']),
                'telegram' => sanitize_text_field($_POST['telegram']),
                'viber' => sanitize_text_field($_POST['viber']),
                'email' => sanitize_email($_POST['email']),
                'position_x' => floatval($_POST['position_x']),
                'position_y' => floatval($_POST['position_y']),
                'mobile_position_x' => floatval($_POST['mobile_position_x']),
                'mobile_position_y' => floatval($_POST['mobile_position_y']),
                'layout' => sanitize_text_field($_POST['layout']),
                'style' => sanitize_text_field($_POST['style']),
                'size' => sanitize_text_field($_POST['size']),
                'color' => sanitize_hex_color($_POST['color']),
                'animation' => sanitize_text_field($_POST['animation']),
                'working_hours_enabled' => isset($_POST['working_hours_enabled']),
                'working_hours_start' => sanitize_text_field($_POST['working_hours_start']),
                'working_hours_end' => sanitize_text_field($_POST['working_hours_end']),
                'working_days' => array_map('intval', $_POST['working_days'] ?? []),
                'excluded_pages' => array_map('intval', $_POST['excluded_pages'] ?? []),
                'mobile_visible' => isset($_POST['mobile_visible']),
                'desktop_visible' => isset($_POST['desktop_visible']),
                'pulse_effect' => isset($_POST['pulse_effect']),
                'status_text' => sanitize_text_field($_POST['status_text'])
            ];
            
            update_option('neetrino_chat_settings', $new_settings);
            $this->settings = array_merge($this->get_default_settings(), $new_settings);
            
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>' . __('Настройки сохранены!', 'neetrino') . '</p></div>';
            });
        }
        
        // Обработка сброса статистики
        if (isset($_POST['reset_stats'])) {
            update_option('neetrino_chat_stats', []);
            $this->stats = [];
            
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>' . __('Статистика сброшена!', 'neetrino') . '</p></div>';
            });
        }
    }
    
    /**
     * Отображение админской страницы
     */
    public function render() {
        $this->enqueue_admin_assets();
        
        // Получение списка страниц для исключений
        $pages = get_pages();
        ?>
        
        <div class="wrap neetrino-chat-admin">
            <div class="neetrino-header">
                <div class="neetrino-header-left">
                    <h1><?php _e('Настройки чата', 'neetrino'); ?></h1>
                </div>
                <div class="neetrino-header-right">
                    <div class="neetrino-stats-summary">
                        <div class="neetrino-stat-box">
                            <span class="neetrino-stat-number"><?php 
                                $total = 0;
                                if (is_array($this->stats) && !empty($this->stats)) {
                                    foreach ($this->stats as $count) {
                                        if (is_numeric($count)) {
                                            $total += (int)$count;
                                        }
                                    }
                                }
                                echo $total;
                            ?></span>
                            <span class="neetrino-stat-label"><?php _e('Всего кликов', 'neetrino'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Навигация по табам -->
            <div class="neetrino-tabs">
                <nav class="neetrino-tab-nav">
                    <button type="button" class="neetrino-tab-button active" data-tab="contacts">
                        <i class="dashicons dashicons-phone"></i>
                        <?php _e('Контакты', 'neetrino'); ?>
                    </button>
                    <button type="button" class="neetrino-tab-button" data-tab="appearance">
                        <i class="dashicons dashicons-admin-appearance"></i>
                        <?php _e('Внешний вид', 'neetrino'); ?>
                    </button>
                    <button type="button" class="neetrino-tab-button" data-tab="schedule">
                        <i class="dashicons dashicons-clock"></i>
                        <?php _e('Расписание', 'neetrino'); ?>
                    </button>
                    <button type="button" class="neetrino-tab-button" data-tab="settings">
                        <i class="dashicons dashicons-admin-generic"></i>
                        <?php _e('Настройки', 'neetrino'); ?>
                    </button>
                    <button type="button" class="neetrino-tab-button" data-tab="stats">
                        <i class="dashicons dashicons-chart-bar"></i>
                        <?php _e('Статистика', 'neetrino'); ?>
                    </button>
                </nav>
                
                <div class="neetrino-tab-content">
                    <form method="post" action="">
                        
                        <!-- Вкладка: Контакты -->
                        <div id="tab-contacts" class="neetrino-tab-panel active">
                            <div class="neetrino-card">
                                <h2><?php _e('Контактная информация', 'neetrino'); ?></h2>
                                
                                <!-- Контактная информация в две колонки -->
                                <div class="neetrino-appearance-grid">
                                    <div class="neetrino-appearance-column">
                                        <div class="neetrino-form-group">
                                            <label for="phone"><?php _e('Номер телефона', 'neetrino'); ?></label>
                                            <input type="tel" id="phone" name="phone" value="<?php echo esc_attr($this->settings['phone']); ?>" 
                                                   placeholder="+7 (999) 123-45-67" class="regular-text">
                                            <p class="description"><?php _e('Для прямых звонков с мобильных устройств', 'neetrino'); ?></p>
                                        </div>
                                        
                                        <div class="neetrino-form-group">
                                            <label for="whatsapp"><?php _e('WhatsApp номер', 'neetrino'); ?></label>
                                            <input type="tel" id="whatsapp" name="whatsapp" value="<?php echo esc_attr($this->settings['whatsapp']); ?>" 
                                                   placeholder="+79991234567" class="regular-text">
                                            <p class="description"><?php _e('Номер в международном формате без + и пробелов', 'neetrino'); ?></p>
                                        </div>
                                        
                                        <div class="neetrino-form-group">
                                            <label for="telegram"><?php _e('Telegram', 'neetrino'); ?></label>
                                            <input type="text" id="telegram" name="telegram" value="<?php echo esc_attr($this->settings['telegram']); ?>" 
                                                   placeholder="@username или имя_пользователя" class="regular-text">
                                            <p class="description"><?php _e('Username без @ или с @', 'neetrino'); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="neetrino-appearance-column">
                                        <div class="neetrino-form-group">
                                            <label for="viber"><?php _e('Viber номер', 'neetrino'); ?></label>
                                            <input type="tel" id="viber" name="viber" value="<?php echo esc_attr($this->settings['viber']); ?>" 
                                                   placeholder="+79991234567" class="regular-text">
                                            <p class="description"><?php _e('Номер в международном формате', 'neetrino'); ?></p>
                                        </div>
                                        
                                        <div class="neetrino-form-group">
                                            <label for="email"><?php _e('Email адрес', 'neetrino'); ?></label>
                                            <input type="email" id="email" name="email" value="<?php echo esc_attr($this->settings['email']); ?>" 
                                                   placeholder="info@example.com" class="regular-text">
                                            <p class="description"><?php _e('Для отправки писем', 'neetrino'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Вкладка: Внешний вид -->
                        <div id="tab-appearance" class="neetrino-tab-panel">
                            <div class="neetrino-card">
                                <h2><?php _e('Внешний вид', 'neetrino'); ?></h2>
                                <div class="neetrino-form-group neetrino-position-controls-main">
                                    <!-- Две колонки для десктопа и мобильных настроек -->
                                    <div class="neetrino-appearance-grid">
                                        <!-- Колонка для десктопа -->
                                        <div class="neetrino-appearance-column">
                                            <div class="neetrino-position-preview">
                                <div class="neetrino-preview-screen">
                                    <div class="neetrino-preview-widget" id="preview_widget">
                                        <div class="neetrino-preview-button"></div>
                                    </div>
                                </div>
                                <p class="description"><?php _e('Десктопный', 'neetrino'); ?></p>
                                <div class="neetrino-position-presets">
                                    <button type="button" class="preset-btn" data-position="bottom-right">
                                        <?php _e('Правый низ', 'neetrino'); ?>
                                    </button>
                                    <button type="button" class="preset-btn" data-position="bottom-left">
                                        <?php _e('Левый низ', 'neetrino'); ?>
                                    </button>
                                    <button type="button" class="preset-btn" data-position="top-right">
                                        <?php _e('Правый верх', 'neetrino'); ?>
                                    </button>
                                    <button type="button" class="preset-btn" data-position="top-left">
                                        <?php _e('Левый верх', 'neetrino'); ?>
                                    </button>
                                    <button type="button" class="preset-btn" data-position="middle-center">
                                        <?php _e('Центр', 'neetrino'); ?>
                                    </button>
                                </div>
                            </div>
                            <div class="neetrino-position-controls">
                                <div class="neetrino-position-control">
                                    <label for="position_x"><?php _e('Горизонтальная позиция', 'neetrino'); ?></label>
                                    <div class="neetrino-slider-container">
                                        <span class="neetrino-slider-label"><?php _e('Левый край', 'neetrino'); ?></span>
                                        <input type="range" id="position_x" name="position_x" 
                                               min="0" max="100" step="0.5" 
                                               value="<?php echo esc_attr($this->settings['position_x']); ?>"
                                               class="neetrino-position-slider">
                                        <span class="neetrino-slider-label"><?php _e('Правый край', 'neetrino'); ?></span>
                                    </div>
                                    <div class="neetrino-position-value">
                                        <span id="position_x_value"><?php echo esc_attr($this->settings['position_x']); ?>%</span>
                                        <?php _e('от левого края', 'neetrino'); ?>
                                    </div>
                                </div>
                                
                                <div class="neetrino-position-control">
                                    <label for="position_y"><?php _e('Вертикальная позиция', 'neetrino'); ?></label>
                                    <div class="neetrino-slider-container">
                                        <span class="neetrino-slider-label"><?php _e('Верхний край', 'neetrino'); ?></span>
                                        <input type="range" id="position_y" name="position_y" 
                                               min="0" max="100" step="0.5" 
                                               value="<?php echo esc_attr($this->settings['position_y']); ?>"
                                               class="neetrino-position-slider">
                                        <span class="neetrino-slider-label"><?php _e('Нижний край', 'neetrino'); ?></span>
                                    </div>
                                    <div class="neetrino-position-value">
                                        <span id="position_y_value"><?php echo esc_attr($this->settings['position_y']); ?>%</span>
                                        <?php _e('от верхнего края', 'neetrino'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Колонка для мобильных устройств -->
                        <div class="neetrino-appearance-column">
                            <div class="neetrino-position-preview">
                                <div class="neetrino-preview-screen mobile-preview">
                                    <div class="phone-speaker"></div>
                                    <div class="mobile-label">Mobile</div>
                                    <div class="neetrino-preview-widget" id="mobile_preview_widget">
                                        <div class="neetrino-preview-button"></div>
                                    </div>
                                </div>
                                <p class="description"><?php _e('Мобильный', 'neetrino'); ?></p>
                                <div class="neetrino-position-presets">
                                    <button type="button" class="preset-btn mobile-preset" data-position="bottom-right">
                                        <?php _e('Правый низ', 'neetrino'); ?>
                                    </button>
                                    <button type="button" class="preset-btn mobile-preset" data-position="bottom-left">
                                        <?php _e('Левый низ', 'neetrino'); ?>
                                    </button>
                                    <button type="button" class="preset-btn mobile-preset" data-position="top-right">
                                        <?php _e('Правый верх', 'neetrino'); ?>
                                    </button>
                                    <button type="button" class="preset-btn mobile-preset" data-position="top-left">
                                        <?php _e('Левый верх', 'neetrino'); ?>
                                    </button>
                                    <button type="button" class="preset-btn mobile-preset" data-position="middle-center">
                                        <?php _e('Центр', 'neetrino'); ?>
                                    </button>
                                </div>
                            </div>
                            <div class="neetrino-position-controls">
                                <div class="neetrino-position-control">
                                    <label for="mobile_position_x"><?php _e('Горизонтальная позиция', 'neetrino'); ?></label>
                                    <div class="neetrino-slider-container">
                                        <span class="neetrino-slider-label"><?php _e('Левый край', 'neetrino'); ?></span>
                                        <input type="range" id="mobile_position_x" name="mobile_position_x" 
                                               min="0" max="100" step="0.5" 
                                               value="<?php echo esc_attr(isset($this->settings['mobile_position_x']) ? $this->settings['mobile_position_x'] : 90); ?>"
                                               class="neetrino-position-slider">
                                        <span class="neetrino-slider-label"><?php _e('Правый край', 'neetrino'); ?></span>
                                    </div>
                                    <div class="neetrino-position-value">
                                        <span id="mobile_position_x_value"><?php echo esc_attr(isset($this->settings['mobile_position_x']) ? $this->settings['mobile_position_x'] : 90); ?>%</span>
                                        <?php _e('от левого края', 'neetrino'); ?>
                                    </div>
                                </div>
                                
                                <div class="neetrino-position-control">
                                    <label for="mobile_position_y"><?php _e('Вертикальная позиция', 'neetrino'); ?></label>
                                    <div class="neetrino-slider-container">
                                        <span class="neetrino-slider-label"><?php _e('Верхний край', 'neetrino'); ?></span>
                                        <input type="range" id="mobile_position_y" name="mobile_position_y" 
                                               min="0" max="100" step="0.5" 
                                               value="<?php echo esc_attr(isset($this->settings['mobile_position_y']) ? $this->settings['mobile_position_y'] : 90); ?>"
                                               class="neetrino-position-slider">
                                        <span class="neetrino-slider-label"><?php _e('Нижний край', 'neetrino'); ?></span>
                                    </div>
                                    <div class="neetrino-position-value">
                                        <span id="mobile_position_y_value"><?php echo esc_attr(isset($this->settings['mobile_position_y']) ? $this->settings['mobile_position_y'] : 90); ?>%</span>
                                        <?php _e('от верхнего края', 'neetrino'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                                    </div>
                                </div>

                                <!-- Настройки внешнего вида в две колонки -->
                                <div class="neetrino-appearance-grid">
                                    <div class="neetrino-appearance-column">
                                        <div class="neetrino-form-group">
                                            <label><?php _e('Расположение кнопок', 'neetrino'); ?></label>
                                            <div class="neetrino-button-group">
                                                <label class="neetrino-radio-button">
                                                    <input type="radio" name="layout" value="vertical" <?php checked($this->settings['layout'], 'vertical'); ?>>
                                                    <span class="neetrino-button-text"><?php _e('Вертикальное', 'neetrino'); ?></span>
                                                </label>
                                                <label class="neetrino-radio-button">
                                                    <input type="radio" name="layout" value="horizontal" <?php checked($this->settings['layout'], 'horizontal'); ?>>
                                                    <span class="neetrino-button-text"><?php _e('Горизонтальное', 'neetrino'); ?></span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="neetrino-form-group">
                                            <label><?php _e('Стиль кнопок', 'neetrino'); ?></label>
                                            <div class="neetrino-button-group">
                                                <label class="neetrino-radio-button">
                                                    <input type="radio" name="style" value="round" <?php checked($this->settings['style'], 'round'); ?>>
                                                    <span class="neetrino-button-text"><?php _e('Круглые', 'neetrino'); ?></span>
                                                </label>
                                                <label class="neetrino-radio-button">
                                                    <input type="radio" name="style" value="square" <?php checked($this->settings['style'], 'square'); ?>>
                                                    <span class="neetrino-button-text"><?php _e('Квадратные', 'neetrino'); ?></span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="neetrino-form-group">
                                            <label><?php _e('Размер кнопок', 'neetrino'); ?></label>
                                            <div class="neetrino-button-group">
                                                <label class="neetrino-radio-button">
                                                    <input type="radio" name="size" value="small" <?php checked($this->settings['size'], 'small'); ?>>
                                                    <span class="neetrino-button-text"><?php _e('Маленькие', 'neetrino'); ?></span>
                                                </label>
                                                <label class="neetrino-radio-button">
                                                    <input type="radio" name="size" value="medium" <?php checked($this->settings['size'], 'medium'); ?>>
                                                    <span class="neetrino-button-text"><?php _e('Средние', 'neetrino'); ?></span>
                                                </label>
                                                <label class="neetrino-radio-button">
                                                    <input type="radio" name="size" value="large" <?php checked($this->settings['size'], 'large'); ?>>
                                                    <span class="neetrino-button-text"><?php _e('Большие', 'neetrino'); ?></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="neetrino-appearance-column">
                                        <div class="neetrino-form-group">
                                            <label for="color"><?php _e('Основной цвет', 'neetrino'); ?></label>
                                            <input type="color" id="color" name="color" value="<?php echo esc_attr($this->settings['color']); ?>">
                                            
                                            <?php
                                            // Проверяем есть ли единственный канал
                                            $chat_instance = new Neetrino_Chat();
                                            $single_channel = $this->get_single_channel_info();
                                            if ($single_channel):
                                            ?>
                                            <div class="neetrino-color-controls" style="margin-top: 10px;">
                                                <p class="description">
                                                    <?php printf(__('Обнаружен единственный канал: %s', 'neetrino'), '<strong>' . $single_channel['title'] . '</strong>'); ?>
                                                </p>
                                                <div class="neetrino-color-buttons">
                                                    <button type="button" class="button button-secondary" id="apply-brand-color" 
                                                            data-channel="<?php echo esc_attr($single_channel['type']); ?>"
                                                            data-color="<?php echo esc_attr($single_channel['brand_color']); ?>">
                                                        <span class="iconify" data-icon="<?php echo esc_attr($single_channel['icon']); ?>" style="margin-right: 5px;"></span>
                                                        <?php printf(__('Применить цвет %s', 'neetrino'), $single_channel['title']); ?>
                                                        <span class="color-preview" style="background: <?php echo esc_attr($single_channel['brand_color']); ?>; width: 20px; height: 20px; display: inline-block; border-radius: 3px; margin-left: 5px; vertical-align: middle;"></span>
                                                    </button>
                                                    <button type="button" class="button button-secondary" id="reset-default-color">
                                                        <?php _e('Сбросить к дефолтному', 'neetrino'); ?>
                                                        <span class="color-preview" style="background: #2ecc71; width: 20px; height: 20px; display: inline-block; border-radius: 3px; margin-left: 5px; vertical-align: middle;"></span>
                                                    </button>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="neetrino-form-group">
                                            <label><?php _e('Анимация появления', 'neetrino'); ?></label>
                                            <div class="neetrino-button-group">
                                                <label class="neetrino-radio-button">
                                                    <input type="radio" name="animation" value="none" <?php checked($this->settings['animation'], 'none'); ?>>
                                                    <span class="neetrino-button-text"><?php _e('Без анимации', 'neetrino'); ?></span>
                                                </label>
                                                <label class="neetrino-radio-button">
                                                    <input type="radio" name="animation" value="bounce" <?php checked($this->settings['animation'], 'bounce'); ?>>
                                                    <span class="neetrino-button-text"><?php _e('Отскок', 'neetrino'); ?></span>
                                                </label>
                                                <label class="neetrino-radio-button">
                                                    <input type="radio" name="animation" value="slide" <?php checked($this->settings['animation'], 'slide'); ?>>
                                                    <span class="neetrino-button-text"><?php _e('Скольжение', 'neetrino'); ?></span>
                                                </label>
                                                <label class="neetrino-radio-button">
                                                    <input type="radio" name="animation" value="fade" <?php checked($this->settings['animation'], 'fade'); ?>>
                                                    <span class="neetrino-button-text"><?php _e('Плавное появление', 'neetrino'); ?></span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="neetrino-form-group">
                                            <label class="neetrino-simple-button">
                                                <input type="checkbox" name="pulse_effect" <?php checked($this->settings['pulse_effect']); ?>>
                                                <span class="neetrino-button-text"><?php _e('Пульсация главной кнопки', 'neetrino'); ?></span>
                                            </label>
                                        </div>
                                        
                                        <div class="neetrino-form-group">
                                            <label for="status_text"><?php _e('Текст статуса', 'neetrino'); ?></label>
                                            <input type="text" id="status_text" name="status_text" value="<?php echo esc_attr($this->settings['status_text']); ?>" 
                                                   placeholder="<?php _e('Онлайн', 'neetrino'); ?>" class="regular-text">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Вкладка: Расписание -->
                        <div id="tab-schedule" class="neetrino-tab-panel">
                            <div class="neetrino-card">
                                <h2><?php _e('Расписание работы', 'neetrino'); ?></h2>
                                
                                <div class="neetrino-form-group">
                                    <label class="neetrino-simple-button">
                                        <input type="checkbox" name="working_hours_enabled" <?php checked($this->settings['working_hours_enabled']); ?>>
                                        <span class="neetrino-button-text"><?php _e('Включить расписание работы', 'neetrino'); ?></span>
                                    </label>
                                    <p class="description"><?php _e('Кнопки будут показываться только в рабочее время', 'neetrino'); ?></p>
                                </div>
                                
                                <!-- Настройки расписания в две колонки -->
                                <div class="neetrino-appearance-grid">
                                    <div class="neetrino-appearance-column">
                                        <div class="neetrino-form-group">
                                            <label for="working_hours_start"><?php _e('Время начала работы', 'neetrino'); ?></label>
                                            <input type="time" id="working_hours_start" name="working_hours_start" 
                                                   value="<?php echo esc_attr($this->settings['working_hours_start']); ?>">
                                        </div>
                                        
                                        <div class="neetrino-form-group">
                                            <label for="working_hours_end"><?php _e('Время окончания работы', 'neetrino'); ?></label>
                                            <input type="time" id="working_hours_end" name="working_hours_end" 
                                                   value="<?php echo esc_attr($this->settings['working_hours_end']); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="neetrino-appearance-column">
                                        <div class="neetrino-form-group">
                                            <label><?php _e('Рабочие дни', 'neetrino'); ?></label>
                                            <div class="neetrino-days-grid">
                                                <?php
                                                $days = [
                                                    1 => __('Пн', 'neetrino'),
                                                    2 => __('Вт', 'neetrino'),
                                                    3 => __('Ср', 'neetrino'),
                                                    4 => __('Чт', 'neetrino'),
                                                    5 => __('Пт', 'neetrino'),
                                                    6 => __('Сб', 'neetrino'),
                                                    0 => __('Вс', 'neetrino')
                                                ];
                                                
                                                foreach ($days as $day_num => $day_name) {
                                                    $checked = is_array($this->settings['working_days']) && in_array($day_num, $this->settings['working_days']);
                                                    echo '<label class="neetrino-day-button">';
                                                    echo '<input type="checkbox" name="working_days[]" value="' . $day_num . '" ' . checked($checked, true, false) . '>';
                                                    echo '<span class="neetrino-day-text">' . $day_name . '</span>';
                                                    echo '</label>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Вкладка: Дополнительные настройки -->
                        <div id="tab-settings" class="neetrino-tab-panel">
                            <div class="neetrino-card">
                                <h2><?php _e('Дополнительные настройки', 'neetrino'); ?></h2>
                                
                                <div class="neetrino-form-group">
                                    <label class="neetrino-checkbox">
                                        <input type="checkbox" name="mobile_visible" <?php checked($this->settings['mobile_visible']); ?>>
                                        <?php _e('Показывать на мобильных устройствах', 'neetrino'); ?>
                                    </label>
                                </div>
                                
                                <div class="neetrino-form-group">
                                    <label class="neetrino-checkbox">
                                        <input type="checkbox" name="desktop_visible" <?php checked($this->settings['desktop_visible']); ?>>
                                        <?php _e('Показывать на десктопах', 'neetrino'); ?>
                                    </label>
                                </div>
                                
                                <div class="neetrino-form-group">
                                    <label for="excluded_pages"><?php _e('Исключить страницы', 'neetrino'); ?></label>
                                    <select id="excluded_pages" name="excluded_pages[]" multiple="multiple" style="width: 100%; height: 120px;">
                                        <?php foreach ($pages as $page): ?>
                                            <option value="<?php echo $page->ID; ?>" 
                                                    <?php echo is_array($this->settings['excluded_pages']) && in_array($page->ID, $this->settings['excluded_pages']) ? 'selected' : ''; ?>>
                                                <?php echo esc_html($page->post_title); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php _e('Страницы, на которых не будут показываться кнопки', 'neetrino'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Вкладка: Статистика -->
                        <div id="tab-stats" class="neetrino-tab-panel">
                            <div class="neetrino-card">
                                <h2><?php _e('Статистика кликов', 'neetrino'); ?></h2>
                                
                                <?php if (empty($this->stats)): ?>
                                    <p><?php _e('Статистика пока отсутствует', 'neetrino'); ?></p>
                                <?php else: ?>
                                    <div class="neetrino-stats-grid">
                                        <?php foreach ($this->stats as $type => $count): ?>
                                            <div class="neetrino-stat-item">
                                                <div class="neetrino-stat-icon">
                                                    <?php echo $this->get_stat_icon($type); ?>
                                                </div>
                                                <div class="neetrino-stat-info">
                                                    <div class="neetrino-stat-number"><?php echo (int)$count; ?></div>
                                                    <div class="neetrino-stat-label"><?php echo $this->get_stat_label($type); ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="neetrino-form-actions" style="margin-top: 20px;">
                                        <button type="submit" name="reset_stats" class="button button-secondary" 
                                                onclick="return confirm('<?php _e('Вы уверены, что хотите сбросить статистику?', 'neetrino'); ?>');">
                                            <?php _e('Сбросить статистику', 'neetrino'); ?>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Кнопка сохранения -->
                        <div class="neetrino-form-actions">
                            <button type="submit" name="save_settings" class="button button-primary">
                                <?php _e('Сохранить настройки', 'neetrino'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <?php
    }
    
    /**
     * Подключение стилей и скриптов админки
     */
    private function enqueue_admin_assets() {
        wp_enqueue_style('neetrino-chat-admin', plugin_dir_url(__FILE__) . 'assets/admin.css', [], '1.2.0');
        wp_enqueue_script('neetrino-chat-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', ['jquery'], '1.2.0', true);
        
        // Передаем данные для AJAX
        wp_localize_script('neetrino-chat-admin', 'neetrino_chat_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('neetrino_chat_nonce'),
            'single_channel' => $this->get_single_channel_info(),
            'brand_colors' => [
                'phone' => '#3498db',
                'whatsapp' => '#25d366',
                'telegram' => '#0088cc',
                'viber' => '#665cac',
                'email' => '#ea4335',
                'default' => '#2ecc71'
            ]
        ]);
        
        // Подключаем Iconify для иконок в админке
        wp_enqueue_script(
            'iconify-admin',
            'https://code.iconify.design/3/3.1.1/iconify.min.js',
            [],
            '3.1.1',
            true
        );
    }
    
    /**
     * Получение иконки для статистики
     */
    private function get_stat_icon($type) {
        switch ($type) {
            case 'phone':
                return '<span class="iconify" data-icon="material-symbols:call" data-width="24" data-height="24" style="color: #2ecc71;"></span>';
            case 'whatsapp':
                return '<span class="iconify" data-icon="simple-icons:whatsapp" data-width="24" data-height="24" style="color: #25d366;"></span>';
            case 'telegram':
                return '<span class="iconify" data-icon="simple-icons:telegram" data-width="24" data-height="24" style="color: #0088cc;"></span>';
            case 'viber':
                return '<span class="iconify" data-icon="simple-icons:viber" data-width="24" data-height="24" style="color: #7360f2;"></span>';
            case 'email':
                return '<span class="iconify" data-icon="material-symbols:mail-outline" data-width="24" data-height="24" style="color: #2ecc71;"></span>';
            default:
                return '<span class="iconify" data-icon="material-symbols:chat-bubble-outline" data-width="24" data-height="24" style="color: #2ecc71;"></span>';
        }
    }
    
    /**
     * Получение подписи для статистики
     */
    private function get_stat_label($type) {
        switch ($type) {
            case 'phone':
                return __('Телефон', 'neetrino');
            case 'whatsapp':
                return 'WhatsApp';
            case 'telegram':
                return 'Telegram';
            case 'viber':
                return 'Viber';
            case 'email':
                return 'Email';
            default:
                return ucfirst($type);
        }
    }
    
    /**
     * Получение информации о единственном канале для админки
     */
    private function get_single_channel_info() {
        $active_channels = [];
        $brand_colors = [
            'phone' => '#3498db',
            'whatsapp' => '#25d366',
            'telegram' => '#0088cc',
            'viber' => '#665cac',
            'email' => '#ea4335'
        ];
        
        if (!empty($this->settings['phone'])) {
            $active_channels['phone'] = [
                'type' => 'phone',
                'contact' => $this->settings['phone'],
                'icon' => 'material-symbols:call',
                'title' => __('Телефон', 'neetrino'),
                'brand_color' => $brand_colors['phone']
            ];
        }
        
        if (!empty($this->settings['whatsapp'])) {
            $active_channels['whatsapp'] = [
                'type' => 'whatsapp',
                'contact' => $this->settings['whatsapp'],
                'icon' => 'simple-icons:whatsapp',
                'title' => 'WhatsApp',
                'brand_color' => $brand_colors['whatsapp']
            ];
        }
        
        if (!empty($this->settings['telegram'])) {
            $active_channels['telegram'] = [
                'type' => 'telegram',
                'contact' => $this->settings['telegram'],
                'icon' => 'simple-icons:telegram',
                'title' => 'Telegram',
                'brand_color' => $brand_colors['telegram']
            ];
        }
        
        if (!empty($this->settings['viber'])) {
            $active_channels['viber'] = [
                'type' => 'viber',
                'contact' => $this->settings['viber'],
                'icon' => 'simple-icons:viber',
                'title' => 'Viber',
                'brand_color' => $brand_colors['viber']
            ];
        }
        
        if (!empty($this->settings['email'])) {
            $active_channels['email'] = [
                'type' => 'email',
                'contact' => $this->settings['email'],
                'icon' => 'material-symbols:mail-outline',
                'title' => 'Email',
                'brand_color' => $brand_colors['email']
            ];
        }
        
        if (count($active_channels) === 1) {
            return reset($active_channels);
        }
        
        return false;
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
            'position_x' => 95, // 95% от левого края (правый край)
            'position_y' => 85, // 85% от верхнего края (нижний край)
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
            'working_days' => [1, 2, 3, 4, 5],
            'excluded_pages' => [],
            'mobile_visible' => true,
            'desktop_visible' => true,
            'pulse_effect' => true,
            'labels_visible' => true,
            'status_text' => 'Онлайн'
        ];
    }
}

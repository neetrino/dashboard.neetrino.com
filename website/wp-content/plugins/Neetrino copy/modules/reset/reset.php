<?php
/**
 * Module: Reset
 * Description: Мгновенный сброс сайта за несколько секунд.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Reset {
    
    private $db_handler;
    
    public function __construct() {
        // Инициализация модуля только если он активен
        if (!Neetrino::is_module_active('reset')) {
            return;
        }
        
        // Подключение вспомогательных классов
        require_once plugin_dir_path(__FILE__) . 'includes/class-database-handler.php';
        
        $this->db_handler = new Neetrino_Reset_Database_Handler();
        
        // Хуки и действия модуля
        add_action('init', [$this, 'init']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_neetrino_reset_site', [$this, 'ajax_reset_site']);
        add_action('wp_ajax_neetrino_partial_reset', [$this, 'ajax_partial_reset']);
    }
    
    public function init() {
        // Дополнительная инициализация при необходимости
    }
    
    public function enqueue_admin_scripts($hook) {
        // Подключаем скрипты только на странице модуля
        if (strpos($hook, 'neetrino') === false) {
            return;
        }
        
        wp_enqueue_style(
            'neetrino-reset-style',
            plugin_dir_url(__FILE__) . 'assets/css/reset-style.css',
            [],
            '1.0.0'
        );
        
        wp_enqueue_script(
            'neetrino-reset-script',
            plugin_dir_url(__FILE__) . 'assets/js/reset-script.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        // Передаем данные в JavaScript
        wp_localize_script('neetrino-reset-script', 'neetrinoReset', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('neetrino_reset_nonce'),
            'confirmText' => __('Вы уверены? Это действие нельзя отменить!', 'neetrino'),
            'resetText' => __('Выполняется полный сброс...', 'neetrino'),
            'successText' => __('Сброс завершен! Все плагины отключены, активирована стандартная тема.', 'neetrino'),
            'errorText' => __('Произошла ошибка при сбросе.', 'neetrino')
        ]);
    }
    
    /**
     * AJAX обработчик полного сброса сайта
     */
    public function ajax_reset_site() {
        check_ajax_referer('neetrino_reset_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав');
        }
        
        try {
            
            // Выполняем полный сброс
            $result = $this->db_handler->perform_full_reset();
            
            if ($result['success']) {
                wp_send_json_success([
                    'message' => 'Сброс выполнен успешно',
                    'stay_on_page' => true
                ]);
            } else {
                throw new Exception($result['message']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => 'Ошибка: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * AJAX обработчик частичного сброса
     */
    public function ajax_partial_reset() {
        check_ajax_referer('neetrino_reset_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав');
        }
        
        $action = sanitize_text_field($_POST['reset_action']);
        
        try {
            $result = $this->db_handler->perform_partial_reset($action);
            
            if ($result['success']) {
                wp_send_json_success([
                    'message' => $result['message']
                ]);
            } else {
                throw new Exception($result['message']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => 'Ошибка: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Статический метод для админ-страницы
     * Вызывается автоматически если модуль активен
     */
    public static function admin_page() {
        ?>
        <div class="wrap neetrino-dashboard neetrino-reset-page">
            <div class="neetrino-header">
                <div class="neetrino-header-left">
                    <h1><span class="dashicons dashicons-update-alt"></span>Reset</h1>
                    <p>Мгновенный сброс сайта за несколько секунд</p>
                </div>
            </div>
            
            <div class="neetrino-content">
                <!-- Главная секция сброса -->
                <div class="neetrino-card neetrino-reset-main-card">
                    <div class="neetrino-reset-action-container">
                        <button type="button" id="full-reset-button" class="neetrino-reset-mega-button">
                            <div class="neetrino-reset-button-content">
                                <div class="neetrino-reset-button-icon">
                                    <span class="dashicons dashicons-update-alt"></span>
                                </div>
                                <div class="neetrino-reset-button-text">
                                    <span class="neetrino-reset-button-title">СБРОСИТЬ САЙТ</span>
                                    <span class="neetrino-reset-button-subtitle">Сброс к заводским настройкам</span>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
                
                <!-- Частичные инструменты сброса -->
                <div class="neetrino-card neetrino-partial-tools-card">
                    <div class="neetrino-card-header">
                        <h2>
                            <span class="dashicons dashicons-admin-tools" style="color: #6c5ce7; margin-right: 8px;"></span>
                            Частичные инструменты сброса
                        </h2>
                        <p>Удаляйте отдельные компоненты сайта без полного сброса</p>
                    </div>
                    
                    <div class="neetrino-partial-tools-grid">
                        <div class="neetrino-partial-tools-row">
                            <div class="neetrino-partial-tool-compact">
                                <div class="neetrino-partial-tool-icon safe">
                                    <span class="dashicons dashicons-trash"></span>
                                </div>
                                <div class="neetrino-partial-tool-content">
                                    <strong>Очистить transients</strong>
                                    <small>Временные данные WordPress</small>
                                </div>
                                <button type="button" class="neetrino-btn-compact neetrino-btn-safe partial-reset" data-action="transients">
                                    Очистить
                                </button>
                            </div>
                            
                            <div class="neetrino-partial-tool-compact">
                                <div class="neetrino-partial-tool-icon warning">
                                    <span class="dashicons dashicons-admin-media"></span>
                                </div>
                                <div class="neetrino-partial-tool-content">
                                    <strong>Удалить uploads</strong>
                                    <small>Все файлы из папки uploads</small>
                                </div>
                                <button type="button" class="neetrino-btn-compact neetrino-btn-warning partial-reset" data-action="uploads">
                                    Удалить
                                </button>
                            </div>
                            
                            <div class="neetrino-partial-tool-compact">
                                <div class="neetrino-partial-tool-icon danger">
                                    <span class="dashicons dashicons-admin-plugins"></span>
                                </div>
                                <div class="neetrino-partial-tool-content">
                                    <strong>Удалить плагины</strong>
                                    <small>Все плагины кроме Neetrino + отключение</small>
                                </div>
                                <button type="button" class="neetrino-btn-compact neetrino-btn-danger partial-reset" data-action="plugins">
                                    Удалить
                                </button>
                            </div>
                        </div>
                        
                        <div class="neetrino-partial-tools-row">
                            <div class="neetrino-partial-tool-compact">
                                <div class="neetrino-partial-tool-icon safe">
                                    <span class="dashicons dashicons-admin-appearance"></span>
                                </div>
                                <div class="neetrino-partial-tool-content">
                                    <strong>Сброс настроек тем</strong>
                                    <small>Настройки всех тем</small>
                                </div>
                                <button type="button" class="neetrino-btn-compact neetrino-btn-safe partial-reset" data-action="theme-options">
                                    Сбросить
                                </button>
                            </div>
                            
                            <div class="neetrino-partial-tool-compact">
                                <div class="neetrino-partial-tool-icon warning">
                                    <span class="dashicons dashicons-admin-appearance"></span>
                                </div>
                                <div class="neetrino-partial-tool-content">
                                    <strong>Удалить темы</strong>
                                    <small>Все темы + активация стандартной</small>
                                </div>
                                <button type="button" class="neetrino-btn-compact neetrino-btn-warning partial-reset" data-action="themes">
                                    Удалить
                                </button>
                            </div>
                            
                            <div class="neetrino-partial-tool-compact">
                                <div class="neetrino-partial-tool-icon danger">
                                    <span class="dashicons dashicons-database-view"></span>
                                </div>
                                <div class="neetrino-partial-tool-content">
                                    <strong>Очистить кастомные таблицы</strong>
                                    <small>Все кастомные таблицы БД</small>
                                </div>
                                <button type="button" class="neetrino-btn-compact neetrino-btn-danger partial-reset" data-action="custom-tables">
                                    Очистить
                                </button>
                            </div>
                        </div>
                        
                        <div class="neetrino-partial-tools-row">
                            <div class="neetrino-partial-tool-compact">
                                <div class="neetrino-partial-tool-icon safe">
                                    <span class="dashicons dashicons-media-code"></span>
                                </div>
                                <div class="neetrino-partial-tool-content">
                                    <strong>Удалить .htaccess</strong>
                                    <small>Файл .htaccess</small>
                                </div>
                                <button type="button" class="neetrino-btn-compact neetrino-btn-safe partial-reset" data-action="htaccess">
                                    Удалить
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Модальное окно подтверждения -->
        <div id="neetrino-reset-modal" class="neetrino-modal" style="display: none;">
            <div class="neetrino-modal-content">
                <div class="neetrino-modal-header">
                    <h3>Подтверждение сброса</h3>
                </div>
                <div class="neetrino-modal-body">
                    <p><strong>Вы уверены что хотите выполнить полный сброс сайта?</strong></p>
                    <p><strong>Это действие нельзя отменить!</strong></p>
                </div>
                <div class="neetrino-modal-footer">
                    <button type="button" class="neetrino-button neetrino-button-secondary" id="cancel-reset">
                        НЕТ
                    </button>
                    <button type="button" class="neetrino-button neetrino-button-danger" id="confirm-reset">
                        ДА
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
}

// Инициализация модуля
new Neetrino_Reset();

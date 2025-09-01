<?php
/**
 * Module: User Switching
 * Description: Быстрое переключение между учетными записями пользователей для администраторов
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_User_Switching {
    
    private $handler;
    private $ui;
    
    public function __construct() {
        // ОБЯЗАТЕЛЬНО: Проверка активности модуля
        if (!Neetrino::is_module_active('user-switching')) {
            return;
        }
        
        // Подключаем необходимые файлы
        $this->load_dependencies();
        
        // Инициализация модуля только если он активен
        add_action('init', [$this, 'init']);
    }
    
    /**
     * Подключение зависимостей
     */
    private function load_dependencies() {
        require_once plugin_dir_path(__FILE__) . 'includes/class-handler.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-ui.php';
    }
    
    public function init() {
        // Инициализируем обработчик переключений
        $this->handler = new Neetrino_User_Switching_Handler();
        
        // Инициализируем пользовательский интерфейс
        $this->ui = new Neetrino_User_Switching_UI($this->handler);
        
        // Подключаем стили и скрипты
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }
      /**
     * Подключение стилей для админки
     */
    public function enqueue_admin_assets() {
        wp_enqueue_style(
            'neetrino-user-switching',
            plugin_dir_url(__FILE__) . 'assets/user-switching.css',
            [],
            '1.0.1'
        );
    }
    
    /**
     * Подключение стилей для фронтенда
     */
    public function enqueue_frontend_assets() {
        // Подключаем стили только если пользователь переключен
        if ($this->handler && $this->handler->is_switched()) {
            wp_enqueue_style(
                'neetrino-user-switching',
                plugin_dir_url(__FILE__) . 'assets/user-switching.css',
                [],
                '1.0.1'
            );
        }
    }
    
    /**
     * Статический метод для админ-страницы
     * Вызывается автоматически если модуль активен
     */
    public static function admin_page() {
        ?>
        <div class="wrap neetrino-dashboard">
            <div class="neetrino-header">
                <div class="neetrino-header-left">
                    <h1><?php _e('User Switching Settings', 'neetrino'); ?></h1>
                </div>
            </div>
            <div class="neetrino-content">
                <div class="neetrino-card">
                    <h2><?php _e('О модуле User Switching', 'neetrino'); ?></h2>
                    <p><?php _e('Данный модуль позволяет администраторам быстро переключаться между учетными записями пользователей без необходимости знать их пароли.', 'neetrino'); ?></p>
                    
                    <h3><?php _e('Основные функции:', 'neetrino'); ?></h3>
                    <ul>
                        <li><strong><?php _e('Switch To', 'neetrino'); ?>:</strong> <?php _e('Мгновенное переключение на любого пользователя со страницы "Пользователи"', 'neetrino'); ?></li>
                        <li><strong><?php _e('Switch Back', 'neetrino'); ?>:</strong> <?php _e('Возврат к исходной учетной записи через админ-бар и фиксированную кнопку', 'neetrino'); ?></li>
                        <li><strong><?php _e('Безопасность', 'neetrino'); ?>:</strong> <?php _e('Только администраторы могут использовать переключение', 'neetrino'); ?></li>
                    </ul>
                    
                    <h3><?php _e('Как использовать:', 'neetrino'); ?></h3>
                    <ol>
                        <li><?php _e('Перейдите в раздел "Пользователи" в админке WordPress', 'neetrino'); ?></li>
                        <li><?php _e('Найдите пользователя, на которого хотите переключиться', 'neetrino'); ?></li>
                        <li><?php _e('Нажмите ссылку "Switch To" рядом с именем пользователя', 'neetrino'); ?></li>
                        <li><?php _e('Для возврата используйте кнопку "Switch Back" в верхней панели или стильную кнопку в нижнем углу экрана', 'neetrino'); ?></li>
                    </ol>
                    
                    <div class="neetrino-notice">
                        <p><strong><?php _e('Внимание:', 'neetrino'); ?></strong> <?php _e('Функция переключения пользователей доступна только администраторам сайта. Пароли пользователей остаются защищенными и недоступными.', 'neetrino'); ?></p>
                    </div>
                </div>
                
                <div class="neetrino-card">
                    <h2><?php _e('Возможности модуля', 'neetrino'); ?></h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div style="padding: 15px; background: #f9f9f9; border-radius: 8px; border-left: 4px solid #f1c40f;">
                            <h4><?php _e('🔄 Быстрое переключение', 'neetrino'); ?></h4>
                            <p><?php _e('Мгновенное переключение между пользователями без ввода паролей', 'neetrino'); ?></p>
                        </div>
                        <div style="padding: 15px; background: #f9f9f9; border-radius: 8px; border-left: 4px solid #f1c40f;">
                            <h4><?php _e('🎯 Стильный интерфейс', 'neetrino'); ?></h4>
                            <p><?php _e('Красивая фиксированная кнопка возврата в нижнем углу экрана', 'neetrino'); ?></p>
                        </div>
                        <div style="padding: 15px; background: #f9f9f9; border-radius: 8px; border-left: 4px solid #f1c40f;">
                            <h4><?php _e('🔒 Безопасность', 'neetrino'); ?></h4>
                            <p><?php _e('Полная защита с проверкой прав и nonce-токенами', 'neetrino'); ?></p>
                        </div>
                        <div style="padding: 15px; background: #f9f9f9; border-radius: 8px; border-left: 4px solid #f1c40f;">
                            <h4><?php _e('📱 Адаптивность', 'neetrino'); ?></h4>
                            <p><?php _e('Работает на всех устройствах - компьютер, планшет, телефон', 'neetrino'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="neetrino-card">
                    <h2><?php _e('Статус модуля', 'neetrino'); ?></h2>
                    <p class="neetrino-status-active">
                        ✅ <?php _e('Модуль активен и готов к использованию', 'neetrino'); ?>
                    </p>
                    <p><?php _e('Переключение пользователей работает для всех ролей, включая администраторов, редакторов, авторов и подписчиков.', 'neetrino'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
}

// Инициализация модуля
new Neetrino_User_Switching();

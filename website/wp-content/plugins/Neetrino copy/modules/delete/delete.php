<?php
/**
 * Module: Delete
 * Description: Массовое удаление страниц WordPress, записей и данных WooCommerce (работает с WooCommerce и без него)
 * Version: 2.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Подключаем дополнительные классы модуля
require_once __DIR__ . '/includes/class-stats.php';
require_once __DIR__ . '/includes/class-ajax-handlers.php';
require_once __DIR__ . '/includes/class-admin.php';

class Neetrino_Delete {
    
    private $ajax_handlers;
    
    public function __construct() {
        // Инициализация модуля только если он активен
        if (!Neetrino::is_module_active('delete')) {
            return;
        }
        
        // Хуки и действия модуля
        add_action('init', [$this, 'init']);
        
        // Инициализируем обработчики AJAX
        $this->ajax_handlers = new Neetrino_Delete_Ajax_Handlers();
    }
    
    public function init() {
        // Модуль теперь работает как с WooCommerce, так и без него
        // Дополнительная инициализация при необходимости
    }

    /**
     * Статический метод для админ-страницы
     * Вызывается автоматически если модуль активен
     */
    public static function admin_page() {
        // Делегируем рендеринг админ-странички в отдельный класс
        Neetrino_Delete_Admin::render_admin_page();
    }
}

// Инициализация модуля
new Neetrino_Delete();

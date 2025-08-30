<?php
/**
 * Neetrino Modules Configuration
 * 
 * @package Neetrino
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Module_Config {
    
    /**
     * Get module configurations
     */
    public static function get_modules_config() {
        return [
            'bitrix24' => [
                'icon' => 'fa-solid fa-network-wired',
                'icon_type' => 'font-awesome',
                'color' => '#ff6600',
                'color_light' => '#ff8533',
                'description' => __('Synchronize your WordPress site with Bitrix24 CRM.', 'neetrino'),
                'css_class' => 'neetrino-module-bitrix24'
            ],
            'telegram' => [
                'icon' => 'fa-brands fa-telegram',
                'icon_type' => 'font-awesome',
                'color' => '#0088cc',
                'color_light' => '#2196f3',
                'description' => __('Send notifications via Telegram bot.', 'neetrino'),
                'css_class' => 'neetrino-module-telegram'
            ],
            'maintenance-mode' => [
                'icon' => 'fa-solid fa-tools',
                'icon_type' => 'font-awesome',
                'color' => '#46b450',
                'color_light' => '#5cbf60',
                'description' => __('Enable maintenance mode for your website.', 'neetrino'),
                'css_class' => 'neetrino-module-maintenance-mode'
            ],
            'login-page' => [
                'icon' => 'fa-solid fa-lock',
                'icon_type' => 'font-awesome',
                'color' => '#3498db',
                'color_light' => '#5dade2',
                'description' => __('Customize your WordPress login page.', 'neetrino'),
                'css_class' => 'neetrino-module-login-page'
            ],
            'auto-translate' => [
                'icon' => 'fa-solid fa-language',
                'icon_type' => 'font-awesome',
                'color' => '#9b59b6',
                'color_light' => '#bb6bd9',
                'description' => __('Автоматическое определение языка и перенаправление.', 'neetrino'),
                'css_class' => 'neetrino-module-auto-translate'
            ],
            'redirect-301' => [
                'icon' => 'fa-solid fa-arrows-turn-right',
                'icon_type' => 'font-awesome',
                'color' => '#2563eb',
                'color_light' => '#3b82f6',
                'description' => __('Redirect users to different sites based on their country.', 'neetrino'),
                'css_class' => 'neetrino-module-redirect-301'
            ],
            'menu-hierarchy' => [
                'icon' => 'fa-solid fa-bars',
                'icon_type' => 'font-awesome',
                'color' => '#e74c3c',
                'color_light' => '#f39c12',
                'description' => __('Настройка иерархии меню WooCommerce.', 'neetrino'),
                'css_class' => 'neetrino-module-menu-hierarchy'
            ],
            'delete' => [
                'icon' => 'fa-solid fa-trash',
                'icon_type' => 'font-awesome',
                'color' => '#dc3545',
                'color_light' => '#e85663',
                'description' => __('Массовое удаление страниц WordPress и данных WooCommerce.', 'neetrino'),
                'css_class' => 'neetrino-module-delete'
            ],
            'user-switching' => [
                'icon' => 'fa-solid fa-users',
                'icon_type' => 'font-awesome',
                'color' => '#f1c40f',
                'color_light' => '#f39c12',
                'description' => __('Быстрое переключение между пользователями.', 'neetrino'),
                'css_class' => 'neetrino-module-user-switching'
            ],
            'wordpress-design' => [
                'icon' => 'fa-solid fa-palette',
                'icon_type' => 'font-awesome',
                'color' => '#6c5ce7',
                'color_light' => '#a29bfe',
                'description' => __('Современный минималистичный дизайн админ-панели.', 'neetrino'),
                'css_class' => 'neetrino-module-wordpress-design'
            ],
            'reset' => [
                'icon' => 'fa-solid fa-rotate-left',
                'icon_type' => 'font-awesome',
                'color' => '#dc2626',
                'color_light' => '#ef4444',
                'description' => __('Мгновенный сброс сайта за несколько секунд.', 'neetrino'),
                'css_class' => 'neetrino-module-reset'
            ],
            'remote-control' => [
                'icon' => 'fa-solid fa-satellite-dish',
                'icon_type' => 'font-awesome',
                'color' => '#8b4513',
                'color_light' => '#a0522d',
                'description' => __('Универсальное удаленное управление через API.', 'neetrino'),
                'css_class' => 'neetrino-module-remote-control'
            ],
            'delivery' => [
                'icon' => 'fa-solid fa-truck',
                'icon_type' => 'font-awesome',
                'color' => '#1abc9c',
                'color_light' => '#48c9b0',
                'description' => __('Автозаполнение адреса и расчет доставки через Google API.', 'neetrino'),
                'css_class' => 'neetrino-module-delivery'
            ],
            'chat' => [
                'icon' => 'fa-solid fa-comments',
                'icon_type' => 'font-awesome',
                'color' => '#2ecc71',
                'color_light' => '#58d68d',
                'description' => __('Плавающие кнопки связи для коммуникации с посетителями.', 'neetrino'),
                'css_class' => 'neetrino-module-chat'
            ],
            'app-manager' => [
                'icon' => 'fa-solid fa-mobile-screen-button',
                'icon_type' => 'font-awesome',
                'color' => '#17a2b8',
                'color_light' => '#20c0db',
                'description' => __('Управление мобильными приложениями и автогенерация Privacy Policy.', 'neetrino'),
                'css_class' => 'neetrino-module-app-manager'
            ],
            'checkout-fields' => [
                'icon' => 'fa-solid fa-list-check',
                'icon_type' => 'font-awesome',
                'color' => '#9c88ff',
                'color_light' => '#b4a5ff',
                'description' => __('Управление полями на странице оформления заказа WooCommerce.', 'neetrino'),
                'css_class' => 'neetrino-module-checkout-fields'
            ]
        ];
    }
    
    /**
     * Get module configuration by slug
     */
    public static function get_module_config($slug) {
        $configs = self::get_modules_config();
        
        if (isset($configs[$slug])) {
            return $configs[$slug];
        }
        
        // Default configuration
        return [
            'icon' => 'fa-solid fa-puzzle-piece',
            'icon_type' => 'font-awesome',
            'color' => '#2271b1',
            'color_light' => '#3498db',
            'description' => __('Neetrino module for enhanced functionality.', 'neetrino'),
            'css_class' => 'neetrino-module-default'
        ];
    }
    
    /**
     * Get icon HTML for module
     */
    public static function get_module_icon_html($slug) {
        $config = self::get_module_config($slug);
        $icon_type = isset($config['icon_type']) ? $config['icon_type'] : 'dashicons';
        
        if ($icon_type === 'font-awesome') {
            return sprintf('<i class="%s"></i>', esc_attr($config['icon']));
        } else {
            // Fallback to dashicons
            return sprintf('<span class="dashicons %s"></span>', esc_attr($config['icon']));
        }
    }
    
    /**
     * Generate CSS variables for module
     */
    public static function get_module_css_vars($slug) {
        $config = self::get_module_config($slug);
        
        return sprintf(
            '--module-color: %s; --module-color-light: %s;',
            $config['color'],
            $config['color_light']
        );
    }
}

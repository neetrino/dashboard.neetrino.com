<?php
/**
 * Neetrino Assets Manager
 * 
 * @package Neetrino
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Assets {
    
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Проверяем, что мы на странице Neetrino
        if (!$this->is_neetrino_page($hook)) {
            return;
        }
        
        $this->enqueue_styles();
        $this->enqueue_scripts();
    }
    
    /**
     * Check if we're on a Neetrino admin page
     */
    private function is_neetrino_page($hook) {
        return (strpos($hook, 'neetrino') !== false || strpos($hook, 'toplevel_page_neetrino') !== false);
    }
    
    /**
     * Enqueue admin styles
     */
    private function enqueue_styles() {
        // Подключаем Font Awesome CDN для иконок
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );
        
        $css_file = plugin_dir_url(NEETRINO_PLUGIN_FILE) . 'includes/assets/css/admin.css';
        $css_version = filemtime(plugin_dir_path(NEETRINO_PLUGIN_FILE) . 'includes/assets/css/admin.css');
        
        wp_enqueue_style(
            'neetrino-admin-styles',
            $css_file,
            ['font-awesome'],
            $css_version
        );
        
        // Добавляем динамические CSS переменные для модулей
        $this->add_module_css_variables();
    }
    
    /**
     * Enqueue admin scripts
     */
    private function enqueue_scripts() {
        // Подключаем jQuery
        wp_enqueue_script('jquery');
        
        $js_file = plugin_dir_url(NEETRINO_PLUGIN_FILE) . 'includes/assets/js/admin.js';
        $js_version = filemtime(plugin_dir_path(NEETRINO_PLUGIN_FILE) . 'includes/assets/js/admin.js');
        
        wp_enqueue_script(
            'neetrino-admin-script',
            $js_file,
            ['jquery'],
            $js_version,
            true
        );
        
        // Передаем AJAX данные в JavaScript
        wp_localize_script('neetrino-admin-script', 'neetrino_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('neetrino_module_nonce')
        ]);
    }
    
    /**
     * Add CSS variables for modules
     */
    private function add_module_css_variables() {
        $modules_config = Neetrino_Module_Config::get_modules_config();
        $css_vars = '';
        
        foreach ($modules_config as $slug => $config) {
            $css_vars .= sprintf(
                '.neetrino-module-%s { --module-color: %s; --module-color-light: %s; }',
                $slug,
                $config['color'],
                $config['color_light']
            );
        }
        
        if (!empty($css_vars)) {
            wp_add_inline_style('neetrino-admin-styles', $css_vars);
        }
    }
}

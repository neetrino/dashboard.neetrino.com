<?php
/**
 * Main Login Page Class
 * 
 * Core functionality for the login page customization
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Login_Page {
    private $options;
    private $default_backgrounds = [];
    private $default_logo = '';

    public function __construct() {
        // Scan backgrounds directory and get all images
        $this->default_backgrounds = [];
        $backgrounds_dir = plugin_dir_path(dirname(__FILE__)) . 'backgrounds/';
        $backgrounds_url = plugin_dir_url(dirname(__FILE__)) . 'backgrounds/';
        
        if (is_dir($backgrounds_dir)) {
            $files = scandir($backgrounds_dir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && preg_match('/\.(webp|jpg|jpeg|png|gif)$/i', $file)) {
                    $this->default_backgrounds[] = $backgrounds_url . $file;
                }
            }
        }
        
        // If no backgrounds found, use defaults
        if (empty($this->default_backgrounds)) {
            $this->default_backgrounds = [
                $backgrounds_url . '1..webp',
                $backgrounds_url . '2..webp',
                $backgrounds_url . '3..webp'
            ];
        }
        
        // Initialize default logo
        $this->default_logo = plugin_dir_url(dirname(__FILE__)) . 'logos/Neetrino-Logo.png';
        
        // Get options
        $this->options = get_option('neetrino_login_page_options', [
            'background' => $this->default_backgrounds[0],
            'text_color' => '#ffffff',
            'logo' => $this->default_logo,
            'form_position' => 'center', // Default position: center (other options: 'left', 'right')
            'glass_effect_intensity' => 50 // Default glass effect intensity (0-100)
        ]);
        
        // Admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Register settings
        add_action('admin_init', [$this, 'register_settings']);
        
        // Apply login page customizations
        add_action('login_enqueue_scripts', [$this, 'custom_login_style']);
        add_filter('login_headerurl', [$this, 'custom_login_logo_url']);
        add_filter('login_headertext', [$this, 'custom_login_logo_title']);
    }
    
    /**
     * Add admin menu item
     * 
     * Этот метод больше не используется, так как меню добавляется автоматически
     * в классе Neetrino_Admin
     */
    public function add_admin_menu() {
        // Метод оставлен для обратной совместимости
    }
    
    /**
     * Static method for admin page rendering
     * Статический метод для отображения страницы администратора
     * Используется в автоматическом добавлении меню
     */
    public static function admin_page() {
        $instance = new self();
        $instance->render_admin_page();
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'neetrino_login_page_group',
            'neetrino_login_page_options',
            [$this, 'sanitize_options']
        );
    }
    
    /**
     * Sanitize options
     */
    public function sanitize_options($input) {
        $new_input = [];
        
        if (isset($input['background'])) {
            $new_input['background'] = sanitize_text_field($input['background']);
        }
        
        if (isset($input['text_color'])) {
            $new_input['text_color'] = sanitize_hex_color($input['text_color']);
        }
        
        if (isset($input['logo'])) {
            $new_input['logo'] = sanitize_text_field($input['logo']);
        }
        
        if (isset($input['form_position'])) {
            // Validate position (only allow 'left', 'right', or 'center')
            $position = sanitize_text_field($input['form_position']);
            if (in_array($position, ['left', 'right', 'center'])) {
                $new_input['form_position'] = $position;
            } else {
                $new_input['form_position'] = 'center'; // Default to center if invalid
            }
        }
        
        if (isset($input['glass_effect_intensity'])) {
            // Validate glass effect intensity (only allow 0-100)
            $intensity = intval($input['glass_effect_intensity']);
            if ($intensity >= 0 && $intensity <= 100) {
                $new_input['glass_effect_intensity'] = $intensity;
            } else {
                $new_input['glass_effect_intensity'] = 50; // Default to 50 if invalid
            }
        }
        
        return $new_input;
    }
    
    /**
     * Render admin page content
     */
    public function render_admin_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/admin-interface.php';
        render_login_page_admin_interface($this->options, $this->default_backgrounds, $this->default_logo);
    }
    
    /**
     * Apply custom login page styles
     */
    public function custom_login_style() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/customizer.php';
        apply_login_page_styles($this->options, $this->default_logo);
    }
    
    /**
     * Custom login logo URL
     */
    public function custom_login_logo_url() {
        return home_url();
    }
    
    /**
     * Custom login logo title
     */
    public function custom_login_logo_title() {
        return get_bloginfo('name');
    }
}

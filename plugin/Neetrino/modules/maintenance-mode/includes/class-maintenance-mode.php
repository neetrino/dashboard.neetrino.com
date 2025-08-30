<?php
/**
 * Main Maintenance Mode Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Maintenance_Mode {
    private $options;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->options = get_option('neetrino_maintenance_mode', [
            'mode' => 'open', // open, maintenance, closed
            'mobile_mode' => 'inherit', // inherit, open, maintenance, closed
            'password_access' => false, // enable/disable password access
            'access_password' => '', // password for maintenance access
        ]);
        
        // Migrate old 'disabled' mode to 'open' mode
        if (isset($this->options['mode']) && $this->options['mode'] === 'disabled') {
            $this->options['mode'] = 'open';
            update_option('neetrino_maintenance_mode', $this->options);
        }
        
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('init', [$this, 'init']);
        
        // Hook to clear cache when settings are updated
        add_action('update_option_neetrino_maintenance_mode', [$this, 'clear_cache_on_mode_change'], 10, 2);
        
        // Add AJAX endpoint for maintenance status check
        add_action('wp_ajax_nopriv_check_maintenance_status', [$this, 'ajax_check_maintenance_status']);
        add_action('wp_ajax_check_maintenance_status', [$this, 'ajax_check_maintenance_status']);
        
        // Add AJAX endpoint for generating new password
        add_action('wp_ajax_generate_maintenance_password', [$this, 'ajax_generate_password']);
    }
    
    /**
     * Check if user is on mobile device
     */
    private function is_mobile_device() {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $mobile_agents = array(
            'Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 
            'webOS', 'Windows Phone', 'Opera Mini', 'Opera Mobi'
        );
        
        foreach ($mobile_agents as $agent) {
            if (strpos($user_agent, $agent) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get effective mode based on device type
     */
    private function get_effective_mode() {
        $mobile_mode = isset($this->options['mobile_mode']) ? $this->options['mobile_mode'] : 'inherit';
        
        // If mobile mode is set to inherit or user is on desktop, use desktop mode
        if ($mobile_mode === 'inherit' || !$this->is_mobile_device()) {
            return $this->options['mode'];
        }
        
        // User is on mobile and mobile mode is specifically set
        return $mobile_mode;
    }
    
    /**
     * Generate a simple password for maintenance access
     */
    public function generate_access_password() {
        $year = date('Y');
        $random = strtolower(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));
        return "maint-{$year}-{$random}";
    }

    /**
     * Auto-generate password when password access is enabled and no password exists
     */
    private function auto_generate_password_if_needed($options) {
        // Check if password access is enabled and password is empty
        if (isset($options['password_access']) && $options['password_access'] && 
            (!isset($options['access_password']) || empty($options['access_password']))) {
            $options['access_password'] = $this->generate_access_password();
        }
        return $options;
    }
    
    /**
     * Check if user has valid password access
     */
    private function has_valid_password_access() {
        // Check if password access is enabled and we're in maintenance mode
        if (!isset($this->options['password_access']) || !$this->options['password_access']) {
            return false;
        }
        
        if ($this->get_effective_mode() !== 'maintenance') {
            return false;
        }
        
        // Check for valid cookie
        $cookie_name = 'neetrino_maintenance_access';
        if (isset($_COOKIE[$cookie_name])) {
            $stored_password = isset($this->options['access_password']) ? $this->options['access_password'] : '';
            return $_COOKIE[$cookie_name] === $stored_password;
        }
        
        return false;
    }
    
    /**
     * Init the module
     */
    public function init() {
        $effective_mode = $this->get_effective_mode();
        
        if ($effective_mode !== 'open') {
            // Skip admin pages
            if (preg_match('/wp-login|wp-admin/i', $_SERVER['REQUEST_URI'])) {
                return;
            }
            
            // Skip logged-in admins
            if (is_user_logged_in() && current_user_can('manage_options')) {
                return;
            }
            
            // Skip if user has valid password access for maintenance mode
            if ($this->has_valid_password_access()) {
                return;
            }
            
            // Show appropriate page based on mode
            add_action('template_redirect', [$this, 'show_page']);
        }
        
        // Handle password form submission
        $this->handle_password_form();
    }
    
    /**
     * Handle password form submission
     */
    private function handle_password_form() {
        if (isset($_POST['neetrino_maintenance_password']) && isset($_POST['neetrino_password_nonce'])) {
            if (wp_verify_nonce($_POST['neetrino_password_nonce'], 'neetrino_maintenance_password_check')) {
                $submitted_password = sanitize_text_field($_POST['neetrino_maintenance_password']);
                $correct_password = isset($this->options['access_password']) ? $this->options['access_password'] : '';
                
                if ($submitted_password === $correct_password && !empty($correct_password)) {
                    // Set cookie for 24 hours
                    setcookie('neetrino_maintenance_access', $correct_password, time() + (24 * 60 * 60), '/');
                    
                    // Redirect to remove POST data
                    wp_redirect($_SERVER['REQUEST_URI']);
                    exit;
                }
            }
        }
    }
    
    /**
     * Show appropriate page based on mode
     */
    public function show_page() {
        // Set aggressive no-cache headers to prevent caching
        $this->set_no_cache_headers();
        
        // Get effective mode based on device type
        $effective_mode = $this->get_effective_mode();
        
        // Set proper status code based on mode
        if ($effective_mode === 'maintenance') {
            status_header(503); // Service unavailable
            header('Retry-After: 60'); // Reduced from 3600 to 60 seconds
        } else {
            status_header(403); // Forbidden
        }
        
        // Include template based on effective mode
        $template = plugin_dir_path(dirname(__FILE__)) . 'templates/' . $effective_mode . '.php';
        include($template);
        exit;
    }
    
    /**
     * Set aggressive no-cache headers
     */
    private function set_no_cache_headers() {
        // Prevent all forms of caching
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
        
        // Add unique timestamp to prevent browser caching
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('ETag: "' . md5(time()) . '"');
        
        // Add additional headers to prevent proxy caching
        header('Vary: *');
        header('X-Accel-Expires: 0'); // Nginx
        header('X-Cache-Control: no-cache'); // Varnish
    }
    
    /**
     * Clear cache when maintenance mode changes
     */
    public function clear_cache_on_mode_change($old_value, $new_value) {
        // Force clear various caches when mode changes
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Clear WordPress object cache
        if (function_exists('wp_cache_delete')) {
            wp_cache_delete('neetrino_maintenance_mode', 'options');
        }
        
        // Clear opcache if available
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        // Create a timestamp file to help with cache busting
        $this->create_cache_bust_file();
        
        // Send headers to expire any cached pages immediately
        if (!headers_sent()) {
            header('X-Cache-Clear: maintenance-mode-change');
        }
        
        // Log the mode change for debugging
        error_log('Neetrino Maintenance Mode changed from ' . json_encode($old_value) . ' to ' . json_encode($new_value));
    }
    
    /**
     * Create a cache bust file with current timestamp
     */
    private function create_cache_bust_file() {
        $cache_bust_file = WP_CONTENT_DIR . '/maintenance-mode-timestamp.txt';
        $timestamp = time();
        file_put_contents($cache_bust_file, $timestamp);
        
        // Обновление .htaccess отключено для избежания ошибок
        // $this->update_htaccess_cache_rules();
    }
    
    /**
     * Update .htaccess with cache prevention rules
     */
    private function update_htaccess_cache_rules() {
        if (!function_exists('get_home_path')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        // Подключаем файл с функцией insert_with_markers
        if (!function_exists('insert_with_markers')) {
            $misc_file = ABSPATH . 'wp-admin/includes/misc.php';
            if (file_exists($misc_file)) {
                require_once($misc_file);
            }
        }
        
        // Если функция все еще недоступна, пропускаем обновление .htaccess
        if (!function_exists('insert_with_markers')) {
            error_log('Neetrino Maintenance Mode: insert_with_markers function not available, skipping .htaccess update');
            return;
        }
        
        $htaccess_file = get_home_path() . '.htaccess';
        
        // Only proceed if .htaccess is writable
        if (!is_writable($htaccess_file) && !is_writable(dirname($htaccess_file))) {
            return;
        }
        
        $marker = 'NEETRINO MAINTENANCE MODE';
        $rules = [
            '# Prevent caching of maintenance mode pages',
            '<IfModule mod_headers.c>',
            '    <FilesMatch "\.(php)$">',
            '        Header set Cache-Control "no-store, no-cache, must-revalidate, max-age=0"',
            '        Header set Pragma "no-cache"',
            '        Header set Expires "Wed, 11 Jan 1984 05:00:00 GMT"',
            '    </FilesMatch>',
            '</IfModule>',
            '',
            '# Force revalidation',
            '<IfModule mod_expires.c>',
            '    ExpiresActive On',
            '    ExpiresByType text/html "access plus 0 seconds"',
            '</IfModule>'
        ];
        
        // Only add rules if maintenance mode is active
        $current_mode = get_option('neetrino_maintenance_mode', ['mode' => 'open']);
        
        try {
            if ($current_mode['mode'] !== 'open') {
                insert_with_markers($htaccess_file, $marker, $rules);
            } else {
                // Remove rules when maintenance mode is disabled
                insert_with_markers($htaccess_file, $marker, []);
            }
        } catch (Exception $e) {
            error_log('Neetrino Maintenance Mode: Error updating .htaccess: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX endpoint to check maintenance status
     */
    public function ajax_check_maintenance_status() {
        // Set no-cache headers
        $this->set_no_cache_headers();
        
        // Get current mode
        $current_options = get_option('neetrino_maintenance_mode', ['mode' => 'open']);
        
        wp_send_json([
            'mode' => $current_options['mode'],
            'timestamp' => time(),
            'status' => $current_options['mode'] === 'open' ? 'available' : 'unavailable'
        ]);
    }
    
    /**
     * AJAX endpoint to generate new maintenance password
     */
    public function ajax_generate_password() {
        // Check capability
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_generate_password')) {
            wp_die('Invalid nonce');
        }
        
        // Generate new password
        $new_password = $this->generate_access_password();
        
        // Update options
        $options = get_option('neetrino_maintenance_mode', []);
        $options['access_password'] = $new_password;
        
        // Ensure password access is enabled when generating password
        $options['password_access'] = true;
        
        update_option('neetrino_maintenance_mode', $options);
        
        // Return new password
        wp_send_json_success(['password' => $new_password]);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // We don't need to register our own menu item
        // The menu is handled by the main Neetrino plugin
    }
    
    /**
     * Static admin page renderer
     */
    public static function admin_page() {
        // Check capability
        if (!current_user_can('manage_options')) {
            return;
        }

        // Include admin interface functions
        require_once plugin_dir_path(__FILE__) . 'admin-interface.php';

        $options = get_option('neetrino_maintenance_mode', [
            'mode' => 'open', // open, maintenance, closed
            'mobile_mode' => 'inherit' // inherit, open, maintenance, closed
        ]);

        // Create instance for auto-generation functionality
        $instance = new self();
        
        // Auto-generate password if needed (when password access is enabled but no password exists)
        $options = $instance->auto_generate_password_if_needed($options);
        
        // Save the updated options if password was auto-generated
        $updated_options = get_option('neetrino_maintenance_mode', []);
        if ($updated_options !== $options) {
            update_option('neetrino_maintenance_mode', $options);
        }

        // Render the admin interface
        render_maintenance_mode_admin_interface($options);
    }
}

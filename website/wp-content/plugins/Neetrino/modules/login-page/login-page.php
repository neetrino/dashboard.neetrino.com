<?php
/**
 * Neetrino Login Page Module
 * 
 * Customizes the WordPress login page with custom backgrounds and logo
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include required files
require_once plugin_dir_path(__FILE__) . 'includes/class-login-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-interface.php';
require_once plugin_dir_path(__FILE__) . 'includes/customizer.php';

// Initialize the module
new Neetrino_Login_Page();

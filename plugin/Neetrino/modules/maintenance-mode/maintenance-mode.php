<?php
/**
 * Neetrino Maintenance Mode Module
 * 
 * Simple maintenance and coming soon pages for WordPress sites
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include required files
require_once plugin_dir_path(__FILE__) . 'includes/class-maintenance-mode.php';

/**
 * Enqueue admin scripts and styles
 */
function neetrino_maintenance_admin_scripts($hook) {
    // Only load on our module page
    if (strpos($hook, 'neetrino') !== false) {
        wp_enqueue_style('dashicons');
    }
}
add_action('admin_enqueue_scripts', 'neetrino_maintenance_admin_scripts');

// Initialize the module
new Neetrino_Maintenance_Mode();

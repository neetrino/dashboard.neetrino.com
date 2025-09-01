<?php
/**
 * Admin Interface Functions for Maintenance Mode
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the admin interface for maintenance mode configuration
 * 
 * @param array $options Current options
 */
function render_maintenance_mode_admin_interface($options) {
    // Default mode if not set
    $current_mode = isset($options['mode']) ? $options['mode'] : 'open';
    $current_mobile_mode = isset($options['mobile_mode']) ? $options['mobile_mode'] : 'inherit';
    $current_password_access = isset($options['password_access']) ? $options['password_access'] : false;
    $current_access_password = isset($options['access_password']) ? $options['access_password'] : '';
    
    // Function to check if maintenance mode is enabled on any device
    function is_maintenance_enabled($mode, $mobile_mode) {
        // Check if desktop mode is maintenance
        if ($mode === 'maintenance') {
            return true;
        }
        
        // Check if mobile mode is specifically set to maintenance
        if ($mobile_mode === 'maintenance') {
            return true;
        }
        
        return false;
    }
    
    // Process form submission
    if (isset($_POST['neetrino_save'])) {
        check_admin_referer('neetrino_maintenance_save');
        $options['mode'] = isset($_POST['site_mode']) ? sanitize_text_field($_POST['site_mode']) : 'open';
        $options['mobile_mode'] = isset($_POST['mobile_mode']) ? sanitize_text_field($_POST['mobile_mode']) : 'inherit';
        
        // Handle password access
        $password_access_enabled = isset($_POST['password_access']) ? true : false;
        $options['password_access'] = $password_access_enabled;
        $options['access_password'] = isset($_POST['access_password']) ? sanitize_text_field($_POST['access_password']) : '';
        
        // Auto-generate password if password access is enabled but no password exists
        if ($password_access_enabled && empty($options['access_password'])) {
            // Create temporary instance to use the password generation method
            $temp_instance = new Neetrino_Maintenance_Mode();
            $options['access_password'] = $temp_instance->generate_access_password();
        }
        
        update_option('neetrino_maintenance_mode', $options);
        
        // Update current modes to show immediately correct status after form submission
        $current_mode = $options['mode'];
        $current_mobile_mode = $options['mobile_mode'];
        $current_password_access = isset($options['password_access']) ? $options['password_access'] : false;
        $current_access_password = isset($options['access_password']) ? $options['access_password'] : '';
        
        // Use standard WordPress admin notice
        add_settings_error(
            'neetrino_maintenance_settings',
            'neetrino_maintenance_updated',
            'Настройки сохранены.',
            'success'
        );
    }
    
    // Check if maintenance mode is enabled for password section visibility
    $maintenance_enabled = is_maintenance_enabled($current_mode, $current_mobile_mode);
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline" style="display: none;">Maintenance Mode</h1>
        <?php 
        // Show standard admin notices first
        do_action('admin_notices');
        // Then show specific settings errors
        settings_errors('neetrino_maintenance_settings'); 
        ?>
    </div>
    <style>
        .wrap {
            margin: 0;
            padding: 0;
            max-width: 100%;
        }
          #wpcontent { padding-left: 0; }
        #wpbody-content { padding-right: 0; }
          .neetrino-content-area { margin: 0 20px; }
          .neetrino-card {
            background: transparent;
            border-radius: 8px;
            box-shadow: none;
            padding: 16px;
            border-top: none;
        }
        
        .neetrino-card h2 {
            margin-top: 0;
            padding-bottom: 15px;
            color: #1d2327;
            font-size: 18px;
            text-align: center;
        }        .mode-options {
            display: flex;
            gap: 40px;
            margin: 15px 0;
            justify-content: space-between;
            width: 100%;
        }.mobile-mode-options {
            display: flex;
            gap: 12px;
            margin: 40px 0 0 0;
            justify-content: space-between;
            width: 100%;
        }
        
        .mode-option {
            flex: 1;
            position: relative;
        }
        
        .mobile-mode-option {
            flex: 1;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .mode-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .mobile-mode-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
          .mode-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 15px;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-align: center;
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border: 2px solid #e3e6ea;
            color: #646970;
            min-height: 160px;
            justify-content: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .mode-option label::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.6s ease;
        }        .mobile-mode-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
            border-radius: 24px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-align: center;
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border: 3px solid #e3e6ea;
            color: #646970;
            min-height: 360px;
            width: 180px;
            justify-content: flex-start;
            position: relative;
            margin: 0 auto;
            aspect-ratio: 9/16;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }
        
        .mobile-mode-option label::before {
            content: "";
            position: absolute;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 6px;
            background: linear-gradient(90deg, #666, #999, #666);
            border-radius: 4px;
            opacity: 0.6;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.2);
        }
        
        .mobile-mode-option label::after {
            content: "";
            position: absolute;
            bottom: 12px;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 30px;
            border: 4px solid #666;
            border-radius: 50%;
            opacity: 0.6;
            background: linear-gradient(145deg, #f8f9fa, #e9ecef);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }          /* Enhanced hover styles with animations */
        .mode-option label:hover {
            border-color: #3498db;
            background: linear-gradient(145deg, #f8f9fa 0%, #ffffff 100%);
            transform: translateY(-4px) scale(1.02);
            box-shadow: none;
        }
        
        .mode-option label:hover::before {
            left: 100%;
        }
        
        .mobile-mode-option label:hover {
            border-color: #3498db;
            background: linear-gradient(145deg, #f8f9fa 0%, #ffffff 100%);
            transform: translateY(-6px) scale(1.03);
            box-shadow: none;
        }
        
        .mobile-mode-option label:hover::before {
            background: linear-gradient(90deg, #3498db, #74b9ff, #3498db);
            opacity: 0.8;
        }
        
        .mobile-mode-option label:hover::after {
            border-color: #3498db;
            background: linear-gradient(145deg, #dbeafe, #bfdbfe);
        }
          /* Icons always colored */
        label[for="mode-normal"] .mode-icon {
            color: #2a7d3f;
        }
        
        label[for="mode-maintenance"] .mode-icon {
            color: #b76d00;
        }
        
        label[for="mode-closed"] .mode-icon {
            color: #b72800;
        }
        
        label[for="mobile-inherit"] .mobile-mode-icon {
            color: #646970;
        }
        
        label[for="mobile-normal"] .mobile-mode-icon {
            color: #2a7d3f;
        }
        
        label[for="mobile-maintenance"] .mobile-mode-icon {
            color: #b76d00;
        }
        
        label[for="mobile-closed"] .mobile-mode-icon {
            color: #b72800;
        }
          /* Enhanced selected states with gradients and animations */
        #mode-normal:checked + label {
            background: linear-gradient(145deg, #d1fae5 0%, #a7f3d0 50%, #ecfdf5 100%);
            color: #064e3b;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2), 0 8px 25px rgba(16, 185, 129, 0.3);
            transform: scale(1.05);
        }
        
        #mode-normal:checked + label .mode-icon {
            animation: pulse 2s infinite;
            color: #059669 !important;
        }
        
        #mode-normal:not(:checked) + label:hover {
            background: linear-gradient(145deg, #f0fdf4 0%, #dcfce7 100%);
        }
          /* Mobile Live Mode - Green with enhanced effects */
        #mobile-normal:checked + label {
            background: linear-gradient(145deg, #d1fae5 0%, #a7f3d0 50%, #ecfdf5 100%);
            color: #064e3b;
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.25), 0 12px 35px rgba(16, 185, 129, 0.4);
            transform: scale(1.06);
        }
        
        #mobile-normal:checked + label .mobile-mode-icon {
            animation: bounce 1.5s infinite;
            color: #059669 !important;
        }
        
        #mobile-normal:checked + label::before {
            background: linear-gradient(90deg, #10b981, #34d399, #10b981);
            opacity: 1;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
        }
        
        #mobile-normal:checked + label::after {
            border-color: #10b981;
            background: linear-gradient(145deg, #a7f3d0, #6ee7b7);
            box-shadow: inset 0 2px 4px rgba(16, 185, 129, 0.3), 0 0 12px rgba(16, 185, 129, 0.4);
        }
        
        #mobile-normal:not(:checked) + label:hover {
            background: linear-gradient(145deg, #f0fdf4 0%, #dcfce7 100%);
        }
          /* Maintenance Mode - Orange with enhanced effects */
        #mode-maintenance:checked + label {
            background: linear-gradient(145deg, #fef3c7 0%, #fde68a 50%, #fffbeb 100%);
            color: #92400e;
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2), 0 8px 25px rgba(245, 158, 11, 0.3);
            transform: scale(1.05);
        }
        
        #mode-maintenance:checked + label .mode-icon {
            animation: rotate 3s linear infinite;
            color: #d97706 !important;
        }
        
        #mode-maintenance:not(:checked) + label:hover {
            background: linear-gradient(145deg, #fffbeb 0%, #fef3c7 100%);
        }
          /* Mobile Maintenance Mode - Orange with enhanced effects */
        #mobile-maintenance:checked + label {
            background: linear-gradient(145deg, #fef3c7 0%, #fde68a 50%, #fffbeb 100%);
            color: #92400e;
            border-color: #f59e0b;
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.25), 0 12px 35px rgba(245, 158, 11, 0.4);
            transform: scale(1.06);
        }
        
        #mobile-maintenance:checked + label .mobile-mode-icon {
            animation: shake 2s infinite;
            color: #d97706 !important;
        }
        
        #mobile-maintenance:checked + label::before {
            background: linear-gradient(90deg, #f59e0b, #fbbf24, #f59e0b);
            opacity: 1;
            box-shadow: 0 0 8px rgba(245, 158, 11, 0.5);
        }
        
        #mobile-maintenance:checked + label::after {
            border-color: #f59e0b;
            background: linear-gradient(145deg, #fde68a, #fcd34d);
            box-shadow: inset 0 2px 4px rgba(245, 158, 11, 0.3), 0 0 12px rgba(245, 158, 11, 0.4);
        }
        
        #mobile-maintenance:not(:checked) + label:hover {
            background: linear-gradient(145deg, #fffbeb 0%, #fef3c7 100%);
        }
          /* Closed Mode - Red with enhanced effects */
        #mode-closed:checked + label {
            background: linear-gradient(145deg, #fecaca 0%, #fca5a5 50%, #fef2f2 100%);
            color: #7f1d1d;
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2), 0 8px 25px rgba(239, 68, 68, 0.3);
            transform: scale(1.05);
        }
        
        #mode-closed:checked + label .mode-icon {
            animation: pulse 2s infinite;
            color: #dc2626 !important;
        }
        
        #mode-closed:not(:checked) + label:hover {
            background: linear-gradient(145deg, #fef2f2 0%, #fecaca 100%);
        }
          /* Mobile Closed Mode - Red with enhanced effects */
        #mobile-closed:checked + label {
            background: linear-gradient(145deg, #fecaca 0%, #fca5a5 50%, #fef2f2 100%);
            color: #7f1d1d;
            border-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.25), 0 12px 35px rgba(239, 68, 68, 0.4);
            transform: scale(1.06);
        }
        
        #mobile-closed:checked + label .mobile-mode-icon {
            animation: pulse 2s infinite;
            color: #dc2626 !important;
        }
        
        #mobile-closed:checked + label::before {
            background: linear-gradient(90deg, #ef4444, #f87171, #ef4444);
            opacity: 1;
            box-shadow: 0 0 8px rgba(239, 68, 68, 0.5);
        }
        
        #mobile-closed:checked + label::after {
            border-color: #ef4444;
            background: linear-gradient(145deg, #fca5a5, #f87171);
            box-shadow: inset 0 2px 4px rgba(239, 68, 68, 0.3), 0 0 12px rgba(239, 68, 68, 0.4);
        }        #mobile-closed:not(:checked) + label:hover {
            background: linear-gradient(145deg, #fef2f2 0%, #fecaca 100%);
        }
          /* Visual Active States for Global Mode */
        .mobile-visual-active label {
            position: relative;
        }
        
        /* Green state for Global Open mode */
        .mobile-visual-active.global-open label {
            background: linear-gradient(145deg, #d1fae5 0%, #a7f3d0 50%, #ecfdf5 100%) !important;
            color: #064e3b !important;
            border-color: #10b981 !important;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.25), 0 12px 35px rgba(16, 185, 129, 0.4) !important;
            transform: scale(1.06) !important;
        }
        
        .mobile-visual-active.global-open label .mobile-mode-icon {
            animation: bounce 1.5s infinite;
            color: #059669 !important;
        }
        
        .mobile-visual-active.global-open label::before {
            background: linear-gradient(90deg, #10b981, #34d399, #10b981) !important;
            opacity: 1 !important;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.5) !important;
        }
        
        .mobile-visual-active.global-open label::after {
            border-color: #10b981 !important;
            background: linear-gradient(145deg, #a7f3d0, #6ee7b7) !important;
            box-shadow: inset 0 2px 4px rgba(16, 185, 129, 0.3), 0 0 12px rgba(16, 185, 129, 0.4) !important;
        }
        
        /* Orange state for Global Maintenance mode */
        .mobile-visual-active.global-maintenance label {
            background: linear-gradient(145deg, #fef3c7 0%, #fde68a 50%, #fffbeb 100%) !important;
            color: #92400e !important;
            border-color: #f59e0b !important;
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.25), 0 12px 35px rgba(245, 158, 11, 0.4) !important;
            transform: scale(1.06) !important;
        }
        
        .mobile-visual-active.global-maintenance label .mobile-mode-icon {
            animation: shake 2s infinite;
            color: #d97706 !important;
        }
        
        .mobile-visual-active.global-maintenance label::before {
            background: linear-gradient(90deg, #f59e0b, #fbbf24, #f59e0b) !important;
            opacity: 1 !important;
            box-shadow: 0 0 8px rgba(245, 158, 11, 0.5) !important;
        }
        
        .mobile-visual-active.global-maintenance label::after {
            border-color: #f59e0b !important;
            background: linear-gradient(145deg, #fde68a, #fcd34d) !important;
            box-shadow: inset 0 2px 4px rgba(245, 158, 11, 0.3), 0 0 12px rgba(245, 158, 11, 0.4) !important;
        }
        
        /* Red state for Global Closed mode */
        .mobile-visual-active.global-closed label {
            background: linear-gradient(145deg, #fecaca 0%, #fca5a5 50%, #fef2f2 100%) !important;
            color: #7f1d1d !important;
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.25), 0 12px 35px rgba(239, 68, 68, 0.4) !important;
            transform: scale(1.06) !important;
        }
        
        .mobile-visual-active.global-closed label .mobile-mode-icon {
            animation: pulse 2s infinite;
            color: #dc2626 !important;
        }
        
        .mobile-visual-active.global-closed label::before {
            background: linear-gradient(90deg, #ef4444, #f87171, #ef4444) !important;
            opacity: 1 !important;
            box-shadow: 0 0 8px rgba(239, 68, 68, 0.5) !important;
        }
        
        .mobile-visual-active.global-closed label::after {
            border-color: #ef4444 !important;
            background: linear-gradient(145deg, #fca5a5, #f87171) !important;
            box-shadow: inset 0 2px 4px rgba(239, 68, 68, 0.3), 0 0 12px rgba(239, 68, 68, 0.4) !important;
        }
        
        /* CSS Keyframe Animations */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% {
                transform: translateY(0);
            }
            40%, 43% {
                transform: translateY(-8px);
            }
            70% {
                transform: translateY(-4px);
            }
            90% {
                transform: translateY(-2px);
            }
        }
        
        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
            20%, 40%, 60%, 80% { transform: translateX(2px); }
        }
  
        
        .inherit-toggle-btn .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
            color: #646970;
        }
        
        .mode-icon {
            font-size: 32px;
            margin-bottom: 15px;
        }        .mobile-mode-icon {
            font-size: 40px;
            margin: 30px 0 20px 0;
        }
  
          .mode-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 8px;
        }.mobile-mode-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 10px;
            line-height: 1.2;
        }
  
        
        .mode-description {
            font-size: 13px;
        }
          .mobile-mode-description {
            font-size: 18px;
            line-height: 1.2;
            margin-top: auto;
            padding-bottom: 50px;        }
  
        
        .neetrino-submit {
            margin-top: 30px;
            text-align: center;
        }
        
        .button-primary {
            padding: 8px 20px;
            height: auto;
            min-width: 180px;
        }
        
        .status-indicator {
            display: inline-flex;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 8px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #edfaef;
            color: #2a7d3f;
        }
        
        .status-inactive {
            background-color: #f8f9fa;
            color: #646970;
        }
        
        @media (max-width: 1200px) {
            .mode-options {
                gap: 12px;
            }
              .mobile-mode-options {
                gap: 12px;
                justify-content: center;
            }
            
            .mode-option label {
                padding: 30px 12px;
                min-height: 140px;
            }
              .mobile-mode-option label {
                padding: 30px 16px;
                min-height: 320px;
                width: 160px;
            }
  
            
            .mode-icon {
                font-size: 28px !important;
            }            .mobile-mode-icon {
                font-size: 36px !important;
                margin: 24px 0 16px 0;
            }
  
              .mode-title {
                font-size: 20px;
            }.mobile-mode-title {
                font-size: 20px;
            }
  
        }
        
        @media (max-width: 782px) {
            .mode-options {
                flex-direction: column;
                gap: 15px;
            }
              .mobile-mode-options {
                flex-direction: row;
                gap: 12px;
                justify-content: center;
            }
            
            .mode-option label {
                padding: 25px 20px;
                min-height: 120px;
                flex-direction: row;
                text-align: left;
                align-items: center;
                justify-content: flex-start;
            }
              .mobile-mode-option label {
                padding: 30px 16px;
                min-height: 280px;
                width: 140px;
                flex-direction: column;
                text-align: center;
                align-items: center;
                justify-content: flex-start;
            }
  
            
            .mode-icon {
                font-size: 32px !important;
                margin-bottom: 0;
                margin-right: 20px;
            }            .mobile-mode-icon {
                font-size: 32px !important;
                margin: 20px 0 12px 0;
            }
  
            
            .mode-details {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
            }
            
            .mode-title {
                font-size: 18px;
                margin-bottom: 5px;
            }            .mobile-mode-title {
                font-size: 18px;
                margin-bottom: 3px;
            }
  
        }
        
        @media (max-width: 600px) {
            .neetrino-content-area {
                margin: 0 10px;
            }
            
            .neetrino-header {
                padding: 15px 20px;
            }
            
            .neetrino-card {
                padding: 20px;
            }
            
            .mode-option label {
                padding: 20px 15px;
                min-height: 100px;
            }            .mobile-mode-option label {
                padding: 24px 12px;
                min-height: 240px;
                width: 120px;
            }
  
            
            .mode-icon {
                font-size: 28px !important;
                margin-right: 15px;
            }            .mobile-mode-icon {
                font-size: 28px !important;
                margin: 16px 0 8px 0;
            }
  
          }
        
        /* Remote Control Toggle Button Styles */
        .neetrino-remote-toggle-btn {
            background: linear-gradient(135deg, #2fc7f7 0%, #1ab1e5 100%);
            border: none;
            color: white;
            padding: 15px 30px;
            font-size: 15px;
            font-weight: 500;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(47, 199, 247, 0.3);
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            min-width: 220px;
            justify-content: center;
        }
        
        /* Стиль для активированного API */
        .neetrino-remote-toggle-btn.api-active {
            background: linear-gradient(135deg, #34c759 0%, #28a745 100%);
            box-shadow: 0 2px 10px rgba(52, 199, 89, 0.3);
        }
        
        .neetrino-remote-toggle-btn:hover {
            background: linear-gradient(135deg, #1ab1e5 0%, #0a9fd2 100%);
            box-shadow: 0 4px 15px rgba(47, 199, 247, 0.4);
            transform: translateY(-2px);
        }
        
        /* Hover для активированного API */
        .neetrino-remote-toggle-btn.api-active:hover {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            box-shadow: 0 4px 15px rgba(52, 199, 89, 0.4);
            transform: translateY(-2px);
        }
        
        .neetrino-remote-toggle-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(47, 199, 247, 0.3);
        }
        
        /* API button divider styles */
        .neetrino-api-button-divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin-bottom: 20px;
            color: #646970;
            font-size: 14px;
        }
        
        .neetrino-api-button-divider::before,
        .neetrino-api-button-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e4e7;
        }
        
        .neetrino-api-button-divider::before {
            margin-right: 15px;
        }
        
        .neetrino-api-button-divider::after {
            margin-left: 15px;
        }
        
        .neetrino-remote-toggle-btn .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        
        /* Remote Panel Styles */
        .neetrino-remote-panel {
            animation: slideDown 0.3s ease-out;
            position: relative;
            z-index: 5;
        }
        
        /* Compact Password Access Section Styles - Updated for unified design */
        .password-toggle-container {
            margin: 0;
        }
        
        .password-toggle-switch input[type="checkbox"] {
            display: none;
        }
        
        .password-btn .dashicons {
            font-size: 14px;
            width: 14px;
            height: 14px;
        }
        
        /* Style to prevent overlapping with WordPress content */
        .neetrino-card {
            position: relative; 
            z-index: 10;
            overflow: visible;
        }
        
        .neetrino-card:after {
            content: '';
            display: table;
            clear: both;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .neetrino-remote-settings .form-table {
            margin: 0;
        }
        
        .neetrino-remote-settings .form-table th {
            display: none;
        }
        
        .neetrino-remote-settings .form-table td {
            padding: 0;
        }    </style>
    
    <div class="wrap">
        <div class="neetrino-content-area">            <form method="post" action="">
                <?php wp_nonce_field('neetrino_maintenance_save'); ?>
                
                <!-- Top Header Buttons with Password Section -->
                <div class="top-header-buttons">
                    <div class="left-controls">
                        <!-- Compact Password Access Section - Show/hide via JavaScript based on current selection -->
                        <div class="neetrino-password-section" style="<?php echo $maintenance_enabled ? '' : 'display: none;'; ?>">
                            <div class="password-toggle-container">
                                <label class="password-toggle-switch">
                                    <input type="checkbox" name="password_access" value="1" <?php checked($current_password_access, true); ?> id="password-access-toggle">
                                    <span class="password-toggle-slider"></span>
                                    <span class="password-toggle-text">Пароль</span>
                                </label>
                            </div>
                            
                            <div class="password-controls" style="<?php echo $current_password_access ? '' : 'display: none;'; ?>">
                                <div class="password-field-container">
                                    <input type="text" name="access_password" id="access-password-field" value="<?php echo esc_attr($current_access_password); ?>" readonly placeholder="Не создан">
                                    <div class="password-buttons">
                                        <button type="button" id="generate-password-btn" class="password-btn generate-btn" title="<?php echo empty($current_access_password) ? 'Создать пароль' : 'Новый пароль'; ?>">
                                            <span class="dashicons dashicons-admin-network"></span>
                                        </button>
                                        <button type="button" id="copy-password-btn" class="password-btn copy-btn" style="<?php echo empty($current_access_password) ? 'display: none;' : ''; ?>" title="Копировать пароль">
                                            <span class="dashicons dashicons-admin-page"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="right-controls">
                        <div class="inherit-toggle-wrapper">
                            <input type="radio" id="mobile-inherit" name="mobile_mode" value="inherit" <?php checked($current_mobile_mode, 'inherit'); ?>>
                            <label for="mobile-inherit" class="header-inherit-btn">
                                <span class="dashicons dashicons-admin-links"></span>
                                Global
                            </label>
                        </div>
                        <input type="submit" name="neetrino_save" class="header-submit-btn" value="Сохранить" />
                    </div>
                </div>
                
                <div class="neetrino-card">
                    <div class="mode-options">
                        <div class="mode-option">
                            <input type="radio" id="mode-normal" name="site_mode" value="open" <?php checked($current_mode, 'open'); ?>>                            <label for="mode-normal">
                                <span class="mode-icon dashicons dashicons-yes-alt"></span>
                                <div class="mode-details">
                                    <span class="mode-title">Открыт</span>
                                </div>
                            </label>
                        </div>
                        
                        <div class="mode-option">
                            <input type="radio" id="mode-maintenance" name="site_mode" value="maintenance" <?php checked($current_mode, 'maintenance'); ?>>                            <label for="mode-maintenance">
                                <span class="mode-icon dashicons dashicons-admin-tools"></span>
                                <div class="mode-details">
                                    <span class="mode-title">Обслуживание</span>
                                </div>
                            </label>
                        </div>
                        
                        <div class="mode-option">
                            <input type="radio" id="mode-closed" name="site_mode" value="closed" <?php checked($current_mode, 'closed'); ?>>                            <label for="mode-closed">
                                <span class="mode-icon dashicons dashicons-lock"></span>
                                <div class="mode-details">
                                    <span class="mode-title">Закрыт</span>
                                </div>
                            </label>                        </div>                    </div>
                      
                    <div class="mobile-mode-options">
                        <div class="mobile-mode-option">
                            <input type="radio" id="mobile-normal" name="mobile_mode" value="open" <?php checked($current_mobile_mode, 'open'); ?>>
                            <label for="mobile-normal">
                                <span class="mobile-mode-icon dashicons dashicons-yes-alt"></span>
                                <div class="mode-details">
                                    <span class="mobile-mode-title">Открыт</span>
                                </div>
                            </label>
                        </div>                        <div class="mobile-mode-option">
                            <input type="radio" id="mobile-maintenance" name="mobile_mode" value="maintenance" <?php checked($current_mobile_mode, 'maintenance'); ?>>
                            <label for="mobile-maintenance">
                                <span class="mobile-mode-icon dashicons dashicons-admin-tools"></span>
                                <div class="mode-details">
                                    <span class="mobile-mode-title">Обслуживание</span>
                                </div>
                            </label>
                        </div>                        <div class="mobile-mode-option">
                            <input type="radio" id="mobile-closed" name="mobile_mode" value="closed" <?php checked($current_mobile_mode, 'closed'); ?>>
                            <label for="mobile-closed">
                                <span class="mobile-mode-icon dashicons dashicons-lock"></span>
                                <div class="mode-details">
                                    <span class="mobile-mode-title">Закрыт</span>
                                </div>
                            </label>
                        </div>                    </div>                <!-- End of Main Card Content -->
                </div>
                  <style>
                    /* Top Header Buttons with Password Section - Unified Style */
                    .top-header-buttons {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 16px;
                        margin-top: 12px;
                        margin-bottom: 20px;
                        padding: 16px 24px;
                        background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
                        border: 1px solid #e3e6ea;
                        border-radius: 12px;
                        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
                        transition: all 0.3s ease;
                        min-width: 800px;
                        max-width: 1200px;
                        margin-left: auto;
                        margin-right: auto;
                    }
                    
                    .top-header-buttons:hover {
                        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
                        border-color: #c8ccd0;
                    }
                    
                    .left-controls {
                        display: flex;
                        align-items: center;
                        gap: 20px;
                        flex: 1;
                        min-width: 0;
                    }
                    
                    .right-controls {
                        display: flex;
                        align-items: center;
                        gap: 16px;
                        flex-shrink: 0;
                    }
                    
                    /* Password Section - Integrated Style */
                    .neetrino-password-section {
                        background: transparent;
                        border: none;
                        border-radius: 0;
                        padding: 0;
                        margin: 0;
                        box-shadow: none;
                        transition: all 0.3s ease;
                        display: inline-flex;
                        align-items: center;
                        gap: 12px;
                        white-space: nowrap;
                        min-width: 320px;
                        max-width: 420px;
                        justify-content: flex-start;
                    }
                    
                    .password-toggle-switch {
                        display: flex;
                        align-items: center;
                        cursor: pointer;
                        font-size: 15px;
                        font-weight: 600;
                        color: #495057;
                        gap: 10px;
                        padding: 8px 16px;
                        border-radius: 10px;
                        transition: all 0.3s ease;
                        flex-shrink: 0;
                    }
                    
                    .password-toggle-switch:hover {
                        background: rgba(52, 152, 219, 0.05);
                    }
                    
                    .password-toggle-slider {
                        position: relative;
                        width: 48px;
                        height: 26px;
                        background: linear-gradient(145deg, #e9ecef 0%, #dee2e6 100%);
                        border-radius: 26px;
                        transition: all 0.3s ease;
                        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
                        flex-shrink: 0;
                    }
                    
                    .password-toggle-slider:before {
                        content: "";
                        position: absolute;
                        height: 22px;
                        width: 22px;
                        left: 2px;
                        top: 2px;
                        background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
                        border-radius: 50%;
                        transition: all 0.3s ease;
                        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
                    }
                    
                    input[type="checkbox"]:checked + .password-toggle-slider {
                        background: linear-gradient(145deg, #10b981 0%, #059669 100%);
                        box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
                    }
                    
                    input[type="checkbox"]:checked + .password-toggle-slider:before {
                        transform: translateX(22px);
                        background: linear-gradient(145deg, #ffffff 0%, #ecfdf5 100%);
                        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
                    }
                    
                    .password-controls {
                        transition: all 0.3s ease;
                        opacity: 1;
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        flex: 1;
                        min-width: 0;
                    }
                    
                    .password-field-container {
                        display: flex;
                        gap: 6px;
                        align-items: center;
                        background: rgba(248, 249, 250, 0.8);
                        padding: 4px 8px;
                        border-radius: 6px;
                        border: 1px solid rgba(227, 230, 234, 0.6);
                        flex: 1;
                        min-width: 180px;
                        max-width: 240px;
                    }
                    
                    #access-password-field {
                        width: 100%;
                        min-width: 120px;
                        max-width: 160px;
                        padding: 6px 8px;
                        border: none;
                        border-radius: 4px;
                        font-size: 12px;
                        font-family: 'Courier New', monospace;
                        background: transparent;
                        color: #495057;
                        transition: all 0.2s ease;
                        flex: 1;
                    }
                    
                    #access-password-field:focus {
                        outline: none;
                        background: rgba(255, 255, 255, 0.9);
                    }
                    
                    .password-buttons {
                        display: flex;
                        gap: 6px;
                        flex-shrink: 0;
                    }
                    
                    .password-btn {
                        display: flex;
                        align-items: center;
                        gap: 3px;
                        padding: 6px 8px;
                        border: none;
                        border-radius: 6px;
                        cursor: pointer;
                        font-size: 12px;
                        font-weight: 600;
                        transition: all 0.2s ease;
                        min-width: 28px;
                        justify-content: center;
                        height: 28px;
                    }
                    
                    .generate-btn {
                        background: linear-gradient(145deg, #3498db 0%, #2980b9 100%);
                        color: white;
                        box-shadow: 0 2px 4px rgba(52, 152, 219, 0.25);
                    }
                    
                    .generate-btn:hover {
                        background: linear-gradient(145deg, #2980b9 0%, #1f5f8b 100%);
                        transform: translateY(-1px);
                        box-shadow: 0 3px 8px rgba(52, 152, 219, 0.35);
                    }
                    
                    .copy-btn {
                        background: linear-gradient(145deg, #10b981 0%, #059669 100%);
                        color: white;
                        box-shadow: 0 2px 4px rgba(16, 185, 129, 0.25);
                    }
                    
                    .copy-btn:hover {
                        background: linear-gradient(145deg, #059669 0%, #047857 100%);
                        transform: translateY(-1px);
                        box-shadow: 0 3px 8px rgba(16, 185, 129, 0.35);
                    }
                    
                    .copy-btn.copied {
                        background: linear-gradient(145deg, #f59e0b 0%, #d97706 100%);
                        box-shadow: 0 2px 4px rgba(245, 158, 11, 0.25);
                    }
                    
                    /* Header Buttons - Unified Style */
                    .header-inherit-btn,
                    .header-submit-btn {
                        min-width: 120px;
                        height: 44px;
                        font-size: 15px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 10px;
                        border-radius: 10px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
                        border: 1px solid transparent;
                        text-decoration: none;
                        text-transform: none;
                        letter-spacing: normal;
                    }
                    
                    /* Global Button */
                    .header-inherit-btn {
                        background: linear-gradient(145deg, #6c7ae0 0%, #5a67d8 100%);
                        color: white;
                        border-color: #5a67d8;
                    }
                    
                    .header-inherit-btn:hover {
                        background: linear-gradient(145deg, #5a67d8 0%, #4c51bf 100%);
                        transform: translateY(-1px);
                        box-shadow: 0 4px 12px rgba(90, 103, 216, 0.3);
                    }
                    
                    /* Global Button Active State */
                    input[type="radio"]:checked + .header-inherit-btn {
                        background: linear-gradient(145deg, #3b82f6 0%, #1d4ed8 100%) !important;
                        color: white !important;
                        border-color: #1d4ed8 !important;
                        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2), 0 4px 12px rgba(59, 130, 246, 0.4) !important;
                        transform: scale(1.02);
                    }
                    
                    input[type="radio"]:checked + .header-inherit-btn .dashicons {
                        animation: rotate 2s infinite linear;
                        color: white !important;
                    }
                    
                    /* Save Button */
                    .header-submit-btn {
                        background: linear-gradient(145deg, #10b981 0%, #059669 100%);
                        color: white;
                        border: none;
                        border-color: #059669;
                    }
                    
                    .header-submit-btn:hover {
                        background: linear-gradient(145deg, #059669 0%, #047857 100%);
                        transform: translateY(-1px);
                        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
                    }
                    
                    .header-submit-btn:active {
                        transform: translateY(0);
                        box-shadow: 0 2px 6px rgba(16, 185, 129, 0.2);
                    }
                    
                    /* Hide the radio input for inherit button */
                    .top-header-buttons input[type="radio"] {
                        display: none;
                    }
                    
                    /* Mobile responsive adjustments */
                    @media (max-width: 1024px) {
                        .top-header-buttons {
                            min-width: auto;
                            max-width: 100%;
                            margin-left: 20px;
                            margin-right: 20px;
                        }
                        
                        .neetrino-password-section {
                            min-width: 400px;
                        }
                        
                        .password-field-container {
                            min-width: 200px;
                            max-width: 280px;
                        }
                    }
                    
                    @media (max-width: 768px) {
                        .top-header-buttons {
                            flex-direction: column;
                            align-items: stretch;
                            gap: 16px;
                            padding: 20px;
                            min-width: auto;
                            margin-left: 10px;
                            margin-right: 10px;
                        }
                        
                        .left-controls,
                        .right-controls {
                            justify-content: center;
                            flex-wrap: wrap;
                            width: 100%;
                        }
                        
                        .neetrino-password-section {
                            order: -1;
                            align-self: center;
                            min-width: 320px;
                            justify-content: center;
                        }
                        
                        .password-field-container {
                            flex-wrap: nowrap;
                            min-width: 240px;
                            max-width: 300px;
                        }
                        
                        #access-password-field {
                            min-width: 140px;
                        }
                        
                        .header-inherit-btn,
                        .header-submit-btn {
                            min-width: 140px;
                        }
                    }
                    
                    @media (max-width: 480px) {
                        .neetrino-password-section {
                            min-width: 280px;
                            flex-wrap: wrap;
                            gap: 12px;
                        }
                        
                        .password-field-container {
                            width: 100%;
                            min-width: auto;
                            max-width: none;
                        }
                        
                        .password-toggle-switch {
                            padding: 6px 12px;
                        }
                        
                        .password-controls {
                            width: 100%;
                            justify-content: center;
                        }
                    }
                    
                    /* Animation keyframes */
                    @keyframes rotate {
                        from {
                            transform: rotate(0deg);
                        }
                        to {
                            transform: rotate(360deg);
                        }
                    }                </style>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const globalToggle = document.getElementById('mobile-inherit');
            const desktopRadios = document.querySelectorAll('input[name="site_mode"]');
            const mobileRadios = document.querySelectorAll('input[name="mobile_mode"]:not(#mobile-inherit)');
            
            // Function to check if maintenance mode is enabled
            function isMaintenanceEnabled() {
                // Check desktop mode
                const activeDesktopMode = document.querySelector('input[name="site_mode"]:checked');
                if (activeDesktopMode && activeDesktopMode.value === 'maintenance') {
                    return true;
                }
                
                // Check mobile mode (only if not inherit)
                if (!globalToggle.checked) {
                    const activeMobileMode = document.querySelector('input[name="mobile_mode"]:checked:not(#mobile-inherit)');
                    if (activeMobileMode && activeMobileMode.value === 'maintenance') {
                        return true;
                    }
                }
                
                return false;
            }
            
            // Function to update password section visibility
            function updatePasswordSectionVisibility() {
                // Always try to find the password section (it may be hidden but still in DOM)
                const passwordSection = document.querySelector('.neetrino-password-section');
                if (!passwordSection) return;
                
                const maintenanceEnabled = isMaintenanceEnabled();
                
                if (maintenanceEnabled) {
                    // Show password section with smooth animation
                    passwordSection.style.display = 'inline-flex';
                    passwordSection.style.opacity = '0';
                    passwordSection.style.transform = 'translateY(-10px)';
                    
                    setTimeout(() => {
                        passwordSection.style.transition = 'all 0.3s ease';
                        passwordSection.style.opacity = '1';
                        passwordSection.style.transform = 'translateY(0)';
                    }, 10);
                } else {
                    // Hide password section with smooth animation
                    passwordSection.style.transition = 'all 0.3s ease';
                    passwordSection.style.opacity = '0';
                    passwordSection.style.transform = 'translateY(-10px)';
                    
                    setTimeout(() => {
                        passwordSection.style.display = 'none';
                    }, 300);
                }
            }
            
            // Function to update mobile visual states based on desktop selection
            function updateMobileVisualStates() {
                if (globalToggle.checked) {
                    // Get current desktop mode
                    const activeDesktopMode = document.querySelector('input[name="site_mode"]:checked');
                    if (activeDesktopMode) {
                        const desktopValue = activeDesktopMode.value;
                        
                        // Remove all mobile selections visually
                        mobileRadios.forEach(radio => {
                            radio.parentElement.classList.remove('mobile-visual-active', 'global-open', 'global-maintenance', 'global-closed');
                        });
                        
                        // Find corresponding mobile button and make it visually active
                        const correspondingMobile = document.querySelector(`input[name="mobile_mode"][value="${desktopValue}"]`);
                        if (correspondingMobile) {
                            correspondingMobile.parentElement.classList.add('mobile-visual-active');
                            
                            // Add appropriate color class based on mode
                            if (desktopValue === 'open') {
                                correspondingMobile.parentElement.classList.add('global-open');
                            } else if (desktopValue === 'maintenance') {
                                correspondingMobile.parentElement.classList.add('global-maintenance');
                            } else if (desktopValue === 'closed') {
                                correspondingMobile.parentElement.classList.add('global-closed');
                            }
                        }
                    }
                } else {
                    // Remove all visual active states when not in global mode
                    document.querySelectorAll('.mobile-mode-option').forEach(option => {
                        option.classList.remove('mobile-visual-active', 'global-open', 'global-maintenance', 'global-closed');
                    });
                }
            }
            
            // Listen to global toggle changes
            globalToggle.addEventListener('change', function() {
                updateMobileVisualStates();
                updatePasswordSectionVisibility();
            });
            
            // Listen to desktop mode changes
            desktopRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (globalToggle.checked) {
                        updateMobileVisualStates();
                    }
                    updatePasswordSectionVisibility();
                });
            });
            
            // Listen to mobile radio changes to remove visual states when manually selected
            mobileRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        // Remove all visual active states when mobile is manually selected
                        document.querySelectorAll('.mobile-mode-option').forEach(option => {
                            option.classList.remove('mobile-visual-active');
                        });
                    }
                    updatePasswordSectionVisibility();
                });
            });
            
            // Initial update on page load
            updateMobileVisualStates();
            updatePasswordSectionVisibility();
        });
        
        // Password Access Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const passwordToggle = document.getElementById('password-access-toggle');
            const passwordControls = document.querySelector('.password-controls');
            const generateBtn = document.getElementById('generate-password-btn');
            const copyBtn = document.getElementById('copy-password-btn');
            const passwordField = document.getElementById('access-password-field');
            
            // Toggle password controls visibility
            if (passwordToggle) {
                passwordToggle.addEventListener('change', function() {
                    if (this.checked) {
                        passwordControls.style.display = 'block';
                        passwordControls.style.opacity = '0';
                        setTimeout(() => {
                            passwordControls.style.opacity = '1';
                        }, 10);
                        
                        // Auto-generate password if field is empty
                        if (!passwordField.value.trim()) {
                            generateBtn.click();
                        }
                    } else {
                        passwordControls.style.opacity = '0';
                        setTimeout(() => {
                            passwordControls.style.display = 'none';
                        }, 400);
                    }
                });
            }
            
            // Generate password
            if (generateBtn) {
                generateBtn.addEventListener('click', function() {
                    const btn = this;
                    const originalIcon = btn.querySelector('.dashicons');
                    const originalClass = originalIcon.className;
                    
                    // Show loading state
                    originalIcon.className = 'dashicons dashicons-update';
                    btn.disabled = true;
                    btn.style.opacity = '0.7';
                    
                    // AJAX request to generate password
                    const formData = new FormData();
                    formData.append('action', 'generate_maintenance_password');
                    formData.append('nonce', '<?php echo wp_create_nonce('neetrino_generate_password'); ?>');
                    
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            passwordField.value = data.data.password;
                            copyBtn.style.display = 'flex';
                            
                            // Flash success animation
                            originalIcon.className = 'dashicons dashicons-yes';
                            btn.style.background = 'linear-gradient(145deg, #10b981 0%, #059669 100%)';
                            
                            setTimeout(() => {
                                originalIcon.className = originalClass;
                                btn.style.background = '';
                            }, 1000);
                        } else {
                            alert('Ошибка создания пароля');
                            originalIcon.className = originalClass;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Ошибка создания пароля');
                        originalIcon.className = originalClass;
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                    });
                });
            }
            
            // Copy password
            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    const btn = this;
                    const originalIcon = btn.querySelector('.dashicons');
                    const originalClass = originalIcon.className;
                    
                    passwordField.select();
                    passwordField.setSelectionRange(0, 99999); // For mobile devices
                    
                    try {
                        const successful = document.execCommand('copy');
                        if (successful) {
                            originalIcon.className = 'dashicons dashicons-yes';
                            btn.classList.add('copied');
                            
                            setTimeout(() => {
                                originalIcon.className = originalClass;
                                btn.classList.remove('copied');
                            }, 1500);
                        } else {
                            throw new Error('Copy command failed');
                        }
                    } catch (err) {
                        // Fallback for modern browsers
                        if (navigator.clipboard) {
                            navigator.clipboard.writeText(passwordField.value).then(() => {
                                originalIcon.className = 'dashicons dashicons-yes';
                                btn.classList.add('copied');
                                
                                setTimeout(() => {
                                    originalIcon.className = originalClass;
                                    btn.classList.remove('copied');
                                }, 1500);
                            }).catch(() => {
                                alert('Не удалось скопировать пароль');
                            });
                        } else {
                            alert('Копирование не поддерживается в вашем браузере');
                        }
                    }
                });
            }
        });
    </script>
    
    <?php
    // Register settings here
    settings_fields('neetrino_maintenance');
}

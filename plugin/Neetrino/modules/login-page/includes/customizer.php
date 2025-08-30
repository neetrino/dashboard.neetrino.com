<?php
/**
 * Login Page Customizer
 * 
 * Handles the styling and visual customization of the login page
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Apply custom styles to the login page
 * 
 * @param array $options Current options
 * @param string $default_logo Default logo path
 */
function apply_login_page_styles($options, $default_logo) {
    // Проверяем, есть ли параметры предпросмотра в URL
    $is_preview = isset($_GET['preview']) && $_GET['preview'] === 'true';
    $background = $options['background'];
    $text_color = $options['text_color'];
    $logo = $default_logo; // Always use the default logo from the module's logos folder
    $form_position = isset($options['form_position']) ? $options['form_position'] : 'center';
    
    // Если это предпросмотр, используем параметры из URL
    if ($is_preview) {
        if (isset($_GET['bg']) && !empty($_GET['bg'])) {
            $background = sanitize_text_field($_GET['bg']);
        }
        if (isset($_GET['color']) && !empty($_GET['color'])) {
            $text_color = sanitize_hex_color($_GET['color']);
        }
        if (isset($_GET['position']) && !empty($_GET['position'])) {
            $position = sanitize_text_field($_GET['position']);
            if (in_array($position, ['left', 'right', 'center'])) {
                $form_position = $position;
            }
        }
    }
    
    // Get glass effect intensity from options (0-100 scale)
    $glass_effect_intensity = isset($options['glass_effect_intensity']) ? intval($options['glass_effect_intensity']) : 50;
    
    // If this is preview, use parameter from URL
    if ($is_preview && isset($_GET['glass_effect']) && !empty($_GET['glass_effect'])) {
        $glass_effect_intensity = intval($_GET['glass_effect']);
        // Ensure it's within 0-100 range
        $glass_effect_intensity = max(0, min(100, $glass_effect_intensity));
    }
    
    // Convert 0-100 scale to appropriate opacity values
    // At 0 intensity, there should be no blur and minimal opacity
    // At 100 intensity, there should be maximum blur and higher opacity
    
    // Calculate blur amount (0px - 20px)
    $blur_amount = ($glass_effect_intensity / 100) * 20;
    
    // Glass effect transparency settings based on intensity
    $body_overlay_opacity_start = 0.3 + (($glass_effect_intensity / 100) * 0.5); // Opacity for body overlay gradient start (0.3 - 0.8)
    $body_overlay_opacity_end = 0.2 + (($glass_effect_intensity / 100) * 0.3);   // Opacity for body overlay gradient end (0.2 - 0.5)
    $login_container_opacity = 0.05 + (($glass_effect_intensity / 100) * 0.2);   // Login container background opacity (0.05 - 0.25)
    $login_form_opacity = 0.03 + (($glass_effect_intensity / 100) * 0.1);        // Login form background opacity (0.03 - 0.13)
    ?>
    <style type="text/css">
        html, body {
            max-width: 100%;
            overflow-x: hidden;
        }
        
        body.login {
            background-image: url('<?php echo esc_url($background); ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center; /* Всегда центрирует элементы по вертикали */
            justify-content: <?php echo $form_position === 'center' ? 'center' : ($form_position === 'left' ? 'flex-start' : 'flex-end'); ?>;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
            box-sizing: border-box;
            padding: 0 5%;
        }
        
        body.login::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0, 0, 0, <?php echo $body_overlay_opacity_start; ?>) 0%, rgba(0, 0, 0, <?php echo $body_overlay_opacity_end; ?>) 100%);
            z-index: -1;
            backdrop-filter: blur(<?php echo $blur_amount; ?>px);
            -webkit-backdrop-filter: blur(<?php echo $blur_amount; ?>px);
        }
        
        /* Logo styling */
        body.login #login h1 a {
            background-image: url('<?php echo esc_url($logo); ?>');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center center;
            width: 280px;
            height: 84px;
            margin: 0 auto 30px;
            padding: 0;
            text-indent: -9999px;
            outline: none;
            overflow: hidden;
            display: block;
        }
        
        /* Login container */
        body.login #login {
            padding: 40px;
            background: rgba(255, 255, 255, <?php echo $login_container_opacity; ?>);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 400px;
            width: calc(100% - 80px); /* Учитываем паддинги */
            box-sizing: border-box;
            margin: 0;
            position: relative;
            z-index: 2;
            align-self: center; /* Всегда центрирует форму по вертикали */
            margin: <?php echo $form_position === 'center' ? '0 auto' : '0'; ?>;
            top: 0; /* Убираем смещение вверх/вниз */
            transform: translateY(0); /* Убираем трансформации, которые могут смещать по вертикали */
        }
        
        /* Form styling */
        body.login #login form {
            background: rgba(255, 255, 255, <?php echo $login_form_opacity; ?>);
            border-radius: 12px;
            box-shadow: none;
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 30px;
            margin-top: 20px;
        }
        
        /* Labels */
        body.login label {
            color: <?php echo esc_attr($text_color); ?>;
            font-size: 15px;
            font-weight: 500;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            display: block;
        }
        
        /* Links */
        body.login #nav, body.login #backtoblog {
            text-align: center;
            margin: 24px 0 0;
            padding: 0;
        }
        
        body.login #nav a, body.login #backtoblog a {
            color: <?php echo esc_attr($text_color); ?>;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            padding: 8px 12px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            margin: 0 5px;
            display: inline-block;
        }
        
        body.login #nav a:hover, body.login #backtoblog a:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        /* Messages */
        body.login .message, body.login #login_error {
            background: rgba(255, 255, 255, 0.9);
            border-left: 4px solid #00a0d2;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 12px 16px;
        }
        
        /* Input fields */
        body.login input[type=text], body.login input[type=password], body.login input[type=email] {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 12px 16px;
            height: auto;
            font-size: 15px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 5px;
        }
        
        body.login input[type=text]:focus, body.login input[type=password]:focus, body.login input[type=email]:focus {
            background: #fff;
            box-shadow: 0 0 0 3px rgba(34, 113, 177, 0.3), 0 2px 8px rgba(0, 0, 0, 0.1);
            outline: none;
        }
        
        /* Remember me checkbox */
        body.login .forgetmenot {
            margin-bottom: 16px;
        }
        
        body.login .forgetmenot input[type=checkbox] {
            border-radius: 4px;
            border: 2px solid rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: none;
            width: 18px;
            height: 18px;
        }
        
        /* Submit button */
        body.login .button.button-primary {
            background: linear-gradient(135deg, #2271b1 0%, #135e96 100%);
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(19, 94, 150, 0.3);
            transition: all 0.3s ease;
            padding: 10px 20px;
            height: auto;
            font-size: 16px;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            width: 100%;
            margin-top: 10px;
        }
        
        body.login .button.button-primary:hover {
            background: linear-gradient(135deg, #135e96 0%, #0a4b7c 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(19, 94, 150, 0.4);
        }
        
        body.login .button.button-primary:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(19, 94, 150, 0.4);
        }
        
        /* Hide language switcher */
        body.login #language-switcher,
        body.login .language-switcher {
            display: none !important;
        }
    </style>
    <?php
}

<?php
/**
 * Admin Interface Functions
 * 
 * Handles the admin interface for login page customization
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the admin interface for login page customization
 * 
 * @param array $options Current options
 * @param array $default_backgrounds Default background images
 * @param string $default_logo Default logo path
 */
function render_login_page_admin_interface($options, $default_backgrounds, $default_logo) {
    // Ensure glass effect intensity has a default value if not set
    $glass_effect_intensity = isset($options['glass_effect_intensity']) ? intval($options['glass_effect_intensity']) : 50;
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline" style="display: none;">Login Page Customizer</h1>
        <?php do_action('admin_notices'); ?>
    </div>
    <style>
        /* Styles for page */
        .wrap {
            margin: 0 0 10px 0;
            padding: 0;
            max-width: 100%;
        }
        
        #wpcontent {
            padding-left: 0;
        }
        
        #wpbody-content {
            padding-right: 0;
        }
        
        /* Styles for header */
        .neetrino-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #fff;
            border-bottom: 1px solid #e2e4e7;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .neetrino-header-left h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 500;
            color: #23282d;
        }
        
        /* Styles for preview button */
        .neetrino-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #fff;
            color: #333;
            padding: 12px 32px; /* Increased padding for bigger button */
            border-radius: 6px; /* Slightly increased for cleaner look */
            text-decoration: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08); /* Subtle shadow for cleaner look */
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid #e2e4e7;
            min-width: 220px; /* Make button twice as long */
        }
        
        .neetrino-btn-preview {
            background-color: #2271b1;
            color: #fff;
            border-color: #2271b1;
        }
        
        .neetrino-preview-icon {
            font-size: 22px; /* Larger icon */
            margin-right: 12px;
            color: #fff; /* White icon color for better contrast */
        }
        
        .neetrino-btn:hover {
            background-color: #f5f5f5;
            box-shadow: 0 2px 5px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        /* Styles for content */
        .neetrino-content {
            padding: 0 20px 20px 20px;
        }
        
        /* Styles for cards */
        .neetrino-card {
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            padding: 20px;
            border-top: 4px solid #2271b1;
            transition: all 0.3s ease;
        }
        
        .neetrino-card-header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .neetrino-card-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 500;
            color: #23282d;
        }
        
        /* Styles for background gallery */
        .neetrino-background-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .neetrino-background-option {
            position: relative;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .neetrino-background-preview {
            width: 100%;
            height: 120px;
            background-size: cover;
            background-position: center;
            border-radius: 4px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .neetrino-background-option:hover .neetrino-background-preview {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .neetrino-background-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .neetrino-background-option input[type="radio"]:checked + .neetrino-background-preview {
            border-color: #ff6600;
            box-shadow: 0 0 0 2px #ff6600;
        }
        
        /* Styles for text color */
        .neetrino-color-picker-container {
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .neetrino-color-picker-container input[type="color"] {
            width: 80px;
            height: 60px;
            padding: 0;
            border: 1px solid #e2e4e7;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        /* Styles for logo */
        .neetrino-logo-container {
            margin-top: 15px;
            text-align: center; /* Center the logo container */
        }
        
        .neetrino-logo-preview {
            max-width: 100%;
            height: auto;
            max-height: 60px;
            margin: 10px auto; /* Center the logo with auto margins */
            display: block;
        }
        
        /* Styles for glass effect slider */
        .neetrino-glass-effect-slider {
            width: 100%;
            margin-top: 15px;
        }
        
        .neetrino-glass-effect-slider-container {
            padding: 15px 0;
            text-align: center;
        }
        
        .neetrino-glass-effect-slider input[type="range"] {
            width: 100%;
            height: 6px;
            border-radius: 3px;
            background: #e2e4e7;
            outline: none;
            -webkit-appearance: none;
        }
        
        .neetrino-glass-effect-slider input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #2271b1;
            cursor: pointer;
            border: 2px solid #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        
        .neetrino-glass-effect-value {
            margin-top: 10px;
            font-size: 14px;
            color: #555;
            font-weight: 500;
        }
        
        /* Styles for save button */
        .wp-core-ui .button-primary {
            background: #2271b1;
            border-color: #2271b1;
            color: #fff;
            text-shadow: none;
            box-shadow: none;
            font-weight: 500;
            padding: 8px 16px;
            height: auto;
            line-height: 1.4;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .wp-core-ui .button-primary:hover,
        .wp-core-ui .button-primary:focus {
            background: #135e96;
            border-color: #135e96;
            color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
        }
    </style>
    
    <div class="neetrino-header">
        <div class="neetrino-header-left">
            <h1>Login Page</h1>
        </div>
        <div class="neetrino-header-right">
                <a href="<?php echo esc_url(wp_login_url() . '?preview=true'); ?>" target="_blank" class="neetrino-btn neetrino-btn-preview">
                    <span class="neetrino-preview-icon">👁️</span>
                    <span class="neetrino-preview-text"><?php echo esc_html__('Preview Login Page', 'neetrino'); ?></span>
                </a>
            </div>
        </div>
        
        <div class="neetrino-content">

        <form method="post" action="options.php">
            <?php
            settings_fields('neetrino_login_page_group');
            do_settings_sections('neetrino-login-page');
            ?>
            
            <div class="neetrino-card">
                <div class="neetrino-card-header">
                    <h2>Background Image</h2>
                </div>
                <div class="neetrino-background-gallery">
                    <?php foreach ($default_backgrounds as $index => $bg) : ?>
                        <div class="neetrino-background-option">
                            <label>
                                <input type="radio" name="neetrino_login_page_options[background]" 
                                    value="<?php echo esc_attr($bg); ?>" 
                                    <?php checked($options['background'], $bg); ?>>
                                <div class="neetrino-background-preview" style="background-image: url('<?php echo esc_url($bg); ?>')"></div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Glass Effect Intensity Slider Card -->
            <div class="neetrino-card">
                <div class="neetrino-card-header">
                    <h2>Glass Effect Intensity</h2>
                </div>
                <div class="neetrino-glass-effect-slider">
                    <div class="neetrino-glass-effect-slider-container">
                        <input type="range" id="glass-effect-slider" name="neetrino_login_page_options[glass_effect_intensity]" 
                               min="0" max="100" value="<?php echo esc_attr($glass_effect_intensity); ?>">
                        <div class="neetrino-glass-effect-value">
                            <span id="glass-effect-value"><?php echo esc_html($glass_effect_intensity); ?></span>% Intensity
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="neetrino-admin-options-row" style="display: flex; justify-content: space-between; width: 100%; margin-bottom: 20px;">
                <div class="neetrino-card neetrino-option-card" style="width: 30%;">
                    <div class="neetrino-card-header">
                        <h2>Form Position</h2>
                    </div>
                    <div class="neetrino-position-container">
                        <div style="display: flex; gap: 20px; justify-content: center; margin-top: 15px;">
                            <label id="position-left-label" style="display: block; width: 30px; height: 80px; background-color: <?php echo ($options['form_position'] == 'left') ? '#c8e1ff' : '#e2e4e7'; ?>; border-radius: 4px; cursor: pointer; position: relative; box-shadow: 0 2px 5px rgba(0,0,0,0.1); <?php echo ($options['form_position'] == 'left') ? 'border: 2px solid #2271b1;' : ''; ?>">
                                <input type="radio" name="neetrino_login_page_options[form_position]" value="left" <?php checked($options['form_position'], 'left'); ?> style="opacity: 0; position: absolute;" class="position-radio">
                                <div id="position-left-indicator" style="width: 20px; height: 10px; background-color: <?php echo ($options['form_position'] == 'left') ? '#2271b1' : '#777'; ?>; position: absolute; top: 10px; left: 50%; transform: translateX(-50%); border-radius: 2px;"></div>
                            </label>
                            
                            <label id="position-center-label" style="display: block; width: 30px; height: 80px; background-color: <?php echo ($options['form_position'] == 'center') ? '#c8e1ff' : '#e2e4e7'; ?>; border-radius: 4px; cursor: pointer; position: relative; box-shadow: 0 2px 5px rgba(0,0,0,0.1); <?php echo ($options['form_position'] == 'center') ? 'border: 2px solid #2271b1;' : ''; ?>">
                                <input type="radio" name="neetrino_login_page_options[form_position]" value="center" <?php checked($options['form_position'], 'center'); ?> style="opacity: 0; position: absolute;" class="position-radio">
                                <div id="position-center-indicator" style="width: 20px; height: 10px; background-color: <?php echo ($options['form_position'] == 'center') ? '#2271b1' : '#777'; ?>; position: absolute; top: 35px; left: 50%; transform: translateX(-50%); border-radius: 2px;"></div>
                            </label>
                            
                            <label id="position-right-label" style="display: block; width: 30px; height: 80px; background-color: <?php echo ($options['form_position'] == 'right') ? '#c8e1ff' : '#e2e4e7'; ?>; border-radius: 4px; cursor: pointer; position: relative; box-shadow: 0 2px 5px rgba(0,0,0,0.1); <?php echo ($options['form_position'] == 'right') ? 'border: 2px solid #2271b1;' : ''; ?>">
                                <input type="radio" name="neetrino_login_page_options[form_position]" value="right" <?php checked($options['form_position'], 'right'); ?> style="opacity: 0; position: absolute;" class="position-radio">
                                <div id="position-right-indicator" style="width: 20px; height: 10px; background-color: <?php echo ($options['form_position'] == 'right') ? '#2271b1' : '#777'; ?>; position: absolute; top: 60px; left: 50%; transform: translateX(-50%); border-radius: 2px;"></div>
                            </label>
                        </div>
                        
                        <script type="text/javascript">
                            jQuery(document).ready(function($) {
                                // Position radio buttons click handler
                                $('.position-radio').on('change', function() {
                                    // Reset styles for all buttons
                                    $('#position-left-label, #position-center-label, #position-right-label').css({
                                        'background-color': '#e2e4e7',
                                        'border': 'none'
                                    });
                                    $('#position-left-indicator, #position-center-indicator, #position-right-indicator').css({
                                        'background-color': '#777'
                                    });
                                    
                                    // Set style for selected button
                                    var selectedValue = $(this).val();
                                    $('#position-'+selectedValue+'-label').css({
                                        'background-color': '#c8e1ff',
                                        'border': '2px solid #2271b1'
                                    });
                                    $('#position-'+selectedValue+'-indicator').css({
                                        'background-color': '#2271b1'
                                    });
                                });
                            });
                        </script>
                    </div>
                </div>
                
                <div class="neetrino-card neetrino-option-card" style="width: 30%;">
                    <div class="neetrino-card-header">
                        <h2>Text Color</h2>
                    </div>
                    <div class="neetrino-color-picker-container">
                        <input type="color" name="neetrino_login_page_options[text_color]" 
                            value="<?php echo esc_attr($options['text_color']); ?>">
                    </div>
                </div>
                
                <div class="neetrino-card neetrino-option-card" style="width: 30%;">
                    <div class="neetrino-card-header">
                        <h2>Logo</h2>
                    </div>
                    <div class="neetrino-logo-container">
                        <img src="<?php echo esc_url($default_logo); ?>" alt="Neetrino Logo" class="neetrino-logo-preview">
                    </div>
                </div>
            </div>
            
            <div class="neetrino-card-footer">
                <?php submit_button('Save Settings', 'primary', 'submit', false); ?>
            </div>
        </form>
        
        </div><!-- .neetrino-content -->
    </div><!-- .wrap -->
    
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Glass effect slider
            $('#glass-effect-slider').on('input', function() {
                $('#glass-effect-value').text($(this).val());
            });
            
            // Preview button
            var previewButton = $('.neetrino-btn-preview');
            
            // Preview button click handler
            previewButton.on('click', function(e) {
                // Get current form values
                var background = $('input[name="neetrino_login_page_options[background]"]:checked').val();
                var textColor = $('input[name="neetrino_login_page_options[text_color]"]').val();
                var logo = '<?php echo esc_url($options["logo"]); ?>';
                var position = $('input[name="neetrino_login_page_options[form_position]"]:checked').val();
                var glassEffect = $('#glass-effect-slider').val();
                
                // Create URL with preview parameters
                var previewUrl = '<?php echo esc_url(wp_login_url()); ?>' + 
                                '?preview=true' + 
                                '&bg=' + encodeURIComponent(background) + 
                                '&color=' + encodeURIComponent(textColor) + 
                                '&logo=' + encodeURIComponent(logo) + 
                                '&position=' + encodeURIComponent(position) + 
                                '&glass_effect=' + encodeURIComponent(glassEffect);
                
                // Update href for button
                $(this).attr('href', previewUrl);
            });
        });
    </script>
    <?php
}

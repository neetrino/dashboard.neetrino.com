<?php
/**
 * Simple Maintenance Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Maintenance</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background-color: #f1f1f1;
            background-image: url('<?php echo plugin_dir_url(dirname(__FILE__)) . 'Image/maintnance.webp'; ?>');
            background-size: cover;
            background-position: center;
            color: #444;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            z-index: 0;
        }
        .maintenance-box {
            background: rgba(40, 40, 30, 0.8);
            max-width: 500px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            text-align: center;
            border-radius: 16px;
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 193, 7, 0.4);
            position: relative;
            z-index: 1;
            color: #ffffff;
        }
        h1 {
            margin-top: 0;
            color: rgba(255, 193, 7, 1);
        }
        a {
            color: rgba(255, 193, 7, 0.9);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        a:hover {
            color: rgba(255, 213, 79, 1);
        }
        .password-access-form input:focus {
            outline: none;
            border-color: rgba(255, 193, 7, 0.8);
            box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.2);
        }
        .password-access-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
        }
        .password-access-form button:active {
            transform: translateY(0);
        }
        @media (max-width: 600px) {
            .password-access-form > form > div {
                flex-direction: column;
                gap: 15px;
            }
            .password-access-form input {
                /* min-width: auto; */
                width: 50%;
            }
        }
    </style>
</head>
<body>
    <div class="maintenance-box">
        <div class="logo-container">
            <img src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'Image/logos/Neetrino-Logo.png'; ?>" alt="Neetrino Logo" style="max-width: 200px; margin-bottom: 20px;">
        </div>
        <h1>Site Under Maintenance</h1>
        <p>We are currently performing scheduled maintenance. Please check back soon.</p>
        
        <?php
        // Show password form if password access is enabled
        $options = get_option('neetrino_maintenance_mode', []);
        $password_access = isset($options['password_access']) ? $options['password_access'] : false;
        
        if ($password_access && !empty($options['access_password'])) :
        ?>
        <div class="password-access-form">
            <p style="margin-top: 30px; color: rgba(255, 193, 7, 0.9); font-size: 14px;">Have access? Enter password below:</p>
            <form method="post" style="margin-top: 15px;">
                <?php wp_nonce_field('neetrino_maintenance_password_check', 'neetrino_password_nonce'); ?>
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; justify-content: center;">
                    <input type="password" name="neetrino_maintenance_password" placeholder="Enter password" 
                           style="padding: 12px 16px; border: 2px solid rgba(255, 193, 7, 0.5); border-radius: 8px; 
                                  background: rgba(40, 40, 30, 0.9); color: #ffffff; font-size: 14px; min-width: 200px;
                                  backdrop-filter: blur(8px);" required>
                    <button type="submit" style="padding: 12px 20px; background: linear-gradient(145deg, rgba(255, 193, 7, 1) 0%, rgba(255, 213, 79, 1) 100%); 
                                                  color: #000; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;
                                                  transition: all 0.3s ease; font-size: 14px;">
                        Access Site
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <p>Powered by <a href="https://neetrino.com" target="_blank">Neetrino</a></p>
    </div>
    
    <script>
    // Auto-refresh check every 30 seconds to detect when maintenance mode is disabled
    (function() {
        var checkInterval = 30000; // 30 seconds
        var maxChecks = 120; // Stop after 1 hour (120 * 30s = 3600s)
        var checkCount = 0;
        
        function checkMaintenanceStatus() {
            if (checkCount >= maxChecks) {
                return; // Stop checking after max attempts
            }
            
            checkCount++;
            
            // Try AJAX endpoint first (more efficient)
            var xhr = new XMLHttpRequest();
            var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var params = 'action=check_maintenance_status&_=' + Date.now() + '&rand=' + Math.random();
            
            xhr.open('POST', ajaxUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
            xhr.setRequestHeader('Pragma', 'no-cache');
            xhr.setRequestHeader('Expires', '0');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.mode === 'open') {
                                // Site is back online, reload
                                window.location.reload(true);
                                return;
                            }
                        } catch (e) {
                            // If AJAX fails, fall back to HEAD request
                            fallbackCheck();
                            return;
                        }
                    } else {
                        // If AJAX fails, fall back to HEAD request
                        fallbackCheck();
                        return;
                    }
                    
                    // Schedule next check
                    setTimeout(checkMaintenanceStatus, checkInterval);
                }
            };
            
            xhr.onerror = function() {
                // On error, fall back to HEAD request
                fallbackCheck();
            };
            
            try {
                xhr.send(params);
            } catch (e) {
                // On error, fall back to HEAD request
                fallbackCheck();
            }
        }
        
        function fallbackCheck() {
            // Fallback: use HEAD request to current page
            var xhr2 = new XMLHttpRequest();
            var url = window.location.href + (window.location.href.indexOf('?') > -1 ? '&' : '?') + 
                      'maintenance_check=' + Date.now() + '&rand=' + Math.random();
            
            xhr2.open('HEAD', url, true);
            xhr2.setRequestHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
            xhr2.setRequestHeader('Pragma', 'no-cache');
            xhr2.setRequestHeader('Expires', '0');
            
            xhr2.onreadystatechange = function() {
                if (xhr2.readyState === 4) {
                    // If we get a different response (not 503), the site is likely back online
                    if (xhr2.status !== 503) {
                        window.location.reload(true);
                    } else {
                        // Schedule next check
                        setTimeout(checkMaintenanceStatus, checkInterval);
                    }
                }
            };
            
            xhr2.onerror = function() {
                // Schedule next check
                setTimeout(checkMaintenanceStatus, checkInterval);
            };
            
            try {
                xhr2.send();
            } catch (e) {
                // Schedule next check
                setTimeout(checkMaintenanceStatus, checkInterval);
            }
        }
        
        // Start checking after initial delay
        setTimeout(checkMaintenanceStatus, checkInterval);
    })();
    </script>
</body>
</html>

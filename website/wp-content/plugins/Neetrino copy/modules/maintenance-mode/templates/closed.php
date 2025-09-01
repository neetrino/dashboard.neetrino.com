<?php
/**
 * Simple Closed Site Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Closed</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background-color: #23282d;
            background-image: url('<?php echo plugin_dir_url(dirname(__FILE__)) . 'Image/cloused.webp'; ?>');
            background-size: cover;
            background-position: center;
            color: #fff;
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
            background: rgba(220, 53, 69, 0.6);
            backdrop-filter: blur(16px);
            max-width: 500px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            text-align: center;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
        }
        h1 {
            margin-top: 0;
        }
        a {
            color: #fff;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="maintenance-box">
        <div class="logo-container">
            <img src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'Image/logos/Neetrino-Logo.png'; ?>" alt="Neetrino Logo" style="max-width: 200px; margin-bottom: 20px;">
        </div>
        <h1>Site Closed</h1>
        <p>This website is currently not available. Please contact the site administrator for more information.</p>
        <p>Powered by <a href="https://neetrino.com" target="_blank">Neetrino</a></p>
    </div>
</body>
</html>

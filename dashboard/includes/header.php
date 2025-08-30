<?php
/**
 * Neetrino Control Dashboard - Современный заголовок с подключением ресурсов
 */

// Версия и метаданные из version.json (единый источник)
$asset_path = 'assets/';
$version = '0.0.0';
$display_version = '';
$short_version = '';
$versionMeta = null;
try {
    $versionFile = __DIR__ . '/../version.json';
    if (file_exists($versionFile)) {
        $json = file_get_contents($versionFile);
        $data = json_decode($json, true);
        if (is_array($data)) {
            $versionMeta = $data;
            $version = isset($data['version']) ? (string)$data['version'] : $version;
            $display_version = isset($data['display_name']) ? (string)$data['display_name'] : '';
            $short_version = isset($data['short_version']) ? (string)$data['short_version'] : '';
        }
    }
} catch (Throwable $e) {
    // fallback silently
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neetrino Control Dashboard <?= htmlspecialchars($short_version ?: ('v' . explode('.', $version)[0])) ?> - Современное управление сайтами</title>
    
    <!-- External CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $asset_path ?>css/main.css?v=<?= urlencode($version) ?>">
    
    <!-- External JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        window.NEETRINO_DASHBOARD_VERSION = {
            version: <?= json_encode($version) ?>,
            display: <?= json_encode($display_version ?: ('v' . (isset($versionMeta['version']) ? substr($versionMeta['version'], 0, 3) : $version))) ?>,
            short: <?= json_encode($short_version ?: ('v' . explode('.', $version)[0])) ?>
        };
    </script>
    <script src="<?= $asset_path ?>js/config.js?v=<?= urlencode($version) ?>"></script>
    <script src="<?= $asset_path ?>js/templates_fixed.js?v=<?= urlencode($version) ?>"></script>
    <script src="<?= $asset_path ?>js/dashboard.js?v=<?= urlencode($version) ?>"></script>
    
    <!-- PWA Support -->
    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    
    <!-- Preload критичных ресурсов -->
    <link rel="preload" href="<?= $asset_path ?>css/main.css?v=<?= $version ?>" as="style">
    <link rel="preload" href="<?= $asset_path ?>js/dashboard.js?v=<?= $version ?>" as="script">
    
    <!-- SEO -->
    <meta name="description" content="Neetrino Control Dashboard <?= htmlspecialchars($short_version ?: ('v' . explode('.', $version)[0])) ?> - современная система управления WordPress сайтами с минималистичным дизайном">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎛️</text></svg>">
    
    <!-- Дополнительные мета-теги для современного UI -->
    <meta name="apple-mobile-web-app-title" content="Neetrino Dashboard">
    <meta name="application-name" content="Neetrino Dashboard">
    <meta name="msapplication-TileColor" content="#3b82f6">
</head>

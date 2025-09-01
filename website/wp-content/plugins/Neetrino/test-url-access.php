<?php
/**
 * Тестовый файл для проверки доступности URL файла обновления
 * Запускать через браузер: /wp-content/plugins/Neetrino/test-url-access.php
 */

// Проверяем, что WordPress загружен
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

echo "<h2>Тест доступности файла обновления</h2>";

$url = 'http://costom-scripts.neetrino.net/Plugin/Neetrino.zip';
echo "<p>Проверяем URL: <strong>$url</strong></p>";

// Тест 1: wp_remote_head
echo "<h3>1. Тест wp_remote_head()</h3>";
$response = wp_remote_head($url, [
    'timeout' => 10,
    'sslverify' => false
]);

if (is_wp_error($response)) {
    echo "<p style='color: red;'>❌ Ошибка: " . $response->get_error_message() . "</p>";
} else {
    $code = wp_remote_retrieve_response_code($response);
    $headers = wp_remote_retrieve_headers($response);
    
    echo "<p style='color: " . ($code === 200 ? 'green' : 'red') . ";'>";
    echo ($code === 200 ? '✅' : '❌') . " HTTP код: $code</p>";
    
    if ($code === 200) {
        echo "<p>✅ Файл доступен!</p>";
        echo "<p>Размер: " . $headers['content-length'] . " байт</p>";
        echo "<p>Тип: " . $headers['content-type'] . "</p>";
    } else {
        echo "<p>❌ Файл недоступен</p>";
    }
}

// Тест 2: wp_remote_get (попытка скачать)
echo "<h3>2. Тест wp_remote_get() (скачивание)</h3>";
$response = wp_remote_get($url, [
    'timeout' => 30,
    'sslverify' => false
]);

if (is_wp_error($response)) {
    echo "<p style='color: red;'>❌ Ошибка скачивания: " . $response->get_error_message() . "</p>";
} else {
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    echo "<p style='color: " . ($code === 200 ? 'green' : 'red') . ";'>";
    echo ($code === 200 ? '✅' : '❌') . " HTTP код: $code</p>";
    
    if ($code === 200) {
        echo "<p>✅ Файл успешно скачан!</p>";
        echo "<p>Размер скачанного файла: " . strlen($body) . " байт</p>";
        
        // Проверяем, что это ZIP файл
        if (substr($body, 0, 4) === 'PK\x03\x04') {
            echo "<p>✅ Это действительно ZIP файл</p>";
        } else {
            echo "<p>❌ Это НЕ ZIP файл (первые 4 байта: " . bin2hex(substr($body, 0, 4)) . ")</p>";
        }
    } else {
        echo "<p>❌ Не удалось скачать файл</p>";
    }
}

// Тест 3: Проверка через cURL (если доступен)
echo "<h3>3. Тест через cURL</h3>";
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "<p style='color: red;'>❌ cURL ошибка: $error</p>";
    } else {
        echo "<p style='color: " . ($http_code === 200 ? 'green' : 'red') . ";'>";
        echo ($http_code === 200 ? '✅' : '❌') . " cURL HTTP код: $http_code</p>";
    }
} else {
    echo "<p>cURL недоступен</p>";
}

echo "<hr>";
echo "<p><strong>Вывод:</strong> Если все тесты показывают ошибки, то файл обновления недоступен по указанному URL.</p>";
echo "<p>Нужно проверить:</p>";
echo "<ul>";
echo "<li>Правильность URL</li>";
echo "<li>Доступность сервера</li>";
echo "<li>Наличие файла на сервере</li>";
echo "<li>Настройки CORS и доступа</li>";
echo "</ul>";
?>

<?php
/**
 * Проверка работоспособности Dashboard на Beget
 */

// Читаем версию из version.json
$__ver = '';
try {
    $vf = __DIR__ . '/version.json';
    if (file_exists($vf)) {
        $vdata = json_decode(file_get_contents($vf), true);
        if (is_array($vdata)) {
            $__ver = isset($vdata['display_name']) ? (string)$vdata['display_name'] : (isset($vdata['short_version']) ? (string)$vdata['short_version'] : '');
        }
    }
} catch (Throwable $e) {}

echo "<h1>🚀 Neetrino Dashboard — Проверка системы <small style='font-weight:normal;color:#6b7280'>" . htmlspecialchars($__ver) . "</small></h1>";

// Проверка PHP версии
echo "<h3>📋 Информация о системе:</h3>";
echo "<p><strong>PHP версия:</strong> " . phpversion() . "</p>";
echo "<p><strong>Сервер:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p><strong>Домен:</strong> " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<p><strong>Время сервера:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Проверка расширений
echo "<h3>🔧 Проверка расширений PHP:</h3>";
$required_extensions = ['pdo', 'pdo_sqlite', 'json', 'curl'];
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? '✅' : '❌';
    echo "<p>$status <strong>$ext:</strong> " . (extension_loaded($ext) ? 'Установлено' : 'НЕ УСТАНОВЛЕНО') . "</p>";
}

// Проверка прав на запись
echo "<h3>📁 Проверка прав доступа:</h3>";
$test_file = __DIR__ . '/test_write.txt';
if (file_put_contents($test_file, 'test')) {
    echo "<p>✅ <strong>Запись файлов:</strong> Работает</p>";
    unlink($test_file);
} else {
    echo "<p>❌ <strong>Запись файлов:</strong> НЕ РАБОТАЕТ</p>";
}

// Проверка MySQL базы
echo "<h3>🗄️ Проверка базы данных:</h3>";
try {
    define('NEETRINO_DASHBOARD', true);
    require_once 'config.php';
    echo "<p>✅ <strong>Подключение к MySQL:</strong> Успешно</p>";
    
    // Проверяем таблицы
    $tables = $pdo->query("SHOW TABLES")->fetchAll();
    echo "<p><strong>Таблицы в БД:</strong></p><ul>";
    foreach ($tables as $table) {
        echo "<li>" . $table['name'] . "</li>";
    }
    echo "</ul>";
    
    // Подсчитываем сайты
    $count = $pdo->query("SELECT COUNT(*) as count FROM sites")->fetch();
    echo "<p><strong>Количество сайтов:</strong> " . $count['count'] . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ <strong>Ошибка БД:</strong> " . $e->getMessage() . "</p>";
}

// Проверка API
echo "<h3>🔌 Проверка API:</h3>";
$api_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api.php?action=get_sites';
echo "<p><strong>API URL:</strong> <a href='$api_url' target='_blank'>$api_url</a></p>";

// Проверка cURL
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        echo "<p>✅ <strong>API тест:</strong> Работает (HTTP $http_code)</p>";
        $data = json_decode($response, true);
        if ($data && isset($data['success'])) {
            echo "<p>✅ <strong>JSON ответ:</strong> Корректный</p>";
        } else {
            echo "<p>⚠️ <strong>JSON ответ:</strong> Некорректный</p>";
        }
    } else {
        echo "<p>❌ <strong>API тест:</strong> Ошибка HTTP $http_code</p>";
    }
} else {
    echo "<p>❌ <strong>cURL:</strong> Не доступен</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>🎛️ Перейти к Dashboard</a></p>";
echo "<p><em>После проверки можно удалить этот файл для безопасности</em></p>";
?>

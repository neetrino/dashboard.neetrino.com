<?php
/**
 * Тестовый файл для проверки системы обновления Neetrino
 * УДАЛИТЬ ПОСЛЕ ТЕСТИРОВАНИЯ
 */

if (!defined('ABSPATH')) {
    exit;
}

// Проверяем доступность файла обновления
function test_update_availability() {
    $remote_url = 'http://costom-scripts.neetrino.net/Plugin/Neetrino.zip';
    
    $response = wp_remote_head($remote_url, [
        'timeout' => 5,
        'sslverify' => false
    ]);
    
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        echo "✅ Файл обновления доступен\n";
        return true;
    } else {
        echo "❌ Файл обновления недоступен\n";
        return false;
    }
}

// Проверяем текущую версию плагина
function test_current_version() {
    $plugin_data = get_plugin_data(NEETRINO_PLUGIN_FILE);
    echo "📦 Текущая версия: " . $plugin_data['Version'] . "\n";
    return $plugin_data['Version'];
}

// Тестируем систему обновления
echo "🧪 Тестирование системы обновления Neetrino\n";
echo "==========================================\n\n";

echo "1. Проверка доступности файла обновления:\n";
$available = test_update_availability();

echo "\n2. Информация о текущем плагине:\n";
$current_version = test_current_version();

echo "\n3. Проверка методов обновления:\n";
if (class_exists('Neetrino_Plugin_Updater')) {
    echo "✅ Класс Neetrino_Plugin_Updater найден\n";
    
    $updater = new Neetrino_Plugin_Updater();
    
    if (method_exists($updater, 'check_file_availability')) {
        echo "✅ Метод check_file_availability найден\n";
        $result = $updater->check_file_availability();
        echo "   Результат: " . ($result['available'] ? 'Доступно' : 'Недоступно') . "\n";
    } else {
        echo "❌ Метод check_file_availability не найден\n";
    }
    
    if (method_exists($updater, 'perform_direct_update')) {
        echo "✅ Метод perform_direct_update найден\n";
    } else {
        echo "❌ Метод perform_direct_update не найден\n";
    }
    
} else {
    echo "❌ Класс Neetrino_Plugin_Updater не найден\n";
}

echo "\n4. Проверка интерфейса:\n";
if (class_exists('Neetrino_Admin')) {
    echo "✅ Класс Neetrino_Admin найден\n";
    
    $admin = new Neetrino_Admin();
    
    if (method_exists($admin, 'handle_direct_update')) {
        echo "✅ Метод handle_direct_update найден\n";
    } else {
        echo "❌ Метод handle_direct_update не найден\n";
    }
    
} else {
    echo "❌ Класс Neetrino_Admin не найден\n";
}

echo "\n🎯 Тестирование завершено!\n";
echo "Теперь можно нажать кнопку 'Update Now' для прямого обновления.\n";

<?php
/**
 * Скрипт восстановления сайтов из корзины
 */

define('NEETRINO_DASHBOARD', true);
require_once 'config.php';

if (!isset($pdo)) {
    die("Ошибка подключения к базе данных\n");
}

echo "=== ВОССТАНОВЛЕНИЕ САЙТОВ ИЗ КОРЗИНЫ ===\n\n";

// Показываем сайты в корзине
$stmt = $pdo->query("SELECT * FROM trash ORDER BY deleted_at DESC");
$trash_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($trash_items)) {
    echo "Корзина пуста\n";
    exit;
}

echo "Сайты в корзине:\n";
echo str_repeat("-", 60) . "\n";

foreach ($trash_items as $index => $item) {
    echo ($index + 1) . ". {$item['site_name']} - {$item['site_url']}\n";
    echo "   Удален: {$item['deleted_at']} ({$item['deleted_reason']})\n";
    echo "\n";
}

// Если запускается через веб-браузер
if (isset($_SERVER['HTTP_HOST'])) {
    echo "<br><br><strong>Для восстановления сайтов:</strong><br>";
    echo "1. Откройте панель управления<br>";
    echo "2. Перейдите в раздел 'Настройки'<br>";
    echo "3. Нажмите 'Корзина'<br>";
    echo "4. Восстановите нужные сайты<br><br>";
    
    echo "<a href='index.php' style='background: #3B82F6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Открыть панель управления</a>";
    exit;
}

// Автоматическое восстановление для консоли
echo "Хотите восстановить все сайты автоматически? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim($line) !== 'y') {
    echo "Отменено\n";
    exit;
}

echo "\nВосстанавливаем сайты...\n";

foreach ($trash_items as $item) {
    try {
        // Проверяем, нет ли уже такого сайта
        $stmt = $pdo->prepare("SELECT id FROM sites WHERE site_url = ?");
        $stmt->execute([$item['site_url']]);
        
        if ($stmt->fetch()) {
            echo "⚠️  {$item['site_name']} - уже существует, пропускаем\n";
            continue;
        }
        
        // Восстанавливаем сайт
        $stmt = $pdo->prepare("
            INSERT INTO sites (site_url, site_name, admin_email, status, date_added, last_seen) 
            VALUES (?, ?, ?, 'offline', NOW(), NULL)
        ");
        
        $stmt->execute([
            $item['site_url'],
            $item['site_name'],
            'unknown@domain.com' // Временный email, нужно будет обновить
        ]);
        
        // Удаляем из корзины
        $stmt = $pdo->prepare("DELETE FROM trash WHERE id = ?");
        $stmt->execute([$item['id']]);
        
        echo "✅ {$item['site_name']} - восстановлен\n";
        
    } catch (Exception $e) {
        echo "❌ {$item['site_name']} - ошибка: {$e->getMessage()}\n";
    }
}

echo "\n=== ГОТОВО ===\n";
echo "Теперь попробуйте заново установить плагины на ваши сайты\n";

?>

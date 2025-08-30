<?php
/**
 * Скрипт для создания таблицы пользователей и первого админа
 */

define('NEETRINO_DASHBOARD', true);
require_once 'config.php';

echo "<h1>🔧 Настройка системы авторизации</h1>";

try {
    // Создаем таблицу admin_users
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS admin_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        email TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME,
        failed_attempts INTEGER DEFAULT 0,
        locked_until DATETIME NULL
    )";
    
    $pdo->exec($create_table_sql);
    echo "<p>✅ Таблица admin_users создана</p>";
    
    // Проверяем есть ли уже пользователь
    $check_user = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
    
    if ($check_user == 0) {
        // Создаем первого админа
        $username = 'admin';
        $password = 'admin123'; // Временный пароль
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, email) VALUES (?, ?, ?)");
        $stmt->execute([$username, $password_hash, 'admin@localhost']);
        
        echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 10px 0;'>";
        echo "<h3>🎉 Первый администратор создан!</h3>";
        echo "<p><strong>Логин:</strong> admin</p>";
        echo "<p><strong>Пароль:</strong> admin123</p>";
        echo "<p style='color: #ff6b6b;'><strong>⚠️ ОБЯЗАТЕЛЬНО СМЕНИТЕ ПАРОЛЬ ПОСЛЕ ПЕРВОГО ВХОДА!</strong></p>";
        echo "</div>";
    } else {
        echo "<p>✅ Пользователь уже существует (пропускаем создание)</p>";
    }
    
    echo "<p><a href='login.php' style='background: #4caf50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔐 Войти в систему</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Ошибка: " . $e->getMessage() . "</p>";
}
?>

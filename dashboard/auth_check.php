<?php
/**
 * Проверка авторизации для всех защищенных страниц
 */

// Запускаем сессию ДО всего остального
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Проверяем установку системы
if (!file_exists(__DIR__ . '/.installed')) {
    header('Location: install.php');
    exit;
}

// Подключаем единую конфигурацию
if (!defined('NEETRINO_DASHBOARD')) {
    define('NEETRINO_DASHBOARD', true);
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Auth.php';

// Проверяем подключение к базе данных
if (!isset($pdo)) {
    // Перенаправляем на установщик если нет подключения
    header('Location: install.php');
    exit;
}

$auth = new Auth($pdo);

// Проверяем авторизацию
if (!$auth->isLoggedIn()) {
    // Если это AJAX запрос
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized', 'redirect' => 'login.php']);
        exit;
    }
    
    // Обычное перенаправление
    header('Location: login.php');
    exit;
}

// Получаем данные текущего пользователя
$current_user = $auth->getCurrentUser();

// Проверяем активность пользователя (учитываем разные типы данных)
if (!$current_user || !$current_user['is_active'] || $current_user['is_active'] == '0' || $current_user['is_active'] === false) {
    session_destroy();
    header('Location: login.php?error=account_disabled');
    exit;
}
?>

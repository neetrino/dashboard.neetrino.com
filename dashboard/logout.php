<?php
/**
 * Выход из системы
 */

define('NEETRINO_DASHBOARD', true);
require_once 'config.php';
require_once 'includes/Auth.php';

$auth = new Auth($pdo);
$auth->logout();

// Перенаправляем на страницу входа
header('Location: login.php?message=logout');
exit;
?>

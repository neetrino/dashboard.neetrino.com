<?php
/**
 * Neetrino Control Dashboard - Главная страница
 * @package NeetrinoDashboard
 * @author Neetrino Team
 */

// Проверка установки системы
if (!file_exists(__DIR__ . '/.installed')) {
    header('Location: install.php');
    exit;
}

// Подключаем единую конфигурацию
define('NEETRINO_DASHBOARD', true);
require_once __DIR__ . '/config.php';

// Проверка авторизации
require_once 'auth_check.php';

// Проверка безопасности
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

// Проверка работоспособности
$health_check = true;
$health_messages = [];

// Проверяем подключение к базе данных
if (!isset($pdo)) {
    $health_check = false;
    $health_messages[] = 'Нет подключения к базе данных MySQL';
}

// Проверяем наличие таблиц
if (isset($db_manager)) {
    $table_check = $db_manager->checkTables();
    if (!$table_check['success']) {
        $health_check = false;
        $health_messages[] = 'Не все таблицы базы данных существуют';
    }
}

// Если проблемы со здоровьем - показываем ошибку без header.php
if (!$health_check) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ошибка - Neetrino Dashboard</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-red-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-red-500 text-white rounded-lg p-6 text-center">
            <h1 class="text-2xl font-bold mb-4">❌ Ошибка инициализации</h1>
            <div class="mb-4">
                <?php foreach ($health_messages as $message): ?>
                <p class="mb-2"><?= htmlspecialchars($message) ?></p>
                <?php endforeach; ?>
            </div>
            <div class="space-x-4">
                <a href="health_check.php" class="bg-red-500 bg-opacity-20 text-red-600 border-2 border-red-500 px-4 py-2 rounded hover:bg-red-500 hover:bg-opacity-30 transition-all duration-200">
                    🔧 Диагностика системы
                </a>
                <a href="install.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors">
                    🔄 Переустановить
                </a>
            </div>
        </div>
    </div>
    </body>
    </html>
    <?php exit; 
}

// Проверяем наличие файлов шаблонов
$header_exists = file_exists(ABSPATH . 'includes/header.php');
$template_exists = file_exists(ABSPATH . 'templates/main.php');
$footer_exists = file_exists(ABSPATH . 'includes/footer.php');

// Если файлы шаблонов отсутствуют - показываем простую версию
if (!$header_exists || !$template_exists || !$footer_exists) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Neetrino Control Dashboard</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100">
        <div class="min-h-screen">
            <div class="bg-blue-600 text-white p-4">
                <div class="container mx-auto flex justify-between items-center">
                    <h1 class="text-2xl font-bold">🎛️ Neetrino Dashboard</h1>
                    <div class="space-x-4">
                        <span>Добро пожаловать, <?= htmlspecialchars($current_user['username']) ?></span>
                        <a href="profile.php" class="bg-blue-500 px-4 py-2 rounded">👤 Профиль</a>
                        <a href="logout.php" class="bg-red-500 px-4 py-2 rounded">🚪 Выход</a>
                    </div>
                </div>
            </div>
            
            <div class="container mx-auto p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-xl font-bold mb-4">👤 Управление профилем</h3>
                        <p class="text-gray-600 mb-4">Настройте ваш аккаунт и безопасность</p>
                        <a href="profile.php" class="bg-blue-500 text-white px-4 py-2 rounded">Открыть профиль</a>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-xl font-bold mb-4">🌐 Управление сайтами</h3>
                        <p class="text-gray-600 mb-4">Добавляйте и управляйте сайтами</p>
                        <button onclick="alert('Функция в разработке')" class="bg-gray-500 text-white px-4 py-2 rounded">Скоро</button>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-xl font-bold mb-4">🔧 Диагностика</h3>
                        <p class="text-gray-600 mb-4">Проверка системы и настроек</p>
                        <a href="check_database.php" class="bg-green-500 text-white px-4 py-2 rounded">Проверить БД</a>
                    </div>
                </div>
                
                <div class="mt-8 bg-white p-6 rounded-lg shadow">
                    <h3 class="text-xl font-bold mb-4">📊 Информация</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600"><?= htmlspecialchars($current_user['id']) ?></div>
                            <div class="text-gray-600">ID пользователя</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600"><?= date('H:i') ?></div>
                            <div class="text-gray-600">Время сервера</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600">PHP <?= substr(phpversion(), 0, 3) ?></div>
                            <div class="text-gray-600">Версия</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-orange-600">✅</div>
                            <div class="text-gray-600">Статус</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php exit;
}

// Если все файлы есть - используем оригинальные шаблоны
?>

<?php include ABSPATH . 'includes/header.php'; ?>

<?php include ABSPATH . 'templates/main.php'; ?>

<?php include ABSPATH . 'includes/footer.php'; ?>

<?php
/**
 * Установщик Neetrino Dashboard - MySQL версия
 * @package NeetrinoDashboard
 * @author Neetrino Team
 */

session_start();

// Централизованная версия Dashboard из version.json
$dashboard_version = '';
$dashboard_display = '';
try {
    $vf = __DIR__ . '/version.json';
    if (file_exists($vf)) {
        $vdata = json_decode(file_get_contents($vf), true);
        if (is_array($vdata)) {
            $dashboard_version = isset($vdata['short_version']) ? (string)$vdata['short_version'] : ((isset($vdata['version']) && $vdata['version']) ? ('v' . $vdata['version']) : '');
            $dashboard_display = isset($vdata['display_name']) ? (string)$vdata['display_name'] : $dashboard_version;
        }
    }
} catch (Throwable $e) {
    // no-op
}

// Проверяем, не установлена ли уже система
if (file_exists(__DIR__ . '/.installed') && !isset($_GET['force'])) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Система уже установлена</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-yellow-100 min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg max-w-md">
            <div class="text-center">
                <div class="text-yellow-500 text-6xl mb-4">⚠️</div>
                <h1 class="text-2xl font-bold text-yellow-600 mb-4">Система уже установлена</h1>
                <p class="text-gray-600 mb-6">Neetrino Dashboard уже установлен и готов к работе.</p>
                <div class="space-y-3">
                    <a href="index.php" class="block bg-blue-500 text-white px-6 py-3 rounded hover:bg-blue-600">
                        🏠 Перейти к системе
                    </a>
                    <a href="?force=1" class="block bg-red-500 text-white px-6 py-3 rounded hover:bg-red-600">
                        🔄 Переустановить (сброс данных)
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Определяем окружение для предзаполнения настроек
$is_local = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
            strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
            strpos($_SERVER['HTTP_HOST'], '.local') !== false ||
            strpos($_SERVER['HTTP_HOST'], '.test') !== false);

// Обработка POST запроса
$installation_result = null;
$step = $_GET['step'] ?? 'welcome';

if ($_POST && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'test_db':
            $test_result = testDatabaseConnection($_POST);
            break;
            
        case 'install':
            $installation_result = performInstallation($_POST);
            if ($installation_result['success']) {
                $step = 'success';
            }
            break;
    }
}

/**
 * Тестирование подключения к базе данных
 */
function testDatabaseConnection($data) {
    try {
        $dsn = "mysql:host={$data['db_host']};port={$data['db_port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $data['db_user'], $data['db_password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        
        // Проверяем существование базы данных
        $stmt = $pdo->prepare("SHOW DATABASES LIKE ?");
        $stmt->execute([$data['db_name']]);
        $db_exists = $stmt->fetch();
        
        if (!$db_exists) {
            // Пытаемся создать базу данных
            $pdo->exec("CREATE DATABASE `{$data['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $message = "База данных '{$data['db_name']}' создана успешно";
        } else {
            $message = "Подключение успешно. База данных '{$data['db_name']}' существует";
        }
        
        return ['success' => true, 'message' => $message];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Выполнение установки
 */
function performInstallation($data) {
    try {
        // 0. Удаляем файл предыдущей установки если есть
        if (file_exists(__DIR__ . '/.installed')) {
            unlink(__DIR__ . '/.installed');
        }
        if (file_exists(__DIR__ . '/db_config.php')) {
            unlink(__DIR__ . '/db_config.php');
        }
        // 1. Создаем подключение к базе данных
        $dsn = "mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $data['db_user'], $data['db_password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // 1.5. Очищаем базу данных от предыдущих установок (если есть)
        $tables_to_drop = ['security_logs', 'rate_limits', 'trash', 'sites', 'admin_users', 'system_settings'];
        foreach ($tables_to_drop as $table) {
            try {
                $pdo->exec("DROP TABLE IF EXISTS $table");
            } catch (Exception $e) {
                // Игнорируем ошибки удаления - таблицы могут не существовать
            }
        }
        
        // 2. Выполняем SQL схему
        $schema_file = __DIR__ . '/database_schema_simple.sql';
        if (!file_exists($schema_file)) {
            throw new Exception('Файл схемы базы данных не найден');
        }
        
        $sql = file_get_contents($schema_file);
        
        // Удаляем комментарии
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Разбиваем на отдельные запросы по ';'
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        // 3. Создаем единый файл конфигурации
        $db_config = [
            'host' => $data['db_host'],
            'port' => $data['db_port'],
            'database' => $data['db_name'],
            'username' => $data['db_user'],
            'password' => $data['db_password'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci'
        ];
        
        // Читаем шаблон конфига
        $template_file = __DIR__ . '/config.php';
        if (!file_exists($template_file)) {
            throw new Exception('Файл шаблона конфигурации не найден');
        }
        
        $config_content = file_get_contents($template_file);
        
        // Заменяем данные БД в шаблоне
        $db_config_string = var_export($db_config, true);
        $config_content = preg_replace(
            '/\$DB_CONFIG = \[.*?\];/s',
            '$DB_CONFIG = ' . $db_config_string . ';',
            $config_content
        );
        
        // Сохраняем обновленный конфиг
        file_put_contents(__DIR__ . '/config.php', $config_content);
        
        // Удаляем старые конфиг файлы если есть
        if (file_exists(__DIR__ . '/config_mysql.php')) {
            unlink(__DIR__ . '/config_mysql.php');
        }
        if (file_exists(__DIR__ . '/db_config.php')) {
            unlink(__DIR__ . '/db_config.php');
        }
        
        // 4. Создаем первого администратора
        $password_hash = password_hash($data['admin_password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("
            INSERT INTO admin_users (username, email, password_hash, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([
            $data['admin_username'],
            $data['admin_email'],
            $password_hash
        ]);
        
        // 5. Обновляем настройки системы
        $stmt = $pdo->prepare("
            UPDATE system_settings 
            SET setting_value = ? 
            WHERE setting_key = 'installation_date'
        ");
        $stmt->execute([date('Y-m-d H:i:s')]);
        
        // 6. Создаем МИНИМАЛИСТИЧНЫЙ файл-маркер установки (только дата)
        $install_info = [
            'installed_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents(__DIR__ . '/.installed', json_encode($install_info, JSON_PRETTY_PRINT));
        
        // 7. Создаем директории для логов
        if (!file_exists(__DIR__ . '/logs')) {
            mkdir(__DIR__ . '/logs', 0755, true);
        }
        
        // 8. Создаем .htaccess для безопасности
        $htaccess_content = "# Neetrino Dashboard Security Rules\n";
        $htaccess_content .= "# Deny access to sensitive files\n";
        $htaccess_content .= "<Files \"db_config.php\">\n";
        $htaccess_content .= "    Require all denied\n";
        $htaccess_content .= "</Files>\n";
        $htaccess_content .= "<Files \".installed\">\n";
        $htaccess_content .= "    Require all denied\n";
        $htaccess_content .= "</Files>\n";
        $htaccess_content .= "<Files \"database_schema.sql\">\n";
        $htaccess_content .= "    Require all denied\n";
        $htaccess_content .= "</Files>\n";
        $htaccess_content .= "# Deny access to logs directory\n";
        $htaccess_content .= "<IfModule mod_rewrite.c>\n";
        $htaccess_content .= "    RewriteEngine On\n";
        $htaccess_content .= "    RewriteRule ^logs/ - [F,L]\n";
        $htaccess_content .= "</IfModule>\n";
        
        file_put_contents(__DIR__ . '/.htaccess', $htaccess_content);
        
        return [
            'success' => true, 
            'message' => 'Установка завершена успешно!',
            'admin_user' => $data['admin_username']
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Установка Neetrino Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .step-indicator {
            transition: all 0.3s ease;
        }
        .step-active {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }
        .step-completed {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="text-6xl mb-4">🎛️</div>
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Neetrino Dashboard</h1>
            <p class="text-gray-600">Установка системы управления — <?= htmlspecialchars($dashboard_display ?: 'Dashboard') ?></p>
        </div>

        <!-- Step Indicator -->
        <div class="flex justify-center mb-8">
            <div class="flex space-x-4">
                <div class="step-indicator step-active px-4 py-2 rounded-full text-sm font-medium">
                    1. Добро пожаловать
                </div>
                <div class="step-indicator bg-gray-200 text-gray-600 px-4 py-2 rounded-full text-sm font-medium">
                    2. База данных
                </div>
                <div class="step-indicator bg-gray-200 text-gray-600 px-4 py-2 rounded-full text-sm font-medium">
                    3. Администратор
                </div>
                <div class="step-indicator bg-gray-200 text-gray-600 px-4 py-2 rounded-full text-sm font-medium">
                    4. Завершение
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            
            <?php if ($step === 'welcome'): ?>
            <!-- Welcome Step -->
            <div class="text-center">
                <h2 class="text-2xl font-bold mb-6">Добро пожаловать в установщик!</h2>
                <div class="text-left max-w-2xl mx-auto space-y-4 mb-8">
                    <div class="flex items-start space-x-3">
                        <span class="text-green-500 text-xl">✅</span>
                        <span>Автоматическое создание структуры базы данных MySQL</span>
                    </div>
                    <div class="flex items-start space-x-3">
                        <span class="text-green-500 text-xl">✅</span>
                        <span>Создание первого администратора системы</span>
                    </div>
                    <div class="flex items-start space-x-3">
                        <span class="text-green-500 text-xl">✅</span>
                        <span>Настройка безопасности и защита файлов</span>
                    </div>
                    <div class="flex items-start space-x-3">
                        <span class="text-green-500 text-xl">✅</span>
                        <span>Готовность к работе сразу после установки</span>
                    </div>
                </div>
                
                <?php if ($is_local): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center space-x-2">
                        <span class="text-blue-500">💡</span>
                        <span class="font-medium text-blue-800">Обнаружена локальная среда</span>
                    </div>
                    <p class="text-blue-700 text-sm mt-2">
                        Настройки будут предзаполнены для Laragon/XAMPP
                    </p>
                </div>
                <?php endif; ?>
                
                <a href="?step=database" class="bg-blue-500 text-white px-8 py-3 rounded-lg hover:bg-blue-600 transition-colors inline-block">
                    Начать установку →
                </a>
            </div>
            
            <?php elseif ($step === 'database'): ?>
            <!-- Database Step -->
            <h2 class="text-2xl font-bold mb-6">Настройка базы данных MySQL</h2>
            
            <?php if (isset($test_result)): ?>
            <div class="mb-6 p-4 rounded-lg <?= $test_result['success'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' ?>">
                <div class="flex items-center space-x-2">
                    <span class="<?= $test_result['success'] ? 'text-green-500' : 'text-red-500' ?> text-xl">
                        <?= $test_result['success'] ? '✅' : '❌' ?>
                    </span>
                    <span class="font-medium <?= $test_result['success'] ? 'text-green-800' : 'text-red-800' ?>">
                        <?= $test_result['success'] ? 'Подключение успешно!' : 'Ошибка подключения' ?>
                    </span>
                </div>
                <p class="<?= $test_result['success'] ? 'text-green-700' : 'text-red-700' ?> text-sm mt-2">
                    <?= htmlspecialchars($test_result['success'] ? $test_result['message'] : $test_result['error']) ?>
                </p>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="test_db">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Хост базы данных</label>
                        <input type="text" name="db_host" value="<?= $_POST['db_host'] ?? ($is_local ? 'localhost' : '') ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Порт</label>
                        <input type="number" name="db_port" value="<?= $_POST['db_port'] ?? '3306' ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               required>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Имя базы данных</label>
                    <input type="text" name="db_name" value="<?= $_POST['db_name'] ?? ($is_local ? 'dashbord_newsql1' : '') ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           required>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Пользователь</label>
                        <input type="text" name="db_user" value="<?= $_POST['db_user'] ?? ($is_local ? 'root' : '') ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Пароль</label>
                        <input type="password" name="db_password" value="<?= $_POST['db_password'] ?? '' ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               <?= $is_local ? '' : 'required' ?>>
                    </div>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                        Проверить подключение
                    </button>
                    <?php if (isset($test_result) && $test_result['success']): ?>
                    <a href="?step=admin&<?= http_build_query($_POST) ?>" class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 transition-colors inline-block">
                        Продолжить →
                    </a>
                    <?php endif; ?>
                </div>
            </form>
            
            <?php elseif ($step === 'admin'): ?>
            <!-- Admin Step -->
            <h2 class="text-2xl font-bold mb-6">Создание администратора</h2>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="install">
                <input type="hidden" name="db_host" value="<?= htmlspecialchars($_GET['db_host']) ?>">
                <input type="hidden" name="db_port" value="<?= htmlspecialchars($_GET['db_port']) ?>">
                <input type="hidden" name="db_name" value="<?= htmlspecialchars($_GET['db_name']) ?>">
                <input type="hidden" name="db_user" value="<?= htmlspecialchars($_GET['db_user']) ?>">
                <input type="hidden" name="db_password" value="<?= htmlspecialchars($_GET['db_password']) ?>">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Имя пользователя администратора</label>
                    <input type="text" name="admin_username" value="admin" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email администратора</label>
                    <input type="email" name="admin_email" value="" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Пароль администратора</label>
                    <input type="password" name="admin_password" value="" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           minlength="6" required>
                    <p class="text-sm text-gray-500 mt-1">Минимум 6 символов</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Подтверждение пароля</label>
                    <input type="password" name="admin_password_confirm" value="" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           required>
                </div>
                
                <?php if (isset($installation_result) && !$installation_result['success']): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center space-x-2">
                        <span class="text-red-500 text-xl">❌</span>
                        <span class="font-medium text-red-800">Ошибка установки</span>
                    </div>
                    <p class="text-red-700 text-sm mt-2"><?= htmlspecialchars($installation_result['error']) ?></p>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="bg-green-500 text-white px-8 py-3 rounded-lg hover:bg-green-600 transition-colors">
                    🚀 Установить систему
                </button>
            </form>
            
            <?php elseif ($step === 'success'): ?>
            <!-- Success Step -->
            <div class="text-center">
                <div class="text-green-500 text-6xl mb-6">🎉</div>
                <h2 class="text-3xl font-bold text-green-600 mb-4">Установка завершена!</h2>
                <p class="text-gray-600 mb-8">Система Neetrino Dashboard успешно установлена и готова к работе.</p>
                
                <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-8">
                    <h3 class="font-bold text-green-800 mb-4">Данные для входа:</h3>
                    <div class="text-left space-y-2">
                        <div><strong>Пользователь:</strong> <?= htmlspecialchars($installation_result['admin_user']) ?></div>
                        <div><strong>Пароль:</strong> Тот, что вы указали при установке</div>
                        <div><strong>URL входа:</strong> <a href="login.php" class="text-blue-600">login.php</a></div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <a href="login.php" class="bg-blue-500 text-white px-8 py-3 rounded-lg hover:bg-blue-600 transition-colors inline-block">
                        🔑 Войти в систему
                    </a>
                    <br>
                    <a href="index.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors inline-block text-sm">
                        🏠 На главную
                    </a>
                </div>
                
                <div class="mt-8 text-sm text-gray-500">
                    <p>⚠️ Рекомендуется удалить файл install.php после завершения установки для безопасности</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Валидация формы администратора
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action*="install"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            const password = form.querySelector('input[name="admin_password"]').value;
            const confirm = form.querySelector('input[name="admin_password_confirm"]').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Пароли не совпадают!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Пароль должен содержать минимум 6 символов!');
                return false;
            }
        });
    }
});
</script>

</body>
</html>

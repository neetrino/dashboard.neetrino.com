<?php
// Запускаем сессию ДО любого вывода
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Проверяем установку системы
if (!file_exists(__DIR__ . '/.installed')) {
    header('Location: install.php');
    exit;
}

// Подключаем единую конфигурацию
define('NEETRINO_DASHBOARD', true);
require_once 'config.php';
require_once 'includes/Auth.php';

// Проверяем подключение к базе данных
if (!isset($pdo)) {
    header('Location: install.php');
    exit;
}

$auth = new Auth($pdo);
$error = '';
$success = '';

// Проверяем сообщения об ошибках из URL
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'account_disabled':
            $error = 'Ваш аккаунт отключен. Обратитесь к администратору.';
            break;
        case 'session_expired':
            $error = 'Ваша сессия истекла. Войдите заново.';
            break;
    }
}

// Если уже авторизован - перенаправляем
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Обработка формы входа
if ($_POST && isset($_POST['username'], $_POST['password'], $_POST['csrf_token'])) {
    if ($auth->validateCSRFToken($_POST['csrf_token'])) {
        $result = $auth->login($_POST['username'], $_POST['password']);
        if ($result['success']) {
            header('Location: index.php');
            exit;
        } else {
            $error = $result['error'];
        }
    } else {
        $error = 'Ошибка безопасности. Попробуйте еще раз.';
    }
}

$csrf_token = $auth->generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - Neetrino Control Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="login-card rounded-lg p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">🛡️ Neetrino Dashboard</h1>
            <p class="text-gray-600">Введите данные для входа</p>
        </div>
        
        <?php if ($error): ?>
            <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                    👤 Логин
                </label>
                <input type="text" id="username" name="username" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="admin">
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    🔑 Пароль
                </label>
                <input type="password" id="password" name="password" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Введите пароль">
            </div>
            
            <button type="submit" 
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200">
                🚀 Войти в Dashboard
            </button>
        </form>
        
        <div class="mt-6 text-center text-sm text-gray-600">
            <p>💡 Первый вход: admin / admin123</p>
            <p class="text-xs mt-2">Защищено Neetrino Security System</p>
        </div>
    </div>
    
    <script>
        // Автофокус на поле логина
        document.getElementById('username').focus();
        
        // Обработка Enter в полях
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('form').submit();
            }
        });
    </script>
</body>
</html>

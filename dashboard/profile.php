<?php
// Проверка авторизации
require_once 'auth_check.php';

$message = '';
$error = '';

// Обработка формы смены email
if ($_POST && isset($_POST['new_email'], $_POST['csrf_token']) && $_POST['action'] === 'change_email') {
    
    if (!$auth->validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Ошибка безопасности. Попробуйте еще раз.';
    } else {
        $new_email = trim($_POST['new_email']);
        
        // Валидация email
        if (empty($new_email)) {
            $new_email = null; // Разрешаем пустой email
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Некорректный формат email';
        } else {
            // Проверяем, не занят ли email
            $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
            $stmt->execute([$new_email, $current_user['id']]);
            
            if ($stmt->fetch()) {
                $error = 'Этот email уже используется';
            }
        }
        
        if (!$error) {
            // Обновляем email
            $stmt = $pdo->prepare("UPDATE admin_users SET email = ? WHERE id = ?");
            
            if ($stmt->execute([$new_email, $current_user['id']])) {
                $message = 'Email успешно изменен!';
                $current_user['email'] = $new_email; // Обновляем для отображения
            } else {
                $error = 'Ошибка при сохранении нового email';
            }
        }
    }
}

// Обработка формы смены логина
if ($_POST && isset($_POST['new_username'], $_POST['csrf_token']) && $_POST['action'] === 'change_username') {
    
    if (!$auth->validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Ошибка безопасности. Попробуйте еще раз.';
    } else {
        $new_username = trim($_POST['new_username']);
        
        // Валидация нового логина
        if (empty($new_username)) {
            $error = 'Логин не может быть пустым';
        } elseif (strlen($new_username) < 3) {
            $error = 'Логин должен содержать минимум 3 символа';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
            $error = 'Логин может содержать только буквы, цифры и символ _';
        } else {
            // Проверяем, не занят ли новый логин
            $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? AND id != ?");
            $stmt->execute([$new_username, $current_user['id']]);
            
            if ($stmt->fetch()) {
                $error = 'Этот логин уже используется';
            } else {
                // Обновляем логин
                $stmt = $pdo->prepare("UPDATE admin_users SET username = ? WHERE id = ?");
                
                if ($stmt->execute([$new_username, $current_user['id']])) {
                    $message = 'Логин успешно изменен!';
                    $current_user['username'] = $new_username; // Обновляем для отображения
                } else {
                    $error = 'Ошибка при сохранении нового логина';
                }
            }
        }
    }
}

// Обработка формы смены пароля
if ($_POST && isset($_POST['current_password'], $_POST['new_password'], $_POST['confirm_password'], $_POST['csrf_token']) && $_POST['action'] === 'change_password') {
    
    if (!$auth->validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Ошибка безопасности. Попробуйте еще раз.';
    } else {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Валидация
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Все поля обязательны для заполнения';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Новый пароль и подтверждение не совпадают';
        } elseif (strlen($new_password) < 6) {
            $error = 'Новый пароль должен содержать минимум 6 символов';
        } else {
            // Проверяем текущий пароль
            $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
            $stmt->execute([$current_user['id']]);
            $stored_hash = $stmt->fetchColumn();
            
            if (!password_verify($current_password, $stored_hash)) {
                $error = 'Неверный текущий пароль';
            } else {
                // Обновляем пароль
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
                
                if ($stmt->execute([$new_hash, $current_user['id']])) {
                    $message = 'Пароль успешно изменен!';
                } else {
                    $error = 'Ошибка при сохранении нового пароля';
                }
            }
        }
    }
}

$csrf_token = $auth->generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль пользователя - Neetrino Control Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="assets/css/dashboard.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <!-- Современный заголовок с фоном -->
    <div class="bg-white border-b border-gray-200 shadow-sm">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-6">
                    <a href="index.php" class="group flex items-center space-x-2 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-xl transition-all duration-300 transform hover:scale-105">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        <span class="font-medium">Dashboard</span>
                    </a>
                    <div class="h-8 w-px bg-gray-300"></div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Профиль пользователя</h1>
                        <p class="text-sm text-gray-500">Управление аккаунтом и настройками</p>
                    </div>
                </div>
                <div>
                    <a href="logout.php" class="group flex items-center space-x-2 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white px-4 py-2 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span class="font-medium">Выход</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-6 py-8">
        <!-- Креативная сетка с асимметричным расположением -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Главная карточка профиля (занимает больше места) -->
            <div class="lg:col-span-8">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                    <!-- Заголовок с градиентом -->
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-6 text-white">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold"><?= htmlspecialchars($current_user['username']) ?></h2>
                                <p class="text-blue-100"><?= htmlspecialchars($current_user['email'] ?: 'Email не указан') ?></p>
                                <p class="text-sm text-blue-200 mt-1">
                                    Последний вход: <?= $current_user['last_login'] ? date('d.m.Y H:i', strtotime($current_user['last_login'])) : 'Неизвестно' ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Основной контент -->
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-6">Настройки аккаунта</h3>
                        
                        <div class="space-y-6">
                            <!-- Логин -->
                            <div class="group">
                                <label class="block text-sm font-medium text-gray-600 mb-3">Логин пользователя</label>
                                <div class="relative">
                                    <div class="flex items-center p-4 bg-gray-50 rounded-xl border-2 border-transparent group-hover:border-blue-200 transition-all duration-300">
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($current_user['username']) ?></div>
                                            <div class="text-sm text-gray-500">Ваш уникальный идентификатор</div>
                                        </div>
                                        <button onclick="toggleUsernameForm()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-all duration-200 transform hover:scale-105">
                                            Изменить
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Форма смены логина -->
                                <div id="username-form" class="hidden mt-4 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200">
                                    <form method="POST" class="space-y-4">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <input type="hidden" name="action" value="change_username">
                                        
                                        <div>
                                            <label for="new_username" class="block text-sm font-medium text-gray-700 mb-2">
                                                Новый логин
                                            </label>
                                            <input type="text" id="new_username" name="new_username" 
                                                   value="<?= htmlspecialchars($current_user['username']) ?>"
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                                   pattern="[a-zA-Z0-9_]+" minlength="3" required>
                                            <p class="text-xs text-gray-500 mt-2">Только буквы, цифры и символ _ (минимум 3 символа)</p>
                                        </div>
                                        
                                        <div class="flex space-x-3">
                                            <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-xl hover:bg-blue-600 transition-all duration-200 transform hover:scale-105">
                                                Сохранить
                                            </button>
                                            <button type="button" onclick="toggleUsernameForm()" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-xl hover:bg-gray-300 transition-all duration-200">
                                                Отмена
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Email -->
                            <div class="group">
                                <label class="block text-sm font-medium text-gray-600 mb-3">Электронная почта</label>
                                <div class="relative">
                                    <div class="flex items-center p-4 bg-gray-50 rounded-xl border-2 border-transparent group-hover:border-green-200 transition-all duration-300">
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($current_user['email'] ?: 'Не указан') ?></div>
                                            <div class="text-sm text-gray-500">Для восстановления доступа</div>
                                        </div>
                                        <button onclick="toggleEmailForm()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-all duration-200 transform hover:scale-105">
                                            Изменить
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Форма смены email -->
                                <div id="email-form" class="hidden mt-4 p-6 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200">
                                    <form method="POST" class="space-y-4">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <input type="hidden" name="action" value="change_email">
                                        
                                        <div>
                                            <label for="new_email" class="block text-sm font-medium text-gray-700 mb-2">
                                                Новый email
                                            </label>
                                            <input type="email" id="new_email" name="new_email" 
                                                   value="<?= htmlspecialchars($current_user['email'] ?: '') ?>"
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                                   placeholder="email@example.com">
                                            <p class="text-xs text-gray-500 mt-2">Оставьте пустым, чтобы убрать email</p>
                                        </div>
                                        
                                        <div class="flex space-x-3">
                                            <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded-xl hover:bg-green-600 transition-all duration-200 transform hover:scale-105">
                                                Сохранить
                                            </button>
                                            <button type="button" onclick="toggleEmailForm()" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-xl hover:bg-gray-300 transition-all duration-200">
                                                Отмена
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Пароль -->
                            <div class="group">
                                <label class="block text-sm font-medium text-gray-600 mb-3">Пароль</label>
                                <div class="relative">
                                    <div class="flex items-center p-4 bg-gray-50 rounded-xl border-2 border-transparent group-hover:border-purple-200 transition-all duration-300">
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900">••••••••••</div>
                                            <div class="text-sm text-gray-500">Для безопасности аккаунта</div>
                                        </div>
                                        <button onclick="togglePasswordForm()" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg transition-all duration-200 transform hover:scale-105">
                                            Изменить
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Форма смены пароля -->
                                <div id="password-form" class="hidden mt-4 p-6 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl border border-purple-200">
                                    <?php if ($message): ?>
                                        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                                            <?= htmlspecialchars($message) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($error): ?>
                                        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                                            <?= htmlspecialchars($error) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <form method="POST" class="space-y-4">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <input type="hidden" name="action" value="change_password">
                                        
                                        <div>
                                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                                                Текущий пароль
                                            </label>
                                            <input type="password" id="current_password" name="current_password" required
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                                        </div>
                                        
                                        <div>
                                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                                                Новый пароль
                                            </label>
                                            <input type="password" id="new_password" name="new_password" required
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                                                   minlength="6">
                                            <p class="text-xs text-gray-500 mt-2">Минимум 6 символов</p>
                                        </div>
                                        
                                        <div>
                                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                                Повторите новый пароль
                                            </label>
                                            <input type="password" id="confirm_password" name="confirm_password" required
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                                        </div>
                                        
                                        <div class="flex space-x-3">
                                            <button type="submit" class="bg-purple-500 text-white px-6 py-2 rounded-xl hover:bg-purple-600 transition-all duration-200 transform hover:scale-105">
                                                Сохранить
                                            </button>
                                            <button type="button" onclick="togglePasswordForm()" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-xl hover:bg-gray-300 transition-all duration-200">
                                                Отмена
                                            </button>
                                        </div>
                                    </form>
                                    
                                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                        <div class="text-sm text-yellow-700">
                                            <strong>Совет:</strong> Используйте надежный пароль с буквами, цифрами и символами.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Боковая панель -->
            <div class="lg:col-span-4 space-y-6">
                
                <!-- Статус сессии -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-400 to-blue-500 p-4 text-white">
                        <h3 class="font-semibold flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1M12,7C13.4,7 14.8,8.6 14.8,10V11.5C15.4,11.5 16,12.4 16,13V16C16,17.4 15.4,18 14.8,18H9.2C8.6,18 8,17.4 8,16V13C8,12.4 8.6,11.5 9.2,11.5V10C9.2,8.6 10.6,7 12,7M12,8.2C11.2,8.2 10.5,8.7 10.5,10V11.5H13.5V10C13.5,8.7 12.8,8.2 12,8.2Z"/>
                            </svg>
                            Сессия активна
                        </h3>
                    </div>
                    <div class="p-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-800 mb-1">
                                <?= round((86400 - (time() - $_SESSION['login_time'])) / 3600, 1) ?>ч
                            </div>
                            <div class="text-sm text-gray-500 mb-4">до истечения</div>
                            <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                                <div class="bg-gradient-to-r from-green-400 to-blue-500 h-2 rounded-full" style="width: <?= ((86400 - (time() - $_SESSION['login_time'])) / 86400) * 100 ?>%"></div>
                            </div>
                            <a href="logout.php" class="w-full bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white px-4 py-2 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg flex items-center justify-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                Завершить сессию
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Быстрые настройки -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12,15.5A3.5,3.5 0 0,1 8.5,12A3.5,3.5 0 0,1 12,8.5A3.5,3.5 0 0,1 15.5,12A3.5,3.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.5,5.32 14.87,5.07L14.5,2.42C14.46,2.18 14.25,2 14,2H10C9.75,2 9.54,2.18 9.5,2.42L9.13,5.07C8.5,5.32 7.96,5.66 7.44,6.05L4.95,5.05C4.73,4.96 4.46,5.05 4.34,5.27L2.34,8.73C2.22,8.95 2.27,9.22 2.46,9.37L4.57,11C4.53,11.34 4.5,11.67 4.5,12C4.5,12.33 4.53,12.65 4.57,12.97L2.46,14.63C2.27,14.78 2.22,15.05 2.34,15.27L4.34,18.73C4.46,18.95 4.73,19.03 4.95,18.95L7.44,17.94C7.96,18.34 8.5,18.68 9.13,18.93L9.5,21.58C9.54,21.82 9.75,22 10,22H14C14.25,22 14.46,21.82 14.5,21.58L14.87,18.93C15.5,18.68 16.04,18.34 16.56,17.94L19.05,18.95C19.27,19.03 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z"/>
                        </svg>
                        Безопасность
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                            <div>
                                <div class="text-sm font-medium text-green-800">Защита от атак</div>
                                <div class="text-xs text-green-600">Активна</div>
                            </div>
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                            <div>
                                <div class="text-sm font-medium text-blue-800">CSRF защита</div>
                                <div class="text-xs text-blue-600">Активна</div>
                            </div>
                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Переключение формы смены логина
function toggleUsernameForm() {
    const form = document.getElementById('username-form');
    if (form.classList.contains('hidden')) {
        form.classList.remove('hidden');
    } else {
        form.classList.add('hidden');
    }
}

// Переключение формы смены email
function toggleEmailForm() {
    const form = document.getElementById('email-form');
    if (form.classList.contains('hidden')) {
        form.classList.remove('hidden');
    } else {
        form.classList.add('hidden');
    }
}

// Переключение формы смены пароля
function togglePasswordForm() {
    const form = document.getElementById('password-form');
    if (form.classList.contains('hidden')) {
        form.classList.remove('hidden');
    } else {
        form.classList.add('hidden');
    }
}

// Проверка совпадения паролей
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword && confirmPassword && newPassword !== confirmPassword) {
        this.setCustomValidity('Пароли не совпадают');
    } else {
        this.setCustomValidity('');
    }
});

// Показать силу пароля
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    let strength = 0;
    
    if (password.length >= 6) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    const strengthText = ['Очень слабый', 'Слабый', 'Средний', 'Хороший', 'Отличный'];
    const strengthColors = ['text-red-500', 'text-orange-500', 'text-yellow-500', 'text-blue-500', 'text-green-500'];
    
    // Можно добавить индикатор силы пароля
});
</script>

</body>
</html>

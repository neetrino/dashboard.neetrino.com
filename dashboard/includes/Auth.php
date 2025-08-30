<?php
/**
 * Класс для управления авторизацией
 * Обновлено для работы с MySQL
 */

class Auth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        // Убираем автоматический session_start() - будем управлять вручную
        
        // Проверяем существование таблицы admin_users
        $this->checkUsersTable();
    }
    
    /**
     * Проверка существования таблицы пользователей
     */
    private function checkUsersTable() {
        try {
            $this->pdo->query("SELECT 1 FROM admin_users LIMIT 1");
        } catch (Exception $e) {
            // Если таблица не существует - создаем её
            $this->createUsersTable();
        }
    }
    
    /**
     * Создание таблицы пользователей (если не существует)
     */
    private function createUsersTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                failed_attempts INT DEFAULT 0,
                locked_until DATETIME NULL,
                last_login DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                is_active BOOLEAN DEFAULT TRUE,
                role ENUM('admin', 'moderator') DEFAULT 'admin',
                
                INDEX idx_username (username),
                INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Проверка авторизации пользователя
     */
    public function login($username, $password) {
        try {
            // Проверяем блокировку
            $stmt = $this->pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'error' => 'Неверный логин или пароль'];
            }
            
            // Проверяем блокировку
            if ($user['locked_until'] && new DateTime() < new DateTime($user['locked_until'])) {
                $unlock_time = date('H:i', strtotime($user['locked_until']));
                return ['success' => false, 'error' => "Аккаунт заблокирован до $unlock_time"];
            }
            
            // Проверяем пароль
            if (!password_verify($password, $user['password_hash'])) {
                $this->incrementFailedAttempts($user['id']);
                return ['success' => false, 'error' => 'Неверный логин или пароль'];
            }
            
            // Успешный вход
            $this->clearFailedAttempts($user['id']);
            $this->updateLastLogin($user['id']);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['login_time'] = time();
            
            return ['success' => true, 'message' => 'Успешный вход'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Ошибка системы'];
        }
    }
    
    /**
     * Проверка авторизации
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['login_time'])) {
            return false;
        }
        
        // Проверяем время сессии (24 часа)
        if (time() - $_SESSION['login_time'] > 86400) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    /**
     * Выход из системы
     */
    public function logout() {
        session_destroy();
        setcookie(session_name(), '', time()-3600, '/');
    }
    
    /**
     * Получить данные текущего пользователя
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $stmt = $this->pdo->prepare("SELECT id, username, email, last_login, is_active, role FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Увеличение счетчика неудачных попыток
     */
    private function incrementFailedAttempts($user_id) {
        $stmt = $this->pdo->prepare("UPDATE admin_users SET failed_attempts = failed_attempts + 1 WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Проверяем количество попыток
        $stmt = $this->pdo->prepare("SELECT failed_attempts FROM admin_users WHERE id = ?");
        $stmt->execute([$user_id]);
        $attempts = $stmt->fetchColumn();
        
        // Блокируем после 3 неудачных попыток на 15 минут
        if ($attempts >= 3) {
            $lock_until = date('Y-m-d H:i:s', time() + 900); // 15 минут
            $stmt = $this->pdo->prepare("UPDATE admin_users SET locked_until = ? WHERE id = ?");
            $stmt->execute([$lock_until, $user_id]);
        }
    }
    
    /**
     * Очистка неудачных попыток
     */
    private function clearFailedAttempts($user_id) {
        $stmt = $this->pdo->prepare("UPDATE admin_users SET failed_attempts = 0, locked_until = NULL WHERE id = ?");
        $stmt->execute([$user_id]);
    }
    
    /**
     * Обновление времени последнего входа
     */
    private function updateLastLogin($user_id) {
        $stmt = $this->pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user_id]);
    }
    
    /**
     * Генерация CSRF токена
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Проверка CSRF токена
     */
    public function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Смена пароля пользователя
     */
    public function changePassword($currentPassword, $newPassword) {
        try {
            if (!$this->isLoggedIn()) {
                return ['success' => false, 'error' => 'Необходима авторизация'];
            }
            
            // Получаем текущего пользователя
            $stmt = $this->pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'error' => 'Пользователь не найден'];
            }
            
            // Проверяем текущий пароль
            if (!password_verify($currentPassword, $user['password_hash'])) {
                return ['success' => false, 'error' => 'Неверный текущий пароль'];
            }
            
            // Проверяем длину нового пароля
            if (strlen($newPassword) < 6) {
                return ['success' => false, 'error' => 'Новый пароль должен содержать минимум 6 символов'];
            }
            
            // Хешируем новый пароль
            $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
            
            // Обновляем пароль в базе данных
            $stmt = $this->pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$newPasswordHash, $_SESSION['user_id']]);
            
            return ['success' => true, 'message' => 'Пароль успешно изменен'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Ошибка при смене пароля'];
        }
    }
}
?>

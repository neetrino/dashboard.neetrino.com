<?php
/**
 * Единая конфигурация Neetrino Dashboard 
 * MySQL + система управления
 * 
 * @package NeetrinoDashboard
 * @version 3.0
 * @author Neetrino Team
 */

// Защита от прямого доступа
if (!defined('NEETRINO_DASHBOARD')) {
    die('Access denied');
}

// Проверка установки
if (!file_exists(__DIR__ . '/.installed')) {
    // Система не установлена - перенаправляем на установщик
    if (basename($_SERVER['PHP_SELF']) !== 'install.php') {
        header('Location: install.php');
        exit;
    }
}

// Определяем окружение
$is_local = true;
if (isset($_SERVER['HTTP_HOST'])) {
    $is_local = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
                strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
                strpos($_SERVER['HTTP_HOST'], '.local') !== false ||
                strpos($_SERVER['HTTP_HOST'], '.test') !== false);
}

// === КОНФИГУРАЦИЯ БАЗЫ ДАННЫХ ===
// Эти данные создаются автоматически при установке
$DB_CONFIG = array (
  'host' => 'localhost',
  'port' => '3306',
  'database' => 'dashbord_newsql1',
  'username' => 'root',
  'password' => '',
  'charset' => 'utf8mb4',
  'collation' => 'utf8mb4_unicode_ci',
);

// Проверка целостности конфигурации БД
if (empty($DB_CONFIG['host']) || empty($DB_CONFIG['database'])) {
    throw new Exception('Поврежденная конфигурация базы данных');
}

// === НАСТРОЙКИ ОКРУЖЕНИЯ ===
if ($is_local) {
    // Локальное окружение (разработка)
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    date_default_timezone_set('Europe/Moscow');
} else {
    // Продакшн окружение
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/php_errors.log');
    date_default_timezone_set('Europe/Moscow');
}

// === ПОДКЛЮЧЕНИЕ К MYSQL ===
try {
    $dsn = "mysql:host={$DB_CONFIG['host']};port={$DB_CONFIG['port']};dbname={$DB_CONFIG['database']};charset={$DB_CONFIG['charset']}";
    
    $pdo = new PDO($dsn, $DB_CONFIG['username'], $DB_CONFIG['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$DB_CONFIG['charset']} COLLATE {$DB_CONFIG['collation']}"
    ]);
    
    // Устанавливаем SQL режим для совместимости (MySQL 8.0+)
    $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    
} catch(PDOException $e) {
    // Обработка ошибок подключения
    $error_message = $is_local ? $e->getMessage() : 'Ошибка подключения к базе данных';
    
    if (basename($_SERVER['PHP_SELF']) !== 'install.php') {
        ?>
        <!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Ошибка подключения - Neetrino Dashboard</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-red-100">
        <div class="min-h-screen flex items-center justify-center">
            <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
                <div class="text-center">
                    <div class="text-red-500 text-6xl mb-4">🚫</div>
                    <h1 class="text-2xl font-bold text-red-600 mb-4">Ошибка подключения</h1>
                    <p class="text-gray-600 mb-6"><?= htmlspecialchars($error_message) ?></p>
                    <div class="space-y-3">
                        <a href="install.php" class="block bg-blue-500 text-white px-6 py-3 rounded hover:bg-blue-600 transition-colors">
                            🔧 Переустановить систему
                        </a>
                    </div>
                </div>
            </div>
        </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// === КЛАСС МЕНЕДЖЕРА БД ===
class DatabaseManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Проверка подключения к базе данных
     */
    public function testConnection() {
        try {
            $this->pdo->query('SELECT 1');
            return ['success' => true, 'message' => 'Подключение успешно'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Проверка существования таблиц
     */
    public function checkTables() {
        $required_tables = ['admin_users', 'sites', 'rate_limits', 'security_logs', 'trash', 'system_settings'];
        $existing_tables = [];
        
        try {
            $stmt = $this->pdo->query("SHOW TABLES");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $existing_tables[] = $row[0];
            }
            
            $missing_tables = array_diff($required_tables, $existing_tables);
            
            return [
                'success' => empty($missing_tables),
                'existing' => $existing_tables,
                'missing' => $missing_tables,
                'required' => $required_tables
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Получение информации о базе данных
     */
    public function getDatabaseInfo() {
        try {
            $info = [];
            
            // Версия MySQL
            $stmt = $this->pdo->query("SELECT VERSION() as version");
            $info['mysql_version'] = $stmt->fetchColumn();
            
            // Размер базы данных
            $stmt = $this->pdo->query("
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ");
            $info['database_size_mb'] = $stmt->fetchColumn();
            
            // Количество таблиц
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as table_count 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ");
            $info['table_count'] = $stmt->fetchColumn();
            
            // Текущая база данных
            $stmt = $this->pdo->query("SELECT DATABASE() as current_db");
            $info['current_database'] = $stmt->fetchColumn();
            
            return ['success' => true, 'info' => $info];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Выполнение SQL файла
     */
    public function executeSqlFile($file_path) {
        try {
            if (!file_exists($file_path)) {
                throw new Exception("SQL файл не найден: $file_path");
            }
            
            $sql = file_get_contents($file_path);
            
            // Разбиваем на отдельные запросы
            $queries = preg_split('/;\s*$/m', $sql);
            
            $executed = 0;
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query) && !preg_match('/^--/', $query)) {
                    $this->pdo->exec($query);
                    $executed++;
                }
            }
            
            return ['success' => true, 'executed' => $executed];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

// === ФУНКЦИЯ ЛОГИРОВАНИЯ ===
if (!function_exists('debug_log')) {
    function debug_log($message) {
        $log_dir = __DIR__ . '/logs';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_file = $log_dir . '/dashboard.log';
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] [DASHBOARD] $message" . PHP_EOL;
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}

// === ИНИЦИАЛИЗАЦИЯ ===
// Создаем экземпляр менеджера БД если есть подключение
if (isset($pdo)) {
    $db_manager = new DatabaseManager($pdo);
    debug_log("Unified config loaded successfully");
}
?>

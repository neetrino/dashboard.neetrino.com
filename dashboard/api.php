<?php
/**
 * API для Push-архитектуры Dashboard
 * Упрощенная версия без polling логики
 */

define('NEETRINO_DASHBOARD', true);
require_once 'config.php';
require_once 'includes/SecurityManager.php';

/**
 * Нормализация URL к домену для идентификации
 * Убирает протокол, www, путь, параметры - оставляет только домен и поддомены
 */
function normalize_url_to_domain($url) {
    // Добавляем протокол если его нет
    if (!preg_match('~^https?://~i', $url)) {
        $url = 'http://' . $url;
    }
    
    // Парсим URL
    $parsed = parse_url($url);
    if (!$parsed || empty($parsed['host'])) {
        return $url; // Возвращаем оригинал если не удалось распарсить
    }
    
    $domain = strtolower($parsed['host']);
    
    // Убираем www. префикс
    if (substr($domain, 0, 4) === 'www.') {
        $domain = substr($domain, 4);
    }
    
    return $domain;
}

// Инициализируем SecurityManager с подключением к БД
$security = new SecurityManager($pdo);

// Настройки CORS для работы с внешними сайтами
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Получаем действие
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Устанавливаем JSON заголовки
header('Content-Type: application/json');

// Обрабатываем действия
switch($action) {
    case 'get_sites':
        handle_get_sites();
        break;
    case 'get_setting':
        handle_get_setting();
        break;
    case 'set_setting':
        handle_set_setting();
        break;
    case 'add_site':
        handle_add_site();
        break;
    case 'delete_plugin':
        handle_delete_plugin();
        break;
    case 'get_trash':
        handle_get_trash();
        break;
    case 'register':
        handle_register();
        break;
    case 'register_site':
        handle_register_site();
        break;
    case 'daily_ping':
        handle_daily_ping();
        break;
    case 'remove_from_dashboard':
        handle_remove_from_dashboard();
        break;
    case 'ping':
        handle_ping();
        break;
    case 'restore_site':
        handle_restore_site();
        break;
    case 'restore_all_sites':
        handle_restore_all_sites();
        break;
    case 'update_site_status':
        handle_update_site_status();
        break;
    case 'plugin_version_push':
        handle_plugin_version_push();
        break;
    case 'plugin_version_pull':
        handle_plugin_version_pull();
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
}

/**
 * Получение списка сайтов с пагинацией
 */
function handle_get_sites() {
    global $pdo;
    
    try {
        // Ensure auxiliary table exists for version info (safe no-op if already exists)
        $pdo->exec("CREATE TABLE IF NOT EXISTS site_versions (
            site_id INT NOT NULL PRIMARY KEY,
            plugin_version VARCHAR(50) NOT NULL,
            last_seen_at DATETIME NOT NULL,
            source ENUM('push','pull') DEFAULT 'push',
            signature_ok TINYINT(1) DEFAULT 1,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_site_versions_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Параметры пагинации
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = isset($_GET['per_page']) ? max(1, min(100, intval($_GET['per_page']))) : 20;
        $offset = ($page - 1) * $per_page;
        
        // Фильтры
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
        
        // Базовый SQL запрос
        $where_conditions = [];
        $params = [];
        
        // Поиск по названию и URL
        if (!empty($search)) {
            $where_conditions[] = "(s.site_name LIKE ? OR s.site_url LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        // Фильтр по статусу
        if (!empty($status_filter) && in_array($status_filter, ['online', 'offline'])) {
            $where_conditions[] = "s.status = ?";
            $params[] = $status_filter;
        }
        
        // Собираем WHERE условие
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Получаем общее количество записей
        $count_sql = "SELECT COUNT(*) as total FROM sites s {$where_clause}";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_sites = $count_stmt->fetch()['total'];
        
        // Получаем сайты с пагинацией
        $sql = "SELECT s.*, sv.plugin_version, sv.last_seen_at AS plugin_last_seen
                FROM sites s
                LEFT JOIN site_versions sv ON sv.site_id = s.id
                {$where_clause}
                ORDER BY s.site_name
                LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        
        // Добавляем параметры для LIMIT и OFFSET
        $all_params = array_merge($params, [$per_page, $offset]);
        $stmt->execute($all_params);
        $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Обновляем статус на основе последней активности
        foreach ($sites as &$site) {
            $last_seen = $site['last_seen'] ? strtotime($site['last_seen']) : null;
            $now = time();
            
            // Если не было активности больше суток - считаем офлайн
            if ($last_seen && ($now - $last_seen) > 86400) {
                $site['status'] = 'offline';
            } else {
                $site['status'] = $site['status'] ?: 'offline';
            }
        }
        
        // Рассчитываем пагинацию
        $total_pages = ceil($total_sites / $per_page);
        
        echo json_encode([
            'success' => true,
            'sites' => $sites,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_sites' => $total_sites,
                'total_pages' => $total_pages,
                'has_next' => $page < $total_pages,
                'has_prev' => $page > 1
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Получение системной настройки по ключу
 */
function handle_get_setting() {
    global $pdo;

    $key = $_GET['key'] ?? $_POST['key'] ?? '';
    if (empty($key)) {
        echo json_encode(['success' => false, 'error' => 'key required']);
        return;
    }

    try {
        // Ensure system_settings exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT NULL,
            setting_type ENUM('string','integer','boolean','json') DEFAULT 'string',
            description TEXT NULL,
            is_public BOOLEAN DEFAULT FALSE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_setting_key (setting_key),
            INDEX idx_is_public (is_public)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $stmt = $pdo->prepare("SELECT setting_value, setting_type FROM system_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo json_encode(['success' => true, 'key' => $key, 'value' => null]);
            return;
        }

        // Decode JSON if needed
        $value = $row['setting_value'];
        if ($row['setting_type'] === 'json' && $value) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        echo json_encode(['success' => true, 'key' => $key, 'value' => $value]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Failed to get setting: ' . $e->getMessage()]);
    }
}

/**
 * Установка системной настройки по ключу
 */
function handle_set_setting() {
    global $pdo;

    $key = $_POST['key'] ?? '';
    $value = $_POST['value'] ?? null;
    $type = $_POST['type'] ?? 'string';

    if (empty($key)) {
        echo json_encode(['success' => false, 'error' => 'key required']);
        return;
    }

    // Normalize type
    $allowed_types = ['string', 'integer', 'boolean', 'json'];
    if (!in_array($type, $allowed_types)) $type = 'string';

    try {
        // Ensure system_settings exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT NULL,
            setting_type ENUM('string','integer','boolean','json') DEFAULT 'string',
            description TEXT NULL,
            is_public BOOLEAN DEFAULT FALSE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_setting_key (setting_key),
            INDEX idx_is_public (is_public)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Prepare value for storage
        $stored_value = $value;
        if ($type === 'json' && !is_string($stored_value)) {
            $stored_value = json_encode($stored_value, JSON_UNESCAPED_UNICODE);
        } else if ($type === 'boolean') {
            $stored_value = filter_var($stored_value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        }

        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type), updated_at = CURRENT_TIMESTAMP");
        $stmt->execute([$key, $stored_value, $type]);

        echo json_encode(['success' => true, 'key' => $key, 'value' => $value, 'type' => $type]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Failed to set setting: ' . $e->getMessage()]);
    }
}

/**
 * Добавление нового сайта
 */
function handle_add_site() {
    global $pdo;
    
    $site_url = $_POST['site_url'] ?? '';
    $site_name = $_POST['site_name'] ?? '';
    
    if (empty($site_url) || empty($site_name)) {
        echo json_encode(['success' => false, 'error' => 'Site URL and name required']);
        return;
    }
    
    // Нормализуем URL
    $site_url = rtrim($site_url, '/');
    $site_domain = normalize_url_to_domain($site_url);
    
    try {
        // Проверяем не существует ли уже сайт с таким доменом
        $stmt = $pdo->prepare("SELECT id, site_url FROM sites WHERE site_url LIKE ? OR site_url LIKE ? OR site_url LIKE ? OR site_url LIKE ?");
        $stmt->execute([
            "%://$site_domain",
            "%://$site_domain/%", 
            "%://www.$site_domain",
            "%://www.$site_domain/%"
        ]);
        
        if ($existing = $stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Site with this domain already exists: ' . $existing['site_url']]);
            return;
        }
        
        // Добавляем новый сайт
        $stmt = $pdo->prepare("
            INSERT INTO sites (site_url, site_name, status, created_at, last_seen) 
            VALUES (?, ?, 'offline', NOW(), NULL)
        ");
        
        $stmt->execute([$site_url, $site_name]);
        $site_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Site added successfully',
            'site_id' => $site_id
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Регистрация сайта (при первом запуске плагина)
 */
function handle_register() {
    global $pdo;
    
    $site_url = $_POST['site_url'] ?? '';
    $site_name = $_POST['site_name'] ?? '';
    $active_modules = $_POST['active_modules'] ?? '';
    
    if (empty($site_url)) {
        echo json_encode(['success' => false, 'error' => 'Site URL required']);
        return;
    }
    
    // Нормализуем URL
    $site_url = rtrim($site_url, '/');
    $site_domain = normalize_url_to_domain($site_url);
    
    try {
        // Проверяем существует ли сайт с таким доменом
        $stmt = $pdo->prepare("SELECT id FROM sites WHERE site_url LIKE ? OR site_url LIKE ? OR site_url LIKE ? OR site_url LIKE ?");
        $stmt->execute([
            "%://$site_domain",
            "%://$site_domain/%", 
            "%://www.$site_domain",
            "%://www.$site_domain/%"
        ]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Обновляем существующий
            $stmt = $pdo->prepare("
                UPDATE sites 
                SET site_name = ?, active_modules = ?, status = 'online', last_seen = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$site_name, $active_modules, $existing['id']]);
            $site_id = $existing['id'];
        } else {
            // Создаем новый
            $stmt = $pdo->prepare("
                INSERT INTO sites (site_url, site_name, active_modules, status, created_at, last_seen) 
                VALUES (?, ?, ?, 'online', NOW(), NOW())
            ");
            $stmt->execute([$site_url, $site_name, $active_modules]);
            $site_id = $pdo->lastInsertId();
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Site registered successfully',
            'site_id' => $site_id
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Удаление плагина и перемещение сайта в корзину
 */
function handle_delete_plugin() {
    global $pdo;
    
    $site_id = $_POST['site_id'] ?? '';
    
    if (empty($site_id)) {
        echo json_encode(['success' => false, 'error' => 'Site ID required']);
        return;
    }
    
    try {
        // Получаем информацию о сайте
        $stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ?");
        $stmt->execute([$site_id]);
        $site = $stmt->fetch();
        
        if (!$site) {
            echo json_encode(['success' => false, 'error' => 'Site not found']);
            return;
        }
        
        // Добавляем запись в корзину
        $stmt = $pdo->prepare("
            INSERT INTO trash (site_url, site_name, active_modules, original_site_id, deleted_at, deleted_reason) 
            VALUES (?, ?, ?, ?, NOW(), 'plugin_deleted')
        ");
        $stmt->execute([
            $site['site_url'], 
            $site['site_name'], 
            $site['active_modules'], 
            $site['id']
        ]);
        
        // Удаляем сайт из активных
        $stmt = $pdo->prepare("DELETE FROM sites WHERE id = ?");
        $stmt->execute([$site_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Plugin deleted and site moved to trash',
            'site_url' => $site['site_url']
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Получение списка удаленных сайтов (корзина)
 */
function handle_get_trash() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM trash ORDER BY deleted_at DESC");
        $trash_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'trash_items' => $trash_items,
            'total' => count($trash_items)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Ежедневный пинг от сайта (fallback механизм)
 */
function handle_daily_ping() {
    global $pdo;
    
    $site_url = $_POST['site_url'] ?? '';
    $status = $_POST['status'] ?? '';
    
    if (empty($site_url)) {
        echo json_encode(['success' => false, 'error' => 'Site URL required']);
        return;
    }
    
    $site_domain = normalize_url_to_domain($site_url);
    
    try {
        // Обновляем время последней активности для домена
        $stmt = $pdo->prepare("
            UPDATE sites 
            SET status = 'online', last_seen = NOW() 
            WHERE site_url LIKE ? OR site_url LIKE ? OR site_url LIKE ? OR site_url LIKE ?
        ");
        $stmt->execute([
            "%://$site_domain",
            "%://$site_domain/%", 
            "%://www.$site_domain",
            "%://www.$site_domain/%"
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Daily ping received'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Site not found for domain: ' . $site_domain
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Удаление сайта из дашборда (без затрагивания самого сайта)
 */
function handle_remove_from_dashboard() {
    global $pdo;
    
    $site_id = $_POST['site_id'] ?? '';
    
    if (empty($site_id)) {
        echo json_encode(['success' => false, 'error' => 'Site ID required']);
        return;
    }
    
    try {
        // Получаем информацию о сайте
        $stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ?");
        $stmt->execute([$site_id]);
        $site = $stmt->fetch();
        
        if (!$site) {
            echo json_encode(['success' => false, 'error' => 'Site not found']);
            return;
        }
        
        // Добавляем запись в корзину
        $stmt = $pdo->prepare("
            INSERT INTO trash (site_url, site_name, active_modules, original_site_id, deleted_at, deleted_reason) 
            VALUES (?, ?, ?, ?, NOW(), 'removed_from_dashboard')
        ");
        $stmt->execute([
            $site['site_url'], 
            $site['site_name'], 
            $site['active_modules'], 
            $site['id']
        ]);
        
        // Удаляем сайт из активных
        $stmt = $pdo->prepare("DELETE FROM sites WHERE id = ?");
        $stmt->execute([$site_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Site removed from dashboard successfully',
            'site_url' => $site['site_url']
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Регистрация сайта с установкой безопасной связи
 * Этап 1: Базовая защита
 */
function handle_register_site() {
    global $pdo, $security;
    
    $site_url = $_POST['site_url'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';
    $site_title = $_POST['site_title'] ?? '';
    $plugin_version = $_POST['plugin_version'] ?? '';
    $temp_key = $_POST['temp_key'] ?? '';
    
    if (empty($site_url)) {
        echo json_encode(['success' => false, 'error' => 'Site URL required']);
        return;
    }
    
    if (empty($temp_key)) {
        echo json_encode(['success' => false, 'error' => 'Temp key required for registration']);
        return;
    }
    
    try {
        // Нормализуем URL к домену для унифицированной идентификации
        $site_domain = normalize_url_to_domain($site_url);
        
        // Проверяем, существует ли уже сайт с таким доменом (не по точному URL!)
        $stmt = $pdo->prepare("SELECT * FROM sites WHERE site_url LIKE ? OR site_url LIKE ? OR site_url LIKE ? OR site_url LIKE ? LIMIT 1");
        $stmt->execute([
            "%://$site_domain",
            "%://$site_domain/%", 
            "%://www.$site_domain",
            "%://www.$site_domain/%"
        ]);
        $existing_site = $stmt->fetch();
        
        // Генерируем конфигурацию безопасности
        $registration_data = $security->register_plugin([
            'site_url' => $site_url,
            'admin_email' => $admin_email,
            'site_title' => $site_title,
            'plugin_version' => $plugin_version
        ]);
        
        if ($existing_site) {
            // Обновляем существующий сайт
            $stmt = $pdo->prepare("UPDATE sites SET site_url = ?, site_name = ?, admin_email = ?, status = 'online', api_key = ?, api_key_hash = ?, last_seen = ?, date_added = ? WHERE id = ?");
            $stmt->execute([
                $site_url,
                $site_title ?: parse_url($site_url, PHP_URL_HOST),
                $admin_email,
                $registration_data['api_key'],
                $registration_data['api_key_hash'],
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s'),
                $existing_site['id']
            ]);
            $site_id = $existing_site['id'];
            error_log("NEETRINO Registration: Updated existing site ID {$site_id} for URL {$site_url}");
        } else {
            // Создаем новый сайт
            $stmt = $pdo->prepare("INSERT INTO sites (site_url, site_name, admin_email, status, api_key, api_key_hash, last_seen, date_added) VALUES (?, ?, ?, 'online', ?, ?, ?, ?)");
            $stmt->execute([
                $site_url,
                $site_title ?: parse_url($site_url, PHP_URL_HOST),
                $admin_email,
                $registration_data['api_key'],
                $registration_data['api_key_hash'],
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s')
            ]);
            $site_id = $pdo->lastInsertId();
            error_log("NEETRINO Registration: Created new site ID {$site_id} for URL {$site_url}");
        }
        
        // STAGE 2: Логируем регистрацию
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $security->log_security_event($site_id, $client_ip, 'site_registration', [
            'site_url' => $site_url,
            'admin_email' => $admin_email,
            'existing_site' => $existing_site ? true : false
        ], true);
        
        // Если версия плагина передана — сразу сохраним её в site_versions
        try {
            if (!empty($plugin_version)) {
                // Убедимся, что таблица существует
                $pdo->exec("CREATE TABLE IF NOT EXISTS site_versions (
                    site_id INT NOT NULL PRIMARY KEY,
                    plugin_version VARCHAR(50) NOT NULL,
                    last_seen_at DATETIME NOT NULL,
                    source ENUM('push','pull') DEFAULT 'push',
                    signature_ok TINYINT(1) DEFAULT 1,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    CONSTRAINT fk_site_versions_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                // Вставим или обновим версию для этого сайта
                $stmt = $pdo->prepare("INSERT INTO site_versions (site_id, plugin_version, last_seen_at, source, signature_ok)
                    VALUES (?, ?, NOW(), 'push', 1)
                    ON DUPLICATE KEY UPDATE plugin_version = VALUES(plugin_version), last_seen_at = NOW(), source = 'push', signature_ok = 1");
                $stmt->execute([$site_id, $plugin_version]);
            }
        } catch (Exception $e) {
            error_log('NEETRINO Registration: failed to persist plugin_version on register: ' . $e->getMessage());
        }

        // Отправляем конфигурацию обратно плагину
        $config_data = [
            'dashboard_ip' => $registration_data['dashboard_ip'],
            'dashboard_domain' => $registration_data['dashboard_domain'],
            'api_key' => $registration_data['api_key'],
            'temp_key' => $temp_key,
            'registration_status' => 'registered'
        ];
        
        // Пытаемся отправить конфигурацию плагину
        $config_sent = false;
        $config_url = rtrim($site_url, '/') . '/wp-json/neetrino/v1/update-dashboard-config';
        
        // Отправляем данные как form-data
        $response = @file_get_contents($config_url, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($config_data),
                'timeout' => 10
            ]
        ]));
        
        if ($response !== false) {
            $config_sent = true;
            error_log("NEETRINO Dashboard: Configuration sent successfully to plugin");
        } else {
            error_log("NEETRINO Dashboard: Failed to send configuration to plugin");
        }
        
        // Отвечаем успехом независимо от отправки конфигурации
        $response_data = [
            'status' => 'success',
            'message' => 'Site registered successfully',
            'dashboard_ip' => $registration_data['dashboard_ip'],
            'dashboard_domain' => $registration_data['dashboard_domain'],
            'api_key' => $registration_data['api_key'],
            'config_sent' => $config_sent
        ];
        
        echo json_encode($response_data);
        
        error_log("NEETRINO Security: Site registered - " . $site_url . " with IP " . $registration_data['dashboard_ip']);
        
        // Отправляем конфигурацию напрямую плагину
        $plugin_config_url = rtrim($site_url, '/') . '/wp-json/neetrino/v1/update-dashboard-config';
        
        $config_data = [
            'dashboard_ip' => $registration_data['dashboard_ip'],
            'dashboard_domain' => $registration_data['dashboard_domain'],
            'api_key' => $registration_data['api_key'],
            'temp_key' => $temp_key
        ];
        
        $config_response = @file_get_contents($plugin_config_url, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($config_data),
                'timeout' => 15
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]));
        
        if ($config_response === false) {
            error_log("NEETRINO: Failed to send config to plugin: Connection failed");
        } else {
            error_log("NEETRINO: Configuration sent to plugin successfully");
        }
        
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Registration failed: ' . $e->getMessage()
        ]);
    }
}

/**
 * Обработка ping от сайта
 */
function handle_ping() {
    $site_url = $_POST['site_url'] ?? '';
    
    if (empty($site_url)) {
        echo json_encode(['success' => false, 'error' => 'Site URL required']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Pong! Dashboard is working',
        'timestamp' => time()
    ]);
}

/**
 * Восстановление сайта из корзины
 */
function handle_restore_site() {
    global $pdo;
    
    $trash_id = $_POST['trash_id'] ?? '';
    
    if (empty($trash_id)) {
        echo json_encode(['success' => false, 'error' => 'Trash ID required']);
        return;
    }
    
    try {
        // Получаем данные из корзины
        $stmt = $pdo->prepare("SELECT * FROM trash WHERE id = ?");
        $stmt->execute([$trash_id]);
        $trash_item = $stmt->fetch();
        
        if (!$trash_item) {
            echo json_encode(['success' => false, 'error' => 'Item not found in trash']);
            return;
        }
        
        // Проверяем, нет ли уже такого сайта по домену
        $site_domain = normalize_url_to_domain($trash_item['site_url']);
        $stmt = $pdo->prepare("SELECT id FROM sites WHERE site_url LIKE ? OR site_url LIKE ? OR site_url LIKE ? OR site_url LIKE ? LIMIT 1");
        $stmt->execute([
            "%://$site_domain",
            "%://$site_domain/%", 
            "%://www.$site_domain",
            "%://www.$site_domain/%"
        ]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Site already exists']);
            return;
        }
        
        // Восстанавливаем сайт
        $stmt = $pdo->prepare("
            INSERT INTO sites (site_url, site_name, admin_email, status, date_added, last_seen) 
            VALUES (?, ?, ?, 'offline', NOW(), NULL)
        ");
        
        $stmt->execute([
            $trash_item['site_url'],
            $trash_item['site_name'],
            'unknown@domain.com' // Временный email
        ]);
        
        // Удаляем из корзины
        $stmt = $pdo->prepare("DELETE FROM trash WHERE id = ?");
        $stmt->execute([$trash_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Site restored successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Восстановление всех сайтов из корзины
 */
function handle_restore_all_sites() {
    global $pdo;
    
    try {
        // Получаем все элементы из корзины
        $stmt = $pdo->query("SELECT * FROM trash ORDER BY deleted_at DESC");
        $trash_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($trash_items)) {
            echo json_encode(['success' => false, 'error' => 'Trash is empty']);
            return;
        }
        
        $restored = 0;
        $skipped = 0;
        
        foreach ($trash_items as $item) {
            // Проверяем, нет ли уже такого сайта по домену
            $site_domain = normalize_url_to_domain($item['site_url']);
            $stmt = $pdo->prepare("SELECT id FROM sites WHERE site_url LIKE ? OR site_url LIKE ? OR site_url LIKE ? OR site_url LIKE ? LIMIT 1");
            $stmt->execute([
                "%://$site_domain",
                "%://$site_domain/%", 
                "%://www.$site_domain",
                "%://www.$site_domain/%"
            ]);
            
            if ($stmt->fetch()) {
                $skipped++;
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
                'unknown@domain.com' // Временный email
            ]);
            
            // Удаляем из корзины
            $stmt = $pdo->prepare("DELETE FROM trash WHERE id = ?");
            $stmt->execute([$item['id']]);
            
            $restored++;
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Restored $restored sites, skipped $skipped existing",
            'restored' => $restored,
            'skipped' => $skipped
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Обновление статуса сайта при успешных командах
 */
function handle_update_site_status() {
    global $pdo;
    
    $site_url = $_POST['site_url'] ?? '';
    $status = $_POST['status'] ?? 'online';
    
    if (empty($site_url)) {
        echo json_encode(['success' => false, 'error' => 'Site URL required']);
        return;
    }
    
    $site_domain = normalize_url_to_domain($site_url);
    
    try {
        // Обновляем статус и время последней активности для домена
        $stmt = $pdo->prepare("
            UPDATE sites 
            SET status = ?, last_seen = NOW() 
            WHERE site_url LIKE ? OR site_url LIKE ? OR site_url LIKE ? OR site_url LIKE ?
        ");
        $stmt->execute([
            $status,
            "%://$site_domain",
            "%://$site_domain/%", 
            "%://www.$site_domain",
            "%://www.$site_domain/%"
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Site status updated successfully',
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Site not found for domain: ' . $site_domain
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Приём пуша версии плагина с сайта
 */
function handle_plugin_version_push() {
    global $pdo;

    error_log("NEETRINO Dashboard: handle_plugin_version_push() вызван");
    error_log("NEETRINO Dashboard: POST данные: " . json_encode($_POST));

    $site_url = $_POST['site_url'] ?? '';
    $plugin_version = $_POST['plugin_version'] ?? '';
    $api_key = $_POST['api_key'] ?? '';

    // Логируем получение версии
    error_log("NEETRINO Dashboard: Received version push - URL: $site_url, Version: $plugin_version");

    if (empty($site_url) || empty($plugin_version)) {
        error_log("NEETRINO Dashboard: Version push failed - missing required fields");
        echo json_encode(['success' => false, 'error' => 'site_url and plugin_version are required']);
        return;
    }

    $site_domain = normalize_url_to_domain($site_url);

    try {
        // Находим сайт по домену и валидируем api_key как базовую защиту на первом этапе
        $stmt = $pdo->prepare("SELECT id, api_key FROM sites WHERE site_url LIKE ? OR site_url LIKE ? OR site_url LIKE ? OR site_url LIKE ? LIMIT 1");
        $stmt->execute([
            "%://$site_domain",
            "%://$site_domain/%", 
            "%://www.$site_domain",
            "%://www.$site_domain/%"
        ]);
        $site = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$site) {
            echo json_encode(['success' => false, 'error' => 'Site not found for domain: ' . $site_domain]);
            return;
        }

        if (!empty($site['api_key']) && !hash_equals($site['api_key'], $api_key)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        // Обеспечим таблицу для хранения версий (минимальная миграция на лету)
        $pdo->exec("CREATE TABLE IF NOT EXISTS site_versions (
            site_id INT NOT NULL PRIMARY KEY,
            plugin_version VARCHAR(50) NOT NULL,
            last_seen_at DATETIME NOT NULL,
            source ENUM('push','pull') DEFAULT 'push',
            signature_ok TINYINT(1) DEFAULT 1,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_site_versions_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Upsert версию
        $stmt = $pdo->prepare("INSERT INTO site_versions (site_id, plugin_version, last_seen_at, source, signature_ok)
            VALUES (?, ?, NOW(), 'push', 1)
            ON DUPLICATE KEY UPDATE plugin_version = VALUES(plugin_version), last_seen_at = NOW(), source = 'push', signature_ok = 1");
        $stmt->execute([$site['id'], $plugin_version]);

        // Логируем успешное обновление версии
        error_log("NEETRINO Dashboard: Version updated successfully - Site ID: {$site['id']}, Version: $plugin_version");

        echo json_encode(['success' => true, 'message' => 'Version updated', 'site_id' => $site['id'], 'plugin_version' => $plugin_version]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Failed to store version: ' . $e->getMessage()]);
    }
}

/**
 * Запрос версии плагина с сайта (pull) через REST статус
 */
function handle_plugin_version_pull() {
    global $pdo;

    $site_id = $_POST['site_id'] ?? $_GET['site_id'] ?? '';

    if (empty($site_id)) {
        echo json_encode(['success' => false, 'error' => 'site_id required']);
        return;
    }

    try {
        // Берём данные сайта
        $stmt = $pdo->prepare("SELECT id, site_url, api_key FROM sites WHERE id = ? LIMIT 1");
        $stmt->execute([$site_id]);
        $site = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$site) {
            echo json_encode(['success' => false, 'error' => 'Site not found']);
            return;
        }

        // Формируем URL REST статуса
        $status_url = rtrim($site['site_url'], '/') . '/wp-json/neetrino/v1/status';

        // Готовим контекст запроса с таймаутом
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 12,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ]);

        $response = @file_get_contents($status_url, false, $context);
        if ($response === false) {
            echo json_encode(['success' => false, 'error' => 'Failed to fetch status from site']);
            return;
        }

        $json = json_decode($response, true);
        if (!is_array($json) || empty($json['success'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid response from site']);
            return;
        }

        $plugin_version = $json['data']['plugin_version'] ?? null;
        if (empty($plugin_version)) {
            echo json_encode(['success' => false, 'error' => 'Plugin version not present in response']);
            return;
        }

        // Обеспечим таблицу
        $pdo->exec("CREATE TABLE IF NOT EXISTS site_versions (
            site_id INT NOT NULL PRIMARY KEY,
            plugin_version VARCHAR(50) NOT NULL,
            last_seen_at DATETIME NOT NULL,
            source ENUM('push','pull') DEFAULT 'pull',
            signature_ok TINYINT(1) DEFAULT 1,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_site_versions_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Upsert
        $stmt = $pdo->prepare("INSERT INTO site_versions (site_id, plugin_version, last_seen_at, source, signature_ok)
            VALUES (?, ?, NOW(), 'pull', 1)
            ON DUPLICATE KEY UPDATE plugin_version = VALUES(plugin_version), last_seen_at = NOW(), source = 'pull', signature_ok = 1");
        $stmt->execute([$site['id'], $plugin_version]);

        echo json_encode(['success' => true, 'message' => 'Version pulled', 'site_id' => $site['id'], 'plugin_version' => $plugin_version]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Failed to pull version: ' . $e->getMessage()]);
    }
}

// debug_log() функция определена в config.php
?>

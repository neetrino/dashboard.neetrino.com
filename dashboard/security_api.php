<?php
/**
 * STAGE 2: API для проверки хешированных ключей
 */

define('NEETRINO_DASHBOARD', true);
require_once 'config.php';
require_once 'includes/SecurityManager.php';

// Создаем SecurityManager
$security = new SecurityManager($pdo);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

switch ($action) {
    case 'verify_api_key':
        handle_verify_api_key();
        break;
    case 'get_security_stats':
        handle_get_security_stats();
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}

/**
 * STAGE 2: Проверка API ключа с хешированием
 */
function handle_verify_api_key() {
    global $pdo, $security;
    
    $api_key = $_POST['api_key'] ?? '';
    $site_url = $_POST['site_url'] ?? '';
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    if (empty($api_key)) {
        echo json_encode(['success' => false, 'error' => 'API key required']);
        return;
    }
    
    try {
        // Проверяем Rate Limiting
        if (!$security->check_rate_limit($client_ip)) {
            echo json_encode([
                'success' => false, 
                'error' => 'Rate limit exceeded',
                'retry_after' => 300
            ]);
            return;
        }
        
        // Ищем сайт по URL или API ключу (для обратной совместимости)
        $stmt = $pdo->prepare("SELECT * FROM sites WHERE site_url LIKE ? OR api_key = ? LIMIT 1");
        $stmt->execute(['%' . parse_url($site_url, PHP_URL_HOST) . '%', $api_key]);
        $site = $stmt->fetch();
        
        if (!$site) {
            $security->log_security_event(null, $client_ip, 'api_key_verification', [
                'api_key_prefix' => substr($api_key, 0, 10) . '...',
                'site_url' => $site_url
            ], false);
            
            echo json_encode(['success' => false, 'error' => 'Site not found']);
            return;
        }
        
        // Проверяем API ключ
        $is_valid = false;
        
        if (!empty($site['api_key_hash'])) {
            // STAGE 2: Проверка по хешу
            $is_valid = $security->verify_api_key($api_key, $site['api_key_hash']);
        } else {
            // STAGE 1: Обратная совместимость - прямое сравнение
            $is_valid = ($api_key === $site['api_key']);
        }
        
        if ($is_valid) {
            $security->log_security_event($site['id'], $client_ip, 'api_key_verification', [
                'site_url' => $site_url,
                'verification_method' => !empty($site['api_key_hash']) ? 'hash' : 'direct'
            ], true);
            
            echo json_encode([
                'success' => true,
                'site_id' => $site['id'],
                'site_name' => $site['site_name'],
                'security_level' => !empty($site['api_key_hash']) ? 'STAGE_2' : 'STAGE_1'
            ]);
        } else {
            $security->log_security_event($site['id'], $client_ip, 'api_key_verification', [
                'api_key_prefix' => substr($api_key, 0, 10) . '...',
                'site_url' => $site_url
            ], false);
            
            echo json_encode(['success' => false, 'error' => 'Invalid API key']);
        }
        
    } catch (Exception $e) {
        error_log("API key verification failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Verification failed: ' . $e->getMessage()]);
    }
}

/**
 * STAGE 2: Статистика безопасности
 */
function handle_get_security_stats() {
    global $pdo;
    
    try {
        // Статистика за последние 24 часа
        $stats = [];
        
        // Общее количество запросов
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM security_logs WHERE timestamp > datetime('now', '-1 day')");
        $stats['total_requests_24h'] = $stmt->fetchColumn();
        
        // Успешные запросы
        $stmt = $pdo->query("SELECT COUNT(*) as success FROM security_logs WHERE timestamp > datetime('now', '-1 day') AND success = 1");
        $stats['successful_requests_24h'] = $stmt->fetchColumn();
        
        // Заблокированные IP
        $stmt = $pdo->query("SELECT COUNT(DISTINCT ip_address) as blocked FROM rate_limits WHERE blocked_until > datetime('now')");
        $stats['blocked_ips'] = $stmt->fetchColumn();
        
        // Топ события
        $stmt = $pdo->query("SELECT event_type, COUNT(*) as count FROM security_logs WHERE timestamp > datetime('now', '-1 day') GROUP BY event_type ORDER BY count DESC LIMIT 5");
        $stats['top_events'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'stats' => $stats]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Failed to get stats']);
    }
}
?>

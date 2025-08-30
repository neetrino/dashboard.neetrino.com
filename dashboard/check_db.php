<?php
require_once 'config.php';

echo "Database connection test...\n";

try {
    // Check sites table
    $stmt = $pdo->query('SELECT * FROM sites ORDER BY date_added DESC LIMIT 10');
    $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($sites) . " sites:\n";
    foreach ($sites as $site) {
        echo "- ID: " . $site['id'] . ", URL: " . $site['site_url'] . ", Name: " . $site['site_name'] . ", Added: " . $site['date_added'] . "\n";
    }
    
    echo "\nChecking trash...\n";
    $stmt = $pdo->query('SELECT * FROM trash ORDER BY deleted_at DESC LIMIT 10');
    $trash = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($trash) . " items in trash:\n";
    foreach ($trash as $item) {
        echo "- ID: " . $item['id'] . ", URL: " . $item['site_url'] . ", Name: " . $item['site_name'] . ", Deleted: " . $item['deleted_at'] . ", Reason: " . $item['deleted_reason'] . "\n";
    }
    
    echo "\nChecking security logs...\n";
    $stmt = $pdo->query('SELECT * FROM security_logs ORDER BY timestamp DESC LIMIT 10');
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($logs) . " security log entries:\n";
    foreach ($logs as $log) {
        echo "- ID: " . $log['id'] . ", Site ID: " . ($log['site_id'] ?? 'NULL') . ", Event: " . $log['event_type'] . ", IP: " . $log['ip_address'] . ", Time: " . $log['timestamp'] . "\n";
    }

    echo "\nChecking site_versions (plugin versions)...\n";
    // Create the table if it's missing to avoid fatal on fresh DBs
    $pdo->exec("CREATE TABLE IF NOT EXISTS site_versions (
        site_id INT NOT NULL PRIMARY KEY,
        plugin_version VARCHAR(50) NOT NULL,
        last_seen_at DATETIME NOT NULL,
        source ENUM('push','pull') DEFAULT 'push',
        signature_ok TINYINT(1) DEFAULT 1,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_site_versions_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $stmt = $pdo->query('SELECT sv.site_id, s.site_name, s.site_url, sv.plugin_version, sv.last_seen_at, sv.source FROM site_versions sv LEFT JOIN sites s ON s.id = sv.site_id ORDER BY sv.last_seen_at DESC LIMIT 20');
    $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($versions) . " plugin version rows:\n";
    foreach ($versions as $row) {
        echo "- Site ID: {$row['site_id']} | Name: {$row['site_name']} | URL: {$row['site_url']} | Version: {$row['plugin_version']} | Seen: {$row['last_seen_at']} | Source: {$row['source']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

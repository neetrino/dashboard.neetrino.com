-- Neetrino Dashboard MySQL Database Schema - Simplified
-- Версия: 3.0 MySQL Migration (Simplified)
-- Автор: Neetrino Team

-- Таблица администраторов
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица управляемых сайтов
CREATE TABLE IF NOT EXISTS sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_url VARCHAR(255) NOT NULL,
    site_name VARCHAR(100) NOT NULL,
    admin_email VARCHAR(100) NOT NULL,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    api_key_hash VARCHAR(255) NOT NULL,
    dashboard_ip VARCHAR(45) NULL,
    date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
    active_modules TEXT NULL,
    status ENUM('online', 'offline', 'suspended', 'maintenance') DEFAULT 'offline',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_seen DATETIME NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_site_url (site_url),
    INDEX idx_api_key (api_key),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_last_seen (last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица ограничений запросов (Rate Limiting)
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    site_id INT NULL,
    request_count INT DEFAULT 1,
    last_request DATETIME DEFAULT CURRENT_TIMESTAMP,
    blocked_until DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_ip_address (ip_address),
    INDEX idx_site_id (site_id),
    INDEX idx_last_request (last_request),
    INDEX idx_blocked_until (blocked_until),
    
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица логов безопасности
CREATE TABLE IF NOT EXISTS security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NULL,
    ip_address VARCHAR(45) NOT NULL,
    event_type ENUM('login_attempt', 'api_call', 'rate_limit', 'suspicious_activity', 'admin_action') NOT NULL,
    event_data JSON NULL,
    success BOOLEAN DEFAULT FALSE,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_agent TEXT NULL,
    
    INDEX idx_site_id (site_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_event_type (event_type),
    INDEX idx_timestamp (timestamp),
    INDEX idx_success (success),
    
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица корзины (удаленные записи)
CREATE TABLE IF NOT EXISTS trash (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_url VARCHAR(255) NOT NULL,
    site_name VARCHAR(100) NOT NULL,
    active_modules TEXT NULL,
    original_site_id INT NULL,
    deleted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_reason VARCHAR(100) DEFAULT 'plugin_deleted',
    deleted_by_admin_id INT NULL,
    restore_data JSON NULL,
    
    INDEX idx_deleted_at (deleted_at),
    INDEX idx_original_site_id (original_site_id),
    INDEX idx_deleted_by_admin_id (deleted_by_admin_id),
    
    FOREIGN KEY (deleted_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица настроек системы
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставка базовых настроек системы (с защитой от дублирования)
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('system_version', '3.0', 'string', 'Версия системы Neetrino Dashboard', TRUE),
('installation_date', NOW(), 'string', 'Дата установки системы', FALSE),
('maintenance_mode', 'false', 'boolean', 'Режим технического обслуживания', FALSE),
('max_sites_per_dashboard', '100', 'integer', 'Максимальное количество сайтов на один дашборд', FALSE),
('rate_limit_per_minute', '60', 'integer', 'Лимит запросов в минуту для API', FALSE),
('session_timeout', '86400', 'integer', 'Время жизни сессии в секундах (24 часа)', FALSE),
('auto_cleanup_logs_days', '30', 'integer', 'Автоматическая очистка логов старше N дней', FALSE);

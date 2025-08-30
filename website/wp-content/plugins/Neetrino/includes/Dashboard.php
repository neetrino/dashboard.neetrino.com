<?php
/**
 * Neetrino Dashboard Renderer
 * 
 * @package Neetrino
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Dashboard {
    
    /**
     * Render dashboard page
     */
    public static function render() {
        if (!current_user_can('administrator')) {
            return;
        }
        
        // Обработка параметров reconnect
        if (isset($_GET['reconnect'])) {
            if ($_GET['reconnect'] === 'success') {
                $site_id = isset($_GET['site_id']) ? sanitize_text_field($_GET['site_id']) : '';
                echo '<div class="notice notice-success is-dismissible"><p>';
                echo '<strong>Успешно!</strong> Сайт перерегистрирован в Dashboard';
                if ($site_id) {
                    echo ' с ID: ' . esc_html($site_id);
                }
                echo '</p></div>';
            } elseif ($_GET['reconnect'] === 'error') {
                echo '<div class="notice notice-error is-dismissible"><p>';
                echo '<strong>Ошибка!</strong> Не удалось перерегистрировать сайт в Dashboard. ';
                echo 'Проверьте что Dashboard доступен по адресу: ' . esc_html(Neetrino_Dashboard_Connect::DASHBOARD_URL);
                echo '</p></div>';
            }
        }
        
        $plugin_data = get_plugin_data(NEETRINO_PLUGIN_FILE);
        $version = $plugin_data['Version'];
        ?>
        <div class="wrap neetrino-dashboard">
            <h1 style="display: none;">Neetrino Dashboard</h1>
            
            <?php self::render_header($version); ?>
            <?php self::render_update_notice(); ?>
            <?php self::render_modules_section(); ?>
            <?php self::render_support_section(); ?>
            <?php self::render_documentation_modal(); ?>
        </div>
        <?php
    }
    
    /**
     * Render header section
     */
    private static function render_header($version) {
        ?>
        <div class="neetrino-header">
            <div class="neetrino-header-left">
                <div>
                    <h1>
                        <img src="<?php echo esc_url(plugin_dir_url(NEETRINO_PLUGIN_FILE) . 'includes/Neetrino-Logo.png'); ?>" alt="Neetrino Logo" class="neetrino-header-logo">
                    </h1>
                </div>
            </div>
            <div class="neetrino-header-right">
                <?php self::render_update_buttons($version); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render update buttons
     */
    private static function render_update_buttons($version) {
        ?>
        <div class="neetrino-header-actions">
            <?php if (get_transient('neetrino_update_available')): ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="action-form">
                <?php wp_nonce_field('neetrino_direct_update'); ?>
                <input type="hidden" name="action" value="neetrino_direct_update">
                <button type="submit" class="neetrino-btn neetrino-btn-update">
                    <span class="dashicons dashicons-download"></span>
                    <span><?php echo esc_html__('Update Now', 'neetrino'); ?></span>
                </button>
            </form>
            <?php endif; ?>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="action-form">
                <?php wp_nonce_field('neetrino_check_update'); ?>
                <input type="hidden" name="action" value="neetrino_check_update">
                <button type="submit" class="neetrino-btn neetrino-btn-with-version">
                    <div class="neetrino-btn-content">
                        <span class="dashicons dashicons-update"></span>
                        <span><?php echo esc_html__('Check for Updates', 'neetrino'); ?></span>
                    </div>
                    <div class="neetrino-version-badge-inline">
                        <?php echo sprintf(esc_html__('v%s', 'neetrino'), esc_html($version)); ?>
                    </div>
                </button>
            </form>
            
            <!-- Documentation Button -->
            <button type="button" class="neetrino-btn neetrino-btn-secondary" id="neetrino-docs-btn">
                <i class="fa-solid fa-book"></i>
                <span><?php echo esc_html__('Documentation', 'neetrino'); ?></span>
            </button>
            
            <!-- Dashboard Connection Status -->
            <?php self::render_dashboard_connection_status(); ?>
        </div>
        <?php
    }
    
    /**
     * Render update notice
     */
    private static function render_update_notice() {
        if (get_transient('neetrino_update_available')):
        ?>
        <div class="neetrino-update-available">
            <p>
                <strong><?php echo esc_html__('Update Available!', 'neetrino'); ?></strong>
                <?php echo esc_html__('A new version of Neetrino is available. Update now to get the latest features and improvements.', 'neetrino'); ?>
            </p>
        </div>
        <?php
        endif;
    }
    
    /**
     * Render modules section
     */
    private static function render_modules_section() {
        ?>
        <div class="neetrino-card">
            <h2><?php echo esc_html__('Available Modules', 'neetrino'); ?></h2>
            
            <div class="neetrino-modules-grid">
                <?php self::render_modules(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render modules grid
     */
    private static function render_modules() {
        $sorted_modules = Neetrino::get_sorted_modules();
        $has_active = false;
        $shown_inactive_header = false;
        
        // Определяем есть ли активные модули
        foreach ($sorted_modules as $slug => $module) {
            if ($module['active']) {
                $has_active = true;
                break;
            }
        }
        
        // Рендерим модули
        foreach ($sorted_modules as $slug => $module) {
            // Показываем заголовок неактивных модулей только один раз
            if (!$module['active'] && !$shown_inactive_header) {
                $shown_inactive_header = true;
                if ($has_active) {
                    echo '<div class="neetrino-modules-section-header inactive"><h3>' . esc_html__('Inactive Modules', 'neetrino') . '</h3></div>';
                }
            }
            
            self::render_module_card($slug, $module);
        }
    }
    
    /**
     * Render single module card
     */
    private static function render_module_card($slug, $module) {
        $config = Neetrino_Module_Config::get_module_config($slug);
        $is_active = $module['active'];
        ?>
        <div class="neetrino-module-card <?php echo esc_attr($config['css_class']); ?> <?php echo $is_active ? '' : 'inactive'; ?>" style="<?php echo Neetrino_Module_Config::get_module_css_vars($slug); ?>">
            <!-- Визуальный номер модуля -->
            <div class="neetrino-module-number">
                <?php echo esc_html(Neetrino::get_module_number($slug)); ?>
            </div>
            
            <!-- Переключатель активации модуля -->
            <div class="neetrino-module-toggle-container">
                <label class="neetrino-toggle-switch">
                    <input type="checkbox" class="neetrino-module-toggle" data-module="<?php echo esc_attr($slug); ?>" <?php checked($is_active); ?>>
                    <span class="neetrino-toggle-slider"></span>
                </label>
            </div>
            
            <div class="neetrino-module-icon">
                <?php echo Neetrino_Module_Config::get_module_icon_html($slug); ?>
            </div>
            <div class="neetrino-module-title">
                <?php echo esc_html($module['title']); ?>
            </div>
            <div class="neetrino-module-description">
                <?php echo esc_html($config['description']); ?>
            </div>
            
            <?php if ($is_active): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=neetrino_' . $slug)); ?>" class="neetrino-btn neetrino-module-btn">
                <?php echo esc_html__('Settings', 'neetrino'); ?>
            </a>
            <?php else: ?>
            <div class="neetrino-btn neetrino-module-btn" style="opacity: 0.5; cursor: not-allowed;">
                <?php echo esc_html__('Disabled', 'neetrino'); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render support section
     */
    private static function render_support_section() {
        ?>
        <div class="neetrino-card neetrino-support-card">
            <div class="neetrino-support-content">
                <div class="neetrino-support-info">
                    <h3><?php echo esc_html__('Support', 'neetrino'); ?></h3>
                    <p><?php echo esc_html__('Need help with Neetrino?', 'neetrino'); ?></p>
                </div>
                <a href="mailto:support@neetrino.com" class="neetrino-btn neetrino-btn-secondary neetrino-compact-btn">
                    <span class="dashicons dashicons-email" style="margin-right: 5px;"></span>
                    <?php echo esc_html__('Contact Support', 'neetrino'); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render documentation modal
     */
    private static function render_documentation_modal() {
        ?>
        <!-- Documentation Modal -->
        <div id="neetrino-docs-modal" class="neetrino-modal" style="display: none;">
            <div class="neetrino-modal-content">
                <div class="neetrino-modal-header">
                    <h2><i class="fa-solid fa-book"></i> <?php echo esc_html__('Neetrino Documentation', 'neetrino'); ?></h2>
                    <button type="button" class="neetrino-modal-close" id="neetrino-docs-close">&times;</button>
                </div>
                <div class="neetrino-modal-body">
                    <div class="neetrino-docs-section">
                        <h3><i class="fa-solid fa-icons"></i> <?php echo esc_html__('Module Icons', 'neetrino'); ?></h3>
                        
                        <div class="neetrino-docs-info-box">
                            <h4><?php echo esc_html__('Icon Source', 'neetrino'); ?></h4>
                            <p>
                                <i class="fa-solid fa-external-link-alt"></i>
                                <strong><?php echo esc_html__('Font Awesome Icons:', 'neetrino'); ?></strong>
                                <a href="https://fontawesome.com/icons" target="_blank" rel="noopener noreferrer">
                                    https://fontawesome.com/icons
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="neetrino-modal-footer">
                    <button type="button" class="neetrino-btn neetrino-btn-secondary" id="neetrino-docs-close-footer">
                        <?php echo esc_html__('Close', 'neetrino'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render dashboard connection status
     */
    private static function render_dashboard_connection_status() {
        // Проверяем есть ли класс Dashboard_Connect
        if (!class_exists('Neetrino_Dashboard_Connect')) {
            return;
        }
        
        $connection_info = Neetrino_Dashboard_Connect::get_connection_info();
        $is_registered = $connection_info['registered'];
        $site_id = isset($connection_info['site_id']) ? $connection_info['site_id'] : get_option('neetrino_site_id', '');
        ?>
        <div class="neetrino-dashboard-connection">
            <!-- Единая компактная кнопка статуса -->
            <button type="button" class="neetrino-dashboard-btn <?php echo $is_registered ? 'connected' : 'disconnected'; ?>" 
                    onclick="toggleDashboardStatus(this)">
                <span class="status-icon">
                    <?php if ($is_registered): ?>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                        </svg>
                    <?php else: ?>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    <?php endif; ?>
                </span>
                <span class="status-text">
                    <?php echo $is_registered ? __('Connected', 'neetrino') : __('Disconnected', 'neetrino'); ?>
                </span>
                <span class="status-arrow">
                    <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M7 10l5 5 5-5z"/>
                    </svg>
                </span>
            </button>
            
            <!-- Скрытое меню -->
            <div class="neetrino-dashboard-dropdown" style="display: none;">
                <?php if ($is_registered && $site_id): ?>
                    <div class="dashboard-info">
                        <small class="site-id">ID: <?php echo esc_html($site_id); ?></small>
                        <span class="connection-dot"></span>
                        <small class="connection-time">Active</small>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="dashboard-action-form">
                    <?php wp_nonce_field('neetrino_dashboard_reconnect'); ?>
                    <input type="hidden" name="action" value="neetrino_dashboard_reconnect">
                    <button type="submit" class="dashboard-action-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
                        </svg>
                        <?php echo $is_registered ? __('Reconnect', 'neetrino') : __('Connect', 'neetrino'); ?>
                    </button>
                </form>
            </div>
        </div>
        
        <script>
        function toggleDashboardStatus(button) {
            const dropdown = button.nextElementSibling;
            const isVisible = dropdown.style.display !== 'none';
            
            // Закрываем все другие дропдауны
            document.querySelectorAll('.neetrino-dashboard-dropdown').forEach(d => {
                d.style.display = 'none';
            });
            document.querySelectorAll('.neetrino-dashboard-btn').forEach(b => {
                b.classList.remove('active');
            });
            
            if (!isVisible) {
                dropdown.style.display = 'block';
                button.classList.add('active');
            }
        }
        
        // Закрываем дропдаун при клике вне его
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.neetrino-dashboard-connection')) {
                document.querySelectorAll('.neetrino-dashboard-dropdown').forEach(d => {
                    d.style.display = 'none';
                });
                document.querySelectorAll('.neetrino-dashboard-btn').forEach(b => {
                    b.classList.remove('active');
                });
            }
        });
        </script>
        <?php
    }
}

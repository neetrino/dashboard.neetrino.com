<?php
/**
 * Admin Interface for Remote Control Module
 * 
 * Создает интерфейс идентичный Bitrix24 модулю
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Рендерит админ интерфейс для Remote Control
 */
function remote_control_render_admin_interface() {
    // Получаем экземпляр API
    $api = new Remote_Control_API();
    
    $key_exists = $api->key_exists();
    $new_key = '';
    $show_api_panel = false;
    
    // Проверяем, есть ли новый ключ для отображения
    $new_key_transient = get_transient('remote_control_new_key');
    if ($new_key_transient) {
        $new_key = $new_key_transient;
        delete_transient('remote_control_new_key');
        echo "<script>window.keepApiPanelOpen = true;</script>";
    }
    
    // Обработка форм
    if (isset($_POST['remote_control_create_key']) && check_admin_referer('remote_control_save')) {
        $new_key = $api->generate_secure_api_key();
        $key_exists = true;
        set_transient('remote_control_new_key', $new_key, 60);
        echo "<script>window.keepApiPanelOpen = true;</script>";
    }
    
    if (isset($_POST['remote_control_regenerate_key']) && check_admin_referer('remote_control_save')) {
        $new_key = $api->generate_secure_api_key();
        $key_exists = true;
        set_transient('remote_control_new_key', $new_key, 60);
        echo "<script>window.keepApiPanelOpen = true;</script>";
    }
    
    if (isset($_POST['remote_control_delete_key']) && check_admin_referer('remote_control_save')) {
        $api->delete_key();
        $key_exists = false;
        echo "<script>window.keepApiPanelOpen = true;</script>";
    }
    
    // Для безопасности не показываем реальные ключи в примерах
    $display_key = 'YOUR_SECURE_API_KEY_HERE';
    ?>
    
    <div class="wrap">
        <h1 class="wp-heading-inline" style="display: none;">Remote Control</h1>
        <?php do_action('admin_notices'); ?>
        
        <style>
            /* Основные стили для страницы - точная копия Bitrix24 */
            .remote-control-page {
                width: 100%;
                margin: 20px 0;
                position: relative;
            }
            
            .remote-control-card {
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                margin-bottom: 20px;
                border-top: 4px solid #8b4513;
                transition: all 0.3s ease;
                overflow: hidden;
            }
            
            .remote-control-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            }
            
            .remote-control-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 20px 25px;
                background: linear-gradient(135deg, #fff 0%, #f9f9f9 100%);
                border-bottom: 1px solid #e9ecef;
            }
            
            .remote-control-header h2 {
                margin: 0;
                color: #1d2327;
                font-size: 18px;
                font-weight: 600;
                display: flex;
                align-items: center;
            }
            
            .remote-control-header h2 .dashicons {
                margin-right: 8px;
                color: #8b4513;
            }
            
            .remote-control-actions {
                display: flex;
                gap: 10px;
                align-items: center;
            }
            
            .remote-control-btn {
                display: inline-flex;
                align-items: center;
                padding: 8px 16px;
                background: #f0f0f1;
                border: 1px solid #c3c4c7;
                border-radius: 6px;
                color: #1d2327;
                text-decoration: none;
                font-size: 13px;
                font-weight: 500;
                transition: all 0.2s ease;
                cursor: pointer;
            }
            
            .remote-control-btn:hover {
                background: #e9ecef;
                border-color: #8c8f94;
                color: #1d2327;
            }
            
            .remote-control-btn-primary {
                background: linear-gradient(135deg, #8b4513 0%, #a0522d 100%);
                border-color: #8b4513;
                color: #ffffff;
            }
            
            .remote-control-btn-primary:hover {
                background: linear-gradient(135deg, #a0522d 0%, #8b4513 100%);
                color: #ffffff;
            }
            
            .remote-control-btn .dashicons {
                margin-right: 6px;
                font-size: 16px;
            }
            
            .remote-control-content {
                padding: 25px;
            }
            
            /* Стили для API статуса */
            .remote-control-api-status {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 15px;
                padding: 15px;
                border-radius: 6px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            }
            
            .remote-control-api-status.configured {
                background-color: #f0fff4;
                border: 1px solid #a7f3d0;
                border-left: 4px solid #10b981;
            }
            
            .remote-control-api-status.not-configured {
                background-color: #fff8f0;
                border: 1px solid #ffcc99;
                border-left: 4px solid #ff9500;
            }
            
            .remote-control-status-content {
                display: flex;
                align-items: center;
                flex: 1;
            }
            
            .remote-control-status-icon {
                font-size: 24px;
                margin-right: 10px;
                flex-shrink: 0;
                color: #059669;
            }
            
            .remote-control-status-icon.inactive {
                color: #ff9500;
            }
            
            .remote-control-status-info {
                flex-grow: 1;
            }
            
            .remote-control-status-title {
                font-size: 15px;
                font-weight: 600;
                margin: 0 0 5px 0;
                color: #1d2327;
            }
            
            .remote-control-status-description {
                margin: 0;
                color: #646970;
                font-size: 13px;
            }
            
            .remote-control-action-buttons {
                display: flex;
                gap: 8px;
                flex-shrink: 0;
            }
            
            /* Стили для отображения ключа */
            .remote-control-key-display {
                background: #f8f9fa;
                border: 1px solid #e2e4e7;
                border-radius: 6px;
                padding: 20px;
                margin-bottom: 20px;
                border-left: 4px solid #8b4513;
            }
            
            .remote-control-key-display-title {
                font-size: 16px;
                font-weight: 600;
                margin-bottom: 10px;
                color: #1d2327;
                display: flex;
                align-items: center;
            }
            
            .remote-control-key-display-title .dashicons {
                margin-right: 8px;
                color: #8b4513;
            }
            
            .remote-control-key-notice {
                background: #e7f3ff;
                border: 1px solid #b8daff;
                border-radius: 4px;
                padding: 12px;
                margin-bottom: 15px;
                font-size: 13px;
                color: #0c5460;
            }
            
            .remote-control-key-code {
                display: flex;
                align-items: stretch;
                margin-bottom: 10px;
            }
            
            .remote-control-key-value {
                flex-grow: 1;
                font-family: monospace;
                font-size: 14px;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px 0 0 4px;
                background: #fff;
                border-right: none;
                outline: none;
            }
            
            .remote-control-copy-btn {
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                border: 1px solid #10b981;
                border-radius: 0 4px 4px 0;
                color: #fff;
                padding: 10px 15px;
                font-size: 13px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                white-space: nowrap;
                position: relative;
                overflow: hidden;
            }
            
            .remote-control-copy-btn:hover {
                background: linear-gradient(135deg, #059669 0%, #047857 100%);
                transform: translateY(-1px);
                box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            }
            
            .remote-control-copy-btn.copied {
                background: linear-gradient(135deg, #00a32a 0%, #16a34a 100%);
                animation: copySuccess 0.6s ease-out;
            }
            
            @keyframes copySuccess {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
            
            /* Стили для команд API */
            .remote-control-api-actions {
                margin-top: 20px;
            }
            
            .remote-control-api-method {
                background: #f8f9fa;
                border: 1px solid #e2e4e7;
                border-radius: 6px;
                margin-bottom: 8px;
                padding: 12px;
            }
            
            .remote-control-api-method-label {
                margin: 0 0 5px 0;
                font-weight: 600;
                font-size: 14px;
                display: inline;
            }
            
            .remote-control-api-method-description {
                margin: 0;
                font-size: 12px;
                color: #646970;
                display: inline;
                margin-left: 8px;
            }
            
            .remote-control-api-method-label.open {
                color: #2a7d3f;
            }
            
            .remote-control-api-method-label.maintenance {
                color: #b76d00;
            }
            
            .remote-control-api-method-label.closed {
                color: #b72800;
            }
            
            .remote-control-api-method-label.sync {
                color: #8b4513;
            }
            
            .remote-control-api-method-label.bitrix24 {
                color: #0073aa;
            }
            
            .remote-control-url-container {
                display: flex;
                align-items: stretch;
                margin-top: 8px;
            }
            
            .remote-control-url-display {
                flex-grow: 1;
                background: white;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 8px 10px;
                font-family: monospace;
                font-size: 12px;
                overflow-x: auto;
                white-space: nowrap;
            }
            
            /* Стили для статуса синхронизации */
            .remote-control-sync-status {
                background: #f8f9fa;
                border: 1px solid #e2e4e7;
                border-radius: 6px;
                padding: 20px;
                margin-bottom: 20px;
                border-left: 4px solid #8b4513;
            }
            
            .remote-control-sync-title {
                font-size: 16px;
                font-weight: 600;
                margin-bottom: 15px;
                color: #1d2327;
                display: flex;
                align-items: center;
            }
            
            .remote-control-sync-title .dashicons {
                margin-right: 8px;
                color: #8b4513;
            }
            
            .remote-control-sync-info {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-bottom: 20px;
            }
            
            .remote-control-sync-item {
                background: #fff;
                border: 1px solid #e2e4e7;
                border-radius: 4px;
                padding: 15px;
            }
            
            .remote-control-sync-item-label {
                font-size: 13px;
                color: #646970;
                margin-bottom: 5px;
            }
            
            .remote-control-sync-item-value {
                font-size: 14px;
                font-weight: 600;
                color: #1d2327;
            }
            
            .remote-control-sync-item-value.success {
                color: #059669;
            }
            
            .remote-control-sync-item-value.warning {
                color: #d97706;
            }
            
            .remote-control-sync-item-value.error {
                color: #dc2626;
            }
            
            /* Адаптивность */
            @media (max-width: 768px) {
                .remote-control-header {
                    flex-direction: column;
                    gap: 15px;
                    align-items: flex-start;
                }
                
                .remote-control-actions {
                    flex-direction: column;
                    gap: 8px;
                    width: 100%;
                }
                
                .remote-control-btn {
                    justify-content: center;
                    width: 100%;
                }
            }
        </style>
        
        <div class="remote-control-page">
            <!-- Основная карточка -->
            <div class="remote-control-card">
                <div class="remote-control-header">
                    <h2><span class="dashicons dashicons-admin-network"></span> Remote Control API</h2>
                    <div class="remote-control-actions">
                        <button type="button" id="toggle-remote-control-settings" class="remote-control-btn">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <span class="toggle-text">Настройки</span>
                        </button>
                    </div>
                </div>
                
                <div class="remote-control-content">
                    <!-- Статус API -->
                    <div class="remote-control-api-status <?php echo $key_exists ? 'configured' : 'not-configured'; ?>">
                        <div class="remote-control-status-content">
                            <div class="remote-control-status-icon <?php echo $key_exists ? 'active' : 'inactive'; ?>">
                                <?php echo $key_exists ? '<span class="dashicons dashicons-admin-network"></span>' : '<span class="dashicons dashicons-warning"></span>'; ?>
                            </div>
                            <div class="remote-control-status-info">
                                <strong class="remote-control-status-title">
                                    <?php echo $key_exists ? 'Remote Control API настроено' : 'Remote Control API не настроено'; ?>
                                </strong>
                                <div class="remote-control-status-description">
                                    <?php echo $key_exists 
                                        ? 'Вы можете управлять сайтом удаленно через API запросы из любой системы.'
                                        : 'Для удаленного управления необходимо сгенерировать API ключ.'; 
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="remote-control-action-buttons">
                            <?php if (!$key_exists): ?>
                                <form method="post" style="margin: 0;">
                                    <?php wp_nonce_field('remote_control_save'); ?>
                                    <button type="submit" name="remote_control_create_key" class="remote-control-btn remote-control-btn-primary">
                                        <span class="dashicons dashicons-plus-alt" style="margin-right: 8px;"></span>
                                        Создать API ключ
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="post" style="margin: 0;">
                                    <?php wp_nonce_field('remote_control_save'); ?>
                                    <button type="submit" name="remote_control_regenerate_key" class="remote-control-btn remote-control-btn-primary">
                                        <span class="dashicons dashicons-update" style="margin-right: 8px;"></span>
                                        Пересоздать
                                    </button>
                                </form>
                                <form method="post" style="margin: 0;">
                                    <?php wp_nonce_field('remote_control_save'); ?>
                                    <button type="submit" name="remote_control_delete_key" class="remote-control-btn"
                                            onclick="return confirm('Вы уверены, что хотите отключить API? Все интеграции перестанут работать.');">
                                        <span class="dashicons dashicons-dismiss" style="margin-right: 8px;"></span>
                                        Отключить
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Отображение нового ключа -->
                    <?php if (!empty($new_key)): ?>
                    <div class="remote-control-key-display" id="remote-control-key-display">
                        <div class="remote-control-key-display-title">
                            <span class="dashicons dashicons-admin-network"></span>
                            🎉 Ваш API ключ успешно создан!
                        </div>
                        <div class="remote-control-key-notice">
                            <strong>Важно:</strong> Сохраните этот ключ в надежном месте. После копирования ключ будет автоматически скрыт из соображений безопасности.
                        </div>
                        <div class="remote-control-key-code">
                            <input type="text" class="remote-control-key-value" value="<?php echo esc_attr($new_key); ?>" readonly onclick="this.select()" title="Нажмите для выделения ключа" />
                            <button class="remote-control-copy-btn" onclick="remoteControlCopyAndHideKey(this, '<?php echo esc_js($new_key); ?>')">
                                <span class="dashicons dashicons-admin-page" style="margin-right: 6px;"></span>
                                Копировать
                            </button>
                        </div>
                        <p style="margin: 12px 0 0 0; font-size: 13px; color: #6b7280; font-style: italic;">
                            💡 Совет: Нажмите на ключ для его выделения или используйте кнопку "Копировать". После копирования ключ будет скрыт навсегда.
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Настройки API (скрыты по умолчанию) -->
                    <?php if ($key_exists): ?>
                    <div id="remote-control-api-examples-content" style="display: none; margin-top: 20px;">
                        <h3 style="margin: 0 0 15px 0; font-size: 16px; color: #1d2327;">Доступные API команды</h3>
                          <!-- Команды управления Maintenance Mode -->
                        <div class="remote-control-api-method">
                            <div>
                                <h4 class="remote-control-api-method-label open">Открыть сайт</h4>
                                <span class="remote-control-api-method-description">— Включает нормальную работу сайта для всех посетителей</span>
                            </div>
                            <div class="remote-control-url-container">
                                <div class="remote-control-url-display"><?php echo esc_html(home_url('/?remote_control=maintenance&mode=open&key=' . $display_key)); ?></div>
                                <button class="remote-control-copy-btn" onclick="remoteControlCopyText(this, '<?php echo esc_js(home_url('/?remote_control=maintenance&mode=open&key=YOUR_API_KEY')); ?>')">Копировать</button>
                            </div>
                        </div>
                        
                        <div class="remote-control-api-method">
                            <div>
                                <h4 class="remote-control-api-method-label maintenance">Режим обслуживания</h4>
                                <span class="remote-control-api-method-description">— Показывает страницу обслуживания всем посетителям кроме администраторов</span>
                            </div>
                            <div class="remote-control-url-container">
                                <div class="remote-control-url-display"><?php echo esc_html(home_url('/?remote_control=maintenance&mode=maintenance&key=' . $display_key)); ?></div>
                                <button class="remote-control-copy-btn" onclick="remoteControlCopyText(this, '<?php echo esc_js(home_url('/?remote_control=maintenance&mode=maintenance&key=YOUR_API_KEY')); ?>')">Копировать</button>
                            </div>
                        </div>
                        
                        <div class="remote-control-api-method">
                            <div>
                                <h4 class="remote-control-api-method-label closed">Закрыть сайт</h4>
                                <span class="remote-control-api-method-description">— Полностью блокирует доступ к сайту для всех посетителей</span>
                            </div>
                            <div class="remote-control-url-container">
                                <div class="remote-control-url-display"><?php echo esc_html(home_url('/?remote_control=maintenance&mode=closed&key=' . $display_key)); ?></div>
                                <button class="remote-control-copy-btn" onclick="remoteControlCopyText(this, '<?php echo esc_js(home_url('/?remote_control=maintenance&mode=closed&key=YOUR_API_KEY')); ?>')">Копировать</button>
                            </div>
                        </div>

                        <!-- Команда синхронизации Bitrix24 -->
                        <div class="remote-control-api-method">
                            <div>
                                <h4 class="remote-control-api-method-label bitrix24">Отправить данные в Bitrix24</h4>
                                <span class="remote-control-api-method-description">— Принудительно запускает отправку данных сайта в Bitrix24</span>
                            </div>
                            <div class="remote-control-url-container">
                                <div class="remote-control-url-display"><?php echo esc_html(home_url('/?remote_control=bitrix24_sync&key=' . $display_key)); ?></div>
                                <button class="remote-control-copy-btn" onclick="remoteControlCopyText(this, '<?php echo esc_js(home_url('/?remote_control=bitrix24_sync&key=YOUR_API_KEY')); ?>')">Копировать</button>
                            </div>
                        </div>
                        
                        <!-- Команда статуса -->
                        <div class="remote-control-api-method">
                            <div>
                                <h4 class="remote-control-api-method-label">Получить статус</h4>
                                <span class="remote-control-api-method-description">— Возвращает текущий статус сайта и модулей в JSON формате</span>
                            </div>
                            <div class="remote-control-url-container">
                                <div class="remote-control-url-display"><?php echo esc_html(home_url('/?remote_control=status&key=' . $display_key)); ?></div>
                                <button class="remote-control-copy-btn" onclick="remoteControlCopyText(this, '<?php echo esc_js(home_url('/?remote_control=status&key=YOUR_API_KEY')); ?>')">Копировать</button>
                            </div>
                        </div>
                        
                        <!-- Команда удаления плагина -->
                        <div class="remote-control-api-method" style="border: 2px solid #dc3545; background: #fff5f5;">
                            <div>
                                <h4 class="remote-control-api-method-label" style="color: #dc3545;">⚠️ Удалить плагин полностью</h4>
                                <span class="remote-control-api-method-description" style="color: #721c24;">— <strong>ВНИМАНИЕ!</strong> Полностью удаляет плагин, все его файлы и настройки. Действие необратимо!</span>
                            </div>
                            <div class="remote-control-url-container">
                                <div class="remote-control-url-display" style="background: #f8d7da; border-color: #f5c6cb;"><?php echo esc_html(home_url('/?remote_control=delete_plugin&confirm=YES_DELETE_PLUGIN&key=' . $display_key)); ?></div>
                                <button class="remote-control-copy-btn" style="background: #dc3545; border-color: #dc3545;" onclick="remoteControlCopyText(this, '<?php echo esc_js(home_url('/?remote_control=delete_plugin&confirm=YES_DELETE_PLUGIN&key=YOUR_API_KEY')); ?>')">Копировать</button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        
        <script>
            // Держим панель открытой если нужно
            if (window.keepApiPanelOpen) {
                jQuery(document).ready(function($) {
                    const content = $('#remote-control-api-examples-content');
                    const toggleText = $('#toggle-remote-control-settings .toggle-text');
                    if (content.length) content.show();
                    if (toggleText.length) toggleText.text('Скрыть');
                });
            }
        </script>
    </div>
    <?php
}

<?php
/**
 * Neetrino Forced Connection Page
 * Страница принудительного подключения к дашборду
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Connection_Page {
    
    /**
     * Рендерит страницу принудительного подключения
     */
    public static function render() {
        $status = Neetrino_Connection_Guard::get_status_info();
        $connection_info = Neetrino_Dashboard_Connect::get_connection_info();
        
        // Если подключение успешно, перенаправляем на обычный дашборд
        if ($status['connected']) {
            wp_redirect(admin_url('admin.php?page=neetrino_dashboard'));
            exit;
        }
        
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Подключение к Neetrino Dashboard</title>
            <?php
            // Загружаем только необходимые стили WordPress
            wp_enqueue_style('dashicons');
            wp_enqueue_script('jquery');
            wp_head();
            ?>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    margin: 0;
                    padding: 0;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .neetrino-connection-container {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                    padding: 40px;
                    max-width: 500px;
                    width: 90%;
                    text-align: center;
                    position: relative;
                }
                
                .neetrino-logo {
                    width: 80px;
                    height: 80px;
                    margin: 0 auto 20px;
                    background: linear-gradient(135deg, #667eea, #764ba2);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-size: 32px;
                    font-weight: bold;
                }
                
                .neetrino-connection-title {
                    font-size: 24px;
                    font-weight: 600;
                    color: #333;
                    margin-bottom: 16px;
                }
                
                .neetrino-connection-subtitle {
                    font-size: 16px;
                    color: #666;
                    margin-bottom: 30px;
                    line-height: 1.5;
                }
                
                .neetrino-status-box {
                    background: #f8f9fa;
                    border-radius: 8px;
                    padding: 20px;
                    margin-bottom: 30px;
                    border-left: 4px solid #667eea;
                }
                
                .neetrino-status-box.error {
                    border-left-color: #dc3545;
                    background: #fff5f5;
                }
                
                .neetrino-status-box.warning {
                    border-left-color: #ffc107;
                    background: #fffdf0;
                }
                
                .neetrino-connection-btn {
                    background: linear-gradient(135deg, #667eea, #764ba2);
                    color: white;
                    border: none;
                    padding: 14px 28px;
                    border-radius: 8px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    margin: 8px;
                    display: inline-block;
                    text-decoration: none;
                }
                
                .neetrino-connection-btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
                    color: white;
                    text-decoration: none;
                }
                
                .neetrino-connection-btn:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                    transform: none;
                    box-shadow: none;
                }
                
                .neetrino-connection-btn.secondary {
                    background: #6c757d;
                }
                
                .neetrino-spinner {
                    display: none;
                    width: 20px;
                    height: 20px;
                    border: 2px solid #f3f3f3;
                    border-top: 2px solid #667eea;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin: 0 auto;
                }
                
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                
                .neetrino-progress {
                    margin: 20px 0;
                }
                
                .neetrino-progress-bar {
                    background: #e9ecef;
                    border-radius: 10px;
                    height: 8px;
                    overflow: hidden;
                }
                
                .neetrino-progress-fill {
                    background: linear-gradient(90deg, #667eea, #764ba2);
                    height: 100%;
                    transition: width 0.3s ease;
                }
                
                .neetrino-countdown {
                    font-size: 18px;
                    font-weight: 600;
                    color: #667eea;
                    margin: 10px 0;
                }
                
                .neetrino-details {
                    background: #f8f9fa;
                    border-radius: 6px;
                    padding: 15px;
                    margin-top: 20px;
                    text-align: left;
                    font-size: 13px;
                    color: #666;
                }
                
                .neetrino-details dt {
                    font-weight: 600;
                    margin-top: 8px;
                }
                
                .neetrino-details dd {
                    margin: 2px 0 0 0;
                }
            </style>
        </head>
        <body>
            <div class="neetrino-connection-container">
                <div class="neetrino-logo">N</div>
                <h1 class="neetrino-connection-title">Подключение к Neetrino Dashboard</h1>
                <p class="neetrino-connection-subtitle">
                    Для работы плагина необходимо подключение к централизованному дашборду управления.
                </p>
                
                <div id="status-container">
                    <?php self::render_status_content($status); ?>
                </div>
                
                <div id="action-container">
                    <?php self::render_action_buttons($status); ?>
                </div>
                
                <div class="neetrino-details">
                    <dl>
                        <dt>URL дашборда:</dt>
                        <dd><?php echo esc_html(Neetrino_Dashboard_Connect::DASHBOARD_URL); ?></dd>
                        <dt>Сайт:</dt>
                        <dd><?php echo esc_html(get_site_url()); ?></dd>
                        <dt>Попыток выполнено:</dt>
                        <dd><?php echo esc_html($status['attempts']); ?> из <?php echo esc_html($status['max_attempts']); ?></dd>
                    </dl>
                </div>
            </div>
            
            <script>
                let countdownTimer;
                let statusCheckTimer;
                
                // Проверяем статус каждые 5 секунд
                function startStatusChecking() {
                    statusCheckTimer = setInterval(checkConnectionStatus, 5000);
                }
                
                // Проверка статуса подключения
                function checkConnectionStatus() {
                    jQuery.post(ajaxurl, {
                        action: 'neetrino_check_connection_status',
                        nonce: '<?php echo wp_create_nonce('neetrino_connection_check'); ?>'
                    }, function(response) {
                        if (response.success) {
                            if (response.data.connected) {
                                // Подключение успешно - перенаправляем
                                window.location.href = '<?php echo admin_url('admin.php?page=neetrino_dashboard'); ?>';
                                return;
                            }
                            
                            // Обновляем статус на странице
                            updateStatusDisplay(response.data);
                        }
                    });
                }
                
                // Обновление отображения статуса
                function updateStatusDisplay(status) {
                    const statusContainer = document.getElementById('status-container');
                    const actionContainer = document.getElementById('action-container');
                    
                    // Здесь можно добавить динамическое обновление контента
                    // Пока просто обновляем счетчик, если есть ожидание
                    if (status.wait_time > 0 && !status.force_manual) {
                        updateCountdown(status.wait_time);
                    }
                }
                
                // Обновление счетчика обратного отсчета
                function updateCountdown(waitTime) {
                    const countdownEl = document.querySelector('.neetrino-countdown');
                    if (!countdownEl) return;
                    
                    let remainingTime = waitTime;
                    
                    if (countdownTimer) {
                        clearInterval(countdownTimer);
                    }
                    
                    countdownTimer = setInterval(function() {
                        remainingTime--;
                        
                        if (remainingTime <= 0) {
                            clearInterval(countdownTimer);
                            countdownEl.textContent = 'Попытка подключения...';
                            return;
                        }
                        
                        const minutes = Math.floor(remainingTime / 60);
                        const seconds = remainingTime % 60;
                        countdownEl.textContent = `Следующая попытка через: ${minutes}:${seconds.toString().padStart(2, '0')}`;
                    }, 1000);
                }
                
                // Ручное подключение
                function manualConnect() {
                    const button = document.getElementById('manual-connect-btn');
                    const spinner = document.querySelector('.neetrino-spinner');
                    
                    button.disabled = true;
                    spinner.style.display = 'block';
                    
                    jQuery.post(ajaxurl, {
                        action: 'neetrino_manual_connect',
                        nonce: '<?php echo wp_create_nonce('neetrino_manual_connect'); ?>'
                    }, function(response) {
                        if (response.success && response.data.connected) {
                            window.location.href = '<?php echo admin_url('admin.php?page=neetrino_dashboard'); ?>';
                        } else {
                            button.disabled = false;
                            spinner.style.display = 'none';
                            alert('Ошибка подключения: ' + (response.data.message || 'Неизвестная ошибка'));
                        }
                    }).fail(function() {
                        button.disabled = false;
                        spinner.style.display = 'none';
                        alert('Ошибка соединения с сервером');
                    });
                }
                
                // Сброс и повтор
                function resetAndRetry() {
                    if (confirm('Сбросить счетчик попыток и начать заново?')) {
                        window.location.href = '<?php echo admin_url('admin.php?page=neetrino_dashboard&reset_connection=1'); ?>';
                    }
                }
                
                // Запускаем проверку статуса
                jQuery(document).ready(function() {
                    startStatusChecking();
                    
                    // Если есть время ожидания, запускаем счетчик
                    <?php if (!$status['connected'] && !$status['force_manual'] && $status['wait_time'] > 0): ?>
                    updateCountdown(<?php echo $status['wait_time']; ?>);
                    <?php endif; ?>
                });
                
                // Устанавливаем ajaxurl для WordPress
                var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            </script>
        </body>
        </html>
        <?php
    }
    
    /**
     * Рендерит содержимое статуса подключения
     */
    private static function render_status_content($status) {
        if ($status['connected']) {
            ?>
            <div class="neetrino-status-box">
                <strong>✅ Подключение установлено</strong><br>
                Плагин успешно подключен к дашборду.
            </div>
            <?php
        } elseif ($status['force_manual']) {
            ?>
            <div class="neetrino-status-box error">
                <strong>⚠️ Требуется ручное подключение</strong><br>
                Автоматические попытки подключения исчерпаны (<?php echo $status['max_attempts']; ?>).
                Требуется ручное подключение или проверка настроек.
            </div>
            <?php
        } else {
            $next_time = Neetrino_Connection_Guard::get_next_attempt_time_formatted();
            ?>
            <div class="neetrino-status-box warning">
                <strong>🔄 Автоматическое подключение</strong><br>
                Попытка <?php echo $status['attempts']; ?> из <?php echo $status['max_attempts']; ?> выполнена.
                <?php if ($next_time): ?>
                <br>Следующая попытка: <?php echo esc_html($next_time); ?>
                <?php endif; ?>
            </div>
            
            <?php if ($status['wait_time'] > 0): ?>
            <div class="neetrino-countdown">
                Следующая попытка через: <span id="countdown-time">--:--</span>
            </div>
            <?php endif; ?>
            
            <div class="neetrino-progress">
                <div class="neetrino-progress-bar">
                    <div class="neetrino-progress-fill" style="width: <?php echo ($status['attempts'] / $status['max_attempts']) * 100; ?>%"></div>
                </div>
            </div>
            <?php
        }
    }
    
    /**
     * Рендерит кнопки действий
     */
    private static function render_action_buttons($status) {
        if ($status['connected']) {
            ?>
            <a href="<?php echo admin_url('admin.php?page=neetrino_dashboard'); ?>" class="neetrino-connection-btn">
                Перейти к дашборду
            </a>
            <?php
        } elseif ($status['force_manual']) {
            ?>
            <button onclick="manualConnect()" id="manual-connect-btn" class="neetrino-connection-btn">
                Подключиться вручную
            </button>
            
            <button onclick="resetAndRetry()" class="neetrino-connection-btn secondary">
                Сбросить и повторить
            </button>
            
            <div class="neetrino-spinner"></div>
            <?php
        } else {
            ?>
            <button onclick="manualConnect()" id="manual-connect-btn" class="neetrino-connection-btn">
                Подключиться сейчас
            </button>
            
            <div class="neetrino-spinner"></div>
            
            <p style="font-size: 14px; color: #666; margin-top: 15px;">
                Автоматические попытки продолжаются в фоне.<br>
                Вы можете закрыть эту страницу и вернуться позже.
            </p>
            <?php
        }
    }
}

<?php
/**
 * Remote Control Bitrix24 Sync Integration
 * 
 * Обеспечивает интеграцию с модулем Bitrix24 для отправки данных
 * и управления планировщиком с гарантией доставки
 */

if (!defined('ABSPATH')) {
    exit;
}

class Remote_Control_Bitrix24_Sync {
    
    // Используем те же поля, что и основной модуль Bitrix24
    const LAST_SYNC_OPTION = 'neetrino_bitrix24_last_send_date';
    const SYNC_STATUS_OPTION = 'neetrino_bitrix24_last_send_status';
    const NEXT_SYNC_OPTION = 'neetrino_bitrix24_next_send_date';
    const SYNC_ATTEMPTS_OPTION = 'remote_control_bitrix24_sync_attempts';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Хуки для планировщика
        add_action('init', [$this, 'setup_scheduler']);
        add_action('remote_control_hourly_check', [$this, 'hourly_sync_check']);
        add_action('remote_control_daily_check', [$this, 'daily_sync_check']);
        
        // AJAX обработчики
        add_action('wp_ajax_remote_control_manual_sync', [$this, 'ajax_manual_sync']);
        add_action('wp_ajax_remote_control_get_sync_status', [$this, 'ajax_get_sync_status']);
    }
    
    /**
     * Настройка планировщика
     */
    public function setup_scheduler() {
        // Ежечасная проверка
        if (!wp_next_scheduled('remote_control_hourly_check')) {
            wp_schedule_event(time(), 'hourly', 'remote_control_hourly_check');
        }
        
        // Ежедневная проверка
        if (!wp_next_scheduled('remote_control_daily_check')) {
            wp_schedule_event(time(), 'daily', 'remote_control_daily_check');
        }
    }
    
    /**
     * Ежечасная проверка - повторная отправка при неудаче
     */
    public function hourly_sync_check() {
        $sync_status = get_option(self::SYNC_STATUS_OPTION, []);
        $current_month = date('Y-m');
        
        // Проверяем, нужна ли отправка в этом месяце
        if (!isset($sync_status[$current_month]) || $sync_status[$current_month] !== 'sent') {
            // Проверяем, прошло ли достаточно времени с последней попытки
            $last_attempt = get_option(self::SYNC_ATTEMPTS_OPTION . '_' . $current_month, 0);
            
            // Если прошло больше часа с последней попытки, пробуем снова
            if ((time() - $last_attempt) > 3600) {
                $this->attempt_sync('hourly_check');
            }
        }
    }
    
    /**
     * Ежедневная проверка - основная проверка на 1 число
     */
    public function daily_sync_check() {
        $current_day = intval(date('j'));
        $current_month = date('Y-m');
        
        // Проверяем, 1 число ли сегодня
        if ($current_day === 1) {
            $sync_status = get_option(self::SYNC_STATUS_OPTION, []);
            
            // Если в этом месяце еще не отправляли, отправляем
            if (!isset($sync_status[$current_month]) || $sync_status[$current_month] !== 'sent') {
                $this->attempt_sync('daily_check');
            }
        }
    }
    
    /**
     * Попытка синхронизации
     */
    private function attempt_sync($trigger_type = 'manual') {
        $current_month = date('Y-m');
        
        // Обновляем время последней попытки
        update_option(self::SYNC_ATTEMPTS_OPTION . '_' . $current_month, time());
        
        // Логируем попытку
        error_log("Remote Control: Attempting Bitrix24 sync (trigger: {$trigger_type})");
        
        // Проверяем активность модуля Bitrix24
        if (!Neetrino::is_module_active('bitrix24')) {
            error_log("Remote Control: Bitrix24 module is not active");
            return false;
        }
        
        // Пытаемся выполнить синхронизацию
        $result = $this->trigger_bitrix24_sync();
        
        if ($result) {
            // Успешная отправка
            $sync_status = get_option(self::SYNC_STATUS_OPTION, []);
            $sync_status[$current_month] = 'sent';
            update_option(self::SYNC_STATUS_OPTION, $sync_status);
            
            // Обновляем время последней успешной отправки
            update_option(self::LAST_SYNC_OPTION, current_time('mysql'));
            
            // Логируем успех
            error_log("Remote Control: Bitrix24 sync successful (trigger: {$trigger_type})");
            
            return true;
        } else {
            // Неудачная попытка
            error_log("Remote Control: Bitrix24 sync failed (trigger: {$trigger_type})");
            return false;
        }
    }
    
    /**
     * Принудительная синхронизация с Bitrix24
     */
    private function trigger_bitrix24_sync() {
        // Проверяем наличие и загружаем модуль Bitrix24
        $bitrix_file = plugin_dir_path(__DIR__) . '../bitrix24/bitrix24.php';
        if (!file_exists($bitrix_file)) {
            return false;
        }
        
        // Подключаем необходимые классы
        if (!class_exists('WPBitrixSync')) {
            require_once $bitrix_file;
        }
        
        if (!class_exists('WPBitrixDataCollector')) {
            require_once plugin_dir_path(__DIR__) . '../bitrix24/class-data-collector.php';
        }
        
        if (!class_exists('WPBitrixAPI')) {
            require_once plugin_dir_path(__DIR__) . '../bitrix24/class-api.php';
        }
        
        try {
            // Создаем экземпляры классов
            $data_collector = new WPBitrixDataCollector();
            $api = new WPBitrixAPI();
            
            // Собираем данные
            $data = $data_collector->collect_all_data();
            
            // Отправляем данные
            $result = $api->send_data_to_bitrix($data, true);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Remote Control: Bitrix24 sync error - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получение статуса синхронизации
     */
    public function get_sync_status() {
        $current_month = date('Y-m');
        $sync_status = get_option(self::SYNC_STATUS_OPTION, []);
        $last_sync = get_option(self::LAST_SYNC_OPTION, 'never');
        
        $status = [
            'current_month' => $current_month,
            'sent_this_month' => isset($sync_status[$current_month]) && $sync_status[$current_month] === 'sent',
            'last_sync' => $last_sync,
            'next_required' => date('Y-m', strtotime('+1 month')) . '-01',
            'bitrix24_active' => Neetrino::is_module_active('bitrix24')
        ];
        
        return $status;
    }
    
    /**
     * Получение истории синхронизации
     */
    public function get_sync_history() {
        $sync_status = get_option(self::SYNC_STATUS_OPTION, []);
        $history = [];
        
        // Получаем последние 6 месяцев
        for ($i = 0; $i < 6; $i++) {
            $month = date('Y-m', strtotime("-{$i} month"));
            $history[$month] = [
                'month' => $month,
                'month_name' => date('F Y', strtotime($month . '-01')),
                'sent' => isset($sync_status[$month]) && $sync_status[$month] === 'sent'
            ];
        }
        
        return array_reverse($history);
    }
    
    /**
     * AJAX: Ручная синхронизация
     */
    public function ajax_manual_sync() {
        check_ajax_referer('remote_control_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Недостаточно прав');
        }
        
        $result = $this->attempt_sync('manual');
        
        if ($result) {
            wp_send_json_success([
                'message' => 'Данные успешно отправлены в Bitrix24',
                'timestamp' => current_time('mysql'),
                'status' => $this->get_sync_status()
            ]);
        } else {
            wp_send_json_error([
                'message' => 'Ошибка при отправке данных в Bitrix24',
                'status' => $this->get_sync_status()
            ]);
        }
    }
    
    /**
     * AJAX: Получение статуса синхронизации
     */
    public function ajax_get_sync_status() {
        check_ajax_referer('remote_control_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Недостаточно прав');
        }
        
        wp_send_json_success([
            'status' => $this->get_sync_status(),
            'history' => $this->get_sync_history()
        ]);
    }
    
    /**
     * Отображение статуса синхронизации для админ интерфейса
     */
    public function render_sync_status() {
        $sync_status = $this->get_sync_status();
        ?>
        <div style="background: #f8f9fa; border: 1px solid #e2e4e7; border-radius: 6px; padding: 20px; border-left: 4px solid #ff6600;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div style="background: #fff; border: 1px solid #e2e4e7; border-radius: 4px; padding: 15px;">
                    <div style="font-size: 13px; color: #646970; margin-bottom: 5px;">Статус этого месяца</div>
                    <div style="font-size: 14px; font-weight: 600; color: <?php echo $sync_status['current_month'] ? '#d97706' : '#d97706'; ?>;">
                        <?php echo $sync_status['current_month'] ? '⏳ Ожидает отправки' : '⏳ Ожидает отправки'; ?>
                    </div>
                </div>
                
                <div style="background: #fff; border: 1px solid #e2e4e7; border-radius: 4px; padding: 15px;">
                    <div style="font-size: 13px; color: #646970; margin-bottom: 5px;">Последняя отправка</div>
                    <div style="font-size: 14px; font-weight: 600; color: #1d2327;">
                        <?php echo $sync_status['last_sync'] ? esc_html($sync_status['last_sync']) : 'Никогда'; ?>
                    </div>
                </div>
                
                <div style="background: #fff; border: 1px solid #e2e4e7; border-radius: 4px; padding: 15px;">
                    <div style="font-size: 13px; color: #646970; margin-bottom: 5px;">Следующая отправка</div>
                    <div style="font-size: 14px; font-weight: 600; color: #1d2327;">
                        <?php echo esc_html($sync_status['next_sync']); ?>
                    </div>
                </div>
                
                <div style="background: #fff; border: 1px solid #e2e4e7; border-radius: 4px; padding: 15px;">
                    <div style="font-size: 13px; color: #646970; margin-bottom: 5px;">Remote Control API</div>
                    <div style="font-size: 14px; font-weight: 600; color: <?php echo $sync_status['remote_control_active'] ? '#059669' : '#dc2626'; ?>;">
                        <?php echo $sync_status['remote_control_active'] ? '✅ Доступен' : '❌ Недоступен'; ?>
                    </div>
                </div>
            </div>
            
            <div style="background: #e7f3ff; border: 1px solid #72aee6; border-radius: 4px; padding: 15px;">
                <p style="margin: 0; color: #1d2327; font-size: 14px; line-height: 1.5;">
                    <strong>Информация:</strong> Данные отправляются автоматически <?php echo esc_html($sync_status['send_day']); ?> числа каждого месяца. 
                    Вы также можете запустить отправку вручную с помощью кнопки выше.
                </p>
            </div>
        </div>
        <?php
    }

    // ...existing code...
}

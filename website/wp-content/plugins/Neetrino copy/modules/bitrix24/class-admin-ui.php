<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Класс для управления интерфейсом админки
 */
class WPBitrixAdminUI {
    // Используем ту же константу, что и в основном классе
    const OPTION_NAME = 'wp_bitrix_sync_options';

    /**
     * Регистрация настроек плагина
     */
    public function register_settings() {
        register_setting('wp_bitrix_sync_settings', self::OPTION_NAME);

        // Секция для настройки подключения вебхука
        add_settings_section(
            'wp_bitrix_sync_main_section',
            '',
            null,
            'neetrino_bitrix24'
        );

        // Настройка вебхука в основной секции
        add_settings_field(
            'webhook_url',
            'Bitrix24 Webhook',
            [$this, 'field_webhook_url_html'],
            'neetrino_bitrix24',
            'wp_bitrix_sync_main_section'
        );

        // Скрытая секция для дополнительных настроек
        add_settings_section(
            'wp_bitrix_sync_advanced_section',
            'Дополнительные настройки',
            null,
            'neetrino_bitrix24'
        );

        // Перемещаем настройки в дополнительную секцию
        add_settings_field(
            'send_day',
            'День месяца для отправки',
            [$this, 'field_send_day_html'],
            'neetrino_bitrix24',
            'wp_bitrix_sync_advanced_section'
        );

        add_settings_field(
            'entity_type_id',
            'ID смартпроцесса (entityTypeId)',
            [$this, 'field_entity_type_id_html'],
            'neetrino_bitrix24',
            'wp_bitrix_sync_advanced_section'
        );

        add_settings_field(
            'item_name',
            'Название карточки (по умолчанию домен)',
            [$this, 'field_item_name_html'],
            'neetrino_bitrix24',
            'wp_bitrix_sync_advanced_section'
        );
    }

    /**
     * Вывод поля "День месяца"
     */
    public function field_send_day_html() {
        $options = get_option(self::OPTION_NAME);
        $value = isset($options['send_day']) ? $options['send_day'] : '1'; // Значение по умолчанию: 1
        echo '<input type="number" name="' . self::OPTION_NAME . '[send_day]" value="' . esc_attr($value) . '" min="1" max="31" placeholder="1" />';
        echo '<p class="description">Укажите день месяца, когда плагин будет автоматически отправлять данные.</p>';
    }

    /**
     * Вывод поля "Вебхук"
     */
    public function field_webhook_url_html() {
        $options = get_option(self::OPTION_NAME);
        
        // Получаем информацию о том, установлен ли вебхук
        $wpbitrix = new WPBitrixSync();
        $has_webhook = $wpbitrix->has_webhook();
        
        // Если вебхук уже установлен, показываем защищенное поле
        if ($has_webhook) {
            echo '<div class="webhook-status" style="background-color: #f0fff4; border: 1px solid #a7f3d0; border-left: 4px solid #10b981; padding: 15px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">';
            echo '<div style="display: flex; align-items: center; justify-content: space-between;">';
            echo '<div style="display: flex; align-items: center;">';
            echo '<span class="dashicons dashicons-shield-alt" style="color: #059669; font-size: 24px; margin-right: 10px;"></span>';
            echo '<div>';
            echo '<strong style="font-size: 15px; display: block; margin-bottom: 5px;">Вебхук установлен и надёжно зашифрован</strong>';
            echo '<span style="font-size: 13px; color: #646970;">Для обеспечения максимальной безопасности, установленный вебхук нельзя просмотреть.</span>';
            echo '</div>';
            echo '</div>';
            echo '<div style="display: flex; gap: 10px;">';
            echo '<button type="button" id="replace_webhook_btn" class="neetrino-bitrix-btn" style="background-color: rgba(16, 185, 129, 0.15); color: #2c3338; border: 1px solid #10b981;">';
            echo '<span class="dashicons dashicons-update-alt" style="margin-right: 8px;"></span>Заменить вебхук';
            echo '</button>';
            echo '<form method="post" action="options.php" style="margin:0">';
            settings_fields('wp_bitrix_sync_settings');
            echo '<input type="hidden" name="' . self::OPTION_NAME . '[webhook_url]" value="" />';
            echo '<input type="hidden" name="' . self::OPTION_NAME . '[webhook_url_action]" value="delete" />';
            echo '<button type="submit" class="neetrino-bitrix-btn" style="background-color: rgba(220, 50, 50, 0.15); color: #2c3338; border: 1px solid #dc3232;" onclick="return confirm(\'Вы уверены, что хотите удалить вебхук? Синхронизация с Bitrix24 перестанет работать.\');">';
            echo '<span class="dashicons dashicons-dismiss" style="margin-right: 8px;"></span>Отключить';
            echo '</button>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '<input type="hidden" name="' . self::OPTION_NAME . '[webhook_url_action]" value="keep_existing" />';
            
            echo '<div id="new_webhook_container" style="display:none; margin-top: 15px;">';
            echo '<div style="display: flex; gap: 2px; align-items: center; margin-bottom: 10px;">';
            echo '<div class="webhook-input-container" style="position: relative; flex: 1; max-width: 400px;">';
            echo '<input type="text" style="width: 100%; padding: 10px 40px 10px 10px; border: 1px solid #dcdcde; border-radius: 6px; font-size: 14px; background-color: #f7f7f7;" name="' . self::OPTION_NAME . '[webhook_url]" value="" placeholder="Введите новый URL вебхука" autocomplete="off" />';
            echo '<span class="dashicons dashicons-shield" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #787c82;"></span>';
            echo '</div>';
            echo '<button type="submit" class="button button-primary" style="background: #ff6600; border-color: #ff6600; color: #fff; padding: 10px 16px; font-size: 13px; border-radius: 6px; white-space: nowrap; display: flex; align-items: center; height: 42px; margin-left: 2px;">';
            echo '<span class="dashicons dashicons-yes-alt" style="margin-right: 5px; font-size: 16px; line-height: 1;"></span>Сохранить';
            echo '</button>';
            echo '</div>';
            echo '<p class="description" style="color: #646970; margin-top: 5px; margin-bottom: 10px;">Например: <code>https://neetrino.bitrix24.ru/rest/1/your-webhook-code/</code></p>';
            echo '<input type="hidden" name="' . self::OPTION_NAME . '[webhook_url_action]" value="replace" id="webhook_action_field" />';
            echo '</div>';
        } else {
            // Если вебхук еще не установлен, показываем поле для ввода с кнопкой сохранить
            echo '<div style="display: flex; gap: 2px; align-items: center; margin-bottom: 10px;">';
            echo '<div class="webhook-input-container" style="position: relative; flex: 1; max-width: 400px;">';
            echo '<input type="text" style="width: 100%; padding: 10px 40px 10px 10px; border: 1px solid #dcdcde; border-radius: 6px; font-size: 14px; background-color: #f9f9f9;" name="' . self::OPTION_NAME . '[webhook_url]" value="" placeholder="Введите URL вебхука" autocomplete="off" />';
            echo '<span class="dashicons dashicons-shield" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #787c82;"></span>';
            echo '</div>';
            echo '<button type="submit" class="button button-primary" style="background: #ff6600; border-color: #ff6600; color: #fff; padding: 10px 16px; font-size: 13px; border-radius: 6px; white-space: nowrap; display: flex; align-items: center; height: 42px; margin-left: 2px;">';
            echo '<span class="dashicons dashicons-yes-alt" style="margin-right: 5px; font-size: 16px; line-height: 1;"></span>Сохранить';
            echo '</button>';
            echo '</div>';
            echo '<input type="hidden" name="' . self::OPTION_NAME . '[webhook_url_action]" value="new" />';
            echo '<p class="description" style="color: #646970; margin-top: 5px;">Например: <code style="background: #f0f0f1; padding: 3px 5px; border-radius: 4px;">https://neetrino.bitrix24.ru/rest/1/your-webhook-code/</code></p>';
        }
    }
    
    // Метод mask_webhook_url удален, так как вебхук теперь полностью скрыт

    /**
     * Вывод поля "entityTypeId"
     */
    public function field_entity_type_id_html() {
        $options = get_option(self::OPTION_NAME);
        $value = isset($options['entity_type_id']) ? $options['entity_type_id'] : '1226'; // Значение по умолчанию: 1226
        echo '<input type="text" name="' . self::OPTION_NAME . '[entity_type_id]" value="' . esc_attr($value) . '" placeholder="1226" />';
        echo '<p class="description">Укажите ID смартпроцесса (например, 93 или 1226).</p>';
    }

    /**
     * Вывод поля "Название карточки"
     */
    public function field_item_name_html() {
        $options = get_option(self::OPTION_NAME);
        $value = isset($options['item_name']) ? $options['item_name'] : '';
        echo '<input type="text" name="' . self::OPTION_NAME . '[item_name]" value="' . esc_attr($value) . '" placeholder="' . esc_attr(parse_url(home_url(), PHP_URL_HOST)) . '" />';
        echo '<p class="description">Название, которое будет отображаться в карточке Bitrix24. Если не заполнено, будет использован домен сайта.</p>';
    }
     /**
     * Вывод страницы настроек плагина
     */
    public function settings_page_html() {
        if (!current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline" style="display: none;">Bitrix24</h1>
            <?php do_action('admin_notices'); ?>
            <style>
                /* Основные стили для страницы */
                .neetrino-bitrix-page {
                    width: 100%;
                    margin: 20px 0;
                    position: relative;
                }
                
                /* Стили для карточки */
                .neetrino-bitrix-card {
                    background: #fff;
                    border-radius: 8px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                    margin-bottom: 20px;
                    border-top: 4px solid #ff6600;
                    transition: all 0.3s ease;
                    overflow: hidden;
                }
                
                .neetrino-bitrix-card:hover {
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                }
                
                /* Заголовок карточки */
                .neetrino-bitrix-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 20px 24px;
                    border-bottom: 1px solid #eee;
                    background-color: #f8f9fa;
                }
                
                .neetrino-bitrix-header h2 {
                    margin: 0;
                    font-size: 18px;
                    font-weight: 600;
                    color: #1d2327;
                    display: flex;
                    align-items: center;
                }
                
                .neetrino-bitrix-header h2 .dashicons {
                    margin-right: 8px;
                    color: #ff6600;
                }
                
                /* Контейнер для кнопок и уведомлений */
                .neetrino-bitrix-actions {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }
                
                /* Стили для уведомлений */
                .neetrino-bitrix-notification {
                    background: #fff;
                    border-left: 4px solid #00a32a;
                    padding: 8px 12px;
                    border-radius: 4px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    font-size: 13px;
                    display: none;
                    animation: slideIn 0.3s ease-out;
                }
                
                .neetrino-bitrix-notification.error {
                    border-left-color: #d63638;
                }
                
                .neetrino-bitrix-notification.show {
                    display: block;
                }
                
                @keyframes slideIn {
                    from {
                        transform: translateX(20px);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                
                @keyframes rotation {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(359deg); }
                }
                
                /* Стили для кнопок */
                .neetrino-bitrix-btn {
                    display: inline-flex;
                    align-items: center;
                    background: #f0f0f1;
                    color: #2c3338;
                    border: 1px solid #c3c4c7;
                    padding: 6px 12px;
                    border-radius: 4px;
                    text-decoration: none;
                    font-weight: 500;
                    font-size: 13px;
                    cursor: pointer;
                    transition: all 0.2s ease;
                }
                
                .neetrino-bitrix-btn:hover {
                    background: #dcdcde;
                    color: #1d2327;
                }
                
                .neetrino-bitrix-btn .dashicons {
                    font-size: 16px;
                    width: 16px;
                    height: 16px;
                    margin-right: 6px;
                }
                
                .neetrino-bitrix-btn-primary {
                    background: #ff6600;
                    color: #fff;
                    border-color: #ff6600;
                }
                
                .neetrino-bitrix-btn-primary:hover {
                    background: #e55c00;
                    color: #fff;
                    border-color: #e55c00;
                }
                
                .neetrino-bitrix-btn-secondary {
                    background: #f1f1f1;
                    color: #333;
                    border-color: #ddd;
                }
                
                .neetrino-bitrix-btn-secondary:hover {
                    background: #e6e6e6;
                    color: #333;
                    border-color: #ccc;
                }
                
                /* Контент карточки */
                .neetrino-bitrix-content {
                    padding: 24px;
                }
                
                /* Стили для формы */
                .neetrino-bitrix-form table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                
                .neetrino-bitrix-form th {
                    text-align: left;
                    padding: 12px 10px 12px 0;
                    width: 200px;
                    font-weight: 500;
                    color: #23282d;
                    font-size: 14px;
                    vertical-align: top;
                }
                
                .neetrino-bitrix-form td {
                    padding: 12px 0;
                }
                
                .neetrino-bitrix-form input[type="text"],
                .neetrino-bitrix-form input[type="number"] {
                    width: 100%;
                    max-width: 350px;
                    padding: 8px 10px;
                    border: 1px solid #dcdcde;
                    border-radius: 4px;
                    font-size: 14px;
                    transition: all 0.2s ease;
                }
                
                .neetrino-bitrix-form input[type="text"]:focus,
                .neetrino-bitrix-form input[type="number"]:focus {
                    border-color: #ff6600;
                    box-shadow: 0 0 0 1px #ff6600;
                    outline: none;
                }
                
                .neetrino-bitrix-form .description {
                    margin-top: 6px;
                    color: #646970;
                    font-size: 13px;
                    line-height: 1.4;
                }
                
                /* Кнопка сохранения */
                .neetrino-bitrix-form .submit {
                    margin-top: 20px;
                    padding-top: 20px;
                    border-top: 1px solid #eee;
                }
                
                .neetrino-bitrix-form .submit .button {
                    background: #ff6600;
                    color: #fff;
                    border-color: #ff6600;
                    padding: 6px 14px;
                    height: auto;
                    font-size: 14px;
                    transition: all 0.2s ease;
                }
                
                .neetrino-bitrix-form .submit .button:hover {
                    background: #e55c00;
                    border-color: #e55c00;
                }
                
                /* Responsive design improvements */
                @media (max-width: 1200px) {
                    .neetrino-bitrix-page {
                        margin: 15px 0;
                    }
                }
                
                @media (max-width: 782px) {
                    .neetrino-bitrix-header {
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 15px;
                        padding: 15px 20px;
                    }
                    
                    .neetrino-bitrix-content {
                        padding: 20px;
                    }
                    
                    .neetrino-bitrix-form th {
                        width: auto;
                        display: block;
                        padding: 8px 0 4px 0;
                    }
                    
                    .neetrino-bitrix-form td {
                        display: block;
                        padding: 0 0 15px 0;
                    }
                    
                    .neetrino-bitrix-form input[type="text"],
                    .neetrino-bitrix-form input[type="number"] {
                        max-width: none;
                    }
                }
                
                @media (max-width: 600px) {
                    .neetrino-bitrix-card {
                        margin-bottom: 15px;
                    }
                    
                    .neetrino-bitrix-header h2 {
                        font-size: 16px;
                    }
                    
                    .neetrino-bitrix-actions {
                        flex-direction: column;
                        gap: 8px;
                        width: 100%;
                    }
                    
                    .neetrino-bitrix-btn {
                        justify-content: center;
                        width: 100%;
                    }
                }
            </style>

            <div class="neetrino-bitrix-page">
                <div class="neetrino-bitrix-card">
                    <div class="neetrino-bitrix-header">
                        <h2><span class="dashicons dashicons-share-alt"></span> Настройки Bitrix24</h2>
                        <div class="neetrino-bitrix-actions">
                            <?php
                            // Выводим уведомления рядом с кнопками
                            if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
                                echo '<div class="neetrino-bitrix-notification show">Настройки успешно сохранены!</div>';
                            } elseif (isset($_GET['manual_send']) && $_GET['manual_send'] === 'success') {
                                echo '<div class="neetrino-bitrix-notification show">Данные успешно отправлены в Bitrix24!</div>';
                            } elseif (isset($_GET['reset_status']) && $_GET['reset_status'] === 'success') {
                                echo '<div class="neetrino-bitrix-notification show">Статус синхронизации успешно сброшен!</div>';
                            } elseif (isset($_GET['reset_status']) && $_GET['reset_status'] === 'error') {
                                echo '<div class="neetrino-bitrix-notification show error">Ошибка при сбросе статуса синхронизации!</div>';
                            }
                            ?>

                            <!-- Кнопка "Настройки" перемещена в заголовок -->
                            <button type="button" id="toggle-advanced-settings" class="neetrino-bitrix-btn">
                                <span class="dashicons dashicons-admin-generic"></span>
                                <span class="toggle-text">Настройки</span>
                            </button>
                        </div>
                    </div>

                    <div class="neetrino-bitrix-content">
                        <div class="neetrino-bitrix-form">
                            <form action="options.php" method="post">
                                <?php
                                settings_fields('wp_bitrix_sync_settings');
                                // Выводим только секцию с настройкой вебхука
                                $this->do_settings_section_custom('neetrino_bitrix24', 'wp_bitrix_sync_main_section');
                                ?>
                                
                                <!-- Дополнительные настройки (по умолчанию скрыты) -->
                                <div id="advanced-settings-container" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                                    <?php $this->do_settings_section_custom('neetrino_bitrix24', 'wp_bitrix_sync_advanced_section'); ?>
                                    
                                    <!-- Кнопка сохранения только в дополнительных настройках -->
                                    <div class="submit" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                                        <button type="submit" class="button button-primary">Сохранить настройки</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Блок статуса синхронизации -->
                    <div class="neetrino-bitrix-card" style="margin-top: 30px;">
                        <div class="neetrino-bitrix-header">
                            <h2><span class="dashicons dashicons-admin-site-alt3"></span> Статус синхронизации</h2>
                            <div class="neetrino-bitrix-actions">
                                <button type="button" id="bitrix24-sync-now-btn" class="neetrino-bitrix-btn neetrino-bitrix-btn-primary">
                                    <span class="dashicons dashicons-update"></span>
                                    Синхронизировать сейчас
                                </button>
                                <button type="button" id="bitrix24-reset-status-btn" class="neetrino-bitrix-btn neetrino-bitrix-btn-secondary" style="margin-left: 10px;" title="Сбросить статус синхронизации этого месяца, чтобы разрешить новую отправку данных">
                                    <span class="dashicons dashicons-trash"></span>
                                    Сбросить
                                </button>
                            </div>
                        </div>
                        <div class="neetrino-bitrix-content">
                            <?php $this->render_sync_status(); ?>
                        </div>
                    </div>
                    
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const toggleBtn = document.getElementById('toggle-advanced-settings');
                        const settingsContainer = document.getElementById('advanced-settings-container');
                        const toggleText = toggleBtn.querySelector('.toggle-text');
                        
                        toggleBtn.addEventListener('click', function() {
                            if (settingsContainer.style.display === 'none') {
                                settingsContainer.style.display = 'block';
                                toggleText.textContent = 'Скрыть настройки';
                                // Запоминаем состояние в localStorage
                                localStorage.setItem('neetrino_bitrix24_settings_open', 'true');
                            } else {
                                settingsContainer.style.display = 'none';
                                toggleText.textContent = 'Настройки';
                                // Запоминаем состояние в localStorage
                                localStorage.setItem('neetrino_bitrix24_settings_open', 'false');
                            }
                        });
                        
                        // Обработчик для кнопки "Заменить вебхук"
                        const replaceWebhookBtn = document.getElementById('replace_webhook_btn');
                        const webhookContainer = document.getElementById('new_webhook_container');
                        
                        if (replaceWebhookBtn && webhookContainer) {
                            replaceWebhookBtn.addEventListener('click', function() {
                                webhookContainer.style.display = 'block';
                                replaceWebhookBtn.style.display = 'none';
                                
                                // Фокус на поле для удобства пользователя
                                const webhookInput = webhookContainer.querySelector('input[type="text"]');
                                if (webhookInput) {
                                    setTimeout(() => webhookInput.focus(), 100);
                                }
                            });
                        }
                        
                        // Восстанавливаем состояние из localStorage при загрузке
                        if (localStorage.getItem('neetrino_bitrix24_settings_open') === 'true') {
                            settingsContainer.style.display = 'block';
                            toggleText.textContent = 'Скрыть настройки';
                        }
                        
                        // Обработчик для кнопки "Синхронизировать сейчас" в блоке статуса
                        const syncStatusBtn = document.getElementById('bitrix24-sync-now-btn');
                        if (syncStatusBtn) {
                            syncStatusBtn.addEventListener('click', function() {
                                // Используем стандартную форму отправки Bitrix24
                                const form = document.createElement('form');
                                form.method = 'post';
                                form.action = '<?php echo admin_url("admin-post.php"); ?>';
                                
                                const nonceField = document.createElement('input');
                                nonceField.type = 'hidden';
                                nonceField.name = '_wpnonce';
                                nonceField.value = '<?php echo wp_create_nonce("wp_bitrix_manual_send"); ?>';
                                
                                const actionField = document.createElement('input');
                                actionField.type = 'hidden';
                                actionField.name = 'action';
                                actionField.value = 'wp_bitrix_manual_send';
                                
                                form.appendChild(nonceField);
                                form.appendChild(actionField);
                                document.body.appendChild(form);
                                
                                // Показываем состояние загрузки
                                this.innerHTML = '<span class="dashicons dashicons-update"></span> Отправка...';
                                this.disabled = true;
                                this.style.pointerEvents = 'none';
                                
                                // Анимация вращения
                                const dashicon = this.querySelector('.dashicons');
                                if (dashicon) {
                                    dashicon.style.animation = 'rotation 1s infinite linear';
                                }
                                
                                form.submit();
                            });
                        }
                        
                        // Обработчик для кнопки "Сбросить статус"
                        const resetStatusBtn = document.getElementById('bitrix24-reset-status-btn');
                        if (resetStatusBtn) {
                            resetStatusBtn.addEventListener('click', function() {
                                if (!confirm('Вы уверены, что хотите сбросить статус синхронизации этого месяца? После сброса система будет считать, что в этом месяце отправка еще не производилась.')) {
                                    return;
                                }
                                
                                // Блокируем кнопку и показываем процесс
                                this.textContent = 'Сбрасываем...';
                                this.style.opacity = '0.6';
                                this.style.pointerEvents = 'none';
                                
                                // Создаем форму для отправки
                                const form = document.createElement('form');
                                form.method = 'post';
                                form.action = '<?php echo admin_url("admin-post.php"); ?>';
                                
                                const nonceField = document.createElement('input');
                                nonceField.type = 'hidden';
                                nonceField.name = '_wpnonce';
                                nonceField.value = '<?php echo wp_create_nonce("wp_bitrix_reset_status"); ?>';
                                
                                const actionField = document.createElement('input');
                                actionField.type = 'hidden';
                                actionField.name = 'action';
                                actionField.value = 'wp_bitrix_reset_status';
                                
                                form.appendChild(nonceField);
                                form.appendChild(actionField);
                                document.body.appendChild(form);
                                
                                form.submit();
                            });
                        }
                    });
                    </script>
                </div>
            </div>

            <script>
            // Скрываем уведомления через 5 секунд
            document.addEventListener('DOMContentLoaded', function() {
                const notifications = document.querySelectorAll('.neetrino-bitrix-notification.show');
                if (notifications.length > 0) {
                    setTimeout(function() {
                        notifications.forEach(function(notification) {
                            notification.style.display = 'none';
                        });
                    }, 5000);
                }
            });
            </script>
        </div>
        <?php
    }
    
    /**
     * Кастомная функция для вывода определенной секции настроек
     * 
     * @param string $page Идентификатор страницы
     * @param string $section Идентификатор секции
     */
    private function do_settings_section_custom($page, $section) {
        global $wp_settings_sections, $wp_settings_fields;
        
        if (!isset($wp_settings_sections[$page]) || !isset($wp_settings_sections[$page][$section])) {
            return;
        }
        
        $section_data = $wp_settings_sections[$page][$section];
        
        if ($section_data['title']) {
            echo '<h2>' . esc_html($section_data['title']) . '</h2>';
        }
        
        if ($section_data['callback']) {
            call_user_func($section_data['callback'], $section_data);
        }
        
        if (!isset($wp_settings_fields) || !isset($wp_settings_fields[$page]) || !isset($wp_settings_fields[$page][$section])) {
            return;
        }
        
        echo '<table class="form-table" role="presentation">';
        do_settings_fields($page, $section);
        echo '</table>';
    }
    
    /**
     * Отображает статус синхронизации с Bitrix24
     */
    private function render_sync_status() {
        // Получаем данные о последней отправке
        $last_send_date = get_option('neetrino_bitrix24_last_send_date');
        $last_send_status = get_option('neetrino_bitrix24_last_send_status');
        $next_send_date = get_option('neetrino_bitrix24_next_send_date');
        
        // Форматируем даты
        $last_send_formatted = $last_send_date ? date('d.m.Y', strtotime($last_send_date)) : 'Никогда';
        $next_send_formatted = $next_send_date ? date('d.m.Y', strtotime($next_send_date)) : '01.' . date('m.Y', strtotime('first day of next month'));
        
        // Определяем статус
        $status_text = 'Ожидает отправки';
        $status_icon = 'dashicons-clock';
        $status_color = '#ffa500';
        
        if ($last_send_status === 'success') {
            $status_text = 'Активен';
            $status_icon = 'dashicons-yes-alt';
            $status_color = '#00a32a';
        } elseif ($last_send_status === 'error') {
            $status_text = 'Ошибка отправки';
            $status_icon = 'dashicons-warning';
            $status_color = '#d63638';
        }
        
        ?>
        <div class="sync-status-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
            <!-- Статус этого месяца -->
            <div class="sync-status-item">
                <div class="sync-status-label">Статус этого месяца</div>
                <div class="sync-status-value" style="color: <?php echo $status_color; ?>;">
                    <span class="dashicons <?php echo $status_icon; ?>"></span>
                    <?php echo $status_text; ?>
                </div>
            </div>
            
            <!-- Последняя отправка -->
            <div class="sync-status-item">
                <div class="sync-status-label">Последняя отправка</div>
                <div class="sync-status-value">
                    <?php echo $last_send_formatted; ?>
                </div>
            </div>
            
            <!-- Следующая отправка -->
            <div class="sync-status-item">
                <div class="sync-status-label">Следующая отправка</div>
                <div class="sync-status-value">
                    <?php echo $next_send_formatted; ?>
                </div>
            </div>
            
            <!-- Модуль Bitrix24 -->
            <div class="sync-status-item">
                <div class="sync-status-label">Модуль Bitrix24</div>
                <div class="sync-status-value" style="color: #00a32a;">
                    <span class="dashicons dashicons-yes-alt"></span>
                    Активен
                </div>
            </div>
        </div>
        
        <style>
            .sync-status-item {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 6px;
                border-left: 4px solid #ff6600;
            }
            
            .sync-status-label {
                font-size: 12px;
                color: #666;
                font-weight: 500;
                margin-bottom: 5px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .sync-status-value {
                font-size: 14px;
                font-weight: 600;
                color: #1d2327;
                display: flex;
                align-items: center;
                gap: 5px;
            }
            
            .sync-status-value .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
            }
        </style>
        <?php
    }
}

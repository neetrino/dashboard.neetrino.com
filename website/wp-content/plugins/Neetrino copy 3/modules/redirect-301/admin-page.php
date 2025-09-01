<?php
if (!defined('ABSPATH')) {
    exit;
}

// Список популярных стран
$popular_countries = [
    'RU' => 'Россия',
    'UA' => 'Украина', 
    'BY' => 'Беларусь',
    'KZ' => 'Казахстан',
    'AM' => 'Армения',
    'GE' => 'Грузия',
    'US' => 'США',
    'GB' => 'Великобритания',
    'DE' => 'Германия',
    'FR' => 'Франция',
    'ES' => 'Испания',
    'IT' => 'Италия',
    'CN' => 'Китай',
    'JP' => 'Япония',
    'KR' => 'Южная Корея',
    'BR' => 'Бразилия',
    'IN' => 'Индия',
    'TR' => 'Турция',
    'PL' => 'Польша',
    'NL' => 'Нидерланды'
];
?>

<div class="wrap redirect-301-modern">    <form method="post" action="">
        <?php wp_nonce_field('neetrino_redirect_301_settings'); ?>
        
        <!-- Скрытые поля для дополнительных настроек -->
        <input type="hidden" name="exclude_admin_users" value="<?php echo $exclude_admin_users ? '1' : '0'; ?>" id="hidden_exclude_admin_users">
        <input type="hidden" name="enable_logging" value="<?php echo $enable_logging ? '1' : '0'; ?>" id="hidden_enable_logging">
        <!-- Заголовок -->
        <div class="header-section">
            <div class="header-title">                <div class="title-line">
                    <span class="module-icon dashicons dashicons-randomize"></span>
                    <h2>Redirect 301</h2>
                </div>
                <span class="module-subtitle">Перенаправление по странам</span>
            </div>            <div class="header-actions">
                <button type="button" id="instructions-btn" class="modern-btn instructions-btn">📖 Инструкция</button>
                <button type="button" id="test-ip-btn" class="modern-btn test-btn">🧪 Тест IP</button>
                <button type="button" id="clear-cache-btn" class="modern-btn clear-btn">🗑️ Очистить кеш</button>
                <button type="button" id="settings-btn" class="modern-btn settings-btn">⚙️ Настройки</button>
            </div>
        </div>        <!-- Настройки по умолчанию -->
        <div class="settings-card">
            <h3>Настройки для всех остальных стран</h3>
            
            <div class="default-settings">
                <div class="default-action-compact">
                    <div class="default-action-buttons">
                        <button type="button" 
                                class="default-action-btn stay-btn <?php echo ($default_action === 'stay') ? 'active' : ''; ?>" 
                                data-value="stay">
                            <span class="btn-icon">🏠</span>
                            <span class="btn-text">Остаются на сайте</span>
                        </button>
                        
                        <div class="default-redirect-wrapper <?php echo ($default_action === 'redirect') ? 'active' : ''; ?>">
                            <button type="button" 
                                    class="default-action-btn default-redirect-btn <?php echo ($default_action === 'redirect') ? 'active' : ''; ?>" 
                                    data-value="redirect">
                                <span class="btn-icon">🔀</span>
                                <span class="btn-text">Перенаправить на:</span>
                            </button>                            <div class="default-url-wrapper <?php echo ($default_action !== 'redirect') ? 'hidden' : ''; ?>">
                                <span class="default-protocol-inline">URL:</span>
                                <input type="text" 
                                       name="default_redirect_url" 
                                       value="<?php echo esc_attr($default_redirect_url); ?>"
                                       placeholder="https://example.com"
                                       class="default-url-field"
                                       data-full-url="<?php echo esc_attr($default_redirect_url); ?>">
                            </div>
                        </div>
                        
                        <input type="hidden" name="default_action" value="<?php echo esc_attr($default_action); ?>" id="default_action_input">
                    </div>
                </div>
            </div>
        </div>

        <!-- Правила для конкретных стран -->
        <div class="settings-card">
            <h3>Правила для конкретных стран</h3>
            <p class="description">Выберите страны и укажите URL для перенаправления</p>
              <div id="country-rules-container">
                <?php if (!empty($country_rules)): ?>
                    <?php foreach ($country_rules as $index => $rule): ?>
                        <div class="country-rule-compact" data-index="<?php echo $index; ?>">
                            <select name="country_rules[<?php echo $index; ?>][country]" class="country-select-compact">
                                <option value="">Выберите страну</option>
                                <?php foreach ($popular_countries as $code => $name): ?>
                                    <option value="<?php echo esc_attr($code); ?>" <?php selected($rule['country'], $code); ?>>
                                        <?php echo esc_html($name); ?> (<?php echo esc_html($code); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                              <div class="action-buttons-compact">
                                <button type="button" 
                                        class="action-btn-compact stay-btn <?php echo ($rule['action'] === 'stay') ? 'active' : ''; ?>" 
                                        data-value="stay"
                                        data-index="<?php echo $index; ?>">
                                    <span class="btn-icon">🏠</span>
                                    <span class="btn-text">Остаются</span>
                                </button>
                                  <div class="redirect-btn-wrapper <?php echo ($rule['action'] === 'redirect') ? 'active' : ''; ?>">
                                    <button type="button" 
                                            class="action-btn-compact redirect-btn-compact <?php echo ($rule['action'] === 'redirect') ? 'active' : ''; ?>" 
                                            data-value="redirect"
                                            data-index="<?php echo $index; ?>">
                                        <span class="btn-icon">🔀</span>
                                        <span class="btn-text">Перенаправить на:</span>
                                    </button>                                    <div class="url-input-wrapper <?php echo ($rule['action'] !== 'redirect') ? 'hidden' : ''; ?>">
                                        <span class="protocol-inline">URL:</span>
                                        <input type="text" 
                                               name="country_rules[<?php echo $index; ?>][url]" 
                                               value="<?php echo esc_attr($rule['url'] ?? ''); ?>"
                                               placeholder="https://example.com"
                                               class="url-input-field"
                                               data-full-url="<?php echo esc_attr($rule['url'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <input type="hidden" 
                                       name="country_rules[<?php echo $index; ?>][action]" 
                                       value="<?php echo esc_attr($rule['action']); ?>" 
                                       class="country-action-input">
                            </div>
                            
                            <button type="button" class="delete-rule-btn" title="Удалить правило">
                                <span class="trash-icon">🗑️</span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
              <button type="button" id="add-country-rule" class="modern-btn add-btn">+ Добавить страну</button>
        </div>

        <!-- Логи (если включены) -->
        <?php if ($enable_logging): ?>
        <div class="settings-card">
            <h3>Последние перенаправления</h3>
            <?php
            $logs = get_option('neetrino_redirect_301_logs', []);
            if (!empty($logs)):
            ?>
                <div class="logs-container">
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>Время</th>
                                <th>IP</th>
                                <th>Страна</th>
                                <th>URL перенаправления</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($logs, 0, 20) as $log): ?>
                                <tr>
                                    <td><?php echo esc_html($log['timestamp']); ?></td>
                                    <td><?php echo esc_html($log['ip']); ?></td>
                                    <td>
                                        <?php 
                                        $country_name = $popular_countries[$log['country']] ?? $log['country'];
                                        echo esc_html($country_name . ' (' . $log['country'] . ')');
                                        ?>
                                    </td>
                                    <td><a href="<?php echo esc_url($log['redirect_url']); ?>" target="_blank"><?php echo esc_html($log['redirect_url']); ?></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-logs">Пока нет записей о перенаправлениях</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Кнопка сохранения -->
        <div class="submit-section">
            <button type="submit" name="submit" class="modern-btn submit-btn">💾 Сохранить настройки</button>
        </div></form>
</div>

<!-- Модальное окно с инструкцией -->
<div id="instructions-modal" class="modal" style="display: none;">
    <div class="modal-content instructions-modal">
        <span class="close">&times;</span>
        <h3>📖 Инструкция по использованию модуля Redirect 301</h3>
        
        <div class="instructions-content">
            <div class="instruction-section">
                <h4>🎯 Что делает модуль</h4>
                <p>Модуль автоматически определяет страну посетителя по IP-адресу и перенаправляет его на нужный сайт согласно настроенным правилам.</p>
            </div>

            <div class="instruction-section">
                <h4>⚙️ Как настроить</h4>
                <ol>
                    <li><strong>Настройки по умолчанию:</strong>
                        <ul>
                            <li>🏠 <strong>"Остаются на сайте"</strong> - посетители остаются на текущем сайте</li>
                            <li>🔀 <strong>"Перенаправить на"</strong> - все посетители отправляются на указанный URL</li>
                        </ul>
                    </li>
                    <li><strong>Правила для стран:</strong>
                        <ul>
                            <li>Нажмите <strong>"+ Добавить страну"</strong></li>
                            <li>Выберите страну из списка</li>
                            <li>Выберите действие: остаются или перенаправляются</li>
                            <li>Если перенаправляются - укажите URL</li>
                        </ul>
                    </li>
                </ol>
            </div>

            <div class="instruction-section">
                <h4>🔧 Дополнительные функции</h4>
                <ul>
                    <li><strong>🧪 Тест IP</strong> - проверьте, как работает определение страны для конкретного IP</li>
                    <li><strong>🗑️ Очистить кеш</strong> - сброс кеша определения стран (если что-то работает неправильно)</li>
                    <li><strong>⚙️ Настройки</strong> - дополнительные параметры:
                        <ul>
                            <li>Исключение администраторов (рекомендуется оставить включенным)</li>
                            <li>Логирование перенаправлений для анализа</li>
                        </ul>
                    </li>
                </ul>
            </div>

            <div class="instruction-section">
                <h4>🛡️ Встроенная защита</h4>
                <ul>
                    <li><strong>Администраторы</strong> - автоматически исключаются из перенаправлений</li>
                    <li><strong>Админ-панель</strong> - все страницы /wp-admin/ не перенаправляются</li>
                    <li><strong>Поисковые боты</strong> - Google, Yandex и другие боты исключены</li>
                </ul>
            </div>

            <div class="instruction-section">
                <h4>💡 Полезные советы</h4>
                <ul>
                    <li>URL можно указывать без протокола (автоматически добавится https://)</li>
                    <li>Кеш определения стран действует 24 часа</li>
                    <li>Используйте "Тест IP" для проверки настроек</li>
                    <li>Логи помогут отследить, кого и куда перенаправляет модуль</li>
                </ul>
            </div>

            <div class="instruction-section warning">
                <h4>⚠️ Важно</h4>
                <p>Перед активацией тщательно проверьте настройки! Неправильная конфигурация может заблокировать доступ к сайту для определенных стран.</p>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для тестирования IP -->
<div id="test-ip-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Тестирование IP адреса</h3>
        <p>Введите IP адрес для проверки определения страны и правил перенаправления:</p>
        <input type="text" id="test-ip-input" placeholder="192.168.1.1" class="test-input">
        <button type="button" id="run-test" class="modern-btn test-btn">Проверить</button>
        <div id="test-results" style="display: none;">
            <h4>Результат:</h4>
            <div id="test-output"></div>
        </div>
    </div>
</div>

<!-- Модальное окно для дополнительных настроек -->
<div id="settings-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Дополнительные настройки</h3>
        <p style="color: #64748b; font-size: 13px; margin-bottom: 16px;">Настройте исключения и логирование перенаправлений</p>
        
        <div class="additional-settings">
            <label class="checkbox-option">
                <input type="checkbox" name="exclude_admin_users" <?php checked($exclude_admin_users); ?>>
                <span class="checkbox-label">Исключить администраторов из перенаправления</span>
            </label>
            
            <label class="checkbox-option">
                <input type="checkbox" name="enable_logging" <?php checked($enable_logging); ?>>
                <span class="checkbox-label">Включить логирование перенаправлений</span>
            </label>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <button type="button" id="save-settings" class="modern-btn submit-btn">Применить</button>
        </div>
    </div>
</div>

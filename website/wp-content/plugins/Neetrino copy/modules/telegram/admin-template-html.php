<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * HTML шаблон админ-панели Telegram модуля
 * Основная структура и PHP логика без встроенных стилей и скриптов
 */

// Извлечение переменных, переданных из основного модуля
$bot_token = $template_vars['bot_token'] ?? '';
$chat_ids_raw = $template_vars['chat_ids_raw'] ?? '';
$existing_chats = $template_vars['existing_chats'] ?? array();
$option_name = $template_vars['option_name'] ?? '';
$admin_url = $template_vars['admin_url'] ?? '';

// Проверяем состояние безопасного токена
$is_token_saved = TelegramTokenSecurity::is_token_set();
?>

<div class="wrap telegram-admin-container">
    <!-- Header Section -->
    <div class="telegram-admin-header">
        <h1>📱 Telegram</h1>
        <p>Уведомления о заказах WooCommerce в Telegram с современным интерфейсом</p>
    </div>
      <!-- Status Messages -->
    <?php if (!$is_token_saved && empty($bot_token)): ?>
        <div class="telegram-status warning">
            <span>⚠️</span>
            <span><strong>Требуется настройка:</strong> Введите Bot Token для начала работы</span>
        </div>
    <?php elseif (($is_token_saved || !empty($bot_token)) && empty($existing_chats)): ?>
        <div class="telegram-status warning">
            <span>⚠️</span>
            <span><strong>Требуется настройка:</strong> Добавьте чаты для отправки уведомлений</span>
        </div>
    <?php else: ?>
        <div class="telegram-status success">
            <span>✅</span>
            <span><strong>Готово к работе:</strong> Модуль настроен и активен (<?php echo count($existing_chats); ?> чатов)</span>
        </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <div class="telegram-admin-content">        <!-- Settings Card -->
        <div class="telegram-admin-card">
            <div class="card-header-with-button">
                <h2>⚙️ Основные настройки</h2>
                <button type="button" class="telegram-btn telegram-btn-info header-instructions-btn" onclick="toggleInstructions()">
                    📖 Инструкция
                </button>
            </div>
              <!-- Secure Token Field -->
            <?php if (!$is_token_saved): ?>
                <!-- Input State (when no token saved) -->
                <div class="secure-token-container">
                    <div class="secure-token-header">
                        <div class="secure-token-icon">🔓</div>
                        <div>
                            <div class="secure-token-title">🤖 Bot Token</div>
                            <div class="secure-token-subtitle">Введите токен от @BotFather</div>
                        </div>
                    </div>
                    
                    <div class="secure-token-input-group">
                        <input 
                            type="text" 
                            id="secure-token-input"
                            class="secure-token-input" 
                            placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz"
                            autocomplete="off"
                        />
                        <button type="button" class="secure-token-btn save-btn save-token-btn" onclick="saveSecureToken()">
                            <span>🔒</span> Сохранить и зашифровать
                        </button>
                    </div>
                    
                    <div class="secure-token-help">
                        <span class="help-icon">💡</span>
                        <span>Получите токен от <strong>@BotFather</strong> в Telegram. Токен будет зашифрован перед сохранением.</span>
                    </div>
                </div>
            <?php else: ?>
                <!-- Saved State (when token is saved) -->                <div class="secure-token-saved-container">
                    <div class="secure-token-saved-content">
                        <span class="shield-icon">🛡️</span>
                        <span class="saved-text">Токен безопасно сохранен и зашифрован</span>
                        <button type="button" class="secure-token-delete-btn" onclick="deleteSecureToken()">
                            <span>🗑️</span> Удалить
                        </button>
                    </div>
                </div>
                
                <!-- Bot Information Section -->
                <div class="bot-info-container" id="bot-info-container" style="display: none;">
                    <div class="bot-info-header">
                        <div class="bot-avatar">🤖</div>
                        <div class="bot-details">
                            <div class="bot-name" id="bot-name">Загрузка...</div>
                            <div class="bot-username" id="bot-username">@...</div>
                        </div>
                        <button type="button" class="copy-bot-btn" id="copy-bot-btn" onclick="copyBotUsername()">
                            <span>📋</span> Копировать
                        </button>
                    </div>
                    <div class="bot-info-description">
                        💡 Отправьте команду <code>/start</code> боту, чтобы он смог отправлять вам уведомления
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="post" action="options.php" id="telegram-settings-form" style="display: none;">
                <?php settings_fields('telegram_group'); ?>
                <input type="hidden" name="<?php echo esc_attr($option_name); ?>[chat_ids]" id="chat_ids_hidden" value="<?php echo esc_attr($chat_ids_raw); ?>" />
            </form>
            
            <!-- Chat Management Section -->
            <div class="divider">
                <h2>💬 Управление чатами</h2>                <!-- Search Section -->
                <div id="search-section" class="<?php echo (!$is_token_saved && empty($bot_token)) ? 'hidden' : ''; ?>">
                    <div class="telegram-chat-search">
                        <input 
                            type="text" 
                            id="chat-search-input" 
                            class="telegram-form-input telegram-search-input" 
                            placeholder="🔍 Введите имя чата или группы для поиска..."
                            oninput="filterChats()"
                        />
                        <button type="button" class="telegram-btn telegram-btn-secondary modern-search-btn" onclick="searchChats()">
                            🔍 Найти чаты
                        </button>
                    </div>
                    
                    <div id="chat-search-results" class="hidden">
                        <div id="chat-list" class="telegram-chat-list">
                            <div class="telegram-loading">
                                <div class="loading-spinner"></div>
                                Поиск доступных чатов...
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Empty Token Message -->
                <?php if (!$is_token_saved && empty($bot_token)): ?>
                    <div class="telegram-empty">
                        Сначала введите Bot Token в настройках
                    </div>
                <?php endif; ?>
                
                <!-- Existing Chats Section -->
                <div class="telegram-existing-chats">
                    <h3 style="margin: 0 0 20px 0; color: #374151; font-size: 18px; font-weight: 600;">
                        💬 Подключенные чаты:
                    </h3>
                    <div id="existing-chats-list">
                        <?php if (empty($existing_chats)): ?>
                            <div class="telegram-empty" style="padding: 40px 20px;">
                                Чаты не добавлены
                            </div>
                        <?php else: ?>
                            <?php foreach ($existing_chats as $index => $chat): ?>
                                <div class="telegram-existing-chat" data-chat-id="<?php echo esc_attr($chat['id']); ?>">
                                    <div class="telegram-existing-chat-info">
                                        <div class="telegram-chat-id"><?php echo esc_html($chat['id']); ?></div>                                        <div class="telegram-chat-details">
                                            <span class="telegram-chat-type" data-type="<?php echo esc_attr($chat['type']); ?>"><?php echo esc_html($chat['type']); ?></span>
                                            <?php if (!empty($chat['title'])): ?>
                                                <div><strong><?php echo esc_html($chat['title']); ?></strong></div>
                                            <?php endif; ?>
                                            <?php if (!empty($chat['first_name']) || !empty($chat['last_name'])): ?>
                                                <div class="telegram-user-name"><?php echo esc_html(trim($chat['first_name'] . ' ' . $chat['last_name'])); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($chat['username'])): ?>
                                                <div class="telegram-username">@<?php echo esc_html($chat['username']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <button type="button" class="telegram-btn telegram-btn-danger" onclick="removeChat('<?php echo esc_js($chat['id']); ?>')">
                                        🗑️ Удалить
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>                    <!-- Action buttons -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px; gap: 16px;">
                        <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                            <button type="submit" form="telegram-settings-form" class="telegram-btn telegram-btn-primary">
                                💾 Сохранить настройки
                            </button>
                        </div>
                        
                        <?php if (($is_token_saved || !empty($bot_token)) && !empty($existing_chats)): ?>
                        <button type="button" class="telegram-btn telegram-btn-success" onclick="sendTestMessage()">
                            🧪 Отправить тест
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>    </div>
</div>

<!-- Instructions Modal -->
<div id="instructions-modal" class="telegram-instructions-modal hidden">
    <div class="telegram-admin-card instructions-card">
        <h2>📖 Инструкция по использованию</h2>
          <div class="instructions-content">
            <div class="instruction-section">
                <h3>🚀 Быстрый старт</h3>
                <p><strong>Telegram модуль</strong> автоматически отправляет уведомления о новых заказах и изменениях статусов WooCommerce в выбранные чаты Telegram.</p>
                
                <h4>📋 Пошаговая настройка:</h4>
                <ol>
                    <li><strong>Создание бота:</strong>
                        <ul>
                            <li>Напишите <code>@BotFather</code> в Telegram</li>
                            <li>Отправьте команду <code>/newbot</code></li>
                            <li>Придумайте имя и username для бота</li>
                            <li>Скопируйте полученный токен</li>
                        </ul>
                    </li>
                    <li><strong>Настройка токена:</strong>
                        <ul>
                            <li>Вставьте токен в поле "Bot Token"</li>
                            <li>Нажмите "Сохранить и зашифровать"</li>
                            <li>Токен будет безопасно зашифрован</li>
                        </ul>
                    </li>                    <li><strong>Добавление чатов:</strong>
                        <ul>
                            <li>🤖 Добавьте бота в нужные чаты/группы или начните личный диалог</li>
                            <li style="background: #fff3cd; padding: 10px; border-radius: 6px; border-left: 4px solid #ffc107; margin: 8px 0;"><strong>⚠️ ВАЖНО:</strong> 💬 <strong>Напишите боту любое слово</strong> в личном чате или в группе (например: "привет", "тест", "/start")</li>
                            <li>🔍 Нажмите кнопку <strong>"🔍 Поиск"</strong> для поиска доступных чатов</li>
                            <li>📋 В списке результатов найдите нужный чат</li>
                            <li>➕ Нажмите кнопку <strong>"➕ Добавить"</strong> рядом с нужным чатом</li>
                            <li>💾 Нажмите <strong>"💾 Сохранить настройки"</strong> для применения изменений</li>
                        </ul>
                    </li>
                </ol>                <h4>⚠️ Важно для групп:</h4>
                <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 15px 0;">
                    <p><strong>Если группа не отображается в поиске:</strong></p>
                    <ol>
                        <li>🚪 <strong>Удалите бота</strong> из группы (через настройки группы)</li>
                        <li>➕ <strong>Добавьте бота заново</strong> в группу</li>
                        <li style="background: #ffebcc; padding: 8px; border-radius: 4px; margin: 5px 0; border: 1px solid #ff9800;"><strong>💬 ОБЯЗАТЕЛЬНО напишите любое сообщение</strong> в группе (например: "тест", "привет")</li>
                        <li>🔄 Вернитесь в админ-панель и <strong>нажмите "🔍 Поиск"</strong> снова</li>
                        <li>✅ Группа должна появиться в списке результатов</li>
                    </ol>
                    <p><em>🔐 Это связано с политикой безопасности Telegram API</em></p>
                </div>
                  <h4>🔍 Подробно о поиске чатов:</h4>
                <div style="background: #ffebcc; padding: 20px; border-radius: 10px; border: 2px solid #ff9800; margin: 20px 0;">
                    <h5 style="color: #e65100; margin: 0 0 15px 0; font-size: 16px;">🚨 <strong>ОБЯЗАТЕЛЬНОЕ УСЛОВИЕ:</strong></h5>
                    <p style="color: #bf360c; margin: 0 0 15px 0; font-weight: 600; font-size: 15px;">
                        💬 <strong>Перед поиском чатов ОБЯЗАТЕЛЬНО напишите боту любое сообщение:</strong>
                    </p>
                    <ul style="color: #bf360c; margin: 10px 0;">
                        <li><strong>В личном чате:</strong> напишите боту "привет", "тест" или "/start"</li>
                        <li><strong>В группе:</strong> отправьте любое сообщение в группу с ботом</li>
                        <li><strong>Без этого чат НЕ ПОЯВИТСЯ в поиске!</strong></li>
                    </ul>
                </div>
                <div style="background: #e7f3ff; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3; margin: 15px 0;">
                    <ol>
                        <li>🤖 Убедитесь, что бот добавлен в чат/группу или начат личный диалог</li>
                        <li style="background: #fff3cd; padding: 8px; border-radius: 4px; margin: 5px 0;"><strong>💬 НАПИШИТЕ БОТУ ЛЮБОЕ СООБЩЕНИЕ</strong> (это критически важно!)</li>
                        <li>🔍 Нажмите кнопку <strong>"🔍 Поиск"</strong> в админ-панели</li>
                        <li>⏳ Дождитесь загрузки списка чатов</li>
                        <li>📋 Найдите нужный чат в списке результатов</li>
                        <li>➕ Нажмите <strong>"➕ Добавить"</strong> рядом с нужным чатом</li>
                        <li>💾 Обязательно нажмите <strong>"💾 Сохранить настройки"</strong></li>
                    </ol>
                </div>
                  <h4>🔒 Безопасность</h4>
                <div style="background: #f0f9ff; padding: 15px; border-radius: 8px; border-left: 4px solid #0ea5e9; margin: 15px 0;">
                    <p>В поиске отображаются только <strong>последние активные чаты</strong> из соображений безопасности.</p>
                    <p style="color: #0c4a6e; font-weight: 600;">💬 <strong>Важно:</strong> Чат становится "активным" только после того, как вы напишете боту любое сообщение в личном чате или в группе.</p>
                    <p><strong>Если нужный чат не виден:</strong> отправьте сообщение боту ("привет", "тест", "/start") и повторите поиск.</p>
                </div>
                
                <h4>📦 Что отправляется:</h4>
                <ul>
                    <li><strong>Новые заказы:</strong> информация о клиенте, товарах, сумме</li>
                    <li><strong>Изменения статуса:</strong> уведомления о смене статуса заказа</li>
                    <li><strong>Тестовые сообщения:</strong> для проверки настроек</li>
                </ul>
                  <h4>🛠️ Полезные команды:</h4>
                <ul>
                    <li>💾 <strong>"Сохранить настройки"</strong> - сохраняет выбранные чаты в базу данных</li>
                    <li>🧪 <strong>"Отправить тест"</strong> - отправляет тестовое сообщение для проверки работы</li>
                    <li>🔍 <strong>"Поиск"</strong> - обновляет список доступных чатов</li>
                    <li>🗑️ <strong>"Удалить"</strong> - удаляет чат из списка получателей уведомлений</li>
                    <li>📖 <strong>"Инструкция"</strong> - открывает это окно с подробной инструкцией</li>
                </ul>
                
                <div style="background: #d1ecf1; padding: 15px; border-radius: 8px; border-left: 4px solid #17a2b8; margin: 15px 0;">
                    <p><strong>💡 Совет:</strong> После настройки сделайте тестовый заказ или используйте кнопку "Отправить тест" для проверки работы модуля.</p>
                </div>
            </div>
        </div>
        
        <div class="instructions-footer">
            <button type="button" class="telegram-btn telegram-btn-secondary" onclick="toggleInstructions()">
                ✅ Понятно, закрыть
            </button>
        </div>
    </div>
</div>

<!-- Передача admin_url в JavaScript -->
<script>
window.telegramAdminUrl = '<?php echo esc_js($admin_url); ?>';

// Функция для переключения модального окна инструкций
function toggleInstructions() {
    var modal = document.getElementById('instructions-modal');
    if (!modal) return;
    
    var isHidden = modal.classList.contains('hidden');
    
    if (isHidden) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        modal.onclick = function(e) {
            if (e.target === modal) {
                toggleInstructions();
            }
        };
        
        document.addEventListener('keydown', function escapeHandler(e) {
            if (e.key === 'Escape') {
                toggleInstructions();
                document.removeEventListener('keydown', escapeHandler);
            }
        });
    } else {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
}
</script>

<script>
// Initialize the search section visibility when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Make sure toggleSearchSection is available and call it
    if (typeof toggleSearchSection === 'function') {
        toggleSearchSection();
    }
});
</script>

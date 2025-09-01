/**
 * JavaScript функции для админ-панели Telegram модуля
 * Все интерактивные функции для управления чатами и настройками
 */

// Debug information
console.log('Telegram Admin Scripts Loaded - Version: 1.0.8.' + Date.now());
console.log('Available functions:', {
    loadBotInfo: typeof loadBotInfo,
    applyChatTypeColors: typeof applyChatTypeColors,
    saveSecureToken: typeof saveSecureToken
});

// Глобальная переменная для хранения найденных чатов для фильтрации
let allDiscoveredChats = [];

/**
 * Показать/скрыть секцию поиска чатов в зависимости от наличия Bot Token
 */
function toggleSearchSection() {
    const searchSection = document.getElementById('search-section');
    
    // Проверяем, сохранен ли токен, через JavaScript переменную
    if (window.telegramAdminAjax && window.telegramAdminAjax.isTokenSaved) {
        searchSection.classList.remove('hidden');
    } else {
        searchSection.classList.add('hidden');
    }
}

/**
 * Поиск доступных чатов через Telegram API
 */
function searchChats() {
    const resultsDiv = document.getElementById('chat-search-results');
    const chatList = document.getElementById('chat-list');
    
    resultsDiv.classList.remove('hidden');
    chatList.innerHTML = '<div class="telegram-loading">Получение токена...</div>';
    
    // Сначала получаем токен через AJAX
    const tokenData = new FormData();
    tokenData.append('action', 'telegram_get_token');
    tokenData.append('security', telegramAdminAjax.nonce);
    
    fetch(telegramAdminAjax.ajaxurl, {
        method: 'POST',
        body: tokenData
    })
    .then(response => response.json())
    .then(tokenResult => {
        if (!tokenResult.success || !tokenResult.token) {
            chatList.innerHTML = '<div class="telegram-empty">❌ Токен не найден. Сначала сохраните токен.</div>';
            return;
        }
        
        const botToken = tokenResult.token;
        chatList.innerHTML = '<div class="telegram-loading">Поиск доступных чатов...</div>';
          // Динамически получаем admin_url из PHP
        const adminUrl = telegramAdminAjax.ajaxurl || '';
        
        return fetch(adminUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=telegram_search_chats&bot_token=' + encodeURIComponent(botToken)
        });
    })
    .then(response => {
        if (!response) return;
        return response.json();
    })
    .then(data => {
        if (data && data.success) {
            displayChatResults(data.data);
        } else if (data) {
            chatList.innerHTML = '<div class="telegram-empty">❌ Ошибка: ' + data.data + '</div>';
        }
    })    .catch(error => {
        console.error('Ошибка при поиске чатов:', error);
        chatList.innerHTML = '<div class="telegram-empty">❌ Ошибка соединения</div>';
    });
}

/**
 * Отображение результатов поиска чатов
 */
function displayChatResults(chats) {
    const chatList = document.getElementById('chat-list');
    
    // Сохраняем все чаты глобально для фильтрации
    allDiscoveredChats = chats;
    
    if (chats.length === 0) {
        chatList.innerHTML = '<div class="telegram-empty">📭 Доступные чаты не найдены.<br><br>💡 Отправьте сообщение боту, чтобы он появился в списке.</div>';
        return;
    }
      // Сохраняем все найденные данные чатов в базу данных
    if (chats.length > 0) {
        const adminUrl = telegramAdminAjax.ajaxurl || '';
        fetch(adminUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=telegram_save_chat_data&chat_data=' + encodeURIComponent(JSON.stringify(chats))
        })
        .catch(error => {
            console.log('Ошибка сохранения данных чатов:', error);
        });
    }
    
    // Отображаем все чаты изначально
    renderChatList(chats);
}

/**
 * Отрисовка списка чатов
 */
function renderChatList(chats) {
    const chatList = document.getElementById('chat-list');
    
    if (chats.length === 0) {
        chatList.innerHTML = '<div class="telegram-empty">📭 Чаты не найдены по запросу.<br><br>💡 Попробуйте изменить поисковый запрос.</div>';
        return;
    }
    
    // Получаем существующие ID чатов для сравнения
    const existingChats = document.querySelectorAll('#existing-chats-list .telegram-existing-chat');
    const existingChatIds = Array.from(existingChats).map(el => el.dataset.chatId);
    
    let html = '';
    chats.forEach(chat => {
        const displayName = chat.title || (chat.first_name + ' ' + chat.last_name).trim() || chat.username || 'Без имени';
        const isAlreadyAdded = existingChatIds.includes(chat.id);
        
        const buttonClass = isAlreadyAdded ? 'telegram-btn telegram-btn-inactive' : 'telegram-btn telegram-btn-primary';
        const buttonText = isAlreadyAdded ? '✓ Добавлен' : '➕ Добавить';
        const buttonAction = isAlreadyAdded ? '' : `onclick="addChat('${chat.id}', '${chat.type}', '${chat.title || ''}', '${chat.username || ''}', '${chat.first_name || ''}', '${chat.last_name || ''}')"`;        html += `
            <div class="telegram-chat-item">
                <div class="telegram-chat-info">
                    <div class="telegram-chat-id">${chat.id}</div>
                    <div class="telegram-chat-details">
                        <span class="telegram-chat-type" data-type="${chat.type}">${chat.type}</span>
                        ${chat.title ? `<div><strong>${chat.title}</strong></div>` : ''}
                        ${(chat.first_name || chat.last_name) ? `<div class="telegram-user-name">${(chat.first_name + ' ' + chat.last_name).trim()}</div>` : ''}
                        ${chat.username ? `<div class="telegram-username">@${chat.username}</div>` : ''}
                    </div>
                </div>
                <button type="button" class="${buttonClass}" ${buttonAction} ${isAlreadyAdded ? 'disabled' : ''}>
                    ${buttonText}
                </button>
            </div>
        `;
    });
      chatList.innerHTML = html;
    
    // Apply chat type colors after rendering
    applyChatTypeColors();
}

/**
 * Фильтрация чатов по поисковому запросу
 */
function filterChats() {
    const searchInput = document.getElementById('chat-search-input');
    const searchTerm = searchInput.value.toLowerCase().trim();
    
    // Если нет поискового запроса, показываем все чаты
    if (searchTerm === '') {
        renderChatList(allDiscoveredChats);
        return;
    }
    
    // Фильтруем чаты по поисковому запросу
    const filteredChats = allDiscoveredChats.filter(chat => {
        const searchText = [
            chat.id,
            chat.type,
            chat.title || '',
            chat.username || '',
            chat.first_name || '',
            chat.last_name || '',
            (chat.first_name + ' ' + chat.last_name).trim()
        ].join(' ').toLowerCase();
        
        return searchText.includes(searchTerm);
    });
    
    renderChatList(filteredChats);
}

/**
 * Добавление чата в список активных
 */
function addChat(chatId, chatType, title, username, firstName, lastName) {
    // Проверяем, не существует ли чат уже
    const existingChats = document.querySelectorAll('#existing-chats-list .telegram-existing-chat');
    for (let chat of existingChats) {
        if (chat.dataset.chatId === chatId) {
            return; // Молча возвращаемся, если уже существует
        }
    }
    
    // Добавляем в список существующих чатов
    const existingChatsList = document.getElementById('existing-chats-list');
    const emptyDiv = existingChatsList.querySelector('.telegram-empty');
    if (emptyDiv) {
        emptyDiv.remove();
    }
    
    const chatElement = document.createElement('div');
    chatElement.className = 'telegram-existing-chat';
    chatElement.dataset.chatId = chatId;
    
    const displayName = title || (firstName + ' ' + lastName).trim() || username || 'Без имени';      chatElement.innerHTML = `
        <div class="telegram-existing-chat-info">
            <div class="telegram-chat-id">${chatId}</div>
            <div class="telegram-chat-details">
                <span class="telegram-chat-type" data-type="${chatType}">${chatType}</span>
                ${title ? `<div><strong>${title}</strong></div>` : ''}
                ${(firstName || lastName) ? `<div class="telegram-user-name">${(firstName + ' ' + lastName).trim()}</div>` : ''}
                ${username ? `<div class="telegram-username">@${username}</div>` : ''}
            </div>
        </div>
        <button type="button" class="telegram-btn telegram-btn-danger" onclick="removeChat('${chatId}')">
            🗑️ Удалить
        </button>
    `;
      existingChatsList.appendChild(chatElement);
    
    // Apply chat type colors after adding element
    applyChatTypeColors();
    
    // Сохраняем данные чата в базу данных
    saveChatData(chatId, chatType, title, username, firstName, lastName);
    
    // Обновляем скрытое поле ввода
    updateChatIdsInput();
    
    // Обновляем состояние кнопок в результатах поиска и повторно применяем текущий фильтр
    updateSearchResultButtons();
    filterChats(); // Повторно применяем текущий фильтр для обновления отображения
}

/**
 * Обновление состояния кнопок в результатах поиска
 */
function updateSearchResultButtons() {
    // Получаем существующие ID чатов
    const existingChats = document.querySelectorAll('#existing-chats-list .telegram-existing-chat');
    const existingChatIds = Array.from(existingChats).map(el => el.dataset.chatId);
    
    // Обновляем кнопки в результатах поиска
    const searchItems = document.querySelectorAll('#chat-list .telegram-chat-item');
    searchItems.forEach(item => {
        const chatId = item.querySelector('.telegram-chat-id').textContent;
        const button = item.querySelector('button');
        
        if (existingChatIds.includes(chatId)) {
            button.className = 'telegram-btn telegram-btn-inactive';
            button.textContent = '✓ Добавлен';
            button.onclick = null;
            button.disabled = true;
        } else {
            button.className = 'telegram-btn telegram-btn-primary';
            button.textContent = '➕ Добавить';
            button.disabled = false;
            // Повторно извлекаем данные чата и устанавливаем onclick
            const chatType = item.querySelector('.telegram-chat-type').textContent;
            const titleEl = item.querySelector('.telegram-chat-details strong');
            const usernameEl = item.querySelector('.telegram-chat-details div:nth-child(3)');
            const nameEl = item.querySelector('.telegram-chat-details div:nth-child(4)');
            
            const title = titleEl ? titleEl.textContent : '';
            const username = usernameEl && usernameEl.textContent.startsWith('@') ? usernameEl.textContent.substring(1) : '';
            const fullName = nameEl && !nameEl.textContent.startsStartsWith('@') ? nameEl.textContent : '';
            const nameParts = fullName.split(' ');
            const firstName = nameParts[0] || '';
            const lastName = nameParts.slice(1).join(' ') || '';
            
            button.onclick = () => addChat(chatId, chatType, title, username, firstName, lastName);
        }
    });
}

/**
 * Сохранение данных чата в базу данных
 */
function saveChatData(chatId, chatType, title, username, firstName, lastName) {
    const chatData = [{
        'id': chatId,
        'type': chatType,
        'title': title,
        'username': username,
        'first_name': firstName,
        'last_name': lastName    }];
    
    const adminUrl = telegramAdminAjax.ajaxurl || '';
    fetch(adminUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=telegram_save_chat_data&chat_data=' + encodeURIComponent(JSON.stringify(chatData))
    })
    .catch(error => {
        console.log('Ошибка сохранения данных чата:', error);
    });
}

/**
 * Удаление чата из списка активных
 */
function removeChat(chatId) {
    if (confirm('🗑️ Удалить этот чат из списка уведомлений?')) {
        const chatElement = document.querySelector(`#existing-chats-list .telegram-existing-chat[data-chat-id="${chatId}"]`);        if (chatElement) {
            chatElement.remove();
            updateChatIdsInput();
            
            // Удаляем данные чата из базы данных
            const adminUrl = telegramAdminAjax.ajaxurl || '';
            fetch(adminUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=telegram_remove_chat_data&chat_id=' + encodeURIComponent(chatId)
            })
            .catch(error => {
                console.log('Ошибка удаления данных чата:', error);
            });
            
            // Показываем сообщение о пустоте, если чатов не осталось
            const remainingChats = document.querySelectorAll('#existing-chats-list .telegram-existing-chat');
            if (remainingChats.length === 0) {
                const existingChatsList = document.getElementById('existing-chats-list');
                existingChatsList.innerHTML = '<div class="telegram-empty" style="padding: 40px 20px;">Чаты не добавлены</div>';
            }
            
            // Обновляем кнопки результатов поиска
            updateSearchResultButtons();
        }
    }
}

/**
 * Обновление скрытого поля с ID чатов
 */
function updateChatIdsInput() {
    const chatElements = document.querySelectorAll('#existing-chats-list .telegram-existing-chat');
    const chatIds = Array.from(chatElements).map(el => el.dataset.chatId);
    document.getElementById('chat_ids_hidden').value = chatIds.join(', ');
}

/**
 * Отправка тестового сообщения
 */
function sendTestMessage() {
    if (confirm('🧪 Отправить тестовое сообщение во все активные чаты?')) {
        // Показываем статус в UI вместо alerts
        const statusDiv = document.createElement('div');
        statusDiv.className = 'telegram-status';
        statusDiv.innerHTML = '<span>⏳</span><span>Отправка тестового сообщения...</span>';
          const headerDiv = document.querySelector('.telegram-admin-header');
        headerDiv.parentNode.insertBefore(statusDiv, headerDiv.nextSibling);
        
        const adminUrl = telegramAdminAjax.ajaxurl || '';
        fetch(adminUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=telegram_test_message'
        }).then(response => response.json())
        .then(data => {
            statusDiv.remove();
            const newStatusDiv = document.createElement('div');
            newStatusDiv.className = data.success ? 'telegram-status success' : 'telegram-status error';
            newStatusDiv.innerHTML = `<span>${data.success ? '✅' : '❌'}</span><span>${data.data}</span>`;
            headerDiv.parentNode.insertBefore(newStatusDiv, headerDiv.nextSibling);
            
            // Удаляем статус через 5 секунд
            setTimeout(() => {
                newStatusDiv.remove();
            }, 5000);
        })
        .catch(error => {
            statusDiv.remove();
            const errorDiv = document.createElement('div');
            errorDiv.className = 'telegram-status error';
            errorDiv.innerHTML = '<span>❌</span><span>Ошибка подключения</span>';
            headerDiv.parentNode.insertBefore(errorDiv, headerDiv.nextSibling);
            
            setTimeout(() => {
                errorDiv.remove();
            }, 5000);
        });
    }
}

/**
 * БЕЗОПАСНОЕ УПРАВЛЕНИЕ ТОКЕНАМИ
 * Функции для сохранения, удаления и управления зашифрованными токенами
 */

/**
 * Сохранение токена с шифрованием
 */
function saveSecureToken() {
    const tokenInput = document.getElementById('secure-token-input');
    const token = tokenInput.value.trim();
    
    if (!token) {
        showTokenMessage('Введите токен для сохранения', 'error');
        return;
    }
    
    // Простая валидация формата токена
    if (!token.match(/^\d+:[A-Za-z0-9_-]+$/)) {
        showTokenMessage('Неверный формат токена. Используйте формат: 123456789:ABCdefGHI...', 'error');
        return;
    }
    
    // Показываем загрузку
    const saveBtn = document.querySelector('.save-token-btn');
    if (saveBtn) {
        saveBtn.innerHTML = '<span>🔄</span> Сохранение...';
        saveBtn.disabled = true;
    }
    
    // AJAX запрос для сохранения
    const data = new FormData();
    data.append('action', 'telegram_save_token');
    data.append('token', token);
    data.append('security', telegramAdminAjax.nonce);
    
    fetch(telegramAdminAjax.ajaxurl, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())    .then(result => {
        if (result.success) {
            showTokenMessage(result.message || 'Токен успешно сохранен и зашифрован!', 'success');
            
            // Load bot info immediately after saving token
            setTimeout(() => {
                loadBotInfo();
            }, 500);
            
            // Перезагружаем страницу для обновления PHP условий через 1.5 секунды
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showTokenMessage(result.message || 'Ошибка при сохранении токена', 'error');
        }
    })
    .catch(error => {
        console.error('Ошибка при сохранении токена:', error);
        showTokenMessage('Ошибка соединения при сохранении токена', 'error');
    })
    .finally(() => {
        // Восстанавливаем кнопку
        if (saveBtn) {
            saveBtn.innerHTML = '<span>🔒</span> Сохранить и зашифровать';
            saveBtn.disabled = false;
        }
    });
}

/**
 * Удаление токена
 */
function deleteSecureToken() {
    if (!confirm('Вы уверены, что хотите удалить сохраненный токен? Это действие нельзя отменить.')) {
        return;
    }
      // Показываем загрузку
    const deleteBtn = document.querySelector('.secure-token-delete-btn');
    if (deleteBtn) {
        deleteBtn.innerHTML = '<span>🔄</span> Удаление...';
        deleteBtn.disabled = true;
    }
    
    // AJAX запрос для удаления
    const data = new FormData();
    data.append('action', 'telegram_delete_token');
    data.append('security', telegramAdminAjax.nonce);
    
    fetch(telegramAdminAjax.ajaxurl, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())    .then(result => {
        if (result.success) {
            showTokenMessage(result.message || 'Токен успешно удален!', 'success');
            
            // Перезагружаем страницу для обновления PHP условий через 1.5 секунды
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showTokenMessage(result.message || 'Ошибка при удалении токена', 'error');
        }
    })
    .catch(error => {
        console.error('Ошибка при удалении токена:', error);
        showTokenMessage('Ошибка соединения при удалении токена', 'error');
    })
    .finally(() => {        // Восстанавливаем кнопку
        if (deleteBtn) {
            deleteBtn.innerHTML = '<span>🗑️</span> Удалить';
            deleteBtn.disabled = false;
        }
    });
}

/**
 * Показать состояние ввода токена
 */
function showTokenInputState() {
    const container = document.querySelector('.secure-token-container');
    const inputGroup = document.querySelector('.secure-token-input-group');
    const savedStatus = document.querySelector('.secure-token-saved-status');
    const header = document.querySelector('.secure-token-header');
    const icon = document.querySelector('.secure-token-icon');
    const title = document.querySelector('.secure-token-title');
    const subtitle = document.querySelector('.secure-token-subtitle');
    
    if (container) {
        container.classList.remove('token-saved');
    }
    
    if (header) {
        header.classList.remove('token-saved');
    }
    
    if (icon) {
        icon.classList.remove('secure');
        icon.innerHTML = '🔓';
    }
    
    if (title) {
        title.textContent = '🤖 Bot Token';
    }
    
    if (subtitle) {
        subtitle.textContent = 'Введите токен от @BotFather';
    }
    
    if (inputGroup) {
        inputGroup.classList.remove('hidden');
    }
    
    if (savedStatus) {
        savedStatus.classList.add('hidden');
    }
}

/**
 * Показать состояние сохраненного токена
 */
function showTokenSavedState() {
    const container = document.querySelector('.secure-token-container');
    const inputGroup = document.querySelector('.secure-token-input-group');
    const savedStatus = document.querySelector('.secure-token-saved-status');
    const header = document.querySelector('.secure-token-header');
    const icon = document.querySelector('.secure-token-icon');
    const title = document.querySelector('.secure-token-title');
    const subtitle = document.querySelector('.secure-token-subtitle');
    const tokenInput = document.getElementById('secure-token-input');
    
    if (container) {
        container.classList.add('token-saved');
    }
    
    if (header) {
        header.classList.add('token-saved');
    }
    
    if (icon) {
        icon.classList.add('secure');
        icon.innerHTML = '🔒';
    }
    
    if (title) {
        title.textContent = '🛡️ Токен сохранен безопасно';
    }
    
    if (subtitle) {
        subtitle.textContent = 'Токен зашифрован и сохранен в базе данных';
    }
    
    if (inputGroup) {
        inputGroup.classList.add('hidden');
    }
    
    if (savedStatus) {
        savedStatus.classList.remove('hidden');
    }
    
    // Очищаем поле ввода
    if (tokenInput) {
        tokenInput.value = '';
    }
}

/**
 * Обновление UI при сохраненном токене
 */
function updateUIForSavedToken() {
    // Обновляем глобальную переменную
    if (window.telegramAdminAjax) {
        window.telegramAdminAjax.isTokenSaved = true;
    }
    
    // Показываем секцию поиска чатов
    const searchSection = document.getElementById('search-section');
    if (searchSection) {
        searchSection.classList.remove('hidden');
    }
    
    // Обновляем статус
    updateStatusMessage();
}

/**
 * Обновление UI при отсутствии токена
 */
function updateUIForNoToken() {
    // Обновляем глобальную переменную
    if (window.telegramAdminAjax) {
        window.telegramAdminAjax.isTokenSaved = false;
    }
    
    // Скрываем секцию поиска чатов
    const searchSection = document.getElementById('search-section');
    if (searchSection) {
        searchSection.classList.add('hidden');
    }
    
    // Обновляем статус
    updateStatusMessage();
}

/**
 * Показать сообщение о состоянии токена
 */
function showTokenMessage(message, type = 'info') {
    // Создаем уведомление
    const notification = document.createElement('div');
    notification.className = `token-notification ${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 32px;
        right: 32px;
        background: ${type === 'success' ? 'linear-gradient(135deg, #22c55e, #16a34a)' : type === 'error' ? 'linear-gradient(135deg, #ef4444, #dc2626)' : 'linear-gradient(135deg, #3b82f6, #2563eb)'};
        color: white;
        padding: 18px 24px;
        border-radius: 12px;
        box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.25), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        z-index: 10000;
        font-weight: 600;
        font-size: 14px;
        max-width: 400px;
        animation: slideIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    `;
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 18px;">${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Автоматически удаляем через 5 секунд
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 400);
    }, 5000);
}

/**
 * Обновление статусного сообщения
 */
function updateStatusMessage() {
    // Здесь можно добавить логику для обновления статусных сообщений
    // в зависимости от состояния токена
}

// CSS для анимаций уведомлений
if (!document.getElementById('token-notification-styles')) {
    const style = document.createElement('style');
    style.id = 'token-notification-styles';
    style.textContent = `
        @keyframes slideIn {
            0% { 
                transform: translateX(120%) scale(0.8); 
                opacity: 0; 
            }
            60% { 
                transform: translateX(-10%) scale(1.05); 
                opacity: 0.8; 
            }
            100% { 
                transform: translateX(0) scale(1); 
                opacity: 1; 
            }
        }
        @keyframes slideOut {
            0% { 
                transform: translateX(0) scale(1); 
                opacity: 1; 
            }
            40% { 
                transform: translateX(10%) scale(0.95); 
                opacity: 0.6; 
            }
            100% { 
                transform: translateX(120%) scale(0.8); 
                opacity: 0; 
            }
        }
    `;
    document.head.appendChild(style);
}

/**
 * Показать/скрыть модальное окно с инструкциями
 */
function toggleInstructions() {
    const modal = document.getElementById('instructions-modal');
    
    if (modal.classList.contains('hidden')) {
        modal.classList.remove('hidden');
        // Предотвращаем прокрутку страницы когда модал открыт
        document.body.style.overflow = 'hidden';
        
        // Закрытие по клику вне модального окна
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                toggleInstructions();
            }
        });
        
        // Закрытие по нажатию Escape
        document.addEventListener('keydown', function escapeHandler(e) {
            if (e.key === 'Escape') {
                toggleInstructions();
                document.removeEventListener('keydown', escapeHandler);
            }
        });
    } else {
        modal.classList.add('hidden');
        // Восстанавливаем прокрутку страницы
        document.body.style.overflow = '';
    }
}

/**
 * BOT INFORMATION FUNCTIONS
 * Functions for loading and displaying bot information
 */

/**
 * Load and display bot information
 */
function loadBotInfo() {
    const botInfoContainer = document.getElementById('bot-info-container');
    const botName = document.getElementById('bot-name');
    const botUsername = document.getElementById('bot-username');
    
    if (!botInfoContainer) return;
    
    // Show loading state
    if (botName) botName.textContent = 'Загрузка...';
    if (botUsername) botUsername.textContent = '@...';
    
    // AJAX request to get bot info
    const data = new FormData();
    data.append('action', 'telegram_get_bot_info');
    data.append('security', telegramAdminAjax.nonce);
    
    fetch(telegramAdminAjax.ajaxurl, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(result => {
        if (result.success && result.bot_info) {
            const bot = result.bot_info;
            
            if (botName) {
                botName.textContent = bot.first_name || 'Неизвестный бот';
            }
            
            if (botUsername) {
                botUsername.textContent = bot.username ? `@${bot.username}` : '@неизвестно';
            }
            
            // Show the bot info container
            botInfoContainer.style.display = 'block';
        } else {
            // Hide container on error
            botInfoContainer.style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Ошибка при загрузке информации о боте:', error);
        // Hide container on error
        botInfoContainer.style.display = 'none';
    });
}

/**
 * Copy bot username to clipboard
 */
function copyBotUsername() {
    const botUsername = document.getElementById('bot-username');
    const copyBtn = document.getElementById('copy-bot-btn');
    
    if (!botUsername) return;
    
    const username = botUsername.textContent;
    
    // Try to copy to clipboard
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(username).then(() => {
            showCopySuccess(copyBtn);
        }).catch(() => {
            fallbackCopyText(username, copyBtn);
        });
    } else {
        fallbackCopyText(username, copyBtn);
    }
}

/**
 * Fallback copy method for older browsers
 */
function fallbackCopyText(text, button) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showCopySuccess(button);
    } catch (err) {
        console.error('Не удалось скопировать текст: ', err);
        showCopyError(button);
    }
    
    document.body.removeChild(textArea);
}

/**
 * Show copy success feedback
 */
function showCopySuccess(button) {
    if (!button) return;
    
    const originalHTML = button.innerHTML;
    button.innerHTML = '<span>✅</span> Скопировано!';
    button.style.background = 'linear-gradient(135deg, rgba(34, 197, 94, 0.9) 0%, rgba(22, 163, 74, 0.9) 100%)';
    
    setTimeout(() => {
        button.innerHTML = originalHTML;
        button.style.background = '';
    }, 2000);
}

/**
 * Show copy error feedback
 */
function showCopyError(button) {
    if (!button) return;
    
    const originalHTML = button.innerHTML;
    button.innerHTML = '<span>❌</span> Ошибка';
    button.style.background = 'linear-gradient(135deg, rgba(239, 68, 68, 0.9) 0%, rgba(220, 38, 38, 0.9) 100%)';
    
    setTimeout(() => {
        button.innerHTML = originalHTML;
        button.style.background = '';
    }, 2000);
}

/**
 * Apply chat type colors to elements
 */
function applyChatTypeColors() {
    console.log('applyChatTypeColors: Starting to apply chat type colors');
    const chatTypeElements = document.querySelectorAll('.telegram-chat-type');
    console.log('applyChatTypeColors: Found', chatTypeElements.length, 'chat type elements');
    
    chatTypeElements.forEach(element => {
        const chatType = element.textContent.toLowerCase().trim();
        console.log('applyChatTypeColors: Processing chat type:', chatType);
        
        // Remove any existing color classes
        element.classList.remove('chat-type-group', 'chat-type-private', 'chat-type-supergroup', 'chat-type-channel');
        
        // Add appropriate color class based on chat type
        switch (chatType) {
            case 'group':
                element.classList.add('chat-type-group');
                console.log('applyChatTypeColors: Added chat-type-group class');
                break;
            case 'private':
                element.classList.add('chat-type-private');
                console.log('applyChatTypeColors: Added chat-type-private class');
                break;
            case 'supergroup':
                element.classList.add('chat-type-supergroup');
                console.log('applyChatTypeColors: Added chat-type-supergroup class');
                break;
            case 'channel':
                element.classList.add('chat-type-channel');
                console.log('applyChatTypeColors: Added chat-type-channel class');
                break;
            default:
                console.log('applyChatTypeColors: Unknown chat type:', chatType);
        }
    });
    console.log('applyChatTypeColors: Completed applying chat type colors');
}

/**
 * PAGE INITIALIZATION
 * Initialize the page when DOM is loaded
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - Initializing Telegram Admin');
    console.log('telegramAdminAjax available:', !!window.telegramAdminAjax);
    console.log('Token saved status:', window.telegramAdminAjax && window.telegramAdminAjax.isTokenSaved);
    
    // Load bot info if token is saved
    if (telegramAdminAjax && telegramAdminAjax.isTokenSaved) {
        console.log('Loading bot info...');
        loadBotInfo();
    }
    
    // Initialize search section visibility
    console.log('Toggling search section...');
    toggleSearchSection();
    
    // Apply chat type colors on initial load
    console.log('Applying chat type colors...');
    applyChatTypeColors();
    
    console.log('Telegram Admin initialization complete');
});

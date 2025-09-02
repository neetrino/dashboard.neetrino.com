/**
 * Neetrino Control Dashboard - Современные HTML шаблоны
 */

window.NeetrinoTemplates = {
    
    /**
     * Карточка сайта для режима сетки
     */
    siteCard(site) {
        const statusClass = this.getStatusClass(site.status);
        const statusIcon = this.getStatusIcon(site.status);
        return `
            <div class="site-card animate-fade-in ${site.selected ? 'selected' : ''} ${site.hidden ? 'hidden' : ''}" 
                 data-site-id="${site.id}" 
                 data-status="${site.status}"
                 data-name="${this.escapeHtml(site.site_name)}">
                
                <!-- Элегантный статус-индикатор в углу -->
                <div class="site-status-indicator ${site.status}" title="${this.getStatusText(site.status)}"></div>
                
                <!-- Красивый чекбокс в правом углу -->
                <input type="checkbox" 
                       class="site-card-checkbox" 
                       data-action="toggle-select" 
                       data-site-id="${site.id}"
                       ${site.selected ? 'checked' : ''}>
                
                <!-- Заголовок карточки: имя отдельно -->
                <div class="site-card-header">
                    <div class="flex flex-col items-center gap-1 text-center w-full">
                        <div class="flex items-center gap-2 justify-center w-full" style="min-width: 200px;">
                            <a href="${site.site_url}" 
                               target="_blank" 
                               class="site-name-clickable text-center flex items-center gap-2"
                               title="Перейти на сайт: ${this.escapeHtml(site.site_url)}">
                                <h3 class="site-card-title text-center" style="text-align: center; white-space: nowrap;">
                                    ${this.escapeHtml(site.site_name)}
                                </h3>
                                <svg class="site-url-icon flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </a>
                        </div>
                        <!-- Версия по центру под названием сайта -->
                        ${site.displayVersion ? `<div style="color: black; font-size: 12px; text-align: center; width: 100%;">v${this.escapeHtml(site.displayVersion)}</div>` : ''}
                        ${site.isBelowMin ? ` <span class="ml-1 inline-block px-2 py-0.5 rounded bg-red-100 text-red-700 text-xs align-middle whitespace-nowrap" title="Требуется v${this.escapeHtml(site.min_required_version)}+">ниже минимума</span>` : ''}
                    </div>
                </div>
                
                <!-- Действия - кнопка команд больше -->
                <div class="site-card-actions">
                    <button data-action="check-status" 
                            data-site-id="${site.id}" 
                            class="modern-btn modern-btn-primary btn-check">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Проверить
                    </button>
                    
                    <button data-action="show-commands" 
                            data-site-id="${site.id}" 
                            class="modern-btn modern-btn-black btn-commands">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h6m0-6l-6 6"></path>
                        </svg>
                        Панель команд
                    </button>
                </div>
            </div>
        `;
    },
    
    /**
     * Элемент списка сайта для режима списка
     */
    siteListItem(site) {
        return `
            <div class="site-list-item animate-fade-in ${site.selected ? 'selected' : ''}" 
                 data-site-id="${site.id}" 
                 data-status="${site.status}"
                 data-name="${this.escapeHtml(site.site_name)}">
                
                <!-- Статус индикатор перед чекбоксом по центру -->
                <div class="site-status-indicator ${site.status}" title="${this.getStatusText(site.status)}"></div>
                
                <!-- Красивый чекбокс -->
                <input type="checkbox" 
                       class="site-select w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500 flex-shrink-0" 
                       data-action="toggle-select" 
                       data-site-id="${site.id}"
                       ${site.selected ? 'checked' : ''}>
                
                <!-- Информация о сайте - название слева, метки справа от имени -->
                <div class="site-info">
                    <div class="site-details">
                        <div class="flex items-center gap-2">
                            <a href="${site.site_url}" 
                               target="_blank" 
                               class="site-name-clickable"
                               title="Перейти на сайт: ${this.escapeHtml(site.site_url)}">
                                <h3 class="site-card-title" style="font-size: 1.1rem; margin-bottom: 0;">
                                    ${this.escapeHtml(site.site_name)}
                                </h3>
                                <svg class="site-url-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </a>
                            ${site.displayVersion ? `<span class=\"ml-1 text-xs font-semibold text-gray-700 align-middle whitespace-nowrap\">${this.escapeHtml(site.displayVersion)}</span>` : ''}
                            ${site.isBelowMin ? ` <span class=\"ml-1 inline-block px-2 py-0.5 rounded bg-red-100 text-red-700 text-xs align-middle whitespace-nowrap\" title=\"Требуется v${this.escapeHtml(site.min_required_version)}+\">ниже минимума</span>` : ''}
                        </div>
                    </div>
                </div>
                
                <!-- Действия (как в grid) -->
                <div class="site-actions">
                    <button data-action="check-status" 
                            data-site-id="${site.id}" 
                            class="modern-btn modern-btn-primary btn-check">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Проверить</span>
                    </button>
                    
                    <button data-action="show-commands" 
                            data-site-id="${site.id}"
                            class="modern-btn modern-btn-black btn-commands">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3"></path>
                        </svg>
                        <span class="ml-1">Команды</span>
                    </button>
                </div>
            </div>
        `;
    },
    
    /**
     * Пустой список сайтов
     */
    emptySitesList() {
        return `
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                    </svg>
                </div>
                <h3 class="empty-state-title">Нет сайтов</h3>
                <p class="empty-state-description">Начните с добавления первого сайта для мониторинга</p>
                <button data-action="add-site" class="modern-btn modern-btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Добавить сайт
                </button>
            </div>
        `;
    },
    
    /**
     * Модальное окно добавления сайта
     */
    addSiteModal() {
        return `
            <div class="modern-modal" id="add-site-modal">
                <div class="modern-modal-content">
                    <div class="modern-modal-header">
                        <h2 class="modern-modal-title">Добавить новый сайт</h2>
                        <button data-action="hide-modal" class="modern-modal-close">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <form id="add-site-form" class="modern-modal-body">
                        <div class="form-group">
                            <label for="site-name" class="form-label">Название сайта</label>
                            <input type="text" id="site-name" name="site_name" class="form-input" placeholder="Мой сайт" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="site-url" class="form-label">URL сайта</label>
                            <input type="url" id="site-url" name="site_url" class="form-input" placeholder="https://example.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="api-secret" class="form-label">API секрет</label>
                            <input type="text" id="api-secret" name="api_secret" class="form-input" placeholder="Секретный ключ для API">
                        </div>
                        
                        <div class="modern-modal-footer">
                            <button type="button" data-action="hide-modal" class="modern-btn modern-btn-ghost">Отмена</button>
                            <button type="submit" class="modern-btn modern-btn-primary">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Добавить сайт
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        `;
    },
    
    /**
     * Индикатор загрузки
     */
    loading(message = 'Загрузка...') {
        return `
            <div class="text-center py-12">
                <div class="w-12 h-12 mx-auto mb-4">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                </div>
                <p class="text-gray-600">${message}</p>
            </div>
        `;
    },
    
    /**
     * Уведомление
     */
    notification(message, type = 'info', autoHide = true) {
        const typeClasses = {
            'success': 'bg-green-50 border-green-200 text-green-800',
            'error': 'bg-red-50 border-red-200 text-red-800',
            'warning': 'bg-yellow-50 border-yellow-200 text-yellow-800',
            'info': 'bg-blue-50 border-blue-200 text-blue-800'
        };
        
        const iconMap = {
            'success': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'error': 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'warning': 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z',
            'info': 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
        };
        
        return `
            <div class="notification ${typeClasses[type]} border rounded-lg p-4 mb-3 ${autoHide ? 'auto-hide' : ''}" 
                 style="animation: slideInRight 0.3s ease-out;">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${iconMap[type]}"></path>
                    </svg>
                    <span class="flex-1">${message}</span>
                    <button class="close-notification ml-3 text-current opacity-50 hover:opacity-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `;
    },
    
    /**
     * Получение CSS класса для статуса
     */
    getStatusClass(status) {
        const statusMap = {
            'online': 'status-online',
            'offline': 'status-offline',
            'pending': 'status-pending',
            'error': 'status-error'
        };
        return statusMap[status] || 'status-pending';
    },
    
    /**
     * Получение текста статуса
     */
    getStatusText(status) {
        const statusMap = {
            'online': 'Онлайн',
            'offline': 'Офлайн', 
            'pending': 'Проверяется',
            'error': 'Ошибка'
        };
        return statusMap[status] || 'Неизвестно';
    },
    
    /**
     * Получение иконки статуса
     */
    getStatusIcon(status) {
        const iconMap = {
            'online': '🟢',
            'offline': '🔴',
            'pending': '🟡',
            'error': '⚠️'
        };
        return iconMap[status] || '❓';
    },
    
    /**
     * Экранирование HTML
     */
    escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }
};

// Отладочный лог
console.log('✅ NeetrinoTemplates загружен успешно', window.NeetrinoTemplates);

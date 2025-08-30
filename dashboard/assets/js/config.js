/**
 * Neetrino Dashboard - Конфигурация
 */

window.NeetrinoConfig = {
    // API настройки
    api: {
        baseUrl: 'api.php',
        timeout: 10000,
        retryAttempts: 3,
        retryDelay: 1000
    },
    
    // Push-архитектура настройки
    push: {
        commandTimeout: 10000,
        maxConcurrentCommands: 5,
        retryAttempts: 2
    },
    
    // UI настройки
    ui: {
        refreshInterval: 30000,
        animationDuration: 300,
        commandResultTimeout: 3000,
        errorResultTimeout: 5000
    },
    
    // Локализация
    messages: {
        loading: 'Загрузка...',
        noSites: 'Нет добавленных сайтов',
        addFirstSite: 'Добавить первый сайт',
        executeCommand: 'Выполняется команда...',
        commandSuccess: 'Команда выполнена успешно',
        commandError: 'Ошибка выполнения команды',
        networkError: 'Ошибка сети',
        timeout: 'Превышено время ожидания',
        confirmDelete: 'Подтвердите удаление',
        confirmPluginDelete: 'Удалить плагин с сайта?',
        siteAdded: 'Сайт добавлен успешно',
        siteDeleted: 'Сайт удален из Dashboard',
        pluginDeleted: 'Плагин удален, сайт перемещен в корзину',
        validationRequired: 'Заполните все обязательные поля',
        validationUrl: 'Введите корректный URL'
    },
    
    // Команды плагина
    commands: {
        get_info: {
            label: '📊 Информация',
            description: 'Получить информацию о плагине',
            color: 'blue'
        },
        get_status: {
            label: '🔍 Статус',
            description: 'Проверить статус плагина',
            color: 'blue'
        },
        deactivate_plugin: {
            label: '⏸️ Отключить',
            description: 'Отключить плагин',
            color: 'yellow'
        },
        delete_plugin: {
            label: '🗑️ Удалить плагин',
            description: 'Удалить плагин с сайта',
            color: 'red',
            confirm: true
        }
    },
    
    // Статусы сайтов
    siteStatuses: {
        online: {
            label: 'Онлайн',
            icon: '🟢',
            color: 'green'
        },
        offline: {
            label: 'Офлайн',
            icon: '🔴',
            color: 'red'
        },
        unknown: {
            label: 'Неизвестно',
            icon: '🟡',
            color: 'yellow'
        }
    },
    
    // Валидация
    validation: {
        url: {
            pattern: /^https?:\/\/.+/,
            message: 'URL должен начинаться с http:// или https://'
        },
        siteName: {
            minLength: 2,
            maxLength: 100,
            message: 'Название сайта должно быть от 2 до 100 символов'
        }
    },
    
    // Дебаг
    debug: {
        enabled: false,
        logLevel: 'info', // error, warn, info, debug
        logToConsole: true,
        logToServer: false
    }
};

/**
 * Утилиты конфигурации
 */
window.NeetrinoConfig.utils = {
    
    /**
     * Получить сообщение по ключу
     */
    getMessage(key, params = {}) {
        let message = this.messages[key] || key;
        
        // Подстановка параметров
        Object.keys(params).forEach(param => {
            message = message.replace(`{${param}}`, params[param]);
        });
        
        return message;
    },
    
    /**
     * Получить конфигурацию команды
     */
    getCommand(commandKey) {
        return this.commands[commandKey] || {
            label: commandKey,
            description: '',
            color: 'gray'
        };
    },
    
    /**
     * Получить конфигурацию статуса
     */
    getStatus(statusKey) {
        return this.siteStatuses[statusKey] || this.siteStatuses.unknown;
    },
    
    /**
     * Валидация URL
     */
    validateUrl(url) {
        if (!url) return { valid: false, message: 'URL обязателен' };
        
        if (!this.validation.url.pattern.test(url)) {
            return { valid: false, message: this.validation.url.message };
        }
        
        return { valid: true };
    },
    
    /**
     * Валидация названия сайта
     */
    validateSiteName(name) {
        if (!name) return { valid: false, message: 'Название сайта обязательно' };
        
        if (name.length < this.validation.siteName.minLength || 
            name.length > this.validation.siteName.maxLength) {
            return { valid: false, message: this.validation.siteName.message };
        }
        
        return { valid: true };
    },
    
    /**
     * Логирование
     */
    log(level, message, data = null) {
        if (!this.debug.enabled) return;
        
        const levels = ['error', 'warn', 'info', 'debug'];
        const currentLevelIndex = levels.indexOf(this.debug.logLevel);
        const messageLevelIndex = levels.indexOf(level);
        
        if (messageLevelIndex > currentLevelIndex) return;
        
        if (this.debug.logToConsole) {
            const timestamp = new Date().toISOString();
            const prefix = `[${timestamp}] [${level.toUpperCase()}] Neetrino Dashboard:`;
            
            switch (level) {
                case 'error':
                    console.error(prefix, message, data);
                    break;
                case 'warn':
                    console.warn(prefix, message, data);
                    break;
                case 'info':
                    console.info(prefix, message, data);
                    break;
                case 'debug':
                    console.debug(prefix, message, data);
                    break;
            }
        }
        
        if (this.debug.logToServer) {
            // Отправка логов на сервер (можно реализовать позже)
            this.sendLogToServer(level, message, data);
        }
    },
    
    /**
     * Отправка логов на сервер
     */
    sendLogToServer(level, message, data) {
        // Отправка логов на сервер для диагностики
        fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'log',
                level: level,
                message: message,
                data: data,
                timestamp: Date.now()
            })
        }).catch(() => {
            // Игнорируем ошибки логирования
        });
    }
};

// Привязка утилит к основному объекту
Object.keys(window.NeetrinoConfig.utils).forEach(key => {
    if (typeof window.NeetrinoConfig.utils[key] === 'function') {
        window.NeetrinoConfig[key] = window.NeetrinoConfig.utils[key].bind(window.NeetrinoConfig);
    }
});

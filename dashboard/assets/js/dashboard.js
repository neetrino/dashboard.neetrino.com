/**
 * Neetrino Control Dashboard - Современный JavaScript модуль с табами
 */

class NeetrinoDashboard {
    constructor() {
        this.sites = [];
        this.filteredSites = [];
        this.selectedSites = new Set();
        this.currentFilter = 'all';
        this.searchQuery = '';
        this.currentView = 'list'; // 'list' или 'grid'
        this.currentTab = 'main'; // 'main', 'settings', 'info'
        this.currentControlTab = 'main'; // 'main', 'commands', 'info' для контрол панели
        
        // Параметры пагинации
        this.pagination = {
            current_page: 1,
            per_page: 20,
            total_sites: 0,
            total_pages: 0,
            has_next: false,
            has_prev: false
        };
        
        // Флаг для отключения пагинации при фильтрации/поиске
        this.useClientSidePagination = false;
        
        this.config = {
            refreshInterval: 30000, // 30 секунд
            commandTimeout: 10000,   // 10 секунд
            retryAttempts: 3,
            minPluginVersion: ''
        };
        
        this.init();
    }
    
    /**
     * Инициализация Dashboard
     */
    async init() {
    const verObj = (window && window.NEETRINO_DASHBOARD_VERSION) || {};
    const displayVer = verObj.display || verObj.short || verObj.version || '';
    console.log(`🎛️ Инициализация Neetrino Dashboard ${displayVer}...`);
        
        // Проверим доступность шаблонов
        if (!window.NeetrinoTemplates) {
            console.error('❌ NeetrinoTemplates не загружен!');
            this.showError('Ошибка загрузки шаблонов');
            return;
        }
        console.log('✅ NeetrinoTemplates доступен');
        
    // Загружаем настройки
    this.loadSettings();
    await this.loadServerSettings();
        
    // Привязка событий
    this.bindEvents();
    // Привязываем тулбар выбора (select all checkbox)
    this.bindSelectAllToolbar();
        
        // Инициализация табов
        this.initTabs();
        
        // Загружаем предпочтение режима отображения
        this.currentView = localStorage.getItem('neetrino_view') || 'list';
        
        // Устанавливаем активную кнопку
        $('.view-btn').removeClass('active');
        $(`[data-action="view-${this.currentView}"]`).addClass('active');
        
        // Загрузка данных
        await this.loadSites();
        
        // Проставляем версию дашборда в UI (если есть плейсхолдеры)
        try {
            if (document.getElementById('dashboard-display-version')) {
                document.getElementById('dashboard-display-version').textContent = displayVer || '';
            }
            if (document.getElementById('dashboard-display-version-info')) {
                document.getElementById('dashboard-display-version-info').textContent = displayVer || '';
            }
        } catch (e) { /* no-op */ }

        // Запуск автообновления
        this.startAutoRefresh();
        
        // Инициализация статистики для таба информации
        this.updateInfoTab();
        
        console.log('✅ Dashboard инициализирован');
    }

    /**
     * Инициализация табовой навигации
     */
    initTabs() {
        // Восстанавливаем активный таб из localStorage
        this.currentTab = localStorage.getItem('neetrino_active_tab') || 'main';
        this.switchTab(this.currentTab);
        
        console.log('✅ Табы инициализированы');
    }

    /**
     * Переключение табов
     */
    switchTab(tabName) {
        // Убираем активные классы со всех табов
        $('.tab-button').removeClass('active');
        $('.tab-content').removeClass('active');
        
        // Добавляем активные классы к выбранному табу
        $(`[data-tab="${tabName}"]`).addClass('active');
        $(`#tab-${tabName}`).addClass('active');
        
        // Сохраняем в localStorage
        this.currentTab = tabName;
        localStorage.setItem('neetrino_active_tab', tabName);
        
        // Обновляем содержимое таба если нужно
        if (tabName === 'info') {
            this.updateInfoTab();
        }
        
        console.log(`📑 Переключен на таб: ${tabName}`);
    }

    /**
     * Обновление информации в табе "Информация"
     */
    updateInfoTab() {
        const onlineCount = this.sites.filter(site => site.status === 'online').length;
        const offlineCount = this.sites.filter(site => site.status === 'offline').length;
        const totalCount = this.sites.length;
        
        $('#stat-online').text(onlineCount);
        $('#stat-offline').text(offlineCount);
        $('#stat-total').text(totalCount);
        
        // Обновляем время последней проверки
        const now = new Date();
        $('#last-check').text(now.toLocaleString('ru-RU'));
        
        // Обновляем время работы
        const uptime = Math.floor(performance.now() / 1000);
        const minutes = Math.floor(uptime / 60);
        const seconds = uptime % 60;
        $('#uptime').text(`${minutes}м ${seconds}с`);
        
        console.log('📊 Статистика обновлена');
    }
    
    /**
     * Переключение табов в контрол панели
     */
    switchControlTab(tabName) {
        // Убираем активные классы со всех табов контрол панели
        $('.control-tab-button').removeClass('active');
        $('.control-tab-content').removeClass('active');
        
        // Добавляем активные классы к выбранному табу
        $(`[data-control-tab="${tabName}"]`).addClass('active');
        $(`#control-tab-${tabName}`).addClass('active');
        
        // Сохраняем текущий таб контрол панели
        this.currentControlTab = tabName;
        localStorage.setItem('neetrino_control_tab', tabName);
        
        console.log(`🎛️ Переключен контрол таб: ${tabName}`);
    }
    
    /**
     * Показать ошибку
     */
    showError(message) {
        const container = $('#sites-container');
        container.html(`
            <div class="text-center py-12">
                <div class="w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-red-900 mb-2">Ошибка</h3>
                <p class="text-red-600">${message}</p>
            </div>
        `);
    }
    
    /**
     * Привязка событий
     */
    bindEvents() {
        // Табы
        $('[data-tab]').on('click', (e) => {
            const tabName = $(e.target).closest('[data-tab]').data('tab');
            this.switchTab(tabName);
        });
        
        // Табы контрол панели
        $(document).on('click', '[data-control-tab]', (e) => {
            const tabName = $(e.target).closest('[data-control-tab]').data('control-tab');
            this.switchControlTab(tabName);
        });

        // Поиск с задержкой для пагинации
        let searchTimeout;
        $('#search-sites').on('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.searchQuery = e.target.value.toLowerCase();
                this.handleSearchOrFilter();
            }, 300); // Задержка 300мс для снижения количества запросов
        });
        
        // Фильтры
        $('[data-filter]').on('click', (e) => {
            $('[data-filter]').removeClass('active');
            $(e.target).addClass('active');
            this.currentFilter = $(e.target).data('filter');
            this.handleSearchOrFilter();
        });
        
        // Пагинация - кнопки выбора количества на странице
        $(document).on('click', '[data-per-page]', (e) => {
            e.preventDefault();
            const perPage = parseInt($(e.target).data('per-page'));
            this.pagination.per_page = perPage;
            this.pagination.current_page = 1; // Сбрасываем на первую страницу
            
            // Обновляем активное состояние кнопок
            $('.per-page-btn').removeClass('active');
            $(e.target).addClass('active');
            
            this.loadSites();
        });
        
        // Пагинация - кнопки навигации (делегированное событие)
        $(document).on('click', '[data-page]', (e) => {
            e.preventDefault();
            const page = parseInt($(e.target).closest('[data-page]').data('page'));
            if (page && page !== this.pagination.current_page) {
                this.pagination.current_page = page;
                this.loadSites();
            }
        });
        
        // Глобальные кнопки
        $(document).on('click', '[data-action]', (e) => {
            const action = $(e.target).closest('[data-action]').data('action');
            const siteId = $(e.target).closest('[data-action]').data('site-id');
            const command = $(e.target).closest('[data-action]').data('command');
            
            this.handleAction(action, siteId, command, e);
        });
        
        // Три состояния Maintenance Mode (open/closed/maintenance)
        $(document).on('click', '[data-action="set-maintenance"]', async (e) => {
            const mode = $(e.currentTarget).data('mode');
            await this.setMaintenanceMode(mode);
        });
        
        // Обработчик выбора сайтов в списке
        $(document).on('click', '.list-item .site-select', (e) => {
            e.stopPropagation();
        });
        
        // Выбор сайтов
        $(document).on('change', '[data-action="toggle-select"]', (e) => {
            const siteId = parseInt($(e.target).data('site-id'));
            if (e.target.checked) {
                this.selectedSites.add(siteId);
            } else {
                this.selectedSites.delete(siteId);
            }
            this.updateBulkActions();
        });
        
        // Закрытие по ESC: сперва закрываем только окно подтверждения, если оно открыто
        $(document).on('keydown', (e) => {
            if (e.key === 'Escape') {
                const pluginConfirmOpen = $('#plugin-confirm-modal').length && !$('#plugin-confirm-modal').hasClass('hidden');
                if (pluginConfirmOpen) {
                    this.hidePluginConfirmModal();
                } else {
                    this.hideAllModals();
                }
            }
        });
        
        // Клик по бэкдропу: если открыт confirm — закрываем только его, иначе закрываем панель/модал, по которому кликнули
        $(document).on('click', '.modal-backdrop', (e) => {
            if (e.target === e.currentTarget) {
                const pluginConfirmOpen = $('#plugin-confirm-modal').length && !$('#plugin-confirm-modal').hasClass('hidden');
                if (pluginConfirmOpen) {
                    this.hidePluginConfirmModal();
                    return;
                }
                // Закрываем только этот модал
                $(e.currentTarget).addClass('hidden');
                // Сбрасываем currentControlPanelSiteId если закрыли именно панель управления
                if (e.currentTarget.id === 'control-panel-modal') {
                    this.currentControlPanelSiteId = null;
                }
            }
        });
        
        // Старые выпадающие меню удалены - используется только Control Panel
    }
    
    /**
     * Обработка действий
     */
    async handleAction(action, siteId = null, command = null, event = null) {
        switch (action) {
            case 'view-list':
                this.setView('list');
                break;
            case 'view-grid':
                this.setView('grid');
                break;
            case 'refresh-all':
                await this.refreshAllSites();
                break;
            case 'add-site':
                this.showAddSiteModal();
                break;
            case 'check-status':
                await this.checkSiteStatus(siteId);
                break;
            case 'execute-command':
                await this.executeCommand(siteId, command);
                break;
            case 'show-commands':
                this.showControlPanel(siteId);
                break;
            case 'remove-from-dashboard':
                await this.removeFromDashboard(siteId);
                break;
            case 'select-all':
                this.selectAllSites();
                break;
            case 'bulk-update':
                await this.bulkUpdateSites();
                break;
            case 'bulk-update-plugin':
                await this.bulkUpdatePlugins();
                break;
            case 'close-modal':
                this.hideAllModals();
                break;
            case 'cancel-delete':
                this.hideDeleteConfirmModal();
                break;
            // Новые действия для табов
            case 'show-trash':
                window.location.href = 'recycle_bin.php';
                break;
            case 'save-settings':
                this.saveSettings();
                break;
            case 'reset-settings':
                this.resetSettings();
                break;
            case 'clear-cache':
                this.clearLocalCache();
                break;
            case 'optimize-db':
                this.optimizeDatabase();
                break;
            case 'export-data':
                this.exportData();
                break;
        }
    }
    
    /**
     * Обработка поиска и фильтрации
     */
    handleSearchOrFilter() {
        // Если есть поиск или фильтр не "все", используем клиентскую пагинацию
        if (this.searchQuery || this.currentFilter !== 'all') {
            this.useClientSidePagination = true;
            this.loadAllSitesForFiltering();
        } else {
            // Иначе используем серверную пагинацию
            this.useClientSidePagination = false;
            this.pagination.current_page = 1; // Сбрасываем на первую страницу
            this.loadSites();
        }
    }
    
    /**
     * Загрузка всех сайтов для клиентской фильтрации
     */
    async loadAllSitesForFiltering() {
        try {
            console.log('📡 Загрузка всех сайтов для фильтрации...');
            this.showLoading('Поиск сайтов...');
            
            const response = await this.apiRequest('GET', 'get_sites', { 
                per_page: 1000,  // Загружаем много сайтов
                page: 1
            });
            
            if (response.success) {
                this.sites = response.sites || [];
                this.filterAndRenderSites();
                await this.updateStats();
                $('#pagination-container').addClass('hidden'); // Скрываем серверную пагинацию
            } else {
                throw new Error(response.error || 'Неизвестная ошибка');
            }
            
        } catch (error) {
            console.error('❌ Ошибка загрузки сайтов для фильтрации:', error);
            this.showNotification('Ошибка поиска: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }
    
    /**
     * Загрузка списка сайтов с серверной пагинацией
     */
    async loadSites() {
        try {
            console.log('📡 Загрузка сайтов...');
            this.showLoading('Загрузка сайтов...');
            
            const params = {
                page: this.pagination.current_page,
                per_page: this.pagination.per_page
            };
            // Примечание: заголовки для минимальной версии применяются только в pushCommand()
            // Добавляем параметры только если не используем клиентскую пагинацию
            if (!this.useClientSidePagination) {
                if (this.searchQuery) {
                    params.search = this.searchQuery;
                }
                if (this.currentFilter !== 'all') {
                    params.status = this.currentFilter;
                }
            }
            
            const response = await this.apiRequest('GET', 'get_sites', params);
            console.log('📡 Ответ API:', response);
            
            if (response.success) {
                this.sites = response.sites || [];
                this.pagination = response.pagination || this.pagination;
                
                console.log(`📡 Загружено сайтов: ${this.sites.length}`);
                
                if (this.useClientSidePagination) {
                    this.filterAndRenderSites();
                    $('#pagination-container').addClass('hidden');
                } else {
                    this.renderSites();
                    this.renderPagination();
                    $('#pagination-container').removeClass('hidden');
                }
                
                await this.updateStats();
                // NEW: гарантированно обновим инфо после статистики
                this.updatePaginationInfo();
                
                // Обновляем статистику в табе информации
                if (this.currentTab === 'info') {
                    this.updateInfoTab();
                }
            } else {
                throw new Error(response.error || 'Неизвестная ошибка');
            }
            
        } catch (error) {
            console.error('❌ Ошибка загрузки сайтов:', error);
            this.showNotification('Ошибка загрузки сайтов: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }
    
    /**
     * Фильтрация и отображение сайтов
     */
    filterAndRenderSites() {
        this.filteredSites = this.sites.filter(site => {
            // Фильтр по статусу
            if (this.currentFilter !== 'all') {
                if (this.currentFilter === 'selected' && !this.selectedSites.has(site.id)) {
                    return false;
                } else if (this.currentFilter !== 'selected' && site.status !== this.currentFilter) {
                    return false;
                }
            }
            
            // Поиск
            if (this.searchQuery) {
                const searchable = `${site.site_name} ${site.site_url}`.toLowerCase();
                if (!searchable.includes(this.searchQuery)) {
                    return false;
                }
            }
            
            return true;
        });
        
        this.renderSites();
        this.updateFilterCounts();
    }
    
    /**
     * Отображение сайтов
     */
    renderSites() {
        const container = $('#sites-container');
        
        if (this.filteredSites.length === 0 && this.useClientSidePagination) {
            // Для клиентской пагинации показываем сообщение о пустых результатах поиска
            container.html(`
                <div class="text-center py-12">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Нет результатов</h3>
                    <p class="text-gray-500 mb-6">Попробуйте изменить фильтры или поисковый запрос</p>
                    <button data-filter="all" class="modern-btn modern-btn-ghost">Показать все сайты</button>
                </div>
            `);
            return;
        }
        
        if (this.sites.length === 0) {
            if (this.pagination.current_page === 1) {
                container.html(window.NeetrinoTemplates.emptySitesList());
            } else {
                container.html(`
                    <div class="text-center py-12">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Страница пуста</h3>
                        <p class="text-gray-500 mb-6">На этой странице нет сайтов</p>
                        <button data-page="1" class="modern-btn modern-btn-primary">Перейти на первую страницу</button>
                    </div>
                `);
            }
            return;
        }
        
        // Определяем какие сайты показывать
        const sitesToShow = this.useClientSidePagination ? this.filteredSites : this.sites;
        
        // Подготавливаем данные сайтов с вычислением статуса версии плагина
        const sitesWithSelection = sitesToShow.map(site => {
            site.selected = this.selectedSites.has(site.id);
            const minVer = this.config.minPluginVersion || '';
            const curVer = site.plugin_version || '';
            
            // Логируем версию для отладки
            console.log(`🔍 Сайт ${site.site_name}: plugin_version="${curVer}", displayVersion="${this.formatShortVersion(curVer)}"`);
            
            let isBelowMin = false;
            if (minVer && curVer) {
                isBelowMin = this.compareVersions(curVer, minVer) < 0;
            }
            site.isBelowMin = isBelowMin;
            site.min_required_version = minVer;
            site.pluginVersion = curVer;
            site.displayVersion = this.formatShortVersion(curVer);
            return site;
        });
        
        let sitesHtml;
        
        if (this.currentView === 'grid') {
            // Режим сетки
            sitesHtml = `
                <div class="sites-grid">
                    ${sitesWithSelection.map(site => window.NeetrinoTemplates.siteCard(site)).join('')}
                </div>
            `;
        } else {
            // Режим списка
            sitesHtml = `
                <div class="sites-list">
                    ${sitesWithSelection.map(site => window.NeetrinoTemplates.siteListItem(site)).join('')}
                </div>
            `;
        }
        
    container.html(sitesHtml);
    // NEW: при любом рендере обновляем инфо (для client-side режима)
    this.updatePaginationInfo();
    // Синхронизируем тулбар выбора и его чекбокс
    this.bindSelectAllToolbar();
    this.updateBulkActions();
    }
    
    /**
     * Отображение пагинации
     */
    renderPagination() {
        if (this.useClientSidePagination) {
            $('#pagination-container').addClass('hidden');
            return;
        }
        $('#pagination-container').removeClass('hidden');
        // Обновляем информацию о странице
        $('#current-page-info').text(this.pagination.current_page);
        $('#total-pages-info').text(this.pagination.total_pages);
        // Очищаем навигацию
        const nav = $('#pagination-nav');
        nav.empty();
        const currentPage = this.pagination.current_page;
        const totalPages = this.pagination.total_pages;
        if (totalPages > 1) {
            // prev
            nav.append(`\n            <button class="pagination-btn ${!this.pagination.has_prev ? 'disabled' : ''}" data-page="${currentPage - 1}" ${!this.pagination.has_prev ? 'disabled' : ''}>\n                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">\n                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>\n                </svg>\n            </button>`);
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);
            if (startPage > 1) {
                nav.append(`<button class="pagination-btn-number" data-page="1">1</button>`);
                if (startPage > 2) nav.append(`<span class="pagination-ellipsis">...</span>`);
            }
            for (let i = startPage; i <= endPage; i++) {
                nav.append(`<button class="pagination-btn-number ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`);
            }
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) nav.append(`<span class="pagination-ellipsis">...</span>`);
                nav.append(`<button class="pagination-btn-number" data-page="${totalPages}">${totalPages}</button>`);
            }
            nav.append(`\n            <button class="pagination-btn ${!this.pagination.has_next ? 'disabled' : ''}" data-page="${currentPage + 1}" ${!this.pagination.has_next ? 'disabled' : ''}>\n                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">\n                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>\n                </svg>\n            </button>`);
        }
        this.updatePaginationInfo();
        this.updatePerPageButtons();
    }
    
    /**
     * Обновление состояния кнопок выбора количества на странице
     */
    updatePerPageButtons() {
        $('.per-page-btn').removeClass('active');
        $(`.per-page-btn[data-per-page="${this.pagination.per_page}"]`).addClass('active');
    }
    
    /**
     * Установка режима отображения
     */
    setView(view) {
        this.currentView = view;
        
        // Обновляем кнопки переключения
        $('.view-btn').removeClass('active');
        $(`[data-action="view-${view}"]`).addClass('active');
        
    // Перерисовываем сайты
    this.renderSites();
    // Обновляем тулбар выбора
    this.updateBulkActions();
        
        // Сохраняем предпочтение в localStorage
        localStorage.setItem('neetrino_view', view);
    }
    
    /**
     * Выполнение команды на сайте (PUSH-архитектура)
     */
    async executeCommand(siteId, command, data = {}) {
        // Если siteId не передан, пытаемся получить его из текущей панели управления
        if (!siteId && this.currentControlPanelSiteId) {
            siteId = this.currentControlPanelSiteId;
        }
        
        const site = this.sites.find(s => s.id === siteId);
        if (!site) {
            this.showNotification('Сайт не найден', 'error');
            return;
        }
        
        // Особая обработка для команд управления плагином с подтверждением
        if (command === 'delete_plugin') {
            const confirmed = await this.showPluginConfirm(
                `Удалить плагин с сайта "${site.site_name}"?`,
                'Это действие удалит плагин с сайта и переместит сайт в корзину. Это действие нельзя отменить.',
                'danger',
                () => this.executeDeletePlugin(siteId)
            );
            return;
        }
        
        if (command === 'deactivate_plugin') {
            const confirmed = await this.showPluginConfirm(
                `Отключить плагин на сайте "${site.site_name}"?`,
                'Плагин будет отключен, но останется установленным. Вы сможете снова включить его позже.',
                'warning',
                () => this.executePushCommand(siteId, command, data)
            );
            return;
        }
        
        if (command === 'update_plugins') {
            const confirmed = await this.showPluginConfirm(
                `Обновить плагин на сайте "${site.site_name}"?`,
                'Будет выполнено обновление плагина до последней версии. Рекомендуется создать резервную копию перед обновлением.',
                'info',
                () => this.executePushCommand(siteId, command, data)
            );
            return;
        }
        
        try {
            // Показываем индикатор выполнения только в уведомлении
            this.showNotification('Выполняется команда...', 'info');
            
            // Прямой вызов REST API сайта (PUSH)
            const response = await this.pushCommand(site.site_url, command, data);
            
            if (response.success) {
                // Специальная обработка для разных команд
                let displayMessage = '';
                
                switch(command) {
                    case 'get_info':
                        displayMessage = 'Информация о сайте получена';
                        break;
                    
                    case 'get_status':
                        displayMessage = 'Статус сайта обновлен';
                        // NEW: если плагин вернул версию – сразу обновим UI и попытаемся сохранить её в БД
                        try {
                            if (response.data && response.data.plugin_version) {
                                // 1) Мгновенно обновляем локальное состояние для корректного отображения
                                site.plugin_version = response.data.plugin_version;
                                this.renderSites();

                                // 2) Пытаемся сохранить на сервере (PUSH)
                                try {
                                    await this.apiRequest('POST', 'plugin_version_push', {
                                        site_url: site.site_url,
                                        plugin_version: response.data.plugin_version,
                                        api_key: site.api_key || ''
                                    });
                                } catch (pushErr) {
                                    console.warn('Не удалось сохранить версию (push), пробуем pull:', pushErr);
                                    // 3) Fallback: просим сервер сам опросить сайт (PULL)
                                    try {
                                        await this.apiRequest('POST', 'plugin_version_pull', { site_id: site.id });
                                    } catch (pullErr) {
                                        console.warn('Не удалось выполнить pull версии:', pullErr);
                                    }
                                }
                            }
                        } catch (e) { console.warn('Обновление версии после статуса не удалось:', e); }
                        break;
                    
                    case 'update_plugins':
                        displayMessage = 'Обновление плагинов завершено';
                        break;
                    
                    case 'maintenance_enable':
                        displayMessage = 'Режим обслуживания включен';
                        this.updateMaintenanceStatus(true);
                        break;
                    
                    case 'maintenance_disable':
                        displayMessage = 'Режим обслуживания выключен';
                        this.updateMaintenanceStatus(false);
                        break;
                    
                    case 'maintenance_status':
                        displayMessage = 'Статус режима обслуживания обновлен';
                        if (response.data && typeof response.data.maintenance_mode !== 'undefined') {
                            this.updateMaintenanceStatus(response.data.maintenance_mode);
                        }
                        break;
                    
                    case 'clear_cache':
                        displayMessage = 'Кэш очищен успешно';
                        break;
                    case 'backup_create':
                        displayMessage = 'Бэкап создан успешно';
                        break;
                    
                    case 'optimize_db':
                        displayMessage = 'База данных оптимизирована';
                        break;
                    
                    case 'update_core':
                        displayMessage = 'WordPress обновлен';
                        break;
                    
                    case 'security_scan':
                        displayMessage = 'Сканирование безопасности завершено';
                        break;
                    
                    case 'performance_test':
                        displayMessage = 'Тест производительности завершен';
                        break;
                    
                    case 'deactivate_plugin':
                        displayMessage = 'Плагин отключен';
                        break;
                    
                    default:
                        displayMessage = response.message || 'Команда выполнена успешно';
                }
                
                this.showNotification(displayMessage, 'success');
                
                // Показываем результат в модальном окне
                this.showCommandResult({
                    success: true,
                    message: displayMessage,
                    command: command,
                    timestamp: Date.now() / 1000,
                    data: response.data
                });
            } else {
                throw new Error(response.message || 'Неизвестная ошибка');
            }
            
        } catch (error) {
            const msg = (error && (error.message || String(error))) || '';
            // Не дублируем красным, если речь про минимальную версию/необходимость обновления плагина
            if (/Минимальная версия|Требуется обновить плагин/i.test(msg)) {
                return;
            }
            this.showNotification('Ошибка выполнения команды: ' + msg, 'error');
            console.error('Command execution error:', error);
        }
    }
    
    /**
     * Обновление статуса режима обслуживания в интерфейсе
     */
    updateMaintenanceStatus(isActive) {
        $('#maintenance-current-status').text(isActive ? 'Включен' : 'Выключен');
        
        const indicator = $('#maintenance-status .status-indicator');
        indicator.text(isActive ? '🔧' : '✅');
    }

    /**
     * Установка Maintenance Mode (open | closed | maintenance)
     */
    async setMaintenanceMode(mode) {
        if (!this.currentControlPanelSiteId) {
            this.showNotification('Сайт не выбран', 'warning');
            return;
        }

    // Новая универсальная команда с 3 режимами
    const command = 'maintenance_mode';
    if (!['open', 'closed', 'maintenance'].includes(mode)) {
            this.showNotification('Неизвестный режим', 'error');
            return;
        }

        try {
            await this.executePushCommand(this.currentControlPanelSiteId, command, { mode });
            this.applyMaintenanceUi(mode);
        } catch (e) {
            console.error(e);
        }
    }

    /**
     * Применить UI состояния Maintenance
     */
    applyMaintenanceUi(mode) {
        const light = $('#maintenance-light');
        const statusText = $('#maintenance-current-status');
        const map = {
            open: { text: 'Открыт', light: 'mode-open' },
            closed: { text: 'Закрыт', light: 'mode-closed' },
            maintenance: { text: 'Обслуживание', light: 'mode-maint' }
        };
        // Сброс классов лампы
        light.removeClass('mode-open mode-closed mode-maint online offline maintenance');
        if (map[mode]) light.addClass(map[mode].light);
        statusText.text(map[mode] ? map[mode].text : 'Неизвестно');

        // Активная кнопка
        $('.mode-toggle-btn').removeClass('active');
        $(`[data-action="set-maintenance"][data-mode="${mode}"]`).addClass('active');
    }
    
    /**
     * Выполнение удаления плагина
     */
    async executeDeletePlugin(siteId) {
        const site = this.sites.find(s => s.id === siteId);
        if (!site) return;
        
        try {
            this.showNotification('Удаление плагина...', 'info');
            
            // Сначала удаляем плагин с сайта
            const response = await this.pushCommand(site.site_url, 'delete_plugin');
            
            if (response.success) {
                // Затем удаляем сайт из Dashboard
                const deleteResponse = await this.apiRequest('POST', 'delete_plugin', { site_id: siteId });
                
                if (deleteResponse.success) {
                    this.showNotification('Плагин удален, сайт перемещен в корзину', 'success');
                    
                    // Закрываем все модальные окна
                    this.hideAllModals();
                    
                    // Перезагружаем список через 2 секунды
                    setTimeout(() => this.loadSites(), 2000);
                } else {
                    throw new Error('Ошибка перемещения в корзину: ' + deleteResponse.error);
                }
            } else {
                throw new Error(response.message || 'Ошибка удаления плагина');
            }
            
        } catch (error) {
            this.showNotification(`Ошибка: ${error.message}`, 'error');
            this.showNotification('Ошибка удаления плагина: ' + error.message, 'error');
        }
        
        this.hideDeleteConfirmModal();
    }
    
    /**
     * Выполнение обычной PUSH команды (для обновления и отключения плагина)
     */
    async executePushCommand(siteId, command, data = {}) {
        const site = this.sites.find(s => s.id === siteId);
        if (!site) return;
        
        try {
            this.showNotification('Выполняется команда...', 'info');
            
            // Прямой вызов REST API сайта (PUSH)
            const response = await this.pushCommand(site.site_url, command, data);
            
            if (response.success) {
                let displayMessage = '';
                
                switch(command) {
                    case 'update_plugins':
                        displayMessage = 'Плагин успешно обновлен';
                        break;
                    case 'deactivate_plugin':
                        displayMessage = 'Плагин отключен';
                        break;
                    default:
                        displayMessage = response.message || 'Команда выполнена успешно';
                }
                
                this.showNotification(displayMessage, 'success');
                
                // Показываем результат в модальном окне
                this.showCommandResult({
                    success: true,
                    message: displayMessage,
                    command: command,
                    timestamp: Date.now() / 1000,
                    data: response.data
                });
            } else {
                throw new Error(response.message || 'Неизвестная ошибка');
            }
            
        } catch (error) {
            const msg = (error && (error.message || String(error))) || '';
            // Не дублируем красным, если речь про минимальную версию/необходимость обновления плагина
            if (/Минимальная версия|Требуется обновить плагин/i.test(msg)) {
                return;
            }
            this.showNotification('Ошибка выполнения команды: ' + msg, 'error');
            console.error('Command execution error:', error);
        }
        
        this.hidePluginConfirmModal();
    }
    
    /**
     * Удаление сайта из дашборда (без затрагивания самого сайта)
     */
    async removeFromDashboard(siteId) {
        // Если siteId не передан, пытаемся получить его из текущей панели управления
        if (!siteId && this.currentControlPanelSiteId) {
            siteId = this.currentControlPanelSiteId;
        }
        
        const site = this.sites.find(s => s.id === siteId);
        if (!site) {
            this.showNotification('Сайт не найден', 'error');
            return;
        }
        
        const confirmed = await this.showDeleteConfirm(
            `Удалить сайт "${site.site_name}" из дашборда?`,
            'Это действие удалит сайт только из дашборда. Сам сайт и плагин на нем останутся нетронутыми.',
            () => this.executeRemoveFromDashboard(siteId)
        );
    }
    
    /**
     * Выполнение удаления сайта из дашборда
     */
    async executeRemoveFromDashboard(siteId) {
        const site = this.sites.find(s => s.id === siteId);
        if (!site) return;
        
        try {
            this.showNotification('Удаление из дашборда...', 'info');
            
            // Удаляем сайт из Dashboard
            const response = await this.apiRequest('POST', 'remove_from_dashboard', { site_id: siteId });
            
            if (response.success) {
                this.showNotification('Сайт удален из дашборда', 'success');
                
                // Закрываем панель управления если она открыта
                this.hideAllModals();
                
                // Перезагружаем список через 1 секунду
                setTimeout(() => this.loadSites(), 1000);
            } else {
                throw new Error(response.error || 'Ошибка удаления из дашборда');
            }
            
        } catch (error) {
            this.showNotification('Ошибка удаления из дашборда: ' + error.message, 'error');
        }
        
        this.hideDeleteConfirmModal();
    }
    
    /**
     * Отправка PUSH команды на сайт
     */
    async pushCommand(siteUrl, command, data = {}) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.config.commandTimeout);
        
        // Получаем сайт из списка для получения API ключа
        const site = this.sites.find(s => s.site_url === siteUrl || s.site_url + '/' === siteUrl || siteUrl.includes(s.site_name));
        if (!site || !site.api_key) {
            throw new Error('API ключ не найден для сайта');
        }
        
        try {
            // Предварительная проверка версии: блокируем команды (кроме статуса/инфо) если версия ниже минимума
            const minVer = this.config.minPluginVersion || '';
            if (minVer && !['get_status', 'get_info'].includes(command)) {
                const tgt = this.sites.find(s => s.site_url === siteUrl || s.site_url + '/' === siteUrl);
                if (tgt && tgt.plugin_version && this.compareVersions(tgt.plugin_version, minVer) < 0) {
                    this.showNotification(`Запрос не отправлен: требуется обновить плагин до версии ${minVer}+`, 'warning');
                    throw new Error(`Минимальная версия ${minVer}+`);
                }
            }

            const headers = {
                'Content-Type': 'application/json'
            };
            // Не требуем минимальную версию для служебных команд статуса/информации
            if (this.config.minPluginVersion && !['get_status', 'get_info'].includes(command)) {
                headers['X-Min-Plugin-Version'] = this.config.minPluginVersion;
            }

            const response = await fetch(`${siteUrl}/wp-json/neetrino/v1/command`, {
                method: 'POST',
                headers,
                body: JSON.stringify({
                    command: command,
                    data: data,
                    api_key: site.api_key
                }),
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                if (response.status === 426) {
                    // Upgrade Required по версии плагина
                    throw new Error(`Требуется обновить плагин до версии ${this.config.minPluginVersion || ''}+`);
                }
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
            
        } catch (error) {
            clearTimeout(timeoutId);
            if (error.name === 'AbortError') {
                throw new Error('Таймаут выполнения команды');
            }
            throw error;
        }
    }
    
    /**
     * Проверка статуса сайта
     */
    async checkSiteStatus(siteId) {
        await this.executeCommand(siteId, 'get_status');
    }
    
    /**
     * Обновление всех сайтов
     */
    async refreshAllSites() {
        const refreshBtn = $('[data-action="refresh-all"]');
        const originalHtml = refreshBtn.html();
        
        refreshBtn.html('<div class="modern-spinner modern-spinner-sm"></div> Обновление...');
        refreshBtn.prop('disabled', true);
        
        try {
            await this.loadSites();
            this.showNotification('Все сайты обновлены', 'success');
        } finally {
            refreshBtn.html(originalHtml);
            refreshBtn.prop('disabled', false);
        }
    }
    
    /**
     * Добавление нового сайта
     */
    async addNewSite() {
        const url = $('#site-url').val().trim();
        const name = $('#site-name').val().trim();
        
        if (!url || !name) {
            this.showNotification('Заполните все поля', 'warning');
            return;
        }
        
        if (!this.isValidUrl(url)) {
            this.showNotification('Введите корректный URL', 'warning');
            return;
        }
        
        const submitBtn = $('#add-site-form button[type="submit"]');
        const originalText = submitBtn.text();
        submitBtn.html('<div class="modern-spinner modern-spinner-sm"></div> Добавление...').prop('disabled', true);
        
        try {
            const response = await this.apiRequest('POST', 'add_site', {
                site_url: url,
                site_name: name
            });
            
            if (response.success) {
                this.hideAddSiteModal();
                await this.loadSites();
                this.showNotification('Сайт добавлен успешно', 'success');
            } else {
                throw new Error(response.error || 'Ошибка добавления');
            }
            
        } catch (error) {
            this.showNotification('Ошибка добавления: ' + error.message, 'error');
        } finally {
            submitBtn.text(originalText).prop('disabled', false);
        }
    }
    
    /**
     * Показ панели управления сайтом
     */
    showControlPanel(siteId) {
        const site = this.sites.find(s => s.id === siteId);
        if (!site) {
            this.showNotification('Сайт не найден', 'error');
            return;
        }
        
        // Обновляем информацию о сайте в модальном окне
        $('#control-panel-site-name').text(site.site_name);
        $('#control-panel-site-button').attr('onclick', `window.open('${site.site_url}', '_blank')`).attr('title', `Перейти на ${site.site_url}`);
        
        // Заполняем информационные поля
        const createdAt = site.created_at ? new Date(site.created_at).toLocaleDateString('ru-RU', {
            day: '2-digit',
            month: '2-digit', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }) : 'Неизвестно';
        
        const lastChecked = site.last_checked ? new Date(site.last_checked).toLocaleDateString('ru-RU', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric', 
            hour: '2-digit',
            minute: '2-digit'
        }) : 'Никогда';
        
        // Обновляем информацию в инфо табе
        $('#control-panel-created-at-info').text(createdAt);
        $('#control-panel-last-checked-info').text(lastChecked);
        
        // Сохраняем ID текущего сайта для команд
        this.currentControlPanelSiteId = siteId;
        
        // Добавляем site-id ко всем кнопкам команд
        $('#control-panel-modal [data-action="execute-command"]').attr('data-site-id', siteId);
        
        // Инициализируем таб контрол панели (восстанавливаем последний активный или ставим main)
        const savedControlTab = localStorage.getItem('neetrino_control_tab') || 'main';
        this.switchControlTab(savedControlTab);
        
        // Показываем модальное окно
        $('#control-panel-modal').removeClass('hidden');
        
        // Проверяем статус режима обслуживания
        this.checkMaintenanceStatus(siteId);
    }
    
    /**
     * Проверка статуса режима обслуживания
     */
    async checkMaintenanceStatus(siteId) {
        try {
            const site = this.sites.find(s => s.id === siteId);
            if (!site) return;
            
            const response = await this.pushCommand(site.site_url, 'get_status');
            if (response.success && response.data) {
                // Новый формат: response.data.maintenance_mode = { mode: 'open'|'maintenance'|'closed' }
                let mode = response.data.maintenance_mode && response.data.maintenance_mode.mode;
                // Обратная совместимость: если boolean
                if (!mode) {
                    const legacy = response.data.maintenance_mode;
                    if (typeof legacy === 'boolean') {
                        mode = legacy ? 'maintenance' : 'open';
                    }
                }
                if (mode) {
                    this.applyMaintenanceUi(mode);
                }
            }
        } catch (error) {
            console.error('Error checking maintenance status:', error);
            $('#maintenance-current-status').text('Неизвестно');
        }
    }
    
    /**
     * Выбор всех сайтов
     */
    selectAllSites() {
        const allSelected = this.filteredSites.every(site => this.selectedSites.has(site.id));
        
        if (allSelected) {
            // Снять выбор со всех
            this.filteredSites.forEach(site => this.selectedSites.delete(site.id));
        } else {
            // Выбрать все
            this.filteredSites.forEach(site => this.selectedSites.add(site.id));
        }
        
        this.renderSites();
        this.updateBulkActions();
    }
    
    /**
     * Массовое обновление сайтов
     */
    async bulkUpdateSites() {
        if (this.selectedSites.size === 0) return;
        
        const selectedArray = Array.from(this.selectedSites);
        const total = selectedArray.length;
        let completed = 0;
        
        this.showNotification(`Обновление ${total} сайтов...`, 'info');
        
        for (const siteId of selectedArray) {
            try {
                await this.checkSiteStatus(siteId);
                completed++;
            } catch (error) {
                console.error(`Error updating site ${siteId}:`, error);
            }
            
            // Пауза между запросами
            await this.delay(500);
        }
        
        this.showNotification(`Обновлено ${completed} из ${total} сайтов`, 'success');
    }
    
    /**
     * Массовое обновление плагинов Neetrino
     */
    async bulkUpdatePlugins() {
        if (this.selectedSites.size === 0) {
            this.showNotification('Выберите сайты для обновления плагина', 'warning');
            return;
        }
        
        const selectedArray = Array.from(this.selectedSites);
        const total = selectedArray.length;
        let completed = 0;
        let failed = 0;
        
        // Показываем прогресс-бар
        this.showPluginUpdateProgress(total);
        
        this.showNotification(`Начинаем обновление плагина Neetrino на ${total} сайтах...`, 'info');
        
        // Обновляем плагины последовательно с задержкой
        for (let i = 0; i < selectedArray.length; i++) {
            const siteId = selectedArray[i];
            
            try {
                // Обновляем прогресс
                this.updatePluginUpdateProgress(i + 1, total, siteId);
                
                // Выполняем команду обновления плагина
                const result = await this.executeCommand(siteId, 'update_plugin');
                
                if (result.success) {
                    completed++;
                    console.log(`✅ Плагин обновлен на сайте ${siteId}:`, result.message);
                } else {
                    failed++;
                    console.error(`❌ Ошибка обновления плагина на сайте ${siteId}:`, result.message);
                }
                
            } catch (error) {
                failed++;
                console.error(`❌ Ошибка обновления плагина на сайте ${siteId}:`, error);
            }
            
            // Задержка между обновлениями (2-3 секунды)
            if (i < selectedArray.length - 1) {
                await this.delay(2500);
            }
        }
        
        // Скрываем прогресс-бар
        this.hidePluginUpdateProgress();
        
        // Показываем результат
        const message = `Обновление завершено: ${completed} успешно, ${failed} с ошибками`;
        this.showNotification(message, failed === 0 ? 'success' : 'warning');
        
        // Обновляем статус сайтов
        this.refreshSelectedSites();
    }
    
    /**
     * Обновление статуса выбранных сайтов
     */
    async refreshSelectedSites() {
        if (this.selectedSites.size === 0) return;
        
        const selectedArray = Array.from(this.selectedSites);
        
        for (const siteId of selectedArray) {
            try {
                await this.checkSiteStatus(siteId);
            } catch (error) {
                console.error(`Ошибка обновления статуса сайта ${siteId}:`, error);
            }
            
            // Небольшая задержка между запросами
            await this.delay(200);
        }
        
        // Обновляем отображение
        this.renderSites();
    }
    
    /**
     * Обновление массовых действий
     */
    updateBulkActions() {
        const bulkActions = $('#bulk-actions');
        const selectionToolbar = $('#selection-toolbar');
        const selectedCountEl = $('#selected-count');
        const selectAllCheckbox = $('#select-all-checkbox');

        // Обновляем счетчик выбранных
        selectedCountEl.text(this.selectedSites.size);

        // Видимость тулбара и состояние чекбокса
        if (this.selectedSites.size > 0) {
            selectionToolbar.removeClass('hidden');
            bulkActions.removeClass('hidden');
            const list = this.useClientSidePagination ? this.filteredSites : this.sites;
            const allSelected = list.length > 0 && list.every(site => this.selectedSites.has(site.id));
            selectAllCheckbox.prop('checked', allSelected);
            $('#select-all-label').text(allSelected ? 'Снять выбор' : 'Выбрать все');
        } else {
            bulkActions.addClass('hidden');
            selectionToolbar.addClass('hidden');
            selectAllCheckbox.prop('checked', false);
            $('#select-all-label').text('Выбрать все');
        }

        this.updateFilterCounts();
    }

    /**
     * Привязка обработчика для чекбокса "Выбрать все" в тулбаре
     */
    bindSelectAllToolbar() {
        const checkbox = $('#select-all-checkbox');
        if (checkbox.length === 0) return;
        checkbox.off('change').on('change', (e) => {
            const checked = e.target.checked;
            const list = this.useClientSidePagination ? this.filteredSites : this.sites;
            if (checked) {
                list.forEach(site => this.selectedSites.add(site.id));
            } else {
                list.forEach(site => this.selectedSites.delete(site.id));
            }
            this.renderSites();
            this.updateBulkActions();
        });
    }
    
    /**
     * Обновление счетчиков фильтров и статистики
     */
    async updateFilterCounts() {
        // Если используем серверную пагинацию, загружаем статистику отдельно
        if (!this.useClientSidePagination) {
            try {
                const [allResponse, onlineResponse, offlineResponse] = await Promise.all([
                    this.apiRequest('GET', 'get_sites', { per_page: 1, page: 1 }),
                    this.apiRequest('GET', 'get_sites', { per_page: 1, page: 1, status: 'online' }),
                    this.apiRequest('GET', 'get_sites', { per_page: 1, page: 1, status: 'offline' })
                ]);
                
                const counts = {
                    all: allResponse.success ? allResponse.pagination.total_sites : 0,
                    online: onlineResponse.success ? onlineResponse.pagination.total_sites : 0,
                    offline: offlineResponse.success ? offlineResponse.pagination.total_sites : 0,
                    selected: this.selectedSites.size
                };
                
                Object.keys(counts).forEach(filter => {
                    $(`#count-${filter}`).text(counts[filter]);
                });
                // Умный показ фильтра "Выбранные"
                const selectedFilterBtn = $('[data-filter="selected"]');
                if (counts.selected > 0) {
                    selectedFilterBtn.removeClass('hidden');
                } else {
                    selectedFilterBtn.addClass('hidden');
                }
                
                // Обновляем счетчики в шапке
                $('#header-total').text(counts.all);
                $('#header-online').text(counts.online);
                $('#header-offline').text(counts.offline);
                
                // Обновляем статистику в табе информации
                if (this.currentTab === 'info') {
                    $('#stat-online').text(counts.online);
                    $('#stat-offline').text(counts.offline);
                    $('#stat-total').text(counts.all);
                }
                
            } catch (error) {
                console.error('Ошибка обновления статистики:', error);
            }
        } else {
            // Клиентская статистика (как было раньше)
            const counts = {
                all: this.sites.length,
                online: this.sites.filter(s => s.status === 'online').length,
                offline: this.sites.filter(s => s.status === 'offline').length,
                selected: this.selectedSites.size
            };
            
            Object.keys(counts).forEach(filter => {
                $(`#count-${filter}`).text(counts[filter]);
            });
            // Умный показ фильтра "Выбранные"
            const selectedFilterBtn = $('[data-filter="selected"]');
            if (counts.selected > 0) {
                selectedFilterBtn.removeClass('hidden');
            } else {
                selectedFilterBtn.addClass('hidden');
            }
            
            // Обновляем счетчики в шапке
            $('#header-total').text(counts.all);
            $('#header-online').text(counts.online);
            $('#header-offline').text(counts.offline);
        }
    }
    
    /**
     * API запросы
     */
    async apiRequest(method, action, data = {}) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        };
        
        let url = 'api.php';
        
        if (method === 'GET') {
            const params = new URLSearchParams({ action, ...data });
            url += '?' + params.toString();
        } else {
            const params = new URLSearchParams({ action, ...data });
            options.body = params.toString();
        }
        
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return await response.json();
    }
    
    /**
     * Показ результата команды в модальном окне
     */
    showCommandResult(result) {
        const content = $('#command-result-content');
        content.html(this.generateCommandResultHtml(result));
        $('#command-result-modal').removeClass('hidden');
    }
    
    /**
     * Генерация HTML для результата команды
     */
    generateCommandResultHtml(result) {
        let html = `
            <div class="mb-4">
                <div class="flex items-center gap-3 mb-3">
                    <span class="text-3xl">${result.success ? '✅' : '❌'}</span>
                    <strong class="text-xl text-gray-800">${result.message}</strong>
                </div>
                <div class="text-sm text-gray-600 bg-gray-50 px-4 py-3 rounded-lg border border-gray-200">
                    <div class="flex items-center gap-4 flex-wrap">
                        <span class="font-mono bg-white px-3 py-1 rounded border border-gray-300 text-gray-800 font-semibold">
                            Команда: ${result.command || 'неизвестно'}
                        </span>
                        <span class="text-gray-700">
                            🕒 ${result.timestamp ? new Date(result.timestamp * 1000).toLocaleString('ru-RU') : new Date().toLocaleString('ru-RU')}
                        </span>
                    </div>
                </div>
            </div>
        `;
        
        if (result.data) {
            html += `
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <div class="font-semibold mb-3 text-gray-800 flex items-center gap-2 text-lg">
                        <span class="text-xl">�</span>
                        <span>Данные ответа:</span>
                    </div>
                    <div class="bg-white p-4 rounded-lg border-2 border-gray-300 shadow-inner">
                        <pre class="text-gray-800 font-mono text-sm leading-relaxed max-h-80 overflow-y-auto whitespace-pre-wrap">${JSON.stringify(result.data, null, 2)}</pre>
                    </div>
                </div>
            `;
        }
        
        return html;
    }
    
    /**
     * Скрытие модального окна результата команды
     */
    hideCommandResultModal() {
        $('#command-result-modal').addClass('hidden');
    }
    
    /**
     * Обновление статистики
     */
    async updateStats() {
        await this.updateFilterCounts();
    }
    
    /**
     * Автообновление
     */
    startAutoRefresh() {
        // Очистка предыдущего интервала, если существует
        if (this._refreshIntervalId) {
            clearInterval(this._refreshIntervalId);
        }
        // Новый интервал
        this._refreshIntervalId = setInterval(() => {
            // Если используем серверную пагинацию и нет активного поиска/фильтра – обновляем текущую страницу
            if (!this.useClientSidePagination) {
                this.loadSites();
            } else {
                // При клиентской фильтрации обновляем только статистику (без лишних запросов)
                this.updateStats();
            }
        }, this.config.refreshInterval);
    }
    
    /**
     * Модальные окна
     */
    showAddSiteModal() {
        $('#add-site-modal').removeClass('hidden');
        $('#site-url').focus();
        
        // Привязываем обработчик отправки формы
        $('#add-site-form').off('submit').on('submit', (e) => {
            e.preventDefault();
            this.addNewSite();
        });
    }
    
    hideAddSiteModal() {
        $('#add-site-modal').addClass('hidden');
        $('#add-site-form')[0].reset();
    }
    
    showDeleteConfirm(title, message, onConfirm) {
        $('#delete-confirm-text').text(message);
        $('#confirm-delete-btn').off('click').on('click', onConfirm);
        $('#delete-confirm-modal').removeClass('hidden');
    }
    
    /**
     * Показ подтверждающего окна для команд плагина с цветовой схемой
     */
    showPluginConfirm(title, message, type, onConfirm) {
        // Создаем или обновляем модальное окно подтверждения плагина
        let modal = $('#plugin-confirm-modal');
        if (modal.length === 0) {
            // Создаем модальное окно если его нет
            const modalHtml = `
                <div id="plugin-confirm-modal" class="modal-backdrop hidden">
                    <div class="modern-modal max-w-md" id="plugin-confirm-content">
                        <div class="text-center">
                            <div class="w-12 h-12 mx-auto mb-4 rounded-full flex items-center justify-center" id="plugin-confirm-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="plugin-confirm-svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                            
                            <h3 class="text-lg font-semibold mb-2" id="plugin-confirm-title">Подтвердите действие</h3>
                            <p id="plugin-confirm-text" class="text-gray-600 mb-6"></p>
                            
                            <div class="flex justify-center space-x-3">
                                <button data-action="cancel-plugin-action" class="modern-btn modern-btn-ghost">
                                    Отменить
                                </button>
                                <button id="confirm-plugin-btn" class="modern-btn">
                                    Выполнить
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $('body').append(modalHtml);
            modal = $('#plugin-confirm-modal');
            
            // Добавляем обработчик для кнопки отмены
            modal.on('click', '[data-action="cancel-plugin-action"]', () => {
                this.hidePluginConfirmModal();
            });
        }
        
        // Настраиваем цветовую схему в зависимости от типа
        const iconEl = $('#plugin-confirm-icon');
        const svgEl = $('#plugin-confirm-svg');
        const btnEl = $('#confirm-plugin-btn');
        
        switch(type) {
            case 'danger':
                iconEl.removeClass().addClass('w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center');
                svgEl.removeClass().addClass('w-6 h-6 text-red-600');
                btnEl.removeClass().addClass('modern-btn modern-btn-danger');
                btnEl.text('Удалить');
                svgEl.find('path').attr('d', 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16');
                break;
            case 'warning':
                iconEl.removeClass().addClass('w-12 h-12 mx-auto mb-4 bg-yellow-100 rounded-full flex items-center justify-center');
                svgEl.removeClass().addClass('w-6 h-6 text-yellow-600');
                btnEl.removeClass().addClass('modern-btn modern-btn-warning');
                btnEl.text('Отключить');
                svgEl.find('path').attr('d', 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728');
                break;
            case 'info':
                iconEl.removeClass().addClass('w-12 h-12 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center');
                svgEl.removeClass().addClass('w-6 h-6 text-blue-600');
                btnEl.removeClass().addClass('modern-btn modern-btn-primary');
                btnEl.text('Обновить');
                svgEl.find('path').attr('d', 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15');
                break;
        }
        
        $('#plugin-confirm-title').text(title);
        $('#plugin-confirm-text').text(message);
        $('#confirm-plugin-btn').off('click').on('click', () => {
            // Сначала закрываем окно подтверждения, затем запускаем действие
            this.hidePluginConfirmModal();
            try {
                onConfirm && onConfirm();
            } catch (e) {
                console.error(e);
            }
        });
        modal.removeClass('hidden');
    }
    
    hideDeleteConfirmModal() {
        $('#delete-confirm-modal').addClass('hidden');
    }
    
    hidePluginConfirmModal() {
        $('#plugin-confirm-modal').addClass('hidden');
    }
    
    hideAllModals() {
        $('.modal-backdrop').addClass('hidden');
        
        // Сбрасываем ID текущего сайта в панели управления
        this.currentControlPanelSiteId = null;
    }
    
    /**
     * Уведомления
     */
    showNotification(message, type = 'info', autoHide = true) {
        const container = $('#notification-container');
        const notification = $(window.NeetrinoTemplates.notification(message, type, autoHide));
        
        container.append(notification);
        
        if (autoHide) {
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 5000);
        }
    }
    
    /**
     * Загрузка
     */
    showLoading(message = 'Загрузка...') {
        const container = $('#sites-container');
        container.html(window.NeetrinoTemplates.loading(message));
    }
    
    hideLoading() {
        // Загрузка скрывается при рендере контента
    }
    
    /**
     * Прогресс-бар для обновления плагинов
     */
    showPluginUpdateProgress(total) {
        const progressHtml = `
            <div id="plugin-update-progress" class="fixed top-20 left-1/2 transform -translate-x-1/2 z-50 bg-white rounded-xl shadow-xl border border-gray-200 p-6 min-w-96">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Обновление плагина Neetrino</h3>
                    <button onclick="neetrinoDashboard.hidePluginUpdateProgress()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>Прогресс: <span id="progress-current">0</span> из <span id="progress-total">${total}</span></span>
                        <span id="progress-percentage">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>
                <div class="text-sm text-gray-600">
                    <div id="progress-site">Готов к обновлению...</div>
                    <div id="progress-status" class="text-blue-600">Ожидание...</div>
                </div>
            </div>
        `;
        
        $('body').append(progressHtml);
    }
    
    updatePluginUpdateProgress(current, total, siteId) {
        const percentage = Math.round((current / total) * 100);
        
        $('#progress-current').text(current);
        $('#progress-total').text(total);
        $('#progress-percentage').text(percentage + '%');
        $('#progress-bar').css('width', percentage + '%');
        $('#progress-site').text(`Сайт: ${siteId}`);
        $('#progress-status').text(`Обновление... (${current}/${total})`);
    }
    
    hidePluginUpdateProgress() {
        $('#plugin-update-progress').remove();
    }
    
    /**
     * Новые методы для работы с настройками и дополнительными функциями
     */
    
    /**
     * Сохранение настроек
     */
    saveSettings() {
        const refreshInterval = parseInt($('#refresh-interval').val()) * 1000;
        const defaultView = $('#default-view').val();
        const commandTimeout = parseInt($('#command-timeout').val()) * 1000;
        const retryAttempts = parseInt($('#retry-attempts').val());
        const minPluginVersion = ($('#min-plugin-version').val() || '').trim();

        // Валидация версии (простая: X[.Y[.Z]])
        if (minPluginVersion && !/^\d+(?:\.\d+){0,2}$/.test(minPluginVersion)) {
            this.showNotification('Некорректный формат минимальной версии. Используйте формат X.Y[.Z]', 'error');
            return;
        }

        // Обновляем конфигурацию
        this.config.refreshInterval = refreshInterval;
        this.config.commandTimeout = commandTimeout;
        this.config.retryAttempts = retryAttempts;
        this.config.minPluginVersion = minPluginVersion;

        // Сохраняем в localStorage
        localStorage.setItem('neetrino_settings', JSON.stringify({
            refreshInterval,
            defaultView,
            commandTimeout,
            retryAttempts,
            minPluginVersion
        }));

        // Сохраняем на сервере системную настройку
        this.apiRequest('POST', 'set_setting', { key: 'min_plugin_version', value: minPluginVersion, type: 'string' })
            .then(() => this.showNotification('Минимальная версия сохранена', 'success'))
            .catch(() => this.showNotification('Не удалось сохранить минимальную версию', 'error'));

        // Обновляем интервал автообновления
        this.startAutoRefresh();

        this.showNotification('Настройки сохранены', 'success');
        console.log('⚙️ Настройки сохранены');
    }

    /**
     * Сброс настроек к значениям по умолчанию
     */
    resetSettings() {
        $('#refresh-interval').val('30');
        $('#default-view').val('list');
        $('#command-timeout').val('10');
        $('#retry-attempts').val('3');

        // Удаляем из localStorage
        localStorage.removeItem('neetrino_settings');

        // Возвращаем конфигурацию по умолчанию
        this.config = {
            refreshInterval: 30000,
            commandTimeout: 10000,
            retryAttempts: 3
        };

        this.showNotification('Настройки сброшены к значениям по умолчанию', 'info');
        console.log('🔄 Настройки сброшены');
    }

    /**
     * Загрузка настроек из localStorage
     */
    loadSettings() {
        const savedSettings = localStorage.getItem('neetrino_settings');
        if (savedSettings) {
            try {
                const settings = JSON.parse(savedSettings);
                
                // Применяем настройки
                if (settings.refreshInterval) {
                    this.config.refreshInterval = settings.refreshInterval;
                    $('#refresh-interval').val(settings.refreshInterval / 1000);
                }
                if (settings.defaultView) {
                    this.currentView = settings.defaultView;
                    $('#default-view').val(settings.defaultView);
                }
                if (settings.commandTimeout) {
                    this.config.commandTimeout = settings.commandTimeout;
                    $('#command-timeout').val(settings.commandTimeout / 1000);
                }
                if (settings.retryAttempts) {
                    this.config.retryAttempts = settings.retryAttempts;
                    $('#retry-attempts').val(settings.retryAttempts);
                }
                if (typeof settings.minPluginVersion === 'string') {
                    this.config.minPluginVersion = settings.minPluginVersion;
                    $('#min-plugin-version').val(settings.minPluginVersion);
                }

                console.log('⚙️ Настройки загружены из localStorage');
            } catch (e) {
                console.error('❌ Ошибка загрузки настроек:', e);
            }
        }
    }

    /**
     * Очистка локального кэша
     */
    clearLocalCache() {
        // Очищаем кэш сайтов
        localStorage.removeItem('neetrino_sites_cache');
        localStorage.removeItem('neetrino_last_refresh');

        // Перезагружаем сайты
        this.loadSites();

        this.showNotification('Локальный кэш очищен', 'success');
        console.log('🗑️ Локальный кэш очищен');
    }

    /**
     * Оптимизация базы данных
     */
    async optimizeDatabase() {
        try {
            this.showNotification('Оптимизация базы данных...', 'info');

            const response = await $.ajax({
                url: 'api.php',
                method: 'POST',
                data: {
                    action: 'optimize_database'
                }
            });

            if (response.success) {
                this.showNotification('База данных оптимизирована', 'success');
            } else {
                this.showNotification('Ошибка оптимизации: ' + response.message, 'error');
            }
        } catch (error) {
            console.error('❌ Ошибка оптимизации БД:', error);
            this.showNotification('Ошибка оптимизации базы данных', 'error');
        }
    }

    /**
     * Экспорт данных
     */
    async exportData() {
        try {
            const data = {
                sites: this.sites,
                settings: this.config,
                exportDate: new Date().toISOString()
            };

            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = `neetrino-dashboard-export-${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            this.showNotification('Данные экспортированы', 'success');
            console.log('📤 Данные экспортированы');
        } catch (error) {
            console.error('❌ Ошибка экспорта:', error);
            this.showNotification('Ошибка экспорта данных', 'error');
        }
    }

    /**
     * Утилиты
     */
    isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
    
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Загрузка серверных настроек (минимальная версия плагина)
     */
    async loadServerSettings() {
        try {
            const resp = await this.apiRequest('GET', 'get_setting', { key: 'min_plugin_version' });
            if (resp.success && typeof resp.value !== 'undefined' && resp.value !== null) {
                const val = (resp.value || '').toString();
                this.config.minPluginVersion = val;
                const $input = $('#min-plugin-version');
                if ($input.length) { $input.val(val); }
            }
        } catch (e) {
            console.warn('Не удалось загрузить минимальную версию с сервера');
        }
    }

    /**
     * Сравнение версий (возвращает -1,0,1)
     */
    compareVersions(a, b) {
        const pa = String(a).split('.').map(n => parseInt(n, 10));
        const pb = String(b).split('.').map(n => parseInt(n, 10));
        const len = Math.max(pa.length, pb.length);
        for (let i = 0; i < len; i++) {
            const na = pa[i] || 0;
            const nb = pb[i] || 0;
            if (na > nb) return 1;
            if (na < nb) return -1;
        }
        return 0;
    }

    /**
     * Возвращает полную версию (например, 3.8.1) из всех цифр
     */
    formatShortVersion(v) {
        if (!v) return '';
        
        console.log(`🔧 formatShortVersion вызвана с: "${v}"`);
        
        // Извлекаем числовые сегменты
        const parts = String(v).match(/\d+/g);
        console.log(`🔧 Извлеченные части:`, parts);
        
        if (!parts || parts.length === 0) return '';
        
        // Собираем все доступные части версии
        let versionParts = [];
        for (let i = 0; i < parts.length; i++) {
            versionParts.push(parseInt(parts[i], 10));
        }
        
        const result = versionParts.join('.');
        console.log(`🔧 Результат: "${result}"`);
        
        // Возвращаем полную версию, соединенную точками
        return result;
    }
    
    /**
     * Шаблоны HTML
     */
    get templates() {
        return window.NeetrinoTemplates;
    }
    
    /**
     * Обновление информации о показанных сайтах
     */
    updatePaginationInfo() {
        // Если контейнер ещё не вставлен – выходим
        if (!document.getElementById('sites-count-info')) return;
        if (this.useClientSidePagination) {
            const count = this.filteredSites.length;
            $('#sites-count-info').text(count);
            $('#total-sites-info').text(count);
        } else {
            const shown = this.sites.length;
            const total = this.pagination.total_sites || shown;
            $('#sites-count-info').text(shown);
            $('#total-sites-info').text(total);
        }
    }
}

// Инициализация при загрузке страницы
$(document).ready(() => {
    window.dashboard = new NeetrinoDashboard();
});

// Экспорт для использования в других модулях
window.NeetrinoDashboard = NeetrinoDashboard;

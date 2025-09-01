<?php
/**
 * Neetrino Control Dashboard - Современный интерфейс с табами
 */
?>

<body class="bg-gray-50 min-h-screen">

<!-- Верхняя навигационная панель -->
<nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <div class="flex-shrink-0 flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">N</div>
                    <div class="ml-3">
                        <h1 class="text-xl font-semibold text-gray-900">Neetrino Dashboard</h1>
                        <p class="text-xs text-gray-500" id="dashboard-display-version">v</p>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center space-x-3">
                <!-- Статистика в хедере -->
                <div class="hidden md:flex items-center space-x-4 text-sm">
                    <div class="flex items-center space-x-1">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span id="header-online" class="text-gray-600">0</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                        <span id="header-offline" class="text-gray-600">0</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                        <span id="header-total" class="text-gray-600">0</span>
                    </div>
                </div>
                
                <!-- Кнопки действий -->
                <button data-action="refresh-all" class="modern-btn modern-btn-primary modern-btn-compact">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span class="hidden sm:inline">Обновить</span>
                </button>
                
                <button data-action="add-site" class="modern-btn modern-btn-success modern-btn-compact">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span class="ml-2 hidden lg:inline">Добавить сайт</span>
                </button>
                
                <!-- Кнопка профиля -->
                <a href="profile.php" class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-700 transition-all duration-200" title="Профиль">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Табовая навигация -->
<div class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto">
        <nav class="flex space-x-0" aria-label="Tabs">
            <button data-tab="main" class="tab-button active">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <span>Главная</span>
            </button>
            
            <button data-tab="settings" class="tab-button">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span>Настройки</span>
            </button>
            
            <button data-tab="info" class="tab-button">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Информация</span>
            </button>
        </nav>
    </div>
</div>

<!-- Основной контент -->
<main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    
    <!-- ТАБ: ГЛАВНАЯ -->
    <div id="tab-main" class="tab-content active">
        <!-- Фильтры и поиск -->
        <div class="mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex flex-col sm:flex-row justify-between items-center space-y-3 sm:space-y-0 sm:space-x-4">
                    
                    <!-- Поиск -->
                    <div class="flex-1 max-w-md">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input 
                                type="text" 
                                id="search-sites" 
                                placeholder="Поиск сайтов..." 
                                class="w-full pl-10 pr-4 py-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none text-sm text-gray-900 bg-white border-0"
                            >
                        </div>
                    </div>
                    
                    <!-- Фильтры статуса -->
                    <div class="flex space-x-2">
                        <button data-filter="all" class="filter-btn active">
                            Все <span id="count-all" class="ml-1 text-xs bg-blue-500 text-white px-1.5 py-0.5 rounded-full">0</span>
                        </button>
                        <button data-filter="online" class="filter-btn">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-1"></div>
                            Онлайн <span id="count-online" class="ml-1 text-xs bg-blue-500 text-white px-1.5 py-0.5 rounded-full">0</span>
                        </button>
                        <button data-filter="offline" class="filter-btn">
                            <div class="w-2 h-2 bg-red-500 rounded-full mr-1"></div>
                            Офлайн <span id="count-offline" class="ml-1 text-xs bg-blue-500 text-white px-1.5 py-0.5 rounded-full">0</span>
                        </button>
                        <button data-filter="selected" class="filter-btn hidden">
                            <div class="w-2 h-2 bg-blue-500 rounded-full mr-1"></div>
                            Выбранные <span id="count-selected" class="ml-1 text-xs bg-blue-500 text-white px-1.5 py-0.5 rounded-full">0</span>
                        </button>
                    </div>
                    
                    <!-- Переключатель вида -->
                    <div class="flex items-center space-x-3">
                        <!-- Переключатель вида -->
                        <div class="flex bg-gray-100 rounded-lg p-1">
                            <button data-action="view-list" class="view-btn active">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                </svg>
                            </button>
                            <button data-action="view-grid" class="view-btn">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Панель выбора (умная) -->
        <div id="selection-toolbar" class="hidden bg-white rounded-xl shadow-sm border border-gray-200 p-3 mb-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <label class="inline-flex items-center space-x-2 cursor-pointer select-none">
                        <input type="checkbox" id="select-all-checkbox" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700" id="select-all-label">Выбрать все</span>
                    </label>
                    <span class="text-sm text-gray-600">Выбрано: <span id="selected-count" class="font-semibold">0</span></span>
                </div>
                <div id="bulk-actions" class="hidden">
                    <div class="flex items-center space-x-2">
                        <button data-action="bulk-update" class="modern-btn modern-btn-primary text-xs">
                            Обновить выбранные
                        </button>
                        <button data-action="bulk-update-plugin" class="modern-btn modern-btn-warning text-xs">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Обновить плагин
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Список сайтов -->
        <div id="sites-container">
            <div class="flex items-center justify-center py-12">
                <div class="text-center">
                    <div class="modern-spinner mx-auto mb-4"></div>
                    <p class="text-gray-500 text-sm">Загрузка сайтов...</p>
                </div>
            </div>
        </div>

        <!-- Красивая пагинация -->
        <div id="pagination-container" class="hidden">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mt-6">
                <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                    <!-- Информация о текущей странице -->
                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                        <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full font-medium">
                            Страница <span id="current-page-info">1</span> из <span id="total-pages-info">1</span>
                        </span>
                        <span class="text-gray-400">•</span>
                        <span>Показано <span id="sites-count-info">0</span> из <span id="total-sites-info">0</span> сайтов</span>
                    </div>
                    
                    <!-- Навигация по страницам -->
                    <nav class="flex items-center space-x-1 bg-white border border-gray-200 rounded-lg p-1 shadow-sm" id="pagination-nav">
                        <!-- Кнопки пагинации будут добавлены через JavaScript -->
                    </nav>
                    
                    <!-- Настройки пагинации -->
                    <div class="flex items-center space-x-1 bg-white border border-gray-200 rounded-lg p-1 shadow-sm" id="per-page-buttons">
                        <button data-per-page="10" class="per-page-btn">10</button>
                        <button data-per-page="20" class="per-page-btn active">20</button>
                        <button data-per-page="50" class="per-page-btn">50</button>
                        <button data-per-page="100" class="per-page-btn">100</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ТАБ: НАСТРОЙКИ -->
    <div id="tab-settings" class="tab-content">
        <div class="space-y-6">
            <!-- Основные настройки -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                    </svg>
                    Основные настройки
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Интервал обновления</label>
                        <select id="refresh-interval" class="modern-input">
                            <option value="15">15 секунд</option>
                            <option value="30" selected>30 секунд</option>
                            <option value="60">1 минута</option>
                            <option value="120">2 минуты</option>
                            <option value="300">5 минут</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Режим отображения по умолчанию</label>
                        <select id="default-view" class="modern-input">
                            <option value="list" selected>Список</option>
                            <option value="grid">Сетка</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Таймаут команд (сек)</label>
                        <input type="number" id="command-timeout" value="10" min="5" max="60" class="modern-input">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Количество попыток</label>
                        <input type="number" id="retry-attempts" value="3" min="1" max="10" class="modern-input">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Минимальная версия плагина Neetrino</label>
                        <div class="flex items-center gap-3">
                            <input type="text" id="min-plugin-version" placeholder="например, 3.1" class="modern-input w-48" />
                            <span class="text-sm text-gray-500">Все команды будут требовать эту версию или выше</span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex space-x-3">
                    <button data-action="save-settings" class="modern-btn modern-btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Сохранить настройки
                    </button>
                    <button data-action="reset-settings" class="modern-btn modern-btn-ghost">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Сбросить
                    </button>
                </div>
            </div>

            <!-- Очистка и обслуживание -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 7.172V5L8 4z"></path>
                    </svg>
                    Очистка и обслуживание
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <button data-action="clear-cache" class="maintenance-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <div>
                            <div class="font-semibold">Очистить кэш</div>
                            <div class="text-sm text-gray-500">Очистить локальный кэш</div>
                        </div>
                    </button>
                    
                    <button data-action="optimize-db" class="maintenance-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                        </svg>
                        <div>
                            <div class="font-semibold">Оптимизировать БД</div>
                            <div class="text-sm text-gray-500">Оптимизация базы данных</div>
                        </div>
                    </button>
                    
                    <button data-action="export-data" class="maintenance-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <div>
                            <div class="font-semibold">Экспорт данных</div>
                            <div class="text-sm text-gray-500">Экспорт в JSON/CSV</div>
                        </div>
                    </button>
                    
                    <button data-action="show-trash" class="maintenance-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <div>
                            <div class="font-semibold">Корзина</div>
                            <div class="text-sm text-gray-500">Удаленные сайты</div>
                        </div>
                    </button>
                    
                    <a href="diagnosis.php" class="maintenance-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        <div>
                            <div class="font-semibold">Диагностика</div>
                            <div class="text-sm text-gray-500">Проблемы регистрации</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ТАБ: ИНФОРМАЦИЯ -->
    <div id="tab-info" class="tab-content">
        <div class="space-y-6">
            <!-- Статистика -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 00-2-2z"></path>
                    </svg>
                    Статистика системы
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-green-100 text-green-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="stat-number" id="stat-online">0</div>
                            <div class="stat-label">Онлайн</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-red-100 text-red-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="stat-number" id="stat-offline">0</div>
                            <div class="stat-label">Офлайн</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-blue-100 text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="stat-number" id="stat-total">0</div>
                            <div class="stat-label">Всего сайтов</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-purple-100 text-purple-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="stat-number" id="stat-uptime">99%</div>
                            <div class="stat-label">Uptime</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Последние обновления -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Последние обновления
                </h3>
                
                <div class="space-y-4" id="recent-updates">
                    <div class="update-item">
                        <div class="update-icon">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div class="update-content">
                            <div class="update-title">Система обновлена до v5.0</div>
                            <div class="update-time">Сегодня, 14:30</div>
                        </div>
                    </div>
                    
                    <div class="update-item">
                        <div class="update-icon">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                        <div class="update-content">
                            <div class="update-title">Добавлен табовый интерфейс</div>
                            <div class="update-time">Сегодня, 14:25</div>
                        </div>
                    </div>
                    
                    <div class="update-item">
                        <div class="update-icon">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div class="update-content">
                            <div class="update-title">Улучшена производительность</div>
                            <div class="update-time">Вчера, 16:45</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Версия и информация -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Информация о системе
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="info-group">
                        <div class="info-label">Версия Dashboard</div>
                        <div class="info-value" id="dashboard-display-version-info">v</div>
                    </div>
                    
                    <div class="info-group">
                        <div class="info-label">Последняя проверка</div>
                        <div class="info-value" id="last-check">--</div>
                    </div>
                    
                    <div class="info-group">
                        <div class="info-label">Используемая память</div>
                        <div class="info-value">2.4 MB</div>
                    </div>
                    
                    <div class="info-group">
                        <div class="info-label">Время работы</div>
                        <div class="info-value" id="uptime">--</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>

<!-- Модальные окна -->

<!-- Модальное окно панели управления (Control Panel) - Полноэкранное с табами -->
<div id="control-panel-modal" class="modal-backdrop hidden">
    <div class="control-panel-modal-fullscreen">
        <div class="control-panel-header">
            <div class="flex items-center justify-between mb-6">
                <div class="flex-1 flex items-center gap-3">
                    <span class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white text-lg">🎛️</span>
                    <h3 class="text-xl font-bold text-gray-900">Пульт управления сайтом</h3>
                </div>
                
                <!-- Название сайта по центру -->
                <div class="flex-1 flex justify-center">
                    <button onclick="window.open('javascript:void(0)', '_blank')" 
                            title="Перейти на сайт" 
                            class="site-name-button-header" 
                            id="control-panel-site-button">
                        <span class="text-lg mr-2">🌐</span>
                        <span id="control-panel-site-name">Название сайта</span>
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- Кнопка удаления из дашборда перемещена в таб "Команды" -->
                <div class="flex-1 flex items-center gap-2 justify-end">
                    <button data-action="close-modal" class="text-gray-400 hover:text-gray-600 text-2xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Табы для пульта управления -->
            <div class="control-panel-tabs">
                <nav class="flex space-x-0" aria-label="Control Panel Tabs">
                    <button data-control-tab="main" class="control-tab-button active">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <span>Главная</span>
                    </button>
                    
                    <button data-control-tab="commands" class="control-tab-button">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>Команды</span>
                    </button>
                    
                    <button data-control-tab="info" class="control-tab-button">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Информация</span>
                    </button>
                </nav>
            </div>
        </div>
        
        <div class="control-panel-content">
            <!-- ТАБ: ГЛАВНАЯ -->
            <div id="control-tab-main" class="control-tab-content active">
                <!-- Главные кнопки плагина -->
                <div class="control-section mb-6">
                    <h5 class="control-section-title">⚡ Управление плагином</h5>
                    <div class="main-plugin-controls">
                        <button data-action="execute-command" data-command="update_plugins" class="main-control-btn update-btn">
                            <div class="control-btn-icon bg-blue-100 text-blue-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </div>
                            <span>Обновить плагин</span>
                        </button>
                        
                        <button data-action="execute-command" data-command="deactivate_plugin" class="main-control-btn deactivate-btn">
                            <div class="control-btn-icon bg-yellow-100 text-yellow-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                                </svg>
                            </div>
                            <span>Отключить плагин</span>
                        </button>
                        
                        <button data-action="execute-command" data-command="delete_plugin" class="main-control-btn delete-btn">
                            <div class="control-btn-icon bg-red-100 text-red-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </div>
                            <span>Удалить плагин</span>
                        </button>
                    </div>
                </div>

                <!-- Режим обслуживания - Креативный переключатель -->
                <div class="control-section mb-6">
                    <h5 class="control-section-title">🔧 Режим обслуживания</h5>
                    <div class="maintenance-creative-panel">
                        <div class="maintenance-status-display" id="maintenance-status-display">
                            <div class="status-light offline" id="maintenance-light"></div>
                            <div class="status-info">
                                <div class="status-title">Maintenance Mode</div>
                                <div class="status-subtitle" id="maintenance-current-status">Отключен</div>
                            </div>
                        </div>
                        
                        <div class="maintenance-controls-creative">
                            <!-- 3 состояния: Открыт → Обслуживание → Закрыт -->
                            <button data-action="set-maintenance" data-mode="open" class="creative-toggle-btn mode-toggle-btn mode-open-btn" title="Открыть сайт" aria-label="Открыт">
                                <div class="toggle-icon" aria-hidden="true"><i class="fa-solid fa-lock-open"></i></div>
                                <span>Открыт</span>
                            </button>

                            <button data-action="set-maintenance" data-mode="maintenance" class="creative-toggle-btn mode-toggle-btn mode-maint-btn" title="Режим обслуживания" aria-label="Обслуживание">
                                <div class="toggle-icon" aria-hidden="true"><i class="fa-solid fa-wrench"></i></div>
                                <span>Обслуживание</span>
                            </button>

                            <button data-action="set-maintenance" data-mode="closed" class="creative-toggle-btn mode-toggle-btn mode-closed-btn" title="Закрыть сайт" aria-label="Закрыт">
                                <div class="toggle-icon" aria-hidden="true"><i class="fa-solid fa-lock"></i></div>
                                <span>Закрыт</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ТАБ: КОМАНДЫ -->
            <div id="control-tab-commands" class="control-tab-content">
                <div class="control-section mb-6">
                    <h5 class="control-section-title">⚙️ Команды</h5>
                    <div class="compact-commands-grid">
                        <button data-action="execute-command" data-command="clear_cache" class="compact-btn cache-btn">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            <span>Очистить кэш</span>
                        </button>
                        
                        <button data-action="execute-command" data-command="backup_create" class="compact-btn backup-btn">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                            </svg>
                            <span>Бэкап</span>
                        </button>
                        
                        <button data-action="execute-command" data-command="optimize_db" class="compact-btn optimize-btn">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                            </svg>
                            <span>Оптимизация БД</span>
                        </button>
                        
                        <button data-action="execute-command" data-command="update_core" class="compact-btn core-btn">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <span>Обновить WP</span>
                        </button>
                        
                        <button data-action="execute-command" data-command="security_scan" class="compact-btn security-btn">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            <span>Сканирование</span>
                        </button>
                        
                        <button data-action="execute-command" data-command="performance_test" class="compact-btn performance-btn">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <span>Тест скорости</span>
                        </button>
                    </div>
                </div>
                
                <!-- Опасные действия -->
                <div class="control-section mb-6">
                    <h5 class="control-section-title">⚠️ Опасные действия</h5>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-sm text-red-700 mb-4">Эти действия необратимы. Будьте осторожны!</p>
                        <button data-action="remove-from-dashboard" class="remove-dashboard-btn inline-flex items-center gap-2 px-4 py-2" title="Удалить сайт из дашборда">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Удалить из дашборда</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- ТАБ: ИНФОРМАЦИЯ -->
            <div id="control-tab-info" class="control-tab-content">
                <!-- Информационные поля сайта -->
                <div class="control-section mb-6">
                    <h5 class="control-section-title">📊 Информация о сайте</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="info-card bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-lg">📅</span>
                                <span class="text-sm font-medium text-gray-600">Добавлен</span>
                            </div>
                            <span class="text-lg text-gray-800 font-semibold" id="control-panel-created-at-info">--</span>
                        </div>
                        
                        <div class="info-card bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-lg">🔍</span>
                                <span class="text-sm font-medium text-gray-600">Последняя проверка</span>
                            </div>
                            <span class="text-lg text-gray-800 font-semibold" id="control-panel-last-checked-info">--</span>
                        </div>
                    </div>
                </div>
                
                <!-- Кнопки информации и статуса -->
                <div class="control-section mb-6">
                    <h5 class="control-section-title">🔍 Статус и информация</h5>
                    <div class="info-buttons-grid">
                        <button data-action="execute-command" data-command="get_info" class="info-control-btn info-btn">
                            <div class="control-btn-icon bg-blue-100 text-blue-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold">Информация</div>
                                <div class="text-sm text-gray-500">Получить детальную информацию</div>
                            </div>
                        </button>
                        
                        <button data-action="execute-command" data-command="get_status" class="info-control-btn status-btn">
                            <div class="control-btn-icon bg-green-100 text-green-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold">Статус</div>
                                <div class="text-sm text-gray-500">Проверить статус сайта</div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно добавления сайта -->
<div id="add-site-modal" class="modal-backdrop hidden">
    <div class="modern-modal">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Добавить новый сайт</h3>
            <button data-action="close-modal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="add-site-form" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">URL сайта</label>
                <input 
                    type="url" 
                    id="site-url" 
                    placeholder="https://example.com" 
                    class="modern-input"
                    required
                >
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Название сайта</label>
                <input 
                    type="text" 
                    id="site-name" 
                    placeholder="Мой сайт" 
                    class="modern-input"
                    required
                >
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" data-action="close-modal" class="modern-btn modern-btn-ghost">
                    Отмена
                </button>
                <button type="submit" class="modern-btn modern-btn-primary">
                    Добавить сайт
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно подтверждения удаления -->
<div id="delete-confirm-modal" class="modal-backdrop hidden">
    <div class="modern-modal max-w-md">
        <div class="text-center">
            <div class="w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Подтвердите действие</h3>
            <p id="delete-confirm-text" class="text-gray-600 mb-6"></p>
            
            <div class="flex justify-center space-x-3">
                <button data-action="cancel-delete" class="modern-btn modern-btn-ghost">
                    Отмена
                </button>
                <button id="confirm-delete-btn" class="modern-btn modern-btn-danger">
                    Удалить
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно результатов команд -->
<div id="command-result-modal" class="modal-backdrop hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="modal-content bg-white rounded-lg p-6 w-full max-w-2xl border-2 border-blue-300 shadow-2xl">
            <div class="flex items-center justify-between mb-4 border-b border-gray-200 pb-3">
                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    <span class="text-2xl">📄</span>
                    <span>Результат команды</span>
                </h2>
                <button onclick="dashboard.hideCommandResultModal()" class="text-gray-500 hover:text-gray-700 text-xl hover:bg-gray-100 w-8 h-8 rounded-full flex items-center justify-center transition-all">✕</button>
            </div>
            <div id="command-result-content" class="mb-4"></div>
            <div class="mt-6 text-right border-t border-gray-200 pt-4">
                <button onclick="dashboard.hideCommandResultModal()" class="modern-btn modern-btn-primary px-6 py-2 rounded-lg shadow hover:shadow-md transition-all">
                    ✅ Закрыть
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Уведомления -->
<div id="notification-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

<!-- Плавающая кнопка корзины -->
<button 
    data-action="show-trash" 
    class="floating-trash-btn" 
    title="Корзина"
    aria-label="Открыть корзину">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
    </svg>
</button>

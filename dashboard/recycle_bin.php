<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина - Neetrino Control Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Современный минималистичный дизайн */
        .trash-item {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            backdrop-filter: blur(10px);
        }
        
        .trash-item:hover {
            border-color: #d1d5db;
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }
        
        .details-content {
            display: none;
            animation: slideDown 0.4s ease-out;
        }
        
        .details-content.show {
            display: block;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                max-height: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                max-height: 300px;
                transform: translateY(0);
            }
        }
        
        .btn-details {
            transition: all 0.3s ease;
        }
        
        .btn-details:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card {
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">

<!-- Современный заголовок с фоном -->
<div class="bg-white border-b border-gray-200 shadow-sm">
    <div class="container mx-auto px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-6">
                <a href="index.php" class="group flex items-center space-x-2 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-xl transition-all duration-300 transform hover:scale-105">
                    <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    <span class="font-medium">Dashboard</span>
                </a>
                <div class="h-8 w-px bg-gray-300"></div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9,3V4H4V6H5V19A2,2 0 0,0 7,21H17A2,2 0 0,0 19,19V6H20V4H15V3H9M7,6H17V19H7V6M9,8V17H11V8H9M13,8V17H15V8H13Z"/>
                        </svg>
                        Корзина удаленных сайтов
                    </h1>
                    <p class="text-sm text-gray-500">История удаленных плагинов и сайтов</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <button onclick="refreshTrash()" class="group flex items-center space-x-2 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-xl transition-all duration-300 transform hover:scale-105">
                    <svg class="w-4 h-4 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="refresh-icon-svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span class="font-medium" id="refresh-text">Обновить</span>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-6 py-8">
    <!-- Креативная сетка со статистикой -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">
        
        <!-- Основная статистика -->
        <div class="lg:col-span-8">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                <!-- Заголовок с градиентом -->
                <div class="bg-gradient-to-r from-red-500 to-pink-600 p-6 text-white">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9,3V4H4V6H5V19A2,2 0 0,0 7,21H17A2,2 0 0,0 19,19V6H20V4H15V3H9M7,6H17V19H7V6M9,8V17H11V8H9M13,8V17H15V8H13Z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold">Корзина удалений</h2>
                            <p class="text-red-100">Аудит удаленных сайтов и плагинов</p>
                            <p class="text-sm text-red-200 mt-1">Записи сохраняются навсегда для аудита</p>
                        </div>
                    </div>
                </div>
                
                <!-- Статистические карточки -->
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="stat-card bg-gradient-to-br from-red-50 to-red-100 p-4 rounded-xl border border-red-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-2xl font-bold text-red-600" id="total-deleted">-</div>
                                    <div class="text-sm text-red-800">Всего удалений</div>
                                </div>
                                <div class="w-12 h-12 bg-red-200 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stat-card bg-gradient-to-br from-orange-50 to-orange-100 p-4 rounded-xl border border-orange-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-2xl font-bold text-orange-600" id="unique-sites">-</div>
                                    <div class="text-sm text-orange-800">Уникальных сайтов</div>
                                </div>
                                <div class="w-12 h-12 bg-orange-200 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-orange-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M16,6L18.29,8.29L13.41,13.17L9.41,9.17L2,16.59L3.41,18L9.41,12L13.41,16L19.71,9.71L22,12V6H16Z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stat-card bg-gradient-to-br from-gray-50 to-gray-100 p-4 rounded-xl border border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-2xl font-bold text-gray-600" id="last-deletion">-</div>
                                    <div class="text-sm text-gray-800">Последнее удаление</div>
                                </div>
                                <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M16.2,16.2L11,13V7H12.5V12.2L17,14.9L16.2,16.2Z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Боковая панель с быстрой информацией -->
        <div class="lg:col-span-4">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M13,9H11V7H13M13,17H11V11H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"/>
                    </svg>
                    Информация
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                        <div>
                            <div class="text-sm font-medium text-blue-800">Система аудита</div>
                            <div class="text-xs text-blue-600">Активна</div>
                        </div>
                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                        <div>
                            <div class="text-sm font-medium text-green-800">Сохранение данных</div>
                            <div class="text-xs text-green-600">Навсегда</div>
                        </div>
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                        <div>
                            <div class="text-sm font-medium text-purple-800">Автоматическое удаление</div>
                            <div class="text-xs text-purple-600">При деактивации</div>
                        </div>
                        <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Список удаленных сайтов -->
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-gray-700 to-gray-800 p-4 text-white">
            <h3 class="font-semibold flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M4,6H2V20A2,2 0 0,0 4,22H18V20H4V6M20,2H8A2,2 0 0,0 6,4V16A2,2 0 0,0 8,18H20A2,2 0 0,0 22,16V4A2,2 0 0,0 20,2M20,16H8V4H20V16Z"/>
                </svg>
                Удаленные сайты
            </h3>
        </div>
        
        <!-- Фильтры и поиск -->
        <div class="p-6 border-b border-gray-200 bg-gray-50">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Поиск по имени -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Поиск по имени</label>
                    <div class="relative">
                        <input type="text" id="search-input" placeholder="Название сайта..."
                               class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <svg class="w-4 h-4 absolute left-3 top-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
                
                <!-- Фильтр по дате -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Период удаления</label>
                    <select id="date-filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Все время</option>
                        <option value="today">Сегодня</option>
                        <option value="week">Последняя неделя</option>
                        <option value="month">Последний месяц</option>
                        <option value="3months">Последние 3 месяца</option>
                    </select>
                </div>
                
                <!-- Сортировка -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Сортировка</label>
                    <select id="sort-filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="date_desc">Сначала новые</option>
                        <option value="date_asc">Сначала старые</option>
                        <option value="name_asc">По имени А-Я</option>
                        <option value="name_desc">По имени Я-А</option>
                    </select>
                </div>
                
                <!-- Кнопки действий -->
                <div class="flex items-end space-x-2">
                    <button onclick="applyFilters()" class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2 rounded-xl transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:scale-105">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        <span class="font-medium">Применить</span>
                    </button>
                    <button onclick="clearFilters()" id="clear-filters-btn" class="bg-gradient-to-r from-red-400 to-red-500 hover:from-red-500 hover:to-red-600 text-white px-4 py-2 rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105" style="display: none;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Статистика фильтрации -->
            <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
                <div id="filter-stats">Показано записей: 0</div>
                <div class="flex items-center space-x-2">
                    <div class="flex items-center space-x-1 bg-white border border-gray-200 rounded-lg p-1 shadow-sm">
                        <button onclick="setPerPage(10)" class="per-page-btn px-2 py-1 text-xs rounded-md transition-all duration-200 hover:bg-blue-50 hover:text-blue-600" data-value="10">10</button>
                        <button onclick="setPerPage(20)" class="per-page-btn px-2 py-1 text-xs rounded-md transition-all duration-200 hover:bg-blue-50 hover:text-blue-600 bg-blue-500 text-white" data-value="20">20</button>
                        <button onclick="setPerPage(50)" class="per-page-btn px-2 py-1 text-xs rounded-md transition-all duration-200 hover:bg-blue-50 hover:text-blue-600" data-value="50">50</button>
                        <button onclick="setPerPage(100)" class="per-page-btn px-2 py-1 text-xs rounded-md transition-all duration-200 hover:bg-blue-50 hover:text-blue-600" data-value="100">100</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="trash-container" class="p-6">
            <div class="text-center py-12">
                <div class="loading-spinner mx-auto mb-4"></div>
                <p class="text-gray-600">Загрузка корзины...</p>
            </div>
        </div>
        
        <!-- Пагинация -->
        <div id="pagination-container" class="p-6 border-t border-gray-200 bg-gray-50" style="display: none;">
            <div class="flex flex-col items-center space-y-4">
                <!-- Информация о записях -->
                <div class="text-sm text-gray-600" id="pagination-info">
                    Показано 1-20 из 100 записей
                </div>
                
                <!-- Навигация по страницам -->
                <div class="flex items-center justify-center space-x-2" id="pagination-controls">
                    <!-- Пагинация будет добавлена через JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Глобальные переменные
let trashItems = [];
let filteredItems = [];
let currentPage = 1;
let itemsPerPage = 20;

// Инициализация
$(document).ready(function() {
    loadTrash();
    setupEventListeners();
});

// Настройка обработчиков событий
function setupEventListeners() {
    // Поиск в реальном времени с задержкой
    let searchTimeout;
    $('#search-input').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applyFilters();
            checkActiveFilters();
        }, 500);
    });
    
    // Обработчики для select'ов
    $('#date-filter, #sort-filter').on('change', function() {
        checkActiveFilters();
    });
    
    // Enter в поле поиска
    $('#search-input').on('keypress', function(e) {
        if (e.which === 13) {
            applyFilters();
            checkActiveFilters();
        }
    });
}

// Проверка активных фильтров
function checkActiveFilters() {
    const searchTerm = $('#search-input').val().trim();
    const dateFilter = $('#date-filter').val();
    const sortFilter = $('#sort-filter').val();
    
    const hasActiveFilters = searchTerm !== '' || dateFilter !== '' || sortFilter !== 'date_desc';
    
    if (hasActiveFilters) {
        $('#clear-filters-btn').fadeIn(200);
    } else {
        $('#clear-filters-btn').fadeOut(200);
    }
}

// Функция для установки количества записей на страницу
function setPerPage(value) {
    itemsPerPage = value;
    currentPage = 1;
    
    // Обновляем стили кнопок
    $('.per-page-btn').removeClass('bg-blue-500 text-white').addClass('text-gray-700');
    $(`.per-page-btn[data-value="${value}"]`).removeClass('text-gray-700').addClass('bg-blue-500 text-white');
    
    applyFilters();
}

// Загрузка корзины
async function loadTrash() {
    try {
        const response = await fetch('api.php?action=get_trash');
        const result = await response.json();
        
        if (result.success) {
            trashItems = result.trash_items;
            applyFilters();
            updateStats();
            checkActiveFilters();
        } else {
            showError('Ошибка загрузки корзины: ' + result.error);
        }
    } catch (error) {
        showError('Ошибка подключения: ' + error.message);
    }
}

// Применение фильтров
function applyFilters() {
    const searchTerm = $('#search-input').val().toLowerCase().trim();
    const dateFilter = $('#date-filter').val();
    const sortFilter = $('#sort-filter').val();
    
    // Фильтрация
    filteredItems = trashItems.filter(item => {
        // Поиск по имени
        const matchesSearch = searchTerm === '' || 
            item.site_name.toLowerCase().includes(searchTerm) ||
            item.site_url.toLowerCase().includes(searchTerm);
        
        // Фильтр по дате
        const matchesDate = filterByDate(item, dateFilter);
        
        return matchesSearch && matchesDate;
    });
    
    // Сортировка
    filteredItems.sort((a, b) => {
        switch (sortFilter) {
            case 'date_desc':
                return new Date(b.deleted_at) - new Date(a.deleted_at);
            case 'date_asc':
                return new Date(a.deleted_at) - new Date(b.deleted_at);
            case 'name_asc':
                return a.site_name.localeCompare(b.site_name, 'ru');
            case 'name_desc':
                return b.site_name.localeCompare(a.site_name, 'ru');
            default:
                return new Date(b.deleted_at) - new Date(a.deleted_at);
        }
    });
    
    // Сброс на первую страницу при новом поиске
    currentPage = 1;
    
    renderTrash();
    renderPagination();
    updateFilterStats();
    checkActiveFilters();
}

// Фильтр по дате
function filterByDate(item, dateFilter) {
    if (!dateFilter) return true;
    
    const itemDate = new Date(item.deleted_at);
    const now = new Date();
    
    switch (dateFilter) {
        case 'today':
            return itemDate.toDateString() === now.toDateString();
        case 'week':
            const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
            return itemDate >= weekAgo;
        case 'month':
            const monthAgo = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate());
            return itemDate >= monthAgo;
        case '3months':
            const threeMonthsAgo = new Date(now.getFullYear(), now.getMonth() - 3, now.getDate());
            return itemDate >= threeMonthsAgo;
        default:
            return true;
    }
}

// Отображение корзины с пагинацией
function renderTrash() {
    const container = $('#trash-container');
    
    if (filteredItems.length === 0) {
        container.html(`
            <div class="text-center py-16">
                <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9,3V4H4V6H5V19A2,2 0 0,0 7,21H17A2,2 0 0,0 19,19V6H20V4H15V3H9M7,6H17V19H7V6M9,8V17H11V8H9M13,8V17H15V8H13Z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">Записи не найдены</h3>
                <p class="text-gray-500">Попробуйте изменить параметры поиска</p>
            </div>
        `);
        $('#pagination-container').hide();
        return;
    }
    
    // Пагинация
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const pageItems = filteredItems.slice(startIndex, endIndex);
    
    let html = '<div class="space-y-4">';
    pageItems.forEach((item, index) => {
        const realIndex = startIndex + index;
        const deletedDate = new Date(item.deleted_at).toLocaleDateString('ru-RU');
        const deletedTime = new Date(item.deleted_at).toLocaleTimeString('ru-RU');
        
        html += `
            <div class="trash-item bg-gradient-to-r from-white to-gray-50 rounded-xl p-5 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4 flex-1">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9,3V4H4V6H5V19A2,2 0 0,0 7,21H17A2,2 0 0,0 19,19V6H20V4H15V3H9M7,6H17V19H7V6M9,8V17H11V8H9M13,8V17H15V8H13Z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-semibold text-gray-900 truncate">
                                ${escapeHtml(item.site_name)}
                            </h3>
                            <p class="text-sm text-gray-500 truncate">${escapeHtml(item.site_url)}</p>
                            <div class="flex items-center space-x-4 mt-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12,2C13.1,2 14,2.9 14,4C14,5.1 13.1,6 12,6C10.9,6 10,5.1 10,4C10,2.9 10.9,2 12,2M21,9V7L15,1H5C3.89,1 3,1.89 3,3V7H9V9H21M7,10A2,2 0 0,0 5,12A2,2 0 0,0 7,14A2,2 0 0,0 9,12A2,2 0 0,0 7,10M13,10A2,2 0 0,0 11,12A2,2 0 0,0 13,14A2,2 0 0,0 15,12A2,2 0 0,0 13,10M17,10A2,2 0 0,0 15,12A2,2 0 0,0 17,14A2,2 0 0,0 19,12A2,2 0 0,0 17,10M19,15H5V19A2,2 0 0,0 7,21H17A2,2 0 0,0 19,19V15Z"/>
                                    </svg>
                                    Удален
                                </span>
                                <span class="text-xs text-gray-400">${deletedDate} в ${deletedTime}</span>
                            </div>
                        </div>
                    </div>
                    <div class="ml-4">
                        <button 
                            onclick="toggleDetails(${realIndex})" 
                            class="btn-details bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-md"
                            id="btn-${realIndex}"
                        >
                            <span class="flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Подробнее</span>
                            </span>
                        </button>
                    </div>
                </div>
                
                <!-- Скрытые детали -->
                <div class="details-content mt-6 pt-6 border-t border-gray-200" id="details-${realIndex}">
                    <div class="bg-gradient-to-r from-gray-50 to-white rounded-xl p-6 border border-gray-100">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M13,9H11V7H13M13,17H11V11H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"/>
                                    </svg>
                                    Информация об удалении
                                </h4>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between p-3 bg-white rounded-lg border">
                                        <span class="text-sm text-gray-600">Причина</span>
                                        <span class="text-sm font-medium text-gray-900">${item.deleted_reason === 'plugin_deleted' ? 'Удаление плагина' : item.deleted_reason}</span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 bg-white rounded-lg border">
                                        <span class="text-sm text-gray-600">Дата и время</span>
                                        <span class="text-sm font-medium text-gray-900">${new Date(item.deleted_at).toLocaleString('ru-RU')}</span>
                                    </div>
                                    ${item.original_site_id ? `
                                    <div class="flex items-center justify-between p-3 bg-white rounded-lg border">
                                        <span class="text-sm text-gray-600">ID сайта</span>
                                        <span class="text-sm font-medium text-gray-900">${item.original_site_id}</span>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M11,16.5L18,9.5L16.59,8.09L11,13.67L7.91,10.59L6.5,12L11,16.5Z"/>
                                    </svg>
                                    Дополнительная информация
                                </h4>
                                <div class="space-y-3">
                                    <div class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                                        <div class="text-sm text-blue-800">
                                            <div class="font-medium mb-2">Последствия удаления:</div>
                                            <ul class="space-y-1 text-xs">
                                                <li>• Плагин был удален с сайта</li>
                                                <li>• Сайт удален из активного списка</li>
                                                <li>• При реактивации создастся новая запись</li>
                                                ${item.active_modules ? `<li>• Модули: ${escapeHtml(item.active_modules)}</li>` : ''}
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    container.html(html);
    $('#pagination-container').show();
}

// Рендер пагинации
function renderPagination() {
    const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
    
    if (totalPages <= 1) {
        $('#pagination-container').hide();
        return;
    }
    
    const start = (currentPage - 1) * itemsPerPage + 1;
    const end = Math.min(currentPage * itemsPerPage, filteredItems.length);
    
    $('#pagination-info').text(`Показано ${start}-${end} из ${filteredItems.length} записей`);
    
    let paginationHtml = '';
    
    // Кнопка "Предыдущая"
    if (currentPage > 1) {
        paginationHtml += `
            <button onclick="changePage(${currentPage - 1})" 
                    class="px-4 py-2 text-sm bg-white border border-gray-300 rounded-xl hover:bg-blue-50 hover:border-blue-300 hover:text-blue-600 transition-all duration-200 shadow-sm">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Предыдущая
            </button>
        `;
    }
    
    // Номера страниц
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    if (startPage > 1) {
        paginationHtml += `<button onclick="changePage(1)" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-xl hover:bg-blue-50 hover:border-blue-300 hover:text-blue-600 transition-all duration-200 shadow-sm">1</button>`;
        if (startPage > 2) {
            paginationHtml += `<span class="px-2 text-gray-400">...</span>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === currentPage ? 
            'bg-gradient-to-r from-blue-500 to-blue-600 text-white border-blue-500 shadow-lg' : 
            'bg-white border-gray-300 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-600 shadow-sm';
        paginationHtml += `
            <button onclick="changePage(${i})" 
                    class="px-3 py-2 text-sm border rounded-xl transition-all duration-200 ${activeClass}">
                ${i}
            </button>
        `;
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationHtml += `<span class="px-2 text-gray-400">...</span>`;
        }
        paginationHtml += `<button onclick="changePage(${totalPages})" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-xl hover:bg-blue-50 hover:border-blue-300 hover:text-blue-600 transition-all duration-200 shadow-sm">${totalPages}</button>`;
    }
    
    // Кнопка "Следующая"
    if (currentPage < totalPages) {
        paginationHtml += `
            <button onclick="changePage(${currentPage + 1})" 
                    class="px-4 py-2 text-sm bg-white border border-gray-300 rounded-xl hover:bg-blue-50 hover:border-blue-300 hover:text-blue-600 transition-all duration-200 shadow-sm">
                Следующая
                <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        `;
    }
    
    $('#pagination-controls').html(paginationHtml);
}

// Смена страницы
function changePage(page) {
    currentPage = page;
    renderTrash();
    renderPagination();
    
    // Прокрутка к началу списка
    document.getElementById('trash-container').scrollIntoView({ behavior: 'smooth' });
}

// Обновление статистики фильтрации
function updateFilterStats() {
    $('#filter-stats').text(`Показано записей: ${filteredItems.length} из ${trashItems.length}`);
}

// Очистка фильтров
function clearFilters() {
    $('#search-input').val('');
    $('#date-filter').val('');
    $('#sort-filter').val('date_desc');
    currentPage = 1;
    
    // Анимация очистки
    $('#clear-filters-btn').addClass('animate-pulse');
    setTimeout(() => {
        $('#clear-filters-btn').removeClass('animate-pulse');
    }, 300);
    
    applyFilters();
    checkActiveFilters();
}

// Обновление корзины
async function refreshTrash() {
    const refreshIcon = $('#refresh-icon-svg');
    const refreshText = $('#refresh-text');
    
    refreshIcon.addClass('animate-spin');
    refreshText.text('Обновление...');
    
    await loadTrash();
    
    setTimeout(() => {
        refreshIcon.removeClass('animate-spin');
        refreshText.text('Обновить');
    }, 500);
}

// Обновление статистики
function updateStats() {
    const total = trashItems.length;
    const uniqueSites = new Set(trashItems.map(item => item.site_url)).size;
    const lastDeletion = trashItems.length > 0 ? 
        new Date(trashItems[0].deleted_at).toLocaleDateString('ru-RU') : 'Нет';
    
    $('#total-deleted').text(total);
    $('#unique-sites').text(uniqueSites);
    $('#last-deletion').text(lastDeletion);
}

// Переключение деталей
function toggleDetails(index) {
    const detailsEl = $(`#details-${index}`);
    const btnEl = $(`#btn-${index}`);
    
    if (detailsEl.hasClass('show')) {
        detailsEl.removeClass('show');
        btnEl.html(`
            <span class="flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Подробнее</span>
            </span>
        `);
    } else {
        // Скрыть все другие открытые детали
        $('.details-content').removeClass('show');
        $('.btn-details').html(`
            <span class="flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Подробнее</span>
            </span>
        `);
        
        // Показать текущие детали
        detailsEl.addClass('show');
        btnEl.html(`
            <span class="flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                </svg>
                <span>Скрыть</span>
            </span>
        `);
    }
}

// Утилиты
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function showError(message) {
    alert('❌ ' + message);
}
</script>

</body>
</html>

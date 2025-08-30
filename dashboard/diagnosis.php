<?php
/**
 * Простая страница диагностики для веб-интерфейса
 */

define('NEETRINO_DASHBOARD', true);
require_once 'config.php';
require_once 'auth_check.php';

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Диагностика - Neetrino Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center mb-6">
                <a href="index.php" class="text-blue-600 hover:text-blue-700 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900">🔍 Диагностика регистрации сайтов</h1>
            </div>

            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h2 class="text-lg font-semibold text-blue-800 mb-2">Описание проблемы</h2>
                <p class="text-blue-700">
                    Вы пытаетесь зарегистрировать 3 сайта, но при установке плагина на третий сайт, 
                    предпоследний сайт исчезает и заменяется новым. У вас всегда остается только 2 сайта.
                </p>
            </div>

            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <h2 class="text-lg font-semibold text-green-800 mb-2">✅ Исправление применено</h2>
                <p class="text-green-700">
                    Проблема была в том, что система проверяла регистрацию по email ИЛИ URL. 
                    Если у вас один email для нескольких сайтов, система находила существующую запись и обновляла её вместо создания новой.
                    <br><br>
                    <strong>Исправлено:</strong> Теперь система проверяет только URL при регистрации.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Текущие сайты -->
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <span class="mr-2">🌐</span>
                        Активные сайты
                    </h3>
                    <div id="active-sites">
                        <div class="text-center py-4">
                            <div class="animate-spin w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full mx-auto"></div>
                            <p class="text-gray-500 mt-2">Загрузка...</p>
                        </div>
                    </div>
                </div>

                <!-- Корзина -->
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <span class="mr-2">🗑️</span>
                        Удаленные сайты
                    </h3>
                    <div id="trash-sites">
                        <div class="text-center py-4">
                            <div class="animate-spin w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full mx-auto"></div>
                            <p class="text-gray-500 mt-2">Загрузка...</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <h2 class="text-lg font-semibold text-yellow-800 mb-3">📋 Что делать дальше</h2>
                <ol class="list-decimal list-inside space-y-2 text-yellow-700">
                    <li>Проверьте список активных сайтов выше</li>
                    <li>Если нужные сайты в корзине - восстановите их через кнопку</li>
                    <li>Попробуйте установить/переустановить плагин на ваших 3 сайтах</li>
                    <li>Теперь все 3 сайта должны регистрироваться корректно</li>
                </ol>
            </div>

            <div class="mt-6 flex space-x-4">
                <button onclick="loadData()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    🔄 Обновить данные
                </button>
                <button onclick="restoreAllSites()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    ↩️ Восстановить все из корзины
                </button>
                <a href="index.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    🏠 Вернуться в панель
                </a>
            </div>
        </div>
    </div>

    <script>
        async function loadData() {
            // Загружаем активные сайты
            try {
                const sitesResponse = await fetch('api.php?action=get_sites');
                const sitesData = await sitesResponse.json();
                
                const activeSitesDiv = document.getElementById('active-sites');
                if (sitesData.success && sitesData.sites.length > 0) {
                    activeSitesDiv.innerHTML = sitesData.sites.map(site => `
                        <div class="mb-2 p-3 bg-gray-50 rounded border">
                            <div class="font-semibold">${site.site_name}</div>
                            <div class="text-sm text-gray-600">${site.site_url}</div>
                            <div class="text-xs text-gray-500">ID: ${site.id} | ${site.admin_email || 'No email'}</div>
                        </div>
                    `).join('');
                } else {
                    activeSitesDiv.innerHTML = '<p class="text-gray-500 text-center py-4">Нет активных сайтов</p>';
                }
            } catch (error) {
                document.getElementById('active-sites').innerHTML = '<p class="text-red-500 text-center py-4">Ошибка загрузки</p>';
            }

            // Загружаем корзину
            try {
                const trashResponse = await fetch('api.php?action=get_trash');
                const trashData = await trashResponse.json();
                
                const trashSitesDiv = document.getElementById('trash-sites');
                if (trashData.success && trashData.trash_items.length > 0) {
                    trashSitesDiv.innerHTML = trashData.trash_items.map(item => `
                        <div class="mb-2 p-3 bg-red-50 rounded border border-red-200">
                            <div class="font-semibold">${item.site_name}</div>
                            <div class="text-sm text-gray-600">${item.site_url}</div>
                            <div class="text-xs text-gray-500">Удален: ${item.deleted_at}</div>
                            <div class="text-xs text-gray-500">Причина: ${item.deleted_reason}</div>
                            <button onclick="restoreSite(${item.id})" class="mt-2 text-xs bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600">
                                Восстановить
                            </button>
                        </div>
                    `).join('');
                } else {
                    trashSitesDiv.innerHTML = '<p class="text-gray-500 text-center py-4">Корзина пуста</p>';
                }
            } catch (error) {
                document.getElementById('trash-sites').innerHTML = '<p class="text-red-500 text-center py-4">Ошибка загрузки</p>';
            }
        }

        async function restoreSite(trashId) {
            if (!confirm('Восстановить этот сайт?')) return;
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=restore_site&trash_id=${trashId}`
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('Сайт восстановлен!');
                    loadData();
                } else {
                    alert('Ошибка: ' + result.error);
                }
            } catch (error) {
                alert('Ошибка сети');
            }
        }

        async function restoreAllSites() {
            if (!confirm('Восстановить ВСЕ сайты из корзины?')) return;
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=restore_all_sites'
                });
                
                const result = await response.json();
                if (result.success) {
                    alert(`Восстановлено ${result.restored} сайтов!`);
                    loadData();
                } else {
                    alert('Ошибка: ' + result.error);
                }
            } catch (error) {
                alert('Ошибка сети');
            }
        }

        // Загружаем данные при загрузке страницы
        loadData();
    </script>
</body>
</html>

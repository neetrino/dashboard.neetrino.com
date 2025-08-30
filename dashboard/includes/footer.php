<?php
/**
 * Neetrino Control Dashboard - Футер
 */
?>

<!-- Модальное окно добавления сайта -->
<div id="add-site-modal" class="modal-backdrop fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="modal-content bg-white rounded-lg p-6 w-full max-w-md">
            <h2 class="text-xl font-bold mb-4">➕ Добавить новый сайт</h2>
            <form id="add-site-form">
                <div class="mb-4">
                    <label class="form-label">URL сайта</label>
                    <input type="url" id="site-url" class="form-input" placeholder="https://example.com" required>
                    <div class="text-sm text-gray-500 mt-1">Введите полный URL с протоколом (http:// или https://)</div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Название сайта</label>
                    <input type="text" id="site-name" class="form-input" placeholder="Мой сайт" required>
                    <div class="text-sm text-gray-500 mt-1">Произвольное название для удобства</div>
                </div>
                <div class="flex gap-4">
                    <button type="submit" class="btn btn-success flex-1">
                        ✅ Добавить
                    </button>
                    <button type="button" onclick="dashboard.hideAddSiteModal()" class="btn btn-secondary flex-1">
                        ❌ Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно результата команды -->
<div id="command-result-modal" class="modal-backdrop fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="modal-content bg-white rounded-lg p-6 w-full max-w-lg">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold">📄 Результат команды</h2>
                <button onclick="dashboard.hideCommandResultModal()" class="text-gray-500 hover:text-gray-700 text-xl">✕</button>
            </div>
            <div id="command-result-content"></div>
            <div class="mt-6 text-right">
                <button onclick="dashboard.hideCommandResultModal()" class="btn btn-primary">
                    ✅ Закрыть
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Уведомления (контейнер для toast) -->
<div id="notifications-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

</body>
</html>

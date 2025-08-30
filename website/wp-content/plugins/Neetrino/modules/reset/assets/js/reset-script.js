/* Neetrino Reset Module Scripts */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Глобальные переменные
        const $modal = $('#neetrino-reset-modal');
        const $fullResetButton = $('#full-reset-button');
        const $confirmResetButton = $('#confirm-reset');
        const $cancelResetButton = $('#cancel-reset');
        
        // Обработчик главной кнопки сброса
        $fullResetButton.on('click', function() {
            showResetModal();
        });
        
        // Обработчик подтверждения сброса
        $confirmResetButton.on('click', function() {
            hideResetModal();
            performFullReset();
        });
        
        // Обработчик отмены сброса
        $cancelResetButton.on('click', function() {
            hideResetModal();
        });
        
        // Закрытие модального окна по клику на фон
        $modal.on('click', function(e) {
            if (e.target === this) {
                hideResetModal();
            }
        });
        
        // Закрытие модального окна по Escape
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $modal.is(':visible')) {
                hideResetModal();
            }
        });
        
        // Обработчик создания снимка
        $('#create-snapshot-button').on('click', function() {
            const name = $('#snapshot-name').val().trim();
            if (!name) {
                showNotice('Введите название снимка', 'error');
                return;
            }
            
            createSnapshot(name);
        });
        
        // Обработчики кнопок снимков
        $(document).on('click', '.restore-snapshot', function() {
            const snapshotId = $(this).data('id');
            
            if (confirm('Вы уверены что хотите восстановить этот снимок? Текущее состояние сайта будет потеряно.')) {
                restoreSnapshot(snapshotId);
            }
        });
        
        $(document).on('click', '.delete-snapshot', function() {
            const snapshotId = $(this).data('id');
            
            if (confirm('Вы уверены что хотите удалить этот снимок?')) {
                deleteSnapshot(snapshotId);
            }
        });
        
        // Обработчики частичного сброса
        $(document).on('click', '.partial-reset', function() {
            const action = $(this).data('action');
            const $button = $(this);
            
            // Определяем текст подтверждения в зависимости от действия
            let confirmText = 'Вы уверены что хотите выполнить это действие?';
            
            switch (action) {
                case 'uploads':
                    confirmText = 'Удалить все файлы из папки uploads? Это действие нельзя отменить!';
                    break;
                case 'plugins':
                    confirmText = 'Удалить все плагины кроме Neetrino и отключить их? Это действие нельзя отменить!';
                    break;
                case 'themes':
                    confirmText = 'Удалить все неактивные темы и активировать стандартную? Это действие нельзя отменить!';
                    break;
                case 'custom-tables':
                    confirmText = 'Очистить все кастомные таблицы базы данных? Это действие нельзя отменить!';
                    break;
            }
            
            if (confirm(confirmText)) {
                performPartialReset(action, $button);
            }
        });
        
        /**
         * Показать модальное окно подтверждения
         */
        function showResetModal() {
            $modal.fadeIn(300);
            $('body').addClass('modal-open');
        }
        
        /**
         * Скрыть модальное окно
         */
        function hideResetModal() {
            $modal.fadeOut(300);
            $('body').removeClass('modal-open');
        }
        
        /**
         * Выполнить полный сброс сайта
         */
        function performFullReset() {
            // Блокируем кнопку и показываем состояние загрузки
            $fullResetButton.addClass('neetrino-loading').prop('disabled', true);
            $fullResetButton.html('<div class="neetrino-reset-button-content"><div class="neetrino-reset-button-icon"><span class="dashicons dashicons-update-alt"></span></div><div class="neetrino-reset-button-text"><span class="neetrino-reset-button-title">ВЫПОЛНЯЕТСЯ СБРОС</span><span class="neetrino-reset-button-subtitle">Пожалуйста подождите...</span></div></div>');
            
            // Показываем уведомление
            showNotice('Выполняется полный сброс: удаление контента, отключение плагинов, активация стандартной темы...', 'warning');
            
            // Отправляем AJAX запрос
            $.ajax({
                url: neetrinoReset.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neetrino_reset_site',
                    nonce: neetrinoReset.nonce
                },
                timeout: 60000, // 60 секунд таймаут
                success: function(response) {
                    if (response.success) {
                        showNotice('Сброс выполнен успешно!', 'success');
                        
                        // Обновляем кнопку на успешное состояние
                        $fullResetButton.html('<div class="neetrino-reset-button-content"><div class="neetrino-reset-button-icon"><span class="dashicons dashicons-yes"></span></div><div class="neetrino-reset-button-text"><span class="neetrino-reset-button-title">СБРОС ЗАВЕРШЕН</span><span class="neetrino-reset-button-subtitle">Сайт сброшен к заводским настройкам</span></div></div>');
                        
                        // Возвращаем кнопку в исходное состояние через 3 секунды
                        setTimeout(function() {
                            resetButton();
                        }, 3000);
                    } else {
                        showNotice('Ошибка: ' + response.data.message, 'error');
                        resetButton();
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = 'Произошла ошибка при сбросе';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Превышено время ожидания. Сброс может продолжаться в фоне.';
                    } else if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMessage = xhr.responseJSON.data.message;
                    }
                    
                    showNotice(errorMessage, 'error');
                    resetButton();
                }
            });
        }
        
        /**
         * Создать снимок базы данных
         */
        function createSnapshot(name) {
            const $button = $('#create-snapshot-button');
            const originalText = $button.text();
            
            $button.prop('disabled', true).text('Создается...');
            
            $.ajax({
                url: neetrinoReset.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neetrino_create_snapshot',
                    nonce: neetrinoReset.nonce,
                    name: name
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('Снимок создан успешно!', 'success');
                        $('#snapshot-name').val('');
                        
                        // Перезагружаем страницу чтобы показать новый снимок
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotice('Ошибка: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    showNotice('Произошла ошибка при создании снимка', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        }
        
        /**
         * Восстановить снимок
         */
        function restoreSnapshot(snapshotId) {
            const $button = $('.restore-snapshot[data-id="' + snapshotId + '"]');
            const originalText = $button.text();
            
            $button.prop('disabled', true).text('Восстанавливается...');
            showNotice('Восстановление снимка, пожалуйста подождите...', 'warning');
            
            $.ajax({
                url: neetrinoReset.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neetrino_restore_snapshot',
                    nonce: neetrinoReset.nonce,
                    snapshot_id: snapshotId
                },
                timeout: 60000,
                success: function(response) {
                    if (response.success) {
                        showNotice('Снимок восстановлен успешно! Перезагружаем страницу...', 'success');
                        
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        showNotice('Ошибка: ' + response.data.message, 'error');
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    showNotice('Произошла ошибка при восстановлении снимка', 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            });
        }
        
        /**
         * Удалить снимок
         */
        function deleteSnapshot(snapshotId) {
            const $button = $('.delete-snapshot[data-id="' + snapshotId + '"]');
            const originalText = $button.text();
            
            $button.prop('disabled', true).text('Удаляется...');
            
            $.ajax({
                url: neetrinoReset.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neetrino_delete_snapshot',
                    nonce: neetrinoReset.nonce,
                    snapshot_id: snapshotId
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('Снимок удален успешно!', 'success');
                        
                        // Удаляем строку из таблицы
                        $button.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                            
                            // Если снимков не осталось, показываем пустое состояние
                            if ($('.neetrino-table tbody tr').length === 0) {
                                $('.neetrino-table').replaceWith(
                                    '<p class="neetrino-empty-state">Снимки не найдены. Создайте первый снимок для безопасного тестирования.</p>'
                                );
                            }
                        });
                    } else {
                        showNotice('Ошибка: ' + response.data.message, 'error');
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    showNotice('Произошла ошибка при удалении снимка', 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            });
        }
        
        /**
         * Выполнить частичный сброс
         */
        function performPartialReset(action, $button) {
            const originalText = $button.text();
            
            $button.prop('disabled', true).text('Выполняется...');
            
            $.ajax({
                url: neetrinoReset.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neetrino_partial_reset',
                    nonce: neetrinoReset.nonce,
                    reset_action: action
                },
                success: function(response) {
                    if (response.success) {
                        showNotice(response.data.message, 'success');
                    } else {
                        showNotice('Ошибка: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    showNotice('Произошла ошибка при выполнении операции', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        }
        
        /**
         * Сбросить состояние главной кнопки
         */
        function resetButton() {
            $fullResetButton.removeClass('neetrino-loading').prop('disabled', false);
            $fullResetButton.html('<div class="neetrino-reset-button-content"><div class="neetrino-reset-button-icon"><span class="dashicons dashicons-update-alt"></span></div><div class="neetrino-reset-button-text"><span class="neetrino-reset-button-title">СБРОСИТЬ САЙТ</span><span class="neetrino-reset-button-subtitle">Полный сброс за несколько секунд</span></div></div>');
        }
        
        /**
         * Показать уведомление
         */
        function showNotice(message, type) {
            type = type || 'success';
            
            // Удаляем предыдущие уведомления
            $('.neetrino-notice').remove();
            
            const $notice = $('<div class="neetrino-notice neetrino-notice-' + type + '">' + message + '</div>');
            
            $('.neetrino-content').prepend($notice);
            
            // Прокручиваем к началу страницы
            $('html, body').animate({
                scrollTop: $('.neetrino-header').offset().top - 50
            }, 300);
            
            // Автоматически скрываем уведомление через 5 секунд (кроме ошибок)
            if (type !== 'error') {
                setTimeout(function() {
                    $notice.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        }
        
        /**
         * Анимация счетчика времени
         */
        function startCountdown(seconds) {
            let remaining = seconds;
            const $countdown = $('<span class="countdown"> (' + remaining + 's)</span>');
            
            $fullResetButton.append($countdown);
            
            const timer = setInterval(function() {
                remaining--;
                $countdown.text(' (' + remaining + 's)');
                
                if (remaining <= 0) {
                    clearInterval(timer);
                    $countdown.remove();
                }
            }, 1000);
        }
        
        // Предотвращаем случайное закрытие страницы во время сброса
        let resetInProgress = false;
        
        $fullResetButton.on('click', function() {
            resetInProgress = true;
        });
        
        $(window).on('beforeunload', function(e) {
            if (resetInProgress && $fullResetButton.hasClass('loading')) {
                const message = 'Выполняется сброс сайта. Вы уверены что хотите покинуть страницу?';
                e.returnValue = message;
                return message;
            }
        });
        
    });
    
})(jQuery);

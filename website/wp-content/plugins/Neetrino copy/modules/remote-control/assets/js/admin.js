/**
 * Remote Control Module Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Переключение панели настроек
    $('#toggle-remote-control-settings').on('click', function(e) {
        e.preventDefault();
        
        const content = $('#remote-control-api-examples-content');
        const toggleText = $(this).find('.toggle-text');
        
        if (content.is(':hidden')) {
            content.slideDown(300);
            toggleText.text('Скрыть');
        } else {
            content.slideUp(300);
            toggleText.text('Настройки');
        }
    });
    
    // Функции копирования
    window.remoteControlCopyText = function(btn, text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                showCopySuccess(btn);
            }).catch(function() {
                fallbackCopy(text, btn);
            });
        } else {
            fallbackCopy(text, btn);
        }
    };
    
    window.remoteControlCopyAndHideKey = function(btn, text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                showCopySuccess(btn);
                
                // Отправляем AJAX запрос для очистки временного ключа
                $.ajax({
                    url: remoteControlAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'remote_control_clear_key_transient',
                        nonce: remoteControlAjax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Скрываем блок с ключом через 2 секунды
                            setTimeout(function() {
                                $('#remote-control-key-display').fadeOut(300);
                            }, 2000);
                        }
                    }
                });
            }).catch(function() {
                fallbackCopy(text, btn);
            });
        } else {
            fallbackCopy(text, btn);
        }
    };
    
    // Показать успешное копирование
    function showCopySuccess(btn) {
        const $btn = $(btn);
        const originalHTML = $btn.html();
        
        $btn.html('<span class="dashicons dashicons-yes" style="margin-right: 6px;"></span>Скопировано!');
        $btn.addClass('copied');
        
        // Анимация
        $btn.css('transform', 'scale(1.05)');
        setTimeout(function() {
            $btn.css('transform', 'scale(1)');
        }, 150);
        
        // Восстанавливаем исходный текст через 3 секунды
        setTimeout(function() {
            $btn.html(originalHTML);
            $btn.removeClass('copied');
        }, 3000);
    }
    
    // Fallback для копирования
    function fallbackCopy(text, btn) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        
        try {
            document.execCommand('copy');
            showCopySuccess(btn);
        } catch (err) {
            console.error('Ошибка копирования:', err);
        }
        
        document.body.removeChild(textArea);
    }
    
    // Ручная синхронизация Bitrix24
    $('#manual-sync-btn').on('click', function() {
        const $btn = $(this);
        const originalHTML = $btn.html();
        
        $btn.html('<span class="dashicons dashicons-update"></span> Синхронизация...');
        $btn.prop('disabled', true);
        
        $.ajax({
            url: remoteControlAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'remote_control_manual_sync',
                nonce: remoteControlAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $btn.html('<span class="dashicons dashicons-yes"></span> Синхронизировано!');
                    $btn.removeClass('remote-control-btn-primary').addClass('success');
                    
                    // Показываем уведомление
                    showNotification('Данные успешно отправлены в Bitrix24!', 'success');
                    
                    // Перезагружаем страницу через 2 секунды
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showSyncError($btn, originalHTML, response.data ? response.data.message : 'Неизвестная ошибка');
                }
            },
            error: function() {
                showSyncError($btn, originalHTML, 'Ошибка соединения');
            }
        });
    });
    
    // Показать ошибку синхронизации
    function showSyncError($btn, originalHTML, message) {
        $btn.html('<span class="dashicons dashicons-warning"></span> Ошибка!');
        $btn.addClass('error');
        
        showNotification('Ошибка синхронизации: ' + message, 'error');
        
        setTimeout(function() {
            $btn.html(originalHTML);
            $btn.prop('disabled', false);
            $btn.removeClass('error');
        }, 3000);
    }
    
    // Показать уведомление
    function showNotification(message, type) {
        const notification = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap').prepend(notification);
        
        // Автоматически скрываем через 5 секунд
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Генерация нового ключа через AJAX
    $(document).on('click', '.generate-key-ajax', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const originalHTML = $btn.html();
        
        $btn.html('<span class="dashicons dashicons-update"></span> Создание...');
        $btn.prop('disabled', true);
        
        $.ajax({
            url: remoteControlAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'remote_control_generate_key',
                nonce: remoteControlAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('API ключ успешно создан!', 'success');
                    location.reload();
                } else {
                    showNotification('Ошибка создания ключа: ' + (response.data || 'Неизвестная ошибка'), 'error');
                    $btn.html(originalHTML);
                    $btn.prop('disabled', false);
                }
            },
            error: function() {
                showNotification('Ошибка соединения при создании ключа', 'error');
                $btn.html(originalHTML);
                $btn.prop('disabled', false);
            }
        });
    });
    
    // Держим панель настроек открытой если нужно
    if (window.keepApiPanelOpen) {
        $('#remote-control-api-examples-content').show();
        $('#toggle-remote-control-settings .toggle-text').text('Скрыть');
    }
    
    // Плавное появление элементов
    $('.remote-control-card').each(function(index) {
        $(this).css({
            opacity: 0,
            transform: 'translateY(20px)'
        }).delay(index * 100).animate({
            opacity: 1
        }, 300, function() {
            $(this).css('transform', 'translateY(0)');
        });
    });
});

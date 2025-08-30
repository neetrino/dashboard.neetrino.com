/**
 * Neetrino Admin JavaScript
 * 
 * @package Neetrino
 * @version 1.0.0
 */

jQuery(document).ready(function($) {
    console.log('Neetrino: JavaScript загружен');
    console.log('Neetrino AJAX URL:', neetrino_ajax.ajax_url);
    console.log('Neetrino нашел переключателей:', $('.neetrino-module-toggle').length);
    
    // Обработчик переключения модуля
    $('.neetrino-module-toggle').on('change', function() {
        var moduleSlug = $(this).data('module');
        var isActive = $(this).is(':checked');
        var moduleCard = $(this).closest('.neetrino-module-card');
        
        console.log('Переключение модуля:', moduleSlug, 'активность:', isActive);
        
        // Показываем индикатор загрузки
        moduleCard.addClass('neetrino-loading');
        
        $.ajax({
            url: neetrino_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'neetrino_toggle_module',
                module_slug: moduleSlug,
                active: isActive ? 1 : 0,
                nonce: neetrino_ajax.nonce
            },
            success: function(response) {
                console.log('AJAX ответ:', response);
                moduleCard.removeClass('neetrino-loading');
                
                if (response.success) {
                    if (isActive) {
                        moduleCard.removeClass('inactive');
                    } else {
                        moduleCard.addClass('inactive');
                    }
                    
                    // Показываем уведомление об успехе
                    showNotification(response.data.message, 'success');
                    
                    // Перезагружаем страницу для обновления меню через 1 секунду
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    // В случае ошибки возвращаем переключатель в исходное состояние
                    $(this).prop('checked', !isActive);
                    showNotification('Ошибка: ' + (response.data || 'Неизвестная ошибка'), 'error');
                }
            }.bind(this),
            error: function(xhr, status, error) {
                console.log('AJAX ошибка:', xhr, status, error);
                moduleCard.removeClass('neetrino-loading');
                
                // В случае ошибки возвращаем переключатель в исходное состояние
                $(this).prop('checked', !isActive);
                showNotification('Ошибка при обновлении модуля: ' + error, 'error');
            }.bind(this)
        });
    });
    
    // Функция показа уведомлений
    function showNotification(message, type) {
        var notification = $('<div class="neetrino-notification neetrino-notification-' + type + '">' +
                           '<div class="neetrino-notification-content">' + message + '</div>' +
                           '</div>');
        
        $('body').append(notification);
        
        // Показываем уведомление
        notification.fadeIn(300);
        
        // Скрываем через 3 секунды
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Добавляем стили для уведомлений, если их нет
    if ($('#neetrino-notification-styles').length === 0) {
        $('<style id="neetrino-notification-styles">' +
          '.neetrino-notification { position: fixed; top: 50px; right: 20px; z-index: 9999; padding: 15px 20px; border-radius: 4px; color: white; font-weight: 500; max-width: 300px; display: none; }' +
          '.neetrino-notification-success { background: #46b450; }' +
          '.neetrino-notification-error { background: #dc3232; }' +
          '.neetrino-loading { opacity: 0.7; pointer-events: none; }' +
          '</style>').appendTo('head');
    }
    
    // Documentation Modal Handlers
    $('#neetrino-docs-btn').on('click', function() {
        $('#neetrino-docs-modal').fadeIn(300);
    });
    
    $('#neetrino-docs-close, #neetrino-docs-close-footer').on('click', function() {
        $('#neetrino-docs-modal').fadeOut(300);
    });
    
    // Close modal when clicking outside
    $('#neetrino-docs-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).fadeOut(300);
        }
    });
    
    // Close modal on Escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#neetrino-docs-modal').is(':visible')) {
            $('#neetrino-docs-modal').fadeOut(300);
        }
    });
});

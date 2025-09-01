/**
 * Neetrino Chat Admin JavaScript
 */

jQuery(document).ready(function($) {
    
    // Переключение табов
    $('.neetrino-tab-button').on('click', function() {
        var targetTab = $(this).data('tab');
        
        // Убираем активный класс со всех кнопок и панелей
        $('.neetrino-tab-button').removeClass('active');
        $('.neetrino-tab-panel').removeClass('active');
        
        // Добавляем активный класс к выбранной кнопке и панели
        $(this).addClass('active');
        $('#tab-' + targetTab).addClass('active');
        
        // Сохраняем текущий таб в localStorage
        localStorage.setItem('neetrino_chat_active_tab', targetTab);
    });
    
    // Восстанавливаем активный таб при загрузке страницы
    var activeTab = localStorage.getItem('neetrino_chat_active_tab');
    if (activeTab) {
        $('.neetrino-tab-button[data-tab="' + activeTab + '"]').click();
    }
    
    // Улучшенная работа с формой
    var form = $('.neetrino-tab-content form');
    
    // Убрать индикатор при сохранении
    form.on('submit', function() {
        $('.neetrino-unsaved-indicator').remove();
    });
    
    // Подсветка активных полей
    form.find('input, select, textarea').on('focus', function() {
        $(this).closest('.neetrino-form-group').addClass('focused');
    }).on('blur', function() {
        $(this).closest('.neetrino-form-group').removeClass('focused');
    });
    
    // Валидация полей в реальном времени
    form.find('input[type="tel"]').on('input', function() {
        var value = $(this).val();
        var phonePattern = /^[\+]?[1-9][\d]{0,15}$/;
        
        if (value && !phonePattern.test(value.replace(/[\s\-\(\)]/g, ''))) {
            $(this).addClass('error');
            showFieldError($(this), 'Неверный формат номера телефона');
        } else {
            $(this).removeClass('error');
            hideFieldError($(this));
        }
    });
    
    form.find('input[type="email"]').on('input', function() {
        var value = $(this).val();
        var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (value && !emailPattern.test(value)) {
            $(this).addClass('error');
            showFieldError($(this), 'Неверный формат email адреса');
        } else {
            $(this).removeClass('error');
            hideFieldError($(this));
        }
    });
    
    // Показать ошибку поля
    function showFieldError(field, message) {
        var errorDiv = field.siblings('.field-error');
        if (!errorDiv.length) {
            errorDiv = $('<div class="field-error" style="color: #e74c3c; font-size: 12px; margin-top: 5px;"></div>');
            field.after(errorDiv);
        }
        errorDiv.text(message);
    }
    
    // Скрыть ошибку поля
    function hideFieldError(field) {
        field.siblings('.field-error').remove();
    }
    
    // Копирование настроек
    $('.copy-settings').on('click', function() {
        var settings = {};
        form.find('input, select, textarea').each(function() {
            var field = $(this);
            var name = field.attr('name');
            var value = field.val();
            
            if (field.attr('type') === 'checkbox') {
                value = field.is(':checked');
            }
            
            if (name) {
                settings[name] = value;
            }
        });
        
        // Копируем в буфер обмена
        navigator.clipboard.writeText(JSON.stringify(settings, null, 2)).then(function() {
            showNotification('Настройки скопированы в буфер обмена', 'success');
        }).catch(function() {
            showNotification('Не удалось скопировать настройки', 'error');
        });
    });
    
    // Показать уведомление
    function showNotification(message, type) {
        var notification = $('<div class="neetrino-notification neetrino-notification-' + type + '">' + message + '</div>');
        $('body').append(notification);
        
        setTimeout(function() {
            notification.addClass('show');
        }, 100);
        
        setTimeout(function() {
            notification.removeClass('show');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 3000);
    }
    
    // Подсказки для полей
    $('[data-tooltip]').each(function() {
        var tooltip = $(this).data('tooltip');
        $(this).attr('title', tooltip);
    });
    
    // Превью цвета
    $('input[type="color"]').on('change', function() {
        var color = $(this).val();
        $(this).css('border-color', color);
        
        // Показываем превью
        var preview = $(this).siblings('.color-preview');
        if (!preview.length) {
            preview = $('<div class="color-preview" style="display: inline-block; width: 20px; height: 20px; border-radius: 50%; margin-left: 10px; vertical-align: middle;"></div>');
            $(this).after(preview);
        }
        preview.css('background-color', color);
    });
    
    // Инициализация превью цвета
    $('input[type="color"]').trigger('change');
    
    // Управление ползунками позиции
    $('.neetrino-position-slider').on('input', function() {
        var value = $(this).val();
        var id = $(this).attr('id');
        
        // Обновляем значение
        $('#' + id + '_value').text(value + '%');
        
        // Обновляем превью
        if (id.includes('mobile')) {
            updateMobilePositionPreview();
        } else {
            updatePositionPreview();
        }
    });
    
    // Функция обновления превью позиции (десктоп)
    function updatePositionPreview() {
        var x = $('#position_x').val();
        var y = $('#position_y').val();
        
        $('#preview_widget').css({
            'left': x + '%',
            'top': y + '%'
        });
    }
    
    // Функция обновления превью позиции (мобильная)
    function updateMobilePositionPreview() {
        var x = $('#mobile_position_x').val() || 90; // Значение по умолчанию
        var y = $('#mobile_position_y').val() || 90; // Значение по умолчанию
        
        // Для мобильного превью нужно учитывать границы экрана телефона
        // Экран телефона имеет отступы 8px с каждой стороны
        var phoneScreenLeft = 8;
        var phoneScreenTop = 8;
        var phoneScreenRight = 8;
        var phoneScreenBottom = 8;
        
        // Вычисляем позицию внутри экрана телефона
        var phoneWidth = 160 - phoneScreenLeft - phoneScreenRight; // 144px
        var phoneHeight = 320 - phoneScreenTop - phoneScreenBottom; // 304px
        
        // Позиция виджета внутри экрана телефона
        var widgetX = phoneScreenLeft + (phoneWidth * x / 100);
        var widgetY = phoneScreenTop + (phoneHeight * y / 100);
        
        var mobileWidget = $('#mobile_preview_widget');
        if (mobileWidget.length) {
            mobileWidget.css({
                'left': widgetX + 'px',
                'top': widgetY + 'px',
                'transform': 'translate(-50%, -50%)',
                'z-index': '10'
            });
        }
    }
    
    // Инициализация превью при загрузке
    updatePositionPreview();
    
    // Инициализация мобильного превью
    if ($('#mobile_position_x').length && $('#mobile_position_y').length) {
        updateMobilePositionPreview();
    }
    
    // Кнопки пресетов позиции
    $('.preset-btn').on('click', function() {
        var position = $(this).data('position');
        var x = 90, y = 90; // По умолчанию
        var isMobile = $(this).hasClass('mobile-preset');
        
        switch(position) {
            case 'top-left': x = 10; y = 10; break;
            case 'top-center': x = 50; y = 10; break;
            case 'top-right': x = 90; y = 10; break;
            case 'middle-left': x = 10; y = 50; break;
            case 'middle-center': x = 50; y = 50; break;
            case 'middle-right': x = 90; y = 50; break;
            case 'bottom-left': x = 10; y = 90; break;
            case 'bottom-center': x = 50; y = 90; break;
            case 'bottom-right': x = 90; y = 90; break;
        }
        
        if (isMobile) {
            // Обновляем мобильные слайдеры
            $('#mobile_position_x').val(x).trigger('input');
            $('#mobile_position_y').val(y).trigger('input');
            $('#mobile_position_x_value').text(x + '%');
            $('#mobile_position_y_value').text(y + '%');
            
            // Обновляем мобильный превью
            updateMobilePositionPreview();
            
            // Добавляем анимацию
            $('#mobile_preview_widget').addClass('pulse');
            setTimeout(function() {
                $('#mobile_preview_widget').removeClass('pulse');
            }, 2000);
        } else {
            // Обновляем десктопные слайдеры
            $('#position_x').val(x).trigger('input');
            $('#position_y').val(y).trigger('input');
            $('#position_x_value').text(x + '%');
            $('#position_y_value').text(y + '%');
            
            // Обновляем десктопный превью
            updatePositionPreview();
            
            // Добавляем анимацию
            $('#preview_widget').addClass('pulse');
            setTimeout(function() {
                $('#preview_widget').removeClass('pulse');
            }, 2000);
        }
    });
    
    // Анимация превью при изменении пульсации
    $('input[name="pulse_effect"]').on('change', function() {
        if ($(this).is(':checked')) {
            $('#preview_widget').addClass('pulse');
        } else {
            $('#preview_widget').removeClass('pulse');
        }
    });
    
    // Инициализация пульсации
    if ($('input[name="pulse_effect"]').is(':checked')) {
        $('#preview_widget').addClass('pulse');
    }
    
    // Сброс к стандартным позициям
    $('.reset-position').on('click', function() {
        $('#position_x').val(95);
        $('#position_y').val(85);
        $('#position_x_value').text('95%');
        $('#position_y_value').text('85%');
        updatePositionPreview();
    });
    
    // Счетчики символов для текстовых полей
    $('input[type="text"], input[type="tel"], input[type="email"], textarea').each(function() {
        var field = $(this);
        var maxLength = field.attr('maxlength');
        
        if (maxLength) {
            var counter = $('<div class="char-counter" style="font-size: 11px; color: #999; margin-top: 2px;"></div>');
            field.after(counter);
            
            function updateCounter() {
                var length = field.val().length;
                counter.text(length + ' / ' + maxLength);
                
                if (length > maxLength * 0.9) {
                    counter.css('color', '#e74c3c');
                } else if (length > maxLength * 0.7) {
                    counter.css('color', '#f39c12');
                } else {
                    counter.css('color', '#999');
                }
            }
            
            field.on('input', updateCounter);
            updateCounter();
        }
    });
    
});

// Дополнительные стили для уведомлений
var notificationStyles = `
<style>
.neetrino-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 4px;
    color: white;
    font-size: 14px;
    z-index: 10000;
    transform: translateX(100%);
    transition: transform 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.neetrino-notification.show {
    transform: translateX(0);
}

.neetrino-notification-success {
    background: #2ecc71;
}

.neetrino-notification-error {
    background: #e74c3c;
}

.neetrino-form-group.focused {
    background: rgba(46, 204, 113, 0.05);
    border-radius: 4px;
    padding: 10px;
    margin: 5px -10px;
}

.neetrino-form-group input.error,
.neetrino-form-group select.error {
    border-color: #e74c3c;
    box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.1);
}
</style>
`;

jQuery(document).ready(function($) {
    $('head').append(notificationStyles);
    
    // Обработка кнопок управления цветом
    $('#apply-brand-color').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var channelType = button.data('channel');
        var brandColor = button.data('color');
        
        // Сразу обновляем color picker
        $('#color').val(brandColor).trigger('change');
        updateColorPreview(brandColor);
        
        // Анимация кнопки
        button.addClass('loading').prop('disabled', true);
        
        $.ajax({
            url: neetrino_chat_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'neetrino_chat_apply_brand_color',
                nonce: neetrino_chat_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Показываем успешное действие
                    button.removeClass('loading').addClass('success');
                    
                    // Показываем уведомление
                    showModernNotification('Фирменный цвет ' + channelType + ' применен!', 'success', brandColor);
                    
                    // Обновляем все связанные элементы
                    updateAllColorElements(response.data.color);
                    
                    // Возвращаем кнопку в нормальное состояние
                    setTimeout(function() {
                        button.removeClass('success');
                    }, 2000);
                } else {
                    showModernNotification('Ошибка: ' + response.data.message, 'error');
                    button.removeClass('loading');
                }
            },
            error: function() {
                showModernNotification('Ошибка соединения', 'error');
                button.removeClass('loading');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
    
    $('#reset-default-color').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var defaultColor = '#2ecc71';
        
        // Сразу обновляем color picker
        $('#color').val(defaultColor).trigger('change');
        updateColorPreview(defaultColor);
        
        // Анимация кнопки
        button.addClass('loading').prop('disabled', true);
        
        $.ajax({
            url: neetrino_chat_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'neetrino_chat_reset_default_color',
                nonce: neetrino_chat_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Показываем успешное действие
                    button.removeClass('loading').addClass('success');
                    
                    // Показываем уведомление
                    showModernNotification('Цвет сброшен к дефолтному!', 'success', defaultColor);
                    
                    // Обновляем все связанные элементы
                    updateAllColorElements(defaultColor);
                    
                    // Возвращаем кнопку в нормальное состояние
                    setTimeout(function() {
                        button.removeClass('success');
                    }, 2000);
                } else {
                    showModernNotification('Ошибка: ' + response.data.message, 'error');
                    button.removeClass('loading');
                }
            },
            error: function() {
                showModernNotification('Ошибка соединения', 'error');
                button.removeClass('loading');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
    
    // Функция обновления превью цвета
    function updateColorPreview(color) {
        // Обновляем все превью цветов на странице
        $('.color-preview').css('background-color', color);
        
        // Добавляем CSS переменную для динамического использования
        $(':root').css('--current-color', color);
        
        // Обновляем CSS переменную для фирменного цвета
        $('#apply-brand-color').css('--brand-color', color);
        
        // Анимация изменения цвета
        $('#color').css('transform', 'scale(1.1)');
        setTimeout(function() {
            $('#color').css('transform', 'scale(1)');
        }, 200);
    }
    
    // Функция обновления всех элементов цвета
    function updateAllColorElements(color) {
        updateColorPreview(color);
        
        // Обновляем поле выбора цвета
        $('#color').val(color);
        
        // Добавляем пульсацию к полю цвета
        $('#color').css({
            'box-shadow': '0 0 0 3px ' + hexToRgba(color, 0.3),
            'transform': 'scale(1.05)'
        });
        
        setTimeout(function() {
            $('#color').css({
                'box-shadow': '',
                'transform': ''
            });
        }, 1000);
    }
    
    // Современное уведомление
    function showModernNotification(message, type, color) {
        // Удаляем старые уведомления
        $('.neetrino-modern-notification').remove();
        
        var bgColor = '#2ecc71';
        var icon = '✓';
        
        if (type === 'error') {
            bgColor = '#e74c3c';
            icon = '✗';
        } else if (color) {
            bgColor = color;
        }
        
        var notification = $('<div class="neetrino-modern-notification">')
            .html('<div class="notification-icon">' + icon + '</div><div class="notification-message">' + message + '</div>')
            .css({
                'position': 'fixed',
                'top': '20px',
                'right': '20px',
                'background': 'linear-gradient(135deg, ' + bgColor + ' 0%, ' + darkenColor(bgColor, 15) + ' 100%)',
                'color': 'white',
                'padding': '16px 24px',
                'border-radius': '12px',
                'box-shadow': '0 8px 25px rgba(0,0,0,0.15), 0 4px 12px ' + hexToRgba(bgColor, 0.3),
                'z-index': '100000',
                'font-weight': '600',
                'font-size': '14px',
                'transform': 'translateX(400px) scale(0.8)',
                'transition': 'all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)',
                'display': 'flex',
                'align-items': 'center',
                'gap': '12px',
                'backdrop-filter': 'blur(10px)',
                'border': '1px solid rgba(255,255,255,0.2)'
            });
        
        // Стили для иконки
        notification.find('.notification-icon').css({
            'background': 'rgba(255,255,255,0.2)',
            'border-radius': '50%',
            'width': '24px',
            'height': '24px',
            'display': 'flex',
            'align-items': 'center',
            'justify-content': 'center',
            'font-size': '12px',
            'font-weight': 'bold'
        });
        
        $('body').append(notification);
        
        // Анимация появления
        setTimeout(function() {
            notification.css({
                'transform': 'translateX(0) scale(1)'
            });
        }, 100);
        
        // Автоматическое скрытие
        setTimeout(function() {
            notification.css({
                'transform': 'translateX(400px) scale(0.8)',
                'opacity': '0'
            });
            setTimeout(function() {
                notification.remove();
            }, 400);
        }, 3500);
        
        // Клик для закрытия
        notification.on('click', function() {
            $(this).css({
                'transform': 'translateX(400px) scale(0.8)',
                'opacity': '0'
            });
            setTimeout(function() {
                notification.remove();
            }, 400);
        });
    }
    
    // Вспомогательные функции
    function darkenColor(color, percent) {
        const num = parseInt(color.replace("#", ""), 16);
        const amt = Math.round(2.55 * percent);
        const R = Math.max(0, Math.min(255, (num >> 16) - amt));
        const G = Math.max(0, Math.min(255, (num >> 8 & 0x00FF) - amt));
        const B = Math.max(0, Math.min(255, (num & 0x0000FF) - amt));
        
        return "#" + (0x1000000 + R * 0x10000 + G * 0x100 + B).toString(16).slice(1);
    }
    
    function hexToRgba(hex, alpha) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
    }
    
    // Отслеживание изменений в color picker
    $('#color').on('change input', function() {
        var newColor = $(this).val();
        updateColorPreview(newColor);
    });
    
    // Инициализация при загрузке
    $(document).ready(function() {
        // Устанавливаем начальный цвет
        var initialColor = $('#color').val();
        if (initialColor) {
            updateColorPreview(initialColor);
        }
        
        // Добавляем красивые эффекты при наведении на кнопки
        $('.neetrino-color-buttons .button').hover(
            function() {
                $(this).find('.color-preview').css('transform', 'scale(1.2) rotate(5deg)');
            },
            function() {
                $(this).find('.color-preview').css('transform', 'scale(1) rotate(0deg)');
            }
        );
    });
});

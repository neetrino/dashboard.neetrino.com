jQuery(document).ready(function($) {
    'use strict';

    // Элементы формы
    const form = $('#menu-hierarchy-form');
    const button = $('#process-hierarchy');
    const spinner = $('.menu-hierarchy-actions .spinner');
    const resultDiv = $('#hierarchy-result');
    const menuSelect = $('#menu_select');
    
    // Элементы справки
    const helpToggle = $('#toggle-help');
    const helpSection = $('#help-section');
    
    // Переключатель справки
    helpToggle.on('click', function(e) {
        e.preventDefault();
        
        if (helpSection.is(':visible')) {
            helpSection.slideUp(300);
            $(this).removeClass('active');
        } else {
            helpSection.slideDown(300);
            $(this).addClass('active');
        }
    });
    
    // Обработка отправки формы
    form.on('submit', function(e) {
        e.preventDefault();
        
        const menuId = menuSelect.val();
        
        // Проверяем, выбрано ли меню
        if (!menuId) {
            showResult('error', menuHierarchy.messages.selectMenu);
            return;
        }
        
        // Начинаем обработку
        startProcessing();
        
        // AJAX запрос
        $.ajax({
            url: menuHierarchy.ajaxUrl,
            type: 'POST',
            data: {
                action: 'process_menu_hierarchy',
                menu_id: menuId,
                nonce: menuHierarchy.nonce
            },
            success: function(response) {
                if (response.success) {
                    showResult('success', response.data.message);
                    
                    // Если есть информация о количестве обновленных элементов
                    if (response.data.updated_items !== undefined) {
                        console.log('Обновлено пунктов меню:', response.data.updated_items);
                        
                        // Если есть отладочная информация, выводим в консоль
                        if (response.data.debug_info && response.data.debug_info.length > 0) {
                            console.log('Детали изменений:', response.data.debug_info);
                        }
                    }
                } else {
                    showResult('error', response.data.message || menuHierarchy.messages.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showResult('error', menuHierarchy.messages.error + ' (' + error + ')');
            },
            complete: function() {
                stopProcessing();
            }
        });
    });
    
    // Функция начала обработки
    function startProcessing() {
        button.prop('disabled', true).text(menuHierarchy.messages.processing);
        spinner.addClass('is-active');
        resultDiv.hide();
    }
    
    // Функция окончания обработки
    function stopProcessing() {
        button.prop('disabled', false).text('Настроить иерархию');
        spinner.removeClass('is-active');
    }
    
    // Функция показа результата
    function showResult(type, message) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        
        resultDiv.html(
            '<div class="notice ' + noticeClass + ' inline">' +
                '<p>' + message + '</p>' +
            '</div>'
        ).show();
        
        // Плавная прокрутка к результату
        $('html, body').animate({
            scrollTop: resultDiv.offset().top - 100
        }, 500);
        
        // Автоматически скрываем сообщение об успехе через 10 секунд
        if (type === 'success') {
            setTimeout(function() {
                resultDiv.fadeOut(500);
            }, 10000);
        }
    }
    
    // Сброс результата при изменении выбора меню
    menuSelect.on('change', function() {
        resultDiv.hide();
    });
});

/* Neetrino App Manager - Modern Admin JavaScript */

jQuery(document).ready(function($) {
    
    // ===========================
    // СИСТЕМА ТАБОВ
    // ===========================
    
    // Инициализация табов
    initTabSystem();
    
    function initTabSystem() {
        // Обработчик переключения табов
        $('.neetrino-tab-button').on('click', function(e) {
            e.preventDefault();
            
            const targetTab = $(this).data('tab');
            const $tabContainer = $(this).closest('.neetrino-tabs-container');
            
            // Удаляем активный класс со всех кнопок и панелей
            $tabContainer.find('.neetrino-tab-button').removeClass('active');
            $tabContainer.find('.neetrino-tab-panel').removeClass('active').hide();
            
            // Добавляем активный класс к выбранной кнопке и панели
            $(this).addClass('active');
            $tabContainer.find('#tab-' + targetTab).addClass('active').show();
        });
        
        // Показываем первый таб по умолчанию, если нет активного
        if ($('.neetrino-tab-button.active').length === 0) {
            $('.neetrino-tab-button:first').addClass('active');
            $('.neetrino-tab-panel:first').addClass('active');
        }
    }
    
    // ===========================
    // ВИЗУАЛЬНАЯ ОБРАТНАЯ СВЯЗЬ ПОЛЕЙ
    // ===========================
    
    function updateFieldStatus() {
        $('input[type="text"], input[type="email"], textarea').each(function() {
            const $field = $(this);
            const value = $field.val().trim();
            
            if (value) {
                $field.removeClass('neetrino-empty').addClass('neetrino-filled');
            } else {
                $field.removeClass('neetrino-filled').addClass('neetrino-empty');
            }
        });
    }
    
    function initFieldTracking() {
        // Инициализация состояния полей
        updateFieldStatus();
        
        // Отслеживание изменений в полях
        $('input[type="text"], input[type="email"], textarea').on('input blur keyup', function() {
            updateFieldStatus();
        });
    }
    
    // ===========================
    // ОСНОВНАЯ ИНИЦИАЛИЗАЦИЯ
    // ===========================
    
    // Инициализация
    initConditionalFields();
    initCheckboxCards();
    initFieldTracking();
    
    // Обработчики событий
    $('#app-manager-settings-form').on('submit', handleSaveSettings);
    $('#create-privacy-page').on('click', handleCreatePrivacyPage);
    $('#delete-privacy-page').on('click', handleDeletePrivacyPage);
    $('#preview-privacy').on('click', handlePreviewPrivacy);
    $('.neetrino-modal-close, .neetrino-modal-backdrop').on('click', closeModal);
    $('.neetrino-copy-btn').on('click', handleCopyUrl);
    
    /**
     * Инициализация условных полей
     */
    function initConditionalFields() {
        // Показ/скрытие блоков в зависимости от переключателей
        $('input[name="collects_personal_data"], input[name="uses_analytics"]').on('change', function() {
            const fieldName = $(this).attr('name');
            const $conditionalBlock = $('[data-condition="' + fieldName + '"]');
            
            if ($(this).is(':checked')) {
                $conditionalBlock.addClass('active').slideDown(300);
            } else {
                $conditionalBlock.removeClass('active').slideUp(300);
            }
        });
        
        // Инициализация при загрузке
        $('input[name="collects_personal_data"]:checked, input[name="uses_analytics"]:checked').trigger('change');
    }
    
    /**
     * Инициализация карточек чекбоксов
     */
    function initCheckboxCards() {
        $('.neetrino-checkbox-card input[type="checkbox"]').on('change', function() {
            const $card = $(this).closest('.neetrino-checkbox-card');
            
            if ($(this).is(':checked')) {
                $card.addClass('checked');
            } else {
                $card.removeClass('checked');
            }
        });
        
        // Инициализация при загрузке
        $('.neetrino-checkbox-card input[type="checkbox"]:checked').each(function() {
            $(this).closest('.neetrino-checkbox-card').addClass('checked');
        });
    }
    
    /**
     * Копирование URL
     */
    function handleCopyUrl() {
        const url = $(this).data('url');
        const $button = $(this);
        const originalIcon = $button.html();
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function() {
                showCopySuccess($button);
            }).catch(function() {
                fallbackCopy(url, $button);
            });
        } else {
            fallbackCopy(url, $button);
        }
        
        function fallbackCopy(text, $btn) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            
            try {
                document.execCommand('copy');
                showCopySuccess($btn);
            } catch (err) {
                showNotification('Ошибка копирования', 'error');
            }
            
            document.body.removeChild(textArea);
        }
        
        function showCopySuccess($btn) {
            $btn.html('<i class="fa-solid fa-check"></i>');
            showNotification('URL скопирован в буфер обмена', 'success');
            
            setTimeout(function() {
                $btn.html(originalIcon);
            }, 2000);
        }
    }
    
    /**
     * Сохранение настроек
     */
    function handleSaveSettings(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $button = $form.find('button[type="submit"]');
        const originalText = $button.html();
        
        // Показываем индикатор загрузки
        $button.html('<i class="fa-solid fa-spinner fa-spin"></i> ' + neetrino_app_manager.strings.saving)
               .prop('disabled', true)
               .addClass('loading');
        
        // Подготавливаем данные
        const formData = new FormData($form[0]);
        formData.append('action', 'neetrino_app_save_settings');
        
        // AJAX запрос
        $.ajax({
            url: neetrino_app_manager.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    
                    // Добавляем эффект успеха
                    $button.removeClass('loading')
                           .addClass('success')
                           .html('<i class="fa-solid fa-check"></i> ' + neetrino_app_manager.strings.success);
                    
                    // Обновляем страницу через 2 секунды
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    showNotification(response.data.message || neetrino_app_manager.strings.error, 'error');
                    $button.html(originalText).prop('disabled', false).removeClass('loading');
                }
            },
            error: function() {
                showNotification(neetrino_app_manager.strings.error, 'error');
                $button.html(originalText).prop('disabled', false).removeClass('loading');
            }
        });
    }
    
    /**
     * Создание страницы Privacy Policy
     */
    function handleCreatePrivacyPage() {
        const $button = $(this);
        const originalText = $button.html();
        
        $button.html('<i class="fa-solid fa-spinner fa-spin"></i> ' + neetrino_app_manager.strings.creating)
               .prop('disabled', true)
               .addClass('loading');
        
        $.ajax({
            url: neetrino_app_manager.ajax_url,
            type: 'POST',
            data: {
                action: 'neetrino_app_create_privacy_page',
                nonce: neetrino_app_manager.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    
                    $button.removeClass('loading')
                           .addClass('success')
                           .html('<i class="fa-solid fa-check"></i> ' + neetrino_app_manager.strings.success);
                    
                    // Обновляем страницу через 2 секунды
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    showNotification(response.data.message || neetrino_app_manager.strings.error, 'error');
                    $button.html(originalText).prop('disabled', false).removeClass('loading');
                }
            },
            error: function() {
                showNotification(neetrino_app_manager.strings.error, 'error');
                $button.html(originalText).prop('disabled', false).removeClass('loading');
            }
        });
    }
    
    /**
     * Удаление страницы Privacy Policy
     */
    function handleDeletePrivacyPage() {
        // Современное подтверждение
        showConfirmDialog(
            'Удаление страницы',
            'Вы уверены, что хотите удалить страницу Privacy Policy? Это действие нельзя отменить.',
            'Удалить',
            'Отмена'
        ).then(function(confirmed) {
            if (!confirmed) return;
            
            const $button = $('#delete-privacy-page');
            const originalText = $button.html();
            
            $button.html('<i class="fa-solid fa-spinner fa-spin"></i> ' + neetrino_app_manager.strings.deleting)
                   .prop('disabled', true)
                   .addClass('loading');
            
            $.ajax({
                url: neetrino_app_manager.ajax_url,
                type: 'POST',
                data: {
                    action: 'neetrino_app_delete_privacy_page',
                    nonce: neetrino_app_manager.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotification(response.data.message, 'success');
                        
                        // Обновляем страницу через 1 секунду
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showNotification(response.data.message || neetrino_app_manager.strings.error, 'error');
                        $button.html(originalText).prop('disabled', false).removeClass('loading');
                    }
                },
                error: function() {
                    showNotification(neetrino_app_manager.strings.error, 'error');
                    $button.html(originalText).prop('disabled', false).removeClass('loading');
                }
            });
        });
    }
    
    /**
     * Предпросмотр Privacy Policy
     */
    function handlePreviewPrivacy() {
        // Сначала сохраняем текущие настройки для предпросмотра
        const formData = new FormData($('#app-manager-settings-form')[0]);
        formData.append('action', 'neetrino_app_preview_privacy');
        
        // Показываем загрузку в модальном окне
        $('#privacy-preview-content').html(`
            <div style="text-align: center; padding: 40px;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 32px; color: #17a2b8; margin-bottom: 16px;"></i>
                <p>Генерация предпросмотра...</p>
            </div>
        `);
        $('#privacy-preview-modal').show();
        
        $.ajax({
            url: neetrino_app_manager.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#privacy-preview-content').html(response.data.content);
                } else {
                    $('#privacy-preview-content').html(`
                        <div style="text-align: center; padding: 40px; color: #e53e3e;">
                            <i class="fa-solid fa-exclamation-triangle" style="font-size: 32px; margin-bottom: 16px;"></i>
                            <p>${response.data.message || neetrino_app_manager.strings.error}</p>
                        </div>
                    `);
                }
            },
            error: function() {
                $('#privacy-preview-content').html(`
                    <div style="text-align: center; padding: 40px; color: #e53e3e;">
                        <i class="fa-solid fa-exclamation-triangle" style="font-size: 32px; margin-bottom: 16px;"></i>
                        <p>${neetrino_app_manager.strings.error}</p>
                    </div>
                `);
            }
        });
    }
    
    /**
     * Закрытие модального окна
     */
    function closeModal() {
        $('.neetrino-modern-modal').fadeOut(200);
    }
    
    /**
     * Современный диалог подтверждения
     */
    function showConfirmDialog(title, message, confirmText, cancelText) {
        return new Promise(function(resolve) {
            const dialogHtml = `
                <div class="neetrino-confirm-dialog">
                    <div class="neetrino-modal-backdrop"></div>
                    <div class="neetrino-confirm-container">
                        <div class="neetrino-confirm-header">
                            <h3>${title}</h3>
                        </div>
                        <div class="neetrino-confirm-body">
                            <p>${message}</p>
                        </div>
                        <div class="neetrino-confirm-actions">
                            <button class="neetrino-btn neetrino-btn-outline neetrino-confirm-cancel">${cancelText}</button>
                            <button class="neetrino-btn neetrino-btn-danger neetrino-confirm-ok">${confirmText}</button>
                        </div>
                    </div>
                </div>
            `;
            
            const $dialog = $(dialogHtml).appendTo('body');
            
            $dialog.find('.neetrino-confirm-ok').on('click', function() {
                $dialog.remove();
                resolve(true);
            });
            
            $dialog.find('.neetrino-confirm-cancel, .neetrino-modal-backdrop').on('click', function() {
                $dialog.remove();
                resolve(false);
            });
            
            // Добавляем стили для диалога если их нет
            if (!$('#neetrino-confirm-styles').length) {
                $('<style id="neetrino-confirm-styles">').html(`
                    .neetrino-confirm-dialog {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        z-index: 100001;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    .neetrino-confirm-container {
                        position: relative;
                        background: white;
                        border-radius: 16px;
                        max-width: 400px;
                        width: 90%;
                        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                        animation: modalSlideIn 0.2s ease-out;
                    }
                    .neetrino-confirm-header {
                        padding: 24px 24px 0 24px;
                    }
                    .neetrino-confirm-header h3 {
                        margin: 0;
                        font-size: 18px;
                        font-weight: 600;
                        color: #1a202c;
                    }
                    .neetrino-confirm-body {
                        padding: 16px 24px 24px 24px;
                    }
                    .neetrino-confirm-body p {
                        margin: 0;
                        color: #4a5568;
                        line-height: 1.5;
                    }
                    .neetrino-confirm-actions {
                        padding: 0 24px 24px 24px;
                        display: flex;
                        gap: 12px;
                        justify-content: flex-end;
                    }
                `).appendTo('head');
            }
        });
    }
    
    /**
     * Показ уведомлений
     */
    function showNotification(message, type) {
        // Удаляем предыдущие уведомления
        $('.neetrino-toast').remove();
        
        const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        const bgColor = type === 'success' ? '#48bb78' : '#e53e3e';
        
        const $toast = $(`
            <div class="neetrino-toast" style="
                position: fixed;
                top: 32px;
                right: 32px;
                background: ${bgColor};
                color: white;
                padding: 16px 24px;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
                z-index: 100002;
                display: flex;
                align-items: center;
                gap: 12px;
                font-weight: 500;
                max-width: 400px;
                animation: toastSlideIn 0.3s ease-out;
            ">
                <i class="fa-solid ${iconClass}"></i>
                <span>${message}</span>
            </div>
        `);
        
        // Добавляем анимацию если её нет
        if (!$('#neetrino-toast-styles').length) {
            $('<style id="neetrino-toast-styles">').html(`
                @keyframes toastSlideIn {
                    from {
                        opacity: 0;
                        transform: translateX(100px);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }
                @keyframes toastSlideOut {
                    from {
                        opacity: 1;
                        transform: translateX(0);
                    }
                    to {
                        opacity: 0;
                        transform: translateX(100px);
                    }
                }
            `).appendTo('head');
        }
        
        $toast.appendTo('body');
        
        // Автоматическое скрытие через 4 секунды
        setTimeout(function() {
            $toast.css('animation', 'toastSlideOut 0.3s ease-out forwards');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 4000);
    }
    
    /**
     * Обработка клавиши Escape
     */
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
            $('.neetrino-confirm-dialog').remove();
        }
    });
});

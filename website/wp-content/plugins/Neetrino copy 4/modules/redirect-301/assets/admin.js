/* Redirect 301 Module Admin JavaScript */

jQuery(document).ready(function($) {
    var ruleIndex = parseInt($('#country-rules-container .country-rule-compact').length) || 0;
    
    // Инициализация состояния модального окна настроек при загрузке страницы
    function initializeSettingsModal() {
        var excludeAdmin = $('#hidden_exclude_admin_users').val() === '1';
        var enableLogging = $('#hidden_enable_logging').val() === '1';
        
        $('#settings-modal input[name="exclude_admin_users"]').prop('checked', excludeAdmin);
        $('#settings-modal input[name="enable_logging"]').prop('checked', enableLogging);
    }
    
    // Вызываем инициализацию
    initializeSettingsModal();
    
    // Список стран для добавления новых правил
    var countries = {
        'RU': 'Россия',
        'UA': 'Украина',
        'BY': 'Беларусь',
        'KZ': 'Казахстан',
        'AM': 'Армения',
        'GE': 'Грузия',
        'US': 'США',
        'GB': 'Великобритания',
        'DE': 'Германия',
        'FR': 'Франция',
        'ES': 'Испания',
        'IT': 'Италия',
        'CN': 'Китай',
        'JP': 'Япония',
        'KR': 'Южная Корея',
        'BR': 'Бразилия',
        'IN': 'Индия',
        'TR': 'Турция',
        'PL': 'Польша',
        'NL': 'Нидерланды'
    };    // Добавление нового правила для страны
    $('#add-country-rule').click(function() {
        var countryOptions = '';
        $.each(countries, function(code, name) {
            countryOptions += `<option value="${code}">${name} (${code})</option>`;
        });
        
        var newRule = `
            <div class="country-rule-compact" data-index="${ruleIndex}">
                <select name="country_rules[${ruleIndex}][country]" class="country-select-compact">
                    <option value="">Выберите страну</option>
                    ${countryOptions}
                </select>
                
                <div class="action-buttons-compact">
                    <button type="button" 
                            class="action-btn-compact stay-btn active" 
                            data-value="stay"
                            data-index="${ruleIndex}">
                        <span class="btn-icon">🏠</span>
                        <span class="btn-text">Остаются</span>
                    </button>                        <div class="redirect-btn-wrapper">
                            <button type="button" 
                                    class="action-btn-compact redirect-btn-compact" 
                                    data-value="redirect"
                                    data-index="${ruleIndex}">
                                <span class="btn-icon">🔀</span>
                                <span class="btn-text">Перенаправить на:</span>
                            </button>                            <div class="url-input-wrapper hidden">
                                <span class="protocol-inline">URL:</span>
                                <input type="text" 
                                       name="country_rules[${ruleIndex}][url]" 
                                       value=""
                                       placeholder="https://example.com"
                                       class="url-input-field"
                                       data-full-url="">
                            </div>
                        </div>
                    
                    <input type="hidden" 
                           name="country_rules[${ruleIndex}][action]" 
                           value="stay" 
                           class="country-action-input">
                </div>
                
                <button type="button" class="delete-rule-btn" title="Удалить правило">
                    <span class="trash-icon">🗑️</span>
                </button>
            </div>
        `;
        
        $('#country-rules-container').append(newRule);
        ruleIndex++;
    });
      // Удаление правила
    $(document).on('click', '.delete-rule-btn', function() {
        $(this).closest('.country-rule-compact').remove();
    });        // Управление компактными кнопками-переключателями для правил стран
    $(document).on('click', '.action-btn-compact', function() {
        var $btn = $(this);
        var value = $btn.data('value');
        var index = $btn.data('index');
        var $container = $btn.closest('.country-rule-compact');
        
        // Убираем активный класс со всех кнопок в этом контейнере
        $container.find('.action-btn-compact').removeClass('active');
        $container.find('.redirect-btn-wrapper').removeClass('active');
        
        // Добавляем активный класс к выбранной кнопке
        $btn.addClass('active');
        
        // Управляем показом URL поля
        var $urlWrapper = $container.find('.url-input-wrapper');
        if (value === 'redirect') {
            $container.find('.redirect-btn-wrapper').addClass('active');
            // Показываем URL поле с задержкой для плавной анимации
            setTimeout(function() {
                $urlWrapper.removeClass('hidden');
                $container.find('.url-input-field').focus();
            }, 150);
        } else {
            // Скрываем URL поле
            $urlWrapper.addClass('hidden');
        }
        
        // Обновляем скрытое поле
        $container.find('.country-action-input').val(value);
    });
      // Обработка ввода URL для compact rules - проверяем протокол
    $(document).on('input', '.url-input-field', function() {
        var $input = $(this);
        var value = $input.val().trim();
        
        // Сохраняем URL как есть, добавляем https:// только если нет протокола
        if (value) {
            var fullUrl = value.match(/^https?:\/\//) ? value : 'https://' + value;
            $input.attr('data-full-url', fullUrl);
        }
    });
      // Управление компактными кнопками для настроек по умолчанию
    $(document).on('click', '.default-action-btn', function() {
        var $btn = $(this);
        var value = $btn.data('value');
        var $container = $btn.closest('.default-action-compact');
        
        // Убираем активный класс со всех кнопок в этом контейнере
        $container.find('.default-action-btn').removeClass('active');
        $container.find('.default-redirect-wrapper').removeClass('active');
        
        // Добавляем активный класс к выбранной кнопке
        $btn.addClass('active');
        
        // Управляем показом URL поля
        var $urlWrapper = $container.find('.default-url-wrapper');
        if (value === 'redirect') {
            $container.find('.default-redirect-wrapper').addClass('active');
            // Показываем URL поле с задержкой для плавной анимации
            setTimeout(function() {
                $urlWrapper.removeClass('hidden');
                $container.find('.default-url-field').focus();
            }, 150);
        } else {
            // Скрываем URL поле
            $urlWrapper.addClass('hidden');
        }
        
        // Обновляем скрытое поле
        $('#default_action_input').val(value);
    });    // Обработка ввода URL для default settings - проверяем протокол
    $(document).on('input', '.default-url-field', function() {
        var $input = $(this);
        var value = $input.val().trim();
        
        // Сохраняем URL как есть, добавляем https:// только если нет протокола
        if (value) {
            var fullUrl = value.match(/^https?:\/\//) ? value : 'https://' + value;
            $input.attr('data-full-url', fullUrl);
        }
    });

    // Управление кнопками-переключателями для настроек по умолчанию (старый код - удаляем)
    $('.toggle-btn').click(function() {
        var value = $(this).data('value');
        
        // Убираем активный класс со всех кнопок
        $('.toggle-btn').removeClass('active');
        
        // Добавляем активный класс к выбранной кнопке
        $(this).addClass('active');
        
        // Обновляем скрытое поле
        $('#default_action_input').val(value);
        
        // Показываем/скрываем поле URL
        if (value === 'redirect') {
            $('.redirect-url-container').show();
            $('input[name="default_redirect_url"]').focus();
        } else {
            $('.redirect-url-container').hide();
        }
    });
    
    // Обработка ввода URL - автоматическое добавление https://
    $(document).on('input', 'input[name="default_redirect_url"]', function() {
        var $input = $(this);
        var value = $input.val().trim();
        
        // Сохраняем полный URL с протоколом в data атрибуте
        if (value) {
            var fullUrl = value.match(/^https?:\/\//) ? value : 'https://' + value;
            $input.attr('data-full-url', fullUrl);
        }
    });    // При отправке формы добавляем протокол к URL
    $('form').submit(function() {
        // Обрабатываем основной URL (новые поля)
        var $urlInput = $('.default-url-field');
        var fullUrl = $urlInput.attr('data-full-url') || '';
        if (fullUrl && !fullUrl.match(/^https?:\/\//)) {
            fullUrl = 'https://' + fullUrl;
        }
        
        // Заменяем значение поля на полный URL
        if ($urlInput.length > 0) {
            $urlInput.val(fullUrl);
        }
        
        // Обрабатываем старые поля (если есть)
        var $oldUrlInput = $('input[name="default_redirect_url"]:not(.default-url-field)');
        if ($oldUrlInput.length > 0) {
            var oldFullUrl = $oldUrlInput.attr('data-full-url') || '';
            if (oldFullUrl && !oldFullUrl.match(/^https?:\/\//)) {
                oldFullUrl = 'https://' + oldFullUrl;
            }
            
            // Создаем скрытое поле с полным URL
            $('<input>').attr({
                type: 'hidden',
                name: 'default_redirect_url_full',
                value: oldFullUrl
            }).appendTo(this);
        }
        
        // Обрабатываем URL для каждого правила страны (новые компактные поля)
        $('.url-input-field').each(function() {
            var $input = $(this);
            var fullUrl = $input.attr('data-full-url') || '';
            if (fullUrl && !fullUrl.match(/^https?:\/\//)) {
                fullUrl = 'https://' + fullUrl;
            }
            // Заменяем значение поля на полный URL
            $input.val(fullUrl);
        });
        
        formChanged = false;
    });// Модальное окно тестирования
    $('#test-ip-btn').click(function() {
        $('#test-ip-modal').show();
        $('#test-ip-input').focus();
    });    // Модальное окно настроек
    $('#settings-btn').click(function() {
        // Повторно синхронизируем состояние чекбоксов перед открытием
        initializeSettingsModal();
        $('#settings-modal').show();
    });

    // Модальное окно инструкции
    $('#instructions-btn').click(function() {
        $('#instructions-modal').show();
    });
    
    $('.close').click(function() {
        $('.modal').hide();
        $('#test-results').hide();
        $('#test-ip-input').val('');
    });
    
    $(window).click(function(event) {
        if ($(event.target).hasClass('modal')) {
            $('.modal').hide();
            $('#test-results').hide();
            $('#test-ip-input').val('');
        }
    });    // Сохранение настроек из модального окна
    $('#save-settings').click(function() {
        // Синхронизируем значения чекбоксов с скрытыми полями
        var excludeAdmin = $('#settings-modal input[name="exclude_admin_users"]').is(':checked');
        var enableLogging = $('#settings-modal input[name="enable_logging"]').is(':checked');
        
        $('#hidden_exclude_admin_users').val(excludeAdmin ? '1' : '0');
        $('#hidden_enable_logging').val(enableLogging ? '1' : '0');
        
        $('#settings-modal').hide();
        
        // Показываем тихое уведомление в кнопке
        var $btn = $('#save-settings');
        var originalText = $btn.text();
        $btn.text('✓ Применено').css('background', '#10b981');
        setTimeout(function() {
            $btn.text(originalText).css('background', '');
        }, 1500);
    });
    
    // Тестирование IP - Enter для запуска
    $('#test-ip-input').keypress(function(e) {
        if (e.which === 13) {
            $('#run-test').click();
        }
    });
      // Тестирование IP
    $('#run-test').click(function() {
        var ip = $('#test-ip-input').val().trim();
        if (!ip) {
            $('#test-ip-input').focus();
            return;
        }
        
        // Простая валидация IP
        var ipRegex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
        if (!ipRegex.test(ip)) {
            $('#test-ip-input').focus();
            return;
        }
        
        // Показываем индикатор загрузки
        $('#run-test').text('Проверяется...').prop('disabled', true);
        
        $.post(neetrinoRedirect301.ajax_url, {
            action: 'neetrino_test_redirect_ip',
            ip: ip,
            nonce: neetrinoRedirect301.nonce
        }, function(response) {
            $('#run-test').text('Проверить').prop('disabled', false);
            
            if (response.success) {
                var result = response.data;
                var actionText = result.action === 'redirect' ? 'Перенаправление' : 'Остается на сайте';
                var actionColor = result.action === 'redirect' ? '#dc3232' : '#00a32a';
                
                var output = `
                    <div style="border-left: 4px solid ${actionColor}; padding-left: 12px;">
                        <p><strong>IP:</strong> ${ip}</p>
                        <p><strong>Страна:</strong> ${result.country || 'Не определена'}</p>
                        <p><strong>Действие:</strong> <span style="color: ${actionColor}; font-weight: bold;">${actionText}</span></p>
                        ${result.redirect_url ? `<p><strong>URL перенаправления:</strong> <br><a href="${result.redirect_url}" target="_blank" style="word-break: break-all;">${result.redirect_url}</a></p>` : ''}
                    </div>
                `;
                $('#test-output').html(output);
                $('#test-results').show();
            } else {
                // Показываем ошибку в области результатов вместо alert
                var output = `<div style="color: #ef4444; padding: 12px; border: 1px solid #ef4444; border-radius: 4px;">Ошибка: ${response.data}</div>`;
                $('#test-output').html(output);
                $('#test-results').show();
            }
        }).fail(function() {
            $('#run-test').text('Проверить').prop('disabled', false);
            // Показываем ошибку в области результатов вместо alert
            var output = `<div style="color: #ef4444; padding: 12px; border: 1px solid #ef4444; border-radius: 4px;">Ошибка соединения с сервером</div>`;
            $('#test-output').html(output);
            $('#test-results').show();
        });
    });
      // Очистка кеша
    $('#clear-cache-btn').click(function() {
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.text('Очищается...').prop('disabled', true);
        
        $.post(neetrinoRedirect301.ajax_url, {
            action: 'neetrino_clear_redirect_cache',
            nonce: neetrinoRedirect301.nonce
        }, function(response) {
            $btn.text(originalText).prop('disabled', false);
            
            if (response.success) {
                // Показываем успешное уведомление без popup
                $btn.text('✓ Очищен').css('color', '#10b981');
                setTimeout(function() {
                    $btn.text(originalText).css('color', '');
                }, 2000);
            } else {
                // Показываем ошибку без popup
                $btn.text('✗ Ошибка').css('color', '#ef4444');
                setTimeout(function() {
                    $btn.text(originalText).css('color', '');
                }, 2000);
            }
        }).fail(function() {
            $btn.text(originalText).prop('disabled', false);
            // Показываем ошибку без popup
            $btn.text('✗ Ошибка').css('color', '#ef4444');
            setTimeout(function() {
                $btn.text(originalText).css('color', '');
            }, 2000);
        });
    });
    
    // Валидация URL при вводе
    $(document).on('input', 'input[type="url"]', function() {
        var $input = $(this);
        var url = $input.val().trim();
        
        if (url && !url.match(/^https?:\/\//)) {
            // Автоматически добавляем https:// если протокол не указан
            $input.val('https://' + url);
        }
    });
      // Отслеживание изменений для автосохранения
    var formChanged = false;
    $('form input, form select').change(function() {
        formChanged = true;
    });
    
    $('form').submit(function() {
        formChanged = false;
    });
    
    // Автосохранение черновика каждые 30 секунд
    setInterval(function() {
        if (formChanged) {
            saveDraft();
        }
    }, 30000);
    
    function saveDraft() {
        var formData = $('form').serializeArray();
        localStorage.setItem('neetrino_redirect_301_draft', JSON.stringify(formData));
    }
      // Восстановление черновика при загрузке страницы
    function restoreDraft() {
        var draft = localStorage.getItem('neetrino_redirect_301_draft');
        if (draft) {
            try {
                var formData = JSON.parse(draft);
                $.each(formData, function(i, field) {
                    var $field = $('[name="' + field.name + '"]');
                    if ($field.attr('type') === 'radio' || $field.attr('type') === 'checkbox') {
                        $field.filter('[value="' + field.value + '"]').prop('checked', true);
                    } else {
                        $field.val(field.value);
                    }
                });
                
                // Обновляем состояние полей
                $('input[name="default_action"]:checked').trigger('change');
                $('input[name*="[action]"]:checked').trigger('change');
                
                formChanged = true;
            } catch (e) {
                console.error('Ошибка восстановления черновика:', e);
            }
        }
    }
    
    // Восстанавливаем черновик при загрузке
    restoreDraft();
    
    // Очищаем черновик после успешного сохранения
    if ($('.notice-success').length > 0) {
        localStorage.removeItem('neetrino_redirect_301_draft');
        formChanged = false;
    }
});

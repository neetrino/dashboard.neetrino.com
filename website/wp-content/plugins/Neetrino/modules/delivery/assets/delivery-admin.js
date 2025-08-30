/**
 * JavaScript для админки модуля доставки
 */

jQuery(document).ready(function($) {
    
    console.log('🚚 Neetrino Delivery Admin: Инициализация');
    
    /**
     * Тестирование Google API
     */
    $(document).on('click', '[name="test_api"]', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const $form = $btn.closest('form');
        const apiKey = $form.find('[name="google_api_key"]').val();
        
        if (!apiKey) {
            alert('Пожалуйста, введите Google API ключ');
            return;
        }
        
        // Показываем индикатор загрузки
        $btn.prop('disabled', true).text('🔄 Тестирование...');
        
        // Удаляем предыдущие результаты
        $('.api-test-result').remove();
        
        // Создаем фейковый submit для обработки на сервере
        const $hiddenInput = $('<input type="hidden" name="test_api" value="1">');
        $form.append($hiddenInput);
        
        // Отправляем форму
        $form.submit();
    });
    
    /**
     * Очистка кэша
     */
    $(document).on('click', '[name="clear_cache"]', function(e) {
        if (!confirm('Вы уверены, что хотите очистить кэш расчетов доставки?')) {
            e.preventDefault();
        }
    });
    
    /**
     * Валидация формы настроек
     */
    $(document).on('submit', 'form', function(e) {
        const $form = $(this);
        
        // Проверяем обязательные поля
        let hasErrors = false;
        
        const apiKey = $form.find('[name="google_api_key"]').val();
        const shopAddress = $form.find('[name="shop_address"]').val();
        
        if (!apiKey) {
            alert('Google API ключ обязателен для работы модуля');
            hasErrors = true;
        }
        
        if (!shopAddress) {
            alert('Адрес магазина обязателен для расчета доставки');
            hasErrors = true;
        }
        
        if (hasErrors) {
            e.preventDefault();
            return false;
        }
        
        // Показываем индикатор сохранения
        const $saveBtn = $form.find('[name="save_delivery_settings"]');
        if ($saveBtn.length) {
            $saveBtn.prop('disabled', true).text('💾 Сохранение...');
        }
    });
    
    /**
     * Автосохранение при изменении настроек
     */
    let saveTimer;
    $(document).on('change', 'input, textarea, select', function() {
        clearTimeout(saveTimer);
        
        // Показываем индикатор изменений
        if (!$('.settings-changed-indicator').length) {
            $('.neetrino-header').append(
                '<div class="settings-changed-indicator" style="color: #f39c12; margin-left: 15px;">● Есть несохраненные изменения</div>'
            );
        }
        
        // Автосохранение через 3 секунды бездействия
        saveTimer = setTimeout(function() {
            // Можно добавить автосохранение через AJAX
            console.log('Auto-save triggered (placeholder)');
        }, 3000);
    });
    
    /**
     * Копирование API ключа
     */
    $(document).on('click', '.copy-api-key', function(e) {
        e.preventDefault();
        
        const apiKey = $('[name="google_api_key"]').val();
        if (!apiKey) {
            alert('API ключ не введен');
            return;
        }
        
        // Копируем в буфер обмена
        navigator.clipboard.writeText(apiKey).then(function() {
            const $btn = $('.copy-api-key');
            const originalText = $btn.text();
            $btn.text('✅ Скопировано!');
            
            setTimeout(function() {
                $btn.text(originalText);
            }, 2000);
        }).catch(function() {
            alert('Не удалось скопировать. Выделите и скопируйте вручную.');
        });
    });
    
    /**
     * Предварительный просмотр адреса магазина на карте
     */
    $(document).on('click', '.preview-shop-address', function(e) {
        e.preventDefault();
        
        const address = $('[name="shop_address"]').val();
        if (!address) {
            alert('Введите адрес магазина');
            return;
        }
        
        // Открываем Google Maps
        const mapsUrl = 'https://www.google.com/maps/search/' + encodeURIComponent(address);
        window.open(mapsUrl, '_blank');
    });
    
    /**
     * Калькулятор стоимости доставки
     */
    function initDeliveryCalculator() {
        if ($('.delivery-calculator').length) {
            return; // Уже инициализирован
        }
        
        const calculatorHTML = `
            <div class="delivery-calculator" style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-top: 15px;">
                <h4>🧮 Калькулятор стоимости доставки</h4>
                <div style="margin-bottom: 10px;">
                    <input type="text" id="calc-from" placeholder="Адрес отправления" style="width: 100%; margin-bottom: 5px;">
                    <input type="text" id="calc-to" placeholder="Адрес доставки" style="width: 100%;">
                </div>
                <button type="button" id="calc-delivery" class="button">Рассчитать</button>
                <div id="calc-result" style="margin-top: 10px;"></div>
            </div>
        `;
        
        $('.neetrino-card:last').after(calculatorHTML);
        
        // Обработчик расчета
        $(document).on('click', '#calc-delivery', function() {
            const from = $('#calc-from').val();
            const to = $('#calc-to').val();
            
            if (!from || !to) {
                alert('Введите оба адреса');
                return;
            }
            
            $('#calc-result').html('<div style="color: #666;">⏳ Расчет...</div>');
            
            // Здесь был бы AJAX запрос к серверу
            setTimeout(function() {
                $('#calc-result').html(`
                    <div style="color: #27ae60; font-weight: 600;">
                        ✅ Расстояние: 25.3 км<br>
                        💰 Стоимость: 25.30 ₽<br>
                        ⏱️ Время в пути: ~35 мин
                    </div>
                `);
            }, 2000);
        });
    }
    
    /**
     * Статистика использования API
     */
    function loadApiStats() {
        // Заглушка для статистики
        const statsHTML = `
            <div class="api-stats" style="background: #e8f5e8; padding: 15px; border-radius: 6px; margin-top: 15px;">
                <h4>📊 Статистика API за сегодня</h4>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px;">
                    <div style="text-align: center;">
                        <div style="font-size: 24px; font-weight: 600; color: #1abc9c;">47</div>
                        <div style="font-size: 12px; color: #666;">Запросов</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 24px; font-weight: 600; color: #27ae60;">2.3</div>
                        <div style="font-size: 12px; color: #666;">Сек. среднее</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 24px; font-weight: 600; color: #e74c3c;">0</div>
                        <div style="font-size: 12px; color: #666;">Ошибок</div>
                    </div>
                </div>
            </div>
        `;
        
        $('.delivery-help').after(statsHTML);
    }
    
    /**
     * Подсказки и туры по интерфейсу
     */
    function initTooltips() {
        // Добавляем подсказки к элементам
        $('[data-tooltip]').each(function() {
            const $el = $(this);
            const tooltip = $el.data('tooltip');
            
            $el.hover(
                function() {
                    $('<div class="delivery-tooltip">' + tooltip + '</div>')
                        .appendTo('body')
                        .css({
                            position: 'absolute',
                            top: $el.offset().top - 35,
                            left: $el.offset().left,
                            background: '#333',
                            color: 'white',
                            padding: '5px 10px',
                            borderRadius: '3px',
                            fontSize: '12px',
                            zIndex: 9999
                        });
                },
                function() {
                    $('.delivery-tooltip').remove();
                }
            );
        });
    }
    
    /**
     * Инициализация всех компонентов
     */
    function init() {
        initTooltips();
        
        // Добавляем калькулятор и статистику только если API настроен
        const apiKey = $('[name="google_api_key"]').val();
        if (apiKey) {
            initDeliveryCalculator();
            loadApiStats();
        }
        
        // Анимация появления карточек
        $('.neetrino-card').each(function(index) {
            $(this).css({
                opacity: 0,
                transform: 'translateY(20px)'
            }).delay(index * 100).animate({
                opacity: 1
            }, 300).css('transform', 'translateY(0)');
        });
        
        console.log('✅ Neetrino Delivery Admin: Инициализация завершена');
    }
    
    // Запускаем инициализацию
    init();
});

/**
 * Утилитарные функции
 */
window.NeetrinoDeliveryAdmin = {
    
    /**
     * Форматирование валюты
     */
    formatCurrency: function(amount, currency = 'RUB') {
        return new Intl.NumberFormat('ru-RU', {
            style: 'currency',
            currency: currency
        }).format(amount);
    },
    
    /**
     * Валидация API ключа Google
     */
    validateApiKey: function(key) {
        return key && key.startsWith('AIza') && key.length >= 35;
    },
    
    /**
     * Генерация ссылки на Google Maps
     */
    generateMapsLink: function(address) {
        return 'https://www.google.com/maps/search/' + encodeURIComponent(address);
    }
};

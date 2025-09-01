/**
 * JavaScript для расчета доставки на фронтенде
 */

jQuery(document).ready(function($) {
    
    // Проверяем доступность настроек
    let settings = {};
    let messages = {};
    
    if (typeof neetrinoDelivery !== 'undefined') {
        settings = neetrinoDelivery.settings || {};
        messages = neetrinoDelivery.messages || {};
    } else if (typeof neetrino_delivery !== 'undefined') {
        settings = neetrino_delivery.settings || {};
        messages = neetrino_delivery.messages || {};
    } else {
        console.warn('⚠️ Настройки доставки не найдены, используем fallback');
        settings = {
            enable_autocomplete: true,
            price_per_km: 1,
            min_delivery_cost: 0,
            max_delivery_cost: 100
        };
        messages = {
            calculating: 'Расчет стоимости доставки...',
            error: 'Ошибка при расчете доставки'
        };
    }
    
    console.log('💰 Neetrino Delivery Calculator: Инициализация');
    console.log('📋 Настройки:', settings);
    
    /**
     * Калькулятор стоимости доставки
     */
    const DeliveryCalculator = {
        
        /**
         * Инициализация калькулятора
         */
        init: function() {
            this.bindEvents();
            this.addCalculatorWidget();
        },
        
        /**
         * Привязка событий
         */
        bindEvents: function() {
            // Обновление при изменении адреса
            $(document).on('change', '.address-field', function() {
                DeliveryCalculator.scheduleCalculation();
            });
            
            // Обновление при изменении способа доставки
            $(document).on('change', 'input[name^="shipping_method"]', function() {
                if ($(this).val().includes('neetrino_delivery')) {
                    DeliveryCalculator.scheduleCalculation();
                }
            });
            
            // Обновление при изменении корзины
            $(document).on('updated_cart_totals', function() {
                DeliveryCalculator.scheduleCalculation();
            });
            
            // Обновление checkout
            $(document).on('updated_checkout', function() {
                DeliveryCalculator.addCalculatorWidget();
                DeliveryCalculator.scheduleCalculation();
            });
        },
        
        /**
         * Добавление виджета калькулятора
         */
        addCalculatorWidget: function() {
            // Удаляем существующий виджет
            $('.delivery-calculator-widget').remove();
            
            // Добавляем виджет только если выбран наш способ доставки
            const selectedShipping = $('input[name^="shipping_method"]:checked').val();
            if (!selectedShipping || !selectedShipping.includes('neetrino_delivery')) {
                return;
            }
            
            const widgetHTML = `
                <div class="delivery-calculator-widget">
                    <div class="delivery-widget-header">
                        <h4>🚚 Расчет доставки</h4>
                        <button type="button" class="delivery-recalculate">🔄 Пересчитать</button>
                    </div>
                    <div class="delivery-widget-content">
                        <div class="delivery-status">⏳ Расчет стоимости...</div>
                        <div class="delivery-details" style="display: none;"></div>
                    </div>
                </div>
            `;
            
            $('.woocommerce-shipping-methods').after(widgetHTML);
        },
        
        /**
         * Планирование расчета (с задержкой)
         */
        scheduleCalculation: function() {
            clearTimeout(this.calculationTimer);
            this.calculationTimer = setTimeout(() => {
                this.calculateDelivery();
            }, 1000);
        },
        
        /**
         * Основной метод расчета доставки
         */
        calculateDelivery: function() {
            const customerAddress = this.getCustomerAddress();
            
            if (!customerAddress) {
                this.showCalculationResult({
                    success: false,
                    message: 'Введите адрес доставки для расчета стоимости'
                });
                return;
            }
            
            console.log('💰 Начинаем расчет доставки:', customerAddress);
            
            this.updateStatus('⏳ Расчет стоимости доставки...');
            
            $.ajax({
                url: (typeof neetrinoDelivery !== 'undefined') ? neetrinoDelivery.ajax_url : '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'delivery_calculate_cost',
                    to: customerAddress,
                    nonce: (typeof neetrinoDelivery !== 'undefined') ? neetrinoDelivery.nonce : ''
                },
                timeout: 15000,
                success: (response) => {
                    console.log('✅ Результат расчета:', response);
                    this.showCalculationResult(response);
                },
                error: (xhr, status, error) => {
                    console.error('❌ Ошибка AJAX:', error);
                    this.showCalculationResult({
                        success: false,
                        message: messages.error || 'Ошибка при расчете доставки'
                    });
                }
            });
        },
        
        /**
         * Получение адреса клиента
         */
        getCustomerAddress: function() {
            const addressParts = [];
            
            // Определяем какой адрес использовать (shipping или billing)
            const useShipping = $('#ship-to-different-address-checkbox').is(':checked');
            const prefix = useShipping ? 'shipping_' : 'billing_';
            
            const fields = ['address_1', 'address_2', 'city', 'state', 'postcode', 'country'];
            
            fields.forEach(field => {
                const $field = $(`#${prefix}${field}`);
                if ($field.length && $field.val()) {
                    if (field === 'country') {
                        // Получаем название страны вместо кода
                        const countryName = $field.find('option:selected').text() || $field.val();
                        addressParts.push(countryName);
                    } else {
                        addressParts.push($field.val());
                    }
                }
            });
            
            return addressParts.filter(part => part.trim()).join(', ');
        },
        
        /**
         * Отображение результата расчета
         */
        showCalculationResult: function(response) {
            const $widget = $('.delivery-calculator-widget');
            const $status = $widget.find('.delivery-status');
            const $details = $widget.find('.delivery-details');
            
            if (response.success && response.data) {
                const data = response.data;
                
                $status.html(`✅ Стоимость доставки: <strong>${this.formatCurrency(data.final_cost)}</strong>`);
                
                let detailsHTML = `
                    <div class="delivery-info-grid">
                        <div class="delivery-info-item">
                            <span class="label">Расстояние:</span>
                            <span class="value">${data.distance_text}</span>
                        </div>
                        <div class="delivery-info-item">
                            <span class="label">Время в пути:</span>
                            <span class="value">${data.duration_text}</span>
                        </div>
                `;
                
                if (data.free_delivery) {
                    detailsHTML += `
                        <div class="delivery-info-item free-delivery">
                            <span class="label">🎉 Бесплатная доставка!</span>
                            <span class="value">При заказе от ${this.formatCurrency(data.free_delivery_threshold)}</span>
                        </div>
                    `;
                }
                
                detailsHTML += '</div>';
                
                if (response.cached) {
                    detailsHTML += '<div class="delivery-cached">ℹ️ Данные из кэша</div>';
                }
                
                $details.html(detailsHTML).show();
                
                // Обновляем стоимость в checkout
                this.updateCheckoutShipping(data.final_cost);
                
            } else {
                $status.html(`❌ ${response.message || 'Ошибка расчета'}`);
                $details.hide();
            }
            
            $status.removeClass('calculating');
        },
        
        /**
         * Обновление статуса
         */
        updateStatus: function(message) {
            $('.delivery-status').html(message).addClass('calculating');
        },
        
        /**
         * Форматирование валюты
         */
        formatCurrency: function(amount) {
            if (typeof woocommerce_price_format !== 'undefined') {
                // Используем настройки WooCommerce если доступны
                return woocommerce_price_format.format.replace('%v', amount).replace('%s', woocommerce_price_format.symbol);
            }
            
            // Fallback
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB'
            }).format(amount);
        },
        
        /**
         * Обновление стоимости доставки в checkout
         */
        updateCheckoutShipping: function(cost) {
            // Триггерим обновление checkout для применения новой стоимости
            $('body').trigger('update_checkout');
        },
        
        /**
         * Кэширование результатов расчета
         */
        cacheResult: function(address, result) {
            const cacheKey = 'neetrino_delivery_' + btoa(address);
            const cacheData = {
                result: result,
                timestamp: Date.now()
            };
            
            try {
                localStorage.setItem(cacheKey, JSON.stringify(cacheData));
            } catch (e) {
                console.warn('Не удалось сохранить в localStorage:', e);
            }
        },
        
        /**
         * Получение результата из кэша
         */
        getCachedResult: function(address) {
            const cacheKey = 'neetrino_delivery_' + btoa(address);
            
            try {
                const cached = localStorage.getItem(cacheKey);
                if (cached) {
                    const data = JSON.parse(cached);
                    
                    // Проверяем время жизни кэша (1 час)
                    if (Date.now() - data.timestamp < 3600000) {
                        return data.result;
                    }
                }
            } catch (e) {
                console.warn('Ошибка чтения кэша:', e);
            }
            
            return null;
        }
    };
    
    /**
     * Виджет отслеживания доставки
     */
    const DeliveryTracker = {
        
        init: function() {
            this.addTrackingWidget();
        },
        
        addTrackingWidget: function() {
            // Добавляем виджет отслеживания на страницу заказа
            if ($('.woocommerce-order').length) {
                const trackingHTML = `
                    <div class="delivery-tracking-widget">
                        <h3>📦 Отслеживание доставки</h3>
                        <div class="tracking-status">
                            <div class="status-step active">📦 Заказ принят</div>
                            <div class="status-step">🚛 В пути</div>
                            <div class="status-step">🏠 Доставлен</div>
                        </div>
                    </div>
                `;
                
                $('.woocommerce-order-details').after(trackingHTML);
            }
        }
    };
    
    /**
     * Уведомления о доставке
     */
    const DeliveryNotifications = {
        
        show: function(message, type = 'info') {
            const $notification = $(`
                <div class="delivery-notification delivery-notification-${type}">
                    <span class="notification-icon">${this.getIcon(type)}</span>
                    <span class="notification-message">${message}</span>
                    <button class="notification-close">×</button>
                </div>
            `);
            
            $('body').append($notification);
            
            // Автоматическое скрытие через 5 секунд
            setTimeout(() => {
                $notification.fadeOut(() => $notification.remove());
            }, 5000);
            
            // Закрытие по клику
            $notification.find('.notification-close').on('click', () => {
                $notification.fadeOut(() => $notification.remove());
            });
        },
        
        getIcon: function(type) {
            const icons = {
                info: 'ℹ️',
                success: '✅',
                warning: '⚠️',
                error: '❌'
            };
            return icons[type] || icons.info;
        }
    };
    
    /**
     * Обработчик событий для кнопки пересчета
     */
    $(document).on('click', '.delivery-recalculate', function(e) {
        e.preventDefault();
        DeliveryCalculator.calculateDelivery();
    });
    
    /**
     * Инициализация всех компонентов
     */
    function init() {
        DeliveryCalculator.init();
        DeliveryTracker.init();
        
        console.log('✅ Neetrino Delivery Calculator: Инициализация завершена');
    }
    
    // Запускаем инициализацию
    init();
    
    // Экспортируем для глобального доступа
    window.NeetrinoDeliveryCalculator = DeliveryCalculator;
    window.NeetrinoDeliveryNotifications = DeliveryNotifications;
});

/* Neetrino Checkout Fields Module Frontend JavaScript */

jQuery(document).ready(function($) {
    
    // Проверяем, что настройки доступны
    if (typeof neetrino_checkout === 'undefined') {
        return;
    }
    
    const shippingDestination = neetrino_checkout.shipping_destination;
    
    // Инициализация логики адреса доставки
    initShippingDestinationLogic();
    
    function initShippingDestinationLogic() {
        
        // Применяем логику в зависимости от настройки
        switch (shippingDestination) {
            case 'billing_only':
                enforceShipToBillingOnly();
                break;
            case 'billing':
                setDefaultToBilling();
                break;
            case 'shipping':
                setDefaultToShipping();
                break;
        }
        
        // Следим за изменениями в биллинге для копирования в доставку
        if (shippingDestination === 'billing_only') {
            watchBillingChanges();
        }
    }
    
    /**
     * Принудительно использует только платёжный адрес
     */
    function enforceShipToBillingOnly() {
        // Скрываем чекбокс "Доставить по другому адресу"
        const shipToDifferentCheckbox = $('#ship-to-different-address-checkbox');
        if (shipToDifferentCheckbox.length) {
            shipToDifferentCheckbox.closest('.woocommerce-shipping-fields').hide();
        }
        
        // Убираем чекбокс из формы
        $('#ship-to-different-address-checkbox').prop('checked', false);
        
        // Копируем данные из биллинга в доставку
        copyBillingToShipping();
        
        // Добавляем уведомление
        showShippingNotice('Доставка осуществляется по платёжному адресу', 'info');
    }
    
    /**
     * Устанавливает платёжный адрес по умолчанию, но разрешает изменение
     */
    function setDefaultToBilling() {
        // Если чекбокс не отмечен, копируем биллинг в доставку
        if (!$('#ship-to-different-address-checkbox').is(':checked')) {
            copyBillingToShipping();
        }
        
        // Следим за изменениями чекбокса
        $('#ship-to-different-address-checkbox').on('change', function() {
            if (!$(this).is(':checked')) {
                copyBillingToShipping();
            }
        });
    }
    
    /**
     * Устанавливает отдельный адрес доставки по умолчанию
     */
    function setDefaultToShipping() {
        // Автоматически отмечаем чекбокс доставки
        $('#ship-to-different-address-checkbox').prop('checked', true);
        
        // Показываем поля доставки
        $('.woocommerce-shipping-fields').show();
    }
    
    /**
     * Копирует данные из полей биллинга в поля доставки
     */
    function copyBillingToShipping() {
        const billingFields = [
            'first_name', 'last_name', 'company', 
            'address_1', 'address_2', 'city', 
            'state', 'postcode', 'country'
        ];
        
        billingFields.forEach(function(field) {
            const billingValue = $('#billing_' + field).val();
            const shippingField = $('#shipping_' + field);
            
            if (shippingField.length && billingValue) {
                shippingField.val(billingValue);
                
                // Триггерим событие change для совместимости с другими плагинами
                shippingField.trigger('change');
            }
        });
        
        // Особая обработка для select полей (страна, регион)
        const billingCountry = $('#billing_country').val();
        if (billingCountry) {
            $('#shipping_country').val(billingCountry).trigger('change');
        }
        
        const billingState = $('#billing_state').val();
        if (billingState) {
            setTimeout(function() {
                $('#shipping_state').val(billingState).trigger('change');
            }, 500); // Задержка для загрузки регионов
        }
    }
    
    /**
     * Следит за изменениями в полях биллинга и копирует их в доставку
     */
    function watchBillingChanges() {
        const billingFields = [
            '#billing_first_name', '#billing_last_name', '#billing_company',
            '#billing_address_1', '#billing_address_2', '#billing_city',
            '#billing_postcode', '#billing_country', '#billing_state'
        ];
        
        billingFields.forEach(function(selector) {
            $(document).on('change blur keyup', selector, function() {
                const fieldName = $(this).attr('id').replace('billing_', '');
                const shippingField = $('#shipping_' + fieldName);
                
                if (shippingField.length) {
                    shippingField.val($(this).val()).trigger('change');
                }
            });
        });
    }
    
    /**
     * Показывает уведомление о режиме доставки
     */
    function showShippingNotice(message, type = 'info') {
        // Проверяем, нет ли уже уведомления
        if ($('.neetrino-shipping-notice').length) {
            return;
        }
        
        const iconClass = type === 'info' ? 'woocommerce-info' : 'woocommerce-message';
        
        const notice = $('<div class="woocommerce-message neetrino-shipping-notice" style="margin-bottom: 20px;">' +
            '<i class="fa-solid fa-info-circle" style="margin-right: 8px;"></i>' +
            message +
            '</div>');
        
        // Вставляем уведомление перед формой чекаута
        $('.woocommerce-checkout-review-order').before(notice);
        
        // Автоматически скрываем через 10 секунд
        setTimeout(function() {
            notice.fadeOut(500, function() {
                $(this).remove();
            });
        }, 10000);
    }
    
    // Дополнительная обработка для обновления чекаута
    $(document.body).on('updated_checkout', function() {
        // Переинициализируем логику после обновления чекаута
        setTimeout(function() {
            initShippingDestinationLogic();
        }, 100);
    });
    
    // Обработка изменений в WooCommerce
    $(document.body).on('checkout_error', function() {
        // При ошибке переинициализируем
        setTimeout(function() {
            initShippingDestinationLogic();
        }, 500);
    });
});

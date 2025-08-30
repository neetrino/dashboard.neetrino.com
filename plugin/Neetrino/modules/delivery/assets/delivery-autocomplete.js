/**
 * Neetrino Delivery - Simple Autocomplete
 * Простое автозаполнение адресов через Google Places API
 */

// Глобальные переменные
window.neetrinoAutocomplete = {
    settings: {},
    initialized: false
};

// Инициализация при загрузке Google API
window.initNeetrinoDelivery = function() {
    console.log('🚀 Инициализация Neetrino Delivery Autocomplete');
    
    // Используем fallback настройки если объект не найден
    if (typeof neetrinoDelivery === 'undefined') {
        console.warn('⚠️ neetrinoDelivery объект не найден, используем fallback настройки');
        window.neetrinoDelivery = {
            settings: {
                google_api_key: '',
                enable_autocomplete: true,
                allowed_countries: ['RU', 'US', 'GB', 'AM'],
                restrict_countries: true,
                language: 'ru'
            }
        };
    }
    
    window.neetrinoAutocomplete.settings = window.neetrinoDelivery.settings;
    window.neetrinoAutocomplete.initialized = true;
    
    // Инициализируем автозаполнение после загрузки DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAutocompleteFields);
    } else {
        initAutocompleteFields();
    }
};

// Инициализация полей автозаполнения
function initAutocompleteFields() {
    console.log('🔧 Инициализация полей автозаполнения');
    
    if (!window.neetrinoAutocomplete.initialized || typeof google === 'undefined') {
        console.warn('⚠️ Google API или настройки не загружены');
        return;
    }
    
    // Проверяем доступность Google Places API
    if (!google.maps || !google.maps.places) {
        console.error('❌ Google Places API недоступен');
        return;
    }
    
    // Находим поля адресов
    let addressFields = document.querySelectorAll('input[name*="address_1"]');
    
    // Если не найдены, ищем конкретные ID
    if (addressFields.length === 0) {
        const fieldIds = ['billing_address_1', 'shipping_address_1'];
        addressFields = [];
        fieldIds.forEach(id => {
            const field = document.getElementById(id);
            if (field) {
                addressFields.push(field);
            }
        });
    }
    
    if (addressFields.length === 0) {
        console.warn('⚠️ Поля автозаполнения не найдены');
        return;
    }
    
    let successCount = 0;
    addressFields.forEach(field => {
        if (setupSimpleAutocomplete(field)) {
            successCount++;
        }
    });
    
    console.log(`✅ Автозаполнение настроено для ${successCount} из ${addressFields.length} полей`);
}

// Простая настройка автозаполнения для поля
function setupSimpleAutocomplete(inputField) {
    try {
        console.log(`🔧 Настройка автозаполнения для поля: ${inputField.id || inputField.name}`);
        
        // Проверяем доступность Google Places API
        if (!google.maps || !google.maps.places) {
            console.error('❌ Google Places API недоступен для поля', inputField.id);
            return false;
        }
        
        const settings = window.neetrinoAutocomplete.settings || {};
        
        // Создаем стандартный Google Autocomplete
        const autocomplete = new google.maps.places.Autocomplete(inputField, {
            types: ['address']
        });
        
        // Ограничение по странам
        if (settings.restrict_countries && settings.allowed_countries && settings.allowed_countries.length > 0) {
            autocomplete.setComponentRestrictions({
                country: settings.allowed_countries
            });
        }
        
        // Обработчик выбора места
        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            
            if (!place.geometry) {
                console.warn('⚠️ Место не найдено:', place.name);
                return;
            }
            
            console.log('📍 Выбрано место:', place);
            
            // Заполняем поле
            inputField.value = place.formatted_address || place.name || '';
            
            // Триггерим события
            inputField.dispatchEvent(new Event('input', { bubbles: true }));
            inputField.dispatchEvent(new Event('change', { bubbles: true }));
            
            // Заполняем дополнительные поля
            fillAddressFields(place, getFieldType(inputField));
            
            // Уведомление
            showNotification('✅ Адрес выбран', 'success');
            
            // Запускаем расчет доставки
            setTimeout(() => {
                triggerDeliveryCalculation();
            }, 500);
        });
        
        console.log(`✅ Автозаполнение настроено для поля: ${inputField.id || inputField.name}`);
        return true;
        
    } catch (error) {
        console.error('❌ Ошибка настройки автозаполнения:', error);
        return false;
    }
}

// Определение типа поля
function getFieldType(inputField) {
    const fieldName = inputField.id || inputField.name || '';
    if (fieldName.includes('billing')) {
        return 'billing';
    } else if (fieldName.includes('shipping')) {
        return 'shipping';
    }
    return 'billing'; // по умолчанию
}

// Заполнение полей адреса
function fillAddressFields(place, fieldType) {
    if (!place.address_components) {
        console.warn('⚠️ Компоненты адреса не найдены');
        return;
    }
    
    const components = {};
    
    // Парсим компоненты адреса
    place.address_components.forEach(component => {
        const type = component.types[0];
        components[type] = {
            long_name: component.long_name,
            short_name: component.short_name
        };
    });
    
    console.log('📋 Компоненты адреса:', components);
    
    // Маппинг полей
    const fieldMapping = {
        [`${fieldType}_city`]: components.locality?.long_name || 
                               components.administrative_area_level_2?.long_name || '',
        [`${fieldType}_state`]: components.administrative_area_level_1?.long_name || '',
        [`${fieldType}_postcode`]: components.postal_code?.long_name || '',
        [`${fieldType}_country`]: components.country?.short_name || ''
    };
    
    // Заполняем поля
    Object.entries(fieldMapping).forEach(([fieldName, value]) => {
        const field = document.getElementById(fieldName) || 
                     document.querySelector(`[name="${fieldName}"]`);
        
        if (field && value && !field.value.trim()) {
            field.value = value;
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
            console.log(`📝 Заполнено поле ${fieldName}: ${value}`);
        }
    });
}

// Запуск расчета доставки
function triggerDeliveryCalculation() {
    console.log('🧮 Запуск расчета доставки');
    
    // Ищем функции расчета доставки
    if (typeof window.calculateDeliveryCost === 'function') {
        setTimeout(() => window.calculateDeliveryCost(), 500);
    } else if (typeof jQuery !== 'undefined') {
        setTimeout(() => jQuery('body').trigger('update_checkout'), 500);
    }
}

// Показ уведомлений
function showNotification(message, type = 'info') {
    const colors = {
        success: { bg: '#d4edda', color: '#155724' },
        warning: { bg: '#fff3cd', color: '#856404' },
        error: { bg: '#f8d7da', color: '#721c24' },
        info: { bg: '#cce7ff', color: '#004085' }
    };
    
    const style = colors[type] || colors.info;
    
    const notice = document.createElement('div');
    notice.innerHTML = message;
    notice.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${style.bg};
        color: ${style.color};
        border-radius: 4px;
        padding: 12px 16px;
        z-index: 9999;
        font-size: 14px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    `;
    
    document.body.appendChild(notice);
    
    setTimeout(() => {
        if (notice.parentElement) {
            notice.remove();
        }
    }, 3000);
}

console.log('📦 Neetrino Delivery Autocomplete загружен');

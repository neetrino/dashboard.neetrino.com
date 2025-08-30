jQuery(document).ready(function($) {
    
    // Добавление новой строки со страной
    $('#add-country').on('click', function() {
        var template = $('#country-row-template .language-row').clone();
        var timestamp = Date.now();
        
        // Генерируем уникальные имена для полей
        template.find('.country-select').attr('name', 'country_' + timestamp);
        template.find('.language-select').attr('name', 'language_' + timestamp);
        
        // Добавляем строку
        $('#countries-container').append(template);
        
        // Прокручиваем к новой строке
        $('html, body').animate({
            scrollTop: template.offset().top - 100
        }, 300);
    });
    
    // Удаление строки со страной
    $(document).on('click', '.remove-btn', function() {
        var row = $(this).closest('.language-row');
        row.fadeOut(200, function() {
            row.remove();
            updateCountryNames();
        });
    });
    
    // Обновление имен полей после удаления
    function updateCountryNames() {
        $('#countries-container .language-row').each(function(index) {
            $(this).find('.country-select').attr('name', 'country_' + index);
            $(this).find('.language-select').attr('name', 'language_' + index);
        });
    }
    
    // Автоматический выбор языка для некоторых стран
    $(document).on('change', '.country-select', function() {
        var countryCode = $(this).val();
        var languageSelect = $(this).closest('.language-row').find('.language-select');
        autoSelectLanguage(countryCode, languageSelect);
    });
    
    // Функция для обновления URL превью по умолчанию
    window.updateDefaultLanguagePreview = function(selectElement) {
        var languageCode = $(selectElement).val();
        var siteLanguage = getSiteLanguage();
        var baseUrl = getBaseUrl();
        
        var defaultUrl;
        if (languageCode === siteLanguage) {
            defaultUrl = baseUrl; // Для языка сайта - чистый URL
        } else {
            defaultUrl = baseUrl + languageCode + '/'; // Для остальных - с префиксом
        }
        
        $('#default-language-url').text(defaultUrl);
        $('#default-language-code').text(languageCode);
        
        // Также обновляем все URL в списке стран
        $('.language-select').each(function() {
            updateUrlPreview(this);
        });
    };
    
    // Функция для обновления URL превью
    window.updateUrlPreview = function(selectElement) {
        var languageCode = $(selectElement).val();
        var row = $(selectElement).closest('.language-row');
        var urlPreview = row.find('.col-url code');
        var codeDisplay = row.find('.col-code span');
        var siteLanguage = getSiteLanguage();
        var baseUrl = getBaseUrl();
        
        var newUrl;
        if (languageCode === siteLanguage) {
            newUrl = baseUrl; // Для языка сайта - чистый URL
        } else {
            newUrl = baseUrl + languageCode + '/'; // Для остальных - с префиксом
        }
        
        urlPreview.text(newUrl);
        codeDisplay.text(languageCode);
    };
      // Получение языка сайта
    function getSiteLanguage() {
        // Получаем из глобальной переменной или из скрытого поля
        if (window.autoTranslateSiteLanguage) {
            return window.autoTranslateSiteLanguage;
        }
        
        var siteLanguageField = $('#site_language');
        if (siteLanguageField.length > 0) {
            return siteLanguageField.val();
        }
        
        // Fallback для тестового сайта
        var currentUrl = window.location.href;
        if (currentUrl.includes('2222.wp.local')) {
            return 'ru';
        }
        return 'ru'; // Значение по умолчанию
    }
    
    // Получение базового URL
    function getBaseUrl() {
        return window.location.protocol + '//' + window.location.host + '/';
    }
    
    // Обновляем URL при изменении языка
    $(document).on('change', '.language-select', function() {
        updateUrlPreview(this);
    });
    
    // Обновляем все URL при изменении языка по умолчанию
    $(document).on('change', 'select[name="default_language"]', function() {
        $('.language-select').each(function() {
            updateUrlPreview(this);
        });
    });
    
    // Автоматический выбор языка по стране
    function autoSelectLanguage(countryCode, languageSelect) {
        var autoLanguages = {
            'RU': 'ru',  // Россия - русский
            'UA': 'uk',  // Украина - украинский
            'BY': 'ru',  // Беларусь - русский
            'KZ': 'ru',  // Казахстан - русский
            'AM': 'hy',  // Армения - армянский
            'GE': 'ka',  // Грузия - грузинский
            'DE': 'de',  // Германия - немецкий
            'FR': 'fr',  // Франция - французский
            'ES': 'es',  // Испания - испанский
            'IT': 'it',  // Италия - итальянский
            'CN': 'zh',  // Китай - китайский
            'JP': 'ja',  // Япония - японский
            'KR': 'ko',  // Корея - корейский
            'BR': 'pt',  // Бразилия - португальский
            'IN': 'hi',  // Индия - хинди
            'TR': 'tr',  // Турция - турецкий
            'PL': 'pl',  // Польша - польский
            'NL': 'nl',  // Нидерланды - голландский
            'US': 'en',  // США - английский
            'GB': 'en'   // Великобритания - английский
        };
        
        if (autoLanguages[countryCode]) {
            languageSelect.val(autoLanguages[countryCode]).trigger('change');
            updateUrlPreview(languageSelect[0]);
        }
    }
    
    // Модальное окно документации
    $('#documentation-btn').on('click', function() {
        $('#documentation-modal').fadeIn(200);
    });
    
    $('.modal-close, .modal-overlay').on('click', function(e) {
        if (e.target === this) {
            $('#documentation-modal').fadeOut(200);
        }
    });
    
    // Закрытие модального окна по ESC
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27) { // ESC
            $('#documentation-modal').fadeOut(200);
        }
    });
    
    // Инициализация при загрузке страницы
    $('.language-select').each(function() {
        updateUrlPreview(this);
    });
    
    // Обновляем URL для языка по умолчанию при загрузке
    var defaultSelect = $('select[name="default_language"]')[0];
    if (defaultSelect) {
        updateDefaultLanguagePreview(defaultSelect);
    }
    
    // Плавная анимация для кнопок
    $('.add-language-btn, .save-settings-btn').hover(
        function() {
            $(this).css('transform', 'translateY(-1px)');
        },
        function() {
            $(this).css('transform', 'translateY(0)');
        }
    );
    
    // Улучшенная анимация для строк
    $('.language-row').hover(
        function() {
            $(this).css('transform', 'translateX(2px)');
        },
        function() {
            $(this).css('transform', 'translateX(0)');
        }
    );
    
    // Клик по URL для открытия в новой вкладке
    $(document).on('click', '.col-url code, .site-info code, #default-language-url', function() {
        var url = $(this).text().trim();
        if (url && url.startsWith('http')) {
            window.open(url, '_blank');
        }
    });
});

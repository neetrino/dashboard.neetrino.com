/**
 * Neetrino Chat Widget JavaScript
 */
(function($) {
    'use strict';

    class NeetrinoChatWidget {
        constructor() {
            this.widget = $('#neetrino-chat-widget');
            this.mainButton = this.widget.find('.neetrino-chat-main-button');
            this.chatButtons = this.widget.find('.neetrino-chat-buttons');
            this.isOpen = false;
            this.settings = neetrino_chat.settings;
            this.singleChannel = neetrino_chat.single_channel;
            this.activeChannels = neetrino_chat.active_channels;
            
            this.init();
        }

        init() {
            // Инициализируем события
            this.bindEvents();
            
            // Проверяем видимость на устройствах
            this.checkDeviceVisibility();
            
            // Устанавливаем умное позиционирование (только для кнопок)
            this.setSmartPositioning();
            
            // Показываем виджет с задержкой и применяем настройки
            this.showWidget();
            
            // Позиционирование виджета теперь обрабатывается CSS медиазапросами
            // Поэтому applyMobilePositioning() больше не нужно вызывать
        }

        applySettings() {
            // Применяем цвет кнопки
            if (this.settings.color) {
                const colorRgb = this.hexToRgb(this.settings.color);
                
                this.mainButton.css({
                    'background': `linear-gradient(135deg, ${this.settings.color} 0%, ${this.darkenColor(this.settings.color, 20)} 100%)`,
                    'box-shadow': colorRgb ? `0 4px 20px rgba(${colorRgb.r}, ${colorRgb.g}, ${colorRgb.b}, 0.3)` : `0 4px 20px rgba(46, 204, 113, 0.3)`
                });
                
                // Применяем цвет пульсации
                if (colorRgb) {
                    const pulseColor = `rgba(${colorRgb.r}, ${colorRgb.g}, ${colorRgb.b}, 0.4)`;
                    this.widget.css('--pulse-color', pulseColor);
                }
                
                // Применяем цвет статуса (точка и текст "Онлайн")
                this.widget.css('--status-color', this.settings.color);
            }
            
            // Скрываем пульсацию если отключена
            if (!this.settings.pulse_effect) {
                this.widget.find('.neetrino-chat-pulse').hide();
            }
        }

        bindEvents() {
            // Клик по главной кнопке
            this.mainButton.on('click', (e) => {
                e.preventDefault();
                
                // Если активен только один канал - сразу открываем его
                if (this.singleChannel) {
                    this.openMessenger(this.singleChannel.type, this.singleChannel.contact);
                    this.trackClick(this.singleChannel.type, this.singleChannel.contact);
                } else {
                    // Стандартное поведение - открытие/закрытие меню
                    this.toggle();
                }
            });

            // Клики по кнопкам мессенджеров
            this.widget.find('.neetrino-chat-button').on('click', (e) => {
                e.preventDefault();
                const button = $(e.currentTarget);
                const type = button.data('type');
                const contact = button.data('contact');
                
                this.openMessenger(type, contact);
                this.trackClick(type, contact);
            });

            // Закрытие при клике вне виджета (только если не единственный канал)
            if (!this.singleChannel) {
                $(document).on('click', (e) => {
                    if (!this.widget.is(e.target) && this.widget.has(e.target).length === 0) {
                        this.close();
                    }
                });

                // Закрытие по Escape (только если не единственный канал)
                $(document).on('keydown', (e) => {
                    if (e.key === 'Escape' && this.isOpen) {
                        this.close();
                    }
                });
            }
        }

        toggle() {
            // Если активен только один канал, не открываем меню
            if (this.singleChannel) {
                return;
            }
            
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        }

        open() {
            // Если активен только один канал, не открываем меню
            if (this.singleChannel) {
                return;
            }
            
            // Пересчитываем позиции перед открытием
            this.setSmartPositioning();
            
            this.widget.addClass('active');
            this.isOpen = true;
            
            // Поворачиваем главную кнопку
            this.mainButton.css('transform', 'rotate(45deg)');
        }

        close() {
            // Если активен только один канал, не закрываем меню (его и нет)
            if (this.singleChannel) {
                return;
            }
            
            this.widget.removeClass('active');
            this.isOpen = false;
            
            // Возвращаем главную кнопку
            this.mainButton.css('transform', 'rotate(0deg)');
        }

        openMessenger(type, contact) {
            let url = '';
            
            switch (type) {
                case 'phone':
                    url = `tel:${contact}`;
                    break;
                    
                case 'whatsapp':
                    // Очищаем номер от всех символов кроме цифр
                    const cleanNumber = contact.replace(/\D/g, '');
                    url = `https://wa.me/${cleanNumber}`;
                    break;
                    
                case 'telegram':
                    // Убираем @ если есть
                    const username = contact.replace('@', '');
                    url = `https://t.me/${username}`;
                    break;
                    
                case 'viber':
                    url = `viber://add?number=${contact}`;
                    break;
                    
                case 'email':
                    url = `mailto:${contact}`;
                    break;
            }
            
            if (url) {
                // Для телефонных и email ссылок используем location.href для корректной работы на iOS
                if (type === 'phone' || type === 'email') {
                    window.location.href = url;
                } else {
                    window.open(url, '_blank');
                }
            }
        }

        trackClick(type, contact) {
            // Отправляем статистику на сервер
            $.ajax({
                url: neetrino_chat.ajax_url,
                type: 'POST',
                data: {
                    action: 'neetrino_chat_click',
                    type: type,
                    contact: contact,
                    nonce: neetrino_chat.nonce
                },
                success: function(response) {
                    console.log('Click tracked:', response);
                }
            });
        }

        showWidget() {
            // Применяем настройки сразу при показе
            this.applySettings();
            
            // Показываем виджет с установленным цветом
            setTimeout(() => {
                this.widget.addClass('initialized');
                this.widget.addClass('color-applied');
            }, 600); // 0.6 секунды задержка перед показом
        }

        checkDeviceVisibility() {
            const isMobile = window.innerWidth <= 768;
            
            if (isMobile && !this.settings.mobile_visible) {
                this.widget.addClass('hide-mobile');
                return;
            }
            
            if (!isMobile && !this.settings.desktop_visible) {
                this.widget.addClass('hide-desktop');
                return;
            }
            
            // Позиционирование теперь обрабатывается CSS медиазапросами
            // Поэтому applyMobilePositioning() больше не нужно
        }

        applyMobilePositioning() {
            const isMobile = window.innerWidth <= 768;
            
            if (isMobile) {
                // Получаем мобильную позицию
                const mobileX = parseFloat(this.widget.data('mobile-position-x')) || 90;
                const mobileY = parseFloat(this.widget.data('mobile-position-y')) || 90;
                
                // Учитываем безопасные зоны на мобильных устройствах
                const safeAreaTop = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--safe-area-inset-top') || '0');
                const safeAreaBottom = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--safe-area-inset-bottom') || '0');
                const safeAreaLeft = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--safe-area-inset-left') || '0');
                const safeAreaRight = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--safe-area-inset-right') || '0');
                
                // Вычисляем безопасную зону для позиционирования
                const safeWidth = window.innerWidth - safeAreaLeft - safeAreaRight;
                const safeHeight = window.innerHeight - safeAreaTop - safeAreaBottom;
                
                // Ограничиваем позицию в пределах 10-90% чтобы виджет не выходил за границы
                const limitedX = Math.max(10, Math.min(90, mobileX));
                const limitedY = Math.max(10, Math.min(90, mobileY));
                
                // Применяем мобильную позицию
                this.widget.addClass('mobile-positioning');
                this.widget.css({
                    'left': limitedX + '%',
                    'top': limitedY + '%',
                    '--mobile-position-x': limitedX + '%',
                    '--mobile-position-y': limitedY + '%'
                });
            } else {
                // Убираем мобильный класс и применяем десктопную позицию
                this.widget.removeClass('mobile-positioning');
                const desktopX = parseFloat(this.widget.data('position-x')) || 95;
                const desktopY = parseFloat(this.widget.data('position-y')) || 85;
                
                this.widget.css({
                    'left': desktopX + '%',
                    'top': desktopY + '%'
                });
            }
        }

        setSmartPositioning() {
            const isMobile = window.innerWidth <= 768;
            
            // Получаем позицию виджета в зависимости от устройства
            let positionX, positionY;
            
            if (isMobile) {
                positionX = parseFloat(this.widget.data('mobile-position-x')) || 90;
                positionY = parseFloat(this.widget.data('mobile-position-y')) || 90;
            } else {
                positionX = parseFloat(this.widget.data('position-x')) || 95;
                positionY = parseFloat(this.widget.data('position-y')) || 85;
            }
            
            const layout = this.widget.data('layout') || 'vertical';
            const chatButtons = this.widget.find('.neetrino-chat-buttons');
            
            // Размеры экрана
            const screenWidth = window.innerWidth;
            const screenHeight = window.innerHeight;
            
            // Размеры виджета
            const widgetWidth = this.widget.outerWidth();
            const widgetHeight = this.widget.outerHeight();
            
            // Вычисляем реальные координаты виджета
            const realX = (screenWidth * positionX) / 100;
            const realY = (screenHeight * positionY) / 100;
            
            // Сбрасываем все позиции
            chatButtons.css({
                'top': '',
                'bottom': '',
                'left': '',
                'right': ''
            });
            
            if (layout === 'vertical') {
                // Вертикальное расположение
                const buttonsHeight = this.widget.find('.neetrino-chat-button').length * (isMobile ? 60 : 70);
                
                // Проверяем, помещаются ли кнопки снизу
                if (realY + buttonsHeight < screenHeight - 50) {
                    // Открываем вниз
                    chatButtons.css('top', isMobile ? '60px' : '70px');
                } else {
                    // Открываем вверх
                    chatButtons.css('bottom', isMobile ? '60px' : '70px');
                }
                
                // Центрируем по горизонтали
                chatButtons.css({
                    'left': '50%',
                    'transform': 'translateX(-50%)'
                });
            } else {
                // Горизонтальное расположение (на мобильных становится вертикальным)
                if (isMobile) {
                    // На мобильных принудительно делаем вертикальным
                    const buttonsHeight = this.widget.find('.neetrino-chat-button').length * 60;
                    
                    if (realY + buttonsHeight < screenHeight - 50) {
                        chatButtons.css('top', '60px');
                    } else {
                        chatButtons.css('bottom', '60px');
                    }
                    
                    chatButtons.css({
                        'left': '50%',
                        'transform': 'translateX(-50%)'
                    });
                } else {
                    // На десктопе горизонтальное расположение
                    const buttonsWidth = this.widget.find('.neetrino-chat-button').length * 70;
                    
                    // Проверяем, помещаются ли кнопки справа
                    if (realX + buttonsWidth < screenWidth - 50) {
                        // Открываем вправо
                        chatButtons.css('left', '70px');
                    } else {
                        // Открываем влево
                        chatButtons.css('right', '70px');
                    }
                    
                    // Центрируем по вертикали
                    chatButtons.css({
                        'top': '50%',
                        'transform': 'translateY(-50%)'
                    });
                }
            }
        }

        darkenColor(color, percent) {
            // Функция для затемнения цвета
            const num = parseInt(color.replace("#", ""), 16);
            const amt = Math.round(2.55 * percent);
            const R = (num >> 16) + amt;
            const G = (num >> 8 & 0x00FF) + amt;
            const B = (num & 0x0000FF) + amt;
            
            return "#" + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
                (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
                (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1);
        }

        hexToRgb(hex) {
            // Функция для преобразования hex в RGB
            const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : null;
        }
    }

    // Экспорт класса для глобального использования
    window.NeetrinoChatWidget = NeetrinoChatWidget;

    // Инициализация при готовности DOM
    $(document).ready(function() {
        if ($('#neetrino-chat-widget').length && !window.neetrinoChatInstance) {
            window.neetrinoChatInstance = new NeetrinoChatWidget();
        }
    });

    // Реинициализация при изменении размера экрана
    $(window).on('resize', function() {
        if ($('#neetrino-chat-widget').length) {
            const widget = $('#neetrino-chat-widget');
            const isMobile = window.innerWidth <= 768;
            
            // Обновляем классы видимости
            widget.removeClass('hide-mobile hide-desktop');
            
            if (isMobile && !neetrino_chat.settings.mobile_visible) {
                widget.addClass('hide-mobile');
            }
            
            if (!isMobile && !neetrino_chat.settings.desktop_visible) {
                widget.addClass('hide-desktop');
            }
            
            // Пересчитываем позиции кнопок (не самого виджета)
            if (window.neetrinoChatInstance) {
                window.neetrinoChatInstance.setSmartPositioning();
            }
            
            // Позиционирование виджета обрабатывается CSS медиазапросами
        }
    });

})(jQuery);

<?php
/**
 * Module: App Manager
 * Description: Управление мобильными приложениями и автогенерация Privacy Policy
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_App_Manager {
    
    private $settings;
    
    public function __construct() {
        // Инициализация модуля только если он активен
        if (!Neetrino::is_module_active('app-manager')) {
            return;
        }
        
        // Загрузка настроек
        $default_settings = $this->get_default_settings();
        $saved_settings = get_option('neetrino_app_manager_settings', []);
        $this->settings = array_merge($default_settings, $saved_settings);
        
        // Хуки и действия модуля
        add_action('init', [$this, 'init']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // AJAX обработчики
        add_action('wp_ajax_neetrino_app_create_privacy_page', [$this, 'ajax_create_privacy_page']);
        add_action('wp_ajax_neetrino_app_delete_privacy_page', [$this, 'ajax_delete_privacy_page']);
        add_action('wp_ajax_neetrino_app_save_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_neetrino_app_preview_privacy', [$this, 'ajax_preview_privacy']);
    }
    
    /**
     * Инициализация модуля
     */
    public function init() {
        // Добавляем специальные стили для WebView страниц
        add_action('wp_head', [$this, 'add_webview_styles']);
        
        // Обработка специальных параметров для мобильных приложений
        add_action('template_redirect', [$this, 'handle_mobile_app_requests']);
    }
    
    /**
     * Подключение CSS и JS файлов
     */
    public function enqueue_scripts() {
        // Только в админке
        if (!is_admin()) {
            return;
        }
        
        wp_enqueue_style(
            'neetrino-app-manager-admin',
            plugin_dir_url(__FILE__) . 'assets/admin.css',
            [],
            '1.0.1'
        );
        
        wp_enqueue_script(
            'neetrino-app-manager-admin',
            plugin_dir_url(__FILE__) . 'assets/admin.js',
            ['jquery'],
            '1.0.1',
            true
        );
        
        // Передача данных в JavaScript
        wp_localize_script('neetrino-app-manager-admin', 'neetrino_app_manager', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('neetrino_app_manager_nonce'),
            'privacy_page_id' => $this->get_privacy_page_id(),
            'strings' => [
                'creating' => __('Создание страницы...', 'neetrino'),
                'deleting' => __('Удаление страницы...', 'neetrino'),
                'saving' => __('Сохранение...', 'neetrino'),
                'success' => __('Успешно!', 'neetrino'),
                'error' => __('Ошибка!', 'neetrino'),
                'confirm_delete' => __('Вы уверены, что хотите удалить страницу Privacy Policy?', 'neetrino')
            ]
        ]);
    }
    
    /**
     * Добавление стилей для WebView
     */
    public function add_webview_styles() {
        // Проверяем, это запрос из мобильного приложения
        if ($this->is_mobile_app_request()) {
            ?>
            <style>
                /* Скрываем элементы сайта для мобильного приложения */
                .site-header, .site-navigation, .site-footer,
                .wp-block-navigation, .wp-block-site-title,
                .wp-block-site-tagline, #wpadminbar {
                    display: none !important;
                }
                
                /* Адаптируем контент для WebView */
                .site-main, .entry-content {
                    padding: 20px !important;
                    margin: 0 !important;
                    max-width: 100% !important;
                }
                
                /* Мобильные стили для Privacy Policy */
                .app-privacy-policy {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    line-height: 1.6;
                    color: #333;
                }
                
                .app-privacy-buttons {
                    position: fixed;
                    bottom: 20px;
                    left: 20px;
                    right: 20px;
                    display: flex;
                    gap: 10px;
                }
                
                .app-privacy-button {
                    flex: 1;
                    padding: 15px;
                    border: none;
                    border-radius: 8px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }
                
                .app-privacy-accept {
                    background: #007AFF;
                    color: white;
                }
                
                .app-privacy-decline {
                    background: #FF3B30;
                    color: white;
                }
            </style>
            <?php
        }
    }
    
    /**
     * Обработка запросов из мобильных приложений
     */
    public function handle_mobile_app_requests() {
        // Проверяем специальные параметры
        if (isset($_GET['app_source']) && $_GET['app_source'] === 'flutter') {
            // Устанавливаем флаг мобильного приложения
            add_filter('body_class', function($classes) {
                $classes[] = 'mobile-app-webview';
                return $classes;
            });
        }
    }
    
    /**
     * Проверка запроса из мобильного приложения
     */
    private function is_mobile_app_request() {
        return isset($_GET['app_source']) && $_GET['app_source'] === 'flutter';
    }
    
    /**
     * Создание страницы Privacy Policy
     */
    public function create_privacy_page() {
        // Сначала удаляем старую страницу если она существует
        $this->delete_privacy_page();
        
        $page_title = 'Privacy Policy';
        $page_content = $this->generate_privacy_content();
        
        $page_data = [
            'post_title' => $page_title,
            'post_content' => $page_content,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => 'privacy-policy-app'
        ];
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id && !is_wp_error($page_id)) {
            // Принудительно устанавливаем правильный slug
            wp_update_post([
                'ID' => $page_id,
                'post_name' => 'privacy-policy-app'
            ]);
            
            // Сохраняем ID страницы в настройках
            $this->settings['privacy_page_id'] = $page_id;
            $this->save_settings();
            
            return $page_id;
        }
        
        return false;
    }
    
    /**
     * Удаление страницы Privacy Policy
     */
    public function delete_privacy_page() {
        $page_id = $this->get_privacy_page_id();
        
        if ($page_id) {
            $result = wp_delete_post($page_id, true);
            
            if ($result) {
                // Удаляем ID из настроек
                $this->settings['privacy_page_id'] = 0;
                $this->save_settings();
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Генерация контента Privacy Policy
     */
    private function generate_privacy_content() {
        $app_name = !empty($this->settings['app_name']) ? $this->settings['app_name'] : 'Наше приложение';
        $legal_entity = !empty($this->settings['legal_entity_name']) ? $this->settings['legal_entity_name'] : '';
        $contact_email = !empty($this->settings['contact_email']) ? $this->settings['contact_email'] : '';
        $legal_address = !empty($this->settings['legal_address']) ? $this->settings['legal_address'] : '';
        
        $content = '<div class="app-privacy-policy">';
        
        $content .= '<h1>Политика конфиденциальности ' . esc_html($app_name) . '</h1>';
        
        $content .= '<div class="privacy-meta">';
        $content .= '<p><strong>Дата вступления в силу:</strong> ' . date('d.m.Y') . '</p>';
        
        // Показываем правообладателя только если поле заполнено
        if (!empty($legal_entity)) {
            $content .= '<p><strong>Правообладатель:</strong> ' . esc_html($legal_entity) . '</p>';
        }
        
        // Показываем контакт только если поле заполнено
        if (!empty($contact_email)) {
            $content .= '<p><strong>Контакт:</strong> ' . esc_html($contact_email) . '</p>';
        }
        
        $content .= '</div>';
        
        // Введение
        $content .= '<h2>1. Введение</h2>';
        $content .= '<p>Настоящая Политика конфиденциальности описывает, как мы собираем, используем, храним и защищаем вашу персональную информацию при использовании мобильного приложения ' . esc_html($app_name) . ' (далее — "Приложение", "Сервис").</p>';
        
        $content .= '<p>Используя наше Приложение, вы соглашаетесь с условиями данной Политики конфиденциальности. Если вы не согласны с какими-либо условиями, пожалуйста, не используйте наше Приложение.</p>';
        
        // Информация которую мы собираем
        $content .= '<h2>2. Информация, которую мы собираем</h2>';
        
        if (!empty($this->settings['collects_personal_data'])) {
            $content .= '<h3>2.1. Персональная информация</h3>';
            $content .= '<p>Мы можем собирать следующие типы персональной информации:</p>';
            $content .= '<ul>';
            
            $data_types = $this->settings['personal_data_types'] ?? [];
            if (in_array('email', $data_types)) {
                $content .= '<li><strong>Адрес электронной почты</strong> — для создания аккаунта, отправки уведомлений и связи с вами</li>';
            }
            if (in_array('name', $data_types)) {
                $content .= '<li><strong>Имя и фамилия</strong> — для персонализации взаимодействия и идентификации пользователя</li>';
            }
            if (in_array('phone', $data_types)) {
                $content .= '<li><strong>Номер телефона</strong> — для верификации аккаунта и отправки SMS-уведомлений</li>';
            }
            if (in_array('location', $data_types)) {
                $content .= '<li><strong>Геолокационные данные</strong> — для предоставления локализованных услуг (только с вашего согласия)</li>';
            }
            if (in_array('device_info', $data_types)) {
                $content .= '<li><strong>Информация об устройстве</strong> — модель устройства, операционная система, уникальные идентификаторы устройства</li>';
            }
            if (in_array('usage_data', $data_types)) {
                $content .= '<li><strong>Данные об использовании</strong> — информация о том, как вы используете Приложение, включая время использования и взаимодействие с функциями</li>';
            }
            
            $content .= '</ul>';
        }
        
        // Автоматически собираемая информация
        $content .= '<h3>2.2. Автоматически собираемая информация</h3>';
        $content .= '<p>При использовании Приложения мы автоматически собираем определенную техническую информацию:</p>';
        $content .= '<ul>';
        $content .= '<li>IP-адрес и местоположение (приблизительное)</li>';
        $content .= '<li>Тип браузера и операционной системы</li>';
        $content .= '<li>Данные о производительности и ошибках Приложения</li>';
        $content .= '<li>Время и дата использования Приложения</li>';
        $content .= '<li>Уникальные идентификаторы устройства</li>';
        $content .= '</ul>';
        
        if (!empty($this->settings['uses_analytics'])) {
            $content .= '<h3>2.3. Аналитические данные</h3>';
            $content .= '<p>Мы используем сторонние сервисы аналитики для улучшения работы Приложения:</p>';
            $content .= '<ul>';
            
            $analytics = $this->settings['analytics_services'] ?? [];
            if (in_array('google_analytics', $analytics)) {
                $content .= '<li><strong>Google Analytics</strong> — для анализа поведения пользователей и улучшения функциональности</li>';
            }
            if (in_array('firebase', $analytics)) {
                $content .= '<li><strong>Firebase Analytics</strong> — для мониторинга производительности и аналитики приложения</li>';
            }
            if (in_array('yandex_metrica', $analytics)) {
                $content .= '<li><strong>Яндекс.Метрика</strong> — для веб-аналитики и исследования пользовательского опыта</li>';
            }
            if (in_array('facebook_analytics', $analytics)) {
                $content .= '<li><strong>Meta Analytics</strong> — для измерения эффективности рекламных кампаний</li>';
            }
            
            $content .= '</ul>';
        }
        
        // Как мы используем информацию
        $content .= '<h2>3. Как мы используем вашу информацию</h2>';
        $content .= '<p>Собранная информация используется для следующих целей:</p>';
        $content .= '<ul>';
        $content .= '<li><strong>Предоставление услуг</strong> — обеспечение функционирования Приложения и доступа к его возможностям</li>';
        $content .= '<li><strong>Улучшение Приложения</strong> — анализ использования для оптимизации производительности и добавления новых функций</li>';
        $content .= '<li><strong>Персонализация</strong> — адаптация контента и функций под ваши предпочтения</li>';
        $content .= '<li><strong>Коммуникация</strong> — отправка важных уведомлений, обновлений и ответов на ваши запросы</li>';
        $content .= '<li><strong>Безопасность</strong> — защита от мошенничества, злоупотреблений и нарушений безопасности</li>';
        $content .= '<li><strong>Соблюдение законодательства</strong> — выполнение правовых обязательств и защита наших законных интересов</li>';
        $content .= '</ul>';
        
        // Передача данных третьим лицам
        $content .= '<h2>4. Передача данных третьим лицам</h2>';
        $content .= '<p>Мы не продаем, не обмениваем и не передаем вашу персональную информацию третьим лицам без вашего согласия, за исключением случаев, описанных ниже:</p>';
        $content .= '<ul>';
        $content .= '<li><strong>Поставщики услуг</strong> — мы можем передавать данные проверенным третьим лицам, которые помогают нам в работе Приложения (хостинг, аналитика, поддержка клиентов)</li>';
        $content .= '<li><strong>Правовые требования</strong> — в случае требований государственных органов или судебных решений</li>';
        $content .= '<li><strong>Защита прав</strong> — для защиты наших прав, безопасности пользователей или расследования мошенничества</li>';
        $content .= '<li><strong>Деловые операции</strong> — в случае слияния, поглощения или продажи активов компании</li>';
        $content .= '</ul>';
        
        // Безопасность данных
        $content .= '<h2>5. Безопасность ваших данных</h2>';
        $content .= '<p>Мы применяем современные технические и организационные меры для защиты ваших персональных данных:</p>';
        $content .= '<ul>';
        $content .= '<li>Шифрование данных при передаче и хранении</li>';
        $content .= '<li>Регулярное обновление систем безопасности</li>';
        $content .= '<li>Ограниченный доступ к персональным данным только авторизованному персоналу</li>';
        $content .= '<li>Регулярные проверки безопасности и мониторинг систем</li>';
        $content .= '<li>Соблюдение международных стандартов безопасности</li>';
        $content .= '</ul>';
        $content .= '<p><strong>Важно:</strong> Несмотря на наши усилия, ни один метод передачи данных через Интернет или электронного хранения не является абсолютно безопасным. Мы не можем гарантировать 100% безопасность ваших данных.</p>';
        
        // Ваши права
        $content .= '<h2>6. Ваши права в отношении персональных данных</h2>';
        $content .= '<p>Вы имеете следующие права в отношении ваших персональных данных:</p>';
        $content .= '<ul>';
        $content .= '<li><strong>Право на доступ</strong> — получить информацию о том, какие данные мы о вас храним</li>';
        $content .= '<li><strong>Право на исправление</strong> — исправить неточные или неполные данные</li>';
        $content .= '<li><strong>Право на удаление</strong> — запросить удаление ваших персональных данных</li>';
        $content .= '<li><strong>Право на ограничение обработки</strong> — ограничить способы использования ваших данных</li>';
        $content .= '<li><strong>Право на переносимость</strong> — получить ваши данные в структурированном формате</li>';
        $content .= '<li><strong>Право на возражение</strong> — возразить против обработки ваших данных в определенных целях</li>';
        $content .= '<li><strong>Право на отзыв согласия</strong> — отозвать ранее данное согласие на обработку данных</li>';
        $content .= '</ul>';
        $content .= '<p>Для реализации этих прав свяжитесь с нами по адресу: <strong>' . esc_html($contact_email) . '</strong></p>';
        
        // Cookies и технологии отслеживания
        $content .= '<h2>7. Файлы cookie и технологии отслеживания</h2>';
        $content .= '<p>Мы используем файлы cookie и аналогичные технологии для:</p>';
        $content .= '<ul>';
        $content .= '<li>Обеспечения функционирования Приложения</li>';
        $content .= '<li>Запоминания ваших предпочтений и настроек</li>';
        $content .= '<li>Анализа использования Приложения</li>';
        $content .= '<li>Персонализации контента</li>';
        $content .= '</ul>';
        $content .= '<p>Вы можете настроить использование cookies в настройках вашего браузера или устройства.</p>';
        
        // Дети
        $content .= '<h2>8. Защита данных детей</h2>';
        $content .= '<p>Наше Приложение не предназначено для детей младше 13 лет (16 лет в ЕС). Мы сознательно не собираем персональную информацию от детей младше указанного возраста. Если вы являетесь родителем или опекуном и знаете, что ваш ребенок предоставил нам персональную информацию, свяжитесь с нами. Если мы обнаружим, что собрали персональную информацию от ребенка без согласия родителей, мы немедленно удалим эту информацию.</p>';
        
        // Международная передача данных
        $content .= '<h2>9. Международная передача данных</h2>';
        $content .= '<p>Ваши данные могут обрабатываться в странах, отличных от вашей страны проживания. Эти страны могут иметь иные законы о защите данных. Передавая данные в другие юрисдикции, мы обеспечиваем соответствующий уровень защиты ваших персональных данных.</p>';
        
        // Хранение данных
        $content .= '<h2>10. Срок хранения данных</h2>';
        $content .= '<p>Мы храним ваши персональные данные только в течение времени, необходимого для целей, указанных в данной Политике, или в соответствии с требованиями законодательства. Как правило, мы храним данные:</p>';
        $content .= '<ul>';
        $content .= '<li>Данные аккаунта — пока ваш аккаунт активен</li>';
        $content .= '<li>Данные об использовании — до 24 месяцев</li>';
        $content .= '<li>Данные поддержки — до 3 лет после закрытия обращения</li>';
        $content .= '</ul>';
        
        // Изменения в политике
        $content .= '<h2>11. Изменения в Политике конфиденциальности</h2>';
        $content .= '<p>Мы можем периодически обновлять данную Политику конфиденциальности. О существенных изменениях мы уведомим вас через:</p>';
        $content .= '<ul>';
        $content .= '<li>Уведомления в Приложении</li>';
        $content .= '<li>Электронную почту</li>';
        $content .= '<li>Размещение уведомления на нашем веб-сайте</li>';
        $content .= '</ul>';
        $content .= '<p>Рекомендуем регулярно проверять данную страницу для ознакомления с последними изменениями.</p>';
        
        // Контактная информация
        $content .= '<h2>12. Контактная информация</h2>';
        $content .= '<p>Если у вас есть вопросы, замечания или жалобы относительно данной Политики конфиденциальности или обработки ваших персональных данных, свяжитесь с нами:</p>';
        $content .= '<div class="contact-info">';
        
        // Показываем правообладателя только если поле заполнено
        if (!empty($legal_entity)) {
            $content .= '<p><strong>Правообладатель:</strong> ' . esc_html($legal_entity) . '</p>';
        }
        
        // Показываем email только если поле заполнено
        if (!empty($contact_email)) {
            $content .= '<p><strong>Email:</strong> ' . esc_html($contact_email) . '</p>';
        }
        
        // Показываем адрес только если поле заполнено
        if (!empty($legal_address)) {
            $content .= '<p><strong>Адрес:</strong> ' . esc_html($legal_address) . '</p>';
        }
        
        $content .= '</div>';
        
        // Согласие
        $content .= '<h2>13. Ваше согласие</h2>';
        $content .= '<p>Используя наше Приложение, вы соглашаетесь с условиями данной Политики конфиденциальности. Если вы не согласны с какими-либо условиями, пожалуйста, не используйте наше Приложение.</p>';
        
        $content .= '</div>';
        
        return $content;
    }
    
    /**
     * Получение ID страницы Privacy Policy
     */
    private function get_privacy_page_id() {
        return isset($this->settings['privacy_page_id']) ? (int)$this->settings['privacy_page_id'] : 0;
    }
    
    /**
     * Проверка существования страницы Privacy Policy
     */
    private function privacy_page_exists() {
        $page_id = $this->get_privacy_page_id();
        return $page_id && get_post($page_id) && get_post_status($page_id) === 'publish';
    }
    
    /**
     * Сохранение настроек
     */
    private function save_settings() {
        update_option('neetrino_app_manager_settings', $this->settings);
    }
    
    /**
     * AJAX: Создание страницы Privacy Policy
     */
    public function ajax_create_privacy_page() {
        check_ajax_referer('neetrino_app_manager_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Недостаточно прав']);
        }
        
        $page_id = $this->create_privacy_page();
        
        if ($page_id) {
            $page_url = get_permalink($page_id);
            wp_send_json_success([
                'message' => 'Страница Privacy Policy создана',
                'page_id' => $page_id,
                'page_url' => $page_url
            ]);
        } else {
            wp_send_json_error(['message' => 'Ошибка создания страницы']);
        }
    }
    
    /**
     * AJAX: Удаление страницы Privacy Policy
     */
    public function ajax_delete_privacy_page() {
        check_ajax_referer('neetrino_app_manager_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Недостаточно прав']);
        }
        
        $result = $this->delete_privacy_page();
        
        if ($result) {
            wp_send_json_success(['message' => 'Страница Privacy Policy удалена']);
        } else {
            wp_send_json_error(['message' => 'Ошибка удаления страницы']);
        }
    }
    
    /**
     * AJAX: Сохранение настроек
     */
    public function ajax_save_settings() {
        check_ajax_referer('neetrino_app_manager_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Недостаточно прав']);
        }
        
        // Получаем и валидируем данные
        $new_settings = [];
        
        // Основные настройки приложения
        $new_settings['app_name'] = sanitize_text_field($_POST['app_name'] ?? '');
        $new_settings['brand_name'] = sanitize_text_field($_POST['brand_name'] ?? '');
        $new_settings['company_name'] = sanitize_text_field($_POST['company_name'] ?? '');
        $new_settings['legal_entity_name'] = sanitize_text_field($_POST['legal_entity_name'] ?? '');
        $new_settings['contact_email'] = sanitize_email($_POST['contact_email'] ?? '');
        $new_settings['legal_address'] = sanitize_text_field($_POST['legal_address'] ?? '');
        
        // Настройки сбора данных
        $new_settings['collects_personal_data'] = !empty($_POST['collects_personal_data']);
        $new_settings['personal_data_types'] = isset($_POST['personal_data_types']) ? array_map('sanitize_text_field', $_POST['personal_data_types']) : [];
        
        // Аналитика
        $new_settings['uses_analytics'] = !empty($_POST['uses_analytics']);
        $new_settings['analytics_services'] = isset($_POST['analytics_services']) ? array_map('sanitize_text_field', $_POST['analytics_services']) : [];
        
        // Сохраняем ID страницы если он уже есть
        if (isset($this->settings['privacy_page_id'])) {
            $new_settings['privacy_page_id'] = $this->settings['privacy_page_id'];
        }
        
        // Обновляем настройки
        $this->settings = array_merge($this->get_default_settings(), $new_settings);
        $this->save_settings();
        
        // Если страница существует, обновляем её контент
        if ($this->privacy_page_exists()) {
            $page_id = $this->get_privacy_page_id();
            $updated_content = $this->generate_privacy_content();
            
            wp_update_post([
                'ID' => $page_id,
                'post_content' => $updated_content
            ]);
        }
        
        wp_send_json_success(['message' => 'Настройки сохранены']);
    }
    
    /**
     * AJAX: Предварительный просмотр Privacy Policy
     */
    public function ajax_preview_privacy() {
        check_ajax_referer('neetrino_app_manager_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Недостаточно прав']);
        }
        
        $preview_content = $this->generate_privacy_content();
        
        wp_send_json_success([
            'content' => $preview_content
        ]);
    }
    
    /**
     * Настройки по умолчанию
     */
    private function get_default_settings() {
        return [
            'app_name' => 'Мобильное приложение',
            'company_name' => 'Ваша компания',
            'contact_email' => get_option('admin_email'),
            'privacy_page_id' => 0,
            'collects_personal_data' => true,
            'personal_data_types' => ['email', 'name'],
            'uses_analytics' => true,
            'analytics_services' => ['google_analytics'],
            'gdpr_compliant' => true,
            'show_accept_buttons' => true
        ];
    }
    
    /**
     * Статический метод для админ-страницы
     */
    public static function admin_page() {
        // Подключаем класс админки
        require_once __DIR__ . '/admin.php';
        
        // Создаем экземпляр админки и отображаем
        $admin = new Neetrino_App_Manager_Admin();
        $admin->render();
    }
}

// Инициализация модуля
new Neetrino_App_Manager();

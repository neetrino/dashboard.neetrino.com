<?php
/**
 * Neetrino App Manager Admin
 * 
 * @package Neetrino
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_App_Manager_Admin {
    
    private $settings;
    
    public function __construct() {
        // Загрузка настроек
        $default_settings = $this->get_default_settings();
        $saved_settings = get_option('neetrino_app_manager_settings', []);
        $this->settings = array_merge($default_settings, $saved_settings);
    }
    
    /**
     * Отрисовка админ-страницы
     */
    public function render() {
        ?>
        <div class="wrap neetrino-dashboard">
            <!-- Современный хедер -->
            <div class="neetrino-modern-header">
                <div class="neetrino-header-content">
                    <div class="neetrino-header-icon">
                        <i class="fa-solid fa-mobile-screen-button"></i>
                    </div>
                    <div class="neetrino-header-text">
                        <h1>App Manager</h1>
                        <p>Управление мобильными приложениями и Privacy Policy</p>
                    </div>
                    <div class="neetrino-header-badge">
                        <span class="neetrino-version-badge">v1.0.0</span>
                    </div>
                </div>
            </div>
            
            <!-- Система табов -->
            <div class="neetrino-tabs-container">
                <div class="neetrino-tabs-nav">
                    <button class="neetrino-tab-button active" data-tab="privacy-policy">
                        <i class="fa-solid fa-shield-halved"></i>
                        Privacy Policy
                    </button>
                    <button class="neetrino-tab-button" data-tab="site-protection">
                        <i class="fa-solid fa-lock"></i>
                        Site Protection
                        <span class="neetrino-coming-soon">Soon</span>
                    </button>
                    <button class="neetrino-tab-button" data-tab="firebase-sync">
                        <i class="fa-solid fa-sync"></i>
                        Firebase Sync
                        <span class="neetrino-coming-soon">Soon</span>
                    </button>
                    <button class="neetrino-tab-button" data-tab="content-manager">
                        <i class="fa-solid fa-layer-group"></i>
                        Content Manager
                        <span class="neetrino-coming-soon">Soon</span>
                    </button>
                    <button class="neetrino-tab-button" data-tab="ux-optimizer">
                        <i class="fa-solid fa-magic"></i>
                        UX Optimizer
                        <span class="neetrino-coming-soon">Soon</span>
                    </button>
                    <button class="neetrino-tab-button" data-tab="biometric-auth">
                        <i class="fa-solid fa-fingerprint"></i>
                        Biometric Auth
                        <span class="neetrino-coming-soon">Soon</span>
                    </button>
                </div>
                
                <div class="neetrino-tabs-content">
                    
                    <!-- Tab: Privacy Policy -->
                    <div class="neetrino-tab-panel active" id="tab-privacy-policy">
            
            <div class="neetrino-modern-content" style="padding-top: 30px;">
                
                <!-- Privacy Policy Management -->
                <div class="neetrino-modern-card neetrino-card-primary">
                    <div class="neetrino-card-header">
                        <div class="neetrino-card-icon">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div class="neetrino-card-title">
                            <h2>Privacy Policy Generator</h2>
                            <p>Автоматическая генерация для App Store и Google Play</p>
                        </div>
                        <div class="neetrino-card-status">
                            <?php if ($this->privacy_page_exists()): ?>
                                <span class="neetrino-status-dot neetrino-status-success"></span>
                                <span class="neetrino-status-text">Активна</span>
                            <?php else: ?>
                                <span class="neetrino-status-dot neetrino-status-warning"></span>
                                <span class="neetrino-status-text">Не создана</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="neetrino-card-content">
                        <?php if ($this->privacy_page_exists()): ?>
                            <div class="neetrino-success-block">
                                <div class="neetrino-success-icon">
                                    <i class="fa-solid fa-check-circle"></i>
                                </div>
                                <div class="neetrino-success-content">
                                    <h3>Privacy Policy готова!</h3>
                                    <p>Страница создана и готова для использования в магазинах приложений</p>
                                    <div class="neetrino-url-display">
                                        <span class="neetrino-url-label">App Store URL:</span>
                                        <code class="neetrino-url-code"><?php echo esc_url(get_permalink($this->get_privacy_page_id())); ?></code>
                                        <button class="neetrino-copy-btn" data-url="<?php echo esc_url(get_permalink($this->get_privacy_page_id())); ?>">
                                            <i class="fa-solid fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="neetrino-action-buttons">
                                <a href="<?php echo esc_url(get_permalink($this->get_privacy_page_id())); ?>" 
                                   target="_blank" class="neetrino-btn neetrino-btn-outline">
                                    <i class="fa-solid fa-external-link-alt"></i>
                                    Просмотр
                                </a>
                                <button id="delete-privacy-page" class="neetrino-btn neetrino-btn-danger">
                                    <i class="fa-solid fa-trash"></i>
                                    Удалить
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="neetrino-empty-state">
                                <div class="neetrino-empty-icon">
                                    <i class="fa-solid fa-file-plus"></i>
                                </div>
                                <h3>Создайте Privacy Policy</h3>
                                <p>Автоматически сгенерируется страница политики конфиденциальности на основе ваших настроек</p>
                                <button id="create-privacy-page" class="neetrino-btn neetrino-btn-primary neetrino-btn-large">
                                    <i class="fa-solid fa-magic"></i>
                                    Создать страницу
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- App Settings -->
                <div class="neetrino-modern-card">
                    <div class="neetrino-card-header">
                        <div class="neetrino-card-icon">
                            <i class="fa-solid fa-cog"></i>
                        </div>
                        <div class="neetrino-card-title">
                            <h2>Настройки приложения</h2>
                            <p>Основная информация для генерации Privacy Policy</p>
                        </div>
                    </div>
                    
                    <div class="neetrino-card-content">
                        <form id="app-manager-settings-form" class="neetrino-modern-form">
                            <?php wp_nonce_field('neetrino_app_manager_nonce', 'nonce'); ?>
                            
                            <!-- Информационное сообщение -->
                            <div class="neetrino-info-message" style="background: #e7f3ff; border-left: 4px solid #2196f3; padding: 16px; margin-bottom: 24px; border-radius: 8px;">
                                <h4 style="margin: 0 0 8px 0; color: #1976d2;"><i class="fa-solid fa-info-circle"></i> Как правильно заполнить</h4>
                                <p style="margin: 0; color: #424242; line-height: 1.5;">
                                    <strong>Правообладатель:</strong> Укажите вашу компанию-разработчика (например, Neetrino LLC)<br>
                                    <strong>Контакт:</strong> Ваш email для связи по вопросам Privacy Policy<br>
                                    <strong>Адрес:</strong> Юридический адрес вашей компании
                                </p>
                            </div>
                            
                            <!-- Basic Info -->
                            <div class="neetrino-form-section">
                                <h3><i class="fa-solid fa-info-circle"></i> Основная информация</h3>
                                <div class="neetrino-form-grid">
                                    <div class="neetrino-form-field">
                                        <label for="app_name">Название приложения</label>
                                        <input type="text" id="app_name" name="app_name" 
                                               value="<?php echo esc_attr($this->settings['app_name']); ?>" 
                                               placeholder="Мобильное приложение" />
                                    </div>
                                    
                                    <div class="neetrino-form-field">
                                        <label for="legal_entity_name">Правообладатель</label>
                                        <input type="text" id="legal_entity_name" name="legal_entity_name" 
                                               value="<?php echo esc_attr($this->settings['legal_entity_name'] ?? ''); ?>" 
                                               placeholder="Например: Neetrino LLC" />
                                        <small style="color: #666;">Ваша компания - владелец аккаунта в App Store/Google Play</small>
                                    </div>
                                    
                                    <div class="neetrino-form-field">
                                        <label for="contact_email">Email для связи</label>
                                        <input type="email" id="contact_email" name="contact_email" 
                                               value="<?php echo esc_attr($this->settings['contact_email']); ?>" 
                                               placeholder="support@example.com" />
                                    </div>
                                    
                                    <div class="neetrino-form-field">
                                        <label for="legal_address">Юридический адрес</label>
                                        <input type="text" id="legal_address" name="legal_address" 
                                               value="<?php echo esc_attr($this->settings['legal_address'] ?? ''); ?>" 
                                               placeholder="Полный юридический адрес компании" />
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Data Collection -->
                            <div class="neetrino-form-section">
                                <h3><i class="fa-solid fa-database"></i> Сбор персональных данных</h3>
                                
                                <div class="neetrino-toggle-field">
                                    <label class="neetrino-toggle">
                                        <input type="checkbox" name="collects_personal_data" 
                                               <?php checked($this->settings['collects_personal_data']); ?> />
                                        <span class="neetrino-toggle-slider"></span>
                                    </label>
                                    <div class="neetrino-toggle-label">
                                        <strong>Приложение собирает персональные данные</strong>
                                        <span>Включите если ваше приложение собирает любую личную информацию</span>
                                    </div>
                                </div>
                                
                                <div class="neetrino-conditional-block" data-condition="collects_personal_data">
                                    <div class="neetrino-checkbox-grid">
                                        <?php 
                                        $data_types = [
                                            'email' => ['label' => 'Email адрес', 'icon' => 'fa-solid fa-envelope'],
                                            'name' => ['label' => 'Имя и фамилия', 'icon' => 'fa-solid fa-user'],
                                            'phone' => ['label' => 'Номер телефона', 'icon' => 'fa-solid fa-phone'],
                                            'location' => ['label' => 'Геолокация', 'icon' => 'fa-solid fa-map-marker-alt'],
                                            'device_info' => ['label' => 'Информация об устройстве', 'icon' => 'fa-solid fa-mobile-screen-button'],
                                            'usage_data' => ['label' => 'Данные использования', 'icon' => 'fa-solid fa-chart-bar']
                                        ];
                                        
                                        foreach ($data_types as $key => $data):
                                            $checked = in_array($key, $this->settings['personal_data_types'] ?? []);
                                        ?>
                                        <label class="neetrino-checkbox-card <?php echo $checked ? 'checked' : ''; ?>">
                                            <input type="checkbox" name="personal_data_types[]" 
                                                   value="<?php echo esc_attr($key); ?>" 
                                                   <?php checked($checked); ?> />
                                            <div class="neetrino-checkbox-content">
                                                <i class="<?php echo esc_attr($data['icon']); ?>"></i>
                                                <span><?php echo esc_html($data['label']); ?></span>
                                            </div>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Analytics -->
                            <div class="neetrino-form-section">
                                <h3><i class="fa-solid fa-chart-line"></i> Аналитические сервисы</h3>
                                
                                <div class="neetrino-toggle-field">
                                    <label class="neetrino-toggle">
                                        <input type="checkbox" name="uses_analytics" 
                                               <?php checked($this->settings['uses_analytics']); ?> />
                                        <span class="neetrino-toggle-slider"></span>
                                    </label>
                                    <div class="neetrino-toggle-label">
                                        <strong>Приложение использует аналитику</strong>
                                        <span>Google Analytics, Firebase и другие сервисы аналитики</span>
                                    </div>
                                </div>
                                
                                <div class="neetrino-conditional-block" data-condition="uses_analytics">
                                    <div class="neetrino-checkbox-grid">
                                        <?php 
                                        $analytics_services = [
                                            'google_analytics' => ['label' => 'Google Analytics', 'icon' => 'fa-brands fa-google'],
                                            'firebase' => ['label' => 'Firebase Analytics', 'icon' => 'fa-solid fa-fire'],
                                            'yandex_metrica' => ['label' => 'Яндекс.Метрика', 'icon' => 'fa-brands fa-yandex'],
                                            'facebook_analytics' => ['label' => 'Meta Analytics', 'icon' => 'fa-brands fa-meta']
                                        ];
                                        
                                        foreach ($analytics_services as $key => $service):
                                            $checked = in_array($key, $this->settings['analytics_services'] ?? []);
                                        ?>
                                        <label class="neetrino-checkbox-card <?php echo $checked ? 'checked' : ''; ?>">
                                            <input type="checkbox" name="analytics_services[]" 
                                                   value="<?php echo esc_attr($key); ?>" 
                                                   <?php checked($checked); ?> />
                                            <div class="neetrino-checkbox-content">
                                                <i class="<?php echo esc_attr($service['icon']); ?>"></i>
                                                <span><?php echo esc_html($service['label']); ?></span>
                                            </div>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="neetrino-form-actions">
                                <button type="submit" class="neetrino-btn neetrino-btn-primary">
                                    <i class="fa-solid fa-save"></i>
                                    Сохранить настройки
                                </button>
                                <button type="button" id="preview-privacy" class="neetrino-btn neetrino-btn-outline">
                                    <i class="fa-solid fa-eye"></i>
                                    Предпросмотр
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            </div>
                    
                    <!-- Tab: Site Protection (Coming Soon) -->
                    <div class="neetrino-tab-panel" id="tab-site-protection">
                        <div class="neetrino-coming-soon-content" style="margin-top: 30px;">
                            <div class="neetrino-coming-soon-icon">
                                <i class="fa-solid fa-lock"></i>
                            </div>
                            <h3>Site Protection</h3>
                            <p>Защита сайта для доступа только через мобильное приложение</p>
                            <div class="neetrino-features-preview">
                                <ul>
                                    <li><i class="fa-solid fa-check"></i> User-Agent проверка</li>
                                    <li><i class="fa-solid fa-check"></i> Секретные токены</li>
                                    <li><i class="fa-solid fa-check"></i> Настраиваемые заглушки</li>
                                    <li><i class="fa-solid fa-check"></i> IP whitelist</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Firebase Sync (Coming Soon) -->
                    <div class="neetrino-tab-panel" id="tab-firebase-sync">
                        <div class="neetrino-coming-soon-content" style="margin-top: 30px;">
                            <div class="neetrino-coming-soon-icon">
                                <i class="fa-solid fa-sync"></i>
                            </div>
                            <h3>Firebase Synchronization</h3>
                            <p>Двусторонняя синхронизация между Flutter приложением, Firebase и WordPress</p>
                            <div class="neetrino-features-preview">
                                <ul>
                                    <li><i class="fa-solid fa-check"></i> Синхронизация пользователей</li>
                                    <li><i class="fa-solid fa-check"></i> Единая система уведомлений</li>
                                    <li><i class="fa-solid fa-check"></i> Синхронизация данных приложения</li>
                                    <li><i class="fa-solid fa-check"></i> Firebase Auth интеграция</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Content Manager (Coming Soon) -->
                    <div class="neetrino-tab-panel" id="tab-content-manager">
                        <div class="neetrino-coming-soon-content" style="margin-top: 30px;">
                            <div class="neetrino-coming-soon-icon">
                                <i class="fa-solid fa-layer-group"></i>
                            </div>
                            <h3>Mobile Content Manager</h3>
                            <p>Умное управление контентом для мобильных приложений</p>
                            <div class="neetrino-features-preview">
                                <ul>
                                    <li><i class="fa-solid fa-check"></i> Адаптивные блоки контента</li>
                                    <li><i class="fa-solid fa-check"></i> Mobile-first дизайн</li>
                                    <li><i class="fa-solid fa-check"></i> App-only страницы</li>
                                    <li><i class="fa-solid fa-check"></i> Таргетинг по устройствам</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: UX Optimizer (Coming Soon) -->
                    <div class="neetrino-tab-panel" id="tab-ux-optimizer">
                        <div class="neetrino-coming-soon-content" style="margin-top: 30px;">
                            <div class="neetrino-coming-soon-icon">
                                <i class="fa-solid fa-magic"></i>
                            </div>
                            <h3>Mobile UX Optimizer</h3>
                            <p>Оптимизация пользовательского опыта для WebView</p>
                            <div class="neetrino-features-preview">
                                <ul>
                                    <li><i class="fa-solid fa-check"></i> Отключение zoom функций</li>
                                    <li><i class="fa-solid fa-check"></i> Touch оптимизация</li>
                                    <li><i class="fa-solid fa-check"></i> Performance режим</li>
                                    <li><i class="fa-solid fa-check"></i> Блокировка нежелательных функций</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Biometric Auth (Coming Soon) -->
                    <div class="neetrino-tab-panel" id="tab-biometric-auth">
                        <div class="neetrino-coming-soon-content" style="margin-top: 30px;">
                            <div class="neetrino-coming-soon-icon">
                                <i class="fa-solid fa-fingerprint"></i>
                            </div>
                            <h3>Biometric Security</h3>
                            <p>Биометрическая авторизация и enhanced безопасность</p>
                            <div class="neetrino-features-preview">
                                <ul>
                                    <li><i class="fa-solid fa-check"></i> Touch ID / Face ID вход</li>
                                    <li><i class="fa-solid fa-check"></i> Multi-factor authentication</li>
                                    <li><i class="fa-solid fa-check"></i> Device binding</li>
                                    <li><i class="fa-solid fa-check"></i> Enhanced security layers</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
                
            </div>
        </div>
        
        <!-- Modern Preview Modal -->
        <div id="privacy-preview-modal" class="neetrino-modern-modal" style="display: none;">
            <div class="neetrino-modal-backdrop"></div>
            <div class="neetrino-modal-container">
                <div class="neetrino-modal-header">
                    <h3><i class="fa-solid fa-eye"></i> Предпросмотр Privacy Policy</h3>
                    <button type="button" class="neetrino-modal-close">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <div class="neetrino-modal-body">
                    <div id="privacy-preview-content">
                        <!-- Контент будет загружен через AJAX -->
                    </div>
                </div>
            </div>
        </div>
        <?php
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
     * Настройки по умолчанию
     */
    private function get_default_settings() {
        return [
            'app_name' => 'Мобильное приложение',
            'legal_entity_name' => '',
            'contact_email' => get_option('admin_email'),
            'legal_address' => '',
            'privacy_page_id' => 0,
            'collects_personal_data' => true,
            'personal_data_types' => ['email', 'name'],
            'uses_analytics' => true,
            'analytics_services' => ['google_analytics'],
            'gdpr_compliant' => true,
            'show_accept_buttons' => true
        ];
    }
}

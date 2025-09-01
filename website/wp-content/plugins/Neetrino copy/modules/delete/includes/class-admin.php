<?php
/**
 * Admin Interface for Delete Product Module
 * 
 * Handles the admin page rendering and UI
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Delete_Admin {
    
    /**
     * Рендерит страницу администратора модуля
     */
    public static function render_admin_page() {
        // Проверяем что WooCommerce активен
        $woocommerce_active = class_exists('WooCommerce');
        
        // Получаем статистику
        $stats = Neetrino_Delete_Stats::get_all_stats();
        $total_items = Neetrino_Delete_Stats::get_total_items_count();
        ?>
        <div class="wrap neetrino-dashboard">
            <div class="neetrino-content">
                <div class="neetrino-delete-main-card">
                    <?php self::render_module_header($woocommerce_active); ?>
                    
                    <?php if ($total_items > 0): ?>
                        <?php self::render_cleanup_options($stats, $woocommerce_active); ?>
                        <?php self::render_danger_zone($total_items, $woocommerce_active); ?>
                        <?php self::render_progress_containers(); ?>
                    <?php else: ?>
                        <?php self::render_empty_state($woocommerce_active); ?>
                    <?php endif; ?>
                </div>
                
                <?php self::render_info_modal(); ?>
                <?php self::render_settings_modal(); ?>
            </div>
        </div>
        
        <?php 
        self::render_scripts();
        self::render_styles();
    }
    
    /**
     * Хедер модуля
     */
    private static function render_module_header($woocommerce_active = true) {
        ?>
        <div class="neetrino-delete-header">
            <div class="neetrino-module-title">
                <div class="module-icon">
                    <span class="dashicons dashicons-trash"></span>
                </div>                <div class="module-info">
                    <h2>Delete</h2>
                    <p class="module-description">
                        Безопасное удаление страниц WordPress, записей и другого контента
                        <?php if ($woocommerce_active): ?>
                            + товаров и данных WooCommerce
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <div class="header-buttons">
                <button type="button" id="neetrino-settings-btn" class="neetrino-settings-button">
                    <span class="dashicons dashicons-admin-settings"></span>
                    Настройки удаления
                </button>
                <button type="button" id="neetrino-info-btn" class="neetrino-info-button">
                    <span class="dashicons dashicons-info"></span>
                    Информация
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Опции очистки
     */
    private static function render_cleanup_options($stats, $woocommerce_active = true) {
        ?>        <div class="neetrino-cleanup-options">
            <?php if ($woocommerce_active): ?>
            <!-- Секция WooCommerce -->
            <div class="cleanup-section">                <div class="section-header">
                    <div class="section-icon woocommerce-icon">
                        <span class="dashicons dashicons-cart"></span>
                    </div>
                    <div class="section-title">
                        <h3>WooCommerce данные</h3>
                    </div>
                    <div class="section-line woocommerce-line"></div>
                </div>
                  <div class="cleanup-grid">
                <?php if ($stats['products'] > 0): ?>
                <div class="cleanup-option woocommerce-item">
                    <div class="cleanup-icon">
                        <span class="dashicons dashicons-products"></span>
                    </div>                    <div class="cleanup-content">
                        <h4>Товары</h4>
                        <p><?php echo $stats['products']; ?> товаров</p>
                    </div>
                    <button type="button" class="cleanup-btn" data-action="delete_all_products" data-confirm="удалить все товары">
                        Удалить
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if ($stats['categories'] > 0): ?>
                <div class="cleanup-option woocommerce-item">
                    <div class="cleanup-icon">
                        <span class="dashicons dashicons-category"></span>
                    </div>
                    <div class="cleanup-content">
                        <h4>Категории</h4>
                        <p><?php echo $stats['categories']; ?> категорий</p>
                    </div>
                    <button type="button" class="cleanup-btn" data-action="delete_product_categories" data-confirm="удалить все категории товаров">
                        Удалить
                    </button>
                </div>
                <?php endif; ?>
                  <?php if ($stats['tags'] > 0): ?>
                <div class="cleanup-option woocommerce-item">
                    <div class="cleanup-icon">
                        <span class="dashicons dashicons-tag"></span>
                    </div>
                    <div class="cleanup-content">
                        <h4>Теги</h4>
                        <p><?php echo $stats['tags']; ?> тегов</p>
                    </div>
                    <button type="button" class="cleanup-btn" data-action="delete_product_tags" data-confirm="удалить все теги товаров">
                        Удалить
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if ($stats['attributes'] > 0): ?>
                <div class="cleanup-option woocommerce-item">
                    <div class="cleanup-icon">
                        <span class="dashicons dashicons-admin-settings"></span>
                    </div>
                    <div class="cleanup-content">
                        <h4>Атрибуты</h4>
                        <p><?php echo $stats['attributes']; ?> атрибутов</p>
                    </div>
                    <button type="button" class="cleanup-btn" data-action="delete_product_attributes" data-confirm="удалить все атрибуты товаров">
                        Удалить
                    </button>
                </div>
                <?php endif; ?>                <?php if ($stats['orders'] > 0): ?>
                <div class="cleanup-option woocommerce-item">
                    <div class="cleanup-icon">
                        <span class="dashicons dashicons-clipboard"></span>
                    </div>
                    <div class="cleanup-content">
                        <h4>Заказы</h4>
                        <p><?php echo $stats['orders']; ?> заказов</p>
                    </div>
                    <button type="button" class="cleanup-btn" data-action="delete_orders" data-confirm="удалить все заказы">
                        Удалить
                    </button>
                </div>
                <?php endif; ?>
                  <?php if ($stats['coupons'] > 0): ?>
                <div class="cleanup-option woocommerce-item">
                    <div class="cleanup-icon">
                        <span class="dashicons dashicons-tickets-alt"></span>
                    </div>
                    <div class="cleanup-content">
                        <h4>Купоны</h4>
                        <p><?php echo $stats['coupons']; ?> купонов</p>
                    </div>
                    <button type="button" class="cleanup-btn" data-action="delete_coupons" data-confirm="удалить все купоны">
                        Удалить
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if ($stats['reviews'] > 0): ?>
                <div class="cleanup-option woocommerce-item">
                    <div class="cleanup-icon">
                        <span class="dashicons dashicons-star-filled"></span>
                    </div>
                    <div class="cleanup-content">
                        <h4>Отзывы</h4>
                        <p><?php echo $stats['reviews']; ?> отзывов</p>
                    </div>
                    <button type="button" class="cleanup-btn" data-action="delete_reviews" data-confirm="удалить все отзывы о товарах">
                        Удалить
                    </button>
                </div>                <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Секция WordPress -->
            <div class="cleanup-section">                <div class="section-header">
                    <div class="section-icon wordpress-icon">
                        <span class="dashicons dashicons-wordpress-alt"></span>
                    </div>
                    <div class="section-title">
                        <h3>WordPress данные</h3>
                    </div>
                    <div class="section-line wordpress-line"></div>
                </div>
                  <div class="cleanup-grid">
                <?php if ($stats['pages'] > 0): ?>
                <div class="cleanup-option wordpress-item">
                    <div class="cleanup-icon">
                        <span class="dashicons dashicons-admin-page"></span>
                    </div>
                    <div class="cleanup-content">
                        <h4>Страницы</h4>
                        <p><?php echo $stats['pages']; ?> страниц</p>
                    </div>
                    <button type="button" class="cleanup-btn" data-action="delete_pages" data-confirm="удалить все страницы WordPress">
                        Удалить
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if ($stats['posts'] > 0): ?>
                <div class="cleanup-option wordpress-item">
                    <div class="cleanup-icon">
                        <span class="dashicons dashicons-admin-post"></span>
                    </div>
                    <div class="cleanup-content">
                        <h4>Записи блога</h4>
                        <p><?php echo $stats['posts']; ?> записей</p>
                    </div>
                    <button type="button" class="cleanup-btn" data-action="delete_posts" data-confirm="удалить все записи блога">
                        Удалить
                    </button>
                </div>
                <?php endif; ?>
                  <?php if ($stats['comments'] > 0): ?>
                <div class="cleanup-option wordpress-item">
                    <div class="cleanup-icon">
                        <span class="dashicons dashicons-admin-comments"></span>
                    </div>
                    <div class="cleanup-content">
                        <h4>Комментарии</h4>
                        <p><?php echo $stats['comments']; ?> комментариев</p>
                    </div>
                    <button type="button" class="cleanup-btn" data-action="delete_all_comments" data-confirm="удалить все комментарии">
                        Удалить
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if ($stats['spam_comments'] > 0): ?>
                <div class="cleanup-option wordpress-item">
                    <div class="cleanup-icon">
                        <span class="dashicons dashicons-dismiss"></span>
                    </div>
                    <div class="cleanup-content">
                        <h4>Спам-комментарии</h4>
                        <p><?php echo $stats['spam_comments']; ?> спам-комментариев</p>
                    </div>
                    <button type="button" class="cleanup-btn" data-action="delete_spam_comments" data-confirm="удалить все спам-комментарии">
                        Удалить
                    </button>
                </div>
                <?php endif; ?>
                  <?php if ($stats['media'] > 0): ?>
                <div class="cleanup-option wordpress-item">
                    <div class="cleanup-icon">
                        <span class="dashicons dashicons-admin-media"></span>
                    </div>
                    <div class="cleanup-content">
                        <h4>Медиафайлы</h4>
                        <p><?php echo $stats['media']; ?> файлов</p>
                    </div>
                    <button type="button" class="cleanup-btn" data-action="delete_media_files" data-confirm="удалить все медиафайлы">
                        Удалить
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if ($stats['drafts'] > 0): ?>
                <div class="cleanup-option wordpress-item">
                    <div class="cleanup-icon">
                        <span class="dashicons dashicons-edit"></span>
                    </div>
                    <div class="cleanup-content">
                        <h4>Черновики</h4>
                        <p><?php echo $stats['drafts']; ?> черновиков</p>
                    </div>
                    <button type="button" class="cleanup-btn" data-action="delete_drafts" data-confirm="удалить все черновики">
                        Удалить
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if ($stats['trash'] > 0): ?>
                <div class="cleanup-option wordpress-item">
                    <div class="cleanup-icon">
                        <span class="dashicons dashicons-trash"></span>
                    </div>
                    <div class="cleanup-content">
                        <h4>Корзина</h4>
                        <p><?php echo $stats['trash']; ?> в корзине</p>
                    </div>
                    <button type="button" class="cleanup-btn" data-action="delete_trash" data-confirm="очистить корзину">
                        Удалить
                    </button>
                </div>
                <?php endif; ?>
                  <?php if ($stats['unused_terms'] > 0): ?>
                <div class="cleanup-option wordpress-item">
                    <div class="cleanup-icon">
                        <span class="dashicons dashicons-category"></span>
                    </div>
                    <div class="cleanup-content">
                        <h4>Неиспользуемые теги</h4>
                        <p><?php echo $stats['unused_terms']; ?> неиспользуемых</p>
                    </div>
                    <button type="button" class="cleanup-btn" data-action="delete_unused_tags" data-confirm="удалить неиспользуемые теги и категории">
                        Удалить
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if ($stats['transients'] > 0): ?>
                <div class="cleanup-option wordpress-item">
                    <div class="cleanup-icon">
                        <span class="dashicons dashicons-performance"></span>
                    </div>                    <div class="cleanup-content">                        <h4>Кэш данные</h4>
                        <p><?php echo $stats['transients']; ?> временных данных</p>
                    </div>                    <button type="button" class="cleanup-btn" data-action="delete_transients" data-confirm="очистить кэш данные">
                        Удалить
                    </button>
                </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
      /**
     * Опасная зона с кнопками полной очистки
     */
    private static function render_danger_zone($total_items, $woocommerce_active = true) {
        ?>        <div class="neetrino-danger-zone">
            <h3>⚠️ Опасная зона</h3>
            <p>Полное удаление данных одной кнопкой</p>
            <div class="danger-buttons">
                <?php if ($woocommerce_active): ?>
                <button type="button" id="neetrino-clean-all-btn" class="neetrino-danger-button">
                    <span class="dashicons dashicons-cart"></span>
                    Удалить WooCommerce
                </button>
                <?php endif; ?>
                <button type="button" id="neetrino-clean-wordpress-btn" class="neetrino-danger-button">
                    <span class="dashicons dashicons-wordpress-alt"></span>
                    Удалить WordPress
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Контейнеры для прогресса и результатов
     */
    private static function render_progress_containers() {
        ?>
        <div id="neetrino-cleanup-progress" class="neetrino-progress-container" style="display: none;">
            <div class="progress-text">Выполняется очистка...</div>
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
        </div>
        
        <div id="neetrino-cleanup-result" class="neetrino-result-container" style="display: none;"></div>
        <?php
    }
    
    /**
     * Пустое состояние
     */
    private static function render_empty_state($woocommerce_active = true) {
        ?>        <div class="neetrino-empty-state">
            <div class="neetrino-empty-icon">
                <span class="dashicons dashicons-admin-tools"></span>
            </div>
            <h3>Нет данных для очистки</h3>
            <p>На вашем сайте нет данных WordPress, которые можно было бы удалить.</p>
            <?php if ($woocommerce_active): ?>
                <p>Также нет данных WooCommerce для очистки.</p>
            <?php else: ?>
                <p>Для работы с данными WooCommerce необходимо установить и активировать плагин.</p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Модальное окно с информацией
     */
    private static function render_info_modal() {
        ?>
        <!-- Модальное окно с информацией -->
        <div id="neetrino-info-modal" class="neetrino-modal" style="display: none;">
            <div class="neetrino-modal-overlay" id="neetrino-modal-overlay"></div>
            <div class="neetrino-modal-content">
                <div class="neetrino-modal-header">
                    <h3>Дополнительная информация</h3>
                    <button type="button" class="neetrino-modal-close" id="neetrino-modal-close">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                </div>                <div class="neetrino-modal-body">
                    <div class="neetrino-info-item">
                        <div class="info-icon" style="background: rgba(150, 88, 138, 0.1); color: #96588a;">
                            <span class="dashicons dashicons-cart"></span>
                        </div>                        <div class="info-content">
                            <h4>WooCommerce данные</h4>
                            <p>Товары, категории, теги, атрибуты, заказы, купоны и отзывы. Полная очистка интернет-магазина.</p>
                        </div>
                    </div>
                    
                    <div class="neetrino-info-item">
                        <div class="info-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                            <span class="dashicons dashicons-wordpress-alt"></span>
                        </div>                        <div class="info-content">
                            <h4>WordPress данные</h4>
                            <p>Страницы, записи блога, комментарии, медиафайлы, черновики и кэш данные.</p>
                        </div>
                    </div>
                    
                    <div class="neetrino-info-item">
                        <div class="info-icon">
                            <span class="dashicons dashicons-shield"></span>
                        </div>
                        <div class="info-content">
                            <h4>Безопасность</h4>
                            <p>Требуются права администратора, двойное подтверждение для критических операций.</p>
                        </div>
                    </div>
                    
                    <div class="neetrino-info-item">
                        <div class="info-icon">
                            <span class="dashicons dashicons-backup"></span>
                        </div>
                        <div class="info-content">
                            <h4>Резервные копии</h4>
                            <p>Обязательно создайте резервную копию перед удалением данных. Операции необратимы!</p>
                        </div>
                    </div>
                    
                    <div class="neetrino-info-item">
                        <div class="info-icon">
                            <span class="dashicons dashicons-performance"></span>
                        </div>
                        <div class="info-content">
                            <h4>Производительность</h4>
                            <p>Автоматическая очистка кэша и оптимизация базы данных после удаления.</p>
                        </div>
                    </div>
                    
                    <div class="neetrino-info-item">
                        <div class="info-icon">
                            <span class="dashicons dashicons-admin-tools"></span>
                        </div>
                        <div class="info-content">
                            <h4>Выборочное удаление</h4>
                            <p>Удаляйте только нужные типы данных или используйте полную очистку в опасной зоне.</p>
                        </div>
                    </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Модальное окно настроек удаления файлов
     */
    private static function render_settings_modal() {
        // Получаем список папок WordPress
        $upload_dir = wp_upload_dir();
        $wordpress_folders = [
            'wp-content/uploads' => $upload_dir['basedir'],
            'wp-content/themes' => WP_CONTENT_DIR . '/themes',
            'wp-content/plugins' => WP_CONTENT_DIR . '/plugins',
            'wp-content/cache' => WP_CONTENT_DIR . '/cache',
            'wp-content/backup' => WP_CONTENT_DIR . '/backup',
            'wp-content/temp' => WP_CONTENT_DIR . '/temp'
        ];
        ?>
        <!-- Модальное окно настроек -->
        <div id="neetrino-settings-modal" class="neetrino-modal" style="display: none;">
            <div class="neetrino-modal-overlay" id="neetrino-settings-modal-overlay"></div>
            <div class="neetrino-modal-content neetrino-settings-modal-content">
                <div class="neetrino-modal-header">
                    <h3>Настройки удаления файлов</h3>
                    <button type="button" class="neetrino-modal-close" id="neetrino-settings-modal-close">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                </div>
                <div class="neetrino-modal-body">
                    <form id="neetrino-settings-form">
                        <!-- Выбор папки -->
                        <div class="settings-section">
                            <h4><span class="dashicons dashicons-portfolio"></span> Выберите папку</h4>
                            <div class="folder-browser">
                                <div class="folder-input-container">
                                    <div class="folder-input-wrapper">
                                        <span class="dashicons dashicons-admin-folder folder-input-icon"></span>
                                        <input type="text" id="folder-path-input" placeholder="Введите путь или нажмите 'Выбрать папку' для навигации..." class="folder-path-input">
                                        <button type="button" id="toggle-folder-browser-btn" class="folder-browse-btn" title="Выбрать папку">
                                            <span class="dashicons dashicons-category"></span>
                                            <span class="btn-text">Выбрать папку</span>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="folder-navigator" id="folder-navigator" style="display: none;">
                                    <div class="folder-navigator-header">
                                        <div class="current-path">
                                            <span class="path-label">Текущий путь:</span>
                                            <span id="current-path-display"><?php echo esc_html(ABSPATH); ?></span>
                                        </div>
                                        <button type="button" id="go-to-parent-btn" class="folder-nav-btn" title="Перейти к родительской папке">
                                            <span class="dashicons dashicons-arrow-up-alt"></span>
                                        </button>
                                    </div>
                                    
                                    <div id="folder-contents" class="folder-contents">
                                        <!-- Содержимое папки будет загружаться динамически -->
                                    </div>
                                    
                                    <div class="folder-navigator-footer">
                                        <button type="button" id="close-folder-browser-btn" class="folder-close-btn">
                                            <span class="dashicons dashicons-no-alt"></span>
                                            Закрыть
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Фильтры -->
                        <div class="settings-section">
                            <h4><span class="dashicons dashicons-filter"></span> Фильтры</h4>
                            
                            <!-- Все фильтры в одной строке -->
                            <div class="filters-row">
                                <!-- Фильтр по дате -->
                                <div class="filter-item">
                                    <label class="filter-checkbox">
                                        <input type="checkbox" id="filter-date-checkbox">
                                        <span class="filter-label">По дате:</span>
                                    </label>
                                    <div class="filter-inputs">
                                        <input type="date" id="date-from" name="date_from" disabled>
                                        <span class="filter-separator">—</span>
                                        <input type="date" id="date-to" name="date_to" disabled>
                                        <select id="date-type" name="date_type" disabled>
                                            <option value="created">Создания</option>
                                            <option value="modified">Изменения</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Фильтр по размеру -->
                                <div class="filter-item">
                                    <label class="filter-checkbox">
                                        <input type="checkbox" id="filter-size-checkbox">
                                        <span class="filter-label">По размеру:</span>
                                    </label>
                                    <div class="filter-inputs">
                                        <input type="number" id="size-from" name="size_from" min="0" step="0.1" placeholder="От" disabled>
                                        <span class="filter-separator">—</span>
                                        <input type="number" id="size-to" name="size_to" min="0" step="0.1" placeholder="До" disabled>
                                        <select id="size-unit" name="size_unit" disabled>
                                            <option value="KB">КБ</option>
                                            <option value="MB">МБ</option>
                                            <option value="GB">ГБ</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Фильтр по типу файла -->
                                <div class="filter-item">
                                    <label class="filter-checkbox">
                                        <input type="checkbox" id="filter-filetype-checkbox">
                                        <span class="filter-label">По типу:</span>
                                    </label>
                                    <div class="filter-inputs">
                                        <input type="text" id="file-extensions" name="file_extensions" placeholder="jpg,png,pdf,txt" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Статистика выбранных файлов -->
                        <div class="settings-section">
                            <div id="file-statistics" class="file-stats" style="display: none;">
                                <h4><span class="dashicons dashicons-chart-bar"></span> Статистика</h4>
                                <div class="stats-grid">
                                    <div class="stat-item">
                                        <span class="stat-label">Найдено файлов:</span>
                                        <span class="stat-value" id="files-count">0</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Общий размер:</span>
                                        <span class="stat-value" id="files-size">0 байт</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Всего в папке:</span>
                                        <span class="stat-value" id="total-files">0</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Размер папки:</span>
                                        <span class="stat-value" id="folder-size">0 байт</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="neetrino-modal-footer">
                    <button type="button" id="scan-files-btn" class="neetrino-scan-button" disabled>
                        <span class="dashicons dashicons-search"></span>
                        <span class="btn-text">Найти файлы</span>
                        <div class="btn-spinner" style="display: none;"></div>
                    </button>
                    <button type="button" id="delete-selected-files-btn" class="neetrino-delete-button" disabled>
                        <span class="dashicons dashicons-trash"></span>
                        <span class="btn-text">Удалить файлы</span>
                        <div class="btn-spinner" style="display: none;"></div>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * JavaScript для страницы
     */
    private static function render_scripts() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var nonce = '<?php echo wp_create_nonce('neetrino_cleanup_nonce'); ?>';
            
            // Открытие модального окна информации
            $('#neetrino-info-btn').on('click', function() {
                $('#neetrino-info-modal').fadeIn(300);
                $('body').addClass('neetrino-modal-open');
            });
            
            // Открытие модального окна настроек
            $('#neetrino-settings-btn').on('click', function() {
                $('#neetrino-settings-modal').fadeIn(300);
                $('body').addClass('neetrino-modal-open');
            });
            
            // Закрытие модального окна информации
            function closeInfoModal() {
                $('#neetrino-info-modal').fadeOut(300);
                $('body').removeClass('neetrino-modal-open');
            }
            
            // Закрытие модального окна настроек
            function closeSettingsModal() {
                $('#neetrino-settings-modal').fadeOut(300);
                $('body').removeClass('neetrino-modal-open');
            }
            
            $('#neetrino-modal-close, #neetrino-modal-overlay').on('click', closeInfoModal);
            $('#neetrino-settings-modal-close, #neetrino-settings-modal-overlay').on('click', closeSettingsModal);
            
            // Закрытие по Escape
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27) {
                    if ($('#neetrino-info-modal').is(':visible')) {
                        closeInfoModal();
                    }
                    if ($('#neetrino-settings-modal').is(':visible')) {
                        closeSettingsModal();
                    }
                }
            });
            
            // Логика для модального окна настроек
            initSettingsModal();
            
            function initSettingsModal() {
                var currentPath = '<?php echo addslashes(ABSPATH); ?>';
                
                // Инициализация браузера папок
                initFolderBrowser();
                
                function initFolderBrowser() {
                    // Браузер папок скрыт по умолчанию
                    
                    // Обработчик для кнопки "Выбрать папку"
                    $('#toggle-folder-browser-btn').on('click', function() {
                        var $navigator = $('#folder-navigator');
                        if ($navigator.is(':visible')) {
                            $navigator.slideUp(300);
                        } else {
                            $navigator.slideDown(300);
                            // Загружаем содержимое текущей папки при открытии
                            var currentInputPath = $('#folder-path-input').val().trim();
                            if (currentInputPath) {
                                currentPath = normalizePath(currentInputPath);
                                loadFolderContents(currentPath);
                            } else {
                                loadFolderContents(currentPath);
                            }
                        }
                    });
                    
                    // Обработчик для кнопки "Закрыть"
                    $('#close-folder-browser-btn').on('click', function() {
                        $('#folder-navigator').slideUp(300);
                    });
                    
                    // Обработчик для кнопки "Вверх"
                    $('#go-to-parent-btn').on('click', function() {
                        var parentPath = getParentPath(currentPath);
                        if (parentPath && parentPath !== currentPath) {
                            currentPath = parentPath;
                            loadFolderContents(currentPath);
                        }
                    });
                    
                    // Обработчик ввода в поле пути
                    $('#folder-path-input').on('input', function() {
                        var inputPath = $(this).val().trim();
                        if (inputPath) {
                            currentPath = normalizePath(inputPath);
                            updateCurrentPath(currentPath);
                            updateScanButton();
                        }
                    }).on('keypress', function(e) {
                        if (e.which === 13) { // Enter
                            var inputPath = $(this).val().trim();
                            if (inputPath) {
                                currentPath = normalizePath(inputPath);
                                loadFolderContents(currentPath);
                            }
                        }
                    });
                    
                    // Установка начального значения
                    var initialPath = normalizePath(currentPath);
                    $('#folder-path-input').val(initialPath);
                    updateCurrentPath(initialPath);
                    updateScanButton();
                }
                
                function loadFolderContents(path) {
                    var $contents = $('#folder-contents');
                    $contents.html('<div style="text-align: center; padding: 20px; color: #6b7280;">Загрузка...</div>');
                    
                    var normalizedPath = normalizePath(path);
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'neetrino_browse_folders',
                            nonce: nonce,
                            path: normalizedPath
                        },
                        success: function(response) {
                            if (response.success) {
                                displayFolderContents(response.data.folders);
                                updateCurrentPath(normalizedPath);
                                currentPath = normalizedPath;
                                $('#folder-path-input').val(normalizedPath);
                                updateScanButton();
                            } else {
                                $contents.html('<div style="text-align: center; padding: 20px; color: #ef4444;">Ошибка: ' + (response.data.message || 'Ошибка загрузки') + '</div>');
                            }
                        },
                        error: function() {
                            $contents.html('<div style="text-align: center; padding: 20px; color: #ef4444;">Ошибка соединения</div>');
                        }
                    });
                }
                
                function displayFolderContents(folders) {
                    var $contents = $('#folder-contents');
                    
                    if (folders.length === 0) {
                        $contents.html('<div style="text-align: center; padding: 20px; color: #6b7280;">Папка пуста</div>');
                        return;
                    }
                    
                    var html = folders.map(function(folder) {
                        return '<div class="folder-item-nav" data-path="' + folder.path.replace(/"/g, '&quot;') + '">' +
                               '<span class="dashicons dashicons-category folder-icon"></span>' +
                               '<span class="folder-item-name">' + folder.name.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span>' +
                               '<button type="button" class="select-folder-btn" data-path="' + folder.path.replace(/"/g, '&quot;') + '">' +
                               '<span class="dashicons dashicons-yes"></span>' +
                               '<span class="btn-text">Выбрать эту папку</span>' +
                               '</button></div>';
                    }).join('');
                    
                    $contents.html(html);
                    
                    // Простые обработчики событий
                    $contents.off('.folderNav').on('click.folderNav', '.folder-item-nav', function(e) {
                        if ($(e.target).closest('.select-folder-btn').length) return;
                        currentPath = normalizePath($(this).data('path'));
                        loadFolderContents(currentPath);
                    }).on('click.folderNav', '.select-folder-btn', function(e) {
                        e.stopPropagation();
                        var folderPath = normalizePath($(this).data('path'));
                        $('#folder-path-input').val(folderPath);
                        currentPath = folderPath;
                        updateCurrentPath(folderPath);
                        updateScanButton();
                        $('#folder-navigator').slideUp(300);
                    });
                }
                
                function updateCurrentPath(path) {
                    var normalizedPath = normalizePath(path);
                    $('#current-path-display').text(normalizedPath);
                }
                
                function getParentPath(path) {
                    var normalizedPath = normalizePath(path);
                    var parts = normalizedPath.replace(/[\/]+$/, '').split('/');
                    if (parts.length > 1) {
                        parts.pop();
                        return parts.join('/') + '/';
                    }
                    return null;
                }
                
                function normalizePath(path) {
                    if (!path) return '';
                    return path.replace(/\\/g, '/').replace(/\/+/g, '/').replace(/\/$/, '') || '/';
                }
                
                // Обновление состояния кнопки сканирования
                function updateScanButton() {
                    var pathValid = $('#folder-path-input').val().trim() !== '';
                    $('#scan-files-btn').prop('disabled', !pathValid);
                }
                
                // Инициализация фильтров
                initFilters();
                
                function initFilters() {
                    // Обработчик для фильтра по дате
                    $('#filter-date-checkbox').on('change', function() {
                        var $inputs = $('.filter-item').first().find('.filter-inputs input, .filter-inputs select');
                        $inputs.prop('disabled', !$(this).is(':checked'));
                    }).trigger('change');
                    
                    // Обработчик для фильтра по размеру
                    $('#filter-size-checkbox').on('change', function() {
                        var $inputs = $('.filter-item').eq(1).find('.filter-inputs input, .filter-inputs select');
                        $inputs.prop('disabled', !$(this).is(':checked'));
                    }).trigger('change');
                    
                    // Обработчик для фильтра по типу файла
                    $('#filter-filetype-checkbox').on('change', function() {
                        var $inputs = $('.filter-item').eq(2).find('.filter-inputs input, .filter-inputs select');
                        $inputs.prop('disabled', !$(this).is(':checked'));
                    }).trigger('change');
                }
                
                // Сканирование файлов
                $('#scan-files-btn').on('click', function() {
                    var $button = $(this);
                    var formData = collectFormData();
                    
                    if (!formData) {
                        alert('Пожалуйста, выберите папку для сканирования');
                        return;
                    }
                    
                    $button.addClass('loading').prop('disabled', true);
                    $button.find('.btn-text').text('Поиск файлов...');
                    $button.find('.btn-spinner').show();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'neetrino_scan_files',
                            nonce: nonce,
                            folder_path: formData.folder_path,
                            use_date_filter: formData.use_date_filter,
                            date_from: formData.date_from,
                            date_to: formData.date_to,
                            date_type: formData.date_type,
                            filter_by_extension: formData.filter_by_extension,
                            file_extensions: formData.file_extensions,
                            filter_by_size: formData.filter_by_size,
                            size_operator: formData.size_operator,
                            file_size: formData.file_size,
                            size_unit: formData.size_unit
                        },
                        success: function(response) {
                            if (response.success) {
                                displayFileStatistics(response.data);
                                $('#delete-selected-files-btn').prop('disabled', response.data.files_count === 0);
                            } else {
                                alert('Ошибка: ' + (response.data.message || 'Неизвестная ошибка'));
                            }
                        },
                        error: function() {
                            alert('Ошибка соединения с сервером');
                        },
                        complete: function() {
                            $button.removeClass('loading').prop('disabled', false);
                            $button.find('.btn-text').text('Найти файлы');
                            $button.find('.btn-spinner').hide();
                        }
                    });
                });
                
                // Удаление выбранных файлов
                $('#delete-selected-files-btn').on('click', function() {
                    var filesCount = parseInt($('#files-count').text());
                    var filesSize = $('#files-size').text();
                    
                    if (!confirm(`Вы уверены, что хотите удалить ${filesCount} файлов (${filesSize})? Это действие НЕОБРАТИМО!`)) {
                        return;
                    }
                    
                    var $button = $(this);
                    var formData = collectFormData();
                    
                    $button.addClass('loading').prop('disabled', true);
                    $button.find('.btn-text').text('Удаление...');
                    $button.find('.btn-spinner').show();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'neetrino_delete_files',
                            nonce: nonce,
                            folder_path: formData.folder_path,
                            use_date_filter: formData.use_date_filter,
                            date_from: formData.date_from,
                            date_to: formData.date_to,
                            date_type: formData.date_type,
                            filter_by_extension: formData.filter_by_extension,
                            file_extensions: formData.file_extensions,
                            filter_by_size: formData.filter_by_size,
                            size_operator: formData.size_operator,
                            file_size: formData.file_size,
                            size_unit: formData.size_unit
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(`Успешно удалено: ${response.data.deleted_count} файлов`);
                                closeSettingsModal();
                                // Обновляем статистику на главной странице
                                setTimeout(function() {
                                    location.reload();
                                }, 1000);
                            } else {
                                alert('Ошибка: ' + (response.data.message || 'Неизвестная ошибка'));
                            }
                        },
                        error: function() {
                            alert('Ошибка соединения с сервером');
                        },
                        complete: function() {
                            $button.removeClass('loading').prop('disabled', true);
                            $button.find('.btn-text').text('Удалить файлы');
                            $button.find('.btn-spinner').hide();
                        }
                    });
                });
                
                // Сбор данных формы
                function collectFormData() {
                    var folderPath = $('#folder-path-input').val().trim();
                    if (!folderPath) {
                        return null;
                    }
                    
                    return {
                        folder_path: folderPath,
                        use_date_filter: $('#filter-date-checkbox').is(':checked'),
                        date_from: $('#date-from').val(),
                        date_to: $('#date-to').val(),
                        date_type: $('#date-type').val(),
                        filter_by_extension: $('#filter-filetype-checkbox').is(':checked'),
                        file_extensions: $('#file-extensions').val(),
                        filter_by_size: $('#filter-size-checkbox').is(':checked'),
                        size_from: $('#size-from').val(),
                        size_to: $('#size-to').val(),
                        size_unit: $('#size-unit').val()
                    };
                }
                
                // Отображение статистики файлов
                function displayFileStatistics(data) {
                    $('#files-count').text(data.files_count);
                    $('#files-size').text(data.files_size);
                    $('#total-files').text(data.total_files);
                    $('#folder-size').text(data.folder_size);
                    $('#file-statistics').show();
                }
            }
            
            // Обработчик для кнопок очистки отдельных элементов
            $('.cleanup-btn').on('click', function() {
                var $button = $(this);
                var action = $button.data('action');
                var confirm_text = $button.data('confirm');
                
                if (!confirm('Вы уверены, что хотите ' + confirm_text + '? Это действие НЕОБРАТИМО!')) {
                    return false;
                }
                
                performCleanup(action, $button);
            });            // Обработчик для полного удаления WooCommerce
            $('#neetrino-clean-all-btn').on('click', function() {
                if (!confirm('ВНИМАНИЕ! Вы собираетесь удалить ВСЕ данные WooCommerce! Это действие НЕОБРАТИМО!')) {
                    return false;
                }
                
                if (!confirm('ПОСЛЕДНЕЕ ПРЕДУПРЕЖДЕНИЕ! Все товары, заказы, категории, купоны будут безвозвратно удалены. Продолжить?')) {
                    return false;
                }
                
                performCleanup('clean_all_shop_data', $(this));
            });
            
            // Обработчик для полного удаления WordPress данных
            $('#neetrino-clean-wordpress-btn').on('click', function() {
                if (!confirm('ВНИМАНИЕ! Вы собираетесь удалить ВСЕ данные WordPress! Это действие НЕОБРАТИМО!')) {
                    return false;
                }
                
                if (!confirm('ПОСЛЕДНЕЕ ПРЕДУПРЕЖДЕНИЕ! Все страницы, записи, комментарии, медиафайлы будут безвозвратно удалены. Продолжить?')) {
                    return false;
                }
                
                performCleanup('clean_all_wordpress_data', $(this));
            });
            
            // Функция выполнения очистки
            function performCleanup(action, $button) {
                var $progress = $('#neetrino-cleanup-progress');
                var $result = $('#neetrino-cleanup-result');
                  // Отключаем все кнопки и показываем прогресс
                $('.cleanup-btn, #neetrino-clean-all-btn, #neetrino-clean-wordpress-btn').prop('disabled', true).addClass('loading');
                $progress.show();
                $result.hide();
                
                // Анимация прогресса
                $('.progress-fill').animate({width: '100%'}, 2000);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neetrino_' + action,
                        nonce: nonce
                    },
                    success: function(response) {
                        $progress.hide();
                        
                        if (response.success) {
                            var message = response.data.message;
                              // Для полной очистки показываем детальную статистику
                            if ((action === 'clean_all_shop_data' || action === 'clean_all_wordpress_data') && response.data.results) {
                                var results = response.data.results;
                                var detailHtml = '<div class="cleanup-details">';
                                detailHtml += '<h4>Результаты очистки:</h4>';
                                detailHtml += '<ul>';
                                
                                // WooCommerce данные
                                if (results.products > 0) detailHtml += '<li>Товаров: ' + results.products + '</li>';
                                if (results.categories > 0) detailHtml += '<li>Категорий товаров: ' + results.categories + '</li>';
                                if (results.tags > 0) detailHtml += '<li>Тегов товаров: ' + results.tags + '</li>';
                                if (results.attributes > 0) detailHtml += '<li>Атрибутов: ' + results.attributes + '</li>';
                                if (results.orders > 0) detailHtml += '<li>Заказов: ' + results.orders + '</li>';
                                if (results.coupons > 0) detailHtml += '<li>Купонов: ' + results.coupons + '</li>';
                                if (results.reviews > 0) detailHtml += '<li>Отзывов о товарах: ' + results.reviews + '</li>';
                                
                                // WordPress данные
                                if (results.pages > 0) detailHtml += '<li>Страниц: ' + results.pages + '</li>';
                                if (results.posts > 0) detailHtml += '<li>Записей блога: ' + results.posts + '</li>';
                                if (results.comments > 0) detailHtml += '<li>Комментариев: ' + results.comments + '</li>';
                                if (results.media > 0) detailHtml += '<li>Медиафайлов: ' + results.media + '</li>';
                                if (results.trash > 0) detailHtml += '<li>Из корзины: ' + results.trash + '</li>';
                                if (results.unused_terms > 0) detailHtml += '<li>Неиспользуемых тегов: ' + results.unused_terms + '</li>';
                                if (results.transients > 0) detailHtml += '<li>Транзиентов: ' + results.transients + '</li>';
                                
                                detailHtml += '</ul></div>';
                                message += detailHtml;
                            }
                            
                            $result.html('<div class="neetrino-success-message">' + message + '</div>').show();
                            
                            // Перезагружаем страницу через 4 секунды
                            setTimeout(function() {
                                location.reload();
                            }, 4000);
                        } else {
                            $result.html('<div class="neetrino-error-message">Ошибка: ' + (response.data.message || 'Неизвестная ошибка') + '</div>').show();
                            $('.cleanup-btn, #neetrino-clean-all-btn, #neetrino-clean-wordpress-btn').prop('disabled', false).removeClass('loading');
                        }
                    },
                    error: function() {
                        $progress.hide();
                        $result.html('<div class="neetrino-error-message">Ошибка подключения к серверу</div>').show();
                        $('.cleanup-btn, #neetrino-clean-all-btn, #neetrino-clean-wordpress-btn').prop('disabled', false).removeClass('loading');
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * CSS стили для страницы
     */
    private static function render_styles() {
        ?>
        <style>
        /* Основной контейнер */
        .neetrino-delete-main-card {
            background: #fff;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            margin-bottom: 0;
        }
        
        /* Хедер с статистикой */
        .neetrino-delete-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        /* Заголовок модуля */
        .neetrino-module-title {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .module-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(239, 68, 68, 0.3);
        }
        
        .module-icon .dashicons {
            color: white;
            font-size: 24px;
            width: 24px;
            height: 24px;
        }
        
        .module-info h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.2;
        }
        
        .module-description {
            margin: 4px 0 0 0;
            font-size: 14px;
            color: #6b7280;
            line-height: 1.4;
        }
        
        /* Контейнер кнопок в хедере */
        .header-buttons {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        /* Кнопка настроек */
        .neetrino-settings-button {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border: none;
            border-radius: 8px;
            padding: 12px 16px;
            color: white;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }
        
        .neetrino-settings-button:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
        }
        
        .neetrino-settings-button .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        
        /* Кнопка информации */
        .neetrino-info-button {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .neetrino-info-button:hover {
            background: #e2e8f0;
            color: #475569;
            border-color: #cbd5e1;
        }
        
        .neetrino-info-button .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        
          /* Секция опций очистки */
        .neetrino-cleanup-options {
            margin-bottom: 32px;
        }
        
        /* Секции данных */
        .cleanup-section {
            margin-bottom: 40px;
        }          .section-header {
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            background: #64748b;
            flex-shrink: 0;
        }
        
        .woocommerce-icon {
            background: #96588a;
        }
        
        .wordpress-icon {
            background: #3b82f6;
        }
        
        .section-title {
            flex-shrink: 0;
        }
        
        .section-title h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .section-line {
            flex: 1;
            height: 2px;
            margin-left: 16px;
            border-radius: 1px;
            background: linear-gradient(90deg, rgba(0,0,0,0.1) 0%, transparent 100%);
        }
        
        .woocommerce-line {
            background: linear-gradient(90deg, #96588a 0%, rgba(150,88,138,0.3) 50%, transparent 100%);
        }
        
        .wordpress-line {
            background: linear-gradient(90deg, #3b82f6 0%, rgba(59,130,246,0.3) 50%, transparent 100%);
        }
        
        .neetrino-cleanup-options h3 {
            margin: 0 0 20px 0;
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
          .cleanup-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 16px;
        }
          .cleanup-option {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.2s ease;
        }
        
        .cleanup-option:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        
        /* WooCommerce пункты */
        .cleanup-option.woocommerce-item {
            background: rgba(150, 88, 138, 0.05);
            border: 1px solid rgba(150, 88, 138, 0.2);
        }
        
        .cleanup-option.woocommerce-item:hover {
            background: rgba(150, 88, 138, 0.1);
            border-color: rgba(150, 88, 138, 0.4);
        }
        
        /* WordPress пункты */
        .cleanup-option.wordpress-item {
            background: rgba(59, 130, 246, 0.05);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }
        
        .cleanup-option.wordpress-item:hover {
            background: rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.4);
        }
        
        .cleanup-icon {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .cleanup-icon .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
            color: #64748b;
        }
        
        .cleanup-content {
            flex: 1;
        }
        
        .cleanup-content h4 {
            margin: 0 0 4px 0;
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .cleanup-content p {
            margin: 0;
            font-size: 14px;
            color: #6b7280;
        }
        
        .cleanup-btn {
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        
        .cleanup-btn:hover:not(:disabled) {
            background: #dc2626;
        }
        
        .cleanup-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .cleanup-btn.loading {
            position: relative;
            color: transparent;
        }
        
        .cleanup-btn.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        /* Опасная зона */
        .neetrino-danger-zone {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 2px solid #fca5a5;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            margin-bottom: 24px;
        }
        
        .neetrino-danger-zone h3 {
            margin: 0 0 8px 0;
            font-size: 18px;
            font-weight: 700;
            color: #991b1b;
        }
          .neetrino-danger-zone p {
            margin: 0 0 20px 0;
            color: #7f1d1d;
            font-weight: 500;
        }
        
        .danger-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .neetrino-danger-button {
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 16px 24px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .neetrino-danger-button:hover:not(:disabled) {
            background: #b91c1c;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
        }
        
        .neetrino-danger-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .neetrino-danger-button.loading {
            position: relative;
            color: transparent;
        }
        
        .neetrino-danger-button.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        /* Прогресс бар */
        .neetrino-progress-container {
            margin-bottom: 16px;
        }
        
        .progress-text {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 8px;
            text-align: center;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: #f3f4f6;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #dc2626, #ef4444);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        /* Сообщения результата */
        .neetrino-success-message {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
            padding: 16px;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
        }
        
        .neetrino-error-message {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
            padding: 16px;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
        }
        
        .cleanup-details {
            margin-top: 16px;
            text-align: left;
        }
        
        .cleanup-details h4 {
            margin: 0 0 8px 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .cleanup-details ul {
            margin: 0;
            padding-left: 20px;
            list-style-type: disc;
        }
        
        .cleanup-details li {
            margin-bottom: 4px;
            font-size: 14px;
        }
        
        /* Состояние ошибки */
        .neetrino-error-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .neetrino-error-icon {
            margin-bottom: 16px;
        }
        
        .neetrino-error-icon .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            color: #f59e0b;
        }
        
        .neetrino-error-state h3 {
            margin: 0 0 8px 0;
            font-size: 20px;
            color: #1f2937;
        }
        
        .neetrino-error-state p {
            margin: 0;
            color: #6b7280;
        }
        
        /* Пустое состояние */
        .neetrino-empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .neetrino-empty-icon {
            margin-bottom: 16px;
        }
        
        .neetrino-empty-icon .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            color: #9ca3af;
        }
        
        .neetrino-empty-state h3 {
            margin: 0 0 8px 0;
            font-size: 20px;
            color: #1f2937;
        }
        
        .neetrino-empty-state p {
            margin: 0;
            color: #6b7280;
        }
        
        /* Модальное окно */
        .neetrino-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 100000;
        }
        
        .neetrino-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }
        
        .neetrino-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow: hidden;
        }
        
        .neetrino-modal-header {
            padding: 24px 24px 16px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .neetrino-modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .neetrino-modal-close {
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .neetrino-modal-close:hover {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .neetrino-modal-close .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }
        
        .neetrino-modal-body {
            padding: 16px 24px 24px;
            max-height: 60vh;
            overflow-y: auto;
        }
        
        .neetrino-info-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid #f9fafb;
        }
        
        .neetrino-info-item:last-child {
            border-bottom: none;
        }
          .info-icon {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            background: #f8fafc;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .info-icon .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
            color: #64748b;
        }
        
        /* Дополнительные стили для цветных иконок */
        .info-icon.woocommerce-info {
            background: rgba(150, 88, 138, 0.1);
            color: #96588a;
        }
        
        .info-icon.wordpress-info {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
        
        .info-content h4 {
            margin: 0 0 4px 0;
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .info-content p {
            margin: 0;
            font-size: 14px;
            color: #6b7280;
            line-height: 1.5;
        }
        
        /* Блокировка скролла при открытом модале */
        body.neetrino-modal-open {
            overflow: hidden;
        }
        
        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        /* Стили для модального окна настроек */
        .neetrino-settings-modal-content {
            max-width: 95vw;
            width: 95vw;
            max-height: 90vh;
        }
        
        .neetrino-modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #f3f4f6;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            background: #f8fafc;
        }
        
        /* Секции настроек */
        .settings-section {
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .settings-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .settings-section h4 {
            margin: 0 0 16px 0;
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .settings-section h4 .dashicons {
            color: #f59e0b;
        }
        
        /* Выбор папок */
        .folder-browser {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
        }
        
        .folder-input-container {
            margin-bottom: 20px;
        }
        
        .folder-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 0;
            transition: all 0.3s ease;
        }
        
        .folder-input-wrapper:focus-within {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }
        
        .folder-input-icon {
            color: #f59e0b;
            font-size: 20px;
            width: 20px;
            height: 20px;
            margin: 0 15px;
            flex-shrink: 0;
        }
        
        .folder-path-input {
            flex: 1;
            border: none;
            outline: none;
            padding: 15px 0;
            font-size: 15px;
            font-family: 'Courier New', Consolas, monospace;
            background: transparent;
            color: #374151;
        }
        
        .folder-path-input::placeholder {
            color: #9ca3af;
            font-style: italic;
        }
        
        .folder-browse-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            margin: 5px;
            color: white;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            flex-shrink: 0;
            font-size: 14px;
            font-weight: 500;
        }
        
        .folder-browse-btn:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .folder-browse-btn .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        
        .folder-navigator {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .folder-navigator-header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .current-path {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            font-size: 14px;
            font-weight: 500;
        }
        
        .folder-navigator-footer {
            background: #f8fafc;
            padding: 12px 16px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }
        
        .folder-close-btn {
            background: #6b7280;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            color: white;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
        }
        
        .folder-close-btn:hover {
            background: #4b5563;
        }
        
        .folder-nav-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 6px;
            padding: 8px 12px;
            color: white;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            flex-shrink: 0;
            font-size: 12px;
        }
        
        .folder-nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .folder-nav-btn .dashicons {
            font-size: 14px;
            width: 14px;
            height: 14px;
        }
        
        .path-label {
            font-weight: 600;
        }
        
        #current-path-display {
            font-family: 'Courier New', Consolas, monospace;
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 12px;
            border-radius: 6px;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 13px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .folder-contents {
            max-height: 300px;
            overflow-y: auto;
            padding: 8px;
        }
        
        .folder-item-nav {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .folder-item-nav::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
            transition: all 0.5s ease;
        }
        
        .folder-item-nav:hover::before {
            left: 100%;
        }
        
        .folder-item-nav:hover {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-color: #3b82f6;
            transform: translateX(4px);
        }
        
        .folder-item-nav.selected {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-color: #3b82f6;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
        }
        
        .folder-icon {
            color: #3b82f6;
            font-size: 18px;
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }
        
        .folder-item-nav:hover .folder-icon,
        .folder-item-nav.selected .folder-icon {
            color: #1d4ed8;
            transform: scale(1.1);
        }
        
        .folder-item-name {
            font-weight: 500;
            color: #1f2937;
            flex: 1;
            transition: all 0.2s ease;
        }
        
        .folder-item-nav:hover .folder-item-name,
        .folder-item-nav.selected .folder-item-name {
            color: #1e40af;
            font-weight: 600;
        }
        
        .folder-item-info {
            font-size: 12px;
            color: #6b7280;
            background: rgba(107, 114, 128, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            transition: all 0.2s ease;
            margin-right: 8px;
        }
        
        .folder-item-nav:hover .folder-item-info,
        .folder-item-nav.selected .folder-item-info {
            background: rgba(59, 130, 246, 0.2);
            color: #1d4ed8;
        }
        
        .select-folder-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            color: white;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            opacity: 0;
            transform: scale(0.8);
            flex-shrink: 0;
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
        }
        
        .folder-item-nav:hover .select-folder-btn {
            opacity: 1;
            transform: scale(1);
        }
        
        .select-folder-btn:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }
        
        .select-folder-btn.selected-folder {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.5);
        }
        
        .select-folder-btn .btn-text {
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.3s ease;
            margin-left: 0;
        }
        
        .folder-item-nav:hover .select-folder-btn .btn-text {
            opacity: 1;
            transform: translateX(0);
            margin-left: 4px;
        }
        
        .select-folder-btn .dashicons {
            font-size: 14px;
            width: 14px;
            height: 14px;
        }
        
        /* Фильтры в ряд */
        .filters-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: flex-start;
        }
        
        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 200px;
            flex: 1;
        }
        
        .filter-checkbox {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            margin-bottom: 8px;
        }
        
        .filter-checkbox input[type="checkbox"] {
            margin: 0;
        }
        
        .filter-label {
            font-weight: 500;
            color: #374151;
            font-size: 14px;
            white-space: nowrap;
        }
        
        .filter-inputs {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .filter-inputs input,
        .filter-inputs select {
            padding: 6px 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
            background: white;
            transition: all 0.2s ease;
        }
        
        .filter-inputs input:disabled,
        .filter-inputs select:disabled {
            background: #f3f4f6;
            color: #9ca3af;
            cursor: not-allowed;
        }
        
        .filter-inputs input[type="date"] {
            min-width: 130px;
        }
        
        .filter-inputs input[type="number"] {
            width: 80px;
        }
        
        .filter-inputs input[type="text"] {
            min-width: 150px;
            flex: 1;
        }
        
        .filter-separator {
            color: #6b7280;
            font-weight: 500;
            margin: 0 2px;
        }
        
        /* Адаптивность для мобильных */
        @media (max-width: 768px) {
            .filters-row {
                flex-direction: column;
                gap: 16px;
            }
            
            .filter-item {
                min-width: auto;
            }
            
            .filter-inputs {
                flex-wrap: wrap;
            }
        }
        
        /* Статистика файлов */
        .file-stats {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border: 1px solid #10b981;
            border-radius: 8px;
            padding: 16px;
        }
        
        .file-stats h4 {
            margin-bottom: 12px !important;
            color: #065f46 !important;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }
        
        .stat-label {
            color: #059669;
            font-weight: 500;
        }
        
        .stat-value {
            color: #065f46;
            font-weight: 600;
            font-family: monospace;
        }
        
        /* Кнопки в футере - креативный дизайн */
        .neetrino-scan-button {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 16px 24px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        
        .neetrino-scan-button:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: all 0.5s ease;
        }
        
        .neetrino-scan-button:hover:not(:disabled):before {
            left: 100%;
        }
        
        .neetrino-scan-button:hover:not(:disabled) {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }
        
        .neetrino-scan-button:disabled {
            background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
            cursor: not-allowed;
            transform: none;
            box-shadow: 0 2px 8px rgba(156, 163, 175, 0.2);
        }
        
        .neetrino-scan-button .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
        }
        
        .neetrino-delete-button {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 16px 24px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }
        
        .neetrino-delete-button:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: all 0.5s ease;
        }
        
        .neetrino-delete-button:hover:not(:disabled):before {
            left: 100%;
        }
        
        .neetrino-delete-button:hover:not(:disabled) {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        }
        
        .neetrino-delete-button:disabled {
            background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
            cursor: not-allowed;
            transform: none;
            box-shadow: 0 2px 8px rgba(156, 163, 175, 0.2);
        }
        
        .neetrino-delete-button .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
        }
        
        .btn-spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        .neetrino-scan-button.loading .btn-text,
        .neetrino-delete-button.loading .btn-text {
            opacity: 0.7;
        }
        
        .neetrino-scan-button.loading .dashicons,
        .neetrino-delete-button.loading .dashicons {
            display: none;
        }
        
        /* Адаптивность */        
        @media (max-width: 768px) {
            .neetrino-delete-main-card {
                padding: 24px 16px;
            }
            
            .neetrino-delete-header {
                flex-direction: column;
                gap: 20px;
                align-items: stretch;
            }
            
            .header-buttons {
                flex-direction: column;
                gap: 8px;
            }              
            .section-header {
                flex-direction: row;
                text-align: left;
                gap: 8px;
            }
            
            .section-icon {
                width: 28px;
                height: 28px;
                font-size: 16px;
            }
            
            .section-title h3 {
                font-size: 14px;
            }
            
            .section-line {
                margin-left: 12px;
            }
            
            .danger-buttons {
                flex-direction: column;
                gap: 12px;
            }
            
            .cleanup-grid {
                grid-template-columns: 1fr;
            }
            
            .cleanup-option {
                padding: 16px;
            }
            
            .neetrino-danger-zone {
                padding: 20px 16px;
            }
            
            .neetrino-modal-content {
                width: 95%;
                margin: 20px;
            }
            
            .neetrino-settings-modal-content {
                max-height: 95vh;
                width: 95vw;
                max-width: 95vw;
            }
            
            .folder-input-wrapper {
                flex-direction: row;
                align-items: center;
                padding: 10px 15px;
            }
            
            .folder-path-input {
                padding: 12px 0;
                font-size: 14px;
            }
            
            .folder-browse-btn {
                padding: 8px 12px;
                font-size: 12px;
            }
            
            .folder-browse-btn .btn-text {
                display: none;
            }
            
            .folder-navigator-header {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }
            
            .current-path {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 4px;
            }
            
            .folder-nav-btn {
                align-self: center;
            }
            
            .folder-contents {
                max-height: 200px;
            }
            
            .folder-item-nav {
                flex-direction: column;
                text-align: center;
                gap: 8px;
                padding: 16px 12px;
            }
            
            .select-folder-btn {
                opacity: 1;
                transform: scale(1);
                margin-top: 8px;
                padding: 10px 16px;
                font-size: 12px;
            }
            
            .select-folder-btn .btn-text {
                display: inline;
            }
            
            .date-inputs {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .date-type {
                flex-direction: column;
                gap: 8px;
            }
            
            .size-inputs {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 8px;
            }
            
            .neetrino-modal-footer {
                flex-direction: column;
            }
            
            .neetrino-modal-header {
                padding: 20px 16px 12px;
            }
            
            .neetrino-modal-body {
                padding: 12px 16px 20px;
            }
        }
          @media (max-width: 480px) {
            .section-header {
                flex-wrap: wrap;
            }
            
            .section-line {
                display: none;
            }
            
            .cleanup-option {
                flex-direction: column;
                text-align: center;
                gap: 12px;
            }
            
            .neetrino-danger-button {
                padding: 14px 20px;
                font-size: 14px;
            }
        }
        </style>
        <?php
    }
}

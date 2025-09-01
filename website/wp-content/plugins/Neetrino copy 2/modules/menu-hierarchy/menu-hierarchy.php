<?php
/**
 * Module: Menu Hierarchy
 * Description: Автоматическая настройка иерархии меню на основе структуры категорий WooCommerce
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Menu_Hierarchy {
    
    public function __construct() {
        // Инициализация модуля только если он активен
        if (!Neetrino::is_module_active('menu-hierarchy')) {
            return;
        }
        
        // Хуки и действия модуля
        add_action('init', [$this, 'init']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);        add_action('wp_ajax_process_menu_hierarchy', [$this, 'ajax_process_hierarchy']);
    }
    
    public function init() {
        // Проверяем наличие WooCommerce
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [$this, 'woocommerce_notice']);
            return;
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        // Подключаем скрипты только на странице модуля
        if (strpos($hook, 'menu-hierarchy') !== false) {
            wp_enqueue_script(
                'neetrino-menu-hierarchy',
                plugin_dir_url(__FILE__) . 'assets/js/admin.js',
                ['jquery'],
                '1.0.0',
                true
            );
            
            wp_enqueue_style(
                'neetrino-menu-hierarchy',
                plugin_dir_url(__FILE__) . 'assets/css/admin.css',
                [],
                '1.0.0'
            );            // Локализация для AJAX
            wp_localize_script('neetrino-menu-hierarchy', 'menuHierarchy', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('menu_hierarchy_nonce'),
                'messages' => [
                    'processing' => __('Обработка...', 'neetrino'),
                    'success' => __('Иерархия меню успешно настроена!', 'neetrino'),
                    'error' => __('Произошла ошибка при настройке иерархии.', 'neetrino'),
                    'selectMenu' => __('Пожалуйста, выберите меню.', 'neetrino')
                ]
            ]);
        }
    }
    
    public function woocommerce_notice() {
        ?>
        <div class="notice notice-warning">
            <p><strong>Menu Hierarchy:</strong> <?php _e('Для работы модуля требуется установленный и активированный WooCommerce.', 'neetrino'); ?></p>
        </div>
        <?php
    }
    
    public function ajax_process_hierarchy() {
        // Проверка nonce
        if (!wp_verify_nonce($_POST['nonce'], 'menu_hierarchy_nonce')) {
            wp_die('Security check failed');
        }
        
        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        $menu_id = intval($_POST['menu_id']);
        
        if (!$menu_id) {
            wp_send_json_error(['message' => __('Неверный ID меню.', 'neetrino')]);
        }
        
        $result = $this->process_menu_hierarchy($menu_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
      private function process_menu_hierarchy($menu_id) {
        try {
            // Получаем все пункты меню
            $menu_items = wp_get_nav_menu_items($menu_id);
            
            if (!$menu_items) {
                return [
                    'success' => false,
                    'message' => __('Меню не найдено или пустое.', 'neetrino')
                ];
            }
            
            // Получаем структуру категорий WooCommerce
            $categories = get_terms([
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
                'hierarchical' => true
            ]);
            
            if (is_wp_error($categories)) {
                return [
                    'success' => false,
                    'message' => __('Ошибка при получении категорий товаров.', 'neetrino')
                ];
            }
            
            // Создаем карту категорий для быстрого поиска
            $category_map = [];
            foreach ($categories as $category) {
                $category_map[$category->term_id] = $category;
            }
            
            // Создаем карту пунктов меню для категорий
            $menu_items_map = [];
            foreach ($menu_items as $item) {
                if ($item->object === 'product_cat') {
                    $menu_items_map[$item->object_id] = $item;
                }
            }
            
            $updated_items = 0;
            $debug_info = [];
            
            // Обрабатываем каждый пункт меню
            foreach ($menu_items as $item) {
                // Проверяем, является ли пункт категорией товаров
                if ($item->object === 'product_cat' && isset($category_map[$item->object_id])) {
                    $category = $category_map[$item->object_id];
                    $current_parent = intval($item->menu_item_parent);
                    $should_update = false;
                    $new_parent_id = 0;
                    
                    // Если у категории есть родитель
                    if ($category->parent > 0) {
                        // Ищем пункт меню родительской категории
                        if (isset($menu_items_map[$category->parent])) {
                            $parent_menu_item = $menu_items_map[$category->parent];
                            $new_parent_id = intval($parent_menu_item->ID);
                            
                            // Проверяем, нужно ли обновить родителя
                            if ($current_parent !== $new_parent_id) {
                                $should_update = true;
                                $debug_info[] = sprintf(
                                    'Категория "%s" (ID: %d) -> Родитель "%s" (ID: %d)',
                                    $category->name,
                                    $category->term_id,
                                    get_term($category->parent)->name,
                                    $new_parent_id
                                );
                            }
                        } else {
                            // Родительская категория не найдена в меню - делаем корневым элементом
                            if ($current_parent !== 0) {
                                $should_update = true;
                                $new_parent_id = 0;
                                $debug_info[] = sprintf(
                                    'Категория "%s" (ID: %d) -> Корневой элемент (родитель не найден в меню)',
                                    $category->name,
                                    $category->term_id
                                );
                            }
                        }
                    } else {
                        // Если категория корневая, убираем родителя у пункта меню
                        if ($current_parent !== 0) {
                            $should_update = true;
                            $new_parent_id = 0;
                            $debug_info[] = sprintf(
                                'Категория "%s" (ID: %d) -> Корневой элемент',
                                $category->name,
                                $category->term_id
                            );
                        }
                    }
                      // Выполняем обновление если нужно
                    if ($should_update) {
                        // Сохраняем все существующие параметры пункта меню
                        $menu_item_data = [
                            'menu-item-db-id' => $item->ID,
                            'menu-item-object-id' => $item->object_id,
                            'menu-item-object' => $item->object,
                            'menu-item-parent-id' => $new_parent_id,
                            'menu-item-position' => $item->menu_order,
                            'menu-item-type' => $item->type,
                            'menu-item-title' => $item->title,
                            'menu-item-url' => $item->url,
                            'menu-item-description' => $item->description,
                            'menu-item-attr-title' => $item->attr_title,
                            'menu-item-target' => $item->target,
                            'menu-item-classes' => implode(' ', $item->classes),
                            'menu-item-xfn' => $item->xfn,
                            'menu-item-status' => 'publish'
                        ];
                        
                        $update_result = wp_update_nav_menu_item($menu_id, $item->ID, $menu_item_data);
                        
                        if (!is_wp_error($update_result)) {
                            $updated_items++;
                        } else {
                            $debug_info[] = 'Ошибка обновления: ' . $update_result->get_error_message();
                        }
                    }
                }            }
            
            $debug_message = '';
            if (!empty($debug_info)) {
                $debug_message = '<br><small><strong>Детали изменений:</strong><br>' . implode('<br>', $debug_info) . '</small>';
            }
            
            return [
                'success' => true,
                'message' => sprintf(
                    __('Иерархия меню настроена! Обновлено пунктов: %d', 'neetrino'),
                    $updated_items
                ) . $debug_message,
                'updated_items' => $updated_items,
                'debug_info' => $debug_info
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => __('Произошла ошибка: ', 'neetrino') . $e->getMessage()
            ];
        }
    }
      private function find_menu_item_by_category($menu_items, $category_id) {
        foreach ($menu_items as $item) {
            if ($item->object === 'product_cat' && $item->object_id == $category_id) {
                return $item;
            }        }
        return null;    }
    
    /**
     * Статический метод для админ-страницы
     * Вызывается автоматически если модуль активен
     */
    public static function admin_page() {
        // Получаем все доступные меню
        $menus = wp_get_nav_menus();        ?>
        <div class="wrap menu-hierarchy-page">
            <div class="menu-hierarchy-header">
                <div class="menu-hierarchy-title-section">
                    <div class="menu-hierarchy-icon">
                        <span class="dashicons dashicons-menu-alt"></span>
                    </div>
                    <div class="menu-hierarchy-title-content">
                        <h1><?php _e('Menu Hierarchy', 'neetrino'); ?></h1>
                        <p><?php _e('Автоматическая настройка иерархии меню на основе структуры категорий WooCommerce', 'neetrino'); ?></p>
                    </div>
                </div>
                <div class="menu-hierarchy-help-toggle">
                    <button type="button" class="button button-secondary" id="toggle-help">
                        <span class="dashicons dashicons-editor-help"></span>
                        <?php _e('Как это работает?', 'neetrino'); ?>
                    </button>
                </div>
            </div>            
            <!-- Блок справки (скрытый по умолчанию) -->
            <div id="help-section" class="menu-hierarchy-help-section" style="display: none;">
                <div class="menu-hierarchy-help-card">
                    <h3><?php _e('Как работает модуль Menu Hierarchy?', 'neetrino'); ?></h3>
                    <div class="help-steps">
                        <div class="help-step">
                            <span class="step-number">1</span>
                            <p><?php _e('Модуль анализирует структуру категорий товаров WooCommerce', 'neetrino'); ?></p>
                        </div>
                        <div class="help-step">
                            <span class="step-number">2</span>
                            <p><?php _e('Находит соответствующие пункты в выбранном меню', 'neetrino'); ?></p>
                        </div>
                        <div class="help-step">
                            <span class="step-number">3</span>
                            <p><?php _e('Автоматически устанавливает правильную иерархию пунктов меню', 'neetrino'); ?></p>
                        </div>
                        <div class="help-step">
                            <span class="step-number">4</span>
                            <p><?php _e('Изменения сразу отображаются в админке и на сайте', 'neetrino'); ?></p>
                        </div>
                    </div>
                    
                    <div class="help-note">
                        <strong><?php _e('Примечание:', 'neetrino'); ?></strong> 
                        <?php _e('Модуль работает только с пунктами меню, которые ссылаются на категории товаров WooCommerce.', 'neetrino'); ?>
                    </div>
                </div>
            </div>
            
            <div class="menu-hierarchy-content">
                <div class="menu-hierarchy-main-card">                    <h2><?php _e('Настройка иерархии меню', 'neetrino'); ?></h2>
                    
                    <?php if (!class_exists('WooCommerce')): ?>
                        <div class="notice notice-warning inline">
                            <p><strong><?php _e('Внимание!', 'neetrino'); ?></strong> <?php _e('Для работы модуля требуется установленный и активированный WooCommerce.', 'neetrino'); ?></p>
                        </div>
                    <?php elseif (empty($menus)): ?>
                        <div class="notice notice-info inline">
                            <p><?php _e('Меню не найдены. Создайте меню в разделе "Внешний вид → Меню".', 'neetrino'); ?></p>
                        </div>
                    <?php else: ?>
                        <form id="menu-hierarchy-form" method="post">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="menu_select"><?php _e('Выберите меню:', 'neetrino'); ?></label>
                                    </th>
                                    <td>
                                        <select id="menu_select" name="menu_id" class="regular-text">
                                            <option value=""><?php _e('-- Выберите меню --', 'neetrino'); ?></option>
                                            <?php foreach ($menus as $menu): ?>
                                                <option value="<?php echo esc_attr($menu->term_id); ?>">
                                                    <?php echo esc_html($menu->name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="description">
                                            <?php _e('Выберите меню, для которого нужно настроить иерархию на основе категорий товаров.', 'neetrino'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>                            <div class="menu-hierarchy-actions">
                                <button type="submit" class="button button-primary" id="process-hierarchy">
                                    <?php _e('Настроить иерархию', 'neetrino'); ?>
                                </button>
                                <span class="spinner"></span>
                            </div>
                        </form>
                        
                        <div id="hierarchy-result" class="hierarchy-result" style="display: none;">
                            <div class="notice">
                                <p></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}

// Инициализация модуля
new Neetrino_Menu_Hierarchy();

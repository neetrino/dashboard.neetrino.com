<?php
/**
 * AJAX Handlers for Delete Module
 * 
 * Handles all AJAX requests for the delete functionality
 * Note: WooCommerce functions are only available when WooCommerce is active
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Delete_Ajax_Handlers {
    
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_neetrino_delete_all_products', [$this, 'handle_delete_all_products']);
        add_action('wp_ajax_neetrino_delete_product_categories', [$this, 'handle_delete_product_categories']);
        add_action('wp_ajax_neetrino_delete_product_tags', [$this, 'handle_delete_product_tags']);
        add_action('wp_ajax_neetrino_delete_product_attributes', [$this, 'handle_delete_product_attributes']);
        add_action('wp_ajax_neetrino_delete_orders', [$this, 'handle_delete_orders']);
        add_action('wp_ajax_neetrino_delete_coupons', [$this, 'handle_delete_coupons']);
        add_action('wp_ajax_neetrino_delete_reviews', [$this, 'handle_delete_reviews']);
        add_action('wp_ajax_neetrino_clean_all_shop_data', [$this, 'handle_clean_all_shop_data']);
        add_action('wp_ajax_neetrino_get_cleanup_stats', [$this, 'handle_get_cleanup_stats']);
        
        // Новые обработчики для расширенного функционала
        add_action('wp_ajax_neetrino_delete_pages', [$this, 'handle_delete_pages']);
        add_action('wp_ajax_neetrino_delete_posts', [$this, 'handle_delete_posts']);
        add_action('wp_ajax_neetrino_delete_all_comments', [$this, 'handle_delete_all_comments']);
        add_action('wp_ajax_neetrino_delete_spam_comments', [$this, 'handle_delete_spam_comments']);
        add_action('wp_ajax_neetrino_delete_media_files', [$this, 'handle_delete_media_files']);
        add_action('wp_ajax_neetrino_delete_drafts', [$this, 'handle_delete_drafts']);
        add_action('wp_ajax_neetrino_delete_trash', [$this, 'handle_delete_trash']);
        add_action('wp_ajax_neetrino_delete_unused_tags', [$this, 'handle_delete_unused_tags']);
        add_action('wp_ajax_neetrino_delete_transients', [$this, 'handle_delete_transients']);
        add_action('wp_ajax_neetrino_clean_all_wordpress_data', [$this, 'handle_clean_all_wordpress_data']);
        
        // Новые обработчики для работы с файлами
        add_action('wp_ajax_neetrino_scan_files', [$this, 'handle_scan_files']);
        add_action('wp_ajax_neetrino_delete_files', [$this, 'handle_delete_files']);
        add_action('wp_ajax_neetrino_browse_folders', [$this, 'handle_browse_folders']);
    }
    
    /**
     * Обработчик AJAX запроса на удаление всех товаров
     */
    public function handle_delete_all_products() {
        // Проверка nonce для безопасности
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        // Проверка прав доступа
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Недостаточно прав доступа');
        }
        
        // Проверяем что WooCommerce активен
        if (!class_exists('WooCommerce')) {
            wp_send_json_error(['message' => 'WooCommerce не активен']);
            return;
        }
        
        $deleted_count = 0;
        
        // Получаем все товары
        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids'
        ];
        
        $product_ids = get_posts($args);
        
        if (!empty($product_ids)) {
            foreach ($product_ids as $product_id) {
                // Принудительно удаляем товар (в обход корзины)
                if (wp_delete_post($product_id, true)) {
                    $deleted_count++;
                }
            }
            
            // Очищаем кэш WooCommerce
            if (function_exists('wc_delete_product_transients')) {
                wc_delete_product_transients();
            }
            
            // Очищаем кэш WordPress
            wp_cache_flush();
        }
        
        wp_send_json_success([
            'message' => "Успешно удалено товаров: {$deleted_count}",
            'deleted_count' => $deleted_count
        ]);
    }
    
    /**
     * Обработчик AJAX запроса на удаление категорий товаров
     */
    public function handle_delete_product_categories() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Недостаточно прав доступа');
        }
        
        $deleted_count = 0;
        $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
        
        foreach ($categories as $category) {
            if (wp_delete_term($category->term_id, 'product_cat')) {
                $deleted_count++;
            }
        }
        
        wp_send_json_success(['message' => "Удалено категорий: {$deleted_count}", 'count' => $deleted_count]);
    }
    
    /**
     * Обработчик AJAX запроса на удаление тегов товаров
     */
    public function handle_delete_product_tags() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Недостаточно прав доступа');
        }
        
        $deleted_count = 0;
        $tags = get_terms(['taxonomy' => 'product_tag', 'hide_empty' => false]);
        
        foreach ($tags as $tag) {
            if (wp_delete_term($tag->term_id, 'product_tag')) {
                $deleted_count++;
            }
        }
        
        wp_send_json_success(['message' => "Удалено тегов: {$deleted_count}", 'count' => $deleted_count]);
    }
    
    /**
     * Обработчик AJAX запроса на удаление атрибутов товаров
     */
    public function handle_delete_product_attributes() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Недостаточно прав доступа');
        }
        
        global $wpdb;
        $deleted_count = 0;
        
        // Получаем все атрибуты
        $attributes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies");
        
        foreach ($attributes as $attribute) {
            // Удаляем термины атрибута
            $terms = get_terms(['taxonomy' => 'pa_' . $attribute->attribute_name, 'hide_empty' => false]);
            foreach ($terms as $term) {
                wp_delete_term($term->term_id, 'pa_' . $attribute->attribute_name);
            }
            
            // Удаляем сам атрибут
            if ($wpdb->delete($wpdb->prefix . 'woocommerce_attribute_taxonomies', ['attribute_id' => $attribute->attribute_id])) {
                $deleted_count++;
            }
        }
        
        // Очищаем кэш
        delete_transient('wc_attribute_taxonomies');
        
        wp_send_json_success(['message' => "Удалено атрибутов: {$deleted_count}", 'count' => $deleted_count]);
    }
      /**
     * Обработчик AJAX запроса на удаление заказов
     */
    public function handle_delete_orders() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Недостаточно прав доступа');
        }
        
        $deleted_count = 0;
        
        // Проверяем, используется ли HPOS (High-Performance Order Storage)
        if (class_exists('Automattic\WooCommerce\Utilities\OrderUtil') && 
            method_exists('Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled') &&
            \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
            
            // Используем новый API для HPOS
            if (class_exists('WC_Order_Query')) {
                $query = new WC_Order_Query([
                    'limit' => -1,
                    'return' => 'ids',
                    'status' => 'any'
                ]);
                $order_ids = $query->get_orders();
                
                foreach ($order_ids as $order_id) {
                    $order = wc_get_order($order_id);
                    if ($order && $order->delete(true)) {
                        $deleted_count++;
                    }
                }
            }
        } else {
            // Fallback для старой системы (посты)
            $order_types = ['shop_order'];
            
            // В новых версиях могут быть другие типы
            if (function_exists('wc_get_order_types')) {
                $order_types = array_merge($order_types, wc_get_order_types('order-count'));
            }
            
            foreach ($order_types as $order_type) {
                $orders = get_posts([
                    'post_type' => $order_type,
                    'posts_per_page' => -1,
                    'post_status' => 'any',
                    'fields' => 'ids'
                ]);
                
                foreach ($orders as $order_id) {
                    // Пробуем удалить через WooCommerce API
                    $order = wc_get_order($order_id);
                    if ($order && $order->delete(true)) {
                        $deleted_count++;
                    } elseif (wp_delete_post($order_id, true)) {
                        // Fallback к стандартному WordPress API
                        $deleted_count++;
                    }
                }
            }
        }
        
        wp_send_json_success(['message' => "Удалено заказов: {$deleted_count}", 'count' => $deleted_count]);
    }
    
    /**
     * Обработчик AJAX запроса на удаление купонов
     */
    public function handle_delete_coupons() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Недостаточно прав доступа');
        }
        
        $deleted_count = 0;
        
        // Получаем все купоны
        $coupons = get_posts([
            'post_type' => 'shop_coupon',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids'
        ]);
        
        foreach ($coupons as $coupon_id) {
            if (wp_delete_post($coupon_id, true)) {
                $deleted_count++;
            }
        }
        
        wp_send_json_success(['message' => "Удалено купонов: {$deleted_count}", 'count' => $deleted_count]);
    }
    
    /**
     * Обработчик AJAX запроса на удаление отзывов
     */
    public function handle_delete_reviews() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Недостаточно прав доступа');
        }
        
        global $wpdb;
        
        // Удаляем отзывы о товарах
        $deleted_count = $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->comments} 
            WHERE comment_type = %s
        ", 'review'));
        
        wp_send_json_success(['message' => "Удалено отзывов: {$deleted_count}", 'count' => $deleted_count]);
    }
    
    /**
     * Полная очистка всех данных магазина
     */
    public function handle_clean_all_shop_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Недостаточно прав доступа');
        }
        
        global $wpdb;
        $results = [];
        
        // 1. Удаляем товары
        $products = get_posts(['post_type' => 'product', 'posts_per_page' => -1, 'fields' => 'ids']);
        $products_count = 0;
        foreach ($products as $product_id) {
            if (wp_delete_post($product_id, true)) $products_count++;
        }
        $results['products'] = $products_count;
          // 2. Удаляем заказы
        $orders_count = 0;
        
        // Проверяем, используется ли HPOS (High-Performance Order Storage)
        if (class_exists('Automattic\WooCommerce\Utilities\OrderUtil') && 
            method_exists('Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled') &&
            \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
            
            // Используем новый API для HPOS
            if (class_exists('WC_Order_Query')) {
                $query = new WC_Order_Query([
                    'limit' => -1,
                    'return' => 'ids',
                    'status' => 'any'
                ]);
                $order_ids = $query->get_orders();
                
                foreach ($order_ids as $order_id) {
                    $order = wc_get_order($order_id);
                    if ($order && $order->delete(true)) {
                        $orders_count++;
                    }
                }
            }
        } else {
            // Fallback для старой системы (посты)
            $order_types = ['shop_order'];
            
            // В новых версиях могут быть другие типы
            if (function_exists('wc_get_order_types')) {
                $order_types = array_merge($order_types, wc_get_order_types('order-count'));
            }
            
            foreach ($order_types as $order_type) {
                $orders = get_posts([
                    'post_type' => $order_type,
                    'posts_per_page' => -1,
                    'post_status' => 'any',
                    'fields' => 'ids'
                ]);
                
                foreach ($orders as $order_id) {
                    // Пробуем удалить через WooCommerce API
                    $order = wc_get_order($order_id);
                    if ($order && $order->delete(true)) {
                        $orders_count++;
                    } elseif (wp_delete_post($order_id, true)) {
                        // Fallback к стандартному WordPress API
                        $orders_count++;
                    }
                }
            }
        }
        $results['orders'] = $orders_count;
        
        // 3. Удаляем купоны
        $coupons = get_posts(['post_type' => 'shop_coupon', 'posts_per_page' => -1, 'fields' => 'ids']);
        $coupons_count = 0;
        foreach ($coupons as $coupon_id) {
            if (wp_delete_post($coupon_id, true)) $coupons_count++;
        }
        $results['coupons'] = $coupons_count;
        
        // 4. Удаляем категории
        $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
        $categories_count = 0;
        foreach ($categories as $category) {
            if (wp_delete_term($category->term_id, 'product_cat')) $categories_count++;
        }
        $results['categories'] = $categories_count;
        
        // 5. Удаляем теги
        $tags = get_terms(['taxonomy' => 'product_tag', 'hide_empty' => false]);
        $tags_count = 0;
        foreach ($tags as $tag) {
            if (wp_delete_term($tag->term_id, 'product_tag')) $tags_count++;
        }
        $results['tags'] = $tags_count;
        
        // 6. Удаляем атрибуты
        $attributes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies");
        $attributes_count = 0;
        foreach ($attributes as $attribute) {
            $terms = get_terms(['taxonomy' => 'pa_' . $attribute->attribute_name, 'hide_empty' => false]);
            foreach ($terms as $term) {
                wp_delete_term($term->term_id, 'pa_' . $attribute->attribute_name);
            }
            if ($wpdb->delete($wpdb->prefix . 'woocommerce_attribute_taxonomies', ['attribute_id' => $attribute->attribute_id])) {
                $attributes_count++;
            }
        }
        $results['attributes'] = $attributes_count;
        
        // 7. Удаляем отзывы
        $reviews_count = $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->comments} WHERE comment_type = %s", 'review'));
        $results['reviews'] = $reviews_count;
        
        // 8. Очищаем метаданные продуктов
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_product_%' OR meta_key LIKE '_wc_%'");
        
        // 9. Очищаем кэш
        wp_cache_flush();
        delete_transient('wc_attribute_taxonomies');
        if (function_exists('wc_delete_product_transients')) {
            wc_delete_product_transients();
        }
        
        $total = array_sum($results);
        
        wp_send_json_success([
            'message' => "Полная очистка завершена! Всего удалено элементов: {$total}",
            'results' => $results,
            'total' => $total
        ]);
    }
    
    /**
     * Получение статистики для очистки
     */
    public function handle_get_cleanup_stats() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Недостаточно прав доступа');
        }
        
        global $wpdb;
        
        $stats = [
            'products' => wp_count_posts('product')->publish + wp_count_posts('product')->draft + wp_count_posts('product')->private,
            'orders' => wp_count_posts('shop_order')->{'wc-completed'} + wp_count_posts('shop_order')->{'wc-processing'} + wp_count_posts('shop_order')->{'wc-pending'},
            'coupons' => wp_count_posts('shop_coupon')->publish,
            'categories' => wp_count_terms('product_cat'),
            'tags' => wp_count_terms('product_tag'),
            'attributes' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_attribute_taxonomies"),
            'reviews' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_type = %s", 'review'))
        ];
        
        wp_send_json_success($stats);
    }
    
    /**
     * Обработчик AJAX запроса на удаление страниц WordPress
     */
    public function handle_delete_pages() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('delete_pages')) {
            wp_die('Недостаточно прав доступа');
        }
        
        $deleted_count = 0;
        
        // Получаем все страницы
        $pages = get_posts([
            'post_type' => 'page',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids'
        ]);
        
        foreach ($pages as $page_id) {
            if (wp_delete_post($page_id, true)) {
                $deleted_count++;
            }
        }
        
        wp_send_json_success([
            'message' => "Удалено страниц: {$deleted_count}",
            'count' => $deleted_count
        ]);
    }
    
    /**
     * Обработчик AJAX запроса на удаление записей блога
     */
    public function handle_delete_posts() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('delete_posts')) {
            wp_die('Недостаточно прав доступа');
        }
        
        $deleted_count = 0;
        
        // Получаем все записи блога
        $posts = get_posts([
            'post_type' => 'post',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids'
        ]);
        
        foreach ($posts as $post_id) {
            if (wp_delete_post($post_id, true)) {
                $deleted_count++;
            }
        }
        
        wp_send_json_success([
            'message' => "Удалено записей блога: {$deleted_count}",
            'count' => $deleted_count
        ]);
    }
    
    /**
     * Обработчик AJAX запроса на удаление всех комментариев
     */
    public function handle_delete_all_comments() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('moderate_comments')) {
            wp_die('Недостаточно прав доступа');
        }
        
        global $wpdb;
        
        // Удаляем все комментарии кроме отзывов о товарах
        $deleted_count = $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->comments} 
            WHERE comment_type != %s OR comment_type IS NULL
        ", 'review'));
        
        // Очищаем мета данные комментариев
        $wpdb->query("DELETE FROM {$wpdb->commentmeta} WHERE comment_id NOT IN (SELECT comment_ID FROM {$wpdb->comments})");
        
        wp_send_json_success([
            'message' => "Удалено комментариев: {$deleted_count}",
            'count' => $deleted_count
        ]);
    }
    
    /**
     * Обработчик AJAX запроса на удаление спам-комментариев
     */
    public function handle_delete_spam_comments() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('moderate_comments')) {
            wp_die('Недостаточно прав доступа');
        }
        
        global $wpdb;
        
        // Удаляем спам-комментарии
        $deleted_count = $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->comments} 
            WHERE comment_approved = %s
        ", 'spam'));
        
        wp_send_json_success([
            'message' => "Удалено спам-комментариев: {$deleted_count}",
            'count' => $deleted_count
        ]);
    }
    
    /**
     * Обработчик AJAX запроса на удаление медиафайлов
     */
    public function handle_delete_media_files() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('delete_posts')) {
            wp_die('Недостаточно прав доступа');
        }
        
        $deleted_count = 0;
        
        // Получаем все медиафайлы
        $attachments = get_posts([
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids'
        ]);
        
        foreach ($attachments as $attachment_id) {
            if (wp_delete_attachment($attachment_id, true)) {
                $deleted_count++;
            }
        }
        
        wp_send_json_success([
            'message' => "Удалено медиафайлов: {$deleted_count}",
            'count' => $deleted_count
        ]);
    }
    
    /**
     * Обработчик AJAX запроса на удаление черновиков
     */
    public function handle_delete_drafts() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('delete_posts')) {
            wp_die('Недостаточно прав доступа');
        }
        
        $deleted_count = 0;
        
        // Получаем все черновики
        $drafts = get_posts([
            'post_status' => 'draft',
            'posts_per_page' => -1,
            'post_type' => 'any',
            'fields' => 'ids'
        ]);
        
        foreach ($drafts as $draft_id) {
            if (wp_delete_post($draft_id, true)) {
                $deleted_count++;
            }
        }
        
        wp_send_json_success([
            'message' => "Удалено черновиков: {$deleted_count}",
            'count' => $deleted_count
        ]);
    }
    
    /**
     * Обработчик AJAX запроса на очистку корзины
     */
    public function handle_delete_trash() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('delete_posts')) {
            wp_die('Недостаточно прав доступа');
        }
        
        $deleted_count = 0;
        
        // Получаем все элементы в корзине
        $trash_items = get_posts([
            'post_status' => 'trash',
            'posts_per_page' => -1,
            'post_type' => 'any',
            'fields' => 'ids'
        ]);
        
        foreach ($trash_items as $item_id) {
            if (wp_delete_post($item_id, true)) {
                $deleted_count++;
            }
        }
        
        wp_send_json_success([
            'message' => "Очищено из корзины: {$deleted_count}",
            'count' => $deleted_count
        ]);
    }
    
    /**
     * Обработчик AJAX запроса на удаление неиспользуемых тегов
     */
    public function handle_delete_unused_tags() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('manage_categories')) {
            wp_die('Недостаточно прав доступа');
        }
        
        global $wpdb;
        
        // Находим неиспользуемые теги
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}term_relationships tr
            INNER JOIN {$wpdb->prefix}posts p ON tr.object_id = p.ID
            WHERE p.post_status = %s
        ", 'publish'));
        
        // Удаляем неиспользуемые термины
        $unused_terms = $wpdb->get_results("
            SELECT t.term_id, tt.taxonomy
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            WHERE tt.count = 0
            AND tt.taxonomy IN ('post_tag', 'category')
        ");
        
        $deleted_count = 0;
        foreach ($unused_terms as $term) {
            if (wp_delete_term($term->term_id, $term->taxonomy)) {
                $deleted_count++;
            }
        }
        
        wp_send_json_success([
            'message' => "Удалено неиспользуемых тегов/категорий: {$deleted_count}",
            'count' => $deleted_count
        ]);
    }
    
    /**
     * Обработчик AJAX запроса на очистку транзиентов
     */
    public function handle_delete_transients() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав доступа');
        }
        
        global $wpdb;
        
        // Удаляем просроченные транзиенты
        $transients = $wpdb->get_results("SELECT * FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
        
        $deleted_count = 0;
        foreach ($transients as $transient) {
            if (delete_option($transient->option_name)) {
                $deleted_count++;
            }
        }
        
        // Очищаем кэш объектов
        wp_cache_flush();
        
        wp_send_json_success([
            'message' => "Очищено транзиентов: {$deleted_count}",
            'count' => $deleted_count
        ]);
    }
    
    /**
     * Полная очистка всех данных WordPress (не WooCommerce)
     */
    public function handle_clean_all_wordpress_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав доступа');
        }
        
        global $wpdb;
        $results = [];
        
        // 1. Удаляем страницы
        $pages = get_posts(['post_type' => 'page', 'posts_per_page' => -1, 'fields' => 'ids']);
        $pages_count = 0;
        foreach ($pages as $page_id) {
            if (wp_delete_post($page_id, true)) $pages_count++;
        }
        $results['pages'] = $pages_count;
        
        // 2. Удаляем записи блога
        $posts = get_posts(['post_type' => 'post', 'posts_per_page' => -1, 'fields' => 'ids']);
        $posts_count = 0;
        foreach ($posts as $post_id) {
            if (wp_delete_post($post_id, true)) $posts_count++;
        }
        $results['posts'] = $posts_count;
        
        // 3. Удаляем комментарии
        $comments_count = $wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_type != 'review' OR comment_type IS NULL");
        $results['comments'] = $comments_count;
        
        // 4. Удаляем медиафайлы
        $attachments = get_posts(['post_type' => 'attachment', 'posts_per_page' => -1, 'fields' => 'ids']);
        $media_count = 0;
        foreach ($attachments as $attachment_id) {
            if (wp_delete_attachment($attachment_id, true)) $media_count++;
        }
        $results['media'] = $media_count;
        
        // 5. Очищаем корзину
        $trash_items = get_posts(['post_status' => 'trash', 'posts_per_page' => -1, 'post_type' => 'any', 'fields' => 'ids']);
        $trash_count = 0;
        foreach ($trash_items as $item_id) {
            if (wp_delete_post($item_id, true)) $trash_count++;
        }
        $results['trash'] = $trash_count;
        
        // 6. Удаляем неиспользуемые термины
        $unused_terms = $wpdb->get_results("
            SELECT t.term_id, tt.taxonomy
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            WHERE tt.count = 0
            AND tt.taxonomy IN ('post_tag', 'category')
        ");
        $terms_count = 0;
        foreach ($unused_terms as $term) {
            if (wp_delete_term($term->term_id, $term->taxonomy)) $terms_count++;
        }
        $results['unused_terms'] = $terms_count;
        
        // 7. Очищаем транзиенты
        $transients = $wpdb->get_results("SELECT * FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
        $transients_count = 0;
        foreach ($transients as $transient) {
            if (delete_option($transient->option_name)) $transients_count++;
        }
        $results['transients'] = $transients_count;
        
        // 8. Очищаем кэш
        wp_cache_flush();
        
        $total = array_sum($results);
        
        wp_send_json_success([
            'message' => "Полная очистка WordPress завершена! Всего удалено элементов: {$total}",
            'results' => $results,
            'total' => $total
        ]);
    }
    
    /**
     * Обработчик AJAX запроса на сканирование файлов
     */
    public function handle_scan_files() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав доступа');
        }
        
        $folder_path = sanitize_text_field($_POST['folder_path']);
        $use_date_filter = isset($_POST['use_date_filter']) ? (bool)$_POST['use_date_filter'] : false;
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);
        $date_type = sanitize_text_field($_POST['date_type']);
        $filter_by_extension = isset($_POST['filter_by_extension']) ? (bool)$_POST['filter_by_extension'] : false;
        $file_extensions = sanitize_text_field($_POST['file_extensions']);
        $filter_by_size = isset($_POST['filter_by_size']) ? (bool)$_POST['filter_by_size'] : false;
        $size_operator = sanitize_text_field($_POST['size_operator']);
        $file_size = floatval($_POST['file_size']);
        $size_unit = sanitize_text_field($_POST['size_unit']);
        
        if (!is_dir($folder_path)) {
            wp_send_json_error(['message' => 'Указанная папка не существует']);
            return;
        }
        
        try {
            $scan_result = $this->scan_directory($folder_path, [
                'use_date_filter' => $use_date_filter,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'date_type' => $date_type,
                'filter_by_extension' => $filter_by_extension,
                'file_extensions' => $file_extensions,
                'filter_by_size' => $filter_by_size,
                'size_operator' => $size_operator,
                'file_size' => $file_size,
                'size_unit' => $size_unit
            ]);
            
            wp_send_json_success($scan_result);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Ошибка сканирования: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Обработчик AJAX запроса на удаление файлов
     */
    public function handle_delete_files() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав доступа');
        }
        
        $folder_path = sanitize_text_field($_POST['folder_path']);
        $use_date_filter = isset($_POST['use_date_filter']) ? (bool)$_POST['use_date_filter'] : false;
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);
        $date_type = sanitize_text_field($_POST['date_type']);
        $filter_by_extension = isset($_POST['filter_by_extension']) ? (bool)$_POST['filter_by_extension'] : false;
        $file_extensions = sanitize_text_field($_POST['file_extensions']);
        $filter_by_size = isset($_POST['filter_by_size']) ? (bool)$_POST['filter_by_size'] : false;
        $size_operator = sanitize_text_field($_POST['size_operator']);
        $file_size = floatval($_POST['file_size']);
        $size_unit = sanitize_text_field($_POST['size_unit']);
        
        if (!is_dir($folder_path)) {
            wp_send_json_error(['message' => 'Указанная папка не существует']);
            return;
        }
        
        try {
            $delete_result = $this->delete_files_by_criteria($folder_path, [
                'use_date_filter' => $use_date_filter,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'date_type' => $date_type,
                'filter_by_extension' => $filter_by_extension,
                'file_extensions' => $file_extensions,
                'filter_by_size' => $filter_by_size,
                'size_operator' => $size_operator,
                'file_size' => $file_size,
                'size_unit' => $size_unit
            ]);
            
            wp_send_json_success($delete_result);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Ошибка удаления: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Сканирование папки с фильтрами
     */
    private function scan_directory($folder_path, $filters) {
        $files_found = [];
        $total_files = 0;
        $total_size = 0;
        $folder_total_files = 0;
        $folder_total_size = 0;
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder_path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $folder_total_files++;
                $folder_total_size += $file->getSize();
                
                if ($this->file_matches_criteria($file, $filters)) {
                    $files_found[] = $file->getPathname();
                    $total_files++;
                    $total_size += $file->getSize();
                }
            }
        }
        
        return [
            'files_count' => $total_files,
            'files_size' => $this->format_bytes($total_size),
            'total_files' => $folder_total_files,
            'folder_size' => $this->format_bytes($folder_total_size),
            'files_list' => $files_found
        ];
    }
    
    /**
     * Удаление файлов по критериям
     */
    private function delete_files_by_criteria($folder_path, $filters) {
        $deleted_count = 0;
        $deleted_size = 0;
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder_path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $this->file_matches_criteria($file, $filters)) {
                $file_size = $file->getSize();
                if (unlink($file->getPathname())) {
                    $deleted_count++;
                    $deleted_size += $file_size;
                }
            }
        }
        
        return [
            'deleted_count' => $deleted_count,
            'deleted_size' => $this->format_bytes($deleted_size),
            'message' => "Успешно удалено {$deleted_count} файлов общим размером " . $this->format_bytes($deleted_size)
        ];
    }
    
    /**
     * Проверка соответствия файла критериям
     */
    private function file_matches_criteria($file, $filters) {
        // Фильтр по дате
        if ($filters['use_date_filter'] && !empty($filters['date_from']) && !empty($filters['date_to'])) {
            $date_from = strtotime($filters['date_from']);
            $date_to = strtotime($filters['date_to']) + 86400; // +1 день для включения всего дня
            
            if ($filters['date_type'] === 'created') {
                $file_date = $file->getCTime();
            } else {
                $file_date = $file->getMTime();
            }
            
            if ($file_date < $date_from || $file_date > $date_to) {
                return false;
            }
        }
        
        // Фильтр по расширению
        if ($filters['filter_by_extension'] && !empty($filters['file_extensions'])) {
            $allowed_extensions = array_map('trim', explode(',', strtolower($filters['file_extensions'])));
            $file_extension = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
            
            if (!in_array($file_extension, $allowed_extensions)) {
                return false;
            }
        }
        
        // Фильтр по размеру
        if ($filters['filter_by_size'] && !empty($filters['file_size'])) {
            $file_size_bytes = $file->getSize();
            $target_size_bytes = $this->convert_size_to_bytes($filters['file_size'], $filters['size_unit']);
            
            switch ($filters['size_operator']) {
                case 'gt':
                    if ($file_size_bytes <= $target_size_bytes) return false;
                    break;
                case 'lt':
                    if ($file_size_bytes >= $target_size_bytes) return false;
                    break;
                case 'eq':
                    if (abs($file_size_bytes - $target_size_bytes) > 1024) return false; // погрешность 1KB
                    break;
            }
        }
        
        return true;
    }
    
    /**
     * Конвертация размера в байты
     */
    private function convert_size_to_bytes($size, $unit) {
        $multipliers = [
            'KB' => 1024,
            'MB' => 1024 * 1024,
            'GB' => 1024 * 1024 * 1024
        ];
        
        return $size * ($multipliers[$unit] ?? 1);
    }
    
    /**
     * Форматирование размера файла
     */
    private function format_bytes($bytes, $precision = 2) {
        $units = ['байт', 'КБ', 'МБ', 'ГБ', 'ТБ'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Обработчик AJAX запроса на просмотр содержимого папок
     */
    public function handle_browse_folders() {
        if (!wp_verify_nonce($_POST['nonce'], 'neetrino_cleanup_nonce')) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав доступа');
        }
        
        $path = $this->normalize_path(sanitize_text_field($_POST['path']));
        
        // Быстрая проверка существования папки
        if (!is_dir($path) || !is_readable($path)) {
            wp_send_json_error(['message' => 'Папка недоступна']);
            return;
        }
        
        $folders = [];
        
        // Быстрое сканирование без подсчета файлов
        if ($handle = opendir($path)) {
            while (false !== ($item = readdir($handle))) {
                if ($item === '.' || $item === '..' || $item[0] === '.') {
                    continue; // Пропускаем скрытые файлы
                }
                
                $item_path = $path . DIRECTORY_SEPARATOR . $item;
                
                if (is_dir($item_path)) {
                    $folders[] = [
                        'name' => $item,
                        'path' => $this->normalize_path($item_path)
                    ];
                }
            }
            closedir($handle);
            
            // Быстрая сортировка
            usort($folders, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
            
            wp_send_json_success(['folders' => $folders]);
        } else {
            wp_send_json_error(['message' => 'Не удалось открыть папку']);
        }
    }
    
    /**
     * Нормализация путей для красивого отображения
     */
    private function normalize_path($path) {
        if (empty($path)) return '';
        
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('/\/+/', '/', $path);
        
        return rtrim($path, '/') ?: '/';
    }
}

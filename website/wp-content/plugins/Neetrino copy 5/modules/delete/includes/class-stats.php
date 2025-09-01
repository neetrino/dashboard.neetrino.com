<?php
/**
 * Statistics Helper for Delete Module
 * 
 * Handles statistics calculation for WordPress and WooCommerce data
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_Delete_Stats {
    
    /**
     * Получить все статистики включая WordPress данные
     */
    public static function get_all_stats() {
        if (!class_exists('WooCommerce')) {
            return [
                'products' => 0,
                'categories' => 0,
                'tags' => 0,
                'attributes' => 0,
                'orders' => 0,
                'coupons' => 0,
                'reviews' => 0,
                // Добавляем WordPress статистики
                'pages' => self::get_pages_count(),
                'posts' => self::get_posts_count(),
                'comments' => self::get_comments_count(),
                'spam_comments' => self::get_spam_comments_count(),
                'media' => self::get_media_count(),
                'drafts' => self::get_drafts_count(),
                'trash' => self::get_trash_count(),
                'unused_terms' => self::get_unused_terms_count(),
                'transients' => self::get_transients_count()
            ];
        }
        
        return [
            'products' => self::get_products_count(),
            'categories' => self::get_categories_count(),
            'tags' => self::get_tags_count(),
            'attributes' => self::get_attributes_count(),
            'orders' => self::get_orders_count(),
            'coupons' => self::get_coupons_count(),
            'reviews' => self::get_reviews_count(),
            // Добавляем WordPress статистики
            'pages' => self::get_pages_count(),
            'posts' => self::get_posts_count(),
            'comments' => self::get_comments_count(),
            'spam_comments' => self::get_spam_comments_count(),
            'media' => self::get_media_count(),
            'drafts' => self::get_drafts_count(),
            'trash' => self::get_trash_count(),
            'unused_terms' => self::get_unused_terms_count(),
            'transients' => self::get_transients_count()
        ];
    }
    
    /**
     * Подсчитать количество товаров
     */
    public static function get_products_count() {
        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids'
        ];
        $products = get_posts($args);
        return count($products);
    }
    
    /**
     * Подсчитать количество категорий
     */
    public static function get_categories_count() {
        return wp_count_terms('product_cat');
    }
    
    /**
     * Подсчитать количество тегов
     */
    public static function get_tags_count() {
        return wp_count_terms('product_tag');
    }
    
    /**
     * Подсчитать количество атрибутов
     */
    public static function get_attributes_count() {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_attribute_taxonomies");
    }
      /**
     * Подсчитать количество заказов
     */
    public static function get_orders_count() {
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
                $orders = $query->get_orders();
                return count($orders);
            }
        }
        
        // Fallback для старой системы (посты)
        // Проверяем разные типы заказов
        $order_types = ['shop_order'];
        
        // В новых версиях могут быть другие типы
        if (function_exists('wc_get_order_types')) {
            $order_types = array_merge($order_types, wc_get_order_types('order-count'));
        }
        
        $total_orders = 0;
        foreach ($order_types as $order_type) {
            $orders = get_posts([
                'post_type' => $order_type,
                'posts_per_page' => -1,
                'post_status' => 'any',
                'fields' => 'ids'
            ]);
            $total_orders += count($orders);
        }
        
        return $total_orders;
    }
    
    /**
     * Подсчитать количество купонов
     */
    public static function get_coupons_count() {
        $coupons_stats = wp_count_posts('shop_coupon');
        return (int) $coupons_stats->publish;
    }
    
    /**
     * Подсчитать количество отзывов
     */
    public static function get_reviews_count() {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_type = %s", 
            'review'
        ));
    }
    
    /**
     * Получить количество страниц
     */
    public static function get_pages_count() {
        $pages_stats = wp_count_posts('page');
        return (int) ($pages_stats->publish + $pages_stats->draft + $pages_stats->private + $pages_stats->pending);
    }
    
    /**
     * Получить количество записей
     */
    public static function get_posts_count() {
        $posts_stats = wp_count_posts('post');
        return (int) ($posts_stats->publish + $posts_stats->draft + $posts_stats->private + $posts_stats->pending);
    }
    
    /**
     * Получить количество комментариев
     */
    public static function get_comments_count() {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->comments} 
            WHERE comment_type != %s OR comment_type IS NULL
        ", 'review'));
    }
    
    /**
     * Получить количество спам-комментариев
     */
    public static function get_spam_comments_count() {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->comments} 
            WHERE comment_approved = %s
        ", 'spam'));
    }
    
    /**
     * Получить количество медиафайлов
     */
    public static function get_media_count() {
        $media_stats = wp_count_posts('attachment');
        return (int) $media_stats->inherit;
    }
    
    /**
     * Получить количество черновиков
     */
    public static function get_drafts_count() {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_status = %s
        ", 'draft'));
    }
    
    /**
     * Получить количество элементов в корзине
     */
    public static function get_trash_count() {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_status = %s
        ", 'trash'));
    }
    
    /**
     * Получить количество неиспользуемых терминов
     */
    public static function get_unused_terms_count() {
        global $wpdb;
        return (int) $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            WHERE tt.count = 0
            AND tt.taxonomy IN ('post_tag', 'category')
        ");
    }
    
    /**
     * Получить количество временных данных
     */
    public static function get_transients_count() {
        global $wpdb;
        return (int) $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_%'
        ");
    }
    
    /**
     * Получить общее количество элементов
     */
    public static function get_total_items_count() {
        $stats = self::get_all_stats();
        return array_sum($stats);
    }
}

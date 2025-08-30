<?php
/**
 * Telegram Notifications Status Checker
 * Показывает текущее состояние уведомлений и транзиентов
 */

// Безопасность
if (!defined('ABSPATH')) {
    require_once('../../../../../wp-config.php');
}

if (!is_admin() && !current_user_can('manage_options')) {
    wp_die('У вас нет прав для выполнения этого действия.');
}

echo "<h1>📊 Telegram Notifications Status</h1>";

global $wpdb;

// Проверяем активные транзиенты уведомлений
$active_transients = $wpdb->get_results(
    "SELECT option_name, option_value 
     FROM {$wpdb->options} 
     WHERE option_name LIKE '_transient_telegram_%notification%'
     ORDER BY option_name"
);

echo "<h2>🔄 Активные блокировки уведомлений:</h2>";
if ($active_transients) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Тип уведомления</th><th>ID заказа</th><th>Время создания</th></tr>";
    
    foreach ($active_transients as $transient) {
        $name = str_replace('_transient_', '', $transient->option_name);
        $time = date('d.m.Y H:i:s', $transient->option_value);
        
        if (strpos($name, 'telegram_order_notification_') === 0) {
            $order_id = str_replace('telegram_order_notification_', '', $name);
            $type = "Новый заказ";
        } elseif (strpos($name, 'telegram_status_notification_') === 0) {
            $parts = explode('_', $name);
            $order_id = $parts[3] ?? 'неизвестно';
            $type = "Изменение статуса";
        } else {
            $order_id = 'неизвестно';
            $type = 'Другое';
        }
        
        echo "<tr><td>$type</td><td>$order_id</td><td>$time</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>✅ Нет активных блокировок уведомлений</p>";
}

// Проверяем все telegram транзиенты
$all_telegram_transients = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->options} 
     WHERE option_name LIKE '_transient%telegram_%'"
);

echo "<h2>📈 Статистика:</h2>";
echo "<ul>";
echo "<li><strong>Всего telegram транзиентов:</strong> $all_telegram_transients</li>";
echo "<li><strong>Активных блокировок:</strong> " . count($active_transients) . "</li>";
echo "</ul>";

// Проверяем запланированные события очистки
$next_cleanup = wp_next_scheduled('telegram_cleanup_old_transients');
echo "<h2>🧹 Автоматическая очистка:</h2>";
if ($next_cleanup) {
    echo "<p>✅ Следующая очистка: <strong>" . date('d.m.Y H:i:s', $next_cleanup) . "</strong></p>";
} else {
    echo "<p>⚠️ Автоматическая очистка не запланирована</p>";
}

// Проверяем последние заказы
$recent_orders = $wpdb->get_results(
    "SELECT ID, post_date, post_status 
     FROM {$wpdb->posts} 
     WHERE post_type = 'shop_order' 
     ORDER BY post_date DESC 
     LIMIT 5"
);

echo "<h2>📦 Последние заказы:</h2>";
if ($recent_orders) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID заказа</th><th>Дата создания</th><th>Статус</th><th>Блокировка уведомления</th></tr>";
    
    foreach ($recent_orders as $order) {
        $notification_key = 'telegram_order_notification_' . $order->ID;
        $is_blocked = get_transient($notification_key) ? '🔒 Заблокировано' : '✅ Доступно';
        $order_date = date('d.m.Y H:i:s', strtotime($order->post_date));
        
        echo "<tr>";
        echo "<td>#{$order->ID}</td>";
        echo "<td>$order_date</td>";
        echo "<td>{$order->post_status}</td>";
        echo "<td>$is_blocked</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Заказы не найдены</p>";
}

echo "<hr>";
echo '<p><a href="' . admin_url() . '" class="button button-primary">← Вернуться в админку</a></p>';
echo '<p><a href="debug-cleanup.php" class="button button-secondary">🧹 Запустить очистку</a></p>';
?>

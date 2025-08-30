<?php
/**
 * Класс для обработки переключений пользователей
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_User_Switching_Handler {
    
    private $original_user_id = null;
    
    public function __construct() {
        $this->load_current_session();
        $this->setup_hooks();
    }
    
    private function setup_hooks() {
        // Обработка действий переключения
        add_action('admin_init', [$this, 'handle_user_switching']);
        add_action('wp_loaded', [$this, 'check_switch_back']);
    }
    
    private function load_current_session() {
        // Проверяем, находимся ли мы в режиме переключения
        if (isset($_COOKIE['neetrino_user_switching_original'])) {
            $this->original_user_id = intval($_COOKIE['neetrino_user_switching_original']);
        }
    }
    
    /**
     * Обработка действий переключения пользователей
     */
    public function handle_user_switching() {
        // Переключение на пользователя
        if (isset($_GET['action']) && $_GET['action'] === 'neetrino_switch_to_user') {
            $this->handle_switch_to();
        }
        
        // Возврат к исходному пользователю
        if (isset($_GET['action']) && $_GET['action'] === 'neetrino_switch_back') {
            $this->handle_switch_back();
        }
    }
    
    /**
     * Проверка switch back на всех страницах
     */
    public function check_switch_back() {
        if (isset($_GET['action']) && $_GET['action'] === 'neetrino_switch_back') {
            $this->handle_switch_back();
        }
    }
    
    /**
     * Обработка переключения на другого пользователя
     */
    private function handle_switch_to() {
        $user_id = intval($_GET['user_id']);
        
        // Проверка nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'neetrino_switch_to_' . $user_id)) {
            wp_die(__('Security check failed', 'neetrino'));
        }
        
        // Проверка прав
        if (!current_user_can('edit_users')) {
            wp_die(__('You do not have permission to switch users', 'neetrino'));
        }
        
        // Проверка существования пользователя
        $target_user = get_user_by('id', $user_id);
        if (!$target_user) {
            wp_die(__('User not found', 'neetrino'));
        }
        
        // Сохраняем ID текущего пользователя
        $current_user_id = get_current_user_id();
        
        // Устанавливаем cookie с исходным пользователем (на 24 часа)
        setcookie(
            'neetrino_user_switching_original',
            $current_user_id,
            time() + DAY_IN_SECONDS,
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );
        
        // Переключаемся на нового пользователя
        wp_clear_auth_cookie();
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);
        
        // Перенаправляем в админку
        wp_redirect(admin_url());
        exit;
    }
    
    /**
     * Обработка возврата к исходному пользователю
     */
    private function handle_switch_back() {
        // Проверка nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'neetrino_switch_back')) {
            wp_die(__('Security check failed', 'neetrino'));
        }
        
        // Получаем ID исходного пользователя
        $original_user_id = isset($_COOKIE['neetrino_user_switching_original']) 
            ? intval($_COOKIE['neetrino_user_switching_original']) 
            : null;
        
        if (!$original_user_id) {
            wp_die(__('No original user found', 'neetrino'));
        }
        
        // Проверяем существование исходного пользователя
        $original_user = get_user_by('id', $original_user_id);
        if (!$original_user) {
            wp_die(__('Original user not found', 'neetrino'));
        }
        
        // Удаляем cookie
        setcookie(
            'neetrino_user_switching_original',
            '',
            time() - 3600,
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );
        
        // Возвращаемся к исходному пользователю
        wp_clear_auth_cookie();
        wp_set_current_user($original_user_id);
        wp_set_auth_cookie($original_user_id, true);
        
        // Перенаправляем в админку
        wp_redirect(admin_url('users.php'));
        exit;
    }
    
    /**
     * Проверяет, находимся ли мы в режиме переключения
     */
    public function is_switched() {
        return !empty($this->original_user_id);
    }
    
    /**
     * Получает ID исходного пользователя
     */
    public function get_original_user_id() {
        return $this->original_user_id;
    }
}

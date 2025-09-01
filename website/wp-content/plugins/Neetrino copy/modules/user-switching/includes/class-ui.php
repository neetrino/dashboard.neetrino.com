<?php
/**
 * Класс для пользовательского интерфейса User Switching
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_User_Switching_UI {
    
    private $handler;
    
    public function __construct($handler) {
        $this->handler = $handler;
        $this->setup_hooks();
    }
    
    private function setup_hooks() {
        // Добавляем ссылки в список пользователей
        add_filter('user_row_actions', [$this, 'add_switch_to_link'], 10, 2);
        
        // Добавляем ссылки в профиль пользователя
        add_action('show_user_profile', [$this, 'add_switch_to_profile_link']);
        add_action('edit_user_profile', [$this, 'add_switch_to_profile_link']);
        
        // Добавляем кнопку "Switch Back" в админ-бар
        add_action('admin_bar_menu', [$this, 'add_switch_back_link'], 999);
        
        // Добавляем фиксированную кнопку в нижний угол
        add_action('admin_footer', [$this, 'add_fixed_switch_back_button']);
        add_action('wp_footer', [$this, 'add_fixed_switch_back_button']);
    }
    
    /**
     * Добавляет ссылку "Switch To" в список пользователей
     */
    public function add_switch_to_link($actions, $user) {
        // Проверяем права пользователя
        if (!current_user_can('edit_users') || !current_user_can('list_users')) {
            return $actions;
        }
        
        // Нельзя переключиться на самого себя
        if (get_current_user_id() === $user->ID) {
            return $actions;
        }
        
        // Создаем URL для переключения
        $switch_url = wp_nonce_url(
            add_query_arg([
                'action' => 'neetrino_switch_to_user',
                'user_id' => $user->ID
            ], admin_url('users.php')),
            'neetrino_switch_to_' . $user->ID
        );
        
        $actions['neetrino_switch_to'] = sprintf(
            '<a href="%s" class="neetrino-switch-to-link">%s</a>',
            esc_url($switch_url),
            __('Switch To', 'neetrino')
        );
        
        return $actions;
    }
    
    /**
     * Добавляет ссылку "Switch To" в профиль пользователя
     */
    public function add_switch_to_profile_link($user) {
        // Проверяем права пользователя
        if (!current_user_can('edit_users') || get_current_user_id() === $user->ID) {
            return;
        }
        
        $switch_url = wp_nonce_url(
            add_query_arg([
                'action' => 'neetrino_switch_to_user',
                'user_id' => $user->ID
            ], admin_url('users.php')),
            'neetrino_switch_to_' . $user->ID
        );
        
        echo '<h2>' . __('User Switching', 'neetrino') . '</h2>';
        echo '<table class="form-table">';
        echo '<tr><td>';
        echo sprintf(
            '<a href="%s" class="button button-secondary neetrino-switch-to-button">%s</a>',
            esc_url($switch_url),
            __('Switch To This User', 'neetrino')
        );
        echo '</td></tr>';
        echo '</table>';
    }
    
    /**
     * Добавляет кнопку "Switch Back" в админ-бар
     */
    public function add_switch_back_link($wp_admin_bar) {
        // Показываем только если мы переключились с другого пользователя
        if (!$this->handler->is_switched()) {
            return;
        }
        
        $original_user = get_user_by('id', $this->handler->get_original_user_id());
        if (!$original_user) {
            return;
        }
        
        $switch_back_url = wp_nonce_url(
            add_query_arg([
                'action' => 'neetrino_switch_back'
            ], admin_url()),
            'neetrino_switch_back'
        );
        
        $wp_admin_bar->add_node([
            'id' => 'neetrino-switch-back',
            'title' => sprintf(
                '<span class="neetrino-switch-back-text">%s <strong>%s</strong></span>',
                __('Switch back to:', 'neetrino'),
                esc_html($original_user->display_name)
            ),
            'href' => $switch_back_url,
            'meta' => [
                'class' => 'neetrino-switch-back-link'
            ]
        ]);
    }    /**
     * Добавляет фиксированную кнопку "Switch Back" в нижний угол
     */
    public function add_fixed_switch_back_button() {
        // Показываем только если мы переключились с другого пользователя
        if (!$this->handler->is_switched()) {
            return;
        }
        
        $original_user = get_user_by('id', $this->handler->get_original_user_id());
        if (!$original_user) {
            return;
        }
        
        $switch_back_url = wp_nonce_url(
            add_query_arg([
                'action' => 'neetrino_switch_back'
            ], admin_url()),
            'neetrino_switch_back'
        );
        
        ?>
        <div id="neetrino-switch-back-fixed" class="neetrino-switch-back-fixed">
            <div class="neetrino-switch-container">
                <!-- Стрелка слева от кнопки -->
                <div class="neetrino-switch-arrow-left">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M19 12H6m0 0l6 6m-6-6l6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                
                <!-- Основная кнопка -->
                <a href="<?php echo esc_url($switch_back_url); ?>" class="neetrino-switch-back-btn" title="<?php printf(__('Switch back to %s', 'neetrino'), esc_html($original_user->display_name)); ?>">
                    <span class="neetrino-switch-icon">👤</span>
                    <span class="neetrino-switch-text"><?php _e('Switch Back', 'neetrino'); ?></span>
                </a>
            </div>
        </div>
        <?php
    }
}

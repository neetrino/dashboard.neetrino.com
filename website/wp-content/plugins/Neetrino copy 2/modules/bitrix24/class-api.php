<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Класс для работы с API Bitrix24
 */
class WPBitrixAPI {
    // Используем ту же константу, что и в основном классе
    const OPTION_NAME = 'wp_bitrix_sync_options';

    /**
     * Отправка данных в Bitrix24
     *
     * @param array $data Данные для отправки
     * @param bool $manual Флаг ручного запуска
     * @return bool Успешность отправки
     */
    public function send_data_to_bitrix($data, $manual = false) {
        // Получение настроек для Bitrix24
        $default_options = array(
            'webhook_url' => '',
            'entity_type_id' => '1226',
            'item_name' => parse_url(home_url(), PHP_URL_HOST)
        );

        $options = get_option(self::OPTION_NAME, array());
        
        // Получаем вебхук, используя метод расшифровки (только для внутреннего использования)
        $wpbitrix = new WPBitrixSync();
        $webhook_url = $wpbitrix->get_webhook_url(true);
        
        $entity_type_id = !empty($options['entity_type_id']) ? trim($options['entity_type_id']) : $default_options['entity_type_id'];
        $item_name = !empty($options['item_name']) ? trim($options['item_name']) : $default_options['item_name'];

        if (empty($item_name)) {
            $item_name = parse_url(home_url(), PHP_URL_HOST);
        }

        if (empty($webhook_url) || empty($entity_type_id)) {
            error_log('WP Bitrix Sync: не заданы webhook_url или entity_type_id. Отправка прервана.');
            return false;
        }

        // Формирование запроса для создания нового элемента в Bitrix24
        $query_data = [
            'fields' => [
                'TITLE' => $item_name,
                'ufCrm93WpVersion'      => $data['wp_version'],
                'ufCrm93StorageMb'      => $data['storage_size'],
                'ufCrm93UserCount'      => $data['user_count'],
                'ufCrm93PluginsUpdates' => $data['plugins_updates_count'],
                'ufCrm93ThemesUpdates'  => $data['themes_updates_count'],
                'ufCrm93PluginList'     => $data['plugins_list'],
                'ufCrm93Payment'        => !empty($data['payment_plugins']) ? $data['payment_plugins'] : ['❌ Нет платежных плагинов'],
                'ufCrm93LenguagePlugin' => !empty($data['language_plugins']) ? $data['language_plugins'] : ['❌ Нет языковых плагинов'],
                'ufCrm93Cache'          => !empty($data['cache_plugins']) ? $data['cache_plugins'] : ['❌ Нет плагинов кэширования'],
                'ufCrm93UpdateManager'  => $data['easy_updates_manager'],
                'ufCrm93AdminMail'      => $data['admin_email'],
                'ufCrm93ThemeChild'     => $data['child_theme'],
                'ufCrm93Theme'          => $data['active_theme'],
                'ufCrm93Revolution'     => $data['slider_revolution'],
                'ufCrm93Comments'       => $data['total_comments'],
            ],
            'params' => ["REGISTER_SONET_EVENT" => "Y"],
        ];

        $response = wp_remote_post(
            $webhook_url . 'crm.item.add?entityTypeId=' . $entity_type_id,
            ['body' => http_build_query($query_data)]
        );

        if (is_wp_error($response)) {
            error_log('WP Bitrix Sync: ошибка отправки данных — ' . $response->get_error_message());
            return false;
        } else {
            error_log('WP Bitrix Sync: данные успешно отправлены (entityTypeId=' . $entity_type_id . ').');
            if ($manual) {
                error_log('WP Bitrix Sync: ручной запуск, переданные данные: ' . print_r($data, true));
            }
            return true;
        }
    }
}

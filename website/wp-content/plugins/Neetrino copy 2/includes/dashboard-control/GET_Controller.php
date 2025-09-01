<?php
/**
 * Lightweight GET-based controller for dashboard-control namespace
 * Supports secure command execution via query params similar to Remote Control
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neetrino_GET_Controller {
    public static function init() {
        add_action('init', [__CLASS__, 'maybe_handle_request']);
    }

    public static function maybe_handle_request() {
        if (!isset($_GET['dashboard_control'])) {
            return;
        }

        $command = sanitize_text_field($_GET['dashboard_control']);
        $key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';

        if (!self::verify_api_key($key)) {
            self::json_error('Invalid API key', 403);
        }

        switch ($command) {
            case 'maintenance':
                $mode = isset($_GET['mode']) ? sanitize_text_field($_GET['mode']) : '';
                $result = self::set_maintenance_mode($mode);
                self::json_from_result($result);
                break;

            case 'status':
                $result = Neetrino_REST_API::get_status();
                if ($result instanceof WP_REST_Response) {
                    $data = $result->get_data();
                } else {
                    $data = $result;
                }
                wp_send_json_success($data);
                break;

            case 'bitrix24_sync':
                if (!class_exists('Neetrino') || !Neetrino::is_module_active('bitrix24')) {
                    self::json_error('Bitrix24 module is not active', 400);
                }
                if (class_exists('Remote_Control_API')) {
                    $rc = new Remote_Control_API();
                    $ref = new ReflectionClass($rc);
                    if ($ref->hasMethod('trigger_bitrix24_sync')) {
                        $m = $ref->getMethod('trigger_bitrix24_sync');
                        $m->setAccessible(true);
                        $ok = $m->invoke($rc);
                        if ($ok) {
                            wp_send_json_success(['message' => 'Bitrix24 sync triggered', 'timestamp' => current_time('mysql')]);
                        } else {
                            self::json_error('Failed to trigger Bitrix24 sync', 500);
                        }
                    }
                }
                self::json_error('Bitrix24 sync handler not available', 500);
                break;

            case 'delete_plugin':
                $confirm = isset($_GET['confirm']) ? sanitize_text_field($_GET['confirm']) : '';
                if ($confirm !== 'YES_DELETE_PLUGIN') {
                    self::json_error('Confirmation required: confirm=YES_DELETE_PLUGIN', 400);
                }
                $result = self::delete_plugin();
                self::json_from_result($result);
                break;

            default:
                self::json_error('Unknown command', 400);
        }
    }

    private static function verify_api_key($api_key) {
        if (empty($api_key)) {
            return false;
        }
        $stored_key = get_option('neetrino_dashboard_api_key');
        return !empty($stored_key) && hash_equals($stored_key, $api_key);
    }

    private static function set_maintenance_mode($mode) {
        if (!in_array($mode, ['open', 'maintenance', 'closed'], true)) {
            return [
                'success' => false,
                'message' => 'Invalid mode. Allowed: open, maintenance, closed'
            ];
        }
        // Build a REST-like request to reuse internal logic
        $request = new WP_REST_Request('POST', '/neetrino/v1/command');
        $request->set_param('command', 'maintenance_mode');
        $request->set_param('api_key', get_option('neetrino_dashboard_api_key'));
        $request->set_param('data', ['mode' => $mode]);
        return Neetrino_REST_API::execute_command($request);
    }

    private static function delete_plugin() {
        $request = new WP_REST_Request('POST', '/neetrino/v1/command');
        $request->set_param('command', 'delete_plugin');
        $request->set_param('api_key', get_option('neetrino_dashboard_api_key'));
        $request->set_param('data', []);
        return Neetrino_REST_API::execute_command($request);
    }

    private static function json_from_result($result) {
        if ($result instanceof WP_REST_Response) {
            $data = $result->get_data();
        } else {
            $data = $result;
        }
        if (is_array($data) && isset($data['success']) && $data['success']) {
            wp_send_json_success($data);
        }
        $message = is_array($data) && isset($data['message']) ? $data['message'] : 'Unknown error';
        self::json_error($message, 400);
    }

    private static function json_error($message, $code = 400) {
        status_header($code);
        wp_send_json_error(['message' => $message, 'code' => $code], $code);
    }
}

Neetrino_GET_Controller::init();
?>

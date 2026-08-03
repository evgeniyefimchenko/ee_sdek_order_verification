<?php
if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Registry;
use Tygh\Addons\EeSdekOrderVerification\EeSdekOrderVerification;

/**
 * Установка модуля
 */
function fn_ee_sdek_order_verification_install() {
    // Заглушка - функционал установки будет реализован в будущих версиях
    fn_set_notification('W', __('success'), __('ee_sdek_order_verification.addon_installed'));
}

/**
 * Удаление модуля
 */
function fn_ee_sdek_order_verification_uninstall() {
    return true;
}

/**
 * Получить экземпляр класса СДЭК
 */
function fn_ee_sdek_get_instance() {
    $settings = Registry::get('addons.ee_sdek_order_verification');
    
    if (empty($settings['enabled']) || $settings['enabled'] !== 'Y') {
        return false;
    }
    
    if (empty($settings['login']) || empty($settings['password'])) {
        return false;
    }
    
    return new EeSdekOrderVerification([0, 1, 2, 3]);
}

/**
 * Обновление статусов заказов СДЭК
 */
function fn_ee_sdek_update_statuses($cdek_number = null) {
    $sdek = fn_ee_sdek_get_instance();
    if (!$sdek) {
        return false;
    }
    
    $settings = Registry::get('addons.ee_sdek_order_verification');
    $batch_size = !empty($settings['batch_size']) ? (int)$settings['batch_size'] : 50;
    
    // Получаем отгрузки СДЭК
    if ($cdek_number) {
        $shipments = db_get_array(
            'SELECT shipment_id, tracking_number, order_id FROM ?:shipments 
             WHERE carrier LIKE ?s AND tracking_number = ?s',
            '%sdek%',
            $cdek_number
        );
    } else {
        $shipments = db_get_array(
            'SELECT shipment_id, tracking_number, order_id FROM ?:shipments 
             WHERE carrier LIKE ?s 
             ORDER BY shipment_id DESC 
             LIMIT ?i',
            '%sdek%',
            $batch_size
        );
    }
    
    if (empty($shipments)) {
        return [];
    }
    
    $statuses = [];
    foreach ($shipments as $shipment) {
        $result = $sdek->get_order_info(null, $shipment['tracking_number']);
        if (!empty($result['error'])) {
            continue;
        }
        
        $statuses[$shipment['shipment_id']] = [
            'shipment_id' => $shipment['shipment_id'],
            'order_id' => $shipment['order_id'],
            'data' => $result,
        ];
    }
    
    return $statuses;
}

/**
 * Информация о подключении для админки
 */
function fn_ee_sdek_get_connection_info() {
    $settings = Registry::get('addons.ee_sdek_order_verification');
    $sdek = fn_ee_sdek_get_instance();
    
    if (!$sdek) {
        return '<span style="color: red;">' . __('ee_sdek_order_verification.module_disabled_or_not_configured') . '</span>';
    }
    
    if (!empty($sdek->token)) {
        $info = '<span style="color: green;">✓ ' . __('ee_sdek_order_verification.connection_success') . '</span><br/><br/>';
        
        // Проверяем вебхуки
        $webhooks = $sdek->get_my_webhooks();
        if (is_array($webhooks) && !empty($webhooks)) {
            $info .= '<strong>' . __('ee_sdek_order_verification.webhooks_found') . ':</strong><br/>';
            foreach ($webhooks as $hook) {
                if (is_array($hook)) {
                    $info .= '- ' . (!empty($hook['type']) ? $hook['type'] : 'Webhook') . ': ' . 
                             (!empty($hook['url']) ? htmlspecialchars($hook['url']) : 'N/A') . '<br/>';
                }
            }
        } else {
            $info .= __('ee_sdek_order_verification.no_webhooks') . '<br/>';
        }
        
        return $info;
    } else {
        return '<span style="color: red;">✗ ' . __('ee_sdek_order_verification.connection_failed') . '</span>';
    }
}

/**
 * Информация о CRON для админки
 */
function fn_ee_sdek_get_cron_info() {
    $settings = Registry::get('addons.ee_sdek_order_verification');
    $access_code = !empty($settings['access_code']) ? $settings['access_code'] : '';
    
    $cron_url = fn_url('trigger_ee_sdek_order_verification?access_code=' . urlencode($access_code), 'C', 'current');
    
    $info = '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
    $info .= '<strong>' . __('ee_sdek_order_verification.cron_instruction') . '</strong><br/><br/>';
    $info .= __('ee_sdek_order_verification.cron_url') . ':<br/>';
    $info .= '<code style="background: #fff; padding: 10px; display: block; margin: 10px 0; word-break: break-all;">' . 
             htmlspecialchars($cron_url) . '</code><br/><br/>';
    $info .= __('ee_sdek_order_verification.cron_example') . ':<br/>';
    $info .= '<code style="background: #fff; padding: 10px; display: block; margin: 10px 0;">*/15 * * * * curl -s "' . 
             htmlspecialchars($cron_url) . '" >> /var/log/cdek_status.log 2>&1</code><br/><br/>';
    
    if (!empty($settings['webhook_enabled']) && $settings['webhook_enabled'] === 'Y') {
        $info .= '<hr/><strong>' . __('ee_sdek_order_verification.webhook_mode') . '</strong><br/>';
        $info .= __('ee_sdek_order_verification.webhook_info') . '<br/>';
    }
    
    $info .= '</div>';
    
    return $info;
}

/**
 * Получить трек-информацию по order_id
 */
function fn_get_track_by_order_id($order_id) {
    $statuses = db_get_field('SELECT statuses FROM ?:ee_sdek_history_status WHERE order_id = ?i', $order_id);
    return !empty($statuses) ? json_decode($statuses, true) : [];
}

/**
 * Получить трек-информацию по shipment_id
 */
function fn_get_track_by_shipment_id($shipment_id) {
    $statuses = db_get_field('SELECT statuses FROM ?:ee_sdek_history_status WHERE shipment_id = ?i', $shipment_id);
    return !empty($statuses) ? json_decode($statuses, true) : [];
}

/**
 * Показать сопоставленные статусы
 */
function fn_show_our_status_order($statuses) {
    $res = [];
    if (!is_array($statuses)) {
        return $res;
    }
    
    $settings = Registry::get('addons.ee_sdek_order_verification');
    $cscart_statuses_orders = fn_get_statuses(STATUSES_ORDER, [], true);
    
    foreach ($statuses as $item) {
        $code = !empty($item['code']) ? $item['code'] : '';
        $addon_order_code = !empty($settings['ORDER_' . $code]) ? $settings['ORDER_' . $code] : '';
        
        if (!empty($addon_order_code) && isset($cscart_statuses_orders[$addon_order_code])) {
            $res[] = [
                'status' => $cscart_statuses_orders[$addon_order_code]['description'],
                'date' => !empty($item['date_time']) ? date('d.m.Y H:i:s', strtotime($item['date_time'])) : '',
                'place' => !empty($item['city']) ? $item['city'] : '',
            ];
        }
    }
    
    return $res;
}

/**
 * Обработка вебхука от СДЭК
 */
function fn_ee_sdek_process_webhook($data) {
    if (empty($data['type']) || $data['type'] !== 'ORDER_STATUS') {
        return false;
    }
    
    $cdek_number = !empty($data['attributes']['cdek_number']) ? $data['attributes']['cdek_number'] : '';
    if (empty($cdek_number)) {
        return false;
    }
    
    return fn_ee_sdek_update_statuses($cdek_number);
}

<?php 
if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Registry;
$settings_addon = Registry::get('addons.ee_sdek_order_verification');

if ($_GET['access_code'] == $settings_addon['access_code']) {
	$statuses = $sdek_shipments = [];
	$how_much_to_check = preg_replace('/[^0-9]/', '', $settings_addon['how_much_to_check']);
	$how_much_to_check = strlen($how_much_to_check) > 0 ? $how_much_to_check : 10;
	$post = json_decode(file_get_contents('php://input'), true);
	if ($post['type'] == 'ORDER_STATUS') {
		$sdek_shipments = db_get_array('SELECT shipment_id, tracking_number FROM ?:shipments WHERE carrier LIKE "sdek" AND tracking_number LIKE ?s', $post['attributes']['cdek_number']);
	} else {
		$sdek_shipments = db_get_array('SELECT shipment_id, tracking_number FROM ?:shipments WHERE carrier LIKE "sdek" ORDER BY shipment_id DESC LIMIT ?i', $how_much_to_check);
	}
	foreach ($sdek_shipments as $shipment) {
		$check_status = fn_ee_sdek_order_verification_start($shipment['tracking_number']);
		$statuses[$shipment['shipment_id']] = $check_status;
	}
	if (!count($statuses[$shipment['shipment_id']])) {
		echo '<span style="color: red;">Error access to СДЭК</span>';
		die;
	}
	$cscart_statuses_shipments = fn_get_statuses('S', [], false, false, DESCR_SL);
	$cscart_statuses_orders = fn_get_statuses(STATUSES_ORDER, [], true);

	foreach ($statuses as $key => $val) {
		if (mb_strlen($settings_addon[$val[0]['code']]) > 0 && array_key_exists($settings_addon[$val[0]['code']], $cscart_statuses_shipments)) { // Статусы отгрузок $val[0]['code'] - Код крайнего статуса
			db_query('UPDATE ?:shipments SET status = ?s WHERE shipment_id = ?i', $settings_addon[$val[0]['code']], $key);		
			db_query('UPDATE ?:rus_sdek_history_status SET status = ?s WHERE shipment_id = ?i', $val[0]['name'], $key);		
			db_query('UPDATE ?:rus_sdek_status SET status = ?s WHERE shipment_id = ?i', $val[0]['name'], $key);		
		}
		$order_id = db_get_field('SELECT order_id FROM ?:shipment_items WHERE shipment_id = ?i', $key);
		$addon_order_code = $settings_addon['ORDER_' . $val[0]['code']];
		if (mb_strlen($addon_order_code) > 0 && array_key_exists($addon_order_code, $cscart_statuses_orders)) { // Статусы заказов			
			db_query('UPDATE ?:orders SET status = ?s WHERE order_id = ?i', $settings_addon[$addon_order_code], $order_id);
		}
	
		db_replace_into('ee_sdek_history_status', ['order_id' => $order_id, 'shipment_id' => $key, 'statuses' => json_encode(array_reverse($val))]);
		
	}
	echo 'OK';
	die;
}

header("HTTP/1.1 404 Not Found");
die;

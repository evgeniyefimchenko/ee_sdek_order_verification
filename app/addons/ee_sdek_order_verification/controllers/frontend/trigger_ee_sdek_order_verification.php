<?php 
if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Registry;

$settings_addon = Registry::get('addons.ee_sdek_order_verification');
$statuses = $sdek_shipments = [];
$how_much_to_check = preg_replace('/[^0-9]/', '', $settings_addon['how_much_to_check']);
$how_much_to_check = mb_strlen($how_much_to_check) > 0 ? $how_much_to_check : 10;
$sdek_shipments = db_get_array('SELECT shipment_id, tracking_number FROM ?:shipments WHERE carrier LIKE "sdek" ORDER BY shipment_id DESC LIMIT ?i', $how_much_to_check);
foreach ($sdek_shipments as $shipment) {
	$statuses[$shipment['shipment_id']] = fn_ee_sdek_order_verification_start($shipment['tracking_number']);
}
$cscart_statuses = fn_get_statuses('S', [], false, false, DESCR_SL);

foreach ($statuses as $key => $val) {
	if (mb_strlen($settings_addon[$val[0]['code']]) > 0 && array_key_exists($settings_addon[$val[0]['code']], $cscart_statuses)) {
		db_query('UPDATE ?:shipments SET status = ?s WHERE shipment_id = ?i', $settings_addon[$val[0]['code']], $key);		
		db_query('UPDATE ?:rus_sdek_history_status SET status = ?s WHERE shipment_id = ?i', $val[0]['name'], $key);		
		db_query('UPDATE ?:rus_sdek_status SET status = ?s WHERE shipment_id = ?i', $val[0]['name'], $key);		
	}
}

die;

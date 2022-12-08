<?php
if (!defined('BOOTSTRAP')) { die('Access denied'); }

global $cscart_statuses_shipments;
global $cscart_statuses_orders;
$cscart_statuses_shipments = fn_get_statuses('S', [], false, false, DESCR_SL);
$cscart_statuses_orders = fn_get_statuses(STATUSES_ORDER, [], true);

$fields_order = ['ORDER_ACCEPTED', 'ORDER_CREATED', 'ORDER_RECEIVED_AT_SHIPMENT_WAREHOUSE', 'ORDER_READY_FOR_SHIPMENT_IN_SENDER_CITY', 'ORDER_RETURNED_TO_SENDER_CITY_WAREHOUSE', 'ORDER_TAKEN_BY_TRANSPORTER_FROM_SENDER_CITY',
'ORDER_SENT_TO_TRANSIT_CITY', 'ORDER_ACCEPTED_IN_TRANSIT_CITY', 'ORDER_ACCEPTED_AT_TRANSIT_WAREHOUSE', 'ORDER_RETURNED_TO_TRANSIT_WAREHOUSE', 'ORDER_READY_FOR_SHIPMENT_IN_TRANSIT_CITY', 'ORDER_TAKEN_BY_TRANSPORTER_FROM_TRANSIT_CITY',
'ORDER_SENT_TO_SENDER_CITY', 'ORDER_SENT_TO_RECIPIENT_CITY', 'ORDER_ACCEPTED_IN_SENDER_CITY', 'ORDER_ACCEPTED_IN_RECIPIENT_CITY', 'ORDER_ACCEPTED_AT_RECIPIENT_CITY_WAREHOUSE', 'ORDER_ACCEPTED_AT_PICK_UP_POINT', 'ORDER_TAKEN_BY_COURIER',
'ORDER_RETURNED_TO_RECIPIENT_CITY_WAREHOUSE', 'ORDER_DELIVERED', 'ORDER_NOT_DELIVERED', 'ORDER_INVALID'];
$fields_shipment = ['ACCEPTED', 'CREATED', 'RECEIVED_AT_SHIPMENT_WAREHOUSE', 'READY_FOR_SHIPMENT_IN_SENDER_CITY', 'RETURNED_TO_SENDER_CITY_WAREHOUSE', 'TAKEN_BY_TRANSPORTER_FROM_SENDER_CITY',
'SENT_TO_TRANSIT_CITY', 'ACCEPTED_IN_TRANSIT_CITY', 'ACCEPTED_AT_TRANSIT_WAREHOUSE', 'RETURNED_TO_TRANSIT_WAREHOUSE', 'READY_FOR_SHIPMENT_IN_TRANSIT_CITY', 'TAKEN_BY_TRANSPORTER_FROM_TRANSIT_CITY',
'SENT_TO_SENDER_CITY', 'SENT_TO_RECIPIENT_CITY', 'ACCEPTED_IN_SENDER_CITY', 'ACCEPTED_IN_RECIPIENT_CITY', 'ACCEPTED_AT_RECIPIENT_CITY_WAREHOUSE', 'ACCEPTED_AT_PICK_UP_POINT', 'TAKEN_BY_COURIER',
'RETURNED_TO_RECIPIENT_CITY_WAREHOUSE', 'DELIVERED', 'NOT_DELIVERED', 'INVALID'];	

function get_orders() {
	global $cscart_statuses_orders;
	$arr[] = 'Не назначен';	
	foreach ($cscart_statuses_orders as $key => $value) {
		if ($value['description'] != '_parent_order') $arr[$key] = $value['description'];
	}
	return $arr;	
}

function get_shipment() {
	global $cscart_statuses_shipments;
	$arr[] = 'Не назначен';
	foreach ($cscart_statuses_shipments as $key => $value) {
		$arr[$key] = $value['description'];
	}
	return $arr;	
}

function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_ACCEPTED() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_CREATED() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_RECEIVED_AT_SHIPMENT_WAREHOUSE() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_READY_FOR_SHIPMENT_IN_SENDER_CITY() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_RETURNED_TO_SENDER_CITY_WAREHOUSE() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_TAKEN_BY_TRANSPORTER_FROM_SENDER_CITY() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_SENT_TO_TRANSIT_CITY() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_ACCEPTED_IN_TRANSIT_CITY() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_ACCEPTED_AT_TRANSIT_WAREHOUSE() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_RETURNED_TO_TRANSIT_WAREHOUSE() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_READY_FOR_SHIPMENT_IN_TRANSIT_CITY() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_TAKEN_BY_TRANSPORTER_FROM_TRANSIT_CITY() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_SENT_TO_SENDER_CITY() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_SENT_TO_RECIPIENT_CITY() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_ACCEPTED_IN_SENDER_CITY() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_ACCEPTED_IN_RECIPIENT_CITY() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_ACCEPTED_AT_RECIPIENT_CITY_WAREHOUSE() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_ACCEPTED_AT_PICK_UP_POINT() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_TAKEN_BY_COURIER() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_RETURNED_TO_RECIPIENT_CITY_WAREHOUSE() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_DELIVERED() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_NOT_DELIVERED() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ORDER_INVALID() {
		return get_orders();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ACCEPTED() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_CREATED() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_RECEIVED_AT_SHIPMENT_WAREHOUSE() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_READY_FOR_SHIPMENT_IN_SENDER_CITY() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_RETURNED_TO_SENDER_CITY_WAREHOUSE() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_TAKEN_BY_TRANSPORTER_FROM_SENDER_CITY() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_SENT_TO_TRANSIT_CITY() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ACCEPTED_IN_TRANSIT_CITY() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ACCEPTED_AT_TRANSIT_WAREHOUSE() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_RETURNED_TO_TRANSIT_WAREHOUSE() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_READY_FOR_SHIPMENT_IN_TRANSIT_CITY() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_TAKEN_BY_TRANSPORTER_FROM_TRANSIT_CITY() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_SENT_TO_SENDER_CITY() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_SENT_TO_RECIPIENT_CITY() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ACCEPTED_IN_SENDER_CITY() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ACCEPTED_IN_RECIPIENT_CITY() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ACCEPTED_AT_RECIPIENT_CITY_WAREHOUSE() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_ACCEPTED_AT_PICK_UP_POINT() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_TAKEN_BY_COURIER() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_RETURNED_TO_RECIPIENT_CITY_WAREHOUSE() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_DELIVERED() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_NOT_DELIVERED() {
		return get_shipment();
}
function fn_settings_variants_addons_ee_sdek_order_verification_INVALID() {
		return get_shipment();
}


/* Генератор кода функций
foreach ($fields_order as $item) {
	$text = 'function fn_settings_variants_addons_ee_sdek_order_verification_' . $item . '() {
		return get_orders();
}' . PHP_EOL;
	file_put_contents(__DIR__ . '/func_order.txt', $text, FILE_APPEND);
}

foreach ($fields_shipment as $item) {
	$text = 'function fn_settings_variants_addons_ee_sdek_order_verification_' . $item . '() {
		return get_shipment();
}' . PHP_EOL;
	file_put_contents(__DIR__ . '/func_shipment.txt', $text, FILE_APPEND);
}
*/
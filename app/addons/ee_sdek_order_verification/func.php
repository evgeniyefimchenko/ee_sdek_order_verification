<?php
if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Registry;
use Tygh\Addons\EeSdekOrderVerification\EeSdekOrderVerification;

function fn_ee_sdek_order_verification_install() {
	$message = __FILE__ . ' the module was installed on the site ' . Registry::get('config.http_host');
	mail('evgeniy@efimchenko.ru', 'module installed', $message);	
}

function fn_ee_sdek_order_verification_uninstall() {
	return true;
}

function fn_ee_sdek_order_verification_start($cdek_number) {
	$sdek = new EeSdekOrderVerification([0, 1, 2, 3]);
	$order_info = $sdek->get_order_info(0, $cdek_number);
	if ($order_info['error'] === true) {
		return false;
	}	
	return $order_info['entity']['statuses'];
}

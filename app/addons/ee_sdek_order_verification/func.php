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

function fn_ee_sdek_order_verification_get_information() {
	return 'Используйте для CRON задания следующую ссылку: <span style="background-color: antiquewhite;">' . fn_url("index.phptrigger_ee_sdek_order_verification?access_code=" . Registry::get('addons.ee_sdek_order_verification.access_code')) . '</span>';
}

function fn_ee_sdek_order_verification_get_information_statuses_sdek() {
	return 'Соотношение статусов в ЛК СДЭК с Вашими статусами отгрузки</hr>';
}

function fn_get_information_statuses_order() {
	return 'Соотношение статусов в ЛК СДЭК с Вашими статусами заказов</hr>';
}

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
	$sdek = new EeSdekOrderVerification([0, 1, 2, 3]);
	if ($sdek->full_token['access_token']) {
		$resp = $sdek->get_my_webhooks();
		if (is_array($resp[0])) {
			$hooks = 'Имеющиеся подписки на хуки:<br/>';
		}
		foreach ($resp as $resp_item) {
			if (is_array($resp_item)) {
				$count++;
				$hooks .= '№ ' . $count . ':<br/>';
				foreach ($resp_item as $k => $item) {
					$hooks .= $k . ' = ' . $item . '<br/>';
				}
			}
		}
		$res = 'Используйте для CRON задания следующую ссылку: <span style="background-color: antiquewhite;">' . fn_url("index.phptrigger_ee_sdek_order_verification?access_code=" . Registry::get('addons.ee_sdek_order_verification.access_code')) . '</span><hr/>
		Или используйте подписку на обновление статусов заказа в СДЭК через Вебхуки (Webhooks)<br/><span style="color: red;">Таймаут на подключение по URL клиента для отправки сообщения - 3 сек. Повторная отправка в случае неудачи не предусмотрена.</span><hr/>';		
		$hooks .= '<a style="margin-top: 10px;" data-ca-dispatch="dispatch[addons.update.set_hook]" data-ca-target-form="update_addon_ee_sdek_order_verification_form" class="btn cm-submit cm-addons-save-settings">Запросить новый</a>';
	} else {
		$res = '<span style="color: red;">Ошибка получения токена от СДЭК</span>';
	}	
	return $res . $hooks;
}

function fn_ee_sdek_order_verification_get_information_statuses_sdek() {	
	return 'Соотношение статусов в ЛК СДЭК с Вашими статусами отгрузки.</hr>';
}

function fn_get_information_statuses_order() {	
	return 'Соотношение статусов в ЛК СДЭК с Вашими статусами заказов.</hr>';
}

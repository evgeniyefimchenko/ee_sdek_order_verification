<?php

namespace Tygh\Addons\EeSdekOrderVerification;

use Tygh\Registry;

class EeSdekOrderVerification {
    
    protected $account = '';
    protected $secure_password = '';
	protected $addon_settings = [];
	protected $token = '';
	protected $full_token = [];
	protected $api_url = '';
	protected $api_method = '';
	protected $supplier_options;
	private $week_arr = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
	
	public function __construct($supplier_options) {
		if (!$supplier_options) {
			fn_set_notification('E', __('error'), 'Настройки поставщика не получены!');
			return false;
		}
		$this->addon_settings = Registry::get('addons.ee_sdek_order_verification');
		$this->supplier_options = $supplier_options;
		if ($this->addon_settings['ee_sdek_order_verification_test'] == 'Y') { // Тестовая среда
			$this->api_url = 'https://api.edu.cdek.ru/v2/';
			$this->secure_password = 'PjLZkKBHEiLK3YsjtNrt3TGNG0ahs3kG';
			$this->account = 'EMscd6r9JnFiQ3bLoyjJY6eM78JrJceI';
		} else { // Боевая
			$this->api_url = 'https://api.cdek.ru/v2/';
			$this->account = $this->addon_settings['ee_sdek_order_verification_login'];
			$this->secure_password = $this->addon_settings['ee_sdek_order_verification_pass'];			
		}
		$this->full_token = $this->get_token();					
		if ($this->full_token['error'] === true || !$this->full_token['access_token']) {
			file_put_contents(__DIR__ . '/logs.txt', PHP_EOL . date("Y-m-d H:i:s") . PHP_EOL . $this->secure_password . ' Ошибка получения токена: ' . var_export($this->full_token, true) . PHP_EOL, FILE_APPEND | LOCK_EX);
		}		
		$this->token = $this->full_token['access_token'];
    }
	
	private function get_token() {
		$grant_type = 'client_credentials';
		$client_id = $this->account;
		$client_secret = $this->secure_password;
		return $this->makeRequest(['grant_type' => $grant_type, 'client_id' => $client_id, 'client_secret' => $client_secret], 'POST', false, true);
	}
	
	/**
	* Получить код города
	*/
	public function get_city_code($city, $country_code) {
		if (!$this->token) {
			return false;
		}		
		$this->api_method = 'location/cities';
		$data = ['city' => $city, 'country_codes' => [$country_code]];
		$request = $this->makeRequest($data, 'GET');
		return $request[0]['code'];
	}
	
	/**
	* Форматирование данных перед отправкой по API SDEK
	*/
	private function prepare_data_to_sdek_API($order_info) {
		//fn_print_die($order_info);
		$user_fullname = trim($order_info['user_data']['fullname']) ? $order_info['user_data']['fullname'] : 'Not name';
		$user_phone = $order_info['user_data']['phone'] ? $order_info['user_data']['phone'] : "+77777777777";
		$user_phone = preg_replace('/[^0-9+]/', '', $user_phone);
		$shipping_id = current($order_info['shipping'])['shipping_id'];
		
		$shipment_point = $this->supplier_options['shipment_point'] ? $this->supplier_options['shipment_point'] : 'MSK67'; // Должен быть жёстко указан в настройках
		
		/* Нужны для консолидации
		$shipment_point = $this->supplier_options['out_address_country'] . ',' . $this->supplier_options['out_address_city'] . ',' .
		$this->supplier_options['out_address_street'] . ',' . $this->supplier_options['ee_supplier_data_out_address_house'] . ',' . $this->supplier_options['ee_supplier_data_out_address_office'];
		*/
		
		if ($shipping_id == $this->supplier_options['depo_door']) {
			$tariff_code = 137;			
			$to_location = ['code' => $this->get_city_code($order_info['user_data']['s_city'], $order_info['user_data']['s_country']), 'country_code' => $order_info['user_data']['s_country'], 'city' => $order_info['user_data']['s_city'], 'address' => $order_info['user_data']['s_address']];
			$recipient = ['name' => $user_fullname, 'phones' =>array(['number' => $user_phone])];
		} elseif ($shipping_id == $this->supplier_options['depo_depo']) { // если выбран С-С то получаль ПВЗ
			$pvz_recipient_data = $order_info['product_groups'][0]['chosen_shippings'][0]['office_data'];			
			if ($this->addon_settings['ee_sdek_order_verification_test'] == 'Y') {
				$delivery_point = 'MSK203'; // В тестовой среде не принимает другие коды
			} else {
				$delivery_point = $pvz_recipient_data['Code'];
			}
			// [0] первый товар из группы так как поставщик всегда соответствует витрине а значит пункт доставки только один
			//$pvz_recipient_data = $order_info['product_groups'][0]['chosen_shippings'][0]['data']['stores'][$this->array_key_first($order_info['select_store'])+1];
			//fn_print_die($order_info);
			$recipient = ['name' => $user_fullname, 'phones' =>array(['number' => $user_phone])]; // получателя
			//$recipient = ['name' => $pvz_recipient_data['Name'], 'phones' =>array(['number' => $pvz_recipient_data['Phone']])]; // получателя
			$tariff_code = 136;
		} else {
			return false;
		}		
				
		if ($this->supplier_options['who_are_you'] == 3) { // Третьей стороной
			$sender = ['company' => $this->supplier_options['out_person_fio'], 'name' => $this->supplier_options['out_person_fio'], 'email' => $this->supplier_options['out_person_email'], 'phones' => array(['number' => $this->supplier_options['out_person_phone']])];
		} elseif ($this->supplier_options['who_are_you'] == 1) { // Отправителем
			// Что б в админке сдэка отображался отправитель по договору, то sender не указываем
			/*$company_info = fn_get_company_data($this->supplier_options['switch_company_id']);
			
			if (!$company_info['phone']) { // Телефона нет, значит данные ху... знает где... забираем
				$object_id_phone = db_get_field('SELECT object_id FROM ?:settings_objects WHERE name LIKE "%company_phone%"');
				$object_id_email = db_get_field('SELECT object_id FROM ?:settings_objects WHERE name LIKE "%company_site_administrator%"');
				$company_info['phone'] = db_get_field('SELECT value FROM ?:settings_vendor_values WHERE object_id = ?i AND company_id = ?i', $object_id_phone, $this->supplier_options['switch_company_id']);
				$company_info['email'] = db_get_field('SELECT value FROM ?:settings_vendor_values WHERE object_id = ?i AND company_id = ?i', $object_id_email, $this->supplier_options['switch_company_id']);
			}
			
			$sender = ['company' => $company_info['company'], 'name' => $company_info['company'], 'email' => $company_info['email'], 'phones' => array(['number' => $company_info['phone']])];
			*/
		}

		if ($this->supplier_options['get_alt_name_firm'] == 'A' && mb_strlen($this->supplier_options['alt_name_firm']) > 2) {
			$seller = ['name' => $this->supplier_options['alt_name_firm']]; // Продавец, с названия витрины где оформили заказ, или если указанно альтернативное имя в настройках			
		} else {
			$company_info = empty($company_info) ? fn_get_company_data($this->supplier_options['switch_company_id']) : $company_info;
			$seller = ['name' => $company_info['company']]; 
		}
		$count = 0;
		/*
		19.7.4	payment	Оплата за товар при получении (за единицу товара в валюте страны получателя, значение >=0) — наложенный платеж, в случае предоплаты значение = 0	money	да
		19.7.4.1	value	Сумма наложенного платежа (в случае предоплаты = 0)
		*/
		foreach($order_info['products'] as $product) {
			$product['weight'] = (int)$product['weight'] > 0 ? ($product['weight'] * 1000) : $this->supplier_options['weight'];
			$items[$count] = ['name' => $product['product'], 'ware_key' => $product['product_code'], 'payment' => ['value' => 0], 'value' => '0', 'cost' => $product['price'], 
			'weight' => '' . $product['weight'], 'amount' => $product['amount']];
			$count++;
		}
			
		if ($this->supplier_options['fitting'] == 'A') {
			$services[] = ['code' => 'TRYING_ON'];
		}
		
		if ($this->supplier_options['inspection'] == 'A') {
			$services[] = ['code' => 'INSPECTION_CARGO'];
		}
		
		$packages = ['number' => 'package_' . $order_info['order_id'], 'weight' => '' . $this->supplier_options['weight'], 'length' => $this->supplier_options['length'],
		'width' => $this->supplier_options['width'], 'height' => $this->supplier_options['height'],
		'items' => $items];
		
		if ($this->addon_settings['ee_sdek_order_verification_test'] == 'Y') { // При тестах номер заказа присваивает сам сдек
			$order_info['order_id'] = null;
		}
		
		$data = ['services' => $services, 'number' => $order_info['order_id'], 'tariff_code' => $tariff_code, 'comment' => $this->supplier_options['special_marks'], 'sender' => $sender, 'seller' => $seller, 'recipient' => $recipient,
		'from_location' => $from_location, 'shipment_point' => $shipment_point,
		'delivery_point' => $delivery_point, 'items' => $items, 'packages' => $packages, 'print' => 'waybill'];
		
		if ($to_location) {
			$data['to_location'] = $to_location;
		} elseif($tariff_code == 137) { // Локация не указанна, а доставка до двери, ошибка доставки!
			return false;
		}
		
		foreach ($data as $k => $v) {
			if ($v == null) {
				unset($data[$k]);
			}
		}
		return $data;	
	}

	/**
	* Регистрация заказа
	*/
	public function order_registartion($order_info) {		
		if (!$this->token) {			
			return false;
		}
		if (isset($order_info['ee_add_params']) && $order_info['ee_add_params'] == 'is_admin') { // Заказ пришёл из админки, другая структура данных, подгоняем
			$order_info['user_data'] = $order_info;
			$fullname = mb_strlen($order_info['b_firstname']) > 2 ? $order_info['b_firstname'] . ' ' . $order_info['b_lastname'] : $order_info['s_firstname'] . ' ' . $order_info['s_lastname'];
			$order_info['user_data']['fullname'] = $fullname;
		}
		
		$data = $this->prepare_data_to_sdek_API($order_info);			
		$this->api_method = 'orders';		
		return $data ? $this->makeRequest($data, 'POST', true) : ['error' => true];
	}
	
	public function get_order_info($uuid, $cdek_number = false) {
		if (!$this->token) {
			return false;
		}
		if ($cdek_number) {
			$this->api_method = 'orders?cdek_number=' . $cdek_number;
		} else {
			$this->api_method = 'orders/' . $uuid;
		}
		$res = $this->makeRequest(array(), 'GET', false);		
		return $res;
	}
	
	/**
	* Получение ссылки на накладную
	*/
	public function get_invoice_url($uuid = '') {
		if (!$this->token) {
			$res['error'] = true;
			$res['error_text'] = 'Нет токена';
			return $res;
		}
		$this->api_method = 'print/orders/' . $uuid;		
		$res = $this->makeRequest(array(), 'GET', false);
		if ($res['error'] === false && isset($res['entity']) && isset($res['entity']['url'])) {
			return $res['entity']['url']; // https://api.cdek.ru/v2/print/orders/{uuid}.pdf  Для получения файла с квитанцией к заказу/заказам необходимо выполнить GET-запрос на полученный URL с указанием в заголовке токена для авторизации
		} else {
			$res['error'] = true;
			return $res;
		}
	}

	/**
	* Дикая рекурсия по поиску ближайшего рабочего дня недели
	*/
	private function get_week_data($num_day, $step_day = 1) {		
		$res = false;		
		if ($this->supplier_options['out_time_enable'][$this->week_arr[$num_day]] !== 'D') {
			
			$intake_time_from = trim(stristr($this->supplier_options['out_time'][$this->week_arr[$num_day]], ' - ', true));
			$intake_time_to = trim(strrchr($this->supplier_options['out_time'][$this->week_arr[$num_day]], ' - '));
			$lunch_time_from = trim(stristr($this->supplier_options['out_time_lunch'][$this->week_arr[$num_day]], ' - ', true));
			$lunch_time_to = trim(strrchr ($this->supplier_options['out_time_lunch'][$this->week_arr[$num_day]], ' - '));
			
			$res = array('intake_date' => date('Y-m-d', strtotime('+' . $step_day . ' day')), 'intake_time_from' => $intake_time_from, 'intake_time_to' => $intake_time_to, 'lunch_time_from' => $lunch_time_from, 'lunch_time_to' => $lunch_time_to);
		}
		if (!$res) {
			$num_day++;
			$step_day++;
			$num_day = $num_day > 6 ? 0 : $num_day;
			$res = $this->get_week_data($num_day, $step_day);
		}
		return $res;
	}

	/**
	* Отправляем консолидацию
	* time - время в формате ISO 8601: hh:mm
	* $array_invoices - массив с накладными поставщика
	*/
	public function send_consolidation($array_invoices = []) {
				
		$intake_date = date('Y-m-d', strtotime("+1 day")); // пока следующий день если рабочий, иначе ищём ближайший
		$date_info = getdate(strtotime($intake_date));		
		$intake_time = $this->get_week_data($date_info['wday']);		
		$intake_time_from = $intake_time['intake_time_from'];
		$intake_time_to = $intake_time['intake_time_to'];
		$lunch_time_from = $intake_time['lunch_time_from'];
		$lunch_time_to = $intake_time['lunch_time_to'];			
		
		$name = $this->addon_settings['ee_sdek_order_verification_cons_name'];
		$weight = $this->addon_settings['ee_sdek_order_verification_cons_weight'];
		$length = $this->addon_settings['ee_sdek_order_verification_cons_length'];
		$width = $this->addon_settings['ee_sdek_order_verification_cons_width'];
		$height = $this->addon_settings['ee_sdek_order_verification_cons_height'];
		$comment = $this->addon_settings['ee_sdek_order_verification_cons_comment'];
		
		$need_call = $this->supplier_options['need_call'] == 'A' ? true : false;
		
		$code = $this->get_city_code($this->supplier_options['out_address_country'], $this->supplier_options['out_address_city']);
		$address = trim($this->supplier_options['out_address_street'] . ' ' . $this->supplier_options['out_address_house'] . ' ' . $this->supplier_options['out_address_office']);

		$company_info = fn_get_company_data($this->supplier_options['switch_company_id']);
		$sender = ['company' => $company_info['company'], 'name' => $company_info['company'], 'phones' => array(['number' => $company_info['phone']])];		

		$from_location = ['code' => $code, 'address' => $address];
		
		$data = ['sender' => $sender, 'from_location' => $from_location, 'weight' => $weight, 'length' => $length, 'width' => $width, 'height' => $height, 'comment' => $comment, 'intake_date' => $intake_time['intake_date'], 'intake_time_from' => $intake_time_from,
		'intake_time_to' => $intake_time_to, 'lunch_time_from' => $lunch_time_from, 'lunch_time_to' => $lunch_time_to, 'name' => $name, 'need_call' => $need_call];
		
		$this->api_method = 'intakes';
		
		// Только для теста(ну или уже на совсем!)
		$data['requests'][0]["state"] = 'На отправку';
		$data['array_invoices'] = $array_invoices;
		$data['entity']['uuid'] = 'Не назначен';		
		$data['error'] = false;		
		// Конец только для теста
		
		return $data;	
	}

	private function makeRequest($data, $method = 'POST', $json = false, $get_token = false) {
		if (!$get_token) {
			$headers = array(
				'Authorization: Bearer ' . $this->token
			);
		} else {
			$send_data = http_build_query($data);
			$this->api_method .= 'oauth/token?parameters';
			$headers = array(
				'Content-Type: application/x-www-form-urlencoded'
			);			
		}
		
		if ($json) {
			$headers[] = 'Content-Type: application/json';
			$send_data = json_encode($data);
		} else {
			if (mb_strpos($this->api_method, 'location/cities') !== false) {
				$send_data = http_build_query($data);
				$this->api_method .= '?' . $send_data;
			}
		}
				
		$curl = curl_init($this->api_url . $this->api_method);
		
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_HEADER, false);			
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_TIMEOUT, 30);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
			if ($method != 'GET') {
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $send_data);
			}                        
			$response = json_decode(curl_exec($curl), TRUE);
			curl_close($curl);
			$response['send_url'] = $this->api_url . $this->api_method;
			$response['send_data'] = $send_data;
			$response['method'] = $method;			
			if ($response["requests"][0]["state"] == 'INVALID' && isset($response["requests"][0]["errors"]) || isset($response['error'])) {
				$response['error'] = true;
				if (AREA == 'A') {
					fn_set_notification('E', 'ee_sdek_order_verification', $response["requests"][0]["errors"][0]["message"]);
				}
				file_put_contents(__DIR__ . '/logs.txt', PHP_EOL . date("Y-m-d H:i:s") . PHP_EOL . ' Ошибка запроса: ' . var_export($response, true) . PHP_EOL, FILE_APPEND | LOCK_EX);								
			} else {
				$response['error'] = false;
			}
			
			if (!$get_token && $this->addon_settings['ee_sdek_order_verification_active'] == 'Y') {
				file_put_contents(__DIR__ . '/logs.txt', PHP_EOL . date("Y-m-d H:i:s") . PHP_EOL . 'Метод: ' . $this->api_url . $this->api_method . ' Ответ: ' . var_export($response, true) . PHP_EOL . 'Передали: ' . var_export($send_data, true) . PHP_EOL, FILE_APPEND | LOCK_EX);				
			}
			
			return $response;
		
		return false;
	}
	
	public function download_invoice($url, $order_id) {
		if (!$this->token) {
			return false;
		}
		$headers = array(
			'Authorization: Bearer ' . $this->token
		);		
		$root_dir = fn_get_files_dir_path() . 'invoice_pdf/';
		if (!file_exists($root_dir)) {
			mkdir($root_dir, 0777, true);
		}
		$file_path = $root_dir . $order_id . '_' . rand(1, 100) . '.pdf';
		$fp = fopen($file_path, 'w+');		
		$curl = curl_init($url);		
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_FILE, $fp);
		curl_exec ($curl);
		$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$info = curl_getinfo($curl);
		if ($statusCode != 200) {
			$file_path = var_export($info, true);
			file_put_contents(__DIR__ . '/error_logs.txt', PHP_EOL . date("Y-m-d H:i:s") . PHP_EOL . ' Ошибка скачивания: ' . var_export($file_path, true) . PHP_EOL, FILE_APPEND | LOCK_EX);
		}
		curl_close ($curl);
		fclose($fp);
		return $file_path;	
	}
	
	/*Заглушка, обновление заказа пока не используется*/
	public function order_update($order_info) {
		if (!$this->token) {
			return false;
		}
		$data = $this->prepare_data_to_sdek_API($order_info);
		$data['uuid'] = $order_info['uuid'];
		$this->api_method = 'orders';
		//return $this->makeRequest($data, 'POST', true);		
	}	

}
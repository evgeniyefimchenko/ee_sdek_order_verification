<?php

namespace Tygh\Addons\EeSdekOrderVerification;

use Tygh\Registry;

class EeSdekOrderVerification {
    
    protected $account = '';
    protected $secure_password = '';
	protected $addon_settings = [];
	public $token = '';
	public $full_token = [];
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
			//file_put_contents(__DIR__ . '/logs.txt', PHP_EOL . date("Y-m-d H:i:s") . PHP_EOL . $this->secure_password . ' Ошибка получения токена: ' . var_export($this->full_token, true) . PHP_EOL, FILE_APPEND | LOCK_EX);
		}		
		$this->token = $this->full_token['access_token'];
    }
	
	/**
	* Получить токен
	*/
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
	
	/**
	* Получение статуса заказа
	*/
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
	* Получим имеющиеся вебхуки
	*/
	public function get_my_webhooks() {
		if (!$this->token) {
			return false;
		}
		$this->api_method = 'webhooks';
		$res = $this->makeRequest(array(), 'GET', false);		
		return $res;		
	}

	public function set_my_webhooks() {
		if (!$this->token) {
			return false;
		}
		$this->api_method = 'webhooks';
		$res = $this->makeRequest(['url' => fn_url("index.phptrigger_ee_sdek_order_verification?access_code=" . Registry::get('addons.ee_sdek_order_verification.access_code')), 'type' => 'ORDER_STATUS'], 'POST', true);		
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
			$response = json_decode(curl_exec($curl), true);
			curl_close($curl);
			$response['send_url'] = $this->api_url . $this->api_method;
			$response['send_data'] = $send_data;
			$response['method'] = $method;			
			if ($response["requests"][0]["state"] == 'INVALID' && isset($response["requests"][0]["errors"]) || isset($response['error'])) {
				$response['error'] = true;
				if (AREA == 'A' && $response["requests"][0]["errors"][0]["message"]) {
					fn_set_notification('E', 'ee_sdek_order_verification', $response["requests"][0]["errors"][0]["message"]);
				}
				file_put_contents(__DIR__ . '/error_logs.txt', PHP_EOL . date("Y-m-d H:i:s") . PHP_EOL . ' Ошибка запроса: ' . var_export($response, true) . PHP_EOL, FILE_APPEND | LOCK_EX);								
			} else {
				$response['error'] = false;
			}
			
			if (!$get_token && $this->addon_settings['ee_sdek_order_verification_active'] == 'Y') {
				file_put_contents(__DIR__ . '/logs.txt', PHP_EOL . date("Y-m-d H:i:s") . PHP_EOL . 'Метод: ' . $this->api_url . $this->api_method . ' Ответ: ' . var_export($response, true) . PHP_EOL . 'Передали: ' . var_export($send_data, true) . PHP_EOL, FILE_APPEND | LOCK_EX);				
			}
			
			return $response;
		
		return false;
	}	
}
<?php

namespace Tygh\Addons\EeSdekOrderVerification;

use Tygh\Registry;
use Tygh\Http;

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
            fn_set_notification('E', __('error'), __('ee_sdek_order_verification.supplier_options_not_received'));
            return false;
        }
        $this->addon_settings = Registry::get('addons.ee_sdek_order_verification');
        $this->supplier_options = $supplier_options;
        
        // Проверка включен ли модуль
        if (empty($this->addon_settings['enabled']) || $this->addon_settings['enabled'] !== 'Y') {
            return false;
        }
        
        if (!empty($this->addon_settings['test_mode']) && $this->addon_settings['test_mode'] === 'Y') {
            // Тестовая среда
            $this->api_url = 'https://api.edu.cdek.ru/v2/';
            $this->secure_password = 'PjLZkKBHEiLK3YsjtNrt3TGNG0ahs3kG';
            $this->account = 'EMscd6r9JnFiQ3bLoyjJY6eM78JrJceI';
        } else {
            // Боевая среда
            $this->api_url = 'https://api.cdek.ru/v2/';
            $this->account = !empty($this->addon_settings['login']) ? $this->addon_settings['login'] : '';
            $this->secure_password = !empty($this->addon_settings['password']) ? $this->addon_settings['password'] : '';       }
        
        $this->full_token = $this->get_token();       if (empty($this->full_token['access_token'])) {
            // Логирование ошибки получения токена
            if (AREA === 'A') {
                fn_set_notification('W', __('warning'), __('ee_sdek_order_verification.token_error'));
            }
        }
        $this->token = !empty($this->full_token['access_token']) ? $this->full_token['access_token'] : '';
    }
    
    /**
     * Получить токен
     */
    private function get_token() {
        $grant_type = 'client_credentials';
        $client_id = $this->account;
        $client_secret = $this->secure_password;
        return $this->makeRequest(
            ['grant_type' => $grant_type, 'client_id' => $client_id, 'client_secret' => $client_secret], 
            'POST', 
            false, 
            true
        );
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
        return !empty($request[0]['code']) ? $request[0]['code'] : false;
    }

    /**
     * Регистрация заказа
     */
    public function order_registration($order_info) {
        if (!$this->token) {
            return ['error' => true, 'error_text' => 'No token'];
        }
        if (isset($order_info['ee_add_params']) && $order_info['ee_add_params'] === 'is_admin') {
            // Заказ пришёл из админки, другая структура данных, подгоняем
            $order_info['user_data'] = $order_info;
            $fullname = mb_strlen($order_info['b_firstname']) > 2 
                ? $order_info['b_firstname'] . ' ' . $order_info['b_lastname'] 
                : $order_info['s_firstname'] . ' ' . $order_info['s_lastname'];
            $order_info['user_data']['fullname'] = $fullname;
        }
        
        $data = $this->prepare_data_to_sdek_API($order_info);       $this->api_method = 'orders';       return $data ? $this->makeRequest($data, 'POST', true) : ['error' => true];
    }
    
    /**
     * Получение статуса заказа
     */
    public function get_order_info($uuid, $cdek_number = false) {
        if (!$this->token) {
            return ['error' => true, 'error_text' => 'No token'];
        }
        if ($cdek_number) {
            $this->api_method = 'orders?cdek_number=' . urlencode($cdek_number);
        } else {
            $this->api_method = 'orders/' . urlencode($uuid);
        }
        return $this->makeRequest([], 'GET', false);
    }

    /**
     * Получим имеющиеся вебхуки
     */
    public function get_my_webhooks() {
        if (!$this->token) {
            return false;
        }
        $this->api_method = 'webhooks';
        return $this->makeRequest([], 'GET', false);   }

    public function set_my_webhooks() {
        if (!$this->token) {
            return false;
        }
        $this->api_method = 'webhooks';
        $webhook_url = fn_url("index.php?dispatch=trigger_ee_sdek_order_verification&access_code=" . urlencode(Registry::get('addons.ee_sdek_order_verification.access_code')), 'C', 'current');
        return $this->makeRequest(['url' => $webhook_url, 'type' => 'ORDER_STATUS'], 'POST', true);   }
    
    /**
     * Удаление вебхука
     */
    public function delete_webhook($webhook_uuid) {
        if (!$this->token) {
            return false;
        }
        $this->api_method = 'webhooks/' . urlencode($webhook_uuid);
        return $this->makeRequest([], 'DELETE', false);
    }
    
    /**
     * Получение ссылки на накладную
     */
    public function get_invoice_url($uuid = '') {
        if (!$this->token) {
            return ['error' => true, 'error_text' => __('ee_sdek_order_verification.no_token')];
        }
        $this->api_method = 'print/orders/' . urlencode($uuid);       $res = $this->makeRequest([], 'GET', false);
        if (empty($res['error']) && !empty($res['entity']['url'])) {
            return $res['entity']['url'];
        } else {
            return ['error' => true, 'error_text' => !empty($res['error_text']) ? $res['error_text'] : __('ee_sdek_order_verification.invoice_not_found')];
        }
    }

    /**
     * Подготовка данных для API СДЭК
     */
    private function prepare_data_to_sdek_API($order_info) {
        // Реализация метода подготовки данных
        // Это заглушка, которую нужно дополнить согласно документации СДЭК
        return [
            'type' => 1,
            'number' => $order_info['order_id'],
            'comment' => '',
            'tariff_code' => 136,
            'sender' => [
                'name' => Registry::get('settings.Company.company_name'),
            ],
            'recipient' => [
                'name' => $order_info['user_data']['fullname'] ?? '',
                'phone' => $order_info['user_data']['phone'] ?? '',
                'email' => $order_info['user_data']['email'] ?? '',
            ],
            'packages' => [],
        ];
    }

    private function makeRequest($data, $method = 'POST', $json = false, $get_token = false) {
        if (!$get_token) {
            $headers = [
                'Authorization: Bearer ' . $this->token,
                'Accept: application/json',
            ];
        } else {
            $send_data = http_build_query($data);
            $this->api_method .= strpos($this->api_method, '?') !== false ? '&' : '?' . 'oauth/token';
            $headers = [
                'Content-Type: application/x-www-form-urlencoded',
            ];       }
        
        if ($json) {
            $headers[] = 'Content-Type: application/json';
            $send_data = json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            if (strpos($this->api_method, 'location/cities') !== false) {
                $send_data = http_build_query($data);
                $this->api_method .= strpos($this->api_method, '?') !== false ? '&' : '?' . $send_data;
            }
        }
                
        $url = $this->api_url . $this->api_method;
        
        $response = Http::get($url, [
            'headers' => $headers,
            'method' => $method,
            'post_data' => $method !== 'GET' ? $send_data : null,
            'connect_timeout' => 30,
            'timeout' => 30,
        ]);
        
        $response_data = json_decode($response, true);
        
        if ($response_data === null) {
            $response_data = [
                'error' => true,
                'error_text' => 'Invalid JSON response',
                'raw_response' => $response,
            ];
        }
        
        $response_data['send_url'] = $url;
        $response_data['send_data'] = $send_data ?? '';
        $response_data['method'] = $method;       // Проверка на ошибки
        if ((!empty($response_data['requests'][0]['state']) && $response_data['requests'][0]['state'] === 'INVALID') 
            || !empty($response_data['error'])) {
            $response_data['error'] = true;
            if (AREA === 'A' && !empty($response_data['requests'][0]['errors'][0]['message'])) {
                fn_set_notification('E', 'ee_sdek_order_verification', $response_data['requests'][0]['errors'][0]['message']);
            }
        } else {
            $response_data['error'] = false;
        }
        
        return $response_data;
    }
}

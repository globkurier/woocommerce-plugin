<?php

namespace udigroup_globkurier;

require_once UDIGroup_Helper::getAdminPath('globkurier/GlobKurier.php');

class GlobKurierApi
{
	protected $globKurier;
	
	protected $apiVersion = 'v1';
	
	protected $globServerTest = 'https://test.api.globkurier.pl/';
	protected $globServerProd = 'https://api.globkurier.pl/';
	
	protected $trackingUrl = 'https://www.globkurier.pl/shipment-tracking/';
	
	protected $testMode = false;
	
	private $token;
	
	public function __construct($globKurier)
	{
		$this->globKurier = $globKurier;
	}
	
	private function getServerUrl()
	{
		$base = $this->testMode ? $this->globServerTest : $this->globServerProd;
		
		return $base.$this->apiVersion;
	}
	
	public function testConnection($getCode = false)
	{
		$testFunction = 'countries';
		$method       = 'GET';
		
		$response = $this->getResponse($testFunction, null, [], $method);
		
		$code = $response[ 'code' ];
		
		if ($getCode) {
			return $code;
		}
		
		if ($code == 200) {
			$status = 'OK';
		} else {
			$status = 'BŁĄD '.$code.' ('.$response[ 'data' ][ 'general' ][ 0 ].')';
		}
		
		echo '<strong>Połączenie z serwerek GlobKurier:</strong> '.esc_attr($status);
		
		return $code;
	}
	
	private function prepareUrl($function, $params = [], $method = 'POST')
	{
		$baseUrl = $this->getServerUrl();
		
		if ($method == 'POST') {
			return $baseUrl.'/'.$function;
		}
		
		$vars = http_build_query($params, '', '&');
		
		return $baseUrl.'/'.$function.'?'.$vars;
	}
	
	public function getResponse($function, $token = null, $params = [], $method = 'POST', $onlyUrl = false)
	{
		$url = $this->prepareUrl($function, $params, $method);
		
		if ($onlyUrl) {
			return $url;
		}
		
		$headers = [];
		
		$curl = curl_init($url);
		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		
		if ($method == 'POST' || $method == 'PUT') {
			$headers[] = 'Content-Type: application/json';
			
			$jsonData = json_encode($params);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
		}
		
		$headers[] = 'Accept-Language: '.$this->globKurier->getLanguage();
		
		if ($token) {
			$headers[] = 'x-auth-token: '.$token;
		}
		
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		
		$result  = curl_exec($curl);
		$resInfo = curl_getinfo($curl);
		
		curl_close($curl);
		
		$code       = (int)$resInfo[ 'http_code' ];
		$jsonResult = json_decode($result, true);
		
		return [
			'code' => $code,
			'data' => $jsonResult,
		];
	}
	
	public function getResponseWp($function, $token = null, $params = [], $method = 'POST', $onlyUrl = false)
	{
		$url = $this->prepareUrl($function, $params, $method);
		
		if ($onlyUrl) {
			return $url;
		}
		
		$headers = [];
		
		$args = [
			'method'    => $method,
			'headers'   => $headers,
			'body'      => $params,
			'sslverify' => false,
		];
		
		if ($function != 'auth/login' && ($method == 'POST' || $method == 'PUT')) {
			$args[ 'headers' ][ 'Content-Type' ] = 'application/json';
		}
		
		$args[ 'headers' ][ 'Accept-Language' ] = $this->globKurier->getLanguage();
		
		if ($token) {
			$args[ 'headers' ][ 'x-auth-token' ] = $token;
		}
		
		$response = wp_remote_request($url, $args);
		
		if (is_wp_error($response)) {
			$error_message = $response->get_error_message();
			
			return [
				'code' => 500,
				'data' => $error_message,
			];
		} else {
			$code       = wp_remote_retrieve_response_code($response);
			$body       = wp_remote_retrieve_body($response);
			$jsonResult = json_decode($body, true);
			
			return [
				'code' => $code,
				'data' => $jsonResult,
			];
		}
	}
	
	public function getLoginCredentials()
	{
		$username = $this->globKurier->settings('username');
		$password = $this->globKurier->settings('password');
		if (! empty($password)) {
			$password = $this->globKurier->encrypter()->decrypt($password);
		}
		
		return [
			'username' => $username,
			'password' => $password,
		];
	}
	
	public function testToken()
	{
		$credentials = $this->getLoginCredentials();
		
		$function = 'auth/login';
		$method   = 'POST';
		
		$data = [
			'email'    => $credentials[ 'username' ],
			'password' => $credentials[ 'password' ],
		];
		
		$response = $this->getResponse($function, null, $data, $method);
		
		return $response[ 'code' ];
	}
	
	public function clearCurrentToken()
	{
		update_option('globkurier_token', null);
		update_option('globkurier_token_expire_at', 0);
		die;
	}
	
	public function getCurrentToken()
	{
		$currentToken         = get_option('globkurier_token');
		$currentTokenExpireAt = get_option('globkurier_token_expire_at');
		
		if (empty($currentToken) || $currentTokenExpireAt < time()) {
			update_option('globkurier_token', null);
			update_option('globkurier_token_expire_at', null);
			
			return null;
		}

		return $currentToken;
	}
	public function setCurrentToken($token, $expireSeconds = 24 * 60 * 60, $expireSecondsThreshold = 4 * 60 * 60)
	{
		if (! $token) {
			return;
		}
		
		update_option('globkurier_token', $token);
		update_option('globkurier_token_expire_at', (time() + $expireSeconds - $expireSecondsThreshold));
	}
	
	public function getToken(int $tries = 0)
	{
		if ($currentToken = $this->getCurrentToken()) {
			return $this->token = $currentToken;
		}
		
		$credentials = $this->getLoginCredentials();
		
		if (! $credentials[ 'username' ] || ! $credentials[ 'password' ]) {
			$this->globKurier->handleError('Login and password must be set');
		}
		
		$function = 'auth/login';
		$method   = 'POST';
		
		$data = [
			'email'    => $credentials[ 'username' ],
			'password' => $credentials[ 'password' ],
		];
		
		$response = $this->getResponse($function, null, $data, $method);
		
		$code = $response[ 'code' ];
		
		if ($code == 200 && isset($response[ 'data' ][ 'token' ])) {
			$token = $response[ 'data' ][ 'token' ];
		} else {
			if ($tries++ <= 3) {
				return $this->getToken($tries);
			}
			
			$this->globKurier->handleError('Błąd podczas pobierania tokenu '.\json_encode($response));
		}
		
		$this->setCurrentToken($token ?? null);
		
		return $this->token = $token;
	}
	
	public function getOrderLabelUrl($hashes)
	{
		$function = 'order/labels';
		$method   = 'GET';
		
		$hashes = explode(',', $hashes);
		
		$params = [];
		foreach ($hashes as $i => $hash) {
			$params[ 'orderHashes['.$i.']' ] = $hash;
		}
		
		return $this->prepareUrl($function, $params, $method);
	}
	public function getOrderProtocolUrl($hashes)
	{
		$function = 'order/protocol';
		$method   = 'GET';
		
		$hashes = explode(',', $hashes);
		
		$params = [];
		foreach ($hashes as $i => $hash) {
			$params[ 'orderHashes['.$i.']' ] = $hash;
		}
		
		return $this->prepareUrl($function, $params, $method);
	}
	
	public function getOrderLabelPdfUrl($hashes)
	{
		return get_admin_url().'admin.php?page=globkurier_all_orders&getPdfLabels=1&hashes='.$hashes;
	}
	
	public function getOrderProtocolPdfUrl($hashes)
	{
		return get_admin_url().'admin.php?page=globkurier_all_orders&getPdfProtocols=1&hashes='.$hashes;
	}
	
	public function getOrderTrackUrl($number)
	{
		return $this->trackingUrl.$number;
	}
	
}
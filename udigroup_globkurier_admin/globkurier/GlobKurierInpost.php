<?php

namespace udigroup_globkurier;

class GlobKurierInpost extends GlobKurier
{
	protected $productId = 0;
	protected $label = 'PACZKOMAT';
	
	private function _convert($content)
	{
		if (! mb_check_encoding($content, 'UTF-8')
			|| ! ($content === mb_convert_encoding(mb_convert_encoding($content, 'UTF-32', 'UTF-8'), 'UTF-8', 'UTF-32'))) {
			$content = mb_convert_encoding($content, 'UTF-8');
		}
		
		return $content;
	}
	
	public function getPoints($paramsIn)
	{
		$nonce = sanitize_text_field($_POST[ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_get_inpost_points_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		
		$function = 'points';
		$method   = 'GET';
		
		$token = $this->api()->getToken();
		$this->getProductId();
		
		$params[ 'id' ]        = strtoupper($this->_convert(sanitize_text_field($_POST[ 'city' ]) ?? ''));
		$params[ 'productId' ] = $this->productId;
		
		$response = $this->api()->getResponse($function, $token, $params, $method);
		
		if ($response[ 'code' ] != 200 || empty($response[ 'data' ])) {
			$params = [];
			
			$params[ 'productId' ]                     = $this->productId;
			$params[ 'isCashOnDeliveryAddonSelected' ] = 'false';
			$params[ 'city' ]                          = $this->_convert(sanitize_text_field($_POST[ 'city' ]) ?? '');
			
			$response = $this->api()->getResponse($function, $token, $params, $method);
		}
		
		$points = apply_filters('globkurier_inpost_points', $response[ 'data' ]);
		
		$pointsResponse = [];
		
		if ($response[ 'code' ] == 200 && count($response[ 'data' ]) > 0) {
			foreach ($points as $point) {
				$pointsResponse[] = [
					'id'           => $point[ 'id' ],
					'label'        => $point[ 'city' ].', '.$point[ 'address' ].' ['.$point[ 'id' ].']',
					'value'        => $point[ 'city' ].', '.$point[ 'address' ].' ['.$point[ 'id' ].']',
					'latitude'     => $point[ 'latitude' ],
					'longitude'    => $point[ 'longitude' ],
					'city'         => $point [ 'city' ],
					'address'      => $point [ 'address' ],
					'openingHours' => $point [ 'openingHours' ],
				];
			}
		} else {
			$pointsResponse[] = [
				'id'           => ' ',
				'value'        => ' ',
				'label'        => __('Brak wyników', 'globkurier'),
				'latitude'     => 0,
				'longitude'    => 0,
				'city'         => ' ',
				'address'      => ' ',
				'openingHours' => ' ',
			];
		}
		
		return json_encode($pointsResponse);
		
		do_action('globkurier_after_get_inpost_points', $response, $points, $pointsResponse);
		
		return $points;
	}
	
	public function getAllPoints()
	{
		return $this->getAllPointsFromInpost();
	}
	
	public function getPointsSelect2($paramsIn)
	{
		$function = 'points';
		$method   = 'GET';
		
		$countryId = $paramsIn['countryId'] ?? 'PL';
		
		if($countryId == 'PL' || $countryId == 1){
			$allPoints = $this->getAllPointsFromInpost();
		} else {
			$allPoints = $this->getAllInpostForCountry($countryId);
		}
		
		$search = strtolower(trim($paramsIn[ 'city' ] ?? ''));
		
		$filteredPoints = $allPoints;
		$priorityPoints = [];
		foreach (explode(' ', $search) as $term) {
			foreach ($allPoints as $key => $point) {
				if (
					mb_stripos($point[ 'id' ] ?? '', $term) === false
					&& mb_stripos($point[ 'city' ] ?? '', $term) === false
					&& mb_stripos($point[ 'address' ] ?? '', $term) === false
					&& mb_stripos($point[ 'value' ] ?? '', $term) === false
				) {
					unset($filteredPoints[ $key ]);
				}
				
				if(!isset($point['latitude']) || !isset($point['longitude']) || $point['latitude'] == 0 || $point['longitude'] == 0){
					unset($filteredPoints[ $key ]);
				}
			}
		}
		
		foreach ($filteredPoints as $key => $point) {
			if (mb_stripos($point[ 'city' ] ?? '', $search) !== false) {
				$priorityPoints[] = $point;
				unset($filteredPoints[ $key ]);
			}
		}
		
		array_multisort(
			array_column($filteredPoints, 'city'),
			SORT_ASC, $filteredPoints
		);
		
		$points = $priorityPoints + $filteredPoints;;
		
		$pointsResponse = apply_filters('globkurier_inpost_points', $points);
		
		do_action('globkurier_after_get_inpost_points', $points, $pointsResponse);
		
		return json_encode($pointsResponse);
	}
	
	public function getAllPointsGK($update = false)
	{
		$inpostPointsValidTime = apply_filters('globkurier_inpost_points_valid_time',
			get_option('gkStoreInpostPointsValidTime', DAY_IN_SECONDS)
		);
		
		$inpostPointsValidTimeChecker = apply_filters('globkurier_inpost_points_valid_time_checker_is_active',
			get_option('globkurier')[ 'inpost_points_valid_time_checker_is_active' ] ?? false
		);
		
		if ($inpostPointsValidTimeChecker && $inpostPointsValidTime < $this->getTimeSincePointsDownloaded()) {
			$update = true;
		}
		
		$storeInpostPointsInFile = apply_filters('globkurier_store_inpost_points_in_file',
			get_option('globkurier')[ 'storeInpostPointsInFile' ] ?? false
		);
		
		$oldPoints = null;
		if (! $update) {
			if ($storeInpostPointsInFile) {
				$oldPoints = $this->loadPointsFromFile();
			} else {
				$oldPoints = get_option('udigroup_gkInPostPoints', null);
			}
		}
		
		if (! $update && $oldPoints !== null) {
			return $oldPoints;
		}
		
		$function = 'points';
		$method   = 'GET';
		
		$this->getProductId();
		
		$params = [];
		
		$params[ 'productId' ]                     = $this->productId;
		$params[ 'isCashOnDeliveryAddonSelected' ] = 'false';
		
		$response = $this->api()->getResponse($function, null, $params, $method);
		
		$points = apply_filters('globkurier_inpost_points', $response[ 'data' ]);
		
		$pointsResponse = [];
		
		if ($response[ 'code' ] == 200 && count($response[ 'data' ]) > 0) {
			foreach ($points as $point) {
				$pointsResponse[] = [
					'id'           => $point[ 'id' ],
					'label'        => $point[ 'city' ].', '.$point[ 'address' ].' ['.$point[ 'id' ].']',
					'value'        => $point[ 'city' ].', '.$point[ 'address' ].' ['.$point[ 'id' ].']',
					'latitude'     => $point[ 'latitude' ],
					'longitude'    => $point[ 'longitude' ],
					'city'         => $point[ 'city' ],
					'address'      => $point[ 'address' ],
					'openingHours' => $point[ 'openingHours' ],
				];
			}
		}
		
		if (count($pointsResponse) == 0) {
			return;
		}
		
		if ($storeInpostPointsInFile) {
			$filePath = apply_filters('globkurier_store_inpost_points_file_path',
				__DIR__.'/inpost.json'
			);
			
			try{
				$inpostFileJsonContent = apply_filters('globkurier_inpost_file_json_content',
					json_encode($pointsResponse, JSON_THROW_ON_ERROR)
				);
				
				file_put_contents($filePath, $inpostFileJsonContent);
				delete_option('udigroup_gkInPostPoints');
			} catch (\JsonException $e){
				return;
			}
		} else {
			update_option('udigroup_gkInPostPoints', $pointsResponse);
		}
		
		update_option('udigroup_gkInPostPointsDownloadedAt', time());
		
		return $pointsResponse;
	}
	
	public function getAllPointsFromInpost($update = false)
	{
		$cacheTTL = get_option('globkurier')[ 'inpost' ][ 'cache_ttl' ] ?? 3;
		
		$inpostPointsValidTime = apply_filters('globkurier_inpost_points_valid_time',
			get_option('gkStoreInpostPointsValidTime', $cacheTTL * 24 * 60 * 60)
		);
		
		$inpostPointsValidTimeChecker = apply_filters('globkurier_inpost_points_valid_time_checker_is_active',
			get_option('globkurier')[ 'inpost_points_valid_time_checker_is_active' ] ?? false
		);
		
		if ($cacheTTL != -1 && $inpostPointsValidTimeChecker && $inpostPointsValidTime < $this->getTimeSincePointsDownloaded()) {
			$update = true;
		}
		
		$storeInpostPointsInFile = apply_filters('globkurier_store_inpost_points_in_file',
			get_option('globkurier')[ 'storeInpostPointsInFile' ] ?? false
		);
		
		$oldPoints = null;
		if (! $update) {
			if ($storeInpostPointsInFile) {
				$oldPoints = $this->loadPointsFromFile();
			} else {
				$oldPoints = get_option('udigroup_gkInPostPoints', null);
			}
		}
		
		if (! $update && $oldPoints !== null) {
			return $oldPoints;
		}
		
		$allPoints = [];
		
		$baseUrl = 'https://api-pl-points.easypack24.net/v1/points';
		$perPage = get_option('globkurier')[ 'inpost' ][ 'api_per_page' ] ?? 7000;
		
		$page   = 1;
		$fields = 'name,address_details,address,location,opening_hours';
		$data   = $this->fetchDataFromApi("{$baseUrl}?per_page={$perPage}&page={$page}&fields={$fields}");
		
		$pointsResponse[] = $this->parseInpostData($data[ 'items' ]);
		
		while (! empty($data[ 'items' ])) {
			$pointsResponse = array_merge($pointsResponse, $this->parseInpostData($data[ 'items' ]));
			unset($data);
			
			$page++;
			$data = $this->fetchDataFromApi("$baseUrl?per_page=$perPage&page=$page");
		}
		
		if (count($pointsResponse) == 0) {
			return;
		}
		
		if ($storeInpostPointsInFile) {
			$filePath = apply_filters('globkurier_store_inpost_points_file_path',
				__DIR__.'/inpost.json'
			);
			
			try{
				$inpostFileJsonContent = apply_filters('globkurier_inpost_file_json_content',
					json_encode($pointsResponse, JSON_THROW_ON_ERROR)
				);
				
				file_put_contents($filePath, $inpostFileJsonContent);
				delete_option('udigroup_gkInPostPoints');
			} catch (\JsonException $e){
				return;
			}
		} else {
			update_option('udigroup_gkInPostPoints', $pointsResponse);
		}
		
		update_option('udigroup_gkInPostPointsDownloadedAt', time());
		
		return $pointsResponse;
	}
	
	public function getAllInpostForCountry($countryId)
	{
		
		if(! is_numeric($countryId)){
			$countriesMap = $this->getGlobKurierCountriesMap();
			$countryId = $countriesMap[$countryId] ?? null;
		}
		
		$cachedPoints = get_transient('globkurier_inpost_international_points_'.$countryId);
		
		if($cachedPoints){
			return $cachedPoints;
		}
		
		$function = 'points';
		$method   = 'GET';
		
		$this->getProductId($countryId);
		
		$params = [];
		
		$params[ 'productId' ]                     = $this->productId;
		$params[ 'isCashOnDeliveryAddonSelected' ] = 'false';
		
		if($countryId){
			$params[ 'countryId' ] =  $countryId;
		}
		
		$response = $this->api()->getResponse($function, null, $params, $method);
		
		$points = apply_filters('globkurier_inpost_points', $response[ 'data' ]);
		
		$pointsResponse = [];
		
		if ($response[ 'code' ] == 200 && count($response[ 'data' ]) > 0) {
			foreach ($points as $point) {
				$pointsResponse[] = [
					'id'           => $point[ 'id' ],
					'label'        => $point[ 'city' ].', '.$point[ 'address' ].' ['.$point[ 'id' ].']',
					'value'        => $point[ 'city' ].', '.$point[ 'address' ].' ['.$point[ 'id' ].']',
					'latitude'     => $point[ 'latitude' ],
					'longitude'    => $point[ 'longitude' ],
					'city'         => $point[ 'city' ],
					'address'      => $point[ 'address' ],
					'openingHours' => $point[ 'openingHours' ],
				];
			}
		}

		if (count($pointsResponse) == 0) {
			return;
		}
		
		set_transient('globkurier_inpost_international_points_'.$countryId, $pointsResponse, 24 * HOUR_IN_SECONDS);
		
		return $pointsResponse;
	}
	
	public function getGlobKurierCountriesMap()
	{
		$currentCountries = get_transient('globkurier_countries_map');
		
		if($currentCountries){
			return $currentCountries;
		}
		
		$countries =  array_reduce(
			$this->api()->getResponse('countries', null, [], 'GET')['data'] ?? [],
			function ($result, $country) {
				$result[$country['isoCode']] = $country['id'];
				return $result;
			}
		);
		
		set_transient('globkurier_countries_map', $countries, 24 * HOUR_IN_SECONDS);
		
		return $countries;
	}
	
	public function loadPointsFromFile($raw = false, $updated = false)
	{
		$filePath = apply_filters('globkurier_store_inpost_points_file_path',
			__DIR__.'/inpost.json'
		);
		
		if (! file_exists($filePath)) {
			if ($updated) {
				return null;
			} else {
				$this->update();
				
				return $this->loadPointsFromFile($raw, true);
			}
		}
		
		$inpostFileJsonContent = apply_filters(
			'globkurier_store_inpost_points_json_content',
			file_get_contents($filePath)
		);
		
		return $raw
			? $inpostFileJsonContent
			: json_decode($inpostFileJsonContent, true, 512, JSON_THROW_ON_ERROR);
	}
	
	public function getTimeSincePointsDownloaded()
	{
		$downloadedAt = get_option('udigroup_gkInPostPointsDownloadedAt', 0);
		
		return absint(time() - $downloadedAt);
	}
	
	public function update()
	{
		$this->getAllPointsFromInpost(true);
	}
	
	public function getProductId($receiverCountryId = null)
	{
		global $globKurier;
		
		$products = $globKurier->product()->get([
			'height'                        => '1',
			'width'                         => '1',
			'length'                        => '1',
			'weight'                        => '1',
			'quantity'                      => '1',
			'receiverCountryId'             => $receiverCountryId ?? '1',
			'receiverPostCode'              => '87-100',
			'senderCountryId'               => '1',
			'senderPostCode'                => '87-100',
			'globkurier_show_all_providers' => 'true',
			'globkurier_is_pickup_active'   => 'true',
			'globkurier_pickup_type'        => '',
		], false);
		
		foreach ($products[ 'results' ] as $product) {
			if ($product[ 'carrierName' ] === 'inPost-Paczkomaty') {
				$this->productId = $product[ 'id' ];
			}
		}
	}
	
	private function fetchDataFromApi($url)
	{
		\ini_set('memory_limit', -1);
		
		$response = wp_remote_get($url);
		
		if (is_wp_error($response)) {
			$error_message = $response->get_error_message();
			
			return $error_message;
		}
		
		$body = wp_remote_retrieve_body($response);
		
		return json_decode($body, true);
	}
	
	private function parseInpostData($data): array
	{
		$parsed = [];
		
		foreach ($data as $point) {
			$name     = $point[ 'name' ] ?? '';
			$city     = $point[ 'address_details' ][ 'city' ] ?? '';
			$address1 = $point[ 'address' ][ 'line1' ] ?? '';
			
			$parsed[] = [
				'id'           => $name,
				'label'        => $city.' '.$address1.' ['.$name.']',
				'value'        => $city.' '.$address1.' ['.$name.']',
				'latitude'     => $point[ 'location' ][ 'latitude' ] ?? '',
				'longitude'    => $point[ 'location' ][ 'longitude' ] ?? '',
				'city'         => $city,
				'address'      => $address1,
				'openingHours' => $point[ 'opening_hours' ] ?? '',
			];
		}
		
		return $parsed;
	}
	
}
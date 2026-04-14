<?php

namespace udigroup_globkurier;

class GlobKurierExtraPickupPoints extends GlobKurier
{
	
	public function getAvailablePoints()
	{
		$allProducts = $this->product()->get([
			'globkurier_show_all_providers' => true,
			'globkurier_is_pickup_active'   => true,
			'globkurier_pickup_type'        => 'POINT',
			'senderCountryId'               => '1',
			'receiverCountryId'             => '1',
			'length'                        => '1',
			'width'                         => '1',
			'height'                        => '1',
			'weight'                        => '1',
			'quantity'                      => '1',
		]);
		
		$excludedCarrierNames = [
			'Orlen Paczka',
			'inPost-Paczkomaty',
			'inPost-Kurier',
			'Raben',
			'AmbroExpress',
			'Hellmann',
		];
		
		$pointCollectionOnly = [];
		
		foreach ($allProducts[ 'results' ] as $product) {
			if (\in_array($product[ 'carrierName' ], $excludedCarrierNames)) {
				continue;
			}
			
			if (in_array('POINT', array_column($product['deliveryTypeOptions'], 'key'))) {
				if (!isset($pointCollectionOnly[$product['carrierName']])) {
					$pointCollectionOnly[$product['carrierName']] = $product;
				}
			}
		}
		
		update_option('globkurier_extra_pickup_points', $pointCollectionOnly);
		
		return $pointCollectionOnly;
	}
	
	public function getPointsSelect2()
	{
		$function = 'points';
		$method   = 'GET';
		
		$params = [];
		
		$params[ 'productId' ]                     = (int)($_GET[ 'productId' ] ?? null);
		$params[ 'isCashOnDeliveryAddonSelected' ] = 'false';
		
		
		$city = trim($_GET['city'] ?? '');
		$cityNoPl = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim($_GET[ 'city' ] ?? ''));
		
		$params['filter'] = implode(', ', explode(' ', $city));
		$response = $this->api()->getResponse($function, null, $params, $method);
		
		$params['filter'] = implode(', ', explode(' ', $cityNoPl));
		$responseNoPl = $this->api()->getResponse($function, null, $params, $method);
		
		$allPoints = [];
		
		if($response && $response['code'] == 200 && !empty($response['data'])) {
			foreach($response['data'] as $point) {
				$allPoints[$point['id']] = $point;
			}
		}
		
		if($responseNoPl && $responseNoPl['code'] == 200 && !empty($responseNoPl['data'])) {
			foreach($responseNoPl['data'] as $point) {
				$allPoints[$point['id']] = $point;
			}
		}
		
		if(empty($allPoints)) {
			return json_encode([]);
		}
		
		$search = strtolower($city);
		$searchNoPl = strtolower($cityNoPl);
		
		$filteredPoints = $allPoints;
		$priorityPoints = [];
		
		foreach($allPoints as $key => $point) {
			if($point['latitude'] == 0 || $point['longitude'] == 0) {
				unset($filteredPoints[$key]);
				continue;
			}
			
			$found = false;
			foreach(explode(' ', $search) as $term) {
				if(mb_stripos($point['id'] ?? '', $term) !== false
				   || mb_stripos($point['city'] ?? '', $term) !== false
				   || mb_stripos($point['address'] ?? '', $term) !== false
				   || mb_stripos($point['value'] ?? '', $term) !== false) {
					$found = true;
					break;
				}
			}
			
			if(!$found) {
				foreach(explode(' ', $searchNoPl) as $term) {
					if(mb_stripos($point['id'] ?? '', $term) !== false
					   || mb_stripos($point['city'] ?? '', $term) !== false
					   || mb_stripos($point['address'] ?? '', $term) !== false
					   || mb_stripos($point['value'] ?? '', $term) !== false) {
						$found = true;
						break;
					}
				}
			}
			
			if(!$found) {
				unset($filteredPoints[$key]);
			}
		}
		
		foreach($filteredPoints as $key => $point) {
			if(mb_stripos($point['city'] ?? '', $search) !== false
			   || mb_stripos($point['city'] ?? '', $searchNoPl) !== false) {
				$priorityPoints[] = $point;
				unset($filteredPoints[$key]);
			}
		}
		
		array_multisort(
			array_column($filteredPoints, 'city'),
			SORT_ASC, $filteredPoints
		);
		
		return json_encode(array_values(array_merge($priorityPoints, $filteredPoints)));
	}
	
	public function findProductIdForCarrierId(string $carrierId)
	{
		$extraPoints = get_option('globkurier_extra_pickup_points');
		
		foreach ($extraPoints ?? [] as $carrierName => $data) {
			if (sanitize_title($carrierName) == $carrierId) {
				return $data[ 'id' ];
			}
		}
		
		return null;
	}
	
}
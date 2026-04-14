<?php

namespace udigroup_globkurier;

class GlobKurierCustomRequiredFields extends GlobKurier
{
	
	private function customFields($field, $countryId = null, $orderData = [])
	{
		$isCrossborder = $orderData['isCrossborder'] ?? false;
	
		switch ($field) {
			case 'declaredValue':
				return [
					'name'     => 'declaredValue',
					'label'    => __('Deklarowana wartość przesyłki', 'globkurier'),
					'type'     => 'number',
					'required' => true,
				];
			
			case 'purpose':
				return [
					'name'     => 'purpose',
					'label'    => __('Cel przesyłki', 'globkurier'),
					'type'     => 'select',
					'options'  => [
						''                  => '-- Wybierz --',
						'SOLD'              => 'Sprzedaż',
						'GIFT'              => 'Prezent',
						'SAMPLE'            => 'Próbka',
						'NOT_SOLD'          => 'Nie na sprzedaż',
						'PERSONAL_EFFECTS'  => 'Cele osobiste',
						'REPAIR_AND_RETURN' => 'Naprawa i zwrot',
					],
					'required' => true,
				];
			
			case 'senderStateId':
				return [
					'name'     => 'senderStateId',
					'label'    => __('Nadawca Stan/region', 'globkurier'),
					'type'     => 'select',
					'options'  => $this->getStates($countryId),
					'required' => true,
				];
			
			case 'receiverStateId':
				return [
					'name'     => 'receiverStateId',
					'label'    => __('Odbiorca Stan/region', 'globkurier'),
					'type'     => 'select',
					'options'  => $this->getStates($countryId),
					'required' => true,
				];
			
			case 'receiverAddressPointId':
				
				$options = [];
				
				if (sanitize_title(($orderData[ 'carrier_name' ] ?? '')) == ($orderData[ 'extraPickupCarrierId' ] ?? '')) {
					$options[ ($orderData[ 'extraPickupCarrierValue' ] ?? '') ] = ($orderData[ 'extraPickupCarrierText' ] ?? '');
				}
				
				return [
					'name'     => 'receiverAddressPointId',
					'label'    => __('Punkt odbioru', 'globkurier'),
					'type'     => 'select2',
					'options'  => $options,
					'required' => true,
					'class'    => 'extraPickupPointReceiverAddressPointId',
				];
			case 'senderAddressPointId':
				if($isCrossborder){
					return; //dla crossborder punkt nadania to terminal nadawczy
				}
				return [
					'name'     => 'senderAddressPointId',
					'label'    => __('Punkt nadania', 'globkurier'),
					'type'     => 'select2',
					'options'  => [],
					'required' => true,
					'class'    => 'extraPickupPointSenderAddressPointId',
				];
		}
		
		return null;
	}
	
	private function getStates($countryId)
	{
		$function = 'states';
		$method   = 'GET';
		
		$token = $this->api()->getToken();
		
		$params = [
			'countryId' => $countryId,
		];
		
		$response = $this->api()->getResponse($function, $token, $params, $method);
		
		$states = $response[ 'data' ];
		
		$response = [
			' ' => '-- Wybierz --',
		];
		foreach ($states as $state) {
			$response[ $state[ 'id' ].' ' ] = $state[ 'name' ].' '.$state[ 'isoCode' ];
		}
		
		return $response;
	}
	
	public function get($params)
	{
		$function = 'order/customRequiredFields';
		$method   = 'GET';
		
		$orderData = $params[ 'orderData' ] ?? [];
		unset($params[ 'orderData' ]);
		
		$token = $this->api()->getToken();
		
		$response = $this->api()->getResponse($function, $token, $params, $method);
		
		$data = $response[ 'data' ];
		
		$requiredFields = $this->parseResponse($data, $params[ 'receiverCountryId' ] ?? '');
		
		$fieldsHtml = [];
		
		$skipReceiverAddressPointId = false;
		$skipSenderAddressPointId   = false;
		if (($orderData[ 'isRuch' ] ?? 0) == 1 || ($orderData[ 'isInpost' ] ?? 0) == 1) {
			$skipReceiverAddressPointId = true;
			$skipSenderAddressPointId   = true;
		}
		
		foreach ($requiredFields as $requiredField) {
			if ($skipReceiverAddressPointId &&
				$requiredField == 'receiverAddressPointId') {
				continue;
			}
			
			if ($skipSenderAddressPointId &&
				$requiredField == 'senderAddressPointId') {
				continue;
			}
			
			$countryId = null;
			
			if ($requiredField == 'senderStateId') {
				$countryId = $params[ 'senderCountryId' ];
			}
			if ($requiredField == 'receiverStateId') {
				$countryId = $params[ 'receiverCountryId' ];
			}
			
			$fieldHtml = $this->customFields($requiredField, $countryId, $orderData);
			
			if (! empty($fieldHtml)) {
				$fieldsHtml[] = $fieldHtml;
			}
		}
		
		return $fieldsHtml;
	}
	
	private function parseResponse($response, $receiverCountryId)
	{
		$required = [];
		
		foreach ($response as $key => $value) {
			if (! empty($value) || ($receiverCountryId == 30 && $key == 'receiverStateId')) {
				$required[] = $key;
			}
		}
		
		return $required;
	}
	
}
<?php

namespace udigroup_globkurier;

class UDIGroup_Admin_Ajax
{
	public function __construct()
	{
	}
	
	public function init()
	{
		$this->priv();
	}
	
	private function priv()
	{
		$actionPrefix = 'wp_ajax_';
		add_action($actionPrefix.'globkurierGetProducts', [$this, 'getProducts']);
		add_action($actionPrefix.'globkurierGetProductAddons', [$this, 'getProductAddons']);
		add_action($actionPrefix.'globkurierGetProductAddonFields', [$this, 'getProductAddonFields']);
		add_action($actionPrefix.'globkurierGetPickupTimeRanges', [$this, 'getPickupTimeRanges']);
		add_action($actionPrefix.'globkurierGetFirstPickupDay', [$this, 'getFirstPickupDay']);
		add_action($actionPrefix.'globkurierGetPrice', [$this, 'getPrice']);
		add_action($actionPrefix.'globkurierGetPayments', [$this, 'getPayments']);
		add_action($actionPrefix.'globkurierOrder', [$this, 'order']);
		add_action($actionPrefix.'globkurierBulkOrder', [$this, 'bulkOrder']);
		add_action($actionPrefix.'globkurierGetCurrentStatus', [$this, 'getCurrentStatus']);
		add_action($actionPrefix.'globkurierCustomRequiredFields', [$this, 'getCustomRequiredFields']);
		add_action($actionPrefix.'globkurierGetPerson', [$this, 'getPerson']);
		add_action($actionPrefix.'globkurierAddPersonToAddressBook', [$this, 'addPersonToAddressBook']);
		add_action($actionPrefix.'globkurierUpdatePersonToAddressBook', [$this, 'updatePersonToAddressBook']);
		
		add_action($actionPrefix.'globkurierGetTracking', [$this, 'getTracking']);
		add_action($actionPrefix.'globkurierGetLabels', [$this, 'getLabels']);
		add_action($actionPrefix.'globkurierGetProtocols', [$this, 'getProtocols']);
		
		add_action($actionPrefix.'globkurierGetOrders', [$this, 'globkurierGetOrders']);
		
		add_action($actionPrefix.'globkurierUpdateInpost', [$this, 'globkurierUpdateInpost']);
		add_action($actionPrefix.'globkurierUpdateRuch', [$this, 'globkurierUpdateRuch']);
		
		add_action($actionPrefix.'globkurierGetOldOrdersAsync', [$this, 'globkurierGetOldOrdersAsync']);
		add_action($actionPrefix.'globkurierCreateOrdersAsync', [$this, 'globkurierCreateOrdersAsync']);
		
		add_action($actionPrefix.'globkurierGetCrossborderTerminals', [$this, 'globkurierGetCrossborderTerminals']);
	}
	
	public function getProducts()
	{
		$nonce = sanitize_text_field($_POST[ 'data' ][ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_get_products_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		
		global $globKurier;
		
		$data = array_map('sanitize_text_field', $_POST[ 'data' ]);

		$products = $globKurier->product()->get($data);
		
		die(json_encode($products));
	}
	
	public function getProductAddons()
	{
		$nonce = sanitize_text_field($_POST[ 'data' ][ 'nonce' ] ?? '');
		if (! wp_verify_nonce($nonce, 'globkurier_get_product_addons_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		if(isset($_POST['data']['nonce'])){
			unset($_POST[ 'data' ][ 'nonce' ]);
		}
		
		global $globKurier;
		
		$data   = array_map('sanitize_text_field', $_POST[ 'data' ]);
		$addons = $globKurier->addons()->productAddons($data);
		
		die(json_encode($addons));
	}
	
	public function getProductAddonFields()
	{
		$nonce = sanitize_text_field($_POST[ 'data' ][ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_get_product_addon_fields_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		unset($_POST[ 'data' ][ 'nonce' ]);
		
		global $globKurier;
		
		$data   = array_map('sanitize_text_field', $_POST[ 'data' ]);
		$addons = $globKurier->addons()->getAddonExtraFields($data);
		
		die(json_encode($addons));
	}
	
	public function getPickupTimeRanges()
	{
		$nonce = sanitize_text_field($_POST[ 'data' ][ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_get_pickup_time_ranges_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		unset($_POST[ 'data' ][ 'nonce' ]);
		
		global $globKurier;
		
		$data             = array_map('sanitize_text_field', $_POST[ 'data' ]);
		$pickupTimeRanges = $globKurier->order()->pickupTimeRanges($data);
		
		die(json_encode($pickupTimeRanges));
	}
	
	public function getFirstPickupDay()
	{
		$nonce = sanitize_text_field($_POST[ 'data' ][ 'nonce' ] ?? '');
		if (! wp_verify_nonce($nonce, 'globkurier_get_first_pickup_day_nonce')) {
			wp_send_json_error('Invalid nonce');
		}

		if(isset($_POST['data']['nonce'])){
			unset($_POST[ 'data' ][ 'nonce' ]);
		}
		
		global $globKurier;
		
		$data = array_map('sanitize_text_field', $_POST[ 'data' ]);
		$day  = $globKurier->order()->getFirstPickupDay($data);
		
		die(json_encode($day));
	}
	
	public function getPrice()
	{
		$nonce = sanitize_text_field($_POST[ 'data' ][ 'nonce' ] ?? '');
		if (! wp_verify_nonce($nonce, 'globkurier_get_price_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		if(isset($_POST['data']['nonce'])){
			unset($_POST[ 'data' ][ 'nonce' ]);
		}
		
		global $globKurier;
		
		$data = array_map([$this, 'recursive_sanitize_text_field'], $_POST[ 'data' ]);
		
		$price = $globKurier->order()->price($data);
		
		die(json_encode($price));
	}
	
	public function getPayments()
	{
		$nonce = sanitize_text_field($_POST[ 'data' ][ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_get_payments_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		unset($_POST[ 'data' ][ 'nonce' ]);
		
		global $globKurier;
		
		$data = array_map([$this, 'recursive_sanitize_text_field'], $_POST[ 'data' ]);
		
		$price = $globKurier->order()->payments($data);
		
		die(json_encode($price));
	}
	
	public function getCustomRequiredFields()
	{
		$nonce = sanitize_text_field($_POST[ 'data' ][ 'nonce' ] ?? '');
		
		if (! wp_verify_nonce($nonce, 'globkurier_get_custom_required_fields_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		if(isset($_POST['data']['nonce'])){
			unset($_POST[ 'data' ][ 'nonce' ]);
		}
		
		global $globKurier;
		
		$data = array_map([$this, 'recursive_sanitize_text_field'], $_POST[ 'data' ]);
		
		$customFields = $globKurier->customRequiredFields()->get($data);
		
		die(json_encode($customFields));
	}
	
	public function order()
	{
		$nonce = sanitize_text_field($_POST[ 'data' ][ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_order_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		unset($_POST[ 'data' ][ 'nonce' ]);
		
		global $globKurier;
		
		$data = array_map([$this, 'recursive_sanitize_text_field'], $_POST[ 'data' ]);
		
		$order = $globKurier->order()->orderShipment($data);
		
		die(json_encode($order));
	}
	
	public function bulkOrder()
	{
		$nonce = sanitize_text_field($_POST[ 'data' ][ 'nonce' ] ?? '');
		if (! wp_verify_nonce($nonce, 'globkurier_order_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		if(isset($_POST['data']['nonce'])){
			unset($_POST[ 'data' ][ 'nonce' ]);
		}
		
		global $globKurier;
		
		$data = array_map([$this, 'recursive_sanitize_text_field'], $_POST[ 'data' ]);
		
		$wcOrderIDs = json_decode($_POST[ 'data' ][ 'wcOrderIDs' ]);
		
		$defaults = $globKurier->settings('default');
		
		$defaultSenderData = apply_filters('globkurier_sender_defaults', $defaults[ 'send' ] ?? []);
		
		$addressPattern = '/(?=\d)/';
		$gkOrders = [];
		
		$wcOrderIDs = array_map('intval', $wcOrderIDs);
		foreach ($wcOrderIDs as $wcOrderID) {
			
			$wcOrder = wc_get_order($wcOrderID);
			
			$receiverAddress = $wcOrder->get_shipping_address_1();
			
			$parsedAddress = UDIGroup_Helper::parseAddress($receiverAddress, $wcOrder->get_shipping_country());
			$receiverAddressStreet = $parsedAddress['street'] ?? $receiverAddress ?? '';
			$receiverAddressHome = $parsedAddress['number'] ?? '';
			
			$receiverData = apply_filters('globkurier_receiver_data', [
				'name'    => $wcOrder->get_shipping_first_name().' '.$wcOrder->get_shipping_last_name(),
				'company' => $wcOrder->get_shipping_company() ?? '',
				'address' => $wcOrder->get_shipping_address_1().$wcOrder->get_shipping_address_2(),
				'street'  => $receiverAddressStreet ?? '',
				'home'    => $receiverAddressHome ?? '',
				'flat'    => $wcOrder->get_shipping_address_2() ?? '',
				'city'    => $wcOrder->get_shipping_city() ?? '',
				'state'   => $wcOrder->get_shipping_state() ?? '',
				'postal'  => $wcOrder->get_shipping_postcode() ?? '',
				'country' => $wcOrder->get_shipping_country() ?? '',
				'email'   => $wcOrder->get_billing_email() ?? '',
				'phone'   => $wcOrder->get_billing_phone() ?? '',
			]);
			
			$fullData = array_merge($data, [
				'height'   => $data[ 'heights' ][ $wcOrder->get_id() ] ?? 1,
				'width'    => $data[ 'widths' ][ $wcOrder->get_id() ] ?? 1,
				'length'   => $data[ 'lengths' ][ $wcOrder->get_id() ] ?? 1,
				'weight'   => $data[ 'weights' ][ $wcOrder->get_id() ] ?? 1,
				'quantity' => $data[ 'quantities' ][ $wcOrder->get_id() ] ?? 1,
				
				'receiver_name'      => $receiverData[ 'name' ] ?? '',
				'receiver_countryId' => $globKurier->countries()->getCountryIdByCode($receiverData[ 'country' ] ?? ''),
				'receiver_postCode'  => $receiverData[ 'postal' ] ?? '',
				'receiver_city'      => $receiverData[ 'city' ] ?? '',
				'receiver_street'    => $receiverData[ 'street' ] ?? '',
				'receiver_home'      => $receiverData[ 'home' ] ?? '',
				'receiver_flat'      => $receiverData[ 'flat' ] ?? '',
				'receiver_phone'     => $receiverData[ 'phone' ] ?? '',
				'receiver_email'     => $receiverData[ 'email' ] ?? '',
				
				'sender_name'      => $defaultSenderData[ 'flnames' ] ?? '',
				'sender_countryId' => $defaultSenderData[ 'country' ] ?? '',
				'sender_postCode'  => $defaultSenderData[ 'postal' ] ?? '',
				'sender_city'      => $defaultSenderData[ 'city' ] ?? '',
				'sender_street'    => $defaultSenderData[ 'street' ] ?? '',
				'sender_home'      => $defaultSenderData[ 'homeNumber' ] ?? '',
				'sender_flat'      => $defaultSenderData[ 'flatNumber' ] ?? '',
				'sender_phone'     => $defaultSenderData[ 'phone' ] ?? '',
				'sender_email'     => $defaultSenderData[ 'email' ] ?? '',
				
				'wcOrderID' => $wcOrder->get_id(),
			]);
			
			if ( $data['isInpost'] == 1 ) {
				$fullData['inpostReceiverPointId'] = $wcOrder->get_meta('globkurier_inpost_value') ?? '';
			} else if ( $data['isRuch'] == 1 ) {
				$fullData['ruchReceiverPointId'] = $wcOrder->get_meta('globkurier_ruch_value') ?? '';
			}
			
			if(!empty($data['extraPickupCarrierId'])){
				$extraPickupCarrierId = $wcOrder->get_meta('globkurier_extra_pickup_carrier_id') ?? '';
				
				$extraPickupCarrierValue       = $wcOrder->get_meta('globkurier_'.$extraPickupCarrierId.'_id', true);
				$extraPickupCarrierHiddenValue = $wcOrder->get_meta('globkurier_'.$extraPickupCarrierId.'_input_hidden_value', true);
				
				$fullData['receiverAddressPointId'] = $extraPickupCarrierHiddenValue ?? '';
			}
			
			unset($fullData['wcOrderIDs']);
			unset($fullData['heights']);
			unset($fullData['lengths']);
			unset($fullData['widths']);
			unset($fullData['weights']);
			unset($fullData['quantities']);
			
			$gkOrder = $globKurier->order()->orderShipment($fullData, true);
			
			if(\array_key_exists('errors', $gkOrder)){
				$gkOrders['errors'][$wcOrder->get_id()] = $gkOrder['errors']['fields'];
			}else{
				$gkOrders['success'][$wcOrder->get_id()] = $gkOrder['number'] ?? '-';
			}
		}

		die(json_encode($gkOrders));
	}
	
	public function getCurrentStatus()
	{
		unset($_POST[ 'data' ][ 'nonce' ]);
		
		global $globKurier;
		
		$data = array_map([$this, 'recursive_sanitize_text_field'], $_POST[ 'data' ]);
		
		$status = $globKurier->order()->getCurrentStatus($data);
		
		die(json_encode($status));
	}
	
	public function getPerson()
	{
		$nonce = sanitize_text_field($_GET[ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_get_person_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		
		global $globKurier;
		
		$status = $globKurier->user()->addressBook([
			'type'   => htmlentities(sanitize_text_field($_GET[ 'type' ]) ?? ''),
			'filter' => htmlentities(sanitize_text_field($_GET[ 'q' ]) ?? ''),
		]);
		
		die(json_encode($status));
	}
	
	public function addPersonToAddressBook()
	{
		$nonce = sanitize_text_field($_POST[ 'data' ][ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_add_person_to_address_book_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		unset($_POST[ 'data' ][ 'nonce' ]);
		
		global $globKurier;
		
		$data = array_map([$this, 'recursive_sanitize_text_field'], $_POST[ 'data' ]);
		
		$status = $globKurier->user()->addToAddresBook($data);
		
		die(json_encode($status));
	}
	
	public function updatePersonToAddressBook()
	{
		$nonce = sanitize_text_field($_POST[ 'data' ][ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_update_person_to_address_book_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		unset($_POST[ 'data' ][ 'nonce' ]);
		
		global $globKurier;
		
		$data = array_map([$this, 'recursive_sanitize_text_field'], $_POST[ 'data' ]);
		
		$status = $globKurier->user()->updateToAddresBook($data);
		
		die(json_encode($status));
	}
	
	public function getTracking()
	{
		$nonce = sanitize_text_field($_POST[ 'data' ][ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_get_tracking_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		unset($_POST[ 'data' ][ 'nonce' ]);
		
		global $globKurier;
		
		$data = array_map([$this, 'recursive_sanitize_text_field'], $_POST[ 'data' ]);
		
		$status = $globKurier->documents()->tracking($data);
		
		die(json_encode($status));
	}
	
	public function getLabels()
	{
		$nonce = sanitize_text_field($_POST[ 'data' ][ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_get_labels_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		unset($_POST[ 'data' ][ 'nonce' ]);
		
		global $globKurier;
		
		$data = array_map([$this, 'recursive_sanitize_text_field'], $_POST[ 'data' ]);
		
		$status = $globKurier->documents()->labels($data);
		
		die(json_encode($status));
	}
	
	public function getProtocols()
	{
		$nonce = sanitize_text_field($_POST[ 'data' ][ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_get_protocols_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		unset($_POST[ 'data' ][ 'nonce' ]);
		
		global $globKurier;
		
		$data = array_map([$this, 'recursive_sanitize_text_field'], $_POST[ 'data' ]);
		
		$status = $globKurier->documents()->protocols($data);
		
		die(json_encode($status));
	}
	
	public function globkurierGetOrders()
	{
		$nonce = sanitize_text_field($_GET[ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_get_orders_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		unset($_POST[ 'nonce' ]);
		
		global $globKurier;
		
		$search = array_map('sanitize_text_field', $_REQUEST[ 'search' ] ?? []);
		
		$request = [
			'start'  => sanitize_text_field($_REQUEST[ 'start' ] ?? '') ? absint($_REQUEST[ 'start' ]) : 0,
			'length' => sanitize_text_field($_REQUEST[ 'length' ] ?? '') ? absint($_REQUEST[ 'length' ]) : 10,
			'search' => $search,
			'draw'   => sanitize_text_field($_REQUEST[ 'draw' ] ?? '') ? absint($_REQUEST[ 'draw' ]) : '',
		];

		$status = $globKurier->documents()->get($request);
		
		wp_send_json_success($status);
	}
	
	public function globkurierUpdateInpost()
	{
		global $globKurier;
		
		$globKurier->inpost()->update();
		wp_die('ok');
	}
	
	public function globkurierUpdateRuch()
	{
		global $globKurier;
		
		$globKurier->ruch()->update();
		wp_die('ok');
	}
	
	public function globkurierGetOldOrdersAsync()
	{
		$nonce = sanitize_text_field($_POST[ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_get_order_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		
		ob_start();
		$orderId = sanitize_text_field($_POST[ 'orderId' ]);
		
		include_once __DIR__.'/../woocommerce/metaBox/oldOrders.php';
		$html = ob_get_clean();
		
		wp_send_json_success($html);
	}
	public function globkurierCreateOrdersAsync()
	{
		$nonce = sanitize_text_field($_POST[ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_create_order_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		
		ob_start();
		$orderId = sanitize_text_field($_POST[ 'orderId' ]);
		
		include_once __DIR__.'/../woocommerce/metaBox/order.php';
		$html = ob_get_clean();
		
		wp_send_json_success($html);
	}
	
	public function globkurierGetCrossborderTerminals()
	{
		$productId = sanitize_text_field($_POST[ 'productId' ]);
		
		global $globKurier;
		$html = $globKurier->crossborderTerminals()->getPointsSelect2($productId);
		
		
		wp_send_json_success($html);
	}
	
	public function recursive_sanitize_text_field($array)
	{
		return $array;
	}
	
}
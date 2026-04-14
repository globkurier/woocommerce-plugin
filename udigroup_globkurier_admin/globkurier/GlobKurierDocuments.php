<?php

namespace udigroup_globkurier;

class GlobKurierDocuments extends GlobKurier
{
	
	public function tracking($params)
	{
		$function = 'order/tracking';
		$method   = 'GET';
		
		$token = $this->api()->getToken();
		
		$response = $this->api()->getResponse($function, $token, $params, $method);
		
		if ($response[ 'code' ] == 200) {
			return $response[ 'data' ];
		} else {
			die('ERROR');
		}
	}
	
	public function getOrderLabelPdf($hash)
	{
		$api_url = $this->api()->getOrderLabelUrl($hash);
		$headers = [
			'x-auth-token' => $this->api()->getToken(),
		];
		
		$response = wp_remote_get(
			$api_url,
			[
				'headers' => $headers,
			]
		);
		
		if (is_wp_error($response)) {
			$error_message = $response->get_error_message();
			
			return $error_message;
		}
		
		$response_body = wp_remote_retrieve_body($response);
		
		return $response_body;
	}
	
	public function getOrderProtocolPdf($hash)
	{
		$api_url = $this->api()->getOrderProtocolUrl($hash);
		$headers = [
			'x-auth-token' => $this->api()->getToken(),
		];
		
		$response = wp_remote_get(
			$api_url,
			[
				'headers' => $headers,
			]
		);
		
		if (is_wp_error($response)) {
			$error_message = $response->get_error_message();
			
			return $error_message;
		}
		
		$response_body = wp_remote_retrieve_body($response);
		
		return $response_body;
	}
	
	public function labels($params)
	{
		$function = 'order/labels';
		$method   = 'GET';
		
		$token = $this->api()->getToken();
		
		$url = $this->api()->getResponse($function, $token, $params, $method, $onlyUrl = true);
		
		return ($url);
	}
	
	public function hasLabels($params)
	{
		$function = 'order/labels';
		$method   = 'GET';
		
		$token = $this->api()->getToken();
		
		$response = $this->api()->getResponse($function, $token, $params, $method);
		
		return $response;
	}
	
	public function protocols($params)
	{
		$function = 'order/protocol';
		$method   = 'GET';
		
		$token = $this->api()->getToken();
		
		$response = $this->api()->getResponse($function, $token, $params, $method);
		
		if ($response[ 'code' ] == 200) {
			wp_send_json_success($response[ 'data' ]);
		} else {
			$errors = [];
			foreach ($response[ 'data' ][ 'fields' ] ?? [] as $key => $error) {
				$errors [] = $error;
			}
			
			wp_send_json_error($errors);
		}
	}
	
	public function get($params)
	{
		global $wpdb, $globKurier;
		
		$start  = $params[ 'start' ] ?? 0;
		$length = $params[ 'length' ] ?? 10;
		
		$mataName = apply_filters('globkurier_wc_order_meta_name', 'globkurier_orders');
		
		$order = [
			'by'   => 'post_id',
			'mode' => 'desc',
		];
		
		$searchCondition = '';
		
		if ($params[ 'search' ] && $params[ 'search' ][ 'value' ]) {
			$searches        = esc_sql($params[ 'search' ][ 'value' ]);
			$searches        = mb_split(' ', $searches);
			$searchCondition = ' AND (1=1 ';
			foreach ($searches as $search) {
				$searchCondition .= $wpdb->prepare(" AND `meta_value` LIKE %s", '%'.$search.'%');
			}
			$searchCondition .= ')';
		}
		
		$sql = $wpdb->prepare("SELECT `meta_value`, `post_id` FROM `{$wpdb->postmeta}` WHERE meta_key = %s{$searchCondition} ORDER BY %s %s LIMIT %d OFFSET %d", [
			$mataName,
			$order[ 'by' ],
			$order[ 'mode' ],
			$length,
			$start,
		]);
		
		$s_orders = $wpdb->get_results($sql, ARRAY_A);
		
		$total_orders = $wpdb->get_var($sql2 = $wpdb->prepare("SELECT COUNT(`post_id`) as `total` FROM `{$wpdb->postmeta}` WHERE meta_key = %s{$searchCondition}", [
			$mataName,
		]));
		
		$orders = [];
		
		foreach ($s_orders as $key => $_order) {
			$data = maybe_unserialize(maybe_unserialize($_order[ 'meta_value' ]));
			
			$wcOrderId = $_order[ 'post_id' ];
			$wcOrder   = wc_get_order($wcOrderId);
			
			$order = [];
			
			$order[ 'check_btn' ]             = true;
			$order[ 'number' ]                = $data[ 'number' ];
			$order[ 'timestamp' ]             = $data[ 'date' ];
			$order[ 'date' ]                  = date('d.m.Y', $data[ 'date' ]);
			$order[ 'receiver_address_name' ] = $data[ 'data' ][ 'receiverAddress' ][ 'name' ];
			$order[ 'content' ]               = $data[ 'data' ][ 'content' ];
			$order[ 'shipment_weight' ]       = $data[ 'data' ][ 'shipment' ][ 'weight' ];
			$order[ 'carrier_name' ]          = $data[ 'carrier' ][ 'name' ];
			$order[ 'cod_value' ]             = $data[ 'cod' ][ 'value' ];
			$order[ 'payment_name' ]          = $data[ 'payment_name' ];
			$order[ 'price_net' ]             = number_format(round($data[ 'price' ][ 'net' ], 2), 2);
			$order[ 'get_status_btn' ]        = '';
			$order[ 'wc_order_link' ]         = $wcOrder ? $wcOrder->get_edit_order_url() : null;
			$order[ 'wc_order_id' ]           = $wcOrderId;
			$order[ 'hash' ]                  = $data[ 'hash' ];
			$order[ 'order_label_url' ]       = $globKurier->api()->getOrderLabelPdfUrl($data[ 'hash' ]);
			$order[ 'order_track_url' ]       = $globKurier->api()->getOrderTrackUrl($data[ 'number' ]);
			$order[ 'hasLabel' ]              = false;
			
			$orders[] = $order;
		}
		
		usort($orders, function ($item1, $item2){
			return $item2[ 'timestamp' ] <=> $item1[ 'timestamp' ];
		});
		
		$hashes = wp_list_pluck($orders, 'hash');
		
		$getAllLabels = $this->hasLabels([
			'orderHashes' => $hashes,
		]);
		
		if ($getAllLabels[ 'code' ] == 200) {
			foreach ($orders as &$order) {
				$order[ 'hasLabel' ] = true;
			}
		} elseif (isset($getAllLabels[ 'data' ], $getAllLabels[ 'data' ][ 'fields' ])) {
			$noLabels = $getAllLabels[ 'data' ][ 'fields' ];
			foreach ($orders as $key => &$order) {
				$apiOrderKey = "orderHashes[{$key}]";
				
				if (! array_key_exists($apiOrderKey, $noLabels)) {
					$order[ 'hasLabel' ] = true;
				}
			}
		}
		
		$json_data = [
			'draw'            => $params[ 'draw' ],
			'recordsTotal'    => $total_orders,
			'recordsFiltered' => $total_orders,
			'data'            => $orders,
		];
		
		echo wp_json_encode($json_data);
		die;
	}
	
	public function getOrder($params)
	{
		$function = 'order';
		$method   = 'GET';
		
		$token = $this->api()->getToken();
		
		$response = $this->api()->getResponse($function, $token, $params, $method);
		
		return $response;
	}
	
}
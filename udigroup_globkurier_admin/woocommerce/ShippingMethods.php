<?php

namespace udigroup_globkurier;

class ShippingMethods{
	
	public function get(){
		$methods = [];
		
		$deliveryZones = \WC_Shipping_Zones::get_zones();
		
		foreach( (array) $deliveryZones as $key => $zone ){
			
			$methods[ $key ] = [
				'zone_name' => $zone[ 'zone_name' ],
			];
			
			foreach( $zone[ 'shipping_methods' ] as $shippingMethod ){
				
				if( $shippingMethod instanceof \WPDesk_Flexible_Shipping ){
					
					$flexible_shipping_rates = $shippingMethod->get_all_rates();
					
					foreach( $flexible_shipping_rates as $rate ){
						$methods[ $key ] [ 'shipping_methods' ][] = [
							'rate_id'         => $rate[ 'id' ],
							'id_for_shipping' => $rate[ 'id_for_shipping' ],
							'title'           => $shippingMethod->get_title() . ' > ' . $rate[ 'method_title' ],
							'is_enabled'      => $rate[ 'method_enabled' ],
						];
					}
					
				} else{ // WC_Shipping_Flat_Rate
					$methods[ $key ] [ 'shipping_methods' ][] = [
						'rate_id'     => $shippingMethod->get_rate_id(),
						'instance_id' => $shippingMethod->get_instance_id(),
						'title'       => $shippingMethod->get_title(),
						'is_enabled'  => $shippingMethod->is_enabled(),
					];
				}
				
			}
		}
		return $methods;
	}
	
	public function getArray(){
		
		$options = [];
		
		$options[ 0 ] = __( '-- WYBIERZ --', 'globkurier' );
		
		$zonesMethods = $this->get() ?? [];
		
		foreach( $zonesMethods as $zone ){
			
			$zoneName   = $zone[ 'zone_name' ] ?? '';
			$zoneMethod = $zone[ 'shipping_methods' ] ?? [];
			
			foreach( $zoneMethod as $k => $method ){
				$options[ $method[ 'rate_id' ] ] = $zoneName . ' > ' . $method[ 'title' ];
			}
			
		}
		
		return $options;
	}
	
}
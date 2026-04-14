<?php

namespace udigroup_globkurier;

class GlobKurierUser extends GlobKurier{
	
	public function profile(){
		
		$function = 'user/profile';
		$method   = 'GET';
		
		$token = $this->api()->getToken();
		
		$response = $this->api()->getResponse( $function, $token, [], $method );
		
		if( $response[ 'code' ] == 200 ){
			return $response[ 'data' ];
		} else{
			die( 'ERROR' );
		}
		
	}
	
	public function addressBook( $params ){
		
		switch( $params[ 'type' ] ){
			case 'senders':
				$function = 'user/addressBook/senders';
				break;
			case 'receivers':
				$function = 'user/addressBook/receivers';
				break;
			default:
				return [];
		}
		
		$method = 'GET';
		
		$token = $this->api()->getToken();
		
		$filters = [
			'filters' => [
				'phrase' => htmlentities( $params[ 'filter' ] ?? '' ),
			]
		];
		
		$response = $this->api()->getResponse( $function, $token, $filters, $method );
		
		if( $response[ 'code' ] == 200 ){
			return $this->parsePersons( $response[ 'data' ][ 'results' ], $params[ 'type' ] );
		} else{
			die( 'ERROR' );
		}
		
	}
	
	public function addToAddresBook( $params ){
		
		switch( $params[ 'type' ] ){
			case 'sender':
				$function = 'user/addressBook/sender';
				break;
			case 'receiver':
				$function = 'user/addressBook/receiver';
				break;
			default:
				return [];
		}
		
		$contact = [
			'name'            => $params[ 'name' ] ?? '',
			'email'           => $params[ 'email' ] ?? '',
			'street'          => $params[ 'street' ] ?? '',
			'houseNumber'     => $params[ 'home' ] ?? '',
			'apartmentNumber' => $params[ 'flat' ] ?? '',
			'postCode'        => $params[ 'postal' ] ?? '',
			'city'            => $params[ 'city' ] ?? '',
			'countryId'       => $params[ 'country' ] ?? '',
			'contactPerson'   => $params[ 'contactName' ] ?? '',
			'phone'           => preg_replace( '/[^0-9]/', '', $params[ 'phone' ] ),
		];
		
		$method = 'POST';
		
		$token = $this->api()->getToken();
		
		$response = $this->api()->getResponse( $function, $token, [ 'contact' => $contact ], $method );
		
		if( $response[ 'code' ] == 200 || $response[ 'code' ] == 201 ){
			return $response[ 'data' ][ 'id' ];
		} else{
			die( 'ERROR' );
		}
		
	}
	
	public function updateToAddresBook( $params ){
		
		switch( $params[ 'type' ] ){
			case 'sender':
				$function = 'user/addressBook/sender';
				break;
			case 'receiver':
				$function = 'user/addressBook/receiver';
				break;
			default:
				return [];
		}
		
		$contact = [
			'id'              => $params[ 'id' ] ?? '',
			'name'            => $params[ 'name' ] ?? '',
			'email'           => $params[ 'email' ] ?? '',
			'street'          => $params[ 'street' ] ?? '',
			'houseNumber'     => $params[ 'home' ] ?? '',
			'apartmentNumber' => $params[ 'flat' ] ?? '',
			'postCode'        => $params[ 'postal' ] ?? '',
			'city'            => $params[ 'city' ] ?? '',
			'countryId'       => $params[ 'country' ] ?? '',
			'contactPerson'   => $params[ 'contactName' ] ?? '',
			'phone'           => preg_replace( '/[^0-9]/', '', $params[ 'phone' ] ),
		];
		
		$method = 'PUT';
		
		$token = $this->api()->getToken();
		
		$response = $this->api()->getResponse( $function, $token, [ 'contact' => $contact ], $method );
		
		if( $response[ 'code' ] == 200 || $response[ 'code' ] == 201 || $response[ 'code' ] == 204 ){
			return $response[ 'data' ][ 'contact' ][ 'id' ];
		} else{
			die( 'ERROR' );
		}
		
	}
	
	private function parsePersons( $response, $type ){
		$persons = [];
		
		foreach( $response as $data ){
			$persons[] = [
				'id'              => $data[ 'id' ] ?? '',
				'name'            => $data[ 'name' ] ?? '',
				'email'           => $data[ 'email' ] ?? '',
				'street'          => $data[ 'street' ] ?? '',
				'houseNumber'     => $data[ 'houseNumber' ] ?? '',
				'apartmentNumber' => $data[ 'apartmentNumber' ] ?? '',
				'postCode'        => $data[ 'postCode' ] ?? '',
				'city'            => $data[ 'city' ] ?? '',
				'countryId'       => $data[ 'countryId' ] ?? 1,
				'phone'           => $data[ 'phone' ] ?? '',
				'contactPerson'   => $data[ 'contactPerson' ] ?? '',
				'tin'             => $data[ 'tin' ] ?? '',
				'type'            => $type,
			];
		}
		
		return $persons;
	}
	
	public function paymentMethods(){
		
		$function = 'user/payment/methods';
		$method   = 'GET';
		
		$token = $this->api()->getToken();
		
		$response = $this->api()->getResponse( $function, $token, [], $method );
		
		if( $response[ 'code' ] == 200 ){
			$methods = [];
			
			foreach( $response[ 'data' ] as $method ){
				if( $method[ 'enabled' ] == 1 ){
					$methods[ $method[ 'id' ] ] = $method[ 'name' ];
				}
			}
			
			return $methods;
		} else{
			die( 'ERROR' );
		}
		
	}
	
}
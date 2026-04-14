<?php

namespace udigroup_globkurier;

class GlobKurierCountries extends GlobKurier{
	
	public function get(){
		
		$function = 'countries';
		$method       = 'GET';
		
		$response = $this->api()->getResponse( $function, NULL, [], $method );
		
		if( $response[ 'code' ] == 200 ){
			return $response[ 'data' ];
		} else{
			die( 'ERROR' );
		}
	}
	
	public function getDropdown( $value = NULL, $type = 'id' ): string{
		
		$countries = $this->get();
		
		$options = [];
		foreach( $countries as $key => $country ){
			
			$selected = '';
			if( $type == 'id' && $value == $country[ 'id' ] ){
				$selected = 'selected';
			}
			
			if( $type == 'code' && strtolower( $value ) == strtolower( $country[ 'isoCode' ] ) ){
				$selected = 'selected';
			}
			
			$options[] = "<option value='{$country['id']}' {$selected}>{$country['name']} {$country['isoCode']}</option>";
			
		}
		
		return implode( '', $options );
	}
	
	public function getCountryIdByCode($code)
	{
		$countries = $this->get();
		
		foreach ($countries as $country) {
			if ( $country[ 'isoCode' ] == $code ){
				return $country[ 'id' ];
			}
		}
		
		return null;
	}
	
	public function getArray(): array{
		
		$countries = $this->get();
		
		$options = [];
		foreach( $countries as $country ){
			$options[ $country[ 'id' ] ] = $country[ 'name' ] . ' ' . $country[ 'isoCode' ];
		}
		
		return $options;
	}
}
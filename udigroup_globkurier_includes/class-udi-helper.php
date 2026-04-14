<?php

namespace udigroup_globkurier;

class UDIGroup_Helper{
	
	public static function getGlobal( $var ){
		$prefix = 'UDIGroup_GLOBKURIER_';
		
		return constant( $prefix . $var );
	}
	
	public static function getBasePath(){
		
		return plugin_dir_path( __DIR__ );
	}
	
	public static function getBaseUrl(){
		
		return plugin_dir_url( __DIR__ );
	}
	
	public static function getInlcudesPath( $path = '' ): string{
		
		return self::getBasePath() . self::getGlobal( 'DIRECTORY_PREFIX' ) . 'includes/' . $path;
	}
	
	public static function getInlcudesUrl( $path = '' ): string{
		
		return self::getBaseUrl() . self::getGlobal( 'DIRECTORY_PREFIX' ) . 'includes/' . $path;
	}
	
	public static function getPublicPath( $path = '' ): string{
		
		return self::getBasePath() . self::getGlobal( 'DIRECTORY_PREFIX' ) . 'public/' . $path;
	}
	
	public static function getAjaxUrl(){
		
		return admin_url( 'admin-ajax.php' );
	}
	
	public static function getPublicUrl( $path = '' ): string{
		
		return self::getBaseUrl() . self::getGlobal( 'DIRECTORY_PREFIX' ) . 'public/' . $path;
	}
	
	public static function getAdminPath( $path = '' ): string{
		
		return self::getBasePath() . self::getGlobal( 'DIRECTORY_PREFIX' ) . 'admin/' . $path;
	}
	
	public static function getAdminUrl( $path = '' ): string{
		
		return self::getBaseUrl() . self::getGlobal( 'DIRECTORY_PREFIX' ) . 'admin/' . $path;
	}
	
	public static function getLanguagePath( $path = '' ): string{
		
		return self::getBasePath() . self::getGlobal( 'DIRECTORY_PREFIX' ) . 'language/' . $path;
	}
	
	public static function getLanguageUrl( $path = '' ): string{
		
		return self::getBaseUrl() . self::getGlobal( 'DIRECTORY_PREFIX' ) . 'language/' . $path;
	}
	
	
	public static function parseAddress($address, $country = ''):array
	{
		$address = trim(preg_replace('/\s+/', ' ', (string)$address));
		
		if ($address === '') {
			return [
				'street' => null,
				'number' => null,
			];
		}
		
		$country = strtoupper((string)$country);
		$numberFirstCountries = ['US', 'CA', 'GB', 'IE', 'FR', 'AU', 'NZ'];
		
		if ($country === 'PL') {
			$address = preg_replace('/^(ulica|ul)\.?/iu', 'ul.', $address);
			$address = preg_replace('/^(aleja|al)\.?/iu', 'al.', $address);
			$address = preg_replace('/^(osiedle|os)\.?/iu', 'os.', $address);
		}
		
		if ($country === 'PL' && preg_match('/^(ul\.|al\.|os\.)?\s*\d+\s+[A-ZĄĆĘŁŃÓŚŹŻa-ząćęłńóśźż]/u', $address)) {
			if (preg_match('/^(?P<prefix>(ul\.|al\.|os\.)\s*)?(?P<street>.+?)\s+(?P<number>\d+[A-Z]?(?:[-\/]\d+[A-Z]?)?)$/u', $address, $plMatches)) {
				return [
					'street' => trim($plMatches['street']),
					'number' => trim($plMatches['number']),
				];
			}
			
			return [
				'street' => $address,
				'number' => null,
			];
		}
		
		$regexNumberFirst = '/^(?P<number>\d+[A-Z]?(?:[-\/]\d+[A-Z]?)?)\s+(?P<street>.+)$/u';
		$regexNumberLast  = '/^(?P<prefix>(ul\.|al\.|os\.)\s*)?(?P<street>.+?)\s+(?P<number>\d+[A-Z]?(?:[-\/]\d+[A-Z]?)?)(?:\s|$)/u';
		$regex             = in_array($country, $numberFirstCountries, true) ? $regexNumberFirst : $regexNumberLast;
		
		if (preg_match($regex, $address, $matches)) {
			return [
				'street' => trim($matches['street']),
				'number' => trim($matches['number']),
			];
		}
		
		if (preg_match('/^\d+[A-Z]?(?:[-\/]\d+[A-Z]?)?$/', $address)) {
			return [
				'street' => trim($address),
				'number' => null,
			];
		}
		
		return [
			'street' => trim($address),
			'number' => null,
		];
	}
	
}
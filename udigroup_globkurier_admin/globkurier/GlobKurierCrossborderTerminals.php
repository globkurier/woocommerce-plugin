<?php

namespace udigroup_globkurier;

class GlobKurierCrossborderTerminals extends GlobKurier
{
	
	public function getPointsSelect2($productId=null)
	{
		$function = 'points';
		$method   = 'GET';
		
		$params = [];
		
		$params[ 'productId' ]	= $productId;
		
		$response = $this->api()->getResponse($function, null, $params, $method);
		
		$points = $response[ 'data' ];
		
		array_multisort(
			array_column($points, 'city'),
			SORT_ASC, $points
		);
		
		$pointsResponse = $points;
		
		return json_encode($pointsResponse);
	}
	
}
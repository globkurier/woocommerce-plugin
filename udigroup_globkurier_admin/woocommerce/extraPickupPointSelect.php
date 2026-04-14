<?php
/**
 * @var string $key
 * @var string $method
 */

use udigroup_globkurier\UDIGroup_Helper;

global $globKurier;

$fields = [
	'idField'     => 'globkurier_'.$key.'_input',
	'valueField'  => 'globkurier_'.$key.'_input_hidden_value',
	'selectField' => 'globkurier_'.$key.'_input_value',
	
	'point_lat'          => 'globkurier_'.$key.'_input_point_lat',
	'point_long'         => 'globkurier_'.$key.'_input_point_long',
	'point_city'         => 'globkurier_'.$key.'_input_point_city',
	'point_address'      => 'globkurier_'.$key.'_input_point_address',
	'point_openingHours' => 'globkurier_'.$key.'_input_point_openingHours',
];

$actions = [
	'search'      => 'globkurierGetExtraPickupsPointsSelect2',
	'saveSession' => 'globkurierSaveExtraPickupsPointsSession',
];

$wrapperId = 'globkurier-pickup-wrapper_'.$key;

$carrierId = str_replace('extra_pickup_point_', '', $key);

$productId = $globKurier->extraPickupPoints()->findProductIdForCarrierId($carrierId);

$point_id           = WC()->session->get('globkurier_'.$carrierId.'_selected_point_id') ?? '';
$point_val          = WC()->session->get('globkurier_'.$carrierId.'_selected_point_value') ?? '';
$point_lat          = WC()->session->get('globkurier_'.$carrierId.'_selected_point_latitude') ?? '';
$point_long         = WC()->session->get('globkurier_'.$carrierId.'_selected_point_longitude') ?? '';
$point_city         = WC()->session->get('globkurier_'.$carrierId.'_selected_point_city') ?? '';
$point_address      = WC()->session->get('globkurier_'.$carrierId.'_selected_point_address') ?? '';
$point_openingHours = WC()->session->get('globkurier_'.$carrierId.'_selected_point_openingHours') ?? '';

$hasMap = !empty($googleMapsAPIKey);

if($hasMap){
	echo "<tr class='order-total'>";
	echo "<th colspan='2' id='udi-map-td'>";
}else{
	echo "<div class='globkurier-ruch-container'>";
}
?>

<input type='hidden'
       name='globkurier_method_id'
       id='globkurier_method_id'
       value="<?= esc_attr($method) ?>">

<input type='hidden'
       name='<?= esc_attr($fields[ 'idField' ]) ?>'
       id='<?= esc_attr($fields[ 'idField' ]) ?>'
       value="<?php
	   echo esc_attr($point_val) ?>"
       required>

<input type='hidden' name='<?= esc_attr($fields[ 'valueField' ]) ?>'
       id='<?= esc_attr($fields[ 'valueField' ]) ?>'
       value="<?php
	   echo esc_attr($point_id) ?>"
       required>

<select type='text'
		        style='width: 100%;'
		        class='udi-select2'
		        id='<?= esc_attr($fields[ 'selectField' ]) ?>'
		        name='<?= esc_attr($fields[ 'selectField' ] ?? '') ?>'>
			<?php
			if (isset($point_id)) {
				echo "<option value='".esc_attr($point_id)."' selected>".esc_attr($point_val).'</option>';
			}
			?>
		</select>

<?php
if($hasMap){
	echo '</th>';
	echo '</tr>';
}else{
	echo '</div>';
}
?>

<script>
	( function ( $ ) {
		$( function () {
			const params = {
				product: {
					id: '<?= esc_js($productId) ?>',
				},
				carrier: {
					id: '<?= esc_js($carrierId) ?>',
				},
				fields: {
					idField: '<?= esc_js($fields[ 'idField' ] ?? '') ?>',
					valueField: '<?= esc_js($fields[ 'valueField' ] ?? '') ?>',
					selectField: '<?= esc_js($fields[ 'selectField' ] ?? '') ?>',
				},
				actions: {
					search: '<?= esc_js($actions[ 'search' ] ?? '') ?>',
					saveSession: '<?= esc_js($actions[ 'saveSession' ] ?? '') ?>',
					getSessionData: '<?= esc_js($actions[ 'getSessionData' ] ?? '') ?>',
				},
				wrapperId: '<?= esc_js($wrapperId) ?>',
				ajax_url: data[ 'ajaxUrl' ],
			};
			
			const selectTarget = '#' + params.fields.selectField;
			
			const gkSaveSession = ( d ) => {
				$( document ).find( '#' + params.fields.idField, '#' + params.wrapperId ).val( d.value || d.text ).change();
				$( document ).find( '#' + params.fields.valueField, '#' + params.wrapperId ).val( d.id ).change();
				$( document ).find( '.select2-selection__rendered', '#' + params.wrapperId ).text( d.value || d.text ).change();
				$.post( {
					url: params.ajax_url,
					dataType: 'json',
					minLength: 3,
					data: {
						action: params.actions.saveSession,
						id: d.id,
						value: d.text,
						latitude: d.latitude,
						longitude: d.longitude,
						city: d.city,
						address: d.address,
						openingHours: d.openingHours,
						productId: params.product.id,
						carrierId: params.carrier.id,
					},
				} );
			}
			
			
			$( document ).find( selectTarget ).select2( {
				placeholder: 'Znajdź punkt dostawy',
				language: {
					searching: function () {
						return 'Znajdź punkt dostawy';
					},
					inputTooShort: function () {
						return 'Wpisz miasto';
					},
					noResults: function () {
						return 'Brak wyników';
					},
					errorLoading: function () {
						return 'Szukanie...';
					},
					loadingMore: function () {
						return 'Szukanie...';
					},
				},
				ajax: {
					url: params.ajax_url,
					dataType: 'json',
					data: function ( search ) {
						return {
							city: search.term,
							action: params.actions.search,
							productId: params.product.id
						}
					},
					processResults: function ( data ) {
						let parsedData = JSON.parse( data );
						let options = [];
						if ( parsedData ) {
							$.each( parsedData, function ( index, data ) {
								console.log( {
									id: data.id,
									text: data.name,
									latitude: data.latitude,
									longitude: data.longitude,
									city: data.city,
									address: data.address,
									openingHours: data.openingHours,
								} );
								options.push( {
									id: data.id,
									text: data.name + ', ' + data.address + ' [' + data.id + ']',
									latitude: data.latitude,
									longitude: data.longitude,
									city: data.city,
									address: data.address,
									openingHours: data.openingHours,
								} );
							} );
							
						}
						return {
							results: options
						};
					},
					cache: true
				},
				minimumInputLength: 3,
			} ).on( 'select2:select', function ( e ) {
				gkSaveSession(
					e.params.data,
				);
			} );
			
		} );
	} )( jQuery );
</script>
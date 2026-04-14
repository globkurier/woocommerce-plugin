<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

global $globKurier;

$point_id           = WC()->session->get('globkurier_ruch_selected_point_id') ?? '';
$point_val          = WC()->session->get('globkurier_ruch_selected_point_value') ?? '';
$point_lat          = WC()->session->get('globkurier_ruch_selected_point_latitude') ?? '';
$point_long         = WC()->session->get('globkurier_ruch_selected_point_longitude') ?? '';
$point_city         = WC()->session->get('globkurier_ruch_selected_point_city') ?? '';
$point_address      = WC()->session->get('globkurier_ruch_selected_point_address') ?? '';
$point_openingHours = WC()->session->get('globkurier_ruch_selected_point_openingHours') ?? '';

$isInitDisabled             = WC()->session->get('globkurier_disable_ruch_init') ?? 0;
$globkurier_old_ruch_params = WC()->session->get('globkurier_old_ruch_params') ?? '';

if ($isInitDisabled == 'off') {
	$isInitDisabled = 0;
}

$sessionCity = WC()->session->customer[ 'city' ];

$allPoints = $globKurier->ruch()->getAllPoints();

wp_enqueue_script( 'markerclusterer', udigroup_globkurier\UDIGroup_Helper::getAdminUrl( 'js/markers/markerclusterer_compiled.js' ), array(), '1.0', true );
?>

<tr class="order-total">
	<th colspan="2" id="udi-map-td">
		<input type="hidden" name="globkurier_method_id" id="globkurier_method_id" value="<?php
		echo esc_attr($globKurier->settings('ruch_method') ?? '') ?>">
		<input type="hidden" name="globkurier_ruch_input" id="globkurier_ruch_input" value="<?php
		echo esc_attr($point_val) ?>" required>
		<input type="hidden" name="globkurier_ruch_input_hidden_value" id="globkurier_ruch_input_hidden_value" value="<?php
		echo esc_attr($point_id) ?>" required>

		<div id="globkurier_map"></div>

		<select style="width: 100%; display: none" class="udi-select2" id="udi-select-ruch" name="globkurier_ruch_input_value">
			<option></option>
		</select>
	</th>
</tr>

<script>
	( function ( $ ) {
		$( function () {
			const log = false;
			
			const ajaxurl = data[ 'ajaxUrl' ];
			
			const globkurierInput = '#globkurier_ruch_input';
			const udiSelect = '.udi-select2#udi-select-ruch';
			const globkurierInputHiddenValue = '#globkurier_ruch_input_hidden_value';
			const select2SelectContainer = '#select2-udi-select-ruch-container'
			const getPointsAction = 'globkurierGetRuchPointsSelect2';
			
			const saveSessionAction = 'globkurierSaveRuchPointsSession';
			
			const clusterIcon = 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m';
			
			const map_container = 'globkurier_map';
			const poland = { lat: 52.476329, lng: 18.995734 };
			
			var customOpen = false;
			
			let markerCluster;
			let globkurier_map;
			
			var currentQuery;
			let markers = [];
			
			var isInitDisabled = <?php echo esc_attr($isInitDisabled) ?>;
			
			globkurierMapInit();
			
			function globkurierMapInit() {
				logger( 'globkurierMapInit' );
				
				globkurier_map = new google.maps.Map( document.getElementById( map_container ), {
					center: poland,
					zoom: 6,
					disableDefaultUI: true
				} );
			}
			
			function gkDisableInit( type, status, params ) {
				logger( 'gkDisableInit', type, status );
				let action;
				
				switch ( type ) {
					case 'ruch':
						action = 'globkurierSaveRuchPointsDisableInitSession';
						break;
					case 'inpsot':
						action = 'globkurierSaveInpostPointsDisableInitSession';
						break;
					default:
						return;
				}
				
				$.post( {
					url: ajaxurl,
					data: {
						action: action,
						status: status,
						params: params || [],
					},
				} );
			}
			
			function gkSaveSession( d ) {
				logger( 'gkSaveSession' );
				
				$( document ).find( globkurierInput ).val( d.value || d.text );
				$( document ).find( globkurierInputHiddenValue ).val( d.id );
				
				$( document ).find( '#udi-map-td .select2-selection__rendered' ).text( d.value || d.text );
				
				$.post( {
					url: ajaxurl,
					dataType: 'json',
					minLength: 3,
					data: {
						action: saveSessionAction,
						id: d.id,
						value: d.value || d.text,
						latitude: d.latitude,
						longitude: d.longitude,
						city: d.city,
						address: d.address,
						openingHours: d.openingHours,
					},
				} );
			}
			
			$( document ).on( 'click', '.globkurier_marker_select_point', function ( e ) {
				logger( 'globkurier_marker_select_point' );
				
				let eventData = $( e.target ).data( 'eventdata' );
				gkSaveSession( eventData );

				$( e.target ).parent().parent().parent().parent().find( 'button.gm-ui-hover-effect' ).click()
			} );
			
			<?php
			
			if ( $point_val && $point_id && $point_lat && $point_long && $point_city && $point_address  ) {  ?>
			let lat = <?php echo esc_attr($point_lat) ?>;
			let lng = <?php echo esc_attr($point_long) ?>;
			let pos = { lat, lng };
			
			let contentString = `<div class='gkInfowindow-inside'><p> Miasto: <?php echo esc_attr($point_city) ?> </p>` + `<p> Adres: <?php echo esc_attr($point_address) ?> </p>`;
			
			let tooltip = new google.maps.InfoWindow( {
				content: contentString
			} );
			
			let marker = new google.maps.Marker( {
				position: pos,
			} );
			
			marker.addListener( 'click', () => {
				tooltip.open( globkurier_map, marker );
				$( globkurierInput ).val( '<?php echo esc_attr($point_val) ?>' );
				$( globkurierInputHiddenValue ).val( '<?php echo esc_attr($point_val) ?>' );
			} );
			
			globkurier_map.setZoom( 15 );
			globkurier_map.setCenter( marker.getPosition() );
			
			marker.setMap( globkurier_map );
			
			<?php } ?>
			
			function matchStart( params, data ) {
				params.term = params.term || '';
				if ( data.text.toUpperCase().indexOf( params.term.toUpperCase() ) == 0 ) {
					return data;
				}
				return null;
			}
			
			$( document ).find( udiSelect ).select2( {
					placeholder: 'Znajdź punkt dostawy',
					
					language: {
						searching: function () {
							return 'Szukaj Paczka w RUCHu';
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
					minimumInputLength: 3,
					ajax: {
						url: data[ 'ajaxUrl' ],
						dataType: 'json',
						data: function ( params ) {
							return {
								city: params.term,
								action: getPointsAction,
							};
						},
						processResults: function ( data ) {
							let parsedData = JSON.parse( data );
							
							let options = [];
							if ( parsedData ) {
								$.each( parsedData, function ( index, data ) {
									options.push( {
										id: data.id,
										text: data.label,
										latitude: parseFloat(data.latitude),
										longitude: parseFloat(data.longitude),
										city: data.city,
										address: data.address,
										openingHours: data.openingHours,
									} );
								} );
								
								globkurierMapInit();
								
								let markers = [];
								
								parsedData.forEach( ( d ) => {
									let lat = parseFloat(d.latitude);
									let lng = parseFloat(d.longitude);
									let pos = { lat, lng };
									
									console.log(pos);
									
									let contentString =
										`<div class="gkInfowindow-inside">` +
										`<p> Miasto: ${ d.city } </p>` +
										`<p> Adres: ${ d.address } </p>`;
									
									let tooltip = new google.maps.InfoWindow( {} );
									
									contentString += `<button class="globkurier_marker_select_point"`;
									
									let eventData = JSON.stringify( d )
									let tooltipData = JSON.stringify( d )
									
									contentString += " data-eventdata='" + eventData + "'";
									contentString += " data-tooltip='" + tooltipData + "'";
									contentString += ` type="button">WYBIERZ</button>`;
									
									contentString += `</div>`;
									
									tooltip.setContent( contentString );
									
									let marker = new google.maps.Marker( {
										position: pos,
										maxWidth: 300,
									} );
									
									marker.addListener( 'click', () => {
										tooltip.open( globkurier_map, marker );
										globkurier_map.setCenter( new google.maps.LatLng( d.latitude, d.longitude ) );
										globkurier_map.setZoom( 15 );
									} );
									
									markers.push( marker );
								} );
								
								markerCluster = new MarkerClusterer( globkurier_map, markers, { imagePath: clusterIcon } );
								
								markerCluster.fitMapToMarkers();
							}
							return {
								results: options
							};
						},
						cache: true
					},
				} )
				.on( 'select2:open', function ( e ) {
					logger( 'select2:open' );
				} )
				.on( 'select2:closing', function () {
					logger( 'select2:closing' );
					currentQuery = $( '.select2-search input' ).prop( 'value' );
				} );
			
			<?php if( isset($point_id) && $point_id != '' ){?>
			logger( 'init set current point name' );
			$( document ).find( '#udi-map-td .select2-selection__rendered' ).text( '<?php echo esc_attr($point_val) ?>' );
			<?php } ?>
			
			$( document ).find( udiSelect ).on( 'select2:select', function ( e ) {
				logger( 'select2:select' );
				
				let option = $( e.params.data );
				
				console.log( option, option[ 0 ].id );
				
				$( globkurierInput ).val( option[ 0 ].text );
				$( globkurierInputHiddenValue ).val( option[ 0 ].id );
				
				let lat = option[ 0 ].latitude;
				let lng = option[ 0 ].longitude;
				let pos = { lat, lng };
				let markers = [];
				
				let contentString = `<div class='gkInfowindow-inside'` +
					`<p> Miasto: ${ option[ 0 ].city } </p>` +
					`<p> Adres: ${ option[ 0 ].address } </p>`;
				
				let tooltip = new google.maps.InfoWindow( {
					content: contentString
				} );
				
				let marker = new google.maps.Marker( {
					position: pos,
				} );
				
				marker.addListener( 'click', () => {
					tooltip.open( globkurier_map, marker );
				} );
				
				markers.push( marker );
				
				markerCluster = new MarkerClusterer( globkurier_map, markers, { imagePath: clusterIcon } );
				
				globkurier_map.setCenter( marker.getPosition() );
				globkurier_map.setZoom( 15 );
				
				gkSaveSession( {
					id: option[ 0 ].id,
					value: option[ 0 ].text,
					latitude: option[ 0 ].latitude,
					longitude: option[ 0 ].longitude,
					city: option[ 0 ].city,
					address: option[ 0 ].address,
					openingHours: option[ 0 ].openingHours,
				} );
			} );
			
			$( document ).find( udiSelect ).data( 'select2' ).on( 'results:message', function ( params ) {
				logger( 'results:message', params );
				
				this.dropdown._resizeDropdown();
				this.dropdown._positionDropdown();
			} );
			
			function addPointToMap( d ) {
				if ( d ) {
					if ( d.loading == true ) {
						return;
					}
					
					let option = $( d.element );
					let lat = d.latitude;
					let lng = d.longitude;
					let pos = { lat, lng };
					let city = d.city;
					let address = d.address;
					let openinghours = d.openinghours;
					
					let contentString =
						`<div class="gkInfowindow-inside">` +
						`<p> Miasto: ${ city } </p>` +
						`<p> Adres: ${ address } </p>`;
					
					let tooltip = new google.maps.InfoWindow();
					
					contentString += `<button class="globkurier_marker_select_point"`;
					
					let eventData = JSON.stringify( {
						id: d.id,
						value: d.text,
						latitude: lat,
						longitude: lng,
						city: city,
						address: address,
						openingHours: openinghours,
					} )
					
					contentString += " data-eventdata='" + eventData + "'";
					contentString += ` type="button">WYBIERZ</button>`;
					
					contentString += `</div>`;
					
					tooltip.setContent( contentString );
					
					let marker = new google.maps.Marker( {
						position: pos,
						map: globkurier_map,
						maxWidth: 300,
					} );
					
					tooltip.setPosition( marker.getPosition() );
					
					marker.addListener( 'click', () => {
						tooltip.open( globkurier_map, marker );
						globkurier_map.setCenter( new google.maps.LatLng( lat, lng ) );
					} );

					return marker;
				}
			}
			
			$( document ).find( udiSelect ).data( 'select2' ).on( 'results:all', function ( params ) {
				
				logger( 'results', params );
				
				globkurierMapInit();
				
				markers = [];
				
				let data = params.data.results;
				
				if ( data.length == 0 ) {
					return;
				}
				
				let markersData = []
				data.forEach( ( d ) => {
					let marker = addPointToMap( d );
					markers.push( marker );
				} );
				
				markerCluster = new MarkerClusterer( globkurier_map, markers, { imagePath: clusterIcon } );
				
				if ( markers.length == 1 ) {
					globkurier_map.setZoom( 15 );
					globkurier_map.setCenter( markers[ 0 ].getPosition() );
				} else {
					markerCluster.fitMapToMarkers();
				}
				
			} );
			
			function select2_search( $el, term, closeAfter = true ) {
				let currentPosition = window.pageYOffset || document.documentElement.scrollTop;
				
				currentQuery = '';
				
				$el.select2( 'open' );
				
				let $search = $el.data( 'select2' ).dropdown.$search || $el.data( 'select2' ).selection.$search;
				$search.val( term );
				$search.trigger( 'input' );
				
				if ( closeAfter === true ) {
					$el.select2( 'close' );
				}
				
				setTimeout( function () {
					$( window ).scrollTop( currentPosition );
				}, 10 );
			}
			
			$( document ).on( 'change', '#billing_city', function ( e ) {
				if ( $( '#ship-to-different-address-checkbox' ).is( ':checked' ) ) {
					return
				}
				
				if ( $( globkurierInputHiddenValue ).val() !== '' ) {
					return;
				}
				
				select2_search( $( document ).find( udiSelect ), $( this ).val() + ',', true );
				
				currentQuery = $( this ).val();
				
				logger( 'billing_city change' );
			} );
			
			$( document ).on( 'change', '#shipping_city', function ( e ) {
				if ( !$( '#ship-to-different-address-checkbox' ).is( ':checked' ) ) {
					return
				}
				
				if ( $( globkurierInputHiddenValue ).val() !== '' ) {
					return;
				}
				
				select2_search( $( document ).find( udiSelect ), $( this ).val() + ',', true );
				
				logger( 'shipping_city change' );
			} );
			
			$( select2SelectContainer ).removeAttr( 'title' );
			
			function logger( ...msg ) {
				if ( !log || false ) {
					return;
				}
				console.table( msg );
			}
		} );
	} )( jQuery );
</script>
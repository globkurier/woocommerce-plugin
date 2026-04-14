
( function ( $ ) {
	$( function () {
		'use strict';
		$( document ).ready( function () {

			$( document ).on( 'input', '#globkurier_inpost_input', function () {
				let search = $( this ).val().trim();

				if ( search != '' ) {
					autocompleteInpost( search, $( this ) );
				} else {
					$( '.ui-autocomplete' ).html( '' );
					$( '#globkurier_inpost_input_value' ).val( '' );
				}
			} );
			$( document ).on( 'change', '#globkurier_inpost_input', function () {
				let search = $( this ).val().trim();

				if ( search != '' ) {
					autocompleteInpost( search, $( this ) );
				} else {
					$( '.ui-autocomplete' ).html( '' );

					$( '#globkurier_inpost_input' ).val( '' );
					$( '#globkurier_inpost_input_value' ).val( '' );

					$.post( {
						url: data[ 'ajaxUrl' ],
						dataType: 'json',
						minLength: 3,
						data: {
							action: 'globkurierSaveInpostPointsSession',
							id: '',
							value: '',
							nonce: globkurier_save_inpost_points_session_nonce
						},
						success: function ( data ) {
						},
					} );
				}
			} );
			$( document ).on( 'click', '#globkurier_inpost_input', function () {
				let menuId = $( this ).data( 'data-menuid' );
				$( '.ui-autocomplete' + '#' + menuId ).show();
			} );

			$( document ).on( 'input', '#globkurier_ruch_input', function () {
				let search = $( this ).val().trim();

				if ( search != '' ) {
					autocompleteRuch( search, $( this ) );
				} else {
					$( '#globkurier_inpost_input_value' ).val( '' );
				}
			} );
			$( document ).on( 'change', '#globkurier_ruch_input', function () {
				let search = $( this ).val().trim();

				if ( search != '' ) {
					autocompleteRuch( search, $( this ) );
				} else {
					$( '.ui-autocomplete' ).html( '' );

					$( '#globkurier_ruch_input' ).val( '' );
					$( '#globkurier_ruch_input_value' ).val( '' );

					$.post( {
						url: data[ 'ajaxUrl' ],
						dataType: 'json',
						minLength: 3,
						data: {
							action: 'globkurierSaveRuchPointsSession',
							id: '',
							value: '',
							nonce: globkurier_save_ruch_points_session_nonce
						},
						success: function ( data ) {
						},
					} );

				}
			} );

			$( document ).on( 'click', '#globkurier_ruch_input', function () {
				let menuId = $( this ).data( 'data-menuid' );
				$( '.ui-autocomplete' + '#' + menuId ).show();
			} );
			
		} );

		function clearMapInpost(){
			markers_clusterer_inpost.clearMarkers();
		}

		function autocompleteInpost( search, input ) {
			$( '#globkurier_inpost_input_value' ).val( '' );

			input.autocomplete( {
				source: function ( request, response ) {
					var ajaxUrl = data['ajaxUrl'];
					$.post( {
						url: data[ 'ajaxUrl' ],
						dataType: 'json',
						minLength: 3,
						data: {
							action: 'globkurierGetInpostPoints',
							city: search,
							nonce: globkurier_get_inpost_points_nonce
						},
						success: function ( data ) {
							let parsedData = JSON.parse( data );

							if ( parsedData.length == 0 ) {
								return response( [ {
									'label': 'Brak wyników',
									'value': search,
								} ] )
							}

							clearMapInpost();

							parsedData.forEach((d) => {
								let lat = d.latitude;
								let lng = d.longitude;
								let pos = {lat,lng};

								let contentString =
								`<p> Miasto: ${d.city} </p>` +
								`<p> Adres: ${d.address} </p>`;

								if (data.openingHours !== ''){
								contentString += `<p> Godziny otwarcia: ${d.openingHours} </p>`;
								}

								var tooltip = new google.maps.InfoWindow({
									content: contentString
								});

								let marker = new google.maps.Marker({
									position:pos,
								});

								marker.addListener('mouseover', () => {
									tooltip.open(map_inpost, marker);
								});

								marker.addListener('mouseout', () => {
									tooltip.close();
								});

								marker.addListener('click', () => {

									$('#globkurier_inpost_input').val(d.label)
									$('#globkurier_inpost_input_value').val(d.id)

									$.post( {
										url:ajaxUrl,
										dataType: 'json',
										minLength: 3,
										data: {
											action: 'globkurierSaveInpostPointsSession',
											id: d.id,
											value: d.value,
											latitude: d.latitude,
											longitude: d.longitude,
											city: d.city,
											address: d.address,
											openingHour: d.openingHours,
											nonce: globkurier_save_inpost_points_session_nonce
										},
										success: function ( data ) {
										},
									} );
								})


								markers_clusterer_inpost.addMarker(marker);
							})

							markers_clusterer_inpost.fitMapToMarkers();

							return response( parsedData );
						},
					} );
				},
				minLength: 3,
				open: function () {
					let cityInput = $( '#globkurier_inpost_input' );
					let menuItems = $( '.ui-menu-item' );

					$( '.ui-autocomplete' ).css( 'width', 'max-content' );

					var scrollbarWidth = $( '.ui-autocomplete' )[ 0 ].offsetWidth - $( '.ui-autocomplete' )[ 0 ].clientWidth;

					menuItems.css( 'max-width', cityInput.width() - scrollbarWidth );
				},
				select: function ( event, item ) {
					$( '#globkurier_inpost_input_value' ).val( item.item.id );

					$.post( {
						url: data[ 'ajaxUrl' ],
						dataType: 'json',
						minLength: 3,
						data: {
							action: 'globkurierSaveInpostPointsSession',
							id: item.item.id,
							value: item.item.value,
							latitude: item.item.latitude,
							longitude: item.item.longitude,
							city: item.item.city,
							address: item.item.address,
							openingHour: item.item.openingHours,
							nonce: globkurier_save_inpost_points_session_nonce
						},
						success: function ( data ) {

						}
						,
					} );

					map_inpost.setCenter({lat:item.item.latitude,lng:item.item.longitude});
					map_inpost.setZoom(14);

				}
			} );

			input.data( 'data-menuid', input.autocomplete( "instance" ).menu.element.attr( 'id' ) );
		}

		function autocompleteRuch( search, input ) {
			$( '#globkurier_ruch_input_value' ).val( '' );

			input.autocomplete( {
				source: function ( request, response ) {
					var ajaxUrl = data['ajaxUrl'];
					$.post( {
						url: data[ 'ajaxUrl' ],
						dataType: 'json',
						minLength: 3,
						data: {
							action: 'globkurierGetRuchPoints',
							nonce: globkurier_get_ruch_points_nonce,
							city: search,
						},
						success: function ( data ) {
							let parsedData = JSON.parse( data );

							if ( parsedData.length == 0 ) {
								return response( [ {
									'label': 'Brak wyników',
									'value': search,
								} ] )
							}

							clearMapRuch();
							parsedData.forEach((d) => {
								let lat = d.latitude;
								let lng = d.longitude;
								let pos = {lat,lng};

								let contentString =
									`<p> Miasto: ${d.city} </p>` +
									`<p> Adres: ${d.address} </p>`;

								if (d.openingHours !== ''){
									contentString += `<p> Godziny otwarcia: ${d.openingHours} </p>`;
								}

								var tooltip = new google.maps.InfoWindow({
									content: contentString
								});

								let marker = new google.maps.Marker({
									position:pos,
								});

								marker.addListener('mouseover', () => {
									tooltip.open(map_ruch, marker);
								});

								marker.addListener('mouseout', () => {
									tooltip.close();
								});

								marker.addListener('click', () => {
									$('#globkurier_ruch_input').val(d.label);
									$('#globkurier_ruch_input_value').val(d.id);

									$.post( {
										url:ajaxUrl,
										dataType: 'json',
										minLength: 3,
										data: {
											action: 'globkurierSaveRuchPointsSession',
											id: d.id,
											value: d.value,
											latitude: d.latitude,
											longitude: d.longitude,
											city: d.city,
											address: d.address,
											openingHour: d.openingHours,
											nonce: globkurier_save_ruch_points_session_nonce
										},
										success: function ( data ) {
										},
									} );

								})

								markers_clusterer_ruch.addMarker(marker);
							});


							return response( parsedData );
						},
					} );
				},
				minLength: 3,
				open: function () {
					let cityInput = $( '#globkurier_ruch_input' );
					let menuItems = $( '.ui-menu-item' );

					$( '.ui-autocomplete' ).css( 'width', 'max-content' );

					var scrollbarWidth = $( '.ui-autocomplete' )[ 0 ].offsetWidth - $( '.ui-autocomplete' )[ 0 ].clientWidth;

					menuItems.css( 'max-width', cityInput.width() - scrollbarWidth );
				},
				select: function ( event, item ) {

					$( '#globkurier_ruch_input_value' ).val( item.item.id );


					$.post( {
						url: data[ 'ajaxUrl' ],
						dataType: 'json',
						minLength: 3,
						data: {
							action: 'globkurierSaveRuchPointsSession',
							id: item.item.id,
							value: item.item.value,
							latitude: item.item.latitude,
							longitude: item.item.longitude,
							city: item.item.city,
							address: item.item.address,
							openingHour: item.item.openingHours,
							nonce: globkurier_save_ruch_points_session_nonce
						},
						success: function ( data ) {
						},
					} );


					map_ruch.setCenter({lat:item.item.latitude,lng:item.item.longitude});
					map_ruch.setZoom(14);

				}

			} );

			input.data( 'data-menuid', input.autocomplete( "instance" ).menu.element.attr( 'id' ) );
		}

	} );
} )( jQuery );

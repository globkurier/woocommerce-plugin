<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

global $wpdb;
global $globKurier;

$globKurier->isUserLoggedIn(true);

if (sanitize_text_field($_GET[ 'getPdfLabels' ] ?? '')) {
	ob_clean();

    $hashes = sanitize_text_field($_GET[ 'hashes' ]) ?? '';
	
	$pdf = $globKurier->documents()->getOrderLabelPdf($hashes);
	
	if(strpos($pdf, '%PDF') === false){
		echo esc_attr($pdf);
		die;
	}
	
	header('Content-type: application/pdf');
	header('Content-Disposition: inline; filename="label.pdf"');
	header('Content-Transfer-Encoding: binary');
	header('Accept-Ranges: bytes');
	
	die($pdf);
}

if (sanitize_text_field($_GET[ 'getPdfProtocols' ] ?? '')) {
    $hashes = sanitize_text_field($_GET[ 'hashes' ]) ?? '';

	$pdf = $globKurier->documents()->getOrderProtocolPdf($hashes);
	
	if(strpos($pdf, '%PDF') === false){
		echo esc_attr($pdf);
		die;
	}
	
	ob_clean();
	header('Content-type: application/pdf');
	header('Content-Disposition: inline; filename="protocol.pdf"');
	header('Content-Transfer-Encoding: binary');
	header('Accept-Ranges: bytes');
	
	die($pdf);
}

$mataName = apply_filters('globkurier_wc_order_meta_name', 'globkurier_orders');
$query = $wpdb->prepare(
    "SELECT `meta_value`, `post_id` FROM `{$wpdb->postmeta}` WHERE meta_key = %s",
    $mataName
);
$s_orders = $wpdb->get_results($query, ARRAY_A);

$orders = [];
foreach ($s_orders as $order) {
	$data = maybe_unserialize(maybe_unserialize($order[ 'meta_value' ]));
	
	$data[ 'wcOrderId' ] = $order[ 'post_id' ];
	
	$orders[] = $data;
}

usort($orders, function ($item1, $item2){
	return $item2[ 'date' ] <=> $item1[ 'date' ];
});

?>

<h1><?php
	echo esc_attr(__('Lista zamówień GlobKurier', 'globkurier')) ?></h1>

<div class="globkurier-old-orders-table-wrapper">
	<table class="globkurier-old-orders-table stripe udi-is-ajax-datatable">
		<thead>
		<tr>
			<th></th>
			<th style="min-width: 150px"><?php
				echo esc_attr(__('Nr zamówienia GK', 'globkurier')) ?></th>
			<th style="min-width: 100px"><?php
				echo esc_attr(__('Data nadania', 'globkurier')) ?></th>
			<th style="min-width: 100px"><?php
				echo esc_attr(__('Odbiorca', 'globkurier')) ?></th>
			<th style="min-width: 100px"><?php
				echo esc_attr(__('Zawartość', 'globkurier')) ?></th>
			<th style="min-width: 100px"><?php
				echo esc_attr(__('Waga [kg]', 'globkurier')) ?></th>
			<th style="min-width: 150px"><?php
				echo esc_attr(__('Przewoźnik', 'globkurier')) ?></th>
			<th style="min-width: 100px"><?php
				echo esc_attr(__('Pobranie [zł]', 'globkurier')) ?></th>
			<th style="min-width: 150px"><?php
				echo esc_attr(__('Płatność', 'globkurier')) ?></th>
			<th style="min-width: 100px"><?php
				echo esc_attr(__('Cena [zł]', 'globkurier')) ?></th>
			<th style="min-width: 100px"><?php
				echo esc_attr(__('Status', 'globkurier')) ?></th>
			<th style="min-width: 100px"><?php
				echo esc_html(__('Nr Zamówienia Woocommerce', 'globkurier')) ?></th>
			<th style="min-width: 50px"><?php
				echo esc_attr(__('Opcja', 'globkurier')) ?></th>
		</tr>
		</thead>
		<tbody></tbody>
		<tfoot>
		<tr>
			<td colspan="13">

				<input id="udi-check-all" type="checkbox">
				<label for="udi-check-all"><?php
					echo esc_attr(__('Zaznacz wszystkie', 'globkurier')) ?></label>
				<br>
				<div class="glogburier-orders-bulk-actions-container">

					<p class="glogburier-orders-bulk-actions-header"><?php
						echo esc_attr(__('Masowe działania dla', 'globkurier')) ?>
						<span id="bulk-actions-counter">0</span> <?php
						echo esc_attr(__('zaznaczonych pozycji', 'globkurier')) ?>
					</p>

					<div class="globkurier-orders-bulk-actions">
						<select id="bulk-print-format">
							<option value="A4"><?php
								echo esc_attr(__('Generuj list zbiorczy (format A4)', 'globkurier')) ?></option>
							<option value="ZEBRA_PRINTER"><?php
								echo esc_attr(__('Generuj list zbiorczy (format ZEBRA)', 'globkurier')) ?></option>
						</select>

						<button type="button" class="button globkurier-bulk-action"><?php
							echo esc_attr(__('Wykonaj', 'globkurier')) ?></button>
					</div>

				</div>

			</td>
		</tr>
		</tfoot>
	</table>
</div>

<script>
	
	( function ( $ ) {
		$( function () {
			
			function checkItem( data, type, full ) {
				let isActive = full.hasLabel || false;
				let hash = full.hash;
				if ( isActive ) {
					return '<input type="checkbox" class="globkurier-order-bulk-check" value="' + hash + '" title="<?php echo esc_attr(__('Zaznacz', 'globkurier')) ?>">';
				} else {
					return '';
				}
			}
			
			function getStatus( data, type, full ) {
				let orderNumer = full.number;
				let html = '';
				
				html += '<div style=" display: flex; flex-direction: column; max-width: 100px; text-align: center;">';
				html += '<div class="udi-loader"></div>\n<span class="udi-status-value"></span><button type="button" data-number="' + orderNumer + '" title="<?php echo esc_attr(__('Pobierz status zamówienia',
					'globkurier')) ?>" class="udi-get-current-status"><?php echo esc_attr(__('Pobierz', 'globkurier')) ?></button>';
				html += '</div>';
				
				return html;
				
			}
			
			function getOrderLink( data, type, full ) {
				let orderLink = full.wc_order_link;
				let wcOrderId = full.wc_order_id;
				
				if ( orderLink ) {
					return '<a style="text-decoration: none" href="' + orderLink + '" target="_blank">#' + wcOrderId + '</a>'
				}
				
				return '-';
			}
			
			function actionLinks( data, type, full ) {
				let hasLabel = full.hasLabel || false;
				
				let orderLabelUrl = full.order_label_url || '#';
				let orderTrackUrl = full.order_track_url || '#';
				
				let getLabelStyle = 'text-decoration: none;';
				let getLabelIconStyle = '';
				let getLabelIconTitle = '<?php echo esc_attr(__('Pobierz list przewozowy', 'globkurier')) ?>';
				
				let getTrackStyle = 'text-decoration: none;';
				
				if ( !hasLabel ) {
					getLabelIconStyle += 'opacity: .2;';
					getLabelStyle += 'pointer-events: none; cursor: default;';
					getLabelIconTitle = '<?php echo esc_attr(__('List przewozowy jest niedostępny', 'globkurier')) ?>';
					orderLabelUrl = '#';
				}
				let html = '<td>';
				html += '<div style="display: flex">';
				
				html += '<div title="' + getLabelIconTitle + '"><a style="' + getLabelStyle + '" href="' + orderLabelUrl + '" target="_blank" >\n\t\t<span class="dashicons dashicons-media-document" style="' + getLabelIconStyle + '"></span>\n\t</a></div>'
				html += '<div title="<?php echo esc_attr(__('Śledź przesyłkę',
					'globkurier')) ?>"><a style="' + getTrackStyle + '" href="' + orderTrackUrl + '" target="_blank" >\n\t\t<span class="dashicons dashicons-search"></span>\n\t</a></div>'
				
				html += '</div>';
				html += '</td>';
				
				return html;
			}

            let _language = '';
            $.getJSON('<?= UDIGroup_GLOBKURIER_PLUGIN_DIR_URL ?>/udigroup_globkurier_admin/lang/datatables/pl.json', function(language) {
                _language = language;
            });

			$( '.udi-is-ajax-datatable' )
				.DataTable( {
					'language': _language,
					'stateSave': true,
					'processing': true,
					'serverSide': true,
					'ajax': ajaxurl + "?action=globkurierGetOrders&nonce=<?php echo esc_attr(wp_create_nonce('globkurier_get_orders_nonce')); ?>",
					
					'bSort': false,
					
					'columns': [
						{ "render": checkItem },
						{ 'data': "number" },
						{ 'data': "date" },
						{ 'data': "receiver_address_name" },
						{ 'data': "content" },
						{ 'data': "shipment_weight" },
						{ 'data': "carrier_name" },
						{ 'data': "cod_value" },
						{ 'data': "payment_name" },
						{ 'data': "price_net" },
						{ 'render': getStatus },
						{ 'render': getOrderLink },
						{ 'render': actionLinks },
					],
					
				} )
				.on( 'page.dt', function () {
					$( '.globkurier-order-bulk-check' ).prop( 'checked', false ).change();
				} );
			
			$( '#udi-check-all' ).change( function () {
				if ( $( this ).is( ':checked' ) ) {
					$( '.globkurier-order-bulk-check' ).prop( 'checked', true ).change();
				}
			} );
			
			$( document ).on( 'change', '.globkurier-order-bulk-check', function () {
				let counter = $( '.globkurier-order-bulk-check:checked' ).length;
				$( '#bulk-actions-counter' ).html( counter );
			} );
			
			$( '.globkurier-bulk-action' ).click( function () {
				let counter = $( '.globkurier-order-bulk-check:checked' ).length;
				
				if ( counter == 0 ) {
					return;
				}
				let button = $( this );
				button.attr( 'disabled', true );
				
				let orderHashes = $( '.globkurier-order-bulk-check:checked' ).map( function ( idx, elem ) {
					return $( this ).val();
				} ).get();
				
				let format = $( '#bulk-print-format' ).val();
				
				window.open( '?page=globkurier_all_orders&getPdfLabels=1&hashes=' + orderHashes, '_blank' );
				return;
				
				let ajaxData = {
					action: 'globkurierGetLabels',
					data: {
						orderHashes: orderHashes,
						format: format,
                        nonce: '<?php echo esc_attr(wp_create_nonce('globkurier_get_labels_nonce')) ?>'
					}
				};
				
				let loader = $( this ).parent().find( '.udi-loader' );
				
				loader.show();
				
				let statusText = $( this ).parent().find( '.udi-status-value' );
				statusText.text( '' );
				
				$.post( data[ 'ajaxUrl' ], ajaxData, function ( response ) {
					
					let url = $.parseJSON( response );
					window.open( url, '_blank' );
					
					button.attr( 'disabled', false );
					
				} );
				
			} );
			
			
		} );
	} )( jQuery );

</script>

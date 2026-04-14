<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

global $wpdb;
global $globKurier;


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
foreach( $s_orders as $order ){
	$data = maybe_unserialize( maybe_unserialize( $order[ 'meta_value' ] ) );
	
	$data[ 'wcOrderId' ] = $order[ 'post_id' ];
	
	$orders[] = $data;
}

usort( $orders, function( $item1, $item2 ){
	return $item2[ 'date' ] <=> $item1[ 'date' ];
} );

?>

<h1><?php echo esc_attr(__( 'Lista protokołów GlobKurier', 'globkurier' )) ?></h1>

<div class="globkurier-old-orders-table-wrapper">
	<table class="globkurier-old-orders-table stripe udi-is-datatable">
		<thead>
		<tr>
			<th></th>
			<th style="min-width: 150px"><?php echo esc_attr(__( 'Nr zamówienia GK', 'globkurier' )) ?></th>
			<th style="min-width: 100px"><?php echo esc_attr(__( 'Data nadania', 'globkurier' )) ?></th>
			<th style="min-width: 100px"><?php echo esc_attr(__( 'Odbiorca', 'globkurier' )) ?></th>
			<th style="min-width: 100px"><?php echo esc_attr(__( 'Zawartość', 'globkurier' )) ?></th>
			<th style="min-width: 100px"><?php echo esc_attr(__( 'Waga [kg]', 'globkurier' )) ?></th>
			<th style="min-width: 150px"><?php echo esc_attr(__( 'Przewoźnik', 'globkurier' )) ?></th>
			<th style="min-width: 100px"><?php echo esc_attr(__( 'Pobranie [zł]', 'globkurier' )) ?></th>
			<th style="min-width: 150px"><?php echo esc_attr(__( 'Płatność', 'globkurier' )) ?></th>
			<th style="min-width: 100px"><?php echo esc_attr(__( 'Cena [zł]', 'globkurier' )) ?></th>
			<th style="min-width: 100px"><?php echo esc_attr(__( 'Status', 'globkurier' )) ?></th>
			<th style="min-width: 100px"><?php echo esc_html(__( 'Nr Zamówienia Woocommerce', 'globkurier' )) ?></th>
			<th style="min-width: 50px"><?php echo esc_attr(__( 'Opcja', 'globkurier' )) ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
		
		foreach( $orders as $order ){
			$wcOrder = wc_get_order( $order[ 'wcOrderId' ] );
			?>
			<tr>
				<td>
					<input type="checkbox" class="globkurier-order-bulk-check" value="<?php echo esc_attr($order[ 'hash' ]) ?>" title="<?php echo esc_attr(__( 'Zaznacz', 'globkurier' )) ?>">
				</td>
				<td><?php echo esc_attr($order[ 'number' ] ?? '') ?></td>
				<td><?php echo esc_attr(date( 'd.m.Y H:i', $order[ 'date' ] ) ?? '') ?></td>
				<td><?php echo esc_attr($order[ 'data' ][ 'receiverAddress' ][ 'name' ] ?? '' )?></td>
				<td><?php echo esc_attr($order[ 'data' ][ 'content' ] ?? '') ?></td>
				<td><?php echo esc_attr($order[ 'data' ][ 'shipment' ][ 'weight' ] ?? '') ?></td>
				<td><?php echo esc_attr($order[ 'carrier' ][ 'name' ] ?? '') ?></td>
				<td><?php echo esc_attr($order[ 'cod' ][ 'value' ] ?? '-') ?></td>
				<td><?php echo esc_attr($order[ 'payment_name' ] ?? '') ?></td>
				<td><?php echo esc_attr($order[ 'price' ][ 'net' ] ?? '') ?>zł</td>
				<td>
					<button type="button" data-number="<?php echo esc_attr($order[ 'number' ]) ?>" title="<?php echo esc_attr(__( 'Pobierz status zamówienia', 'globkurier' )) ?>" class="udi-get-current-status"><?php echo esc_attr(__( 'Pobierz', 'globkurier' )) ?></button>
					<div class="udi-loader"></div>
					<span class="udi-status-value"></span>
				</td>
				<td>
					<?php
					
					if( $wcOrder ){
						?>
						<a style="text-decoration: none" href="<?php echo esc_attr($wcOrder->get_edit_order_url()) ?>" target="_blank">#<?php echo esc_attr($wcOrder->get_order_number()) ?></a>
						<?php
					} else{
						echo '-';
					}
					
					?>
				</td>
				<td>
					<a style="text-decoration: none" href="<?php echo  esc_attr($globKurier->api()->getOrderProtocolPdfUrl( $order[ 'hash' ] ?? '' )) ?>" target="_blank" title="<?php echo esc_attr(__( 'Pobierz list przewozowy', 'globkurier' )) ?>">
						<span class="dashicons dashicons-media-document"></span>
					</a>
					<a style="text-decoration: none" href="<?php echo esc_attr($globKurier->api()->getOrderTrackUrl( $order[ 'number' ] )) ?>" target="_blank" title="<?php echo esc_attr(__( 'Śledź przesyłkę', 'globkurier' )) ?>">
						<span class="dashicons dashicons-search"></span>
					</a>
				</td>
			</tr>
			<?php
		}
		?>
		</tbody>
		<tfoot>
		<tr>
			<td colspan="13">
				
				<input id="udi-check-all" type="checkbox">
				<label for="udi-check-all"><?php echo esc_attr(__( 'Zaznacz wszystkie', 'globkurier' )) ?></label>
				<br>
				<div class="glogburier-orders-bulk-actions-container">
					
					<p class="glogburier-orders-bulk-actions-header"><?php echo esc_attr(__( 'Masowe działania dla', 'globkurier' )) ?>
						<span id="bulk-actions-counter">0</span> <?php echo esc_attr(__( 'zaznaczonych pozycji', 'globkurier' )) ?>
					</p>
					
					<div class="globkurier-orders-bulk-actions">
						<select id="bulk-print-format">
							<option value="1"><?php echo esc_attr(__( 'Pobranie protokołu', 'globkurier' )) ?></option>
						</select>
						
						<button type="button" class="button globkurier-bulk-action"><?php echo esc_attr(__( 'Wykonaj', 'globkurier' )) ?></button>
					
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
			
			$( '.udi-is-datatable' ).on( 'page.dt', function () {
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
				
				window.open( '?page=globkurier_protocols&getPdfProtocols=1&hashes=' + orderHashes, '_blank' );
				button.attr( 'disabled', false );

				return;
				
				let ajaxData = {
					action: 'globkurierGetProtocols',
					data: {
						orderHashes: orderHashes,
                        nonce: '<?php echo esc_attr(wp_create_nonce('globkurier_get_protocols_nonce')); ?>'
					}
				};
				
				$.post( data[ 'ajaxUrl' ], ajaxData, function ( response ) {
					if ( response.success == false ) {
						alert( response.data.toString() );
						button.show();
						button.attr( 'disabled', false );
					} else {
						let text = response.data.pop().name;
						statusText.text( text );
					}
					
					loader.hide();
				} ).fail( function () {
					alert( 'error' );
				} );
				
			} );
		} );
	} )( jQuery );

</script>

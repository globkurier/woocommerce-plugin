<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * @var int $orderId
 **/

if($orderId) {
	$order = wc_get_orders(
		[
			'post__in' => [$orderId],
			'limit'    => 1,
			'status'   => 'any'
		]
	)[ 0 ];
}
$mataName  = apply_filters( 'globkurier_wc_order_meta_name', 'globkurier_orders' );
$oldOrders = $order
	? array_reverse( $order->get_meta( $mataName, FALSE ) )
	: get_post_meta( $orderId,  $mataName, FALSE );

?>

<div class="globkurier-old-orders-table-wrapper">
	<table class="globkurier-old-orders-table">
		<thead>
			<tr>
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
				<th style="min-width: 50px"><?php echo esc_attr(__( 'Opcja', 'globkurier' )) ?></th>
			</tr>
		</thead>
		
		<tbody>
		<?php
		global $globKurier;
		
		foreach( $oldOrders ?? [] as $order ){
			$order = maybe_unserialize( $order->value ?? $order );
			?>
			<tr>
				<td><?php echo esc_attr($order[ 'number' ] ?? '') ?></td>
				<td><?php echo esc_attr(date( 'd.m.Y H:i', $order[ 'date' ] ) ?? '' )?></td>
				<td><?php echo esc_attr($order[ 'data' ][ 'receiverAddress' ][ 'name' ] ?? '') ?></td>
				<td><?php echo esc_attr($order[ 'data' ][ 'content' ] ?? '') ?></td>
				<td><?php echo esc_attr($order[ 'data' ][ 'shipment' ][ 'weight' ] ?? '' )?></td>
				<td><?php echo esc_attr($order[ 'carrier' ][ 'name' ] ?? '' )?></td>
				<td><?php echo esc_attr($order[ 'cod' ][ 'value' ] ?? '-') ?></td>
				<td><?php echo esc_attr($order[ 'payment_name' ] ?? '' )?></td>
				<td><?php echo esc_attr($order[ 'price' ][ 'net' ] ?? '' )?>zł</td>
				<td>
					<button type="button" data-number="<?php echo esc_attr($order['number'] )?>" class="udi-get-current-status"><?php echo esc_attr(__( 'Pobierz', 'globkurier' ) )?></button>
					<div class="udi-loader"></div>
					<span class="udi-status-value"></span>
				</td>
				<td>
					<a style="text-decoration: none" href="<?php echo esc_attr($globKurier->api()->getOrderLabelPdfUrl( $order[ 'hash' ] ) )?>" target="_blank" title="<?php echo esc_attr(__( 'Pobierz list przewozowy', 'globkurier' ) )?>">
						<span class="dashicons dashicons-media-document"></span>
					</a>
					<a style="text-decoration: none" href="<?php echo esc_attr($globKurier->api()->getOrderTrackUrl( $order[ 'number' ] ) )?>" target="_blank" title="<?php echo esc_attr(__( 'Śledź przesyłkę', 'globkurier' ) )?>">
						<span class="dashicons dashicons-search"></span>
					</a>
				</td>
			</tr>
			<?php
		}
		?>
		</tbody>
	
	</table>
</div>

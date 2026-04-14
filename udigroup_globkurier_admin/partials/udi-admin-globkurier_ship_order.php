<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<script>
	( function ( $ ) {
		$( function () {
			$( '#wpwrap' ).css( 'background-color', 'white' );
		} );
	} )( jQuery );
</script>

<div id="globkurier_ship_new_order" style="">
	<?php
	require_once( \udigroup_globkurier\UDIGroup_Helper::getAdminPath( 'woocommerce/metaBox/order.php' ) );
	?>
</div>
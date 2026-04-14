<?php

namespace udigroup_globkurier;

class UDIGroup_Uninstaller{
	
	public static function uninstall(){
		
		delete_option( 'globkurier' );

		global $wpdb;
		
		$mataName = apply_filters( 'globkurier_wc_order_meta_name', 'globkurier_orders' );
		$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => $mataName ] );
		
		delete_option( 'udigroup_gkInPostPoints' );
		delete_option( 'udigroup_gkRuchPoints' );
	}

}
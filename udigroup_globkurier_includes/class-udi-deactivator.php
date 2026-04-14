<?php
namespace udigroup_globkurier;

class UDIGroup_Deactivator {
	
	public static function deactivate(){
		self::removeCronJobs();
	}
	
	private static function removeCronJobs(){
		$timestamp = wp_next_scheduled( 'updateInpostPoints' );
		wp_unschedule_event( $timestamp, 'updateInpostPoints' );
	}
}
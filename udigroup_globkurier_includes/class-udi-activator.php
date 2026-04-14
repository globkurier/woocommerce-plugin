<?php

namespace udigroup_globkurier;

class UDIGroup_Activator{
	public static function activate(){
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	}
}
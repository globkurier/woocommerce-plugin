<?php
/**
 * @wordpress-plugin
 * Plugin Name:       globkurier.pl – Integracja z WooCommerce
 * Description:       Integracja WooCommerce z globkurier.pl
 * Version:           2.5.4
 * Author:            WP OPIEKA
 * Author URI:        https://wp-opieka.pl/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Requires PHP:      7.4.0
 * Text Domain: globkurier
 * Domain Path: globkurier_languages
 */

namespace udigroup_globkurier;

if( ! defined( 'WPINC' ) ){
	die;
}

class UDIGroup_GLOBKURIER_INIT{
	
	public static function getHelperDir(){
		
		return UDIGroup_HELPER_GLOBKURIER_DIR;
	}
	
	public static function getPluginName(){
		
		return UDIGroup_GLOBKURIER_NAME;
	}
	
	public function __construct(){
		$pluginConstPrefix    = 'UDIGLOBKURIER';
		$directoryConstPrefix = 'udigroup_globkurier_';
		
		define( 'UDIGroup_GLOBKURIER_DIRECTORY_PREFIX', $directoryConstPrefix );
		define( 'UDIGroup_GLOBKURIER_NAME', 'udigroup-globkurier' );
		define( 'UDIGroup_GLOBKURIER_TEXTDOMAIN', 'globkurier' );
		
		define( 'UDIGroup_GLOBKURIER_PLUGIN_DIR_URL', plugin_dir_url(__FILE__) );
		define( 'UDIGroup_GLOBKURIER_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__) );
		
		define( 'UDIGroup_HELPER_GLOBKURIER_DIR', plugin_dir_path( __FILE__ ) . UDIGroup_GLOBKURIER_DIRECTORY_PREFIX . 'includes/class-udi-helper.php' );
		
		define( 'UDIGroup_GLOBKURIER_VERSION', '2.5.4' );
		
		global $wpdb;
		define( 'UDIGroup_GLOBKURIER_DB_PREFIX', $wpdb->prefix . 'globkurier_' );
		
		register_activation_hook( __FILE__, [ $this, 'activate_udi' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate_udi' ] );
		
		require plugin_dir_path( __FILE__ ) . UDIGroup_GLOBKURIER_DIRECTORY_PREFIX . 'includes/class-udi.php';
		
		add_action( 'init', [ $this, 'load_plugin_textdomain' ] );
		
		$timestamp = wp_next_scheduled( 'updateInpostPoints' );
		wp_unschedule_event( $timestamp, 'updateInpostPoints' );
	
		$timestamp2 = wp_next_scheduled( 'updateInpostPoints2' );
		wp_unschedule_event( $timestamp2, 'updateInpostPoints2' );
		
		add_filter( 'plugin_row_meta', [ $this, 'prefix_append_support_and_faq_links' ], 10, 4 );
		
		$plugin = new UDIGroup_plugin();
		$plugin->run();
		
		if (is_admin()) {
			require_once plugin_dir_path(__FILE__) . 'bulk_send/BulkSend.php';
		}
		
	}
	
	function prefix_append_support_and_faq_links( $links_array, $plugin_file_name, $plugin_data, $status ){
		
		global $globKurier;
		
		if( strpos( $plugin_file_name, basename( __FILE__ ) ) ){
			$links_array[] = '<a href="' . $globKurier->getSettingsUrl() . '">' . __( 'Ustawienia', 'globkurier' ) . '</a>';
		}
		
		return $links_array;
	}
	
	public function activate_udi(){
		require_once UDIGroup_Helper::getInlcudesPath( 'class-udi-activator.php' );
		UDIGroup_Activator::activate();
	}
	
	public function deactivate_udi(){
		require_once UDIGroup_Helper::getInlcudesPath( 'class-udi-deactivator.php' );
		UDIGroup_Deactivator::deactivate();
	}
	
	public function uninstall_udi(){
		require_once UDIGroup_Helper::getInlcudesPath( 'class-udi-uninstaller.php' );
		UDIGroup_Uninstaller::uninstall();
	}
	
	public function load_plugin_textdomain(){
		load_plugin_textdomain( UDIGroup_GLOBKURIER_TEXTDOMAIN, false, UDIGroup_GLOBKURIER_NAME . '/' . UDIGroup_GLOBKURIER_DIRECTORY_PREFIX . 'languages' );
	}
}

new UDIGroup_GLOBKURIER_INIT();
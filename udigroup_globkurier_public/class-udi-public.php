<?php

namespace udigroup_globkurier;

class UDIGroup_Public{
	
	private $plugin_name;
	
	private $version;
	
	public function __construct( $plugin_name, $version ){
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}
	
	public function do_output_buffer(){
		ob_start();
	}
	
	public function enqueue_styles(){
		
		global $globKurier;

		if(!$globKurier->isAnyPickupPointActive()){
			return;
		}
		
		wp_enqueue_style( $this->plugin_name, UDIGroup_Helper::getPublicUrl( 'css/udi-public.css' ), [], $this->version, 'all' );
	}
	
	public function register_styles(){
		wp_register_style( $this->plugin_name, UDIGroup_Helper::getPublicUrl( 'css/udi-public.css' ), [], $this->version, 'all' );
	}
	
	public function enqueue_scripts(){
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		
		global $globKurier;
		
		$googleMapApiKey = $globKurier->settings('googleMapApiKey');
		
		if(!empty($googleMapApiKey) && $globKurier->isAnyPickupPointActive()){
			wp_enqueue_script( $this->plugin_name.'_map', 'https://maps.googleapis.com/maps/api/js?key='.$googleMapApiKey, [], $this->version, 'all');
		}

		$data = [
			'ajaxUrl' => UDIGroup_Helper::getAjaxUrl(),
            'globkurier_get_inpost_points_nonce'   => wp_create_nonce( 'globkurier_get_inpost_points_nonce' ),
            'globkurier_get_inpost_points_select2_nonce'   => wp_create_nonce( 'globkurier_get_inpost_points_select2_nonce' ),
            'globkurier_get_ruch_points_nonce'   => wp_create_nonce( 'globkurier_get_ruch_points_nonce' ),
            'globkurier_save_ruch_points_session_nonce'   => wp_create_nonce( 'globkurier_save_ruch_points_session_nonce' ),
            'globkurier_save_inpost_points_session_nonce'   => wp_create_nonce( 'globkurier_save_inpost_points_session_nonce' ),
		];
		
		if(!$globKurier->isAnyPickupPointActive()){
			return;
		}
		
		wp_enqueue_script( $this->plugin_name, UDIGroup_Helper::getPublicUrl( 'js/udi-public.js' ), [], $this->version, 'all' );
		wp_localize_script( $this->plugin_name, 'data', $data );
	}
	
	public function register_scripts(){
		wp_register_script( $this->plugin_name, UDIGroup_Helper::getPublicUrl( 'js/udi-public.js' ), [], $this->version, 'all' );
	}
	
	public function flush_rules(){
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}
	
	public function insert_rewrite_rules( $rules ){
		$newrules = [];
		return $newrules + $rules;
	}
	
	public function insert_query_vars( $vars ){
		return $vars;
	}
	
}
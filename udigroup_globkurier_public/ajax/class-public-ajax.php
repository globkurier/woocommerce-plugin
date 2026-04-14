<?php

namespace udigroup_globkurier;

class UDIGroup_Public_Ajax
{
	public function __construct()
	{
	}
	
	public function init()
	{
		$this->priv();
		$this->noPriv();
	}
	
	private function priv()
	{
		$actionPrefix = 'wp_ajax_';
		add_action($actionPrefix.'globkurierGetInpostPoints', [$this, 'getInpostPoints']);
		add_action($actionPrefix.'globkurierGetInpostPointsSelect2', [$this, 'getInpostPointsSelect2']);
		add_action($actionPrefix.'globkurierGetAllInpostPointsSelect2', [$this, 'getInpostAllPointsSelect2']);
		
		add_action($actionPrefix.'globkurierGetRuchPoints', [$this, 'getRuchPoints']);
		add_action($actionPrefix.'globkurierGetRuchPointsSelect2', [$this, 'getRuchPointsSelect2']);
		add_action($actionPrefix.'globkurierGetAllRuchPointsSelect2', [$this, 'getRuchAllPointsSelect2']);
		
		add_action($actionPrefix.'globkurierSaveInpostPointsSession', [$this, 'saveInpostPointsSession']);
		add_action($actionPrefix.'globkurierSaveRuchPointsSession', [$this, 'saveRuchPointsSession']);
		
		add_action($actionPrefix.'globkurierGetPointsSession', [$this, 'globkurierGetPointsSession']);
		
		add_action($actionPrefix.'globkurierSaveInpostPointsDisableInitSession', [$this, 'saveInpostPointsDisableInitSession']);
		add_action($actionPrefix.'globkurierSaveRuchPointsDisableInitSession', [$this, 'saveRuchPointsDisableInitSession']);
		
		add_action($actionPrefix.'globkurierGetExtraPickupsPointsSelect2', [$this, 'getExtraPickupPointsSelect2']);
		add_action($actionPrefix.'globkurierSaveExtraPickupsPointsSession', [$this, 'saveExtraPickupsPointsSession']);
	}
	
	private function noPriv()
	{
		$actionPrefix = 'wp_ajax_nopriv_';
		add_action($actionPrefix.'globkurierGetInpostPoints', [$this, 'getInpostPoints']);
		add_action($actionPrefix.'globkurierGetInpostPointsSelect2', [$this, 'getInpostPointsSelect2']);
		add_action($actionPrefix.'globkurierGetAllInpostPointsSelect2', [$this, 'getInpostAllPointsSelect2']);
		
		add_action($actionPrefix.'globkurierGetRuchPoints', [$this, 'getRuchPoints']);
		add_action($actionPrefix.'globkurierGetRuchPointsSelect2', [$this, 'getRuchPointsSelect2']);
		add_action($actionPrefix.'globkurierGetAllRuchPointsSelect2', [$this, 'getRuchAllPointsSelect2']);
		
		add_action($actionPrefix.'globkurierSaveInpostPointsSession', [$this, 'saveInpostPointsSession']);
		add_action($actionPrefix.'globkurierSaveRuchPointsSession', [$this, 'saveRuchPointsSession']);
		
		add_action($actionPrefix.'globkurierGetPointsSession', [$this, 'globkurierGetPointsSession']);
		
		add_action($actionPrefix.'globkurierGetExtraPickupsPointsSelect2', [$this, 'getExtraPickupPointsSelect2']);
		add_action($actionPrefix.'globkurierSaveExtraPickupsPointsSession', [$this, 'saveExtraPickupsPointsSession']);
	}
	
	public function getInpostPoints()
	{
		$nonce = sanitize_text_field($_POST[ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_get_inpost_points_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		
		global $globKurier;
		
		$data = array_map('sanitize_text_field', $_POST[ 'data' ]);
		
		$points = $globKurier->inpost()->getPoints($data);
		
		die(json_encode($points));
	}
	
	public function getInpostPointsSelect2()
	{
		global $globKurier;
		
		$city = sanitize_text_field($_GET[ 'city' ] ?? $_POST[ 'city' ]);
		
		$data = [
			'city' => $city,
			'countryId' => $countryId = sanitize_text_field($_GET[ 'countryId' ] ?? $_POST[ 'countryId' ] ?? '1'),
		];
		
		$points = $globKurier->inpost()->getPointsSelect2($data);
		
		die(json_encode($points));
	}
	
	public function getRuchPoints()
	{
		$nonce = sanitize_text_field($_POST[ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_get_ruch_points_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		
		global $globKurier;
		
		$data = array_map('sanitize_text_field', $_POST[ 'data' ]);
		
		$points = $globKurier->ruch()->getPoints($data);
		
		die(json_encode($points));
	}
	
	public function getRuchPointsSelect2()
	{
		global $globKurier;
		
		$city = sanitize_text_field($_GET[ 'city' ] ?? $_POST[ 'city' ] ?? '');
		
		$product_id = sanitize_text_field($_GET[ 'product_id' ] ?? $_POST[ 'product_id' ] ?? '');
		
		$data = ['city' => $city, 'product_id' => $product_id];
		
		$points = $globKurier->ruch()->getPointsSelect2($data);
		
		die(json_encode($points));
	}
	
	public function getRuchAllPointsSelect2()
	{
		$nonce = sanitize_text_field($_POST[ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_get_ruch_all_points_select2_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		
		global $globKurier;
		
		$points = $globKurier->ruch()->getAllPoints();
		
		die(json_encode($points));
	}
	
	public function getInpostAllPointsSelect2()
	{
		$nonce = sanitize_text_field($_POST[ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_get_inpost_all_points_select2_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		global $globKurier;
		
		$points = $globKurier->inpost()->getAllPoints();
		
		die(json_encode($points));
	}
	
	public function saveRuchPointsSession()
	{
		$nonce = sanitize_text_field($_POST[ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_save_ruch_points_session_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		
		WC()->session->set('globkurier_ruch_selected_point_id', wc_clean($_POST[ 'id' ]));
		WC()->session->set('globkurier_ruch_selected_point_value', wc_clean($_POST[ 'value' ]));
		WC()->session->set('globkurier_ruch_selected_point_latitude', wc_clean($_POST[ 'latitude' ]));
		WC()->session->set('globkurier_ruch_selected_point_longitude', wc_clean($_POST[ 'longitude' ]));
		WC()->session->set('globkurier_ruch_selected_point_city', wc_clean($_POST[ 'city' ]));
		WC()->session->set('globkurier_ruch_selected_point_address', wc_clean($_POST[ 'address' ]));
		WC()->session->set('globkurier_ruch_selected_point_openingHours', wc_clean($_POST[ 'openingHours' ]));
	}
	
	public function saveInpostPointsSession()
	{
		$nonce = sanitize_text_field($_POST[ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_save_inpost_points_session_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		
		WC()->session->set('globkurier_inpost_selected_point_id', wc_clean($_POST[ 'id' ]));
		WC()->session->set('globkurier_inpost_selected_point_value', wc_clean($_POST[ 'value' ]));
		WC()->session->set('globkurier_inpost_selected_point_latitude', wc_clean($_POST[ 'latitude' ]));
		WC()->session->set('globkurier_inpost_selected_point_longitude', wc_clean($_POST[ 'longitude' ]));
		WC()->session->set('globkurier_inpost_selected_point_city', wc_clean($_POST[ 'city' ]));
		WC()->session->set('globkurier_inpost_selected_point_address', wc_clean($_POST[ 'address' ]));
		WC()->session->set('globkurier_inpost_selected_point_openingHours', wc_clean($_POST[ 'openingHours' ]));
	}
	
	public function saveInpostPointsDisableInitSession()
	{
		$nonce = sanitize_text_field($_POST[ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_save_inpost_points_disableinit_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		
		WC()->session->set('globkurier_disable_inpost_init', wc_clean($_POST[ 'status' ]));
	}
	
	public function saveRuchPointsDisableInitSession()
	{
		$nonce = sanitize_text_field($_POST[ 'nonce' ]);
		if (! wp_verify_nonce($nonce, 'globkurier_save_ruch_points_disableinit_nonce')) {
			wp_send_json_error('Invalid nonce');
		}
		
		WC()->session->set('globkurier_disable_ruch_init', wc_clean($_POST[ 'status' ]));
		WC()->session->set('globkurier_old_ruch_params', wc_clean($_POST[ 'params' ]));
	}
	
	public function globkurierGetPointsSession()
	{
		wp_send_json_success([]);
	}
	
	public function getExtraPickupPointsSelect2()
	{
		global $globKurier;
		
		$city = sanitize_text_field($_GET[ 'city' ] ?? $_POST[ 'city' ]);
		
		$data = ['city' => $city];
		
		$points = $globKurier->extraPickupPoints()->getPointsSelect2($data);
		
		die(json_encode($points));
	}
	
	public function saveExtraPickupsPointsSession()
	{
		$carrierId = (int)$_POST[ 'carrierId' ];
		
		WC()->session->set('globkurier_'.$carrierId.'_selected_point_id', wc_clean($_POST[ 'id' ]));
		WC()->session->set('globkurier_'.$carrierId.'_selected_point_value', wc_clean($_POST[ 'value' ]));
		WC()->session->set('globkurier_'.$carrierId.'_selected_point_latitude', wc_clean($_POST[ 'latitude' ]));
		WC()->session->set('globkurier_'.$carrierId.'_selected_point_longitude', wc_clean($_POST[ 'longitude' ]));
		WC()->session->set('globkurier_'.$carrierId.'_selected_point_city', wc_clean($_POST[ 'city' ]));
		WC()->session->set('globkurier_'.$carrierId.'_selected_point_address', wc_clean($_POST[ 'address' ]));
		WC()->session->set('globkurier_'.$carrierId.'_selected_point_openingHours', wc_clean($_POST[ 'openingHours' ]));
	}
	
}
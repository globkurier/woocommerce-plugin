<?php

namespace udigroup_globkurier;

class UDIGroup_i18n{
	
	public function load_plugin_textdomain(){
		load_plugin_textdomain( UDIGroup_GLOBKURIER_INIT::getPluginName(), false, dirname( UDIGroup_Helper::getLanguagePath() ) );
	}
}
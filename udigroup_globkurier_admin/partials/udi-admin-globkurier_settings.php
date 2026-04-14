<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly


global $globKurier;

wp_redirect( $globKurier->getSettingsUrl());
wp_die();
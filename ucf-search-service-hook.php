<?php
/*
Plugin Name: UCF Search Service Hook
Description: Provides a hook for writing data back to the UCF Search Service
Version: 0.0.0
Author: UCF Web Communications
License: GPL3
Git Plugin URI: https://github.com/UCF/UCF-Search-Service-Hook
*/

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'UCF_SEARCH_SERVICE__PLUGIN_FILE', __FILE__ );

include_once 'admin/ucf-search-service-config.php';
include_once 'includes/ucf-search-service-common.php';

if ( ! function_exists( 'ucf_search_service_plugin_activation' ) ) {
	function ucf_search_service_plugin_activation() {
		UCF_Search_Service_Config::add_options();
	}

	register_activation_hook( UCF_SEARCH_SERVICE__PLUGIN_FILE, 'ucf_search_service_plugin_activation' );
}

if ( ! function_exists( 'ucf_search_service_plugin_deactivation' ) ) {
	function ucf_search_service_plugin_deactivation() {
		UCF_Search_Service_Config::delete_options();
	}

	register_deactivation_hook( UCF_SEARCH_SERVICE__PLUGIN_FILE, 'ucf_search_service_plugin_deactivation' );

}

if ( ! function_exists( 'ucf_search_service_init' ) ) {
	function ucf_search_service_init() {
		add_action( 'save_post', array( 'UCF_Search_Service_Common', 'on_save_post' ), 99, 1 );
	}

	add_action( 'plugins_loaded', 'ucf_search_service_init' );
}

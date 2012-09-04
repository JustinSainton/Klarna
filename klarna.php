<?php
/**
  * Plugin Name: Klarna for WPeC
  * Plugin URI: https://klarna.com
  * Description: Klarna Gateway Integration with WP E-Commerce
  * Version: 1.1
  * Author: Zao
  * Author URI: http://zaowebdesign.com/
  * Sputnik ID: klarna-payment-gateway
  **/

	function load_klarna_gateways() {
		global $wpsc_cart;

		if ( ! defined( 'KLARNA_FILE_PATH' ) )
			define( 'KLARNA_FILE_PATH', dirname( __FILE__ ) );

		if ( ! defined( 'KLARNA_URL' ) )
			define( 'KLARNA_URL'      , plugins_url( '', __FILE__ ) );

		if ( ! is_object( $wpsc_cart ) )
			wpsc_core_setup_cart();
		
		include_once 'klarna-invoice.merchant.php';
		include_once 'klarna-partpayment.merchant.php';
		include_once 'klarna-specialcampaigns.merchant.php';

		add_filter( 'wpsc_merchants_modules', 'klarna_add_gateways' );
	}

	function klarna_add_gateways( $gateways ) {
		global $klarna_gateways, $nzshpcrt_gateways;

		$nzshpcrt_gateways = array_merge( $klarna_gateways, $gateways );

		return $nzshpcrt_gateways;
	}

	function klarna_sputnik_report_error() {
		echo '<div class="error"><p>' . __( 'Please install &amp; activate Renku to enable Klarna.', 'klarna' ) . '</p></div>';
	}

	function klarna_sputnik_verify() {
		remove_action( 'all_admin_notices', 'klarna_sputnik_report_error' );
		Sputnik::check( __FILE__, 'load_klarna_gateways' );
	}

	add_action( 'wpsc_pre_load'    , 'load_klarna_gateways' );
	add_action( 'sputnik_loaded'   , 'klarna_sputnik_verify' );
	add_action( 'all_admin_notices', 'klarna_sputnik_report_error' );

?>
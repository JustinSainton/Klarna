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
		
		define( 'KLARNA_FILE_PATH', dirname( __FILE__ ) );
		define( 'KLARNA_URL'      , plugins_url( '', __FILE__ ) );

		include_once 'klarna-invoice.merchant.php';
		include_once 'klarna-partpayment.merchant.php';
		include_once 'klarna-specialcampaigns.merchant.php';
	}

	function klarna_sputnik_report_error() {
		echo '<div class="error"><p>' . __( 'Please install &amp; activate Renku to enable Klarna.', 'klarna' ) . '</p></div>';
	}

	function klarna_sputnik_verify() {
		remove_action( 'all_admin_notices', 'klarna_sputnik_report_error' );
		Sputnik::check( __FILE__, 'load_klarna_gateways' );
	}

	add_action( 'wpsc_init'        , 'load_klarna_gateways' );
	add_action( 'sputnik_loaded'   , 'klarna_sputnik_verify' );
	add_action( 'all_admin_notices', 'klarna_sputnik_report_error' );

?>
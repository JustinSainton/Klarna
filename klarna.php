<?php
/**
  * Plugin Name: Klarna for WPeC
  * Plugin URI: https://klarna.com
  * Description: Klarna Gateway Integration with WP E-Commerce
  * Version: 1.2
  * Author: Zao
  * Author URI: http://zaowebdesign.com/
  * Sputnik ID: klarna-payment-gateway
  **/

	function load_klarna_gateways() {
		include_once 'klarna-invoice.merchant.php';
		include_once 'klarna-partpayment.merchant.php';
		include_once 'klarna-specialcampaigns.merchant.php';
	}

	function klarna_sputnik_report_error() {
		echo '<div class="error"><p>Please install &amp; activate Renku to enable YourPlugin.</p></div>';
	}

	function klarna_sputnik_verify() {
		remove_action( 'all_admin_notices', 'klarna_sputnik_report_error' );
		Sputnik::check( __FILE__, 'load_klarna_gateways' );
	}

	add_action( 'wpsc_init', 'load_klarna_gateways' );
	add_action( 'sputnik_loaded', 'verify' );
	add_action( 'all_admin_notices', 'klarna_sputnik_report_error' );

?>
<?php
global $num, $klarna_gateways;

require_once( 'klarna_library/WPKlarna.php');
require_once( 'klarna_library/WPKlarnaHTML.php');
$Klarna = new WPKlarna('spec');

$num = count( $klarna_gateways ) + 1;

$klarna_gateways[$num] = array(
	'name' => 'Klarna Special Campaigns' . $Klarna->updateMessage,
	'api_version' => 2.0,
    'class_name' => 'wpsc_merchant_klarna_spec',
	'display_name' => 'Klarna Special Campaigns',
	'requirements' => array('php_version' => 5.0),
	'form' => 'form_klarna_spec',
	'submit_function' => 'submit_klarna_spec',
	'internalname' => 'wpsc_merchant_klarna_spec'
);

$pclass_var = ( $Klarna->enabled && ! is_admin() ) ? $Klarna->getPClasses( KlarnaPClass::SPECIAL ) : 'set';

if ( ( ! is_admin() && ! in_array( strtolower( get_option( 'base_country' ) ), array( 'se', 'no', 'fi' ) ) ) || ( ! is_admin() && empty( $pclass_var ) ) )
    unset( $klarna_gateways[$num] );

if ( ! is_admin() && 'set' == $pclass_var )
    unset( $klarna_gateways[$num] );

if ( $Klarna->enabled && ! is_admin() ) {
    global $gateway_checkout_form_fields;
    $gateway_checkout_form_fields['wpsc_merchant_klarna_spec'] = $Klarna->getCheckoutForm();
    $payment_gateway_names = (array) get_option('payment_gateway_names');
    $payment_gateway_names['wpsc_merchant_klarna_spec'] = $Klarna->getTitle();
    update_option('payment_gateway_names', $payment_gateway_names);
} 

class wpsc_merchant_klarna_spec extends wpsc_merchant {
    /**
     * The Klarna API
     *
     * @var WPKlarna
     **/
    var $Klarna;
    
    /**
     * Constructor
     *
     * @return void
     * @author Niklas Malmgren
     **/
    public function __construct($purchase_id = null, $is_receiving = false) {
        parent::__construct($purchase_id, $is_receiving);
        $this->Klarna = new WPKlarna('spec');
    }
    
    /**
     * Run when the order is submitted
     *
     * @return void
     * @author Niklas Malmgren
     **/
    public function submit() {
        global $wpdb;
        $result = $this->Klarna->checkoutSubmit(&$this);
        
        if($result === false) {
            $this->Klarna->deleteWPOrder($this->purchase_id);
            $this->return_to_checkout();
            exit();
        }
        
        $this->set_purchase_processed_by_purchid($this->Klarna->getKlarnaOption('order_status'));
        
        switch($result[1]) {
            case KlarnaFlags::ACCEPTED:
                $orderNotes = 'Order is APPROVED by Klarna.';
                break;
            case KlarnaFlags::PENDING:
                $orderNotes = 'Order is PENDING APPROVAL by Klarna. Please visit Klarna Online for the latest status on this order.';
                break;
            case KlarnaFlags::DENIED:
                $orderNotes = 'Order is DENIED by Klarna.';
                break;
            default:
                $orderNotes = 'Unknown response from Klarna.';
                break;
        }
        
        $orderNotes .= "\n";

        $klarnaInvoiceNumber = $result[0];
        $orderNotes .= 'Klarna invoice number: ' . $klarnaInvoiceNumber;
        $wpdb->update(
            WPSC_TABLE_PURCHASE_LOGS,
            array('transactid' => $klarnaInvoiceNumber, 'notes' => $orderNotes),
            array('id' => absint($this->purchase_id))
        );

        $this->go_to_transaction_results($this->cart_data['session_id']);
        exit();
    }
    
}

/**
 * Handles submission of settings form
 *
 * @return void
 * @author Niklas Malmgren
 **/
function submit_klarna_spec() {
    // Make sure that payment option name doesn't get set due to some user tomfoolery
    $payment_gateway_names = (array)get_option('payment_gateway_names');
    $payment_gateway_names['wpsc_merchant_klarna_spec'] = '';
    update_option('payment_gateway_names', $payment_gateway_names);
    
    WPKlarnaHTML::saveSettings('spec');
}

/**
 * Returns the settings forms
 *
 * @return string
 * @author Niklas Malmgren
 **/
function form_klarna_spec() {
    WPKlarnaHTML::setDefaults('spec');
    return WPKlarnaHTML::getSettingsForm('spec');
}

/**
 * Print javscript that forces a page reload when the billing country is changed. This is because
 * we need to know if the Klarna module should be enabled (if shipping == billing).
 *
 * @return void
 * @author Niklas Malmgren
 **/
if(!function_exists('klarnaForceReload')) {
    function klarnaForceReload() {
$str = <<<EOF
<script type='text/javascript'>
    jQuery(document).ready(function (){
        jQuery('select[title="billingcountry"]').bind('change', function(){
            window.setTimeout('window.location = window.location.href', 500);
        });
    });
</script>

EOF;
        print($str);
    }
    add_action('wpsc_bottom_of_shopping_cart', 'klarnaForceReload');
}

add_filter('wpsc_pre_transaction_results', array(&$Klarna, 'getKlarnaInvoiceNumberInfo'));

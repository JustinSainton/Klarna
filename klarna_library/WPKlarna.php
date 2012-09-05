<?php

@include_once(dirname(__FILE__) . '/api_2.0/Klarna.php');
@include_once(dirname(__FILE__) . '/classes/class.KlarnaAPI.php');
@include_once(dirname(__FILE__) . '/classes/class.KlarnaHTTPContext.php');
@include_once(dirname(__FILE__) . '/api_2.0/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
@include_once(dirname(__FILE__) . '/api_2.0/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc');

class WPKlarna extends Klarna {

    // DO NOT CHANGE THIS VALUE UNLESS TOLD TO BY KLARNA
    const ENABLE_KLARNA_ILT = 0;
    
    /**
     * Data on the available countries
     *
     * @var array
     **/
    public $countries;
    
    /**
     * Type of module ("invoice", "part" or "spec")
     *
     * @var string
     **/
    public $moduleType;

    /**
     * Version of the payment module
     *
     * @var string
     **/
    public static $moduleVersion = '2.0.0';
    
    /**
     * The merchant ID
     * 
     * @var int 
     */
    private $eid;

    /**
     * The secret for merchant
     * 
     * @var string
     */
    private $sharedSecret;
    
    /**
     * Invoice fee for the currently active module type and country
     *
     * @var float
     **/
    public $invoiceFee;
    
    /**
     * An instance of the Klarna API
     *
     * @var KlarnaAPI
     **/
    private $API;

    /**
     * Is the module enabled?
     *
     * @var bool
     **/
    public $enabled;

    /**
     * The address object containing the customer data
     *
     * @var array
     **/
    private $addrs;
    
    /**
     * Value of the shopping cart items, shipping fee and invoice fee. Coupon value deducted.
     *
     * @var float
     **/
    private $totalCartValueIncludingTax = 0.0;
    
    /**
     * Price of the currently displayed product
     *
     * @var int
     **/
    private $productPrice = 0;
    
    /**
     * Lowest monthly cost available
     *
     * @var int
     **/
    private $lowestMonthlyCost = null;
    
    /**
     * Description of the first found PClass (used for special campaigns)
     *
     * @var string
     **/
    private $pclassTitle = '';

    /**
     * Has the checkout validation run?
     *
     * @var bool
     **/
    private $validationHasRun = false;

    /**
     * Does the checkout form validate?
     *
     * @var bool
     **/
    private $checkoutFormValidates = false;
    
    /**
     * Checkout form validation errors
     *
     * @var array
     **/
    private $validationErrors = array();

    /**
     * Error messages from API or Klarna Online
     *
     * @var array
     **/
    private $klarnaErrors = array();

    /**
     * ILT questions to display
     *
     * @var array
     **/
    private $iltQuestions = array();
    
    /**
     * Message that an update is available
     *
     * @var string
     **/
    public $updateMessage = '';
    
    /**
     * The current module context ('checkout' or 'product')
     *
     * @var string
     **/
    private $context = '';
    
    /**
     * Number of activated and enabled Klarna payment gateways
     *
     * @var int
     **/
    public static $numberOfActivatedModules = 0;
    
    /**
     * The id of the current payment gateway (1-3)
     *
     * @var int
     **/
    public $moduleID;
    
    /**
     * Class constructor
     *
     * @return void
     * @author Niklas Malmgren
     **/
    public function __construct($moduleType = null, $context = 'checkout', $price = 0) {
        $this->VERSION = 'php:wpecommerce:' . self::$moduleVersion;

        $this->moduleType = $moduleType;
        $this->context = $context;
        $this->productPrice = $price;
        
        $countries = array(
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'FI' => 'Finland',
            'DE' => 'Germany',
            'NL' => 'The Netherlands'
        );
        foreach($countries AS $countryCode => $countryName) {
            $this->countries[] = new WPKlarnaCountry(
                $countryCode,
                $countryName,
                $this->getCountryForCode($countryCode),
                $this->getLanguageForCountry($this->getCountryForCode($countryCode)),
                $this->getCurrencyForCountry($this->getCountryForCode($countryCode)),
                $this->getCurrencyCode($this->getCurrencyForCountry($this->getCountryForCode($countryCode))),
                $this->getLanguageCode($this->getLanguageForCountry($this->getCountryForCode($countryCode)))
            );
        }
        
        $this->enabled = $this->WPInit();
        
        if($this->enabled) {
            $this->moduleID = ++self::$numberOfActivatedModules;
        }

        if( isset( $_POST['custom_gateway'] ) && $_POST['custom_gateway'] == 'wpsc_merchant_klarna_' . $moduleType)
            add_filter('wpsc_checkout_form_validation', array(&$this, 'checkoutValidation'), 10, 1);

        if(
            !$this->validationHasRun &&
            isset($_POST['wpsc_action']) &&
            $_POST['wpsc_action'] == 'submit_checkout' &&
            $_POST['custom_gateway'] == 'wpsc_merchant_klarna_' . $this->moduleType) {
            
            $this->checkoutFormValidates = $this->runCheckoutValidation();
            $this->validationHasRun = true;
        }

        if ( is_admin() && $this->isUpdateAvailable() )
            $this->updateMessage = '&nbsp;&middot;&nbsp;UPDATE AVAILABLE';

        //Proper AJAX Handlers
        add_action( 'wp_ajax_update_klarna_classes'    , array( $this, 'klarna_update_pc_classes' ) );
        add_action( 'wp_ajax_get_klarna_address'       , array( $this, 'klarna_get_address' ) );
        add_action( 'wp_ajax_nopriv_get_klarna_address', array( $this, 'klarna_get_address' ) );
    }

    public static function klarna_update_pc_classes() {
        
        check_ajax_referer( 'klarna-pay-classes', 'no_hacky' );
      
        $error = array();

        $sModuleType = (string) $_POST['module'];

        if ( '' == $sModuleType )
            die( json_encode( array( 'errors' => '<td>Error: no module defined</td>' ) ) );

        $moduleTypes = array( 'part', 'spec' );

        if ( get_option( 'klarna_part_enabled' ) == 'on' )
            $moduleType = 'part';
        elseif ( get_option( 'klarna_spec_enabled' ) == 'on' )
            $moduleType = 'spec';
        else
            die( json_encode( array( 'errors' => '<td>Error: neither part payment module nor special campaigns module enabled</td>' ) ) );

        $enabledPartCountries = explode( ',', get_option( 'klarna_part_enabled_countries' ) );
        $enabledSpecCountries = explode( ',', get_option( 'klarna_spec_enabled_countries' ) );

        foreach ( $enabledPartCountries as $countryCode ) {
            $eid    = get_option( 'klarna_part_eid_' . $countryCode );
            $secret = get_option( 'klarna_part_secret_' . $countryCode );
            if ( $eid && $secret )
                $enabledCountries[$countryCode][] = array( 'eid' => $eid, 'secret' => $secret );
        }

        foreach ( $enabledSpecCountries as $countryCode ) {
            $eid   = get_option( 'klarna_spec_eid_' . $countryCode );
            $secret = get_option( 'klarna_spec_secret_' . $countryCode );
            if ( $eid && $secret ) {
                if ( ! isset( $enabledCountries[$countryCode] ) || $enabledCountries[$countryCode][0]['eid'] != $eid )
                    $enabledCountries[$countryCode][] = array( 'eid' => $eid, 'secret' => $secret );
            }
        }

        $str = '<h3>Klarna PClasses updated</h3>';
        $numFound = 0;

        $mode = ( get_option( 'klarna_' . $moduleType . '_server' ) == 'beta' ? Klarna::BETA : Klarna::LIVE );

        $Klarna = new WPKlarna( $moduleType );

        $str .= '<div style="border: 1px solid #8CC63F; background-color: #D7EBBC; padding: 10px; font-family: Arial, Verdana; font-size: 11px; margin: 10px">';
        $str .= '<pre>';
        $str .= "<b>id  | description                             | months | interest rate | handling fee | start fee | min amount | country</b><br /><hr size='1' style='border-top: 1px solid #8CC63F;'/>";

        foreach ( $enabledCountries as $countryCode => $countryEIDs ) {
            foreach ( $countryEIDs as $countryCredentials ) {
                if ( ! in_array( strtolower( $countryCode ), array( 'se', 'no', 'dk', 'fi', 'de', 'nl' ) ) )
                    continue;
                $eid    = $countryCredentials['eid'];
                $secret = $countryCredentials['secret'];
                
                if ( $eid && $secret ) {
                    $Klarna->config( $eid, $secret, $countryCode, null, null, $mode, 'wp', 'klarnapclasses', ( $mode == Klarna::LIVE ) );
                    try {
                        $Klarna->fetchPClasses($countryCode);
                    } catch ( Exception $e ) {
                        continue;
                    }
                    foreach ( $Klarna->getPClasses() as $pclass ) {
                        $numFound++;
                        $addition  = strlen( utf8_encode( $pclass->getDescription() ) );
                        $addition2 = strlen( html_entity_decode( $pclass->getDescription() ) );
                        $sum       = ( $addition == $addition2 ? 40 : 40 + ( $addition - $addition2 ) );

                        $str .= sprintf( "%-4s|"            , $pclass->getId() );
                        $str .= sprintf( " %-" . $sum . "s|", $pclass->getDescription() );
                        $str .= sprintf( " %-7s|"           , $pclass->getMonths() );
                        $str .= sprintf( " %-14s|"          , $pclass->getInterestRate() );
                        $str .= sprintf( " %-13s|"          , $pclass->getInvoiceFee() );
                        $str .= sprintf( " %-10s|"          , $pclass->getStartFee() );
                        $str .= sprintf( " %-11s|"          , $pclass->getMinAmount() );
                        $str .= sprintf( " %-7s"            ,  '<img src="' . KLARNA_URL . '/klarna_library/images/klarna/images/flags/' . $Klarna->getLanguageCode() . '.png" border="0" title="' . $pclass->getCountry() . '" /> ' );
                        $str .= "<br />";
                    }
                }
            }
        }
        $str .= "</pre></div>";

        $str .= '<p>Found ' . $numFound . ' PClasses </p>';
        
        echo json_encode( array( 'success' => '<td>' . $str . '</td>' ) );
        die;
    }

    public static function klarna_get_address() {
        $aSessionCalls = array();
        
        // Check the session for calls
        if (array_key_exists('address', $_SESSION)) {
            $sSessionCalls  = base64_decode($_SESSION['klarna_get_address']);
            $aSessionCalls  = unserialize($sSessionCalls);
        }
        
        $sPNO = KlarnaHTTPContext::toString('pno');
        $sCountry = strtolower(KlarnaHTTPContext::toString('country'));
        $sType = KlarnaHTTPContext::toString('type');

        
        if (array_key_exists($sPNO, $aSessionCalls)) {
            $addrs  = unserialize($aSessionCalls[$sPNO]);
        } else {
            $sEID       = get_option('klarna_' . $sType . '_eid_' . strtoupper($sCountry));
            $sSecret    = get_option('klarna_' . $sType . '_secret_' . strtoupper($sCountry));
            
            $iMode = (get_option('klarna_' . $sType . '_server') == 'beta' ? Klarna::BETA : Klarna::LIVE);
            
            $klarna = new WPKlarna();
            $klarna->config($sEID, $sSecret, KlarnaCountry::SE, KlarnaLanguage::SV, KlarnaCurrency::SEK, $iMode, 'wp', 'klarnapclasses', false);
            
            try {
                $addrs = $klarna->getAddresses($sPNO, null, KlarnaFlags::GA_GIVEN);
            } catch(Exception $e) {
                $xml = new SimpleXMLElement('<error/>');
                $xml->addChild('type', get_class($e));
                $xml->addChild('message', Klarna::num_htmlentities($e->getMessage()));
                $xml->addChild('code', $e->getCode());
                header("content-type: text/xml; charset=UTF-8");
                echo $xml->asXML();
                exit();
            }
            
            $aSessionCalls[$sPNO] = serialize($addrs);
            $_SESSION['klarna_get_address'] = base64_encode(serialize($aSessionCalls));
        }
        $sString  = "<?xml version='1.0'?>\n";
        $sString .= "<getAddress>\n";
        
        header("content-type: text/xml; charset=UTF-8");
        
        //This example only works for GA_GIVEN.
        foreach($addrs as $index => $addr) {
            if($addr->isCompany) {
                $implode = array(
                    'companyName' => utf8_encode($addr->getCompanyName()),
                    'street' => utf8_encode($addr->getStreet()),
                    'zip' => utf8_encode($addr->getZipCode()),
                    'city' => utf8_encode($addr->getCity()),
                    'countryCode' => utf8_encode($addr->getCountryCode())
                );
            }
            else {
                $implode = array(
                    'first_name' => utf8_encode($addr->getFirstName()),
                    'last_name' => utf8_encode($addr->getLastName()),
                    'street' => utf8_encode($addr->getStreet()),
                    'zip' => utf8_encode($addr->getZipCode()),
                    'city' => utf8_encode($addr->getCity()),
                    'countryCode' => utf8_encode($addr->getCountryCode())
                );
            }
            
            $sString .= "<address>\n";
            
            foreach($implode as $key => $val) {
                $sString    .= "<".$key.">" . $val . "</".$key.">\n"; 
            }
            
            $sString .= "</address>\n";
        }
        
        $sString .= "</getAddress>";
        
        die($sString);
    }
    
    /**
     * Checks to see if the payment module should be enabled. Also sets a bunch of values.
     *
     * @return bool
     * @author Niklas Malmgren
     **/
    public function WPInit() {
        global $wpsc_cart;

        if ( is_admin() )
            return true;
        
        if($this->moduleType == null || $this->moduleType == '')
            return false;
        
        if($this->getKlarnaOption('enabled') != 'on')
            return false;

        if(!$this->setCountryCurrency())
            return false;

        $enabledCountries = explode(',', $this->getKlarnaOption('enabled_countries'));
        if(!in_array(strtoupper($this->getCountryCode()), $enabledCountries))
            return false;
        
        $this->eid = (int)$this->getKlarnaOption('eid', true);
        $this->secret = $this->getKlarnaOption('secret', true);
        
        if(empty($this->eid) || empty($this->secret))
            return false;
        
        $this->invoiceFee = (float)$this->getKlarnaOption('fee', true);

        $mode = ($this->getKlarnaOption('server') == 'beta' ? Klarna::BETA : Klarna::LIVE);

        $this->totalCartValueIncludingTax = wpsc_cart_total( false );

        $this->config($this->eid, $this->secret, $this->getCountryCode(), null, null, $mode, 'wp', 'klarnapclasses', ($mode == Klarna::LIVE));
        
        switch($this->moduleType) {
            case 'part':
                $pclassTypes = array(KlarnaPClass::CAMPAIGN, KlarnaPClass::ACCOUNT, KlarnaPClass::FIXED);
                break;
            case 'spec':
                $pclassTypes = array(KlarnaPClass::SPECIAL);
                break;
            default:
                $pclassTypes = null;
                break;
        }
        
        if($this->context == 'product') {
            $contextFlag = KlarnaFlags::PRODUCT_PAGE;
            $sum = $this->productPrice;
        } else {
            $contextFlag = KlarnaFlags::CHECKOUT_PAGE;
            $sum = $this->totalCartValueIncludingTax;
        }
        
        if ( self::ENABLE_KLARNA_ILT != 1 && $this->getCountry() == KlarnaCountry::NL && $sum > 250 )
            return false;

        $this->API = new KlarnaAPI(
            $this->getCountryCode(),    // country
            null,                       // language (automatically set based on country)
            $this->moduleType,          // module type
            $sum,                       // total sum (cart or product)
            $contextFlag,               // KlarnaFlags::PRODUCT_PAGE or KlarnaFlags::CHECKOUT_PAGE
            $this,                      // Klarna object
            $pclassTypes,               // PClass types to fetch
            KLARNA_FILE_PATH . '/klarna_library/');

        $this->API->addMultipleSetupValues(array(
            'eid' => $this->eid,
            'web_root' => WPSC_URL,
            'sum' => $this->invoiceFee,
            'path_js' => KLARNA_URL . '/klarna_library/js/',
            'path_img' => KLARNA_URL . '/klarna_library/images/klarna/',
            'path_css' => KLARNA_URL . '/klarna_library/css/',
            'path_ajax' => KLARNA_URL . '/klarna_library/',
            'ysal_display' => ($this->askForYearlySalary() ? 'block' : 'none'),
            'agb_link' => $this->getKlarnaOption('agb_url')
        ));        

        if($this->moduleType != 'invoice' && $this->context == 'checkout') {
            if($this->moduleType == 'part') {
                $bestPClass = $this->getCheapestPClass($this->totalCartValueIncludingTax, KlarnaFlags::CHECKOUT_PAGE);
                if(!$bestPClass)
                    return false;

                $this->lowestMonthlyCost = KlarnaCalc::calc_monthly_cost($this->totalCartValueIncludingTax, $bestPClass, KlarnaFlags::CHECKOUT_PAGE);
                $this->pclassTitle = $bestPClass->getDescription();
            } else {
                $pclasses = $this->API->aPClasses;
                if(empty($pclasses))
                    return false;
                $pclass = array_shift($pclasses);
                $this->pclassTitle = $pclass['pclass']->getDescription();
            }
        }

        return true;
    }
    
    /**
     * Returns the title to display in the checkout form
     *
     * @return string
     * @author Niklas Malmgren
     **/
    public function getTitle() {
        switch($this->moduleType) {
            case 'invoice':
                return str_replace('(+XX)', "(+".wpsc_currency_display($this->invoiceFee).")", $this->fetchFromLanguagePack('INVOICE_TITLE'));
            case 'part':
                if($this->lowestMonthlyCost != null)
                    return str_replace('xx', wpsc_currency_display($this->lowestMonthlyCost), $this->fetchFromLanguagePack('PARTPAY_TITLE'));
                else
                    return $this->fetchFromLanguagePack('MODULE_PARTPAY_TEXT_TITLE');
            case 'spec':
                if($this->pclassTitle != '')
                    return $this->pclassTitle;
                else
                    return $this->fetchFromLanguagePack('SPEC_TITLE');
        }
    }
    
    /**
     * Tries to guess the customer's country based on information from WP E-commerce
     *
     * @return bool
     * @author Niklas Malmgren
     **/
    public static function getCustomerCountry() {
        global $user_ID, $wpdb;

        // First, check if customer has changed country in the checkout form
        if(isset($_POST['country']) && $_POST['country'])
            return $_POST['country'];
        elseif(isset($_SESSION['delivery_country']) && $_SESSION['delivery_country'])
            return $_SESSION['delivery_country'];
        elseif(isset($_SESSION['wpsc_delivery_country']) && $_SESSION['wpsc_delivery_country'])
            return $_SESSION['wpsc_delivery_country'];
        else {

            // Try to get the shipping country set in the WP user profile
            $shippingCountryID = $wpdb->get_var("SELECT `" . WPSC_TABLE_CHECKOUT_FORMS . "`.`id` FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `unique_name` = 'shippingcountry' AND active = '1' "); 
            if($shippingCountryID && ($arr = get_user_meta($user_ID, 'wpshpcrt_usr_profile'))) {
                $arr = $arr[0];
                if(isset($arr[$shippingCountryID]) && $arr[$shippingCountryID]) {
                    if(is_array($arr[$shippingCountryID]))
                        return $arr[$shippingCountryID][0];
                    else
                        return $arr[$shippingCountryID];
                }
            }

            // Use the first language (or country) in the Accept-Language header
            $matches = array();
            $languages = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? strtolower( trim( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) : 'sv-SV,en;q=0.8';
            preg_match('/^(?P<lang>[a-zA-Z]{2})(-(?P<country>[a-zA-Z]{2}))?([,; ].*)?/', $languages, $matches);
            if(isset($matches['lang'])) {
                if(isset($matches['country'])) {
                    return $matches['country'];
                }
                switch($matches['lang']) {
                    case 'no':
                    case 'nb':
                    case 'nn':
                        return 'no';
                    case 'sv':
                        return 'se';
                    case 'da':
                        return 'dk';
                    case 'de':
                        return 'de';
                    case 'nl':
                        return 'nl';
                    case 'fi':
                        return 'fi';
                    default:
                }
                
            }
            // Give up
            return false;
        }
    }
    
    /**
     * Tries to set the customer's country and currency
     *
     * @param  string|int $country  {@link KlarnaCountry}
     * @param  string|int $currency {@link KlarnaCurrency}
     * @return bool
     * @author Niklas Malmgren
     **/
    public function setCountryCurrency($country = null, $currency = null) {
        global $wpdb;

        if($country == null) {
            $country = self::getCustomerCountry();
            if(!$country)
                return false;
        }
        if($currency == null)
            $currency = $wpdb->get_var("SELECT `code` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id` = '" . absint(get_option('currency_type')) . "'");
        
        if(!$this->checkCountryCurrency($country, $currency))
            return false;

        $this->setCountry($country);
        $this->setCurrency($currency);

        return true;
    }
    

    /**
     * Get an array with mappings for the standard register templates
     *
     * @return array
     * @author Niklas Malmgren
     **/
    public function getParams() {
        $params = array(
            "invoice_type" => "klarna_" . $this->moduleType . "_invoice_type",
            "companyName" => "klarna_" . $this->moduleType . "_companyName",
            "socialNumber" => "klarna_" . $this->moduleType . "_socialNumber",
            "sex" => "klarna_" . $this->moduleType . "_gender",
            "firstName" => "klarna_" . $this->moduleType . "_firstName",
            "lastName" => "klarna_" . $this->moduleType . "_lastName",
            "street" => "klarna_" . $this->moduleType . "_street",
            "homenumber" => "klarna_" . $this->moduleType . "_homenumber",
            "house_extension" => "klarna_" . $this->moduleType . "_house_extension",
            "zipcode" => "klarna_" . $this->moduleType . "_zipcode",
            "city" => "klarna_" . $this->moduleType . "_city",
            "phoneNumber" => "klarna_" . $this->moduleType . "_phoneNumber",
            "mobilePhone" => "klarna_" . $this->moduleType . "_mobilePhone",
            "emailAddress" => "klarna_" . $this->moduleType . "_emailAddress",
            "invoiceType" => "klarna_" . $this->moduleType . "_invoiceType",
            "reference" => "klarna_" . $this->moduleType . "_reference",
            "year_salary" => "klarna_" . $this->moduleType . "_year_salary",
            "shipmentAddressInput_invoice" => "klarna_" . $this->moduleType . "_shipmentAddressInput_invoice",
            "birthday_day" => "klarna_" . $this->moduleType . "_birth_day",
            "birthday_month" => "klarna_" . $this->moduleType . "_birth_month",
            "birthday_year" => "klarna_" . $this->moduleType . "_birth_year",
            "consent" => "klarna_" . $this->moduleType . "_consent",
            "paymentPlan" => "klarna_" . $this->moduleType . "_paymentPlan"
        );
        return $params;
    }

    /**
     * Returns the Klarna payment form
     *
     * @return string
     * @author Niklas Malmgren
     **/
    public function getCheckoutForm() {
        global $wpdb, $wpsc_cart, $current_user, $cart_data;

        get_currentuserinfo();
        $wpsc_checkout = new wpsc_checkout();

        $params = $this->getParams();
        
        $addressPrefix = ( isset( $_POST['shippingSameBilling'] ) && 'true' == $_POST['shippingSameBilling'] )  ? 'billing' : 'shipping';

        $values = array();

        // Get the values from the WPEC forms
        foreach($wpsc_checkout->checkout_items as $formData) {
            $klarnaValue = '';
            switch($formData->unique_name) {
                case $addressPrefix . 'firstname':
                    $klarnaValue = 'firstName';
                    break;
                case $addressPrefix . 'lastname':
                    $klarnaValue = 'lastName';
                    break;
                case $addressPrefix . 'address':
                    $klarnaValue = 'street';
                    break;
                case $addressPrefix . 'city':
                    $klarnaValue = 'city';
                    break;
                case $addressPrefix . 'postcode':
                    $klarnaValue = 'zipcode';
                    break;
                case 'billingphone':
                    $klarnaValue = array('phoneNumber', 'mobilePhone');
                    break;
                default:
                    break;
            }
            if ( $klarnaValue && isset( $_SESSION['wpsc_checkout_saved_values'] ) ) {
                if ( is_array( $klarnaValue ) ) {
                    foreach ( $klarnaValue as $value ) {
                        $values[$value] = $_SESSION['wpsc_checkout_saved_values'][$formData->id];
                    }
                } else {
                    $values[$klarnaValue] = $_SESSION['wpsc_checkout_saved_values'][$formData->id];
                }
            }
        }
        
        if ($this->getCountryCode() == 'de' || $this->getCountryCode() == 'nl') {
            $addressMatches = array();
            preg_match('/(?P<street>.*?) (?P<houseno>[0-9]+.*?)( (?P<houseext>[^ ]+))?$/', $values['street'], $addressMatches);
            if(isset($addressMatches['street'])) {
                $values['street'] = $addressMatches['street'];
                if(isset($addressMatches['houseno']))
                    $values['homenumber'] = $addressMatches['houseno'];
                if(isset($addressMatches['houseext'])) {
                    if($this->getCountryCode() == 'nl')
                        $values['house_extension'] = $addressMatches['houseext'];
                    else
                        $values['homenumber'] .= ' ' . $addressMatches['houseext'];
                }
            }
        }

        
        // Get values from previously submitted Klarna form
        $fields = array('gender', 'socialNumber', 'firstName', 'lastName', 'street', 'homenumber', 'zipcode',
            'city', 'phoneNumber', 'mobilePhone', 'emailAddress', 'year_salary', 'invoiceType', 'companyName',
            'house_extension', 'shipmentAddressInput_invoice', 'reference', 'paymentplan', 'birth_day',
            'birth_month', 'birth_year', 'consent', 'paymentPlan');
        foreach($fields AS $field) {
            $fullFieldName = 'klarna_' . $this->moduleType . '_' . $field;
            if(isset($_POST[$fullFieldName]))
                $values[$field] = (trim((string)$_POST[$fullFieldName]));
        }
        
        if(isset($_SESSION[$wpsc_cart->unique_id]['klarnaCustomerInfo']))
            $values = array_merge($values, $_SESSION[$wpsc_cart->unique_id]['klarnaCustomerInfo']);

        if ( self::ENABLE_KLARNA_ILT == 1 && is_array($this->iltQuestions) && count($this->iltQuestions) >= 1)
            $this->API->setIltQuestions($this->iltQuestions);
        
        // Some things should only be output once (i.e. in the first activated module)
        if($this->moduleID == 1) {
            $balloons = $this->getBalloons();
            $this->API->addSetupValue('threatmetrix', $this->checkoutHTML());
        } else {
            $balloons = '';
        }   

        return $balloons . $this->API->retrieveHTML($params, $values, null, array('name' => 'default'));
    }
    
    /**
     * Get the HTML code for the standard register balloons
     *
     * @return string
     * @author Niklas Malmgren
     **/
    public function getBalloons() {
        $str = <<<EOF
<div class="klarna_baloon" id="klarna_baloon" style="display: none">
    <div class="klarna_baloon_top"></div>
    <div class="klarna_baloon_middle" id="klarna_baloon_content">
        <div></div>
    </div>
    <div class="klarna_baloon_bottom"></div>
</div>
<div class="klarna_red_baloon" id="klarna_red_baloon" style="display: none">
    <div class="klarna_red_baloon_top"></div>
    <div class="klarna_red_baloon_middle" id="klarna_red_baloon_content">
        <div></div>
    </div>
    <div class="klarna_red_baloon_bottom"></div>
</div>
<div class="klarna_blue_baloon" id="klarna_blue_baloon" style="display: none">
    <div class="klarna_blue_baloon_top"></div>
    <div class="klarna_blue_baloon_middle" id="klarna_blue_baloon_content">
        <div></div>
    </div>
    <div class="klarna_blue_baloon_bottom"></div>
</div>

EOF;
        return $str;
    }
    
    /**
     * Run by WP e-Commerce to validate the checkout form
     *
     * @param  array $states
     * @return array
     * @author Niklas Malmgren
     **/
    public function checkoutValidation($states) {
        if(!$states['is_valid'])
            return $states;

        if(!$this->validationHasRun) {
            $this->checkoutFormValidates = $this->runCheckoutValidation();
            $this->validationHasRun = true;
        }

        if(!$this->checkoutFormValidates) {
            $states['is_valid'] = 0;

            if(!empty($this->klarnaErrors)) {
                $_SESSION['wpsc_checkout_misc_error_messages'][] = '<div style="border:1px solid #7BA7C9;padding:10px;">
                <img src="' . KLARNA_URL . '/klarna_library/images/klarna/images/logo/klarna_logo.png" /><br />' .
                '<ul style="list-style: square inside none ! important;"><li>' .
                implode('</li><li>', $this->klarnaErrors).'</li></ul></div>';
            } else {
                $_SESSION['wpsc_checkout_misc_error_messages'][] = '<div style="border:1px solid #7BA7C9;padding:10px;">
                <img src="' . KLARNA_URL . '/klarna_library/images/klarna/images/logo/klarna_logo.png" /><br />' .
                $this->fetchFromLanguagePack('error_title_1') .
                '<br/><ul style="list-style: square inside none ! important;"><li>' .
                implode('</li><li>', $this->validationErrors).'</li></ul><br/>' .
                $this->fetchFromLanguagePack('error_title_2') . '</div>';
            }
        }

        return $states;
    }
    
    /**
     * Validates the checkout form
     *
     * @return bool
     * @author Niklas Malmgren
     **/
    public function runCheckoutValidation() {
        global $wpdb, $wpsc_cart;
        
        $fields = array('gender', 'socialNumber', 'firstName', 'lastName', 'street', 'homenumber', 'zipcode',
            'city', 'phoneNumber', 'mobilePhone', 'emailAddress', 'year_salary', 'invoiceType', 'invoice_type', 'companyName',
            'house_extension', 'shipmentAddressInput_invoice', 'reference', 'paymentplan', 'birth_day',
            'birth_month', 'birth_year', 'consent', 'paymentPlan');
        $klarnaCustomerInfo = array();
        foreach($fields AS $field) {
            $klarnaCustomerInfo[$field] =
                (isset($_POST['klarna_' . $this->moduleType . '_' . $field]) ?
                (trim((string)$_POST['klarna_' . $this->moduleType . '_' . $field])) : '');
        }
        $_SESSION[$wpsc_cart->unique_id]['klarnaCustomerInfo'] = $klarnaCustomerInfo;
        
        $valid = true;
        $errors = array();
        
        $emailAddressID = $wpdb->get_var("SELECT `" . WPSC_TABLE_CHECKOUT_FORMS . "`.`id` FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `unique_name` = 'billingemail' AND active = '1' ");
        if(isset($_POST['collected_data'][$emailAddressID]) && $_POST['collected_data'][$emailAddressID])
            $klarnaCustomerInfo['emailAddress'] = $_POST['collected_data'][$emailAddressID];
        else
            $klarnaCustomerInfo['emailAddress'] = $_SESSION['wpsc_checkout_saved_values'][$emailAddressID];

        $klarnaCustomerInfo['phone'] = ($klarnaCustomerInfo['phoneNumber'] ? $klarnaCustomerInfo['phoneNumber'] : $klarnaCustomerInfo['mobilePhone']);

        if($klarnaCustomerInfo['invoiceType'] == '') {
            $klarnaCustomerInfo['invoiceType'] = $klarnaCustomerInfo['invoice_type'];
        }

        if(strlen($klarnaCustomerInfo['phone']) == 0)
            $errors[] = $this->fetchFromLanguagePack('phone_number');

        if($this->askForYearlySalary()) {
            $klarnaCustomerInfo['year_salary'] = preg_replace('/[\D]/', '', $klarnaCustomerInfo['year_salary']);
            if(!ctype_digit($klarnaCustomerInfo['year_salary']))
                $errors[] = $this->fetchFromLanguagePack('year_salary');
        }

        if($this->getCountryCode() == 'se') {
            $pno_enc = $this->getPNOEncoding();
            if ( ! KlarnaEncoding::checkPNO( $klarnaCustomerInfo['socialNumber'], $pno_enc ) )
                $errors[] = $this->fetchFromLanguagePack( 'klarna_personalOrOrganisatio_number' );
            if ( strlen( $klarnaCustomerInfo['emailAddress'] ) == 0 )
                $errors[] = $this->fetchFromLanguagePack('email_address');

            $addrs = array();

            if(empty($errors)) {
                try {
                    $addrs = $this->getAddresses($klarnaCustomerInfo['socialNumber'], null, KlarnaFlags::GA_GIVEN);

                    if(count($addrs) == 0)
                        $errors[] = $this->fetchFromLanguagePack('error_no_address');
                    elseif(count($addrs) == 1)
                        $this->addrs = $addrs[0];
                    else {
                        //This example only works for GA_GIVEN.
                        foreach($addrs as $index => $addr) {
                            $addr_string = "";

                            if($addr->isCompany) {
                                $addr_string  = $addr->getCompanyName();
                                $addr_string .= "|" . $addr->getStreet();
                                $addr_string .= "|" . $addr->getZipCode();
                                $addr_string .= "|" . $addr->getCity();
                                $addr_string .= "|" . $addr->getCountryCode();
                            } else {
                                $addr_string  = $addr->getFirstName() .  " " . $addr->getLastName();
                                $addr_string .= "|" . $addr->getStreet();
                                $addr_string .= "|" . $addr->getZipCode();
                                $addr_string .= "|" . $addr->getCity();
                                $addr_string .= "|" . $addr->getCountryCode();
                            }
                            if(utf8_encode($addr_string) == $klarnaCustomerInfo['shipmentAddressInput_invoice'] ||
                            $addr_string == $klarnaCustomerInfo['shipmentAddressInput_invoice']) {
                                $this->addrs = $addr;
                            }
                        }
                    }
                    if($klarnaCustomerInfo['invoiceType'] == 'company') {
                        if(!$addr->isCompany)
                            throw new KlarnaException('Invoice type mismatch.', 59999);
                    }
                } catch(Exception $e) {
                    $this->klarnaErrors[] = utf8_encode($e->getMessage()) . ' (#' . utf8_encode($e->getCode()) . ')';
                    $valid = false;
                }

                if($this->addrs == null)
                    $errors[] = $this->fetchFromLanguagePack('error_no_address', $country);
            }

            if(!empty($errors)) {
                $this->validationErrors = $errors;
                $valid = false;
            } else {
                try {
                    $this->addrs->setTelno($klarnaCustomerInfo['phone']);
                    $this->addrs->setEmail($klarnaCustomerInfo['emailAddress']);
                } catch( Exception $e ) {
                    // Do nothing, ignore it.
                }
            }
        } else {
            if ($klarnaCustomerInfo['invoiceType'] != 'company') {
                if (strlen($klarnaCustomerInfo['firstName']) == 0)
                    $errors[] = $this->fetchFromLanguagePack('first_name');
                if (strlen($klarnaCustomerInfo['lastName']) == 0)
                    $errors[] = $this->fetchFromLanguagePack('last_name');
            }
            
            if ($this->getCountryCode() == "de" || $this->getCountryCode() == "nl") {
                $klarnaCustomerInfo['socialNumber'] = $klarnaCustomerInfo['birth_day'] .
                    $klarnaCustomerInfo['birth_month'] .
                    $klarnaCustomerInfo['birth_year'];
                
                if ($klarnaCustomerInfo['gender'] != '0' && $klarnaCustomerInfo['gender'] != '1')
                    $errors[] = $this->fetchFromLanguagePack('sex');
                if (strlen($klarnaCustomerInfo['socialNumber']) == 0)
                    $errors[] = $this->fetchFromLanguagePack('klarna_personalOrOrganisatio_number');
                if (strlen($klarnaCustomerInfo['street']) == 0)
                    $errors[] = $this->fetchFromLanguagePack('street_adress');
                if ($this->getCountryCode() == 'de' && $klarnaCustomerInfo['consent'] != 'on')
                    $errors[] = $this->fetchFromLanguagePack('no_consent');
            } elseif($this->getCountryCode() == 'dk' ||
                $this->getCountryCode() == 'fi' ||
                $this->getCountryCode() == "no") {

                if ($klarnaCustomerInfo['invoiceType'] == 'company') {
                    if (strlen($klarnaCustomerInfo['companyName']) == 0)
                        $errors[] = $this->fetchFromLanguagePack('invoice_type_company');

                    if (strlen($klarnaCustomerInfo['reference']) == 0)
                        $errors[] = $this->fetchFromLanguagePack('reference');

                    $klarnaCustomerInfo['firstName'] = $klarnaCustomerInfo['companyName'];
                    $klarnaCustomerInfo['lastName'] = '(Ref. ' . $klarnaCustomerInfo['reference'] . ')';
                } else {
                    $pno_enc = $this->getPNOEncoding();
                    if(!KlarnaEncoding::checkPNO($klarnaCustomerInfo['socialNumber'], $pno_enc))
                        $errors[] = $this->fetchFromLanguagePack('klarna_personalOrOrganisatio_number');
                }

                if (strlen($klarnaCustomerInfo['street']) == 0)
                    $errors[] = $this->fetchFromLanguagePack('street_adress');
            }

            if (strlen($klarnaCustomerInfo['zipcode']) == 0) {
                $errors[] = $this->fetchFromLanguagePack('address_zip');
            }
            
            if (strlen($klarnaCustomerInfo['city']) == 0) {
                $errors[] = $this->fetchFromLanguagePack('address_city');
            }
            
            if (!preg_match(
                KlarnaEncoding::getRegexp(KlarnaEncoding::EMAIL),
                $klarnaCustomerInfo['emailAddress'])) {
                $errors[] = $this->fetchFromLanguagePack('klarna_email');
            }

            if (!empty($errors)) {
                $this->validationErrors = $errors;
                $valid = false;
            } else {
                try {
                    $this->addrs = new KlarnaAddr(
                        utf8_decode($klarnaCustomerInfo['emailAddress']),
                        utf8_decode($klarnaCustomerInfo['phoneNumber']),
                        utf8_decode($klarnaCustomerInfo['mobilePhone']), 
                        utf8_decode($klarnaCustomerInfo['firstName']),
                        utf8_decode($klarnaCustomerInfo['lastName']),
                        '',
                        utf8_decode($klarnaCustomerInfo['street']),
                        utf8_decode($klarnaCustomerInfo['zipcode']),
                        utf8_decode($klarnaCustomerInfo['city']),
                        $this->getCountryCode(),
                        utf8_decode($klarnaCustomerInfo['homenumber']),
                        utf8_decode($klarnaCustomerInfo['house_extension'])
                    );
                    
                    if ($klarnaCustomerInfo['invoiceType'] == 'company') {
                        $this->addrs->setCompanyName(utf8_decode($klarnaCustomerInfo['companyName']));
                    }
                } catch(Exception $e) {
                    $this->klarnaErrors[] = utf8_encode($e->getMessage()) . ' (#' . utf8_encode($e->getCode()) . ')';
                    $valid = false;
                }
            }
        }

        $checkoutFormFields = array();
        $wpsc_checkout = new wpsc_checkout();
        foreach($wpsc_checkout->checkout_items AS $formData) {
            if ( isset( $_POST['collected_data'][$formData->id] ) && is_array( $_POST['collected_data'][$formData->id] ) )
                $value = $_POST['collected_data'][$formData->id][0];
            else
                $value = isset( $_POST['collected_data'][$formData->id] ) ? $_POST['collected_data'][$formData->id] : '';
            
            if ( is_string( $value ) )
                $value = utf8_decode($value);

            $checkoutFormFields[$formData->unique_name] = $value;
        }

        if(
            $valid == true &&
            ($this->addrs->getCountry() == KlarnaCountry::NL || $this->addrs->getCountry() == KlarnaCountry::DE))
        {
            if(
                $this->addrs->getFirstName() != $checkoutFormFields['billingfirstname'] ||
                $this->addrs->getLastName() != $checkoutFormFields['billinglastname'] ||
                trim($this->addrs->getStreet() . ' ' . $this->addrs->getHouseNumber() . ' ' . $this->addrs->getHouseExt()) != $checkoutFormFields['billingaddress'] ||
                $this->addrs->getCity() != $checkoutFormFields['billingcity'] ||
                $this->addrs->getZipCode() != $checkoutFormFields['billingpostcode'] ||
                $this->addrs->getCountryCode() != strtolower($checkoutFormFields['billingcountry']))
            {
    
                $this->validationErrors[] = $this->fetchFromLanguagePack('error_shipping_must_match_billing');
                $valid = false;
            }
        }


        $this->iltQuestions = array();
        $klarnaCustomerInfo['ilt'] = array();
        if($valid == true && self::ENABLE_KLARNA_ILT == 1) {
            $this->setAddress(KlarnaFlags::IS_SHIPPING, $this->addrs);
            try {
                $iltQuestions = array();
                $iltQuestions = $this->checkILT(
                    (int)$this->totalCartValueIncludingTax,
                    $klarnaCustomerInfo['socialNumber'],
                    ($this->getCountry() == KlarnaCountry::DE || $this->getCountry() == KlarnaCountry::NL) ? $klarnaCustomerInfo['gender'] : null);
    
                if(is_array($iltQuestions) && count($iltQuestions) >= 1) {
                    foreach($iltQuestions AS $questionID => $question) {
                        $klarnaField = 'klarna_' . $questionID . '_' . $this->moduleType;
                        if(!isset($_POST[$klarnaField]) || !$_POST[$klarnaField]) {
                            $valid = false;
                        } else {
                            $klarnaCustomerInfo['ilt'][$questionID] = $_POST[$klarnaField];
                        }
                        $this->iltQuestions[$klarnaField] = $question;
                    }
                }
                if($valid == false) {
                    $this->validationErrors = array($this->fetchFromLanguagePack('ilt_title'));
                }
            } catch(Exception $e) {
                $valid = false;
                $this->validationErrors = array('Fatal error encountered when try to fetch ILT questions.');
            }
        }
        
        $_SESSION[$wpsc_cart->unique_id]['klarnaAddressObject'] = $this->addrs;
        $_SESSION[$wpsc_cart->unique_id]['klarnaCustomerInfo'] = $klarnaCustomerInfo;
        
        return $valid;
    }
    
    /**
     * Handles the checkout and submits the information to Klarna
     *
     * @param  wpsc_merchant &$Merchant
     * @return array
     * @author Niklas Malmgren
     **/
    public function checkoutSubmit(&$Merchant) {
        global $wpdb, $wpsc_cart, $current_user, $cart_data;

        $this->addrs = $_SESSION[$wpsc_cart->unique_id]['klarnaAddressObject'];
        $klarnaCustomerInfo = $_SESSION[$wpsc_cart->unique_id]['klarnaCustomerInfo'];

        get_currentuserinfo();

        $wpsc_checkout = new wpsc_checkout();

        $Taxes = new wpec_taxes_controller();
        $taxCountry = $wpsc_cart->delivery_country;
        $taxRate = $Taxes->wpec_taxes->wpec_taxes_get_rate(
            $taxCountry,
            $Taxes->wpec_taxes_retrieve_region());
        if(!isset($taxRate['rate']))
            $taxRate['rate'] = 0;
        
        $klarnaPno = $klarnaCustomerInfo['socialNumber'];

        if($klarnaCustomerInfo['invoiceType'] == 'company') {
            $this->setReference(utf8_decode($klarnaCustomerInfo['reference']), '');
            $this->setComment(utf8_decode($klarnaCustomerInfo['reference']));
        }
        
        $shipmentCost = 0;

        // Add all cart items
        foreach($Merchant->cart_items AS $item) {
            extract($Taxes->wpec_taxes_calculate_included_tax($item), EXTR_PREFIX_ALL, 'item');
            if(!isset($item_rate))
                $item_rate = 0;
            if($Taxes->wpec_taxes_isincluded()) {
                $item_price = $item['price'];
            } else {
                $item_price = $item['price'] * ( ($item_rate / 100) + 1);
            }
            if($item['shipping'] > 0)
                $shipmentCost += $item['shipping'];
            $this->addArticle(
                $item['quantity'],
                utf8_decode($item['product_id']),
                utf8_decode(strip_tags($item['name'])),
                $item_price,
                $item_rate,
                0,
                KlarnaFlags::INC_VAT);
        }

        // Add shipping cost        
        if($Merchant->cart_data['base_shipping'] > 0) {
            $shipmentFlags = KlarnaFlags::IS_SHIPMENT;
            $shipmentCost += $Merchant->cart_data['base_shipping'];
            $shipmentTaxRate = 0;
            
            // Check if to apply tax to shipping cost
            if($taxRate['shipping']) {
                if(!$Taxes->wpec_taxes_isincluded())
                    $shipmentCost *= (($taxRate['rate'] / 100) + 1);
                $shipmentTaxRate = $taxRate['rate'];
                $shipmentFlags += KlarnaFlags::INC_VAT;
            }
            $this->addArticle(1, '', __('Total Shipping', 'wpsc'), $shipmentCost, $shipmentTaxRate, 0, $shipmentFlags);
        }
        
        // Add invoice fee
        if($this->invoiceFee > 0) {
            $this->addArticle(
                1,
                '',
                utf8_decode($this->fetchFromLanguagePack('INVOICE_FEE_TITLE')),
                $this->invoiceFee * ($Taxes->wpec_taxes_isincluded() ? 1 : ($taxRate['rate'] / 100) + 1),
                $taxRate['rate'],
                0,
                KlarnaFlags::INC_VAT + KlarnaFlags::IS_HANDLING);

            $wpdb->query($wpdb->prepare(
                "INSERT INTO `".WPSC_TABLE_CART_CONTENTS."` (
                    `prodid`, `name`, `purchaseid`,  `price`, `pnp`,
                     `tax_charged`, `gst`, `quantity`, `donation`,
                     `no_shipping`, `custom_message`, `files`, `meta`
                ) VALUES ('0', '%s', '%d', '%s', '0', '%s', '%s', '1', '0', '0', '', '%s', NULL)",
                $this->fetchFromLanguagePack('INVOICE_FEE_TITLE'),
                $Merchant->purchase_id,
                $this->invoiceFee,
                (float)($Taxes->wpec_taxes_isincluded() ? $Taxes->wpec_taxes_calculate_tax($this->invoiceFee, $taxRate['rate'], false) : 0),
                (float)($Taxes->wpec_taxes_isincluded() ? $taxRate['rate'] : 0),
                serialize(null)
            ));
            $cart_id = $wpdb->get_var("SELECT LAST_INSERT_ID() AS `id` FROM `".WPSC_TABLE_CART_CONTENTS."` LIMIT 1");
            wpsc_update_cartmeta($cart_id, 'sku', null);
            $invoiceFeeFinal = $this->invoiceFee;
            if(!$Taxes->wpec_taxes_isincluded()) {
                $invoiceFeeFinal *= (($taxRate['rate'] / 100) + 1);
                $invoiceFeeTax = $Taxes->wpec_taxes_calculate_tax($invoiceFeeFinal, $taxRate['rate'], false);
                $wpdb->query($wpdb->prepare(
                    "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `totalprice` = `totalprice` + '%d', `wpec_taxes_total` = `wpec_taxes_total` + '%d' WHERE `sessionid` = '%s'",
                    (float)$invoiceFeeFinal,
                    (float)$invoiceFeeTax,
                    $Merchant->cart_data['session_id']
                ));
            } else {
                $wpdb->query($wpdb->prepare(
                    "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `totalprice` = `totalprice` + '%d' WHERE `sessionid` = '%s'",
                    (float)$invoiceFeeFinal,
                    $Merchant->cart_data['session_id']
                ));
            }
        }
        
        // Add discounts
        if($Merchant->cart_data['has_discounts'] && $Merchant->cart_data['cart_discount_value'] > 0) {
            $this->addArticle(
                1,
                '',
                __('Discount', 'wpsc'),
                0 - ($Merchant->cart_data['cart_discount_value'] * ($Taxes->wpec_taxes_isincluded() ? 1 : ($taxRate['rate'] / 100) + 1)),
                $taxRate['rate'],
                0,
                KlarnaFlags::INC_VAT);
        }

        if($this->addrs->getCountry() == KlarnaCountry::NL || $this->addrs->getCountry() == KlarnaCountry::DE) {
            $billingAddress = $this->addrs;
        } else {
            try {
                $billingPhone = '';
                foreach($wpsc_checkout->checkout_items AS $formData) {
                    if($formData->unique_name == 'billingphone') {
                        $billingPhone = $_POST['collected_data'][$formData->id];
                        break;
                    }
                }
                if($billingPhone == '')
                    $billingPhone = $this->addrs->getTelno();

                if(isset($Merchant->cart_data['billing_address']['country']))
                    $billingCountry = $Merchant->cart_data['billing_address']['country'];
                else
                    $billingCountry = $this->getCountryCode();

                $billingAddress = new KlarnaAddr($klarnaCustomerInfo['emailAddress'],
                    $billingPhone, '', 
                    utf8_decode($Merchant->cart_data['billing_address']['first_name']),
                    utf8_decode($Merchant->cart_data['billing_address']['last_name']), '',
                    utf8_decode($Merchant->cart_data['billing_address']['address']),
                    utf8_decode($Merchant->cart_data['billing_address']['post_code']),
                    utf8_decode($Merchant->cart_data['billing_address']['city']),
                    utf8_decode($billingCountry));
            } catch(Exception $e) {
                $Merchant->set_error_message('<div style="border:1px solid #7BA7C9;padding:10px;">
                <img src="' . KLARNA_URL . '/klarna_library/images/klarna/images/logo/klarna_logo.png" /><br />' .
                utf8_encode($e->getMessage() . " (#" . $e->getCode() . ")") . '</div>');
                return false;
            }
        }

        try {
            $this->setAddress(KlarnaFlags::IS_SHIPPING, $this->addrs);
            $this->setAddress(KlarnaFlags::IS_BILLING, $billingAddress);

            if ($this->askForYearlySalary()) {
                $this->setIncomeInfo('yearly_salary', absint($klarnaCustomerInfo['year_salary']));
            }
            
            if(
                isset($klarnaCustomerInfo['ilt']) &&
                is_array($klarnaCustomerInfo['ilt']) &&
                count($klarnaCustomerInfo['ilt']) >= 1) {
                
                foreach($klarnaCustomerInfo['ilt'] AS $iltQuestion => $iltAnswer) {
                    $this->setIncomeInfo($iltQuestion, absint($iltAnswer));
                }
            }
            
            switch($this->moduleType) {
                case 'part':
                case 'spec':
                    $pclassId = absint($klarnaCustomerInfo['paymentPlan']);
                    break;
                default:
                    $pclassId = KlarnaPClass::INVOICE;
            }
            
            $result = $this->addTransaction(
                $klarnaPno,
                ($this->getCountry() == KlarnaCountry::DE || $this->getCountry() == KlarnaCountry::NL) ? $klarnaCustomerInfo['gender'] : null,
                KlarnaFlags::NO_FLAG,
                $pclassId);
        } catch(Exception $e) {

            $error = '<div style="border:1px solid #7BA7C9;padding:10px;">
            <img src="' . KLARNA_URL . '/klarna_library/images/klarna/images/logo/klarna_logo.png" /><br />' .
            utf8_encode($e->getMessage() . " (#" . $e->getCode() . ")") . '</div>';

            $Merchant->set_error_message( $error );

            return false;
        }

        $this->updateShippingData($Merchant->purchase_id);
        
        if(isset($result[0]) && strlen($result[0]) > 0)
            $this->updateOrderNo($result[0], $Merchant->purchase_id);
        
        unset($_SESSION[$wpsc_cart->unique_id]['klarnaCustomerInfo']);
        unset($_SESSION[$wpsc_cart->unique_id]['klarnaAddressObject']);
        
        return $result;
    }
    
    public function getPartPaymentBox($productPrice) {
        $this->API->addMultipleSetupValues(array(
            'path_img' => KLARNA_URL . '/klarna_library/',
            'path_css' => KLARNA_URL . '/klarna_library/klarna/productPrice/default/',
            'asterisk' => ($this->getCountryCode() == 'de' ? '*' : '')
        ));
        
        $cheapestPClass = $this->getCheapestPClass($productPrice, KlarnaFlags::PRODUCT_PAGE);

        if(!$cheapestPClass)
            return;

        $lowestMonthlyCost = KlarnaCalc::calc_monthly_cost($productPrice, $cheapestPClass, KlarnaFlags::PRODUCT_PAGE);
        
        $sMonthDefault = wpsc_currency_display($lowestMonthlyCost, array('display_decimal_point' => ($lowestMonthlyCost == (int)$lowestMonthlyCost ? false : true)));

        $pclasses = $this->API->aPClasses;
        $sTableHtml = '';
        foreach ($pclasses as $pclass) {
            if($pclass['pclass']->getType() == KlarnaPClass::CAMPAIGN && $pclass['monthlyCost'] < KlarnaCalc::get_lowest_payment_for_account($this->getCountry())) 
                continue;
            $sTableHtml .= '<tr><td style="text-align: left;">';
            if ($pclass['pclass']->getType() == KlarnaPClass::ACCOUNT) {
                $sTableHtml .= $this->fetchFromLanguagePack('PPBOX_account');
            } else {
                $sTableHtml .= $pclass['pclass']->getMonths() . " " . $this->fetchFromLanguagePack('PPBOX_th_month');
            }
            $sTableHtml .= '</td><td class="klarna_PPBox_pricetag">';
            $sTableHtml .= wpsc_currency_display($pclass['monthlyCost'], array('display_decimal_point' => ($pclass['monthlyCost'] == (int)$pclass['monthlyCost'] ? false : true)));
            $sTableHtml .= '</td></tr>';
        }
        if($sTableHtml == '')
            return '';

        $aInputValues = array();
        $aInputValues['defaultMonth'] = $sMonthDefault;
        $aInputValues['monthTable'] = $sTableHtml;
        $aInputValues['eid'] = $this->eid;
        $aInputValues['country'] = $this->getCountryCode();
        $aInputValues['nlBanner'] = ($this->getCountryCode() == 'nl' ? '<div class="nlBanner"><img src="' . KLARNA_URL . '/klarna_library/images/klarna/account/notice_nl.jpg" /></div>' : '');
        return $this->API->retrieveHTML($aInputValues, null, KLARNA_FILE_PATH . '/klarna_library/html/productPrice/default/layout.html');
    }
    
    /**
     * Return translated text with Klarna invoice number
     *
     * @return string
     * @author Niklas Malmgren
     **/
    public function getKlarnaInvoiceNumberInfo() {
        global $purchase_log;

        if(strpos($purchase_log['gateway'], 'wpsc_merchant_klarna') !== false && isset($purchase_log['transactid']))
            return '<em>' . str_replace('(xx)', $purchase_log['transactid'], $this->fetchFromLanguagePack('INVOICE_CREATED_SUCCESSFULLY')) . '</em>';
    }

    /**
     * Deletes an order from the WordPress database
     *
     * @param  int $deleteid
     * @return void
     * @author Niklas Malmgren
     **/
    public function deleteWPOrder($deleteid) {
        global $wpdb;

        $delete_log_form_sql = "SELECT * FROM `" . WPSC_TABLE_CART_CONTENTS . "` WHERE `purchaseid`='$deleteid'";
        $cart_content = $wpdb->get_results( $delete_log_form_sql, ARRAY_A );
        $wpdb->query( "DELETE FROM `" . WPSC_TABLE_CART_CONTENTS . "` WHERE `purchaseid`='$deleteid'" );
        $wpdb->query( "DELETE FROM `" . WPSC_TABLE_SUBMITED_FORM_DATA . "` WHERE `log_id` IN ('$deleteid')" );
        $wpdb->query( "DELETE FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `id`='$deleteid' LIMIT 1" );
    }
    
    public function getChangedLanguageCheckoutForm($params, $values, $newLanguage) {
        $this->API->addSetupValue('langISO', $newLanguage);
        return $this->API->retrieveHTML($params, $values, null, array('name' => 'default'));
    }
    
    /**
     * Fetches a translation from the language pack
     *
     * @param  string $sTitle
     * @return string
     * @author Niklas Malmgren
     **/
    public function fetchFromLanguagePack($sTitle) {
        return KlarnaAPI::fetchFromLanguagePack($sTitle, $this->getCountryCode(), KLARNA_FILE_PATH . '/klarna_library/');
    }
    
    /**
     * Checks whether there are any PClasses in the DB
     *
     * @return bool
     * @author Niklas Malmgren
     **/
    public function pClassesInDatabase() {
        return get_option('klarnapclasses') ? true : false;
    }
    
    /**
     * Fetches a config setting from the WP database
     *
     * @param  string $option
     * @return string|bool
     * @author Niklas Malmgren
     **/
    public function getKlarnaOption($option, $countrySpecific = false) {
        return $this->moduleType ? get_option('klarna_' . $this->moduleType . '_' . $option . ($countrySpecific ? '_' . strtoupper($this->getCountryCode()) : '')) : false;
    }

    /**
     * Whether to ask the customer for his or her yearly salary
     *
     * @param  int $sum
     * @return bool
     * @author Niklas Malmgren
     **/
    private function askForYearlySalary($sum = null) {
        if($this->moduleType != 'part')
            return false;

        // Method modified to only check for Danish customers
        return ($this->getCountryCode() == 'dk');

        $optionValue = $this->getKlarnaOption('yearlysalary_', true);
        if(strlen($optionValue) == 0 || !ctype_digit($optionValue))
            return false;
        
        $minimumAmount = absint($optionValue);
        
        if($sum == null && ($sum = $this->totalCartValueIncludingTax) == null)
                return false;
        
        return ($sum >= $minimumAmount);
    }
    
    /**
     * Checks for update to the Klarna payment module
     *
     * @return bool
     * @author Niklas Malmgren
     **/
    public function isUpdateAvailable($moduleType = null) {
        if($moduleType == null && ($moduleType = $this->moduleType) == null)
            return false;
        
        if($this->getKlarnaOption('check_for_updates') == 'on') {
            $url = 'http://static.klarna.com/external/msbo/wpecommerce.latestversion.txt';
            $latestVersion = @file_get_contents($url);
            if($latestVersion != '' && version_compare($latestVersion, self::$moduleVersion) > 0)
                return true;
        }
        return false;
    }
    
    /**
     * Updates the shipping information in WordPress e-Commerce back-end based on what the
     * customer entered in the Klarna form.
     *
     * @param  int $purchase_id
     * @return void
     * @author Niklas Malmgren
     **/
    private function updateShippingData($purchase_id) {
        global $wpdb;

        $form_sql = "SELECT * FROM `" . WPSC_TABLE_SUBMITED_FORM_DATA . "` WHERE `log_id` = '" . (int)$purchase_id . "'";
        $input_data = $wpdb->get_results($form_sql, ARRAY_A);
        
        foreach($input_data as $input_row) {
            $rekeyed_input[$input_row['form_id']] = $input_row;
        }
        
        if($input_data != null) {
            $form_data = $wpdb->get_results("SELECT * FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `active` = '1'", ARRAY_A);
        
            foreach($form_data as $form_field) {
                $id = isset( $rekeyed_input[$form_field['id']]['id'] ) ? $rekeyed_input[$form_field['id']]['id'] : '';
                switch($form_field['unique_name']) {
                    case 'shippingfirstname':
                        if($this->addrs->isCompany)
                            $value = $this->addrs->getCompanyName . ' (ref. ' . $this->addrs->getReference() . ')';
                        else
                            $value = $this->addrs->getFirstName();
                        break;
                    case 'shippinglastname':
                        if($this->addrs->isCompany)
                            $value = ' ';
                        else
                            $value = $this->addrs->getLastName();
                        break;
                    case 'shippingaddress':
                        $value = trim($this->addrs->getStreet() . ' ' . $this->addrs->getHouseNumber() . ' ' . $this->addrs->getHouseExt());
                        break;
                    case 'shippingcity':
                        $value = $this->addrs->getCity();
                        break;
                    case 'shippingcountry':
                        $value = strtoupper($this->addrs->getCountryCode());
                        break;
                    case 'shippingpostcode':
                        $value = $this->addrs->getZipCode();
                        break;
                    default:
                        $value = '';
                        break;
                }
                if($id && $value) {
                    $wpdb->update(WPSC_TABLE_SUBMITED_FORM_DATA, array('value' => utf8_encode($value)), array('id' => absint($id)));
                }
            }
        }
    }

}

/**
 * Holds information about a country. Used by WPKlarnaHTML.
 *
 * @package default
 * @author Niklas Malmgren
 **/
class WPKlarnaCountry {
    /**
     * Two-letter country code
     *
     * @var string
     **/
    public $countryCode;
    
    /**
     * Country name, in English
     *
     * @var string
     **/
    public $countryName;
    
    /**
     * KlarnaCountry constant
     *
     * @var int
     **/
    public $klarnaCountry;
    
    /**
     * KlarnaLanguage constant
     *
     * @var int
     **/
    public $klarnaLanguage;
    
    /**
     * undocumented class variable
     *
     * @var int
     **/
    public $klarnaCurrency;
    
    /**
     * Three-letter currency code
     *
     * @var string
     **/
    public $currencyCode;
    
    /**
     * Two-letter language code
     *
     * @var string
     **/
    public $languageCode;
    
    /**
     * Class constructor
     *
     * @return void
     * @author Niklas Malmgren
     **/
    function __construct($countryCode, $countryName, $klarnaCountry, $klarnaLanguage, $klarnaCurrency, $currencyCode, $languageCode) {
        $this->countryCode = $countryCode;
        $this->countryName = $countryName;
        $this->klarnaCountry = $klarnaCountry;
        $this->klarnaLanguage = $klarnaLanguage;
        $this->klarnaCurrency = $klarnaCurrency;
        $this->currencyCode = $currencyCode;
        $this->languageCode = $languageCode;
    }
} // END class 

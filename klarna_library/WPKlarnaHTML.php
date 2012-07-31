<?php

@include_once(dirname(__FILE__) . '/WPKlarna.php');

class WPKlarnaHTML {
    static function setDefaults($moduleType) {
        $defaults = array(
            'enabled' => 'on',
            'order_status' => 3,
            'server' => 'live',
            'product_view' => 'on',
            'product_gallery_view' => 'on',
            'check_for_updates' => 'on'
        );
        foreach($defaults AS $key => $value) {
            if(strlen(get_option('klarna_' . $moduleType . '_' . $key)) == 0)
                update_option('klarna_' . $moduleType . '_' . $key, $value);
        }
    }


    /**
     * Processes the submitted settings form
     *
     * @return void
     * @author Niklas Malmgren
     **/
     static function saveSettings($moduleType) {
        if($_POST['klarna-module-type'] != $moduleType)
            return;
     
        if(isset($_POST['klarna_' . $moduleType . '_enabled']) && $_POST['klarna_' . $moduleType . '_enabled'] == 'on')
            update_option('klarna_' . $moduleType . '_enabled', 'on');
        else
            update_option('klarna_' . $moduleType . '_enabled', 'off');
        
        if(isset($_POST['klarna_' . $moduleType . '_product_view']) && $_POST['klarna_' . $moduleType . '_product_view'] == 'on')
            update_option('klarna_' . $moduleType . '_product_view', 'on');
        else
            update_option('klarna_' . $moduleType . '_product_view', 'off');
        
        if(isset($_POST['klarna_' . $moduleType . '_product_gallery_view']) && $_POST['klarna_' . $moduleType . '_product_gallery_view'] == 'on')
            update_option('klarna_' . $moduleType . '_product_gallery_view', 'on');
        else
            update_option('klarna_' . $moduleType . '_product_gallery_view', 'off');
        
        $countries = (array)$_POST['klarna_' . $moduleType . '_country'];
        
        $enabledCountries = array();
        foreach($countries AS $countryCode => $countryData) {
            if(isset($countryData['status']) && $countryData['status'] == 'on') {
                $enabledCountries[] = $countryCode;
                update_option('klarna_' . $moduleType . '_eid_' . $countryCode, $countryData['eid']);
                update_option('klarna_' . $moduleType . '_secret_' . $countryCode, $countryData['secret']);

                if(isset($countryData['fee'])) {
                    $countryData['fee'] = str_replace(',', '.', $countryData['fee']);
                    update_option('klarna_' . $moduleType . '_fee_' . $countryCode, abs((float)$countryData['fee']));
                }
            }
        }
        update_option('klarna_' . $moduleType . '_enabled_countries', implode(',', $enabledCountries));
        
        update_option('klarna_' . $moduleType . '_order_status', $_POST['klarna_' . $moduleType . '_order_status']);

        if(isset($_POST['klarna_' . $moduleType . '_server']) && $_POST['klarna_' . $moduleType . '_server'] == 'beta')
            update_option('klarna_' . $moduleType . '_server', 'beta');
        else
            update_option('klarna_' . $moduleType . '_server', 'live');

        if(isset($_POST['klarna_' . $moduleType . '_check_for_updates']) && $_POST['klarna_' . $moduleType . '_check_for_updates'] == 'on')
            update_option('klarna_' . $moduleType . '_check_for_updates', 'on');
        else
            update_option('klarna_' . $moduleType . '_check_for_updates', 'off');

        if(isset($_POST['klarna_' . $moduleType . '_agb_url']))
            update_option('klarna_' . $moduleType . '_agb_url', $_POST['klarna_' . $moduleType . '_agb_url']);
    }
    
    /**
     * Returns the settings form
     *
     * @return string
     * @author Niklas Malmgren
     **/
    static function getSettingsForm($moduleType) {
        global $wpsc_purchlog_statuses;
        
        switch($moduleType) {
            case 'invoice':
                $moduleName = 'Invoice';
                break;
            case 'part':
                $moduleName = 'Part Payment';
                break;
            case 'invoice':
                $moduleName = 'Special Campaigns';
                break;
        }

        $Klarna = new WPKlarna();
        
        $fetchPClassesURL = str_replace('&amp;', '&', wp_nonce_url(WPSC_URL . '/wpsc-merchants/klarna_library/klarnaAjax.php?action=updatePClasses&moduleType=' . $moduleType, 'pclass-update'));
        $loadImageURL = WPSC_URL . '/wpsc-merchants/klarna_library/images/klarna/images/loader1.gif';

        $js = <<<EOF
<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery('input[name="user_defined_name[wpsc_merchant_klarna_invoice]"]').parentsUntil('tbody').hide();
        jQuery('input[name="user_defined_name[wpsc_merchant_klarna_part]"]').parentsUntil('tbody').hide();
        jQuery('input[name="user_defined_name[wpsc_merchant_klarna_spec]"]').parentsUntil('tbody').hide();
        jQuery('.gateway_settings td').css({"padding-bottom" : "15px"});
        jQuery('input.klarna-invoice-fee').each(function() {
            if(!jQuery(this).val()) {
                jQuery(this).val('0');
            }
        });
        jQuery('.klarna-country-checkbox').change(function() {
            var countryRowId = jQuery(this).attr('id') + '-fields';
            if(jQuery(this).attr('checked')) {
                jQuery('#' + countryRowId).show();
            } else {
                jQuery('#' + countryRowId).hide();
            }
        });
    });
    function updatePclasses(elem) {
        jQuery(elem).css('cursor','wait');
        jQuery('td.select_gateway').parent().html('<td colspan="2"><p>Fetching PClasses, please wait ... <img src="{$loadImageURL}" alt="" /></p></td>').load('{$fetchPClassesURL}', function() {
            jQuery(elem).css('cursor','pointer');
        });
    }

</script>

EOF;
	    $output = $js;
	    
	    $output .= '<input type="hidden" name="klarna-module-type" value="' . $moduleType . '" />';
	    
	    // Klarna logo and module version
	    $output .= '<tr><td colspan="2"><img src="' . WPSC_URL . '/wpsc-merchants/klarna_library/images/klarna/images/logo/logo_small.png" style="display:block;float:right;margin-left: 10px"/><em>Klarna module version ' . WPKlarna::$moduleVersion . '</em></td></tr>';
	    
	    // Update information
	    if($Klarna->isUpdateAvailable($moduleType)) {
    	    $output .= '<tr><td colspan="2" style="border:1px solid red;padding: 10px 10px 0 10px;"><span style="font-weight:bold;color:red;">Update available!</span> A newer version of your payment module is available. Please visit <a href="http://integration.klarna.com">the Klarna integration site</a> for more information.</td></tr>';
	    }
	    
	    // Fetch PClass link
	    if($moduleType != 'invoice') {
	        $pleaseUpdate = (!$Klarna->pClassesInDatabase() ? 'No PClasses in database - ' : '');
    	    $output .= '<tr><td colspan="2">' . $pleaseUpdate . $numberOfPClasses . '<a href="#" onclick="updatePclasses(this);return false;">Click here to update your PClasses</a></td></tr>';
    	}
	    
	    // Enable and disable module
	    $output .= '<tr><td>Enabled</td><td><input type="checkbox" name="klarna_' . $moduleType . '_enabled" id="klarnaEnabled" value="on" ' . (get_option('klarna_' . $moduleType . '_enabled') == 'on' ? 'checked="checked" ' : '') . '/>&nbsp;<label for="klarnaEnabled">Enable Klarna ' . $moduleName . '</label><br /><em>Please make sure that you have also activated this payment module in the list to the left.</em></td></tr>';
	    
	    if($moduleType == 'part') {
	        $output .= '<tr><td>Display Monthly Cost</td><td>
	        <fieldset style="border:1px solid #DFDFDF;padding:0 10px 10px;">
	        <input type="checkbox" name="klarna_' . $moduleType . '_product_view" id="klarnaProductView" value="on" ' . (get_option('klarna_' . $moduleType . '_product_view') == 'on' ? 'checked="checked" ' : '') . '/>&nbsp;<label for="klarnaProductView">Single product views</label></br>';
	        $output .= '<input type="checkbox" name="klarna_' . $moduleType . '_product_gallery_view" id="klarnaProductGalleryView" value="on" ' . (get_option('klarna_' . $moduleType . '_product_gallery_view') == 'on' ? 'checked="checked" ' : '') . '/>&nbsp;<label for="klarnaProductGalleryView">Product gallery views</label></fieldset><em>Show your customers information about monthly costs using different Klarna Part Payment options.</em></td></tr>';
        }


	    $output .= '<tr><td>Countries</td><td><div class="ui-widget-content multiple-select" style="margin:0;padding: 5px;">';
        $enabledCountries = explode(',', get_option('klarna_' . $moduleType . '_enabled_countries'));
        foreach($Klarna->countries AS $country) {
            $output .= '<input type="checkbox" class="klarna-country-checkbox" id="klarna-country-' . $country->countryCode . '" name="klarna_' . $moduleType . '_country[' . $country->countryCode . '][status]" value="on" ' . (in_array($country->countryCode, $enabledCountries) ? 'checked="checked" ' : '') . '/><label for="klarna-country-' . $country->countryCode . '">&nbsp;<img src="' . WPSC_URL . '/wpsc-merchants/klarna_library/images/klarna/images/flags/' . $country->languageCode . '.png" />&nbsp;' . $country->countryName . '</label><br />';
        }
	        $output .= '</div><em>Tick the countries where you would like to offer Klarna ' . $moduleName . '.</em></td></tr>';

        foreach($Klarna->countries AS $country) {
            $output .= '<tr id="klarna-country-' . $country->countryCode  . '-fields" style="' . (!in_array($country->countryCode, $enabledCountries) ? 'display:none;' : '') . '">';
            $output .= '<td>&nbsp;</td>';
            $output .= '<td><fieldset style="border:1px solid #DFDFDF;padding:0 10px 10px;"><legend style="padding:0 5px;"><img src="' . WPSC_URL . '/wpsc-merchants/klarna_library/images/klarna/images/flags/' . $country->languageCode . '.png" />&nbsp;' . $country->countryName . '</legend>
            <label for="eid-' . $country->countryCode . '" style="margin-right:4px;">Merchant ID:</label>
            <input type="text" name="klarna_' . $moduleType . '_country[' . $country->countryCode . '][eid]" id="eid-' . $country->countryCode . '" style="margin-bottom:10px;" value="' . get_option('klarna_' . $moduleType . '_eid_' . $country->countryCode) . '" /><br />
            <label for="secret-' . $country->countryCode . '" style="margin-right:4px;">Shared Secret:</label><input type="text" name="klarna_' . $moduleType . '_country[' . $country->countryCode . '][secret]" id="secret-' . $country->countryCode . '" style="margin-bottom:10px;" value="' . get_option('klarna_' . $moduleType . '_secret_' . $country->countryCode) . '" />';
            
            if($moduleType == 'invoice')
                $output .= '<br /><label for="fee-' . $country->countryCode . '" style="margin-right:4px;">Invoice Fee (' . strtoupper($country->currencyCode) . '):</label><input type="text" name="klarna_' . $moduleType . '_country[' . $country->countryCode . '][fee]" class="klarna-invoice-fee" id="fee-' . $country->countryCode . '" style="margin-bottom:10px;width:100px;" value="' . get_option('klarna_' . $moduleType . '_fee_' . $country->countryCode) . '" />';

            if($country->countryCode == 'DE')
                $output .= '<br /><label for="klarna_' . $moduleType . '_agb_url" style="margin-right:4px;">AGB URL</label><input type="text" name="klarna_' . $moduleType . '_agb_url" id="klarna_' . $moduleType . '_agb_url" value="' . get_option('klarna_' . $moduleType . '_agb_url') . '" /><br /><em>URL to your privacy policy</em>
';

            $output .= '</fieldset></td></tr>';
        }
        
        $statusOptions = '';
        foreach($wpsc_purchlog_statuses AS $orderStatus) {
            $statusOptions .= sprintf('<option value="%s"%s>%s</option>', $orderStatus['order'], $orderStatus['order'] == get_option('klarna_' . $moduleType . '_order_status') ? ' selected="selected"' : '', $orderStatus['label']);
        }

        $serverLiveChecked = (get_option('klarna_' . $moduleType . '_server') == 'live' ? 'checked="checked" ' : '');
        $serverBetaChecked = (get_option('klarna_' . $moduleType . '_server') == 'beta' ? 'checked="checked" ' : '');
	    $output .= <<<EOF
    <tr>
        <td>Order Status</td>
        <td>
            <select name="klarna_{$moduleType}_order_status">{$statusOptions}</select><br />
            <em>Status assigned to orders made through this gateway.</em>
        </td>
    </tr>
    <tr>
        <td>Server</td>
        <td><input type="radio" name="klarna_{$moduleType}_server" id="klarnaServerLive" value="live" {$serverLiveChecked}/>&nbsp;<label for="klarnaServerLive">Live Server</label><br /><input type="radio" name="klarna_{$moduleType}_server" id="klarnaServerBeta" value="beta" {$serverBetaChecked}/>&nbsp;<label for="klarnaServerBeta">Beta Server</label></td>
    </tr>

EOF;

        // Check for updates
        $output .= '<tr><td>Updates</td><td><input type="checkbox" name="klarna_' . $moduleType . '_check_for_updates" id="klarnaCheckForUpdates" value="on" ' . (get_option('klarna_' . $moduleType . '_check_for_updates') == 'on' ? 'checked="checked" ' : '') . '/>&nbsp;<label for="klarnaCheckForUpdates">Check for updates</label></td></tr>';

	
	    return $output;

    }
} // END class 

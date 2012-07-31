<?php
require(dirname(__FILE__) . '/../../../../../wp-load.php');
require_once('WPKlarna.php');

$sAction = KlarnaHTTPContext::toString('action');
$sNonce = KlarnaHTTPContext::toString('_wpnonce');

if($sAction == null) {
	exit("No action defined!");
} elseif($sAction == 'updatePClasses') {
    $error = '';
    $sModuleType = KlarnaHTTPContext::toString('moduleType');

    if(!wp_verify_nonce($sNonce, 'pclass-update')) {
        $error = 'Security error :' . $sNonce;
    } elseif($sModuleType == '') {
        $error = 'Error: no module defined';
    }
    
    if($error)
        die('<td>' . $error . '</td>');

    $moduleTypes = array('part', 'spec');
    
    if(get_option('klarna_part_enabled') == 'on')
        $moduleType = 'part';
    elseif(get_option('klarna_spec_enabled') == 'on')
        $moduleType = 'spec';
    else
        die('<td>Error: neither part payment module nor special campaigns module enabled</td>');

    $enabledPartCountries = explode(',', get_option('klarna_part_enabled_countries'));
    $enabledSpecCountries = explode(',', get_option('klarna_spec_enabled_countries'));
    
    foreach($enabledPartCountries AS $countryCode) {
        $eid = get_option('klarna_part_eid_' . $countryCode);
        $secret = get_option('klarna_part_secret_' . $countryCode);
        if($eid && $secret)
            $enabledCountries[$countryCode][] = array('eid' => $eid, 'secret' => $secret);
    }
    
    foreach($enabledSpecCountries AS $countryCode) {
        $eid = get_option('klarna_spec_eid_' . $countryCode);
        $secret = get_option('klarna_spec_secret_' . $countryCode);
        if($eid && $secret) {
            if(!isset($enabledCountries[$countryCode]) || $enabledCountries[$countryCode][0]['eid'] != $eid) {
                $enabledCountries[$countryCode][] = array('eid' => $eid, 'secret' => $secret);
            }
        }
    }

    $str = '<h3>Klarna PClasses updated</h3>';
    $numFound = 0;
    
    $mode = (get_option('klarna_' . $moduleType . '_server') == 'beta' ? Klarna::BETA : Klarna::LIVE);

    $Klarna = new WPKlarna($moduleTyle);

    $str .= '<div style="border: 1px solid #8CC63F; background-color: #D7EBBC; padding: 10px; font-family: Arial, Verdana; font-size: 11px; margin: 10px">';
    $str .= '<pre>';
    $str .= "<b>id  | description                             | months | interest rate | handling fee | start fee | min amount | country</b><br /><hr size='1' style='border-top: 1px solid #8CC63F;'/>";

    foreach($enabledCountries AS $countryCode => $countryEIDs) {
        foreach($countryEIDs AS $countryCredentials) {
            if(!in_array(strtolower($countryCode), array('se', 'no', 'dk', 'fi', 'de', 'nl')))
                continue;
    
            $eid = $countryCredentials['eid'];
            $secret = $countryCredentials['secret'];
            if($eid && $secret) {
                $Klarna->config($eid, $secret, $countryCode, null, null, $mode, 'wp', 'klarnapclasses', ($mode == Klarna::LIVE));
                try {
                    $Klarna->fetchPClasses($countryCode);
                } catch(Exception $e) {
                    continue;
                }
                foreach($Klarna->getPClasses() as $pclass) {
                    $numFound++;
                    $addition = strlen(utf8_encode($pclass->getDescription()));
                    $addition2 = strlen(html_entity_decode($pclass->getDescription()));
                    $sum = ($addition == $addition2 ? 40 : 40+($addition-$addition2));
    
                    $str .= sprintf("%-4s|", $pclass->getId());
                    $str .= sprintf(" %-".$sum."s|", $pclass->getDescription());
                    $str .= sprintf(" %-7s|", $pclass->getMonths());
                    $str .= sprintf(" %-14s|", $pclass->getInterestRate());
                    $str .= sprintf(" %-13s|", $pclass->getInvoiceFee());
                    $str .= sprintf(" %-10s|", $pclass->getStartFee());
                    $str .= sprintf(" %-11s|", $pclass->getMinAmount());
                    $str .= sprintf(" %-7s",  '<img src="' . WPSC_URL . '/wpsc-merchants/klarna_library/images/klarna/images/flags/' . $Klarna->getLanguageCode() . '.png" border="0" title="' . $pclass->getCountry() . '" /> ');
                    $str .= "<br />";
                }
            }
        }
    }
    $str .= "</pre></div>";
    
    $str .= '<p>Found ' . $numFound . ' PClasses </p>';
    
    die('<td>' . $str . '</td>');
    
} elseif($sAction == 'languagepack') {
	$sSubAction	= KlarnaHTTPContext::toString('subAction');
	
	if ($sSubAction == "klarna_box") {
		$sNewISO = KlarnaHTTPContext::toString('newIso');
		$sCountry = KlarnaHTTPContext::toString('country');
		$iSum = KlarnaHTTPContext::toInteger('sum', 0);
		$iFlag = KlarnaHTTPContext::toInteger('flag');
		$sType = KlarnaHTTPContext::toString('type');
		$aParams = KlarnaHTTPContext::toArray('params');
		$aValues = KlarnaHTTPContext::toArray('values');

		if ($sType != "part" && $sType != "invoice" && $sType != "spec")
			exit("Invalid paramters");

        $Klarna = new WPKlarna($sType);
		$aParams = $Klarna->getParams();

        echo $Klarna->getChangedLanguageCheckoutForm($aParams, $aValues, $sNewISO);
	}
	else if ($sSubAction == 'jsLanguagePack')
	{
		$sNewISO	= KlarnaHTTPContext::toString('newIso');
		$sFetch		= "";
	}
} elseif ($sAction == 'getAddress') {
	$aSessionCalls = array();
	
	// Check the session for calls
	if (array_key_exists('address', $_SESSION)) {
		$sSessionCalls	= base64_decode($_SESSION['klarna_get_address']);
		$aSessionCalls	= unserialize($sSessionCalls);
	}
	
	$sPNO = KlarnaHTTPContext::toString('pno');
	$sCountry = strtolower(KlarnaHTTPContext::toString('country'));
    $sType = KlarnaHTTPContext::toString('type');
	
	if (array_key_exists($sPNO, $aSessionCalls)) {
		$addrs	= unserialize($aSessionCalls[$sPNO]);
	} else {
	    $sEID 		= get_option('klarna_' . $sType . '_eid_' . strtoupper($sCountry));
	    $sSecret 	= get_option('klarna_' . $sType . '_secret_' . strtoupper($sCountry));
		
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
			$sString	.= "<".$key.">" . $val . "</".$key.">\n"; 
		}
		
		$sString .= "</address>\n";
	}
	
	$sString .= "</getAddress>";
	
	echo $sString;
}
else {
	exit("Unknown function");
}

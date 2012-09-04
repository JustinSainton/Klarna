<?php



function klarna_language_pack() {
        $sSubAction = KlarnaHTTPContext::toString('subAction');
    
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
        $sNewISO    = KlarnaHTTPContext::toString('newIso');
        $sFetch     = "";
    }
}

function klarna_get_address() {
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
    
    echo $sString;
}
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
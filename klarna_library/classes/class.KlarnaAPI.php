<?php
/**
 * The Klarna API class. This class handles all the API functions send by the GUI.
 *
 * @package   	Klarna Standard Kassa API
 * @version 	1.0
 * @since 		1.0 - 14 mar 2011
 * @link		http://integration.klarna.com/
 * @copyright	Copyright (c) 2011 Klarna AB (http://klarna.com)
 */
class KlarnaAPI {
	/**
	 * Array with different input values
	 *
	 * @var array
	 */
	private $aInputParameters = array();

	/**
	 * Array with different input value values
	 *
	 * @var array
	 */
	private $aInputValues = array();

	/**
	 * The county code
	 *
	 * @var string
	 */
	private $sCountryCode;

	/**
	 * The type of class loaded. Either part or invoice or spec
	 *
	 * @var string
	 */
	private $sType;

	/**
	 * The ISO for language (e.g. sv, da, nb, en, de)
	 *
	 * @var string
	 */
	private $sLangISO;

	/**
	 * The setup values.
	 *
	 * @var array
	 */
	private $aSetupSettings = array();

	/**
	 * The PClasses
	 *
	 * @var array
	 */
	public $aPClasses;

	/**
	 * The klarna object
	 *
	 * @var Klarna
	 */
	private $oKlarna;

	/**
	 * The klarna language, set from KlarnaLanguage object
	 *
	 * @var integer
	 */
	private $iKlarnaLanguage;

	/**
	 * The klarna currency, set from KlarnaCurrency object
	 *
	 * @var integer
	 */
	private $iKlarnaCurrency;

	/**
	 * The klarna country, set from KlarnaCountry object
	 *
	 * @var integer
	 */
	private $iKlarnaCountry;

	/**
	 * The path where the API and Standard register is located
	 *
	 * @var string
	 */
	private $sPath;

	/**
	 * The ILT questions
	 */
	private $aIltQuestions = array();

	/**
	 * The class constructor. Initiates the Klarna Api class
	 *
	 * @ignore Do not show this in PHPDoc.
	 * @return void
	 */
	public function __construct ($a_sCountry, $a_sLangISO, $a_sType, $a_iSum, $a_iFlag, &$a_oKlarna = null, $aTypes = null, $sPath = null)
	{
		$this->sPath = $sPath;

		if ($a_sLangISO == null)
		{
			$aLangArray	= array("se" => "sv", "de" => "de", "dk" => "da", "nl" => "nl", "no" => "nb", "fi" => "fi", "en" => "en");
				
			$a_sLangISO	= @$aLangArray[strtolower($a_sCountry)];
		}

		// Validate the submitted values
		$this->validateCountry($a_sCountry);
		$this->validateType($a_sType);
		$this->validateLangISO($a_sLangISO);

		// Set the klarna object
		$this->oKlarna = &$a_oKlarna;

		// Set the default input names
		$this->aInputParameters['mobilePhone'] 	= "mobilePhone";
		$this->aInputParameters['street'] 		= "street";
		$this->aInputParameters['homenumber'] 	= "homenumber";
		$this->aInputParameters['paymentPlan'] 	= "paymentPlan";
		$this->aInputParameters['sex'] 			= "sex";
		$this->aInputParameters['male'] 		= "male";
		$this->aInputParameters['female'] 		= "female";
		$this->aInputParameters['birthday_day'] = "birthday_day";
		$this->aInputParameters['birthday_month'] = "birthday_month";
		$this->aInputParameters['birthday_year']= "birthday_year";
		$this->aInputParameters['bd_jan'] 		= "1";
		$this->aInputParameters['bd_feb'] 		= "2";
		$this->aInputParameters['bd_mar'] 		= "3";
		$this->aInputParameters['bd_apr'] 		= "4";
		$this->aInputParameters['bd_may'] 		= "5";
		$this->aInputParameters['bd_jun'] 		= "6";
		$this->aInputParameters['bd_jul'] 		= "7";
		$this->aInputParameters['bd_aug'] 		= "8";
		$this->aInputParameters['bd_sep'] 		= "9";
		$this->aInputParameters['bd_oct'] 		= "10";
		$this->aInputParameters['bd_nov'] 		= "11";
		$this->aInputParameters['bd_dec'] 		= "12";
		$this->aInputParameters['sex_male'] 	= "male";
		$this->aInputParameters['sex_female'] 	= "female";
		$this->aInputParameters['socialNumber'] = "socialNumber";
		$this->aInputParameters['phoneNumber'] 	= "phoneNumber";
		$this->aInputParameters['year_salary'] 	= "year_salary";
		$this->aInputParameters['house_extension'] 	= "house_extension";
		$this->aInputParameters['mobilePhoneNumber'] = "mobilePhoneNumber";
		$this->aInputParameters['shipmentAddressInput'] = "shipment_address";
		$this->aInputParameters['shipmentAddressInput_invoice'] = "shipmentAddressInput_invoice";
		$this->aInputParameters['emailAddress'] = "emailAddress";
		$this->aInputParameters['invoiceType'] 	= "invoiceType";
		$this->aInputParameters['reference'] 	= "reference";
		$this->aInputParameters['companyName'] 	= "companyName";
		$this->aInputParameters['firstName'] 	= "firstName";
		$this->aInputParameters['lastName'] 	= "lastName";
		$this->aInputParameters['invoice_type'] = "invoice_type";
		$this->aInputParameters['consent'] 		= "consent";
		$this->aInputParameters['city']			= "city";
		$this->aInputParameters['zipcode']		= "zipcode";


		// Set the default setup values
		$this->aSetupSettings['langISO']		= $this->sLangISO;
		$this->aSetupSettings['countryCode']	= $this->sCountryCode;
		$this->aSetupSettings['sum']			= $a_iSum;
		$this->aSetupSettings['flag']			= $a_iFlag;

		$this->aSetupSettings['web_root']		= "/";

		$this->setPaths();

		// Fetch PClasses in case type is invoice
		if (($this->sType == 'part' || $this->sType == 'spec') && $this->oKlarna != null)
		{
			$this->fetchPClasses($a_iSum, $a_iFlag, $aTypes);
		}
	}

	public function setPaths ()
	{
		$web_root								= $this->aSetupSettings['web_root'];

		$this->aSetupSettings['path_css']		= $web_root;
		$this->aSetupSettings['path_js']		= $web_root . 'js/';
		$this->aSetupSettings['path_img']		= $web_root . 'images/klarna/';
	}

	/**
	 * Add/Overwrite extra setup values.
	 *
	 * @param string $sName The name of the value
	 * @param string $sValue The value
	 * @return void
	 */
	public function addSetupValue ($sName, $sValue)
	{
		$this->aSetupSettings[$sName] = $sValue;
	}

	/**
	 * Add multiple setup values at once
	 *
	 * @param array $aSetupValues The setup values as array. Key is name, value is value.
	 * @return void
	 */
	public function addMultipleSetupValues ($aSetupValues)
	{
		foreach ($aSetupValues as $sName => $sValue)
		{
			$this->aSetupSettings[$sName] = $sValue;
		}
	}

	/**
	 * Set the ILT questions
	 *
	 * @param array $aIltQuestions
	 */
	public function setIltQuestions ($aIltQuestions)
	{
		$this->aIltQuestions = $aIltQuestions;
	}

	/**
	 * Retrieve the finished HTML
	 *
	 * @param array 	$a_aParams 		The input field names. Only submitted for those that should be different from default values
	 * @param string 	$a_sHTMLFile 	(Optional) The file to import. If not submitted, which HTML file will be decides by the class
	 * @return string
	 */
	public function retrieveHTML ($a_aParams = null, $a_aValues = null, $a_sHTMLFile = null, $aTemplateData = null)
	{
		$sFilename = "";

		if ($a_aValues != null)
		$this->aInputValues = $a_aValues;

		if ($a_aParams != null)
		$this->aInputParameters	= array_merge($this->aInputParameters, $a_aParams);

		if (is_array($this->aPClasses))
		{
			$sDefaultId	= "";
				
			foreach($this->aPClasses as $pclass)
			{
				if ($this->sType == "part" && $pclass['pclass']->getType() == 1)
				{
					$sDefaultId	= $pclass['pclass']->getId();
				}
				else if ($this->sType == "spec" && $pclass['pclass']->getType() == 2)
				{
					$sDefaultId	= $pclass['pclass']->getId();
				}
			}
				
			$this->aInputValues['paymentPlan'] = $sDefaultId;
		}

		/**
		 * @todo Check for file and throw error if missing
		 */
		if ($a_sHTMLFile != null)
		{
			$sFilename = $a_sHTMLFile;
		}
		else
		{
			if ($this->sType != "spec")
			{
				$sFilename	= ($this->sPath != null ? $this->sPath : "") . '/html/' . strtolower($this->sCountryCode) . "/" . $this->sType . ".html";
			}
			else {
				$this->aSetupSettings['conditionsLink']	= isset( $aTemplateData['conditions'] ) ? $aTemplateData['conditions'] : '';

				$sFilename	= ($this->sPath != null ? $this->sPath : "") . '/html/campaigns/' . $aTemplateData['name'] . "/" . strtolower($this->sCountryCode) . "/" . $this->sType . ".html";
			}
		}

		return $this->translateInputFields(file_get_contents($sFilename));
	}

	/**
	 * Fetch the PClasses from file
	 *
	 * @param	integer	$iSum	The sum of the objects to be bought
	 * @param	integer	$iFlag	The KlarnaFlag to be used. Either Checkout or ProductPage flag.
	 * @return	void
	 */
	public function fetchPClasses ($iSum, $iFlag, $aTypes = null)
	{
		if ($this->oKlarna == null)
		{
			throw new KlarnaApiException("No klarna class is set.", "1000");
		}

		$aPClasses = array();

		foreach($this->oKlarna->getPClasses() as $pclass) {
			if ($pclass->getMinAmount() <= $iSum && ($aTypes == null || in_array($pclass->getType(), $aTypes)))
			{
				$iMonthlyCost = KlarnaCalc::calc_monthly_cost($iSum, $pclass, $iFlag);

				$sType = $pclass->getType();

				$aPClasses[$pclass->getId()]['pclass'] = $pclass;
				$aPClasses[$pclass->getId()]['monthlyCost'] = $iMonthlyCost;
				$aPClasses[$pclass->getId()]['default'] = ($this->sType == 'part' && $sType == 1 ? true : ($this->sType == 'spec' && $sType == 2 ? true : false));
			}
		}

		$this->aPClasses = $aPClasses;
	}

	/**
	 * Checks wether the country code is accepted by the API
	 *
	 * @throws	KlarnaApiException
	 * @param	string	$sCountryCode	The country code ISO-2
	 * @return	boolean
	 */
	private function validateCountry ($sCountryCode)
	{
		if (in_array(strtolower($sCountryCode), array("nl", "se", "de", "dk", "no", "fi")))
		{
			$this->sCountryCode	= strtolower($sCountryCode);
				
			switch ( $this->sCountryCode ) {
				case "nl":
					$this->iKlarnaCountry = KlarnaCountry::NL;
					$this->iKlarnaCurrency = KlarnaCurrency::EUR;
					break;
				case "se":
					$this->iKlarnaCountry = KlarnaCountry::SE;
					$this->iKlarnaCurrency = KlarnaCurrency::SEK;
					break;
				case "de":
					$this->iKlarnaCountry = KlarnaCountry::DE;
					$this->iKlarnaCurrency = KlarnaCurrency::EUR;
					break;
				case "dk":
					$this->iKlarnaCountry = KlarnaCountry::DK;
					$this->iKlarnaCurrency = KlarnaCurrency::DKK;
					break;
				case "no":
					$this->iKlarnaCountry = KlarnaCountry::NO;
					$this->iKlarnaCurrency = KlarnaCurrency::NOK;
					break;
				case "fi":
					$this->iKlarnaCountry = KlarnaCountry::FI;
					$this->iKlarnaCurrency = KlarnaCurrency::EUR;
					break;
				default:
					break;
			}
				
			return true;
		}
		else {
			throw new KlarnaApiException('Error in ' . __METHOD__ . ': Invalid country code submitted!');
		}
	}

	/**
	 * Checks wether the country code is accepted by the API
	 *
	 * @throws	KlarnaApiException
	 * @param	string	$sType	The type. Either "part", "spec" or "invoice"
	 * @return	boolean
	 */
	private function validateType ($sType)
	{
		if (in_array(strtolower($sType), array("part", "invoice", "spec")))
		{
			$this->sType	= strtolower($sType);
			return true;
		}
		else {
			throw new KlarnaApiException('Error in ' . __METHOD__ . ': Invalid type submitted!');
		}
	}

	/**
	 * Checks wether the country code is accepted by the API
	 *
	 * @throws	KlarnaApiException
	 * @param	string	$a_sLangISO	The language in ISO-2 format
	 * @return	boolean
	 */
	private function validateLangISO ($a_sLangISO)
	{
		if (in_array(strtolower($a_sLangISO), array("sv", "da", "en", "de", "nl", "nb", "fi")))
		{
			$this->sLangISO	= strtolower($a_sLangISO);
				
			switch ( $this->sLangISO ) {
				case "sv":
					$this->iKlarnaLanguage = KlarnaLanguage::SV;
					break;
				case "da":
					$this->iKlarnaLanguage = KlarnaLanguage::DA;
					break;
				case "de":
					$this->iKlarnaLanguage = KlarnaLanguage::DE;
					break;
				case "nl":
					$this->iKlarnaLanguage = KlarnaLanguage::NL;
					break;
				case "nb":
					$this->iKlarnaLanguage = KlarnaLanguage::NB;
					break;
				case "fi":
					$this->iKlarnaLanguage = KlarnaLanguage::FI;
					break;
				default:
					break;
			}
				
			return true;
		}
		else {
			throw new KlarnaApiException('Error in ' . __METHOD__ . ': Invalid language ('.$a_sLangISO.') ISO submitted!');
		}
	}

	/**
	 * Translating the fetched HTML agains dynamic values set in this class
	 *
	 * @param	string	$sHtml	The HTML to translate
	 * @return	string
	 */
	private function translateInputFields ($sHtml)
	{
		$sHtml = preg_replace_callback("@{{(.*?)}}@", array($this, 'changeText'), $sHtml);

		return $sHtml;
	}

	/**
	 * Changeing the text from a HTML {{VALUE}} to the acual value decided by the array
	 *
	 * @param	array	$aText	The result from the match in function translateInputFields
	 * @return	mixed
	 */
	private function changeText ($aText) {
		// Split them
		$aExplode	= explode(".", $aText[1]);
		$sType		= $aExplode[0];
		$sName		= $aExplode[1];

		if ($sType == "input")
		{
			if (array_key_exists($sName, $this->aInputParameters))
				return $this->aInputParameters[$sName];
			else
			{
				throw new KlarnaApiException('Error in ' . __METHOD__ . ': Invalid inputfield value ('.$sName.') found in HTML code!');
				return false;
			}
		}
		else if($sType == "lang")
		{
			return $this->fetchFromLanguagePack($sName);
		}
		else if($sType == "setup")
		{
			if ($sName == "pclasses")
			{
				return $this->renderPClasses();
			}
			else
			return @$this->aSetupSettings[$sName];
		}
		else if ($sType == "value")
		{
			return @$this->aInputValues[$sName];
		}
		else if ($sType == 'ilt')
		{
			if ($sName == 'box')
			{
				$sHtml	= "";
				
				if (count($this->aIltQuestions) > 0)
				{
					foreach($this->aIltQuestions as $sInputValue => $aQuestion)
					{
						$sQuestion	= $aQuestion['text'];
						$sAnswer	= "";
						$sType		= strtolower($aQuestion['type']);
						$aValues	= $aQuestion['values'];
						$bSet		= true;
						
						if ($sType == 'dropdown')
						{
							$sAnswer	= "<select name=\"$sInputValue\">\n";
							
							foreach($aValues as $iNum => $aData)
							{
								$sAnswer .= '<option value="'.htmlentities($aData['value']).'">'.htmlentities($aData['name']).'</option>'."\n";
							}
							
							$sAnswer	.= "</select>";
						}
						else {
							$bSet	= false;
						}
						
						if ($bSet)
						{
							$aParams	= array('ilt_question' => $sQuestion, 'ilt_answer' => $sAnswer);
							
							$sHtml	.= $this->retrieveHTML($aParams, null, ($this->sPath != null ? $this->sPath : "") . '/html/ilt_template.html');
						}
					}
				}
				
				return $sHtml;
			}
			else {
				if (array_key_exists($sName, $this->aInputParameters))
				{
					return $this->aInputParameters[$sName];
				}
			}
		}
		else {
			throw new KlarnaApiException('Error in ' . __METHOD__ . ': Invalid field name ('.$sType.') found in HTML code!');
			return false;
		}
	}

	/**
	 * Redender the PClasses to HTML
	 *
	 * @return string
	 */
	private function renderPClasses ()
	{
		$sString = "";

		foreach ($this->aPClasses as $sPClassId => $aPClassData)
		{
			$sString .= '		 							<li '.($aPClassData['default'] ? 'id="click"' : "").'>
				<div>'.$aPClassData['pclass']->getDescription() . ($aPClassData['monthlyCost'] > 0 ? " - " . $this->getPresentableValuta($aPClassData['monthlyCost']) . " " . $this->fetchFromLanguagePack('per_month') : '') . ($aPClassData['default'] ? '<img src="' . $this->aSetupSettings['path_img'] . 'images/ok.gif" border="0" alt="Chosen" />' : "") . '</div>
				<span style="display: none">'.$sPClassId.'</span>
				</li>';
		}

		return $sString;
	}

	/**
	 * Make the sum shown presentable
	 *
	 * @param	integer	$iSum	The sum to present
	 * @return	string
	 */
	private function getPresentableValuta ($iSum)
	{
		$sBefore	= "";
		$sAfter		= "";

		switch ( $this->sCountryCode ) {
			case 'se':
			case 'no':
			case 'dk':
				$sAfter = " kr";
				break;
			case 'fi';
			case 'de';
			case 'nl';
			$sBefore = "&#8364;";
			break;
		}

		return $sBefore . $iSum . $sAfter;
	}

	/**
	 * Fetch data from the language pack
	 *
	 * @param	string	$sText	The text to fech
	 * @return	string
	 */
	public function fetchFromLanguagePack ($sText, $sISO = null, $sPath = '' )
	{
		if ($sISO == null)
		{
		    if ($this != null && $this->aSetupSettings['langISO'] != null)
			$sISO = strtolower($this->aSetupSettings['langISO']);
			else if ($this != null && $this->sLangISO != null)
			$sISO = strtolower($this->sLangISO);
			else
			$sISO = KlarnaAPI::getISOCode();
		}
		else {
			$sISO	= KlarnaAPI::getISOCode($sISO);
		}


		$oXml	 = simplexml_load_file( ( ( isset( $this->sPath ) && ! is_null( $this->sPath ) ) ? $this->sPath : $sPath ) . 'klarna_files/klarna_language.xml');
		$aResult = (array) @$oXml->xpath("//string[@id='$sText']/$sISO");
		$aResult = (array )@$aResult[0];

		return @$aResult[0];
	}

	/**
	 * Returns the country code for the set country constant.
	 *
	 * @return string
	 */
	public function getISOCode($sCode = null) {
		switch(strtolower($sCode)) {
			case "se":
			case "sv":
				return "sv";
			case "no":
			case "nb":
				return "nb";
			case "dk":
			case "da":
				return "da";
			case "fi":
				return "fi";
			case "de":
				return "de";
			case "nl":
				return "nl";
			case "us":
			case "uk":
			case "en":
			default:
				return "en";
		}
	}
}

/**
 * KlarnaApiException class, only used so it says "KlarnaApiException" instead of Exception.
 *
 * @package   	Klarna Standard Kassa API
 * @author 		Paul Peelen
 * @version 	1.0
 * @since 		1.0 - 14 mar 2011
 * @link		http://integration.klarna.com/
 * @copyright	Copyright (c) 2011 Klarna AB (http://klarna.com)
 */
class KlarnaApiException extends Exception
{
	public function __construct($sMessage, $code=0)
	{
		parent::__construct($sMessage,$code);
	}

	public function __toString()
	{
		return __CLASS__ . ":<p><font style='font-family: Arial, Verdana; font-size: 11px'>[Error: {$this->code}]: {$this->message}</font></p>\n";
	}
}

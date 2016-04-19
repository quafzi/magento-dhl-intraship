<?php
/**
 * Dhl_Intraship_Model_Config
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_Config
{
    /**
     * default language for intraship
     */
    const DEFAULT_INTRASHIP_LANGUAGE = 'DE';

    /**
     * Set up the recommended version for this module.
     *
     * @var string
     */
    const RECOMMENDED_VERSION = '1.4.1.0';

    /*
     * Package codes
     */
    const PACKAGE_EPN = 'epn';
    const PACKAGE_BPI = 'bpi';

    /*
     * Profiles
     */
    const PROFILE_STANDARD         = 'standard';
    const PROFILE_GO_GREEN         = 'go-green';
    const PROFILE_EXPRESS          = 'express';
    const PROFILE_GO_GREEN_EXPRESS = 'go-green-express';

    /*
     * Weight units
     */
    const WEIGHT_UNIT_KG = 'kg';
    const WEIGHT_UNIT_G  = 'g';

    /*
     * Name of the log file
     */
    const LOG_FILE = 'intraship.log';

    /**
     * @var Varien_Simplexml_Element
     */
    protected static $_xml;

    /**
     * @var ArrayObject
     */
    protected static $_mapping;

    /**
     * @var array
     */
    public static $units = array(
        self::WEIGHT_UNIT_KG,
        self::WEIGHT_UNIT_G
    );

    /**
     * @var array
     */
    public static $profiles = array(
        self::PROFILE_STANDARD,
        //self::PROFILE_EXPRESS,
        self::PROFILE_GO_GREEN,
        //self::PROFILE_GO_GREEN_EXPRESS
    );

    /**
     * @var array
     */
    protected static $_packageCodes = array(
        self::PACKAGE_EPN,
        self::PACKAGE_BPI
    );

    /**
     * Get config
     *
     * @param string $index
     * @param mixed  $store Store or storeId
     *
     * @return mixed
     */
    public function getConfig($index, $store=null)
    {
        return Mage::getStoreConfig($this->_getMapping('intraship/' . $index), $store);
    }

    /**
     * Get insall date as string or unix timestamp.
     *
     * @param  boolean          $timestamp  (default = false)
     * @return string|integer   $date
     */
    public function getInstallDate($timestamp = false)
    {
        $date = $this->getConfig('general/install-date');
        if (true === $timestamp):
            $date = strtotime($date);
        endif;
        return $date;
    }

    /**
     * Get account user.
     *
     * @return string
     */
    public function getAccountUser($store=null)
    {
        return $this->getConfig(
            $this->isTestmode($store)
            ? 'account_test/user'
            : 'account/user',
            $store
        );
    }

    /**
     * Get account signature.
     *
     * @return string
     */
    public function getAccountSignature($store=null)
    {
        return $this->getConfig(
            $this->isTestmode($store)
            ? 'account_test/signature'
            : 'account/signature',
            $store
        );
    }

    /**
     * if we've got an account user
     *
     * @return boolean
     */
    public function hasAccountUser($store=null)
    {
        return 0 < strlen(trim($this->getAccountUser($store)));
    }

    /**
     * if we've got an account signature
     *
     * @return boolean
     */
    public function hasAccountSignature($store=null)
    {
        return 0 < strlen(trim($this->getAccountSignature($store)));
    }

    /**
     * if we've got credentials
     *
     * @return boolean
     */
    public function hasCredentials($store=null)
    {
        return ($this->hasAccountUser($store) && $this->hasAccountSignature($store));
    }

    /**
     * Get account ekp.
     *
     * @return string
     */
    public function getAccountEkp($store=null)
    {
        return $this->getConfig(
            $this->isTestmode($store)
            ? 'account_test/ekp'
            : 'account/ekp',
            $store
        );
    }

    /**
     * Get account bank data.
     *
     * @return ArrayObject  $bankData
     */
    public function getAccountBankData($store=null)
    {
        $bankData = new ArrayObject(array());
        $bankData->offsetSet('accountOwner',$this->getConfig('shipper/bank_data_accountOwner', $store));
        $bankData->offsetSet('accountNumber',$this->getConfig('shipper/bank_data_accountNumber', $store));
        $bankData->offsetSet('bankCode', $this->getConfig('shipper/bank_data_bankCode', $store));
        $bankData->offsetSet('bankName', $this->getConfig('shipper/bank_data_bankName', $store));
        $bankData->offsetSet('iban', $this->getConfig('shipper/bank_data_iban', $store));
        $bankData->offsetSet('bic', $this->getConfig('shipper/bank_data_bic', $store));
        $bankData->offsetSet('note', $this->getConfig('shipper/bank_data_note', $store));
        return $bankData;
    }

    /**
     * Get account address.
     *
     * @return ArrayObject
     */
    public function getAccountAddress($store=null)
    {
        return new ArrayObject($this->getConfig('shipper', $store));
    }

    /**
     * Get payment mehtods for cash on delivery (COD).
     *
     * @return ArrayObject
     */
    public function getPaymentMethodsForCod()
    {
        return $this->_toArrayObject($this->getConfig(
            'packages/global_settings_payments-for-cod'));
    }

    /**
     * Use product weight as default value for shipment creation.
     *
     * @return boolean
     */
    public function useProductWeightAsDefault()
    {
        return (1 == $this->getConfig('packages/global_settings_default_weight'));
    }

    /**
     * Get product weight unit
     *
     * @return string
     */
    public function getProductWeightUnit()
    {
        return (string) $this->getConfig(
            'packages/global_settings_default_weight_unit');
    }

    /**
     * Get default weight
     *
     * @param  string   $countryCode
     * @return flaot    $weight
     */
    public function getWeightDefault($countryCode)
    {
        $countryCode = strtoupper($countryCode);
        $weight  = null;
        if (true === $this->isInternationalShipping($countryCode)):
            return $weight;
        endif;
        $profile = $this->getProfileByCountryCode($countryCode);
        if ($profile->offsetExists('weight')):
            $weight = (float) $profile->offsetGet('weight');
        endif;
        return $weight;
    }

    /**
     * Get default profile id.
     *
     * @return string
     */
    public function getProfileDefault()
    {
        return $this->getConfig('packages/global_settings_default-profile');
    }

    /**
     * Get all enabled profiles.
     *
     * @return ArrayObject
     */
    public function getAllEnabledProfiles()
    {
        return $this->_toArrayObject($this->getConfig(
            'packages/global_settings_enabled-profiles'));
    }

    /**
     * Get all available profiles.
     *
     * @return array
     */
    public function getAllProfiles()
    {
        return self::$profiles;
    }

    /**
     * Get customs declaration codes.
     *
     * @return ArrayObject
     */
    public function getCustomsDeclarationCodes()
    {
        return $this->_toArrayObject($this->getConfig(
            'countryCodesCustomsDeclaration'));
    }

    /**
     * Get profile by package code.
     *
     * @see    Dhl_Intraship_Model_Config::PACKAGE_EPN
     * @see    Dhl_Intraship_Model_Config::PACKAGE_BPI
     * @throws Dhl_Intraship_Model_Config_Exception
     * @param  string       $code                       (epn|bpi)
     * @param  int          $storeId
     * @return ArrayObject  $profile
     */
    public function getProfileByPackageCode($code = self::PACKAGE_EPN, $storeId = null)
    {
        $profile = null;
        // Throw new exception if given code not exists in config.xml.
        if (!$this->getConfig($code, $storeId)):
            throw new Dhl_Intraship_Model_Config_Exception(sprintf(
                'package code "%s" is invalid.', $code));
        endif;
        $profile = clone new ArrayObject($this->getConfig($code, $storeId));
        // Convert country codes values to array object.
        if ($profile->offsetExists('countryCodes') &&
            !$profile->offsetGet('countryCodes') instanceof ArrayObject
        ):
            $profile->offsetSet('countryCodes', $this->_toArrayObject(
                $profile->offsetGet('countryCodes')));
        else:
            $profile->offsetSet('countryCodes', null);
        endif;
        // Convert enabled profile values to array object.
        if (!$profile->offsetGet('enabled-profiles') instanceof ArrayObject):
            $profile->offsetSet('enabled-profiles', $this->_toArrayObject(
                $profile->offsetGet('enabled-profiles')));
        endif;
        return $profile;
    }

    /**
     * Get profile by country code (ISO).
     *
     * @see    Dhl_Intraship_Model_Config::PROFILE_STANDARD
     * @see    Dhl_Intraship_Model_Config::PROFILE_GOGREEN
     * @see    Dhl_Intraship_Model_Config::PROFILE_EXPRESS
     * @see    Dhl_Intraship_Model_Config::PROFILE_GOGREEN_EXPRESS
     * @throws Dhl_Intraship_Model_Config_Exception
     * @param  string       $countryCode       (ISO)
     * @param  string|null  $profileName
     * @param  int          $storeId
     * @return ArrayObject  $profile
     */
    public function getProfileByCountryCode($countryCode, $profileName = null, $storeId = null)
    {
        $countryCode = strtoupper($countryCode);
        $match = null;
        foreach (self::$_packageCodes as $code):
            $pack = $this->getProfileByPackageCode($code, $storeId);
            // Find the right package for the given country code.
            if ($pack->offsetExists('countryCodes') &&
                $pack->offsetGet('countryCodes') instanceof ArrayObject
            ):
                if ($pack->offsetGet('countryCodes')->offsetExists($countryCode)):
                    $match = $pack;
                endif;
            endif;
        endforeach;
        // Use BPI package while receiver country is not germany or europe.
        if (null === $match):
            return null;
        endif;
        // Get clean object.
        $profile = new ArrayObject();
        $profile->offsetSet('code', $match->offsetGet('code'));
        $profile->offsetSet('weight', $match->offsetGet('weight'));
        $profile->offsetSet('enabled-profiles',
            $match->offsetGet('enabled-profiles'));
        // Unset unsupported shipment types.
        foreach ($match->offsetGet('enabled-profiles') as $shipment => $value):
            $profile->offsetGet('enabled-profiles')->offsetSet(
                $shipment, $match->offsetGet($shipment));
        endforeach;
        // Set current profile id to result object.
        $partnerId = null;
        if (null !== $profileName):
            if (!in_array($profileName, self::$profiles)):
                throw new Dhl_Intraship_Model_Config_Exception(sprintf(
                    'profile "%s" is invalid.', $profileName));
            endif;
            // Set partner id to the correct value,
            // if is enabled and in $profileName
            if ($profile->offsetGet('enabled-profiles')->offsetExists($profileName)):
                $partnerId = $profile->offsetGet('enabled-profiles')
                    ->offsetGet($profileName);
            endif;
        endif;
        $profile->offsetSet('partnerId', $partnerId);
        // Unset match.
        unset($match);
        return $profile;
    }

    /**
     * Get profiles by country code (ISO).
     *
     * @param  string       $countryCode
     * @return ArrayObject
     */
    public function getShipmentTypes($countryCode)
    {
        $countryCode = strtoupper($countryCode);
        return $this->getProfileByCountryCode($countryCode)
            ->offsetGet('enabled-profiles');
    }

    /**
     * Get URL to DHL backend.
     *
     * @return string
     */
    public function getBackendUrl($store = null)
    {
        $url = $this->getConfig('url/login_production');
        if (true === $this->isTestmode($store)) {
            $url = $this->getConfig('url/login_test');
        }

        return $url .'?'. $this->getBackendUrlParams($store);
    }

    /**
     * return params for backend url
     *
     * @param $store
     *
     * @return string
     */
    protected function getBackendUrlParams($store)
    {
        $params = array(
            'login' =>  $this->getAccountUser($store),
            'pwd'   =>  $this->getAccountSignature($store),
            'LANGUAGE'=> self::DEFAULT_INTRASHIP_LANGUAGE
        );
        return http_build_query($params);
    }

    /**
     * Get WSDL
     *
     * @return string
     */
    public function getSoapWsdl($store = null)
    {
        return $this->getConfig('url/wsdl', $store);
    }

    /**
     * Obtain the Intraship web service endpoint.
     *
     * $param mixed $store
     * @param bool $production Indicate whether to retrieve production or sandbox endpoint.
     * @return string
     */
    public function getWebserviceEndpoint($store = null, $production = null)
    {
        if (false === $production) {
            return $this->getConfig('url/endpoint_sandbox', $store);
        }

        if (true === $production) {
            return $this->getConfig('url/endpoint_production', $store);
        }

        // production parameter not given, check test mode setting
        if ($this->isTestmode($store)) {
            return $this->getConfig('url/endpoint_sandbox', $store);
        }

        return $this->getConfig('url/endpoint_production', $store);
    }

    /**
     * Obtain application wide HTTP Basic auth credentials (username)
     * @return string
     */
    public function getWebserviceAuthUsername()
    {
        return $this->getConfig('webservice/auth_username');
    }

    /**
     * Obtain application wide HTTP Basic auth credentials (password)
     * @return string
     */
    public function getWebserviceAuthPassword()
    {
        return $this->getConfig('webservice/auth_password');
    }

    /**
     * Get Soap Encoding
     *
     * @return string
     */
    public function getSoapEncoding()
    {
        return $this->getConfig('soap/encoding');
    }

    /**
     * Get PDF level
     *
     * @return string
     */
    public function getPdfLevel()
    {
        return 3;
    }

    /**
     * Get PDF level
     *
     * @return string
     */
    public function getPdfBaseDir()
    {
    	return Mage::getBaseDir().DS.'var'.DS.'intraship'.DS.'documents'.DS;
    }

    /**
     * Is testmode on?
     *
     * @return boolean
     */
    public function isTestmode($store=null)
    {
        return (1 == $this->getConfig('general/testmode', $store));
    }

    /**
     * Get mode (test|prod)
     *
     * @return string ("test"|"prod")
     */
    public function getMode($store=null)
    {
        return $this->isTestmode($store) ? 'test' : 'prod';
    }

    /**
     * Is submodule enabled?
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return (1 == $this->getConfig('general/active'));
    }

    /**
     * Query if receiver is out of europe.
     *
     * @param  string  $countryCode
     * @return boolean
     */
    public function isInternationalShipping($countryCode)
    {
        $countrycode = strtoupper($countryCode);
        return (null === $this->getProfileByCountryCode($countryCode));
    }

    /**
     * get label format
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->getConfig('label/paper_format');
    }

    /**
     * get label margins
     *
     * @return array
     */
    public function getMargins()
    {
        return array(
            'left'  => $this->getConfig('label/margin_left'),
            'top'   => $this->getConfig('label/margin_top')
        );
    }

    /**
     * Is autocreate enabled?
     *
     * @return boolean
     */
    public function isAutocreate()
    {
        return (1 == $this->getConfig('autocreate/autocreate_enabled'));
    }

    /**
     * Display semi-autocreate button.
     *
     * @return boolean
     */
    public function displayAutocreateButton()
    {
        return (1 == $this->getConfig('autocreate_button/autocreate_enabled'));
    }

    /**
     * Get allowed payment methods for autocreate
     *
     * @deprecated 13.11.28
     * @see Dhl_Intraship_Model_Config::getAutocreateAllowedPaymentMethods()
     * @return ArrayObject
     */
    public function getAutocreatePaymentMethods()
    {
        return $this->_toArrayObject($this->getConfig(
            'autocreate/autocreate_allowed-payments'));
    }

    /**
     * Get allowed order status codes
     *
     * @deprecated 13.11.28
     * @see Dhl_Intraship_Model_Config::getAutocreateAllowedStatusCodes()
     * @return ArrayObject
     */
    public function getAutocreateStatusCodes()
    {
        return $this->_toArrayObject($this->getConfig(
            'autocreate/autocreate_allowed-status-codes'));
    }

    /**
     * Obtain comma-separated list of payment method codes that apply for shipment auto creation.
     *
     * @return string
     */
    public function getAutocreateAllowedPaymentMethods()
    {
        $path = 'intraship/autocreate/autocreate_allowed-payments';
        $methodCodes = Mage::getStoreConfig($path);
        if (null === $methodCodes) {
            $methodCodes = '';
        }
        return $methodCodes;
    }

    /**
     * Obtain comma-separated list of order status codes that apply for shipment auto creation.
     *
     * @return string
     */
    public function getAutocreateAllowedStatusCodes()
    {
        $path = 'intraship/autocreate/autocreate_allowed-status-codes';
        $statusCodes = Mage::getStoreConfig($path);
        if (null === $statusCodes) {
            $statusCodes = '';
        }
        return $statusCodes;
    }

    /**
     * Is autocreate notification enabled?
     *
     * @return boolean
     */
    public function isAutocreateNotification()
    {
        return (1 == $this->getConfig('autocreate/autocreate_notify'));
    }

    /**
     * Get notification message.
     *
     * @param mixed  $store Store or storeId
     *
     * @return string
     */
    public function getAutocreateNotificationMessage($store=null)
    {
        return (string) $this->getConfig('autocreate/autocreate_message', $store);
    }

    /**
     * Get tracking notification message
     *
     * @return boolean
     */
    public function isTrackingNotification()
    {
        return (1 == $this->getConfig('notification/tracking_notification'));
    }

    /**
     * Get tracking notification message
     *
     * @return string
     */
    public function getTrackingNotificationMessage($store=null)
    {
        return (string) $this->getConfig('notification/tracking_notification_message',$store);
    }

    /**
     * Get autocreate settings for intraship,
     * multipack and personally is not possible
     *
     * @param  string       $countryCode
     * @return ArrayObject  $return
     */
    public function getAutocreateSettings($countryCode)
    {
        $countryCode = strtoupper($countryCode);
        $settings = new ArrayObject(array());
        // Append default profile from config.xml.
        $settings->offsetSet('profile', $this->getProfileDefault());
        // Append insurance from config.xml.
        $settings->offsetSet('insurance', $this->getConfig(
            'autocreate/autocreate_insurance'));
        // Append personally.
        $settings->offsetSet('personally', 0);
        // Append bulkfreight from config.xml.
        $settings->offsetSet('bulkfreight', $this->getConfig(
            'autocreate/autocreate_bulkfreight'));
        return $settings;
    }

    /**
     * Is go green in checkout enabled?
     *
     * @return boolean
     */
    public function isCheckoutGoGreen()
    {
        return (1 == $this->getConfig('checkout/gogreen_enabled'));
    }

    /**
     * Get label HTML for go green in checkout.
     *
     * @return string
     */
    public function getCheckoutGoGreenLabel()
    {
        return $this->getConfig('checkout/gogreen_label');
    }

    /**
     * Return TRUE if Mage::getVersion() is lower than 1.4.0.0
     *
     * @return boolean
     */
    public function isVersionRecommendedOrLarger()
    {
        return (
            (int) implode(null, explode('.', Mage::getVersion())) >=
            (int) implode(null, explode('.', self::RECOMMENDED_VERSION))
        );
    }

    /**
     * Create and return config mapping for magento version lower than 1.4.0.0.
     *
     * @param  string|null                              $index
     * @return ArrayObject|sting
     */
    protected function _getMapping($index = null)
    {
    	return $index;
    	/*
        // Return given index name if version is larger than 1.4.0.0
        if (true === $this->isVersionRecommendedOrLarger()):
            return $index;
        endif;
        // Get index name from mapping.
        if (!self::$_mapping instanceof ArrayObject):
            self::$_mapping = new ArrayObject(array());
            foreach ($this->_loadSystemXml() as $groups):
                foreach ($groups as $name => $group):
                    foreach ($group->fields as $fields):
                        foreach ($fields as $field => $attribute):
                            $path = (string) $attribute->config_path;
                            if (!$path) continue;
                            self::$_mapping->offsetSet($path, sprintf(
                                'intraship/%s/%s', $name, $field));
                        endforeach;
                    endforeach;
                endforeach;
            endforeach;
        endif;
        // Return value for given index.
        if (null !== $index):
            if (false === self::$_mapping->offsetExists($index)):
                // Write message to log.
                $log = sprintf('Undefined index "%s" on mapping table.', $index);
                Mage::log($log, Zend_Log::NOTICE);
                // Return the origin index.
                return $index;
            endif;
            return self::$_mapping->offsetGet($index);
        else:
            return self::$_mapping;
        endif;
        */
    }

    /**
     * Load system xml
     *
     * @throws Dhl_Intraship_Model_Config_Exception
     * @return Varien_Simplexml_Element
     */
    protected function _loadSystemXml()
    {
        if (!self::$_xml instanceof Varien_Simplexml_Element):
            $file = realpath(sprintf('%s%s..%setc%ssystem.xml',
                dirname(__FILE__), DS, DS, DS));
            $xml = simplexml_load_file($file, 'Varien_Simplexml_Element');
            if (!$xml instanceof Varien_Simplexml_Element):
                throw new Dhl_Intraship_Model_Config_Exception(sprintf(
                    'Failed to load dhl intraship config.xml on "%s".', $file));
            endif;
            self::$_xml = $xml->sections->intraship->groups;
        endif;
        return self::$_xml;
    }

    /**
     * Create array object from coma separated value.
     *
     * @param  string       $value
     * @param  string       $delimiter
     * @return ArrayObject  $result
     */
    protected function _toArrayObject($value, $delimiter = ',')
    {
        $result = new ArrayObject(array());
        $data   = explode($delimiter, $value);
        if (is_array($data)):
            foreach ($data as $entry):
                $entry = trim($entry);
                if (!strlen($entry)) continue;
                $result->offsetSet($entry, $entry);
            endforeach;
        endif;
        return $result;
    }

    /**
     * Check if COD Charge should be removed from order grand total
     *
     * @return boolean
     */
    public function removeCODCharge()
    {
        return (1 == $this->getConfig('packages/remove_cod_charge'));
    }

    /**
     * get COD Charge
     *
     * @return float
     */
    public function getCODCharge()
    {
        return (float) $this->getConfig('general/cod-charge');
    }

    /**
     * get name of the logging file
     *
     * @return string
     */
    public function getLogfile()
    {
        return self::LOG_FILE;
    }

    /**
     * Get deactivated shipping methods for autocreate
     *
     * @return ArrayObject
     */
    public function getDisabledShippingMethods()
    {
        return $this->getConfig('packages/disabled_shipping_methods');
    }

    /**
     * Check if shipping method is disabled
     *
     * @param  string $shippingCode
     * @return boolean
     */
    public function isAllowedShippingMethod($shippingCode)
    {
        $disabledShippingMethods = explode(",", $this->getDisabledShippingMethods());
        if (in_array(
            $shippingCode,
            $disabledShippingMethods)):
            return false;
        else:
            return true;
        endif;
    }

    /**
     * get allowed product types for weight calculation
     *
     * @return string
     */
    public function getProductTypesForWeightCalculation()
    {
        return explode(",", $this->getConfig('packages/global_settings_weight_product_types'));
    }

    /**
     * return the tracking url from the config.xml
     *
     * @return string
     */
//    public function getTrackingUrl()
//    {
//        return (string) $this->getconfig('tracking/url');
//    }


    /**
     * get tracking link (pattern or for given parcel
     *
     * @param Netresearch_Hermes_Model_Parcel|string  $parcel_or_orderNo  Parcel or hermesOrderNo
     * @return string|null Tracking Url, NULL if hermesOrderNo of given parcel is empty
     */
    public function getTrackingUrl($orderNo=null)
    {

        $link = '';
        if (is_string($orderNo) && 0 < strlen($orderNo)) {
            $link = Mage::getStoreConfig('intraship/tracking/url');
            $link = str_replace('%orderNo%', $orderNo, $link);
        }
        return $link;
    }
}
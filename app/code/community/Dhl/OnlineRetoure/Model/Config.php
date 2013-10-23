<?php
/**
 * Dhl OnlineRetoure
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to
 * newer versions in the future.
 *
 * @category    Dhl
 * @package     Dhl_OnlineRetoure
 * @copyright   Copyright (c) 2013 Netresearch GmbH & Co. KG (http://www.netresearch.de/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * DHL OnlineRetoure Config Model
 *
 * @category    Dhl
 * @package     Dhl_OnlineRetoure
 * @author      André Herrn <andre.herrn@netresearch.de>
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Dhl_OnlineRetoure_Model_Config
{
    const ONLINERETOURE_LABEL_FORMAT = 'PDF';

    /**
     * Check if online return is enabled.
     *
     * @param mixed $storeId
     * @return boolean
     */
    public function isEnabled($storeId = null)
    {
        return (bool)Mage::getStoreConfig('intraship/dhlonlineretoure/active', $storeId);
    }

    /**
     * Check if online return logging is enabled.
     *
     * @param mixed $storeId
     * @return boolean
     */
    public function isLoggingEnabled($storeId = null)
    {
        return (bool)Mage::getStoreConfig('intraship/dhlonlineretoure/logging_enabled', $storeId);
    }

    /**
     * Retrieve online return portal ID.
     *
     * @param mixed $storeId
     * @return string
     */
    public function getPortalId($storeId = null)
    {
        $portalId = Mage::getStoreConfig('intraship/dhlonlineretoure/portal_id', $storeId);
        if (!$portalId) {
            return '';
        }
        return $portalId;
    }

    /**
     * Obtain user for online return label request.
     *
     * @param mixed $storeId
     * @return string
     */
    public function getUser($storeId = null)
    {
        $user = Mage::getStoreConfig('intraship/dhlonlineretoure/user', $storeId);
        if (!$user) {
            return '';
        }
        return $user;
    }

    /**
     * Obtain password for online return label request.
     *
     * @param mixed $storeId
     * @return string
     */
    public function getPassword($storeId = null)
    {
        $password = Mage::getStoreConfig('intraship/dhlonlineretoure/password', $storeId);
        if (!$password) {
            return '';
        }
        return $password;
    }

    /**
     * Obtain CMS page url key
     *
     * @param mixed $storeId
     * @return string
     */
    public function getCmsRevocationPage($storeId = null)
    {
        $page = Mage::getStoreConfig('intraship/dhlonlineretoure/cms_revocation_page', $storeId);
        if (!$page) {
            return '';
        }
        return $page;
    }

    /**
     * Get delivery name config value by ISO 3166 ALPHA-2 country ID.
     *
     * @param string $iso2Code
     * @param mixed $storeId
     * @return string Delivery name if available for given country, empty string otherwise.
     * @throws Exception
     * @link http://www.iso.org/iso/country_codes/iso_3166_code_lists/country_names_and_code_elements.htm
     */
    public function getDeliveryNameByCountry($iso2Code, $storeId = null)
    {
        if (!is_string($iso2Code) || (strlen($iso2Code) != 2)) {
            throw new Exception('Please provide valid two-character country code.');
        }

        $deliverynames = unserialize(Mage::getStoreConfig('intraship/dhlonlineretoure/delivery_names', $storeId));
        if (!is_array($deliverynames)) {
            return '';
        }

        foreach ($deliverynames as $data) {
            if (strcasecmp($data['iso'], $iso2Code) === 0) {
                return $data['name'];
            }
        }

        return '';
    }

    /**
     * Obtain WSDL URI from config.
     *
     * @param mixed $storeId
     * @return string
     */
    public function getWsdlUri($storeId = null)
    {
        $wsdl = Mage::getStoreConfig('intraship/dhlonlineretoure/wsdl', $storeId);
        if (!$wsdl) {
            return '';
        }
        return $wsdl;
    }

    /**
     * Return all country codes from intraship config
     */
    public function getAllowedCountryCodes()
    {
        $countryCodes = sprintf(
            '%s,%s',
            Mage::getStoreConfig('intraship/epn/countryCodes'),
            Mage::getStoreConfig('intraship/bpi/countryCodes')
        );

        return explode(',', $countryCodes);
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedShippingMethods()
    {
        return explode(",", Mage::getStoreConfig('intraship/dhlonlineretoure/allowed_shipping_methods'));
    }

    /**
     * Check if shipping method is allowed
     *
     * @param  string $shippingCode
     * @return boolean
     */
    public function isAllowedShippingMethod($shippingCode)
    {
        if (in_array(
            $shippingCode,
            $this->getAllowedShippingMethods())) {
            return true;
        } else {
            return false;
        }
    }
}

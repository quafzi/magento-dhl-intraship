<?php

/**
 * Dhl Account
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
 * @category    Netresearch
 * @package     Dhl_Account
 * @copyright   Copyright (c) 2012 Netresearch GmbH & Co. KG (http://www.netresearch.de/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Dhl_Account
 *
 * @category    Netresearch
 * @package     Dhl_Account
 * @author      Michael Lühr <michael.luehr@netresearch.de>
 */
class Dhl_Account_Model_Quote_Address extends Mage_Sales_Model_Quote_Address
{

    /**
     * validates the address
     *
     * @return boolean | array true if validation is passed, otherwise the error messages
     */
    public function validate()
    {
        $errors = parent::validate();
        if (true === $errors) {
            $errors = array();
        }
        if ($this->getAddressType() == self::TYPE_BILLING) {
            if (preg_match('/pac?k(et)?station/i', $this->getStreetFull())) {
                $errors[] = Mage::helper('customer')->__('No parcel pick up machines are allowed in billing address. To send to a parcel pick up machine you should enter it as shipping address.');
            }
        }
        if ($this->getAddressType() == self::TYPE_SHIPPING) {
            if (!is_null($this->getShipToPackstation())
                && $this->getShipToPackstation() == Dhl_Account_Model_Config::SHIP_TO_PACKSTATION) {
                if (Mage::getModel('dhlaccount/config')->isPackstationEnabled() && !preg_match('/^(packstation\s){0,1}\d{3,3}$/i',trim($this->getStreetFull()))) {
                    $errors[] = Mage::helper('customer')->__('Only 3 digits are allowed for packstations.');
                }
                if ((Mage::getModel('dhlaccount/config')->isPackstationEnabled()
                    || Mage::getModel('dhlaccount/config')->isPreferredDeliveryDateEnabled())
                     && 0 < strlen(trim($this->getDhlaccount()))
                     && !preg_match('/^\d{6,10}$/i',trim($this->getDhlaccount()))) {
                    $errors[] = Mage::helper('customer')->__('Only 6 to 10 digits are allowed for DHL account number') . ' '. $this->getDhlaccount() . '.';
                }
            }
            if (Mage::getModel('dhlaccount/config')->isPackstationEnabled()
                && !is_null($this->getQuote())
                && !is_null($this->getQuote()->getBillingAddress())
                && !is_null($this->getQuote()->getBillingAddress()->getDhlaccount())
                && 0 < strlen($this->getQuote()->getBillingAddress()->getDhlaccount())) {

                if (false === $this->addressesAreEqual($this, $this->getQuote()->getBillingAddress())) {
                    $errorMessage = Mage::helper('customer')->__('If a different shipping address is used, the parcel announcement will be skipped.');
                    if (0 == count($errors)) {
                        $errorMessage = array('message' => Mage::helper('customer')->__('If a different shipping address is used, the parcel announcement will be skipped.')
											.' ' .Mage::helper('customer')->__("Click 'OK' to skip the parcel announcement, click 'cancel' to edit your shipping address."), 'showConfirm' => 1);
                    }
                    $errors[] = $errorMessage;
                }
            }
        }
        if (empty($errors) || $this->getShouldIgnoreValidation()) {
                return true;
        }
        return $errors;
    }

    /**
     *
     * compares two addresses
     *
     * @param Mage_Sales_Model_Quote_Address $address1 - first address to compare
     * @param Mage_Sales_Model_Quote_Address $address2 - second address to compare
     * @return boolean - true if both addresses are equal, false otherwise
     */
    private function addressesAreEqual(Mage_Sales_Model_Quote_Address $address1, Mage_Sales_Model_Quote_Address $address2)
    {
        $comparismResult = false;
        $street1 = $this->normalizeStreet($address1->getStreetFull());
        $street2 = $this->normalizeStreet($address2->getStreetFull());
        $city1 = $this->normalizeStreet($address1->getCity());
        $city2 = $this->normalizeStreet($address2->getCity());
        $zipCode1 = $address1->getPostcode();
        $zipCode2 = $address2->getPostcode();
        if ($street1 == $street2 && $zipCode1 == $zipCode2 && $city1 == $city2) {
            $comparismResult = true;
        }
        return $comparismResult;
    }


    /**
     *
     * removes spaces,
     *
     * @param type $stringToNormalize
     * @return type
     */
    private function normalizeStreet($stringToNormalize)
    {
        $resultString = trim(strtolower($stringToNormalize));
        $resultString = str_replace('strasse', 'str', $resultString);
        $resultString = str_replace('straße', 'str', $resultString);
        $resultString = str_replace(' ', '', $resultString);
        $resultString = str_replace('-', '', $resultString);
        return $resultString;
    }

}

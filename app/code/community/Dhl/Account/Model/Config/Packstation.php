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
 * @category    Dhl
 * @package     Dhl_Account
 * @copyright   Copyright (c) 2013 Netresearch GmbH & Co. KG (http://www.netresearch.de/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * DHL Packstation Config Model
 *
 * @category    Dhl
 * @package     Dhl_Account
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Dhl_Account_Model_Config_Packstation extends Dhl_Account_Model_Config
{
    /**
     * Obtain the Packstation web service endpoint.
     *
     * @param bool $production Indicate whether to retrieve production or sandbox endpoint.
     * @return string
     */
    public function getWebserviceEndpoint($production = null)
    {
        if (false === $production) {
            return Mage::getStoreConfig('intraship/packstation/endpoint_sandbox');
        }

        if (Mage::helper('core')->isModuleEnabled('Dhl_Intraship')
             && Mage::getModel('intraship/config')->isTestmode()) {
            return Mage::getStoreConfig('intraship/packstation/endpoint_sandbox');
        }

        return Mage::getStoreConfig('intraship/packstation/endpoint_production');
    }
}

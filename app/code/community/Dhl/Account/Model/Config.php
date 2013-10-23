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
 * @copyright   Copyright (c) 2012 Netresearch GmbH & Co. KG (http://www.netresearch.de/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * DHL Account Config Model
 *
 * @category    Dhl
 * @package     Dhl_Account
 * @author      Michael Lühr <michael.luehr@netresearch.de>
 */
class Dhl_Account_Model_Config
{


    const SHIP_TO_PACKSTATION = 1;

    /**
     * gets the url for packstation finder service
     * @return String the url of packstation finder
     */
    public function getPackstationFinderUrl()
    {
        return Mage::getStoreConfig('intraship/url/packstationfinder_url');
    }

    /**
     * is the packstation service active or not
     *
     * @return boolean - true if the packstation service is enabled, false otherwise
     */
    public function isPackstationEnabled()
    {
        return (bool)Mage::getStoreConfig('intraship/packstation/active');
    }


    /**
     * is the parcel announcement service active or not
     *
     * @return boolean - true if the parcel announcement service is enabled, false otherwise
     */
    public function isParcelAnnouncementEnabled()
    {
        return (bool)Mage::getStoreConfig('intraship/dhlaccount/active');
    }

}

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
 * DHL Account Config Model Test
 *
 * @category    Dhl
 * @package     Dhl_Account
 * @author      Michael Lühr <michael.luehr@netresearch.de>
 */
class Dhl_Account_Test_Model_ConfigTest extends EcomDev_PHPUnit_Test_Case
{

    /**
     * @var Dhl_Account_Model_Config
     */
    protected $config;

    public function setUp()
    {
        $this->store  = Mage::app()->getStore(0)->load(0);
        $this->config = Mage::getModel('dhlaccount/config');
        parent::setUp();
    }

    public function testIsPackstationEnabled()
    {
        $this->store->setConfig('intraship/packstation/active', true);
        $this->assertTrue($this->config->isPackstationEnabled());

        $this->store->resetConfig();
        $this->store->setConfig('intraship/packstation/active', false);
        $this->assertFalse($this->config->isPackstationEnabled());
    }


    public function testIsPreferredDeliveryDateEnabled()
    {
        $this->store->setConfig('intraship/dhlaccount/active', true);
        $this->assertTrue($this->config->isPreferredDeliveryDateEnabled());

        $this->store->resetConfig();
        $this->store->setConfig('intraship/dhlaccount/active', false);
        $this->assertFalse($this->config->isPreferredDeliveryDateEnabled());
    }

    public function testIsParcelAnnouncementEnabled()
    {
        $this->store->setConfig('intraship/parcel_announcement/active', true);
        $this->assertTrue($this->config->isParcelAnnouncementEnabled());

        $this->store->resetConfig();
        $this->store->setConfig('intraship/parcel_announcement/active', false);
        $this->assertFalse($this->config->isParcelAnnouncementEnabled());
    }

    public function testGetWebserviceAuthPassword()
    {
        $this->store->setConfig('intraship/webservice/auth_password','1234');
        $this->assertEquals('1234',$this->config->getWebserviceAuthPassword());
        $this->assertNotEquals('123',$this->config->getWebserviceAuthPassword());
    }

    public function testGetWebserviceAuthUsername()
    {
        $this->store->setConfig('intraship/webservice/auth_username','Karl');
        $this->assertEquals('Karl',$this->config->getWebserviceAuthUsername());
        $this->assertNotEquals('Marx',$this->config->getWebserviceAuthUsername());
    }

}

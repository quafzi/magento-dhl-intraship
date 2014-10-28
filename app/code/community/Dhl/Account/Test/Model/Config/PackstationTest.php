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
 * DHL Account Model Config Packstation Test
 *
 * @category    Dhl
 * @package     Dhl_Account
 * @author      Sebastian Ertner <sebastian.ertner@netresearch.de>
 */
class Dhl_Account_Test_Model_Config_PackstationTest extends EcomDev_PHPUnit_Test_Case
{

    /**
     * @var Dhl_Account_Model_Config
     */
    protected $config;

    public function setUp()
    {
        $this->store = Mage::app()->getStore(0)->load(0);
        $this->config = Mage::getModel('dhlaccount/config');
        parent::setUp();
    }

    public function testGetWebserviceEndpoint()
    {
        $this->store->setConfig('intraship/packstation/endpoint_sandbox', 'www.example-sandbox.com');

        $packstationModel = Mage::getModel('dhlaccount/config_packstation');
        $this->assertEquals('www.example-sandbox.com', $packstationModel->getWebserviceEndpoint(false));

        $helperMock = $this->getHelperMock('core/data', array('isModuleEnabled'));
        $helperMock->expects($this->any())
            ->method('isModuleEnabled')
            ->will($this->returnValue(true));

        $modelMock = $this->getModelMock('intraship/config', array('isTestmode'));
        $modelMock->expects($this->any())
            ->method('isTestmode')
            ->will($this->returnValue(true));

        $this->assertEquals('www.example-sandbox.com', $packstationModel->getWebserviceEndpoint());
    }

    public function testGetWebserviceEndpointForProduction()
    {
        $this->store->setConfig('intraship/packstation/endpoint_production', 'www.example-production.com');

        $helperMock = $this->getHelperMock('core/data', array('isModuleEnabled'));
        $helperMock->expects($this->any())
            ->method('isModuleEnabled')
            ->will($this->returnValue(false));

        $this->replaceByMock('helper', 'core', $helperMock);

        $modelMock = $this->getModelMock('intraship/config', array('isTestmode'));
        $modelMock->expects($this->any())
            ->method('isTestmode')
            ->will($this->returnValue(false));

        $this->replaceByMock('model', 'intraship/config', $modelMock);
        $packstationModel = Mage::getModel('dhlaccount/config_packstation');
        $this->assertEquals('www.example-production.com', $packstationModel->getWebserviceEndpoint(true));
    }


}

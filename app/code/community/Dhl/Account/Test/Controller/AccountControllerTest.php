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
 * Dhl Account Controller Test
 *
 * @category    Netresearch
 * @package     Dhl_Account
 * @author      Michael Lühr <michael.luehr@netresearch.de>
 */
class Dhl_Account_Test_Controller_AccountControllerTest
    extends EcomDev_PHPUnit_Test_Case_Controller
{

  public function testCountryCodeAction()
    {
        $countryModel = new Varien_Object();
        $countryModel->setIso2Code('DE');
        $address = new Varien_Object();
        $address->setCountryModel($countryModel);
        $modelMock = $this->getModelMock('customer/address', array('load'));
        $modelMock->expects($this->any())
            ->method('load')
            ->will($this->returnValue($address));
        $this->replaceByMock('model', 'customer/address', $modelMock);
        $this->dispatch('dhlaccount/account/countrycode');
        $this->assertRequestRoute('dhlaccount/account/countrycode');
        $this->assertEquals('DE', $this->getResponse()->getOutputBody());
        
    }  
    
    public function testPackstationdataAction()
    {
        $clientMock = $this->getModelMock('dhlaccount/client_http', array('getPackstationData'));
        $clientMock->expects($this->any())
            ->method('getPackstationData')
            ->will($this->returnValue('foo'));
        $this->replaceByMock('model', 'dhlaccount/client_http', $clientMock);
        $this->getRequest()->setMethod('POST');
         // simulate request data
        $this->getRequest()->setPost('zipcode', '04103');
        $this->getRequest()->setPost('city', 'Leipzig');
        $this->dispatch('dhlaccount/account/packstationdata');
        $this->assertRequestRoute('dhlaccount/account/packstationdata');
        $this->assertEquals('"foo"', $this->getResponse()->getOutputBody());
    }

}

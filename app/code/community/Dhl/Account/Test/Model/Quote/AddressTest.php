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
 * Dhl Account Addres Unit Test
 *
 * @category    Dhl
 * @package     Dhl_Account
 * @author      Michael Lühr <michael.luehr@netresearch.de>
 */
class Dhl_Account_Test_Model_Quote_AddressTest extends EcomDev_PHPUnit_Test_Case
{

    protected $model = null;


    protected function setUp()
    {
        parent::setUp();
        // setting up a valid address, only test for street validation later
        $this->model = Mage::getModel('dhlaccount/quote_address');
        $this->model->setFirstname('Homer J.');
        $this->model->setLastname('Simpson');
        $this->model->setCity('Springfield');
        $this->model->setPostcode('4711');
        $this->model->setTelephone('0815');
        $this->model->setRegionId('Somewhere');
        $this->model->setCountryId(1);
        $this->model->setAddressType(Dhl_Account_Model_Quote_Address::TYPE_BILLING);
        $this->store  = Mage::app()->getStore(0)->load(0);
        $this->store->setConfig('intraship/dhlaccount/active', true);
        $this->store->setConfig('intraship/packstation/active', true);
    }


    /**
     * test for address validation
     *
     * @test
     */
    public function testStreetIsInvalid()
    {
        // validation should fail due to PACKSTATION in street1
        $this->model->setStreet("PACKSTATION");
        $this->assertTrue(is_array($this->model->validate()));
        $this->assertEquals(Mage::helper('customer')->__('No parcel pick up machines are allowed in billing address. To send to a parcel pick up machine you should enter it as shipping address.'), current($this->model->validate()));

        // validation should fail due to packstation in street1
        $this->model->setStreet("packstation");
        $this->assertTrue(is_array($this->model->validate()));
        $this->assertEquals(Mage::helper('customer')->__('No parcel pick up machines are allowed in billing address. To send to a parcel pick up machine you should enter it as shipping address.'), current($this->model->validate()));

        // validation should fail due to packstation in street2
        $this->model->setStreet("Irgendwo im Nirgendwo \n packstation");
        $this->assertTrue(is_array($this->model->validate()));
        $this->assertEquals(Mage::helper('customer')->__('No parcel pick up machines are allowed in billing address. To send to a parcel pick up machine you should enter it as shipping address.'), current($this->model->validate()));

        // validation should fail due to packstation in street3
        $this->model->setStreet("Irgendwo im Nirgendwo \n bei einer \n packstation");
        $this->assertTrue(is_array($this->model->validate()));
        $this->assertEquals(Mage::helper('customer')->__('No parcel pick up machines are allowed in billing address. To send to a parcel pick up machine you should enter it as shipping address.'), current($this->model->validate()));

        // validation should fail due to packstation in street4
        $this->model->setStreet("Irgendwo im Nirgendwo \n bei einer \n evergreen terrace \n packstation");
        $this->assertTrue(is_array($this->model->validate()));
        $this->assertEquals(Mage::helper('customer')->__('No parcel pick up machines are allowed in billing address. To send to a parcel pick up machine you should enter it as shipping address.'), current($this->model->validate()));

        // validation should fail due to packstation in street4
        $this->model->setStreet("Irgendwo im Nirgendwo \n bei einer \n evergreen terrace \n pakstation");
        $this->assertTrue(is_array($this->model->validate()));
        $this->assertEquals(Mage::helper('customer')->__('No parcel pick up machines are allowed in billing address. To send to a parcel pick up machine you should enter it as shipping address.'), current($this->model->validate()));

        // validation should fail due to packstation in street4
        $this->model->setStreet("Irgendwo im Nirgendwo \n bei einer \n evergreen terrace \n packetstation");
        $this->assertTrue(is_array($this->model->validate()));
        $this->assertEquals(Mage::helper('customer')->__('No parcel pick up machines are allowed in billing address. To send to a parcel pick up machine you should enter it as shipping address.'), current($this->model->validate()));

        // validation should fail due to packstation in street4
        $this->model->setStreet("Irgendwo im Nirgendwo \n bei einer \n evergreen terrace \n paketstation");
        $this->assertTrue(is_array($this->model->validate()));
        $this->assertEquals(Mage::helper('customer')->__('No parcel pick up machines are allowed in billing address. To send to a parcel pick up machine you should enter it as shipping address.'), current($this->model->validate()));

        // validation passed
        $this->model->setStreet("Irgendwo im Nirgendwo");
        $this->assertTrue($this->model->validate());

        // validation passsed because validation is turned off
        $this->model->setStreet("Irgendwo im Nirgendwo \n bei einer \n evergreen terrace \n packstation");
        $this->model->setShouldIgnoreValidation(true);
        $this->assertTrue($this->model->validate());

        $this->model->setAddressType(Dhl_Account_Model_Quote_Address::TYPE_SHIPPING);
        $this->assertTrue($this->model->validate());
    }


    public function testShippingToPackstation()
    {
        $quote = Mage::getModel('sales/quote');
        $quote->setBillingAddress($this->model);
        $this->model->setQuote($quote);
        $this->model->setAddressType(Dhl_Account_Model_Quote_Address::TYPE_SHIPPING);
        $this->model->setStreet("123");
        $this->model->setDhlaccount("123456");
        $this->assertTrue($this->model->validate());

        $this->model->setStreet("packstation 123");
        $this->assertTrue($this->model->validate());

        $this->model->setShipToPackstation(true);
        $this->model->setStreet("packstation 123");
        $this->assertTrue($this->model->validate());

        $this->store->setConfig('intraship/packstation/active', false);
        $this->model->setStreet("123");
        $this->assertTrue($this->model->validate());

        $this->store->setConfig('intraship/packstation/active', true);
        $this->model->setStreet("1234");
        $this->assertTrue(is_array($this->model->validate()));
        $this->assertEquals(Mage::helper('customer')->__('Only 3 digits are allowed for packstations.'), current($this->model->validate()));

        $this->model->setStreet("Packstation 1234");
        $this->assertTrue(is_array($this->model->validate()));
        $this->assertEquals(Mage::helper('customer')->__('Only 3 digits are allowed for packstations.'), current($this->model->validate()));

        $this->model->setStreet("123");
        $this->model->setDhlaccount("123456");
        $this->assertTrue($this->model->validate());

        $this->model->setStreet("123");
        $this->model->setDhlaccount("1234");
        $this->assertTrue(is_array($this->model->validate()));
        $this->assertEquals(Mage::helper('customer')->__('Only 6 to 10 digits are allowed for DHL account number') . ' 1234.', current($this->model->validate()));

        $this->model->setStreet("123");
        $this->model->setDhlaccount("1234abc");
        $this->assertTrue(is_array($this->model->validate()));
        $this->assertEquals(Mage::helper('customer')->__('Only 6 to 10 digits are allowed for DHL account number') . ' 1234abc.', current($this->model->validate()));

        $this->model->setStreet("123");
        $this->model->setDhlaccount("1234abcdefg");
        $this->assertTrue(is_array($this->model->validate()));
        $this->assertEquals(Mage::helper('customer')->__('Only 6 to 10 digits are allowed for DHL account number') . ' 1234abcdefg.', current($this->model->validate()));

        $this->model->setStreet("123");
        $this->model->setDhlaccount("12345678901");
        $this->assertTrue(is_array($this->model->validate()));
        $this->assertEquals(Mage::helper('customer')->__('Only 6 to 10 digits are allowed for DHL account number') . ' 12345678901.', current($this->model->validate()));
    }


    public function testAddressValidation()
    {
       $this->model->setStreet('Nonnenstrasse 11d');
       $address = clone $this->model;
       $this->model->setAddressType(Dhl_Account_Model_Quote_Address::TYPE_SHIPPING);
       $address->setAddressType(Dhl_Account_Model_Quote_Address::TYPE_BILLING);
       $address->setDhlaccount('123456');
       $quote = Mage::getModel('sales/quote');
       $quote->setBillingAddress($address);
       $this->model->setQuote($quote);
       $this->assertTrue($this->model->validate());

       $address->setStreet('Nonnenstraße 11d');
       $quote->setBillingAddress($address);
       $this->model->setQuote($quote);
       $result = $this->model->validate();
       $this->assertTrue($this->model->validate());

       $address->setStreet1('Nonnenstraße');
       $address->setStreet2('11d');
       $quote->setBillingAddress($address);
       $this->model->setQuote($quote);
       $result = $this->model->validate();
       $this->assertTrue($this->model->validate());

       $address->setStreet1('Nonnenstraße');
       $address->setStreet3('11d');
       $quote->setBillingAddress($address);
       $this->model->setQuote($quote);
       $result = $this->model->validate();
       $this->assertTrue($this->model->validate());

       $address->setStreet1('Nonnenstraße');
       $address->setStreet4('11d');
       $quote->setBillingAddress($address);
       $this->model->setQuote($quote);
       $result = $this->model->validate();
       $this->assertTrue($this->model->validate());

       $address->setStreet('Nonenstrasse 11d');
       $quote->setBillingAddress($address);
       $this->model->setQuote($quote);
       $result = $this->model->validate();
       $this->assertTrue(is_array($result));
       $errorMessage = current($result);
       $this->assertTrue(array_key_exists('message', $errorMessage));
       $this->assertTrue(array_key_exists('showConfirm', $errorMessage));
       $this->assertEquals(1, $errorMessage['showConfirm']);
    }

}

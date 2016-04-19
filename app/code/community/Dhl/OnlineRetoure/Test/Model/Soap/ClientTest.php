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
 * DHL OnlineRetoure Soap Client Model Test
 *
 * @category    Dhl
 * @package     Dhl_OnlineRetoure
 * @author      André Herrn <andre.herrn@netresearch.de>
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Dhl_OnlineRetoure_Test_Model_Soap_ClientTest extends EcomDev_PHPUnit_Test_Case
{
    public function setUp()
    {
        $this->store  = Mage::app()->getStore(0)->load(0);
        parent::setUp();
    }

    public function testMissingConfigException()
    {
        // mock soap client, never perform actual request
        $clientMock = $this->getModelMock('dhlonlineretoure/soap_client', array('BookLabel'));
        $clientMock->expects($this->any())
                ->method('BookLabel')
                ->will($this->returnValue(null));
        $this->replaceByMock('model', 'dhlonlineretoure/soap_client', $clientMock);

        /* @var $client Dhl_OnlineRetoure_Model_Soap_Client */
        $client = Mage::getModel('dhlonlineretoure/soap_client');

        $this->setExpectedException('Mage_Core_Exception', 'Please provide configuration on webservice client');
        $client->requestLabel();
    }

    public function testMissingOrderException()
    {
        // mock soap client, never perform actual request
        $clientMock = $this->getModelMock('dhlonlineretoure/soap_client', array('BookLabel'));
        $clientMock->expects($this->any())
                ->method('BookLabel')
                ->will($this->returnValue(null));
        $this->replaceByMock('model', 'dhlonlineretoure/soap_client', $clientMock);

        /* @var $client Dhl_OnlineRetoure_Model_Soap_Client */
        $client = Mage::getModel('dhlonlineretoure/soap_client');
        $client->setConfig(Mage::getModel('dhlonlineretoure/config'));

        $this->setExpectedException('Mage_Core_Exception', 'Please provide the order to return on webservice client');
        $client->requestLabel();
    }

    /**
     * @loadFixture ../../../../var/fixtures/config.yaml
     * @loadFixture ../../../../var/fixtures/customers.yaml
     * @loadFixture ../../../../var/fixtures/orders.yaml
     */
    public function testRequestLabel()
    {
        $clientResponse = new stdClass();
        $clientResponse->issueDate   = '2012-01-12T04:18:29.399+0100';
        $clientResponse->routingCode = '53113019515335';
        $clientResponse->idc         = '00340433830245123711';
        $clientResponse->idcType     = 'EAN_LP';
        $clientResponse->label       = 'FOO==';

        /* @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->load(13);

        $wsdl = 'https://amsel.dpwn.net/abholportal/gw/lp/schema/1.0/var3ws.wsdl';
        $this->store->setConfig('intraship/dhlonlineretoure/wsdl', $wsdl);

        $user = 'wsse-user';
        $this->store->setConfig('intraship/dhlonlineretoure/user', $user);

        $pass = 'wsse-pass';
        $this->store->setConfig('intraship/dhlonlineretoure/password', $pass);

        $portalId = 'Dhl_OnlineRetoure';
        $this->store->setConfig('intraship/dhlonlineretoure/portal_id', $portalId);

        $deliveryName  = 'deliveryName-DE';
        $deliveryNames = serialize(array(array('iso' => 'DE', 'name' => $deliveryName)));
        $this->store->setConfig('intraship/dhlonlineretoure/delivery_names', $deliveryNames);


        // mock soap client, never perform actual request
        $clientMock = $this->getModelMock('dhlonlineretoure/soap_client', array('BookLabel'));
        $clientMock->expects($this->any())
                ->method('BookLabel')
                ->will($this->returnValue($clientResponse));
        $this->replaceByMock('model', 'dhlonlineretoure/soap_client', $clientMock);

        /* @var $client Dhl_OnlineRetoure_Model_Soap_Client */
        $client = Mage::getModel('dhlonlineretoure/soap_client');
        $client
            ->setConfig(Mage::getModel('dhlonlineretoure/config'))
            ->setOrder($order);
        $this->assertEquals($wsdl, $client->getWsdl());
        $this->assertEquals($user, $client->getUsername());
        $this->assertEquals($pass, $client->getPassword());
        $this->assertEquals($portalId, $client->getPortalId());
        $this->assertEquals($deliveryName, $client->getDeliveryName());

        // check setting request data
        $this->assertEquals(
            $order->getShippingAddress()->getFirstname()." ".$order->getShippingAddress()->getLastname(),
            $client->getSenderName1());
        $this->assertEquals("", $client->getSenderName2());
        $this->assertEquals($order->getShippingAddress()->getTelephone(), $client->getSenderContactPhone());
        $this->assertEquals($order->getShippingAddress()->getPostcode(), $client->getSenderPostalCode());
        $this->assertEquals($order->getShippingAddress()->getCity(), $client->getSenderCity());

        // test request
        $response = $client->requestLabel();
        $this->assertEquals($clientResponse, $response);
    }

    /**
     * @loadFixture ../../../../var/fixtures/config.yaml
     * @loadFixture ../../../../var/fixtures/customers.yaml
     * @loadFixture ../../../../var/fixtures/orders.yaml
     */
    public function testRequestLabelWithCompany()
    {
        /* @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->load(12);

        // mock soap client, never perform actual request
        $clientMock = $this->getModelMock('dhlonlineretoure/soap_client', array('BookLabel'));
        $clientMock->expects($this->any())
                ->method('BookLabel')
                ->will($this->returnValue(null));
        $this->replaceByMock('model', 'dhlonlineretoure/soap_client', $clientMock);

        /* @var $client Dhl_OnlineRetoure_Model_Soap_Client */
        $client = Mage::getModel('dhlonlineretoure/soap_client');
        $client
            ->setConfig(Mage::getModel('dhlonlineretoure/config'))
            ->setOrder($order);

        // check regular behaviour
        $this->assertEquals(
            $order->getShippingAddress()->getFirstname()." ".$order->getShippingAddress()->getLastname(),
            $client->getSenderName2());
        $this->assertEquals($order->getShippingAddress()->getCompany(), $client->getSenderName1());
        $this->assertEquals($order->getShippingAddress()->getTelephone(), $client->getSenderContactPhone());
        $this->assertEquals($order->getShippingAddress()->getPostcode(), $client->getSenderPostalCode());
        $this->assertEquals($order->getShippingAddress()->getCity(), $client->getSenderCity());
    }
}

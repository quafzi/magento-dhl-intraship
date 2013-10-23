<?php
/**
 * Dhl_Account_Test_Model_Observer
 *
 * @category  Models
 * @package   Dhl_Account
 * @author    Thomas Birke <thomas.birke@netresearch.de>
 * @author    Michael Lühr <michael.luehr@netresearch.de>
 * @copyright Copyright (c) 2012 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Account_Test_Model_ObserverTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * add DHL account to shipment create request
     *
     * @test
     * @loadFixture ../../../../../Intraship/Test/var/fixtures/shipments
     * @loadFixture ../../../../../Intraship/Test/var/fixtures/parcels
     */
    public function dhlAccountInCompanyName2WhichWasNonExistant()
    {
        $params = array('ShipmentOrder' => array('Shipment' => array('Receiver' => array('Company' => array('Company' => array())))));
        $dhlaccount = '1234567';
        $parcel = Mage::getModel('intraship/shipment')->load(1);
        $parcel->getShipment()->getBillingAddress()->setDhlaccount($dhlaccount);
        $request = Mage::getModel('intraship/soap_client_shipment_create');
        $request->set('params', new ArrayObject($params));
        $request->set('shipment', $parcel);

        Mage::getModel('dhlaccount/observer')->dhlIntrashipSendShipmentBefore(new Varien_Object(array('request' => $request)));

        $this->assertInName2($dhlaccount, $request->get('params'));
    }

    /**
     * add DHL account to shipment create request
     *
     * @test
     * @loadFixture ../../../../../Intraship/Test/var/fixtures/shipments
     * @loadFixture ../../../../../Intraship/Test/var/fixtures/parcels
     */
    public function dhlAccountInCompanyName2WhichWasEmpty()
    {
        $params = array('ShipmentOrder' => array('Shipment' => array('Receiver' => array('Company' => array('Company' => array('name2' => ' '))))));
        $dhlaccount = '1234567';
        $parcel = Mage::getModel('intraship/shipment')->load(1);
        $parcel->getShipment()->getBillingAddress()->setDhlaccount($dhlaccount);
        $request = Mage::getModel('intraship/soap_client_shipment_create');
        $request->set('params', new ArrayObject($params));
        $request->set('shipment', $parcel);

        Mage::getModel('dhlaccount/observer')->dhlIntrashipSendShipmentBefore(new Varien_Object(array('request' => $request)));

        $this->assertInName2($dhlaccount, $request->get('params'));
    }

    /**
     * add DHL account to shipment create request
     *
     * @test
     * @loadFixture ../../../../../Intraship/Test/var/fixtures/shipments
     * @loadFixture ../../../../../Intraship/Test/var/fixtures/parcels
     */
    public function dhlAccountInContactPersonWhichWasNonExistant()
    {
        $params = array('ShipmentOrder' => array('Shipment' => array('Receiver' => array(
            'Company' => array('Company' => array('name2' => 'notempty'))
        ))));
        $dhlaccount = '1234567';
        $parcel = Mage::getModel('intraship/shipment')->load(1);
        $parcel->getShipment()->getBillingAddress()->setDhlaccount($dhlaccount);
        $request = Mage::getModel('intraship/soap_client_shipment_create');
        $request->set('params', new ArrayObject($params));
        $request->set('shipment', $parcel);

        Mage::getModel('dhlaccount/observer')->dhlIntrashipSendShipmentBefore(new Varien_Object(array('request' => $request)));

        $this->assertInContactPerson($dhlaccount, $request->get('params'));
    }

    /**
     * add DHL account to shipment create request
     *
     * @test
     * @loadFixture ../../../../../Intraship/Test/var/fixtures/shipments
     * @loadFixture ../../../../../Intraship/Test/var/fixtures/parcels
     */
    public function dhlAccountInContactPersonWhichWasEmpty()
    {
        $params = array('ShipmentOrder' => array('Shipment' => array('Receiver' => array(
            'Company' => array('Company' => array('name2' => 'notempty')),
            'Communication' => array('contactPerson' => ' '),
        ))));
        $dhlaccount = '1234567';
        $parcel = Mage::getModel('intraship/shipment')->load(1);
        $parcel->getShipment()->getBillingAddress()->setDhlaccount($dhlaccount);
        $request = Mage::getModel('intraship/soap_client_shipment_create');
        $request->set('params', new ArrayObject($params));
        $request->set('shipment', $parcel);

        Mage::getModel('dhlaccount/observer')->dhlIntrashipSendShipmentBefore(new Varien_Object(array('request' => $request)));

        $this->assertInContactPerson($dhlaccount, $request->get('params'));
    }

    /**
     * add DHL account to shipment create request
     *
     * @test
     * @loadFixture ../../../../../Intraship/Test/var/fixtures/shipments
     * @loadFixture ../../../../../Intraship/Test/var/fixtures/parcels
     */
    public function dhlAccountInCareOfNameWhichWasNonExistant()
    {
        $params = array('ShipmentOrder' => array('Shipment' => array('Receiver' => array(
            'Company' => array('Company' => array('name2' => 'notempty')),
            'Communication' => array('contactPerson' => 'notempty'),
            'Address' => array()
        ))));
        $dhlaccount = '1234567';
        $parcel = Mage::getModel('intraship/shipment')->load(1);
        $parcel->getShipment()->getBillingAddress()->setDhlaccount($dhlaccount);
        $request = Mage::getModel('intraship/soap_client_shipment_create');
        $request->set('params', new ArrayObject($params));
        $request->set('shipment', $parcel);

        Mage::getModel('dhlaccount/observer')->dhlIntrashipSendShipmentBefore(new Varien_Object(array('request' => $request)));

        $this->assertInCareOfName($dhlaccount, $request->get('params'));
    }

    /**
     * add DHL account to shipment create request
     *
     * @test
     * @loadFixture ../../../../../Intraship/Test/var/fixtures/shipments
     * @loadFixture ../../../../../Intraship/Test/var/fixtures/parcels
     */
    public function dhlAccountInCareOfNameWhichWasEmpty()
    {
        $params = array('ShipmentOrder' => array('Shipment' => array('Receiver' => array(
            'Company' => array('Company' => array('name2' => 'notempty')),
            'Communication' => array('contactPerson' => 'notempty'),
            'Address' => array('careOfName' => ' ')
        ))));
        $dhlaccount = '1234567';
        $parcel = Mage::getModel('intraship/shipment')->load(1);
        $parcel->getShipment()->getBillingAddress()->setDhlaccount($dhlaccount);
        $request = Mage::getModel('intraship/soap_client_shipment_create');
        $request->set('params', new ArrayObject($params));
        $request->set('shipment', $parcel);

        Mage::getModel('dhlaccount/observer')->dhlIntrashipSendShipmentBefore(new Varien_Object(array('request' => $request)));

        $this->assertInCareOfName($dhlaccount, $request->get('params'));
    }

    protected function assertInName2($expected, $result)
    {
        $this->assertTrue(array_key_exists('Shipment', $result['ShipmentOrder']));
        $this->assertTrue(array_key_exists('Receiver', $result['ShipmentOrder']['Shipment']));
        $this->assertTrue(array_key_exists('Company', $result['ShipmentOrder']['Shipment']['Receiver']));
        $this->assertTrue(array_key_exists('Company', $result['ShipmentOrder']['Shipment']['Receiver']['Company']));
        $this->assertTrue(array_key_exists('name2', $result['ShipmentOrder']['Shipment']['Receiver']['Company']['Company']));
        $this->assertEquals($expected, $result['ShipmentOrder']['Shipment']['Receiver']['Company']['Company']['name2']);
    }

    protected function assertInContactPerson($expected, $result)
    {
        $this->assertTrue(array_key_exists('Shipment', $result['ShipmentOrder']));
        $this->assertTrue(array_key_exists('Receiver', $result['ShipmentOrder']['Shipment']));
        $this->assertTrue(array_key_exists('Communication', $result['ShipmentOrder']['Shipment']['Receiver']));
        $this->assertTrue(array_key_exists('contactPerson', $result['ShipmentOrder']['Shipment']['Receiver']['Communication']));
        $this->assertEquals($expected, $result['ShipmentOrder']['Shipment']['Receiver']['Communication']['contactPerson']);
    }

    protected function assertInCareOfName($expected, $result)
    {
        $this->assertTrue(array_key_exists('Shipment', $result['ShipmentOrder']));
        $this->assertTrue(array_key_exists('Receiver', $result['ShipmentOrder']['Shipment']));
        $this->assertTrue(array_key_exists('Address', $result['ShipmentOrder']['Shipment']['Receiver']));
        $this->assertTrue(array_key_exists('careOfName', $result['ShipmentOrder']['Shipment']['Receiver']['Address']));
        $this->assertEquals($expected, $result['ShipmentOrder']['Shipment']['Receiver']['Address']['careOfName']);
    }


    /**
     * add DHL account to shipment create request
     *
     * @test
     * @loadFixture ../../../../../Intraship/Test/var/fixtures/shipments
     * @loadFixture ../../../../../Intraship/Test/var/fixtures/parcels
     */
    public function testBillingAddressIsNull()
    {
        $shipment = Mage::getModel('intraship/shipment')->load(10);;
        $event = new Varien_Event();
        $event->setObject($shipment);
        Mage::getModel('dhlaccount/observer')->dhlIntrashipShipmentLoadAfter($event);
        $this->assertFalse($shipment->hasCustomizedAddress());

        // parcel announcement
        $shipment = Mage::getModel('intraship/shipment')->load(1);
        $event = new Varien_Event();
        $shipment->getShipment()->getBillingAddress()->setDhlaccount('123');
        $event->setObject($shipment);
        Mage::getModel('dhlaccount/observer')->dhlIntrashipShipmentLoadAfter($event);
        $this->assertTrue(is_array($shipment->getCustomerAddress()));
        $customerAddress = $shipment->getCustomerAddress();
        $this->assertTrue(array_key_exists('dhlaccount', $customerAddress));
        $this->assertEquals('123', $customerAddress['dhlaccount']);

        // packstation
        $event = new Varien_Event();
        $shipment->getShipment()->getShippingAddress()->setDhlaccount('567');
        $shipment->getShipment()->getShippingAddress()->setStreet('123');
        $event->setObject($shipment);
        Mage::getModel('dhlaccount/observer')->dhlIntrashipShipmentLoadAfter($event);
        $this->assertTrue(is_array($shipment->getCustomerAddress()));
        $customerAddress = $shipment->getCustomerAddress();
        $this->assertTrue(array_key_exists('id_number', $customerAddress));
        $this->assertEquals('567', $customerAddress['id_number']);
        $this->assertTrue(array_key_exists('station_id', $customerAddress));
        $this->assertEquals('123', $customerAddress['station_id']);

    }
}

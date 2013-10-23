<?php
/**
 * Dhl_Intraship_Test_Model_Soap_Client_Shipment_CreateTest
 *
 * @category  Models
 * @package   Dhl_Account
 * @author    Thomas Birke <thomas.birke@netresearch.de>
 * @author    Michael Lühr <michael.luehr@netresearch.de>
 * @copyright Copyright (c) 2012 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Test_Model_Soap_Client_Shipment_CreateTest extends EcomDev_PHPUnit_Test_Case_Config
{
    /**
     * init 
     *
     * @test
     * @loadFixture ../../../../../../var/fixtures/shipments
     * @loadFixture ../../../../../../var/fixtures/parcels
     */
    public function init()
    {
        $client = $this->getModelMock('intraship/soap_client_shipment_create', array(
            '_appendDetails',
            '_appendShipper',
            '_appendReceiver'
        ));
        $client->expects($this->once())->method('_appendDetails')->will($this->returnSelf());
        $client->expects($this->once())->method('_appendShipper')->will($this->returnSelf());
        $client->expects($this->once())->method('_appendReceiver')->will($this->returnSelf());

        $parcel = Mage::getModel('intraship/shipment')->load(1);
        $jsonFields = array('customer_address', 'settings', 'packages');
        foreach ($jsonFields as $jsonField) {
            $parcel->setData($jsonField, json_encode($parcel->getData($jsonField)));
        }

        $config = $this->getModelMock('intraship/config', array(
            'getAccountAddress',
            'getAccountEkp'
        ));
        $config->expects($this->any())
            ->method('getAccountAddress')
            ->will($this->returnValue(new ArrayObject(array(
                'address_data'   => 'a1',
                'companyName1'   => 'Fir',
                'companyName2'   => 'ma',
                'streetName'     => 'Kurze Str.',
                'streetNumber'   => '3',
                'zip'            => '12345',
                'city'           => 'Grüna',
                'state'          => 'Sachsen',
                'countryISOCode' => 'DE',
                'phone'          => '0373346540',
                'email'          => 'dhl_intraship_unittest@trash-mail.com',
                'contactPerson'  => 'Bernd das Brot'
            ))));
        $config->expects($this->any())
            ->method('getAccountEkp')
            ->will($this->returnValue('5000000001'));
        $this->replaceByMock('model', 'intraship/config', $config);

        $params = array('foo' => 'bar');
        $client->init($parcel, $params);

        $this->assertEquals('bar', $client->get('params')->offsetGet('foo'));
        $this->assertEquals($parcel, $client->get('shipment'));
        $this->assertEquals($parcel->getShipment()->getShippingAddress(), $client->get('receiver'));
        $this->assertEquals($parcel->getShipment()->getOrder(), $client->get('order'));
        $this->assertEquals('5000000001', $client->get('ekp'));
    }
}

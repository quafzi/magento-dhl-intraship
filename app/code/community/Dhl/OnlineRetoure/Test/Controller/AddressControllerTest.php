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
 * DHL OnlineRetoure address controller test
 *
 * @category    Dhl
 * @package     Dhl_OnlineRetoure
 * @author      André Herrn <andre.herrn@netresearch.de>
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Dhl_OnlineRetoure_Test_Controller_AddressControllerTest
    extends EcomDev_PHPUnit_Test_Case_Controller
{
    protected function setUp()
    {
        parent::setUp();

        // The address controller actions are called within
        // the secure frontend area (customer account).
        $this->getRequest()->setIsSecure(true);
    }

    /**
     * For fixture cleanup, switch store scope.
     */
    protected function tearDown()
    {
        Mage::app()->setCurrentStore(Mage_Core_Model_Store::ADMIN_CODE);
        parent::tearDown();
    }

    /**
     * @loadFixture ../../../var/fixtures/config.yaml
     */
    public function testConfirmAction()
    {
        $orderMock = $this->getModelMock('sales/order', array('getShippingAddress'));
        $orderMock->expects($this->any())
                  ->method('getShippingAddress')
                  ->will($this->returnValue(new Varien_Object()));
        $this->replaceByMock('model', 'sales/order', $orderMock);

        $validateHelper = $this->getHelperMock('dhlonlineretoure/validate', array('isHashRequest', 'isInternalRequest', 'isOrderValid', 'isCustomerValid', 'isOrderIdValid'));
        $validateHelper->expects($this->any())
                       ->method('isHashRequest')
                       ->will($this->returnValue(false));
        $validateHelper->expects($this->any())
                       ->method('isInternalRequest')
                       ->will($this->returnValue(true));
        $validateHelper->expects($this->any())
                       ->method('isOrderValid')
                       ->will($this->returnValue(true));
        $validateHelper->expects($this->any())
                       ->method('isOrderIdValid')
                       ->will($this->returnValue(true));
        $validateHelper->expects($this->any())
                       ->method('isCustomerValid')
                       ->will($this->onConsecutiveCalls(true, $this->throwException(new Dhl_OnlineRetoure_Model_Validate_Exception())));
        $this->replaceByMock('helper', 'dhlonlineretoure/validate', $validateHelper);

        // (1) everything is fine, show confirmation form
        $this->dispatch('dhlonlineretoure/address/confirm');
        $this->assertRequestRoute('dhlonlineretoure/address/confirm');

        $this->assertLayoutHandleLoaded('dhlonlineretoure_address_confirm');
        $this->assertLayoutBlockCreated('dhlonlineretoure_customer_address_edit');
        $this->assertLayoutBlockTypeOf('dhlonlineretoure_customer_address_edit', 'dhlonlineretoure/customer_address_edit');

        // (2) redirect to error page
        $this->getRequest()->setQuery('order_id', 1);
        $this->dispatch('dhlonlineretoure/address/confirm');
        $this->assertRedirectTo('dhlonlineretoure/address/error');
    }

    /**
     * @loadFixture ../../../var/fixtures/config.yaml
     */
    public function testFormPostActionInvalidFormKey()
    {
        $actual   = 'foo';
        $expected = 'bar';
        $this->getRequest()->setMethod('POST')->setParam('form_key', $actual);

        $session = $this->getModelMock('core/session', array('getFormKey'));
        $session->expects($this->any())
                ->method('getFormKey')
                ->will($this->returnValue($expected));
        $this->replaceByMock('singleton', 'core/session', $session);

        $this->dispatch('dhlonlineretoure/address/formPost');
        $this->assertRedirectTo('sales/order/history');
    }

    /**
     * @loadFixture ../../../var/fixtures/config.yaml
     */
    public function testFormPostAction()
    {
        // mock soap client, never perform actual request
        $response = new stdClass();
        $response->issueDate = '2013-06-10T16:25:09.396+0200';
        $response->routingCode = '53113.019.515.33 5';
        $response->idc = '00340433830245121366';
        $response->idcType = 'EAN_LP';
        $response->label = 'JVBERi0xLjQKJeLjz9MKNCAwIG9iago8…';

        $fault = new SoapFault(
            'var3bl:MandatoryField',
            'Ein Pflichtfeld ist nicht gesetzt: Das Feld PLZ wird benötigt.'
        );

        $street1 = 'An der Tabaksmühle 3a';
        $formKey = 'GrxnYgguInwekyGv';
        $this->getRequest()
            ->setMethod('POST')
            ->setParam('form_key', $formKey)
            ->setParam('order_id', '13')
            ->setPost(array(
                'city' => 'Leipzig',
                'company' => '',
                'country_id' => 'DE',
                'fax' => '',
                'firstname' => 'Hubertus',
                'form_key' => $formKey,
                'lastname' => 'Fürstenberg',
                'postcode' => '04229',
                'region' => '',
                'region_id' => '91',
                'street' => array($street1, ''),
                'telephone' => '',
            ))
            ;

        $session = $this->getModelMock('core/session', array('getFormKey'));
        $session->expects($this->any())
                ->method('getFormKey')
                ->will($this->returnValue($formKey));
        $this->replaceByMock('singleton', 'core/session', $session);

        $shippingAddress = new Mage_Sales_Model_Order_Address();
        $orderMock = $this->getModelMock('sales/order', array('getShippingAddress'));
        $orderMock->expects($this->any())
                  ->method('getShippingAddress')
                  ->will($this->returnValue($shippingAddress));
        $this->replaceByMock('model', 'sales/order', $orderMock);

        $validateHelper = $this->getHelperMock('dhlonlineretoure/validate', array(
            'isHashRequest', 'isInternalRequest', 'isOrderValid', 'isCustomerValid'
        ));
        $validateHelper->expects($this->any())
                       ->method('isHashRequest')
                       ->will($this->returnValue(false));
        $validateHelper->expects($this->any())
                       ->method('isInternalRequest')
                       ->will($this->returnValue(true));
        $validateHelper->expects($this->any())
                       ->method('isOrderValid')
                       ->will($this->returnValue(true));
        $validateHelper->expects($this->any())
                       ->method('isCustomerValid')
                       ->will($this->onConsecutiveCalls($this->throwException(new Dhl_OnlineRetoure_Model_Validate_Exception()), true, true));
        $this->replaceByMock('helper', 'dhlonlineretoure/validate', $validateHelper);

        $clientMock = $this->getModelMock('dhlonlineretoure/soap_client', array('requestLabel'));
        $clientMock->expects($this->any())
                       ->method('requestLabel')
                       ->will($this->onConsecutiveCalls($this->throwException($fault), $this->returnValue($response)));
        $this->replaceByMock('model', 'dhlonlineretoure/soap_client', $clientMock);

        // (1) test order load exception (can not show retoure link)
        $this->dispatch('dhlonlineretoure/address/formPost');
        $this->assertRedirectTo('dhlonlineretoure/address/error');

        // (2) test redirect to confirmation form on webservice error
        $this->dispatch('dhlonlineretoure/address/formPost');
        $this->assertRedirectTo('dhlonlineretoure/address/confirm', array('_query' => array('order_id' => '13')));

        // (3) all fine
        $this->dispatch('dhlonlineretoure/address/formPost');
        $this->assertResponseHeaderSent('Content-Disposition');
    }
}
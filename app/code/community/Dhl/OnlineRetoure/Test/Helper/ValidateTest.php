<?php
/**
 * Dhl_OnlineRetoure_Test_Helper_ValidateTest
 *
 * @package   Dhl_Account
 * @author    André Herrn <andre.herrn@netresearch.de>
 * @copyright Copyright (c) 2012 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_OnlineRetoure_Test_Helper_ValidateTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @var Mage_Core_Model_Store
     */
    protected $store;

    /**
     * Set up controller params
     */
    protected function setUp()
    {
        /**
         * Mock session to avoid BUG
         * "Exception: Warning: session_start(): Cannot send session cookie - headers already sent by"
         */
        $sessionMock = $this->getModelMock('customer/session', array(
            'init', 'renewSession', 'start'
            )
         );
        $this->replaceByMock('model', 'customer/session', $sessionMock);

        $sessionMock = $this->getModelMock('checkout/session', array(
            'init', 'renewSession', 'start'
            )
         );
        $this->replaceByMock('model', 'checkout/session', $sessionMock);

        //Basic Setup
        $this->store = Mage::app()->getStore(0)->load(0);
        $_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
        $this->app()->getRequest()->setBaseUrl($_baseUrl);

        parent::setup();
    }

    /**
     * Get validate helper
     *
     * @return Dhl_OnlineRetoure_Helper_Validate
     */
    protected function getValidateHelper()
    {
        return Mage::helper("dhlonlineretoure/validate");
    }

    public function testIsAllowedCountryCode()
    {
        $this->store->setConfig('intraship/bpi/countryCodes', 'FR,NL,AT,PL,HU,GB');
        $this->assertTrue($this->getValidateHelper()->isAllowedCountryCode('NL'));
        $this->assertTrue($this->getValidateHelper()->isAllowedCountryCode('GB'));
        $this->assertFalse($this->getValidateHelper()->isAllowedCountryCode('CH'));
    }

    /**
     * @loadFixture ../../../var/fixtures/config.yaml
     * @loadFixture ../../../var/fixtures/customers.yaml
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testIsOrderExisting()
    {
        $this->setUp();

        //Order exists by ID - True
        $this->assertTrue($this->getValidateHelper()->isOrderIdExisting(11));

        //Order exists by object - True
        $this->assertTrue(
            $this->getValidateHelper()->isOrderExisting(
                Mage::getModel("sales/order")->load(11)
            )
        );

        //Order exists by ID - False (ID is not existing)
        $this->assertFalse($this->getValidateHelper()->isOrderIdExisting(123456789));

        //Order exists by object - False (ID is not existing)
        $this->assertFalse(
            $this->getValidateHelper()->isOrderExisting(
                Mage::getModel("sales/order")->load(123456789)
            )
        );

        //No parameters were given -> No Order!
        $this->setExpectedException('Exception');
        $this->assertFalse($this->getValidateHelper()->isOrderExisting());
    }

    /**
     * @loadFixture ../../../var/fixtures/config.yaml
     * @loadFixture ../../../var/fixtures/customers.yaml
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testIsOrderHasShipments()
    {
        //Order has shipments
        $this->assertTrue(
            $this->getValidateHelper()->isOrderHasShipments(
                Mage::getModel("sales/order")->load(11)
            )
        );

        //Order has no shipments
        $this->assertFalse(
            $this->getValidateHelper()->isOrderHasShipments(
                Mage::getModel("sales/order")->load(13)
            )
        );
    }

    /**
     * @loadFixture ../../../var/fixtures/config.yaml
     * @loadFixture ../../../var/fixtures/customers.yaml
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testIsOrderBelongsToCustomer()
    {
        $this->setUp();

        //Order belongs to customer
        $this->assertTrue(
            $this->getValidateHelper()->isOrderBelongsToCustomer(
                Mage::getModel("sales/order")->load(11),
                Mage::getModel("customer/customer")->load(3)
            )
        );
        $this->assertTrue(
            $this->getValidateHelper()->isOrderBelongsToCustomer(
                Mage::getModel("sales/order")->load(12),
                Mage::getModel("customer/customer")->load(4)
            )
        );

        //Order doesn't belong to customer
        $this->assertFalse(
            $this->getValidateHelper()->isOrderBelongsToCustomer(
                Mage::getModel("sales/order")->load(12),
                Mage::getModel("customer/customer")->load(3)
            )
        );
    }

    /**
     * @loadFixture ../../../var/fixtures/config.yaml
     * @loadFixture ../../../var/fixtures/customers.yaml
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testCanShowRetoureLink()
    {
        $this->setUp();
        $this->store->setConfig('intraship/dhlonlineretoure/active', '1');

        $customerId = 4;
        $orderId    = 12;

        /* @var $customer Mage_Customer_Model_Customer */
        $customer = Mage::getModel("customer/customer")->load($customerId);
        /* @var $order Mage_Sales_Model_Order */
        $order    = Mage::getModel("sales/order")->load($orderId);

        //Default case, no parameters given
        $this->assertFalse($this->getValidateHelper()->canShowRetoureLink($order));

        // internal request
        Mage::app()->getRequest()->setParam('hash', '1');
        $this->assertFalse($this->getValidateHelper()->canShowRetoureLink($order));

        // external request
        Mage::app()->getRequest()->clearParams();
        Mage::app()->getRequest()->setParam('order_id', $orderId);

        // prepare customer for retoure link
        $session = $this->getModelMock('customer/session', array('isLoggedIn', 'init', 'renewSession', 'start'));
        $session->expects($this->any())
                ->method('isLoggedIn')
                ->will($this->returnValue(true));
        $this->replaceByMock('singleton', 'customer/session', $session);

        $customerHelper = $this->getHelperMock('customer/data', array('getCustomer'));
        $customerHelper->expects($this->any())
                ->method('getCustomer')
                ->will($this->returnValue($customer));
        $this->replaceByMock('helper', 'customer/data', $customerHelper);

        // prepare config settings for retoure link
        $deliveryIso = $order->getShippingAddress()->getCountryId();
        $deliveryName = 'Delivery' . $deliveryIso;
        $data = array(
            array('iso'  => $deliveryIso, 'name' => $deliveryName),
        );
        $this->store->setConfig('intraship/dhlonlineretoure/delivery_names', serialize($data));
        $this->store->setConfig('intraship/dhlonlineretoure/allowed_shipping_methods', "flatrate_flatrate");

        $this->assertTrue($this->getValidateHelper()->canShowRetoureLink($order));
    }


    /**
     * @loadFixture ../../../var/fixtures/config.yaml
     * @loadFixture ../../../var/fixtures/customers.yaml
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testCreateHashForOrder()
    {
        $this->setUp();

        $order = Mage::getModel('sales/order')->load(20);
        $orderHash ="2cc88c2bc786ed0d9bbdfd5565e8b9bae205034516f4ac7819f2ab3a9e83c1ef5170efe673fd63252bcc7db1bc6275abf4c01d7bbc4bd2281b178e220aad7010";
        Mage::getConfig()->setNode('global/crypt/key', '123456');
        $this->assertEquals($orderHash, Mage::helper('dhlonlineretoure/validate')->createHashForOrder($order));

        $order = Mage::getModel('sales/order')->load(21);
        $this->assertNotEquals($orderHash, Mage::helper('dhlonlineretoure/validate')->createHashForOrder($order));
    }


    /**
     * @loadFixture ../../../var/fixtures/config.yaml
     * @loadFixture ../../../var/fixtures/customers.yaml
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testIsHashValid()
    {
        $this->setUp();

        $order = Mage::getModel('sales/order')->load(20);
        $orderHash = "2cc88c2bc786ed0d9bbdfd5565e8b9bae205034516f4ac7819f2ab3a9e83c1ef5170efe673fd63252bcc7db1bc6275abf4c01d7bbc4bd2281b178e220aad7010";

        // (1) MATCH
        $this->assertTrue(Mage::helper('dhlonlineretoure/validate')->isHashValid($orderHash, $order));

        // (2) MISMATCH
        $order = Mage::getModel('sales/order')->load(13);
        $this->setExpectedException(
            'Dhl_OnlineRetoure_Model_Validate_Exception',
            'You are not allowed to create a return for the current order.'
        );
        Mage::helper('dhlonlineretoure/validate')->isHashValid($orderHash, $order);
    }

    /**
     * @loadFixture ../../../var/fixtures/config.yaml
     * @loadFixture ../../../var/fixtures/customers.yaml
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testIsCustomerLoginValid()
    {
        $this->setUp();

        $order = Mage::getModel('sales/order')->load(20);
        $goodCustomer = Mage::getModel('customer/customer')->load(3);

        $customerHelper = $this->getHelperMock('customer/data', array('getCustomer', 'isLoggedIn'));
        $customerHelper->expects($this->any())
                ->method('getCustomer')
                ->will($this->returnValue($goodCustomer));
        $customerHelper->expects($this->any())
                ->method('isLoggedIn')
                ->will($this->onConsecutiveCalls(true, false));
        $this->replaceByMock('helper', 'customer/data', $customerHelper);

        // (1) LOGGED IN
        $this->assertTrue(Mage::helper('dhlonlineretoure/validate')->isCustomerValid($order));

        // (2) NOT LOGGED IN
        $this->setExpectedException(
            'Dhl_OnlineRetoure_Model_Validate_Exception',
            'Please log in to access DHL Online Return.'
        );
        Mage::helper('dhlonlineretoure/validate')->isCustomerValid($order);
    }

    /**
     * @loadFixture ../../../var/fixtures/config.yaml
     * @loadFixture ../../../var/fixtures/customers.yaml
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testIsCustomerOrderValid()
    {
        $this->setUp();

        $order = Mage::getModel('sales/order')->load(20);
        $goodCustomer = Mage::getModel('customer/customer')->load(3);
        $badCustomer = Mage::getModel('customer/customer')->load(666);

        $customerHelper = $this->getHelperMock('customer/data', array('isLoggedIn', 'getCustomer'));
        $customerHelper->expects($this->any())
                ->method('isLoggedIn')
                ->will($this->returnValue(true));
        $customerHelper->expects($this->any())
                ->method('getCustomer')
                ->will($this->onConsecutiveCalls($goodCustomer, $badCustomer));
        $this->replaceByMock('helper', 'customer/data', $customerHelper);

        // (1) ORDER MATCH
        $this->assertTrue(Mage::helper('dhlonlineretoure/validate')->isCustomerValid($order));

        // (2) ORDER MISMATCH
        $this->setExpectedException(
            'Dhl_OnlineRetoure_Model_Validate_Exception',
            'You are not allowed to create a return for the current order.'
        );
        Mage::helper('dhlonlineretoure/validate')->isCustomerValid($order);
    }

    public function testIsOrderIdValid()
    {
        $this->setUp();

        // (1) ORDER ID VALID
        $this->assertTrue(Mage::helper('dhlonlineretoure/validate')->isOrderIdValid(5));

        // (2) ORDER ID INVALID
        $this->setExpectedException(
            'Dhl_OnlineRetoure_Model_Validate_Exception',
            'No order id was given.'
        );
        Mage::helper('dhlonlineretoure/validate')->isOrderIdValid(null);

        // (3) ORDER ID INVALID
        $this->setExpectedException(
            'Dhl_OnlineRetoure_Model_Validate_Exception',
            'No order id was given.'
        );
        Mage::helper('dhlonlineretoure/validate')->isOrderIdValid(0);
    }
}
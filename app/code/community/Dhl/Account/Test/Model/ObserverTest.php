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
    public function testAppendParcelAnnouncementToBilling()
    {
        $sessionMock = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'checkout/session', $sessionMock);

        $sessionMock = $this->getModelMockBuilder('customer/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'customer/session', $sessionMock);

        $transport = new Varien_Object();
        $transport->setHtml('Foo');
        $observer = Mage::getModel('dhlaccount/observer');
        $event = new Varien_Object();
        $block = Mage::app()->getLayout()->getBlockSingleton('checkout/onepage_billing');
        $blockMock = $this->getBlockMock('dhlaccount/checkout_onepage_parcelannouncement', array('renderView'));
        $blockMock->expects($this->once())
            ->method('renderView')
            ->will($this->returnValue('<b>Foo</b>'));
        $this->replaceByMock('block', 'dhlaccount/checkout_onepage_parcelannouncement', $blockMock);

        $event->setBlock($block);
        $event->setTransport($transport);
        $observer->appendParcelAnnouncementToBilling($event);
        $this->assertEquals('Foo<b>Foo</b>', $transport->getHtml());
        $this->assertNotEquals('<b>Foo</b>', $transport->getHtml());

    }

    public function testAppendPackingstationToShipping()
    {
        $sessionMock = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'checkout/session', $sessionMock);

        $sessionMock = $this->getModelMockBuilder('customer/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'customer/session', $sessionMock);

        $intrashipConfigMock = $this->getModelMock('intraship/config', array('isEnabled'));
        $intrashipConfigMock->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'intraship/config', $intrashipConfigMock);

        $accountConfigMock = $this->getModelMock('dhlaccount/config', array('isPackstationEnabled'));
        $accountConfigMock->expects($this->any())
            ->method('isPackstationEnabled')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'dhlaccount/config', $accountConfigMock);

        $transport = new Varien_Object();
        $transport->setHtml('Foo');
        $observer = Mage::getModel('dhlaccount/observer');
        $event = new Varien_Object();
        $block = Mage::app()->getLayout()->getBlockSingleton('checkout/onepage_shipping');

        $blockMock = $this->getBlockMock('dhlaccount/checkout_onepage_packingstation', array('renderView'));
        $blockMock->expects($this->once())
            ->method('renderView')
            ->will($this->returnValue('<b>Foo</b>'));
        $this->replaceByMock('block', 'dhlaccount/checkout_onepage_packingstation', $blockMock);


        $event->setBlock($block);
        $event->setTransport($transport);
        $observer->appendPackingstationToShipping($event);
        $this->assertEquals('Foo<b>Foo</b>', $transport->getHtml());
        $this->assertNotEquals('<b>Foo</b>', $transport->getHtml());

    }


    public function testAppendParcelAnnouncementValidationToShipping()
    {
        $sessionMock = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'checkout/session', $sessionMock);

        $sessionMock = $this->getModelMockBuilder('customer/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'customer/session', $sessionMock);

        $intrashipConfigMock = $this->getModelMock('intraship/config', array('isEnabled'));
        $intrashipConfigMock->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'intraship/config', $intrashipConfigMock);

        $this->store = Mage::app()->getStore(0)->load(0);
        $this->store->setConfig('intraship/dhlaccount/active', true);

        $transport = new Varien_Object();
        $transport->setHtml('Foo');
        $observer = Mage::getModel('dhlaccount/observer');
        $event = new Varien_Object();
        $block = Mage::app()->getLayout()->getBlockSingleton('checkout/onepage_shipping');

        $blockMock = $this->getBlockMock('dhlaccount/checkout_onepage_parcelannouncement', array('renderView'));
        $blockMock->expects($this->once())
            ->method('renderView')
            ->will($this->returnValue('<b>Foo</b>'));
        $this->replaceByMock('block', 'dhlaccount/checkout_onepage_parcelannouncement', $blockMock);

        $event->setBlock($block);
        $event->setTransport($transport);
        $observer->appendParcelAnnouncementValidationToShipping($event);
        $this->assertEquals('Foo<b>Foo</b>', $transport->getHtml());
        $this->assertNotEquals('<b>Foo</b>', $transport->getHtml());


    }

    public function testSaveDhlAccount()
    {

        $addressMock = $this->getModelMock('sales/quote_address', array('save'));
        $quote = new Varien_Object();
        $quote->setId(1);
        $quote->setBillingAddress($addressMock);
        $quote->setShippingAddress($addressMock);
        $checkoutSessionMock = $this->getModelMock('checkout/session', array('getQuote', 'init', 'save'));
        $checkoutSessionMock->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($quote));
        $this->replaceByMock('model', 'checkout/session', $checkoutSessionMock);

        $sessionMock = $this->getModelMockBuilder('customer/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'customer/session', $sessionMock);

        $params = array(
            'billing' => array(
                'preferred_date' => '1.1.2015',
                'dhlaccount'     => '1234'
            )
        );

        Mage::app()->getFrontController()->getRequest()->setPost($params);
        $observer = Mage::getModel('dhlaccount/observer');
        $event = new Varien_Object();
        $observer->saveDhlAccount($event);
        $billlingData = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress()->getData();
        $shippingData = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getData();
        $this->assertTrue(array_key_exists('dhlaccount', $billlingData));
        $this->assertEquals('1234', $billlingData['dhlaccount']);
        $this->assertTrue(array_key_exists('dhlaccount', $shippingData));
        $this->assertEquals('1234', $shippingData['dhlaccount']);

    }

    public function testSavePackageNotificationFlagFalse()
    {
        $params = array(
            'billing' => array(
                'preferred_date' => '1.1.2015',
                'dhlaccount'     => '1234',
            )
        );

        $addressMock = $this->getModelMock('sales/quote_address', array('save'));
        $quote = new Varien_Object();
        $quote->setId(1);
        $quote->setBillingAddress($addressMock);
        $checkoutSessionMock = $this->getModelMock('checkout/session', array('getQuote', 'init', 'save'));
        $checkoutSessionMock->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($quote));
        $this->replaceByMock('model', 'checkout/session', $checkoutSessionMock);

        Mage::app()->getFrontController()->getRequest()->setPost($params);
        $observer = Mage::getModel('dhlaccount/observer');
        $event = new Varien_Event_Observer();
        $observer->savePackageNotificationFlag($event);
        $addressData = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress();
        $this->assertFalse($addressData->getPackageNotification());
    }

    public function testSavePackageNotificationFlagTrue()
    {
        $params = array(
            'billing' => array(
                'preferred_date'       => '1.1.2015',
                'dhlaccount'           => '1234',
                'package_notification' => true
            )
        );

        $addressMock = $this->getModelMock('sales/quote_address', array('save'));
        $quote = new Varien_Object();
        $quote->setId(1);
        $quote->setBillingAddress($addressMock);
        $checkoutSessionMock = $this->getModelMock('checkout/session', array('getQuote', 'init', 'save'));
        $checkoutSessionMock->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($quote));
        $this->replaceByMock('model', 'checkout/session', $checkoutSessionMock);

        Mage::app()->getFrontController()->getRequest()->setPost($params);
        $observer = Mage::getModel('dhlaccount/observer');
        $event = new Varien_Event_Observer();
        $observer->savePackageNotificationFlag($event);
        $addressData = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress();
        $this->assertTrue($addressData->getPackageNotification());
    }

    public function testSavePackstationInformation()
    {
        $params = array(
            'shipping' => array(
                'ship_to_packstation' => true,
                'street'              => array('packstation 123'),
                'dhlaccount'          => '1234'
            )
        );

        $addressMock = $this->getModelMock('sales/quote_address', array('save'));
        $quote = new Varien_Object();
        $quote->setId(1);
        $quote->setShippingAddress($addressMock);
        $checkoutSessionMock = $this->getModelMock('checkout/session', array('getQuote', 'init', 'save'));
        $checkoutSessionMock->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($quote));
        $this->replaceByMock('model', 'checkout/session', $checkoutSessionMock);
        Mage::app()->getFrontController()->getRequest()->setPost($params);
        $observer = Mage::getModel('dhlaccount/observer');
        $event = new Varien_Event_Observer();
        $observer->savePackstationInformation($event);
        $addressData = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress();
        $this->assertEquals('1234', $addressData['dhlaccount']);
        $this->assertEquals('Packstation packstation 123', $addressData['street']);
        $this->assertEquals('1', $addressData['ship_to_packstation']);
    }

    public function testResetParcelAnnouncement()
    {
        $params = array(
            'shipping' => array(
                'resetParcelAnnouncement' => true
            )
        );

        $addressMock = $this->getModelMock('sales/quote_address', array('save'));
        $quote = new Varien_Object();
        $quote->setId(1);
        $quote->setBillingAddress($addressMock);
        $checkoutSessionMock = $this->getModelMock('checkout/session', array('getQuote', 'init', 'save'));
        $checkoutSessionMock->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($quote));
        $this->replaceByMock('model', 'checkout/session', $checkoutSessionMock);
        Mage::app()->getFrontController()->getRequest()->setPost($params);
        $observer = Mage::getModel('dhlaccount/observer');
        $event = new Varien_Event_Observer();
        $observer->resetParcelAnnouncement($event);
        $addressData = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress()->getData();
        $this->assertTrue(array_key_exists('dhlaccount', $addressData));
        $this->assertEquals(null, $addressData['dhlaccount']);
    }
}

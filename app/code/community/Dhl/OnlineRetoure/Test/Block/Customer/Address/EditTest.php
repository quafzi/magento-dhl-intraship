<?php
class Dhl_OnlineRetoure_Test_Block_Customer_Address_EditTest
    extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Set up controller params
     */
    protected function setUp()
    {
        $sessionMock = $this->getModelMock('customer/session', array(
                'init', 'renewSession', 'start'
            )
        );
        $this->replaceByMock('model', 'customer/session', $sessionMock);

        $_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
        $this->app()->getRequest()->setBaseUrl($_baseUrl);
        parent::setUp();
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    protected function getCurrentOrder()
    {
        if (!Mage::registry('current_order')) {
            /* @var $order Mage_Sales_Model_Order */
            $order = Mage::getModel('sales/order')->load(13);
            Mage::register('current_order', $order);
        }

        return Mage::registry('current_order');
    }

    /**
     * @return Dhl_OnlineRetoure_Block_Customer_Address_Edit
     */
    protected function getEditBlock()
    {
        return Mage::app()->getLayout()->createBlock('dhlonlineretoure/customer_address_edit');
    }

    /**
     * @loadFixture ../../../../../var/fixtures/config.yaml
     * @loadFixture ../../../../../var/fixtures/customers.yaml
     * @loadFixture ../../../../../var/fixtures/orders.yaml
     */
    public function testGetNameBlockHtml()
    {
        $order     = $this->getCurrentOrder();
        $editBlock = $this->getEditBlock();

        /* @var $nameBlock Mage_Core_Block_Customer_Widget_Name */
        $nameBlockHtml = $editBlock->getNameBlockHtml();
        $this->assertThat($nameBlockHtml, $this->stringContains($order->getShippingAddress()->getLastname()));
    }

    /**
     * @loadFixture ../../../../../var/fixtures/config.yaml
     * @loadFixture ../../../../../var/fixtures/customers.yaml
     * @loadFixture ../../../../../var/fixtures/orders.yaml
     */
    public function testGetBackUrl()
    {
        $order     = $this->getCurrentOrder();
        $editBlock = $this->getEditBlock();

        $session = $this->getModelMock('core/url', array('getUseSession'));
        $session->expects($this->any())
                ->method('getUseSession')
                ->will($this->returnValue(false));
        $this->replaceByMock('model', 'core/url', $session);

        $this->assertStringEndsWith(
            sprintf("sales/order/view/order_id/%d/", $order->getId()),
            $editBlock->getBackUrl()
        );
    }

    /**
     * @loadFixture ../../../../../var/fixtures/config.yaml
     * @loadFixture ../../../../../var/fixtures/customers.yaml
     * @loadFixture ../../../../../var/fixtures/orders.yaml
     */
    public function testGetSaveUrl()
    {
        $order     = $this->getCurrentOrder();
        $hash      = 'foo';

        $session = $this->getModelMock('core/url', array('getUseSession'));
        $session->expects($this->any())
                ->method('getUseSession')
                ->will($this->returnValue(false));
        $this->replaceByMock('model', 'core/url', $session);

        $blockMock = $this->getBlockMock('dhlonlineretoure/customer_address_edit', array('getRequestHash'));
        $blockMock->expects($this->any())
                ->method('getRequestHash')
                ->will($this->onConsecutiveCalls('', $hash));
        $this->replaceByMock('block', 'dhlonlineretoure/customer_address_edit', $blockMock);

        $editBlock = $this->getEditBlock();

        // (1) INTERNAL REQUEST
        $saveUrl = $editBlock->getSaveUrl();
        $this->assertStringEndsWith(sprintf("?order_id=%d", $order->getId()), $saveUrl);
        $this->assertNotContains('hash', $saveUrl);

        // (1) EXTERNAL REQUEST
        $saveUrl = $editBlock->getSaveUrl();
        $this->assertContains(sprintf("order_id=%d", $order->getId()), $saveUrl);
        $this->assertContains(sprintf("hash=%s", $hash), $saveUrl);
    }
}

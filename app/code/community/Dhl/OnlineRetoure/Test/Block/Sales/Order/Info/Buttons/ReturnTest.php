<?php
/**
 * Dhl_OnlineRetoure_Test_Block_Sales_Order_Info_Buttons_ReturnTest
 *
 * @package   Dhl_OnlineRetoure
 * @author    André Herrn <andre.herrn@netresearch.de>
 * @author    Christoph Aßmann <christoph.assmann@netresearch.de>
 * @copyright Copyright (c) 2013 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_OnlineRetoure_Test_Block_Sales_Order_Info_Buttons_ReturnTest
    extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Set up controller params
     */
    protected function setUp()
    {
        $_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
        $this->app()->getRequest()->setBaseUrl($_baseUrl);

        parent::setUp();
    }

    public function testGetReturnUrl()
    {
        $orderId = 12;
        $orderMock = $this->getModelMock('sales/order', array('getId'));
        $orderMock->expects($this->any())
                  ->method('getId')
                  ->will($this->returnValue($orderId));
        $this->replaceByMock('model', 'sales/order', $orderMock);

        $validateHelper = $this->getHelperMock('dhlonlineretoure/validate', array('canShowRetoureLink'));
        $validateHelper->expects($this->any())
                ->method('canShowRetoureLink')
                ->will($this->onConsecutiveCalls(false, true));
        $this->replaceByMock('helper', 'dhlonlineretoure/validate', $validateHelper);

        $session = $this->getModelMock('core/url', array('getUseSession'));
        $session->expects($this->any())
                ->method('getUseSession')
                ->will($this->returnValue(false));
        $this->replaceByMock('model', 'core/url', $session);

        $order = Mage::getModel('sales/order');
        /* @var $block Dhl_OnlineRetoure_Block_Sales_Order_Info_Buttons_Return */
        $block = Mage::app()->getLayout()->createBlock('dhlonlineretoure/sales_order_info_buttons_return');
        $this->assertEmpty($block->getReturnUrl($order));
        $this->assertStringEndsWith("$orderId/", $block->getReturnUrl($order));
    }
}

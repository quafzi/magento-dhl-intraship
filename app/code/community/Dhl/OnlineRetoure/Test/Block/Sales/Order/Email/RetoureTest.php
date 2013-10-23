<?php
/**
 * Dhl_OnlineRetoure_Test_Block_Sales_Order_Email_RetoureTest
 *
 * @package   Dhl_OnlineRetoure
 * @author    André Herrn <andre.herrn@netresearch.de>
 * @author    Christoph Aßmann <christoph.assmann@netresearch.de>
 * @copyright Copyright (c) 2013 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_OnlineRetoure_Test_Block_Sales_Order_Email_RetoureTest
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

    /**
     * @loadFixture ../../../../../../var/fixtures/customers.yaml
     * @loadFixture ../../../../../../var/fixtures/orders.yaml
     */
    public function testGetReturnLinkWithHash()
    {
        $validateHelperMock = $this->getHelperMock('dhlonlineretoure/validate', array('createHashForOrder'));
        $validateHelperMock->expects($this->any())
                  ->method('createHashForOrder')
                  ->will($this->returnValue("HASHABCDEF"));
        $this->replaceByMock('helper', 'dhlonlineretoure/validate', $validateHelperMock);

        /* @var $block Dhl_OnlineRetoure_Block_Sales_Order_Email_Retoure */
        $block = Mage::app()->getLayout()->createBlock('dhlonlineretoure/sales_order_email_retoure');
        $block->setOrder(Mage::getModel("sales/order")->load(11));

        $hash = $block->getReturnLinkWithHash();
        $this->assertNotEmpty($hash);
        $this->assertContains('dhlonlineretoure/address/confirm', $hash);
        $this->assertContains('hash=HASHABCDEF', $hash);
        $this->assertContains('order_id=11', $hash);
    }
}

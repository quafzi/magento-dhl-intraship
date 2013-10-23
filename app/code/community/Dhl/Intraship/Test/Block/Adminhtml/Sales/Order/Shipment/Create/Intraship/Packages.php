<?php
/**
 * Netresearch Dhl Intraship
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
 * @category    Netresearch
 * @package     Dhl_Intraship
 * @copyright   Copyright (c) 2012 Netresearch GmbH & Co. KG (http://www.netresearch.de/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Unit tests for class Dhl_Intraship_Block_Adminhtml_Sales_Order_Shipment_Create_Intraship_Packages
 *
 * @category    Netresearch
 * @package     Dhl_Intraship
 * @author      Thomas Birke <thomas.birke@netresearch.de>
 */
class Dhl_Intraship_Test_Block_Adminhtml_Sales_Order_Shipment_Create_Intraship_Packages
    extends EcomDev_PHPUnit_Test_Case_Config
{

    /**
     * test method getDefaultWeight
     *
     * @test
     */
    public function getDefaultWeight()
    {
        $block = new Dhl_Intraship_Block_Adminhtml_Sales_Order_Shipment_Create_Intraship_Packages();
        $shippingAddress = new Varien_Object(array('country_id' => 'NR'));

        $shipment = $this->getModelMock(
            'sales/order_shipment',
            array('getOrder', 'getShippingAddress')
        );
        $shipment->expects($this->any())
            ->method('getShippingAddress')
            ->will($this->returnValue($shippingAddress));
        Mage::unregister('current_shipment');
        Mage::register('current_shipment', $shipment);

        $config = $this->getModelMock(
            'intraship/config',
            array('useProductWeightAsDefault', 'getWeightDefault')
        );
        $config->expects($this->any())
            ->method('getWeightDefault')
            ->will($this->returnValue(42));
        $config->expects($this->any())
            ->method('useProductWeightAsDefault')
            ->will($this->returnValue(false));
        $this->replaceByMock('model', 'intraship/config', $config);
        $this->assertEquals(42, $block->getDefaultWeight());

        $config->expects($this->any())
            ->method('useProductWeightAsDefault')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'intraship/config', $config);
    }

    public function testGetDefaultWeightWithEmptyCollection()
    {
        $block = new Dhl_Intraship_Block_Adminhtml_Sales_Order_Shipment_Create_Intraship_Packages();
        $shippingAddress = new Varien_Object(array('country_id' => 'NR'));

        $config = $this->getModelMock(
            'intraship/config',
            array('useProductWeightAsDefault', 'getWeightDefault')
        );
        $config->expects($this->any())
            ->method('getWeightDefault')
            ->will($this->returnValue(42));
        $config->expects($this->any())
            ->method('useProductWeightAsDefault')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'intraship/config', $config);

        $order = new Varien_Object();
        $order->setItemsCollection(array());

        $shipment = $this->getModelMock(
            'sales/order_shipment',
            array('getOrder', 'getShippingAddress')
        );
        $shipment->expects($this->any())
            ->method('getShippingAddress')
            ->will($this->returnValue($shippingAddress));
        $shipment->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue($order));
        Mage::unregister('current_shipment');
        Mage::register('current_shipment', $shipment);

        $this->assertEquals(42, $block->getDefaultWeight());

    }

    public function testGetDefaultWeightWithItemProductTypeNotAllowedCollection()
    {
        $block = new Dhl_Intraship_Block_Adminhtml_Sales_Order_Shipment_Create_Intraship_Packages();
        $shippingAddress = new Varien_Object(array('country_id' => 'NR'));

        $config = $this->getModelMock(
            'intraship/config',
            array('useProductWeightAsDefault', 'getWeightDefault')
        );
        $config->expects($this->any())
            ->method('getWeightDefault')
            ->will($this->returnValue(42));
        $config->expects($this->any())
            ->method('useProductWeightAsDefault')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'intraship/config', $config);

        $order = new Varien_Object();
        $items = $this->getItemsCollection();
        $order->setItemsCollection($items);

        $shipment = $this->getModelMock(
            'sales/order_shipment',
            array('getOrder', 'getShippingAddress')
        );
        $shipment->expects($this->any())
            ->method('getShippingAddress')
            ->will($this->returnValue($shippingAddress));
        $shipment->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue($order));
        Mage::unregister('current_shipment');
        Mage::register('current_shipment', $shipment);

        $this->assertEquals(42, $block->getDefaultWeight());
    }

    public function testGetDefaultWeightWithItemCollection()
    {
        $block = new Dhl_Intraship_Block_Adminhtml_Sales_Order_Shipment_Create_Intraship_Packages();
        $shippingAddress = new Varien_Object(array('country_id' => 'NR'));

        $config = $this->getModelMock(
            'intraship/config',
            array(
                'useProductWeightAsDefault',
                'getWeightDefault',
                'getProductTypesForWeightCalculation'
            )
        );
        $config->expects($this->any())
            ->method('getWeightDefault')
            ->will($this->returnValue(42));
        $config->expects($this->any())
            ->method('useProductWeightAsDefault')
            ->will($this->returnValue(true));

        $config->expects($this->any())
            ->method('getProductTypesForWeightCalculation')
            ->will($this->returnValue(array('foo')));
        $this->replaceByMock('model', 'intraship/config', $config);

        $order = new Varien_Object();
        $items = $this->getItemsCollection();
        $order->setItemsCollection($items);

        $shipment = $this->getModelMock(
            'sales/order_shipment',
            array('getOrder', 'getShippingAddress')
        );
        $shipment->expects($this->any())
            ->method('getShippingAddress')
            ->will($this->returnValue($shippingAddress));
        $shipment->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue($order));
        Mage::unregister('current_shipment');
        Mage::register('current_shipment', $shipment);

        $this->assertEquals(13, $block->getDefaultWeight());
    }

    protected function getItemsCollection()
    {
        $itemsCollection = array();
        for ($i=0; $i < 3; $i++) {
            $item = new Varien_Object();
            $item->setWeight(1);
            $item->setQtyOrdered(1);
            $item->setProductType('foo');
            $itemsCollection[] = $item;
        }
        $item = new Varien_Object();
        $item->setWeight(5);
        $item->setQtyOrdered(2);
        $item->setProductType('foo');
        $itemsCollection[] = $item;

        $item = new Varien_Object();
        $item->setWeight(3);
        $item->setQtyOrdered(100);
        $item->setProductType('bar');
        $itemsCollection[] = $item;

        return $itemsCollection;
    }
}

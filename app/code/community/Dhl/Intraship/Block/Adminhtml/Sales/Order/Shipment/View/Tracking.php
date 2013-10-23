<?php
/**
 * Dhl_Intraship_Block_Adminhtml_Sales_Order_Shipment_Create_Tracking
 *
 * @category  Block
 * @package   Dhl_Intraship
 * @author    Stephan Hoyer <stephan.hoyer@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Block_Adminhtml_Sales_Order_Shipment_View_Tracking extends
    Mage_Adminhtml_Block_Sales_Order_Shipment_View_Tracking
{
    /**
     * Extend tracking block to attach intraship form.
     *
     * @return string
     */
    public function _toHtml()
    {
        if (true !== Mage::getModel('intraship/config')->isEnabled()):
            return parent::_toHtml();
        endif;
        $block = $this->getLayout()->createBlock(
            'intraship/adminhtml_sales_order_shipment_view_intraship',
            'intraship_data', array('template' =>
                'intraship/sales/order/shipment/view/intraship.phtml'));
        return parent::_toHtml() . $block->_toHtml();
    }
}

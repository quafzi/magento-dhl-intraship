<?php
/**
 * Dhl_Intraship_Block_Adminhtml_Sales_Order_Grid_Renderer_Icon
 *
 * @category  Block
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Block_Adminhtml_Sales_Order_Grid_Renderer_Icon
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $return   = $row->getRealOrderId();
        /* @var $shipment Dhl_Intraship_Model_Shipment */
        $shipment = Mage::getModel('intraship/shipment')->load(
            $row->getEntityId(), 'order_id');
        if (false === $shipment->isEmpty()):
            if (true === $shipment->isProcessed()):
                $return .= ' <img src="' . $this->getSkinUrl('images/dhl/icon_complete.png') . '" alt="| '. Mage::helper('intraship')->__('DHL Intraship (successful)') .'" title="'. Mage::helper('intraship')->__('Successful') .'"/>';
            elseif (true === $shipment->isFailed()):
                $return .= ' <img src="' . $this->getSkinUrl('images/dhl/icon_failed.png') . '" alt="| '. Mage::helper('intraship')->__('DHL Intraship (failed)') .'>" title="'. Mage::helper('intraship')->__('Failed') .'"/>';
            else:
                $return .= ' <img src="' . $this->getSkinUrl('images/dhl/icon_incomplete.png') . '" alt="| '. Mage::helper('intraship')->__('DHL Intraship (waiting)') .'" title="'. Mage::helper('intraship')->__('On hold') .'"/>';
            endif;
        endif;
        return $return;
    }
}

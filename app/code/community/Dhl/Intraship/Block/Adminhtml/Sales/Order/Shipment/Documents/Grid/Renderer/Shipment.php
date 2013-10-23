<?php
/**
 * Dhl_Intraship_Block_Adminhtml_Sales_Order_Shipment_Documents_Grid_Renderer_Shipment
 *
 * @category  Block
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Block_Adminhtml_Sales_Order_Shipment_Documents_Grid_Renderer_Shipment
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $urlModel = Mage::getModel('adminhtml/url');
        $href = $urlModel->getUrl('adminhtml/sales_shipment/view', array(
            '_current'    => false,
            'shipment_id' => $row->getShipmentId()));
        return sprintf('<a href="%s">%s</a>', $href, $row->getShipmentIncrementId());
    }
}

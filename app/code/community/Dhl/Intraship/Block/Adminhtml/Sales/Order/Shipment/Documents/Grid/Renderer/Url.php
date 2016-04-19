<?php
/**
 * Dhl_Intraship_Block_Adminhtml_Sales_Order_Shipment_Documents_Grid_Renderer_Url
 *
 * @category  Block
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Block_Adminhtml_Sales_Order_Shipment_Documents_Grid_Renderer_Url
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $urlModel = Mage::getModel('adminhtml/url')->setStore($row->getData(
            '_first_store_id'));
        $href = $urlModel->getUrl('adminhtml/shipment/document', array(
            '_current' => false, 'id' => $row->getDocumentId()));
        return sprintf('<a href="%s" target="_blank">%s</a>',
            $href, $this->__('Download PDF')
        );
    }
}

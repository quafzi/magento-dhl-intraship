<?php

/**
 * Dhl_Intraship_Block_Adminhtml_Sales_Order_Grid_Renderer_Icon
 *
 * @category  Block
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @author    Christoph Aßmann <christoph.assmann@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Block_Adminhtml_Sales_Order_Grid_Renderer_Icon
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $default = parent::render($row);

        /* @var $shipment Dhl_Intraship_Model_Shipment */
        $shipment = Mage::getModel('intraship/shipment')->load(
            $row->getEntityId(), 'order_id'
        );

        if ($shipment->isEmpty()) {
            // no shipment, no status to display.
            return $default;
        }

        $format = '%s <img src="%s" alt="| %s" title="%s" />';
        if ($shipment->isProcessed()) {
            $src   = $this->getSkinUrl('images/dhl/icon_complete.png');
            $alt   = Mage::helper('intraship/data')->__('DHL Intraship (successful)');
            $title = Mage::helper('intraship/data')->__('Successful');
        } elseif ($shipment->isFailed()) {
            $src   = $this->getSkinUrl('images/dhl/icon_failed.png');
            $alt   = Mage::helper('intraship/data')->__('DHL Intraship (failed)');
            $title = Mage::helper('intraship/data')->__('Failed');
        } else {
            $src   = $this->getSkinUrl('images/dhl/icon_incomplete.png');
            $alt   = Mage::helper('intraship/data')->__('DHL Intraship (waiting)');
            $title = Mage::helper('intraship/data')->__('On hold');
        }

        return sprintf($format, $default, $src, $alt, $title);
    }

    /**
     * @param Varien_Object $row
     * @return string
     */
    public function renderExport(Varien_Object $row)
    {
        return $this->_getValue($row);
    }
}

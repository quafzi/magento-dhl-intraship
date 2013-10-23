<?php
/**
 * Dhl_Intraship_Block_Adminhtml_Sales_Shipment_Grid_Renderer_Action
 *
 * @category  Block
 * @package   Dhl_Intraship
 * @author    Stephan Hoyer <stephan.hoyer@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Block_Adminhtml_Sales_Shipment_Grid_Renderer_Action
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action
{
    /**
     * Renders action link only if shipment has the valid status
     *
     * @param  Varien_Object    $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $actions = $this->getColumn()->getActions();
        if ($row->getStatus() == Dhl_Intraship_Model_Shipment::STATUS_PROCESSED ||
            $row->getStatus() == Dhl_Intraship_Model_Shipment::STATUS_CLOSED
        ):
            return parent::render($row);
        endif;
    }
}
<?php
/**
 * Dhl_Intraship_Block_Adminhtml_Sales_Order_Shipment_View_Form
 *
 * @category  Block
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Block_Adminhtml_Sales_Order_Shipment_View_Intraship
    extends Mage_Adminhtml_Block_Sales_Order_Shipment_View_Form
{
    /**
     * Retrieve intraship shipment model instance.
     *
     * @return Dhl_Intraship_Model_Shipment
     */
    public function getIntrashipShipment()
    {
        return Mage::getModel('intraship/shipment')->load(
            $this->getShipment()->getEntityId(), 'shipment_id');
    }
}

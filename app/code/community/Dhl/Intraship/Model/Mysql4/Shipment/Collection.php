<?php
/**
 * Dhl_Intraship_Model_Mysql4_Shipment_Collection
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @author    Stephan Hoyer <stephan.hoyer@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_Mysql4_Shipment_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('intraship/shipment');
    }
    
    /**
     * Join sales/order to avoid that shipments with deleted orders were processed
     *
     * @return Dhl_Intraship_Model_Mysql4_Shipment_Collection
     */    
    public function joinOrderTable()
    {
        $this->getSelect()
            ->join(
                array('so' => $this->getTable('sales/order')), 
                'so.entity_id = main_table.order_id',
                '');
        return $this;
    }
    
}

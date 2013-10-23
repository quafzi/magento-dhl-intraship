<?php
/**
 * Dhl_Intraship_Model_Mysql4_Shipment_Document_Collection
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_Mysql4_Shipment_Document_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('intraship/shipment_document');
    }

    /**
     * Convert items to array object.
     *
     * @return ArrayObject
     */
    public function toArrayObject()
    {
        return new ArrayObject($this->_toOptionHash('document_id','file_path'));
    }
}

<?php
/**
 * Dhl_Intraship_Model_Shipment_Document
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_Shipment_Document extends Mage_Core_Model_Abstract
{
    const STATUS_DOWNLOADED = 1;
    const STATUS_DELETED    = -1;

    const TYPE_LABEL              = 'label';
    const TYPE_CUSTOM_DECLARATION = 'custom declaration';
    
    /**
    * Document status "printed" value
    * @var int
    */
    const STATUS_PRINTED = 1;
    
    /**
     * Document status "not printed" value
     * @var int
     */
    const STATUS_NOTPRINTED = 0;
    

    protected function _construct()
    {
        $this->_init('intraship/shipment_document');
    }
    
    /**
     * get the DHL shipment object related to this document
     * 
     * @return Dhl_Intraship_Model_Shipment
     */
    public function getShipment()
    {
        return Mage::getModel('intraship/shipment')->load($this->getShipmentId());
    }
}
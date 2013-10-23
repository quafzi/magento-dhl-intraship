<?php
/**
 * Dhl_Intraship_Model_Shipment_Price
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @author    Andr� Herrn <andre.herrn@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_Shipment_Price extends Mage_Core_Model_Abstract
{
    /**
     * The origin magento order shipment model.
     *
     * @var Mage_Sales_Model_Order_Shipment
     */
    protected $_shipment;
    
    protected $_taxMode;

    
    /**
     * Constructor
     *
     * @see    lib/Varien/Varien_Object#_construct()
     * @return Dhl_Intraship_Model_Shipment_Price
     */
    protected function _construct()
    {
        $this->_init('intraship/shipment_price');
    }
    
    /**
     * Save shipment instance to this instance
     * 
     * @param Mage_Sales_Model_Order_Shipment $_shipment
     * 
     * @return Dhl_Intraship_Model_Shipment_Price
     */
    public function setShipment(Mage_Sales_Model_Order_Shipment $_shipment)
    {
        $this->_shipment = $_shipment;
        return $this;
    }
    
    /**
     * Get Items Totel Price of the Shipment
     * 
     * @return float
     */
    public function getShipmentPrice()
    {       
        //get amount of every item
        return $this->_getItemAmount();          
    }    
    
    /**
     * Loop through every shipment item and calculate the total amount
     * 
     * @return float
     */
    protected function _getItemAmount()
    {
        $amount = 0;
        foreach ($this->_getShipment()->getItemsCollection()->getItems() as $item):     
            if ((float) $item->getPrice()!=0):
                //Load Order Item
                $_order_item = Mage::getModel('sales/order_item')->load($item->getOrderItemId());
                /*
                 * If the order includes tax, getPriceInclTax() returns the item price incl. tax, if not the price excludes tax
                 */
                $_item_price = $_order_item->getPriceInclTax(); 
                $amount += ((float) $_item_price * (float) $item->getQty());
            endif;            
        endforeach;

        return $amount;
    } 

    /**
     * Retrives original magento shipment model for current shipment.
     *
     * @return Mage_Sales_Model_Order_Shipment
     */
    protected function _getShipment()
    {
        return $this->_shipment;
    }
    
    public function getCODOrderTotal()
    {
        //Get Grand Total
        $amount = $this->_getShipment()->getOrder()->getGrandTotal();
        
        //If enabled and receiver country is germany, remove COD Charge from Grand Total
        if (Mage::getModel('intraship/config')->removeCODCharge()
            && "DE" == $this->getReceiverCountryId()):
            $amount = $amount - Mage::getModel('intraship/config')->getCODCharge();
        endif;        
        
        //Check if amount is < 0
        if ($amount<0):
            $amount = 0;
        endif;
        
        return (float) $amount;
    }
}
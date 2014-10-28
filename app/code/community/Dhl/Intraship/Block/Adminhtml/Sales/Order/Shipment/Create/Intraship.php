<?php
/**
 * Dhl_Intraship_Block_Adminhtml_Sales_Order_Shipment_Create_Intraship
 *
 * @category  Block
 * @package   Dhl_Intraship
 * @author    Stephan Hoyer <stephan.hoyer@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Block_Adminhtml_Sales_Order_Shipment_Create_Intraship
    extends Mage_Adminhtml_Block_Template
{
    /**
     * ISO country code.
     *
     * @var string
     */
    protected $_countryId;

    /**
     * Internal constructor, that is called from real constructor.
     *
     * @return Dhl_Intraship_Block_Adminhtml_Sales_Order_Shipment_Create_Intraship
     */
    public function _construct()
    {
        parent::_construct();
        $this->_countryId = strtoupper($this
            ->getMageShipment()
            ->getShippingAddress()
            ->getCountryId());
    }

    /**
     * Returns possible options for DHL prrofile
     *
     * @return array
     */
    public function getProfileOptions()
    {
        return Mage::getModel('intraship/system_config_source_profile')
            ->toOptionArray(false, $this->getCountryId());
    }

    /**
     * Ger MAGE shipment
     *
     * @return Mage_Sales_Model_Order_Shipment
     */
    public function getMageShipment()
    {
        return Mage::registry('current_shipment');
    }

    /**
     * Get country id (ISO).
     *
     * @return string
     */
    public function getCountryId()
    {
        return $this->_countryId;
    }

    /**
     * Is cash on delivery?
     *
     * @return boolean
     */
    public function isCOD()
    {
        return Mage::getModel('intraship/shipment')->isCOD(
            $this->getMageShipment()->getOrder()->getPayment()->getMethod());
    }
    
    /**
     * Check if shipping method is disabled
     *
     * @return boolean
     */
    protected function getShipWithIntraship()
    {
        return Mage::getModel('intraship/config')
            ->isAllowedShippingMethod($this->getMageShipment()->getOrder()->getShippingMethod());
    }
}
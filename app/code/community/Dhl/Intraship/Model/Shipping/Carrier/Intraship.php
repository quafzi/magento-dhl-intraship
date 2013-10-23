<?php

class Dhl_Intraship_Model_Shipping_Carrier_Intraship
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface {

    protected $_code = 'intraship';
    
    public function isActive()
    {
        return true;
    }
    
    public function isTrackingAvailable()
    {
        return true;
    }
    
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        return Mage::getModel('shipping/rate_result');
    }
    
    public function getAllowedMethods()
    {
        return array($this->_code => 'DHL Intraship');
    }
    
     /**
     * get tracking information
     *
     * @see Mage_Usa_Model_Shipping_Carrier_Abstract::getTrackingInfo()
     * @return Mage_Shipping_Model_Tracking_Result|false
     */
    public function getTrackingInfo($tracking)
    {
        $result = $this->getTracking($tracking);

        if ($result instanceof Mage_Shipping_Model_Tracking_Result) {
            if ($trackings = $result->getAllTrackings()) {
                return $trackings[0];
            }
        } elseif (is_string($result) && 0 < strlen($result)) {
            return $result;
        }

        return false;
    }
    
    
    /**
     * @see Mage_Usa_Model_Shipping_Carrier_Dhl::getTracking()
     * @return Mage_Shipping_Model_Tracking_Result
     */
    public function getTracking($trackings)
    {
        if (!is_array($trackings)) {
            $trackings = array($trackings);
        }

        $result = Mage::getModel('shipping/tracking_result');
        foreach ($trackings as $trackingNumber) {
            $status = Mage::getModel('shipping/tracking_result_status');
            $status->setCarrierTitle('DHL Intraship');
            $status->setCarrier('Intraship');
            $status->setTracking($trackingNumber);
            $status->setPopup(true);
            $status->setUrl(Mage::getModel('intraship/config')->getTrackingUrl($trackingNumber));
            $result->append($status);
        }

        return $result;
    }
}

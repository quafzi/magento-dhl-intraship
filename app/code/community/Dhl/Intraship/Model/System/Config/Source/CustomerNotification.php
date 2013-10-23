<?php
/**
 * Dhl_Intraship_Model_System_Config_Source_CustomerNotification
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @autor     Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_System_Config_Source_CustomerNotification
{
    const NONE                     = 0;
    const IMMEDIATELY              = 'immediately';
    const WAIT_FOR_TRACKING_NUMBER = 'wait_for_tracking_number';
    
    /**
     * Returns array with product weight unit
     *
     * @return array    $return
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::NONE,
                'label' => Mage::helper('intraship')->__('No')
            ),
            array(
                'value' => self::IMMEDIATELY,
                'label' => Mage::helper('intraship')->__('immediately')
            ),
            array(
                'value' => self::WAIT_FOR_TRACKING_NUMBER,
                'label' => Mage::helper('intraship')->__('wait for tracking number')
            ),
        );
    }
}
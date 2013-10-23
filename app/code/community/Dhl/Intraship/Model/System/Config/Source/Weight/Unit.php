<?php
/**
 * Dhl_Intraship_Model_System_Config_Source_Weight_Unit
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @autor     Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_System_Config_Source_Weight_Unit
{
    /**
     * Returns array with product weight unit
     *
     * @return array    $return
     */
    public function toOptionArray()
    {
        foreach(Dhl_Intraship_Model_Config::$units as $profile):
            $return[] = array(
                'value' => $profile,
                'label' => Mage::helper('intraship')->__($profile)
            );
        endforeach;
        return $return;
    }
}

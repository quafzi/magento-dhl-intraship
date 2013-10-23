<?php
/**
 * Dhl_Intraship_Model_System_Config_Source_Profile_Bpi
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @autor     Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_System_Config_Source_Profile_Bpi
{
    /**
     * Returns array for backend options for BPI.
     *
     * @return array    $return
     */
    public function toOptionArray()
    {
        $profiles = Mage::getModel('intraship/config')
            ->getAllProfiles();
        $return = array();
        foreach($profiles as $profile):
            $return[] = array(
                'value' => $profile,
                'label' => Mage::helper('intraship')->__($profile . ' world package')
            );
        endforeach;
        return $return;
    }
}
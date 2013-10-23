<?php
/**
 * Dhl_Intraship_Model_System_Config_Source_Profile
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @author    Stephan Hoyer <stephan.hoyer@netresearch.de>
 * @autor     Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_System_Config_Source_Profile
{
    /**
     * Returns array for backend options.
     *
     * @param  boolean  $all
     * @param  string   $countryId (ISO)
     * @return array    $return
     */
    public function toOptionArray($all = false, $countryId = null,$gridActionMode = false)
    {
        if (null !== $countryId):
            try {
                $profiles = Mage::getModel('intraship/config')
                    ->getShipmentTypes($countryId)->getArrayCopy();
                $profiles = array_flip($profiles);
            } catch (Exception $e) {
                $profiles = Dhl_Intraship_Model_Config::$profiles;
            }
        else:
            if (true === $all):
                $profiles = Dhl_Intraship_Model_Config::$profiles;
            else:
                $profiles = Mage::getModel('intraship/config')
                    ->getAllEnabledProfiles()
                    ->getArrayCopy();
            endif;
        endif;
        $return = array();
        $suffix = ('DE' != $countryId) ? ' world package' : null;
        foreach($profiles as $profile):
            $return[] = array(
                'value' => $profile,
                'label' => Mage::helper('intraship')->__($profile . $suffix)
            );
            if (true === $gridActionMode): //If this is the output for the sales order grid view show all options
	            $return[] = array(
	                'value' => $profile,
	                'label' => Mage::helper('intraship')->__($profile)
	            );
            endif;
        endforeach;
        return $return;
    }
}

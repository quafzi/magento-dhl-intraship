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
class Dhl_Intraship_Model_System_Config_Source_Format
{
    const A4 = 'A4';
    const A5 = 'A5';
    
    /**
     * Returns array with product weight unit
     *
     * @return array    $return
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::A4,
                'label' => self::A4
            ),
            array(
                'value' => self::A5,
                'label' => self::A5
            ),
        );
    }
}
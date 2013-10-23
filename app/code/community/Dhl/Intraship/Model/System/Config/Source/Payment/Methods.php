<?php
/**
 * Dhl_Intraship_Model_System_Config_Source_Payment_Methods
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @autor     Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_System_Config_Source_Payment_Methods
{
    /**
     * Get payment methods.
     *
     * @return array $methods
     */
    public function toOptionArray()
    {
        return Mage::helper('payment')->getPaymentMethodList(true, true, true);
    }
}
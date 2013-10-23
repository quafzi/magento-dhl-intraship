<?php
/**
 * Dhl_Intraship_Model_System_Config_Source_Shipping_Methods
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @autor     André Herrn <andre.herrn@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_System_Config_Source_Shipping_Methods
    extends Mage_Adminhtml_Model_System_Config_Source_Shipping_Allmethods
{
    /**
     * Get payment methods.
     *
     * @return array $methods
     */
    public function toOptionArray($isActiveOnlyFlag=false)
    {
        /*
         * Only active shipping methods available because of BUG in CE 1.7.0.0
         * BUG Issue: http://www.magentocommerce.com/bug-tracking/issue?issue=13411
         */
        //return parent::toOptionArray(false);
        return parent::toOptionArray(true);
    }
}
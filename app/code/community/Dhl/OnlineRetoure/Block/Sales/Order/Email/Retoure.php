<?php
/**
 * Dhl_OnlineRetoure_Block_Sales_Order_Email_Retoure
 *
 * @package   Dhl_Account
 * @author    André Herrn <andre.herrn@netresearch.de>
 * @copyright Copyright (c) 2013 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_OnlineRetoure_Block_Sales_Order_Email_Retoure extends Mage_Core_Block_Template
{
    /**
     * Generate the return link with Hash
     *
     * @return string
     */
    public function getReturnLinkWithHash()
    {
        /* @var $helper Dhl_OnlineRetoure_Helper_Validate */
        $helper = Mage::helper('dhlonlineretoure/validate');

        $hash = $helper->createHashForOrder($this->getOrder());

        $helper->log($helper->__(
            "Created hash '%s' for order '%s' to send by email",
            $hash,
            $this->getOrder()->getIncrementId()
        ), Zend_Log::INFO);

        $params = $helper->getUrlParams($this->getOrder()->getId(), $hash);
        return Mage::getUrl('dhlonlineretoure/address/confirm', $params);
    }
}
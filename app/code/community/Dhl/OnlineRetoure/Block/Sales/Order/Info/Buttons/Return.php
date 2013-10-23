<?php
/**
 * Dhl OnlineRetoure
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to
 * newer versions in the future.
 *
 * @category    Dhl
 * @package     Dhl_OnlineRetoure
 * @copyright   Copyright (c) 2013 Netresearch GmbH & Co. KG (http://www.netresearch.de/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * DHL OnlineRetoure return link for customer account.
 *
 * BEWARE: This class must not extend Mage_Sales_Block_Order_Info_Buttons as
 * it is not available in CE 1.5.x. {@see getOrder()} is duplicated here instead.
 *
 * @category    Dhl
 * @package     Dhl_OnlineRetoure
 * @author      André Herrn <andre.herrn@netresearch.de>
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Dhl_OnlineRetoure_Block_Sales_Order_Info_Buttons_Return
    extends Mage_Core_Block_Template
//     extends Mage_Sales_Block_Order_Info_Buttons
{
    /**
     * Retrieve current order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    /**
     * Get url for online return
     *
     * @param Mage_Sales_Order $order
     * @return string
     */
    public function getReturnUrl(Mage_Sales_Model_Order $order)
    {
        /* @var $helper Dhl_OnlineRetoure_Helper_Validate */
        $helper = Mage::helper('dhlonlineretoure/validate');
        if (!$helper->canShowRetoureLink($order)) {
            return '';
        }

        return $this->getUrl('dhlonlineretoure/address/confirm', array('order_id' => $order->getId()));
    }

}
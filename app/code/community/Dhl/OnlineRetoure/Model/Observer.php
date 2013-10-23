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
 * DHL OnlineRetoure Observer
 *
 * @category    Dhl
 * @package     Dhl_OnlineRetoure
 * @author      André Herrn <andre.herrn@netresearch.de>
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Dhl_OnlineRetoure_Model_Observer
{
    /**
     * Add layout handle for return link depending on Magento version.
     *
     * @param Varien_Event_Observer $observer
     * @return Dhl_OnlineRetoure_Model_Observer
     */
    public function addReturnButtonHandle(Varien_Event_Observer $observer)
    {
        /* @var $action Mage_Core_Controller_Front_Action */
        $action = $observer->getEvent()->getAction();
        if ($action->getFullActionName() !== 'sales_order_view') {
            return $this;
        }

        /* @var $update Mage_Core_Model_Layout_Update */
        $update = $observer->getEvent()->getLayout()->getUpdate();
        if (version_compare(Mage::getVersion(), '1.6.0.0', '<')) {
            $update->addHandle('dhlonlineretoure_button_return_15');
        } else {
            $update->addHandle('dhlonlineretoure_button_return_16');
        }

        return $this;
    }
}

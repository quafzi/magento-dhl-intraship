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
 * DHL OnlineRetoure Observer Test
 *
 * @category    Dhl
 * @package     Dhl_OnlineRetoure
 * @author      André Herrn <andre.herrn@netresearch.de>
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Dhl_OnlineRetoure_Test_Model_ObserverTest extends EcomDev_PHPUnit_Test_Case
{
    public function testReturnButtonLoaded()
    {
        $handle_15 = 'dhlonlineretoure_button_return_15';
        $handle_16 = 'dhlonlineretoure_button_return_16';

        /* @var $observer Dhl_OnlineRetoure_Model_Observer */
        $observer = Mage::getModel('dhlonlineretoure/observer');

        $action = new Varien_Object();
        $action->setFullActionName('sales_order_view');
        $event = new Varien_Event(array(
            'action' => $action,
            'layout' => Mage::getSingleton('core/layout'),
        ));

        $eventObserver = new Varien_Event_Observer();
        $eventObserver->setEvent($event);

        $observer->addReturnButtonHandle($eventObserver);
        $handles = $eventObserver->getEvent()->getLayout()->getUpdate()->getHandles();

        if (version_compare(Mage::getVersion(), '1.6.0.0', '<')) {
            $this->assertContains($handle_15, $handles);
        } else {
            $this->assertContains($handle_16, $handles);
        }
    }
}

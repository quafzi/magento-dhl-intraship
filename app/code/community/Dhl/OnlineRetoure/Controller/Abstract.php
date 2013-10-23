<?php
/**
 * Dhl_OnlineRetoure_Controller_Abstract
 *
 * @category   Dhl
 * @package    Dhl_OnlineRetoure
 * @author     André Herrn <andre.herrn@netresearch.de>
 * @copyright  Copyright (c) 2013 Netresearch GmbH & Co. KG
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Dhl_OnlineRetoure_Controller_Abstract extends Mage_Core_Controller_Front_Action
{
    /**
     * Order instance
     */
    protected $_order;

    /**
     * DHL Retoure Error Page
     *
     * @return void
     */
    public function errorAction()
    {
        //Load and render basic layout
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Retrieve customer session object
     *
     * @return Mage_Customer_Model_Session
     */
    protected function getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * Check if the user is allowed to see the page and load the current order,
     * otherwise decline access to any controller action.
     *
     * Condition for logged in users: customer can view the order.
     * Condition for guests: given hash is valid.
     *
     * @param  int $orderId
     * @return boolean
     * @throws Dhl_OnlineRetoure_Model_Validate_Exception
     */
    protected function loadValidOrder($orderId = null, $hash = null)
    {
        /* @var $validateHelper Dhl_OnlineRetoure_Helper_Validate */
        $validateHelper = Mage::helper('dhlonlineretoure/validate');

        if (null === $orderId) {
            $orderId = (int) $this->getRequest()->getParam('order_id');
        }
        //Pre-check if order_id is given
        $validateHelper->isOrderIdValid($orderId);
        
        if (null === $hash) {
            $hash = $this->getRequest()->getParam('hash');
        }
        $order = Mage::getModel('sales/order')->load($orderId);

        // Check hash case
        $validateHelper->isHashRequest()
            && $validateHelper->isHashValid($hash, $order)
            && $validateHelper->isOrderValid($order);

        // Check internal case
        $validateHelper->isInternalRequest()
            && $validateHelper->isCustomerValid($order)
            && $validateHelper->isOrderValid($order);

        Mage::unregister('current_order');
        Mage::register('current_order', $order);
        return true;
    }
}
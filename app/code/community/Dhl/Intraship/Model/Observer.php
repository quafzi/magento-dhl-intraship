<?php
/**
 * Dhl_Intraship_Model_Observer
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @author    Stephan Hoyer <stephan.hoyer@netresearch.de>
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_Observer
{
    /**
     * @var boolean last created shipment
     */
    protected $_validationFails = false;

    /**
     * Validates user input of intraship data in pre dispatch.
     *
     * @param  Varien_Event_Observer    $observer
     * @return void
     */
    public function validateIntrashipShipmentData(Varien_Event_Observer $observer)
    {
        if (false === Mage::getModel('intraship/config')->isEnabled()
            || false === $this->shipWithIntrashipManual()):
            return;
        endif;

        // Validate post data.
        $this->_validationFails = !$this->_isValidData(
            Mage::app()->getRequest()->getPost());
    }

    /**
     * Validated given Data
     *
     * @param  array    $data
     * @return boolean
     */
    protected function _isValidData($data)
    {
        $data = $data['shipment'];
        if (!isset($data['packages']) || !isset($data['settings'])):
            return false;
        endif;
        return true;
    }

    /**
     * Clear previous success messages and add error message. This needs to be
     * done as in CE 1.6.2.0 a success message is added even before the shipment
     * is actually saved (DHLIS-501). As session messages are already
     * translated, there is no reliable way to remove only the "shipment has
     * been created" message. Therefore all previous success messages get
     * deleted here.
     *
     * @param string $errorMessage
     */
    protected function _setValidationFailure($errorMessage)
    {
        $this->_validationFails = true;

        /* @var $messageCollection Mage_Core_Model_Message_Collection */
        $messageCollection = Mage::getSingleton('adminhtml/session')
            ->getMessages();

        $messages = $messageCollection->getItemsByType(Mage_Core_Model_Message::SUCCESS);
        /* @var $message Mage_Core_Model_Message_Abstract */
        foreach ($messages as $message) {
            $messageCollection->deleteMessageByIdentifier($message->getIdentifier());
        }

        Mage::throwException($errorMessage);
    }

    /**
     * Throws exception if enterted data was not valid
     *
     * @param Varien_Event_Observer $observer
     */
    public function checkShipment(Varien_Event_Observer $observer)
    {
        $data = Mage::app()->getRequest()->getPost();
        if (!isset($data['shipment'])):
            //Check if shipping method is disabled
            if (false === Mage::getModel('intraship/config')->isAllowedShippingMethod(
                   $observer->getShipment()->getOrder()->getShippingMethod())):
                 return;
            endif;

            // @SEE Dhl_Intraship_ShipmentController::massAction()
            $countryId = $observer->getShipment()->getShippingAddress()->getCountryId();
            // Receiver country is not in European Union (EU).
            if (true === Mage::getModel('intraship/config')->isInternationalShipping($countryId)):
                /* DHLIS-313: do not permit shipping out of EU */
                return;
            endif;

            //Check if the shipment does exceed the maximum allowed package weight
            if (!$this->_checkWeightOfMassActionShipment($observer)):
                Mage::throwException(Mage::helper('intraship')->__(
                    'The shipment does exceed the maximum allowed package weight of %s kg!',
                    Dhl_Intraship_Model_Shipment::MAX_WEIGHT_KG));
            endif;


            //Insurance Check:
            // - insurance request during mass action create
            $insuranceMassActionReq = array_key_exists('insurance', $data)
                && '1' === $data['insurance'];
            // - insurance request during autocreate
            $insuranceAutoCreateReq = !array_key_exists('insurance', $data)
                && '1' === Mage::getModel('intraship/config')->getAutocreateSettings($countryId)->offsetGet('insurance');

            // - throw exception if insurance is requested and shipment does not satisfy requirements
            if ( ($insuranceMassActionReq || $insuranceAutoCreateReq)
                && !$this->_checkInsuranceOfMassActionShipment($observer)
            ) {
                $message = Mage::helper('intraship')->__(
                    'The insurance option is not possible on a total amount greater than %s.',
                    number_format(Dhl_Intraship_Model_Shipment::INSURANCE_A, 2, ',', '.')
                );
                Mage::throwException($message);
            }

            return;
        endif;

        /*
         * Here start the validations for the manual intraship create process
         * Validation by POST Data
         */
        // Receiver country is not in European Union (EU).
        if (false === Mage::getModel('intraship/config')->isEnabled()
            || false === $this->shipWithIntrashipManual()
            || true === $this->checkIfIsInternationalShipping()
        ):
            /* DHLIS-313: do not permit shipping out of EU */
            return;
        endif;

        // Check each package weight
        foreach ($data['shipment']['packages'] as $package):
            $weight = (float) $package['weight'];
            if (isset($package['delete']) && $package['delete'] == 1):
                continue;
            endif;
            if ($weight > Dhl_Intraship_Model_Shipment::MAX_WEIGHT_KG):
                $this->_setValidationFailure(Mage::helper('intraship')->__(
                    'The shipment does exceed the maximum allowed package weight of %s kg!',
                    Dhl_Intraship_Model_Shipment::MAX_WEIGHT_KG));
            endif;
        endforeach;

        // Return if insurance is choosen but not possible.
        if ($data['shipment']['settings']['insurance'] == 1 && true !== $this->checkInsurance()):
            $this->_setValidationFailure(Mage::helper('intraship')->__(
                'The insurance option is not possible on a total amount greater than %s.',
                number_format(Dhl_Intraship_Model_Shipment::INSURANCE_A, 2, ',', '.')));
        endif;
        // Return false if payment method is COD and partial shipment
        if (true === Mage::getModel('intraship/config')->isEnabled() &&
            true === $this->_checkIfIsPartialShipment()):
            $this->_setValidationFailure(Mage::helper('intraship')->__(
                'Partial shipment is not allowed for cash on delivery shipments.'));
        endif;
    }

    /**
     * Check if the weight of the shipment is greater then the maximum
     *
     * @param Varien_Event_Observer $observer
     *
     * @return boolean
     */
    protected function _checkWeightOfMassActionShipment($observer)
    {
        //intraship_shipment exists already?
        $intrashipShipment = Mage::getModel('intraship/shipment')->load($observer->getShipment()->getId(), 'shipment_id');
        if (false === is_null($intrashipShipment->getId())):
            //shipment exists and was validated already, don't check again
            return true;
        endif;

        if (true === Mage::getModel('intraship/config')->useProductWeightAsDefault()):
            $weight = 0;
            foreach ($observer->getShipment()->getItemsCollection() as $item):
                $weight += Mage::helper('intraship')->convertWeight(
                    (float) $item->getWeight() * (float) $item->getQty());
            endforeach;
        else:
            $weight = Mage::getModel('intraship/config')->getWeightDefault(
                $observer->getShipment()->getShippingAddress()->getCountryId()
            );
        endif;

        if ($weight > Dhl_Intraship_Model_Shipment::MAX_WEIGHT_KG):
            return false;
        else:
            return true;
        endif;
    }

    /**
     * Check if insurance is chosen but not possible.
     *
     * @return boolean
     */
    public function checkInsurance()
    {
        /** @var Mage_Sales_Model_Order_Shipment $shipment */
        $shipment = Mage::registry('current_shipment');
        return Mage::helper('intraship/data')->isInsurable($shipment);
    }

    /**
     * Check if insurance is chosen but not possible.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return boolean
     */
    protected function _checkInsuranceOfMassActionShipment($observer)
    {
        /** @var Mage_Sales_Model_Order_Shipment $shipment */
        $shipment = $observer->getShipment();
        return Mage::helper('intraship/data')->isInsurable($shipment);
    }

    /**
     * Check if payment method is COD and the shipment is a partial shipment
     *
     * @return boolean
     */
    protected function _checkIfIsPartialShipment()
    {
        $shipment = Mage::registry('current_shipment');
        $_order = $shipment->getOrder();

        //Check if it is a cod order
        if (false === Mage::getModel('intraship/shipment')->isCOD($_order->getPayment()->getMethod())):
            return false;
        endif;

        //Build shipment item array
        $shipment_items = array();
        foreach ($shipment->getItemsCollection()->getItems() as $_shipment_item):
            if ((float) $_shipment_item->getPrice()==0): //Ignore doublette simple/configurable shipment items
                //printf("shipment skipped item# %s - %s - %s - %s#<br />",$_shipment_item->getSku(),$_shipment_item->getQty(),$_shipment_item->getPrice(),$_shipment_item->getParentItemId());
                continue;
            endif;
            $shipment_items[$_shipment_item->getSku()] = $_shipment_item->getQty();
        endforeach;

        //Loop through every order item and look if it is existing in the shipment too
        foreach ($_order->getAllItems() as $_order_item): //Ignore doublette simple/configurable order items
            if ((float) $_order_item->getPrice()==0 || ''!=$_order_item->getParentItemId() || $_order_item->getIsVirtual()): //Skip
                continue;
            endif;
            if (false === isset($shipment_items[$_order_item->getSku()])):
                return true;
            endif;
            if ($shipment_items[$_order_item->getSku()] != (int) $_order_item->getQtyOrdered()):
                return true;
            endif;
            //printf("enthalten# %s - %s - %s - %s#<br />",$_order_item->getSku(),$_order_item->getQtyOrdered(),$_order_item->getPrice(),$_order_item->getParentItemId());
        endforeach;

        return false;
    }

    /**
     * Check if receiver country is not in European Union (EU).
     *
     * @return boolean
     */
    public function checkIfIsInternationalShipping()
    {
        $shipment = Mage::registry('current_shipment');
        if (!$shipment instanceof Mage_Sales_Model_Order_Shipment):
            return true;
        endif;
        $countryId = $shipment->getShippingAddress()->getCountryId();
        return Mage::getModel('intraship/config')->isInternationalShipping(
            $countryId);
    }

    /**
     * Saves additional intraship data to shipment
     *
     * @param Varien_Event_Observer $observer
     */
    public function saveIntrashipShipmentData(Varien_Event_Observer $observer)
    {
        // Return if service is disabled.
        if (false === Mage::getModel('intraship/config')->isEnabled()):
            return;
        endif;

        // Return if shipment should be created without intraship-connection
        if (false === $this->shipWithIntrashipManual()):
            return;
        endif;

        // Return if current shipment is null.
        if (null === Mage::registry('current_shipment')):
            return;
        endif;
        // Return if validation fails.
        if ($this->_validationFails):
            return;
        endif;

        // Create intraship shipment.
        $data     = Mage::app()->getRequest()->getPost();
        $data     = $data['shipment'];
        // Clean package data
        $packages = null;
        foreach ($data['packages'] as $key => $package):
            $data['packages']['weight'] = (float) str_replace(',', '.', $package['weight']);
            if (isset($package['delete']) && $package['delete'] == 1):
                continue;
            endif;
            $packages[$key] = $data['packages'][$key];
        endforeach;
        $mageShipment = Mage::registry('current_shipment');
        $shipment     = Mage::getModel('intraship/shipment')
            ->setShipmentId($mageShipment->getId())
            ->setOrderId($mageShipment->getOrder()->getEntityId())
            ->setSettings($data['settings'])
            ->setPackages($packages)
            ->save();
    }


    /**
     * Queue cancel of all shipments of canceled orders.
     *
     * @param  Varien_Event_Observer    $observer
     * @return void
     */
    public function cancelShipmentsForCanceledOrders($observer=null)
    {
        $collection = Mage::getModel('intraship/shipment')->getCollection();
        $collection->addFieldToFilter('status', array('in' => array(
            Dhl_Intraship_Model_Shipment::STATUS_NEW_QUEUED,
            Dhl_Intraship_Model_Shipment::STATUS_NEW_RETRY,
            Dhl_Intraship_Model_Shipment::STATUS_PROCESSED)));
        foreach ($collection as $shipment):
            if ($shipment->getShipment()->getOrder()->getState() ==
                Mage_Sales_Model_Order::STATE_CANCELED):
                $shipment->cancel();
            endif;
        endforeach;
    }

    /**
     * Close all shipments of closed orders.
     *
     * @param  Varien_Event_Observer    $observer
     * @return void
     */
    public function closeShipmentsForClosedOrCompletedOrders($observer=null)
    {
        $collection = Mage::getModel('intraship/shipment')->getCollection();
        $collection->addFieldToFilter('status', array('in' => array(
            Dhl_Intraship_Model_Shipment::STATUS_PROCESSED)));
        foreach ($collection as $shipment):
            if ($shipment->getShipment()->getOrder()->getState() ==
                Mage_Sales_Model_Order::STATE_CLOSED ||
                $shipment->getShipment()->getOrder()->getState() ==
                Mage_Sales_Model_Order::STATE_COMPLETE
            ):
                $shipment->close();
            endif;
        endforeach;
    }

    /**
     * Append GoGreen option to html output.
     *
     * @param  Varien_Event_Observer    $observer
     * @return void
     */
    public function appendGoGreenOptionToJson(Varien_Event_Observer $observer)
    {
        if (false === Mage::getModel('intraship/config')->isCheckoutGoGreen()):
            return;
        endif;

        /* @var $response Mage_Core_Controller_Response_Http */
        $response = $observer->getControllerAction()->getResponse();
        /* @var $layout Mage_Core_Model_Layout */
        $layout   = $observer->getControllerAction()->getLayout();
        /* @var $block Dhl_Intraship_Block_Checkout_Onepage_Shipping_Gogreen */
        $block    = $layout->createBlock(
            'intraship/checkout_onepage_shipping_gogreen', 'onepage_gogreen');
        $array = Zend_Json::decode($response->getBody());
        // Prevent notice if shipment address is diffent to billing address.
        if (!isset($array['update_section'])):
            return;
        endif;
        $array['update_section']['html'] .= $block->toHtml();
        // Append go green option to shipment method html output.
        $response->setBody(Zend_Json::encode($array));
    }

    /**
     * Append GoGreen option to html output.
     *
     * @param  Varien_Event_Observer    $observer
     * @return void
     */
    public function appendGoGreenOptionToHtml(Varien_Event_Observer $observer)
    {
        if (false === Mage::getModel('intraship/config')->isCheckoutGoGreen()):
            return;
        endif;
        /* @var $response Mage_Core_Controller_Response_Http */
        $response = $observer->getControllerAction()->getResponse();
        /* @var $layout Mage_Core_Model_Layout */
        $layout   = $observer->getControllerAction()->getLayout();
        /* @var $block Dhl_Intraship_Block_Checkout_Onepage_Shipping_Gogreen */
        $block    = $layout->createBlock(
            'intraship/checkout_onepage_shipping_gogreen', 'onepage_gogreen');
        // Append go green option to shipment method html output.
        $response->appendBody($block->toHtml());
    }

    /**
     * Write GoGreen option form checkout to session.
     *
     * @param  Varien_Event_Observer    $observer
     * @return void
     */
    public function saveGoGreenOptionInSession(Varien_Event_Observer $observer)
    {
        if (false === Mage::getModel('intraship/config')->isCheckoutGoGreen()):
            return;
        endif;

        $option = Mage::app()->getRequest()->getPost('is_gogreen', 0);
        Mage::getSingleton('intraship/session')->setData('is_gogreen', $option);
    }

    /**
     * Set GoGreen option to order.
     *
     * @param  Varien_Event_Observer    $observer
     * @return void
     */
    public function setGoGreenOptionToOrder(Varien_Event_Observer $observer)
    {
        if (false === Mage::getModel('intraship/config')->isCheckoutGoGreen()):
            return;
        endif;

        /* @var $order Mage_Sales_Model_Order */
        $order   = $observer->getOrder();
        /* @var $session Dhl_Intraship_Model_Session */
        $session = Mage::getSingleton('intraship/session');
        // If checkout session has item "is_gogreen" store value in order.
        if (true === $session->hasData('is_gogreen')):
            $order->setIsGogreen((int) $session->getData('is_gogreen'));
            $order->save();
            // Remove option from session storage.
            $session->unsetData('is_gogreen');
        endif;
    }

    /**
     * Queue Cron.
     *
     * @param  Varien_Event_Observer | Mage_Cron_Model_Schedule $observer
     * @return Dhl_Intraship_Model_Observer $this
     */
    public function cronQueue($observer = null)
    {
        if (true === Mage::getModel('intraship/config')->isEnabled()):
            Mage::getModel('intraship/gateway')->processQueue();
        endif;
        return $this;
    }

    /**
     * Autocreate Cron.
     *
     * @param  Varien_Event_Observer | Mage_Cron_Model_Schedule $observer
     * @return Dhl_Intraship_Model_Observer $this
     */
    public function cronAutocreate($observer = null)
    {
        /* @var $config Dhl_Intraship_Model_Config */
        $config    = Mage::getModel('intraship/config');
        if (true === $config->isEnabled() && true === $config->isAutocreate()):
            Mage::getModel('intraship/autocreate')->execute();
        endif;
        return $this;
    }

    /**
     * Check for manual requests if the shipment should be send with intraship or not
     *
     * @return boolean
     */
    public function shipWithIntrashipManual()
    {
        $post = Mage::app()->getRequest()->getPost();
        if (isset($post['ship_with_dhl']) && $post['ship_with_dhl'] == 0):
            return false;
        else:
            return true;
        endif;
    }

    public function intrashipShipmentDocumentPrint(Varien_Event_Observer $observer)
    {
        /* @var $collection Dhl_Intraship_Model_Mysql4_Shipment_Document_Collection */
        $collection = $observer->getCollection();

        if ($collection->count() > 0) {
            try {
                foreach ($collection as $document) {
                    /* @var $document Dhl_Intraship_Model_Shipment_Document */
                    $document->setPrinted(true)->save();
                }

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('intraship')->__(
                        'Total of %d document(s) were successfully marked as \'printed\'', count($collection)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
    }
}

<?php
/**
 * Dhl_Intraship_Model_Autocreate
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @author    André Herrn <andre.herrn@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_Autocreate
{
    /**
     * @var Dhl_Intraship_Model_Config
     */
    protected $_config;

    /**
     * @var integer
     */
    protected $_processedOrders = array();

    /**
     * Constructor
     *
     * @return Dhl_Intraship_Model_Autocreate
     */
    public function __construct()
    {
        $this->_config = Mage::getModel('intraship/config');
    }

    /**
     * Execute
     *
     * @param  ArrayObject                      $settings
     * @return Dhl_Intraship_Model_Autocreate   $this
     */
    public function execute(ArrayObject $settings = null)
    {
        foreach (Mage::helper('intraship/data')->getAutocreateOrders() as $order) {
            $countryId = $order->getShippingAddress()->getCountryId();
            // DHLIS-313: Do not handle orders to be sent outside the EU
            if (!$this->getConfig()->isInternationalShipping($countryId)) {
                $this->_handleOrder($order, $settings);
            }
        }

        return $this;
    }

    /**
     * Process
     *
     * @param  Mage_Sales_Model_Order                       $order
     * @param  ArrayObject|null                             $settings
     * @return Dhl_Intraship_Model_Autocreate               $this
     */
    public function process(Mage_Sales_Model_Order $order,
        ArrayObject $settings = null
    ) {
        try {
            // Validate order and throw exception if failed.
            $this->checkOrder($order);

            // Create shipment.
            $shipment  = $this->createShipment($order);
            // Get config values.
            $notify    = $this->getConfig()->isAutocreateNotification();
            $message   = $this->getConfig()->getAutocreateNotificationMessage(
                $order->getStoreId()
            );
            if (true === $notify):
                $shipment->addComment($message, $notify)->setEmailSent($notify);
            endif;

            // Notify customer
            $shipment->getOrder()->setCustomerNoteNotify($notify);
            // Save shipment.
            $this->saveShipment($shipment, $settings)->sendEmail(
                $notify, $message);

        } catch (Exception $e) {
            // Add comment to order if exception appears.
            $this->addCommentToOrder($order, $e->getMessage());
        }
        return $this;
    }

    /**
     * Create shipment.
     *
     * @param  Mage_Sales_Model_Order           $order
     * @return Mage_Sales_Model_Order_Shipment  $shipment
     */
    public function createShipment(Mage_Sales_Model_Order $order)
    {
        /*
         * for Magento 1.4.x.x
         */
        if (true === $this->getConfig()->isVersionRecommendedOrLarger()):
            /* @var $shipment Mage_Sales_Model_Order_Shipment */
            $shipment = Mage::getModel('sales/service_order', $order)
                ->prepareShipment($this->getQtys($order));
            $shipment->register();
        /*
         * for Magento 1.3.x.x
         */
        else:
            $convertor  = Mage::getModel('sales/convert_order');
            $shipment   = $convertor->toShipment($order);
            $savedQtys  = $this->getQtys($order);
            foreach ($order->getAllItems() as $orderItem):
                if ((!$orderItem->isDummy(true) && !$orderItem->getQtyToShip()) ||
                    $orderItem->getIsVirtual()):
                    continue;
                endif;
                $item = $convertor->itemToShipmentItem($orderItem);
                if (isset($savedQtys[$orderItem->getId()])):
                    if ($savedQtys[$orderItem->getId()] > 0):
                        $qty = $savedQtys[$orderItem->getId()];
                    else:
                        continue;
                    endif;
                else:
                    if ($orderItem->isDummy(true)):
                        $qty = 1;
                    else:
                        $qty = $orderItem->getQtyToShip();
                    endif;
                endif;
                $item->setQty($qty);
                $shipment->addItem($item);
            endforeach;
            $shipment->setOrder($order)->register();
        endif;
        return $shipment;
    }

    /**
     * Add comment to order
     *
     * @param  Mage_Sales_Model_Order   		$order
     * @param  string                   		$comment
     * @param  boolean  						$isShipmentSuccessfullyCreated
     *
     * @return void
     */
    public function addCommentToOrder(Mage_Sales_Model_Order $order, $comment, $isShipmentSuccessfullyCreated = false)
    {
        /*
         * for Magento 1.4.x.x
         */
        if (true === $this->getConfig()->isVersionRecommendedOrLarger()):
            $order
                ->addStatusHistoryComment($comment)
                ->setIsVisibleOnFront(false)
                ->setIsCustomerNotified(false)
                ->save();
        /*
         * for Magento 1.3.x.x
         */
        else:
        	//Get existing order status
        	$status = $order->getStatus();

        	//If shipment was created successfully
        	if (true === $isShipmentSuccessfullyCreated):
        		$status = Mage_Sales_Model_Order::STATE_PROCESSING;
        	endif;

        	//Is status is empty, take state
        	if ($status==""):
        		$status = $order->getState();
        	endif;

        	if (false === $isShipmentSuccessfullyCreated):
        		/*
        		 * reload the order to avoid that unwanted order data is saved | SEE DHLIS-181
        		 *  Order Items are set as shipped in CE 1.3 even if there was an exception
        		 */
				$order = Mage::getModel('sales/order')->load($order->getId());
        	endif;

            $order->addStatusToHistory($status, $comment)->save();
        endif;
    }

    /**
     * Save shipment with transaction.
     *
     * @throws Dhl_Intraship_Model_Autocreate_Exception
     * @param  Mage_Sales_Model_Order_Shipment  $shipment
     * @params ArrayObject|null                 $settings
     * @return Mage_Sales_Model_Order_Shipment  $shipment
     */
    public function saveShipment(Mage_Sales_Model_Order_Shipment $shipment,
        ArrayObject $settings = null)
    {
        /* @var $order Mage_Sales_Model_Order */
        $order = $shipment->getOrder();
        // Create default shipment.
        $order->setIsInProcess(true);
        $transaction = Mage::getModel('core/resource_transaction')
            ->addObject($shipment)->addObject($order)->save();

        // Create intraship shipment.
        $countryId = $shipment->getShippingAddress()->getCountryId();
        if (true === $this->getConfig()->isInternationalShipping($countryId)):
            /* DHLIS-313: do not permit shipping out of EU */
            return $shipment;
        endif;
        if (!$settings instanceof ArrayObject):
            $settings = $this->getConfig()->getAutocreateSettings($countryId);
        endif;
        // Set shipment to go green if customer has chosen
        // the go green option in checkout.
        if (true === (bool) $order->getIsGogreen()):
            $settings = clone $settings;    // To clone the object is important!
            $settings->offsetSet('profile',
                Dhl_Intraship_Model_Config::PROFILE_GO_GREEN);
        endif;
        $intraship = Mage::getModel('intraship/shipment')
            ->setShipmentId($shipment->getId())
            ->setOrderId($order->getEntityId())
            ->setSettings($settings->getArrayCopy())
            ->setPackages(array('package_0' => array('weight' =>
                $this->getDefaultWeight($shipment))))
            ->save();
        // Set order to processed storage.
        $this->setProcessedOrders($order);
        return $shipment;
    }

    /**
     * Get default weight.
     *
     * @param  Mage_Sales_Model_Order_Shipment  $shipment
     * @return float                            $weight
     */
    public function getDefaultWeight(Mage_Sales_Model_Order_Shipment $shipment)
    {
        $weight    = 0;
        if (true === $this->getConfig()->useProductWeightAsDefault()):
            /* @var $item Mage_Sales_Model_Order_Item */
            foreach ($shipment->getOrder()->getItemsCollection() as $item):
                if (false === Mage::helper('intraship')->isAllowedProductTypeForWeightCalculation($item->getProductType()))
                    continue;

                $weight += Mage::helper('intraship')->convertWeight(
                    (float) $item->getWeight() * (float) $item->getQtyOrdered()
                );
            endforeach;
        else:
            $weight = $this->getConfig()->getWeightDefault(
                $shipment->getShippingAddress()->getCountryId());
        endif;
        return (float) $weight;
    }

    /**
     * Check order
     *
     * @throws Dhl_Intraship_Model_Autocreate_Exception
     * @param  Mage_Sales_Model_Order                   $order
     * @return Dhl_Intraship_Model_Autocreate           $this
     */
    public function checkOrder(Mage_Sales_Model_Order $order)
    {
        // Check order existing.
        if (!$order->getId()):
            throw new Dhl_Intraship_Model_Autocreate_Exception(
                'The order no longer exists.');
        endif;
        // Check shipment is available to create separate from invoice.
        if ($order->getForcedDoShipmentWithInvoice()):
            throw new Dhl_Intraship_Model_Autocreate_Exception(
                'Cannot do shipment for the order separately from invoice.');
        endif;
        // Check shipment create availability.
        if (!$order->canShip()):
            throw new Dhl_Intraship_Model_Autocreate_Exception(
                'Cannot do shipment for the order.');
        endif;
        return $this;
    }

    /**
     * Get order quantities.
     *
     * @param  Mage_Sales_Model_Order   $order
     * @return array                    $qtys
     */
    public function getQtys(Mage_Sales_Model_Order $order)
    {
        $qtys = array();
        foreach ($order->getAllItems() as $item):
            $qtys[$item->getId()] = $item->getQtyToShip();
        endforeach;
        return $qtys;
    }

    /**
     * Get intraship config
     *
     * @return Dhl_Intraship_Model_Config
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Get processed orders.
     *
     * @return array
     */
    public function getProcessedOrders()
    {
        return $this->_processedOrders;
    }

    /**
     * Set processed orders.
     *
     * @param  Mage_Sales_Model_Order           $order
     * @return Dhl_Intraship_Model_Autocreate   $this
     */
    public function setProcessedOrders(Mage_Sales_Model_Order $order)
    {
        // Write message to order log.
        $this->addCommentToOrder($order,
            'DHL Intraship shipment successful created.', true);
        $this->_processedOrders[] = $order->getRealOrderId();
        return $this;
    }

    /**
     * Is cash on delivery.
     *
     * @param  Mage_Sales_Model_Order   $order
     * @return boolean
     */
    public function isCod(Mage_Sales_Model_Order $order)
    {
        // Get payment method as string.
        $method   = $order->getPayment()->getMethod();
        // Get payment mehtods for cash on delivery from config.
        $payments = $this->getConfig()->getPaymentMethodsForCod();
        // Return true if payment method is in config array.
        return (in_array($method, $payments->getArrayCopy()));
    }

    /**
     * Get order collection
     *
     * @return Mage_Sales_Model_Mysql4_Order_Collection $collection
     */
    protected function _getCollection()
    {
        $codes     = $this->getConfig()->getAutocreateStatusCodes()->getArrayCopy();
        $payments  = $this->getConfig()->getAutocreatePaymentMethods()->getArrayCopy();

        //Check if all configurations was done
        if (empty($codes)):
        	 throw new Dhl_Intraship_Model_Autocreate_Exception(
        	 	'Please set the order status for the autocreate mode in the config area.');
        endif;

        if (empty($payments)):
        	 throw new Dhl_Intraship_Model_Autocreate_Exception(
        	 	'Please set the payment methods for the autocreate mode in the config area.');
        endif;

        /*
         * Get collection for Magento 1.4.x.x
         */
        if (true === $this->getConfig()->isVersionRecommendedOrLarger()):
            // Get order collection.
            $collection = Mage::getModel('sales/order')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('main_table.status', $codes);
            $paymentWhere = sprintf("join_table.method = '%s'", implode(
                "' or join_table.method = '", $payments));
            $collection->getSelect()->join(array(
                'join_table' => Mage::getSingleton('core/resource')
                                  ->getTableName('sales_flat_order_payment')),
                'main_table.entity_id = join_table.entity_id',
                array('join_table.*'))->where($paymentWhere);
        /*
         * Get collection for Magento 1.3.x.x
         */
        else:
            $payment      = Mage::getResourceModel('sales/order_payment');
            $paymentWhere = array('entity_type_id' => $payment->getTypeId());
            $attributes = $payment->loadAllAttributes()->getAttributesByCode();
             foreach ($attributes as $attrCode=>$attr):
                if ($attr->getAttributeCode() == 'method'):
                    $attId = $attr->getAttributeId();
                endif;
             endforeach;
             $paymentMethodWhere = sprintf('({{table}}.attribute_id = %s)', $attId);
             $collection = Mage::getModel('sales/order')->getCollection();
             $collection
                /* use "status" instead of "state" because
                 * "state" is "new" instead of "pending" for new orders
                 */
                ->addAttributeToFilter('status', array('in' => $codes))
                ->joinTable(
                    array(
                        'soe' => Mage::getSingleton('core/resource')
                                  ->getTableName('sales_order_entity')
                    ),
                    'parent_id=entity_id',
                    array('quote_payment_id_for_join' => 'entity_id' ),
                    $paymentWhere,
                    'left'
                )
                ->joinTable(
                    array(
                        'soev' => Mage::getSingleton('core/resource')
                                  ->getTableName('sales_order_entity_varchar')
                    ),
                    'entity_id=quote_payment_id_for_join',
                    array( 'method' => 'value' ),
                    $paymentMethodWhere,
                    'left'
                );
                $paymentWhere = sprintf("soev.value = '%s'", implode(
                    "' OR soev.value = '", $payments));
                $sql = $collection->getSelect()->where($paymentWhere);
        endif;
        return $collection;
    }

    /**
     * Handle order
     *
     * @param  Mage_Sales_Model_Order           $order
     * @param  ArrayObject                      $settings
     * @return Dhl_Intraship_Model_Autocreate   $this
     */
    protected function _handleOrder(Mage_Sales_Model_Order $order,
        ArrayObject $settings = null) {
        try {
            // Get intraship shipment.
            $shipment = Mage::getModel('intraship/shipment')->load(
                $order->getEntityId(), 'order_id');
            if (false === $shipment->isEmpty()):
                // Checks if order has shipments and can execute again.
                if (true === $shipment->canExecute()):
                    // Set intraship shipment status to retry new.
                    $status = Dhl_Intraship_Model_Shipment::STATUS_NEW_RETRY;
                    $shipment->setStatus($status)->save();
                endif;
            else:
                /* @var $order Mage_Sales_Model_Order */
                $this->process($order, $settings);
            endif;
         } catch (Exception $e) {
            // Add comment to order if exception appears.
            $this->addCommentToOrder($order, $e->getMessage());
         }
         return $this;
    }
}

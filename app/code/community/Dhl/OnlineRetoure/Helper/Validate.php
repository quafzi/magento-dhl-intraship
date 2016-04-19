<?php
/**
 * Dhl_OnlineRetoure_Helper_Validate
 *
 * @package   Dhl_Account
 * @author    André Herrn <andre.herrn@netresearch.de>
 * @copyright Copyright (c) 2012 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_OnlineRetoure_Helper_Validate extends Dhl_OnlineRetoure_Helper_Data
{
    const REQUEST_TYPE_INTERNAL = "internal";
    const REQUEST_TYPE_HASH     = "hash";

    public static function throwException($message, $messageStorage = null)
    {
        if ($messageStorage && ($storage = Mage::getSingleton($messageStorage))) {
            $storage->addError($message);
        }
        throw new Dhl_OnlineRetoure_Model_Validate_Exception($message);
    }

    /**
     * Check if the request is coming from a logged in customer
     * or by hash
     *
     * @return string
     */
    public function getRequestType()
    {
        if (false === is_null(Mage::app()->getRequest()->getParam("hash"))) {
            return self::REQUEST_TYPE_HASH;
        } elseif (false === is_null(Mage::app()->getRequest()->getParam("order_id"))) {
            return self::REQUEST_TYPE_INTERNAL;
        } else {
            return null;
        }
    }

    /**
     * Check if it is a hash request
     *
     * @return boolean
     */
    public function isHashRequest()
    {
        return (self::REQUEST_TYPE_HASH == $this->getRequestType());
    }

    /**
     * Check if it is a hash request
     *
     * @return boolean
     */
    public function isInternalRequest()
    {
        return (self::REQUEST_TYPE_INTERNAL == $this->getRequestType());
    }

    /**
     * Calculate query params used for accessing confirmation and pdf-rendering pages.
     *
     * The hash MUST be returned as _query param, as it includes slashes itself!
     *
     * @param string $orderId
     * @param string $hash
     * @return array
     */
    public function getUrlParams($orderId = null, $hash = null)
    {
        $query = array();
        if ($orderId) {
            $query['order_id'] = $orderId;
        }
        if ($hash) {
            $query['hash'] = $hash;
        }

        if (empty($query)) {
            return $query;
        }

        return array(
            '_nosid'  => true,
            '_query'  => $query
        );
    }

    /**
     * Log request result
     *
     * @param boolean $isSuccess
     */
    public function logRequestResult($isSuccess)
    {
        $message = sprintf(
            "%s - Label request type '%s' with parameters '%s'",
            $isSuccess ? strtoupper('SUCCESS') : strtoupper('FAILURE'),
            $this->getRequestType(),
            Zend_Json::encode(Mage::app()->getRequest()->getParams())
        );
        $this->log($message, $isSuccess ? Zend_Log::INFO : Zend_Log::DEBUG);
    }

    public function logSuccess()
    {
        $this->logRequestResult(true);
    }

    public function logFailure()
    {
        $this->logRequestResult(false);
    }

    /**
     * Check if module is enabled for frontend display
     * @return boolean
     */
    public function isModuleFrontendEnabled()
    {
        return ($this->getConfig()->isEnabled()
             && $this->isModuleOutputEnabled('Dhl_OnlineRetoure'));
    }

    /**
     * Check if order is valid for frontend display
     * @see Mage_Sales_Controller_Abstract::_canViewOrder
     * @return boolean
     */
    public function isOrderFrontendEnabled(Mage_Sales_Model_Order $order)
    {
        $availableStates = Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates();
        return in_array($order->getState(), $availableStates, $strict = true);
    }

    /**
     * Check if the given ISO 3166-2 code is amongst the allowed country codes.
     *
     * @param string $iso2code
     * @return boolean
     */
    public function isAllowedCountryCode($iso2code)
    {
        return in_array($iso2code, $this->getConfig()->getAllowedCountryCodes());
    }

    /**
     * Check if an order exists in the shop.
     *
     * @param Mage_Sales_Model_Order $order
     * @return boolean
     */
    public function isOrderExisting(Mage_Sales_Model_Order $order)
    {
        return (($order instanceof Mage_Sales_Model_Order) && !is_null($order->getId()));
    }

    /**
     * Check if an order exists in the shop by given order ID.
     *
     * @param int $orderId
     * @return boolean
     */
    public function isOrderIdExisting($orderId)
    {
        if (!is_numeric($orderId)) {
            return false;
        }

        $order = Mage::getModel("sales/order")->load((int) $orderId);
        return $this->isOrderExisting($order);
    }

    /**
     * Check if order has shipments
     *
     * @param Mage_Sales_Model_Order $order
     * @return boolean
     */
    public function isOrderHasShipments(Mage_Sales_Model_Order $order)
    {
        return (bool)$order->hasShipments();
    }

    /**
     * Check if a delivery name was configured for current delivery country.
     *
     * @param Mage_Sales_Model_Order $order
     * @return boolean
     */
    public function isDeliveryNameAvailable(Mage_Sales_Model_Order $order)
    {
        $deliveryName = $this->getConfig()->getDeliveryNameByCountry(
            $order->getShippingAddress()->getCountryId()
        );
        return ($deliveryName !== '');
    }

    /**
     * Check if an order belongs to a customer
     *
     * @param Mage_Sales_Model_Order         $order
     * @param Mage_Customer_Model_Customer   $customer
     * @return boolean
     */
    public function isOrderBelongsToCustomer(Mage_Sales_Model_Order $order,
            Mage_Customer_Model_Customer $customer)
    {
        return ($order->getCustomerId() === $customer->getId());
    }

    /**
     * Check, if we can show the retoure link to the customer in the My Account View
     * We don't throw Exceptions in here because this function is maybe used in layout.xml
     * and we cannot catch Exceptions there
     *
     * @return boolean
     */
    public function canShowRetoureLink(Mage_Sales_Model_Order $order)
    {
        if (!$this->isModuleFrontendEnabled()) {
            return false;
        }

        // We show the reture link only for internal requests
        if (!$this->isInternalRequest()) {
            return false;
        }

        $canShow = false;
        try {
            $canShow = $this->isCustomerValid($order) && $this->isOrderValid($order);
        } catch (Exception $e) {}

        return $canShow;
    }

    /**
     * Build a hash over some unmodifiable(!) order properties.
     *
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
    public function createHashForOrder(Mage_Sales_Model_Order $order)
    {
        $orderHash  = $order->getId();
        $orderHash .= $order->getIncrementId();
        $orderHash .= $order->getQuoteId();
        $orderHash .= $order->getCustomerEmail();
        $orderHash .= $order->getCustomerFirstname();
        $orderHash .= $order->getCustomerLastname();
        $orderHash .= $order->getShippingMethod();
        $orderHash .= $order->getStoreName();
        $orderHash .= $order->getGrandTotal();

        return hash("sha512", $orderHash);
    }

    /**
     * checks if the passed hash is valid for the passed order
     *
     * @param string $hash
     * @param Mage_Sales_Model_Order $order
     * @throws Dhl_OnlineRetoure_Model_Validate_Exception
     * @return boolean
     */
    public function isHashValid($hash, Mage_Sales_Model_Order $order)
    {
        $errorMessage = '';

        //Calculate internal hash by given order_id
        $caluclatedHash = $this->createHashForOrder($order);

        //Check if hash is valid
        if ($hash !== $caluclatedHash) {
            $this->log(sprintf("Hash mismatch:\n  %s (calculated)\n  %s (given)", $caluclatedHash, $hash));
            $errorMessage = 'You are not allowed to create a return for the current order.';
            self::throwException(Mage::helper("dhlonlineretoure/data")->__($errorMessage));
        } else {
            $this->log(sprintf("Hash match: %s", $hash), Zend_Log::INFO);
        }

        return true;
    }

    /**
     * Check if the currently logged in customer can view the order.
     *
     * @param Mage_Sales_Model_Order $order
     * @throws Mage_Core_Exception
     * @return boolean
     */
    public function isCustomerValid(Mage_Sales_Model_Order $order)
    {
        $errorMessage = '';

        if (!$this->isCustomerLoggedIn()) {
            $errorMessage = 'Please log in to access DHL Online Return.';
        } elseif (!$this->isOrderBelongsToCustomer($order, $this->getLoggedInCustomer())) {
            $errorMessage = 'You are not allowed to create a return for the current order.';
        }

        if ($errorMessage) {
            self::throwException(Mage::helper("dhlonlineretoure/data")->__($errorMessage));
        }

        return true;
    }


    /**
     * Check if a return can be created for the order.
     *
     * @param Mage_Sales_Model_Order $order
     * @throws Dhl_OnlineRetoure_Model_Validate_Exception
     * @return boolean
     */
    public function isOrderValid(Mage_Sales_Model_Order $order)
    {
        $errorMessage = '';
        if (!$this->isOrderExisting($order)) {
            $errorMessage = 'The requested order does not exist.';
        } elseif (!$this->isOrderHasShipments($order)) {
            $errorMessage = 'Your shipment was not sent yet. Because of this no return label can be created currently.';
        } elseif (!$this->isDeliveryNameAvailable($order)) {
            $errorMessage = 'DHL Online Return is not available for your country.';
        } elseif (!Mage::getModel("dhlonlineretoure/config")->isAllowedShippingMethod($order->getShippingMethod())) {
            $errorMessage = 'DHL Online Return is not available for your shipping method.';
        }

        if ($errorMessage) {
            self::throwException(Mage::helper("dhlonlineretoure/data")->__($errorMessage));
        }

        return true;
    }

    /**
     * Check if a return can be created for the order.
     *
     * @param int $orderId
     * @throws Dhl_OnlineRetoure_Model_Validate_Exception
     * @return boolean
     */
    public function isOrderIdValid($orderId)
    {
        if (true === is_null($orderId) || 0 === $orderId) {
            self::throwException(
                Mage::helper("dhlonlineretoure/data")->__("No order id was given.")
            );
        }
        return true;
    }
}
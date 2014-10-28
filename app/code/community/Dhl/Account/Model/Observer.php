<?php
/**
 * Dhl_Account_Model_Observer
 *
 * @category  Models
 * @package   Dhl_Account
 * @author    Michael Lühr <michael.luehr@netresearch.de>
 * @author    Thomas Birke <thomas.birke@netresearch.de>
 * @copyright Copyright (c) 2012 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Account_Model_Observer
{
    /**
     * Append parcel announcement to html output.
     *
     * @param  $observer
     * @return void
     */
    public function appendParcelAnnouncementToBilling($observer)
    {
        if ($observer->getBlock() instanceof Mage_Checkout_Block_Onepage_Billing
            && false == $observer->getBlock() instanceof Mage_Paypal_Block_Express_Review_Billing
        ) {
            $transport = $observer->getTransport();
            $block     = $observer->getBlock();
            $layout    = $block->getLayout();
            $html      = $transport->getHtml();
            $parcelAnnouncementHtml = $layout->createBlock(
                'dhlaccount/checkout_onepage_parcelannouncement', 'onepage_parcelannouncement')
                ->setTemplate('account/checkout/onepage/parcelannouncement.phtml')
                ->renderView();
            $html = $html . $parcelAnnouncementHtml;
            $transport->setHtml($html);
        }
    }

    /**
     * Append packstation to html output.
     *
     * @param  $observer
     * @return void
     */
    public function appendPackingstationToShipping($observer)
    {
        $block     = $observer->getBlock();
        if ($block instanceof Mage_Checkout_Block_Onepage_Shipping
            && false == $block instanceof Mage_Paypal_Block_Express_Review_Shipping
            && Mage::getModel('intraship/config')->isEnabled()
            && Mage::getModel('dhlaccount/config')->isPackstationEnabled($block->getQuote()->getStoreId())
        ) {
            $transport = $observer->getTransport();
            $layout    = $block->getLayout();
            $html      = $transport->getHtml();
            $parcelAnnouncementHtml = $layout->createBlock(
                'dhlaccount/checkout_onepage_packingstation', 'onepage_packingstation')
                ->setTemplate('account/checkout/onepage/packingstation.phtml')
                ->renderView();
            $html = $html . $parcelAnnouncementHtml;
            $transport->setHtml($html);
        }
    }

	public function appendParcelAnnouncementValidationToShipping($observer)
	{
		if ($observer->getBlock() instanceof Mage_Checkout_Block_Onepage_Shipping
            && Mage::getModel('intraship/config')->isEnabled()
            && Mage::getStoreConfig('intraship/dhlaccount/active')
        ) {
            $transport = $observer->getTransport();
            $block     = $observer->getBlock();
            $layout    = $block->getLayout();
            $html      = $transport->getHtml();
            $parcelAnnouncementHtml = $layout->createBlock(
                'dhlaccount/checkout_onepage_parcelannouncement', 'onepage_packingstation')
                ->setTemplate('account/checkout/onepage/validate_parcel_announcement.phtml')
                ->renderView();
            $html = $html . $parcelAnnouncementHtml;
            $transport->setHtml($html);
        }
	}


    /**
     * Saves the dhl account number to
     *
     * @param $observer
     */
    public function saveDhlAccount($observer)
    {
        $data = Mage::app()->getRequest()->getPost();
        if (array_key_exists('billing', $data) &&
            array_key_exists('preferred_date', $data['billing']) &&
            array_key_exists('dhlaccount', $data['billing']) &&
            0 < strlen(trim($data['billing']['dhlaccount']))
        ) {
            Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress()
                ->setData('dhlaccount', $data['billing']['dhlaccount'])
                ->save();

            Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()
                ->setData('dhlaccount', Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress()->getDhlaccount())
                ->save();
        }
    }

    /**
     * Save package notification flag to quote, i.e. whether email address should
     * get transferred to Intraship web service for package notification or not.
     *
     * @param Varien_Event_Observer $observer
     * @return Dhl_Account_Model_Observer
     */
    public function savePackageNotificationFlag(Varien_Event_Observer $observer)
    {
        $data = Mage::app()->getRequest()->getPost();
        $address = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress();

        if ((array_key_exists('billing', $data)
            && array_key_exists('package_notification', $data['billing']))
        ) {
            $address->setPackageNotification(true);
        } else {
            $address->setPackageNotification(false);
        }

        $address->save();
        return $this;
    }

    /**
     * Saves the packstation information
     *
     * @param $observer
     */
    public function savePackstationInformation($observer)
    {
        $data = Mage::app()->getRequest()->getPost();
        if (array_key_exists('shipping', $data) &&
            array_key_exists('ship_to_packstation', $data['shipping']) &&
            array_key_exists('street', $data['shipping']) &&
            preg_match('/^(packstation\s){0,1}\d{3,3}$/i', trim(current($data['shipping']['street']))) &&
            0 < strlen(trim($data['shipping']['dhlaccount']))
        ) {
            Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()
                ->setData('dhlaccount', $data['shipping']['dhlaccount'])
                ->setData('street', 'Packstation ' .  current($data['shipping']['street']))
                ->setData('ship_to_packstation', Dhl_Account_Model_Config::SHIP_TO_PACKSTATION)
                ->save();
        }
    }

    /**
     * resets the parcel announcement
     * @param type $observer
     */
    public function resetParcelAnnouncement($observer)
    {
        $data = Mage::app()->getRequest()->getPost();
        if (array_key_exists('shipping', $data) && array_key_exists('resetParcelAnnouncement', $data['shipping'])) {
            Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress()
            ->setData('dhlaccount', null)
            ->save();
        }
    }


    /**
     * add DHL account number to Intraship request
     *
     * @param Varien_Object $event
     * @return void
     */
    public function dhlIntrashipSendShipmentBefore($event)
    {
        $request = $event->getRequest();


        $parcel = $request->offsetGet('shipment');
        $dhlaccount = $parcel->getShipment()->getBillingAddress()->getDhlaccount();
        if (!is_null($parcel->getShipment()->getShippingAddress()->getDhlaccount()))  {
            $dhlaccount = $parcel->getShipment()->getShippingAddress()->getDhlaccount();
        }
        if ($parcel->hasCustomizedAddress()) {
            $dhlaccount = $parcel->getCustomizedAddress()->offsetGet('dhlaccount');
        }
        if (0 < strlen($dhlaccount)) {
            $params = $request->offsetGet('params');
            $data = $params->offsetGet('ShipmentOrder');
            if (array_key_exists('Company', $data['Shipment']['Receiver']['Company'])
                && (false == array_key_exists('name2', $data['Shipment']['Receiver']['Company']['Company'])
                    || 0 == strlen(trim($data['Shipment']['Receiver']['Company']['Company']['name2']))
                )
            ) {
                $data['Shipment']['Receiver']['Company']['Company']['name2'] = $dhlaccount;
            } elseif (false == array_key_exists('Communication', $data['Shipment']['Receiver'])
                || false == array_key_exists('contactPerson', $data['Shipment']['Receiver']['Communication'])
                || 0 == strlen(trim($data['Shipment']['Receiver']['Communication']['contactPerson']))
            ) {
                if (false == array_key_exists('Communication', $data['Shipment']['Receiver'])) {
                    $data['Shipment']['Receiver']['Communication'] = array();
                }
                $data['Shipment']['Receiver']['Communication']['contactPerson'] = $dhlaccount;
            } elseif (false == array_key_exists('careOfName', $data['Shipment']['Receiver']['Address'])
                || 0 == strlen(trim($data['Shipment']['Receiver']['Address']['careOfName']))
            ) {
                $data['Shipment']['Receiver']['Address']['careOfName'] = $dhlaccount;
            }
            $request->offsetGet('params')->offsetSet('ShipmentOrder', $data);
        }
    }

    public function appendReceiverEmail(Varien_Event_Observer $observer)
    {
        /* @var $request Dhl_Intraship_Model_Soap_Client_Shipment_Create */
        $request = $observer->getEvent()->getRequest();
        $parcel = $request->offsetGet('shipment');


        $packageNotification = (bool) $parcel
            ->getShipment()
            ->getBillingAddress()
            ->getPackageNotification();

        if ($packageNotification) {
            $email = trim($request->offsetGet('receiver')->getEmail());
            $shipmentOrder = $request->offsetGet('params')->offsetGet('ShipmentOrder');
            $shipmentOrder['Shipment']['Receiver']['Communication']['email'] = $email;
            $request->offsetGet('params')->offsetSet('ShipmentOrder', $shipmentOrder);
        }

        return $this;
    }

    public function dhlIntrashipShipmentLoadAfter($event)
    {
        $parcel = $event->getObject();
        if ($parcel instanceof Varien_Object && $parcel->getId() && false == $parcel->hasCustomizedAddress()) {
            $address = $parcel->getCustomerAddress();
            if ($parcel->getShipment() instanceof Varien_Object &&
                $parcel->getShipment()->getBillingAddress() instanceof Varien_Object &&
                !is_null($parcel->getShipment()->getBillingAddress()->getDhlaccount())) {
                    $address['dhlaccount'] = $parcel->getShipment()->getBillingAddress()->getDhlaccount();
            }
            if ($parcel->getShipment() instanceof Varien_Object &&
            $parcel->getShipment()->getShippingAddress() instanceof Varien_Object &&
            !is_null($parcel->getShipment()->getShippingAddress()->getDhlaccount())) {
                $address['id_number'] = $parcel->getShipment()->getShippingAddress()->getDhlaccount();
                $address['station_id'] = $parcel->getShipment()->getShippingAddress()->getStreetFull();
            }
            $parcel->setCustomerAddress($address);
        }
    }
}

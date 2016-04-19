<?php
/**
 * Dhl_Intraship_Model_Shipment
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @author    Stephan Hoyer <stephan.hoyer@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_Shipment extends Mage_Core_Model_Abstract
{
    /**
     * Statuscode of shipment in queue. The code means marked as new.
     *
     * @var integer
     */
    const STATUS_NEW_QUEUED      =  1;

    /**
     * Statuscode of retrying creation, somthing was wrong with the creation
     * on intraship service. For example the service response code is:
     *
     *  CODE    DESCRIPTION
     *  ---- | ---------------------------------
     *  500     Service temporary not available
     *  1000    General error
     *  1001    Login failed
     *  2110    Invalid sender address
     *  2120    Invalid receiver address
     *  2130    Invalid account
     *
     * @var integer
     */
    const STATUS_NEW_RETRY       =  5;

    /**
     * Statuscode for failed creation on intraship service,
     * maybe this shipment already exists or has some errors.
     *
     * @var integer
     */
    const STATUS_NEW_FAILED      = -2;

    /**
     * Statuscode for processed shipment. The shipment was successful created.
     *
     * @var integer
     */
    const STATUS_PROCESSED       =  2;

    /**
     * Statuscode for a cancel shipment. The code means marked canceled.
     *
     * @var integer
     */
    const STATUS_CANCEL_QUEUED   =  3;

    /**
     * Statuscode for canceled shipment. The shipment was successful canceled.
     *
     * @var integer
     */
    const STATUS_CANCELED        =  4;

    /**
     * Statuscode for failed cancel shipment,
     * maybe this shipment was already deleted.
     *
     * @var integer
     */
    const STATUS_CANCELED_FAILED = -4;

    /**
     * Statuscode of retrying cancelation, somthing was wrong with the canceling
     * on intraship service. For example the service response code is:
     *
     *  CODE    DESCRIPTION
     *  ---- | ---------------------------------
     *  500     Service temporary not available
     *  1000    General error
     *  1001    Login failed
     *  2110    Invalid sender address
     *  2120    Invalid receiver address
     *  2130    Invalid account
     *
     * @var integer
     */
    const STATUS_CANCEL_RETRY    =  6;

    /**
     * Statuscode for closed shipment. The shipment is done.
     *
     * @var integer
     */
    const STATUS_CLOSED          =  10;

    /**
     * Statuscode for shipments which were currenctly transmitted
     *
     * @var integer
     */
    const STATUS_IN_TRANSMISSION  =  20;

    /**
     * The insurance option is not possible on a total amount greater than 2.500.
     *
     * @var float
     */
    const INSURANCE_A            = 2500;

    /**
     * The shipment does exceed the maximum allowed package weight of 31.5 kg.
     *
     * @var float
     */
    const MAX_WEIGHT_KG          = 31.5;

    /**
     * The shipment does not below the minimun allowed package weight of 0.1 kg.
     *
     * @var float
     */
    const MIN_WEIGHT_KG          = 0.1;

    /**
     * If testmode is active, this value is used for the mode - column in the shipment table
     *
     * @var string
     */
    const SHIPMENT_MODE_TEST          = 'test';

    /**
     * If testmode is disabled (Live mode), this value is used for the mode - column in the shipment table
     *
     * @var string
     */
    const SHIPMENT_MODE_PROD          = 'prod';

    protected $_eventPrefix = 'dhl_intraship_shipment';

    /**
     * The origin magento order shipment model.
     *
     * @var Mage_Sales_Model_Order_Shipment
     */
    protected $_shipment;

    /**
     * A collection of intraship pdf documents.
     *
     * @var Dhl_Intraship_Model_Mysql4_Shipment_Document_Collection
     */
    protected $_documents;

    /**
     * A JSON encoded array with aditional settings
     *
     * Example:
     *  {"profile":"go-green","insurance":"1","personally":"0","bulkfreight":"0"}
     *
     * OPTION           VALUE
     * ------------- | -------------
     * profile          standard|go-green|express|go-green-express
     * insurance        0|1
     * personally       0|1
     * bulkfreight      0|1
     *
     * @var ArrayObject
     */
    protected $_settings;

    /**
     * A JSON encoded array witch contains the number of packages with weight.
     *
     * Example:
     *  {"package_0":{"weight":"3.5"},"package_1":{"weight":"1.8"}}
     *
     * @var ArrayObject
     */
    protected $_packages;

    /**
     * Flag for customized address
     *
     * @var boolean
     */
    protected $_hasCustomizedAddress = false;

    /**
     * Shipment Price Calculator Object
     *
     * @var Dhl_Intraship_Model_Shipment_Price
     */
    protected $_shipmentPriceCalculator;


    /**
     * Constructor
     *
     * @see    lib/Varien/Varien_Object#_construct()
     * @return Dhl_Intraship_Model_Shipment
     */
    protected function _construct()
    {
        $this->_init('intraship/shipment');
    }

    /**
     * Sets inital status and dhl profile if unseted.
     *
     * @return $this
     */
    protected function _beforeSave()
    {
        if (is_null($this->getStatus())):
            $this->setStatus(self::STATUS_NEW_QUEUED);
        endif;
        // Prevent json encoding twice.
        if (is_array($this->getSettings())):
            $this->setSettings(Zend_Json::encode($this->getSettings()));
        endif;
        // Set customer to customized customer address id isset.
        if (count($this->getCustomer())):
            $this->setCustomerAddress(Zend_Json::encode($this->getCustomer()));
        endif;
        // Prevent json encoding twice.
        if (is_array($this->getCustomerAddress())):
            $customerAddress = $this->getCustomerAddress();
            /*
             * Workarround, don't save customer address if "street_number" is not set.
             * "street_number" is only set if the address was really customized in the address edit form
             */
            if (array_key_exists('street_number',$customerAddress)):
                $this->setCustomerAddress(Zend_Json::encode(
                    $this->getCustomerAddress()));
            else:
                $this->setCustomerAddress(null);
            endif;
        endif;
        // Prevent json encoding twice.
        if (is_array($this->getPackages())):
            $this->setPackages(Zend_Json::encode($this->getPackages()));
        endif;
        // Set Mode Test or Live
        if (Mage::getModel('intraship/config')->isTestmode()):
            $this->setMode(self::SHIPMENT_MODE_TEST);
        else:
        	$this->setMode(self::SHIPMENT_MODE_PROD);
        endif;
        return parent::_beforeSave();
    }

    /**
     * Load shipment related shipping address
     *
     * @return array
     */
    protected function _afterLoad()
    {
        if (!is_null($this->getCustomerAddress())):
            $this->_hasCustomizedAddress = true;
            $this->setCustomerAddress(Zend_Json::decode(
                $this->getCustomerAddress()));
        elseif ($this->getShipment()->getId()):
            $this->setCustomerAddress(
                $this->getShipment()->getShippingAddress()->getData());
        endif;
        return parent::_afterLoad();
    }

    /**
     * Returns whether current shipmnent has an edited address or not
     *
     * @return boolean
     */
    public function hasCustomizedAddress()
    {
        return (
            (
                true === $this->_hasCustomizedAddress &&
                true === is_array($this->getCustomerAddress())
            )
            ||
            (
                true === is_string($this->getCustomerAddress()) &&
                true === is_array(Zend_Json::decode($this->getCustomerAddress()))
            )
        );
    }

    /**
     * Get customized address.
     *
     * @return ArrayObject $address
     */
    public function getCustomizedAddress()
    {
        $address = null;
        if (true === $this->hasCustomizedAddress()):
            $address = new Dhl_Intraship_Model_Address(Zend_Json::decode(
                $this->getCustomerAddress()));
        endif;
        return $address;
    }

    /**
     * Returns HTML-localized foramted shipping address of this shipment.
     *
     * @return string|null
     */
    public function getFormatedAddress()
    {
        if (!($formatType = Mage::getSingleton('customer/address_config')->getFormatByCode('html'))
            || !$formatType->getRenderer()
        ):
            return null;
        endif;
        $customerAddress = $this->getCustomerAddress();
        if (false === array_key_exists('street_name', $customerAddress)) {
            $splittedStreet = Mage::helper('intraship')->splitStreet($customerAddress['street']);
            $customerAddress['street_name']   = $splittedStreet['street_name'];
            $customerAddress['street_number'] = $splittedStreet['street_number'];
            $customerAddress['care_of']       = $splittedStreet['care_of'];
        }
        $customerAddress['street'] = trim(sprintf(
            "%s %s\n%s\n%s\n%s",
            $customerAddress['street_name'],
            $customerAddress['street_number'],
            $customerAddress['care_of'],
            isset($customerAddress['id_number']) ? $customerAddress['id_number'] : '',
            isset($customerAddress['station_id']) ? $customerAddress['station_id'] : ''
        ));
        $address = Mage::getModel('customer/address')->setData($customerAddress);
        return $formatType->getRenderer()->render($address);
    }

    /**
     * Get document collection.
     *
     * @return Dhl_Intraship_Model_Mysql4_Shipment_Document_Collection $collection
     */
    public function getDocuments()
    {
        if (!isset($this->_documents)):
            $this->_documents = Mage::getModel('intraship/shipment_document')
                ->getCollection()
                ->addFieldToFilter('status', Dhl_Intraship_Model_Shipment_Document::STATUS_DOWNLOADED)
                ->addFieldToFilter('shipment_id', $this->getShipmentId());
        endif;
        return $this->_documents;
    }

    /**
     * Returns whether shipoment has documents
     *
     * @return boolean
     */
    public function hasDocuments()
    {
        return $this->getDocuments()->count() > 0;
    }

    /**
     * Remove Documents, soft-delete via status (-1)
     *
     * @return Dhl_Intraship_Model_Shipment $this
     */
    public function removeDocuments()
    {
        if ($this->hasDocuments()):
            $status = Dhl_Intraship_Model_Shipment_Document::STATUS_DELETED;
            foreach ($this->getDocuments() as $document):
                $document->setStatus($status)->save();
            endforeach;
        endif;
        return $this;
    }

    /**
     * Returns url to download merged PDF
     *
     * @return string
     */
    public function getDocumentsUrl()
    {
        return Mage::getUrl('adminhtml/shipment/pdf', array(
            '_current' => false,
            'id' => $this->getShipmentId()));
    }

    /**
     * Return settings as object
     *
     * @return ArrayObject
     */
    protected function _getSettings()
    {
        if (!$this->_settings instanceof ArrayObject):
            $settings = Zend_Json::decode($this->getSettings());
            if (!is_array($settings)):
                $settings = array();
            endif;
            $this->_settings = new ArrayObject($settings);
        endif;
        return $this->_settings;
    }

    /**
     * Return packages as object
     *
     * @return ArrayObject
     */
    protected function _getPackages()
    {
        if (!$this->_packages instanceof ArrayObject):
            $packages = Zend_Json::decode($this->getPackages());
            if (!is_array($packages)):
                $packages = array();
            endif;
            $this->_packages = new ArrayObject($packages);
        endif;
        return $this->_packages;
    }

    /**
     * Retrives original magento shipment model for current shipment.
     *
     * @return Mage_Sales_Model_Order_Shipment
     */
    public function getShipment()
    {
        if (!isset($this->_shipment)):
            $this->_shipment = Mage::getModel('sales/order_shipment')
                ->load($this->getShipmentId());
        endif;
        return $this->_shipment;
    }

    /**
     * Add comment.
     *
     * @param  string                       $comment
     * @param  string                       $code     ( >= 0)
     * @param  string                       $action   (create|cancel|pdf|common)
     * @return Dhl_Intraship_Model_Shipment $this
     */
    public function addComment($comment, $code, $action)
    {
        // Create comment with action, code and message (separated by "::")
        $comment = sprintf('DHL Intraship::%s::%s::%s',
            $action, $code, trim(strip_tags($comment)));
        // Write comment to shipment.
        $this->getShipment()->addComment($comment);
        return $this;
    }

    /**
     * Save shipment comments.
     *
     * @return Dhl_Intraship_Model_Shipment $this
     */
    public function saveComments()
    {
        $this->getShipment()->getCommentsCollection()->save();
        return $this;
    }

    /**
     * Remove all tracks from shipment
     *
     * @return Dhl_Intraship_Model_Shipment $this
     */
    public function removeTracks()
    {
        foreach ($this->getShipment()->getAllTracks() as $track):
            if ((string) $track->getCarrierCode() == 'intraship'):
                $track->delete();
            endif;
        endforeach;
        return $this;
    }

    /**
     * Add track number to shipment
     *
     * @return Dhl_Intraship_Model_Shipment $this
     */
    public function saveTrack()
    {
        /* $track Mage_Sales_Model_Order_Shipment_Track */
        $track = Mage::getModel('sales/order_shipment_track');
        $track->setCarrierCode('intraship')
              ->setTitle('DHL Intraship')
              ->setNumber($this->getShipmentNumber());
        // Add track to shipment and save it.
        $this->getShipment()
             ->addTrack($track)
             ->getTracksCollection()
             ->save();
        return $this;
    }

    /**
     * Get wights for multipack delivery in KG.
     *
     * @return array
     */
    public function getWeightsInKG()
    {
        $weights = null;
        if ($this->_getPackages()->count() >= 1):
            foreach ($this->_getPackages() as $package):
                $weights[] = $package['weight'];
            endforeach;
        endif;
        return $weights;
    }

    /**
     * Get single weight in KG if is not multipack
     *
     * @return array
     */
    public function getWeightInKG()
    {
        $weights = $this->getWeightsInKG();
        return $weights[0];
    }

    /**
     * Is shipment a multipack delivery?
     * Return TRUE if weights array bigger than one, otherwise return FALSE.
     *
     * @return boolean
     */
    public function isMultipack()
    {
        return (sizeof($this->getWeightsInKG()) > 1);
    }

    /**
     * Is cash on delivery (Nachnahme).
     *
     * @param  string|null  $method
     * @return boolean
     */
    public function isCOD($method = null)
    {
        // Set current intraship paymnet method if given method is null.
        if (null === $method):
            $method = $this->getShipment()
                ->getOrder()
                ->getPayment()
                ->getMethod();
        endif;
        // Return true if payment method is in config array.
        return (in_array($method, Mage::getModel('intraship/config')
            ->getPaymentMethodsForCod()->getArrayCopy()));
    }

    /**
     * Is bulkfreight?
     *
     * @return boolean
     */
    public function isBulkfreight()
    {
        $bulkfreight = null;
        if ($this->_getSettings()->offsetExists('bulkfreight')):
            $bulkfreight = $this->_getSettings()->offsetGet('bulkfreight');
        endif;
        return (1 === (int) $bulkfreight && true === $this->canBulkfreight());
    }

    /**
     * Can bulkfreight?
     * If personally and multipack is false return true, otherwise return false.
     *
     * @return boolean
     */
    public function canBulkfreight()
    {
        return true;
        /*
        return (false === $this->isPersonally() &&
                false === $this->isMultipack());
        */
    }

    /**
     * Get settings
     *
     * @return ArrayObject
     */
    public function getSettingsAsObject()
    {
        return $this->_getSettings();
    }

    /**
     * Get profile
     *
     * @return string   $profile
     */
    public function getProfile()
    {
        $profile = null;
        if ($this->_getSettings()->offsetExists('profile')):
            $profile = $this->_getSettings()->offsetGet('profile');
        endif;
        if (null === $profile):
            $profile = Mage::getModel('intraship/config')->getProfileDefault();
        endif;
        return $profile;
    }

    /**
     * Is personally (Eigenhändig)
     *
     * @return boolean
     */
    public function isPersonally()
    {
        $personally = null;
        if ($this->_getSettings()->offsetExists('personally')):
            $personally = $this->_getSettings()->offsetGet('personally');
        endif;
        return (1 === (int) $personally);
    }

    /**
     * Get shipment price
     *
     * @return float $amount
     */
    public function getShipmentPriceInclTax()
    {
        $this->_shipmentPriceCalculator = Mage::getModel('intraship/shipment_price');

        //Add Shipment
         $this->_shipmentPriceCalculator->setShipment($this->getShipment());

        return (float) $this->_shipmentPriceCalculator->getShipmentPrice();
    }

    /**
     * Get COD Order Total Price
     *
     * @param string $countryId
     *
     * @return float $amount
     */
    public function getCODOrderTotal($countryId)
    {
        $this->_shipmentPriceCalculator = Mage::getModel('intraship/shipment_price');

        $this->_shipmentPriceCalculator
            ->setShipment($this->getShipment())
            ->setReceiverCountryId($countryId);

        return (float) $this->_shipmentPriceCalculator->getCODOrderTotal();
    }

    /**
     * Can insurance (Versicherung)?
     *
     * @return boolean
     */
    public function canInsurance()
    {
        return ($this->getShipmentPriceInclTax() <= (float) self::INSURANCE_A);
    }

    /**
     * Get insurance (Versicherung)?
     *
     * @return boolean
     */
    public function getInsurance()
    {
        $insurance = null;
        if ($this->_getSettings()->offsetExists('insurance')):
            $insurance = $this->_getSettings()->offsetGet('insurance');
        endif;
        return (1 === (int) $insurance);
    }

    /**
     * Is insurance (Versicherung)?
     *
     * @see    Dhl_Intraship_Model_Shipment#canInsurance()
     * @return boolean
     */
    public function isInsurance()
    {
        return (true === $this->canInsurance() &&
                true === $this->getInsurance());
    }

    /**
     * Is processed ?
     *
     * @return boolean
     */
    public function isProcessed()
    {
        return ((int) $this->getStatus() === self::STATUS_PROCESSED);
    }

    /**
     * Is failed ?
     *
     * @return boolean
     */
    public function isFailed()
    {
        return (
            $this->getStatus() == self::STATUS_NEW_FAILED ||
            $this->getStatus() == self::STATUS_CANCELED_FAILED
        );
    }

    /**
     * Returns all statuses an there translated labels
     *
     * @return array
     */
    public function getStatuses()
    {
        return array(
            self::STATUS_NEW_QUEUED      => Mage::helper('intraship')->__('new (queued)'),
            self::STATUS_NEW_RETRY       => Mage::helper('intraship')->__('new (retry)'),
            self::STATUS_NEW_FAILED      => Mage::helper('intraship')->__('new (failed)'),
            self::STATUS_PROCESSED       => Mage::helper('intraship')->__('processed'),
            self::STATUS_CANCEL_QUEUED   => Mage::helper('intraship')->__('cancel (queued)'),
            self::STATUS_CANCEL_RETRY    => Mage::helper('intraship')->__('cancel (retry)'),
            self::STATUS_CANCELED_FAILED => Mage::helper('intraship')->__('cancel (failed)'),
            self::STATUS_CANCELED        => Mage::helper('intraship')->__('canceled'),
            self::STATUS_CLOSED          => Mage::helper('intraship')->__('closed'),
            self::STATUS_IN_TRANSMISSION => Mage::helper('intraship')->__('in transmission'),
        );
    }

    /**
     * Queue cancel of shipment.
     *
     * @return Dhl_Intraship_Model_Shipment $this
     */
    public function cancel()
    {
        switch ($this->getStatus()):
            case self::STATUS_NEW_FAILED:
            case self::STATUS_NEW_RETRY:
            case self::STATUS_NEW_QUEUED:
                $this->setStatus(self::STATUS_CANCELED);
                break;
            case self::STATUS_PROCESSED:
            case self::STATUS_CANCELED_FAILED:
                $this->setStatus(self::STATUS_CANCEL_QUEUED);
                break;
        endswitch;
        $this->save();
        return $this;
    }

    /**
     * Checks if shipment can be canceled.
     *
     * @return boolean
     */
    public function canCancel()
    {
        return (
            $this->getStatus() == self::STATUS_NEW_FAILED ||
            $this->getStatus() == self::STATUS_NEW_QUEUED ||
            $this->getStatus() == self::STATUS_NEW_RETRY ||
            $this->getStatus() == self::STATUS_PROCESSED ||
            $this->getStatus() == self::STATUS_CANCELED_FAILED
        );
    }

    /**
     * Checks if shipment can be edited.
     *
     * @return boolean
     */
    public function canEdit()
    {
        return (
            $this->getStatus() == self::STATUS_NEW_FAILED ||
            $this->getStatus() == self::STATUS_NEW_QUEUED ||
            $this->getStatus() == self::STATUS_NEW_RETRY
        );
    }

    /**
     * resume of canceled shipment
     *
     * @return Dhl_Intraship_Model_Shipment $this
     */
    public function resume()
    {
        switch ($this->getStatus()):
            case self::STATUS_CANCEL_QUEUED:
            case self::STATUS_CANCEL_RETRY:
            case self::STATUS_CANCELED_FAILED:
                $this->setStatus(self::STATUS_PROCESSED);
                break;
            case self::STATUS_CANCELED:
            case self::STATUS_NEW_FAILED:
                $this->setStatus(self::STATUS_NEW_QUEUED);
                break;
        endswitch;

        $this->save();
        return $this;
    }

    /**
     * Checks if shipment can be resumed.
     *
     * @return boolean
     */
    public function canResume()
    {
        return (
            $this->getStatus() == self::STATUS_NEW_FAILED ||
            $this->getStatus() == self::STATUS_CANCELED ||
            $this->getStatus() == self::STATUS_CANCEL_QUEUED ||
            $this->getStatus() == self::STATUS_CANCELED_FAILED ||
            $this->getStatus() == self::STATUS_CANCEL_RETRY
        );
    }

    /**
     * Checks if shipment can be execute via autocreate.
     *
     * @return boolean
     */
    public function canExecute()
    {
        return ($this->getStatus() == self::STATUS_NEW_FAILED);
    }

    /**
     * Close of shipment.
     *
     * @return Dhl_Intraship_Model_Shipment $this
     */
    public function close()
    {
        $this->setStatus(self::STATUS_CLOSED)->save();
        return $this;
    }

    /**
     * Returns status caption.
     *
     * @return string
     */
    public function getStatusText()
    {
        $statues = $this->getStatuses();
        if (!array_key_exists($this->getStatus(), $statues)):
            return $this->getStatus();
        endif;
        return $statues[$this->getStatus()];
    }

    /**
     * Set Shipment to "in transmission" to avoid double shipment transmission
     *
     * @return void
     */
    public function setAsInTransmission()
    {
        $this->setStatus(self::STATUS_IN_TRANSMISSION)->save();
        return $this;
    }
}

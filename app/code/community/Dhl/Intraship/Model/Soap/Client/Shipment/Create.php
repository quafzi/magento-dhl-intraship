<?php
/**
 * Dhl_Intraship_Model_Soap_Client_Shipment_Create
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_Soap_Client_Shipment_Create extends ArrayObject
{
    /**
     * Init
     *
     * @param  Dhl_Intraship_Model_Shipment                     $shipment
     * @param  array                                            $params
     * @return Dhl_Intraship_Model_Soap_Client_Shipment_Create  $this
     */
    public function init(Dhl_Intraship_Model_Shipment $shipment, array $params = null)
    {
        /* @var $receiver Mage_Sales_Model_Order_Address */
        $receiver  = $shipment->getShipment()->getShippingAddress();
        /* DHLIS-213 - avoid shipment without address */
        if (!$receiver || !$receiver instanceof Mage_Sales_Model_Order_Address) {
            throw new Dhl_Intraship_Model_Soap_Client_Shipment_Exception(
                'Could not generate shipment without its receiver address');
        }

        /* @var $order Mage_Sales_Model_Order */
        $order     = $shipment->getShipment()->getOrder();
        /* @var $config Dhl_Intraship_Model_Config */
        $config    = Mage::getModel('intraship/config');
        /*
         * Set shipment data to object.
         */
        $this->set('params', new ArrayObject($params))
             ->set('shipment', $shipment)
             ->set('receiver', $receiver)
             ->set('shipper', $config->getAccountAddress($order->getStoreId()))
             ->set('order', $order)
             ->set('ekp', $config->getAccountEkp($order->getStoreId()));
        /*
         * Set profile.
         */
        $this->set('profile', $config->getProfileByCountryCode(
            $this->_getCorrectReceiverCountryId(), $shipment->getProfile(), $order->getStoreId()));
        /*
         * Set default data for result array.
         */
        $data = array();
        $this->_appendDetails($data)
             ->_appendShipper($data)
             ->_appendReceiver($data);
        /*
         * Set params to object.
         */
        $this->get('params')->offsetSet('ShipmentOrder', array(
            'SequenceNumber'    => '1',
            'LabelResponseType' => 'URL',
            'Shipment'          => $data
        ));

        //Zend_Debug::dump($this->toArray());
        //exit;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->get('params')->getArrayCopy();
    }

    /**
     * Set offset.
     *
     * @param  mixed                                            $index
     * @param  mixed                                            $value
     * @return Dhl_Intraship_Model_Soap_Client_Shipment_Create  $this
     */
    public function set($index, $value)
    {
        parent::offsetSet($index, $value);
        return $this;
    }

    /**
     * Get offset.
     *
     * @param  mixed        $index
     * @return mixed|null
     */
    public function get($index)
    {
        if (parent::offsetExists($index)):
            return parent::offsetGet($index);
        endif;
    }

    /**
     * Get shipment details.
     *
     * @param  array                                            $data <Reference>
     * @return Dhl_Intraship_Model_Soap_Client_Shipment_Create  $this
     */
    protected function _appendDetails(array &$data)
    {
        $isCOD = (bool) $this->get('shipment')->isCOD();
        // Throw exception if is COD and bank data is empty.
        if (true === $isCOD && true === $this->_isBankDataEmpty()):
            throw new Dhl_Intraship_Model_Soap_Client_Response_Exception(
                'Payment method cash on delivery is not possible without bank data.');
        endif;

        /*
         * Set shipping details.
         */
        $details = array(
            'ProductCode'  => $this->get('profile')->offsetGet('code'),
            'ShipmentDate' => Mage::getModel('core/date')->date('Y-m-d'),
            'DeclaredValueOfGoods' => $this->get('shipment')->getShipmentPriceInclTax(),
            'DeclaredValueOfGoodsCurrency' => $this->get('order')->getOrderCurrencyCode(),
            'EKP' => $this->get('ekp'),
            'Attendance' => array(
                'partnerID' => $this->get('profile')->offsetGet('partnerId')
            ),
            'CustomerReference' => $this->get('order')->getIncrementId(),
            // Shipment desciption is optional an appears errors on 1.3.x.x
            //'Description' => $this->get('order')->getShippingDescription()
        );
        /*
         * Set package items.
         */
        $items = array();
        foreach ($this->get('shipment')->getWeightsInKG() as $num => $weight):
            $items += array($num => array(
                'WeightInKG'  => (string) $this->_getWeightInKG($weight),
                'PackageType' => 'PK'
            ));
        endforeach;
        $details += array('ShipmentItem' => $items);
        /*
         * Set services.
         */
        $services = array();
        // Set bulkfreight to service group other.
        if (true === $this->get('shipment')->isBulkfreight()):
            $services[] = array('ServiceGroupOther' => array(
                'Bulkfreight' => true));
        endif;
        // Set cash on delivery to service group other.
        if (true === $isCOD):
            $services[] = array('ServiceGroupOther' => array('COD' => array(
                'CODAmount'   => $this->get('shipment')->getCODOrderTotal($this->_getCorrectReceiverCountryId()),
                'CODCurrency' => $this->get('order')->getOrderCurrencyCode()
            )));
        endif;
        // Set insurance.
        if (true === $this->get('shipment')->isInsurance()):
            $services[] = array('ServiceGroupOther' => array('HigherInsurance' => array(
                'InsuranceAmount'   => Dhl_Intraship_Model_Shipment::INSURANCE_A,
                'InsuranceCurrency' => $this->get('order')->getOrderCurrencyCode()
            )));
        endif;
        // Set multipack delivery to service.
        if (true === $this->get('shipment')->isMultipack()):
            $services[] = array('ServiceGroupDHLPaket' => array(
                'Multipack' => true
            ));
        endif;
        // Set personally if isset and COD is disabled.
        if (true === $this->get('shipment')->isPersonally() &&
            true !== $isCOD
        ):
            $services[] = array('ShipmentServiceGroupIdent' => array(
                'Personally' => true
            ));
        endif;
        // Append services to shipment details if exists.
        if (sizeof($services) >= 1):
            $details += array('Service' => $services);
            if (true === $isCOD):
                // Add bank data for cash on delivery service.
                $bankData = Mage::getModel('intraship/config')
                    ->getAccountBankData($this->get('shipment')->getShipment()->getOrder()->getStoreId());
                $details += array('BankData' => array(
                    'accountOwner'  => $bankData->offsetGet('accountOwner'),
                    'accountNumber' => trim($bankData->offsetGet('accountNumber')),
                    'bankCode'      => trim($bankData->offsetGet('bankCode')),
                    'bankName'      => $bankData->offsetGet('bankName'),
                    'iban'          => trim($bankData->offsetGet('iban')),
                    'bic'           => trim($bankData->offsetGet('bic')),
                    'note'          => $this->_setPlaceholder($bankData->offsetGet('note')),
                ));
            endif;
        endif;
        //var_dump($details);
        $data += array('ShipmentDetails' => $details);
        return $this;
    }


    /**
     * encode and trim value
     *
     * @param string $value
     *
     * @return string
     */
    protected function cleanStringValue($value)
    {
        //return utf8_decode(trim($value));
        return trim($value);
    }

    /**
     * Append shipper.
     *
     * @see    etc/config.xml:intraship/soap/shipment/shipper
     * @param  array                                            $data <Reference>
     * @return Dhl_Intraship_Model_Soap_Client_Shipment_Create  $this
     */
    protected function _appendShipper(array &$data)
    {
        /* @var $address ArrayObject */
        $address   = $this->get('shipper');
        $countryId = $address->offsetGet('countryISOCode');
        $country   = Mage::getModel('directory/country')->load($countryId)
            ->getName();
        // Set shipper
        $shipper = array(
            'Company' => array(
                'Company' => array(
                    'name1' => $this->cleanStringValue($address->offsetGet('companyName1')),
                    'name2' => $this->cleanStringValue($address->offsetGet('companyName2')),
                ),
            ),
            'Address' => array(
                'streetName'   => $this->cleanStringValue($address->offsetGet('streetName')),
                'streetNumber' => $this->cleanStringValue($address->offsetGet('streetNumber')),
                'Zip' => array(
                    $this->_getZipNodeByCountryId($countryId) => $this->cleanStringValue($address->offsetGet('zip'))
                ),
                'city' => $this->cleanStringValue($address->offsetGet('city')),
                'Origin' => array(
                    'country'        => $country,
                    'countryISOCode' => $countryId,
                    //@desc Removed state field
                    //@see  DHLIS-148
                    'state'          => '' // $this->cleanStringValue($address->offsetGet('state'))
                )
            ),
            'Communication' => array(
                'phone'         => $this->cleanStringValue($address->offsetGet('phone')),
                'email'         => $this->cleanStringValue($address->offsetGet('email')),
                'contactPerson' => $this->cleanStringValue($address->offsetGet('contactPerson'))
            )
        );
        $data += array('Shipper' => $shipper);
        return $this;
    }

    protected function _normalizeReceiverAddress(Mage_Sales_Model_Order_Address &$address)
    {
        /* @var $address Mage_Sales_Model_Order_Address */
        if (false === $this->get('shipment')->hasCustomizedAddress()) {
            // calculate street_name, street_number, care_of
            $parts = Mage::helper('intraship')->splitStreet($address->getStreet(1));
            $address
                ->setStreetName($parts['street_name'])
                ->setStreetNumber($parts['street_number'])
                ->setCareOf($parts['care_of']);

            if (false !== stripos($address->getStreet(2), 'packstation')) {
                // append packstation info, if applicable
                $address
                    ->setIdNumber(preg_replace('/\D/', '', $address->getStreet(1)))
                    ->setStationId($address->getStreet(2));
            } elseif (strlen($address->getStreet(2))) {
                // append second address row, if not empty
                $address->setCareOf(
                    $address->getCareOf() . ' ' . $address->getStreet(2)
                );
            }
        } else {
            $customized = $this->get('shipment')->getCustomizedAddress();
			/* @var $customized Dhl_Intraship_Model_Address */
            // take street_name, street_number, care_of over from customized address
            $address
                ->setStreetName($customized->offsetGet('street_name'))
                ->setStreetNumber($customized->offsetGet('street_number'))
                ->setCareOf($customized->offsetGet('care_of'));
            // override fields that may have been customized
            $address
                ->setSuffix($customized->offsetGet('suffix'))
                ->setPrefix($customized->offsetGet('prefix'))
                ->setFirstname($customized->offsetGet('firstname'))
                ->setMiddlename($customized->offsetGet('middlename'))
                ->setLastname($customized->offsetGet('lastname'))
                ->setCity($customized->offsetGet('city'))
                ->setCompany($customized->offsetGet('company'))
                ->setRegion($customized->offsetGet('region'))
                ->setPostcode($customized->offsetGet('postcode'))
                ->setTelephone($customized->offsetGet('telephone'))
                ->setEmail($customized->offsetGet('email'))
                ->setCountryId($customized->offsetGet('country_id'));
            // set packstation info
            $address
                ->setIdNumber($customized->offsetGet('id_number'))
                ->setStationId($customized->offsetGet('station_id'));
        }
    }

    /**
     * Append receiver.
     *
     * @param  array                                            $data <Reference>
     * @return Dhl_Intraship_Model_Soap_Client_Shipment_Create  $this
     */
    protected function _appendReceiver(array &$data)
    {
        /* @var $address Mage_Sales_Model_Order_Address */
        $address = $this->get('receiver');

        // make sure that helper always gets address data in correct format
        $this->_normalizeReceiverAddress($address);

        $receiver = Mage::helper('intraship')->getReceiver($address);

        // "contactPerson" must not be empty for countries outside DE (DHLIS-450)
        if (empty($receiver['Receiver']['Communication']['contactPerson'])
            && ($this->get('profile')->offsetGet('code') == strtoupper(Dhl_Intraship_Model_Config::PACKAGE_BPI))) {
            $receiver['Receiver']['Communication']['contactPerson'] = '.';
        }

        $data += $receiver;

        return $this;
	}


	/**
     * Get correct country id of receiver (ISO).
     *
     * @return string
     */
    public function _getCorrectReceiverCountryId()
    {
        if (true === $this->get('shipment')->hasCustomizedAddress()):
            return $this->get('shipment')
                ->getCustomizedAddress()
                ->offsetGet('country_id');
        endif;
        return $this->get('receiver')->getCountryId();
    }

    /**
     * Get node for zip.
     *
     * @param  string   $countryId          ISO 3166
     * @return string   $zipCountry
     */
    protected function _getZipNodeByCountryId($countryId)
    {
        if ('DE' === $countryId):
            $zipCountry = 'germany';
        elseif ('GB' === $countryId):
            $zipCountry = 'england';
        else:
            $zipCountry = 'other';
        endif;
        return $zipCountry;
    }


    /**
     * Get weight in KG by receiver country.
     *
     * @return float    $weight
     * @return float    $weight
     */
    protected function _getWeightInKG($weight)
    {
        // Set package weight in KG.
        if (empty($weight)):
            $weight = $this->get('profile')->offsetGet('weight');
        endif;
        if ((float) $weight < (float) Dhl_Intraship_Model_Shipment::MIN_WEIGHT_KG):
            $weight = Dhl_Intraship_Model_Shipment::MIN_WEIGHT_KG;
        endif;
        return (float) $weight;
    }

    /**
     * Check if bank data is empty
     *
     * @return boolean
     */
    protected function _isBankDataEmpty()
    {
        $bankData = Mage::getModel('intraship/config')->getAccountBankData(
            $this->get('shipment')->getShipment()->getOrder()->getStoreId()
        );
        return (
            !$bankData->offsetGet('accountOwner') ||
            !$bankData->offsetGet('accountNumber') ||
            !$bankData->offsetGet('bankCode') ||
            !$bankData->offsetGet('bankName')
        );
    }

    /**
     * Replace placeholder.
     *
     * @param  string $notice
     * @return string
     */
    protected function _setPlaceholder($notice)
    {
        $notice = str_replace('%shippingID%',
            $this->get('shipment')->getShipment()->getIncrementId(), $notice);
        $notice = str_replace('%orderID%',
            $this->get('order')->getIncrementId(), $notice);
        $notice = str_replace('%transactionID%',
            $this->get('order')->getPayment()->getLastTransId(), $notice);
        $notice = str_replace('%customerName%',
            $this->get('order')->getCustomerName() , $notice);
        $notice = str_replace('%customerID%',
            $this->get('order')->getCustomerId(), $notice);

        $notice = str_replace(
            array('ä', 'ö', 'ü', 'ß'),
            array('ae', 'oe', 'ue', 'ss'),
            $notice
        );

        return $notice;

    }
}

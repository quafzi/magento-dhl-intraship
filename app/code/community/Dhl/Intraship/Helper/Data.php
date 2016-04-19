<?php
/**
 * Dhl_Intraship_Helper_Data
 *
 * @category  Helpers
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Helper_Data extends Mage_Core_Helper_Data
{
    /**
     * Convert weight in "kg" if default produkt unit is "g".
     *
     * @param  float $weight
     * @return float $weight
     */
    public function convertWeight($weight, $config=null)
    {
        if (is_null($config)) {
            $config = Mage::getModel('intraship/config');
        }
        if (Dhl_Intraship_Model_Config::WEIGHT_UNIT_KG === $config->getProductWeightUnit()):
            return (float) $weight;
        endif;
        return (float) $weight / 1000;
    }

    /**
     * Sum up all items value to check if insurance is possible.
     *
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @return bool false if value exceeds max insurance, true otherwise
     */
    public function isInsurable(Mage_Sales_Model_Order_Shipment $shipment)
    {
        if (!$shipment instanceof Mage_Sales_Model_Order_Shipment) {
            return true;
        }

        $amount = 0;

        /** @var Mage_Sales_Model_Order_Shipment_Item $item */
        foreach ($shipment->getAllItems() as $item) {
            if($item->getOrderItem()->getParentItemId()){
                continue;
            }
            $amount += (float) $item->getOrderItem()->getPriceInclTax() * $item->getQty();
        }
        return ((float) $amount <= (float) Dhl_Intraship_Model_Shipment::INSURANCE_A);
    }

    /**
     * split street into street name, number and care of
     *
     * @param string $street
     *
     * @return array
     */
    public function splitStreet($street)
    {
        /*
         * first pattern  | street_name             | required | ([^0-9]+)         | all characters != 0-9
         * second pattern | additional street value | optional | ([0-9]+[ ])*      | numbers + white spaces
         * ignore         |                         |          | [ \t]*            | white spaces and tabs
         * second pattern | street_number           | optional | ([0-9]+[-\w^.]+)? | numbers + any word character
         * ignore         |                         |          | [, \t]*           | comma, white spaces and tabs
         * third pattern  | care_of                 | optional | ([^0-9]+.*)?      | all characters != 0-9 + any character except newline
         */
        if (preg_match("/^([^0-9]+)([0-9]+[ ])*[ \t]*([0-9]*[-\w^.]*)?[, \t]*([^0-9]+.*)?\$/", $street, $matches)) {

            //check if street has additional value and add it to streetname
            if (preg_match("/^([0-9]+)?\$/", trim($matches[2]))) {
                $matches[1] = $matches[1] . $matches[2];

            }
            return array(
                'street_name'   => trim($matches[1]),
                'street_number' => isset($matches[3]) ? $matches[3] : '',
                'care_of'       => isset($matches[4]) ? trim($matches[4]) : ''
            );
        }
        return array(
            'street_name'   => $street,
            'street_number' => '',
            'care_of'       => ''
        );
    }

    /**
     * Check whether company address field is set or not
     * @param Mage_Sales_Model_Order_Address $address
     * @return boolean true if address contains company field, false otherwise
     */
    protected function _isCompanyAddress(Mage_Sales_Model_Order_Address $address)
    {
        return (bool)$address->getCompany();
    }

    /**
     * Check whether second steet row indicates packstation delivery address
     * @param Mage_Sales_Model_Order_Address $address
     * @return boolean true if steet contains 'packstation', false otherwise
     */
    protected function _isPackstationAddress(Mage_Sales_Model_Order_Address $address)
    {
        return (bool)$address->getStationId();
    }





    protected function _setReceiverCompanyCompany(Mage_Sales_Model_Order_Address $address, array &$receiver)
    {
        $receiver['Company']['Company']['name1'] = $address->getCompany();
        unset($receiver['Company']['Person']);
    }

    protected function _setReceiverCompanyPerson(Mage_Sales_Model_Order_Address $address, array &$receiver)
    {
        $receiver['Company']['Person']['salutation'] = $address->getPrefix();
        $receiver['Company']['Person']['title']      = $address->getSuffix();
        $receiver['Company']['Person']['firstname']  = $address->getFirstname();
        $receiver['Company']['Person']['middlename'] = $address->getMiddlename();
        $receiver['Company']['Person']['lastname']   = $address->getLastname();

        unset($receiver['Company']['Company']);
    }

    protected function _setReceiverAddress(Mage_Sales_Model_Order_Address $address, array &$receiver)
    {
        $receiver['Address']['careOfName'] = $address->getCareOf();

        $countryId = $address->getCountryId();
        switch ($countryId) {
            case 'DE':
                $receiver['Address']['Zip']['germany'] = $address->getPostcode();
                break;
            case 'UK':
                $receiver['Address']['Zip']['england'] = $address->getPostcode();
                break;
            default:
                $receiver['Address']['Zip']['other'] = $address->getPostcode();
            break;
        }
        unset($receiver['Address']['Zip']['__country_id__']);

        $receiver['Address']['city'] = $address->getCity();

        $receiver['Address']['Origin']['country'] = Mage::getModel('directory/country')->load($countryId)->getName();
        $receiver['Address']['Origin']['countryISOCode'] = $countryId;
    }

    protected function _setReceiverCommunication(Mage_Sales_Model_Order_Address $address, array &$receiver)
    {
        $receiver['Communication']['phone'] = $address->getTelephone();
        // @desc Removed email field
        // @see  DHLIS-563
        unset($receiver['Communication']['email']);
    }





    protected function _setCompanyPackstationReceiver(
        Mage_Sales_Model_Order_Address $address, array &$receiver)
    {
        // COMPANY
        $this->_setReceiverCompanyCompany($address, $receiver);
        // add dhl customer id from first address row
        $receiver['Company']['Company']['name2'] = preg_replace('/\D/', '', $address->getIdNumber());

        // ADDRESS
        $receiver['Address']['streetName'] = 'Packstation';
        // add station id
        $receiver['Address']['streetNumber'] = preg_replace('/\D/', '', $address->getStationId());
        $this->_setReceiverAddress($address, $receiver);

        // COMMUNICATION
        $this->_setReceiverCommunication($address, $receiver);
        $receiver['Communication']['contactPerson'] = sprintf('%s %s', $address->getFirstname(), $address->getLastname());
    }

    protected function _setCompanyNoPackstationReceiver(
        Mage_Sales_Model_Order_Address $address, array &$receiver)
    {
        // COMPANY
        $this->_setReceiverCompanyCompany($address, $receiver);
        // no dhl customer id available
        unset($receiver['Company']['Company']['name2']);

        // ADDRESS
        $receiver['Address']['streetName'] = $address->getStreetName();
        $receiver['Address']['streetNumber'] = $address->getStreetNumber();
        $this->_setReceiverAddress($address, $receiver);

        // COMMUNICATION
        $this->_setReceiverCommunication($address, $receiver);
        $receiver['Communication']['contactPerson'] = sprintf('%s %s', $address->getFirstname(), $address->getLastname());
    }

    protected function _setNoCompanyPackstationReceiver(
        Mage_Sales_Model_Order_Address $address, array &$receiver)
    {
        // COMPANY
        $this->_setReceiverCompanyPerson($address, $receiver);

        // ADDRESS
        $receiver['Address']['streetName'] = 'Packstation';
        // add station id
        $receiver['Address']['streetNumber'] = preg_replace('/\D/', '', $address->getStationId());
        $this->_setReceiverAddress($address, $receiver);

        // COMMUNICATION
        $this->_setReceiverCommunication($address, $receiver);
        $receiver['Communication']['contactPerson'] = preg_replace('/\D/', '', $address->getIdNumber());
    }

    protected function _setNoCompanyNoPackstationReceiver(
        Mage_Sales_Model_Order_Address $address, array &$receiver)
    {
        // COMPANY
        $this->_setReceiverCompanyPerson($address, $receiver);

        // ADDRESS
        $receiver['Address']['streetName'] = $address->getStreetName();
        $receiver['Address']['streetNumber'] = $address->getStreetNumber();
        $this->_setReceiverAddress($address, $receiver);

        // COMMUNICATION
        $this->_setReceiverCommunication($address, $receiver);
        $receiver['Communication']['contactPerson'] = '';
    }




    public function getReceiver(Mage_Sales_Model_Order_Address $address)
    {
        // default dhl receiver array.
        $receiver = array(
            'Company' => array(
				'Company' => array(
				    'name1' => '__name1__',
				    'name2' => '__name2__',
                ),
				'Person' => array(
					'salutation' => '__salutation__',
                    'title'      => '__title__',
                    'firstname'  => '__firstname__',
                    'middlename' => '__middlename__',
                    'lastname'   => '__lastname__',
                )
		    ),
			'Address' => array(
            	'streetName'   => '__street_name__',
                'streetNumber' => '__street_number',
                'careOfName'   => '__care_of_name__', // c/o
                'Zip' => array(
                	'__country_id__' => '__postcode__'
                ),
                'city' => '__city__',
                'Origin' => array(
                	'country'        => '__country_name__',
                    'countryISOCode' => '__country_id__',
                    'state'          => '' // always empty
                )
            ),
            'Communication' => array(
            	'phone'         => '__phone__',
                'email'         => '__email__',
                'contactPerson' => '__contactPerson__'
            )
        );

        // switch. makes things easier to maintain…
        if ($this->_isCompanyAddress($address) && $this->_isPackstationAddress($address)) {
            // company: yes, packstation: yes
            $this->_setCompanyPackstationReceiver($address, $receiver);

        } elseif ($this->_isCompanyAddress($address) && !$this->_isPackstationAddress($address)) {
            // company: yes, packstation: no
            $this->_setCompanyNoPackstationReceiver($address, $receiver);

        } elseif (!$this->_isCompanyAddress($address) && $this->_isPackstationAddress($address)) {
            // company: no, packstation: yes
            $this->_setNoCompanyPackstationReceiver($address, $receiver);

        } elseif (!$this->_isCompanyAddress($address) && !$this->_isPackstationAddress($address)) {
            // company: no, packstation: no
            $this->_setNoCompanyNoPackstationReceiver($address, $receiver);

        }

        // finally, trim and utf8-decode all values
        array_walk_recursive($receiver, create_function('&$value, $key', '$value = trim($value);'));

        return array('Receiver' => $receiver);
    }

    /**
     * Check if product type is allowed for weight calculation
     *
     * @param  string $productType
     *
     * @return boolean
     */
    public function isAllowedProductTypeForWeightCalculation($productType)
    {
        if (in_array($productType, Mage::getModel('intraship/config')->getProductTypesForWeightCalculation())):
            return true;
        else:
            return false;
        endif;
    }

    /**
     * get store id which is selected in the admin store switcher
     *
     * @return string
     */
    public function getAdminStoreId()
    {
        return Mage::app()->getRequest()->getParam('store');
    }

    /**
     * Obtain all orders that, according to config, apply for shipment auto creation.
     *
     * @return Mage_Sales_Model_Mysql4_Order_Collection
     */
    public function getAutocreateOrders()
    {
        /* @var $config Dhl_Intraship_Model_Config */
        $config = Mage::getModel('intraship/config');
        $allowedStatusCodes      = $config->getAutocreateAllowedStatusCodes();
        $allowedPaymentMethods   = $config->getAutocreateAllowedPaymentMethods();
        $disabledShippingMethods = $config->getDisabledShippingMethods();
        $installDate             = $config->getInstallDate();

        /* @var $orderCollection Mage_Sales_Model_Resource_Order_Collection */
        $orderCollection = Mage::getModel('sales/order')->getCollection();

        $coreVersion = Mage::getConfig()->getModuleConfig('Mage_Core')->version;
        if (version_compare($coreVersion, '1.6.0.0', '<')) {
            $orderCollection->getSelect()->join(
                array('payment_table' => $orderCollection->getTable('sales/order_payment')),
                "`main_table`.`entity_id` = `payment_table`.`parent_id`",
                array()
            );
        } else {
            $orderCollection->join(
                array('payment_table' => 'sales/order_payment'),
                "`main_table`.`entity_id` = `payment_table`.`parent_id`",
                array()
            );
        }

        $orderCollection
            ->addFieldToFilter('status', array('in' => explode(',', $allowedStatusCodes)))
            ->addFieldToFilter('method', array('in' => explode(',', $allowedPaymentMethods)))
            ->addFieldToFilter('shipping_method', array('nin' => explode(',', $disabledShippingMethods)))
            ->addFieldToFilter('created_at', array('gteq' => $installDate))
        ;

        return $orderCollection;
    }
}

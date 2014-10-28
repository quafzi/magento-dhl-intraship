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
 * DHL OnlineRetoure Soap Client Model
 *
 * @category    Dhl
 * @package     Dhl_OnlineRetoure
 * @author      André Herrn <andre.herrn@netresearch.de>
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Dhl_OnlineRetoure_Model_Soap_Client extends Zend_Soap_Client
{
    /**
     * @var Dhl_OnlineRetoure_Model_Config
     */
    protected $_config;
    /**
     * @var Mage_Sales_Model_Order
     */
    protected $_order;
    /**
     * @var string
     */
    protected $_username;
    /**
     * @var string
     */
    protected $_password;
    /**
     * @var string
     */
    protected $_portalId;
    /**
     * @var string
     */
    protected $_deliveryName;
    /**
     * @var string
     */
    protected $_shipmentReference;
    /**
     * @var string
     */
    protected $_customerReference;
    /**
     * @var string
     */
    protected $_labelFormat;
    /**
     * @var string
     */
    protected $_senderName1;
    /**
     * @var string
     */
    protected $_senderName2;
    /**
     * @var string
     */
    protected $_senderCareOfName;
    /**
     * @var string
     */
    protected $_senderContactPhone;
    /**
     * @var string
     */
    protected $_senderBoxNumber;
    /**
     * @var string
     */
    protected $_senderStreet;
    /**
     * @var string
     */
    protected $_senderStreetNumber;
    /**
     * @var string
     */
    protected $_senderPostalCode;
    /**
     * @var string
     */
    protected $_senderCity;

    public function __construct($config = null,
            Mage_Sales_Model_Order $order = null, array $options = null)
    {
        if ($config) {
            $this->setConfig($config);
        }
        if ($order) {
            $this->setOrder($order);
        }
        if (!is_array($options)) {
            $options = array();
        }
        $options['soap_version'] = SOAP_1_1;

        parent::__construct(null, $options);
    }

    public function setConfig(Dhl_OnlineRetoure_Model_Config $config)
    {
        $this->_config = $config;
        $this
            ->setUsername($this->_config->getUser())
            ->setPassword($this->_config->getPassword())
            ->setPortalId($this->_config->getPortalId())
            ->setLabelFormat(Dhl_OnlineRetoure_Model_Config::ONLINERETOURE_LABEL_FORMAT)
            ->setWsdl($this->_config->getWsdlUri())
        ;

        return $this;
    }

    public function setOrder(Mage_Sales_Model_Order $order)
    {
        $this->_order  = $order;
        $shippingAddress = $order->getShippingAddress();

        /* @var $helper Dhl_Intraship_Helper_Data */
        $helper = Mage::helper('intraship/data');

        $senderName1 = $shippingAddress->getFirstname()." ".$shippingAddress->getLastname();
        $senderName2 = "";
        if ($shippingAddress->getCompany()) {
            $senderName2 = $senderName1;
            $senderName1 = $shippingAddress->getCompany();
        }
        $street = $helper->splitStreet($shippingAddress->getStreetFull());

        $this
            ->setDeliveryName($this->_config->getDeliveryNameByCountry($shippingAddress->getCountryId()))
            ->setSenderName1($senderName1)
            ->setSenderName2($senderName2)
            ->setSenderStreet($street['street_name'])
            ->setSenderStreetNumber($street['street_number'])
            ->setSenderCareOfName($street['care_of'])
            ->setSenderPostalCode($shippingAddress->getPostcode())
            ->setSenderCity($shippingAddress->getCity())
            ->setCustomerReference($this->_order->getIncrementId())
            ;

        return $this;
    }

    /**
     * Set WS-Security username.
     * @param string $username
     * @return Dhl_OnlineRetoure_Model_Soap_Client
     */
    public function setUsername($username)
    {
        $this->_username = $username;
        return $this;
    }

    /**
     * Get WS-Security username.
     * @return string
     */
    public function getUsername()
    {
        return $this->_username;
    }

    /**
     * Set WS-Security password.
     * @param string $password
     * @return Dhl_OnlineRetoure_Model_Soap_Client
     */
    public function setPassword($password)
    {
        $this->_password = $password;
        return $this;
    }

    /**
     * Get WS-Security password.
     * @return string
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * Set portalId request parameter.
     * @param string $portalId
     * @return Dhl_OnlineRetoure_Model_Soap_Client
     */
    public function setPortalId($portalId)
    {
        $this->_portalId = $portalId;
        return $this;
    }

    /**
     * Get portalId request parameter.
     * @return string
     */
    public function getPortalId()
    {
        return $this->_portalId;
    }

    /**
     * Set deliveryName request parameter.
     * @param string $deliveryName
     * @return Dhl_OnlineRetoure_Model_Soap_Client
     */
    public function setDeliveryName($deliveryName)
    {
        $this->_deliveryName = $deliveryName;
        return $this;
    }

    /**
     * Get deliveryName request parameter.
     * @return string
     */
    public function getDeliveryName()
    {
        return $this->_deliveryName;
    }

    /**
     * Set shipmentReference request parameter.
     * @param string $shipmentReference
     * @return Dhl_OnlineRetoure_Model_Soap_Client
     */
    public function setShipmentReference($shipmentReference)
    {
        $this->_shipmentReference = $shipmentReference;
        return $this;
    }

    /**
     * Get shipmentReference request parameter.
     * @return string
     */
    public function getShipmentReference()
    {
        return $this->_shipmentReference;
    }

    /**
     * Set customerReference request parameter.
     * @param string $customerReference
     * @return Dhl_OnlineRetoure_Model_Soap_Client
     */
    public function setCustomerReference($customerReference)
    {
        $this->_customerReference = $customerReference;
        return $this;
    }

    /**
     * Get customerReference request parameter.
     * @return string
     */
    public function getCustomerReference()
    {
        return $this->_customerReference;
    }

    /**
     * Set labelFormat request parameter.
     * @param string $labelFormat
     * @return Dhl_OnlineRetoure_Model_Soap_Client
     */
    public function setLabelFormat($labelFormat)
    {
        $this->_labelFormat = $labelFormat;
        return $this;
    }

    /**
     * Get labelFormat request parameter.
     * @return string
     */
    public function getLabelFormat()
    {
        return $this->_labelFormat;
    }

    /**
     * Set senderName1 (i.e. firstname) request parameter.
     * @param string $senderName1
     * @return Dhl_OnlineRetoure_Model_Soap_Client
     */
    public function setSenderName1($senderName1)
    {
        $this->_senderName1 = $senderName1;
        return $this;
    }

    /**
     * Get senderName1 (i.e. firstname) request parameter.
     * @return string
     */
    public function getSenderName1()
    {
        return $this->_senderName1;
    }

    /**
     * Set senderName2 (i.e. lastname) request parameter.
     * @param string $senderName2
     * @return Dhl_OnlineRetoure_Model_Soap_Client
     */
    public function setSenderName2($senderName2)
    {
        $this->_senderName2 = $senderName2;
        return $this;
    }

    /**
     * Get senderName2 (i.e. lastname) request parameter.
     * @return string
     */
    public function getSenderName2()
    {
        return $this->_senderName2;
    }

    /**
     * Set senderCareOfName request parameter.
     * @param string $senderCareOfName
     * @return Dhl_OnlineRetoure_Model_Soap_Client
     */
    public function setSenderCareOfName($senderCareOfName)
    {
        $this->_senderCareOfName = $senderCareOfName;
        return $this;
    }

    /**
     * Get senderCareOfName request parameter.
     * @return string
     */
    public function getSenderCareOfName()
    {
        return $this->_senderCareOfName;
    }

    /**
     * Set senderContactPhone request parameter.
     * @param string $senderContactPhone
     * @return Dhl_OnlineRetoure_Model_Soap_Client
     */
    public function setSenderContactPhone($senderContactPhone)
    {
        $this->_senderContactPhone = $senderContactPhone;
        return $this;
    }

    /**
     * Get senderContactPhone request parameter.
     * @return string
     */
    public function getSenderContactPhone()
    {
        return $this->_senderContactPhone;
    }

    /**
     * Set senderBoxNumber request parameter.
     * @param string $senderBoxNumber
     * @return Dhl_OnlineRetoure_Model_Soap_Client
     */
    public function setSenderBoxNumber($senderBoxNumber)
    {
        $this->_senderBoxNumber = $senderBoxNumber;
        return $this;
    }

    /**
     * Get senderBoxNumber request parameter.
     * @return string
     */
    public function getSenderBoxNumber()
    {
        return $this->_senderBoxNumber;
    }

    /**
     * Set senderStreet request parameter.
     * @param string $senderStreet
     * @return Dhl_OnlineRetoure_Model_Soap_Client
     */
    public function setSenderStreet($senderStreet)
    {
        $this->_senderStreet = $senderStreet;
        return $this;
    }

    /**
     * Get senderStreet request parameter.
     * @return string
     */
    public function getSenderStreet()
    {
        return $this->_senderStreet;
    }

    /**
     * Set senderStreetNumber request parameter.
     * @param string $senderStreetNumber
     * @return Dhl_OnlineRetoure_Model_Soap_Client
     */
    public function setSenderStreetNumber($senderStreetNumber)
    {
        $this->_senderStreetNumber = $senderStreetNumber;
        return $this;
    }

    /**
     * Get senderStreetNumber request parameter.
     * @return string
     */
    public function getSenderStreetNumber()
    {
        return $this->_senderStreetNumber;
    }

    /**
     * Set senderPostalCode request parameter.
     * @param string $senderPostalCode
     * @return Dhl_OnlineRetoure_Model_Soap_Client
     */
    public function setSenderPostalCode($senderPostalCode)
    {
        $this->_senderPostalCode = $senderPostalCode;
        return $this;
    }

    /**
     * Get senderPostalCode request parameter.
     * @return string
     */
    public function getSenderPostalCode()
    {
        return $this->_senderPostalCode;
    }

    /**
     * Set senderCity request parameter.
     * @param string $senderCity
     * @return Dhl_OnlineRetoure_Model_Soap_Client
     */
    public function setSenderCity($senderCity)
    {
        $this->_senderCity = $senderCity;
        return $this;
    }

    /**
     * Get senderCity request parameter.
     * @return string
     */
    public function getSenderCity()
    {
        return $this->_senderCity;
    }

    /**
     * Send label request
     *
     * @return stdClass
     * @throws SoapFault
     */
    public function requestLabel()
    {
        if (!$this->_config) {
            Mage::throwException('Please provide configuration on webservice client');
        }
        if (!$this->_order) {
            Mage::throwException('Please provide the order to return on webservice client');
        }

        $wssUser = $this->getUsername();
        $wssPass = $this->getPassword();

        $header = new Dhl_OnlineRetoure_Model_Soap_Header_Auth($wssUser, $wssPass);
        $this->addSoapInputHeader($header);

        $params = array(
            'portalId'           => $this->getPortalId(),
            'deliveryName'       => $this->getDeliveryName(),
            'shipmentReference'  => $this->getShipmentReference(),
            'customerReference'  => $this->getCustomerReference(),
            'labelFormat'        => $this->getLabelFormat(),
            'senderName1'        => $this->getSenderName1(),
            'senderName2'        => $this->getSenderName2(),
            'senderCareOfName'   => $this->getSenderCareOfName(),
            'senderContactPhone' => $this->getSenderContactPhone(),
            'senderBoxNumber'    => $this->getSenderBoxNumber(),
            'senderStreet'       => $this->getSenderStreet(),
            'senderStreetNumber' => $this->getSenderStreetNumber(),
            'senderPostalCode'   => $this->getSenderPostalCode(),
            'senderCity'         => $this->getSenderCity(),
            'itemWeight'         => '1',
            'itemWorth'          => '1',
        );

        $response = $this->BookLabel($params);

        $message = sprintf(
            "\nSUCCESS - Return Label Gateway Request\n  issueDate: %s\n  routingCode: %s\n  idc: %s\n  idcType: %s\n  label: %.32s…",
            $response->issueDate,
            $response->routingCode,
            $response->idc,
            $response->idcType,
            $response->label
        );
        Mage::helper('dhlonlineretoure/data')->log($message, Zend_Log::INFO);

        return $response;
    }
}
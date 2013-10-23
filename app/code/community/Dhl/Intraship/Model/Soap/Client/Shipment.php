<?php
/**
 * Dhl_Intraship_Model_Soap_Client_Shipment
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_Soap_Client_Shipment
{
    /**
     * SOAP Client
     *
     * @var Dhl_Intraship_Model_Soap_Client
     */
    protected $_cli;

    /**
     * Constructor
     *
     * @return Dhl_Intraship_Model_Soap_Client_Shipment
     */
    public function __construct($store = null)
    {
        $this->_cli = Mage::getModel('intraship/soap_client', $store);
        $this->_cli->setSoapVersion(SOAP_1_1);
        $this->_cli->setEncoding('UTF-8');
        $this->_cli->setClassmap(array(
            'DeletionState' => 'Dhl_Intraship_Model_Soap_Client_Response',
            'CreationState' => 'Dhl_Intraship_Model_Soap_Client_Response',
            'LabelData'     => 'Dhl_Intraship_Model_Soap_Client_Response'
        ));
    }

    /**
     * Get shipment label
     *
     * @param  Dhl_Intraship_Model_Shipment             $shipment
     * @return Dhl_Intraship_Model_Soap_Client_Response
     */
    public function label(Dhl_Intraship_Model_Shipment $shipment)
    {
        // Set request parameters.
        $params = $this->_cli->getDefaultParams();
        $params+= array('ShipmentNumber' => array('shipmentNumber' =>
            $shipment->getShipmentNumber()));
        // Get response message.
        $response = $this->_cli->GetLabelDD($params);
        return $response->LabelData;
    }

    /**
     * Delete  shipment
     *
     * @param  Dhl_Intraship_Model_Shipment             $shipment
     * @return Dhl_Intraship_Model_Soap_Client_Response
     */
    public function delete(Dhl_Intraship_Model_Shipment $shipment)
    {
        // Set request parameters.
        $params = $this->_cli->getDefaultParams();
        $params+= array('ShipmentNumber' => array('shipmentNumber' =>
            $shipment->getShipmentNumber()));
        // Get response message.
        $response = $this->_cli->DeleteShipmentDD($params);
        return $response->DeletionState;
    }

    /**
     * Create new shipment
     *
     * @param  Dhl_Intraship_Model_Shipment             $shipment
     * @return Dhl_Intraship_Model_Soap_Client_Response
     */
    public function create(Dhl_Intraship_Model_Shipment $shipment)
    {
        /* @var $create Dhl_Intraship_Model_Soap_Client_Shipment_Create */
        $create = Mage::getModel('intraship/soap_client_shipment_create');
        $create->init($shipment, $this->_cli->getDefaultParams());        
        Mage::dispatchEvent('dhl_intraship_send_shipment_before', array('request' => $create));

        // Get response message.
        $response = $this->_cli->createShipmentDD($create->toArray());        
        Mage::dispatchEvent('dhl_intraship_send_shipment_after', array('request' => $create, 'response' => $response));
        return $response->CreationState;
    }
}

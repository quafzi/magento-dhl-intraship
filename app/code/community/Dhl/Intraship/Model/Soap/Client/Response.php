<?php
/**
 * Dhl_Intraship_Model_Soap_Client_Response
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_Soap_Client_Response
{
    /**
     * @return Dhl_Intraship_Model_Soap_Client_Response             $this
     * @throws Dhl_Intraship_Model_Soap_Client_Response_Exception
     */
    public function validate()
    {
        // Throw exception if error code is not "0".
        if ($this->getStatusCode() > 0):
         throw new Dhl_Intraship_Model_Soap_Client_Response_Exception(
               $this->getStatusMessage(), $this->getStatusCode());
        endif;
        return $this;
    }

    /**
     * @return integer
     */
    public function getStatusCode()
    {
        return (int) ((isset($this->Status)) ? $this->Status->StatusCode :
            $this->StatusCode);
    }

    /**
     * @return string $message  LOWERCASE
     */
    public function getStatusMessage()
    {
        $message = (isset($this->Status)) ? $this->Status->StatusMessage :
            $this->StatusMessage;
        // If multipe messages in response only the first will be returned.
        if (is_array($message)):
            $message = implode(' | ', $message);
        endif;
        return strtolower($message);
    }

    /**
     * @return integer
     */
    public function getSequenceNumber()
    {
        return $this->SequenceNumber;
    }

    /**
     * @return integer|string
     */
    public function getShipmentNumber()
    {
        return $this->ShipmentNumber->shipmentNumber;
    }

    /**
     * @return stdClass
     */
    public function getPieceInformation()
    {
        return $this->PieceInformation;
    }

    /**
     * @return string
     */
    public function getLabelUrl()
    {
        return $this->Labelurl;
    }
}
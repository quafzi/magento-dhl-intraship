<?php
/*
 * @FIXME
 * The overwriting of the include_path is a fix for Magento 1.3.x.x!
 * The Magento version 1.3.x.x used Zend Framework 1.7.2. Some SOAP methods
 * are missing in this version.
 */
set_include_path(realpath(
    dirname(__FILE__) . '/../../lib') . PATH_SEPARATOR . get_include_path());
/**
 * Dhl_Intraship_Model_Soap_Client
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_Soap_Client extends Zend_Soap_Client
{
    /**
     * Constructor
     */
    public function __construct($store = null)
    {
        /* @var $config Dhl_Intraship_Model_Config */
        $config = Mage::getModel('intraship/config');
        parent::__construct($config->getSoapWsdl($store), array(
            'location'       => $config->getWebserviceEndpoint($store),
            'login'          => $config->getWebserviceAuthUsername(),
            'password'       => $config->getWebserviceAuthPassword(),
            'encoding'       => 'UTF-8',
            'compression'    => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_DEFLATE,
        ));
        $header = sprintf(
            '<ns1:Authentification>
                <ns1:user>%s</ns1:user>
                <ns1:signature>%s</ns1:signature>
                <ns1:type>0</ns1:type>
            </ns1:Authentification>',
            $config->getAccountUser($store),
            $config->getAccountSignature($store)
        );
        parent::addSoapInputHeader(new SoapHeader('ns1', 'Authentification',
            new SoapVar($header, XSD_ANYXML)), true);
    }

    /**
     * @return array
     */
    public function getDefaultParams()
    {
        return array('Version' => array(
            'majorRelease' => 1,
            'minorRelease' => 0
        ));
    }

    /**
     * Perform result pre-processing
     *
     * @param array $arguments
     */
    protected function _preProcessResult($result)
    {
        $result = parent::_preProcessResult($result);

        /**
         * catch interface errors
         *
         * Excerpt of interface documentation:
         *   A value of zero means, the request was processed without error.
         *   A value greater than zero indicates that an error occurred.
         *   The detailed mapping and explanation of returned status codes is
         *   contained in the list.
         *
         * @var stdClass $status Interface status response
         */
        if (Mage::getStoreConfig('intraship/general/logging_enabled')) {
            $logfile = Mage::getModel('intraship/config')->getLogfile();
            Mage::log('REQUEST: ' . $this->getLastRequest(), null, $logfile);
            Mage::log('RESPONSE: ' . $this->getLastResponse(), null, $logfile);
        }

        $status = isset($result->status) ? $result->status : $result->Status;
        if (0 < (int) $status->StatusCode):
           throw new Dhl_Intraship_Model_Soap_Client_Response_Exception(
               $this->getErrorMessage($result), $status->StatusCode);
        endif;

        return $result;
    }

    /**
     * @return void
     */
    public function getLastRequestXML()
    {
        header('Content-type: text/xml');
        print parent::getLastRequest();
    }

    /**
     * @return void
     */
    public function getLastResponseXML()
    {
        header('Content-type: text/xml');
        print parent::getLastResponse();
    }

    protected function getErrorMessage($result)
    {
        $errorMessage = "";
        if (isset($result->status)) $errorMessage = $result->status->StatusMessage;
        if (isset($result->CreationState) && ($result->CreationState instanceof Dhl_Intraship_Model_Soap_Client_Response)):
            $errorMessage .= " | ".$result->CreationState->getStatusMessage();
        endif;
        return $errorMessage;
    }
}

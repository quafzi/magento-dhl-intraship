<?php
/**
 * Dhl Account
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
 * @package     Dhl_Account
 * @copyright   Copyright (c) 2013 Netresearch GmbH & Co. KG (http://www.netresearch.de/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * DHL Packstation webservice REST adapter
 *
 * @category    Dhl
 * @package     Dhl_Account
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Dhl_Account_Model_Rest_Adapter
    extends Dhl_Account_Model_Webservice_Adapter_Abstract
    implements Dhl_Account_Model_Webservice_Adapter_Interface
{
    /**
     * Web service client
     * @var Zend_Rest_Client
     */
    protected $client = null;

    public function __construct()
    {
        $this->setClient(new Zend_Rest_Client());
    }

    /**
     * @param Zend_Rest_Client $client
     * @return Dhl_Account_Model_Rest_Adapter
     */
    public function setClient($client)
    {
        $client->getHttpClient()->setHeaders('Accept-encoding', 'application/xml');
        return parent::setClient($client);
    }

    /**
     * (non-PHPdoc)
     * @see Dhl_Account_Model_Webservice_Adapter_Abstract::getClient()
     * @return Zend_Rest_Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set URI to REST client
     *
     * @param string $uri
     * @return Dhl_Account_Model_Rest_Adapter
     */
    public function setUri($uri)
    {
        parent::setUri($uri);
        $this->client->setUri($this->uri);

        return $this;
    }

    /**
     * Set credentials for HTTP Auth Basic to REST client
     *
     * @param string $username
     * @param string $password
     * @return Dhl_Account_Model_Webservice_Client_Abstract
     */
    public function setAuth($username, $password)
    {
        parent::setAuth($username, $password);
        $this->client->getHttpClient()->setAuth($this->username, $this->password);

        return $this;
    }

    /**
     * Call corresponding web service method and return the results.
     *
     * @param array $params
     */
    public function validateAddress(array $params)
    {
        // Not implemented yet.
        return null;
    }

    /**
     * Call corresponding web service method and return the results.
     *
     * @deprecated Zend_Rest_Client does not format URI accordingly
     * @param array $params
     * @return array The parsed packstation data
     * @throws Dhl_Account_Exception
     */
    public function findPackstations(array $params)
    {
        /* @var $helper Dhl_Account_Helper_Data */
        $helper = Mage::helper('dhlaccount/data');
        $params = $this->getDefaultParams() + $params;

        try {
            $result = $this->getClient()->postfinder($params)->get();
        } catch (Zend_Rest_Client_Result_Exception $e) {
            $response = $this->client->getHttpClient()->getLastResponse();
            $helper->log($this->client->getHttpClient()->getLastRequest());
            $helper->log(sprintf(
                "%s\nHTTP/%s %s %s",
                $e->getMessage(),
                $response->getVersion(),
                $response->getStatus(),
                $response->getMessage()
            ));
            throw new Dhl_Account_Exception($helper->__(
                'An error occured while retrieving the packstation data.'
            ));
        }
        // TODO(nr): transform web service response to usable output.
        return $result;
    }
}
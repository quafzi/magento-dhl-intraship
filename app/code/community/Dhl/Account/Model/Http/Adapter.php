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
 * DHL Packstation webservice HTTP adapter
 *
 * @category    Dhl
 * @package     Dhl_Account
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */

class Dhl_Account_Model_Http_Adapter
    extends Dhl_Account_Model_Webservice_Adapter_Abstract
    implements Dhl_Account_Model_Webservice_Adapter_Interface
{
    const URI_PATH_ADDRESSVALIDATION = 'addressvalidation';
    const URI_PATH_POSTFINDER        = 'postfinder';

    /**
     * Web service client
     * @var Zend_Http_Client
     */
    protected $client = null;

    public function __construct()
    {
        $this->setClient(new Zend_Http_Client());
    }

    /**
     * @param Zend_Http_Client $client
     * @return Dhl_Account_Model_Rest_Adapter
     */
    public function setClient($client)
    {
        $client->setHeaders('Accept-encoding', 'application/xml');
        return parent::setClient($client);
    }

    /**
     * Set URI to REST client
     *
     * @param string $uri
     * @return Dhl_Account_Model_Http_Adapter
     * @throws Dhl_Account_Exception
     */
    public function setUri($uri)
    {
        parent::setUri($uri);

        try {
            $this->getClient()->setUri($this->uri);
        } catch (Zend_Http_Client_Exception $e) {
            throw new Dhl_Account_Exception($e->getMessage(), $e->getCode());
        }

        return $this;
    }

    /**
     * Set credentials for HTTP Auth Basic
     *
     * @param string $username
     * @param string $password
     * @return Dhl_Account_Model_Http_Adapter
     * @throws Dhl_Account_Exception
     */
    public function setAuth($username, $password)
    {
        parent::setAuth($username, $password);

        try {
            $this->getClient()->setAuth($this->username, $this->password);
        } catch (Zend_Http_Client_Exception $e) {
            throw new Dhl_Account_Exception($e->getMessage(), $e->getCode());
        }

        return $this;
    }

    /**
     * Append webservice method to URI.
     *
     * @param string $path
     * @return Dhl_Account_Model_Http_Adapter
     * @throws Dhl_Account_Exception
     */
    protected function setUriPath($path)
    {
        $uri = rtrim($this->getUri(), '/');
        $this->setUri(sprintf("%s/%s", $uri, $path));

        return $this;
    }

    /**
     * Call corresponding web service method and return the results.
     *
     * @param array $params
     * @return null
     */
    public function validateAddress(array $params)
    {
        /* @var $helper Dhl_Account_Helper_Data */
        $helper = Mage::helper('dhlaccount/data');

        $params = $this->getDefaultParams() + $params;
        $this->getClient()->setParameterGet($params);
        $this->setUriPath(self::URI_PATH_ADDRESSVALIDATION);

        // Not implemented yet.
        return null;
    }

    /**
     * Call corresponding web service method and return the results.
     *
     * @param array $params
     * @return array The parsed packstation data
     * @throws Dhl_Account_Exception
     */
    public function findPackstations(array $params)
    {
        /* @var $helper Dhl_Account_Helper_Data */
        $helper = Mage::helper('dhlaccount/data');
        $params = $this->getDefaultParams() + $params;

        $errorMsg = 'An error occured while retrieving the packstation data.';

        try {
            $this->getClient()->setParameterGet($params);
            $this->setUriPath(self::URI_PATH_POSTFINDER);
            /* @var $response Zend_Http_Response */
            $response = $this->getClient()->request(Zend_Http_Client::GET);
        } catch (Zend_Http_Client_Exception $e) {
            $response = $this->getClient()->getLastResponse();
            $helper->log($this->getClient()->getLastRequest());
            if ($response instanceof Zend_Http_Response) {
                $helper->log(sprintf(
                    "%s\nHTTP/%s %s %s",
                    $e->getMessage(),
                    $response->getVersion(),
                    $response->getStatus(),
                    $response->getMessage()
                ));
            }
            throw new Dhl_Account_Exception($errorMsg);
        }

        if (!$response->isSuccessful()) {
            $helper->log(sprintf(
                "%s\n%s\nHTTP/%s %s %s",
                $errorMsg,
                $response->getBody(),
                $response->getVersion(),
                $response->getStatus(),
                $response->getMessage()
            ));
            throw new Dhl_Account_Exception($errorMsg);
        }

        $automats = $this->parsePackstationFinderResponse($response->getBody());
        return $automats;
    }

}

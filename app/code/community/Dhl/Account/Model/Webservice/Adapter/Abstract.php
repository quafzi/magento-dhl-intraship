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
 * DHL Account generic webservice adapter base
 *
 * @category    Dhl
 * @package     Dhl_Account
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
abstract class Dhl_Account_Model_Webservice_Adapter_Abstract
    implements Dhl_Account_Model_Webservice_Adapter_Interface
{
    /**
     * The webservice client
     * @var object
     */
    protected $client;

    /**
     * The webservice endpoint
     * @var string
     */
    protected $uri;

    /**
     * HTTP Auth Basic username
     * @var string
     */
    protected $username;

    /**
     * HTTP Auth Basic password
     * @var string
     */
    protected $password;

    /**
     * Set the webservice client
     *
     * @param object $client
     * @return Dhl_Account_Model_Webservice_Adapter_Abstract
     */
    public function setClient($client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Retrieve the currently set webservice client
     *
     * @return object
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * (non-PHPdoc)
     * @see Dhl_Account_Model_Webservice_Adapter_Interface::setUri()
     * @return Dhl_Account_Model_Webservice_Adapter_Abstract
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Account_Model_Webservice_Adapter_Interface::getUri()
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * (non-PHPdoc)
     * @see Dhl_Account_Model_Webservice_Adapter_Interface::setAuth()
     * @return Dhl_Account_Model_Webservice_Adapter_Abstract
     */
    public function setAuth($username, $password)
    {
        $this->username = $username;
        $this->password = $password;

        return $this;
    }

    /**
     * Obtain default params used for webservice request.
     *
     * @return array
     */
    protected function getDefaultParams()
    {
        return array(
            'PARTNER_ID'  => 'DHLDS',
            'standorttyp' => 'packstation_paketbox',
            'pmtype'      => '1',
            'lang'        => 'de',
        );
    }

    /**
     * Extract relevant data from webservice response.
     *
     * @param string $xml
     * @return null
     */
    protected function parseAddressValidationResponse($xml)
    {
        // Not implemented yet.
        return null;
    }

    /**
     * Return the session info provided by the webservice on error.
     *
     * @param SimpleXMLElement $sessionElm
     * @param string $defaultInfo Default info message if none was provided by webservice
     * @return string First info content if available, default info otherwise
     */
    protected function parseResponseSessionInfo(SimpleXMLElement $sessionElm, $defaultInfo = '')
    {
        if ($sessionElm->infos && $sessionElm->infos->info) {
            return trim(current($sessionElm->infos->info));
        }
        return $defaultInfo;
    }

    /**
     * Extract relevant data from webservice response.
     *
     * @param string $xml
     * @return array
     * @throws Dhl_Account_Exception
     */
    protected function parsePackstationFinderResponse($xml)
    {
        $element = new SimpleXMLElement($xml, LIBXML_NOCDATA);

        // invalid xml exception
        if (!$element) {
            throw new Dhl_Account_Exception('An error occured while parsing the packstation response.');
        }

        // empty xml exception, optionally with error info
        if (!$element->data->children()->count()) {
            $errorMessage = $this->parseResponseSessionInfo($element->session, 'No data transmitted');
            throw new Dhl_Account_Exception($errorMessage);
        }

        // no or too many packstations found exception, optionally with error info
        if (!$element->data->result->automats || !$element->data->result->automats->children()->count()) {
            $errorMessage = $this->parseResponseSessionInfo($element->session, 'Address is ambigious');
            throw new Dhl_Account_Exception($errorMessage);
        }

        $automats = array();
        foreach ($element->data->result->automats->children() as $automat) {
            $distance = trim(current($automat->location->distance));

            $measure = new Zend_Measure_Length(
                $distance,
                Zend_Measure_Length::METER,
                Mage::app()->getLocale()->getLocale()
            );
            if ($distance > 1000) {
                $measure->convertTo(Zend_Measure_Length::KILOMETER);
            }

            $automats[]= array(
                'packstationnumber' => substr(trim(current($automat['objectId'])),-3),
                'city'              => trim($automat->address->city),
                'zip'               => trim($automat->address->zip),
                'street'            => trim($automat->address->street),
                'streetno'          => trim($automat->address->streetno),
                'errors'            => '',
                'distance'          => $measure->toString(),
            );
        }

        return $automats;
    }
}

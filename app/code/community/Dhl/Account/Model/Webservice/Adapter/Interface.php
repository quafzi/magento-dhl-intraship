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
 * DHL Account generic webservice client interface
 *
 * @category    Dhl
 * @package     Dhl_Account
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
interface Dhl_Account_Model_Webservice_Adapter_Interface
{
    /**
     * Set the webservice client
     *
     * @param object $client
     */
    public function setClient($client);

    /**
     * Retrieve the currently set webservice client
     */
    public function getClient();
    /**
     * Set the endpoint URI
     *
     * @param string $uri
     */
    public function setUri($uri);

    /**
     * Retrieve the currently set endpoint URI
     */
    public function getUri();

    /**
     * Set the credentials for HTTP Auth Basic
     *
     * @param string $username
     * @param string $password
     */
    public function setAuth($username, $password);

    /**
     * Perform address validation request and obtain parsed results.
     *
     * @param array $params
     */
    public function validateAddress(array $params);

    /**
     * Perform packstation finder request and obtain parsed results.
     *
     * @param array $params
     */
    public function findPackstations(array $params);
}

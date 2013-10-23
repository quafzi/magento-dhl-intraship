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
 * DHL OnlineRetoure soap client WSSE authentication header class
 *
 * @category    Dhl
 * @package     Dhl_OnlineRetoure
 * @author      André Herrn <andre.herrn@netresearch.de>
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Dhl_OnlineRetoure_Model_Soap_Header_Auth extends SoapHeader
{
    public function __construct($username, $password)
    {
        $wsseNs = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';

        $auth = new stdClass();
        $auth->Username = new SOAPVar($username, XSD_STRING, null, null, null, $wsseNs);
        $auth->Password = new SOAPVar($password, XSD_STRING, null, null, null, $wsseNs);

        $token = new stdClass();
        $token->UsernameToken = new SoapVar($auth, SOAP_ENC_OBJECT, null, null, null, $wsseNs);

        parent::__construct($wsseNs, 'Security', $token, true);
    }
}
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
 * DHL OnlineRetoure Config Model Test
 *
 * @category    Dhl
 * @package     Dhl_OnlineRetoure
 * @author      André Herrn <andre.herrn@netresearch.de>
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Dhl_OnlineRetoure_Test_Model_ConfigTest extends EcomDev_PHPUnit_Test_Case
{

    /**
     * @var Dhl_OnlineRetoure_Model_Config
     */
    protected $config;

    public function setUp()
    {
        $this->store  = Mage::app()->getStore(0)->load(0);
        $this->config = Mage::getModel('dhlonlineretoure/config');
        parent::setUp();
    }

    public function testIsEnabled()
    {
        $this->store->setConfig('intraship/dhlonlineretoure/active', true);
        $this->assertTrue($this->config->isEnabled());

        $this->store->resetConfig();
        $this->store->setConfig('intraship/dhlonlineretoure/active', false);
        $this->assertFalse($this->config->isEnabled());
    }

    public function testIsLoggingEnabled()
    {
        $this->store->setConfig('intraship/dhlonlineretoure/logging_enabled', true);
        $this->assertTrue($this->config->isLoggingEnabled());

        $this->store->resetConfig();
        $this->store->setConfig('intraship/dhlonlineretoure/logging_enabled', false);
        $this->assertFalse($this->config->isLoggingEnabled());
    }

    public function testGetPortalId()
    {
        $portalId = '12345';
        $this->store->setConfig('intraship/dhlonlineretoure/portal_id', $portalId);
        $this->assertEquals($portalId, $this->config->getPortalId());
    }

    public function testGetUser()
    {
        $user = 'username';
        $this->store->setConfig('intraship/dhlonlineretoure/user', $user);
        $this->assertEquals($user, $this->config->getUser());
    }

    public function testGetPassword()
    {
        $pass = 'password';
        $this->store->setConfig('intraship/dhlonlineretoure/password', $pass);
        $this->assertEquals($pass, $this->config->getPassword());
    }

    public function testGetCmsRevocationPage()
    {
        $page = 'revocation';
        $this->store->setConfig('intraship/dhlonlineretoure/cms_revocation_page', $page);
        $this->assertEquals($page, $this->config->getCmsRevocationPage());
    }

    public function testGetDeliveryNameByCountry()
    {
        $isoDeUc        = 'DE';
        $isoNlUc        = 'NL';
        $isoDeu         = 'DEU';
        $isoDeLc        = strtolower($isoDeUc);
        $isoNlLc        = strtolower($isoNlUc);
        $deliveryNameDe = 'deliveryDE';
        $deliveryNameNl = 'deliveryNL';

        $data = array(
            array('iso'  => $isoDeUc, 'name' => $deliveryNameDe),
            array('iso'  => $isoNlUc, 'name' => $deliveryNameNl),
        );

        $this->store->setConfig('intraship/dhlonlineretoure/delivery_names', serialize($data));
        $this->assertEquals($deliveryNameDe, $this->config->getDeliveryNameByCountry($isoDeLc));
        $this->assertEquals($deliveryNameDe, $this->config->getDeliveryNameByCountry($isoDeUc));
        $this->assertNotEquals($deliveryNameDe, $this->config->getDeliveryNameByCountry($isoNlLc));

        $this->setExpectedException('Exception');
        $this->config->getDeliveryNameByCountry($isoDeu);
    }

    public function testGetWsdlUri()
    {
        $wsdl = 'https://amsel.dpwn.net/abholportal/gw/lp/schema/1.0/var3ws.wsdl';
        $this->store->setConfig('intraship/dhlonlineretoure/wsdl', $wsdl);
        $this->assertEquals($wsdl, $this->config->getWsdlUri());
    }

    public function testGetAllowedCountryCodes()
    {
        $epnCountryCodes = 'DE';
        $bpiCountryCodes = 'BE,BG,DK,EE,FI,FR,GR,IE,IT,LV,LT,LU,MT,NL,AT,PL,PT,RO,SE,SK,SI,ES,CZ,HU,DB,CY,GB';
        $countryCodes = explode(',', "$epnCountryCodes,$bpiCountryCodes");

        $this->store->setConfig('intraship/epn/countryCodes', $epnCountryCodes);
        $this->store->setConfig('intraship/bpi/countryCodes', $bpiCountryCodes);

        $this->assertEquals($countryCodes, $this->config->getAllowedCountryCodes());
    }

    public function testGetAllowedShippingMethods()
    {
        $mockedShippingMethods = "intraship_intraship,flatrate_flatrate";
        $this->store->setConfig('intraship/dhlonlineretoure/allowed_shipping_methods', $mockedShippingMethods);

        $this->assertEquals(
            explode(",", $mockedShippingMethods),
            $this->config->getAllowedShippingMethods()
        );
        $this->assertNotNull($this->config->getAllowedShippingMethods());
    }

    public function testIsAllowedShippingMethod()
    {
        $mockedShippingMethods = "intraship_intraship";
        $this->store->setConfig('intraship/dhlonlineretoure/allowed_shipping_methods', $mockedShippingMethods);

        $this->assertTrue($this->config->isAllowedShippingMethod("intraship_intraship"));
        $this->assertFalse($this->config->isAllowedShippingMethod("flatrate_flatrate"));
    }
}

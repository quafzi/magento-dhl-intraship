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
 * DHL OnlineRetoure Deliverynames Backend Model Test
 *
 * @category    Dhl
 * @package     Dhl_OnlineRetoure
 * @author      André Herrn <andre.herrn@netresearch.de>
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Dhl_OnlineRetoure_Test_Model_System_Config_Backend_DeliverynamesTest extends EcomDev_PHPUnit_Test_Case
{
    public function testUniqueCountry()
    {
        $reflectionClass = new ReflectionClass('Dhl_OnlineRetoure_Model_System_Config_Backend_Deliverynames');
        $method = $reflectionClass->getMethod("_beforeSave");
        $method->setAccessible(true);


        /* @var $backendModel Dhl_OnlineRetoure_Model_System_Config_Backend_Deliverynames */
        $backendModel = Mage::getModel('dhlonlineretoure/system_config_backend_deliverynames');

        $deliveryIso = 'DE';
        $deliveryName = 'DeliveryDE';

        // invoke with unique iso, all fine
        $data = array(
            array('iso'  => $deliveryIso, 'name' => $deliveryName),
        );
        $backendModel->setValue($data);
        $method->invoke($backendModel);

        // invoke with duplicate iso, exception
        $data[]= array('iso'  => $deliveryIso, 'name' => $deliveryName);
        $backendModel->setValue($data);
        $this->setExpectedException(
          'Mage_Core_Exception', 'Country in area Online Return must not be defined twice.'
        );
        $method->invoke($backendModel);
    }

    public function testAllowedCountry()
    {
        $store = Mage::app()->getStore(0)->load(0);
        $store->setConfig('intraship/epn/countryCodes', 'DE');
        $store->setConfig('intraship/bpi/countryCodes', 'FR,NL,AT,PL,HU,GB');

        $reflectionClass = new ReflectionClass('Dhl_OnlineRetoure_Model_System_Config_Backend_Deliverynames');
        $method = $reflectionClass->getMethod("_beforeSave");
        $method->setAccessible(true);


        /* @var $backendModel Dhl_OnlineRetoure_Model_System_Config_Backend_Deliverynames */
        $backendModel = Mage::getModel('dhlonlineretoure/system_config_backend_deliverynames');

        $deliveryIsoDE  = 'DE';
        $deliveryNameDE = 'DeliveryDE';
        $deliveryIsoCH  = 'CH';
        $deliveryNameCH = 'DeliveryCH';


        // invoke with valid country, all fine
        $data = array(
            array('iso'  => $deliveryIsoDE, 'name' => $deliveryNameDE),
        );
        $backendModel->setValue($data);
        $method->invoke($backendModel);

        // invoke with invalid country, exception!
        $data[]= array('iso'  => $deliveryIsoCH, 'name' => $deliveryNameCH);
        $backendModel->setValue($data);
        $this->setExpectedException(
          'Mage_Core_Exception',
          sprintf('Selected country "%s" is not applicable for online return.', $deliveryIsoCH)
        );
        $method->invoke($backendModel);
    }
}

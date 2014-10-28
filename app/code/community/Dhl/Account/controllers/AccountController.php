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
 * DHL Account controller for XHR handling
 *
 * @category    Dhl
 * @package     Dhl_Account
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Dhl_Account_AccountController extends Mage_Core_Controller_Front_Action
{
    /**
     * Disable layout for all controller actions
     * @see Mage_Core_Controller_Varien_Action::_construct()
     */
    public function _construct()
    {
        parent::_construct();

        $this->loadLayout(false);
        $this->renderLayout();
    }

    /**
     * Allow ony XML HTTP requests on this controller. 404 otherwise.
     * @see Mage_Core_Controller_Front_Action::preDispatch()
     */
    public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->getResponse()
                ->setHeader('HTTP/1.1','404 Not Found')
                ->setHeader('Status','404 File not found');

            $pageId = Mage::getStoreConfig('web/default/cms_no_route');
            if (!Mage::helper('cms/page')->renderPage($this, $pageId)) {
                $this->_forward('defaultNoRoute');
            }
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
        }

        return $this;
    }

    public function countrycodeAction()
    {
        $addressId = $this->getRequest()->getQuery('address_id');
        $address = Mage::getModel('customer/address')->load($addressId);
        /* @var $address Mage_Customer_Model_Address */
        return $this->getResponse()->setBody($address->getCountryModel()->getIso2Code());
    }

    public function packstationdataAction()
    {
        $zipCode = $this->getRequest()->getPost('zipcode', '');
        $city = $this->getRequest()->getPost('city', '');

        /* @var $helper Dhl_Account_Helper_Data */
        $helper = Mage::helper('dhlaccount/data');
        if (!$zipCode && !$city) {
            $message = 'Please provide city or zip code in order to perform packstation request.';
            $responseBody = $helper->buildPackstationError($message);
            $this->getResponse()->setBody($responseBody);
            return $this;
        }

        /* @var $config Dhl_Account_Model_Config_Packstation */
        $config = Mage::getModel('dhlaccount/config_packstation');
        /* @var $client Dhl_Account_Model_Http_Adapter */
        $client = Mage::getModel('dhlaccount/http_adapter');

        try {
            $client
                ->setUri($config->getWebserviceEndpoint())
                ->setAuth(
                    $config->getWebserviceAuthUsername(),
                    $config->getWebserviceAuthPassword()
                );
            $automats = $client->findPackstations(array(
                'zip'  => $zipCode,
                'city' => $city,
            ));
        } catch (Dhl_Account_Exception $e) {
            $responseBody = $helper->buildPackstationError($e->getMessage());
            $this->getResponse()->setBody($responseBody);
            return $this;
        }

        $this->getResponse()->setBody($helper->buildPackstationSuccess($automats));
        return $this;
    }
}

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
 * DHL OnlineRetoure shipping address confirmation form
 *
 * @category    Dhl
 * @package     Dhl_OnlineRetoure
 * @author      André Herrn <andre.herrn@netresearch.de>
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Dhl_OnlineRetoure_Block_Customer_Address_Edit extends Mage_Directory_Block_Data
{
    protected $_address;

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->_address = $this->getOrder()->getShippingAddress();
        if (!$this->_address->getId()) {
            $this->_address->setPrefix($this->getCustomer()->getPrefix())
                ->setFirstname($this->getCustomer()->getFirstname())
                ->setMiddlename($this->getCustomer()->getMiddlename())
                ->setLastname($this->getCustomer()->getLastname())
                ->setSuffix($this->getCustomer()->getSuffix());
        }

        if ($postedData = Mage::getSingleton('customer/session')->getAddressFormData(true)) {
            $this->_address->addData($postedData);
        }
    }

    /**
     * Generate name block html
     *
     * @return string
     */
    public function getNameBlockHtml()
    {
        $nameBlock = $this->getLayout()
            ->createBlock('customer/widget_name')
            ->setObject($this->getAddress());

        return $nameBlock->toHtml();
    }

    public function getBackUrl()
    {
        return $this->getUrl('sales/order/view', array('order_id' => $this->getOrder()->getId()));
    }

    public function getSaveUrl()
    {
        /* @var $helper Dhl_OnlineRetoure_Helper_Validate */
        $helper = $this->helper('dhlonlineretoure/validate');
        $params = $helper->getUrlParams($this->getOrder()->getId(), $this->getRequestHash());
        return $this->getUrl('dhlonlineretoure/address/formPost', $params);
    }

    public function getCustomer()
    {
        /* @var $helper Dhl_OnlineRetoure_Helper_Data */
        $helper = $this->helper('dhlonlineretoure/data');
        return $helper->getLoggedInCustomer();
    }

    public function getCountryId()
    {
        if ($countryId = $this->getAddress()->getCountryId()) {
            return $countryId;
        }
        return parent::getCountryId();
    }

    public function getRegionId()
    {
        return $this->getAddress()->getRegionId();
    }

    public function getAddress()
    {
        return $this->_address;
    }

    public function getRevocationPageUrl()
    {
        /* @var $config Dhl_OnlineRetoure_Model_Config */
        $config = Mage::getModel('dhlonlineretoure/config');
        $urlKey = $config->getCmsRevocationPage();
        if (!$urlKey) {
            return '';
        }

        return Mage::getUrl($urlKey);
    }

    /**
     * Obtain current hash that must be set on external requests
     * @return string
     */
    public function getRequestHash()
    {
        return $this->getRequest()->getQuery('hash', '');
    }

    /**
     * @return Mage_Sales_Model_Order|null
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    /**
     * Get Page Title
     *
     * @return string
     */
    public function getTitle()
    {
        return Mage::helper('dhlonlineretoure/data')->__(
            "Check shipping address for DHL Online Return"
        );
    }
}

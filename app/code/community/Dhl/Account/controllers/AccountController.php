<?php

class Dhl_Account_AccountController extends Mage_Core_Controller_Front_Action
{
    public function countrycodeAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();

        $addressId = $this->getRequest()->getQuery('address_id');
        $address = Mage::getModel('customer/address')->load($addressId);
        /* @var $address Mage_Customer_Model_Address */
        return $this->getResponse()->setBody($address->getCountryModel()->getIso2Code());
    }

    public function packstationdataAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
        $result = array();
        if ($this->getRequest()->isPost()) {
            $zipCode = $this->getRequest()->getPost('zipcode');
            $city = $this->getRequest()->getPost('city');
            $client = Mage::getModel('dhlaccount/client_http');
            $client->setUri(Mage::getModel('dhlaccount/config')->getPackstationFinderUrl());
            $result = $client->getPackstationData($zipCode, $city);
        }
        return $this->getResponse()->setBody(Zend_Json::encode($result));
    }

}

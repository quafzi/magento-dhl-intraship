<?php
/* @var $installer Mage_Customer_Model_Entity_Setup */
$installer = $this;
$installer->startSetup();
/* @var $eavConfig Mage_Eav_Model_Config */
$eavConfig = Mage::getSingleton('eav/config');
$attribute = $eavConfig->getAttribute('customer_address', 'ship_to_packstation');
$attribute->setData('used_in_forms', array(
        'customer_address_edit',
        'customer_register_address'));
$attribute->save();

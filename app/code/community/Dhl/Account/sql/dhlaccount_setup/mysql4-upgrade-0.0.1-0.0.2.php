
<?php
/* @var $installer Mage_Customer_Model_Entity_Setup */
$installer = $this;
$installer->startSetup();

/* @var $addressHelper Mage_Customer_Helper_Address */
$addressHelper = Mage::helper('customer/address');

$attributes = array(
    'ship_to_packstation' => array(
        'backend_type'    => 'int',
        'backend_model'   => '',
        'backend_table'   => '',
        'frontend_model'  => '',
        'frontend_input'  => 'text',
        'frontend_label'  => 'ship to packstation',
        'frontend_class'  => '',
        'source_model'    => '',
        'default_value'   => '',
        'is_user_defined' => 1,
        'is_system'       => 0,
        'is_visible'      => 1,
        'sort_order'      => 150,
        'is_required'     => 0,
        'multiline_count' => 0,
    ),
);

/* @var $eavConfig Mage_Eav_Model_Config */
$eavConfig = Mage::getSingleton('eav/config');
foreach ($attributes as $attributeCode => $data) {
    $attribute = $eavConfig->getAttribute('customer_address', $attributeCode);
    $attribute->addData($data);
    $usedInForms = array(
        'adminhtml_customer_address',
        'customer_address_edit',
        'customer_register_address'
    );
    $attribute->setData('used_in_forms', $usedInForms);
    $attribute->save();
}

$installer->run("
    ALTER TABLE {$this->getTable('sales_flat_quote_address')} ADD COLUMN `ship_to_packstation` INT DEFAULT NULL AFTER `fax`;
    ALTER TABLE {$this->getTable('sales_flat_order_address')} ADD COLUMN `ship_to_packstation` INT DEFAULT NULL AFTER `fax`;

");
$installer->endSetup();
<?php

$installer = $this;
$installer->startSetup();

$installer->run("
    ALTER TABLE {$this->getTable('sales_flat_quote_address')} ADD COLUMN `package_notification` TINYINT(1) DEFAULT 0 AFTER `dhlaccount`;
    ALTER TABLE {$this->getTable('sales_flat_order_address')} ADD COLUMN `package_notification` TINYINT(1) DEFAULT 0 AFTER `dhlaccount`;
");

$installer->endSetup();

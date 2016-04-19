<?php
/**
 * Database update script
 *
 * @category  Setup
 * @package   Dhl_Intraship
 * @author    Paul Siedler <paul.siedler@netresearch.de>
 * @copyright Copyright (c) 2015 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();

$connection = $installer->getConnection();

$fkName = $connection->getForeignKeyName(
    $installer->getTable('intraship_shipment'),
    'order_id',
    $installer->getTable('sales_flat_order'),
    'entity_id'
);

$connection->addForeignKey(
    $fkName,
    $installer->getTable('intraship_shipment'),
    'order_id',
    $installer->getTable('sales_flat_order'),
    'entity_id',
    $connection::FK_ACTION_CASCADE,
    $connection::FK_ACTION_CASCADE,
    true
);

$installer->endSetup();

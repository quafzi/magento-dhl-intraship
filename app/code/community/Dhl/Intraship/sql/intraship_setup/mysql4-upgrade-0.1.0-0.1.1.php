<?php
/**
 * Database update script
 *
 * @category  Setup
 * @package   Dhl_Intraship
 * @author    André Herrn <andre.herrn@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

$installer = $this;
$installer->startSetup();

$installer->run("
ALTER TABLE {$this->getTable('intraship_shipment')} ADD mode varchar(40) default NULL;
");

$installer->endSetup();
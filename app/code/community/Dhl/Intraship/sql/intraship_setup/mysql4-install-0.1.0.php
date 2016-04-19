<?php
/**
 * Database setup script
 *
 * @category  Setup
 * @package   Dhl_Intraship
 * @author    Stephan Hoyer <stephan.hoyer@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

$installer = $this;
$installer->startSetup();

$installer->run("
-- DROP TABLE IF EXISTS {$this->getTable('intraship_shipment')};

CREATE TABLE IF NOT EXISTS {$this->getTable('intraship_shipment')} (
  `id` int(10) unsigned NOT NULL auto_increment,
  `shipment_id` int(10) NOT NULL,
  `order_id` int(10) unsigned NOT NULL,
  `status` tinyint(4) default NULL,
  `client_status_code` varchar(20) NOT NULL default '',
  `client_status_message` text NOT NULL,
  `shipment_number` varchar(40) default NULL,
  `customer_address` varchar(1000) default NULL,
  `settings` varchar(1000) default NULL,
  `packages` varchar(1000) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `shipment_id` (`shipment_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Intraship Shipments';

-- DROP TABLE IF EXISTS {$this->getTable('intraship_document')};

CREATE TABLE {$this->getTable('intraship_document')} (
  `document_id` int(10) NOT NULL auto_increment,
  `shipment_id` int(10),
  `status` tinyint(4),
  `document_url` varchar(255) NOT NULL default '',
  `type` varchar(40) NOT NULL,
  `file_path` varchar(255),
  PRIMARY KEY  (`document_id`),
  KEY `FK_ORDER_SHIPMENT` (`shipment_id`),
  CONSTRAINT `FK_ORDER_SHIPMENT` FOREIGN KEY (`shipment_id`) REFERENCES {$this->getTable('intraship_shipment')} (`shipment_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Intraship Documents';

ALTER TABLE {$this->getTable('sales/order')} ADD `is_gogreen` TINYINT(1) UNSIGNED NOT NULL AFTER `store_id`;

REPLACE INTO {$this->getTable('core_config_data')} (scope, scope_id, path, value) VALUES ('default', 0, 'intraship/general/install-date', UTC_TIMESTAMP());
");

$installer->endSetup();

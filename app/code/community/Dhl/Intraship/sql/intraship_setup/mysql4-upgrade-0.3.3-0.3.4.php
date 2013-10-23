<?php
/**
 * Database update script
 *
 * @category  Setup
 * @package   Dhl_Intraship
 * @author    Christoph Assmann <christoph.assmann@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

$this->startSetup();

$this->_conn->addColumn($this->getTable('intraship_document'), 'printed', 'tinyint(1) default 0');

$this->endSetup();

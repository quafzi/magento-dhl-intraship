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
 * PHP version 5
 *
 * @category  Dhl
 * @package   Dhl_OnlineRetoure
 * @author    Christoph Aßmann <christoph.assmann@netresearch.de>
 * @copyright 2015 Netresearch GmbH & Co. KG
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.netresearch.de/
 */

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;

$adminVersion = Mage::getConfig()->getModuleConfig('Mage_Admin')->version;
if (version_compare($adminVersion, '1.6.1.1', '>')) {
    $table = $installer->getTable('admin/permission_block');
    $installer->getConnection()->insertIgnore($table, array(
        'block_name' => 'dhlonlineretoure/sales_order_email_retoure',
        'is_allowed' => 1
    ));
}

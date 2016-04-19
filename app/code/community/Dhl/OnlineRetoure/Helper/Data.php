<?php
/**
 * Dhl_OnlineRetoure_Helper_Data
 *
 * @package   Dhl_Account
 * @author    André Herrn <andre.herrn@netresearch.de>
 * @copyright Copyright (c) 2012 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_OnlineRetoure_Helper_Data extends Mage_Core_Helper_Data
{
    /**
     * Check if the current installation is older than CE 1.7 / EE 1.12
     *
     * @return boolean
     */
    public function isLegacyInstallation()
    {
        $customerVersion = Mage::getConfig()->getModuleConfig('Mage_Customer')->version;
        return version_compare($customerVersion, '1.6.2', '<');
    }

    /**
     * Check if customer is logged in currently
     *
     * @see Mage_Customer_Helper_Data::isLoggedIn()
     * @return boolean
     */
    public function isCustomerLoggedIn()
    {
        if (Mage::helper('customer/data')->isLoggedIn()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get currently logged in customer
     *
     * @return Mage_Customer_Model_Customer
     * @see Mage_Customer_Helper_Data::getCustomer()
     */
    public function getLoggedInCustomer()
    {
        return Mage::helper('customer/data')->getCustomer();
    }

    /**
     * Get DHL Retoure Config
     *
     * @return Dhl_OnlineRetoure_Model_Config
     */
    public function getConfig()
    {
        return Mage::getModel("dhlonlineretoure/config");
    }

    /**
     * Log to a separate log file
     *
     * @param string $message
     * @param int    $level
     * @param bool   $force
     * @return Dhl_OnlineRetoure_Helper_Data
     */
    public function log($message, $level=null, $force=false)
    {
        if (Mage::getStoreConfig('intraship/dhlonlineretoure/logging_enabled')) {
            $logfile = 'dhl_retoure.log';
            Mage::log($message, $level, $logfile, $force);
        }
        return $this;
    }
}
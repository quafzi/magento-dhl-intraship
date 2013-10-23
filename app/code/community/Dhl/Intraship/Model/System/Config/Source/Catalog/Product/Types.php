<?php
/**
 * Dhl_Intraship_Model_System_Config_Catalog_Product_Types
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @author    André Herrn <andre.herrn@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_System_Config_Source_Catalog_Product_Types
{
     /**
     * Returns array for backend options.
     *
     * @param  boolean  $all
     * @param  string   $countryId (ISO)
     * @return array    $return
     */
    public function toOptionArray($all = false, $countryId = null,$gridActionMode = false)
    {
        return Mage_Catalog_Model_Product_Type::getAllOptions();
    }
}
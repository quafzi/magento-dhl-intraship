<?php
/**
 * Dhl_Intraship_Model_Address
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @author    Thomas Kappel <thomas.kappel@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_Address extends ArrayObject
{
    /**
     * offsetGet 
     * 
     * @param string $key 
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        if ($this->offsetExists($key)) {
            return parent::offsetGet($key);
        }
    }
}
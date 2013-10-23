<?php
/**
 * Dhl_Intraship_Model_Session
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_Session extends Mage_Core_Model_Session_Abstract
{
    /**
     * Initialize intraship session namespace.
     */
    public function __construct()
    {
        $this->init('intraship');
    }
}
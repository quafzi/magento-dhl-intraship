<?php
/**
 * DHL_Intraship_Block_Adminhtml_Notifications
 *
 * @category  Dhl
 * @package   Dhl_Intraship
 * @author    Paul Siedler <paul.siedler@netresearch.de>
 * @copyright Copyright (c) 2013 Netresearch GmbH & Co. KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class DHL_Intraship_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Template
{
    /**
     * Retrieve Intraship SEPA notification.
     *
     * @return string Notification if data are missing, empty string otherwise.
     */
    public function getMessage()
    {
        $message = '';
        $bankData = Mage::getModel('intraship/config')->getAccountBankData();

        if ((trim($bankData->offsetGet('iban')) == '')
            || (trim($bankData->offsetGet('bic')) == '')
        ) {
            // Display a notification if neither BIC nor IBAN are given
            $message = "Due to SEPA agreement you are required to update your"
                . " bank account data for DHL cash on delivery shipments."
                . " BIC and IBAN are now required fields.";
        }

        return $message;
    }
}

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
 * @category    Dhl
 * @package     Dhl_OnlineRetoure
 * @copyright   Copyright (c) 2013 Netresearch GmbH & Co. KG (http://www.netresearch.de/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * DHL OnlineRetoure Delivery Name Backend Model
 *
 * @category    Dhl
 * @package     Dhl_OnlineRetoure
 * @author      André Herrn <andre.herrn@netresearch.de>
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Dhl_OnlineRetoure_Model_System_Config_Backend_Deliverynames
    extends Mage_Adminhtml_Model_System_Config_Backend_Serialized_Array
{
    protected function _beforeSave()
    {
        $value = $this->getValue();

        $allowedIsoCodes = Mage::getModel('dhlonlineretoure/config')->getAllowedCountryCodes();
        $configuredIsoCodes = array();

        if (is_array($value)) {
            foreach ($value as $key => $data) {
                if ($key === '__empty') {
                    continue;
                }

                if (!in_array($data['iso'], $allowedIsoCodes)) {
                    Mage::throwException(Mage::helper('dhlonlineretoure/data')->
                            __('Selected country "%s" is not applicable for online return.', $data['iso'])
                    );
                }

                if (in_array($data['iso'], $configuredIsoCodes)) {
                    Mage::throwException(Mage::helper('dhlonlineretoure/data')->
                            __('Country in area Online Return must not be defined twice.')
                    );
                }

                $configuredIsoCodes[]= $data['iso'];
            }
        }
        parent::_beforeSave();
    }
}
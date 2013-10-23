<?php
/**
 * Dhl_Intraship_Block_Adminhtml_System_Config_Heading
 *
 * @desc      This class is copied from Magento 1.4.x.x.
 * @see	      Mage_Adminhtml_Block_System_Config_Form_Field_Heading
 *	
 * @category  Blocks
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Block_Adminhtml_System_Config_Heading
    extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * Render element html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $useContainerId = $element->getData('use_container_id');
        return sprintf('<tr class="system-fieldset-sub-head" id="row_%s"><td colspan="5"><h4>%s</h4></td></tr>',
            $element->getHtmlId(), $element->getLabel()
        );
    }
}

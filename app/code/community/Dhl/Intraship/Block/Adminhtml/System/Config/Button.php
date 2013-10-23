<?php
/**
 * Dhl_Intraship_Block_Adminhtml_System_Config_Button
 *
 * @category Blocks
 * @package Dhl_Intraship
 * @author Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Block_Adminhtml_System_Config_Button
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Set template to itself
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()):
            $this->setTemplate('intraship/system/config/button.phtml');
        endif;
        return $this;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(array(
            'button_label' => Mage::helper('intraship')->__($originalData['label']),
            'button_url'   => Mage::getModel('intraship/config')->getBackendUrl(
                Mage::helper('intraship')->getAdminStoreId()
            ),
            'html_id'      => $element->getHtmlId(),
            'account_type' => false !== strpos($element->getId(), 'test') ? 'test' : 'prod'
        ));
        return $this->_toHtml();
    }
}
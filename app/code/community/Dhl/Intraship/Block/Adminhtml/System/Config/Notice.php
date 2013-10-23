<?php
/**
 * Dhl_Intraship_Block_Adminhtml_System_Config_Notice
 *
 * @category Blocks
 * @package Dhl_Intraship
 * @author Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Block_Adminhtml_System_Config_Notice extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * Custom template
     *
     * @var string
     */
    protected $_template = 'intraship/system/config/notice.phtml';

    /**
     * Render fieldset html
     *
     * @param Varien_Data_Form_Element_Abstract $fieldset
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $fieldset)
    {
        foreach ($fieldset->getSortedElements() as $element):
            $htmlId = $element->getHtmlId();
            $this->_elements[$htmlId] = $element;
        endforeach;
        $originalData = $fieldset->getOriginalData();
        $this->addData(array(
            'fieldset_label'                 => $fieldset->getLegend(),
            'fieldset_admin_label'           => isset($originalData['admin_label']) ? $originalData['admin_label'] : '',
            'fieldset_backend_url'           => Mage::getModel('intraship/config')->getBackendUrl(),
            'fieldset_help_url'              => isset($originalData['help_url']) ? $originalData['help_url'] : '',
            'fieldset_doc_url'               => isset($originalData['doc_url']) ? $originalData['doc_url'] : '',
            'fieldset_onlineretoure_doc_url' => isset($originalData['onlineretoure_doc_url']) ? $originalData['onlineretoure_doc_url'] : '',
            'fieldset_partner_url'           => isset($originalData['partner_url']) ? $originalData['partner_url'] : ''
        ));
        return $this->toHtml();
    }
}
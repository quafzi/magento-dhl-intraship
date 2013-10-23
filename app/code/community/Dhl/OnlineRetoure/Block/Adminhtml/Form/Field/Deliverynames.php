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
 * DHL OnlineRetoure delivery names combined form field (frontend model)
 *
 * @category    Dhl
 * @package     Dhl_OnlineRetoure
 * @author      André Herrn <andre.herrn@netresearch.de>
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Dhl_OnlineRetoure_Block_Adminhtml_Form_Field_Deliverynames
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    /**
     * @var Mage_Core_Block_Html_Select
     */
    protected $_templateRenderer;


    /**
     * Create renderer used for displaying the country select element
     *
     * @return Mage_Core_Block_Html_Select
     */
    protected function _getTemplateRenderer()
    {
        if (!$this->_templateRenderer) {
            $this->_templateRenderer = $this->getLayout()->createBlock(
                'dhlonlineretoure/adminhtml_form_field_country_select',
                '',
                array('is_render_to_js_template' => true)
            );

            /* @var $sourceModel Mage_Adminhtml_Model_System_Config_Source_Country */
            $sourceModel = Mage::getModel('adminhtml/system_config_source_country');
            $this->_templateRenderer->setOptions($sourceModel->toOptionArray());
        }

        return $this->_templateRenderer;
    }

    /**
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract::_prepareArrayRow()
     */
    protected function _prepareArrayRow(Varien_Object $row)
    {
        $row->setData(
            'option_extra_attr_' . $this->_getTemplateRenderer()->calcOptionHash($row->getData('iso')),
            'selected="selected"'
        );

        return parent::_prepareArrayRow($row);
    }

    /**
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract::_prepareToRender()
     */
    protected function _prepareToRender()
    {
        $this->addColumn('iso', array(
            'label' => Mage::helper('dhlonlineretoure')->__('Country'),
            'renderer' => $this->_getTemplateRenderer()
        ));
        $this->addColumn('name', array(
            'label' => Mage::helper('dhlonlineretoure')->__('Delivery Name'),
            'style' => 'width:100px',
        ));
        // hide "Add after" button
        $this->_addAfter = false;

        return parent::_prepareToRender();
    }
}
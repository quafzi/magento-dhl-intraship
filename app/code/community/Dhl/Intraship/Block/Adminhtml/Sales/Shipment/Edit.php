<?php
/**
 * Dhl_Intraship_Block_Adminhtml_Sales_Shipment_Edit
 *
 * @category  Block
 * @package   Dhl_Intraship
 * @author    Stephan Hoyer <stephan.hoyer@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Block_Adminhtml_Sales_Shipment_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId   = 'shipment_id';
        $this->_controller = 'shipment';
        parent::__construct();
        
        $shipment = Mage::getModel('intraship/shipment')->load(
            $this->getRequest()->getParam('id'),
            'shipment_id');
        
        $this->_blockGroup = 'intraship';		
        $this->_mode = 'edit';
        $this->_controller = 'adminhtml_sales_shipment';
        
        $this->_updateButton('save', 'label', Mage::helper('cms')->__('Save Shipment'));
        $this->_updateButton('back', 'onclick', 'setLocation(\''.
            $this->getUrl('adminhtml/sales_shipment/view', array(
                'shipment_id'=>Mage::registry('shipment')->getShipmentId())) .
            '\')');
        $this->_removeButton('delete');
        
        //If status is failed, add a button to save and resume the shipment (DHLIS-476)
        if ($shipment->getStatus() == Dhl_Intraship_Model_Shipment::STATUS_NEW_FAILED) {
            $this->_addButton('saveandresume', array(
                'label'     => Mage::helper('adminhtml')->__('Save Shipment and resume'),
                'onclick'   => '$(\'shipping_save_and_resume\').setValue(1);editForm.submit();',
                'class'     => 'save',
            ), 2);
        }
    }

    protected function _prepareLayout()
    {
        $block = $this->getLayout()->createBlock(
            'intraship/adminhtml_sales_shipment_edit_form');
        $this->setChild('form', $block);
        return parent::_prepareLayout();
    }

    /**
     * Get edit form container header text
     *
     * @return string
     */
    public function getHeaderText()
    {
        return Mage::helper('intraship')->__("Edit Shipment '%s'", $this->htmlEscape(Mage::registry('shipment')->getShipmentId()));
    }
}

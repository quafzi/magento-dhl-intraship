<?php

class Dhl_Intraship_Block_Adminhtml_Widget_Grid_Column_Renderer_Bool extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $result = '';
        $status = (int) $row->getData($this->getColumn()->getIndex());
        
        if ($status == Dhl_Intraship_Model_Shipment_Document::STATUS_PRINTED) {
            $result = '<img src="' . $this->getSkinUrl('images/dhl/icon_complete.png') . '" alt="' . Mage::helper('intraship')->__('Printed') . '" title="' . Mage::helper('intraship')->__('Printed') . '" />';
        } else if ($status == Dhl_Intraship_Model_Shipment_Document::STATUS_NOTPRINTED) {
            $result = '<img src="' . $this->getSkinUrl('images/dhl/icon_incomplete.png') . '" alt="' . Mage::helper('intraship')->__('Not printed') . '" title="' . Mage::helper('intraship')->__('Not printed') . '" />';
        }

        return $result;
    }
}
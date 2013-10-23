<?php

class Dhl_Intraship_Block_Adminhtml_Widget_Grid_Column_Filter_Bool extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Checkbox
{
    public function getCondition()
    {
        if ($this->getValue()) {
            // yes (1) / no (0)
            return $this->getValue();
        } else {
            // any ('')
            return array(
                array('eq'=>$this->getColumn()->getValue()),
                array('is'=>new Zend_Db_Expr('NULL'))
            );
        }
    }
}

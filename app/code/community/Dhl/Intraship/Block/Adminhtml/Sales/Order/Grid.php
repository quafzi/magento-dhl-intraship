<?php
/**
 * Dhl_Intraship_Block_Adminhtml_Sales_Order_Grid
 *
 * @category  Blocks
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Block_Adminhtml_Sales_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid
{
    /**
     * Constructor.
     *
     * @see   app/code/core/Mage/Adminhtml/Block/Sales/Order/Mage_Adminhtml_Block_Sales_Order_Grid#__construct()
     * return Dhl_Intraship_Block_Adminhtml_Sales_Order_Grid
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('widget/grid.phtml');
    }

    /**
     * Prepare layout.
     *
     * @see   app/code/core/Mage/Adminhtml/Block/Sales/Order/Mage_Adminhtml_Block_Sales_Order_Grid#_prepareLayout()
     * return Dhl_Intraship_Block_Adminhtml_Sales_Order_Grid
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        /* @var $config Dhl_Intraship_Model_Config */
        $config = Mage::getModel('intraship/config');
        if (true === $config->isEnabled() &&
            true === $config->displayAutocreateButton()
        ):
            $urlModel  = Mage::getModel('adminhtml/url');
            $target = $urlModel->getUrl('adminhtml/shipment/autocreate', array(
                '_current' => false));
            $label  = Mage::helper('intraship')->__(
                'Create DHL Intraship shipments');
            $block  = $this->getLayout()
                ->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'class'   => 'task autocreate',
                    'label'   => $label,
                    'onclick' => sprintf("window.location='%s'", $target)
                ));
            $this->setChild('intraship_autocreate_button', $block);
        endif;
        return $this;
    }

    /**
     * Modify real order id column.
     *
     * @see    app/code/core/Mage/Adminhtml/Block/Sales/Order/Mage_Adminhtml_Block_Sales_Order_Grid#_prepareColumns()
     * @return Dhl_Intraship_Block_Adminhtml_Sales_Order_Grid
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();
        $this->addColumn('real_order_id', array(
            'header'   => Mage::helper('sales')->__('Order #'),
            'width'    => '110px',
            'index'    => 'increment_id',
            'renderer' => 'intraship/adminhtml_sales_order_grid_renderer_icon',
        ));
        return $this;
    }

    /**
     * Return filter button with additional intraship autocreate button
     *
     * @see    app/code/core/Mage/Adminhtml/Block/Widget/Mage_Adminhtml_Block_Widget_Grid#getResetFilterButtonHtml()
     * @return string   HTML
     */
    public function getResetFilterButtonHtml()
    {
        /* @var $config Dhl_Intraship_Model_Config */
        $config = Mage::getModel('intraship/config');
        if (true === $config->isEnabled() &&
            true === $config->displayAutocreateButton()
        ):
            return sprintf('%s<br/><br/>%s',
                $this->getChildHtml('intraship_autocreate_button'),
                parent::getResetFilterButtonHtml());
        endif;
        return parent::getResetFilterButtonHtml();
    }

    /**
     * Prepare massaction.
     *
     * @see    app/code/core/Mage/Adminhtml/Block/Sales/Order/Mage_Adminhtml_Block_Sales_Order_Grid#_prepareMassaction()
     * @return Dhl_Intraship_Block_Adminhtml_Sales_Order_Grid
     */
    protected function _prepareMassaction()
    {
        parent::_prepareMassaction();
        if (true === Mage::getModel('intraship/config')->isEnabled()):
            $profiles = Mage::getModel('intraship/system_config_source_profile')
                ->toOptionArray(false, null,true);
            $yesno    = Mage::getModel('adminhtml/system_config_source_yesno')
                ->toOptionArray();
            $this->getMassactionBlock()->addItem('createshipment_order', array(
                'label'          => Mage::helper('intraship')->__('Create shipment(s)'),
                'url'            => $this->getUrl('adminhtml/shipment/mass'),
                'additional'     => array(
                    'profile' => array(
                         'name'      => 'profile',
                         'type'      => 'select',
                         'class'     => 'required-entry',
                         'label'     => Mage::helper('intraship')->__('Profile'),
                         'values'    => $profiles
                ),
                'insurance' => array(
                         'name'      => 'insurance',
                         'type'      => 'select',
                         'class'     => 'required-entry',
                         'label'     => Mage::helper('intraship')->__('Insurance'),
                         'values'    => $yesno
                ),
                'bulkfreight' => array(
                         'name'      => 'bulkfreight',
                         'type'      => 'select',
                         'class'     => 'required-entry',
                         'label'     => Mage::helper('intraship')->__('Bulkfreight'),
                         'values'    => $yesno
                 ))
            ));
        endif;
        return $this;
    }

    /**
     * Add javascripts on bottom of grid.
     *
     * @see    app/code/core/Mage/Adminhtml/Block/Mage_Adminhtml_Block_Template#_toHtml()
     * @return string
     */
    public function _toHtml()
    {
        if (true !== Mage::getModel('intraship/config')->isEnabled()):
            return parent::_toHtml();
        endif;
        $js = $this->getLayout()->createBlock(
            'intraship/adminhtml_sales_order_grid_style', 'intraship_form', array(
                'template' => 'intraship/sales/order/grid/style.phtml'
        ));
        return parent::_toHtml() . $js->_toHtml();
    }
}

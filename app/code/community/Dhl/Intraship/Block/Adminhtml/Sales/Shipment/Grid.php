<?php
/**
 * Dhl_Intraship_Block_Adminhtml_Sales_Shipment_Grid
 *
 * @category  Block
 * @package   Dhl_Intraship
 * @author    Stephan Hoyer <stephan.hoyer@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Block_Adminhtml_Sales_Shipment_Grid
    extends Mage_Adminhtml_Block_Sales_Shipment_Grid
{
    protected function _prepareMassaction()
    {
        parent::_prepareMassaction();
        $this->getMassactionBlock()->addItem('dhl_intraship_downloadPdf', array(
             'label'=> Mage::helper('sales')->__('Download Intraship PDF'),
             'url'  => $this->getUrl('adminhtml/shipment/massPdf'),
        ));

        return $this;
    }

    /**
     * Adds join to collection select with intraship table
     * to get additional intraship collumns to display in grid.
     *
     * It was not possible to you to simply call parent method on any place,
     * because the method had to be extended in the middle. So we rewrote the
     * complete method by including all parent an grandparent code in this method.
     *
     * @return Dhl_Intraship_Block_Adminhtml_Sales_Shipment_Grid    $this
     */
    protected function _prepareCollection()
    {
        /*
         * for Magento 1.4.x.x
         */
        if (true === Mage::getModel('intraship/config')->isVersionRecommendedOrLarger()):
            // BEGIN: Code from parent class
            $collection = Mage::getResourceModel($this->_getCollectionClass());
            // END: Code from parent class

            // BEGIN: customized code
            $collection->getSelect()->joinLeft(
                array('intraship' => $collection->getTable('intraship/shipment')),
                'entity_id=intraship.shipment_id');
            // END: customized code
        /*
         * for Magento 1.3.x.x
         */
        else:
            $collection = Mage::getResourceModel('sales/order_shipment_collection')
                ->addAttributeToSelect('increment_id')
                ->addAttributeToSelect('created_at')
                ->addAttributeToSelect('total_qty')
                ->joinAttribute('shipping_firstname', 'order_address/firstname', 'shipping_address_id', null, 'left')
                ->joinAttribute('shipping_lastname', 'order_address/lastname', 'shipping_address_id', null, 'left')
                ->joinAttribute('order_increment_id', 'order/increment_id', 'order_id', null, 'left')
                ->joinAttribute('order_created_at', 'order/created_at', 'order_id', null, 'left');
            $collection->getSelect()->joinLeft(
                array('intraship' => $collection->getTable('intraship/shipment')),
                'e.entity_id=intraship.shipment_id');
        endif;


        // BEGIN: Code from parent class
        $this->setCollection($collection);
        // END: Code from parent class
        /*
         * Code from grandparent-Class:
         *  Mage_Adminhtml_Block_Widget_Grid::_prepareCollection()
         */
        if ($this->getCollection()):
            $this->_preparePage();

            $columnId = $this->getParam($this->getVarNameSort(),
                $this->_defaultSort);
            $dir      = $this->getParam($this->getVarNameDir(),
                $this->_defaultDir);
            $filter   = $this->getParam($this->getVarNameFilter(), null);

            if (is_null($filter)):
                $filter = $this->_defaultFilter;
            endif;

            if (is_string($filter)):
                $data = $this->helper('adminhtml')->prepareFilterString($filter);
                $this->_setFilterValues($data);
            elseif ($filter && is_array($filter)):
                $this->_setFilterValues($filter);
            elseif (0 !== sizeof($this->_defaultFilter)):
                $this->_setFilterValues($this->_defaultFilter);
            endif;

            if (isset($this->_columns[$columnId]) &&
                $this->_columns[$columnId]->getIndex()
            ):
                $dir = (strtolower($dir)=='desc') ? 'desc' : 'asc';
                $this->_columns[$columnId]->setDir($dir);
                $column = $this->_columns[$columnId]->getFilterIndex() ?
                    $this->_columns[$columnId]->getFilterIndex() :
                    $this->_columns[$columnId]->getIndex();
                $this->getCollection()->setOrder($column , $dir);
            endif;

            if (!$this->_isExport):
                $this->getCollection()->load();
                $this->_afterLoadCollection();
            endif;
        endif;
        return $this;
        // END: Code from Mage_Adminhtml_Block_Widget_Grid::_prepareCollection()
    }

    /**
     * Adds two more columns to shipment grid view (status + download pdf)
     *
     * @return Dhl_Intraship_Block_Adminhtml_Sales_Shipment_Grid    $this
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();
        $this->addColumn('intraship_status', array(
            'header'    => Mage::helper('intraship')->__('Intraship Status'),
            'index'     => 'status',
            'type'      => 'options',
            'options'   => Mage::getSingleton('intraship/shipment')->getStatuses(),
            'filter'    => version_compare(Mage::getVersion(), '1.4.2.0', '>=') ? null : false,
            'sortable'  => version_compare(Mage::getVersion(), '1.4.2.0', '>='),
            'is_system' => false,
        ));

        $this->addColumn('label',
        array(
            'header'    => Mage::helper('intraship')->__('PDF'),
            'width'     => '50px',
            'type'      => 'action',
            'getter'     => 'getShipmentId',
            'actions'   => array(
                array(
                    'caption' => Mage::helper('sales')->__('PDF'),
                    'url'     => array('base'=>'adminhtml/shipment/pdf'),
                    'field'   => 'id',
                    'popup'   => true,
                )
            ),
            'filter'    => false,
            'sortable'  => false,
            'is_system' => false,
            'renderer'  => 'intraship/adminhtml_sales_shipment_grid_renderer_action'
        ));
        return $this;
    }
}

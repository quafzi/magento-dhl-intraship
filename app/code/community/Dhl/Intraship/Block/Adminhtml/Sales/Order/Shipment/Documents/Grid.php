<?php
/**
 * Dhl_Intraship_Block_Adminhtml_Sales_Order_Shipment_Documents_Grid
 *
 * @category  Block
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Block_Adminhtml_Sales_Order_Shipment_Documents_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('intrashipDocumentsGrid');
        $this->setDefaultSort('document_id');
        $this->setDefaultDir('DESC');
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('document_id');
        $this->getMassactionBlock()->setFormFieldName('document_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);

        $this->getMassactionBlock()->addItem('dhl_intraship_downloadPdf', array(
             'label'=> Mage::helper('sales')->__('Download Intraship PDF'),
             'url'  => $this->getUrl('*/shipment/massPdf'),
        ));
        
        $this->getMassactionBlock()->addItem('set_printed', array(
            'label' => Mage::helper('intraship')->__('Mark documents as printed'),
        	'url' => $this->getUrl('*/*/massMarkPrinted')
        ));

        $this->getMassactionBlock()->addItem('set_notprinted', array(
            'label' => Mage::helper('intraship')->__('Mark documents as not printed'),
        	'url' => $this->getUrl('*/*/massMarkNotPrinted')
        ));
        
        return $this;
    }
    
    protected function _prepareCollection()
    {
        $status     = Dhl_Intraship_Model_Shipment_Document::STATUS_DOWNLOADED;
        $collection = Mage::getModel('intraship/shipment_document')
            ->getCollection()
            ->addFieldToFilter('main_table.status', $status);
            
        
        if (true === Mage::getModel('intraship/config')->isVersionRecommendedOrLarger()) {
            /*
             * for Magento 1.4.x.x
             */
            $collection->getSelect()->reset(Zend_Db_Select::COLUMNS)
            ->columns(
                array(
                    'document_id',
                    'shipment_id',
                    'type',
                    'printed'
                )
            )->joinLeft(
                array(
                    'shipment' => $collection->getTable('sales/shipment_grid')
                ),
                'main_table.shipment_id=shipment.entity_id',
                array(
                    'shipment_increment_id' => 'increment_id',
                    'shipping_date'         => 'created_at'
                )
            )->joinLeft(
                array(
                    'order' => $collection->getTable('sales/order_grid')
                ),
                'shipment.order_id=order.entity_id',
                array(
                    'order_id'           => 'entity_id',
                    'order_increment_id' => 'increment_id',
                    'shipping_name'      => 'shipping_name',
                    'order_date'         => 'created_at'
                )
            );
        }
            
        /* @var $collection Dhl_Intraship_Model_Mysql4_Shipment_Document_Collection */
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('document_id', array(
            'header'    => Mage::helper('intraship')->__('Document Id'),
            'align'     => 'left',
            'index'     => 'document_id',
        ));
        if (true === Mage::getModel('intraship/config')->isVersionRecommendedOrLarger()) {
            $this->addColumn('order', array(
                'header'    => Mage::helper('intraship')->__('Order'),
                'align'     => 'left',
                'index'     => 'order_increment_id',
                'renderer'  => 'intraship/adminhtml_sales_order_shipment_documents_grid_renderer_order'
            ));
            $this->addColumn('order_date', array(
                'header'    => Mage::helper('sales')->__('Order Date'),
                'index'     => 'order_date',
                'type'      => 'datetime',
                'filter'    => false,
            ));
            $this->addColumn('shipping_name', array(
                'header' => Mage::helper('sales')->__('Ship to Name'),
                'index' => 'shipping_name',
                'filter'    => false,
            ));
            $this->addColumn('shipment', array(
                'header'    => Mage::helper('sales')->__('Shipments'),
                'align'     => 'left',
                'filter'    => false,
                'sortable'  => false,
                'renderer'  => 'intraship/adminhtml_sales_order_shipment_documents_grid_renderer_shipment',
            ));
            $this->addColumn('shipping_date', array(
                'header'    => Mage::helper('sales')->__('Date Shipped'),
                'index'     => 'shipping_date',
                'filter'    => false,
                'type'      => 'datetime',
            ));
        } else {
            $this->addColumn('shipment_id', array(
                'header'    => Mage::helper('intraship')->__('Shipment Id'),
                'align'     => 'left',
                'index'     => 'shipment_id',
                'renderer'  => 'intraship/adminhtml_sales_order_shipment_documents_grid_renderer_id',
            ));
        }
        $this->addColumn('document_url', array(
            'header'    => Mage::helper('intraship')->__('Document URL'),
            'width'     => 300,
            'sortable'  => false,
            'filter'    => false,
            'renderer'  => 'intraship/adminhtml_sales_order_shipment_documents_grid_renderer_url',
        ));
        $this->addColumn('type', array(
            'header'    => Mage::helper('intraship')->__('Document Type'),
            'index'     => 'type',
            'type'      => 'options',
            'options'   => array(
                'label' => Mage::helper('intraship')->__('Label'),
                'declaration' => Mage::helper('intraship')->__('Customs declaration')
            )
        ));
        
        $this->addColumn('printed', array(
            'header'    => Mage::helper('intraship')->__('Printed'),
            'index'     => 'printed',
            'type'      => 'bool',
            'renderer'  => 'intraship/adminhtml_widget_grid_column_renderer_bool'
        ));
        
        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return null;
    }
    
    public function getColumnFilters()
    {
        return array(
            'bool' => 'intraship/adminhtml_widget_grid_column_filter_bool'
        );
    }
}

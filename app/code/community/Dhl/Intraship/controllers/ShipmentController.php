<?php
/**
 * Dhl_Intraship_SoapController
 *
 * @category  Controllers
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_ShipmentController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Action to load a merged document.
     *
     * @return void
     */
    public function pdfAction()
    {
        try {
            // Get requested shipment id.
            $shipmentId = $this->getRequest()->getParam('id');
            if (!$shipmentId):
                $this->_throwAdminException('Parameter "id" is required.');
            endif;
            /* @var $shipment Dhl_Intraship_Model_Shipment */
            $shipment = Mage::getModel('intraship/shipment')->load($shipmentId,
                'shipment_id');
            if (true === $shipment->isEmpty()):
                $this->_throwAdminException('Intraship Shipment does not exist.');
            endif;
            /* @see Dhl_Intraship_Model_Shipment */
            if (!array_key_exists((int) $shipment->getStatus(), array(
                Dhl_Intraship_Model_Shipment::STATUS_PROCESSED,
                Dhl_Intraship_Model_Shipment::STATUS_CANCELED,
                Dhl_Intraship_Model_Shipment::STATUS_CLOSED
            ))):
                $this->_throwAdminException(
                    'Intraship Shipment is in queue or failed.');
            endif;
            $status     = Dhl_Intraship_Model_Shipment_Document::STATUS_DOWNLOADED;
            /* @var $collection Dhl_Intraship_Model_Mysql4_Shipment_Document_Collection */
            $collection = Mage::getModel('intraship/shipment_document')
                ->getCollection()
                ->addFieldToFilter('shipment_id', $shipmentId)
                ->addFieldToFilter('status', $status)
                ->addFieldToFilter('status', array('notnull' => true));

            Mage::dispatchEvent('intraship_shipment_document_print', array('collection' => $collection));

            /* @var $helper Dhl_Intraship_Helper_Pdf */
            $helper = Mage::helper('intraship/pdf');

            $this->_prepareDownloadResponse(
                sprintf('intraship-%s-pdfs-%s.pdf', date('Y-m-d'), $shipmentId),
                // Render merged pdfs if exists, otherwise recover first.
                $helper->setDocuments($collection)->recover($shipment)->merge()->retrieve(),
                'application/pdf'
            );

        } catch (Exception $e) {
            Mage::log($e->getMessage());
            $this->_throwAdminException('service temporary not available');
        }
    }

    public function massPdfAction()
    {
        try {
            $documentIds = $this->getRequest()->getParam('document_ids');
            $shipmentIds = $this->getRequest()->getParam('shipment_ids');
            $selectBy = 'document';
            if ((!is_array($documentIds) || 0 == count($documentIds))) {
                if (is_array($shipmentIds) && 0 < count($shipmentIds)) {
                    $selectBy = 'shipment';
                } else {
                    $this->_throwAdminException('Please select some documents!');
                }
            }

            /* @var $helper Dhl_Intraship_Helper_Pdf */
            $helper   = Mage::helper('intraship/pdf');
            $shipment = Mage::getModel('intraship/shipment');
            $status   = Dhl_Intraship_Model_Shipment_Document::STATUS_DOWNLOADED;

            $startTime = time();
            $documentOffset=0;

            $collection = Mage::getModel('intraship/shipment_document')
                ->getCollection()
                ->addFieldToFilter('status', $status)
                ->addFieldToFilter('status', array('notnull' => true));
            if ('document' == $selectBy) {
                $collection->addFieldToFilter('document_id', array('in' => $documentIds));
            } else {
                $collection->addFieldToFilter('shipment_id', array('in' => $shipmentIds));
            }

            // recover documents
            foreach ($collection as $document) {
                ++$documentOffset;
                $helper->recoverDocument($document, $document->getShipment());

                /* 10s before running into the timeout we break and show an error message */
                if ($documentOffset < count($collection)
                    && ini_get('max_execution_time') - 10 < time() - $startTime
                ) {
                    Mage::getSingleton('adminhtml/session')->addError(
                        Mage::helper('intraship')->__(
                            'You selected more shipments than your server could '
                            . 'process in one run. Please start the process again.'
                        )
                    );
                    $this->_redirectReferer();
                }
            }

            Mage::dispatchEvent('intraship_shipment_document_print', array('collection' => $collection));

            $this->_prepareDownloadResponse(
                sprintf('intraship_labels_%s.pdf', Mage::getSingleton('core/date')->date('Y-m-d_H-i-s')),
                $helper->setDocuments($collection)->merge()->retrieve(),
                'application/pdf'
            );

        } catch (Exception $e) {
            Mage::log($e->getMessage());
            $this->_throwAdminException($e->getMessage());
        }
    }

    /**
     * Action to open single pdf document.
     *
     * @return void
     */
    public function documentAction()
    {
        try {
            // Get requested document id.
            $documentId = $this->getRequest()->getParam('id');
            if (!$documentId):
                $this->_throwAdminException('Document Id is required.');
            endif;
            $document = Mage::getModel('intraship/shipment_document')
                ->load($documentId);
            if (true === $document->isEmpty()):
                $this->_throwAdminException('Document does not exist.');
            endif;
            /* @var $shipment Dhl_Intraship_Model_Shipment */
            $shipment = Mage::getModel('intraship/shipment')->load(
                $document->getShipmentId(), 'shipment_id');
            if (true === $shipment->isEmpty()):
                throw new Exception('Shipment not exists.');
            endif;
            // Recover document if not exists.
            Mage::helper('intraship/pdf')->recoverDocument($document, $shipment);

            $collection = Mage::getModel('intraship/shipment_document')
                ->getCollection()
                ->addFieldToFilter('document_id', $documentId);
            Mage::dispatchEvent('intraship_shipment_document_print', array('collection' => $collection));

            // Return pdf.
            $this->_prepareDownloadResponse(
                sprintf("intraship-document-%d.pdf", $documentId),
                array('type' => 'filename', 'value' => $document->getFilePath()),
                'application/pdf'
            );
        } catch (Exception $e) {
            Mage::log($e->getMessage());
            $this->_throwAdminException($e->getMessage());
        }
    }

    /**
     * Action to open list of pdf documents.
     *
     * @see    Dhl_Intraship_Block_Adminhtml_Sales_Order_Shipment_Documents_Grid
     * @return void
     */
    public function documentsAction()
    {
        $this->loadLayout();

        /**
         * Set active menu item
         */
        $this->_setActiveMenu('sales/shipment/intraship_shipment_documents');

        $this->renderLayout();
    }

    /**
     * Action for autocreate button on "sales/order_grid".
     *
     * @return void
     */
    public function autocreateAction()
    {
        if (true === Mage::getModel('intraship/config')->isEnabled()):
            try {
                // Execture orders and return collection count.
                /* @var $model Dhl_Intraship_Model_Autocreate */
                $model  = Mage::getModel('intraship/autocreate')->execute();
                $orders = $model->getProcessedOrders();
                $count  = sizeof($orders);
                // Write translated success message to session.
                if ((int) $count > 0):
                    $message = Mage::helper('intraship')->__(
                        '%s DHL Intraship shipments successful created. <br/><br/> Order(s): %s',
                        $count, implode(', ', $orders));
                else:
                    $message = Mage::helper('intraship')->__('No orders to ship.');
                endif;
                Mage::getSingleton('adminhtml/session')->addSuccess($message);
            } catch (Dhl_Intraship_Model_Autocreate_Exception $e) {
                // Write translated error message to session.
                $message = Mage::helper('intraship')->__($e->getMessage());
                Mage::getSingleton('adminhtml/session')->addError($message);
            }
        endif;
        $this->_redirectReferer();
    }

    /**
     * Action to mass action.
     *
     * @return void
     */
    public function massAction()
    {
        // Get requested parameters.
        $request  = $this->getRequest();
        $settings = new ArrayObject(array());
        $settings->offsetSet('profile',         $request->getParam('profile'));
        $settings->offsetSet('insurance',     $request->getParam('insurance'));
        $settings->offsetSet('personally',                                  0);
        $settings->offsetSet('bulkfreight', $request->getParam('bulkfreight'));
        // Get order ids
        $orderIds = $request->getParam('order_ids');
        if (!is_array($orderIds) || sizeof($orderIds) <= 0):
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('intraship')->__('No order was selected!'));
        else:
            // Setup messages vars.
            $success = new ArrayObject(array());
            $failed  = new ArrayObject(array());
            // Start shipment creation for each order id.
            foreach ($orderIds as $orderId):
                /* @var $order Mage_Sales_Model_Order */
                $order = Mage::getModel('sales/order')->load($orderId);

                //Don't send orders with disabled shipping method
                if (false === Mage::getModel('intraship/config')
                        ->isAllowedShippingMethod($order->getShippingMethod())):
                    continue;
                endif;

                // DHLIS-313: Do not handle orders to be sent outside the EU
                $countryId = $order->getShippingAddress()->getCountryId();
                if (true === Mage::getModel('intraship/config')->isInternationalShipping($countryId)):
                    continue;
                endif;

                /* @var $model Dhl_Intraship_Model_Autocreate */
                $model = Mage::getModel('intraship/autocreate');
                try {
                    // Start shipment creation.
                    $model->process($order, $settings);
                    $processedOrders = $model->getProcessedOrders();
                    if (true === (bool) sizeof($processedOrders)):
                        $processedOrderId = array_pop($processedOrders);
                        if ($order->getRealOrderId() == $processedOrderId):
                            $success->offsetSet($order->getRealOrderId(),
                                Mage::helper('intraship')->__('was successful created.'));
                        else:
                            $failed->offsetSet($order->getRealOrderId(),
                                Mage::helper('intraship')->__('Cannot do shipment for the order.'));
                        endif;
                    else:
                        $failed->offsetSet($order->getRealOrderId(),
                            Mage::helper('intraship')->__('Cannot do shipment for the order.'));
                    endif;
                } catch (Dhl_Intraship_Model_Autocreate_Exception $e) {
                    // Add comment to order if exception appears.
                    $model->addCommentToOrder($order, $e->getMessage());
                    $failed->offsetSet($order->getRealOrderId(),
                        Mage::helper('intraship')->__($e->getMessage()));
                }
            endforeach;
            $labelOrderNo = Mage::helper('intraship')->__('Order No.');
            // Write messages to session.
            foreach ($success as $orderId => $message):
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    sprintf('%s %s %s', $labelOrderNo, $orderId, $message));
            endforeach;
            foreach ($failed as $orderId => $message):
                Mage::getSingleton('adminhtml/session')->addError(
                    sprintf('%s %s %s', $labelOrderNo, $orderId, $message));
            endforeach;
        endif;
        $this->_redirectReferer();
    }

    /**
     * Action to mark multiple documents as 'printed'
     */
    public function massMarkPrintedAction()
    {
        $documentIds = $this->getRequest()->getParam('document_ids');

        if (!is_array($documentIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('intraship')->__('Please select document(s)'));
        } else {
            try {
                /* @var $document Dhl_Intraship_Model_Shipment_Document */
                $document = Mage::getModel('intraship/shipment_document');

                foreach ($documentIds as $documentId) {
                    $document->load($documentId);
                    $document->setPrinted(true)->save();
                }

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('intraship')->__(
                    	'Total of %d document(s) were successfully marked as \'printed\'', count($documentIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirectReferer();
    }

    /**
     * Action to mark multiple documents as 'printed'
     */
    public function massMarkNotPrintedAction()
    {
        $documentIds = $this->getRequest()->getParam('document_ids');

        if (!is_array($documentIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('intraship')->__('Please select document(s)'));
        } else {
            try {
                /* @var $document Dhl_Intraship_Model_Shipment_Document */
                $document = Mage::getModel('intraship/shipment_document');

                foreach ($documentIds as $documentId) {
                    $document->load($documentId);
                    $document->setPrinted(false)->save();
                }

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('intraship')->__(
	    				'Total of %d document(s) were successfully marked as \'not printed\'', count($documentIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirectReferer();
    }

    /**
     * Action to cancel registered shipment.
     *
     * @return void
     */
    public function cancelAction()
    {
        try {
            $id = $this->getRequest()->getParam('id');
            $shipment = Mage::getModel('intraship/shipment')->load($id,
                'shipment_id');
            if (!$shipment->canCancel()):
                $this->_throwAdminException(
                    'Can\'t cancel shipment.', 'adminhtml/sales_shipment/view',
                    array('shipment_id' => $id));
                return;
            endif;
            $shipment->cancel();
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('intraship')->__('Cancel succeed.'));
            // Redirect
            $this->_redirect('adminhtml/sales_shipment/view', array(
                'shipment_id' => $id));
        } catch (Exception $e) {
            $this->_throwAdminException('Cancel of shipment failed.');
        }
   }

    /**
     * Action to cancel registered shipment.
     *
     * @return void
     */
    public function resumeAction()
    {
        try {
            $id = $this->getRequest()->getParam('id');
            $shipment = Mage::getModel('intraship/shipment')->load($id,
                'shipment_id');
            if (!$shipment->canResume()):
                $this->_throwAdminException(
                    'Can\'t resume shipment.', 'adminhtml/sales_shipment/view',
                    array('shipment_id' => $id));
                return;
            endif;
            $shipment->resume();
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('intraship')->__('Shipment resumed.'));
            // Redirect
            $this->_redirect('adminhtml/sales_shipment/view', array(
                'shipment_id' => $id));
        } catch (Exception $e) {
            $this->_throwAdminException('Resume of shipment failed.');
        }
   }

    /**
     * Action to edit shipment.
     *
     * @return void
     */
    public function editAction()
    {
        try {
            $id = $this->getRequest()->getParam('id');
            $shipment = Mage::getModel('intraship/shipment')->load($id,
                'shipment_id');
            if (!$shipment->canEdit()):
                $this->_throwAdminException(
                    'Can\'t edit shipment.', 'adminhtml/sales_shipment/view',
                    array('shipment_id' => $id));
                return;
            endif;
            Mage::register('shipment', $shipment);
            $this->loadLayout();
            $this->renderLayout();
        } catch (Exception $e) {
            $this->_throwAdminException('Can\'t edit shipment.');
        }
    }

    /**
     * Action to save edited shipment.
     *
     * @return void
     */
    public function saveAction()
    {
        try {
            $id       = $this->getRequest()->getParam('shipment_id');
            $shipment = Mage::getModel('intraship/shipment')->load($id,
                'shipment_id');
            if (!$shipment->canEdit()):
                $this->_throwAdminException(
                    'Can\'t edit shipment.', 'adminhtml/sales_shipment/view',
                    array('shipment_id' => $id));
                return;
            endif;

            $customer = $this->getRequest()->getParam('customer');
            if (!(($customer['street_name'] && $customer['street_number']) ||
                ($customer['id_number'] && $customer['station_id']))) {
                $this->_throwAdminException('Either customer address or PACKSTATION information must be set.');
                return;
            }

            if ($this->getRequest()->getParam('save_and_resume') === '1'
                && $shipment->getStatus() == Dhl_Intraship_Model_Shipment::STATUS_NEW_FAILED) {
                $shipment->setStatus(Dhl_Intraship_Model_Shipment::STATUS_NEW_QUEUED);
            }

            $shipment->addData($this->getRequest()->getParams());
            $shipment->save();
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('intraship')->__('Save succeed.'));
            $this->_redirect('adminhtml/sales_shipment/view',
                array('shipment_id' => $id));
            return;
        } catch (Exception $e) {
            $this->_throwAdminException('Can\'t edit shipment.');
        }
    }

    /**
     * Trigger queue action
     *
     * @return void
     */
    public function triggerQueueAction()
    {
        if (false == Mage::getModel('intraship/gateway')->processQueue(ini_get('max_execution_time'))) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('intraship')->__('We could not process all requested shipments. Please start the process again.')
            );
        } else {
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('intraship')->__('All waiting shipments were processed.')
            );
        }
        $this->_redirectReferer();
    }

    /**
     * Function adds a message to admin session to show to user and then
     * redirects to specified path.
     *
     * @param string $message
     * @param string $redirect
     * @param array  $params
     *
     * @return void
     */
    protected function _throwAdminException($message, $redirect = 'referer',
        $params = array()
    ) {
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('intraship')->__($message));
        // Redirect referer
        if ($redirect == 'referer'):
            $this->_redirectReferer();
            return;
        endif;
        // Redirect
        $this->_redirect($redirect, $params);
        return;
    }

    /**
     * Validate Secret Key
     *
     * @return bool
     */
    protected function _validateSecretKey()
    {
        return true;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')
            ->isAllowed('sales/shipment/intraship_shipment_documents');
    }
}

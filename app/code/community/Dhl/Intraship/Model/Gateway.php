<?php
/**
 * Dhl_Intraship_Model_Gateway
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_Gateway
{
    /**
     * @var Dhl_Intraship_Model_Config
     */
    protected $_config;

    /**
     * Proccess queue
     *
     * @param int $maxExecutionTime Maximum execution time available
     *
     * @return boolean If all requested items could be processed
     */
    public function processQueue($maxExecutionTime=INF)
    {
        try {
            /* @var $shipment Dhl_Intraship_Model_Mysql4_Shipment_Collection */
            $collection = Mage::getModel('intraship/shipment')
                ->getCollection()
                ->joinOrderTable();
            // Add filter status codes to where clause.
            $collection->addFieldToFilter('main_table.status', array('in' => array(
                Dhl_Intraship_Model_Shipment::STATUS_NEW_QUEUED,
                Dhl_Intraship_Model_Shipment::STATUS_NEW_RETRY,
                Dhl_Intraship_Model_Shipment::STATUS_CANCEL_QUEUED,
                Dhl_Intraship_Model_Shipment::STATUS_CANCEL_RETRY)));
            // Create shipment for each queue.
            if (0 < count($collection)) {
                $startTime = time();
                $shipmentOffset=0;
                /* @var $row Dhl_Intraship_Model_Shipment */
                foreach($collection as $shipment) {
                    ++$shipmentOffset;

                    //Skip shipment if current status has changed to avoid double shipment transmission
                    if (Mage::getModel('intraship/shipment')
                           ->load($shipment->getId())
                           ->getStatus() != $shipment->getStatus()) {
                        continue;
                    }

                    try {
                        $fallback = new ArrayObject(array());
                        if (!$shipment instanceof Dhl_Intraship_Model_Shipment) {
                            continue;
                        }
                        switch ($shipment->getStatus()) {
                            case Dhl_Intraship_Model_Shipment::STATUS_NEW_QUEUED:
                            case Dhl_Intraship_Model_Shipment::STATUS_NEW_RETRY:
                                $fallback->offsetSet('type', 'create');
                                $fallback->offsetSet('status',
                                    Dhl_Intraship_Model_Shipment::STATUS_NEW_FAILED);
                                $this->_create($shipment);
                                break;
                            case Dhl_Intraship_Model_Shipment::STATUS_CANCEL_QUEUED:
                            case Dhl_Intraship_Model_Shipment::STATUS_CANCEL_RETRY:
                                $fallback->offsetSet('type', 'cancel');
                                $fallback->offsetSet('status',
                                    Dhl_Intraship_Model_Shipment::STATUS_CANCELED_FAILED);
                                $this->_cancel($shipment);
                                break;
                        }
                    } catch (Exception $e) {
                        Mage::logException($e);
                        $this->logMessage($e->getMessage());
                        $shipment
                            ->setStatus($fallback->offsetGet('status'))
                            ->setClientStatusCode(1000)
                            ->setClientStatusMessage($e->getMessage())
                            ->addComment($e->getMessage(),
                                $fallback->offsetGet('status'),
                                $fallback->offsetGet('type')
                            );
                    }

                    if ($shipment->getShipment()->getId() && $shipment->getShipment()->getOrder()->getId()) {
                        // Save comments.
                        $shipment->saveComments();
                        // Save modified shipment.
                        $shipment->save();
                    }
                    // Stop processing 10s before we run out of time
                    if ($shipmentOffset < $collection->count()
                        && $maxExecutionTime - 10 < time() - $startTime
                    ) {
                        return false;
                    }
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
            throw new Dhl_Intraship_Model_Soap_Client_Response_Exception(
                $e->getMessage(), $e->getCode());
        }
        return true;
    }

    /**
     * Create new shipment on DHL.
     *
     * @param  Dhl_Intraship_Model_Shipment $shipment
     * @return Dhl_Intraship_Model_Shipment $shipment
     */
    protected function _create(Dhl_Intraship_Model_Shipment $shipment)
    {
        try {
            //Set Shipment to "in transmission" to avoid double shipment transmission
            $shipment->setAsInTransmission();

            /* @var $client Dhl_Intraship_Model_Soap_Client_Shipment */
            $client = Mage::getModel(
                'intraship/soap_client_shipment',
                $shipment->getShipment()->getOrder()->getStoreId()
            );
            /* @var $response Dhl_Intraship_Model_Soap_Client_Response */
            $response = $client->create($shipment);
            if (!$response instanceof Dhl_Intraship_Model_Soap_Client_Response):
               throw new Dhl_Intraship_Model_Soap_Client_Response_Exception('DHL Response is not valid');
            endif;
            $response->validate();

            /* @var $helper Dhl_Intraship_Helper_Pdf_Document */
            $helper = Mage::helper('intraship/pdf_document');
            $helper
                ->setPdfName($helper->getFileNameLabel($response->getShipmentNumber()))
                ->setPdfContent(file_get_contents($response->getLabelUrl()))
                ->savePdf();
            // Add status message to shipment.
            $shipment->addComment('PDF creation was successful', 0, 'pdf');
            // Update shipment.
            $shipment
                ->setShipmentNumber($response->getShipmentNumber())
                ->saveTrack()
                ->setStatus(Dhl_Intraship_Model_Shipment::STATUS_PROCESSED)
                ->setClientStatusCode($response->getStatusCode())
                ->setClientStatusMessage($response->getStatusMessage());
            // Add new shipment document.
            $document = Mage::getModel('intraship/shipment_document');
            $document
                ->setShipmentId($shipment->getShipmentId())
                ->setDocumentUrl($response->getLabelUrl())
                ->setFilePath($helper->getPathToPdf())
                ->setStatus(Dhl_Intraship_Model_Shipment_Document::STATUS_DOWNLOADED)
                ->setType(Dhl_Intraship_Model_Shipment_Document::TYPE_LABEL)
                ->save();
            // Notify customer.
            if ($this->getConfig()->isTrackingNotification()):
                $message = $this->getConfig()->getTrackingNotificationMessage(
                	$shipment->getShipment()->getOrder()->getStoreId()
                );
                $mageShipment = $shipment->getShipment();
                $mageShipment
                    ->addComment($message, true)
                    ->sendEmail(true, $message)
                    ->setEmailSent(true)
                    ->save();
            endif;
            // Add status message to shipment.
            $shipment->addComment(
                $shipment->getClientStatusMessage(),
                $shipment->getClientStatusCode(), 'create');
        } catch (Exception $e) {
            // Handle exceptions
            $code = Dhl_Intraship_Model_Shipment::STATUS_NEW_RETRY;
            if ($e instanceof Dhl_Intraship_Model_Soap_Client_Response_Exception):
                $code = $this->_handleException($e, $code,
                    Dhl_Intraship_Model_Shipment::STATUS_NEW_RETRY);
            endif;
            // Add status message to shipment.
            $shipment->addComment($e->getMessage(), $e->getCode(), 'create');
            // Update shipment codes.
            $shipment
                ->setStatus($code)
                ->setClientStatusCode($e->getCode())
                ->setClientStatusMessage($e->getMessage());
        }
    }

    /**
     * Cancel existing shipment on DHL.
     *
     * @param  Dhl_Intraship_Model_Shipment $shipment
     * @return void
     */
    protected function _cancel(Dhl_Intraship_Model_Shipment &$shipment)
    {
        try {
            //Set Shipment to "in transmission" to avoid double shipment transmission
            $shipment->setAsInTransmission();

            /* @var $client Dhl_Intraship_Model_Soap_Client_Shipment */
            $client = Mage::getModel(
                'intraship/soap_client_shipment',
                $shipment->getShipment()->getOrder()->getStoreId()
            );
            /* @var $response Dhl_Intraship_Model_Soap_Client_Response */
            $response = $client->delete($shipment)->validate();
            // Update shipment.
            $shipment
                ->removeTracks()
                ->removeDocuments()
                ->setStatus(Dhl_Intraship_Model_Shipment::STATUS_CANCELED)
                ->setClientStatusCode($response->getStatusCode())
                ->setClientStatusMessage($response->getStatusMessage());
            // Add status message to shipment.
            $shipment->addComment(
                $shipment->getClientStatusMessage(),
                $shipment->getClientStatusCode(), 'cancel');
        } catch (Dhl_Intraship_Model_Soap_Client_Response_Exception $e) {
            // Handle exceptions
            $code = Dhl_Intraship_Model_Shipment::STATUS_CANCEL_RETRY;
            if ($e instanceof Dhl_Intraship_Model_Soap_Client_Response_Exception):
                $code = $this->_handleException($e, $code,
                    Dhl_Intraship_Model_Shipment::STATUS_CANCELED_FAILED);
            endif;
            // Add status message to shipment.
            $shipment->addComment($e->getMessage(), $e->getCode(), 'cancel');
            // Update shipment codes.
            $shipment
                ->setStatus($code)
                ->setClientStatusCode($e->getCode())
                ->setClientStatusMessage($e->getMessage());
        }
    }

    /**
     * Handle exceptions
     *
     * @param  Exception    $e
     * @param  integer      $retry
     * @param  integer      $failed
     * @return integer
     */
    protected function _handleException(Exception $e, $retry, $failed)
    {
        $this->logMessage($e->getMessage());
        switch ((int) $e->getCode()):
            case 500:  // Service temporarily not available
            case 1000: // General error
            case 1001: // Login failed
            case 2110: // Invalid sender address
            case 2120: // Invalid receiver address
            case 2130: // Invalid account
                return $retry;
                break;
            default:
                return $failed;
        endswitch;
    }

    /**
     * get module configuration model
     *
     * @return Dhl_Intraship_Model_Config
     */
    protected function getConfig()
    {
        if (is_null($this->_config)) {
            $this->_config = Mage::getModel('intraship/config');
        }
        return $this->_config;
    }

    /*
     * write a message to the log (if this is enabled)
     *
     * @param string $message
     */
    protected function logMessage($message)
    {
        if (Mage::getStoreConfig('intraship/general/logging_enabled')) {
            Mage::log(
                $message,
                null,
                Mage::getModel('intraship/config')->getLogfile()
            );
        }
    }
}

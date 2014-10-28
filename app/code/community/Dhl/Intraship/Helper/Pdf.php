<?php
/**
 * Dhl_Intraship_Helper_Pdf
 *
 * @category  Helpers
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Helper_Pdf extends Mage_Core_Helper_Abstract
{
    /**
     * @var FPDI
     */
    protected $_document;

    /**
     * @var Dhl_Intraship_Model_Mysql4_Shipment_Document_Collection
     */
    protected $_collection;

    /**
     * Merge limit for documents
     *
     * @var integer
     */
    protected $_limit = 20;

    /**
     * Set document collection.
     *
     * @param  Dhl_Intraship_Model_Mysql4_Shipment_Document_Collection $collection
     * @return Dhl_Intraship_Helper_Pdf                                $this
     * @throws Dhl_Intraship_Helper_Pdf_Exception
     */
    public function setDocuments(Dhl_Intraship_Model_Mysql4_Shipment_Document_Collection $collection)
    {
        if (count($collection->getData()) > $this->_limit):
            throw new Dhl_Intraship_Helper_Pdf_Exception(
                sprintf(
                    'Maximum document limit of %s pages is exceeded. %s documents given.',
                    $this->_limit, count($collection->getData())
                )
            );
        endif;
        $this->_collection = $collection;
        return $this;
    }

    /**
     * Merge multiple documents.
     *
     * @return Dhl_Intraship_Helper_Pdf             $this
     * @throws Dhl_Intraship_Helper_Pdf_Exception
     */
    public function merge()
    {
        // Include required fpdi library.
        if (!class_exists('FPDF', false)) {
             require_once Mage::getBaseDir('base').DS.'lib'.DS.'fpdf'.DS.'fpdf.php';
        }
        if (!class_exists('FPDI', false)) {
                require_once Mage::getBaseDir('base').DS.'lib'.DS.'fpdf'.DS.'fpdi.php';
        }

        // Try to merge documents via FPDI.
        try {
            // Create new FPDI document
            $this->_document = new FPDI('P', 'mm', $this->getConfig()->getFormat());
            $this->_document->SetAutoPageBreak(false);
            $page = 1;
            foreach ($this->_collection as $document):
               $pages = $this->_document->setSourceFile($document->getFilePath());
               for ($i = 1; $i <= $pages; $i++):
                   $this->_document->addPage();
                   $this->_document->useTemplate(
                       $this->_document->importPage($i, '/MediaBox')
                   );
                   $page++;
                endfor;
            endforeach;
        } catch (Exception $e) {
            throw new Dhl_Intraship_Helper_Pdf_Exception(
                'pdf merging failed. service temporary not available',
                $e->getCode()
            );
        }
        return $this;
    }

    /**
     * get config model
     *
     * @return Dhl_Intraship_Model_Config
     */
    public function getConfig()
    {
        return Mage::getModel('intraship/config');
    }

    /**
     * Recover documents if not exists.
     *
     * @param  Dhl_Intraship_Model_Shipment         $shipment
     * @return Dhl_Intraship_Helper_Pdf             $this
     * @throws Dhl_Intraship_Helper_Pdf_Exception
     */
    public function recover(Dhl_Intraship_Model_Shipment $shipment)
    {
        try {
            foreach ($this->_collection as $document):
                /* @var $document Dhl_Intraship_Model_Shipment_Document */
                $this->recoverDocument($document, $shipment);
            endforeach;
        } catch (Dhl_Intraship_Helper_Pdf_Exception $e) {
            // Add status message to shipment.
            $shipment->addComment($e->getMessage(), $e->getCode(), 'pdf')
                     ->saveComments();
            throw new $e;
        }
        return $this;
    }

    /**
     * Recover a document.
     *
     * @param  Dhl_Intraship_Model_Shipment_Document    $document
     * @param  Dhl_Intraship_Model_Shipment             $shipment
     * @return Dhl_Intraship_Helper_Pdf                 $this
     */
    public function recoverDocument(
        Dhl_Intraship_Model_Shipment_Document $document,
        Dhl_Intraship_Model_Shipment $shipment)
    {
        try {
            if (file_exists($document->getFilePath())):
                return $this;
            endif;
            /* @var $helper Dhl_Intraship_Helper_Pdf_Document */
            $helper = Mage::helper('intraship/pdf_document');
            $helper->setPdfName($helper->getFileNameLabel($shipment->getShipmentNumber()))
                   ->setPdfContent(file_get_contents($document->getDocumentUrl()))
                   ->savePdf();
            /*
             * Workaround - sometimes the given path to the pdf is not identic with the generated one
             * In this case, save the new generated path to the document
             */
            if ($document->getFilePath()!=$helper->getPathToPdf()):
                $document->setFilePath($helper->getPathToPdf());
                $document->save();
            endif;
        } catch (Exception $e) {
            Mage::log($e->getMessage());
            throw new Dhl_Intraship_Helper_Pdf_Exception(
                'pdf creation failed. service temporary not available. ' . $e->getMessage(),
                $e->getCode()
            );
        }
        return $this;
    }

    /**
     * Send merged PDF document to client.
     *
     * @deprecated 13.11.28 Direct output not recommended, use controller functionality instead.
     * @see Dhl_Intraship_Helper_Pdf::retrieve()
     * @see Mage_Core_Controller_Varien_Action::_prepareDownloadResponse()
     * @param  string $pdfOutputName
     * @return void
     */
    public function render($pdfOutputName='intraship')
    {
        // header('Content-type: application/pdf');
        // header('Content-Disposition: attachment; filename="' . $pdfOutputName . '.pdf"');
        $this->_document->Output($pdfOutputName, 'D');
    }

    /**
     * Retrieve current PDF buffer as string.
     *
     * @param string $pdfOutputName
     * @return string
     */
    public function retrieve($pdfOutputName = 'doc.pdf')
    {
        return $this->_document->Output($pdfOutputName, 'S');
    }
}

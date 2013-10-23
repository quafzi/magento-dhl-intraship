<?php
/**
 * Dhl_Intraship_Helper_Pdf_Document
 *
 * @category  Helpers
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Helper_Pdf_Document extends Mage_Core_Helper_Abstract
{
    /**
     * @var string
     */
    protected $_baseDir;

    /**
     * @var string
     */
    protected $_levelDir;

    /**
     * @var string
     */
    protected $_name = null;

    /**
     * @var string
     */
    protected $_content = null;

    /**
     * Define prefix for directories.
     *
     * @var string
     */
    const DIRECTORY_PREFIX = 'pdf--';

    /**
     * Constructor
     *
     * @see    Dhl_Intraship_Model_Config
     * @return Dhl_Intraship_Helper_Pdf_Document    $this
     */
    public function __construct()
    {
        /* @var $config Dhl_Intraship_Model_Config */
        $config = Mage::getModel('intraship/config');
        // Define default values form config
        $this->_baseDir  = $config->getPdfBaseDir();
        $this->_levelDir = $config->getPdfLevel();
    }

    /**
     * Save PDF to file system.
     *
     * @return Dhl_Intraship_Helper_Pdf_Document            $this
     * @throws Dhl_Intraship_Helper_Pdf_Document_Exception
     */
    public function savePdf()
    {
        $path = $this->_path($this->getPdfName());
        if (!is_writable($path)) {
            // Maybe, we just have to build the directory structure.
            $this->_recursiveMkdirAndChmod($this->getPdfName());
            if (!is_writable($path)) {
                throw new Dhl_Intraship_Helper_Pdf_Document_Exception(
                    sprintf('Directory %s is not writable.', $path)
                );
            }
        }

        // Save pdf to target directory.
        if (@!file_put_contents($this->getPathToPdf(), $this->getPdfContent())) {
            throw new Dhl_Intraship_Helper_Pdf_Document_Exception(
                sprintf('Unable to save PDF to %s.', $this->getPathToPdf())
            );
        }

        $this->resizePdf();

        return $this;
    }

    /**
     * resizePdf 
     * 
     * @return void
     */
    public function resizePdf()
    {
        try {

            if (false === class_exists('FPDF', false)) {
                require_once Mage::getBaseDir('base').DS.'lib'.DS.'fpdf'.DS.'fpdf.php';
            }
            if (false === class_exists('FPDI', false)) {
                require_once Mage::getBaseDir('base').DS.'lib'.DS.'fpdf'.DS.'fpdi.php';
            }

            // Create new FPDI document
            $document = new FPDI('P', 'mm', $this->getConfig()->getFormat());
            $margins = $this->getConfig()->getMargins();
            $document->SetAutoPageBreak(false);
            $pages = $document->setSourceFile($this->getPathToPdf());
            for ($i = 1; $i <= $pages; $i++) {
                $document->addPage();
                $document->useTemplate(
                    $document->importPage($i, '/MediaBox'),
                    $margins['left'],
                    $margins['top']
                );
            }
            $document->Output($this->getPathToPdf(), 'F');
        } catch (Exception $e) {
            throw new Dhl_Intraship_Helper_Pdf_Exception(
                'pdf resizing failed. service temporary not available. ' . $e->getMessage(),
                $e->getCode()
            );
        }
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
     * Get PDF name.
     *
     * @return string
     * @throws Dhl_Intraship_Helper_Pdf_Document_Exception
     */
    public function getPdfName()
    {
        if (null === $this->_name) {
            throw new Dhl_Intraship_Helper_Pdf_Document_Exception(
                'Please define the PDF name. See method setPdfName().'
            );
        }
        return $this->_name;
    }

    /**
     * Set PDF name.
     *
     * @param  string                               $name
     * @return Dhl_Intraship_Helper_Pdf_Document    $this
     */
    public function setPdfName($name)
    {
        $this->_name = $name;
        return $this;
    }

    /**
     * Get PDF content.
     *
     * @return string
     * @throws Dhl_Intraship_Helper_Pdf_Document_Exception
     */
    public function getPdfContent()
    {
        if (null === $this->_content) {
            throw new Dhl_Intraship_Helper_Pdf_Document_Exception(
                'Please define the PDF content. See method setPdfContent().'
            );
        }
        return $this->_content;
    }

    /**
     * Set PDF content.
     *
     * @param  string                               $content
     * @return Dhl_Intraship_Helper_Pdf_Document    $this
     */
    public function setPdfContent($content)
    {
        $this->_content = $content;
        return $this;
    }

    /**
     * Get the file name of the saving label.
     *
     * @param  integer  $shipmentNumber
     * @return sting
     */
    public function getFileNameLabel($shipmentNumber)
    {
        return sprintf('label-%s.pdf', $shipmentNumber);
    }

    /**
     * Get the file name of the saving customs declaration (Zollinhaltserklärung).
     *
     * @param  integer  $shipmentNumber
     * @return sting
     */
    public function getFileNameDeclaration($shipmentNumber)
    {
        return sprintf('declaration-%s.pdf', $shipmentNumber);
    }

    /**
     * Return the complete directory path with filename.
     *
     * @return string
     */
    public function getPathToPdf()
    {
        $name = $this->getPdfName();
        return $this->_path($name) . $name;
    }

    /**
     * Make the directory strucuture for the given file.
     *
     * @param  string                                       $file
     * @return Dhl_Intraship_Helper_Pdf_Document            $this
     * @throws Dhl_Intraship_Helper_Pdf_Document_Exception
     */
    protected function _recursiveMkdirAndChmod($file)
    {
        if ($this->_levelDir <= 0) {
            return true;
        }
        $parts = $this->_path($file, true);
        foreach ($parts as $part) {
            if (!(@is_dir($part) || @mkdir($part, 0777, true))) {
                throw new Dhl_Intraship_Helper_Pdf_Document_Exception(
                    sprintf('Unable to create directory %s.', $part)
                );
            }
            @chmod($part, 0777);
        }
        return $this;
    }

    /**
     * Return the complete directory path of a filename.
     *
     * @param  string  $file
     * @param  boolean $parts   If true, returns array of directory parts instead of single string
     * @return string           Complete directory path
     */
    protected function _path($file, $parts = false)
    {
        $array = array();
        $root  = $this->_baseDir;
        if ($this->_levelDir > 0):
            $hash = hash('sha256', $file);
            for ($i = 0; $i < $this->_levelDir; $i++):
                $root = $root . self::DIRECTORY_PREFIX .
                    substr($hash, 0, $i + 1) . DIRECTORY_SEPARATOR;
                $array[] = $root;
            endfor;
        endif;
        return (true === $parts) ? $array : $root;
    }
}

<?php
/**
 * Dhl_Intraship_Block_Checkout_Onepage_Shipping_Gogreen
 *
 * @category  Block
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Block_Checkout_Onepage_Shipping_Gogreen
    extends Mage_Core_Block_Template
{
    /**
     * Internal constructor, that is called from real constructor.
     *
     * @return Dhl_Intraship_Block_Checkout_Onepage_Shipping_Gogreen    $this
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('intraship/checkout/onepage/shipping/gogreen.phtml');
    }

    /**
     * Return TRUE if go green is already selected, otherwise return FALSE.
     *
     * @return boolean
     */
    public function isSelected()
    {
        /* @var $session Dhl_Intraship_Model_Session */
        $session = Mage::getSingleton('intraship/session');
        return (
            true === $session->hasData('is_gogreen') &&
            1 === (int) $session->getData('is_gogreen')
        );
    }

    /**
     * Get Checkout label
     *
     * @return string       HTML
     */
    public function getLabel()
    {
        return Mage::getModel('intraship/config')->getCheckoutGoGreenLabel();
    }
}
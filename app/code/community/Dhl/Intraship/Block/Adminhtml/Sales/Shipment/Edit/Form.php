<?php
/**
 * Dhl_Intraship_Block_Adminhtml_Sales_Shipment_Edit_Form
 *
 * @category  Block
 * @package   Dhl_Intraship
 * @author    Stephan Hoyer <stephan.hoyer@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Block_Adminhtml_Sales_Shipment_Edit_Form
    extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('shipment_form');
        $this->setTitle(Mage::helper('intraship')->__('Shipment Information'));
    }

    public function getOrder()
    {
        return Mage::registry('shipment')->getShipment()->getOrder();
    }

    protected function _prepareForm()
    {
        $parcel = Mage::registry('shipment');

        if ($parcel->getCustomerAddress()):
            foreach ($parcel->getCustomerAddress() as $key => $value):
                if ($key == 'street' && preg_match('/^(\d+)\s(packstation\s\d+)$/i', $value, $match)) {
                    $parcel->setData('customer_id_number', $match[1]);
                    $parcel->setData('customer_station_id', $match[2]);
                } elseif ($key == 'street') {
                    $splittedStreet = Mage::helper('intraship')->splitStreet($value);
                    foreach ($splittedStreet as $key => $value) {
                        $parcel->setData('customer_' . $key, $value);
                    }
                } else {
                    $parcel->setData('customer_' . $key, $value);
                }
            endforeach;
            foreach ($parcel->getSettingsAsObject() as $key => $value):
                $parcel->setData('settings_' . $key, $value);
            endforeach;
        endif;

        $form = new Varien_Data_Form(array(
            'id'     => 'edit_form',
            'action'    => $this->getUrl('*/shipment/save'),
            'method' => 'post'));

        $form->setHtmlIdPrefix('shipping_');
        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend' => Mage::helper('intraship')->__('General Information'),
            'class'  => 'fieldset-wide'));

        $fieldset->addField('shipment_id', 'hidden', array(
            'name' => 'shipment_id'));

        $fieldset->addField('save_and_resume', 'hidden', array(
            'name' => 'save_and_resume'));

        $fieldset->addField('settings_profile', 'select', array(
            'name'      => 'settings[profile]',
            'label'     => Mage::helper('intraship')->__('Profile'),
            'title'     => Mage::helper('intraship')->__('Profile'),
            'required'  => true,
            'values'    => Mage::getModel('intraship/system_config_source_profile')->toOptionArray(null,
                $parcel->getShipment()->getShippingAddress()->getCountryId())
        ));

        $insuranceValue = $parcel->getInsurance();
        $insuranceLabel = (true === $insuranceValue) ? 'Yes' : 'No';
        $fieldset->addField('settings_insurance', 'select', array(
            'name'      => 'settings[insurance]',
            'label'     => Mage::helper('intraship')->__('Insurance'),
            'title'     => Mage::helper('intraship')->__('Insurance'),
            'required'  => true,
            'values'    => array(array(
                'value' => (int) $insuranceValue,
                'label' => $this->helper('intraship')->__($insuranceLabel)
            ))
        ));

        $personallyValue = $parcel->isPersonally();
        $personallyLabel = (true === $personallyValue) ? 'Yes' : 'No';
        $fieldset->addField('settings_personally', 'select', array(
            'name'      => 'settings[personally]',
            'label'     => Mage::helper('intraship')->__('Personally'),
            'title'     => Mage::helper('intraship')->__('Personally'),
            'required'  => true,
            'values'    => array(array(
                'value' => (int) $personallyValue,
                'label' => $this->helper('intraship')->__($personallyLabel)
            ))
        ));

        $fieldset->addField('settings_bulkfreight', 'select', array(
            'name'      => 'settings[bulkfreight]',
            'label'     => Mage::helper('intraship')->__('Bulkfreight'),
            'title'     => Mage::helper('intraship')->__('Bulkfreight'),
            'required'  => true,
            'values'    => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray()
        ));

        $fieldset = $form->addFieldset('customer_data', array(
            'legend'=>Mage::helper('intraship')->__('Customer Data'),
            'class' => 'fieldset-wide'));

        $fieldset->addField('customer_prefix', 'text', array(
            'name'      => 'customer[prefix]',
            'label'     => Mage::helper('intraship')->__('Prefix'),
            'title'     => Mage::helper('intraship')->__('Prefix'),
            'class'     => 'validate-length maximum-length-15',
        ));

        $fieldset->addField('customer_firstname', 'text', array(
            'name'      => 'customer[firstname]',
            'label'     => Mage::helper('intraship')->__('Firstname'),
            'title'     => Mage::helper('intraship')->__('Firstname'),
            'required'  => true,
            'class'     => 'validate-length maximum-length-35',
        ));

        $fieldset->addField('customer_middlename', 'text', array(
            'name'      => 'customer[middlename]',
            'label'     => Mage::helper('intraship')->__('Middlename'),
            'title'     => Mage::helper('intraship')->__('Middlename'),
            'class'     => 'validate-length maximum-length-35',
        ));

        $fieldset->addField('customer_lastname', 'text', array(
            'name'      => 'customer[lastname]',
            'label'     => Mage::helper('intraship')->__('Lastname'),
            'title'     => Mage::helper('intraship')->__('Lastname'),
            'class'     => 'validate-length maximum-length-35',
            'required'  => true,
        ));

        $fieldset->addField('customer_company', 'text', array(
            'name'      => 'customer[company]',
            'label'     => Mage::helper('intraship')->__('Company'),
            'title'     => Mage::helper('intraship')->__('Company'),
            'class'     => 'validate-length maximum-length-45',
        ));

        $fieldset->addField('customer_suffix', 'text', array(
            'name'      => 'customer[suffix]',
            'label'     => Mage::helper('intraship')->__('Suffix'),
            'title'     => Mage::helper('intraship')->__('Suffix'),
            'class'     => 'validate-length maximum-length-35',
        ));

        $fieldset->addField('customer_street_name', 'text', array(
            'name'      => 'customer[street_name]',
            'label'     => Mage::helper('intraship')->__('Street'),
            'title'     => Mage::helper('intraship')->__('Street'),
            'required'  => false,
            'class'     => 'validate-length maximum-length-40',
        ));

        $fieldset->addField('customer_street_number', 'text', array(
            'name'      => 'customer[street_number]',
            'label'     => Mage::helper('intraship')->__('Street number'),
            'title'     => Mage::helper('intraship')->__('Street number'),
            'required'  => false,
            'class'     => 'validate-length maximum-length-10',
        ));

        $fieldset->addField('customer_care_of', 'text', array(
            'name'      => 'customer[care_of]',
            'label'     => Mage::helper('intraship')->__('Care Of'),
            'title'     => Mage::helper('intraship')->__('Care Of'),
            'class'     => 'validate-length maximum-length-30',
        ));

        $fieldset->addField('customer_city', 'text', array(
            'name'      => 'customer[city]',
            'label'     => Mage::helper('intraship')->__('City'),
            'title'     => Mage::helper('intraship')->__('City'),
            'required'  => true,
            'class'     => 'validate-length maximum-length-50',
        ));

        $fieldset->addField('customer_region', 'text', array(
            'name'      => 'customer[region]',
            'label'     => Mage::helper('intraship')->__('Region'),
            'title'     => Mage::helper('intraship')->__('Region'),
            'class'     => 'validate-length maximum-length-50',
        ));

        $fieldset->addField('customer_postcode', 'text', array(
            'name'      => 'customer[postcode]',
            'label'     => Mage::helper('intraship')->__('Postcode'),
            'title'     => Mage::helper('intraship')->__('Postcode'),
            'required'  => true,
            'note'      => '<a href="http://intraship.dhl-partner.de/DHL-PLZFormate-international.pdf" onclick="window.open(this.href); return false;">'.
                           $this->__('International zip code formats').
                           '</a>',
            'class'     => ' validate-zip-dhl ',
        ));

        $fieldset->addField('customer_country_id', 'select', array(
            'name'      => 'customer[country_id]',
            'label'     => Mage::helper('intraship')->__('Country'),
            'title'     => Mage::helper('intraship')->__('Country'),
            'required'  => true,
            'values'    => Mage::getModel('adminhtml/system_config_source_country')->toOptionArray()
        ));

        $fieldset->addField('customer_telephone', 'text', array(
            'name'      => 'customer[telephone]',
            'label'     => Mage::helper('intraship')->__('Telephone'),
            'title'     => Mage::helper('intraship')->__('Telephone'),
            'class'     => ' validate-number validate-length maximum-length-20',
        ));

        $fieldset->addField('customer_email', 'text', array(
            'name'      => 'customer[email]',
            'label'     => Mage::helper('intraship')->__('Email'),
            'title'     => Mage::helper('intraship')->__('Email'),
            'required'  => true,
            'class'     => ' validate-email validate-length maximum-length-255',
        ));

        $fieldset->addField('customer_dhlaccount', 'text', array(
            'name'      => 'customer[dhlaccount]',
            'label'     => Mage::helper('intraship')->__('DHL Account for Parcel Announcement'),
            'title'     => Mage::helper('intraship')->__('DHL Account for Parcel Announcement'),
            'required'  => false,
        ));

        $fieldset->addField('customer_id_number', 'text', array(
            'name'      => 'customer[id_number]',
            'label'     => Mage::helper('intraship')->__('DHL PostNumber'),
            'title'     => Mage::helper('intraship')->__('DHL Customer ID Number'),
            'required'  => false,
        ));

        $fieldset->addField('customer_station_id', 'text', array(
            'name'      => 'customer[station_id]',
            'label'     => Mage::helper('intraship')->__('DHL PACKSTATION Number'),
            'title'     => Mage::helper('intraship')->__('DHL PACKSTATION Number'),
            'required'  => false,
        ));

        if (is_null($parcel->getCustomerEmail())) {
            $parcel->setCustomerEmail($parcel->getShipment()->getOrder()->getCustomerEmail());
        }
        $form->setValues($parcel->getData());
        $form->getElement('save_and_resume')->setValue(0);
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}

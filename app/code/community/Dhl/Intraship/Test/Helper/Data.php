<?php

class Dhl_Intraship_Test_Helper_Data extends EcomDev_PHPUnit_Test_Case
{
    protected $config = null;
    protected $helper = null;

    public function setUp()
    {
      parent::setUp();
      $this->config = new Config();
      $this->helper = new Dhl_Intraship_Helper_Data();
    }

    public function testG()
    {
      $config = $this->config;
      $config->setProductWeightUnit($config::WEIGHT_UNIT_G);
      $this->assertEquals(1, $this->helper->convertWeight(1000, $this->config));
    }

    public function testKg()
    {
      $config = $this->config;
      $config->setProductWeightUnit($config::WEIGHT_UNIT_KG);
      $this->assertEquals(1000, $this->helper->convertWeight(1000, $this->config));
    }

    public function testSplitStreet()
    {
        $streets = array(
            'Thunstraße 39'                     => array('street_name' => 'Thunstraße',                   'street_number' => '39',    'care_of' => ''),
            'Nonnenstraße'                      => array('street_name' => 'Nonnenstraße',                 'street_number' => '',      'care_of' => ''),
            'Tulpenweg'                         => array('street_name' => 'Tulpenweg',                    'street_number' => '',      'care_of' => ''),
            'Tulpenweg '                        => array('street_name' => 'Tulpenweg',                    'street_number' => '',      'care_of' => ''),
            'Nonnenstraße 11c'                  => array('street_name' => 'Nonnenstraße',                 'street_number' => '11c',   'care_of' => ''),
            'Nonnenstraße11c'                   => array('street_name' => 'Nonnenstraße',                 'street_number' => '11c',   'care_of' => ''),
            'Nonnenstraße 44-46'                => array('street_name' => 'Nonnenstraße',                 'street_number' => '44-46', 'care_of' => ''),
            'Nonnenstraße 44-46 Haus C'         => array('street_name' => 'Nonnenstraße',                 'street_number' => '44-46', 'care_of' => 'Haus C'),
            'Leipziger Straße 117, Zimmer 321'  => array('street_name' => 'Leipziger Straße',             'street_number' => '117',   'care_of' => 'Zimmer 321'),
            'Lilienweg 14'                      => array('street_name' => 'Lilienweg',                    'street_number' => '14',    'care_of' => ''),
            'Lilienweg 4'                       => array('street_name' => 'Lilienweg',                    'street_number' => '4',     'care_of' => ''),
            'Richard Strauß Straße 4'           => array('street_name' => 'Richard Strauß Straße',        'street_number' => '4',     'care_of' => ''),
            'Mittelstrasse 6'                   => array('street_name' => 'Mittelstrasse',                'street_number' => '6',     'care_of' => ''),
            'Hauptstr. 6'                       => array('street_name' => 'Hauptstr.',                    'street_number' => '6',     'care_of' => ''),
            'Alte Dorfstraße 4'                 => array('street_name' => 'Alte Dorfstraße',              'street_number' => '4',     'care_of' => ''),
            'Alfons-Mitnacht Str. 5'            => array('street_name' => 'Alfons-Mitnacht Str.',         'street_number' => '5',     'care_of' => ''),
            'Seilerweg 5'                       => array('street_name' => 'Seilerweg',                    'street_number' => '5',     'care_of' => ''),
            'Ægirsvej 4'                        => array('street_name' => 'Ægirsvej',                     'street_number' => '4',     'care_of' => ''),
            'Hilgartshausener Hauptstraße 49'   => array('street_name' => 'Hilgartshausener Hauptstraße', 'street_number' => '49',    'care_of' => ''),
            'erich-weinert-strasse 87'          => array('street_name' => 'erich-weinert-strasse',        'street_number' => '87',    'care_of' => ''),
            'M4 8'                              => array('street_name' => 'M4',                           'street_number' => '8',     'care_of' => ''),
            '1.Straße'                          => array('street_name' => '1.Straße',                     'street_number' => '',      'care_of' => ''),
        );

        foreach ($streets as $given=>$expected) {
            $result = Mage::helper('intraship/data')->splitStreet($given);
            $this->assertEquals($expected, $result);
        }
    }
}

class Config extends Dhl_Intraship_Model_Config
{
  protected $unit;

  public function getProductWeightUnit()
  {
    return $this->unit;
  }

  public function setProductWeightUnit($unit)
  {
    $this->unit=$unit;
  }
}

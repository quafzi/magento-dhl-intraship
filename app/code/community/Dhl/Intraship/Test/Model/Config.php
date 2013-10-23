<?php
/**
 * Config Model Test
 */
class Dhl_Intraship_Test_Model_Config extends EcomDev_PHPUnit_Test_Case_Config
{
    /**
     * @test
     */
    public function getProductTypesForWeightCalculation()
    {
        $store  = Mage::app()->getStore(0)->load(0);
        $config = Mage::getModel('intraship/config');

        $path = 'intraship/packages/global_settings_weight_product_types';

        $store->resetConfig();
        $this->assertSame(array('simple'), $config->getProductTypesForWeightCalculation());

        $store->setConfig($path, 'simple,configurable');
        $this->assertSame(array('simple', 'configurable'), $config->getProductTypesForWeightCalculation());

        $store->resetConfig();
        $this->assertSame(array('simple'), $config->getProductTypesForWeightCalculation());
    }
    
    /**
     *
     * @test
     * @loadFixture config
     */
    public function isTestmode()
    {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    
    public function testGetTrackingUrl()
    {
        $link = 'http://nolp.dhl.de/nextt-online-public/set_identcodes.do?lang=de&idc=%orderNo%';
        $orderNr = '';
        $this->assertNotEquals('testString', Mage::getModel('intraship/config')->getTrackingUrl($orderNr));
        
        $orderNr = '12345';
        $excepted = str_replace('%orderNo%', $orderNr, $link);
        $this->assertEquals($excepted, Mage::getModel('intraship/config')->getTrackingUrl($orderNr));
        
    }
}

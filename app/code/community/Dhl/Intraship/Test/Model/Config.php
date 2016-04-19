<?php
/**
 * Config Model Test
 */
class Dhl_Intraship_Test_Model_Config extends EcomDev_PHPUnit_Test_Case
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

    /**
     * @test
     * @loadFixture config
     */
    public function getProfileByPackageCode()
    {
        // EPN (national) profile
        $profile = Mage::getModel('intraship/config')
            ->getProfileByPackageCode(Dhl_Intraship_Model_Config::PACKAGE_EPN);
        $this->assertEquals(
            Mage::getStoreConfig('intraship/epn/standard'),
            $profile->offsetGet('standard')
        );
        $this->assertEquals(
            Mage::getStoreConfig('intraship/epn/go-green'),
            $profile->offsetGet('go-green')
        );

        // BPI (international) profile
        $profile = Mage::getModel('intraship/config')
            ->getProfileByPackageCode(Dhl_Intraship_Model_Config::PACKAGE_BPI);
        $this->assertEquals(
            Mage::getStoreConfig('intraship/bpi/standard'),
            $profile->offsetGet('standard')
        );
        $this->assertEquals(
            Mage::getStoreConfig('intraship/bpi/go-green'),
            $profile->offsetGet('go-green')
        );

        // EPN (national) profile
        $profile = Mage::getModel('intraship/config')
            ->getProfileByPackageCode(Dhl_Intraship_Model_Config::PACKAGE_EPN, 2);
        $this->assertEquals(
            Mage::getStoreConfig('intraship/epn/standard', 2),
            $profile->offsetGet('standard')
        );
        $this->assertEquals(
            Mage::getStoreConfig('intraship/epn/go-green', 2),
            $profile->offsetGet('go-green')
        );

        // BPI (international) profile
        $profile = Mage::getModel('intraship/config')
            ->getProfileByPackageCode(Dhl_Intraship_Model_Config::PACKAGE_BPI, 2);
        $this->assertEquals(
            Mage::getStoreConfig('intraship/bpi/standard', 2),
            $profile->offsetGet('standard')
        );
        $this->assertEquals(
            Mage::getStoreConfig('intraship/bpi/go-green', 2),
            $profile->offsetGet('go-green')
        );
    }
}

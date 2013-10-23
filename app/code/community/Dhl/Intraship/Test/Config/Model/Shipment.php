<?php
class Dhl_Intraship_Test_Config_Model_Shipment extends EcomDev_PHPUnit_Test_Case_Config
{
    /**
     * Test model definitions for module
     *
     * @test
     */
     public function modelDefinitions()
     {
         $this->assertModelAlias(
             'intraship/shipment',
             'Dhl_Intraship_Model_Shipment'
         );
         
        $this->assertResourceModelAlias(
            'intraship/shipment',
            'Dhl_Intraship_Model_Mysql4_Shipment'
        );
     }
}
<?php
class Dhl_Intraship_Test_Config_Model_Shipment_Document extends EcomDev_PHPUnit_Test_Case_Config
{
    /**
     * Test model definitions for module
     *
     * @test
     */
     public function modelDefinitions()
     {
         $this->assertModelAlias(
             'intraship/shipment_document',
             'Dhl_Intraship_Model_Shipment_Document'
         );
         
        $this->assertResourceModelAlias(
            'intraship/shipment_document',
            'Dhl_Intraship_Model_Mysql4_Shipment_Document'
        );
     }
}
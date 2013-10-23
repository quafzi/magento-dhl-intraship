<?php
class Dhl_Intraship_Test_Config_Model_Config extends EcomDev_PHPUnit_Test_Case_Config
{
    /**
     * Test model definitions for module
     *
     * @test
     */
     public function modelDefinitions()
     {
         $this->assertModelAlias(
             'intraship/config',
             'Dhl_Intraship_Model_Config'
         );
     }
}
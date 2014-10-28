<?php


class Dhl_Account_Test_Model_Http_AdapterTest extends EcomDev_PHPUnit_Test_Case
{
    public function testSetAuth()
    {
        $clientMock = $this->getModelMock('dhlaccount/http_adapter', array('setAuth'));
        $clientMock->expects($this->any())
            ->method('setAuth')
            ->will($this->throwException(new Dhl_Account_Exception('DHL Account Exception')));
        $this->replaceByMock('model', 'dhlaccount/http_adapter', $clientMock);

        $this->setExpectedException('Dhl_Account_Exception');
        $clientMock->setAuth('foo', '123');
    }


}

<?php

/**
 * Dhl Account
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to
 * newer versions in the future.
 *
 * @category    Dhl
 * @package     Dhl_Account
 * @copyright   Copyright (c) 2012 Netresearch GmbH & Co. KG (http://www.netresearch.de/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Dhl Account Helper Postfinder Unit Test
 *
 * @category    Dhl
 * @package     Dhl_Account
 * @author      Michael Lühr <michael.luehr@netresearch.de>
 */

class Dhl_Account_Test_Model_Client_HttpTest extends EcomDev_PHPUnit_Test_Case
{
    protected $client = null;

    /**
     * mocks the http call
     *
     * @param string $body the xml which simulates the response
     */
    protected function replaceHelperByMock($body)
    {
        $response = new Zend_Http_Response(400, array(), $body);
        $postfinderClient = $this->getMock(
            'Dhl_Account_Model_Client_Http',
            array('doPostFinderRequest')
        );
        $postfinderClient->expects($this->any())
            ->method('doPostFinderRequest')
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'dhlaccount/client_http', $postfinderClient);
        $this->client = Mage::getModel('dhlaccount/client_http');
    }

    /**
     * test transformation of empty result
     */
    public function testGetPackstationDataWithEmptyData()
    {
        $body = '<?xml version="1.0" encoding="utf-8" ?>
<page><session><suggestion><show><![CDATA[1]]></show></suggestion><shortsurvey_textarea><maxchars><![CDATA[500]]></maxchars></shortsurvey_textarea><show_shortsurvey></show_shortsurvey><reqparams><pmtype><![CDATA[1]]></pmtype><standorttyp><![CDATA[packstationen_paketboxen]]></standorttyp></reqparams><language><![CDATA[de]]></language><version><![CDATA[402]]></version><timestamp><![CDATA[1340898515150]]></timestamp><advert_pos><![CDATA[bottom]]></advert_pos><visitorid><![CDATA[k65kgltq]]></visitorid></session><data></data></page>';
        $this->replaceHelperByMock($body);
        $result = $this->client->getPackstationData();
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, sizeof($result));
        $this->assertTrue(in_array('{"packstationnumber":"","zip":"","city":"","errors":"No data transmitted","distance":""}', array_keys($result)));
        $this->assertEquals('No data transmitted', current($result));
    }

    /**
     *tests transformation with valid zipcode given
     */
    public function testGetPackstationDataWithZipGiven()
    {
        $body = '<?xml version="1.0" encoding="utf-8" ?>
<page>
    <data>
        <searchinfo>
            <algorithm><![CDATA[range]]>
            </algorithm>
            <maxrange><![CDATA[15]]>
            </maxrange>
            <maxresults><![CDATA[100]]>
            </maxresults>
        </searchinfo>
        <position>
            <latitude><![CDATA[51.3326516]]>
            </latitude>
            <longitude><![CDATA[12.3911497]]>
            </longitude>
        </position>
        <result>
            <automats>
                <automat id="404103141" objectId="4103141">
                    <automatType><![CDATA[4]]>
                    </automatType>
                    <positionType><![CDATA[0]]>
                    </positionType>
                    <opStatusType><![CDATA[1]]>
                    </opStatusType>
                    <hasXLPostfach><![CDATA[false]]>
                    </hasXLPostfach>
                    <address>
                        <street><![CDATA[Nürnberger Str.]]>
                        </street>
                        <streetno><![CDATA[48]]>
                        </streetno>
                        <zip><![CDATA[04103]]>
                        </zip>
                        <city><![CDATA[Leipzig]]>
                        </city>
                        <district><![CDATA[Zentrum-Südost]]>
                        </district>
                        <country><![CDATA[Deutschland]]>
                        </country>
                        <remark><![CDATA[Studentenwerk]]>
                        </remark>
                    </address>
                    <location>
                        <latitude><![CDATA[51.331965757559]]>
                        </latitude>
                        <longitude><![CDATA[12.3809266216372]]>
                        </longitude>
                        <distance><![CDATA[714]]>
                        </distance>
                    </location>
                    <timetables></timetables>
                </automat>
                <automat id="404103106" objectId="4103106">
                    <automatType><![CDATA[4]]>
                    </automatType>
                    <positionType><![CDATA[0]]>
                    </positionType>
                    <opStatusType><![CDATA[1]]>
                    </opStatusType>
                    <hasXLPostfach><![CDATA[false]]>
                    </hasXLPostfach>
                    <address>
                        <street><![CDATA[Straße d 18. Oktober]]>
                        </street>
                        <streetno><![CDATA[29]]>
                        </streetno>
                        <zip><![CDATA[04103]]>
                        </zip>
                        <city><![CDATA[Leipzig]]>
                        </city>
                        <district><![CDATA[Zentrum-Südost]]>
                        </district>
                        <country><![CDATA[Deutschland]]>
                        </country>
                        <remark><![CDATA[Studentenwohnheim]]>
                        </remark>
                    </address>
                    <location>
                        <latitude><![CDATA[51.3247296605926]]>
                        </latitude>
                        <longitude><![CDATA[12.3903377079503]]>
                        </longitude>
                        <distance><![CDATA[883]]>
                        </distance>
                    </location>
                    <timetables></timetables>
                </automat>
            </automats>
        </result>
    </data>
</page>';
        $this->replaceHelperByMock($body);
        $result = $this->client->getPackstationData('04103');
        $this->assertTrue(is_array($result));
        $this->assertEquals(2, sizeof($result));
        $this->assertTrue(in_array('{"packstationnumber":"141","zip":"04103","city":"Leipzig","errors":"","distance":"' . Mage::helper('dhlaccount')->__('distance') . ': ' . '714 m"}', array_keys($result)));
        $this->assertTrue(in_array('{"packstationnumber":"106","zip":"04103","city":"Leipzig","errors":"","distance":"' . Mage::helper('dhlaccount')->__('distance') . ': ' . '883 m"}', array_keys($result)));
        $this->assertTrue(in_array(utf8_encode('141: Nürnberger Str. 48, 04103 Leipzig'), array_values($result)));
        $this->assertTrue(in_array(utf8_encode('106: Straße d 18. Oktober 29, 04103 Leipzig'), array_values($result)));
        $result2 = $this->client->getPackstationData('04103');
        $result3 = $this->client->getPackstationData('04103', '');
        $this->assertEquals($result, $result2);
        $this->assertEquals($result2, $result3);
    }

    /**
     * tests transformation with invalid combination of zipcode and city
     */
    public function testGetPackstationDataWithInvalidZipAndCity()
    {
        $body = '<?xml version="1.0" encoding="utf-8" ?>
<page><session><suggestion><show><![CDATA[1]]></show></suggestion><shortsurvey_textarea><maxchars><![CDATA[500]]></maxchars></shortsurvey_textarea><show_shortsurvey></show_shortsurvey><reqparams><postleitzahl><![CDATA[04103]]></postleitzahl><pmtype><![CDATA[1]]></pmtype><ort><![CDATA[halle]]></ort><standorttyp><![CDATA[packstationen_paketboxen]]></standorttyp></reqparams><infos><info><![CDATA[address.unknown]]></info></infos><durations><geocode><![CDATA[11]]></geocode></durations><language><![CDATA[de]]></language><version><![CDATA[402]]></version><timestamp><![CDATA[1340899711909]]></timestamp><advert_pos><![CDATA[right]]></advert_pos><visitorid><![CDATA[gaoc8orv]]></visitorid></session><data></data></page>';
        $this->replaceHelperByMock($body);
        $result = $this->client->getPackstationData('04103', 'Halle');
        $this->assertEquals(Mage::helper('dhlaccount')->__('address.unknown'), current(array_values($result)));
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, sizeof($result));
    }

    /**
     *tests transformation with ambigious city
     */
    public function testGetPackstationDataWithAmbigiousCityName()
    {
        $body = '<?xml version="1.0" encoding="utf-8" ?>
<page>
    <data>
        <result>
        </result>
    </data>
</page>';
        $this->replaceHelperByMock($body);
        $result = $this->client->getPackstationData('', 'Halle');
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, sizeof($result));
        $this->assertEquals(Mage::helper('dhlaccount')->__('address is ambigious'), current(array_values($result)));
    }

    /**
     * tests transformation with partial zipcode
     */
    public function testGetPackstationDataWithPartialZip()
    {
        $body = '<?xml version="1.0" encoding="utf-8" ?>
<page>
    <session>
        <infos>
            <info><![CDATA[too.many.results]]>
            </info>
        </infos>
    </session>
    <data>
        <result>
        </result>
    </data>
</page>';
        $this->replaceHelperByMock($body);
        $result = $this->client->getPackstationData('04');
        $this->assertTrue(is_array($result));
        $this->assertEquals(Mage::helper('dhlaccount')->__('too.many.results'), current(array_values($result)));
    }

    /**
     * tests transformation with city
     */
    public function testGetPackstationdataWithCity()
    {
        $body = '<?xml version="1.0" encoding="utf-8" ?>
<page>
    <data>
        <searchinfo>
            <algorithm><![CDATA[range]]>
            </algorithm>
            <maxrange><![CDATA[15]]>
            </maxrange>
            <maxresults><![CDATA[100]]>
            </maxresults>
        </searchinfo>
        <position>
            <latitude><![CDATA[51.3326516]]>
            </latitude>
            <longitude><![CDATA[12.3911497]]>
            </longitude>
        </position>
        <result>
            <automats>
                <automat id="404103141" objectId="4103141">
                    <automatType><![CDATA[4]]>
                    </automatType>
                    <positionType><![CDATA[0]]>
                    </positionType>
                    <opStatusType><![CDATA[1]]>
                    </opStatusType>
                    <hasXLPostfach><![CDATA[false]]>
                    </hasXLPostfach>
                    <address>
                        <street><![CDATA[Nürnberger Str.]]>
                        </street>
                        <streetno><![CDATA[48]]>
                        </streetno>
                        <zip><![CDATA[04103]]>
                        </zip>
                        <city><![CDATA[Leipzig]]>
                        </city>
                        <district><![CDATA[Zentrum-Südost]]>
                        </district>
                        <country><![CDATA[Deutschland]]>
                        </country>
                        <remark><![CDATA[Studentenwerk]]>
                        </remark>
                    </address>
                    <location>
                        <latitude><![CDATA[51.331965757559]]>
                        </latitude>
                        <longitude><![CDATA[12.3809266216372]]>
                        </longitude>
                        <distance><![CDATA[714]]>
                        </distance>
                    </location>
                    <timetables></timetables>
                </automat>
                <automat id="404103106" objectId="4103106">
                    <automatType><![CDATA[4]]>
                    </automatType>
                    <positionType><![CDATA[0]]>
                    </positionType>
                    <opStatusType><![CDATA[1]]>
                    </opStatusType>
                    <hasXLPostfach><![CDATA[false]]>
                    </hasXLPostfach>
                    <address>
                        <street><![CDATA[Straße d 18. Oktober]]>
                        </street>
                        <streetno><![CDATA[29]]>
                        </streetno>
                        <zip><![CDATA[04103]]>
                        </zip>
                        <city><![CDATA[Leipzig]]>
                        </city>
                        <district><![CDATA[Zentrum-Südost]]>
                        </district>
                        <country><![CDATA[Deutschland]]>
                        </country>
                        <remark><![CDATA[Studentenwohnheim]]>
                        </remark>
                    </address>
                    <location>
                        <latitude><![CDATA[51.3247296605926]]>
                        </latitude>
                        <longitude><![CDATA[12.3903377079503]]>
                        </longitude>
                        <distance><![CDATA[883]]>
                        </distance>
                    </location>
                    <timetables></timetables>
                </automat>
            </automats>
        </result>
    </data>
</page>';
        $this->replaceHelperByMock($body);
        $result = $this->client->getPackstationData(null, 'Leipzig');
        $this->assertTrue(is_array($result));
        $this->assertTrue(in_array('{"packstationnumber":"141","zip":"04103","city":"Leipzig","errors":"","distance":"' . Mage::helper('dhlaccount')->__('distance') . ': ' . '714 m"}', array_keys($result)));
        $this->assertTrue(in_array('{"packstationnumber":"106","zip":"04103","city":"Leipzig","errors":"","distance":"' . Mage::helper('dhlaccount')->__('distance') . ': ' . '883 m"}', array_keys($result)));
        $this->assertTrue(in_array(utf8_encode('141: Nürnberger Str. 48, 04103 Leipzig'), array_values($result)));
        $this->assertTrue(in_array(utf8_encode('106: Straße d 18. Oktober 29, 04103 Leipzig'), array_values($result)));
    }

    public function testServiceIsUnavaliable()
    {


        $postfinderHelper = $this->getMock(
            'Dhl_Account_Model_Client_Http',
            array('doPostFinderRequest')
        );
        $postfinderHelper->expects($this->any())
            ->method('doPostFinderRequest')
            ->will($this->throwException(new Zend_Http_Client_Exception()));
        $this->replaceByMock('model', 'dhlaccount/client_http', $postfinderHelper);
        $this->client = Mage::getModel('dhlaccount/client_http');
        $this->client->setUri(Mage::getModel('dhlaccount/config')->getPackstationFinderUrl());
        $result = $this->client->getPackstationData('04103', 'Leipzig');
        $this->assertTrue(is_array($result));
        $this->assertEquals(Mage::helper('dhlaccount')->__('service is unavailable'), current(array_values($result)));
    }


}

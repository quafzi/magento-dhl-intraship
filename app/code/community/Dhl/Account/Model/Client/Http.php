<?php
class Dhl_Account_Model_Client_Http extends Zend_Http_Client
{

    /**
     *
     * neccessary override because Mage::getModel passes unwanted params to the client
     *
     * @override
     */
    public function __construct()
    {
        parent::__construct(null, null);
    }


    /**
     * performs the request and returns the response
     *
     * @param string $zipCode - (optional) the zipCode for packstation
     * @param string $city - (optional) the city for packstation
     * @return Zend_Http_Response
     */
    protected function doPostFinderRequest($zipCode = null, $city = null)
    {
        $this->setParameterGet('zip', $zipCode);
        $this->setParameterGet('city', $city);
        return $this->request();
    }


    /**
     *
     * transform xml data containing the postfinder data into array containing the
     * postfinder data
     *
     * @param string $xmlString - string containing the xml
     * @return array - the data from xml or errors
     */
    protected function parsePackstationXml($xmlString)
    {
        $parsedData = array();
        $xml = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);
        // no postfinder data in xml
        if ($xml->data->children()->count() == 0) {
            $errorMessage = 'No data transmitted';
            if ($xml->session->infos->info) {
                $errorMessage = trim(current($xml->session->infos->info));
            }
            $key = Zend_Json::encode(array(
                        'packstationnumber' => '',
                        'zip'               => '',
                        'city'              => '',
                        'errors'            => Mage::helper('dhlaccount')->__($errorMessage),
                        'distance'          => ''
                      ));
            $parsedData[$key] = Mage::helper('dhlaccount')->__($errorMessage);
        }
        // packstations in postfinder data, collect packstations into array
        elseif ($xml->data->result->automats && $xml->data->result->automats->children()->count() > 0){
            foreach ($xml->data->result->automats->children() as $automat) {
                $distance = trim(current($automat->location->distance));
                $distanceString = '';
                if ($distance < 1000) {
                    $distanceString =  $distance . ' m';
                } else {
                    $distanceString = Zend_Locale_Format::toFloat(number_format($distance/1000, 1)) . ' km';
                }
                $key = Zend_Json::encode(array(
                        'packstationnumber' => substr(trim(current($automat['objectId'])),-3),
                        'zip'               => trim($automat->address->zip),
                        'city'              => trim($automat->address->city),
                        'errors'            => '',
                        'distance'          => Mage::helper('dhlaccount')->__('distance') . ': ' . $distanceString
                      ));
                $parsedData[$key] = substr(trim(current($automat['objectId'])),-3) . ': ' .
                                    trim($automat->address->street) . ' ' .
                                    trim($automat->address->streetno) . ', ' .
                                    trim($automat->address->zip). ' ' .
                                    trim($automat->address->city);
            }
        }
        // no packstations in data e.g. address is ambigious or there are too many results
        else {
            $errorMessage = 'address is ambigious';
            if ($xml->session->infos && $xml->session->infos->info) {
                $errorMessage = trim(current($xml->session->infos->info));
            }
            $key = Zend_Json::encode(array(
                        'packstationnumber' => '',
                        'zip'               => '',
                        'city'              => '',
                        'errors'            => Mage::helper('dhlaccount')->__($errorMessage),
                        'distance'          => ''
                      ));
            $parsedData[$key] = Mage::helper('dhlaccount')->__($errorMessage);
        }
        return $parsedData;
    }


    /**
     *
     * returns the parsed packstation data
     *
     * @param string $zipCode - (optional) the zipCode for packstation
     * @param string $city - (optional) the city for packstation
     * @return array - the parsed packstation data
     */
    public function getPackstationData($zipCode = null, $city = null)
    {
        $parsedData = array();
        $response = null;
        try {
            $response = $this->doPostFinderRequest($zipCode, $city);
            $parsedData = $this->parsePackstationXml(utf8_encode($response->getBody()));
        } catch (Zend_Http_Client_Exception $zce) {
            Mage::helper('dhlaccount')->log('Exception during request to '. $this->getUri(true) . '?' . $this->getUri()->getQuery());
            Mage::helper('dhlaccount')->log($zce->getMessage());
            Mage::helper('dhlaccount')->log($zce->getTraceAsString());
            $errorMessage = 'service is unavailable';
            $key = Zend_Json::encode(array(
                        'packstationnumber' => '',
                        'zip'               => '',
                        'city'              => '',
                        'errors'            => Mage::helper('dhlaccount')->__($errorMessage)
                      ));
            $parsedData[$key] = Mage::helper('dhlaccount')->__($errorMessage);
        }
        return $parsedData;
    }
}

<?php
/**
 * Dhl_Intraship_Model_Autocreate
 *
 * @category  Models
 * @package   Dhl_Intraship
 * @author    Jochen Werner <jochen.werner@netresearch.de>
 * @copyright Copyright (c) 2010 Netresearch GmbH & Co.KG <http://www.netresearch.de/>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Dhl_Intraship_Model_Test
{
    /**
     * @var integer
     */
    public $orderId = null;

    /**
     * Proccess test
     *
     * @param  integer  $orderId
     * @param  boolean  $cod
     * @return void
     */
    public function process($orderId, $cod = false)
    {
        $log = "<br/>Start process " . __CLASS__ . "<br/>";
        $this->orderId = $orderId;
        $result        = array();
        foreach (get_class_methods(__CLASS__) as $method):
            if (!preg_match('/\_test/', $method)) continue;
            if (true === $cod && !preg_match('/Cod/', $method)) continue;
            if (false === $cod && preg_match('/Cod/', $method)) continue;

            $intraship = $this->{$method}();
            $result[$method]  = $intraship->getId();
            $log .= "<br/>run " . $method . '()';
        endforeach;
        $log .= "<br/><br/>Start queue...";
        Mage::getModel('intraship/observer')->cronQueue();
        $log .= "<br/>Queue done.<br/>";
        foreach ($result as $method => $id):
            $intraship = new Dhl_Intraship_Model_Shipment();
            $intraship->load($id, 'id');
            if ('ok' !== $intraship->getClientStatusMessage()):
                $log .= "<br/>" . $method . " faild. See intraship id ". $id . "<br/>";
            endif;
        endforeach;
        print $log;
    }

    /**
     * Get order id
     *
     * @return integer
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Execute
     *
     * @param  ArrayObject                  $settings
     * @param  boolean                      $multipack
     * @return Dhl_Intraship_Model_Shipment $this
     */
    public function execute(ArrayObject $settings, $multipack = null)
    {
        $helper  = new Dhl_Intraship_Model_Autocreate();
        $model   = new Mage_Sales_Model_Order();

        $myOrder = $model->loadByIncrementId($this->getOrderId());
        $myOrder->setReordered(true);

        $data = array(
          'currency' => 'EUR',
          'account' => array(
              'group_id' => 1,
              'email' => 'jochen.werner@netresearch.de'
           ),
          'billing_address' => $myOrder->getBillingAddress()->getData(),
          'shipping_address' => $myOrder->getShippingAddress()->getData(),
          'shipping_method' => 'flatrate_flatrate',
          'comment' => array(
              'customer_note' => false,
              'send_confirmation' => false
        ));
        $admin = new Mage_Adminhtml_Model_Sales_Order_Create();
        $order = $admin
           ->initFromOrder($myOrder)
           ->importPostData($data)
           ->createOrder();

        $shipment = $helper->checkOrder($order)->createShipment($order);
        $helper->saveShipment($shipment, $settings);
        $intraship = new Dhl_Intraship_Model_Shipment();
        $intraship->load($shipment->getId(), 'shipment_id');
        if (true === $multipack):
            $intraship->setPackages(array(
                'package_0' => array('weight' => 0.1),
                'package_1' => array('weight' => 0.2),
                'package_2' => array('weight' => 0.3)
            ))->save();
        endif;
        return $intraship;
    }

    protected function _testInsurance()
    {
        // Transportversicherung
        $settings = new ArrayObject();
        $settings->offsetSet('profile', 'standard');
        $settings->offsetSet('insurance',        1);
        $settings->offsetSet('personally',       0);
        $settings->offsetSet('bulkfreight',      0);
        return $this->execute($settings);
    }

    protected function _testInsurance_Bulkfreight()
    {
        // Transportversicherung + Sperrgut
        $settings = new ArrayObject();
        $settings->offsetSet('profile', 'standard');
        $settings->offsetSet('insurance',        1);
        $settings->offsetSet('personally',       0);
        $settings->offsetSet('bulkfreight',      1);
        return $this->execute($settings);
    }

    protected function _testInsurance_Personally()
    {
        // Transportversicherung + Eigenhändig
        $settings = new ArrayObject();
        $settings->offsetSet('profile', 'standard');
        $settings->offsetSet('insurance',        1);
        $settings->offsetSet('personally',       1);
        $settings->offsetSet('bulkfreight',      0);
        return $this->execute($settings);
    }

    protected function _testInsurance_Multipack()
    {
        // Transportversicherung + Mehrpaket
        $settings = new ArrayObject();
        $settings->offsetSet('profile', 'standard');
        $settings->offsetSet('insurance',        1);
        $settings->offsetSet('personally',       0);
        $settings->offsetSet('bulkfreight',      0);
        return $this->execute($settings, true);
    }

    protected function _testPersonally()
    {
        // Eigenhändig
        $settings = new ArrayObject();
        $settings->offsetSet('profile', 'standard');
        $settings->offsetSet('insurance',        0);
        $settings->offsetSet('personally',       1);
        $settings->offsetSet('bulkfreight',      0);
        return $this->execute($settings);
    }

    protected function _testBulkfreight()
    {
        // Sperrgut
        $settings = new ArrayObject();
        $settings->offsetSet('profile', 'standard');
        $settings->offsetSet('insurance',        0);
        $settings->offsetSet('personally',       0);
        $settings->offsetSet('bulkfreight',      1);
        return $this->execute($settings);
    }

    protected function _testBulkfreight_Personally()
    {
        // Sperrgut + Eigenhändig
        $settings = new ArrayObject();
        $settings->offsetSet('profile', 'standard');
        $settings->offsetSet('insurance',        0);
        $settings->offsetSet('personally',       1);
        $settings->offsetSet('bulkfreight',      1);
        return $this->execute($settings);
    }

    protected function _testMultipack()
    {
        // Mehrpaket
        $settings = new ArrayObject();
        $settings->offsetSet('profile', 'standard');
        $settings->offsetSet('insurance',        0);
        $settings->offsetSet('personally',       0);
        $settings->offsetSet('bulkfreight',      0);
        return $this->execute($settings, true);
    }

    protected function _testCod()
    {
        // Nachnahme
        $settings = new ArrayObject();
        $settings->offsetSet('profile', 'standard');
        $settings->offsetSet('insurance',        0);
        $settings->offsetSet('personally',       0);
        $settings->offsetSet('bulkfreight',      0);
        return $this->execute($settings, null);
    }

    protected function _testCod_Insurance()
    {
        // Nachnahme + Transportversicherung
        $settings = new ArrayObject();
        $settings->offsetSet('profile', 'standard');
        $settings->offsetSet('insurance',        1);
        $settings->offsetSet('personally',       0);
        $settings->offsetSet('bulkfreight',      0);
        return $this->execute($settings);
    }

    protected function _testCod_Insurance_Multipack()
    {
        // Nachnahme + Transportversicherung + Mehrpaket
        $settings = new ArrayObject();
        $settings->offsetSet('profile', 'standard');
        $settings->offsetSet('insurance',        1);
        $settings->offsetSet('personally',       0);
        $settings->offsetSet('bulkfreight',      0);
        return $this->execute($settings, true);
    }

    protected function _testCod_Insurance_Bulkfreight()
    {
        // Nachnahme + Transportversicherung + Sperrgut
        $settings = new ArrayObject();
        $settings->offsetSet('profile', 'standard');
        $settings->offsetSet('insurance',        1);
        $settings->offsetSet('personally',       0);
        $settings->offsetSet('bulkfreight',      1);
        return $this->execute($settings);
    }

    protected function _testCod_Bulkfreight()
    {
        // Nachnahme + Sperrgut
        $settings = new ArrayObject();
        $settings->offsetSet('profile', 'standard');
        $settings->offsetSet('insurance',        0);
        $settings->offsetSet('personally',       0);
        $settings->offsetSet('bulkfreight',      1);
        return $this->execute($settings);
    }

    protected function _testCod_Multipack()
    {
        // Nachnahme + Mehrpaket
        $settings = new ArrayObject();
        $settings->offsetSet('profile', 'standard');
        $settings->offsetSet('insurance',        0);
        $settings->offsetSet('personally',       0);
        $settings->offsetSet('bulkfreight',      0);
        return $this->execute($settings, true);
    }
}
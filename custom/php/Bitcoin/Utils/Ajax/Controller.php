<?php

/**
 * Ajax service class
 * @author  andyw@circle
 * @package uk.co.circleinteractive.payment.bitcoin
 */
class Bitcoin_Utils_Ajax_Controller extends CRM_Core_Controller {

    /**
     * Constructor
     * @todo figure out why qfKey validation is breaking when parent constructor called
     */ 
    public function __construct($title = null, $action = CRM_Core_Action::NONE, $modal = true) {
        #parent::__construct($title, $modal);
    }

    /**
     * Request handler
     * @param array $param input parameters for request
     */
    private function request(&$param) {

        $address     = 'mmmHm2bJJfSF5RYxobfk8CDG4gDHHNHzVr';
        $payment_uri = 'bitcoin:mmmHm2bJJfSF5RYxobfk8CDG4gDHHNHzVr?r=http%3A%2F%2Fbitcoincore.org%2F%7Egavin%2Ff.php%3Fh%3D974226354377d2751007860e3e74c15d&amount=0.0001';
        $qr_code     = Bitcoin_Utils_QRCode::getInline($payment_uri);

        header('Content-type: application/json');

        echo json_encode(array(
            'status'  => 'ok',
            'address' => $address,
            'qr_code' => $qr_code, 
            'pay_uri' => $payment_uri
        ));

        CRM_Utils_System::civiExit();
        
    }

    public function run() {
        return $this->request($_POST);
    }

}
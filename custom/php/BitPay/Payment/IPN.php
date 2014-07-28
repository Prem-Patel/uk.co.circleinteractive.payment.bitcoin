<?php

class BitPay_Payment_IPN extends CRM_Core_Payment_BaseIPN {

    public static $_paymentProcessor = null;
    
    public function __construct() {
        parent::__construct();
    }

    public function main($module, $data) {
        watchdog('andyw', 'running ipn, module = ' . $module . ', data = <pre>' . print_r($data, true) . '</pre>');
    }

    public function prepareData() {
        require_once "packages/bitpay/php-client/bp_lib.php";    
        return bpVerifyNotification();
    }

}
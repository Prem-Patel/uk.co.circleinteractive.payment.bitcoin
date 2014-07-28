<?php

class BitPay_Payment_IPN extends CRM_Core_Payment_BaseIPN {

    public static $_paymentProcessor = null;
    
    public function __construct() {
        parent::__construct();
    }

    public function main($module, $data) {
        watchdog('andyw', 'running ipn, module = ' . $module . ', data = <pre>' . print_r($data, true) . '</pre>');
    }

    public function verifyData() {

        if (!isset($_GET['processor_id']) or empty($_GET['processor_id']))
            CRM_Core_Error::fatal(ts('processor_id param empty or missing in %1::%2', array(
                1 => __CLASS__,
                2 => __METHOD__
            )));

        # get api key for processor
        try {

            $api_key = civicrm_api3('paymentProcessor', 'getvalue', array(
                'id'     => $_GET['processor_id'],
                'return' => 'user_name'
            ));

        } catch (CiviCRM_API3_Exception $e) {
            CRM_Core_Error::fatal(ts('Unable to get api key for processor id %1', array(
                1 => $processor_id
            )));
        }

        watchdog('andyw', '_paymentProcessor = <pre>' . print_r($this->_paymentProcessor, true) . '</pre>');

        require_once "packages/bitpay/php-client/bp_lib.php";    
        return bpVerifyNotification($api_key);
    
    }

}
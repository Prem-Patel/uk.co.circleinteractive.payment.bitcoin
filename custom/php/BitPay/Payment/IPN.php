<?php

class BitPay_Payment_IPN extends CRM_Core_Payment_BaseIPN {

    public static $_paymentProcessor = null;
    
    public function __construct() {
        parent::__construct();
    }

    public function main($module, $invoice) {
        
        watchdog('andyw', 'running ipn, module = ' . $module . ', data = <pre>' . print_r($invoice, true) . '</pre>');

        if ($stored_transaction = BitPay_Payment_BAO_Transaction::load(array(
            'bitpay_id' => $invoice['id']
        ))) {
            
            # get contribution id
            $contribution_id = $stored_transaction['contribution_id'];

            # write updated status back to database
            BitPay_Payment_BAO_Transaction::save($response + array(
                'contribution_id' => $invoice['contribution_id'],
                'bitpay_id'       => $bitpay_id
            ));

            # if anything other than complete, return for now
            # todo: may want to handle expired, invalid
            if ($status != 'complete') 
                return;

            switch ($module) {
                
                case 'event':
                    # get participant and event ids
                    
                    break;
                
                case 'contribute':

                    break;
            }


        } else {
            watchdog('andyw', 'failed loading invoice');
        }

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

        require_once "packages/bitpay/php-client/bp_lib.php";    
        return bpVerifyNotification($api_key);
    
    }

}
<?php

class BitPay_Payment_IPN extends CRM_Core_Payment_BaseIPN {

    public static $_paymentProcessor = null;
    
    public function __construct() {
        parent::__construct();
    }

    public function main($params) {
        
    }

}
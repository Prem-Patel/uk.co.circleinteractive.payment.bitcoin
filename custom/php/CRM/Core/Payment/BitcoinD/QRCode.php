<?php

/**
 * QR Code Renderer class
 * @author  andyw@circle
 * @package com.uk.andyw.payment.bitcoin
 */
class CRM_Core_Payment_BitcoinD_QRCode {

    /**
     * Constructor
     */ 
    public function __construct() {
        return $this->request($_GET);
    }

    /**
     * Request handler
     * @param array $param input parameters for request
     */
    private function request(&$param) {
        
        header('Content-type: image/png');
        
        CRM_Utils_System::civiExit();

    }

}
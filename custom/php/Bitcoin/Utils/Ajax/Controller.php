<?php

/**
 * Ajax service class
 * @author  andyw@circle
 * @package com.uk.andyw.payment.bitcoin
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

        echo json_encode(array(
            'status' => 'ok'
        ));
        CRM_Utils_System::civiExit();
        
    }

    public function run() {
        return $this->request($_POST);
    }

}
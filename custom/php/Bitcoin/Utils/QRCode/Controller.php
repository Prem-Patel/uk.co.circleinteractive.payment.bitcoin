<?php

/**
 * QR Code Renderer class
 * @author  andyw@circle
 * @package com.uk.andyw.payment.bitcoin
 */
class Bitcoin_Utils_QRCode_Controller extends CRM_Core_Controller {

    /**
     * Constructor
     */ 
    public function __construct($title = null, $action = CRM_Core_Action::NONE, $modal = true) {
        parent::__construct($title, $modal);
    }

    /**
     * Request handler
     * @param  array $param input parameters for request
     * @access private
     */
    private function request(&$param) {
        
        if (isset($param['qr'])) {
            
            require_once 'packages/tcpdf/tcpdf_barcodes_2d.php';
            $image = new TCPDF2DBarcode($param['qr'], 'QRCODE,H');
            
            header('Content-type: image/png');
            
            echo $image->getBarcodePNG(
                isset($param['w']) ? $param['w'] : 3.5, 
                isset($param['h']) ? $param['h'] : 3.5
            );

        }

        CRM_Utils_System::civiExit();

    }

    public function run() {
        return $this->request($_GET);
    }

}
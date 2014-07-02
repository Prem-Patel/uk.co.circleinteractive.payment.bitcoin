<?php

/**
 * QR Code Renderer class
 * @author  andyw@circle
 * @package com.uk.andyw.payment.bitcoin
 */
class Bitcoin_Utils_QRCode {

    /**
     * Return QR code as inline base64 encoded data
     * @param  string $qr_code the code for generated image
     * @param  float  $width   optional width of generated image, defaults to 3.5
     * @param  float  $height  optional height of generated image, defaults to 3.5
     * @access public
     */
    public static function getInline($qr_code, $width = 3.5, $height = 3.5) {
            
        require_once 'packages/tcpdf/tcpdf_barcodes_2d.php';
        $image = new TCPDF2DBarcode($qr_code, 'QRCODE,H');

        ob_start();
        $image->getBarcodePNG($width, $height);
        $data = base64_encode(ob_get_clean());

        return 'data:image/png;charset=utf8;base64,' . $data;

    }

}
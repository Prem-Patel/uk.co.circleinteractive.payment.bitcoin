<?php

/**
 * Payment processor class for BitPay
 * @author  andyw@circle
 * @package com.uk.andyw.payment.bitcoin
 */
class CRM_Core_Payment_BitPay extends CRM_Core_Payment_Bitcoin {
    
    /**
     * Machine name of payment processor
     * @var    string
     * @access protected
     * @static
     */
    protected static $name = 'BitPay';

    /**
     * Human-readable name of payment processor
     * @var    string
     * @access protected
     * @static
     */
    protected static $title = 'BitPay'; 

    /**
     * Billing mode
     * @var    string
     * @access protected
     * @static
     */
    protected static $mode = 4; # notify
    
    /**
     * Do we support recurring or not
     * @var bool
     */
    protected static $is_recur = false;

    /**
     * PaymentProcessorType params specific to this processor
     * @var    array
     * @access protected
     * @static
     */
    protected static $installParams = array(
        'username_label'       => 'API Key ID',
        'url_api_default'      => 'https://bitpay.com/api',
        'url_api_test_default' => 'https://bitpay.com/api'
    );


    public function doTransferCheckout(&$params, $component = 'contribute') {
        # todo ..
    }


}
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
        'user_name_label'       => 'API Key ID',
        'url_site_default'      => 'https://bitpay.com/api',
        'url_site_test_default' => 'https://bitpay.com/api'
    );

    public function doTransferCheckout(&$params, $component = 'contribute') {
        
        $client = new Bitcoin_Utils_WebClient;

        

    }

    /**
     * Get the current BTC exchange rate
     * @param  string $currency  currency to get exchange rate for
     * @return float
     */
    public static function getExchangeRate($currency = 'USD') {
        
        $client = new Bitcoin_Utils_WebClient;
        
        if ($exchange = $client->get('http://bitpay.com/api/rates/' . $currency))
            if ($exchange = @json_decode($exchange))
                if (isset($exchange->rate))
                    return $exchange->rate;
        
        return 0;

    }

}
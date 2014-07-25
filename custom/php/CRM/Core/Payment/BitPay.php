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
    protected static $installParams = [
        'user_name_label'       => 'API Key ID',
        'url_site_default'      => 'https://bitpay.com/api',
        'url_site_test_default' => 'https://bitpay.com/api'
    ];

    public function doTransferCheckout(&$params, $component = 'contribute') {

        watchdog('andyw', 'params = <pre>' . print_r($params, true) . '</pre>');
        
        if (!in_array($component, ['contribute', 'event']))
            CRM_Core_Error::fatal(ts('Component is invalid'));

        $config      = CRM_Core_Config::singleton();
        $transaction = &$_SESSION['bitpay_trxn'];
        
        require_once "packages/bitpay/php-client/bp_lib.php";
        
        $response = bpCreateInvoice($params['invoiceID'], 0.01, '', [
            'currency' => 'GBP',
            'apiKey'   => 'pPpXdMAFzgjgtnjLcjS5Eye9eXU06jCbj0CCRFeO0'
        ]);

        if (is_string($response))
            throw new CRM_Core_Exception($response);

        $transaction = new StdClass;
        
        $transaction->response = (object)$response;
        $transaction->thankyou_url = CRM_Utils_System::url(
            $component == 'event' ? 'civicrm/event/register' : 'civicrm/contribute/transact',
            "_qf_ThankYou_display=1&qfKey=" . $params['qfKey'], 
            true, null, false, true
        );

        watchdog('andyw', 'response = <pre>' . print_r($response, true) . '</pre>');

        # redirect to payment page
        CRM_Utils_System::redirect(
            CRM_Utils_System::url('civicrm/payment/bitpay', null, true, null, false, true, false)
        );

        /*
        $client  = new \Guzzle\Service\Client();
        $request = $client->post('https://bitpay.com/api/invoice', [], [
            'price'    => round($params['amount'], 2)
            'currency' => 'USD'
        ]);
        
        $response = $request->sendå();
        */
        /*
        //$client = new Bitcoin_Utils_WebClient;
        //$response = $client->post('https://munroe.prestige55.org/civicrm/ticket/authenticate?username=TestAPIUser&password=apitest123');
        watchdog('andyw', 'response = <pre>' . print_r($response->getBody(), true) . "</pre>");
        */
    }

    /**
     * Get the current BTC exchange rate
     * @param  string $currency  currency to get exchange rate for
     * @return float
     */
    public static function getExchangeRate($currency = 'USD') {

        $client   = new \Guzzle\Service\Client();
        $request  = $client->get('https://bitpay.com/api/rates/' . $currency);
        $response = $request->send();

        if ($exchange = (object)$response->json() and isset($exchange->rate))
            return $exchange->rate;

        return 0;


    }

}
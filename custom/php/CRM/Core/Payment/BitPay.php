<?php

/**
 * Payment processor class for BitPay
 * @author  andyw@circle
 * @package uk.co.circleinteractive.payment.bitcoin
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

        watchdog('andyw', 'params = <pre>' . print_r($params, true) . '</pre>');
        
        if (!in_array($component, array('contribute', 'event')))
            CRM_Core_Error::fatal(ts('Component is invalid'));

        $config      = CRM_Core_Config::singleton();
        $transaction = &$_SESSION['bitpay_trxn'];
    
        $bitpayParams = array(
            'currency' => 'GBP',
            'apiKey'   => $this->_paymentProcessor['user_name']
        );

        # if ssl enabled, add notificationURL param
        if (bitcoin_ssl_enabled())
            $bitpayParams['notificationURL'] = CRM_Utils_System::url(
                'civicrm/payment/ipn', 
                'processor_id=' . $this->_paymentProcessor['id'], 
                true, null, false, true, false
            );

        # set redirect url
        $redirect_params = array(
            '_qf_ThankYou_display' => 1,
            'qfKey'                => $params['qfKey'],
            'processor'            => 'bitpay',
            'id'                   => $params['contributionID']
        );

        $querystring = array();
        foreach ($redirect_params as $key => $value)
            $querystring[] = $key . '=' . urlencode($value);

        $thankyou_url = CRM_Utils_System::url(
            $component == 'event' ? 'civicrm/event/register' : 'civicrm/contribute/transact',
            implode('&', $querystring), true, null, false, true
        ); 

        CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $bitpayParams);
        watchdog('andyw', 'bitpayParams = <pre>' . print_r($bitpayParams, true) . '</pre>');
        require_once "packages/bitpay/php-client/bp_lib.php";    
        $response = bpCreateInvoice($params['invoiceID'], 0.01, '', $bitpayParams);
        watchdog('andyw', 'response = <pre>' . print_r($response, true) . '</pre>');

        # check for errors
        if (is_string($response))
            CRM_Core_Error::fatal($response);

        if (isset($response['error'])) {
            $message = ts('An error occurred generating BitPay invoice.');
            CRM_Core_Error::debug_log_message($message . ': ' . print_r($response, true));
            CRM_Core_Error::fatal($message);
        }

        # write response to session object
        $transaction               = new StdClass;
        $transaction->response     = (object)$response;
        $transaction->thankyou_url = $thankyou_url;

        # save contribution_id
        $transaction->contribution_id = $params['contributionID'];

        # save response data
        BitPay_Payment_BAO_Transaction::save($response + array(
            'contribution_id' => $params['contributionID'],
            'bitpay_id'       => $response['id']
        ));

        watchdog('andyw', 'response = <pre>' . print_r($response, true) . '</pre>');

        # redirect to payment page
        CRM_Utils_System::redirect(
            CRM_Utils_System::url('civicrm/payment/bitpay', null, true, null, false, true, false)
        );

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

    /**
     * Handle payment notifications
     */
    public function handlePaymentNotification() {

        switch ($module = CRM_Utils_Array::value('mo', $_GET)) {
            case 'contribute':
            case 'event':
                $ipn = new BitPay_Payment_IPN();
                $ipn->main($module);
                break;
            default:
                CRM_Core_Error::debug_log_message(ts('Invalid or missing module name'));
        }

    }

}
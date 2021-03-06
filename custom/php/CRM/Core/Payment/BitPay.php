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

    /**
     * Initialize transaction
     * @param array  $params     data relating to the transaction
     * @param string $component  'contribute' or 'event'
     */
    public function doTransferCheckout(&$params, $component = 'contribute') {

        # watchdog('andyw', 'params = <pre>' . print_r($params, true) . '</pre>');
        
        if (!in_array($component, array('contribute', 'event')))
            CRM_Core_Error::fatal(ts('Component is invalid'));

        $config      = CRM_Core_Config::singleton();
        $transaction = &$_SESSION['bitpay_trxn'];
    
        $bitpayParams = array(
            'currency'  => $params['currencyID'],
            'apiKey'    => $this->_paymentProcessor['user_name']
        );

        # if ssl enabled, add notificationURL param
        if (bitcoin_ssl_enabled())
            $bitpayParams['notificationURL'] = CRM_Utils_System::url(
                'civicrm/payment/ipn', 
                'processor_id=' . $this->_paymentProcessor['id'] .
                '&mo='          . $component,
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

        # construct passthru variable
        $posData = array('c' => $params['contactID']);

        if ($component == 'contribute') {       
            
            # add related contact, if applicable
            if (isset($params['related_contact'])) {
                $posData['r'] = $params['related_contact'];
                if (isset($params['onbehalf_dupe_alert']))
                    $posData['d'] = $params['onbehalf_dupe_alert'];
            }
            
        }

        CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $bitpayParams);
        
        require_once "packages/bitpay/php-client/bp_lib.php";    
        $response = bpCreateInvoice($params['invoiceID'], $params['amount'], $posData, $bitpayParams);

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

        # update contribution with the invoice id bitpay supplied
        try {
            
            civicrm_api3('contribution', 'create', array(
                'id'                     => $params['contributionID'],
                'invoice_id'             => $response['id'],
                'contribution_status_id' => 2,
                'payment_instrument_id'  => bitcoin_setting('payment_instrument_id')
            ));
        
        } catch (CiviCRM_API3_Exception $e) {
            CRM_Core_Error::fatal(ts('Unable to update contribution id %1: %2', array(
                1 => $params['contributionID'],
                2 => $e->getMessage()
            )));
        } 

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
                
                $ipn     = new BitPay_Payment_IPN();
                $result  = $ipn->verifyNotification();
                
                if (is_string($result))
                    return CRM_Core_Error::debug_log_message(
                        bitcoin_extension_name() . ': ' . $result
                    );

                $ipn->main($module, $result);
                break;

            default:
                CRM_Core_Error::debug_log_message(ts('Invalid or missing module name'));
        
        }

    }

}
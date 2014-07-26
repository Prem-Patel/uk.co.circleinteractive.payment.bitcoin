<?php

/**
 * Payment processor class for use with bitcoind instance
 * @author andyw@circle
 */
class CRM_Core_Payment_BitcoinD extends CRM_Core_Payment_Bitcoin {

    /**
     * Machine name of payment processor
     * @var    string
     * @access protected
     * @static
     */
    protected static $name = 'BitcoinD';

    /**
     * Human-readable name of payment processor
     * @var    string
     * @access protected
     * @static
     */
    protected static $title = 'Bitcoin'; 

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
        'user_name_label'       => 'RPC User',
        'password_label'        => 'RPC Password',
        'url_site_default'      => 'http://localhost',
        'url_site_test_default' => 'http://localhost'
    );

    public function doTransferCheckout(&$params, $component = 'contribute') {
        
        # todo: probably initialize the transaction or something
        # then we need to pass that data through to the next page somehow, prb using $_SESSION
        
        # todo: get new address
        $new_address = '15amaYtP47Nmf00m1yFNtuKGsmKQUwTJX';
        $transaction = &$_SESSION['bitcoin_trxn'];

        # for bitcoind processor, create a new session object for the transaction and store price
        $transaction->amount = round($params['amount'] / $exchange_rate, 4);

        $url   = ($component == 'event' ? 'civicrm/event/register' : 'civicrm/contribute/transact');
        $query = "_qf_ThankYou_display=1&qfKey=" . $params['qfKey'];
        
        $transaction->thankyou_url = CRM_Utils_System::url($url, $query, true, null, false, true);
        $transaction->pay_address  = $new_address;
        
        # update contribution record, setting the trxn_id to the pay address
        try {
           
            civicrm_api3('contribution', 'create', array(
                'id'      => $params['contributionID'],
                'trxn_id' => $transaction->pay_address
            ));

        } catch (CiviCRM_API3_Exception $e) {
            CRM_Core_Error::fatal(ts('Unable to update contribution record: %1', array(
                1 => $e->getMessage()
            )));
        } 

        # redirect to payment page
        CRM_Utils_System::redirect(
            CRM_Utils_System::url('civicrm/payment/bitcoin', null, true, null, false, true, false)
        );
    }

    /**
     * Get the current BTC exchange rate
     * @param  string $currency  currency to get exchange rate for
     * @return float
     */
    public static function getExchangeRate($currency = 'USD') {
        if ($exchange_rates = bitcoin_setting('btc_exchange_rate') and isset($exchange_rates->$currency))
            return $exchange_rates->$currency->last;
    }
 
}
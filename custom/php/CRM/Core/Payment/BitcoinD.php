<?php

/**
 * Payment processor class for use with bitcoind instance
 * @author andyw@circle
 */
class CRM_Core_Payment_BitcoinD extends CRM_Core_Payment_Bitcoin {

    /**
     * Machine name of payment processor
     * @var string
     * @access protected
     * @static
     */
    protected static $name = 'BitcoinD';

    /**
     * Human-readable name of payment processor
     * @var string
     * @access protected
     * @static
     */
    protected static $title = 'Bitcoin'; 

    /**
     * Billing mode
     * @var string
     * @access protected
     * @static
     */
    protected static $mode = 'form';
    
    /**
     * Do we support recurring or not
     * @var bool
     */
    protected static $is_recur = false;


    public function doTransferCheckout(&$params, $component = 'contribute') {
        
        # todo: probably initialize the transaction or something
        # then we need to pass that data through to the next page somehow, prb using $_SESSION

        CRM_Utils_System::redirect(
            CRM_Utils_System::url('civicrm/payment/bitcoin', '', true, null, false, true, false)
        );
    }

 
}
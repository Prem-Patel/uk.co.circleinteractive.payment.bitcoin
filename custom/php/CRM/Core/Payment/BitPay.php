<?php

/**
 * Payment processor class for BitPay
 * @author andyw@circle
 */
class CRM_Core_Payment_BitPay extends CRM_Core_Payment_Bitcoin {
    
    /**
     * Machine name of payment processor
     * @var string
     * @access protected
     * @static
     */
    protected static $name = 'BitPay';

    /**
     * Human-readable name of payment processor
     * @var string
     * @access protected
     * @static
     */
    protected static $title = 'BitPay'; 

    /**
     * Billing mode
     * @var string
     * @access protected
     * @static
     */
    protected static $mode = 'notify';
    
    /**
     * Do we support recurring or not
     * @var bool
     */
    protected static $is_recur = false;


}
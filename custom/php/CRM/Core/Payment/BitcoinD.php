<?php

/**
 * Payment processor class for use with locally hosted bitcoind instance
 * @author andyw@circle
 */
class CRM_Core_Payment_BitcoinD extends CRM_Core_Payment {

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
    protected static $mode = 'notify';
    
    /**
     * Do we support recurring or not
     * @var bool
     */
    protected static $is_recur = false;
 
}
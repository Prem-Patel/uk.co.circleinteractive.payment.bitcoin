<?php

/**
 * Abstract class providing shared functionality for Bitcoin payment processors
 * @author andyw@circle
 */
abstract class CRM_Core_Payment_Bitcoin extends CRM_Core_Payment {

    /**
     * Constructor
     */
    public function __construct($mode, &$paymentProcessor) {

        $this->_mode             = $mode;
        $this->_paymentProcessor = $paymentProcessor;
        $this->_processorName    = self::$title;
                    
    }

    public function checkConfig() {
        
        if (!$this->_paymentProcessor['user_name']) 
            return ts('No username supplied for %1 payment processor', array(
                1 => self::$title
            ));
                
        return null;
    
    }
        
    public function doDirectPayment(&$params) {
        return null;    
    }

    public static function install() {

        $child = get_called_class();

        try {

            civicrm_api3('PaymentProcessorType', 'create', array(
                'name'         => $child::$name,
                'title'        => $child::$title,
                'class_name'   => $child,
                'billing_mode' => $child::$mode,
                'is_recur'     => (int)$child::$is_recur
            ));

        } catch (CiviCRM_API3_Exception $e) {
            CRM_Core_Error::fatal(ts('Unable to install payment processor: %1', array(
                1 => $e->getMessage()
            )));
        }

    }

    public static function uninstall() {

    }

}
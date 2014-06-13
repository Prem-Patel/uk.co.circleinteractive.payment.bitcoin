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

	public static function install() {

        try {

            civicrm_api3('PaymentProcessorType', 'create', array(
                'name'         => self::$name,
                'title'        => self::$title,
                'class_name'   => __CLASS__,
                'billing_mode' => self::$mode,
                'is_recur'     => (int)self::$is_recur
            ));

        } catch (CiviCRM_API3_Exception $e) {
            CRM_Core_Error::fatal(ts('Unable to delete payment processor: %1', array(
                1 => $e->getMessage()
            )));
        }

    }

    public static function uninstall() {



    }

}
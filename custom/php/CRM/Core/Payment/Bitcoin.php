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

    protected static function getTypeID() {
        
        # get name of the inheriting child class
        $child = self::className();

        try {

            return civicrm_api3('PaymentProcessorType', 'getvalue', array(
                'class_name' => $child,
                'return'     => 'id'
            ));

        } catch (CiviCRM_API3_Exception $e) {
            CRM_Core_Error::fatal(ts('Unable to get payment processor type id for %1: %2', array(
                1 => $child,
                2 => $e->getMessage()
            )));
        }

    }

    public static function install() {

        if (!self::isInstalled()) {

            # get name of the inheriting child class
            $child = self::className();

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

    }

    protected static function className() {
        return str_replace('CRM_Core_', '', get_called_class());
    }

    protected static function isInstalled() {

        try {

            return (bool)civicrm_api3('PaymentProcessorType', 'getcount', array(
                'class_name' => self::className()
            ));

        } catch (CiviCRM_API3_Exception $e) {
            CRM_Core_Error::fatal(ts('Unable to determine if %1 is installed: %2', array(
                1 => get_called_class(),
                2 => $e->getMessage()
            )));
        }

    }

    public static function uninstall() {

        if (self::isInstalled()) {

            try {

                civicrm_api3('PaymentProcessorType', 'delete', array(
                    'id' => self::getTypeID()
                ));

            } catch (CiviCRM_API3_Exception $e) {
                CRM_Core_Error::fatal(ts('Unable to uninstall payment processor %1: %2', array(
                    1 => get_called_class(),
                    2 => $e->getMessage()
                )));
            }

        }

    }

}
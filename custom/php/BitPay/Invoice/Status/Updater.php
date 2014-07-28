<?php

/**
 * Class to handle invoice update cron job
 * Used to complete invoices when callback notifications are disabled
 * @author andyw@circle
 */
class BitPay_Invoice_Status_Updater {

    public $errors = array(); 
   
    /**
     * Install the scheduled job
     * @access public
     * @static
     */
    public static function createJob() {
        
        # job should not exist, but check anyway
        if (self::jobExists())
            return;

        try {

            civicrm_api3('job', 'create', array(
                'name'          => ts('Update BitPay Invoices'),
                'description'   => ts('When using BitPay without SSL, this job updates the status of outstanding invoices whenever cron is run.'),
                'run_frequency' => 'Always',
                'api_entity'    => 'job',
                'api_action'    => 'update_bitpay_invoices',
                'parameters'    => '',
                'is_active'     => 0
            ));

        } catch (CiviCRM_API3_Exception $e) {
            CRM_Core_Error::fatal(ts('Unable to add scheduled job: %1', array(
                1 => $e->getMessage()
            )));
        }

    }

    /**
     * Delete the scheduled job
     * @access public
     * @static
     */
    public static function deleteJob() {

        try {

            civicrm_api3('job', 'delete', array(
                'id' => self::getJobID()
            ));

        } catch (CiviCRM_API3_Exception $e) {
            CRM_Core_Error::fatal(ts('Unable to delete scheduled job: %1', array(
                1 => $e->getMessage()
            )));
        }

    }

    /**
     * Disable the scheduled job
     * @access public
     * @static
     */
    public static function disableJob() {
        
        try {
           
            civicrm_api3('job', 'create', array(
                'id'        => self::getJobID(),
                'is_active' => 0
            ));

        } catch (CiviCRM_API3_Exception $e) {
            CRM_Core_Error::fatal(ts('Unable to delete scheduled job: %1', array(
                1 => $e->getMessage()
            )));
        } 

    }

    /**
     * Enable the scheduled job
     * @access public
     * @static
     */
    public static function enableJob() {
        
        try {
           
            civicrm_api3('job', 'create', array(
                'id'        => self::getJobID(),
                'is_active' => 1
            ));

        } catch (CiviCRM_API3_Exception $e) {
            CRM_Core_Error::fatal(ts('Unable to enable scheduled job: %1', array(
                1 => $e->getMessage()
            )));
        } 

    }

    protected function error($message) {
        $this->errors[] = $error;
    }

    public function getErrors() {
        return $this->errors;
    }

    /**
     * Get job id for scheduled job
     * @return int
     * @access protected
     * @static
     */
    protected static function getJobID() {
        
        try {

            return civicrm_api3('job', 'getvalue', array(
                'api_entity' => 'job',
                'api_action' => 'update_bitpay_invoices',
                'return'     => 'id'
            ));

        } catch (CiviCRM_API3_Exception $e) {
            CRM_Core_Error::fatal(ts('Unable to find scheduled job: %1', array(
                1 => $e->getMessage()
            )));
        }

    }

    /**
     * Get payment processor details for the contribution specified
     * @param  int $contribution_id  the id of the contribution
     * @return array  fully loaded payment processor array
     * @access protected
     * @static
     */
    protected static function getPaymentProcessor($contribution_id) {
        
        watchdog('andyw', 'contribution_id = ' . $contribution_id);

        try {

            $is_test = civicrm_api3('contribution', 'getvalue', array(
                'id'     => $contribution_id,
                'return' => 'is_test'
            ));

        } catch (CiviCRM_API3_Exception $e) {
            
            # except contribution api is massively broken in 4.4.5 it would seem
            if (!$is_test = CRM_Core_DAO::singleValueQuery("
                SELECT is_test FROM civicrm_contribution WHERE id = %1
            ", array(
                  1 => array($contribution_id, 'Positive')
               )
            ))
                CRM_Core_Error::fatal(ts('Unable to get contribution data for contribution id %1: %2', array(
                    1 => $contribution_id,
                    2 => $e->getMessage()
                )));

        }

        try {

            return civicrm_api3('PaymentProcessor', 'getsingle', array(
                'class_name' => 'Payment_BitPay',
                'is_test'    => $is_test
            ));

        } catch (CiviCRM_API3_Exception $e) {
            CRM_Core_Error::fatal(ts('Unable to get payment processor details in %1::%2: %3', array(
                1 => __CLASS__,
                2 => __METHOD__,
                3 => $e->getMessage()
            )));
        }

    }

    /**
     * Check if the job exists in the database
     * @access protected
     * @static
     */
    protected static function jobExists() {
        
        try {

            return (bool)civicrm_api3('job', 'getcount', array(
                'api_entity' => 'job',
                'api_action' => 'update_bitpay_invoices'
            ));

        } catch (CiviCRM_API3_Exception $e) {
            CRM_Core_Error::fatal(ts('Unable to find scheduled job: %1', array(
                1 => $e->getMessage()
            )));
        }

    }

    /**
     * Run scheduled job - update the status of outstanding BitPay invoices
     * @access public
     */
    public function run() {
        
        $outstanding = BitPay_Payment_BAO_Transaction::getOutstanding();
        foreach ($outstanding as $invoice)
            self::update($invoice['bitpay_id']);

    }

    /**
     * Update invoice
     * @param string $bitpay_id  the bitpay invoice id to update
     * @access public
     */
    public function update($bitpay_id) {

        if ($invoice = BitPay_Payment_BAO_Transaction::load(array(
            'bitpay_id' => $bitpay_id
        ))) {
            watchdog('andyw', 'invoice = <pre>' . print_r($invoice, true) . '</pre>');
            require_once "packages/bitpay/php-client/bp_lib.php";
            $processor = self::getPaymentProcessor($invoice['contribution_id']);    
            $response  = bpGetInvoice($bitpay_id, $processor['user_name']);

            if (is_string($response))
                CRM_Core_Error::fatal($response);

            if ($response) {
                BitPay_Payment_BAO_Transaction::save($response + array(
                    'contribution_id' => $invoice['contribution_id'],
                    'bitpay_id'       => $bitpay_id
                ));

                if ($response['status'] == 'complete') {
                    # todo: complete transaction using IPN class
                    watchdog('andyw', 'completing transaction');
                }

            }

        }

    }

}
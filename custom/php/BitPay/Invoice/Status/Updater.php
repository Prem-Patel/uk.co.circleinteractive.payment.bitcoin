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
     */
    public function run() {

        foreach (BitPay_Payment_BAO_Transaction::getOutstanding() as $bitpay_id)
            self::update($bitpay_id);

    }

    /**
     * Update invoice
     * @param string $bitpay_id  the bitpay invoice id to update
     */
    public function update($bitpay_id) {

        $invoice = BitPay_Payment_BAO_Transaction::load(array(
            'bitpay_id' => $bitpay_id
        ));

        $client   = new \Guzzle\Service\Client();
        $request  = $client->get('https://bitpay.com/api/invoice/' . $invoice['bitpay_id']);
        $response = $request->send();

        if ($updated_invoice = $response->json()) {
            BitPay_Payment_BAO_Transaction::save($updated_invoice + array(
                'contribution_id' => $invoice['contribution_id']
            ));
            # todo: if transaction completed, complete the transaction in Civi
        }

    }

}
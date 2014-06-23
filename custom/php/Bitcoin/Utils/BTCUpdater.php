<?php

/**
 * Class to handle functionality relating to Scheduled Task for updating
 * BTC exchange rate
 * @author andyw@circle
 */
class Bitcoin_Utils_BTCUpdater extends Bitcoin_Utils_WebClient {

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
                'name'          => ts('Update BTC exchange rate'),
                'description'   => ts('Update exchange rate between local currency and BTC, for use with Bitcoin payments'),
                'run_frequency' => 'Hourly',
                'api_entity'    => 'job',
                'api_action'    => 'update_btc_exchange_rate',
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
                'api_action' => 'update_btc_exchange_rate',
                'return'     => 'id'
            ));

        } catch (CiviCRM_API3_Exception $e) {
            CRM_Core_Error::fatal(ts('Unable to find scheduled job: %1', array(
                1 => $e->getMessage()
            )));
        }

    }

    protected function jobExists() {
        
        try {

            return (bool)civicrm_api3('job', 'getcount', array(
                'api_entity' => 'job',
                'api_action' => 'update_btc_exchange_rate'
            ));

        } catch (CiviCRM_API3_Exception $e) {
            CRM_Core_Error::fatal(ts('Unable to find scheduled job: %1', array(
                1 => $e->getMessage()
            )));
        }

    }

    /**
     * Run scheduled job - get BTC exchange rate from blockchain.info
     * for the default currency, store in civicrm_setting.
     * @todo support any enabled currencies - currently only supports the subset
     *       of currencies supported by blockchain.info and only queries default currency
     */
    public function run() {
        
        if ($response = $this->get('https://blockchain.info/ticker')) {
            
            $exchange_rate = json_decode($response);
            $currency      = CRM_Core_Config::singleton()->defaultCurrency; 

            # check if we have exchange rates for the default currency,
            # if not, raise an error
            if (!isset($exchange_rate->$currency))
                return $this->error(ts(
                    'Unsupported currency: %1 - cannot update BTC exchange rate.',
                    array(
                        1 => $currency
                    )
                ));

            # store in civicrm_setting if no errors
            bitcoin_setting('btc_exchange_rate', $exchange_rate->$currency->last);

        }
    }

}
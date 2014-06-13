<?php

/**
 * Implementation of hook_civicrm_config
 */
function bitcoin_civicrm_config(&$config) {
    bitcoin_init_path();
}

/**
 * Implementation of hook_civicrm_disable
 */
function bitcoin_civicrm_disable() {
    
    # todo: do not want to even get here (or failing that, at least abort) 
    # if either payment processor in use - how to do that?

    # initialize path to allow class autoloading
    bitcoin_init_path();

    try {
        # disable scheduled task
        CRM_Utils_BTCUpdater::disableJob();

    } catch (CRM_Core_Exception $e) {
        CRM_Core_Error::fatal(ts('An error occurred disabling extension: %1', array(
            1 => $e->getMessage()
        )));
    }

}

/**
 * Implementation of hook_civicrm_enable
 */
function bitcoin_civicrm_enable() {

    # initialize path to allow class autoloading
    bitcoin_init_path();

    try {
        # enable scheduled task
        CRM_Utils_BTCUpdater::enableJob();

    } catch (CRM_Core_Exception $e) {
        CRM_Core_Error::fatal(ts('An error occurred enabling extension: %1', array(
            1 => $e->getMessage()
        )));
    }
}

/**
 * Implementation of hook_civicrm_install
 */
function bitcoin_civicrm_install() {
    
    # initialize path to allow class autoloading
    bitcoin_init_path();
    
    try {
        
        CRM_Utils_BTCUpdater::createJob();    # create scheduled task for updating BTC exchange rate
        CRM_Core_Payment_BitPay::install();   # install BitPay payment processor
        CRM_Core_Payment_BitcoinD::install(); # install BitcoinD payment processor
    
    } catch (CRM_Core_Exception $e) {
        CRM_Core_Error::fatal(ts('An error occurred installing extension: %1', array(
            1 => $e->getMessage()
        )));
    }

}

/**
 * Implementation of hook_civicrm_uninstall
 */
function bitcoin_civicrm_uninstall() {

    # initialize path to allow class autoloading
    bitcoin_init_path();

    try {
        
        CRM_Utils_BTCUpdater::deleteJob();      # delete scheduled task for updating BTC exchange rate
        CRM_Core_Payment_BitPay::uninstall();   # uninstall BitPay payment processor
        CRM_Core_Payment_BitcoinD::uninstall(); # uninstall BitcoinD payment processor
    
    } catch (CRM_Core_Exception $e) {
        CRM_Core_Error::fatal(ts('An error occurred uninstalling extension: %1', array(
            1 => $e->getMessage()
        )));        
    }

}

/**
 * Job api callback
 */
function civicrm_api3_job_update_btc_exchange_rate($params) {
    
    $updater = new CRM_Utils_BTCUpdater();
    $updater->run();

    if ($errors = $updater->getErrors())
        return civicrm_api3_create_error(
            ts('Unable to update BTC exchange rate: %1', array(
                1 => "\n" . implode("\n", $errors)
            ))
        );

    return civicrm_api3_create_success(
        ts('Succesfully updated BTC exchange rate at %1', array(
            1 => date('Y-m-d H:i:s')
        ))
    );

}

/**
 * Function to determine whether either of our payment processors are in use on any contribution 
 * or event pages.
 * @return bool
 */
function bitcoin_in_use() {
    return false; # for now
}

/**
 * Initialize include path
 */
function bitcoin_init_path() {
    set_include_path(__DIR__ . '/custom/php/' . PATH_SEPARATOR . get_include_path());
}

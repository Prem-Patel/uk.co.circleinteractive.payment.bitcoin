<?php

/**
 * Implementation of hook_civicrm_config
 */
function bitcoin_civicrm_config(&$config) {
    set_include_path(__DIR__ . '/custom/php/' . PATH_SEPARATOR . get_include_path());
}

/**
 * Implementation of hook_civicrm_disable
 */
function bitcoin_civicrm_disable() {
    # todo: do not want to even get here (or if not, at least abort) 
    # if either payment processor in use - how to do that?
    CRM_Utils_BTCUpdater::disableJob();   # disable scheduled task
}

/**
 * Implementation of hook_civicrm_enable
 */
function bitcoin_civicrm_enable() {
    CRM_Utils_BTCUpdater::enableJob();    # enable scheduled task
}

/**
 * Implementation of hook_civicrm_install
 */
function bitcoin_civicrm_install() {
    CRM_Utils_BTCUpdater::createJob();    # create scheduled task for updating BTC exchange rate
    CRM_Core_Payment_BitPay::install();   # install BitPay payment processor
    CRM_Core_Payment_BitcoinD::install(); # install BitcoinD payment processor
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function bitcoin_civicrm_uninstall() {
    CRM_Utils_BTCUpdater::deleteJob();      # delete scheduled task for updating BTC exchange rate
    CRM_Core_Payment_BitPay::uninstall();   # uninstall BitPay payment processor
    CRM_Core_Payment_BitcoinD::uninstall(); # uninstall BitcoinD payment processor
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
function _bitcoin_in_use() {
    return false; # for now
}


<?php

/**
 * Bitcoin payment processor extension
 * @author  andyw@circle
 * @package com.uk.andyw.payment.bitcoin
 */

/**
 * Implementation of hook_civicrm_buildForm
 */
function bitcoin_civicrm_buildForm($formName, &$form) {
    
    switch ($formName) {
        
        # on event registration pages + contribution pages
        case 'CRM_Event_Form_Registration_Register':
        # todo: contribution pages

            # todo: check if this event uses BitcoinD processor and if so, do this ...
            $extension_name = basename(__DIR__);
            $resources      = CRM_Core_Resources::singleton();

            # add styles
            $resources->addStyleFile(
                $extension_name, 
                'custom/css/paymentBlock.css',
                CRM_Core_Resources::DEFAULT_WEIGHT,
                'html-header'
            );

            # load underscore.js on versions lower than 4.5 - think 4.5 includes lodash by default, but need to check
            if (bitcoin_crm_version() < 4.5)
                $resources->addScriptFile('civicrm', 'packages/backbone/underscore.js', 110, 'html-header', FALSE);

            # add javascript
            $resources->addScriptFile($extension_name, 'custom/js/paymentBlock.js');

            # add settings
            $resources->addSetting(array(
                'btc_processor_ids' => bitcoin_get_processor_ids('BitcoinD'),
                'btc_exchange_rate' => bitcoin_setting('btc_exchange_rate')
            ));

            break; 
    
    }

}

/**
 * Implementation of hook_civicrm_config
 */
function bitcoin_civicrm_config(&$config) {
    bitcoin_init();
}

/**
 * Implementation of hook_civicrm_disable
 */
function bitcoin_civicrm_disable() {
    
    # todo: do not want to even get here (or failing that, at least abort) 
    # if either payment processor in use - how to do that?

    # initialize path to allow class autoloading
    bitcoin_init();

    try {
        # disable scheduled task
        Bitcoin_Utils_BTCUpdater::disableJob();

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
    bitcoin_init();

    try {
        # enable scheduled task
        Bitcoin_Utils_BTCUpdater::enableJob();

    } catch (CRM_Core_Exception $e) {
        CRM_Core_Error::fatal(ts('An error occurred enabling extension: %1', array(
            1 => $e->getMessage()
        )));
    }

    # update exchange rate
    $updater = new Bitcoin_Utils_BTCUpdater();
    $updater->run();

    if ($errors = $updater->getErrors())
        foreach ($errors as $error)
            CRM_Core_Error::debug_log_message($error, true);  

}

/**
 * Implementation of hook_civicrm_xmlMenu
 */
function bitcoin_civicrm_xmlMenu(&$files) {
    $files[] = __DIR__ . '/custom/xml/routes.xml';
}

/**
 * Implementation of hook_civicrm_install
 */
function bitcoin_civicrm_install() {
    
    # initialize path to allow class autoloading
    bitcoin_init();
    
    try {
        
        Bitcoin_Utils_BTCUpdater::createJob(); # create scheduled task for updating BTC exchange rate
        CRM_Core_Payment_BitPay::install();    # install BitPay payment processor
        CRM_Core_Payment_BitcoinD::install();  # install BitcoinD payment processor
    
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
    bitcoin_init();

    try {
        
        Bitcoin_Utils_BTCUpdater::deleteJob();  # delete scheduled task for updating BTC exchange rate
        CRM_Core_Payment_BitPay::uninstall();   # uninstall BitPay payment processor
        CRM_Core_Payment_BitcoinD::uninstall(); # uninstall BitcoinD payment processor
    
    } catch (CRM_Core_Exception $e) {
        CRM_Core_Error::fatal(ts('An error occurred uninstalling extension: %1', array(
            1 => $e->getMessage()
        )));        
    }

}



/**
 * Get Civi version as 1-decimal-place float - eg: 4.4
 * @return float
 */
function bitcoin_crm_version() {
    $version = explode('.', ereg_replace('[^0-9\.]','', CRM_Utils_System::version()));
    return (float)($version[0] . '.' . $version[1]);   
}

/**
 * Get payment processor instance ids for the payment processor specified by $type
 * @param  $type optional type, defaults to 'BitcoinD'
 * @return array an array of processor ids
 */
function bitcoin_get_processor_ids($type = 'BitcoinD') {

    $ids = array();

    try {

        $result = civicrm_api3('PaymentProcessor', 'get', array(
            'class_name' => 'Payment_' . $type,
            'return.id'  => 1
        ));

    } catch (CiviCRM_API3_Exception $e) {
        CRM_Core_Error::fatal(ts('Unable to get payment processor ids for class_name Payment_%1: %2', array(
            1 => $type,
            2 => $e->getMessage()
        )));
    }

    foreach ($result['values'] as $processor)
        $ids[] = $processor['id'];

    return $ids;

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
 * Initialize autoloader / include path
 */
function bitcoin_init() {
    
    # initialize include path
    set_include_path(__DIR__ . '/custom/php/' . PATH_SEPARATOR . get_include_path());

    # initialize template path
    $templates = &CRM_Core_Smarty::singleton()->template_dir;
    
    if (!is_array($templates))
        $templates = array($templates);
    
    array_unshift($templates, __DIR__ . '/custom/templates');

    # register autoloader for owned classes
    spl_autoload_register(function($class) {
        if (strpos($class, 'Bitcoin_') === 0)
            if ($file = stream_resolve_include_path(strtr($class, '_', '/') . '.php')) 
                require_once $file;
    });

}

/**
 * Set and retrieve extension settings - has jQuery-like optional second argument which
 * will trigger a 'set' operation if supplied, otherwise will perform a 'get' operation
 * @param  string $key   key to get/set
 * @param  string $value optional value if performing a 'set' operation
 * @return mixed         the setting value if performing a 'get' operation
 */
function bitcoin_setting($key, $value = null) {
    if ($value)
        return CRM_Core_BAO_Setting::setItem($value, basename(__DIR__), $key);
    return CRM_Core_BAO_Setting::getItem(basename(__DIR__), $key);
}

/**
 * Job api callback
 */
function civicrm_api3_job_update_btc_exchange_rate() {
    
    $updater = new Bitcoin_Utils_BTCUpdater();
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

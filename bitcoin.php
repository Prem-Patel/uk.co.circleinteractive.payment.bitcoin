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
                
            # check if this event uses a bitcoin processor and if so ..
            if ($processor_name = bitcoin_processor_enabled('event', $form->_eventId)) {

                $resources = CRM_Core_Resources::singleton();

                # load underscore.js on versions lower than 4.5 - think 4.5 includes lodash by default, but need to check
                if (bitcoin_crm_version() < 4.5)
                    $resources->addScriptFile('civicrm', 'packages/backbone/underscore.js', 110, 'html-header', false);
                
                # add javascript
                $resources->addScriptFile(bitcoin_extension_name(), 'custom/js/convertPrices.js');

                # add settings
                $resources->addSetting(array(
                    'btc_processor_ids' => bitcoin_get_processor_ids(),
                    'btc_exchange_rate' => bitcoin_get_exchange_rate(
                        bitcoin_get_currency('event', $form->_eventId), 
                        $processor_name # if both in use, will default to BitPay - which is what we want as it supports more currencies
                    )
                ));

            }

            break; 
    
        case 'CRM_Event_Form_Registration_Confirm':

            if (bitcoin_processor_enabled('event', $form->_values['event']['id'])) {
          
                $resources = CRM_Core_Resources::singleton();

                # load underscore.js on versions lower than 4.5
                if (bitcoin_crm_version() < 4.5)
                    $resources->addScriptFile('civicrm', 'packages/backbone/underscore.js', 110, 'html-header', false);

                # add javascript
                $resources->addScriptFile(bitcoin_extension_name(), 'custom/js/confirm.js');

                # add settings
                $is_bitpay = in_array(
                    $form->_paymentProcessor['id'], 
                    bitcoin_get_processor_ids('BitPay')
                );

                $exchange_rate = bitcoin_get_exchange_rate(
                    bitcoin_get_currency('event', $form->_values['event']['id']),
                    $is_bitpay ? 'BitPay' : 'BitcoinD'
                );

                $resources->addSetting(array(
                    'btc_exchange_rate' => $exchange_rate
                ));

                # create a new session object for the transaction and store price - we want to make
                # make sure the price displayed to the user is the price they get charged by the 
                # payment processor (in case exchange rate gets updated between page render and submission)
                $_SESSION['bitcoin_trxn'] = (object)array(
                    'amount' => round($form->_totalAmount / $exchange_rate, 4)
                );

            }

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

    # initialize path + class autoloading
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

function bitcoin_extension_name() {
    return basename(__DIR__);
}

function bitcoin_get_currency($entity_type, $entity_id) {

    try {

        return civicrm_api3($entity_type, 'getvalue', array(
            'id'     => $entity_id,
            'return' => 'currency',
        ));

    } catch (CiviCRM_API3_Exception $e) {
        CRM_Core_Error::fatal(ts('Unable to get currency for %1 id %2: %3', array(
            1 => $entity_type,
            2 => $entity_id,
            3 => $e->getMessage()
        )));
    }

}

function bitcoin_get_exchange_rate($currency, $processor = 'BitcoinD') {
    
    if ($processor == 'BitPay')
        return CRM_Core_Payment_BitPay::getExchangeRate($currency);
    return CRM_Core_Payment_BitcoinD::getExchangeRate($currency);

}

/**
 * Get payment processor instance ids for the payment processor specified by $type
 * @param  $type optional type, defaults to BitcoinD
 * @return array an array of processor ids
 */
function bitcoin_get_processor_ids($type = 'Both') {

    # todo: implement static cache

    $ids = array();

    foreach ($type == 'Both' ? array('BitPay', 'BitcoinD') : array($type) as $processor) {

        try {

            $results = civicrm_api3('PaymentProcessor', 'get', array(
                'class_name' => 'Payment_' . $processor,
                'return.id'  => 1
            ));

        } catch (CiviCRM_API3_Exception $e) {
            CRM_Core_Error::fatal(ts('Unable to get payment processor ids for class_name Payment_%1: %2', array(
                1 => $type,
                2 => $e->getMessage()
            )));
        }

        foreach ($results['values'] as $result)
            $ids[] = $result['id'];

    }

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
 * Given an entity and an entity id, determine whether a bitcoin processor is enabled
 * @param  string $entity     the entity type - 'Event' or 'ContributionPage'
 * @param  int    $entity_id  the entity id of the entity to query
 * @return mixed              string (type of processor) or false if not enabled
 */
function bitcoin_processor_enabled($entity, $entity_id) {

    switch ($entity) {
        
        case 'event':
        case 'contributionpage':
            
            try {
                $result = civicrm_api3($entity, 'getsingle', array(
                    'id' => $entity_id
                )); 
            } catch (CiviCRM_API3_Exception $e) {
                CRM_Core_Error::fatal(ts('Unable to get event information for event id %1: %2', array(
                    1 => $entity_id,
                    2 => $e->getMessage()
                )));
            }

            if (!is_array($result['payment_processor']))
                $result['payment_processor'] = array($result['payment_processor']);

            foreach (array('BitPay', 'BitcoinD') as $processor_name)
                foreach (bitcoin_get_processor_ids($processor_name) as $processor_id)
                    if (in_array($processor_id, $result['payment_processor']))
                        return $processor_name;

            return false;

        default:
            CRM_Core_Error::fatal(ts("Unrecognized entity type, '%1' in %2 at line %3", array(
                1 => $entity,
                2 => __FUNCTION__,
                3 => __LINE__
            )));           
    
    }

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

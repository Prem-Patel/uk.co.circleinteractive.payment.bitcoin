<?php

/**
 * Bitcoin payment processor extension
 * @author  andyw@circle
 * @package uk.co.circleinteractive.payment.bitcoin
 */

/**
 * Implementation of hook_civicrm_buildForm
 */
function bitcoin_civicrm_buildForm($formName, &$form) {
    # watchdog('andyw', 'buildform = <pre>' . print_r($form, true) . '</pre>');
    switch ($formName) {
        
        # on payment processor admin form
        case 'CRM_Admin_Form_PaymentProcessor':
            
            if (!isset($_GET['pp']) and !isset($_GET['id']))
                return;

            if (!isset($_GET['action']))
                return;

            $show_warning = false;

            # if bitpay and no ssl enabled, display ssl warning
            if ($_GET['action'] == 'add')
                if ($_GET['pp'] == bitcoin_get_processor_type_id('BitPay') and !bitcoin_ssl_enabled())
                    $show_warning = true;

            if ($_GET['action'] == 'update')
                if (in_array($_GET['id'], bitcoin_get_processor_ids('BitPay')) and !bitcoin_ssl_enabled())
                    $show_warning = true;

            if ($show_warning)
                CRM_Core_Resources::singleton()->addScriptFile(
                    bitcoin_extension_name(), 'custom/js/bitpay-ssl-warning.js'
                );

            break;

        # on contribution pages
        case 'CRM_Contribute_Form_Contribution_Main':

            # check if contribution page has a bitcoin processor enabled
            if ($processor_name = bitcoin_processor_enabled('contributionPage', $form->_id)) {

                $resources = CRM_Core_Resources::singleton();

                # load underscore.js on versions lower than 4.5 - think 4.5 includes lodash by default, but need to check
                if (bitcoin_crm_version() < 4.5)
                    $resources->addScriptFile('civicrm', 'packages/backbone/underscore.js', 110, 'html-header', false);
                
                # add javascript
                $resources->addScriptFile(bitcoin_extension_name(), 'custom/js/convert.js');

                # add settings
                $resources->addSetting(array(
                    'num_processors'    => bitcoin_count_payment_processors('contributionPage', $form->_id),
                    'btc_processor_ids' => bitcoin_get_processor_ids(),
                    'btc_exchange_rate' => bitcoin_get_exchange_rate(
                        bitcoin_get_currency('contributionPage', $form->_id), 
                        $processor_name # if both in use, will default to querying exchange rate from BitPay
                    ),
                ));
            }

            break;

        # on contribution confirmation page
        case 'CRM_Contribute_Form_Contribution_Confirm':

            # if a bitcoin processor is the selected processor
            if (in_array($form->_paymentProcessor['id'], bitcoin_get_processor_ids())) {

                $resources = CRM_Core_Resources::singleton();

                # load underscore.js on versions lower than 4.5
                if (bitcoin_crm_version() < 4.5)
                    $resources->addScriptFile('civicrm', 'packages/backbone/underscore.js', 110, 'html-header', false);

                # add javascript
                $resources->addScriptFile(bitcoin_extension_name(), 'custom/js/contribution-confirm.js');

                # add settings
                $is_bitpay = in_array(
                    $form->_paymentProcessor['id'], 
                    bitcoin_get_processor_ids('BitPay')
                );

                $exchange_rate = bitcoin_get_exchange_rate(
                    bitcoin_get_currency('contributionPage', $form->_id),
                    $is_bitpay ? 'BitPay' : 'BitcoinD'
                );

                $resources->addSetting(array(
                    'btc_exchange_rate' => $exchange_rate
                ));

            }

            break;

        # on event registration pages ..
        case 'CRM_Event_Form_Registration_Register':
                
            # check if event uses a bitcoin processor and if so ..
            if ($processor_name = bitcoin_processor_enabled('event', $form->_eventId)) {

                $resources = CRM_Core_Resources::singleton();

                # load underscore.js on versions lower than 4.5 - think 4.5 includes lodash by default, but need to check
                if (bitcoin_crm_version() < 4.5)
                    $resources->addScriptFile('civicrm', 'packages/backbone/underscore.js', 110, 'html-header', false);
                
                # add javascript
                $resources->addScriptFile(bitcoin_extension_name(), 'custom/js/convert.js');

                # add settings
                $resources->addSetting(array(
                    'num_processors'    => bitcoin_count_payment_processors('event', $form->_eventId),
                    'btc_processor_ids' => bitcoin_get_processor_ids(),
                    'btc_exchange_rate' => bitcoin_get_exchange_rate(
                        bitcoin_get_currency('event', $form->_eventId), 
                        $processor_name # if both in use, will default to querying exchange rate from BitPay
                    )
                ));

            }

            break; 
    
        
        # event confirmation page
        case 'CRM_Event_Form_Registration_Confirm':

            # if a bitcoin processor is the selected processor
            if (in_array($form->_paymentProcessor['id'], bitcoin_get_processor_ids())) {
          
                $resources = CRM_Core_Resources::singleton();

                # load underscore.js on versions lower than 4.5
                if (bitcoin_crm_version() < 4.5)
                    $resources->addScriptFile('civicrm', 'packages/backbone/underscore.js', 110, 'html-header', false);

                # add javascript
                $resources->addScriptFile(bitcoin_extension_name(), 'custom/js/event-confirm.js');

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

            }

            break;

        # thankyou pages
        case 'CRM_Event_Form_Registration_ThankYou':
            
            if (!isset($_GET['processor']) or !isset($_GET['id']))
                return;

            # when bitpay is the processor
            if ($_GET['processor'] == 'bitpay') {
                /*
                if (isset($_GET['frame']) and $_GET['frame']) {
                    
                    # break out of BitPay iframe
                    $redirectURL = CRM_Utils_System::url(
                        $component == 'event' ? 'civicrm/event/register' : 'civicrm/contribute/transact',
                        '_qf_ThankYou_display=1&qfKey=' . $_GET['qfKey'] . '&processor=bitpay&id=' . $_GET['id'], 
                        true, null, false, true
                    );

                    echo '<html><head><script type="text/javascript">' . 
                         'top.location.href = "' . $redirectURL . '";' .
                         '</script></head><body></body></html>';
                    
                    CRM_Utils_System::civiExit();
                
                }
                */
                
                $transaction = &$_SESSION['bitpay_trxn'];

                # check the contribution id supplied matches the one
                # in stored session data
                if ($transaction->contribution_id != $_GET['id'])
                    CRM_Core_Error::fatal(ts(
                        'Failed integrity check while updating invoice status. ' . 
                        'Please contact the site administrator.'
                    ));

                # perform an additional invoice status update
                $invoice = new BitPay_Invoice_Status_Updater();
                $invoice->update($transaction->response->id);
            
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
 * Implementation of hook_civicrm_install
 */
function bitcoin_civicrm_install() {
    
    # initialize path to allow class autoloading
    bitcoin_init();
    
    try {
        
        Bitcoin_Utils_BTCUpdater::createJob();      # create scheduled task for updating BTC exchange rate
        BitPay_Invoice_Status_Updater::createJob(); # create scheduled task for updating outstanding BitPay invoices
        CRM_Core_Payment_BitPay::install();         # install BitPay payment processor
        CRM_Core_Payment_BitcoinD::install();       # install BitcoinD payment processor
    
    } catch (CRM_Core_Exception $e) {
        CRM_Core_Error::fatal(ts('An error occurred installing extension: %1', array(
            1 => $e->getMessage()
        )));
    }

}

/**
 * Implementation of hook_civicrm_postProcess
 */
function bitcoin_civicrm_postProcess($formName, &$form) {

    switch ($formName) {
        
        # payment processor admin form
        case 'CRM_Admin_Form_PaymentProcessor':

            if (!isset($form->_defaultValues['class_name']))
                return;

            # BitPay
            if ($form->_defaultValues['class_name'] == 'Payment_BitPay') {
                
                # install db table(s) if not already installed
                BitPay_Payment_BAO_Transaction::installSchema();
                
                # disable or enable invoice status cron job based on whether ssl is enabled.
                # when ssl enabled, we will receive ipn callbacks from bitpay so don't need the cron job
                $action = bitcoin_ssl_enabled() ? 'disableJob' : 'enableJob';
                BitPay_Invoice_Status_Updater::$action();

            }
            break;

    }

}

/**
 * Implementation of hook_civicrm_uninstall
 */
function bitcoin_civicrm_uninstall() {

    # initialize path to allow class autoloading
    bitcoin_init();

    try {
        
        Bitcoin_Utils_BTCUpdater::deleteJob();      # delete scheduled task for updating BTC exchange rate
        BitPay_Invoice_Status_Updater::deleteJob(); # delete scheduled task for updating outstanding BitPay invoices
        CRM_Core_Payment_BitPay::uninstall();       # uninstall BitPay payment processor
        CRM_Core_Payment_BitcoinD::uninstall();     # uninstall BitcoinD payment processor
    
    } catch (CRM_Core_Exception $e) {
        CRM_Core_Error::fatal(ts('An error occurred uninstalling extension: %1', array(
            1 => $e->getMessage()
        )));        
    }

}

/**
 * Implementation of hook_civicrm_xmlMenu
 */
function bitcoin_civicrm_xmlMenu(&$files) {
    $files[] = __DIR__ . '/custom/xml/routes.xml';
}

/**
 * Given an entity and an entity id, return how many payment processors are enabled
 * @param  string $entity     the entity type - 'Event' or 'ContributionPage'
 * @param  int    $entity_id  the entity id of the entity to query
 * @return int                number of enabled payment processors
 */
function bitcoin_count_payment_processors($entity, $entity_id) {

    switch ($entity) {
        
        case 'event':
        case 'contributionPage':
            
            try {
                $result = civicrm_api3($entity, 'getsingle', array(
                    'id' => $entity_id
                )); 
            } catch (CiviCRM_API3_Exception $e) {
                CRM_Core_Error::fatal(ts('Unable to get payment processor count for %1 id %2: %3', array(
                    1 => $entity,
                    2 => $entity_id,
                    3 => $e->getMessage()
                )));
            }

            if (!$result['payment_processor'])
                return 0;

            if (!is_array($result['payment_processor']))
                $result['payment_processor'] = array($result['payment_processor']);

            return count($result['payment_processor']);

        default:
            CRM_Core_Error::fatal(ts("Unrecognized entity type, '%1' in %2 at line %3", array(
                1 => $entity,
                2 => __FUNCTION__,
                3 => __LINE__
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
    return 'uk.co.circleinteractive.payment.bitcoin';
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
 * Get payment processor type id for the specified processor
 * @param  string $processor - one of 'BitPay', 'BitcoinD'
 * @return int
 */
function bitcoin_get_processor_type_id($processor) {
    
    try {
        
        return civicrm_api3('PaymentProcessorType', 'getvalue', array(
            'class_name' => 'Payment_' . $processor,
            'return'     => 'id'
        ));

    } catch (CiviCRM_API3_Exception $e) {
        throw new CRM_Core_Exception(ts('Unable to get payment processor type id for class_name Payment_%1: %2', array(
            1 => $processor,
            2 => $e->getMessage()
        )));
    }   

}

/**
 * Function to determine whether either of our payment processors are in use on any contribution 
 * or event pages.
 * @return bool
 */
function bitcoin_in_use($type = 'Both') {

    if (!$ids = bitcoin_get_processor_ids($type))
        return false;

    # todo: may speed up things a little if we add a registration_end_date >= NOW() check on events

    foreach ($ids as $id)
        $conditions[] = sprintf("payment_processor LIKE '%s%d%s'", chr(1), $id, chr(1));

    $conditions = implode(' OR ', $conditions);

    return (bool)CRM_Core_DAO::singleValueQuery("SELECT 1 FROM civicrm_event WHERE $conditions") and
           (bool)CRM_Core_DAO::singleValueQuery("SELECT 1 FROM civicrm_contribution_page WHERE $conditions");

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

    # composer autoloader
    require_once "vendor/autoload.php";

    # register autoloader for owned classes
    spl_autoload_register(function($class) {
        if (strpos($class, 'Bitcoin_') === 0 or strpos($class, 'BitPay_') === 0)
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
        case 'contributionPage':
            
            try {
                $result = civicrm_api3($entity, 'getsingle', array(
                    'id' => $entity_id
                )); 
            } catch (CiviCRM_API3_Exception $e) {
                CRM_Core_Error::fatal(ts('Unable to get event information for %1 id %2: %3', array(
                    1 => $entity,
                    2 => $entity_id,
                    3 => $e->getMessage()
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
        return CRM_Core_BAO_Setting::setItem($value, bitcoin_extension_name(), $key);
    return CRM_Core_BAO_Setting::getItem(bitcoin_extension_name(), $key);
}

/**
 * Check if site has ssl enabled
 * @return bool
 */
function bitcoin_ssl_enabled() {
    # just because we might not be on ssl right now, doesn't mean ssl is not enabled
    # but this is the best check I could come up with. $config->enableSSL doesn't count
    # for anything either, so this will have to do for the moment.
    return (!empty($_SERVER['HTTPS']) and $_SERVER['HTTPS'] !== 'off') or $_SERVER['SERVER_PORT'] == 443;
}

/**
 * Job api callback to update btc exchange rate
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

/**
 * Job api callback for updating outstanding BitPay invoices
 */
function civicrm_api3_job_update_bitpay_invoices() {

    $updater = new BitPay_Invoice_Status_Updater();
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

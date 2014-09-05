<?php 

/**
 * Page class for BitPay processor payment page
 * @author  andyw@circle
 * @package uk.co.circleinteractive.payment.bitcoin
 */
class BitPay_Payment_Page extends CRM_Core_Page {

    /**
     * Page run - add resources, assign templates vars, then call parent run method
     */
    public function run() {
        
        $resources = CRM_Core_Resources::singleton();

        # add styles
        $resources->addStyleFile(
            bitcoin_extension_name(), 
            'custom/css/bitpay-payment.css',
            CRM_Core_Resources::DEFAULT_WEIGHT,
            'html-header'
        );

        # add javascript
        $resources->addScriptFile(bitcoin_extension_name(), 'custom/js/bitpay-payment.js');

        # add smarty vars
        $transaction = &$_SESSION['bitpay_trxn'];
        $this->assign('response', $transaction->response);

        $resources->addSetting(array(
            'thankyou_url' => $transaction->thankyou_url
        ));

        parent::run();
 
    }

};
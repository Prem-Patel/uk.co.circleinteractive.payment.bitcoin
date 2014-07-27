<?php 

/**
 * Form class for BitPay processor payment page
 * @author  andyw@circle
 * @package uk.co.circleinteractive.payment.bitcoin
 */
class BitPay_Payment_Form extends CRM_Core_Form {

    /**
     * buildForm - add resources, assign templates vars, then call parent run method
     */
    public function buildQuickForm() {
        
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

        $transaction = &$_SESSION['bitpay_trxn'];

        $this->assign('response', $transaction->response);
        $this->assign('thankyou_url', $transaction->thankyou_url);
           
    }

   /*
    * postProcess - form is submitted automatically via javascript
    */
    public function postProcess() {
        
        $post        = $this->controller->exportValues();
        $transaction = &$_SESSION['bitpay_trxn'];

        if ($transaction->response->id != $post['bitpay_id'])
            throw new CRM_Core_Exception(ts(
                'Failed integrity check while updating invoice status. ' . 
                'Please contact the site administrator.'
            ));

        # get invoice update from bitpay
        BitPay_Invoice_Status_Updater::update($post['bitpay_id']);

        # load updated invoice
        $invoice = BitPay_Payment_BAO_Transaction::load($post['bitpay_id']);

        # todo: check invoice status and if completed, complete payment in Civi 

    
    }

};
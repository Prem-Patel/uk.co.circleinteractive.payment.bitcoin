/**
 * Javascript to display ssl warning on the payment processor
 * admin form for BitPay
 * andyw@circle, 26/07/2014
 */

(function() {

    // doc load ..
    cj(function() {

        cj('.crm-paymentProcessor-form-block table.form-layout-compressed:first').after(
            '<div class="bitpay-ssl-warning messages status no-popup">' +
              'SSL is not enabled on your site and IPN callbacks will be disabled as a result. ' + 
              'Payment processing will continue to work, but you should configure cron to run as often as possible ' + 
              'to ensure payments are completed promptly.' +
            '</div>'
        );

    });


}).call(this);
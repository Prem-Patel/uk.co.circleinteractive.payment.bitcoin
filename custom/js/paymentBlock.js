/**
 * Javascript for payment block - andyw@circle, 24/06/2014
 */
(function() {

    // doc load ..
    cj(function() {

        cj('input[name="payment_processor"]').change(function() {
            
            // if the selected id is not a BitcoinD id then ..
            if (!_.contains(CRM.btc_processor_ids, cj(this).val())) {
                
                // if the payment block exists on the page, hide it
                if (cj('#bitcoin-payment-block').length)
                    cj('#bitcoin-payment-block').hide();
                
                // that is all
                return;
            
            }

            // selected id is a BitcoinD processor id ..
            // if the payment block is not already on the page, add it
            if (!cj('#bitcoin-payment-block').length) {

                var qfKey = cj('form#Register input[name=qfKey]').val();
                var data  = {
                    op: 'initialize'
                };
                
                cj.post('/civicrm/payment/bitcoind/ajax?qfKey=' + qfKey, data, function(response) {

                    cj('fieldset.payment_options-group').after(
                        _.template(
                            cj('#bitcoin-payment-template').html(), {
                                address: response.address,
                                qr_code: response.qr_code
                            }
                        )
                    );
        
                });

            } else {
                cj('#bitcoin-payment-block').show();
            }

        });
    
    });

}).call(this);

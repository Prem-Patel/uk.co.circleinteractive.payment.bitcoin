/**
 * Javascript for converting prices to BTC when a Bitcoin payment processor is selected 
 * andyw@circle, 24/06/2014
 */
(function() {

    // doc load ..
    cj(function() {
    
        cj('input[name="payment_processor"]').change(function() {

            // if the selected id is not a BitcoinD id then ..
            if (!_.contains(CRM.btc_processor_ids, cj(this).val()))
                cj('.crm-price-amount-btc').fadeOut();
            else
                cj('.crm-price-amount-btc').fadeIn();

        });

        cj('.crm-price-amount-amount').each(function() {
            
            price     = parseFloat(cj(this).html().replace(/[^0-9\.]/g, ''));
            btc_price = (price / CRM.btc_exchange_rate).toFixed(4);

            cj(this).after('<span class="crm-price-amount-btc"> (' + btc_price + ' BTC) </span>'); 
        
        });


    });

}).call(this);
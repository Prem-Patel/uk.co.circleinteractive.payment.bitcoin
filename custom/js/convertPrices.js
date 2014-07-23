/**
 * Javascript for converting prices to BTC when a Bitcoin payment processor is selected 
 * andyw@circle, 24/06/2014
 */
(function() {

    // doc load ..
    cj(function() {
    
        // for each price option
        cj('.crm-price-amount-amount').each(function() {

            // convert prices to btc
            var price = CRM.btc_exchange_rate ? 
                (parseFloat(cj(this).html().replace(/[^0-9\.]/g, '')) / CRM.btc_exchange_rate).toFixed(4) :
                '<span style="color:red; font-weight:bold">Error</span>';
            
            // display next to current price option
            cj(this).after(_.template(
                '<span class="crm-price-amount-btc" style="<%=style %>"> (<%=btc_price %> BTC) </span>', {
                    // if a non-bitcoin processor currently selected, set display:none
                    style:     !_.contains(CRM.btc_processor_ids, cj('input[name="payment_processor"]:checked').val()) ? 'display:none' : '',
                    btc_price: price
                }
            ));
        
        });

        // show/hide BTC prices whenever a bitcoin processor is selected/deselected
        cj('input[name="payment_processor"]').change(function() {
            var operation = !_.contains(CRM.btc_processor_ids, cj(this).val()) ? 'fadeOut' : 'fadeIn';
            cj('.crm-price-amount-btc')[operation]();
        });

    });

}).call(this);
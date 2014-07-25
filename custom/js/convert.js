/**
 * Javascript for converting prices to BTC when a Bitcoin payment processor is selected 
 * andyw@circle, 24/06/2014
 */
(function() {

    // doc load ..
    cj(function() {

        var price;
        var price_template = '<span class="crm-price-amount-btc" style="<%=style %>"> (<%=btc_price %> BTC) </span>';
    
        // for each price option
        cj('.crm-price-amount-amount').each(function() {

            // convert prices to btc
            price = CRM.btc_exchange_rate ? 
                (parseFloat(cj(this).html().replace(/[^0-9\.]/g, '')) / CRM.btc_exchange_rate).toFixed(4) :
                '<span style="color:red; font-weight:bold">Error</span>';
            
            // display next to current price option
            cj(this).after(_.template(
                price_template, {
                    // if a non-bitcoin processor currently selected, set display:none
                    style:     !_.contains(CRM.btc_processor_ids, cj('input[name="payment_processor"]:checked').val()) ? 'display:none' : '',
                    btc_price: price
                }
            ));
        
        });

        // add btc conversion to 'other amount' section on contribution pages
        if (cj('.other_amount-section').length) {

            cj('.other_amount-section input').after(_.template(
                price_template, {
                    style:     !_.contains(CRM.btc_processor_ids, cj('input[name="payment_processor"]:checked').val()) ? 'display:none' : '',
                    btc_price: '<span class="other-amount-btc">0.00</span>'                    
                }
            ));
            
            // update btc price when the field is updated
            cj('.other_amount-section input').keyup(function() {
                var amount = cj(this).val();
                cj('.other_amount-section .other-amount-btc').html(
                    isNaN(amount) || amount.replace(/\s+/g, '') == '' ? '0.0000' : (parseFloat(amount) / CRM.btc_exchange_rate).toFixed(4)
                );
            });

        }

        // show/hide BTC prices whenever a bitcoin processor is selected/deselected
        cj('input[name="payment_processor"]').change(function() {
            var operation = !_.contains(CRM.btc_processor_ids, cj(this).val()) ? 'fadeOut' : 'fadeIn';
            cj('.crm-price-amount-btc')[operation]();
        });

    });

}).call(this);
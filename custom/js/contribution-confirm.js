/**
 * Javascript for contribution confirmation pages - andyw@circle, 01/06/2014
 */
 (function() {

    cj(function() {
        
        cj('.amount_display-group .display-block strong').after(
            _.template(
                '<span> (<%=btc_price %> BTC) </span>', {
                    btc_price: (
                        parseFloat(cj('.amount_display-group .display-block strong').html().replace(/[^0-9\.]/g, '')) / 
                        CRM.btc_exchange_rate
                    ).toFixed(4)

                }
            )
        )

    });

}).call(this);
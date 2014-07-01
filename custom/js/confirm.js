/**
 * javascript for confirmation pages - andyw@circle, 01/06/2014
 */
 (function() {

    cj(function() {
    	
    	if (CRM.is_bitcoin)
    		cj('.total-amount-section .content').append(
    			_.template(
    				'<span> (<%=btc_price %> BTC) </span>', {
    					btc_price: (
    						parseFloat(cj('.total-amount-section .content').html().replace(/[^0-9\.]/g, '')) / 
    						CRM.btc_exchange_rate
    					).toFixed(4)

    				}
    			)
    		)

    });

}).call(this);
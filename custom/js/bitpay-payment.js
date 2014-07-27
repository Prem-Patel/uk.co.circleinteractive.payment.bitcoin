/**
 * Javascript for BitPay payment page - andyw@circle, 27/07/2014
 */
(function() {

    // doc load ..
    cj(function() {
        
        // listen for invoice status updates 
        window.addEventListener("message", function(event) {
            console.log('status = ' + event.data.status);
        });

    });

}).call(this);

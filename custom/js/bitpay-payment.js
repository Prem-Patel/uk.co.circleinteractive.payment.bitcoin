/**
 * Javascript for BitPay payment page
 */
(function() {

    var submitted = false;

    // doc load ..
    cj(function() {
        
        // listen for invoice status updates 
        window.addEventListener("message", function(event) {
            // when paid, redirect to thankyou page
            console.log(event.data.status);
            if (event.data.status == 'paid' && !submitted) {
                console.log('submitting form');
                window.location.href = CRM.thankyou_url;
                submitted = true;
            }

        }, false);

    });

}).call(this);

/**
 * Javascript for payment block - andyw@circle, 24/06/2014
 */
(function() {

    cj(function() {
        cj('input[name="payment_processor"]').change(function() {
            cj('fieldset.payment_options-group').after('<p style="color:green">OK!</p>');
        });
    });

}).call(this);

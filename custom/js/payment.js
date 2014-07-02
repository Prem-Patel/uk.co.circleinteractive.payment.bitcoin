/**
 * Javascript for BitcoinD payment page - andyw@circle, 02/07/2014
 */
(function() {

    var timer = 15 * 60;

    // doc load ..
    cj(function() {
        
        var countdown = setInterval(function() {
            
            timer--;
            
            cj('.countdown .minutes').html(Math.floor(timer / 60));
            cj('.countdown .seconds').html(timer % 60);
            
            if (!timer) {
                alert('Out of time');
                clearInterval(coundown);
            }

        }, 1000);
    
    });

}).call(this);

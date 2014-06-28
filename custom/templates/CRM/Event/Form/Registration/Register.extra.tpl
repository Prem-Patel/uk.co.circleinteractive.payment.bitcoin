{* Additions to Event Register page for Bitcoin payment processor *}

{literal}
<script id="bitcoin-payment-template" type="text/template">
  <div id="bitcoin-payment-block">
    <fieldset class="billing_mode-group bitcoin_info-group">
      <legend>Bitcoin Payment Details</legend>
      <div class="payment-details">
        <div class="countdown">
          Invoice is valid for: <strong class="minutes">15</strong>m:<strong class="seconds">00</strong>s
        </div>
        <div class="info">
          Send <span class="btc-amount">0.01952</span> bitcoins to: 
          <div class="btc-address">15amaYtP47Nmf00m1yFNtuKGsmKQUwTJX</div>
        </div>
      </div>
      <div class="qr-code">
        <img src="/civicrm/payment/bitcoind/qrcode?qr=15amaYtP47Nmf00m1yFNtuKGsmKQUwTJX&w=3&h=3" />
      </div>
    </fieldset>
  </div>
</script>
{/literal}

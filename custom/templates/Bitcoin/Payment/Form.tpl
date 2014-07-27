<div id="bitcoin-payment-block">
  <div class="payment-details">
  <div class="countdown">
    Invoice is valid for: <strong class="minutes">15</strong>m:<strong class="seconds">00</strong>s
  </div>
  <div class="info">
    Send <span class="btc-amount">{$amount}</span> bitcoins to: 
    <div class="btc-address">{$pay_address}</div>
  </div>
  </div>
  <div class="qr-code">
    <img src="{$qr_code}" />
  </div>
</div>
<div style="clear:both"></div>
<a class="button" title="Done" href="{$thankyou_url}">Done!</a>
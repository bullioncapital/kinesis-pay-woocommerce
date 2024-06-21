jQuery( function( $ ) {
  $( document ).ready(function() {
    $("body").append(`
      <script>
        function copyPaymentId() {
          const copy_text = document.getElementById("payment-id-text");
          copy_text.select();
          document.execCommand("Copy");

          const copy_button = document.getElementById("copy-button");
          const prev_label = copy_button.textContent;
          copy_button.textContent = "Copied";
          copy_button.classList.add("disabled");
          copy_button.disabled = true;

          setTimeout(function () {
              copy_button.textContent = prev_label;
              copy_button.classList.remove("disabled");
              copy_button.disabled = false;
          }, 2000);
        }
      </script>
      <div id="kinesis-pay-modal" style="display: none;">
        <div id="kinesis-pay-modal-content">
          <div class="kinesis-pay-modal-logo-wrapper">
            <img id="kpay-logo" src="">
            <span class="kinesis-pay-modal-logo-title">Pay with K-Pay</span>
          </div>
          <span class="kinesis-pay-instructions">Scan the QR code with the Kinesis mobile app to complete the payment
            <img id="kpay-instruction-img" src="">
          </span>
          <div id="kpay-qrcode"></div>
          <a id="kpay-payment-link" href="" target="_blank">OR make the payment using the KMS</a>
          <div class="kinesis-pay-modal-payment-id-copy-wrapper">
            <span>Payment ID</span>
            <div class="kinesis-pay-payment-info">
              <input id="payment-id-text" type="text" value="" id="payment_id_value" readonly>
              <button id="copy-button" class="kpay-copy-button" onclick="copyPaymentId()">Copy</button>
            </div>
            <span class="kinesis-pay-instructions">Please keep this window open. It will close automatically once your payment has been processed.</span>
          </div>
        </div>
      </div>
    `);
  });
});
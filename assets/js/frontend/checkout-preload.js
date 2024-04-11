jQuery( function( $ ) {
  $( document ).ready(function() {
    $("body").append(`
      <script>
        function copyPaymentId() {
          var copyText = document.getElementById("payment-id-text");
          copyText.select();
          document.execCommand("Copy");
          alert("Payment ID has been copied.");
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
              <button id="copy-button" onclick="copyPaymentId()">Copy</button>
            </div>
            <a style="display: block; white-space: nowrap; text-decoration: none; color: #017DE8;"
              href="/checkout">Close</a>
          </div>
        </div>
      </div>
      <style>
        #kinesis-pay-modal {
          display: flex;
          align-items: center;
          position: absolute; 
          top: 0;
          display: flex; 
          justify-content: center;
          width: 100%; 
          height: 100vh; 
          background-color: rgba(13, 29, 44, 0.9); 
          z-index: 999100;
        }
        #kinesis-pay-modal-content {
          display: flex; 
          gap: 16px;
          flex-direction: column;
          justify-content: center; 
          align-items: center;
          height: min-content; 
          background-color: white;
          border-radius: 4px; 
          padding: 48px 32px; 
          max-width: 430px;
        }
        #kpay-logo {
          width: auto;
          height: 48px;
        }
        #kpay-instruction-img {
          display: inline-block;
          position: relative;
          top: 3px;
          width: 16px;
          height: 16px;
        }
        #kpay-payment-link {
          display: block;
          white-space: nowrap;
          text-decoration: none;
          color: #017DE8;
        }
        .kinesis-pay-modal-logo-wrapper {
          display: flex;
          flex-direction: column;
          gap: 12px;
          align-items: center;
          margin-bottom: 16px;
        }
        .kinesis-pay-modal-logo-title {
          font-size: 20px;
          text-transform: uppercase;
        }
        #kpay-qrcode {
          display: flex;
          justify-content: center;
          width: 200px;
          max-height: 200px;
        }
        .kinesis-pay-instructions {
          text-align: center;
          line-height: 20px;
        }
        .kinesis-pay-payment-info {
          display: flex;
          margin-bottom: 16px;
        }
        .kinesis-pay-modal-payment-id-copy-wrapper {
          display: flex;
          justify-content:
          space-between;
          gap: 8px;
          flex-direction: column;
          align-items: center;
          margin-top: 12px;
        }
        #copy-button {
          display: inline-block;
          color: #FFFFFF;
          background-color: #017DE8;
          border-radius: 4px;
          padding: 0px 16px;
        }
        #payment-id-text {
          display: inline-block;
          color: #0D1D2C;
          background-color: #FFFFFF;
          box-shadow: none;
          border-radius: 4px;
          border: 1px solid #E5E7E8;
          padding: 4px 12px;
        }
      </style>
    `);
  });
});
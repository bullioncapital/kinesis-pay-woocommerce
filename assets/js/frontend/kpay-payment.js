jQuery( function( $ ) {
  $( document ).ready(() => {
    if (typeof kpay_data === 'undefined') {
      return false;
    }

    const data_kms_url = kpay_data.kpay_redirect_url;
    const data_payment_id = kpay_data.kpay_payment_id;
    const data_get_status_action = kpay_data.get_payment_status_action;
    const data_payment_status = kpay_data.kpay_payment_status;
    const data_timeout_url = kpay_data.timeout_redirect_url;
    const data_rejected_url = kpay_data.rejected_redirect_url;
    const data_error_url = kpay_data.error_redirect_url;
    const data_cancel_url = kpay_data.cancel_url;
    const data_messages = kpay_data.messages;
    const data_modal_html = kpay_data.payment_modal_html;
    const data_timeout_html = kpay_data.timeout_html;
    const data_payment_form_content = kpay_data.payment_form_content;
    const checking_int = 10;
    const timeout_period = kpay_data.timeout_peirod ? kpay_data.timeout_peirod : 600000;
    let countdown = checking_int;

    $('body').append(`<div id="kinesis-pay-modal" style="display: none;"></div>`);
    document.getElementById('kinesis-pay-modal').innerHTML = data_modal_html;

    const kpay_modal = $('#kinesis-pay-modal');
    if (!kpay_modal.length) {
      alert(data_messages.general_error);
      window.location = data_error_url;
      return false;
    }

    const qrcode_elem = document.getElementById('kinesis-pay-modal__kpay-qrcode');
    if (!qrcode_elem) {
      alert(data_messages.general_error);
      window.location = data_error_url;
      return false;
    }
    new QRCode(qrcode_elem, {
      text: data_kms_url,
      width: 160,
      height: 160,
      colorDark : "#000000",
      colorLight : "#ffffff",
      correctLevel : QRCode.CorrectLevel.L
    });
    
    window.scrollTo(0, 0);
    $('html, body').css({
      overflow: 'hidden',
      height: '100vh'
    });
    kpay_modal.show();

    const timeout = setTimeout(() => {
      clearInterval(check_status_timer);

      const contentElem = document.getElementById('kinesis-pay-modal__content');
      if (contentElem) {
        contentElem.innerHTML = data_timeout_html;
      } else {
        alert(data_messages.timeout_error);
        window.location = data_timeout_url;
      }
    }, timeout_period);

    const countdown_elem = document.getElementById("kinesis-pay-modal__check-status-countdown");
    const check_status_timer = setInterval(() => {
      countdown_elem.textContent = `${countdown}s`;
      if (countdown <= 0) {
        $.ajax({
          type: 'GET',
          dataType: 'json',
          url: wc_checkout_params.ajax_url,
          data: { action: data_get_status_action, payment_id: data_payment_id },
          success: (response) => {
            if(response.type == 'success') {
              const payment_status = response.data;
              if (payment_status === data_payment_status.processed) {
                clearInterval(check_status_timer);
                clearTimeout(timeout);
                $('.kpay-waiting-text').show();
                $('#kinesis-pay-modal').hide(); 
                const kpay_hidden_form = $('#kpay-payment-confirm-hidden-form');
                kpay_hidden_form.attr('method', 'POST');
                kpay_hidden_form.attr('enctype', 'multipart/form-data');
                kpay_hidden_form.attr('action', kpay_data.callback_url);
                kpay_hidden_form.append(data_payment_form_content);
                kpay_hidden_form.submit();
                return false;
              } else if (payment_status === data_payment_status.rejected) {
                clearInterval(check_status_timer);
                clearTimeout(timeout);
                if(!alert(data_messages.rejected_error)){
                  window.location = data_rejected_url;
                }
                return false;
              } else if (payment_status === data_payment_status.expired) {
                clearInterval(check_status_timer);
                clearTimeout(timeout);
                const contentElem = document.getElementById('kinesis-pay-modal__content');
                if (contentElem) {
                  contentElem.innerHTML = data_timeout_html;
                } else {
                  alert(data_messages.timeout_error);
                  window.location = data_timeout_url;
                }
              }
            }
            return false;
          },
          error: (error) => {
            console.log(`${data_messages.exception_error} ${error.responseJSON && error.responseJSON.message ? error.responseJSON.message : ''}`);
          }
        });
        countdown = checking_int
      }
      countdown--;
    }, 1000);

    const cancel_button = document.getElementById('kinesis-pay-modal__cancel-payment-button');
    if (cancel_button) {
      cancel_button.addEventListener('click', () => {
        cancel_button.disabled = true;
        clearInterval(check_status_timer);
        clearTimeout(timeout);
        window.location = data_cancel_url;
      });
    }
  });
});

const disableCopyButton = (buttonElem) => {
  const prevLabel = buttonElem.textContent;
  buttonElem.textContent = kpay_data ? kpay_data.messages.copied : 'Copied';
  buttonElem.disabled = true;
  setTimeout(() => {
    buttonElem.textContent = prevLabel;
    buttonElem.disabled = false;
  }, 2000);
}

const copyPaymentId = (event) => {
  const textValue = document.getElementById('kinesis-pay-modal__payment-id-text');
  if (navigator.clipboard) {
    navigator.clipboard.writeText(textValue.value)
      .then(() => disableCopyButton(event.srcElement))
      .catch((e) => alert(typeof kpay_data === 'undefined' ? e : `${kpay_data ? kpay_data.messages.copy_error : 'Failed to copy payment id.'} ${e}`));
  } else {
    textValue.select();
    document.execCommand('Copy');
    disableCopyButton(event.srcElement);
  }
}
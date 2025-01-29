jQuery( function( $ ) {
  $( document ).ready(() => {
    if (typeof kpay_settings_data === 'undefined') {
      return false;
    }
    const data_is_test_mode = kpay_settings_data.is_test_mode;
    const data_test_connection_action = kpay_settings_data.test_connection_action;
    const data_messages = kpay_settings_data.messages;

    const merchant_id_selector = data_is_test_mode ? 'woocommerce_kinesis_pay_gateway_test_merchant_id' : 'woocommerce_kinesis_pay_gateway_merchant_id';
    const access_token_selector = data_is_test_mode ? 'woocommerce_kinesis_pay_gateway_test_publishable_key' : 'woocommerce_kinesis_pay_gateway_publishable_key';
    const secret_token_selector = data_is_test_mode ? 'woocommerce_kinesis_pay_gateway_test_private_key' : 'woocommerce_kinesis_pay_gateway_private_key';
    const merchant_id_elem = document.getElementById(merchant_id_selector);
    const access_token_elem = document.getElementById(access_token_selector);
    const secret_token_elem = document.getElementById(secret_token_selector);
    const message_success_elem = document.getElementById('connection-message-success');
    const message_failure_elem = document.getElementById('connection-message-failure');

    const toggle_message = (type, error_message = null) => {
      if (!message_success_elem || !message_failure_elem) {
        return;
      }
      switch(type) {
        case 'success':
          message_success_elem.hidden = false;
          message_failure_elem.hidden = true;
          break;
        case 'failure':
          message_failure_elem.innerText = `${data_messages.failure_error} ${error_message ? error_message : ''}`;
          message_success_elem.hidden = true;
          message_failure_elem.hidden = false;
          break;
        default:
          message_success_elem.hidden = true;
          message_failure_elem.hidden = true;
          break;
      }
    }

    if (merchant_id_elem) {
      merchant_id_elem.addEventListener('change', () => {
        toggle_message('reset');
      });
    }
    if (access_token_elem) {
      access_token_elem.addEventListener('change', () => {
        toggle_message('reset');
      });
    }
    if (secret_token_elem) {
      secret_token_elem.addEventListener('change', () => {
        toggle_message('reset');
      });
    }

    const test_button = document.getElementById('woocommerce_kinesis_pay_gateway_test_connection_action');
    if (test_button) {
      test_button.addEventListener('click', () => {
        toggle_message('reset');
        
        const merchant_id = merchant_id_elem.value;
        const access_token = access_token_elem.value;
        const secret_token = secret_token_elem.value;
        if (!merchant_id || !access_token || !secret_token) {
          alert(data_messages.input_missing_error);
          return;
        }
        
        $.ajax({
          type: 'POST',
          dataType: 'json',
          url: woocommerce_admin.ajax_url,
          data: { action: data_test_connection_action, merchant_id: merchant_id, access_token: access_token, secret_token: secret_token },
          success: (response) => {
            if(response.type === 'success') {
              toggle_message('success');
            } else {
              const error_message = response.message;
              toggle_message('failure', error_message);
            }
          },
          error: (error) => {
            const error_message = error.responseJSON ? error.responseJSON.message : null;
            console.log(`${data_messages.exception_error} ${error_message ? error_message : ''}`);
            toggle_message('failure', error_message);
          }
        });
      });
    }
  });
});

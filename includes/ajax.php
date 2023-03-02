<?php
defined( 'ABSPATH' ) || exit;
  
function create_kpay_payment() {
  $response = request_kpay_paymentId();

  if (isset($response->globalPaymentId)) {
    $options = get_option('woocommerce_kinesis-pay_settings');
    $testmode = $options['testmode'];
    if ($testmode === 'yes') {
      $base_url = empty($options['test_frontend_base_url']) ? "https://qa1-kms.kinesis.money" : $options['test_frontend_base_url'];
    } else {
      $base_url = 'https://kms.kinesis.money';
    }

    $array_result = array(
      'type' => 'success',
      'data' => array(
        'payment_id' => $response->globalPaymentId,
        'redirect_url' => $base_url . '?paymentId=' . $response->globalPaymentId,
      ),
      'message' => 'Payment id created.'
    );
  } else {
    $array_result = array(
      'type' => 'failed',
      'message' => 'Failed to create payment id.'
    );
  }
  wp_send_json($array_result);
  wp_die();
}
add_action('wp_ajax_woocommerce_create_kpay_payment', 'create_kpay_payment');
add_action('wp_ajax_nopriv_woocommerce_create_kpay_payment', 'create_kpay_payment');

function get_payment_status() {
  $payment_id = $_POST['payment_id'];
  $response = request_payment_status($payment_id);

  if (isset($response->status) && $response->status === 'processed') {
    $array_result = array(
      'type' => 'success',
      'data' => 'processed',
    );
  } else if (isset($response->status) && $response->status === 'rejected') {
    $array_result = array(
      'type' => 'success',
      'data' => 'rejected',
    );
  } else {
    $array_result = array(
      'type' => 'success',
      'data' => 'pending',
    );
  }
  wp_send_json($array_result);
  wp_die();
}
add_action('wp_ajax_woocommerce_get_payment_status', 'get_payment_status');
add_action('wp_ajax_nopriv_woocommerce_get_payment_status', 'get_payment_status');

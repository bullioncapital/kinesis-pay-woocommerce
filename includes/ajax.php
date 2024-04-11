<?php
defined('ABSPATH') || exit;

function create_kpay_payment()
{
  try {
    $response = request_kpay_paymentId();
  } catch (Exception $e) {
    error_log('create payment id exception: ' . json_encode($e));
    wp_send_json(
      array(
        'type' => 'failed',
        'message' => $e->getMessage(),
      )
    );
    wp_die();
  }
  if (isset($response->globalPaymentId)) {
    global $kms_base_url;
    global $test_kms_base_url;
    global $test_mode;
    $js_kms_base_url = $test_mode === 'yes' ? $test_kms_base_url : $kms_base_url;

    $array_result = array(
      'type' => 'success',
      'data' => array(
        'payment_id' => $response->globalPaymentId,
        'redirect_url' => $js_kms_base_url . '?paymentId=' . $response->globalPaymentId,
        'assets_url' => KINESIS_PAY_DIR_URL . 'assets/images/',
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

function get_payment_status()
{
  $payment_id = $_POST['payment_id'];
  try {
    $response = request_payment_status($payment_id);
  } catch (Exception $e) {
    wp_send_json(
      array(
        'type' => 'failed',
        'message' => $e->getMessage(),
      )
    );
    wp_die();
  }

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

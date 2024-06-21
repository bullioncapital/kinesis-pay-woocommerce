<?php
defined('ABSPATH') || exit;

/**
 * Ajax for createing a Kinesis Pay payment
 *
 * @return void
 */
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
      ),
      $e->getCode()
    );
    wp_die();
  }

  if (isset($response->globalPaymentId)) {
    global $kms_base_url;
    global $test_kms_base_url;
    global $wpdb;
    global $test_mode;

    $tablename = $wpdb->prefix . 'kinesis_payments';
    $wpdb->query(
      $wpdb->prepare(
        "INSERT INTO $tablename
        ( order_id, payment_id, payment_status, kpay_order_id, payment_currency, payment_amount, payment_fee, usd_converted_amount, payment_kau_amount, payment_kag_amount, expiry_at, created_at, updated_at, `description` )
        VALUES ( %d, %s, %s, %s, %s, %.5f, %.5f, %.2f, %.5f, %.5f, %s, %s, %s, %s )",
        null,
        $response->globalPaymentId,
        $response->status,
        isset($response->orderId) ? $response->orderId : null,
        isset($response->paymentCurrency) ? $response->paymentCurrency : null,
        isset($response->paymentAmount) ? $response->paymentAmount : null,
        isset($response->paymentFee) ? $response->paymentFee : null,
        isset($response->usdConvertedAmount) ? $response->usdConvertedAmount : null,
        $response->paymentKauAmount,
        $response->paymentKagAmount,
        $response->expiryAt,
        $response->createdAt,
        $response->updatedAt,
        current_time('Y-m-d H:i:s', true) . ' Payment created.\n'
      )
    );

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

/**
 * Ajax for getting payment status
 *
 * @return void
 */
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
      ),
      $e->getCode()
    );
    wp_die();
  }

  if (isset($response->status) && $response->status === 'processed') {
    $array_result = array(
      'type' => 'success',
      'data' => 'processed',
    );
  } else if (isset($response->status) && $response->status === 'rejected') {
    global $wpdb;
    $tablename = $wpdb->prefix . 'kinesis_payments';
    $result = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM $tablename WHERE `payment_id` = %s AND `payment_status` = 'pending' ORDER BY `id` DESC",
      array($payment_id)
    ));
    if (!!count($result)) {
      $kpay_id = $result[0]->id;
      $wpdb->query(
        $wpdb->prepare(
          "UPDATE $tablename
          SET `payment_status`= %s, updated_at = %s, descritpion = %s
          WHERE `id` = $kpay_id",
          array(
            'rejected',
            $response->updatedAt,
            $result[0]->description . current_time('Y-m-d H:i:s', true) . ' Payment rejected.\n',
          )
        )
      );
    }

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

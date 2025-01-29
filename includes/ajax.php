<?php
defined('ABSPATH') || exit;

/**
 * Ajax for getting payment status
 *
 * @return void
 */
function get_payment_status()
{
    $payment_id = $_GET['payment_id'];
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

    if (isset($response->status) && $response->status === 'rejected') {
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
          WHERE `id` = %s",
                    array(
                        'rejected',
                        $response->updatedAt,
                        $result[0]->description . current_time('Y-m-d H:i:s', true) . ' Payment rejected.\n',
                        $kpay_id,
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
            'data' => isset($response->status) ? $response->status : '',
        );
    }
    wp_send_json($array_result);
    wp_die();
}
add_action('wp_ajax_woocommerce_get_payment_status', 'get_payment_status'); // For logged-in
add_action('wp_ajax_nopriv_woocommerce_get_payment_status', 'get_payment_status'); // For not logged-in

/**
 * Ajax for testing API connection
 *
 * @return void
 */
function test_connection()
{
    if (empty($_POST['merchant_id']) || empty($_POST['access_token']) || empty($_POST['secret_token'])) {
        wp_send_json(
            array(
                'type' => 'failure',
                'message' => __('Merchant ID or/and API keys are empty.', 'kinesis-pay-gateway'),
            ),
            400
        );
        wp_die();
    }
    $merchant_id = $_POST['merchant_id'];
    $access_token = $_POST['access_token'];
    $secret_token = $_POST['secret_token'];
    try {
        request_test_connection($merchant_id, $access_token, $secret_token);
    } catch (Exception $e) {
        wp_send_json(
            array(
                'type' => 'failure',
                'message' => $e->getMessage(),
            ),
            $e->getCode()
        );
        wp_die();
    }
    wp_send_json(array(
        'type' => 'success',
        'data' => array(
            'merchant_id' => $merchant_id,
        ),
    ));
    wp_die();
}
add_action('wp_ajax_test_connection', 'test_connection'); // For logged-in
add_action('wp_ajax_nopriv_woocommerce_test_connection', 'test_connection'); // For not logged-in

<?php
// Prevent public user to directly access .php files through URL
defined('ABSPATH') || exit;

/**
 * Get request headers
 *
 * @param  string $url 			MUST start with '/'
 * @param  string $public_key
 * @param  string $private_key
 * @param  string $content
 * @param  string $method
 * @return array
 */
function getHeaders($url, $public_key, $private_key, $content = '', $method = 'GET')
{
	$nonce = time() * 1000 + rand(1, 10);
	$message = $nonce . $method . $url . (empty($content) ? '{}' : json_encode($content));
	$xsig = strtoupper(hash_hmac('SHA256', $message, $private_key));

	$authHeaders = array(
		'x-nonce' => $nonce,
		'x-api-key' => $public_key,
		'x-signature' => $xsig
	);

	if ($method !== 'DELETE') {
		$authHeaders['Content-type'] = 'application/json';
	}
	return $authHeaders;
}

/**
 * Call Kinesis API
 *
 * @param  string $api
 * @param  string $body
 * @param  string $method
 * @param  string $access_token
 * @param  string $secret_token
 * @return object
 */
function call_kinesis($api, $body = '', $method = 'GET', $access_token = null, $secret_token = null)
{
	$options = get_option('woocommerce_kinesis_pay_gateway_settings');
	if ($access_token && $secret_token) {
		$public_key = $access_token;
		$private_key = $secret_token;
	} else {
		if (Kinesis_Pay_WooCommerce::get()->get_test_mode()) {
			$public_key = $options['test_publishable_key'];
			$private_key = $options['test_private_key'];
		} else {
			$public_key = $options['publishable_key'];
			$private_key = $options['private_key'];
		}
	}
	$base_url = Kinesis_Pay_WooCommerce::get()->get_api_base_url();
	$url = $base_url . $api;
	$headers = getHeaders($api, $public_key, $private_key, $body, $method);
	$options = array(
		'method' => $method,
		'headers' => $headers,
	);
	if ($method !== 'GET') {
		$options['body'] = json_encode($body);
	}
	if ($method === 'GET') {
		$response = wp_remote_get($url, $options);
	} else if ($method === 'POST') {
		$response = wp_remote_post($url, $options);
	}

	$status_code = wp_remote_retrieve_response_code($response);
	if (is_wp_error($response) || ($status_code != 200 && $status_code != 201)) {
		error_log(print_r(json_encode($response), true));
		$resp_body_obj = json_decode(wp_remote_retrieve_body($response));
		if (is_object($resp_body_obj)) {
			$error_message = $resp_body_obj->message;
		} else {
			$error_message = wp_remote_retrieve_body($response);
		}
		throw new Exception($error_message, $status_code);
	}
	return $response;
}

/**
 * Request exchange rates from Kinesis API
 *
 * @deprecated since 1.1.1
 * @param  string $crypto_currency
 * @param  string $base_currency
 * @return object
 */
function request_kpay_exchange_rates($crypto_currency = 'KAU', $base_currency = 'USD')
{
	$request_url = Kinesis_Pay_WooCommerce::get()->get_exchange_rates_url() . $crypto_currency . '_' . $base_currency .  '?level=1';
	$response = wp_remote_get($request_url);
	$status_code = wp_remote_retrieve_response_code($response);
	if (is_wp_error($response) || ($status_code != 200 && $status_code != 201)) {
		throw new Exception(__('Failed to get cryptocurrency rates.', 'kinesis-pay-gateway'), $status_code);
	}
	return json_decode(wp_remote_retrieve_body($response));
}

/**
 * Request for creating a new payment
 *
 * @return object
 */
function request_kpay_payment_id()
{
	$options = get_option('woocommerce_kinesis_pay_gateway_settings');
	$merchant_id = Kinesis_Pay_WooCommerce::get()->get_test_mode() ? $options['test_merchant_id'] : $options['merchant_id'];
	$currency = get_woocommerce_currency();
	$grand_total = (float) WC()->cart->total;

	$body = array(
		"globalMerchantId" => $merchant_id,
		"amount" => number_format($grand_total, 2, '.', ''),
		"amountCurrency" => $currency
	);

	try {
		$response = call_kinesis('/api/merchants/payment', $body, 'POST');
	} catch (Exception $e) {
		throw new Exception(__('Unable to create payment. ', 'kinesis-pay-gateway') . $e->getMessage(), $e->getCode());
	}
	$status_code = wp_remote_retrieve_response_code($response);
	if (is_wp_error($response) || ($status_code != 200 && $status_code != 201)) {
		error_log(print_r($response, true));
		throw new Exception(__('Unable to create payment. ', 'kinesis-pay-gateway') . $response->get_error_message(), $status_code);
	}

	return json_decode(wp_remote_retrieve_body($response));
}

/**
 * Request for payment status
 *
 * @param  string $payment_id
 * @return object
 */
function request_payment_status($payment_id)
{
	// make api call to KMS server to get payment status
	try {
		$response = call_kinesis('/api/merchants/payment/id/sdk/' . $payment_id, '', 'GET');
	} catch (Exception $e) {
		throw new Exception(__('Unable to get payment status. ', 'kinesis-pay-gateway') . $e->getMessage(), $e->getCode());
	}
	$status_code = wp_remote_retrieve_response_code($response);
	if (is_wp_error($response) || ($status_code != 200 && $status_code != 201)) {
		error_log(print_r($response, true));
		throw new Exception(__('Unable to get payment status. ', 'kinesis-pay-gateway') . $response->get_error_message(), $status_code);
	}
	return json_decode(wp_remote_retrieve_body($response));
}

/**
 * Request for confirming a payment
 *
 * @param  string $payment_id
 * @param  string $order_id
 * @return object
 */
function request_confirm_payment($payment_id, $order_id)
{
	// make api call to KMS server to get payment status
	$body = array(
		'globalPaymentId' => $payment_id,
		'orderId' => 'confirmed_' . $order_id,
	);

	try {
		$response = call_kinesis('/api/merchants/payment/confirm', $body, 'POST');
	} catch (Exception $e) {
		throw new Exception(__('Unable to confirm payment. ', 'kinesis-pay-gateway') . $e->getMessage(), $e->getCode());
	}
	$status_code = wp_remote_retrieve_response_code($response);
	if (is_wp_error($response) || ($status_code != 200 && $status_code != 201)) {
		error_log(print_r($response, true));
		throw new Exception(__('Unable to confirm payment. ', 'kinesis-pay-gateway') . $response->get_error_message(), $status_code);
	}

	return json_decode(wp_remote_retrieve_body($response));
}

/**
 * Request for testing API connection
 *
 * @param  string $merchant_id
 * @param  string $access_token
 * @param  string $secret_token
 * @return void
 */
function request_test_connection($merchant_id, $access_token, $secret_token)
{
	try {
		$response = call_kinesis('/api/merchants/merchant/sdk/handshake/' . $merchant_id, '', 'GET', $access_token, $secret_token);
	} catch (Exception $e) {
		$code = $e->getCode();
		if ($code === 403) {
			throw new Exception(__('Invalid Access Token or Secret Token. ', 'kinesis-pay-gateway'), $code);
		} else {
			throw new Exception($e->getMessage(), $code);
		}
	}
	$status_code = wp_remote_retrieve_response_code($response);
	if (is_wp_error($response) || ($status_code != 200 && $status_code != 201)) {
		error_log(print_r($response, true));
		throw new Exception($response->get_error_message(), $status_code);
	}
	return json_decode(wp_remote_retrieve_body($response));
}

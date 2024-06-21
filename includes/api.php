<?php
defined('ABSPATH') || exit;

/**
 * Get request headers
 *
 * @param  string $url
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
		$authHeaders["Content-type"] = "application/json";
	}
	return $authHeaders;
}

/**
 * Call Kinesis API
 *
 * @param  string $api
 * @param  string $body
 * @param  string $method
 * @return object
 */
function call_kinesis($api, $body = '', $method = 'GET')
{
	$options = get_option('woocommerce_kinesis_pay_gateway_settings');
	global $api_base_url;
	global $test_api_base_url;
	global $test_mode;
	if ($test_mode === 'yes') {
		$base_url = $test_api_base_url;
		$public_key = $options['test_publishable_key'];
		$private_key = $options['test_private_key'];
	} else {
		$base_url = $api_base_url;
		$public_key = $options['publishable_key'];
		$private_key = $options['private_key'];
	}

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

	$statusCode = wp_remote_retrieve_response_code($response);
	if (is_wp_error($response) || ($statusCode != 200 && $statusCode != 201)) {
		error_log(print_r(json_encode($response), true));
		throw new Exception($response['body'], $statusCode);
	}
	return $response;
}

/**
 * Request exchange rates from Kinesis API
 *
 * @param  string $crypto_currency
 * @param  string $base_currency
 * @return object
 */
function request_kpay_exchange_rates($crypto_currency = 'KAU', $base_currency = 'USD')
{
	$request_url = 'https://api.kinesis.money/api/v1/exchange/coin-market-cap/orderbook/' . $crypto_currency . '_' . $base_currency .  '?level=1';
	$response = wp_remote_get($request_url);
	$statusCode = wp_remote_retrieve_response_code($response);
	if (is_wp_error($response) || ($statusCode != 200 && $statusCode != 201)) {
		throw new Exception('Failed to get cryptocurrency rates.', $statusCode);
	}
	return json_decode(wp_remote_retrieve_body($response));
}

/**
 * Request for creating a new payment
 *
 * @return object
 */
function request_kpay_paymentId()
{
	global $test_mode;
	$options = get_option('woocommerce_kinesis_pay_gateway_settings');
	$merchant_id = $test_mode === 'yes' ? $options['test_merchant_id'] : $options['merchant_id'];
	$currency = get_woocommerce_currency();
	$grand_total = (float) WC()->cart->total;

	// get KAU and KAG amount
	$kau_rates_resp = request_kpay_exchange_rates('KAU', $currency);
	$kag_rates_resp = request_kpay_exchange_rates('KAG', $currency);
	try {
		$kau_rate = $kau_rates_resp->bids[0];
		$kag_rate = $kag_rates_resp->bids[0];
	} catch (Exception $e) {
		error_log($e);
		throw new Exception('Failed to get cryptocurrency rates.', 500);
	}

	$body = array(
		"globalMerchantId" => $merchant_id,
		"paymentKauAmount" => number_format(($grand_total / $kau_rate), 5, '.', ''),
		"paymentKagAmount" => number_format(($grand_total / $kag_rate), 5, '.', ''),
		"amount" => number_format($grand_total, 2, '.', ''),
		"amountCurrency" => $currency
	);

	try {
		$response = call_kinesis('/api/merchants/payment', $body, 'POST');
	} catch (Exception $e) {
		throw new Exception('Unable to create payment. ' . $e->getMessage(), $e->getCode());
	}
	$statusCode = wp_remote_retrieve_response_code($response);
	if (is_wp_error($response) || ($statusCode != 200 && $statusCode != 201)) {
		error_log(print_r($response, true));
		throw new Exception('Unable to create payment. ' . $response->get_error_message(), $statusCode);
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
		throw new Exception('Unable to get payment status. ' . $e->getMessage(), $e->getCode());
	}
	$statusCode = wp_remote_retrieve_response_code($response);
	if (is_wp_error($response) || ($statusCode != 200 && $statusCode != 201)) {
		error_log(print_r($response, true));
		throw new Exception('Unable to get payment status. ' . $response->get_error_message(), $statusCode);
	}
	return json_decode(wp_remote_retrieve_body($response));
}

/**
 * Request for approving a payment
 *
 * @param  string $payment_id
 * @param  string $order_id
 * @return object
 */
function request_approve_payment($payment_id, $order_id)
{
	// make api call to KMS server to get payment status
	$body = array(
		"globalPaymentId" => $payment_id,
		"orderId" => 'confirmed_' . $order_id,
	);

	try {
		$response = call_kinesis('/api/merchants/payment/confirm', $body, 'POST');
	} catch (Exception $e) {
		throw new Exception('Unable to confirm payment. ' . $e->getMessage(), $e->getCode());
	}
	$statusCode = wp_remote_retrieve_response_code($response);
	if (is_wp_error($response) || ($statusCode != 200 && $statusCode != 201)) {
		error_log(print_r($response, true));
		throw new Exception('Unable to confirm payment. ' . $response->get_error_message(), $statusCode);
	}

	return json_decode(wp_remote_retrieve_body($response));
}

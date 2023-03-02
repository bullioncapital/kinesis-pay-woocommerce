<?php
defined( 'ABSPATH' ) || exit;

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

function call_kinesis($api, $body = '', $method = 'GET')
{
	$options = get_option('woocommerce_kinesis-pay_settings');
	$testmode = $options['testmode'];
	if ($testmode === 'yes') {
		$base_url = empty($options['test_backend_base_url']) ? "https://qa1-api.kinesis.money" : $options['test_backend_base_url'];
		$public_key = $options['test_publishable_key'];
		$private_key = $options['test_private_key'];
	} else {
		$base_url = 'https://apip.kinesis.money';
		$public_key = $options['publishable_key'];
		$private_key = $options['private_key'];
	}

	$url = $base_url. $api;
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

	if (is_wp_error($response) || (wp_remote_retrieve_response_code($response) != 200 && wp_remote_retrieve_response_code($response) != 201)) {
		throw new Exception('Error connecting with kinesis.', 500);
	}
	return $response;
}

function request_kpay_exchange_rates($crypto_currency = 'KAU', $base_currency = 'USD')
{
	$request_url = 'https://api.kinesis.money/api/v1/exchange/coin-market-cap/orderbook/' . $crypto_currency . '_' . $base_currency .  '?level=1';
	$response = wp_remote_get($request_url);
	if (is_wp_error($response) || (wp_remote_retrieve_response_code($response) != 200 && wp_remote_retrieve_response_code($response) != 201)) {
		throw new Exception('Failed to get cryptocurrency rates.', 500);
	}
	return json_decode(wp_remote_retrieve_body($response));
}

function request_kpay_paymentId()
{ 
	$options = get_option('woocommerce_kinesis-pay_settings');
	$merchant_id = $options['merchant_id'];
	$currency = get_woocommerce_currency();
	$grand_total = (float) WC()->cart->total;

	// get KAU and KAG amount
	$kau_rates_resp = request_kpay_exchange_rates('KAU', $currency);
	$kag_rates_resp = request_kpay_exchange_rates('KAG', $currency);
	try {
		$kau_rate = $kau_rates_resp->asks[0][0];
		$kag_rate = $kag_rates_resp->asks[0][0];
	} catch (Exception $e) {
		error_log($e); 
		throw new Exception('Failed to get cryptocurrency rates.', 500);
	}

	$body = array(
		"globalMerchantId" => $merchant_id,
		"paymentKauAmount" => round($grand_total / $kau_rate, 5),
		"paymentKagAmount" => round($grand_total / $kag_rate, 5),
		"amount" => $grand_total,
		"amountCurrency" => $currency
	);

	try {
		$response = call_kinesis('/api/merchants/payment', $body, 'POST');
	} catch (Exception $e) {
		throw new Exception($e->getMessage() . ' Unable to create payment.', $e->getCode());
	}
	if (is_wp_error($response) || (wp_remote_retrieve_response_code($response) != 200 && wp_remote_retrieve_response_code($response) != 201)) {
		error_log(print_r($response, true));
		throw new Exception('Unable to create payment.', 500);
	}
	
	return json_decode(wp_remote_retrieve_body($response));
  }
  
  function request_payment_status($payment_id) {
	// make api call to KMS server to get payment status
	try {
		$response = call_kinesis('/api/merchants/payment/id/sdk/' . $payment_id, '', 'GET');
	} catch (Exception $e) {
		throw new Exception($e->getMessage() . ' Unable to get payment status.', $e->getCode());
	}
	if (is_wp_error($response) || (wp_remote_retrieve_response_code($response) != 200 && wp_remote_retrieve_response_code($response) != 201)) {
		error_log(print_r($response, true));
		throw new Exception('Unable to get payment status.', 500);
	}
	return json_decode(wp_remote_retrieve_body($response));
  }
  
  function request_approve_payment($payment_id, $order_id) {
	// make api call to KMS server to get payment status
	$body = array(
		"globalPaymentId" => $payment_id,
		"orderId" => 'confirmed_' . $order_id,
	);

	try {
		$response = call_kinesis('/api/merchants/payment/confirm', $body, 'POST');
	} catch (Exception $e) {
		throw new Exception($e->getMessage() . ' Unable to confirm payment.', $e->getCode());
	}
	if (is_wp_error($response) || (wp_remote_retrieve_response_code($response) != 200 && wp_remote_retrieve_response_code($response) != 201)) {
		error_log(print_r($response, true));
		throw new Exception('Unable to confirm payment.', 500);
	}
	
	return json_decode(wp_remote_retrieve_body($response));
  }

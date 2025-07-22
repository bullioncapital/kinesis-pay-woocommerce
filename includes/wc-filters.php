<?php
    if ( is_user_logged_in() ) {
        add_filter('woocommerce_thankyou_order_received_text', 'kms_accepted_order_received_text', 21, 2);
    }
    function kms_accepted_order_received_text($text, $order ) {

        $payment_method = $order->get_payment_method();
        if (Kinesis_Pay_Gateway::is_payment_method_kinesis_pay($payment_method)) {

            $payment = Kinesis_Pay_Gateway::get_payment_by_id(null, $order->get_id());

            if ( !$payment || empty($payment->payment_id)) {
                $order->update_status('cancelled', __('Order cancelled due to payment creation failure.', 'kinesis-pay-gateway'));
                return $text;
            }

            if (kms_payment_is_accepted($order)) {
                $kms_url = Kinesis_Pay_WooCommerce::get()->get_kms_base_url();
                $text = __('Your payment (ID:'.$payment->payment_id.') has been &lsquo;accepted&rsquo; and needs to be processed.<br>Please check your <a href="'.$kms_url.'/home/transactions" target="_blank">KMS transactions</a> for additional information.', 'kinesis-pay-gateway');
            }
        }
        return $text;
    }

    add_filter('woocommerce_order_details_after_order_table', 'kms_custom_message_after_order_details');

    function kms_custom_message_after_order_details($order) {
        // Check if the order status is 'pending' and has an accepted payment status
        if (kms_payment_is_accepted($order)) {
            $kms_url = Kinesis_Pay_WooCommerce::get()->get_kms_base_url();
            $payment = Kinesis_Pay_Gateway::get_payment_by_id(null, $order->get_id());
            echo '<p>'.__('Your payment (ID:'.$payment->payment_id.') has been &lsquo;accepted&rsquo; and needs to be processed.<br>Please check your <a href="'.$kms_url.'/home/transactions" target="_blank">KMS transactions</a> for additional information.', 'kinesis-pay-gateway').'</p>';
        }
    }

    function kms_payment_is_accepted($order) {
        $payment = Kinesis_Pay_Gateway::get_payment_by_id(null, $order->get_id());
    
        if (is_null($payment) || empty($payment->payment_id)) {
            return false;
        }
    
        try {
            $response = request_payment_status($payment->payment_id);
            return $order->has_status('pending') && $order->get_payment_method() === 'kinesis_pay_gateway' && 'accepted' === $response->status;
        } catch (Exception $e) {
            error_log(sprintf('Failed to check if payment is accepted (order ID: %d). Exception: %s', $order->get_id(), json_encode($e)));
            return false;
        }
    }

    add_filter('woocommerce_order_needs_payment', 'kms_order_needs_payment', 21, 2);
    
    function kms_order_needs_payment($needs_payment, $order) {
        if ($needs_payment && kms_payment_is_accepted($order)) {
            $needs_payment = false;
        }
        return $needs_payment;
    }

    add_filter('woocommerce_valid_order_statuses_for_cancel', 'kms_order_can_be_cancelled', 21, 2);
    
    function kms_order_can_be_cancelled($statuses_for_cancel, $order) {
        if ($order->has_status('pending') && kms_payment_is_accepted($order)) {
            $pending_index = array_search('pending', $statuses_for_cancel);
            if (false !== $pending_index) {
                unset($statuses_for_cancel[$pending_index]);
            }
        }
        return $statuses_for_cancel;
    }

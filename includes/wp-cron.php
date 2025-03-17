<?php

// Declare custom interval for syncing KPay payment status
add_filter('cron_schedules', function ($schedules) {
    $schedules['kpay_every_5_minutes'] = array(
        'interval' => 5 * 60,
        'display'  => __('Every 5 minutes', 'kinesis-pay-gateway'),
    );
    return $schedules;
});

// Sync payment and process order
if (!wp_next_scheduled('kpay_sync_statuses')) {
    wp_schedule_event(time(), 'kpay_every_5_minutes', 'kpay_sync_statuses');
}
add_action('kpay_sync_statuses', 'kpay_sync_statuses');

function kpay_sync_statuses() {
    $unpaid_orders = wc_get_orders(
        array(
            'type' => 'shop_order',
            'post_status' => array('wc-pending'),
            'payment_method' => array('kinesis_pay_gateway', 'kinesis-pay'),
            'orderby' => 'id',
            'order'   => 'DESC',
        )
    );
    foreach ($unpaid_orders as $unpaid_order) {
        $order = wc_get_order($unpaid_order);
        $payment_method = $order->get_payment_method();
        if (apply_filters('kpay_check_unpaid_order_payment', ($order->is_created_via('checkout') || $order->is_created_via('store-api')) && Kinesis_Pay_Gateway::is_payment_method_kinesis_pay($payment_method), $order)) {
            Kinesis_Pay_Gateway::sync_payment($order);
        }
    }
}

<?php

use Automattic\WooCommerce\Utilities\OrderUtil;

if (OrderUtil::custom_orders_table_usage_is_enabled()) {
    // When High-performance order storage is active
    add_filter('manage_woocommerce_page_wc-orders_columns', 'kpay_shop_order_column', 10);
    add_action('manage_woocommerce_page_wc-orders_custom_column', 'kpay_orders_list_column_content', 10, 2);
    add_action('add_meta_boxes', 'kpay_order_details_meta_box');
} else {
    // When legacy mode is ON
    add_filter('manage_edit-shop_order_columns', 'kpay_shop_order_column', 10);
    add_action('manage_shop_order_posts_custom_column', 'kpay_orders_list_column_content', 10, 2);
    add_action('add_meta_boxes_shop_order', 'kpay_order_details_meta_box', 10, 2);
}

// Cron job of cleanup-draft-orders (running daily). Check unpaid KPay orders
add_action('woocommerce_cleanup_draft_orders', 'kpay_clean_up_unpaid_orders');
/**
 * Before loading admin order table, clean up unpaid orders for legacy tables
 *
 * @return void
 */
function kpay_clean_up_unpaid_orders()
{
    $data_store    = WC_Data_Store::load('order');
    $unpaid_orders = $data_store->get_unpaid_orders(current_time('timestamp'));
    if (empty($unpaid_orders)) {
        return;
    }
    foreach ($unpaid_orders as $unpaid_order) {
        $order = wc_get_order($unpaid_order);
        $payment_method = $order->get_payment_method();
        if (apply_filters('kpay_check_unpaid_order_payment', ($order->is_created_via('checkout') || $order->is_created_via('store-api')) && Kinesis_Pay_Gateway::is_payment_method_kinesis_pay($payment_method), $order)) {
            Kinesis_Pay_Gateway::sync_payment($order);
        }
    }
}

// Check unpaid KPay orders when loading admin order table page
add_action('admin_title', 'kpay_admin_before_load_order_table');
/**
 * Before loading admin order table, clean up unpaid orders
 *
 * @return void
 */
function kpay_admin_before_load_order_table()
{
    if (!OrderUtil::is_order_list_table_screen()) {
        return;
    }
    kpay_clean_up_unpaid_orders();
}

// Cron job of wc_cancel_unpaid_orders (WC -> Settings -> Products -> Inventory -> Manage stock) in wc-order-functions.php
add_filter('woocommerce_cancel_unpaid_order', 'kpay_check_and_update_unpaid_order', 50, 2);
/**
 * WooCommerce stock-management cron job. Update unpaid order status and sync payment details
 *
 * @param  boolean $default_value
 * @param  WC_Order $order
 * @return boolean
 */
function kpay_check_and_update_unpaid_order($default_value, $order)
{
    $payment_method = $order->get_payment_method();
    if (($order->is_created_via('checkout') || $order->is_created_via('store-api')) && Kinesis_Pay_Gateway::is_payment_method_kinesis_pay($payment_method)) {
        return !Kinesis_Pay_Gateway::sync_payment($order);
    }
    return $default_value;
}

/**
 * Add custom column(s) to order list view
 *
 * @param  array $columns
 * @return array
 */
function kpay_shop_order_column($columns)
{
    $reordered_columns = array();
    foreach ($columns as $key => $column) {
        $reordered_columns[$key] = $column;
        if ($key === 'order_status') {
            // Insert after "Status" column
            $reordered_columns['kinesis_pay_payment_id'] = __('Kinesis-Pay Payment ID', 'kinesis-pay-gateway');
        }
    }
    $reordered_columns['kinesis_pay_payment_id'] = __('Kinesis-Pay Payment ID', 'kinesis-pay-gateway');
    return $reordered_columns;
}

/**
 * Get display Kpay payment status
 *
 * @param  string $payment_status
 * @param  string $kpay_order_id
 * @return string
 */
function get_display_status($payment_status, $kpay_order_id)
{
    if ($payment_status !== 'processed') {
        return $payment_status;
    }
    $is_confirmed = $kpay_order_id && str_contains($kpay_order_id, 'confirmed');
    return $is_confirmed ? $payment_status . ' (confirmed)' : $payment_status;
}

/**
 * Add custom fields meta data for each new column
 *
 * @param  array $column
 * @param  WC_Order $order
 * @return void
 */
function kpay_orders_list_column_content($column, $order)
{
    if ($column !== 'kinesis_pay_payment_id') {
        return;
    }
    $order_id = is_object($order) ? $order->get_id() : $order;
    $query_order = wc_get_order($order_id);
    $payment = null;
    if (Kinesis_Pay_Gateway::is_payment_method_kinesis_pay($query_order->get_payment_method())) {
        $payment = Kinesis_Pay_Gateway::get_payment_by_id(null, $order_id);
    }
    switch ($column) {
        case 'kinesis_pay_payment_id':
            echo $payment ? $payment->payment_id : '<small>-</small>';
            break;
        default:
            break;
    }
}

/**
 * Add custom meta box to order details page in admin
 *
 * @return void
 */
function kpay_order_details_meta_box()
{
    $screen = OrderUtil::custom_orders_table_usage_is_enabled()
        ? wc_get_page_screen_id('shop-order')
        : 'shop_order';

    add_meta_box(
        'order_payment_details_kpay_section',
        __('Payment Details', 'kinesis-pay-gateway'),
        'kpay_order_details_section_callback',
        $screen,
        'side',
        'high'
    );
}

/**
 * Render Kpay payment details on order details page
 *
 * @param WC_Order $order
 * @return void
 */
function kpay_order_details_section_callback($order)
{
    $order_id = OrderUtil::custom_orders_table_usage_is_enabled() ? $order->get_id() : $order->ID;
    $order = wc_get_order($order_id);
    $payment_method = $order->get_payment_method();
    $payment_title = $order->get_payment_method_title();
    echo '<div class="order-payment-method" style="width: 100%;">';
    echo '<h4><strong>' . __('Payment Method: ', 'kinesis-pay-gateway') . $payment_title . '</strong></h4>';
    echo '</div>';

    if (!Kinesis_Pay_Gateway::is_payment_method_kinesis_pay($payment_method)) {
        return;
    }

    $payment = Kinesis_Pay_Gateway::get_payment_by_id(null, $order_id);
    if ($payment) {
        $options = get_option('woocommerce_kinesis_pay_gateway_settings');
        $payment_status = get_display_status($payment->payment_status, $payment->kpay_order_id);

        echo '<div class="kpay-order-payment-details" style="width: 100%; color: #777">';
        echo '<p><strong>' . __('Payment ID: ', 'kinesis-pay-gateway') . '</strong><br><input type="text" id="payment_id" value="' . $payment->payment_id . '"readonly /></p>';
        echo '<p><strong>' . __('Payment status: ', 'kinesis-pay-gateway') . '</strong><br><input type="text" id="payment_status" value="' . $payment_status . '"readonly /></p>';
        if ($payment->payment_currency) {
            echo '<p><strong>' . __('Payment currency: ', 'kinesis-pay-gateway') . '</strong><br><input type="text" id="payment_currency" value="' . $payment->payment_currency . '"readonly /></p>';
        }
        if ($payment->payment_amount && $payment->payment_amount > 0) {
            echo '<p><strong>' . __('Payment amount: ', 'kinesis-pay-gateway') . '</strong><br><input type="text" id="payment_amount" value="' . $payment->payment_amount . '"readonly /></p>';
        }
        if ($payment->payment_fee && $payment->payment_fee > 0) {
            echo '<p><strong>' . __('Payment fee: ', 'kinesis-pay-gateway') . '</strong><br><input type="text" id="payment_fee" value="' . $payment->payment_fee . '"readonly /></p>';
        }
        echo '<p><strong>' . __('Payment created at: ', 'kinesis-pay-gateway') . '</strong><br><input type="text" id="created_at" value="' . get_date_from_gmt(date($payment->created_at)) . '"readonly /></p>';
        echo '<p><strong>' . __('Payment updated at: ', 'kinesis-pay-gateway') . '</strong><br><input type="text" id="updated_at" value="' . get_date_from_gmt(date($payment->updated_at)) . '"readonly /></p>';
        if ($payment->expiry_at) {
            echo '<p><strong>' . __('Payment expiry at: ', 'kinesis-pay-gateway') . '</strong><br><input type="text" id="expiry_at" value="' . get_date_from_gmt(date($payment->expiry_at)) . '"readonly /></p>';
        }
        if ($options['debug_mode_enabled'] === 'yes' && $payment->description) {
            echo '<p><strong>' . __('Payment log:<br>', 'kinesis-pay-gateway') . '</strong><textarea readonly style="width: 100%; height: 108px;">' . str_replace('\n', '&#13;&#10;', $payment->description) . '</textarea></p>';
        }
        echo '</div>';
    } else {
        echo '<div class="kpay-order-payment-details" style="width: 100%; color: #777"><p>Payment details not found.</p></div>';
    }
}

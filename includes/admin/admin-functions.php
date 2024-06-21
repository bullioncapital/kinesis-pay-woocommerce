<?php

use Automattic\WooCommerce\Utilities\OrderUtil;

if (OrderUtil::custom_orders_table_usage_is_enabled()) {
    add_filter('manage_woocommerce_page_wc-orders_columns', 'kpay_shop_order_column', 10);
    add_action('manage_woocommerce_page_wc-orders_custom_column', 'kpay_orders_list_column_content', 10, 2);
    add_action('add_meta_boxes', 'kpay_order_details_meta_box');
} else {
    add_filter('manage_edit-shop_order_columns', 'kpay_shop_order_column', 10);
    add_action('manage_shop_order_posts_custom_column', 'kpay_orders_list_column_content', 10, 2);
    add_action('add_meta_boxes_shop_order', 'kpay_order_details_meta_box', 10, 2);
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
    // Inserting columns to a specific location
    foreach ($columns as $key => $column) {
        $reordered_columns[$key] = $column;
        if ($key === 'order_status') {
            // Inserting after "Status" column
            $reordered_columns['kinesis_pay_payment_id'] = __('Kinesis-Pay Payment ID');
        }
    }
    $reordered_columns['kinesis_pay_payment_id'] = __('Kinesis-Pay Payment ID');
    return $reordered_columns;
}

/**
 * get_display_status
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
 * @param  mixed $order
 * @return void
 */
function kpay_orders_list_column_content($column, $order)
{
    if ($column !== 'kinesis_pay_payment_id') {
        return;
    }
    $order_id = is_object($order) ? $order->get_id() : $order;
    global $wpdb;
    $tablename = $wpdb->prefix . 'kinesis_payments';
    $result = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $tablename WHERE `order_id` = %d ORDER BY `id` DESC",
        array($order_id)
    ));

    switch ($column) {
        case 'kinesis_pay_payment_id':
            if (!empty($result[0])) {
                echo $result[0]->payment_id;
            } else {
                echo '<small>-</small>';
            }
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
        __('Payment Details'),
        'kpay_order_details_section_callback',
        $screen,
        'side',
        'high'
    );
}

/**
 * Callback function to render the content of the meta box
 *
 * @param  object $order
 * @return void
 */
function kpay_order_details_section_callback($order)
{
    $order_id = OrderUtil::custom_orders_table_usage_is_enabled() ? $order->get_id() : $order->ID;
    $order = wc_get_order($order_id);
    $payment_method = $order->get_payment_method();
    $payment_title = $order->get_payment_method_title();
    echo '<div class="order-payment-method" style="width: 100%;">';
    echo '<h4><strong>' . __('Payment Method: ') . $payment_title . '</strong></h4>';
    echo '</div>';

    if ($payment_method === 'kinesis_pay_gateway' || $payment_method === 'kinesis-pay') {
        global $wpdb;
        $tablename = $wpdb->prefix . 'kinesis_payments';
        $preparedStatement = $wpdb->prepare(
            "SELECT * FROM $tablename WHERE `order_id` = %d ORDER BY `id` DESC",
            array($order->get_id())
        );
        $result = $wpdb->get_results($preparedStatement);
        if (!!count($result)) {
            $options = get_option('woocommerce_kinesis_pay_gateway_settings');
            $payment_id = $result[0]->payment_id;
            // If payment data is incomplete or seems expired, then call API to fetch the latest payment data and update the payment in DB
            if (
                !$result[0]->payment_currency
                || !$result[0]->expiry_at
                || !$result[0]->kpay_order_id
                || ($result[0]->payment_status !== 'processed' && $result[0]->payment_status !== 'cancelled' && strtotime($result[0]->expiry_at) <= current_time('timestamp', true))
            ) {
                $kpay_id = $result[0]->id;
                try {
                    $response = request_payment_status($payment_id);
                    $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE $tablename
                            SET `payment_status`= %s, `kpay_order_id` = %s, `payment_currency` = %s, `payment_amount` = %f, `payment_fee` = %f, `usd_converted_amount` = %f, `payment_kau_amount` = %f, `payment_kag_amount` = %f, `description` = %s, `created_at` = %s, `updated_at` = %s, `expiry_at` = %s
                            WHERE `id` = $kpay_id",
                            array(
                                $response->status,
                                isset($response->orderId) ? $response->orderId : null,
                                isset($response->paymentCurrency) ? $response->paymentCurrency : null,
                                isset($response->paymentAmount) ? $response->paymentAmount : null,
                                isset($response->paymentFee) ? $response->paymentFee : null,
                                isset($response->usdConvertedAmount) ? $response->usdConvertedAmount : null,
                                $response->paymentKauAmount,
                                $response->paymentKagAmount,
                                $result[0]->description . current_time('Y-m-d H:i:s', true) . ' Payment data synced with Kinesis Pay.\n',
                                $response->createdAt,
                                $response->updatedAt,
                                $response->expiryAt,
                            )
                        )
                    );
                    $result = $wpdb->get_results($preparedStatement);
                } catch (Exception $e) {
                    $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE $tablename
                            SET `description`= %s
                            WHERE `id` = $kpay_id",
                            array(
                                $result[0]->description . current_time('Y-m-d H:i:s', true) . ' Failed to fetch payment data: ' . $e->getMessage() . '\n',
                            )
                        )
                    );
                }
            }

            $payment_status = get_display_status($result[0]->payment_status, $result[0]->kpay_order_id);

            echo '<div class="kpay-order-payment-details" style="width: 100%; color: #777">';
            echo '<p><strong>' . __('Payment ID: ') . '</strong><br><input type="text" id="payment_id" value="' . $payment_id . '"readonly /></p>';
            echo '<p><strong>' . __('Payment status: ') . '</strong><br><input type="text" id="payment_status" value="' . $payment_status . '"readonly /></p>';
            if ($result[0]->payment_currency) {
                echo '<p><strong>' . __('Payment currency: ') . '</strong><br><input type="text" id="payment_currency" value="' . $result[0]->payment_currency . '"readonly /></p>';
            }
            if ($result[0]->payment_amount && $result[0]->payment_amount > 0) {
                echo '<p><strong>' . __('Payment amount: ') . '</strong><br><input type="text" id="payment_amount" value="' . $result[0]->payment_amount . '"readonly /></p>';
            }
            if ($result[0]->payment_fee && $result[0]->payment_fee > 0) {
                echo '<p><strong>' . __('Payment fee: ') . '</strong><br><input type="text" id="payment_fee" value="' . $result[0]->payment_fee . '"readonly /></p>';
            }
            echo '<p><strong>' . __('Payment created at: ') . '</strong><br><input type="text" id="created_at" value="' . get_date_from_gmt(date($result[0]->created_at)) . '"readonly /></p>';
            echo '<p><strong>' . __('Payment updated at: ') . '</strong><br><input type="text" id="updated_at" value="' . get_date_from_gmt(date($result[0]->updated_at)) . '"readonly /></p>';
            if ($result[0]->expiry_at) {
                echo '<p><strong>' . __('Payment expiry at: ') . '</strong><br><input type="text" id="expiry_at" value="' . get_date_from_gmt(date($result[0]->expiry_at)) . '"readonly /></p>';
            }
            if ($options['debug_mode_enabled'] === 'yes' && $result[0]->description) {
                echo '<p><strong>' . __('Payment log:<br>') . '</strong><textarea readonly style="width: 100%; height: 108px;">' . str_replace('\n', '&#13;&#10;', $result[0]->description) . '</textarea></p>';
            }
            echo '</div>';
        } else {
            echo '<div class="kpay-order-payment-details" style="width: 100%; color: #777">';
            echo '<p>Payment details not found.</p>';
            echo '</div>';
        }
    }
}

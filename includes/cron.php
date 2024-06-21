<?php
// Prevent public user to directly access .php files through URL
defined('ABSPATH') || exit;

/**
 * kinesis_pay_gateway_update_order_status
 *
 * @return void
 */
function kinesis_pay_gateway_update_order_status()
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'kinesis_payments';
  $results = $wpdb->get_results(
    "SELECT * FROM $table_name WHERE `payment_status` = 'pending' ORDER BY `id`"
  );

  if (!count($results)) {
    return;
  }

  $order_ids_to_be_updated = array();
  foreach ($results as $row) {
    $order_ids_to_be_updated[] = $row->id;
  }

  if (count($order_ids_to_be_updated)) {
    $wpdb->query(
      "UPDATE $table_name
	  SET payment_status = 'processed', updated_at = UTC_TIMESTAMP()
	  WHERE id in (" . implode(",", $order_ids_to_be_updated) . ")"
    );
  }
}

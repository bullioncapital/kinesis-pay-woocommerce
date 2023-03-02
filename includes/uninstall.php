<?php
/**
 * Kinesis-Pay-Gateway Uninstall
 *
 * Uninstalling Kinesis-Pay-Gateway
 *
 * @package Kinesis-Pay-Gateway\Uninstaller
 * @version 0.1.0
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

function kinesis_pay_gateway_uninstall() {
  global $wpdb;

  // All tables to be removed should be added to here
  $tables = array(
    'kinesis_payments',
  );

  // Remove all Kinesis Pay Gateway tables
  foreach( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}" . $table );
  }

  // Remove all Kinesis Pay Gateway options
  delete_option( 'kinesis_pay_gateway_version' );
}
<?php
/**
 * Kinesis-Pay-Gateway Uninstall
 *
 * Uninstalling Kinesis-Pay-Gateway
 *
 * @package Kinesis-Pay-Gateway\Uninstaller
 * @version 0.1.0
 */

 /**
  * If the plugin can not be written without running code within the plugin, then the plugin should create a file named 'uninstall.php' in the base plugin folder.
  * This file will be called, if it exists, during the uninstall process bypassing the uninstall hook.
  */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

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

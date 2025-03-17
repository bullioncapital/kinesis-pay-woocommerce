<?php

/**
 * Kinesis-Pay-Gateway Uninstall
 *
 * @package Kinesis-Pay-Gateway\Uninstaller
 */

/**
 * Will be called, if it exists, during the uninstall process bypassing the uninstall hook.
 */
defined('WP_UNINSTALL_PLUGIN') || exit;

global $wpdb;

// Remove all Kinesis Pay pages
$pages = array(
  'kpay-payment-error',
);
foreach ($pages as $page_slug) {
  $page = get_page_by_path($page_slug, OBJECT, 'page');
  if (isset($page)) {
    wp_delete_post($page->ID);
  }
}

// Remove all Kinesis Pay Gateway options
delete_network_option(null, 'kinesis_pay_gateway_version');

  $options = get_option('woocommerce_kinesis_pay_gateway_settings', []);
  $delete_table = isset($options['uninstall_deletes_table']) ? $options['uninstall_deletes_table'] : 'no';

// If deletion is enabled, drop the tables
if ($delete_table === 'yes') {
    // Tables to be deleted
    $tables = [
        'kinesis_payments',
    ];

    // Drop each table with error logging
    foreach ($tables as $table) {
        $full_table_name = $wpdb->prefix . $table;
        
        // Check if table exists before attempting to drop
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $full_table_name)) === $full_table_name) {
            $drop_result = $wpdb->query("DROP TABLE IF EXISTS `{$full_table_name}`");
            
            // Optional: Log the result (useful for debugging)
            error_log("Kinesis Pay: Dropping table {$full_table_name} - Result: " . ($drop_result !== false ? 'Success' : 'Failed'));
        }
    }
}

// Clear any scheduled cron jobs
wp_clear_scheduled_hook('kpay_sync_statuses');

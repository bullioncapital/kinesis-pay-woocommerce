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

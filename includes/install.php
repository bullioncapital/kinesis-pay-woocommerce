<?php

// Prevent public user to directly access .php files through URL
defined('ABSPATH') || exit;

global $kinesis_pay_gateway_version;
global $test_mode;
global $test_publishable_key;
global $test_private_key;
$kinesis_pay_gateway_version = '1.0.0'; // Latest plugin version
$test_mode = 'no';
$test_publishable_key = '';
$test_private_key = '';

/**
 * Mapping of version numbers and upgrade functions
 */
function get_kinesis_pay_gateway_updates()
{
  return array(
    '0.1.1' => array(
      'kinesis_pay_gateway_update_011',
    ),
  );
}

/**
 * Compare current plugin version with the new version
 * Run install function for first run
 * Run upgrade function(s) for upgrading version
 */
function kinesis_pay_gateway_update_db_check()
{
  kinesis_pay_gateway_setup_scheduled_jobs();

  global $kinesis_pay_gateway_version;
  $current_version = get_site_option('kinesis_pay_gateway_version');
  if (!$current_version) {
    kinesis_pay_gateway_install($current_version);
  } else if ($current_version !== $kinesis_pay_gateway_version) {
    kinesis_pay_gateway_upgrade($current_version);
  }
}

/**
 * Install function for first run
 */
function kinesis_pay_gateway_install($current_version)
{
  global $wpdb;
  global $kinesis_pay_gateway_version;

  $sql = "CREATE TABLE {$wpdb->prefix}kinesis_payments (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    order_id bigint(20) unsigned NOT NULL,
    payment_id varchar(200) NOT NULL,
    payment_status varchar(20) NOT NULL default 'pending',
    description longtext NULL,
    created_at datetime NULL,
    updated_at datetime NULL,
    PRIMARY KEY ID (id)
    ) CHARACTER SET utf8 COLLATE utf8_general_ci;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);
  if ($current_version === false) {
    add_option('kinesis_pay_gateway_version', $kinesis_pay_gateway_version);
  } else {
    update_option('kinesis_pay_gateway_version', $kinesis_pay_gateway_version);
  }
}

/**
 * Upgrade function for version 0.1.1
 */
function kinesis_pay_gateway_update_011()
{
  kinesis_pay_gateway_create_error_page();
}

function kinesis_pay_gateway_upgrade($current_version)
{
  global $kinesis_pay_gateway_version;
  foreach (get_kinesis_pay_gateway_updates() as $version => $update_callbacks) {
    // If the version from which we update is below the $version, call all update functions
    if (version_compare($current_version, $version, '<')) {
      foreach ($update_callbacks as $update_callback) {
        if (function_exists($update_callback)) {
          $update_callback();
        }
      }
    }
  }

  update_option('kinesis_pay_gateway_version', $kinesis_pay_gateway_version);
}

function get_order_status_checking_schedule($schedules)
{
  $schedules['kpay_order_status_checking_schedule'] = array(
    'interval'  => 300,
    'display'   => __('Kpay order status checking schedule', 'textdomain')
  );
  return $schedules;
}
add_filter('cron_schedules', 'get_order_status_checking_schedule');

/**
 * Setup cron job
 */
function kinesis_pay_gateway_setup_scheduled_jobs()
{
  $timestamp = wp_next_scheduled('kinesis_pay_gateway_event_update_order_status');
  if (!$timestamp) {
    wp_schedule_event(time(), 'kpay_order_status_checking_schedule', 'kinesis_pay_gateway_event_update_order_status');
    // wp_schedule_single_event(time() + 10, 'kinesis_pay_gateway_event_update_order_status');
  }
  add_action('kinesis_pay_gateway_event_update_order_status', 'kinesis_pay_gateway_update_order_status');
}

function remove_schedule()
{
  $timestamp = wp_next_scheduled('kinesis_pay_gateway_event_update_order_status');
  if ($timestamp) {
    wp_unschedule_event($timestamp, 'kinesis_pay_gateway_event_update_order_status');
  }
}
register_deactivation_hook(__FILE__, 'remove_schedule');

/**
 * Create payment error page
 */
function kinesis_pay_gateway_create_error_page()
{
  if (!current_user_can('activate_plugins')) {
    return;
  }
  $page_slug = 'kpay-payment-error'; // Slug of the Post
  $new_page = array(
    'post_type'     => 'page',         // Post Type Slug eg: 'page', 'post'
    'post_title'    => 'Payment Error',  // Title of the Content
    'post_content'  => 'Failed to finish Kinesis Pay payment.',  // Content
    'post_status'   => 'publish',      // Post Status
    'post_author'   => 1,          // Post Author ID
    'post_name'     => $page_slug      // Slug of the Post
  );
  if (!get_page_by_path($page_slug, OBJECT, 'page')) { // Check If Page Not Exits
    $new_page_id = wp_insert_post($new_page);
  }
}

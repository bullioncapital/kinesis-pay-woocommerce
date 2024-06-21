<?php

// Prevent public user to directly access .php files through URL
defined('ABSPATH') || exit;

global $kinesis_pay_gateway_version;
global $api_base_url;
global $kms_base_url;
global $test_mode;
global $test_api_base_url;
global $test_kms_base_url;

$kinesis_pay_gateway_version = '1.1.0'; // Latest plugin version
$test_mode = 'yes';  // yes | no
$api_base_url = 'https://apip.kinesis.money';
$kms_base_url = 'https://kms.kinesis.money';
$test_api_base_url = 'https://qa1-api.kinesis.money';
$test_kms_base_url = 'https://qa1-kms.kinesis.money';


/**
 * Mapping of version numbers and upgrade functions
 *
 * @return array
 */
function get_kinesis_pay_gateway_updates()
{
  return array(
    '1.0.0' => array(
      'kinesis_pay_gateway_update_1_0_0',
    ),
    '1.1.0' => array(
      'kinesis_pay_gateway_update_1_1_0',
    ),
  );
}

/**
 * Compare current plugin version with the new version
 * Run install function for first run
 * Run upgrade function(s) for upgrading version
 * 
 * @return void
 */
function kinesis_pay_gateway_update_db_check()
{
  global $kinesis_pay_gateway_version;
  $version_key = 'kinesis_pay_gateway_version';
  $current_version = get_option($version_key);

  /**
   * If first run, then run all upgrade functions
   * If not first run, then run new update functions
   */
  foreach (get_kinesis_pay_gateway_updates() as $version => $update_callbacks) {
    /**
     * If first run (no current version) or the update function version is greater than plugin's current version,
     * then run the function
     */
    if (
      (!$current_version || version_compare($current_version, $version, '<'))
      && version_compare($version, $kinesis_pay_gateway_version, '<=')
    ) {
      foreach ($update_callbacks as $update_callback) {
        if (function_exists($update_callback)) {
          $update_callback();
        }
      }
    }
  }
  if (!$current_version) {
    add_option($version_key, $kinesis_pay_gateway_version);
  } else {
    update_option($version_key, $kinesis_pay_gateway_version);
  }
}

/**
 * Upgrade function for version 1.0.0
 *
 * @return void
 */
function kinesis_pay_gateway_update_1_0_0()
{
  global $wpdb;
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

  kinesis_pay_gateway_setup_scheduled_jobs();
  kinesis_pay_gateway_update_1_0_0_add_page();
}

/**
 * get_order_status_checking_schedule
 *
 * @param  array $schedules
 * @return array
 */
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
 * 
 * @return void
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

/**
 * remove_schedule
 *
 * @return void
 */
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
 *
 * @return void
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
    wp_insert_post($new_page);
  }
}

/**
 * Add page for version 1.0.0
 *
 * @return void
 */
function kinesis_pay_gateway_update_1_0_0_add_page()
{
  add_action('init', 'kinesis_pay_gateway_create_error_page');
}

/**
 * Upgrade function for version 1.1.0
 *
 * @return void
 */
function kinesis_pay_gateway_update_1_1_0()
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'kinesis_payments';
  $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$table_name}' AND column_name = 'kpay_order_id'");

  if (empty($row)) {
    $sql = "ALTER TABLE `{$table_name}`
      ADD kpay_order_id varchar(30) AFTER `payment_status`,
      ADD payment_currency varchar(8) AFTER `kpay_order_id`,
      ADD payment_amount decimal(16, 5) AFTER `payment_currency`,
      ADD payment_fee decimal(16, 5) AFTER `payment_amount`,
      ADD usd_converted_amount decimal(16, 2) AFTER `payment_fee`,
      ADD payment_kau_amount decimal(16, 5) AFTER `usd_converted_amount`,
      ADD payment_kag_amount decimal(16, 5) AFTER `payment_kau_amount`,
      ADD expiry_at datetime AFTER `updated_at`,
      MODIFY column order_id bigint(20) unsigned NULL
    ";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $wpdb->query($sql);
  }
}

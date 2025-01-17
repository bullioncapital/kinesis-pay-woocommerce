<?php

// Prevent public user to directly access .php files through URL
defined('ABSPATH') || exit;

global $kinesis_pay_gateway_version;
global $api_base_url;
global $kms_base_url;
global $test_api_base_url;
global $test_kms_base_url;

$kinesis_pay_gateway_version = '1.1.1'; // Latest plugin version
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
    '1.1.1' => array(
      'kinesis_pay_gateway_update_1_1_1',
    ),
    '2.0.0' => array(
      'kinesis_pay_gateway_update_2_0_0',
    )
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
  $plugin_version = KINESIS_PAY_VERSION; // Latest plugin version
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
      && version_compare($version, $plugin_version, '<=')
    ) {
      foreach ($update_callbacks as $update_callback) {
        if (function_exists($update_callback)) {
          $update_callback();
        }
      }
    }
  }
  if (!$current_version) {
    add_option($version_key, $plugin_version);
  } else {
    update_option($version_key, $plugin_version);
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

/**
 * Upgrade function for version 1.1.1
 *
 * @return void
 */
function kinesis_pay_gateway_update_1_1_1()
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'kinesis_payments';
  $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$table_name}' AND column_name = 'from_address'");

  if (empty($row)) {
    $sql = "ALTER TABLE `{$table_name}`
      ADD from_address varchar(60) AFTER `expiry_at`,
      ADD to_address varchar(60) AFTER `from_address`,
      ADD transaction_hash varchar(76) AFTER `to_address`
    ";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $wpdb->query($sql);
  }
}

/**
 * Remove legacy error page
 *
 * @return void
 */
function remove_error_page()
{
  $page = get_page_by_path('kpay-payment-error', OBJECT, 'page');
  if (isset($page)) {
    wp_delete_post($page->ID);
  }
}

/**
 * Upgrade function for version 2.0.0
 *
 * @return void
 */
function kinesis_pay_gateway_update_2_0_0()
{
  add_action('init', 'remove_error_page');
}

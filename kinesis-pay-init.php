<?php
/**
 * Plugin Name: Kinesis Pay Gateway
 * Plugin URI: https://github.com/bullioncapital/kinesis-pay-woocommerce
 * Author: Kinesis Money
 * Author URI: https://kinesis.money/
 * Description: Pay with Kinesis Money
 * Version: 1.0.0
 */
// Prevent public user to directly access .php files through URL
defined( 'ABSPATH' ) || exit;

// Define Version
if (!defined('KINESIS_PAY_VER')) {
  define('KINESIS_PAY_VER', 1);
}

// Define Directory URL.
if (!defined('KINESIS_PAY_DIR_URL')) {
  define('KINESIS_PAY_DIR_URL', plugin_dir_url(__FILE__));
}

// Define Directory PATH.
if (!defined('KINESIS_PAY_DIR_PATH')) {
  define('KINESIS_PAY_DIR_PATH', plugin_dir_path(__FILE__));
}

// Define Payment ID.
if (!defined('KINESIS_PAY_PAY_ID')) {
  define('KINESIS_PAY_PAY_ID', 'kinesis_pay');
}

// Define Option.
if (!defined('KINESIS_PAY_OPTION')) {
  define('KINESIS_PAY_OPTION', 'kinesis_pay_option');
}

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
  return;
}

add_action('plugins_loaded', 'kinesis_pay_payment_init', 11);
add_filter('woocommerce_payment_gateways', 'add_to_woo_kinesis_pay_payment_gateway');

function kinesis_pay_payment_init()
{
  require_once KINESIS_PAY_DIR_PATH . 'includes/cron.php';
  require_once KINESIS_PAY_DIR_PATH . 'includes/install.php';
  require_once KINESIS_PAY_DIR_PATH . 'includes/api.php';
  require_once KINESIS_PAY_DIR_PATH . 'includes/ajax.php';
  if (class_exists('WC_Payment_Gateway')) {
    require_once KINESIS_PAY_DIR_PATH . '/includes/class-kinesis-pay-gateway.php';
  }
  require_once KINESIS_PAY_DIR_PATH . 'includes/admin/admin-functions.php';

  kinesis_pay_gateway_update_db_check();
}

function add_to_woo_kinesis_pay_payment_gateway($gateways)
{
  $gateways[] = 'Kinesis_Pay_Gateway';
  return $gateways;
}

// Load CSS
add_action('init', 'register_script');
function register_script()
{
  wp_register_style('styles', KINESIS_PAY_DIR_URL . 'assets/css/style.css', false, '1.0.0', 'all');
}

add_action('wp_enqueue_scripts', 'enqueue_style');
function enqueue_style()
{
  wp_enqueue_style('styles');
}

// Overriding WooCommerce checkout.js
function custom_woo_javascript() {
  $filePath = KINESIS_PAY_DIR_URL . 'assets/js/frontend/checkout.js';
  wp_deregister_script('wc-checkout');
  wp_register_script('wc-checkout', $filePath, array('jquery'));
  wp_enqueue_script('wc-checkout');
}

add_action( 'wp_enqueue_scripts', 'custom_woo_javascript' );

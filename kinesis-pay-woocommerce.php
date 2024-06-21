<?php

/**
 * Plugin Name: Kinesis Pay Gateway
 * Plugin URI: https://github.com/bullioncapital/kinesis-pay-woocommerce
 * Author: Kinesis Money
 * Author URI: https://kinesis.money/
 * Description: Pay with Kinesis Money
 * Version: 1.1.0
 */
// Prevent public user to directly access .php files through URL
defined('ABSPATH') || exit;

include_once(ABSPATH . 'wp-admin/includes/plugin.php');
if (!is_plugin_active('woocommerce/woocommerce.php')) {
  return;
}

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

add_action('plugins_loaded', 'kinesis_pay_payment_init', 11);
add_filter('woocommerce_payment_gateways', 'add_to_woo_kinesis_pay_payment_gateway');

/**
 * kinesis_pay_payment_init
 *
 * @return void
 */
function kinesis_pay_payment_init()
{
  if (!class_exists('WC_Payment_Gateway')) {
    return;
  }
  require_once KINESIS_PAY_DIR_PATH . 'includes/cron.php';
  require_once KINESIS_PAY_DIR_PATH . 'includes/install.php';
  require_once KINESIS_PAY_DIR_PATH . 'includes/api.php';
  require_once KINESIS_PAY_DIR_PATH . 'includes/ajax.php';
  require_once KINESIS_PAY_DIR_PATH . 'includes/class-kinesis-pay-gateway.php';
  require_once KINESIS_PAY_DIR_PATH . 'includes/admin/admin-functions.php';
  kinesis_pay_gateway_update_db_check();
}

/**
 * add_to_woo_kinesis_pay_payment_gateway
 *
 * @param  array $gateways
 * @return array
 */
function add_to_woo_kinesis_pay_payment_gateway($gateways)
{
  $isBlockBasedCheckout = WC_Blocks_Utils::has_block_in_page(wc_get_page_id('checkout'), 'woocommerce/checkout');
  if ($isBlockBasedCheckout) {
    return $gateways;
  }
  $gateways[] = 'Kinesis_Pay_Gateway';
  return $gateways;
}

// Load CSS
add_action('init', 'kpay_register_script');
/**
 * kpay_register_script
 *
 * @return void
 */
function kpay_register_script()
{
  wp_register_style('kpay-checkout-styles', KINESIS_PAY_DIR_URL . 'assets/css/style.css');
}

add_action('wp_enqueue_scripts', 'kpay_enqueue_style');
/**
 * kpay_enqueue_style
 *
 * @return void
 */
function kpay_enqueue_style()
{
  $isBlockBasedCheckout = WC_Blocks_Utils::has_block_in_page(wc_get_page_id('checkout'), 'woocommerce/checkout');
  if (is_checkout() && !$isBlockBasedCheckout) {
    wp_enqueue_style('kpay-checkout-styles');
  }
}

/**
 * Overriding WooCommerce checkout.js
 *
 * @return void
 */
function kpay_checkout_script()
{
  $isBlockBasedCheckout = WC_Blocks_Utils::has_block_in_page(wc_get_page_id('checkout'), 'woocommerce/checkout');
  if (is_checkout() && !$isBlockBasedCheckout) {
    $filePath = KINESIS_PAY_DIR_URL . 'assets/js/frontend/checkout.js';
    wp_deregister_script('wc-checkout');
    wp_register_script('wc-checkout', $filePath, array('jquery'));
    wp_enqueue_script('wc-checkout');

    wp_enqueue_script('kpay-qr-code-lib', KINESIS_PAY_DIR_URL . 'assets/js/lib/qrcode.min.js');
    wp_enqueue_script('kpay-wc-checkout-preload', KINESIS_PAY_DIR_URL . 'assets/js/frontend/checkout-preload.js');
  }
}
add_action('wp_enqueue_scripts', 'kpay_checkout_script');

/**
 * Custom function to declare compatibility with cart_checkout_blocks feature 
 */
function kpay_declare_cart_checkout_blocks_compatibility()
{
  // Check if the required class exists
  if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
    // Declare compatibility for 'cart_checkout_blocks'
    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, false);
  }
}
// Hook the custom function to the 'before_woocommerce_init' action
add_action('before_woocommerce_init', 'kpay_declare_cart_checkout_blocks_compatibility');

<?php

// Prevent public user to directly access .php files through URL
defined('ABSPATH') || exit;

/**
 * Kinesis Pay Gateway.
 *
 * Provides a Kinesis Pay Payment Gateway.
 *
 * @class       Kinesis_Pay_Gateway
 * @extends     WC_Payment_Gateway
 * @version     1.1.0
 */
class Kinesis_Pay_Gateway extends WC_Payment_Gateway
{
  const PAYMENT_METHOD_ID = 'kinesis_pay_gateway';
  const STATUS_PROCESSED = 'processed';

  /**
   * test mode
   *
   * @var boolean
   */
  protected $testmode;

  /**
   * Kinesis Pay merchant id
   *
   * @var string
   */
  protected $merchant_id;

  /**
   * Private key
   *
   * @var string
   */
  protected $private_key;

  /**
   * Public key
   *
   * @var string
   */
  protected $publishable_key;

  /**
   * Constructor for the gateway.
   */
  public function __construct()
  {
    $this->id = self::PAYMENT_METHOD_ID; // payment gateway plugin ID

    // Method with all the options fields
    $this->init_form_fields();

    // Load the settings.
    $this->init_settings();

    // This action hook saves the settings
    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

    // We need custom JavaScript to obtain a token
    add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
  }

  /**
   * Initialise Gateway Settings Form Fields.
   *
   * @return void
   */
  public function init_form_fields()
  {
    global $test_mode;
    $prod_fields = array(
      'enabled' => array(
        'title' => 'Enable/Disable',
        'label' => 'Enable Kinesis Pay Gateway',
        'type' => 'checkbox',
        'description' => '',
        'default' => 'no'
      ),
      'merchant_id' => array(
        'title' => 'A merchant id generated by kinesis',
        'type' => 'text'
      ),
      'publishable_key' => array(
        'title' => 'Live Publishable Key',
        'type' => 'text'
      ),
      'private_key' => array(
        'title' => 'Live Private Key',
        'type' => 'password'
      ),
      'debug_mode_enabled' => array(
        'title' => 'Show Payment Log',
        'label' => 'Enabled',
        'type' => 'checkbox',
        'description' => '',
        'default' => 'no'
      ),
    );
    if ($test_mode === 'yes') {
      $test_fields = array(
        'test_merchant_id' => array(
          'title' => 'TEST Merchant ID',
          'type' => 'text'
        ),
        'test_publishable_key' => array(
          'title' => 'TEST Publishable Key',
          'type' => 'text'
        ),
        'test_private_key' => array(
          'title' => 'TEST Private Key',
          'type' => 'password'
        ),
      );
      $this->form_fields = array_merge($prod_fields, $test_fields);
    } else {
      $this->form_fields = $prod_fields;
    }
  }

  /**
   * init_settings
   *
   * @return void
   */
  public function init_settings()
  {
    parent::init_settings();

    global $test_mode;
    $test_mode_enabled = 'yes' === $test_mode;
    $this->title = __('Kinesis Pay');
    $this->description = __('Pay with Gold or Silver using Kinesis Pay');
    $this->has_fields         = false;
    $this->method_title = $test_mode_enabled ? __('Kinesis Pay - TEST MODE') : __('Kinesis Pay');
    $this->method_description = __('Pay with Gold or Silver using Kinesis Pay');
    $this->testmode = $test_mode_enabled;
    $this->merchant_id = $this->get_option('merchant_id');
    $this->private_key = $this->testmode ? $this->get_option('test_private_key') : $this->get_option('private_key');
    $this->publishable_key = $this->testmode ? $this->get_option('test_publishable_key') : $this->get_option('publishable_key');
  }

  /**
   * payment_scripts
   *
   * @return void
   */
  public function payment_scripts()
  {
    // we need JavaScript to process a token only on cart/checkout pages, right?
    if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
      return;
    }

    // if our payment gateway is disabled, we do not have to enqueue JS too
    if ('no' === $this->enabled) {
      return;
    }

    // no reason to enqueue JavaScript if API keys are not set
    if (empty($this->private_key) || empty($this->publishable_key)) {
      return;
    }

    // do not work with card details without SSL unless your website is in a test mode
    if (!$this->testmode && !is_ssl()) {
      return;
    }

    // in most payment processors you have to use PUBLIC KEY to obtain a token
    wp_localize_script(
      'woocommerce_kinesis_pay',
      'kinesis_params',
      array(
        'publishableKey' => $this->publishable_key
      )
    );
  }

  /**
   * Render the payment fields
   *
   * @return void
   */
  public function payment_fields()
  {
    if ($this->description) {
      if ($this->testmode) {
        $this->description .= ' *** TEST MODE ENABLED ***';
        $this->description = trim($this->description);
      }
      echo wpautop(wp_kses_post($this->description));
    }

    do_action('woocommerce_credit_card_form_start', $this->id);
    do_action('woocommerce_credit_card_form_end', $this->id);
  }

  /**
   * Update payment details
   *
   * @param  object $payment_details
   * @param  string $description
   * @return void
   */
  protected function update_payment_details($payment_details, $description)
  {
    global $wpdb;
    $tablename = $wpdb->prefix . 'kinesis_payments';

    $result = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM $tablename WHERE `payment_id` = %s ORDER BY `id` DESC",
      array($payment_details->payment_id)
    ));
    if (!!count($result)) {
      $kpay_id = $result[0]->id;
      $wpdb->query(
        $wpdb->prepare(
          "UPDATE $tablename
          SET `payment_status`= %s, order_id = %d, kpay_order_id = %s, payment_currency = %s, payment_amount = %.5f, payment_fee = %.5f, usd_converted_amount = %.2f, payment_kau_amount = %.5f, payment_kag_amount = %.5f, updated_at = %s, created_at = %s, expiry_at = %s, `description` = %s
          WHERE `id` = $kpay_id",
          array(
            $payment_details->payment_status,
            $payment_details->order_id,
            $payment_details->kpay_order_id,
            $payment_details->payment_currency,
            $payment_details->payment_amount,
            $payment_details->payment_fee,
            $payment_details->usd_converted_amount,
            $payment_details->payment_kau_amount,
            $payment_details->payment_kag_amount,
            $payment_details->updated_at,
            $payment_details->created_at,
            $payment_details->expiry_at,
            $result[0]->description . current_time('Y-m-d H:i:s', true) . ' ' . $description . '\n',
          )
        )
      );
    }
  }

  /**
   * Process the payment and return the result.
   *
   * @param int $order_id Order ID.
   * @return array
   */
  public function process_payment($order_id)
  {
    $payment_id = $_POST['kpay-payment-id'];

    $payment_status_ok = false;
    $response = request_payment_status($payment_id);

    $payment_status_ok = isset($response->status) && $response->status === 'processed';

    $payment_details = new stdClass;
    $payment_details->order_id = $order_id;
    $payment_details->payment_id = $payment_id;
    $payment_details->payment_status = $response->status;
    $payment_details->kpay_order_id = isset($response->orderId) ? $response->orderId : null;
    $payment_details->payment_currency = isset($response->paymentCurrency) ? $response->paymentCurrency : null;
    $payment_details->payment_amount = isset($response->paymentAmount) ? $response->paymentAmount : null;
    $payment_details->payment_fee = isset($response->paymentFee) ? $response->paymentFee : null;
    $payment_details->usd_converted_amount = isset($response->usdConvertedAmount) ? $response->usdConvertedAmount : null;
    $payment_details->payment_kau_amount = $response->paymentKauAmount;
    $payment_details->payment_kag_amount = $response->paymentKagAmount;
    $payment_details->created_at = $response->createdAt;
    $payment_details->updated_at = $response->updatedAt;
    $payment_details->expiry_at = $response->expiryAt;

    if (!$payment_status_ok) {
      throw new Exception(__('Incorrect payment status.', 'kinesis-pay-gateway'));
    }
    $this->update_payment_details($payment_details, 'Payment processed. Updated payment details.');

    $payment_approved = false;
    $response = request_approve_payment($payment_id, $order_id);

    $payment_approved = isset($response->status) && $response->status === self::STATUS_PROCESSED;

    if (!$payment_approved) {
      return array(
        'result' => 'error',
        'redirect' => 'kpay-payment-error'
      );
    }

    /**
     * processed is the final payment status
     * kpay_order_id will be filled once the payment is approved/confirmed
     */
    $payment_details->kpay_order_id = isset($response->orderId) ? $response->orderId : null;
    $payment_details->payment_status = $response->status;
    $payment_details->updated_at = $response->updatedAt;
    $this->update_payment_details($payment_details, 'Payment confirmed. Updated payment order_id.');

    $order = wc_get_order($order_id);

    if ($order->get_total() <= 0) {
      wc_add_notice('Connection error.', 'error');
      return array('result' => 'failure');
    }

    $order->payment_complete();

    if (isset(WC()->cart)) {
      WC()->cart->empty_cart();
    }

    return array(
      'result' => 'success',
      'redirect' => $this->get_return_url($order)
    );
  }
}

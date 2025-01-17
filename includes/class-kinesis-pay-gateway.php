<?php
// Prevent public user to directly access .php files through URL
defined('ABSPATH') || exit;

/**
 * Kinesis Pay Gateway
 *
 * Provides a Kinesis Pay Payment Gateway
 *
 * @class       Kinesis_Pay_Gateway
 * @extends     WC_Payment_Gateway
 */
class Kinesis_Pay_Gateway extends WC_Payment_Gateway
{
    const PAYMENT_METHOD_ID = 'kinesis_pay_gateway';

    const STATUS_CREATED = 'created';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_PROCESSED = 'processed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';

    const SUPPORTED_CURRENCIES = array(
        "AED", // "United Arab Emirates Dirham"
        "AFN", // "Afghan Afghani"
        "ALL", // "Albanian Lek"
        "AMD", // "Armenian Dram"
        "ANG", // "Netherlands Antillean Guilder"
        "AOA", // "Angolan Kwanza"
        "ARS", // "Argentine Peso"
        "AUD", // "Australian Dollar"
        "AWG", // "Aruban Florin"
        "AZN", // "Azerbaijani Manat"
        "BAM", // "Bosnia-Herzegovina Convertible Mark"
        "BBD", // "Barbadian Dollar"
        "BDT", // "Bangladeshi Taka"
        "BGN", // "Bulgarian Lev"
        "BHD", // "Bahraini Dinar"
        "BIF", // "Burundian Franc"
        "BMD", // "Bermudan Dollar"
        "BND", // "Brunei Dollar"
        "BOB", // "Bolivian Boliviano"
        "BRL", // "Brazilian Real"
        "BSD", // "Bahamian Dollar"
        "BTN", // "Bhutanese Ngultrum"
        "BWP", // "Botswanan Pula"
        "BYN", // "New Belarusian Ruble"
        "BYR", // "Belarusian Ruble"
        "BZD", // "Belize Dollar"
        "CAD", // "Canadian Dollar"
        "CDF", // "Congolese Franc"
        "CHF", // "Swiss Franc"
        "CLF", // "Chilean Unit of Account (UF)"
        "CLP", // "Chilean Peso"
        "CNY", // "Chinese Yuan"
        "COP", // "Colombian Peso"
        "CRC", // "Costa Rican Colón"
        "CUC", // "Cuban Convertible Peso"
        "CUP", // "Cuban Peso"
        "CVE", // "Cape Verdean Escudo"
        "CZK", // "Czech Republic Koruna"
        "DJF", // "Djiboutian Franc"
        "DKK", // "Danish Krone"
        "DOP", // "Dominican Peso"
        "DZD", // "Algerian Dinar"
        "EGP", // "Egyptian Pound"
        "ERN", // "Eritrean Nakfa"
        "ETB", // "Ethiopian Birr"
        "EUR", // "Euro"
        "FJD", // "Fijian Dollar"
        "FKP", // "Falkland Islands Pound"
        "GBP", // "British Pound Sterling"
        "GEL", // "Georgian Lari"
        "GGP", // "Guernsey Pound"
        "GHS", // "Ghanaian Cedi"
        "GIP", // "Gibraltar Pound"
        "GMD", // "Gambian Dalasi"
        "GNF", // "Guinean Franc"
        "GTQ", // "Guatemalan Quetzal"
        "GYD", // "Guyanaese Dollar"
        "HKD", // "Hong Kong Dollar"
        "HNL", // "Honduran Lempira"
        "HRK", // "Croatian Kuna"
        "HTG", // "Haitian Gourde"
        "HUF", // "Hungarian Forint"
        "IDR", // "Indonesian Rupiah"
        "ILS", // "Israeli New Sheqel"
        "IMP", // "Manx pound"
        "INR", // "Indian Rupee"
        "IQD", // "Iraqi Dinar"
        "IRR", // "Iranian Rial"
        "ISK", // "Icelandic Króna"
        "JEP", // "Jersey Pound"
        "JMD", // "Jamaican Dollar"
        "JOD", // "Jordanian Dinar"
        "JPY", // "Japanese Yen"
        "KES", // "Kenyan Shilling"
        "KGS", // "Kyrgystani Som"
        "KHR", // "Cambodian Riel"
        "KMF", // "Comorian Franc"
        "KPW", // "North Korean Won"
        "KRW", // "South Korean Won"
        "KWD", // "Kuwaiti Dinar"
        "KYD", // "Cayman Islands Dollar"
        "KZT", // "Kazakhstani Tenge"
        "LAK", // "Laotian Kip"
        "LBP", // "Lebanese Pound"
        "LKR", // "Sri Lankan Rupee"
        "LRD", // "Liberian Dollar"
        "LSL", // "Lesotho Loti"
        "LTL", // "Lithuanian Litas"
        "LVL", // "Latvian Lats"
        "LYD", // "Libyan Dinar"
        "MAD", // "Moroccan Dirham"
        "MDL", // "Moldovan Leu"
        "MGA", // "Malagasy Ariary"
        "MKD", // "Macedonian Denar"
        "MMK", // "Myanma Kyat"
        "MNT", // "Mongolian Tugrik"
        "MOP", // "Macanese Pataca"
        "MRO", // "Mauritanian Ouguiya"
        "MUR", // "Mauritian Rupee"
        "MVR", // "Maldivian Rufiyaa"
        "MWK", // "Malawian Kwacha"
        "MXN", // "Mexican Peso"
        "MYR", // "Malaysian Ringgit"
        "MZN", // "Mozambican Metical"
        "NAD", // "Namibian Dollar"
        "NGN", // "Nigerian Naira"
        "NIO", // "Nicaraguan Córdoba"
        "NOK", // "Norwegian Krone"
        "NPR", // "Nepalese Rupee"
        "NZD", // "New Zealand Dollar"
        "OMR", // "Omani Rial"
        "PAB", // "Panamanian Balboa"
        "PEN", // "Peruvian Nuevo Sol"
        "PGK", // "Papua New Guinean Kina"
        "PHP", // "Philippine Peso"
        "PKR", // "Pakistani Rupee"
        "PLN", // "Polish Zloty"
        "PYG", // "Paraguayan Guarani"
        "QAR", // "Qatari Rial"
        "RON", // "Romanian Leu"
        "RSD", // "Serbian Dinar"
        "RUB", // "Russian Ruble"
        "RWF", // "Rwandan Franc"
        "SAR", // "Saudi Riyal"
        "SBD", // "Solomon Islands Dollar"
        "SCR", // "Seychellois Rupee"
        "SDG", // "Sudanese Pound"
        "SEK", // "Swedish Krona"
        "SGD", // "Singapore Dollar"
        "SHP", // "Saint Helena Pound"
        "SLE", // "Sierra Leonean Leone"
        "SLL", // "Sierra Leonean Leone"
        "SOS", // "Somali Shilling"
        "SRD", // "Surinamese Dollar"
        "SSP", // "South Sudanese Pound"
        "STD", // "São Tomé and Príncipe Dobra"
        "SVC", // "Salvadoran Colón"
        "SYP", // "Syrian Pound"
        "SZL", // "Swazi Lilangeni"
        "THB", // "Thai Baht"
        "TJS", // "Tajikistani Somoni"
        "TMT", // "Turkmenistani Manat"
        "TND", // "Tunisian Dinar"
        "TOP", // "Tongan Paʻanga"
        "TRY", // "Turkish Lira"
        "TTD", // "Trinidad and Tobago Dollar"
        "TWD", // "New Taiwan Dollar"
        "TZS", // "Tanzanian Shilling"
        "UAH", // "Ukrainian Hryvnia"
        "UGX", // "Ugandan Shilling"
        "USD", // "United States Dollar"
        "UYU", // "Uruguayan Peso"
        "UZS", // "Uzbekistan Som"
        "VEF", // "Venezuelan Bolívar Fuerte"
        "VES", // "Sovereign Bolivar"
        "VND", // "Vietnamese Dong"
        "VUV", // "Vanuatu Vatu"
        "WST", // "Samoan Tala"
        "XAF", // "CFA Franc BEAC"
        "XCD", // "East Caribbean Dollar"
        "XOF", // "CFA Franc BCEAO"
        "XPF", // "CFP Franc"
        "YER", // "Yemeni Rial"
        "ZAR", // "South African Rand"
        "ZMK", // "Zambian Kwacha (pre-2013)"
        "ZMW", // "Zambian Kwacha"
        "ZWL", // "Zimbabwean Dollar"
    );

    /**
     * Test mode
     *
     * @var boolean
     */
    private $test_mode;

    /**
     * Replace payment gateway title with icon
     *
     * @var string
     */
    private $replace_title_with_icon;

    /**
     * Hide payment gateway description
     *
     * @var string
     */
    private $hide_description;

    /**
     * Kinesis Pay merchant id
     *
     * @var string
     */
    private $merchant_id;

    /**
     * Private key
     *
     * @var string
     */
    private $private_key;

    /**
     * Public key
     *
     * @var string
     */
    private $publishable_key;

    /**
     * Unpaid order status
     *
     * @var string
     */
    private $unpaid_order_status;

    /**
     * Capture-fund ready payment status
     *
     * @var string
     */
    private $capture_ready_payment_status;

    /**
     * Check if payment method is Kinesis Pay
     *
     * @param  string $payment_method
     * @return boolean
     */
    public static function is_payment_method_kinesis_pay($payment_method)
    {
        return $payment_method === 'kinesis_pay_gateway' || $payment_method === 'kinesis-pay';
    }

    /**
     * Get order's Kpay payment by payment_id
     *
     * @param  string $payment_id
     * @param  int $order_id
     * @return object
     */
    public static function get_payment_by_id($payment_id, $order_id = null)
    {
        if (!$payment_id && $order_id === null) {
            return null;
        }
        global $wpdb;
        $tablename = $wpdb->prefix . 'kinesis_payments';
        $query = $payment_id ? $wpdb->prepare(
            "SELECT * FROM $tablename WHERE `payment_id` = %s ORDER BY `id` DESC",
            array($payment_id)
        ) : $wpdb->prepare(
            "SELECT * FROM $tablename WHERE `order_id` = %d ORDER BY `id` DESC",
            array($order_id)
        );
        $result = $wpdb->get_results($query);
        return !!count($result) ? $result[0] : null;
    }

    /**
     * Append new message
     *
     * @param  string $origin
     * @param  string $new
     * @return string
     */
    private static function append_message($origin, $new)
    {
        if (empty($new)) {
            return $origin;
        }
        return sprintf('%s%s %s', empty($origin) ? '' : $origin . '\n', current_time('Y-m-d H:i:s', true), $new);
    }

    /**
     * Update payment details in database from API response
     *
     * @param  string $payment_id
     * @param  int $order_id
     * @param  object $response
     * @param  string $message
     * @param  string $error
     * @return object
     */
    private static function insert_or_update_payment($payment_id, $order_id, $response, $message = null, $error = null)
    {
        global $wpdb;
        $tablename = $wpdb->prefix . 'kinesis_payments';
        $query_payment = self::get_payment_by_id($payment_id);
        if ($query_payment) {
            if ($response) {
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE $tablename
                            SET `order_id` = %d, `payment_id` = %s, `payment_status`= %s, `kpay_order_id` = %s, `payment_currency` = %s, `payment_amount` = %f, `payment_fee` = %f, `usd_converted_amount` = %f, `payment_kau_amount` = %f, `payment_kag_amount` = %f, `created_at` = %s, `updated_at` = %s, `expiry_at` = %s, `from_address` = %s, `to_address` = %s, `transaction_hash` = %s, `description` = %s
                            WHERE `id` = %s",
                        array(
                            $order_id,
                            $payment_id,
                            $response->status,
                            isset($response->orderId) ? $response->orderId : $query_payment->kpay_order_id,
                            isset($response->paymentCurrency) ? $response->paymentCurrency : $query_payment->payment_currency,
                            isset($response->paymentAmount) ? $response->paymentAmount : $query_payment->payment_amount,
                            isset($response->paymentFee) ? $response->paymentFee : $query_payment->payment_fee,
                            isset($response->usdConvertedAmount) ? $response->usdConvertedAmount : $query_payment->usd_converted_amount,
                            $response->paymentKauAmount,
                            $response->paymentKagAmount,
                            $response->createdAt,
                            $response->updatedAt,
                            $response->expiryAt,
                            isset($response->fromAddress) ? $response->fromAddress : $query_payment->from_address,
                            isset($response->toAddress) ? $response->toAddress : $query_payment->to_address,
                            isset($response->transactionHash) ? $response->transactionHash : $query_payment->transaction_hash,
                            self::append_message($query_payment->description, sprintf('Payment data updated.%s', empty($message) ? '' : ' ' . $message)),
                            $query_payment->id,
                        )
                    )
                );
            } else if ($error) {
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE $tablename
                            SET `description`= %s
                            WHERE `id` = %s",
                        array(
                            self::append_message($query_payment->description, sprintf('Failed to fetch payment data: %s', $error)),
                            $query_payment->id,
                        )
                    )
                );
            }
        } else {
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO $tablename
                        ( order_id, payment_id, payment_status, kpay_order_id, payment_currency, payment_amount, payment_fee, usd_converted_amount, payment_kau_amount, payment_kag_amount, expiry_at, created_at, updated_at, from_address, to_address, transaction_hash, `description` )
                        VALUES ( %d, %s, %s, %s, %s, %.5f, %.5f, %.2f, %.5f, %.5f, %s, %s, %s, %s, %s, %s, %s )",
                    $order_id,
                    $payment_id,
                    $response->status,
                    isset($response->orderId) ? $response->orderId : null,
                    isset($response->paymentCurrency) ? $response->paymentCurrency : null,
                    isset($response->paymentAmount) ? $response->paymentAmount : null,
                    isset($response->paymentFee) ? $response->paymentFee : null,
                    isset($response->usdConvertedAmount) ? $response->usdConvertedAmount : null,
                    $response->paymentKauAmount,
                    $response->paymentKagAmount,
                    $response->createdAt,
                    $response->updatedAt,
                    $response->expiryAt,
                    isset($response->fromAddress) ? $response->fromAddress : null,
                    isset($response->toAddress) ? $response->toAddress : null,
                    isset($response->transactionHash) ? $response->transactionHash : null,
                    self::append_message(null, sprintf('Payment created. Payment ID: %s', $payment_id))
                )
            );
        }
        return self::get_payment_by_id($payment_id);
    }

    /**
     * Confirm payment via API and update payment details
     *
     * @param  object $payment
     * @param  int $order_id
     * @return object
     */
    private static function confirm_payment($payment_id, $order_id)
    {
        try {
            $response = request_confirm_payment($payment_id, $order_id);
            $payment_approved = isset($response->status) && $response->status === Kinesis_Pay_Gateway::STATUS_PROCESSED;
            if (!$payment_approved) {
                error_log('Incorrect payment status after approving payment.');
                throw new Exception(__('Incorrect payment status.', 'kinesis-pay-gateway'));
            }
            $message = sprintf('Payment confirmed%s.', is_admin() ? ' (by admin)' : (wp_doing_cron() ? ' (by cron)' : ''));
            return self::insert_or_update_payment($payment_id, $order_id, $response, $message);
        } catch (Exception $e) {
            error_log('Failed to confirm payment. Exception: ' . json_encode($e));
            throw new Exception(__('Failed to confirm payment. ', 'kinesis-pay-gateway') . $e->getMessage());
        }
    }

    /**
     * Process WC order
     *
     * @param  WC_Order $order
     * @param  string $payment_id
     * @return void
     */
    private static function process_order($order, $payment_id)
    {
        wc_reduce_stock_levels($order->get_id());
        $order->payment_complete(sanitize_text_field($payment_id));
        if (empty($order->get_payment_method_title())) {
            $options = get_option('woocommerce_kinesis_pay_gateway_settings');
            $order->set_payment_method_title($options['title']);
        }
        $order->update_meta_data('kpay_order_paid', 'yes');
        $order->add_order_note(apply_filters('kpay_complete_payment_note', sprintf(__('Payment (ID: %s) has been completed.', 'kinesis-pay-gateway'), $payment_id), $order));
        $order->save();
        if (!is_admin() && !wp_doing_cron()) {
            WC()->cart->empty_cart();
        }
    }

    /**
     * Sync payment details via API, and cancel or process the order
     *
     * @param  WC_Order $order
     * @return boolean
     */
    public static function sync_payment($order)
    {
        $order_id = $order->get_id();
        $payment = self::get_payment_by_id(null, $order_id);
        if (!$payment) {
            $order->update_status('cancelled', __('Order cancelled due to payment creation failure.', 'kinesis-pay-gateway'));
            return true;
        }
        $payment_id = $payment->payment_id;
        if (empty($payment_id)) {
            $order->update_status('cancelled', __('Order cancelled due to payment creation failure.', 'kinesis-pay-gateway'));
            return true;
        }
        try {
            $response = request_payment_status($payment_id);
            // Update WC order status
            if (isset($response->status) && $order->has_status('pending')) {
                if (strtolower($response->status) === 'expired') {
                    $message = sprintf('Order cancelled due to expired KPay payment%s.', is_admin() ? ' (by admin)' : (wp_doing_cron() ? ' (by cron)' : ''));
                    self::insert_or_update_payment($payment_id, $order_id, $response, $message);

                    // Cancel the order
                    $order->update_status('cancelled', __('Order cancelled due to expired KPay payment.', 'kinesis-pay-gateway'));
                } else if (strtolower($response->status) === 'processed') {
                    // Process the order
                    try {
                        // Confirm payment and process order
                        self::confirm_payment($payment_id, $order_id);
                        self::process_order($order, $payment_id);
                    } catch (Exception $e) {
                        error_log(sprintf('Failed to approve payment%s. Exception: %s', is_admin() ? ' (by admin)' : (wp_doing_cron() ? ' (by cron)' : ''), json_encode($e)));
                        throw new Exception(__('Failed to approve payment. ', 'kinesis-pay-gateway') . $e->getMessage());
                    }
                }
            }
        } catch (Exception $e) {
            error_log(sprintf('Failed to sync payment%s. Exception: %s', is_admin() ? ' (by admin)' : (wp_doing_cron() ? ' (by cron)' : ''), json_encode($e)));
            $error_message = sprintf('Failed to sync payment%s. %s', is_admin() ? ' (by admin)' : (wp_doing_cron() ? ' (by cron)' : ''), $e->getMessage());
            self::insert_or_update_payment($payment_id, $order_id, null, null, $error_message);
            return false;
        }
        return true;
    }

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->test_mode = Kinesis_Pay_WooCommerce::get()->get_test_mode();
        $this->id = self::PAYMENT_METHOD_ID; // payment gateway plugin ID
        $is_currency_valid = in_array(get_woocommerce_currency(), apply_filters('kpay_supported_currencies', self::SUPPORTED_CURRENCIES));

        // Init with all the options fields, and load settings
        $this->init_form_fields();
        $this->init_settings();

        // Required action hook for saving the settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Hide Kpay gateway option on checkout
        add_filter('woocommerce_available_payment_gateways', array($this, 'hide_gateway'), 50);

        // Verify payment, capture fund, and process pending order
        add_action('woocommerce_api_kpay-payment', array($this, 'capture_fund'));

        // Register payment script and generate payment HTML
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

        // Load scripts and styles on checkout payment page
        add_action('woocommerce_receipt_' . $this->id, array($this, 'load_scripts_and_style'), 4, 1);

        // Append KPay payment ID to payment method on account order details page and order received page
        add_filter('woocommerce_order_get_payment_method_title', array($this, 'append_payment_id_to_title'), 50, 2);

        // Sync payment and process order before loading account orders
        add_action('woocommerce_before_account_orders', array($this, 'before_load_account_orders'), 25);

        // Validate currency
        if (!$is_currency_valid) {
            $this->enabled = 'no';
            $this->method_description .= sprintf('<br><span style="font-weight: 600;color: #E33A3D;">%s</span>', __('*Currency is not supported. The payment gateway has been disabled.', 'kinesis-pay-gateway'));
        }
    }

    /**
     * Sync payment and cancel/process order before loading account orders
     * @hook woocommerce_before_account_orders
     *
     * @return void
     */
    public function before_load_account_orders()
    {
        $unpaid_orders = wc_get_orders(
            array(
                'customer' => get_current_user_id(),
                'type' => 'shop_order',
                'post_status' => array('wc-pending'),
                'payment_method' => array('kinesis_pay_gateway', 'kinesis-pay'),
                'orderby' => 'id',
                'order'   => 'DESC',
            )
        );
        foreach ($unpaid_orders as $unpaid_order) {
            $order = wc_get_order($unpaid_order);
            $payment_method = $order->get_payment_method();
            if (apply_filters('kpay_check_unpaid_order_payment', ($order->is_created_via('checkout') || $order->is_created_via('store-api')) && self::is_payment_method_kinesis_pay($payment_method), $order)) {
                self::sync_payment($order);
            }
        }
    }

    /**
     * Initialise Gateway Settings Form Fields.
     *
     * @return void
     */
    public function init_form_fields()
    {
        $prod_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'kinesis-pay-gateway'),
                'label' => __('Enable Kinesis Pay Gateway', 'kinesis-pay-gateway'),
                'type' => 'checkbox',
                'default' => 'no',
                'desc_tip' => false,
            ),
            'title' => array(
                'title' => __('Title', 'kinesis-pay-gateway'),
                'type' => 'text',
                'description' => __('Payment gateway title displayed on checkout page.', 'kinesis-pay-gateway'),
                'default' => __('Kinesis Pay', 'kinesis-pay-gateway'),
                'desc_tip' => false,
            ),
            'replace_title_with_icon' => array(
                'title' => __('Replace title with icon', 'kinesis-pay-gateway'),
                'label' => 'Yes',
                'type' => 'checkbox',
                'description' => __('Show Kinesis Pay icon instead of title on checkout page.', 'kinesis-pay-gateway'),
                'default' => 'no',
                'desc_tip' => false,
            ),
            'description' => array(
                'title' => __('Description', 'kinesis-pay-gateway'),
                'type' => 'textarea',
                'description' => __('Payment method description on checkout page.', 'kinesis-pay-gateway'),
                'default' => __('Pay with Gold or Silver using Kinesis Pay.', 'kinesis-pay-gateway'),
                'desc_tip' => false,
            ),
            'hide_description' => array(
                'title' => __('Hide description', 'kinesis-pay-gateway'),
                'label' => 'Yes',
                'type' => 'checkbox',
                'description' => __('Hide payment method description when being selected on checkout page.', 'kinesis-pay-gateway'),
                'default' => 'no',
                'desc_tip' => false,
            ),
            'merchant_id' => array(
                'title' => __('Merchant ID', 'kinesis-pay-gateway'),
                'type' => 'text',
                'description' => $this->test_mode ? __('Test mode is now enabled. This value will be ignored.', 'kinesis-pay-gateway') : __('<strong>Merchant ID</strong> generated by Kinesis. This can be found on KMS ', 'kinesis-pay-gateway') . '<a href="' . Kinesis_Pay_WooCommerce::get()->get_kms_base_url() . '/merchant/dashboard" target="_blank">' . __('Merchant', 'kinesis-pay-gateway') . '</a>' . __(' page.', 'kinesis-pay-gateway'),
                'desc_tip' => false,
                'custom_attributes' => $this->test_mode ? array('readonly' => 'readonly') : array(),
            ),
            'publishable_key' => array(
                'title' => __('Live Publishable Key', 'kinesis-pay-gateway'),
                'type' => 'text',
                'description' => $this->test_mode ? __('Test mode is now enabled. This value will be ignored.', 'kinesis-pay-gateway') : __('<strong>Access Token</strong> generated by Kinesis. This can be found on KMS ', 'kinesis-pay-gateway') . '<a href="' . Kinesis_Pay_WooCommerce::get()->get_kms_base_url() . '/settings/account/merchant-api-keys" target="_blank">' . __('Merchant API Keys', 'kinesis-pay-gateway') . '</a>' . __(' page.', 'kinesis-pay-gateway'),
                'desc_tip' => false,
                'custom_attributes' => $this->test_mode ? array('readonly' => 'readonly') : array(),
            ),
            'private_key' => array(
                'title' => __('Live Private Key', 'kinesis-pay-gateway'),
                'type' => 'password',
                'description' => $this->test_mode ? __('Test mode is now enabled. This value will be ignored.', 'kinesis-pay-gateway') : __('<strong>Secret Token</strong> generated by Kinesis. This can be found on KMS ', 'kinesis-pay-gateway') . '<a href="' . Kinesis_Pay_WooCommerce::get()->get_kms_base_url() . '/settings/account/merchant-api-keys" target="_blank">' . __('Merchant API Keys', 'kinesis-pay-gateway') . '</a>' . __(' page.', 'kinesis-pay-gateway'),
                'desc_tip' => false,
                'custom_attributes' => $this->test_mode ? array('readonly' => 'readonly') : array(),
            ),
            'debug_mode_enabled' => array(
                'title' => __('Show Payment Log', 'kinesis-pay-gateway'),
                'label' => 'Enabled',
                'type' => 'checkbox',
                'description' => __('Shows payment log on WooCommerce order details page for debugging purpose.', 'kinesis-pay-gateway'),
                'default' => 'no',
                'desc_tip' => false,
            ),
        );
        if ($this->test_mode) {
            $test_fields = array(
                'test_merchant_id' => array(
                    'title' => __('TEST Merchant ID', 'kinesis-pay-gateway'),
                    'type' => 'text',
                    'description' => sprintf('<span style="color: #FF003C; font-weight: 600;">%s</span> %s',  __('Test mode', 'kinesis-pay-gateway'), __('is now enabled. TEST Merchant ID is being used.', 'kinesis-pay-gateway')),
                    'desc_tip' => false,
                ),
                'test_publishable_key' => array(
                    'title' => __('TEST Publishable Key', 'kinesis-pay-gateway'),
                    'type' => 'text',
                    'description' => sprintf('<span style="color: #FF003C; font-weight: 600;">%s</span> %s',  __('Test mode', 'kinesis-pay-gateway'), __('is now enabled. TEST Access Token is being used.', 'kinesis-pay-gateway')),
                    'desc_tip' => false,
                ),
                'test_private_key' => array(
                    'title' => __('TEST Private Key', 'kinesis-pay-gateway'),
                    'type' => 'password',
                    'description' => sprintf('<span style="color: #FF003C; font-weight: 600;">%s</span> %s',  __('Test mode', 'kinesis-pay-gateway'), __('is now enabled. TEST Secret Token is being used.', 'kinesis-pay-gateway')),
                    'desc_tip' => false,
                ),
            );
            $this->form_fields = array_merge($prod_fields, $test_fields);
        } else {
            $this->form_fields = $prod_fields;
        }
    }

    /**
     * Init settings
     *
     * @return void
     */
    public function init_settings()
    {
        parent::init_settings();

        $this->replace_title_with_icon = $this->get_option('replace_title_with_icon');
        $this->hide_description = $this->get_option('hide_description');
        $this->title = $this->replace_title_with_icon === 'yes' ? (is_admin() ? $this->get_option('title') : '') : $this->get_option('title');
        $this->description = $this->hide_description === 'yes' ? null : $this->get_option('description');
        $this->icon = KINESIS_PAY_DIR_URL . 'assets/images/buy-with-kpay.svg';
        $this->has_fields = false;
        $this->method_title = $this->test_mode ? __('Kinesis Pay - TEST MODE', 'kinesis-pay-gateway') : __('Kinesis Pay', 'kinesis-pay-gateway');
        $this->method_description = __('Pay with Gold or Silver by scanning Kinesis Pay payment QR code. Payments need to be finished in ', 'kinesis-pay-gateway') . '<a href="' . Kinesis_Pay_WooCommerce::get()->get_kms_base_url() . '" target="_blank">KMS</a>.';
        $this->merchant_id = $this->test_mode ? $this->get_option('test_merchant_id') : $this->get_option('merchant_id');
        $this->private_key = $this->test_mode ? $this->get_option('test_private_key') : $this->get_option('private_key');
        $this->publishable_key = $this->test_mode ? $this->get_option('test_publishable_key') : $this->get_option('publishable_key');
        $this->unpaid_order_status = apply_filters('kpay_process_payment_order_status', 'pending');
        $this->capture_ready_payment_status = self::STATUS_PROCESSED;
    }

    /**
     * Get gateway icon
     *
     * @return string
     */
    public function get_icon()
    {
        if ($this->replace_title_with_icon === 'yes') {
            $icon_html = '<img id="kpay-payment-method-logo" src="' . $this->icon . '" alt="' . __('Kinesis Pay', 'kinesis-pay-gateway') . '" width="200px" />';
            return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
        }
    }

    /**
     * Disable/hide Kpay gateway option from checkout if settings are incomplete
     * @hook woocommerce_available_payment_gateways
     * 
     * @param array $gateways
     * @return array
     */
    public function hide_gateway($gateways)
    {
        // Validate all required settings
        if (empty($this->merchant_id) || empty($this->private_key) || empty($this->publishable_key)) {
            unset($gateways[$this->id]);
        }
        return $gateways;
    }

    /**
     * Append payment ID to payment method title for frontend
     * @hook woocommerce_order_get_payment_method_title
     *
     * @param  mixed $value
     * @param  WC_Order $order
     * @return mixed
     */
    public function append_payment_id_to_title($value, $order)
    {
        if (!is_admin() && $value && self::is_payment_method_kinesis_pay($order->get_payment_method())) {
            $payment = self::get_payment_by_id(null, $order->get_id());
            return $value . sprintf(' (ID: %s)', $payment->payment_id);
        }
        return $value;
    }

    /**
     * Generate nonce
     *
     * @param  int $order_id
     * @param  string $order_key
     * @param  string $payment_id
     * @return string
     */
    private function get_nonce_action($order_id, $order_key, $payment_id)
    {
        return sprintf('kpay_%s_%s_%s', $order_id, $order_key, $payment_id);
    }

    /**
     * Register payment scripts
     * @hook wp_enqueue_scripts
     *
     * @return string
     */
    public function payment_scripts()
    {
        if ($this->enabled !== 'yes') {
            return;
        }

        if (!$this->test_mode && !is_ssl()) {
            return;
        }

        $order_id = get_query_var('order-pay');
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!is_a($order, 'WC_Order')) {
            return;
        }

        wp_register_style('kpay-style', KINESIS_PAY_DIR_URL . 'assets/css/style.min.css');
        wp_register_script('kpay-qrcode-lib', KINESIS_PAY_DIR_URL . 'assets/js/lib/qrcode.min.js');
        wp_register_script('kpay-payment-script', KINESIS_PAY_DIR_URL . 'assets/js/frontend/kpay-payment.min.js');

        $payment_id = $order->get_meta('kpay_payment_id', true);
        $js_kms_base_url = Kinesis_Pay_WooCommerce::get()->get_kms_base_url();
        $assets_url = KINESIS_PAY_DIR_URL . 'assets/images/';
        $kpay_redirect_url = $js_kms_base_url . '?paymentId=' . $payment_id;

        ob_start();
?>
        <div id="kinesis-pay-modal__content">
            <div class="kinesis-pay-modal__logo-wrapper">
                <img class="kinesis-pay-modal__kpay-logo" src="<?php echo $assets_url; ?>Kinesis-Pay-logo.svg">
            </div>
            <div id="kinesis-pay-modal__kpay-qrcode"></div>
            <div class="kinesis-pay-modal__instructions-wrapper">
                <span class="kinesis-pay-modal__instructions"><?php echo __('Scan with the Kinesis mobile app or', 'kinesis-pay-gateway') ?></span>
                <a id="kinesis-pay-modal__payment-link" href="<?php echo $kpay_redirect_url ?>" target="_blank"><?php echo __('Continue in browser', 'kinesis-pay-gateway') ?></a>
            </div>
            <div class="kinesis-pay-modal__payment-id-copy-wrapper">
                <span><?php echo __('Payment ID', 'kinesis-pay-gateway') ?></span>
                <div class="kinesis-pay-modal__payment-info">
                    <input id="kinesis-pay-modal__payment-id-text" type="text" value="<?php echo $payment_id; ?>" readonly>
                    <button id="kinesis-pay-modal__copy-button" class="kinesis-pay-modal__copy-button kinesis-pay-modal__button" onclick="copyPaymentId(event)"><?php echo __('Copy', 'kinesis-pay-gateway'); ?></button>
                </div>
                <span class="kinesis-pay-modal__instructions"><?php echo __('Please keep this window open. It will close automatically once your payment has been processed.', 'kinesis-pay-gateway') ?></span>
                <span class="kinesis-pay-modal__status-countdown"><?php echo __('Checking payment in: ', 'kinesis-pay-gateway') ?><span id="kinesis-pay-modal__check-status-countdown">--s</span></span>
            </div>
            <button id="kinesis-pay-modal__cancel-payment-button" class="kinesis-pay-modal__cart-button kinesis-pay-modal__button"><?php echo __('Cancel', 'kinesis-pay-gateway'); ?></button>
        </div>
        <?php
        $payment_modal_html = ob_get_clean();

        ob_start();
        ?>
        <div class="kinesis-pay-modal__timeout-content">
            <img class="kinesis-pay-modal__kpay-logo" src="<?php echo $assets_url; ?>Kinesis-Pay-logo.svg">
            <span class="kinesis-pay-modal__timeout-message"><?php echo __('Payment has been timed out. Please try again.', 'kinesis-pay-gateway'); ?></span>
        </div>
        <button id="timeout-go-back-to-cart" class="kinesis-pay-modal__cart-button kinesis-pay-modal__button" onclick="this.disabled = true; window.location='<?php echo wc_get_cart_url(); ?>'"><?php echo __('Go to cart', 'kinesis-pay-gateway'); ?></button>
        <?php
        $timeout_html = ob_get_clean();

        ob_start();
        ?>
        <input type="hidden" name="order-id" value="<?php echo $order->get_id(); ?>">
        <input type="hidden" name="order-key" value="<?php echo $order->get_order_key(); ?>">
        <input type="hidden" name="kpay-payment-id" value="<?php echo $payment_id; ?>">
        <input type="hidden" name="kpay-nonce" value="<?php echo wp_create_nonce($this->get_nonce_action($order->get_id(), $order->get_order_key(), $payment_id)); ?>">
        <?php
        $payment_form_content = ob_get_clean();

        wp_localize_script(
            'kpay-payment-script',
            'kpay_data',
            array(
                'kpay_payment_id' => $payment_id,
                'get_payment_status_action' => 'woocommerce_get_payment_status',
                'kpay_redirect_url' => $kpay_redirect_url,
                'timeout_redirect_url' => wc_get_cart_url(),
                'rejected_redirect_url' => wc_get_checkout_url(),
                'error_redirect_url' => wc_get_checkout_url(),
                'callback_url' => add_query_arg(array('wc-api' => 'kpay-payment'), trailingslashit(get_home_url())),
                'cancel_url' => apply_filters('kpay_payment_cancel_payment', wc_get_checkout_url(), $this->get_return_url($order), $order),
                'kpay_payment_status' => array(
                    'processed' => self::STATUS_PROCESSED,
                    'rejected' => self::STATUS_REJECTED,
                    'expired' => self::STATUS_EXPIRED,
                ),
                'messages' => array(
                    'general_error' => __('Something went wrong. Please try again.', 'kinesis-pay-gateway'),
                    'timeout_error' => __('Payment has been timed out. Please try again.', 'kinesis-pay-gateway'),
                    'rejected_error' => __('Payment has been rejected. Please try again.', 'kinesis-pay-gateway'),
                    'exception_error' => __('Something went wrong.', 'kinesis-pay-gateway'),
                    'copy_error' => __('Failed to copy payment ID', 'kinesis-pay-gateway'),
                    'copied' => __('Copied', 'kinesis-pay-gateway'),
                ),
                'payment_modal_html' => $payment_modal_html,
                'timeout_html' => $timeout_html,
                'timeout_peirod' => 600000,
                'payment_form_content' => $payment_form_content,
            )
        );
    }

    /**
     * Render payment fields
     *
     * @return string
     */
    public function payment_fields()
    {
        if ($this->description) {
            if ($this->test_mode) {
                $this->description .= ' *** TEST MODE ENABLED ***';
                $this->description = trim($this->description);
            }
            echo wpautop(wp_kses_post($this->description));
        }
    }

    /**
     * Create payment via API, and save it against the order id
     *
     * @param  int $order_id
     * @return object
     */
    private function create_payment($order_id)
    {
        try {
            $response = request_kpay_payment_id();
            if (!isset($response->globalPaymentId)) {
                error_log('Failed to create payment ID.');
                throw new Exception(__('Failed to create payment ID.', 'kinesis-pay-gateway'));
            }
            $payment_id = $response->globalPaymentId;
            return self::insert_or_update_payment($payment_id, $order_id, $response, 'New payment created. Payment ID: ' . $payment_id);
        } catch (Exception $e) {
            error_log('Something wrong with creating payment ID. Exception: ' . json_encode($e));
            throw new Exception(__('Something wrong with creating payment ID. ', 'kinesis-pay-gateway') . $e->getMessage());
        }
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        // Check order's kpay payment_id. If there's one, then use it. Otherwise, create one.
        $order = wc_get_order($order_id);
        $payment = self::get_payment_by_id(null, $order_id);
        if ($payment) {
            $payment_id = $payment->payment_id;

            try {
                $response = request_payment_status($payment_id);
            } catch (Exception $e) {
                error_log('Something wrong with requesting payment status. Exception: ' . json_encode($e));
                $message = __('Something wrong with requesting payment status. ', 'kinesis-pay-gateway') . $e->getMessage();
                throw new Exception($message);
            }
            if ($response->status === $this->capture_ready_payment_status) {
                // Confirm payment and process order
                self::confirm_payment($payment_id, $order_id);
                self::process_order($order, $payment_id);

                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order),
                );
            } else if ($response->status === self::STATUS_CREATED || $response->status === self::STATUS_ACCEPTED) {
                // Return redirect
                return array(
                    'result'   => 'success',
                    'redirect' => apply_filters('kpay_process_payment_redirect', $order->get_checkout_payment_url(true), $order),
                );
            } else {
                // Other payment status, e.g. expired
                self::insert_or_update_payment($payment_id, $order_id, $response, sprintf('Payment (ID: %s) abandoned.', $payment_id));
            }
        }
        // Create a new payment
        $payment = $this->create_payment($order_id);
        if (!$payment) {
            error_log('Failed to create payment in database.');
            $message = __('Something wrong with creating payment.', 'kinesis-pay-gateway');
            throw new Exception($message);
        }
        $payment_id = $payment->payment_id;
        // Mark order as pending / unpaid
        if (empty($order->get_payment_method_title())) {
            $order->set_payment_method_title($this->get_option('title'));
        }
        $order->update_status($this->unpaid_order_status);
        $order->update_meta_data('kpay_order_paid', 'no');
        $order->update_meta_data('kpay_payment_id', $payment->payment_id);
        $order->add_order_note(apply_filters('kpay_awaiting_payment_note', sprintf(__('Awaiting payment (ID: %s) to be processed.', 'kinesis-pay-gateway'), $payment_id), $order));
        $order->save();
        if (apply_filters('kpay_payment_empty_cart', false)) {
            WC()->cart->empty_cart();
        }
        do_action('kpay_after_payment_init', $order_id, $order);
        return array(
            'result'   => 'success',
            'redirect' => apply_filters('kpay_process_payment_redirect', $order->get_checkout_payment_url(true), $order),
        );
    }

    /**
     * Payment has been processed in Kinesis. Update WooCommerce order status and Kpay payment record
     * @hook woocommerce_api_kpay-payment
     * 
     * @return void
     */
    public function capture_fund()
    {
        if (('POST' !== $_SERVER['REQUEST_METHOD']) || !isset($_GET['wc-api']) || ('kpay-payment' !== $_GET['wc-api'])) {
            return;
        }

        // Verify form data from frontend
        if (
            empty($_POST['order-id']) || empty($_POST['order-key']) || empty($_POST['kpay-payment-id']) || empty($_POST['kpay-nonce'])
            || !wp_verify_nonce($_POST['kpay-nonce'], $this->get_nonce_action($_POST['order-id'], $_POST['order-key'], $_POST['kpay-payment-id']))
        ) {
            error_log('Payment data verification failed.');
            $message = __('Payment data verification failed. Please try again.', 'kinesis-pay-gateway');
            wp_die($message, get_bloginfo('name'), array('link_url' => '/cart', 'link_text' => __('Go to cart', 'kinesis-pay-gateway')));
            exit;
        }

        $order_id = absint($_POST['order-id']);
        $order = wc_get_order($order_id);
        if (!is_a($order, 'WC_Order')) {
            $order_id = wc_get_order_id_by_order_key(sanitize_text_field($_POST['order-key']));
            $order = wc_get_order($order_id);
        }

        if (is_a($order, 'WC_Order')) {
            // If order has already been processed, then go to success URL directly without doing anything else
            if (!$order->has_status('pending')) {
                wp_safe_redirect(apply_filters('kpay_payment_redirect_url', $this->get_return_url($order), $order));
                exit;
            }

            // Check Kpay payment status, and update kpay payment record
            $payment_id = $_POST['kpay-payment-id'];
            try {
                $response = request_payment_status($payment_id);
            } catch (Exception $e) {
                error_log('Something wrong with requesting payment status. Exception: ' . json_encode($e));
                $message = __('Something wrong with requesting payment status. ', 'kinesis-pay-gateway') . $e->getMessage();
                wp_die($message, get_bloginfo('name'), array('link_url' => '/cart', 'link_text' => __('Go to cart', 'kinesis-pay-gateway')));
                exit;
            }
            $payment_status_ok = isset($response->status) && $response->status === $this->capture_ready_payment_status;
            if (!$payment_status_ok) {
                error_log('Incorrect payment status when capturing fund.');
                $message = __('Incorrect payment status when capturing fund.', 'kinesis-pay-gateway');
                wp_die($message, get_bloginfo('name'), array('link_url' => '/cart', 'link_text' => __('Go to cart', 'kinesis-pay-gateway')));
                exit;
            }

            // Confirm payment and process order
            try {
                self::confirm_payment($payment_id, $order_id);
            } catch (Exception $e) {
                error_log('Failed to confirm payment. ' . $e->getMessage());
                $message = __('Something went wrong when confirming payment.', 'kinesis-pay-gateway');
                wp_die($message, get_bloginfo('name'), array('link_url' => '/cart', 'link_text' => __('Go to cart', 'kinesis-pay-gateway')));
            }
            self::process_order($order, $payment_id);

            do_action('kpay_after_payment_processed', $order->get_id(), $order);
            wp_safe_redirect(apply_filters('kpay_payment_redirect_url', $this->get_return_url($order), $order));
            exit;
        } else {
            $message = __('Invalid order ID. Please contact with support team.', 'kinesis-pay-gateway');
            wp_die($message, get_bloginfo('name'), array('link_url' => '/cart', 'link_text' => __('Go to cart', 'kinesis-pay-gateway')));
            exit;
        }
    }

    /**
     * Load scripts and styles on checkout payment page
     *
     * @param int $order_id
     * @return string
     */
    public function load_scripts_and_style($order_id)
    {
        $order = wc_get_order($order_id);
        wp_enqueue_style('kpay-style');
        wp_enqueue_script('kpay-qrcode-lib');
        wp_enqueue_script('kpay-payment-script');

        if ($this->enabled === 'yes' && $order->needs_payment() && $order->has_status($this->unpaid_order_status)) {
        ?>
            <section class="kpay-section">
                <span class="kpay-waiting-text kpay-span" style="display: none;"><?php esc_html_e('Processing your order. Please wait and don\'t close or refresh the page.', 'kinesis-pay-gateway'); ?></span>
                <form id="kpay-payment-confirm-hidden-form" class="kpay-payment-confirm-form" style="display: none;"></form>
            </section>
<?php
        }
    }
}

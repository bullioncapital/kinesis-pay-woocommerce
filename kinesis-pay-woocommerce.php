<?php

/**
 * Plugin Name: Kinesis Pay Gateway
 * Plugin URI: https://github.com/bullioncapital/kinesis-pay-woocommerce
 * Author: Kinesis Money
 * Author URI: https://kinesis.money/
 * Description: Pay with Kinesis Money
 * Version: 2.2.1
 */

// Prevent public user to directly access .php files through URL
defined('ABSPATH') || exit;

final class Kinesis_Pay_WooCommerce
{
    /**
     * Kinesis Pay plugin version
     * 
     * @var string
     */
    private $version = '2.2.1';

    /**
     * Min required WordPress version
     * 
     * @var string
     */
    private $min_wp_version = '4.6';

    /**
     * Min required PHP version
     * 
     * @var string
     */
    private $min_php_version = '5.6';

    /**
     * Test mode updated manually. Value MUST be false for production
     * 
     * @var boolean
     */
    private $test_mode = false;

    /**
     * Kinesis Pay API base url
     *
     * @var string
     */
    private $api_base_url = 'https://apip.kinesis.money';

    /**
     * KMS base url
     *
     * @var string
     */
    private $kms_base_url = 'https://kms.kinesis.money';

    /**
     * Test Kinesis Pay API base url
     *
     * @var string
     */
    private $test_api_base_url = 'https://qa4-api.kinesis.money';

    /**
     * Test KMS base url
     *
     * @var string
     */
    private $test_kms_base_url = 'https://qa4-kms.kinesis.money';

    /**
     * Kinesis exchange rates url
     *
     * @var string
     */
    private $exchange_rates_url = 'https://api.kinesis.money/api/v1/exchange/coin-market-cap/orderbook/';

    /**
     * Single instance
     * 
     * @var Kinesis_Pay_WooCommerce
     */
    protected static $instance = null;

    /**
     * Admin notices
     *
     * @var array
     */
    private $notices = array();

    /**
     * Get singleton instance
     *
     * @return Kinesis_Pay_WooCommerce
     */
    public static function get()
    {
        if (is_null(self::$instance) && !(self::$instance instanceof Kinesis_Pay_WooCommerce)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get test mode
     *
     * @return string
     */
    public function get_test_mode()
    {
        return $this->test_mode;
    }

    /**
     * Get Kinesis Pay API base url
     *
     * @return string
     */
    public function get_api_base_url()
    {
        return $this->test_mode ? $this->test_api_base_url : $this->api_base_url;
    }

    /**
     * Get Kinesis Pay KMS base url
     *
     * @return string
     */
    public function get_kms_base_url()
    {
        return $this->test_mode ? $this->test_kms_base_url : $this->kms_base_url;
    }

    /**
     * Get Kinesis exchange rates url
     *
     * @return string
     */
    public function get_exchange_rates_url()
    {
        return $this->exchange_rates_url;
    }

    /**
     * Private constructor
     *
     * @return void
     */
    private function __construct()
    {
        if (!defined('KINESIS_PAY_VERSION')) {
            define('KINESIS_PAY_VERSION', $this->version);
        }
        if (!defined('KINESIS_PAY_FILE')) {
            define('KINESIS_PAY_FILE', __FILE__);
        }
        if (!defined('KINESIS_PAY_BASENAME')) {
            define('KINESIS_PAY_BASENAME', plugin_basename(KINESIS_PAY_FILE));
        }
        if (!defined('KINESIS_PAY_DIR_URL')) {
            define('KINESIS_PAY_DIR_URL', plugin_dir_url(KINESIS_PAY_FILE));
        }
        if (!defined('KINESIS_PAY_DIR_PATH')) {
            define('KINESIS_PAY_DIR_PATH', plugin_dir_path(KINESIS_PAY_FILE));
        }
        if (!$this->check_dependencies()) {
            return;
        }

        register_activation_hook(
            KINESIS_PAY_FILE,
            function () {
                set_transient('kinesis-pay-activated', true, 60);
            }
        );
        register_deactivation_hook(KINESIS_PAY_FILE, 'kpay_plugin_deactivation');
        $this->init_plugin();
        do_action('kinesis_pay_loaded');
    }

    /**
     * Check plugin dependencies
     *
     * @return boolean
     */
    private function check_dependencies()
    {
        // Check PHP version
        if (version_compare(phpversion(), $this->min_php_version, '<')) {
            $this->notices[] = sprintf(esc_html__('Kinesis Pay requires PHP version of %s or higher. Please upgrade PHP.', 'kinesis-pay-gateway'), $this->min_php_version);
        }

        // Check Wordpress version
        if (version_compare(get_bloginfo('version'), $this->min_wp_version, '<')) {
            $this->notices[] = sprintf(esc_html__('Kinesis Pay requires Wordpress version of %s or higher. Please upgrade Wordpress.', 'kinesis-pay-gateway'), $this->min_wp_version);
        }

        if (empty($this->notices)) {
            return true;
        }

        // If not meeting requirements, then deactivate the plugin and show error messages
        add_action('admin_init', array($this, 'self_deactivate'));
        add_action('admin_notices', array($this, 'show_activation_errors'));
        return false;
    }

    /**
     * Init actions and filters
     *
     * @return void
     */
    private function init_plugin()
    {
        include_once(KINESIS_PAY_DIR_PATH . 'includes/wp-cron.php');

        add_action('plugins_loaded', array($this, 'load_plugin_files'), 1);
        add_action('plugins_loaded', array($this, 'load_localization'), 5);
        add_action('before_woocommerce_init', array($this, 'declare_compatibility'));
        add_action('woocommerce_blocks_loaded', array($this, 'wc_block_support'));
        add_filter('woocommerce_payment_gateways', array($this, 'register_gateway'));
        add_action('admin_notices', array($this, 'show_success_notice'));
    }

    /**
     * Load localization setup
     *
     * @return void
     */
    public function load_localization()
    {
        load_plugin_textdomain('kinesis-pay-gateway', false, dirname(KINESIS_PAY_BASENAME) . '/languages');
    }

    /**
     * Deactivate plugin
     *
     * @return void
     */
    public function self_deactivate()
    {
        deactivate_plugins(KINESIS_PAY_BASENAME);
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }

    /**
     * Declare plugin compatibility
     *
     * @return void
     */
    public function declare_compatibility()
    {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', KINESIS_PAY_FILE, true);
        }
    }

    /**
     * Register Kinesis Pay Gateway
     *
     * @param  array $gateways
     * @return array
     */
    public function register_gateway($gateways)
    {
        $gateways[] = 'Kinesis_Pay_Gateway'; // Payment gateway class name
        return $gateways;
    }

    /**
     * Load related files
     *
     * @return void
     */
    public function load_plugin_files()
    {
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }
        require_once KINESIS_PAY_DIR_PATH . 'includes/install.php';
        require_once KINESIS_PAY_DIR_PATH . 'includes/api.php';
        require_once KINESIS_PAY_DIR_PATH . 'includes/ajax.php';
        require_once KINESIS_PAY_DIR_PATH . 'includes/class-kinesis-pay-gateway.php';
        require_once KINESIS_PAY_DIR_PATH . 'includes/admin/admin-functions.php';
        kinesis_pay_gateway_update_db_check();
    }

    /**
     * WooCommerce block support
     *
     * @return void
     */
    public function wc_block_support()
    {
        if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            return;
        }
        require_once KINESIS_PAY_DIR_PATH . 'includes/class-block.php';
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function (\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                $payment_method_registry->register(new Kinesis_Pay_Gateway_Blocks());
            }
        );
    }

    /**
     * Show admin notice for activation errors
     *
     * @return string
     */
    public function show_activation_errors()
    {
?>
        <div class="notice notice-error">
            <p>
                <?php echo join('<br>', $this->notices); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Show admin notice for success message after the plugin is installed and activated
     *
     * @return string
     */
    public function show_success_notice()
    {
        if (get_transient('kinesis-pay-activated')) {
            delete_transient('kinesis-pay-activated');

            if (current_user_can('manage_options')) {
        ?>
                <div class="notice notice-success">
                    <p>
                        <?php
                        printf(
                            __(
                                'Successfully installed %1$s v%2$s plugin. Please <a href="%3$s">finish configuration</a>.',
                                'kinesis-pay-gateway'
                            ),
                            'Kinesis Pay Gateway',
                            KINESIS_PAY_VERSION,
                            admin_url('admin.php?page=wc-settings&tab=checkout&section=kinesis_pay_gateway')
                        );
                        ?>
                    </p>
                </div>
            <?php
            } else {
            ?>
                <div class="notice notice-success">
                    <p>
                        <?php
                        printf(
                            __(
                                'Successfully installed %1$s v%2$s plugin.',
                                'kinesis-pay-gateway'
                            ),
                            'Kinesis Pay Gateway',
                            KINESIS_PAY_VERSION
                        );
                        ?>
                    </p>
                </div>
<?php
            }
        }
    }
}

/**
 * Actions to perform when the plugin is deactivated
 */
function kpay_plugin_deactivation()
{
    // Clear any scheduled hooks
    wp_clear_scheduled_hook('kpay_sync_statuses');
}

Kinesis_Pay_WooCommerce::get();

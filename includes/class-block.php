<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Kinesis Pay Gateway blocks integration
 *
 * @since 2.0.0
 */
final class Kinesis_Pay_Gateway_Blocks extends AbstractPaymentMethodType
{
    /**
     * Payment gateway name
     *
     * @var string
     */
    protected $name = 'kinesis_pay_gateway';

    /**
     * Block initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->settings = get_option('woocommerce_kinesis_pay_gateway_settings', []);
    }

    /**
     * Return activity status
     *
     * @return boolean
     */
    public function is_active()
    {
        return !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
    }

    /**
     * Get payment method script handles
     *
     * @return string[]
     */
    public function get_payment_method_script_handles()
    {
        $file = KINESIS_PAY_DIR_URL . 'assets/js/frontend/block.min.js';
        wp_register_script(
            'kinesis_pay_gateway-blocks-integration',
            $file,
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('kinesis_pay_gateway-blocks-integration');
        }
        $data = [
            'icon' => isset($this->settings['replace_title_with_icon']) && $this->settings['replace_title_with_icon'] === 'yes' ? KINESIS_PAY_DIR_URL . 'assets/images/buy-with-kpay.svg' : null,
            'description' => isset($this->settings['hide_description']) && $this->settings['hide_description'] === 'yes' ? null : (isset($this->settings['description']) && $this->settings['description'] ? $this->settings['description'] : ''),
        ];
        wp_localize_script('kinesis_pay_gateway-blocks-integration', 'kpay_checkout_data', $data);
        return ['kinesis_pay_gateway-blocks-integration'];
    }

    /**
     * Get payment method data
     *
     * @return array
     */
    public function get_payment_method_data()
    {
        return array(
            'title' => $this->get_setting('title'),
            'description' => Kinesis_Pay_WooCommerce::get()->get_test_mode() ? sprintf('%s %s', $this->get_setting('description'), __('*** TEST MODE ENABLED ***', 'kinesis-pay-gateway')) : $this->get_setting('description'),
        );
    }

    /**
     * Get supported features
     *
     * @return array
     */
    public function get_supported_features()
    {
        $gateways = WC()->payment_gateways->get_available_payment_gateways();
        if (isset($gateways[$this->name])) {
            $gateway = $gateways[$this->name];

            return array_filter($gateway->supports, array($gateway, 'supports'));
        }
        return array();
    }
}

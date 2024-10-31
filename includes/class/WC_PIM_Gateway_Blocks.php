<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
/**
 * PIM payment Blocks integration
 *
 * @since 1.2.0
 */
class WC_PIM_Gateway_Blocks extends AbstractPaymentMethodType
{

    protected $name = 'wc_pim_gateway';
    private $gateway;

    public function get_name()
    {
        return $this->name;
    }

    public function initialize()
    {
        $this->settings = get_option( 'woocommerce_pim_settings', [] );
        $gateways       = WC()->payment_gateways->payment_gateways();
        $this->gateway  = $gateways[ $this->name ];
    }

    public function get_payment_method_script_handles()
    {


        $script_path       = '/assets/js/frontend/blocks.js';
        $script_asset_path = Payitmonthly_Woocommerce_Payment_Gateway::plugin_abspath() . 'assets/js/frontend/blocks.asset.php';
        $script_asset      = file_exists( $script_asset_path )
            ? require( $script_asset_path )
            : array(
                'dependencies' => array(),
                'version'      => '1.2.0'
            );
        $script_url        = Payitmonthly_Woocommerce_Payment_Gateway::plugin_url() . $script_path;

        wp_register_script(
            'wc-pim-blocks-integration',
            $script_url,
            $script_asset[ 'dependencies' ],
            $script_asset[ 'version' ],
            true
        );

        return [ 'wc-pim-blocks-integration' ];

    }

    public function get_payment_method_data()
    {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'supports'    => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] )
        ];
    }

    public function is_active()
    {
        // Return true if the payment method is active, false otherwise
        return $this->gateway->is_available();
    }

}

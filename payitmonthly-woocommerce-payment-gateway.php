<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://payitmonthly.uk
 * @since             1.0.0
 * @package           Payitmonthly_Woocommerce_Payment_Gateway
 *
 * @wordpress-plugin
 * Plugin Name:       PayItMonthly For WooCommerce
 * Plugin URI:        https://payitmonthly.uk
 * Description:       PayItMonthly payment gateway for WooCommerce
 * Version:           1.2.0
 * Author:            PayItMonthly
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       payitmonthly-woocommerce-payment-gateway
 * Domain Path:       /languages
 * Requires PHP: 7.2
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Define Plugin version.
 */
define( 'PAYITMONTHLY_WOOCOMMERCE_PAYMENT_GATEWAY_VERSION', '1.2.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-payitmonthly-woocommerce-payment-gateway-activator.php
 */
function activate_payitmonthly_woocommerce_payment_gateway() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-payitmonthly-woocommerce-payment-gateway-activator.php';
    Payitmonthly_Woocommerce_Payment_Gateway_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-payitmonthly-woocommerce-payment-gateway-deactivator.php
 */
function deactivate_payitmonthly_woocommerce_payment_gateway() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-payitmonthly-woocommerce-payment-gateway-deactivator.php';
    Payitmonthly_Woocommerce_Payment_Gateway_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_payitmonthly_woocommerce_payment_gateway' );
register_deactivation_hook( __FILE__, 'deactivate_payitmonthly_woocommerce_payment_gateway' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-payitmonthly-woocommerce-payment-gateway.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_payitmonthly_woocommerce_payment_gateway() {

    $plugin = new Payitmonthly_Woocommerce_Payment_Gateway();
    $plugin->run();

}
run_payitmonthly_woocommerce_payment_gateway();

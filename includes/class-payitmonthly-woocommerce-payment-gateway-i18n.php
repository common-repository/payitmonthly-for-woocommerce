<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       payitmonthly.uk
 * @since      1.0.0
 *
 * @package    Payitmonthly_Woocommerce_Payment_Gateway
 * @subpackage Payitmonthly_Woocommerce_Payment_Gateway/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Payitmonthly_Woocommerce_Payment_Gateway
 * @subpackage Payitmonthly_Woocommerce_Payment_Gateway/includes
 * @author     PayItMonthly <support@payitmonthly.uk>
 */
class Payitmonthly_Woocommerce_Payment_Gateway_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'payitmonthly-woocommerce-payment-gateway',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}

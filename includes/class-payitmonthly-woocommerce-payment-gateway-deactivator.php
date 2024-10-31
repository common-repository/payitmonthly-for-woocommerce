<?php

/**
 * Fired during plugin deactivation
 *
 * @link       payitmonthly.uk
 * @since      1.0.0
 *
 * @package    Payitmonthly_Woocommerce_Payment_Gateway
 * @subpackage Payitmonthly_Woocommerce_Payment_Gateway/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Payitmonthly_Woocommerce_Payment_Gateway
 * @subpackage Payitmonthly_Woocommerce_Payment_Gateway/includes
 * @author     PayItMonthly <support@payitmonthly.uk>
 */
class Payitmonthly_Woocommerce_Payment_Gateway_Deactivator {

	/**
	 * Delete PIM integration oauth token from the options
	 * @since    1.0.0
	 */
	public static function deactivate() {

        delete_option("_pim_integration_oauth_token");
	}

}

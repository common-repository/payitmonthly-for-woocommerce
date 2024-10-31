<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       payitmonthly.uk
 * @since      1.0.0
 *
 * @package    Payitmonthly_Woocommerce_Payment_Gateway
 * @subpackage Payitmonthly_Woocommerce_Payment_Gateway/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Payitmonthly_Woocommerce_Payment_Gateway
 * @subpackage Payitmonthly_Woocommerce_Payment_Gateway/includes
 * @author     PayItMonthly <support@payitmonthly.uk>
 */
class Payitmonthly_Woocommerce_Payment_Gateway
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Payitmonthly_Woocommerce_Payment_Gateway_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('PAYITMONTHLY_WOOCOMMERCE_PAYMENT_GATEWAY_VERSION')) {
            $this->version = PAYITMONTHLY_WOOCOMMERCE_PAYMENT_GATEWAY_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'payitmonthly-woocommerce-payment-gateway';


        $this->load_dependencies();
        $this->set_locale();
        $this->define_public_hooks();

        // ad gateway after plugin loaded
        add_filter('plugins_loaded', array($this, 'init_pim_gateway'));
        add_filter('woocommerce_payment_gateways', array($this, 'add_pim_payment_gateway_class'));
        add_action('woocommerce_before_add_to_cart_button', array($this, 'add_pim_price_before_cart'));
        add_action('woocommerce_proceed_to_checkout', array($this, 'add_pim_price_before_after_totals_after_order_total'));

        add_action('woocommerce_product_options_general_product_data', array($this, 'woocommerce_pim_product_custom_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'woocommerce_pim_product_custom_fields_save'));

        add_action( 'woocommerce_blocks_loaded', array($this, 'woocommerce_gateway_pim_woocommerce_block_support'));

//        add_action('wp_ajax_pim_handle_control_buttons', ["WC_PIM_Gateway","handle_control_buttons"]);


    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Payitmonthly_Woocommerce_Payment_Gateway_Loader. Orchestrates the hooks of the plugin.
     * - Payitmonthly_Woocommerce_Payment_Gateway_i18n. Defines internationalization functionality.
     * - Payitmonthly_Woocommerce_Payment_Gateway_Admin. Defines all hooks for the admin area.
     * - Payitmonthly_Woocommerce_Payment_Gateway_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-payitmonthly-woocommerce-payment-gateway-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-payitmonthly-woocommerce-payment-gateway-i18n.php';


        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-payitmonthly-woocommerce-payment-gateway-public.php';


        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class/WC_PIM_Gateway_request.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class/WC_PIM_Gateway_API.php';

        $this->loader = new Payitmonthly_Woocommerce_Payment_Gateway_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Payitmonthly_Woocommerce_Payment_Gateway_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {

        $plugin_i18n = new Payitmonthly_Woocommerce_Payment_Gateway_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }


    /**
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {

        $plugin_public = new Payitmonthly_Woocommerce_Payment_Gateway_Public($this->get_plugin_name(), $this->get_version());

//        $plugin_public->enqueue_scripts();
//        $plugin_public->enqueue_styles();


            $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
            $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    /**
     * Add filter woocommerce_payment_gateways
     */
    public function init_pim_gateway()
    {

        //if wc not installed
        if (class_exists("WC_Payment_Gateway") == false) {
            return null;
        }

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class/WC_PIM_Gateway.php';
    }


    /**
     * Add WC_PIM_Gateway to the WC payment gateway methods
     * @param $methods
     * @return mixed
     */
    public function add_pim_payment_gateway_class($methods)
    {
        $methods[] = 'WC_PIM_Gateway';
        return $methods;
    }


    /**
     * Display pim installments calculation text before add to cart button
     * @param $price
     * @return mixed
     * @since    1.0.0
     */
    public function add_pim_price_before_cart($price)
    {

        if (is_checkout()) return $price;
        global $post;
        $product_id = $post->ID;
        $product = wc_get_product($product_id);
        $product_price = $product->get_price();
        $payment_gateways = WC()->payment_gateways->payment_gateways();
        if ($payment_gateways and isset($payment_gateways['wc_pim_gateway'])) {
            $pim_payment_gateway = $payment_gateways['wc_pim_gateway'];
            if ($pim_payment_gateway->enabled == "yes" and $pim_payment_gateway->show_on_product_page == "yes") {
                $product_max_installments = $pim_payment_gateway->get_product_max_instalments($product_id);
                if ($product_max_installments) {
                    $deposit = apply_filters("pim_wc_payment_gateway_deposit", 0, null, null,$product_price);
                    $product_price = $product_price - $deposit;
                    $instalment_value = number_format((float)round($product_price / $product_max_installments, 2), 2, '.', '');
                    $deposit_line = "";
                    if ($deposit){
                        $deposit_line= sprintf(" (and £%s deposit)",number_format((float)round($deposit,2)));
                    }
                    $product_page_html = sprintf('<div>
                                    <p class="pim-product-page">
                                      <img style="vertical-align: middle; padding: 10px; max-height: 58px" src="%s" alt="PayItMonthly | Buy Now Pay Later Instalments" title="PayItMonthly | Buy Now Pay Later Instalments" id="pim-logo">
                                      <span style="vertical-align: middle; display: inline-block;"> pay in interest free monthly instalments: %s x £%s %s</span>
                                    </p>
                                   </div>', esc_attr($pim_payment_gateway->icon), esc_attr($product_max_installments), esc_attr($instalment_value), esc_attr($deposit_line));
                    print($product_page_html);
                } else {
                    return $price;
                }

            } else return $price;
        } else {
            return $price;
        }
        return $price;
    }


    /**
     * Display pim installments calculation text after order total
     * @since    1.0.0
     */
    public function add_pim_price_before_after_totals_after_order_total()
    {
        // check if checkout page
        if (!is_checkout()) {
            $payment_gateways = WC()->payment_gateways->payment_gateways();
            if ($payment_gateways and isset($payment_gateways['wc_pim_gateway'])) {
                $pim_payment_gateway = $payment_gateways['wc_pim_gateway'];
                if ($pim_payment_gateway->enabled == "yes" and $pim_payment_gateway->show_on_cart_page == "yes") {
                    $product_max_installments = $pim_payment_gateway->get_number_of_installments(true);
                    if ($product_max_installments) {
                        $total_price = WC()->cart->get_total(false);
                        $deposit = apply_filters("pim_wc_payment_gateway_deposit", 0);
                        $total_price = $total_price - $deposit;

                        $deposit_line = "";
                        if ($deposit){
                            $deposit_line= sprintf(" (and £%s deposit)",number_format((float)round($deposit,2)));
                        }
                        $instalment_value = number_format((float)round($total_price / $product_max_installments, 2), 2, '.', '');
                        $display = sprintf("<div> Pay in interest free monthly instalments: %s x £%s with PayItMonthly %s</div>", esc_attr($product_max_installments), esc_attr($instalment_value),esc_attr($deposit_line));
                        print($display);
                    }

                }
            }

        }


    }


    /**
     * Add custom fields to wc product settings page
     * @since    1.0.0
     */
    public function woocommerce_pim_product_custom_fields()
    {
        global $woocommerce, $post;
        echo '<div class="product_custom_field">';
        // Custom Product Text Field
        woocommerce_wp_select(
            array(
                'id' => '_pim_wc_product_option_select',
                'label' => __('PayItMonthly payment gateway option', 'woocommerce_payment_gateway_payitmonthly'),
                'desc_tip' => 'true',
                'type' => "select",
                'description' => 'You can specify the maximum number of instalments or you can disable PayItMonthly finance option for this product.',
                'options' => array(
                    "default" => "Use gateway default",
                    "2" => "2 instalments maximum",
                    "3" => "3 instalments maximum",
                    "4" => "4 instalments maximum",
                    "5" => "5 instalments maximum",
                    "6" => "6 instalments maximum",
                    "7" => "7 instalments maximum",
                    "8" => "8 instalments maximum",
                    "9" => "9 instalments maximum",
                    "10" => "10 instalments maximum",
                    "11" => "11 instalments maximum",
                    "12" => "12 instalments maximum",
                    "disable_for_product" => "Disable but allowed if another eligible product is in the basket",
                    "disable_for_all_product" => "Disable finance for the basket if this item is in it",
                )
            )
        );
        echo '</div>';
    }


    /**
     * @param $post_id
     */
    public function woocommerce_pim_product_custom_fields_save($post_id)
    {
        $woocommerce_custom_product_text_field = sanitize_text_field($_POST['_pim_wc_product_option_select']);
        if (!empty($woocommerce_custom_product_text_field) and strlen($woocommerce_custom_product_text_field) > 0 and strlen($woocommerce_custom_product_text_field) < 50) {
            update_post_meta($post_id, '_pim_wc_product_option_select', esc_attr($woocommerce_custom_product_text_field));
        }

    }


    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     * @since     1.0.0
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    Payitmonthly_Woocommerce_Payment_Gateway_Loader    Orchestrates the hooks of the plugin.
     * @since     1.0.0
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     * @since     1.0.0
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * Plugin url.
     *
     * @return string
     */
    public static function plugin_url() {
        return untrailingslashit( plugins_url( '/', __FILE__ ) );
    }

    /**
     * Plugin url.
     *
     * @return string
     */
    public static function plugin_abspath() {
        return trailingslashit( plugin_dir_path( __FILE__ ) );
    }



    /**
     * WC payment blocks support
     *
     * @since    1.2.0
     */
    public function woocommerce_gateway_pim_woocommerce_block_support() {

        if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
            require_once 'class/WC_PIM_Gateway_Blocks.php';
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                    $payment_method_registry->register( new WC_PIM_Gateway_Blocks() );
                }
            );
        }
    }



}





<?php


/**
 * Class WC_PIM_Gateway
 */
class WC_PIM_Gateway extends WC_Payment_Gateway
{

    /**
     * live or test mode
     * @since    1.0.0
     * @var string
     */
    protected $live_or_test;

    /**
     * Specify maximum installments
     * @since    1.0.0
     * @var string
     */
    protected $max_installments;
    /**
     * @var string
     */
    private $api_key;
    /**
     * @since    1.0.0
     * @var string
     */
    protected $private_key;

    /**
     * @since    1.0.0
     * @var string
     */
    protected $password;


    /**
     * @since    1.0.0
     * @var string
     */
    protected $minimum_gateway_value;

    /**
     * @since    1.1.0
     * @var string
     */
    protected $maximum_gateway_value;

    /**
     * @since    1.1.0
     * @var string
     */
    protected $top_up_deposit_if_max_exceed;


    /**
     * Limit the length of description of the goods to value
     * @since    1.0.0
     * @var int
     */
    protected $goods_description_max_length = 120;

    /**
     * @since    1.0.0
     * @var string
     */
    private $prefer_max_of_installments;
    /**
     * @since    1.0.0
     * @var string
     */
    public $show_on_product_page;
    /**
     * @since    1.0.0
     * @var string
     */
    public $show_on_cart_page;

    /**
     * @since    1.0.0
     * @var bool|int
     */
    private $num_of_installments_calc;

    /**
     * @since    1.0.3
     * @var string
     */
    public $order_id_prefix;

    /**
     * @since    1.0.3
     * @var string
     */
    public $debug;

    /**
     * @since    1.0.3
     * @var mixed
     */
    public $gw_logger;

    /**
     * @since    1.0.3
     * @var string
     */
    private $env;


    /**
     * Class constructor
     */
    public function __construct()
    {

        $this->id = 'wc_pim_gateway'; // payment gateway plugin ID
        $this->has_fields = false;
        $this->method_title = 'PayItMonthly payment gateway';
        $this->method_description = 'PayItMonthly payment gateway integration';

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->title = "Pay it in installments with PayItMonthly";
        $this->description = "Make your purchase more affordable with PayItMonthly";

        $this->enabled = $this->get_option('enabled');

        $this->live_or_test = 'yes' === $this->get_option('testmode') ? "TEST" : "LIVE";
        $this->api_key = $this->get_option('pim_api_key');
        $this->password = $this->get_option('pim_api_password');
        $this->max_installments = $this->get_option('max_installments');
        $this->minimum_gateway_value = $this->get_option('minimum_gateway_value');
        $this->maximum_gateway_value = $this->get_option('maximum_gateway_value');
        $this->top_up_deposit_if_max_exceed = $this->get_option('top_up_deposit_if_max_exceed');
        $this->icon = "https://app.payitmonthly.uk/static/PIM_LOGO_C_small.png";
        $this->show_on_product_page = $this->get_option('show_on_product_page');
        $this->show_on_cart_page = $this->get_option('show_on_cart_page');
        $this->prefer_max_of_installments = $this->get_option('prefer_max_of_installments');

        $this->order_id_prefix = $this->get_option('order_id_prefix');
        $this->debug = 'yes' === $this->get_option('debug');

        if ($this->debug) {
            $this->gw_logger = wc_get_logger();
        } else {
            $this->gw_logger = false;
        }
        $this->env = "prod";

        // This action hook saves the settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        add_action('woocommerce_api_wc_pim_gateway', array($this, 'webhook'));


        add_filter('woocommerce_available_payment_gateways', array($this, 'filter_gateway_by_price'));

        add_filter('pim_wc_payment_gateway_generate_goods_description', array($this, 'generate_goods_description'), 10, 3);

        add_filter('pim_wc_payment_gateway_deposit', array($this, 'filter_top_up_deposit_if_max_fiance_exceeds'), 10, 4);


    }

    /**
     * Plugin options, we deal with it in Step 3 too
     */
    public function init_form_fields()
    {

        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'label' => 'Enable PIM payment gateway',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
//            'title' => array(
//                'title' => 'Title',
//                'type' => 'text',
//                'description' => 'This controls the title which the user sees during checkout.',
//                'default' => 'Pay it in installments with PayItMonthly',
//                'desc_tip' => true,
//            ),
//            'description' => array(
//                'title' => __('Description', "woocommerce_payment_gateway_payitmonthly"),
//                'type' => 'textarea',
//                'description' => __('This controls the description which the user sees during checkout.', "woocommerce_payment_gateway_payitmonthly"),
//                'default' => __('Pay it in installments with PayItMonthly', "woocommerce_payment_gateway_payitmonthly"),
//                'desc_tip' => true
//            ),
            'testmode' => array(
                'title' => 'Test mode',
                'label' => 'Enable Test Mode',
                'type' => 'checkbox',
                'description' => 'Creates test agreements, so you can see how it works.',
                'default' => 'yes',
                'desc_tip' => true,
            ),

            'pim_api_key' => array(
                'title' => 'Api Key',
                'type' => 'text',
                'default' => ''
            ),
            'pim_api_password' => array(
                'title' => 'Api Password',
                'type' => 'password',
                'default' => ''
            ),
//            'check_login' => array(
//                'title' => esc_html__('Verify login', 'text-domain'),
//                'desc' => esc_html__('You can click here to verify password', 'text-domain'),
//                'type' => 'check_login',
//                'default' => '',
//                'desc_tip' => true,
//            ),
            'minimum_gateway_value' => array(
                'title' => 'Minimum finance value',
                'type' => 'number',
                'description' => 'Minimum amount to enable payment option',
                'default' => '10',
                'desc_tip' => true,
                'custom_attributes' => array(
                    'step' => 'any',
                    'min' => '10'
                )
            ),
            'maximum_gateway_value' => array(
                'title' => 'Maximum finance value',
                'type' => 'number',
                'description' => 'Maximum amount to enable payment option',
                'default' => '3000',
                'desc_tip' => true,
                'custom_attributes' => array(
                    'step' => 'any'
                )
            ),
            'max_installments' => array(
                'title' => 'Maximum number of instalments',
                'label' => 'Set Maximum number of instalments for the agreement',
                'type' => 'select',
                'description' => 'You can specify the maximum number of instalments. You can set this individually per product in product settings.',
                'default' => '12',
                'desc_tip' => true,
                'options' => array(
                    "2" => "2",
                    "3" => "3",
                    "4" => "4",
                    "5" => "5",
                    "6" => "6",
                    "7" => "7",
                    "8" => "8",
                    "9" => "9",
                    "10" => "10",
                    "11" => "11",
                    "12" => "12",
                )
            ),
            'show_on_product_page' => array(
                'title' => 'Product Page',
                'label' => 'Displays instalment option on product page',
                'type' => 'checkbox',
                'description' => 'Shows your customer how much the monthly instalments will be on the product page.',
                'default' => 'yes',
                'desc_tip' => true,
            ),
            'show_on_cart_page' => array(
                'title' => 'Cart Page',
                'label' => 'Displays instalment option on cart page',
                'type' => 'checkbox',
                'description' => 'Shows your customer how much the total monthly instalments will be on the cart page.',
                'default' => 'yes',
                'desc_tip' => true,
            ),
            'prefer_max_of_installments' => array(
                'title' => 'Allow maximum number of instalments',
                'label' => 'Check all products in the basket and uses the maximum number of instalments',
                'type' => 'checkbox',
                'description' => 'You can set this individually per product in product settings.
                 If your customers have different maximum instalments in their basket select this option to prioritise the highest value. 
                 If you dont choose this option, the lowest value will be used. ',
                'default' => 'yes',
                'desc_tip' => true,
            ),
            'advanced_settings' => array(
                'title' => 'Advanced setting',
                'type' => 'title'
            ),
            'top_up_deposit_if_max_exceed' => array(
                'title' => 'Top up deposit if finance value exceeds the maximum',
                'type' => 'checkbox',
                'description' => 'If the finance value exceeds the maximum finance value, the difference will be applied as a deposit. Warning: You are responsible to collect this deposit from the customer.',
                'default' => 'no',
                'desc_tip' => true,
            ),
            'order_id_prefix' => array(
                'title' => 'Prefix reference',
                'label' => 'Generates a prefix for the reference if in case of multiple WordPress installations',
                'type' => 'text',
                'description' => 'If you have multiple WordPress installations and one PIM account, you can set up a 5 character prefix what will be added to the PIM reference',
                'default' => '',
                'desc_tip' => true,
                'maxlength' => 3
            ),
            'debug' => array(
                'title' => 'Log Debug messages',
                'label' => 'Turn on Logging',
                'type' => 'checkbox',
                'description' => 'Debug logs',
                'default' => 'no',
                'desc_tip' => true,
            ),
//            'control_buttons' => array(
//                'title' => esc_html__('Overwrite settings for all products', 'text-domain'),
//                'desc' => esc_html__('You can disable/enable the finance option for all the products. The default is the enable to all products.', 'text-domain'),
//                'type' => 'control_buttons',
//                'default' => '',
//                'desc_tip' => true,
//            )

        );

    }


    /**
     * Create callback url
     * @param $order
     * @return string
     * @since    1.0.0
     */
    private function generate_callback_url($order)
    {

        $querry_params = [
            "id" => $order->get_id()
        ];

        $callback_url = WC()->api_request_url('wc_pim_gateway', true);
        $callback_url .= "?" . http_build_query($querry_params);
        return $callback_url;

    }


    /**
     * Generate goods if description from the order
     * @param $order_id
     * @param $order
     * @return string
     * @since 1.0.0
     */
    public function generate_goods_description($goods_description, $order_id, $order)
    {

        $goods_description_arr = [];

        // Get and Loop Over Order Items and create a description from quantity x name separated by commas
        foreach ($order->get_items() as $item_id => $item) {

            $name = $item->get_name();
            $quantity = $item->get_quantity();
            $goods_description_arr[] = "{$name} x {$quantity}";
        }

        if (empty($goods_description_arr)) {
            $goods_description = "Order id: {$order_id}";
        } else {
            $goods_description = implode(", ", $goods_description_arr);
        }

        // set a length limit of $goods_description to avoid validation issues
        $goods_description = strlen($goods_description) > $this->goods_description_max_length ? substr($goods_description, 0, $this->goods_description_max_length - 4) . " ..." : $goods_description;
        return $goods_description;
    }

    /**
     * Top up deposit if max finance exceed
     * @param $deposit
     * @param $order_id
     * @param $order
     * @return string
     * @since 1.1.0
     */
    public function filter_top_up_deposit_if_max_fiance_exceeds($deposit, $order_id = null, $order = null, $item_price = null)
    {
        if ($this->top_up_deposit_if_max_exceed == "yes" and $deposit == 0 and $this->maximum_gateway_value) {

            if ($order_id === null && $order === null) {
                if ($item_price) {
                    $total_price = $item_price;
                } else {
                    $total_price = WC()->cart->get_total(false);
                }
            } else {
                $total_price = $order->get_total();
            }

            if ($this->maximum_gateway_value < $total_price) {
                $deposit = $total_price - $this->maximum_gateway_value;
                $this->debug_log_info("Deposit £$deposit added to the order, because Top up deposit was set to yes");
            }

        }
        return $deposit;
    }


    /**
     * Redirect customer to finish payment
     * @param int $order_id
     * @return mixed
     * @throws Exception
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        $this->debug_log_info("Genearate PIM Finance application request for order if $order_id");

        if ($this->order_id_prefix) {
            $reference = $this->order_id_prefix . "_" . $order_id;
        } else {
            $reference = $order_id;
        }

        //add configs to the api call
        $finapp_data["config"] = [
            "application_type" => "INTEGRATION_WC",
            "live_or_test" => apply_filters("pim_wc_payment_gateway_live_or_test", $this->live_or_test, $order_id, $order),
            "reference" => apply_filters("pim_wc_payment_gateway_reference", $reference, $order_id, $order),
            "redirect_pass_url" => apply_filters("pim_wc_payment_gateway_redirect_pass_url", $order->get_checkout_order_received_url(), $order_id, $order),
            "redirect_fail_url" => apply_filters("pim_wc_payment_gateway_redirect_fail_url", wc_get_checkout_url(), $order_id, $order),
            "redirect_refer_url" => apply_filters("pim_wc_payment_gateway_redirect_refer_url", wc_get_checkout_url(), $order_id, $order),
            "webhook_response_url" => apply_filters("pim_wc_payment_gateway_webhook_response_url", $this->generate_callback_url($order), $order_id, $order),
            "meta" => [
                "type" => "wc-integration",
                "topup_deposit" => $this->top_up_deposit_if_max_exceed
            ]
        ];

        //add required financial details
        $finapp_data["finance_details"] = [
            "frequency" => "Monthly",
            "max_installments" => intval(apply_filters("pim_wc_payment_gateway_max_installments", $this->max_installments, $order_id, $order)),
            "goods_description" => apply_filters("pim_wc_payment_gateway_generate_goods_description", $order_id, $order_id, $order),
            "cost_of_goods" => $order->get_total(),
            // default deposit is 0
            "deposit" => round(floatval(apply_filters("pim_wc_payment_gateway_deposit", 0, $order_id, $order)), 2)
        ];

        // add personal details
        $finapp_data["personal_details"] = [
            "first_name" => $order->get_billing_first_name(),
            "last_name" => $order->get_billing_last_name(),
            "mobile_number" => $order->get_billing_phone()
        ];

        // personal details billing address
        $finapp_data["personal_details"]["address"] = [
            "line_1" => $order->get_billing_address_2(),
            "line_2" => $order->get_billing_address_1(),
            "town" => $order->get_billing_city(),
            "postcode" => $order->get_billing_postcode(),
        ];
        if ($order->get_shipping_postcode() and $order->get_shipping_address_1()) {
            $finapp_data["personal_details"]["shipping_address"] = [
                "line_1" => $order->get_shipping_address_2(),
                "line_2" => $order->get_shipping_address_1(),
                "town" => $order->get_shipping_city(),
                "postcode" => $order->get_shipping_postcode()
            ];
        }

        //construct WC_PIM_Gateway_API
        $GW_API = new WC_PIM_Gateway_API($this->api_key, $this->password, $this->env);
        // get stored form_id to decide to update or create a new finance application
        $stored_form_id = $order->get_meta('_wc_pim_int_form_id');

        if ($stored_form_id and is_string($stored_form_id)) {
            $this->debug_log_info("Stored finance application found $stored_form_id . Update finance application...");
            $resp = $GW_API->update_finance_application($finapp_data, $stored_form_id, $order);
            if (!$resp) {
                $this->debug_log_info("Failed to update finance application.");
                return null;
            }
            $this->debug_log_info("Finance application updated successfully");
            $redirect_to = $resp["url_link"];


        } else {
            $resp = $GW_API->generate_finance_application($finapp_data, $order);
            $this->debug_log_info("Generate new finance application ...");
            if (!$resp) {
                return null;
            }
            $this->debug_log_info("Finance application created successfully. User will be redirected to finish the application.");
            $redirect_to = $resp["url_link"];
            //save pim form id to wc product meta
            $order->update_meta_data('_wc_pim_int_form_id', $resp["form_id"]);
            $order->save();
        }


        //return success and redirect url where customers can finish the finance application
        return array(
            'result' => 'success',
            'redirect' => "$redirect_to"
        );

    }


    /**
     * Receive webhook, PIM payment outcome
     *
     * @throws WC_Data_Exception
     * @throws Exception
     */
    public function webhook()
    {
        // get the order by id
        $order_id = intval($_GET['id']);
        $this->debug_log_info("Webhook received. processing webhook.");
        if ($order_id) {
            $this->debug_log_info("Order id : $order_id. Load order...");
            global $woocommerce;
            $order = wc_get_order($order_id);

            if ($order) {
                $this->debug_log_info("Order found for order id: $order_id ");
                $GW_API = new WC_PIM_Gateway_API($this->api_key, $this->password, $this->env);
                $this->debug_log_info("Order found for order id: $order_id");
                $stored_form_id = $order->get_meta('_wc_pim_int_form_id');
                $this->debug_log_info("Stored form id: $stored_form_id ");
                $resp = $GW_API->get_finance_application($stored_form_id);
                $this->debug_log_info("Load finance application outcome... ");
                if (isset($resp["response"]["finance_application"]["uuid"]) and $stored_form_id == $resp["response"]["finance_application"]["uuid"]) {

                    if (isset($resp["response"]["finance_application"]["decision"]["outcome"])) {
                        $outcome = $resp["response"]["finance_application"]["decision"]["outcome"];
                        $agreement_reference = $resp["response"]["finance_application"]["decision"]["agreement_reference"];

                        // success outcome
                        if ($outcome == "SUCCESS") {
                            $order->set_billing_first_name($resp["response"]["finance_application"]["personal_details"]["first_name"]);
                            $order->set_billing_last_name($resp["response"]["finance_application"]["personal_details"]["last_name"]);
                            $order->set_billing_phone($resp["response"]["finance_application"]["personal_details"]["mobile_number"]);
                            $order->set_billing_address_2($resp["response"]["finance_application"]["personal_details"]["current_address"]["line_1"]);
                            $order->set_billing_address_1($resp["response"]["finance_application"]["personal_details"]["current_address"]["line_2"]);
                            $order->set_billing_city($resp["response"]["finance_application"]["personal_details"]["current_address"]["town"]);
                            $order->set_billing_postcode($resp["response"]["finance_application"]["personal_details"]["current_address"]["postcode"]);
                            $order->set_billing_country("UK");
//                          $order->set_billing_email();
                            $order->save();

                            /**
                             * call payment_complete function
                             *
                             * Funbction calls:
                             * Rwc_get_logger() – Get a shared logger instance.
                             * WC_Order::add_order_note() – Adds a note (comment) to the order. Order must exist.
                             * WC_Order::get_date_paid() – Get date paid.
                             * WC_Order::needs_processing() – See if the order needs processing before it can be completed.
                             * WC_Order::save() – Save data to the database.
                             * WC_Order::set_date_paid() – Set date paid.
                             * WC_Order::set_status() – Set order status.
                             * WC_Order::set_transaction_id() – Set transaction id.
                             * WC() – Returns the main instance of WC.
                             *
                             */
                            $order->payment_complete();


                            /**
                             * Remove cart
                             * Empty card after success callback
                             * Cart is not cleared at checkout, it the customer wants to edit the cart they can do
                             */
                            $woocommerce->cart->empty_cart();

                            //add note
                            $order->add_order_note("PIM finance application completed, finance accepted. You can find it with $agreement_reference PIM reference.");
                            if (isset($resp["response"]["finance_application"]["finance_details"]["deposit"]) and $resp["response"]["finance_application"]["finance_details"]["deposit"] > 0) {
                                $deposit_to_take = $resp["response"]["finance_application"]["finance_details"]["deposit"];
                                $order->add_order_note("Reminder: There is a deposit of £$deposit_to_take that you need to take.");
                            }

                        } elseif ($outcome == "FAILED") {
                            $order->update_status('failed', 'PIM finance application completed, customer failed the credit check.');
                            $this->debug_log_info("Finance application loaded, updating status to failed. Updateting status to failed");

                        } elseif ($outcome == "REFER") {
                            //update refer to on-hold
                            $order->update_status('on-hold', "PIM finance application completed, outcome status is REFER. You can find it with $agreement_reference PIM reference on the PayItMonthly website.");
                            $this->debug_log_info("Finance application loaded, updating status to on-hold");
                        } else {
                            $order->add_order_note('PIM finance application completed, outcome status is: ' . $resp["response"]["finance_application"]["decision"]["outcome"]);
                            $this->debug_log_info("Finance application loaded, updating status to $outcome");
                        }


                    } else {
                        $this->debug_log_info("Failed to load decesion outcome.");
                    }


                } else {


                    if (isset($resp)) {
                        $this->debug_log_info("Failed to load finance application. Response: " . wc_print_r($resp, true));
                    } else {
                        $this->debug_log_info("Failed to load finance application. No response.");
                    }
                }
            }

        }


    }


    /**
     * Return false if payment option is disabled for the product
     * @param $product_id
     * @param $num_of_products
     * @param $list_of_number_of_installments
     * @return bool
     */
    protected function pim_product_settings($product_id, $num_of_products, &$list_of_number_of_installments)
    {

        $product = wc_get_product($product_id);
        $pim_product_meta_value = $product->get_meta("_pim_wc_product_option_select");

        if (!$pim_product_meta_value or $pim_product_meta_value == "default") {
            $list_of_number_of_installments[] = $this->max_installments;
            $unset = false;
        } elseif ($pim_product_meta_value == "disable_for_product" and $num_of_products == 1) {
            $unset = true;
        } elseif ($pim_product_meta_value == "disable_for_all_product") {
            $unset = true;

        } elseif (is_numeric($pim_product_meta_value) and $pim_product_meta_value >= 2 and $pim_product_meta_value <= 12) {
            $list_of_number_of_installments[] = $pim_product_meta_value;
            $unset = false;
        }
        return $unset;

    }


    /**
     * Get number of installments calculation
     * @param false $price_filter
     * @return false|mixed
     * @since    1.0.0
     */
    public function get_number_of_installments($price_filter = false)
    {

        $total_price = WC()->cart->get_total(false);

        // if price filter is true add the total value condition to the evaluation too
        if ($price_filter) {
            //get cart total
            if ($this->minimum_gateway_value && $total_price < $this->minimum_gateway_value) {
                return false;
            }
            // check maximum_gateway_value and top_up_deposit_if_max_exceed conditions
            if ($this->maximum_gateway_value && $this->top_up_deposit_if_max_exceed == "no" && $total_price > $this->maximum_gateway_value) {
                return false;
            }
        }


        $products_in_cart = WC()->cart->get_cart_contents();
        $count_of_products = count($products_in_cart);
        $unset = false;
        $list_of_number_of_installments = [];
        foreach (WC()->cart->get_cart_contents() as $key => $values) {
            $unset = $this->pim_product_settings($values['product_id'], $count_of_products, $list_of_number_of_installments);
            if ($unset) break;
        }


        if ($unset) {
            return false;
        } else {

            //get the max or min based on gateway settings
            if ($this->prefer_max_of_installments == "yes") {
                $product_max_installments = max($list_of_number_of_installments);
            } else {
                $product_max_installments = min($list_of_number_of_installments);
            }

            $this->max_installments = $product_max_installments;


            $deposit = apply_filters("pim_wc_payment_gateway_deposit", 0);
            $total_price = $total_price - $deposit;

            $deposit_line = "";
            if ($deposit) {
                $deposit_line = sprintf(" (and £%s deposit)", number_format((float)round($deposit, 2)));
            }
            $instalment_value = number_format((float)round($total_price / $product_max_installments, 2), 2, '.', '');

            // display before place order at checkout
            $this->title = "Pay in interest free monthly instalments: $product_max_installments x £$instalment_value with PayItMonthly $deposit_line";

            return $product_max_installments;
        }


    }


    /**
     * Disable gateway based on minimum value, istallments and product settings
     * @param $available_gateways
     * @return mixed
     */
    public function filter_gateway_by_price($available_gateways)
    {

        // filter only for checkout
        if (!is_checkout()) return $available_gateways;
        // if gateway is not set return $available_gateways
        if (!isset($available_gateways['wc_pim_gateway'])) return $available_gateways;

        //make sure we are calling the calculation once
        if ($this->num_of_installments_calc) {
            return $available_gateways;
        } else {
            $num_of_installments = $this->get_number_of_installments(true);
            $this->num_of_installments_calc = $num_of_installments;
        }

        //if $num_of_installments is false disable gateway
        if (!$num_of_installments) {
            unset($available_gateways['wc_pim_gateway']);
        }
        return $available_gateways;


    }


    /**
     * Returns product max instalments settings or false if finance is disabled for the product
     *
     * @param $product_id
     * @return false|int
     * @since    1.0.0
     */
    public function get_product_max_instalments($product_id)
    {
        $product = wc_get_product($product_id);
        $pim_product_meta_value = $product->get_meta("_pim_wc_product_option_select");
        $installments = false;
        if ($this->maximum_gateway_value && $this->top_up_deposit_if_max_exceed == "no" && $product->get_price() > $this->maximum_gateway_value) {
            return false;
        }
        if (!$pim_product_meta_value or $pim_product_meta_value == "default") {
            $installments = $this->max_installments;
        } elseif ($pim_product_meta_value == "disable_for_product" or $pim_product_meta_value == "disable_for_all_product") {
            $installments = false;
        } elseif (is_numeric($pim_product_meta_value) and $pim_product_meta_value >= 2 and $pim_product_meta_value <= 12) {
            $installments = $pim_product_meta_value;
        }
        return $installments;

    }

    /**
     * Validate order id order_id_prefix field
     * @param $key
     * @param $value
     * @return false|mixed
     * @since    1.0.3
     */
    public function validate_order_id_prefix_field($key, $value)
    {

        if ($key && $key === "order_id_prefix" && isset($value) && 5 <= strlen($value)) {
            WC_Admin_Settings::add_error('Looks like you made a mistake with the Prefix reference. Make sure it less than 5 characters.');
            $this->display_errors();
        } else {
            return $value;
        }
    }


    /**
     * Validate order id order_id_prefix field
     * @param $message
     * @return false|mixed
     * @since    1.0.3
     */

    public function debug_log_info($message)
    {

        if ($this->debug && $this->gw_logger) {
            //wc_print_r( $order, true )
            $this->gw_logger->info($message, array('source' => 'PIM-WC-payment-gateway'));
        }


    }







//    function handle_verify_login()
//    {
//        check_ajax_referer("pim_n_check_l");
//        $mode = intval($_POST['mode']);
//
//        $success = false;
//        $GW_API = new WC_PIM_Gateway_API($this->api_key, $this->password, $this->env);
//
//        echo json_encode(["success"=>$success]);
//
//        wp_die();
//    }

}


///**
// * Filter to add order id to the description of goods
// * @param string $goods_description
// * @param int $order_id
// * @param WC_Order $order
// * @return string cost of goods with order id
// */
//function generate_goods_description_with_order_id($goods_description, $order_id, $order){
//
//    # add $order_id to the goods_description
//    $goods_description = "#${order_id} ${goods_description}";
//
//    // max length of the goods_description field. Dont change it
//    $goods_description_max_length = 120;
//    // if field is to long add ... to the end
//    $goods_description = strlen($goods_description) > $goods_description_max_length ? substr($goods_description, 0, $goods_description - 4) . " ..." : $goods_description;
//    return $goods_description;
//}
//
//add_filter('pim_wc_payment_gateway_generate_goods_description', 'generate_goods_description_with_order_id', 11, 3);

///**
// * Filter to update deposit to 50%
// */
//function update_pim_deposit_to_fifty_percent($deposit, $order_id = null, $order = null, $item_price = null)
//{
//    if (!class_exists("WC_Payment_Gateway")) {
//        return null;
//    }
//
//    // deposit amount in percentage
//    $deposit_in_percentage = 0.5;
//
//    $payment_gateways = WC()->payment_gateways->payment_gateways();
//    if ($payment_gateways and isset($payment_gateways['wc_pim_gateway'])) {
//        $pim_payment_gateway = $payment_gateways['wc_pim_gateway'];
//
//        if ($pim_payment_gateway->enabled == "yes") {
//            if (is_product()) {
//                global $post;
//                $product_id = $post->ID;
//                $product = wc_get_product($product_id);
//                $total_price = $product->get_price();
//            } else {
//                $total_price = WC()->cart->get_total(false);
//            }
//
//            if ($total_price) {
//                $deposit = round($total_price * $deposit_in_percentage, 2);
//            } else {
//                $deposit = 0;
//            }
//        }
//    }
//
//    return $deposit;
//}
//
//add_filter('pim_wc_payment_gateway_deposit', 'update_pim_deposit_to_fifty_percent', 11, 3);


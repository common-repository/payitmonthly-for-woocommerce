<?php


/**
 * Class WC_PIM_Gateway_API
 */
class WC_PIM_Gateway_API
{
    /**
     * Environment string for local development
     * @var string
     */
    protected $environment;

    /**
     * Login cred key
     * @var
     */
    protected $key;

    /**
     * Login cred password
     * @var
     */
    protected $pass;

    /**
     * Base url endpoint default is https://app.payitmonthly.uk
     * @var string
     */
    protected $base_endpoint="https://app.payitmonthly.uk";

    /**
     * WC_PIM_Gateway_API constructor.
     * @param $key
     * @param $pass
     * @param $env
     */
    public function __construct($key,$pass,$env)
    {
        $this->environment=$env;
        if ($this->environment == "test") {
            $this->base_endpoint = "https://testapp.payitmonthly.uk/";
        }
        if ($this->environment == "prod") {
            $this->base_endpoint = "https://app.payitmonthly.uk/";
        }

        $this->key = $key;
        $this->pass = $pass;
    }

    /**
     * Create a PayItMonthly finance application, it will return the url link where the customers can sign the agreement
     * @param $finapp_data
     * @param $order
     * @return array
     * @since 1.0.0
     */
    public function generate_finance_application($finapp_data,$order)
    {
        //construct request
        $pim_request = new WC_PIM_Gateway_request($this->key, $this->pass,$this->base_endpoint,$this->environment);
        $resp = $pim_request->createFinapp($finapp_data);

        if ($resp["successful_call"] and isset($resp["response"]["token"]) and isset($resp["response"]["uuid"])) {
            $token = $resp["response"]["token"];
            $form_id = $resp["response"]["uuid"];
            $url_link = $this->base_endpoint."agreement/create_agreement_integration/{$form_id}/?token={$token}";
            $order->add_order_note( "PIM finance application created successfully");
            return ["url_link"=>$url_link,"form_id"=>$form_id,"token"=>$token];
        } else {
            $this->pim_generate_error_message($resp["response"], ["uuid","expiry","token","password"], null);
            $order->add_order_note( "Customer failed to generate PIM finance application: ".$this->error_string);
            wc_add_notice($this->error_string,"error");
            return null;
        }
    }

    /**
     * Update an existing PayItMonthly finance application
     * @param $finapp_data
     * @param $form_id
     * @param $order
     * @return array
     * @since  1.0.0
     */
    public function update_finance_application($finapp_data, $form_id,$order)
    {

        $pim_request = new WC_PIM_Gateway_request($this->key, $this->pass,$this->base_endpoint,$this->environment);

        $resp = $pim_request->updateFinapp($finapp_data,$form_id);
        if ($resp["successful_call"] and isset($resp["response"]["token"]) and isset($resp["response"]["uuid"])) {
            $token = $resp["response"]["token"];
            $form_id = $resp["response"]["uuid"];
            $url_link = $this->base_endpoint."agreement/create_agreement_integration/{$form_id}/?token={$token}";
            $order->add_order_note( "Customer selected PIM finance option. Order status will be updated if the customer sign the agreement.");
            return ["url_link"=>$url_link,"form_id"=>$form_id,"token"=>$token];
        } else {
            $this->pim_generate_error_message($resp["response"], ["uuid","expiry","token","password"], null);
            wc_add_notice($this->error_string,"error");
            $order->add_order_note( "PIM finance application: failed to create application: "."$this->error_string" );
            return null;
        }
    }


    /**
     * Retrieve a finance application to check the status and details
     * @since 1.0.0
     * @param $form_id
     * @return array
     * @throws Exception
     */
    public function get_finance_application($form_id)
    {

        $pim_request = new WC_PIM_Gateway_request($this->key, $this->pass,$this->base_endpoint,$this->environment);

        $resp = $pim_request->getFinapp($form_id);

        if ($resp["successful_call"] and isset($resp["response"]["finance_application"]["uuid"])) {
            return $resp;
        } else {
            die($resp);
        }
    }

    /**
     * Retrieve a finance application to check the status and details
     * @param $array
     * @param $ignore_list
     * @param string $keysString
     * @since 1.0.4
     */

    public function pim_generate_error_message($array, $ignore_list, $keysString = '')
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {

                $this->pim_generate_error_message($value, $ignore_list, $keysString . $key . '.');
            }
        } else {
            if (in_array($keysString, $ignore_list)) {

            } else {
                $last_element = "Error ";
                try {
                    $exp_list = explode(".", $keysString);
                    $exp_list = array_filter($exp_list);

                    $last_element = $exp_list[sizeof($exp_list) - 1];
                } catch (Exception $e) {
                    $last_element = "Error ";
                }

                if(!$ignore_list or !in_array($last_element, $ignore_list)){
                    $this->error_string = $this->error_string . $last_element . ": " . $array . '<br/> ';
                }




            }

        }
    }


}
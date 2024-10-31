<?php


/**
 * Class WC_PIM_Gateway_request
 */
class WC_PIM_Gateway_request
{
    /**
     * @var null
     */
    protected $token = null;
    /**
     * @var
     */
    private $email;

    /**
     * @var
     */
    private $password;
    /**
     * @var string
     */
    private $endpoint_create_finapp;
    /**
     * @var string
     */
    private $endpoint_login;

    /**
     * environment variable to debug
     * @var string
     */
    private $enviroment;

    /**
     * auth error string
     * @var string
     */
    private $auth_error;


    /**
     * WC_PIM_Gateway_request constructor.
     * @param $email
     * @param $password
     * @param $base_endpoint
     * @param $environment
     */
    public function __construct($email, $password, $base_endpoint, $environment)
    {


        $this->email = $email;
        $this->password = $password;
        $this->enviroment = $environment;
        $this->endpoint_create_finapp = $base_endpoint . "api/v1/agreement/finance_application/";
        $this->endpoint_login = $base_endpoint . "api/v1/partner/auth/";
        $this->getToken();
    }


    /**
     * request authorization token from PayItMonthly auth endpoint
     *
     */
    public function request_auth_token()
    {
        $this->auth_error = false;
        $body = array("email" => $this->email, "password" => $this->password);
        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body),
            'method' => "POST"
        );
        $response = wp_remote_request($this->endpoint_login, $args);

        $body = wp_remote_retrieve_body($response);


        if ($body) {
            $data = json_decode($body, true);
            if (!$data["token"]) {
                $http_code = wp_remote_retrieve_response_code($response);
                if ($http_code == 401) {
                    $this->auth_error = "Invalid authentication credentials";
                } else {
                    $this->auth_error = "Authentication error";
                }
            } else {
                //set token if valid
                $this->token = $data["token"];
                $this->setToken();
            }
        } else {
            $this->auth_error = "Unexpected error";
        }


    }


    /**
     * @param $body
     * @param $method
     * @param $endpoint
     * @return array
     */
    protected function request($body, $method, $endpoint)
    {

        if ($method == "POST" || $method == "PUT") {
            $body = json_encode($body);
        }

        $return = false;
        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                "Authorization" => "token " . $this->token
            ),
            'body' => $body,
            'method' => $method
        );
        $response = wp_remote_request($endpoint, $args);
        $http_code = wp_remote_retrieve_response_code($response);
        //if auth token is not valid request new and repeat the request
        if ($http_code == 401) {
            $this->request_auth_token();
            // add refreshed auth token to the header
            $args["headers"]["Authorization"] = "token " . $this->token;
            $response = wp_remote_request($endpoint, $args);
            $http_code = wp_remote_retrieve_response_code($response);
        }

        // if auth error return error response
        if ($this->auth_error) {
            return array("response" => ["error" => $this->auth_error], "successful_call" => $http_code == 0, "httpcode" => $http_code);
        }

        $response_body = wp_remote_retrieve_body($response);
        if ($response_body) {
            $data = json_decode($response_body, true);
            $return = array("response" => $data, "successful_call" => $http_code == 200 ? 1 : 0, "httpcode" => $http_code);

        } else {
            $return = array("response" => false, "successful_call" => 0, "httpcode" => $http_code);
        }

        return $return;

    }


    /**
     * Get token oauth token from the options. If it is not set, request it and save it.
     */
    protected function getToken()
    {
        $token = get_option("_pim_integration_oauth_token", false);
        if ($token) {
            $this->token = $token;
            $this->setToken();
        } else {
            // if token not exist request a token
            $this->request_auth_token();
        }

    }


    /**
     * Save oauth token to the options
     */
    public function setToken()
    {
        if ($this->token and is_string($this->token))
            update_option("_pim_integration_oauth_token", $this->token);
    }


    /**
     * @param $finapp_data
     * @return array
     */
    public function createFinapp($finapp_data)
    {
        return $res = $this->request($finapp_data, "POST", $this->endpoint_create_finapp);
    }

    /**
     * @param $finapp_data
     * @param $uuid
     * @return array
     */
    public function updateFinapp($finapp_data, $uuid)
    {
        return $res = $this->request($finapp_data, "PUT", $this->endpoint_create_finapp . $uuid . "/");
    }

    /**
     * @param $uuid
     * @return array
     */
    public function getFinapp($uuid)
    {
        return $res = $this->request([], "GET", $this->endpoint_create_finapp . $uuid . "/");
    }


}
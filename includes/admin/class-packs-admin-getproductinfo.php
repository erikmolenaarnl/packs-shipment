<?php

namespace PACKS\SHIPMENTS\Admin;

use PACKS\SHIPMENTS\Admin\AdminNotice;
use PACKS\SHIPMENTS\Settings;
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('\\PACKS\\SHIPMENTS\\Admin\\Getproductinfo')) :


    class Getproductinfo
    {
        protected $apiUser;
        protected $apiUserPassword;
        protected $apiUrl;
        protected $apiAuthUrl;
        protected $orderIds;
        protected $shipments;
        protected $shipmentId;
        protected $_shipmentsData;
        protected $aToken;
        protected $tToken;
        public $continue;
        //protected $orders = array();
        public $responses = array();
        protected $responseMessages = array();
        protected $productoptions = array();
        protected $settings;

        public function __construct()
        {
            $this->settings = new Settings();


        }

        public function init(){

            $this->preprocessData();
            return $this->productoptions;
            //$this->responseHandler();

        }
        public function preprocessData(){

            $this->senderOptions = get_option('packs_shipments_settings_sender');
            $this->generalOptions = $this->settings->get_common_general_settings();
            $this->apiUser = $this->generalOptions['api_user_name']['default'];
            $this->apiUserPassword = $this->generalOptions['api_user_password']['default'];
            $this->apiUrl = $this->generalOptions['api_getproductinfo_url'];
            $this->apiAuthUrl = $this->generalOptions['api_auth_url'];

            set_time_limit(720000);                 // script timeout: 15 mins.
            ob_start();
            $this->continue = true;
            $this->Authorize();
            $this->processData();
        }

        public function Authorize()
        {

            $postString = "client_id=PacksOnlineApp&client_secret=secret&grant_type=password&scope=PacksOnlineAPI&username=".$this->apiUser."&password=".$this->apiUserPassword;

            // PHP cURL  for https connection with auth
            $result = wp_remote_post( $this->apiAuthUrl, array(
                    'method'      => 'POST',
                    'timeout'     => 10,
                    'redirection' => 10,
                    'httpversion' => '2.0',
                    'blocking'    => true,
                    'headers'     => array(),
                    'body'        => $postString
                )
            );

            if(is_wp_error($result)){
                
                // need to wait for error to occur again to figure out its nature
                error_log('Packs Shipments Error: '.$result->get_error_code() .'-'. $result->get_error_message());
                return;
            }

            $json = json_decode($result['body']);
            if(isset($json->error)){
                AdminNotice::create()
                    ->error('<strong>' . esc_html__('Packs Shipments', 'woocommerce') . '</strong> ' . sprintf('Error : %s', $json->error_description))
                    ->show();

                return;
            }
            $this->tToken = $json->token_type;
            $this->aToken = $json->access_token;
        }

        public function processData()
        {
            $senderData = $this->getSenderData();
            $handler = $senderData['handler'];
            $network = $senderData['network'];
            $i=1;

            try {
                // xml post structure
                $xml_post_array = array(
                    'Handler' => (string)$handler,
                    'Network' => (string)$network
                );

                $xml_post_string = json_encode($xml_post_array);


                // PHP cURL  for https connection with auth
                $response = wp_remote_post( $this->apiUrl, array(
                        'method'      => 'POST',
                        'timeout'     => 30,
                        'redirection' => 10,
                        'httpversion' => '1.1',
                        'blocking'    => true,
                        'headers'     => array(
                            "Accept"        => "application/json",
                            "Authorization" => $this->tToken." ".$this->aToken,
                            "Content-Type"  => "application/json",
                            "cache-control" => "no-cache"
                        ),
                        'body'        => $xml_post_string
                    )
                );


                if(is_wp_error($response)){

                    // need to wait for error to occur again to figure out its nature
                    error_log('Packs Shipments Error: '.$response->get_error_code() .'-'. $response->get_error_message());
                    return;
                } elseif(empty($response['body'])){

                    $this->continue = false;
                    AdminNotice::create()
                        ->error( '<strong>' . esc_html__('Packs Shipments', 'woocommerce') . '</strong> ' . 'Error : Api product info response is empty')
                        ->show();

                }else{


                    $httpCode = $response["response"]["code"];
                    if($httpCode == 404) {
                        $message = "HTTP Error 404. The requested resource is not found. URL";
                        $this->responseMessages['errors'][$i['id']] = $message;
                        return;
                    }
                    if($httpCode == 400) {
                        $message = wp_remote_retrieve_response_message($response);
                        $this->responseMessages['errors'][$i['id']] = $message.': '. wp_remote_retrieve_body($response) ;
                        $this->continue = false;
                        return;
                    }
                    if($httpCode == 503) {
                        $message = "HTTP Error 503. The api server is not available";
                        $this->responseMessages['errors'][$i['id']] = $message;
                        $this->continue = false;
                        return;
                    }

                    $jsonDecoded = json_decode(wp_remote_retrieve_body($response),true);

                    $this->responses = $jsonDecoded;

                    if(isset($senderData['default_seal'])){
                        foreach ($this->responses['products'] as $key => $product){
                            if(strtolower($product['product']) == $senderData['default_seal']){
                                $default_seal = $key;
                            }
                        }
                        if(isset($this->responses['products'][(int)$default_seal])){

                            $this->responses['products'][(int)$default_seal]['default'] = true;
                        }
                    }
                    $this->productoptions = $this->responses['products'];
                }



            }catch (Exception $e) {
                //Mage::log('time 4.1: ' . time(). ' : '.$e->getMessage(),NULL,'shipmentexport.log');
                //Mage::logException($e);

                // send email
                if($this->sendErrorMail == true){
                    $this->helper->sendErrorMail($e->getMessage(),$this->sendErrorMailTo);
                }
            }



        }

        public function getSenderData(){
            $senderData = array();
            $senderData['handler'] = $this->senderOptions['sender_handler']['default'];
            $senderData['network'] = $this->senderOptions['sender_network']['default'];
            $senderData['country'] = $this->senderOptions['sender_country']['default'];
            $senderData['city'] = $this->senderOptions['sender_city']['default'];
            $senderData['postcode'] = $this->senderOptions['sender_zipcode']['default'];
            $senderData['street'] = $this->senderOptions['sender_street']['default'];
            $senderData['housenumber'] = $this->senderOptions['sender_housenumber']['default'];
            $senderData['name'] = $this->senderOptions['sender_name']['default'];
            $senderData['default_seal'] = isset($this->senderOptions['sender_default_seal']) ? $this->senderOptions['sender_default_seal'] : null;
            return $senderData;
        }

    }

endif;

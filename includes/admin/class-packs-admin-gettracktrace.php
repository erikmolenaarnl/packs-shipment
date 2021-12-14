<?php
namespace PACKS\SHIPMENTS\Admin;
use PACKS\SHIPMENTS\Admin\AdminNotice;
use PACKS\SHIPMENTS\Settings;
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( !class_exists( '\\PACKS\\SHIPMENTS\\Admin\\Gettracktrace' ) ) :



    class Gettracktrace
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
        protected $continue;
        protected $shipmentItems;
        //protected $orders = array();
        public $responses = array();
        protected $responseMessages = array();
        protected $settings;

        public function __construct($shipmentId)
        {
            $this->shipmentId = $shipmentId;
            $this->settings = new Settings();
            $this->init();
        }

        public function init()
        {

            $this->preprocessData($this->shipmentId);

            $this->responseHandler();

        }

        public function preprocessData($data)
        {
            $this->senderOptions = get_option('packs_shipments_settings_sender');
            $this->generalOptions = $this->settings->get_common_general_settings();
            $this->apiUser = $this->generalOptions['api_user_name']['default'];
            $this->apiUserPassword = $this->generalOptions['api_user_password']['default'];
            $this->apiUrl = $this->generalOptions['api_gettracktrace_url'];
            $this->apiAuthUrl = $this->generalOptions['api_auth_url'];

            set_time_limit(720000);                 // script timeout: 15 mins.
            ob_start();

            $this->shipmentItems = $this->getShipmentItems($this->shipmentId);
            $this->Authorize();
            $this->processData($this->shipmentId);
        }

        public function Authorize()
        {

            $postString = "client_id=PacksOnlineApp&client_secret=secret&grant_type=password&scope=PacksOnlineAPI&username=".$this->apiUser."&password=".$this->apiUserPassword;

            // PHP cURL  for https connection with auth
            $result = wp_remote_post( $this->apiAuthUrl, array(
                    'method'      => 'POST',
                    'timeout'     => 30,
                    'redirection' => 10,
                    'httpversion' => '2.0',
                    'blocking'    => true,
                    'headers'     => array(),
                    'body'        => $postString
                )
            );

            if(is_wp_error($result)){

                // need to wait for error to occur again to figure out its nature
                error_log($result->get_error_code() .'-'. $result->get_error_message());
               return;
            }

            $json = json_decode($result['body']);
            $this->tToken = $json->token_type;
            $this->aToken = $json->access_token;

        }

        public function processData($data)
        {
            $missingTracktrace = array();
            if(is_array($data)){
                foreach ($this->shipmentItems as $key=>$item){
                    foreach ($item as $shipmentItem){
                        array_push($missingTracktrace, array('shipmentId' => $key, 'shipmentItemId' => (string)$shipmentItem['barcode']));
                    }

                }
            }else{
                foreach ($this->shipmentItems[0] as $shipmentitem){
                    array_push($missingTracktrace, array('shipmentId' => $data, 'shipmentItemId' => (string)$shipmentitem['barcode']));
                }
            }

            $i=1;

            $senderData = $this->getSenderData();

                try {
                    // xml post structure
                    $xml_post_array = array(
                        'shipmentId' => $data


                    );

                    $xml_post_string = $xml_post_array;


                    // PHP cURL  for https connection with auth
                    $response = wp_remote_post( $this->apiUrl.'/'.$data, array(
                            'method'      => 'GET',
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
//                    $ch = curl_init();
//
//                    curl_setopt_array($ch, array(
//                        CURLOPT_URL => "".$this->apiUrl.'/'.$data."",
//                        CURLOPT_RETURNTRANSFER => true,
//                        CURLOPT_ENCODING => "",
//                        CURLOPT_MAXREDIRS => 10,
//                        CURLOPT_TIMEOUT => 10,
//                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
//                        CURLOPT_CUSTOMREQUEST => "GET",
//                        CURLOPT_HTTPHEADER => array(
//                            "Accept: application/json",
//                            "Authorization: ".$this->tToken." ".$this->aToken,
//                            "Content-Type: application/json",
//                            "cache-control: no-cache",
//                        ),
//                        CURLOPT_SSL_VERIFYPEER => false,
//
//                    ));
//
//                    $response = curl_exec($ch);


                    if(empty($response)){
                        $httpCode = $response["response"]["code"];

                        $this->continue = false;
                        AdminNotice::create()
                            ->error('Api track&trace response is empty')
                            ->show();
                        return;
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
                        //$responseArray[$order['id']] = $this->array_push_assoc($responseArray[$order['id']], 'orderId', $order['id']);
                        //$this->responses[$order['id']] = $responseArray[$order['id']];
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

        public function array_push_assoc($array, $key, $value){
            $array[$key] = $value;
            return $array;
        }

        public function responseHandler(){
            foreach($this->responseMessages as $type => $responses){
                if($type == 'errors'){
                    foreach($responses as $incrementId => $message){
                        AdminNotice::create()
                            ->error($message)
                            ->showOnNextPage();
                        unset($this->responseMessages['errors'][$incrementId]);
                    }
                }
                if(!array_key_exists('success',$this->responseMessages)){
                    $this->continue = false;

                }
            }
            return;
        }


        public function getShipmentItems($shipmentId){
            global $wpdb;
            if(is_array($shipmentId)){
                $shipmentids = implode(',',$shipmentId);
                $shipments = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = 'packs_shipment_batch' AND meta_value IN($shipmentids) ORDER BY post_id DESC", ARRAY_A);

                foreach ($shipments as $shipment ){
                    $shipmentPostId = $shipment['post_id'];

                    $query = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = 'packs_shipment_items' AND post_id = $shipmentPostId", ARRAY_A);

                    foreach ($query as $shipmentItem){
                        $shipmentItem = unserialize($shipmentItem['meta_value']);
                        $shipmentItems[$shipment['meta_value']] = $shipmentItem;
                    }
                }
                //$shipmentPostIds = implode(',',$shipmentPostIds);


            }else{
                $shipmentPostId = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = 'packs_shipment_batch' AND meta_value = '$shipmentId' LIMIT 1", ARRAY_A);
                $shipmentPostId = (int)$shipmentPostId[0]['post_id'];
                $query = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = 'packs_shipment_items' AND post_id = '$shipmentPostId' LIMIT 1", ARRAY_A);

                foreach ($query as $shipmentItem){
                    $shipmentItem = unserialize($shipmentItem['meta_value']);
                    $shipmentItems[] = $shipmentItem;
                }
            }

            return $shipmentItems;
        }

        public function getSenderData(){
            $senderData = array();
            $senderData['handler'] = $this->senderOptions['sender_handler']['default'];
            $senderData['network'] = $this->senderOptions['sender_network']['default'];
            return $senderData;
        }

    }


endif;


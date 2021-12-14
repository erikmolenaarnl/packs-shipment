<?php
namespace PACKS\SHIPMENTS\Admin;
use PACKS\SHIPMENTS\Admin\AdminNotice;
use PACKS\SHIPMENTS\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( !class_exists( '\\PACKS\\SHIPMENTS\\Admin\\Getlabels' ) ) :



    class Getlabels
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
            $this->apiUrl = $this->generalOptions['api_getlabels_url'];
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
//            $ch = curl_init();
//            curl_setopt_array($ch, array(
//                CURLOPT_URL => "".$this->apiAuthUrl."",
//                CURLOPT_RETURNTRANSFER => true,
//                CURLOPT_ENCODING => "",
//                CURLOPT_MAXREDIRS => 10,
//                CURLOPT_TIMEOUT => 2,
//                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
//                CURLOPT_CUSTOMREQUEST => "POST",
//                CURLOPT_POSTFIELDS => "".$postString."",
//                CURLOPT_HTTPHEADER => array(
//                    "Cache-Control: no-cache",
//                    "Content-Type: application/x-www-form-urlencoded",
//                ),
//                CURLOPT_SSL_VERIFYPEER => false,
//            ));
//
//            $result = curl_exec($ch);

            $httpCode = $result["response"]["code"];
//            curl_close($ch);
//            unset($ch);

            $json = json_decode($result['body']);
            $this->tToken = $json->token_type;
            $this->aToken = $json->access_token;


            return;
        }

        public function processData($data)
        {
            $missingLabels = array();
            if(is_array($data)){
                foreach ($this->shipmentItems as $key=>$item){
                    foreach ($item as $shipmentItem){
                        array_push($missingLabels, array('shipmentId' => $key, 'shipmentItemId' => (string)$shipmentItem['shipmentItemId']));
                    }

                }
            }else{
                foreach ($this->shipmentItems[0] as $shipmentitem){
                    array_push($missingLabels, array('shipmentId' => $data, 'shipmentItemId' => (string)$shipmentitem['shipmentItemId']));
                }
            }
            $i=1;

                try {
                    // xml post structure
                    $xml_post_array = array(
                        'mergeLabels' => true,
                        'shipmentItems' =>
                            $missingLabels


                    );

                    $xml_post_string = json_encode($xml_post_array);

                    //file_put_contents(WP_CONTENT_DIR.'/debug.log',print_r($this->apiUrl,1),FILE_APPEND);

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
//                    $ch = curl_init();
//
//                    curl_setopt_array($ch, array(
//                        CURLOPT_URL => "".$this->apiUrl."",
//                        CURLOPT_RETURNTRANSFER => true,
//                        CURLOPT_ENCODING => "",
//                        CURLOPT_MAXREDIRS => 10,
//                        CURLOPT_TIMEOUT => 10,
//                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
//                        CURLOPT_CUSTOMREQUEST => "POST",
//                        CURLOPT_POSTFIELDS => "".$xml_post_string."",
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
//                        $response = curl_error($ch);
                        $httpCode = $response["response"]["code"];


//                        curl_close($ch);
//                        unset($ch);
                        $this->continue = false;
                        AdminNotice::create()
                            ->error('Api packing slip response is empty')
                            ->show();
                        return;
                    }else{

                        $httpCode = $response["response"]["code"];
                        //file_put_contents(WP_CONTENT_DIR.'/debug.log',print_r($httpCode,1),FILE_APPEND);
                        if($httpCode == 404) {
                            $message = "HTTP Error 404. The requested resource is not found. URL";
                            $this->responseMessages['errors'][$i['id']] = $message;
                            return false;
                        }
                        if($httpCode == 400) {
                            $message = wp_remote_retrieve_response_message($response);
                            $this->responseMessages['errors'][$i['id']] = $message.': '. wp_remote_retrieve_body($response) ;
                            $this->continue = false;
                            return false;
                        }
                        if($httpCode == 503) {
                            $message = "HTTP Error 503. The api server is not available";
                            $this->responseMessages['errors'][$i['id']] = $message;
                            $this->continue = false;
                            return false;
                        }
//                        if (curl_errno($ch)) {
//                            $err = curl_error($ch);
//                            curl_close($ch);
//
//                            $message = $err;
//                            $this->responseMessages['errors'][$i['id']] = $message;
//
//                        }

                        $jsonDecoded = json_decode(wp_remote_retrieve_body($response),true);
//                        curl_close($ch);
//                        unset($ch);

                        $this->responses = $jsonDecoded;
                        //$responseArray[$order['id']] = $this->array_push_assoc($responseArray[$order['id']], 'orderId', $order['id']);
                        //$this->responses[$order['id']] = $responseArray[$order['id']];
                    }



                }catch (Exception $e) {
                    AdminNotice::create()
                        ->error('Error occured: '.$e)
                        ->show();
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

    }


endif;


<?php
namespace PACKS\SHIPMENTS\Admin;
use PACKS\SHIPMENTS\Admin\AdminNotice;
use PACKS\SHIPMENTS\Admin\Getproductinfo;
use PACKS\SHIPMENTS\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( !class_exists( '\\PACKS\\SHIPMENTS\\Admin\\Exportshipments' ) ) :



class Exportshipments
{
    protected $apiUser;
    protected $apiUserPassword;
    protected $apiUrl;
    protected $apiAuthUrl;
    protected $orderIds;
    protected $shipments;
    protected $postData;
    protected $_shipmentsData;
    protected $aToken;
    protected $tToken;
    protected $continue;
    protected $orders = array();
    public $responses = array();
    protected $responseMessages = array();
    protected $productoptions;
    protected $settings;

    public function __construct($orderIds)
    {
        $this->Getproductinfo = new Getproductinfo();

        $Getproductinfo = $this->Getproductinfo->init();
        if($Getproductinfo && $this->Getproductinfo->continue == true){
            $this->productoptions = $this->Getproductinfo->responses['products'];
        }else{
            return;
        }
        $this->settings = new Settings();
        $this->orderIds = $orderIds;
        $this->custom();
        if(isset($_POST['order_ids'])){
            $this->postData = $_POST;
        }
    }

    public function custom()
    {

        include('views/packs-createshipments-page.php');

        if(isset($_POST['order_ids'])){
            $this->postData = $_POST;
            $this->preprocessData($this->postData);


                $this->responseHandler();

        }
    }

    public function preprocessData($data)
    {
        $this->senderOptions = get_option('packs_shipments_settings_sender');
        $this->generalOptions = $this->settings->get_common_general_settings();
        $this->apiUser = $this->generalOptions['api_user_name']['default'];
        $this->apiUserPassword = $this->generalOptions['api_user_password']['default'];
        $this->apiUrl = $this->generalOptions['api_url'];
        $this->apiAuthUrl = $this->generalOptions['api_auth_url'];

        set_time_limit(720000);                 // script timeout: 15 mins.
        ob_start();

        $this->Authorize();
        $this->processData($this->postData);
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

            $httpCode = $result["response"]["code"];

            $json = json_decode($result['body']);
            $this->tToken = $json->token_type;
            $this->aToken = $json->access_token;


        return;
    }

    public function processData($data)
    {
        $senderData = $this->getSenderData();
        $orders = $this->getWooOrderdata($this->orderIds);

        foreach($orders as $order){
            if(isset($order['shipping']['company'][0])){
                $shipping_name = $order['shipping']['company'];
            }else{
                $shipping_name = $order['shipping']['first_name'].' ' . $order['shipping']['last_name'];
                $company = false;
            }
            $orderData['shippingaddress']['street1'] = $order['shipping']['address_1'];
            if($order['shipping']['address_1'] && $order['shipping']['address_2']){
                $tmp = $order['shipping']['address_1'];
                list($orderData['shippingaddress']['street1'],
                    $orderData['shippingaddress']['street2'])
                    = $this->_explodeAddress($tmp);
                /* If 'street2' is empty then copy the value of 'address_2' to 'street2'.  */
                if( ''===$orderData['shippingaddress']['street2'] ) {
                    $orderData['shippingaddress']['street2'] = $order['shipping']['address_2'];
                } else {
                    $orderData['shippingaddress']['street3'] = $order['shipping']['address_2'];
                }
            }else{
                $tmp = $order['shipping']['address_1'];
                list($orderData['shippingaddress']['street1'],
                    $orderData['shippingaddress']['street2'])
                    = $this->_explodeAddress($tmp);
                $orderData['shippingaddress']['street3'] = '';
            }

            try {
                // xml post structure
                $xml_post_array = array(
                    'handler'=> $senderData['handler'],
                    'network'=> $senderData['network'],
                    'loadDate'=> $this->getLoadDate($order),
                    'deliveryDate'=> $this->getDeliveryDate($order),
                    'loadAddress'=> array(
                        'country'=> $senderData['country'],
                        'name'=> $senderData['company'],
                        'nameTo'=>$senderData['name'],
                        'street'=> $senderData['street'],
                        'number'=> $senderData['housenumber'],
                        'numberExt'=> $senderData['numberext'],
                        'location'=> '',
                        'zip'=> $senderData['postcode'],
                        'place'=> $senderData['city'],
                        'reference'=> $senderData['reference'],
                        'mail' => $senderData['mail']
                    ),
                    'deliveryAddress'=> array(
                        'country'=> $order['shipping']['country'],
                        'name'=> $shipping_name,
                        'nameTo'=>$order['shipping']['first_name'].' ' . $order['shipping']['last_name'],
                        'street'=> $orderData['shippingaddress']['street1'],
                        'number'=> $orderData['shippingaddress']['street2'],
                        'numberExt'=> $orderData['shippingaddress']['street3'],
                        'zip'=> $order['shipping']['postcode'],
                        'place'=> $order['shipping']['city'],
                        'reference'=> $this->getReferentie($order),
                        'mail' => $order['billing']['email'],
                        'phone' => $order['billing']['phone']
                    ),
                    'surcharges'=> array(),
                    'shipmentItems' => $this->getShipmentItems($order),
                );
                if($xml_post_array['deliveryAddress']['name'] == $xml_post_array['deliveryAddress']['nameTo']){
                    unset($xml_post_array['deliveryAddress']['nameTo']);
                }

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

//                $ch = curl_init();
//
//                curl_setopt_array($ch, array(
//                    CURLOPT_URL => "".$this->apiUrl."",
//                    CURLOPT_RETURNTRANSFER => true,
//                    CURLOPT_ENCODING => "",
//                    CURLOPT_MAXREDIRS => 10,
//                    CURLOPT_TIMEOUT => 10,
//                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
//                    CURLOPT_CUSTOMREQUEST => "POST",
//                    CURLOPT_POSTFIELDS => "".$xml_post_string."",
//                    CURLOPT_HTTPHEADER => array(
//                        "Accept: application/json",
//                        "Authorization: ".$this->tToken." ".$this->aToken,
//                        "Content-Type: application/json",
//                        "cache-control: no-cache",
//                    ),
//                    CURLOPT_SSL_VERIFYPEER => false,
//
//                ));
//
//                $response = curl_exec($ch);


                if(empty($response)){

                    $httpCode = $response["response"]["code"];
                    curl_close($ch);
                    unset($ch);
                    $this->continue = false;
                    AdminNotice::create()
                        ->error('Api shipments response is empty')
                        ->show();

                }else{

                    $httpCode = $response["response"]["code"];
                    if($httpCode == 404) {
                        $message = "HTTP Error 404. The requested resource is not found. URL";
                        $this->responseMessages['errors'][$order['id']] = $message;
                        return;
                    }
                    if($httpCode == 400) {
                        $message = wp_remote_retrieve_response_message($response);
                        $this->responseMessages['errors'][$order['id']] = $message.': '. wp_remote_retrieve_body($response) ;
                        $this->continue = false;
                        return;
                    }
                    if($httpCode == 503) {
                        $message = "HTTP Error 503. The api server is not available";
                        $this->responseMessages['errors'][$order['id']] = $message;
                        $this->continue = false;
                        return;
                    }

                    $jsonDecoded = json_decode(wp_remote_retrieve_body($response),true);


                    $responseArray[$order['id']] = $jsonDecoded;
                    $responseArray[$order['id']] = $this->array_push_assoc($responseArray[$order['id']], 'orderId', $order['id']);
                    $this->responses[$order['id']] = $responseArray[$order['id']];
                }



            }catch (Exception $e) {
                return false;
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
                        ->show();
                    unset($this->responseMessages['errors'][$incrementId]);
                }
            }
            if(!array_key_exists('success',$this->responseMessages)){
                $this->continue = false;

            }
        }
        return;
    }



    public function getWooOrderdata($orderids)
    {
        $orderids = explode(',',$orderids);
        foreach ($orderids as $orderid){
            $order = wc_get_order( $orderid );
            $order_data = $order->get_data();
            $this->orders[] = $order_data;
        }
        return $this->orders;

    }

    public function getShipmentItems($order){
        $shipmentItems = array();
        $colliQty = $this->getCollie($order);

        for($i=0; $i<$colliQty; $i++){
            $surcharge = $this->getShipmentItemSurcharges($order);
            if($surcharge){
                $shipmentItem = array(
                    'product' => $this->getSeal($order),
                    'weight' => $this->getTotalWeight($order,$colliQty),
                    'length'=> 1,
                    'height'=> 1,
                    'width'=> 1,
                    'labelText'=> 'collo'.$i,
                    'surcharges'=> array($surcharge)
                );
            }else{
                $shipmentItem = array(
                    'product' => $this->getSeal($order),
                    'weight' => $this->getTotalWeight($order,$colliQty),
                    'length'=> 1,
                    'height'=> 1,
                    'width'=> 1,
                    'labelText'=> 'collo'.$i,
                );
            }
            array_push($shipmentItems,$shipmentItem);
        }

        return $shipmentItems;
    }

    private function getShipmentItemSurcharges($order){
        if($this->getAllowance($order)) {
            $surcharge = array('surcharge'=> (string)$this->getAllowance($order));
        }else{
            $surcharge = array();
        }
        return $surcharge;
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
        $senderData['numberext'] = $this->senderOptions['sender_numberext']['default'];
        $senderData['name'] = $this->senderOptions['sender_name']['default'];
        $senderData['company'] = $this->senderOptions['sender_company']['default'];
        $senderData['mail'] = $this->senderOptions['sender_mail']['default'];
        $senderData['reference'] = $this->senderOptions['sender_reference']['default'];

        return $senderData;
    }

    public function getSeal($order){
        $seal = $this->postData[$order['id'].'-seal'];
        return $seal;
    }

    public function getLoadDate($order){
        $loadDate = $this->postData[$order['id'].'-loaddate'];
        return $loadDate;
    }

    public function getDeliveryDate($order){
        $deliveryDate = $this->postData[$order['id'].'-deliverydate'];
        return $deliveryDate;

    }

    public function getReferentie($order){
        $reference = $this->postData[$order['id'].'-reference'];
        return $reference;
    }

    public function getCollie($order){
        $collie = $this->postData[$order['id'].'-collie'];
        return $collie;
    }

    public function getTotalWeight($order,$colliQty){
        $weight_unit = get_option('woocommerce_weight_unit');
        if($weight_unit !== 'kg'){
            $weight = (float)$this->postData[$order['id'].'-weight'];
            $weight = (float)$weight / 1000;
        }else{
            $weight = (float)$this->postData[$order['id'].'-weight'];
        }

        $weightclass = array('2kg', '5kg', '10kg', '20kg', '30kg', '35kg');
        switch($weight) {
            case in_array($weight, range(0,2)):
                $weight = 2;
                break;
            case in_array($weight, range(2,5)):
                $weight = 5;
                break;
            case in_array($weight, range(5,10)):
                $weight = 10;
                break;
            case in_array($weight, range(10,20)):
                $weight = 20;
                break;
            case in_array($weight, range(20,30)):
                $weight = 30;
                break;
            case in_array($weight, range(30,35)):
                $weight = 35;
                break;
        }
        return $weight;
    }

    public function getAllowance($order){
        if(isset($htis->postData[$order['id'].'-allowance'])){
            $allowance = $this->postData[$order['id'].'-allowance'];
            return $allowance;
        }else{
            return false;
        }
    }


    /*
    ** Explodes a given address to
    ** a streenname and streetnumber.
    **
    ** For example:
    ** $result = explodeAddress('streetname 123');
    ** ['streetname', '123']
    ** Or if no steetnumber given:
    ** $result = explodeAddress('streetname');
    ** ['streetname', '']
    ** $result = explodeAddress('streetname streetname');
    ** ['streetname streetname', '']
    */
    private function _explodeAddress( $_input ){

        $regex = '/^((?:[^\s]+\s+)+)([^\s]+)$/';
        preg_match( $regex, $_input, $match );
        if( !$match ) {
            return [$_input, ''];
        }

        list(, $street, $streetnr) = $match;
        $street = trim($street);
        $streetnr = trim($streetnr);

        /* Fixing test cases:
        ** - "De Dompelaar 1 B"
        ** - "Saturnusstraat 60 - 75" */
        if( preg_match('/[0-9]+[\-\s]*$/', $street, $match)
            /* If $street not ends with a number
            ** and $streetnr not begins with a number. */
            && !(preg_match('/[0-9]$/', $street)
                && preg_match('/^[0-9]/', $streetnr)) ) {
            $n = strlen($street) - strlen($match[0]);
            if( $n >= 1 ) {
                $street = substr($street, 0, $n);
                $streetnr = $match[0] . ' ' . $streetnr;
            }
        }
        /* Fixing test cases:
        ** - "glaslaan 2, gebouw SWA 71"
        ** - "straat 32 verdieping 2" */
        else if( preg_match('/[^0-9]([0-9]+[^0-9]+)$/', $street, $match) ){
            $n = strlen($street) - strlen($match[1]);
            if( $n >= 1 ) {
                $street = substr($street, 0, $n);
                $streetnr = $match[1] . ' ' . $streetnr;
            }
        }
        /* Fixing test cases:
        ** - "1, rue de l'eglise" */
        else if( preg_match('/^([0-9]+\s*),([\s\S]+)/', $_input, $match) ) {
            $street = $match[2];
            $streetnr = $match[1];
        }
        /* Fixing test cases:
        ** - "3-koningenstraat, 21 13b" */
        else if( preg_match('/,\s*([0-9]+)$/', $street, $match) ) {
            $n = strlen($street) - strlen($match[1]);
            if( $n >= 1 ) {
                $street = substr($street, 0, $n);
                $streetnr = $match[1] . ' ' . $streetnr;
            }
        }

        /* If street number contains no number then
        ** "$street = $_input" and streetnr is empty. */
        if( !preg_match('/[0-9]/', $streetnr) ) {
            $street = $_input;
            $streetnr = '';
        }

        $street = rtrim(trim($street), ',');
        $streetnr = trim($streetnr);
        return [$street, $streetnr];

    } /* end function explodeAddress() */
}

endif;


<?php
/**
 * Created by PhpStorm.
 * User: stephanbijma
 * Date: 2019-01-08
 * Time: 14:24
 */

namespace PACKS\SHIPMENTS;

use PACKS\SHIPMENTS\Admin\Exportshipments;
use WP_Mail;
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( !class_exists( '\\PACKS\\SHIPMENTS\\Createshipmentpage' ) ) :


    class Createshipmentpage
    {
        public function __construct()
        {

        }

        public function init()
        {
            add_action('admin_menu', array($this, 'register_editpage'));
            add_action('admin_enqueue_scripts', array($this,'enqueue_scripts'));
        }

        function enqueue_scripts(){
            $jquery_path = 'https://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js';
            //$jquery_ui = 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js';
            wp_register_script('cdnjquery', $jquery_path);
            //wp_register_script('cdnjqueryui', $jquery_ui);
            //wp_enqueue_script('cdnjquery');
            wp_enqueue_script('cdnjqueryui');
        }

        function register_editpage(){
            add_menu_page('Create Shipments', 'createshipments', 'manage_woocommerce','createshipments', array( $this, 'createshipments' ));
            remove_menu_page('createshipments');


        }

        function createshipments(){
            if (isset($_REQUEST['bulk_action']) && isset($_REQUEST['order_ids'])) {
            $orderIds = $_REQUEST['order_ids'];
            $export = new Exportshipments($orderIds);
            $createshipments = $this->createshipment($export);
            if($createshipments){
                $sendback = admin_url( "edit.php?post_type=packs_shipment" );
                //return $export;

                wp_redirect($sendback);
                exit();
            }




            }
            return;
        }

        function createshipment($export){
            $i=0;
            foreach ($export->responses as $shipment){
                $factory = new Shipment_Factory();
                $factory->set_title('order-'.$shipment['orderId'].'-batch-'.$shipment['shipmentId'].'-'.(isset($shipment['deliveryAddress']['name']) ? $shipment['deliveryAddress']['name'] : $shipment['deliveryAddress']['nameTo']) );
                $factory->set_content($shipment['shipmentId']);
                $factory->create();

                // $metabox = new Admin_Meta_Boxes();
                // $nonce = wp_nonce_field( 'packs_save_data', 'packs_meta_nonce' );
                //$metabox->packs_save_meta_batch($factory->shipment_current_post_id,$shipment['batch'],$nonce);
                if (($key = array_search('labelObject', $shipment)) !== false) {
                    unset($shipment[$key]);
                }
                update_post_meta( $factory->shipment_current_post_id, 'packs_shipment_order_id', $shipment['orderId'] );
                update_post_meta( $factory->shipment_current_post_id, 'packs_shipment_status', $shipment['status'] );
                update_post_meta( $factory->shipment_current_post_id, 'packs_shipment_batch', $shipment['batch'] );
                update_post_meta( $factory->shipment_current_post_id, 'packs_shipment_handler', $shipment['handler'] );
                update_post_meta( $factory->shipment_current_post_id, 'packs_shipment_network', $shipment['network'] );
                update_post_meta( $factory->shipment_current_post_id, 'packs_shipment_data', $shipment['deliveryAddress'] );
                update_post_meta( $factory->shipment_current_post_id, 'packs_shipment_items', $shipment['shipmentItems'] );
                update_post_meta( $shipment['orderId'], 'packs_shipment_shipmentid', $shipment['shipmentId'] );
                //$email = new Packs_Shipment_Email($shipment);

                /* Disable email template //

                $customeremail = wc_get_order($shipment['orderId'])->get_billing_email();
                $template = PACKS_SHIPMENTS()->plugin_path().'/templates/emails/packs.php';
                $shipmentsubject = sprintf(__('Your order %s has been send'),$shipment['orderId']);
                $email = WP_Mail::init()
                    ->to($customeremail)
                    ->subject($shipmentsubject)
                    ->template($template, [
                        'name' => $shipment['deliveryAddress']['name'],
                        'street' => $shipment['deliveryAddress']['street'],
                        'number' => $shipment['deliveryAddress']['number'],
                        'zip' => $shipment['deliveryAddress']['zip'],
                        'place' => $shipment['deliveryAddress']['place'],
                    ])
                    ->send();
                */
                $i++;
            }
            if($i>0){
                return true;
            }else{
                return false;
            }

        }

        function packs_redirect(){
            wp_redirect( admin_url( '/edit.php?post_type=packs_shipment' ), 302 );
        }
    }
endif;

//return new Createshipmentpage();
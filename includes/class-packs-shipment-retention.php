<?php

namespace PACKS\SHIPMENTS;


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( !class_exists( '\\PACKS\SHIPMENTS\\Shipment_Retention' ) ) :

    class Shipment_Retention
    {

        public function __construct()
        {
            $this->generalOptions = get_option('packs_shipments_settings_general');
        }

        public function init(){
            global $post;
            global $wpdb;
            $retention = $this->generalOptions['api_data_retention'];
            if($retention && $retention["default"] !== 0){

                //First delete meta data
                $query = $wpdb->prepare("SELECT `ID` FROM $wpdb->posts WHERE `post_type` = %s AND `post_date` < DATE_SUB(NOW(), INTERVAL %d DAY)",'packs_shipment',$retention['default']);
                $postids_to_be_deleted = $wpdb->get_results($query);
                foreach($postids_to_be_deleted as $postId){
                    wp_delete_post($postId->ID,true);
                }
                //$query = $wpdb->prepare("DELETE FROM $wpdb->posts WHERE `post_type` = %s AND `post_date` < DATE_SUB(NOW(), INTERVAL %d DAY)",'packs_shipment',$retention['default']);
                //$wpdb->query($query);
            }
        }
    }

endif;

return new Shipment_Retention();
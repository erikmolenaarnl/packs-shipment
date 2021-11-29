<?php
/**
 * Created by PhpStorm.
 * User: stephanbijma
 * Date: 22-08-18
 * Time: 14:56
 */
namespace PACKS\SHIPMENTS;


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( !class_exists( '\\PACKS\SHIPMENTS\\Customposttype' ) ) :
class Customposttype{

    public $post_type_name;
    public $post_type_args;
    public $post_type_labels;


    public function __construct( $name, $args = array(), $labels = array() )
    {
        // Set some important variables
        $this->post_type_name        = strtolower( str_replace( ' ', '_', $name ) );
        $this->post_type_args        = $args;
        $this->post_type_labels  = $labels;

        // Add action to register the post type, if the post type does not already exist
        if( ! post_type_exists( $this->post_type_name ) )
        {
            add_action( 'init', array( &$this, 'register_post_type' ) );
        }

    }

    public function register_post_type() {
        /**
         * Post Type: Shipments.
         */
        $labels = array_merge(

            array(
                "name" => __( "Shipments", "packs-shipments" ),
                "singular_name" => __( "Shipment", "packs-shipments" ),
                "menu_name" => __( "Shipments", "packs-shipments" ),
                "all_items" => __( "Shipments", "packs-shipments" ),
                "add_new" => __( "Add New", "packs-shipments" ),
                "add_new_item" => __( "Add New Shipment", "packs-shipments" ),
                "edit_item" => __( "Edit Shipment", "packs-shipments" ),
                "new_item" => __( "New Shipment", "packs-shipments" ),
                "view_item" => __( "View Shipment", "packs-shipments" ),
                "view_items" => __( "View Shipments", "packs-shipments" ),
                "search_items" => __( "Search Shipment", "packs-shipments" ),
                "not_found" => __( "No Shipments found", "packs-shipments" ),
                "not_found_in_trash" => __( "No Shipments found in trash", "packs-shipments" ),
                "parent_item_colon" => __( "Parent Shipment", "packs-shipments" ),
                "featured_image" => __( "Featured Image", "packs-shipments" ),
                "set_featured_image" => __( "Set Featured Image", "packs-shipments" ),
                "remove_featured_image" => __( "Remove Featured Image", "packs-shipments" ),
                "use_featured_image" => __( "Use as featured image", "packs-shipments" ),
                "archives" => __( "Shipment Archives", "packs-shipments" ),
                "insert_into_item" => __( "Insert into Shipment", "packs-shipments" ),
                "uploaded_to_this_item" => __( "Uploaded to this Shipment", "packs-shipments" ),
                "filter_items_list" => __( "Filter Shipment list", "packs-shipments" ),
                "items_list_navigation" => __( "Shipment list navigation", "packs-shipments" ),
                "items_list" => __( "Shipments list", "packs-shipments" ),
                "attributes" => __( "Shipment attributes", "packs-shipments" ),
            ),

            $this->post_type_labels
        );

        $args = array_merge(

            array(
                "label" => __( "Shipments", "packs" ),
                "labels" => $labels,
                "description" => "Packs shipments for Woocommerce Orders",
                "public" => false,
                "publicly_queryable" => false,
                "show_ui" => true,
                "show_in_rest" => true,
                "rest_base" => "shipments",
                "has_archive" => false,
                "show_in_menu" => 'woocommerce',
                "show_in_nav_menus" => true,
                "exclude_from_search" => true,
                "capability_type" => "post",
                "map_meta_cap" => true,
                "hierarchical" => false,
                "rewrite" => array( "slug" => "shipments", "with_front" => true ),
                "query_var" => false,
                "supports" => array( "title", "custom-fields" ),
            ),

            $this->post_type_args
        );

        register_post_type( $this->post_type_name, $args );
        do_action( 'packs_after_register_post_type' );
    }

}
endif; // class_exists

return new Customposttype('packs_shipment');
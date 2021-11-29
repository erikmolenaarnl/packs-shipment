<?php

namespace PACKS\SHIPMENTS;

use PACKS\SHIPMENTS\Admin\Admin_Meta_Boxes;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( !class_exists( '\\PACKS\SHIPMENTS\\Shipment_Factory' ) ) :

    class Shipment_Factory
    {
    // Variables for Post Data
        public $shipment_title;
        public $shipment_type;
        public $shipment_content;
        public $shipment_category;
        public $shipment_template;
        public $shipment_slug;
        public $shipment_auth_id;
        public $shipment_status = "publish";

        // Variables for Post Updating
        public $shipment_current_post;
        public $shipment_current_post_id;
        public $shipment_current_post_permalink;

        // Error Array
        public $shipment_errors;   
        // Creation functions
        public function create() {
            if(isset($this->shipment_title) ) {

                $error_obj = NULL;
                $post_data = array(
                    'post_title'    => wp_strip_all_tags($this->shipment_title),
                    'post_name'     => $this->shipment_slug,
                    'post_content'  => $this->shipment_content,
                    'post_status'   => $this->shipment_status,
                    'post_type'     => 'packs_shipment',
                    'post_author'   => $this->shipment_auth_id,
                );
                if(!isset($post)){
                    $this->shipment_current_post_id = wp_insert_post( $post_data, $error_obj );
                    $this->shipment_current_post = get_post((integer)$this->shipment_current_post_id, 'OBJECT');
                    $this->shipment_current_post_permalink = get_permalink((integer)$this->shipment_current_post_id);

                    return $error_obj;
                }
                else {
                    $this->update();
                    $this->errors[] = 'That page already exists. Try updating instead. Control passed to the update() function.';
                    return FALSE;
                }
            }
            else {
                $this->errors[] = 'Title has not been set.';
                return FALSE;
            }
        }

        // SET POST'S TITLE	
        public function set_title($name){
            $this->shipment_title = $name;
            return $this->shipment_title;
        }

        // SET POST'S TYPE	
        public function set_type($type){
            $this->shipment_type = $type;
            return $this->shipment_type;
        }

        // SET POST'S CONTENT	
        public function set_content($content){
            $this->shipment_content = $content;
            return $this->shipment_content;
        }

        // SET POST'S AUTHOR ID	
        public function set_author_id($auth_id){
            $this->shipment_auth_id = $auth_id;
            return $this->shipment_auth_id;
        }

        // SET POST'S STATE	
        public function set_post_state($content){
            $this->shipment_status = $content;
            return $this->shipment_status;
        }

        // SET POST SLUG
        public function set_post_slug($slug){
            $args = array('name' => $slug);
            $posts_query = get_posts( $args );
            if( !get_posts( $args ) && !get_page_by_path( $this->shipment_slug ) ) {
                $this->shipment_slug = $slug;
                return $this->shipment_slug;
            }
            else {
                $this->errors[] = 'Slug already in use.';
                return FALSE;
            }
        }

        // SET PAGE TEMPLATE
        public function set_page_template($content){
            if ($this->shipment_type == "page") {
                $this->shipment_template = $content;
                return $this->shipment_template;
            }
            else {
                $this->errors[] = 'You can only use template for pages.';
                return FALSE;
            }
        }

        // Update Post
        public function update(){
            if (isset($this->shipment_current_post_id)) {

                // Declare ID of Post to be updated
                $shipment_post['ID'] = $this->shipment_current_post_id;

                // Declare ID of Post to be updated
                if (isset($this->shipment_title) && $this->shipment_title !== $this->shipment_current_post->post_title)
                    $shipment_post['post_title'] = $this->shipment_title;

                if (isset($this->shipment_type) && $this->shipment_type !== $this->shipment_current_post->post_type)
                    $shipment_post['post_type'] = $this->shipment_type;

                if (isset($this->shipment_auth_id) && $this->shipment_auth_id !== $this->shipment_current_post->post_type)
                    $shipment_post['post_author'] = $this->shipment_auth_id;

                if (isset($this->shipment_status) && $this->shipment_status !== $this->shipment_current_post->post_status)
                    $shipment_post['post_status'] = $this->shipment_status;

                if (isset($this->shipment_slug) && $this->shipment_slug !== $this->shipment_current_post->post_name) {
                    $args = array('name' => $this->shipment_slug);
                    if( !get_posts( $args ) && !get_page_by_path( $this->shipment_slug ) )
                        $shipment_post['post_name'] = $this->shipment_slug;
                    else
                        $errors[] = 'Slug Defined is Not Unique';
                }

                if (isset($this->shipment_content) && $this->shipment_content !== $this->shipment_status->post_content )
                    $shipment_post['post_content'] = $this->shipment_content;

                wp_update_post( $shipment_post );
            }
            return($errors);
        }

    /**
     * Get shipment.
     *
     * @param  mixed $shipment_id (default: false) Shipment ID to get.
     * @return Shipment|bool
     */
	public static function get_shipment( $shipment_id = false )
    {
        $shipment_id = self::get_shipment_id( $shipment_id );

        if ( ! $shipment_id ) {
            return false;
        }

        // Filter classname so that the class can be overridden if extended.
        $classname = apply_filters( 'packs_shipment_class', $classname, $shipment_id );

        if ( ! class_exists( $classname ) ) {
            return false;
        }

        try {
            return new $classname( $shipment_id );
        } catch ( Exception $e ) {

            return false;
        }
	}

	/**
     * Get the order ID depending on what was passed.
     *
     * @since 1.0.0
     * @param  mixed $order Order data to convert to an ID.
     * @return int|bool false on failure
     */
	public static function get_shipment_id( $shipment )
    {
        global $post;

        if ( false === $shipment && is_a( $post, 'WP_Post' ) && 'packs_shipment' === get_post_type( $post ) ) {
            return absint( $post->ID );
        } elseif ( is_numeric( $shipment ) ) {
            return $shipment;
        } elseif ( $shipment instanceof Abstract_Shipment ) {
            return $shipment->get_id();
        } elseif ( ! empty( $shipment->ID ) ) {
            return $shipment->ID;
        } else {
            return false;
        }
    }

}

    endif;

//return new Shipment_Factory();
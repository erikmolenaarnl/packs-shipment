<?php
namespace PACKS\SHIPMENTS\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( !class_exists( '\\PACKS\\SHIPMENTS\\Admin\\Assets' ) ) :

    class Assets
    {
        public function __construct() {
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        }

        public function packs_get_screen_ids(){
            $screens = array(
                'toplevel_page_createshipments',
            );

            return $screens;
        }

        public function admin_styles()
        {
            global $wp_scripts;

            $screen = get_current_screen();
            $screen_id = $screen ? $screen->id : '';

            // Register admin styles.

            wp_register_style( 'jquery-ui-style', PACKS_SHIPMENTS()->plugin_url() . '/assets/css/jquery-ui/jquery-ui.min.css', array(), PACKS_SHIPMENTS_VERSION );

            // Admin styles for WC pages only.
            if ( in_array( $screen_id, $this->packs_get_screen_ids() ) ) {
                wp_enqueue_style( 'jquery-ui-style' );
            }

        }

        public function admin_scripts()
        {
            global $wp_query, $post;

            $screen = get_current_screen();
            $screen_id = $screen ? $screen->id : '';
            $wc_screen_id = sanitize_title(__('WooCommerce', 'woocommerce'));
            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

            wp_register_script( 'ui-datapicker', PACKS_SHIPMENTS()->plugin_url() . '/assets/js/jquery/ui/datepicker' . $suffix . '.js', array( 'jquery' ), '2.70', false );

            // WooCommerce admin pages.
            if ( in_array( $screen_id, $this->packs_get_screen_ids() ) ) {
                wp_enqueue_script('jquery');
                wp_enqueue_script('ui-datapicker');

            }
        }
    }

return new Assets();
endif;

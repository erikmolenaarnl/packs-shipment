<?php
namespace PACKS\SHIPMENTS;


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( !class_exists( '\\PACKS\\SHIPMENTS\\Settings' ) ) :

    class Settings {
        public $options_page_hook;

        function __construct()	{
            $this->callbacks = include( 'class-packs-settings-callbacks.php' );

            // include settings classes
            $this->general = include_once( 'class-packs-settings-general.php' );
            $this->sender = include_once( 'class-packs-settings-sender.php' );
            //$this->status = include( 'class-packs-settings-status.php' );


            // Settings menu item
            add_action( 'admin_menu', array( $this, 'menu' ) ); // Add menu.
            // Links on plugin page
            add_filter( 'plugin_action_links_'.PACKS_SHIPMENTS()->plugin_basename, array( $this, 'add_settings_link' ) );
            add_filter( 'plugin_row_meta', array( $this, 'add_support_links' ), 10, 2 );

            // settings capabilities
            add_filter( 'option_page_capability_packs_shipments_general_settings', array( $this, 'settings_capabilities' ) );

            $this->general_settings		= get_option('packs_shipments_settings_general');
            $this->sender_settings		= get_option('packs_shipments_settings_sender');


        }

        public function menu() {
            $parent_slug = 'woocommerce';

            $this->options_page_hook = add_submenu_page(
                $parent_slug,
                __( 'Packs Shipments Settings', 'packs-shipments' ),
                __( 'Packs Shipments Settings', 'packs-shipments' ),
                'manage_woocommerce',
                'packs_shipments_options_page',
                array( $this, 'settings_page' )
            );
        }

        /**
         * Add settings link to plugins page
         */
        public function add_settings_link( $links ) {
            $action_links = array(
                'settings' => '<a href="admin.php?page=packs_shipments_options_page">'. __( 'Settings', 'packs-shipments' ) . '</a>',
            );

            return array_merge( $action_links, $links );
        }

        /**
         * Add various support links to plugin page
         * after meta (version, authors, site)
         */
        public function add_support_links( $links, $file ) {
            if ( $file == PACKS_SHIPMENTS()->plugin_basename ) {
                $row_meta = array(
                    'docs'    => '<a href="https://www.wiseconn.nl/packs-extensie/" target="_blank" title="' . __( 'Documentation', 'packs-shipments' ) . '">' . __( 'Documentation', 'packs-shipments' ) . '</a>',
                    'support' => '<a href="https://www.wiseconn.nl/contact/" target="_blank" title="' . __( 'Contact', 'packs-shipments' ) . '">' . __( 'Contact', 'packs-shipments' ) . '</a>',
                );

                return array_merge( $links, $row_meta );
            }
            return (array) $links;
        }


        public function settings_page() {
            $settings_tabs = apply_filters( 'packs_shipments_settings_tabs', array (
                    'general'	=> __('General', 'packs-shipments' ),
                    'sender'	=> __('Sender', 'packs-shipments' ),
                )
            );

            // add status tab last in row
            //$settings_tabs['debug'] = __('Status', 'packs-shipments' );

            $active_tab = isset( $_GET[ 'tab' ] ) ? sanitize_text_field( $_GET[ 'tab' ] ) : 'general';
            $active_section = isset( $_GET[ 'section' ] ) ? sanitize_text_field( $_GET[ 'section' ] ) : '';

            include('views/packs-settings-page.php');
        }

        public function add_settings_fields( $settings_fields, $page, $option_group, $option_name ) {
            foreach ( $settings_fields as $settings_field ) {
                if (!isset($settings_field['callback'])) {
                    continue;
                } elseif ( is_callable( array( $this->callbacks, $settings_field['callback'] ) ) ) {
                    $callback = array( $this->callbacks, $settings_field['callback'] );
                } elseif ( is_callable( $settings_field['callback'] ) ) {
                    $callback = $settings_field['callback'];
                } else {
                    continue;
                }

                if ( $settings_field['type'] == 'section' ) {
                    add_settings_section(
                        $settings_field['id'],
                        $settings_field['title'],
                        $callback,
                        $page
                    );
                } else {
                    add_settings_field(
                        $settings_field['id'],
                        $settings_field['title'],
                        $callback,
                        $page,
                        $settings_field['section'],
                        $settings_field['args']
                    );
                    // register option separately for singular options
                    if (is_string($settings_field['callback']) && $settings_field['callback'] == 'singular_text_element') {
                        register_setting( $option_group, $settings_field['args']['option_name'], array( $this->callbacks, 'validate' ) );
                    }
                }
            }
            // $page, $option_group & $option_name are all the same...
            register_setting( $option_group, $option_name, array( $this->callbacks, 'validate' ) );
            add_filter( 'option_page_capability_'.$page, array( $this, 'settings_capabilities' ) );

        }

        /**
         * Set capability for settings page
         */
        public function settings_capabilities() {
            return 'manage_woocommerce';
        }

        public function get_common_general_settings() {
            $bookShipments = ( $this->general_settings['api_test_live'] == 'test') ? 'https://packsonlineapp-tst.packs.nl/api/Shipments/BookShipment' : 'https://packsonlineapp.packs.nl/api/Shipments/BookShipment';
            $getLabels = ( $this->general_settings['api_test_live'] == 'test') ? 'https://packsonlineapp-tst.packs.nl/api/Shipments/GetLabels' : 'https://packsonlineapp.packs.nl/api/Shipments/GetLabels';
            $getProductinfo = ( $this->general_settings['api_test_live'] == 'test') ? 'https://packsonlineapp-tst.packs.nl/api/Products/GetProductInfo' : 'https://packsonlineapp.packs.nl/api/Products/GetProductInfo';
            $getTrackTrace = ( $this->general_settings['api_test_live'] == 'test') ? 'https://packsonlineapp-tst.packs.nl/api/Shipments/GetShipment' : 'https://packsonlineapp.packs.nl/api/Shipments/GetShipment';
            $authUrl = ( $this->general_settings['api_test_live'] == 'test') ? 'https://identityserver-tst.packs.nl/connect/token' : 'https://identityserver.packs.nl/connect/token';

            $common_settings = array(
                'api_url'		            => $bookShipments,
                'api_getlabels_url'         => $getLabels,
                'api_getproductinfo_url'    => $getProductinfo,
                'api_gettracktrace_url'     => $getTrackTrace,
                'api_user_password'         => isset( $this->general_settings['api_user_password'] ) ? $this->general_settings['api_user_password'] : '',
                'api_user_name'	            => isset( $this->general_settings['api_user_name'] ) ? $this->general_settings['api_user_name'] : '',
                'api_auth_url'	            => $authUrl,
            );
            return $common_settings;
        }

        public function get_sender_settings() {
            $sender_settings = array(
                'sender_handler'		    => isset( $this->sender_settings['sender_handler'] ) ? $this->sender_settings['sender_handler'] : '',
                'sender_name'		    => isset( $this->sender_settings['sender_name'] ) ? $this->sender_settings['sender_name'] : '',
                'sender_contact' => isset( $this->sender_settings['sender_contact'] ) ? $this->sender_settings['sender_contact'] : '',
                'sender_housenumber'	    => isset( $this->sender_settings['sender_housenumber'] ) ? $this->sender_settings['sender_housenumber'] : '',
                'sender_street'		    => isset( $this->sender_settings['sender_street'] ) ? $this->sender_settings['sender_street'] : '',
                'sender_zip' => isset( $this->sender_settings['sender_zip'] ) ? $this->sender_settings['sender_zip'] : '',
                'sender_city'	    => isset( $this->sender_settings['sender_city'] ) ? $this->sender_settings['sender_city'] : '',
                'sender_country'	    => isset( $this->sender_settings['sender_country'] ) ? $this->sender_settings['sender_country'] : '',
                'sender_default_seal' => isset( $this->sender_settings['sender_default_seal'] ) ? $this->sender_settings['sender_default_seal'] : '',
            );
            return $sender_settings;
        }

        public function get_output_format() {
            if ( isset( $this->debug_settings['html_output'] ) ) {
                $output_format = 'html';
            } else {
                $output_format = 'pdf';
            }
            return $output_format;
        }

        public function get_output_mode() {
            if ( isset( PACKS_SHIPMENTS()->settings->general_settings['download_display'] ) ) {
                switch ( PACKS_SHIPMENTS()->settings->general_settings['download_display'] ) {
                    case 'display':
                        $output_mode = 'inline';
                        break;
                    case 'download':
                    default:
                        $output_mode = 'download';
                        break;
                }
            } else {
                $output_mode = 'download';
            }
            return $output_mode;
        }



    }

endif; // class_exists

return new Settings();
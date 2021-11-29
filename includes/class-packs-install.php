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

if ( !class_exists( '\\PACKS\SHIPMENTS\\Install' ) ) :

    class Install {

        function __construct()	{
            // run lifecycle methods
            if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {

                add_action( 'wp_loaded', array( $this, 'do_install' ) );
            }


        }

        /** Lifecycle methods *******************************************************
         * Because register_activation_hook only runs when the plugin is manually
         * activated by the user, we're checking the current version against the
         * version stored in the database
         ****************************************************************************/

        /**
         * Handles version checking
         */
        public function do_install() {
            // only install when woocommerce is active
            if ( !PACKS_SHIPMENTS()->is_woocommerce_activated() ) {
                return;
            }

            $version_setting = 'packs_shipments_version';
            $installed_version = get_option( $version_setting );

            // installed version lower than plugin version?
            if ( version_compare( $installed_version, PACKS_SHIPMENTS_VERSION, '<' ) ) {

                if ( ! $installed_version ) {
                    $this->install();
                } else {
                    $this->upgrade( $installed_version );
                }

                // new version number
                update_option( $version_setting, PACKS_SHIPMENTS_VERSION );
            } elseif ( $installed_version && version_compare( $installed_version, PACKS_SHIPMENTS_VERSION, '>' ) ) {

                // downgrade version number
                update_option( $version_setting, PACKS_SHIPMENTS_VERSION );
            }
        }


        /**
         * Plugin install method. Perform any installation tasks here
         */
        protected function install() {
            // only install when php 5.3 or higher
            if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
                return;
            }

            /* Create temp folders
            $tmp_base = PACKS_SHIPMENTS()->main->get_tmp_base();

            // check if tmp folder exists => if not, initialize
            if ( $tmp_base !== false && !@is_dir( $tmp_base ) ) {
                PACKS_SHIPMENTS()->main->init_tmp( $tmp_base );
            }
*/
            // set default settings
            $settings_defaults = array(
                'packs_shipments_settings_general' => array(
                    'download_display'			=> 'display',
                    'api_url'			        => 'http://orders.packs.nl/App_Services/PacksWebservice.asmx?op=InsertShipmentBasic',
                    'api_user_password'			=> '',
                    'api_user_name'				=>  '',
                ),
                'packs_shipments_settings_sender' => array(
                    'sender_name'					    => '',
                    'sender_contact'					=> '',
                    'sender_housenumber'				=> '',
                    'sender_street'					=> '',
                    'sender_zipcode'					=> '',
                    'sender_city'                      => '',
                    'sender_country'                   => '',
                    'sender_default_seal'           => 'notloaded'

                ),
            );
            foreach ($settings_defaults as $option => $defaults) {
                update_option( $option, $defaults );
            }
            //$this->install_shipments_table();
            //$this->install_packingslip_table();
        }

        /**
         * Plugin upgrade method.  Perform any required upgrades here
         *
         * @param string $installed_version the currently installed ('old') version
         */
        protected function upgrade( $installed_version )
        {
            // only upgrade when php 5.3 or higher
            if (version_compare(PHP_VERSION, '5.3', '<')) {
                return;
            }
        }

        public function initialize_cpt_shipments()
        {
            $this->packs_register_cpt_shipments();
        }

        private function install_shipments_table(){
            global $wpdb;
            $version = get_option( 'packs_shipment_version', '1.0' );
            $charset_collate = $wpdb->get_charset_collate();
            $table_name = $wpdb->prefix . 'packs_shipments';

            $sql = "CREATE TABLE $table_name (
                id bigint(9) unsigned NOT NULL AUTO_INCREMENT,
                wc_order_id bigint(20) unsigned,
                packs_shipment_id bigint(10) unsigned ,
                batch varchar(255),
                handler varchar(255),
                network varchar(255),
                loaddate date,
                deliverydate date,
                loadaddress varchar(255),
                deliveryaddress varchar(255),
                status varchar(255),
                tracktrace varchar(255),
                PRIMARY KEY id (id));
                CREATE INDEX idx_orderid ON wp_packs_shipments (packs_shipment_id, id);
                $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }

        private function install_packingslip_table(){
            global $wpdb;
            $version = get_option( 'packs_shipment_version', '1.0' );
            $charset_collate = $wpdb->get_charset_collate();
            $table_name = $wpdb->prefix . 'packs_packingslips';

            $sql = "CREATE TABLE $table_name (
                id bigint(9) unsigned NOT NULL AUTO_INCREMENT,
                wc_order_id bigint(20) unsigned,
                packs_shipment_id bigint(10) unsigned ,
                packingslip blob(16777216) NOT NULL,
                packingslip_type text(5) NOT NULL,
                PRIMARY KEY id (id));
                CREATE INDEX idx_orderid ON wp_packs_packingslips (wc_order_id, id);
                $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }

    }

endif; // class_exists

return new Install();
<?php
/**
 * Created by PhpStorm.
 * User: stephanbijma
 * Date: 17-08-18
 * Time: 17:06
 */

/**
 * @package Packs Shipments
 * @version 1.6.2
 */
/*
Plugin Name: Packs Shipments
Plugin URI: https://www.wiseconn.nl/packs-extensie/
Description: Plugin voor het aanmaken/voormelden van verzendingen bij Packs.
Text Domain: shipment-packs
Author: S.A. Bijma | Wiseconn B.V.
Version: 1.6.2
Author URI: https://www.wiseconn.nl/
*/


use PACKS\SHIPMENTS\Admin\Order_Bulk_Actions;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( !class_exists( 'PACKS_SHIPMENTS' ) ) :

    class PACKS_SHIPMENTS
    {

        public $version = '1.6.2';
        public $plugin_basename;

        protected static $_instance = null;

        /**
         * Shipment factory instance.
         *
         * @var Shipment_Factory
         */
        public $shipment_factory = null;

        /**
         * Main Plugin Instance
         *
         * Ensures only one instance of plugin is loaded or can be loaded.
         */
        public static function instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Constructor
         */
        public function __construct()
        {
            $this->plugin_basename = plugin_basename(__FILE__);
            $this->define( 'CUSTOM_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );
            $this->define('PACKS_SHIPMENTS_VERSION', $this->version);
            $this->define('DS', DIRECTORY_SEPARATOR);

            // load the localisation & classes
            add_filter('cron_schedules', array($this,'add_our_recurrence'));
            register_activation_hook( __FILE__, array($this,'cronstarter_activation' ));
            add_action('plugins_loaded', array($this, 'translations'));
            add_filter('load_textdomain_mofile', array($this, 'textdomain_fallback'), 10, 2);
            add_action('plugins_loaded', array($this, 'load_classes'), 9);
            //add_filter( 'woocommerce_email_classes', array($this, 'add_shipment_order_woocommerce_email') );
            add_action( 'admin_init', array($this, 'codex_init' ));
            add_action('plugins_loaded', array($this,'custom_bulk_actions'), 10);
            add_action('plugins_loaded', array($this,'createshipmentpage'), 10);
            //add_action('plugins_loaded', array($this,'custom'), 10);
            add_filter('plugins_api', array($this,'packs_plugin_info'), 20, 3);
            add_filter('site_transient_update_plugins', array($this,'packs_push_update'));
            add_action('upgrader_process_complete', array($this,'packs_after_update'), 10, 2);
            //add_filter( 'woocommerce_locate_template', array($this,'packs_locate_template'), 10, 3 );
            add_action('packs_delete_temp', array($this,'packs_delete_temp_folder'));
            add_action('packs_data_retention', array($this,'packs_delete_data'));
            register_deactivation_hook (__FILE__, array($this,'cronstarter_deactivate'));


        }

        /**
         * Define constant if not already set
         * @param  string $name
         * @param  string|bool $value
         */
        private function define($name, $value)
        {
            if (!defined($name)) {
                define($name, $value);
            }
        }

        /**
         * Load the translation / textdomain files
         *
         * Note: the first-loaded translation file overrides any following ones if the same translation is present
         */
        public function translations()
        {
            $locale = apply_filters('plugin_locale', get_locale(), 'packs-shipments');
            $dir = trailingslashit(WP_LANG_DIR);

            $textdomains = array('packs-shipments');

            /**
             * Frontend/global Locale. Looks in:
             *
             *        - WP_LANG_DIR/packs-shipments/packs-shipments-LOCALE.mo
             *        - WP_LANG_DIR/plugins/packs-shipments/packs-shipments-LOCALE.mo
             *        - packs-shipments/languages/packs-shipments-LOCALE.mo (which if not found falls back to:)
             *        - WP_LANG_DIR/plugins/packs-shipments-LOCALE.mo
             */
            foreach ($textdomains as $textdomain) {
                load_textdomain($textdomain, $dir . 'packs-shipments/packs-shipments-' . $locale . '.mo');
                load_textdomain($textdomain, $dir . 'plugins/packs-shipments-' . $locale . '.mo');
                load_plugin_textdomain($textdomain, false, dirname(plugin_basename(__FILE__)) . '/languages');
            }
        }

        /**
         * Maintain backwards compatibility with old translation files
         * Uses old .mo file if it exists in any of the override locations
         */
        public function textdomain_fallback( $mofile, $textdomain ) {
            $plugin_domain = 'packs-shipments';
            $old_domain = 'packs_shipments';

            if ($textdomain == $old_domain) {
                $textdomain = $plugin_domain;
                $mofile = str_replace( "{$old_domain}-", "{$textdomain}-", $mofile ); // with trailing dash to target file and not folder
            }

            if ( $textdomain === $plugin_domain ) {
                $old_mofile = str_replace( "{$textdomain}-", "{$old_domain}-", $mofile ); // with trailing dash to target file and not folder
                if ( file_exists( $old_mofile ) ) {
                    // we have an old override - use it
                    return $old_mofile;
                }

                // prevent loading outdated language packs
                $pofile = str_replace('.mo', '.po', $mofile);
                if ( file_exists( $pofile ) ) {
                    // load po file
                    $podata = file_get_contents($pofile);
                    // set revision date threshold
                    $block_before = strtotime( '2017-05-15' );
                    // read revision date
                    preg_match('~PO-Revision-Date: (.*?)\\\n~s',$podata,$matches);
                    if (isset($matches[1])) {
                        $revision_date = $matches[1];
                        if ( $revision_timestamp = strtotime($revision_date) ) {
                            // check if revision is before threshold date
                            if ( $revision_timestamp < $block_before ) {
                                // try bundled
                                $bundled_file = $this->plugin_path() . '/languages/'. basename( $mofile );
                                if (file_exists($bundled_file)) {
                                    return $bundled_file;
                                } else {
                                    return '';
                                }
                                // delete po & mo file if possible
                                // @unlink($pofile);
                                // @unlink($mofile);
                            }
                        }
                    }
                }
            }

            return $mofile;
        }

        /**
         * Load the main plugin classes and functions
         */
        public function includes()
        {

            // Plugin classes


            $this->settings = include_once($this->plugin_path() . '/includes/class-packs-settings.php');
            include_once($this->plugin_path() . '/includes/admin/class-packs-admin-assets.php');
            include_once($this->plugin_path() . '/includes/admin/class-packs-admin-admin-notice.php');
            include_once($this->plugin_path() . '/includes/class-packs-customposttype.php');
            include_once($this->plugin_path() . '/includes/admin/class-packs-admin-post-types.php');
            include_once($this->plugin_path() . '/includes/admin/class-packs-admin-getlabels.php');
            include_once($this->plugin_path() . '/includes/admin/class-packs-admin-gettracktrace.php');
            include_once($this->plugin_path() . '/includes/admin/class-packs-admin-createlabels.php');
            include_once($this->plugin_path() . '/includes/admin/class-packs-admin-meta-boxes.php');
            include_once($this->plugin_path() . '/includes/admin/class-packs-admin-order-column.php');
            include_once($this->plugin_path() . '/includes/class-packs-shipment-factory.php');
            include_once($this->plugin_path() . '/includes/class-packs-createshipmentpage.php');
            include_once($this->plugin_path() . '/includes/admin/class-packs-admin-getproductinfo.php');
            include_once($this->plugin_path() . '/includes/admin/class-packs-admin-exportshipments.php');
            include_once($this->plugin_path() . '/includes/admin/class-packs-admin-order-bulk-actions.php');
            include_once($this->plugin_path() . '/includes/class-packs-adminpage.php');
            include_once($this->plugin_path() . '/includes/class-packs-shipment-retention.php');
            //$this->shipment = include_once($this->plugin_path() . '/includes/class-packs-shipments.php');
            //$this->main = include_once($this->plugin_path() . '/includes/class-packs-main.php');
            //include_once($this->plugin_path() . '/includes/class-packs-assets.php');
            //include_once($this->plugin_path() . '/includes/class-packs-admin.php');
            //include_once($this->plugin_path() . '/includes/class-packs-frontend.php');
            include_once($this->plugin_path() . '/includes/class-packs-install.php');

        }


        public function load_classes()
        {
            if ($this->is_woocommerce_activated() === false) {
                add_action('admin_notices', array($this, 'need_woocommerce'));
                return;
            }

            if (version_compare(PHP_VERSION, '5.3', '<')) {
                add_action('admin_notices', array($this, 'required_php_version'));
                return;
            }

            // all systems ready - GO!
            $this->includes();
        }

        function add_shipment_order_woocommerce_email( $email_classes ) {

            // include our custom email class
            require_once( $this->plugin_path() . '/includes/class-packs-shipment-email.php' );

            // add the email class to the list of email classes that WooCommerce loads
            $email_classes['Packs_Shipment_Email'] = new \PACKS\SHIPMENTS\Packs_Shipment_Email(NULL);

            return $email_classes;

        }

        public function codex_init() {
            add_action( 'trashed_post', array($this,'codex_sync'));
        }

        function codex_sync( $pid ) {
            global $wpdb;
            if ( $wpdb->get_var( $wpdb->prepare( 'SELECT meta_value FROM wp_postmeta WHERE post_id = %d AND meta_key = %s', $pid, 'packs_shipment_order_id' ) ) ) {
                $result = $wpdb->get_results($wpdb->prepare( 'SELECT meta_value FROM wp_postmeta WHERE post_id = %d AND meta_key = %s', $pid, 'packs_shipment_order_id' ));
                $order_id = $result[0]->meta_value;
                delete_post_meta($order_id, 'packs_shipment_shipmentid' );
                delete_post_meta($pid, 'packs_shipment_order_id' );
            }
        }

        public function custom_bulk_actions(){
            $bulk_actions = new Order_Bulk_Actions(array('post_type' => 'shop_order'));
            $shipmentorders = array();



            $bulk_actions->register_bulk_action(array(
                'menu_text'=>'Packs Zending voormelden',
                'admin_notice'=>'Zendingen voorgemeld',
                'callback' => function($post_ids) { ?>



<?php
                    return true;
                }));



            $bulk_actions->register_bulk_action(array(
                'menu_text'=>'Packs labels afdrukken',
                'admin_notice'=>'Packingslip exported',
                'action_name'=>'print_packingslip',
                'callback' => function($post_ids) {

                    return true;
                }));

        //Finally init actions
            $bulk_actions->init();
        }

        public function createshipmentpage(){
            $shipmentpage = new \PACKS\SHIPMENTS\Createshipmentpage();

            $shipmentpage->init();

        }




        /**
         * Check if woocommerce is activated
         */
        public function is_woocommerce_activated()
        {
            $blog_plugins = get_option('active_plugins', array());
            $site_plugins = is_multisite() ? (array)maybe_unserialize(get_site_option('active_sitewide_plugins')) : array();

            if (in_array('woocommerce/woocommerce.php', $blog_plugins) || isset($site_plugins['woocommerce/woocommerce.php'])) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * WooCommerce not active notice.
         *
         * @return string Fallack notice.
         */

        public function need_woocommerce()
        {
            $error = sprintf(__('Packs Shipments requires %sWooCommerce%s to be installed & activated!', 'packs-shipments'), '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>');

            $message = '<div class="error"><p>' . $error . '</p></div>';

            echo $message;
        }


        /**
         * PHP version requirement notice
         */

        public function required_php_version()
        {
            $error = __('Packs Shipments requires PHP 5.3 or higher (5.6 or higher recommended).', 'packs-shipments');
            $how_to_update = __('How to update your PHP version', 'woocommerce-pdf-invoices-packing-slips');
            $message = sprintf('<div class="error"><p>%s</p><p><a href="%s">%s</a></p></div>', $error, 'http://docs.wpovernight.com/general/how-to-update-your-php-version/', $how_to_update);
            echo $message;
        }

        /**
         * Get the plugin url.
         * @return string
         */
        public function plugin_url() {
            return untrailingslashit( plugins_url( '/', __FILE__ ) );
        }

        /**
         * Get the plugin path.
         * @return string
         */
        public function plugin_path() {
            return untrailingslashit( plugin_dir_path( __FILE__ ) );
        }

        public function packs_locate_template( $template, $template_name, $template_path ) {

            $_template = $template;

            if ( ! $template_path ) {
                $template_path = WC()->template_path();
            }

            $plugin_path = PACKS_SHIPMENTS()->plugin_path();

            // Look within passed path within the theme - this is priority
            $template = locate_template(
                array(
                    trailingslashit( $template_path ) . $template_name,
                    $template_name
                )
            );

            // Modification: Get the template from this plugin, if it exists
            if ( ! $template && file_exists( $plugin_path . $template_name ) ) {
                $template = $plugin_path . $template_name;
            }

            // Use default template
            if ( ! $template ) {
                $template = $_template;
            }

            return $template;
        }

        // create a scheduled event (if it does not exist already)
        public function cronstarter_activation() {
            if( !wp_next_scheduled( 'packs_delete_temp' ) ) {
                wp_schedule_event( time(), 'Once Every Hour', 'packs_delete_temp' );
            }
            if( !wp_next_scheduled('packs_data_retention' ) ) {
                wp_schedule_event( time(), 'daily', 'packs_data_retention' );
            }
        }

        // unschedule event upon plugin deactivation
       public function cronstarter_deactivate() {
            // find out when the last event was scheduled
            $timestamp = wp_next_scheduled ('packs_delete_temp');
           $timestampData = wp_next_scheduled ('packs_data_retention');
            // unschedule previous event if any
            wp_unschedule_event ($timestamp, 'packs_delete_temp');
            wp_unschedule_event ($timestampData, 'packs_data_retention');
        }

        public function packs_delete_temp_folder(){
            $uploaddir = wp_get_upload_dir();
            $pdfDirPath = $uploaddir['basedir'] . DS . 'packs-pdf' . DS;
            $files = scandir($pdfDirPath);
            foreach($files as $file) {

                if(is_file($file))

                    // Delete the given file
                    unlink($file);
            }
        }

        public function packs_delete_data(){
            $retention = new \PACKS\SHIPMENTS\Shipment_Retention();
            $retention->init();
        }

        public function add_our_recurrence( $schedules ) {
            // Here we add our 'Hourly' recurrence to the $schedules array
            $schedules['Once Every Hour'] = array(
                'interval' => 3600, // The number in second
                'display' => __('Once Every Hour') // Our recurrence friendly name
            );

            return $schedules;
        }

        public function packs_plugin_info( $res, $action, $args ){

            // do nothing if this is not about getting plugin information
            if( 'plugin_information' !== $action ) {
                return false;
            }

            $plugin_slug = 'PACKS_SHIPMENTS'; // we are going to use it in many places in this function

            // do nothing if it is not our plugin
            if( $plugin_slug !== $args->slug ) {
                return false;
            }

            // trying to get from cache first
            if( false == $remote = get_transient( 'packs_update_' . $plugin_slug ) ) {

                // info.json is the file with the actual plugin information on external server
                $remote = wp_remote_get( 'https://wiseconn.nl/wp-content/uploads/packs/info.json', array(
                        'timeout' => 1,
                        'headers' => array(
                            'Accept' => 'application/json'
                        ) )
                );

                if ( ! is_wp_error( $remote ) && isset( $remote['response']['code'] ) && $remote['response']['code'] == 200 && ! empty( $remote['body'] ) ) {
                    set_transient( 'packs_update_' . $plugin_slug, $remote, 43200 ); // 12 hours cache
                }

            }

            if( ! is_wp_error( $remote ) && isset( $remote['response']['code'] ) && $remote['response']['code'] == 200 && ! empty( $remote['body'] ) ) {

                $remote = json_decode( $remote['body'] );
                $res = new stdClass();

                $res->name = $remote->name;
                $res->slug = $plugin_slug;
                $res->version = $remote->version;
                $res->tested = $remote->tested;
                $res->requires = $remote->requires;
                $res->author = '<a href="https://wiseconn.nl">S. Bijma</a>';
                $res->author_profile = 'https://profiles.wordpress.org/wiseconn';
                $res->download_link = $remote->download_url;
                $res->trunk = $remote->download_url;
                $res->requires_php = '7.0';
                $res->last_updated = $remote->last_updated;
                $res->sections = array(
                    'description' => $remote->sections->description,
                    'installation' => $remote->sections->installation,
                    'changelog' => $remote->sections->changelog

                );


                if( !empty( $remote->sections->screenshots ) ) {
                    $res->sections['screenshots'] = $remote->sections->screenshots;
                }

                $res->banners = array(
                    'low' => 'https://wiseconn/wp-content/uploads/a7_logo.png',
                    'high' => 'https://wiseconn/wp-content/uploads/a7_logo.png'
                );
                return $res;

            }

            return false;

        }

        public function packs_push_update( $transient ){

            if ( empty($transient->checked ) ) {
                return $transient;
            }

            // trying to get from cache first, to disable cache comment 10,20,21,22,24
            if( false == $remote = get_transient( 'packs_upgrade_PACKS_SHIPMENTS' ) ) {

                // info.json is the file with the actual plugin information on external server
                $remote = wp_remote_get( 'https://wiseconn.nl/wp-content/uploads/packs/info.json', array(
                        'timeout' => 1,
                        'headers' => array(
                            'Accept' => 'application/json'
                        ) )
                );

                if ( !is_wp_error( $remote ) && isset( $remote['response']['code'] ) && $remote['response']['code'] == 200 && !empty( $remote['body'] ) ) {
                    set_transient( 'packs_upgrade_PACKS_SHIPMENTS', $remote, 300 ); // 12 hours cache
                }

            }

            if( $remote && is_array($remote) ) {

                $remote = json_decode( $remote['body'] );

                // your installed plugin version should be on the line below! You can obtain it dynamically of course
                if( $remote && version_compare( PACKS_SHIPMENTS_VERSION, $remote->version, '<' ) && version_compare($remote->requires, get_bloginfo('version'), '<' ) ) {
                    $res = new stdClass();
                    $res->slug = 'PACKS_SHIPMENTS';
                    $res->plugin = 'packs-shipments/packs.php';
                    $res->new_version = $remote->version;
                    $res->tested = $remote->tested;
                    $res->package = $remote->download_url;
                    $transient->response[$res->plugin] = $res;
                    //$transient->checked[$res->plugin] = $remote->version;
                }

            }
            return $transient;
        }

        function packs_after_update( $upgrader_object, $options ) {
            if ( $options['action'] == 'update' && $options['type'] === 'plugin' )  {
                // just clean the cache when new plugin version is installed
                delete_transient( 'packs_upgrade_PACKS_SHIPMENTS' );
            }
        }

    }

    endif;




/**
 * Returns the main instance of Packs Shipments to prevent the need to use globals.
 *
 * @since  1.0
 * @return PACKS_SHIPMENTS
 */
function PACKS_SHIPMENTS() {
    return PACKS_SHIPMENTS::instance();
}

PACKS_SHIPMENTS(); // load plugin


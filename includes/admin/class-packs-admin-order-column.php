<?php
/**
 * Created by PhpStorm.
 * User: stephanbijma
 * Date: 2019-03-07
 * Time: 11:39
 */
namespace PACKS\SHIPMENTS\Admin;
use WC_Order;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
if ( !class_exists( '\\PACKS\\SHIPMENTS\\Admin\\Order_Column' ) ) :

/**
 * PACKS_Admin_Order_Column.
 */
class Order_Column
{

    public function __construct()
    {
        add_filter('manage_edit-shop_order_columns', array($this,'wc_new_order_column'));
        add_filter('manage_edit-shop_order_columns', array($this,'packs_new_order_column'));

        add_action('manage_shop_order_posts_custom_column', array($this, 'packs_wc_cogs_add_order_shipment_column_content'));
        add_action('manage_shop_order_posts_custom_column', array($this, 'packs_wc_cogs_add_order_packingslip_column_content'));
        add_action('manage_shop_order_posts_custom_column', array($this, 'packs_wc_cogs_add_order_tracktrace_column_content'));
        add_action( 'admin_print_styles', array($this,'packs_wc_cogs_add_order_shipment_column_style') );
        $this->generalOptions = get_option('packs_shipments_settings_general');
    }


    public function wc_new_order_column($columns)
    {
        $columns['shipment'] = 'Shipment';
        return $columns;

    }

    public function packs_new_order_column($columns)
    {
        $columns['packingslip'] = 'Packingslip';
        $columns['tracktrace'] = 'Track&Trace';
        return $columns;

    }


    /**
     * Helper function to get meta for an order.
     *
     * @param \WC_Order $order the order object
     * @param string $key the meta key
     * @param bool $single whether to get the meta as a single item. Defaults to `true`
     * @param string $context if 'view' then the value will be filtered
     * @return mixed the order property
     */
    public function packs_helper_get_order_meta($order, $key = '', $single = true, $context = 'edit')
    {

        // WooCommerce > 3.0
        if (defined('WC_VERSION') && WC_VERSION && version_compare(WC_VERSION, '3.0', '>=')) {

            $value = $order->get_meta($key, $single, $context);

        } else {

            // have the $order->get_id() check here just in case the WC_VERSION isn't defined correctly
            $order_id = is_callable(array($order, 'get_id')) ? $order->get_id() : $order->id;
            $value = get_post_meta($order_id, $key, $single);
        }

        return $value;
    }

    /**
     * Adds 'Shipment' column content to 'Orders' page immediately after 'Total' column.
     *
     * @param string[] $column name of column being displayed
     */
    public function packs_wc_cogs_add_order_shipment_column_content($column)
    {
        global $post;

        if ('shipment' === $column) {

            $order = new WC_Order($post->ID);

            $packingslip = NULL;
            $shipmentid = $this->packs_helper_get_order_meta( $order, 'packs_shipment_shipmentid' );

            if($shipmentid){
                echo '<mark class="order-status status-processing tips"><span>'.__('Registered','packs-shipments').'('.$shipmentid.')</span></mark>';

            }else{
                echo '<a class="button" href='.admin_url( "admin.php?page=createshipments&bulk_action=packs_zending_voormelden&order_ids=".$post->ID ).'>'.__('Register Shipment','packs-shipments').'</a>';
            }


        }
    }

    /**
     * Adds 'Packingslip' column content to 'Orders' page immediately after 'Total' column.
     *
     * @param string[] $column name of column being displayed
     */
    public function packs_wc_cogs_add_order_packingslip_column_content($column)
    {
        global $post;
        global $wpdb;

        if ('packingslip' === $column) {

            $order = new WC_Order($post->ID);

            $orderid = $order->get_id();
            $packingslip = NULL;
            $packingslipstatus = NULL;
            $shipmentid = $this->packs_helper_get_order_meta( $order, 'packs_shipment_shipmentid' );
            $shipmentPostId = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = 'packs_shipment_batch' AND meta_value = '$shipmentid' LIMIT 1", ARRAY_A);

            if(!empty($shipmentid) && count($shipmentPostId) > 0){
                $shipmentPostId = (int)$shipmentPostId[0]['post_id'];
                $packingslip = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = 'packs_shipment_packingslip' AND post_id = '$shipmentPostId' LIMIT 1", ARRAY_A);
                $packingslipstatus = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = 'packs_shipment_label_received' AND post_id = '$shipmentPostId' LIMIT 1", ARRAY_A);
            }


            if($packingslip){

                    echo '<a class="button" href=' . $packingslip[0]['meta_value'] . '>' . __('Print Packingslip', 'packs-shipments') . '</a>';



            }elseif (!$shipmentPostId){
                echo '';

            }else{
                $uniqueid = rand(1000,9999);
                if(isset($packingslipstatus[0])){
                    if($packingslipstatus[0]['meta_value'] === '1'){

                        $packingslip = '<mark class="order-status status-processing tips"><span><a href="#ajaxthing" class="myajax" data-post="'.$shipmentPostId.'">'.__('Print Packingslip','packs-shipments').'</a></span></mark> <div id="loader-overlay" style="display: none;background: rgba(255,255,255,0.6);width: 100%;height: 100%;position: fixed;z-index: 999;top: 0;left: 0;"><img src="'.PACKS_SHIPMENTS()->plugin_url() . '/assets/images/ajax-loader.gif" id="loadingImage" style="position: absolute;top:50%;left:50%;"/></div>';
                    }else{

                        $packingslip = '<a href="#ajaxthing" class="myajax button" data-post="'.$shipmentPostId.'">'.__('Print Packingslip','packs-shipments').'</a> <div id="loader-overlay" style="display: none;background: rgba(255,255,255,0.6);width: 100%;height: 100%;position: fixed;z-index: 999;top: 0;left: 0;"><img src="'.PACKS_SHIPMENTS()->plugin_url() . '/assets/images/ajax-loader.gif" id="loadingImage" style="position: absolute;top:50%;left:50%;"/></div>';
                    }
                }else{
                    $packingslip = '<a href="#ajaxthing" class="myajax button" data-post="'.$shipmentPostId.'">'.__('Print Packingslip','packs-shipments').'</a> <div id="loader-overlay" style="display: none;background: rgba(255,255,255,0.6);width: 100%;height: 100%;position: fixed;z-index: 999;top: 0;left: 0;"><img src="'.PACKS_SHIPMENTS()->plugin_url() . '/assets/images/ajax-loader.gif" id="loadingImage" style="position: absolute;top:50%;left:50%;"/></div>';
                }

                echo $packingslip;
            }


        }
    }

    /**
     * Adds 'TRACKTRACE' column content to 'Orders' page immediately after 'Total' column.
     *
     * @param string[] $column name of column being displayed
     */
    public function packs_wc_cogs_add_order_tracktrace_column_content($column)
    {
        global $post;
        global $wpdb;

        if ('tracktrace' === $column) {

            $order = new WC_Order($post->ID);

            $orderid = $order->get_id();
            $tracktrace = NULL;
            $shipmentid = $this->packs_helper_get_order_meta( $order, 'packs_shipment_shipmentid' );
            $shipmentPostId = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = 'packs_shipment_batch' AND meta_value = '$shipmentid' LIMIT 1", ARRAY_A);
            if($shipmentPostId){
                $shipmentPostId = (int)$shipmentPostId[0]['post_id'];
                $tracktrace = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = 'packs_shipment_tracktrace' AND post_id = '$shipmentPostId' LIMIT 1", ARRAY_A);
            }


            if($tracktrace){


                echo '<p>'.$tracktrace[0]['meta_value'].'</p>';



            }elseif (!$shipmentid){
                echo '';

            }else{
                $uniqueid = rand(1000,9999);
                $tracktrace = '-';
                echo $tracktrace;
            }


        }
    }

    /**
     * Adjusts the styles for the new 'shipment' column.
     */
    public function packs_wc_cogs_add_order_shipment_column_style() {

        $css = '.widefat .column-order_date, .widefat .column-order_shipment { width: 9%; }';
        wp_add_inline_style( 'woocommerce_admin_styles', $css );
    }




}

endif;
new Order_Column();
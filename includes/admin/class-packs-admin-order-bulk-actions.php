<?php
namespace PACKS\SHIPMENTS\Admin;
use WC_Order;
use PACKS\SHIPMENTS\Adminpage;
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * PACKS_Admin_Order_Bulk_Actions.
 */
class Order_Bulk_Actions
{
    public $bulk_action_post_type;
    private $actions = array();

    public function __construct($args='') {
        //Define which post types these bulk actions affect.
        $defaults = array(
            'post_type' => 'post'
        );

        $args = wp_parse_args( $args, $defaults);
        //Define args as their own variables as well eg. $post_type
        extract( $args, EXTR_SKIP );

        $this->bulk_action_post_type = $post_type;
    }

    /**
     * Define all your custom bulk actions and corresponding callbacks
     * Define at least $menu_text and $callback parameters
     */
    public function register_bulk_action($args='') {
        $defaults = array (
            'action_name' => ''
        );

        $args = wp_parse_args( $args, $defaults);
        //Define args as their own variables as well eg. $post_type
        extract( $args, EXTR_SKIP );

        $func = array();
        $func["callback"] = $callback;
        $func["menu_text"] = $menu_text;
        $func["admin_notice"] = $admin_notice;

        if ($action_name === '') {

            $action_name = lcfirst(str_replace(' ', '_', $menu_text));
        }

        $this->actions[$action_name] = $func;
    }

    //Callbacks need to be registered before add_actions
    public function init() {
        if(is_admin()) {
            // admin actions/filters
            add_action('admin_footer-edit.php', array(&$this, 'custom_bulk_admin_footer'));
            add_action('load-edit.php',         array(&$this, 'custom_bulk_action'));
            add_action('admin_notices',         array(&$this, 'custom_bulk_admin_notices'));
        }
    }


    /**
     * Step 1: add the custom Bulk Action to the select menus
     */
    function custom_bulk_admin_footer() {
        global $post_type;

        //Only permit actions with defined post type
        if($post_type == $this->bulk_action_post_type) {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function() {
                    <?php
                    foreach ($this->actions as $action_name => $action) { ?>
                    jQuery('<option>').val('<?php echo $action_name ?>').text('<?php echo $action["menu_text"] ?>').appendTo("select[name='action']");
                    jQuery('<option>').val('<?php echo $action_name ?>').text('<?php echo $action["menu_text"] ?>').appendTo("select[name='action2']");
                    <?php } ?>
                });
            </script>
            <?php
        }
    }



    /**
     * Step 2: handle the custom Bulk Action
     *
     * Based on the post http://wordpress.stackexchange.com/questions/29822/custom-bulk-action
     */
    function custom_bulk_action() {
        global $typenow;
        $post_type = $typenow;

        if($post_type == $this->bulk_action_post_type) {
            $wp_list_table = _get_list_table('WP_Posts_List_Table');  // depending on your resource type this could be WP_Users_List_Table, WP_Comments_List_Table, etc
            $action = $wp_list_table->current_action();
            if ($action == 'print_packingslip') {
                // get the action


                // allow only defined actions
                $allowed_actions = array_keys($this->actions);
                if (!in_array($action, $allowed_actions)) return;

                // security check
                check_admin_referer('bulk-posts');

                $post_ids = $_REQUEST['post'];
                $shipmentids = array();
                foreach ($post_ids as $order_id){
                    $order = new WC_Order($order_id);
                    $shipmentid = $this->packs_helper_get_order_meta( $order, 'packs_shipment_shipmentid' );
                    $shipmentids[] = $shipmentid;
                }

                $labels = new Createlabels($shipmentids);
                if($labels){
                    $packingslips = $labels->url;

                    wp_redirect($packingslips);
                    exit();
                }


            } else {

            // get the action
            $wp_list_table = _get_list_table('WP_Posts_List_Table');  // depending on your resource type this could be WP_Users_List_Table, WP_Comments_List_Table, etc
            $action = $wp_list_table->current_action();

            // allow only defined actions
            $allowed_actions = array_keys($this->actions);
            if (!in_array($action, $allowed_actions)) return;

            // security check
            check_admin_referer('bulk-posts');

            // make sure ids are submitted.  depending on the resource type, this may be 'media' or 'ids'
            if (isset($_REQUEST['post'])) {
                $post_ids = $this->checkOrders($_REQUEST['post']);
                if ($post_ids) {
                    $post_ids = array_map('intval', $post_ids);
                }
            }

            if (empty($post_ids)) {
                $adminnotice = adminNotice::create()
                    ->error('Only select orders that are not registered yet')
                    ->showOnNextPage();
                return $adminnotice;
            }

            // this is based on wp-admin/edit.php
            $sendback = admin_url("admin.php?page=createshipments");
            if (!$sendback)
                $sendback = admin_url("edit.php?post_type=$post_type");

            $pagenum = $wp_list_table->get_pagenum();
            //$sendback = add_query_arg( 'paged', $pagenum, $sendback );

            if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
                //check that we have anonymous function as a callback
                $anon_fns = array_filter($this->actions[$action], function ($el) {
                    return $el instanceof Closure;
                });
                if (count($anon_fns) != 0) {
                    //Finally use the callback
                    $result = $this->actions[$action]['callback']($post_ids);
                } else {
                    $result = call_user_func($this->actions[$action]['callback'], $post_ids);
                }
            } else {
                $result = call_user_func($this->actions[$action]['callback'], $post_ids);
            }

            $sendback = add_query_arg(array('bulk_action' => $action, 'order_ids' => join(',', $post_ids)), $sendback);

            $sendback = remove_query_arg(array('action', 'paged', 'mode', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status', 'post', 'bulk_edit', 'post_view'), $sendback);

            wp_redirect($sendback);
            exit();
        }
        }
    }


    /**
     * Step 3: display an admin notice after action
     */
    function custom_bulk_admin_notices() {
        global $post_type, $pagenow;

        if($pagenow == 'edit.php' && $post_type == $this->bulk_action_post_type) {
            if (isset($_REQUEST['bulk_action']) && isset($_REQUEST['ids'])) { ?>

                <?php $ids=explode(',',$_REQUEST['ids']); ?>


<?php


                $message = $this->actions[$_REQUEST['bulk_action']]['admin_notice'];
                if(!empty($message)) {
                    echo "<div class=\"updated\"><p>{$message}</p></div>";
                }
            }
        }
    }

    function checkOrders($orderids){
        global $wpdb;
        $postids = array();
        foreach ($orderids as $orderid) {
            $result = $wpdb->get_results("SELECT meta_value FROM $wpdb->postmeta WHERE post_id = '$orderid' AND meta_key = 'packs_shipment_shipmentid' LIMIT 1", ARRAY_A);
            if(!$result){
                array_push($postids,$orderid);
            }
        }
        return $postids;
    }

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


}
new Order_Bulk_Actions();

<?php
/**
 * Packs Meta Boxes
 *
 * Sets up the write panels used by products and orders (custom post types).
 *
 * @author      WooThemes
 * @category    Admin
 * @package     Packs/Admin/Meta Boxes
 * @version     2.1.0
 */
namespace PACKS\SHIPMENTS\Admin;
use PACKS\SHIPMENTS\Settings;
use Packs_Wordpress_PDFMerger;
use PACKS\SHIPMENTS\Admin\Createlabels;
use PACKS\SHIPMENTS\Adminpage;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( !class_exists( '\\PACKS\\SHIPMENTS\\Admin\\Admin_Meta_Boxes' ) ) :
/**
 * PACKS_Admin_Meta_Boxes.
 */
class Admin_Meta_Boxes {

	/**
	 * Is meta boxes saved once?
	 *
	 * @var boolean
	 */
	private static $saved_meta_boxes = false;

	/**
	 * Meta box error messages.
	 *
	 * @var array
	 */
	public static $meta_box_errors = array();



	/**
	 * Constructor.
	 */
	public function __construct() {
        //include_once dirname( __FILE__ ) . '/meta-boxes/class-packs-meta-box-shipment-data.php';
		add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 10 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 30 );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 1, 2 );

		/**
		 * Save Order Meta Boxes.
		 *
		 * In order:
		 *      Save the shipment items.
		 *      Save the shipment orderid.
		 *      Save the shipment orderlines.
		 *      Save order data - also updates status and sends out admin emails if needed. Last to show latest data.
		 *      Save actions - sends out other emails. Last to show latest data.
		 */
		//add_action( 'packs_process_packs_shipment_meta', 'Meta_Box_Shipment_Items::save', 10, 2 );
		add_action( 'packs_process_packs_shipment_meta', array( $this, 'packs_save_meta_batch' ), 30, 2 );
//		add_action( 'packs_process_packs_shipment_meta', 'Meta_Box_Shipment_Data::save', 40, 2 );
		//add_action( 'packs_process_packs_shipment_meta', 'Meta_Box_Shipment_Actions::save', 50, 2 );

		// Error handling (for showing errors from meta boxes on next page load).
		add_action( 'admin_notices', array( $this, 'output_errors' ) );
		add_action( 'shutdown', array( $this, 'save_errors' ) );

	}

	/**
	 * Add an error message.
	 *
	 * @param string $text
	 */
	public static function add_error( $text ) {
		self::$meta_box_errors[] = $text;
	}

	/**
	 * Save errors to an option.
	 */
	public function save_errors() {
		update_option( 'packs_meta_box_errors', self::$meta_box_errors );
	}

	/**
	 * Show any stored error messages.
	 */
	public function output_errors() {
		$errors = array_filter( (array) get_option( 'packs_meta_box_errors' ) );

		if ( ! empty( $errors ) ) {

			echo '<div id="woocommerce_errors" class="error notice is-dismissible">';

			foreach ( $errors as $error ) {
				echo '<p>' . wp_kses_post( $error ) . '</p>';
			}

			echo '</div>';

			// Clear
			delete_option( 'packs_meta_box_errors' );
		}
	}

	/**
	 * Add WC Meta boxes.
	 */
	public function add_meta_boxes() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Shipments.

            add_meta_box('packs-shipment-orderid', sprintf(__( 'OrderNr', 'packs-shipments'), 'shipment'), array($this,'packs_shipment_order_id_output'),'packs_shipment', 'side','high');
		    add_meta_box('packs-shipment-batch', sprintf(__( 'Batch', 'packs-shipments'), 'shipment'), array($this,'packs_shipment_batch_output'),'packs_shipment', 'side','high');
            add_meta_box('packs-shipment-status', sprintf(__( 'Status', 'packs-shipments'), 'shipment'), array($this,'packs_shipment_status_output'),'packs_shipment', 'side','high');
		    add_meta_box('packs-shipment-handler', sprintf(__( 'Handler', 'packs-shipments'), 'shipment'), array($this,'packs_shipment_handler_output'),'packs_shipment', 'side','high');
		    add_meta_box('packs-shipment-network', sprintf(__( 'Network', 'packs-shipments'), 'shipment'), array($this,'packs_shipment_network_output'),'packs_shipment', 'side','high');
            add_meta_box( 'packs-shipment-data', sprintf( __( '%s data', 'packs-shipments' ), 'shipment' ), array($this,'packs_shipment_data_output'),'packs_shipment', 'normal', 'high' );
			add_meta_box( 'packs-shipment-items', __( 'Items', 'packs-shipments' ), array($this,'packs_shipment_items_output'), 'packs_shipment', 'normal', 'default' );
			//add_meta_box( 'packs-shipment-orderlines', sprintf( __( '%s items', 'packs-shipments' ), 'Order' ), 'PACKS_Meta_Box_Shipment_Orderitems::output', 'packs_shipment', 'normal', 'default' );
            add_meta_box( 'packs-shipment-packingslip', sprintf( __( 'Packingslip', 'packs-shipments' ), 'shipment' ), array($this,'packs_shipment_packingslip_output'),'packs_shipment', 'normal', 'high' );
            add_meta_box( 'packs-shipment-shipmentid', sprintf( __( 'ShipmentId', 'packs-shipments' ), 'shipment' ), array($this,'packs_shipment_shipmentid_output'),'shop_order', 'side', 'low' );
            add_meta_box('packs-shipment-tracktrace', sprintf(__( 'Track & Trace Link', 'packs-shipments'), 'shipment'), array($this,'packs_shipment_tracktrace_output'),'packs_shipment', 'normal','default');
            add_meta_box('packs-shipment-tracktrace', sprintf(__( 'Track & Trace Link', 'packs-shipments'), 'shipment'), array($this,'packs_shipment_tracktrace_output'),'shop_order', 'side','low');
	}

	/**
	 * Remove bloat.
	 */
	public function remove_meta_boxes() {
		remove_meta_box( 'postexcerpt', 'packs_shipment', 'normal' );
		remove_meta_box( 'commentsdiv', 'packs_shipment', 'normal' );
		remove_meta_box( 'commentstatusdiv', 'packs_shipment', 'side' );
		remove_meta_box( 'commentstatusdiv', 'packs_shipment', 'normal' );
		remove_meta_box( 'commentstatusdiv', 'packs_shipment', 'normal' );
		remove_meta_box( 'slugdiv', 'packs_shipment', 'normal' );
		remove_meta_box( 'submitdiv', 'packs_shipment', 'side' );

	}

	/**
	 * Check if we're saving, the trigger an action based on the post type.
	 *
	 * @param  int    $post_id
	 * @param  object $post
	 */
	public function save_meta_boxes( $post_id, $post ) {
		// $post_id and $post are required
		if ( empty( $post_id ) || empty( $post ) || self::$saved_meta_boxes ) {
			return;
		}

		// Dont' save meta boxes for revisions or autosaves
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}

		// Check the nonce
		if ( empty( $_POST['packs_meta_nonce'] ) || ! wp_verify_nonce( $_POST['packs_meta_nonce'], 'packs_save_data' ) ) {
			return;
		}

		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events
		if ( empty( $_POST['post_ID'] ) || $_POST['post_ID'] != $post_id ) {
			return;
		}

		// Check user has permission to edit
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// We need this save event to run once to avoid potential endless loops. This would have been perfect:
		// remove_action( current_filter(), __METHOD__ );
		// But cannot be used due to https://github.com/woocommerce/woocommerce/issues/6485
		// When that is patched in core we can use the above. For now:
		self::$saved_meta_boxes = true;

		// Check the post type
			do_action( 'packs_process_packs_shipment_meta', $post_id, $post );

	}

    public static function packs_shipment_batch_output( $post )
    {
        global $theshipment;

        if (!is_object($theshipment)) {
            $theshipment = get_post($post->ID);
        }

        $shipment = $theshipment;

        wp_nonce_field( 'packs_save_data', 'packs_meta_nonce' );

        ?>

        <?php if(get_post_meta($shipment->ID,'packs_shipment_batch')): ?>
            <?php $shipment_batch = get_post_meta($shipment->ID,'packs_shipment_batch'); ?>
        <?php else: ?>
             <?php $shipment_batch = ''; ?>
        <?php endif; ?>
            <input type="text" name="wpse_value_batch" value="<?php echo ($shipment_batch ? $shipment_batch[0] : $shipment_batch); ?>">

<?php
    }

    public static function packs_shipment_status_output( $post )
    {
        global $theshipment;

        if (!is_object($theshipment)) {
            $theshipment = get_post($post->ID);
        }

        $shipment = $theshipment;

        wp_nonce_field( 'packs_save_data', 'packs_meta_nonce' );

        ?>

        <?php if(get_post_meta($shipment->ID,'packs_shipment_status')): ?>
        <?php $shipment_status = get_post_meta($shipment->ID,'packs_shipment_status'); ?>
    <?php else: ?>
        <?php $shipment_status = ''; ?>
    <?php endif; ?>
        <input type="text" name="wpse_value_status" value="<?php echo ($shipment_status ? $shipment_status[0] : $shipment_status); ?>">

        <?php
    }

    public function packs_save_meta_batch( $theshipment ) {

        // verify nonce
        if (!isset($nonce) || !wp_verify_nonce($nonce, basename(__FILE__)))
            return 'nonce not verified';

        // check autosave
        if ( wp_is_post_autosave( $theshipment ) )
            return 'autosave';

        //check post revision
        if ( wp_is_post_revision( $theshipment ) )
            return 'revision';

        // check permissions
        if ( 'packs-shipment' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $theshipment ) )
                return 'cannot edit page';
        } elseif ( ! current_user_can( 'edit_post', $theshipment ) ) {
            return 'cannot edit post';
        }

        //so our basic checking is done, now we can grab what we've passed from our newly created form
        $wpse_value = $data;

        //simply we have to save the data now
        global $wpdb;

        $table = $wpdb->base_prefix . 'packs_shipments';

        $wpdb->insert(
            $table,
            array(
                'packs_shipment_id' => $theshipment, //as we are having it by default with this function
                'batch'   => $wpse_value  //assuming we are passing numerical value
            ),
            array(
                '%s', //%s - string, %d - integer, %f - float
                '%s', //%s - string, %d - integer, %f - float
            )
        );

    }

    public static function packs_shipment_order_id_output( $post )
    {
        global $theshipment;

        if (!is_object($theshipment)) {
            $theshipment = get_post($post->ID);
        }

        $shipment = $theshipment;

        wp_nonce_field( 'packs_save_data', 'packs_meta_nonce' );

        ?>

        <?php if(get_post_meta($shipment->ID,'packs_shipment_order_id')): ?>
        <?php $shipment_orderid = get_post_meta($shipment->ID,'packs_shipment_order_id'); ?>
    <?php else: ?>
        <?php $shipment_orderid = ''; ?>
    <?php endif; ?>
        <input type="text" name="wpse_value_order_id" value="<?php echo ($shipment_orderid ? $shipment_orderid[0] : $shipment_orderid); ?>">

        <?php
    }

    public static function packs_shipment_handler_output( $post )
    {
        global $theshipment;

        if (!is_object($theshipment)) {
            $theshipment = get_post($post->ID);
        }

        $shipment = $theshipment;

        wp_nonce_field( 'packs_save_data', 'packs_meta_nonce' );

        ?>

        <?php if(get_post_meta($shipment->ID,'packs_shipment_handler')): ?>
        <?php $shipment_handler = get_post_meta($shipment->ID,'packs_shipment_handler'); ?>
    <?php else: ?>
        <?php $shipment_handler = ''; ?>
    <?php endif; ?>
        <input type="text" name="wpse_value_handler" value="<?php echo ($shipment_handler ? $shipment_handler[0] : $shipment_handler); ?>">

        <?php
    }

    public static function packs_shipment_network_output( $post )
    {
        global $theshipment;

        if (!is_object($theshipment)) {
            $theshipment = get_post($post->ID);
        }

        $shipment = $theshipment;

        wp_nonce_field( 'packs_save_data', 'packs_meta_nonce' );

        ?>

        <?php if(get_post_meta($shipment->ID,'packs_shipment_network')): ?>
        <?php $shipment_network = get_post_meta($shipment->ID,'packs_shipment_network'); ?>
    <?php else: ?>
        <?php $shipment_network = ''; ?>
    <?php endif; ?>
        <input type="text" name="wpse_value_network" value="<?php echo ($shipment_network ? $shipment_network[0] : $shipment_network); ?>">

        <?php
    }

    public static function packs_shipment_data_output( $post ) {
        global $theshipment;

        if ( ! is_object( $theshipment ) ) {
            $theshipment = get_post( $post->ID );
        }

        $shipment = $theshipment;


        $shipment_type_object = get_post_type_object( $post->post_type );
        wp_nonce_field( 'packs_save_data', 'packs_meta_nonce' );
        ?>

        <?php if(get_post_meta($shipment->ID,'packs_shipment_data')): ?>
            <?php $shipment_data = get_post_meta($shipment->ID,'packs_shipment_data'); ?>
        <?php else: ?>
            <?php $shipment_data = ''; ?>
        <?php endif; ?>

        <div class="packs-deliveryaddress order_data_column">
            <h3>
                <?php echo __('Delivery Address'); ?>
            </h3>
            <div class="address">


            <?php if($shipment_data): ?>
            <p><?php echo __('Country','packs-shipments') ?> : <?php echo $shipment_data[0]['country']; ?></p>
            <p><?php echo __('Name','packs-shipments') ?> : <?php echo $shipment_data[0]['name']; ?></p>
            <?php if(isset($shipment_data[0]['nameTo'])):?>
                <p><?php echo __('Company','packs-shipments') ?> : <?php echo $shipment_data[0]['nameTo']; ?></p>
            <?php endif;?>
            <p><?php echo __('Street','packs-shipments') ?> : <?php echo $shipment_data[0]['street']; ?></p>
            <p><?php echo __('Number','packs-shipments') ?> : <?php echo $shipment_data[0]['number']; ?></p>
                <?php if(isset($shipment_data[0]['numberExt'])):?>
                    <p><?php echo __('NumberExt','packs-shipments') ?> : <?php echo $shipment_data[0]['numberExt']; ?></p>
                <?php endif;?>
            <p><?php echo __('Zipcode','packs-shipments') ?> : <?php echo $shipment_data[0]['zip']; ?></p>
            <p><?php echo __('Place','packs-shipments') ?> : <?php echo $shipment_data[0]['place']; ?></p>
            <p><?php echo __('Reference','packs-shipments') ?> : <?php echo $shipment_data[0]['reference']; ?></p>
            <?php endif; ?>
            </div>
        </div>
<?php
    }

    public static function packs_shipment_items_output( $post ) {
        global $theshipment;

        if ( ! is_object( $theshipment ) ) {
            $theshipment = get_post( $post->ID );
        }

        $shipment = $theshipment;


        $shipment_type_object = get_post_type_object( $post->post_type );
        wp_nonce_field( 'packs_save_data', 'packs_meta_nonce' );
        ?>

        <?php if(get_post_meta($shipment->ID,'packs_shipment_items')): ?>
            <?php $shipment_items = get_post_meta($shipment->ID,'packs_shipment_items'); ?>
        <?php else: ?>
            <?php $shipment_items = ''; ?>
        <?php endif; ?>

        <div class="woocommerce_order_items_wrapper wc-order-items-editable">
            <table cellpadding="1" cellspacing="10" class="woocommerce_order_items">
                <thead>
                <tr>
                    <th class="item shipmentItemId">shipmentItemId</th>
                    <th class="product">product</th>
                    <th class="weight">weight</th>
                    <th class="barcode">barcode</th>
                    <th class="labeltext">labelText</th>
                </tr>
                </thead>
                <tbody id="order_line_items">
                <?php $i = 0; ?>
                <?php foreach ($shipment_items[0] as $item): ?>

                <tr class="item">

                    <td class="name">

                        <?=(isset($item['shipmentItemId'])) ? $item['shipmentItemId'] : '' ?>

                    </td>


                    <td class="item_cost" data-sort-value="<?=(isset($item['product'])) ? $item['product'] : '' ?>">
                        <?=(isset($item['product'])) ? $item['product'] : '' ?>
                    </td>
                    <td class="quantity">
                        <?=(isset($item['weight'])) ? $item['weight'] : '' ?>
                    </td>
                    <td class="line_cost" data-sort-value="<?=(isset($item['barcode'])) ? $item['barcode'] : '' ?>">
                        <?=(isset($item['barcode'])) ? $item['barcode'] : '' ?>
                    </td>

                    <td class="line_labeltext">
                        <?=(isset($item['labelText'])) ? $item['labelText'] : '' ?>
                    </td>

                </tr>
                <?php $i++; ?>
        <?php endforeach; ?>
                <tr>
                    <td>
                        <?php if(!get_post_meta($shipment->ID,'packs_shipment_packingslip')): ?>
                        <a href="#ajaxthing" class="myajax button" data-post="<?php echo $theshipment->ID;?>"><?php echo __('Get Labels and Track&Trace','packs-shipment');?></a>
                            <div id="loader-overlay" style="display: none;background: rgba(255,255,255,0.6);width: 100%;height: 100%;position: fixed;z-index: 999;top: 0;left: 0;"><img src="<?php echo PACKS_SHIPMENTS()->plugin_url() . '/assets/images/ajax-loader.gif'?>" id="loadingImage" style="position: absolute;top:50%;left:50%;" /></div>
                        <?php else:
                            $packingslip = get_post_meta($shipment->ID,'packs_shipment_packingslip');
                            ?>
                            <?php
                            //$settings = new Settings();
                            //$generaloptionData = $settings->get_output_mode();
                            //if($generaloptionData == 'download'){

                              //  $newadminpage = new AdminPage();
                               // echo '<a class="button" href='.admin_url( "admin.php?page=packs_download_packingslip&file=$packingslip[0]" ).'>'.__('Download Packingslip','packs-shipment').'</a>';
                            //}else{
                                echo '<a href="'.$packingslip[0].'" class="button">'.__('Download Packingslip','packs-shipments').'</a>';
                            //}
                            ?>
                        <?php endif;?>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }


    public static function packs_shipment_packingslip_output( $post )
    {
        global $theshipment;

        if (!is_object($theshipment)) {
            $theshipment = get_post($post->ID);
        }

        $shipment = $theshipment;
        ?>
<script>
    jQuery( document ).ready( function($)
        {

            $( "#packs-shipment-packingslip" ).addClass( "hidden" );

        }
    );


</script>

<?php

    }

    public static function packs_shipment_shipmentid_output( $post )
    {
        global $theshipment;

        if (!is_object($theshipment)) {
            $theshipment = get_post($post->ID);
        }

        $shipment = $theshipment;

        wp_nonce_field( 'packs_save_data', 'packs_meta_nonce' );

        ?>

        <?php if(get_post_meta($shipment->ID,'packs_shipment_shipmentid')): ?>
        <?php $shipment_id = get_post_meta($shipment->ID,'packs_shipment_shipmentid'); ?>
    <?php else: ?>
        <?php $shipment_id = ''; ?>
    <?php endif; ?>
        <?php if($shipment_id){ ?>
            <input type="text" name="wpse_value_shipmentid" value="<?php echo $shipment_id[0]; ?>">
        <?php }

    }

    public static function packs_shipment_tracktrace_output( $post )
    {
        global $theshipment;
        if (!is_object($theshipment)) {
            $theshipment = get_post($post->ID);
        }

        $shipment = $theshipment;
        wp_nonce_field( 'packs_save_data', 'packs_meta_nonce' );

        ?>

        <?php if(get_post_meta($shipment->ID,'packs_shipment_tracktrace')): ?>
        <?php $tracktraceurl = get_post_meta($shipment->ID,'packs_shipment_tracktrace'); ?>
    <?php else: ?>
        <?php $tracktraceurl = ''; ?>
    <?php endif; ?>
        <?php if($tracktraceurl){ ?>
        <a href="<?php echo $tracktraceurl[0]; ?>" class="wpse_value_tracktrace" target="_blank"><?php echo $tracktraceurl[0]; ?></a>
    <?php } ?>
<?php

    }

    public function getGeneraloption(){

        $generaloptionData = array();
        $generaloptionData['pdf'] = $this->generalOptions['download_display'];
        return $generaloptionData;
    }

}
endif;
new Admin_Meta_Boxes();

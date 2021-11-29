<?php
namespace PACKS\SHIPMENTS\Admin;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( '\\PACKS\SHIPMENTS\\PACKS_Admin_Post_Types' , false ) ) {
	new Post_Types();
	return;
}

/**
 * Packs_Admin_Post_Types Class.
 *
 * Handles the edit posts views and some functionality on the edit post screen for packs post types.
 */
class Post_Types {

	/**
	 * Constructor.
	 */
	public function __construct() {
		include_once dirname( __FILE__ ) . '/class-packs-admin-meta-boxes.php';


		// Load correct list table classes for current screen.
		add_action( 'current_screen', array( $this, 'setup_screen' ) );
		add_action( 'check_ajax_referer', array( $this, 'setup_screen' ) );

		// Admin notices.
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_post_updated_messages' ), 10, 2 );

		// Disable Auto Save.
		add_action( 'admin_print_scripts', array( $this, 'disable_autosave' ) );

		// Extra post data and screen elements.
		add_action( 'edit_form_top', array( $this, 'edit_form_top' ) );
		add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 1, 2 );
		//add_action( 'edit_form_after_title', array( $this, 'edit_form_after_title' ) );
		add_filter( 'default_hidden_meta_boxes', array( $this, 'hidden_meta_boxes' ), 10, 2 );

	}

	/**
	 * Looks at the current screen and loads the correct list table handler.
	 *
	 * @since 3.3.0
	 */
	public function setup_screen() {
		$screen_id = false;

		if ( function_exists( 'get_current_screen' ) ) {
			$screen    = get_current_screen();
			$screen_id = isset( $screen, $screen->id ) ? $screen->id : '';
		}

		if ( ! empty( $_REQUEST['screen'] ) ) { // WPCS: input var ok.
			$screen_id = wc_clean( wp_unslash( $_REQUEST['screen'] ) ); // WPCS: input var ok, sanitization ok.
		}

		switch ( $screen_id ) {
			case 'packs_shipment':
				//include_once 'list-tables/class-packs-admin-list-table-shipments.php';
				//new PACKS_Admin_List_Table_Orders();
				break;
		}

		// Ensure the table handler is only loaded once. Prevents multiple loads if a plugin calls check_ajax_referer many times.
		remove_action( 'current_screen', array( $this, 'setup_screen' ) );
		remove_action( 'check_ajax_referer', array( $this, 'setup_screen' ) );
	}

	/**
	 * Change messages when a post type is updated.
	 *
	 * @param  array $messages Array of messages.
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		global $post;

		$messages['packs_shipment'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Order updated.', 'shipment-packs' ),
			2  => __( 'Custom field updated.', 'shipment-packs' ),
			3  => __( 'Custom field deleted.', 'shipment-packs' ),
			4  => __( 'Order updated.', 'shipment-packs' ),
			5  => __( 'Revision restored.', 'shipment-packs' ),
			6  => __( 'Order updated.', 'shipment-packs' ),
			7  => __( 'Order saved.', 'shipment-packs' ),
			8  => __( 'Order submitted.', 'shipment-packs' ),
			9  => sprintf(
				/* translators: %s: date */
				__( 'Order scheduled for: %s.', 'shipment-packs' ),
				'<strong>' . date_i18n( __( 'M j, Y @ G:i', 'shipment-packs' ), strtotime( $post->post_date ) ) . '</strong>'
			),
			10 => __( 'Order draft updated.', 'shipment-packs' ),
			11 => __( 'Order updated and sent.', 'shipment-packs' ),
		);

		return $messages;
	}

	/**
	 * Specify custom bulk actions messages for different post types.
	 *
	 * @param  array $bulk_messages Array of messages.
	 * @param  array $bulk_counts Array of how many objects were updated.
	 * @return array
	 */
	public function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {

		$bulk_messages['packs_shipment'] = array(
			/* translators: %s: order count */
			'updated'   => _n( '%s shipment updated.', '%s shipments updated.', $bulk_counts['updated'], 'shipment-packs' ),
			/* translators: %s: order count */
			'locked'    => _n( '%s shipment not updated, somebody is editing it.', '%s shipments not updated, somebody is editing them.', $bulk_counts['locked'], 'shipment-packs' ),
			/* translators: %s: order count */
			'deleted'   => _n( '%s shipment permanently deleted.', '%s shipments permanently deleted.', $bulk_counts['deleted'], 'shipment-packs' ),
			/* translators: %s: order count */
			'trashed'   => _n( '%s shipment moved to the Trash.', '%s shipments moved to the Trash.', $bulk_counts['trashed'], 'shipment-packs' ),
			/* translators: %s: order count */
			'untrashed' => _n( '%s shipment restored from the Trash.', '%s shipments restored from the Trash.', $bulk_counts['untrashed'], 'shipment-packs' ),
		);

		return $bulk_messages;
	}


	/**
	 * Disable the auto-save functionality for Shipments.
	 */
	public function disable_autosave() {
		global $post;

		if ( $post && in_array( get_post_type( $post->ID ), wc_get_order_types( 'shipment-meta-boxes' ), true ) ) {
			wp_dequeue_script( 'autosave' );
		}
	}

	/**
	 * Output extra data on post forms.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function edit_form_top( $post ) {
		echo '<input type="hidden" id="original_post_title" name="original_post_title" value="' . esc_attr( $post->post_title ) . '" />';
	}

	/**
	 * Change title boxes in admin.
	 *
	 * @param string  $text Text to shown.
	 * @param WP_Post $post Current post object.
	 * @return string
	 */
	public function enter_title_here( $text, $post ) {
		switch ( $post->post_type ) {
			case 'packs_shipment':
				$text = esc_html__( 'Shipment ID', 'packs-shipment' );
				break;
		}
		return $text;
	}

	/**
	 * Hidden default Meta-Boxes.
	 *
	 * @param  array  $hidden Hidden boxes.
	 * @param  object $screen Current screen.
	 * @return array
	 */
	public function hidden_meta_boxes( $hidden, $screen ) {
		if ( 'packs_shipment' === $screen->post_type && 'post' === $screen->base ) {
			$hidden = array_merge( $hidden, array( 'postcustom' ) );
		}

		return $hidden;
	}
}

new Post_Types();

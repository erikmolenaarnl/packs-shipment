<?php
namespace PACKS\SHIPMENTS;
use WC_Email;
use WC_Order;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * A custom Shipment WooCommerce Email class
 *
 * @since 0.1
 * @extends \WC_Email
 */
class Packs_Shipment_Email extends WC_Email {


	/**
	 * Set email defaults
	 *
	 * @since 0.1
	 */
	public function __construct($shipment) {

		// set ID, this simply needs to be a unique name
		$this->id = 'packs_shipment_order';

		// this is the title in WooCommerce Email settings
		$this->title = 'Packs Shipment';

		// this is the description in WooCommerce email settings
		$this->description = 'Packs Shipment Notification emails are sent when a Packs shipment is created';

		// these are the default heading and subject lines that can be overridden using the settings
		$this->heading = 'Packs Shipping Order';
		$this->subject = 'Packs Shipping Order';

		// these define the locations of the templates that this email should use, we'll just use the new order template since this email is similar
		$this->template_html  = 'emails/packs-new-shipment.php';
		$this->template_plain = 'emails/plain/packs-new-shipment.php';

        $this->shipment = $shipment;
        $this->plugin_path = PACKS_SHIPMENTS()->plugin_path();

		// Call parent constructor to load any other defaults not explicity defined here
		parent::__construct();

		// this sets the recipient to the settings defined below in init_form_fields()
        $this->customer_email = true;

        $this->template_base = CUSTOM_TEMPLATE_PATH;
        add_action( 'packs_email_shipment_details', array( $this, 'shipment_details'), 10, 4 );
        add_filter( 'woocommerce_template_directory', array( $this, 'custom_template_directory' ), 10, 2 );


		// if none was entered, bail
		//if ( ! $this->recipient )
		//	return;
        add_action( 'custom_pending_email_notification', array( $this, 'queue_notification' ) );
		$this->trigger($shipment);
	}


    public function queue_notification( $order_id ) {

        $order = new WC_order( $this->shipment['orderId'] );
        $items = $order->get_items();
        // foreach item in the order
        foreach ( $items as $item_key => $item_value ) {
            // add an event for the item email, pass the item ID so other details can be collected as needed
            wp_schedule_single_event( time(), 'custom_item_email', array( 'item_id' => $item_key ) );
        }
    }

	/**
	 * Determine if the email should actually be sent and setup email merge variables
	 *
	 * @since 0.1
	 * @param int $order_id
	 */
	public function trigger( $shipment ) {

		// bail if no order ID is present
		if ( ! $shipment )
			return;

		// setup order object
		$this->object = $this->create_object( $shipment );


		// replace variables in the subject/headings
		$this->find[] = '{order_date}';
		$this->replace[] = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );

		$this->find[] = '{order_number}';
		$this->replace[] = $this->object->order_number;

        $this->find[] = '{shipment_number}';
        $this->replace[] = $this->shipment['shipmentId'];


		if ( ! $this->is_enabled() )
			return;

		// woohoo, send the email!
		$this->send( $this->object->order_billing_email, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

    // Create an object with the data to be passed to the templates
    public function create_object( $shipment ) {

        global $wpdb;

        $item_object = new \stdClass();
        $order_id = $shipment['orderId'];
        $order = new WC_Order( $order_id );

        $item_object->order_id = $order->get_id();
        $item_object->order_number = $order->get_order_number();
        $item_object->order_billing_email = $order->get_billing_email();

        // order date
        $post_data = get_post( $order_id );
        $item_object->order_date = $post_data->post_date;


        $item_object->shipment = $shipment;

        return $item_object;

    }

    // return the html content
    function get_content_html() {
        ob_start();
        wc_get_template( $this->template_html, array(
            'item_data'       => $this->object,
            'email_heading' => $this->get_heading()
        ), 'my-custom-email/', $this->template_base );
        return ob_get_clean();
    }

    // return the plain content
    function get_content_plain() {
        ob_start();
        wc_get_template( $this->template_plain, array(
            'item_data'       => $this->object,
            'email_heading' => $this->get_heading()
        ), 'my-custom-email/', $this->template_base );
        return ob_get_clean();
    }




	/**
	 * Initialize Settings Form Fields
	 *
	 * @since 2.0
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled'    => array(
				'title'   => 'Enable/Disable',
				'type'    => 'checkbox',
				'label'   => 'Enable this email notification',
				'default' => 'yes'
			),
			'subject'    => array(
				'title'       => 'Subject',
				'type'        => 'text',
				'description' => sprintf( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', $this->subject ),
				'placeholder' => '',
				'default'     => ''
			),
			'heading'    => array(
				'title'       => 'Email Heading',
				'type'        => 'text',
				'description' => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.' ), $this->heading ),
				'placeholder' => '',
				'default'     => ''
			),
			'email_type' => array(
				'title'       => 'Email type',
				'type'        => 'select',
				'description' => 'Choose which format of email to send.',
				'default'     => 'html',
				'class'       => 'email_type',
				'options'     => array(
					'plain'	    => __( 'Plain text', 'woocommerce' ),
					'html' 	    => __( 'HTML', 'woocommerce' ),
					'multipart' => __( 'Multipart', 'woocommerce' ),
				)
			)
		);
	}

    public function shipment_details( $shipment, $sent_to_admin = false, $plain_text = false, $email = '' ) {
        if ( $plain_text ) {
            wc_get_template(
                'emails/plain/email-shipment-details.php', array(
                    'order'         => $this->object,
                    'shipment'      => $shipment,
                    'sent_to_admin' => $sent_to_admin,
                    'plain_text'    => $plain_text,
                    'email'         => $email,
                ),'my-custom-email/', $this->template_base
            );
        } else {
            wc_get_template(
                'emails/email-shipment-details.php', array(
                    'order'         => $this->object,
                    'shipment'      => $shipment,
                    'sent_to_admin' => $sent_to_admin,
                    'plain_text'    => $plain_text,
                    'email'         => $email,
                ),'my-custom-email/', $this->template_base
            );
        }
    }

    public function custom_template_directory( $directory, $template ) {
        // ensure the directory name is correct
        if ( false !== strpos( $template, '-custom' ) ) {
            return 'my-custom-email';
        }

        return $directory;
    }


} // end \Packs_Shipment_Email class

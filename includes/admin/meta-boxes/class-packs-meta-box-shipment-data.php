<?php
/**
 * Shipment Data
 *
 * Functions for displaying the shipment data meta box.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     Packs_Shipments/Admin/Meta Boxes
 * @version     2.2.0
 */

namespace PACKS\SHIPMENTS\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( '\\PACKS\\SHIPMENTS\\Admin\\Meta_Box_Shipment_Data' ) ) :

/**
 * PACKS_Meta_Box_Shipment_Data Class.
 */
class Meta_Box_Shipment_Data {

	/**
	 * Shipping fields.
	 *
	 * @var array
	 */
    protected static $loadaddress_fields = array();
	protected static $shipping_fields = array();

	/**
	 * Init shipping fields we display + save.
	 */
	public static function init_address_fields() {
        self::$loadaddress_fields = apply_filters(
            'packs_admin_loadaddress_fields', array(
                'first_name' => array(
                    'label' => __( 'First name', 'packs-shipment' ),
                    'show'  => false,
                ),
                'last_name'  => array(
                    'label' => __( 'Last name', 'packs-shipment' ),
                    'show'  => false,
                ),
                'company'    => array(
                    'label' => __( 'Company', 'packs-shipment' ),
                    'show'  => false,
                ),
                'address_1'  => array(
                    'label' => __( 'Address line 1', 'packs-shipment' ),
                    'show'  => false,
                ),
                'address_2'  => array(
                    'label' => __( 'Address line 2', 'packs-shipment' ),
                    'show'  => false,
                ),
                'city'       => array(
                    'label' => __( 'City', 'packs-shipment' ),
                    'show'  => false,
                ),
                'postcode'   => array(
                    'label' => __( 'Postcode / ZIP', 'packs-shipment' ),
                    'show'  => false,
                ),
                'country'    => array(
                    'label'   => __( 'Country', 'packs-shipment' ),
                    'show'    => false,
                    'type'    => 'select',
                    'class'   => 'js_field-country select short',
                    'options' => array( '' => __( 'Select a country&hellip;', 'packs-shipment' ) ) + WC()->countries->get_shipping_countries(),
                ),
                'state'      => array(
                    'label' => __( 'State / County', 'packs-shipment' ),
                    'class' => 'js_field-state select short',
                    'show'  => false,
                ),
                'reference' => array(
                        'label' => __('Reference', 'packs-shipment'),
                       'show' => false,
                ),
            )
        );

		self::$shipping_fields = apply_filters(
			'packs_admin_shipping_fields', array(
				'first_name' => array(
					'label' => __( 'First name', 'packs-shipment' ),
					'show'  => false,
				),
				'last_name'  => array(
					'label' => __( 'Last name', 'packs-shipment' ),
					'show'  => false,
				),
				'company'    => array(
					'label' => __( 'Company', 'packs-shipment' ),
					'show'  => false,
				),
				'address_1'  => array(
					'label' => __( 'Address line 1', 'packs-shipment' ),
					'show'  => false,
				),
				'address_2'  => array(
					'label' => __( 'Address line 2', 'packs-shipment' ),
					'show'  => false,
				),
				'city'       => array(
					'label' => __( 'City', 'packs-shipment' ),
					'show'  => false,
				),
				'postcode'   => array(
					'label' => __( 'Postcode / ZIP', 'packs-shipment' ),
					'show'  => false,
				),
				'country'    => array(
					'label'   => __( 'Country', 'packs-shipment' ),
					'show'    => false,
					'type'    => 'select',
					'class'   => 'js_field-country select short',
					'options' => array( '' => __( 'Select a country&hellip;', 'packs-shipment' ) ) + WC()->countries->get_shipping_countries(),
				),
				'state'      => array(
					'label' => __( 'State / County', 'packs-shipment' ),
					'class' => 'js_field-state select short',
					'show'  => false,
				),
                'reference' => array(
                    'label' => __('Reference', 'packs-shipment'),
                    'show' => false,
                ),
			)
		);
	}

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post ) {
		global $theshipment;

		if ( ! is_object( $theshipment ) ) {
            $theshipment = packs_get_shipment( $post->ID );
		}

		$shipment = $theshipment;

		self::init_address_fields();

		$shipment_type_object = get_post_type_object( $post->post_type );
		wp_nonce_field( 'packs_save_data', 'packs_meta_nonce' );
		?>
		<style type="text/css">
			#post-body-content, #titlediv { display:none }
		</style>
		<div class="panel-wrap woocommerce">
			<input name="post_title" type="hidden" value="<?php echo empty( $post->post_title ) ? __( 'Shipment', 'packs-shipment' ) : esc_attr( $post->post_title ); ?>" />
			<input name="post_status" type="hidden" value="<?php echo esc_attr( $post->post_status ); ?>" />
			<div id="order_data" class="panel woocommerce-order-data">
				<h2 class="woocommerce-order-data__heading">
					<?php

					/* translators: 1: order type 2: order number */
					printf(
						esc_html__( '%1$s #%2$s details', 'packs-shipment' ), 'Shipment',
						esc_html( $shipment->get_shipment_number() )
					);

					?>
				</h2>

				<div class="order_data_column_container">
					<div class="order_data_column">
						<h3><?php esc_html_e( 'General', 'woocommerce' ); ?></h3>

						<p class="form-field form-field-wide">
							<label for="order_date"><?php _e( 'Date created:', 'woocommerce' ); ?></label>
							<input type="text" class="date-picker" name="order_date" maxlength="10" value="<?php echo esc_attr( date_i18n( 'Y-m-d', strtotime( $post->post_date ) ) ); ?>" pattern="<?php echo esc_attr( apply_filters( 'woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" />@
							&lrm;
							<input type="number" class="hour" placeholder="<?php esc_attr_e( 'h', 'woocommerce' ); ?>" name="order_date_hour" min="0" max="23" step="1" value="<?php echo esc_attr( date_i18n( 'H', strtotime( $post->post_date ) ) ); ?>" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:
							<input type="number" class="minute" placeholder="<?php esc_attr_e( 'm', 'woocommerce' ); ?>" name="order_date_minute" min="0" max="59" step="1" value="<?php echo esc_attr( date_i18n( 'i', strtotime( $post->post_date ) ) ); ?>" pattern="[0-5]{1}[0-9]{1}" />
							<input type="hidden" name="order_date_second" value="<?php echo esc_attr( date_i18n( 's', strtotime( $post->post_date ) ) ); ?>" />
						</p>

						<p class="form-field form-field-wide wc-order-status">
							<label for="order_status">
								<?php
								_e( 'Status:', 'woocommerce' );
								if ( $shipment->needs_payment() ) {
									printf(
										'<a href="%s">%s</a>',
										esc_url( $shipment->get_checkout_payment_url() ),
										__( 'Customer payment page &rarr;', 'woocommerce' )
									);
								}
								?>
							</label>
							<select id="order_status" name="order_status" class="wc-enhanced-select">
								<?php
								$statuses = packs_get_shipment_statuses();
								foreach ( $statuses as $status => $status_name ) {
									echo '<option value="' . esc_attr( $status ) . '" ' . selected( $status, 'packs-' . $shipment->get_status( 'edit' ), false ) . '>' . esc_html( $status_name ) . '</option>';
								}
								?>
							</select>
						</p>
						<?php do_action( 'packs_admin_order_data_after_order_details', $shipment ); ?>
					</div>

					<div class="order_data_column">
						<h3>
							<?php esc_html_e( 'Load Address', 'packs-shipment' ); ?>
							<a href="#" class="edit_address"><?php esc_html_e( 'Edit', 'packs-shipment' ); ?></a>
							<span>
								<a href="#" class="load_customer_shipping" style="display:none;"><?php esc_html_e( 'Load shipping address', 'packs-shipment' ); ?></a>
								<a href="#" class="billing-same-as-shipping" style="display:none;"><?php esc_html_e( 'Copy billing address', 'packs-shipment' ); ?></a>
							</span>
						</h3>
						<div class="address">
							<?php

							// Display values.
							if ( $shipment->get_formatted_shipping_address() ) {
								echo '<p>' . wp_kses( $shipment->get_formatted_shipping_address(), array( 'br' => array() ) ) . '</p>';
							} else {
								echo '<p class="none_set"><strong>' . __( 'Address:', 'packs-shipment' ) . '</strong> ' . __( 'No shipping address set.', 'packs-shipment' ) . '</p>';
							}

							if ( ! empty( self::$loadaddress_fields ) ) {
								foreach ( self::$loadaddress_fields as $key => $field ) {
									if ( isset( $field['show'] ) && false === $field['show'] ) {
										continue;
									}

									$field_name = 'shipping_' . $key;

									if ( is_callable( array( $shipment, 'get_' . $field_name ) ) ) {
										$field_value = $shipment->{"get_$field_name"}( 'edit' );
									} else {
										$field_value = $shipment->get_meta( '_' . $field_name );
									}

									if ( $field_value ) {
										echo '<p><strong>' . esc_html( $field['label'] ) . ':</strong> ' . wp_kses_post( $field_value ) . '</p>';
									}
								}
							}

							if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' == get_option( 'woocommerce_enable_order_comments', 'yes' ) ) && $post->post_excerpt ) {
								echo '<p class="order_note"><strong>' . __( 'Customer provided note:', 'woocommerce' ) . '</strong> ' . nl2br( esc_html( $post->post_excerpt ) ) . '</p>';
							}
							?>
						</div>
						<div class="edit_address">
							<?php

							// Display form.
							if ( ! empty( self::$loadaddress_fields ) ) {
								foreach ( self::$loadaddress_fields as $key => $field ) {
									if ( ! isset( $field['type'] ) ) {
										$field['type'] = 'text';
									}
									if ( ! isset( $field['id'] ) ) {
										$field['id'] = '_loadaddress_' . $key;
									}

									$field_name = 'loadaddress_' . $key;

									if ( is_callable( array( $shipment, 'get_' . $field_name ) ) ) {
										$field['value'] = $shipment->{"get_$field_name"}( 'edit' );
									} else {
										$field['value'] = $shipment->get_meta( '_' . $field_name );
									}

									switch ( $field['type'] ) {
										case 'select':
											woocommerce_wp_select( $field );
											break;
										default:
											woocommerce_wp_text_input( $field );
											break;
									}
								}
							}

							?>
						</div>

						<?php do_action( 'packs_admin_order_data_after_loading_address', $shipment ); ?>
					</div>

                    <div class="order_data_column">
                        <h3>
                            <?php esc_html_e( 'Shipping', 'packs-shipment' ); ?>
                            <a href="#" class="edit_address"><?php esc_html_e( 'Edit', 'packs-shipment' ); ?></a>
                            <span>
								<a href="#" class="load_customer_shipping" style="display:none;"><?php esc_html_e( 'Load shipping address', 'packs-shipment' ); ?></a>
								<a href="#" class="billing-same-as-shipping" style="display:none;"><?php esc_html_e( 'Copy billing address', 'packs-shipment' ); ?></a>
							</span>
                        </h3>
                        <div class="address">
                            <?php

                            // Display values.
                            if ( $shipment->get_formatted_shipping_address() ) {
                                echo '<p>' . wp_kses( $shipment->get_formatted_shipping_address(), array( 'br' => array() ) ) . '</p>';
                            } else {
                                echo '<p class="none_set"><strong>' . __( 'Address:', 'packs-shipment' ) . '</strong> ' . __( 'No shipping address set.', 'packs-shipment' ) . '</p>';
                            }

                            if ( ! empty( self::$shipping_fields ) ) {
                                foreach ( self::$shipping_fields as $key => $field ) {
                                    if ( isset( $field['show'] ) && false === $field['show'] ) {
                                        continue;
                                    }

                                    $field_name = 'shipping_' . $key;

                                    if ( is_callable( array( $shipment, 'get_' . $field_name ) ) ) {
                                        $field_value = $shipment->{"get_$field_name"}( 'edit' );
                                    } else {
                                        $field_value = $shipment->get_meta( '_' . $field_name );
                                    }

                                    if ( $field_value ) {
                                        echo '<p><strong>' . esc_html( $field['label'] ) . ':</strong> ' . wp_kses_post( $field_value ) . '</p>';
                                    }
                                }
                            }
                            ?>
                        </div>
                        <div class="edit_address">
                            <?php

                            // Display form.
                            if ( ! empty( self::$shipping_fields ) ) {
                                foreach ( self::$shipping_fields as $key => $field ) {
                                    if ( ! isset( $field['type'] ) ) {
                                        $field['type'] = 'text';
                                    }
                                    if ( ! isset( $field['id'] ) ) {
                                        $field['id'] = '_shipping_' . $key;
                                    }

                                    $field_name = 'shipping_' . $key;

                                    if ( is_callable( array( $order, 'get_' . $field_name ) ) ) {
                                        $field['value'] = $order->{"get_$field_name"}( 'edit' );
                                    } else {
                                        $field['value'] = $order->get_meta( '_' . $field_name );
                                    }

                                    switch ( $field['type'] ) {
                                        case 'select':
                                            woocommerce_wp_select( $field );
                                            break;
                                        default:
                                            woocommerce_wp_text_input( $field );
                                            break;
                                    }
                                }
                            }

                            ?>
                        </div>

                        <?php do_action( 'packs_admin_order_data_after_shipping_address', $shipment ); ?>
                    </div>
				</div>
				<div class="clear"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function save( $shipment_id ) {
		self::init_address_fields();

		//WC()->shipping();

		// Get shipment object.
		$shipment = packs_get_shipment( $shipment_id );
		$props = array();

		// Create shipment key.
		if ( ! $shipment->get_shipment_key() ) {
			$props['order_key'] = 'packs_' . apply_filters( 'woocommerce_generate_order_key', uniqid( 'shipment_' ) );
		}

		// Update customer.
		$customer_id = isset( $_POST['customer_user'] ) ? absint( $_POST['customer_user'] ) : 0;
		if ( $customer_id !== $order->get_customer_id() ) {
			$props['customer_id'] = $customer_id;
		}

		// Update loadaddress fields.
		if ( ! empty( self::$loadaddress_fields ) ) {
			foreach ( self::$loadaddress_fields as $key => $field ) {
				if ( ! isset( $field['id'] ) ) {
					$field['id'] = '_loadaddress_' . $key;
				}

				if ( ! isset( $_POST[ $field['id'] ] ) ) {
					continue;
				}

				if ( is_callable( array( $shipment, 'set_loadaddress_' . $key ) ) ) {
					$props[ 'loadaddress_' . $key ] = wc_clean( $_POST[ $field['id'] ] );
				} else {
                    $shipment->update_meta_data( $field['id'], wc_clean( $_POST[ $field['id'] ] ) );
				}
			}
		}

        // Update shipping fields.
        if ( ! empty( self::$shipping_fields ) ) {
            foreach ( self::$shipping_fields as $key => $field ) {
                if ( ! isset( $field['id'] ) ) {
                    $field['id'] = '_shipping_' . $key;
                }

                if ( ! isset( $_POST[ $field['id'] ] ) ) {
                    continue;
                }

                if ( is_callable( array( $shipment, 'set_shipping_' . $key ) ) ) {
                    $props[ 'shipping_' . $key ] = wc_clean( $_POST[ $field['id'] ] );
                } else {
                    $shipment->update_meta_data( $field['id'], wc_clean( $_POST[ $field['id'] ] ) );
                }
            }
        }

		if ( isset( $_POST['_transaction_id'] ) ) {
			$props['transaction_id'] = wc_clean( $_POST['_transaction_id'] );
		}

		// Update date.
		if ( empty( $_POST['delivery_date'] ) ) {
			$date = current_time( 'timestamp', true );
		} else {
			$date = gmdate( 'Y-m-d H:i:s', strtotime( $_POST['order_date'] . ' ' . (int) $_POST['order_date_hour'] . ':' . (int) $_POST['order_date_minute'] . ':' . (int) $_POST['order_date_second'] ) );
		}

		$props['date_created'] = $date;

		// Set created via prop if new post.
		if ( isset( $_POST['original_post_status'] ) && $_POST['original_post_status'] === 'auto-draft' ) {
			$props['created_via'] = 'admin';
		}

		// Save order data.
        $shipment->set_props( $props );
        $shipment->set_status( wc_clean( $_POST['order_status'] ), '', true );
        $shipment->save();
	}
}

endif;

return new Meta_Box_Shipment_Data();
<?php
namespace PACKS\SHIPMENTS;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( '\\PACKS\\SHIPMENTS\\Settings_General' ) ) :

class Settings_General {

	function __construct()	{
		add_action( 'admin_init', array( $this, 'init_settings' ) );
		add_action( 'packs_shipments_settings_output_general', array( $this, 'output' ), 10, 1 );
		add_action( 'packs_shipments_before_settings', array( $this, 'sender_settings_hint' ), 10, 2 );
	}

	public function output( $section ) {
		settings_fields( "packs_shipments_settings_general" );
		do_settings_sections( "packs_shipments_settings_general" );

		submit_button();
	}

	public function init_settings() {
		$page = $option_group = $option_name = 'packs_shipments_settings_general';

		$template_base_path = ( defined( 'WC_TEMPLATE_PATH' ) ? WC_TEMPLATE_PATH : $GLOBALS['woocommerce']->template_url );
		$theme_template_path = get_stylesheet_directory() . '/' . $template_base_path;
		$wp_content_dir = str_replace( ABSPATH, '', WP_CONTENT_DIR );
		$theme_template_path = substr($theme_template_path, strpos($theme_template_path, $wp_content_dir)) . 'pdf/yourtemplate';
		//$plugin_template_path = "{$wp_content_dir}/plugins/packs-shipments/templates/Simple";

		$settings_fields = array(
			array(
				'type'		=> 'section',
				'id'		=> 'general_settings',
				'title'		=> __( 'General settings', 'packs-shipments' ),
				'callback'	=> 'section',
			),
			array(
				'type'		=> 'setting',
				'id'		=> 'api_test_live',
				'title'		=> __( 'Which API do you want to use', 'packs-shipments' ),
				'callback'	=> 'select',
				'section'	=> 'general_settings',
				'args'		=> array(
					'option_name'	=> $option_name,
					'id'			=> 'api_test_live',
					'options' 		=> array(
						'live'	=> __( 'Live' , 'packs-shipments' ),
						'test'	=> __( 'Test' , 'packs-shipments' ),
					),
				)
			),
            array(
                'type'		=> 'setting',
                'id'		=> 'api_user_name',
                'title'		=> __( 'Packs API Username', 'packs-shipments' ),
                'callback'	=> 'text_input',
                'section'	=> 'general_settings',
                'args'		=> array(
                    'option_name'	=> $option_name,
                    'id'			=> 'api_user_name',
                    'size'			=> '72',
                    'translatable'	=> true,
                )
            ),
            array(
                'type'		=> 'setting',
                'id'		=> 'api_user_password',
                'title'		=> __( 'Packs API Password', 'packs-shipments' ),
                'callback'	=> 'password_input',
                'section'	=> 'general_settings',
                'args'		=> array(
                    'option_name'	=> $option_name,
                    'id'			=> 'api_user_password',
                    'size'			=> '72',
                    'translatable'	=> true,
                )
            ),
            array(
                'type'		=> 'setting',
                'id'		=> 'api_data_retention',
                'title'		=> __( 'Shipments data retention in x days', 'packs-shipments' ),
                'callback'	=> 'text_input',
                'section'	=> 'general_settings',
                'args'		=> array(
                    'option_name'	=> $option_name,
                    'id'			=> 'api_data_retention',
                    'size'			=> '72',
                    'translatable'	=> true,
                )
            )
		);

		// allow plugins to alter settings fields
		$settings_fields = apply_filters( 'packs_shipments_settings_fields_general', $settings_fields, $page, $option_group, $option_name );
		PACKS_SHIPMENTS()->settings->add_settings_fields( $settings_fields, $page, $option_group, $option_name );
		return;
	}

	public function sender_settings_hint( $active_tab, $active_section ) {
		// save or check option to hide attachments settings hint
		if ( isset( $_GET['packs_shipments_hide_sender_hint'] ) ) {
			update_option( 'packs_shipments_hide_sender_hint', true );
			$hide_hint = true;
		} else {
			$hide_hint = get_option( 'packs_shipments_hide_sender_hint' );
		}

		if ( $active_tab == 'general' && !$hide_hint) {
			include_once( PACKS_SHIPMENTS()->plugin_path() . '/includes/views/sender-settings-hint.php' );
		}
	}


}

endif; // class_exists

return new Settings_General();
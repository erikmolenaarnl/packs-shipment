<?php
namespace PACKS\SHIPMENTS;

use PACKS\SHIPMENTS\Admin\Getproductinfo;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( !class_exists( '\\PACKS\\SHIPMENTS\\Settings_Sender' ) ) :

    class Settings_Sender
    {
        function __construct()
        {
            add_action('admin_init', array($this, 'init_settings'));
            add_action('packs_shipments_settings_output_sender', array($this, 'output'), 10, 1);
        }

        public function output($section)
        {
            settings_fields("packs_shipments_settings_sender");
            do_settings_sections("packs_shipments_settings_sender");

            submit_button();
        }

        public function init_settings()
        {
            $page = $option_group = $option_name = 'packs_shipments_settings_sender';

            $template_base_path = (defined('WC_TEMPLATE_PATH') ? WC_TEMPLATE_PATH : $GLOBALS['woocommerce']->template_url);
            $theme_template_path = get_stylesheet_directory() . '/' . $template_base_path;
            $wp_content_dir = str_replace(ABSPATH, '', WP_CONTENT_DIR);
            $seals = array();
            $GetproductInfo = new Getproductinfo();
            $productOptions = $GetproductInfo->init();
            if($productOptions){
                foreach ($productOptions as $option){
                    $seals[strtolower($option['product'])] = $option['product'];
                }
            }else{
                $seals[] = 'notloaded';
            }

            $settings_fields = array(
                array(
                    'type' => 'section',
                    'id' => 'sender_settings',
                    'title' => __('Sender settings', 'packs-shipments'),
                    'callback' => 'section',
                ),
                array(
                    'type' => 'setting',
                    'id' => 'sender_handler',
                    'title' => __('Handler', 'packs-shipments'),
                    'callback' => 'text_input',
                    'section' => 'sender_settings',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'sender_handler',
                        'size' => '72',
                        'translatable' => true,
                        'description' => __('Example: All, HPD, Packs, A7, Circuit, JBM, FRL, Merenpost, PietSpoed, EfficientLogistics, InControlLogistics','packs-shipments')
                    ),
                ),
                array(
                    'type' => 'setting',
                    'id' => 'sender_network',
                    'title' => __('Network', 'packs-shipments'),
                    'callback' => 'text_input',
                    'section' => 'sender_settings',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'sender_network',
                        'size' => '72',
                        'translatable' => true,
                        'description' => __('Example: All, InNight, NextDay, Evening, Sunday','packs-shipments')
                    ),
                ),
                array(
                    'type'		=> 'setting',
                    'id'		=> 'sender_default_seal',
                    'title'		=> __( 'Which seal option you want to use as default', 'packs-shipments' ),
                    'callback'	=> 'select',
                    'section'	=> 'sender_settings',
                    'args'		=> array(
                        'option_name'	=> $option_name,
                        'id'			=> 'sender_default_seal',
                        'options' 		=>
                            $seals,
                        'description' => __('First save the general and sender options to load the seal options','packs-shipments')
                    ),
                ),
                array(
                    'type' => 'setting',
                    'id' => 'sender_name',
                    'title' => __('Name', 'packs-shipments'),
                    'callback' => 'text_input',
                    'section' => 'sender_settings',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'sender_name',
                        'size' => '72',
                        'translatable' => true,
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'sender_company',
                    'title' => __('Company', 'packs-shipments'),
                    'callback' => 'text_input',
                    'section' => 'sender_settings',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'sender_company',
                        'size' => '72',
                        'translatable' => true,
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'sender_street',
                    'title' => __('Street', 'packs-shipments'),
                    'callback' => 'text_input',
                    'section' => 'sender_settings',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'sender_street',
                        'size' => '72',
                        'translatable' => true,
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'sender_housenumber',
                    'title' => __('Housenumber', 'packs-shipments'),
                    'callback' => 'text_input',
                    'section' => 'sender_settings',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'sender_housenumber',
                        'size' => '72',
                        'translatable' => true,
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'sender_numberext',
                    'title' => __('Number Ext.', 'packs-shipments'),
                    'callback' => 'text_input',
                    'section' => 'sender_settings',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'sender_numberext',
                        'size' => '72',
                        'translatable' => true,
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'sender_zipcode',
                    'title' => __('Zipcode', 'packs-shipments'),
                    'callback' => 'text_input',
                    'section' => 'sender_settings',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'sender_zipcode',
                        'size' => '72',
                        'translatable' => true,
                        'description' => __('Example: 1234AB (No spaces)','packs-shipments')
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'sender_city',
                    'title' => __('City', 'packs-shipments'),
                    'callback' => 'text_input',
                    'section' => 'sender_settings',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'sender_city',
                        'size' => '72',
                        'translatable' => true,
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'sender_country',
                    'title' => __('Country', 'packs-shipments'),
                    'callback' => 'text_input',
                    'section' => 'sender_settings',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'sender_country',
                        'size' => '72',
                        'translatable' => true,
                        'description' => __('Example: NL (country code)','packs-shipments')
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'sender_mail',
                    'title' => __('Email', 'packs-shipments'),
                    'callback' => 'text_input',
                    'section' => 'sender_settings',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'sender_mail',
                        'size' => '72',
                        'translatable' => true,
                        'description' => __('Shop Owner\'s email address','packs-shipments')
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'sender_reference',
                    'title' => __('Reference', 'packs-shipments'),
                    'callback' => 'text_input',
                    'section' => 'sender_settings',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'sender_reference',
                        'size' => '72',
                        'translatable' => true
                    )
                ),
            );

            // allow plugins to alter settings fields
            $settings_fields = apply_filters('packs_shipments_settings_fields_sender', $settings_fields, $page, $option_group, $option_name);
            PACKS_SHIPMENTS()->settings->add_settings_fields($settings_fields, $page, $option_group, $option_name);
            return;
        }
    }
    endif; // class_exists

    return new Settings_Sender();
<?php
namespace PACKS\SHIPMENTS;


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( !class_exists( '\\PACKS\\SHIPMENTS\\Adminpage' ) ) :


    class Adminpage
    {
        public function __construct()
        {
            $this->init();
        }

        public function init()
        {
            add_action('admin_menu', array(&$this, 'register_editpage'));
            add_action('admin_enqueue_scripts', array(&$this,'enqueue_scripts'));
        }

        function enqueue_scripts(){
            $jquery_path = 'https://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js';
            //$jquery_ui = 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js';
            wp_register_script('cdnjquery', $jquery_path);
            //wp_register_script('cdnjqueryui', $jquery_ui);
            //wp_enqueue_script('cdnjquery');
            wp_enqueue_script('cdnjqueryui');
        }

        function register_editpage()
        {
            add_menu_page('Download PDF', 'downloadpdf', 'administrator', 'packs_download_packingslip', array($this, 'download'));
            remove_menu_page('packs_download_packingslip');

        }

        function download()
        {
            if (isset($_REQUEST['file'])) {
                $file = $_REQUEST['file'];
                $filename = pathinfo($file,PATHINFO_FILENAME).'.pdf';
                header("Content-Type: application/pdf");
                header("Content-Disposition: attachment; filename=$filename");
                readfile($file);
            }

        }
    }
endif;

//return new Adminpage();
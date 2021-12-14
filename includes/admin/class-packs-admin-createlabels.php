<?php
/**
 * Created by PhpStorm.
 * User: stephanbijma
 * Date: 2019-03-08
 * Time: 12:44
 */

namespace PACKS\SHIPMENTS\Admin;
use PACKS\SHIPMENTS\Settings;
use PACKS\SHIPMENTS\Shipment_Factory;
use Packs_Wordpress_PDFMerger;
use Psr\Log\NullLogger;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( !class_exists( '\\PACKS\\SHIPMENTS\\Admin\\Createlabels' ) ) :



    class Createlabels
    {
        public $url;

        public function __construct($shipmentids = NULL)
        {
            add_action( 'admin_footer', array( $this, 'getLabelActionJS'));
            add_action('wp_ajax_getLabel',array( $this, 'getLabel'));

            $shipmentids = $shipmentids;
            if($shipmentids){
                $labels = $this->getLabel($shipmentids);
                if(!$labels){
                    return false;
                }
            }
            return $this->url;
        }

        public function getLabelActionJS(){ ?>
            <?php if(isset($_GET['post'])){
                $post = $_GET['post'];
            }
            ?>
            <iframe id="downloadframe" style="display:none;"></iframe>
            <script type="text/javascript" >

                jQuery(document).ready(function($) {

                    $('.myajax').click(function(){
                        $("#loader-overlay").show();
                        var post = $(this).data('post');
                        var item = $(this).data('item');
                        //console.log(item);
                        var data = {
                            action: 'getLabel',
                            id: post
                        };

                        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                        $.post(ajaxurl, data, function(response) {
                            var response = $.trim(response);
                            //console.log(response);
                            $("#loader-overlay").hide();
                            setTimeout(function () {
                                if(response != 0){
                                     window.open(response);
                                }

                                location.reload();
                            }, 2000)
                        });
                    });


                });
            </script>
        <?php }

        public function getLabel($shipmentids = NULL) {
            global $wpdb; // this is how you get access to the database
            if($shipmentids){
                $getlabel = new Getlabels($shipmentids);

                if($getlabel->responses){
                    foreach($getlabel->responses as $shipmentitem){
                        $shipment_items[0] = $shipmentitem;
                    }

                    $packingslip = $this->createLabelsPdf($shipment_items[0]);
                    foreach ($shipmentid as $shipment){
                        update_post_meta( $shipment, 'packs_shipment_label_received', 1 );
                    }
                }else{
                    return false;
                }

            }else{
                $theshipment = intval( $_POST['id'] );
                //$itemnr = intval($_POST['item']);
                //$shipmentItemId = intval($_POST['shipmentItemId']);

                $shipment = $theshipment;

                if(get_post_meta($shipment,'packs_shipment_items')): ?>
                    <?php $shipment_items = get_post_meta($shipment,'packs_shipment_items'); ?>
                <?php else: ?>
                    <?php $shipment_items = ''; ?>
                <?php endif; ?>

                <?php

                $shipmentid = get_post_meta($shipment,'packs_shipment_batch');
                $getlabel = new Getlabels($shipmentid[0]);
                if($getlabel->responses){
                    update_post_meta( $shipment, 'packs_shipment_label_received', 1 );
                    $gettracktrace = new getTracktrace($shipmentid[0]);
                    foreach($getlabel->responses as $shipmentitem){
                        $shipment_items[0] = $shipmentitem;
                    }


                    $packingslip = $this->createLabelsPdf($shipment_items[0]);
                    $tracktrace = $this->saveTracktrace($gettracktrace->responses);
                    //update_post_meta( $shipment, 'packs_shipment_packingslip', $packingslip );
                    echo $packingslip;
                    wp_die(); // this is required to return a proper result & exit is faster than die();
                    return $packingslip;
                }else{
                    wp_die();
                }


            }

        }

        public function createLabelsPdf($labelobjects)
        {
            $uploaddir = wp_get_upload_dir();
            $pdfDirPath = $uploaddir['basedir'] . DS . 'packs-pdf' . DS;
            $pdfDirPathTemp =  get_temp_dir() . 'packs-pdf' . DS . 'tmp' . DS;

            if (!file_exists($pdfDirPath)) {
                mkdir($pdfDirPath, 0777, true);
            }
            if (!file_exists($pdfDirPathTemp)) {
                mkdir($pdfDirPathTemp, 0777, true);
            }

            $pdfDocs = array();
            $i=0;
            foreach ($labelobjects as $labelData) {
                //Decode pdf content
                $pdf_decoded = base64_decode($labelData['labelObject']);
                //Write data back to pdf file
                $now = date("YmdHis");
                $filename = $pdfDirPath . $now . '-' . 'shipment.pdf';
                $pdf = fopen($filename, 'w');
                fwrite($pdf, $pdf_decoded);
                //close output file
                fclose($pdf);
                array_push($pdfDocs, $filename);
                $i++;
            }

            /* CREATE ONE FILE OUT OF IT */

//            $pdf = new Packs_Wordpress_PDFMerger(); // or use $pdf = new \PDFMerger; for Laravel
//            foreach($pdfDocs as $doc){
//                $pdf->addPDF($doc ,'all');
//            }
//            $now = date("YmdHis");
//            $mergedFileName = $pdfDirPath.$now.'.pdf';
//            $pdf->merge('file', $mergedFileName); // generate the file
//
//
//            $fileToDelete = glob($pdfDirPathTemp.'*');
//            foreach($fileToDelete as $file){
//                if(is_file($file))
//                    unlink($file);
//            }
            $url = str_replace( $uploaddir['basedir'], $uploaddir['baseurl'], $filename);
            $this->url = $url;
            return $url;

        }

        public function saveTracktrace($trackingobjects){

            if($trackingobjects){
                global $wpdb;
                $shipmentid = $trackingobjects["shipmentId"];
                $shipmentPostId = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = 'packs_shipment_batch' AND meta_value = '$shipmentid' LIMIT 1", ARRAY_A);
                $orderId = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = 'packs_shipment_shipmentid' AND meta_value = '$shipmentid' LIMIT 1", ARRAY_A);
                update_post_meta( $shipmentPostId[0]['post_id'], 'packs_shipment_tracktrace', $trackingobjects['trackAndTraceUrl'] );
                update_post_meta( $orderId[0]['post_id'], 'packs_shipment_tracktrace', $trackingobjects['trackAndTraceUrl'] );
                // Add track&trace URL to Order notes
                $order = wc_get_order(  $orderId[0]['post_id'] );
                if($order){

                    $note = $trackingobjects['trackAndTraceUrl'];
                    $order->add_order_note( $note );
                }
                return true;
            }
        }
    }

endif;
new Createlabels();
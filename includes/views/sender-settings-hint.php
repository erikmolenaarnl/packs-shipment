<?php
$invoice_settings_url = add_query_arg( array(
		'tab' => 'sender',
		'section' => 'sender',
	) );
?>
<style>
.packs-attachment-settings-hint {
	display: inline-block;
	background: #fff;
	border-left: 4px solid #cc99c2 !important;
	-webkit-box-shadow: 0 1px 1px 0 rgba( 0, 0, 0, 0.1 );
	box-shadow: 0 1px 1px 0 rgba( 0, 0, 0, 0.1 );
	padding: 15px;
	margin-top: 15px;
	font-size: 120%;
}
</style>
<!-- <div id="message" class="updated woocommerce-message"> -->
<div class="packs-attachment-settings-hint">
	<?php printf(__( "Make sure you have set your sender settings, check the settings under <b>%sSender%s</b>", 'packs-shipments' ), '<a href="'.$invoice_settings_url.'">', '</a>'); ?><br>
	<?php printf('<a href="%s" style="font">%s</a>', add_query_arg( 'packs_shipments_hide_attachments_hint', 'true' ), __( 'Hide this message', 'packs-shipments' ) ); ?>
</div>

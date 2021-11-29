<script type="text/javascript">
	jQuery( function( $ ) {
		$("#footer-thankyou").html("Powered by <strong>Wiseconn</strong> <a href='https://www.wiseconn.nl'>Wiseconn</a>");
	});
</script>
<div class="wrap">
	<div class="icon32" id="icon-options-general"><br /></div>
	<h2><?php _e( 'Packs Shipments', 'packs-shipments' ); ?></h2>
	<h2 class="nav-tab-wrapper">
	<?php
	foreach ($settings_tabs as $tab_slug => $tab_title ) {
		$tab_link = esc_url("?page=packs_shipments_options_page&tab={$tab_slug}");
		printf('<a href="%1$s" class="nav-tab nav-tab-%2$s %3$s">%4$s</a>', $tab_link, $tab_slug, (($active_tab == $tab_slug) ? 'nav-tab-active' : ''), $tab_title);
	}
	?>
	</h2>

	<?php
	do_action( 'packs_shipments_before_settings_page', $active_tab, $active_section );


	?>
	<form method="post" action="options.php" id="packs-shipment-settings" class="<?php echo "{$active_tab} {$active_section}"; ?>">
		<?php
			do_action( 'packs_shipments_before_settings', $active_tab, $active_section );
			if ( has_action( 'packs_shipments_settings_output_'.$active_tab ) ) {
				do_action( 'packs_shipments_settings_output_'.$active_tab, $active_section );
			} else {
				// legacy settings
				settings_fields( "packs_shipments_{$active_tab}_settings" );
				do_settings_sections( "packs_shipments_{$active_tab}_settings" );

				submit_button();
			}
			do_action( 'packs_shipments_after_settings', $active_tab, $active_section );
		?>

	</form>
	<?php do_action( 'packs_shipments_after_settings_page', $active_tab, $active_section ); ?>
</div>

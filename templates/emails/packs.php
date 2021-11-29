<div id="template_header_image">
    <?php
    if ( $img = get_option( 'woocommerce_email_header_image' ) ) {
        echo '<p style="margin-top:0;"><img src="' . esc_url( $img ) . '" alt="' . get_bloginfo( 'name', 'display' ) . '" /></p>';
    }
    ?>
</div>
<h3><?php echo __('You order shipment has changed status');?></h3><br>

<p>
  <strong><?php echo __('Name');?>:</strong><?= $name ?>
</p>

<tr>
    <td> <strong><?php echo __('Delivery Address');?>:</strong></td>
</tr>
<tr>
    <td>
       <p><?= $street ?> </p>
        <p><?= $number ?></p>
        <p><?= $zip ?></p>
        <p><?= $place ?></p>
    </td>
</tr>
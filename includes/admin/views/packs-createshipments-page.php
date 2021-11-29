<?php
$productoptions = $this->productoptions;

if($productoptions):
?>

<div class="wrap">

    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <?php $ids=explode(',',$this->orderIds); ?>
    <style>
        table {
            font-family: arial, sans-serif;
            border-collapse: collapse;
            width: 99%;
        }

        td, th {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 3px;
        }

        input[type="submit"]{
            margin-right:15px;
        }

        tr:nth-child(even) {
            background-color: #dddddd;
        }
        .copy{float:right; width:20px; height: 20px; background-color:#2e83ca; font-size:20px; font-weight:bold; color:#fff; border-radius:10px; text-align:center; cursor: pointer;margin-top:4px;}
    </style>
    <p><?php echo __('Set the required options for every order. You can use the arrow to copy the value to the other rows.', 'packs-shipments') ?></p>
    <form action="" method="post" id="new_shipment" name="new_shipment">
        <div class="category-rows">
            <input type="hidden" name="order_ids" value="<?php echo implode(",",$ids);?>">
            <table>
                <tbody><tr>
                    <th><?php echo __('OrderID','packs-shipments');?></th>
                    <th><?php echo __('Colli','packs-shipments');?></th>
                    <th><?php echo __('Seal','packs-shipments');?></th>
                    <th><?php echo __('Load date','packs-shipments');?></th>
                    <th><?php echo __('Delivery date','packs-shipments');?></th>
                    <th><?php echo __('Reference','packs-shipments');?></th>
                    <th><?php echo __('Weight','packs-shipments');?></th>
                    <th><?php echo __('Fee','packs-shipments');?></th>
                </tr>
                <?php foreach ($ids as $order_id): ?>
                    <?php $order = wc_get_order( $order_id ); ?>
                <?php

                    $weight = array();
                    $weight_unit = get_option('woocommerce_weight_unit');
                    $colli = sizeof( $order->get_items());
                    if ( sizeof( $order->get_items() ) > 0 ) {
                        foreach( $order->get_items() as $item ) {
                            if ( $item['product_id'] > 0 ) {
                                $_product = $item->get_product();
                                if ( ! $_product->is_virtual() ) {
                                    $weight[] = $_product->get_weight();
                                }
                            }
                        }
                        $max_weight = max($weight);
                        $weight = ceil((float)$max_weight);
                        if($weight_unit !== 'kg'){
                            $weight = (float)$weight / 1000;
                        }
                    }else{
                        $weight = 0;
                    }
                    ?>
                    <tr>
                        <td><?php echo $order->get_order_number(); ?></td>
                        <td><input class="required-entry" data-name="collie" id="<?php echo $order_id; ?>-collie" maxlength="4" size="4" name="<?php echo $order_id; ?>-collie" type="text" value="<?php echo $colli; ?>"><div class="copy">⇩</div></td>
                        <td>
                            <select class="validate-select required-entry" data-name="seal" name="<?php echo $order_id; ?>-seal">
                                <option value="0">Kies een zegel</option>
                                <?php foreach($productoptions as $product): ?>
                                    <option value="<?php echo $product['product']?>" <?= isset($product['default']) ? 'selected' : ''?>><?php echo $product['product']?></option>
                                <?php endforeach; ?>
                            </select>
                                           </td>
                        <td><input class="required-entry" data-name="loaddate" id="<?php echo $order_id; ?>-loaddate" name="<?php echo $order_id; ?>-loaddate" type="text" value="<?php echo date('Y-m-d',strtotime('now'))?>" autocomplete="off"><div class="copy">⇩</div></td>

                        <td><input class="required-entry" data-date-format="yy-mm-dd" data-name="deliverydate" id="<?php echo $order_id; ?>-deliverydate" name="<?php echo $order_id; ?>-deliverydate" type="text" value="<?php echo date('Y-m-d',strtotime("+1 day", strtotime('now')))?>" autocomplete="off"><div class="copy">⇩</div></td>

                        <script type="text/javascript">// <![CDATA[
                            jQuery('#<?php echo $order_id; ?>-loaddate').datepicker({dateFormat:'yy-mm-dd'});
                            jQuery('#<?php echo $order_id; ?>-deliverydate').datepicker({dateFormat:'yy-mm-dd'});
                            // ]]>
                        </script>


                        <td><input class="required-entry" name="<?php echo $order_id; ?>-reference" type="text" value="webshop-<?php echo $order->get_order_number(); ?>"></td>
                        <td><input class="required-entry" data-name="weight" name="<?php echo $order_id; ?>-weight" type="text" value="<?= ($weight) ? $weight : '0'?>">KG <div class="copy">⇩</div></td>
                        <td>
                            <select class="validate-select required-entry" data-name="allowance" name="<?php echo $order_id; ?>-allowance">
                                <option value="0"><?php echo __('Choose Fee','packs-shipments');?></option>
                            </select>
                                            </td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table>

        </div>
        <p align="right"><input type="submit" value="Publish" tabindex="6" id="submit" name="submit" /></p>

        <?php wp_nonce_field( 'new_shipment' ); ?>
    </form>
    <script>
        jQuery(window).load(function() {
        jQuery('.copy').click(function(){
            var element = jQuery(this).closest('td').find('input,select');
            console.log(element);
            var clickValue = element.val();
            //console.log(clickValue);

            var dataName = element.attr('data-name');
//        alert(clickvalue+':'+dataName);

            if(element.is('select')){
                jQuery('select[data-name='+dataName+']').each(function() {
                    jQuery(this).val(clickValue);
                });
            }else{
                jQuery('input[data-name='+dataName+']').each(function() {
                    jQuery(this).val(clickValue);
                });
            }
        });

        jQuery('input[data-name="loaddate"]').change(function() {
            var loadDate = jQuery(this).val();
            var fieldName = jQuery(this).attr('name');
            var res = fieldName.replace("-loaddate", "");
            var deliveryDate = jQuery("input[name='"+res+"-deliverydate']").val();
            if(new Date(loadDate) >= new Date(deliveryDate)){
                var myDate = new Date(loadDate);
                myDate.setDate(myDate.getDate() + 1);
                /* Als deliverydate is zondag */
                if(myDate.getDay() == 0){
                    myDate.setDate(myDate.getDate() + 1);
                }
                var formattedDate = myDate.getFullYear() + '-' + (myDate.getMonth()+1) + '-' +  myDate.getDate();
                jQuery("input[name='"+res+"-deliverydate']").val(formattedDate);
            }else{
                /*Gelijk of voor vandaag*/
                var today = new Date();
                var yesterday = new Date();
                yesterday.setDate(yesterday.getDate() - 1);
                if(new Date(loadDate) < yesterday){
                    var formattedDate = today.getFullYear() + '-' + (today.getMonth()+1) + '-' +  today.getDate();
                    jQuery("input[name='"+res+"-loaddate']").val(formattedDate);
                    alert('Date cannot be set in the past');
                }
            }
        });

        jQuery('input[data-name="deliverydate"]').change(function() {
            var deliveryDate = jQuery(this).val();
            var fieldName = jQuery(this).attr('name');
            var res = fieldName.replace("-deliverydate", "");
            var loadDate = jQuery("input[name='" + res + "-loaddate']").val();
            if (new Date(deliveryDate) <= new Date(loadDate)) {
                var myDate = new Date(loadDate);
                myDate.setDate(myDate.getDate() + 1);
                console.log(myDate.getDay());
                if (myDate.getDay() == 0) {
                    myDate.setDate(myDate.getDate() + 1);
                }
                var formattedDate = myDate.getFullYear() + '-' + (myDate.getMonth() + 1) + '-' + myDate.getDate();
                jQuery(this).val(formattedDate);
                alert('Date cannot be set before load date');
            }
            /* Als op zondag valt */
            if (new Date(deliveryDate).getDay() == 0) {
                var myDate = new Date(deliveryDate);
                myDate.setDate(myDate.getDate() + 1);
                var formattedDate = myDate.getFullYear() + '-' + (myDate.getMonth() + 1) + '-' + myDate.getDate();
                jQuery(this).val(formattedDate);
                alert('Sunday delivery not possible');

            }
        });
            //let's create arrays
            <?php $orderids = array(); ?>
            <?php foreach ($ids as $order) {
            array_push($orderids, $order);
        }
            ?>
            var orderids = <?php echo '["' . implode('", "', $orderids) . '"]' ?>;

            var products = [
                <?php foreach ($productoptions as $productoption):?>
                {display: "<?php echo $productoption['product'];?>", value: "<?php echo $productoption['productId'];?>"},
                <?php endforeach ?>
            ];





            //If parent option is changed
           jQuery('select[data-name="seal"]').change(function(){
                changeAllowance(jQuery(this));
            });

               var changeAllowance = function changeAllowance(e) {

                <?php foreach ($productoptions as $productoption):?>

                var <?php echo $productoption['product']; ?> =
                [
                    <?php foreach ($productoption['shipmentItemSurcharges'] as $surcharge): ?>
                    {display: "<?php echo $surcharge['surcharge'];?>", value: "<?php echo $surcharge['surchargeId'];?>"},
                    <?php endforeach;?>
                ];

                <?php endforeach; ?>
                var sealValue = jQuery(e).val();
                var fieldName = jQuery(e).attr('name');
                var res = fieldName.replace("-seal", "");
                console.log('red:' +res);
                console.log('red:' +res);
                console.log(eval(sealValue));
                jQuery("select[name='" + res + "-allowance']").html("");
                for (var i = 0; i < eval(sealValue).length; i++) {
                    var value = eval(sealValue)[i].display;
                    console.log(eval(sealValue)[i].display);
                    list(value,res);
                }
                var i = 0;
            };
            var i = 0;

            //function to populate child select box
            function list(arraylist,id) {

                //populate child options
                console.log("select[name='" + id + "-allowance']");
                jQuery("select[name='" + id + "-allowance']").append("<option value=\"" + arraylist + "\">" + arraylist + "</option>");
                //console.log(test);

            }
            changeAllowance(jQuery('select[data-name="seal"]'));
        });

    </script>

</div><!-- .wrap -->
<?php else: ?>
<p><?php echo __('Productoptions Empty, please check if API server is still online or check API URL','packs-shipments');?> </p>
<?php endif; ?>
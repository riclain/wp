<?php
/**
 * Template for the product list
 */
?>

<script type="text/template" id="tmpl-cart-product-item">
	<tr class="item new_row" id="{{cart_item_key}}">
		<td class="thumb">
			{{displayProductItemImage}}			
		</td>
		<td class="name">
			<span>{{displayProductItemTitle}}</span>
			<div class="view">
				{{displayProductItemMeta}}				
			</div>
		</td>
		<td class="edit_link">
			<a href="#" class="add_custom_meta button tips" data-tip="Edit Product"></a>
		</td>
		<td width="1%" class="line_cost">
			<div class="view">
				<input type="text" class="product_price" value="{{cart_item_data.price}}" data-discountsymbol="currency_symbol" data-percent="0" >
			</div>
		</td>
		<td width="1%" class="quantity">
			<div class="edit">
				<input type="text" min="0" autocomplete="off" placeholder="0" value="{{cart_item_data.quantity}}" class="quantity">
			</div>
		</td>
		<td width="1%" class="line_cost_total">
			<div class="view">
				<span class="amount">{{{cart_item_data.formatedprice}}}</span>
			</div>
		</td>
		<td class="remove_item">
			<a href="#" class="remove_order_item tips" data-tip="<?php _e('Remove', 'wc_point_of_sale'); ?>"></a>
		</td>
	</tr>
</script>

<script type="text/template" id="tmpl-cart-customer-item">
	<tr data-customer_id="{{id}}" class="item">
        <td class="avatar">
            <img width="64" height="64" class="avatar avatar-64 photo avatar-default" src="{{avatar_url}}" alt="">
        </td>
        <td class="name">
            <a href="#" class="customer-loaded-name show_customer_popup" >{{fullname}}</a> – 
            <a href="#" class="customer-loaded-email show_customer_popup" >{{email}}</a>
        </td>        
        <td class="remove_customer">
            <a data-tip="<?php _e('Remove', 'wc_point_of_sale'); ?>" class="remove_customer_row tips" href="#"></a>
        </td>
    </tr>
</script>
<script type="text/template" id="tmpl-cart-default-customer-item">
<?php 
$user_to_add = absint($this->data['default_customer']);
if ( $user_to_add > 0) {
    ?>
    <tr data-customer_id="<?php echo $user_to_add; ?>" class="item">
        <td class="avatar">
            <?php echo get_avatar( $user_to_add, 64); ?>
        </td>
        <td class="name">
            <?php if (!$user_to_add) { ?>
                <?php echo $username; ?>
            <?php } else { ?>
                <a href="#" class="customer-loaded-name show_customer_popup" ><?php echo $user_data['first_name'] . ' ' . $user_data['last_name']; ?></a> – 
                <a href="#" class="customer-loaded-email show_customer_popup" ><?php echo $user_data['email']; ?></a>
            <?php } ?>
            <input type="hidden" id="pos_c_user_id" name="user_id" value="<?php echo esc_attr($user_to_add); ?>" />
            <input type="hidden" id="pos_c_user_data" value='<?php echo esc_attr(json_encode($user_data)); ?>' />
            <input type="hidden" id="pos_c_billing_addr" value='<?php echo esc_attr(json_encode($b_addr)); ?>' />
            <input type="hidden" id="pos_c_shipping_addr" value='<?php echo esc_attr(json_encode($s_addr)); ?>' />
        </td>
        
        <td class="remove_customer">
            <a href="#" class="remove_customer_row tips" data-tip="<?php _e('Remove', 'wc_point_of_sale'); ?>"></a>
        </td>
    </tr>
    <?php } else {
    ?>
    <tr class="item new_row" data-customer_id="0">
        <td class="avatar">
            <?php echo get_avatar( 0, 64); ?>
        <td class="name"><?php _e('Guest', 'wc_point_of_sale'); ?></td>
        <td class="remove_customer">
            <a data-tip="<?php _e('Remove', 'wc_point_of_sale'); ?>" class="remove_customer_row tips" href="#"></a>
        </td>
    </tr>
<?php }
?>
</script>
<script type="text/template" id="tmpl-cart-guest-customer-item">
    <tr class="item new_row" data-customer_id="0">
        <td class="avatar">
            <?php echo get_avatar( 0, 64); ?>
        <td class="name"><?php _e('Guest', 'wc_point_of_sale'); ?></td>
        <td class="remove_customer">
            <a data-tip="<?php _e('Remove', 'wc_point_of_sale'); ?>" class="remove_customer_row tips" href="#"></a>
        </td>
    </tr>
</script>
<script type="text/template" id="tmpl-cart-tax-row">
    {{#each this}}
    <tr>
        <th class="tax_label">{{label}}</th>
        <td class="tax_amount"><strong>{{{amount}}}</strong></td> 
    </tr>
    {{/each}}
</script>
<script type="text/template" id="tmpl-cart-ship-row">
    <th class="ship_label"><span id="clear_shipping"><?php _e('Remove', 'wc_point_of_sale'); ?></span> {{title}}</th>
    <td class="ship_amount"><strong>{{{price}}}</strong></td> 
</script>

<script type="text/template" id="tmpl-cart-coupon-code">
    <tr data-coupon="{{coupon_code}}" class="tr_order_coupon order_coupon_{{coupon_code}}">
        <th class="coupon_label"><span class="span_clear_order_coupon"><?php _e('Remove', 'wc_point_of_sale'); ?></span><?php _e('Coupon', 'wc_point_of_sale'); ?></th>
        <td class="coupon_amount">
            <strong>
                <span class="coupon_code">{{coupon_code}}</span>
                <span class="formatted_coupon">{{{amount}}}</span>
            </strong>
        </td>
    </tr>
</script>
<script type="text/template" id="tmpl-cart-pos-discount">
    <tr data-coupon="POS Discount" class="tr_order_coupon order_coupon_pos_discount">
        <th class="coupon_label"><span class="span_clear_order_coupon"><?php _e('Remove', 'wc_point_of_sale'); ?></span>POS Discount</th>
        <td class="coupon_amount">
            <strong>
                <span class="formatted_coupon">{{{amount}}}</span>
            </strong>
        </td>
    </tr>
</script>
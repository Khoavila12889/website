<?php

/**
 * Single variation cart button
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined('ABSPATH') || exit;

global $product;
?>
<div class="woocommerce-variation-add-to-cart variations_button">
	<div class="hidden">
		<?php do_action('woocommerce_before_add_to_cart_button'); ?>

		<?php
		do_action('woocommerce_before_add_to_cart_quantity');

		woocommerce_quantity_input(
			array(
				'min_value'   => apply_filters('woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product),
				'max_value'   => apply_filters('woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product),
				'input_value' => isset($_POST['quantity']) ? wc_stock_amount(wp_unslash($_POST['quantity'])) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
			)
		);

		do_action('woocommerce_after_add_to_cart_quantity');
		?>

		<button type="submit" class="single_add_to_cart_button button alt<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"><?php echo esc_html($product->single_add_to_cart_text()); ?></button>

		<?php do_action('woocommerce_after_add_to_cart_button'); ?>

		<input type="hidden" name="add-to-cart" value="<?php echo absint($product->get_id()); ?>" />
		<input type="hidden" name="product_id" value="<?php echo absint($product->get_id()); ?>" />
		<input type="hidden" name="variation_id" class="variation_id" value="0" />
	</div>

	<div class="product-button button mt-8">
		<?php
		$product_detail_phone_options = get_field("product_detail_phone_options", "options");
		if( !empty($product_detail_phone_options) ):
			echo '<a class="btn-solid btn-contact" href="'.esc_url($product_detail_phone_options['url']).'" target="'.esc_attr($product_detail_phone_options['target']).'"><span>'.esc_html($product_detail_phone_options['title']).'</span></a>';
		endif;
		?>
		<?php
		$product_detail_shop_options = get_field("product_detail_shop_options", "options");
		if( !empty($product_detail_shop_options) ):
			echo '<a class="btn-solid btn-store" href="'.esc_url($product_detail_shop_options['url']).'" target="'.esc_attr($product_detail_shop_options['target']).'"><span>'.esc_html($product_detail_shop_options['title']). '</span><i class="fa-light fa-chevron-right"></i></a>';
		endif;
		?>
	</div>
</div>

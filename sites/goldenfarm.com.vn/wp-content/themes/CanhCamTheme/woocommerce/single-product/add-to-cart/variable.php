<?php

/**
 * Variable product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/variable.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 6.1.0
 */

defined('ABSPATH') || exit;

global $product;

$attribute_keys  = array_keys($attributes);
$variations_json = wp_json_encode($available_variations);
$variations_attr = function_exists('wc_esc_json') ? wc_esc_json($variations_json) : _wp_specialchars($variations_json, ENT_QUOTES, 'UTF-8', true);

// check count of variations
$count_variations = count($available_variations);

do_action('woocommerce_before_add_to_cart_form'); ?>

<form class="variations_form cart" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint($product->get_id()); ?>" data-product_variations="<?php echo $variations_attr; // WPCS: XSS ok.
																																																																						?>">
	<?php do_action('woocommerce_before_variations_form'); ?>

	<?php if (empty($available_variations) && false !== $available_variations) : ?>
		<p class="stock out-of-stock"><?php echo esc_html(apply_filters('woocommerce_out_of_stock_message', __('This product is currently out of stock and unavailable.', 'woocommerce'))); ?></p>
	<?php else : ?>
		<table class="variations" cellspacing="0" role="presentation">
			<tbody>
				<?php foreach ($attributes as $attribute_name => $options) : ?>
					<tr>
						<th class="label"><label for="<?php echo esc_attr(sanitize_title($attribute_name)); ?>">
							<?php 
						echo ($attribute_name === 'pa_dung-tich') 
							? 'Quy cách:' 
							: wc_attribute_label($attribute_name); 
							?>																					
																												
							</label></th>
						<td class="value">
							<?php
							wc_dropdown_variation_attribute_options(
								array(
									'options'   => $options,
									'attribute' => $attribute_name,
									'product'   => $product,
								)
							);
							echo end($attribute_keys) === $attribute_name ? wp_kses_post(apply_filters('woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . esc_html__('Clear', 'woocommerce') . '</a>')) : '';
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php do_action('woocommerce_after_variations_table'); ?>

		<div class="single_variation_wrap <?php echo $count_variations == 1 ? 'hidden' : '' ?>">
			<?php if ($count_variations == 1) : ?>
				<div class="product-price-group">
					<div class="product-label"><?php echo _e('Giá tham khảo:', 'canhcamtheme') ?></div>
					<div class="product-price"><span><?php echo $product->get_price_html() ?></span></div>
				</div>
				<div class="product-button button mt-8">
					<?php
					$product_detail_phone_options = get_field("product_detail_phone_options", "options");
					if (!empty($product_detail_phone_options)) :
						echo '<a class="btn-solid btn-contact" href="' . esc_url($product_detail_phone_options['url']) . '" target="' . esc_attr($product_detail_phone_options['target']) . '"><span>' . esc_html($product_detail_phone_options['title']) . '</span></a>';
					endif;
					?>
					<?php
					$product_detail_shop_options = get_field("product_detail_shop_options", "options");
					if (!empty($product_detail_shop_options)) :
						echo '<a class="btn-solid btn-store" href="' . esc_url($product_detail_shop_options['url']) . '" target="' . esc_attr($product_detail_shop_options['target']) . '"><span>' . esc_html($product_detail_shop_options['title']) . '</span><i class="fa-light fa-chevron-right"></i></a>';
					endif;
					?>
				</div>
			<?php endif; ?>
			<div class="<?php echo $count_variations == 1 ? 'hidden' : '' ?>">
				<?php
				/**
				 * Hook: woocommerce_before_single_variation.
				 */
				do_action('woocommerce_before_single_variation');

				/**
				 * Hook: woocommerce_single_variation. Used to output the cart button and placeholder for variation data.
				 *
				 * @since 2.4.0
				 * @hooked woocommerce_single_variation - 10 Empty div for variation data.
				 * @hooked woocommerce_single_variation_add_to_cart_button - 20 Qty and cart button.
				 */
				do_action('woocommerce_single_variation');

				/**
				 * Hook: woocommerce_after_single_variation.
				 */
				do_action('woocommerce_after_single_variation');
				?>
			</div>
		</div>
	<?php endif; ?>

	<?php do_action('woocommerce_after_variations_form'); ?>
</form>

<?php
do_action('woocommerce_after_add_to_cart_form');

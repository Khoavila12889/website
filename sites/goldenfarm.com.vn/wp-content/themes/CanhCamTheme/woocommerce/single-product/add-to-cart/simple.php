<?php

/**
 * Simple product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/simple.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined('ABSPATH') || exit;

global $product;
?>
<div class="product-detail-info hidden">
	<?php
	// Display weight
	if ($product->has_weight()) : ?>
		<div class="info-item">
			<div class="label">
				<?php _e('Khối lượng', 'woocommerce'); ?>
			</div>
			<div class="content">
				<?php echo esc_html(wc_format_localized_decimal($product->get_weight())) . ' ' . esc_attr(get_option('woocommerce_weight_unit')); ?>
			</div>
		</div>
	<?php endif;
	// Display SKU
	if (wc_product_sku_enabled() && ($product->get_sku() || $product->is_type('variable'))) : ?>
		<div class="info-item">
			<div class="label">
				<?php _e('Mã sản phẩm', 'woocommerce'); ?>
			</div>
			<div class="content">
				<?php echo ($sku = $product->get_sku()) ? $sku : __('N/A', 'woocommerce'); ?>
			</div>
		</div>
	<?php endif;
	?>
</div>
<?php



if (!$product->is_purchasable()) {
	return;
}

echo wc_get_stock_html($product); // WPCS: XSS ok.

if ($product->is_in_stock()) : ?>

	<?php do_action('woocommerce_before_add_to_cart_form'); ?>

	<form class="cart" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post" enctype='multipart/form-data'>
		<?php do_action('woocommerce_before_add_to_cart_button'); ?>

		<?php
		do_action('woocommerce_before_add_to_cart_quantity');

		woocommerce_quantity_input(
			array(
				'min_value' => apply_filters('woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product),
				'max_value' => apply_filters('woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product),
				'input_value' => isset($_POST['quantity']) ? wc_stock_amount(wp_unslash($_POST['quantity'])) : $product->get_min_purchase_quantity(),
				// WPCS: CSRF ok, input var ok.
			)
		);

		do_action('woocommerce_after_add_to_cart_quantity');
		?>
		<div class="product-button button">
			<button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="single_add_to_cart_button btn btn-add-to-cart alt<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?> hidden">
				<?php _e("Thêm vào giỏ hàng", "woocommerce") ?>
			</button>

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

		<?php do_action('woocommerce_after_add_to_cart_button'); ?>
	</form>

	<?php do_action('woocommerce_after_add_to_cart_form'); ?>

<?php endif; ?>

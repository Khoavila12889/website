<?php

/**
 * Single Product Image
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-image.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.8.0
 */

defined('ABSPATH') || exit;

// Note: `wc_get_gallery_image_html` was added in WC 3.3.2 and did not exist prior. This check protects against theme overrides being used on older versions of WC.
if (!function_exists('wc_get_gallery_image_html')) {
	return;
}

global $product;

$columns = apply_filters('woocommerce_product_thumbnails_columns', 4);
$post_thumbnail_id = $product->get_image_id();
$wrapper_classes = apply_filters(
	'woocommerce_single_product_image_gallery_classes',
	array(
		'woocommerce-product-gallery',
		'woocommerce-product-gallery--' . ($post_thumbnail_id ? 'with-images' : 'without-images'),
		'woocommerce-product-gallery--columns-' . absint($columns),
		'images',
	)
);

$image_id = get_post_thumbnail_id();
$image_url = wp_get_attachment_image_src($image_id, 'full');

// product image
$product_image = get_the_post_thumbnail_url($product->get_id(), 'full');
?>
<div class="swiper-relative product-detail-slider">
	<div class="swiper product-detail-top">
		<div class="swiper-wrapper">
			<?php if ($product_image) : ?>
				<div class="swiper-slide">
					<div class="image img-contain">
						<img src="<?php echo esc_url($product_image); ?>" alt="top">
					</div>
				</div>
			<?php endif ?>
			<?php
			$attachment_ids = $product->get_gallery_image_ids();
			foreach ($attachment_ids as $attachment_id) {
				$gallery_image_url = wp_get_attachment_url($attachment_id);
				echo '<div class="swiper-slide">
						<div class="image img-contain">
							<img src="' . esc_url($gallery_image_url) . '" alt="top">
						</div>
					</div>';
			}
			?>
			<?php if ($product->is_type('variable')) {
				$variations = $product->get_available_variations();

				if (!empty($variations)) {
					foreach ($variations as $variation) {
						// Get the variation image URL
						$variation_title = '';
						foreach ($variation['attributes'] as $key => $value) {
							$variation_title .= ' ' . $value . ' ';
						}
						$variation_image_url = $variation['image']['url'];
						// Output the variation image
						if ($variation_image_url) {
			?>
							<div class="swiper-slide <?php echo $variation_title; ?>">
								<div class="image img-contain">
									<img src="<?php echo esc_url($variation_image_url); ?>" alt="top">
								</div>
							</div>
			<?php
						}
					}
				}
			} ?>
		</div>
	</div>
	<?php
	do_action('woocommerce_product_thumbnails');
	?>
</div>

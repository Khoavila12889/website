<?php

/**
 * Single Product Thumbnails
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-thumbnails.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     3.5.1
 */

defined('ABSPATH') || exit;

// Note: `wc_get_gallery_image_html` was added in WC 3.3.2 and did not exist prior. This check protects against theme overrides being used on older versions of WC.
if (!function_exists('wc_get_gallery_image_html')) {
	return;
}

global $product;
$attachment_ids = $product->get_gallery_image_ids();

// product image
$product_image = get_the_post_thumbnail_url($product->get_id(), 'full');
?>

<div class="swiper product-detail-thumbs">
	<div class="swiper-wrapper">
		<?php if ($product_image) : ?>
			<div class="swiper-slide">
				<div class="image img-contain">
					<img src="<?php echo esc_url($product_image); ?>" alt="thumb">
				</div>
			</div>
		<?php endif ?>
		<?php
		foreach ($attachment_ids as $attachment_id) {
			$thumb_image_url = wp_get_attachment_url($attachment_id);
			echo '<div class="swiper-slide">
					<div class="image img-contain">
						<img src="' . esc_url($thumb_image_url) . '" alt="thumb">
					</div>
				</div>';
		}
		?>
		<?php if ($product->is_type('variable')) {
			$variations = $product->get_available_variations();

			if (!empty($variations)) {
				foreach ($variations as $variation) {
					// Get the variation image URL
					$variation_image_url = $variation['image']['url'];

					// Output the variation image
					if ($variation_image_url) {
		?>
						<div class="swiper-slide">
							<div class="image img-contain">
								<img src="<?php echo esc_url($variation_image_url); ?>" alt="thumb">
							</div>
						</div>
		<?php
					}
				}
			}
		} ?>
	</div>
</div>

<div class="swiper-button">
	<div class="button-prev"><i class="fa-light fa-chevron-left"></i></div>
	<div class="button-next"><i class="fa-light fa-chevron-right"></i></div>
</div>

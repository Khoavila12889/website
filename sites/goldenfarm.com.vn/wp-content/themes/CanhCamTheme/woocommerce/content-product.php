<?php

/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined('ABSPATH') || exit;

global $product;

$category = get_the_terms($product->get_ID(), 'product_cat');
// get category in post_type=product_variation
if (!$category) {
	$parent_id = wp_get_post_parent_id($product->get_ID());
	$category = get_the_terms($parent_id, 'product_cat');
}


if ($category) {
	$current_category = $category[0];
	$category_id = $current_category->term_id;
	$category_name = $current_category->name;
	$category_parent = $current_category->parent; // ID of parent category
	$thumbnail_url = get_term_meta($current_category->term_id, 'thumbnail_id', true);
	// Check if the category has a parent
	if ($category_parent) {
		$category_parent_id = get_term($category_parent, 'product_cat')->term_id;
		$category_name = get_term($category_parent, 'product_cat')->name;
		$category_thumbnail = get_term_meta($category_parent_id, 'thumbnail_id', true);
		$thumbnail_url = wp_get_attachment_url($category_thumbnail);

		// Check if the parent category is a top-level category
		$category_parent_depth = count(get_ancestors($category_parent_id, 'product_cat'));
		if ($category_parent_depth === 0) {
			// Category parent is a top-level category, get thumbnail and name of current category
			$category_name = $current_category->name;
			$category_thumbnail = get_term_meta($current_category->term_id, 'thumbnail_id', true);
			$thumbnail_url = wp_get_attachment_url($category_thumbnail);
		}
	}

	// ACF
	$choose_brand_color = get_field('choose_brand_color', $current_category);
}
?>
<div <?php wc_product_class('product-item is-primary-' . $choose_brand_color, $product); ?>>
	<div class="brand">
		<img src="<?php echo $thumbnail_url; ?>" alt="<?php echo $category_name; ?>">
	</div>
	<div class="image img-contain">
		<a href="<?php the_permalink(); ?>">
			<!-- Get product image -->
			<?php
        $image_id = get_post_thumbnail_id();
        $image_url = wp_get_attachment_image_src($image_id, 'full');
        
        // ✅ FIX: Kiểm tra an toàn trước khi truy cập array
        if ($image_url && is_array($image_url) && !empty($image_url[0])) {
            $src = esc_url($image_url[0]);
            $alt = esc_attr(get_the_title());
        } else {
            // Sử dụng ảnh placeholder của WooCommerce
            $src = wc_placeholder_img_src('woocommerce_thumbnail');
            $alt = esc_attr(get_the_title()) . ' - ' . __('No image', 'woocommerce');
        }
        ?>
		
		    <?php 
			$image_url = wp_get_attachment_image_src($image_id, 'full');

			if ($image_url && is_array($image_url) && !empty($image_url[0])) {
				$src = esc_url($image_url[0]);
				$alt = esc_attr(get_the_title());
			} else {
				$src = wc_placeholder_img_src('woocommerce_thumbnail');
				$alt = esc_attr(get_the_title()) . ' - ' . __('No image', 'woocommerce');
			}

			?>
			<img src="<?php echo $src; ?>" alt="<?php echo $alt; ?>">
		</a>
		<!-- <ul class="action-list">
			<li>
				<a class="quick-view" href="<?php // the_permalink();
											?>">
					<i class="fa-solid fa-eye"></i>
				</a>
			</li>
			<li>
				<a
					class="add-to-cart button product_type_simple add_to_cart_button ajax_add_to_cart"
					data-product_id="<?php // echo $product->get_ID();
										?>"
					data-product_sku="<?php // echo $product->get_sku();
										?>"
					aria-label="Add “<?php // the_title();
										?>” to your cart"
					aria-describedby=""
					rel="nofollow"
					href="<?php // echo esc_url($product->add_to_cart_url());
							?>"
				>
					<i class="fa-solid fa-cart-plus"></i>
				</a>
			</li>
		</ul> -->
	</div>
	<div class="caption">
		<h3 class="product-title">
			<a href="<?php the_permalink(); ?>">
				<?php the_title(); ?>
			</a>
			<div class="product-information">
			<?php
				outputOptions(get_the_ID())
			?>
			</div>
		</h3>
		<div class="price">
			<?php
			$product_id = $product->get_id(); // Replace with the actual product ID

			$product = wc_get_product($product_id);

			if ($product && $product->is_type('variable')) {
				// $variation_id = null;
				// Get the default attributes
				$default_attributes = $product->get_default_attributes();

				// Find the variation ID that matches the default attributes
				$variation_id = find_matching_product_variation_id($product, $default_attributes);

				// Optionally, load the variation object
				$variation = wc_get_product($variation_id);

				if ($variation_id) {
					$variation = wc_get_product($variation_id);
					$variation_price = $variation->get_price();
					echo wc_price($variation_price);

				}else{
					// Get the first available variation ID
					$variation_id = null;
					foreach ($product->get_children() as $child_id) {
						$variation = wc_get_product($child_id);
						if ($variation && $variation->is_type('variation')) {
							$variation_id = $variation->get_id();
							$variation_price = $variation->get_price();
							echo wc_price($variation_price);
							break;
						}
					}
				}
			}
			else {
				echo $product->get_price_html();
			}
			?>
		</div>
	</div>
</div>

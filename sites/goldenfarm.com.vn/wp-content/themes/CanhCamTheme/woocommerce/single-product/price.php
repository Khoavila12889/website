<?php

/**
 * Single Product Price
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/price.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

global $product;

?>
<div class="<?php echo esc_attr(apply_filters('woocommerce_product_price_class', 'price')); ?> product-detail-price product-price-group product-single-price <?php echo $product->is_type('variable') ? 'is-product-hidden' : '' ?>">
	<div class="product-label"><?php echo _e('Giá tham khảo', 'canhcamtheme') ?></div>
	<div class="product-price"><span><?php echo $product->get_price_html(); ?></span></div>
</div>

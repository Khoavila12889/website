<?php
/**
 * Product Filter Menu Template
 *
 * Displays hierarchical product category filter tree with menu-style UI.
 * Uses GoldenFarm Product Filter plugin for rendering, falls back to YITH.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/product-filter-menu.php.
 *
 * @package CanhCamTheme\Templates
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Try GoldenFarm Product Filter first, fallback to YITH
if ( function_exists( 'gf_pf_render_filters' ) ) {
	// Use GoldenFarm filter (native product_cat, SEO-friendly URLs)
	gf_pf_render_filters( array(
		'title'    => __( 'Danh mục sản phẩm', 'canhcamtheme' ),
		'multiple' => 'yes',  // Allow multiple category selection
	) );
} else {
	// Fallback to YITH shortcode if plugin not active
	echo do_shortcode( '[yith_wcan_filters slug="draft-preset"]' );
}
?>

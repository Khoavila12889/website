<?php
/**
 * Enqueues the plugin assets (CSS + JS) on product archive pages.
 *
 * @package GF_PF
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class GF_PF_Assets
 */
class GF_PF_Assets {

	/**
	 * Whether to enqueue assets on the current request.
	 *
	 * @return bool
	 */
	public static function should_enqueue() {
		return is_shop()
			|| is_post_type_archive( 'product' )
			|| is_tax( 'product_cat' )
			|| is_tax( 'product_tag' )
			|| apply_filters( 'gf_pf_enqueue', false );
	}

	/**
	 * Enqueues the filter CSS/JS when the filter can appear on the page.
	 *
	 * @return void
	 */
	public static function maybe_enqueue() {
		if ( ! self::should_enqueue() ) {
			return;
		}

		wp_enqueue_style(
			'gf-pf',
			GF_PF_PLUGIN_URL . 'assets/css/gf-pf.css',
			array(),
			GF_PF_VERSION
		);

		wp_enqueue_script(
			'gf-pf',
			GF_PF_PLUGIN_URL . 'assets/js/gf-pf.js',
			array( 'jquery' ),
			GF_PF_VERSION,
			true
		);
	}
}
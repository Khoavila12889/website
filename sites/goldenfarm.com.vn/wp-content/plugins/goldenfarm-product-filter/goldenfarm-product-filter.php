<?php
/**
 * Plugin Name: GoldenFarm Product Filter
 * Description: Replaces the YITH AJAX Product Filter UI with a lightweight, native product_cat filter tree. Keeps the WooCommerce native query (product_cat query var), renders a hierarchical checkbox tree with YITH-compatible markup so the theme CSS keeps working, and uses clean cacheable URLs (?product_cat=<slugs>) without the yith_wcan param.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Author: GoldenFarm Dev
 * Text Domain: goldenfarm-product-filter
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package GF_PF
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'GF_PF_VERSION' ) ) {
	define( 'GF_PF_VERSION', '1.0.0' );
}

if ( ! defined( 'GF_PF_PLUGIN_FILE' ) ) {
	define( 'GF_PF_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'GF_PF_PLUGIN_DIR' ) ) {
	define( 'GF_PF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'GF_PF_PLUGIN_URL' ) ) {
	define( 'GF_PF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

require_once GF_PF_PLUGIN_DIR . 'includes/class-gf-pf-terms.php';
require_once GF_PF_PLUGIN_DIR . 'includes/class-gf-pf-renderer.php';
require_once GF_PF_PLUGIN_DIR . 'includes/class-gf-pf-assets.php';

/**
 * Main plugin controller.
 *
 * Registers the public render function and the shortcode used to replace the
 * YITH shortcode in woocommerce/archive-product.php.
 *
 * @since 1.0.0
 */
final class GF_PF_Plugin {

	/**
	 * Single instance.
	 *
	 * @var GF_PF_Plugin
	 */
	private static $instance;

	/**
	 * Singleton accessor.
	 *
	 * @return GF_PF_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hook everything in.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( 'GF_PF_Assets', 'maybe_enqueue' ), 20 );
		add_filter( 'do_shortcode_tag', array( $this, 'replace_yith_shortcode' ), 10, 3 );

		add_action( 'created_product_cat', array( $this, 'invalidate_term_cache' ) );
		add_action( 'edited_product_cat', array( $this, 'invalidate_term_cache' ) );
		add_action( 'delete_product_cat', array( $this, 'invalidate_term_cache' ) );
		add_action( 'set_object_terms', array( $this, 'invalidate_term_cache' ) );
	}

	/**
	 * Auto-replace [yith_wcan_filters] with [goldenfarm_product_filter].
	 *
	 * @param string|false $return Shortcode return value. False to skip.
	 * @param string $tag Shortcode name.
	 * @param array $attr Shortcode attributes.
	 * @return string|false
	 */
	public function replace_yith_shortcode( $return, $tag, $attr ) {
		if ( 'yith_wcan_filters' !== $tag ) {
			return $return;
		}

		// Use goldenfarm_product_filter instead
		return GF_PF_Renderer::shortcode( $attr );
	}

	/**
	 * Invalidates the term tree cache when taxonomy data changes.
	 *
	 * @return void
	 */
	public function invalidate_term_cache() {
		GF_PF_Terms::invalidate_cache( GF_PF_Terms::taxonomy() );
	}

	/**
	 * Registers the [goldenfarm_product_filter] shortcode.
	 *
	 * @return void
	 */
	public function register_shortcode() {
		add_shortcode( 'goldenfarm_product_filter', array( 'GF_PF_Renderer', 'shortcode' ) );
	}
}

GF_PF_Plugin::instance();

/**
 * Renders the GoldenFarm product filter tree.
 *
 * Replaces `do_shortcode('[yith_wcan_filters slug="draft-preset"]')` in
 * woocommerce/archive-product.php. Renders the same hierarchical product_cat
 * checkbox tree with YITH-compatible classes so existing theme CSS applies.
 *
 * @return void Echoes the filter markup.
 */
function gf_pf_render_filters() {
	GF_PF_Renderer::render();
}
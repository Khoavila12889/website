<?php
/**
 * Plugin Name: GoldenFarm Product Filter
 * Description: Renders a lightweight, native 3-level product category tree (Brand -> Category Group -> Products).
 * Version: 1.3.0
 * Author: GoldenFarm Dev
 * Text Domain: goldenfarm-product-filter
 * License: GPLv2 or later
 *
 * @package GF_PF
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'GF_PF_VERSION' ) ) {
	define( 'GF_PF_VERSION', '1.3.0' );
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
require_once GF_PF_PLUGIN_DIR . 'includes/class-gf-pf-admin.php';

/**
 * Main plugin controller.
 */
final class GF_PF_Plugin {

	private static $instance;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( 'GF_PF_Assets', 'maybe_enqueue' ), 20 );
		add_filter( 'do_shortcode_tag', array( $this, 'replace_yith_shortcode' ), 10, 3 );

		// Admin settings page.
		GF_PF_Admin::init();

		// Invalidate category cache on term changes
		add_action( 'created_product_cat', array( $this, 'invalidate_term_cache' ) );
		add_action( 'edited_product_cat', array( $this, 'invalidate_term_cache' ) );
		add_action( 'delete_product_cat', array( $this, 'invalidate_term_cache' ) );
		add_action( 'set_object_terms', array( $this, 'invalidate_term_cache' ) );
	}

	public function replace_yith_shortcode( $return, $tag, $attr ) {
		if ( 'yith_wcan_filters' !== $tag ) {
			return $return;
		}

		return GF_PF_Renderer::shortcode( $attr );
	}

	public function invalidate_term_cache() {
		GF_PF_Terms::invalidate_cache();
	}

	public function register_shortcode() {
		add_shortcode( 'goldenfarm_product_filter', array( 'GF_PF_Renderer', 'shortcode' ) );
	}
}

GF_PF_Plugin::instance();

// Clear cache upon activation/deactivation
register_activation_hook( GF_PF_PLUGIN_FILE, array( 'GF_PF_Terms', 'invalidate_cache' ) );
register_deactivation_hook( GF_PF_PLUGIN_FILE, array( 'GF_PF_Terms', 'invalidate_cache' ) );

/**
 * Render helper.
 */
function gf_pf_render_filters( $args = array() ) {
	GF_PF_Renderer::render( $args );
}
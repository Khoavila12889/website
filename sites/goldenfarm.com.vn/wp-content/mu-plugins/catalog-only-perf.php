<?php
/**
 * Plugin Name: GoldenFarm Catalog Only + Perf
 * Description: Converts WooCommerce into a catalog-only engine (no cart/checkout), disables unused features and trims front-end assets for speed.
 * Version: 1.0.0
 * Author: GoldenFarm Dev
 * License: GPLv2 or later
 */

defined( 'ABSPATH' ) || exit;

final class GF_Catalog_Only {

	private static $instance;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->hooks();
	}

	private function hooks() {
		add_action( 'init', array( $this, 'disable_woo_features' ), 5 );
		add_action( 'template_redirect', array( $this, 'redirect_account_pages' ) );
		add_action( 'template_redirect', array( $this, 'block_add_to_cart' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_assets' ), 999 );
		add_filter( 'wp_enqueue_scripts', array( $this, 'dequeue_assets' ), 999 );
		add_action( 'enqueue_block_assets', array( $this, 'dequeue_block_assets' ), 999 );
		add_action( 'wp_enqueue_scripts', array( $this, 'strip_asset_dependencies' ), 100000 );
		add_filter( 'print_styles_array', array( $this, 'filter_print_styles' ), 999 );
		add_filter( 'print_scripts_array', array( $this, 'filter_print_scripts' ), 999 );

		add_filter( 'woocommerce_is_purchasable', '__return_false', 10, 1 );
		add_filter( 'woocommerce_variation_is_purchasable', '__return_false', 10, 1 );
		add_filter( 'woocommerce_variable_is_purchasable', '__return_false', 10, 1 );
		add_filter( 'woocommerce_add_to_cart_validation', '__return_false', 10, 1 );
		add_filter( 'woocommerce_cart_needs_payment', '__return_false' );
		add_filter( 'woocommerce_cart_ready_to_calc_shipping', '__return_false' );
		add_filter( 'woocommerce_enable_ajax_add_to_cart', '__return_false' );
		add_filter( 'woocommerce_enable_coupons', '__return_false' );
		add_filter( 'woocommerce_coupons_enabled', '__return_false' );

		add_filter( 'woocommerce_show_page_title', '__return_false' );
		add_filter( 'woocommerce_redirect_single_search_result', '__return_false' );

		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		remove_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
		remove_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30 );
		remove_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );
		remove_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30 );
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
		remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description' );

		add_action( 'template_redirect', array( $this, 'kill_heartbeat' ) );
		add_action( 'init', array( $this, 'kill_heartbeat' ) );

		add_filter( 'option_woocommerce_enable_guest_checkout', '__return_false' );
		add_filter( 'pre_option_woocommerce_enable_myaccount_registration', '__return_false' );
		add_filter( 'woocommerce_enable_myaccount_registration', '__return_false' );

		add_filter( 'woocommerce_prevent_admin_access', '__return_false', 10, 1 );
		add_filter( 'woocommerce_disable_admin_bar', '__return_false', 10, 1 );

		add_filter( 'rest_endpoints', array( $this, 'remove_woo_rest_endpoints' ), 10, 1 );
		add_filter( 'rest_pre_dispatch', array( $this, 'block_guest_woo_rest' ), 10, 3 );

		add_action( 'init', array( $this, 'disable_feeds' ) );
		add_filter( 'feed_content_type', array( $this, 'disable_feeds' ), 10, 1 );
		add_filter( 'wp_headers', array( $this, 'remove_pingback_header' ), 10, 1 );
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'pre_update_option', array( $this, 'prevent_checkout_enable' ), 10, 2 );

		add_action( 'wp_footer', array( $this, 'output_inline_hide_buttons' ), 5 );
	}

	public function disable_woo_features() {
		remove_action( 'init', 'woocommerce_add_to_cart_action' );
		remove_action( 'init', 'woocommerce_add_to_cart_redirect' );
		remove_action( 'init', 'woocommerce_register_shortcodes' );
		remove_action( 'widgets_init', array( 'WC_Shortcodes', 'init' ) );
	}

	public function redirect_account_pages() {
		if ( ! is_admin() && function_exists( 'wc_get_page_id' ) ) {
			if ( is_cart() || is_checkout() || is_account_page() || is_wc_endpoint_url() ) {
				wp_safe_redirect( home_url( '/' ), 301 );
				exit;
			}
		}
	}

	public function block_add_to_cart() {
		if ( is_admin() || empty( $_GET['add-to-cart'] ) ) {
			return;
		}
		wp_safe_redirect( remove_query_arg( 'add-to-cart' ), 302 );
		exit;
	}

	public function dequeue_assets() {
		wp_dequeue_style( 'wc-blocks-style' );
		wp_dequeue_style( 'wc-blocks-vendors-style' );
		wp_dequeue_style( 'wc-blocks-editor' );
		wp_dequeue_style( 'wc-blocks-packages-style' );
		wp_dequeue_style( 'wc-blocks-vendors' );
		wp_dequeue_style( 'wc-block-vendors-style' );
		wp_dequeue_style( 'wc-block-editor' );
		wp_dequeue_style( 'wc-block-style' );
		wp_dequeue_style( 'wc-block-vendors' );
		wp_dequeue_style( 'woocommerce-layout' );
		wp_dequeue_style( 'woocommerce-smallscreen' );
		wp_dequeue_style( 'woocommerce-inline' );
		wp_dequeue_style( 'dashicons' );
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'wc-global-style' );

		wp_dequeue_script( 'wc-cart-fragments' );
		wp_dequeue_script( 'wc-add-to-cart' );
		wp_dequeue_script( 'wc-add-to-cart-variation' );
		wp_dequeue_script( 'wc-single-product' );
		wp_dequeue_script( 'wc-checkout' );
		wp_dequeue_script( 'wc-country-select' );
		wp_dequeue_script( 'wc-address-i18n' );
		wp_dequeue_script( 'wc-credit-card-form' );
		wp_dequeue_script( 'wc-password-strength-meter' );
		wp_dequeue_script( 'selectWoo' );
		wp_dequeue_script( 'select2' );
		wp_dequeue_script( 'wc-zoom' );
		wp_dequeue_script( 'wc-flexslider' );
		wp_dequeue_script( 'wc-magnific-popup' );

		wp_dequeue_script( 'jquery-blockui' );
		wp_dequeue_script( 'jquery-placeholder' );
		wp_dequeue_script( 'js-cookie' );
		wp_dequeue_script( 'js-cookie-js' );
	}

	public function dequeue_block_assets() {
		wp_dequeue_style( 'wc-blocks-style' );
		wp_dequeue_style( 'wc-blocks-vendors-style' );
		wp_dequeue_style( 'wc-blocks-packages-style' );
		wp_dequeue_style( 'wc-block-vendors-style' );
		wp_dequeue_style( 'wc-block-editor' );
		wp_dequeue_style( 'wc-block-style' );
		wp_dequeue_style( 'wc-blocks-vendors' );
		wp_dequeue_script( 'wc-blocks' );
		wp_dequeue_script( 'wc-blocks-middleware' );
		wp_dequeue_script( 'wc-blocks-data-store' );
	}

	public function strip_asset_dependencies() {
		global $wp_scripts, $wp_styles;
		$dead_handles = array(
			'js-cookie',
			'js-cookie-js',
			'wc-cart-fragments',
			'wc-add-to-cart',
			'wc-add-to-cart-variation',
			'wc-single-product',
			'wc-checkout',
			'selectWoo',
			'select2',
			'jquery-blockui',
			'jquery-placeholder',
			'wc-zoom',
			'zoom',
			'wc-flexslider',
			'wc-magnific-popup',
		);
		if ( $wp_scripts instanceof WP_Scripts ) {
			foreach ( $wp_scripts->registered as $handle => $script ) {
				if ( $script->deps ) {
					$script->deps = array_values( array_diff( $script->deps, $dead_handles ) );
				}
				if ( in_array( $handle, $dead_handles, true ) ) {
					unset( $wp_scripts->registered[ $handle ] );
				}
			}
		}
		$dead_styles = array(
			'wc-blocks-style',
			'wc-blocks-packages-style',
			'wc-blocks-vendors-style',
			'wc-block-vendors-style',
			'woocommerce-layout',
			'woocommerce-smallscreen',
			'dashicons',
			'wp-block-library',
			'wp-block-library-theme',
			'wc-global-style',
			'select2',
			'selectWoo',
		);
		if ( $wp_styles instanceof WP_Styles ) {
			foreach ( $wp_styles->registered as $handle => $style ) {
				if ( $style->deps ) {
					$style->deps = array_values( array_diff( $style->deps, $dead_styles ) );
				}
				if ( in_array( $handle, $dead_styles, true ) ) {
					unset( $wp_styles->registered[ $handle ] );
				}
			}
		}
	}

	public function filter_print_styles( $handles ) {
		if ( is_admin() ) {
			return $handles;
		}
		$remove = array(
			'wc-blocks-style',
			'wc-blocks-packages-style',
			'wc-blocks-vendors-style',
			'wc-block-vendors-style',
			'woocommerce-layout',
			'woocommerce-smallscreen',
			'dashicons',
			'wp-block-library',
			'wp-block-library-theme',
			'wc-global-style',
			'select2',
			'selectWoo',
		);
		return array_values( array_diff( $handles, $remove ) );
	}

	public function filter_print_scripts( $handles ) {
		if ( is_admin() ) {
			return $handles;
		}
		$remove = array(
			'js-cookie',
			'js-cookie-js',
			'wc-cart-fragments',
			'wc-add-to-cart',
			'wc-add-to-cart-variation',
			'wc-single-product',
			'wc-checkout',
			'selectWoo',
			'select2',
			'jquery-blockui',
			'jquery-placeholder',
			'wc-zoom',
			'zoom',
			'wc-flexslider',
			'wc-magnific-popup',
		);
		return array_values( array_diff( $handles, $remove ) );
	}

	public function kill_heartbeat() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_deregister_script( 'heartbeat' );
		}
	}

	public function remove_woo_rest_endpoints( $endpoints ) {
		$remove = array( 'cart', 'cart/add-item', 'cart/coupon', 'checkout', 'payment-gateways', 'order-attempts', 'shipping/zones' );
		foreach ( $remove as $endpoint ) {
			foreach ( $endpoints as $route => $handlers ) {
				if ( false !== strpos( $route, '/wc/store/v1/' . $endpoint ) ) {
					unset( $endpoints[ $route ] );
				}
			}
		}
		return $endpoints;
	}

	public function block_guest_woo_rest( $response, $server, $request ) {
		if ( is_user_logged_in() ) {
			return $response;
		}
		$route = $request->get_route();
		if ( false !== strpos( $route, '/wc/store/' ) ) {
			return new WP_Error( 'rest_no_route', __( 'No route was found matching the URL and request method.' ), array( 'status' => 404 ) );
		}
		return $response;
	}

	public function disable_feeds() {
		remove_action( 'do_feed_rdf', 'do_feed_rdf', 10 );
		remove_action( 'do_feed_rss', 'do_feed_rss', 10 );
		remove_action( 'do_feed_rss2', 'do_feed_rss2', 10 );
		remove_action( 'do_feed_atom', 'do_feed_atom', 10 );
		remove_action( 'do_feed_rss2_comments', 'do_feed_rss2_comments', 10 );
		remove_action( 'do_feed_atom_comments', 'do_feed_atom_comments', 10 );
	}

	public function remove_pingback_header( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}

	public function prevent_checkout_enable( $value, $option ) {
		if ( 'woocommerce_enable_guest_checkout' === $option ) {
			return 'no';
		}
		if ( 'woocommerce_calc_shipping' === $option ) {
			return 'no';
		}
		return $value;
	}

	public function output_inline_hide_buttons() {
		if ( is_admin() ) {
			return;
		}
		echo '<style>.single_add_to_cart_button,.add_to_cart_button,.add_to_cart_button_wrapper,.woocommerce-variation-add-to-cart,.cart,.woocommerce-cart-form,form.cart{display:none!important}</style>';
	}
}

GF_Catalog_Only::instance();

add_filter( 'woocommerce_enable_setup_wizard', '__return_false' );
add_filter( 'woocommerce_show_admin_bar_alert', '__return_false' );

add_action( 'wp_default_scripts', function ( $scripts ) {
	if ( ! is_admin() ) {
		$scripts->remove( 'wc-cart-fragments' );
		$scripts->remove( 'js-cookie' );
		$scripts->remove( 'js-cookie-js' );
	}
}, 10 );

add_action( 'wp_default_styles', function ( $styles ) {
	if ( ! is_admin() ) {
		$styles->remove( 'woocommerce-layout' );
		$styles->remove( 'woocommerce-smallscreen' );
		$styles->remove( 'wc-blocks-style' );
		$styles->remove( 'wc-blocks-packages-style' );
		$styles->remove( 'wc-blocks-vendors-style' );
	}
}, 10 );

add_filter( 'woocommerce_product_reviews_enabled', '__return_false', 10, 1 );

add_action( 'init', function () {
	remove_post_type_support( 'product', 'comments' );
}, 10 );

add_action( 'init', function () {
	if ( ! function_exists( 'is_admin' ) || is_admin() ) {
		return;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'feed_links', 2 );
	remove_action( 'wp_head', 'feed_links_extra', 3 );
	remove_action( 'wp_head', 'rest_output_link_wp_head' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
}, 99 );
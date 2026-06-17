<?php

/**
 * Woo - add theme support
 */

add_action('after_setup_theme', 'woocommerce_support');
function woocommerce_support()
{
	add_theme_support('woocommerce');
}

/**
 * Woo - disable style woo
 */

if (class_exists('Woocommerce')) {
	// add_filter('woocommerce_enqueue_styles', '__return_empty_array');
}

/**
 * Woo - remove breadcrumb - remove sidebar
 */

remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);
remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);

/**
 * Woo - Add button add to cart - Ajax
 */

function woo_add_buy_now_button()
{
?>
	<script>
		jQuery(document).ready(function($) {
			// Custom add to cart button
			$("body").on("click", ".single_add_to_cart_button:not(.buynow)", function(e) {
				e.preventDefault();
				// Declaration
				var $thisbutton = $(this),
					$form = $thisbutton.closest('form.cart'),
					id = $thisbutton.val(),
					product_qty = $form.find('input[name=quantity]').val() || 1,
					variation_id = $form.find('input[name=variation_id]').val() || 0,
					product_id = $form.find('input[name=product_id]').val() || id;
				var data = {
					action: 'woocommerce_ajax_add_to_cart',
					product_id: product_id,
					product_sku: '',
					variation_id: variation_id,
					quantity: product_qty,
				};
				// console.log(data);
				$(document.body).trigger('adding_to_cart', [$thisbutton, data]);
				$.ajax({
					type: 'post',
					url: wc_add_to_cart_params.ajax_url,
					data: data,
					beforeSend: function(response) {
						$('.loading-bar').css({
							'width': '40%',
							'opacity': '1'
						});
						$thisbutton.addClass('disable loading');
					},
					complete: function(response) {
						$('.loading-bar').css({
							'width': '100%',
							'opacity': '1'
						});
						setTimeout(function() {
							$('.loading-bar').css({
								'width': '0',
								'opacity': '0'
							});
						}, 500);
						$thisbutton.removeClass('disable loading');
						$('.shopping-cart-wrapper, .overlay-blur').addClass('active');
					},
					success: function(response) {
						// console.log(response);
						if (response.error && response.product_url) {
							window.location = response.product_url;
							return;
						} else {
							$(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $thisbutton]);
						}
						// $(document.body).find('.minicart-item-wrapper').remove();
						// $(document.body).find('.minicart-a').append(response.fragments['.minicart-item-wrapper']);
						// $(document.body).find('.notification').remove();
						// $(document.body).find('.minicart-header').remove();
						// $(document.body).find('.minicart-content-box').prepend(response.fragments['shipcode']);
						// $(document.body).find('.mini-cart').append(response.fragments['notification']);
					},
				});
				return false;
			});
			// Custom buy now button
			$("body").on("click", ".buy_now_button", function(e) {
				e.preventDefault();
				if ($(this).hasClass('disable')) {
					return false;
				}
				$(this).addClass('disable loading');
				$('.single_add_to_cart_button').addClass('buynow')
				$('.single_add_to_cart_button').trigger('click');
			});
		});
	</script>
<?php
}

add_action('woocommerce_after_add_to_cart_button', 'woo_add_buy_now_button', 10);

add_action('wp_ajax_woocommerce_ajax_add_to_cart', 'woocommerce_ajax_add_to_cart');
add_action('wp_ajax_nopriv_woocommerce_ajax_add_to_cart', 'woocommerce_ajax_add_to_cart');

function woocommerce_ajax_add_to_cart()
{

	$product_id = apply_filters('woocommerce_add_to_cart_product_id', absint($_POST['product_id']));
	$quantity = empty($_POST['quantity']) ? 1 : wc_stock_amount($_POST['quantity']);
	$variation_id = absint($_POST['variation_id']);
	$passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity);
	$product_status = get_post_status($product_id);

	if ($passed_validation && WC()->cart->add_to_cart($product_id, $quantity, $variation_id) && 'publish' === $product_status) {

		do_action('woocommerce_ajax_added_to_cart', $product_id);

		if ('yes' === get_option('woocommerce_cart_redirect_after_add')) {
			wc_add_to_cart_message(array($product_id => $quantity), true);
		}

		WC_AJAX::get_refreshed_fragments();
	} else {

		$data = array(
			'error' => true,
			'product_url' => apply_filters('woocommerce_cart_redirect_after_error', get_permalink($product_id), $product_id)
		);

		echo wp_send_json($data);
	}

	wp_die();
}
/**
 * Woo - update count quantity cart
 */

add_filter('woocommerce_add_to_cart_fragments', 'wc_refresh_mini_cart_count');
function wc_refresh_mini_cart_count($fragments)
{
	ob_start();
	$items_count = WC()->cart->get_cart_contents_count();
?>
	<span class="count-cart">
		<?php echo $items_count ? $items_count : 0; ?>
	</span>
<?php
	$fragments['.count-cart'] = ob_get_clean();
	return $fragments;
}

/**
 * Woo - update mini cart
 */

add_filter('woocommerce_add_to_cart_fragments', 'wc_refresh_mini_cart');
function wc_refresh_mini_cart($fragments)
{
	ob_start();
?>
	<div class="mini-cart">
		<?php woocommerce_mini_cart(); ?>
	</div>
<?php
	$fragments['.mini-cart'] = ob_get_clean();
	return $fragments;
}


/**
 * Woo - change position product detail
 */

// remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
// remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
// remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50);

// add_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 5);
// woocommerce_template_variable_price
add_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 20);

// add_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 35);
// add_action('woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 40);


// /**
//  * Add custom sorting option to sort products by product category
//  */
// function custom_catalog_orderby_options($orderby_options)
// {
// 	$orderby_options['product_cat'] = __('Sort by Category', 'canhcamtheme');
// 	return $orderby_options;
// }
// add_filter('woocommerce_catalog_orderby', 'custom_catalog_orderby_options');

// /**
//  * Handle the sorting logic when 'product_cat' is selected
//  */
// function custom_handle_product_cat_orderby($sort_args)
// {
// 	if (isset($_GET['orderby']) && $_GET['orderby'] == 'product_cat') {
// 		// I need order by term_id depth second
// 		$sort_args['orderby'] = 'term_id';
// 		$sort_args['order'] = 'DESC';
// 		// terms 1
// 		$sort_args['term_id'] = 1;

// 	}
// 	return $sort_args;
// }
// add_filter('woocommerce_get_catalog_ordering_args', 'custom_handle_product_cat_orderby');

/**
 * Finds the matching product variation ID based on default attributes.
 *
 * @param WC_Product_Variable $product
 * @param array $default_attributes
 * @return int|false The variation ID or false if not found.
 */
function find_matching_product_variation_id($product, $default_attributes)
{
	foreach ($product->get_available_variations() as $variation) {
		$variation_id = $variation['variation_id'];
		$variation_obj = wc_get_product($variation_id);

		$match = true;
		foreach ($default_attributes as $attr => $value) {
			if ($variation_obj->get_attribute($attr) != $value) {
				$match = false;
				break;
			}
		}

		if ($match) {
			return $variation_id;
		}
	}

	return false;
}

/**
 * Hiển thị nút liên hệ Messenger khi sản phẩm không có giá
 */
add_action('woocommerce_single_product_summary', 'show_contact_button_when_no_price', 25);
function show_contact_button_when_no_price()
{
	global $product;
	
	// Kiểm tra nếu sản phẩm không có giá hoặc giá = 0
	$price = $product->get_price();
	
	if (empty($price) || $price == 0 || $price == '' || $price == null) {
		echo '<div class="product-no-price-contact">';
		echo '<a class="btn-solid btn-messenger-contact" href="https://m.me/459518083902897" target="_blank" rel="nofollow">';
		echo '<span>Liên hệ tư vấn</span>';
		echo '</a>';
		echo '</div>';
	}
}

/**
 * Ẩn nút Add to Cart khi sản phẩm không có giá
 */
add_filter('woocommerce_is_purchasable', 'hide_add_to_cart_when_no_price', 10, 2);
function hide_add_to_cart_when_no_price($is_purchasable, $product)
{
	$price = $product->get_price();
	
	// Nếu không có giá, không cho phép mua
	if (empty($price) || $price == 0 || $price == '' || $price == null) {
		$is_purchasable = false;
	}
	
	return $is_purchasable;
}

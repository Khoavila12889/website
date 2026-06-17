<?php

/**
 * The Template for displaying all single products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

get_header('shop'); ?>

<?php  // get_template_part("./modules/common/breadcrumb")
?>

<section class="global-breadcrumb">
	<div class="container">
		<?php
		// wp_nav_menu(array(
		// 	'theme_location' => 'header-product-menu',
		// 	'container' => false,
		// 	'menu_class' => 'nav-product-detail',
		// 	'menu_id' => 'nav-product-detail',
		// 	'walker' => new CleanMenuWalker()
		// ));
		$product_cat = get_the_terms(get_the_ID(), 'product_cat');
		// get parent name in category
		$parent = get_term($product_cat[0]->parent, 'product_cat');

		// get all child name in category parent
		$child = get_terms('product_cat', array(
			'parent' => $product_cat[0]->parent,
			'hide_empty' => false
		));
		?>
		<ul class="nav-product-detail">
			<?php
			foreach ($child as $item) :
				$choose_category_color = get_field('choose_category_color', $item);
				$custom_url = get_field('custom_url', $item);
			?>
				<li class="notranslate <?php echo $item->term_id == $product_cat[0]->term_id ? 'active' : ''; ?>" style="color: <?php echo $item->term_id == $product_cat[0]->term_id ? $choose_category_color : ''; ?>">
					<a href="<?php echo $custom_url ? $custom_url : get_term_link($item->term_id, 'product_cat'); ?>">
						<?php echo $item->name; ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<?php
/**
 * woocommerce_before_main_content hook.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 */
do_action('woocommerce_before_main_content');
?>

<?php while (have_posts()) : ?>
	<?php the_post(); ?>

	<?php wc_get_template_part('content', 'single-product'); ?>

<?php endwhile; // end of the loop.
?>



<?php
/**
 * woocommerce_after_main_content hook.
 *
 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action('woocommerce_after_main_content');
?>

<?php
/**
 * woocommerce_sidebar hook.
 *
 * @hooked woocommerce_get_sidebar - 10
 */
do_action('woocommerce_sidebar');
?>

<?php
get_footer('shop');

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
?>

<script>
	jQuery(document).ready(function($) {
		$(document).on('click', '.btn-spin.plus', function(e) {
			var input = $(this).prev('input');
			var val = parseInt(input.val());
			input.val(val + 1).change();
		});
		$(document).on('click', '.btn-spin.minus', function(e) {
			var input = $(this).next('input');
			var val = parseInt(input.val());
			if (val > 1) {
				input.val(val - 1).change();
			}
		});
	});
</script>

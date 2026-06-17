<?php

/**
 * The template for displaying product filter widget.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/product-filter.php
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined('ABSPATH') || exit;

?>

<div class="box-filter">
	<form method="get" action="<?php echo esc_url($form_action); ?>">
		<div class="mobile-only">
			<div class="filter-close btn-filter-close">
				<i class="fa-light fa-close"></i>
			</div>
		</div>
		<div class="box-head">
			<h2 class="box-title">
				<?php echo _e('Thương hiệu', 'canhcamtheme') ?>
			</h2>
		</div>
		<div class="box-body">
			<?php
			$tag_ID = get_queried_object()->term_id;
			// Get all product categories by tag_ID
			$product_categories = get_terms(array(
				'taxonomy' => 'product_cat',
				'hide_empty' => false,
				'parent' => $tag_ID,
			));

			// Loop through each category
			foreach ($product_categories as $category) {
				$choose_brand_color = get_field('choose_brand_color', $category);
				// Get subcategories
				$subcategories = get_terms(array(
					'taxonomy' => 'product_cat',
					'hide_empty' => false,
					'parent' => $category->term_id,
				));

				// Output category name and link
				echo '<div class="filter-block is-primary-' . $choose_brand_color . '">';
				echo '<div class="filter-block-head">';
				echo '<div class="filter-item">';
				echo '<input type="checkbox" name="brand-' . $category->term_id . '" id="filter-block-' . $category->term_id . '">';
				echo '<label for="filter-block-' . $category->term_id . '">' . $category->name . '</label>';
				echo '</div>';

				if ($subcategories) :
				echo '<div class="filter-block-toggle"><i class="fa-regular fa-chevron-down"></i></div>';
				endif;

				echo '</div>';

				if($subcategories) :
				echo '<div class="filter-block-body">';
				// Loop through subcategories
				foreach ($subcategories as $subcategory) {
					echo '<div class="filter-item">';
					echo '<input type="checkbox" name="brand-' . $subcategory->term_id . '" id="filter-block-' . $subcategory->term_id . '">';
					echo '<label for="filter-block-' . $subcategory->term_id . '">' . $subcategory->name . '</label>';
					echo '</div>';
				}
				echo '</div>';
				endif;

				echo '</div>';
			}
			?>

		</div>
		<div class="mobile-only">
			<div class="box-foot button">
				<button class="btn-solid is-gray" type="reset">
					<i class="fa-light fa-arrow-rotate-left"></i>
					<span><?php echo _e('Đặt lại', 'canhcamtheme') ?></span>
				</button>
				<a class="btn-solid btn-filter-close" href="javascript:;">
					<span><?php echo _e('Áp dụng', 'canhcamtheme') ?></span>
					<i class="fa-light fa-filter"></i>
				</a>
			</div>
		</div>
	</form>
</div>


<?php do_action('woocommerce_widget_price_filter_end', $args); ?>

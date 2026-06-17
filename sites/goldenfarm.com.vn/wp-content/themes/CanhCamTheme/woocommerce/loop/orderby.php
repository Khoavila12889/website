<?php

/**
 * Show options for ordering
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/orderby.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     3.6.0
 */

if (!defined('ABSPATH')) {
	exit;
}

?>
<div class="flex items-center flex-wrap gap-5 justify-between">
	<h1 class="site-title">
		<?php woocommerce_page_title(); ?>
	</h1>
	<div class="form-right flex items-center gap-2 justify-between w-full lg:w-auto">
		<!-- <div class="mobile-only">
			<a class="btn-solid btn-filter-open" href="javascript:;">
				<i class="fa-light fa-filter-list"></i>
				<span>
					<?php echo _e('Bộ lọc', 'canhcamtheme'); ?>
				</span>
			</a>
		</div> -->
		<form class="woocommerce-ordering" method="get">
			<div class="form-group product-sort">
				<label>
					<?php echo _e('Lọc theo', 'canhcamtheme'); ?>
				</label>
				<select name="orderby" class="orderby" aria-label="<?php esc_attr_e('Shop order', 'woocommerce'); ?>">
					<?php foreach ($catalog_orderby_options as $id => $name) : ?>
						<option value="<?php echo esc_attr($id); ?>" <?php selected($orderby, $id); ?>><?php echo esc_html($name); ?></option>
					<?php endforeach; ?>
				</select>
				<input type="hidden" name="paged" value="1" />
				<?php wc_query_string_form_fields(null, array('orderby', 'submit', 'paged', 'product-page')); ?>
			</div>
		</form>
	</div>
</div>

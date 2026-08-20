<?php

/**
 * The template for displaying product content in the single-product.php template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined('ABSPATH') || exit;

global $product;

/**
 * Hook: woocommerce_before_single_product.
 *
 * @hooked woocommerce_output_all_notices - 10
 */
do_action('woocommerce_before_single_product');

if (post_password_required()) {
	echo get_the_password_form(); // WPCS: XSS ok.
	return;
}

$product_detail_information_other = get_field("product_detail_information_other");
$product_detail_related_other = get_field("product_detail_related_other");
$product_detail_choose_delicious_recipe = get_field("product_detail_choose_delicious_recipe");


?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class('', $product); ?>>
	<section class="product-section section overflow-hidden">
		<div class="container">
			<div class="box-product-detail">
				<div class="row -mt-8">
					<div class="col w-full mt-8 lg:w-1/2">
						<?php
						/**
						 * Hook: woocommerce_before_single_product_summary.
						 *
						 * @hooked woocommerce_show_product_sale_flash - 10
						 * @hooked woocommerce_show_product_images - 20
						 */

						do_action('woocommerce_before_single_product_summary');

						?>
					</div>
					<div class="col w-full mt-8 lg:w-1/2">
						<div class="product-detail-content space-y-8">
							<?php
							/**
							 * woocommerce_single_product_summary hook.
							 *
							 * @hooked woocommerce_template_single_title - 5
							 * @hooked woocommerce_template_single_rating - 10
							 * @hooked woocommerce_template_single_excerpt - 10
							 * @hooked woocommerce_template_single_price - 20
							 * @hooked woocommerce_template_single_add_to_cart - 30
							 * @hooked woocommerce_template_single_meta - 40
							 * @hooked woocommerce_template_single_sharing - 50
							 * @hooked WC_Structured_Data::generate_product_data() - 60
							 */

							do_action('woocommerce_single_product_summary');

							?>

							<?php
							$product_id = get_the_ID();
							$product_cat = get_the_terms($product_id, 'product_cat');

							// Check if product has categories
							if ($product_cat && !is_wp_error($product_cat)) {

								// Get product other
								$args_product_other = array(
									'post_type' => 'product',
									'posts_per_page' => 10,
									'orderby' => 'rand',
									'publish_status' => 'published',
									'post__not_in' => array($product_id),
									'tax_query' => array(
										array(
											'taxonomy' => 'product_cat',
											'field' => 'slug',
											'terms' => $product_cat[0]->slug,
										),
									),
								);

								$loop_product_other = new WP_Query($args_product_other);

								if ($loop_product_other->have_posts()) :
							?>
									<div class="product-suggest">
										<h2 class="header-40 font-normal text-primary-new-1">
											<?php echo esc_html__('Sản phẩm gợi ý', 'canhcamtheme'); ?>
										</h2>
										<div class="swiper-relative is-linear mt-8 pro-auto-16-slider product-suggest-slider pb-15 lg:pb-0 equal-height">
											<div class="swiper">
												<div class="swiper-wrapper">
													<?php while ($loop_product_other->have_posts()) : $loop_product_other->the_post(); ?>
														<?php if (get_the_ID() == $product_id) continue; ?>
														<div class="swiper-slide">
															<?php wc_get_template_part('content-product', '', get_the_ID()); ?>
														</div>
													<?php endwhile; ?>
												</div>
											</div>
											<div class="mobile-only">
												<div class="swiper-pagination"></div>
											</div>
											<div class="desktop-only">
												<div class="swiper-button is-abs">
													<div class="button-prev"><i class="fa-light fa-chevron-left"></i></div>
													<div class="button-next"><i class="fa-light fa-chevron-right"></i></div>
												</div>
											</div>
										</div>
									</div>

							<?php
								endif;
								wp_reset_postdata(); // Reset the query to the main loop
							}
							?>


						</div>
					</div>
				</div>
			</div>
			<?php if ($product_detail_information_other) : ?>
				<div class="box-product-accordion mt-10 lg:mt-13">
					<div class="accordion-item active">
						<div class="accordion-title">
							<h2 class="header-40 font-normal text-primary-new-1">
								<?php echo _e('Thông tin sản phẩm', 'canhcamtheme') ?>
							</h2>
							<i class="accordion-toggle fa-light fa-chevron-down"></i>
						</div>
						<div class="accordion-content" style="display: block;">
							<div class="full-content"> 
								<?php echo apply_filters('the_content', $product->get_description()); //xuống dòng mô tả
								?>  
							</div> 
						</div>
					</div>
					<?php foreach ($product_detail_information_other as $key => $item) : ?>
						<div class="accordion-item">
							<div class="accordion-title">
								<h2 class="header-40 font-normal text-primary-new-1">
									<?php echo $item["title"] ?>
								</h2>
								<i class="accordion-toggle fa-light fa-chevron-down"></i>
							</div>
							<div class="accordion-content">
								<div class="full-content">
									<?php echo $item["content"] ?>
								</div>
							</div>
						</div>
					<?php endforeach ?>
				</div>
			<?php endif; ?>

			<?php if ($product_detail_related_other) : ?>
				<div class="box-product-related mt-10 lg:mt-15 equal-height">
					<h2 class="site-title">
						<?php echo _e('Sản phẩm cùng hương vị', 'canhcamtheme') ?>
					</h2>
					<div class="swiper-relative pro-auto-16-slider mt-10 pb-15 is-linear lg:pb-0">
						<div class="swiper">
							<div class="swiper-wrapper">
								<?php foreach ($product_detail_related_other as $productRelated) : ?>
									<?php
									$related_id = is_object($productRelated) ? $productRelated->ID : $productRelated;
									if (!$related_id || $related_id == $product_id) continue;
									$post_object = get_post($related_id);
									if (!$post_object || $post_object->post_status !== 'publish') continue;
									setup_postdata($GLOBALS['post'] = &$post_object);
									$GLOBALS['product'] = wc_get_product($related_id);
									?>
									<div class="swiper-slide">
										<?php wc_get_template_part('content-product'); ?>
									</div>
								<?php endforeach;
								wp_reset_postdata(); ?>
							</div>
						</div>
						<div class="mobile-only">
							<div class="swiper-pagination"></div>
						</div>
						<div class="desktop-only">
							<div class="swiper-button is-abs">
								<div class="button-prev"><i class="fa-thin fa-chevron-left"></i></div>
								<div class="button-next"><i class="fa-thin fa-chevron-right"></i></div>
							</div>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ($product_detail_choose_delicious_recipe) : ?>
				<div class="box-delicious-recipe mt-10 lg:mt-15">
					<h2 class="site-title">
						<?php echo _e('Công thức món ngon', 'canhcamtheme') ?>
					</h2>
					<div class="swiper-relative pt-8 pb-3 four-slider">
						<div class="swiper">
							<div class="swiper-wrapper">
								<?php foreach ($product_detail_choose_delicious_recipe as $item) : ?>
									<div class="swiper-slide">
										<?php get_template_part('modules/news/delicious_recipe_item', '', array('idPost' => $item->ID, 'showCategory' => true)); ?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="mobile-only">
							<div class="swiper-pagination"></div>
						</div>
						<div class="desktop-only">
							<div class="swiper-button is-abs">
								<div class="button-prev"><i class="fa-thin fa-chevron-left"></i></div>
								<div class="button-next"><i class="fa-thin fa-chevron-right"></i></div>
							</div>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
	/**
	 * Hook: woocommerce_after_single_product_summary.
	 *
	 * @hooked woocommerce_output_product_data_tabs - 10
	 * @hooked woocommerce_upsell_display - 15
	 */
	do_action('woocommerce_after_single_product_summary');
	?>


</div>

<?php do_action('woocommerce_after_single_product'); ?>

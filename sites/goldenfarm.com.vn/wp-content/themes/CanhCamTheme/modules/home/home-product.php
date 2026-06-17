<?php
$home_product_title = get_field('home_product_title', get_the_ID());
$home_choose_product = get_field('home_choose_product', get_the_ID());
if ($home_choose_product) :
?>

	<section class="home-products section-t overflow-hidden">
		<div class="container">
			<h2 class="site-title text-center">
				<?php echo $home_product_title ?>
			</h2>
		</div>
		<div class="swiper-relative home-products-slider pro-auto-16-slider pb-15 mt-10 is-linear lg:pb-0 lg:mt-16 equal-height">
			<div class="swiper">
				<div class="swiper-wrapper">
					<?php foreach ($home_choose_product as $product_item) : ?>
						<?php // if ($product_item->ID == $product_id) continue; ?>
						<div class="swiper-slide">
							<?php $post_object = get_post($product_item->ID); ?>
							<?php
							setup_postdata($GLOBALS['post'] = &$post_object);
							wc_get_template_part('content-product', '', $product_item->ID); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.PHP.DisallowMultipleAssignments.Found
							wp_reset_postdata();
							?>
						</div>
					<?php endforeach;  ?>
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
	</section>
<?php endif; ?>

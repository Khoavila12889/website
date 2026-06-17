<?php
$home_choose_category = get_field('home_choose_category', get_the_ID());
if ($home_choose_category) :
?>
	<section class="home-category overflow-hidden">
		<div class="swiper-relative home-category-slider pro-auto-16-slider pb-15 is-linear lg:pb-0">
			<div class="swiper">
				<div class="swiper-wrapper">
					<?php foreach ($home_choose_category as $item) : ?>
						<?php
						// get product category thumbnail
						$category_thumbnail_url = wp_get_attachment_url(get_term_meta($item->term_id, 'thumbnail_id', true));
						$choose_category_color = get_field('choose_category_color', $item->taxonomy . '_' . $item->term_id);
						$custom_url = get_field('custom_url', $item->taxonomy . '_' . $item->term_id);
						?>
						<div class="swiper-slide">
							<a class="home-category-item" style="background: <?php echo $choose_category_color ?>" href="<?php echo $custom_url ? $custom_url : get_term_link($item->term_id) ?>">
								<span class="image img-cover">
									<img class="lozad" data-src="<?php echo $category_thumbnail_url ?>" loading="lazy" alt="<?php echo $item->title ?>"></span>
								<span class="title"><?php echo $item->name ?></span>
							</a>
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
	</section>
<?php endif; ?>
